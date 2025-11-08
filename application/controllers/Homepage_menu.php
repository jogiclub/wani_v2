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

		// 기존 페이지 정보 조회
		$old_page = $this->Homepage_menu_model->get_page_info($org_id, $menu_id);

		// 기존 내용과 비교하여 삭제된 이미지 정리
		if ($old_page && !empty($old_page['page_content'])) {
			$this->cleanup_removed_images($old_page['page_content'], $page_content);
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
		$page = $this->input->post('page') ? (int)$this->input->post('page') : 1;
		$limit = 10; // 10에서 20으로 변경

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
		$youtube_url = $this->input->post('youtube_url');
		$file_path = $this->input->post('file_path');

		if (!$org_id || !$menu_id || !$board_title) {
			echo json_encode(['success' => false, 'message' => '필수 정보가 누락되었습니다.']);
			return;
		}

		// 수정인 경우, 기존 내용과 비교하여 삭제된 이미지 처리
		if ($idx) {
			$old_board = $this->Homepage_menu_model->get_board_detail($idx);
			if ($old_board && !empty($old_board['board_content'])) {
				$this->cleanup_removed_images($old_board['board_content'], $board_content);
			}
		}

		$data = [
			'org_id' => $org_id,
			'menu_id' => $menu_id,
			'board_title' => $board_title,
			'board_content' => $board_content,
			'youtube_url' => $youtube_url ? $youtube_url : NULL,
			'file_path' => $file_path ? $file_path : NULL
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
	 * 게시글 수정 시 삭제된 이미지 정리
	 */
	private function cleanup_removed_images($old_content, $new_content)
	{
		try {
			$old_images = $this->extract_image_urls($old_content);
			$new_images = $this->extract_image_urls($new_content);

			// 기존에 있었지만 새 내용에는 없는 이미지 = 삭제된 이미지
			$removed_images = array_diff($old_images, $new_images);

			foreach ($removed_images as $image_url) {
				$this->delete_image_from_url($image_url);
				log_message('info', '[게시글 수정] 사용하지 않는 이미지 삭제: ' . $image_url);
			}

		} catch (Exception $e) {
			log_message('error', '[게시글 수정] 이미지 정리 중 오류: ' . $e->getMessage());
		}
	}

	/**
	 * Editor.js 본문에서 이미지 URL 배열 추출
	 */
	private function extract_image_urls($content)
	{
		$image_urls = [];

		try {
			$content_data = json_decode($content, true);

			if (!$content_data || !isset($content_data['blocks'])) {
				return $image_urls;
			}

			foreach ($content_data['blocks'] as $block) {
				// 이미지 블록
				if ($block['type'] === 'image' && isset($block['data']['file']['url'])) {
					$image_urls[] = $block['data']['file']['url'];
				}

				// Attaches 블록
				if ($block['type'] === 'attaches' && isset($block['data']['file']['url'])) {
					$image_urls[] = $block['data']['file']['url'];
				}
			}

		} catch (Exception $e) {
			log_message('error', '[이미지 URL 추출] 오류: ' . $e->getMessage());
		}

		return $image_urls;
	}


	/**
	 * 이미지 업로드 (Editor.js용)
	 */
	public function upload_image()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		// org_id 가져오기 (POST 또는 세션에서)
		$org_id = $this->input->post('org_id');

		if (!$org_id) {
			// 세션에서 현재 조직 정보 가져오기
			$current_org = $this->session->userdata('current_org');
			$org_id = $current_org['org_id'] ?? null;
		}

		if (!$org_id) {
			echo json_encode([
				'success' => 0,
				'message' => '조직 정보가 없습니다.'
			]);
			return;
		}

		// 업로드 경로 설정 (게시판 파일과 동일)
		$year = date('Y');
		$month = date('m');
		$day = date('d');
		$upload_path = "./uploads/homepage/{$org_id}/{$year}/{$month}/{$day}/";

		// 디렉토리 생성
		if (!is_dir($upload_path)) {
			mkdir($upload_path, 0755, true);
		}

		$config['upload_path'] = $upload_path;
		$config['allowed_types'] = 'gif|jpg|jpeg|png|webp';
		$config['max_size'] = 5120; // 5MB
		$config['encrypt_name'] = TRUE;

		$this->load->library('upload', $config);

		if ($this->upload->do_upload('image')) {
			$upload_data = $this->upload->data();
			$file_name = $upload_data['file_name'];
			$file_url = base_url("uploads/homepage/{$org_id}/{$year}/{$month}/{$day}/{$file_name}");

			// Editor.js 응답 형식
			echo json_encode([
				'success' => 1,
				'file' => [
					'url' => $file_url
				]
			]);
		} else {
			$error = $this->upload->display_errors('', '');
			echo json_encode([
				'success' => 0,
				'message' => '이미지 업로드 실패: ' . $error
			]);
		}
	}

	/**
	 * URL에서 이미지 가져오기 (Editor.js용)
	 */
	public function fetch_url_image()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$url = $this->input->post('url');
		$org_id = $this->input->post('org_id');

		if (!$org_id) {
			$current_org = $this->session->userdata('current_org');
			$org_id = $current_org['org_id'] ?? null;
		}

		if (!$url || !$org_id) {
			echo json_encode([
				'success' => 0,
				'message' => '필수 정보가 누락되었습니다.'
			]);
			return;
		}

		// URL 유효성 검사
		if (!filter_var($url, FILTER_VALIDATE_URL)) {
			echo json_encode([
				'success' => 0,
				'message' => '유효하지 않은 URL입니다.'
			]);
			return;
		}

		// 이미지 다운로드
		$image_content = @file_get_contents($url);

		if (!$image_content) {
			echo json_encode([
				'success' => 0,
				'message' => '이미지를 가져올 수 없습니다.'
			]);
			return;
		}

		// 파일 확장자 추출
		$path_info = pathinfo(parse_url($url, PHP_URL_PATH));
		$extension = isset($path_info['extension']) ? strtolower($path_info['extension']) : 'jpg';

		// 허용된 확장자인지 확인
		$allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
		if (!in_array($extension, $allowed_extensions)) {
			$extension = 'jpg';
		}

		// 업로드 경로 설정
		$year = date('Y');
		$month = date('m');
		$day = date('d');
		$upload_path = "./uploads/homepage/{$org_id}/{$year}/{$month}/{$day}/";

		// 디렉토리 생성
		if (!is_dir($upload_path)) {
			mkdir($upload_path, 0755, true);
		}

		// 파일명 생성
		$file_name = 'url_' . uniqid() . '.' . $extension;
		$file_path = $upload_path . $file_name;

		// 파일 저장
		if (file_put_contents($file_path, $image_content)) {
			$file_url = base_url("uploads/homepage/{$org_id}/{$year}/{$month}/{$day}/{$file_name}");

			echo json_encode([
				'success' => 1,
				'file' => [
					'url' => $file_url
				]
			]);
		} else {
			echo json_encode([
				'success' => 0,
				'message' => '파일 저장에 실패했습니다.'
			]);
		}
	}

	/**
	 * URL 메타데이터 가져오기 (Editor.js Link Tool용)
	 */
	public function fetch_url_meta()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$url = $this->input->post('url');

		if (!$url) {
			echo json_encode([
				'success' => 0,
				'message' => 'URL이 필요합니다.'
			]);
			return;
		}

		// URL 유효성 검사
		if (!filter_var($url, FILTER_VALIDATE_URL)) {
			echo json_encode([
				'success' => 0,
				'message' => '유효하지 않은 URL입니다.'
			]);
			return;
		}

		// HTML 가져오기
		$html = @file_get_contents($url);

		if (!$html) {
			echo json_encode([
				'success' => 0,
				'message' => 'URL을 가져올 수 없습니다.'
			]);
			return;
		}

		// 메타 정보 추출
		$title = '';
		$description = '';
		$image = '';

		// Title 추출
		if (preg_match('/<title>(.*?)<\/title>/i', $html, $matches)) {
			$title = $matches[1];
		}

		// Description 추출
		if (preg_match('/<meta[^>]+name=["\']description["\'][^>]+content=["\'](.*?)["\']/i', $html, $matches)) {
			$description = $matches[1];
		} elseif (preg_match('/<meta[^>]+property=["\']og:description["\'][^>]+content=["\'](.*?)["\']/i', $html, $matches)) {
			$description = $matches[1];
		}

		// Image 추출
		if (preg_match('/<meta[^>]+property=["\']og:image["\'][^>]+content=["\'](.*?)["\']/i', $html, $matches)) {
			$image = $matches[1];
		}

		echo json_encode([
			'success' => 1,
			'meta' => [
				'title' => $title ?: $url,
				'description' => $description ?: '',
				'image' => [
					'url' => $image ?: ''
				]
			]
		]);
	}

	/**
	 * 파일 업로드 (Editor.js Attaches Tool용)
	 */
	public function upload_file()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$org_id = $this->input->post('org_id');

		if (!$org_id) {
			$current_org = $this->session->userdata('current_org');
			$org_id = $current_org['org_id'] ?? null;
		}

		if (!$org_id) {
			echo json_encode([
				'success' => 0,
				'message' => '조직 정보가 없습니다.'
			]);
			return;
		}

		// 업로드 경로 설정
		$year = date('Y');
		$month = date('m');
		$day = date('d');
		$upload_path = "./uploads/homepage/{$org_id}/{$year}/{$month}/{$day}/";

		// 디렉토리 생성
		if (!is_dir($upload_path)) {
			mkdir($upload_path, 0755, true);
		}

		$config['upload_path'] = $upload_path;
		$config['allowed_types'] = 'pdf|doc|docx|xls|xlsx|ppt|pptx|zip|txt';
		$config['max_size'] = 10240; // 10MB
		$config['encrypt_name'] = TRUE;

		$this->load->library('upload', $config);

		if ($this->upload->do_upload('file')) {
			$upload_data = $this->upload->data();
			$file_name = $upload_data['file_name'];
			$file_url = base_url("uploads/homepage/{$org_id}/{$year}/{$month}/{$day}/{$file_name}");
			$file_size = $upload_data['file_size'] * 1024; // KB to bytes

			// Editor.js Attaches 응답 형식
			echo json_encode([
				'success' => 1,
				'file' => [
					'url' => $file_url,
					'size' => $file_size,
					'name' => $upload_data['orig_name'],
					'extension' => $upload_data['file_ext']
				]
			]);
		} else {
			$error = $this->upload->display_errors('', '');
			echo json_encode([
				'success' => 0,
				'message' => '파일 업로드 실패: ' . $error
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


	/**
	 * 게시판 파일 업로드 (AJAX) - 원본과 썸네일 별도 업로드
	 */
	public function upload_board_file()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$org_id = $this->input->post('org_id');

		if (!$org_id) {
			echo json_encode(['success' => false, 'message' => '조직 정보가 누락되었습니다.']);
			return;
		}

		// 업로드 경로 설정
		$year = date('Y');
		$month = date('m');
		$day = date('d');
		$upload_path = "./uploads/homepage/{$org_id}/{$year}/{$month}/{$day}/";
		$thumb_path = "./uploads/homepage/{$org_id}/{$year}/{$month}/{$day}/thumb/";

		// 디렉토리 생성
		if (!is_dir($upload_path)) {
			mkdir($upload_path, 0755, true);
		}

		if (!is_dir($thumb_path)) {
			mkdir($thumb_path, 0755, true);
		}

		// 원본 파일 업로드
		$config['upload_path'] = $upload_path;
		$config['allowed_types'] = 'gif|jpg|jpeg|png|pdf|doc|docx|ppt|pptx|xls|xlsx|hwp|hwpx|zip';
		$config['max_size'] = 51200; // 50MB
		$config['encrypt_name'] = TRUE;

		$this->load->library('upload', $config);

		if ($this->upload->do_upload('file')) {
			$upload_data = $this->upload->data();
			$file_name = $upload_data['file_name'];
			$file_ext = strtolower($upload_data['file_ext']);

			// 파일 타입 결정
			$image_extensions = ['.jpg', '.jpeg', '.png', '.gif'];
			$is_image = in_array($file_ext, $image_extensions);

			$file_type = $is_image ? 'image' : 'document';
			$file_path = "/uploads/homepage/{$org_id}/{$year}/{$month}/{$day}/{$file_name}";
			$thumb_file_path = null;

			// 이미지인 경우 썸네일도 업로드
			if ($is_image && isset($_FILES['thumbnail']) && $_FILES['thumbnail']['size'] > 0) {
				// 썸네일 업로드 설정
				$thumb_config['upload_path'] = $thumb_path;
				$thumb_config['allowed_types'] = 'gif|jpg|jpeg|png';
				$thumb_config['max_size'] = 2048; // 2MB
				$thumb_config['file_name'] = $file_name; // 원본과 같은 이름 사용
				$thumb_config['overwrite'] = TRUE;

				$this->upload->initialize($thumb_config);

				if ($this->upload->do_upload('thumbnail')) {
					$thumb_file_path = "/uploads/homepage/{$org_id}/{$year}/{$month}/{$day}/thumb/{$file_name}";
				}
			}

			echo json_encode([
				'success' => true,
				'file_name' => $file_name,
				'file_path' => $file_path,
				'thumb_path' => $thumb_file_path,
				'file_type' => $file_type,
				'message' => '파일이 업로드되었습니다.'
			]);
		} else {
			$error = $this->upload->display_errors('', '');
			echo json_encode([
				'success' => false,
				'message' => '파일 업로드 실패: ' . $error
			]);
		}
	}

	/**
	 * 썸네일 생성
	 */
	private function create_thumbnail($source_path, $thumb_path, $width = 200, $height = 200)
	{
		try {
			$this->load->library('image_lib');

			// 이미지 정보 확인
			$image_info = @getimagesize($source_path);
			if (!$image_info) {
				log_message('error', '이미지 정보를 가져올 수 없습니다: ' . $source_path);
				return false;
			}

			// 썸네일 설정
			$config = [
				'image_library' => 'gd2',
				'source_image' => $source_path,
				'new_image' => $thumb_path,
				'maintain_ratio' => TRUE,
				'width' => $width,
				'height' => $height,
				'quality' => 90
			];

			$this->image_lib->clear();
			$this->image_lib->initialize($config);

			if (!$this->image_lib->resize()) {
				$error = $this->image_lib->display_errors('', '');
				log_message('error', '썸네일 생성 실패: ' . $error . ' / ' . $source_path);
				return false;
			}

			$this->image_lib->clear();
			return true;

		} catch (Exception $e) {
			log_message('error', '썸네일 생성 중 오류: ' . $e->getMessage());
			return false;
		}
	}


	/**
	 * 게시판 개별 파일 삭제 (AJAX)
	 */
	public function delete_board_file()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$file_path = $this->input->post('file_path');
		$thumb_path = $this->input->post('thumb_path');

		if (!$file_path) {
			echo json_encode(['success' => false, 'message' => '파일 경로가 누락되었습니다.']);
			return;
		}

		try {
			$deleted = false;

			// 원본 파일 삭제
			$original_file = '.' . $file_path;
			if (file_exists($original_file)) {
				if (@unlink($original_file)) {
					$deleted = true;
					log_message('info', '파일 삭제 성공: ' . $original_file);
				} else {
					log_message('error', '파일 삭제 실패: ' . $original_file);
				}
			} else {
				log_message('warning', '파일이 존재하지 않음: ' . $original_file);
				$deleted = true; // 파일이 없어도 성공으로 처리
			}

			// 썸네일 파일 삭제 (있는 경우)
			if (!empty($thumb_path)) {
				$thumb_file = '.' . $thumb_path;
				if (file_exists($thumb_file)) {
					if (@unlink($thumb_file)) {
						log_message('info', '썸네일 삭제 성공: ' . $thumb_file);
					} else {
						log_message('error', '썸네일 삭제 실패: ' . $thumb_file);
					}
				}
			}

			if ($deleted) {
				echo json_encode(['success' => true, 'message' => '파일이 삭제되었습니다.']);
			} else {
				echo json_encode(['success' => false, 'message' => '파일 삭제에 실패했습니다.']);
			}
		} catch (Exception $e) {
			log_message('error', '파일 삭제 중 오류: ' . $e->getMessage());
			echo json_encode(['success' => false, 'message' => '파일 삭제 중 오류가 발생했습니다.']);
		}
	}


	/**
	 * 선택된 게시글 일괄 삭제 (AJAX)
	 */
	public function delete_selected_boards()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$idx_list = $this->input->post('idx_list');

		if (!$idx_list || !is_array($idx_list) || count($idx_list) === 0) {
			echo json_encode(['success' => false, 'message' => '삭제할 게시글이 선택되지 않았습니다.']);
			return;
		}

		$deleted_count = 0;
		$failed_count = 0;

		foreach ($idx_list as $idx) {
			// 게시글 정보 조회 (첨부파일 및 본문 정보 확인)
			$board = $this->Homepage_menu_model->get_board_detail($idx);

			if ($board) {
				// 첨부파일 및 Editor.js 이미지 삭제
				$this->delete_board_files(
					$board['file_path'] ?? null,
					$board['board_content'] ?? null
				);

				// 게시글 삭제
				$result = $this->Homepage_menu_model->delete_board($idx);

				if ($result) {
					$deleted_count++;
				} else {
					$failed_count++;
				}
			} else {
				$failed_count++;
			}
		}

		if ($deleted_count > 0) {
			$message = "{$deleted_count}건의 게시글이 삭제되었습니다.";
			if ($failed_count > 0) {
				$message .= " ({$failed_count}건 실패)";
			}
			echo json_encode(['success' => true, 'message' => $message]);
		} else {
			echo json_encode(['success' => false, 'message' => '게시글 삭제에 실패했습니다.']);
		}
	}

	/**
	 * 게시글 삭제 (AJAX) - 파일 및 Editor.js 이미지 삭제 추가
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

		// 게시글 정보 조회 (첨부파일 및 본문 정보 확인)
		$board = $this->Homepage_menu_model->get_board_detail($idx);

		if ($board) {
			// 첨부파일 및 Editor.js 이미지 삭제
			$this->delete_board_files(
				$board['file_path'] ?? null,
				$board['board_content'] ?? null
			);

			// 게시글 삭제
			$result = $this->Homepage_menu_model->delete_board($idx);

			if ($result) {
				echo json_encode(['success' => true, 'message' => '게시글이 삭제되었습니다.']);
			} else {
				echo json_encode(['success' => false, 'message' => '게시글 삭제에 실패했습니다.']);
			}
		} else {
			echo json_encode(['success' => false, 'message' => '게시글을 찾을 수 없습니다.']);
		}
	}

	/**
	 * 게시글 첨부파일 및 Editor.js 이미지 삭제
	 */
	private function delete_board_files($file_path_json, $board_content = null)
	{
		try {
			// 1. Dropzone으로 업로드한 첨부파일 삭제
			if (!empty($file_path_json)) {
				$files = json_decode($file_path_json, true);

				if (is_array($files) && !empty($files)) {
					foreach ($files as $file) {
						if (isset($file['path'])) {
							// 원본 파일 삭제
							$original_file = '.' . $file['path'];
							if (file_exists($original_file)) {
								@unlink($original_file);
								log_message('info', '[게시글 삭제] 첨부파일 삭제: ' . $original_file);
							}

							// 썸네일 파일 삭제 (이미지인 경우)
							if (isset($file['thumb_path']) && !empty($file['thumb_path'])) {
								$thumb_file = '.' . $file['thumb_path'];
								if (file_exists($thumb_file)) {
									@unlink($thumb_file);
									log_message('info', '[게시글 삭제] 썸네일 삭제: ' . $thumb_file);
								}
							}
						}
					}
				}
			}

			// 2. Editor.js 본문 내 이미지 삭제
			if (!empty($board_content)) {
				$this->delete_editorjs_images($board_content);
			}

		} catch (Exception $e) {
			log_message('error', '[게시글 삭제] 파일 삭제 중 오류: ' . $e->getMessage());
		}
	}

	/**
	 * Editor.js 본문에서 이미지 URL 추출 및 삭제
	 */
	private function delete_editorjs_images($board_content)
	{
		try {
			$content_data = json_decode($board_content, true);

			if (!$content_data || !isset($content_data['blocks'])) {
				return;
			}

			foreach ($content_data['blocks'] as $block) {
				// 이미지 블록 처리
				if ($block['type'] === 'image' && isset($block['data']['file']['url'])) {
					$image_url = $block['data']['file']['url'];
					$this->delete_image_from_url($image_url);
				}

				// Attaches 블록 처리 (파일 첨부)
				if ($block['type'] === 'attaches' && isset($block['data']['file']['url'])) {
					$file_url = $block['data']['file']['url'];
					$this->delete_image_from_url($file_url);
				}
			}

		} catch (Exception $e) {
			log_message('error', '[게시글 삭제] Editor.js 이미지 삭제 중 오류: ' . $e->getMessage());
		}
	}


	/**
	 * URL에서 실제 파일 경로 추출 및 삭제
	 */
	private function delete_image_from_url($url)
	{
		try {
			// base_url 제거하고 실제 파일 경로만 추출
			$base = base_url();

			if (strpos($url, $base) === 0) {
				$file_path = str_replace($base, './', $url);
			} else {
				// 상대 경로인 경우
				$file_path = '.' . $url;
			}

			if (file_exists($file_path)) {
				if (@unlink($file_path)) {
					log_message('info', '[게시글 삭제] Editor.js 이미지 삭제: ' . $file_path);
				} else {
					log_message('error', '[게시글 삭제] Editor.js 이미지 삭제 실패: ' . $file_path);
				}
			}

		} catch (Exception $e) {
			log_message('error', '[게시글 삭제] 이미지 URL 처리 중 오류: ' . $e->getMessage());
		}
	}


	/**
	 * 메뉴 삭제 시 관련 데이터 삭제
	 */
	public function delete_menu_data()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$org_id = $this->input->post('org_id');
		$menu_id = $this->input->post('menu_id');
		$menu_type = $this->input->post('menu_type');

		if (!$org_id || !$menu_id || !$menu_type) {
			echo json_encode(['success' => false, 'message' => '필수 정보가 누락되었습니다.']);
			return;
		}

		try {
			if ($menu_type === 'page') {
				// 페이지 정보 조회
				$page = $this->Homepage_menu_model->get_page_info($org_id, $menu_id);

				if ($page && !empty($page['page_content'])) {
					// 페이지 내 이미지 삭제
					$this->delete_editorjs_images($page['page_content']);
				}

				// 페이지 데이터 삭제
				$this->Homepage_menu_model->delete_page($org_id, $menu_id);

			} elseif ($menu_type === 'board') {
				// 게시판의 모든 게시글 조회
				$boards = $this->Homepage_menu_model->get_all_boards_by_menu($org_id, $menu_id);

				foreach ($boards as $board) {
					// 첨부파일 삭제
					if (!empty($board['file_path'])) {
						$this->delete_board_files($board['file_path'], $board['board_content']);
					} elseif (!empty($board['board_content'])) {
						// 첨부파일은 없지만 본문에 이미지가 있을 수 있음
						$this->delete_editorjs_images($board['board_content']);
					}
				}

				// 게시판의 모든 게시글 삭제
				$this->Homepage_menu_model->delete_all_boards_by_menu($org_id, $menu_id);

			} elseif ($menu_type === 'link') {
				// 링크 데이터 삭제
				$this->Homepage_menu_model->delete_link($org_id, $menu_id);
			}

			echo json_encode(['success' => true, 'message' => '메뉴 데이터가 삭제되었습니다.']);

		} catch (Exception $e) {
			log_message('error', '[메뉴 삭제] 오류: ' . $e->getMessage());
			echo json_encode(['success' => false, 'message' => '메뉴 데이터 삭제 중 오류가 발생했습니다.']);
		}
	}

	/**
	 * 역할: upload_card_image, delete_card_image 메서드 추가
	 */

	/**
	 * 카드 그리드 이미지 업로드
	 */
	public function upload_card_image()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		// org_id 가져오기
		$org_id = $this->input->post('org_id');

		if (!$org_id) {
			$current_org = $this->session->userdata('current_org');
			$org_id = $current_org['org_id'] ?? null;
		}

		if (!$org_id) {
			echo json_encode([
				'success' => 0,
				'message' => '조직 정보가 없습니다.'
			]);
			return;
		}

		// 업로드 경로 설정
		$year = date('Y');
		$month = date('m');
		$day = date('d');
		$upload_path = "./uploads/homepage/{$org_id}/{$year}/{$month}/{$day}/";

		// 디렉토리 생성
		if (!is_dir($upload_path)) {
			mkdir($upload_path, 0755, true);
		}

		$config['upload_path'] = $upload_path;
		$config['allowed_types'] = 'gif|jpg|jpeg|png|webp';
		$config['max_size'] = 5120; // 5MB
		$config['encrypt_name'] = TRUE;

		$this->load->library('upload', $config);

		if ($this->upload->do_upload('image')) {
			$upload_data = $this->upload->data();
			$file_name = $upload_data['file_name'];
			$file_url = base_url("uploads/homepage/{$org_id}/{$year}/{$month}/{$day}/{$file_name}");

			echo json_encode([
				'success' => 1,
				'file' => [
					'url' => $file_url
				]
			]);
		} else {
			$error = $this->upload->display_errors('', '');
			echo json_encode([
				'success' => 0,
				'message' => '이미지 업로드 실패: ' . $error
			]);
		}
	}

	/**
	 * 카드 그리드 이미지 삭제
	 */
	public function delete_card_image()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$json_input = file_get_contents('php://input');
		$data = json_decode($json_input, true);

		$image_url = $data['image_url'] ?? '';

		if (empty($image_url)) {
			echo json_encode([
				'success' => false,
				'message' => '이미지 URL이 필요합니다.'
			]);
			return;
		}

		try {
			// URL에서 실제 파일 경로 추출
			$base = base_url();

			if (strpos($image_url, $base) === 0) {
				$file_path = str_replace($base, './', $image_url);
			} else {
				$file_path = '.' . $image_url;
			}

			if (file_exists($file_path)) {
				if (@unlink($file_path)) {
					log_message('info', '[카드 이미지 삭제] 성공: ' . $file_path);
					echo json_encode([
						'success' => true,
						'message' => '이미지가 삭제되었습니다.'
					]);
				} else {
					log_message('error', '[카드 이미지 삭제] 실패: ' . $file_path);
					echo json_encode([
						'success' => false,
						'message' => '이미지 삭제에 실패했습니다.'
					]);
				}
			} else {
				echo json_encode([
					'success' => false,
					'message' => '파일을 찾을 수 없습니다.'
				]);
			}
		} catch (Exception $e) {
			log_message('error', '[카드 이미지 삭제] 오류: ' . $e->getMessage());
			echo json_encode([
				'success' => false,
				'message' => '이미지 삭제 중 오류가 발생했습니다.'
			]);
		}
	}

	/**
	 * 바로가기 섹션 이미지 업로드
	 */
	public function upload_link_image()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$org_id = $this->input->post('org_id');

		if (!$org_id) {
			$current_org = $this->session->userdata('current_org');
			$org_id = $current_org['org_id'] ?? null;
		}

		if (!$org_id) {
			echo json_encode([
				'success' => 0,
				'message' => '조직 정보가 없습니다.'
			]);
			return;
		}

		$year = date('Y');
		$month = date('m');
		$day = date('d');
		$upload_path = "./uploads/homepage/{$org_id}/{$year}/{$month}/{$day}/";

		if (!is_dir($upload_path)) {
			mkdir($upload_path, 0755, true);
		}

		$config['upload_path'] = $upload_path;
		$config['allowed_types'] = 'gif|jpg|jpeg|png|webp';
		$config['max_size'] = 5120;
		$config['encrypt_name'] = TRUE;

		$this->load->library('upload', $config);

		if ($this->upload->do_upload('image')) {
			$upload_data = $this->upload->data();
			$file_name = $upload_data['file_name'];
			$file_url = base_url("uploads/homepage/{$org_id}/{$year}/{$month}/{$day}/{$file_name}");

			echo json_encode([
				'success' => 1,
				'file' => [
					'url' => $file_url
				]
			]);
		} else {
			$error = $this->upload->display_errors('', '');
			echo json_encode([
				'success' => 0,
				'message' => '이미지 업로드 실패: ' . $error
			]);
		}
	}

	/**
	 * 바로가기 섹션 이미지 삭제
	 */
	public function delete_link_image()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$json_input = file_get_contents('php://input');
		$data = json_decode($json_input, true);

		$image_url = $data['image_url'] ?? '';

		if (empty($image_url)) {
			echo json_encode([
				'success' => false,
				'message' => '이미지 URL이 필요합니다.'
			]);
			return;
		}

		try {
			$base = base_url();

			if (strpos($image_url, $base) === 0) {
				$file_path = str_replace($base, './', $image_url);
			} else {
				$file_path = '.' . $image_url;
			}

			if (file_exists($file_path)) {
				if (@unlink($file_path)) {
					log_message('info', '[링크 이미지 삭제] 성공: ' . $file_path);
					echo json_encode([
						'success' => true,
						'message' => '이미지가 삭제되었습니다.'
					]);
				} else {
					log_message('error', '[링크 이미지 삭제] 실패: ' . $file_path);
					echo json_encode([
						'success' => false,
						'message' => '이미지 삭제에 실패했습니다.'
					]);
				}
			} else {
				echo json_encode([
					'success' => false,
					'message' => '파일을 찾을 수 없습니다.'
				]);
			}
		} catch (Exception $e) {
			log_message('error', '[링크 이미지 삭제] 오류: ' . $e->getMessage());
			echo json_encode([
				'success' => false,
				'message' => '이미지 삭제 중 오류가 발생했습니다.'
			]);
		}
	}
}
