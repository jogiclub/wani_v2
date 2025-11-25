/**
 * 파일 위치: /var/www/wani/public/assets/js/homepage/wani-preach-frontend.js
 * 역할: 홈페이지에서 게시판 블록 동적 로드 (실제 사이트용)
 */

(function() {
	'use strict';

	// ORG_CODE는 index.html에서 전역으로 정의됨
	if (typeof ORG_CODE === 'undefined') {
		console.error('ORG_CODE가 정의되지 않았습니다.');
		return;
	}

	const API_BASE_URL = 'https://wani.im/api/homepage_api';

	/**
	 * 페이지 로드 시 모든 wani-preach 블록 초기화
	 */
	document.addEventListener('DOMContentLoaded', function() {
		const preachBlocks = document.querySelectorAll('.wani-preach-block');

		preachBlocks.forEach(function(block) {
			const menuId = block.getAttribute('data-menu-id');
			const limit = parseInt(block.getAttribute('data-limit')) || 5;

			if (menuId) {
				loadBoardList(block, menuId, limit);
			}
		});
	});

	/**
	 * 게시판 목록 로드
	 */
	function loadBoardList(blockElement, menuId, limit) {
		const listContainer = blockElement.querySelector('.wani-preach-list');
		const titleElement = blockElement.querySelector('.wani-preach-title');

		// API 호출
		fetch(`${API_BASE_URL}/get_board_list/${ORG_CODE}/${menuId}?limit=${limit}`)
			.then(response => response.json())
			.then(data => {
				if (data.success && data.data && data.data.length > 0) {
					// 게시판 이름 업데이트 (메뉴 정보에서 가져오기)
					loadMenuName(menuId, titleElement);

					// 게시물 목록 렌더링
					renderBoardList(listContainer, data.data, menuId);
				} else {
					// 게시물이 없는 경우
					renderEmptyState(listContainer);
				}
			})
			.catch(error => {
				console.error('게시판 로드 실패:', error);
				renderErrorState(listContainer);
			});
	}

	/**
	 * 메뉴 이름 로드
	 */
	function loadMenuName(menuId, titleElement) {
		fetch(`${API_BASE_URL}/get_menu/${ORG_CODE}`)
			.then(response => response.json())
			.then(data => {
				if (data.success && data.data) {
					const menu = findMenuById(data.data, menuId);
					if (menu && menu.name) {
						titleElement.textContent = menu.name;
					}
				}
			})
			.catch(error => {
				console.error('메뉴 이름 로드 실패:', error);
			});
	}

	/**
	 * 메뉴 트리에서 ID로 메뉴 찾기
	 */
	function findMenuById(menus, menuId) {
		for (let menu of menus) {
			if (menu.id === menuId) {
				return menu;
			}
			if (menu.children) {
				const found = findMenuById(menu.children, menuId);
				if (found) return found;
			}
		}
		return null;
	}

	/**
	 * 게시물 목록 렌더링
	 */
	function renderBoardList(container, boardList, menuId) {
		container.innerHTML = '';

		boardList.forEach(function(board) {
			const li = document.createElement('li');
			li.className = 'list-group-item d-flex justify-content-between align-items-center py-2';
			li.style.cursor = 'pointer';

			// 클릭 이벤트
			li.addEventListener('click', function() {
				window.location.href = `/board/${menuId}/${board.idx}`;
			});

			// 제목
			const titleSpan = document.createElement('span');
			titleSpan.className = 'text-truncate me-2';
			titleSpan.textContent = board.board_title || '';

			// 날짜
			const dateSmall = document.createElement('small');
			dateSmall.className = 'text-muted text-nowrap';
			dateSmall.textContent = formatDate(board.reg_date);

			li.appendChild(titleSpan);
			li.appendChild(dateSmall);
			container.appendChild(li);
		});
	}

	/**
	 * 빈 상태 렌더링
	 */
	function renderEmptyState(container) {
		container.innerHTML = `
			<li class="list-group-item text-center py-4 text-muted">
				<i class="bi bi-inbox fs-3 d-block mb-2"></i>
				등록된 게시물이 없습니다.
			</li>
		`;
	}

	/**
	 * 에러 상태 렌더링
	 */
	function renderErrorState(container) {
		container.innerHTML = `
			<li class="list-group-item text-center py-4 text-danger">
				<i class="bi bi-exclamation-triangle fs-3 d-block mb-2"></i>
				게시물을 불러올 수 없습니다.
			</li>
		`;
	}

	/**
	 * 날짜 포맷팅
	 */
	function formatDate(dateString) {
		if (!dateString) return '';

		const date = new Date(dateString);
		const year = date.getFullYear();
		const month = String(date.getMonth() + 1).padStart(2, '0');
		const day = String(date.getDate()).padStart(2, '0');

		return `${year}-${month}-${day}`;
	}

})();
