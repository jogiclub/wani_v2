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
		$limit = 10;

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

			// upload_board_file() 함수
			echo json_encode([
				'success' => true,
				'message' => '파일이 업로드되었습니다.',
				'file_path' => $file_path,
				'file_name' => $upload_data['orig_name']
			], JSON_UNESCAPED_UNICODE);
		} else {
			// save_board() 함수
			echo json_encode([
				'success' => true,
				'message' => '게시글이 저장되었습니다.'
			], JSON_UNESCAPED_UNICODE);
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
		$config['max_size'] = 10240; // 10MB
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
			// 게시글 정보 조회 (첨부파일 정보 확인)
			$board = $this->Homepage_menu_model->get_board_detail($idx);

			if ($board) {
				// 첨부파일 삭제
				if (!empty($board['file_path'])) {
					$this->delete_board_files($board['file_path']);
				}

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
	 * 게시글 삭제 (AJAX) - 파일 삭제 추가
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

		// 게시글 정보 조회 (첨부파일 정보 확인)
		$board = $this->Homepage_menu_model->get_board_detail($idx);

		if ($board) {
			// 첨부파일 삭제
			if (!empty($board['file_path'])) {
				$this->delete_board_files($board['file_path']);
			}

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
	 * 게시글 첨부파일 삭제
	 */
	private function delete_board_files($file_path_json)
	{
		try {
			$files = json_decode($file_path_json, true);

			if (!is_array($files) || empty($files)) {
				return;
			}

			foreach ($files as $file) {
				if (isset($file['path'])) {
					// 원본 파일 삭제
					$original_file = '.' . $file['path'];
					if (file_exists($original_file)) {
						@unlink($original_file);
						log_message('info', '파일 삭제: ' . $original_file);
					}

					// 썸네일 파일 삭제 (이미지인 경우)
					if (isset($file['thumb_path']) && !empty($file['thumb_path'])) {
						$thumb_file = '.' . $file['thumb_path'];
						if (file_exists($thumb_file)) {
							@unlink($thumb_file);
							log_message('info', '썸네일 삭제: ' . $thumb_file);
						}
					}
				}
			}
		} catch (Exception $e) {
			log_message('error', '파일 삭제 중 오류: ' . $e->getMessage());
		}
	}



}
