'use strict';

/**
 * 파일 위치: assets/js/mng_education.js
 * 역할: 관리자 양육관리 화면의 메인 JavaScript 파일
 */

(function() {
	// 전역 변수
	let eduGrid = null;
	let applicantGrid = null;
	let treeInstance = null;
	let splitInstance = null;
	let selectedNodeType = null;
	let selectedNodeId = null;
	let selectedNodeName = '';
	let eduDetailOffcanvas = null;
	let applicantOffcanvas = null;

	// 검색 관련 전역 변수
	let searchParams = {
		date: '',
		days: [],
		times: [],
		ages: [],
		genders: [],
		keyword: ''
	};

	// DOM 준비 완료 시 초기화
	$(document).ready(function() {
		initializePage();
	});

	/**
	 * 페이지 전체 초기화
	 */
	function initializePage() {
		console.log('양육관리 페이지 초기화 시작');

		// Offcanvas 초기화
		const eduDetailOffcanvasEl = document.getElementById('eduDetailOffcanvas');
		if (eduDetailOffcanvasEl) {
			eduDetailOffcanvas = new bootstrap.Offcanvas(eduDetailOffcanvasEl);
		}

		const applicantOffcanvasEl = document.getElementById('applicantOffcanvas');
		if (applicantOffcanvasEl) {
			applicantOffcanvas = new bootstrap.Offcanvas(applicantOffcanvasEl);
		}

		cleanupExistingInstances();
		initSplitJS();
		initFancytree();
		initSearchControls(); // 검색 컨트롤 초기화
		initParamQueryGrid();
		initApplicantGrid();
		bindGlobalEvents();

		console.log('양육관리 페이지 초기화 완료');
	}

	/**
	 * 기존 인스턴스 정리
	 */
	function cleanupExistingInstances() {
		if (splitInstance && splitInstance.destroy) {
			splitInstance.destroy();
			splitInstance = null;
		}

		if (treeInstance && $.ui.fancytree.getTree('#categoryTree')) {
			$('#categoryTree').fancytree('destroy');
			treeInstance = null;
		}

		if (eduGrid && eduGrid.destroy) {
			eduGrid.destroy();
			eduGrid = null;
		}

		if (applicantGrid && applicantGrid.destroy) {
			applicantGrid.destroy();
			applicantGrid = null;
		}
	}

	/**
	 * Split.js 초기화
	 */
	function initSplitJS() {
		const leftPane = document.getElementById('left-pane');
		const rightPane = document.getElementById('right-pane');

		if (!leftPane || !rightPane) {
			console.error('Split 패널을 찾을 수 없습니다.');
			return;
		}

		splitInstance = Split(['#left-pane', '#right-pane'], {
			sizes: [20, 80],
			minSize: [200, 400],
			gutterSize: 5,
			cursor: 'col-resize'
		});

		console.log('Split.js 초기화 완료');
	}

	/**
	 * Fancytree 초기화
	 */
	function initFancytree() {
		$('#treeSpinner').removeClass('d-none').addClass('d-flex');

		// 조직 트리와 전체 양육 수를 동시에 요청
		Promise.all([
			$.ajax({ url: '/mng/mng_education/get_category_org_tree', type: 'POST', dataType: 'json' }),
			$.ajax({ url: '/mng/mng_education/get_total_edu_count', type: 'POST', dataType: 'json' })
		]).then(function(results) {
			const treeDataFromServer = results[0];
			const totalCountResponse = results[1];
			const totalEduCount = totalCountResponse.total_count || 0;

			console.log('트리 데이터 로드 성공:', treeDataFromServer);
			console.log('전체 양육 수:', totalEduCount);

			// 미분류 노드를 별도로 추출
			const uncategorizedNode = treeDataFromServer.find(node => node.data && node.data.type === 'uncategorized');
			const categoryNodes = treeDataFromServer.filter(node => !node.data || node.data.type !== 'uncategorized');

			// '전체' 노드 생성
			const treeData = [
				{
					key: 'all',
					title: `전체 양육 (${totalEduCount}개)`,
					folder: true,
					expanded: true,
					data: { type: 'all' },
					children: categoryNodes // 미분류를 제외한 카테고리 노드만 추가
				}
			];

			// 미분류 노드가 있으면 최상위 레벨에 추가
			if (uncategorizedNode) {
				treeData.push(uncategorizedNode);
			}


			$('#categoryTree').fancytree({
				source: treeData,
				extensions: ['persist'],
				persist: {
					store: 'local',
					cookiePrefix: 'mng_edu_tree_'
				},
				selectMode: 1,
				activate: function(event, data) {
					handleTreeNodeActivation(data.node);
				},
				renderNode: function(event, data) {
					const node = data.node;
					const $span = $(node.span);

					if (node.data && node.data.type === 'org') {
						$span.find('.fancytree-icon').removeClass('fancytree-ico-cf')
							.addClass('bi bi-building text-primary');
					}
				}
			});

			treeInstance = $.ui.fancytree.getTree('#categoryTree');
			$('#treeSpinner').removeClass('d-flex').addClass('d-none');

			// 첫 번째 노드(전체)를 기본으로 활성화
			const firstNode = treeInstance.getFirstChild();
			if (firstNode) {
				firstNode.setActive();
			}

		}).catch(function(error) {
			console.error('트리 또는 전체 개수 로드 실패:', error);
			$('#treeSpinner').removeClass('d-flex').addClass('d-none');
			showToast('트리 데이터 로드에 실패했습니다.', 'error');
		});
	}

	/**
	 * 검색 컨트롤 초기화
	 */
	function initSearchControls() {
		// 검색 버튼 클릭
		$('#btn_search').on('click', function() {
			searchParams.date = $('#search_date').val();
			searchParams.keyword = $('#search_keyword').val();
			loadEduList();
		});

		// 초기화 버튼 클릭
		$('#btn_reset').on('click', function() {
			resetSearch();
		});

		// 엔터키로 검색
		$('#search_keyword, #search_date').on('keypress', function(e) {
			if (e.which === 13) {
				$('#btn_search').trigger('click');
			}
		});

		// 멀티셀렉트 드롭다운 설정
		setupMultiSelectDropdown('day', searchParams.days, '진행요일');
		setupMultiSelectDropdown('time', searchParams.times, '진행시간');
		setupMultiSelectDropdown('age', searchParams.ages, '연령대');
		setupMultiSelectDropdown('gender', searchParams.genders, '성별');

		// 진행시간 목록 동적 로드
		loadDistinctEduTimes();
	}

	/**
	 * 고유 진행시간 목록 로드 및 드롭다운 구성
	 */
	function loadDistinctEduTimes() {
		$.ajax({
			url: '/mng/mng_education/get_distinct_edu_times',
			type: 'POST',
			dataType: 'json',
			success: function(res) {
				if (res.success && res.data) {
					const $menu = $('#search_time_menu');
					$menu.empty();
					res.data.forEach(function(time) {
						const $li = $('<li><a class="dropdown-item" href="#"><input type="checkbox" value="' + escapeHtml(time) + '" class="form-check-input me-2">' + escapeHtml(time) + '</a></li>');
						$menu.append($li);
					});
				}
			},
			error: function() {
				console.error('진행시간 목록 로드에 실패했습니다.');
			}
		});
	}

	/**
	 * 멀티셀렉트 드롭다운 공통 설정 함수 (이벤트 위임 사용)
	 */
	function setupMultiSelectDropdown(type, targetArray, defaultBtnText) {
		const menuId = `#search_${type}_menu`;
		const btnId = `#search_${type}_btn`;

		$(document).on('change', `${menuId} input[type="checkbox"]`, function() {
			const value = $(this).val();
			if ($(this).is(':checked')) {
				if (!targetArray.includes(value)) {
					targetArray.push(value);
				}
			} else {
				const index = targetArray.indexOf(value);
				if (index > -1) {
					targetArray.splice(index, 1);
				}
			}
			updateMultiSelectButtonText($(btnId), targetArray, defaultBtnText);
			$('#btn_search').trigger('click');
		});

		// 드롭다운 메뉴가 닫히지 않도록 이벤트 전파 중단
		$(document).on('click', menuId, function(e) {
			e.stopPropagation();
		});
	}

	/**
	 * 멀티셀렉트 드롭다운 버튼 텍스트 업데이트
	 */
	function updateMultiSelectButtonText($btn, arr, defaultText) {
		if (arr.length === 0) {
			$btn.text(defaultText);
		} else if (arr.length === 1) {
			$btn.text(arr[0]);
		} else {
			$btn.text(`${defaultText} (${arr.length}개)`);
		}
	}

	/**
	 * 검색 조건 초기화
	 */
	function resetSearch() {
		// 검색 파라미터 초기화
		searchParams.date = '';
		searchParams.days = [];
		searchParams.times = [];
		searchParams.ages = [];
		searchParams.genders = [];
		searchParams.keyword = '';

		// UI 초기화
		$('#search_date').val('');
		$('#search_keyword').val('');

		// 멀티셀렉트 드롭다운 초기화
		$('.dropdown-menu input[type="checkbox"]').prop('checked', false);
		updateMultiSelectButtonText($('#search_day_btn'), searchParams.days, '진행요일');
		updateMultiSelectButtonText($('#search_time_btn'), searchParams.times, '진행시간');
		updateMultiSelectButtonText($('#search_age_btn'), searchParams.ages, '연령대');
		updateMultiSelectButtonText($('#search_gender_btn'), searchParams.genders, '성별');

		// 목록 다시 로드
		loadEduList();
	}

	/**
	 * 트리 노드 활성화 처리
	 */
	function handleTreeNodeActivation(node) {
		const nodeData = node.data;

		if (nodeData.type === 'org') {
			selectedNodeType = 'org';
			selectedNodeId = nodeData.org_id;
			selectedNodeName = nodeData.org_name;
			$('#selectedNodeName').html('<i class="bi bi-building"></i> ' + selectedNodeName);
		} else if (nodeData.type === 'category') {
			selectedNodeType = 'category';
			selectedNodeId = nodeData.category_idx;
			selectedNodeName = nodeData.category_name;
			$('#selectedNodeName').html('<i class="bi bi-folder"></i> ' + selectedNodeName);
		} else {
			selectedNodeType = 'all';
			selectedNodeId = null;
			selectedNodeName = '전체 양육';
			$('#selectedNodeName').html('<i class="bi bi-book"></i> ' + selectedNodeName);
		}

		loadEduList();
	}

	/**
	 * ParamQuery Grid 초기화 (양육 목록)
	 */
	function initParamQueryGrid() {
		const columns = [
			{
				title: "양육명",
				dataIndx: "edu_name",
				width: 250,
				render: function(ui) {
					return '<a href="javascript:void(0);" class="edu-detail-link text-primary text-decoration-none" data-edu-idx="' +
						ui.rowData.edu_idx + '">' + escapeHtml(ui.cellData) + '</a>';
				}
			},
			{
				title: "소속 조직",
				dataIndx: "org_name",
				width: 200,
				render: function(ui) {
					if (ui.cellData && ui.rowData.org_id) {
						return '<span class="badge bg-primary org-dashboard-link" data-org-id="' + ui.rowData.org_id + '" data-org-name="' + ui.cellData + '" style="cursor: pointer;" title="' + ui.cellData + ' 대시보드 바로가기">' +
							ui.cellData + ' <i class="bi bi-box-arrow-up-right"></i></span>';
					}
					return '<span class="badge bg-light text-dark">미지정</span>';
				}
			},
			{
				title: "카테고리",
				dataIndx: "category_name",
				width: 120
			},
			{
				title: "장소",
				dataIndx: "edu_location",
				width: 150
			},
			{
				title: "시작일",
				dataIndx: "edu_start_date",
				width: 100,
				render: function(ui) {
					return ui.cellData ? ui.cellData.substring(0, 10) : '';
				}
			},
			{
				title: "종료일",
				dataIndx: "edu_end_date",
				width: 100,
				render: function(ui) {
					return ui.cellData ? ui.cellData.substring(0, 10) : '';
				}
			},
			{
				title: "진행요일",
				dataIndx: "edu_days",
				width: 120,
				render: function(ui) {
					try {
						const days = JSON.parse(ui.cellData);
						return Array.isArray(days) ? days.join(', ') : '';
					} catch (e) {
						return '';
					}
				}
			},
			{
				title: "진행시간",
				dataIndx: "edu_times",
				width: 100,
				render: function(ui) {
					try {
						const times = JSON.parse(ui.cellData);
						return Array.isArray(times) ? times.join(', ') : '';
					} catch (e) {
						return '';
					}
				}
			},
			{
				title: "담당자",
				dataIndx: "edu_leader",
				width: 100
			},
			{
				title: "연령대",
				dataIndx: "edu_leader_age",
				width: 80,
				render: function(ui) {
					const ageMap = { '10s': '10대', '20s': '20대', '30s': '30대', '40s': '40대', '50s': '50대', '60s': '60대 이상' };
					return ageMap[ui.cellData] || '';
				}
			},
			{
				title: "성별",
				dataIndx: "edu_leader_gender",
				width: 60,
				render: function(ui) {
					const genderMap = { 'male': '남', 'female': '여' };
					return genderMap[ui.cellData] || '';
				}
			},
			{
				title: "신청자",
				dataIndx: "applicant_count",
				width: 80,
				align: 'center',
				render: function(ui) {
					const count = ui.cellData || 0;
					if (count > 0) {
						return '<a href="javascript:void(0);" class="applicant-link text-primary" data-edu-idx="' +
							ui.rowData.edu_idx + '">' + count + '명</a>';
					}
					return '0명';
				}
			},
			{
				title: "공개",
				dataIndx: "public_yn",
				width: 60,
				align: 'center',
				render: function(ui) {
					return ui.cellData === 'Y'
						? '<span class="badge bg-success">공개</span>'
						: '<span class="badge bg-secondary">비공개</span>';
				}
			}
		];

		eduGrid = pq.grid('#eduGrid', {
			width: '100%',
			height: "100%",
			colModel: columns,
			freezeCols: 2,
			dataModel: { data: [] },
			resizable: true,
			numberCell: { show: false },
			selectionModel: { type: 'cell', mode: 'single' },
			strNoRows: '양육 데이터가 없습니다.',
			wrap: false,
			hwrap: false,
			rowInit: function (ui) {
				var style = "height: 40px;";
				return {
					style: style,
				};
			},
			rowClick: function(evt, ui) {
				// 링크 클릭은 별도 처리
			}
		});

		console.log('ParamQuery Grid 초기화 완료');
	}

	/**
	 * 신청자 Grid 초기화
	 */
	function initApplicantGrid() {
		const columns = [
			{
				title: "이름",
				dataIndx: "applicant_name",
				width: 120
			},
			{
				title: "연락처",
				dataIndx: "applicant_phone",
				width: 130
			},
			{
				title: "상태",
				dataIndx: "status",
				width: 100,
				align: 'center',
				render: function(ui) {
					const status = ui.cellData || '신청';
					let badgeClass = 'bg-primary';
					if (status === '수료') badgeClass = 'bg-success';
					else if (status === '중도포기') badgeClass = 'bg-danger';
					else if (status.includes('외부')) badgeClass = 'bg-info';
					return '<span class="badge ' + badgeClass + '">' + escapeHtml(status) + '</span>';
				}
			},
			{
				title: "신청일",
				dataIndx: "regi_date",
				width: 150,
				render: function(ui) {
					return ui.cellData ? ui.cellData.substring(0, 16) : '';
				}
			},
			{
				title: "상태 변경",
				width: 200,
				render: function(ui) {
					const currentStatus = ui.rowData.status || '신청';
					return '<select class="form-select form-select-sm applicant-status-select" data-applicant-idx="' +
						ui.rowData.applicant_idx + '">' +
						'<option value="신청"' + (currentStatus === '신청' ? ' selected' : '') + '>신청</option>' +
						'<option value="승인"' + (currentStatus === '승인' ? ' selected' : '') + '>승인</option>' +
						'<option value="중도포기"' + (currentStatus === '중도포기' ? ' selected' : '') + '>중도포기</option>' +
						'<option value="수료"' + (currentStatus === '수료' ? ' selected' : '') + '>수료</option>' +
						'</select>';
				}
			}
		];

		applicantGrid = pq.grid('#applicantGrid', {
			width: '100%',
			height: 500,
			colModel: columns,
			dataModel: { data: [] },
			scrollModel: { autoFit: true },
			numberCell: { show: true, title: "No", width: 40 },
			strNoRows: '신청자가 없습니다.'
		});

		console.log('신청자 Grid 초기화 완료');
	}

	/**
	 * 양육 목록 로드
	 */
	function loadEduList() {
		$('#gridSpinner').removeClass('d-none').addClass('d-flex');

		const postData = {
			type: selectedNodeType || 'all',
			...searchParams
		};

		if (selectedNodeType === 'org') {
			postData.org_id = selectedNodeId;
		} else if (selectedNodeType === 'category') {
			postData.category_idx = selectedNodeId;
		}

		$.ajax({
			url: '/mng/mng_education/get_edu_list',
			type: 'POST',
			data: postData,
			dataType: 'json',
			success: function(res) {
				if (res.success) {
					eduGrid.option('dataModel.data', res.data);
					eduGrid.refreshDataAndView();
					$('#totalEduCount').text(res.total_count);
				} else {
					showToast(res.message || '양육 목록 로드에 실패했습니다.', 'error');
				}
				$('#gridSpinner').removeClass('d-flex').addClass('d-none');
			},
			error: function() {
				showToast('양육 목록 로드 중 오류가 발생했습니다.', 'error');
				$('#gridSpinner').removeClass('d-flex').addClass('d-none');
			}
		});
	}

	/**
	 * 양육 상세 로드
	 */
	function loadEduDetail(eduIdx) {
		$.ajax({
			url: '/mng/mng_education/get_edu_detail',
			type: 'POST',
			data: { edu_idx: eduIdx },
			dataType: 'json',
			success: function(res) {
				if (res.success) {
					displayEduDetail(res.data);
					eduDetailOffcanvas.show();
				} else {
					showToast(res.message || '양육 상세 정보를 불러올 수 없습니다.', 'error');
				}
			},
			error: function() {
				showToast('양육 상세 정보 로드 중 오류가 발생했습니다.', 'error');
			}
		});
	}

	/**
	 * 양육 상세 정보 표시
	 */
	function displayEduDetail(edu) {
		$('#detail_edu_name').text(edu.edu_name || '');
		$('#detail_org_name').text(edu.org_name || '');
		$('#detail_category_name').text(edu.category_name || '');
		$('#detail_edu_location').text(edu.edu_location || '');
		$('#detail_edu_start_date').text(edu.edu_start_date ? edu.edu_start_date.substring(0, 10) : '');
		$('#detail_edu_end_date').text(edu.edu_end_date ? edu.edu_end_date.substring(0, 10) : '');

		// JSON 필드 파싱
		let days = '';
		if (edu.edu_days) {
			try {
				const daysArray = typeof edu.edu_days === 'string' ? JSON.parse(edu.edu_days) : edu.edu_days;
				days = Array.isArray(daysArray) ? daysArray.join(', ') : edu.edu_days;
			} catch(e) {
				days = edu.edu_days;
			}
		}
		$('#detail_edu_days').text(days);

		let times = '';
		if (edu.edu_times) {
			try {
				const timesArray = typeof edu.edu_times === 'string' ? JSON.parse(edu.edu_times) : edu.edu_times;
				times = Array.isArray(timesArray) ? timesArray.join(', ') : edu.edu_times;
			} catch(e) {
				times = edu.edu_times;
			}
		}
		$('#detail_edu_times').text(times);

		$('#detail_edu_leader').text(edu.edu_leader || '');
		$('#detail_edu_leader_phone').text(edu.edu_leader_phone || '');
		$('#detail_edu_leader_age').text(edu.edu_leader_age || '');
		$('#detail_edu_leader_gender').text(edu.edu_leader_gender || '');
		$('#detail_edu_desc').text(edu.edu_desc || '');
		$('#detail_edu_fee').text(edu.edu_fee ? edu.edu_fee.toLocaleString() + '원' : '무료');
		$('#detail_edu_capacity').text(edu.edu_capacity > 0 ? edu.edu_capacity + '명' : '제한 없음');
		$('#detail_public_yn').text(edu.public_yn === 'Y' ? '공개' : '비공개');

		// ZOOM/YouTube URL
		if (edu.zoom_url) {
			$('#detail_zoom_url').attr('href', edu.zoom_url).text(edu.zoom_url);
			$('#detail_zoom_section').show();
		} else {
			$('#detail_zoom_section').hide();
		}

		if (edu.youtube_url) {
			$('#detail_youtube_url').attr('href', edu.youtube_url).text(edu.youtube_url);
			$('#detail_youtube_section').show();
		} else {
			$('#detail_youtube_section').hide();
		}
	}

	/**
	 * 신청자 목록 로드
	 */
	function loadApplicantList(eduIdx) {
		$.ajax({
			url: '/mng/mng_education/get_applicant_list',
			type: 'POST',
			data: { edu_idx: eduIdx },
			dataType: 'json',
			success: function(res) {
				if (res.success) {
					applicantGrid.option('dataModel.data', res.data);
					applicantGrid.refreshDataAndView();
					$('#applicant_total_count').text(res.total_count);
					applicantOffcanvas.show();
				} else {
					showToast(res.message || '신청자 목록을 불러올 수 없습니다.', 'error');
				}
			},
			error: function() {
				showToast('신청자 목록 로드 중 오류가 발생했습니다.', 'error');
			}
		});
	}

	/**
	 * 신청자 상태 업데이트
	 */
	function updateApplicantStatus(applicantIdx, status) {
		$.ajax({
			url: '/mng/mng_education/update_applicant_status',
			type: 'POST',
			data: {
				applicant_idx: applicantIdx,
				status: status
			},
			dataType: 'json',
			success: function(res) {
				if (res.success) {
					showToast('상태가 업데이트되었습니다.', 'success');
				} else {
					showToast(res.message || '상태 업데이트에 실패했습니다.', 'error');
				}
			},
			error: function() {
				showToast('상태 업데이트 중 오류가 발생했습니다.', 'error');
			}
		});
	}

	/**
	 * 전역 이벤트 바인딩
	 */
	function bindGlobalEvents() {
		// 양육 상세 링크 클릭
		$(document).on('click', '.edu-detail-link', function(e) {
			e.preventDefault();
			const eduIdx = $(this).data('edu-idx');
			loadEduDetail(eduIdx);
		});

		// 신청자 링크 클릭
		$(document).on('click', '.applicant-link', function(e) {
			e.preventDefault();
			const eduIdx = $(this).data('edu-idx');
			loadApplicantList(eduIdx);
		});

		// 신청자 상태 변경
		$(document).on('change', '.applicant-status-select', function() {
			const applicantIdx = $(this).data('applicant-idx');
			const newStatus = $(this).val();
			updateApplicantStatus(applicantIdx, newStatus);
		});

		// 조직 대시보드 링크 클릭
		$(document).on('click', '.org-dashboard-link', function(e) {
			e.preventDefault();
			const orgId = $(this).data('org-id');
			const orgName = $(this).data('org-name');
			goToOrgDashboard(orgId, orgName);
		});
	}

	/**
	 * 조직 대시보드로 이동 (새 탭)
	 */
	function goToOrgDashboard(orgId, orgName) {
		if (!orgId) {
			showToast('조직 정보를 찾을 수 없습니다.', 'warning');
			return;
		}

		// 로컬스토리지에 조직 정보 저장
		try {
			localStorage.setItem('lastSelectedOrgId', orgId);
			localStorage.setItem('lastSelectedOrgName', orgName || '');
		} catch (e) {
			console.warn('로컬스토리지 저장 실패:', e);
		}

		// 서버에 조직 전환 요청 후 대시보드로 이동
		$.ajax({
			url: '/login/set_default_org',
			type: 'POST',
			data: { org_id: orgId },
			dataType: 'json',
			success: function(response) {
				if (response.success) {
					// 새 탭에서 대시보드 열기
					window.open('/dashboard', '_blank');
					showToast((orgName || '조직') + '(으)로 전환되었습니다', 'success');
				} else {
					showToast(response.message || '조직 전환에 실패했습니다', 'error');
				}
			},
			error: function() {
				showToast('조직 전환 중 오류가 발생했습니다.', 'error');
			}
		});
	}

	/**
	 * Toast 메시지 표시
	 */
	function showToast(message, type) {
		const bgColor = type === 'success' ? 'bg-success' : 'bg-danger';
		const toast = `
			<div class="toast align-items-center text-white ${bgColor} border-0" role="alert">
				<div class="d-flex">
					<div class="toast-body">${escapeHtml(message)}</div>
					<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
				</div>
			</div>
		`;
		const container = $('<div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 9999;"></div>').append(toast);
		$('body').append(container);
		const toastEl = container.find('.toast')[0];
		const bsToast = new bootstrap.Toast(toastEl);
		bsToast.show();
		setTimeout(() => container.remove(), 3000);
	}

	/**
	 * HTML 이스케이프
	 */
	function escapeHtml(text) {
		if (!text) return '';
		const map = {
			'&': '&amp;',
			'<': '&lt;',
			'>': '&gt;',
			'"': '&quot;',
			"'": '&#039;'
		};
		return text.toString().replace(/[&<>"']/g, function(m) { return map[m]; });
	}

})();
