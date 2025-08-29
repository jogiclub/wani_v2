<?php
/**
 * 파일 위치: application/controllers/Detail_field.php
 * 역할: 상세필드 설정 페이지의 컨트롤러
 */
defined('BASEPATH') or exit('No direct script access allowed');

class Detail_field extends My_Controller
{

	public function __construct()
	{
		parent::__construct();
		$this->load->model('Detail_field_model');
		$this->load->library('session');
		$this->load->helper('url');
		$this->load->model('User_model');
	}


	/**
	 * 상세필드 설정 메인 페이지
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

		// POST로 조직 변경 요청 처리 (My_Controller의 메소드 사용)
		$this->handle_org_change($data);

		$currentOrgId = $data['current_org']['org_id'];

		// 사용자의 조직 접근 권한 확인 - 상세필드 관리는 최소 레벨 8 이상 필요
		$user_level = $this->User_model->get_org_user_level($user_id, $currentOrgId);
		if ($user_level < 8 && $this->session->userdata('master_yn') !== 'Y') {
			$this->handle_access_denied('상세필드를 관리할 권한이 없습니다.');
			return;
		}

		// 선택된 조직의 상세 정보 가져오기 - 뷰에서 필요한 변수 추가
		$this->load->model('Org_model');
		$data['selected_org_detail'] = $this->Org_model->get_org_detail_by_id($currentOrgId);

		// 선택된 조직의 상세필드 목록 가져오기 - 메서드명 수정
		$data['detail_fields'] = $this->Detail_field_model->get_detail_fields_by_org($currentOrgId);

		// 현재 조직 정보를 JavaScript로 전달하기 위해 orgs 배열에 추가
		$data['orgs'] = array($data['current_org']);

		$this->load->view('detail_field_setting', $data);
	}


	/**
	 * 새로운 상세필드 추가
	 */
	public function add_field() {
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$user_id = $this->session->userdata('user_id');

		// org_id 가져오기 - 우선순위: POST > 쿠키 > 세션
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
		$user_level = $this->Detail_field_model->get_org_user_level($user_id, $org_id);
		if ($user_level < 9 && $this->session->userdata('master_yn') !== 'Y') {
			echo json_encode(array('success' => false, 'message' => '상세필드를 추가할 권한이 없습니다.'));
			return;
		}

		$field_name = $this->input->post('field_name');
		$field_type = $this->input->post('field_type');
		$field_settings = $this->input->post('field_settings');

		if (empty($field_name) || empty($field_type)) {
			echo json_encode(array('success' => false, 'message' => '필드명과 타입은 필수입니다.'));
			return;
		}

		$data = array(
			'field_name' => $field_name,
			'org_id' => $org_id,
			'field_type' => $field_type,
			'field_size' => $field_size,
			'field_settings' => $field_settings ? json_encode($field_settings, JSON_UNESCAPED_UNICODE) : '{}',
			'display_order' => $this->Detail_field_model->get_next_display_order($org_id),
			'is_active' => 'Y'
		);

		$result = $this->Detail_field_model->insert_detail_field($data);

		if ($result) {
			echo json_encode(array('success' => true, 'message' => '상세필드가 추가되었습니다.'));
		} else {
			echo json_encode(array('success' => false, 'message' => '상세필드 추가에 실패했습니다.'));
		}
	}

	/**
	 * 상세필드 수정
	 */
	public function update_field() {
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$user_id = $this->session->userdata('user_id');

		// org_id 가져오기
		$org_id = $this->input->cookie('activeOrg');
		if (!$org_id) {
			$org_id = $this->session->userdata('current_org_id');
		}

		if (!$org_id) {
			echo json_encode(array('success' => false, 'message' => '조직 정보를 찾을 수 없습니다.'));
			return;
		}

		// 권한 검증
		$user_level = $this->Detail_field_model->get_org_user_level($user_id, $org_id);
		if ($user_level < 9 && $this->session->userdata('master_yn') !== 'Y') {
			echo json_encode(array('success' => false, 'message' => '상세필드를 수정할 권한이 없습니다.'));
			return;
		}

		$field_idx = $this->input->post('field_idx');
		$field_name = $this->input->post('field_name');
		$field_type = $this->input->post('field_type');
		$field_size = $this->input->post('field_size');
		$field_settings = $this->input->post('field_settings');

		if (!$field_idx || empty($field_name) || empty($field_type) || empty($field_size)) {
			echo json_encode(array('success' => false, 'message' => '필수 정보가 누락되었습니다.'));
			return;
		}

		$update_data = array(
			'field_name' => $field_name,
			'field_type' => $field_type,
			'field_size' => $field_size,
			'field_settings' => $field_settings ? json_encode($field_settings, JSON_UNESCAPED_UNICODE) : '{}'
		);

		$result = $this->Detail_field_model->update_detail_field($field_idx, $update_data);

		if ($result) {
			echo json_encode(array('success' => true, 'message' => '상세필드가 수정되었습니다.'));
		} else {
			echo json_encode(array('success' => false, 'message' => '상세필드 수정에 실패했습니다.'));
		}
	}

	/**
	 * 파일 위치: application/controllers/Detail_field.php
	 * 역할: 상세필드 삭제 처리 (org_id 처리 개선)
	 */
	public function delete_field()
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
			echo json_encode(array('success' => false, 'message' => '상세필드를 삭제할 권한이 없습니다.'));
			return;
		}

		$field_idx = $this->input->post('field_idx');

		if (!$field_idx) {
			echo json_encode(array('success' => false, 'message' => '필드 ID가 필요합니다.'));
			return;
		}

		// 해당 필드가 현재 조직에 속한지 확인
		$field_info = $this->Detail_field_model->get_detail_field_by_id($field_idx);
		if (!$field_info) {
			echo json_encode(array('success' => false, 'message' => '존재하지 않는 필드입니다.'));
			return;
		}

		if ($field_info['org_id'] != $org_id) {
			echo json_encode(array('success' => false, 'message' => '해당 필드를 삭제할 권한이 없습니다.'));
			return;
		}

		$result = $this->Detail_field_model->delete_detail_field($field_idx);

		if ($result) {
			echo json_encode(array('success' => true, 'message' => '상세필드가 삭제되었습니다.'));
		} else {
			echo json_encode(array('success' => false, 'message' => '상세필드 삭제에 실패했습니다.'));
		}
	}

	/**
	 * 파일 위치: application/controllers/Detail_field.php
	 * 역할: 필드 활성화/비활성화 토글 처리 (org_id 처리 개선)
	 */
	public function toggle_field()
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

		// 권한 검증 - 상태 변경은 레벨 8 이상 필요
		$user_level = $this->User_model->get_org_user_level($user_id, $org_id);
		if ($user_level < 8 && $this->session->userdata('master_yn') !== 'Y') {
			echo json_encode(array('success' => false, 'message' => '상세필드를 관리할 권한이 없습니다.'));
			return;
		}

		$field_idx = $this->input->post('field_idx');

		if (!$field_idx) {
			echo json_encode(array('success' => false, 'message' => '필드 ID가 필요합니다.'));
			return;
		}

		// 해당 필드가 현재 조직에 속한지 확인
		$field_info = $this->Detail_field_model->get_detail_field_by_id($field_idx);
		if (!$field_info) {
			echo json_encode(array('success' => false, 'message' => '존재하지 않는 필드입니다.'));
			return;
		}

		if ($field_info['org_id'] != $org_id) {
			echo json_encode(array('success' => false, 'message' => '해당 필드를 관리할 권한이 없습니다.'));
			return;
		}

		$result = $this->Detail_field_model->toggle_field_active($field_idx);

		if ($result) {
			// 변경된 상태 확인
			$updated_field = $this->Detail_field_model->get_detail_field_by_id($field_idx);
			$status_message = ($updated_field['is_active'] === 'Y') ? '활성화' : '비활성화';
			echo json_encode(array('success' => true, 'message' => '필드가 ' . $status_message . '되었습니다.'));
		} else {
			echo json_encode(array('success' => false, 'message' => '필드 상태 변경에 실패했습니다.'));
		}
	}

	/**
	 * 파일 위치: application/controllers/Detail_field.php
	 * 역할: 필드 순서 업데이트 처리
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

		// 권한 검증 - 상세필드 관리는 최소 레벨 8 이상 필요
		$user_level = $this->User_model->get_org_user_level($user_id, $org_id);
		if ($user_level < 8 && $this->session->userdata('master_yn') !== 'Y') {
			echo json_encode(array('success' => false, 'message' => '필드 순서를 변경할 권한이 없습니다.'));
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
				if (!isset($order_data['field_idx']) || !isset($order_data['display_order'])) {
					continue;
				}

				// 해당 필드가 현재 조직에 속한지 확인
				$field_info = $this->Detail_field_model->get_detail_field_by_id($order_data['field_idx']);
				if (!$field_info || $field_info['org_id'] != $org_id) {
					continue;
				}

				// 순서 업데이트
				$this->Detail_field_model->update_detail_field($order_data['field_idx'], array(
					'display_order' => $order_data['display_order']
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
