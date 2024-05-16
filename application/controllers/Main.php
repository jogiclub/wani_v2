<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Main extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->library('session');
        $this->load->helper('url');
    }



    public function index() {
        // 사용자가 로그인되어 있는지 확인
        if ($this->session->userdata('user_id')) {
            // 로그인된 사용자의 정보 가져오기
            $data['user'] = $this->session->userdata();
            $user_id = $this->session->userdata('user_id');

            // User_model을 로드하고 사용자 정보 가져오기
            $this->load->model('User_model');
            $data['user'] = $this->User_model->get_user_by_id($user_id);

            // 사용자가 개설한 그룹이 있는지 확인
            $userGroups = $this->Group_model->get_user_groups($user_id);
            if (empty($userGroups)) {
                // 개설한 그룹이 없으면 mypage로 리다이렉트
                redirect('mypage');
            }

            // Group_model 로드
            $this->load->model('Group_model');

            // POST로 전달된 group_id 가져오기
            $postGroupId = $this->input->post('group_id');

            // 쿠키에 저장된 activeGroup 가져오기
            $activeGroupId = $this->input->cookie('activeGroup');

            if ($postGroupId) {
                // POST로 전달된 group_id가 있는 경우
                $postGroup = $this->Group_model->get_group_by_id($postGroupId);
                $data['group_name'] = $postGroup['group_name'];
                $data['postGroup'] = $postGroup;

                // 쿠키에 activeGroup 설정 (만료 시간: 1일)
                $this->input->set_cookie('activeGroup', $postGroupId, 86400);
                $currentGroupId = $postGroupId;
            } else if ($activeGroupId) {
                // 쿠키에 저장된 activeGroup이 있는 경우
                $activeGroup = $this->Group_model->get_group_by_id($activeGroupId);
                $data['group_name'] = $activeGroup['group_name'];
                $currentGroupId = $activeGroupId;
            } else {
                // POST로 전달된 group_id와 쿠키에 저장된 activeGroup이 없는 경우
                $min_group_id = $this->Group_model->get_min_group_id($user_id);
                if ($min_group_id) {
                    $activeGroup = $this->Group_model->get_group_by_id($min_group_id);
                    $data['group_name'] = $activeGroup['group_name'];
                    $currentGroupId = $min_group_id;

                    // 쿠키에 activeGroup 설정 (만료 시간: 1일)
                    $this->input->set_cookie('activeGroup', $min_group_id, 86400);
                } else {
                    $data['group_name'] = ''; // 그룹이 없는 경우 기본값 설정
                }
            }

            // 활성화된 그룹의 출석타입 정보 가져오기
            $this->load->model('Attendance_model');
            $data['attendance_types'] = $this->Attendance_model->get_attendance_types($currentGroupId);

            // 선택된 모드 설정 (기본값: mode-1)
            $data['mode'] = $this->input->post('mode') ?? 'mode-1';

            // 사용자의 그룹 레벨 가져오기
            $user_group = $this->User_model->get_group_user($user_id, $currentGroupId);
            $user_level = $user_group ? $user_group['level'] : 0;
            $data['user_level'] = $user_level;

            // main 뷰 로드
            $this->load->view('main', $data);
        } else {
            // 로그인되어 있지 않은 경우 로그인 페이지로 리다이렉트
            redirect('login/index');
        }
    }





    public function save_single_attendance() {
        if ($this->input->is_ajax_request()) {
            $member_idx = $this->input->post('member_idx');
            $att_type_idx = $this->input->post('att_type_idx');
            $att_type_category_idx = $this->input->post('att_type_category_idx');
            $group_id = $this->input->post('group_id');
            $att_date = $this->input->post('att_date');

            // 기존 출석 정보 삭제
            $this->load->model('Attendance_model');
            $this->Attendance_model->delete_attendance_by_category($member_idx, $att_type_category_idx, $att_date);

            // 새로운 출석 정보 저장
            $data = array(
                'att_date' => $att_date,
                'att_type_idx' => $att_type_idx,
                'member_idx' => $member_idx,
                'group_id' => $group_id
            );
            $result = $this->Attendance_model->save_single_attendance($data);

            if ($result) {
                $response = array('status' => 'success');
            } else {
                $response = array('status' => 'error');
            }

            echo json_encode($response);
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

    public function get_member_info() {
        if ($this->input->is_ajax_request()) {
            $member_idx = $this->input->post('member_idx');

            $this->load->model('Member_model');
            $member_info = $this->Member_model->get_member_by_idx($member_idx);

            echo json_encode($member_info);
        }
    }


    public function get_active_members() {
        if ($this->input->is_ajax_request()) {
            $group_id = $this->input->post('group_id');
            $five_weeks_ago = date('Y-m-d', strtotime('-5 weeks'));

            $this->load->model('Member_model');
            $active_members = $this->Member_model->get_active_members($group_id, $five_weeks_ago);

            echo json_encode($active_members);
        }
    }

    public function save_member_info() {
        if ($this->input->is_ajax_request()) {
            $member_idx = $this->input->post('member_idx');
            $grade = $this->input->post('grade');
            $area = $this->input->post('area');
            $member_name = $this->input->post('member_name');
            $member_nick = $this->input->post('member_nick');
            $member_phone = $this->input->post('member_phone');
            $member_birth = $this->input->post('member_birth');
            $school = $this->input->post('school');
            $address = $this->input->post('address');
            $member_etc = $this->input->post('member_etc');
            $leader_yn = $this->input->post('leader_yn') ? 'Y' : 'N';
            $new_yn = $this->input->post('new_yn') ? 'Y' : 'N';

            $data = array(
                'grade' => $grade,
                'area' => $area,
                'member_name' => $member_name,
                'member_nick' => $member_nick,
                'member_phone' => $member_phone,
                'member_birth' => $member_birth,
                'school' => $school,
                'address' => $address,
                'member_etc' => $member_etc,
                'leader_yn' => $leader_yn,
                'new_yn' => $new_yn
            );

            // 사진 업로드 처리
            if (!empty($_FILES['photo']['name'])) {
                $group_id = $this->input->post('group_id');
                $upload_path = './uploads/member_photos/' . $group_id . '/';
                $config['upload_path'] = $upload_path;
                $config['allowed_types'] = 'gif|jpg|png';
                $config['max_size'] = 5120; // 최대 업로드 크기를 5MB로 변경
                $config['file_name'] = 'member_' . $member_idx;
                $config['overwrite'] = true; // 같은 이름의 이미지 덮어쓰기 옵션 추가

                // 업로드 경로 확인 및 없으면 생성
                if (!is_dir($config['upload_path'])) {
                    mkdir($config['upload_path'], 0777, true);
                }

                $this->load->library('upload', $config);

                if ($this->upload->do_upload('photo')) {
                    $upload_data = $this->upload->data();

                    // 이미지 크기 조정
                    $this->load->library('image_lib');
                    $image_config['image_library'] = 'gd2';
                    $image_config['source_image'] = $upload_data['full_path'];
                    $image_config['maintain_ratio'] = TRUE;
                    $image_config['width'] = 200;

                    $this->image_lib->clear();
                    $this->image_lib->initialize($image_config);

                    if (!$this->image_lib->resize()) {
                        // 이미지 리사이즈 실패 시 오류 메시지 전달
                        $response = array('status' => 'error', 'message' => $this->image_lib->display_errors());
                        echo json_encode($response);
                        return;
                    }

                    $data['photo'] = $upload_data['file_name'];

                    // photo_url을 response에 포함
                    $photo_url = base_url('uploads/member_photos/' . $group_id . '/' . $upload_data['file_name']);
                    $response = array('status' => 'success', 'photo_url' => $photo_url);

                } else {
                    $upload_error = $this->upload->display_errors();
                    log_message('error', 'Image upload failed: ' . $upload_error);
                    $response = array('status' => 'error', 'message' => $upload_error);
                    echo json_encode($response);
                    return;
                }
            }

            $this->load->model('Member_model');
            $result = $this->Member_model->update_member($member_idx, $data);

            if ($result) {
                if (!isset($response)) {
                    $response = array('status' => 'success');
                }
            } else {
                $response = array('status' => 'error');
            }

            echo json_encode($response);
        }
    }
    public function save_memo() {
        if ($this->input->is_ajax_request()) {
            $member_idx = $this->input->post('member_idx');
            $memo_type = $this->input->post('memo_type');
            $memo_content = $this->input->post('memo_content');

            $data = array(
                'memo_type' => $memo_type,
                'memo_content' => $memo_content,
                'regi_date' => date('Y-m-d H:i:s'),
                'user_id' => $this->session->userdata('user_email'),
                'member_idx' => $member_idx
            );

            $this->load->model('Memo_model');
            $result = $this->Memo_model->save_memo($data);

            if ($result) {
                $response = array('status' => 'success');
            } else {
                $response = array('status' => 'error');
            }

            echo json_encode($response);
        }
    }
    public function get_memo_list() {
        if ($this->input->is_ajax_request()) {
            $member_idx = $this->input->post('member_idx');
            $page = $this->input->post('page');
            $limit = $this->input->post('limit');

            $offset = ($page - 1) * $limit;

            $this->load->model('Memo_model');
            $memo_list = $this->Memo_model->get_memo_list($member_idx, $limit, $offset);

            if ($memo_list) {
                $response = array('status' => 'success', 'data' => $memo_list);
            } else {
                $response = array('status' => 'error');
            }

            echo json_encode($response);
        }
    }


    public function delete_memo() {
        if ($this->input->is_ajax_request()) {
            $idx = $this->input->post('idx');

            $this->load->model('Memo_model');
            $result = $this->Memo_model->delete_memo($idx);

            if ($result) {
                $response = array('status' => 'success');
            } else {
                $response = array('status' => 'error');
            }

            echo json_encode($response);
        }
    }


    public function get_memo_counts() {
        if ($this->input->is_ajax_request()) {
            $group_id = $this->input->post('group_id');
            $start_date = $this->input->post('start_date');
            $end_date = $this->input->post('end_date');

            $this->load->model('Memo_model');
            $memo_counts = $this->Memo_model->get_memo_counts($group_id, $start_date, $end_date);

            echo json_encode($memo_counts);
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
            $level = $this->input->post('level');

//            print_r($level);
//            exit;
//            $level = 2;
            $start_date = $this->input->post('start_date');
            $end_date = $this->input->post('end_date');

            $this->load->model('Member_model');

            $members = $this->Member_model->get_group_members($group_id, $level, $start_date, $end_date);
            echo json_encode($members);
        }
    }

    public function delete_member() {
        if ($this->input->is_ajax_request()) {
            $member_idx = $this->input->post('member_idx');

            $data = array(
                'del_yn' => 'Y',
                'del_date' => date('Y-m-d H:i:s')
            );

            $this->load->model('Member_model');
            $result = $this->Member_model->update_member($member_idx, $data);

            if ($result) {
                $response = array('status' => 'success');
            } else {
                $response = array('status' => 'error');
            }

            echo json_encode($response);
        }
    }

    public function update_multiple_members() {
        if ($this->input->is_ajax_request()) {
            $member_idx = $this->input->post('memberIdx');
            $grade = $this->input->post('grade');
            $area = $this->input->post('area');
            $all_grade_check = $this->input->post('allGradeCheck') === 'true';
            $all_area_check = $this->input->post('allAreaCheck') === 'true';

            $data = array(
                'modi_date' => date('Y-m-d H:i:s')
            );

            if ($all_grade_check) {
                $data['grade'] = $grade;
            }

            if ($all_area_check) {
                $data['area'] = $area;
            }

            $this->load->model('Member_model');
            $result = $this->Member_model->update_multiple_members($member_idx, $data, $all_grade_check, $all_area_check);

            if ($result) {
                $response = array('status' => 'success');
            } else {
                $response = array('status' => 'error');
            }

            echo json_encode($response);
        }
    }



    function getWeekRange($date, $includeWeek = false) {
        // 입력된 날짜의 타임스탬프 가져오기
        $timestamp = strtotime($date);
        // 현재 주의 일요일 타임스탬프 구하기
        $sundayTimestamp = strtotime('sunday this week', $timestamp);
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
//            print_r($attendance_types);
//            exit;
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
            $start_date = $this->input->post('start_date');
            $end_date = $this->input->post('end_date');

            $this->load->model('Attendance_model');

            // 해당 날짜의 모든 출석 정보 삭제
            $this->Attendance_model->delete_attendance_by_date_range($member_idx, $start_date, $end_date);

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
            'redirect_uris' => ['https://wani.im/login']
        ];
        $client->setAuthConfig($config);
        $client->revokeToken($this->session->userdata('access_token'));

        // 로그인 페이지로 리디렉션
        redirect('login');
    }



    public function get_same_members() {
        if ($this->input->is_ajax_request()) {
            $member_idx = $this->input->post('member_idx');
            $group_id = $this->input->post('group_id');
            $grade = $this->input->post('grade');
            $start_date = $this->input->post('start_date');
            $end_date = $this->input->post('end_date');

            $this->load->model('Member_model');
            $same_members = $this->Member_model->get_same_members($member_idx, $group_id, $grade, $start_date, $end_date);

            // 출석 유형 정보 가져오기
            $this->load->model('Attendance_model');
            $att_types = $this->Attendance_model->get_attendance_types($group_id);

            if ($same_members) {
                $response = array('status' => 'success', 'members' => $same_members, 'att_types' => $att_types);
            } else {
                $response = array('status' => 'error');
            }

            echo json_encode($response);
        }
    }


    public function save_attendance_data() {
        if ($this->input->is_ajax_request()) {
            $attendance_data = json_decode($this->input->post('attendance_data'), true);
            $group_id = $this->input->post('group_id');
            $start_date = $this->input->post('start_date');
            $end_date = $this->input->post('end_date');

            // 멤버별 출석 정보 그룹화
            $member_attendance_data = array();
            foreach ($attendance_data as $data) {
                $member_idx = $data['member_idx'];
                $att_type_idx = $data['att_type_idx'];

                if (!isset($member_attendance_data[$member_idx])) {
                    $member_attendance_data[$member_idx] = array();
                }
                $member_attendance_data[$member_idx][] = $att_type_idx;
            }

            $this->load->model('Attendance_model');

            // 각 멤버의 출석 정보 저장
            foreach ($member_attendance_data as $member_idx => $att_type_idxs) {
                // 기존 출석 정보 삭제
                $this->Attendance_model->delete_attendance_by_date_range($member_idx, $start_date, $end_date);

                // 새로운 출석 정보가 있는 경우에만 저장
                if (!empty($att_type_idxs)) {
                    $att_data = array();
                    foreach ($att_type_idxs as $att_type_idx) {
                        $att_data[] = array(
                            'att_date' => $start_date,
                            'att_type_idx' => $att_type_idx,
                            'member_idx' => $member_idx,
                            'group_id' => $group_id
                        );
                    }
                    $this->Attendance_model->save_attendance_data($att_data, $group_id, $start_date, $end_date);
                }
            }

            $response = array('status' => 'success');
            echo json_encode($response);
        }
    }

// Main.php
    public function get_last_week_attendance() {
        if ($this->input->is_ajax_request()) {
            $group_id = $this->input->post('group_id');
            $grade = $this->input->post('grade');
            $start_date = $this->input->post('start_date');
            $end_date = $this->input->post('end_date');

            $this->load->model('Attendance_model');
            $attendance_data = $this->Attendance_model->get_group_member_attendance($group_id, $grade, $start_date, $end_date);
            $att_types = $this->Attendance_model->get_attendance_types($group_id);

            $response = array(
                'status' => 'success',
                'attendance_data' => $attendance_data,
                'att_types' => $att_types
            );

            echo json_encode($response);
        }
    }

}