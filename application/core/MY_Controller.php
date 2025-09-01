<?php
/**
 * 역할: 기본 컨트롤러 확장 - 헤더 데이터 처리 및 조직 관리 기능
 */

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
	 * 헤더에서 사용할 조직 데이터 준비
	 */
	protected function prepare_header_data()
	{
		if (!$this->session->userdata('user_id')) {
			return array();
		}

		$user_id = $this->session->userdata('user_id');
		$master_yn = $this->session->userdata('master_yn');

		// 사용자 정보 가져오기
		$user_data = $this->User_model->get_user_by_id($user_id);

		// User_management_model을 사용하여 권한이 있는 조직만 가져오기
		$this->load->model('User_management_model');

		// 사용자가 접근 가능한 조직 목록 가져오기
		if ($master_yn === "N") {
			$user_orgs = $this->User_management_model->get_user_orgs($user_id);
		} else {
			$user_orgs = $this->User_management_model->get_user_orgs_master($user_id);
		}

		// 현재 활성화된 조직 정보 가져오기
		$current_org = $this->get_current_organization($user_orgs);

		return array(
			'user' => $user_data,
			'user_orgs' => $user_orgs,
			'current_org' => $current_org
		);
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
	 * POST 조직 변경 요청 처리 및 권한 검증
	 */
	protected function handle_org_change(&$data)
	{
		$postOrgId = $this->input->post('org_id');

		if (!$postOrgId) {
			return false;
		}

		$user_id = $this->session->userdata('user_id');

		// 사용자가 해당 조직에 접근 권한이 있는지 확인
		$has_access = false;
		$selected_org = null;

		foreach ($data['user_orgs'] as $org) {
			if ($org['org_id'] == $postOrgId) {
				$has_access = true;
				$selected_org = $org;
				break;
			}
		}

		if (!$has_access) {
			log_message('warning', "사용자 {$user_id}가 조직 {$postOrgId}에 대한 접근을 시도했으나 권한이 없습니다.");
			show_error('접근 권한이 없는 조직입니다.', 403);
			return false;
		}

		// 조직 변경 적용
		$data['current_org'] = $selected_org;
		$this->input->set_cookie('activeOrg', $postOrgId, 86400);

		// 세션에도 현재 조직 정보 저장
		$this->session->set_userdata('current_org_id', $postOrgId);
		$this->session->set_userdata('current_org_name', $selected_org['org_name']);

		// 로그 기록
		log_message('info', "사용자 {$user_id}가 조직을 {$postOrgId}({$selected_org['org_name']})로 변경했습니다.");

		return true;
	}

	/**
	 * 파일 위치: application/core/MY_Controller.php - check_org_access() 함수 수정
	 * 역할: 사용자의 조직 접근 권한 확인
	 */
	protected function check_org_access($org_id, $required_level = 1)
	{
		$user_id = $this->session->userdata('user_id');
		$master_yn = $this->session->userdata('master_yn');

		if (!$user_id) {
			return false;
		}

		// 마스터 사용자는 모든 권한
		if ($master_yn === 'Y') {
			return true;
		}

		// User_management_model을 사용하여 권한 확인
		if (!isset($this->User_management_model)) {
			$this->load->model('User_management_model');
		}

		// 사용자가 접근 가능한 조직인지 확인
		$user_orgs = $this->User_management_model->get_user_orgs($user_id);
		$has_access = false;
		$user_level = 0;

		foreach ($user_orgs as $org) {
			if ($org['org_id'] == $org_id) {
				$has_access = true;
				$user_level = $org['level'] ?? 1;
				break;
			}
		}

		return $has_access && $user_level >= $required_level;
	}

	/**
	 * 접근 권한 없음 처리
	 */
	protected function handle_access_denied($message = '접근 권한이 없습니다.')
	{
		show_error($message, 403);
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
