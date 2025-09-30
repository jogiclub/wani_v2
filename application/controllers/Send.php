<?php
/**
 * 파일 위치: application/controllers/Send.php
 * 역할: 문자 발송 기능을 처리하는 컨트롤러
 */
defined('BASEPATH') or exit('No direct script access allowed');

class Send extends MY_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model('Send_model');
		$this->load->model('Member_model');
		$this->load->library('session');
		$this->load->helper('url');
	}

	/**
	 * 문자 발송 팝업 표시
	 */
	public function popup()
	{
		$user_id = $this->session->userdata('user_id');
		if (!$user_id) {
			redirect('login');
			return;
		}

		// 선택된 회원 번호들 받기
		$member_ids = $this->input->post('member_ids');
		if (empty($member_ids)) {
			$this->session->set_flashdata('error', '선택된 회원이 없습니다.');
			redirect($_SERVER['HTTP_REFERER']);
			return;
		}

		// 현재 조직 정보 가져오기
		$current_org_id = $this->input->cookie('activeOrg');
		if (!$current_org_id) {
			$this->session->set_flashdata('error', '조직 정보를 찾을 수 없습니다.');
			redirect($_SERVER['HTTP_REFERER']);
			return;
		}

		// 선택된 회원들의 정보 조회
		$selected_members = $this->Send_model->get_selected_members($member_ids, $current_org_id);

		// 발신번호 목록 조회 (인증 상태 포함)
		$sender_numbers = $this->Send_model->get_sender_numbers_with_auth($current_org_id);

		// 미리 등록된 문구 목록 조회
		$message_templates = $this->Send_model->get_message_templates($current_org_id);

		$data = array(
			'selected_members' => $selected_members,
			'sender_numbers' => $sender_numbers,
			'message_templates' => $message_templates,
			'org_id' => $current_org_id
		);

		$this->load->view('send/popup', $data);
	}


	/**
	 * 문자 발송 처리
	 */
	public function send_message()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$user_id = $this->session->userdata('user_id');
		if (!$user_id) {
			echo json_encode(array('success' => false, 'message' => '로그인이 필요합니다.'));
			return;
		}

		$send_type = $this->input->post('send_type');
		$sender_number = $this->input->post('sender_number');
		$sender_name = $this->input->post('sender_name');
		$message_content = $this->input->post('message_content');
		$receiver_numbers = $this->input->post('receiver_numbers');
		$org_id = $this->input->post('org_id');

		// 필수 값 검증
		if (empty($send_type) || empty($sender_number) || empty($message_content) || empty($receiver_numbers)) {
			echo json_encode(array('success' => false, 'message' => '필수 정보가 누락되었습니다.'));
			return;
		}

		// 조직 권한 확인
		if (!$this->check_org_access($org_id)) {
			echo json_encode(array('success' => false, 'message' => '권한이 없습니다.'));
			return;
		}

		$receiver_count = count($receiver_numbers);

		// 발송 전 잔액 차감
		$deduction_result = $this->Send_model->deduct_balance($org_id, $send_type, $receiver_count);

		if (!$deduction_result['success']) {
			echo json_encode(array(
				'success' => false,
				'message' => $deduction_result['message']
			));
			return;
		}

		$success_count = 0;
		$fail_count = 0;
		$fail_messages = array();

		foreach ($receiver_numbers as $receiver_data) {
			$receiver_number = $receiver_data['phone'];
			$receiver_name = $receiver_data['name'];
			$member_idx = $receiver_data['member_idx'];

			// 문자 발송 로그 저장
			$send_data = array(
				'org_id' => $org_id,
				'sender_id' => $user_id,
				'member_idx' => $member_idx,
				'send_type' => $send_type,
				'sender_number' => $sender_number,
				'sender_name' => $sender_name,
				'receiver_number' => $receiver_number,
				'receiver_name' => $receiver_name,
				'message_content' => $message_content,
				'send_status' => 'pending',
				'send_date' => date('Y-m-d H:i:s')
			);

			// 실제 문자 발송 처리
			$send_result = $this->process_message_send($send_type, $sender_number, $receiver_number, $message_content);

			if ($send_result['success']) {
				$send_data['send_status'] = 'success';
				$send_data['result_message'] = $send_result['message'];
				$success_count++;
			} else {
				$send_data['send_status'] = 'failed';
				$send_data['result_message'] = $send_result['message'];
				$fail_count++;
				$fail_messages[] = $receiver_name . ': ' . $send_result['message'];
			}

			// 발송 로그 저장
			$this->Send_model->save_send_log($send_data);
		}

		$total_count = $success_count + $fail_count;
		$response_message = "총 {$total_count}건 중 {$success_count}건 성공, {$fail_count}건 실패";

		if ($fail_count > 0) {
			$response_message .= "\n실패 내역:\n" . implode("\n", $fail_messages);
		}

		// 최신 잔액 조회
		$new_balance = $this->Send_model->get_org_total_balance($org_id);
		$sms_available = $this->Send_model->get_available_balance_by_type($org_id, 'sms');
		$lms_available = $this->Send_model->get_available_balance_by_type($org_id, 'lms');
		$mms_available = $this->Send_model->get_available_balance_by_type($org_id, 'mms');
		$kakao_available = $this->Send_model->get_available_balance_by_type($org_id, 'kakao');

		echo json_encode(array(
			'success' => true,
			'message' => $response_message,
			'success_count' => $success_count,
			'fail_count' => $fail_count,
			'balance' => $new_balance,
			'available_counts' => array(
				'sms' => $sms_available,
				'lms' => $lms_available,
				'mms' => $mms_available,
				'kakao' => $kakao_available
			)
		));
	}

	/**
	 * 실제 문자 발송 처리 (API 연동)
	 */
	private function process_message_send($send_type, $sender_number, $receiver_number, $message_content)
	{
		// 여기에 실제 문자 발송 API 연동 코드를 구현
		// 현재는 임시로 성공 처리

		try {
			// SMS/LMS/MMS/카카오톡 API 연동 로직
			switch ($send_type) {
				case 'sms':
					// SMS 발송 API 호출
					break;
				case 'lms':
					// LMS 발송 API 호출
					break;
				case 'mms':
					// MMS 발송 API 호출
					break;
				case 'kakao':
					// 카카오톡 발송 API 호출
					break;
				default:
					return array('success' => false, 'message' => '지원하지 않는 발송 타입입니다.');
			}

			// 임시로 성공 처리
			return array('success' => true, 'message' => '발송 완료');

		} catch (Exception $e) {
			return array('success' => false, 'message' => '발송 실패: ' . $e->getMessage());
		}
	}

	/**
	 * 발송 이력 조회
	 */
	public function get_send_history()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$org_id = $this->input->post('org_id');
		$page = intval($this->input->post('page')) ?: 1;
		$per_page = 20;

		$history = $this->Send_model->get_send_history($org_id, $page, $per_page);

		echo json_encode($history);
	}

	/**
	 * 메시지 템플릿 관리
	 */
	public function manage_templates()
	{
		$user_id = $this->session->userdata('user_id');
		if (!$user_id) {
			redirect('login');
			return;
		}

		$org_id = $this->input->cookie('activeOrg');
		if (!$org_id) {
			redirect('dashboard');
			return;
		}

		$data = array(
			'templates' => $this->Send_model->get_message_templates($org_id),
			'org_id' => $org_id
		);

		$this->load->view('send/template_manage', $data);
	}

	/**
	 * 메시지 템플릿 저장
	 */
	public function save_template()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$user_id = $this->session->userdata('user_id');
		$org_id = $this->input->post('org_id');
		$template_name = $this->input->post('template_name');
		$template_content = $this->input->post('template_content');

		if (!$org_id || !$template_name || !$template_content) {
			echo json_encode(array('success' => false, 'message' => '필수 정보가 누락되었습니다.'));
			return;
		}

		$data = array(
			'org_id' => $org_id,
			'template_name' => $template_name,
			'template_content' => $template_content,
			'created_by' => $user_id,
			'created_date' => date('Y-m-d H:i:s')
		);

		$result = $this->Send_model->save_message_template($data);

		if ($result) {
			echo json_encode(array('success' => true, 'message' => '템플릿이 저장되었습니다.'));
		} else {
			echo json_encode(array('success' => false, 'message' => '저장에 실패했습니다.'));
		}
	}

	/**
	 * 메시지 템플릿 수정
	 */
	public function update_template()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$template_idx = $this->input->post('template_idx');
		$template_name = $this->input->post('template_name');
		$template_content = $this->input->post('template_content');
		$org_id = $this->input->post('org_id');

		if (!$template_idx || !$template_name || !$template_content || !$org_id) {
			echo json_encode(array('success' => false, 'message' => '필수 정보가 누락되었습니다.'));
			return;
		}

		$data = array(
			'template_name' => $template_name,
			'template_content' => $template_content,
			'updated_date' => date('Y-m-d H:i:s')
		);

		$result = $this->Send_model->update_message_template($template_idx, $data);

		if ($result) {
			echo json_encode(array('success' => true, 'message' => '템플릿이 수정되었습니다.'));
		} else {
			echo json_encode(array('success' => false, 'message' => '수정에 실패했습니다.'));
		}
	}

	/**
	 * 메시지 템플릿 삭제
	 */
	public function delete_template()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$template_idx = $this->input->post('template_idx');
		$org_id = $this->input->post('org_id');

		if (!$template_idx || !$org_id) {
			echo json_encode(array('success' => false, 'message' => '필수 정보가 누락되었습니다.'));
			return;
		}

		$result = $this->Send_model->delete_message_template($template_idx, $org_id);

		if ($result) {
			echo json_encode(array('success' => true, 'message' => '템플릿이 삭제되었습니다.'));
		} else {
			echo json_encode(array('success' => false, 'message' => '삭제에 실패했습니다.'));
		}
	}

	/**
	 * 발신번호 관리 페이지
	 */
	public function manage_senders()
	{
		$user_id = $this->session->userdata('user_id');
		if (!$user_id) {
			redirect('login');
			return;
		}

		$org_id = $this->input->cookie('activeOrg');
		if (!$org_id) {
			redirect('dashboard');
			return;
		}

		$data = array(
			'senders' => $this->Send_model->get_sender_numbers($org_id),
			'org_id' => $org_id
		);

		$this->load->view('send/sender_manage', $data);
	}

	/**
	 * 발신번호 저장
	 */
	public function save_sender()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$user_id = $this->session->userdata('user_id');
		$org_id = $this->input->post('org_id');
		$sender_name = $this->input->post('sender_name');
		$sender_number = $this->input->post('sender_number');
		$is_default = $this->input->post('is_default') ? 'Y' : 'N';

		if (!$org_id || !$sender_name || !$sender_number) {
			echo json_encode(array('success' => false, 'message' => '필수 정보가 누락되었습니다.'));
			return;
		}

		// 기본 발신번호로 설정하는 경우 다른 번호들의 기본값 해제
		if ($is_default === 'Y') {
			$this->Send_model->set_default_sender_number(0, $org_id); // 모든 기본값 해제
		}

		$data = array(
			'org_id' => $org_id,
			'sender_name' => $sender_name,
			'sender_number' => $sender_number,
			'is_default' => $is_default,
			'created_by' => $user_id,
			'created_date' => date('Y-m-d H:i:s')
		);

		$result = $this->Send_model->save_sender_number($data);

		if ($result) {
			echo json_encode(array('success' => true, 'message' => '발신번호가 저장되었습니다.'));
		} else {
			echo json_encode(array('success' => false, 'message' => '저장에 실패했습니다.'));
		}
	}

	/**
	 * 발신번호 수정
	 */
	public function update_sender()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$sender_idx = $this->input->post('sender_idx');
		$sender_name = $this->input->post('sender_name');
		$sender_number = $this->input->post('sender_number');
		$is_default = $this->input->post('is_default') ? 'Y' : 'N';
		$org_id = $this->input->post('org_id');

		if (!$sender_idx || !$sender_name || !$sender_number || !$org_id) {
			echo json_encode(array('success' => false, 'message' => '필수 정보가 누락되었습니다.'));
			return;
		}

		// 기본 발신번호로 설정하는 경우 다른 번호들의 기본값 해제
		if ($is_default === 'Y') {
			$this->Send_model->set_default_sender_number($sender_idx, $org_id);
		}

		$data = array(
			'sender_name' => $sender_name,
			'sender_number' => $sender_number,
			'is_default' => $is_default,
			'updated_date' => date('Y-m-d H:i:s')
		);

		$result = $this->Send_model->update_sender_number($sender_idx, $data);

		if ($result) {
			echo json_encode(array('success' => true, 'message' => '발신번호가 수정되었습니다.'));
		} else {
			echo json_encode(array('success' => false, 'message' => '수정에 실패했습니다.'));
		}
	}

	/**
	 * 발신번호 삭제
	 */
	public function delete_sender()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$sender_idx = $this->input->post('sender_idx');
		$org_id = $this->input->post('org_id');

		if (!$sender_idx || !$org_id) {
			echo json_encode(array('success' => false, 'message' => '필수 정보가 누락되었습니다.'));
			return;
		}

		$result = $this->Send_model->delete_sender_number($sender_idx, $org_id);

		if ($result) {
			echo json_encode(array('success' => true, 'message' => '발신번호가 삭제되었습니다.'));
		} else {
			echo json_encode(array('success' => false, 'message' => '삭제에 실패했습니다.'));
		}
	}

	/**
	 * 기본 발신번호 설정
	 */
	public function set_default_sender()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$sender_idx = $this->input->post('sender_idx');
		$org_id = $this->input->post('org_id');

		if (!$sender_idx || !$org_id) {
			echo json_encode(array('success' => false, 'message' => '필수 정보가 누락되었습니다.'));
			return;
		}

		$result = $this->Send_model->set_default_sender_number($sender_idx, $org_id);

		if ($result) {
			echo json_encode(array('success' => true, 'message' => '기본 발신번호가 설정되었습니다.'));
		} else {
			echo json_encode(array('success' => false, 'message' => '설정에 실패했습니다.'));
		}
	}

	/**
	 * 발송 통계 조회
	 */
	public function get_statistics()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$org_id = $this->input->post('org_id');
		$start_date = $this->input->post('start_date');
		$end_date = $this->input->post('end_date');

		if (!$org_id) {
			echo json_encode(array('success' => false, 'message' => '조직 정보가 필요합니다.'));
			return;
		}

		$statistics = $this->Send_model->get_send_statistics($org_id, $start_date, $end_date);

		echo json_encode(array(
			'success' => true,
			'data' => $statistics
		));
	}

	/**
	 * 충전 패키지 목록 조회
	 */
	public function get_charge_packages()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$packages = $this->Send_model->get_charge_packages();

		echo json_encode(array(
			'success' => true,
			'packages' => $packages
		));
	}

	/**
	 * 조직 문자 잔액 조회
	 */
	public function get_org_balance()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$org_id = $this->input->post('org_id');

		if (!$org_id) {
			echo json_encode(array('success' => false, 'message' => '조직 정보가 필요합니다.'));
			return;
		}

		// 전체 잔액 조회
		$total_balance = $this->Send_model->get_org_total_balance($org_id);

		// 발송 타입별 사용 가능 건수 조회
		$sms_available = $this->Send_model->get_available_balance_by_type($org_id, 'sms');
		$lms_available = $this->Send_model->get_available_balance_by_type($org_id, 'lms');
		$mms_available = $this->Send_model->get_available_balance_by_type($org_id, 'mms');
		$kakao_available = $this->Send_model->get_available_balance_by_type($org_id, 'kakao');

		// 잔액이 있는 패키지의 단가 조회
		$package_prices = $this->Send_model->get_available_package_prices($org_id);

		echo json_encode(array(
			'success' => true,
			'balance' => $total_balance,
			'available_counts' => array(
				'sms' => $sms_available,
				'lms' => $lms_available,
				'mms' => $mms_available,
				'kakao' => $kakao_available
			),
			'package_prices' => $package_prices
		));
	}

	/**
	 * 문자 충전 처리
	 */
	public function charge_sms()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$user_id = $this->session->userdata('user_id');

		if (!$user_id) {
			echo json_encode(array('success' => false, 'message' => '로그인이 필요합니다.'));
			return;
		}

		$org_id = $this->input->post('org_id');
		$package_idx = $this->input->post('package_idx');
		$charge_amount = $this->input->post('charge_amount');

		if (!$org_id || !$package_idx || !$charge_amount) {
			echo json_encode(array('success' => false, 'message' => '필수 정보가 누락되었습니다.'));
			return;
		}

		// 조직 권한 확인
		if (!$this->check_org_access($org_id)) {
			echo json_encode(array('success' => false, 'message' => '권한이 없습니다.'));
			return;
		}

		// 충전 처리
		$result = $this->Send_model->charge_sms($org_id, $user_id, $package_idx, $charge_amount);

		if ($result) {
			// 충전 후 잔액 조회
			$new_balance = $this->Send_model->get_org_total_balance($org_id);

			// 발송 타입별 사용 가능 건수 조회
			$sms_available = $this->Send_model->get_available_balance_by_type($org_id, 'sms');
			$lms_available = $this->Send_model->get_available_balance_by_type($org_id, 'lms');
			$mms_available = $this->Send_model->get_available_balance_by_type($org_id, 'mms');
			$kakao_available = $this->Send_model->get_available_balance_by_type($org_id, 'kakao');

			// 잔액이 있는 패키지의 단가 조회
			$package_prices = $this->Send_model->get_available_package_prices($org_id);

			echo json_encode(array(
				'success' => true,
				'message' => number_format($charge_amount) . '원이 충전되었습니다.',
				'balance' => $new_balance,
				'available_counts' => array(
					'sms' => $sms_available,
					'lms' => $lms_available,
					'mms' => $mms_available,
					'kakao' => $kakao_available
				),
				'package_prices' => $package_prices
			));
		} else {
			echo json_encode(array('success' => false, 'message' => '충전에 실패했습니다.'));
		}
	}

	/**
	 * 충전 내역 조회
	 */
	public function get_charge_history()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$org_id = $this->input->post('org_id');
		$page = intval($this->input->post('page')) ?: 1;
		$per_page = intval($this->input->post('per_page')) ?: 10;

		if (!$org_id) {
			echo json_encode(array('success' => false, 'message' => '조직 정보가 필요합니다.'));
			return;
		}

		$history = $this->Send_model->get_charge_history($org_id, $page, $per_page);

		echo json_encode(array(
			'success' => true,
			'data' => $history
		));
	}

	/**
	 * 발신번호 목록 조회 (AJAX)
	 */
	public function get_sender_list()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$org_id = $this->input->post('org_id');

		if (!$org_id) {
			echo json_encode(array('success' => false, 'message' => '조직 정보가 필요합니다.'));
			return;
		}

		$senders = $this->Send_model->get_sender_numbers_with_auth($org_id);

		echo json_encode(array(
			'success' => true,
			'senders' => $senders
		));
	}

	/**
	 * 발신번호 인증번호 발송
	 */
	public function send_auth_code()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$sender_idx = $this->input->post('sender_idx');
		$sender_number = $this->input->post('sender_number');
		$org_id = $this->input->post('org_id');

		if (!$sender_idx || !$sender_number || !$org_id) {
			echo json_encode(array('success' => false, 'message' => '필수 정보가 누락되었습니다.'));
			return;
		}

		// 6자리 인증번호 생성
		$auth_code = sprintf('%06d', mt_rand(0, 999999));

		// 인증번호 만료 시간 설정 (5분)
		$expires = date('Y-m-d H:i:s', strtotime('+5 minutes'));

		// DB에 인증번호 저장
		$result = $this->Send_model->save_auth_code($sender_idx, $auth_code, $expires);

		if ($result) {
			// 실제로는 SMS API를 통해 인증번호 발송
			// 현재는 임시로 alert로 표시하도록 응답
			echo json_encode(array(
				'success' => true,
				'message' => '인증번호가 발송되었습니다.',
				'auth_code' => $auth_code,
				'sender_number' => $sender_number
			));
		} else {
			echo json_encode(array('success' => false, 'message' => '인증번호 발송에 실패했습니다.'));
		}
	}

	/**
	 * 발신번호 인증 확인
	 */
	public function verify_auth_code()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$sender_idx = $this->input->post('sender_idx');
		$auth_code = $this->input->post('auth_code');
		$org_id = $this->input->post('org_id');

		if (!$sender_idx || !$auth_code || !$org_id) {
			echo json_encode(array('success' => false, 'message' => '필수 정보가 누락되었습니다.'));
			return;
		}

		// 인증번호 확인
		$verification = $this->Send_model->verify_auth_code($sender_idx, $auth_code);

		if ($verification['success']) {
			echo json_encode(array(
				'success' => true,
				'message' => '인증이 완료되었습니다.'
			));
		} else {
			echo json_encode(array(
				'success' => false,
				'message' => $verification['message']
			));
		}
	}


	/**
	 * 주소록 저장
	 */
	public function save_address_book()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$user_id = $this->session->userdata('user_id');
		$org_id = $this->input->post('org_id');
		$address_book_name = $this->input->post('address_book_name');
		$member_list = $this->input->post('member_list');

		if (!$org_id || !$address_book_name || empty($member_list)) {
			echo json_encode(array('success' => false, 'message' => '필수 정보가 누락되었습니다.'));
			return;
		}

		$data = array(
			'org_id' => $org_id,
			'address_book_name' => $address_book_name,
			'member_list' => json_encode($member_list),
			'created_by' => $user_id,
			'created_date' => date('Y-m-d H:i:s')
		);

		$result = $this->Send_model->save_address_book($data);

		if ($result) {
			echo json_encode(array(
				'success' => true,
				'message' => '주소록이 저장되었습니다.',
				'address_book_idx' => $this->db->insert_id()
			));
		} else {
			echo json_encode(array('success' => false, 'message' => '저장에 실패했습니다.'));
		}
	}

	/**
	 * 주소록 목록 조회
	 */
	public function get_address_book_list()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$user_id = $this->session->userdata('user_id');
		$org_id = $this->input->post('org_id');

		if (!$org_id) {
			echo json_encode(array('success' => false, 'message' => '조직 정보가 필요합니다.'));
			return;
		}

		$address_books = $this->Send_model->get_address_book_list($org_id, $user_id);

		echo json_encode(array(
			'success' => true,
			'address_books' => $address_books
		));
	}

	/**
	 * 주소록 삭제
	 */
	public function delete_address_book()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$address_book_idx = $this->input->post('address_book_idx');
		$org_id = $this->input->post('org_id');

		if (!$address_book_idx || !$org_id) {
			echo json_encode(array('success' => false, 'message' => '필수 정보가 누락되었습니다.'));
			return;
		}

		$result = $this->Send_model->delete_address_book($address_book_idx, $org_id);

		if ($result) {
			echo json_encode(array('success' => true, 'message' => '주소록이 삭제되었습니다.'));
		} else {
			echo json_encode(array('success' => false, 'message' => '삭제에 실패했습니다.'));
		}
	}

	public function bulk_edit_popup()
	{
		$this->load->view('send/bulk_edit_popup');
	}

}
