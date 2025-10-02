/**
 * 파일 위치: assets/js/send_popup.js
 * 역할: 문자 발송 팝업의 충전, 발신번호 관리, 수신자 관리, 전체편집 기능 처리
 */

// ===== 전역 변수 =====
let selectedPackage = null;
let bulkEditGridInstance = null;
let authTimer = null;
let authTimeLeft = 120;

// 메시지 타입별 제한 설정
const MESSAGE_LIMITS = {
	sms: { maxChar: 70, label: 'SMS (70자 이하)' },
	lms: { maxChar: 1000, label: 'LMS (1000자 이하)' },
	mms: { maxChar: 1000, label: 'MMS (1000자 이하)' }
};


let availableCounts = {
	sms: 0,
	lms: 0,
	mms: 0,
	kakao: 0
};

let packagePrices = {
	sms: 10,
	lms: 20,
	mms: 30,
	kakao: 20
};

$(document).ready(function() {
	// 초기 잔액 조회
	loadBalance();

	// 전송 히스토리 년월 초기화
	initHistoryYearMonth();

	// 년월 선택 변경 이벤트
	$('#historyYear, #historyMonth').on('change', function() {
		loadSendHistory();
	});

	// 이전월 버튼
	$('#btnPrevMonth').on('click', function() {
		changeHistoryMonth(-1);
	});

	// 다음월 버튼
	$('#btnNextMonth').on('click', function() {
		changeHistoryMonth(1);
	});

	// 발송 타입 변경 이벤트
	$('input[name="send_type"]').on('change', function() {
		handleSendTypeChange($(this).val());
		updateSendCost();
	});

	// 메시지 내용 입력 이벤트
	$('#messageContent').on('input', function() {
		handleMessageInput();
	});

	// 카카오톡 버튼에 툴팁 추가
	$('#kakao').next('label').attr({
		'data-bs-toggle': 'tooltip',
		'data-bs-placement': 'top',
		'title': '현재 준비중입니다'
	});

	// Bootstrap 툴팁 초기화
	const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
	tooltipTriggerList.map(function (tooltipTriggerEl) {
		return new bootstrap.Tooltip(tooltipTriggerEl);
	});

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
	$('#popup-edit').on('click', function(e) {
		e.preventDefault();
		// const totalCount = $('#receiverList tr.receiver-item').length;
		// if (totalCount === 0) {
		// 	showToast('편집할 수신자가 없습니다.', 'warning');
		// 	return;
		// }
		openBulkEditModal();
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


	// 발송 치환 버튼 클릭 이벤트 추가
	$('.btn-replace').filter(function() {
		return $(this).parent().prev('label').text().includes('발송 치환');
	}).on('click', function(e) {
		e.preventDefault();
		const fieldName = $(this).text().trim();
		insertReplacementField(fieldName);
	});


	// 발송시점 라디오 버튼 변경 이벤트
	$('input[name="send_schedule_type"]').on('change', function() {
		handleScheduleTypeChange($(this).val());
	});

	$('#timeAddSelect').on('change', function() {
		const hours = parseInt($(this).val());
		if (hours) {
			addHoursToSchedule(hours);
			$(this).val('');
		}
	});

	// 발송하기 버튼 클릭
	$('#sendBtn').on('click', function() {
		processSendMessage();
	});

	// 발송 히스토리 탭 클릭 시 목록 로드
	$('#history-tab').on('shown.bs.tab', function() {
		loadSendHistory();
	});

	// 예약 발송 목록 탭 클릭 시 목록 로드
	$('#reservation-tab').on('shown.bs.tab', function() {
		loadReservationList();
	});







	// 템플릿 모달 저장 버튼
	$(document).on('click', '#btnSaveTemplate', function() {
		saveTemplateFromModal();
	});

	// 페이지 로드 시 템플릿 목록 로드 (template 탭이 활성화된 경우)
	if ($('#template-tab').hasClass('active')) {
		loadMessageTemplates();
	}


	// 주소록 엑셀 다운로드 버튼
	$('#address-download').on('click', function(e) {
		e.preventDefault();
		downloadAddressBookExcel();
	});


	// 엑셀로 예약발송 추가 버튼
	$('#btnExcelReservationUpload').on('click', function(e) {
		e.preventDefault();
		const offcanvas = new bootstrap.Offcanvas(document.getElementById('excelReservationOffcanvas'));
		offcanvas.show();
	});

	// 엑셀 서식 다운로드
	$('#btnDownloadReservationTemplate').on('click', function() {
		downloadReservationTemplate();
	});

	// 엑셀 업로드 저장
	$('#btnUploadReservationExcel').on('click', function() {
		uploadReservationExcel();
	});


});

// ===== 발송 타입 및 메시지 관련 함수 =====
/**
 * 역할: 발송 타입 변경 시 처리
 */
function handleSendTypeChange(sendType) {
	// 카카오톡 선택 시 차단
	if (sendType === 'kakao') {
		showToast('카카오톡 발송은 현재 준비중입니다.', 'warning');
		$('#sms').prop('checked', true);
		return;
	}

	// MMS 파일첨부 섹션 표시/숨김
	if (sendType === 'mms') {
		$('#mmsFileSection').removeClass('d-none');
	} else {
		$('#mmsFileSection').addClass('d-none');
		$('#mmsFile').val('');
	}

	// 글자 수 제한 업데이트
	const limit = MESSAGE_LIMITS[sendType];
	if (limit) {
		$('#maxChar').text(limit.maxChar);
		$('#messageTypeInfo').text(limit.label);
	}

	// 현재 메시지 재검증
	handleMessageInput();
}

/**
 * 역할: 메시지 입력 시 글자 수 카운트 및 자동 타입 전환
 */
function handleMessageInput() {
	const message = $('#messageContent').val();
	const charCount = message.length;
	const currentType = $('input[name="send_type"]:checked').val();

	// 글자 수 표시
	$('#charCount').text(charCount);

	// SMS에서 70자 초과 시 자동으로 LMS로 전환
	if (currentType === 'sms' && charCount > MESSAGE_LIMITS.sms.maxChar) {
		$('#lms').prop('checked', true);
		$('#maxChar').text(MESSAGE_LIMITS.lms.maxChar);
		$('#messageTypeInfo').text(MESSAGE_LIMITS.lms.label);
		showToast('70자를 초과하여 자동으로 LMS로 전환되었습니다.', 'info');
		updateSendCost();
	}
}

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
				availableCounts = response.available_counts;
				packagePrices = response.package_prices;
				updateSendTypeLabels();
			}
		},
		error: function(xhr, status, error) {
			console.error('잔액 조회 실패:', error);
		}
	});
}

/**
 * 역할: 발송 타입별 건당 단가 표시
 */
function updateSendTypeLabels() {
	const typeLabels = {
		sms: { name: 'SMS' },
		lms: { name: 'LMS' },
		mms: { name: 'MMS' },
		kakao: { name: '카카오톡' }
	};

	Object.keys(typeLabels).forEach(function(type) {
		const price = packagePrices[type] || 0;
		const $label = $(`#${type}`).next('label');

		// 건당 단가 표시
		$label.html(`${typeLabels[type].name} <small>(${price}원)</small>`);
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

/**
 * 역할: 충전 완료 후 잔액 업데이트
 */
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
						availableCounts = response.available_counts;
						packagePrices = response.package_prices;
						updateSendTypeLabels();
						updateSendCost(); // 발송 비용도 업데이트

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

	$('#authCodeMessage').html(`입력한 발신번호(${senderNumber})로 6자리 인증번호를 발송하였습니다.<br>2분 안에 인증번호를 입력해주세요!`);
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

/**
 * 역할: 빈 수신자 메시지 표시
 */
function showEmptyReceiverMessage() {
	const emptyMessage = `
		<tr class="empty-message">
			<td colspan="7" class="text-center text-muted">선택된 회원이 없습니다.</td>
		</tr>
	`;
	$('#receiverList').html(emptyMessage);
}

// 수신자 전체 삭제
$('#btn-remove-all').on('click', function(e) {
	e.preventDefault();
	const totalCount = $('#receiverList tr.receiver-item').length;
	if (totalCount === 0) {
		showToast('삭제할 수신자가 없습니다.', 'warning');
		return;
	}
	const firstMember = $('#receiverList tr.receiver-item:first').data('name');
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
$('#btn-save-addressbook').on('click', function(e) {
	e.preventDefault();
	const totalCount = $('#receiverList tr.receiver-item').length;
	if (totalCount === 0) {
		showToast('저장할 수신자가 없습니다.', 'warning');
		return;
	}
	showAddressBookNameModal();
});

function updateReceiverCount() {
	const count = $('#receiverList tr.receiver-item').length;
	$('#receiverCount').text(count);
}

/**
 * 역할: 발송 비용 표시 업데이트 (발송 타입별 단가 표시 포함)
 */
function updateSendCost() {
	const count = $('#receiverList tr.receiver-item').length;
	const sendType = $('input[name="send_type"]:checked').val();

	// 현재 패키지의 단가 사용
	const costPerMessage = packagePrices[sendType] || 0;

	const totalCost = count * costPerMessage;

	$('#costTotal').text(formatNumber(totalCost) + '원');
	$('#costTotal').next('small').text('(' + count + '명 × ' + costPerMessage + '원)');

	// 잔액과 비교하여 경고 표시
	const currentBalance = parseInt($('#currentBalance').text().replace(/[^0-9]/g, ''));
	if (totalCost > currentBalance) {
		$('#costTotal').addClass('text-danger');
		showToast('잔액이 부족합니다. 문자를 충전해주세요.', 'warning');
	} else {
		$('#costTotal').removeClass('text-danger');
	}
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
			member_phone: $row.data('phone'),
			position_name: $row.find('td:eq(1)').text().trim(),
			area_name: $row.find('td:eq(3)').text().trim(),
			tmp01: $row.find('td:eq(4)').text().trim(),
			tmp02: $row.find('td:eq(5)').text().trim()
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

// renderAddressBookList 함수 수정
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
					<button type="button" class="btn btn-xs btn-primary btn-apply-addressbook" 
							data-book-idx="${book.address_book_idx}">
						적용
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

	// 적용 버튼 이벤트
	$('.btn-apply-addressbook').on('click', function() {
		const bookIdx = $(this).data('book-idx');
		applyAddressBook(bookIdx);
	});

	// 삭제 버튼 이벤트
	$('.btn-delete-addressbook').on('click', function() {
		const bookIdx = $(this).data('book-idx');
		deleteAddressBook(bookIdx);
	});
}


/**
 * 역할: 주소록 적용 - 저장된 member_list를 수신자 목록에 적용
 */
function applyAddressBook(addressBookIdx) {
	$.ajax({
		url: '/send/get_address_book_detail',
		type: 'POST',
		data: {
			address_book_idx: addressBookIdx,
			org_id: SEND_ORG_ID
		},
		dataType: 'json',
		success: function(response) {
			if (response.success) {
				const memberList = response.member_list;

				showConfirmModal(
					'주소록 적용',
					'현재 수신자 목록을 주소록의 회원(' + memberList.length + '명)으로 교체하시겠습니까?',
					function() {
						// 기존 수신자 목록 초기화
						$('#receiverList').empty();

						// 주소록의 회원들을 수신자 목록에 추가
						memberList.forEach(function(member) {
							const row = `
								<tr class="receiver-item"
									 data-member-idx="${member.member_idx || ''}"
									 data-phone="${member.member_phone}"
									 data-name="${member.member_name}">
									<td>${member.member_name}</td>
									<td>${member.position_name || ''}</td>
									<td>${member.member_phone}</td>
									<td>${member.area_name || ''}</td>
									<td>${member.tmp01 || ''}</td>
									<td>${member.tmp02 || ''}</td>
									<td><a class="remove-receiver"><i class="bi bi-x-lg"></i></a></td>
								</tr>
							`;
							$('#receiverList').append(row);
						});

						updateReceiverCount();
						updateSendCost();
						showToast('주소록이 적용되었습니다.', 'success');
					}
				);
			} else {
				showToast(response.message || '주소록을 불러오는데 실패했습니다.', 'error');
			}
		},
		error: function() {
			showToast('주소록 조회 중 오류가 발생했습니다.', 'error');
		}
	});
}

/**
 * 역할: 주소록 전체 엑셀 다운로드
 */
function downloadAddressBookExcel() {
	$.ajax({
		url: '/send/get_address_book_list',
		type: 'POST',
		data: { org_id: SEND_ORG_ID },
		dataType: 'json',
		success: function(response) {
			if (response.success && response.address_books.length > 0) {
				// 엑셀 데이터 생성
				const excelData = [];

				response.address_books.forEach(function(book) {
					const memberList = JSON.parse(book.member_list);

					memberList.forEach(function(member) {
						excelData.push({
							'주소록명': book.address_book_name,
							'이름': member.member_name,
							'직분': member.position_name || '',
							'연락처': member.member_phone,
							'그룹': member.area_name || '',
							'임시1': member.tmp01 || '',
							'임시2': member.tmp02 || ''
						});
					});
				});

				// CSV 형식으로 변환
				let csv = '\uFEFF'; // BOM for UTF-8
				const headers = ['주소록명', '이름', '직분', '연락처', '그룹', '임시1', '임시2'];
				csv += headers.join(',') + '\n';

				excelData.forEach(function(row) {
					const values = headers.map(function(header) {
						const value = row[header] || '';
						// 쉼표나 따옴표가 있으면 따옴표로 감싸기
						if (value.indexOf(',') > -1 || value.indexOf('"') > -1) {
							return '"' + value.replace(/"/g, '""') + '"';
						}
						return value;
					});
					csv += values.join(',') + '\n';
				});

				// 다운로드 실행
				const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
				const link = document.createElement('a');
				const url = URL.createObjectURL(blob);
				const fileName = '주소록_' + new Date().toISOString().slice(0, 10) + '.csv';

				link.setAttribute('href', url);
				link.setAttribute('download', fileName);
				link.style.visibility = 'hidden';
				document.body.appendChild(link);
				link.click();
				document.body.removeChild(link);

				showToast('엑셀 파일이 다운로드되었습니다.', 'success');
			} else {
				showToast('다운로드할 주소록이 없습니다.', 'warning');
			}
		},
		error: function() {
			showToast('엑셀 다운로드 중 오류가 발생했습니다.', 'error');
		}
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

/**
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



/**
 * 역할: 수신자 목록 업데이트
 */
function updateReceiverList(data) {
	const $receiverList = $('#receiverList');
	$receiverList.empty();

	if (!data || data.length === 0) {
		showEmptyReceiverMessage();
		updateReceiverCount();
		updateSendCost();
		return;
	}

	data.forEach(function(item) {
		const row = `
			<tr class="receiver-item"
				 data-member-idx="${item.member_idx || ''}"
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
		$receiverList.append(row);
	});

	updateReceiverCount();
	updateSendCost();
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


/**
 * 역할: 발송 치환 버튼 클릭 시 메시지 입력란에 치환 필드 삽입
 */
function insertReplacementField(fieldName) {
	const textarea = $('#messageContent')[0];
	const cursorPos = textarea.selectionStart;
	const textBefore = textarea.value.substring(0, cursorPos);
	const textAfter = textarea.value.substring(cursorPos);

	const replacement = '{' + fieldName + '}';
	textarea.value = textBefore + replacement + textAfter;

	// 커서 위치를 삽입된 텍스트 뒤로 이동
	const newCursorPos = cursorPos + replacement.length;
	textarea.setSelectionRange(newCursorPos, newCursorPos);

	// 포커스를 textarea로 이동
	textarea.focus();

	// 글자 수 업데이트
	handleMessageInput();
}



/**
 * 역할: 발송시점 타입 변경 시 처리
 */
function handleScheduleTypeChange(scheduleType) {
	const $scheduledTime = $('#scheduledTime');
	const $timeAddSelect = $('#timeAddSelect');

	if (scheduleType === 'scheduled') {
		$scheduledTime.prop('disabled', false);
		$timeAddSelect.prop('disabled', false);

		// 현재 시간으로 초기화
		const now = new Date();
		const year = now.getFullYear();
		const month = String(now.getMonth() + 1).padStart(2, '0');
		const day = String(now.getDate()).padStart(2, '0');
		const hours = String(now.getHours()).padStart(2, '0');
		const minutes = String(now.getMinutes()).padStart(2, '0');
		const formattedDateTime = `${year}-${month}-${day}T${hours}:${minutes}`;

		$scheduledTime.val(formattedDateTime);
	} else {
		$scheduledTime.prop('disabled', true).val('');
		$timeAddSelect.prop('disabled', true).val('');
	}
}

/**
 * 역할: 예약 시간에 지정된 시간 추가
 */
function addHoursToSchedule(hours) {
	const $scheduledTime = $('#scheduledTime');
	const currentValue = $scheduledTime.val();

	if (!currentValue) {
		showToast('먼저 시간을 선택해주세요.', 'warning');
		return;
	}

	const currentDate = new Date(currentValue);
	currentDate.setHours(currentDate.getHours() + hours);

	const year = currentDate.getFullYear();
	const month = String(currentDate.getMonth() + 1).padStart(2, '0');
	const day = String(currentDate.getDate()).padStart(2, '0');
	const hoursStr = String(currentDate.getHours()).padStart(2, '0');
	const minutes = String(currentDate.getMinutes()).padStart(2, '0');
	const formattedDateTime = `${year}-${month}-${day}T${hoursStr}:${minutes}`;

	$scheduledTime.val(formattedDateTime);
}

/**
 * 역할: 메시지 발송 처리
 */
function processSendMessage() {
	const scheduleType = $('input[name="send_schedule_type"]:checked').val();
	const sendType = $('input[name="send_type"]:checked').val();
	const senderNumber = $('#senderSelect').val();
	const senderName = $('#senderSelect option:selected').data('name');
	const messageContent = $('#messageContent').val().trim();
	const scheduledTime = $('#scheduledTime').val();

	// 유효성 검사
	if (!senderNumber) {
		showToast('발신번호를 선택해주세요.', 'warning');
		return;
	}

	if (!messageContent) {
		showToast('메시지 내용을 입력해주세요.', 'warning');
		return;
	}

	const receiverList = [];
	$('#receiverList tr.receiver-item').each(function() {
		const $row = $(this);
		receiverList.push({
			member_idx: $row.data('member-idx'),
			member_name: $row.data('name'),
			member_phone: $row.data('phone'),
			position_name: $row.find('td:eq(1)').text().trim(),
			area_name: $row.find('td:eq(3)').text().trim(),
			tmp01: $row.find('td:eq(4)').text().trim(),
			tmp02: $row.find('td:eq(5)').text().trim()
		});
	});

	if (receiverList.length === 0) {
		showToast('수신자를 선택해주세요.', 'warning');
		return;
	}

	// 예상 발송 비용 계산
	const costPerMessage = packagePrices[sendType] || 0;
	const totalCost = receiverList.length * costPerMessage;

	// 시간지정발송인 경우 시간 검증
	if (scheduleType === 'scheduled') {
		if (!scheduledTime) {
			showToast('예약 발송 시간을 선택해주세요.', 'warning');
			return;
		}

		const selectedDate = new Date(scheduledTime);
		const now = new Date();

		if (selectedDate <= now) {
			showToast('예약 시간은 현재 시간 이후로 설정해주세요.', 'warning');
			return;
		}
	}

	// 발송 확인 모달
	const confirmMessage = scheduleType === 'now'
		? `${receiverList.length}명에게 즉시 발송하시겠습니까?\n예상 비용: ${formatNumber(totalCost)}원`
		: `${receiverList.length}명에게 ${scheduledTime.replace('T', ' ')}에 발송하시겠습니까?\n예상 비용: ${formatNumber(totalCost)}원`;

	showConfirmModal(
		'문자 발송',
		confirmMessage,
		function() {
			if (scheduleType === 'now') {
				sendImmediately(sendType, senderNumber, senderName, messageContent, receiverList, totalCost);
			} else {
				sendScheduled(sendType, senderNumber, senderName, messageContent, receiverList, scheduledTime, totalCost);
			}
		}
	);
}


/**
 * 역할: 즉시 발송 처리 - 메시지 내용 및 수신자별 결과 저장
 */
function sendImmediately(sendType, senderNumber, senderName, messageContent, receiverList, totalCost) {
	// 서버에 비용 차감 요청
	$.ajax({
		url: '/send/send_message_immediately',
		type: 'POST',
		data: {
			org_id: SEND_ORG_ID,
			send_type: sendType,
			sender_number: senderNumber,
			sender_name: senderName,
			message_content: messageContent,
			receiver_list: receiverList,
			total_cost: totalCost
		},
		dataType: 'json',
		success: function(response) {
			if (!response.success) {
				showToast(response.message, 'error');
				return;
			}

			// 비용 차감 성공 후 실제 발송 시뮬레이션
			let successCount = 0;
			let failCount = 0;
			const receiverResults = []; // 수신자별 결과 저장

			receiverList.forEach(function(receiver, index) {
				setTimeout(function() {
					// 치환 필드 처리
					let personalizedMessage = messageContent;
					personalizedMessage = personalizedMessage.replace(/{이름}/g, receiver.member_name);
					personalizedMessage = personalizedMessage.replace(/{직분}/g, receiver.position_name || '');
					personalizedMessage = personalizedMessage.replace(/{연락처}/g, receiver.member_phone);
					personalizedMessage = personalizedMessage.replace(/{그룹}/g, receiver.area_name || '');
					personalizedMessage = personalizedMessage.replace(/{임시1}/g, receiver.tmp01 || '');
					personalizedMessage = personalizedMessage.replace(/{임시2}/g, receiver.tmp02 || '');

					// 실제로는 API 호출이 필요하지만, 여기서는 시뮬레이션
					// 90% 성공률로 시뮬레이션
					const isSuccess = Math.random() > 0.1;
					let resultStatus = 'success';
					let resultMessage = '발송 성공';

					if (!isSuccess) {
						// 실패 사유를 다양하게 시뮬레이션
						const failReasons = [
							'수신 거부 번호',
							'잘못된 번호 형식',
							'통신사 오류',
							'일일 발송 한도 초과',
							'스팸 필터링'
						];
						resultStatus = 'failed';
						resultMessage = failReasons[Math.floor(Math.random() * failReasons.length)];
						failCount++;
					} else {
						successCount++;
					}

					// 수신자 결과 저장
					receiverResults.push({
						member_idx: receiver.member_idx,
						member_name: receiver.member_name,
						member_phone: receiver.member_phone,
						position_name: receiver.position_name || '',
						area_name: receiver.area_name || '',
						status: resultStatus,
						result_message: resultMessage,
						sent_message: personalizedMessage
					});

					// 실시간 toast 표시
					if (isSuccess) {
						const toastMessage = `[${receiver.member_name}(${receiver.member_phone})] ${personalizedMessage}`;
						showToast(toastMessage, 'success');
					} else {
						showToast(`${receiver.member_name}(${receiver.member_phone}) 발송 실패: ${resultMessage}`, 'error');
					}

					// 마지막 수신자 처리 후 히스토리 저장 및 탭 전환
					if (index === receiverList.length - 1) {
						setTimeout(function() {
							// 히스토리 저장 (메시지 내용 및 수신자별 결과 포함)
							saveToHistory(
								sendType,
								senderNumber,
								senderName,
								messageContent,
								receiverResults,
								successCount > 0 ? 'success' : 'failed',
								function(saveSuccess) {
									// 잔액 업데이트
									updateBalanceDisplay(response.new_balance);

									// 완료 메시지
									showToast(`전체 발송 완료: 성공 ${successCount}건, 실패 ${failCount}건`, 'info');

									// 발송 히스토리 탭으로 자동 전환
									if (saveSuccess) {
										setTimeout(function() {
											$('#history-tab').tab('show');
											loadSendHistory();
										}, 1000);
									}
								}
							);
						}, 500);
					}
				}, index * 500); // 각 메시지마다 0.5초 간격
			});
		},
		error: function() {
			showToast('발송 처리 중 오류가 발생했습니다.', 'error');
		}
	});
}



/**
 * 역할: 예약 발송 처리
 */
function sendScheduled(sendType, senderNumber, senderName, messageContent, receiverList, scheduledTime, totalCost) {
	// 예약 발송은 실제 발송 시점에 비용이 차감되므로 현재는 잔액 확인만 수행
	const currentBalance = parseInt($('#currentBalance').text().replace(/[^0-9]/g, ''));

	if (currentBalance < totalCost) {
		showToast('잔액이 부족합니다. 문자를 충전해주세요.', 'error');
		return;
	}

	// 예약 발송 목록에 저장
	const reservationData = {
		org_id: SEND_ORG_ID,
		send_type: sendType,
		sender_number: senderNumber,
		sender_name: senderName,
		message_content: messageContent,
		receiver_list: receiverList,
		scheduled_time: scheduledTime,
		estimated_cost: totalCost
	};

	$.ajax({
		url: '/send/save_reservation',
		type: 'POST',
		data: reservationData,
		dataType: 'json',
		success: function(response) {
			if (response.success) {
				showToast('예약 발송이 등록되었습니다.', 'success');

				// 예약 발송 목록 탭으로 이동
				$('#reservation-tab').tab('show');
				loadReservationList();
			} else {
				showToast(response.message || '예약 발송 등록에 실패했습니다.', 'error');
			}
		},
		error: function() {
			showToast('예약 발송 등록 중 오류가 발생했습니다.', 'error');
		}
	});
}

/**
 * 역할: 발송 히스토리에 저장 (메시지 내용 및 수신자별 결과 포함)
 */
function saveToHistory(sendType, senderNumber, senderName, messageContent, receiverResults, status, callback) {
	const historyData = {
		org_id: SEND_ORG_ID,
		send_type: sendType,
		sender_number: senderNumber,
		sender_name: senderName,
		message_content: messageContent, // 메시지 내용 추가
		receiver_count: receiverResults.length,
		receiver_list: receiverResults, // 수신자별 결과 포함
		status: status,
		send_date: new Date().toISOString()
	};

	$.ajax({
		url: '/send/save_history',
		type: 'POST',
		data: historyData,
		dataType: 'json',
		success: function(response) {
			console.log('히스토리 저장 완료');
			if (callback && typeof callback === 'function') {
				callback(true);
			}
		},
		error: function() {
			console.error('히스토리 저장 실패');
			if (callback && typeof callback === 'function') {
				callback(false);
			}
		}
	});
}

/**
 * 역할: 발송 히스토리 목록 로드
 */
function loadSendHistory() {
	const year = $('#historyYear').val();
	const month = $('#historyMonth').val();

	$.ajax({
		url: '/send/get_send_history',
		type: 'POST',
		data: {
			org_id: SEND_ORG_ID,
			year: year,
			month: month
		},
		dataType: 'json',
		success: function(response) {
			if (response.success) {
				renderSendHistory(response.history);
			}
		},
		error: function() {
			console.error('전송 히스토리 조회 실패');
		}
	});
}

/**
 * 역할: 전송 히스토리 목록 렌더링
 */
function renderSendHistory(historyList) {
	const tbody = $('#historyTableBody');
	tbody.empty();

	if (historyList.length === 0) {
		tbody.append('<tr><td colspan="4" class="text-center text-muted">전송 이력이 없습니다.</td></tr>');
		return;
	}

	historyList.forEach(function(item) {
		const receiverText = item.receiver_count > 1
			? `${item.first_receiver_name} 외 ${item.receiver_count - 1}명`
			: item.first_receiver_name;

		const row = `
			<tr>
				<td>${item.send_date}</td>
				<td>${item.sender_number}</td>
				<td>${receiverText}</td>
				<td><a class="btn btn-xs btn-success btn-view-result" data-history-idx="${item.history_idx}">결과확인</a></td>
			</tr>
		`;
		tbody.append(row);
	});

	// 결과확인 버튼 이벤트
	$('.btn-view-result').on('click', function(e) {
		e.preventDefault();
		const historyIdx = $(this).data('history-idx');
		showHistoryDetail(historyIdx);
	});
}

/**
 * 역할: 예약 발송 목록 로드
 */
function loadReservationList() {
	$.ajax({
		url: '/send/get_reservation_list',
		type: 'POST',
		data: { org_id: SEND_ORG_ID },
		dataType: 'json',
		success: function(response) {
			if (response.success) {
				renderReservationList(response.reservations);
			}
		},
		error: function() {
			console.error('예약 발송 목록 조회 실패');
		}
	});
}

/**
 * 역할: 예약 발송 목록 렌더링
 */
function renderReservationList(reservationList) {
	const tbody = $('#reservation-tab-pane tbody');
	tbody.empty();

	if (reservationList.length === 0) {
		tbody.append('<tr><td colspan="4" class="text-center text-muted">예약된 발송이 없습니다.</td></tr>');
		return;
	}

	reservationList.forEach(function(item) {
		const receiverText = item.receiver_count > 1
			? `${item.first_receiver_name} 외 ${item.receiver_count - 1}명`
			: item.first_receiver_name;

		const row = `
			<tr>
				<td>${item.scheduled_time}</td>
				<td>${item.sender_number}</td>
				<td>${receiverText}</td>
				<td>
					<a class="btn btn-xs btn-warning btn-view-reservation" data-reservation-idx="${item.reservation_idx}">내용확인</a>
					<a class="btn btn-xs btn-outline-danger btn-cancel-reservation" data-reservation-idx="${item.reservation_idx}">취소</a>
				</td>
			</tr>
		`;
		tbody.append(row);
	});

	// 내용확인 버튼 이벤트
	$('.btn-view-reservation').on('click', function(e) {
		e.preventDefault();
		const reservationIdx = $(this).data('reservation-idx');
		showReservationDetail(reservationIdx);
	});

	// 취소 버튼 이벤트
	$('.btn-cancel-reservation').on('click', function(e) {
		e.preventDefault();
		const reservationIdx = $(this).data('reservation-idx');
		cancelReservation(reservationIdx);
	});
}

/**
 * 역할: 예약 발송 취소
 */
function cancelReservation(reservationIdx) {
	showConfirmModal(
		'예약 취소',
		'예약 발송을 취소하시겠습니까?',
		function() {
			$.ajax({
				url: '/send/cancel_reservation',
				type: 'POST',
				data: {
					reservation_idx: reservationIdx,
					org_id: SEND_ORG_ID
				},
				dataType: 'json',
				success: function(response) {
					if (response.success) {
						showToast('예약이 취소되었습니다.', 'success');
						loadReservationList();
					} else {
						showToast(response.message || '취소에 실패했습니다.', 'error');
					}
				},
				error: function() {
					showToast('취소 처리 중 오류가 발생했습니다.', 'error');
				}
			});
		}
	);
}







/**
 * 역할: 메시지 템플릿 목록 렌더링
 */
function renderMessageTemplates(templates) {
	const container = $('#templateContainer');
	container.empty();

	if (templates.length === 0) {
		container.append('<div class="col-12 text-center text-muted py-4">등록된 템플릿이 없습니다.</div>');
		return;
	}

	templates.forEach(function(template) {
		const isAdminTemplate = !template.org_id || template.org_id == 0;
		const typeBadge = getTypeBadge(template.template_type);

		// 관리자 템플릿은 수정/삭제 버튼 없음
		const actionButtons = isAdminTemplate ? '' : `
			<a href="#" class="btn-edit-template text-white position-absolute" style="bottom: 10px; right: 40px" data-template-idx="${template.template_idx}" data-template-type="${template.template_type}" data-template-content="${escapeHtml(template.template_content)}">
				<i class="bi bi-pencil-square"></i>
			</a>
			<a href="#" class="btn-delete-template text-white position-absolute" style="bottom: 10px; right: 10px" data-template-idx="${template.template_idx}">
				<i class="bi bi-trash"></i>
			</a>
		`;

		const templateItem = `
			<div class="col-6 col-sm-4">
				<figure class="figure figure-template rounded bg-dark text-white p-2 position-relative ${isAdminTemplate ? 'admin-template' : ''}" 
						data-template-idx="${template.template_idx}"
						data-template-type="${template.template_type}"
						data-is-admin="${isAdminTemplate}">
					<span class="badge ${typeBadge.class} position-absolute" style="bottom: 8px; left: 8px;">${typeBadge.text}</span>
					<small>${escapeHtml(template.template_content)}</small>
					${actionButtons}
				</figure>
			</div>
		`;
		container.append(templateItem);
	});

	// 템플릿 클릭 이벤트 - 메시지 내용 적용
	$('.figure-template').on('click', function(e) {
		// 버튼 클릭이 아닐 때만 적용
		if ($(e.target).closest('.btn-edit-template, .btn-delete-template').length === 0) {
			const content = $(this).find('small').text();
			applyTemplateToMessage(content);
		}
	});

	// 수정 버튼 클릭 이벤트
	$('.btn-edit-template').on('click', function(e) {
		e.preventDefault();
		e.stopPropagation();
		const templateIdx = $(this).data('template-idx');
		const templateType = $(this).data('template-type');
		const templateContent = $(this).data('template-content');
		showTemplateModal(templateIdx, templateType, templateContent);
	});

	// 삭제 버튼 클릭 이벤트
	$('.btn-delete-template').on('click', function(e) {
		e.preventDefault();
		e.stopPropagation();
		const templateIdx = $(this).data('template-idx');
		deleteTemplate(templateIdx);
	});
}

/**
 * 역할: 템플릿 추가/수정 모달 표시
 */
function showTemplateModal(templateIdx = '', templateType = '', templateContent = '') {
	let templateModal = $('#templateModal');

	// 모달이 없으면 생성
	if (templateModal.length === 0) {
		templateModal = $(`
			<div class="modal fade" id="templateModal" tabindex="-1" aria-hidden="true">
				<div class="modal-dialog">
					<div class="modal-content">
						<div class="modal-header">
							<h5 class="modal-title" id="templateModalLabel">템플릿 추가</h5>
							<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
						</div>
						<div class="modal-body">
							<div class="mb-3">
								<label class="form-label">발송 타입</label>
								<div class="btn-group w-100" role="group">
									<input type="radio" class="btn-check" name="template_type" id="template_sms" value="sms" checked>
									<label class="btn btn-outline-primary" for="template_sms">SMS</label>
									
									<input type="radio" class="btn-check" name="template_type" id="template_lms" value="lms">
									<label class="btn btn-outline-success" for="template_lms">LMS</label>
									
									<input type="radio" class="btn-check" name="template_type" id="template_mms" value="mms">
									<label class="btn btn-outline-warning" for="template_mms">MMS</label>
									
									<input type="radio" class="btn-check" name="template_type" id="template_kakao" value="kakao">
									<label class="btn btn-outline-info" for="template_kakao">카카오</label>
								</div>
							</div>
							<div class="mb-3">
								<label for="templateContent" class="form-label">템플릿 내용</label>
								<textarea class="form-control" id="templateContentInput" rows="5" placeholder="템플릿 내용을 입력하세요"></textarea>
							</div>
							<input type="hidden" id="templateIdx" value="">
						</div>
						<div class="modal-footer">
							<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
							<button type="button" class="btn btn-primary" id="btnSaveTemplate">저장</button>
						</div>
					</div>
				</div>
			</div>
		`);
		$('body').append(templateModal);
	}
// 모달 타이틀 설정
	if (templateIdx) {
		$('#templateModalLabel').text('템플릿 수정');
		$('#templateIdx').val(templateIdx);
		$('#templateContentInput').val(templateContent);
		$(`input[name="template_type"][value="${templateType}"]`).prop('checked', true);
	} else {
		$('#templateModalLabel').text('템플릿 추가');
		$('#templateIdx').val('');
		$('#templateContentInput').val('');
		const currentType = $('input[name="send_type"]:checked').val();
		$(`input[name="template_type"][value="${currentType}"]`).prop('checked', true);
	}

	// 모달 표시
	const modalInstance = new bootstrap.Modal(templateModal[0]);
	modalInstance.show();
}

$(document).on('click', '#btnAddNewTemplate', function(e) {
	e.preventDefault();
	showTemplateModal(); // 빈 모달만 표시
});

/**
 * 역할: 템플릿 모달에서 저장
 */
function saveTemplateFromModal() {
	const templateIdx = $('#templateIdx').val();
	const templateType = $('input[name="template_type"]:checked').val();
	const templateContent = $('#templateContentInput').val().trim();

	if (!templateContent) {
		showToast('템플릿 내용을 입력해주세요.', 'warning');
		return;
	}

	const url = templateIdx ? '/send/update_template' : '/send/save_template';
	const data = {
		org_id: SEND_ORG_ID,
		template_content: templateContent,
		template_type: templateType
	};

	if (templateIdx) {
		data.template_idx = templateIdx;
	}

	$.ajax({
		url: url,
		type: 'POST',
		data: data,
		dataType: 'json',
		beforeSend: function() {
			$('#btnSaveTemplate').prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> 저장중...');
		},
		success: function(response) {
			if (response.success) {
				showToast(response.message, 'success');

				// 모달 닫기
				const modalEl = document.getElementById('templateModal');
				const modal = bootstrap.Modal.getInstance(modalEl);
				if (modal) {
					modal.hide();
				}

				// 템플릿 목록 새로고침
				loadMessageTemplates();
			} else {
				showToast(response.message, 'error');
			}
		},
		error: function(xhr, status, error) {
			showToast('템플릿 저장에 실패했습니다.', 'error');
			console.error('템플릿 저장 실패:', error);
		},
		complete: function() {
			$('#btnSaveTemplate').prop('disabled', false).html('저장');
		}
	});
}


/**
 * 역할: 템플릿 내용을 메시지 입력란에 적용
 */
function applyTemplateToMessage(content) {
	$('#messageContent').val(content);
	handleMessageInput();
	showToast('템플릿이 적용되었습니다.', 'success');
}

/**
 * 역할: 템플릿 삭제
 */
function deleteTemplate(templateIdx) {
	showConfirmModal(
		'템플릿 삭제',
		'템플릿을 삭제하시겠습니까?',
		function() {
			$.ajax({
				url: '/send/delete_template',
				type: 'POST',
				data: {
					template_idx: templateIdx,
					org_id: SEND_ORG_ID
				},
				dataType: 'json',
				success: function(response) {
					if (response.success) {
						showToast(response.message, 'success');
						loadMessageTemplates();
					} else {
						showToast(response.message, 'error');
					}
				},
				error: function(xhr, status, error) {
					showToast('템플릿 삭제에 실패했습니다.', 'error');
					console.error('템플릿 삭제 실패:', error);
				}
			});
		}
	);
}

/**
 * 역할: HTML 이스케이프 처리
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

/**
 * 역할: 메시지 템플릿 목록 로드
 */
function loadMessageTemplates() {
	$.ajax({
		url: '/send/get_send_templates',
		type: 'POST',
		data: { org_id: SEND_ORG_ID },
		dataType: 'json',
		success: function(response) {
			if (response.success) {
				renderMessageTemplates(response.templates);
			}
		},
		error: function(xhr, status, error) {
			console.error('템플릿 목록 조회 실패:', error);
		}
	});
}

/**
 * 역할: 템플릿 타입별 뱃지 텍스트 반환
 */
function getTypeBadge(type) {
	const badges = {
		'sms': { text: 'SMS', class: 'bg-primary' },
		'lms': { text: 'LMS', class: 'bg-success' },
		'mms': { text: 'MMS', class: 'bg-warning' },
		'kakao': { text: '카카오', class: 'bg-info' }
	};
	return badges[type] || { text: 'SMS', class: 'bg-primary' };
}



/**
 * 역할: 템플릿 내용을 메시지 입력란에 적용
 */
function applyTemplateToMessage(content) {
	// 뱃지 텍스트 제거
	content = content.replace(/^(SMS|LMS|MMS|카카오)\s*/, '').trim();
	$('#messageContent').val(content);
	handleMessageInput();
	showToast('템플릿이 적용되었습니다.', 'success');
}

/**
 * 역할: 템플릿 삭제
 */
function deleteTemplate(templateIdx) {
	showConfirmModal(
		'템플릿 삭제',
		'템플릿을 삭제하시겠습니까?',
		function() {
			$.ajax({
				url: '/send/delete_template',
				type: 'POST',
				data: {
					template_idx: templateIdx,
					org_id: SEND_ORG_ID
				},
				dataType: 'json',
				success: function(response) {
					if (response.success) {
						showToast(response.message, 'success');
						loadMessageTemplates();
					} else {
						showToast(response.message, 'error');
					}
				},
				error: function(xhr, status, error) {
					showToast('템플릿 삭제에 실패했습니다.', 'error');
					console.error('템플릿 삭제 실패:', error);
				}
			});
		}
	);
}


/**
 * 역할: HTML 이스케이프 처리
 */
function escapeHtml(text) {
	const map = {
		'&': '&amp;',
		'<': '&lt;',
		'>': '&gt;',
		'"': '&quot;',
		"'": '&#039;'
	};
	return text.replace(/[&<>"']/g, function(m) { return map[m]; });
}



/**
 * 역할: 발송 히스토리 상세 정보 표시
 */
function showHistoryDetail(historyIdx) {
	$.ajax({
		url: '/send/get_history_detail',
		type: 'POST',
		data: {
			history_idx: historyIdx,
			org_id: SEND_ORG_ID
		},
		dataType: 'json',
		success: function(response) {
			if (response.success) {
				const data = response.data;

				// 발송 정보 표시
				$('#historyDetailSendDate').text(data.send_date);
				$('#historyDetailSenderNumber').text(data.sender_number);
				$('#historyDetailSenderName').text(data.sender_name);
				$('#historyDetailSendType').html(getSendTypeBadge(data.send_type));
				$('#historyDetailReceiverCount').text(data.receiver_count + '명');
				$('#historyDetailMessage').text(data.message_content || '메시지 내용 없음');

				// 수신자 목록 표시
				const tbody = $('#historyDetailReceiverList');
				tbody.empty();

				if (data.receiver_list && data.receiver_list.length > 0) {
					data.receiver_list.forEach(function(receiver) {
						let statusBadge = '';
						if (receiver.status === 'success') {
							statusBadge = '<span class="badge bg-success">성공</span>';
						} else {
							// 실패 시 툴팁으로 사유 표시
							const failReason = receiver.result_message || '알 수 없는 오류';
							statusBadge = `<span class="badge bg-danger" 
								data-bs-toggle="tooltip" 
								data-bs-placement="top" 
								title="${failReason}" 
								style="cursor: help;">실패</span>`;
						}

						const row = `
							<tr>
								<td>${receiver.member_name}</td>
								<td>${receiver.member_phone}</td>
								<td>${statusBadge}</td>
							</tr>
						`;
						tbody.append(row);
					});

					// Bootstrap 툴팁 초기화
					const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
					tooltipTriggerList.map(function (tooltipTriggerEl) {
						return new bootstrap.Tooltip(tooltipTriggerEl);
					});
				} else {
					tbody.append('<tr><td colspan="3" class="text-center text-muted">수신자 정보가 없습니다.</td></tr>');
				}

				// Offcanvas 표시
				const offcanvas = new bootstrap.Offcanvas(document.getElementById('historyDetailOffcanvas'));
				offcanvas.show();
			} else {
				showToast(response.message || '상세 정보를 불러오는데 실패했습니다.', 'error');
			}
		},
		error: function() {
			showToast('상세 정보 조회 중 오류가 발생했습니다.', 'error');
		}
	});
}


/**
 * 역할: 예약 발송 상세 정보 표시
 */
function showReservationDetail(reservationIdx) {
	$.ajax({
		url: '/send/get_reservation_detail',
		type: 'POST',
		data: {
			reservation_idx: reservationIdx,
			org_id: SEND_ORG_ID
		},
		dataType: 'json',
		success: function(response) {
			if (response.success) {
				const data = response.data;

				// 예약 정보 표시
				$('#reservationDetailScheduledTime').text(data.scheduled_time);
				$('#reservationDetailSenderNumber').text(data.sender_number);
				$('#reservationDetailSenderName').text(data.sender_name);
				$('#reservationDetailSendType').html(getSendTypeBadge(data.send_type));
				$('#reservationDetailReceiverCount').text(data.receiver_count + '명');
				$('#reservationDetailMessage').text(data.message_content);

				// 수신자 목록 표시
				const tbody = $('#reservationDetailReceiverList');
				tbody.empty();

				if (data.receiver_list && data.receiver_list.length > 0) {
					data.receiver_list.forEach(function(receiver) {
						const row = `
							<tr>
								<td>${receiver.member_name}</td>
								<td>${receiver.member_phone}</td>
								<td>${receiver.position_name || ''}</td>
								<td>${receiver.area_name || ''}</td>
							</tr>
						`;
						tbody.append(row);
					});
				} else {
					tbody.append('<tr><td colspan="4" class="text-center text-muted">수신자 정보가 없습니다.</td></tr>');
				}

				// 예약 취소 버튼에 reservation_idx 저장
				$('#btnCancelReservationDetail').data('reservation-idx', reservationIdx);

				// Offcanvas 표시
				const offcanvas = new bootstrap.Offcanvas(document.getElementById('reservationDetailOffcanvas'));
				offcanvas.show();
			} else {
				showToast(response.message || '상세 정보를 불러오는데 실패했습니다.', 'error');
			}
		},
		error: function() {
			showToast('상세 정보 조회 중 오류가 발생했습니다.', 'error');
		}
	});
}


/**
 * 역할: 발송 타입에 따른 배지 HTML 반환
 */
function getSendTypeBadge(sendType) {
	const badges = {
		'sms': '<span class="badge bg-primary">SMS</span>',
		'lms': '<span class="badge bg-success">LMS</span>',
		'mms': '<span class="badge bg-warning">MMS</span>',
		'kakao': '<span class="badge bg-info">카카오톡</span>'
	};
	return badges[sendType] || '<span class="badge bg-secondary">알 수 없음</span>';
}


// Offcanvas 내부 예약 취소 버튼
$(document).on('click', '#btnCancelReservationDetail', function() {
	const reservationIdx = $(this).data('reservation-idx');
	if (reservationIdx) {
		// Offcanvas 닫기
		const offcanvasEl = document.getElementById('reservationDetailOffcanvas');
		const offcanvas = bootstrap.Offcanvas.getInstance(offcanvasEl);
		if (offcanvas) {
			offcanvas.hide();
		}

		// 예약 취소 실행
		setTimeout(function() {
			cancelReservation(reservationIdx);
		}, 300);
	}
});


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
 * 역할: 히스토리 월 변경 (이전월/다음월)
 */
function changeHistoryMonth(offset) {
	let year = parseInt($('#historyYear').val());
	let month = parseInt($('#historyMonth').val());

	month += offset;

	if (month > 12) {
		month = 1;
		year++;
	} else if (month < 1) {
		month = 12;
		year--;
	}

	$('#historyYear').val(year);
	$('#historyMonth').val(month);

	loadSendHistory();
}


/**
 * 역할: 예약발송 엑셀 서식 다운로드
 */
function downloadReservationTemplate() {
	const headers = ['발송예정일시', '이름', '연락처', '메시지'];
	const sampleData = [
		['2025-12-31 09:00:00', '홍길동', '010-1234-5678', '새해 복 많이 받으세요!'],
		['2025-12-31 09:00:00', '김철수', '010-2345-6789', '새해 복 많이 받으세요!']
	];

	let csv = '\uFEFF'; // BOM for UTF-8
	csv += headers.join(',') + '\n';

	sampleData.forEach(function(row) {
		const values = row.map(function(value) {
			if (value.indexOf(',') > -1 || value.indexOf('"') > -1) {
				return '"' + value.replace(/"/g, '""') + '"';
			}
			return value;
		});
		csv += values.join(',') + '\n';
	});

	const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
	const link = document.createElement('a');
	const url = URL.createObjectURL(blob);
	const fileName = '예약발송_양식_' + new Date().toISOString().slice(0, 10) + '.csv';

	link.setAttribute('href', url);
	link.setAttribute('download', fileName);
	link.style.visibility = 'hidden';
	document.body.appendChild(link);
	link.click();
	document.body.removeChild(link);

	showToast('엑셀 서식이 다운로드되었습니다.', 'success');
}

/**
 * 역할: 예약발송 엑셀 파일 업로드 및 검증
 */
function uploadReservationExcel() {
	const fileInput = $('#reservationExcelFile')[0];

	if (!fileInput.files || fileInput.files.length === 0) {
		showToast('엑셀 파일을 선택해주세요.', 'warning');
		return;
	}

	const file = fileInput.files[0];
	const fileExtension = file.name.split('.').pop().toLowerCase();

	if (fileExtension !== 'xlsx' && fileExtension !== 'xls' && fileExtension !== 'csv') {
		showToast('엑셀 파일(.xlsx, .xls, .csv)만 업로드 가능합니다.', 'error');
		return;
	}

	showToast('파일을 처리하고 있습니다...', 'info');

	const reader = new FileReader();

	reader.onload = function(e) {
		try {
			let parsedData;

			if (fileExtension === 'csv') {
				parsedData = parseCSV(e.target.result);
			} else {
				parsedData = parseExcel(e.target.result);
			}

			if (parsedData.length === 0) {
				showToast('파일에 데이터가 없습니다.', 'error');
				return;
			}

			processReservationData(parsedData);

		} catch (error) {
			console.error('파일 처리 오류:', error);
			showToast('파일 처리 중 오류가 발생했습니다.', 'error');
		}
	};

	reader.onerror = function() {
		showToast('파일 읽기에 실패했습니다.', 'error');
	};

	if (fileExtension === 'csv') {
		reader.readAsText(file);
	} else {
		reader.readAsArrayBuffer(file);
	}
}

/**
 * 역할: CSV 파일 파싱
 */
function parseCSV(csvText) {
	const lines = csvText.split('\n');
	const result = [];

	// 헤더 제거 (첫 번째 줄)
	for (let i = 1; i < lines.length; i++) {
		const line = lines[i].trim();
		if (!line) continue;

		const values = line.split(',').map(v => v.trim().replace(/^"|"$/g, ''));

		if (values.length >= 4) {
			result.push({
				scheduled_time: values[0],
				member_name: values[1],
				member_phone: values[2],
				message_content: values[3]
			});
		}
	}

	return result;
}

/**
 * 역할: Excel 파일 파싱 (XLSX)
 */
function parseExcel(arrayBuffer) {
	const data = new Uint8Array(arrayBuffer);
	const workbook = XLSX.read(data, { type: 'array' });
	const firstSheetName = workbook.SheetNames[0];
	const worksheet = workbook.Sheets[firstSheetName];
	const jsonData = XLSX.utils.sheet_to_json(worksheet, { header: 1 });

	const result = [];

	// 헤더 제거 (첫 번째 줄)
	for (let i = 1; i < jsonData.length; i++) {
		const row = jsonData[i];

		if (row.length >= 4 && row[0] && row[1] && row[2] && row[3]) {
			result.push({
				scheduled_time: row[0],
				member_name: row[1],
				member_phone: String(row[2]).replace(/[^0-9-]/g, ''),
				message_content: row[3]
			});
		}
	}

	return result;
}

/**
 * 역할: 예약발송 데이터 검증 및 저장
 */
function processReservationData(data) {
	const now = new Date();
	const phoneNumbers = new Set();
	const errors = [];
	const validData = [];

	data.forEach(function(item, index) {
		const rowNum = index + 2; // 엑셀 행 번호 (헤더 포함)

		// 발송예정일시 검증
		const scheduledTime = new Date(item.scheduled_time);

		if (isNaN(scheduledTime.getTime())) {
			errors.push(`${rowNum}행: 발송예정일시 형식이 올바르지 않습니다.`);
			return;
		}

		if (scheduledTime <= now) {
			errors.push(`${rowNum}행: 발송예정일시가 현재시간보다 과거입니다.`);
			return;
		}


		// 휴대폰번호 형식 검증
		const phonePattern = /^01[0-9]-?\d{3,4}-?\d{4}$/;
		if (!phonePattern.test(item.member_phone)) {
			errors.push(`${rowNum}행: 휴대폰번호 형식이 올바르지 않습니다.`);
			return;
		}

		// 이름 검증
		if (!item.member_name || item.member_name.trim() === '') {
			errors.push(`${rowNum}행: 이름이 비어있습니다.`);
			return;
		}

		// 메시지 검증
		if (!item.message_content || item.message_content.trim() === '') {
			errors.push(`${rowNum}행: 메시지가 비어있습니다.`);
			return;
		}

		phoneNumbers.add(item.member_phone);
		validData.push(item);
	});

	if (errors.length > 0) {
		const errorMessage = '다음 오류를 수정해주세요:\n\n' + errors.slice(0, 5).join('\n');
		if (errors.length > 5) {
			showToast(errorMessage + `\n\n외 ${errors.length - 5}건`, 'error');
		} else {
			showToast(errorMessage, 'error');
		}
		return;
	}

	if (validData.length === 0) {
		showToast('처리할 데이터가 없습니다.', 'warning');
		return;
	}

	// 서버로 데이터 전송
	saveReservationBatch(validData);
}

/**
 * 역할: 예약발송 일괄 저장
 */
function saveReservationBatch(reservationList) {
	const senderNumber = $('#senderSelect').val();
	const senderName = $('#senderSelect option:selected').data('name');

	if (!senderNumber) {
		showToast('발신번호를 선택해주세요.', 'warning');
		return;
	}

	showConfirmModal(
		'예약발송 일괄 등록',
		`${reservationList.length}건의 예약발송을 등록하시겠습니까?`,
		function() {
			$.ajax({
				url: '/send/save_reservation_batch',
				type: 'POST',
				data: {
					org_id: SEND_ORG_ID,
					sender_number: senderNumber,
					sender_name: senderName,
					reservation_list: reservationList
				},
				dataType: 'json',
				beforeSend: function() {
					$('#btnUploadReservationExcel').prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> 처리중...');
				},
				success: function(response) {
					if (response.success) {
						showToast(response.message, 'success');

						// Offcanvas 닫기
						const offcanvasEl = document.getElementById('excelReservationOffcanvas');
						const offcanvas = bootstrap.Offcanvas.getInstance(offcanvasEl);
						if (offcanvas) {
							offcanvas.hide();
						}

						// 파일 입력 초기화
						$('#reservationExcelFile').val('');

						// 예약발송 목록 새로고침
						$('#reservation-tab').tab('show');
						loadReservationList();
					} else {
						showToast(response.message, 'error');
					}
				},
				error: function() {
					showToast('예약발송 등록 중 오류가 발생했습니다.', 'error');
				},
				complete: function() {
					$('#btnUploadReservationExcel').prop('disabled', false).html('<i class="bi bi-upload"></i> 저장');
				}
			});
		}
	);
}
