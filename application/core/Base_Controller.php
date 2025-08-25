<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Base_Controller extends CI_Controller {

	public function __construct() {
		parent::__construct();
		$this->load->library('session');
		$this->load->helper('url');
		$this->load->model('User_model');
		$this->load->model('Org_model');
	}


	/**
	 * POST 조직 변경 요청 처리 및 권한 검증
	 */
	protected function handle_org_change(&$data) {
		$postOrgId = $this->input->post('org_id');

		if (!$postOrgId) {
			return false;
		}

		$user_id = $this->session->userdata('user_id');
		$master_yn = $this->session->userdata('master_yn');

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

		// 세션에도 현재 조직 정보 저장 (필요한 경우)
		$this->session->set_userdata('current_org_id', $postOrgId);

		// 로그 기록
		log_message('info', "사용자 {$user_id}가 조직을 {$postOrgId}로 변경했습니다.");

		return true;
	}

	/**
	 * 사용자의 조직 접근 권한 확인
	 */
	protected function check_org_access($org_id, $required_level = 1) {
		$user_id = $this->session->userdata('user_id');
		$master_yn = $this->session->userdata('master_yn');

		if (!$user_id) {
			return false;
		}

		// 마스터 사용자는 모든 권한
		if ($master_yn === 'Y') {
			return true;
		}

		// 사용자가 접근 가능한 조직인지 확인
		$user_orgs = $this->Org_model->get_user_orgs($user_id);
		$has_access = false;
		$user_level = 0;

		foreach ($user_orgs as $org) {
			if ($org['org_id'] == $org_id) {
				$has_access = true;
				$user_level = $org['level'] ?? 1;
				break;
			}
		}

		return $has_access && ($user_level >= $required_level);
	}

	/**
	 * 권한 검증 실패 시 처리
	 */
	protected function handle_access_denied($message = '접근 권한이 없습니다.') {
		$user_id = $this->session->userdata('user_id');
		$requested_uri = $this->uri->uri_string();

		log_message('warning', "사용자 {$user_id}의 권한 없는 접근: {$requested_uri}");

		show_error($message, 403);
	}
}
