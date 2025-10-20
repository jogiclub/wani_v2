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
				$page_data['page_content_html'] = $this->convert_editorjs_to_html($page_data['page_content']);
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
	private function convert_editorjs_to_html($json_content)
	{
		$data = json_decode($json_content, true);

		if (!$data || !isset($data['blocks'])) {
			return $json_content;
		}

		$html = '';

		foreach ($data['blocks'] as $block) {
			$type = $block['type'] ?? '';
			$data_content = $block['data'] ?? [];

			switch ($type) {
				case 'header':
					$level = $data_content['level'] ?? 2;
					$text = $data_content['text'] ?? '';
					$html .= "<h{$level}>{$text}</h{$level}>\n";
					break;

				case 'paragraph':
					$text = $data_content['text'] ?? '';
					$html .= "<p>{$text}</p>\n";
					break;

				case 'list':
					$style = $data_content['style'] ?? 'unordered';
					$items = $data_content['items'] ?? [];
					$tag = $style === 'ordered' ? 'ol' : 'ul';
					$html .= "<{$tag}>\n";
					foreach ($items as $item) {
						$html .= "<li>{$item}</li>\n";
					}
					$html .= "</{$tag}>\n";
					break;

				case 'image':
					$url = $data_content['file']['url'] ?? '';
					$caption = $data_content['caption'] ?? '';
					$withBorder = $data_content['withBorder'] ?? false;
					$stretched = $data_content['stretched'] ?? false;
					$withBackground = $data_content['withBackground'] ?? false;

					$classes = [];
					if ($withBorder) $classes[] = 'img-border';
					if ($stretched) $classes[] = 'img-stretched';
					if ($withBackground) $classes[] = 'img-background';
					$class_attr = !empty($classes) ? ' class="' . implode(' ', $classes) . '"' : '';

					$html .= '<figure' . $class_attr . '>';
					$html .= '<img src="' . htmlspecialchars($url) . '" alt="' . htmlspecialchars($caption) . '">';
					if ($caption) {
						$html .= '<figcaption>' . htmlspecialchars($caption) . '</figcaption>';
					}
					$html .= "</figure>\n";
					break;

				case 'quote':
					$text = $data_content['text'] ?? '';
					$caption = $data_content['caption'] ?? '';
					$html .= '<blockquote>';
					$html .= "<p>{$text}</p>";
					if ($caption) {
						$html .= "<cite>{$caption}</cite>";
					}
					$html .= "</blockquote>\n";
					break;

				case 'code':
					$code = $data_content['code'] ?? '';
					$html .= '<pre><code>' . htmlspecialchars($code) . "</code></pre>\n";
					break;

				case 'delimiter':
					$html .= "<hr>\n";
					break;

				case 'table':
					$content = $data_content['content'] ?? [];
					$html .= "<table>\n";
					foreach ($content as $row) {
						$html .= "<tr>\n";
						foreach ($row as $cell) {
							$html .= "<td>{$cell}</td>\n";
						}
						$html .= "</tr>\n";
					}
					$html .= "</table>\n";
					break;

				case 'warning':
					$title = $data_content['title'] ?? '';
					$message = $data_content['message'] ?? '';
					$html .= '<div class="alert alert-warning">';
					if ($title) {
						$html .= "<h4>{$title}</h4>";
					}
					$html .= "<p>{$message}</p>";
					$html .= "</div>\n";
					break;

				case 'checklist':
					$items = $data_content['items'] ?? [];
					$html .= '<ul class="checklist">';
					foreach ($items as $item) {
						$checked = $item['checked'] ?? false;
						$text = $item['text'] ?? '';
						$checked_attr = $checked ? ' checked' : '';
						$html .= '<li>';
						$html .= '<input type="checkbox" disabled' . $checked_attr . '> ';
						$html .= $text;
						$html .= '</li>';
					}
					$html .= "</ul>\n";
					break;

				case 'embed':
					$service = $data_content['service'] ?? '';
					$source = $data_content['source'] ?? '';
					$embed = $data_content['embed'] ?? '';
					$caption = $data_content['caption'] ?? '';

					$html .= '<figure class="embed">';
					$html .= '<iframe src="' . htmlspecialchars($embed) . '" frameborder="0" allowfullscreen></iframe>';
					if ($caption) {
						$html .= '<figcaption>' . htmlspecialchars($caption) . '</figcaption>';
					}
					$html .= "</figure>\n";
					break;

				case 'linkTool':
					$link = $data_content['link'] ?? '';
					$meta = $data_content['meta'] ?? [];
					$title = $meta['title'] ?? $link;
					$description = $meta['description'] ?? '';
					$image = $meta['image']['url'] ?? '';

					$html .= '<div class="link-preview">';
					if ($image) {
						$html .= '<img src="' . htmlspecialchars($image) . '" alt="">';
					}
					$html .= '<div class="link-content">';
					$html .= '<a href="' . htmlspecialchars($link) . '" target="_blank">' . htmlspecialchars($title) . '</a>';
					if ($description) {
						$html .= '<p>' . htmlspecialchars($description) . '</p>';
					}
					$html .= '</div>';
					$html .= "</div>\n";
					break;

				case 'raw':
					$html .= $data_content['html'] ?? '';
					break;

				default:
					// 알 수 없는 블록 타입
					break;
			}
		}

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
