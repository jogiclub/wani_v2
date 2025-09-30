/**
 * 파일 위치: assets/js/send_popup.js
 * 역할: 문자 발송 팝업의 충전, 발신번호 관리, 수신자 관리, 전체편집 기능 처리
 */

// ===== 전역 변수 =====
let selectedPackage = null;
let bulkEditGridInstance = null;
let authTimer = null;
let authTimeLeft = 120;

$(document).ready(function() {
	// 초기 잔액 조회
	loadBalance();

	// 문자충전 버튼 클릭
	$('#btnChargeModal').on('click', function() {
		loadChargePackages();
		const offcanvas = new bootstrap.Offcanvas(document.getElementById('chargeOffcanvas'));
		offcanvas.show();
	});

	// 결제하기 버튼 클릭
	$('#btnCharge').on('click', function() {
		if (!selectedPackage) {
			showToast('충전 패키지를 선택해주세요.', 'warning');
			return;
		}
		processCharge();
	});

	// 발신번호 관리 버튼 클릭
	$('#btnAddSender').on('click', function() {
		loadSenderList();
		const offcanvas = new bootstrap.Offcanvas(document.getElementById('senderManageOffcanvas'));
		offcanvas.show();
	});

	// 발신번호 저장 버튼 이벤트
	$(document).on('click', '#btnSaveSender', function() {
		saveSender();
	});

	// 발신번호 추가 모달 버튼 클릭
	$(document).on('click', '#btnAddSenderModal', function() {
		$('#newSenderName').val('');
		$('#newSenderNumber').val('');
		const modal = new bootstrap.Modal(document.getElementById('addSenderModal'));
		modal.show();
	});

	// 주소록 탭 클릭 시 목록 로드
	$('#address-tab').on('shown.bs.tab', function() {
		loadAddressBookList();
	});

	// 전체편집 버튼 클릭
	$('.btn-success').filter(function() {
		return $(this).find('i').hasClass('bi-pencil-square');
	}).on('click', function(e) {
		e.preventDefault();
		const totalCount = $('#receiverList tr.receiver-item').length;
		if (totalCount === 0) {
			showToast('편집할 수신자가 없습니다.', 'warning');
			return;
		}
		openBulkEditModal();
	});

	// 전체편집 저장 버튼
	$(document).on('click', '#btnSaveBulkEdit', function() {
		saveBulkEdit();
	});

	// 수신자 단건 삭제
	$(document).on('click', '.remove-receiver', function(e) {
		e.preventDefault();
		const $row = $(this).closest('tr');
		const memberName = $row.data('name');
		showConfirmModal(
			'수신자 삭제',
			memberName + '님을 목록에서 삭제합니다.',
			function() {
				$row.remove();
				updateReceiverCount();
				updateSendCost();
				if ($('#receiverList tr').length === 0) {
					showEmptyReceiverMessage();
				}
			}
		);
	});

	// 수신자 전체 삭제
	$('.btn-danger').filter(function() {
		return $(this).find('i').hasClass('bi-x-square');
	}).on('click', function(e) {
		e.preventDefault();
		const totalCount = $('#receiverList tr').length;
		if (totalCount === 0) {
			showToast('삭제할 수신자가 없습니다.', 'warning');
			return;
		}
		const firstMember = $('#receiverList tr:first').data('name');
		const otherCount = totalCount - 1;
		const message = otherCount > 0
			? firstMember + ' 외 ' + otherCount + '명의 목록을 삭제합니다.'
			: firstMember + '님을 목록에서 삭제합니다.';
		showConfirmModal(
			'전체 삭제',
			message,
			function() {
				$('#receiverList').empty();
				showEmptyReceiverMessage();
				updateReceiverCount();
				updateSendCost();
			}
		);
	});

	// 내 주소록에 저장
	$('.btn-primary').filter(function() {
		return $(this).find('i').hasClass('bi-bookmark-check');
	}).on('click', function(e) {
		e.preventDefault();
		const totalCount = $('#receiverList tr').length;
		if (totalCount === 0) {
			showToast('저장할 수신자가 없습니다.', 'warning');
			return;
		}
		showAddressBookNameModal();
	});

	// 주소록 저장
	$(document).on('click', '#btnSaveAddressBook', function() {
		saveAddressBook();
	});


});

// ===== 잔액 관련 함수 =====
function loadBalance() {
	$.ajax({
		url: '/send/get_org_balance',
		type: 'POST',
		data: { org_id: SEND_ORG_ID },
		dataType: 'json',
		success: function(response) {
			if (response.success) {
				updateBalanceDisplay(response.balance);
			}
		},
		error: function(xhr, status, error) {
			console.error('잔액 조회 실패:', error);
		}
	});
}

function updateBalanceDisplay(balance) {
	$('#currentBalance').text(formatNumber(balance) + '원');
}

// ===== 충전 관련 함수 =====
function loadChargePackages() {
	$.ajax({
		url: '/send/get_charge_packages',
		type: 'POST',
		dataType: 'json',
		success: function(response) {
			if (response.success) {
				renderPackageList(response.packages);
			}
		},
		error: function(xhr, status, error) {
			showToast('패키지 목록을 불러오는데 실패했습니다.', 'error');
			console.error('패키지 조회 실패:', error);
		}
	});
}

function renderPackageList(packages) {
	const container = $('#packageList');
	container.empty();

	packages.forEach(function(pkg) {
		const item = `
			<a href="#" class="list-group-item list-group-item-action package-item" 
			   data-package-idx="${pkg.package_idx}"
			   data-package-name="${pkg.package_name}"
			   data-amount="${pkg.package_amount}">
				<div class="d-flex w-100 justify-content-between align-items-center">
					<div>
						<h6 class="mb-1">${formatNumber(pkg.package_amount)}원</h6>
						<small class="text-muted">
							SMS ${pkg.sms_price}원 / LMS ${pkg.lms_price}원 / 
							MMS ${pkg.mms_price}원 / 카카오 ${pkg.kakao_price}원
						</small>
					</div>
					<i class="bi bi-chevron-right"></i>
				</div>
			</a>
		`;
		container.append(item);
	});

	$('.package-item').on('click', function(e) {
		e.preventDefault();
		$('.package-item').removeClass('active');
		$(this).addClass('active');
		selectedPackage = {
			package_idx: $(this).data('package-idx'),
			package_name: $(this).data('package-name'),
			amount: $(this).data('amount')
		};
		$('#selectedAmount').text(formatNumber(selectedPackage.amount) + '원');
	});
}

function processCharge() {
	showConfirmModal(
		'문자 충전',
		formatNumber(selectedPackage.amount) + '원을 충전하시겠습니까?',
		function() {
			$.ajax({
				url: '/send/charge_sms',
				type: 'POST',
				data: {
					org_id: SEND_ORG_ID,
					package_idx: selectedPackage.package_idx,
					charge_amount: selectedPackage.amount
				},
				dataType: 'json',
				beforeSend: function() {
					$('#btnCharge').prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> 처리중...');
				},
				success: function(response) {
					if (response.success) {
						showToast(response.message, 'success');
						updateBalanceDisplay(response.balance);
						const offcanvasEl = document.getElementById('chargeOffcanvas');
						const offcanvas = bootstrap.Offcanvas.getInstance(offcanvasEl);
						if (offcanvas) {
							offcanvas.hide();
						}
						setTimeout(function() {
							selectedPackage = null;
							$('#selectedAmount').text('0원');
							$('.package-item').removeClass('active');
						}, 300);
					} else {
						showToast(response.message, 'error');
					}
				},
				error: function(xhr, status, error) {
					showToast('충전 처리 중 오류가 발생했습니다.', 'error');
					console.error('충전 실패:', error);
				},
				complete: function() {
					$('#btnCharge').prop('disabled', false).html('<i class="bi bi-credit-card"></i> 결제하기');
				}
			});
		}
	);
}

// ===== 발신번호 관련 함수 =====
function loadSenderList() {
	$.ajax({
		url: '/send/get_sender_list',
		type: 'POST',
		data: { org_id: SEND_ORG_ID },
		dataType: 'json',
		success: function(response) {
			if (response.success) {
				renderSenderList(response.senders);
			}
		},
		error: function(xhr, status, error) {
			showToast('발신번호 목록을 불러오는데 실패했습니다.', 'error');
			console.error('발신번호 조회 실패:', error);
		}
	});
}

function renderSenderList(senders) {
	const tbody = $('#senderTableBody');
	tbody.empty();

	if (senders.length === 0) {
		tbody.append('<tr><td colspan="4" class="text-center text-muted">등록된 발신번호가 없습니다.</td></tr>');
		return;
	}

	senders.forEach(function(sender) {
		const authButton = sender.auth_status === 'verified'
			? '<span class="badge bg-success">완료</span>'
			: '<button type="button" class="btn btn-xs btn-warning btn-auth-sender" data-sender-idx="' + sender.sender_idx + '" data-sender-number="' + sender.sender_number + '">인증필요</button>';

		const row = `
			<tr>
				<td>${sender.sender_name}</td>
				<td>${sender.sender_number}</td>
				<td>${authButton}</td>
				<td>
					<button type="button" class="btn btn-xs btn-outline-danger btn-delete-sender" data-sender-idx="${sender.sender_idx}">
						<i class="bi bi-trash"></i> 삭제
					</button>
				</td>
			</tr>
		`;
		tbody.append(row);
	});

	$('.btn-auth-sender').on('click', function() {
		const senderIdx = $(this).data('sender-idx');
		const senderNumber = $(this).data('sender-number');
		requestAuthCode(senderIdx, senderNumber);
	});

	$('.btn-delete-sender').on('click', function() {
		const senderIdx = $(this).data('sender-idx');
		deleteSender(senderIdx);
	});
}

function saveSender() {
	const senderName = $('#newSenderName').val().trim();
	const senderNumber = $('#newSenderNumber').val().trim();

	if (!senderName || !senderNumber) {
		showToast('이름과 발신번호를 모두 입력해주세요.', 'warning');
		return;
	}

	$.ajax({
		url: '/send/save_sender',
		type: 'POST',
		data: {
			org_id: SEND_ORG_ID,
			sender_name: senderName,
			sender_number: senderNumber,
			is_default: 'N'
		},
		dataType: 'json',
		beforeSend: function() {
			$('#btnSaveSender').prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> 저장중...');
		},
		success: function(response) {
			if (response.success) {
				showToast(response.message, 'success');
				$('#addSenderModal').modal('hide');
				$('#newSenderName').val('');
				$('#newSenderNumber').val('');
				loadSenderList();
				loadSenderSelectOptions();
			} else {
				showToast(response.message, 'error');
			}
		},
		error: function(xhr, status, error) {
			showToast('발신번호 저장에 실패했습니다.', 'error');
			console.error('발신번호 저장 실패:', error);
		},
		complete: function() {
			$('#btnSaveSender').prop('disabled', false).html('저장');
		}
	});
}

function requestAuthCode(senderIdx, senderNumber) {
	$.ajax({
		url: '/send/send_auth_code',
		type: 'POST',
		data: {
			sender_idx: senderIdx,
			sender_number: senderNumber,
			org_id: SEND_ORG_ID
		},
		dataType: 'json',
		success: function(response) {
			if (response.success) {
				alert('인증번호: ' + response.auth_code);
				showAuthCodeModal(senderIdx, senderNumber);
			} else {
				showToast(response.message, 'error');
			}
		},
		error: function(xhr, status, error) {
			showToast('인증번호 발송에 실패했습니다.', 'error');
			console.error('인증번호 발송 실패:', error);
		}
	});
}

function showAuthCodeModal(senderIdx, senderNumber) {
	let authModal = $('#authCodeModal');

	if (authModal.length === 0) {
		authModal = $(`
			<div class="modal fade" id="authCodeModal" tabindex="-1" aria-hidden="true">
				<div class="modal-dialog modal-sm">
					<div class="modal-content">
						<div class="modal-header">
							<h5 class="modal-title">발신번호 인증</h5>
							<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
						</div>
						<div class="modal-body">
							<p id="authCodeMessage"></p>
							<div class="text-end">
								<span class="badge bg-danger fs-3 mb-3 d-flex justify-content-center" id="authTimer">02:00</span>
							</div>
							<div class="input-group mb-2">
								<input type="text" class="form-control" id="authCodeInput" placeholder="인증번호 6자리" maxlength="6">
								<button class="btn btn-primary" type="button" id="btnVerifyAuth">확인</button>
							</div>
						</div>
					</div>
				</div>
			</div>
		`);
		$('body').append(authModal);

		authModal.on('hidden.bs.modal', function() {
			if (authTimer) {
				clearInterval(authTimer);
				authTimer = null;
			}
		});
	}

	$('#authCodeMessage').html(`입력한 발신번호(${senderNumber})로 6자리 인증번호를 전송하였습니다.<br>2분 안에 인증번호를 입력해주세요!`);
	$('#authCodeInput').val('');

	authTimeLeft = 120;
	$('#authTimer').text('02:00');

	if (authTimer) {
		clearInterval(authTimer);
	}

	authTimer = setInterval(function() {
		authTimeLeft--;
		const minutes = Math.floor(authTimeLeft / 60);
		const seconds = authTimeLeft % 60;
		const timeText = `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
		$('#authTimer').text(timeText);

		if (authTimeLeft <= 0) {
			clearInterval(authTimer);
			authTimer = null;
			$('#authTimer').text('00:00');
			showToast('인증번호 입력 시간이 만료되었습니다.', 'error');
			$('#authCodeModal').modal('hide');
		}
	}, 1000);

	$('#btnVerifyAuth').off('click').on('click', function() {
		const authCode = $('#authCodeInput').val().trim();
		if (!authCode) {
			showToast('인증번호를 입력해주세요.', 'warning');
			return;
		}
		if (authTimeLeft <= 0) {
			showToast('인증번호 입력 시간이 만료되었습니다.', 'error');
			return;
		}
		verifyAuthCode(senderIdx, authCode);
	});

	const modalInstance = new bootstrap.Modal(authModal[0]);
	modalInstance.show();
}

function verifyAuthCode(senderIdx, authCode) {
	$.ajax({
		url: '/send/verify_auth_code',
		type: 'POST',
		data: {
			sender_idx: senderIdx,
			auth_code: authCode,
			org_id: SEND_ORG_ID
		},
		dataType: 'json',
		success: function(response) {
			if (response.success) {
				if (authTimer) {
					clearInterval(authTimer);
					authTimer = null;
				}
				showToast(response.message, 'success');
				$('#authCodeModal').modal('hide');
				loadSenderList();
				loadSenderSelectOptions();
			} else {
				showToast(response.message, 'error');
			}
		},
		error: function(xhr, status, error) {
			showToast('인증 확인에 실패했습니다.', 'error');
			console.error('인증 실패:', error);
		}
	});
}

function deleteSender(senderIdx) {
	showConfirmModal(
		'발신번호 삭제',
		'발신번호를 삭제하시겠습니까?',
		function() {
			$.ajax({
				url: '/send/delete_sender',
				type: 'POST',
				data: {
					sender_idx: senderIdx,
					org_id: SEND_ORG_ID
				},
				dataType: 'json',
				success: function(response) {
					if (response.success) {
						showToast(response.message, 'success');
						loadSenderList();
						loadSenderSelectOptions();
					} else {
						showToast(response.message, 'error');
					}
				},
				error: function(xhr, status, error) {
					showToast('발신번호 삭제에 실패했습니다.', 'error');
					console.error('발신번호 삭제 실패:', error);
				}
			});
		}
	);
}

function loadSenderSelectOptions() {
	$.ajax({
		url: '/send/get_sender_list',
		type: 'POST',
		data: { org_id: SEND_ORG_ID },
		dataType: 'json',
		success: function(response) {
			if (response.success) {
				const select = $('#senderSelect');
				const currentValue = select.val();
				const verifiedSenders = response.senders.filter(function(sender) {
					return sender.auth_status === 'verified';
				});

				select.empty();

				if (verifiedSenders.length === 0) {
					select.append('<option value="">인증된 발신번호가 없습니다. 발신번호 추가를 진행해주세요!</option>');
					select.prop('disabled', true);
				} else {
					select.prop('disabled', false);
					select.append('<option value="">발신번호를 선택하세요</option>');
					verifiedSenders.forEach(function(sender) {
						const option = `<option value="${sender.sender_number}" 
							data-name="${sender.sender_name}"
							${sender.is_default === 'Y' ? 'selected' : ''}>
							${sender.sender_name} (${sender.sender_number})
						</option>`;
						select.append(option);
					});
					if (currentValue) {
						select.val(currentValue);
					}
				}
			}
		},
		error: function(xhr, status, error) {
			console.error('발신번호 조회 실패:', error);
		}
	});
}

// ===== 수신자 관련 함수 =====
function showEmptyReceiverMessage() {
	const emptyMessage = `
		<tr>
			<td colspan="7" class="text-center text-muted">선택된 회원이 없습니다.</td>
		</tr>
	`;
	$('#receiverList').html(emptyMessage);
}

function updateReceiverCount() {
	const count = $('#receiverList tr.receiver-item').length;
	const $countElement = $('.col-12.mb-2.d-flex strong');
	$countElement.text('선택된 회원 (' + count + '명)');
}

function updateSendCost() {
	const count = $('#receiverList tr.receiver-item').length;
	const sendType = $('input[name="send_type"]:checked').val();

	let costPerMessage = 10;
	if (sendType === 'lms') costPerMessage = 20;
	else if (sendType === 'mms') costPerMessage = 30;
	else if (sendType === 'kakao') costPerMessage = 20;

	const totalCost = count * costPerMessage;

	$('#costTotal').text(formatNumber(totalCost) + '원');
	$('#costTotal').next('small').text('(' + count + '명 × ' + costPerMessage + '원)');
}

// ===== 주소록 관련 함수 =====
function showAddressBookNameModal() {
	let nameModal = $('#addressBookNameModal');

	if (nameModal.length === 0) {
		nameModal = $(`
			<div class="modal fade" id="addressBookNameModal" tabindex="-1" aria-hidden="true">
				<div class="modal-dialog modal-sm">
					<div class="modal-content">
						<div class="modal-header">
							<h5 class="modal-title">주소록 저장</h5>
							<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
						</div>
						<div class="modal-body">
							<p>저장할 주소록명을 입력해주세요!</p>
							<input type="text" class="form-control" id="addressBookNameInput" placeholder="주소록명 입력" maxlength="50">
						</div>
						<div class="modal-footer">
							<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
							<button type="button" class="btn btn-primary" id="btnSaveAddressBook">저장</button>
						</div>
					</div>
				</div>
			</div>
		`);
		$('body').append(nameModal);
	}

	$('#addressBookNameInput').val('');
	const modalInstance = new bootstrap.Modal(nameModal[0]);
	modalInstance.show();
}

function saveAddressBook() {
	const addressBookName = $('#addressBookNameInput').val().trim();

	if (!addressBookName) {
		showToast('주소록명을 입력해주세요.', 'warning');
		return;
	}

	const memberList = [];
	$('#receiverList tr.receiver-item').each(function() {
		const $row = $(this);
		memberList.push({
			member_idx: $row.data('member-idx'),
			member_name: $row.data('name'),
			member_phone: $row.data('phone')
		});
	});

	if (memberList.length === 0) {
		showToast('저장할 수신자가 없습니다.', 'warning');
		return;
	}

	$.ajax({
		url: '/send/save_address_book',
		type: 'POST',
		data: {
			org_id: SEND_ORG_ID,
			address_book_name: addressBookName,
			member_list: memberList
		},
		dataType: 'json',
		beforeSend: function() {
			$('#btnSaveAddressBook').prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> 저장중...');
		},
		success: function(response) {
			if (response.success) {
				showToast(response.message, 'success');
				$('#addressBookNameModal').modal('hide');
				$('#address-tab').tab('show');
				loadAddressBookList();
			} else {
				showToast(response.message, 'error');
			}
		},
		error: function(xhr, status, error) {
			showToast('주소록 저장에 실패했습니다.', 'error');
			console.error('주소록 저장 실패:', error);
		},
		complete: function() {
			$('#btnSaveAddressBook').prop('disabled', false).html('저장');
		}
	});
}

function loadAddressBookList() {
	$.ajax({
		url: '/send/get_address_book_list',
		type: 'POST',
		data: { org_id: SEND_ORG_ID },
		dataType: 'json',
		success: function(response) {
			if (response.success) {
				renderAddressBookList(response.address_books);
			}
		},
		error: function(xhr, status, error) {
			console.error('주소록 목록 조회 실패:', error);
		}
	});
}

function renderAddressBookList(addressBooks) {
	const tbody = $('#address-tab-pane tbody');
	tbody.empty();

	if (addressBooks.length === 0) {
		tbody.append('<tr><td colspan="4" class="text-center text-muted">저장된 주소록이 없습니다.</td></tr>');
		return;
	}

	addressBooks.forEach(function(book) {
		const memberList = JSON.parse(book.member_list);
		const firstMember = memberList[0];
		const otherCount = memberList.length - 1;
		const receiverText = otherCount > 0
			? firstMember.member_name + ' 외 ' + otherCount + '명'
			: firstMember.member_name;

		const row = `
			<tr>
				<td>${book.address_book_name}</td>
				<td>${receiverText}</td>
				<td>
					<button type="button" class="btn btn-xs btn-primary btn-send-addressbook" 
							data-book-idx="${book.address_book_idx}">
						전송
					</button>
				</td>
				<td>
					<button type="button" class="btn btn-xs btn-outline-danger btn-delete-addressbook" 
							data-book-idx="${book.address_book_idx}">
						삭제
					</button>
				</td>
			</tr>
		`;
		tbody.append(row);
	});

	$('.btn-delete-addressbook').on('click', function() {
		const bookIdx = $(this).data('book-idx');
		deleteAddressBook(bookIdx);
	});
}

function deleteAddressBook(addressBookIdx) {
	showConfirmModal(
		'주소록 삭제',
		'주소록을 삭제하시겠습니까?',
		function() {
			$.ajax({
				url: '/send/delete_address_book',
				type: 'POST',
				data: {
					address_book_idx: addressBookIdx,
					org_id: SEND_ORG_ID
				},
				dataType: 'json',
				success: function(response) {
					if (response.success) {
						showToast(response.message, 'success');
						loadAddressBookList();
					} else {
						showToast(response.message, 'error');
					}
				},
				error: function(xhr, status, error) {
					showToast('주소록 삭제에 실패했습니다.', 'error');
					console.error('주소록 삭제 실패:', error);
				}
			});
		}
	);
}

/*
* 역할: 전체편집 모달 열기 시 초기화
*/
function openBulkEditModal() {
	const receiverData = [];

	$('#receiverList tr.receiver-item').each(function() {
		const $row = $(this);
		receiverData.push({
			member_idx: $row.data('member-idx'),
			member_name: $row.data('name'),
			position_name: $row.find('td:eq(1)').text().trim(),
			member_phone: $row.data('phone'),
			area_name: $row.find('td:eq(3)').text().trim(),
			tmp01: $row.find('td:eq(4)').text().trim(),
			tmp02: $row.find('td:eq(5)').text().trim()
		});
	});

	// 데이터를 세션 스토리지에 저장
	sessionStorage.setItem('bulkEditData', JSON.stringify(receiverData));

	// 새 창으로 열기
	const popupWidth = 1201;
	const popupHeight = 800;
	const left = (screen.width - popupWidth) / 2;
	const top = (screen.height - popupHeight) / 2;

	const popup = window.open(
		'/send/bulk_edit_popup',
		'bulkEditPopup',
		`width=${popupWidth},height=${popupHeight},left=${left},top=${top},resizable=yes,scrollbars=yes`
	);

	// 팝업에서 데이터 저장 완료 시 처리
	window.addEventListener('message', function(event) {
		if (event.data.type === 'bulkEditComplete') {
			updateReceiverList(event.data.data);
			showToast('수정 내용이 저장되었습니다.', 'success');
		}
	});
}

function initBulkEditGrid(data) {
	const $grid = $('#bulkEditGrid');

	if (bulkEditGridInstance) {
		try {
			$grid.pqGrid('destroy');
		} catch(e) {
			// 무시
		}
		bulkEditGridInstance = null;
	}

	$grid.empty();

	const colModel = [
		{
			title: '이름',
			dataIndx: 'member_name',
			width: 120,
			editable: true,
			align: 'center'
		},
		{
			title: '직분',
			dataIndx: 'position_name',
			width: 150,
			editable: true,
			align: 'center'
		},
		{
			title: '연락처',
			dataIndx: 'member_phone',
			width: 150,
			editable: true,
			align: 'center'
		},
		{
			title: '그룹',
			dataIndx: 'area_name',
			width: 150,
			editable: true,
			align: 'center'
		},
		{
			title: '임시1',
			dataIndx: 'tmp01',
			width: 150,
			editable: true,
			align: 'center'
		},
		{
			title: '임시2',
			dataIndx: 'tmp02',
			width: 150,
			editable: true,
			align: 'center'
		}
	];

	const gridOptions = {
		width: '100%',
		height: 500,
		colModel: colModel,
		dataModel: {
			data: data
		},
		editable: true,
		editModel: {
			clicksToEdit: 2,
			saveKey: ''
		},
		selectionModel: {
			type: 'cell',
			mode: 'block'
		},
		numberCell: {
			show: true,
			title: '#',
			width: 40,
			resizable: false,
			align: 'center'
		},
		scrollModel: {
			autoFit: true
		},
		showTop: false,
		showTitle: false,
		showToolbar: false,
		wrap: false,
		hwrap: false,
		columnBorders: true,
		rowBorders: true,
		hoverMode: 'row',
		stripeRows: true,
		rowHeight: 30,
		headerHeight: 30
	};

	bulkEditGridInstance = $grid.pqGrid(gridOptions);

	// 그리드에 포커스 주기
	setTimeout(function() {
		$grid.pqGrid('focus');

		// 그리드 영역 클릭 시 포커스 유지
		$grid.on('mousedown', function() {
			$(this).focus();
		});
	}, 100);

	// 문서 레벨에서 Ctrl+C, Ctrl+V 이벤트를 우선 처리
	$(document).off('keydown.bulkEdit').on('keydown.bulkEdit', function(e) {
		// 모달이 열려있고, 그리드가 포커스 상태일 때만 처리
		if ($('#bulkEditModal').hasClass('show')) {
			if ((e.ctrlKey || e.metaKey) && (e.keyCode === 67 || e.keyCode === 86 || e.keyCode === 88)) {
				// pqGrid의 copy/paste 함수 직접 호출
				const gridInstance = $grid.pqGrid('getInstance');
				if (e.keyCode === 67) { // Ctrl+C
					gridInstance.copy();
					e.preventDefault();
					e.stopPropagation();
				} else if (e.keyCode === 86) { // Ctrl+V
					gridInstance.paste();
					e.preventDefault();
					e.stopPropagation();
				} else if (e.keyCode === 88) { // Ctrl+X
					gridInstance.cut();
					e.preventDefault();
					e.stopPropagation();
				}
			}
		}
	});
}

function saveBulkEdit() {
	if (!bulkEditGridInstance) {
		showToast('그리드가 초기화되지 않았습니다.', 'error');
		return;
	}

	try {
		$('#bulkEditGrid').pqGrid('saveEditCell');
		const data = $('#bulkEditGrid').pqGrid('option', 'dataModel.data');

		if (!data || data.length === 0) {
			showToast('저장할 데이터가 없습니다.', 'warning');
			return;
		}

		showConfirmModal(
			'전체편집 저장',
			'수정된 내용을 저장하시겠습니까?',
			function() {
				updateReceiverList(data);
				const modalEl = document.getElementById('bulkEditModal');
				const modal = bootstrap.Modal.getInstance(modalEl);
				if (modal) {
					modal.hide();
				}
				showToast('수정 내용이 저장되었습니다.', 'success');
			}
		);
	} catch(e) {
		console.error('저장 중 오류:', e);
		showToast('저장 중 오류가 발생했습니다.', 'error');
	}
}

function updateReceiverList(data) {
	$('#receiverList').empty();

	data.forEach(function(item) {
		const row = `
			<tr class="receiver-item"
				 data-member-idx="${item.member_idx}"
				 data-phone="${item.member_phone}"
				 data-name="${item.member_name}">
				<td>${item.member_name}</td>
				<td>${item.position_name || ''}</td>
				<td>${item.member_phone}</td>
				<td>${item.area_name || ''}</td>
				<td>${item.tmp01 || ''}</td>
				<td>${item.tmp02 || ''}</td>
				<td><a class="remove-receiver"><i class="bi bi-x-lg"></i></a></td>
			</tr>
		`;
		$('#receiverList').append(row);
	});

	updateReceiverCount();
	updateSendCost();
}

function cleanupBulkEditGrid() {
	// 모달의 키보드 이벤트 핸들러 제거
	$('#bulkEditModal').off('keydown');

	if (bulkEditGridInstance) {
		try {
			$('#bulkEditGrid').pqGrid('destroy');
		} catch(e) {
			// 무시
		}
		bulkEditGridInstance = null;
	}
	$('#bulkEditGrid').empty();
}

// ===== 유틸리티 함수 =====
function formatNumber(num) {
	return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

function showConfirmModal(title, message, confirmCallback, cancelCallback = null) {
	let confirmModal = $('#dynamicConfirmModal');
	if (confirmModal.length === 0) {
		confirmModal = $(`
			<div class="modal fade" id="dynamicConfirmModal" tabindex="-1" aria-labelledby="dynamicConfirmModalLabel" aria-hidden="true">
				<div class="modal-dialog">
					<div class="modal-content">
						<div class="modal-header">
							<h5 class="modal-title" id="dynamicConfirmModalLabel"></h5>
							<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
						</div>
						<div class="modal-body"></div>
						<div class="modal-footer">
							<button type="button" class="btn btn-secondary" data-bs-dismiss="modal" id="modalCancelBtn">취소</button>
							<button type="button" class="btn btn-primary" id="modalConfirmBtn">확인</button>
						</div>
					</div>
				</div>
			</div>
		`);
		$('body').append(confirmModal);
	}

	confirmModal.find('.modal-title').text(title);
	confirmModal.find('.modal-body').html(message.replace(/\n/g, '<br>'));

	confirmModal.find('#modalConfirmBtn').off('click').on('click', function() {
		const modalInstance = bootstrap.Modal.getInstance(confirmModal[0]);
		if (modalInstance) {
			modalInstance.hide();
		} else {
			confirmModal.modal('hide');
		}
		if (confirmCallback) {
			confirmCallback();
		}
	});

	confirmModal.find('#modalCancelBtn').off('click').on('click', function() {
		const modalInstance = bootstrap.Modal.getInstance(confirmModal[0]);
		if (modalInstance) {
			modalInstance.hide();
		} else {
			confirmModal.modal('hide');
		}
		if (cancelCallback) {
			cancelCallback();
		}
	});

	const modalInstance = new bootstrap.Modal(confirmModal[0]);
	modalInstance.show();
}
