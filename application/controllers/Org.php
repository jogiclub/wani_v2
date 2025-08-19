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

			$this->load->model('Org_model');

			if($master_yn === "N"){
				$orgs = $this->Org_model->get_user_orgs($user_id);
			} else {
				$orgs = $this->Org_model->get_user_orgs_master($user_id);
			}


			foreach ($orgs as &$org) {
				$org['user_count'] = $this->User_model->get_org_user_count($org['org_id']);

				// 그룹에 대한 사용자의 level 값과 master_yn 값을 가져옴
				$org['user_level'] = $this->User_model->get_org_user_level($user_id, $org['org_id']);
				$org['user_master_yn'] = $this->session->userdata('master_yn');
			}

			$data['orgs'] = $orgs;

			$this->load->view('org_setting', $data);
		} else {
			redirect('login');
		}
	}

}
