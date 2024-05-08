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
        $this->load->view('login');
    }

    public function google_login()
    {
        require_once APPPATH . '../vendor/autoload.php';

        $client = new Google_Client();
        $config = [
            'client_id' => '665369034498-bf7jrt09lasbit0f5cb4ppne8rs67nt8.apps.googleusercontent.com',
            'client_secret' => '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',
            'redirect_uris' => ['https://wani.im/login/google_login'],
            'access_type' => 'offline',
            'approval_prompt' => 'force',
        ];
        $client->setAuthConfig($config);
        $client->addScope('email');
        $client->addScope('profile');
        $redirect_uri = 'https://wani.im/login/google_login';
        $client->setRedirectUri($redirect_uri);

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
                    'name' => $user_info->name,
                    'email' => $user_info->email,
                    'picture' => $user_info->picture,
                );

                $this->session->set_userdata($user_data);

                // 사용자 정보를 wb_user 테이블에 저장
                $this->load->model('User_model');
                $user_id = $user_info->email;
                $user_name = $user_info->name;
                $user_exists = $this->User_model->check_user($user_id);

                if (!$user_exists) {
                    $data = array(
                        'user_id' => $user_id,
                        'user_name' => $user_name,
                        'group_id' => '',
                        'user_grade' => 1,
                        'user_mail' => $user_info->email,
                        'regi_date' => date('Y-m-d H:i:s'),
                        'modi_date' => date('Y-m-d H:i:s')
                    );
                    $this->User_model->insert_user($data);
                }

                redirect('mypage');
            } else {
                redirect('login');
            }
        } else {
            $auth_url = $client->createAuthUrl();
            redirect($auth_url);
        }
    }


    public function naver_login(){
        define('NAVER_CLIENT_ID', 'Cw90dWKPQbexg4b4I8Kv');
        define('NAVER_CALLBACK_URL', base_url('/login/naver_callback'));
        $state = uniqid();

        $apiURL = "https://nid.naver.com/oauth2.0/authorize?response_type=code&client_id=".NAVER_CLIENT_ID."&redirect_uri=".urlencode(NAVER_CALLBACK_URL)."&state=".$state;



        redirect($apiURL);
    }

    public function naver_callback(){
        define('NAVER_CLIENT_ID', 'Cw90dWKPQbexg4b4I8Kv');
        define('NAVER_CLIENT_SECRET', 'R1fnbUjh7X');
        define('NAVER_CALLBACK_URL', base_url('/login/naver_callback'));

        $client_id = NAVER_CLIENT_ID;
        $client_secret = NAVER_CLIENT_SECRET;
        $code = $_GET["code"];
        $state = $_GET["state"];
        $redirectURI = urlencode(NAVER_CALLBACK_URL);
        $url = "https://nid.naver.com/oauth2.0/token?grant_type=authorization_code&client_id=".$client_id."&client_secret=".$client_secret."&redirect_uri=".$redirectURI."&code=".$code."&state=".$state;
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

            $this->session->set_userdata('naver_access_token', $responseArr['access_token']);
            $this->session->set_userdata('naver_refresh_token', $responseArr['refresh_token']);

            // 토큰값으로 네이버 회원정보 가져오기
            $me_headers = array(
                'Content-Type: application/json',
                sprintf('Authorization: Bearer %s', $responseArr['access_token'])
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

            if ($me_responseArr['response']['id']) {
                // 회원아이디(naver_ 접두사에 네이버 아이디를 붙여줌)
                $mb_uid = 'naver_'.$me_responseArr['response']['id'];

                // 회원정보가 있는지 확인
                $this->load->model('User_model');
                $user_exists = $this->User_model->check_user($mb_uid);

                if ($user_exists) {
                    // 멤버 DB에 토큰값 업데이트
                    $user_data = array(
                        'access_token' => $responseArr['access_token'],
                        'refresh_token' => $responseArr['refresh_token']
                    );
                    $this->User_model->update_user($mb_uid, $user_data);

                    // 로그인 처리
                    $user_info = $this->User_model->get_user_by_id($mb_uid);
                    $this->session->set_userdata($user_info);
                    print_r('로그인');


                    redirect('/mypage/index');
                } else {
                    // 회원정보가 없다면 회원가입



                    $user_id = $mb_uid;
                    $user_name = $me_responseArr['response']['name'];
                    $user_mail = $me_responseArr['response']['email'];
                    $mb_profile_image = $me_responseArr['response']['profile_image']; // 프로필 이미지

                    $data = array(
                        'user_id' => $user_id,
                        'user_name' => $user_name,
                        'user_mail' => $user_mail,
                        'user_profile_image' => $mb_profile_image
                    );

                    $this->User_model->insert_user($data);

                    // 로그인 처리
                    $this->session->set_userdata($data);
                    redirect('mypage');
                }
            } else {
                // 회원정보를 가져오지 못했습니다.
                print_r('회원정보를 가져오지 못했습니다.');
                exit;
                redirect('login');
            }
        } else {
            print_r('토큰값을 가져오지 못했습니다.');
            exit;
            // 토큰값을 가져오지 못했습니다.
            redirect('login');
        }
    }

    public function kakao_login()
    {
        require_once APPPATH . '../vendor/autoload.php';

        $client_id = '클라이언트 ID';
        $redirect_uri = base_url('/login/kakao_login');
        $client_secret = 'ArAxCbw7MlG7IQQvUzcsiZxzYUa3RnKf';

        $kakao = new Kakao($client_id, $redirect_uri, $client_secret);

        if (isset($_GET['code'])) {
            $token = $kakao->getAccessToken($_GET['code']);

            if (isset($token['access_token'])) {
                $user_info = $kakao->getUserProfile($token['access_token']);

                $user_data = array(
                    'name' => $user_info['properties']['nickname'],
                    'email' => $user_info['kakao_account']['email'],
                    'picture' => $user_info['properties']['profile_image'],
                );

                $this->session->set_userdata($user_data);

                // 사용자 정보를 wb_user 테이블에 저장
                $this->load->model('User_model');
                $user_id = $user_info['kakao_account']['email'];
                $user_name = $user_info['properties']['nickname'];
                $user_exists = $this->User_model->check_user($user_id);

                if (!$user_exists) {
                    $data = array(
                        'user_id' => $user_id,
                        'user_name' => $user_name,
                        'group_id' => '',
                        'user_grade' => 1,
                        'user_mail' => $user_info['kakao_account']['email'],
                        'regi_date' => date('Y-m-d H:i:s'),
                        'modi_date' => date('Y-m-d H:i:s')
                    );
                    $this->User_model->insert_user($data);
                }

                redirect('mypage');
            } else {
                redirect('login');
            }
        } else {
            $auth_url = $kakao->getAuthorizationUrl();
            redirect($auth_url);
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

}
