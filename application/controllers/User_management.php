<?php
// application/controllers/User_management.php
// 사용자 관리 컨트롤러 - 조직 내 사용자 목록 조회 및 관리

defined('BASEPATH') OR exit('No direct script access allowed');

class User_management extends CI_Controller {

	public function __construct() {
		parent::__construct();
		$this->load->library('session');
		$this->load->helper('url');
		$this->load->model('User_management_model');

		// 로그인 확인
		if (!$this->session->userdata('user_id')) {
			redirect('login');
		}
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
		$this->load->model('User_model');
		$this->load->model('Org_model');
		$user_data = $this->User_model->get_user_by_id($user_id);

		// 사용자가 접근 가능한 조직 목록 가져오기
		if ($master_yn === "N") {
			$user_orgs = $this->Org_model->get_user_orgs($user_id);
		} else {
			$user_orgs = $this->Org_model->get_user_orgs_master($user_id);
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
	 * 사용자 관리 메인 화면
	 */
	public function index() {
		$user_id = $this->session->userdata('user_id');

		// 헤더 데이터 준비
		$header_data = $this->prepare_header_data();
		if (empty($header_data['user_orgs'])) {
			redirect('mypage');
			return;
		}

		$data = $header_data; // 헤더 데이터 포함

		// org_id 가져오기 - 우선순위: GET > 쿠키 > 세션
		$org_id = $this->input->get('org_id');
		if (!$org_id) {
			$org_id = $this->input->cookie('activeOrg');
		}
		if (!$org_id) {
			$org_id = $this->session->userdata('current_org_id');
		}

		// 현재 조직이 없으면 헤더 데이터의 current_org 사용
		if (!$org_id && isset($data['current_org']['org_id'])) {
			$org_id = $data['current_org']['org_id'];
		}

		// 여전히 조직이 없으면 첫 번째 조직 선택
		if (!$org_id) {
			$master_yn = $this->session->userdata('master_yn');
			if ($master_yn === "N") {
				$user_orgs = $this->User_management_model->get_user_orgs($user_id);
			} else {
				$user_orgs = $this->User_management_model->get_user_orgs_master($user_id);
			}

			if (!empty($user_orgs)) {
				$org_id = $user_orgs[0]['org_id'];
				// 쿠키 설정
				$this->input->set_cookie('activeOrg', $org_id, 86400);
			} else {
				show_error('접근 가능한 조직이 없습니다.', 403);
				return;
			}
		}

		// 권한 검증 - 레벨 9 이상 또는 master_yn이 'Y'인 경우만 접근 가능
		$user_level = $this->User_management_model->get_org_user_level($user_id, $org_id);
		if ($user_level < 9 && $this->session->userdata('master_yn') !== 'Y') {
			show_error('사용자를 관리할 권한이 없습니다.', 403);
			return;
		}

		$master_yn = $this->session->userdata('master_yn');

		// 사용자가 접근 가능한 모든 조직 목록
		if ($master_yn === "N") {
			$orgs = $this->User_management_model->get_user_orgs($user_id);
		} else {
			$orgs = $this->User_management_model->get_user_orgs_master($user_id);
		}

		foreach ($orgs as &$org) {
			$org['user_level'] = $this->User_management_model->get_org_user_level($user_id, $org['org_id']);
			$org['user_count'] = $this->User_management_model->get_org_user_count($org['org_id']);
		}

		$data['orgs'] = $orgs;

		// 현재 선택된 조직의 상세 정보
		$data['selected_org_detail'] = $this->User_management_model->get_org_detail_by_id($org_id);

		// 현재 조직의 사용자 목록
		$data['org_users'] = $this->User_management_model->get_org_users($org_id);

		$this->load->view('user_management', $data);
	}

	/**
	 * 사용자 정보 수정
	 */
	public function update_user() {
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$user_id = $this->session->userdata('user_id');
		$target_user_id = $this->input->post('target_user_id');
		$org_id = $this->input->post('org_id');

		// 권한 검증
		$user_level = $this->User_management_model->get_org_user_level($user_id, $org_id);
		if ($user_level < 9 && $this->session->userdata('master_yn') !== 'Y') {
			echo json_encode(array('success' => false, 'message' => '사용자 정보를 수정할 권한이 없습니다.'));
			return;
		}

		$user_name = $this->input->post('user_name');
		$user_hp = $this->input->post('user_hp');
		$level = $this->input->post('level');

		if (empty($target_user_id) || empty($user_name) || empty($user_hp)) {
			echo json_encode(array('success' => false, 'message' => '필수 입력 항목이 누락되었습니다.'));
			return;
		}

		// 레벨 검증 (0-10)
		if (!is_numeric($level) || $level < 0 || $level > 10) {
			echo json_encode(array('success' => false, 'message' => '올바르지 않은 권한 레벨입니다.'));
			return;
		}

		$result = $this->User_management_model->update_user_info($target_user_id, $org_id, $user_name, $user_hp, $level);

		if ($result) {
			echo json_encode(array('success' => true, 'message' => '사용자 정보가 수정되었습니다.'));
		} else {
			echo json_encode(array('success' => false, 'message' => '사용자 정보 수정에 실패했습니다.'));
		}
	}

	/**
	 * 사용자 삭제 (조직에서 제외)
	 */
	public function delete_user() {
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$user_id = $this->session->userdata('user_id');
		$target_user_id = $this->input->post('target_user_id');
		$org_id = $this->input->post('org_id');

		// 권한 검증
		$user_level = $this->User_management_model->get_org_user_level($user_id, $org_id);
		if ($user_level < 9 && $this->session->userdata('master_yn') !== 'Y') {
			echo json_encode(array('success' => false, 'message' => '사용자를 삭제할 권한이 없습니다.'));
			return;
		}

		// 자기 자신 삭제 방지
		if ($user_id === $target_user_id) {
			echo json_encode(array('success' => false, 'message' => '본인 계정은 삭제할 수 없습니다.'));
			return;
		}

		$result = $this->User_management_model->delete_org_user($target_user_id, $org_id);

		if ($result) {
			echo json_encode(array('success' => true, 'message' => '사용자가 조직에서 제외되었습니다.'));
		} else {
			echo json_encode(array('success' => false, 'message' => '사용자 삭제에 실패했습니다.'));
		}
	}

	/**
	 * 사용자 초대 메일 발송
	 */
	public function invite_user() {
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$user_id = $this->session->userdata('user_id');
		$invite_email = $this->input->post('invite_email');
		$org_id = $this->input->post('org_id');

		// 권한 검증
		$user_level = $this->User_management_model->get_org_user_level($user_id, $org_id);
		if ($user_level < 9 && $this->session->userdata('master_yn') !== 'Y') {
			echo json_encode(array('success' => false, 'message' => '사용자를 초대할 권한이 없습니다.'));
			return;
		}

		if (empty($invite_email)) {
			echo json_encode(array('success' => false, 'message' => '초대할 이메일 주소를 입력해주세요.'));
			return;
		}

		// 이메일 형식 검증
		if (!filter_var($invite_email, FILTER_VALIDATE_EMAIL)) {
			echo json_encode(array('success' => false, 'message' => '올바른 이메일 주소를 입력해주세요.'));
			return;
		}

		// 이미 해당 조직에 속한 사용자인지 확인
		$existing_user = $this->User_management_model->check_user_in_org($invite_email, $org_id);
		if ($existing_user) {
			echo json_encode(array('success' => false, 'message' => '이미 해당 조직에 속한 사용자입니다.'));
			return;
		}

		// 초대 메일 발송 로직 (실제 구현은 메일 라이브러리 설정에 따라 다름)
		$result = $this->User_management_model->send_invite_email($invite_email, $org_id, $user_id);

		if ($result) {
			echo json_encode(array('success' => true, 'message' => '초대 메일이 발송되었습니다.'));
		} else {
			echo json_encode(array('success' => false, 'message' => '초대 메일 발송에 실패했습니다.'));
		}
	}
}
