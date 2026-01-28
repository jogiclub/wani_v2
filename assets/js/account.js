/**
 * 파일 위치: assets/js/account.js
 * 역할: 현금출납 계정관리 화면의 클라이언트 로직 (JSON 기반)
 */

var selectedBookIdx = null;
var selectedBookData = null;
var selectedAccountData = null;
var currentAccountType = 'income';
var incomeTreeInstance = null;
var expenseTreeInstance = null;
var allAccountsFlat = [];
var bankAccountSortable = null;
var deleteBankAccountIdx = null;
var STORAGE_KEY_BOOK = 'account_selected_book_idx';



$(document).ready(function() {
	initPage();
});

/**
 * 페이지 초기화
 */
function initPage() {
	loadBookList();
	bindEvents();
	initBankAccountModule();
}

/**
 * 이벤트 바인딩
 */
function bindEvents() {
	$('#btnAddBook').on('click', function() {
		openBookModal();
	});

	$('#btnSaveBook').on('click', function() {
		saveBook();
	});

	$('#accountTabs button[data-bs-toggle="tab"]').on('shown.bs.tab', function(e) {
		currentAccountType = $(e.target).attr('id') === 'income-tab' ? 'income' : 'expense';
		clearAccountSelection();
	});

	$('#btnAddSubAccount').on('click', function() {
		openAccountModal();
	});

	$('#btnSaveAccount').on('click', function() {
		saveAccount();
	});

	$('#btnRenameAccount').on('click', function() {
		openRenameModal();
	});

	$('#btnConfirmRename').on('click', function() {
		renameAccount();
	});

	$('#btnDeleteAccount').on('click', function() {
		confirmDeleteAccount();
	});

	$('#btnMoveAccount').on('click', function() {
		openMoveAccountModal();
	});

	$('#btnConfirmMove').on('click', function() {
		moveAccount();
	});

	$(document).on('click', function() {
		$('.context-menu').remove();
	});
}

/**
 * 장부 목록 로드
 */
function loadBookList() {
	$.ajax({
		url: window.accountPageData.baseUrl + 'account/get_book_list',
		method: 'POST',
		data: { org_id: window.accountPageData.orgId },
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
	var $bookList = $('#bookList');
	$bookList.empty();

	if (!books || books.length === 0) {
		$('#noBookMessage').show();
		$('#noAccountMessage').show();
		$('#accountTabsContent').hide();
		return;
	}

	$('#noBookMessage').hide();

	books.forEach(function(book) {
		var fiscalText = book.fiscal_base_month == 1 ? '1월 기준' : '12월 기준';
		var $item = $('<button class="list-group-item list-group-item-action book-list-item" type="button" data-book-idx="' + book.book_idx + '">' +
			'<div class="book-name fs-bold">' + escapeHtml(book.book_name) + '</div>' +
			'<small class="book-info">' + fiscalText + '</small>' +
			'</button>');

		$item.on('click', function() {
			selectBook(book);
		});

		$item.on('contextmenu', function(e) {
			e.preventDefault();
			showBookContextMenu(e, book);
		});

		$bookList.append($item);
	});

	// 로컬스토리지에서 저장된 장부 복원
	var savedBookIdx = localStorage.getItem(STORAGE_KEY_BOOK);
	var bookToSelect = null;

	if (savedBookIdx) {
		// 저장된 장부가 현재 목록에 있는지 확인
		bookToSelect = books.find(function(b) { return b.book_idx == savedBookIdx; });
	}

	// 저장된 장부가 없거나 목록에 없으면 첫 번째 장부 선택
	if (!bookToSelect && books.length > 0) {
		bookToSelect = books[0];
	}

	if (bookToSelect) {
		selectBook(bookToSelect);
	}
}

/**
 * 장부 선택
 */
function selectBook(book) {
	selectedBookIdx = book.book_idx;
	selectedBookData = book;

	// 로컬스토리지에 선택된 장부 저장
	localStorage.setItem(STORAGE_KEY_BOOK, book.book_idx);

	$('.book-list-item').removeClass('active');
	$('.book-list-item[data-book-idx="' + book.book_idx + '"]').addClass('active');

	// 기존 트리 인스턴스 초기화
	destroyAllTrees();

	// 트리 로드
	loadAccountTree('income');
	loadAccountTree('expense');

	$('#noAccountMessage').hide();
	$('#accountTabsContent').show();

	clearAccountSelection();

	// 장부 선택 시 계좌 목록 로드
	loadBankAccounts();
}

/**
 * 모든 트리 인스턴스 파괴
 */
function destroyAllTrees() {
	if (incomeTreeInstance) {
		try {
			$('#incomeTree').fancytree('destroy');
		} catch (e) {}
		incomeTreeInstance = null;
	}
	if (expenseTreeInstance) {
		try {
			$('#expenseTree').fancytree('destroy');
		} catch (e) {}
		expenseTreeInstance = null;
	}
	// DOM 초기화
	$('#incomeTree').empty().removeClass('fancytree-container ui-fancytree');
	$('#expenseTree').empty().removeClass('fancytree-container ui-fancytree');
}

/**
 * 계정 트리 로드
 */
function loadAccountTree(accountType) {
	var treeId = accountType === 'income' ? '#incomeTree' : '#expenseTree';

	$.ajax({
		url: window.accountPageData.baseUrl + 'account/get_account_tree',
		method: 'POST',
		data: {
			book_idx: selectedBookIdx,
			account_type: accountType
		},
		dataType: 'json',
		success: function(response) {
			initFancytree(treeId, response, accountType);
		},
		error: function() {
			showToast('계정 목록을 불러오는데 실패했습니다.', 'error');
		}
	});
}

/**
 * 현재 탭의 트리만 새로고침
 */
function refreshCurrentTree() {
	var treeId = currentAccountType === 'income' ? '#incomeTree' : '#expenseTree';
	var treeInstance = currentAccountType === 'income' ? incomeTreeInstance : expenseTreeInstance;

	$.ajax({
		url: window.accountPageData.baseUrl + 'account/get_account_tree',
		method: 'POST',
		data: {
			book_idx: selectedBookIdx,
			account_type: currentAccountType
		},
		dataType: 'json',
		success: function(response) {
			if (treeInstance) {
				// 기존 트리가 있으면 reload 사용
				treeInstance.reload(response).done(function() {
					treeInstance.expandAll();
				});
			} else {
				// 트리가 없으면 새로 초기화
				initFancytree(treeId, response, currentAccountType);
			}
		},
		error: function() {
			showToast('계정 목록을 불러오는데 실패했습니다.', 'error');
		}
	});
}

/**
 * Fancytree 초기화
 */
function initFancytree(treeId, treeData, accountType) {
	// 이미 초기화된 경우 먼저 파괴
	var $tree = $(treeId);
	if ($tree.hasClass('fancytree-container') || $tree.hasClass('ui-fancytree')) {
		try {
			$tree.fancytree('destroy');
		} catch (e) {}
		$tree.empty().removeClass('fancytree-container ui-fancytree');
	}

	$tree.fancytree({
		source: treeData,
		extensions: ['wide'],
		activate: function(event, data) {
			var node = data.node;
			selectedAccountData = {
				id: node.data.id,
				code: node.data.code,
				name: node.data.name,
				level: node.data.level,
				level_name: node.data.level_name
			};
			currentAccountType = accountType;
			updateAccountSelection();
		},
		init: function(event, data) {
			data.tree.expandAll();
			// 트리 인스턴스 저장
			if (accountType === 'income') {
				incomeTreeInstance = data.tree;
			} else {
				expenseTreeInstance = data.tree;
			}
		}
	});
}

/**
 * 계정 선택 상태 업데이트
 */
function updateAccountSelection() {
	if (!selectedAccountData) {
		clearAccountSelection();
		return;
	}

	var levelNames = { 1: '관', 2: '항', 3: '목', 4: '세목', 5: '세세목' };
	var level = selectedAccountData.level || 1;
	var levelName = selectedAccountData.level_name || levelNames[level] || '';

	$('#selectedAccountName').text(selectedAccountData.name);
	$('#selectedAccountLevel').text(levelName + ' (' + selectedAccountData.code + ')');
	$('#selectedAccountInfo').show();

	$('#btnAddSubAccount').prop('disabled', level >= 5);
	$('#btnRenameAccount').prop('disabled', false);
	$('#btnDeleteAccount').prop('disabled', false);
	$('#btnMoveAccount').prop('disabled', false);
}

/**
 * 계정 선택 초기화
 */
function clearAccountSelection() {
	selectedAccountData = null;
	$('#selectedAccountInfo').hide();
	$('#btnAddSubAccount').prop('disabled', true);
	$('#btnRenameAccount').prop('disabled', true);
	$('#btnDeleteAccount').prop('disabled', true);
	$('#btnMoveAccount').prop('disabled', true);
}

/**
 * 장부 모달 열기
 */
function openBookModal(book) {
	$('#bookForm')[0].reset();
	$('#edit_book_idx').val('');

	if (book) {
		$('#bookModalLabel').text('장부 수정');
		$('#edit_book_idx').val(book.book_idx);
		$('#book_name').val(book.book_name);
		$('#fiscal_base_month').val(book.fiscal_base_month);
	} else {
		$('#bookModalLabel').text('장부 추가');
	}

	$('#bookModal').modal('show');
}

/**
 * 장부 저장
 */
function saveBook() {
	var bookIdx = $('#edit_book_idx').val();
	var bookName = $('#book_name').val().trim();
	var fiscalBaseMonth = $('#fiscal_base_month').val();

	if (!bookName) {
		showToast('장부명을 입력해주세요.', 'warning');
		return;
	}

	var url = bookIdx ? 'account/update_book' : 'account/add_book';
	var data = {
		org_id: window.accountPageData.orgId,
		book_name: bookName,
		fiscal_base_month: fiscalBaseMonth
	};

	if (bookIdx) {
		data.book_idx = bookIdx;
	}

	$.ajax({
		url: window.accountPageData.baseUrl + url,
		method: 'POST',
		data: data,
		dataType: 'json',
		success: function(response) {
			if (response.success) {
				showToast(response.message, 'success');
				$('#bookModal').modal('hide');

				if (response.book_idx) {
					selectedBookIdx = response.book_idx;
				}

				loadBookList();
			} else {
				showToast(response.message, 'error');
			}
		},
		error: function() {
			showToast('장부 저장에 실패했습니다.', 'error');
		}
	});
}

/**
 * 장부 컨텍스트 메뉴
 */
function showBookContextMenu(e, book) {
	$('.context-menu').remove();

	var $menu = $('<div class="context-menu dropdown-menu show">' +
		'<a class="dropdown-item" href="#" data-action="edit"><i class="bi bi-pencil me-2"></i>수정</a>' +
		'<a class="dropdown-item text-danger" href="#" data-action="delete"><i class="bi bi-trash me-2"></i>삭제</a>' +
		'</div>');

	$menu.css({
		left: e.pageX,
		top: e.pageY
	});

	$menu.find('[data-action="edit"]').on('click', function(ev) {
		ev.preventDefault();
		ev.stopPropagation();
		$menu.remove();
		openBookModal(book);
	});

	$menu.find('[data-action="delete"]').on('click', function(ev) {
		ev.preventDefault();
		ev.stopPropagation();
		$menu.remove();
		confirmDeleteBook(book);
	});

	$('body').append($menu);
}

/**
 * 장부 삭제 확인
 */
function confirmDeleteBook(book) {
	$('#deleteConfirmMessage').text('"' + book.book_name + '" 장부를 삭제하시겠습니까? 장부에 포함된 모든 계정과목이 함께 삭제됩니다.');
	$('#btnConfirmDelete').off('click').on('click', function() {
		deleteBook(book.book_idx);
	});
	$('#deleteConfirmModal').modal('show');
}

/**
 * 장부 삭제
 */
function deleteBook(bookIdx) {
	$.ajax({
		url: window.accountPageData.baseUrl + 'account/delete_book',
		method: 'POST',
		data: { book_idx: bookIdx },
		dataType: 'json',
		success: function(response) {
			$('#deleteConfirmModal').modal('hide');
			if (response.success) {
				showToast(response.message, 'success');
				if (selectedBookIdx == bookIdx) {
					selectedBookIdx = null;
					selectedBookData = null;
				}
				loadBookList();
			} else {
				showToast(response.message, 'error');
			}
		},
		error: function() {
			$('#deleteConfirmModal').modal('hide');
			showToast('장부 삭제에 실패했습니다.', 'error');
		}
	});
}

/**
 * 계정 추가 모달 열기
 */
function openAccountModal() {
	if (!selectedAccountData) {
		showToast('상위 계정을 선택해주세요.', 'warning');
		return;
	}

	if (selectedAccountData.level >= 5) {
		showToast('더 이상 하위 계정을 생성할 수 없습니다.', 'warning');
		return;
	}

	$('#accountForm')[0].reset();
	$('#account_parent_id').val(selectedAccountData.id);
	$('#account_type').val(currentAccountType);
	$('#parent_account_name').val(selectedAccountData.code + '. ' + selectedAccountData.name);

	var levelNames = { 1: '항', 2: '목', 3: '세목', 4: '세세목' };
	var nextLevel = levelNames[selectedAccountData.level] || '하위계정';
	$('#accountModalLabel').text(nextLevel + ' 추가');

	$('#accountModal').modal('show');
}

/**
 * 계정 저장
 */
function saveAccount() {
	var parentId = $('#account_parent_id').val();
	var accountType = $('#account_type').val();
	var accountName = $('#new_account_name').val().trim();

	if (!accountName) {
		showToast('계정명을 입력해주세요.', 'warning');
		return;
	}

	$.ajax({
		url: window.accountPageData.baseUrl + 'account/add_account',
		method: 'POST',
		data: {
			book_idx: selectedBookIdx,
			parent_id: parentId,
			account_type: accountType,
			account_name: accountName
		},
		dataType: 'json',
		success: function(response) {
			if (response.success) {
				showToast(response.message, 'success');
				$('#accountModal').modal('hide');
				clearAccountSelection();
				refreshCurrentTree();
			} else {
				showToast(response.message, 'error');
			}
		},
		error: function() {
			showToast('계정 추가에 실패했습니다.', 'error');
		}
	});
}

/**
 * 계정명 변경 모달 열기
 */
function openRenameModal() {
	if (!selectedAccountData) {
		showToast('계정을 선택해주세요.', 'warning');
		return;
	}

	$('#rename_account_id').val(selectedAccountData.id);
	$('#rename_account_name').val(selectedAccountData.name);
	$('#renameModal').modal('show');
}

/**
 * 계정명 변경
 */
function renameAccount() {
	var accountId = $('#rename_account_id').val();
	var accountName = $('#rename_account_name').val().trim();

	if (!accountName) {
		showToast('계정명을 입력해주세요.', 'warning');
		return;
	}

	$.ajax({
		url: window.accountPageData.baseUrl + 'account/update_account_name',
		method: 'POST',
		data: {
			book_idx: selectedBookIdx,
			account_id: accountId,
			account_name: accountName,
			account_type: currentAccountType
		},
		dataType: 'json',
		success: function(response) {
			if (response.success) {
				showToast(response.message, 'success');
				$('#renameModal').modal('hide');
				clearAccountSelection();
				refreshCurrentTree();
			} else {
				showToast(response.message, 'error');
			}
		},
		error: function() {
			showToast('계정명 변경에 실패했습니다.', 'error');
		}
	});
}

/**
 * 계정 삭제 확인
 */
function confirmDeleteAccount() {
	if (!selectedAccountData) {
		showToast('계정을 선택해주세요.', 'warning');
		return;
	}

	$('#deleteConfirmMessage').text('"' + selectedAccountData.name + '" 계정을 삭제하시겠습니까?');
	$('#btnConfirmDelete').off('click').on('click', function() {
		deleteAccount();
	});
	$('#deleteConfirmModal').modal('show');
}

/**
 * 계정 삭제
 */
function deleteAccount() {
	$.ajax({
		url: window.accountPageData.baseUrl + 'account/delete_account',
		method: 'POST',
		data: {
			book_idx: selectedBookIdx,
			account_id: selectedAccountData.id,
			account_type: currentAccountType
		},
		dataType: 'json',
		success: function(response) {
			$('#deleteConfirmModal').modal('hide');
			if (response.success) {
				showToast(response.message, 'success');
				clearAccountSelection();
				refreshCurrentTree();
			} else {
				showToast(response.message, 'error');
			}
		},
		error: function() {
			$('#deleteConfirmModal').modal('hide');
			showToast('계정 삭제에 실패했습니다.', 'error');
		}
	});
}

/**
 * 계정 이동 모달 열기
 */
function openMoveAccountModal() {
	if (!selectedAccountData) {
		showToast('이동할 계정을 선택해주세요.', 'warning');
		return;
	}

	$('#moveAccountMessage').text("'" + selectedAccountData.name + "' 계정을 다른 위치로 이동하시겠습니까?");

	// 이동 가능한 계정 목록 로드
	loadMoveTargetAccounts();

	$('#moveAccountModal').modal('show');
}

/**
 * 이동 대상 계정 목록 로드
 */
function loadMoveTargetAccounts() {
	var $select = $('#moveToAccountId');
	$select.empty();
	$select.append('<option value="">최상위로 이동 (관)</option>');

	// 현재 트리 인스턴스 사용
	var tree = currentAccountType === 'income' ? incomeTreeInstance : expenseTreeInstance;

	if (!tree) {
		return;
	}

	var currentId = selectedAccountData.id;

	// 트리를 순회하며 이동 가능한 계정 목록 생성
	tree.visit(function(node) {
		var nodeData = node.data;

		// 자기 자신은 제외
		if (nodeData.id == currentId) {
			return 'skip'; // 하위 노드도 건너뛰기
		}

		// 레벨 4 이하만 상위 계정이 될 수 있음 (하위 계정 생성 시 레벨 5까지 가능)
		if (nodeData.level >= 5) {
			return;
		}

		// 들여쓰기로 계층 표시
		var indent = '';
		for (var i = 1; i < nodeData.level; i++) {
			indent += '\u00A0\u00A0';
		}

		var levelNames = { 1: '관', 2: '항', 3: '목', 4: '세목' };
		var levelName = levelNames[nodeData.level] || '';

		$select.append('<option value="' + nodeData.id + '">' + indent + nodeData.code + '. ' + nodeData.name + ' [' + levelName + ']</option>');
	});
}

/**
 * 계정 이동
 */
function moveAccount() {
	var newParentId = $('#moveToAccountId').val();

	// 자기 자신의 하위로 이동 방지 (서버에서도 체크하지만 클라이언트에서도)
	if (newParentId == selectedAccountData.id) {
		showToast('자기 자신의 하위로 이동할 수 없습니다.', 'warning');
		return;
	}

	$.ajax({
		url: window.accountPageData.baseUrl + 'account/move_account',
		method: 'POST',
		data: {
			book_idx: selectedBookIdx,
			account_id: selectedAccountData.id,
			new_parent_id: newParentId,
			account_type: currentAccountType
		},
		dataType: 'json',
		success: function(response) {
			$('#moveAccountModal').modal('hide');
			if (response.success) {
				showToast(response.message, 'success');
				clearAccountSelection();
				refreshCurrentTree();
			} else {
				showToast(response.message, 'error');
			}
		},
		error: function() {
			$('#moveAccountModal').modal('hide');
			showToast('계정 이동에 실패했습니다.', 'error');
		}
	});
}




function initBankAccountModule() {
	// 계좌 추가 버튼 - 모달 없이 바로 추가
	$('#btnAddBankAccount').on('click', function() {
		if (!selectedBookIdx) {
			showToast('장부를 먼저 선택해주세요.', 'warning');
			return;
		}
		addDefaultBankAccount();
	});

	// 계좌 저장 버튼
	$('#btnSaveBankAccount').on('click', function() {
		saveBankAccount();
	});

	// 계좌 삭제 확인 버튼
	$('#btnConfirmDeleteBankAccount').on('click', function() {
		confirmDeleteBankAccount();
	});

	// 모달 닫힐 때 폼 초기화
	$('#bankAccountModal').on('hidden.bs.modal', function() {
		$('#bankAccountForm')[0].reset();
		$('#bank_account_idx').val('');
	});
}

// ========== 기본 계좌 바로 추가 (새 함수) ==========
function addDefaultBankAccount() {
	$.ajax({
		url: window.accountPageData.baseUrl + 'account/add_bank_account',
		type: 'POST',
		dataType: 'json',
		data: {
			book_idx: selectedBookIdx,
			bank_name: '왔니은행',
			account_number: '000-00-0000000',
			account_desc: ''
		},
		success: function(response) {
			if (response.success) {
				showToast('계좌가 추가되었습니다.', 'success');
				loadBankAccounts();
			} else {
				showToast(response.message || '추가 실패', 'error');
			}
		},
		error: function() {
			showToast('서버 통신 오류', 'error');
		}
	});
}

// ========== 계좌 목록 조회 ==========
function loadBankAccounts() {
	if (!selectedBookIdx) {
		$('#bankAccountBody').empty();
		$('#noBankAccountMessage').show();
		$('#bankAccountList').hide();
		$('#btnAddBankAccount').prop('disabled', true);
		return;
	}

	$.ajax({
		url: window.accountPageData.baseUrl + 'account/get_bank_accounts',
		type: 'POST',
		dataType: 'json',
		data: { book_idx: selectedBookIdx },
		success: function(response) {
			if (response.success) {
				renderBankAccounts(response.data);
			} else {
				showToast('계좌 목록 조회 실패', 'error');
			}
		},
		error: function() {
			showToast('서버 통신 오류', 'error');
		}
	});
}

// ========== 계좌 목록 렌더링 ==========
function renderBankAccounts(accounts) {
	var $tbody = $('#bankAccountBody');
	$tbody.empty();

	$('#btnAddBankAccount').prop('disabled', false);

	if (!accounts || accounts.length === 0) {
		$('#noBankAccountMessage').html(
			'<i class="bi bi-bank fs-1"></i>' +
			'<p class="mb-0 mt-2">등록된 계좌가 없습니다.</p>'
		).show();
		$('#bankAccountList').hide();
		return;
	}

	$('#noBankAccountMessage').hide();
	$('#bankAccountList').show();

	accounts.forEach(function(account) {
		var $row = $('<tr data-idx="' + account.idx + '">' +
			'<td class="text-center drag-handle" style="cursor: move;"><i class="bi bi-grip-vertical text-muted"></i></td>' +
			'<td>' + escapeHtml(account.bank_name) + '</td>' +
			'<td>' + escapeHtml(account.account_number) + '</td>' +
			'<td>' + escapeHtml(account.account_desc || '') + '</td>' +
			'<td class="text-center">' +
			'<button type="button" class="btn btn-xs btn-outline-secondary btn-edit-bank me-1" title="수정">' +
			'<i class="bi bi-pencil"></i>' +
			'</button>' +
			'<button type="button" class="btn btn-xs btn-outline-danger btn-delete-bank" title="삭제">' +
			'<i class="bi bi-trash"></i>' +
			'</button>' +
			'</td>' +
			'</tr>');

		// 수정 버튼 이벤트
		$row.find('.btn-edit-bank').on('click', function() {
			openBankAccountModal(account);
		});

		// 삭제 버튼 이벤트
		$row.find('.btn-delete-bank').on('click', function() {
			openDeleteBankAccountModal(account);
		});

		$tbody.append($row);
	});

	// Sortable 초기화
	initBankAccountSortable();
}

// ========== Sortable 초기화 ==========
function initBankAccountSortable() {
	var el = document.getElementById('bankAccountBody');
	if (!el) return;

	if (bankAccountSortable) {
		bankAccountSortable.destroy();
	}

	bankAccountSortable = new Sortable(el, {
		animation: 150,
		handle: '.drag-handle',
		ghostClass: 'table-primary',
		onEnd: function() {
			saveBankAccountOrder();
		}
	});
}

// ========== 계좌 순서 저장 ==========
function saveBankAccountOrder() {
	var order = [];
	$('#bankAccountBody tr').each(function() {
		order.push($(this).data('idx'));
	});

	$.ajax({
		url: window.accountPageData.baseUrl + 'account/reorder_bank_accounts',
		type: 'POST',
		dataType: 'json',
		data: {
			book_idx: selectedBookIdx,
			order: order
		},
		success: function(response) {
			if (!response.success) {
				showToast(response.message || '순서 변경 실패', 'error');
				loadBankAccounts();
			}
		},
		error: function() {
			showToast('서버 통신 오류', 'error');
			loadBankAccounts();
		}
	});
}

// ========== 계좌 추가/수정 모달 열기 ==========
function openBankAccountModal(account) {
	if (!account) return;

	// 수정 모드만 지원
	$('#bankAccountModalLabel').text('계좌 수정');
	$('#bank_account_idx').val(account.idx);
	$('#bank_name').val(account.bank_name);
	$('#bank_account_number').val(account.account_number);
	$('#bank_account_desc').val(account.account_desc || '');

	var modal = new bootstrap.Modal(document.getElementById('bankAccountModal'));
	modal.show();
}

// ========== 계좌 저장 ==========
function saveBankAccount() {
	var accountIdx = $('#bank_account_idx').val();
	var bankName = $('#bank_name').val().trim();
	var accountNumber = $('#bank_account_number').val().trim();
	var accountDesc = $('#bank_account_desc').val().trim();

	if (!bankName) {
		showToast('은행명을 입력해주세요.', 'warning');
		$('#bank_name').focus();
		return;
	}

	if (!accountNumber) {
		showToast('계좌번호를 입력해주세요.', 'warning');
		$('#bank_account_number').focus();
		return;
	}

	var url = accountIdx
		? window.accountPageData.baseUrl + 'account/update_bank_account'
		: window.accountPageData.baseUrl + 'account/add_bank_account';

	var data = {
		book_idx: selectedBookIdx,
		bank_name: bankName,
		account_number: accountNumber,
		account_desc: accountDesc
	};

	if (accountIdx) {
		data.account_idx = accountIdx;
	}

	$.ajax({
		url: url,
		type: 'POST',
		dataType: 'json',
		data: data,
		success: function(response) {
			if (response.success) {
				showToast(response.message, 'success');
				bootstrap.Modal.getInstance(document.getElementById('bankAccountModal')).hide();
				loadBankAccounts();
			} else {
				showToast(response.message || '저장 실패', 'error');
			}
		},
		error: function() {
			showToast('서버 통신 오류', 'error');
		}
	});
}

// ========== 계좌 삭제 모달 열기 ==========
function openDeleteBankAccountModal(account) {
	deleteBankAccountIdx = account.idx;
	$('#deleteBankAccountMessage').html(
		'<strong>' + escapeHtml(account.bank_name) + ' (' + escapeHtml(account.account_number) + ')</strong> 계좌를 삭제하시겠습니까?'
	);
	var modal = new bootstrap.Modal(document.getElementById('deleteBankAccountModal'));
	modal.show();
}

// ========== 계좌 삭제 확인 ==========
function confirmDeleteBankAccount() {
	if (!deleteBankAccountIdx) return;

	$.ajax({
		url: window.accountPageData.baseUrl + 'account/delete_bank_account',
		type: 'POST',
		dataType: 'json',
		data: {
			book_idx: selectedBookIdx,
			account_idx: deleteBankAccountIdx
		},
		success: function(response) {
			if (response.success) {
				showToast(response.message, 'success');
				bootstrap.Modal.getInstance(document.getElementById('deleteBankAccountModal')).hide();
				loadBankAccounts();
			} else {
				showToast(response.message || '삭제 실패', 'error');
			}
			deleteBankAccountIdx = null;
		},
		error: function() {
			showToast('서버 통신 오류', 'error');
			deleteBankAccountIdx = null;
		}
	});
}




