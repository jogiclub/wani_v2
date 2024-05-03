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
        if ($this->session->userdata('email')) {
            $data['user'] = $this->session->userdata();

            $user_id = $this->session->userdata('email');
            $this->load->model('Group_model');
            $data['groups'] = $this->Group_model->get_user_groups($user_id);

            $this->load->view('mypage', $data);
        } else {
            redirect('main/login');
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


}
