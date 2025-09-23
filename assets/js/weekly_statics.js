'use strict'

$(document).ready(function () {
	// 전역 변수 영역
	let attendanceGrid;                // ParamQuery Grid 인스턴스
	let selectedOrgId = null;          // 선택된 조직 ID
	let selectedAreaIdx = null;        // 선택된 소그룹 ID
	let selectedType = null;           // 선택된 타입 ('org', 'area', 'unassigned')
	let splitInstance = null;          // Split.js 인스턴스
	let currentYear = window.attendancePageData.currentYear; // 현재 년도
	let attendanceTypes = [];          // 출석유형 목록
	let selectedAttTypeIdx = 'all';    // 선택된 출석유형 (기본: 전체)
	let sundayDates = [];              // 일요일 날짜 목록
	let attendanceData = {};           // 출석데이터
	let currentMembers = [];           // 현재 회원 목록

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
	 * 전역 이벤트 바인딩 함수
	 */
	function bindGlobalEvents() {
		// 연도 변경 버튼
		$('#prevYear').off('click').on('click', function () {
			changeYear(-1);
		});

		$('#nextYear').off('click').on('click', function () {
			changeYear(1);
		});


		// 출석유형별 탭 이벤트
		bindAttendanceTypeTabEvents();

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
	 * 출석유형별 탭 이벤트 바인딩
	 */
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
		showTreeSpinner();

		$.ajax({
			url: window.attendancePageData.baseUrl + 'weekly_statics/get_org_tree',
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
	 * Fancytree 인스턴스 설정
	 */
	function setupFancytreeInstance(treeData) {
		$("#groupTree").fancytree({
			source: treeData,
			activate: function (event, data) {
				const node = data.node;
				handleTreeNodeActivate(node);
			}
		});

		// localStorage에서 저장된 선택 상태 복원
		restoreSelectedGroupFromStorage(treeData);
	}

	/**
	 * 트리 노드 활성화 처리
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
	 * ParamQuery Grid 초기화
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
				selectionModel: {type: '', mode: 'single'},
				scrollModel: {autoFit: false, horizontal: true, vertical: true},
				freezeCols: isMobile ? 0 : 4,
				numberCell: {show: false},
				strNoRows: '출석 정보가 없습니다',
				title: false,
				resizable: true,
				sortable: false,
				hoverMode: 'cell',
				wrap: false,
				columnBorders: true
			});

			hideGridSpinner();
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
	 * 동적 컬럼 생성
	 */
	function createDynamicColumns() {
		let columns = getInitialColumns();

		// 합계 컬럼 추가 (count 방식)
		columns.push({
			title: "합계",
			dataIndx: "total_score",
			width: 80,
			align: "center",
			render: function (ui) {
				const totalCount = parseInt(ui.cellData) || 0;
				const color = totalCount > 0 ? '#0d6efd' : '#6c757d';
				return '<span style="font-weight: bold; color: ' + color + ';">' + totalCount + '</span>';
			}
		});

		// 현재 주의 일요일 날짜 계산
		const today = new Date();
		const currentWeekSunday = getCurrentWeekSunday(today);

		// 일요일 날짜 컬럼들 추가
		sundayDates.forEach(function (sunday) {
			const date = new Date(sunday);
			const month = date.getMonth() + 1;
			const day = date.getDate();
			const isCurrentWeek = sunday === currentWeekSunday;

			columns.push({
				title: month + '/' + day,
				dataIndx: 'week_' + sunday.replace(/-/g, ''),
				width: 70,
				align: "center",
				style: isCurrentWeek ? 'background-color: #fff3cd;' : '',
				cls: isCurrentWeek ? 'current-week-column' : '',
				render: function (ui) {
					return createAttendanceCellHtml(ui.rowData.member_idx, sunday);
				}
			});
		});

		return columns;
	}

	/**
	 * 현재 주의 일요일 날짜 계산
	 */
	function getCurrentWeekSunday(today) {
		const currentDate = new Date(today);
		const daysFromSunday = currentDate.getDay();

		if (daysFromSunday > 0) {
			currentDate.setDate(currentDate.getDate() - daysFromSunday);
		}

		return currentDate.toISOString().split('T')[0];
	}

	/**
	 * 출석셀 HTML 생성 - count 기반
	 */
	function createAttendanceCellHtml(memberIdx, sunday) {
		const weekCount = getWeekAttendanceCount(memberIdx, sunday);

		if (weekCount > 0) {
			return '<div class="attendance-score" style="background-color: #d1edff; color: #0d6efd; font-weight: bold; padding: 4px 8px; border-radius: 4px; min-width: 24px; text-align: center;">' + weekCount + '</div>';
		} else {
			return '<div class="attendance-score" style="color: #6c757d;">0</div>';
		}
	}

	/**
	 * 출석유형 목록 로드 및 탭 생성
	 */
	function loadAttendanceTypes() {
		if (!selectedOrgId) {
			return;
		}

		$.ajax({
			url: window.attendancePageData.baseUrl + 'weekly_statics/get_attendance_types',
			method: 'POST',
			data: {org_id: selectedOrgId},
			dataType: 'json',
			success: function (response) {
				if (response.success) {
					attendanceTypes = response.data || [];
					createAttendanceTypeTabs();
				} else {
					console.error('출석유형 로드 실패:', response.message);
				}
			},
			error: function (xhr, status, error) {
				console.error('출석유형 로드 실패:', error);
			}
		});
	}

	/**
	 * 출석유형별 탭 생성
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
	 * 출석데이터 로드
	 */
	function loadAttendanceData() {
		if (!selectedOrgId) {
			return;
		}

		showGridSpinner();

		// 출석유형 먼저 로드
		loadAttendanceTypes();

		$.ajax({
			url: window.attendancePageData.baseUrl + 'weekly_statics/get_attendance_data',
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
				console.error('출석데이터 로드 실패:', error);
				hideGridSpinner();
				showToast('출석데이터를 불러오는데 실패했습니다.', 'error');
			}
		});
	}

	/**
	 * 출석데이터 응답 처리
	 */
	function handleAttendanceDataResponse(response) {
		if (!response.success) {
			console.error('출석데이터 로드 실패:', response.message);
			hideGridSpinner();
			showToast('출석데이터를 불러오는데 실패했습니다.', 'error');
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
	 * 그리드 업데이트
	 */
	function updateGrid() {
		if (!attendanceGrid) {
			console.error('그리드가 초기화되지 않았습니다.');
			return;
		}

		try {
			const newColumns = createDynamicColumns();
			const gridData = prepareGridData();

			attendanceGrid.pqGrid("option", "colModel", newColumns);
			attendanceGrid.pqGrid("option", "dataModel.data", gridData);
			attendanceGrid.pqGrid("refreshDataAndView");
		} catch (error) {
			console.error('그리드 업데이트 실패:', error);
			showToast('그리드 업데이트에 실패했습니다.', 'error');
		}
	}

	/**
	 * 그리드 데이터 준비 - count 기반으로 변경
	 */
	function prepareGridData() {
		return currentMembers.map(function (member) {
			let totalCount = 0;

			if (attendanceData[member.member_idx]) {
				sundayDates.forEach(function (sunday) {
					const weekCount = getWeekAttendanceCount(member.member_idx, sunday);
					totalCount += weekCount;
				});
			}

			let photoUrl = '/assets/images/photo_no.png';
			if (member.photo) {
				if (member.photo.indexOf('/uploads/') === -1) {
					photoUrl = '/uploads/member_photos/' + selectedOrgId + '/' + member.photo;
				} else {
					photoUrl = member.photo;
				}
			}

			const areaName = member.area_name || '미분류';

			return {
				member_idx: member.member_idx,
				member_name: member.member_name,
				area_name: areaName,
				photo: photoUrl,
				total_score: totalCount
			};
		});
	}

	/**
	 * 특정 회원의 특정 주 출석 count 계산 - 선택된 출석유형 기준
	 */
	function getWeekAttendanceCount(memberIdx, sunday) {
		if (!attendanceData[memberIdx] || !attendanceData[memberIdx][sunday]) {
			return 0;
		}

		const weekData = attendanceData[memberIdx][sunday];

		if (selectedAttTypeIdx === 'all') {
			// 전체: 모든 출석유형 count
			let totalCount = 0;
			if (weekData.attendance_types) {
				attendanceTypes.forEach(function (attType) {
					const attTypeData = weekData.attendance_types[attType.att_type_idx];
					if (attTypeData && attTypeData.count > 0) {
						totalCount += 1; // 해당 출석유형이 있으면 1개로 count
					}
				});
			}
			return totalCount;
		} else {
			// 특정 출석유형만
			const attTypeData = weekData.attendance_types && weekData.attendance_types[selectedAttTypeIdx];
			return (attTypeData && attTypeData.count > 0) ? 1 : 0;
		}
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

			showToast('검색결과: ' + filteredData.length + '명', 'info');
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
				displayText = title + ' - 전체 회원';
				break;
			case 'area':
				displayText = title + ' 소그룹';
				break;
			case 'unassigned':
				displayText = '미분류 회원';
				break;
		}

		orgNameElement.html(displayText);
	}

	/**
	 * 정리 이벤트 설정
	 */
	function setupCleanupEvents() {
		$(window).off('beforeunload.attendance').on('beforeunload.attendance', function () {
			destroySplitJS();
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
	 * 디바운스 함수
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

	// 스피너 관련 함수들

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

})
