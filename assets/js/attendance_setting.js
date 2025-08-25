/**
 * 파일 위치: assets/js/attendance_setting.js
 * 역할: 출석설정 페이지의 JavaScript 기능 처리
 */

$(document).ready(function() {
	// 드래그 앤 드롭 정렬 기능 초기화
	initializeSortable();

	// 카테고리 선택 변경 이벤트
	$('#att_type_category_idx').change(function() {
		toggleNewCategoryField();
	});

	// 출석타입 추가 폼 제출
	$('#addAttendanceTypeForm').on('submit', function(e) {
		e.preventDefault();
		submitAttendanceTypeForm(this, 'add');
	});

	// 출석타입 수정 폼 제출
	$('#editAttendanceTypeForm').on('submit', function(e) {
		e.preventDefault();
		submitAttendanceTypeForm(this, 'edit');
	});

	// 출석타입 수정 모달 열기
	$(document).on('click', '.edit-attendance-type-btn', function() {
		openEditModal(this);
	});

	// 출석타입 삭제
	$(document).on('click', '.delete-attendance-type-btn', function() {
		deleteAttendanceType(this);
	});

	// 모달 닫힐 때 초기화
	$('#addAttendanceTypeModal, #editAttendanceTypeModal').on('hidden.bs.modal', function() {
		resetModalForm($(this));
	});
});

/**
 * 드래그 앤 드롭 정렬 기능 초기화
 */
function initializeSortable() {
	var sortableElement = document.getElementById('attendanceTypeTableBody');
	if (sortableElement) {
		Sortable.create(sortableElement, {
			handle: '.handle',
			animation: 150,
			onEnd: function(evt) {
				updateAttendanceTypeOrders();
			}
		});
	}
}

/**
 * 새 카테고리 필드 표시/숨김
 */
function toggleNewCategoryField() {
	var selectedCategory = $('#att_type_category_idx').val();
	var newCategoryDiv = $('#newCategoryDiv');

	if (selectedCategory === '') {
		newCategoryDiv.show();
		$('#att_type_category_name').prop('required', true);
	} else {
		newCategoryDiv.hide();
		$('#att_type_category_name').prop('required', false);
	}
}

/**
 * 출석타입 폼 제출 처리
 */
function submitAttendanceTypeForm(form, mode) {
	var formData = new FormData(form);
	var url = mode === 'add' ? 'attendance_setting/add_attendance_type' : 'attendance_setting/update_attendance_type';
	var submitBtn = $(form).find('button[type="submit"]');
	var originalText = submitBtn.html();

	// 필수 필드 검증
	var typeName = formData.get('att_type_name');
	var typeNickname = formData.get('att_type_nickname');

	if (!typeName || !typeName.trim()) {
		showToast('출석타입명을 입력해주세요.');
		$(form).find('[name="att_type_name"]').focus();
		return;
	}

	if (!typeNickname || !typeNickname.trim()) {
		showToast('출석타입 별칭을 입력해주세요.');
		$(form).find('[name="att_type_nickname"]').focus();
		return;
	}

	// 저장 중 상태 표시
	submitBtn.prop('disabled', true).html('<i class="bi bi-hourglass-split"></i> ' +
		(mode === 'add' ? '추가 중...' : '수정 중...'));

	$.ajax({
		url: url,
		type: 'POST',
		data: formData,
		processData: false,
		contentType: false,
		dataType: 'json',
		success: function(response) {
			if (response.success) {
				showToast(response.message);
				$(form).closest('.modal').modal('hide');
				location.reload();
			} else {
				showToast(response.message);
			}
		},
		error: function() {
			showToast(mode === 'add' ? '출석타입 추가 중 오류가 발생했습니다.' : '출석타입 수정 중 오류가 발생했습니다.');
		},
		complete: function() {
			submitBtn.prop('disabled', false).html(originalText);
		}
	});
}

/**
 * 출석타입 수정 모달 열기
 */
function openEditModal(button) {
	var $btn = $(button);
	var attTypeIdx = $btn.data('att-type-idx');
	var attTypeName = $btn.data('att-type-name');
	var attTypeNickname = $btn.data('att-type-nickname');
	var attTypePoint = $btn.data('att-type-point');
	var attTypeInput = $btn.data('att-type-input');
	var attTypeColor = $btn.data('att-type-color');

	// 모달 필드에 데이터 설정
	$('#edit_att_type_idx').val(attTypeIdx);
	$('#edit_att_type_name').val(attTypeName);
	$('#edit_att_type_nickname').val(attTypeNickname);
	$('#edit_att_type_point').val(attTypePoint || 0);
	$('#edit_att_type_input').val(attTypeInput || 'check');
	$('#edit_att_type_color').val('#' + (attTypeColor || 'CB3227'));

	// 모달 열기
	$('#editAttendanceTypeModal').modal('show');
}

/**
 * 출석타입 삭제
 */
function deleteAttendanceType(button) {
	var $btn = $(button);
	var attTypeIdx = $btn.data('att-type-idx');
	var attTypeName = $btn.data('att-type-name');

	// 확인 모달 표시
	if (typeof showConfirmModal === 'function') {
		showConfirmModal(
			'출석타입 삭제',
			`"${attTypeName}" 출석타입을 삭제하시겠습니까?\n\n삭제된 출석타입은 복구할 수 없습니다.`,
			function() {
				performDeleteAttendanceType(attTypeIdx);
			}
		);
	} else {
		// Fallback - 기본 confirm 사용
		if (confirm(`"${attTypeName}" 출석타입을 삭제하시겠습니까?\n\n삭제된 출석타입은 복구할 수 없습니다.`)) {
			performDeleteAttendanceType(attTypeIdx);
		}
	}
}

/**
 * 출석타입 삭제 실행
 */
function performDeleteAttendanceType(attTypeIdx) {
	$.ajax({
		url: 'attendance_setting/delete_attendance_type',
		type: 'POST',
		data: { att_type_idx: attTypeIdx },
		dataType: 'json',
		success: function(response) {
			if (response.success) {
				showToast(response.message);
				location.reload();
			} else {
				showToast(response.message);
			}
		},
		error: function() {
			showToast('출석타입 삭제 중 오류가 발생했습니다.');
		}
	});
}

/**
 * 출석타입 순서 업데이트
 */
function updateAttendanceTypeOrders() {
	var orders = [];
	$('#attendanceTypeTableBody tr').each(function(index) {
		var attTypeIdx = $(this).data('att-type-idx');
		if (attTypeIdx) {
			orders.push(attTypeIdx);
			// 순서 번호 표시 업데이트
			$(this).find('.order-number').text(index + 1);
		}
	});

	if (orders.length > 0) {
		$.ajax({
			url: 'attendance_setting/update_orders',
			type: 'POST',
			data: { orders: orders },
			dataType: 'json',
			success: function(response) {
				if (response.success) {
					showToast(response.message);
				} else {
					showToast(response.message);
					location.reload(); // 실패 시 페이지 새로고침으로 원복
				}
			},
			error: function() {
				showToast('순서 변경 중 오류가 발생했습니다.');
				location.reload();
			}
		});
	}
}

/**
 * 모달 폼 초기화
 */
function resetModalForm(modal) {
	var form = modal.find('form')[0];
	if (form) {
		form.reset();
	}

	// 색상 필드 기본값 설정
	if (modal.attr('id') === 'addAttendanceTypeModal') {
		$('#att_type_color').val('#CB3227');
		$('#att_type_point').val('0');
		$('#att_type_input').val('check');
		toggleNewCategoryField();
	}
}

/**
 * Toast 메시지 표시 (common.js의 함수 사용)
 */
function showToast(message) {
	if (typeof heyToast === 'function') {
		heyToast(message);
	} else if (typeof alert !== 'undefined') {
		alert(message);
	}
}
