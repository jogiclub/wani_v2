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
