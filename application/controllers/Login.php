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






}
