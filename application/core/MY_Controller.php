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
	}

	/**
	 * 헤더에 필요한 공통 데이터 준비
	 */
	protected function prepare_header_data()
	{
		$user_id = $this->session->userdata('user_id');
		$master_yn = $this->session->userdata('master_yn');

		// 사용자 정보 로드
		$this->load->model('User_model');
		$user = $this->User_model->get_user_by_id($user_id);

		$data = array();
		$data['user'] = $user;

		// 마스터 사용자의 경우 managed_areas 확인
		$visible_categories = array();
		if ($master_yn === 'Y' && !empty($user['managed_areas'])) {
			$managed_areas = json_decode($user['managed_areas'], true);
			if (is_array($managed_areas) && !empty($managed_areas)) {
				$visible_categories = $managed_areas;
			}
		}

		// 조직 목록 조회
		$this->load->model('Org_model');
		if ($master_yn === "N") {
			$user_orgs = $this->Org_model->get_user_orgs($user_id);
		} else {
			// 마스터인 경우 카테고리 필터링 적용
			if (!empty($visible_categories)) {
				$user_orgs = $this->Org_model->get_user_orgs_master_filtered($user_id, $visible_categories);
			} else {
				$user_orgs = $this->Org_model->get_user_orgs_master($user_id);
			}
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

			if (!isset($data['current_org'])) {
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
			// 올바른 메서드명 사용
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

		// 4. 사용자의 첫 번째 조직 반환
		if (!empty($user_orgs)) {
			return $user_orgs[0]['org_id'];
		}

		return null;
	}


	/**
	 * 현재 활성화된 조직 정보 가져오기 (우선순위: 쿠키 > 세션 > 첫 번째 조직)
	 */
	private function get_current_organization($user_orgs)
	{
		if (empty($user_orgs)) {
			return null;
		}

		$current_org = null;

		// 1순위: 쿠키에서 활성 조직 ID 가져오기
		$active_org_id = $this->input->cookie('activeOrg');

		// 2순위: 세션에서 현재 조직 ID 가져오기
		if (!$active_org_id) {
			$active_org_id = $this->session->userdata('current_org_id');
		}

		// 활성 조직 ID가 있는 경우 해당 조직 정보 찾기
		if ($active_org_id) {
			foreach ($user_orgs as $org) {
				if ($org['org_id'] == $active_org_id) {
					$current_org = $org;
					break;
				}
			}
		}

		// 활성화된 조직이 없거나 유효하지 않으면 첫 번째 조직을 기본값으로 설정
		if (!$current_org) {
			$current_org = $user_orgs[0];

			// 쿠키와 세션에 새로운 기본 조직 설정
			$this->input->set_cookie('activeOrg', $current_org['org_id'], 86400);
			$this->session->set_userdata('current_org_id', $current_org['org_id']);
			$this->session->set_userdata('current_org_name', $current_org['org_name']);

			log_message('info', "사용자 {$this->session->userdata('user_id')}의 현재 조직이 {$current_org['org_id']}로 설정되었습니다.");
		}

		return $current_org;
	}

	/**
	 * 조직 변경 처리
	 */
	protected function handle_org_change(&$data)
	{
		$post_org_id = $this->input->post('org_id');

		if ($post_org_id) {
			// POST로 전달된 조직 ID로 변경
			$found = false;
			foreach ($data['user_orgs'] as $org) {
				if ($org['org_id'] == $post_org_id) {
					$data['current_org'] = $org;
					$this->input->set_cookie('activeOrg', $post_org_id, 86400);
					$this->session->set_userdata('current_org_id', $post_org_id);
					$found = true;
					break;
				}
			}

			if (!$found && !empty($data['user_orgs'])) {
				$data['current_org'] = $data['user_orgs'][0];
				$this->input->set_cookie('activeOrg', $data['user_orgs'][0]['org_id'], 86400);
				$this->session->set_userdata('current_org_id', $data['user_orgs'][0]['org_id']);
			}

			// 메시지 정보 재조회
			if (isset($data['current_org'])) {
				$this->load->model('Message_model');
				$user_id = $this->session->userdata('user_id');
				// 올바른 메서드명 사용
				$data['unread_message_count'] = $this->Message_model->get_unread_count($user_id, $data['current_org']['org_id']);
				$data['recent_messages'] = $this->Message_model->get_recent_messages($user_id, $data['current_org']['org_id'], 10);
			}
		}
	}

	/**
	 * 조직 접근 권한 확인
	 */
	protected function check_org_access($org_id)
	{
		$user_id = $this->session->userdata('user_id');
		$master_yn = $this->session->userdata('master_yn');

		// 마스터는 모든 조직 접근 가능
		if ($master_yn === 'Y') {
			return true;
		}

		// 일반 사용자는 소속된 조직만 접근 가능
		$this->load->model('Org_model');
		$user_orgs = $this->Org_model->get_user_orgs($user_id);

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

	/**
	 * 파일 위치: application/core/MY_Controller.php - get_active_org_id() 함수 수정
	 * 역할: 현재 활성 조직 ID 가져오기
	 */
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
			$master_yn = $this->session->userdata('master_yn');

			if ($user_id) {
				// User_management_model 사용
				if (!isset($this->User_management_model)) {
					$this->load->model('User_management_model');
				}

				if ($master_yn === "N") {
					$user_orgs = $this->User_management_model->get_user_orgs($user_id);
				} else {
					$user_orgs = $this->User_management_model->get_user_orgs_master($user_id);
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


	/**
	 * 파일 위치: application/core/MY_Controller.php - has_user_organizations() 함수 수정
	 * 역할: 사용자 조직 존재 여부 확인
	 */
	protected function has_user_organizations($user_id = null)
	{
		if (!$user_id) {
			$user_id = $this->session->userdata('user_id');
		}

		if (!$user_id) {
			return false;
		}

		$master_yn = $this->session->userdata('master_yn');

		// User_management_model 사용
		if (!isset($this->User_management_model)) {
			$this->load->model('User_management_model');
		}

		if ($master_yn === "N") {
			$user_orgs = $this->User_management_model->get_user_orgs($user_id);
		} else {
			$user_orgs = $this->User_management_model->get_user_orgs_master($user_id);
		}

		return !empty($user_orgs);
	}



}

