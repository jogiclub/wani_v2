// 파일 위치: assets/js/mng_master.js
// 역할: 마스터 관리 JavaScript (체크박스 방식)

'use strict';
$(document).ready(function() {
	let masterGrid;
	let masterOffcanvas;
	let topCategories = [];


	// Offcanvas 초기화
	masterOffcanvas = new bootstrap.Offcanvas(document.getElementById('masterOffcanvas'));

	// 최상위 카테고리 목록 로드
	loadTopCategories();

	// 마스터 메뉴 체크박스 생성 (추가)
	createMasterMenuCheckboxes();

	// PQGrid 초기화
	initMasterGrid();

	// 마스터 목록 로드
	loadMasterList();


	/**
	 * 마스터 관리 메뉴 체크박스 생성
	 */
	function createMasterMenuCheckboxes() {
		const $container = $('#menu_permissions');
		$container.empty();

		// 관리자 메뉴 정의
		const masterMenus = [
			// ORG 분류
			{ key: 'mng_org', name: '조직관리', icon: 'bi bi-building', group: 'ORG' },
			{ key: 'mng_member', name: '회원관리', icon: 'bi bi-person', group: 'ORG' },
			// MASTER 분류
			{ key: 'mng_master', name: '마스터관리', icon: 'bi bi-people', group: 'MASTER' },
			{ key: 'mng_cost', name: '비용관리', icon: 'bi bi-cash-coin', group: 'MASTER' },
			{ key: 'mng_homepage', name: '홈페이지관리', icon: 'bi bi-globe', group: 'MASTER' }
		];

		// 전체 선택 체크박스 추가
		const selectAllHtml = `
        <div class="form-check mb-3 border-bottom pb-2">
            <input class="form-check-input" type="checkbox" id="menu_select_all">
            <label class="form-check-label fw-bold" for="menu_select_all">
                전체 선택
            </label>
        </div>
    `;
		$container.append(selectAllHtml);

		// 그룹별로 메뉴 체크박스 생성
		let currentGroup = '';
		masterMenus.forEach(function(menu) {
			// 그룹이 변경되면 그룹 헤더 추가
			if (menu.group !== currentGroup) {
				currentGroup = menu.group;
				const groupHtml = `
                <div class="text-muted small fw-bold mt-3 mb-2">${currentGroup}</div>
            `;
				$container.append(groupHtml);
			}

			const checkboxHtml = `
            <div class="form-check mb-2 ms-2">
                <input class="form-check-input menu-checkbox" type="checkbox" value="${menu.key}" id="menu_${menu.key}">
                <label class="form-check-label" for="menu_${menu.key}">
                    <i class="${menu.icon} me-2"></i>${menu.name}
                </label>
            </div>
        `;
			$container.append(checkboxHtml);
		});

		// 전체 선택 체크박스 이벤트
		$('#menu_select_all').on('change', function() {
			$('.menu-checkbox').prop('checked', $(this).is(':checked'));
		});

		// 개별 체크박스 이벤트 (전체 선택 상태 업데이트)
		$('.menu-checkbox').on('change', function() {
			updateMenuSelectAllState();
		});
	}

	/**
	 * 메뉴 전체 선택 상태 업데이트
	 */
	function updateMenuSelectAllState() {
		const totalMenus = $('.menu-checkbox').length;
		const checkedMenus = $('.menu-checkbox:checked').length;
		const selectAllCheckbox = $('#menu_select_all');

		if (checkedMenus === 0) {
			selectAllCheckbox.prop('checked', false);
			selectAllCheckbox.prop('indeterminate', false);
		} else if (checkedMenus === totalMenus) {
			selectAllCheckbox.prop('checked', true);
			selectAllCheckbox.prop('indeterminate', false);
		} else {
			selectAllCheckbox.prop('checked', false);
			selectAllCheckbox.prop('indeterminate', true);
		}
	}


	/**
	 * 시스템 메뉴 목록 로드
	 */
	function loadSystemMenus() {
		$.ajax({
			url: '/mng/mng_master/get_system_menus',
			type: 'GET',
			dataType: 'json',
			success: function(response) {
				if (response.success) {
					systemMenus = response.data;
					updateMenuCheckboxes();
				}
			},
			error: function() {
				showToast('시스템 메뉴 목록을 불러오는데 실패했습니다.', 'error');
			}
		});
	}
	/**
	 * 메뉴 체크박스 업데이트
	 */
	function updateMenuCheckboxes() {
		const $container = $('#menu_permissions');
		$container.empty();

		if (systemMenus.length === 0) {
			$container.append('<p class="text-muted">시스템 메뉴가 없습니다.</p>');
			return;
		}

		// 전체 선택 체크박스 추가
		const selectAllHtml = `
		<div class="form-check mb-3 border-bottom pb-2">
			<input class="form-check-input" type="checkbox" id="menu_select_all">
			<label class="form-check-label fw-bold" for="menu_select_all">
				전체 선택
			</label>
		</div>
	`;
		$container.append(selectAllHtml);

		// 개별 메뉴 체크박스
		systemMenus.forEach(function(menu) {
			const checkboxHtml = `
			<div class="form-check mb-2">
				<input class="form-check-input menu-checkbox" type="checkbox" value="${menu.key}" id="menu_${menu.key}">
				<label class="form-check-label" for="menu_${menu.key}">
					<i class="${menu.icon} me-2"></i>${menu.name}
				</label>
			</div>
		`;
			$container.append(checkboxHtml);
		});

		// 전체 선택 체크박스 이벤트
		$('#menu_select_all').on('change', function() {
			$('.menu-checkbox').prop('checked', $(this).is(':checked'));
		});

		// 개별 체크박스 이벤트 (전체 선택 상태 업데이트)
		$('.menu-checkbox').on('change', function() {
			updateMenuSelectAllState();
		});
	}

	/**
	 * 메뉴 전체 선택 상태 업데이트
	 */
	function updateMenuSelectAllState() {
		const totalMenus = $('.menu-checkbox').length;
		const checkedMenus = $('.menu-checkbox:checked').length;
		$('#menu_select_all').prop('checked', totalMenus > 0 && totalMenus === checkedMenus);
	}



	/**
	 * 최상위 카테고리 목록 로드
	 */
	function loadTopCategories() {
		$.ajax({
			url: '/mng/mng_master/get_top_categories',
			type: 'GET',
			dataType: 'json',
			success: function(response) {
				if (response.success) {
					topCategories = response.data;
					updateCategoryCheckboxes();
				}
			},
			error: function() {
				showToast('카테고리 목록을 불러오는데 실패했습니다.', 'error');
			}
		});
	}

	/**
	 * 카테고리 체크박스 업데이트
	 */
	function updateCategoryCheckboxes() {
		const $container = $('#category_permissions');
		$container.empty();

		if (topCategories.length === 0) {
			$container.append('<p class="text-muted">최상위 카테고리가 없습니다.</p>');
			return;
		}

		// 전체 선택 체크박스 추가
		const selectAllHtml = `
			<div class="form-check mb-3 border-bottom pb-2">
				<input class="form-check-input" type="checkbox" id="category_select_all">
				<label class="form-check-label fw-bold" for="category_select_all">
					전체 선택
				</label>
			</div>
		`;
		$container.append(selectAllHtml);

		// 개별 카테고리 체크박스
		topCategories.forEach(function(category) {
			const checkboxHtml = `
				<div class="form-check mb-2">
					<input class="form-check-input category-checkbox" type="checkbox" value="${category.category_idx}" id="category_${category.category_idx}">
					<label class="form-check-label" for="category_${category.category_idx}">
						${category.category_name}
					</label>
				</div>
			`;
			$container.append(checkboxHtml);
		});

		// 전체 선택 체크박스 이벤트
		$('#category_select_all').on('change', function() {
			const isChecked = $(this).is(':checked');
			$('.category-checkbox').prop('checked', isChecked);
		});

		// 개별 체크박스 변경 시 전체 선택 상태 업데이트
		$('.category-checkbox').on('change', function() {
			updateCategorySelectAllState();
		});
	}

	/**
	 * 카테고리 전체 선택 체크박스 상태 업데이트
	 */
	function updateCategorySelectAllState() {
		const totalCheckboxes = $('.category-checkbox').length;
		const checkedCount = $('.category-checkbox:checked').length;
		const selectAllCheckbox = $('#category_select_all');

		if (checkedCount === 0) {
			selectAllCheckbox.prop('checked', false);
			selectAllCheckbox.prop('indeterminate', false);
		} else if (checkedCount === totalCheckboxes) {
			selectAllCheckbox.prop('checked', true);
			selectAllCheckbox.prop('indeterminate', false);
		} else {
			selectAllCheckbox.prop('checked', false);
			selectAllCheckbox.prop('indeterminate', true);
		}
	}


	/**
	 * 전체 선택 체크박스 상태 업데이트
	 */
	function updateSelectAllState() {
		const totalCheckboxes = $('.category-checkbox').length;
		const checkedCount = $('.category-checkbox:checked').length;
		const selectAllCheckbox = $('#category_select_all');

		if (checkedCount === 0) {
			selectAllCheckbox.prop('checked', false);
			selectAllCheckbox.prop('indeterminate', false);
		} else if (checkedCount === totalCheckboxes) {
			selectAllCheckbox.prop('checked', true);
			selectAllCheckbox.prop('indeterminate', false);
		} else {
			selectAllCheckbox.prop('checked', false);
			selectAllCheckbox.prop('indeterminate', true);
		}
	}


	/**
	 * PQGrid 초기화
	 */
	function initMasterGrid() {
		const colModel = [
			{
				title: '프로필',
				dataIndx: 'user_profile_image',
				width: 80,
				align: 'center',
				render: function(ui) {
					const imgSrc = ui.cellData || '/assets/images/photo_no.png?3';
					return `<img src="${imgSrc}" class="rounded-circle" width="40" height="40" style="object-fit: cover;">`;
				}
			},
			{
				title: '사용자 ID',
				dataIndx: 'user_id',
				width: 150
			},
			{
				title: '이름',
				dataIndx: 'user_name',
				width: 120
			},
			{
				title: '이메일',
				dataIndx: 'user_mail',
				width: 200
			},
			{
				title: '연락처',
				dataIndx: 'user_hp',
				width: 150
			},
			{
				title: '관리메뉴',
				dataIndx: 'master_managed_menus',
				width: 200,
				render: function(ui) {
					if (!ui.cellData || ui.cellData.length === 0) {
						return '<span class="text-muted">없음</span>';
					}

					const menuNames = {
						'mng_org': '조직관리',
						'mng_master': '마스터관리'
					};

					const displayNames = ui.cellData.map(function(menuKey) {
						return menuNames[menuKey] || menuKey;
					});

					return displayNames.join(', ');
				}
			},
			{
				title: '관리카테고리',
				dataIndx: 'master_managed_category',
				width: 200,
				render: function(ui) {
					if (!ui.cellData || ui.cellData.length === 0) {
						return '<span class="text-muted">없음</span>';
					}

					const categoryNames = ui.cellData.map(function(idx) {
						const category = topCategories.find(cat => cat.category_idx == idx);
						return category ? category.category_name : idx;
					});

					return categoryNames.join(', ');
				}
			},
			{
				title: '등록일',
				dataIndx: 'regi_date',
				width: 150,
				render: function(ui) {
					return formatDateTime(ui.cellData);
				}
			},
			{
				title: '수정일',
				dataIndx: 'modi_date',
				width: 150,
				render: function(ui) {
					return formatDateTime(ui.cellData);
				}
			}
		];

		const options = {
			width: '100%',
			height: '100%',
			colModel: colModel,
			dataModel: {
				data: []
			},
			wrap: false,
			hwrap: false,
			numberCell: { show: true, width: 50 },
			title: false,
			scrollModel: {
				autoFit: false,
				horizontal: true,
				vertical: true
			},
			hoverMode: 'row',
			selectionModel: { type: 'row', mode: 'single' },
			rowClick: function(evt, ui) {
				openMasterOffcanvas(ui.rowData);
			}
		};

		masterGrid = pq.grid('#masterGrid', options);
	}

	/**
	 * 마스터 목록 로드
	 */
	function loadMasterList() {
		showGridSpinner();

		$.ajax({
			url: '/mng/mng_master/get_master_list',
			type: 'GET',
			dataType: 'json',
			success: function(response) {
				hideGridSpinner();

				if (response.success) {
					masterGrid.option('dataModel.data', response.data);
					masterGrid.refreshDataAndView();
				} else {
					showToast(response.message || '마스터 목록을 불러오는데 실패했습니다.', 'error');
				}
			},
			error: function(xhr, status, error) {
				hideGridSpinner();
				console.error('마스터 목록 로드 오류:', error);
				showToast('마스터 목록을 불러오는데 실패했습니다.', 'error');
			}
		});
	}


	/**
	 * 마스터 정보 수정 Offcanvas 열기
	 */
	function openMasterOffcanvas(rowData) {
		$('#edit_user_id').val(rowData.user_id);
		$('#edit_user_name').val(rowData.user_name);
		$('#edit_user_mail').val(rowData.user_mail);
		$('#edit_user_hp').val(rowData.user_hp);

		// 마스터 메뉴 권한 체크박스 설정
		$('.menu-checkbox').prop('checked', false);
		if (Array.isArray(rowData.master_managed_menus)) {
			rowData.master_managed_menus.forEach(function(menuKey) {
				$(`#menu_${menuKey}`).prop('checked', true);
			});
		}

		// 메뉴 전체 선택 상태 업데이트
		updateMenuSelectAllState();

		// 마스터 카테고리 권한 체크박스 설정
		$('.category-checkbox').prop('checked', false);
		if (Array.isArray(rowData.master_managed_category) && rowData.master_managed_category.length > 0) {
			rowData.master_managed_category.forEach(function(categoryIdx) {
				$(`#category_${categoryIdx}`).prop('checked', true);
			});
		}

		// 카테고리 전체 선택 상태 업데이트
		updateCategorySelectAllState();

		masterOffcanvas.show();
	}

	/**
	 * 마스터 정보 저장
	 */
	$('#saveMasterBtn').on('click', function() {
		const userId = $('#edit_user_id').val();
		const userName = $('#edit_user_name').val().trim();
		const userMail = $('#edit_user_mail').val().trim();
		const userHp = $('#edit_user_hp').val().trim();

		if (!userName) {
			showToast('이름을 입력해주세요.', 'warning');
			return;
		}

		if (!userMail) {
			showToast('이메일을 입력해주세요.', 'warning');
			return;
		}

		// 마스터 메뉴 권한 수집
		const masterManagedMenus = [];
		$('.menu-checkbox:checked').each(function() {
			masterManagedMenus.push($(this).val());
		});

		// 마스터 카테고리 권한 수집
		const masterManagedCategory = [];
		$('.category-checkbox:checked').each(function() {
			masterManagedCategory.push($(this).val());
		});

		const data = {
			user_id: userId,
			user_name: userName,
			user_mail: userMail,
			user_hp: userHp,
			master_managed_menus: JSON.stringify(masterManagedMenus),
			master_managed_category: JSON.stringify(masterManagedCategory)
		};

		$.ajax({
			url: '/mng/mng_master/update_master',
			type: 'POST',
			data: data,
			dataType: 'json',
			success: function(response) {
				if (response.success) {
					showToast(response.message, 'success');
					masterOffcanvas.hide();
					loadMasterList();
				} else {
					showToast(response.message || '마스터 정보 업데이트에 실패했습니다.', 'error');
				}
			},
			error: function(xhr, status, error) {
				console.error('마스터 업데이트 오류:', error);
				showToast('마스터 정보 업데이트에 실패했습니다.', 'error');
			}
		});
	});

	/**
	 * 그리드 스피너 표시
	 */
	function showGridSpinner() {
		$('#gridSpinner').removeClass('d-none').addClass('d-flex');
	}

	/**
	 * 그리드 스피너 숨김
	 */
	function hideGridSpinner() {
		$('#gridSpinner').removeClass('d-flex').addClass('d-none');
	}


});
