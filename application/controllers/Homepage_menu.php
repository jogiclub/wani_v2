<?php
/**
 * 파일 위치: application/controllers/Homepage_menu.php
 * 역할: 홈페이지 메뉴 설정 관리 컨트롤러
 */
defined('BASEPATH') or exit('No direct script access allowed');

class Homepage_menu extends My_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model('Homepage_menu_model');
		$this->load->model('User_model');
		$this->load->library('session');
		$this->load->helper('url');
	}

	/**
	 * 홈페이지 메뉴 설정 메인 페이지
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

		$this->load->view('homepage_menu', $data);
	}

	/**
	 * 메뉴 목록 조회 (AJAX)
	 */
	public function get_menu_list()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$org_id = $this->input->post('org_id');
		if (!$org_id) {
			echo json_encode(['success' => false, 'message' => '조직 정보를 찾을 수 없습니다.']);
			return;
		}

		$menu_data = $this->Homepage_menu_model->get_menu_list($org_id);

		echo json_encode(['success' => true, 'data' => $menu_data]);
	}

	/**
	 * 메뉴 저장 (AJAX)
	 */
	public function save_menu()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$org_id = $this->input->post('org_id');
		$menu_json = $this->input->post('menu_json');

		if (!$org_id || !$menu_json) {
			echo json_encode(['success' => false, 'message' => '필수 정보가 누락되었습니다.']);
			return;
		}

		$result = $this->Homepage_menu_model->save_menu($org_id, $menu_json);

		if ($result) {
			echo json_encode(['success' => true, 'message' => '메뉴가 저장되었습니다.']);
		} else {
			echo json_encode(['success' => false, 'message' => '메뉴 저장에 실패했습니다.']);
		}
	}

	/**
	 * 링크 정보 조회 (AJAX)
	 */
	public function get_link_info()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$org_id = $this->input->post('org_id');
		$menu_id = $this->input->post('menu_id');

		if (!$org_id || !$menu_id) {
			echo json_encode(['success' => false, 'message' => '필수 정보가 누락되었습니다.']);
			return;
		}

		$link_info = $this->Homepage_menu_model->get_link_info($org_id, $menu_id);

		echo json_encode(['success' => true, 'data' => $link_info]);
	}

	/**
	 * 링크 정보 저장 (AJAX)
	 */
	public function save_link()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$org_id = $this->input->post('org_id');
		$menu_id = $this->input->post('menu_id');
		$link_url = $this->input->post('link_url');
		$link_target = $this->input->post('link_target');

		if (!$org_id || !$menu_id) {
			echo json_encode(['success' => false, 'message' => '필수 정보가 누락되었습니다.']);
			return;
		}

		$data = [
			'org_id' => $org_id,
			'menu_id' => $menu_id,
			'link_url' => $link_url,
			'link_target' => $link_target
		];

		$result = $this->Homepage_menu_model->save_link($data);

		if ($result) {
			echo json_encode(['success' => true, 'message' => '링크 정보가 저장되었습니다.']);
		} else {
			echo json_encode(['success' => false, 'message' => '링크 정보 저장에 실패했습니다.']);
		}
	}

	/**
	 * 페이지 정보 조회 (AJAX)
	 */
	public function get_page_info()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$org_id = $this->input->post('org_id');
		$menu_id = $this->input->post('menu_id');

		if (!$org_id || !$menu_id) {
			echo json_encode(['success' => false, 'message' => '필수 정보가 누락되었습니다.']);
			return;
		}

		$page_info = $this->Homepage_menu_model->get_page_info($org_id, $menu_id);

		echo json_encode(['success' => true, 'data' => $page_info]);
	}

	/**
	 * 페이지 정보 저장 (AJAX)
	 */
	public function save_page()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$org_id = $this->input->post('org_id');
		$menu_id = $this->input->post('menu_id');
		$page_content = $this->input->post('page_content');

		if (!$org_id || !$menu_id) {
			echo json_encode(['success' => false, 'message' => '필수 정보가 누락되었습니다.']);
			return;
		}

		$data = [
			'org_id' => $org_id,
			'menu_id' => $menu_id,
			'page_content' => $page_content
		];

		$result = $this->Homepage_menu_model->save_page($data);

		if ($result) {
			echo json_encode(['success' => true, 'message' => '페이지 정보가 저장되었습니다.']);
		} else {
			echo json_encode(['success' => false, 'message' => '페이지 정보 저장에 실패했습니다.']);
		}
	}

	/**
	 * 게시판 목록 조회 (AJAX)
	 */
	public function get_board_list()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$org_id = $this->input->post('org_id');
		$menu_id = $this->input->post('menu_id');
		$search_keyword = $this->input->post('search_keyword');
		$page = $this->input->post('page', 1);
		$limit = 20;

		if (!$org_id || !$menu_id) {
			echo json_encode(['success' => false, 'message' => '필수 정보가 누락되었습니다.']);
			return;
		}

		$result = $this->Homepage_menu_model->get_board_list($org_id, $menu_id, $search_keyword, $page, $limit);

		echo json_encode(['success' => true, 'data' => $result['list'], 'total' => $result['total']]);
	}

	/**
	 * 게시글 상세 조회 (AJAX)
	 */
	public function get_board_detail()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$idx = $this->input->post('idx');

		if (!$idx) {
			echo json_encode(['success' => false, 'message' => '필수 정보가 누락되었습니다.']);
			return;
		}

		$board_info = $this->Homepage_menu_model->get_board_detail($idx);

		echo json_encode(['success' => true, 'data' => $board_info]);
	}

	/**
	 * 게시글 저장 (AJAX)
	 */
	public function save_board()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$user_id = $this->session->userdata('user_id');
		$user_name = $this->session->userdata('user_name');
		$org_id = $this->input->post('org_id');
		$menu_id = $this->input->post('menu_id');
		$idx = $this->input->post('idx');
		$board_title = $this->input->post('board_title');
		$board_content = $this->input->post('board_content');

		if (!$org_id || !$menu_id || !$board_title) {
			echo json_encode(['success' => false, 'message' => '필수 정보가 누락되었습니다.']);
			return;
		}

		$data = [
			'org_id' => $org_id,
			'menu_id' => $menu_id,
			'board_title' => $board_title,
			'board_content' => $board_content
		];

		if ($idx) {
			$data['idx'] = $idx;
			$data['modifier_id'] = $user_id;
			$data['modifier_name'] = $user_name;
		} else {
			$data['writer_id'] = $user_id;
			$data['writer_name'] = $user_name;
		}

		$result = $this->Homepage_menu_model->save_board($data);

		if ($result) {
			echo json_encode(['success' => true, 'message' => '게시글이 저장되었습니다.']);
		} else {
			echo json_encode(['success' => false, 'message' => '게시글 저장에 실패했습니다.']);
		}
	}

	/**
	 * 게시글 삭제 (AJAX)
	 */
	public function delete_board()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$idx = $this->input->post('idx');

		if (!$idx) {
			echo json_encode(['success' => false, 'message' => '필수 정보가 누락되었습니다.']);
			return;
		}

		$result = $this->Homepage_menu_model->delete_board($idx);

		if ($result) {
			echo json_encode(['success' => true, 'message' => '게시글이 삭제되었습니다.']);
		} else {
			echo json_encode(['success' => false, 'message' => '게시글 삭제에 실패했습니다.']);
		}
	}

	/**
	 * 이미지 업로드 (Editor.js용)
	 */
	public function upload_image()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$config['upload_path'] = './uploads/homepage/';
		$config['allowed_types'] = 'gif|jpg|jpeg|png|webp';
		$config['max_size'] = 5120; // 5MB
		$config['encrypt_name'] = TRUE;

		if (!is_dir($config['upload_path'])) {
			mkdir($config['upload_path'], 0755, true);
		}

		$this->load->library('upload', $config);

		if ($this->upload->do_upload('image')) {
			$upload_data = $this->upload->data();
			$file_url = base_url('uploads/homepage/' . $upload_data['file_name']);

			echo json_encode([
				'success' => 1,
				'file' => [
					'url' => $file_url
				]
			]);
		} else {
			echo json_encode([
				'success' => 0,
				'message' => $this->upload->display_errors('', '')
			]);
		}
	}


	/**
	 * 게시판 타입 메뉴 목록 조회 (AJAX)
	 */
	public function get_board_menus()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$org_id = $this->input->post('org_id');

		if (!$org_id) {
			echo json_encode(['success' => false, 'message' => '필수 정보가 누락되었습니다.']);
			return;
		}

		$menus = $this->Homepage_menu_model->get_menu_list($org_id);
		$board_menus = $this->extract_board_menus($menus);

		echo json_encode(['success' => true, 'data' => $board_menus]);
	}

	/**
	 * 메뉴 트리에서 게시판 타입만 추출
	 */
	private function extract_board_menus($menus, &$result = [])
	{
		foreach ($menus as $menu) {
			if (isset($menu['type']) && $menu['type'] === 'board') {
				$result[] = [
					'id' => $menu['id'],
					'name' => $menu['name']
				];
			}

			if (isset($menu['children']) && is_array($menu['children'])) {
				$this->extract_board_menus($menu['children'], $result);
			}
		}

		return $result;
	}

}
