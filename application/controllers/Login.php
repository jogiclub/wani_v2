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
	}

	public function index(){
		$user_id = $this->session->userdata('user_id');
		if($user_id) {
			redirect('main/logout');
		}
		$this->load->view('login');
	}

	public function terms(){
		$this->load->view('terms');
	}

	public function privacy(){
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

	/**
	 * 로그인 성공 후 조직 정보 설정
	 */
	private function setup_user_organization($user_id, $master_yn)
	{
		$this->load->model('Org_model');

		// 사용자가 속한 조직 목록 가져오기
		if ($master_yn === "N") {
			$user_orgs = $this->Org_model->get_user_orgs($user_id);
		} else {
			$user_orgs = $this->Org_model->get_user_orgs_master($user_id);
		}

		// 사용자에게 조직이 있는 경우 첫 번째 조직을 기본값으로 설정
		if (!empty($user_orgs)) {
			$default_org = $user_orgs[0];

			// 쿠키에 활성 조직 설정 (24시간)
			$this->input->set_cookie('activeOrg', $default_org['org_id'], 86400);

			// 세션에도 현재 조직 정보 저장
			$this->session->set_userdata('current_org_id', $default_org['org_id']);
			$this->session->set_userdata('current_org_name', $default_org['org_name']);

			log_message('info', "사용자 {$user_id}의 기본 조직이 {$default_org['org_id']}로 설정되었습니다.");

			return true;
		}

		return false;
	}

	public function google_login()
	{
		require_once APPPATH . '../vendor/autoload.php';

		$client = new Google_Client();
		$config = [
			'client_id' => '665369034498-bf7jrt09lasbit0f5cb4ppne8rs67nt8.apps.googleusercontent.com',
			'client_secret' => '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',
			'redirect_uris' => ['https://wani.im/login/google_callback'],
			'access_type' => 'offline',
			'approval_prompt' => 'force',
		];
		$client->setAuthConfig($config);
		$client->addScope('email');
		$client->addScope('profile');
		$redirect_uri = 'https://wani.im/login/google_callback';
		$client->setRedirectUri($redirect_uri);
		$auth_url = $client->createAuthUrl();
		redirect($auth_url);
	}

	public function google_callback()
	{
		require_once APPPATH . '../vendor/autoload.php';

		$client = new Google_Client();
		$config = [
			'client_id' => '665369034498-bf7jrt09lasbit0f5cb4ppne8rs67nt8.apps.googleusercontent.com',
			'client_secret' => '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',
			'redirect_uris' => ['https://wani.im/login/google_callback'],
			'access_type' => 'offline',
			'approval_prompt' => 'force',
		];
		$client->setAuthConfig($config);

		if (isset($_GET['code'])) {
			$token = $client->fetchAccessTokenWithAuthCode($_GET['code']);

			if (isset($token['access_token'])) {
				$client->setAccessToken($token['access_token']);

				// 리프레시 토큰 저장
				if (isset($token['refresh_token'])) {
					$this->session->set_userdata('refresh_token', $token['refresh_token']);
				}

				$this->session->set_userdata('access_token', $token['access_token']);

				// 사용자 정보 가져오기
				$oauth = new Google\Service\Oauth2($client);
				$user_info = $oauth->userinfo->get();

				$user_data = array(
					'user_id' => $user_info->email,
					'user_name' => $user_info->name,
					'user_email' => $user_info->email,
					'picture' => $user_info->picture,
				);
				$this->session->set_userdata($user_data);

				// 사용자 정보를 wb_user 테이블에서 확인
				$this->load->model('User_model');
				$user_id = $user_info->email;
				$user_exists = $this->User_model->check_user($user_id);

				if ($user_exists) {
					// 회원가입이 되어 있는 경우 로그인 처리
					$user = $this->User_model->get_user_by_id($user_id);
					$user_data['user_grade'] = $user['user_grade'];
					$user_data['user_hp'] = $user['user_hp'];
					$user_data['master_yn'] = $user['master_yn']; // master_yn 값 세션에 추가
					$this->session->set_userdata($user_data);

					// 로그인 성공 후 조직 정보 설정
					$has_org = $this->setup_user_organization($user_id, $user['master_yn']);

					redirect('main');
				} else {
					// 회원가입이 되어 있지 않은 경우 회원가입 페이지로 이동
					redirect('login/join');
				}

			} else {
				redirect('login');
			}
		} else {
			redirect('login');
		}
	}

	public function naver_login()
	{
		define('NAVER_CLIENT_ID', 'Cw90dWKPQbexg4b4I8Kv');
		define('NAVER_CALLBACK_URL', base_url('/login/naver_callback'));
		$state = uniqid();

		$oauth_url = "https://nid.naver.com/oauth2.0/authorize?response_type=code&client_id=" . NAVER_CLIENT_ID . "&redirect_uri=" . urlencode(NAVER_CALLBACK_URL) . "&state=" . $state;
		$this->session->set_userdata('oauth_state', $state);
		redirect($oauth_url);
	}

	public function naver_callback()
	{
		define('NAVER_CLIENT_ID', 'Cw90dWKPQbexg4b4I8Kv');
		define('NAVER_CLIENT_SECRET', 'MdUHZlLXl2');

		$code = $this->input->get('code');
		$state = $this->input->get('state');
		$stored_state = $this->session->userdata('oauth_state');

		if ($code && $state && $state === $stored_state) {
			$token_url = "https://nid.naver.com/oauth2.0/token?grant_type=authorization_code&client_id=" . NAVER_CLIENT_ID . "&client_secret=" . NAVER_CLIENT_SECRET . "&redirect_uri=" . urlencode(base_url('/login/naver_callback')) . "&code=" . $code . "&state=" . $state;

			$response = file_get_contents($token_url);
			$token_info = json_decode($response, true);

			if (isset($token_info['access_token'])) {
				$access_token = $token_info['access_token'];

				$me_url = "https://openapi.naver.com/v1/nid/me";
				$opts = array(
					'http' => array(
						'method' => "GET",
						'header' => "Authorization: Bearer " . $access_token
					)
				);
				$context = stream_context_create($opts);
				$me_response = file_get_contents($me_url, false, $context);
				$me_responseArr = json_decode($me_response, true);

				$user_id = $me_responseArr['response']['id'];
				$user_name = $me_responseArr['response']['name'];
				$user_email = $me_responseArr['response']['email'];

				// 사용자 정보를 세션에 저장
				$user_data = array(
					'user_id' => $user_id,
					'user_name' => $user_name,
					'user_email' => $user_email
				);

				$this->session->set_userdata($user_data);

				// 회원가입 여부 확인
				$this->load->model('User_model');
				$user_exists = $this->User_model->check_user($user_id);

				if ($user_exists) {
					// 회원가입이 되어 있는 경우 로그인 처리
					$user = $this->User_model->get_user_by_id($user_id);
					$user_data['user_grade'] = $user['user_grade'];
					$user_data['user_hp'] = $user['user_hp'];
					$user_data['master_yn'] = $user['master_yn']; // master_yn 값 세션에 추가
					$this->session->set_userdata($user_data);

					// 로그인 성공 후 조직 정보 설정
					$has_org = $this->setup_user_organization($user_id, $user['master_yn']);

					redirect('main');
				} else {
					// 회원가입이 되어 있지 않은 경우 회원가입 페이지로 이동
					redirect('login/join');
				}
			} else {
				// 사용자 정보를 가져오지 못한 경우 에러 처리
				redirect('login');
			}
		} else {
			// 액세스 토큰을 가져오지 못한 경우 에러 처리
			redirect('login');
		}
	}

	public function kakao_login()
	{
		$client_id = 'bf21c48aa301d2094730e9b67cd433ea';
		$redirect_uri = base_url('/login/kakao_callback');
		$kakao_oauth_url = "https://kauth.kakao.com/oauth/authorize?client_id={$client_id}&redirect_uri={$redirect_uri}&response_type=code";
		redirect($kakao_oauth_url);
	}

	public function kakao_callback()
	{
		$client_id = 'bf21c48aa301d2094730e9b67cd433ea';
		$client_secret = 'FwEMvQDxbSFRViAofZDCJhNeags0pFCQ';
		$redirect_uri = base_url('/login/kakao_callback');

		$code = $this->input->get('code');

		$token_url = "https://kauth.kakao.com/oauth/token";
		$token_data = array(
			'grant_type' => 'authorization_code',
			'client_id' => $client_id,
			'client_secret' => $client_secret,
			'redirect_uri' => $redirect_uri,
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
		$access_token = $response_data['access_token'];

		$user_url = "https://kapi.kakao.com/v2/user/me";
		$user_headers = array(
			"Authorization: Bearer {$access_token}",
			"Content-Type: application/x-www-form-urlencoded;charset=utf-8"
		);

		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $user_url);
		curl_setopt($curl, CURLOPT_HTTPHEADER, $user_headers);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		$response = curl_exec($curl);
		curl_close($curl);

		$user_data = json_decode($response, true);

		$user_id = 'kakao_' . $user_data['id'];
		$user_name = $user_data['properties']['nickname'];
		$user_email = $user_data['kakao_account']['email'];

		// 사용자 정보를 세션에 저장
		$user_data = array(
			'user_id' => $user_id,
			'user_name' => $user_name,
			'user_email' => $user_email
		);

		$this->session->set_userdata($user_data);

		// 사용자 정보를 wb_user 테이블에서 확인
		$this->load->model('User_model');
		$user_exists = $this->User_model->check_user($user_id);

		if ($user_exists) {
			// 회원가입이 되어 있는 경우 로그인 처리
			$user = $this->User_model->get_user_by_id($user_id);
			$user_data['user_grade'] = $user['user_grade'];
			$user_data['user_hp'] = $user['user_hp'];
			$user_data['master_yn'] = $user['master_yn']; // master_yn 값 세션에 추가
			$this->session->set_userdata($user_data);

			// 로그인 성공 후 조직 정보 설정
			$has_org = $this->setup_user_organization($user_id, $user['master_yn']);

			redirect('main');
		} else {
			// 회원가입이 되어 있지 않은 경우 회원가입 페이지로 이동
			redirect('login/join');
		}
	}

	public function invite($invite_code)
	{
		$this->load->model('Invite_model');
		$invite = $this->Invite_model->get_invite_by_code($invite_code);

		if ($invite) {
			if ($invite['del_yn'] == 'N') {
				$this->load->model('User_model');
				$user_data = array(
					'user_grade' => 1
				);
				$this->User_model->update_user($invite['invite_mail'], $user_data);

				// 초대 코드 비활성화
				$this->Invite_model->update_invite($invite_code, array('del_yn' => 'Y'));

				// 로그인 페이지로 리다이렉트
				redirect('login');
			} else {
				// 이미 사용된 초대 코드인 경우 알림 표시
				echo '<script>alert("유효한 코드가 아닙니다.");</script>';
				redirect('login');
			}
		} else {
			// 초대 코드가 유효하지 않은 경우 에러 페이지로 리다이렉트
			redirect('login');
		}
	}

	/**
	 * 역할: 회원가입 처리 및 초대코드 검증
	 */
	public function process()
	{
		$this->load->model('User_model');
		$this->load->model('Org_model');

		$user_id = $this->session->userdata('user_id');
		$user_email = $this->session->userdata('user_email');
		$user_name = $this->session->userdata('user_name');
		$invite_code = $this->input->post('invite_code');
		$user_exists = $this->User_model->check_user($user_id);

		if($user_email) {
			$user_email = $this->session->userdata('user_email');
		} else {
			$user_email = $this->input->post('user_email');
		}

		if($this->session->userdata('user_name')) {
			$user_name = $this->session->userdata('user_name');
		} else {
			$user_name = $this->input->post('user_name');
		}

		// 초대코드 필수 검증
		if (empty($invite_code)) {
			$response = array(
				'success' => false,
				'message' => '초대코드를 입력해주세요.'
			);
			echo json_encode($response);
			return;
		}

		// 초대코드 유효성 검증
		$org = $this->Org_model->get_org_by_invite_code($invite_code);
		if (!$org || $org['del_yn'] == 'Y') {
			$response = array(
				'success' => false,
				'message' => '유효한 초대코드가 아닙니다. 확인 후 다시 입력바랍니다.'
			);
			echo json_encode($response);
			return;
		}

		// 사용자 정보 저장 (회원가입)
		if (!$user_exists) {
			$user_data = array(
				'user_id' => $user_id,
				'user_name' => $user_name,
				'user_mail' => $user_email,
				'regi_date' => date('Y-m-d H:i:s'),
				'modi_date' => date('Y-m-d H:i:s')
			);
			$this->User_model->insert_user($user_data);
		}

		// 초대코드가 유효한 경우 조직 가입 처리
		$join_result = $this->process_invite_code($user_id, $invite_code);

		if ($join_result) {
			$response = array(
				'success' => true,
				'message' => '회원가입이 완료되었습니다.'
			);
			echo json_encode($response);
		} else {
			$response = array(
				'success' => false,
				'message' => '회원가입 중 오류가 발생했습니다.'
			);
			echo json_encode($response);
		}
	}

	/**
	 * 역할: 초대코드 처리 및 조직 가입
	 */
	private function process_invite_code($user_id, $invite_code)
	{
		// 초대코드로 조직 찾기
		$org = $this->Org_model->get_org_by_invite_code($invite_code);

		if ($org && $org['del_yn'] == 'N') {
			// 이미 해당 조직에 가입되어 있는지 확인
			$existing_membership = $this->Org_model->check_user_org_membership($user_id, $org['org_id']);

			if (!$existing_membership) {
				$org_user_data = array(
					'user_id' => $user_id,
					'org_id' => $org['org_id'],
					'level' => 0, // 기본 레벨
					'join_date' => date('Y-m-d H:i:s')
				);

				$result = $this->Org_model->insert_org_user($org_user_data);

				if ($result) {
					log_message('info', "사용자 {$user_id}가 초대코드 {$invite_code}로 조직 {$org['org_id']}에 가입했습니다.");
					return true;
				}
			} else {
				// 이미 가입된 조직인 경우도 성공으로 처리
				return true;
			}
		}

		return false;
	}
}
