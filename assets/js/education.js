'use strict'

$(document).ready(function () {
	// 전역 변수 영역
	let eduGrid;                      // ParamQuery Grid 인스턴스
	let applicantGrid;                // 신청자 ParamQuery Grid 인스턴스
	let selectedOrgId = null;         // 선택된 조직 ID
	let selectedCategoryCode = null;  // 선택된 카테고리 코드
	let selectedType = null;          // 선택된 타입 ('org', 'category')
	let splitInstance = null;         // Split.js 인스턴스
	let categoryData = [];            // 카테고리 데이터
	let categoryMap = {};             // 카테고리 코드-이름 매핑
	let flatpickrStartInstance = null; // 시작일 Flatpickr 인스턴스
	let flatpickrEndInstance = null;   // 종료일 Flatpickr 인스턴스
	let currentApplicantEduIdx = null;
	let applicantMemberSelect = null;
	let currentExternalEduIdx = null;

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
			bindApplicantEvents();
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

				// 카테고리 맵 생성
				buildCategoryMap(treeData);

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
	 * 카테고리 맵 구축 (코드 -> 이름 매핑)
	 */
	function buildCategoryMap(nodes) {
		nodes.forEach(function (node) {
			if (node.data && node.data.type === 'category') {
				// 괄호 안의 개수 정보 제거하고 순수 카테고리명만 저장
				const categoryName = node.title.split(' (')[0];
				categoryMap[node.data.category_code] = categoryName;
			}
			if (node.children && node.children.length > 0) {
				buildCategoryMap(node.children);
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
					// 조직 선택 시 카테고리 관리 버튼 비활성화
					$('#btnRenameCategory, #btnDeleteCategory, #btnMoveCategory').prop('disabled', true);
				} else if (nodeData.type === 'category') {
					selectedOrgId = window.educationPageData.currentOrgId;
					selectedCategoryCode = nodeData.category_code;
					selectedType = 'category';
					$('#selectedCategoryName').html('<i class="bi bi-folder"></i> ' + node.title);
					// 카테고리 선택 시 관련 버튼 활성화
					$('#btnRenameCategory, #btnDeleteCategory, #btnMoveCategory').prop('disabled', false);
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
	 * 체크박스 클릭 핸들러
	 */
	function handleCheckboxClick(event, eduIdx) {
		var isDirectCheckboxClick = $(event.target).hasClass('edu-checkbox') ||
			$(event.originalEvent?.target).hasClass('edu-checkbox');

		if (!isDirectCheckboxClick) {
			var checkbox = $('.edu-checkbox[data-edu-idx="' + eduIdx + '"]').first();
			if (checkbox.length > 0) {
				var isCurrentlyChecked = checkbox.is(':checked');
				checkbox.prop('checked', !isCurrentlyChecked);
			}
		}

		updateSelectAllCheckbox();
	}

	/**
	 * 전체 선택 체크박스 상태 업데이트
	 */
	function updateSelectAllCheckbox() {
		var totalCheckboxes = $('.edu-checkbox').length;
		var checkedCheckboxes = $('.edu-checkbox:checked').length;

		if (totalCheckboxes === 0) {
			$('#selectAllCheckbox').prop('checked', false);
			$('#selectAllCheckbox').prop('indeterminate', false);
		} else if (checkedCheckboxes === 0) {
			$('#selectAllCheckbox').prop('checked', false);
			$('#selectAllCheckbox').prop('indeterminate', false);
		} else if (checkedCheckboxes === totalCheckboxes) {
			$('#selectAllCheckbox').prop('checked', true);
			$('#selectAllCheckbox').prop('indeterminate', false);
		} else {
			$('#selectAllCheckbox').prop('checked', false);
			$('#selectAllCheckbox').prop('indeterminate', true);
		}
	}

	/**
	 * Flatpickr 초기화
	 */
	function initializeFlatpickr() {
		flatpickr.localize(flatpickr.l10ns.ko);

		flatpickrStartInstance = flatpickr("#eduStartDate", {
			dateFormat: "Y-m-d",
			locale: "ko",
			onChange: function (selectedDates, dateStr) {
				if (flatpickrEndInstance && dateStr) {
					flatpickrEndInstance.set('minDate', dateStr);
				}
			}
		});

		flatpickrEndInstance = flatpickr("#eduEndDate", {
			dateFormat: "Y-m-d",
			locale: "ko",
			onChange: function (selectedDates, dateStr) {
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
		$(window).on('beforeunload', function () {
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
	 * 양육 목록 로드
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
			success: function (response) {
				hideGridSpinner();

				if (response.success) {
					// 카테고리명 추가
					const dataWithCategoryName = response.data.map(function (item) {
						item.category_name = categoryMap[item.category_code] || item.category_code;
						return item;
					});

					updateGrid(dataWithCategoryName);
					$('#totalEduCount').text(response.total_count);
				} else {
					showToast(response.message || '양육 목록을 불러오는데 실패했습니다.', 'error');
				}
			},
			error: function (xhr, status, error) {
				console.error('양육 목록 로드 실패:', error);
				hideGridSpinner();
				showToast('양육 목록을 불러오는 중 오류가 발생했습니다.', 'error');
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
	 * 양육 등록 Offcanvas 열기
	 */
	function openEduAddOffcanvas() {
		resetEduForm();
		loadCategoryOptions();
		$('#eduOffcanvasTitle').text('양육 등록');
		$('#eduIdx').val('');
		$('#eduOrgId').val(selectedOrgId || window.educationPageData.currentOrgId);

		const offcanvas = new bootstrap.Offcanvas('#eduOffcanvas');
		offcanvas.show();
	}

	/**
	 * 양육 수정 Offcanvas 열기
	 */
	function openEduEditOffcanvas(eduIdx) {
		showSpinner();

		$.ajax({
			url: window.educationPageData.baseUrl + 'education/get_edu_detail',
			method: 'POST',
			data: { edu_idx: eduIdx },
			dataType: 'json',
			success: function (response) {
				hideSpinner();

				if (response.success) {
					fillEduForm(response.data);
					$('#eduOffcanvasTitle').text('양육 수정');

					const offcanvas = new bootstrap.Offcanvas('#eduOffcanvas');
					offcanvas.show();
				} else {
					showToast(response.message || '양육 정보를 불러오는데 실패했습니다.', 'error');
				}
			},
			error: function (xhr, status, error) {
				console.error('양육 정보 로드 실패:', error);
				hideSpinner();
				showToast('양육 정보를 불러오는 중 오류가 발생했습니다.', 'error');
			}
		});
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
			tree.visit(function (node) {
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
	 * 요일 체크박스 이벤트 바인딩
	 */
	function bindDayCheckboxEvents() {
		$('.edu-day-checkbox').off('change').on('change', function () {
			updateDaysDisplay();
		});
	}

	/**
	 * 시간대 체크박스 이벤트 바인딩
	 */
	function bindTimeCheckboxEvents() {
		$('.edu-time-checkbox').off('change').on('change', function () {
			updateTimesDisplay();
		});
	}

	/**
	 * 요일 선택 표시 업데이트
	 */
	function updateDaysDisplay() {
		const selectedDays = [];
		$('.edu-day-checkbox:checked').each(function () {
			selectedDays.push($(this).val());
		});

		if (selectedDays.length > 0) {
			$('#eduDaysText').text(selectedDays.join(', '));
			// JSON.stringify 제거 - 배열을 직접 저장
			$('#eduDays').val(selectedDays.join(','));
		} else {
			$('#eduDaysText').text('요일 선택');
			$('#eduDays').val('');
		}
	}

	/**
	 * 시간대 선택 표시 업데이트
	 */
	function updateTimesDisplay() {
		const selectedTimes = [];
		$('.edu-time-checkbox:checked').each(function () {
			selectedTimes.push($(this).val());
		});

		if (selectedTimes.length > 0) {
			$('#eduTimesText').text(selectedTimes.join(', '));
			// JSON.stringify 제거 - 배열을 직접 저장
			$('#eduTimes').val(selectedTimes.join(','));
		} else {
			$('#eduTimesText').text('시간대 선택');
			$('#eduTimes').val('');
		}
	}



	/**
	 * ParamQuery Grid 초기화 - 양육명 클릭 시에만 offcanvas, 신청자 컬럼 추가
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
						title: '<input type="checkbox" id="selectAllCheckbox" />',
						dataIndx: "checkbox",
						width: 40,
						align: "center",
						resizable: false,
						sortable: false,
						editable: false,
						menuIcon: false,
						render: function (ui) {
							var checkboxId = 'edu-checkbox-' + ui.rowData.edu_idx;
							return '<input type="checkbox" class="edu-checkbox" id="' + checkboxId + '" data-edu-idx="' + ui.rowData.edu_idx + '" />';
						}
					},
					{
						title: "양육카테고리",
						dataIndx: "category_name",
						width: 120,
						editable: false,
						align: "center"
					},
					{
						title: "양육명",
						dataIndx: "edu_name",
						editable: false,
						width: 200,
						cls: 'pq-edu-name-cell',
						render: function (ui) {
							return '<span class="text-primary" style="cursor:pointer;">' + escapeHtml(ui.cellData || '') + '</span>';
						}
					},
					{
						title: "신청자",
						dataIndx: "applicant_count_str",
						width: 100,
						align: "center",
						editable: false,
						render: function (ui) {
							var applicantCount = ui.rowData.applicant_count || 0;
							var capacity = ui.rowData.edu_capacity || 0;
							var text = '';
							if (capacity > 0) {
								text = applicantCount + '명 | ' + capacity + '명';
							} else {
								text = applicantCount + '명';
							}
							return '<span class="text-success" style="cursor:pointer;">' + text + '</span>';
						}
					},
					{
						title: "양육지역",
						dataIndx: "edu_location",
						editable: false,
						width: 150
					},
					{
						title: "양육기간",
						dataIndx: "edu_period_str",
						width: 300,
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
						title: "수강료",
						dataIndx: "edu_fee",
						width: 100,
						align: "center",
						render: function (ui) {
							var fee = parseInt(ui.cellData) || 0;
							return fee === 0 ? '무료' : formatNumber(fee) + '원';
						}
					},
					{
						title: "인도자",
						dataIndx: "edu_leader",
						width: 100,
						align: "center"
					},
					{
						title: "인도자연락처",
						dataIndx: "edu_leader_phone",
						width: 140,
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
						title: "외부공개",
						dataIndx: "public_yn",
						width: 80,
						align: "center",
						render: function (ui) {
							return ui.cellData === 'Y' ? '공개' : '비공개';
						}
					},
					{
						title: "ZOOM",
						dataIndx: "zoom_url",
						width: 100,
						align: "center",
						render: function (ui) {
							return ui.cellData ? '<i class="bi bi-camera-video text-primary"></i>' : '-';
						}
					}
				],
				strNoRows: '양육 정보가 없습니다',
				selectionModel: { type: 'row', mode: 'block' },
				numberCell: { show: false },
				title: false,
				resizable: true,
				sortable: true,
				hoverMode: 'row',
				wrap: false,
				columnBorders: true,
				rowBorders: true,
				rowInit: function (ui) {
					var style = "height: 40px;";
					return {
						style: style,
					};
				},
				cellClick: function (event, ui) {
					// 체크박스 컬럼 클릭 시
					if (ui.dataIndx === 'checkbox') {
						handleCheckboxClick(event, ui.rowData.edu_idx);
					}
					// 양육명 컬럼 클릭 시에만 수정 offcanvas 열기
					else if (ui.dataIndx === 'edu_name') {
						openEduEditOffcanvas(ui.rowData.edu_idx);
					}
					// 신청자 컬럼 클릭 시 신청자 관리 offcanvas 열기
					else if (ui.dataIndx === 'applicant_count_str') {
						currentApplicantEduIdx = ui.rowData.edu_idx;
						openApplicantOffcanvas(ui.rowData.edu_idx);
					}
					// 다른 컬럼 클릭 시에는 아무 동작 안 함
				}
			});

			hideGridSpinner();
		} catch (error) {
			console.error('ParamQuery Grid 초기화 실패:', error);
			hideGridSpinner();
			showToast('그리드 초기화에 실패했습니다.', 'error');
		}
	}

	/**
	 * 전역 이벤트 바인딩 - 수강료 포맷팅 이벤트 추가
	 */
	function bindGlobalEvents() {
		// 양육 등록 버튼
		$('#btnAddEdu').off('click').on('click', function () {
			openEduAddOffcanvas();
		});

		// 선택 삭제 버튼
		$('#btnDeleteSelected').off('click').on('click', function () {
			deleteSelectedEdu();
		});

		// 양육 저장 버튼
		$('#btnSaveEdu').off('click').on('click', function () {
			saveEdu();
		});

		// 검색 버튼
		$('#btnSearch').off('click').on('click', function () {
			const keyword = $('#searchKeyword').val().trim();
			filterGrid(keyword);
		});

		// 검색 엔터키
		$('#searchKeyword').off('keypress').on('keypress', function (e) {
			if (e.which === 13) {
				e.preventDefault();
				const keyword = $(this).val().trim();
				filterGrid(keyword);
			}
		});

		// 수강료 입력 포맷팅
		$('#eduFee').off('input').on('input', function () {
			var val = $(this).val().replace(/[^\d]/g, '');
			$(this).val(formatNumber(val));
		});

		// 외부 URL 버튼
		$('#btnExternalUrl').off('click').on('click', function () {
			openExternalUrlModal();
		});

		// 외부 URL 복사 버튼
		$('#btnCopyExternalUrl').off('click').on('click', function () {
			copyExternalUrl();
		});

		// 외부 URL 갱신 버튼
		$('#btnRefreshExternalUrl').off('click').on('click', function () {
			generateExternalUrl();
		});

		// 포스터 이미지 선택
		$('#eduPosterImg').off('change').on('change', function (e) {
			handlePosterImageSelect(e);
		});

		// 포스터 이미지 삭제
		$('#btnRemovePoster').off('click').on('click', function () {
			removePosterImage();
		});

		// 요일 체크박스 이벤트
		bindDayCheckboxEvents();

		// 시간대 체크박스 이벤트
		bindTimeCheckboxEvents();

		// 전체 선택 체크박스
		$(document).on('change', '#selectAllCheckbox', function () {
			var isChecked = $(this).prop('checked');
			$('.edu-checkbox').prop('checked', isChecked);
		});

		// 개별 체크박스
		$(document).on('change', '.edu-checkbox', function () {
			updateSelectAllCheckbox();
		});

		// 윈도우 리사이즈
		$(window).off('resize.education').on('resize.education', debounce(function () {
			if (eduGrid) {
				try {
					eduGrid.pqGrid("refresh");
				} catch (error) {
					console.error('그리드 리사이즈 실패:', error);
				}
			}
		}, 250));

		// 카테고리 관리 버튼 이벤트
		bindCategoryManagementEvents();
	}

	/**
	 * 포스터 이미지 선택 처리
	 */
	function handlePosterImageSelect(e) {
		const file = e.target.files[0];
		if (!file) {
			return;
		}

		// 이미지 파일 검증
		if (!file.type.match('image.*')) {
			showToast('이미지 파일만 업로드 가능합니다.', 'warning');
			$('#eduPosterImg').val('');
			return;
		}

		// 파일 크기 검증 (10MB)
		if (file.size > 10 * 1024 * 1024) {
			showToast('파일 크기는 10MB 이하만 가능합니다.', 'warning');
			$('#eduPosterImg').val('');
			return;
		}

		// 미리보기 표시
		const reader = new FileReader();
		reader.onload = function (e) {
			$('#posterPreview').attr('src', e.target.result).show();
			$('#posterPlaceholder').hide();
			$('#btnRemovePoster').show();
		};
		reader.readAsDataURL(file);
	}

	/**
	 * 포스터 이미지 제거
	 */
	function removePosterImage() {
		$('#eduPosterImg').val('');
		$('#posterPreview').attr('src', '').hide();
		$('#posterPlaceholder').show();
		$('#btnRemovePoster').hide();
		$('#removePosterFlag').val('1');
	}

	/**
	 * 파일 위치: assets/js/education.js
	 * 역할: 양육 폼 데이터 채우기 - 정원, 계좌정보 추가
	 */
	function fillEduForm(data) {
		$('#eduIdx').val(data.edu_idx);
		$('#eduOrgId').val(data.org_id);
		$('#eduCategoryCode').val(data.category_code);
		$('#eduName').val(data.edu_name);
		$('#eduLocation').val(data.edu_location || '');
		$('#eduStartDate').val(data.edu_start_date || '');
		$('#eduEndDate').val(data.edu_end_date || '');
		$('#eduLeader').val(data.edu_leader || '');
		$('#eduLeaderPhone').val(data.edu_leader_phone || '');
		$('#eduLeaderAge').val(data.edu_leader_age || '');
		$('#eduLeaderGender').val(data.edu_leader_gender || '');
		$('#eduDesc').val(data.edu_desc || '');
		$('#eduPublicYn').val(data.public_yn || 'N');
		$('#eduZoomUrl').val(data.zoom_url || '');
		$('#eduYoutubeUrl').val(data.youtube_url || '');

		// 수강료
		var feeValue = parseInt(data.edu_fee) || 0;
		$('#eduFee').val(feeValue > 0 ? formatNumber(feeValue) : '');

		// 정원
		$('#eduCapacity').val(data.edu_capacity || 0);

		// 계좌정보
		if (data.bank_account) {
			try {
				var bankInfo = JSON.parse(data.bank_account);
				$('#eduBankName').val(bankInfo.bank_name || '');
				$('#eduAccountNumber').val(bankInfo.account_number || '');
			} catch (e) {
				$('#eduBankName').val('');
				$('#eduAccountNumber').val('');
			}
		} else {
			$('#eduBankName').val('');
			$('#eduAccountNumber').val('');
		}

		// 요일 체크박스 설정
		$('.edu-day-checkbox').prop('checked', false);
		if (data.edu_days && Array.isArray(data.edu_days)) {
			data.edu_days.forEach(function (day) {
				$('.edu-day-checkbox[value="' + day + '"]').prop('checked', true);
			});
		}
		updateDaysDisplay();

		// 시간대 체크박스 설정
		$('.edu-time-checkbox').prop('checked', false);
		if (data.edu_times && Array.isArray(data.edu_times)) {
			data.edu_times.forEach(function (time) {
				$('.edu-time-checkbox[value="' + time + '"]').prop('checked', true);
			});
		}
		updateTimesDisplay();

		// 포스터 이미지 미리보기
		$('#posterPreview').hide();
		$('#posterPlaceholder').show();
		$('#btnRemovePoster').hide();
		$('#removePosterFlag').val('0');

		if (data.poster_img) {
			// 이미지 경로에 base url 추가
			var imgSrc = data.poster_img;
			if (imgSrc.indexOf('http') !== 0 && window.educationPageData && window.educationPageData.baseUrl) {
				imgSrc = window.educationPageData.baseUrl + imgSrc;
			}

			$('#posterPreview').attr('src', imgSrc).show();
			$('#posterPlaceholder').hide();
			$('#btnRemovePoster').show();
		} else {
			$('#posterPreview').attr('src', '');
		}

		loadCategoryOptions(data.category_code);
	}

	/**
	 * 양육 폼 초기화 - 수강료, 포스터 추가
	 */
	function resetEduForm() {
		$('#eduForm')[0].reset();
		$('#eduIdx').val('');
		$('.edu-day-checkbox').prop('checked', false);
		$('.edu-time-checkbox').prop('checked', false);
		$('#eduDaysText').text('요일 선택');
		$('#eduTimesText').text('시간대 선택');

		// 새로 추가된 필드 초기화
		$('#eduPublicYn').val('N');
		$('#eduOnlineYn').val('N');
		$('#eduYoutubeUrl').val('');
		$('#eduLeaderPhone').val('');
		$('#eduFee').val('0');

		// 포스터 이미지 초기화
		$('#eduPosterImg').val('');
		$('#posterPreview').attr('src', '').hide();
		$('#posterPlaceholder').show();
		$('#btnRemovePoster').hide();
		$('#removePosterFlag').val('0');

		if (flatpickrStartInstance) {
			flatpickrStartInstance.clear();
		}
		if (flatpickrEndInstance) {
			flatpickrEndInstance.clear();
		}
	}

	/**
	 * 파일 위치: assets/js/education.js
	 * 역할: 양육 저장 - 정원, 계좌정보 추가
	 */
	function saveEdu() {
		if (!$('#eduCategoryCode').val()) {
			showToast('카테고리를 선택해주세요.', 'warning');
			return;
		}

		if (!$('#eduName').val().trim()) {
			showToast('양육명을 입력해주세요.', 'warning');
			return;
		}

		// 파일 처리 후 실행할 콜백 함수
		var processFormData = function (posterBlob) {
			const eduIdx = $('#eduIdx').val();
			const url = eduIdx ?
				window.educationPageData.baseUrl + 'education/update_edu' :
				window.educationPageData.baseUrl + 'education/insert_edu';

			// FormData 생성
			var formData = new FormData();

			// 기본 필드
			if (eduIdx) formData.append('edu_idx', eduIdx);
			formData.append('org_id', $('#eduOrgId').val());
			formData.append('category_code', $('#eduCategoryCode').val());
			formData.append('edu_name', $('#eduName').val());
			formData.append('edu_location', $('#eduLocation').val());
			formData.append('edu_start_date', $('#eduStartDate').val());
			formData.append('edu_end_date', $('#eduEndDate').val());
			formData.append('edu_days', $('#eduDays').val());
			formData.append('edu_times', $('#eduTimes').val());
			formData.append('edu_leader', $('#eduLeader').val());
			formData.append('edu_leader_phone', $('#eduLeaderPhone').val());
			formData.append('edu_leader_age', $('#eduLeaderAge').val());
			formData.append('edu_leader_gender', $('#eduLeaderGender').val());
			formData.append('edu_desc', $('#eduDesc').val());
			formData.append('public_yn', $('#eduPublicYn').val());
			// formData.append('online_yn', $('#eduOnlineYn').val());
			formData.append('youtube_url', $('#eduYoutubeUrl').val());
			formData.append('zoom_url', $('#eduZoomUrl').val().trim());

			// 수강료 (숫자만 추출)
			var feeValue = $('#eduFee').val().replace(/[^\d]/g, '');
			formData.append('edu_fee', feeValue || '0');

			// 정원
			formData.append('edu_capacity', $('#eduCapacity').val() || '0');

			// 계좌정보
			var bankName = $('#eduBankName').val().trim();
			var accountNumber = $('#eduAccountNumber').val().trim();
			if (bankName || accountNumber) {
				var bankAccount = JSON.stringify({
					bank_name: bankName,
					account_number: accountNumber
				});
				formData.append('bank_account', bankAccount);
			} else {
				formData.append('bank_account', '');
			}

			// 포스터 이미지
			if (posterBlob) {
				// Blob을 파일로 변환 (파일명 지정 필요)
				var fileName = 'poster.jpg';
				// 원본 파일명이 있다면 확장자 유지
				var originalFile = $('#eduPosterImg')[0].files[0];
				if (originalFile && originalFile.name) {
					var ext = originalFile.name.split('.').pop();
					fileName = 'poster.' + ext;
				}
				formData.append('poster_img', posterBlob, fileName);
			}
			formData.append('remove_poster', $('#removePosterFlag').val());

			showSpinner();

			$.ajax({
				url: url,
				method: 'POST',
				data: formData,
				processData: false,
				contentType: false,
				dataType: 'json',
				success: function (response) {
					hideSpinner();

					if (response.success) {
						showToast(response.message, 'success');

						// Offcanvas 닫기
						const offcanvasElement = document.getElementById('eduOffcanvas');
						const offcanvas = bootstrap.Offcanvas.getInstance(offcanvasElement);
						if (offcanvas) {
							offcanvas.hide();
						}

						loadEduList();
						refreshCategoryTree();
					} else {
						showToast(response.message || '양육 저장에 실패했습니다.', 'error');
					}
				},
				error: function (xhr, status, error) {
					console.error('양육 저장 실패:', error);
					hideSpinner();
					showToast('양육 저장 중 오류가 발생했습니다.', 'error');
				}
			});
		};

		// 이미지 리사이징 처리
		var posterFile = $('#eduPosterImg')[0].files[0];
		if (posterFile) {
			resizeImage(posterFile, 1200, function (resizedBlob) {
				processFormData(resizedBlob);
			});
		} else {
			processFormData(null);
		}
	}

	/**
	 * 이미지 리사이징 함수
	 * @param {File} file 원본 파일
	 * @param {number} maxWidth 최대 너비
	 * @param {function} callback 콜백 함수 (Blob 반환)
	 */
	function resizeImage(file, maxWidth, callback) {
		var reader = new FileReader();
		reader.readAsDataURL(file);
		reader.onload = function (event) {
			var img = new Image();
			img.src = event.target.result;
			img.onload = function () {
				var width = img.width;
				var height = img.height;

				if (width > maxWidth) {
					height = Math.round(height * (maxWidth / width));
					width = maxWidth;
				}

				var canvas = document.createElement('canvas');
				canvas.width = width;
				canvas.height = height;
				var ctx = canvas.getContext('2d');
				ctx.drawImage(img, 0, 0, width, height);

				canvas.toBlob(function (blob) {
					callback(blob);
				}, file.type, 0.9); // 품질 0.9
			};
		};
	}

	/**
	 * 숫자 포맷팅 함수 (천단위 콤마)
	 */
	function formatNumber(num) {
		if (!num) return '0';
		return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
	}


	/**
	 * 선택된 양육 삭제
	 */
	function deleteSelectedEdu() {
		// 선택된 체크박스에서 양육 인덱스 수집
		var eduIndexes = [];
		$('.edu-checkbox:checked').each(function () {
			var eduIdx = $(this).data('edu-idx');
			if (eduIdx) {
				eduIndexes.push(eduIdx);
			}
		});

		if (eduIndexes.length === 0) {
			showToast('삭제할 양육을 선택해주세요.', 'warning');
			return;
		}

		// 삭제 확인 모달
		showConfirmModal(
			'양육 삭제',
			'선택한 ' + eduIndexes.length + '개의 양육을 삭제하시겠습니까?',
			function () {
				deleteMultipleEdu(eduIndexes);
			}
		);
	}

	/**
	 * 여러 양육 삭제
	 */
	function deleteMultipleEdu(eduIndexes) {
		showSpinner();

		$.ajax({
			url: window.educationPageData.baseUrl + 'education/delete_multiple_edu',
			method: 'POST',
			data: { edu_indexes: eduIndexes },
			dataType: 'json',
			success: function (response) {
				hideSpinner();

				if (response.success) {
					showToast(response.message, 'success');
					loadEduList();
					refreshCategoryTree();
				} else {
					showToast(response.message || '양육 삭제에 실패했습니다.', 'error');
				}
			},
			error: function (xhr, status, error) {
				console.error('양육 삭제 실패:', error);
				hideSpinner();
				showToast('양육 삭제 중 오류가 발생했습니다.', 'error');
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
			success: function (treeData) {
				hideTreeSpinner();

				if (!treeData || treeData.length === 0) {
					return;
				}

				// 카테고리 맵 재구성
				categoryMap = {};
				buildCategoryMap(treeData);

				// 트리 다시 로드
				const tree = $("#categoryTree").fancytree("getTree");
				if (tree) {
					tree.reload(treeData);

					// 이전 선택 복원
					restorePreviousSelection();
				}
			},
			error: function (xhr, status, error) {
				console.error('트리 새로고침 실패:', error);
				hideTreeSpinner();
			}
		});
	}

	/**
	 * 카테고리 관리 이벤트 바인딩
	 */
	function bindCategoryManagementEvents() {
		// 카테고리 생성
		$('#btnAddCategory').on('click', function () {
			const tree = $("#categoryTree").fancytree("getTree");
			const node = tree.getActiveNode();
			let parentCode = null;

			if (node && node.data.type === 'category') {
				parentCode = node.data.category_code;
			}

			showPromptModal('새 카테고리 생성', '새 카테고리 이름을 입력하세요.', function (newCategoryName) {
				if (!newCategoryName || !newCategoryName.trim()) {
					showToast('카테고리 이름은 비워둘 수 없습니다.', 'warning');
					return;
				}

				$.ajax({
					url: window.educationPageData.baseUrl + 'mng/mng_education/create_category',
					method: 'POST',
					data: {
						parent_code: parentCode,
						category_name: newCategoryName.trim(),
						org_id: window.educationPageData.currentOrgId
					},
					dataType: 'json',
					success: function (response) {
						if (response.success) {
							showToast('새 카테고리가 생성되었습니다.', 'success');
							refreshCategoryTree();
						} else {
							showToast(response.message || '카테고리 생성에 실패했습니다.', 'error');
						}
					},
					error: function () {
						showToast('카테고리 생성 중 오류가 발생했습니다.', 'error');
					}
				});
			});
		});

		// 카테고리명 변경
		$('#btnRenameCategory').on('click', function () {
			const tree = $("#categoryTree").fancytree("getTree");
			const node = tree.getActiveNode();

			if (!node || node.data.type !== 'category') {
				showToast('변경할 카테고리를 선택해주세요.', 'warning');
				return;
			}

			const currentName = node.title.split(' (')[0];
			$('#newCategoryName').val(currentName);
			$('#renameCategoryModal').modal('show');
		});

		$('#confirmRenameCategoryBtn').on('click', function () {
			const tree = $("#categoryTree").fancytree("getTree");
			const node = tree.getActiveNode();
			const newName = $('#newCategoryName').val().trim();

			if (!newName) {
				showToast('카테고리명을 입력해주세요.', 'warning');
				return;
			}

			$.ajax({
				url: window.educationPageData.baseUrl + 'mng/mng_education/rename_category',
				method: 'POST',
				data: {
					category_code: node.data.category_code,
					new_name: newName,
					org_id: window.educationPageData.currentOrgId
				},
				dataType: 'json',
				success: function (response) {
					if (response.success) {
						showToast('카테고리명이 변경되었습니다.', 'success');
						$('#renameCategoryModal').modal('hide');
						refreshCategoryTree();
						loadEduList(); // 그리드도 새로고침
					} else {
						showToast(response.message || '카테고리명 변경에 실패했습니다.', 'error');
					}
				},
				error: function () {
					showToast('카테고리명 변경 중 오류가 발생했습니다.', 'error');
				}
			});
		});

		// 카테고리 삭제
		$('#btnDeleteCategory').on('click', function () {
			const tree = $("#categoryTree").fancytree("getTree");
			const node = tree.getActiveNode();

			if (!node || node.data.type !== 'category') {
				showToast('삭제할 카테고리를 선택해주세요.', 'warning');
				return;
			}

			const message = `'${node.title.split(' (')[0]}' 카테고리를 삭제하시겠습니까? 하위 카테고리와 포함된 모든 양육 정보가 함께 삭제되며, 복구할 수 없습니다.`;
			$('#deleteCategoryMessage').text(message);
			$('#deleteCategoryModal').modal('show');
		});

		$('#confirmDeleteCategoryBtn').on('click', function () {
			const tree = $("#categoryTree").fancytree("getTree");
			const node = tree.getActiveNode();

			$.ajax({
				url: window.educationPageData.baseUrl + 'mng/mng_education/delete_category',
				method: 'POST',
				data: {
					category_code: node.data.category_code,
					org_id: window.educationPageData.currentOrgId
				},
				dataType: 'json',
				success: function (response) {
					if (response.success) {
						showToast('카테고리가 삭제되었습니다.', 'success');
						$('#deleteCategoryModal').modal('hide');
						refreshCategoryTree();
						selectFirstNode(); // 첫번째 노드 선택
					} else {
						showToast(response.message || '카테고리 삭제에 실패했습니다.', 'error');
					}
				},
				error: function () {
					showToast('카테고리 삭제 중 오류가 발생했습니다.', 'error');
				}
			});
		});


		// 카테고리 이동
		$('#btnMoveCategory').on('click', function () {
			const tree = $("#categoryTree").fancytree("getTree");
			const node = tree.getActiveNode();

			if (!node || node.data.type !== 'category') {
				showToast('이동할 카테고리를 선택해주세요.', 'warning');
				return;
			}

			const $select = $('#moveToCategoryCode');
			$select.empty().append('<option value="">최상위로 이동</option>');

			tree.visit(function (n) {
				// 자기 자신과 자기 자식 노드는 제외
				if (n.data.type === 'category' && n.key !== node.key && !node.isAncestorOf(n)) {
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
		});

		$('#confirmMoveCategoryBtn').on('click', function () {
			const tree = $("#categoryTree").fancytree("getTree");
			const node = tree.getActiveNode();
			const targetParentCode = $('#moveToCategoryCode').val();

			$.ajax({
				url: window.educationPageData.baseUrl + 'mng/mng_education/move_category',
				method: 'POST',
				data: {
					source_code: node.data.category_code,
					target_parent_code: targetParentCode,
					org_id: window.educationPageData.currentOrgId
				},
				dataType: 'json',
				success: function (response) {
					if (response.success) {
						showToast('카테고리가 이동되었습니다.', 'success');
						$('#moveCategoryModal').modal('hide');
						refreshCategoryTree();
					} else {
						showToast(response.message || '카테고리 이동에 실패했습니다.', 'error');
					}
				},
				error: function () {
					showToast('카테고리 이동 중 오류가 발생했습니다.', 'error');
				}
			});
		});
	}


	/**
	 * 스피너 표시
	 */
	function showSpinner() {
		if (typeof window.showSpinner === 'function') {
			window.showSpinner();
		}
	}

	/**
	 * 스피너 숨김
	 */
	function hideSpinner() {
		if (typeof window.hideSpinner === 'function') {
			window.hideSpinner();
		}
	}

	/**
	 * 모든 스피너 표시
	 */
	function showAllSpinners() {
		showTreeSpinner();
		showGridSpinner();
	}

	/**
	 * 모든 스피너 숨김
	 */
	function hideAllSpinners() {
		hideTreeSpinner();
		hideGridSpinner();
	}

	/**
	 * 트리 스피너 표시
	 */
	function showTreeSpinner() {
		$('#treeSpinner').removeClass('d-none').addClass('d-flex');
	}

	/**
	 * 트리 스피너 숨김
	 */
	function hideTreeSpinner() {
		$('#treeSpinner').removeClass('d-flex').addClass('d-none');
	}

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
	 * 디바운스 유틸리티
	 */
	function debounce(func, wait) {
		let timeout;
		return function executedFunction(...args) {
			const later = () => {
				clearTimeout(timeout);
				func(...args);
			};
			clearTimeout(timeout);
			timeout = setTimeout(later, wait);
		};
	}


	/**
	 * 신청자 관리 버튼 클릭 이벤트 바인딩
	 */
	function bindApplicantEvents() {
		// 신청자 관리 버튼 클릭
		$('#btnManageApplicant').on('click', function () {
			// 체크된 양육 확인
			var checkedEdu = [];
			$('.edu-checkbox:checked').each(function () {
				checkedEdu.push({
					edu_idx: $(this).data('edu-idx'),
					edu_name: $(this).closest('tr').find('td[data-dataindx="edu_name"]').text() || ''
				});
			});

			if (checkedEdu.length === 0) {
				showToast('신청자를 관리할 양육을 선택해주세요.', 'warning');
				return;
			}

			if (checkedEdu.length > 1) {
				showToast('신청자 관리는 하나의 양육만 선택해주세요.', 'warning');
				return;
			}

			currentApplicantEduIdx = checkedEdu[0].edu_idx;
			openApplicantOffcanvas(currentApplicantEduIdx);
		});

		// 신청자 추가 버튼
		$('#btnAddApplicant').on('click', function () {
			openAddApplicantModal();
		});

		// 신청자 저장 버튼
		$('#btnSaveApplicant').on('click', function () {
			saveApplicant();
		});

		// 신청자 수정 저장 버튼
		$('#btnUpdateApplicant').on('click', function () {
			updateApplicant();
		});

		// 상태 일괄변경 버튼
		$('#btnChangeStatusBulk').on('click', function () {
			var selectedApplicants = getSelectedApplicants();
			if (selectedApplicants.length === 0) {
				showToast('상태를 변경할 신청자를 선택해주세요.', 'warning');
				return;
			}
			$('#bulkStatusModal').modal('show');
		});

		// 일괄 상태변경 적용 버튼
		$('#btnApplyBulkStatus').on('click', function () {
			applyBulkStatus();
		});

		// 삭제 확인 버튼
		$('#btnConfirmDeleteApplicant').on('click', function () {
			confirmDeleteApplicant();
		});

		// 선택 삭제 버튼
		$('#btnDeleteSelectedApplicant').on('click', function () {
			var selectedApplicants = getSelectedApplicants();
			if (selectedApplicants.length === 0) {
				showToast('삭제할 신청자를 선택해주세요.', 'warning');
				return;
			}
			openDeleteSelectedApplicantsModal(selectedApplicants);
		});

		// Select2 초기화
		initApplicantSelect2();
	}

	/**
	 * Select2 초기화 (신청자 선택)
	 */
	function initApplicantSelect2() {
		if (applicantMemberSelect) {
			applicantMemberSelect.select2('destroy');
		}

		applicantMemberSelect = $('#applicantMemberSelect').select2({
			dropdownParent: $('#addApplicantModal'),
			placeholder: '회원을 선택하거나 이름을 입력하세요',
			allowClear: true,
			multiple: true,
			tags: true,
			ajax: {
				url: window.educationPageData.baseUrl + 'education/search_members',
				dataType: 'json',
				delay: 250,
				data: function (params) {
					return {
						keyword: params.term,
						org_id: selectedOrgId || window.educationPageData.currentOrgId
					};
				},
				processResults: function (data) {
					return {
						results: data.data.map(function (member) {
							return {
								id: member.member_idx,
								text: member.member_name + (member.member_phone ? ' (' + member.member_phone + ')' : ''),
								name: member.member_name,
								phone: member.member_phone
							};
						})
					};
				}
			},
			createTag: function (params) {
				var term = $.trim(params.term);
				if (term === '') {
					return null;
				}
				return {
					id: 'new_' + term,
					text: term + ' (직접입력)',
					name: term,
					phone: '',
					newTag: true
				};
			}
		});
	}

	/**
	 * 신청자 관리 Offcanvas 열기
	 */
	function openApplicantOffcanvas(eduIdx) {
		// 양육명 조회
		var eduName = '';
		if (eduGrid) {
			var data = eduGrid.pqGrid("option", "dataModel.data");
			for (var i = 0; i < data.length; i++) {
				if (data[i].edu_idx == eduIdx) {
					eduName = data[i].edu_name;
					break;
				}
			}
		}

		$('#applicantOffcanvasTitle').text(eduName + ' 신청자 관리');

		const offcanvas = new bootstrap.Offcanvas('#applicantOffcanvas');
		offcanvas.show();

		// Offcanvas가 표시된 후 그리드 초기화
		$('#applicantOffcanvas').one('shown.bs.offcanvas', function () {
			initApplicantGrid();
			loadApplicantList(eduIdx);
		});
	}


	/**
	 * 신청자 목록 로드
	 */
	function loadApplicantList(eduIdx) {
		showApplicantGridSpinner();

		$.ajax({
			url: window.educationPageData.baseUrl + 'education/get_applicant_list',
			method: 'POST',
			data: { edu_idx: eduIdx },
			dataType: 'json',
			success: function (response) {
				hideApplicantGridSpinner();

				if (response.success) {
					updateApplicantGrid(response.data);
				} else {
					showToast(response.message || '신청자 목록 조회 실패', 'error');
				}
			},
			error: function () {
				hideApplicantGridSpinner();
				showToast('신청자 목록을 불러오는 중 오류가 발생했습니다.', 'error');
			}
		});
	}



	/**
	 * 신청자 인덱스로 데이터 찾기
	 */
	function findApplicantByIdx(applicantIdx) {
		if (!applicantGrid) return null;

		var data = applicantGrid.pqGrid('option', 'dataModel.data');
		for (var i = 0; i < data.length; i++) {
			if (data[i].applicant_idx == applicantIdx) {
				return data[i];
			}
		}
		return null;
	}

	/**
	 * 전체 선택/해제
	 */
	function toggleAllApplicantSelection(isChecked) {
		if (!applicantGrid) return;

		var data = applicantGrid.pqGrid('option', 'dataModel.data');
		data.forEach(function (row, index) {
			applicantGrid.pqGrid('updateRow', {
				rowIndx: index,
				row: { pq_selected: isChecked }
			});
		});
		applicantGrid.pqGrid('refresh');
	}





	/**
	 * 신청자 그리드 스피너 표시
	 */
	function showApplicantGridSpinner() {
		$('#applicantGridSpinner').removeClass('d-none').addClass('d-flex');
	}

	/**
	 * 신청자 그리드 스피너 숨김
	 */
	function hideApplicantGridSpinner() {
		$('#applicantGridSpinner').removeClass('d-flex').addClass('d-none');
	}

	/**
	 * 상태 뱃지 반환
	 */
	function getStatusBadge(status) {
		var badgeClass = 'bg-secondary';
		if (status === '신청') badgeClass = 'bg-primary';
		else if (status === '양육중') badgeClass = 'bg-warning text-dark';
		else if (status === '수료') badgeClass = 'bg-success';

		return '<span class="badge ' + badgeClass + '">' + status + '</span>';
	}

	/**
	 * 신청자 추가 모달 열기
	 */
	function openAddApplicantModal() {
		// 양육명 조회
		var eduName = '';
		if (eduGrid && currentApplicantEduIdx) {
			var data = eduGrid.pqGrid("option", "dataModel.data");
			for (var i = 0; i < data.length; i++) {
				if (data[i].edu_idx == currentApplicantEduIdx) {
					eduName = data[i].edu_name;
					break;
				}
			}
		}

		$('#addApplicantModalTitle').text(eduName + ' 신청자 추가');
		$('#applicantMemberSelect').val(null).trigger('change');

		$('#addApplicantModal').modal('show');
	}

	/**
	 * 신청자 저장
	 */
	function saveApplicant() {
		var selectedMembers = $('#applicantMemberSelect').select2('data');

		if (!selectedMembers || selectedMembers.length === 0) {
			showToast('신청자를 선택해주세요.', 'warning');
			return;
		}

		var applicants = selectedMembers.map(function (member) {
			return {
				member_idx: member.newTag ? null : member.id,
				name: member.name || member.text.split(' (')[0],
				phone: member.phone || ''
			};
		});

		$.ajax({
			url: window.educationPageData.baseUrl + 'education/add_applicants',
			method: 'POST',
			data: {
				edu_idx: currentApplicantEduIdx,
				applicants: JSON.stringify(applicants)
			},
			dataType: 'json',
			success: function (response) {
				if (response.success) {
					showToast('신청자가 추가되었습니다.', 'success');
					$('#addApplicantModal').modal('hide');
					loadApplicantList(currentApplicantEduIdx);
					loadEduList(); // 그리드 갱신 (신청자 수 업데이트)
				} else {
					showToast(response.message || '신청자 추가 실패', 'error');
				}
			},
			error: function () {
				showToast('신청자 추가 중 오류가 발생했습니다.', 'error');
			}
		});
	}

	/**
	 * 신청자 수정 모달 열기
	 */
	function openEditApplicantModal(applicant) {
		// 양육명 조회
		var eduName = '';
		if (eduGrid && currentApplicantEduIdx) {
			var data = eduGrid.pqGrid("option", "dataModel.data");
			for (var i = 0; i < data.length; i++) {
				if (data[i].edu_idx == currentApplicantEduIdx) {
					eduName = data[i].edu_name;
					break;
				}
			}
		}

		$('#editApplicantModalTitle').text(eduName + ' ' + applicant.applicant_name + ' 수정');
		$('#editApplicantIdx').val(applicant.applicant_idx);
		$('#editApplicantName').val(applicant.applicant_name);
		$('#editApplicantPhone').val(applicant.applicant_phone || '');
		$('#editApplicantStatus').val(applicant.status);

		$('#editApplicantModal').modal('show');
	}

	/**
	 * 신청자 수정
	 */
	function updateApplicant() {
		var applicantIdx = $('#editApplicantIdx').val();
		var name = $('#editApplicantName').val().trim();
		var phone = $('#editApplicantPhone').val().trim();
		var status = $('#editApplicantStatus').val();

		if (!name) {
			showToast('신청자명을 입력해주세요.', 'warning');
			return;
		}

		$.ajax({
			url: window.educationPageData.baseUrl + 'education/update_applicant',
			method: 'POST',
			data: {
				applicant_idx: applicantIdx,
				applicant_name: name,
				applicant_phone: phone,
				status: status
			},
			dataType: 'json',
			success: function (response) {
				if (response.success) {
					showToast('신청자 정보가 수정되었습니다.', 'success');
					$('#editApplicantModal').modal('hide');
					loadApplicantList(currentApplicantEduIdx);
				} else {
					showToast(response.message || '신청자 수정 실패', 'error');
				}
			},
			error: function () {
				showToast('신청자 수정 중 오류가 발생했습니다.', 'error');
			}
		});
	}

	var deleteApplicantIdx = null;
	var deleteSelectedApplicantsList = [];

	/**
	 * 신청자 삭제 모달 열기
	 */
	function openDeleteApplicantModal(applicantIdx) {
		deleteApplicantIdx = applicantIdx;
		deleteSelectedApplicantsList = [];
		$('#deleteApplicantModal .modal-body p').text('선택한 신청자를 삭제하시겠습니까?');
		$('#deleteApplicantModal').modal('show');
	}

	/**
	 * 신청자 삭제 확인 (단일 및 다중 삭제 통합)
	 */
	function confirmDeleteApplicant() {
		// 선택 삭제인 경우
		if (deleteSelectedApplicantsList && deleteSelectedApplicantsList.length > 0) {
			confirmDeleteSelectedApplicants();
			return;
		}

		// 단일 삭제인 경우
		if (!deleteApplicantIdx) return;

		$.ajax({
			url: window.educationPageData.baseUrl + 'education/delete_applicant',
			method: 'POST',
			data: { applicant_idx: deleteApplicantIdx },
			dataType: 'json',
			success: function (response) {
				if (response.success) {
					showToast('신청자가 삭제되었습니다.', 'success');
					$('#deleteApplicantModal').modal('hide');
					deleteApplicantIdx = null;
					loadApplicantList(currentApplicantEduIdx);
					loadEduList();
				} else {
					showToast(response.message || '신청자 삭제 실패', 'error');
				}
			},
			error: function () {
				showToast('신청자 삭제 중 오류가 발생했습니다.', 'error');
			}
		});
	}

	/**
	 * 상태 일괄변경 적용
	 */
	function applyBulkStatus() {
		var status = $('#bulkStatusSelect').val();
		var selectedApplicants = getSelectedApplicants();

		if (selectedApplicants.length === 0) {
			showToast('상태를 변경할 신청자를 선택해주세요.', 'warning');
			return;
		}

		var applicantIdxList = selectedApplicants.map(function (item) {
			return item.applicant_idx;
		});

		$.ajax({
			url: window.educationPageData.baseUrl + 'education/bulk_update_status',
			method: 'POST',
			data: {
				edu_idx: currentApplicantEduIdx,
				applicant_idx_list: JSON.stringify(applicantIdxList),
				status: status
			},
			dataType: 'json',
			success: function (response) {
				if (response.success) {
					showToast('선택한 신청자의 상태가 변경되었습니다.', 'success');
					$('#bulkStatusModal').modal('hide');
					loadApplicantList(currentApplicantEduIdx);
				} else {
					showToast(response.message || '상태 변경 실패', 'error');
				}
			},
			error: function () {
				showToast('상태 변경 중 오류가 발생했습니다.', 'error');
			}
		});
	}

	/**
	 * 선택 삭제 모달 열기
	 */
	function openDeleteSelectedApplicantsModal(selectedApplicants) {
		deleteSelectedApplicantsList = selectedApplicants;
		$('#deleteApplicantModal .modal-body p').text(selectedApplicants.length + '명의 신청자를 삭제하시겠습니까?');
		$('#deleteApplicantModal').modal('show');
	}

	/**
	 * 선택 삭제 확인
	 */
	function confirmDeleteSelectedApplicants() {
		if (deleteSelectedApplicantsList.length === 0) return;

		var applicantIdxList = deleteSelectedApplicantsList.map(function (item) {
			return item.applicant_idx;
		});

		$.ajax({
			url: window.educationPageData.baseUrl + 'education/delete_applicants',
			method: 'POST',
			data: { applicant_idx_list: JSON.stringify(applicantIdxList) },
			dataType: 'json',
			success: function (response) {
				if (response.success) {
					showToast(applicantIdxList.length + '명의 신청자가 삭제되었습니다.', 'success');
					$('#deleteApplicantModal').modal('hide');
					deleteSelectedApplicantsList = [];
					loadApplicantList(currentApplicantEduIdx);
					loadEduList();
				} else {
					showToast(response.message || '신청자 삭제 실패', 'error');
				}
			},
			error: function () {
				showToast('신청자 삭제 중 오류가 발생했습니다.', 'error');
			}
		});
	}

	/**
	 * 파일 위치: assets/js/education.js
	 * 역할: 신청자 그리드 초기화 함수 수정
	 * - 체크박스 방식을 양육관리 pqGrid 방식으로 변경
	 * - 전체 체크박스 추가
	 * - 관리 필드 제거
	 * - 신청자명에 text-primary 적용 및 클릭 시 수정 모달 표시
	 */

	/**
	 * 신청자 그리드 초기화
	 */
	function initApplicantGrid() {
		// 기존 그리드 제거
		if (applicantGrid) {
			try {
				applicantGrid.pqGrid('destroy');
			} catch (e) {
				console.log('그리드 제거 오류:', e);
			}
			applicantGrid = null;
		}

		var colModel = [
			{
				title: '<input type="checkbox" id="applicantSelectAllCheckbox" />',
				dataIndx: 'checkbox',
				width: 40,
				align: 'center',
				sortable: false,
				editable: false,
				render: function (ui) {
					return '<input type="checkbox" class="applicant-checkbox" data-idx="' + ui.rowData.applicant_idx + '" />';
				}
			},
			{
				title: '신청일',
				dataIndx: 'regi_date',
				dataType: 'string',
				width: 160,
				align: 'center',
				render: function (ui) {
					if (ui.rowData.regi_date) {
						return ui.rowData.regi_date.substring(0, 16).replace('T', ' ');
					}
					return '';
				}
			},
			{
				title: '신청자',
				dataIndx: 'applicant_name',
				dataType: 'string',
				width: 100,
				align: 'center',
				render: function (ui) {
					return '<span class="text-primary cursor-pointer applicant-name-link" data-idx="' + ui.rowData.applicant_idx + '">' + ui.rowData.applicant_name + '</span>';
				}
			},
			{
				title: '연락처',
				dataIndx: 'applicant_phone',
				dataType: 'string',
				width: 140,
				align: 'center',
				render: function (ui) {
					return ui.rowData.applicant_phone || '-';
				}
			},
			{
				title: '상태',
				dataIndx: 'status',
				dataType: 'string',
				width: 80,
				align: 'center',
				render: function (ui) {
					return getStatusBadge(ui.rowData.status);
				}
			}
		];

		applicantGrid = $('#applicantGrid').pqGrid({
			width: '100%',
			height: '700',
			showTitle: false,
			showHeader: true,
			showTop: false,
			showBottom: true,
			numberCell: { show: false },
			stripeRows: true,
			selectionModel: { type: 'row', mode: 'range' },
			scrollModel: { autoFit: true },
			colModel: colModel,
			dataModel: { data: [] },
			strNoRows: '신청자 정보가 없습니다',
			rowInit: function (ui) {
				var style = "height: 40px;";
				return {
					style: style,
				};
			},
			refresh: function () {
				updateApplicantSelectAllCheckbox();
			}
		});

		// 이벤트 바인딩
		bindApplicantGridEvents();
	}

	/**
	 * 파일 위치: assets/js/education.js
	 * 역할: 신청자 그리드 이벤트 바인딩
	 */
	function bindApplicantGridEvents() {
		// 전체 선택 체크박스
		$(document).off('change', '#applicantSelectAllCheckbox').on('change', '#applicantSelectAllCheckbox', function () {
			var isChecked = $(this).prop('checked');
			$('.applicant-checkbox').prop('checked', isChecked);
		});

		// 개별 체크박스
		$(document).off('change', '.applicant-checkbox').on('change', '.applicant-checkbox', function () {
			updateApplicantSelectAllCheckbox();
		});

		// 신청자명 클릭 시 수정 모달
		$(document).off('click', '.applicant-name-link').on('click', '.applicant-name-link', function () {
			var applicantIdx = $(this).data('idx');
			var rowData = findApplicantByIdx(applicantIdx);
			if (rowData) {
				openEditApplicantModal(rowData);
			}
		});
	}

	/**
	 * 파일 위치: assets/js/education.js
	 * 역할: 전체 선택 체크박스 상태 업데이트
	 */
	function updateApplicantSelectAllCheckbox() {
		const totalCheckboxes = $('.applicant-checkbox').length;
		const checkedCount = $('.applicant-checkbox:checked').length;
		const selectAllCheckbox = $('#applicantSelectAllCheckbox');

		if (totalCheckboxes === 0 || checkedCount === 0) {
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
	 * 파일 위치: assets/js/education.js
	 * 역할: 선택된 신청자 목록 반환 (수정됨)
	 */
	function getSelectedApplicants() {
		var selectedApplicants = [];

		$('.applicant-checkbox:checked').each(function () {
			var applicantIdx = $(this).data('idx');
			var rowData = findApplicantByIdx(applicantIdx);
			if (rowData) {
				selectedApplicants.push(rowData);
			}
		});

		return selectedApplicants;
	}

	/**
	 * 파일 위치: assets/js/education.js
	 * 역할: 신청자 그리드 데이터 업데이트 (수정됨)
	 */
	function updateApplicantGrid(applicants) {
		if (!applicantGrid) {
			return;
		}

		var data = applicants || [];
		applicantGrid.pqGrid('option', 'dataModel.data', data);
		applicantGrid.pqGrid('refreshDataAndView');

		// 총 인원 수 업데이트
		$('#applicantTotalCount').text(data.length);

		// 전체 선택 체크박스 초기화
		$('#applicantSelectAllCheckbox').prop('checked', false);
		$('#applicantSelectAllCheckbox').prop('indeterminate', false);
	}



	function getEduInfoByIdx(eduIdx) {
		if (!eduGrid) return null;

		var data = eduGrid.pqGrid("option", "dataModel.data");

		for (var i = 0; i < data.length; i++) {
			if (data[i].edu_idx == eduIdx) {
				return data[i];
			}
		}

		return null;
	}

	/**
	 * 선택된 양육 정보 가져오기
	 */
	function getSelectedEdu() {
		if (!eduGrid) return null;

		var selection = eduGrid.pqGrid("selection", { type: 'row', method: 'getSelection' });

		if (!selection || selection.length === 0) {
			return null;
		}

		var data = eduGrid.pqGrid("option", "dataModel.data");
		var selectedRow = selection[0];

		return data[selectedRow.rowIndx];
	}



	// 파일 위치: assets/js/education.js
// 역할: 외부 URL 모달 열기 - 기존 URL 조회 후 모달 표시

	/**
	 * 외부 URL 모달 열기
	 */
	function openExternalUrlModal() {
		if (!currentApplicantEduIdx) {
			showToast('양육 정보가 없습니다.', 'warning');
			return;
		}

		// 외부 공개 여부 확인
		var eduInfo = getEduInfoByIdx(currentApplicantEduIdx);
		if (eduInfo.public_yn !== 'Y') {
			showToast('외부 공개로 설정된 양육만 URL을 생성할 수 있습니다.', 'warning');
			return;
		}

		currentExternalEduIdx = currentApplicantEduIdx;

		// 기존 URL 조회 (생성하지 않음)
		loadExistingExternalUrl();

		$('#externalUrlModal').modal('show');
	}

	/**
	 * 기존 외부 URL 조회
	 */
	function loadExistingExternalUrl() {
		if (!currentExternalEduIdx) {
			$('#externalUrlInput').val('외부 URL이 생성되지 않았습니다. 갱신 버튼을 클릭하세요.');
			return;
		}

		$.ajax({
			url: window.educationPageData.baseUrl + 'education/get_existing_external_url',
			method: 'POST',
			data: { edu_idx: currentExternalEduIdx },
			dataType: 'json',
			success: function (response) {
				if (response.success && response.url) {
					$('#externalUrlInput').val(response.url);
				} else {
					$('#externalUrlInput').val('외부 URL이 생성되지 않았습니다. 갱신 버튼을 클릭하세요.');
				}
			},
			error: function () {
				$('#externalUrlInput').val('외부 URL 조회 중 오류가 발생했습니다.');
			}
		});
	}

	/**
	 * 외부 URL 생성/갱신
	 */
	function generateExternalUrl() {
		if (!currentExternalEduIdx) {
			showToast('양육 정보가 없습니다.', 'error');
			return;
		}

		$.ajax({
			url: window.educationPageData.baseUrl + 'education/generate_external_url',
			method: 'POST',
			data: { edu_idx: currentExternalEduIdx },
			dataType: 'json',
			success: function (response) {
				if (response.success) {
					$('#externalUrlInput').val(response.url);
					showToast('외부 URL이 생성되었습니다.', 'success');
				} else {
					showToast(response.message || '외부 URL 생성에 실패했습니다.', 'error');
				}
			},
			error: function () {
				showToast('외부 URL 생성 중 오류가 발생했습니다.', 'error');
			}
		});
	}

	/**
	 * 외부 URL 복사
	 */
	function copyExternalUrl() {
		var url = $('#externalUrlInput').val();

		if (!url) {
			showToast('복사할 URL이 없습니다.', 'warning');
			return;
		}

		// 클립보드에 복사
		if (navigator.clipboard && navigator.clipboard.writeText) {
			navigator.clipboard.writeText(url).then(function() {
				showToast('URL이 클립보드에 복사되었습니다.', 'success');
			}).catch(function() {
				fallbackCopyTextToClipboard(url);
			});
		} else {
			fallbackCopyTextToClipboard(url);
		}
	}

	/**
	 * 클립보드 복사 fallback 함수
	 */
	function fallbackCopyTextToClipboard(text) {
		var textArea = document.createElement("textarea");
		textArea.value = text;
		textArea.style.position = "fixed";
		textArea.style.top = "0";
		textArea.style.left = "0";
		textArea.style.width = "2em";
		textArea.style.height = "2em";
		textArea.style.padding = "0";
		textArea.style.border = "none";
		textArea.style.outline = "none";
		textArea.style.boxShadow = "none";
		textArea.style.background = "transparent";
		document.body.appendChild(textArea);
		textArea.focus();
		textArea.select();

		try {
			var successful = document.execCommand('copy');
			if (successful) {
				showToast('URL이 클립보드에 복사되었습니다.', 'success');
			} else {
				showToast('복사에 실패했습니다.', 'error');
			}
		} catch (err) {
			showToast('복사에 실패했습니다.', 'error');
		}

		document.body.removeChild(textArea);
	}
});
