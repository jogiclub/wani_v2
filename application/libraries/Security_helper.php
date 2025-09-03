<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Security_helper {

	private $CI;

	public function __construct() {
		$this->CI =& get_instance();
		$this->CI->load->model('User_model');
		$this->CI->load->model('Org_model');
	}

	/**
	 * 사용자가 특정 조직에 접근 권한이 있는지 확인
	 */
	public function check_org_access($user_id, $org_id) {
		if (!$user_id || !$org_id) {
			return false;
		}

		$master_yn = $this->CI->session->userdata('master_yn');

		// 마스터 권한이 있는 경우 모든 조직 접근 가능
		if ($master_yn === 'Y') {
			return true;
		}

		// 사용자가 해당 조직의 회원인지 확인
		$org_user = $this->CI->User_model->get_org_user($user_id, $org_id);
		return !empty($org_user);
	}

	/**
	 * 현재 세션의 사용자가 조직에 접근 권한이 있는지 확인
	 */
	public function verify_current_user_org_access($org_id) {
		$user_id = $this->CI->session->userdata('user_id');

		if (!$user_id) {
			log_message('error', 'Security: 인증되지 않은 사용자의 조직 접근 시도');
			return false;
		}

		$has_access = $this->check_org_access($user_id, $org_id);

		if (!$has_access) {
			log_message('error', "Security: 사용자 {$user_id}가 조직 {$org_id}에 무권한 접근 시도");
		}

		return $has_access;
	}
}
