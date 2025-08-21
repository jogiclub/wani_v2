/**
 * 파일 위치: E:\SynologyDrive\Example\wani\assets\js\detail_field.js
 * 역할: 상세필드 설정 페이지의 JavaScript 기능 처리
 */

$(document).ready(function() {

	// 필드 타입 변경 시 옵션 영역 표시/숨김
	$('#field_type, #edit_field_type').change(function() {
		var selectedType = $(this).val();
		var isEdit = $(this).attr('id') === 'edit_field_type';
		var targetDiv = isEdit ? '#editSelectOptionsDiv' : '#selectOptionsDiv';

		if (selectedType === 'select') {
			$(targetDiv).show();
		} else {
			$(targetDiv).hide();
		}
	});

	// 필드 추가 폼 제출
	$('#addFieldForm').on('submit', function(e) {
		e.preventDefault();

		var formData = {
			field_name: $('#field_name').val().trim(),
			field_type: $('#field_type').val(),
			field_settings: {}
		};

		// 선택박스 타입인 경우 옵션 설정
		if (formData.field_type === 'select') {
			var options = $('#select_options').val().trim();
			if (options) {
				formData.field_settings = {
					options: options.split('\n').filter(function(option) {
						return option.trim() !== '';
					})
				};
			}
		}

		$.ajax({
			url: 'detail_field/add_field',
			type: 'POST',
			data: formData,
			dataType: 'json',
			success: function(response) {
				if (response.success) {
					showAlert('success', response.message);
					$('#addFieldModal').modal('hide');
					location.reload();
				} else {
					showAlert('danger', response.message);
				}
			},
			error: function() {
				showAlert('danger', '필드 추가 중 오류가 발생했습니다.');
			}
		});
	});

	// 필드 수정 버튼 클릭
	$('.edit-field-btn').on('click', function() {
		var fieldIdx = $(this).data('field-idx');
		var fieldName = $(this).data('field-name');
		var fieldType = $(this).data('field-type');
		var fieldSettings = $(this).data('field-settings');

		$('#edit_field_idx').val(fieldIdx);
		$('#edit_field_name').val(fieldName);
		$('#edit_field_type').val(fieldType).trigger('change');

		// 설정 정보 파싱
		try {
			var settings = JSON.parse(fieldSettings);
			if (fieldType === 'select' && settings.options) {
				$('#edit_select_options').val(settings.options.join('\n'));
			}
		} catch (e) {
			$('#edit_select_options').val('');
		}

		$('#editFieldModal').modal('show');
	});

	// 필드 수정 폼 제출
	$('#editFieldForm').on('submit', function(e) {
		e.preventDefault();

		var formData = {
			field_idx: $('#edit_field_idx').val(),
			field_name: $('#edit_field_name').val().trim(),
			field_type: $('#edit_field_type').val(),
			field_settings: {}
		};

		// 선택박스 타입인 경우 옵션 설정
		if (formData.field_type === 'select') {
			var options = $('#edit_select_options').val().trim();
			if (options) {
				formData.field_settings = {
					options: options.split('\n').filter(function(option) {
						return option.trim() !== '';
					})
				};
			}
		}

		$.ajax({
			url: 'detail_field/update_field',
			type: 'POST',
			data: formData,
			dataType: 'json',
			success: function(response) {
				if (response.success) {
					showAlert('success', response.message);
					$('#editFieldModal').modal('hide');
					location.reload();
				} else {
					showAlert('danger', response.message);
				}
			},
			error: function() {
				showAlert('danger', '필드 수정 중 오류가 발생했습니다.');
			}
		});
	});

	// 필드 삭제 버튼 클릭
	$('.delete-field-btn').on('click', function() {
		var fieldIdx = $(this).data('field-idx');
		var fieldName = $(this).data('field-name');

		if (confirm('"' + fieldName + '" 필드를 정말 삭제하시겠습니까?\n\n이 작업은 되돌릴 수 없습니다.')) {
			$.ajax({
				url: 'detail_field/delete_field',
				type: 'POST',
				data: { field_idx: fieldIdx },
				dataType: 'json',
				success: function(response) {
					if (response.success) {
						showAlert('success', response.message);
						location.reload();
					} else {
						showAlert('danger', response.message);
					}
				},
				error: function() {
					showAlert('danger', '필드 삭제 중 오류가 발생했습니다.');
				}
			});
		}
	});

	// 필드 활성화/비활성화 토글
	$('.toggle-field').on('change', function() {
		var fieldIdx = $(this).data('field-idx');

		$.ajax({
			url: 'detail_field/toggle_field',
			type: 'POST',
			data: { field_idx: fieldIdx },
			dataType: 'json',
			success: function(response) {
				if (response.success) {
					showAlert('success', response.message);
				} else {
					showAlert('danger', response.message);
					// 실패 시 체크박스 상태 되돌리기
					$('.toggle-field[data-field-idx="' + fieldIdx + '"]').prop('checked',
						!$('.toggle-field[data-field-idx="' + fieldIdx + '"]').prop('checked'));
				}
			},
			error: function() {
				showAlert('danger', '필드 상태 변경 중 오류가 발생했습니다.');
				// 실패 시 체크박스 상태 되돌리기
				$('.toggle-field[data-field-idx="' + fieldIdx + '"]').prop('checked',
					!$('.toggle-field[data-field-idx="' + fieldIdx + '"]').prop('checked'));
			}
		});
	});

	// 정렬 기능 초기화
	if (typeof Sortable !== 'undefined' && $('#fieldTableBody').length > 0) {
		var sortable = Sortable.create(document.getElementById('fieldTableBody'), {
			handle: '.handle',
			animation: 150,
			onEnd: function(evt) {
				updateFieldOrders();
			}
		});
	}

	// 모달 초기화 이벤트
	$('#addFieldModal').on('hidden.bs.modal', function() {
		$('#addFieldForm')[0].reset();
		$('#selectOptionsDiv').hide();
	});

	$('#editFieldModal').on('hidden.bs.modal', function() {
		$('#editFieldForm')[0].reset();
		$('#editSelectOptionsDiv').hide();
	});

	/**
	 * 필드 순서 업데이트
	 */
	function updateFieldOrders() {
		var orders = {};
		var orderNumber = 1;

		$('#fieldTableBody tr').each(function() {
			var fieldIdx = $(this).data('field-idx');
			orders[fieldIdx] = orderNumber++;
			$(this).find('.order-number').text(orderNumber - 1);
		});

		$.ajax({
			url: 'detail_field/update_orders',
			type: 'POST',
			data: { orders: orders },
			dataType: 'json',
			success: function(response) {
				if (response.success) {
					showAlert('success', response.message);
				} else {
					showAlert('danger', response.message);
					location.reload(); // 실패 시 페이지 새로고침
				}
			},
			error: function() {
				showAlert('danger', '순서 변경 중 오류가 발생했습니다.');
				location.reload(); // 실패 시 페이지 새로고침
			}
		});
	}

	/**
	 * 알림 메시지 표시
	 */
	function showAlert(type, message) {
		// 기존 알림 제거
		$('.alert-custom').remove();

		var alertHtml = '<div class="alert alert-' + type + ' alert-dismissible fade show alert-custom" role="alert">' +
			message +
			'<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>' +
			'</div>';

		$('.container').prepend(alertHtml);

		// 5초 후 자동 제거
		setTimeout(function() {
			$('.alert-custom').fadeOut(function() {
				$(this).remove();
			});
		}, 5000);
	}

	/**
	 * 폼 유효성 검사
	 */
	function validateForm(formSelector) {
		var isValid = true;

		$(formSelector + ' [required]').each(function() {
			if ($(this).val().trim() === '') {
				$(this).addClass('is-invalid');
				isValid = false;
			} else {
				$(this).removeClass('is-invalid');
			}
		});

		return isValid;
	}

	// 입력 필드 실시간 유효성 검사
	$('input[required], select[required]').on('input change', function() {
		if ($(this).val().trim() !== '') {
			$(this).removeClass('is-invalid');
		}
	});
});
