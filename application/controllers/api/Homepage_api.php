<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Homepage_api extends CI_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model('Homepage_api_model');
		$this->load->helper('url');

		header('Content-Type: application/json; charset=utf-8');
		header('Access-Control-Allow-Origin: *');
		header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
		header('Access-Control-Allow-Headers: Content-Type');
	}

	/**
	 * 메뉴 데이터 조회
	 * GET /api/homepage_api/get_menu/{org_code}
	 */
	public function get_menu($org_code)
	{
		$menu_data = $this->Homepage_api_model->get_menu_by_org_code($org_code);

		if ($menu_data !== false) {
			// 메뉴 데이터에 menu_category 정보 포함 (이미 있으면 그대로 전달)
			echo json_encode(array(
				'success' => true,
				'message' => '메뉴 조회 성공',
				'data' => $menu_data
			));
		} else {
			echo json_encode(array(
				'success' => false,
				'message' => '메뉴 조회 실패'
			));
		}
	}

	/**
	 * 페이지 내용 조회
	 * GET /api/homepage_api/get_page/{org_code}/{menu_id}
	 */
	public function get_page($org_code = null, $menu_id = null)
	{
		if (empty($org_code) || empty($menu_id)) {
			echo json_encode([
				'success' => false,
				'message' => '조직 코드와 메뉴 ID가 필요합니다.',
				'data' => null
			]);
			return;
		}

		$page_data = $this->Homepage_api_model->get_page_by_org_code_and_menu_id($org_code, $menu_id);

		if ($page_data !== false) {
			// Editor.js JSON을 HTML로 변환
			if (!empty($page_data['page_content'])) {
				$page_data['page_content_html'] = $this->convert_editorjs_to_html($page_data['page_content'], $org_code);
			} else {
				$page_data['page_content_html'] = '';
			}

			echo json_encode([
				'success' => true,
				'message' => '페이지 조회 성공',
				'data' => $page_data
			]);
		} else {
			echo json_encode([
				'success' => false,
				'message' => '페이지를 찾을 수 없습니다.',
				'data' => null
			]);
		}
	}

	/**
	 * Editor.js JSON을 HTML로 변환
	 */
	private function convert_editorjs_to_html($json_content, $org_code = null)
	{
		$data = json_decode($json_content, true);

		if (!$data || !isset($data['blocks'])) {
			return '';
		}

		$html = '';

		foreach ($data['blocks'] as $block) {
			$type = $block['type'] ?? '';
			$data_content = $block['data'] ?? [];

			switch ($type) {
				// ... 기존 case들 유지 ...

				case 'waniPreach':
					$boards = $data_content['boards'] ?? [];

					if (!empty($boards) && !empty($org_code)) {
						$html .= '<div class="wani-preach-block mb-4">';
						$html .= '<div class="row g-3">';

						foreach ($boards as $board_config) {
							$menu_id = $board_config['menu_id'] ?? '';
							$limit = $board_config['limit'] ?? 5;

							// 게시판이 선택되지 않은 경우 스킵
							if (empty($menu_id)) {
								continue;
							}

							// 실제 게시물 데이터 조회
							$board_list = $this->Homepage_api_model->get_board_list_for_block($org_code, $menu_id, $limit);

							if (!empty($board_list)) {
								// 메뉴 이름 조회
								$menu_info = $this->Homepage_api_model->get_menu_info($org_code, $menu_id);
								$menu_name = $menu_info['menu_name'] ?? '게시판';

								$html .= '<div class="col-md-6">';
								$html .= '<div class="card h-100">';
								$html .= '<div class="card-header d-flex justify-content-between align-items-center bg-white py-2">';
								$html .= '<h6 class="mb-0 fw-bold">' . htmlspecialchars($menu_name) . '</h6>';
								$html .= '<a href="/board/' . $menu_id . '" class="text-primary text-decoration-none small">';
								$html .= '<i class="bi bi-plus-circle"></i> 더보기';
								$html .= '</a>';
								$html .= '</div>';
								$html .= '<ul class="list-group list-group-flush">';

								foreach ($board_list as $board) {
									$title = htmlspecialchars($board['board_title'] ?? '');
									$idx = $board['idx'] ?? '';
									$reg_date = $board['reg_date'] ?? '';

									$date = '';
									if ($reg_date) {
										$timestamp = strtotime($reg_date);
										$date = date('Y-m-d', $timestamp);
									}

									$html .= '<li class="list-group-item d-flex justify-content-between align-items-center py-2">';
									$html .= '<a href="/board/' . $menu_id . '/' . $idx . '" class="text-truncate me-2 text-decoration-none text-dark flex-grow-1">' . $title . '</a>';
									$html .= '<small class="text-muted text-nowrap">' . $date . '</small>';
									$html .= '</li>';
								}

								$html .= '</ul>';
								$html .= '</div>';
								$html .= '</div>';
							}
						}

						$html .= '</div>';
						$html .= "</div>\n";
					}
					break;

				default:
					// 기존 코드 유지
					break;
			}
		}

		return $html;
	}

	/**
	 * 중첩 리스트 렌더링 (재귀)
	 */
	private function render_nested_list($items, $tag = 'ul')
	{
		if (empty($items)) {
			return '';
		}

		$html = "<{$tag}>\n";

		foreach ($items as $item) {
			$content = $item['content'] ?? '';
			$children = $item['items'] ?? [];

			$html .= "<li>{$content}";

			if (!empty($children)) {
				$html .= "\n" . $this->render_nested_list($children, $tag);
			}

			$html .= "</li>\n";
		}

		$html .= "</{$tag}>\n";

		return $html;
	}

	/**
	 * 링크 정보 조회
	 * GET /api/homepage_api/get_link/{org_code}/{menu_id}
	 */
	public function get_link($org_code = null, $menu_id = null)
	{
		if (empty($org_code) || empty($menu_id)) {
			echo json_encode([
				'success' => false,
				'message' => '조직 코드와 메뉴 ID가 필요합니다.',
				'data' => null
			]);
			return;
		}

		$link_data = $this->Homepage_api_model->get_link_by_org_code_and_menu_id($org_code, $menu_id);

		if ($link_data !== false) {
			echo json_encode([
				'success' => true,
				'message' => '링크 조회 성공',
				'data' => $link_data
			]);
		} else {
			echo json_encode([
				'success' => false,
				'message' => '링크를 찾을 수 없습니다.',
				'data' => null
			]);
		}
	}

	/**
	 * 게시판 목록 조회
	 * GET /api/homepage_api/get_board_list/{org_code}/{menu_id}
	 * Query Parameters: page, limit, search_keyword
	 */
	public function get_board_list($org_code = null, $menu_id = null)
	{
		if (empty($org_code) || empty($menu_id)) {
			echo json_encode([
				'success' => false,
				'message' => '조직 코드와 메뉴 ID가 필요합니다.',
				'data' => null
			]);
			return;
		}

		$page = $this->input->get('page') ? (int)$this->input->get('page') : 1;
		$limit = $this->input->get('limit') ? (int)$this->input->get('limit') : 20;
		$search_keyword = $this->input->get('search_keyword') ?? '';

		$board_data = $this->Homepage_api_model->get_board_list_by_org_code_and_menu_id(
			$org_code,
			$menu_id,
			$search_keyword,
			$page,
			$limit
		);

		if ($board_data !== false) {
			echo json_encode([
				'success' => true,
				'message' => '게시판 목록 조회 성공',
				'data' => $board_data['list'],
				'total' => $board_data['total'],
				'page' => $page,
				'limit' => $limit
			]);
		} else {
			echo json_encode([
				'success' => false,
				'message' => '게시판 목록을 찾을 수 없습니다.',
				'data' => []
			]);
		}
	}

	/**
	 * 게시글 상세 조회
	 * GET /api/homepage_api/get_board_detail/{org_code}/{idx}
	 */
	public function get_board_detail($org_code = null, $idx = null)
	{
		if (empty($org_code) || empty($idx)) {
			echo json_encode([
				'success' => false,
				'message' => '조직 코드와 게시글 번호가 필요합니다.',
				'data' => null
			]);
			return;
		}

		$board_detail = $this->Homepage_api_model->get_board_detail_by_org_code($org_code, $idx);

		if ($board_detail !== false) {
			echo json_encode([
				'success' => true,
				'message' => '게시글 조회 성공',
				'data' => $board_detail
			]);
		} else {
			echo json_encode([
				'success' => false,
				'message' => '게시글을 찾을 수 없습니다.',
				'data' => null
			]);
		}
	}

	/**
	 * 조직 기본 정보 조회 (홈페이지 설정 포함)
	 * GET /api/homepage_api/get_org_info/{org_code}
	 */
	public function get_org_info($org_code = null)
	{
		if (empty($org_code)) {
			echo json_encode([
				'success' => false,
				'message' => '조직 코드가 필요합니다.',
				'data' => null
			]);
			return;
		}

		$org_info = $this->Homepage_api_model->get_org_info_by_org_code($org_code);

		if ($org_info !== false) {
			echo json_encode([
				'success' => true,
				'message' => '조직 정보 조회 성공',
				'data' => $org_info
			]);
		} else {
			echo json_encode([
				'success' => false,
				'message' => '조직 정보를 찾을 수 없습니다.',
				'data' => null
			]);
		}
	}
}
