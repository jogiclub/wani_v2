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

	// 파일 위치: application/controllers/Dashboard.php
// 역할: 대시보드 메인과 통계 API 분리

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

		// 현재 조직 정보를 JavaScript로 전달
		$data['orgs'] = array($data['current_org']);

		// 통계 데이터는 AJAX로 가져오므로 제거
		$this->load->view('dashboard', $data);
	}

	/**
	 * 회원현황 통계 조회 (AJAX)
	 */
	public function get_member_stats()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$org_id = $this->input->post('org_id');

		if (!$org_id) {
			echo json_encode(array(
				'success' => false,
				'message' => '조직 ID가 필요합니다.'
			));
			return;
		}

		try {
			$this->load->model('Member_model');
			$weekly_new_members = $this->Member_model->get_weekly_new_members($org_id);

			echo json_encode(array(
				'success' => true,
				'data' => $weekly_new_members
			));
		} catch (Exception $e) {
			log_message('error', 'Member stats error: ' . $e->getMessage());
			echo json_encode(array(
				'success' => false,
				'message' => '회원현황 통계 조회 중 오류가 발생했습니다.'
			));
		}
	}

	/**
	 * 출석현황 통계 조회 (AJAX)
	 */
	public function get_attendance_stats()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$org_id = $this->input->post('org_id');

		if (!$org_id) {
			echo json_encode(array(
				'success' => false,
				'message' => '조직 ID가 필요합니다.'
			));
			return;
		}

		try {
			$this->load->model('Attendance_model');
			$attendance_stats = $this->Attendance_model->get_weekly_attendance_stats_by_type($org_id);

			echo json_encode(array(
				'success' => true,
				'data' => $attendance_stats
			));
		} catch (Exception $e) {
			log_message('error', 'Attendance stats error: ' . $e->getMessage());
			echo json_encode(array(
				'success' => false,
				'message' => '출석현황 통계 조회 중 오류가 발생했습니다.'
			));
		}
	}

	/**
	 * 타임라인현황 통계 조회 (AJAX)
	 */
	public function get_timeline_stats()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$org_id = $this->input->post('org_id');

		if (!$org_id) {
			echo json_encode(array(
				'success' => false,
				'message' => '조직 ID가 필요합니다.'
			));
			return;
		}

		try {
			$this->load->model('Timeline_model');
			$timeline_stats = $this->Timeline_model->get_weekly_timeline_stats_by_type($org_id);

			echo json_encode(array(
				'success' => true,
				'data' => $timeline_stats
			));
		} catch (Exception $e) {
			log_message('error', 'Timeline stats error: ' . $e->getMessage());
			echo json_encode(array(
				'success' => false,
				'message' => '타임라인현황 통계 조회 중 오류가 발생했습니다.'
			));
		}
	}

	/**
	 * 메모현황 통계 조회 (AJAX)
	 */
	public function get_memo_stats()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$org_id = $this->input->post('org_id');

		if (!$org_id) {
			echo json_encode(array(
				'success' => false,
				'message' => '조직 ID가 필요합니다.'
			));
			return;
		}

		try {
			$this->load->model('Memo_model');
			$memo_stats = $this->Memo_model->get_weekly_memo_stats_by_type($org_id);

			echo json_encode(array(
				'success' => true,
				'data' => $memo_stats
			));
		} catch (Exception $e) {
			log_message('error', 'Memo stats error: ' . $e->getMessage());
			echo json_encode(array(
				'success' => false,
				'message' => '메모현황 통계 조회 중 오류가 발생했습니다.'
			));
		}
	}

}
