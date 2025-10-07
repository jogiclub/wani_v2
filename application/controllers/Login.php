<?php
/**
 * 파일 위치: application/controllers/Login.php
 * 역할: 로그인 처리 및 초기 조직 설정
 */

defined('BASEPATH') OR exit('No direct script access allowed');

class Login extends CI_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->library('session');
		$this->load->helper('url');
		$this->load->model('User_model');
		$this->load->model('Org_model');
	}

	public function index()
	{
		$user_id = $this->session->userdata('user_id');
		if ($user_id) {
			redirect('login/logout');
		}
		$this->load->view('login');
	}

	public function terms()
	{
		$this->load->view('terms');
	}

	public function privacy()
	{
		$this->load->view('privacy');
	}

	public function join()
	{
		$data = array(
			'user_id' => $this->session->userdata('user_id'),
			'user_name' => $this->session->userdata('user_name'),
			'user_email' => $this->session->userdata('user_email')
		);
		$this->load->view('join', $data);
	}

	public function logout()
	{
		$this->session->sess_destroy();
		$this->output->set_header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
		$this->output->set_header('Pragma: no-cache');
		$this->input->set_cookie('ci_session', '', time() - 3600);
		$this->input->set_cookie('activeOrg', '', time() - 3600);
		redirect('login');
	}

	/**
	 * Google 로그인 시작
	 */
	public function google_login()
	{
		require_once APPPATH . '../vendor/autoload.php';

		$client = new Google_Client();
		$client->setAuthConfig([
			'client_id' => '665369034498-bf7jrt09lasbit0f5cb4ppne8rs67nt8.apps.googleusercontent.com',
			'client_secret' => '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',
			'redirect_uris' => ['https://wani.im/login/google_callback'],
			'access_type' => 'offline',
			'approval_prompt' => 'force',
		]);
		$client->addScope('email');
		$client->addScope('profile');
		$client->setRedirectUri('https://wani.im/login/google_callback');

		redirect($client->createAuthUrl());
	}

	/**
	 * Google 로그인 콜백
	 */
	public function google_callback()
	{
		require_once APPPATH . '../vendor/autoload.php';

		$client = new Google_Client();
		$client->setAuthConfig([
			'client_id' => '665369034498-bf7jrt09lasbit0f5cb4ppne8rs67nt8.apps.googleusercontent.com',
			'client_secret' => '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',
			'redirect_uris' => ['https://wani.im/login/google_callback'],
			'access_type' => 'offline',
			'approval_prompt' => 'force',
		]);

		if (!isset($_GET['code'])) {
			redirect('login');
			return;
		}

		$token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
		if (!isset($token['access_token'])) {
			redirect('login');
			return;
		}

		$client->setAccessToken($token['access_token']);
		$oauth = new Google\Service\Oauth2($client);
		$user_info = $oauth->userinfo->get();

		$this->handle_oauth_login([
			'user_id' => $user_info->email,
			'user_name' => $user_info->name,
			'user_email' => $user_info->email
		]);
	}

	/**
	 * Naver 로그인 시작
	 */
	public function naver_login()
	{
		$state = uniqid();
		$this->session->set_userdata('oauth_state', $state);

		$oauth_url = "https://nid.naver.com/oauth2.0/authorize?response_type=code&client_id=Cw90dWKPQbexg4b4I8Kv&redirect_uri=" .
			urlencode(base_url('login/naver_callback')) . "&state=" . $state;

		redirect($oauth_url);
	}

	/**
	 * Naver 로그인 콜백
	 */
	public function naver_callback()
	{
		$code = $this->input->get('code');
		$state = $this->input->get('state');
		$stored_state = $this->session->userdata('oauth_state');

		if (!$code || !$state || $state !== $stored_state) {
			redirect('login');
			return;
		}

		$token_url = "https://nid.naver.com/oauth2.0/token?grant_type=authorization_code&client_id=Cw90dWKPQbexg4b4I8Kv&client_secret=MdUHZlLXl2&redirect_uri=" .
			urlencode(base_url('login/naver_callback')) . "&code=" . $code . "&state=" . $state;

		$response = file_get_contents($token_url);
		$token_info = json_decode($response, true);

		if (!isset($token_info['access_token'])) {
			redirect('login');
			return;
		}

		$opts = array(
			'http' => array(
				'method' => 'GET',
				'header' => 'Authorization: Bearer ' . $token_info['access_token']
			)
		);
		$context = stream_context_create($opts);
		$me_response = file_get_contents('https://openapi.naver.com/v1/nid/me', false, $context);
		$me_data = json_decode($me_response, true);

		if (!isset($me_data['response'])) {
			redirect('login');
			return;
		}

		$this->handle_oauth_login([
			'user_id' => $me_data['response']['id'],
			'user_name' => $me_data['response']['name'],
			'user_email' => $me_data['response']['email']
		]);
	}

	/**
	 * Kakao 로그인 시작
	 */
	public function kakao_login()
	{
		$oauth_url = "https://kauth.kakao.com/oauth/authorize?client_id=bf21c48aa301d2094730e9b67cd433ea&redirect_uri=" .
			base_url('login/kakao_callback') . "&response_type=code";

		redirect($oauth_url);
	}

	/**
	 * Kakao 로그인 콜백
	 */
	public function kakao_callback()
	{
		$code = $this->input->get('code');
		if (!$code) {
			redirect('login');
			return;
		}

		$token_url = "https://kauth.kakao.com/oauth/token";
		$token_data = array(
			'grant_type' => 'authorization_code',
			'client_id' => 'bf21c48aa301d2094730e9b67cd433ea',
			'client_secret' => 'FwEMvQDxbSFRViAofZDCJhNeags0pFCQ',
			'redirect_uri' => base_url('login/kakao_callback'),
			'code' => $code
		);

		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $token_url);
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($token_data));
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		$response = curl_exec($curl);
		curl_close($curl);

		$response_data = json_decode($response, true);
		if (!isset($response_data['access_token'])) {
			redirect('login');
			return;
		}

		$user_headers = array(
			'Authorization: Bearer ' . $response_data['access_token'],
			'Content-Type: application/x-www-form-urlencoded;charset=utf-8'
		);

		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, 'https://kapi.kakao.com/v2/user/me');
		curl_setopt($curl, CURLOPT_HTTPHEADER, $user_headers);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		$user_response = curl_exec($curl);
		curl_close($curl);

		$user_data = json_decode($user_response, true);
		if (!isset($user_data['id'])) {
			redirect('login');
			return;
		}

		$this->handle_oauth_login([
			'user_id' => 'kakao_' . $user_data['id'],
			'user_name' => $user_data['properties']['nickname'],
			'user_email' => isset($user_data['kakao_account']['email']) ? $user_data['kakao_account']['email'] : ''
		]);
	}

	/**
	 * OAuth 로그인 공통 처리
	 */
	private function handle_oauth_login($user_info)
	{
		$user_id = $user_info['user_id'];
		$user_name = $user_info['user_name'];
		$user_email = $user_info['user_email'];

		$this->session->set_userdata($user_info);

		$user_exists = $this->User_model->check_user($user_id);

		if ($user_exists) {
			$user = $this->User_model->get_user_by_id($user_id);

			if (empty($user['user_name']) && !empty($user_name)) {
				$this->User_model->update_user($user_id, [
					'user_name' => $user_name,
					'modi_date' => date('Y-m-d H:i:s')
				]);
			}

			$this->session->set_userdata([
				'user_grade' => $user['user_grade'] ?? 0,
				'user_hp' => $user['user_hp'] ?? '',
				'master_yn' => $user['master_yn'] ?? 'N'
			]);

			$has_org = $this->setup_user_organization($user_id, $user['master_yn'] ?? 'N');

			if (!$has_org) {
				redirect('login/join');
				return;
			}

			// 중간 리다이렉트 페이지로 이동
			$this->load->view('login_redirect', ['redirect_url' => '/qrcheck']);
		} else {
			redirect('login/join');
		}
	}


	/**
	 * 로그인 성공 후 조직 정보 설정 및 초대 상태 업데이트
	 */
	private function setup_user_organization($user_id, $master_yn)
	{
		$this->load->model('User_management_model');

		$this->activate_pending_invites($user_id);

		$user_orgs = ($master_yn === "N")
			? $this->Org_model->get_user_orgs($user_id)
			: $this->Org_model->get_user_orgs_master($user_id);

		if (!empty($user_orgs)) {
			// 첫 번째 조직을 임시 기본 조직으로 설정 (localStorage 확인 전)
			$default_org = $user_orgs[0];

			$this->input->set_cookie('activeOrg', $default_org['org_id'], 86400);
			$this->session->set_userdata([
				'current_org_id' => $default_org['org_id'],
				'current_org_name' => $default_org['org_name']
			]);

			log_message('info', "사용자 {$user_id}의 임시 기본 조직이 {$default_org['org_id']}로 설정되었습니다.");
			return true;
		}

		return false;
	}

	/**
	 * localStorage에서 전달받은 조직으로 기본 조직 설정 (AJAX)
	 */
	public function set_default_org()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
			return;
		}

		$user_id = $this->session->userdata('user_id');
		if (!$user_id) {
			echo json_encode(['success' => false, 'message' => '로그인이 필요합니다.']);
			return;
		}

		$org_id = $this->input->post('org_id');
		if (!$org_id) {
			echo json_encode(['success' => false, 'message' => '조직 ID가 필요합니다.']);
			return;
		}

		$master_yn = $this->session->userdata('master_yn');

		// 사용자의 조직 목록 가져오기
		$user_orgs = ($master_yn === "N")
			? $this->Org_model->get_user_orgs($user_id)
			: $this->Org_model->get_user_orgs_master($user_id);

		// 해당 조직이 사용자의 조직 목록에 있는지 확인
		$org_exists = false;
		$selected_org = null;

		foreach ($user_orgs as $org) {
			if ($org['org_id'] == $org_id) {
				$org_exists = true;
				$selected_org = $org;
				break;
			}
		}

		if (!$org_exists) {
			echo json_encode(['success' => false, 'message' => '해당 조직에 접근 권한이 없습니다.']);
			return;
		}

		// 쿠키와 세션에 선택한 조직 설정
		$this->input->set_cookie('activeOrg', $org_id, 86400);
		$this->session->set_userdata([
			'current_org_id' => $org_id,
			'current_org_name' => $selected_org['org_name']
		]);

		log_message('info', "사용자 {$user_id}의 기본 조직이 localStorage 기반으로 {$org_id}로 설정되었습니다.");

		echo json_encode([
			'success' => true,
			'message' => '조직이 설정되었습니다.',
			'org_id' => $org_id,
			'org_name' => $selected_org['org_name']
		]);
	}

	/**
	 * 초대받은 조직들의 상태를 활성화 (level 0 -> 1)
	 */
	private function activate_pending_invites($user_id)
	{
		$this->load->model('User_management_model');

		$pending_invites = $this->User_management_model->get_user_pending_invites($user_id);

		if (empty($pending_invites)) {
			return;
		}

		foreach ($pending_invites as $invite) {
			$activated = $this->User_management_model->activate_invited_user($user_id, $invite['org_id']);

			if ($activated) {
				$invite_log = $this->User_management_model->get_invite_log($user_id, $invite['org_id']);
				if ($invite_log) {
					$this->User_management_model->update_invite_status($invite_log['idx'], 'joined', 'joined_date');
				}

				log_message('info', "사용자 {$user_id}가 조직 {$invite['org_id']}에 가입 완료 (level 0->1)");
			}
		}

		log_message('info', "사용자 {$user_id}의 " . count($pending_invites) . "개 초대 조직 활성화 처리 완료");
	}

	/**
	 * 회원가입 처리 및 초대코드 검증
	 */
	public function process()
	{
		$user_id = $this->session->userdata('user_id');
		$user_email = $this->session->userdata('user_email') ?: $this->input->post('user_email');
		$user_name = $this->session->userdata('user_name') ?: $this->input->post('user_name');
		$invite_code = $this->input->post('invite_code');

		if (empty($invite_code)) {
			echo json_encode(['success' => false, 'message' => '초대코드를 입력해주세요.']);
			return;
		}

		$org = $this->Org_model->get_org_by_invite_code($invite_code);
		if (!$org || $org['del_yn'] == 'Y') {
			echo json_encode(['success' => false, 'message' => '유효한 초대코드가 아닙니다. 확인 후 다시 입력바랍니다.']);
			return;
		}

		if (!$this->User_model->check_user($user_id)) {
			$this->User_model->insert_user([
				'user_id' => $user_id,
				'user_name' => $user_name,
				'user_mail' => $user_email,
				'regi_date' => date('Y-m-d H:i:s'),
				'modi_date' => date('Y-m-d H:i:s')
			]);
		}

		$join_result = $this->process_invite_code($user_id, $invite_code);

		echo json_encode([
			'success' => $join_result,
			'message' => $join_result ? '회원가입이 완료되었습니다.' : '회원가입 중 오류가 발생했습니다.',
			'redirect_url' => '/login/redirect_after_join'
		]);
	}

	/**
	 * 회원가입 후 리다이렉트
	 */
	public function redirect_after_join()
	{
		$this->load->view('login_redirect', ['redirect_url' => '/qrcheck']);
	}


	/**
	 * 초대코드 처리 및 조직 가입
	 */
	private function process_invite_code($user_id, $invite_code)
	{
		$org = $this->Org_model->get_org_by_invite_code($invite_code);

		if (!$org || $org['del_yn'] == 'Y') {
			return false;
		}

		if ($this->Org_model->check_user_org_membership($user_id, $org['org_id'])) {
			return true;
		}

		$result = $this->Org_model->insert_org_user([
			'user_id' => $user_id,
			'org_id' => $org['org_id'],
			'level' => 0,
			'join_date' => date('Y-m-d H:i:s')
		]);

		if ($result) {
			log_message('info', "사용자 {$user_id}가 초대코드 {$invite_code}로 조직 {$org['org_id']}에 가입했습니다.");
		}

		return $result;
	}

	/**
	 * 새로운 조직 생성 및 최고관리자로 등록
	 */
	public function create_organization()
	{
		if ($this->input->method() !== 'post') {
			show_404();
			return;
		}

		$user_id = $this->session->userdata('user_id');
		if (!$user_id) {
			echo json_encode(['success' => false, 'message' => '로그인이 필요합니다.']);
			return;
		}

		$org_name = trim($this->input->post('org_name'));
		$org_type = trim($this->input->post('org_type'));
		$org_desc = trim($this->input->post('org_desc'));

		if (empty($org_name)) {
			echo json_encode(['success' => false, 'message' => '조직명을 입력해주세요.']);
			return;
		}

		if (empty($org_type)) {
			echo json_encode(['success' => false, 'message' => '조직유형을 선택해주세요.']);
			return;
		}

		if (!$this->User_model->check_user($user_id)) {
			$this->User_model->insert_user([
				'user_id' => $user_id,
				'user_name' => $this->session->userdata('user_name'),
				'user_mail' => $this->session->userdata('user_email'),
				'regi_date' => date('Y-m-d H:i:s'),
				'modi_date' => date('Y-m-d H:i:s')
			]);
		}

		$invite_code = $this->generate_unique_invite_code();

		$this->db->trans_start();

		$org_result = $this->Org_model->insert_organization([
			'org_name' => $org_name,
			'org_type' => $org_type,
			'org_desc' => $org_desc,
			'invite_code' => $invite_code,
			'regi_date' => date('Y-m-d H:i:s'),
			'modi_date' => date('Y-m-d H:i:s'),
			'del_yn' => 'N'
		]);

		if (!$org_result) {
			$this->db->trans_rollback();
			echo json_encode(['success' => false, 'message' => '조직 생성 중 오류가 발생했습니다.']);
			return;
		}

		$org_id = $this->db->insert_id();

		$org_user_result = $this->Org_model->insert_org_user([
			'user_id' => $user_id,
			'org_id' => $org_id,
			'level' => 10,
			'join_date' => date('Y-m-d H:i:s')
		]);

		$this->db->trans_complete();

		if ($this->db->trans_status() === FALSE) {
			echo json_encode(['success' => false, 'message' => '조직 생성 중 오류가 발생했습니다.']);
			return;
		}

		// localStorage에 새로 생성한 조직 저장
		$this->input->set_cookie('activeOrg', $org_id, 86400);
		$this->session->set_userdata([
			'current_org_id' => $org_id,
			'current_org_name' => $org_name
		]);

		log_message('info', "사용자 {$user_id}가 새로운 조직 {$org_id}({$org_name})을 생성했습니다.");

		echo json_encode([
			'success' => true,
			'message' => '조직이 성공적으로 생성되었습니다.',
			'org_id' => $org_id,
			'org_name' => $org_name,
			'redirect_url' => base_url('org')
		]);
	}

	/**
	 * 중복되지 않는 5자리 초대코드 생성
	 */
	private function generate_unique_invite_code()
	{
		$max_attempts = 100;
		$attempt = 0;

		do {
			$invite_code = $this->generate_invite_code();
			$existing_org = $this->Org_model->get_org_by_invite_code($invite_code);
			$attempt++;

			if ($attempt >= $max_attempts) {
				log_message('error', '초대코드 생성 시도 한계 도달');
				break;
			}
		} while ($existing_org && $existing_org['del_yn'] == 'N');

		return $invite_code;
	}

	/**
	 * 5자리 랜덤 초대코드 생성
	 */
	private function generate_invite_code()
	{
		$characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$code = '';

		for ($i = 0; $i < 5; $i++) {
			$code .= $characters[random_int(0, strlen($characters) - 1)];
		}

		return $code;
	}

	/**
	 * 초대 링크를 통한 접속 처리
	 */
	public function invite($token = null)
	{
		if (!$token) {
			show_404();
			return;
		}

		$this->load->model('User_management_model');

		$invite_info = $this->User_management_model->get_invite_by_token($token);

		if (!$invite_info) {
			$this->session->set_flashdata('error', '유효하지 않은 초대 링크입니다.');
			redirect('login');
			return;
		}

		if ($invite_info['status'] === 'sent') {
			$this->User_management_model->update_invite_status($invite_info['idx'], 'opened', 'opened_date');
		}

		$this->session->set_userdata([
			'invite_token' => $token,
			'invite_org_id' => $invite_info['org_id']
		]);

		$data['invite_info'] = $invite_info;
		$this->load->view('login', $data);
	}
}
