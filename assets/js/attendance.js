'use strict'




$(document).ready(function () {
	// ===== 전역 변수 영역 =====
	let attendanceGrid;                // ParamQuery Grid 인스턴스
	let selectedOrgId = null;          // 선택된 조직 ID
	let selectedAreaIdx = null;        // 선택된 소그룹 ID
	let selectedType = null;           // 선택된 타입 ('org', 'area', 'unassigned')
	let splitInstance = null;          // Split.js 인스턴스
	let currentYear = window.attendancePageData.currentYear; // 현재 년도
	let attendanceTypes = [];          // 출석 유형 목록
	let sundayDates = [];              // 일요일 날짜 목록
	let attendanceData = {};           // 출석 데이터
	let currentMembers = [];           // 현재 회원 목록
	let selectedAttTypeIdx = 'all';    // 선택된 출석유형 (기본: 전체)



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

						if (attendanceGrid) {
							try {
								attendanceGrid.pqGrid("refresh");
							} catch (error) {
								console.error('그리드 리프레시 실패:', error);
							}
						}
					// 로컬스토리지에 크기 저장 (선택사항)
					localStorage.setItem('member-split-sizes', JSON.stringify(sizes));
				}
			});

			// 저장된 크기 복원
			const savedSizes = localStorage.getItem('member-split-sizes');
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
	 * 전역 이벤트 바인딩 함수에서 저장 버튼 이벤트 수정
	 */
	function bindGlobalEvents() {
		// 연도 변경 버튼
		$('#prevYear').off('click').on('click', function () {
			changeYear(-1);
		});

		$('#nextYear').off('click').on('click', function () {
			changeYear(1);
		});

		// 점수재계산 버튼
		$('#btnRecalculateStats').off('click').on('click', function () {
			recalculateAttendanceStats();
		});

		// 출석등록 버튼
		$('#btnAddAttendance').off('click').on('click', function () {
			showToast('출석등록 기능은 추후 구현 예정입니다.', 'info');
		});

		// 출석저장 버튼 - saveAttendanceDetail 함수로 수정
		$('#btnSaveAttendance').off('click').on('click', function () {
			saveAttendanceDetail();
		});

		// 출석유형별 탭 이벤트 추가
		bindAttendanceTypeTabEvents();

		// 검색 기능
		bindSearchEvents();

		$('#btnExcelDownload').off('click').on('click', function () {
			downloadAttendanceExcel();
		});

		// 윈도우 리사이즈
		$(window).off('resize.attendance').on('resize.attendance', debounce(function () {
			if (attendanceGrid) {
				try {
					attendanceGrid.pqGrid("refresh");
				} catch (error) {
					console.error('윈도우 리사이즈 시 그리드 리프레시 실패:', error);
				}
			}
		}, 250));
	}

	/**
	 * 검색 이벤트 바인딩
	 */
	function bindSearchEvents() {
		const searchInput = $('.card-header input[type="text"]');
		const searchButton = $('#button-search');

		searchButton.off('click').on('click', function () {
			const searchText = searchInput.val().trim();
			filterGrid(searchText);
		});

		searchInput.off('keypress.search').on('keypress.search', function (e) {
			if (e.which === 13) {
				e.preventDefault();
				const searchText = $(this).val().trim();
				filterGrid(searchText);
			}
		});

		searchInput.off('input.search').on('input.search', function () {
			const searchText = $(this).val().trim();
			if (searchText === '') {
				clearGridFilter();
			}
		});
	}




	function bindAttendanceTypeTabEvents() {
		$(document).off('click', '#attendance-type-tabs button[data-att-type-idx]').on('click', '#attendance-type-tabs button[data-att-type-idx]', function () {
			const newAttTypeIdx = $(this).data('att-type-idx');

			if (newAttTypeIdx !== selectedAttTypeIdx) {
				selectedAttTypeIdx = newAttTypeIdx;

				// 탭 활성화 상태 업데이트
				$('#attendance-type-tabs button').removeClass('active');
				$(this).addClass('active');

				// 그리드 데이터 업데이트
				updateGridWithSelectedAttendanceType();
			}
		});
	}


	/**
	 * 선택된 출석유형에 따라 그리드 업데이트
	 */
	function updateGridWithSelectedAttendanceType() {
		if (!attendanceGrid) {
			return;
		}

		try {
			const gridData = prepareGridData();
			attendanceGrid.pqGrid("option", "dataModel.data", gridData);
			attendanceGrid.pqGrid("refreshDataAndView");
		} catch (error) {
			console.error('출석유형별 그리드 업데이트 실패:', error);
		}
	}

	/**
	 * 출석유형 목록 로드 및 탭 생성
	 */
	function createAttendanceTypeTabs() {
		const $tabContainer = $('#attendance-type-tabs');

		// 기존 동적 탭 제거 (전체 탭은 유지)
		$tabContainer.find('li:not(:first)').remove();

		// 출석유형별 탭 추가
		attendanceTypes.forEach(function (attType) {
			const backgroundColor = attType.att_type_color ? '#' + attType.att_type_color : '#6c757d';
			const tabHtml = `
			<li class="nav-item" role="presentation">
				<button class="nav-link" id="tab-${attType.att_type_idx}" 
						data-bs-toggle="pill" 
						data-att-type-idx="${attType.att_type_idx}" 
						type="button" role="tab"						
						aria-selected="false">
					${attType.att_type_nickname || attType.att_type_name}
				</button>
			</li>
		`;
			$tabContainer.append(tabHtml);
		});

		// 첫 번째 탭(전체) 활성화
		selectedAttTypeIdx = 'all';
		$('#tab-all').addClass('active');
	}

	/**
	 * 연도 변경
	 */
	function changeYear(direction) {
		const newYear = currentYear + direction;

		if (newYear < 2020 || newYear > 2030) {
			showToast('년도 범위를 벗어났습니다.', 'warning');
			return;
		}

		currentYear = newYear;
		$('#currentYear').text(currentYear);

		if (selectedOrgId) {
			loadAttendanceData();
		}
	}

	/**
	 * Fancytree 초기화
	 */
	function initializeFancytree() {

		showTreeSpinner();

		$.ajax({
			url: window.attendancePageData.baseUrl + 'attendance/get_org_tree',
			method: 'POST',
			dataType: 'json',
			success: function (treeData) {


				if (!treeData || treeData.length === 0) {
					hideTreeSpinner();
					showToast('조직 데이터가 없습니다.', 'warning');
					return;
				}

				setupFancytreeInstance(treeData);
				hideTreeSpinner();
			},
			error: function (xhr, status, error) {
				console.error('트리 데이터 로드 실패:', error);
				hideTreeSpinner();
				showToast('그룹 정보를 불러오는데 실패했습니다.', 'error');
			}
		});
	}

	/**
	 * Fancytree 인스턴스 설정 - localStorage 복원 기능 추가
	 */
	function setupFancytreeInstance(treeData) {
		$("#groupTree").fancytree({
			source: treeData,
			activate: function (event, data) {
				const node = data.node;

				handleTreeNodeActivate(node);
			},
			expand: function (event, data) {

			},
			collapse: function (event, data) {

			}
		});

		// localStorage에서 저장된 선택 상태 복원 우선 시도
		restoreSelectedGroupFromStorage(treeData);
	}

	/**
	 * 트리 노드 활성화 처리 - localStorage 저장 기능 추가
	 */
	function handleTreeNodeActivate(node) {
		const nodeData = node.data;

		selectedType = nodeData.type;
		selectedOrgId = nodeData.org_id;
		selectedAreaIdx = nodeData.area_idx || null;

		// localStorage에 선택 상태 저장
		saveSelectedGroupToStorage(nodeData);

		updateSelectedOrgName(node.title, nodeData.type);
		resetSearch();
		loadAttendanceData();
	}

	/**
	 * localStorage에 선택된 그룹 저장
	 */
	function saveSelectedGroupToStorage(nodeData) {
		try {
			const selectedGroup = {
				type: nodeData.type,
				org_id: nodeData.org_id,
				area_idx: nodeData.area_idx || null,
				timestamp: Date.now()
			};

			localStorage.setItem('member_selected_group', JSON.stringify(selectedGroup));

		} catch (error) {
			console.error('localStorage 저장 실패:', error);
		}
	}



	/**
	 * localStorage에서 저장된 그룹 선택 상태 복원
	 */
	function restoreSelectedGroupFromStorage(treeData) {
		try {
			const savedGroup = localStorage.getItem('member_selected_group');

			if (!savedGroup) {
				selectFirstOrganization();
				return;
			}

			const groupData = JSON.parse(savedGroup);

			// 7일 이내의 데이터만 복원
			const sevenDaysAgo = Date.now() - (7 * 24 * 60 * 60 * 1000);
			if (groupData.timestamp < sevenDaysAgo) {
				localStorage.removeItem('member_selected_group');
				selectFirstOrganization();
				return;
			}

			// 저장된 노드 찾기 및 선택
			const tree = $("#groupTree").fancytree("getTree");
			const nodeToSelect = findSavedNode(tree, groupData);

			if (nodeToSelect) {
				nodeToSelect.setActive(true);
				nodeToSelect.setFocus(true);
				expandParentNodes(nodeToSelect, groupData);

			} else {

				selectFirstOrganization();
			}

		} catch (error) {
			console.error('localStorage 복원 실패:', error);
			selectFirstOrganization();
		}
	}


	/**
	 * 저장된 노드 찾기
	 */
	function findSavedNode(tree, groupData) {
		let nodeToSelect = null;

		if (groupData.type === 'unassigned' && groupData.org_id) {
			nodeToSelect = tree.getNodeByKey('unassigned_' + groupData.org_id);
		} else if (groupData.type === 'area' && groupData.area_idx) {
			nodeToSelect = tree.getNodeByKey('area_' + groupData.area_idx);
		}

		if (!nodeToSelect && groupData.org_id) {
			nodeToSelect = tree.getNodeByKey('org_' + groupData.org_id);
		}

		return nodeToSelect;
	}

	/**
	 * 부모 노드 확장
	 */
	function expandParentNodes(nodeToSelect, groupData) {
		if (groupData.type !== 'unassigned' && nodeToSelect.parent && !nodeToSelect.parent.isRootNode()) {
			nodeToSelect.parent.setExpanded(true);
		}
	}

	/**
	 * 첫 번째 조직 자동 선택
	 */
	function selectFirstOrganization() {
		const tree = $("#groupTree").fancytree("getTree");
		if (tree && tree.rootNode && tree.rootNode.children.length > 0) {
			const firstOrgNode = tree.rootNode.children[0];
			firstOrgNode.setActive(true);
			firstOrgNode.setFocus(true);
			firstOrgNode.setExpanded(true);
		}
	}

	/**
	 * ParamQuery Grid 초기화(목록)
	 */
	function initializeParamQuery() {
		showGridSpinner();

		const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)
			|| window.innerWidth <= 768;

		try {
			attendanceGrid = $("#attendanceGrid").pqGrid({
				width: "100%",
				dataModel: {data: []},
				colModel: getInitialColumns(),
				strNoRows: '출석 정보가 없습니다',
				selectionModel: {type: '', mode: 'single'},
				scrollModel: {autoFit: false, horizontal: true, vertical: true},
				freezeCols: isMobile ? 0 : 4,
				numberCell: {show: false},
				title: false,
				resizable: true,
				sortable: false,
				hoverMode: 'cell',
				wrap: false,
				columnBorders: true,


				// 그리드 완성 후 직접 이벤트 바인딩
				complete: function() {
					// 모바일을 위한 직접 터치 이벤트 바인딩
					bindDirectTouchEvents();
				},
				cellMouseEnter: function(event, ui) {
					if (ui.colIndx >= 4 && ui.dataIndx && ui.dataIndx.startsWith('week_')) {
						$(ui.$cell).css('cursor', 'pointer');
					}
				},
				cellMouseLeave: function(event, ui) {
					$(ui.$cell).css('cursor', 'default');
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
	 * 직접 터치 이벤트 바인딩 - ParamQuery Grid 우회 (수정된 버전)
	 */
	function bindDirectTouchEvents() {
		// 그리드 컨테이너에 직접 터치 이벤트 바인딩 (수정된 버전)
		$('#attendanceGrid').off('touchend.attendance click.attendance')
			.on('touchend.attendance click.attendance', 'td', function(e) {
				e.preventDefault();
				e.stopPropagation();

				const $cell = $(this);
				const $row = $cell.closest('tr');

				// 헤더 행 제외
				if ($row.hasClass('pq-grid-header') || $row.find('th').length > 0) {
					return;
				}

				// pqgrid의 데이터 속성을 사용하여 정확한 행/열 인덱스 찾기
				const dataRowIndex = $row.attr('pq-row-indx');
				const dataCellIndex = $cell.attr('pq-col-indx');

				if (dataRowIndex === undefined || dataCellIndex === undefined) {
					return;
				}

				const rowIndex = parseInt(dataRowIndex);
				const cellIndex = parseInt(dataCellIndex);

				if (rowIndex < 0 || cellIndex < 0 || isNaN(rowIndex) || isNaN(cellIndex)) {
					return;
				}

				// 출석 데이터 컬럼인지 확인 (4번째 컬럼부터)
				if (cellIndex >= 4) {
					// 그리드 데이터에서 해당 행 정보 가져오기
					try {
						const gridData = attendanceGrid.pqGrid("option", "dataModel.data");
						const rowData = gridData[rowIndex];

						if (rowData && rowData.member_idx) {
							// 컬럼 모델에서 해당 컬럼의 dataIndx 찾기
							const colModel = attendanceGrid.pqGrid("option", "colModel");
							const colData = colModel[cellIndex];

							if (colData && colData.dataIndx && colData.dataIndx.startsWith('week_')) {
								// 일요일 날짜 추출
								const sundayDateStr = colData.dataIndx.replace('week_', '');
								const sunday = sundayDateStr.replace(/(\d{4})(\d{2})(\d{2})/, '$1-$2-$3');

								// console.log('Direct touch event triggered (attendance):', {
								// 	memberIdx: rowData.member_idx,
								// 	sunday: sunday,
								// 	cellIndex: cellIndex,
								// 	rowIndex: rowIndex
								// });

								// 출석 상세 열기
								openAttendanceDetail(sunday);
							}
						}
					} catch (error) {
						console.error('직접 터치 이벤트 처리 오류:', error);
					}
				}
			});

		// console.log('Direct touch events bound for mobile (attendance)');
	}

	/**
	 * 초기 컬럼 구성
	 */
	function getInitialColumns() {
		return [
			{
				title: "소그룹",
				dataIndx: "area_name",
				width: 140,
				align: "center",
				frozen: true
			},
			{
				title: "사진",
				dataIndx: "photo",
				width: 70,
				align: "center",
				frozen: true,
				render: function (ui) {
					const photoUrl = ui.cellData || '/assets/images/photo_no.png';
					return '<img src="' + photoUrl + '" alt="사진" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">';
				}
			},
			{
				title: "이름",
				dataIndx: "member_name",
				width: 100,
				align: "center",
				frozen: true
			}
		];
	}

	/**
	 * 역할: 동적 컬럼 생성 시 현재 주 일요일 컬럼에 배경색 적용
	 */
	function createDynamicColumns() {
		let columns = getInitialColumns();

		// 합계 컬럼 추가 (숫자만 표시)
		columns.push({
			title: "합계",
			dataIndx: "total_score",
			width: 80,
			align: "center",
			render: function (ui) {
				const totalScore = parseInt(ui.cellData) || 0;
				const color = totalScore > 0 ? '#0d6efd' : '#6c757d';
				return '<span style="font-weight: bold; color: ' + color + ';">' + totalScore + '</span>';
			}
		});

		// 현재 주의 일요일 날짜 계산
		const today = new Date();
		const currentWeekSunday = getCurrentWeekSunday(today);

		// 일요일 날짜 컬럼들 추가 - 현재 주 하이라이트 적용
		sundayDates.forEach(function (sunday) {
			const date = new Date(sunday);
			const month = date.getMonth() + 1;
			const day = date.getDate();

			// 현재 주 일요일인지 확인
			const isCurrentWeek = sunday === currentWeekSunday;

			columns.push({
				title: month + '/' + day,
				dataIndx: 'week_' + sunday.replace(/-/g, ''),
				width: 70,
				align: "center",
				// 현재 주인 경우 배경색 적용
				style: isCurrentWeek ? 'background-color: #fff3cd;' : '',
				cls: isCurrentWeek ? 'current-week-column' : '',
				render: function (ui) {
					return createAttendanceCellHtml(ui.rowData.member_idx, sunday, isCurrentWeek);
				}
			});
		});

		return columns;
	}


	/**
	 * 역할: 현재 주의 일요일 날짜 계산 함수
	 */
	function getCurrentWeekSunday(today) {
		const currentDate = new Date(today);
		const daysFromSunday = currentDate.getDay(); // 0=일요일, 1=월요일...

		if (daysFromSunday > 0) {
			currentDate.setDate(currentDate.getDate() - daysFromSunday);
		}

		return currentDate.toISOString().split('T')[0]; // YYYY-MM-DD 형식
	}



	/**
	 * 출석셀 HTML 생성 - 실제 포인트 표시
	 */
	function createAttendanceCellHtml(memberIdx, sunday) {
		const weekPoints = getWeekScore(memberIdx, sunday);

		if (weekPoints > 0) {
			return `<div class="attendance-cell-wrapper clickable" data-member-idx="${memberIdx}" data-sunday="${sunday}" style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; cursor: pointer; padding: 4px;">
			<div class="attendance-score" style="background-color: #d1edff; color: #0d6efd; font-weight: bold; padding: 4px 8px; border-radius: 4px; min-width: 24px; text-align: center;">
				${weekPoints}
			</div>
		</div>`;
		} else {
			return `<div class="attendance-cell-wrapper clickable" data-member-idx="${memberIdx}" data-sunday="${sunday}" style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; cursor: pointer; padding: 4px;">
			<div class="attendance-score" style="color: #6c757d;">
				0
			</div>
		</div>`;
		}
	}


	/**
	 * 그리드 셀 클릭 처리 - 로깅 추가
	 */
	function handleGridCellClick(event, ui) {
		console.log('handleGridCellClick triggered:', {
			colIndx: ui.colIndx,
			dataIndx: ui.dataIndx,
			memberIdx: ui.rowData ? ui.rowData.member_idx : 'no rowData',
			eventType: event.type || 'unknown',
			originalEventType: event.originalEvent ? event.originalEvent.type : 'no originalEvent'
		});

		// 출석 데이터가 있는 컬럼인지 확인 (week_로 시작하는 데이터 인덱스)
		if (ui.colIndx >= 4 && ui.dataIndx && ui.dataIndx.startsWith('week_')) {
			// 일요일 날짜 추출 (week_20250105 -> 2025-01-05)
			const sundayDateStr = ui.dataIndx.replace('week_', '');
			const sunday = sundayDateStr.replace(/(\d{4})(\d{2})(\d{2})/, '$1-$2-$3');
			const memberIdx = ui.rowData.member_idx;

			console.log('Week column clicked:', {
				memberIdx: memberIdx,
				sunday: sunday,
				sundayDateStr: sundayDateStr
			});

			if (memberIdx && sunday) {
				// 모든 이벤트 타입에 대해 preventDefault 적용
				if (event.originalEvent) {
					event.originalEvent.preventDefault();
					event.originalEvent.stopPropagation();
				}
				event.preventDefault();
				event.stopPropagation();

				// 즉시 실행하여 이벤트 충돌 방지
				openAttendanceDetail(sunday);
				return;
			}
		}

		// 기존 clickable 요소가 클릭된 경우도 처리 (하위 호환성)
		const $target = $(event.originalEvent ? event.originalEvent.target : event.target);
		const $clickableParent = $target.closest('.clickable');

		if ($clickableParent.length > 0) {
			const memberIdx = $clickableParent.data('member-idx');
			const sunday = $clickableParent.data('sunday');

			console.log('Clickable element clicked:', {
				memberIdx: memberIdx,
				sunday: sunday
			});

			if (memberIdx && sunday) {
				if (event.originalEvent) {
					event.originalEvent.preventDefault();
					event.originalEvent.stopPropagation();
				}
				event.preventDefault();
				event.stopPropagation();

				openAttendanceDetail(sunday);
			}
		}
	}


	/**
	 * 출석 데이터 로드
	 */
	function loadAttendanceData() {
		if (!selectedOrgId) {

			return;
		}



		showGridSpinner();

		// 출석 유형 로드
		loadAttendanceTypes();

		$.ajax({
			url: window.attendancePageData.baseUrl + 'attendance/get_attendance_data',
			method: 'POST',
			data: {
				type: selectedType,
				org_id: selectedOrgId,
				area_idx: selectedAreaIdx,
				year: currentYear
			},
			dataType: 'json',
			success: function (response) {

				handleAttendanceDataResponse(response);
			},
			error: function (xhr, status, error) {
				console.error('출석 데이터 로드 실패:', error);
				hideGridSpinner();
				showToast('출석 데이터를 불러오는데 실패했습니다.', 'error');
			}
		});
	}

	/**
	 * 출석 유형 로드
	 */
	function loadAttendanceTypes() {
		$.ajax({
			url: window.attendancePageData.baseUrl + 'attendance/get_attendance_types',
			method: 'POST',
			data: {org_id: selectedOrgId},
			dataType: 'json',
			success: function (response) {
				if (response.success) {
					attendanceTypes = response.data || [];
					createAttendanceTypeTabs(); // 탭 생성 함수 호출 추가
				}
			},
			error: function (xhr, status, error) {
				console.error('출석유형 로드 실패:', error);
			}
		});
	}

	/**
	 * 출석 데이터 응답 처리
	 */
	function handleAttendanceDataResponse(response) {
		if (!response.success) {
			console.error('출석 데이터 로드 실패:', response.message);
			hideGridSpinner();
			showToast('출석 데이터를 불러오는데 실패했습니다.', 'error');
			return;
		}

		const data = response.data;

		// 전역 변수 업데이트
		sundayDates = data.sunday_dates || [];
		attendanceData = data.attendance_data || {};
		currentMembers = data.members || [];



		// 그리드 업데이트
		updateGrid();
		hideGridSpinner();
	}


	/**
	 * 그리드 업데이트 시 터치 이벤트 재바인딩
	 */
	function updateGrid() {
		if (!attendanceGrid) {
			console.error('그리드가 초기화되지 않았습니다.');
			return;
		}

		try {
			// 새 컬럼 구성
			const newColumns = createDynamicColumns();

			// 그리드 데이터 준비
			const gridData = prepareGridData();

			// 그리드 업데이트
			attendanceGrid.pqGrid("option", "colModel", newColumns);
			attendanceGrid.pqGrid("option", "dataModel.data", gridData);
			attendanceGrid.pqGrid("refreshDataAndView");

			// 그리드 업데이트 후 터치 이벤트 재바인딩
			setTimeout(function() {
				bindDirectTouchEvents();
			}, 100);

		} catch (error) {
			console.error('그리드 업데이트 실패:', error);
			showToast('그리드 업데이트에 실패했습니다.', 'error');
		}
	}


	

	/**
	 * 그리드 데이터 준비 - 실제 포인트 합계 계산
	 */
	function prepareGridData() {
		return currentMembers.map(function (member) {
			// 회원별 총 포인트 계산 (각 주별 포인트의 합)
			let totalPoints = 0;

			if (attendanceData[member.member_idx]) {
				sundayDates.forEach(function (sunday) {
					const weekPoints = getWeekScore(member.member_idx, sunday);
					totalPoints += weekPoints;
				});
			}

			// 사진 URL 처리
			let photoUrl = '/assets/images/photo_no.png';
			if (member.photo) {
				if (member.photo.indexOf('/uploads/') === -1) {
					photoUrl = '/uploads/member_photos/' + selectedOrgId + '/' + member.photo;
				} else {
					photoUrl = member.photo;
				}
			}

			// 소그룹명 처리
			const areaName = member.area_name || '미분류';

			return {
				member_idx: member.member_idx,
				member_name: member.member_name,
				area_name: areaName,
				photo: photoUrl,
				total_score: totalPoints // 실제 포인트 합계
			};
		});
	}

	/**
	 * 특정 회원의 특정 주 포인트 계산 - 서버에서 계산된 실제 포인트 반환
	 */
	function getWeekScore(memberIdx, sunday) {
		if (!attendanceData[memberIdx] || !attendanceData[memberIdx][sunday]) {
			return 0;
		}

		const weekData = attendanceData[memberIdx][sunday];

		if (selectedAttTypeIdx === 'all') {
			// 전체: 모든 출석유형의 점수 합계 반환
			return weekData.total_score || 0;
		} else {
			// 특정 출석유형만: 해당 출석유형의 점수만 반환
			if (weekData.attendance_types && weekData.attendance_types[selectedAttTypeIdx]) {
				return weekData.attendance_types[selectedAttTypeIdx].total_points || 0;
			}
			return 0;
		}
	}

	/**
	 * 역할: 전역 변수에 attendance detail grid 추가
	 */
	let attendanceDetailGrid = null;
	let attendanceDetailGridData = [];

	/**
	 * 역할: 출석 상세 정보 열기 - pqgrid 방식으로 변경
	 */
	function openAttendanceDetail(sunday) {
		const date = new Date(sunday);
		const formattedDate = date.getFullYear() + '.' + (date.getMonth() + 1) + '.' + date.getDate();

		$('#attendanceOffcanvasLabel').text(formattedDate + ' 출석상세');

		// offcanvas body 초기화
		$('#attendanceDetailContent').html('<div id="attendanceDetailGrid"></div>');

		// offcanvas 표시
		const offcanvas = new bootstrap.Offcanvas($('#attendanceOffcanvas')[0]);
		offcanvas.show();

		// 출석상세 데이터 로드
		loadAttendanceDetail(sunday);
	}

	/**
	 * 역할: 출석상세 데이터 로드 - 기존 유지
	 */
	function loadAttendanceDetail(sunday) {
		const memberIndices = currentMembers.map(member => member.member_idx);

		$.ajax({
			url: window.attendancePageData.baseUrl + 'attendance/get_week_attendance_detail',
			method: 'POST',
			data: {
				org_id: selectedOrgId,
				sunday_date: sunday,
				member_indices: memberIndices
			},
			dataType: 'json',
			success: function (response) {
				if (response.success) {
					renderAttendanceDetailGrid(response.data, sunday);
				} else {
					$('#attendanceDetailContent').html('<div class="alert alert-danger">출석상세 정보를 불러올 수 없습니다.</div>');
				}
			},
			error: function (xhr, status, error) {
				console.error('출석상세 데이터 로드 실패:', error);
				$('#attendanceDetailContent').html('<div class="alert alert-danger">출석상세 정보를 불러오는 중 오류가 발생했습니다.</div>');
			}
		});
	}


	/**
	 * 역할: pqgrid 기반 출석상세 화면 렌더링
	 */
	function renderAttendanceDetailGrid(data, sunday) {
		const {attendance_types, attendance_records, members_info, memo_records = {}} = data;

		// 그리드 초기화
		initializeAttendanceDetailGrid(attendance_types);

		// 그리드 데이터 준비
		attendanceDetailGridData = prepareAttendanceDetailGridData(members_info, attendance_types, attendance_records, memo_records);

		// 그리드에 데이터 설정
		if (attendanceDetailGrid) {
			attendanceDetailGrid.pqGrid("option", "dataModel.data", attendanceDetailGridData);
			attendanceDetailGrid.pqGrid("refreshDataAndView");
		}
	}




	/**
	 * 출석상세 pqgrid 초기화 - 이벤트 바인딩 개선
	 */
	function initializeAttendanceDetailGrid(attendanceTypes) {
		if (attendanceDetailGrid) {
			attendanceDetailGrid.pqGrid("destroy");
			attendanceDetailGrid = null;
		}

		let colModel = [
			{
				title: "이름",
				dataIndx: "member_name",
				width: 100,
				minWidth: 80,
				maxWidth: 120,
				editable: false,
				align: "center",
				frozen: true,
				resizable: false
			},
			{
				title: "한줄메모",
				dataIndx: "memo_content",
				width: 150,
				minWidth: 100,
				maxWidth: 250,
				editable: true,
				align: "left",
				resizable: true,
				editor: {
					type: "textbox",
					attr: "placeholder='메모 입력...'"
				}
			},
			{
				title: "소계",
				dataIndx: "week_total",
				width: 60,
				minWidth: 50,
				maxWidth: 80,
				editable: false,
				align: "center",
				resizable: false,
				render: function(ui) {
					const totalScore = parseInt(ui.cellData) || 0;
					const color = totalScore > 0 ? '#0d6efd' : '#6c757d';
					return '<span style="font-weight: bold; color: ' + color + ';">' + totalScore + '</span>';
				}
			}
		];

		// 출석유형별 컬럼 동적 추가
		if (attendanceTypes && attendanceTypes.length > 0) {
			attendanceTypes.forEach(function(attType) {
				const inputType = attType.att_type_input || 'check';
				const typePoint = Number(attType.att_type_point) || 10;

				const columnConfig = {
					title: attType.att_type_nickname,
					dataIndx: "att_type_" + attType.att_type_idx,
					width: 50,
					minWidth: 20,
					maxWidth: 80,
					align: "center",
					resizable: true
				};

				if (inputType === 'check') {
					columnConfig.editable = false;
					columnConfig.render = function(ui) {
						const isChecked = ui.cellData === true || ui.cellData === 'Y' || ui.cellData === 1;
						const checkedAttr = isChecked ? 'checked' : '';
						const checkboxId = 'checkbox_' + ui.rowIndx + '_' + attType.att_type_idx;

						return '<div style="text-align: center; padding: 8px;">' +
							'<input type="checkbox" ' + checkedAttr + ' ' +
							'id="' + checkboxId + '" ' +
							'class="attendance-detail-checkbox" ' +
							'data-row-indx="' + ui.rowIndx + '" ' +
							'data-att-type-idx="' + attType.att_type_idx + '" ' +
							'data-att-type-score="' + typePoint + '" ' +
							'data-member-idx="' + ui.rowData.member_idx + '" ' +
							'style="transform: scale(1.3); cursor: pointer; pointer-events: all;">' +
							'</div>';
					};
				} else {
					columnConfig.editable = true;
					columnConfig.editor = {
						type: "textbox",
						attr: "type='number' min='0' max='999' style='text-align: center;'"
					};
					columnConfig.render = function(ui) {
						const value = parseInt(ui.cellData) || 0;
						return '<input type="number" class="form-control form-control-sm attendance-textbox text-center" ' +
							'value="' + value + '" min="0" max="999" ' +
							'data-att-type-idx="' + attType.att_type_idx + '" ' +
							'data-att-type-default-point="' + typePoint + '" ' +
							'data-member-idx="' + ui.rowData.member_idx + '" ' +
							'style="width: 100%; border: none; background: transparent;">';
					};
				}

				colModel.push(columnConfig);
			});
		}

		const gridOptions = {
			width: "100%",
			dataModel: {
				data: []
			},
			colModel: colModel,
			selectionModel: {
				type: 'cell'
			},
			strNoRows: '출석 정보가 없습니다',
			scrollModel: {
				autoFit: false,
				horizontal: true,
				vertical: true
			},
			freezeCols: 1,
			numberCell: { show: false },
			title: false,
			resizable: false,
			sortable: false,
			wrap: false,
			columnBorders: true,
			editable: true,
			editModel: {
				clicksToEdit: 2,
				saveKey: $.ui.keyCode.ENTER
			},
			// 그리드 완성 후 이벤트 바인딩
			complete: function() {
				// 여러 번 호출될 수 있으므로 지연 후 한 번만 바인딩
				setTimeout(function() {
					bindAttendanceDetailEvents();
				}, 200);
			}
		};

		attendanceDetailGrid = $("#attendanceDetailGrid").pqGrid(gridOptions);
	}


	/**
	 * 역할: 출석상세 그리드 데이터 준비
	 */
	function prepareAttendanceDetailGridData(members_info, attendance_types, attendance_records, memo_records) {
		const gridData = [];

		if (!members_info || members_info.length === 0) {
			return gridData;
		}

		members_info.forEach(function(member) {
			const memberIdx = member.member_idx;
			const memberMemo = memo_records[memberIdx] || {};

			const rowData = {
				member_idx: memberIdx,
				member_name: member.member_name,
				memo_content: memberMemo.memo_content || '',
				att_idx: memberMemo.att_idx || null,
				week_total: 0
			};

			let weekTotalScore = 0;

			// 출석유형별 데이터 설정
			if (attendance_types && attendance_types.length > 0) {
				attendance_types.forEach(function(attType) {
					const dataIndx = "att_type_" + attType.att_type_idx;

					// 해당 회원의 출석기록 필터링
					const memberAttendanceRecords = attendance_records.filter(record =>
						record.member_idx == memberIdx && record.att_type_idx == attType.att_type_idx
					);

					if (attType.att_type_input === 'check') {
						const isChecked = memberAttendanceRecords.length > 0;
						rowData[dataIndx] = isChecked;

						if (isChecked) {
							const scoreValue = Number(memberAttendanceRecords[0].att_value) || Number(attType.att_type_point) || 10;
							weekTotalScore += scoreValue;
						}
					} else {
						const currentValue = memberAttendanceRecords.length > 0 ?
							(Number(memberAttendanceRecords[0].att_value) || 0) : 0;
						rowData[dataIndx] = currentValue;
						weekTotalScore += currentValue;
					}
				});
			}

			rowData.week_total = weekTotalScore;
			gridData.push(rowData);
		});

		return gridData;
	}

	/**
	 * 출석상세 그리드 이벤트 바인딩 - PC/모바일 통합 개선
	 */
	function bindAttendanceDetailEvents() {
		// 기존 이벤트 완전히 제거
		$(document).off('change.attendance-detail click.attendance-detail touchend.attendance-detail');
		$(document).off('input.attendance-detail change.attendance-detail');

		// 체크박스 이벤트 - PC/모바일 통합 처리
		$(document).on('change.attendance-detail click.attendance-detail touchend.attendance-detail', '.attendance-detail-checkbox', function(e) {
			e.stopPropagation();

			const $checkbox = $(this);
			const rowIndx = parseInt($checkbox.data('row-indx'));
			const memberIdx = $checkbox.data('member-idx');
			const attTypeIdx = $checkbox.data('att-type-idx');
			const attTypeScore = parseInt($checkbox.data('att-type-score')) || 10;

			// PC에서는 change 이벤트, 모바일에서는 touchend 이벤트 우선 처리
			let shouldProcess = false;

			if (e.type === 'change') {
				shouldProcess = true;
			} else if (e.type === 'touchend') {
				e.preventDefault();
				shouldProcess = true;
				// 터치 이벤트의 경우 체크박스 상태 토글
				setTimeout(function() {
					const checked = !$checkbox.prop('checked');
					$checkbox.prop('checked', checked);
				}, 10);
			} else if (e.type === 'click' && !e.originalEvent?.isTrusted) {
				// 프로그래매틱 클릭은 무시
				return;
			}

			if (shouldProcess) {
				const checked = $checkbox.is(':checked');
				updateGridCellData(rowIndx, attTypeIdx, checked);
				recalculateGridRowTotal(rowIndx);
			}
		});

		// 텍스트박스 이벤트
		$(document).on('input.attendance-detail change.attendance-detail', '.attendance-textbox', function() {
			const $textbox = $(this);
			const value = parseInt($textbox.val()) || 0;
			const rowIndx = $textbox.closest('tr').index();
			const attTypeIdx = $textbox.data('att-type-idx');

			updateGridCellData(rowIndx, attTypeIdx, value);
			recalculateGridRowTotal(rowIndx);
		});

		// 메모 변경 이벤트
		$(document).on('input.attendance-detail change.attendance-detail', 'input[data-indx="memo_content"]', function() {
			$(this).data('changed', true);
		});
	}


	/**
	 * 역할: 그리드 셀 데이터 업데이트
	 */
	function updateGridCellData(rowIndx, attTypeIdx, value) {
		if (!attendanceDetailGrid) return;

		try {
			const gridData = attendanceDetailGrid.pqGrid("option", "dataModel.data");
			if (gridData && gridData[rowIndx]) {
				gridData[rowIndx]["att_type_" + attTypeIdx] = value;
				attendanceDetailGrid.pqGrid("option", "dataModel.data", gridData);
			}
		} catch (error) {
			console.error('그리드 데이터 업데이트 실패:', error);
		}
	}

	/**
	 * 역할: 그리드 행별 소계 재계산
	 */
	function recalculateGridRowTotal(rowIndx) {
		if (!attendanceDetailGrid) return;

		try {
			const gridData = attendanceDetailGrid.pqGrid("option", "dataModel.data");
			if (!gridData || !gridData[rowIndx]) return;

			let total = 0;
			const rowData = gridData[rowIndx];

			// 출석유형별 점수 합계 계산
			attendanceTypes.forEach(function(attType) {
				const dataIndx = "att_type_" + attType.att_type_idx;
				const cellValue = rowData[dataIndx];
				const typePoint = Number(attType.att_type_point) || 10;

				if (attType.att_type_input === 'check') {
					if (cellValue === true || cellValue === 'Y' || cellValue === 1) {
						total += typePoint;
					}
				} else {
					total += (Number(cellValue) || 0);
				}
			});

			// 소계 업데이트
			rowData.week_total = total;
			attendanceDetailGrid.pqGrid("option", "dataModel.data", gridData);
			attendanceDetailGrid.pqGrid("refreshRow", {rowIndx: rowIndx});

		} catch (error) {
			console.error('소계 재계산 실패:', error);
		}
	}



	$(document).off('click', '#btnSaveAttendance').on('click', '#btnSaveAttendance', function() {

		saveAttendanceDetail();
	});



	/**
	 * 역할: 출석 정보 저장 - pqgrid 데이터 기반으로 수정
	 */
	function saveAttendanceDetail() {
		if (!attendanceDetailGrid) {
			showToast('출석 데이터를 찾을 수 없습니다.', 'error');
			return;
		}

		// 제목에서 날짜 추출
		const titleText = $('#attendanceOffcanvasLabel').text();
		const dateMatch = titleText.match(/(\d{4})\.(\d{1,2})\.(\d{1,2})/);

		if (!dateMatch) {
			showToast('날짜 정보를 찾을 수 없습니다.', 'error');
			return;
		}

		const year = dateMatch[1];
		const month = dateMatch[2].padStart(2, '0');
		const day = dateMatch[3].padStart(2, '0');
		const attendanceDate = `${year}-${month}-${day}`;

		// 그리드에서 데이터 수집
		const gridData = attendanceDetailGrid.pqGrid("option", "dataModel.data");
		const attendanceData = [];
		const memoData = [];

		gridData.forEach(function(row) {
			const memberIdx = row.member_idx;
			if (!memberIdx) return;

			const memberAttendanceTypes = [];

			// 출석유형별 데이터 수집
			attendanceTypes.forEach(function(attType) {
				const dataIndx = "att_type_" + attType.att_type_idx;
				const cellValue = row[dataIndx];

				if (attType.att_type_input === 'check') {
					if (cellValue === true || cellValue === 'Y' || cellValue === 1) {
						memberAttendanceTypes.push({
							att_type_idx: attType.att_type_idx,
							att_value: Number(attType.att_type_point) || 10,
							input_type: 'check'
						});
					}
				} else {
					const value = Number(cellValue) || 0;
					if (value > 0) {
						memberAttendanceTypes.push({
							att_type_idx: attType.att_type_idx,
							att_value: value,
							input_type: 'text'
						});
					}
				}
			});

			// 출석 데이터 추가
			if (memberAttendanceTypes.length > 0) {
				attendanceData.push({
					member_idx: memberIdx,
					att_date: attendanceDate,
					attendance_types: memberAttendanceTypes
				});
			}

			// 메모 데이터 수집
			const memoContent = row.memo_content ? row.memo_content.trim() : '';
			const attIdx = row.att_idx || null;

			memoData.push({
				member_idx: memberIdx,
				memo_content: memoContent,
				att_idx: attIdx
			});
		});

		// 저장 버튼 비활성화
		const $saveBtn = $('#btnSaveAttendance');
		const originalText = $saveBtn.text();
		$saveBtn.prop('disabled', true).text('저장중...');

		// AJAX 요청
		$.ajax({
			url: window.attendancePageData.baseUrl + 'attendance/save_attendance_with_memo',
			method: 'POST',
			data: {
				org_id: selectedOrgId,
				attendance_data: JSON.stringify(attendanceData),
				memo_data: JSON.stringify(memoData),
				att_date: attendanceDate,
				year: currentYear
			},
			dataType: 'json',
			success: function(response) {
				if (response.success) {
					showToast('출석 및 메모가 저장되었습니다.', 'success');

					// offcanvas 닫기
					const offcanvas = bootstrap.Offcanvas.getInstance($('#attendanceOffcanvas')[0]);
					if (offcanvas) {
						offcanvas.hide();
					}

					// 그리드 데이터 새로고침
					setTimeout(function() {
						loadAttendanceData();
					}, 1000);

				} else {
					showToast(response.message || '출석 및 메모 저장에 실패했습니다.', 'error');
				}
			},
			error: function(xhr, status, error) {
				console.error('AJAX 오류:', xhr.responseText);
				showToast('출석 및 메모 저장 중 오류가 발생했습니다.', 'error');
			},
			complete: function() {
				$saveBtn.prop('disabled', false).text(originalText);
			}
		});
	}

	/**
	 * 역할: HTML 이스케이프 처리 함수
	 */
	function escapeHtml(text) {
		const div = document.createElement('div');
		div.textContent = text;
		return div.innerHTML;
	}

	/**
	 * 그리드 필터링
	 */
	function filterGrid(searchText) {
		if (!attendanceGrid || !searchText) {
			clearGridFilter();
			return;
		}

		try {
			const allData = attendanceGrid.pqGrid("option", "dataModel.data");

			if (!window.originalAttendanceData) {
				window.originalAttendanceData = JSON.parse(JSON.stringify(allData));
			}

			const filteredData = window.originalAttendanceData.filter(function (member) {
				const memberName = (member.member_name || '').toLowerCase();
				return memberName.includes(searchText.toLowerCase());
			});

			attendanceGrid.pqGrid("option", "dataModel.data", filteredData);
			attendanceGrid.pqGrid("refreshDataAndView");

			showToast(`검색결과: ${filteredData.length}명`, 'info');
		} catch (error) {
			console.error('필터링 중 오류:', error);
			showToast('검색 중 오류가 발생했습니다.', 'error');
		}
	}

	/**
	 * 그리드 필터 해제
	 */
	function clearGridFilter() {
		if (!attendanceGrid || !window.originalAttendanceData) {
			return;
		}

		try {
			attendanceGrid.pqGrid("option", "dataModel.data", window.originalAttendanceData);
			attendanceGrid.pqGrid("refreshDataAndView");
		} catch (error) {
			console.error('필터 해제 중 오류:', error);
		}
	}

	/**
	 * 검색 상태 초기화
	 */
	function resetSearch() {
		const searchInput = $('.card-header input[type="text"]');
		searchInput.val('');
		clearGridFilter();
		window.originalAttendanceData = null;
	}

	/**
	 * 선택된 조직명 업데이트
	 */
	function updateSelectedOrgName(title, type) {
		const orgNameElement = $('#selectedOrgName');
		if (!orgNameElement.length) return;

		let displayText = '';
		switch (type) {
			case 'org':
				displayText = `${title} - 전체 회원`;
				break;
			case 'area':
				displayText = `${title} 소그룹`;
				break;
			case 'unassigned':
				displayText = `미분류 회원`;
				break;
		}

		orgNameElement.html(displayText);
	}


	/**
	 * 역할: offcanvas 정리 이벤트에 그리드 정리 추가
	 */
	function setupCleanupEvents() {
		$(window).off('beforeunload.attendance').on('beforeunload.attendance', function () {
			destroySplitJS();
			cleanupAttendanceDetailGrid();
		});

		$('#attendanceOffcanvas').off('hidden.bs.offcanvas.attendance').on('hidden.bs.offcanvas.attendance', function () {
			cleanupAttendanceDetailGrid();
			$('#attendanceDetailContent').html(getLoadingHtml());
		});
	}



	/**
	 * 역할: 출석상세 그리드 정리
	 */
	function cleanupAttendanceDetailGrid() {
		if (attendanceDetailGrid) {
			try {
				attendanceDetailGrid.pqGrid("destroy");
				attendanceDetailGrid = null;
				attendanceDetailGridData = [];
			} catch (error) {
				console.error('출석상세 그리드 정리 실패:', error);
			}
		}
	}

	/**
	 * Split.js 정리
	 */
	function destroySplitJS() {
		if (splitInstance) {
			try {
				splitInstance.destroy();
				splitInstance = null;
			} catch (error) {
				console.error('Split.js 정리 실패:', error);
			}
		}
	}

	/**
	 * 로딩 HTML 반환
	 */
	function getLoadingHtml() {
		return '<div class="text-center py-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">로딩중...</span></div><div class="mt-2 text-muted">출석 정보를 불러오는 중...</div></div>';
	}

	/**
	 * 디바운스 함수
	 *
	 * 디바운스는 연속적으로 발생하는 이벤트를 제어하는 기법입니다.
	 * 예: 윈도우 리사이즈 이벤트가 1초에 수십번 발생할 때, 마지막 이벤트 후 일정 시간(wait)이 지나면 한 번만 실행
	 *
	 * @param {Function} func - 실행할 함수
	 * @param {number} wait - 대기 시간 (밀리초)
	 * @returns {Function} - 디바운스가 적용된 함수
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
	 * 출석상태 변경 이벤트 바인딩 - 숫자 처리 확실히 하기
	 */
	function bindAttendanceChangeEvents() {
		// 체크박스 변경 이벤트 - 이벤트 위임 사용
		$(document).off('change touchend', '.attendance-checkbox').on('change touchend', '.attendance-checkbox', function(event) {
			// 터치 이벤트의 경우 중복 실행 방지
			if (event.type === 'touchend') {
				event.preventDefault();
				// 체크박스 상태 토글
				const $checkbox = $(this);
				setTimeout(function() {
					$checkbox.prop('checked', !$checkbox.prop('checked'));
					const row = $checkbox.closest('tr');
					recalculateWeekTotal(row);
				}, 10);
			} else if (event.type === 'change') {
				const row = $(this).closest('tr');
				recalculateWeekTotal(row);
			}
		});

		// 텍스트박스 변경 이벤트 - 이벤트 위임 사용
		$(document).off('input', '.attendance-textbox').on('input', '.attendance-textbox', function() {
			const row = $(this).closest('tr');
			recalculateWeekTotal(row);
		});
	}


	/**
	 * 역할: recalculateWeekTotal 함수 수정 - 숫자로 정확히 표시
	 */
	function recalculateWeekTotal(row) {
		let weekTotal = 0;

		// 체크된 체크박스들의 점수 합산 - Number() 사용
		row.find('.attendance-checkbox:checked').each(function() {
			const score = Number($(this).data('att-type-score')) || 0;

			weekTotal += score;
		});

		// 텍스트박스 값들의 합산 - Number() 사용
		row.find('.attendance-textbox').each(function() {
			const value = Number($(this).val()) || 0;

			weekTotal += value;
		});



		// 소계 업데이트 - 숫자만 표시
		row.find('.week-total').text(weekTotal);
	}



	// ===== 스피너 관련 함수들 =====

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
	 * 모든 스피너 표시 (초기 로딩 시)
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
	 * 출석 통계 재계산
	 */
	function recalculateAttendanceStats() {
		if (!selectedOrgId) {
			showToast('조직을 먼저 선택해주세요.', 'warning');
			return;
		}

		// 확인 모달 표시
		if (!confirm('선택된 그룹의 ' + currentYear + '년도 출석 통계를 재계산하시겠습니까?\n\n기존 통계가 새로 계산됩니다.')) {
			return;
		}

		// 버튼 비활성화 및 로딩 표시
		const $btn = $('#btnRecalculateStats');
		const originalHtml = $btn.html();
		$btn.prop('disabled', true)
			.html('<i class="bi bi-arrow-repeat spin"></i> 계산중...');

		// 그리드 스피너도 표시
		showGridSpinner();

		$.ajax({
			url: window.attendancePageData.baseUrl + 'attendance/rebuild_stats',
			method: 'POST',
			data: {
				org_id: selectedOrgId,
				year: currentYear,
				type: selectedType,
				area_idx: selectedAreaIdx
			},
			dataType: 'json',
			success: function (response) {


				if (response.success) {
					showToast('출석 통계가 재계산되었습니다.', 'success');

					// 데이터 새로고침
					setTimeout(function() {
						loadAttendanceData();
					}, 1000);
				} else {
					showToast(response.message || '통계 재계산에 실패했습니다.', 'error');
					hideGridSpinner();
				}
			},
			error: function (xhr, status, error) {
				console.error('통계 재계산 실패:', error);
				showToast('통계 재계산 중 오류가 발생했습니다.', 'error');
				hideGridSpinner();
			},
			complete: function() {
				// 버튼 복원
				$btn.prop('disabled', false).html(originalHtml);
			}
		});
	}

	/**
	 * 역할: 현재 표시된 출석 목록을 엑셀 파일로 다운로드
	 */
	function downloadAttendanceExcel() {
		if (!attendanceGrid || !currentMembers || currentMembers.length === 0) {
			showToast('다운로드할 출석 데이터가 없습니다.', 'warning');
			return;
		}

		if (!selectedOrgId) {
			showToast('조직을 먼저 선택해주세요.', 'warning');
			return;
		}

		// 버튼 비활성화 및 로딩 표시
		const $btn = $('#btnExcelDownload');
		const originalHtml = $btn.html();
		$btn.prop('disabled', true).html('<i class="bi bi-hourglass-split"></i> 생성중...');

		try {
			// 엑셀 데이터 준비
			const excelData = prepareExcelData();

			// 엑셀 파일 생성 및 다운로드
			createAndDownloadExcel(excelData);

			showToast('엑셀 파일이 다운로드되었습니다.', 'success');

		} catch (error) {
			console.error('엑셀 다운로드 오류:', error);
			showToast('엑셀 파일 생성 중 오류가 발생했습니다.', 'error');
		} finally {
			// 버튼 복원
			$btn.prop('disabled', false).html(originalHtml);
		}
	}


	/**
	 * 역할: 그리드 데이터를 엑셀 형식으로 변환
	 */
	function prepareExcelData() {
		const gridData = attendanceGrid.pqGrid("option", "dataModel.data");
		const colModel = attendanceGrid.pqGrid("option", "colModel");

		// 헤더 생성
		const headers = [];
		colModel.forEach(function(col) {
			if (col.dataIndx !== 'photo') { // 사진 컬럼 제외
				headers.push(col.title);
			}
		});

		// 데이터 행 생성
		const rows = [];
		gridData.forEach(function(row) {
			const dataRow = [];
			colModel.forEach(function(col) {
				if (col.dataIndx !== 'photo') { // 사진 컬럼 제외
					let cellValue = row[col.dataIndx];

					// 데이터 타입별 처리
					if (col.dataIndx === 'total_score') {
						cellValue = parseInt(cellValue) || 0;
					} else if (col.dataIndx.startsWith('week_')) {
						// 주별 출석 점수
						const sunday = col.dataIndx.replace('week_', '').replace(/(\d{4})(\d{2})(\d{2})/, '$1-$2-$3');
						cellValue = getWeekScore(row.member_idx, sunday);
					} else {
						cellValue = cellValue || '';
					}

					dataRow.push(cellValue);
				}
			});
			rows.push(dataRow);
		});

		return {
			headers: headers,
			data: rows
		};
	}

	/**
	 * 역할: XLSX 라이브러리를 사용하여 엑셀 파일 생성 및 다운로드
	 */
	function createAndDownloadExcel(excelData) {
		// SheetJS가 로드되어 있는지 확인
		if (typeof XLSX === 'undefined') {
			console.error('XLSX 라이브러리가 로드되지 않았습니다.');
			showToast('엑셀 라이브러리를 불러올 수 없습니다.', 'error');
			return;
		}

		// 워크시트 데이터 준비
		const wsData = [excelData.headers, ...excelData.data];

		// 워크시트 생성
		const ws = XLSX.utils.aoa_to_sheet(wsData);

		// 컬럼 너비 설정
		const colWidths = [];
		excelData.headers.forEach(function(header, index) {
			if (header === '이름') {
				colWidths.push({wch: 12});
			} else if (header === '소그룹') {
				colWidths.push({wch: 15});
			} else if (header === '합계') {
				colWidths.push({wch: 8});
			} else {
				colWidths.push({wch: 8}); // 주별 날짜 컬럼
			}
		});
		ws['!cols'] = colWidths;

		// 헤더 스타일 설정
		const headerRange = XLSX.utils.decode_range(ws['!ref']);
		for (let C = headerRange.s.c; C <= headerRange.e.c; ++C) {
			const headerCell = XLSX.utils.encode_cell({r: 0, c: C});
			if (!ws[headerCell]) continue;

			ws[headerCell].s = {
				font: { bold: true, color: { rgb: "FFFFFF" } },
				fill: { fgColor: { rgb: "4472C4" } },
				alignment: { horizontal: "center", vertical: "center" },
				border: {
					top: { style: "thin", color: { rgb: "000000" } },
					bottom: { style: "thin", color: { rgb: "000000" } },
					left: { style: "thin", color: { rgb: "000000" } },
					right: { style: "thin", color: { rgb: "000000" } }
				}
			};
		}

		// 워크북 생성
		const wb = XLSX.utils.book_new();
		XLSX.utils.book_append_sheet(wb, ws, "출석현황");

		// 파일명 생성
		const orgNameElement = $('#selectedOrgName');
		let orgName = '출석현황';
		if (orgNameElement.length > 0) {
			const orgText = orgNameElement.text().trim();
			if (orgText && orgText !== '조직을 선택해주세요') {
				orgName = orgText.replace(/[<>:"/\\|?*]/g, ''); // 파일명에 사용할 수 없는 문자 제거
			}
		}

		// 현재 선택된 출석유형 정보 추가
		let attTypeText = '';
		if (selectedAttTypeIdx === 'all') {
			attTypeText = '_전체';
		} else {
			const selectedAttType = attendanceTypes.find(type => type.att_type_idx == selectedAttTypeIdx);
			if (selectedAttType) {
				attTypeText = '_' + (selectedAttType.att_type_nickname || selectedAttType.att_type_name);
			}
		}

		const fileName = `${orgName}${attTypeText}_${currentYear}년_${getCurrentDateString()}.xlsx`;

		// 파일 다운로드
		XLSX.writeFile(wb, fileName);
	}

	/**
	 * 역할: YYYYMMDD 형식의 날짜 문자열 반환
	 */
	function getCurrentDateString() {
		const now = new Date();
		const year = now.getFullYear();
		const month = String(now.getMonth() + 1).padStart(2, '0');
		const day = String(now.getDate()).padStart(2, '0');
		return `${year}${month}${day}`;
	}

});
