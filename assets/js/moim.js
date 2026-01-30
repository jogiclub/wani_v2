/**
 * 파일 위치: assets/js/moim.js
 * 역할: 소모임 관리 화면 프론트엔드 로직
 */

'use strict'

$(document).ready(function () {
	// 전역 변수 영역
	let moimGrid;                     // ParamQuery Grid 인스턴스
	let selectedOrgId = null;         // 선택된 조직 ID
	let selectedCategoryCode = null;  // 선택된 카테고리 코드
	let selectedCategoryName = null;  // 선택된 카테고리 이름
	let selectedPositions = [];       // 선택된 카테고리의 직책 목록
	let selectedType = null;          // 선택된 타입 ('org', 'category')
	let splitInstance = null;         // Split.js 인스턴스
	let categoryData = [];            // 카테고리 데이터

	// 초기화 시도
	setTimeout(function () {
		initializePage();
	}, 500);

	/**
	 * 페이지 초기화 메인 함수
	 */
	function initializePage() {
		showAllSpinners();

		// 라이브러리 검증
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
	 * 라이브러리 검증
	 */
	function validateLibraries() {
		if (typeof $.fn.pqGrid === 'undefined') {
			console.error('ParamQuery 라이브러리가 로드되지 않았습니다.');
			showToast('ParamQuery 라이브러리 로드 실패', 'error');
			return false;
		}

		if (typeof $.fn.fancytree === 'undefined') {
			console.error('Fancytree 라이브러리가 로드되지 않았습니다.');
			showToast('Fancytree 라이브러리 로드 실패', 'error');
			return false;
		}

		if (typeof Split === 'undefined') {
			console.error('Split.js 라이브러리가 로드되지 않았습니다.');
			showToast('Split.js 라이브러리 로드 실패', 'error');
			return false;
		}

		if (typeof $.fn.select2 === 'undefined') {
			console.error('Select2 라이브러리가 로드되지 않았습니다.');
			showToast('Select2 라이브러리 로드 실패', 'error');
			return false;
		}

		return true;
	}

	/**
	 * Split.js 초기화
	 */
	function initializeSplitJS() {
		try {
			splitInstance = Split(['#left-pane', '#right-pane'], {
				sizes: [15, 85],
				minSize: [50, 50],
				gutterSize: 7,
				cursor: 'col-resize',
				direction: 'horizontal',
				onDragEnd: function (sizes) {
					if (moimGrid) {
						try {
							moimGrid.pqGrid("refresh");
						} catch (error) {
							console.error('그리드 리프레시 실패:', error);
						}
					}
					localStorage.setItem('moim-split-sizes', JSON.stringify(sizes));
				}
			});

			// 저장된 크기 복원
			const savedSizes = localStorage.getItem('moim-split-sizes');
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
			showToast('화면 분할 기능 초기화에 실패했습니다.', 'error');
		}
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
				if (!treeData || treeData.length === 0) {
					hideTreeSpinner();
					showToast('카테고리 데이터가 없습니다.', 'warning');
					return;
				}

				setupFancytreeInstance(treeData);
				hideTreeSpinner();
			},
			error: function (xhr, status, error) {
				console.error('트리 데이터 로드 실패:', error);
				hideTreeSpinner();
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
				const node = data.node;
				const nodeData = node.data;

				if (nodeData.type === 'org') {
					selectedOrgId = nodeData.org_id;
					selectedCategoryCode = null;
					selectedCategoryName = null;
					selectedPositions = [];
					selectedType = 'org';
					$('#selectedCategoryName').html('<i class="bi bi-building"></i> ' + node.title.split(' (')[0]);
				} else if (nodeData.type === 'category') {
					selectedOrgId = window.moimPageData.currentOrgId;
					selectedCategoryCode = nodeData.category_code;
					selectedCategoryName = node.title.split(' (')[0];
					selectedPositions = nodeData.positions || [];
					selectedType = 'category';
					$('#selectedCategoryName').html('<i class="bi bi-people"></i> ' + selectedCategoryName);
				}

				loadMoimMembers();
			},
			click: function (event, data) {
				if (data.targetType === 'title' || data.targetType === 'icon') {
					data.node.setActive();
				}
			}
		});

		// 첫 번째 노드 자동 선택
		restorePreviousSelection() || selectFirstNode();
	}

	/**
	 * 이전 선택 복원
	 */
	function restorePreviousSelection() {
		try {
			const savedSelection = localStorage.getItem('moim_selected_category');
			if (!savedSelection) {
				return false;
			}

			const selectionData = JSON.parse(savedSelection);
			const sevenDaysAgo = Date.now() - (7 * 24 * 60 * 60 * 1000);

			if (selectionData.timestamp < sevenDaysAgo) {
				localStorage.removeItem('moim_selected_category');
				return false;
			}

			const tree = $("#categoryTree").fancytree("getTree");
			let nodeToSelect = null;

			if (selectionData.type === 'category' && selectionData.category_code) {
				nodeToSelect = tree.getNodeByKey('category_' + selectionData.category_code);
			} else if (selectionData.type === 'org' && selectionData.org_id) {
				nodeToSelect = tree.getNodeByKey('org_' + selectionData.org_id);
			}

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
	 * ParamQuery Grid 초기화
	 */
	function initializeParamQuery() {
		showGridSpinner();

		try {
			moimGrid = $("#moimGrid").pqGrid({
				width: "100%",
				height: "100%",
				dataModel: { data: [] },
				colModel: [
					{
						title: "",
						dataIndx: "photo",
						width: 60,
						align: "center",
						render: function(ui) {
							const photoUrl = ui.cellData || '/assets/images/photo_no.png';
							return '<img src="' + photoUrl + '" class="rounded-circle" style="width: 40px; height: 40px; object-fit: cover;">';
						}
					},
					{
						title: "이름",
						dataIndx: "member_name",
						width: 100,
						align: "center"
					},
					{
						title: "성별",
						dataIndx: "member_sex_str",
						width: 60,
						align: "center"
					},
					{
						title: "직위/직분",
						dataIndx: "position_name",
						width: 120,
						align: "center",
						render: function(ui) {
							const position = ui.rowData.position_name || '';
							const duty = ui.rowData.duty_name || '';
							const parts = [];
							if (position) parts.push(position);
							if (duty) parts.push(duty);
							return parts.join(' / ');
						}
					},
					{
						title: "모임직책",
						dataIndx: "moim_position",
						width: 120,
						align: "center",
						editable: true,
						editor: {
							type: 'select',
							options: [],
							prepend: {'': '선택'}
						},
						render: function(ui) {
							return ui.cellData || '';
						}
					},
					{
						title: "휴대폰번호",
						dataIndx: "member_phone",
						width: 130,
						align: "center"
					},
					{
						title: "생년월일",
						dataIndx: "member_birth_formatted",
						width: 110,
						align: "center"
					},
					{
						title: "주소",
						dataIndx: "address_full",
						width: 250
					},
					{
						title: "상세주소",
						dataIndx: "member_address_detail",
						width: 200
					},
					{
						title: "관리",
						dataIndx: "moim_idx",
						width: 80,
						align: "center",
						render: function(ui) {
							return `
								<button type="button" class="btn btn-sm btn-outline-danger btn-delete-member" data-moim-idx="${ui.cellData}">
									<i class="bi bi-trash"></i>
								</button>
							`;
						}
					}
				],
				strNoRows: '소모임 회원이 없습니다',
				selectionModel: { type: 'row', mode: 'single' },
				numberCell: { show: true },
				title: false,
				resizable: true,
				sortable: true,
				hoverMode: 'row',
				wrap: false,
				columnBorders: true,
				rowBorders: true,
				cellSave: function(event, ui) {
					// 직책 변경 시 서버 업데이트
					if (ui.dataIndx === 'moim_position') {
						updateMoimPosition(ui.rowData.moim_idx, ui.newVal);
					}
				}
			});

			// 그리드 이벤트 바인딩
			bindGridEvents();

			hideGridSpinner();
		} catch (error) {
			console.error('ParamQuery Grid 초기화 실패:', error);
			hideGridSpinner();
			showToast('그리드 초기화에 실패했습니다.', 'error');
		}
	}

	/**
	 * 그리드 이벤트 바인딩
	 */
	function bindGridEvents() {
		// 삭제 버튼 클릭
		$(document).on('click', '.btn-delete-member', function(e) {
			e.preventDefault();
			const moimIdx = $(this).data('moim-idx');
			confirmDeleteMember(moimIdx);
		});
	}

	/**
	 * Select2 초기화
	 */
	function initializeSelect2() {
		$('#memberSelect').select2({
			width: '100%',
			placeholder: '회원을 검색하세요',
			allowClear: true,
			multiple: true,
			ajax: {
				url: window.moimPageData.baseUrl + 'moim/search_members',
				type: 'POST',
				dataType: 'json',
				delay: 250,
				data: function(params) {
					return {
						org_id: window.moimPageData.currentOrgId,
						keyword: params.term
					};
				},
				processResults: function(response) {
					if (response.success) {
						return {
							results: response.data.map(function(member) {
								return {
									id: member.member_idx,
									text: member.member_name + (member.member_phone ? ' (' + member.member_phone + ')' : '')
								};
							})
						};
					}
					return { results: [] };
				},
				cache: true
			},
			minimumInputLength: 1,
			language: {
				inputTooShort: function() {
					return '검색어를 입력하세요 (최소 1자)';
				},
				searching: function() {
					return '검색 중...';
				},
				noResults: function() {
					return '검색 결과가 없습니다.';
				}
			}
		});
	}

	/**
	 * 전역 이벤트 바인딩
	 */
	function bindGlobalEvents() {
		// 회원 추가 버튼
		$('#btnAddMembers').off('click').on('click', function() {
			openAddMemberModal();
		});

		// 카테고리 관리 버튼
		$('#btnManageCategory').off('click').on('click', function() {
			openCategoryModal();
		});

		// 회원 추가 저장 버튼
		$('#btnSaveMembers').off('click').on('click', function() {
			saveMembers();
		});

		// 카테고리 저장 버튼
		$('#btnSaveCategory').off('click').on('click', function() {
			saveCategory();
		});

		// 검색 버튼
		$('#btnSearch').off('click').on('click', function() {
			const keyword = $('#searchKeyword').val().trim();
			filterGrid(keyword);
		});

		// 검색 엔터키
		$('#searchKeyword').off('keypress').on('keypress', function(e) {
			if (e.which === 13) {
				e.preventDefault();
				const keyword = $(this).val().trim();
				filterGrid(keyword);
			}
		});

		// 윈도우 리사이즈
		$(window).off('resize.moim').on('resize.moim', debounce(function() {
			if (moimGrid) {
				try {
					moimGrid.pqGrid("refresh");
				} catch (error) {
					console.error('윈도우 리사이즈 시 그리드 리프레시 실패:', error);
				}
			}
		}, 250));
	}

	/**
	 * 정리 이벤트 설정
	 */
	function setupCleanupEvents() {
		$(window).on('beforeunload', function() {
			if (splitInstance) {
				try {
					splitInstance.destroy();
				} catch (error) {
					console.error('Split.js 인스턴스 정리 실패:', error);
				}
			}
		});
	}

	/**
	 * 소모임 회원 목록 로드
	 */
	function loadMoimMembers() {
		if (!selectedOrgId || !selectedType) {
			return;
		}

		// 선택 정보 저장
		saveCurrentSelection();

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
			success: function(response) {
				hideGridSpinner();

				if (response.success) {
					updateGrid(response.data);
					updatePositionEditor();
					$('#totalMemberCount').text(response.total_count);
				} else {
					showToast(response.message || '회원 목록을 불러오는데 실패했습니다.', 'error');
				}
			},
			error: function(xhr, status, error) {
				console.error('회원 목록 로드 실패:', error);
				hideGridSpinner();
				showToast('회원 목록을 불러오는 중 오류가 발생했습니다.', 'error');
			}
		});
	}

	/**
	 * 직책 에디터 옵션 업데이트
	 */
	function updatePositionEditor() {
		if (!moimGrid || !selectedPositions || selectedPositions.length === 0) {
			return;
		}

		const colModel = moimGrid.pqGrid("option", "colModel");
		const positionCol = colModel.find(col => col.dataIndx === 'moim_position');

		if (positionCol && positionCol.editor) {
			// 직책 옵션 생성
			const options = [];
			selectedPositions.forEach(function(position) {
				options.push(position);
			});

			positionCol.editor.options = options;
			moimGrid.pqGrid("option", "colModel", colModel);
			moimGrid.pqGrid("refresh");
		}
	}

	/**
	 * 현재 선택 정보 저장
	 */
	function saveCurrentSelection() {
		const selectionData = {
			type: selectedType,
			org_id: selectedOrgId,
			category_code: selectedCategoryCode,
			timestamp: Date.now()
		};

		try {
			localStorage.setItem('moim_selected_category', JSON.stringify(selectionData));
		} catch (error) {
			console.error('선택 정보 저장 실패:', error);
		}
	}

	/**
	 * 그리드 업데이트
	 */
	function updateGrid(data) {
		if (!moimGrid) {
			return;
		}

		try {
			moimGrid.pqGrid("option", "dataModel.data", data);
			moimGrid.pqGrid("refreshDataAndView");
		} catch (error) {
			console.error('그리드 업데이트 실패:', error);
		}
	}

	/**
	 * 그리드 필터링
	 */
	function filterGrid(keyword) {
		if (!moimGrid) {
			return;
		}

		try {
			if (!keyword) {
				moimGrid.pqGrid("filter", {
					mode: 'AND',
					rules: []
				});
			} else {
				moimGrid.pqGrid("filter", {
					mode: 'OR',
					rules: [
						{ dataIndx: 'member_name', condition: 'contain', value: keyword },
						{ dataIndx: 'member_phone', condition: 'contain', value: keyword }
					]
				});
			}
		} catch (error) {
			console.error('그리드 필터링 실패:', error);
		}
	}

	/**
	 * 회원 추가 모달 열기
	 */
	function openAddMemberModal() {
		if (!selectedCategoryCode) {
			showToast('소모임 카테고리를 선택해주세요.', 'warning');
			return;
		}

		$('#addMemberForm')[0].reset();
		$('#addOrgId').val(selectedOrgId);
		$('#addCategoryCode').val(selectedCategoryCode);
		$('#addCategoryName').val(selectedCategoryName);

		// Select2 초기화
		$('#memberSelect').val(null).trigger('change');

		// 직책 옵션 설정
		const $positionSelect = $('#moimPosition');
		$positionSelect.empty();
		$positionSelect.append('<option value="">선택</option>');

		if (selectedPositions && selectedPositions.length > 0) {
			selectedPositions.forEach(function(position) {
				$positionSelect.append('<option value="' + position + '">' + position + '</option>');
			});
		}

		$('#addMemberModal').modal('show');
	}

	/**
	 * 회원 추가 저장
	 */
	function saveMembers() {
		const memberIndices = $('#memberSelect').val();

		if (!memberIndices || memberIndices.length === 0) {
			showToast('회원을 선택해주세요.', 'warning');
			return;
		}

		const formData = {
			org_id: $('#addOrgId').val(),
			category_code: $('#addCategoryCode').val(),
			member_indices: memberIndices,
			moim_position: $('#moimPosition').val()
		};

		showSpinner();

		$.ajax({
			url: window.moimPageData.baseUrl + 'moim/add_moim_members',
			method: 'POST',
			data: formData,
			dataType: 'json',
			success: function(response) {
				hideSpinner();

				if (response.success) {
					showToast(response.message, 'success');
					$('#addMemberModal').modal('hide');
					loadMoimMembers();
					refreshCategoryTree();
				} else {
					showToast(response.message || '회원 추가에 실패했습니다.', 'error');
				}
			},
			error: function(xhr, status, error) {
				console.error('회원 추가 실패:', error);
				hideSpinner();
				showToast('회원 추가 중 오류가 발생했습니다.', 'error');
			}
		});
	}

	/**
	 * 직책 수정
	 */
	function updateMoimPosition(moimIdx, position) {
		$.ajax({
			url: window.moimPageData.baseUrl + 'moim/update_moim_position',
			method: 'POST',
			data: {
				moim_idx: moimIdx,
				moim_position: position
			},
			dataType: 'json',
			success: function(response) {
				if (response.success) {
					showToast(response.message, 'success');
				} else {
					showToast(response.message || '직책 수정에 실패했습니다.', 'error');
					loadMoimMembers(); // 실패 시 데이터 리로드
				}
			},
			error: function(xhr, status, error) {
				console.error('직책 수정 실패:', error);
				showToast('직책 수정 중 오류가 발생했습니다.', 'error');
				loadMoimMembers(); // 오류 시 데이터 리로드
			}
		});
	}

	/**
	 * 회원 삭제 확인
	 */
	function confirmDeleteMember(moimIdx) {
		showConfirmModal(
			'소모임 회원 삭제',
			'정말로 이 회원을 소모임에서 삭제하시겠습니까?',
			function() {
				deleteMember(moimIdx);
			}
		);
	}

	/**
	 * 회원 삭제
	 */
	function deleteMember(moimIdx) {
		showSpinner();

		$.ajax({
			url: window.moimPageData.baseUrl + 'moim/delete_moim_member',
			method: 'POST',
			data: { moim_idx: moimIdx },
			dataType: 'json',
			success: function(response) {
				hideSpinner();

				if (response.success) {
					showToast(response.message, 'success');
					loadMoimMembers();
					refreshCategoryTree();
				} else {
					showToast(response.message || '삭제에 실패했습니다.', 'error');
				}
			},
			error: function(xhr, status, error) {
				console.error('회원 삭제 실패:', error);
				hideSpinner();
				showToast('회원 삭제 중 오류가 발생했습니다.', 'error');
			}
		});
	}

	/**
	 * 카테고리 관리 모달 열기
	 */
	function openCategoryModal() {
		showSpinner();

		$.ajax({
			url: window.moimPageData.baseUrl + 'moim/get_category_tree',
			method: 'POST',
			dataType: 'json',
			success: function(treeData) {
				hideSpinner();

				// 조직 노드 제외하고 카테고리만 추출
				let categories = [];
				if (treeData && treeData.length > 0 && treeData[0].children) {
					categories = extractCategories(treeData[0].children);
				}

				const categoryJson = {
					categories: categories
				};

				$('#categoryJson').val(JSON.stringify(categoryJson, null, 2));
				$('#categoryModal').modal('show');
			},
			error: function(xhr, status, error) {
				console.error('카테고리 정보 로드 실패:', error);
				hideSpinner();
				showToast('카테고리 정보를 불러오는 중 오류가 발생했습니다.', 'error');
			}
		});
	}

	/**
	 * 트리 데이터에서 카테고리 추출
	 */
	function extractCategories(nodes) {
		const categories = [];

		nodes.forEach(function(node) {
			if (node.data && node.data.type === 'category') {
				const category = {
					code: node.data.category_code,
					name: node.title.split(' (')[0],
					order: categories.length + 1,
					positions: node.data.positions || []
				};

				if (node.children && node.children.length > 0) {
					category.children = extractCategories(node.children);
				} else {
					category.children = [];
				}

				categories.push(category);
			}
		});

		return categories;
	}

	/**
	 * 카테고리 저장
	 */
	function saveCategory() {
		const categoryJsonStr = $('#categoryJson').val().trim();

		// JSON 유효성 검증
		try {
			const categoryData = JSON.parse(categoryJsonStr);

			if (!categoryData.categories || !Array.isArray(categoryData.categories)) {
				showToast('올바른 카테고리 형식이 아닙니다.', 'warning');
				return;
			}
		} catch (error) {
			showToast('JSON 형식이 올바르지 않습니다.', 'error');
			return;
		}

		showSpinner();

		$.ajax({
			url: window.moimPageData.baseUrl + 'moim/save_category',
			method: 'POST',
			data: {
				org_id: window.moimPageData.currentOrgId,
				category_json: categoryJsonStr
			},
			dataType: 'json',
			success: function(response) {
				hideSpinner();

				if (response.success) {
					showToast(response.message, 'success');
					$('#categoryModal').modal('hide');
					refreshCategoryTree();
				} else {
					showToast(response.message || '카테고리 저장에 실패했습니다.', 'error');
				}
			},
			error: function(xhr, status, error) {
				console.error('카테고리 저장 실패:', error);
				hideSpinner();
				showToast('카테고리 저장 중 오류가 발생했습니다.', 'error');
			}
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
			success: function(treeData) {
				hideTreeSpinner();

				if (treeData && treeData.length > 0) {
					const tree = $("#categoryTree").fancytree("getTree");
					tree.reload(treeData);

					// 이전 선택 복원
					restorePreviousSelection() || selectFirstNode();
				}
			},
			error: function(xhr, status, error) {
				console.error('트리 새로고침 실패:', error);
				hideTreeSpinner();
			}
		});
	}

	/**
	 * 스피너 표시 함수들
	 */
	function showAllSpinners() {
		showTreeSpinner();
		showGridSpinner();
	}

	function hideAllSpinners() {
		hideTreeSpinner();
		hideGridSpinner();
	}

	function showTreeSpinner() {
		$('#treeSpinner').removeClass('d-none').addClass('d-flex');
	}

	function hideTreeSpinner() {
		$('#treeSpinner').removeClass('d-flex').addClass('d-none');
	}

	function showGridSpinner() {
		$('#gridSpinner').removeClass('d-none').addClass('d-flex');
	}

	function hideGridSpinner() {
		$('#gridSpinner').removeClass('d-flex').addClass('d-none');
	}

	function showSpinner() {
		if ($('.global-spinner').length > 0) {
			$('.global-spinner').show();
		}
	}

	function hideSpinner() {
		if ($('.global-spinner').length > 0) {
			$('.global-spinner').hide();
		}
	}

	/**
	 * Toast 메시지 표시
	 */
	function showToast(message, type) {
		type = type || 'info';

		if (typeof window.showToast === 'function') {
			window.showToast(message, type);
		} else {
			alert(message);
		}
	}

	/**
	 * Confirm 모달 표시
	 */
	function showConfirmModal(title, message, confirmCallback) {
		if (typeof window.showConfirmModal === 'function') {
			window.showConfirmModal(title, message, confirmCallback);
		} else {
			if (confirm(message)) {
				confirmCallback();
			}
		}
	}

	/**
	 * Debounce 함수
	 */
	function debounce(func, wait) {
		let timeout;
		return function() {
			const context = this;
			const args = arguments;
			clearTimeout(timeout);
			timeout = setTimeout(function() {
				func.apply(context, args);
			}, wait);
		};
	}
});
