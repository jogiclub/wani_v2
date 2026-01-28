/**
 * 파일 위치: assets/js/income.js
 * 역할: 수입지출 관리 화면의 클라이언트 로직
 */

var selectedBookIdx = null;
var incomeGrid = null;
var entryItemGrid = null;
var selectedBanks = [];
var selectedAccounts = [];
var selectedTags = [];
var allBanks = [];
var allIncomeAccounts = [];
var allExpenseAccounts = [];
var allUsedTags = [];
var allMembers = [];
var entryItemData = [];

// 로컬스토리지 키
var STORAGE_KEY_BANK = 'income_last_bank';
var STORAGE_KEY_ACCOUNT_INCOME = 'income_last_account_income';
var STORAGE_KEY_ACCOUNT_EXPENSE = 'income_last_account_expense';


$(document).ready(function() {
	initPage();
});

/**
 * 페이지 초기화
 */
function initPage() {
	loadBookList();
	initDateRangePicker();
	initGrid();
	bindEvents();
}

/**
 * 파일 위치: assets/js/income.js
 * 역할: 이벤트 바인딩
 */
function bindEvents() {
	// 전체 선택 체크박스
	$(document).on('change', '#selectAllCheckbox', function() {
		var isChecked = $(this).prop('checked');
		$('.income-checkbox').prop('checked', isChecked);
	});

	// 개별 체크박스
	$(document).on('change', '.income-checkbox', function() {
		updateSelectAllCheckbox();
	});

	// 검색 버튼
	$('#btnSearch').on('click', function() {
		loadGridData();
	});

	// 초기화 버튼
	$('#btnReset').on('click', function() {
		resetSearch();
	});

	// 수입 입력 버튼
	$('#btnAddIncome').on('click', function() {
		openEntryOffcanvas('income');
	});

	// 지출 입력 버튼
	$('#btnAddExpense').on('click', function() {
		openEntryOffcanvas('expense');
	});

	// 선택 삭제
	$('#btnDeleteSelected').on('click', function() {
		deleteSelected();
	});

	// 삭제 확인
	$('#btnConfirmDelete').on('click', function() {
		confirmDelete();
	});

	// 금액/적요 입력 필드에서 Enter 키로 항목 추가
	$('#entryAmount, #entryMemo').on('keypress', function(e) {
		if (e.which === 13) {
			e.preventDefault();
			addEntryItem();
		}
	});

	// 적용 버튼 (저장)
	$('#btnApplyEntry').on('click', function() {
		saveEntry();
	});

	// 취소 버튼
	$('#btnCancelEntry').on('click', function() {
		var offcanvasEl = document.getElementById('offcanvasEntry');
		var offcanvas = bootstrap.Offcanvas.getInstance(offcanvasEl);
		if (offcanvas) {
			offcanvas.hide();
		}
	});

	// 금액 입력 포맷
	$('#entryAmount').on('input', function() {
		var val = $(this).val().replace(/[^\d]/g, '');
		$(this).val(formatNumber(val));
	});

	// 멀티셀렉트 드롭다운 클릭 이벤트 전파 방지
	$('.multi-select-dropdown .dropdown-menu').on('click', function(e) {
		e.stopPropagation();
	});

	// 회원 검색 셀렉트 초기화
	initMemberSearchSelect();
}

/**
 * 파일 위치: assets/js/income.js
 * 역할: 검색 가능 셀렉트 초기화 (계좌)
 */
function initMemberSearchSelect() {
	// 계좌 검색
	initSearchableSelect('#entryBank', '#entryBankSearch', function(keyword) {
		return allBanks.filter(function(bank) {
			var searchText = bank.name + (bank.account_number || '');
			return searchText.toLowerCase().indexOf(keyword.toLowerCase()) > -1;
		});
	}, function(item) {
		var displayText = item.name;
		if (item.account_number) {
			displayText += ' (' + item.account_number + ')';
		}
		return { value: item.name, text: displayText };
	}, function(value, text, extra) {
		// 계좌 선택 시 로컬스토리지에 저장
		localStorage.setItem(STORAGE_KEY_BANK, value);
	});
}


/**
 * 파일 위치: assets/js/income.js
 * 역할: 이름 Select2 초기화 (tags 스타일)
 */
function initNameSelect2() {
	// 기존 Select2 인스턴스 제거
	if ($('#entryName').hasClass('select2-hidden-accessible')) {
		$('#entryName').select2('destroy');
	}

	$('#entryName').empty();

	// 회원 목록을 옵션으로 추가
	allMembers.forEach(function(member) {
		var text = member.member_name;
		if (member.member_phone) {
			text += ' (' + member.member_phone + ')';
		}
		var option = new Option(text, member.member_name, false, false);
		$('#entryName').append(option);
	});

	// Select2 초기화
	$('#entryName').select2({
		width: '100%',
		placeholder: '이름을 입력하거나 선택하세요',
		tags: true,
		allowClear: true,
		tokenSeparators: [','],
		dropdownParent: $('#offcanvasEntry'),
		createTag: function(params) {
			var term = $.trim(params.term);
			if (term === '') {
				return null;
			}
			return {
				id: term,
				text: term,
				newTag: true
			};
		},
		language: {
			noResults: function() {
				return '검색 결과가 없습니다';
			},
			searching: function() {
				return '검색 중...';
			}
		}
	});
}


/**
 * 파일 위치: assets/js/income.js
 * 역할: 계정 검색 셀렉트 초기화 (로컬스토리지 저장 포함)
 */
function initAccountSearchSelect(type) {
	var accounts = type === 'income' ? allIncomeAccounts : allExpenseAccounts;
	var storageKey = type === 'income' ? STORAGE_KEY_ACCOUNT_INCOME : STORAGE_KEY_ACCOUNT_EXPENSE;

	initSearchableSelect('#entryAccount', '#entryAccountSearch', function(keyword) {
		return accounts.filter(function(account) {
			var searchText = account.code + ' ' + account.name;
			return searchText.toLowerCase().indexOf(keyword.toLowerCase()) > -1;
		});
	}, function(item) {
		return {
			value: item.code,
			text: item.display,
			extra: { name: item.name }
		};
	}, function(value, text, extra) {
		// 계정 선택 시 계정명도 함께 저장
		if (extra && extra.name) {
			$('#entryAccountName').val(extra.name);
		}
		// 로컬스토리지에 저장
		localStorage.setItem(storageKey, JSON.stringify({ code: value, name: extra ? extra.name : '', display: text }));
	});
}

/**
 * 파일 위치: assets/js/income.js
 * 역할: 검색 가능한 셀렉트박스 공통 초기화
 */
function initSearchableSelect(selectId, searchInputId, filterFn, mapFn, onSelectCallback) {
	var $select = $(selectId);
	var $searchInput = $(searchInputId);
	var $dropdown = $select.closest('.searchable-select-wrapper').find('.dropdown-menu');

	// 검색 입력 이벤트
	$searchInput.on('input', function() {
		var keyword = $(this).val().trim();
		var $optionList = $dropdown.find('.option-list');
		$optionList.empty();

		var filteredItems = filterFn(keyword);

		if (filteredItems.length === 0) {
			$optionList.append('<li class="dropdown-item text-muted">검색 결과가 없습니다</li>');
		} else {
			filteredItems.slice(0, 50).forEach(function(item) {
				var mapped = mapFn(item);
				var extraData = mapped.extra ? ' data-extra=\'' + JSON.stringify(mapped.extra) + '\'' : '';
				$optionList.append('<li class="dropdown-item option-item" data-value="' + escapeHtml(mapped.value) + '"' + extraData + '>' + escapeHtml(mapped.text) + '</li>');
			});
		}
	});

	// 옵션 선택 이벤트
	$dropdown.off('click', '.option-item').on('click', '.option-item', function() {
		var value = $(this).data('value');
		var text = $(this).text();
		var extraData = $(this).data('extra');

		$select.val(value);
		$select.closest('.searchable-select-wrapper').find('.selected-text').text(text || '선택');
		$dropdown.removeClass('show');

		// 콜백 실행
		if (typeof onSelectCallback === 'function') {
			onSelectCallback(value, text, extraData);
		}
	});

	// 드롭다운 열 때 검색어 초기화 및 전체 목록 표시
	$select.closest('.searchable-select-wrapper').find('.dropdown-toggle').off('click').on('click', function() {
		$searchInput.val('');
		$searchInput.trigger('input');
		setTimeout(function() {
			$searchInput.focus();
		}, 100);
	});
}

/**
 * DateRangePicker 초기화
 */
function initDateRangePicker() {
	$('#searchDateRange').daterangepicker({
		autoUpdateInput: false,
		locale: {
			format: 'YYYY-MM-DD',
			separator: ' ~ ',
			applyLabel: '적용',
			cancelLabel: '취소',
			fromLabel: '시작',
			toLabel: '종료',
			customRangeLabel: '직접 선택',
			weekLabel: 'W',
			daysOfWeek: ['일', '월', '화', '수', '목', '금', '토'],
			monthNames: ['1월', '2월', '3월', '4월', '5월', '6월', '7월', '8월', '9월', '10월', '11월', '12월'],
			firstDay: 0
		},
		ranges: {
			'오늘': [moment(), moment()],
			'이번 주': [moment().startOf('week'), moment().endOf('week')],
			'이번 달': [moment().startOf('month'), moment().endOf('month')],
			'지난 달': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
			'최근 3개월': [moment().subtract(3, 'month'), moment()],
			'올해': [moment().startOf('year'), moment().endOf('year')]
		}
	});

	$('#searchDateRange').on('apply.daterangepicker', function(ev, picker) {
		$(this).val(picker.startDate.format('YYYY-MM-DD') + ' ~ ' + picker.endDate.format('YYYY-MM-DD'));
	});

	$('#searchDateRange').on('cancel.daterangepicker', function(ev, picker) {
		$(this).val('');
	});
}

/**
 * 파일 위치: assets/js/income.js
 * 역할: pqGrid 초기화 - 체크박스 직접 렌더링 방식
 */
function initGrid() {
	var colModel = [
		{
			title: '<input type="checkbox" id="selectAllCheckbox" />',
			dataIndx: 'pq_selected',
			width: 40,
			align: 'center',
			resizable: false,
			sortable: false,
			editable: false,
			menuIcon: false,
			render: function(ui) {
				var checkboxId = 'income-checkbox-' + ui.rowData.idx;
				return '<input type="checkbox" class="income-checkbox" id="' + checkboxId + '" data-idx="' + ui.rowData.idx + '" />';
			}
		},
		{
			title: '구분',
			dataIndx: 'income_type',
			width: 60,
			align: 'center',
			render: function(ui) {
				var val = ui.cellData;
				if (val === 'income') {
					return '<span class="income-row">수입</span>';
				} else {
					return '<span class="expense-row">지출</span>';
				}
			}
		},
		{
			title: '계좌',
			dataIndx: 'bank',
			width: 100,
			render: function(ui) {
				if (ui.cellData) {
					try {
						var bankData = JSON.parse(ui.cellData);
						return bankData.name || '';
					} catch(e) {
						return ui.cellData;
					}
				}
				return '';
			}
		},
		{ title: '계정코드', dataIndx: 'account_code', width: 80, align: 'center' },
		{ title: '계정명', dataIndx: 'account_name', width: 120 },
		{ title: '날짜', dataIndx: 'transaction_date_fmt', width: 100, align: 'center' },
		{ title: '건수', dataIndx: 'transaction_cnt', width: 50, align: 'center' },
		{
			title: '금액',
			dataIndx: 'amount',
			width: 120,
			align: 'center',
			render: function(ui) {
				var rowData = ui.rowData;
				var cls = rowData.income_type === 'income' ? 'income-cell' : 'expense-cell';
				return '<span class="' + cls + '">' + formatNumber(ui.cellData) + '</span>';
			}
		},
		{
			title: 'TAG',
			dataIndx: 'tags',
			width: 100,
			render: function(ui) {
				if (ui.cellData) {
					try {
						var tags = JSON.parse(ui.cellData);
						if (Array.isArray(tags)) {
							return tags.join(', ');
						}
					} catch(e) {
						return ui.cellData;
					}
				}
				return '';
			}
		},
		{ title: '등록일', dataIndx: 'regi_date_fmt', width: 130, align: 'center' },
		{ title: '등록자', dataIndx: 'regi_user_name', width: 80, align: 'center' },
		{ title: '수정일', dataIndx: 'modi_date_fmt', width: 130, align: 'center' },
		{ title: '수정자', dataIndx: 'modi_user_name', width: 80, align: 'center' }
	];

	incomeGrid = pq.grid('#incomeGrid', {
		width: '100%',
		height: "100%",
		colModel: colModel,
		dataModel: { data: [] },
		editable: false,
		selectionModel: { type: 'row', mode: 'block' },
		numberCell: { show: false },
		wrap: false,
		resizable: true,
		showTitle: false,
		showToolbar: false,
		showBottom: false,
		showHeader: true,
		freezeCols: 5,
		strNoRows: '조회된 데이터가 없습니다.',
		cellClick: function(event, ui) {
			if (ui.dataIndx === 'pq_selected') {
				handleCheckboxClick(event, ui.rowData.idx);
			}
		},
		rowDblClick: function(event, ui) {
			var rowData = ui.rowData;
			openEntryOffcanvas(rowData.income_type, rowData);
		}
	});
}

/**
 * 파일 위치: assets/js/income.js
 * 역할: 입력 항목 pqGrid 초기화
 */
function initEntryItemGrid() {
	if (entryItemGrid) {
		entryItemGrid.destroy();
	}

	var colModel = [
		{
			title: '이름',
			dataIndx: 'name',
			width: 100,
			align: 'center'
		},
		{
			title: '금액',
			dataIndx: 'amount',
			width: 100,
			align: 'center',
			render: function(ui) {
				return formatNumber(ui.cellData);
			}
		},
		{
			title: '적요',
			dataIndx: 'memo',
			width: 150
		},
		{
			title: '',
			dataIndx: 'action',
			width: 40,
			align: 'center',
			sortable: false,
			render: function(ui) {
				return '<button type="button" class="btn btn-sm btn-outline-danger btn-remove-item" data-row="' + ui.rowIndx + '"><i class="bi bi-trash"></i></button>';
			}
		}
	];

	entryItemGrid = pq.grid('#entryItemGrid', {
		width: '100%',
		height: 500,
		colModel: colModel,



		dataModel: { data: entryItemData },
		editable: false,
		selectionModel: { type: 'row', mode: 'single' },
		numberCell: { show: false },
		wrap: false,
		resizable: false,
		showTitle: false,
		showToolbar: false,
		showBottom: false,
		showHeader: true,
		strNoRows: '항목을 추가해주세요.'
	});

	// 삭제 버튼 이벤트
	$('#entryItemGrid').on('click', '.btn-remove-item', function() {
		var rowIndx = $(this).data('row');
		removeEntryItem(rowIndx);
	});

	updateEntryItemCount();
}

/**
 * 파일 위치: assets/js/income.js
 * 역할: 항목 추가 (다중 이름 지원)
 */
function addEntryItem() {
	var selectedNames = $('#entryName').val(); // Select2 다중선택 값 (배열)
	var amount = $('#entryAmount').val().replace(/[^\d]/g, '');
	var memo = $('#entryMemo').val().trim();

	if (!selectedNames || selectedNames.length === 0) {
		showToast('이름을 선택해주세요.', 'warning');
		$('#entryName').select2('open');
		return;
	}

	if (!amount || parseInt(amount) <= 0) {
		showToast('금액을 입력해주세요.', 'warning');
		$('#entryAmount').focus();
		return;
	}

	var amountValue = parseInt(amount);

	// 선택된 이름 수만큼 항목 추가
	selectedNames.forEach(function(name) {
		entryItemData.push({
			name: name,
			amount: amountValue,
			memo: memo
		});
	});

	// 그리드 갱신
	entryItemGrid.option('dataModel.data', entryItemData);
	entryItemGrid.refreshDataAndView();

	// 입력 필드 초기화 (금액, 적요, 이름)
	$('#entryName').val(null).trigger('change');
	$('#entryAmount').val('');
	$('#entryMemo').val('');

	// 금액 필드로 포커스
	$('#entryAmount').focus();

	updateEntryItemCount();

	// 추가 완료 메시지 (2명 이상일 경우만)
	if (selectedNames.length > 1) {
		showToast(selectedNames.length + '명이 추가되었습니다.', 'success');
	}
}

/**
 * 파일 위치: assets/js/income.js
 * 역할: 항목 삭제
 */
function removeEntryItem(rowIndx) {
	entryItemData.splice(rowIndx, 1);
	entryItemGrid.option('dataModel.data', entryItemData);
	entryItemGrid.refreshDataAndView();
	updateEntryItemCount();
}

/**
 * 파일 위치: assets/js/income.js
 * 역할: 항목 건수 업데이트
 */
function updateEntryItemCount() {
	var count = entryItemData.length;
	var totalAmount = entryItemData.reduce(function(sum, item) {
		return sum + (parseInt(item.amount) || 0);
	}, 0);

	$('#entryItemCount').text(count + '건');
	$('#entryTotalAmount').text(formatNumber(totalAmount) + '원');
}

/**
 * 파일 위치: assets/js/income.js
 * 역할: 체크박스 클릭 핸들러
 */
function handleCheckboxClick(event, idx) {
	var isDirectCheckboxClick = $(event.target).hasClass('income-checkbox') ||
		$(event.originalEvent?.target).hasClass('income-checkbox');

	if (!isDirectCheckboxClick) {
		var checkbox = $('.income-checkbox[data-idx="' + idx + '"]').first();
		if (checkbox.length > 0) {
			var isCurrentlyChecked = checkbox.is(':checked');
			checkbox.prop('checked', !isCurrentlyChecked);
		}
	}

	updateSelectAllCheckbox();
}

/**
 * 파일 위치: assets/js/income.js
 * 역할: 전체 선택 체크박스 상태 업데이트
 */
function updateSelectAllCheckbox() {
	var totalCheckboxes = $('.income-checkbox').length;
	var checkedCheckboxes = $('.income-checkbox:checked').length;

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
 * 장부 목록 로드
 */
function loadBookList() {
	$.ajax({
		url: window.incomePageData.baseUrl + 'account/get_book_list',
		method: 'POST',
		data: { org_id: window.incomePageData.orgId },
		dataType: 'json',
		success: function(response) {
			if (response.success) {
				renderBookList(response.data);
			} else {
				showToast(response.message || '장부 목록을 불러오는데 실패했습니다.', 'error');
			}
		},
		error: function() {
			showToast('장부 목록을 불러오는데 실패했습니다.', 'error');
		}
	});
}

/**
 * 파일 위치: assets/js/income.js
 * 역할: 장부 목록 렌더링 - 1개면 라벨, 2개 이상이면 selectbox
 */
function renderBookList(books) {
	var $container = $('#bookSelectContainer');
	$container.empty();

	if (!books || books.length === 0) {
		$container.append('<span class="text-muted">등록된 장부가 없습니다</span>');
		return;
	}

	if (books.length === 1) {
		var book = books[0];
		$container.append('<span class="form-control-plaintext fw-bold">' + book.book_name + '</span>');
		$container.append('<input type="hidden" id="selectBook" value="' + book.book_idx + '">');
		selectedBookIdx = book.book_idx;
		onBookSelected();
	} else {
		var selectHtml = '<select id="selectBook" class="form-select form-select-sm" style="width:auto;">';
		selectHtml += '<option value="">장부 선택</option>';
		books.forEach(function(book) {
			selectHtml += '<option value="' + book.book_idx + '">' + book.book_name + '</option>';
		});
		selectHtml += '</select>';
		$container.append(selectHtml);

		$('#selectBook').on('change', function() {
			selectedBookIdx = $(this).val();
			if (selectedBookIdx) {
				onBookSelected();
			}
		});
	}
}

/**
 * 장부 선택 시 처리
 */
function onBookSelected() {
	$('#searchArea').show();
	loadBanks();
	loadAccounts();
	loadTags();
	loadMembers();
	loadGridData();
}

/**
 * 계좌(은행) 목록 로드
 */
function loadBanks() {
	$.ajax({
		url: window.incomePageData.baseUrl + 'account/get_banks',
		method: 'POST',
		data: { book_idx: selectedBookIdx },
		dataType: 'json',
		success: function(response) {
			if (response.success) {
				allBanks = response.data || [];
				renderBankDropdown();
				// renderEntryBankSelect();
			}
		}
	});
}

/**
 * 계좌 드롭다운 렌더링
 */
function renderBankDropdown() {
	var $menu = $('#bankDropdownMenu');
	$menu.empty();
	selectedBanks = [];

	if (allBanks.length === 0) {
		$menu.append('<li class="dropdown-item text-muted">등록된 계좌가 없습니다</li>');
		return;
	}

	allBanks.forEach(function(bank) {
		var html = '<li class="dropdown-item">' +
			'<label><input type="checkbox" class="form-check-input me-2 bank-check" value="' + bank.name + '"> ' + bank.name + '</label>' +
			'</li>';
		$menu.append(html);
	});

	$menu.find('.bank-check').on('change', function() {
		updateSelectedBanks();
	});
}

/**
 * 선택된 계좌 업데이트
 */
function updateSelectedBanks() {
	selectedBanks = [];
	$('#bankDropdownMenu .bank-check:checked').each(function() {
		selectedBanks.push($(this).val());
	});

	var btnText = selectedBanks.length > 0 ? '계좌 (' + selectedBanks.length + ')' : '계좌 전체';
	$('#bankDropdown').text(btnText);
}

/**
 * 계정과목 목록 로드
 */
function loadAccounts() {
	$.ajax({
		url: window.incomePageData.baseUrl + 'income/get_accounts',
		method: 'POST',
		data: { book_idx: selectedBookIdx, account_type: 'income' },
		dataType: 'json',
		success: function(response) {
			if (response.success) {
				allIncomeAccounts = response.data || [];
				renderAccountDropdown();
			}
		}
	});

	$.ajax({
		url: window.incomePageData.baseUrl + 'income/get_accounts',
		method: 'POST',
		data: { book_idx: selectedBookIdx, account_type: 'expense' },
		dataType: 'json',
		success: function(response) {
			if (response.success) {
				allExpenseAccounts = response.data || [];
				renderAccountDropdown();
			}
		}
	});
}

/**
 * 계정 드롭다운 렌더링
 */
function renderAccountDropdown() {
	var $menu = $('#accountDropdownMenu');
	$menu.empty();
	selectedAccounts = [];

	var allAccounts = allIncomeAccounts.concat(allExpenseAccounts);

	if (allAccounts.length === 0) {
		$menu.append('<li class="dropdown-item text-muted">등록된 계정이 없습니다</li>');
		return;
	}

	if (allIncomeAccounts.length > 0) {
		$menu.append('<li class="dropdown-header text-primary">수입 계정</li>');
		allIncomeAccounts.forEach(function(account) {
			var html = '<li class="dropdown-item">' +
				'<label><input type="checkbox" class="form-check-input me-2 account-check" value="' + account.code + '"> ' + account.display + '</label>' +
				'</li>';
			$menu.append(html);
		});
	}

	if (allExpenseAccounts.length > 0) {
		$menu.append('<li><hr class="dropdown-divider"></li>');
		$menu.append('<li class="dropdown-header text-danger">지출 계정</li>');
		allExpenseAccounts.forEach(function(account) {
			var html = '<li class="dropdown-item">' +
				'<label><input type="checkbox" class="form-check-input me-2 account-check" value="' + account.code + '"> ' + account.display + '</label>' +
				'</li>';
			$menu.append(html);
		});
	}

	$menu.find('.account-check').on('change', function() {
		updateSelectedAccounts();
	});
}

/**
 * 선택된 계정 업데이트
 */
function updateSelectedAccounts() {
	selectedAccounts = [];
	$('#accountDropdownMenu .account-check:checked').each(function() {
		selectedAccounts.push($(this).val());
	});

	var btnText = selectedAccounts.length > 0 ? '계정 (' + selectedAccounts.length + ')' : '계정 전체';
	$('#accountDropdown').text(btnText);
}

/**
 * 태그 목록 로드
 */
function loadTags() {
	$.ajax({
		url: window.incomePageData.baseUrl + 'income/get_tags',
		method: 'POST',
		data: { book_idx: selectedBookIdx, org_id: window.incomePageData.orgId },
		dataType: 'json',
		success: function(response) {
			if (response.success) {
				allUsedTags = response.data || [];
				renderTagDropdown();
			}
		}
	});
}

/**
 * 태그 드롭다운 렌더링
 */
function renderTagDropdown() {
	var $menu = $('#tagDropdownMenu');
	$menu.empty();
	selectedTags = [];

	if (allUsedTags.length === 0) {
		$menu.append('<li class="dropdown-item text-muted">등록된 TAG가 없습니다</li>');
		return;
	}

	allUsedTags.forEach(function(tag) {
		var html = '<li class="dropdown-item">' +
			'<label><input type="checkbox" class="form-check-input me-2 tag-check" value="' + tag + '"> ' + tag + '</label>' +
			'</li>';
		$menu.append(html);
	});

	$menu.find('.tag-check').on('change', function() {
		updateSelectedTags();
	});
}

/**
 * 선택된 태그 업데이트
 */
function updateSelectedTags() {
	selectedTags = [];
	$('#tagDropdownMenu .tag-check:checked').each(function() {
		selectedTags.push($(this).val());
	});

	var btnText = selectedTags.length > 0 ? 'TAG (' + selectedTags.length + ')' : 'TAG 전체';
	$('#tagDropdown').text(btnText);
}

/**
 * 파일 위치: assets/js/income.js
 * 역할: 회원 목록 로드
 */
function loadMembers() {
	$.ajax({
		url: window.incomePageData.baseUrl + 'income/get_members',
		method: 'POST',
		data: { org_id: window.incomePageData.orgId },
		dataType: 'json',
		success: function(response) {
			if (response.success) {
				allMembers = response.data || [];
			}
		}
	});
}

/**
 * 파일 위치: assets/js/income.js
 * 역할: 그리드 데이터 로드
 */
function loadGridData() {
	if (!selectedBookIdx) return;

	var dateRange = $('#searchDateRange').val();
	var startDate = '';
	var endDate = '';
	if (dateRange) {
		var dates = dateRange.split(' ~ ');
		startDate = dates[0];
		endDate = dates[1];
	}

	var params = {
		book_idx: selectedBookIdx,
		org_id: window.incomePageData.orgId,
		income_type: $('#searchIncomeType').val(),
		bank: selectedBanks,
		account_codes: selectedAccounts,
		tags: selectedTags,
		start_date: startDate,
		end_date: endDate
	};

	$.ajax({
		url: window.incomePageData.baseUrl + 'income/get_list',
		method: 'POST',
		data: params,
		dataType: 'json',
		success: function(response) {
			if (response.success) {
				incomeGrid.option('dataModel.data', response.data);
				incomeGrid.refreshDataAndView();
			} else {
				showToast(response.message || '데이터를 불러오는데 실패했습니다.', 'error');
			}
		},
		error: function() {
			showToast('데이터를 불러오는데 실패했습니다.', 'error');
		}
	});
}

/**
 * 검색 초기화
 */
function resetSearch() {
	$('#searchIncomeType').val('');
	$('#searchDateRange').val('');

	selectedBanks = [];
	selectedAccounts = [];
	selectedTags = [];

	$('#bankDropdown').text('계좌 전체');
	$('#accountDropdown').text('계정 전체');
	$('#tagDropdown').text('TAG 전체');

	$('#bankDropdownMenu .bank-check').prop('checked', false);
	$('#accountDropdownMenu .account-check').prop('checked', false);
	$('#tagDropdownMenu .tag-check').prop('checked', false);

	loadGridData();
}





/**
 * 파일 위치: assets/js/income.js
 * 역할: 입력 Offcanvas 열기 (로컬스토리지 연동)
 */
function openEntryOffcanvas(type, data) {
	var title = type === 'income' ? '수입 입력' : '지출 입력';
	$('#offcanvasEntryLabel').text(data ? (type === 'income' ? '수입 수정' : '지출 수정') : title);
	$('#entryType').val(type);

	// 폼 초기화
	$('#entryIdx').val('');
	$('#entryDate').val(moment().format('YYYY-MM-DD'));
	$('#entryBank').val('');
	$('#entryBankText').text('선택');
	$('#entryAccount').val('');
	$('#entryAccountName').val('');
	$('#entryAccountText').text('선택');
	$('#entryAmount').val('');
	$('#entryMemo').val('');

	// 항목 데이터 초기화
	entryItemData = [];

	// 계정 검색 셀렉트 초기화
	initAccountSearchSelect(type);

	// 이름 Select2 초기화
	initNameSelect2();

	// 수정 모드인 경우 데이터 로드
	if (data) {
		$('#entryIdx').val(data.idx);
		$('#entryDate').val(data.transaction_date_fmt);

		if (data.bank) {
			try {
				var bankData = JSON.parse(data.bank);
				$('#entryBank').val(bankData.name);
				$('#entryBankText').text(bankData.name);
			} catch(e) {
				$('#entryBank').val(data.bank);
				$('#entryBankText').text(data.bank);
			}
		}

		// 계정 선택 설정
		if (data.account_code) {
			$('#entryAccount').val(data.account_code);
			$('#entryAccountName').val(data.account_name || '');
			var accountDisplay = data.account_code + ' ' + (data.account_name || '');
			$('#entryAccountText').text(accountDisplay);
		}

		// details 데이터 로드
		if (data.details) {
			try {
				entryItemData = JSON.parse(data.details);
				if (!Array.isArray(entryItemData)) {
					entryItemData = [];
				}
			} catch(e) {
				entryItemData = [];
			}
		}

		// 기존 데이터에 details가 없는 경우 기존 형식 호환
		if (entryItemData.length === 0 && data.amount) {
			entryItemData.push({
				name: data.account_name || '',
				amount: parseInt(data.amount) || 0,
				memo: data.memo || ''
			});
		}
	} else {
		// 신규 입력 시 로컬스토리지에서 마지막 선택값 불러오기
		loadLastSelections(type);
	}

	// 입력 항목 그리드 초기화
	setTimeout(function() {
		initEntryItemGrid();
	}, 100);

	var offcanvas = new bootstrap.Offcanvas(document.getElementById('offcanvasEntry'));
	offcanvas.show();
}

/**
 * 파일 위치: assets/js/income.js
 * 역할: 로컬스토리지에서 마지막 선택값 불러오기
 */
function loadLastSelections(type) {
	// 계좌 불러오기
	var lastBank = localStorage.getItem(STORAGE_KEY_BANK);
	if (lastBank && allBanks.length > 0) {
		var bankExists = allBanks.some(function(bank) {
			return bank.name === lastBank;
		});
		if (bankExists) {
			$('#entryBank').val(lastBank);
			$('#entryBankText').text(lastBank);
		}
	}

	// 계정 불러오기
	var storageKey = type === 'income' ? STORAGE_KEY_ACCOUNT_INCOME : STORAGE_KEY_ACCOUNT_EXPENSE;
	var lastAccountStr = localStorage.getItem(storageKey);
	if (lastAccountStr) {
		try {
			var lastAccount = JSON.parse(lastAccountStr);
			var accounts = type === 'income' ? allIncomeAccounts : allExpenseAccounts;
			var accountExists = accounts.some(function(account) {
				return account.code === lastAccount.code;
			});
			if (accountExists) {
				$('#entryAccount').val(lastAccount.code);
				$('#entryAccountName').val(lastAccount.name);
				$('#entryAccountText').text(lastAccount.display);
			}
		} catch(e) {
			// 무시
		}
	}
}

/**
 * 파일 위치: assets/js/income.js
 * 역할: 저장 (적용 버튼)
 */
function saveEntry() {
	var idx = $('#entryIdx').val();
	var type = $('#entryType').val();
	var transactionDate = $('#entryDate').val();
	var bankName = $('#entryBank').val();
	var accountCode = $('#entryAccount').val();
	var accountName = $('#entryAccountName').val();

	// 유효성 검사
	if (!transactionDate) {
		showToast('거래일을 선택해주세요.', 'warning');
		return;
	}

	if (!accountCode) {
		showToast('계정을 선택해주세요.', 'warning');
		return;
	}

	if (entryItemData.length === 0) {
		showToast('최소 1개 이상의 항목을 추가해주세요.', 'warning');
		return;
	}

	// 총 금액 계산
	var totalAmount = entryItemData.reduce(function(sum, item) {
		return sum + (parseInt(item.amount) || 0);
	}, 0);

	var bankJson = bankName ? JSON.stringify({ name: bankName }) : '';

	var params = {
		book_idx: selectedBookIdx,
		org_id: window.incomePageData.orgId,
		income_type: type,
		bank: bankJson,
		account_code: accountCode,
		account_name: accountName,
		transaction_date: transactionDate,
		transaction_cnt: entryItemData.length,
		amount: totalAmount,
		details: JSON.stringify(entryItemData),
		tags: '[]',
		memo: ''
	};

	var url = idx ? 'income/update' : 'income/add';
	if (idx) {
		params.idx = idx;
	}

	$.ajax({
		url: window.incomePageData.baseUrl + url,
		method: 'POST',
		data: params,
		dataType: 'json',
		success: function(response) {
			if (response.success) {
				showToast(response.message, 'success');
				bootstrap.Offcanvas.getInstance(document.getElementById('offcanvasEntry')).hide();
				loadGridData();
				loadTags();
			} else {
				showToast(response.message || '저장에 실패했습니다.', 'error');
			}
		},
		error: function() {
			showToast('저장에 실패했습니다.', 'error');
		}
	});
}

/**
 * 파일 위치: assets/js/income.js
 * 역할: 선택 삭제 - 체크박스 기반
 */
function deleteSelected() {
	var selectedIdxs = [];
	$('.income-checkbox:checked').each(function() {
		selectedIdxs.push($(this).data('idx'));
	});

	if (selectedIdxs.length === 0) {
		showToast('삭제할 항목을 선택해주세요.', 'warning');
		return;
	}

	$('#deleteConfirmMessage').text(selectedIdxs.length + '개 항목을 삭제하시겠습니까?');
	var modal = new bootstrap.Modal(document.getElementById('deleteConfirmModal'));
	modal.show();
}

/**
 * 파일 위치: assets/js/income.js
 * 역할: 삭제 확인 - 체크박스 기반
 */
function confirmDelete() {
	var idxList = [];
	$('.income-checkbox:checked').each(function() {
		idxList.push($(this).data('idx'));
	});

	$.ajax({
		url: window.incomePageData.baseUrl + 'income/delete',
		method: 'POST',
		data: { idx_list: idxList },
		dataType: 'json',
		success: function(response) {
			bootstrap.Modal.getInstance(document.getElementById('deleteConfirmModal')).hide();
			if (response.success) {
				showToast(response.message, 'success');
				loadGridData();
			} else {
				showToast(response.message || '삭제에 실패했습니다.', 'error');
			}
		},
		error: function() {
			bootstrap.Modal.getInstance(document.getElementById('deleteConfirmModal')).hide();
			showToast('삭제에 실패했습니다.', 'error');
		}
	});
}

/**
 * 숫자 포맷
 */
function formatNumber(num) {
	if (!num) return '0';
	return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
}

