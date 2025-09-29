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
		$('#chargeModal').modal('show');
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
 * 패키지 목록 렌더링
 */
function renderPackageList(packages) {
	const tbody = $('#packageList');
	tbody.empty();

	packages.forEach(function(pkg) {
		const row = `
			<tr>
				<td>${formatNumber(pkg.package_amount)}원</td>
				<td>${pkg.sms_price}원</td>
				<td>${pkg.lms_price}원</td>
				<td>${pkg.mms_price}원</td>
				<td>${pkg.kakao_price}원</td>
				<td>
					<button type="button" class="btn btn-sm btn-outline-primary btn-select-package"
							data-package-idx="${pkg.package_idx}"
							data-amount="${pkg.package_amount}">
						선택
					</button>
				</td>
			</tr>
		`;
		tbody.append(row);
	});

	// 선택 버튼 이벤트
	$('.btn-select-package').on('click', function() {
		$('.btn-select-package').removeClass('btn-primary').addClass('btn-outline-primary');
		$(this).removeClass('btn-outline-primary').addClass('btn-primary');

		selectedPackage = {
			package_idx: $(this).data('package-idx'),
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
						$('#chargeModal').modal('hide');

						// 선택 초기화
						selectedPackage = null;
						$('#selectedAmount').text('0원');
						$('.btn-select-package').removeClass('btn-primary').addClass('btn-outline-primary');
					} else {
						showToast(response.message, 'error');
					}
				},
				error: function(xhr, status, error) {
					showToast('충전 처리 중 오류가 발생했습니다.', 'error');
					console.error('충전 실패:', error);
				},
				complete: function() {
					$('#btnCharge').prop('disabled', false).html('결제하기');
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
