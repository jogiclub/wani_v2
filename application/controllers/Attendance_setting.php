<?php
/**
 * 파일 위치: application/controllers/Attendance_setting.php
 * 역할: 출석설정 페이지의 컨트롤러 - 출석타입(wb_att_type) 관리 기능
 */
defined('BASEPATH') or exit('No direct script access allowed');

class Attendance_setting extends My_Controller
{

	public function __construct()
	{
		parent::__construct();
		$this->load->model('Attendance_setting_model');
		$this->load->model('User_model');
		$this->load->library('session');
		$this->load->helper('url');
	}

	/**
	 * 출석설정 메인 페이지
	 */
	public function index()
	{
		$user_id = $this->session->userdata('user_id');
		if (!$user_id) {
			redirect('login');
			return;
		}

		// 헤더 데이터 준비
		$header_data = $this->prepare_header_data();
		if (empty($header_data['user_orgs'])) {
			redirect('mypage');
			return;
		}

		$data = $header_data;

		// POST로 조직 변경 요청 처리
		$this->handle_org_change($data);

		$currentOrgId = $data['current_org']['org_id'];

		// 사용자의 조직 접근 권한 확인 - 출석설정은 최소 레벨 9 이상 필요
		$user_level = $this->User_model->get_org_user_level($user_id, $currentOrgId);
		if ($user_level < 9 && $this->session->userdata('master_yn') !== 'Y') {
			$this->handle_access_denied('출석설정을 관리할 권한이 없습니다.');
			return;
		}

		// 선택된 조직의 상세 정보 가져오기
		$this->load->model('Org_model');
		$data['selected_org_detail'] = $this->Org_model->get_org_detail_by_id($currentOrgId);

		// 선택된 조직의 출석타입 목록 가져오기
		$data['attendance_types'] = $this->Attendance_setting_model->get_attendance_types_by_org($currentOrgId);

		// 출석타입 카테고리 목록 가져오기
		$data['attendance_categories'] = $this->Attendance_setting_model->get_attendance_categories_by_org($currentOrgId);

		// 현재 조직 정보를 JavaScript로 전달하기 위해 orgs 배열에 추가
		$data['orgs'] = array($data['current_org']);

		$this->load->view('attendance_setting', $data);
	}

	/**
	 * 새로운 출석타입 추가
	 */
	public function add_attendance_type()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$user_id = $this->session->userdata('user_id');

		// org_id 가져오기
		$org_id = $this->input->post('org_id');
		if (!$org_id) {
			$org_id = $this->input->cookie('activeOrg');
		}
		if (!$org_id) {
			$org_id = $this->session->userdata('current_org_id');
		}

		if (!$org_id) {
			echo json_encode(array('success' => false, 'message' => '조직 정보를 찾을 수 없습니다.'));
			return;
		}

		// 권한 검증
		$user_level = $this->User_model->get_org_user_level($user_id, $org_id);
		if ($user_level < 9 && $this->session->userdata('master_yn') !== 'Y') {
			echo json_encode(array('success' => false, 'message' => '출석타입을 추가할 권한이 없습니다.'));
			return;
		}

		$att_type_name = trim($this->input->post('att_type_name'));
		$att_type_nickname = trim($this->input->post('att_type_nickname'));
		$att_type_category_idx = $this->input->post('att_type_category_idx');
		$att_type_category_name = trim($this->input->post('att_type_category_name'));
		$att_type_point = $this->input->post('att_type_point');
		$att_type_input = $this->input->post('att_type_input') ?: 'check';
		$att_type_color = str_replace('#', '', $this->input->post('att_type_color'));

		// 필수 필드 검증
		if (empty($att_type_name)) {
			echo json_encode(array('success' => false, 'message' => '출석타입명은 필수입니다.'));
			return;
		}

		if (empty($att_type_nickname)) {
			echo json_encode(array('success' => false, 'message' => '출석타입 별칭은 필수입니다.'));
			return;
		}

		// 카테고리 처리
		if (empty($att_type_category_idx) && !empty($att_type_category_name)) {
			// 새로운 카테고리 생성
			$att_type_category_idx = $this->Attendance_setting_model->get_max_category_idx($org_id) + 1;
		} elseif (empty($att_type_category_idx)) {
			$att_type_category_idx = 1; // 기본 카테고리
			$att_type_category_name = '기본';
		}

		// 다음 순서 번호 가져오기
		$att_type_order = $this->Attendance_setting_model->get_next_order($org_id);

		$data = array(
			'att_type_name' => $att_type_name,
			'att_type_nickname' => $att_type_nickname,
			'att_type_category_idx' => $att_type_category_idx,
			'att_type_category_name' => $att_type_category_name,
			'att_type_point' => $att_type_point ?: 0,
			'att_type_input' => $att_type_input,
			'att_type_color' => $att_type_color ?: 'CB3227',
			'org_id' => $org_id,
			'att_type_order' => $att_type_order
		);

		if ($this->Attendance_setting_model->insert_attendance_type($data)) {
			echo json_encode(array('success' => true, 'message' => '출석타입이 추가되었습니다.'));
		} else {
			echo json_encode(array('success' => false, 'message' => '출석타입 추가에 실패했습니다.'));
		}
	}

	/**
	 * 출석타입 정보 수정
	 */
	public function update_attendance_type()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$user_id = $this->session->userdata('user_id');
		$att_type_idx = $this->input->post('att_type_idx');

		if (!$att_type_idx) {
			echo json_encode(array('success' => false, 'message' => '출석타입 정보를 찾을 수 없습니다.'));
			return;
		}

		// 권한 검증
		$att_type = $this->Attendance_setting_model->get_attendance_type_by_id($att_type_idx);
		if (!$att_type) {
			echo json_encode(array('success' => false, 'message' => '출석타입을 찾을 수 없습니다.'));
			return;
		}

		$user_level = $this->User_model->get_org_user_level($user_id, $att_type['org_id']);
		if ($user_level < 9 && $this->session->userdata('master_yn') !== 'Y') {
			echo json_encode(array('success' => false, 'message' => '출석타입을 수정할 권한이 없습니다.'));
			return;
		}

		$att_type_name = trim($this->input->post('att_type_name'));
		$att_type_nickname = trim($this->input->post('att_type_nickname'));
		$att_type_point = $this->input->post('att_type_point');
		$att_type_input = $this->input->post('att_type_input') ?: 'check';
		$att_type_color = str_replace('#', '', $this->input->post('att_type_color'));

		// 필수 필드 검증
		if (empty($att_type_name)) {
			echo json_encode(array('success' => false, 'message' => '출석타입명은 필수입니다.'));
			return;
		}

		if (empty($att_type_nickname)) {
			echo json_encode(array('success' => false, 'message' => '출석타입 별칭은 필수입니다.'));
			return;
		}

		$data = array(
			'att_type_name' => $att_type_name,
			'att_type_nickname' => $att_type_nickname,
			'att_type_point' => $att_type_point ?: 0,
			'att_type_input' => $att_type_input,
			'att_type_color' => $att_type_color ?: 'CB3227'
		);

		if ($this->Attendance_setting_model->update_attendance_type($att_type_idx, $data)) {
			echo json_encode(array('success' => true, 'message' => '출석타입이 수정되었습니다.'));
		} else {
			echo json_encode(array('success' => false, 'message' => '출석타입 수정에 실패했습니다.'));
		}
	}

	/**
	 * 파일 위치: application/controllers/Attendance_setting.php
	 * 역할: 출석타입 삭제 처리 (org_id 처리 개선)
	 */
	public function delete_attendance_type()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$user_id = $this->session->userdata('user_id');

		// org_id 가져오기 - 우선순위: 쿠키 > 세션
		$org_id = $this->input->cookie('activeOrg');
		if (!$org_id) {
			$org_id = $this->session->userdata('current_org_id');
		}

		if (!$org_id) {
			echo json_encode(array('success' => false, 'message' => '조직 정보를 찾을 수 없습니다.'));
			return;
		}

		// 권한 검증 - 삭제는 레벨 9 이상 필요
		$user_level = $this->User_model->get_org_user_level($user_id, $org_id);
		if ($user_level < 9 && $this->session->userdata('master_yn') !== 'Y') {
			echo json_encode(array('success' => false, 'message' => '출석타입을 삭제할 권한이 없습니다.'));
			return;
		}

		$att_type_idx = $this->input->post('att_type_idx');

		if (!$att_type_idx) {
			echo json_encode(array('success' => false, 'message' => '출석타입 ID가 필요합니다.'));
			return;
		}

		// 해당 출석타입이 현재 조직에 속한지 확인
		$att_type_info = $this->Attendance_setting_model->get_attendance_type_by_id($att_type_idx);
		if (!$att_type_info) {
			echo json_encode(array('success' => false, 'message' => '존재하지 않는 출석타입입니다.'));
			return;
		}

		if ($att_type_info['org_id'] != $org_id) {
			echo json_encode(array('success' => false, 'message' => '해당 출석타입을 삭제할 권한이 없습니다.'));
			return;
		}

		// 해당 출석타입을 사용하는 출석 기록이 있는지 확인
		if ($this->Attendance_setting_model->has_attendance_records($att_type_idx)) {
			echo json_encode(array('success' => false, 'message' => '사용 중인 출석타입은 삭제할 수 없습니다.'));
			return;
		}

		$result = $this->Attendance_setting_model->delete_attendance_type($att_type_idx);

		if ($result) {
			echo json_encode(array('success' => true, 'message' => '출석타입이 삭제되었습니다.'));
		} else {
			echo json_encode(array('success' => false, 'message' => '출석타입 삭제에 실패했습니다.'));
		}
	}

	/**
	 * 파일 위치: application/controllers/Attendance_setting.php
	 * 역할: 출석타입 순서 업데이트 처리 (수정 버전)
	 */
	public function update_orders()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$user_id = $this->session->userdata('user_id');

		// org_id 가져오기 - 우선순위: 쿠키 > 세션
		$org_id = $this->input->cookie('activeOrg');
		if (!$org_id) {
			$org_id = $this->session->userdata('current_org_id');
		}

		if (!$org_id) {
			echo json_encode(array('success' => false, 'message' => '조직 정보를 찾을 수 없습니다.'));
			return;
		}

		// 권한 검증 - 출석설정 관리는 최소 레벨 9 이상 필요
		$user_level = $this->User_model->get_org_user_level($user_id, $org_id);
		if ($user_level < 9 && $this->session->userdata('master_yn') !== 'Y') {
			echo json_encode(array('success' => false, 'message' => '출석타입 순서를 변경할 권한이 없습니다.'));
			return;
		}

		$orders = $this->input->post('orders');

		if (!$orders || !is_array($orders)) {
			echo json_encode(array('success' => false, 'message' => '순서 정보가 올바르지 않습니다.'));
			return;
		}

		// 트랜잭션 시작
		$this->db->trans_start();

		try {
			foreach ($orders as $order_data) {
				if (!isset($order_data['att_type_idx']) || !isset($order_data['att_type_order'])) {
					continue;
				}

				// 해당 출석타입이 현재 조직에 속한지 확인
				$att_type_info = $this->Attendance_setting_model->get_attendance_type_by_id($order_data['att_type_idx']);
				if (!$att_type_info || $att_type_info['org_id'] != $org_id) {
					continue;
				}

				// 순서 업데이트
				$this->Attendance_setting_model->update_attendance_type($order_data['att_type_idx'], array(
					'att_type_order' => $order_data['att_type_order']
				));
			}

			$this->db->trans_complete();

			if ($this->db->trans_status() === FALSE) {
				echo json_encode(array('success' => false, 'message' => '순서 저장 중 오류가 발생했습니다.'));
			} else {
				echo json_encode(array('success' => true, 'message' => '순서가 저장되었습니다.'));
			}

		} catch (Exception $e) {
			$this->db->trans_rollback();
			echo json_encode(array('success' => false, 'message' => '순서 저장에 실패했습니다.'));
		}
	}
}
