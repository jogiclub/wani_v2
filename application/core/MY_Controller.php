<?php
defined('BASEPATH') or exit('No direct script access allowed');

class MY_Controller extends CI_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->library('session');
		$this->load->helper('url');
		$this->load->model('User_model');
		$this->load->model('Org_model');

		// 로그인 확인
		$user_id = $this->session->userdata('user_id');
		if (!$user_id) {
			redirect('login');
			return;
		}

		// 조직 확인
		$this->check_user_has_organization();
	}

	/**
	 * 사용자가 조직에 속해 있는지 확인
	 */
	private function check_user_has_organization()
	{
		$user_id = $this->session->userdata('user_id');

		// DB에서 사용자 정보 조회하여 master_yn 확인
		$user = $this->User_model->get_user_by_id($user_id);
		$master_yn = isset($user['master_yn']) ? $user['master_yn'] : 'N';

		// 세션의 master_yn 값이 DB와 다르면 동기화
		if ($this->session->userdata('master_yn') !== $master_yn) {
			$this->session->set_userdata('master_yn', $master_yn);
		}

		// 조직 목록 조회 (Org_model 내부에서 카테고리 필터링 처리)
		if ($master_yn === 'Y') {
			$user_orgs = $this->Org_model->get_user_orgs_master($user_id);
		} else {
			$user_orgs = $this->Org_model->get_user_orgs($user_id);
		}

		// 조직이 없으면 login으로 리다이렉트
		if (empty($user_orgs)) {
			redirect('login');
			return;
		}
	}

	/**
	 * 헤더에 필요한 공통 데이터 준비
	 */
	protected function prepare_header_data()
	{
		$user_id = $this->session->userdata('user_id');

		// 사용자 정보 로드
		$this->load->model('User_model');
		$user = $this->User_model->get_user_by_id($user_id);

		// DB에서 master_yn 값 직접 사용 (세션 값 대신)
		$master_yn = isset($user['master_yn']) ? $user['master_yn'] : 'N';

		$data = array();
		$data['user'] = $user;
		$data['is_master'] = $master_yn;

		// 조직 목록 조회 (Org_model 내부에서 카테고리 필터링 처리)
		$this->load->model('Org_model');
		if ($master_yn === 'Y') {
			$user_orgs = $this->Org_model->get_user_orgs_master($user_id);
		} else {
			$user_orgs = $this->Org_model->get_user_orgs($user_id);
		}

		$data['user_orgs'] = $user_orgs;



		// 현재 선택된 조직 확인
		$current_org_id = $this->get_current_org_id($user_orgs);

		if ($current_org_id) {
			foreach ($user_orgs as $org) {
				if ($org['org_id'] == $current_org_id) {
					$data['current_org'] = $org;
					break;
				}
			}

			if (!isset($data['current_org']) && !empty($user_orgs)) {
				$data['current_org'] = $user_orgs[0];
				$this->input->set_cookie('activeOrg', $user_orgs[0]['org_id'], 86400);
			}
		} else if (!empty($user_orgs)) {
			$data['current_org'] = $user_orgs[0];
			$this->input->set_cookie('activeOrg', $user_orgs[0]['org_id'], 86400);
		}

		// 읽지 않은 메시지와 최근 메시지 조회
		if (isset($data['current_org'])) {
			$this->load->model('Message_model');
			$data['unread_message_count'] = $this->Message_model->get_unread_count($user_id, $data['current_org']['org_id']);
			$data['recent_messages'] = $this->Message_model->get_recent_messages($user_id, $data['current_org']['org_id'], 10);
		}

		return $data;
	}

	/**
	 * 현재 선택된 조직 ID 가져오기
	 */
	protected function get_current_org_id($user_orgs)
	{
		// 1. POST로 전달된 org_id 확인
		$org_id = $this->input->post('org_id');
		if ($org_id) {
			return $org_id;
		}

		// 2. 쿠키에서 확인
		$org_id = $this->input->cookie('activeOrg');
		if ($org_id) {
			// 사용자가 해당 조직에 접근 권한이 있는지 확인
			foreach ($user_orgs as $org) {
				if ($org['org_id'] == $org_id) {
					return $org_id;
				}
			}
		}

		// 3. 세션에서 확인
		$org_id = $this->session->userdata('current_org_id');
		if ($org_id) {
			// 사용자가 해당 조직에 접근 권한이 있는지 확인
			foreach ($user_orgs as $org) {
				if ($org['org_id'] == $org_id) {
					return $org_id;
				}
			}
		}

		// 4. 기본값으로 첫 번째 조직 반환
		if (!empty($user_orgs)) {
			return $user_orgs[0]['org_id'];
		}

		return null;
	}

	/**
	 * 조직 변경 요청 처리
	 */
	protected function handle_org_change(&$data)
	{
		$post_org_id = $this->input->post('org_id');
		if ($post_org_id) {
			foreach ($data['user_orgs'] as $org) {
				if ($org['org_id'] == $post_org_id) {
					$data['current_org'] = $org;
					$this->input->set_cookie('activeOrg', $post_org_id, 86400);
					$this->session->set_userdata([
						'current_org_id' => $post_org_id,
						'current_org_name' => $org['org_name']
					]);
					break;
				}
			}
		}
	}

	/**
	 * 조직 접근 권한 확인
	 */
	protected function check_org_access($org_id)
	{
		$user_id = $this->session->userdata('user_id');

		// DB에서 master_yn 확인
		$user = $this->User_model->get_user_by_id($user_id);
		$master_yn = isset($user['master_yn']) ? $user['master_yn'] : 'N';

		if ($master_yn === 'Y') {
			$user_orgs = $this->Org_model->get_user_orgs_master($user_id);
		} else {
			$user_orgs = $this->Org_model->get_user_orgs($user_id);
		}

		foreach ($user_orgs as $org) {
			if ($org['org_id'] == $org_id) {
				return true;
			}
		}

		return false;
	}

	/**
	 * 접근 거부 처리
	 */
	protected function handle_access_denied($message = '접근 권한이 없습니다.')
	{
		if ($this->input->is_ajax_request()) {
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array(
				'success' => false,
				'message' => $message
			));
			exit;
		} else {
			show_error($message, 403);
		}
	}


	protected function get_active_org_id()
	{
		// 1순위: 쿠키에서 가져오기
		$org_id = $this->input->cookie('activeOrg');

		// 2순위: 세션에서 가져오기
		if (!$org_id) {
			$org_id = $this->session->userdata('current_org_id');
		}

		// 3순위: 사용자의 첫 번째 조직 사용
		if (!$org_id) {
			$user_id = $this->session->userdata('user_id');

			if ($user_id) {
				// DB에서 master_yn 확인
				$user = $this->User_model->get_user_by_id($user_id);
				$master_yn = isset($user['master_yn']) ? $user['master_yn'] : 'N';

				if ($master_yn === 'Y') {
					$user_orgs = $this->Org_model->get_user_orgs_master($user_id);
				} else {
					$user_orgs = $this->Org_model->get_user_orgs($user_id);
				}

				if (!empty($user_orgs)) {
					$org_id = $user_orgs[0]['org_id'];
					// 쿠키와 세션에 저장
					$this->input->set_cookie('activeOrg', $org_id, 86400);
					$this->session->set_userdata('current_org_id', $org_id);
				}
			}
		}

		return $org_id;
	}

	protected function check_menu_access($menu_key)
	{
		// dashboard는 권한 체크 제외
		if ($menu_key === 'OVERVIEW') {
			return;
		}

		$user_id = $this->session->userdata('user_id');

		// DB에서 master_yn 확인
		$user = $this->User_model->get_user_by_id($user_id);
		$master_yn = isset($user['master_yn']) ? $user['master_yn'] : 'N';

		// 마스터 사용자는 모든 메뉴 접근 가능
		if ($master_yn === 'Y') {
			return;
		}

		// 현재 조직에서의 사용자 권한 레벨 확인
		$org_id = $this->get_active_org_id();
		if (!$org_id) {
			show_error('조직 정보를 찾을 수 없습니다.', 403);
			return;
		}

		// User_management_model 로드
		if (!isset($this->User_management_model)) {
			$this->load->model('User_management_model');
		}

		$user_level = $this->User_management_model->get_org_user_level($user_id, $org_id);

		// 최고관리자(레벨 10)는 모든 메뉴 접근 가능
		if ($user_level >= 10) {
			return;
		}

		// 사용자의 관리 메뉴 조회
		$user_managed_menus = $this->User_management_model->get_user_managed_menus($user_id);

		// 관리 메뉴가 비어있으면 접근 불가
		if (empty($user_managed_menus)) {
			show_error('해당 메뉴에 접근할 권한이 없습니다.', 403);
			return;
		}

		// 관리 메뉴에 포함되지 않은 경우 접근 불가
		if (!in_array($menu_key, $user_managed_menus)) {
			show_error('해당 메뉴에 접근할 권한이 없습니다.', 403);
			return;
		}
	}

}
