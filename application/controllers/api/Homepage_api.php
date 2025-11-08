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
				case 'header':
					$level = $data_content['level'] ?? 2;
					$text = $data_content['text'] ?? '';
					$html .= '<h' . $level . ' class="mb-3">' . $text . '</h' . $level . '>';
					break;

				case 'paragraph':
					$text = $data_content['text'] ?? '';
					if (!empty($text)) {
						$html .= '<p class="mb-3">' . $text . '</p>';
					}
					break;

				case 'list':
					$style = $data_content['style'] ?? 'unordered';
					$items = $data_content['items'] ?? [];
					$tag = ($style === 'ordered') ? 'ol' : 'ul';

					if (!empty($items)) {
						$html .= '<' . $tag . ' class="mb-3">';
						foreach ($items as $item) {
							$html .= '<li>' . $item . '</li>';
						}
						$html .= '</' . $tag . '>';
					}
					break;

				case 'nestedList':
					$style = $data_content['style'] ?? 'unordered';
					$items = $data_content['items'] ?? [];
					$html .= $this->render_nested_list($items, $style);
					break;



				case 'quote':
					$text = $data_content['text'] ?? '';
					$caption = $data_content['caption'] ?? '';
					$html .= '<blockquote class="blockquote mb-3">';
					$html .= '<p>' . $text . '</p>';
					if (!empty($caption)) {
						$html .= '<footer class="blockquote-footer">' . $caption . '</footer>';
					}
					$html .= '</blockquote>';
					break;

				case 'code':
					$code = htmlspecialchars($data_content['code'] ?? '', ENT_QUOTES, 'UTF-8');
					$html .= '<pre class="mb-3"><code>' . $code . '</code></pre>';
					break;

				case 'image':
					$file = $data_content['file'] ?? [];
					$url = $file['url'] ?? '';
					$caption = $data_content['caption'] ?? '';
					$withBorder = $data_content['withBorder'] ?? false;
					$withBackground = $data_content['withBackground'] ?? false;
					$stretched = $data_content['stretched'] ?? false;

					if (!empty($url)) {
						$imgClass = 'img-fluid mb-3';
						if ($withBorder) $imgClass .= ' border';
						if ($stretched) $imgClass .= ' w-100';

						$containerClass = 'mb-3';
						if ($withBackground) $containerClass .= ' bg-light p-3';

						$html .= '<figure class="' . $containerClass . '">';
						$html .= '<img src="' . htmlspecialchars($url) . '" class="' . $imgClass . '" alt="' . htmlspecialchars($caption) . '">';
						if (!empty($caption)) {
							$html .= '<figcaption class="text-muted small mt-2">' . htmlspecialchars($caption) . '</figcaption>';
						}
						$html .= '</figure>';
					}
					break;

				case 'embed':
					$service = $data_content['service'] ?? '';
					$embed_url = $data_content['embed'] ?? '';
					$caption = $data_content['caption'] ?? '';
					$width = $data_content['width'] ?? 0;
					$height = $data_content['height'] ?? 0;

					if (!empty($embed_url)) {
						$html .= '<div class="embed-responsive mb-3">';
						$html .= '<iframe src="' . htmlspecialchars($embed_url) . '" ';
						if ($width > 0) $html .= 'width="' . $width . '" ';
						if ($height > 0) $html .= 'height="' . $height . '" ';
						$html .= 'frameborder="0" allowfullscreen class="w-100"></iframe>';
						if (!empty($caption)) {
							$html .= '<p class="text-muted small mt-2">' . htmlspecialchars($caption) . '</p>';
						}
						$html .= '</div>';
					}
					break;

				case 'table':
					$content = $data_content['content'] ?? [];
					$withHeadings = $data_content['withHeadings'] ?? false;

					if (!empty($content)) {
						$html .= '<div class="table-responsive mb-3">';
						$html .= '<table class="table table-bordered">';

						foreach ($content as $index => $row) {
							if ($index === 0 && $withHeadings) {
								$html .= '<thead><tr>';
								foreach ($row as $cell) {
									$html .= '<th>' . $cell . '</th>';
								}
								$html .= '</tr></thead><tbody>';
							} else {
								if ($index === 0) $html .= '<tbody>';
								$html .= '<tr>';
								foreach ($row as $cell) {
									$html .= '<td>' . $cell . '</td>';
								}
								$html .= '</tr>';
							}
						}

						$html .= '</tbody></table></div>';
					}
					break;

				case 'delimiter':
					$html .= '<hr class="my-4">';
					break;

				case 'warning':
					$title = $data_content['title'] ?? '';
					$message = $data_content['message'] ?? '';
					$html .= '<div class="alert alert-warning mb-3" role="alert">';
					if (!empty($title)) {
						$html .= '<h5 class="alert-heading">' . $title . '</h5>';
					}
					if (!empty($message)) {
						$html .= '<p class="mb-0">' . $message . '</p>';
					}
					$html .= '</div>';
					break;

				case 'linkTool':
					$link = $data_content['link'] ?? '';
					$meta = $data_content['meta'] ?? [];
					$title = $meta['title'] ?? $link;
					$description = $meta['description'] ?? '';
					$image = $meta['image']['url'] ?? '';

					if (!empty($link)) {
						$html .= '<div class="card mb-3">';
						if (!empty($image)) {
							$html .= '<img src="' . htmlspecialchars($image) . '" class="card-img-top" alt="' . htmlspecialchars($title) . '">';
						}
						$html .= '<div class="card-body">';
						$html .= '<h5 class="card-title"><a href="' . htmlspecialchars($link) . '" target="_blank">' . htmlspecialchars($title) . '</a></h5>';
						if (!empty($description)) {
							$html .= '<p class="card-text">' . htmlspecialchars($description) . '</p>';
						}
						$html .= '</div></div>';
					}
					break;

				case 'attaches':
					$file = $data_content['file'] ?? [];
					$url = $file['url'] ?? '';
					$name = $file['name'] ?? '';
					$size = $file['size'] ?? 0;
					$title = $data_content['title'] ?? $name;

					if (!empty($url)) {
						$sizeText = $this->format_file_size($size);
						$html .= '<div class="card mb-3">';
						$html .= '<div class="card-body">';
						$html .= '<i class="bi bi-paperclip me-2"></i>';
						$html .= '<a href="' . htmlspecialchars($url) . '" target="_blank">' . htmlspecialchars($title) . '</a>';
						if (!empty($sizeText)) {
							$html .= ' <small class="text-muted">(' . $sizeText . ')</small>';
						}
						$html .= '</div></div>';
					}
					break;

				case 'raw':
					$html_content = $data_content['html'] ?? '';
					$html .= '<div class="mb-3">' . $html_content . '</div>';
					break;


				case 'WaniLatestList':
					$boards = $data_content['boards'] ?? [];

					if (!empty($boards) && !empty($org_code)) {
						$html .= '<div class="wani-latest-list-block mb-4">';
						$html .= '<div class="row g-3">';

						foreach ($boards as $board_config) {
							$menu_id = $board_config['menu_id'] ?? '';
							$limit = $board_config['limit'] ?? 5;
							$display_type = $board_config['display_type'] ?? 'list';

							if (!empty($menu_id)) {
								$posts = $this->get_board_posts($org_code, $menu_id, $limit);

								$html .= '<div class="col-12">';
								$html .= '<div class="card">';
								$html .= '<div class="card-body">';

								if ($display_type === 'list') {
									$html .= '<ul class="list-group list-group-flush">';
									foreach ($posts as $post) {
										$html .= '<li class="list-group-item">';
										$html .= '<a href="/board/' . $menu_id . '/' . $post['idx'] . '" class="text-decoration-none">';
										$html .= htmlspecialchars($post['board_title']);
										$html .= '</a>';
										$html .= '<small class="text-muted ms-2">' . date('Y-m-d', strtotime($post['reg_date'])) . '</small>';
										$html .= '</li>';
									}
									$html .= '</ul>';
								} else {
									foreach ($posts as $post) {
										$html .= '<div class="mb-3">';
										$html .= '<h6><a href="/board/' . $menu_id . '/' . $post['idx'] . '">' . htmlspecialchars($post['board_title']) . '</a></h6>';
										if (!empty($post['board_content'])) {
											$preview = strip_tags(substr($post['board_content'], 0, 150));
											$html .= '<p class="text-muted small">' . htmlspecialchars($preview) . '...</p>';
										}
										$html .= '</div>';
									}
								}

								$html .= '</div></div></div>';
							}
						}

						$html .= '</div></div>';
					}
					break;


				/**
				 * 역할: convert_editorjs_to_html 함수 내 waniCoverSlide 케이스 - 버튼 렌더링 추가
				 */

				case 'waniCoverSlide':
					$cards = $data_content['cards'] ?? [];

					if (!empty($cards)) {
						// 고유 ID 생성
						$slider_id = 'wani-card-slider-' . uniqid();

						$html .= '<section>';
						$html .= '<div class="wani-cover-slide-block">';
						$html .= '<div id="' . $slider_id . '" class="wani-card-slider mb-0 '.$slider_id.'">';

						foreach ($cards as $card) {
							$image = $card['image'] ?? '';
							$title = $card['title'] ?? '';
							$subtitle = $card['subtitle'] ?? '';
							$buttons = $card['buttons'] ?? [];

							$html .= '<div class="slide-item">';

							if (!empty($image)) {
								$html .= '<div style="background:url(' . htmlspecialchars($image) . ') center center/cover" class="cover-slide-img" alt="' . htmlspecialchars($title) . '">';
							} else {
								$html .= '<div class="cover-slide-img bg-light d-flex align-items-center justify-content-center"><i class="bi bi-image" style="font-size: 48px; color: #adb5bd;"></i>';
							}
							$html .= '<div class="cover-overlay"></div>';
							$html .= '<div class="cover-text">';

							if (!empty($title)) {
								$html .= '<h2 class="card-title">' . htmlspecialchars($title) . '</h2>';
							}

							if (!empty($subtitle)) {
								$html .= '<h4 class="card-text">' . nl2br(htmlspecialchars($subtitle)) . '</h4>';
							}

							// 버튼 렌더링
							if (!empty($buttons)) {
								$html .= '<div class="card-buttons mt-3">';
								foreach ($buttons as $button) {
									$btn_name = $button['name'] ?? '';
									$btn_url = $button['url'] ?? '';

									// 버튼명과 URL이 모두 있는 경우만 렌더링
									if (!empty($btn_name) && !empty($btn_url)) {
										$html .= '<a href="' . $btn_url . '" class="btn btn-outline-warning btn-lg mx-1 fw-semibold" style="border-radius:30px;">' . htmlspecialchars($btn_name) . '</a>';
									}
								}
								$html .= '</div>';
							}

							$html .= '</div>';
							$html .= '</div>';
							$html .= '</div>';
						}

						$html .= '</div>';
						$html .= '</div>';
						$html .= '</section>';
					}
					break;




				case 'waniLatestYoutubeSlide':
					$title = $data_content['title'] ?? '';
					$subtitle = $data_content['subtitle'] ?? '';
					$board_menu_ids = $data_content['board_menu_ids'] ?? [];
					$display_count = $data_content['display_count'] ?? '3';
					$show_board_name = $data_content['show_board_name'] ?? true;
					$show_title = $data_content['show_title'] ?? true;



					if (!empty($board_menu_ids) && is_array($board_menu_ids) && !empty($org_code)) {
						$all_posts = [];

						// 여러 게시판에서 유튜브 게시물 수집
						foreach ($board_menu_ids as $board_menu_id) {
							$posts = $this->Homepage_api_model->get_youtube_board_list($org_code, $board_menu_id, 10);

							// 게시판 정보 추가
							foreach ($posts as &$post) {
								$menu_info = $this->Homepage_api_model->get_menu_info($org_code, $board_menu_id);
								$post['board_name'] = $menu_info ? $menu_info['menu_name'] : '';
								$post['menu_id'] = $board_menu_id;
							}

							$all_posts = array_merge($all_posts, $posts);
						}

						// 날짜순 정렬 (최신순)
						usort($all_posts, function($a, $b) {
							return strtotime($b['reg_date']) - strtotime($a['reg_date']);
						});

						// 최대 10개로 제한
						$all_posts = array_slice($all_posts, 0, 10);

						if (!empty($all_posts)) {
							$slider_id = 'youtube-slider-' . uniqid();

							$html .= '<section>';
							$html .= '<div class="wani-youtube-slide-block mb-4">';
							// 타이틀과 서브타이틀 렌더링
							if (!empty($title) || !empty($subtitle)) {
								$html .= '<div class="youtube-slide-header text-center mb-4">';

								if (!empty($title)) {
									$html .= '<h4 class="youtube-slide-main-title mb-2">' . htmlspecialchars($title) . '</h4>';
								}

								if (!empty($subtitle)) {
									$html .= '<h6 class="youtube-slide-subtitle text-muted mt-3 mb-5">' . nl2br(htmlspecialchars($subtitle)) . '</h6>';
								}

								$html .= '</div>';
							}
							$html .= '<div id="' . $slider_id . '" class="youtube-slide-container" data-slides-to-show="' . $display_count . '">';

							foreach ($all_posts as $post) {
								$thumbnail_url = $this->get_youtube_thumbnail($post['youtube_url']);
								$board_title = htmlspecialchars($post['board_title']);
								$board_name = htmlspecialchars($post['board_name']);
								$post_link = '/board/' . $post['menu_id'] . '/' . $post['idx'];

								// 게시판 ID 기반 색상 생성
								$badge_color = $this->get_board_badge_color($post['menu_id']);

								$html .= '<div class="youtube-slide-item">';
								$html .= '<a href="' . $post_link . '" class="youtube-slide-link">';

								if (!empty($thumbnail_url)) {
									$html .= '<div class="youtube-thumbnail-wrapper">';
									$html .= '<img src="' . $thumbnail_url . '" alt="' . $board_title . '" class="youtube-thumbnail">';
									$html .= '<div class="youtube-play-overlay">';
									$html .= '<i class="bi bi-play-circle-fill"></i>';
									$html .= '</div>';
									$html .= '</div>';
								}

								if ($show_board_name || $show_title) {
									$html .= '<div class="youtube-slide-info">';

									if ($show_board_name && !empty($board_name)) {
										$html .= '<span class="youtube-board-name badge" style="background-color: ' . $badge_color . '; color: #fff;">' . $board_name . '</span>';
									}

									if ($show_title) {
										$html .= '<div class="youtube-post-title">' . $board_title . '</div>';
									}

									$html .= '</div>';
								}

								$html .= '</a>';
								$html .= '</div>';
							}

							$html .= '</div>';
							$html .= '</div>';
							$html .= '</section>';
						}
					}
					break;






				case 'waniLinkList':
					$title = $data_content['title'] ?? '';
					$subtitle = $data_content['subtitle'] ?? '';
					$links = $data_content['links'] ?? '';

					if (!empty($links)) {
						$html .= '<section>';
						$html .= '<div class="wani-link-list-block">';
						$html .= '<div class="container">';

						if (!empty($title)) {
							$html .= '<h4 class="text-center mb-2">' . htmlspecialchars($title) . '</h4>';
						}

						if (!empty($subtitle)) {
							$html .= '<h6 class="text-center text-muted mt-3 mb-5">' . nl2br(htmlspecialchars($subtitle)) . '</h6>';
						}

						$html .= '<div class="box-list">';

						foreach ($links as $link) {
							$link_name = $link['name'] ?? '';
							$link_url = $link['url'] ?? '';
							$link_image = $link['image'] ?? '';

							if (!empty($link_name)) {
								$display_url = !empty($link_url) ? htmlspecialchars($link_url) : '#';


								$html .= '<a href="' . $display_url . '" class="text-decoration-none">';
								$html .= '<div class="box" style="transition: all 0.3s;">';

								if (!empty($link_image)) {
									$html .= '<img src="' . htmlspecialchars($link_image) . '" class="card-img-top" alt="' . htmlspecialchars($link_name) . '" style="height: 40px; object-fit: contain;">';
								}
								$html .= '</div>';

								$html .= '<div class="mt-2 mb-0 text-center text-dark fw-semibold">' . htmlspecialchars($link_name) . '</div>';


								$html .= '</a>';

							}
						}

						$html .= '</div>';
						$html .= '</div>';
						$html .= '</div>';
						$html .= '</section>';
					}
					break;




				default:
					log_message('debug', 'Unknown Editor.js block type: ' . $type);
					break;
			}
		}

		return $html;
	}

	/**
	 * 게시판 ID 기반으로 일관된 배지 색상 생성
	 */
	private function get_board_badge_color($menu_id)
	{
		// 미리 정의된 색상 팔레트 (보기 좋은 색상들)
		$color_palette = [
			'#FF6B6B', // 빨강
			'#4ECDC4', // 청록
			'#FFA07A', // 연어
			'#98D8C8', // 민트
			'#F7DC6F', // 노랑
			'#BB8FCE', // 보라
			'#85C1E2', // 파랑
			'#F8B739', // 주황
			'#52C41A', // 초록
			'#FA8C16', // 진한 주황
			'#13C2C2', // 시안
			'#EB2F96', // 핑크
			'#45B7D1', // 하늘
			'#722ED1', // 진한 보라
			'#52C5A5'  // 에메랄드
		];

		// 메뉴 ID를 숫자로 변환하여 색상 인덱스 결정
		$hash = crc32($menu_id);
		$index = abs($hash) % count($color_palette);

		return $color_palette[$index];
	}


	/**
	 * 메뉴 이름 조회
	 */
	private function get_menu_name($org_id, $menu_id)
	{
		$this->db->select('name');
		$this->db->from('tb_homepage_menu');
		$this->db->where('org_id', $org_id);
		$this->db->where('id', $menu_id);
		$query = $this->db->get();

		$row = $query->row_array();
		return $row ? $row['name'] : '';
	}

	private function render_nested_list($items, $style = 'unordered')
	{
		if (empty($items)) {
			return '';
		}

		$tag = ($style === 'ordered') ? 'ol' : 'ul';
		$html = '<' . $tag . ' class="mb-3">';

		foreach ($items as $item) {
			$html .= '<li>';
			$html .= $item['content'] ?? '';

			if (!empty($item['items'])) {
				$html .= $this->render_nested_list($item['items'], $style);
			}

			$html .= '</li>';
		}

		$html .= '</' . $tag . '>';

		return $html;
	}

	private function format_file_size($bytes)
	{
		if ($bytes == 0) return '';

		$units = array('B', 'KB', 'MB', 'GB');
		$bytes = max($bytes, 0);
		$pow = floor(($bytes ? log($bytes) : 0) / log(1024));
		$pow = min($pow, count($units) - 1);
		$bytes /= (1 << (10 * $pow));

		return round($bytes, 2) . ' ' . $units[$pow];
	}

	private function get_board_posts($org_code, $menu_id, $limit)
	{
		$this->load->model('Homepage_api_model');
		return $this->Homepage_api_model->get_board_list_for_block($org_code, $menu_id, $limit);
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




	/**
	 * YouTube URL에서 비디오 ID 추출
	 */
	private function extract_youtube_id($url)
	{
		if (empty($url)) {
			return null;
		}

		$pattern = '/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/ ]{11})/i';

		if (preg_match($pattern, $url, $matches)) {
			return $matches[1];
		}

		return null;
	}

	/**
	 * YouTube 썸네일 URL 생성
	 */
	private function get_youtube_thumbnail($youtube_url)
	{
		$video_id = $this->extract_youtube_id($youtube_url);

		if ($video_id) {
			return "https://img.youtube.com/vi/{$video_id}/maxresdefault.jpg";
		}

		return '';
	}

}
