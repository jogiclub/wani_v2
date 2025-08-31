<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Dashboard extends CI_Controller
{

	public function __construct()
	{
		parent::__construct();
		$this->load->library('session');
		$this->load->helper('url');
	}

	public function index(){
		$user_id = $this->session->userdata('user_id');
		if (!$user_id) {
			redirect('login');
			return;
		}
		// 헤더 데이터 준비
		$header_data = $this->prepare_header_data();
		if (empty($header_data['user_orgs'])) {
			redirect('dashboard');
			return;
		}

		$data = $header_data;

		// POST로 조직 변경 요청 처리 (My_Controller의 메소드 사용)
		$this->handle_org_change($data);

		$currentOrgId = $data['current_org']['org_id'];

		// 선택된 조직의 상세 정보 가져오기 - 뷰에서 필요한 변수 추가
		$this->load->model('Org_model');
		$data['selected_org_detail'] = $this->Org_model->get_org_detail_by_id($currentOrgId);

		// 선택된 조직의 상세필드 목록 가져오기 - 메서드명 수정
		$data['detail_fields'] = $this->Detail_field_model->get_detail_fields_by_org($currentOrgId);

		// 현재 조직 정보를 JavaScript로 전달하기 위해 orgs 배열에 추가
		$data['orgs'] = array($data['current_org']);

		$this->load->view('dashboard');;
	}

}
