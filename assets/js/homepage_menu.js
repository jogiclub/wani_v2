/**
 * 파일 위치: assets/js/homepage_menu.js
 * 역할: 홈페이지 메뉴 설정 화면 스크립트
 */

let splitInstance = null;
let currentMenuId = null;
let currentMenuType = null;
let menuData = [];
let pageContentEditor = null;  // Editor.js 인스턴스 추가
let boardDropzone = null;
let uploadedFileList = [];
let currentBoardPage = 1;
let currentSearchKeyword = '';

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
	$(document).on('click', '#btnDeleteBoard', handleDeleteBoardInOffcanvas); // Offcanvas 삭제 버튼
	$(document).on('click', '#btnSearchBoard', handleSearchBoard);
	$(document).on('click', '#btnDeleteSelected', handleDeleteSelected);

	// 검색 입력창에서 엔터키 처리
	$(document).on('keypress', '#board_search_keyword', function(e) {
		if (e.which === 13) {
			e.preventDefault();
			handleSearchBoard();
		}
	});
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

	// 메인화면 메뉴 (상시 최상단 고정)
	const mainMenuHtml = `
		<li class="list-group-item menu-item-main" data-menu-id="main" data-parent-id="">
			<div class="menu-item">
				<span class="menu-item-title text-truncate" style="cursor: pointer;">
					메인화면
					<span class="badge menu-badge bg-success">페이지</span>
				</span>
				<div class="menu-item-buttons btn-group">
					<button type="button" class="btn btn-sm btn-outline-secondary btn-edit-menu" title="수정"><i class="bi bi-pencil"></i></button>
				</div>
			</div>
		</li>
	`;
	$menuList.append(mainMenuHtml);

	if (!menus || menus.length === 0) {
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
				<span class="menu-item-title text-truncate" style="cursor: pointer;">
					${escapeHtml(menu.name)}
					<span class="badge menu-badge ${typeBadgeClass}">${typeLabel}</span>
				</span>
				<div class="menu-item-buttons btn-group">
					${!isChild ? '<button type="button" class="btn btn-sm btn-primary btn-add-submenu" title="하위메뉴 추가"><i class="bi bi-plus-lg"></i></button>' : ''}
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
		'board': 'bg-warning'
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
		items: 'li:not(.menu-item-main)',
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

	// 메인화면 메뉴인 경우
	if (menuId === 'main') {
		loadPageContent('main');
		$('#menuList li').removeClass('active');
		$item.addClass('active');
		currentMenuId = 'main';
		currentMenuType = 'page';
		return;
	}

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

	// 메인화면 메뉴인 경우
	if (menuId === 'main') {
		$('#menuList li').removeClass('active');
		$item.addClass('active');

		currentMenuId = 'main';
		currentMenuType = 'page';

		loadPageContent('main');
		return;
	}

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
			<label for="page_content" class="form-label">페이지 내용</label>
			<div id="page_content_editor" style="border: 1px solid #dee2e6; border-radius: 0.375rem; padding: 15px; min-height: 400px;"></div>
		</div>
		<hr>
		<button type="button" class="btn btn-primary" id="btnSavePage">저장</button>
	`;

	$('#contentArea').html(html);


		initPageContentEditor(pageContent);

}

/**
 * Editor.js 초기화
 */
function initPageContentEditor(content) {
	// 기존 에디터 인스턴스가 있으면 제거
	if (pageContentEditor) {
		pageContentEditor.destroy();
		pageContentEditor = null;
	}

	// 저장된 데이터 파싱
	let parsedData = null;
	if (content) {
		try {
			parsedData = JSON.parse(content);
		} catch (e) {
			parsedData = {
				blocks: [{
					type: 'paragraph',
					data: { text: content }
				}]
			};
		}
	}

	// EditorJS가 로드되었는지 확인
	if (typeof EditorJS === 'undefined') {
		console.error('EditorJS가 로드되지 않았습니다.');
		showToast('에디터를 불러올 수 없습니다.');
		return;
	}

	// 사용 가능한 도구 확인 및 설정
	const availableTools = {};

	// Header
	if (typeof window.Header !== 'undefined') {
		availableTools.header = {
			class: window.Header,
			config: {
				placeholder: '제목을 입력하세요',
				levels: [1, 2, 3, 4, 5, 6],
				defaultLevel: 2
			},
			inlineToolbar: true
		};
	}

	// Paragraph
	if (typeof window.Paragraph !== 'undefined') {
		availableTools.paragraph = {
			class: window.Paragraph,
			inlineToolbar: true
		};
	}

	// List
	if (typeof window.List !== 'undefined') {
		availableTools.list = {
			class: window.List,
			inlineToolbar: true,
			config: {
				defaultStyle: 'unordered'
			}
		};
	}

	// Nested List
	if (typeof window.NestedList !== 'undefined') {
		availableTools.nestedList = {
			class: window.NestedList,
			inlineToolbar: true
		};
	}

	// Checklist
	if (typeof window.Checklist !== 'undefined') {
		availableTools.checklist = {
			class: window.Checklist,
			inlineToolbar: true
		};
	}

	// Quote
	if (typeof window.Quote !== 'undefined') {
		availableTools.quote = {
			class: window.Quote,
			inlineToolbar: true,
			config: {
				quotePlaceholder: '인용문을 입력하세요',
				captionPlaceholder: '출처'
			}
		};
	}

	// Code
	if (typeof window.CodeTool !== 'undefined') {
		availableTools.code = {
			class: window.CodeTool,
			placeholder: '코드를 입력하세요'
		};
	}

	// Image
	if (typeof window.ImageTool !== 'undefined') {
		availableTools.image = {
			class: window.ImageTool,
			config: {
				endpoints: {
					byFile: '/homepage_menu/upload_image',
					byUrl: '/homepage_menu/fetch_url_image'
				}
			}
		};
	}

	// Embed
	if (typeof window.Embed !== 'undefined') {
		availableTools.embed = {
			class: window.Embed,
			config: {
				services: {
					youtube: true,
					vimeo: true,
					facebook: true,
					instagram: true,
					twitter: true
				}
			}
		};
	}

	// Link Tool
	if (typeof window.LinkTool !== 'undefined') {
		availableTools.linkTool = {
			class: window.LinkTool,
			config: {
				endpoint: '/homepage_menu/fetch_url_meta'
			}
		};
	}

	// Attaches
	if (typeof window.AttachesTool !== 'undefined') {
		availableTools.attaches = {
			class: window.AttachesTool,
			config: {
				endpoint: '/homepage_menu/upload_file'
			}
		};
	}

	// Table
	if (typeof window.Table !== 'undefined') {
		availableTools.table = {
			class: window.Table,
			inlineToolbar: true,
			config: {
				rows: 2,
				cols: 3
			}
		};
	}

	// Delimiter
	if (typeof window.Delimiter !== 'undefined') {
		availableTools.delimiter = window.Delimiter;
	}

	// Warning
	if (typeof window.Warning !== 'undefined') {
		availableTools.warning = {
			class: window.Warning,
			inlineToolbar: true,
			config: {
				titlePlaceholder: '제목',
				messagePlaceholder: '메시지'
			}
		};
	}

	// Marker (Inline tool)
	if (typeof window.Marker !== 'undefined') {
		availableTools.marker = {
			class: window.Marker
		};
	}

	// Inline Code
	if (typeof window.InlineCode !== 'undefined') {
		availableTools.inlineCode = {
			class: window.InlineCode
		};
	}

	// Underline
	if (typeof window.Underline !== 'undefined') {
		availableTools.underline = window.Underline;
	}

	// Raw HTML
	if (typeof window.RawTool !== 'undefined') {
		availableTools.raw = window.RawTool;
	}

	// Personality
	if (typeof window.Personality !== 'undefined') {
		availableTools.personality = {
			class: window.Personality,
			config: {
				endpoint: '/homepage_menu/upload_avatar'
			}
		};
	}

	// WaniPreach - 게시판 블록 (새로 추가)
	if (typeof window.WaniPreach !== 'undefined') {
		availableTools.waniPreach = {
			class: window.WaniPreach
		};
	}

	console.log('사용 가능한 도구:', Object.keys(availableTools));

	// Editor.js 생성
	try {
		pageContentEditor = new EditorJS({
			holder: 'page_content_editor',
			tools: availableTools,
			data: parsedData || {},
			placeholder: '내용을 입력하세요...',
			minHeight: 300,

			onReady: function() {
				console.log('Editor.js 초기화 완료');
			},

			onChange: function(api, event) {
				// 변경 감지 (필요시 사용)
			}
		});

	} catch (error) {
		console.error('Editor.js 초기화 실패:', error);
		showToast('에디터 초기화에 실패했습니다: ' + error.message);
	}
}












/**
 * 페이지 저장 핸들러
 */
function handleSavePage() {
	const orgId = $('#current_org_id').val();

	if (!pageContentEditor) {
		showToast('에디터가 초기화되지 않았습니다.');
		return;
	}

	// Editor.js에서 데이터 가져오기
	pageContentEditor.save().then(function(outputData) {
		// JSON 형식으로 저장
		const pageContent = JSON.stringify(outputData);

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
	}).catch(function(error) {
		console.error('저장 실패:', error);
		showToast('데이터 저장 중 오류가 발생했습니다.');
	});
}




/**
 * 게시글 추가 핸들러
 */
function handleAddBoardItem() {
	$('#boardOffcanvasLabel').text('게시글 작성');
	$('#board_idx').val('');
	$('#board_title').val('');
	$('#board_content').val('');
	$('#youtube_url').val('');

	// 삭제 버튼 숨김 (작성 모드)
	$('#btnDeleteBoard').hide();

	const boardOffcanvas = new bootstrap.Offcanvas(document.getElementById('boardOffcanvas'));
	boardOffcanvas.show();

	// Offcanvas가 완전히 표시된 후 Dropzone 초기화
	$('#boardOffcanvas').one('shown.bs.offcanvas', function() {
		initializeBoardDropzone();
	});
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
				$('#youtube_url').val(response.data.youtube_url || '');

				// 삭제 버튼 표시 (수정 모드)
				$('#btnDeleteBoard').show();

				// Offcanvas 표시
				const boardOffcanvas = new bootstrap.Offcanvas(document.getElementById('boardOffcanvas'));
				boardOffcanvas.show();

				// Offcanvas가 완전히 표시된 후 Dropzone 초기화 및 파일 복원
				$('#boardOffcanvas').one('shown.bs.offcanvas', function() {
					initializeBoardDropzone();

					// 기존 첨부파일 복원
					if (response.data.file_path) {
						try {
							const files = JSON.parse(response.data.file_path);
							if (Array.isArray(files) && files.length > 0) {
								setTimeout(function() {
									restoreUploadedFiles(files);
								}, 300);
							}
						} catch (e) {
							console.error('파일 복원 실패:', e);
						}
					}
				});
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
	const youtubeUrl = $('#youtube_url').val().trim();
	const uploadedFiles = $('#uploaded_files').val();

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
			board_content: boardContent,
			youtube_url: youtubeUrl,
			file_path: uploadedFiles
		},
		success: function(response) {
			if (response.success) {
				showToast(response.message);

				const boardOffcanvas = bootstrap.Offcanvas.getInstance(document.getElementById('boardOffcanvas'));
				boardOffcanvas.hide();

				// 현재 페이지와 검색어 유지하면서 목록 새로고침
				loadBoardContent(currentMenuId, currentBoardPage, currentSearchKeyword);
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
 * Offcanvas에서 게시글 삭제 핸들러
 */
function handleDeleteBoardInOffcanvas() {
	const idx = $('#board_idx').val();

	if (!idx) {
		showToast('삭제할 게시글 정보가 없습니다.');
		return;
	}

	showConfirmModal(
		'게시글 삭제',
		'1건의 게시물을 삭제하시겠습니까?',
		function() {
			$.ajax({
				url: '/homepage_menu/delete_selected_boards',
				type: 'POST',
				dataType: 'json',
				data: {
					idx_list: [idx]
				},
				success: function(response) {
					if (response.success) {
						showToast(response.message);

						// Offcanvas 닫기
						const boardOffcanvas = bootstrap.Offcanvas.getInstance(document.getElementById('boardOffcanvas'));
						boardOffcanvas.hide();

						// 목록 새로고침
						loadBoardContent(currentMenuId, currentBoardPage, currentSearchKeyword);
					} else {
						showToast(response.message);
					}
				},
				error: function() {
					showToast('게시글 삭제에 실패했습니다.');
				}
			});
		}
	);
}

/**
 * YouTube URL 유효성 검사
 */
function isValidYoutubeUrl(url) {
	const youtubeRegex = /^(https?:\/\/)?(www\.)?(youtube\.com\/(watch\?v=|embed\/)|youtu\.be\/)[\w-]+/;
	return youtubeRegex.test(url);
}



/**
 * 컨텐츠 영역 초기화
 */
function clearContentArea() {
	// Editor.js 인스턴스 제거
	if (pageContentEditor) {
		pageContentEditor.destroy();
		pageContentEditor = null;
	}

	$('#contentArea').html('<div class="text-center text-muted py-5">메뉴를 선택하세요</div>');
	currentMenuId = null;
	currentMenuType = null;
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


/**
 * Dropzone 초기화 - 프론트에서 썸네일 생성 + 문서 파일 아이콘 표시 + 파일 삭제
 */
function initializeBoardDropzone() {
	// 기존 Dropzone 인스턴스 완전 제거
	if (boardDropzone) {
		try {
			boardDropzone.destroy();
			boardDropzone = null;
		} catch (e) {
			console.log('Dropzone 제거 중 오류:', e);
		}
	}

	const dropzoneElement = document.querySelector("#dropzoneArea");
	if (dropzoneElement && dropzoneElement.dropzone) {
		try {
			dropzoneElement.dropzone.destroy();
		} catch (e) {
			console.log('DOM Dropzone 제거 중 오류:', e);
		}
	}

	if (dropzoneElement) {
		dropzoneElement.classList.remove('dz-clickable');
		dropzoneElement.innerHTML = '';
	}

	uploadedFileList = [];
	$('#uploaded_files').val('');
	console.log('[Dropzone] 초기화: uploadedFileList 비움');

	Dropzone.autoDiscover = false;

	try {
		boardDropzone = new Dropzone("#dropzoneArea", {
			url: "/homepage_menu/upload_board_file",
			paramName: "file",
			maxFilesize: 10,
			maxFiles: 20,
			addRemoveLinks: true,
			dictDefaultMessage: "파일을 드래그하거나 클릭하여 업로드하세요",
			dictRemoveFile: "삭제",
			dictCancelUpload: "취소",
			dictMaxFilesExceeded: "최대 20개까지만 업로드 가능합니다",
			dictInvalidFileType: "허용되지 않는 파일 형식입니다",
			acceptedFiles: "image/jpeg,image/jpg,image/png,image/gif,.pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.hwp,.hwpx,.zip",
			thumbnailWidth: 120,
			thumbnailHeight: 120,
			thumbnailMethod: 'contain',

			init: function() {
				const dropzoneInstance = this;

				this.on("sending", function(file, xhr, formData) {
					const orgId = $('#current_org_id').val();
					formData.append("org_id", orgId);

					// 이미지 파일이고 썸네일이 생성된 경우 함께 전송
					if (file.type.match(/image.*/) && file.thumbnailBlob) {
						formData.append("thumbnail", file.thumbnailBlob, 'thumb_' + file.name);
						console.log('[Dropzone] 썸네일 포함하여 전송:', file.name);
					}

					console.log('[Dropzone] 파일 전송 시작:', file.name);
				});

				this.on("success", function(file, response) {
					if (typeof response === 'string') {
						response = JSON.parse(response);
					}

					if (response && response.success) {
						file.serverPath = response.file_path;
						file.serverFileName = response.file_name;
						file.thumbPath = response.thumb_path;

						uploadedFileList.push({
							name: response.file_name,
							path: response.file_path,
							thumb_path: response.thumb_path,
							size: file.size,
							type: response.file_type
						});

						updateUploadedFilesInput();
						console.log('[Dropzone] 파일 업로드 성공:', response.file_name);
						if (response.thumb_path) {
							console.log('[Dropzone] 썸네일 경로:', response.thumb_path);
						}
					}
				});

				this.on("error", function(file, errorMessage, xhr) {
					if (xhr && xhr.status === 200 && xhr.responseText) {
						try {
							const response = JSON.parse(xhr.responseText);
							if (response && response.success) {
								file.serverPath = response.file_path;
								file.serverFileName = response.file_name;
								file.thumbPath = response.thumb_path;

								uploadedFileList.push({
									name: response.file_name,
									path: response.file_path,
									thumb_path: response.thumb_path,
									size: file.size,
									type: response.file_type
								});

								updateUploadedFilesInput();
								return;
							}
						} catch (e) {}
					}

					showToast(typeof errorMessage === 'string' ? errorMessage : '파일 업로드 실패');
					this.removeFile(file);
				});

				this.on("removedfile", function(file) {
					console.log('[Dropzone] 파일 삭제:', file.name);

					if (file.serverPath || file.serverFileName) {
						// 업로드된 파일 목록에서 제거
						const beforeLength = uploadedFileList.length;
						const removedFile = uploadedFileList.find(f => f.path === file.serverPath);
						uploadedFileList = uploadedFileList.filter(f => f.path !== file.serverPath);
						console.log('[Dropzone] 삭제 전:', beforeLength, '삭제 후:', uploadedFileList.length);

						updateUploadedFilesInput();

						// 서버에 파일 삭제 요청
						if (removedFile) {
							$.ajax({
								url: '/homepage_menu/delete_board_file',
								type: 'POST',
								dataType: 'json',
								data: {
									file_path: removedFile.path,
									thumb_path: removedFile.thumb_path
								},
								success: function(response) {
									if (response.success) {
										console.log('[Dropzone] 서버 파일 삭제 완료:', removedFile.path);
									} else {
										console.error('[Dropzone] 서버 파일 삭제 실패:', response.message);
									}
								},
								error: function() {
									console.error('[Dropzone] 서버 파일 삭제 요청 실패');
								}
							});
						}
					}
				});

				this.on("maxfilesexceeded", function(file) {
					showToast('최대 20개까지만 업로드 가능합니다');
					this.removeFile(file);
				});

				// 썸네일 생성 후 커스터마이징
				this.on("thumbnail", function(file, dataUrl) {
					console.log('[Dropzone] 썸네일 생성 완료:', file.name);

					// 문서 파일인 경우 썸네일 영역 커스터마이징
					if (!file.type.match(/image.*/)) {
						const preview = file.previewElement;
						if (preview) {
							const imgElement = preview.querySelector('.dz-image img');
							if (imgElement) {
								const ext = getFileExtension(file.name).toUpperCase();

								// 이미지 대신 확장자 박스 표시
								imgElement.style.display = 'none';
								const dzImage = preview.querySelector('.dz-image');
								if (dzImage) {
									dzImage.innerHTML = `<div class="dz-file-icon">${ext}</div>`;
								}
							}
						}
					}
				});

				// 완료 후 다운로드 버튼 추가
				this.on("complete", function(file) {
					if (file.status === "success") {
						const preview = file.previewElement;
						if (preview) {
							// 삭제 버튼 찾기
							const removeBtn = preview.querySelector('.dz-remove');
							if (removeBtn && file.serverPath) {
								// 다운로드 버튼이 이미 있는지 확인
								if (!preview.querySelector('.dz-download')) {
									// 다운로드 버튼 생성
									const downloadBtn = document.createElement('a');
									downloadBtn.href = file.serverPath;
									downloadBtn.download = file.name;
									downloadBtn.className = 'dz-download';
									downloadBtn.textContent = '다운로드';
									downloadBtn.style.marginLeft = '5px';

									// 삭제 버튼 옆에 추가
									removeBtn.parentNode.insertBefore(downloadBtn, removeBtn.nextSibling);
								}
							}
						}
					}
				});
			},

			accept: function(file, done) {
				const fileName = file.name.toLowerCase();
				const imageExtensions = ['.jpg', '.jpeg', '.png', '.gif'];
				const isImage = imageExtensions.some(ext => fileName.endsWith(ext));
				const docExtensions = ['.pdf', '.doc', '.docx', '.ppt', '.pptx', '.xls', '.xlsx', '.hwp', '.hwpx', '.zip'];
				const isDocument = docExtensions.some(ext => fileName.endsWith(ext));

				if (isImage || isDocument) {
					done();
				} else {
					done("이미지(jpg, jpeg, png, gif) 또는 문서(pdf, doc, docx, ppt, pptx, xls, xlsx, hwp, hwpx, zip) 파일만 업로드 가능합니다.");
				}
			},

			transformFile: function(file, done) {
				if (file.type.match(/image.*/)) {
					const reader = new FileReader();
					reader.onload = function(e) {
						const img = new Image();
						img.onload = function() {
							let width = img.width;
							let height = img.height;

							console.log('[Dropzone] 원본 이미지 크기:', width, 'x', height);

							// 원본 이미지 리사이징 (2000px 초과 시)
							const canvas = document.createElement('canvas');
							const ctx = canvas.getContext('2d');
							let needResize = false;

							if (width > 2000 || height > 2000) {
								needResize = true;
								if (width > height) {
									if (width > 2000) {
										height = Math.round((height * 2000) / width);
										width = 2000;
									}
								} else {
									if (height > 2000) {
										width = Math.round((width * 2000) / height);
										height = 2000;
									}
								}

								console.log('[Dropzone] 원본 리사이징:', width, 'x', height);
							}

							// 썸네일 생성 (200x200)
							const thumbCanvas = document.createElement('canvas');
							const thumbCtx = thumbCanvas.getContext('2d');
							let thumbWidth = 200;
							let thumbHeight = 200;

							// 비율 유지하며 썸네일 크기 계산
							const aspectRatio = img.width / img.height;
							if (aspectRatio > 1) {
								thumbHeight = Math.round(thumbWidth / aspectRatio);
							} else {
								thumbWidth = Math.round(thumbHeight * aspectRatio);
							}

							thumbCanvas.width = thumbWidth;
							thumbCanvas.height = thumbHeight;
							thumbCtx.drawImage(img, 0, 0, thumbWidth, thumbHeight);

							console.log('[Dropzone] 썸네일 생성:', thumbWidth, 'x', thumbHeight);

							// 썸네일을 Blob으로 변환하여 파일 객체에 저장
							thumbCanvas.toBlob(function(thumbBlob) {
								file.thumbnailBlob = thumbBlob;
								console.log('[Dropzone] 썸네일 Blob 생성 완료:', thumbBlob.size, 'bytes');

								// 원본 이미지 처리
								if (needResize) {
									canvas.width = width;
									canvas.height = height;
									ctx.drawImage(img, 0, 0, width, height);

									canvas.toBlob(function(blob) {
										blob.name = file.name;
										blob.lastModified = new Date();
										done(blob);
									}, file.type, 0.9);
								} else {
									console.log('[Dropzone] 원본 리사이징 불필요');
									done(file);
								}
							}, file.type, 0.9);
						};
						img.src = e.target.result;
					};
					reader.readAsDataURL(file);
				} else {
					done(file);
				}
			}
		});

		console.log('[Dropzone] 초기화 완료');

	} catch (e) {
		console.error('[Dropzone] 초기화 실패:', e);
		showToast('파일 업로드 영역 초기화에 실패했습니다.');
	}
}

/**
 * 파일 확장자 추출
 */
function getFileExtension(filename) {
	return filename.slice((filename.lastIndexOf(".") - 1 >>> 0) + 2);
}

/**
 * 업로드된 파일 목록 업데이트
 */
function updateUploadedFilesInput() {
	const jsonString = JSON.stringify(uploadedFileList);
	$('#uploaded_files').val(jsonString);
	console.log('[updateUploadedFilesInput] 업데이트됨:', jsonString);
}

/**
 * 기존 파일 복원 (수정 시) - 썸네일 사용 + 다운로드 버튼 추가
 */
function restoreUploadedFiles(files) {
	if (!files || !Array.isArray(files) || files.length === 0) {
		console.log('[restoreUploadedFiles] 복원할 파일 없음');
		return;
	}

	console.log('[restoreUploadedFiles] 파일 복원 시작:', files);

	uploadedFileList = files;
	updateUploadedFilesInput();

	if (boardDropzone) {
		files.forEach(function(fileData) {
			const mockFile = {
				name: fileData.name,
				size: fileData.size,
				type: fileData.type,
				serverPath: fileData.path,
				serverFileName: fileData.name,
				thumbPath: fileData.thumb_path,
				status: Dropzone.SUCCESS,
				accepted: true
			};

			boardDropzone.emit("addedfile", mockFile);

			// 이미지 파일인 경우 썸네일 표시
			if (fileData.type && fileData.type.match(/image.*/)) {
				// 썸네일이 있으면 썸네일 사용, 없으면 원본 사용
				const thumbnailUrl = fileData.thumb_path ? fileData.thumb_path : fileData.path;

				console.log('[restoreUploadedFiles] 썸네일 URL:', thumbnailUrl);

				boardDropzone.emit("thumbnail", mockFile, thumbnailUrl);

				setTimeout(function() {
					const preview = mockFile.previewElement;
					if (preview) {
						const img = preview.querySelector('img');
						if (img) {
							img.style.width = '100%';
							img.style.height = '100%';
							img.style.objectFit = 'contain';
						}
					}
				}, 100);
			} else {
				// 문서 파일인 경우 확장자 박스 표시
				boardDropzone.emit("thumbnail", mockFile, null);

				setTimeout(function() {
					const preview = mockFile.previewElement;
					if (preview) {
						const imgElement = preview.querySelector('.dz-image img');
						if (imgElement) {
							const ext = getFileExtension(fileData.name).toUpperCase();

							imgElement.style.display = 'none';
							const dzImage = preview.querySelector('.dz-image');
							if (dzImage) {
								dzImage.innerHTML = `<div class="dz-file-icon">${ext}</div>`;
							}
						}
					}
				}, 100);
			}

			boardDropzone.emit("complete", mockFile);
			boardDropzone.files.push(mockFile);

			// 다운로드 버튼 추가
			setTimeout(function() {
				const preview = mockFile.previewElement;
				if (preview) {
					const removeBtn = preview.querySelector('.dz-remove');
					removeBtn.className = 'btn btn-xs btn-outline-danger';
					if (removeBtn && fileData.path) {
						// 이미 다운로드 버튼이 있는지 확인
						if (!preview.querySelector('.dz-download')) {
							const downloadBtn = document.createElement('a');
							downloadBtn.href = fileData.path;
							downloadBtn.download = fileData.name;
							downloadBtn.className = 'dz-download btn btn-xs btn-outline-primary';
							downloadBtn.textContent = '다운로드';
							removeBtn.parentNode.insertBefore(downloadBtn, removeBtn.nextSibling);
						}
					}
				}
			}, 150);

			console.log('[restoreUploadedFiles] 파일 복원됨:', fileData.name);
		});

		console.log('[restoreUploadedFiles] 복원 완료, 전체 파일 수:', boardDropzone.files.length);
	}
}





/**
 * 게시판 컨텐츠 로드
 */
function loadBoardContent(menuId, page = 1, searchKeyword = '') {
	const orgId = $('#current_org_id').val();

	currentBoardPage = page;
	currentSearchKeyword = searchKeyword;

	$.ajax({
		url: '/homepage_menu/get_board_list',
		type: 'POST',
		dataType: 'json',
		data: {
			org_id: orgId,
			menu_id: menuId,
			search_keyword: searchKeyword,
			page: page
		},
		success: function(response) {
			if (response.success) {
				renderBoardContent(response.data, response.total, page);
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
 * 게시판 컨텐츠 렌더링 (페이지네이션 포함)
 */
function renderBoardContent(boardList, total, currentPage = 1) {
	let tableRows = '';
	const limit = 20;

	if (boardList && boardList.length > 0) {
		boardList.forEach(function(board) {
			// 아이콘 생성
			let icons = '';

			// YouTube 아이콘
			if (board.youtube_url) {
				icons += '<i class="bi bi-youtube text-danger ms-1" title="YouTube 동영상"></i>';
			}

			// 파일 첨부 아이콘
			if (board.file_path) {
				try {
					const files = JSON.parse(board.file_path);
					if (Array.isArray(files) && files.length > 0) {
						const hasImage = files.some(file => file.type === 'image');
						if (hasImage) {
							icons += '<i class="bi bi-image text-primary ms-1" title="이미지 첨부"></i>';
						}

						const hasDocument = files.some(file => file.type === 'document');
						if (hasDocument) {
							icons += '<i class="bi bi-file-earmark-text text-secondary ms-1" title="문서 첨부"></i>';
						}
					}
				} catch (e) {
					// JSON 파싱 실패 시 무시
				}
			}

			tableRows += `
				<tr class="btn-board-edit" data-idx="${board.idx}" style="cursor: pointer">
					<td><input type="checkbox" class="form-check-input board-checkbox" value="${board.idx}" onclick="event.stopPropagation();"></td>
					<td class="text-start">
						${escapeHtml(board.board_title)}
						${icons}
					</td>
					<td>${board.view_count}</td>
					<td>${formatDate(board.reg_date)}</td>
					<td>${escapeHtml(board.writer_name || '')}</td>
					<td>${board.modi_date ? formatDate(board.modi_date) : ''}</td>
					<td>${escapeHtml(board.modifier_name || '')}</td>					
				</tr>
			`;
		});
	} else {
		tableRows = '<tr><td colspan="7" class="text-center text-muted" style="height: 100px">등록된 게시글이 없습니다.</td></tr>';
	}

	// 페이지네이션 생성
	const totalPages = Math.ceil(total / limit);
	let pagination = '';

	if (totalPages > 1) {
		pagination = '<nav aria-label="게시판 페이지네이션"><ul class="pagination justify-content-center mb-0">';

		// 이전 버튼
		if (currentPage > 1) {
			pagination += `<li class="page-item"><a class="page-link" href="#" data-page="${currentPage - 1}">이전</a></li>`;
		} else {
			pagination += '<li class="page-item disabled"><span class="page-link">이전</span></li>';
		}

		// 페이지 번호
		const startPage = Math.max(1, currentPage - 2);
		const endPage = Math.min(totalPages, currentPage + 2);

		if (startPage > 1) {
			pagination += `<li class="page-item"><a class="page-link" href="#" data-page="1">1</a></li>`;
			if (startPage > 2) {
				pagination += '<li class="page-item disabled"><span class="page-link">...</span></li>';
			}
		}

		for (let i = startPage; i <= endPage; i++) {
			if (i === currentPage) {
				pagination += `<li class="page-item active"><span class="page-link">${i}</span></li>`;
			} else {
				pagination += `<li class="page-item"><a class="page-link" href="#" data-page="${i}">${i}</a></li>`;
			}
		}

		if (endPage < totalPages) {
			if (endPage < totalPages - 1) {
				pagination += '<li class="page-item disabled"><span class="page-link">...</span></li>';
			}
			pagination += `<li class="page-item"><a class="page-link" href="#" data-page="${totalPages}">${totalPages}</a></li>`;
		}

		// 다음 버튼
		if (currentPage < totalPages) {
			pagination += `<li class="page-item"><a class="page-link" href="#" data-page="${currentPage + 1}">다음</a></li>`;
		} else {
			pagination += '<li class="page-item disabled"><span class="page-link">다음</span></li>';
		}

		pagination += '</ul></nav>';
	}

	const html = `
		<div class="d-flex justify-content-between align-items-center mb-3">
			<h5 class="mb-0">게시판 관리</h5>
			<div class="d-flex gap-2">
				<button type="button" class="btn btn-sm btn-danger" id="btnDeleteSelected">
					<i class="bi bi-trash"></i> 선택 삭제
				</button>
				<button type="button" class="btn btn-sm btn-primary" id="btnAddBoardItem">
					<i class="bi bi-plus-lg"></i> 글쓰기
				</button>
			</div>
		</div>
		<div class="d-flex justify-content-between align-items-center mb-2">
			<div>전체 ${total}건</div>
			<div class="input-group board-search-box">
				<input type="text" class="form-control form-control-sm" id="board_search_keyword" placeholder="검색어 입력" value="${escapeHtml(currentSearchKeyword)}">
				<button class="btn btn-sm btn-outline-secondary" type="button" id="btnSearchBoard">검색</button>
			</div>
		</div>
		<div class="table-responsive">
			<table class="table table-hover board-table">
				<thead>
					<tr>
						<th style="width: 40px;"><input type="checkbox" class="form-check-input" id="selectAllBoard"></th>
						<th>제목</th>
						<th style="width: 60px;">조회수</th>
						<th style="width: 100px;">작성일</th>
						<th style="width: 60px;">작성자</th>
						<th style="width: 100px;">수정일</th>
						<th style="width: 60px;">수정자</th>						
					</tr>
				</thead>
				<tbody>
					${tableRows}
				</tbody>
			</table>
		</div>
		${pagination}
	`;

	$('#contentArea').html(html);

	// 페이지네이션 클릭 이벤트 바인딩
	bindPaginationEvents();

	// 전체 선택 체크박스 이벤트 바인딩
	bindSelectAllEvent();
}

/**
 * 전체 선택 체크박스 이벤트 바인딩
 */
function bindSelectAllEvent() {
	$(document).off('change', '#selectAllBoard');
	$(document).on('change', '#selectAllBoard', function() {
		const isChecked = $(this).prop('checked');
		$('.board-checkbox').prop('checked', isChecked);
	});

	// 개별 체크박스 변경 시 전체 선택 체크박스 상태 업데이트
	$(document).off('change', '.board-checkbox');
	$(document).on('change', '.board-checkbox', function() {
		const totalCheckboxes = $('.board-checkbox').length;
		const checkedCheckboxes = $('.board-checkbox:checked').length;
		$('#selectAllBoard').prop('checked', totalCheckboxes === checkedCheckboxes);
	});
}

/**
 * 선택 삭제 핸들러
 */
function handleDeleteSelected() {
	const selectedIds = [];
	$('.board-checkbox:checked').each(function() {
		selectedIds.push($(this).val());
	});

	if (selectedIds.length === 0) {
		showToast('삭제할 게시물을 선택해주세요.');
		return;
	}

	const count = selectedIds.length;
	showConfirmModal(
		'게시글 삭제',
		`${count}건의 게시물을 삭제하시겠습니까?`,
		function() {
			$.ajax({
				url: '/homepage_menu/delete_selected_boards',
				type: 'POST',
				dataType: 'json',
				data: {
					idx_list: selectedIds
				},
				success: function(response) {
					if (response.success) {
						showToast(response.message);
						// 현재 페이지 새로고침
						loadBoardContent(currentMenuId, currentBoardPage, currentSearchKeyword);
					} else {
						showToast(response.message);
					}
				},
				error: function() {
					showToast('게시글 삭제에 실패했습니다.');
				}
			});
		}
	);
}

/**
 * 페이지네이션 클릭 이벤트 바인딩
 */
function bindPaginationEvents() {
	$(document).off('click', '.pagination .page-link');
	$(document).on('click', '.pagination .page-link', function(e) {
		e.preventDefault();
		const page = $(this).data('page');
		if (page) {
			loadBoardContent(currentMenuId, page, currentSearchKeyword);
		}
	});
}

/**
 * 게시판 검색 핸들러
 */
function handleSearchBoard() {
	const searchKeyword = $('#board_search_keyword').val().trim();
	loadBoardContent(currentMenuId, 1, searchKeyword);
}
