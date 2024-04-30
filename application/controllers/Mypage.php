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


}
