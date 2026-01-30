/**
 * 파일 위치: assets/js/education.js
 * 역할: 교육관리 화면 프론트엔드 로직
 */

'use strict'

$(document).ready(function () {
	// 전역 변수 영역
	let eduGrid;                      // ParamQuery Grid 인스턴스
	let selectedOrgId = null;         // 선택된 조직 ID
	let selectedCategoryCode = null;  // 선택된 카테고리 코드
	let selectedType = null;          // 선택된 타입 ('org', 'category')
	let splitInstance = null;         // Split.js 인스턴스
	let categoryData = [];            // 카테고리 데이터
	let flatpickrStartInstance = null; // 시작일 Flatpickr 인스턴스
	let flatpickrEndInstance = null;   // 종료일 Flatpickr 인스턴스

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
			initializeFlatpickr();
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

		if (typeof flatpickr === 'undefined') {
			console.error('Flatpickr 라이브러리가 로드되지 않았습니다.');
			showToast('Flatpickr 라이브러리 로드 실패', 'error');
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
					if (eduGrid) {
						try {
							eduGrid.pqGrid("refresh");
						} catch (error) {
							console.error('그리드 리프레시 실패:', error);
						}
					}
					localStorage.setItem('education-split-sizes', JSON.stringify(sizes));
				}
			});

			// 저장된 크기 복원
			const savedSizes = localStorage.getItem('education-split-sizes');
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
			url: window.educationPageData.baseUrl + 'education/get_category_tree',
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
				cookiePrefix: "fancytree-education-",
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
					selectedType = 'org';
					$('#selectedCategoryName').html('<i class="bi bi-building"></i> ' + node.title);
				} else if (nodeData.type === 'category') {
					selectedOrgId = window.educationPageData.currentOrgId;
					selectedCategoryCode = nodeData.category_code;
					selectedType = 'category';
					$('#selectedCategoryName').html('<i class="bi bi-folder"></i> ' + node.title);
				}

				loadEduList();
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
			const savedSelection = localStorage.getItem('education_selected_category');
			if (!savedSelection) {
				return false;
			}

			const selectionData = JSON.parse(savedSelection);
			const sevenDaysAgo = Date.now() - (7 * 24 * 60 * 60 * 1000);

			if (selectionData.timestamp < sevenDaysAgo) {
				localStorage.removeItem('education_selected_category');
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
			eduGrid = $("#eduGrid").pqGrid({
				width: "100%",
				height: "100%",
				dataModel: { data: [] },
				colModel: [
					{
						title: "교육카테고리",
						dataIndx: "category_code",
						width: 120,
						align: "center"
					},
					{
						title: "교육명",
						dataIndx: "edu_name",
						width: 200,
						render: function(ui) {
							return '<a href="javascript:void(0)" class="text-primary edu-name-link" data-edu-idx="' + ui.rowData.edu_idx + '">' + ui.cellData + '</a>';
						}
					},
					{
						title: "교육지역",
						dataIndx: "edu_location",
						width: 150
					},
					{
						title: "교육기간",
						dataIndx: "edu_period_str",
						width: 200,
						align: "center"
					},
					{
						title: "요일",
						dataIndx: "edu_days_str",
						width: 150
					},
					{
						title: "시간대",
						dataIndx: "edu_times_str",
						width: 150
					},
					{
						title: "인도자",
						dataIndx: "edu_leader",
						width: 100,
						align: "center"
					},
					{
						title: "인도자연령",
						dataIndx: "edu_leader_age_str",
						width: 100,
						align: "center"
					},
					{
						title: "인도자성별",
						dataIndx: "edu_leader_gender_str",
						width: 100,
						align: "center"
					},
					{
						title: "관리",
						dataIndx: "edu_idx",
						width: 120,
						align: "center",
						render: function(ui) {
							return `
								<button type="button" class="btn btn-sm btn-outline-primary btn-edit-edu" data-edu-idx="${ui.cellData}">
									<i class="bi bi-pencil"></i>
								</button>
								<button type="button" class="btn btn-sm btn-outline-danger btn-delete-edu" data-edu-idx="${ui.cellData}">
									<i class="bi bi-trash"></i>
								</button>
							`;
						}
					}
				],
				strNoRows: '교육 정보가 없습니다',
				selectionModel: { type: 'row', mode: 'single' },
				numberCell: { show: true },
				title: false,
				resizable: true,
				sortable: true,
				hoverMode: 'row',
				wrap: false,
				columnBorders: true,
				rowBorders: true
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
		// 교육명 클릭 이벤트
		$(document).on('click', '.edu-name-link', function(e) {
			e.preventDefault();
			const eduIdx = $(this).data('edu-idx');
			openEduDetailModal(eduIdx);
		});

		// 수정 버튼 클릭
		$(document).on('click', '.btn-edit-edu', function(e) {
			e.preventDefault();
			const eduIdx = $(this).data('edu-idx');
			openEduEditModal(eduIdx);
		});

		// 삭제 버튼 클릭
		$(document).on('click', '.btn-delete-edu', function(e) {
			e.preventDefault();
			const eduIdx = $(this).data('edu-idx');
			confirmDeleteEdu(eduIdx);
		});
	}

	/**
	 * Flatpickr 초기화
	 */
	function initializeFlatpickr() {
		flatpickr.localize(flatpickr.l10ns.ko);

		flatpickrStartInstance = flatpickr("#eduStartDate", {
			dateFormat: "Y-m-d",
			locale: "ko",
			onChange: function(selectedDates, dateStr) {
				if (flatpickrEndInstance && dateStr) {
					flatpickrEndInstance.set('minDate', dateStr);
				}
			}
		});

		flatpickrEndInstance = flatpickr("#eduEndDate", {
			dateFormat: "Y-m-d",
			locale: "ko",
			onChange: function(selectedDates, dateStr) {
				if (flatpickrStartInstance && dateStr) {
					flatpickrStartInstance.set('maxDate', dateStr);
				}
			}
		});
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

			if (flatpickrStartInstance) {
				flatpickrStartInstance.destroy();
			}

			if (flatpickrEndInstance) {
				flatpickrEndInstance.destroy();
			}
		});
	}

	/**
	 * 교육 목록 로드
	 */
	function loadEduList() {
		if (!selectedOrgId || !selectedType) {
			return;
		}

		// 선택 정보 저장
		saveCurrentSelection();

		showGridSpinner();

		$.ajax({
			url: window.educationPageData.baseUrl + 'education/get_edu_list',
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
					$('#totalEduCount').text(response.total_count);
				} else {
					showToast(response.message || '교육 목록을 불러오는데 실패했습니다.', 'error');
				}
			},
			error: function(xhr, status, error) {
				console.error('교육 목록 로드 실패:', error);
				hideGridSpinner();
				showToast('교육 목록을 불러오는 중 오류가 발생했습니다.', 'error');
			}
		});
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
			localStorage.setItem('education_selected_category', JSON.stringify(selectionData));
		} catch (error) {
			console.error('선택 정보 저장 실패:', error);
		}
	}

	/**
	 * 그리드 업데이트
	 */
	function updateGrid(data) {
		if (!eduGrid) {
			return;
		}

		try {
			eduGrid.pqGrid("option", "dataModel.data", data);
			eduGrid.pqGrid("refreshDataAndView");
		} catch (error) {
			console.error('그리드 업데이트 실패:', error);
		}
	}

	/**
	 * 그리드 필터링
	 */
	function filterGrid(keyword) {
		if (!eduGrid) {
			return;
		}

		try {
			if (!keyword) {
				eduGrid.pqGrid("filter", {
					mode: 'AND',
					rules: []
				});
			} else {
				eduGrid.pqGrid("filter", {
					mode: 'OR',
					rules: [
						{ dataIndx: 'edu_name', condition: 'contain', value: keyword },
						{ dataIndx: 'edu_location', condition: 'contain', value: keyword },
						{ dataIndx: 'edu_leader', condition: 'contain', value: keyword }
					]
				});
			}
		} catch (error) {
			console.error('그리드 필터링 실패:', error);
		}
	}



	/**
	 * 교육 수정 모달 열기
	 */
	function openEduEditModal(eduIdx) {
		showSpinner();

		$.ajax({
			url: window.educationPageData.baseUrl + 'education/get_edu_detail',
			method: 'POST',
			data: { edu_idx: eduIdx },
			dataType: 'json',
			success: function(response) {
				hideSpinner();

				if (response.success) {
					fillEduForm(response.data);
					$('#eduModalTitle').text('교육 수정');
					$('#eduModal').modal('show');
				} else {
					showToast(response.message || '교육 정보를 불러오는데 실패했습니다.', 'error');
				}
			},
			error: function(xhr, status, error) {
				console.error('교육 정보 로드 실패:', error);
				hideSpinner();
				showToast('교육 정보를 불러오는 중 오류가 발생했습니다.', 'error');
			}
		});
	}

	/**
	 * 교육 상세 모달 열기
	 */
	function openEduDetailModal(eduIdx) {
		openEduEditModal(eduIdx);
	}


	/**
	 * 카테고리 옵션 로드
	 */
	function loadCategoryOptions(selectedCode) {
		const $select = $('#eduCategoryCode');
		$select.empty();
		$select.append('<option value="">카테고리 선택</option>');

		// 카테고리 트리에서 옵션 생성
		const tree = $("#categoryTree").fancytree("getTree");
		if (tree) {
			tree.visit(function(node) {
				if (node.data.type === 'category') {
					const level = node.getLevel() - 1; // org 레벨 제외
					const indent = '&nbsp;'.repeat(level * 4);
					$select.append(
						$('<option></option>')
							.val(node.data.category_code)
							.html(indent + node.title.split(' (')[0])
					);
				}
			});
		}

		if (selectedCode) {
			$select.val(selectedCode);
		}
	}


	/**
	 * 파일 위치: assets/js/education.js - 전역 이벤트 바인딩 함수에 추가
	 * 역할: 요일/시간대 체크박스 이벤트 바인딩 추가
	 */
	function bindGlobalEvents() {
		// 교육 등록 버튼
		$('#btnAddEdu').off('click').on('click', function() {
			openEduAddModal();
		});

		// 카테고리 관리 버튼
		$('#btnManageCategory').off('click').on('click', function() {
			openCategoryModal();
		});

		// 교육 저장 버튼
		$('#btnSaveEdu').off('click').on('click', function() {
			saveEdu();
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

		// 요일 체크박스 이벤트
		bindDayCheckboxEvents();

		// 시간대 체크박스 이벤트
		bindTimeCheckboxEvents();

		// 윈도우 리사이즈
		$(window).off('resize.education').on('resize.education', debounce(function() {
			if (eduGrid) {
				try {
					eduGrid.pqGrid("refresh");
				} catch (error) {
					console.error('윈도우 리사이즈 시 그리드 리프레시 실패:', error);
				}
			}
		}, 250));
	}

	/**
	 * 파일 위치: assets/js/education.js
	 * 역할: 요일 체크박스 이벤트 바인딩
	 */
	function bindDayCheckboxEvents() {
		// 체크박스 변경 이벤트
		$(document).off('change', '.edu-day-checkbox').on('change', '.edu-day-checkbox', function() {
			updateDaySelection();
		});

		// 드롭다운 아이템 클릭 시 닫히지 않도록 방지
		$('#eduDaysMenu .dropdown-item').off('click').on('click', function(e) {
			e.stopPropagation();
		});
	}

	/**
	 * 파일 위치: assets/js/education.js
	 * 역할: 시간대 체크박스 이벤트 바인딩
	 */
	function bindTimeCheckboxEvents() {
		// 체크박스 변경 이벤트
		$(document).off('change', '.edu-time-checkbox').on('change', '.edu-time-checkbox', function() {
			updateTimeSelection();
		});

		// 드롭다운 아이템 클릭 시 닫히지 않도록 방지
		$('#eduTimesMenu .dropdown-item').off('click').on('click', function(e) {
			e.stopPropagation();
		});
	}

	/**
	 * 파일 위치: assets/js/education.js
	 * 역할: 선택된 요일 업데이트
	 */
	function updateDaySelection() {
		const selectedDays = [];
		$('.edu-day-checkbox:checked').each(function() {
			selectedDays.push($(this).val());
		});

		// hidden input에 배열로 저장
		$('#eduDays').val(JSON.stringify(selectedDays));

		// 버튼 텍스트 업데이트
		let displayText = '요일 선택';
		if (selectedDays.length > 0) {
			if (selectedDays.length === 1) {
				displayText = selectedDays[0];
			} else {
				displayText = selectedDays[0] + ' 외 ' + (selectedDays.length - 1) + '개';
			}
		}
		$('#eduDaysText').text(displayText);
	}

	/**
	 * 파일 위치: assets/js/education.js
	 * 역할: 선택된 시간대 업데이트
	 */
	function updateTimeSelection() {
		const selectedTimes = [];
		$('.edu-time-checkbox:checked').each(function() {
			selectedTimes.push($(this).val());
		});

		// hidden input에 배열로 저장
		$('#eduTimes').val(JSON.stringify(selectedTimes));

		// 버튼 텍스트 업데이트
		let displayText = '시간대 선택';
		if (selectedTimes.length > 0) {
			if (selectedTimes.length === 1) {
				displayText = selectedTimes[0];
			} else if (selectedTimes.length <= 3) {
				displayText = selectedTimes.join(', ');
			} else {
				displayText = selectedTimes[0] + ' 외 ' + (selectedTimes.length - 1) + '개';
			}
		}
		$('#eduTimesText').text(displayText);
	}

	/**
	 * 파일 위치: assets/js/education.js - openEduAddModal() 함수 수정
	 * 역할: 교육 등록 모달 열기 (체크박스 초기화 추가)
	 */
	function openEduAddModal() {
		$('#eduModalTitle').text('교육 등록');
		$('#eduForm')[0].reset();
		$('#eduIdx').val('');
		$('#eduOrgId').val(selectedOrgId);

		// 카테고리 옵션 로드
		loadCategoryOptions();

		// 날짜 초기화
		if (flatpickrStartInstance) {
			flatpickrStartInstance.clear();
			flatpickrStartInstance.set('minDate', null);
			flatpickrStartInstance.set('maxDate', null);
		}
		if (flatpickrEndInstance) {
			flatpickrEndInstance.clear();
			flatpickrEndInstance.set('minDate', null);
			flatpickrEndInstance.set('maxDate', null);
		}

		// 요일/시간대 체크박스 초기화
		$('.edu-day-checkbox').prop('checked', false);
		$('.edu-time-checkbox').prop('checked', false);
		$('#eduDaysText').text('요일 선택');
		$('#eduTimesText').text('시간대 선택');
		$('#eduDays').val('');
		$('#eduTimes').val('');

		$('#eduModal').modal('show');
	}

	/**
	 * 파일 위치: assets/js/education.js - fillEduForm() 함수 수정
	 * 역할: 교육 폼 채우기 (체크박스 상태 설정 추가)
	 */
	function fillEduForm(eduData) {
		$('#eduIdx').val(eduData.edu_idx);
		$('#eduOrgId').val(eduData.org_id);

		// 카테고리 옵션 로드 후 선택
		loadCategoryOptions(eduData.category_code);

		$('#eduName').val(eduData.edu_name);
		$('#eduLocation').val(eduData.edu_location);

		// 날짜 설정
		if (flatpickrStartInstance && eduData.edu_start_date) {
			flatpickrStartInstance.setDate(eduData.edu_start_date);
		}
		if (flatpickrEndInstance && eduData.edu_end_date) {
			flatpickrEndInstance.setDate(eduData.edu_end_date);
		}

		// 요일 체크박스 설정
		$('.edu-day-checkbox').prop('checked', false);
		if (eduData.edu_days && Array.isArray(eduData.edu_days)) {
			eduData.edu_days.forEach(function(day) {
				$('.edu-day-checkbox[value="' + day + '"]').prop('checked', true);
			});
		}
		updateDaySelection();

		// 시간대 체크박스 설정
		$('.edu-time-checkbox').prop('checked', false);
		if (eduData.edu_times && Array.isArray(eduData.edu_times)) {
			eduData.edu_times.forEach(function(time) {
				$('.edu-time-checkbox[value="' + time + '"]').prop('checked', true);
			});
		}
		updateTimeSelection();

		$('#eduLeader').val(eduData.edu_leader);
		$('#eduLeaderAge').val(eduData.edu_leader_age);
		$('#eduLeaderGender').val(eduData.edu_leader_gender);
		$('#eduDesc').val(eduData.edu_desc);
	}

	/**
	 * 파일 위치: assets/js/education.js - saveEdu() 함수 수정
	 * 역할: 교육 저장 (요일/시간대 데이터 파싱)
	 */
	function saveEdu() {
		const eduIdx = $('#eduIdx').val();

		// 요일/시간대 hidden input에서 JSON 파싱
		let eduDays = [];
		let eduTimes = [];

		try {
			const daysJson = $('#eduDays').val();
			if (daysJson) {
				eduDays = JSON.parse(daysJson);
			}
		} catch (error) {
			console.error('요일 데이터 파싱 오류:', error);
		}

		try {
			const timesJson = $('#eduTimes').val();
			if (timesJson) {
				eduTimes = JSON.parse(timesJson);
			}
		} catch (error) {
			console.error('시간대 데이터 파싱 오류:', error);
		}

		const formData = {
			edu_idx: eduIdx,
			org_id: $('#eduOrgId').val(),
			category_code: $('#eduCategoryCode').val(),
			edu_name: $('#eduName').val(),
			edu_location: $('#eduLocation').val(),
			edu_start_date: $('#eduStartDate').val(),
			edu_end_date: $('#eduEndDate').val(),
			edu_days: eduDays,
			edu_times: eduTimes,
			edu_leader: $('#eduLeader').val(),
			edu_leader_age: $('#eduLeaderAge').val(),
			edu_leader_gender: $('#eduLeaderGender').val(),
			edu_desc: $('#eduDesc').val()
		};

		// 필수값 검증
		if (!formData.category_code) {
			showToast('교육 카테고리를 선택해주세요.', 'warning');
			return;
		}

		if (!formData.edu_name) {
			showToast('교육명을 입력해주세요.', 'warning');
			return;
		}

		showSpinner();

		const url = eduIdx
			? window.educationPageData.baseUrl + 'education/update_edu'
			: window.educationPageData.baseUrl + 'education/add_edu';

		$.ajax({
			url: url,
			method: 'POST',
			data: formData,
			dataType: 'json',
			success: function(response) {
				hideSpinner();

				if (response.success) {
					showToast(response.message, 'success');
					$('#eduModal').modal('hide');
					loadEduList();
					refreshCategoryTree();
				} else {
					showToast(response.message || '교육 저장에 실패했습니다.', 'error');
				}
			},
			error: function(xhr, status, error) {
				console.error('교육 저장 실패:', error);
				hideSpinner();
				showToast('교육 저장 중 오류가 발생했습니다.', 'error');
			}
		});
	}


	/**
	 * 교육 삭제 확인
	 */
	function confirmDeleteEdu(eduIdx) {
		showConfirmModal(
			'교육 삭제',
			'정말로 이 교육을 삭제하시겠습니까?',
			function() {
				deleteEdu(eduIdx);
			}
		);
	}

	/**
	 * 교육 삭제
	 */
	function deleteEdu(eduIdx) {
		showSpinner();

		$.ajax({
			url: window.educationPageData.baseUrl + 'education/delete_edu',
			method: 'POST',
			data: { edu_idx: eduIdx },
			dataType: 'json',
			success: function(response) {
				hideSpinner();

				if (response.success) {
					showToast(response.message, 'success');
					loadEduList();
					refreshCategoryTree();
				} else {
					showToast(response.message || '교육 삭제에 실패했습니다.', 'error');
				}
			},
			error: function(xhr, status, error) {
				console.error('교육 삭제 실패:', error);
				hideSpinner();
				showToast('교육 삭제 중 오류가 발생했습니다.', 'error');
			}
		});
	}

	/**
	 * 카테고리 관리 모달 열기
	 */
	function openCategoryModal() {
		showSpinner();

		$.ajax({
			url: window.educationPageData.baseUrl + 'education/get_category_tree',
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
					order: categories.length + 1
				};

				if (node.children && node.children.length > 0) {
					category.children = extractCategories(node.children);
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
			url: window.educationPageData.baseUrl + 'education/save_category',
			method: 'POST',
			data: {
				org_id: window.educationPageData.currentOrgId,
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
			url: window.educationPageData.baseUrl + 'education/get_category_tree',
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
		// 전역 스피너가 있다면 표시
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
