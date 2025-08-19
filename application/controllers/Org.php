<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Org extends CI_Controller
{

    public function __construct()
    {
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

			$this->load->view('org_setting', $data);
		} else {
			redirect('login');
		}
	}

}
