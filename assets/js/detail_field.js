/**
 * 파일 위치: E:\SynologyDrive\Example\wani\assets\js\detail_field.js
 * 역할: 상세필드 설정 페이지의 JavaScript 기능 처리 (Toast 메시지 적용)
 */

$(document).ready(function() {

	// 조직 선택 이벤트 (조직목록에서 클릭 시 조직 변경)
	$(document).on('click', '.org-selector-item', function(e) {
		e.preventDefault();

		const orgId = $(this).data('org-id');
		const orgName = $(this).data('org-name');

		if (!orgId) {
			showToast('조직 정보를 가져올 수 없습니다.');
			return;
		}

		// 이미 선택된 조직인 경우 처리하지 않음
		if ($(this).hasClass('active')) {
			return;
		}

		// 로딩 상태 표시 (헤더의 조직명 버튼)
		const currentOrgBtn = $('#current-org-btn');
		const originalText = currentOrgBtn.text();
		currentOrgBtn.text('변경 중...');

		// 조직 변경을 위한 폼 제출
		const form = $('<form>', {
			'method': 'POST',
			'action': window.location.href
		});

		form.append($('<input>', {
			'type': 'hidden',
			'name': 'org_id',
			'value': orgId
		}));

		$('body').append(form);
		form.submit();
	});

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
			}
		});
	});
// 필드 수정 버튼 클릭
	$(document).on('click', '.edit-field-btn', function() {
		var fieldIdx = $(this).data('field-idx');
		var fieldName = $(this).data('field-name');
		var fieldType = $(this).data('field-type');
		var fieldSettings = $(this).data('field-settings');

		$('#edit_field_idx').val(fieldIdx);
		$('#edit_field_name').val(fieldName);
		$('#edit_field_type').val(fieldType).trigger('change');

		// 설정 정보 파싱 (한글 디코딩 처리)
		try {
			var settings;
			if (typeof fieldSettings === 'string') {
				// HTML entity가 있을 수 있으므로 디코딩
				var tempDiv = document.createElement('div');
				tempDiv.innerHTML = fieldSettings;
				var decodedSettings = tempDiv.textContent || tempDiv.innerText || '';
				settings = JSON.parse(decodedSettings);
			} else {
				settings = fieldSettings;
			}

			if (fieldType === 'select' && settings.options && Array.isArray(settings.options)) {
				$('#edit_select_options').val(settings.options.join('\n'));
			} else {
				$('#edit_select_options').val('');
			}
		} catch (e) {
			console.error('설정 파싱 오류:', e);
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

		// 필수 필드 검증
		if (!formData.field_name) {
			showToast('필드명을 입력해주세요.');
			$('#edit_field_name').focus();
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
			}
		});
	});

	// 필드 삭제 버튼 클릭
	$(document).on('click', '.delete-field-btn', function() {
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
	});

	// 필드 활성화/비활성화 토글
	$(document).on('click', '.toggle-field', function() {
		var fieldIdx = $(this).data('field-idx');

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
					// 실패 시 체크박스 상태 되돌리기
					$('.toggle-field[data-field-idx="' + fieldIdx + '"]').prop('checked',
						!$('.toggle-field[data-field-idx="' + fieldIdx + '"]').prop('checked'));
				}
			},
			error: function() {
				showToast('필드 상태 변경 중 오류가 발생했습니다.');
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
					showToast(response.message);
				} else {
					showToast(response.message);
					location.reload(); // 실패 시 페이지 새로고침
				}
			},
			error: function() {
				showToast('순서 변경 중 오류가 발생했습니다.');
				location.reload(); // 실패 시 페이지 새로고침
			}
		});
	}

	/**
	 * Toast 메시지 표시
	 */
	function showToast(message) {
		$('#detailFieldToast .toast-body').text(message);
		const toast = new bootstrap.Toast($('#detailFieldToast'));
		toast.show();
	}
});
