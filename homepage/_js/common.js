/**
 * 파일 위치: /var/www/wani/public/homepage/_js/common.js
 * 역할: 게시판 URL 라우팅 및 기능 개선
 */

const API_BASE_URL = 'https://wani.im/api/homepage';
let orgInfo = null;
let menuData = [];

// HTML 이스케이프 함수
function escapeHtml(text) {
	if (!text) return '';
	const map = {
		'&': '&amp;',
		'<': '&lt;',
		'>': '&gt;',
		'"': '&quot;',
		"'": '&#039;'
	};
	return String(text).replace(/[&<>"']/g, m => map[m]);
}

// 에러 표시 함수
function showError(message) {
	const mainContent = document.getElementById('mainContent');
	if (mainContent) {
		mainContent.innerHTML = `
			<div class="alert alert-danger" role="alert">
				<h4 class="alert-heading">오류 발생</h4>
				<p>${message}</p>
				<hr>
				<p class="mb-0">브라우저 콘솔(F12)을 확인하여 자세한 오류 내용을 확인하세요.</p>
			</div>
		`;
		mainContent.classList.remove('loading');
	}
}

// 조직 정보 로드
async function loadOrgInfo() {
	try {
		console.log('조직 정보 로드 시작:', ORG_CODE);
		const response = await fetch(`${API_BASE_URL}/org/${ORG_CODE}`);
		const result = await response.json();

		if (result.success && result.data) {
			orgInfo = result.data;
			applyOrgInfo(orgInfo);
			return orgInfo;
		} else {
			console.error('조직 정보 조회 실패:', result.message);
			showError('조직 정보를 찾을 수 없습니다: ' + (result.message || '알 수 없는 오류'));
			return null;
		}
	} catch (error) {
		console.error('조직 정보 로드 실패:', error);
		showError('조직 정보를 불러오는 중 오류가 발생했습니다: ' + error.message);
		return null;
	}
}

// 조직 정보 적용
function applyOrgInfo(info) {
	const setting = info.homepage_setting || {};
	const homepageName = setting.homepage_name || info.org_name;

	document.title = homepageName;

	const nameElements = document.querySelectorAll('#homepageName, #footerOrgName, #footerCopyright');
	nameElements.forEach(el => {
		if (el) el.textContent = homepageName;
	});

	const codeElement = document.getElementById('footerOrgCode');
	if (codeElement) codeElement.textContent = info.org_code;

	const yearElement = document.getElementById('currentYear');
	if (yearElement) yearElement.textContent = new Date().getFullYear();

	const logoArea = document.getElementById('logoArea');
	if (logoArea && (setting.logo1 || setting.logo2)) {
		let logoHtml = '';
		if (setting.logo1) {
			logoHtml += `<img src="https://wani.im${setting.logo1}" alt="Logo 1">`;
		}
		if (setting.logo2) {
			logoHtml += `<img src="https://wani.im${setting.logo2}" alt="Logo 2">`;
		}
		logoArea.innerHTML = logoHtml;
	}
}

// 메뉴 HTML 생성
function generateMenuHtml(menus) {
	let html = '';

	menus.forEach(menu => {
		if (!menu.parent_id) {
			const hasChildren = menu.children && menu.children.length > 0;

			if (hasChildren) {
				html += `
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            ${escapeHtml(menu.name)}
                        </a>
                        <ul class="dropdown-menu">`;

				menu.children.forEach(child => {
					const url = getMenuUrl(child.type, child.id);
					html += `<li><a class="dropdown-item" href="${url}" data-menu-id="${child.id}" data-menu-type="${child.type}">${escapeHtml(child.name)}</a></li>`;
				});

				html += `</ul></li>`;
			} else {
				const url = getMenuUrl(menu.type, menu.id);
				html += `<li class="nav-item"><a class="nav-link" href="${url}" data-menu-id="${menu.id}" data-menu-type="${menu.type}">${escapeHtml(menu.name)}</a></li>`;
			}
		}
	});

	return html;
}

// 메뉴 타입에 따른 URL 생성
function getMenuUrl(menuType, menuId) {



	if (menuType === 'page') {
		return `/page/${menuId}`;
	} else if (menuType === 'board') {
		return `/board/${menuId}/`;
	} else {
		return '#';
	}
}

// 메뉴 데이터 로드
async function loadMenu() {
	try {
		console.log('메뉴 로드 시작');
		const response = await fetch(`${API_BASE_URL}/menu/${ORG_CODE}`);
		const result = await response.json();

		if (result.success && result.data && result.data.length > 0) {
			menuData = result.data;
			const menuHtml = generateMenuHtml(result.data);
			document.getElementById('mainMenu').innerHTML = menuHtml;

			document.querySelectorAll('[data-menu-id]').forEach(link => {
				link.addEventListener('click', (e) => {
					const menuType = e.target.dataset.menuType;

					if (menuType === 'link') {
						e.preventDefault();
						const menuId = e.target.dataset.menuId;
						loadLinkContent(menuId);
					}
				});
			});

			console.log('메뉴 로드 완료');
		} else {
			console.log('메뉴가 없습니다.');
			document.getElementById('mainMenu').innerHTML = '';
		}
		hidePageLoading();
	} catch (error) {
		console.error('메뉴 로드 실패:', error);
		document.getElementById('mainMenu').innerHTML = '';
		hidePageLoading();
	}
}

// 페이지 내용 로드
async function loadPageContent(menuId) {
	try {
		console.log('페이지 로드 시작:', menuId);
		const response = await fetch(`${API_BASE_URL}/page/${ORG_CODE}/${menuId}`);
		const result = await response.json();

		const mainContent = document.getElementById('mainContent');

		if (result.success && result.data) {
			if (result.data.page_content_html) {
				mainContent.innerHTML = result.data.page_content_html;
			} else if (result.data.page_content) {
				mainContent.innerHTML = result.data.page_content;
			} else {
				mainContent.innerHTML = '<div class="text-center py-5"><p class="text-muted">페이지 내용이 없습니다.</p></div>';
			}

			mainContent.classList.add('fade-in');
			hidePageLoading();
		} else {
			mainContent.innerHTML = '<div class="text-center py-5"><p class="text-muted">페이지 내용이 없습니다.</p></div>';
			hidePageLoading();
		}
	} catch (error) {
		console.error('페이지 로드 실패:', error);
		const mainContent = document.getElementById('mainContent');
		mainContent.innerHTML = '<div class="text-center py-5"><p class="text-danger">페이지를 불러오는 중 오류가 발생했습니다.</p></div>';
		hidePageLoading();
	}
}

// 링크 처리
async function loadLinkContent(menuId) {
	try {
		const response = await fetch(`${API_BASE_URL}/link/${ORG_CODE}/${menuId}`);
		const result = await response.json();

		if (result.success && result.data && result.data.link_url) {
			const target = result.data.link_target || '_self';
			window.open(result.data.link_url, target);
		} else {
			alert('링크 정보를 찾을 수 없습니다.');
		}
		hidePageLoading();
	} catch (error) {
		console.error('링크 로드 실패:', error);
		alert('링크를 불러오는 중 오류가 발생했습니다.');
		hidePageLoading();
	}
}


// 게시판 목록 로드
async function loadBoardList(menuId, page = 1, searchKeyword = '', viewType = 'list') {
	try {
		// 레이아웃에 따라 limit 설정
		let limit = 10; // list, article 기본값
		if (viewType === 'gallery') {
			limit = 12;
		}

		let url = `${API_BASE_URL}/board/${ORG_CODE}/${menuId}?page=${page}&limit=${limit}`;
		if (searchKeyword) {
			url += `&search_keyword=${encodeURIComponent(searchKeyword)}`;
		}

		const response = await fetch(url);
		const result = await response.json();

		const mainContent = document.getElementById('mainContent');
		mainContent.classList.remove('loading');

		if (result.success) {
			// 메뉴 정보 가져오기
			const menuInfo = getMenuInfo(menuId);
			const menuName = menuInfo ? menuInfo.name : '게시판';
			const categoryName = menuInfo ? menuInfo.category : '';

			let html = '<div class="board-container fade-in">';
			html += '<div class="container">';

			// Breadcrumb 생성
			html += '<nav aria-label="breadcrumb" class="mt-5">';
			html += '<ol class="breadcrumb">';
			html += '<li class="breadcrumb-item"><a href="/"><i class="bi bi-house-door-fill"></i></a></li>';
			if (categoryName) {
				html += `<li class="breadcrumb-item"><a href="#">${escapeHtml(categoryName)}</a></li>`;
			}
			html += `<li class="breadcrumb-item active" aria-current="page">${escapeHtml(menuName)}</li>`;
			html += '</ol>';
			html += '</nav>';

			// 타이틀과 검색, 레이아웃 전환, 글쓰기 버튼
			html += '<div class="d-flex justify-content-between align-items-center mt-2 mb-3">';
			html += `<h4 class="mb-0">${escapeHtml(menuName)}</h4>`;
			html += '<div class="d-flex gap-2">';
			html += '<div class="input-group" style="width: 300px;">';
			html += `<input type="text" class="form-control" id="boardSearchInput" placeholder="제목 또는 작성자 검색" value="${escapeHtml(searchKeyword)}">`;
			html += '<button class="btn btn-outline-secondary" type="button" id="boardSearchBtn">검색</button>';
			html += '</div>';
			html += '<div class="btn-group" role="group">';
			html += `<button type="button" class="btn btn-outline-secondary ${viewType === 'list' ? 'active' : ''}" data-view="list" title="리스트"><i class="bi bi-list-ul"></i></button>`;
			html += `<button type="button" class="btn btn-outline-secondary ${viewType === 'gallery' ? 'active' : ''}" data-view="gallery" title="갤러리"><i class="bi bi-grid-3x3-gap"></i></button>`;
			html += `<button type="button" class="btn btn-outline-secondary ${viewType === 'article' ? 'active' : ''}" data-view="article" title="아티클"><i class="bi bi-card-text"></i></button>`;
			html += '</div>';
			html += `<a href="/board/${menuId}/write" class="btn btn-primary">글쓰기</a>`;
			html += '</div>';
			html += '</div>';

			if (result.data && result.data.length > 0) {
				// 레이아웃 타입에 따라 다른 렌더링
				if (viewType === 'list') {
					html += renderListView(result.data, result.total, page, menuId, limit);
				} else if (viewType === 'gallery') {
					html += renderGalleryView(result.data, menuId);
				} else if (viewType === 'article') {
					html += renderArticleView(result.data, menuId);
				}

				// 페이징
				if (result.total > limit) {
					html += renderPagination(result.total, page, limit);
				}
			} else {
				html += '<div class="text-center py-5"><p class="text-muted">등록된 게시글이 없습니다.</p></div>';
			}

			html += '</div></div>';
			mainContent.innerHTML = html;

			// 검색 버튼 이벤트 바인딩
			document.getElementById('boardSearchBtn')?.addEventListener('click', () => {
				const keyword = document.getElementById('boardSearchInput').value.trim();
				loadBoardList(menuId, 1, keyword, viewType);
			});

			// 검색 입력창 엔터키 이벤트
			document.getElementById('boardSearchInput')?.addEventListener('keypress', (e) => {
				if (e.key === 'Enter') {
					const keyword = document.getElementById('boardSearchInput').value.trim();
					loadBoardList(menuId, 1, keyword, viewType);
				}
			});

			// 레이아웃 전환 버튼 이벤트
			document.querySelectorAll('[data-view]').forEach(btn => {
				btn.addEventListener('click', (e) => {
					const newViewType = e.currentTarget.dataset.view;
					const currentKeyword = document.getElementById('boardSearchInput')?.value.trim() || '';
					loadBoardList(menuId, 1, currentKeyword, newViewType);
				});
			});

			// 페이지네이션 클릭 이벤트
			document.querySelectorAll('.pagination .page-link').forEach(link => {
				link.addEventListener('click', (e) => {
					e.preventDefault();
					const targetPage = parseInt(e.target.dataset.page);
					if (targetPage) {
						const currentKeyword = document.getElementById('boardSearchInput')?.value.trim() || '';
						loadBoardList(menuId, targetPage, currentKeyword, viewType);
					}
				});
			});
		} else {
			mainContent.innerHTML = '<div class="text-center py-5"><p class="text-muted">게시판을 찾을 수 없습니다.</p></div>';
		}
		hidePageLoading();
	} catch (error) {
		console.error('게시판 로드 실패:', error);
		document.getElementById('mainContent').innerHTML = '<div class="text-center py-5"><p class="text-danger">게시판을 불러오는 중 오류가 발생했습니다.</p></div>';
		hidePageLoading();
	}
}

// 리스트 뷰 렌더링
function renderListView(data, total, page, menuId, limit) {
	let html = '<div class="table-responsive"><table class="table table-hover">';
	html += '<thead><tr><th style="width: 80px;">번호</th><th>제목</th><th style="width: 100px;">작성자</th><th style="width: 80px;">조회수</th><th style="width: 120px;">작성일</th></tr></thead>';
	html += '<tbody>';

	data.forEach((item, index) => {
		const num = total - ((page - 1) * limit) - index;
		const date = new Date(item.reg_date).toLocaleDateString('ko-KR');
		html += `<tr style="cursor:pointer" onclick="window.location.href='/board/${menuId}/${item.idx}'">
			<td>${num}</td>
			<td class="text-start">${escapeHtml(item.board_title)}</td>
			<td>${escapeHtml(item.writer_name || '')}</td>
			<td>${item.view_count}</td>
			<td>${date}</td>
		</tr>`;
	});

	html += '</tbody></table></div>';
	return html;
}

// 갤러리 뷰 렌더링
function renderGalleryView(data, menuId) {
	let html = '<div class="row g-3">';

	data.forEach(item => {
		console.log('이미지-->' + item.file_path);
		const thumbnail = getFirstImageFromFiles(item.file_path);
		console.log('썸네일 URL-->' + thumbnail);
		const date = new Date(item.reg_date).toLocaleDateString('ko-KR');

		html += '<div class="col-6 col-md-4 col-lg-3">';
		html += `<div class="card h-100 shadow-sm" style="cursor:pointer" onclick="window.location.href='/board/${menuId}/${item.idx}'">`;

		if (thumbnail) {
			html += `<img src="${thumbnail}" class="card-img-top" alt="${escapeHtml(item.board_title)}" style="height: 200px; object-fit: cover;">`;
		} else {
			html += '<div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height: 200px;">';
			html += '<i class="bi bi-image text-muted" style="font-size: 3rem;"></i>';
			html += '</div>';
		}

		html += '<div class="card-body">';
		html += `<h6 class="card-title text-truncate" title="${escapeHtml(item.board_title)}">${escapeHtml(item.board_title)}</h6>`;
		html += '<div class="d-flex justify-content-between align-items-center">';
		html += `<small class="text-muted">${escapeHtml(item.writer_name || '')}</small>`;
		html += `<small class="text-muted">${date}</small>`;
		html += '</div>';
		html += '</div>';
		html += '</div>';
		html += '</div>';
	});

	html += '</div>';
	return html;
}

// 아티클 뷰 렌더링
function renderArticleView(data, menuId) {
	let html = '<div class="row g-3">';

	data.forEach(item => {
		const thumbnail = getFirstImageFromFiles(item.file_path);
		const date = new Date(item.reg_date).toLocaleDateString('ko-KR');
		const content = item.board_content ? stripHtml(item.board_content).substring(0, 150) : '';

		html += '<div class="col-12">';
		html += `<div class="card shadow-sm" style="cursor:pointer" onclick="window.location.href='/board/${menuId}/${item.idx}'">`;
		html += '<div class="card-body">';
		html += '<div class="row g-3">';

		if (thumbnail) {
			html += '<div class="col-md-3">';
			html += `<img src="${thumbnail}" class="img-fluid rounded" alt="${escapeHtml(item.board_title)}" style="width: 100%; height: 150px; object-fit: cover;">`;
			html += '</div>';
			html += '<div class="col-md-9">';
		} else {
			html += '<div class="col-12">';
		}

		html += `<h5 class="card-title">${escapeHtml(item.board_title)}</h5>`;
		if (content) {
			html += `<p class="card-text text-muted">${escapeHtml(content)}...</p>`;
		}
		html += '<div class="d-flex justify-content-between align-items-center mt-2">';
		html += `<small class="text-muted">작성자: ${escapeHtml(item.writer_name || '')}</small>`;
		html += `<small class="text-muted">조회수: ${item.view_count} | ${date}</small>`;
		html += '</div>';
		html += '</div>';
		html += '</div>';
		html += '</div>';
		html += '</div>';
		html += '</div>';
	});

	html += '</div>';
	return html;
}

// 페이지네이션 렌더링
function renderPagination(total, currentPage, limit) {
	const totalPages = Math.ceil(total / limit);
	let html = '<nav aria-label="게시판 페이지네이션" class="mt-4"><ul class="pagination justify-content-center">';

	// 이전 버튼
	if (currentPage > 1) {
		html += `<li class="page-item"><a class="page-link" href="#" data-page="${currentPage - 1}">이전</a></li>`;
	} else {
		html += '<li class="page-item disabled"><span class="page-link">이전</span></li>';
	}

	// 페이지 번호
	const startPage = Math.max(1, currentPage - 2);
	const endPage = Math.min(totalPages, currentPage + 2);

	if (startPage > 1) {
		html += `<li class="page-item"><a class="page-link" href="#" data-page="1">1</a></li>`;
		if (startPage > 2) {
			html += '<li class="page-item disabled"><span class="page-link">...</span></li>';
		}
	}

	for (let i = startPage; i <= endPage; i++) {
		if (i === currentPage) {
			html += `<li class="page-item active"><span class="page-link">${i}</span></li>`;
		} else {
			html += `<li class="page-item"><a class="page-link" href="#" data-page="${i}">${i}</a></li>`;
		}
	}

	if (endPage < totalPages) {
		if (endPage < totalPages - 1) {
			html += '<li class="page-item disabled"><span class="page-link">...</span></li>';
		}
		html += `<li class="page-item"><a class="page-link" href="#" data-page="${totalPages}">${totalPages}</a></li>`;
	}

	// 다음 버튼
	if (currentPage < totalPages) {
		html += `<li class="page-item"><a class="page-link" href="#" data-page="${currentPage + 1}">다음</a></li>`;
	} else {
		html += '<li class="page-item disabled"><span class="page-link">다음</span></li>';
	}

	html += '</ul></nav>';
	return html;
}

// 파일 경로에서 첫 번째 이미지 추출 (썸네일 우선) - 전체 URL 반환
// 파일 경로에서 첫 번째 이미지 추출 (썸네일 우선) - 전체 URL 반환
function getFirstImageFromFiles(filePath) {
	if (!filePath) return null;

	try {
		let files;

		// 이미 객체인 경우와 문자열인 경우 모두 처리
		if (typeof filePath === 'string') {
			files = JSON.parse(filePath);
		} else {
			files = filePath;
		}

		if (Array.isArray(files) && files.length > 0) {
			for (const file of files) {
				// type이 "image"로 시작하는지 확인
				if (file.type && (file.type === 'image' || file.type.startsWith('image/'))) {
					let imagePath = null;

					// 썸네일이 있으면 썸네일 사용, 없으면 원본 사용
					if (file.thumb_path) {
						imagePath = file.thumb_path;
					} else if (file.path) {
						imagePath = file.path;
					}

					if (imagePath) {
						// 상대 경로를 전체 URL로 변환
						// 이미 http로 시작하면 그대로 반환
						if (imagePath.startsWith('http://') || imagePath.startsWith('https://')) {
							return imagePath;
						}

						// 슬래시로 시작하지 않으면 추가
						if (!imagePath.startsWith('/')) {
							imagePath = '/' + imagePath;
						}

						// 기본 도메인(wani.im) 사용
						const protocol = window.location.protocol; // http: 또는 https:
						const baseDomain = 'wani.im';

						return protocol + '//' + baseDomain + imagePath;
					}
				}
			}
		}
	} catch (e) {
		console.error('파일 정보 파싱 실패:', e, 'filePath:', filePath);
	}

	return null;
}

// HTML 태그 제거
function stripHtml(html) {
	const tmp = document.createElement('div');
	tmp.innerHTML = html;
	return tmp.textContent || tmp.innerText || '';
}

// 메뉴 정보 조회 헬퍼 함수
function getMenuInfo(menuId) {
	if (!menuData || menuData.length === 0) {
		return null;
	}

	// 메뉴 데이터에서 해당 menuId 찾기
	for (const menu of menuData) {
		if (menu.id === menuId) {
			return {
				name: menu.name,
				category: menu.menu_category || ''
			};
		}

		// 하위 메뉴가 있는 경우
		if (menu.children && menu.children.length > 0) {
			for (const child of menu.children) {
				if (child.id === menuId) {
					return {
						name: child.name,
						category: menu.name
					};
				}
			}
		}
	}

	return null;
}






// 메뉴 정보 조회 헬퍼 함수
function getMenuInfo(menuId) {
	if (!menuData || menuData.length === 0) {
		return null;
	}

	// 메뉴 데이터에서 해당 menuId 찾기
	for (const menu of menuData) {
		if (menu.id === menuId) {
			return {
				name: menu.name,
				category: menu.menu_category || ''
			};
		}

		// 하위 메뉴가 있는 경우
		if (menu.children && menu.children.length > 0) {
			for (const child of menu.children) {
				if (child.id === menuId) {
					return {
						name: child.name,
						category: menu.name
					};
				}
			}
		}
	}

	return null;
}



// EditorJS JSON을 HTML로 변환하는 함수
function parseEditorJSToHTML(editorData) {
	try {
		let parsedData;

		// JSON 문자열인 경우 파싱
		if (typeof editorData === 'string') {
			parsedData = JSON.parse(editorData);
		} else {
			parsedData = editorData;
		}

		// blocks 배열이 없으면 빈 문자열 반환
		if (!parsedData || !parsedData.blocks || !Array.isArray(parsedData.blocks)) {
			return '';
		}

		let html = '';

		parsedData.blocks.forEach(block => {
			switch (block.type) {
				case 'header':
					const level = block.data.level || 2;
					html += `<h${level}>${escapeHtml(block.data.text || '')}</h${level}>`;
					break;

				case 'paragraph':
					html += `<p>${block.data.text || ''}</p>`;
					break;

				case 'list':
					const listTag = block.data.style === 'ordered' ? 'ol' : 'ul';
					html += `<${listTag}>`;
					if (block.data.items && Array.isArray(block.data.items)) {
						block.data.items.forEach(item => {
							html += `<li>${item}</li>`;
						});
					}
					html += `</${listTag}>`;
					break;

				case 'quote':
					html += `<blockquote class="blockquote">`;
					html += `<p>${block.data.text || ''}</p>`;
					if (block.data.caption) {
						html += `<footer class="blockquote-footer">${escapeHtml(block.data.caption)}</footer>`;
					}
					html += `</blockquote>`;
					break;

				case 'code':
					html += `<pre><code>${escapeHtml(block.data.code || '')}</code></pre>`;
					break;

				case 'image':
					html += `<figure class="figure w-100">`;
					if (block.data.file && block.data.file.url) {
						html += `<img src="${escapeHtml(block.data.file.url)}" class="figure-img img-fluid rounded" alt="${escapeHtml(block.data.caption || '')}">`;
					}
					if (block.data.caption) {
						html += `<figcaption class="figure-caption">${escapeHtml(block.data.caption)}</figcaption>`;
					}
					html += `</figure>`;
					break;

				case 'embed':
					if (block.data.service === 'youtube' && block.data.embed) {
						html += `<div class="ratio ratio-16x9 mb-3">`;
						html += `<iframe src="${escapeHtml(block.data.embed)}" allowfullscreen></iframe>`;
						html += `</div>`;
					} else if (block.data.embed) {
						html += `<div class="embed-responsive mb-3">`;
						html += `<iframe src="${escapeHtml(block.data.embed)}" class="w-100" style="min-height: 400px;"></iframe>`;
						html += `</div>`;
					}
					break;

				case 'table':
					html += `<div class="table-responsive"><table class="table table-bordered">`;
					if (block.data.content && Array.isArray(block.data.content)) {
						block.data.content.forEach((row, rowIndex) => {
							const tag = block.data.withHeadings && rowIndex === 0 ? 'th' : 'td';
							html += `<tr>`;
							row.forEach(cell => {
								html += `<${tag}>${cell || ''}</${tag}>`;
							});
							html += `</tr>`;
						});
					}
					html += `</table></div>`;
					break;

				case 'delimiter':
					html += `<hr class="my-4">`;
					break;

				case 'raw':
					html += block.data.html || '';
					break;

				default:
					console.warn('Unknown EditorJS block type:', block.type);
					break;
			}
		});

		return html;
	} catch (error) {
		console.error('EditorJS 파싱 오류:', error);
		return '<p class="text-danger">내용을 표시할 수 없습니다.</p>';
	}
}

// 게시글 상세 로드
async function loadBoardDetail(menuId, idx) {
	try {
		const response = await fetch(`${API_BASE_URL}/board/detail/${ORG_CODE}/${idx}`);
		const result = await response.json();

		const mainContent = document.getElementById('mainContent');
		mainContent.classList.remove('loading');

		if (result.success && result.data) {
			const item = result.data;
			const date = new Date(item.reg_date).toLocaleDateString('ko-KR');
			const modDate = item.modi_date ? new Date(item.modi_date).toLocaleDateString('ko-KR') : null;

			// EditorJS JSON을 HTML로 변환
			const contentHTML = parseEditorJSToHTML(item.board_content);


			let html = '<div class="board-container fade-in">';
			html += '<div class="container">';
			html += '<div class="board-detail">';
			html += `<h4>${escapeHtml(item.board_title)}</h4>`;
			html += `<div class="mb-3 text-muted d-flex gap-3 flex-wrap">
                <small>작성자: ${escapeHtml(item.writer_name || '')}</small>
                <small>작성일: ${date}</small>
                <small>조회수: ${item.view_count}</small>`;

			if (modDate && item.modifier_name) {
				html += `<small>수정: ${modDate} (${escapeHtml(item.modifier_name)})</small>`;
			}

			html += `</div>`;
			html += '<hr>';
			html += `<div class="board-content py-4">${contentHTML}</div>`;
			html += '<hr>';
			html += '<div class="d-flex gap-2">';
			html += `<a href="/board/${menuId}/" class="btn btn-secondary">목록으로</a>`;
			html += '</div>';
			html += '</div>';
			html += '</div>';
			html += '</div>';

			mainContent.innerHTML = html;
		} else {
			mainContent.innerHTML = '<div class="text-center py-5"><p class="text-danger">게시글을 찾을 수 없습니다.</p></div>';
		}
	} catch (error) {
		console.error('게시글 로드 실패:', error);
		document.getElementById('mainContent').innerHTML = '<div class="text-center py-5"><p class="text-danger">게시글을 불러오는 중 오류가 발생했습니다.</p></div>';
	}
}

// 게시글 작성 폼 표시
function showBoardWriteForm(menuId) {
	const mainContent = document.getElementById('mainContent');
	mainContent.classList.remove('loading');

	let html = '<div class="board-write fade-in">';
	html += '<h4>게시글 작성</h4>';
	html += '<hr>';
	html += '<form id="boardWriteForm">';
	html += '<div class="mb-3">';
	html += '<label for="boardTitle" class="form-label">제목</label>';
	html += '<input type="text" class="form-control" id="boardTitle" required>';
	html += '</div>';
	html += '<div class="mb-3">';
	html += '<label for="boardContent" class="form-label">내용</label>';
	html += '<textarea class="form-control" id="boardContent" rows="10" required></textarea>';
	html += '</div>';
	html += '<div class="mb-3">';
	html += '<label for="writerName" class="form-label">작성자</label>';
	html += '<input type="text" class="form-control" id="writerName" required>';
	html += '</div>';
	html += '<hr>';
	html += '<div class="d-flex gap-2">';
	html += `<a href="/board/${menuId}/" class="btn btn-secondary">취소</a>`;
	html += '<button type="submit" class="btn btn-primary">등록</button>';
	html += '</div>';
	html += '</form>';
	html += '</div>';

	mainContent.innerHTML = html;

	// 폼 제출 이벤트
	document.getElementById('boardWriteForm').addEventListener('submit', async (e) => {
		e.preventDefault();

		const title = document.getElementById('boardTitle').value.trim();
		const content = document.getElementById('boardContent').value.trim();
		const writerName = document.getElementById('writerName').value.trim();

		if (!title || !content || !writerName) {
			alert('모든 항목을 입력해주세요.');
			return;
		}

		// TODO: 실제 저장 API 구현 필요
		alert('게시글 작성 기능은 준비 중입니다.');
		// 임시로 목록으로 이동
		window.location.href = `/board/${menuId}/`;
	});
}

// URL 라우팅 처리
function handleRouting() {
	const path = window.location.pathname;
	const searchParams = new URLSearchParams(window.location.search);
	console.log('현재 경로:', path);

	const mainContent = document.getElementById('mainContent');
	if (mainContent) {
		mainContent.classList.add('loading');
	}

	// 경로 분석
	if (path === '/' || path === '') {
		// 홈 - 메인 페이지 로드
		loadPageContent('main');
	} else if (path.startsWith('/page/')) {
		$('header').addClass('fixed');
		// 페이지 컨텐츠
		const menuId = path.replace('/page/', '').replace(/\/$/, '');
		loadPageContent(menuId);
	} else if (path.startsWith('/board/')) {
		$('header').addClass('fixed');
		// 게시판 관련 라우팅
		const pathParts = path.replace('/board/', '').replace(/\/$/, '').split('/');
		const menuId = pathParts[0];

		if (pathParts.length === 1 || pathParts[1] === '') {
			// 게시판 목록
			const page = parseInt(searchParams.get('page')) || 1;
			loadBoardList(menuId, page);
		} else if (pathParts[1] === 'write') {
			// 글쓰기
			showBoardWriteForm(menuId);
		} else if (/^\d+$/.test(pathParts[1])) {
			// 게시글 상세 (숫자인 경우)
			const idx = parseInt(pathParts[1]);
			loadBoardDetail(menuId, idx);
		} else {
			// 잘못된 경로
			if (mainContent) {
				mainContent.innerHTML = '<div class="text-center py-5"><p class="text-danger">잘못된 경로입니다.</p></div>';
				mainContent.classList.remove('loading');
			}
		}
	} else {
		// 404
		if (mainContent) {
			mainContent.innerHTML = '<div class="text-center py-5"><p class="text-danger">페이지를 찾을 수 없습니다.</p></div>';
			mainContent.classList.remove('loading');
		}
	}
}

// 페이지 초기화
async function initializePage() {
	console.log('=== 홈페이지 초기화 시작 ===');

	if (typeof ORG_CODE === 'undefined') {
		console.error('ORG_CODE가 정의되지 않았습니다!');
		showError('조직 코드를 찾을 수 없습니다.');
		return;
	}

	console.log('ORG_CODE:', ORG_CODE);

	await loadOrgInfo();
	await loadMenu();
	handleRouting();

	console.log('=== 홈페이지 초기화 완료 ===');
}




// popstate 이벤트 처리
window.addEventListener('popstate', function(e) {
	console.log('popstate 이벤트 발생');
	handleRouting();
});

// 페이지 로드 시 초기화
document.addEventListener('DOMContentLoaded', initializePage);


// 페이지 로딩 숨기기 함수
function hidePageLoading() {
	const pageLoading = document.getElementById('pageLoading');
	if (pageLoading) {
		pageLoading.classList.add('fade-out');
		setTimeout(() => {
			pageLoading.style.display = 'none';
		}, 500);
	}
}




