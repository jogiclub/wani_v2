/**
 * 파일 위치: assets/js/detail_field.js
 * 역할: 상세필드 설정 페이지의 JavaScript 기능 처리 (드래그앤드롭 기능 포함)
 */

$(document).ready(function() {

	// 드래그앤드롭 sortable 초기화
	initSortable();

	// 기존 필드 타입 변경 이벤트
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

	// 순서 저장 버튼 클릭 이벤트
	$('#saveOrderBtn').on('click', function() {
		saveFieldOrder();
	});

	// 기존 필드 추가 폼 제출 이벤트
	$('#addFieldForm').on('submit', function(e) {
		e.preventDefault();

		var formData = {
			field_name: $('#field_name').val().trim(),
			field_type: $('#field_type').val(),
			field_settings: {}
		};

		if (!formData.field_name) {
			showToast('필드명을 입력해주세요.');
			$('#field_name').focus();
			return;
		}

		if (!formData.field_type) {
			showToast('필드 타입을 선택해주세요.');
			$('#field_type').focus();
			return;
		}

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

		const submitBtn = $(this).find('button[type="submit"]');
		const originalText = submitBtn.html();
		submitBtn.prop('disabled', true).html('<i class="bi bi-hourglass-split"></i> 추가 중...');

		$.ajax({
			url: 'detail_field/add_field',
			type: 'POST',
			data: formData,
			dataType: 'json',
			success: function(response) {
				if (response.success) {
					showToast(response.message);
					$('#addFieldModal').modal('hide');
					location.reload();
				} else {
					showToast(response.message);
				}
			},
			error: function() {
				showToast('필드 추가 중 오류가 발생했습니다.');
			},
			complete: function() {
				submitBtn.prop('disabled', false).html(originalText);
			}
		});
	});

	// 기존 필드 수정 폼 제출 이벤트
	$('#editFieldForm').on('submit', function(e) {
		e.preventDefault();

		var formData = {
			field_idx: $('#edit_field_idx').val(),
			field_name: $('#edit_field_name').val().trim(),
			field_type: $('#edit_field_type').val(),
			field_settings: {}
		};

		if (!formData.field_name) {
			showToast('필드명을 입력해주세요.');
			$('#edit_field_name').focus();
			return;
		}

		if (!formData.field_type) {
			showToast('필드 타입을 선택해주세요.');
			$('#edit_field_type').focus();
			return;
		}

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

		const submitBtn = $(this).find('button[type="submit"]');
		const originalText = submitBtn.html();
		submitBtn.prop('disabled', true).html('<i class="bi bi-hourglass-split"></i> 수정 중...');

		$.ajax({
			url: 'detail_field/update_field',
			type: 'POST',
			data: formData,
			dataType: 'json',
			success: function(response) {
				if (response.success) {
					showToast(response.message);
					$('#editFieldModal').modal('hide');
					location.reload();
				} else {
					showToast(response.message);
				}
			},
			error: function() {
				showToast('필드 수정 중 오류가 발생했습니다.');
			},
			complete: function() {
				submitBtn.prop('disabled', false).html(originalText);
			}
		});
	});

	// 기존 필드 수정 버튼 클릭 이벤트
	$(document).on('click', '.edit-field-btn', function() {
		var fieldIdx = $(this).data('field-idx');
		var fieldName = $(this).data('field-name');
		var fieldType = $(this).data('field-type');
		var fieldSettings = $(this).data('field-settings');

		$('#edit_field_idx').val(fieldIdx);
		$('#edit_field_name').val(fieldName);
		$('#edit_field_type').val(fieldType);

		if (fieldType === 'select' && fieldSettings) {
			try {
				var settings = JSON.parse(fieldSettings);
				if (settings.options) {
					$('#edit_select_options').val(settings.options.join('\n'));
					$('#editSelectOptionsDiv').show();
				}
			} catch (e) {
				console.error('Failed to parse field settings:', e);
			}
		} else {
			$('#editSelectOptionsDiv').hide();
		}

		$('#editFieldModal').modal('show');
	});

	// 기존 필드 삭제 버튼 클릭 이벤트
	$(document).on('click', '.delete-field-btn', function() {
		var fieldIdx = $(this).data('field-idx');
		var fieldName = $(this).data('field-name');

		showDeleteConfirmModal(fieldName, fieldIdx);
	});

	// 기존 필드 활성화/비활성화 토글 이벤트
	$(document).on('change', '.toggle-field', function() {
		var fieldIdx = $(this).data('field-idx');
		toggleFieldActive(fieldIdx);
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
 * 필드 순서 저장 함수
 */
function saveFieldOrder() {
	var orders = [];

	$('.sortable-tbody .sortable-row').each(function(index) {
		var fieldIdx = $(this).data('field-idx');
		orders.push({
			field_idx: fieldIdx,
			display_order: index + 1
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
		url: 'detail_field/update_orders',
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
function showDeleteConfirmModal(fieldName, fieldIdx) {
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
							<i class="bi bi-exclamation-triangle"></i> 필드 삭제 확인
						</h5>
						<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
					</div>
					<div class="modal-body">
						<p class="mb-3">
							<strong>'${fieldName}'</strong> 필드를 정말 삭제하시겠습니까?
						</p>
						<div class="alert alert-warning">
							<i class="bi bi-info-circle"></i> 삭제된 필드는 복구할 수 없습니다.
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
		deleteField(fieldIdx);
	});

	// 모달 표시
	$('#deleteConfirmModal').modal('show');

	// 모달이 완전히 숨겨진 후 DOM에서 제거
	$('#deleteConfirmModal').on('hidden.bs.modal', function() {
		$(this).remove();
	});
}
function deleteField(fieldIdx) {
	$.ajax({
		url: 'detail_field/delete_field',
		type: 'POST',
		data: { field_idx: fieldIdx },
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
			showToast('필드 삭제 중 오류가 발생했습니다.');
		}
	});
}

/**
 * Toast 메시지 표시 함수 (common.js의 showToast와 호환)
 */
function showToast(message, header = '알림') {
	// 1. heyToast 함수가 있으면 사용 (common.js)
	if (typeof heyToast === 'function') {
		heyToast(message, header);
		return;
	}

	// 2. 상세필드 페이지 전용 toast 사용
	if ($('#detailFieldToast').length > 0) {
		$('#detailFieldToast .toast-header strong').text(header);
		$('#detailFieldToast .toast-body').text(message);
		const toast = new bootstrap.Toast($('#detailFieldToast')[0]);
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
function toggleFieldActive(fieldIdx) {
	$.ajax({
		url: 'detail_field/toggle_field',
		type: 'POST',
		data: { field_idx: fieldIdx },
		dataType: 'json',
		success: function(response) {
			if (response.success) {
				showToast(response.message);
			} else {
				showToast(response.message);
			}
		},
		error: function() {
			showToast('필드 상태 변경 중 오류가 발생했습니다.');
		}
	});
}
