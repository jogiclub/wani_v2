'use strict'

$(document).ready(function () {
	// 전역 변수
	let moimGrid;
	let selectedOrgId = window.moimPageData.currentOrgId;
	let selectedCategoryCode = null;
	let selectedType = null;
	let splitInstance;
	let categoryPositions = {}; // 카테고리별 직책 저장

	// 초기화 함수 호출
	initializePage();

	/**
	 * 페이지 초기화
	 */
	function initializePage() {
		showAllSpinners();
		if (!validateLibraries()) {
			hideAllSpinners();
			return;
		}
		try {
			initializeSplitJS();
			initializeFancytree();
			initializeParamQuery();
			initializeSelect2();
			bindGlobalEvents();
			setupCleanupEvents();
		} catch (error) {
			console.error('초기화 중 오류:', error);
			hideAllSpinners();
			showToast('페이지 초기화 중 오류가 발생했습니다.', 'error');
		}
	}

	/**
	 * 라이브러리 유효성 검사
	 */
	function validateLibraries() {
		const libraries = {
			'ParamQuery': $.fn.pqGrid,
			'Fancytree': $.fn.fancytree,
			'Split.js': window.Split,
			'Select2': $.fn.select2
		};
		for (const [name, lib] of Object.entries(libraries)) {
			if (!lib) {
				console.error(`${name} 라이브러리가 로드되지 않았습니다.`);
				showToast(`${name} 라이브러리 로드 실패`, 'error');
				return false;
			}
		}
		return true;
	}

	/**
	 * Split.js 초기화
	 */
	function initializeSplitJS() {
		const savedSizes = localStorage.getItem('moim-split-sizes');
		const initialSizes = savedSizes ? JSON.parse(savedSizes) : [20, 80];

		splitInstance = Split(['#left-pane', '#right-pane'], {
			sizes: initialSizes,
			minSize: [200, 300],
			gutterSize: 7,
			cursor: 'col-resize',
			onDragEnd: function (sizes) {
				if (moimGrid) moimGrid.pqGrid("refresh");
				localStorage.setItem('moim-split-sizes', JSON.stringify(sizes));
			}
		});
	}

	/**
	 * Fancytree 초기화
	 */
	function initializeFancytree() {
		showTreeSpinner();
		$.ajax({
			url: window.moimPageData.baseUrl + 'moim/get_category_tree',
			method: 'POST',
			dataType: 'json',
			success: function (treeData) {
				hideTreeSpinner();
				if (!treeData || treeData.length === 0) {
					showToast('카테고리 데이터가 없습니다.', 'warning');
					return;
				}
				setupFancytreeInstance(treeData);
			},
			error: function (xhr, status, error) {
				hideTreeSpinner();
				console.error('트리 데이터 로드 실패:', error);
				showToast('카테고리 정보를 불러오는데 실패했습니다.', 'error');
			}
		});
	}

	/**
	 * Fancytree 인스턴스 설정
	 */
	function setupFancytreeInstance(treeData) {
		$("#categoryTree").fancytree({
			source: treeData,
			extensions: ["persist"],
			persist: {
				cookiePrefix: "fancytree-moim-",
				expandLazy: true,
				overrideSource: false,
				store: "auto"
			},
			activate: function (event, data) {
				handleNodeActivation(data.node);
			},
			click: function(event, data) {
				if (data.targetType === "title") {
					// data.node.toggleExpanded();
				}
			}
		});

		restorePreviousSelection() || selectFirstNode();
	}

	/**
	 * 노드 활성화 처리
	 */
	function handleNodeActivation(node) {
		const nodeData = node.data;
		categoryPositions = {};

		if (nodeData.type === 'org') {
			selectedOrgId = nodeData.org_id;
			selectedCategoryCode = null;
			selectedType = 'org';
			$('#btnRenameCategory, #btnDeleteCategory, #btnMoveCategory').prop('disabled', true);
		} else if (nodeData.type === 'category') {
			selectedOrgId = window.moimPageData.currentOrgId;
			selectedCategoryCode = nodeData.category_code;
			selectedType = 'category';
			categoryPositions = nodeData.positions || [];
			$('#btnRenameCategory, #btnDeleteCategory, #btnMoveCategory').prop('disabled', false);
		}

		loadMoimMembers();
		saveCurrentSelection();
	}

	/**
	 * 이전 선택 복원
	 */
	function restorePreviousSelection() {
		try {
			const savedSelection = localStorage.getItem('moim_selected_category');
			if (!savedSelection) return false;

			const selectionData = JSON.parse(savedSelection);
			if (Date.now() - selectionData.timestamp > 7 * 24 * 60 * 60 * 1000) {
				localStorage.removeItem('moim_selected_category');
				return false;
			}

			const tree = $("#categoryTree").fancytree("getTree");
			const nodeToSelect = tree.getNodeByKey(selectionData.key);

			if (nodeToSelect) {
				nodeToSelect.setActive(true);
				nodeToSelect.setFocus(true);
				if (nodeToSelect.parent && !nodeToSelect.parent.isRootNode()) {
					nodeToSelect.parent.setExpanded(true);
				}
				return true;
			}
		} catch (error) {
			console.error('이전 선택 복원 실패:', error);
		}
		return false;
	}

	/**
	 * 첫 번째 노드 자동 선택
	 */
	function selectFirstNode() {
		const tree = $("#categoryTree").fancytree("getTree");
		if (tree && tree.rootNode && tree.rootNode.children.length > 0) {
			const firstNode = tree.rootNode.children[0];
			firstNode.setActive(true);
			firstNode.setFocus(true);
			firstNode.setExpanded(true);
		}
	}

	/**
	 * 현재 선택 정보 저장
	 */
	function saveCurrentSelection() {
		const tree = $("#categoryTree").fancytree("getTree");
		const activeNode = tree.getActiveNode();
		if (!activeNode) return;

		const selectionData = {
			key: activeNode.key,
			timestamp: Date.now()
		};
		localStorage.setItem('moim_selected_category', JSON.stringify(selectionData));
	}

	/**
	 * ParamQuery Grid 초기화
	 */
	function initializeParamQuery() {
		const colModel = [
			{
				title: '<input type="checkbox" id="selectAllCheckbox" />',
				dataIndx: "checkbox",
				width: 50,
				align: "center",
				sortable: false,
				editable: false,
				render: function (ui) {
					return `<input type="checkbox" class="moim-checkbox" data-moim-idx="${ui.rowData.moim_idx}">`;
				}
			},
			{ title: "이름", dataIndx: "member_name", width: 120, align: "center" },
			{ title: "성별", dataIndx: "member_sex_str", width: 60, align: "center" },
			{ title: "생년월일", dataIndx: "member_birth_formatted", width: 120, align: "center" },
			{ title: "휴대폰", dataIndx: "member_phone", width: 150, align: "center" },
			{ title: "직분", dataIndx: "position_name", width: 120, align: "center" },
			{ title: "모임 직책", dataIndx: "moim_position", width: 150, align: "center",
				editor: {
					type: 'select',
					options: function(ui) {
						const node = $("#categoryTree").fancytree("getTree").getActiveNode();
						if (node && node.data.type === 'category') {
							return node.data.positions || [];
						}
						return [];
					}
				}
			}
		];

		moimGrid = $("#moimGrid").pqGrid({
			width: "100%",
			height: "100%",
			dataModel: { data: [] },
			colModel: colModel,
			strNoRows: '소모임 회원이 없습니다',
			selectionModel: { type: 'none' }, // 기본 선택 비활성화
			editable: true,
			editModel: {
				saveKey: $.ui.keyCode.ENTER,
				onSave: 'next',
				onBlur: 'save'
			},
			editor: {
				select: function( event, ui ) {
					moimGrid.pqGrid("saveEditCell");
				}
			},
			beforeEdit: function( event, ui ) {
				if (ui.dataIndx === "moim_position") {
					const node = $("#categoryTree").fancytree("getTree").getActiveNode();
					if (!node || node.data.type !== 'category' || !node.data.positions || node.data.positions.length === 0) {
						showToast('이 카테고리에는 설정된 직책이 없습니다.', 'warning');
						return false;
					}
				}
			},
			cellSave: function( event, ui ) {
				if (ui.dataIndx === "moim_position") {
					updateMoimPosition(ui.rowData.moim_idx, ui.newVal);
				}
			},
			rowInit: function (ui) {
				var style = "height: 40px;";
				return {
					style: style,
				};
			},
			numberCell: { show: false },
			title: false,
			resizable: true,
			sortable: true,
			wrap: false,
			hwrap: false,
			columnBorders: true,
			rowBorders: true,
			refresh: function() {
				updateSelectAllCheckbox();
			}
		});
	}

	/**
	 * Select2 초기화
	 */
	function initializeSelect2() {
		$('#memberSelect').select2({
			dropdownParent: $('#addMemberModal'),
			placeholder: '회원 검색',
			allowClear: true,
			ajax: {
				url: window.moimPageData.baseUrl + 'moim/search_members',
				type: 'POST',
				dataType: 'json',
				delay: 250,
				data: function (params) {
					return {
						org_id: selectedOrgId,
						keyword: params.term
					};
				},
				processResults: function (data) {
					if (data.success) {
						return {
							results: data.data.map(member => ({
								id: member.member_idx,
								text: `${member.member_name} (${member.member_phone || '연락처 없음'})`
							}))
						};
					}
					return { results: [] };
				},
				cache: true
			}
		});
	}

	/**
	 * 전역 이벤트 바인딩
	 */
	function bindGlobalEvents() {
		$('#btnSearch').on('click', () => filterGrid($('#searchKeyword').val().trim()));
		$('#searchKeyword').on('keypress', e => e.which === 13 && $('#btnSearch').click());
		$('#btnAddMembers').on('click', openAddMemberModal);
		$('#btnSaveMembers').on('click', saveMembers);
		$('#btnDeleteSelected').on('click', deleteSelectedMembers);

		// 체크박스 이벤트
		$(document).on('change', '#selectAllCheckbox', function () {
			const isChecked = $(this).prop('checked');
			$('.moim-checkbox').prop('checked', isChecked);
		});

		$(document).on('change', '.moim-checkbox', function () {
			updateSelectAllCheckbox();
		});


		// 카테고리 관리 버튼
		$('#btnAddCategory').on('click', function() {
			addCategory('새 카테고리');
		});
		$('#btnRenameCategory').on('click', openRenameCategoryModal);
		$('#btnDeleteCategory').on('click', openDeleteCategoryModal);
		$('#btnSaveCategory').on('click', saveCategory);
		$('#confirmDeleteCategoryBtn').on('click', confirmDeleteCategory);
		
		$('#btnMoveCategory').on('click', openMoveCategoryModal);
		$('#confirmMoveCategoryBtn').on('click', moveCategory);
	}

	/**
	 * 전체 선택 체크박스 상태 업데이트
	 */
	function updateSelectAllCheckbox() {
		const totalCheckboxes = $('.moim-checkbox').length;
		const checkedCheckboxes = $('.moim-checkbox:checked').length;
		const $selectAll = $('#selectAllCheckbox');

		if (totalCheckboxes === 0) {
			$selectAll.prop('checked', false).prop('indeterminate', false);
		} else if (checkedCheckboxes === 0) {
			$selectAll.prop('checked', false).prop('indeterminate', false);
		} else if (checkedCheckboxes === totalCheckboxes) {
			$selectAll.prop('checked', true).prop('indeterminate', false);
		} else {
			$selectAll.prop('checked', false).prop('indeterminate', true);
		}
	}

	/**
	 * 정리 이벤트 설정
	 */
	function setupCleanupEvents() {
		$(window).on('beforeunload', function () {
			if (splitInstance) splitInstance.destroy();
		});
	}

	/**
	 * 소모임 회원 목록 로드
	 */
	function loadMoimMembers() {
		if (!selectedOrgId || !selectedType) return;
		showGridSpinner();
		$.ajax({
			url: window.moimPageData.baseUrl + 'moim/get_moim_members',
			method: 'POST',
			data: {
				org_id: selectedOrgId,
				category_code: selectedCategoryCode,
				type: selectedType
			},
			dataType: 'json',
			success: function (response) {
				hideGridSpinner();
				if (response.success) {
					moimGrid.pqGrid("option", "dataModel.data", response.data);
					moimGrid.pqGrid("refreshDataAndView");
				} else {
					showToast(response.message || '회원 목록 로드 실패', 'error');
				}
			},
			error: function (xhr, status, error) {
				hideGridSpinner();
				console.error('회원 목록 로드 실패:', error);
				showToast('회원 목록을 불러오는 중 오류가 발생했습니다.', 'error');
			}
		});
	}

	/**
	 * 그리드 필터링
	 */
	function filterGrid(keyword) {
		if (!moimGrid) return;
		const rules = keyword ? [
			{ dataIndx: 'member_name', condition: 'contain', value: keyword },
			{ dataIndx: 'member_phone', condition: 'contain', value: keyword }
		] : [];
		moimGrid.pqGrid("filter", { mode: 'OR', rules: rules });
	}

	/**
	 * 회원 추가 모달 열기
	 */
	function openAddMemberModal() {
		const node = $("#categoryTree").fancytree("getTree").getActiveNode();
		if (!node || node.data.type !== 'category') {
			showToast('회원을 추가할 카테고리를 선택해주세요.', 'warning');
			return;
		}

		$('#addCategoryName').val(node.title.split(' (')[0]);
		$('#addCategoryCode').val(node.data.category_code);
		$('#memberSelect').val(null).trigger('change');

		const $positionSelect = $('#moimPosition');
		$positionSelect.empty().append('<option value="">선택</option>');
		if (node.data.positions && node.data.positions.length > 0) {
			node.data.positions.forEach(pos => {
				$positionSelect.append(`<option value="${pos}">${pos}</option>`);
			});
		}

		$('#addMemberModal').modal('show');
	}

	/**
	 * 회원 저장
	 */
	function saveMembers() {
		const memberIndices = $('#memberSelect').val();
		if (!memberIndices || memberIndices.length === 0) {
			showToast('추가할 회원을 선택해주세요.', 'warning');
			return;
		}

		showSpinner();
		$.ajax({
			url: window.moimPageData.baseUrl + 'moim/add_moim_members',
			method: 'POST',
			data: {
				org_id: selectedOrgId,
				category_code: $('#addCategoryCode').val(),
				member_indices: memberIndices,
				moim_position: $('#moimPosition').val()
			},
			dataType: 'json',
			success: function (response) {
				hideSpinner();
				if (response.success) {
					showToast(response.message, 'success');
					$('#addMemberModal').modal('hide');
					loadMoimMembers();
					refreshCategoryTree();
				} else {
					showToast(response.message || '회원 추가 실패', 'error');
				}
			},
			error: function (xhr, status, error) {
				hideSpinner();
				console.error('회원 추가 실패:', error);
				showToast('회원 추가 중 오류가 발생했습니다.', 'error');
			}
		});
	}

	/**
	 * 소모임 직책 수정
	 */
	function updateMoimPosition(moimIdx, newPosition) {
		showSpinner();
		$.ajax({
			url: window.moimPageData.baseUrl + 'moim/update_moim_position',
			method: 'POST',
			data: { moim_idx: moimIdx, moim_position: newPosition },
			dataType: 'json',
			success: function (response) {
				hideSpinner();
				if (response.success) {
					showToast(response.message, 'success');
					moimGrid.pqGrid("commit");
				} else {
					showToast(response.message || '직책 수정 실패', 'error');
					moimGrid.pqGrid("rollback");
				}
			},
			error: function (xhr, status, error) {
				hideSpinner();
				console.error('직책 수정 실패:', error);
				showToast('직책 수정 중 오류가 발생했습니다.', 'error');
				moimGrid.pqGrid("rollback");
			}
		});
	}

	/**
	 * 선택된 회원 삭제
	 */
	function deleteSelectedMembers() {
		const moimIndices = $('.moim-checkbox:checked').map(function() {
			return $(this).data('moim-idx');
		}).get();

		if (moimIndices.length === 0) {
			showToast('삭제할 회원을 선택해주세요.', 'warning');
			return;
		}

		showConfirmModal('선택 회원 삭제', `선택된 ${moimIndices.length}명의 회원을 소모임에서 삭제하시겠습니까?`, function() {
			showSpinner();
			$.ajax({
				url: window.moimPageData.baseUrl + 'moim/delete_moim_members',
				method: 'POST',
				data: { moim_indices: moimIndices },
				dataType: 'json',
				success: function (response) {
					hideSpinner();
					if (response.success) {
						showToast(response.message, 'success');
						loadMoimMembers();
						refreshCategoryTree();
					} else {
						showToast(response.message || '회원 삭제 실패', 'error');
					}
				},
				error: function (xhr, status, error) {
					hideSpinner();
					console.error('회원 삭제 실패:', error);
					showToast('회원 삭제 중 오류가 발생했습니다.', 'error');
				}
			});
		});
	}

	/**
	 * 카테고리 트리 새로고침
	 */
	function refreshCategoryTree() {
		showTreeSpinner();
		$.ajax({
			url: window.moimPageData.baseUrl + 'moim/get_category_tree',
			method: 'POST',
			dataType: 'json',
			success: function (treeData) {
				hideTreeSpinner();
				if (treeData) {
					const tree = $("#categoryTree").fancytree("getTree");
					tree.reload(treeData).done(function(){
						restorePreviousSelection() || selectFirstNode();
					});
				}
			},
			error: function () {
				hideTreeSpinner();
			}
		});
	}

	/**
	 * 카테고리명 변경 모달 열기
	 */
	function openRenameCategoryModal() {
		const node = $("#categoryTree").fancytree("getTree").getActiveNode();
		if (!node || node.data.type !== 'category') {
			showToast('이름을 변경할 카테고리를 선택하세요.', 'warning');
			return;
		}
		const currentName = node.title.split(' (')[0];

		$('#categoryFormModalTitle').text('카테고리명 변경');
		$('#categoryAction').val('rename');
		$('#categoryNameInput').val(currentName);
		$('#categoryFormModal').modal('show');
	}

	/**
	 * 카테고리 저장 (생성/수정)
	 */
	function saveCategory() {
		const action = $('#categoryAction').val();
		const categoryName = $('#categoryNameInput').val().trim();
		if (!categoryName) {
			showToast('카테고리 이름을 입력해주세요.', 'warning');
			return;
		}

		if (action === 'rename') {
			renameCategory(categoryName);
		}
	}

	/**
	 * 카테고리 추가
	 */
	function addCategory(categoryName) {
		const node = $("#categoryTree").fancytree("getTree").getActiveNode();
		const parentCode = (node && node.data.type === 'category') ? node.data.category_code : null;

		showSpinner();
		$.ajax({
			url: window.moimPageData.baseUrl + 'moim/add_category',
			method: 'POST',
			data: {
				org_id: selectedOrgId,
				parent_code: parentCode,
				category_name: categoryName
			},
			dataType: 'json',
			success: function(response) {
				hideSpinner();
				if (response.success) {
					showToast(response.message, 'success');
					refreshCategoryTree();
				} else {
					showToast(response.message || '카테고리 생성 실패', 'error');
				}
			},
			error: function() {
				hideSpinner();
				showToast('카테고리 생성 중 오류 발생', 'error');
			}
		});
	}

	/**
	 * 카테고리명 변경
	 */
	function renameCategory(newName) {
		const node = $("#categoryTree").fancytree("getTree").getActiveNode();
		if (!node || node.data.type !== 'category') {
			return;
		}

		showSpinner();
		$.ajax({
			url: window.moimPageData.baseUrl + 'moim/rename_category',
			method: 'POST',
			data: {
				org_id: selectedOrgId,
				category_code: node.data.category_code,
				new_name: newName
			},
			dataType: 'json',
			success: function(response) {
				hideSpinner();
				if (response.success) {
					showToast(response.message, 'success');
					$('#categoryFormModal').modal('hide');
					refreshCategoryTree();
				} else {
					showToast(response.message || '이름 변경 실패', 'error');
				}
			},
			error: function() {
				hideSpinner();
				showToast('이름 변경 중 오류 발생', 'error');
			}
		});
	}

	/**
	 * 카테고리 삭제 모달 열기
	 */
	function openDeleteCategoryModal() {
		const node = $("#categoryTree").fancytree("getTree").getActiveNode();
		if (!node || node.data.type !== 'category') {
			showToast('삭제할 카테고리를 선택하세요.', 'warning');
			return;
		}
		const categoryName = node.title.split(' (')[0];
		$('#deleteCategoryMessage').text(`'${categoryName}' 카테고리를 삭제하시겠습니까?`);
		$('#deleteCategoryModal').modal('show');
	}

	/**
	 * 카테고리 삭제 확인
	 */
	function confirmDeleteCategory() {
		const node = $("#categoryTree").fancytree("getTree").getActiveNode();
		if (!node || node.data.type !== 'category') return;

		showSpinner();
		$.ajax({
			url: window.moimPageData.baseUrl + 'moim/delete_category',
			method: 'POST',
			data: {
				org_id: selectedOrgId,
				category_code: node.data.category_code
			},
			dataType: 'json',
			success: function(response) {
				hideSpinner();
				$('#deleteCategoryModal').modal('hide');
				if (response.success) {
					showToast(response.message, 'success');
					refreshCategoryTree();
				} else {
					showToast(response.message || '카테고리 삭제 실패', 'error');
				}
			},
			error: function() {
				hideSpinner();
				$('#deleteCategoryModal').modal('hide');
				showToast('카테고리 삭제 중 오류 발생', 'error');
			}
		});
	}

	/**
	 * 카테고리 이동 모달 열기
	 */
	function openMoveCategoryModal() {
		const tree = $("#categoryTree").fancytree("getTree");
		const node = tree.getActiveNode();

		if (!node || node.data.type !== 'category') {
			showToast('이동할 카테고리를 선택해주세요.', 'warning');
			return;
		}

		const $select = $('#moveToCategoryCode');
		$select.empty().append('<option value="">최상위로 이동</option>');

		tree.visit(function (n) {
			if (n.data.type === 'category' && n.key !== node.key && !n.isDescendantOf(node)) {
				const level = n.getLevel() - 1;
				const indent = '&nbsp;'.repeat(level * 4);
				$select.append(
					$('<option></option>')
						.val(n.data.category_code)
						.html(indent + n.title.split(' (')[0])
				);
			}
		});

		$('#moveCategoryMessage').text(`'${node.title.split(' (')[0]}' 카테고리를 어디로 이동하시겠습니까?`);
		$('#moveCategoryModal').modal('show');
	}

	/**
	 * 카테고리 이동
	 */
	function moveCategory() {
		const tree = $("#categoryTree").fancytree("getTree");
		const node = tree.getActiveNode();
		const targetCode = $('#moveToCategoryCode').val() || null;

		showSpinner();
		$.ajax({
			url: window.moimPageData.baseUrl + 'moim/move_category',
			method: 'POST',
			data: {
				org_id: selectedOrgId,
				source_code: node.data.category_code,
				target_code: targetCode,
				hit_mode: 'over'
			},
			dataType: 'json',
			success: function(response) {
				hideSpinner();
				$('#moveCategoryModal').modal('hide');
				if (response.success) {
					showToast(response.message, 'success');
					refreshCategoryTree();
				} else {
					showToast(response.message || '이동 실패', 'error');
				}
			},
			error: function() {
				hideSpinner();
				$('#moveCategoryModal').modal('hide');
				showToast('이동 중 오류 발생', 'error');
			}
		});
	}

	// 스피너 함수들
	function showSpinner() { $('#globalSpinner').removeClass('d-none').addClass('d-flex'); }
	function hideSpinner() { $('#globalSpinner').removeClass('d-flex').addClass('d-none'); }
	function showAllSpinners() { showTreeSpinner(); showGridSpinner(); }
	function hideAllSpinners() { hideTreeSpinner(); hideGridSpinner(); }
	function showTreeSpinner() { $('#treeSpinner').removeClass('d-none').addClass('d-flex'); }
	function hideTreeSpinner() { $('#treeSpinner').removeClass('d-flex').addClass('d-none'); }
	function showGridSpinner() { $('#gridSpinner').removeClass('d-none').addClass('d-flex'); }
	function hideGridSpinner() { $('#gridSpinner').removeClass('d-flex').addClass('d-none'); }
});
