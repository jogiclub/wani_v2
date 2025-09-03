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
			field_size: $('#field_size').val(),
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

		if (!formData.field_size) {
			showToast('필드 타입을 선택해주세요.');
			$('#field_size').focus();
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
			field_size: $('#edit_field_size').val(),
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
		if (!formData.field_size) {
			showToast('필드 사이즈를 선택해주세요.');
			$('#edit_field_size').focus();
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
		var fieldSize = $(this).data('field-size');
		var fieldSettings = $(this).attr('data-field-settings'); // .data() 대신 .attr() 사용

		console.log('fieldSettings:', fieldSettings); // 디버깅용
		console.log('fieldType:', fieldType); // 디버깅용

		$('#edit_field_idx').val(fieldIdx);
		$('#edit_field_name').val(fieldName);
		$('#edit_field_type').val(fieldType);
		$('#edit_field_size').val(fieldSize);

		// select type 필드인 경우 옵션 영역을 먼저 보여주고 값을 설정
		if (fieldType === 'select') {
			$('#editSelectOptionsDiv').show();

			if (fieldSettings) {
				try {
					var settings = JSON.parse(fieldSettings);
					console.log('Parsed settings:', settings); // 디버깅용

					if (settings.options && Array.isArray(settings.options)) {
						$('#edit_select_options').val(settings.options.join('\n'));
						console.log('Options set:', settings.options.join('\n')); // 디버깅용
					}
				} catch (e) {
					console.error('Failed to parse field settings:', e);
					console.error('fieldSettings:', fieldSettings);
					$('#edit_select_options').val('');
				}
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
