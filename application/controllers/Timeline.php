<?php
/**
 * 역할: 타임라인 관리 컨트롤러 - 회원 타임라인 이력 조회 및 관리
 */

defined('BASEPATH') or exit('No direct script access allowed');

class Timeline extends My_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->library('session');
		$this->load->helper('url');
		$this->load->model('Timeline_model');
		$this->load->model('Member_model');
		$this->load->model('Org_model');

		if (!$this->session->userdata('user_id')) {
			redirect('login');
		}
	}

	/**
	 * 타임라인 관리 메인 페이지
	 */
	public function index()
	{
		$user_id = $this->session->userdata('user_id');

		$header_data = $this->prepare_header_data();
		if (empty($header_data['user_orgs'])) {
			redirect('dashboard');
			return;
		}

		$data = $header_data;
		$this->handle_org_change($data);

		$currentOrgId = $data['current_org']['org_id'];

		if (!$this->check_org_access($currentOrgId)) {
			$this->handle_access_denied('해당 조직의 타임라인을 관리할 권한이 없습니다.');
			return;
		}

		$data['orgs'] = array($data['current_org']);

		$this->load->view('timeline', $data);
	}

	/**
	 * 타임라인 목록 조회 (PQGrid용)
	 */
	public function get_timelines()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$org_id = $this->input->post('org_id');
		$timeline_type = $this->input->post('timeline_type');
		$search_text = $this->input->post('search_text');
		$page = $this->input->post('pq_curpage') ? $this->input->post('pq_curpage') : 1;
		$rpp = $this->input->post('pq_rpp') ? $this->input->post('pq_rpp') : 20;

		if (!$org_id) {
			echo json_encode(array('success' => false, 'message' => '조직 ID가 필요합니다.'));
			return;
		}

		$offset = ($page - 1) * $rpp;

		$filters = array(
			'timeline_type' => $timeline_type,
			'search_text' => $search_text
		);

		$timelines = $this->Timeline_model->get_timelines($org_id, $filters, $rpp, $offset);
		$total_count = $this->Timeline_model->get_timelines_count($org_id, $filters);

		echo json_encode(array(
			'success' => true,
			'data' => $timelines,
			'totalRecords' => $total_count,
			'curPage' => $page
		));
	}

	/**
	 * 타임라인 항목 조회
	 */
	public function get_timeline_types()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$org_id = $this->input->post('org_id');

		if (!$org_id) {
			echo json_encode(array('success' => false, 'message' => '조직 ID가 필요합니다.'));
			return;
		}

		$timeline_types = $this->Org_model->get_timeline_types($org_id);

		echo json_encode(array(
			'success' => true,
			'data' => $timeline_types
		));
	}

	/**
	 * 회원 목록 조회 (Select2용)
	 */
	public function get_members_for_select()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$org_id = $this->input->get('org_id');
		$search = $this->input->get('search');

		if (!$org_id) {
			echo json_encode(array('success' => false, 'message' => '조직 ID가 필요합니다.'));
			return;
		}

		$members = $this->Member_model->get_members_for_select($org_id, $search);

		echo json_encode(array(
			'success' => true,
			'data' => $members
		));
	}

	/**
	 * 타임라인 추가
	 */
	public function add_timeline()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$org_id = $this->input->post('org_id');
		$member_idxs = $this->input->post('member_idxs');
		$timeline_type = $this->input->post('timeline_type');
		$timeline_date = $this->input->post('timeline_date');
		$timeline_content = $this->input->post('timeline_content');
		$user_id = $this->session->userdata('user_id');

		if (!$org_id || !$member_idxs || !$timeline_type || !$timeline_date) {
			echo json_encode(array('success' => false, 'message' => '필수 항목을 입력해주세요.'));
			return;
		}

		if (!is_array($member_idxs)) {
			$member_idxs = array($member_idxs);
		}

		$data = array(
			'timeline_type' => $timeline_type,
			'timeline_date' => $timeline_date,
			'timeline_content' => $timeline_content,
			'user_id' => $user_id
		);

		$result = $this->Timeline_model->add_timelines($member_idxs, $data);

		if ($result) {
			echo json_encode(array('success' => true, 'message' => '타임라인이 추가되었습니다.'));
		} else {
			echo json_encode(array('success' => false, 'message' => '타임라인 추가에 실패했습니다.'));
		}
	}

	/**
	 * 타임라인 수정
	 */
	public function update_timeline()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$idx = $this->input->post('idx');
		$timeline_type = $this->input->post('timeline_type');
		$timeline_date = $this->input->post('timeline_date');
		$timeline_content = $this->input->post('timeline_content');

		if (!$idx || !$timeline_type || !$timeline_date) {
			echo json_encode(array('success' => false, 'message' => '필수 항목을 입력해주세요.'));
			return;
		}

		$data = array(
			'timeline_type' => $timeline_type,
			'timeline_date' => $timeline_date,
			'timeline_content' => $timeline_content
		);

		$result = $this->Timeline_model->update_timeline($idx, $data);

		if ($result) {
			echo json_encode(array('success' => true, 'message' => '타임라인이 수정되었습니다.'));
		} else {
			echo json_encode(array('success' => false, 'message' => '타임라인 수정에 실패했습니다.'));
		}
	}

	/**
	 * 타임라인 삭제
	 */
	public function delete_timelines()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$idxs = $this->input->post('idxs');

		if (!$idxs || !is_array($idxs) || count($idxs) === 0) {
			echo json_encode(array('success' => false, 'message' => '삭제할 항목을 선택해주세요.'));
			return;
		}

		$result = $this->Timeline_model->delete_timelines($idxs);

		if ($result) {
			echo json_encode(array('success' => true, 'message' => count($idxs) . '개의 타임라인이 삭제되었습니다.'));
		} else {
			echo json_encode(array('success' => false, 'message' => '타임라인 삭제에 실패했습니다.'));
		}
	}

	/**
	 * 타임라인 상세 조회
	 */
	public function get_timeline_detail()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$idx = $this->input->post('idx');

		if (!$idx) {
			echo json_encode(array('success' => false, 'message' => 'IDX가 필요합니다.'));
			return;
		}

		$timeline = $this->Timeline_model->get_timeline_by_idx($idx);

		if ($timeline) {
			echo json_encode(array('success' => true, 'data' => $timeline));
		} else {
			echo json_encode(array('success' => false, 'message' => '타임라인을 찾을 수 없습니다.'));
		}
	}
}
