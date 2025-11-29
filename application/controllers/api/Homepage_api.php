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
		// OPTIONS 요청 처리 (Preflight)
		if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
			exit(0);
		}
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
	 * 파일 다운로드 프록시
	 */
	/**
	 * 파일 다운로드 프록시
	 */
	public function download_file()
	{
		$file_path = $this->input->get('file');
		$file_name = $this->input->get('name');

		if (!$file_path) {
			log_message('error', '[파일 다운로드] 파일 경로 없음');
			show_404();
			return;
		}

		// 보안: 상대 경로 공격 방지
		if (strpos($file_path, '..') !== false) {
			log_message('error', '[파일 다운로드] 잘못된 경로(..포함): ' . $file_path);
			show_404();
			return;
		}

		// http:// 또는 https:// 로 시작하는 경로 차단
		if (strpos($file_path, 'http://') === 0 || strpos($file_path, 'https://') === 0) {
			log_message('error', '[파일 다운로드] 잘못된 경로(URL): ' . $file_path);
			show_404();
			return;
		}

		// 파일 경로 정리 (앞의 / 제거)
		$file_path = ltrim($file_path, '/');

		// 실제 파일 전체 경로
		// FCPATH = /var/www/wani/public/
		$full_path = FCPATH . $file_path;

		log_message('info', '[파일 다운로드] 요청 경로: ' . $file_path);
		log_message('info', '[파일 다운로드] FCPATH: ' . FCPATH);
		log_message('info', '[파일 다운로드] 전체 경로: ' . $full_path);

		// 파일 존재 확인
		if (!file_exists($full_path)) {
			log_message('error', '[파일 다운로드] 파일 없음: ' . $full_path);
			show_404();
			return;
		}

		if (!is_file($full_path)) {
			log_message('error', '[파일 다운로드] 디렉토리임: ' . $full_path);
			show_404();
			return;
		}

		// 파일명이 없으면 원본 파일명 사용
		if (!$file_name) {
			$file_name = basename($full_path);
		}

		// 파일명 인코딩 처리 (한글 파일명 지원)
		$encoded_filename = rawurlencode($file_name);

		// MIME 타입 설정
		$mime_type = 'application/octet-stream';
		if (function_exists('finfo_open')) {
			$finfo = finfo_open(FILEINFO_MIME_TYPE);
			if ($finfo) {
				$detected_mime = finfo_file($finfo, $full_path);
				if ($detected_mime) {
					$mime_type = $detected_mime;
				}
				finfo_close($finfo);
			}
		}

		log_message('info', '[파일 다운로드] 다운로드 시작: ' . $file_name . ' (' . $mime_type . ')');

		// 모든 출력 버퍼 비우기
		while (ob_get_level()) {
			ob_end_clean();
		}

		// 다운로드 헤더 설정
		header('Content-Type: ' . $mime_type);
		header('Content-Disposition: attachment; filename="' . $encoded_filename . '"');
		header('Content-Length: ' . filesize($full_path));
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Pragma: public');
		header('Expires: 0');
		header('Content-Transfer-Encoding: binary');
		header('Accept-Ranges: bytes');

		// 파일 출력 (대용량 파일 지원)
		$file = fopen($full_path, 'rb');
		if ($file) {
			while (!feof($file)) {
				echo fread($file, 8192);
				flush();
			}
			fclose($file);
		} else {
			readfile($full_path);
		}

		exit;
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
						$html .= '<section>';
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

						$html .= '</div></div></section>';
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
							$html .= '<div class="wani-youtube-slide-block">';
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


				/**
				 * 파일 위치: application/controllers/api/Homepage_api.php
				 * 역할: convert_editorjs_to_html 함수 내 waniIntroLink 케이스 추가
				 */

				case 'waniIntroLink':
					$title = $data_content['title'] ?? '';
					$subtitle = $data_content['subtitle'] ?? '';
					$cards = $data_content['cards'] ?? [];

					if (!empty($cards)) {
						$html .= '<section>';
						$html .= '<div class="wani-intro-link-block">';
						$html .= '<div class="container">';

						// 상단 타이틀 섹션
						if (!empty($title) || !empty($subtitle)) {
							$html .= '<div class="text-center mb-5">';
							if (!empty($title)) {
								$html .= '<h4 class="fw-bold mb-3">' . htmlspecialchars($title) . '</h4>';
							}
							if (!empty($subtitle)) {
								$html .= '<h6 class="text-muted">' . nl2br(htmlspecialchars($subtitle)) . '</h6>';
							}
							$html .= '</div>';
						}

						// 커버 슬라이드
						$html .= '<div class="row g-4">';

						foreach ($cards as $card) {
							$image = $card['image'] ?? '';
							$imageTitle = $card['imageTitle'] ?? '';
							$cardTitle = $card['title'] ?? '';
							$cardSubtitle = $card['subtitle'] ?? '';
							$buttons = $card['buttons'] ?? [];

							$html .= '<div class="col-12 col-md-6 col-lg-4">';
							$html .= '<div class="card h-100 border-0 shadow-sm">';

							// 이미지 영역
							if (!empty($image)) {
								$html .= '<div class="position-relative">';
								$html .= '<img src="' . htmlspecialchars($image) . '" class="card-img-top" alt="' . htmlspecialchars($cardTitle) . '" style="height: 250px; object-fit: cover;">';

								// 이미지 제목 오버레이
								if (!empty($imageTitle)) {
									$html .= '<div class="overlay position-absolute bottom-0 start-0 end-0 bg-dark bg-opacity-50 text-white p-2">';
									$html .= '<p class="img-title mb-0 small fw-semibold">' . htmlspecialchars($imageTitle) . '</p>';
									$html .= '</div>';
								}

								$html .= '</div>';
							} else {
								$html .= '<div class="bg-light d-flex align-items-center justify-content-center" style="height: 250px;">';
								$html .= '<i class="bi bi-image" style="font-size: 48px; color: #adb5bd;"></i>';
								$html .= '</div>';
							}

							// 카드 본문
							$html .= '<div class="card-body">';

							if (!empty($cardTitle)) {
								$html .= '<h5 class="card-title fw-bold">' . htmlspecialchars($cardTitle) . '</h5>';
							}

							if (!empty($cardSubtitle)) {
								$html .= '<p class="card-text text-muted">' . nl2br(htmlspecialchars($cardSubtitle)) . '</p>';
							}

							// 버튼 렌더링
							if (!empty($buttons)) {
								$html .= '<div class="btn-group w-100">';
								foreach ($buttons as $button) {
									$btnName = $button['name'] ?? '';
									$btnUrl = $button['url'] ?? '';


									if (!empty($btnName)) {
										$html .= '<a href="' . htmlspecialchars($btnUrl) . '" class="btn btn-outline-dark">' . htmlspecialchars($btnName) . '</a>';
									}
								}
								$html .= '</div>';
							}

							$html .= '</div>'; // card-body
							$html .= '</div>'; // card
							$html .= '</div>'; // col
						}

						$html .= '</div>'; // row
						$html .= '</div>'; // container
						$html .= '</div>'; //
						$html .= '</section>';
					}
					break;
				case 'waniLinkListBg':
					$title = $data_content['title'] ?? '';
					$subtitle = $data_content['subtitle'] ?? '';
					$backgroundImage = $data_content['backgroundImage'] ?? '';
					$buttons = $data_content['buttons'] ?? [];

					if (!empty($buttons)) {
						$html .= '<section>';
						$html .= '<div class="wani-link-list-bg-block">';

						// 백그라운드 이미지가 있는 경우
						$bg_style = '';
						if (!empty($backgroundImage)) {
							$bg_style = 'background-image: url(' . htmlspecialchars($backgroundImage) . '); background-size: cover; background-position: center; background-repeat: no-repeat;';
						}

						$html .= '<div class="bg-wrapper" style="' . $bg_style . '">';
						$html .= '<div class="overlay"></div>';
						$html .= '<div class="container">';

						// 타이틀 및 서브타이틀
						if (!empty($title) || !empty($subtitle)) {
							$html .= '<div class="text-center mb-4">';

							if (!empty($title)) {
								$html .= '<h4 class="mb-2">' . htmlspecialchars($title) . '</h4>';
							}

							if (!empty($subtitle)) {
								$html .= '<h6 class="mt-3 mb-5">' . nl2br(htmlspecialchars($subtitle)) . '</h6>';
							}

							$html .= '</div>';
						}

						// 버튼 리스트
						$html .= '<div class="btn-group button-list">';

						foreach ($buttons as $button) {
							$btn_name = $button['name'] ?? '';
							$btn_url = $button['url'] ?? '';

							if (!empty($btn_name)) {
								$display_url = !empty($btn_url) ? htmlspecialchars($btn_url) : '#';
								$html .= '<a href="' . $display_url . '" class="btn btn-lg fw-semibold px-4 py-2"><span>' . htmlspecialchars($btn_name) . '</span><i class="bi bi-chevron-right"></i></a>';
							}
						}

						$html .= '</div>';
						$html .= '</div>';
						$html .= '</div>';
						$html .= '</div>';
						$html .= '</section>';
					}
					break;


				/**
				 * 파일 위치: application/controllers/api/Homepage_api.php
				 * 역할: waniLatestImageSlide 케이스 수정 - 참조 변수 정리
				 */

				case 'waniLatestImageSlide':
					$title = $data_content['title'] ?? '';
					$subtitle = $data_content['subtitle'] ?? '';
					$board_menu_ids = $data_content['board_menu_ids'] ?? [];
					$display_count = $data_content['display_count'] ?? '3';
					$show_board_name = $data_content['show_board_name'] ?? true;
					$show_title = $data_content['show_title'] ?? true;

					if (!empty($board_menu_ids) && is_array($board_menu_ids) && !empty($org_code)) {
						$all_posts = [];

						foreach ($board_menu_ids as $board_menu_id) {
							$posts = $this->Homepage_api_model->get_image_board_list($org_code, $board_menu_id, 10);

							// 게시판 정보 추가
							foreach ($posts as &$post) {
								$menu_info = $this->Homepage_api_model->get_menu_info($org_code, $board_menu_id);
								$post['board_name'] = $menu_info ? $menu_info['menu_name'] : '';
								$post['menu_id'] = $board_menu_id;
							}
							unset($post); // ← 참조 변수 정리 (중요!)

							$all_posts = array_merge($all_posts, $posts);
						}

						// 날짜순 정렬 (최신순)
						usort($all_posts, function($a, $b) {
							return strtotime($b['reg_date']) - strtotime($a['reg_date']);
						});

						// 최대 10개로 제한
						$all_posts = array_slice($all_posts, 0, 10);

						if (!empty($all_posts)) {
							$slider_id = 'image-slider-' . uniqid();

							$html .= '<section>';
							$html .= '<div class="wani-image-slide-block">';

							// 타이틀과 서브타이틀 렌더링
							if (!empty($title) || !empty($subtitle)) {
								$html .= '<div class="image-slide-header text-center mb-4">';

								if (!empty($title)) {
									$html .= '<h4 class="image-slide-main-title mb-2">' . htmlspecialchars($title) . '</h4>';
								}

								if (!empty($subtitle)) {
									$html .= '<h6 class="image-slide-subtitle text-muted mt-3 mb-5">' . nl2br(htmlspecialchars($subtitle)) . '</h6>';
								}

								$html .= '</div>';
							}

							$html .= '<div id="' . $slider_id . '" class="image-slide-container" data-slides-to-show="' . $display_count . '">';

							foreach ($all_posts as $post) {  // ← $index => $post가 아닌 $post만 사용
								$thumbnail_url = $this->get_first_image_thumbnail($post['file_path']);
								$board_title = htmlspecialchars($post['board_title']);
								$board_name = htmlspecialchars($post['board_name']);
								$post_link = '/board/' . $post['menu_id'] . '/' . $post['idx'];

								// 게시판 ID 기반 색상 생성
								$badge_color = $this->get_board_badge_color($post['menu_id']);

								$html .= '<div class="image-slide-item">';
								$html .= '<a href="' . $post_link . '" class="image-slide-link">';

								if (!empty($thumbnail_url)) {
									$html .= '<div class="image-thumbnail-wrapper">';
									$html .= '<img src="' . $thumbnail_url . '" alt="' . $board_title . '" class="image-thumbnail">';
									$html .= '<div class="image-overlay">';
									$html .= '</div>';
									$html .= '</div>';
								}

								if ($show_board_name || $show_title) {
									$html .= '<div class="image-slide-info">';

									if ($show_board_name && !empty($board_name)) {
										$html .= '<span class="image-board-name badge" style="background-color: ' . $badge_color . '; color: #fff;">' . $board_name . '</span>';
									}

									if ($show_title) {
										$html .= '<div class="image-post-title">' . $board_title . '</div>';
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
			// 메뉴 정보 추가
			if (isset($board_detail['menu_id'])) {
				$menu_info = $this->Homepage_api_model->get_menu_info_by_id($org_code, $board_detail['menu_id']);
				if ($menu_info) {
					$board_detail['menu_name'] = $menu_info['menu_name'] ?? '';
					$board_detail['category_name'] = $menu_info['category_name'] ?? '';
					$board_detail['parent_menu_name'] = $menu_info['parent_menu_name'] ?? '';
				}
			}

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


	/**
	 * 파일 위치: application/controllers/api/Homepage_api.php
	 * 역할: get_first_image_thumbnail 함수 수정 - 전체 URL 반환
	 */

	private function get_first_image_thumbnail($file_path)
	{
		if (empty($file_path)) {
			return '';
		}

		try {
			$files = json_decode($file_path, true);

			if (!is_array($files) || count($files) === 0) {
				return '';
			}

			// 첫 번째 이미지 파일 찾기
			foreach ($files as $file) {
				// type이 "image" 또는 "image/"로 시작하는지 체크
				if (isset($file['type']) && ($file['type'] === 'image' || strpos($file['type'], 'image/') === 0)) {
					// 썸네일이 있으면 썸네일 반환, 없으면 원본 반환
					if (!empty($file['thumb_path'])) {
						// 상대 경로를 전체 URL로 변환
						return base_url(ltrim($file['thumb_path'], '/'));
					} else if (!empty($file['path'])) {
						// 상대 경로를 전체 URL로 변환
						return base_url(ltrim($file['path'], '/'));
					}
				}
			}

			return '';
		} catch (Exception $e) {
			log_message('error', '이미지 썸네일 추출 실패: ' . $e->getMessage());
			return '';
		}
	}






	/**
	 * 파일 위치: application/controllers/api/Homepage_api.php
	 * 역할: 작성자 회원 확인 API
	 * POST /api/homepage_api/verify_writer
	 */
	public function verify_writer()
	{
		$input = json_decode(file_get_contents('php://input'), true);

		$org_code = isset($input['org_code']) ? $input['org_code'] : '';
		$member_name = isset($input['member_name']) ? trim($input['member_name']) : '';
		$member_phone = isset($input['member_phone']) ? trim($input['member_phone']) : '';

		if (empty($org_code) || empty($member_name) || empty($member_phone)) {
			echo json_encode([
				'success' => false,
				'message' => '필수 정보가 누락되었습니다.',
				'data' => null
			]);
			return;
		}

		$result = $this->Homepage_api_model->verify_member($org_code, $member_name, $member_phone);

		echo json_encode($result);
	}

	/**
	 * 파일 위치: application/controllers/api/Homepage_api.php
	 * 역할: 게시글 저장 API (프론트엔드용)
	 * POST /api/homepage_api/save_board
	 */
	public function save_board()
	{
		$input = json_decode(file_get_contents('php://input'), true);

		$org_code = isset($input['org_code']) ? $input['org_code'] : '';
		$menu_id = isset($input['menu_id']) ? $input['menu_id'] : '';
		$board_title = isset($input['board_title']) ? trim($input['board_title']) : '';
		$board_content = isset($input['board_content']) ? trim($input['board_content']) : '';
		$writer_name = isset($input['writer_name']) ? trim($input['writer_name']) : '';
		$writer_phone = isset($input['writer_phone']) ? trim($input['writer_phone']) : '';
		$youtube_url = isset($input['youtube_url']) ? trim($input['youtube_url']) : '';
		$file_path = isset($input['file_path']) ? $input['file_path'] : '';

		if (empty($org_code) || empty($menu_id) || empty($board_title) || empty($board_content) || empty($writer_name)) {
			echo json_encode([
				'success' => false,
				'message' => '필수 정보가 누락되었습니다.',
				'data' => null
			]);
			return;
		}

		// 조직 ID 조회
		$org_id = $this->Homepage_api_model->get_org_id_by_code($org_code);
		if ($org_id === false) {
			echo json_encode([
				'success' => false,
				'message' => '조직 정보를 찾을 수 없습니다.',
				'data' => null
			]);
			return;
		}

		// 게시글 데이터
		$data = [
			'org_id' => $org_id,
			'menu_id' => $menu_id,
			'board_title' => $board_title,
			'board_content' => $board_content,
			'writer_name' => $writer_name,
			'youtube_url' => $youtube_url ? $youtube_url : null,
			'file_path' => $file_path ? $file_path : null
		];

		$result = $this->Homepage_api_model->save_board($data);

		if ($result) {
			echo json_encode([
				'success' => true,
				'message' => '게시글이 등록되었습니다.',
				'data' => null
			]);
		} else {
			echo json_encode([
				'success' => false,
				'message' => '게시글 저장에 실패했습니다.',
				'data' => null
			]);
		}
	}


	/**
	 * 파일 위치: application/controllers/api/Homepage_api.php
	 * 역할: 프론트엔드용 파일 업로드 API (CORS 지원)
	 * POST /api/homepage_api/upload_file
	 */
	public function upload_file()
	{
		// OPTIONS 요청 처리 (Preflight)
		if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
			exit(0);
		}

		$org_code = $this->input->post('org_code');

		if (!$org_code) {
			echo json_encode([
				'success' => false,
				'message' => '조직 코드가 누락되었습니다.'
			]);
			return;
		}

		// org_code로 org_id 조회
		$org_id = $this->Homepage_api_model->get_org_id_by_code($org_code);

		if ($org_id === false) {
			echo json_encode([
				'success' => false,
				'message' => '조직 정보를 찾을 수 없습니다.'
			]);
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
			$original_name = $upload_data['orig_name'];
			$file_ext = strtolower($upload_data['file_ext']);

			// 파일 타입 결정
			$image_extensions = ['.jpg', '.jpeg', '.png', '.gif'];
			$is_image = in_array($file_ext, $image_extensions);
			$file_type = $is_image ? 'image' : 'document';

			// 상대 경로
			$file_path = "/uploads/homepage/{$org_id}/{$year}/{$month}/{$day}/{$file_name}";
			$file_url = "https://wani.im" . $file_path;
			$thumb_file_path = null;
			$thumb_file_url = null;

			// 이미지인 경우 썸네일 생성
			if ($is_image) {
				$source_path = $upload_path . $file_name;
				$thumb_file = $thumb_path . $file_name;

				if ($this->create_thumbnail($source_path, $thumb_file, 400, 400)) {
					$thumb_file_path = "/uploads/homepage/{$org_id}/{$year}/{$month}/{$day}/thumb/{$file_name}";
					$thumb_file_url = "https://wani.im" . $thumb_file_path;
				}
			}

			echo json_encode([
				'success' => true,
				'file_info' => [
					'name' => $file_name,
					'original_name' => $original_name,
					'path' => $file_path,
					'url' => $file_url,
					'thumb_path' => $thumb_file_path,
					'thumb_url' => $thumb_file_url,
					'size' => $upload_data['file_size'] * 1024,
					'type' => $file_type
				]
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
	 * 파일 위치: application/controllers/api/Homepage_api.php
	 * 역할: 썸네일 생성 함수
	 */
	private function create_thumbnail($source_path, $thumb_path, $width = 400, $height = 400)
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
				log_message('error', '썸네일 생성 실패: ' . $error);
				return false;
			}

			return true;
		} catch (Exception $e) {
			log_message('error', '썸네일 생성 중 오류 발생: ' . $e->getMessage());
			return false;
		}
	}

}
