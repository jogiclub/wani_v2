<?php
/**
 * 파일 위치: E:\SynologyDrive\Example\wani\application\controllers\Detail_field.php
 * 역할: 상세필드 설정 페이지의 컨트롤러
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class Detail_field extends CI_Controller {

	public function __construct() {
		parent::__construct();
		$this->load->model('Detail_field_model');
		$this->load->library('session');
		$this->load->helper('url'); // URL 헬퍼 로드 추가
		$this->load->model('User_model'); // User_model 추가
	}

	/**
	 * 헤더에서 사용할 조직 데이터 준비
	 */
	private function prepare_header_data() {
		if (!$this->session->userdata('user_id')) {
			return array();
		}

		$user_id = $this->session->userdata('user_id');
		$master_yn = $this->session->userdata('master_yn');

		// 사용자 정보 가져오기
		$user_data = $this->User_model->get_user_by_id($user_id);

		// 사용자가 접근 가능한 조직 목록 가져오기
		if ($master_yn === "N") {
			$user_orgs = $this->Detail_field_model->get_user_orgs($user_id);
		} else {
			$user_orgs = $this->Detail_field_model->get_user_orgs_master($user_id);
		}

		// 현재 활성화된 조직 정보
		$active_org_id = $this->input->cookie('activeOrg');
		$current_org = null;

		if ($active_org_id) {
			foreach ($user_orgs as $org) {
				if ($org['org_id'] == $active_org_id) {
					$current_org = $org;
					break;
				}
			}
		}

		// 활성화된 조직이 없거나 유효하지 않으면 첫 번째 조직을 기본값으로 설정
		if (!$current_org && !empty($user_orgs)) {
			$current_org = $user_orgs[0];
			$this->input->set_cookie('activeOrg', $current_org['org_id'], 86400);
		}

		return array(
			'user' => $user_data,
			'user_orgs' => $user_orgs,
			'current_org' => $current_org
		);
	}

	/**
	 * 상세필드 설정 메인 페이지
	 */
	public function index() {
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

		// URL 파라미터에서 org_id 확인
		$request_org_id = $this->input->get('org_id');
		$currentOrgId = $this->session->userdata('current_org_id');

		// URL로 org_id가 전달된 경우 세션 업데이트
		if ($request_org_id && is_numeric($request_org_id)) {
			$this->session->set_userdata('current_org_id', $request_org_id);
			$currentOrgId = $request_org_id;
		}

		// 여전히 current_org_id가 없으면 현재 활성화된 조직을 기본값으로 설정
		if (!$currentOrgId && $header_data['current_org']) {
			$currentOrgId = $header_data['current_org']['org_id'];
			$this->session->set_userdata('current_org_id', $currentOrgId);
		}

		// 그래도 없으면 첫 번째 조직을 사용
		if (!$currentOrgId) {
			$master_yn = $this->session->userdata('master_yn');
			if ($master_yn === "N") {
				$user_orgs = $this->Detail_field_model->get_user_orgs($user_id);
			} else {
				$user_orgs = $this->Detail_field_model->get_user_orgs_master($user_id);
			}

			if (!empty($user_orgs)) {
				$currentOrgId = $user_orgs[0]['org_id'];
				$this->session->set_userdata('current_org_id', $currentOrgId);
			} else {
				show_error('접근 가능한 조직이 없습니다.', 403);
				return;
			}
		}

		// 권한 검증
		$user_level = $this->Detail_field_model->get_org_user_level($user_id, $currentOrgId);
		if ($user_level < 9 && $this->session->userdata('master_yn') !== 'Y') {
			show_error('상세필드를 관리할 권한이 없습니다.', 403);
			return;
		}

		$master_yn = $this->session->userdata('master_yn');

		// 사용자가 접근 가능한 모든 조직 목록
		if ($master_yn === "N") {
			$orgs = $this->Detail_field_model->get_user_orgs($user_id);
		} else {
			$orgs = $this->Detail_field_model->get_user_orgs_master($user_id);
		}

		foreach ($orgs as &$org) {
			$org['user_level'] = $this->Detail_field_model->get_org_user_level($user_id, $org['org_id']);
			$org['user_master_yn'] = $this->session->userdata('master_yn');
		}

		// 헤더 데이터와 페이지 데이터를 합쳐서 뷰에 전달
		$data = $header_data; // 헤더 데이터 포함
		$data['orgs'] = $orgs;

		// 현재 선택된 조직의 상세 정보 가져오기
		$data['selected_org_detail'] = $this->Detail_field_model->get_org_detail_by_id($currentOrgId);

		// 현재 조직의 상세필드 목록 가져오기
		$data['detail_fields'] = $this->Detail_field_model->get_detail_fields_by_org($currentOrgId);

		// 뷰 로드 전 데이터 확인
		log_message('debug', 'Detail_field: Loading view with data - org_id: ' . $currentOrgId);

		// 뷰 로드
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
		$org_id = $this->session->userdata('current_org_id');

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
			'field_settings' => $field_settings ? json_encode($field_settings) : '{}',
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
	 * 상세필드 정보 업데이트
	 */
	public function update_field() {
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$user_id = $this->session->userdata('user_id');
		$org_id = $this->session->userdata('current_org_id');

		// 권한 검증
		$user_level = $this->Detail_field_model->get_org_user_level($user_id, $org_id);
		if ($user_level < 9 && $this->session->userdata('master_yn') !== 'Y') {
			echo json_encode(array('success' => false, 'message' => '상세필드를 수정할 권한이 없습니다.'));
			return;
		}

		$field_idx = $this->input->post('field_idx');
		$field_name = $this->input->post('field_name');
		$field_type = $this->input->post('field_type');
		$field_settings = $this->input->post('field_settings');

		if (!$field_idx || empty($field_name) || empty($field_type)) {
			echo json_encode(array('success' => false, 'message' => '필수 정보가 누락되었습니다.'));
			return;
		}

		$data = array(
			'field_name' => $field_name,
			'field_type' => $field_type,
			'field_settings' => $field_settings ? json_encode($field_settings) : '{}'
		);

		$result = $this->Detail_field_model->update_detail_field($field_idx, $data);

		if ($result) {
			echo json_encode(array('success' => true, 'message' => '상세필드가 수정되었습니다.'));
		} else {
			echo json_encode(array('success' => false, 'message' => '상세필드 수정에 실패했습니다.'));
		}
	}

	/**
	 * 상세필드 삭제
	 */
	public function delete_field() {
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$user_id = $this->session->userdata('user_id');
		$org_id = $this->session->userdata('current_org_id');

		// 권한 검증
		$user_level = $this->Detail_field_model->get_org_user_level($user_id, $org_id);
		if ($user_level < 9 && $this->session->userdata('master_yn') !== 'Y') {
			echo json_encode(array('success' => false, 'message' => '상세필드를 삭제할 권한이 없습니다.'));
			return;
		}

		$field_idx = $this->input->post('field_idx');

		if (!$field_idx) {
			echo json_encode(array('success' => false, 'message' => '필드 ID가 필요합니다.'));
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
	 * 필드 활성화/비활성화 토글
	 */
	public function toggle_field() {
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$user_id = $this->session->userdata('user_id');
		$org_id = $this->session->userdata('current_org_id');

		// 권한 검증
		$user_level = $this->Detail_field_model->get_org_user_level($user_id, $org_id);
		if ($user_level < 9 && $this->session->userdata('master_yn') !== 'Y') {
			echo json_encode(array('success' => false, 'message' => '상세필드를 관리할 권한이 없습니다.'));
			return;
		}

		$field_idx = $this->input->post('field_idx');

		if (!$field_idx) {
			echo json_encode(array('success' => false, 'message' => '필드 ID가 필요합니다.'));
			return;
		}

		$result = $this->Detail_field_model->toggle_field_active($field_idx);

		if ($result) {
			echo json_encode(array('success' => true, 'message' => '필드 상태가 변경되었습니다.'));
		} else {
			echo json_encode(array('success' => false, 'message' => '필드 상태 변경에 실패했습니다.'));
		}
	}

	/**
	 * 필드 순서 업데이트
	 */
	public function update_orders() {
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$user_id = $this->session->userdata('user_id');
		$org_id = $this->session->userdata('current_org_id');

		// 권한 검증
		$user_level = $this->Detail_field_model->get_org_user_level($user_id, $org_id);
		if ($user_level < 9 && $this->session->userdata('master_yn') !== 'Y') {
			echo json_encode(array('success' => false, 'message' => '필드 순서를 변경할 권한이 없습니다.'));
			return;
		}

		$orders = $this->input->post('orders');

		if (!$orders) {
			echo json_encode(array('success' => false, 'message' => '순서 정보가 필요합니다.'));
			return;
		}

		$result = $this->Detail_field_model->update_display_orders($orders);

		if ($result) {
			echo json_encode(array('success' => true, 'message' => '필드 순서가 변경되었습니다.'));
		} else {
			echo json_encode(array('success' => false, 'message' => '필드 순서 변경에 실패했습니다.'));
		}
	}
}
