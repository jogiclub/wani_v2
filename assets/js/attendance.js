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
	 * 전역 이벤트 바인딩
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

		// 출석 저장 버튼
		$('#btnSaveAttendance').off('click').on('click', function () {
			saveAttendanceChanges();
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
	 * Fancytree 인스턴스 설정
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

		// 첫 번째 조직 자동 선택
		setTimeout(function () {
			const tree = $("#groupTree").fancytree("getTree");
			if (tree && tree.rootNode && tree.rootNode.children.length > 0) {
				const firstOrgNode = tree.rootNode.children[0];
				firstOrgNode.setActive(true);
				firstOrgNode.setFocus(true);
				firstOrgNode.setExpanded(true);
			}
		}, 100);
	}

	/**
	 * 트리 노드 활성화 처리
	 */
	function handleTreeNodeActivate(node) {
		const nodeData = node.data;

		selectedType = nodeData.type;
		selectedOrgId = nodeData.org_id;
		selectedAreaIdx = nodeData.area_idx || null;

		updateSelectedOrgName(node.title, nodeData.type);
		resetSearch();
		loadAttendanceData();
	}


	/**
	 * ParamQuery Grid 초기화 - 셀 클릭 개선 적용
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
				selectionModel: {type: 'row', mode: 'single'},
				scrollModel: {autoFit: false, horizontal: true, vertical: true},
				freezeCols: 3,
				numberCell: {show: false},
				title: false,
				resizable: true,
				sortable: false,
				hoverMode: 'row',
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

	/**
	 * 출석상세 정보 렌더링 - 숫자 타입 강제 변환
	 */
	function renderAttendanceDetail(data, sunday) {
		const {attendance_types, attendance_records, members_info} = data;
		const date = new Date(sunday);

		let html = `
		<div class="attendance-detail">
			<div class="mb-4">
				<h6 class="fw-bold">${date.getFullYear()}.${date.getMonth() + 1}.${date.getDate()} 출석상세</h6>
			</div>
			
			<div class="table-responsive">
				<table class="table table-sm table-bordered">
					<thead class="table-light">
						<tr>
							<th style="width: 100px;">이름</th>
							<th style="width: 80px;">소계</th>
	`;

		// 출석유형 헤더 추가 (입력타입에 따라 다른 표시)
		attendance_types.forEach(function (type) {
			const typeName = type.att_type_nickname || type.att_type_name;
			const inputType = type.att_type_input || 'check';
			const typePoint = Number(type.att_type_point) || 10; // 숫자로 강제 변환
			const displayScore = inputType === 'text' ? '' : `(${typePoint})`;
			html += `<th style="width: 100px; text-align: center;">${typeName}${displayScore}</th>`;
		});

		html += '</tr></thead><tbody>';

		// 회원별 출석정보 렌더링
		members_info.forEach(function (member) {
			const memberAttendance = attendance_records.filter(record => record.member_idx == member.member_idx);

			// 먼저 회원별 총 점수를 정확히 계산 - 숫자 타입 보장
			let totalMemberScore = 0; // 초기값을 숫자 0으로 설정

			// 각 출석유형에 대해 점수 계산
			attendance_types.forEach(function (type) {
				const inputType = type.att_type_input || 'check';
				const typeScore = Number(type.att_type_point) || 10; // 숫자로 강제 변환

				// 해당 출석유형의 기존 출석기록 확인
				const existingRecord = memberAttendance.find(record => record.att_type_idx == type.att_type_idx);

				if (existingRecord) {
					if (inputType === 'text') {
						// textbox의 경우 실제 저장된 값 사용 - 숫자로 강제 변환
						const actualValue = Number(existingRecord.att_value) || Number(typeScore) || 0;
						totalMemberScore += actualValue;
					} else {
						// checkbox의 경우 출석유형의 기본 점수 사용 - 숫자로 강제 변환
						totalMemberScore += Number(typeScore) || 10;
					}
				}
			});

			// 행 시작 및 회원이름, 소계 추가 - Number()로 확실히 숫자 보장
			html += `<tr>
			<td class="fw-medium">${member.member_name}</td>
			<td class="fw-bold text-primary">${Number(totalMemberScore)}점</td>`;

			// 각 출석유형별 입력 컨트롤 생성
			attendance_types.forEach(function (type) {
				const inputType = type.att_type_input || 'check';
				const typeScore = Number(type.att_type_point) || 10; // 숫자로 강제 변환
				const existingRecord = memberAttendance.find(record => record.att_type_idx == type.att_type_idx);
				const hasAttendance = !!existingRecord;

				if (inputType === 'text') {
					// 텍스트박스 입력
					const currentValue = hasAttendance ? (Number(existingRecord.att_value) || Number(typeScore) || 0) : 0;
					const textboxId = `att_${member.member_idx}_${type.att_type_idx}`;

					html += `
					<td style="text-align: center;">
						<input type="number" class="form-control form-control-sm attendance-textbox" 
							   id="${textboxId}" 
							   data-member-idx="${member.member_idx}" 
							   data-att-type-idx="${type.att_type_idx}"
							   data-att-type-score="${Number(typeScore)}"
							   value="${Number(currentValue)}"
							   min="0"
							   style="width: 70px; margin: auto;">
					</td>
				`;
				} else {
					// 체크박스 입력
					const checkboxId = `att_${member.member_idx}_${type.att_type_idx}`;

					html += `
					<td style="text-align: center;">
						<input type="checkbox" class="form-check-input attendance-checkbox" 
							   id="${checkboxId}" 
							   data-member-idx="${member.member_idx}" 
							   data-att-type-idx="${type.att_type_idx}"
							   data-att-type-score="${Number(typeScore)}"
							   ${hasAttendance ? 'checked' : ''}>
					</td>
				`;
				}
			});

			html += '</tr>';
		});

		html += '</tbody></table></div></div>';

		$('#attendanceDetailContent').html(html);

		// 이벤트 바인딩
		bindAttendanceInputEvents();
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
		const scoreCell = row.find('td:nth-child(2)');

		let totalScore = 0; // 초기값을 숫자 0으로 설정

		// 체크박스 점수 계산 - 숫자 강제 변환
		row.find('.attendance-checkbox:checked').each(function () {
			const score = Number($(this).data('att-type-score')) || 10;
			totalScore += score;
		});

		// 텍스트박스 점수 계산 - 숫자 강제 변환
		row.find('.attendance-textbox').each(function () {
			const value = Number($(this).val()) || 0;
			if (value > 0) {
				totalScore += value;
			}
		});

		// 점수 표시 업데이트 - Number()로 확실히 숫자 보장
		scoreCell.html(`${Number(totalScore)}점`);
	}


	/**
	 * 출석변경사항 저장
	 */
	function saveAttendanceChanges() {
		const offcanvasTitle = $('#attendanceOffcanvasLabel').text();
		const dateMatch = offcanvasTitle.match(/(\d{4})\.(\d{1,2})\.(\d{1,2})/);

		if (!dateMatch) {
			showToast('날짜 정보를 찾을 수 없습니다.', 'error');
			return;
		}

		const year = dateMatch[1];
		const month = dateMatch[2].padStart(2, '0');
		const day = dateMatch[3].padStart(2, '0');
		const attendanceDate = `${year}-${month}-${day}`;

		// 출석 데이터 수집
		const attendanceData = [];

		// 각 회원별로 출석 정보 수집
		$('#attendanceDetailContent table tbody tr').each(function() {
			const row = $(this);
			const memberName = row.find('td:first').text().trim();

			// 회원 인덱스 찾기 (현재 회원목록에서)
			const member = currentMembers.find(m => m.member_name === memberName);
			if (!member) return;

			const memberIdx = member.member_idx;
			const memberAttendanceTypes = [];

			// 체크박스 처리
			row.find('.attendance-checkbox:checked').each(function() {
				const attTypeIdx = $(this).data('att-type-idx');
				const attTypeScore = $(this).data('att-type-score');

				memberAttendanceTypes.push({
					att_type_idx: attTypeIdx,
					att_value: attTypeScore || 10,  // 기본값 10점
					input_type: 'check'
				});
			});

			// 텍스트박스 처리
			row.find('.attendance-textbox').each(function() {
				const value = parseInt($(this).val()) || 0;
				if (value > 0) {
					const attTypeIdx = $(this).data('att-type-idx');

					memberAttendanceTypes.push({
						att_type_idx: attTypeIdx,
						att_value: value,
						input_type: 'text'
					});
				}
			});

			if (memberAttendanceTypes.length > 0) {
				attendanceData.push({
					member_idx: memberIdx,
					att_date: attendanceDate,
					attendance_types: memberAttendanceTypes
				});
			}
		});

		if (attendanceData.length === 0) {
			showToast('저장할 출석 데이터가 없습니다.', 'warning');
			return;
		}

		// 저장 버튼 비활성화
		const $saveBtn = $('#btnSaveAttendance');
		const originalText = $saveBtn.text();
		$saveBtn.prop('disabled', true).text('저장중...');

		// AJAX 요청
		$.ajax({
			url: window.attendancePageData.baseUrl + 'attendance/save_attendance_with_values',
			method: 'POST',
			data: {
				org_id: selectedOrgId,
				attendance_data: JSON.stringify(attendanceData),
				att_date: attendanceDate,
				year: currentYear
			},
			dataType: 'json',
			success: function(response) {
				if (response.success) {
					showToast('출석이 저장되었습니다.', 'success');

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
					showToast(response.message || '출석 저장에 실패했습니다.', 'error');
				}
			},
			error: function(xhr, status, error) {
				console.error('출석 저장 실패:', error);
				showToast('출석 저장 중 오류가 발생했습니다.', 'error');
			},
			complete: function() {
				$saveBtn.prop('disabled', false).text(originalText);
			}
		});
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
	 * Toast 메시지 표시
	 */
	function showToast(message, type = 'info') {
		const toast = $('#attendanceToast');
		const toastBody = toast.find('.toast-body');

		// 타입별 아이콘 설정
		let icon = '';
		let bgClass = '';

		switch(type) {
			case 'success':
				icon = '<i class="bi bi-check-circle-fill text-success me-2"></i>';
				bgClass = 'bg-success-subtle';
				break;
			case 'error':
				icon = '<i class="bi bi-exclamation-triangle-fill text-danger me-2"></i>';
				bgClass = 'bg-danger-subtle';
				break;
			case 'warning':
				icon = '<i class="bi bi-exclamation-triangle-fill text-warning me-2"></i>';
				bgClass = 'bg-warning-subtle';
				break;
			case 'info':
			default:
				icon = '<i class="bi bi-info-circle-fill text-info me-2"></i>';
				bgClass = 'bg-info-subtle';
				break;
		}

		// Toast 내용 설정
		toastBody.removeClass('bg-success-subtle bg-danger-subtle bg-warning-subtle bg-info-subtle')
			.addClass(bgClass)
			.html(icon + message);

		// Toast 표시
		const bsToast = new bootstrap.Toast(toast[0], {
			autohide: true,
			delay: 3000
		});
		bsToast.show();
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
