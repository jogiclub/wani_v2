<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Main_20240426 extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->library('session');
        $this->load->helper('url');
    }



    public function index() {
        if ($this->session->userdata('email')) {
            $data['user'] = $this->session->userdata();

            $user_id = $this->session->userdata('email');
            $this->load->model('Group_model');
            $data['groups'] = $this->Group_model->get_user_groups($user_id);

            // 현재 주차 범위 계산
            $currentDate = date('Y-m-d');
            $data['current_week_range'] = $this->getWeekRange($currentDate, true);

            // 전체 주차 범위 계산
            $allWeekRanges = array();
            $startDate = date('Y-m-d', strtotime('first day of January ' . date('Y')));
            $endDate = date('Y-m-d', strtotime('last day of December ' . date('Y')));

            while ($startDate <= $endDate) {
                $allWeekRanges[] = $this->getWeekRange($startDate);
                $startDate = date('Y-m-d', strtotime('+1 week', strtotime($startDate)));
            }

            $data['all_week_ranges'] = array_reverse($allWeekRanges);

            // 활성화된 그룹의 group_name 가져오기
            $activeGroupId = $this->input->cookie('activeGroup');
            if ($activeGroupId) {
                $activeGroup = $this->Group_model->get_group_by_id($activeGroupId);
                $data['group_name'] = $activeGroup['group_name'];
            } else {
                $user_id = $this->session->userdata('email');
                $this->load->model('Group_model');
                $min_group_id = $this->Group_model->get_min_group_id($user_id);

                if ($min_group_id) {
                    $activeGroup = $this->Group_model->get_group_by_id($min_group_id);
                    $data['group_name'] = $activeGroup['group_name'];
                    $this->input->set_cookie('activeGroup', $min_group_id, 86400); // 쿠키 설정 (만료 시간: 1일)
                } else {
                    $data['group_name'] = ''; // 그룹이 없는 경우 기본값 설정
                }
            }

            // 활성화된 그룹의 출석 종류 가져오기
            $active_group_id = $this->input->cookie('activeGroup');
            $this->load->model('Attendance_model');
            $data['attendance_types'] = $this->Attendance_model->get_attendance_types_by_group($active_group_id);


            $this->load->view('main', $data);
        } else {
            redirect('main/login');
        }
    }




    public function add_member() {
        if ($this->input->is_ajax_request()) {
            $group_id = $this->input->post('group_id');
            $member_name = $this->input->post('member_name');

            $data = array(
                'group_id' => $group_id,
                'grade' => 0,
                'area' => '새가족',
                'member_name' => $member_name,
                'member_nick' => $member_name,
                'new_yn' => 'Y',
                'del_yn' => 'N',
                'regi_date' => date('Y-m-d H:i:s'),
                'modi_date' => date('Y-m-d H:i:s')
            );

            $this->load->model('Member_model');
            $result = $this->Member_model->add_member($data);

            if ($result) {
                $response = array('status' => 'success');
            } else {
                $response = array('status' => 'error');
            }

            echo json_encode($response);
        }
    }

    public function get_attendance_data() {
        if ($this->input->is_ajax_request()) {
            $group_id = $this->input->post('group_id');
            $start_date = $this->input->post('start_date');
            $end_date = $this->input->post('end_date');

            $this->load->model('Attendance_model');
            $attendance_data = $this->Attendance_model->get_group_attendance_data($group_id, $start_date, $end_date);

            echo json_encode($attendance_data);
        }
    }


    public function get_members() {
        if ($this->input->is_ajax_request()) {
            $group_id = $this->input->post('group_id');
            $start_date = $this->input->post('start_date');
            $end_date = $this->input->post('end_date');

            $this->load->model('Member_model');

            $members = $this->Member_model->get_group_members($group_id, $start_date, $end_date);
//            print_r($members);
//            exit;
            echo json_encode($members);
        }
    }




    function getWeekRange($date, $includeWeek = false) {
        // 입력된 날짜의 타임스탬프 가져오기
        $timestamp = strtotime($date);
        // 현재 주의 일요일 타임스탬프 구하기
        $sundayTimestamp = strtotime('last sunday', $timestamp);
        // 다음 주 일요일 타임스탬프 구하기
        $nextSundayTimestamp = strtotime('+1 week', $sundayTimestamp);
        // 주차 계산
        $week = date('W', $sundayTimestamp);
        // 시작일과 종료일 가져오기
        $startDate = date('Y.m.d', $sundayTimestamp);
        $endDate = date('Y.m.d', $nextSundayTimestamp - (24 * 60 * 60)); // 1일을 빼서 토요일로 만듦
        // 출력 형식 지정
        $output = "{$startDate}~{$endDate}";
        if ($includeWeek) {
            $output .= " ({$week}주차)";
        }
        return $output;
    }



    public function get_attendance_types() {
        if ($this->input->is_ajax_request()) {
            $group_id = $this->input->post('group_id');
            $this->load->model('Attendance_model');
            $attendance_types = $this->Attendance_model->get_attendance_types($group_id);
            $response = array(
                'attendance_types' => $attendance_types
            );

            echo json_encode($response);
        }
    }


    public function save_attendance() {
        if ($this->input->is_ajax_request()) {
            $member_idx = $this->input->post('member_idx');
            $attendance_data = json_decode($this->input->post('attendance_data'), true);
            $group_id = $this->input->post('group_id');
            $att_date = $this->input->post('att_date');

            $this->load->model('Attendance_model');

            // 해당 날짜의 모든 출석 정보 삭제
            $this->Attendance_model->delete_attendance_by_date($member_idx, $att_date);

            if (!empty($attendance_data) && is_array($attendance_data)) {
                foreach ($attendance_data as $att_type_idx) {
                    $data = array(
                        'att_date' => $att_date,
                        'att_type_idx' => $att_type_idx,
                        'member_idx' => $member_idx,
                        'group_id' => $group_id
                    );

                    $this->db->insert('wb_member_att', $data);
                }

                $response = array('status' => 'success');
            } else {
                $response = array('status' => 'success', 'message' => 'Attendance data deleted successfully.');
            }

            echo json_encode($response);
        }
    }


    public function google_login() {
        require_once APPPATH . '../vendor/autoload.php';

        $client = new Google_Client();
        $config = [
            'client_id' => '665369034498-bf7jrt09lasbit0f5cb4ppne8rs67nt8.apps.googleusercontent.com',
            'client_secret' => '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',
            'redirect_uris' => ['https://simple.webhows.com/main/google_login'],
            'access_type' => 'offline',
            'approval_prompt' => 'force',
        ];
        $client->setAuthConfig($config);
        $client->addScope('email');
        $client->addScope('profile');
        $redirect_uri = 'https://simple.webhows.com/main/google_login';
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

                redirect('main');
            } else {
                redirect('main/login');
            }
        } else {
            $auth_url = $client->createAuthUrl();
            redirect($auth_url);
        }
    }




    public function login() {
        $this->load->view('login');
    }



    public function get_member_attendance() {
        if ($this->input->is_ajax_request()) {
            $member_idx = $this->input->post('member_idx');
            $start_date = $this->input->post('start_date');
            $end_date = $this->input->post('end_date');

            $this->load->model('Attendance_model');
            $attendance_data = $this->Attendance_model->get_member_attendance($member_idx, $start_date, $end_date);

            $response = array(
                'attendance_data' => $attendance_data
            );

            echo json_encode($response);
        }
    }



    public function add_group() {
        if ($this->input->is_ajax_request()) {
            $group_name = $this->input->post('group_name');
            $user_id = $this->session->userdata('email');

            $this->load->model('Group_model');
            $group_id = $this->Group_model->create_group($group_name);

            if ($group_id) {
                $this->Group_model->add_user_to_group($user_id, $group_id);
                $response = array('status' => 'success');
            } else {
                $response = array('status' => 'error');
            }

            echo json_encode($response);
        }
    }

    public function update_group() {
        if ($this->input->is_ajax_request()) {
            $group_id = $this->input->post('group_id');
            $group_name = $this->input->post('group_name');
            $attendance_types = json_decode($this->input->post('attendance_types'), true);

            $this->load->model('Group_model');
            $result = $this->Group_model->update_group($group_id, $group_name);

            if ($result) {
                // 출석 종류 업데이트 로직 추가
                $this->load->model('Attendance_model');
                $this->Attendance_model->update_attendance_types($group_id, $attendance_types);

                $response = array('status' => 'success');
            } else {
                $response = array('status' => 'error');
            }

            echo json_encode($response);
        }
    }


    public function update_del_yn() {
        if ($this->input->is_ajax_request()) {
            $group_id = $this->input->post('group_id');


            $this->load->model('Group_model');
            $result = $this->Group_model->update_del_yn($group_id);

            if ($result) {
                $response = array('status' => 'success');
            } else {
                $response = array('status' => 'error');
            }

            echo json_encode($response);
        } else {
            // AJAX 요청이 아닌 경우 에러 처리
            $response = array('status' => 'error', 'message' => 'Invalid request');
            echo json_encode($response);
        }
    }

    public function get_groups() {
        if ($this->input->is_ajax_request()) {
            $user_id = $this->session->userdata('email');

            $this->load->model('Group_model');
            $groups = $this->Group_model->get_user_groups($user_id);

            $group_data = array();
            foreach ($groups as $group) {
                $group_data[] = array(
                    'group_id' => $group['group_id'],
                    'group_name' => $group['group_name']
                );
            }

            echo json_encode($group_data);
        }
    }







    public function logout() {
        // 세션 데이터 삭제
        $this->session->unset_userdata('name');
        $this->session->unset_userdata('email');
        $this->session->unset_userdata('picture');
        $this->session->unset_userdata('refresh_token');
        $this->session->unset_userdata('access_token');

        // 세션 파괴
        $this->session->sess_destroy();

        // 구글 로그아웃 처리
        require_once APPPATH . '../vendor/autoload.php';
        $client = new Google_Client();
        $config = [
            'client_id' => '665369034498-bf7jrt09lasbit0f5cb4ppne8rs67nt8.apps.googleusercontent.com',
            'client_secret' => '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',
            'redirect_uris' => ['https://simple.webhows.com/main/google_login'],
        ];
        $client->setAuthConfig($config);
        $client->revokeToken($this->session->userdata('access_token'));

        // 로그인 페이지로 리디렉션
        redirect('main/login');
    }



    public function edit_group($group_id) {
        $this->load->model('Attendance_model');
        $data['attendance_types'] = $this->Attendance_model->get_attendance_types_by_group($group_id);

        $this->load->view('edit_group_modal', $data);
    }


    public function get_attendance_types_by_group() {
        if ($this->input->is_ajax_request()) {
            $group_id = $this->input->post('group_id');
            $this->load->model('Attendance_model');
            $attendance_types = $this->Attendance_model->get_attendance_types_by_group($group_id);
            $response = array(
                'attendance_types' => $attendance_types
            );

//            print_r($response);
//            exit;



            echo json_encode($response);
        }
    }

}