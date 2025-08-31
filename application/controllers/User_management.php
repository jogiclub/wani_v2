<?php
/**
 * 파일 위치: application/controllers/User_management.php
 * 역할: 사용자 관리 컨트롤러 - 조직 내 사용자 목록 조회 및 관리
 */

defined('BASEPATH') or exit('No direct script access allowed');

class User_management extends My_Controller
{
	public function __construct()
	{
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
	 * 사용자 관리 메인 화면
	 */
	public function index()
	{
		$user_id = $this->session->userdata('user_id');

		// 헤더 데이터 준비
		$header_data = $this->prepare_header_data();
		if (empty($header_data['user_orgs'])) {
			redirect('mypage');
			return;
		}

		$data = $header_data;

		// org_id 가져오기
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

		// POST로 조직 변경 요청 처리 (My_Controller의 메소드 사용)
		if ($this->input->post('org_id')) {
			$this->handle_org_change($data);
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
				$this->input->set_cookie('activeOrg', $org_id, 86400);
				// 헤더 데이터 업데이트
				$data['current_org'] = $user_orgs[0];
			} else {
				show_error('접근 가능한 조직이 없습니다.', 403);
				return;
			}
		}

		// 권한 검증 - 사용자 관리는 최소 레벨 9 이상 필요
		$user_level = $this->User_management_model->get_org_user_level($user_id, $org_id);
		if ($user_level < 9 && $this->session->userdata('master_yn') !== 'Y') {
			show_error('사용자를 관리할 권한이 없습니다.', 403);
			return;
		}

		$master_yn = $this->session->userdata('master_yn');

		// 사용자가 접근 가능한 모든 조직 목록 (권한 레벨 포함)
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
		$data['selected_org_detail'] = $this->User_management_model->get_org_detail_by_id($org_id);
		$data['org_users'] = $this->User_management_model->get_org_users($org_id);

		$this->load->view('user_management', $data);
	}



    /**
     * 관리 메뉴 목록 조회 API
     */
    public function get_managed_menus() {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }

        // 메뉴 헬퍼 로드
        $this->load->helper('menu');
        $system_menus = get_system_menus();

        $menus = array();
        if ($system_menus && is_array($system_menus)) {
            foreach ($system_menus as $menu_key => $menu_info) {
                $menus[] = array(
                    'key' => $menu_key,
                    'name' => $menu_info['name']
                );
            }
        }

        echo json_encode(array('success' => true, 'menus' => $menus));
    }

    /**
     * 관리 그룹 목록 조회 API
     */
    public function get_managed_areas() {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }

        // POST와 GET 모두 처리
        $org_id = $this->input->post('org_id');
        if (!$org_id) {
            $org_id = $this->input->get('org_id');
        }

        // 쿠키에서 현재 조직 정보 가져오기
        if (!$org_id) {
            $org_id = $this->input->cookie('activeOrg');
        }

        if (!$org_id) {
            echo json_encode(array('success' => false, 'message' => '조직 정보가 필요합니다.'));
            return;
        }

        // 조직의 그룹 목록 조회
        $areas = $this->User_management_model->get_org_areas($org_id);

        echo json_encode(array('success' => true, 'areas' => $areas));
    }

    /**
     * 사용자 관리 정보 조회 API
     */
    public function get_user_management_info() {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }

        $user_id = $this->input->post('user_id');
        if (!$user_id) {
            echo json_encode(array('success' => false, 'message' => '사용자 ID가 필요합니다.'));
            return;
        }

        $managed_menus = $this->User_management_model->get_user_managed_menus($user_id);
        $managed_areas = $this->User_management_model->get_user_managed_areas($user_id);

        echo json_encode(array(
            'success' => true,
            'managed_menus' => $managed_menus,
            'managed_areas' => $managed_areas
        ));
    }

    /**
     * 사용자 정보 수정 (관리 메뉴 및 그룹 포함)
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
		$user_mail = $this->input->post('user_mail');
		$user_hp = $this->input->post('user_hp');

		if (empty($target_user_id) || empty($user_name) || empty($user_mail) || empty($user_hp)) {
			echo json_encode(array('success' => false, 'message' => '필수 입력 항목이 누락되었습니다.'));
			return;
		}

		// 이메일 형식 검증
		if (!filter_var($user_mail, FILTER_VALIDATE_EMAIL)) {
			echo json_encode(array('success' => false, 'message' => '올바른 이메일 형식이 아닙니다.'));
			return;
		}

		// 사용자 정보 업데이트
		$result = $this->User_management_model->update_user_basic_info($target_user_id, $user_name, $user_mail, $user_hp);

		if ($result) {
			echo json_encode(array('success' => true, 'message' => '사용자 정보가 수정되었습니다.'));
		} else {
			echo json_encode(array('success' => false, 'message' => '사용자 정보 수정에 실패했습니다.'));
		}
	}

	/**
	 * 선택된 사용자들의 권한 일괄 수정
	 */
	public function bulk_update_users() {
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$user_id = $this->session->userdata('user_id');
		$org_id = $this->input->post('org_id');
		$user_ids_json = $this->input->post('user_ids');

		// 권한 검증
		$user_level = $this->User_management_model->get_org_user_level($user_id, $org_id);
		if ($user_level < 9 && $this->session->userdata('master_yn') !== 'Y') {
			echo json_encode(array('success' => false, 'message' => '권한을 수정할 권한이 없습니다.'));
			return;
		}

		// 대상 사용자 ID 배열 파싱
		$user_ids = json_decode($user_ids_json, true);
		if (!is_array($user_ids) || empty($user_ids)) {
			echo json_encode(array('success' => false, 'message' => '수정할 사용자를 선택해주세요.'));
			return;
		}

		// 입력값 검증 및 처리
		$level = $this->input->post('level');
		$managed_menus = $this->input->post('managed_menus');
		$managed_areas = $this->input->post('managed_areas');

		// 레벨 값이 있는 경우에만 검증
		if ($level !== '' && (!is_numeric($level) || $level < 0 || $level > 10)) {
			echo json_encode(array('success' => false, 'message' => '올바르지 않은 권한 레벨입니다.'));
			return;
		}

		// 관리 메뉴/그룹 JSON 변환
		$managed_menus_json = null;
		$managed_areas_json = null;

		if (!empty($managed_menus) && is_array($managed_menus)) {
			$managed_menus_json = json_encode($managed_menus);
		}

		if (!empty($managed_areas) && is_array($managed_areas)) {
			$managed_areas_json = json_encode($managed_areas);
		}

		// 일괄 업데이트 실행
		$success_count = 0;
		$total_count = count($user_ids);

		foreach ($user_ids as $target_user_id) {
			// 각 사용자별로 권한 업데이트
			$result = $this->User_management_model->bulk_update_user_permissions(
				$target_user_id,
				$org_id,
				$level !== '' ? $level : null,
				$managed_menus_json,
				$managed_areas_json
			);

			if ($result) {
				$success_count++;
			}
		}

		if ($success_count === $total_count) {
			echo json_encode(array('success' => true, 'message' => "{$success_count}명의 사용자 권한이 수정되었습니다."));
		} else {
			$failed_count = $total_count - $success_count;
			echo json_encode(array('success' => false, 'message' => "{$success_count}명 수정 완료, {$failed_count}명 실패했습니다."));
		}
	}

	/**
	 * 사용자 삭제 (조직에서 제외) - 응답 개선 버전
	 */
	public function delete_user()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		// Content-Type 헤더 설정
		header('Content-Type: application/json; charset=utf-8');

		$user_id = $this->session->userdata('user_id');
		$target_user_id = $this->input->post('target_user_id');
		$org_id = $this->input->post('org_id');

		log_message('debug', "Delete request received - target: {$target_user_id}, org: {$org_id}, by: {$user_id}");

		// 입력값 검증
		if (empty($target_user_id) || empty($org_id)) {
			$response = array('success' => false, 'message' => '삭제할 사용자 정보를 찾을 수 없습니다.');
			echo json_encode($response);
			return;
		}

		// 권한 검증
		$user_level = $this->User_management_model->get_org_user_level($user_id, $org_id);
		if ($user_level < 9 && $this->session->userdata('master_yn') !== 'Y') {
			log_message('error', "Delete user permission denied: User {$user_id} (level {$user_level}) trying to delete {$target_user_id} in org {$org_id}");
			$response = array('success' => false, 'message' => '사용자를 삭제할 권한이 없습니다.');
			echo json_encode($response);
			return;
		}

		// 자기 자신 삭제 방지
		if ($user_id === $target_user_id) {
			$response = array('success' => false, 'message' => '본인 계정은 삭제할 수 없습니다.');
			echo json_encode($response);
			return;
		}

		// 삭제 대상 사용자 정보 확인
		$target_user_info = $this->User_management_model->get_org_user_info($target_user_id, $org_id);
		if (!$target_user_info) {
			log_message('error', "Delete user failed: Target user {$target_user_id} not found in org {$org_id}");
			$response = array('success' => false, 'message' => '삭제할 사용자 정보를 찾을 수 없습니다.');
			echo json_encode($response);
			return;
		}

		log_message('debug', "Attempting to delete user: {$target_user_id} from org: {$org_id} by user: {$user_id}");

		// 사용자 삭제 실행
		$result = $this->User_management_model->delete_org_user($target_user_id, $org_id);

		if ($result) {
			log_message('info', "User deleted successfully: {$target_user_id} from org: {$org_id} by user: {$user_id}");
			$response = array('success' => true, 'message' => '사용자가 조직에서 제외되었습니다.');
		} else {
			log_message('error', "User deletion failed: {$target_user_id} from org: {$org_id} by user: {$user_id}");
			$response = array('success' => false, 'message' => '사용자 삭제에 실패했습니다.');
		}

		echo json_encode($response);
	}

    /**
     * 사용자 초대 메일 발송
     */
    public function invite_user()
    {
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

        // 초대 메일 발송 로직
        $result = $this->User_management_model->send_invite_email($invite_email, $org_id, $user_id);

        if ($result) {
            echo json_encode(array('success' => true, 'message' => '초대 메일이 발송되었습니다.'));
        } else {
            echo json_encode(array('success' => false, 'message' => '초대 메일 발송에 실패했습니다.'));
        }
    }


	/**
	 * 관리 그룹 트리 구조 목록 조회 API
	 */
	public function get_managed_areas_tree() {
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		// POST와 GET 모두 처리
		$org_id = $this->input->post('org_id');
		if (!$org_id) {
			$org_id = $this->input->get('org_id');
		}

		// 쿠키에서 현재 조직 정보 가져오기
		if (!$org_id) {
			$org_id = $this->input->cookie('activeOrg');
		}

		if (!$org_id) {
			echo json_encode(array('success' => false, 'message' => '조직 정보가 필요합니다.'));
			return;
		}

		// Member_area_model 로드 (트리 구조 조회를 위해)
		$this->load->model('Member_area_model');

		// 트리 구조로 그룹 목록 조회
		$areas_tree = $this->Member_area_model->get_member_areas_tree($org_id);

		echo json_encode(array('success' => true, 'areas' => $areas_tree));
	}

	/**
	 * 사용자로 로그인 (마스터 전용)
	 */
	public function login_as_user()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		// Content-Type 헤더 설정
		header('Content-Type: application/json; charset=utf-8');

		// 마스터 권한 확인
		if ($this->session->userdata('master_yn') !== 'Y') {
			$response = array('success' => false, 'message' => '권한이 없습니다.');
			echo json_encode($response);
			return;
		}

		$current_user_id = $this->session->userdata('user_id');
		$target_user_id = $this->input->post('target_user_id');

		// 입력값 검증
		if (empty($target_user_id)) {
			$response = array('success' => false, 'message' => '사용자 정보가 누락되었습니다.');
			echo json_encode($response);
			return;
		}

		// 자기 자신으로 로그인 방지
		if ($current_user_id === $target_user_id) {
			$response = array('success' => false, 'message' => '본인 계정으로는 로그인할 수 없습니다.');
			echo json_encode($response);
			return;
		}

		// 대상 사용자 정보 확인
		$target_user = $this->User_management_model->get_user_info($target_user_id);
		if (!$target_user) {
			$response = array('success' => false, 'message' => '사용자 정보를 찾을 수 없습니다.');
			echo json_encode($response);
			return;
		}

		// 로그 기록
		log_message('info', "Admin login as user: {$current_user_id} -> {$target_user_id}");

		// 세션 데이터 업데이트 (원래 관리자 정보는 별도로 저장)
		$session_data = array(
			'user_id' => $target_user['user_id'],
			'user_name' => $target_user['user_name'],
			'user_mail' => $target_user['user_mail'],
			'user_hp' => $target_user['user_hp'],
			'master_yn' => 'N', // 로그인한 사용자는 손님로 설정
			'original_admin_id' => $current_user_id, // 원래 관리자 ID 저장
			'is_admin_login' => true // 관리자 로그인 플래그
		);

		$this->session->set_userdata($session_data);

		$response = array(
			'success' => true,
			'message' => $target_user['user_name'] . '님으로 로그인되었습니다.'
		);
		echo json_encode($response);
	}

	/**
	 * 관리자 계정으로 돌아가기
	 */
	public function return_to_admin()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		// Content-Type 헤더 설정
		header('Content-Type: application/json; charset=utf-8');

		$original_admin_id = $this->session->userdata('original_admin_id');
		$is_admin_login = $this->session->userdata('is_admin_login');

		if (!$original_admin_id || !$is_admin_login) {
			$response = array('success' => false, 'message' => '관리자 로그인 상태가 아닙니다.');
			echo json_encode($response);
			return;
		}

		// 원래 관리자 정보 복원
		$admin_user = $this->User_management_model->get_user_info($original_admin_id);
		if (!$admin_user) {
			$response = array('success' => false, 'message' => '관리자 정보를 찾을 수 없습니다.');
			echo json_encode($response);
			return;
		}

		// 세션 데이터 복원
		$session_data = array(
			'user_id' => $admin_user['user_id'],
			'user_name' => $admin_user['user_name'],
			'user_mail' => $admin_user['user_mail'],
			'user_hp' => $admin_user['user_hp'],
			'master_yn' => 'Y',
			'original_admin_id' => null,
			'is_admin_login' => false
		);

		$this->session->set_userdata($session_data);

		$response = array('success' => true, 'message' => '관리자 계정으로 돌아왔습니다.');
		echo json_encode($response);
	}

}
