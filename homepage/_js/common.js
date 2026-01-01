/**
 
 * 역할: 게시판 URL 라우팅 및 기능 개선
 */

const API_BASE_URL = 'https://wani.im/api/homepage';
let orgInfo = null;
let menuData = [];

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
function convertEditorJsToHtml(editorData) {
	return parseEditorJSToHTML(editorData);
}

// YouTube URL에서 비디오 ID 추출
function extractYoutubeId(url) {
	if (!url) return null;

	const patterns = [
		/(?:youtube\.com\/watch\?v=|youtu\.be\/)([^&\s]+)/,
		/youtube\.com\/embed\/([^&\s]+)/,
		/youtube\.com\/v\/([^&\s]+)/
	];

	for (const pattern of patterns) {
		const match = url.match(pattern);
		if (match && match[1]) {
			return match[1];
		}
	}

	return null;
}

// 파일 크기를 읽기 쉬운 형식으로 변환
function formatBytes(bytes) {
	if (bytes === 0) return '0 Bytes';
	if (!bytes) return '';

	const k = 1024;
	const sizes = ['Bytes', 'KB', 'MB', 'GB'];
	const i = Math.floor(Math.log(bytes) / Math.log(k));

	return Math.round((bytes / Math.pow(k, i)) * 100) / 100 + ' ' + sizes[i];
}

// YouTube 비디오 ID로 썸네일 URL 생성
function getYoutubeThumbnail(videoId) {
	if (!videoId) return null;
	return `https://img.youtube.com/vi/${videoId}/hqdefault.jpg`;
}

// YouTube URL에서 썸네일 URL 가져오기
function getYoutubeThumbnailFromUrl(youtubeUrl) {
	if (!youtubeUrl) return null;
	const videoId = extractYoutubeId(youtubeUrl);
	return getYoutubeThumbnail(videoId);
}



// 로컬스토리지에서 뷰 타입 설정 가져오기
function getBoardViewType(menuId) {
	try {
		const storageKey = `board_view_types_${ORG_CODE}`;
		const viewTypes = localStorage.getItem(storageKey);

		if (viewTypes) {
			const parsed = JSON.parse(viewTypes);
			return parsed[menuId] || 'list'; // 기본값은 list
		}

		return 'list'; // 기본값
	} catch (error) {
		console.error('뷰 타입 로드 실패:', error);
		return 'list';
	}
}

// 로컬스토리지에 뷰 타입 설정 저장하기
function saveBoardViewType(menuId, viewType) {
	try {
		const storageKey = `board_view_types_${ORG_CODE}`;
		let viewTypes = {};

		// 기존 설정 불러오기
		const existing = localStorage.getItem(storageKey);
		if (existing) {
			viewTypes = JSON.parse(existing);
		}

		// 현재 메뉴의 뷰 타입 저장
		viewTypes[menuId] = viewType;

		// 로컬스토리지에 저장
		localStorage.setItem(storageKey, JSON.stringify(viewTypes));

		console.log(`[뷰 타입 저장] 메뉴: ${menuId}, 타입: ${viewType}`);
	} catch (error) {
		console.error('뷰 타입 저장 실패:', error);
	}
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

	const yearElement = document.getElementById('currentYear');
	if (yearElement) yearElement.textContent = new Date().getFullYear();

	// 헤더 로고 적용
	const logoArea = document.getElementById('logoArea');
	if (logoArea && (setting.logo1 || setting.logo2)) {
		let logoHtml = '';
		if (setting.logo1) {
			logoHtml += `<img src="https://wani.im${setting.logo1}" alt="Logo 1">`;
		}
		logoArea.innerHTML = logoHtml;
	}

	// 푸터 로고 적용
	const footerLogoArea = document.getElementById('footerLogoArea');
	if (footerLogoArea && (setting.logo1 || setting.logo2)) {
		let footerLogoHtml = '';
		if (setting.logo1) {
			footerLogoHtml += `<img src="https://wani.im${setting.logo1}" alt="${homepageName}" style="max-height: 50px;">`;
		}
		if (setting.logo2) {
			footerLogoHtml += `<img src="https://wani.im${setting.logo2}" alt="${homepageName}" style="max-height: 50px; margin-left: 10px;">`;
		}
		footerLogoArea.innerHTML = footerLogoHtml;
	}

	// 푸터 주소 적용
	const footerAddress = document.getElementById('footerAddress');
	if (footerAddress) {
		let addressText = '';
		if (info.org_address) {
			addressText = info.org_address;
			if (info.org_address_detail) {
				addressText += ' ' + info.org_address_detail;
			}
		}
		footerAddress.textContent = addressText;
	}

	// 푸터 전화번호 적용
	const footerPhone = document.getElementById('footerPhone');
	if (footerPhone && info.org_phone) {
		footerPhone.textContent = info.org_phone;
	}


	const footerAddressWrap = document.getElementById('footerAddressWrap');
	if (footerAddressWrap && !info.org_address) {
		footerAddressWrap.style.display = 'none';
	}

	const footerPhoneWrap = document.getElementById('footerPhoneWrap');
	if (footerPhoneWrap && !info.org_phone) {
		footerPhoneWrap.style.display = 'none';
	}

	const footerRepWrap = document.getElementById('footerRepWrap');
	if (footerRepWrap && !info.org_rep) {
		footerRepWrap.style.display = 'none';
	}

	// 푸터 대표자 적용
	const footerRep = document.getElementById('footerRep');
	if (footerRep && info.org_rep) {
		footerRep.textContent = info.org_rep;
	}


}

// 메뉴 HTML 생성
function generateMenuHtml(menus) {
	let html = '';
	menus.forEach(menu => {
		if (!menu.parent_id) {
			const hasChildren = menu.children && menu.children.length > 0;
			if (hasChildren) {
				html += ` <li class="nav-item dropdown"> <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown"> ${escapeHtml(menu.name)} </a> <ul class="dropdown-menu">`;
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
// 메뉴 HTML 생성
function generateMenuHtml2(menus) {
	let html = '';

	// 아코디언의 고유 ID 생성을 위한 index
	menus.forEach((menu, index) => {
		if (!menu.parent_id) {
			const hasChildren = menu.children && menu.children.length > 0;
			const accordionId = `menuAccordion_${index}`;

			if (hasChildren) {
				html += `
                    <div class="accordion-item border-0 ">
                        <h2 class="accordion-header" id="heading${accordionId}">
                            <button class="accordion-button collapsed  text-white py-3 px-3" type="button" data-bs-toggle="collapse" data-bs-target="#collapse${accordionId}" aria-expanded="false" aria-controls="collapse${accordionId}">
                                ${escapeHtml(menu.name)}
                            </button>
                        </h2>
                        <div id="collapse${accordionId}" class="accordion-collapse collapse" aria-labelledby="heading${accordionId}" data-bs-parent="#mainMenu">
                            <div class="accordion-body p-0 ps-3">
                                <ul class="p-0">`;
				menu.children.forEach(child => {
					const url = getMenuUrl(child.type, child.id);
					html += `<li><a class="nav-link py-3 text-white-50" href="${url}" data-menu-id="${child.id}" data-menu-type="${child.type}">${escapeHtml(child.name)}</a></li>`;
				});

				html += `</ul>
                            </div>
                        </div>
                    </div>`;
			} else {
				const url = getMenuUrl(menu.type, menu.id);
				html += `<div class="accordion-item border-0 "><h2 class="accordion-header" id="heading${accordionId}"><button class="collapsed  text-white py-3 px-3" href="${url}" data-menu-id="${menu.id}" data-menu-type="${menu.type}">${escapeHtml(menu.name)}</button></h2></div>`;
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
			const menuHtml2 = generateMenuHtml2(result.data);
			document.getElementById('mainMenu').innerHTML = menuHtml;
			document.getElementById('offcanvasMainMenu').innerHTML = menuHtml2;

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

/**
 * 페이지 콘텐츠 로드
 * @param {string} menuId - 메뉴 ID
 */
async function loadPageContent(menuId) {
	const mainContent = document.getElementById('mainContent');

	try {
		console.log('페이지 로드 시작:', menuId);
		const response = await fetch(`${API_BASE_URL}/page/${ORG_CODE}/${menuId}`);
		const result = await response.json();

		// 1. 초기화
		mainContent.innerHTML = '';
		mainContent.className = '';

		// 2. 최상위 게시판 컨테이너 생성
		const pageContainer = document.createElement('div');
		if (menuId !== 'main' && menuId !== '' && menuId !== null) {
			pageContainer.className = 'page-container sub';
		} else {
			pageContainer.className = 'page-container';
		}



		if (result.success && result.data) {

			if (menuId !== 'main' && menuId !== '' && menuId !== null) {
				const container = document.createElement('div');
				container.className = 'container py-5';


				const pageData = result.data;
				const contentHtml = pageData.page_content_html || pageData.page_content || '';

				// 메뉴 정보 가져오기
				const menuInfo = getMenuInfo(menuId);
				const menuName = menuInfo ? menuInfo.name : (pageData.page_title || '페이지');
				const categoryName = menuInfo ? menuInfo.category : '';

				// Breadcrumb HTML 생성
				let breadcrumbHtml = '<nav aria-label="breadcrumb">';
				breadcrumbHtml += '<ol class="breadcrumb">';
				breadcrumbHtml += '<li class="breadcrumb-item"><a href="/"><i class="bi bi-house-door-fill"></i></a></li>';
				if (categoryName) {
					breadcrumbHtml += `<li class="breadcrumb-item"><a href="#">${escapeHtml(categoryName)}</a></li>`;
				}
				breadcrumbHtml += `<li class="breadcrumb-item active" aria-current="page">${escapeHtml(menuName)}</li>`;
				breadcrumbHtml += '</ol>';
				breadcrumbHtml += '</nav>';

				// 4. HTML 구조 생성 (헤더 + page-body)
				container.innerHTML = `
                <div class="page-header mb-4">
                    <h4 class="mb-0">${escapeHtml(menuName)}</h4>
                    ${breadcrumbHtml}
                </div>

                <div class="page-body">
                    ${contentHtml || '<p class="text-center text-muted">내용이 없습니다.</p>'}
                </div>
            `;

				pageContainer.appendChild(container);
				mainContent.appendChild(pageContainer);
			} else {



				const pageData = result.data;
				const contentHtml = pageData.page_content_html || pageData.page_content || '';

				// 4. HTML 구조 생성 (헤더 + page-body)
				pageContainer.innerHTML = `
                
                    ${contentHtml || '<p class="text-center text-muted">내용이 없습니다.</p>'}
                
                `;

				//pageContainer.appendChild(contentHtml);
				mainContent.appendChild(pageContainer);
			}
			// AOS 애니메이션 새로고침
			setTimeout(() => { if (typeof AOS !== 'undefined') AOS.refresh(); }, 100);

		} else {
			// 데이터가 없는 경우
			container.innerHTML = '<div class="text-center py-5"><p class="text-muted">페이지를 찾을 수 없습니다.</p></div>';
			pageContainer.appendChild(container);
			mainContent.appendChild(pageContainer);
		}

	} catch (error) {
		console.error('로드 오류:', error);
		mainContent.innerHTML = `
            <div class="board-container">
                <div class="container py-5 text-center">
                    <p class="text-danger">데이터를 불러오는 중 오류가 발생했습니다.</p>
                </div>
            </div>`;
	} finally {
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
async function loadBoardList(menuId, page = 1, searchKeyword = '', viewType = null) {
	try {
		// viewType이 지정되지 않았으면 로컬스토리지에서 불러오기
		if (viewType === null) {
			viewType = getBoardViewType(menuId);
		}

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
			html += '<div class="container py-5">';



			html += `<h4 class="mb-0">${escapeHtml(menuName)}</h4>`;
			// Breadcrumb 생성
			html += '<nav aria-label="breadcrumb">';
			html += '<ol class="breadcrumb">';
			html += '<li class="breadcrumb-item"><a href="/"><i class="bi bi-house-door-fill"></i></a></li>';
			if (categoryName) {
				html += `<li class="breadcrumb-item"><a href="#">${escapeHtml(categoryName)}</a></li>`;
			}
			html += `<li class="breadcrumb-item active" aria-current="page">${escapeHtml(menuName)}</li>`;
			html += '</ol>';
			html += '</nav>';

			// 타이틀과 검색, 레이아웃 전환, 글쓰기 버튼
			html += '<div class="d-flex justify-content-between align-items-center py-3">';
			html += '<div class="input-group" style="width: 300px;">';
			html += `<input type="text" class="form-control" id="boardSearchInput" placeholder="제목 또는 작성자 검색" value="${escapeHtml(searchKeyword)}">`;
			html += '<button class="btn btn-outline-secondary" type="button" id="boardSearchBtn">검색</button>';
			html += '</div>';
			html += '<div class="d-flex gap-2">';
			html += `<a href="/board/${menuId}/write" class="btn btn-primary">글쓰기</a>`;
			html += '<div class="btn-group" role="group">';
			html += `<button type="button" class="btn btn-outline-secondary ${viewType === 'list' ? 'active' : ''}" data-view="list" title="리스트"><i class="bi bi-list-ul"></i></button>`;
			html += `<button type="button" class="btn btn-outline-secondary ${viewType === 'gallery' ? 'active' : ''}" data-view="gallery" title="갤러리"><i class="bi bi-grid-3x3-gap"></i></button>`;
			html += `<button type="button" class="btn btn-outline-secondary ${viewType === 'article' ? 'active' : ''}" data-view="article" title="아티클"><i class="bi bi-card-text"></i></button>`;
			html += '</div>';
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

					// 로컬스토리지에 뷰 타입 저장
					saveBoardViewType(menuId, newViewType);

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
	html += '<thead><tr><th style="width: 80px;">번호</th><th>제목</th><th style="width: 100px;">작성자</th><th style="width: 80px;">조회수</th><th style="width: 140px;">작성일</th></tr></thead>';
	html += '<tbody>';

	const currentDate = new Date();

	data.forEach((item, index) => {
		const num = total - ((page - 1) * limit) - index;
		const date = new Date(item.reg_date).toLocaleDateString('ko-KR');

		// 아이콘 생성
		let icons = '';

		// NEW 아이콘 (3일 이내 게시글)
		const regDate = new Date(item.reg_date);
		const diffTime = Math.abs(currentDate - regDate);
		const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
		if (diffDays <= 3) {
			icons += '<span class="badge bg-danger ms-1">NEW</span>';
		}

		// YouTube 아이콘
		if (item.youtube_url) {
			icons += '<i class="bi bi-youtube text-danger ms-1" title="YouTube 동영상"></i>';
		}

		// 첨부파일 아이콘 (이미지 / 일반 파일)
		if (item.file_path) {
			try {
				const files = JSON.parse(item.file_path);
				if (Array.isArray(files) && files.length > 0) {
					const hasImage = files.some(file => file.type && file.type.includes('image'));
					const hasDocument = files.some(file => file.type && !file.type.includes('image'));

					if (hasImage) {
						icons += '<i class="bi bi-image text-primary ms-1" title="이미지 첨부"></i>';
					}

					if (hasDocument) {
						icons += '<i class="bi bi-file-earmark-text text-secondary ms-1" title="문서 첨부"></i>';
					}
				}
			} catch (e) {
				console.error('파일 정보 파싱 실패:', e);
			}
		}

		html += `<tr style="cursor:pointer" onclick="window.location.href='/board/${menuId}/${item.idx}'">
			<td>${num}</td>
			<td class="text-start">${escapeHtml(item.board_title)}${icons}</td>
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
	const currentDate = new Date();

	data.forEach(item => {
		console.log('이미지-->' + item.file_path);
		console.log('YouTube URL-->' + item.youtube_url);

		// 썸네일 우선순위: 첨부 이미지 > YouTube 썸네일
		let thumbnail = getFirstImageFromFiles(item.file_path);

		// 첨부 이미지가 없고 YouTube URL이 있으면 YouTube 썸네일 사용
		if (!thumbnail && item.youtube_url) {
			thumbnail = getYoutubeThumbnailFromUrl(item.youtube_url);
			console.log('YouTube 썸네일-->' + thumbnail);
		}

		const date = new Date(item.reg_date).toLocaleDateString('ko-KR');

		// 아이콘 생성
		let icons = '';

		// NEW 아이콘 (3일 이내 게시글)
		const regDate = new Date(item.reg_date);
		const diffTime = Math.abs(currentDate - regDate);
		const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
		if (diffDays <= 3) {
			icons += '<span class="badge bg-danger ms-1">NEW</span>';
		}

		// YouTube 아이콘
		if (item.youtube_url) {
			icons += '<i class="bi bi-youtube text-danger ms-1" title="YouTube 동영상"></i>';
		}

		// 첨부파일 아이콘 (이미지 / 일반 파일)
		if (item.file_path) {
			try {
				const files = JSON.parse(item.file_path);
				if (Array.isArray(files) && files.length > 0) {
					const hasImage = files.some(file => file.type && file.type.includes('image'));
					const hasDocument = files.some(file => file.type && !file.type.includes('image'));

					if (hasImage) {
						icons += '<i class="bi bi-image text-primary ms-1" title="이미지 첨부"></i>';
					}

					if (hasDocument) {
						icons += '<i class="bi bi-file-earmark-text text-secondary ms-1" title="문서 첨부"></i>';
					}
				}
			} catch (e) {
				console.error('파일 정보 파싱 실패:', e);
			}
		}

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
		html += `<h6 class="card-title" title="${escapeHtml(item.board_title)}">${escapeHtml(item.board_title)}${icons}</h6>`;
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
	const currentDate = new Date();

	data.forEach(item => {
		console.log('이미지-->' + item.file_path);
		console.log('YouTube URL-->' + item.youtube_url);

		// 썸네일 우선순위: 첨부 이미지 > YouTube 썸네일
		let thumbnail = getFirstImageFromFiles(item.file_path);

		// 첨부 이미지가 없고 YouTube URL이 있으면 YouTube 썸네일 사용
		if (!thumbnail && item.youtube_url) {
			thumbnail = getYoutubeThumbnailFromUrl(item.youtube_url);
			console.log('YouTube 썸네일-->' + thumbnail);
		}

		const date = new Date(item.reg_date).toLocaleDateString('ko-KR');

		// 본문 내용 추출 (EditorJS 또는 일반 텍스트)
		let content = '';
		if (item.board_content) {
			try {
				// EditorJS 형식인 경우
				const parsedContent = JSON.parse(item.board_content);
				if (parsedContent.blocks && Array.isArray(parsedContent.blocks)) {
					// 모든 블록의 텍스트 추출
					const textBlocks = parsedContent.blocks
						.filter(block => block.type === 'paragraph' || block.type === 'header')
						.map(block => {
							if (block.data && block.data.text) {
								return stripHtml(block.data.text);
							}
							return '';
						})
						.filter(text => text.length > 0);

					content = textBlocks.join(' ').substring(0, 150);
				} else {
					content = stripHtml(item.board_content).substring(0, 150);
				}
			} catch (e) {
				// JSON 파싱 실패시 일반 텍스트로 처리
				content = stripHtml(item.board_content).substring(0, 150);
			}
		}

		// 아이콘 생성
		let icons = '';

		// NEW 아이콘 (3일 이내 게시글)
		const regDate = new Date(item.reg_date);
		const diffTime = Math.abs(currentDate - regDate);
		const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
		if (diffDays <= 3) {
			icons += '<span class="badge bg-danger ms-1">NEW</span>';
		}

		// YouTube 아이콘
		if (item.youtube_url) {
			icons += '<i class="bi bi-youtube text-danger ms-1" title="YouTube 동영상"></i>';
		}

		// 첨부파일 아이콘 (이미지 / 일반 파일)
		if (item.file_path) {
			try {
				const files = JSON.parse(item.file_path);
				if (Array.isArray(files) && files.length > 0) {
					const hasImage = files.some(file => file.type && file.type.includes('image'));
					const hasDocument = files.some(file => file.type && !file.type.includes('image'));

					if (hasImage) {
						icons += '<i class="bi bi-image text-primary ms-1" title="이미지 첨부"></i>';
					}

					if (hasDocument) {
						icons += '<i class="bi bi-file-earmark-text text-secondary ms-1" title="문서 첨부"></i>';
					}
				}
			} catch (e) {
				console.error('파일 정보 파싱 실패:', e);
			}
		}

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

		html += `<h5 class="card-title mb-2">${escapeHtml(item.board_title)}${icons}</h5>`;

		// 본문 내용 (2라인 정도 표시)
		if (content) {
			html += `<p class="card-text text-muted mb-2" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; text-overflow: ellipsis; line-height: 1.5;">${escapeHtml(content)}...</p>`;
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
						// URL이 상대경로인 경우 절대경로로 변환
						let imageUrl = block.data.file.url;
						if (!imageUrl.startsWith('http')) {
							if (!imageUrl.startsWith('/')) {
								imageUrl = '/' + imageUrl;
							}
							imageUrl = 'https://wani.im' + imageUrl;
						}
						html += `<a href="${escapeHtml(imageUrl)}" class="glightbox" data-gallery="editor-images" data-title="${escapeHtml(block.data.caption || '')}">`;
						html += `<img src="${escapeHtml(imageUrl)}" class="figure-img img-fluid rounded editor-image" alt="${escapeHtml(block.data.caption || '')}" style="cursor: pointer;">`;
						html += `</a>`;
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




// 게시글 작성 폼 표시
async function showBoardWriteForm(menuId) {
	const mainContent = document.getElementById('mainContent');
	mainContent.classList.remove('loading');

	// 메뉴 정보 조회
	const menuInfo = menuData.find(item => item.id === menuId);
	const menuName = menuInfo ? menuInfo.name : '게시판';
	const categoryName = menuInfo ? menuInfo.category : '';

	let html = '<div class="board-container fade-in">';
	html += '<div class="container py-5">';
	html += `<h4 class="mb-0">${escapeHtml(menuName)}</h4>`;

	// Breadcrumb 추가
	html += '<nav aria-label="breadcrumb">';
	html += '<ol class="breadcrumb">';
	html += '<li class="breadcrumb-item"><a href="/"><i class="bi bi-house-door-fill"></i></a></li>';
	if (categoryName) {
		html += `<li class="breadcrumb-item"><a href="#">${escapeHtml(categoryName)}</a></li>`;
	}
	html += `<li class="breadcrumb-item"><a href="/board/${menuId}/">${escapeHtml(menuName)}</a></li>`;
	html += `<li class="breadcrumb-item active" aria-current="page">게시글 작성</li>`;
	html += '</ol>';
	html += '</nav>';

	html += '<form id="boardWriteForm" class="mt-4">';

	// 작성자 확인 필드
	html += '<div class="mb-3">';
	html += '<label class="form-label">작성자 확인</label>';
	html += '<div class="row g-2">';
	html += '<div class="col-md-4">';
	html += '<input type="text" class="form-control" id="writerName" placeholder="이름" required>';
	html += '</div>';
	html += '<div class="col-md-4">';
	html += '<input type="text" class="form-control" id="writerPhone" placeholder="휴대폰번호" required>';
	html += '</div>';
	html += '<div class="col-md-4">';
	html += '<button type="button" class="btn btn-outline-secondary w-100" id="btnVerifyWriter">확인</button>';
	html += '</div>';
	html += '</div>';
	html += '<div id="verifyResult" class="mt-2"></div>';
	html += '<input type="hidden" id="isVerified" value="0">';
	html += '</div>';

	// 제목
	html += '<div class="mb-3">';
	html += '<label for="boardTitle" class="form-label">제목</label>';
	html += '<input type="text" class="form-control" id="boardTitle" required>';
	html += '</div>';

	// 내용
	html += '<div class="mb-3">';
	html += '<label for="boardContent" class="form-label">내용</label>';
	html += '<textarea class="form-control" id="boardContent" rows="10" required></textarea>';
	html += '</div>';

	// YouTube URL
	html += '<div class="mb-3">';
	html += '<label for="youtubeUrl" class="form-label">YouTube URL (선택)</label>';
	html += '<input type="url" class="form-control" id="youtubeUrl" placeholder="https://www.youtube.com/watch?v=...">';
	html += '<small class="text-muted">YouTube 동영상 URL을 입력하면 게시글에 표시됩니다.</small>';
	html += '</div>';

	// 파일 첨부
	html += '<div class="mb-3">';
	html += '<label class="form-label">파일 첨부 (선택)</label>';
	html += '<div id="boardFileDropzone" class="dropzone"></div>';
	html += '<input type="hidden" id="uploadedFiles" value="">';
	html += '</div>';

	html += '<div class="d-flex gap-2">';
	html += `<a href="/board/${menuId}/" class="btn btn-secondary">취소</a>`;
	html += '<button type="submit" class="btn btn-primary">등록</button>';
	html += '</div>';
	html += '</form>';
	html += '</div>';
	html += '</div>';

	mainContent.innerHTML = html;

	// Dropzone 초기화
	initBoardWriteDropzone();

	// 작성자 확인 버튼 이벤트
	document.getElementById('btnVerifyWriter').addEventListener('click', verifyWriter);

	// 폼 제출 이벤트
	document.getElementById('boardWriteForm').addEventListener('submit', async (e) => {
		e.preventDefault();

		const isVerified = document.getElementById('isVerified').value;
		if (isVerified !== '1') {
			showToast('작성자 확인을 먼저 진행해주세요.');
			return;
		}

		const title = document.getElementById('boardTitle').value.trim();
		const content = document.getElementById('boardContent').value.trim();
		const writerName = document.getElementById('writerName').value.trim();
		const writerPhone = document.getElementById('writerPhone').value.trim();
		const youtubeUrl = document.getElementById('youtubeUrl').value.trim();
		const uploadedFiles = document.getElementById('uploadedFiles').value;

		if (!title || !content) {
			showToast('제목과 내용을 입력해주세요.');
			return;
		}

		// EditorJS 형식으로 변환
		const editorJsContent = {
			time: Date.now(),
			blocks: [
				{
					id: generateRandomId(),
					type: "paragraph",
					data: {
						text: content
					}
				}
			],
			version: "2.31.0"
		};

		// 게시글 저장 API 호출
		try {
			const response = await fetch(`${API_BASE_URL}/board/save`, {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json'
				},
				body: JSON.stringify({
					org_code: ORG_CODE,
					menu_id: menuId,
					board_title: title,
					board_content: JSON.stringify(editorJsContent), // EditorJS 형식으로 저장
					writer_name: writerName,
					writer_phone: writerPhone,
					youtube_url: youtubeUrl,
					file_path: uploadedFiles
				})
			});

			const result = await response.json();

			if (result.success) {
				showToast('게시글이 등록되었습니다.');
				window.location.href = `/board/${menuId}/`;
			} else {
				showToast(result.message || '게시글 등록에 실패했습니다.');
			}
		} catch (error) {
			console.error('게시글 저장 실패:', error);
			showToast('게시글 저장 중 오류가 발생했습니다.');
		}
	});
}

// EditorJS ID 생성 헬퍼 함수
function generateRandomId() {
	return Math.random().toString(36).substr(2, 10);
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

		$(window).scroll(function() {
			if ($(this).scrollTop() > 500) {
				$('header').addClass('fixed');
			} else {
				$('header').removeClass('fixed');
			}
		});

	} else if (path.startsWith('/page/')) {
		$('header').addClass('fixed');
		const menuId = path.replace('/page/', '').replace(/\/$/, '');
		loadPageContent(menuId);
	} else if (path.startsWith('/board/')) {
		$('header').addClass('fixed');
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
			// 게시글 상세 또는 수정
			const idx = parseInt(pathParts[1]);

			if (pathParts[2] === 'edit') {
				// 수정 페이지
				showBoardEditForm(menuId, idx);
			} else {
				// 상세 페이지
				loadBoardDetail(menuId, idx);
			}
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



function findMenuById(menus, menuId) {
	return getMenuInfo(menuId);
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

// 첨부파일 Grid 렌더링
function renderAttachmentGrid(filePath) {
	if (!filePath) {
		console.log('[첨부파일] filePath 없음');
		return '';
	}

	try {
		let files;
		if (typeof filePath === 'string') {
			files = JSON.parse(filePath);
		} else {
			files = filePath;
		}

		if (!Array.isArray(files) || files.length === 0) {
			console.log('[첨부파일] 파일 배열 비어있음');
			return '';
		}

		console.log('[첨부파일] 전체 파일 목록:', files);

		// 이미지와 문서 분리 (확장자 기반으로도 체크)
		const images = files.filter(file => {
			// type 필드로 먼저 체크
			if (file.type && file.type.startsWith('image/')) {
				return true;
			}
			// type이 없으면 확장자로 체크
			const fileName = file.original_name || file.name; // 원본 파일명 우선 사용
			if (fileName) {
				const ext = fileName.split('.').pop().toLowerCase();
				return ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'].includes(ext);
			}
			return false;
		});

		const documents = files.filter(file => {
			// type 필드로 먼저 체크
			if (file.type && file.type.startsWith('image/')) {
				return false;
			}
			// type이 없으면 확장자로 체크
			const fileName = file.original_name || file.name; // 원본 파일명 우선 사용
			if (fileName) {
				const ext = fileName.split('.').pop().toLowerCase();
				return !['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'].includes(ext);
			}
			return true;
		});

		console.log('[첨부파일] 이미지:', images.length, '문서:', documents.length);

		let html = '';

		// 이미지 Grid
		if (images.length > 0) {
			html += '<div class="attachment-section mt-4">';
			html += '<h5 class="mb-3"><i class="bi bi-images"></i> 첨부 이미지 (' + images.length + ')</h5>';
			html += '<div class="row g-3">';

			images.forEach(file => {
				// 썸네일이 있으면 썸네일 사용, 없으면 원본 사용
				let imageUrl = file.thumb_path || file.path;
				let fullImageUrl = file.path;
				const displayName = file.original_name || file.name; // 원본 파일명 우선 사용

				console.log('[이미지 렌더링]', displayName, '썸네일:', imageUrl, '원본:', fullImageUrl);

				// 상대경로를 절대경로로 변환
				if (!imageUrl.startsWith('http')) {
					if (!imageUrl.startsWith('/')) imageUrl = '/' + imageUrl;
					imageUrl = 'https://wani.im' + imageUrl;
				}
				if (!fullImageUrl.startsWith('http')) {
					if (!fullImageUrl.startsWith('/')) fullImageUrl = '/' + fullImageUrl;
					fullImageUrl = 'https://wani.im' + fullImageUrl;
				}

				html += '<div class="col-6 col-md-4 col-lg-3">';
				html += '<div class="card shadow-sm h-100 position-relative" style="transition: transform 0.2s;">';
				// GLightbox용 링크 추가 - 원본 파일명 사용
				html += '<a href="' + escapeHtml(fullImageUrl) + '" class="glightbox" data-gallery="board-attachments" data-title="' + escapeHtml(displayName) + '">';
				html += '<div class="card-img-wrapper" style="height: 200px; overflow: hidden; cursor: pointer; background: #f8f9fa;">';
				html += '<img src="' + escapeHtml(imageUrl) + '" class="card-img-top" style="width: 100%; height: 100%; object-fit: cover;" alt="' + escapeHtml(displayName) + '" onerror="this.style.display=\'none\'; this.parentElement.innerHTML=\'<div class=\\\'d-flex align-items-center justify-content-center h-100\\\'><i class=\\\'bi bi-image text-muted fs-1\\\'></i></div>\';">';
				html += '</div>';
				html += '</a>';

				// 원본 파일명 표시
				html += '<div class="overlay" style="position: absolute; bottom: 0; width: 100%;background: linear-gradient(#00000000 30%, #666); height: 90px;"></div>';
				html += '<small class="d-block text-truncate text-white w-100 px-3" style="position: absolute; bottom:10px;" title="' + escapeHtml(displayName) + '">' + escapeHtml(displayName) + '</small>';
				// 다운로드 버튼 - 원본 파일명으로 다운로드
				html += '<button type="button" class="btn btn-sm btn-outline-primary bg-white" style="position: absolute; top: 10px; right: 10px;" onclick="event.stopPropagation(); downloadFile(\'' + fullImageUrl.replace(/'/g, "\\'") + '\', \'' + escapeHtml(displayName).replace(/'/g, "\\'") + '\');"><i class="bi bi-download"></i></button>';

				html += '</div>';
				html += '</div>';
			});

			html += '</div>';
			html += '</div>';
		}

		// 문서 목록
		if (documents.length > 0) {
			html += '<div class="attachment-section mt-4">';
			html += '<h5 class="mb-3"><i class="bi bi-file-earmark-text"></i> 첨부 문서 (' + documents.length + ')</h5>';
			html += '<div class="list-group">';

			documents.forEach(file => {
				let fileUrl = file.path;
				const displayName = file.original_name || file.name; // 원본 파일명 우선 사용

				if (!fileUrl.startsWith('http')) {
					if (!fileUrl.startsWith('/')) fileUrl = '/' + fileUrl;
					fileUrl = 'https://wani.im' + fileUrl;
				}

				// 파일 확장자 추출 (원본 파일명에서)
				const ext = displayName.split('.').pop().toUpperCase();
				const fileSize = formatFileSize(file.size);

				html += '<div class="list-group-item d-flex justify-content-between align-items-center">';
				html += '<div class="d-flex align-items-center flex-grow-1">';
				html += '<span class="badge bg-secondary me-2">' + ext + '</span>';
				// 원본 파일명 표시
				html += '<span class="text-truncate me-2">' + escapeHtml(displayName) + '</span>';
				html += '<small class="text-muted">(' + fileSize + ')</small>';
				html += '</div>';
				// 다운로드 버튼 - 원본 파일명으로 다운로드
				html += '<button type="button" class="btn btn-sm btn-outline-primary" onclick="downloadFile(\'' + fileUrl.replace(/'/g, "\\'") + '\', \'' + escapeHtml(displayName).replace(/'/g, "\\'") + '\');"><i class="bi bi-download"></i> 다운로드</button>';
				html += '</div>';
			});

			html += '</div>';
			html += '</div>';
		}

		return html;
	} catch (error) {
		console.error('첨부파일 렌더링 오류:', error);
		return '';
	}
}



/**
 * GLightbox 초기화
 */
function initGLightbox() {
	if (typeof GLightbox !== 'undefined') {
		const lightbox = GLightbox({
			touchNavigation: true,
			loop: true,
			autoplayVideos: true,
			closeButton: true,
			closeOnOutsideClick: true,
			skin: 'clean',
			openEffect: 'fade',
			closeEffect: 'fade',
			slideEffect: 'slide',
			moreLength: 0
		});
		console.log('[GLightbox] 초기화 완료');
		return lightbox;
	} else {
		console.warn('[GLightbox] 라이브러리가 로드되지 않았습니다.');
		return null;
	}
}





// 파일 크기 포맷팅
function formatFileSize(bytes) {
	if (!bytes || bytes === 0) return '0 Bytes';
	const k = 1024;
	const sizes = ['Bytes', 'KB', 'MB', 'GB'];
	const i = Math.floor(Math.log(bytes) / Math.log(k));
	return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
}

/**
 * 파일 다운로드 (메인 도메인 API 사용)
 */
function downloadFile(url, filename) {
	console.log('[파일 다운로드] 시작:', url, filename);

	try {
		// URL에서 wani.im 제거 (상대 경로로 변환)
		let filePath = url;
		if (filePath.includes('wani.im')) {
			filePath = filePath.replace(/https?:\/\/[^/]+\.wani\.im/, ''); // 서브도메인 제거
			filePath = filePath.replace(/https?:\/\/wani\.im/, ''); // 메인 도메인 제거
		}
		if (!filePath.startsWith('/')) {
			filePath = '/' + filePath;
		}

		console.log('[파일 다운로드] 파일 경로:', filePath);
		console.log('[파일 다운로드] 파일명:', filename);

		// 메인 도메인의 API 사용 (중요!)
		const downloadUrl = 'https://wani.im/api/download?file=' + encodeURIComponent(filePath) + '&name=' + encodeURIComponent(filename);

		console.log('[파일 다운로드] 다운로드 URL:', downloadUrl);

		// iframe을 사용한 다운로드 (페이지 이동 없이)
		const iframe = document.createElement('iframe');
		iframe.style.display = 'none';
		iframe.src = downloadUrl;
		document.body.appendChild(iframe);

		// iframe 정리 (3초 후)
		setTimeout(() => {
			document.body.removeChild(iframe);
			console.log('[파일 다운로드] 완료:', filename);
		}, 3000);

	} catch (error) {
		console.error('[파일 다운로드] 오류:', error);
		alert('파일 다운로드에 실패했습니다: ' + error.message);
	}
}

// 작성자 확인 함수
async function verifyWriter() {
	const writerName = document.getElementById('writerName').value.trim();
	const writerPhone = document.getElementById('writerPhone').value.trim();
	const resultDiv = document.getElementById('verifyResult');

	if (!writerName || !writerPhone) {
		resultDiv.innerHTML = '<span class="text-danger">이름과 휴대폰번호를 모두 입력해주세요.</span>';
		document.getElementById('isVerified').value = '0';
		return;
	}

	try {
		const response = await fetch(`${API_BASE_URL}/board/verify_writer`, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json'
			},
			body: JSON.stringify({
				org_code: ORG_CODE,
				member_name: writerName,
				member_phone: writerPhone
			})
		});

		const result = await response.json();

		if (result.success && result.data && result.data.is_member) {
			resultDiv.innerHTML = '<span class="text-success">확인 OK</span>';
			document.getElementById('isVerified').value = '1';
		} else {
			resultDiv.innerHTML = '<span class="text-danger">조직의 회원이 아닙니다.</span>';
			document.getElementById('isVerified').value = '0';
		}
	} catch (error) {
		console.error('작성자 확인 실패:', error);
		resultDiv.innerHTML = '<span class="text-danger">확인 중 오류가 발생했습니다.</span>';
		document.getElementById('isVerified').value = '0';
	}
}

// Dropzone 인스턴스 변수
let boardWriteDropzone = null;




/**
 * 역할: 게시글 작성 페이지 Dropzone 초기화 - API 서버 사용
 */
function initBoardWriteDropzone() {
	if (boardWriteDropzone) {
		boardWriteDropzone.destroy();
		boardWriteDropzone = null;
	}

	const dropzoneElement = document.getElementById('boardFileDropzone');
	if (!dropzoneElement) {
		console.error('Dropzone 요소를 찾을 수 없습니다.');
		return;
	}

	boardWriteDropzone = new Dropzone('#boardFileDropzone', {
		url: 'https://wani.im/api/homepage_api/upload_file',  // API 서버 경로로 변경
		paramName: 'file',
		maxFilesize: 50,
		maxFiles: 20,
		addRemoveLinks: true,
		dictDefaultMessage: '파일을 드래그하거나 클릭하여 업로드하세요',
		dictRemoveFile: '삭제',
		dictCancelUpload: '취소',
		dictMaxFilesExceeded: '최대 20개까지 업로드 가능합니다',
		acceptedFiles: 'image/*,application/pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.hwp,.hwpx,.zip',
		autoProcessQueue: true,
		headers: {
			'X-Requested-With': 'XMLHttpRequest'
		},
		params: {
			org_code: ORG_CODE  // org_id 대신 org_code 사용
		},
		init: function() {
			this.on('success', function(file, response) {
				console.log('파일 업로드 성공:', response);

				if (response.success && response.file_info) {
					// 서버에서 반환된 파일 정보 저장
					file.serverFileName = response.file_info.name;
					file.serverFilePath = response.file_info.path;
					file.serverFileUrl = response.file_info.url;
					file.serverThumbPath = response.file_info.thumb_path;
					file.serverThumbUrl = response.file_info.thumb_url;
					file.originalFileName = response.file_info.original_name;
					file.fileType = response.file_info.type;

					updateUploadedFilesList();
				}
			});

			this.on('error', function(file, errorMessage) {
				console.error('파일 업로드 실패:', errorMessage);

				// 에러 메시지 파싱
				let message = '파일 업로드에 실패했습니다.';
				if (typeof errorMessage === 'object' && errorMessage.message) {
					message = errorMessage.message;
				} else if (typeof errorMessage === 'string') {
					message = errorMessage;
				}

				showToast(message);
			});

			this.on('removedfile', function(file) {
				console.log('파일 제거됨:', file);
				updateUploadedFilesList();
			});

			// 이미지 파일 썸네일 생성
			this.on('thumbnail', function(file, dataUrl) {
				if (file.type.match(/image.*/)) {
					console.log('이미지 썸네일 생성됨:', file.name);
				}
			});
		}
	});
}

/**
 * 역할: 업로드된 파일 목록 업데이트 - JSON 배열로 저장
 */

function updateUploadedFilesList() {
	if (!boardWriteDropzone) return;

	const files = boardWriteDropzone.files.filter(f => f.status === 'success' && f.serverFileName);

	// 파일 정보 배열 생성
	const fileBlocks = files.map(f => {
		return {
			name: f.serverFileName,
			original_name: f.originalFileName || f.name,
			path: f.serverFilePath,
			url: f.serverFileUrl,
			thumb_path: f.serverThumbPath,
			thumb_url: f.serverThumbUrl,
			size: f.size,
			type: f.fileType || (f.type.startsWith('image/') ? 'image' : 'document')
		};
	});

	document.getElementById('uploadedFiles').value = JSON.stringify(fileBlocks);
	console.log('업로드된 파일 목록:', fileBlocks);
}

// Toast 메시지 표시 함수 (없을 경우 대비)
if (typeof showToast === 'undefined') {
	function showToast(message) {
		alert(message);
	}
}


/**
 * 게시글 수정 폼 표시 (기존 파일 유지 버전)
 */
async function showBoardEditForm(menuId, idx) {
	const mainContent = document.getElementById('mainContent');

	try {
		// 기존 게시글 데이터 조회
		const response = await fetch(`${API_BASE_URL}/board/detail/${ORG_CODE}/${idx}`);
		const result = await response.json();

		if (!result.success || !result.data) {
			showToast('게시글을 찾을 수 없습니다.');
			window.location.href = `/board/${menuId}/`;
			return;
		}

		const boardData = result.data;

		const menuInfo = findMenuById(menuData, menuId);
		const menuName = menuInfo ? menuInfo.name : '게시판';
		const categoryName = menuInfo ? menuInfo.category : '';

		let html = '<div class="board-container fade-in">';
		html += '<div class="container py-5">';

		// Breadcrumb
		html += '<h4 class="mb-0">' + escapeHtml(menuName) + '</h4>';
		html += '<nav aria-label="breadcrumb">';
		html += '<ol class="breadcrumb">';
		html += '<li class="breadcrumb-item"><a href="/"><i class="bi bi-house-door-fill"></i></a></li>';
		if (categoryName) {
			html += '<li class="breadcrumb-item"><a href="#">' + escapeHtml(categoryName) + '</a></li>';
		}
		html += '<li class="breadcrumb-item"><a href="/board/' + menuId + '">' + escapeHtml(menuName) + '</a></li>';
		html += '<li class="breadcrumb-item active" aria-current="page">게시글 수정</li>';
		html += '</ol>';
		html += '</nav>';

		html += '<div class="card shadow-sm mt-4">';
		html += '<div class="card-body">';
		html += '<h5 class="card-title mb-4">게시글 수정</h5>';
		html += '<form id="boardEditForm">';
		html += '<input type="hidden" id="boardIdx" value="' + idx + '">';
		html += '<input type="hidden" id="isVerified" value="1">'; // 이미 확인된 상태

		// 제목
		html += '<div class="mb-3">';
		html += '<label for="boardTitle" class="form-label">제목 <span class="text-danger">*</span></label>';
		html += '<input type="text" class="form-control" id="boardTitle" value="' + escapeHtml(boardData.board_title) + '" required>';
		html += '</div>';

		// 작성자 정보 (읽기 전용)
		html += '<div class="mb-3">';
		html += '<label class="form-label">작성자</label>';
		html += '<input type="text" class="form-control" id="writerName" value="' + escapeHtml(boardData.writer_name || '') + '" readonly>';
		html += '</div>';

		// 내용
		html += '<div class="mb-3">';
		html += '<label for="boardContent" class="form-label">내용 <span class="text-danger">*</span></label>';
		html += '<textarea class="form-control" id="boardContent" rows="10" required></textarea>';
		html += '</div>';

		// YouTube URL
		html += '<div class="mb-3">';
		html += '<label for="youtubeUrl" class="form-label">YouTube 동영상 (선택)</label>';
		html += '<input type="url" class="form-control" id="youtubeUrl" value="' + escapeHtml(boardData.youtube_url || '') + '" placeholder="예: https://www.youtube.com/watch?v=...">';
		html += '<small class="text-muted">YouTube 동영상 URL을 입력하면 게시글에 표시됩니다.</small>';
		html += '</div>';

		// 파일 첨부
		html += '<div class="mb-3">';
		html += '<label class="form-label">파일 첨부 (선택)</label>';
		html += '<div id="boardFileDropzone" class="dropzone"></div>';
		html += '<input type="hidden" id="uploadedFiles" value="">';
		html += '<input type="hidden" id="existingFiles" value="' + escapeHtml(boardData.file_path || '') + '">';
		html += '</div>';

		html += '<div class="d-flex gap-2">';
		html += `<a href="/board/${menuId}/${idx}" class="btn btn-secondary">취소</a>`;
		html += '<button type="submit" class="btn btn-primary">수정 완료</button>';
		html += '</div>';
		html += '</form>';
		html += '</div>';
		html += '</div>';
		html += '</div>';
		html += '</div>';

		mainContent.innerHTML = html;
		mainContent.classList.remove('loading');

		// Dropzone 초기화 (수정 모드 - 기존 파일 포함)
		initBoardEditDropzone(boardData.file_path);

		// 기존 내용을 textarea에 표시 (EditorJS에서 텍스트만 추출)
		try {
			const content = JSON.parse(boardData.board_content);
			if (content.blocks && Array.isArray(content.blocks)) {
				const textContent = content.blocks.map(block => {
					if (block.type === 'paragraph') {
						return block.data.text;
					}
					return '';
				}).join('\n\n');
				document.getElementById('boardContent').value = textContent;
			}
		} catch (e) {
			document.getElementById('boardContent').value = boardData.board_content || '';
		}

		// 폼 제출 이벤트
		document.getElementById('boardEditForm').addEventListener('submit', async (e) => {
			e.preventDefault();

			const title = document.getElementById('boardTitle').value.trim();
			const content = document.getElementById('boardContent').value.trim();
			const writerName = document.getElementById('writerName').value.trim();
			const youtubeUrl = document.getElementById('youtubeUrl').value.trim();
			const uploadedFiles = document.getElementById('uploadedFiles').value;

			if (!title || !content) {
				showToast('제목과 내용을 입력해주세요.');
				return;
			}

			// EditorJS 형식으로 변환
			const editorJsContent = {
				time: Date.now(),
				blocks: [
					{
						id: generateRandomId(),
						type: "paragraph",
						data: {
							text: content
						}
					}
				],
				version: "2.31.0"
			};

			// 게시글 수정 API 호출
			try {
				const response = await fetch(`${API_BASE_URL}/board/update`, {
					method: 'POST',
					headers: {
						'Content-Type': 'application/json'
					},
					body: JSON.stringify({
						idx: idx,
						org_code: ORG_CODE,
						menu_id: menuId,
						board_title: title,
						board_content: JSON.stringify(editorJsContent),
						writer_name: writerName,
						youtube_url: youtubeUrl,
						file_path: uploadedFiles
					})
				});

				const result = await response.json();

				if (result.success) {
					showToast('게시글이 수정되었습니다.');
					window.location.href = `/board/${menuId}/${idx}`;
				} else {
					showToast(result.message || '게시글 수정에 실패했습니다.');
				}
			} catch (error) {
				console.error('게시글 수정 실패:', error);
				showToast('게시글 수정 중 오류가 발생했습니다.');
			}
		});

	} catch (error) {
		console.error('게시글 수정 폼 로드 실패:', error);
		mainContent.innerHTML = '<div class="text-center py-5"><p class="text-danger">게시글을 불러오는 중 오류가 발생했습니다.</p></div>';
		mainContent.classList.remove('loading');
	}
}

/**
 * 게시글 수정용 Dropzone 초기화 (기존 파일 복원 포함)
 */
function initBoardEditDropzone(existingFilePath) {
	// 기존 Dropzone 인스턴스 제거
	if (boardWriteDropzone) {
		boardWriteDropzone.destroy();
		boardWriteDropzone = null;
	}

	const dropzoneElement = document.getElementById('boardFileDropzone');
	if (!dropzoneElement) {
		console.error('Dropzone 요소를 찾을 수 없습니다.');
		return;
	}

	// 기존 파일 파싱
	let existingFiles = [];
	if (existingFilePath) {
		try {
			existingFiles = JSON.parse(existingFilePath);
			if (!Array.isArray(existingFiles)) {
				existingFiles = [];
			}
		} catch (e) {
			console.error('기존 파일 파싱 실패:', e);
			existingFiles = [];
		}
	}

	boardWriteDropzone = new Dropzone('#boardFileDropzone', {
		url: 'https://wani.im/api/homepage_api/upload_file',
		paramName: 'file',
		maxFilesize: 50,
		maxFiles: 20,
		addRemoveLinks: true,
		dictDefaultMessage: '파일을 드래그하거나 클릭하여 업로드하세요',
		dictRemoveFile: '삭제',
		dictCancelUpload: '취소',
		dictMaxFilesExceeded: '최대 20개까지 업로드 가능합니다',
		acceptedFiles: 'image/*,application/pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.hwp,.hwpx,.zip',
		autoProcessQueue: true,
		headers: {
			'X-Requested-With': 'XMLHttpRequest'
		},
		params: {
			org_code: ORG_CODE
		},
		init: function() {
			const dropzoneInstance = this;

			// 기존 파일 복원
			existingFiles.forEach((file, index) => {
				// 이미지 여부 확인
				const isImage = file.type && (file.type === 'image' || file.type.startsWith('image/'));

				// 표시용 파일명 (원본 파일명 우선)
				const displayName = file.original_name || file.name;

				// 썸네일 URL 생성
				let thumbUrl = null;
				if (isImage) {
					let imagePath = file.thumb_path || file.path;
					if (imagePath) {
						if (!imagePath.startsWith('http')) {
							if (!imagePath.startsWith('/')) imagePath = '/' + imagePath;
							thumbUrl = 'https://wani.im' + imagePath;
						} else {
							thumbUrl = imagePath;
						}
					}
				}

				// Mock 파일 객체 생성 (기존 파일임을 표시)
				const mockFile = {
					name: displayName,
					size: file.size || 0,
					type: isImage ? 'image/jpeg' : 'application/octet-stream',
					status: Dropzone.SUCCESS,
					accepted: true,
					upload: { progress: 100 },
					// 기존 파일 정보 저장
					existingFile: true,
					fileData: {
						name: file.name,
						original_name: file.original_name || file.name,
						path: file.path,
						url: file.url || '',
						thumb_path: file.thumb_path || '',
						thumb_url: file.thumb_url || '',
						size: file.size || 0,
						type: file.type || 'document'
					}
				};

				// Dropzone에 파일 추가
				dropzoneInstance.files.push(mockFile);
				dropzoneInstance.emit('addedfile', mockFile);

				// 이미지인 경우 썸네일 표시
				if (isImage && thumbUrl) {
					dropzoneInstance.emit('thumbnail', mockFile, thumbUrl);
				}

				dropzoneInstance.emit('complete', mockFile);

				console.log('[기존 파일 복원]', displayName, isImage ? '(이미지)' : '(문서)');
			});

			// 초기 파일 목록 업데이트
			updateEditFilesList();

			// 새 파일 업로드 성공
			this.on('success', function(file, response) {
				console.log('파일 업로드 성공:', response);

				if (response.success && response.file_info) {
					// 새로 업로드된 파일 정보 저장
					file.existingFile = false;
					file.fileData = {
						name: response.file_info.name,
						original_name: response.file_info.original_name,
						path: response.file_info.path,
						url: response.file_info.url,
						thumb_path: response.file_info.thumb_path,
						thumb_url: response.file_info.thumb_url,
						size: file.size,
						type: response.file_info.type
					};

					updateEditFilesList();
				}
			});

			// 파일 업로드 실패
			this.on('error', function(file, errorMessage) {
				console.error('파일 업로드 실패:', errorMessage);

				let message = '파일 업로드에 실패했습니다.';
				if (typeof errorMessage === 'object' && errorMessage.message) {
					message = errorMessage.message;
				} else if (typeof errorMessage === 'string') {
					message = errorMessage;
				}

				showToast(message);
			});

			// 파일 제거
			this.on('removedfile', function(file) {
				console.log('파일 제거됨:', file.name);
				updateEditFilesList();
			});
		}
	});
}

/**
 * 수정 폼용 파일 목록 업데이트
 */
function updateEditFilesList() {
	if (!boardWriteDropzone) return;

	const fileBlocks = [];

	// Dropzone의 모든 파일 순회
	boardWriteDropzone.files.forEach(file => {
		// 기존 파일 또는 업로드 성공한 파일만 포함
		if (file.fileData) {
			fileBlocks.push({
				name: file.fileData.name,
				original_name: file.fileData.original_name,
				path: file.fileData.path,
				url: file.fileData.url || '',
				thumb_path: file.fileData.thumb_path || '',
				thumb_url: file.fileData.thumb_url || '',
				size: file.fileData.size || 0,
				type: file.fileData.type || 'document'
			});
		}
	});

	// hidden input 업데이트
	const uploadedFilesInput = document.getElementById('uploadedFiles');
	if (uploadedFilesInput) {
		uploadedFilesInput.value = JSON.stringify(fileBlocks);
		console.log('[수정 폼] 파일 목록 업데이트:', fileBlocks.length, '개');
	}
}


/**
 * 수정 폼용 업로드 파일 목록 업데이트
 */
function updateUploadedFilesListForEdit() {
	if (!boardWriteDropzone) return;

	// 기존 파일 + 새로 업로드된 파일 모두 포함
	const files = boardWriteDropzone.files.filter(f => {
		// 기존 파일이거나 업로드 성공한 파일
		return f.isExisting || (f.status === 'success' && f.serverFileName);
	});

	const fileBlocks = files.map(f => {
		return {
			name: f.serverFileName || f.name,
			original_name: f.originalFileName || f.name,
			path: f.serverFilePath || '',
			url: f.serverFileUrl || '',
			thumb_path: f.serverThumbPath || '',
			thumb_url: f.serverThumbUrl || '',
			size: f.size || 0,
			type: f.fileType || 'document'
		};
	});

	document.getElementById('uploadedFiles').value = JSON.stringify(fileBlocks);
	console.log('[수정 폼] 파일 목록 업데이트:', fileBlocks);
}



/**
 * 게시글 상세 로드 함수 (수정 버튼에 권한 확인 로직 추가)
 */
async function loadBoardDetail(menuId, idx) {
	try {
		const response = await fetch(`${API_BASE_URL}/board/detail/${ORG_CODE}/${idx}`);
		const result = await response.json();

		const mainContent = document.getElementById('mainContent');
		mainContent.classList.remove('loading');

		if (result.success && result.data) {
			const item = result.data;
			const date = new Date(item.reg_date).toLocaleDateString('ko-KR');
			const modDate = item.modi_date ?
				'<br><small class="text-muted">(수정: ' + new Date(item.modi_date).toLocaleDateString('ko-KR') + ')</small>' : '';

			const menuInfo = findMenuById(menuData, menuId);
			const menuName = menuInfo ? menuInfo.name : '게시판';
			const categoryName = menuInfo ? menuInfo.category : '';

			let html = '<div class="board-container fade-in">';
			html += '<div class="container py-5">';

			// Breadcrumb
			html += '<h4 class="mb-0">' + escapeHtml(menuName) + '</h4>';
			html += '<nav aria-label="breadcrumb">';
			html += '<ol class="breadcrumb">';
			html += '<li class="breadcrumb-item"><a href="/"><i class="bi bi-house-door-fill"></i></a></li>';
			if (categoryName) {
				html += '<li class="breadcrumb-item"><a href="#">' + escapeHtml(categoryName) + '</a></li>';
			}
			html += '<li class="breadcrumb-item"><a href="/board/' + menuId + '">' + escapeHtml(menuName) + '</a></li>';
			html += '<li class="breadcrumb-item active" aria-current="page">게시글 상세</li>';
			html += '</ol>';
			html += '</nav>';

			// 게시글 제목
			html += '<div class="card shadow-sm mt-4">';
			html += '<div class="card-body">';
			html += '<h3 class="card-title">' + escapeHtml(item.board_title) + '</h3>';
			html += '<div class="d-flex justify-content-between align-items-center text-muted border-bottom pb-3">';
			html += '<div>';
			html += '<small>작성자: ' + escapeHtml(item.writer_name || '') + '</small> | ';
			html += '<small>작성일: ' + date + modDate + '</small> | ';
			html += '<small>조회수: ' + item.view_count + '</small>';
			html += '</div>';
			html += '</div>';

			// YouTube 동영상
			if (item.youtube_url) {
				const videoId = extractYoutubeId(item.youtube_url);
				if (videoId) {
					html += '<div class="ratio ratio-16x9 my-4">';
					html += '<iframe src="https://www.youtube.com/embed/' + videoId + '" allowfullscreen></iframe>';
					html += '</div>';
				}
			}

			// 게시글 내용
			html += '<div class="mt-4">';
			html += convertEditorJsToHtml(item.board_content);
			html += '</div>';

			// 첨부파일 (이미지 lightbox + 문서 목록)
			if (item.file_path) {
				html += renderAttachmentGrid(item.file_path);
			}

			html += '</div>';
			html += '</div>';

			// 버튼 영역 (목록으로 + 수정 버튼)
			html += '<div class="mt-4 d-flex gap-2">';
			html += '<a href="/board/' + menuId + '/" class="btn btn-secondary">목록으로</a>';
			// 수정 버튼 - 클릭 시 권한 확인
			html += '<button type="button" class="btn btn-primary" id="btnEditBoard" data-menu-id="' + menuId + '" data-idx="' + idx + '" data-writer-name="' + escapeHtml(item.writer_name || '') + '">수정</button>';
			html += '</div>';

			html += '</div>';
			html += '</div>';

			// 작성자 확인 모달 추가
			html += getEditVerifyModalHtml();

			mainContent.innerHTML = html;

			// GLightbox 초기화
			initGLightbox();

			// 수정 버튼 이벤트 바인딩
			document.getElementById('btnEditBoard').addEventListener('click', handleEditButtonClick);

		} else {
			mainContent.innerHTML = '<div class="text-center py-5"><p class="text-danger">게시글을 찾을 수 없습니다.</p></div>';
		}
		hidePageLoading();
	} catch (error) {
		console.error('게시글 로드 실패:', error);
		document.getElementById('mainContent').innerHTML = '<div class="text-center py-5"><p class="text-danger">게시글을 불러오는 중 오류가 발생했습니다.</p></div>';
		hidePageLoading();
	}
}

/**
 * 수정 버튼 클릭 핸들러
 */
async function handleEditButtonClick(e) {
	const btn = e.currentTarget;
	const menuId = btn.dataset.menuId;
	const idx = btn.dataset.idx;
	const writerName = btn.dataset.writerName;

	// 1. 먼저 관리자인지 확인 (로그인된 사용자 ID가 있다면)
	const userId = getCurrentUserId(); // 현재 로그인된 사용자 ID 가져오기

	if (userId) {
		try {
			const adminResponse = await fetch(`${API_BASE_URL}/check_admin`, {
				method: 'POST',
				headers: { 'Content-Type': 'application/json' },
				body: JSON.stringify({
					org_code: ORG_CODE,
					user_id: userId
				})
			});
			const adminResult = await adminResponse.json();

			// 관리자인 경우 바로 수정 페이지로 이동
			if (adminResult.success && adminResult.data && adminResult.data.is_admin) {
				console.log('[수정] 관리자 권한 확인됨, 바로 수정 페이지로 이동');
				window.location.href = `/board/${menuId}/${idx}/edit`;
				return;
			}
		} catch (error) {
			console.error('관리자 확인 실패:', error);
			// 오류 발생 시 일반 사용자로 처리
		}
	}

	// 2. 관리자가 아닌 경우 작성자 확인 모달 표시
	showEditVerifyModal(menuId, idx, writerName);
}

/**
 * 현재 로그인된 사용자 ID 가져오기
 * (쿠키 또는 전역 변수에서 가져옴)
 */
function getCurrentUserId() {
	// 방법 1: 전역 변수에서 가져오기
	if (typeof CURRENT_USER_ID !== 'undefined' && CURRENT_USER_ID) {
		return CURRENT_USER_ID;
	}

	// 방법 2: 쿠키에서 가져오기
	const cookies = document.cookie.split(';');
	for (let cookie of cookies) {
		const [name, value] = cookie.trim().split('=');
		if (name === 'user_id' || name === 'wani_user_id') {
			return decodeURIComponent(value);
		}
	}

	// 방법 3: localStorage에서 가져오기
	const storedUserId = localStorage.getItem('user_id');
	if (storedUserId) {
		return storedUserId;
	}

	return null;
}

/**
 * 작성자 확인 모달 HTML 생성
 */
function getEditVerifyModalHtml() {
	return `
        <div class="modal fade" id="editVerifyModal" tabindex="-1" aria-labelledby="editVerifyModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editVerifyModalLabel">작성자 확인</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted mb-3">게시글 수정을 위해 작성자 본인 확인이 필요합니다.</p>
                        <form id="editVerifyForm">
                            <input type="hidden" id="editMenuId" value="">
                            <input type="hidden" id="editIdx" value="">
                            <div class="mb-3">
                                <label for="editWriterName" class="form-label">이름</label>
                                <input type="text" class="form-control" id="editWriterName" placeholder="이름을 입력하세요" required>
                            </div>
                            <div class="mb-3">
                                <label for="editWriterPhone" class="form-label">휴대폰번호</label>
                                <input type="tel" class="form-control" id="editWriterPhone" placeholder="휴대폰번호 (- 없이)" maxlength="11" required>
                            </div>
                            <div id="editVerifyResult" class="mb-3"></div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                        <button type="button" class="btn btn-primary" id="btnConfirmEdit">확인</button>
                    </div>
                </div>
            </div>
        </div>
    `;
}

/**
 * 작성자 확인 모달 표시
 */
function showEditVerifyModal(menuId, idx, writerName) {
	// 값 설정
	document.getElementById('editMenuId').value = menuId;
	document.getElementById('editIdx').value = idx;
	document.getElementById('editWriterName').value = '';
	document.getElementById('editWriterPhone').value = '';
	document.getElementById('editVerifyResult').innerHTML = '';

	// 모달 표시
	const modal = new bootstrap.Modal(document.getElementById('editVerifyModal'));
	modal.show();

	// 확인 버튼 이벤트 (기존 이벤트 제거 후 새로 바인딩)
	const btnConfirm = document.getElementById('btnConfirmEdit');
	const newBtnConfirm = btnConfirm.cloneNode(true);
	btnConfirm.parentNode.replaceChild(newBtnConfirm, btnConfirm);

	newBtnConfirm.addEventListener('click', async function() {
		const menuId = document.getElementById('editMenuId').value;
		const idx = document.getElementById('editIdx').value;
		const name = document.getElementById('editWriterName').value.trim();
		const phone = document.getElementById('editWriterPhone').value.trim();
		const resultDiv = document.getElementById('editVerifyResult');

		if (!name || !phone) {
			resultDiv.innerHTML = '<div class="alert alert-warning py-2">이름과 휴대폰번호를 모두 입력해주세요.</div>';
			return;
		}

		// 버튼 비활성화
		newBtnConfirm.disabled = true;
		newBtnConfirm.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>확인 중...';

		try {
			// 수정 권한 확인 API 호출
			const response = await fetch(`${API_BASE_URL}/verify_edit_permission`, {
				method: 'POST',
				headers: { 'Content-Type': 'application/json' },
				body: JSON.stringify({
					org_code: ORG_CODE,
					idx: parseInt(idx),
					member_name: name,
					member_phone: phone
				})
			});

			const result = await response.json();

			if (result.success && result.data && result.data.can_edit) {
				// 확인 성공 - 모달 닫고 수정 페이지로 이동
				resultDiv.innerHTML = '<div class="alert alert-success py-2">확인되었습니다. 수정 페이지로 이동합니다.</div>';

				// 잠시 후 페이지 이동
				setTimeout(() => {
					bootstrap.Modal.getInstance(document.getElementById('editVerifyModal')).hide();
					window.location.href = `/board/${menuId}/${idx}/edit`;
				}, 500);
			} else {
				// 확인 실패
				resultDiv.innerHTML = '<div class="alert alert-danger py-2">' + (result.message || '작성자 확인에 실패했습니다.') + '</div>';
				newBtnConfirm.disabled = false;
				newBtnConfirm.innerHTML = '확인';
			}
		} catch (error) {
			console.error('수정 권한 확인 실패:', error);
			resultDiv.innerHTML = '<div class="alert alert-danger py-2">확인 중 오류가 발생했습니다.</div>';
			newBtnConfirm.disabled = false;
			newBtnConfirm.innerHTML = '확인';
		}
	});
}
