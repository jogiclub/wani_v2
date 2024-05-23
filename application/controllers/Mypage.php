<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Mypage extends CI_Controller
{

    public function __construct(){
        parent::__construct();
        $this->load->library('session');
        $this->load->helper('url');
    }


    public function index() {



        if ($this->session->userdata('user_id')) {
            $data['user'] = $this->session->userdata();
            $user_id = $this->session->userdata('user_id');
            $master_yn = $this->session->userdata('master_yn');
            $this->load->model('User_model');
            $data['user'] = $this->User_model->get_user_by_id($user_id);

            $this->load->model('Group_model');

            if($master_yn === "N"){
                $groups = $this->Group_model->get_user_groups($user_id);
            } else {
                $groups = $this->Group_model->get_user_groups_master($user_id);
            }


            foreach ($groups as &$group) {
                $group['user_count'] = $this->User_model->get_group_user_count($group['group_id']);

                // 그룹에 대한 사용자의 level 값과 master_yn 값을 가져옴
                $group['user_level'] = $this->User_model->get_group_user_level($user_id, $group['group_id']);
                $group['user_master_yn'] = $this->session->userdata('master_yn');
            }

            $data['groups'] = $groups;

            $this->load->view('mypage', $data);
        } else {
            redirect('login');
        }
    }






    public function add_group() {
        if ($this->input->is_ajax_request()) {
            $group_name = $this->input->post('group_name');
            $user_id = $this->session->userdata('user_id');

//            print_r($this->session->userdata('user_id'));
//            exit;

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





    public function update_group() {
        if ($this->input->is_ajax_request()) {
            $group_id = $this->input->post('group_id');
            $group_name = $this->input->post('group_name');
            $leader_name = $this->input->post('leader_name');
            $new_name = $this->input->post('new_name');

            $this->load->model('Group_model');
            $result = $this->Group_model->update_group($group_id, $group_name, $leader_name, $new_name);

            if ($result) {
                $response = array('status' => 'success');
            } else {
                $response = array('status' => 'error');
            }

            echo json_encode($response);
        }
    }





    public function edit_group($group_id) {

        $this->load->view('edit_group_modal', $data);
    }


    public function get_attendance_types() {
        if ($this->input->is_ajax_request()) {
            $group_id = $this->input->post('group_id');

            $this->load->model('Attendance_model');
            $attendance_types = $this->Attendance_model->get_attendance_types($group_id);

            echo json_encode($attendance_types);
        }
    }

    public function save_attendance_type() {
        if ($this->input->is_ajax_request()) {
            $group_id = $this->input->post('group_id');
            $att_type_category_name = $this->input->post('att_type_category_name');
            $att_type_name = $this->input->post('att_type_name');
            $att_type_nickname = $this->input->post('att_type_nickname');
            $att_type_color = $this->input->post('att_type_color');

            // 출석 타입 카테고리 인덱스 관리
            $att_type_category_idx = $this->Attendance_model->get_max_category_idx($group_id) + 1;

            $this->load->model('Attendance_model');
            $result = $this->Attendance_model->save_attendance_type($group_id, $att_type_category_name, $att_type_name, $att_type_nickname, $att_type_color, $att_type_category_idx);

            if ($result) {
                $response = array('status' => 'success');
            } else {
                $response = array('status' => 'error');
            }

            echo json_encode($response);
        }
    }

    public function update_attendance_type() {
        if ($this->input->is_ajax_request()) {
            $att_type_idx = $this->input->post('att_type_idx');
            $att_type_name = $this->input->post('att_type_name');
            $att_type_nickname = $this->input->post('att_type_nickname');
            $att_type_color = $this->input->post('att_type_color');

            $this->load->model('Attendance_model');
            $result = $this->Attendance_model->update_attendance_type($att_type_idx, $att_type_name, $att_type_nickname, $att_type_color);

            if ($result) {
                $response = array('status' => 'success');
            } else {
                $response = array('status' => 'error');
            }

            echo json_encode($response);
        }
    }



    public function add_attendance_type_category() {
        if ($this->input->is_ajax_request()) {
            $att_type_category_name = $this->input->post('att_type_category_name');
            $group_id = $this->input->post('group_id');

            $this->load->model('Attendance_model');

            // 해당 그룹에서 사용하는 가장 큰 att_type_category_idx 값을 가져옴
            $max_category_idx = $this->Attendance_model->get_max_category_idx($group_id);
            $att_type_category_idx = $max_category_idx + 1;

            $data = array(
                'att_type_category_name' => $att_type_category_name,
                'att_type_category_idx' => $att_type_category_idx,
                'att_type_name' => '새출석타입',
                'att_type_nickname' => '출',
                'group_id' => $group_id
            );




            $result = $this->Attendance_model->add_attendance_type_category($data);

            if ($result) {
                $response = array('status' => 'success');
            } else {
                $response = array('status' => 'error');
            }

            echo json_encode($response);
        }
    }

    public function add_attendance_type() {
        if ($this->input->is_ajax_request()) {
            $att_type_name = $this->input->post('att_type_name');
            $att_type_category_idx = $this->input->post('att_type_category_idx');
            $att_type_category_name = $this->input->post('att_type_category_name');
            $att_type_nickname = $this->input->post('att_type_nickname');
            $group_id = $this->input->post('group_id');

            $data = array(
                'att_type_name' => $att_type_name,
                'att_type_category_idx' => $att_type_category_idx,
                'att_type_category_name' => $att_type_category_name,
                'att_type_nickname' => $att_type_nickname,
                'group_id' => $group_id
            );

            $this->load->model('Attendance_model');
            $result = $this->Attendance_model->add_attendance_type($data);

            if ($result) {
                // add_attendance_type() 함수 실행 후 reordering() 함수 호출
                $this->reordering($group_id);
                $response = array('status' => 'success');
            } else {
                $response = array('status' => 'error');
            }

            echo json_encode($response);
        }
    }


    public function attendance_type_setting() {
        if ($this->input->is_ajax_request()) {
            $group_id = $this->input->post('group_id');

            $this->load->model('Attendance_model');
            $attendance_type_categories = $this->Attendance_model->get_attendance_type_categories($group_id);

            $response = array(
                'status' => 'success',
                'attendance_type_categories' => $attendance_type_categories,
                'group_id' => $group_id
            );

            echo json_encode($response);
        }
    }


    public function reordering($group_id) {



            // wb_att_type 테이블에서 해당 group_id의 데이터를 att_type_category_idx와 att_type_idx 순서로 정렬
            $this->db->select('att_type_idx');
            $this->db->from('wb_att_type');
            $this->db->where('group_id', $group_id);
            $this->db->order_by('att_type_category_idx', 'ASC');
            $this->db->order_by('att_type_idx', 'ASC');
            $query = $this->db->get();
            $result = $query->result_array();

            // att_type_order 값을 1부터 순차적으로 증가시키며 업데이트
            $order = 1;
            foreach ($result as $row) {
                $att_type_idx = $row['att_type_idx'];
                $data = array(
                    'att_type_order' => $order
                );
                $this->db->where('att_type_idx', $att_type_idx);
                $this->db->update('wb_att_type', $data);
                $order++;
            }



    }


    public function delete_attendance_type() {
        if ($this->input->is_ajax_request()) {
            $att_type_idx = $this->input->post('att_type_idx');

            $this->load->model('Attendance_model');
            $result = $this->Attendance_model->delete_attendance_type($att_type_idx);

            if ($result) {
                $response = array('status' => 'success');
            } else {
                $response = array('status' => 'error');
            }

            echo json_encode($response);
        }
    }
    public function get_attendance_type_count($group_id) {
        $this->load->model('Attendance_model');
        $count = $this->Attendance_model->get_attendance_type_count($group_id);
        return $count;
    }


    public function get_group_users() {
        if ($this->input->is_ajax_request()) {
            $group_id = $this->input->post('group_id');

            $this->load->model('User_model');
            $users = $this->User_model->get_group_users($group_id);

            echo json_encode($users);
        }
    }


    public function excel_upload()
    {
        if ($this->input->is_ajax_request()) {
            try {
                $group_id = $this->input->post('group_id');
                $file = $_FILES['excel_file']['tmp_name'];

                // 엑셀 파일 읽기
                $this->load->library('excel');
                $objPHPExcel = PHPExcel_IOFactory::load($file);
                $sheet = $objPHPExcel->getActiveSheet();
                $highestRow = $sheet->getHighestRow();

                for ($row = 2; $row <= $highestRow; $row++) {
                    $data = array(
                        'group_id' => $group_id,
                        'grade' => $sheet->getCell('A' . $row)->getValue(),
                        'area' => $sheet->getCell('B' . $row)->getValue(),
                        'member_name' => $sheet->getCell('C' . $row)->getValue(),
                        'member_nick' => $sheet->getCell('D' . $row)->getValue(),
                        'member_phone' => $sheet->getCell('E' . $row)->getValue(),
                        'member_birth' => $sheet->getCell('F' . $row)->getValue(),
                        'school' => $sheet->getCell('G' . $row)->getValue(),
                        'address' => $sheet->getCell('H' . $row)->getValue(),
                        'member_etc' => $sheet->getCell('I' . $row)->getValue(),
                        'leader_yn' => $sheet->getCell('J' . $row)->getValue(),
                        'new_yn' => $sheet->getCell('K' . $row)->getValue(),
                        'regi_date' => date('Y-m-d H:m:s'),
                    );

                    // 빈 값 제거
                    $data = array_filter($data, function($value) {
                        return $value !== null && $value !== '';
                    });

                    // 데이터베이스에 삽입
                    $this->load->model('Member_model');
                    $result = $this->Member_model->add_member($data);

                    if (!$result) {
                        throw new Exception('Failed to insert member data.');
                    }
                }

                $response = array('status' => 'success');
                echo json_encode($response);
            } catch (Exception $e) {
                $response = array('status' => 'error', 'message' => $e->getMessage());
                echo json_encode($response);
            }
        }
    }


    public function save_user() {
        if ($this->input->is_ajax_request()) {
            $user_id = $this->input->post('user_id');
            $user_name = $this->input->post('user_name');
            $user_hp = $this->input->post('user_hp');
            $level = $this->input->post('level');
            $group_id = $this->input->post('group_id');

            $this->load->model('User_model');
            $result = $this->User_model->save_user($user_id, $user_name, $user_hp, $level, $group_id);

            if ($result) {
                $response = array('status' => 'success');
            } else {
                $response = array('status' => 'error');
            }

            echo json_encode($response);
        }
    }


    public function delete_user() {
        if ($this->input->is_ajax_request()) {
            $user_id = $this->input->post('user_id');
            $group_id = $this->input->post('group_id');

            $this->load->model('User_model');
            $result = $this->User_model->delete_user($user_id, $group_id);

            if ($result) {
                $response = array('status' => 'success');
            } else {
                $response = array('status' => 'error');
            }

            echo json_encode($response);
        }
    }


    public function invite_user() {
        if ($this->input->is_ajax_request()) {
            $email = $this->input->post('email');
            $group_id = $this->input->post('group_id');

            $this->load->model('User_model');
            $existing_user = $this->User_model->get_user_by_email($email);

            if ($existing_user) {
                $user_id = $existing_user['user_id'];
                $existing_group_user = $this->User_model->get_group_user($user_id, $group_id);

                if ($existing_group_user) {
                    $response = array('status' => 'exists');
                } else {
                    $group_user_data = array(
                        'user_id' => $user_id,
                        'group_id' => $group_id,
                        'level' => 1
                    );
                    $this->User_model->insert_group_user($group_user_data);
                    $response = array('status' => 'success');
                }
            } else {
                $user_data = array(
                    'user_id' => $email,
                    'user_name' => '새사용자',
                    'user_grade' => 0,
                    'user_mail' => $email,
                    'user_hp' => '',
                    'regi_date' => date('Y-m-d H:i:s'),
                    'modi_date' => date('Y-m-d H:i:s'),
                    'del_yn' => 'N',
                    'del_date' => '0000-00-00 00:00:00'
                );
                $user_id = $this->User_model->insert_user($user_data);

                if ($user_id) {
                    $group_user_data = array(
                        'user_id' => $email,
                        'group_id' => $group_id,
                        'level' => 1
                    );
                    $this->User_model->insert_group_user($group_user_data);
                    $response = array('status' => 'success');
                } else {
                    $response = array('status' => 'error');
                }
            }

            if ($response['status'] === 'success') {
                $invite_code = $this->generate_invite_code();
                $subject = 'wani의 초대 메일입니다.';
                $message = $this->session->userdata('user_name') . "님께서 초대하셨습니다.\n";
                $message .= "아래의 링크를 클릭하면 페이지로 이동합니다.\n";
                $message .= "https://wani.im/login/invite/" . $invite_code;

                $this->load->library('email');
                $this->email->from('no-replay@wani.im', '왔니');
                $this->email->to($email);
                $this->email->subject($subject);
                $this->email->message($message);

                if ($this->email->send()) {
                    $invite_data = array(
                        'invite_mail' => $email,
                        'invite_code' => $invite_code,
                        'invite_date' => date('Y-m-d H:i:s'),
                        'del_yn' => 'N'
                    );
                    $this->load->model('Invite_model');
                    $this->Invite_model->insert_invite($invite_data);
                } else {
                    $response = array('status' => 'error');
                }
            }

            echo json_encode($response);
        }
    }

    public function login_as_user() {
        if ($this->input->is_ajax_request()) {
            $user_id = $this->input->post('user_id');

            $this->load->model('User_model');
            $user = $this->User_model->get_user_by_id($user_id);

            if ($user) {
                $this->session->set_userdata($user);
                $response = array('status' => 'success');
            } else {
                $response = array('status' => 'error');
            }

            echo json_encode($response);
        }
    }

    private function generate_invite_code() {
        return bin2hex(random_bytes(16));
    }




    public function get_group_members() {
        if ($this->input->is_ajax_request()) {
            $group_id = $this->input->post('group_id');

            $this->load->model('Member_model');
            $members = $this->Member_model->get_group_members($group_id);

            echo json_encode($members);
        }
    }

    public function print_qr() {
        $group_id = $this->input->get('group_id');
        $data['group_id'] = $group_id;
        $this->load->view('print_qr_view', $data);
    }

    public function summery_week() {
        $group_id = $this->input->get('group_id');

        // 시작일과 종료일 계산
        $start_date = date('Y-m-d', strtotime('first sunday of january this year'));
        $end_date = date('Y-m-d', strtotime('next saturday'));
        $current_week = date('W', strtotime($end_date));

        // 주차 정보 생성 (최근 주차부터 역순으로)
        $weeks = array();
        for ($week = $current_week; $week >= 1; $week--) {
            $week_start = date('Y-m-d', strtotime(date('Y') . 'W' . str_pad($week, 2, '0', STR_PAD_LEFT) . ' -1 days'));
            $week_end = date('Y-m-d', strtotime(date('Y') . 'W' . str_pad($week, 2, '0', STR_PAD_LEFT) . ' +5 days'));
            $weeks[] = array(
                'start_date' => $week_start,
                'end_date' => $week_end,
                'week_number' => $week
            );
        }

        // 해당 그룹의 출석 타입 목록 가져오기 (att_type_order 순서대로)
        $this->load->model('Attendance_model');
        $attendance_types = $this->Attendance_model->get_attendance_types($group_id);

        // 해당 그룹의 주차별 출석 타입 합산 데이터 가져오기
        $attendance_data = array();
        foreach ($weeks as $week) {
            $week_start = $week['start_date'];
            $week_end = $week['end_date'];
            $attendance_data[$week['week_number']] = $this->Attendance_model->get_week_attendance_sum($group_id, $week_start, $week_end);
        }

        $data['group_id'] = $group_id;
        $data['weeks'] = $weeks;
        $data['attendance_types'] = $attendance_types;
        $data['attendance_data'] = $attendance_data;

        $this->load->view('summery_week', $data);
    }



    public function summery_member() {
        $group_id = $this->input->get('group_id');
        $post_group_id = $this->input->post('group_id');
        $att_type_idx = $this->input->post('att_type_idx');


        // 시작일과 종료일 계산
        $start_date = date('Y-m-d', strtotime('first sunday of january this year'));
        $end_date = date('Y-m-d', strtotime('next saturday'));
        $current_week = date('W', strtotime($end_date));

        // 주차 정보 생성
        $weeks = array();
        for ($week = 1; $week <= $current_week; $week++) {
            $week_start = date('Y-m-d', strtotime(date('Y') . 'W' . str_pad($week, 2, '0', STR_PAD_LEFT) . ' +6 days'));
            $week_end = date('Y-m-d', strtotime(date('Y') . 'W' . str_pad($week, 2, '0', STR_PAD_LEFT) . ' +12 days'));
            $weeks[] = array(
                'start_date' => $week_start,
                'end_date' => $week_end,
                'week_number' => $week
            );
        }

        // 해당 그룹의 회원 목록 가져오기
        $this->load->model('Member_model');
        $members = $this->Member_model->get_group_members($group_id);

        // 해당 그룹의 출석 타입 목록 가져오기
        $this->load->model('Attendance_model');
        $attendance_types = $this->Attendance_model->get_attendance_types($group_id);
        $data['group_id'] = $group_id;
        $data['weeks'] = $weeks;
        $data['members'] = $members;
        $data['attendance_types'] = $attendance_types;

        if ($this->input->is_ajax_request()) {
            // 선택된 출석 타입에 대한 회원별 출석 데이터 가져오기
            $attendance_data = array();
            if ($att_type_idx) {
                $attendance_data = $this->Attendance_model->get_member_attendance_summery($post_group_id, $att_type_idx);
            }
            echo json_encode($attendance_data);
        } else {
            $this->load->view('summery_member', $data);
        }
    }




}
