'use strict'

$(document).ready(function () {
	// ===== 전역 변수 영역 =====
	let memberGrid;                    // ParamQuery Grid 인스턴스
	let selectedOrgId = null;          // 선택된 조직 ID
	let selectedAreaIdx = null;        // 선택된 소그룹 ID
	let selectedType = null;           // 선택된 타입 ('org', 'area', 'unassigned')
	let croppieInstance = null;        // Croppie 인스턴스 (전역으로 이동)
	let splitInstance = null;          // Split.js 인스턴스

	// 메모 관련 전역 변수 추가
	let currentMemberIdx = null;       // 현재 선택된 회원 ID
	let editingMemoIdx = null;         // 현재 수정 중인 메모 ID

	// 타임라인 관련 전역 변수 추가
	let currentMemberTimelineIdx = null;       // 현재 선택된 회원 ID (타임라인용)
	let editingTimelineIdx = null;             // 현재 수정 중인 타임라인 ID
	let timelineTypes = [];                    // 조직의 타임라인 호칭 목록


	// 초기화 시도 (지연 로딩)
	setTimeout(function () {
		initializePage();
	}, 800);

	/**
	 * 페이지 초기화 메인 함수 (수정된 버전)
	 */
	function initializePage() {
		showAllSpinners();

		if (typeof $.fn.pqGrid === 'undefined') {
			console.error('ParamQuery 라이브러리가 로드되지 않았습니다.');
			hideAllSpinners();
			showToast('ParamQuery 라이브러리 로드 실패', 'error');
			return;
		}

		if (typeof $.fn.fancytree === 'undefined') {
			console.error('Fancytree 라이브러리가 로드되지 않았습니다.');
			hideAllSpinners();
			showToast('Fancytree 라이브러리 로드 실패', 'error');
			return;
		}

		if (typeof Split === 'undefined') {
			console.error('Split.js 라이브러리가 로드되지 않았습니다.');
			hideAllSpinners();
			showToast('Split.js 라이브러리 로드 실패', 'error');
			return;
		}

		try {
			initializeSplitJS();
			initializeFancytree();
			// initializeParamQuery()는 여기서 호출하지 않고 loadMemberData()에서 처리
			bindGlobalEvents();
			setupCleanupEvents();
			initDetailTab();
			bindMemoTabEvents();
			bindTimelineTabEvents();
		} catch (error) {
			console.error('초기화 중 오류:', error);
			hideAllSpinners();
			showToast('페이지 초기화 중 오류가 발생했습니다.', 'error');
		}
	}




	/**
	 * Split.js 초기화
	 */
	function initializeSplitJS() {


		try {
			splitInstance = Split(['#left-pane', '#right-pane'], {
				sizes: [15, 85],              // 초기 크기 비율 (왼쪽 25%, 오른쪽 75%)
				minSize: [50, 50],          // 최소 크기 (px)
				gutterSize: 7,                // divider 두께
				cursor: 'col-resize',         // 커서 스타일
				direction: 'horizontal',      // 수평 분할
				onDragEnd: function(sizes) {
					// 크기 조정 완료 시 그리드 리프레시
					if (memberGrid) {
						try {
							memberGrid.pqGrid("refresh");
						} catch (error) {
							console.error('그리드 리프레시 실패:', error);
						}
					}
					// 로컬스토리지에 크기 저장 (선택사항)
					localStorage.setItem('member-split-sizes', JSON.stringify(sizes));
				}
			});
			// 이전에 저장된 크기가 있다면 복원
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
	 * 전역 이벤트 바인딩
	 */
	function bindGlobalEvents() {
		// 회원 삭제 버튼
		$('#btnDeleteMember').on('click', function () {
			const selectedMembers = getSelectedMembers();
			if (selectedMembers.length === 0) {
				showToast('삭제할 회원을 선택해주세요.', 'warning');
				return;
			}
			deleteSelectedMembers(selectedMembers);
		});

		// 회원 이동 버튼
		$('#btnMoveMember').on('click', function () {
			const selectedMembers = getSelectedMembers();
			if (selectedMembers.length === 0) {
				showToast('이동할 회원을 선택해주세요.', 'warning');
				return;
			}
			moveSelectedMembers(selectedMembers);
		});

		// 회원 저장 버튼
		$('#btnSaveMember').on('click', function () {
			saveMember();
		});

		// 회원 추가 버튼 (이벤트 위임 사용)
		$(document).on('click', '#btnAddMember', function (e) {
			e.preventDefault();
			handleAddMemberClick();
		});

		// 엑셀 다운로드 버튼 이벤트 추가
		$(document).on('click', '#btnExcelDownload', function (e) {
			e.preventDefault();
			exportMemberToExcel();
		});

		// 회원 검색 기능 바인딩
		bindMemberSearchEvents();

		// 윈도우 리사이즈 이벤트
		$(window).on('resize', debounce(function() {
			if (memberGrid) {
				try {
					memberGrid.pqGrid("refresh");
				} catch (error) {
					console.error('윈도우 리사이즈 시 그리드 리프레시 실패:', error);
				}
			}
		}, 250));
	}

	/**
	 * 타임라인 탭 이벤트 바인딩
	 */
	function bindTimelineTabEvents() {
		// 타임라인 탭 클릭 시 타임라인 목록 로드
		$('#timeline-tab').off('shown.bs.tab').on('shown.bs.tab', function() {
			const memberIdx = $('#member_idx').val();
			if (memberIdx) {
				currentMemberTimelineIdx = memberIdx;
				loadTimelineTypes();
				loadTimelineList(memberIdx);
			}
		});

		// 타임라인 추가 버튼 클릭
		$(document).off('click', '#addTimelineBtn').on('click', '#addTimelineBtn', function() {
			saveTimeline();
		});

		// 타임라인 목록 내 버튼 이벤트 (동적 요소용 이벤트 위임)
		$(document).off('click', '.btn-timeline-edit').on('click', '.btn-timeline-edit', function() {
			const idx = $(this).data('idx');
			const timelineData = getTimelineDataFromElement($(this).closest('.timeline-item'));
			startEditTimeline(idx, timelineData);
		});

		$(document).off('click', '.btn-timeline-delete').on('click', '.btn-timeline-delete', function() {
			const idx = $(this).data('idx');
			showDeleteTimelineModal(idx);
		});

		$(document).off('click', '.btn-timeline-save').on('click', '.btn-timeline-save', function() {
			const idx = $(this).data('idx');
			const timelineData = getTimelineDataFromEditForm($(this).closest('.timeline-item'));
			updateTimeline(idx, timelineData);
		});

		$(document).off('click', '.btn-timeline-cancel').on('click', '.btn-timeline-cancel', function() {
			cancelEditTimeline();
		});

		// Enter 키로 타임라인 추가 기능
		$(document).off('keydown', '#newTimelineContent').on('keydown', '#newTimelineContent', function(e) {
			if (e.ctrlKey && e.keyCode === 13) {
				saveTimeline();
			}
		});

		// 타임라인 수정 중 ESC 키로 취소
		$(document).off('keydown', '.timeline-content-edit').on('keydown', '.timeline-content-edit', function(e) {
			if (e.keyCode === 27) {
				cancelEditTimeline();
			}
		});

		// 타임라인 수정 중 Ctrl + Enter로 저장
		$(document).off('keydown', '.timeline-content-edit').on('keydown', '.timeline-content-edit', function(e) {
			if (e.ctrlKey && e.keyCode === 13) {
				const idx = $(this).closest('.timeline-item').data('idx');
				const timelineData = getTimelineDataFromEditForm($(this).closest('.timeline-item'));
				updateTimeline(idx, timelineData);
			}
		});
	}

	/**
	 * 조직의 타임라인 호칭 목록 로드
	 */
	function loadTimelineTypes() {
		if (!selectedOrgId) return;

		$.ajax({
			url: '/member/get_timeline_types',
			method: 'POST',
			data: { org_id: selectedOrgId },
			dataType: 'json',
			success: function(response) {
				if (response.success) {
					timelineTypes = response.data || [];
					populateTimelineTypeSelect();
				} else {
					console.error('타임라인 호칭 로드 실패:', response.message);
					timelineTypes = [];
					populateTimelineTypeSelect();
				}
			},
			error: function() {
				console.error('타임라인 호칭 로드 중 오류 발생');
				timelineTypes = [];
				populateTimelineTypeSelect();
			}
		});
	}

	/**
	 * 타임라인 타입 선택박스 채우기
	 */
	function populateTimelineTypeSelect() {
		const select = $('#newTimelineType');
		select.html('<option value="">항목 선택</option>');

		timelineTypes.forEach(function(type) {
			select.append(`<option value="${escapeHtml(type)}">${escapeHtml(type)}</option>`);
		});
	}

	/**
	 * 타임라인 목록 로드
	 */
	function loadTimelineList(memberIdx) {
		if (!memberIdx) return;

		$.ajax({
			url: '/member/get_timeline_list',
			method: 'POST',
			data: {
				member_idx: memberIdx,
				org_id: selectedOrgId,
				page: 1,
				limit: 50
			},
			dataType: 'json',
			success: function(response) {
				if (response.success) {
					renderTimelineList(response.data);
				} else {
					showToast('타임라인 목록을 불러오는데 실패했습니다.', 'error');
				}
			},
			error: function() {
				showToast('타임라인 목록을 불러오는데 실패했습니다.', 'error');
			}
		});
	}

	/**
	 * 타임라인 목록 렌더링
	 */
	function renderTimelineList(timelineList) {
		const timelineListContainer = $('#timelineList');
		timelineListContainer.empty();

		if (!timelineList || timelineList.length === 0) {
			timelineListContainer.html('<div class="text-center text-muted py-3" id="emptyTimelineMessage">등록된 타임라인이 없습니다.</div>');
			return;
		}

		// 날짜별로 정렬 (최신순)
		timelineList.sort(function(a, b) {
			return new Date(b.timeline_date) - new Date(a.timeline_date);
		});

		timelineList.forEach(function(timeline, index) {
			const timelineHtml = createTimelineItemHtml(timeline);
			timelineListContainer.append(timelineHtml);

			// 타임라인 연결선 추가 (마지막 항목이 아닌 경우)
			if (index < timelineList.length - 1) {
				timelineListContainer.append('<div class="timeline-connector">|</div>');
			}
		});
	}

	/**
	 * 타임라인 아이템 HTML 생성
	 */
	function createTimelineItemHtml(timeline) {
		const formattedDate = formatTimelineDate(timeline.timeline_date);
		const regDate = formatMemoDateTime(timeline.regi_date);

		return `
			<div class="timeline-item" data-idx="${timeline.idx}">
				<div class="row align-items-center">				
					<div class="col-9">
						<div class="timeline-content">
							<span class="timeline-date">${formattedDate}</span>
							<span class="timeline-type">${escapeHtml(timeline.timeline_type)}</span>
							<span class="timeline-text">${escapeHtml(timeline.timeline_content || '')}</span>
						</div>						
					</div>
					<div class="col-3 d-flex justify-content-end">
						<div class="btn-group">
							<button type="button" class="btn btn-sm btn-outline-secondary btn-timeline-edit" data-idx="${timeline.idx}">수정</button>
							<button type="button" class="btn btn-sm btn-outline-danger btn-timeline-delete" data-idx="${timeline.idx}">삭제</button>
						</div>
					</div>
				</div>
			</div>
		`;
	}

	/**
	 * 타임라인 저장
	 */
	function saveTimeline() {
		const timelineType = $('#newTimelineType').val().trim();
		const timelineDate = $('#newTimelineDate').val();
		const timelineContent = $('#newTimelineContent').val().trim();

		if (!timelineType) {
			showToast('타임라인 항목을 선택해주세요.', 'warning');
			return;
		}

		if (!timelineDate) {
			showToast('날짜를 선택해주세요.', 'warning');
			return;
		}

		if (!currentMemberTimelineIdx) {
			showToast('회원 정보를 찾을 수 없습니다.', 'error');
			return;
		}

		$.ajax({
			url: '/member/save_timeline',
			method: 'POST',
			data: {
				member_idx: currentMemberTimelineIdx,
				timeline_type: timelineType,
				timeline_date: timelineDate,
				timeline_content: timelineContent,
				org_id: selectedOrgId
			},
			dataType: 'json',
			success: function(response) {
				if (response.success) {
					$('#newTimelineType').val('');
					$('#newTimelineDate').val('');
					$('#newTimelineContent').val('');
					loadTimelineList(currentMemberTimelineIdx);
					showToast('타임라인이 저장되었습니다.', 'success');
				} else {
					showToast(response.message || '타임라인 저장에 실패했습니다.', 'error');
				}
			},
			error: function() {
				showToast('타임라인 저장에 실패했습니다.', 'error');
			}
		});
	}

	/**
	 * 타임라인 수정 시작
	 */
	function startEditTimeline(idx, timelineData) {
		editingTimelineIdx = idx;
		const timelineItem = $(`.timeline-item[data-idx="${idx}"]`);

		const editHtml = createTimelineEditHtml(timelineData);
		timelineItem.find('.timeline-content').parent().replaceWith(editHtml);
		timelineItem.find('.btn-group').hide();
	}

	/**
	 * 타임라인 수정 폼 HTML 생성
	 */
	function createTimelineEditHtml(timelineData) {
		let typeOptions = '<option value="">타임라인 항목 선택</option>';
		timelineTypes.forEach(function(type) {
			const selected = type === timelineData.timeline_type ? 'selected' : '';
			typeOptions += `<option value="${escapeHtml(type)}" ${selected}>${escapeHtml(type)}</option>`;
		});

		return `
			<div class="col-8">
				<div class="timeline-edit-form">
					<div class="row mb-2">
						<div class="col-4">
							<select class="form-select form-select-sm timeline-type-edit">
								${typeOptions}
							</select>
						</div>
						<div class="col-4">
							<input type="date" class="form-control form-control-sm timeline-date-edit" value="${timelineData.timeline_date}">
						</div>
						<div class="col-4">
							<input type="text" class="form-control form-control-sm timeline-content-edit" value="${escapeHtml(timelineData.timeline_content || '')}" placeholder="내용 입력">
						</div>
					</div>
					<div class="text-end">
						<button type="button" class="btn btn-sm btn-success btn-timeline-save" data-idx="${editingTimelineIdx}">저장</button>
						<button type="button" class="btn btn-sm btn-secondary btn-timeline-cancel">취소</button>
					</div>
				</div>
			</div>
		`;
	}

	/**
	 * 타임라인 수정 취소
	 */
	function cancelEditTimeline() {
		if (editingTimelineIdx) {
			loadTimelineList(currentMemberTimelineIdx);
			editingTimelineIdx = null;
		}
	}

	/**
	 * 타임라인 업데이트
	 */
	function updateTimeline(idx, timelineData) {
		if (!timelineData.timeline_type.trim()) {
			showToast('타임라인 항목을 선택해주세요.', 'warning');
			return;
		}

		if (!timelineData.timeline_date) {
			showToast('날짜를 선택해주세요.', 'warning');
			return;
		}

		$.ajax({
			url: '/member/update_timeline',
			method: 'POST',
			data: {
				idx: idx,
				timeline_type: timelineData.timeline_type.trim(),
				timeline_date: timelineData.timeline_date,
				timeline_content: timelineData.timeline_content.trim(),
				org_id: selectedOrgId
			},
			dataType: 'json',
			success: function(response) {
				if (response.success) {
					loadTimelineList(currentMemberTimelineIdx);
					editingTimelineIdx = null;
					showToast('타임라인이 수정되었습니다.', 'success');
				} else {
					showToast(response.message || '타임라인 수정에 실패했습니다.', 'error');
				}
			},
			error: function() {
				showToast('타임라인 수정에 실패했습니다.', 'error');
			}
		});
	}

	/**
	 * 타임라인 삭제 확인 모달 표시
	 */
	function showDeleteTimelineModal(idx) {
		const modalHtml = `
			<div class="modal fade" id="deleteTimelineModal" tabindex="-1" aria-labelledby="deleteTimelineModalLabel" aria-hidden="true">
				<div class="modal-dialog">
					<div class="modal-content">
						<div class="modal-header">
							<h5 class="modal-title" id="deleteTimelineModalLabel">타임라인 삭제</h5>
							<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
						</div>
						<div class="modal-body">
							<p>이 타임라인을 삭제하시겠습니까?</p>
						</div>
						<div class="modal-footer">
							<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
							<button type="button" class="btn btn-danger" id="confirmDeleteTimelineBtn" data-idx="${idx}">삭제</button>
						</div>
					</div>
				</div>
			</div>
		`;

		$('#deleteTimelineModal').remove();
		$('body').append(modalHtml);

		$('#confirmDeleteTimelineBtn').on('click', function() {
			const timelineIdx = $(this).data('idx');
			deleteTimeline(timelineIdx);
		});

		$('#deleteTimelineModal').modal('show');
	}

	/**
	 * 타임라인 삭제 실행
	 */
	function deleteTimeline(idx) {
		$.ajax({
			url: '/member/delete_timeline',
			method: 'POST',
			data: {
				idx: idx,
				org_id: selectedOrgId
			},
			dataType: 'json',
			success: function(response) {
				if (response.success) {
					$('#deleteTimelineModal').modal('hide');
					loadTimelineList(currentMemberTimelineIdx);
					showToast('타임라인이 삭제되었습니다.', 'success');
				} else {
					showToast(response.message || '타임라인 삭제에 실패했습니다.', 'error');
				}
			},
			error: function() {
				showToast('타임라인 삭제에 실패했습니다.', 'error');
			}
		});
	}

	/**
	 * 타임라인용 날짜 형식화
	 */
	function formatTimelineDate(dateString) {
		if (!dateString) return '';

		const date = new Date(dateString);
		const year = date.getFullYear();
		const month = String(date.getMonth() + 1).padStart(2, '0');
		const day = String(date.getDate()).padStart(2, '0');

		return `${year}.${month}.${day}`;
	}

	/**
	 * 타임라인 요소에서 데이터 추출
	 */
	function getTimelineDataFromElement(element) {
		const content = element.find('.timeline-content');
		const timelineType = content.find('.timeline-type').text().replace(/[\[\]]/g, '');
		const timelineDate = content.find('.timeline-date').text().replace(/\./g, '-');
		const timelineText = content.find('.timeline-text').text();

		// 날짜 형식 변환 (YYYY.MM.DD -> YYYY-MM-DD)
		const dateParts = timelineDate.split('.');
		let formattedDate = timelineDate;
		if (dateParts.length === 3) {
			formattedDate = `${dateParts[0]}-${dateParts[1].padStart(2, '0')}-${dateParts[2].padStart(2, '0')}`;
		}

		return {
			timeline_type: timelineType,
			timeline_date: formattedDate,
			timeline_content: timelineText
		};
	}

	/**
	 * 수정 폼에서 타임라인 데이터 추출
	 */
	function getTimelineDataFromEditForm(element) {
		return {
			timeline_type: element.find('.timeline-type-edit').val(),
			timeline_date: element.find('.timeline-date-edit').val(),
			timeline_content: element.find('.timeline-content-edit').val()
		};
	}


	function exportMemberToExcel() {
		// 조직이 선택되지 않은 경우
		if (!selectedOrgId) {
			showToast('조직을 먼저 선택해주세요.', 'warning');
			return;
		}

		// 그리드가 초기화되지 않은 경우
		if (!memberGrid) {
			showToast('회원 데이터를 불러온 후 다시 시도해주세요.', 'warning');
			return;
		}

		try {
			// 현재 그리드 데이터 가져오기
			const gridData = memberGrid.pqGrid("option", "dataModel.data");

			if (!gridData || gridData.length === 0) {
				showToast('다운로드할 회원 데이터가 없습니다.', 'info');
				return;
			}

			// 현재 선택된 조직명 가져오기
			const selectedOrgName = $('#selectedOrgName').text().trim() || '회원목록';
			const currentDate = new Date();
			const dateStr = currentDate.getFullYear() +
				String(currentDate.getMonth() + 1).padStart(2, '0') +
				String(currentDate.getDate()).padStart(2, '0');

			const fileName = selectedOrgName.replace(/[^\w가-힣]/g, '_') + '_' + dateStr;

			// ParamQuery Grid의 내장 엑셀 익스포트 기능 사용
			const blob = memberGrid.pqGrid('exportData', {
				format: 'xlsx',
				render: true,
				type: 'blob',
				sheetName: '회원목록',
				noCols: getExcelExportColumns()
			});

			// FileSaver.js를 사용하여 파일 다운로드
			if (typeof saveAs !== 'undefined' && blob) {
				saveAs(blob, fileName + '.xlsx');
				showToast('엑셀 파일 다운로드가 시작되었습니다.', 'success');
			} else {
				// FileSaver가 없는 경우 대체 방법
				const url = URL.createObjectURL(blob);
				const a = document.createElement('a');
				a.href = url;
				a.download = fileName + '.xlsx';
				document.body.appendChild(a);
				a.click();
				document.body.removeChild(a);
				URL.revokeObjectURL(url);
				showToast('엑셀 파일 다운로드가 시작되었습니다.', 'success');
			}

		} catch (error) {
			console.error('엑셀 다운로드 오류:', error);
			showToast('엑셀 다운로드 중 오류가 발생했습니다.', 'error');
		}
	}


	/**
	 * 엑셀 다운로드용 컬럼 설정 (수정된 버전 - 직위/직책 컬럼 추가)
	 */
	function getExcelExportColumns() {
		return [
			{ dataIndx: "member_idx", title: "회원번호" },
			{ dataIndx: "area_name", title: "소그룹" },
			{ dataIndx: "member_name", title: "이름" },
			{ dataIndx: "member_nick", title: "닉네임" },
			{ dataIndx: "position_name", title: "직위/직분" },
			{ dataIndx: "duty_name", title: "직책" },
			{ dataIndx: "member_phone", title: "휴대폰번호" },
			{ dataIndx: "member_birth", title: "생년월일" },
			{ dataIndx: "member_address", title: "주소" },
			{ dataIndx: "member_address_detail", title: "상세주소" },
			{ dataIndx: "member_etc", title: "특이사항" },
			{
				dataIndx: "regi_date",
				title: "등록일",
				exportRender: true
			},
			{
				dataIndx: "modi_date",
				title: "수정일",
				exportRender: true
			}
		];
	}

	/**
	 * 엑셀용 날짜 포맷팅
	 */
	function formatDateForExcel(dateTimeString) {
		if (!dateTimeString) return '';

		try {
			const date = new Date(dateTimeString);
			const year = date.getFullYear();
			const month = String(date.getMonth() + 1).padStart(2, '0');
			const day = String(date.getDate()).padStart(2, '0');
			const hours = String(date.getHours()).padStart(2, '0');
			const minutes = String(date.getMinutes()).padStart(2, '0');

			return `${year}-${month}-${day} ${hours}:${minutes}`;
		} catch (error) {
			return dateTimeString;
		}
	}

	/**
	 * 회원 검색 이벤트 바인딩
	 */
	function bindMemberSearchEvents() {
		const searchInput = $('.card-header input[type="text"]');
		const searchButton = $('#button-search');

		// 검색 버튼 클릭 이벤트만 활성화
		searchButton.on('click', function() {
			const searchText = searchInput.val().trim();
			if (searchText) {
				filterMemberGrid(searchText);
			} else {
				clearMemberGridFilter();
			}
		});

		// Enter 키 이벤트
		searchInput.on('keypress', function(e) {
			if (e.which === 13) {
				e.preventDefault();
				const searchText = $(this).val().trim();
				if (searchText) {
					filterMemberGrid(searchText);
				} else {
					clearMemberGridFilter();
				}
			}
		});

		// 입력 필드 초기화 시 필터 해제
		searchInput.on('input', function() {
			const searchText = $(this).val().trim();
			if (searchText === '') {
				clearMemberGridFilter();
			}
		});
	}

	/**
	 * 회원 그리드 필터링 (간단한 클라이언트 필터링)
	 */
	function filterMemberGrid(searchText) {
		if (!memberGrid) {
			return;
		}

		try {
			if (!searchText) {
				clearMemberGridFilter();
				return;
			}



			// 그리드 데이터 직접 필터링 방식 사용
			const allData = memberGrid.pqGrid("option", "dataModel.data");

			if (!allData || allData.length === 0) {
				showToast('검색할 데이터가 없습니다.', 'info');
				return;
			}

			// 원본 데이터 저장 (첫 검색 시에만)
			if (!window.originalGridData) {
				window.originalGridData = JSON.parse(JSON.stringify(allData));
			}

			// 이름과 휴대폰번호에서 검색
			const filteredData = window.originalGridData.filter(function(member) {
				const memberName = (member.member_name || '').toLowerCase();
				const memberPhone = (member.member_phone || '').toLowerCase();
				const searchLower = searchText.toLowerCase();

				return memberName.includes(searchLower) || memberPhone.includes(searchLower);
			});

			// 필터링된 데이터로 그리드 업데이트
			memberGrid.pqGrid("option", "dataModel.data", filteredData);
			memberGrid.pqGrid("refreshDataAndView");



			// 검색 결과에 따른 toast 메시지 표시
			if (filteredData.length === 0) {
				showToast(`'${searchText}'에 대한 검색 결과가 없습니다.`, 'info');
			} else {
				showToast(`'${searchText}' 검색결과: ${filteredData.length}명`, 'info');
			}

		} catch (error) {
			console.error('그리드 필터링 중 오류:', error);
			showToast('검색 중 오류가 발생했습니다.', 'error');
		}
	}

	/**
	 * 회원 그리드 필터 해제
	 */
	function clearMemberGridFilter() {
		if (!memberGrid) {
			return;
		}

		try {
			// 원본 데이터가 있으면 복원
			if (window.originalGridData) {
				memberGrid.pqGrid("option", "dataModel.data", window.originalGridData);
				memberGrid.pqGrid("refreshDataAndView");
			}

		} catch (error) {
			console.error('그리드 필터 해제 중 오류:', error);
		}
	}



	/**
	 * 검색 상태 초기화
	 */
	function resetMemberSearch() {
		const searchInput = $('.card-header input[type="text"]');
		searchInput.val('');
		clearMemberGridFilter();

		// 검색 타임아웃 초기화
		if (window.searchTimeout) {
			clearTimeout(window.searchTimeout);
		}
	}

	/**
	 * 정리 이벤트 설정
	 */
	function setupCleanupEvents() {
		// 페이지 떠날 때 정리
		$(window).on('beforeunload', function() {
			destroyCroppie();
			destroySplitJS();
		});

		// offcanvas 닫힐 때 정리
		$('#memberOffcanvas').on('hidden.bs.offcanvas', function() {
			destroyCroppie();
			// 메모 관련 정리
			currentMemberIdx = null;
			editingMemoIdx = null;
			$('#deleteMemoModal').remove(); // 메모 삭제 모달 제거
		});
	}



	/**
	 * Fancytree 초기화
	 */
	function initializeFancytree() {


		// 트리 스피너 표시
		showTreeSpinner();

		$.ajax({
			url: window.memberPageData.baseUrl + 'member/get_org_tree',
			method: 'POST',
			dataType: 'json',
			success: function (treeData) {


				if (!treeData || treeData.length === 0) {
					hideTreeSpinner();
					showToast('조직 데이터가 없습니다.', 'warning');
					return;
				}

				setupFancytreeInstance(treeData);
				restoreSelectedGroupFromStorage(treeData);

				// 트리 로딩 완료 후 스피너 숨김
				hideTreeSpinner();

			},
			error: function (xhr, status, error) {
				console.error('그룹 트리 로드 실패:', error);
				console.error('Response:', xhr.responseText);

				// 에러 발생 시 스피너 숨김
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
			activate: function(event, data) {
				const node = data.node;
				const nodeKey = node.key;
				const nodeTitle = node.title;



				// 검색 상태 초기화
				resetMemberSearch();

				// 기존 노드 활성화 처리 함수 호출
				handleTreeNodeActivate(node);
			},
			autoScroll: true,
			keyboard: true,
			focusOnSelect: true
		});
	}


	/**
	 * 트리 노드 활성화 처리
	 */
	function handleTreeNodeActivate(node) {
		const nodeData = node.data;


		// 전역 변수 업데이트
		selectedType = nodeData.type;
		selectedOrgId = nodeData.org_id;
		selectedAreaIdx = nodeData.area_idx || null;

		// 상태 저장 및 UI 업데이트
		saveSelectedGroupToStorage(nodeData);
		updateSelectedOrgName(node.title, nodeData.type);
		loadMemberData();
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
	 * ParamQuery Grid 초기화 (개선된 버전 - 컬럼 순서 변경 이벤트 추가)
	 */
	function initializeParamQuery(detailFields = []) {
		console.log('initializeParamQuery 호출, 상세필드:', detailFields.length, '개');
		showGridSpinner();

		const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)
			|| window.innerWidth <= 768;

		const colModel = createColumnModel(detailFields);
		console.log('생성된 컬럼 모델:', colModel.length, '개');

		const gridOptions = {
			width: "100%",
			height: "100%",
			headerHeight: 35,
			dataModel: {
				data: []
			},
			colModel: colModel,
			selectionModel: {
				type: 'cell',
				mode: 'single'
			},
			scrollModel: {
				autoFit: false,
				horizontal: true,
				vertical: true
			},
			freezeCols: isMobile ? 0 : 4,
			numberCell: { show: false },
			title: false,
			strNoRows: '회원 정보가 없습니다',
			resizable: true,
			sortable: false,
			hoverMode: 'row',
			wrap: false,
			columnBorders: true,
			cellClick: function(event, ui) {
				handleGridCellClick(event, ui);
			},
			cellDblClick: function(event, ui) {
				handleGridCellClick(event, ui);
			},
			complete: function() {
				console.log('그리드 complete 이벤트 발생');
				setTimeout(function() {
					removeDuplicateCheckboxes();
					bindMobileTouchEvents();
				}, 100);
			},
			// 컬럼 순서 변경 이벤트 추가
			columnOrder: function(evt, ui) {
				console.log('컬럼 순서 변경됨');

				// 변경된 컬럼 순서 저장
				setTimeout(function() {
					if (memberGrid) {
						const currentColModel = memberGrid.pqGrid("option", "colModel");
						saveColumnOrder(currentColModel);
						showToast('컬럼 순서가 저장되었습니다.', 'success');
					}
				}, 100);
			}
		};

		try {
			// 기존 그리드 제거
			if (memberGrid) {
				try {
					console.log('기존 그리드 제거 중...');
					memberGrid.pqGrid("destroy");
					memberGrid = null;
				} catch (e) {
					console.warn('기존 그리드 제거 중 경고:', e);
				}
			}

			$("#memberGrid").empty();
			console.log('새 그리드 생성 중...');
			memberGrid = $("#memberGrid").pqGrid(gridOptions);
			window.memberGrid = memberGrid;

			console.log('그리드 초기화 완료');
			hideGridSpinner();
		} catch (error) {
			console.error('ParamQuery Grid 초기화 실패:', error);
			console.error('Error stack:', error.stack);
			hideGridSpinner();
			showToast('그리드 초기화에 실패했습니다.', 'error');
		}
	}

	/**
	 * 그리드 옵션 생성 (개선된 버전)
	 */
	function createGridOptions() {
		showGridSpinner();

		const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)
			|| window.innerWidth <= 768;

		return {
			width: "100%",
			height: "100%",
			headerHeight: 500,
			dataModel: {
				data: []
			},
			colModel: createColumnModel(),
			selectionModel: {
				type: 'cell',
				mode: 'single'
			},
			scrollModel: {
				autoFit: false,
				horizontal: true,
				vertical: true
			},
			freezeCols: isMobile ? 0 : 4,
			numberCell: { show: false },
			title: false,
			strNoRows: '회원 정보가 없습니다',
			resizable: true,
			sortable: false,
			hoverMode: 'row',
			wrap: false,
			columnBorders: true,
			cellClick: function(event, ui) {
				handleGridCellClick(event, ui);
			},
			// 모바일 터치 지원을 위한 추가 이벤트
			cellDblClick: function(event, ui) {
				// 더블클릭도 동일하게 처리
				handleGridCellClick(event, ui);
			},
			// 그리드 렌더링 완료 후 모바일 터치 이벤트 바인딩
			complete: function() {
				setTimeout(function() {
					removeDuplicateCheckboxes();
					bindMobileTouchEvents(); // 모바일 터치 이벤트 추가
				}, 100);
			}
		};
	}

	/**
	 * 모바일 터치 이벤트 바인딩 - ParamQuery Grid 우회 (수정된 버전)
	 */
	function bindMobileTouchEvents() {
		const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)
			|| window.innerWidth <= 768;

		if (!isMobile) return;

		// 그리드 컨테이너에 직접 터치 이벤트 바인딩 (수정된 버전)
		$('#memberGrid').off('touchend.mobile click.mobile')
			.on('touchend.mobile click.mobile', 'td', function(e) {
				e.preventDefault();
				e.stopPropagation();

				const $cell = $(this);
				const $row = $cell.closest('tr');

				// 헤더 행 제외
				if ($row.hasClass('pq-grid-header') || $row.find('th').length > 0) {
					return;
				}

				// 데이터 행인지 확인
				const dataRowIndex = $row.attr('pq-row-indx');
				if (dataRowIndex === undefined || dataRowIndex === null) {
					return;
				}

				const rowIndex = parseInt(dataRowIndex);
				if (rowIndex < 0 || isNaN(rowIndex)) return;

				// 컬럼 인덱스 찾기 (더 정확한 방법)
				const cellIndex = $cell.attr('pq-col-indx');
				if (cellIndex === undefined || cellIndex === null) {
					return;
				}

				const colIndex = parseInt(cellIndex);

				try {
					// 그리드 데이터에서 해당 행 정보 가져오기
					const gridData = memberGrid.pqGrid("option", "dataModel.data");
					const rowData = gridData[rowIndex];

					if (rowData) {


						// 체크박스 컬럼인 경우 (첫 번째 컬럼)
						if (colIndex === 0) {
							handleCheckboxColumnClick(e, rowData.member_idx);
						} else {
							// 일반 셀인 경우 회원 정보 수정 창 열기
							clearTimeout(window.memberCellClickTimeout);
							window.memberCellClickTimeout = setTimeout(function() {
								openMemberOffcanvas('edit', rowData);
							}, 200);
						}
					}
				} catch (error) {
					console.error('모바일 터치 이벤트 처리 오류:', error);
				}
			});


	}

	/**
	 * ParamQuery Grid 컬럼 모델 생성 (수정된 버전 - 컬럼 순서 복원 지원)
	 */
	function createColumnModel(detailFields = []) {
		console.log('createColumnModel 호출, 상세필드:', detailFields);

		const baseColumns = [
			{
				title: '<input type="checkbox" id="selectAllCheckbox" />',
				dataIndx: "pq_selected",
				width: 50,
				align: "center",
				resizable: false,
				sortable: false,
				editable: false,
				menuIcon: false,
				frozen: true,
				render: function (ui) {
					const checkboxId = 'member-checkbox-' + ui.rowData.member_idx;
					return '<input type="checkbox" class="member-checkbox" id="' + checkboxId + '" data-member-idx="' + ui.rowData.member_idx + '" />';
				}
			},
			{
				title: "소그룹",
				dataIndx: "area_name",
				width: 140,
				align: "center",
				editable: false,
				frozen: true
			},
			{
				title: "사진",
				dataIndx: "photo",
				width: 70,
				editable: false,
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
				width: 80,
				editable: false,
				align: "center",
				frozen: true
			},
			{
				title: "직위/직분",
				dataIndx: "position_name",
				width: 100,
				editable: false,
				align: "center"
			},
			{
				title: "직책",
				dataIndx: "duty_name",
				width: 100,
				editable: false,
				align: "center"
			},
			{
				title: "회원번호",
				dataIndx: "member_idx",
				width: 80,
				editable: false,
				align: "center",
				hidden: true
			},
			{
				title: "닉네임",
				dataIndx: "member_nick",
				width: 100,
				editable: false,
				align: "center"
			},
			{
				title: "휴대폰번호",
				dataIndx: "member_phone",
				width: 140,
				editable: false,
				align: "center"
			},
			{
				title: "생년월일",
				dataIndx: "member_birth",
				width: 110,
				editable: false,
				align: "center"
			},
			{
				title: "주소",
				dataIndx: "member_address",
				width: 250,
				editable: false,
				align: "left"
			},
			{
				title: "상세주소",
				dataIndx: "member_address_detail",
				width: 250,
				editable: false,
				align: "left"
			}
		];

		// 상세필드 컬럼 동적 추가
		if (detailFields && Array.isArray(detailFields) && detailFields.length > 0) {
			console.log('상세필드 컬럼 추가 중...');
			detailFields.forEach(function(field) {
				if (field.is_active === 'Y') {
					const fieldColumn = {
						title: field.field_name,
						dataIndx: 'detail_' + field.field_idx,
						width: 150,
						editable: false,
						align: field.field_type === 'textarea' ? 'left' : 'center',
						render: function(ui) {
							return renderDetailFieldValue(ui.cellData, field.field_type);
						}
					};
					baseColumns.push(fieldColumn);
					console.log('상세필드 컬럼 추가:', field.field_name, '(detail_' + field.field_idx + ')');
				}
			});
		}

		// 등록일/수정일 컬럼 추가
		baseColumns.push(
			{
				title: "등록일",
				dataIndx: "regi_date",
				width: 120,
				editable: false,
				align: "center",
				render: function (ui) {
					return formatDateTime(ui.cellData);
				}
			},
			{
				title: "수정일",
				dataIndx: "modi_date",
				width: 120,
				editable: false,
				align: "center",
				render: function (ui) {
					return formatDateTime(ui.cellData);
				}
			}
		);

		console.log('최종 컬럼 모델 개수:', baseColumns.length);

		// 저장된 컬럼 순서가 있으면 재정렬
		const savedOrder = loadColumnOrder();
		if (savedOrder) {
			return reorderColumnModel(baseColumns, savedOrder);
		}

		return baseColumns;
	}

	/**
	 * 날짜시간 포맷팅 유틸리티
	 */
	function formatDateTime(dateTimeString) {
		if (!dateTimeString) return '';

		const date = new Date(dateTimeString);
		const year = date.getFullYear();
		const month = String(date.getMonth() + 1).padStart(2, '0');
		const day = String(date.getDate()).padStart(2, '0');
		const hours = String(date.getHours()).padStart(2, '0');
		const minutes = String(date.getMinutes()).padStart(2, '0');
		const seconds = String(date.getSeconds()).padStart(2, '0');

		return `${year}-${month}-${day}<br>${hours}:${minutes}:${seconds}`;
	}

	/**
	 * 그리드 셀 클릭 처리 - 모바일/PC 통합 개선
	 */
	function handleGridCellClick(event, ui) {


		const colIndx = ui.colIndx;
		const rowData = ui.rowData;
		const memberIdx = rowData.member_idx;

		// 모바일에서는 직접 터치 이벤트로 처리되므로 중복 실행 방지
		const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
		if (isMobile && event.originalEvent && event.originalEvent.type === 'touchend') {

			return;
		}

		// 체크박스 컬럼인 경우
		if (colIndx === 0) {
			handleCheckboxColumnClick(event, memberIdx);
			return;
		}

		// 기타 컬럼인 경우 - 회원 정보 수정 창 열기
		clearTimeout(window.memberCellClickTimeout);
		window.memberCellClickTimeout = setTimeout(function() {
			openMemberOffcanvas('edit', rowData);
		}, 200);
	}

	/**
	 * 체크박스 컬럼 클릭 처리 - 모바일 터치 지원 강화
	 */
	function handleCheckboxColumnClick(event, memberIdx) {


		// 직접 체크박스를 클릭한 경우가 아니라면 체크박스 토글
		const isDirectCheckboxClick = $(event.target).hasClass('member-checkbox') ||
			$(event.originalEvent?.target).hasClass('member-checkbox');

		if (!isDirectCheckboxClick) {
			const checkbox = $('.member-checkbox[data-member-idx="' + memberIdx + '"]').first();
			if (checkbox.length > 0) {
				const isCurrentlyChecked = checkbox.is(':checked');
				checkbox.prop('checked', !isCurrentlyChecked);

			}
		}

		// 체크박스 상태 업데이트
		updateSelectAllCheckbox();
		updateSelectedMemberButtons();
	}

	/**
	 * 체크박스 이벤트 바인딩 (최종 개선 버전)
	 */
	function bindCheckboxEvents() {


		// 기존 이벤트 완전히 제거
		$(document).off('change', '#selectAllCheckbox');
		$(document).off('change', '.member-checkbox');

		// 전체 선택 체크박스 이벤트
		$(document).on('change', '#selectAllCheckbox', function (e) {
			e.stopPropagation();

			const isChecked = $(this).is(':checked');


			// 실제 존재하는 고유 체크박스만 선택
			const uniqueCheckboxes = getUniqueCheckboxes();


			uniqueCheckboxes.forEach(function(checkbox) {
				$(checkbox).prop('checked', isChecked);
			});

			updateSelectedMemberButtons();
		});

		// 개별 체크박스 이벤트
		$(document).on('change', '.member-checkbox', function (e) {
			e.stopPropagation();
			updateSelectAllCheckbox();
			updateSelectedMemberButtons();
		});


	}


	/**
	 * 고유한 체크박스만 가져오기
	 */
	function getUniqueCheckboxes() {
		const uniqueCheckboxes = [];
		const seenMemberIds = new Set();

		$('.member-checkbox').each(function() {
			const memberIdx = $(this).data('member-idx');
			if (!seenMemberIds.has(memberIdx)) {
				seenMemberIds.add(memberIdx);
				uniqueCheckboxes.push(this);
			}
		});

		return uniqueCheckboxes;
	}

	/**
	 * 전체 선택 체크박스 상태 업데이트
	 */
	function updateSelectAllCheckbox() {
		const totalCheckboxes = $('.member-checkbox').length;
		const checkedCheckboxes = $('.member-checkbox:checked').length;
		const selectAllCheckbox = $('#selectAllCheckbox');

		if (checkedCheckboxes === 0) {
			selectAllCheckbox.prop('checked', false).prop('indeterminate', false);
		} else if (checkedCheckboxes === totalCheckboxes) {
			selectAllCheckbox.prop('checked', true).prop('indeterminate', false);
		} else {
			selectAllCheckbox.prop('checked', false).prop('indeterminate', true);
		}
	}


	/**
	 * 선택된 회원 기반 버튼 상태 업데이트 (최종 버전)
	 */
	function updateSelectedMemberButtons() {
		// 고유 체크박스 중 체크된 것만 카운트
		const uniqueCheckedBoxes = [];
		const seenMemberIds = new Set();

		$('.member-checkbox:checked').each(function() {
			const memberIdx = $(this).data('member-idx');
			if (!seenMemberIds.has(memberIdx)) {
				seenMemberIds.add(memberIdx);
				uniqueCheckedBoxes.push(this);
			}
		});

		const checkedCount = uniqueCheckedBoxes.length;

		$('#btnDeleteMember').prop('disabled', checkedCount === 0);
		$('#btnMoveMember').prop('disabled', checkedCount === 0);
	}


	/**
	 * 선택된 회원들 이동
	 */
	function moveSelectedMembers(selectedMembers) {
		const memberCount = selectedMembers.length;
		const tree = $("#groupTree").fancytree("getTree");
		const activeNode = tree.getActiveNode();

		if (!activeNode) {
			showToast('그룹을 선택해주세요.', 'warning');
			return;
		}


		// 이동 확인 메시지
		const message = `선택한 ${memberCount}명의 회원을 다른 소그룹으로 이동하시겠습니까?`;

		// 모달에 메시지 설정 및 소그룹 옵션 로드
		$('#moveMessage').text(message);
		loadMoveAreaOptions(selectedOrgId, function() {
			setupMoveConfirmButton(selectedMembers);
			$('#moveMemberModal').modal('show');
		});
	}

	/**
	 * 이동 모달용 소그룹 옵션 로드
	 */
	function loadMoveAreaOptions(orgId, callback) {
		const areaSelect = $('#moveToAreaIdx');
		areaSelect.html('<option value="">소그룹 선택</option>');

		try {
			const tree = $("#groupTree").fancytree("getTree");
			const groupNode = tree.getNodeByKey('org_' + orgId);

			if (groupNode && groupNode.children) {
				function addAreaOptionsRecursively(nodes, depth = 0) {
					nodes.forEach(function(node) {
						const areaData = node.data;
						if (areaData.type === 'area') {
							const indent = '　'.repeat(depth);
							const optionText = indent + node.title.replace(/\s*\(\d+명\)$/, '');
							areaSelect.append(`<option value="${areaData.area_idx}">${optionText}</option>`);

							if (node.children && node.children.length > 0) {
								addAreaOptionsRecursively(node.children, depth + 1);
							}
						}
					});
				}

				addAreaOptionsRecursively(groupNode.children);
			}

			if (typeof callback === 'function') {
				callback();
			}
		} catch (error) {
			console.error('이동용 소그룹 옵션 로드 오류:', error);
			if (typeof callback === 'function') {
				callback();
			}
		}
	}

	/**
	 * 이동 확인 버튼 설정
	 */
	function setupMoveConfirmButton(selectedMembers) {
		$('#confirmMoveBtn').off('click').on('click', function() {
			const moveToAreaIdx = $('#moveToAreaIdx').val();

			if (!moveToAreaIdx) {
				showToast('이동할 소그룹을 선택해주세요.', 'warning');
				return;
			}

			executeMemberMove(selectedMembers, moveToAreaIdx);
			$('#moveMemberModal').modal('hide');
		});
	}

	/**
	 * 실제 회원 이동 실행
	 */
	function executeMemberMove(selectedMembers, moveToAreaIdx) {
		const memberIndices = selectedMembers.map(member => member.member_idx);

		$('#confirmMoveBtn').prop('disabled', true).html('<i class="bi bi-hourglass-split"></i> 이동 중...');

		$.ajax({
			url: window.memberPageData.baseUrl + 'member/move_members',
			method: 'POST',
			data: {
				member_indices: memberIndices,
				move_to_area_idx: moveToAreaIdx,
				org_id: selectedOrgId
			},
			dataType: 'json',
			success: function (response) {
				const toastType = response.success ? 'success' : 'error';
				showToast(response.message, toastType);
				if (response.success) {
					loadMemberData();
					refreshGroupTree();
					$('.member-checkbox').prop('checked', false);
					updateSelectAllCheckbox();
					updateSelectedMemberButtons();
				}
			},
			error: function (xhr, status, error) {
				console.error('회원 이동 실패:', error);
				showToast('회원 이동에 실패했습니다.', 'error');
			},
			complete: function() {
				$('#confirmMoveBtn').prop('disabled', false).html('이동');
			}
		});
	}





	/**
	 * 회원 추가 버튼 클릭 처리
	 */
	function handleAddMemberClick() {
		const tree = $("#groupTree").fancytree("getTree");
		const activeNode = tree.getActiveNode();

		if (!activeNode) {
			showToast('그룹을 선택해주세요.', 'warning');
			return;
		}

		const nodeData = activeNode.data;

		if (nodeData.type === 'org') {
			showToast('가장 상위 그룹에서는 회원을 추가할 수 없습니다. 하위 그룹을 선택해주세요.', 'warning');
			return;
		}

		addNewMember(nodeData);
	}

	/**
	 * 새 회원 추가
	 */
	function addNewMember(nodeData) {
		const addData = {
			org_id: nodeData.org_id,
			area_idx: nodeData.area_idx || null
		};

		$.ajax({
			url: '/member/add_member',
			type: 'POST',
			data: addData,
			dataType: 'json',
			success: function (response) {
				if (response.success) {
					showToast('회원이 추가되었습니다: ' + response.member_name, 'success');
					loadMemberData();
					refreshGroupTree();
				} else {
					showToast(response.message || '회원 추가에 실패했습니다.', 'error');
				}
			},
			error: function (xhr, status, error) {
				console.error('회원 추가 오류:', error);
				showToast('회원 추가 중 오류가 발생했습니다.', 'error');
			}
		});
	}

	/**
	 * 선택된 회원들 삭제
	 */
	function deleteSelectedMembers(selectedMembers) {
		const memberCount = selectedMembers.length;
		const tree = $("#groupTree").fancytree("getTree");
		const activeNode = tree.getActiveNode();

		if (!activeNode) {
			showToast('그룹을 선택해주세요.', 'warning');
			return;
		}

		const nodeData = activeNode.data;
		const isUnassigned = nodeData.type === 'unassigned';

		// 삭제 확인 메시지
		const message = isUnassigned
			? `미분류에서의 삭제는 복원이 불가합니다. 정말로 ${memberCount}명의 회원을 삭제하시겠습니까?`
			: `정말로 ${memberCount}명의 회원을 삭제하시겠습니까?(미분류로 이동)`;

		// 모달에 메시지 설정 및 표시
		$('#deleteMessage').text(message);
		setupDeleteConfirmButton(selectedMembers, isUnassigned);
		$('#deleteMemberModal').modal('show');
	}

	/**
	 * 삭제 확인 버튼 설정
	 */
	function setupDeleteConfirmButton(selectedMembers, isUnassigned) {
		$('#confirmDeleteBtn').off('click').on('click', function() {
			const deleteType = isUnassigned ? 'unassigned' : 'area';
			executeMemberDelete(selectedMembers, deleteType);
			$('#deleteMemberModal').modal('hide');
		});
	}

	/**
	 * 실제 회원 삭제 실행
	 */
	function executeMemberDelete(selectedMembers, deleteType) {
		const memberIndices = selectedMembers.map(member => member.member_idx);

		$.ajax({
			url: window.memberPageData.baseUrl + 'member/delete_members',
			method: 'POST',
			data: {
				member_indices: memberIndices,
				delete_type: deleteType
			},
			dataType: 'json',
			success: function (response) {
				const toastType = response.success ? 'success' : 'error';
				showToast(response.message, toastType);
				if (response.success) {
					loadMemberData();
					refreshGroupTree();
				}
			},
			error: function (xhr, status, error) {
				console.error('회원 삭제 실패:', error);
				showToast('회원 삭제에 실패했습니다.', 'error');
			}
		});
	}



	/**
	 * 그룹 트리 새로고침
	 */
	function refreshGroupTree() {

		// 현재 선택된 트리 정보 저장
		const tree = $("#groupTree").fancytree("getTree");
		const activeNode = tree ? tree.getActiveNode() : null;
		const currentSelection = activeNode ? {
			key: activeNode.key,
			type: activeNode.data.type,
			org_id: activeNode.data.org_id,
			area_idx: activeNode.data.area_idx
		} : null;

		// 트리 새로고침 시 스피너 표시
		showTreeSpinner();

		$.ajax({
			url: window.memberPageData.baseUrl + 'member/get_org_tree',
			method: 'POST',
			dataType: 'json',
			success: function (treeData) {


				if (!treeData || treeData.length === 0) {
					hideTreeSpinner();
					showToast('조직 데이터가 없습니다.', 'warning');
					return;
				}

				$("#groupTree").fancytree("destroy");
				setupFancytreeInstance(treeData);

				if (currentSelection) {
					restoreTreeSelection(currentSelection);
				}

				// 트리 새로고침 완료 후 스피너 숨김
				hideTreeSpinner();

			},
			error: function (xhr, status, error) {
				console.error('그룹 트리 새로고침 실패:', error);

				// 에러 발생 시 트리 스피너 숨김
				hideTreeSpinner();
				showToast('그룹 정보 새로고침에 실패했습니다.', 'error');
			}
		});
	}

	/**
	 * 트리 선택 상태 복원
	 */
	function restoreTreeSelection(selection) {
		try {
			const tree = $("#groupTree").fancytree("getTree");
			let nodeToSelect = null;

			if (selection.key) {
				nodeToSelect = tree.getNodeByKey(selection.key);
			}

			if (!nodeToSelect) {
				if (selection.type === 'unassigned' && selection.org_id) {
					nodeToSelect = tree.getNodeByKey('unassigned_' + selection.org_id);
				} else if (selection.type === 'area' && selection.area_idx) {
					nodeToSelect = tree.getNodeByKey('area_' + selection.area_idx);
				} else if (selection.type === 'org' && selection.org_id) {
					nodeToSelect = tree.getNodeByKey('org_' + selection.org_id);
				}
			}

			if (nodeToSelect) {
				nodeToSelect.setActive(true);
				nodeToSelect.setFocus(true);

				if (nodeToSelect.parent && !nodeToSelect.parent.isRootNode()) {
					nodeToSelect.parent.setExpanded(true);
				}


			} else {

				selectFirstOrganization();
			}
		} catch (error) {
			console.error('트리 선택 상태 복원 실패:', error);
			selectFirstOrganization();
		}
	}



	/**
	 * 회원 데이터 로드 (상세필드 포함)
	 */
	function loadMemberData() {
		if (!selectedOrgId) return;

		showGridSpinner();

		$.ajax({
			url: window.memberPageData.baseUrl + 'member/get_members',
			method: 'POST',
			data: {
				type: selectedType,
				org_id: selectedOrgId,
				area_idx: selectedAreaIdx
			},
			dataType: 'json',
			success: function (response) {
				// console.log('회원 데이터 응답:', response);
				handleMemberDataResponse(response);
			},
			error: function (xhr, status, error) {
				console.error('회원 데이터 로드 실패:', error);
				hideGridSpinner();
				showToast('회원 데이터를 불러오는데 실패했습니다.', 'error');
			}
		});
	}


	/**
	 * 회원 데이터 응답 처리 (수정된 버전)
	 */
	function handleMemberDataResponse(response) {
		// console.log('handleMemberDataResponse 시작');

		if (!response.success) {
			console.error('회원 데이터 로드 실패:', response.message);
			hideGridSpinner();
			showToast('회원 데이터를 불러오는데 실패했습니다.', 'error');
			return;
		}



		// 그리드가 없거나 상세필드 구조가 변경된 경우에만 재생성
		if (!memberGrid) {

			initializeParamQuery(response.detail_fields || []);
		} else if (response.detail_fields && response.detail_fields.length > 0) {
			// 기존 컬럼 구조와 비교
			const currentCols = memberGrid.pqGrid("option", "colModel");
			const needsRebuild = checkIfColumnsChanged(currentCols, response.detail_fields);

			if (needsRebuild) {
				// console.log('상세필드 구조가 변경되어 그리드 재생성');
				initializeParamQuery(response.detail_fields || []);
			}
		}

		// 데이터 업데이트
		if (memberGrid) {
			try {
				// console.log('그리드에 데이터 설정 중...');

				// 먼저 데이터 초기화
				memberGrid.pqGrid("option", "dataModel.data", []);
				memberGrid.pqGrid("refreshDataAndView");

				// 잠시 대기 후 실제 데이터 설정
				setTimeout(function() {
					// console.log('실제 데이터 설정:', response.data.length, '건');
					memberGrid.pqGrid("option", "dataModel.data", response.data || []);
					memberGrid.pqGrid("refreshDataAndView");

					// 체크박스 관련 처리
					setTimeout(function() {
						removeDuplicateCheckboxes();
						$('.member-checkbox').prop('checked', false);
						$('#selectAllCheckbox').prop('checked', false);
						bindCheckboxEvents();
						bindMobileTouchEvents();

						// console.log('그리드 데이터 로딩 완료');
					}, 100);
				}, 50);

			} catch (error) {
				console.error('그리드 데이터 업데이트 실패:', error);
				showToast('데이터 표시 중 오류가 발생했습니다.', 'error');
			}
		} else {
			console.error('memberGrid 인스턴스가 없습니다!');
			showToast('그리드 초기화 오류', 'error');
		}

		$('#btnDeleteMember').prop('disabled', true);
		hideGridSpinner();
	}

	/**
	 * 컬럼 구조 변경 확인
	 */
	function checkIfColumnsChanged(currentCols, detailFields) {
		if (!currentCols || !detailFields) return false;

		// 현재 그리드의 상세필드 컬럼 개수 확인
		const currentDetailCols = currentCols.filter(col =>
			col.dataIndx && col.dataIndx.startsWith('detail_')
		);

		// 활성화된 상세필드 개수 확인
		const activeDetailFields = detailFields.filter(field => field.is_active === 'Y');

		// console.log('현재 상세 컬럼:', currentDetailCols.length, '개');
		// console.log('새로운 상세필드:', activeDetailFields.length, '개');

		// 개수가 다르면 재생성 필요
		return currentDetailCols.length !== activeDetailFields.length;
	}


	/**
	 * 중복 체크박스 제거 함수
	 */
	function removeDuplicateCheckboxes() {
		const seenMemberIds = new Set();
		const checkboxesToRemove = [];

		$('.member-checkbox').each(function() {
			const memberIdx = $(this).data('member-idx');

			if (seenMemberIds.has(memberIdx)) {
				// 중복된 체크박스 발견
				checkboxesToRemove.push(this);

			} else {
				seenMemberIds.add(memberIdx);
			}
		});

		// 중복된 체크박스들 제거
		checkboxesToRemove.forEach(function(checkbox) {
			$(checkbox).closest('td').html(''); // 해당 셀을 비움
		});

	}

	/**
	 * 선택된 조직명 업데이트
	 */
	function updateSelectedOrgName(title, type) {
		const orgNameElement = $('#selectedOrgName');
		if (!orgNameElement.length) return;

		let displayText = '';
		switch(type) {
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

		orgNameElement.text(displayText);
	}


	/**
	 * 회원 정보 수정 모달 열기 (수정된 버전 - 상세필드 미리 로드)
	 */
	function openMemberOffcanvas(mode, memberData = null) {
		const offcanvas = $('#memberOffcanvas');
		const title = generateOffcanvasTitle(mode, memberData);

		$('#memberOffcanvasLabel').text(title);

		// 폼 및 UI 초기화
		resetOffcanvasForm();

		// 탭 초기화 - 항상 첫 번째 탭(회원정보)을 활성화
		resetTabsToFirst();

		if (mode === 'edit' && memberData) {
			// 소그룹 옵션과 직위/직책 옵션을 모두 로드한 후 데이터 채우기
			loadAreaOptionsWithCallback(selectedOrgId, function() {
				loadPositionsAndDuties(selectedOrgId, function() {
					populateFormData(memberData);
					// 메모 관련 초기화
					currentMemberIdx = memberData.member_idx;
					currentMemberTimelineIdx = memberData.member_idx;

					// 회원정보 탭에 표시될 상세필드 로드
					loadDetailFields(selectedOrgId, memberData.member_idx);
				});
			});
		} else {
			// 새 회원 추가 시에도 옵션들 로드
			loadAreaOptions(selectedOrgId);
			loadPositionsAndDuties(selectedOrgId);

			// 새 회원 추가 시에도 빈 상세필드 폼 로드
			if (selectedOrgId) {
				loadDetailFields(selectedOrgId, null);
			}
		}

		// 사진 이벤트 바인딩
		bindPhotoEvents();

		// offcanvas 표시 및 정리 이벤트 설정
		showOffcanvasWithCleanup(offcanvas);
	}

	/**
	 * 탭을 첫 번째 탭(회원정보)으로 초기화
	 */
	function resetTabsToFirst() {
		$('#memberTab .nav-link').removeClass('active');
		$('#memberTabContent .tab-pane').removeClass('show active');
		$('#profile-tab').addClass('active').attr('aria-selected', 'true');
		$('#profile-tab-pane').addClass('show active');
		$('#detail-tab, #memo-tab').attr('aria-selected', 'false');
		$('#detail-tab, #timeline-tab, #memo-tab').attr('aria-selected', 'false');
	}

	/**
	 * 콜백을 지원하는 소그룹 옵션 로드 함수
	 */
	function loadAreaOptionsWithCallback(orgId, callback) {
		const areaSelect = $('#area_idx');
		areaSelect.html('<option value="">소그룹 선택</option>');

		try {
			const tree = $("#groupTree").fancytree("getTree");
			const groupNode = tree.getNodeByKey('org_' + orgId);

			if (groupNode && groupNode.children) {
				function addAreaOptionsRecursively(nodes, depth = 0) {
					nodes.forEach(function(node) {
						const areaData = node.data;
						if (areaData.type === 'area') {
							const indent = '　'.repeat(depth);
							const optionText = indent + node.title.replace(/\s*\(\d+명\)$/, '');
							areaSelect.append(`<option value="${areaData.area_idx}">${optionText}</option>`);

							if (node.children && node.children.length > 0) {
								addAreaOptionsRecursively(node.children, depth + 1);
							}
						}
					});
				}

				addAreaOptionsRecursively(groupNode.children);
			}

			if (typeof callback === 'function') {
				callback();
			}
		} catch (error) {
			console.error('소그룹 옵션 로드 오류:', error);
			if (typeof callback === 'function') {
				callback();
			}
		}
	}

	/**
	 * 기존 소그룹 옵션 로드 함수 (기존 호환성 유지)
	 */
	function loadAreaOptions(orgId) {
		loadAreaOptionsWithCallback(orgId, null);
	}

	/**
	 * Offcanvas 제목 생성
	 */
	function generateOffcanvasTitle(mode, memberData) {
		if (mode === 'add') {
			return '회원 추가';
		}

		if (mode === 'edit' && memberData && memberData.member_name) {
			return memberData.member_name + ' 회원 정보 수정';
		}

		return '회원 정보 수정';
	}

	/**
	 * Offcanvas 폼 초기화 (수정된 버전)
	 */
	function resetOffcanvasForm() {
		$('#memberForm')[0].reset();
		$('#photoPreview').hide();
		$('#photoUpload').show();
		$('#cropContainer').hide();

		$('#delete_photo').remove();
		$('input[name="detail_field"]').remove(); // 상세필드 히든 필드도 제거
		$('#detail-front').empty().hide();
		$('#detailFieldsContainer').empty();

		// 메모 관련 초기화
		currentMemberIdx = null;
		editingMemoIdx = null;
		$('#newMemoContent').val('');
		$('#memoList').empty();

		// 타임라인 관련 초기화
		currentMemberTimelineIdx = null;
		editingTimelineIdx = null;
		$('#newTimelineType').val('');
		$('#newTimelineDate').val('');
		$('#newTimelineContent').val('');
		$('#timelineList').empty();
		timelineTypes = [];

		destroyCroppie();
	}


	/**
	 * 폼 데이터 채우기 (수정된 버전 - 직위/직책 필드 포함)
	 */
	function populateFormData(memberData) {
		const fieldMappings = {
			'member_idx': memberData.member_idx,
			'member_name': memberData.member_name,
			'member_nick': memberData.member_nick || '',
			'position_name': memberData.position_name || '',
			'duty_name': memberData.duty_name || '',
			'member_phone': memberData.member_phone || '',
			'member_birth': memberData.member_birth || '',
			'member_address': memberData.member_address || '',
			'member_address_detail': memberData.member_address_detail || '',
			'member_etc': memberData.member_etc || '',
			'grade': memberData.grade || 0,
			'area_idx': memberData.area_idx || '',
			'org_id': memberData.org_id
		};

		Object.keys(fieldMappings).forEach(function(fieldName) {
			const element = $('#' + fieldName);
			if (element.length) {
				element.val(fieldMappings[fieldName]);
			}
		});

		$('#leader_yn').prop('checked', memberData.leader_yn === 'Y');
		$('#new_yn').prop('checked', memberData.new_yn === 'Y');

		if (memberData.photo && memberData.photo !== '/assets/images/photo_no.png') {
			$('#previewImage').attr('src', memberData.photo);
			$('#photoPreview').show();
			$('#photoUpload').hide();
		}
	}
	/**
	 * Offcanvas 표시 및 정리 이벤트 설정
	 */
	function showOffcanvasWithCleanup(offcanvas) {
		const offcanvasInstance = new bootstrap.Offcanvas(offcanvas[0]);
		offcanvasInstance.show();

		offcanvas.off('hidden.bs.offcanvas.croppie').on('hidden.bs.offcanvas.croppie', function() {
			destroyCroppie();
		});
	}


	/**
	 * 메모 탭 이벤트 바인딩
	 */
	function bindMemoTabEvents() {
		// 메모 탭 클릭 시 메모 목록 로드
		$('#memo-tab').off('shown.bs.tab').on('shown.bs.tab', function() {
			const memberIdx = $('#member_idx').val();
			if (memberIdx) {
				currentMemberIdx = memberIdx;
				loadMemoList(memberIdx);
			}
		});

		// 메모 추가 버튼 클릭
		$(document).off('click', '#addMemoBtn').on('click', '#addMemoBtn', function() {
			saveMemo();
		});

		// 메모 목록 내 버튼 이벤트 (동적 요소용 이벤트 위임)
		$(document).off('click', '.btn-memo-edit').on('click', '.btn-memo-edit', function() {
			const idx = $(this).data('idx');
			const content = $(this).closest('.memo-item').find('.memo-content').text().trim();
			startEditMemo(idx, content);
		});

		$(document).off('click', '.btn-memo-delete').on('click', '.btn-memo-delete', function() {
			const idx = $(this).data('idx');
			showDeleteMemoModal(idx);
		});

		$(document).off('click', '.btn-memo-save').on('click', '.btn-memo-save', function() {
			const idx = $(this).data('idx');
			const content = $(this).closest('.memo-item').find('.memo-content-edit').val();
			updateMemo(idx, content);
		});

		$(document).off('click', '.btn-memo-cancel').on('click', '.btn-memo-cancel', function() {
			cancelEditMemo();
		});

		// Enter 키로 메모 추가 기능
		$(document).off('keydown', '#newMemoContent').on('keydown', '#newMemoContent', function(e) {
			if (e.ctrlKey && e.keyCode === 13) {
				saveMemo();
			}
		});

		// 메모 수정 중 ESC 키로 취소
		$(document).off('keydown', '.memo-content-edit').on('keydown', '.memo-content-edit', function(e) {
			if (e.keyCode === 27) {
				cancelEditMemo();
			}
		});

		// 메모 수정 중 Ctrl + Enter로 저장
		$(document).off('keydown', '.memo-content-edit').on('keydown', '.memo-content-edit', function(e) {
			if (e.ctrlKey && e.keyCode === 13) {
				const idx = $(this).closest('.memo-item').data('idx');
				const content = $(this).val();
				updateMemo(idx, content);
			}
		});
	}

	/**
	 * 메모 목록 로드
	 */
	function loadMemoList(memberIdx) {
		if (!memberIdx) return;

		$.ajax({
			url: '/member/get_memo_list',
			method: 'POST',
			data: {
				member_idx: memberIdx,
				org_id: selectedOrgId,
				page: 1,
				limit: 20
			},
			dataType: 'json',
			success: function(response) {
				if (response.success) {
					renderMemoList(response.data);
				} else {
					showToast('메모 목록을 불러오는데 실패했습니다.', 'error');
				}
			},
			error: function() {
				showToast('메모 목록을 불러오는데 실패했습니다.', 'error');
			}
		});
	}

	/**
	 * 메모 목록 렌더링
	 */
	function renderMemoList(memoList) {
		const memoListContainer = $('#memoList');
		memoListContainer.empty();

		if (!memoList || memoList.length === 0) {
			memoListContainer.html('<div class="text-center text-muted py-3" id="emptyMemoMessage">등록된 메모가 없습니다.</div>');
			return;
		}


		memoList.forEach(function(memo, index) {
			const memoHtml = createMemoItemHtml(memo);
			memoListContainer.append(memoHtml);

			if (index < memoList.length - 1) {
				memoListContainer.append('<div class="border-bottom my-2"></div>');
			}
		});
	}

	/**
	 * 메모 아이템 HTML 생성
	 */
	function createMemoItemHtml(memo) {
		const formattedDate = formatMemoDateTime(memo.regi_date);

		return `
			<div class="memo-item" data-idx="${memo.idx}">				
				<div class="row">
					<div class="col-9">
						<div class="memo-content">${escapeHtml(memo.memo_content)}</div>
						<span class="text-muted fs-6" style="font-size: 12px!important; color: #ff6400!important;">${formattedDate}</span>
					</div>
					
					<div class="memo-actions col-3 d-flex align-items-center justify-content-end">
						<div class="btn-group">
							<button type="button" class="btn btn-sm btn-outline-secondary d-flex align-items-center justify-content-end btn-memo-edit" data-idx="${memo.idx}">수정</button>
							<button type="button" class="btn btn-sm btn-outline-danger d-flex align-items-center justify-content-end btn-memo-delete" data-idx="${memo.idx}">삭제</button>
						</div>
					</div>
				</div>				
			</div>
		`;
	}

	/**
	 * 메모 저장
	 */
	function saveMemo() {
		const content = $('#newMemoContent').val().trim();

		if (!content) {
			showToast('메모 내용을 입력해주세요.', 'warning');
			return;
		}

		if (!currentMemberIdx) {
			showToast('회원 정보를 찾을 수 없습니다.', 'error');
			return;
		}

		$.ajax({
			url: '/member/save_memo',
			method: 'POST',
			data: {
				member_idx: currentMemberIdx,
				memo_content: content,
				memo_type: 1,
				org_id: selectedOrgId
			},
			dataType: 'json',
			success: function(response) {
				if (response.success) {
					$('#newMemoContent').val('');
					loadMemoList(currentMemberIdx);
					showToast('메모가 저장되었습니다.', 'success');
				} else {
					showToast(response.message || '메모 저장에 실패했습니다.', 'error');
				}
			},
			error: function() {
				showToast('메모 저장에 실패했습니다.', 'error');
			}
		});
	}

	/**
	 * 메모 수정 시작
	 */
	function startEditMemo(idx, currentContent) {
		editingMemoIdx = idx;
		const memoItem = $(`.memo-item[data-idx="${idx}"]`);

		const editHtml = `
			<div class="memo-content-edit-wrapper">
				<textarea class="form-control memo-content-edit" rows="3">${escapeHtml(currentContent)}</textarea>
				<div class="mt-2 text-end">
					<button type="button" class="btn btn-sm btn-success btn-memo-save" data-idx="${idx}">저장</button>
					<button type="button" class="btn btn-sm btn-secondary btn-memo-cancel">취소</button>
				</div>
			</div>
		`;

		memoItem.find('.memo-content').replaceWith(editHtml);
		memoItem.find('.memo-actions').hide();
	}

	/**
	 * 메모 수정 취소
	 */
	function cancelEditMemo() {
		if (editingMemoIdx) {
			loadMemoList(currentMemberIdx);
			editingMemoIdx = null;
		}
	}

	/**
	 * 메모 업데이트
	 */
	function updateMemo(idx, content) {
		if (!content.trim()) {
			showToast('메모 내용을 입력해주세요.', 'warning');
			return;
		}

		$.ajax({
			url: '/member/update_memo',
			method: 'POST',
			data: {
				idx: idx,
				memo_content: content.trim(),
				org_id: selectedOrgId
			},
			dataType: 'json',
			success: function(response) {
				if (response.success) {
					loadMemoList(currentMemberIdx);
					editingMemoIdx = null;
					showToast('메모가 수정되었습니다.', 'success');
				} else {
					showToast(response.message || '메모 수정에 실패했습니다.', 'error');
				}
			},
			error: function() {
				showToast('메모 수정에 실패했습니다.', 'error');
			}
		});
	}

	/**
	 * 메모 삭제 확인 모달 표시
	 */
	function showDeleteMemoModal(idx) {
		const modalHtml = `
			<div class="modal fade" id="deleteMemoModal" tabindex="-1" aria-labelledby="deleteMemoModalLabel" aria-hidden="true">
				<div class="modal-dialog">
					<div class="modal-content">
						<div class="modal-header">
							<h5 class="modal-title" id="deleteMemoModalLabel">메모 삭제</h5>
							<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
						</div>
						<div class="modal-body">
							<p>이 메모를 삭제하시겠습니까?</p>
						</div>
						<div class="modal-footer">
							<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
							<button type="button" class="btn btn-danger" id="confirmDeleteMemoBtn" data-idx="${idx}">삭제</button>
						</div>
					</div>
				</div>
			</div>
		`;

		$('#deleteMemoModal').remove();
		$('body').append(modalHtml);

		$('#confirmDeleteMemoBtn').on('click', function() {
			const memoIdx = $(this).data('idx');
			deleteMemo(memoIdx);
		});

		$('#deleteMemoModal').modal('show');
	}

	/**
	 * 메모 삭제 실행
	 */
	function deleteMemo(idx) {
		$.ajax({
			url: '/member/delete_memo',
			method: 'POST',
			data: {
				idx: idx,
				org_id: selectedOrgId
			},
			dataType: 'json',
			success: function(response) {
				if (response.success) {
					$('#deleteMemoModal').modal('hide');
					loadMemoList(currentMemberIdx);
					showToast('메모가 삭제되었습니다.', 'success');
				} else {
					showToast(response.message || '메모 삭제에 실패했습니다.', 'error');
				}
			},
			error: function() {
				showToast('메모 삭제에 실패했습니다.', 'error');
			}
		});
	}

	/**
	 * 메모용 날짜 형식화
	 */
	function formatMemoDateTime(dateString) {
		const date = new Date(dateString);
		const year = date.getFullYear();
		const month = String(date.getMonth() + 1).padStart(2, '0');
		const day = String(date.getDate()).padStart(2, '0');
		const hours = String(date.getHours()).padStart(2, '0');
		const minutes = String(date.getMinutes()).padStart(2, '0');
		const seconds = String(date.getSeconds()).padStart(2, '0');

		return `${year}.${month}.${day} ${hours}:${minutes}:${seconds}`;
	}

	// ===== 사진 관련 함수들 =====

	/**
	 * 사진 관련 이벤트 바인딩 (Croppie 적용)
	 */
	function bindPhotoEvents() {
		$('#member_photo').off('change').on('change', handlePhotoFileSelect);
		$('#cropPhoto').off('click').on('click', handleCropButtonClick);
		$('#removePhoto').off('click').on('click', handleRemovePhotoClick);
		$('#saveCrop').off('click').on('click', saveCroppedImage);
		$('#cancelCrop').off('click').on('click', cancelCrop);
	}

	/**
	 * 사진 파일 선택 처리
	 */
	function handlePhotoFileSelect(e) {
		const file = e.target.files[0];
		if (!file) return;

		if (!validateImageFile(file)) {
			return;
		}

		$('#delete_photo').remove();

		const reader = new FileReader();
		reader.onload = function(e) {
			$('#previewImage').attr('src', e.target.result);
			$('#photoPreview').show();
			$('#photoUpload').hide();
		};
		reader.readAsDataURL(file);
	}

	/**
	 * 크롭 버튼 클릭 처리
	 */
	function handleCropButtonClick() {
		const imageSrc = $('#previewImage').attr('src');
		if (imageSrc) {
			initCroppie(imageSrc);
		}
	}

	/**
	 * 사진 삭제 버튼 처리
	 */
	function handleRemovePhotoClick() {
		$('#member_photo').val('');
		$('#photoPreview').hide();
		$('#photoUpload').show();
		destroyCroppie();

		let deletePhotoField = $('#delete_photo');
		if (deletePhotoField.length === 0) {
			$('#memberForm').append('<input type="hidden" id="delete_photo" name="delete_photo" value="">');
			deletePhotoField = $('#delete_photo');
		}
		deletePhotoField.val('Y');
	}

	/**
	 * Croppie 초기화
	 */
	function initCroppie(imageSrc) {
		destroyCroppie();

		$('#photoPreview').hide();
		$('#cropContainer').show();

		croppieInstance = new Croppie(document.getElementById('cropBox'), {
			viewport: {
				width: 150,
				height: 150,
				type: 'circle'
			},
			boundary: {
				width: 250,
				height: 250
			},
			showZoomer: true,
			enableResize: false,
			enableOrientation: true,
			mouseWheelZoom: 'ctrl'
		});

		croppieInstance.bind({
			url: imageSrc
		}).catch(function(error) {
			console.error('Croppie 바인딩 오류:', error);
			showToast('이미지 로드에 실패했습니다.', 'error');
			cancelCrop();
		});
	}

	/**
	 * 크롭된 이미지 저장
	 */
	function saveCroppedImage() {
		if (!croppieInstance) {
			showToast('크롭 인스턴스가 없습니다.', 'error');
			return;
		}

		croppieInstance.result({
			type: 'canvas',
			size: { width: 200, height: 200 },
			format: 'jpeg',
			quality: 0.9,
			circle: true
		}).then(function(croppedImage) {
			$('#previewImage').attr('src', croppedImage);

			dataURLtoFile(croppedImage, 'cropped_image.jpg').then(function(file) {
				const dt = new DataTransfer();
				dt.items.add(file);
				document.getElementById('member_photo').files = dt.files;

				$('#delete_photo').remove();
			});

			$('#cropContainer').hide();
			$('#photoPreview').show();
			destroyCroppie();

		}).catch(function(error) {
			console.error('크롭 처리 오류:', error);
			showToast('이미지 크롭에 실패했습니다.', 'error');
		});
	}

	/**
	 * 크롭 취소
	 */
	function cancelCrop() {
		$('#cropContainer').hide();
		$('#photoPreview').show();
		destroyCroppie();
	}

	/**
	 * Split.js 인스턴스 정리
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
	 * Croppie 인스턴스 제거
	 */
	function destroyCroppie() {
		if (croppieInstance) {
			try {
				croppieInstance.destroy();
			} catch (error) {
				console.warn('Croppie 인스턴스 제거 중 오류:', error);
			}
			croppieInstance = null;
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

	/**
	 * Base64 DataURL을 File 객체로 변환
	 */
	function dataURLtoFile(dataURL, filename) {
		return new Promise(function(resolve) {
			const arr = dataURL.split(',');
			const mime = arr[0].match(/:(.*?);/)[1];
			const bstr = atob(arr[1]);
			let n = bstr.length;
			const u8arr = new Uint8Array(n);

			while (n--) {
				u8arr[n] = bstr.charCodeAt(n);
			}

			const file = new File([u8arr], filename, { type: mime });
			resolve(file);
		});
	}

	/**
	 * 회원 저장 (수정된 버전)
	 */
	function saveMember() {
		if (!validateMemberForm()) {
			return;
		}

		if ($('#cropContainer').is(':visible')) {
			showToast('이미지 크롭을 완료하거나 취소해주세요.', 'warning');
			return;
		}

		// 상세정보 데이터 준비
		saveDetailData();

		const form = $('#memberForm')[0];
		const formData = new FormData(form);
		formData.append('org_id', selectedOrgId);

		const saveBtn = $('#btnSaveMember');
		const originalText = saveBtn.html();
		saveBtn.prop('disabled', true).html('<i class="bi bi-hourglass-split"></i> 저장 중...');

		const url = formData.get('member_idx') ?
			window.memberPageData.baseUrl + 'member/update_member' :
			window.memberPageData.baseUrl + 'member/add_member';

		$.ajax({
			url: url,
			method: 'POST',
			data: formData,
			processData: false,
			contentType: false,
			dataType: 'json',
			success: function(response) {
				handleSaveMemberResponse(response);
			},
			error: function(xhr, status, error) {
				console.error('회원 저장 실패:', error);
				showToast('회원 정보 저장에 실패했습니다.', 'error');
			},
			complete: function() {
				saveBtn.prop('disabled', false).html(originalText);
			}
		});
	}

	/**
	 * 회원 폼 유효성 검증
	 */
	function validateMemberForm() {
		const memberName = $('#member_name').val().trim();
		if (!memberName) {
			showToast('이름을 입력해주세요.', 'warning');
			$('#member_name').focus();
			return false;
		}
		return true;
	}

	/**
	 * 회원 저장 응답 처리
	 */
	function handleSaveMemberResponse(response) {
		const toastType = response.success ? 'success' : 'error';
		showToast(response.message, toastType);

		if (response.success) {
			const offcanvasInstance = bootstrap.Offcanvas.getInstance($('#memberOffcanvas')[0]);
			if (offcanvasInstance) {
				offcanvasInstance.hide();
			}
			loadMemberData();
			refreshGroupTree();
		}
	}

	// ===== 상세정보 탭 관련 함수들 =====

	/**
	 * 상세정보 탭 초기화 (수정된 버전)
	 */
	function initDetailTab() {
		$('#detail-tab').on('click', function() {
			const orgId = $('#org_id').val();
			const memberIdx = $('#member_idx').val();

			// 이미 로드되었는지 확인
			const frontContainer = $('#detail-front');
			const backContainer = $('#detailFieldsContainer');

			// front나 back 컨테이너에 이미 필드가 있으면 다시 로드하지 않음
			if (frontContainer.children().length > 0 || backContainer.children().length > 0) {
				return;
			}

			if (orgId && memberIdx) {
				loadDetailFields(orgId, memberIdx);
			} else if (orgId) {
				loadDetailFields(orgId, null);
			}
		});
	}

	/**
	 * 상세필드 로드 및 폼 생성
	 */
	function loadDetailFields(orgId, memberIdx = null) {
		const frontContainer = $('#detail-front');
		const backContainer = $('#detailFieldsContainer');
		const loading = $('#detailFieldsLoading');
		const empty = $('#detailFieldsEmpty');

		loading.show();
		empty.hide();
		frontContainer.empty();
		backContainer.empty();

		$.ajax({
			url: '/member/get_detail_fields',
			type: 'POST',
			data: { org_id: orgId },
			dataType: 'json',
			success: function(response) {
				loading.hide();

				if (response.success && response.data.length > 0) {
					if (memberIdx) {
						getMemberDetailData(orgId, memberIdx, response.data);
					} else {
						generateDetailForm(response.data, {});
					}
				} else {
					empty.show();
					frontContainer.hide();
				}
			},
			error: function() {
				loading.hide();
				showToast('상세필드를 불러오는데 실패했습니다.', 'error');
				frontContainer.hide();
			}
		});
	}

	/**
	 * 회원 상세정보 데이터 가져오기
	 */
	function getMemberDetailData(orgId, memberIdx, fields) {
		$.ajax({
			url: '/member/get_member_detail',
			type: 'POST',
			data: {
				org_id: orgId,
				member_idx: memberIdx
			},
			dataType: 'json',
			success: function(response) {
				if (response.success) {
					generateDetailForm(fields, response.data);
				} else {
					generateDetailForm(fields, {});
				}
			},
			error: function() {
				generateDetailForm(fields, {});
			}
		});
	}

	/**
	 * 상세정보 폼 생성
	 */
	function generateDetailForm(fields, memberDetail) {
		const frontContainer = $('#detail-front');
		const backContainer = $('#detailFieldsContainer');

		frontContainer.empty();
		backContainer.empty();

		if (!fields || fields.length === 0) {
			return;
		}

		fields.forEach(function(field) {
			if (field.is_active !== 'Y') {
				return; // 비활성 필드는 건너뛰기
			}

			const fieldValue = memberDetail[field.field_idx] || '';
			const fieldHtml = generateFieldHtml(field, fieldValue);

			// field_position에 따라 다른 컨테이너에 추가
			if (field.field_position === 'front') {
				frontContainer.append(fieldHtml);
			} else {
				backContainer.append(fieldHtml);
			}
		});

		// front 영역에 필드가 있으면 보이도록 설정
		if (frontContainer.children().length > 0) {
			frontContainer.show();
		} else {
			frontContainer.hide();
		}
	}

	/**
	 * 필드별 HTML 생성
	 */
	/**
	 * 필드별 HTML 생성
	 */
	function generateFieldHtml(field, value) {
		const fieldId = 'detail_field_' + field.field_idx;
		const fieldName = 'detail_field[' + field.field_idx + ']';

		// field_size에 따라 col 클래스 결정 (수정된 버전)
		let colClass = 'col-12'; // 기본값
		if (field.field_size == 1) {
			colClass = 'col-6 col-sm-4';
		} else if (field.field_size == 2) {
			colClass = 'col-8 col-sm-6';
		} else if (field.field_size == 3) {
			colClass = 'col-12';
		}

		let inputHtml = '';

		switch (field.field_type) {
			case 'text':
				inputHtml = `<input type="text" class="form-control" id="${fieldId}" name="${fieldName}" value="${escapeHtml(value)}">`;
				break;

			case 'select':
				inputHtml = generateSelectHtml(field, value, fieldId, fieldName);
				break;

			case 'textarea':
				inputHtml = `<textarea class="form-control" id="${fieldId}" name="${fieldName}" rows="3">${escapeHtml(value)}</textarea>`;
				break;

			case 'checkbox':
				const checked = value === 'Y' ? 'checked' : '';
				inputHtml = `
			<div class="form-check">
				<input class="form-check-input" type="checkbox" id="${fieldId}" name="${fieldName}" value="Y" ${checked}>
				<label class="form-check-label" for="${fieldId}">${escapeHtml(field.field_name)}</label>
			</div>
		`;
				break;

			case 'date':
				inputHtml = `<input type="date" class="form-control" id="${fieldId}" name="${fieldName}" value="${value}">`;
				break;

			default:
				inputHtml = `<input type="text" class="form-control" id="${fieldId}" name="${fieldName}" value="${escapeHtml(value)}">`;
		}

		const labelHtml = field.field_type === 'checkbox' ? '' : `<label for="${fieldId}" class="form-label">${escapeHtml(field.field_name)}</label>`;

		return `
	<div class="${colClass} mb-3">
		${labelHtml}
		${inputHtml}
	</div>
`;
	}

	/**
	 * 선택박스 HTML 생성
	 */
	function generateSelectHtml(field, value, fieldId, fieldName) {
		let options = '<option value="">선택하세요</option>';

		if (field.field_settings && field.field_settings.options) {
			field.field_settings.options.forEach(function(option) {
				const selected = value === option ? 'selected' : '';
				options += `<option value="${escapeHtml(option)}" ${selected}>${escapeHtml(option)}</option>`;
			});
		}

		return `<select class="form-select" id="${fieldId}" name="${fieldName}">${options}</select>`;
	}


	/**
	 * 상세정보 저장 - member_detail 컬럼 활용 (수정된 버전)
	 */
	function saveDetailData() {
		const orgId = $('#org_id').val();
		const memberIdx = $('#member_idx').val();

		if (!orgId || !memberIdx) {
			return true;
		}

		const detailData = {};

		// front와 back 영역 모두에서 입력값 수집
		$('#detail-front input, #detail-front select, #detail-front textarea, #detailFieldsContainer input, #detailFieldsContainer select, #detailFieldsContainer textarea').each(function() {
			const name = $(this).attr('name');
			if (name && name.startsWith('detail_field[')) {
				const fieldIdx = name.match(/detail_field\[(\d+)\]/)[1];

				if ($(this).is(':checkbox')) {
					detailData[fieldIdx] = $(this).is(':checked') ? 'Y' : 'N';
				} else {
					detailData[fieldIdx] = $(this).val() || '';
				}
			}
		});

		// 기존 히든 필드 제거 (중복 방지)
		$('input[name="detail_field"]').remove();

		// 상세필드 데이터를 폼에 추가 (히든 필드로) - 폼의 직접 자식으로 추가
		$('#memberForm').append(
			'<input type="hidden" name="detail_field" value=\'' + JSON.stringify(detailData) + '\' />'
		);

		return true;
	}

	// ===== 유틸리티 함수들 =====

	/**
	 * 이미지 파일 유효성 검사
	 */
	function validateImageFile(file) {
		const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
		if (!allowedTypes.includes(file.type)) {
			showToast('지원하지 않는 이미지 형식입니다. (JPG, PNG, GIF만 가능)', 'error');
			return false;
		}

		const maxSize = 5 * 1024 * 1024;
		if (file.size > maxSize) {
			showToast('파일 크기는 5MB 이하만 가능합니다.', 'error');
			return false;
		}

		return true;
	}

	/**
	 * 이미지 로드 에러 처리
	 */
	function handleImageLoadError() {
		showToast('이미지를 불러올 수 없습니다.', 'error');
		$('#photoPreview').hide();
		$('#photoUpload').show();
		$('#member_photo').val('');
		destroyCroppie();
	}




	// ===== 회원 저장 버튼 클릭 이벤트 수정 =====

	$(document).on('click', '#btnSaveMember', function() {
		const form = $('#memberForm')[0];
		const formData = new FormData(form);

		// 상세정보 저장
		saveDetailData();

		$.ajax({
			url: '/member/update_member',
			type: 'POST',
			data: formData,
			processData: false,
			contentType: false,
			dataType: 'json',
			success: function(response) {
				const toastType = response.success ? 'success' : 'error';
				showToast(response.message, toastType);
				if (response.success) {
					loadMemberData();
					$('#memberOffcanvas').offcanvas('hide');
				}
			},
			error: function() {
				showToast('회원 정보 저장 중 오류가 발생했습니다.', 'error');
			}
		});
	});


	// 파일 위치: assets/js/member.js
// 역할: 페이지 로딩 시 스피너 표시/숨김 관련 함수들

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



// 선택QR인쇄 버튼 클릭 이벤트 (수정된 버전)
	$(document).on('click', '#btnSelectedQrPrint', function() {
		const selectedMembers = getSelectedMembers();



		if (selectedMembers.length === 0) {
			showToast('인쇄할 회원을 선택해주세요.', 'warning');
			return;
		}

		if (selectedMembers.length > 70) {
			showConfirmModal(
				'선택된 회원이 70명을 초과합니다',
				'선택된 회원(' + selectedMembers.length + '명)이 한 장의 라벨지 용량(70개)을 초과합니다. 여러 장에 나누어 인쇄됩니다. 계속하시겠습니까?',
				function() {
					openSelectedQrPrintModal(selectedMembers);
				}
			);
		} else {
			openSelectedQrPrintModal(selectedMembers);
		}
	});


	/**
	 * 선택된 회원 목록 가져오기 (최종 버전)
	 */
	function getSelectedMembers() {
		const selectedMembers = [];
		const processedMemberIds = new Set();

		// 고유한 체크된 체크박스만 처리
		$('.member-checkbox:checked').each(function() {
			const memberIdx = $(this).data('member-idx');

			// 중복 제거
			if (processedMemberIds.has(memberIdx)) {
				return true; // continue
			}

			processedMemberIds.add(memberIdx);

			// 그리드에서 해당 회원의 데이터 찾기
			if (memberGrid && memberGrid.length > 0) {
				const gridData = memberGrid.pqGrid('option', 'dataModel.data');
				const memberData = gridData.find(row => row.member_idx == memberIdx);

				if (memberData) {
					selectedMembers.push({
						member_idx: memberData.member_idx,
						member_name: memberData.member_name,
						area_name: memberData.area_name
					});
				}
			}
		});


		return selectedMembers;
	}

	/**
	 * 선택QR인쇄 모달 열기 (수정된 버전)
	 */
	function openSelectedQrPrintModal(selectedMembers) {


		$('#selectedMemberCount').val(selectedMembers.length + '명 선택됨');

		// 모달에 선택된 회원 정보 저장
		$('#selectedQrPrintModal').data('selectedMembers', selectedMembers);

		// 시작 위치 초기화
		$('#startPositionSelect').val('1');

		$('#selectedQrPrintModal').modal('show');
	}




	/**
	 * 인쇄하기 버튼 클릭 이벤트 (수정된 버전)
	 */
	$(document).on('click', '#executePrintSelectedQr', function() {
		const selectedMembers = $('#selectedQrPrintModal').data('selectedMembers');
		const startPosition = $('#startPositionSelect').val();



		if (!selectedMembers || selectedMembers.length === 0) {
			showToast('선택된 회원이 없습니다.', 'error');
			return;
		}

		// 회원 인덱스 배열 생성
		const memberIndices = selectedMembers.map(member => member.member_idx);

		// URL 생성
		const url = '/member/print_selected_qr?' +
			'members=' + memberIndices.join(',') +
			'&start_position=' + startPosition;



		// 새 창에서 인쇄 페이지 열기
		window.open(url, '_blank', 'width=1200,height=800,scrollbars=yes');

		// 모달 닫기
		$('#selectedQrPrintModal').modal('hide');
	});

	/**
	 * Confirm 모달 표시 함수 (공통 함수)
	 */
	function showConfirmModal(title, message, onConfirm, onCancel) {
		// 기존 확인 모달이 있으면 제거
		$('#confirmModal').remove();

		const modalHtml = `
        <div class="modal fade" id="confirmModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">${title}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>${message}</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                        <button type="button" class="btn btn-primary" id="confirmYes">확인</button>
                    </div>
                </div>
            </div>
        </div>
    `;

		$('body').append(modalHtml);

		// 확인 버튼 클릭 이벤트
		$('#confirmYes').on('click', function() {
			$('#confirmModal').modal('hide');
			if (typeof onConfirm === 'function') {
				onConfirm();
			}
		});

		// 모달 닫힘 이벤트
		$('#confirmModal').on('hidden.bs.modal', function() {
			$(this).remove();
			if (typeof onCancel === 'function') {
				onCancel();
			}
		});

		$('#confirmModal').modal('show');
	}


	/**
	 * 조직별 직위/직분 및 직책 옵션 로드
	 */
	function loadPositionsAndDuties(orgId, callback) {
		if (!orgId) {
			if (typeof callback === 'function') {
				callback();
			}
			return;
		}

		$.ajax({
			url: window.memberPageData.baseUrl + 'member/get_org_positions_duties',
			method: 'POST',
			data: { org_id: orgId },
			dataType: 'json',
			success: function(response) {
				if (response.success) {
					populatePositionOptions(response.data.positions);
					populateDutyOptions(response.data.duties);
				} else {
					console.error('직위/직책 데이터 로드 실패:', response.message);
				}

				if (typeof callback === 'function') {
					callback();
				}
			},
			error: function(xhr, status, error) {
				console.error('직위/직책 데이터 로드 오류:', error);

				if (typeof callback === 'function') {
					callback();
				}
			}
		});
	}

	/**
	 * 직위/직분 선택 옵션 채우기
	 */
	function populatePositionOptions(positions) {
		const positionSelect = $('#position_name');
		positionSelect.html('<option value="">직위/직분 선택</option>');

		if (positions && Array.isArray(positions)) {
			positions.forEach(function(position) {
				positionSelect.append(`<option value="${escapeHtml(position)}">${escapeHtml(position)}</option>`);
			});
		}
	}

	/**
	 * 직책 선택 옵션 채우기
	 */
	function populateDutyOptions(duties) {
		const dutySelect = $('#duty_name');
		dutySelect.html('<option value="">직책 선택</option>');

		if (duties && Array.isArray(duties)) {
			duties.forEach(function(duty) {
				dutySelect.append(`<option value="${escapeHtml(duty)}">${escapeHtml(duty)}</option>`);
			});
		}
	}



	/**
	 * 컬럼 순서를 localStorage에 저장
	 */
	function saveColumnOrder(colModel) {
		if (!selectedOrgId) return;

		try {
			// 컬럼의 dataIndx만 순서대로 저장 (필수 컬럼 제외)
			const columnOrder = colModel
				.filter(col => col.dataIndx && col.dataIndx !== 'pq_selected')
				.map(col => col.dataIndx);

			const storageKey = 'member_column_order_' + selectedOrgId;
			localStorage.setItem(storageKey, JSON.stringify(columnOrder));

			console.log('컬럼 순서 저장됨:', columnOrder);
		} catch (error) {
			console.error('컬럼 순서 저장 실패:', error);
		}
	}

	/**
	 * localStorage에서 컬럼 순서 불러오기
	 */
	function loadColumnOrder() {
		if (!selectedOrgId) return null;

		try {
			const storageKey = 'member_column_order_' + selectedOrgId;
			const savedOrder = localStorage.getItem(storageKey);

			if (savedOrder) {
				const columnOrder = JSON.parse(savedOrder);
				console.log('저장된 컬럼 순서 로드됨:', columnOrder);
				return columnOrder;
			}
		} catch (error) {
			console.error('컬럼 순서 로드 실패:', error);
		}

		return null;
	}

	/**
	 * 저장된 순서에 따라 컬럼 모델 재정렬
	 */
	function reorderColumnModel(colModel, savedOrder) {
		if (!savedOrder || savedOrder.length === 0) {
			return colModel;
		}

		// 체크박스 컬럼은 항상 첫 번째에 고정
		const checkboxCol = colModel.find(col => col.dataIndx === 'pq_selected');
		const otherCols = colModel.filter(col => col.dataIndx !== 'pq_selected');

		// 저장된 순서대로 재정렬
		const reorderedCols = [];
		const colMap = new Map(otherCols.map(col => [col.dataIndx, col]));

		// 저장된 순서대로 먼저 추가
		savedOrder.forEach(dataIndx => {
			if (colMap.has(dataIndx)) {
				reorderedCols.push(colMap.get(dataIndx));
				colMap.delete(dataIndx);
			}
		});

		// 저장된 순서에 없는 새로운 컬럼들은 뒤에 추가 (상세필드가 추가된 경우)
		colMap.forEach(col => {
			reorderedCols.push(col);
		});

		// 체크박스 컬럼을 맨 앞에 추가
		return checkboxCol ? [checkboxCol, ...reorderedCols] : reorderedCols;
	}



});



/**
 * 상세필드 값 렌더링
 */
function renderDetailFieldValue(value, fieldType) {
	if (!value) return '';

	switch(fieldType) {
		case 'checkbox':
			return value === 'Y' ? '<i class="bi bi-check-circle-fill text-success"></i>' : '';
		case 'date':
			return formatDateOnly(value);
		case 'textarea':
			// 긴 텍스트는 일부만 표시
			return value.length > 50 ? value.substring(0, 50) + '...' : value;
		default:
			return escapeHtml(value);
	}
}


/**
 * 날짜만 포맷팅 (yyyy-mm-dd)
 */
function formatDateOnly(dateString) {
	if (!dateString) return '';
	const date = new Date(dateString);
	const year = date.getFullYear();
	const month = String(date.getMonth() + 1).padStart(2, '0');
	const day = String(date.getDate()).padStart(2, '0');
	return `${year}-${month}-${day}`;
}


/**
 * HTML 이스케이프 함수
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


