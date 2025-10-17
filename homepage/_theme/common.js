/**
 * 파일 위치: /var/www/wani/public/_theme/common.js
 * 역할: 모든 테마에서 공통으로 사용하는 JavaScript
 */

const API_BASE_URL = 'https://wani.im/api/homepage';
let ORG_CODE = '';
let orgInfo = null;

// 현재 URL에서 조직 코드 추출
function getOrgCodeFromUrl() {
	const hostname = window.location.hostname;
	// 로컬 테스트용
	if (hostname === 'localhost' || hostname === '127.0.0.1') {
		return 'TEST001'; // 테스트용 기본값
	}
	// 실제 도메인에서 조직 정보 조회는 서버에서 처리
	return null;
}

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

// 조직 정보 로드
async function loadOrgInfo() {
	try {
		const response = await fetch(`${API_BASE_URL}/org/${ORG_CODE}`);
		const result = await response.json();

		if (result.success && result.data) {
			orgInfo = result.data;
			applyOrgInfo(orgInfo);
			return orgInfo;
		} else {
			console.error('조직 정보를 찾을 수 없습니다.');
			return null;
		}
	} catch (error) {
		console.error('조직 정보 로드 실패:', error);
		return null;
	}
}

// 조직 정보 적용
function applyOrgInfo(info) {
	const setting = info.homepage_setting || {};
	const homepageName = setting.homepage_name || info.org_name;

	// 타이틀 설정
	document.title = homepageName;

	// 페이지 내 조직명 설정
	const nameElements = document.querySelectorAll('#homepageName, #footerOrgName, #footerCopyright');
	nameElements.forEach(el => {
		if (el) el.textContent = homepageName;
	});

	// 조직 코드 설정
	const codeElement = document.getElementById('footerOrgCode');
	if (codeElement) codeElement.textContent = info.org_code;

	// 현재 연도 설정
	const yearElement = document.getElementById('currentYear');
	if (yearElement) yearElement.textContent = new Date().getFullYear();

	// 로고 설정
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
		const hasChildren = menus.some(m => m.parent_id === menu.id);

		if (!menu.parent_id) {
			if (hasChildren) {
				html += `
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            ${escapeHtml(menu.name)}
                        </a>
                        <ul class="dropdown-menu">`;

				menus.forEach(child => {
					if (child.parent_id === menu.id) {
						html += `<li><a class="dropdown-item" href="#" data-menu-id="${child.id}" data-menu-type="${child.type}">${escapeHtml(child.name)}</a></li>`;
					}
				});

				html += `</ul></li>`;
			} else {
				html += `<li class="nav-item"><a class="nav-link" href="#" data-menu-id="${menu.id}" data-menu-type="${menu.type}">${escapeHtml(menu.name)}</a></li>`;
			}
		}
	});

	return html;
}

// 메뉴 데이터 로드
async function loadMenu() {
	try {
		const response = await fetch(`${API_BASE_URL}/menu/${ORG_CODE}`);
		const result = await response.json();

		if (result.success && result.data) {
			const menuHtml = generateMenuHtml(result.data);
			document.getElementById('mainMenu').innerHTML = menuHtml;

			// 메뉴 클릭 이벤트 바인딩
			document.querySelectorAll('[data-menu-id]').forEach(link => {
				link.addEventListener('click', (e) => {
					e.preventDefault();
					const menuId = e.target.dataset.menuId;
					const menuType = e.target.dataset.menuType;
					loadContent(menuId, menuType);
				});
			});
		} else {
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
		const response = await fetch(`${API_BASE_URL}/page/${ORG_CODE}/${menuId}`);
		const result = await response.json();

		if (result.success && result.data) {
			document.getElementById('mainContent').innerHTML = result.data.page_content || '<div class="text-center py-5"><p class="text-muted">페이지 내용이 없습니다.</p></div>';
		} else {
			document.getElementById('mainContent').innerHTML = '<div class="text-center py-5"><p class="text-muted">페이지를 찾을 수 없습니다.</p></div>';
		}
	} catch (error) {
		console.error('페이지 로드 실패:', error);
		document.getElementById('mainContent').innerHTML = '<div class="text-center py-5"><p class="text-danger">페이지를 불러오는 중 오류가 발생했습니다.</p></div>';
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
async function loadBoardContent(menuId, page = 1) {
	try {
		const response = await fetch(`${API_BASE_URL}/board/${ORG_CODE}/${menuId}?page=${page}&limit=20`);
		const result = await response.json();

		if (result.success) {
			let html = '<div class="board-container">';
			html += '<h4 class="mb-4">게시판</h4>';

			if (result.data && result.data.length > 0) {
				html += '<div class="table-responsive"><table class="table table-hover">';
				html += '<thead><tr><th>번호</th><th>제목</th><th>작성자</th><th>조회수</th><th>작성일</th></tr></thead>';
				html += '<tbody>';

				result.data.forEach((item, index) => {
					const num = result.total - ((page - 1) * result.limit) - index;
					const date = new Date(item.reg_date).toLocaleDateString('ko-KR');
					html += `<tr style="cursor:pointer" onclick="loadBoardDetail('${menuId}', ${item.idx})">
                        <td>${num}</td>
                        <td class="text-start">${escapeHtml(item.board_title)}</td>
                        <td>${escapeHtml(item.writer_name || '')}</td>
                        <td>${item.view_count}</td>
                        <td>${date}</td>
                    </tr>`;
				});

				html += '</tbody></table></div>';

				// 페이징
				if (result.total > result.limit) {
					const totalPages = Math.ceil(result.total / result.limit);
					html += '<nav><ul class="pagination justify-content-center">';

					for (let i = 1; i <= totalPages; i++) {
						html += `<li class="page-item ${i === page ? 'active' : ''}">
                            <a class="page-link" href="#" onclick="loadBoardContent('${menuId}', ${i}); return false;">${i}</a>
                        </li>`;
					}

					html += '</ul></nav>';
				}
			} else {
				html += '<div class="text-center py-5"><p class="text-muted">등록된 게시글이 없습니다.</p></div>';
			}

			html += '</div>';
			document.getElementById('mainContent').innerHTML = html;
		} else {
			document.getElementById('mainContent').innerHTML = '<div class="text-center py-5"><p class="text-muted">게시판을 찾을 수 없습니다.</p></div>';
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

		if (result.success && result.data) {
			const item = result.data;
			const date = new Date(item.reg_date).toLocaleDateString('ko-KR');

			let html = '<div class="board-detail">';
			html += `<h4 class="mb-3">${escapeHtml(item.board_title)}</h4>`;
			html += `<div class="mb-3 text-muted">
                <small>작성자: ${escapeHtml(item.writer_name || '')}</small> | 
                <small>작성일: ${date}</small> | 
                <small>조회수: ${item.view_count}</small>
            </div>`;
			html += '<hr>';
			html += `<div class="board-content py-4">${item.board_content}</div>`;
			html += '<hr>';
			html += `<button class="btn btn-secondary" onclick="loadBoardContent('${menuId}')">목록으로</button>`;
			html += '</div>';

			document.getElementById('mainContent').innerHTML = html;
		} else {
			alert('게시글을 찾을 수 없습니다.');
		}
	} catch (error) {
		console.error('게시글 로드 실패:', error);
		alert('게시글을 불러오는 중 오류가 발생했습니다.');
	}
}

// 컨텐츠 로드 (타입별 분기)
function loadContent(menuId, menuType) {
	switch(menuType) {
		case 'page':
			loadPageContent(menuId);
			break;
		case 'link':
			loadLinkContent(menuId);
			break;
		case 'board':
			loadBoardContent(menuId);
			break;
		default:
			document.getElementById('mainContent').innerHTML = '<div class="text-center py-5"><p class="text-muted">지원하지 않는 메뉴 타입입니다.</p></div>';
	}
}

// 페이지 초기화
async function initializePage() {
	// URL에서 조직 코드 추출 또는 설정
	ORG_CODE = getOrgCodeFromUrl();

	if (!ORG_CODE) {
		// 조직 코드를 찾을 수 없는 경우 처리
		document.getElementById('mainContent').innerHTML = '<div class="text-center py-5"><p class="text-danger">조직 정보를 찾을 수 없습니다.</p></div>';
		return;
	}

	// 조직 정보 로드
	await loadOrgInfo();

	// 메뉴 로드
	await loadMenu();

	// 메인 페이지 로드
	await loadPageContent('main');
}

// 페이지 로드 시 초기화
document.addEventListener('DOMContentLoaded', initializePage);
