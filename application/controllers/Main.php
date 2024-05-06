<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Main extends CI_Controller {

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
            $postGroupId = $this->input->post('group_id');
            $activeGroupId = $this->input->cookie('activeGroup');

            if ($postGroupId) {
                $postGroup = $this->Group_model->get_group_by_id($postGroupId);
                $data['group_name'] = $postGroup['group_name'];
                $data['postGroup'] = $postGroup; // postGroup 데이터를 뷰 파일에 전달
                $this->input->set_cookie('activeGroup', $postGroupId, 86400); // 쿠키 설정 (만료 시간: 1일)
                $currentGroupId = $postGroupId;
            } else if ($activeGroupId) {
                $activeGroup = $this->Group_model->get_group_by_id($activeGroupId);
                $data['group_name'] = $activeGroup['group_name'];
                $currentGroupId = $activeGroupId;
            } else {
                $min_group_id = $this->Group_model->get_min_group_id($user_id);
                if ($min_group_id) {
                    $activeGroup = $this->Group_model->get_group_by_id($min_group_id);
                    $data['group_name'] = $activeGroup['group_name'];
                    $currentGroupId = $min_group_id;
                    $this->input->set_cookie('activeGroup', $min_group_id, 86400); // 쿠키 설정 (만료 시간: 1일)
                } else {
                    $data['group_name'] = ''; // 그룹이 없는 경우 기본값 설정
                }
            }

            // 활성화된 그룹의 출석타입 정보 가져오기
            $this->load->model('Attendance_model');
            $data['attendance_types'] = $this->Attendance_model->get_attendance_types($currentGroupId);


            $data['mode'] = $this->input->post('mode') ?? 'mode-1';

            $this->load->view('main', $data);
        } else {
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
                'user_id' => $this->session->userdata('email'),
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











}