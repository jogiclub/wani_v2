<?php
/**
 * 파일 위치: application/controllers/Income.php
 * 역할: 수입지출 관리 컨트롤러
 */
defined('BASEPATH') or exit('No direct script access allowed');

class Income extends My_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model('Income_expense_model');
		$this->load->model('Cash_book_model');
		$this->load->library('session');
		$this->load->helper('url');
	}

	/**
	 * 수입지출 메인 페이지
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

		$this->load->view('income', $data);
	}

	/**
	 * 수입지출 목록 조회 (pqGrid용)
	 */
	public function get_list()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$book_idx = $this->input->post('book_idx');
		$org_id = $this->input->post('org_id');

		if (!$org_id) {
			$org_id = $this->input->cookie('activeOrg');
		}

		$params = array(
			'book_idx' => $book_idx,
			'org_id' => $org_id,
			'income_type' => $this->input->post('income_type'),
			'bank' => $this->input->post('bank'),
			'account_codes' => $this->input->post('account_codes'),
			'tags' => $this->input->post('tags'),
			'start_date' => $this->input->post('start_date'),
			'end_date' => $this->input->post('end_date'),
			'keyword' => $this->input->post('keyword')
		);

		$result = $this->Income_expense_model->get_list($params);
		echo json_encode(array('success' => true, 'data' => $result));
	}

	/**
	 * 수입지출 상세 조회
	 */
	public function get_detail()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$idx = $this->input->post('idx');
		if (!$idx) {
			echo json_encode(array('success' => false, 'message' => '항목 정보가 누락되었습니다.'));
			return;
		}

		$result = $this->Income_expense_model->get_detail($idx);
		if ($result) {
			echo json_encode(array('success' => true, 'data' => $result));
		} else {
			echo json_encode(array('success' => false, 'message' => '데이터를 찾을 수 없습니다.'));
		}
	}

	/**
	 * 수입지출 등록
	 */
	public function add()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$user_id = $this->session->userdata('user_id');
		$user_name = $this->session->userdata('user_name');

		$data = array(
			'book_idx' => $this->input->post('book_idx'),
			'org_id' => $this->input->post('org_id'),
			'income_type' => $this->input->post('income_type'),
			'bank' => $this->input->post('bank'),
			'account_code' => $this->input->post('account_code'),
			'account_name' => $this->input->post('account_name'),
			'transaction_date' => $this->input->post('transaction_date'),
			'transaction_cnt' => $this->input->post('transaction_cnt') ?: 1,
			'amount' => $this->input->post('amount'),
			'tags' => $this->input->post('tags'),
			'memo' => $this->input->post('memo'),
			'regi_user_id' => $user_id,
			'regi_user_name' => $user_name
		);

		if (!$data['book_idx'] || !$data['org_id'] || !$data['income_type'] ||
			!$data['account_code'] || !$data['transaction_date'] || !$data['amount']) {
			echo json_encode(array('success' => false, 'message' => '필수 정보가 누락되었습니다.'));
			return;
		}

		$idx = $this->Income_expense_model->add($data);
		if ($idx) {
			echo json_encode(array('success' => true, 'message' => '등록되었습니다.', 'idx' => $idx));
		} else {
			echo json_encode(array('success' => false, 'message' => '등록에 실패했습니다.'));
		}
	}

	/**
	 * 수입지출 수정
	 */
	public function update()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$user_id = $this->session->userdata('user_id');
		$user_name = $this->session->userdata('user_name');
		$idx = $this->input->post('idx');

		if (!$idx) {
			echo json_encode(array('success' => false, 'message' => '항목 정보가 누락되었습니다.'));
			return;
		}

		$data = array(
			'bank' => $this->input->post('bank'),
			'account_code' => $this->input->post('account_code'),
			'account_name' => $this->input->post('account_name'),
			'transaction_date' => $this->input->post('transaction_date'),
			'transaction_cnt' => $this->input->post('transaction_cnt') ?: 1,
			'amount' => $this->input->post('amount'),
			'tags' => $this->input->post('tags'),
			'memo' => $this->input->post('memo'),
			'modi_user_id' => $user_id,
			'modi_user_name' => $user_name
		);

		$result = $this->Income_expense_model->update($idx, $data);
		if ($result) {
			echo json_encode(array('success' => true, 'message' => '수정되었습니다.'));
		} else {
			echo json_encode(array('success' => false, 'message' => '수정에 실패했습니다.'));
		}
	}

	/**
	 * 수입지출 삭제 (단건/복수)
	 */
	public function delete()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$idx_list = $this->input->post('idx_list');
		if (empty($idx_list)) {
			echo json_encode(array('success' => false, 'message' => '삭제할 항목을 선택해주세요.'));
			return;
		}

		if (!is_array($idx_list)) {
			$idx_list = array($idx_list);
		}

		$result = $this->Income_expense_model->delete($idx_list);
		if ($result) {
			echo json_encode(array('success' => true, 'message' => '삭제되었습니다.'));
		} else {
			echo json_encode(array('success' => false, 'message' => '삭제에 실패했습니다.'));
		}
	}

	/**
	 * 계정과목 목록 조회 (검색용)
	 */
	public function get_accounts()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$book_idx = $this->input->post('book_idx');
		$account_type = $this->input->post('account_type');

		if (!$book_idx) {
			echo json_encode(array('success' => false, 'data' => array()));
			return;
		}

		$book = $this->Cash_book_model->get_book($book_idx);
		if (!$book) {
			echo json_encode(array('success' => false, 'data' => array()));
			return;
		}

		$field = $account_type === 'income' ? 'income_accounts' : 'expense_accounts';
		$accounts_json = $book[$field];

		if (empty($accounts_json)) {
			echo json_encode(array('success' => true, 'data' => array()));
			return;
		}

		$accounts_data = json_decode($accounts_json, true);
		$flat_list = $this->flatten_accounts($accounts_data['accounts']);

		echo json_encode(array('success' => true, 'data' => $flat_list));
	}

	/**
	 * 계정과목 평탄화 (재귀)
	 */
	private function flatten_accounts($accounts, &$result = array())
	{
		foreach ($accounts as $account) {
			$result[] = array(
				'code' => $account['code'],
				'name' => $account['name'],
				'display' => $account['code'] . ' ' . $account['name']
			);
			if (!empty($account['children'])) {
				$this->flatten_accounts($account['children'], $result);
			}
		}
		return $result;
	}

	/**
	 * 계좌 목록 조회
	 */
	public function get_banks()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$book_idx = $this->input->post('book_idx');
		if (!$book_idx) {
			echo json_encode(array('success' => false, 'data' => array()));
			return;
		}

		$book = $this->Cash_book_model->get_book($book_idx);
		if (!$book || empty($book['bank'])) {
			echo json_encode(array('success' => true, 'data' => array()));
			return;
		}

		$banks = json_decode($book['bank'], true);
		echo json_encode(array('success' => true, 'data' => $banks ?: array()));
	}

	/**
	 * 태그 목록 조회
	 */
	public function get_tags()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$book_idx = $this->input->post('book_idx');
		$org_id = $this->input->post('org_id');

		if (!$org_id) {
			$org_id = $this->input->cookie('activeOrg');
		}

		$tags = $this->Income_expense_model->get_used_tags($book_idx, $org_id);
		echo json_encode(array('success' => true, 'data' => $tags));
	}
}
