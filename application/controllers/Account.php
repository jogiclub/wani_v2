<?php
/**
 * 파일 위치: application/controllers/Account.php
 * 역할: 현금출납 계정관리 컨트롤러 (JSON 기반)
 */
defined('BASEPATH') or exit('No direct script access allowed');

class Account extends My_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model('Cash_book_model');
		$this->load->library('session');
		$this->load->helper('url');
	}

	/**
	 * 계정관리 메인 페이지
	 */
	public function index()
	{
		$user_id = $this->session->userdata('user_id');
		if (!$user_id) {
			redirect('login');
			return;
		}

		$header_data = $this->prepare_header_data();
		if (empty($header_data['user_orgs'])) {
			redirect('dashboard');
			return;
		}

		$data = $header_data;
		$this->handle_org_change($data);

		$currentOrgId = $data['current_org']['org_id'];

		$this->load->model('Org_model');
		$data['selected_org_detail'] = $this->Org_model->get_org_detail_by_id($currentOrgId);
		$data['orgs'] = array($data['current_org']);

		$this->load->view('account', $data);
	}

	/**
	 * 장부 목록 조회
	 */
	public function get_book_list()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$org_id = $this->input->post('org_id');
		if (!$org_id) {
			$org_id = $this->input->cookie('activeOrg');
		}

		if (!$org_id) {
			echo json_encode(array('success' => false, 'message' => '조직 정보를 찾을 수 없습니다.'));
			return;
		}

		$books = $this->Cash_book_model->get_book_list($org_id);
		echo json_encode(array('success' => true, 'data' => $books));
	}

	/**
	 * 장부 추가
	 */
	public function add_book()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$user_id = $this->session->userdata('user_id');
		$org_id = $this->input->post('org_id');
		$book_name = $this->input->post('book_name');
		$fiscal_base_month = $this->input->post('fiscal_base_month');

		if (!$org_id || !$book_name || !$fiscal_base_month) {
			echo json_encode(array('success' => false, 'message' => '필수 정보가 누락되었습니다.'));
			return;
		}

		// 기본 계정과목 생성
		$income_accounts = $this->Cash_book_model->get_default_income_accounts();
		$expense_accounts = $this->Cash_book_model->get_default_expense_accounts();

		$data = array(
			'org_id' => $org_id,
			'book_name' => $book_name,
			'fiscal_base_month' => $fiscal_base_month,
			'income_accounts' => json_encode($income_accounts, JSON_UNESCAPED_UNICODE),
			'expense_accounts' => json_encode($expense_accounts, JSON_UNESCAPED_UNICODE),
			'regi_user_id' => $user_id
		);

		$book_idx = $this->Cash_book_model->add_book($data);

		if ($book_idx) {
			echo json_encode(array('success' => true, 'message' => '장부가 생성되었습니다.', 'book_idx' => $book_idx));
		} else {
			echo json_encode(array('success' => false, 'message' => '장부 생성에 실패했습니다.'));
		}
	}

	/**
	 * 장부 수정
	 */
	public function update_book()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$user_id = $this->session->userdata('user_id');
		$book_idx = $this->input->post('book_idx');
		$book_name = $this->input->post('book_name');
		$fiscal_base_month = $this->input->post('fiscal_base_month');

		if (!$book_idx || !$book_name || !$fiscal_base_month) {
			echo json_encode(array('success' => false, 'message' => '필수 정보가 누락되었습니다.'));
			return;
		}

		$data = array(
			'book_name' => $book_name,
			'fiscal_base_month' => $fiscal_base_month,
			'modi_user_id' => $user_id
		);

		$result = $this->Cash_book_model->update_book($book_idx, $data);

		if ($result) {
			echo json_encode(array('success' => true, 'message' => '장부가 수정되었습니다.'));
		} else {
			echo json_encode(array('success' => false, 'message' => '장부 수정에 실패했습니다.'));
		}
	}

	/**
	 * 장부 삭제
	 */
	public function delete_book()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$book_idx = $this->input->post('book_idx');

		if (!$book_idx) {
			echo json_encode(array('success' => false, 'message' => '장부 정보가 누락되었습니다.'));
			return;
		}

		$result = $this->Cash_book_model->delete_book($book_idx);

		if ($result) {
			echo json_encode(array('success' => true, 'message' => '장부가 삭제되었습니다.'));
		} else {
			echo json_encode(array('success' => false, 'message' => '장부 삭제에 실패했습니다.'));
		}
	}

	/**
	 * 계정과목 트리 조회
	 */
	public function get_account_tree()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$book_idx = $this->input->post('book_idx');
		$account_type = $this->input->post('account_type');

		if (!$book_idx || !$account_type) {
			echo json_encode(array());
			return;
		}

		$book = $this->Cash_book_model->get_book($book_idx);
		if (!$book) {
			echo json_encode(array());
			return;
		}

		$field = $account_type === 'income' ? 'income_accounts' : 'expense_accounts';
		$accounts_json = $book[$field];

		if (empty($accounts_json)) {
			echo json_encode(array());
			return;
		}

		$accounts_data = json_decode($accounts_json, true);
		$tree_data = $this->Cash_book_model->convert_to_fancytree($accounts_data['accounts']);

		echo json_encode($tree_data);
	}

	/**
	 * 계정과목 추가 (하위 계정 생성)
	 */
	public function add_account()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$user_id = $this->session->userdata('user_id');
		$book_idx = $this->input->post('book_idx');
		$parent_id = $this->input->post('parent_id');
		$account_name = $this->input->post('account_name');
		$account_type = $this->input->post('account_type');

		if (!$book_idx || !$account_name || !$account_type) {
			echo json_encode(array('success' => false, 'message' => '필수 정보가 누락되었습니다.'));
			return;
		}

		$result = $this->Cash_book_model->add_account($book_idx, $account_type, $parent_id, $account_name, $user_id);

		if ($result['success']) {
			echo json_encode(array('success' => true, 'message' => '계정이 생성되었습니다.'));
		} else {
			echo json_encode(array('success' => false, 'message' => $result['message']));
		}
	}

	/**
	 * 계정과목 이름 변경
	 */
	public function update_account_name()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$user_id = $this->session->userdata('user_id');
		$book_idx = $this->input->post('book_idx');
		$account_id = $this->input->post('account_id');
		$account_name = $this->input->post('account_name');
		$account_type = $this->input->post('account_type');

		if (!$book_idx || !$account_id || !$account_name || !$account_type) {
			echo json_encode(array('success' => false, 'message' => '필수 정보가 누락되었습니다.'));
			return;
		}

		$result = $this->Cash_book_model->update_account_name($book_idx, $account_type, $account_id, $account_name, $user_id);

		if ($result) {
			echo json_encode(array('success' => true, 'message' => '계정명이 변경되었습니다.'));
		} else {
			echo json_encode(array('success' => false, 'message' => '계정명 변경에 실패했습니다.'));
		}
	}

	/**
	 * 계정과목 삭제
	 */
	public function delete_account()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$user_id = $this->session->userdata('user_id');
		$book_idx = $this->input->post('book_idx');
		$account_id = $this->input->post('account_id');
		$account_type = $this->input->post('account_type');

		if (!$book_idx || !$account_id || !$account_type) {
			echo json_encode(array('success' => false, 'message' => '필수 정보가 누락되었습니다.'));
			return;
		}

		$result = $this->Cash_book_model->delete_account($book_idx, $account_type, $account_id, $user_id);

		if ($result['success']) {
			echo json_encode(array('success' => true, 'message' => '계정이 삭제되었습니다.'));
		} else {
			echo json_encode(array('success' => false, 'message' => $result['message']));
		}
	}

	/**
	 * 계정과목 이동
	 */
	public function move_account()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$user_id = $this->session->userdata('user_id');
		$book_idx = $this->input->post('book_idx');
		$account_id = $this->input->post('account_id');
		$new_parent_id = $this->input->post('new_parent_id');
		$account_type = $this->input->post('account_type');
		$new_index = $this->input->post('new_index');

		if (!$book_idx || !$account_id || !$account_type) {
			echo json_encode(array('success' => false, 'message' => '필수 정보가 누락되었습니다.'));
			return;
		}

		$result = $this->Cash_book_model->move_account($book_idx, $account_type, $account_id, $new_parent_id, $new_index, $user_id);

		if ($result['success']) {
			echo json_encode(array('success' => true, 'message' => '계정이 이동되었습니다.'));
		} else {
			echo json_encode(array('success' => false, 'message' => $result['message']));
		}
	}
}
