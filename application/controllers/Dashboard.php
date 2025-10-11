<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Dashboard extends My_Controller
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

		// POST로 조직 변경 요청 처리
		$this->handle_org_change($data);

		$currentOrgId = $data['current_org']['org_id'];

		// 선택된 조직의 상세 정보 가져오기
		$this->load->model('Org_model');
		$data['selected_org_detail'] = $this->Org_model->get_org_detail_by_id($currentOrgId);

		// 선택된 조직의 상세필드 목록 가져오기
		$this->load->model('Detail_field_model');
		$data['detail_fields'] = $this->Detail_field_model->get_detail_fields_by_org($currentOrgId);

		// 현재 조직 정보를 JavaScript로 전달하기 위해 orgs 배열에 추가
		$data['orgs'] = array($data['current_org']);

		// 회원현황 통계 데이터 조회
		$this->load->model('Member_model');
		$data['weekly_new_members'] = $this->Member_model->get_weekly_new_members($currentOrgId);

		// 출석현황 통계 데이터 조회
		$this->load->model('Attendance_model');
		$attendance_stats = $this->Attendance_model->get_weekly_attendance_stats_by_type($currentOrgId);
		$data['attendance_stats'] = $attendance_stats;

		// 타임라인현황 통계 데이터 조회
		$this->load->model('Timeline_model');
		try {
			$timeline_stats = $this->Timeline_model->get_weekly_timeline_stats_by_type($currentOrgId);
			$data['timeline_stats'] = $timeline_stats;

			// 디버깅용 로그
			log_message('debug', 'Timeline stats loaded: ' . json_encode($timeline_stats));
		} catch (Exception $e) {
			log_message('error', 'Timeline stats error: ' . $e->getMessage());
			$data['timeline_stats'] = array('weekly_data' => array(), 'timeline_types' => array());
		}

		// 메모현황 통계 데이터 조회
		$this->load->model('Memo_model');
		try {
			$memo_stats = $this->Memo_model->get_weekly_memo_stats_by_type($currentOrgId);
			$data['memo_stats'] = $memo_stats;

			// 디버깅용 로그
			log_message('debug', 'Memo stats loaded: ' . json_encode($memo_stats));
		} catch (Exception $e) {
			log_message('error', 'Memo stats error: ' . $e->getMessage());
			$data['memo_stats'] = array('weekly_data' => array(), 'memo_types' => array());
		}

		$this->load->view('dashboard', $data);
	}

}
