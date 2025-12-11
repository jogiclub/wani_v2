<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Org extends My_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->library('session');
		$this->load->helper('url');
		$this->load->model('User_model');
		$this->load->model('Org_model');
		$this->load->helper('file');
		$this->load->library('upload');
		$this->load->library('email');

		$this->check_menu_access('ORG_SETTING');
	}

	public function index() {


		$user_id = $this->session->userdata('user_id');

		// 헤더 데이터 준비
		$header_data = $this->prepare_header_data();
		if (empty($header_data['user_orgs'])) {
			redirect('dashboard');
			return;
		}

		$data = $header_data;

		// POST로 조직 변경 요청 처리 (My_Controller의 메소드 사용)
		$this->handle_org_change($data);

		$currentOrgId = $data['current_org']['org_id'];

		// 조직 관리 권한 확인 - 최소 레벨 8 이상 필요
		$user_level = $this->User_model->get_org_user_level($user_id, $currentOrgId);
		if ($user_level < 8 && $this->session->userdata('master_yn') !== 'Y') {
			$this->handle_access_denied('조직 설정을 관리할 권한이 없습니다.');
			return;
		}

		// 선택된 조직의 상세 정보 가져오기
		$data['selected_org_detail'] = $this->Org_model->get_org_detail_by_id($currentOrgId);

		// 조직의 최고관리자 정보 가져오기
		$data['org_admin'] = $this->Org_model->get_org_admin($currentOrgId);

		$this->load->view('org_setting', $data);
	}

	/**
	 * 파일 위치: application/controllers/Org.php
	 * 역할: 조직 정보 업데이트 (조직장 필드 추가)
	 */
	public function update_org_info() {
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$org_id = $this->input->post('org_id');
		$org_name = $this->input->post('org_name');
		$org_type = $this->input->post('org_type');
		$org_rep = $this->input->post('org_rep');
		$org_desc = $this->input->post('org_desc');
		$leader_name = $this->input->post('leader_name');
		$new_name = $this->input->post('new_name');
		$auto_message = $this->input->post('auto_message');

		// 직위/직분, 직책, 타임라인 데이터 처리
		$position_names = $this->input->post('position_names');
		$duty_names = $this->input->post('duty_names');
		$timeline_names = $this->input->post('timeline_names');
		$memo_names = $this->input->post('memo_names');

		if (!$org_id) {
			echo json_encode(array('success' => false, 'message' => '조직 ID가 필요합니다.'));
			return;
		}

		// 권한 검증
		$user_id = $this->session->userdata('user_id');
		$user_level = $this->User_model->get_org_user_level($user_id, $org_id);

		if ($user_level < 9 && $this->session->userdata('master_yn') !== 'Y') {
			echo json_encode(array('success' => false, 'message' => '조직 정보를 수정할 권한이 없습니다.'));
			return;
		}

		$update_data = array(
			'org_name' => $org_name,
			'org_type' => $org_type,
			'org_rep' => $org_rep, // 추가
			'org_desc' => $org_desc,
			'leader_name' => $leader_name,
			'new_name' => $new_name,
			'modi_date' => date('Y-m-d H:i:s')
		);

		// 배열 형태의 데이터를 처리 (빈 배열이 아닌 경우에만)
		if (!empty($position_names) && is_array($position_names)) {
			$update_data['position_name'] = $position_names;
		}

		if (!empty($duty_names) && is_array($duty_names)) {
			$update_data['duty_name'] = $duty_names;
		}

		if (!empty($timeline_names) && is_array($timeline_names)) {
			$update_data['timeline_name'] = $timeline_names;
		}

		if (!empty($memo_names) && is_array($memo_names)) {
			$update_data['memo_name'] = $memo_names;
		}

		// 알림 메시지 설정 처리
		if (!empty($auto_message)) {
			$update_data['auto_message'] = $auto_message;
		}

		$result = $this->Org_model->update_org_info($org_id, $update_data);

		if ($result) {
			echo json_encode(array('success' => true, 'message' => '조직 정보가 수정되었습니다.'));
		} else {
			echo json_encode(array('success' => false, 'message' => '조직 정보 수정에 실패했습니다.'));
		}
	}

	/**
	 * 조직 아이콘 업로드
	 */
	public function upload_org_icon() {
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$org_id = $this->input->post('org_id');

		if (!$org_id) {
			echo json_encode(array('success' => false, 'message' => '조직 ID가 필요합니다.'));
			return;
		}

		// 권한 검증
		$user_id = $this->session->userdata('user_id');
		$user_level = $this->User_model->get_org_user_level($user_id, $org_id);

		if ($user_level < 9 && $this->session->userdata('master_yn') !== 'Y') {
			echo json_encode(array('success' => false, 'message' => '조직 아이콘을 변경할 권한이 없습니다.'));
			return;
		}

		// 업로드 디렉토리 설정
		$upload_path = './uploads/org_icons/';
		if (!is_dir($upload_path)) {
			mkdir($upload_path, 0755, true);
		}

		// 업로드 설정
		$config['upload_path'] = $upload_path;
		$config['allowed_types'] = 'jpg|jpeg|png';
		$config['max_size'] = 2048; // 2MB
		$config['file_name'] = 'org_' . $org_id . '_' . time();
		$config['overwrite'] = TRUE;

		$this->upload->initialize($config);

		if ($this->upload->do_upload('org_icon')) {
			$upload_data = $this->upload->data();
			$file_path = '/uploads/org_icons/' . $upload_data['file_name'];

			// 기존 아이콘 파일 삭제
			$current_org = $this->Org_model->get_org_detail_by_id($org_id);
			if ($current_org && $current_org['org_icon'] && file_exists('.' . $current_org['org_icon'])) {
				unlink('.' . $current_org['org_icon']);
			}

			// DB에 파일 경로 저장
			$update_data = array('org_icon' => $file_path);
			$result = $this->Org_model->update_org_info($org_id, $update_data);

			if ($result) {
				echo json_encode(array(
					'success' => true,
					'message' => '조직 아이콘이 업로드되었습니다.',
					'icon_url' => $file_path
				));
			} else {
				echo json_encode(array('success' => false, 'message' => '아이콘 정보 저장에 실패했습니다.'));
			}
		} else {
			$error = $this->upload->display_errors('', '');
			echo json_encode(array('success' => false, 'message' => '파일 업로드 실패: ' . $error));
		}
	}

	/**
	 * 조직 관리자 위임
	 */
	public function delegate_admin() {
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$org_id = $this->input->post('org_id');
		$delegate_email = $this->input->post('delegate_email');

		if (!$org_id || !$delegate_email) {
			echo json_encode(array('success' => false, 'message' => '필수 정보가 누락되었습니다.'));
			return;
		}

		// 권한 검증 - 현재 최고관리자만 위임 가능
		$user_id = $this->session->userdata('user_id');
		$user_level = $this->User_model->get_org_user_level($user_id, $org_id);

		if ($user_level != 10 && $this->session->userdata('master_yn') !== 'Y') {
			echo json_encode(array('success' => false, 'message' => '최고관리자만 위임할 수 있습니다.'));
			return;
		}

		// 위임받을 사용자가 존재하는지 확인
		$delegate_user = $this->User_model->get_user_by_email($delegate_email);
		if (!$delegate_user) {
			echo json_encode(array('success' => false, 'message' => '해당 이메일의 사용자를 찾을 수 없습니다.'));
			return;
		}

		// 해당 사용자가 이미 조직 회원인지 확인
		$existing_org_user = $this->Org_model->get_org_user($delegate_user['user_id'], $org_id);
		if (!$existing_org_user) {
			echo json_encode(array('success' => false, 'message' => '해당 사용자는 이 조직의 회원가 아닙니다.'));
			return;
		}

		// 위임 처리
		$result = $this->Org_model->delegate_admin($user_id, $delegate_user['user_id'], $org_id);

		if ($result) {
			// 위임 알림 메일 발송
			$this->send_delegation_email($delegate_user, $org_id);

			echo json_encode(array('success' => true, 'message' => '관리자 권한이 위임되었습니다.'));
		} else {
			echo json_encode(array('success' => false, 'message' => '관리자 위임에 실패했습니다.'));
		}
	}

	/**
	 * 위임 알림 메일 발송
	 */
	private function send_delegation_email($delegate_user, $org_id) {
		$org_info = $this->Org_model->get_org_detail_by_id($org_id);

		$config['protocol'] = 'smtp';

		$config['smtp_host'] = 'smtp.gmail.com';
		$config['smtp_user'] = 'hello@webhows.com'; // 실제 Gmail 주소로 변경
		$config['smtp_pass'] = 'hzeh kaik dyuh utty'; // Gmail 앱 비밀번호로 변경
		$config['smtp_port'] = 587;
		$config['smtp_crypto'] = 'tls';


		$config['charset'] = 'utf-8';
		$config['mailtype'] = 'html';
		$config['newline'] = "\r\n";

		$this->email->initialize($config);

		$this->email->from('no-reply@wani.im', '왔니 시스템');
		$this->email->to($delegate_user['user_mail']);
		$this->email->subject('[왔니] 조직 관리자 권한 위임 알림');

		$message = "
        <h3>조직 관리자 권한 위임 알림</h3>
        <p>안녕하세요, {$delegate_user['user_name']}님</p>
        <p><strong>{$org_info['org_name']}</strong> 조직의 최고관리자 권한이 귀하에게 위임되었습니다.</p>
        <p>이제 해당 조직의 모든 설정을 관리할 수 있습니다.</p>
        <p><a href='" . base_url('org') . "'>조직 설정 페이지로 이동</a></p>
        <br>
        <p>감사합니다.</p>
        <p>왔니 시스템</p>
        ";

		$this->email->message($message);
		$this->email->send();
	}

	/**
	 * 조직 상세 정보 가져오기
	 */
	public function get_org_detail() {
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$org_id = $this->input->post('org_id');

		if (!$org_id) {
			echo json_encode(array('success' => false, 'message' => '조직 ID가 필요합니다.'));
			return;
		}

		$org_detail = $this->Org_model->get_org_detail_by_id($org_id);

		if ($org_detail) {
			echo json_encode(array('success' => true, 'data' => $org_detail));
		} else {
			echo json_encode(array('success' => false, 'message' => '조직 정보를 찾을 수 없습니다.'));
		}
	}

	/**
	 * 초대 코드 갱신
	 */
	public function refresh_invite_code() {
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$org_id = $this->input->post('org_id');

		if (!$org_id) {
			echo json_encode(array('success' => false, 'message' => '조직 ID가 필요합니다.'));
			return;
		}

		// 권한 검증
		$user_id = $this->session->userdata('user_id');
		$user_level = $this->User_model->get_org_user_level($user_id, $org_id);

		if ($user_level < 9 && $this->session->userdata('master_yn') !== 'Y') {
			echo json_encode(array('success' => false, 'message' => '초대 코드를 갱신할 권한이 없습니다.'));
			return;
		}

		// 새로운 초대코드 생성 및 업데이트
		$new_invite_code = $this->Org_model->refresh_invite_code($org_id);

		if ($new_invite_code) {
			echo json_encode(array(
				'success' => true,
				'message' => '초대 코드가 갱신되었습니다.',
				'invite_code' => $new_invite_code
			));
		} else {
			echo json_encode(array('success' => false, 'message' => '초대 코드 갱신에 실패했습니다.'));
		}
	}

	/**
	 * 조직 직인 업로드
	 */
	public function upload_org_seal() {
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$org_id = $this->input->post('org_id');

		if (!$org_id) {
			echo json_encode(array('success' => false, 'message' => '조직 ID가 필요합니다.'));
			return;
		}

		// 권한 검증
		$user_id = $this->session->userdata('user_id');
		$user_level = $this->User_model->get_org_user_level($user_id, $org_id);

		if ($user_level < 9 && $this->session->userdata('master_yn') !== 'Y') {
			echo json_encode(array('success' => false, 'message' => '조직 직인을 변경할 권한이 없습니다.'));
			return;
		}

		// 업로드 디렉토리 설정
		$upload_path = './uploads/org_seals/';
		if (!is_dir($upload_path)) {
			mkdir($upload_path, 0755, true);
		}

		// 업로드 설정
		$config['upload_path'] = $upload_path;
		$config['allowed_types'] = 'jpg|jpeg|png';
		$config['max_size'] = 2048; // 2MB
		$config['file_name'] = 'org_seal_' . $org_id . '_' . time();
		$config['overwrite'] = TRUE;

		$this->upload->initialize($config);

		if ($this->upload->do_upload('org_seal')) {
			$upload_data = $this->upload->data();
			$file_path = '/uploads/org_seals/' . $upload_data['file_name'];

			// 기존 직인 파일 삭제
			$current_org = $this->Org_model->get_org_detail_by_id($org_id);
			if ($current_org && !empty($current_org['org_seal']) && file_exists('.' . $current_org['org_seal'])) {
				unlink('.' . $current_org['org_seal']);
			}

			// DB에 파일 경로 저장
			$update_data = array('org_seal' => $file_path);
			$result = $this->Org_model->update_org_info($org_id, $update_data);

			if ($result) {
				echo json_encode(array(
					'success' => true,
					'message' => '조직 직인이 업로드되었습니다.',
					'seal_url' => $file_path
				));
			} else {
				echo json_encode(array('success' => false, 'message' => '직인 정보 저장에 실패했습니다.'));
			}
		} else {
			$error = $this->upload->display_errors('', '');
			echo json_encode(array('success' => false, 'message' => '파일 업로드 실패: ' . $error));
		}
	}

}
