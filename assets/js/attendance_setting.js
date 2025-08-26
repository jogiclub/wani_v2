/**
 * 파일 위치: assets/js/attendance_setting.js
 * 역할: 출석설정 페이지의 JavaScript 기능 처리 (드래그앤드롭 기능 포함)
 */

$(document).ready(function() {

	// 드래그앤드롭 sortable 초기화
	initSortable();

	// 순서 저장 버튼 클릭 이벤트
	$('#saveOrderBtn').on('click', function() {
		saveAttendanceTypeOrder();
	});

	// 출석타입 추가 폼 제출
	$('#addAttendanceTypeForm').on('submit', function(e) {
		e.preventDefault();

		var formData = {
			att_type_name: $('#att_type_name').val().trim(),
			att_type_nickname: $('#att_type_nickname').val().trim(),
			att_type_point: $('#att_type_point').val(),
			att_type_input: $('#att_type_input').val(),
			att_type_color: $('#att_type_color').val()
		};

		if (!formData.att_type_name) {
			showToast('출석타입명을 입력해주세요.');
			$('#att_type_name').focus();
			return;
		}

		if (!formData.att_type_nickname) {
			showToast('별칭을 입력해주세요.');
			$('#att_type_nickname').focus();
			return;
		}

		const submitBtn = $(this).find('button[type="submit"]');
		const originalText = submitBtn.html();
		submitBtn.prop('disabled', true).html('<i class="bi bi-hourglass-split"></i> 추가 중...');

		$.ajax({
			url: 'attendance_setting/add_attendance_type',
			type: 'POST',
			data: formData,
			dataType: 'json',
			success: function(response) {
				if (response.success) {
					showToast(response.message);
					$('#addAttendanceTypeModal').modal('hide');
					location.reload();
				} else {
					showToast(response.message);
				}
			},
			error: function() {
				showToast('출석타입 추가 중 오류가 발생했습니다.');
			},
			complete: function() {
				submitBtn.prop('disabled', false).html(originalText);
			}
		});
	});

	// 출석타입 수정 폼 제출
	$('#editAttendanceTypeForm').on('submit', function(e) {
		e.preventDefault();

		var formData = {
			att_type_idx: $('#edit_att_type_idx').val(),
			att_type_name: $('#edit_att_type_name').val().trim(),
			att_type_nickname: $('#edit_att_type_nickname').val().trim(),
			att_type_point: $('#edit_att_type_point').val(),
			att_type_input: $('#edit_att_type_input').val(),
			att_type_color: $('#edit_att_type_color').val()
		};

		if (!formData.att_type_name) {
			showToast('출석타입명을 입력해주세요.');
			$('#edit_att_type_name').focus();
			return;
		}

		if (!formData.att_type_nickname) {
			showToast('별칭을 입력해주세요.');
			$('#edit_att_type_nickname').focus();
			return;
		}

		const submitBtn = $(this).find('button[type="submit"]');
		const originalText = submitBtn.html();
		submitBtn.prop('disabled', true).html('<i class="bi bi-hourglass-split"></i> 수정 중...');

		$.ajax({
			url: 'attendance_setting/update_attendance_type',
			type: 'POST',
			data: formData,
			dataType: 'json',
			success: function(response) {
				if (response.success) {
					showToast(response.message);
					$('#editAttendanceTypeModal').modal('hide');
					location.reload();
				} else {
					showToast(response.message);
				}
			},
			error: function() {
				showToast('출석타입 수정 중 오류가 발생했습니다.');
			},
			complete: function() {
				submitBtn.prop('disabled', false).html(originalText);
			}
		});
	});

	// 출석타입 수정 버튼 클릭 이벤트
	$(document).on('click', '.edit-attendance-type-btn', function() {
		var attTypeIdx = $(this).data('att-type-idx');
		var attTypeName = $(this).data('att-type-name');
		var attTypeNickname = $(this).data('att-type-nickname');
		var attTypePoint = $(this).data('att-type-point');
		var attTypeInput = $(this).data('att-type-input');
		var attTypeColor = $(this).data('att-type-color');

		$('#edit_att_type_idx').val(attTypeIdx);
		$('#edit_att_type_name').val(attTypeName);
		$('#edit_att_type_nickname').val(attTypeNickname);
		$('#edit_att_type_point').val(attTypePoint);
		$('#edit_att_type_input').val(attTypeInput);
		$('#edit_att_type_color').val('#' + attTypeColor);

		$('#editAttendanceTypeModal').modal('show');
	});

	// 출석타입 삭제 버튼 클릭 이벤트
	$(document).on('click', '.delete-attendance-type-btn', function() {
		var attTypeIdx = $(this).data('att-type-idx');
		var attTypeName = $(this).data('att-type-name');

		showDeleteConfirmModal(attTypeName, attTypeIdx);
	});
});

/**
 * 드래그앤드롭 sortable 초기화 함수
 */
function initSortable() {
	if ($('.sortable-tbody').length > 0) {
		$('.sortable-tbody').sortable({
			handle: '.handle',
			placeholder: 'ui-sortable-placeholder',
			helper: 'clone',
			axis: 'y',
			containment: 'parent',
			tolerance: 'pointer',
			start: function(e, ui) {
				ui.placeholder.height(ui.helper.outerHeight());
				ui.helper.addClass('ui-sortable-helper');
			},
			update: function(e, ui) {
				updateOrderNumbers();
				$('#saveOrderBtn').show().addClass('btn-warning').removeClass('btn-primary');
			},
			stop: function(e, ui) {
				ui.item.removeClass('ui-sortable-helper');
			}
		});

		$('.sortable-tbody').disableSelection();
	}
}

/**
 * 순서 번호 업데이트 함수
 */
function updateOrderNumbers() {
	$('.sortable-tbody .sortable-row').each(function(index) {
		$(this).find('.order-number').text(index + 1);
	});
}

/**
 * 출석타입 순서 저장 함수
 */
function saveAttendanceTypeOrder() {
	var orders = [];

	$('.sortable-tbody .sortable-row').each(function(index) {
		var attTypeIdx = $(this).data('att-type-idx');
		orders.push({
			att_type_idx: attTypeIdx,
			att_type_order: index + 1
		});
	});

	if (orders.length === 0) {
		showToast('저장할 순서 정보가 없습니다.');
		return;
	}

	const saveBtn = $('#saveOrderBtn');
	const originalText = saveBtn.html();
	saveBtn.prop('disabled', true).html('<i class="bi bi-hourglass-split"></i> 저장 중...');

	$.ajax({
		url: 'attendance_setting/update_orders',
		type: 'POST',
		data: { orders: orders },
		dataType: 'json',
		success: function(response) {
			if (response.success) {
				showToast('순서가 저장되었습니다.');
				$('#saveOrderBtn').hide().removeClass('btn-warning').addClass('btn-primary');
			} else {
				showToast(response.message || '순서 저장에 실패했습니다.');
			}
		},
		error: function() {
			showToast('순서 저장 중 오류가 발생했습니다.');
		},
		complete: function() {
			saveBtn.prop('disabled', false).html(originalText);
		}
	});
}

/**
 * 삭제 컨펌 모달 표시 함수
 */
function showDeleteConfirmModal(attTypeName, attTypeIdx) {
	// 모달이 이미 있으면 제거
	if ($('#deleteConfirmModal').length) {
		$('#deleteConfirmModal').remove();
	}

	// 동적으로 모달 생성
	const modalHtml = `
		<div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-labelledby="deleteConfirmModalLabel" aria-hidden="true">
			<div class="modal-dialog">
				<div class="modal-content">
					<div class="modal-header">
						<h5 class="modal-title text-danger" id="deleteConfirmModalLabel">
							<i class="bi bi-exclamation-triangle"></i> 출석타입 삭제 확인
						</h5>
						<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
					</div>
					<div class="modal-body">
						<p class="mb-3">
							<strong>'${attTypeName}'</strong> 출석타입을 정말 삭제하시겠습니까?
						</p>
						<div class="alert alert-warning">
							<i class="bi bi-info-circle"></i> 삭제된 출석타입은 복구할 수 없습니다. 해당 출석타입을 사용하는 출석 기록이 있으면 삭제할 수 없습니다.
						</div>
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
						<button type="button" class="btn btn-danger" id="confirmDeleteBtn">
							<i class="bi bi-trash"></i> 삭제
						</button>
					</div>
				</div>
			</div>
		</div>
	`;

	// 모달을 body에 추가
	$('body').append(modalHtml);

	// 삭제 확인 버튼 클릭 이벤트
	$('#confirmDeleteBtn').on('click', function() {
		$('#deleteConfirmModal').modal('hide');
		deleteAttendanceType(attTypeIdx);
	});

	// 모달 표시
	$('#deleteConfirmModal').modal('show');

	// 모달이 완전히 숨겨진 후 DOM에서 제거
	$('#deleteConfirmModal').on('hidden.bs.modal', function() {
		$(this).remove();
	});
}

/**
 * 출석타입 삭제 함수
 */
function deleteAttendanceType(attTypeIdx) {
	$.ajax({
		url: 'attendance_setting/delete_attendance_type',
		type: 'POST',
		data: { att_type_idx: attTypeIdx },
		dataType: 'json',
		success: function(response) {
			if (response.success) {
				showToast('출석타입이 성공적으로 삭제되었습니다.');
				// 삭제된 행을 부드럽게 제거
				$('tr[data-att-type-idx="' + attTypeIdx + '"]').fadeOut(300, function() {
					$(this).remove();
					updateOrderNumbers();
				});
			} else {
				showToast(response.message || '출석타입 삭제에 실패했습니다.');
			}
		},
		error: function() {
			showToast('출석타입 삭제 중 오류가 발생했습니다.');
		}
	});
}

/**
 * Toast 메시지 표시 함수
 */
function showToast(message, header = '알림') {
	// 1. heyToast 함수가 있으면 사용 (common.js)
	if (typeof heyToast === 'function') {
		heyToast(message, header);
		return;
	}

	// 2. 출석설정 페이지 전용 toast 사용
	if ($('#attendanceSettingToast').length > 0) {
		$('#attendanceSettingToast .toast-header strong').text(header);
		$('#attendanceSettingToast .toast-body').text(message);
		const toast = new bootstrap.Toast($('#attendanceSettingToast')[0]);
		toast.show();
		return;
	}

	// 3. 일반적인 liveToast 사용
	if ($('#liveToast').length > 0) {
		$('#liveToast .toast-header strong').text(header);
		$('#liveToast .toast-body').text(message);
		const toast = new bootstrap.Toast($('#liveToast')[0]);
		toast.show();
		return;
	}

	// 4. 동적으로 toast 생성
	createAndShowToast(message, header);
}

/**
 * 동적 Toast 생성 및 표시
 */
function createAndShowToast(message, header = '알림') {
	// Toast 컨테이너가 없으면 생성
	if ($('.toast-container').length === 0) {
		$('body').append('<div class="toast-container position-fixed bottom-0 end-0 p-3"></div>');
	}

	// Toast 요소 생성
	const toastHtml = `
		<div class="toast" role="alert" aria-live="assertive" aria-atomic="true">
			<div class="toast-header">
				<i class="bi bi-info-circle-fill text-primary me-2"></i>
				<strong class="me-auto">${header}</strong>
				<button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
			</div>
			<div class="toast-body">${message}</div>
		</div>
	`;

	const $toast = $(toastHtml);
	$('.toast-container').append($toast);

	// Toast 표시
	const toast = new bootstrap.Toast($toast[0], {
		autohide: true,
		delay: 3000
	});
	toast.show();

	// Toast가 숨겨진 후 DOM에서 제거
	$toast.on('hidden.bs.toast', function() {
		$(this).remove();
	});
}
