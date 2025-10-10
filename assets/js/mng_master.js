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

	// PQGrid 초기화
	initMasterGrid();

	// 마스터 목록 로드
	loadMasterList();

	/**
	 * 날짜 포맷팅 함수
	 */
	function formatDateTime(dateString) {
		if (!dateString) return '';

		const date = new Date(dateString);
		const year = date.getFullYear();
		const month = String(date.getMonth() + 1).padStart(2, '0');
		const day = String(date.getDate()).padStart(2, '0');
		const hours = String(date.getHours()).padStart(2, '0');
		const minutes = String(date.getMinutes()).padStart(2, '0');

		return `${year}-${month}-${day} ${hours}:${minutes}`;
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
			updateSelectAllState();
		});
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
				width: 130
			},
			{
				title: '메뉴 권한',
				dataIndx: 'managed_menus',
				width: 200,
				render: function(ui) {
					if (!ui.cellData || !Array.isArray(ui.cellData) || ui.cellData.length === 0) {
						return '<span class="text-muted">미설정</span>';
					}
					return ui.cellData.join(', ');
				}
			},
			{
				title: '카테고리 권한',
				dataIndx: 'managed_areas',
				width: 200,
				render: function(ui) {
					if (!ui.cellData || !Array.isArray(ui.cellData) || ui.cellData.length === 0) {
						return '<span class="text-muted">미설정</span>';
					}

					// 카테고리 ID를 이름으로 변환
					const categoryNames = ui.cellData.map(function(idx) {
						const category = topCategories.find(c => c.category_idx == idx);
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

		// 메뉴 권한 체크박스 설정
		$('#menu_permissions input[type="checkbox"]').prop('checked', false);
		if (Array.isArray(rowData.managed_menus)) {
			rowData.managed_menus.forEach(function(menu) {
				$(`#menu_${menu}`).prop('checked', true);
			});
		}

		// 카테고리 권한 체크박스 설정
		$('.category-checkbox').prop('checked', false);
		if (Array.isArray(rowData.managed_areas) && rowData.managed_areas.length > 0) {
			rowData.managed_areas.forEach(function(categoryIdx) {
				$(`#category_${categoryIdx}`).prop('checked', true);
			});
		}

		// 전체 선택 상태 업데이트
		updateSelectAllState();

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

		// 메뉴 권한 수집
		const managedMenus = [];
		$('#menu_permissions input[type="checkbox"]:checked').each(function() {
			managedMenus.push($(this).val());
		});

		// 카테고리 권한 수집 (체크된 항목만)
		const managedAreas = [];
		$('.category-checkbox:checked').each(function() {
			managedAreas.push($(this).val());
		});

		const data = {
			user_id: userId,
			user_name: userName,
			user_mail: userMail,
			user_hp: userHp,
			managed_menus: JSON.stringify(managedMenus),
			managed_areas: JSON.stringify(managedAreas)
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

	/**
	 * Toast 메시지 표시
	 */
	function showToast(message, type = 'info') {
		const bgColor = {
			'success': 'bg-success',
			'error': 'bg-danger',
			'warning': 'bg-warning',
			'info': 'bg-info'
		}[type] || 'bg-info';

		const toastHtml = `
			<div class="toast align-items-center text-white ${bgColor} border-0" role="alert" aria-live="assertive" aria-atomic="true">
				<div class="d-flex">
					<div class="toast-body">${message}</div>
					<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
				</div>
			</div>
		`;

		let toastContainer = $('#toastContainer');
		if (toastContainer.length === 0) {
			$('body').append('<div id="toastContainer" class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 9999;"></div>');
			toastContainer = $('#toastContainer');
		}

		toastContainer.append(toastHtml);
		const toastElement = toastContainer.find('.toast').last()[0];
		const toast = new bootstrap.Toast(toastElement, { delay: 3000 });
		toast.show();

		$(toastElement).on('hidden.bs.toast', function() {
			$(this).remove();
		});
	}
});
