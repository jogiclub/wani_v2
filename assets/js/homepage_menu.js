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
				<tr class="btn-board-edit" data-idx="${board.idx}" style="cursor: pointer">
					<td><input type="checkbox" class="form-check-input" value="${board.idx}"></td>
					<td class="text-start">${escapeHtml(board.board_title)}</td>
					<td>${board.view_count}</td>
					<td>${formatDate(board.reg_date)}</td>
					<td>${escapeHtml(board.writer_name || '')}</td>
					<td>${board.modi_date ? formatDate(board.modi_date) : ''}</td>
					<td>${escapeHtml(board.modifier_name || '')}</td>					
				</tr>
			`;
		});
	} else {
		tableRows = '<tr><td colspan="8" class="text-center text-muted" style="height: 100px">등록된 게시글이 없습니다.</td></tr>';
	}

	const html = `
		<div class="d-flex justify-content-between align-items-center mb-3">
			<h5 class="mb-0">게시판 관리</h5>
			<button type="button" class="btn btn-sm btn-primary" id="btnAddBoardItem">
				<i class="bi bi-plus-lg"></i> 글쓰기
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
	`;

	$('#contentArea').html(html);
}

/**
 * 게시글 추가 핸들러 (수정)
 */
function handleAddBoardItem() {
	$('#boardOffcanvasLabel').text('게시글 작성');
	$('#board_idx').val('');
	$('#board_title').val('');
	$('#board_content').val('');
	$('#youtube_url').val('');
	$('#uploaded_files').val('');

	// Dropzone 초기화
	initializeBoardDropzone();

	const boardOffcanvas = new bootstrap.Offcanvas(document.getElementById('boardOffcanvas'));
	boardOffcanvas.show();
}

/**
 * 게시글 수정 핸들러 (수정)
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

				// Dropzone 초기화
				initializeBoardDropzone();

				// 기존 파일 복원
				if (response.data.file_path) {
					try {
						const files = JSON.parse(response.data.file_path);
						restoreUploadedFiles(files);
					} catch (e) {
						console.error('파일 데이터 파싱 오류:', e);
					}
				}

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
 * 게시글 저장 핸들러 - 디버깅 추가
 */
function handleSaveBoard() {
	const orgId = $('#current_org_id').val();
	const idx = $('#board_idx').val();
	const boardTitle = $('#board_title').val().trim();
	const boardContent = $('#board_content').val();
	const youtubeUrl = $('#youtube_url').val().trim();
	const uploadedFiles = $('#uploaded_files').val();

	console.log('=== 게시글 저장 시작 ===');
	console.log('uploadedFileList:', uploadedFileList);
	console.log('uploaded_files input 값:', uploadedFiles);

	if (!boardTitle) {
		showToast('제목을 입력해주세요.');
		return;
	}

	// YouTube URL 유효성 검사 (입력된 경우에만)
	if (youtubeUrl && !isValidYoutubeUrl(youtubeUrl)) {
		showToast('올바른 YouTube URL을 입력해주세요.');
		return;
	}

	const postData = {
		org_id: orgId,
		menu_id: currentMenuId,
		idx: idx,
		board_title: boardTitle,
		board_content: boardContent,
		youtube_url: youtubeUrl,
		file_path: uploadedFiles
	};

	console.log('전송할 데이터:', postData);

	$.ajax({
		url: '/homepage_menu/save_board',
		type: 'POST',
		dataType: 'json',
		data: postData,
		success: function(response) {
			console.log('서버 응답:', response);

			if (response.success) {
				showToast(response.message);

				const boardOffcanvas = bootstrap.Offcanvas.getInstance(document.getElementById('boardOffcanvas'));
				boardOffcanvas.hide();

				loadBoardContent(currentMenuId);
			} else {
				showToast(response.message);
			}
		},
		error: function(xhr, status, error) {
			console.error('저장 실패:', error);
			console.error('응답:', xhr.responseText);
			showToast('게시글 저장에 실패했습니다.');
		}
	});
}
/**
 * YouTube URL 유효성 검사
 */
function isValidYoutubeUrl(url) {
	const youtubeRegex = /^(https?:\/\/)?(www\.)?(youtube\.com\/(watch\?v=|embed\/)|youtu\.be\/)[\w-]+/;
	return youtubeRegex.test(url);
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
 * Dropzone 초기화
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

	// DOM 요소에서 Dropzone 클래스 제거
	const dropzoneElement = document.querySelector("#dropzoneArea");
	if (dropzoneElement && dropzoneElement.dropzone) {
		try {
			dropzoneElement.dropzone.destroy();
		} catch (e) {
			console.log('DOM Dropzone 제거 중 오류:', e);
		}
	}

	// Dropzone 클래스 제거
	if (dropzoneElement) {
		dropzoneElement.classList.remove('dz-clickable');
		dropzoneElement.innerHTML = '';
	}

	// 파일 목록 초기화
	uploadedFileList = [];
	$('#uploaded_files').val('');
	console.log('[Dropzone] 초기화: uploadedFileList 비움');

	// Dropzone 자동 발견 비활성화
	Dropzone.autoDiscover = false;

	// 새 Dropzone 인스턴스 생성
	try {
		boardDropzone = new Dropzone("#dropzoneArea", {
			url: "/homepage_menu/upload_board_file",
			paramName: "file",
			maxFilesize: 10, // MB
			maxFiles: 5,
			addRemoveLinks: true,
			dictDefaultMessage: "파일을 드래그하거나 클릭하여 업로드하세요",
			dictRemoveFile: "삭제",
			dictCancelUpload: "취소",
			dictMaxFilesExceeded: "최대 5개까지만 업로드 가능합니다",
			dictInvalidFileType: "허용되지 않는 파일 형식입니다",
			acceptedFiles: "image/jpeg,image/jpg,image/png,image/gif,.pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.hwp,.hwpx,.zip",

			// 썸네일 설정
			thumbnailWidth: 120,
			thumbnailHeight: 120,
			thumbnailMethod: 'contain',

			init: function() {
				const dropzoneInstance = this;

				this.on("sending", function(file, xhr, formData) {
					const orgId = $('#current_org_id').val();
					formData.append("org_id", orgId);
					console.log('[Dropzone] 파일 전송 시작:', file.name);
				});

				this.on("success", function(file, response) {
					// 문자열 응답 파싱
					if (typeof response === 'string') {
						response = JSON.parse(response);
					}

					if (response && response.success) {
						// 파일 정보 저장 (중요!)
						file.serverPath = response.file_path;
						file.serverFileName = response.file_name;

						uploadedFileList.push({
							name: response.file_name,
							path: response.file_path,
							size: file.size,
							type: file.type
						});

						updateUploadedFilesInput();
					}
				});

				this.on("error", function(file, errorMessage, xhr) {
					// 실제로는 성공인지 확인
					if (xhr && xhr.status === 200 && xhr.responseText) {
						try {
							const response = JSON.parse(xhr.responseText);
							if (response && response.success) {
								// 성공 처리
								file.serverPath = response.file_path;
								file.serverFileName = response.file_name;

								uploadedFileList.push({
									name: response.file_name,
									path: response.file_path,
									size: file.size,
									type: file.type
								});

								updateUploadedFilesInput();
								return; // 파일 삭제 안 함!
							}
						} catch (e) {}
					}

					// 진짜 에러만 처리
					showToast(typeof errorMessage === 'string' ? errorMessage : '파일 업로드 실패');
					this.removeFile(file);
				});

				this.on("removedfile", function(file) {
					console.log('[Dropzone] 파일 삭제:', file.name);

					// 서버에 업로드된 파일 정보로 필터링
					if (file.serverPath || file.serverFileName) {
						const beforeLength = uploadedFileList.length;
						uploadedFileList = uploadedFileList.filter(f => f.path !== file.serverPath);
						console.log('[Dropzone] 삭제 전:', beforeLength, '삭제 후:', uploadedFileList.length);
						console.log('[Dropzone] 현재 uploadedFileList:', uploadedFileList);

						updateUploadedFilesInput();
						console.log('[Dropzone] uploaded_files 값:', $('#uploaded_files').val());
					}
				});

				this.on("maxfilesexceeded", function(file) {
					showToast('최대 5개까지만 업로드 가능합니다');
					this.removeFile(file);
				});

				// 썸네일 생성 후 이벤트
				this.on("thumbnail", function(file, dataUrl) {
					console.log('[Dropzone] 썸네일 생성됨:', file.name);
				});
			},

			// 파일 검증 함수
			accept: function(file, done) {
				const fileName = file.name.toLowerCase();

				// 이미지 파일 체크
				const imageExtensions = ['.jpg', '.jpeg', '.png', '.gif'];
				const isImage = imageExtensions.some(ext => fileName.endsWith(ext));

				// 문서 파일 체크
				const docExtensions = ['.pdf', '.doc', '.docx', '.ppt', '.pptx', '.xls', '.xlsx', '.hwp', '.hwpx', '.zip'];
				const isDocument = docExtensions.some(ext => fileName.endsWith(ext));

				if (isImage) {
					done();
				} else if (isDocument) {
					done();
				} else {
					done("이미지(jpg, jpeg, png, gif) 또는 문서(pdf, doc, docx, ppt, pptx, xls, xlsx, hwp, hwpx, zip) 파일만 업로드 가능합니다.");
				}
			},

			// 이미지 리사이징 처리
			transformFile: function(file, done) {
				// 이미지 파일인지 확인
				if (file.type.match(/image.*/)) {
					const reader = new FileReader();
					reader.onload = function(e) {
						const img = new Image();
						img.onload = function() {
							let width = img.width;
							let height = img.height;

							console.log('[Dropzone] 원본 이미지 크기:', width, 'x', height);

							// 긴 쪽이 2000px 넘으면 리사이징
							if (width > 2000 || height > 2000) {
								const canvas = document.createElement('canvas');
								const ctx = canvas.getContext('2d');

								// 비율 계산
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

								console.log('[Dropzone] 리사이징 후 크기:', width, 'x', height);

								canvas.width = width;
								canvas.height = height;
								ctx.drawImage(img, 0, 0, width, height);

								// Canvas를 Blob으로 변환
								canvas.toBlob(function(blob) {
									// Blob에 원본 파일명 추가
									blob.name = file.name;
									blob.lastModified = new Date();
									done(blob);
								}, file.type, 0.9);
							} else {
								console.log('[Dropzone] 리사이징 불필요');
								// 리사이징 불필요
								done(file);
							}
						};
						img.src = e.target.result;
					};
					reader.readAsDataURL(file);
				} else {
					// 이미지가 아니면 원본 그대로
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
 * 업로드된 파일 목록 업데이트
 */
function updateUploadedFilesInput() {
	const jsonString = JSON.stringify(uploadedFileList);
	$('#uploaded_files').val(jsonString);
	console.log('[updateUploadedFilesInput] 업데이트됨:', jsonString);
}

/**
 * 역할: 파일 복원 시 썸네일 표시 및 삭제 기능 수정
 */

/**
 * 기존 파일 복원 (수정 시) - 썸네일 표시 및 삭제 기능 개선
 */
function restoreUploadedFiles(files) {
	if (!files || !Array.isArray(files) || files.length === 0) {
		console.log('[restoreUploadedFiles] 복원할 파일 없음');
		return;
	}

	console.log('[restoreUploadedFiles] 파일 복원 시작:', files);

	uploadedFileList = files;
	updateUploadedFilesInput();

	// boardDropzone가 초기화된 후에 파일 추가
	if (boardDropzone) {
		files.forEach(function(fileData) {
			const mockFile = {
				name: fileData.name,
				size: fileData.size,
				type: fileData.type,
				// 서버 경로 정보 저장 (삭제 시 필요)
				serverPath: fileData.path,
				serverFileName: fileData.name,
				// 업로드 완료 상태로 설정
				status: Dropzone.SUCCESS,
				accepted: true
			};

			// 파일 추가
			boardDropzone.emit("addedfile", mockFile);

			// 이미지 파일인 경우 썸네일 표시
			if (fileData.type && fileData.type.match(/image.*/)) {
				// 썸네일 URL 생성 (실제 이미지 경로 사용)
				const thumbnailUrl = fileData.path;

				// 썸네일 수동 설정
				boardDropzone.emit("thumbnail", mockFile, thumbnailUrl);

				// 썸네일 이미지가 생성되면 크기 조정
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
				// 문서 파일인 경우 기본 아이콘 표시
				boardDropzone.emit("thumbnail", mockFile, null);
			}

			// 완료 상태로 표시
			boardDropzone.emit("complete", mockFile);

			// Dropzone 파일 목록에 추가
			boardDropzone.files.push(mockFile);

			console.log('[restoreUploadedFiles] 파일 복원됨:', fileData.name);
		});

		console.log('[restoreUploadedFiles] 복원 완료, 전체 파일 수:', boardDropzone.files.length);
	}
}

/**
 * removedfile 이벤트 핸들러 (init 함수 내부에서 수정)
 */
// init 함수 내부의 removedfile 이벤트를 다음과 같이 수정:

this.on("removedfile", function(file) {
	console.log('[Dropzone] 파일 삭제 요청:', file.name);
	console.log('[Dropzone] serverPath:', file.serverPath);
	console.log('[Dropzone] serverFileName:', file.serverFileName);

	// 서버에 업로드된 파일 정보로 필터링
	if (file.serverPath || file.serverFileName) {
		const beforeLength = uploadedFileList.length;

		// uploadedFileList에서 제거
		uploadedFileList = uploadedFileList.filter(function(f) {
			const isMatch = f.path === file.serverPath;
			if (isMatch) {
				console.log('[Dropzone] 매칭된 파일 찾음:', f.name);
			}
			return !isMatch;
		});

		const afterLength = uploadedFileList.length;
		console.log('[Dropzone] 삭제 전:', beforeLength, '→ 삭제 후:', afterLength);
		console.log('[Dropzone] 현재 uploadedFileList:', uploadedFileList);

		// hidden input 업데이트
		updateUploadedFilesInput();
		console.log('[Dropzone] uploaded_files 값:', $('#uploaded_files').val());

		// 실제로 삭제되었는지 확인
		if (beforeLength > afterLength) {
			console.log('[Dropzone] 파일이 uploadedFileList에서 제거됨');
		} else {
			console.log('[Dropzone] 경고: uploadedFileList에서 제거되지 않음!');
		}
	} else {
		console.log('[Dropzone] serverPath 없음 - 업로드 전 파일');
	}
});






