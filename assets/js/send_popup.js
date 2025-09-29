/**
 * 파일 위치: assets/js/send_popup.js
 * 역할: 문자 발송 팝업의 충전 기능 처리
 */

let selectedPackage = null;

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
});

/**
 * 조직 잔액 조회
 */
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

/**
 * 잔액 표시 업데이트
 */
function updateBalanceDisplay(balance) {
	$('#currentBalance').text(formatNumber(balance) + '원');
}

/**
 * 충전 패키지 목록 조회
 */
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

/**
 * 패키지 목록 렌더링 (Offcanvas용)
 */
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

	// 선택 이벤트
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
 * 충전 처리
 */
function processCharge() {
	showConfirmModal(
		'문자 충전',
		formatNumber(selectedPackage.amount) + '원을 충전하시겠습니까?',
		function() {
			// 확인 클릭 시
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

						// Offcanvas 닫기
						const offcanvasEl = document.getElementById('chargeOffcanvas');
						const offcanvas = bootstrap.Offcanvas.getInstance(offcanvasEl);
						if (offcanvas) {
							offcanvas.hide();
						}

						// 선택 초기화
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

/**
 * 숫자 포맷팅
 */
function formatNumber(num) {
	return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}


/**
 * 확인 모달 표시
 */
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
 * 발신번호 관리 버튼 클릭
 */
$('#btnAddSender').on('click', function() {
	loadSenderList();
	const offcanvas = new bootstrap.Offcanvas(document.getElementById('senderManageOffcanvas'));
	offcanvas.show();
});

/**
 * 발신번호 추가 모달 버튼 클릭
 */
$(document).on('click', '#btnAddSenderModal', function() {
	// 입력 필드 초기화
	$('#newSenderName').val('');
	$('#newSenderNumber').val('');

	// 모달 표시
	const modal = new bootstrap.Modal(document.getElementById('addSenderModal'));
	modal.show();
});

/**
 * 발신번호 목록 조회
 */
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

/**
 * 발신번호 목록 렌더링
 */
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

	// 인증 버튼 이벤트
	$('.btn-auth-sender').on('click', function() {
		const senderIdx = $(this).data('sender-idx');
		const senderNumber = $(this).data('sender-number');
		requestAuthCode(senderIdx, senderNumber);
	});

	// 삭제 버튼 이벤트
	$('.btn-delete-sender').on('click', function() {
		const senderIdx = $(this).data('sender-idx');
		deleteSender(senderIdx);
	});
}

/**
 * 발신번호 저장
 */
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

				// 모달 닫기
				$('#addSenderModal').modal('hide');

				// 입력 필드 초기화
				$('#newSenderName').val('');
				$('#newSenderNumber').val('');

				// offcanvas 목록 새로고침
				loadSenderList();

				// 발신번호 선택박스 새로고침
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


let authTimer = null;
let authTimeLeft = 120;

/**
 * 인증번호 요청
 */
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
				// 임시로 alert로 인증번호 표시
				alert('인증번호: ' + response.auth_code);

				// 인증번호 입력 모달 표시
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

/**
 * 인증번호 입력 모달 표시
 */
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

		// 모달이 닫힐 때 타이머 정리
		authModal.on('hidden.bs.modal', function() {
			if (authTimer) {
				clearInterval(authTimer);
				authTimer = null;
			}
		});
	}

	$('#authCodeMessage').html(`입력한 발신번호(${senderNumber})로 6자리 인증번호를 전송하였습니다.<br>2분 안에 인증번호를 입력해주세요!`);
	$('#authCodeInput').val('');

	// 타이머 초기화 및 시작
	authTimeLeft = 120;
	$('#authTimer').text('02:00');

	// 기존 타이머가 있으면 정리
	if (authTimer) {
		clearInterval(authTimer);
	}

	// 타이머 시작
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


/**
 * 인증번호 확인
 */
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
				// 타이머 정리
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

/**
 * 발신번호 삭제
 */
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

/**
 * 발신번호 셀렉트 옵션 새로고침
 */
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

				// 인증된 발신번호만 필터링
				const verifiedSenders = response.senders.filter(function(sender) {
					return sender.auth_status === 'verified';
				});

				select.empty();

				if (verifiedSenders.length === 0) {
					// 인증된 발신번호가 없는 경우
					select.append('<option value="">인증된 발신번호가 없습니다. 발신번호 추가를 진행해주세요!</option>');
					select.prop('disabled', true);
				} else {
					// 인증된 발신번호가 있는 경우
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
 * 발신번호 저장 버튼 이벤트
 */
$(document).on('click', '#btnSaveSender', function() {
	saveSender();
});
