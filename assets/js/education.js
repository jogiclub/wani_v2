'use strict'

$(document).ready(function () {
	// 전역 변수 영역
	let eduGrid;                      // ParamQuery Grid 인스턴스
	let selectedOrgId = null;         // 선택된 조직 ID
	let selectedCategoryCode = null;  // 선택된 카테고리 코드
	let selectedType = null;          // 선택된 타입 ('org', 'category')
	let splitInstance = null;         // Split.js 인스턴스
	let categoryData = [];            // 카테고리 데이터
	let categoryMap = {};             // 카테고리 코드-이름 매핑
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
		nodes.forEach(function(node) {
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
					// 카테고리명 추가
					const dataWithCategoryName = response.data.map(function(item) {
						item.category_name = categoryMap[item.category_code] || item.category_code;
						return item;
					});

					updateGrid(dataWithCategoryName);
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
	 * 교육 등록 Offcanvas 열기
	 */
	function openEduAddOffcanvas() {
		resetEduForm();
		loadCategoryOptions();
		$('#eduOffcanvasTitle').text('교육 등록');
		$('#eduIdx').val('');
		$('#eduOrgId').val(selectedOrgId || window.educationPageData.currentOrgId);

		const offcanvas = new bootstrap.Offcanvas('#eduOffcanvas');
		offcanvas.show();
	}

	/**
	 * 교육 수정 Offcanvas 열기
	 */
	function openEduEditOffcanvas(eduIdx) {
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
					$('#eduOffcanvasTitle').text('교육 수정');

					const offcanvas = new bootstrap.Offcanvas('#eduOffcanvas');
					offcanvas.show();
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
	 * 요일 체크박스 이벤트 바인딩
	 */
	function bindDayCheckboxEvents() {
		$('.edu-day-checkbox').off('change').on('change', function() {
			updateDaysDisplay();
		});
	}

	/**
	 * 시간대 체크박스 이벤트 바인딩
	 */
	function bindTimeCheckboxEvents() {
		$('.edu-time-checkbox').off('change').on('change', function() {
			updateTimesDisplay();
		});
	}

	/**
	 * 요일 선택 표시 업데이트
	 */
	function updateDaysDisplay() {
		const selectedDays = [];
		$('.edu-day-checkbox:checked').each(function() {
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
		$('.edu-time-checkbox:checked').each(function() {
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
	 * 파일 위치: assets/js/education.js
	 * 역할: 수강료, 포스터 필드 추가
	 */

	/**
	 * ParamQuery Grid 초기화 - 수강료 컬럼 추가
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
						render: function(ui) {
							var checkboxId = 'edu-checkbox-' + ui.rowData.edu_idx;
							return '<input type="checkbox" class="edu-checkbox" id="' + checkboxId + '" data-edu-idx="' + ui.rowData.edu_idx + '" />';
						}
					},
					{
						title: "교육카테고리",
						dataIndx: "category_name",
						width: 120,
						editable: false,
						align: "center"
					},
					{
						title: "교육명",
						dataIndx: "edu_name",
						editable: false,
						width: 200
					},
					{
						title: "교육지역",
						dataIndx: "edu_location",
						editable: false,
						width: 150
					},
					{
						title: "교육기간",
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
						render: function(ui) {
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
						width: 120,
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
						render: function(ui) {
							return ui.cellData === 'Y' ? '공개' : '비공개';
						}
					},
					{
						title: "온라인",
						dataIndx: "online_yn",
						width: 80,
						align: "center",
						render: function(ui) {
							return ui.cellData === 'Y' ? '가능' : '-';
						}
					}
				],
				strNoRows: '교육 정보가 없습니다',
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
				cellClick: function(event, ui) {
					// 체크박스 컬럼 클릭 시
					if (ui.dataIndx === 'checkbox') {
						handleCheckboxClick(event, ui.rowData.edu_idx);
					}
					// 다른 컬럼 클릭 시 수정 offcanvas 열기
					else {
						openEduEditOffcanvas(ui.rowData.edu_idx);
					}
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
		// 교육 등록 버튼
		$('#btnAddEdu').off('click').on('click', function() {
			openEduAddOffcanvas();
		});

		// 선택 삭제 버튼
		$('#btnDeleteSelected').off('click').on('click', function() {
			deleteSelectedEdu();
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

		// 수강료 입력 포맷팅
		$('#eduFee').off('input').on('input', function() {
			var val = $(this).val().replace(/[^\d]/g, '');
			$(this).val(formatNumber(val));
		});

		// 포스터 이미지 선택
		$('#eduPosterImg').off('change').on('change', function(e) {
			handlePosterImageSelect(e);
		});

		// 포스터 이미지 삭제
		$('#btnRemovePoster').off('click').on('click', function() {
			removePosterImage();
		});

		// 요일 체크박스 이벤트
		bindDayCheckboxEvents();

		// 시간대 체크박스 이벤트
		bindTimeCheckboxEvents();

		// 전체 선택 체크박스
		$(document).on('change', '#selectAllCheckbox', function() {
			var isChecked = $(this).prop('checked');
			$('.edu-checkbox').prop('checked', isChecked);
		});

		// 개별 체크박스
		$(document).on('change', '.edu-checkbox', function() {
			updateSelectAllCheckbox();
		});

		// 윈도우 리사이즈
		$(window).off('resize.education').on('resize.education', debounce(function() {
			if (eduGrid) {
				try {
					eduGrid.pqGrid("refresh");
				} catch (error) {
					console.error('그리드 리사이즈 실패:', error);
				}
			}
		}, 250));
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
		reader.onload = function(e) {
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
	 * 교육 폼 채우기 - 수강료, 포스터 추가
	 */
	function fillEduForm(eduData) {
		$('#eduIdx').val(eduData.edu_idx);
		$('#eduOrgId').val(eduData.org_id);
		$('#eduName').val(eduData.edu_name);
		$('#eduLocation').val(eduData.edu_location);
		$('#eduLeader').val(eduData.edu_leader);
		$('#eduLeaderPhone').val(eduData.edu_leader_phone);
		$('#eduLeaderAge').val(eduData.edu_leader_age);
		$('#eduLeaderGender').val(eduData.edu_leader_gender);
		$('#eduDesc').val(eduData.edu_desc);

		// 수강료
		var fee = parseInt(eduData.edu_fee) || 0;
		$('#eduFee').val(fee > 0 ? formatNumber(fee) : '0');

		// 외부공개 여부
		$('#eduPublicYn').val(eduData.public_yn || 'N');

		// 온라인 가능 여부
		$('#eduOnlineYn').val(eduData.online_yn || 'N');

		// 유튜브 URL
		$('#eduYoutubeUrl').val(eduData.youtube_url || '');

		// 포스터 이미지
		$('#removePosterFlag').val('0');
		if (eduData.poster_img) {
			$('#posterPreview').attr('src', window.educationPageData.baseUrl + eduData.poster_img).show();
			$('#posterPlaceholder').hide();
			$('#btnRemovePoster').show();
		} else {
			$('#posterPreview').attr('src', '').hide();
			$('#posterPlaceholder').show();
			$('#btnRemovePoster').hide();
		}

		// 카테고리 옵션 로드 후 선택
		loadCategoryOptions(eduData.category_code);

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
			updateDaysDisplay();
		}

		// 시간대 체크박스 설정
		$('.edu-time-checkbox').prop('checked', false);
		if (eduData.edu_times && Array.isArray(eduData.edu_times)) {
			eduData.edu_times.forEach(function(time) {
				$('.edu-time-checkbox[value="' + time + '"]').prop('checked', true);
			});
			updateTimesDisplay();
		}
	}

	/**
	 * 교육 폼 초기화 - 수강료, 포스터 추가
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
	 * 교육 저장 - FormData로 변경
	 */
	function saveEdu() {
		// 필수값 검증
		if (!$('#eduCategoryCode').val()) {
			showToast('교육카테고리를 선택해주세요.', 'warning');
			return;
		}

		if (!$('#eduName').val().trim()) {
			showToast('교육명을 입력해주세요.', 'warning');
			return;
		}

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
		formData.append('online_yn', $('#eduOnlineYn').val());
		formData.append('youtube_url', $('#eduYoutubeUrl').val());

		// 수강료 (숫자만 추출)
		var feeValue = $('#eduFee').val().replace(/[^\d]/g, '');
		formData.append('edu_fee', feeValue || '0');

		// 포스터 이미지
		var posterFile = $('#eduPosterImg')[0].files[0];
		if (posterFile) {
			formData.append('poster_img', posterFile);
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
			success: function(response) {
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
	 * 숫자 포맷팅 함수 (천단위 콤마)
	 */
	function formatNumber(num) {
		if (!num) return '0';
		return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
	}


	/**
	 * 선택된 교육 삭제
	 */
	function deleteSelectedEdu() {
		// 선택된 체크박스에서 교육 인덱스 수집
		var eduIndexes = [];
		$('.edu-checkbox:checked').each(function() {
			var eduIdx = $(this).data('edu-idx');
			if (eduIdx) {
				eduIndexes.push(eduIdx);
			}
		});

		if (eduIndexes.length === 0) {
			showToast('삭제할 교육을 선택해주세요.', 'warning');
			return;
		}

		// 삭제 확인 모달
		showConfirmModal(
			'교육 삭제',
			'선택한 ' + eduIndexes.length + '개의 교육을 삭제하시겠습니까?',
			function() {
				deleteMultipleEdu(eduIndexes);
			}
		);
	}

	/**
	 * 여러 교육 삭제
	 */
	function deleteMultipleEdu(eduIndexes) {
		showSpinner();

		$.ajax({
			url: window.educationPageData.baseUrl + 'education/delete_multiple_edu',
			method: 'POST',
			data: { edu_indexes: eduIndexes },
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
			showToast('JSON 형식이 올바르지 않습니다.', 'warning');
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
});
