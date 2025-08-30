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

	// ===== 디버깅 및 초기화 =====
	console.log('attendance.js 로드됨');
	console.log('현재 년도:', currentYear);

	// 초기화 시도
	setTimeout(function () {
		initializePage();
	}, 500);

	/**
	 * 페이지 초기화 메인 함수
	 */
	function initializePage() {
		console.log('출석관리 페이지 초기화 시작');

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
			console.log('출석관리 페이지 초기화 완료');
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
				sizes: [25, 75],
				minSize: [250, 400],
				gutterSize: 7,
				cursor: 'col-resize',
				direction: 'horizontal',
				onDragEnd: function (sizes) {
					setTimeout(function () {
						if (attendanceGrid) {
							try {
								attendanceGrid.pqGrid("refresh");
							} catch (error) {
								console.error('그리드 리프레시 실패:', error);
							}
						}
					}, 100);
				}
			});

			// 저장된 크기 복원
			const savedSizes = localStorage.getItem('attendance-split-sizes');
			if (savedSizes) {
				try {
					const sizes = JSON.parse(savedSizes);
					splitInstance.setSizes(sizes);
				} catch (error) {
					console.error('저장된 크기 복원 실패:', error);
				}
			}

			console.log('Split.js 초기화 완료');
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

		// 출석 등록 버튼
		$('#btnAddAttendance').off('click').on('click', function () {
			showToast('출석 등록 기능은 추후 구현 예정입니다.', 'info');
		});

		// 출석 저장 버튼 - saveAttendanceDetail 함수로 수정
		$('#btnSaveAttendance').off('click').on('click', function () {
			saveAttendanceDetail();
		});

		// 검색 기능
		bindSearchEvents();

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
		console.log('Fancytree 초기화 시작');
		showTreeSpinner();

		$.ajax({
			url: window.attendancePageData.baseUrl + 'attendance/get_org_tree',
			method: 'POST',
			dataType: 'json',
			success: function (treeData) {
				console.log('트리 데이터 로드됨:', treeData);

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
				console.log('노드 선택됨:', node.title);
				handleTreeNodeActivate(node);
			},
			expand: function (event, data) {
				console.log('노드 확장:', data.node.title);
			},
			collapse: function (event, data) {
				console.log('노드 축소:', data.node.title);
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
			console.log('선택된 그룹 저장됨:', selectedGroup);
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
				console.log('저장된 그룹 선택 복원됨:', groupData);
			} else {
				console.log('저장된 그룹을 찾을 수 없음, 첫 번째 조직 선택');
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
	 * ParamQuery Grid 초기화 - 세로 hover 적용
	 */
	function initializeParamQuery() {
		console.log('ParamQuery Grid 초기화');
		showGridSpinner();

		try {
			attendanceGrid = $("#attendanceGrid").pqGrid({
				width: "100%",
				height: "100%",
				dataModel: {data: []},
				colModel: getInitialColumns(),
				selectionModel: {type: '', mode: 'single'},
				scrollModel: {autoFit: false, horizontal: true, vertical: true},
				freezeCols: 3,
				numberCell: {show: false},
				title: false,
				resizable: true,
				sortable: false,
				hoverMode: 'cell',  // 'row' → 'column'으로 변경
				wrap: false,
				columnBorders: true,
				cellClick: function (event, ui) {
					handleGridCellClick(event, ui);
				},
				// 마우스오버 시 커서 변경을 위한 이벤트 추가
				cellMouseEnter: function(event, ui) {
					// 출석 데이터 컬럼인지 확인
					if (ui.colIndx >= 4 && ui.dataIndx && ui.dataIndx.startsWith('week_')) {
						$(ui.$cell).css('cursor', 'pointer');
					}
				},
				cellMouseLeave: function(event, ui) {
					$(ui.$cell).css('cursor', 'default');
				}
			});

			hideGridSpinner();
			console.log('ParamQuery Grid 초기화 완료');
		} catch (error) {
			console.error('ParamQuery Grid 초기화 실패:', error);
			hideGridSpinner();
			showToast('그리드 초기화에 실패했습니다.', 'error');
		}
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
	 * 동적 컬럼 생성 - 셀 클릭 개선 적용
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

		// 일요일 날짜 컬럼들 추가 - 전체 셀 클릭 가능하도록 수정
		sundayDates.forEach(function (sunday) {
			const date = new Date(sunday);
			const month = date.getMonth() + 1;
			const day = date.getDate();

			columns.push({
				title: month + '/' + day,
				dataIndx: 'week_' + sunday.replace(/-/g, ''),
				width: 70,
				align: "center",
				render: function (ui) {
					return createAttendanceCellHtml(ui.rowData.member_idx, sunday);
				}
			});
		});

		return columns;
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
	 * 그리드 셀 클릭 처리 개선 - 전체 셀 영역에서 이벤트 감지
	 */
	function handleGridCellClick(event, ui) {
		console.log('그리드 셀 클릭:', ui);

		// 출석 데이터가 있는 컬럼인지 확인 (week_로 시작하는 데이터 인덱스)
		if (ui.colIndx >= 4 && ui.dataIndx && ui.dataIndx.startsWith('week_')) {
			// 일요일 날짜 추출 (week_20250105 -> 2025-01-05)
			const sundayDateStr = ui.dataIndx.replace('week_', '');
			const sunday = sundayDateStr.replace(/(\d{4})(\d{2})(\d{2})/, '$1-$2-$3');

			const memberIdx = ui.rowData.member_idx;

			console.log('출석상세 열기:', {
				member_idx: memberIdx,
				sunday: sunday,
				member_name: ui.rowData.member_name
			});

			if (memberIdx && sunday) {
				openAttendanceDetail(sunday);
			}
			return;
		}

		// 기존 clickable 요소가 클릭된 경우도 처리 (하위 호환성)
		const $target = $(event.originalEvent.target);
		const $clickableParent = $target.closest('.clickable');

		if ($clickableParent.length > 0) {
			const memberIdx = $clickableParent.data('member-idx');
			const sunday = $clickableParent.data('sunday');

			console.log('Clickable 요소 클릭:', {
				member_idx: memberIdx,
				sunday: sunday
			});

			if (memberIdx && sunday) {
				openAttendanceDetail(sunday);
			}
		}
	}

	/**
	 * 출석 데이터 로드
	 */
	function loadAttendanceData() {
		if (!selectedOrgId) {
			console.log('선택된 조직이 없음');
			return;
		}

		console.log('출석 데이터 로드:', {
			type: selectedType,
			org_id: selectedOrgId,
			area_idx: selectedAreaIdx,
			year: currentYear
		});

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
				console.log('출석 데이터 응답:', response);
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
					console.log('출석 유형 로드됨:', attendanceTypes);
				}
			},
			error: function (xhr, status, error) {
				console.error('출석 유형 로드 실패:', error);
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

		console.log('일요일 날짜들:', sundayDates);
		console.log('출석 데이터:', attendanceData);

		// 그리드 업데이트
		updateGrid();
		hideGridSpinner();
	}

	/**
	 * 그리드 업데이트
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

			console.log('그리드 업데이트 완료');
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

		// 서버에서 계산된 실제 포인트 합계 반환
		return weekData.total_score || 0;
	}

	/**
	 * 출석 상세 정보 열기
	 */
	function openAttendanceDetail(sunday) {
		console.log('출석 상세 열기:', sunday);

		const date = new Date(sunday);
		const formattedDate = date.getFullYear() + '.' + (date.getMonth() + 1) + '.' + date.getDate();

		$('#attendanceOffcanvasLabel').text(formattedDate + ' 출석 상세');

		// 로딩 상태로 초기화
		$('#attendanceDetailContent').html(getLoadingHtml());

		// offcanvas 표시
		const offcanvas = new bootstrap.Offcanvas($('#attendanceOffcanvas')[0]);
		offcanvas.show();

		// 출석 상세 데이터 로드
		loadAttendanceDetail(sunday);
	}

	/**
	 * 출석 상세 데이터 로드
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
					renderAttendanceDetail(response.data, sunday);
				} else {
					$('#attendanceDetailContent').html('<div class="alert alert-danger">출석 상세 정보를 불러올 수 없습니다.</div>');
				}
			},
			error: function (xhr, status, error) {
				console.error('출석 상세 데이터 로드 실패:', error);
				$('#attendanceDetailContent').html('<div class="alert alert-danger">출석 상세 정보를 불러오는 중 오류가 발생했습니다.</div>');
			}
		});
	}


	$(document).off('click', '#btnSaveAttendance').on('click', '#btnSaveAttendance', function() {
		console.log('저장 버튼 클릭됨 - saveAttendanceDetail 호출');
		saveAttendanceDetail();
	});


	/**
	 * 역할: 출석 정보 저장 - 메모 정보도 함께 저장 (수정된 버전)
	 */
	function saveAttendanceDetail() {
		console.log('출석 및 메모 저장 시작 - saveAttendanceDetail 함수 실행됨');

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

		console.log('출석 날짜:', attendanceDate);

		// 출석 데이터 수집
		const attendanceData = [];
		const memoData = []; // 메모 데이터 수집용

		// 각 회원별로 출석 정보 및 메모 수집
		$('#attendanceDetailContent table tbody tr').each(function() {
			const row = $(this);
			const memberIdx = row.data('member-idx');
			console.log('처리 중인 회원 IDX:', memberIdx);

			// 회원 인덱스가 없으면 스킵
			if (!memberIdx) {
				console.log('회원 인덱스가 없어서 스킵');
				return;
			}

			// 회원 인덱스 찾기 (현재 회원목록에서)
			const member = currentMembers.find(m => m.member_idx == memberIdx);
			if (!member) {
				console.log('회원을 찾을 수 없음:', memberIdx);
				return;
			}

			const memberAttendanceTypes = [];

			// 체크박스 처리
			row.find('.attendance-checkbox:checked').each(function() {
				const attTypeIdx = $(this).data('att-type-idx');
				const attTypeScore = $(this).data('att-type-score');
				console.log('체크박스 발견:', attTypeIdx, attTypeScore);

				memberAttendanceTypes.push({
					att_type_idx: attTypeIdx,
					att_value: attTypeScore || 10,
					input_type: 'check'
				});
			});

			// 텍스트박스 처리
			row.find('.attendance-textbox').each(function() {
				const value = parseInt($(this).val()) || 0;
				if (value > 0) {
					const attTypeIdx = $(this).data('att-type-idx');
					console.log('텍스트박스 값:', attTypeIdx, value);

					memberAttendanceTypes.push({
						att_type_idx: attTypeIdx,
						att_value: value,
						input_type: 'text'
					});
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

			// 메모 데이터 수집 - 디버깅 강화
			const memoInput = row.find('.attendance-memo');
			console.log('메모 입력 필드 찾음:', memoInput.length, memberIdx);

			if (memoInput.length > 0) {
				const memoContent = memoInput.val() ? memoInput.val().trim() : '';
				const attIdx = memoInput.data('att-idx') || null;
				const isChanged = memoInput.data('changed') || false;

				console.log('메모 정보:', {
					memberIdx: memberIdx,
					memoContent: memoContent,
					attIdx: attIdx,
					isChanged: isChanged
				});

				// 메모가 변경되었거나 내용이 있는 경우 저장 대상에 추가
				// 빈 메모도 처리하도록 수정
				memoData.push({
					member_idx: memberIdx,
					memo_content: memoContent,
					att_idx: attIdx
				});
				console.log('메모 데이터에 추가됨');
			}
		});

		console.log('수집된 출석 데이터:', attendanceData);
		console.log('수집된 메모 데이터:', memoData);

		// 저장 버튼 비활성화
		const $saveBtn = $('#btnSaveAttendance');
		const originalText = $saveBtn.text();
		$saveBtn.prop('disabled', true).text('저장중...');

		console.log('AJAX 요청 URL:', window.attendancePageData.baseUrl + 'attendance/save_attendance_with_memo');

		// AJAX 요청 - save_attendance_with_memo 호출
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
			beforeSend: function() {
				console.log('AJAX 요청 시작 - save_attendance_with_memo');
			},
			success: function(response) {
				console.log('서버 응답:', response);

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
				console.error('상태:', status);
				console.error('오류:', error);

				showToast('출석 및 메모 저장 중 오류가 발생했습니다.', 'error');
			},
			complete: function() {
				// 저장 버튼 복원
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
	 * 역할: text 타입 출석유형도 헤더에 포인트 표시하도록 수정
	 */

	function renderAttendanceDetail(data, sunday) {
		const {attendance_types, attendance_records, members_info, memo_records = {}} = data;

		let html = `
<div class="attendance-detail simple-table">
	<table class="table table-sm table-bordered">
		<thead class="table-light">
			<tr>
				<th style="width: 80px;">이름</th>
				<th style="width: 140px;">한줄메모</th>
				<th style="width: 60px;">소계</th>
`;

		// 출석유형 헤더 추가 - text 타입도 포인트 표시하도록 수정
		attendance_types.forEach(function (type) {
			const typeName = type.att_type_nickname || type.att_type_name;
			const inputType = type.att_type_input || 'check';
			const typePoint = Number(type.att_type_point) || 10;



			html += `<th class="text-center" style="width: 80px;">${typeName}<br/><span class='att-point'>(${typePoint})</span></th>`;
		});

		html += `
			</tr>
		</thead>
		<tbody>
`;

		// 회원별 데이터 처리
		members_info.forEach(function (member) {
			const memberIdx = member.member_idx;
			const memberName = member.member_name;

			// 해당 회원의 메모 조회
			const memberMemo = memo_records[memberIdx] || {};
			const memoContent = memberMemo.memo_content || '';
			const attIdx = memberMemo.att_idx || null;

			// 해당 회원의 출석기록 필터링
			const memberAttendanceRecords = attendance_records.filter(record =>
				record.member_idx == memberIdx
			);

			// 출석 유형별 그룹핑
			const attendanceByType = {};
			let weekTotalScore = 0;

			memberAttendanceRecords.forEach(function (record) {
				const typeIdx = record.att_type_idx;
				if (!attendanceByType[typeIdx]) {
					attendanceByType[typeIdx] = [];
				}
				attendanceByType[typeIdx].push(record);

				// att_value가 있으면 그 값을 사용, 없으면 att_type_point 사용
				const scoreValue = Number(record.att_value) || Number(record.att_type_point) || 10;
				weekTotalScore += scoreValue;
			});

			html += `<tr data-member-idx="${memberIdx}">`;
			html += `<td class="member-name">${memberName}</td>`;

			// 메모 입력 필드 추가
			html += `
		<td>
			<input type="text" class="form-control form-control-sm attendance-memo" 
				   value="${escapeHtml(memoContent)}" 
				   data-member-idx="${memberIdx}"
				   data-att-idx="${attIdx || ''}"
				   placeholder="메모 입력">
		</td>
	`;

			// 소계
			html += `<td class="text-center week-total">${weekTotalScore}</td>`;

			// 각 출석유형별 체크박스/입력박스 생성
			attendance_types.forEach(function (type) {
				const typeIdx = type.att_type_idx;
				const inputType = type.att_type_input || 'check';
				const typeRecords = attendanceByType[typeIdx] || [];

				html += '<td class="text-center">';

				if (inputType === 'text') {
					// 텍스트박스인 경우 실제 값 표시
					const currentValue = typeRecords.length > 0 ? (Number(typeRecords[0].att_value) || 0) : 0;
					const defaultPoint = Number(type.att_type_point) || 10;
					html += `<input type="number" class="form-control form-control-sm attendance-textbox text-center" 
					 value="${currentValue}" min="0" max="999" 
					 data-att-type-idx="${typeIdx}" 
					 data-att-type-default-point="${defaultPoint}"
					 data-member-idx="${memberIdx}"
					 placeholder="${defaultPoint}"
					 style="width: 60px;">`;
				} else {
					// 체크박스인 경우
					const isChecked = typeRecords.length > 0;
					const typeScore = Number(type.att_type_point) || 10;
					html += `<div class="form-check d-flex justify-content-center">
					<input class="form-check-input attendance-checkbox" type="checkbox" 
						   ${isChecked ? 'checked' : ''} 
						   data-att-type-idx="${typeIdx}"
						   data-att-type-score="${typeScore}"
						   data-member-idx="${memberIdx}">
					</div>`;
				}

				html += '</td>';
			});

			html += '</tr>';
		});

		html += `
		</tbody>
	</table>
</div>
`;

		// 콘텐츠 업데이트
		$('#attendanceDetailContent').html(html);

		// 출석상태 변경 시 소계 재계산 이벤트 바인딩
		bindAttendanceChangeEvents();

		// 메모 입력 이벤트 바인딩
		bindMemoEvents();
	}

	/**
	 * 역할: 메모 관련 이벤트 바인딩
	 */
	function bindMemoEvents() {
		// 메모 입력 필드 변경 이벤트 - 더 정확한 이벤트 감지
		$(document).off('input change keyup paste', '.attendance-memo').on('input change keyup paste', '.attendance-memo', function() {
			$(this).data('changed', true);
			console.log('메모 변경됨:', $(this).data('member-idx'));
		});

		// 포커스 시 원본 값 저장
		$(document).off('focus', '.attendance-memo').on('focus', '.attendance-memo', function() {
			$(this).data('original-value', $(this).val());
		});

		// 포커스 아웃 시 변경 여부 확인
		$(document).off('blur', '.attendance-memo').on('blur', '.attendance-memo', function() {
			const originalValue = $(this).data('original-value') || '';
			const currentValue = $(this).val() || '';

			if (originalValue !== currentValue) {
				$(this).data('changed', true);
				console.log('메모 변경 확인됨:', $(this).data('member-idx'), originalValue, '->', currentValue);
			}
		});
	}

	/**
	 * 역할: 출석상세 모달에서 입력 변경 시 소계 실시간 업데이트
	 */
	function updateDetailTotalScores() {
		// 각 회원별로 소계 계산
		$('#attendanceDetailContent tbody tr').each(function() {
			const $row = $(this);
			let totalScore = 0;

			// 해당 행의 모든 입력 요소 확인
			$row.find('.attendance-checkbox, .attendance-textbox').each(function() {
				const $input = $(this);
				const typeScore = Number($input.data('att-type-score')) || 10;

				if ($input.hasClass('attendance-checkbox')) {
					// 체크박스인 경우
					if ($input.is(':checked')) {
						totalScore += typeScore;
					}
				} else if ($input.hasClass('attendance-textbox')) {
					// 텍스트박스인 경우
					const inputValue = Number($input.val()) || 0;
					totalScore += inputValue;
				}
			});

			// 소계 업데이트
			$row.find('td:nth-child(2)').html(`${totalScore}점`).addClass('fw-bold text-primary');
		});
	}

	/**
	 * 출석입력 이벤트 바인딩 - checkbox와 textbox 모두 처리
	 */
	function bindAttendanceInputEvents() {
		// 체크박스 이벤트
		$('.attendance-checkbox').off('change').on('change', function () {
			updateMemberScoreFromInputs($(this));
		});

		// 텍스트박스 이벤트
		$('.attendance-textbox').off('input').on('input', function () {
			updateMemberScoreFromInputs($(this));
		});
	}



	/**
	 * 회원 점수 업데이트 - 숫자 타입 강제 변환
	 */
	function updateMemberScoreFromInputs($input) {
		const row = $input.closest('tr');
		const scoreCell = row.find('.week-total');

		let totalScore = 0; // 숫자 0으로 초기화

		// 체크박스 점수 계산 - Number() 강제 변환
		row.find('.attendance-checkbox:checked').each(function () {
			const score = Number($(this).data('att-type-score')) || 0;
			console.log('체크박스 점수:', score, typeof score);
			totalScore += score;
		});

		// 텍스트박스 점수 계산 - Number() 강제 변환
		row.find('.attendance-textbox').each(function () {
			const value = Number($(this).val()) || 0;
			console.log('텍스트박스 값:', value, typeof value);
			if (value > 0) {
				totalScore += value;
			}
		});

		console.log('최종 계산된 점수:', totalScore, typeof totalScore);

		// 점수 표시 업데이트 - Number()로 확실히 숫자 보장
		scoreCell.text(totalScore);
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

		orgNameElement.html('<i class="bi bi-calendar-check"></i> ' + displayText);
	}

	/**
	 * 정리 이벤트 설정
	 */
	function setupCleanupEvents() {
		$(window).off('beforeunload.attendance').on('beforeunload.attendance', function () {
			destroySplitJS();
		});

		$('#attendanceOffcanvas').off('hidden.bs.offcanvas.attendance').on('hidden.bs.offcanvas.attendance', function () {
			$('#attendanceDetailContent').html(getLoadingHtml());
		});
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
		// 체크박스 변경 이벤트
		$(document).off('change', '.attendance-checkbox').on('change', '.attendance-checkbox', function() {
			const row = $(this).closest('tr');
			recalculateWeekTotal(row);
		});

		// 텍스트박스 변경 이벤트
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
			console.log('체크박스 점수:', score, typeof score);
			weekTotal += score;
		});

		// 텍스트박스 값들의 합산 - Number() 사용
		row.find('.attendance-textbox').each(function() {
			const value = Number($(this).val()) || 0;
			console.log('텍스트박스 값:', value, typeof value);
			weekTotal += value;
		});

		console.log('계산된 주간 총합:', weekTotal, typeof weekTotal);

		// 소계 업데이트 - 숫자만 표시
		row.find('.week-total').text(weekTotal);
	}

	/**
	 * 역할: Toast 메시지 표시 함수
	 */
	function showToast(message, type = 'info') {
		const toast = $('#attendanceToast');
		const toastBody = toast.find('.toast-body');

		// Toast 메시지 설정
		toastBody.text(message);

		// 타입별 스타일 적용
		toast.removeClass('bg-success bg-danger bg-warning bg-info');
		switch(type) {
			case 'success':
				toast.addClass('bg-success text-white');
				break;
			case 'error':
				toast.addClass('bg-danger text-white');
				break;
			case 'warning':
				toast.addClass('bg-warning text-dark');
				break;
			default:
				toast.addClass('bg-info text-white');
				break;
		}

		// Toast 표시
		const toastInstance = new bootstrap.Toast(toast[0], {
			autohide: true,
			delay: 3000
		});
		toastInstance.show();
	}

	// ===== 스피너 관련 함수들 =====

	/**
	 * 트리 스피너 표시
	 */
	function showTreeSpinner() {
		$('#treeSpinner').removeClass('d-none').addClass('d-flex');
		console.log('트리 스피너 표시');
	}

	/**
	 * 트리 스피너 숨김
	 */
	function hideTreeSpinner() {
		$('#treeSpinner').removeClass('d-flex').addClass('d-none');
		console.log('트리 스피너 숨김');
	}

	/**
	 * 그리드 스피너 표시
	 */
	function showGridSpinner() {
		$('#gridSpinner').removeClass('d-none').addClass('d-flex');
		console.log('그리드 스피너 표시');
	}

	/**
	 * 그리드 스피너 숨김
	 */
	function hideGridSpinner() {
		$('#gridSpinner').removeClass('d-flex').addClass('d-none');
		console.log('그리드 스피너 숨김');
	}

	/**
	 * 모든 스피너 표시 (초기 로딩 시)
	 */
	function showAllSpinners() {
		showTreeSpinner();
		showGridSpinner();
		console.log('모든 스피너 표시');
	}

	/**
	 * 모든 스피너 숨김
	 */
	function hideAllSpinners() {
		hideTreeSpinner();
		hideGridSpinner();
		console.log('모든 스피너 숨김');
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
				console.log('통계 재계산 응답:', response);

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

});
