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
            $this->load->model('User_model');
            $data['user'] = $this->User_model->get_user_by_id($user_id);

            $this->load->model('Group_model');
            $groups = $this->Group_model->get_user_groups($user_id);

            // 각 그룹별 att_type_idx 개수 가져오기
//            $this->load->model('Attendance_model');
            foreach ($groups as &$group) {
//                $group['att_count'] = $this->Attendance_model->get_attendance_type_count($group['group_id']);
                $group['user_count'] = $this->User_model->get_group_user_count($group['group_id']);
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
            $user_grade = $this->input->post('user_grade');


            $this->load->model('User_model');
            $result = $this->User_model->save_user($user_id, $user_name, $user_hp, $user_grade);

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
                        'group_id' => $group_id
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
                        'group_id' => $group_id
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



}
