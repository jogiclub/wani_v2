<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Memos extends My_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->library('session');
		$this->load->helper('url');
		$this->load->model('Memo_model');
		$this->load->model('Member_model');
		$this->load->model('Org_model');

		if (!$this->session->userdata('user_id')) {
			redirect('login');
		}
	}

	/**
	 * 메모 관리 메인 페이지
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
			$this->handle_access_denied('해당 조직의 메모를 관리할 권한이 없습니다.');
			return;
		}

		$data['orgs'] = array($data['current_org']);

		$this->load->view('memos', $data);
	}

	/**
	 * 메모 목록 조회
	 */
	public function get_memos()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$org_id = $this->input->post('org_id');
		$memo_types = $this->input->post('memo_types');
		$search_text = $this->input->post('search_text');
		$year = $this->input->post('year');
		$month = $this->input->post('month');

		if (!$org_id) {
			echo json_encode(array(
				'success' => false,
				'message' => '조직 ID가 필요합니다.'
			));
			return;
		}

		$filters = array(
			'memo_types' => $memo_types,
			'search_text' => $search_text,
			'year' => $year,
			'month' => $month
		);

		$memos = $this->Memo_model->get_memos($org_id, $filters);
		$total_count = $this->Memo_model->get_memos_count($org_id, $filters);

		echo json_encode(array(
			'success' => true,
			'curPage' => 1,
			'totalRecords' => $total_count,
			'data' => $memos
		));
	}

	/**
	 * 메모 타입 조회
	 */
	public function get_memo_types()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$org_id = $this->input->post('org_id');

		if (!$org_id) {
			echo json_encode(array('success' => false, 'message' => '조직 ID가 필요합니다.'));
			return;
		}

		$memo_types = $this->Org_model->get_memo_types($org_id);

		echo json_encode(array(
			'success' => true,
			'data' => $memo_types
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
	 * 메모 일괄추가
	 */
	public function add_memo()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$org_id = $this->input->post('org_id');
		$member_idxs = $this->input->post('member_idxs');
		$memo_type = $this->input->post('memo_type');
		$memo_date = $this->input->post('memo_date');
		$memo_content = $this->input->post('memo_content');
		$user_id = $this->session->userdata('user_id');

		if (!$org_id || !$member_idxs || !$memo_type || !$memo_date) {
			echo json_encode(array('success' => false, 'message' => '필수 항목을 입력해주세요.'));
			return;
		}

		if (!is_array($member_idxs)) {
			$member_idxs = array($member_idxs);
		}

		$data = array(
			'memo_type' => $memo_type,
			'att_date' => $memo_date,
			'memo_content' => $memo_content,
			'user_id' => $user_id
		);

		$result = $this->Memo_model->add_memos($member_idxs, $data);

		if ($result) {
			echo json_encode(array('success' => true, 'message' => '메모가 추가되었습니다.'));
		} else {
			echo json_encode(array('success' => false, 'message' => '메모 일괄추가에 실패했습니다.'));
		}
	}

	/**
	 * 메모 수정
	 */
	public function update_memo()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$idx = $this->input->post('idx');
		$memo_type = $this->input->post('memo_type');
		$memo_date = $this->input->post('memo_date');
		$memo_content = $this->input->post('memo_content');

		if (!$idx || !$memo_type || !$memo_date) {
			echo json_encode(array('success' => false, 'message' => '필수 항목을 입력해주세요.'));
			return;
		}

		$data = array(
			'memo_type' => $memo_type,
			'att_date' => $memo_date,
			'memo_content' => $memo_content,
			'modi_date' => date('Y-m-d H:i:s')
		);

		$result = $this->Memo_model->update_memo($idx, $data);

		if ($result) {
			echo json_encode(array('success' => true, 'message' => '메모가 수정되었습니다.'));
		} else {
			echo json_encode(array('success' => false, 'message' => '메모 수정에 실패했습니다.'));
		}
	}

	/**
	 * 메모 삭제
	 */
	public function delete_memos()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$idxs = $this->input->post('idxs');

		if (!$idxs || !is_array($idxs) || count($idxs) === 0) {
			echo json_encode(array('success' => false, 'message' => '삭제할 항목을 선택해주세요.'));
			return;
		}

		$result = $this->Memo_model->delete_memos($idxs);

		if ($result) {
			echo json_encode(array('success' => true, 'message' => count($idxs) . '개의 메모가 삭제되었습니다.'));
		} else {
			echo json_encode(array('success' => false, 'message' => '메모 삭제에 실패했습니다.'));
		}
	}

	/**
	 * 메모 상세 조회
	 */
	public function get_memo_detail()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$idx = $this->input->post('idx');

		if (!$idx) {
			echo json_encode(array('success' => false, 'message' => 'IDX가 필요합니다.'));
			return;
		}

		$memo = $this->Memo_model->get_memo_detail_by_idx($idx);

		if ($memo) {
			echo json_encode(array('success' => true, 'data' => $memo));
		} else {
			echo json_encode(array('success' => false, 'message' => '메모를 찾을 수 없습니다.'));
		}
	}


	/**
	 * 전체 회원 목록 조회 (Select2용 - 미리 로드)
	 */
	public function get_all_members()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$org_id = $this->input->post('org_id');

		if (!$org_id) {
			echo json_encode(array('success' => false, 'message' => '조직 ID가 필요합니다.'));
			return;
		}

		$members = $this->Member_model->get_members_for_select($org_id, '');

		echo json_encode(array(
			'success' => true,
			'data' => $members
		));
	}

}
