<?php
/**
 * 파일 위치: application/controllers/mng/Mng_org.php
 * 역할: 마스터 권한 조직 관리 컨트롤러
 */

defined('BASEPATH') or exit('No direct script access allowed');

class Mng_org extends CI_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->library('session');
		$this->load->helper('url');
		$this->load->model('Org_model');
		$this->load->model('Org_category_model');
		$this->load->model('User_model');

		// 로그인 확인
		if (!$this->session->userdata('user_id')) {
			redirect('login');
		}

		// 마스터 권한 확인
		if ($this->session->userdata('master_yn') !== 'Y') {
			show_error('접근 권한이 없습니다.', 403);
		}
	}

	/**
	 * 조직관리 메인 페이지
	 */
	public function index()
	{
		$data['user'] = $this->User_model->get_user_by_id($this->session->userdata('user_id'));
		$this->load->view('mng/mng_orglist', $data);
	}

	/**
	 * 조직 카테고리 트리 데이터 조회 (AJAX)
	 */
	public function get_category_tree()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$tree_data = $this->Org_category_model->get_category_tree();

		header('Content-Type: application/json; charset=utf-8');
		echo json_encode($tree_data);
	}

	/**
	 * 조직 목록 조회 (AJAX)
	 */
	public function get_org_list()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$category_idx = $this->input->get('category_idx');
		$orgs = $this->Org_model->get_orgs_by_category_detailed($category_idx);

		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(array(
			'success' => true,
			'data' => $orgs
		));
	}

	/**
	 * 조직 상세 정보 조회 (AJAX)
	 */
	public function get_org_detail()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$org_id = $this->input->get('org_id');
		if (!$org_id) {
			echo json_encode(array('success' => false, 'message' => '조직 ID가 누락되었습니다.'));
			return;
		}

		$org_detail = $this->Org_model->get_org_detail_by_id($org_id);
		if (!$org_detail) {
			echo json_encode(array('success' => false, 'message' => '조직 정보를 찾을 수 없습니다.'));
			return;
		}

		// 조직 회원 수 조회
		$member_count = $this->Org_model->get_org_member_count($org_id);
		$org_detail['member_count'] = $member_count;

		// 조직 관리자 정보 조회
		$org_admin = $this->Org_model->get_org_admin($org_id);
		$org_detail['admin_info'] = $org_admin;

		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(array(
			'success' => true,
			'data' => $org_detail
		));
	}

	/**
	 * 새 카테고리 추가 (AJAX)
	 */
	public function add_category()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$category_name = trim($this->input->post('category_name'));
		$parent_idx = $this->input->post('parent_idx');

		if (empty($category_name)) {
			echo json_encode(array('success' => false, 'message' => '카테고리명을 입력해주세요.'));
			return;
		}

		// parent_idx가 빈 값이면 null로 설정
		if (empty($parent_idx)) {
			$parent_idx = null;
		}

		$data = array(
			'category_name' => $category_name,
			'parent_idx' => $parent_idx,
			'category_order' => $this->Org_category_model->get_next_order($parent_idx)
		);

		$result = $this->Org_category_model->insert_category($data);

		if ($result) {
			echo json_encode(array('success' => true, 'message' => '카테고리가 추가되었습니다.'));
		} else {
			echo json_encode(array('success' => false, 'message' => '카테고리 추가에 실패했습니다.'));
		}
	}

	/**
	 * 카테고리명 수정 (AJAX)
	 */
	public function rename_category()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$category_idx = $this->input->post('category_idx');
		$category_name = trim($this->input->post('category_name'));

		if (!$category_idx || empty($category_name)) {
			echo json_encode(array('success' => false, 'message' => '필수 정보가 누락되었습니다.'));
			return;
		}

		$result = $this->Org_category_model->update_category($category_idx, array('category_name' => $category_name));

		if ($result) {
			echo json_encode(array('success' => true, 'message' => '카테고리명이 수정되었습니다.'));
		} else {
			echo json_encode(array('success' => false, 'message' => '카테고리명 수정에 실패했습니다.'));
		}
	}

	/**
	 * 카테고리 삭제 (AJAX)
	 */
	public function delete_category()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$category_idx = $this->input->post('category_idx');

		if (!$category_idx) {
			echo json_encode(array('success' => false, 'message' => '카테고리 ID가 누락되었습니다.'));
			return;
		}

		// 하위 카테고리 또는 조직이 있는지 확인
		if ($this->Org_category_model->has_children($category_idx) || $this->Org_model->has_orgs_in_category($category_idx)) {
			echo json_encode(array('success' => false, 'message' => '하위 카테고리나 조직이 있어서 삭제할 수 없습니다.'));
			return;
		}

		$result = $this->Org_category_model->delete_category($category_idx);

		if ($result) {
			echo json_encode(array('success' => true, 'message' => '카테고리가 삭제되었습니다.'));
		} else {
			echo json_encode(array('success' => false, 'message' => '카테고리 삭제에 실패했습니다.'));
		}
	}

	/**
	 * 조직 삭제 (AJAX)
	 */
	public function delete_org()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$org_id = $this->input->post('org_id');

		if (!$org_id) {
			echo json_encode(array('success' => false, 'message' => '조직 ID가 누락되었습니다.'));
			return;
		}

		// 조직에 회원이 있는지 확인
		$member_count = $this->Org_model->get_org_member_count($org_id);
		if ($member_count > 0) {
			echo json_encode(array('success' => false, 'message' => '조직에 회원이 있어서 삭제할 수 없습니다.'));
			return;
		}

		$result = $this->Org_model->delete_org($org_id);

		if ($result) {
			echo json_encode(array('success' => true, 'message' => '조직이 삭제되었습니다.'));
		} else {
			echo json_encode(array('success' => false, 'message' => '조직 삭제에 실패했습니다.'));
		}
	}
}
