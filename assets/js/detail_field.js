/**
 * 파일 위치: assets/js/detail_field.js
 * 역할: 상세필드 설정 페이지의 JavaScript 기능 처리 (Toast 메시지 적용)
 */

$(document).ready(function() {

	// 조직 선택 이벤트는 common.js에서 통합 처리하므로 제거
	// 기존 .org-selector-item 이벤트 핸들러 제거됨

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

		// 필수 필드 검증
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

		// 저장 중 상태 표시
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

	// 필드 수정 폼 제출
	$('#editFieldForm').on('submit', function(e) {
		e.preventDefault();

		var formData = {
			field_idx: $('#edit_field_idx').val(),
			field_name: $('#edit_field_name').val().trim(),
			field_type: $('#edit_field_type').val(),
			field_settings: {}
		};

		// 필수 필드 검증
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

		// 저장 중 상태 표시
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

	// 필드 수정 모달 열기
	$(document).on('click', '.edit-field-btn', function() {
		var fieldIdx = $(this).data('field-idx');
		var fieldName = $(this).data('field-name');
		var fieldType = $(this).data('field-type');
		var fieldOptions = $(this).data('field-options');

		$('#edit_field_idx').val(fieldIdx);
		$('#edit_field_name').val(fieldName);
		$('#edit_field_type').val(fieldType).trigger('change');

		if (fieldType === 'select' && fieldOptions) {
			$('#edit_select_options').val(fieldOptions.join('\n'));
		}

		$('#editFieldModal').modal('show');
	});

	// 필드 삭제
	$(document).on('click', '.delete-field-btn', function() {
		var fieldIdx = $(this).data('field-idx');
		var fieldName = $(this).data('field-name');

		// 모달로 확인
		if (typeof showConfirmModal === 'function') {
			showConfirmModal(
				'필드 삭제',
				`"${fieldName}" 필드를 삭제하시겠습니까? 삭제된 필드의 데이터는 복구할 수 없습니다.`,
				function() {
					deleteField(fieldIdx);
				}
			);
		} else {
			// 모달 함수가 없으면 confirm 사용
			if (confirm(`"${fieldName}" 필드를 삭제하시겠습니까?\n삭제된 필드의 데이터는 복구할 수 없습니다.`)) {
				deleteField(fieldIdx);
			}
		}
	});

	// 필드 삭제 실행 함수
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

	// 필드 순서 변경 (드래그 앤 드롭)
	if ($('#fieldsList').length > 0) {
		$('#fieldsList').sortable({
			handle: '.drag-handle',
			axis: 'y',
			placeholder: 'field-placeholder',
			update: function(event, ui) {
				var fieldOrder = [];
				$('#fieldsList .field-item').each(function(index) {
					fieldOrder.push({
						field_idx: $(this).data('field-idx'),
						order: index + 1
					});
				});

				$.ajax({
					url: 'detail_field/update_field_order',
					type: 'POST',
					data: { field_order: fieldOrder },
					dataType: 'json',
					success: function(response) {
						if (response.success) {
							showToast('필드 순서가 변경되었습니다.');
						} else {
							showToast(response.message);
							// 실패 시 원래 순서로 복원
							$('#fieldsList').sortable('cancel');
						}
					},
					error: function() {
						showToast('필드 순서 변경 중 오류가 발생했습니다.');
						// 실패 시 원래 순서로 복원
						$('#fieldsList').sortable('cancel');
					}
				});
			}
		});
	}

	// 모달 초기화
	$('#addFieldModal').on('hidden.bs.modal', function() {
		$(this).find('form')[0].reset();
		$('#selectOptionsDiv').hide();
	});

	$('#editFieldModal').on('hidden.bs.modal', function() {
		$(this).find('form')[0].reset();
		$('#editSelectOptionsDiv').hide();
	});
});
