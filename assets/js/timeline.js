/**
 * 역할: 타임라인 관리 화면 동작 제어
 */

$(document).ready(function() {
	const baseUrl = window.timelinePageData.baseUrl;
	const currentOrgId = window.timelinePageData.currentOrgId;
	let timelineGrid;
	let timelineTypes = [];

	// 현재 연/월 초기화
	initializeYearMonth();

	// PQGrid 초기화
	initPQGrid();

	// 타임라인 항목 로드
	loadTimelineTypes();

	// 이벤트 바인딩
	bindEvents();

	// 전송 히스토리 년월 초기화
	initHistoryYearMonth();

	/**
	 * 역할: PQGrid 초기화 - cellClick 이벤트 추가
	 */
	function initPQGrid() {
		const colModel = [
			{
				title: '<input type="checkbox" id="selectAllCheckbox" />',
				dataIndx: "pq_selected",
				width: 50,
				align: "center",
				resizable: false,
				sortable: false,
				editable: false,
				menuIcon: false,
				render: function (ui) {
					const checkboxId = 'timeline-checkbox-' + ui.rowData.idx;
					return '<input type="checkbox" class="timeline-checkbox" id="' + checkboxId + '" data-idx="' + ui.rowData.idx + '" />';
				}
			},
			{
				title: '항목',
				dataIndx: 'timeline_type',
				dataType: 'string',
				width: 120
			},
			{
				title: '날짜',
				dataIndx: 'timeline_date',
				dataType: 'string',
				width: 120,
				render: function(ui) {
					if (ui.cellData) {
						const date = new Date(ui.cellData);
						const year = date.getFullYear();
						const month = String(date.getMonth() + 1).padStart(2, '0');
						const day = String(date.getDate()).padStart(2, '0');
						return `${year}-${month}-${day}`;
					}
					return '';
				}
			},
			{
				title: '이름',
				dataIndx: 'member_name',
				dataType: 'string',
				width: 100
			},
			{
				title: '내용',
				dataIndx: 'timeline_content',
				dataType: 'string',
				width: 250
			},
			{
				title: '등록일',
				dataIndx: 'regi_date',
				dataType: 'string',
				width: 120,
				render: function(ui) {
					if (ui.cellData) {
						const date = new Date(ui.cellData);
						const year = date.getFullYear();
						const month = String(date.getMonth() + 1).padStart(2, '0');
						const day = String(date.getDate()).padStart(2, '0');
						const hours = String(date.getHours()).padStart(2, '0');
						const minutes = String(date.getMinutes()).padStart(2, '0');
						const seconds = String(date.getSeconds()).padStart(2, '0');
						return `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
					}
					return '';
				}
			},
			{
				title: '등록자',
				dataIndx: 'regi_user_name',
				dataType: 'string',
				width: 100
			},
			{
				title: '수정일',
				dataIndx: 'modi_date',
				dataType: 'string',
				width: 150,
				render: function(ui) {
					if (ui.cellData) {
						const date = new Date(ui.cellData);
						const year = date.getFullYear();
						const month = String(date.getMonth() + 1).padStart(2, '0');
						const day = String(date.getDate()).padStart(2, '0');
						const hours = String(date.getHours()).padStart(2, '0');
						const minutes = String(date.getMinutes()).padStart(2, '0');
						const seconds = String(date.getSeconds()).padStart(2, '0');
						return `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
					}
					return '';
				}
			}
		];

		const dataModel = {
			location: 'remote',
			dataType: 'JSON',
			method: 'POST',
			url: baseUrl + 'timeline/get_timelines',
			postData: function() {
				// 선택된 타임라인 항목들 가져오기
				const selectedTypes = [];
				if (!$('#searchType_all').is(':checked')) {
					$('.timeline-type-checkbox:checked').each(function() {
						selectedTypes.push($(this).val());
					});
				}

				return {
					org_id: currentOrgId,
					timeline_types: selectedTypes,
					search_text: $('#searchText').val()
				};
			},
			getData: function(response) {
				return {
					curPage: response.curPage,
					totalRecords: response.totalRecords,
					data: response.data
				};
			}
		};

		const gridOptions = {
			width: '100%',
			height: "100%",
			colModel: colModel,
			dataModel: dataModel,
			editable: false,
			pageModel: { type: null },
			selectionModel: { type: 'row', mode: 'block' },
			numberCell: { show: false },
			showTitle: false,
			showToolbar: false,
			showBottom: false,
			showTop: false,
			showHeader: true,
			scrollModel: { autoFit: false },
			strNoRows: '조회된 타임라인이 없습니다.',
			loading: { show: false },
			cellClick: function(event, ui) {
				if (ui.dataIndx === 'pq_selected') {
					handleCheckboxColumnClick(event, ui.rowData.idx);
				} else {
					// 체크박스 컬럼이 아닌 경우 수정 폼 열기
					editTimeline(ui.rowData);
				}
			},
			beforeRemoteRequest: function(event, ui) {
				// 데이터 로드 시작 시 스피너 표시
				showGridLoading();
			},
			load: function(event, ui) {
				// 데이터 로드 완료 시 스피너 숨김
				hideGridLoading();
			}
		};

		timelineGrid = pq.grid('#timelineGrid', gridOptions);
	}

	/**
	 * 그리드 로딩 스피너 표시
	 */
	function showGridLoading() {
		$('#timelineGridLoading').show();
	}

	/**
	 * 그리드 로딩 스피너 숨김
	 */
	function hideGridLoading() {
		$('#timelineGridLoading').hide();

	}

	/**
	 * 연/월 초기화
	 */
	function initializeYearMonth() {
		const today = new Date();
		const currentYear = today.getFullYear();
		const currentMonth = today.getMonth() + 1;

		$('#historyYear').val(currentYear);
		$('#historyMonth').val(currentMonth);
	}


	/**
	 * 역할: 히스토리 년월 선택 초기화
	 */
	function initHistoryYearMonth() {
		const now = new Date();
		const currentYear = now.getFullYear();
		const currentMonth = now.getMonth() + 1;

		// 년도 옵션 생성 (현재년도 기준 ±5년)
		const yearSelect = $('#historyYear');
		yearSelect.empty();

		for (let year = currentYear - 5; year <= currentYear + 1; year++) {
			const option = `<option value="${year}">${year}년</option>`;
			yearSelect.append(option);
		}

		// 현재 년월로 설정
		$('#historyYear').val(currentYear);
		$('#historyMonth').val(currentMonth);
	}

	/**
	 * 역할: 체크박스 컬럼 클릭 핸들러
	 */
	function handleCheckboxColumnClick(event, idx) {
		// 직접 체크박스를 클릭한 경우가 아니라면 체크박스 토글
		const isDirectCheckboxClick = $(event.target).hasClass('timeline-checkbox') ||
			$(event.originalEvent?.target).hasClass('timeline-checkbox');

		if (!isDirectCheckboxClick) {
			const checkbox = $('.timeline-checkbox[data-idx="' + idx + '"]').first();
			if (checkbox.length > 0) {
				const isCurrentlyChecked = checkbox.is(':checked');
				checkbox.prop('checked', !isCurrentlyChecked);
			}
		}

		// 체크박스 상태 업데이트
		updateSelectAllCheckbox();
		updateSelectedTimelineButtons();
	}

	/**
	 
	 * 역할: 전체 선택 체크박스 상태 업데이트
	 */
	function updateSelectAllCheckbox() {
		const totalCheckboxes = $('.timeline-checkbox').length;
		const checkedCheckboxes = $('.timeline-checkbox:checked').length;

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
	 
	 * 역할: 선택된 타임라인에 따라 버튼 활성화/비활성화
	 */
	function updateSelectedTimelineButtons() {
		const checkedCount = $('.timeline-checkbox:checked').length;

		if (checkedCount === 0) {
			$('#btnDelete').prop('disabled', true);
		} else {
			$('#btnDelete').prop('disabled', false);
		}
	}

	/**
	 * 타임라인 항목 로드
	 */
	function loadTimelineTypes() {
		$.ajax({
			url: baseUrl + 'timeline/get_timeline_types',
			type: 'POST',
			dataType: 'json',
			data: { org_id: currentOrgId },
			success: function(response) {
				if (response.success) {
					timelineTypes = response.data;
					updateTimelineTypeSelect();
				}
			},
			error: function() {
				showToast('타임라인 항목 로드에 실패했습니다.', 'error');
			}
		});
	}

	/**
	 * 타임라인 항목 셀렉트 업데이트
	 */
	function updateTimelineTypeSelect() {
		// 검색 드롭다운 메뉴 업데이트
		const $searchMenu = $('#searchTypeMenu');

		// 기존 항목 제거 (전체와 구분선은 유지)
		$searchMenu.find('li:gt(1)').remove();

		// 타임라인 항목들 추가
		timelineTypes.forEach(function(type) {
			const itemId = 'searchType_' + type.replace(/\s+/g, '_');
			const $li = $('<li></li>');
			const $div = $('<div class="dropdown-item"></div>');
			const $checkbox = $('<input type="checkbox" class="form-check-input me-2 timeline-type-checkbox">');
			$checkbox.attr('id', itemId);
			$checkbox.attr('value', type);
			const $label = $('<label class="form-check-label"></label>');
			$label.attr('for', itemId);
			$label.text(type);

			$div.append($checkbox);
			$div.append($label);
			$li.append($div);
			$searchMenu.append($li);
		});

		// offcanvas의 항목 셀렉트 업데이트
		$('#timeline_type').empty();
		$('#timeline_type').append('<option value="">항목을 선택하세요</option>');
		timelineTypes.forEach(function(type) {
			$('#timeline_type').append(`<option value="${type}">${type}</option>`);
		});

		// 드롭다운 이벤트 바인딩
		bindDropdownEvents();
	}

	/**
	 * 드롭다운 이벤트 바인딩
	 */
	function bindDropdownEvents() {
		// 전체 체크박스 클릭 이벤트
		$('#searchType_all').on('change', function() {
			const isChecked = $(this).is(':checked');
			$('.timeline-type-checkbox').prop('checked', false);
			if (isChecked) {
				updateSearchTypeText();
			}
		});

		// 개별 항목 체크박스 클릭 이벤트
		$(document).off('change', '.timeline-type-checkbox').on('change', '.timeline-type-checkbox', function() {
			// 개별 항목이 체크되면 전체 체크 해제
			if ($(this).is(':checked')) {
				$('#searchType_all').prop('checked', false);
			}

			// 모든 개별 항목이 체크 해제되면 전체 체크
			if ($('.timeline-type-checkbox:checked').length === 0) {
				$('#searchType_all').prop('checked', true);
			}

			updateSearchTypeText();
		});

		// 드롭다운 아이템 클릭 시 닫히지 않도록 방지
		$('#searchTypeMenu .dropdown-item').on('click', function(e) {
			e.stopPropagation();
		});
	}

	/**
	 * 선택된 타임라인 항목 텍스트 업데이트
	 */
	function updateSearchTypeText() {
		const $allCheckbox = $('#searchType_all');
		const $typeCheckboxes = $('.timeline-type-checkbox:checked');

		if ($allCheckbox.is(':checked') || $typeCheckboxes.length === 0) {
			$('#searchTypeText').text('전체');
		} else if ($typeCheckboxes.length === 1) {
			$('#searchTypeText').text($typeCheckboxes.first().val());
		} else {
			$('#searchTypeText').text($typeCheckboxes.length + '개 항목 선택');
		}
	}


	/**
	 * Select2 초기화
	 */
	function initMemberSelect2() {
		$('#member_select').select2({
			width: '100%',
			placeholder: '회원을 선택하세요',
			allowClear: true,
			multiple: true,
			closeOnSelect: false,
			language: {
				noResults: function() {
					return '검색 결과가 없습니다.';
				},
				searching: function() {
					return '검색 중...';
				},
				inputTooShort: function() {
					return '검색어를 입력하세요.';
				}
			},
			ajax: {
				url: baseUrl + 'timeline/get_members_for_select',
				dataType: 'json',
				delay: 250,
				data: function(params) {
					return {
						org_id: currentOrgId,
						search: params.term
					};
				},
				processResults: function(response) {
					if (response.success) {
						return {
							results: response.data.map(function(member) {
								return {
									id: member.member_idx,
									text: member.member_name
								};
							})
						};
					}
					return { results: [] };
				},
				cache: true
			},
			minimumInputLength: 0,
			templateResult: function(data) {
				if (!data.id) {
					return data.text;
				}
				return $('<span>' + data.text + '</span>');
			},
			templateSelection: function(data) {
				return data.text;
			}
		});

		// Select2 드래그앤드롭 기능 적용
		$('#member_select').select2Sortable();
	}



	/**
	 * 역할: 이벤트 바인딩 - 체크박스 이벤트 수정
	 */
	function bindEvents() {
		$('#btnSearch').on('click', searchTimelines);
		$('#searchText').on('keypress', function(e) {
			if (e.which === 13) {
				searchTimelines();
			}
		});

		$('#btnAdd').on('click', showAddForm);
		$('#btnDelete').on('click', showDeleteConfirm);

		$('#btnSaveTimeline').on('click', saveTimeline);
		$('#confirmDeleteBtn').on('click', deleteTimelines);

		$('#timelineOffcanvas').on('hidden.bs.offcanvas', resetForm);

		// 전체 선택 체크박스 이벤트
		$(document).on('change', '#selectAllCheckbox', function() {
			const isChecked = $(this).prop('checked');
			$('.timeline-checkbox').prop('checked', isChecked);
			updateSelectedTimelineButtons();
		});

		// 개별 체크박스 이벤트
		$(document).on('change', '.timeline-checkbox', function() {
			updateSelectAllCheckbox();
			updateSelectedTimelineButtons();
		});
	}

	/**
	 
	 * 역할: 타임라인 검색 - 검색 후 버튼 상태 초기화
	 */
	function searchTimelines() {
		showGridLoading();
		timelineGrid.refreshDataAndView();

		// 검색 후 버튼 비활성화
		setTimeout(function() {
			updateSelectAllCheckbox();
			updateSelectedTimelineButtons();
			hideGridLoading();
		}, 100);
	}


	/**
	 * 추가 폼 표시
	 */
	function showAddForm() {
		resetForm();
		$('#timeline_mode').val('add');
		$('#timelineOffcanvasLabel').text('타임라인 일괄추가');
		$('#memberSelectDiv').show();
		$('#memberNameDiv').hide();

		// 오늘 날짜를 기본값으로 설정
		const today = new Date();
		const year = today.getFullYear();
		const month = String(today.getMonth() + 1).padStart(2, '0');
		const day = String(today.getDate()).padStart(2, '0');
		$('#timeline_date').val(`${year}-${month}-${day}`);

		initMemberSelect2();

		const offcanvas = new bootstrap.Offcanvas(document.getElementById('timelineOffcanvas'));
		offcanvas.show();
	}

	/**
	 * 수정 폼 표시
	 */
	function showEditForm() {
		const selectedCheckboxes = $('.timeline-checkbox:checked');

		if (selectedCheckboxes.length === 0) {
			showToast('수정할 항목을 선택해주세요.', 'warning');
			return;
		}

		if (selectedCheckboxes.length > 1) {
			showToast('한 개의 항목만 선택해주세요.', 'warning');
			return;
		}

		const idx = selectedCheckboxes.first().data('idx');
		const allData = timelineGrid.getData();
		const rowData = allData.find(row => row.idx === idx);

		if (rowData) {
			editTimeline(rowData);
		}
	}

	/**
	 * 타임라인 수정
	 */
	function editTimeline(rowData) {
		$.ajax({
			url: baseUrl + 'timeline/get_timeline_detail',
			type: 'POST',
			dataType: 'json',
			data: { idx: rowData.idx },
			success: function(response) {
				if (response.success) {
					const timeline = response.data;
					$('#timeline_mode').val('edit');
					$('#timeline_idx').val(timeline.idx);
					$('#timeline_type').val(timeline.timeline_type);
					$('#timeline_date').val(timeline.timeline_date);
					$('#timeline_content').val(timeline.timeline_content);
					$('#member_name_display').val(timeline.member_name);

					$('#timelineOffcanvasLabel').text('타임라인 수정');
					$('#memberSelectDiv').hide();
					$('#memberNameDiv').show();

					const offcanvas = new bootstrap.Offcanvas(document.getElementById('timelineOffcanvas'));
					offcanvas.show();
				} else {
					showToast(response.message || '타임라인 정보를 불러올 수 없습니다.', 'error');
				}
			},
			error: function() {
				showToast('타임라인 정보 로드에 실패했습니다.', 'error');
			}
		});
	}

	/**
	 * 타임라인 저장
	 */
	function saveTimeline() {
		const mode = $('#timeline_mode').val();
		const timeline_type = $('#timeline_type').val();
		const timeline_date = $('#timeline_date').val();

		if (!timeline_type) {
			showToast('항목을 선택해주세요.', 'warning');
			return;
		}

		if (!timeline_date) {
			showToast('날짜를 입력해주세요.', 'warning');
			return;
		}

		if (mode === 'add') {
			const member_idxs = $('#member_select').val();
			if (!member_idxs || member_idxs.length === 0) {
				showToast('회원을 선택해주세요.', 'warning');
				return;
			}

			addTimeline();
		} else {
			updateTimeline();
		}
	}

	/**
	 * 타임라인 일괄추가
	 */
	function addTimeline() {
		const formData = {
			org_id: currentOrgId,
			member_idxs: $('#member_select').val(),
			timeline_type: $('#timeline_type').val(),
			timeline_date: $('#timeline_date').val(),
			timeline_content: $('#timeline_content').val()
		};

		$.ajax({
			url: baseUrl + 'timeline/add_timeline',
			type: 'POST',
			dataType: 'json',
			data: formData,
			success: function(response) {
				if (response.success) {
					showToast(response.message, 'success');
					bootstrap.Offcanvas.getInstance(document.getElementById('timelineOffcanvas')).hide();
					searchTimelines();
				} else {
					showToast(response.message || '타임라인 일괄추가에 실패했습니다.', 'error');
				}
			},
			error: function() {
				showToast('타임라인 일괄추가에 실패했습니다.', 'error');
			}
		});
	}

	/**
	 * 타임라인 수정
	 */
	function updateTimeline() {
		const formData = {
			idx: $('#timeline_idx').val(),
			timeline_type: $('#timeline_type').val(),
			timeline_date: $('#timeline_date').val(),
			timeline_content: $('#timeline_content').val()
		};

		$.ajax({
			url: baseUrl + 'timeline/update_timeline',
			type: 'POST',
			dataType: 'json',
			data: formData,
			success: function(response) {
				if (response.success) {
					showToast(response.message, 'success');
					bootstrap.Offcanvas.getInstance(document.getElementById('timelineOffcanvas')).hide();
					searchTimelines();
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
	 * 삭제 확인 모달 표시
	 */
	function showDeleteConfirm() {
		const selectedIdxs = $('.timeline-checkbox:checked');

		if (selectedIdxs.length === 0) {
			showToast('삭제할 항목을 선택해주세요.', 'warning');
			return;
		}

		const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
		modal.show();
	}

	/**
	 
	 * 역할: 타임라인 삭제 - 체크박스 방식에 맞게 수정
	 */
	function deleteTimelines() {
		const selectedIdxs = [];
		$('.timeline-checkbox:checked').each(function() {
			selectedIdxs.push($(this).data('idx'));
		});

		$.ajax({
			url: baseUrl + 'timeline/delete_timelines',
			type: 'POST',
			dataType: 'json',
			data: { idxs: selectedIdxs },
			success: function(response) {
				if (response.success) {
					showToast(response.message, 'success');
					bootstrap.Modal.getInstance(document.getElementById('deleteModal')).hide();
					searchTimelines();
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
	 * 폼 초기화
	 */
	function resetForm() {
		$('#timelineForm')[0].reset();
		$('#timeline_idx').val('');
		$('#timeline_mode').val('add');

		if ($('#member_select').data('select2')) {
			$('#member_select').val(null).trigger('change');
		}
	}
});
