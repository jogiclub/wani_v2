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
	} catch (error) {
		console.error('메뉴 로드 실패:', error);
		document.getElementById('mainMenu').innerHTML = '';
	}
}

// 페이지 내용 로드
async function loadPageContent(menuId) {
	try {
		console.log('페이지 로드 시작:', menuId);
		const response = await fetch(`${API_BASE_URL}/page/${ORG_CODE}/${menuId}`);
		const result = await response.json();

		const mainContent = document.getElementById('mainContent');

		if (result.success && result.data && result.data.page_content) {
			mainContent.innerHTML = result.data.page_content;
			mainContent.classList.remove('loading');
			mainContent.classList.add('fade-in');
		} else {
			mainContent.innerHTML = '<div class="text-center py-5"><p class="text-muted">페이지 내용이 없습니다.</p></div>';
			mainContent.classList.remove('loading');
		}
	} catch (error) {
		console.error('페이지 로드 실패:', error);
		const mainContent = document.getElementById('mainContent');
		mainContent.innerHTML = '<div class="text-center py-5"><p class="text-danger">페이지를 불러오는 중 오류가 발생했습니다.</p></div>';
		mainContent.classList.remove('loading');
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
	} catch (error) {
		console.error('링크 로드 실패:', error);
		alert('링크를 불러오는 중 오류가 발생했습니다.');
	}
}

// 게시판 목록 로드
async function loadBoardList(menuId, page = 1) {
	try {
		const response = await fetch(`${API_BASE_URL}/board/${ORG_CODE}/${menuId}?page=${page}&limit=20`);
		const result = await response.json();

		const mainContent = document.getElementById('mainContent');
		mainContent.classList.remove('loading');

		if (result.success) {
			let html = '<div class="board-container fade-in">';
			html += '<div class="d-flex justify-content-between align-items-center mb-3">';
			html += '<h4 class="mb-0">게시판</h4>';
			html += `<a href="/board/${menuId}/write" class="btn btn-primary">글쓰기</a>`;
			html += '</div>';

			if (result.data && result.data.length > 0) {
				html += '<div class="table-responsive"><table class="table table-hover">';
				html += '<thead><tr><th style="width: 80px;">번호</th><th>제목</th><th style="width: 100px;">작성자</th><th style="width: 80px;">조회수</th><th style="width: 120px;">작성일</th></tr></thead>';
				html += '<tbody>';

				result.data.forEach((item, index) => {
					const num = result.total - ((page - 1) * 20) - index;
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

				// 페이징
				if (result.total > 20) {
					const totalPages = Math.ceil(result.total / 20);
					html += '<nav><ul class="pagination justify-content-center">';

					if (page > 1) {
						html += `<li class="page-item"><a class="page-link" href="/board/${menuId}/?page=${page - 1}">이전</a></li>`;
					}

					const startPage = Math.max(1, page - 2);
					const endPage = Math.min(totalPages, page + 2);

					for (let i = startPage; i <= endPage; i++) {
						html += `<li class="page-item ${i === page ? 'active' : ''}">
                            <a class="page-link" href="/board/${menuId}/?page=${i}">${i}</a>
                        </li>`;
					}

					if (page < totalPages) {
						html += `<li class="page-item"><a class="page-link" href="/board/${menuId}/?page=${page + 1}">다음</a></li>`;
					}

					html += '</ul></nav>';
				}
			} else {
				html += '<div class="text-center py-5"><p class="text-muted">등록된 게시글이 없습니다.</p></div>';
			}

			html += '</div>';
			mainContent.innerHTML = html;
		} else {
			mainContent.innerHTML = '<div class="text-center py-5"><p class="text-muted">게시판을 찾을 수 없습니다.</p></div>';
		}
	} catch (error) {
		console.error('게시판 로드 실패:', error);
		document.getElementById('mainContent').innerHTML = '<div class="text-center py-5"><p class="text-danger">게시판을 불러오는 중 오류가 발생했습니다.</p></div>';
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

			let html = '<div class="board-detail fade-in">';
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
			html += `<div class="board-content py-4">${item.board_content}</div>`;
			html += '<hr>';
			html += '<div class="d-flex gap-2">';
			html += `<a href="/board/${menuId}/" class="btn btn-secondary">목록으로</a>`;
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
		// 페이지 컨텐츠
		const menuId = path.replace('/page/', '').replace(/\/$/, '');
		loadPageContent(menuId);
	} else if (path.startsWith('/board/')) {
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
