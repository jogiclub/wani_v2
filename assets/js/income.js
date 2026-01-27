/**
 * 파일 위치: assets/js/income.js
 * 역할: 수입지출 관리 화면의 클라이언트 로직
 */

var selectedBookIdx = null;
var incomeGrid = null;
var selectedBanks = [];
var selectedAccounts = [];
var selectedTags = [];
var allBanks = [];
var allIncomeAccounts = [];
var allExpenseAccounts = [];
var allUsedTags = [];

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
 * 이벤트 바인딩
 */
function bindEvents() {
	// 장부 선택
	$('#selectBook').on('change', function() {
		selectedBookIdx = $(this).val();
		if (selectedBookIdx) {
			$('#searchArea').show();
			loadBanks();
			loadAccounts();
			loadTags();
			loadGridData();
		} else {
			$('#searchArea').hide();
			if (incomeGrid) {
				incomeGrid.pqGrid('option', 'dataModel.data', []);
				incomeGrid.pqGrid('refreshDataAndView');
			}
		}
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

	// 저장 버튼
	$('#entryForm').on('submit', function(e) {
		e.preventDefault();
		saveEntry();
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
 * pqGrid 초기화
 */
function initGrid() {
	var colModel = [
		{
			title: '',
			dataIndx: 'cb',
			width: 30,
			type: 'checkBoxSelection',
			cls: 'pq-text-center',
			sortable: false,
			cb: { all: true }
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
		{ title: '건수', dataIndx: 'transaction_cnt', width: 50, align: 'right' },
		{
			title: '금액',
			dataIndx: 'amount',
			width: 120,
			align: 'right',
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

	incomeGrid = $('#incomeGrid').pqGrid({
		width: '100%',
		height: 500,
		colModel: colModel,
		dataModel: { data: [] },
		selectionModel: { type: 'row', mode: 'range' },
		scrollModel: { autoFit: true },
		numberCell: { show: true },
		stripeRows: true,
		wrap: false,
		hwrap: false,
		resizable: true,
		columnBorders: true,
		rowBorders: true,
		freezeCols: 2,
		rowDblClick: function(event, ui) {
			var rowData = ui.rowData;
			openEntryOffcanvas(rowData.income_type, rowData);
		}
	});
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
 * 장부 목록 렌더링
 */
function renderBookList(books) {
	var $select = $('#selectBook');
	$select.find('option:not(:first)').remove();

	if (books && books.length > 0) {
		books.forEach(function(book) {
			$select.append('<option value="' + book.book_idx + '">' + book.book_name + '</option>');
		});
	}
}

/**
 * 계좌 목록 로드
 */
function loadBanks() {
	$.ajax({
		url: window.incomePageData.baseUrl + 'income/get_banks',
		method: 'POST',
		data: { book_idx: selectedBookIdx },
		dataType: 'json',
		success: function(response) {
			if (response.success) {
				allBanks = response.data || [];
				renderBankDropdown();
				renderEntryBankSelect();
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

	allBanks.forEach(function(bank, idx) {
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
	// 수입 계정 로드
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

	// 지출 계정 로드
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

	// 수입 계정
	if (allIncomeAccounts.length > 0) {
		$menu.append('<li class="dropdown-header text-primary">수입 계정</li>');
		allIncomeAccounts.forEach(function(account) {
			var html = '<li class="dropdown-item">' +
				'<label><input type="checkbox" class="form-check-input me-2 account-check" value="' + account.code + '"> ' + account.display + '</label>' +
				'</li>';
			$menu.append(html);
		});
	}

	// 지출 계정
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
 * 그리드 데이터 로드
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
				incomeGrid.pqGrid('option', 'dataModel.data', response.data);
				incomeGrid.pqGrid('refreshDataAndView');
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
 * 입력 Offcanvas 열기
 */
function openEntryOffcanvas(type, data) {
	var title = type === 'income' ? '수입 입력' : '지출 입력';
	$('#offcanvasEntryLabel').text(data ? (type === 'income' ? '수입 수정' : '지출 수정') : title);
	$('#entryType').val(type);

	// 폼 초기화
	$('#entryForm')[0].reset();
	$('#entryIdx').val('');
	$('#entryDate').val(moment().format('YYYY-MM-DD'));
	$('#entryAmount').val('');

	// 계정 셀렉트 로드
	loadEntryAccountSelect(type);

	// 수정 모드인 경우 데이터 로드
	if (data) {
		$('#entryIdx').val(data.idx);
		$('#entryDate').val(data.transaction_date_fmt);
		$('#entryCnt').val(data.transaction_cnt);
		$('#entryAmount').val(formatNumber(data.amount));
		$('#entryMemo').val(data.memo);

		if (data.tags) {
			try {
				var tags = JSON.parse(data.tags);
				$('#entryTags').val(Array.isArray(tags) ? tags.join(', ') : data.tags);
			} catch(e) {
				$('#entryTags').val(data.tags);
			}
		}

		// 계좌, 계정 선택은 셀렉트 로드 후 설정
		setTimeout(function() {
			if (data.bank) {
				try {
					var bankData = JSON.parse(data.bank);
					$('#entryBank').val(bankData.name);
				} catch(e) {
					$('#entryBank').val(data.bank);
				}
			}
			$('#entryAccount').val(data.account_code);
		}, 300);
	}

	var offcanvas = new bootstrap.Offcanvas(document.getElementById('offcanvasEntry'));
	offcanvas.show();
}

/**
 * 입력 폼 계좌 셀렉트 렌더링
 */
function renderEntryBankSelect() {
	var $select = $('#entryBank');
	$select.find('option:not(:first)').remove();

	allBanks.forEach(function(bank) {
		$select.append('<option value="' + bank.name + '">' + bank.name + '</option>');
	});
}

/**
 * 입력 폼 계정 셀렉트 로드
 */
function loadEntryAccountSelect(type) {
	var accounts = type === 'income' ? allIncomeAccounts : allExpenseAccounts;
	var $select = $('#entryAccount');
	$select.find('option:not(:first)').remove();

	accounts.forEach(function(account) {
		$select.append('<option value="' + account.code + '" data-name="' + account.name + '">' + account.display + '</option>');
	});
}

/**
 * 저장
 */
function saveEntry() {
	var idx = $('#entryIdx').val();
	var type = $('#entryType').val();
	var accountCode = $('#entryAccount').val();
	var accountName = $('#entryAccount option:selected').data('name') || '';

	var tags = $('#entryTags').val();
	var tagsArray = tags ? tags.split(',').map(function(t) { return t.trim(); }).filter(function(t) { return t; }) : [];

	var bankName = $('#entryBank').val();
	var bankJson = bankName ? JSON.stringify({ name: bankName }) : '';

	var params = {
		book_idx: selectedBookIdx,
		org_id: window.incomePageData.orgId,
		income_type: type,
		bank: bankJson,
		account_code: accountCode,
		account_name: accountName,
		transaction_date: $('#entryDate').val(),
		transaction_cnt: $('#entryCnt').val() || 1,
		amount: $('#entryAmount').val().replace(/[^\d]/g, ''),
		tags: JSON.stringify(tagsArray),
		memo: $('#entryMemo').val()
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
 * 선택 삭제
 */
function deleteSelected() {
	var selectedRows = incomeGrid.pqGrid('selection', { type: 'row', method: 'getSelection' });

	if (!selectedRows || selectedRows.length === 0) {
		showToast('삭제할 항목을 선택해주세요.', 'warning');
		return;
	}

	$('#deleteConfirmMessage').text(selectedRows.length + '개 항목을 삭제하시겠습니까?');
	var modal = new bootstrap.Modal(document.getElementById('deleteConfirmModal'));
	modal.show();
}

/**
 * 삭제 확인
 */
function confirmDelete() {
	var selectedRows = incomeGrid.pqGrid('selection', { type: 'row', method: 'getSelection' });
	var idxList = selectedRows.map(function(row) { return row.rowData.idx; });

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

/**
 * Toast 메시지 표시
 */
function showToast(message, type) {
	if (typeof window.showToast === 'function') {
		window.showToast(message, type);
	} else {
		alert(message);
	}
}
