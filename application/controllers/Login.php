<?php
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

//        print_r($this->session->userdata);
//        exit;

        $data = array(
            'user_id' => $this->session->userdata('user_id'),
            'user_name' => $this->session->userdata('user_name'),
            'user_email' => $this->session->userdata('user_email')
        );
        $this->load->view('join', $data);
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
                    redirect('mypage');
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
        redirect($oauth_url);
    }

    public function naver_callback()
    {
        define('NAVER_CLIENT_ID', 'Cw90dWKPQbexg4b4I8Kv');
        define('NAVER_CLIENT_SECRET', 'R1fnbUjh7X');
        define('NAVER_CALLBACK_URL', base_url('/login/naver_callback'));

        $client_id = NAVER_CLIENT_ID;
        $client_secret = NAVER_CLIENT_SECRET;
        $code = $_GET["code"];
        $state = $_GET["state"];
        $redirectURI = urlencode(NAVER_CALLBACK_URL);
        $url = "https://nid.naver.com/oauth2.0/token?grant_type=authorization_code&client_id=" . $client_id . "&client_secret=" . $client_secret . "&redirect_uri=" . $redirectURI . "&code=" . $code . "&state=" . $state;

        $is_post = false;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, $is_post);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($status_code == 200) {
            $responseArr = json_decode($response, true);

            $access_token = $responseArr['access_token'];
            $refresh_token = $responseArr['refresh_token'];

            // 액세스 토큰을 이용하여 사용자 정보 가져오기
            $me_headers = array(
                'Content-Type: application/json',
                sprintf('Authorization: Bearer %s', $access_token)
            );
            $me_is_post = false;
            $me_ch = curl_init();
            curl_setopt($me_ch, CURLOPT_URL, "https://openapi.naver.com/v1/nid/me");
            curl_setopt($me_ch, CURLOPT_POST, $me_is_post);
            curl_setopt($me_ch, CURLOPT_HTTPHEADER, $me_headers);
            curl_setopt($me_ch, CURLOPT_RETURNTRANSFER, true);
            $me_response = curl_exec($me_ch);
            $me_status_code = curl_getinfo($me_ch, CURLINFO_HTTP_CODE);
            curl_close($me_ch);

            $me_responseArr = json_decode($me_response, true);


            if ($me_responseArr['resultcode'] == '00') {
                $user_id = 'naver_' . $me_responseArr['response']['id'];
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
                    redirect('mypage');
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
            redirect('mypage');
        } else {
            // 회원가입이 되어 있지 않은 경우 회원가입 페이지로 이동
            redirect('login/join');
        }

    }





    public function invite($invite_code) {
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


    public function process()
    {
        $this->load->model('User_model');

        $user_id = $this->session->userdata('user_id');
        $user_email = $this->session->userdata('user_email');
        $user_name = $this->session->userdata('user_name');
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


//        print_r($user_id);
//        print_r($user_email);
//        print_r($user_name);
//        exit;


        if (!$user_exists) {
            $data = array(
                'user_id' => $user_id,
                'user_name' => $user_name,
                'user_mail' => $user_email,
                'regi_date' => date('Y-m-d H:i:s'),
                'modi_date' => date('Y-m-d H:i:s')
            );
            $this->User_model->insert_user($data);


//            print_r($data);
//            exit;

        }

        redirect('login');
    }



}
