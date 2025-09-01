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
	 * 초대받은 조직들의 상태를 활성화 (level 0 -> 1)
	 */
	private function activate_pending_invites($user_id)
	{
		// 초대받은 조직 목록 조회 (level=0)
		$pending_invites = $this->User_management_model->get_user_pending_invites($user_id);

		if (!empty($pending_invites)) {
			foreach ($pending_invites as $invite) {
				// 레벨 0에서 1로 업데이트
				$activated = $this->User_management_model->activate_invited_user($user_id, $invite['org_id']);

				if ($activated) {
					// 초대 메일 로그의 상태도 업데이트
					$invite_log = $this->User_management_model->get_invite_log($user_id, $invite['org_id']);
					if ($invite_log) {
						$this->User_management_model->update_invite_status($invite_log['idx'], 'joined', 'joined_date');
					}

					log_message('info', "사용자 {$user_id}가 조직 {$invite['org_id']}에 가입 완료 (level 0->1)");
				}
			}

			log_message('info', "사용자 {$user_id}의 " . count($pending_invites) . "개 초대 조직 활성화 처리 완료");
		}
	}

	/**
	 * 로그인 성공 후 조직 정보 설정 및 초대 상태 업데이트
	 */
	private function setup_user_organization($user_id, $master_yn)
	{
		$this->load->model('Org_model');

		// 초대받은 조직들의 레벨을 0에서 1로 업데이트
		$this->activate_pending_invites($user_id);

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


	/**
	 * Google 로그인 콜백 처리 수정
	 */
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

					// 초대받았지만 아직 이름이 없는 경우 업데이트
					if (empty($user['user_name']) && !empty($user_info->name)) {
						$update_data = array(
							'user_name' => $user_info->name,
							'modi_date' => date('Y-m-d H:i:s')
						);
						$this->User_model->update_user($user_id, $update_data);
						$user['user_name'] = $user_info->name;
					}

					// 사용자 정보를 세션에 저장 (기본값 설정)
					$user_data['user_grade'] = isset($user['user_grade']) ? $user['user_grade'] : 0;
					$user_data['user_hp'] = isset($user['user_hp']) ? $user['user_hp'] : '';
					$user_data['master_yn'] = isset($user['master_yn']) ? $user['master_yn'] : 'N';
					$this->session->set_userdata($user_data);

					// 로그인 성공 후 조직 정보 설정 및 초대 상태 업데이트
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




	/**
	 * 파일 위치: application/controllers/Login.php - create_organization() 함수
	 * 역할: 새로운 조직 생성 및 최고관리자로 등록
	 */
	public function create_organization()
	{
		// POST 요청만 허용
		if ($this->input->method() !== 'post') {
			show_404();
			return;
		}

		// 로그인 확인
		$user_id = $this->session->userdata('user_id');
		if (!$user_id) {
			$response = array(
				'success' => false,
				'message' => '로그인이 필요합니다.'
			);
			echo json_encode($response);
			return;
		}

		// 입력값 검증
		$org_name = trim($this->input->post('org_name'));
		$org_type = trim($this->input->post('org_type'));
		$org_desc = trim($this->input->post('org_desc'));

		if (empty($org_name)) {
			$response = array(
				'success' => false,
				'message' => '조직명을 입력해주세요.'
			);
			echo json_encode($response);
			return;
		}

		if (empty($org_type)) {
			$response = array(
				'success' => false,
				'message' => '조직유형을 선택해주세요.'
			);
			echo json_encode($response);
			return;
		}

		$this->load->model('Org_model');
		$this->load->model('User_model');

		// 사용자 정보 확인 및 생성
		$user_exists = $this->User_model->check_user($user_id);
		if (!$user_exists) {
			// 사용자 정보 생성
			$user_data = array(
				'user_id' => $user_id,
				'user_name' => $this->session->userdata('user_name'),
				'user_mail' => $this->session->userdata('user_email'),
				'regi_date' => date('Y-m-d H:i:s'),
				'modi_date' => date('Y-m-d H:i:s')
			);
			$this->User_model->insert_user($user_data);
		}

		// 5자리 초대코드 생성 (중복 확인)
		$invite_code = $this->generate_unique_invite_code();

		// 조직 정보 준비
		$org_data = array(
			'org_name' => $org_name,
			'org_type' => $org_type,
			'org_desc' => $org_desc,
			'invite_code' => $invite_code,
			'regi_date' => date('Y-m-d H:i:s'),
			'modi_date' => date('Y-m-d H:i:s'),
			'del_yn' => 'N'
		);

		// 트랜잭션 시작
		$this->db->trans_start();

		// 조직 생성
		$org_result = $this->Org_model->insert_organization($org_data);

		if (!$org_result) {
			$this->db->trans_rollback();
			$response = array(
				'success' => false,
				'message' => '조직 생성 중 오류가 발생했습니다.'
			);
			echo json_encode($response);
			return;
		}

		// 생성된 조직 ID 가져오기
		$org_id = $this->db->insert_id();

		// 조직 사용자 등록 (최고관리자 레벨 10)
		$org_user_data = array(
			'user_id' => $user_id,
			'org_id' => $org_id,
			'level' => 10, // 최고관리자
			'join_date' => date('Y-m-d H:i:s')
		);

		$org_user_result = $this->Org_model->insert_org_user($org_user_data);

		if (!$org_user_result) {
			$this->db->trans_rollback();
			$response = array(
				'success' => false,
				'message' => '관리자 등록 중 오류가 발생했습니다.'
			);
			echo json_encode($response);
			return;
		}

		// 트랜잭션 완료
		$this->db->trans_complete();

		if ($this->db->trans_status() === FALSE) {
			$response = array(
				'success' => false,
				'message' => '조직 생성 중 오류가 발생했습니다.'
			);
			echo json_encode($response);
			return;
		}

		// 세션에 조직 정보 설정
		$this->input->set_cookie('activeOrg', $org_id, 86400);
		$this->session->set_userdata('current_org_id', $org_id);
		$this->session->set_userdata('current_org_name', $org_name);

		log_message('info', "사용자 {$user_id}가 새로운 조직 {$org_id}({$org_name})을 생성했습니다.");

		$response = array(
			'success' => true,
			'message' => '조직이 성공적으로 생성되었습니다.',
			'org_id' => $org_id,
			'redirect_url' => base_url('org')
		);
		echo json_encode($response);
	}

	/**
	 * 파일 위치: application/controllers/Login.php - generate_unique_invite_code() 함수
	 * 역할: 중복되지 않는 5자리 초대코드 생성
	 */
	private function generate_unique_invite_code()
	{
		$this->load->model('Org_model');

		$max_attempts = 100; // 최대 시도 횟수
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
	 * 파일 위치: application/controllers/Login.php - generate_invite_code() 함수
	 * 역할: 5자리 랜덤 초대코드 생성
	 */
	private function generate_invite_code()
	{
		$characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$code = '';
		$length = 5;

		for ($i = 0; $i < $length; $i++) {
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
		}

		// 초대 정보 조회
		$invite_info = $this->User_management_model->get_invite_by_token($token);

		if (!$invite_info) {
			$this->session->set_flashdata('error', '유효하지 않은 초대 링크입니다.');
			redirect('login');
			return;
		}

		// 초대 상태 업데이트 (열람됨)
		if ($invite_info['status'] === 'sent') {
			$this->User_management_model->update_invite_status($invite_info['idx'], 'opened', 'opened_date');
		}

		// 세션에 초대 정보 저장
		$this->session->set_userdata('invite_token', $token);
		$this->session->set_userdata('invite_org_id', $invite_info['org_id']);

		// 로그인 페이지로 리다이렉트 (초대 정보 포함)
		$data['invite_info'] = $invite_info;
		$this->load->view('login', $data);
	}




}
