/**
 * 파일 위치: assets/js/homepage_menu.js
 * 역할: 홈페이지 메뉴 설정 화면 스크립트
 */

let splitInstance = null;
let currentMenuId = null;
let currentMenuType = null;
let menuData = [];

$(document).ready(function() {
	initializePage();
});

/**
 * 페이지 초기화
 */
function initializePage() {
	const orgId = $('#current_org_id').val();
	if (!orgId) {
		return;
	}

	initializeSplit();
	loadMenuList();
	bindEvents();
}

/**
 * Split.js 초기화
 */
function initializeSplit() {
	try {
		splitInstance = Split(['#left-pane', '#right-pane'], {
			sizes: [30, 70],
			minSize: [200, 400],
			gutterSize: 7,
			cursor: 'col-resize',
			direction: 'horizontal',
			onDragEnd: function(sizes) {
				localStorage.setItem('homepage-menu-split-sizes', JSON.stringify(sizes));
			}
		});

		const savedSizes = localStorage.getItem('homepage-menu-split-sizes');
		if (savedSizes) {
			try {
				const sizes = JSON.parse(savedSizes);
				splitInstance.setSizes(sizes);
			} catch (error) {
				console.error('저장된 크기 복원 실패:', error);
			}
		}
	} catch (error) {
		console.error('Split.js 초기화 실패:', error);
	}
}

/**
 * 이벤트 바인딩
 */
function bindEvents() {
	$('#btnAddMenu').on('click', handleAddMenu);
	$('#btnSaveMenuEdit').on('click', handleSaveMenuEdit);
	$('#btnSaveBoard').on('click', handleSaveBoard);

	$(document).on('click', '.btn-add-submenu', handleAddSubMenu);
	$(document).on('click', '.btn-edit-menu', handleEditMenu);
	$(document).on('click', '.btn-delete-menu', handleDeleteMenu);
	$(document).on('click', '.menu-item-title', handleMenuClick);

	$(document).on('click', '#btnSaveLink', handleSaveLink);
	$(document).on('click', '#btnSavePage', handleSavePage);
	$(document).on('click', '#btnAddBoardItem', handleAddBoardItem);
	$(document).on('click', '.btn-board-edit', handleEditBoardItem);
	$(document).on('click', '.btn-board-delete', handleDeleteBoardItem);
	$(document).on('click', '#btnSearchBoard', handleSearchBoard);
}

/**
 * 메뉴 목록 로드
 */
function loadMenuList() {
	const orgId = $('#current_org_id').val();

	$.ajax({
		url: '/homepage_menu/get_menu_list',
		type: 'POST',
		dataType: 'json',
		data: { org_id: orgId },
		success: function(response) {
			if (response.success) {
				menuData = response.data;
				renderMenuList(menuData);
				initializeSortable();
			} else {
				showToast(response.message);
			}
		},
		error: function() {
			showToast('메뉴 목록을 불러오는데 실패했습니다.');
		}
	});
}

/**
 * 메뉴 목록 렌더링
 */
function renderMenuList(menus) {
	const $menuList = $('#menuList');
	$menuList.empty();

	if (!menus || menus.length === 0) {
		$menuList.append('<li class="list-group-item text-center text-muted">메뉴를 추가해주세요.</li>');
		return;
	}

	menus.forEach(function(menu) {
		const menuHtml = createMenuItemHtml(menu, false);
		$menuList.append(menuHtml);

		if (menu.children && menu.children.length > 0) {
			menu.children.forEach(function(child) {
				const childHtml = createMenuItemHtml(child, true);
				$menuList.append(childHtml);
			});
		}
	});
}

/**
 * 메뉴 아이템 HTML 생성
 */
function createMenuItemHtml(menu, isChild) {
	const typeLabel = getMenuTypeLabel(menu.type);
	const typeBadgeClass = getMenuTypeBadgeClass(menu.type);
	const childClass = isChild ? 'menu-item-child' : '';

	return `
		<li class="list-group-item ${childClass}" data-menu-id="${menu.id}" data-parent-id="${menu.parent_id || ''}">
			<div class="menu-item">
				<span class="menu-drag-handle" title="드래그하여 순서 변경">
					<i class="bi bi-grip-vertical"></i>
				</span>
				<span class="menu-item-title" style="cursor: pointer;">
					${escapeHtml(menu.name)}
					<span class="badge menu-badge ${typeBadgeClass}">${typeLabel}</span>
				</span>
				<div class="menu-item-buttons">
					${!isChild ? '<button type="button" class="btn btn-sm btn-outline-primary btn-add-submenu" title="하위메뉴 추가"><i class="bi bi-plus"></i></button>' : ''}
					<button type="button" class="btn btn-sm btn-outline-secondary btn-edit-menu" title="수정"><i class="bi bi-pencil"></i></button>
					<button type="button" class="btn btn-sm btn-outline-danger btn-delete-menu" title="삭제"><i class="bi bi-trash"></i></button>
				</div>
			</div>
		</li>
	`;
}

/**
 * 메뉴 타입 라벨 반환
 */
function getMenuTypeLabel(type) {
	const types = {
		'link': '링크',
		'page': '페이지',
		'board': '게시판'
	};
	return types[type] || type;
}

/**
 * 메뉴 타입별 뱃지 클래스 반환
 */
function getMenuTypeBadgeClass(type) {
	const badgeClasses = {
		'link': 'bg-primary',
		'page': 'bg-success',
		'board': 'bg-info'
	};
	return badgeClasses[type] || 'bg-secondary';
}

/**
 * Sortable 초기화
 */
function initializeSortable() {
	$('#menuList').sortable({
		handle: '.menu-drag-handle',
		placeholder: 'ui-sortable-placeholder',
		forcePlaceholderSize: true,
		tolerance: 'pointer',
		update: function(event, ui) {
			saveMenuOrder();
		},
		start: function(event, ui) {
			ui.placeholder.height(ui.item.height());
		}
	});
}

/**
 * 메뉴 순서 저장
 */
function saveMenuOrder() {
	const newMenuData = [];
	let currentParent = null;

	$('#menuList li').each(function() {
		const $item = $(this);
		const menuId = $item.data('menu-id');
		const parentId = $item.data('parent-id');

		if (!menuId) return;

		const menuItem = findMenuById(menuId);
		if (!menuItem) return;

		if (!parentId) {
			currentParent = { ...menuItem, children: [] };
			newMenuData.push(currentParent);
		} else {
			if (currentParent) {
				currentParent.children.push(menuItem);
			}
		}
	});

	menuData = newMenuData;
	saveMenuToServer();
}

/**
 * 메뉴 ID로 찾기
 */
function findMenuById(menuId, menus = menuData) {
	for (let menu of menus) {
		if (menu.id === menuId) {
			return menu;
		}
		if (menu.children) {
			const found = findMenuById(menuId, menu.children);
			if (found) return found;
		}
	}
	return null;
}

/**
 * 메뉴 추가 핸들러
 */
function handleAddMenu() {
	$('#menuEditModalLabel').text('메뉴 추가');
	$('#edit_menu_id').val('');
	$('#edit_parent_id').val('');
	$('#edit_menu_type').val('link');
	$('#edit_menu_name').val('');

	const menuEditModal = new bootstrap.Modal(document.getElementById('menuEditModal'));
	menuEditModal.show();
}

/**
 * 하위 메뉴 추가 핸들러
 */
function handleAddSubMenu(e) {
	e.stopPropagation();

	const $item = $(this).closest('li');
	const parentId = $item.data('menu-id');

	$('#menuEditModalLabel').text('하위메뉴 추가');
	$('#edit_menu_id').val('');
	$('#edit_parent_id').val(parentId);
	$('#edit_menu_type').val('link');
	$('#edit_menu_name').val('');

	const menuEditModal = new bootstrap.Modal(document.getElementById('menuEditModal'));
	menuEditModal.show();
}

/**
 * 메뉴 수정 핸들러
 */
function handleEditMenu(e) {
	e.stopPropagation();

	const $item = $(this).closest('li');
	const menuId = $item.data('menu-id');
	const menu = findMenuById(menuId);

	if (!menu) {
		showToast('메뉴 정보를 찾을 수 없습니다.');
		return;
	}

	$('#menuEditModalLabel').text('메뉴 수정');
	$('#edit_menu_id').val(menu.id);
	$('#edit_parent_id').val(menu.parent_id || '');
	$('#edit_menu_type').val(menu.type);
	$('#edit_menu_name').val(menu.name);

	const menuEditModal = new bootstrap.Modal(document.getElementById('menuEditModal'));
	menuEditModal.show();
}

/**
 * 메뉴 삭제 핸들러
 */
function handleDeleteMenu(e) {
	e.stopPropagation();

	const $item = $(this).closest('li');
	const menuId = $item.data('menu-id');
	const menu = findMenuById(menuId);

	if (!menu) {
		showToast('메뉴 정보를 찾을 수 없습니다.');
		return;
	}

	if (menu.children && menu.children.length > 0) {
		showToast('하위 메뉴가 있는 메뉴는 삭제할 수 없습니다.');
		return;
	}

	showConfirmModal('메뉴 삭제', '정말 삭제하시겠습니까?', function() {
		deleteMenu(menuId);
	});
}

/**
 * 메뉴 삭제
 */
function deleteMenu(menuId) {
	function removeMenuById(menus, id) {
		for (let i = 0; i < menus.length; i++) {
			if (menus[i].id === id) {
				menus.splice(i, 1);
				return true;
			}
			if (menus[i].children) {
				if (removeMenuById(menus[i].children, id)) {
					return true;
				}
			}
		}
		return false;
	}

	if (removeMenuById(menuData, menuId)) {
		saveMenuToServer();
		renderMenuList(menuData);
		initializeSortable();
		showToast('메뉴가 삭제되었습니다.');

		if (currentMenuId === menuId) {
			clearContentArea();
		}
	}
}

/**
 * 메뉴 수정 저장 핸들러
 */
function handleSaveMenuEdit() {
	const menuId = $('#edit_menu_id').val();
	const parentId = $('#edit_parent_id').val();
	const menuType = $('#edit_menu_type').val();
	const menuName = $('#edit_menu_name').val().trim();

	if (!menuName) {
		showToast('메뉴 이름을 입력해주세요.');
		return;
	}

	if (menuId) {
		updateMenu(menuId, menuType, menuName);
	} else {
		addMenu(parentId, menuType, menuName);
	}

	const menuEditModal = bootstrap.Modal.getInstance(document.getElementById('menuEditModal'));
	menuEditModal.hide();
}

/**
 * 메뉴 추가
 */
function addMenu(parentId, menuType, menuName) {
	const newMenu = {
		id: 'menu_' + Date.now(),
		name: menuName,
		type: menuType,
		parent_id: parentId || null,
		children: []
	};

	if (parentId) {
		const parentMenu = findMenuById(parentId);
		if (parentMenu) {
			if (!parentMenu.children) {
				parentMenu.children = [];
			}
			parentMenu.children.push(newMenu);
		}
	} else {
		menuData.push(newMenu);
	}

	saveMenuToServer();
	renderMenuList(menuData);
	initializeSortable();
	showToast('메뉴가 추가되었습니다.');
}

/**
 * 메뉴 업데이트
 */
function updateMenu(menuId, menuType, menuName) {
	const menu = findMenuById(menuId);
	if (menu) {
		menu.type = menuType;
		menu.name = menuName;

		saveMenuToServer();
		renderMenuList(menuData);
		initializeSortable();
		showToast('메뉴가 수정되었습니다.');
	}
}

/**
 * 메뉴 서버 저장
 */
function saveMenuToServer() {
	const orgId = $('#current_org_id').val();
	const menuJson = JSON.stringify(menuData);

	$.ajax({
		url: '/homepage_menu/save_menu',
		type: 'POST',
		dataType: 'json',
		data: {
			org_id: orgId,
			menu_json: menuJson
		},
		success: function(response) {
			if (!response.success) {
				showToast(response.message);
			}
		},
		error: function() {
			showToast('메뉴 저장에 실패했습니다.');
		}
	});
}

/**
 * 메뉴 클릭 핸들러
 */
function handleMenuClick(e) {
	e.stopPropagation();

	const $item = $(this).closest('li');
	const menuId = $item.data('menu-id');
	const menu = findMenuById(menuId);

	if (!menu) {
		showToast('메뉴 정보를 찾을 수 없습니다.');
		return;
	}

	$('#menuList li').removeClass('active');
	$item.addClass('active');

	currentMenuId = menuId;
	currentMenuType = menu.type;

	loadMenuContent(menu);
}

/**
 * 메뉴 컨텐츠 로드
 */
function loadMenuContent(menu) {
	switch (menu.type) {
		case 'link':
			loadLinkContent(menu.id);
			break;
		case 'page':
			loadPageContent(menu.id);
			break;
		case 'board':
			loadBoardContent(menu.id);
			break;
	}
}

/**
 * 링크 컨텐츠 로드
 */
function loadLinkContent(menuId) {
	const orgId = $('#current_org_id').val();

	$.ajax({
		url: '/homepage_menu/get_link_info',
		type: 'POST',
		dataType: 'json',
		data: {
			org_id: orgId,
			menu_id: menuId
		},
		success: function(response) {
			if (response.success) {
				renderLinkContent(response.data);
			} else {
				renderLinkContent(null);
			}
		},
		error: function() {
			showToast('링크 정보를 불러오는데 실패했습니다.');
		}
	});
}

/**
 * 링크 컨텐츠 렌더링
 */
function renderLinkContent(data) {
	const linkUrl = data ? data.link_url : '';
	const linkTarget = data ? data.link_target : '_self';

	const html = `
		<h5 class="mb-3">링크 설정</h5>
		<div class="mb-3">
			<label for="link_url" class="form-label">URL링크</label>
			<input type="text" class="form-control" id="link_url" value="${escapeHtml(linkUrl)}" placeholder="https://example.com">
		</div>
		<div class="mb-3">
			<label for="link_target" class="form-label">타겟</label>
			<select class="form-select" id="link_target">
				<option value="_self" ${linkTarget === '_self' ? 'selected' : ''}>자체이동</option>
				<option value="_blank" ${linkTarget === '_blank' ? 'selected' : ''}>새로운창으로 이동</option>
			</select>
		</div>
		<hr>
		<button type="button" class="btn btn-primary" id="btnSaveLink">저장</button>
	`;

	$('#contentArea').html(html);
}

/**
 * 링크 저장 핸들러
 */
function handleSaveLink() {
	const orgId = $('#current_org_id').val();
	const linkUrl = $('#link_url').val().trim();
	const linkTarget = $('#link_target').val();

	$.ajax({
		url: '/homepage_menu/save_link',
		type: 'POST',
		dataType: 'json',
		data: {
			org_id: orgId,
			menu_id: currentMenuId,
			link_url: linkUrl,
			link_target: linkTarget
		},
		success: function(response) {
			if (response.success) {
				showToast(response.message);
			} else {
				showToast(response.message);
			}
		},
		error: function() {
			showToast('링크 저장에 실패했습니다.');
		}
	});
}

/**
 * 페이지 컨텐츠 로드
 */
function loadPageContent(menuId) {
	const orgId = $('#current_org_id').val();

	$.ajax({
		url: '/homepage_menu/get_page_info',
		type: 'POST',
		dataType: 'json',
		data: {
			org_id: orgId,
			menu_id: menuId
		},
		success: function(response) {
			if (response.success) {
				renderPageContent(response.data);
			} else {
				renderPageContent(null);
			}
		},
		error: function() {
			showToast('페이지 정보를 불러오는데 실패했습니다.');
		}
	});
}

/**
 * 페이지 컨텐츠 렌더링
 */
function renderPageContent(data) {
	const pageContent = data ? data.page_content : '';

	const html = `
		<h5 class="mb-3">페이지 설정</h5>
		<div class="mb-3">
			<label for="page_content" class="form-label">페이지 내용 (HTML)</label>
			<textarea class="form-control" id="page_content" rows="15" placeholder="HTML 태그를 입력하세요">${escapeHtml(pageContent)}</textarea>
		</div>
		<hr>
		<button type="button" class="btn btn-primary" id="btnSavePage">저장</button>
	`;

	$('#contentArea').html(html);
}

/**
 * 페이지 저장 핸들러
 */
function handleSavePage() {
	const orgId = $('#current_org_id').val();
	const pageContent = $('#page_content').val();

	$.ajax({
		url: '/homepage_menu/save_page',
		type: 'POST',
		dataType: 'json',
		data: {
			org_id: orgId,
			menu_id: currentMenuId,
			page_content: pageContent
		},
		success: function(response) {
			if (response.success) {
				showToast(response.message);
			} else {
				showToast(response.message);
			}
		},
		error: function() {
			showToast('페이지 저장에 실패했습니다.');
		}
	});
}

/**
 * 게시판 컨텐츠 로드
 */
function loadBoardContent(menuId) {
	const orgId = $('#current_org_id').val();

	$.ajax({
		url: '/homepage_menu/get_board_list',
		type: 'POST',
		dataType: 'json',
		data: {
			org_id: orgId,
			menu_id: menuId,
			search_keyword: '',
			page: 1
		},
		success: function(response) {
			if (response.success) {
				renderBoardContent(response.data, response.total);
			} else {
				showToast(response.message);
			}
		},
		error: function() {
			showToast('게시판 목록을 불러오는데 실패했습니다.');
		}
	});
}

/**
 * 게시판 컨텐츠 렌더링
 */
function renderBoardContent(boardList, total) {
	let tableRows = '';

	if (boardList && boardList.length > 0) {
		boardList.forEach(function(board) {
			tableRows += `
				<tr>
					<td><input type="checkbox" class="form-check-input" value="${board.idx}"></td>
					<td>${escapeHtml(board.board_title)}</td>
					<td>${board.view_count}</td>
					<td>${formatDate(board.reg_date)}</td>
					<td>${escapeHtml(board.writer_name || '')}</td>
					<td>${board.modi_date ? formatDate(board.modi_date) : ''}</td>
					<td>${escapeHtml(board.modifier_name || '')}</td>
					<td>
						<button type="button" class="btn btn-sm btn-outline-primary btn-board-edit" data-idx="${board.idx}">수정</button>
					</td>
				</tr>
			`;
		});
	} else {
		tableRows = '<tr><td colspan="8" class="text-center text-muted">등록된 게시글이 없습니다.</td></tr>';
	}

	const html = `
		<div class="d-flex justify-content-between align-items-center mb-3">
			<h5 class="mb-0">게시판 관리</h5>
			<button type="button" class="btn btn-sm btn-primary" id="btnAddBoardItem">
				<i class="bi bi-plus-circle"></i> 글쓰기
			</button>
		</div>
		<div class="d-flex justify-content-between align-items-center mb-2">
			<div>전체 ${total}건</div>
			<div class="input-group board-search-box">
				<input type="text" class="form-control form-control-sm" id="board_search_keyword" placeholder="검색어 입력">
				<button class="btn btn-sm btn-outline-secondary" type="button" id="btnSearchBoard">검색</button>
			</div>
		</div>
		<div class="table-responsive">
			<table class="table table-sm table-hover board-table">
				<thead>
					<tr>
						<th style="width: 40px;"><input type="checkbox" class="form-check-input" id="selectAllBoard"></th>
						<th>제목</th>
						<th style="width: 80px;">조회수</th>
						<th style="width: 100px;">작성일</th>
						<th style="width: 100px;">작성자</th>
						<th style="width: 100px;">수정일</th>
						<th style="width: 100px;">수정자</th>
						<th style="width: 80px;">관리</th>
					</tr>
				</thead>
				<tbody>
					${tableRows}
				</tbody>
			</table>
		</div>
	`;

	$('#contentArea').html(html);
}

/**
 * 게시글 추가 핸들러
 */
function handleAddBoardItem() {
	$('#boardOffcanvasLabel').text('게시글 작성');
	$('#board_idx').val('');
	$('#board_title').val('');
	$('#board_content').val('');

	const boardOffcanvas = new bootstrap.Offcanvas(document.getElementById('boardOffcanvas'));
	boardOffcanvas.show();
}

/**
 * 게시글 수정 핸들러
 */
function handleEditBoardItem() {
	const idx = $(this).data('idx');

	$.ajax({
		url: '/homepage_menu/get_board_detail',
		type: 'POST',
		dataType: 'json',
		data: { idx: idx },
		success: function(response) {
			if (response.success && response.data) {
				$('#boardOffcanvasLabel').text('게시글 수정');
				$('#board_idx').val(response.data.idx);
				$('#board_title').val(response.data.board_title);
				$('#board_content').val(response.data.board_content);

				const boardOffcanvas = new bootstrap.Offcanvas(document.getElementById('boardOffcanvas'));
				boardOffcanvas.show();
			} else {
				showToast('게시글 정보를 불러오는데 실패했습니다.');
			}
		},
		error: function() {
			showToast('게시글 정보를 불러오는데 실패했습니다.');
		}
	});
}

/**
 * 게시글 삭제 핸들러
 */
function handleDeleteBoardItem() {
	const idx = $(this).data('idx');

	showConfirmModal('게시글 삭제', '정말 삭제하시겠습니까?', function() {
		$.ajax({
			url: '/homepage_menu/delete_board',
			type: 'POST',
			dataType: 'json',
			data: { idx: idx },
			success: function(response) {
				if (response.success) {
					showToast(response.message);
					loadBoardContent(currentMenuId);
				} else {
					showToast(response.message);
				}
			},
			error: function() {
				showToast('게시글 삭제에 실패했습니다.');
			}
		});
	});
}

/**
 * 게시글 저장 핸들러
 */
function handleSaveBoard() {
	const orgId = $('#current_org_id').val();
	const idx = $('#board_idx').val();
	const boardTitle = $('#board_title').val().trim();
	const boardContent = $('#board_content').val();

	if (!boardTitle) {
		showToast('제목을 입력해주세요.');
		return;
	}

	$.ajax({
		url: '/homepage_menu/save_board',
		type: 'POST',
		dataType: 'json',
		data: {
			org_id: orgId,
			menu_id: currentMenuId,
			idx: idx,
			board_title: boardTitle,
			board_content: boardContent
		},
		success: function(response) {
			if (response.success) {
				showToast(response.message);

				const boardOffcanvas = bootstrap.Offcanvas.getInstance(document.getElementById('boardOffcanvas'));
				boardOffcanvas.hide();

				loadBoardContent(currentMenuId);
			} else {
				showToast(response.message);
			}
		},
		error: function() {
			showToast('게시글 저장에 실패했습니다.');
		}
	});
}

/**
 * 게시판 검색 핸들러
 */
function handleSearchBoard() {
	const orgId = $('#current_org_id').val();
	const searchKeyword = $('#board_search_keyword').val().trim();

	$.ajax({
		url: '/homepage_menu/get_board_list',
		type: 'POST',
		dataType: 'json',
		data: {
			org_id: orgId,
			menu_id: currentMenuId,
			search_keyword: searchKeyword,
			page: 1
		},
		success: function(response) {
			if (response.success) {
				renderBoardContent(response.data, response.total);
			} else {
				showToast(response.message);
			}
		},
		error: function() {
			showToast('검색에 실패했습니다.');
		}
	});
}

/**
 * 컨텐츠 영역 초기화
 */
function clearContentArea() {
	currentMenuId = null;
	currentMenuType = null;

	const html = `
		<div class="text-center text-muted py-5">
			<i class="bi bi-hand-index display-1"></i>
			<p class="mt-3">왼쪽에서 메뉴를 선택해주세요.</p>
		</div>
	`;

	$('#contentArea').html(html);
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

/**
 * HTML 이스케이프
 */
function escapeHtml(text) {
	if (!text) return '';

	const map = {
		'&': '&amp;',
		'<': '&lt;',
		'>': '&gt;',
		'"': '&quot;',
		"'": '&#039;'
	};

	return text.replace(/[&<>"']/g, function(m) { return map[m]; });
}
