/**
 * 역할: 메모 관리 화면 동작 제어
 */

$(document).ready(function() {
	const baseUrl = window.memoPageData.baseUrl;
	const currentOrgId = window.memoPageData.currentOrgId;
	let memoGrid;
	let memoTypes = [];

	// 전송 히스토리 년월 초기화
	initHistoryYearMonth();


	// PQGrid 초기화
	initPQGrid();

	// 메모 항목 로드
	loadMemoTypes();

	// 이벤트 바인딩
	bindEvents();




	/**
	 * PQGrid 초기화
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
					const checkboxId = 'memo-checkbox-' + ui.rowData.idx;
					return '<input type="checkbox" class="memo-checkbox" id="' + checkboxId + '" data-idx="' + ui.rowData.idx + '" />';
				}
			},
			{
				title: '항목',
				dataIndx: 'memo_type',
				dataType: 'string',
				width: 120
			},
			{
				title: '출석일',
				dataIndx: 'memo_date',
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
				dataIndx: 'memo_content',
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
			url: baseUrl + 'memos/get_memos',
			postData: function() {
				const selectedTypes = [];
				if (!$('#searchType_all').is(':checked')) {
					$('.memo-type-checkbox:checked').each(function() {
						selectedTypes.push($(this).val());
					});
				}

				return {
					org_id: currentOrgId,
					memo_types: selectedTypes,
					search_text: $('#searchText').val(),
					year: $('#historyYear').val(),
					month: $('#historyMonth').val()
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
			strNoRows: '조회된 메모가 없습니다.',
			loading: { show: false },
			cellClick: function(event, ui) {
				if (ui.dataIndx === 'pq_selected') {
					handleCheckboxColumnClick(event, ui.rowData.idx);
				} else {
					editMemo(ui.rowData);
				}
			},
			beforeRemoteRequest: function(event, ui) {
				showGridLoading();
			},
			load: function(event, ui) {
				hideGridLoading();
			}
		};

		memoGrid = pq.grid('#memoGrid', gridOptions);
	}

	/**
	 * 이벤트 바인딩
	 */
	function bindEvents() {
		$('#btnSearch').on('click', searchMemos);
		$('#searchText').on('keypress', function(e) {
			if (e.which === 13) {
				searchMemos();
			}
		});

		$('#btnAdd').on('click', showAddForm);
		$('#btnDelete').on('click', showDeleteConfirm);

		$('#btnSaveMemo').on('click', saveMemo);
		$('#confirmDeleteBtn').on('click', deleteMemos);

		$('#memoOffcanvas').on('hidden.bs.offcanvas', resetForm);

		$(document).on('change', '#selectAllCheckbox', function() {
			const isChecked = $(this).prop('checked');
			$('.memo-checkbox').prop('checked', isChecked);
			updateSelectedMemoButtons();
		});

		$(document).on('change', '.memo-checkbox', function() {
			updateSelectAllCheckbox();
			updateSelectedMemoButtons();
		});

		// 연/월 변경 이벤트
		$('#historyYear, #historyMonth').on('change', function() {
			searchMemos();
		});

		// 이전월 버튼
		$('#btnPrevMonth').on('click', function() {
			let year = parseInt($('#historyYear').val());
			let month = parseInt($('#historyMonth').val());

			month--;
			if (month < 1) {
				month = 12;
				year--;
			}

			$('#historyYear').val(year);
			$('#historyMonth').val(month);
			searchMemos();
		});

		// 다음월 버튼
		$('#btnNextMonth').on('click', function() {
			let year = parseInt($('#historyYear').val());
			let month = parseInt($('#historyMonth').val());

			month++;
			if (month > 12) {
				month = 1;
				year++;
			}

			$('#historyYear').val(year);
			$('#historyMonth').val(month);
			searchMemos();
		});
	}

	/**
	 * 그리드 로딩 스피너 표시
	 */
	function showGridLoading() {
		$('#memoGridLoading').show();
	}

	/**
	 * 그리드 로딩 스피너 숨김
	 */
	function hideGridLoading() {
		$('#memoGridLoading').hide();
	}

	/**
	 * 역할: 체크박스 컬럼 클릭 핸들러
	 */
	function handleCheckboxColumnClick(event, idx) {
		const isDirectCheckboxClick = $(event.target).hasClass('memo-checkbox') ||
			$(event.originalEvent?.target).hasClass('memo-checkbox');

		if (!isDirectCheckboxClick) {
			const checkbox = $('.memo-checkbox[data-idx="' + idx + '"]').first();
			if (checkbox.length > 0) {
				const isCurrentlyChecked = checkbox.is(':checked');
				checkbox.prop('checked', !isCurrentlyChecked);
			}
		}

		updateSelectAllCheckbox();
		updateSelectedMemoButtons();
	}

	/**
	 * 역할: 전체 선택 체크박스 상태 업데이트
	 */
	function updateSelectAllCheckbox() {
		const totalCheckboxes = $('.memo-checkbox').length;
		const checkedCheckboxes = $('.memo-checkbox:checked').length;

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
	 * 역할: 선택된 메모에 따라 버튼 활성화/비활성화
	 */
	function updateSelectedMemoButtons() {
		const checkedCount = $('.memo-checkbox:checked').length;

		if (checkedCount === 0) {
			$('#btnDelete').prop('disabled', true);
		} else {
			$('#btnDelete').prop('disabled', false);
		}
	}

	/**
	 * 메모 항목 로드
	 */
	function loadMemoTypes() {
		$.ajax({
			url: baseUrl + 'memos/get_memo_types',
			type: 'POST',
			dataType: 'json',
			data: { org_id: currentOrgId },
			success: function(response) {
				if (response.success) {
					memoTypes = response.data;
					updateMemoTypeSelect();
				}
			},
			error: function() {
				showToast('메모 항목 로드에 실패했습니다.', 'error');
			}
		});
	}

	/**
	 * 메모 항목 셀렉트 업데이트
	 */
	function updateMemoTypeSelect() {
		const $searchMenu = $('#searchTypeMenu');

		$searchMenu.find('li:gt(1)').remove();

		memoTypes.forEach(function(type) {
			const itemId = 'searchType_' + type.replace(/\s+/g, '_');
			const $li = $('<li></li>');
			const $div = $('<div class="dropdown-item"></div>');
			const $checkbox = $('<input type="checkbox" class="form-check-input me-2 memo-type-checkbox">');
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

		$('#memo_type').empty();
		$('#memo_type').append('<option value="">항목을 선택하세요</option>');
		memoTypes.forEach(function(type) {
			$('#memo_type').append(`<option value="${type}">${type}</option>`);
		});

		bindDropdownEvents();
	}

	/**
	 * 드롭다운 이벤트 바인딩
	 */
	function bindDropdownEvents() {
		$('#searchType_all').on('change', function() {
			const isChecked = $(this).is(':checked');
			$('.memo-type-checkbox').prop('checked', false);
			if (isChecked) {
				updateSearchTypeText();
			}
		});

		$(document).off('change', '.memo-type-checkbox').on('change', '.memo-type-checkbox', function() {
			if ($(this).is(':checked')) {
				$('#searchType_all').prop('checked', false);
			}

			if ($('.memo-type-checkbox:checked').length === 0) {
				$('#searchType_all').prop('checked', true);
			}

			updateSearchTypeText();
		});

		$('#searchTypeMenu .dropdown-item').on('click', function(e) {
			e.stopPropagation();
		});
	}

	/**
	 * 선택된 메모 항목 텍스트 업데이트
	 */
	function updateSearchTypeText() {
		const $allCheckbox = $('#searchType_all');
		const $typeCheckboxes = $('.memo-type-checkbox:checked');

		if ($allCheckbox.is(':checked') || $typeCheckboxes.length === 0) {
			$('#searchTypeText').text('전체');
		} else if ($typeCheckboxes.length === 1) {
			$('#searchTypeText').text($typeCheckboxes.first().val());
		} else {
			$('#searchTypeText').text($typeCheckboxes.length + '개 항목 선택');
		}
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
	 * 이벤트 바인딩
	 */
	function bindEvents() {
		$('#btnSearch').on('click', searchMemos);
		$('#searchText').on('keypress', function(e) {
			if (e.which === 13) {
				searchMemos();
			}
		});

		$('#btnAdd').on('click', showAddForm);
		$('#btnDelete').on('click', showDeleteConfirm);

		$('#btnSaveMemo').on('click', saveMemo);
		$('#confirmDeleteBtn').on('click', deleteMemos);

		$('#memoOffcanvas').on('hidden.bs.offcanvas', resetForm);

		$(document).on('change', '#selectAllCheckbox', function() {
			const isChecked = $(this).prop('checked');
			$('.memo-checkbox').prop('checked', isChecked);
			updateSelectedMemoButtons();
		});

		$(document).on('change', '.memo-checkbox', function() {
			updateSelectAllCheckbox();
			updateSelectedMemoButtons();
		});

		// 연/월 변경 이벤트
		$('#historyYear, #historyMonth').on('change', function() {
			searchMemos();
		});

		// 이전월 버튼
		$('#btnPrevMonth').on('click', function() {
			let year = parseInt($('#historyYear').val());
			let month = parseInt($('#historyMonth').val());

			month--;
			if (month < 1) {
				month = 12;
				year--;
			}

			$('#historyYear').val(year);
			$('#historyMonth').val(month);
			searchMemos();
		});

		// 다음월 버튼
		$('#btnNextMonth').on('click', function() {
			let year = parseInt($('#historyYear').val());
			let month = parseInt($('#historyMonth').val());

			month++;
			if (month > 12) {
				month = 1;
				year++;
			}

			$('#historyYear').val(year);
			$('#historyMonth').val(month);
			searchMemos();
		});
	}

	/**
	 * 역할: 메모 검색 - 검색 후 버튼 상태 초기화
	 */
	function searchMemos() {
		showGridLoading();
		memoGrid.refreshDataAndView();

		setTimeout(function() {
			updateSelectAllCheckbox();
			updateSelectedMemoButtons();
			hideGridLoading();
		}, 100);
	}

	/**
	 * 추가 폼 표시
	 */
	function showAddForm() {
		resetForm();
		$('#memo_mode').val('add');
		$('#memoOffcanvasLabel').text('메모 일괄추가');
		$('#memberSelectDiv').show();
		$('#memberNameDiv').hide();

		const today = new Date();
		const year = today.getFullYear();
		const month = String(today.getMonth() + 1).padStart(2, '0');
		const day = String(today.getDate()).padStart(2, '0');
		$('#memo_date').val(`${year}-${month}-${day}`);

		initMemberSelect2();

		const offcanvas = new bootstrap.Offcanvas(document.getElementById('memoOffcanvas'));
		offcanvas.show();
	}

	/**
	 * 메모 수정
	 */
	function editMemo(rowData) {
		$.ajax({
			url: baseUrl + 'memos/get_memo_detail',
			type: 'POST',
			dataType: 'json',
			data: { idx: rowData.idx },
			success: function(response) {
				if (response.success) {
					const memo = response.data;
					$('#memo_mode').val('edit');
					$('#memo_idx').val(memo.idx);
					$('#memo_type').val(memo.memo_type);
					$('#memo_date').val(memo.memo_date);
					$('#memo_content').val(memo.memo_content);
					$('#member_name_display').val(memo.member_name);

					$('#memoOffcanvasLabel').text('메모 수정');
					$('#memberSelectDiv').hide();
					$('#memberNameDiv').show();

					const offcanvas = new bootstrap.Offcanvas(document.getElementById('memoOffcanvas'));
					offcanvas.show();
				} else {
					showToast(response.message || '메모 정보를 불러올 수 없습니다.', 'error');
				}
			},
			error: function() {
				showToast('메모 정보 로드에 실패했습니다.', 'error');
			}
		});
	}

	/**
	 * 메모 저장
	 */
	function saveMemo() {
		const mode = $('#memo_mode').val();
		const memo_type = $('#memo_type').val();
		const memo_date = $('#memo_date').val();

		if (!memo_type) {
			showToast('항목을 선택해주세요.', 'warning');
			return;
		}

		if (!memo_date) {
			showToast('날짜를 입력해주세요.', 'warning');
			return;
		}

		if (mode === 'add') {
			const member_idxs = $('#member_select').val();
			if (!member_idxs || member_idxs.length === 0) {
				showToast('회원을 선택해주세요.', 'warning');
				return;
			}

			addMemo();
		} else {
			updateMemo();
		}
	}

	/**
	 * 메모 일괄추가
	 */
	function addMemo() {
		const formData = {
			org_id: currentOrgId,
			member_idxs: $('#member_select').val(),
			memo_type: $('#memo_type').val(),
			memo_date: $('#memo_date').val(),
			memo_content: $('#memo_content').val()
		};

		$.ajax({
			url: baseUrl + 'memos/add_memo',
			type: 'POST',
			dataType: 'json',
			data: formData,
			success: function(response) {
				if (response.success) {
					showToast(response.message, 'success');
					bootstrap.Offcanvas.getInstance(document.getElementById('memoOffcanvas')).hide();
					searchMemos();
				} else {
					showToast(response.message || '메모 일괄추가에 실패했습니다.', 'error');
				}
			},
			error: function() {
				showToast('메모 일괄추가에 실패했습니다.', 'error');
			}
		});
	}

	/**
	 * 메모 수정
	 */
	function updateMemo() {
		const formData = {
			idx: $('#memo_idx').val(),
			memo_type: $('#memo_type').val(),
			memo_date: $('#memo_date').val(),
			memo_content: $('#memo_content').val()
		};

		$.ajax({
			url: baseUrl + 'memos/update_memo',
			type: 'POST',
			dataType: 'json',
			data: formData,
			success: function(response) {
				if (response.success) {
					showToast(response.message, 'success');
					bootstrap.Offcanvas.getInstance(document.getElementById('memoOffcanvas')).hide();
					searchMemos();
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
	 * 삭제 확인 모달 표시
	 */
	function showDeleteConfirm() {
		const selectedIdxs = $('.memo-checkbox:checked');

		if (selectedIdxs.length === 0) {
			showToast('삭제할 항목을 선택해주세요.', 'warning');
			return;
		}

		const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
		modal.show();
	}

	/**
	 * 역할: 메모 삭제 - 체크박스 방식에 맞게 수정
	 */
	function deleteMemos() {
		const selectedIdxs = [];
		$('.memo-checkbox:checked').each(function() {
			selectedIdxs.push($(this).data('idx'));
		});

		$.ajax({
			url: baseUrl + 'memos/delete_memos',
			type: 'POST',
			dataType: 'json',
			data: { idxs: selectedIdxs },
			success: function(response) {
				if (response.success) {
					showToast(response.message, 'success');
					bootstrap.Modal.getInstance(document.getElementById('deleteModal')).hide();
					searchMemos();
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
	 * 폼 초기화
	 */
	function resetForm() {
		$('#memoForm')[0].reset();
		$('#memo_idx').val('');
		$('#memo_mode').val('add');

		if ($('#member_select').data('select2')) {
			$('#member_select').val(null).trigger('change');
		}
	}


	/**
	 * 전체 회원 데이터 로드
	 */
	function loadAllMembers(callback) {
		$.ajax({
			url: baseUrl + 'memos/get_all_members',
			type: 'POST',
			dataType: 'json',
			data: { org_id: currentOrgId },
			success: function(response) {
				if (response.success && typeof callback === 'function') {
					callback(response.data);
				}
			},
			error: function() {
				showToast('회원 목록 로드에 실패했습니다.', 'error');
				if (typeof callback === 'function') {
					callback([]);
				}
			}
		});
	}

	/**
	 * Select2 초기화 (전체 데이터 미리 로드 방식)
	 */
	function initMemberSelect2() {
		loadAllMembers(function(members) {
			$('#member_select').select2({
				width: '100%',
				placeholder: '회원을 선택하세요',
				allowClear: true,
				multiple: true,
				closeOnSelect: false,
				data: members.map(function(member) {
					return {
						id: member.member_idx,
						text: member.member_name
					};
				}),
				language: {
					noResults: function() {
						return '검색 결과가 없습니다.';
					},
					searching: function() {
						return '검색 중...';
					}
				},
				matcher: function(params, data) {
					if ($.trim(params.term) === '') {
						return data;
					}

					if (typeof data.text === 'undefined') {
						return null;
					}

					if (data.text.indexOf(params.term) > -1) {
						return data;
					}

					return null;
				},
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

			$('#member_select').select2Sortable();
		});
	}

});
