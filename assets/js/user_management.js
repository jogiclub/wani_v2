
/**
 * Select2 초기화
 */
function initializeSelect2() {
	$('#edit_managed_menus').select2({
		dropdownParent: $('#editUserModal'),
		placeholder: '관리할 메뉴를 선택하세요',
		allowClear: true,
		width: '100%'
	});

	$('#edit_managed_areas').select2({
		dropdownParent: $('#editUserModal'),
		placeholder: '관리할 그룹을 선택하세요',
		allowClear: true,
		width: '100%'
	});
}

/**
 * 관리 메뉴 데이터 로드
 */
function loadManagedMenus() {
	$.ajax({
		url: 'user_management/get_managed_menus',
		type: 'POST',
		dataType: 'json',
		success: function(response) {
			if (response.success) {
				var options = '';
				response.menus.forEach(function(menu) {
					options += '<option value="' + menu.key + '">' + menu.name + '</option>';
				});
				$('#edit_managed_menus').html(options);
			} else {
				showToast('관리 메뉴 데이터를 불러오는데 실패했습니다.');
			}
		},
		error: function() {
			showToast('관리 메뉴 데이터를 불러오는데 실패했습니다.');
		}
	});
}

/**
 * 관리 그룹 데이터 로드
 */
function loadManagedAreas(orgId) {
	$.ajax({
		url: 'user_management/get_managed_areas',
		type: 'POST',
		data: { org_id: orgId },
		dataType: 'json',
		success: function(response) {
			if (response.success) {
				var options = '';
				response.areas.forEach(function(area) {
					options += '<option value="' + area.area_idx + '">' + area.area_name + '</option>';
				});
				$('#edit_managed_areas').html(options);
			} else {
				showToast('관리 그룹 데이터를 불러오는데 실패했습니다.');
			}
		},
		error: function() {
			showToast('관리 그룹 데이터를 불러오는데 실패했습니다.');
		}
	});
}

/**
 * 사용자 수정 모달 열기
 */
function openEditUserModal(userId, userName, userHp, level, orgId, managedMenus, managedAreas) {
	$('#edit_user_id').val(userId);
	$('#edit_org_id').val(orgId);
	$('#edit_user_name').val(userName);
	$('#edit_user_hp').val(userHp);
	$('#edit_user_level').val(level);

	// 권한 레벨에 따른 필드 표시/숨김
	toggleManagementFields(level);

	// 관리 그룹 데이터 로드
	loadManagedAreas(orgId);

	// 기존 관리 메뉴 선택값 설정
	if (managedMenus && Array.isArray(managedMenus)) {
		setTimeout(function() {
			$('#edit_managed_menus').val(managedMenus).trigger('change');
		}, 100);
	} else {
		$('#edit_managed_menus').val([]).trigger('change');
	}

	// 기존 관리 그룹 선택값 설정
	if (managedAreas && Array.isArray(managedAreas)) {
		setTimeout(function() {
			$('#edit_managed_areas').val(managedAreas).trigger('change');
		}, 200);
	} else {
		$('#edit_managed_areas').val([]).trigger('change');
	}

	$('#editUserModal').modal('show');
}

/**
 * 권한 레벨에 따른 관리 필드 표시/숨김
 */
function toggleManagementFields(level) {
	if (level == 10) { // 최고관리자
		$('#managed_menus_group').hide();
		$('#managed_areas_group').hide();
	} else {
		$('#managed_menus_group').show();
		$('#managed_areas_group').show();
	}
}

// 권한 레벨 변경 시 필드 표시/숨김 처리
$('#edit_user_level').on('change', function() {
	var level = $(this).val();
	toggleManagementFields(level);
});

// 사용자 수정 폼 제출
$('#editUserForm').on('submit', function(e) {
	e.preventDefault();

	var formData = new FormData(this);

	// Select2 다중 선택값 처리
	var managedMenus = $('#edit_managed_menus').val();
	var managedAreas = $('#edit_managed_areas').val();

	// 기존 managed_menus[], managed_areas[] 제거
	formData.delete('managed_menus[]');
	formData.delete('managed_areas[]');

	// 새로운 값들 추가
	if (managedMenus && managedMenus.length > 0) {
		managedMenus.forEach(function(menu) {
			formData.append('managed_menus[]', menu);
		});
	}

	if (managedAreas && managedAreas.length > 0) {
		managedAreas.forEach(function(area) {
			formData.append('managed_areas[]', area);
		});
	}

	$.ajax({
		url: 'user_management/update_user',
		type: 'POST',
		data: formData,
		processData: false,
		contentType: false,
		dataType: 'json',
		success: function(response) {
			if (response.success) {
				showToast(response.message);
				$('#editUserModal').modal('hide');
				location.reload();
			} else {
				showToast(response.message);
			}
		},
		error: function() {
			showToast('사용자 정보 수정 중 오류가 발생했습니다.');
		}
	});
});

/**
 * Toast 메시지 표시
 */
function showToast(message) {
	var toast = $('<div class="toast" role="alert" aria-live="assertive" aria-atomic="true">' +
		'<div class="toast-header">' +
		'<strong class="me-auto">알림</strong>' +
		'<button type="button" class="btn-close" data-bs-dismiss="toast"></button>' +
		'</div>' +
		'<div class="toast-body">' + message + '</div>' +
		'</div>');

	$('body').append(toast);

	var bsToast = new bootstrap.Toast(toast[0]);
	bsToast.show();

	toast.on('hidden.bs.toast', function() {
		$(this).remove();
	});
}


/**
 * assets/js/user_management.js
 * 사용자 관리 페이지 JavaScript - 사용자 초대, 수정, 삭제 기능
 */

$(document).ready(function() {

	// Select2 초기화
	initializeSelect2();

	// 관리 메뉴 데이터 로드
	loadManagedMenus();

	let currentOrgId = getCurrentOrgId();

	// 사용자 초대 폼 제출
	$('#inviteUserForm').on('submit', function(e) {
		e.preventDefault();

		const formData = new FormData(this);
		formData.append('org_id', currentOrgId);

		const submitBtn = $(this).find('button[type="submit"]');
		const originalText = submitBtn.html();

		submitBtn.prop('disabled', true).html('<i class="spinner-border spinner-border-sm me-1"></i>발송중...');

		$.ajax({
			url: 'user_management/invite_user',
			type: 'POST',
			data: Object.fromEntries(formData),
			dataType: 'json',
			success: function(response) {
				if (response.success) {
					showToast(response.message, 'success');
					$('#inviteUserModal').modal('hide');
				} else {
					showToast(response.message, 'error');
				}
			},
			error: function() {
				showToast('초대 메일 발송 중 오류가 발생했습니다.', 'error');
			},
			complete: function() {
				submitBtn.prop('disabled', false).html(originalText);
			}
		});
	});

// 사용자 수정 버튼 클릭 이벤트
	$(document).on('click', '.edit-user-btn', function() {
		var userId = $(this).data('user-id');
		var userName = $(this).data('user-name');
		var userHp = $(this).data('user-hp');
		var level = $(this).data('level');
		var orgId = $(this).data('org-id');

		// 관리 메뉴와 관리 그룹 정보 조회
		getUserManagementInfo(userId, function(managedMenus, managedAreas) {
			openEditUserModal(userId, userName, userHp, level, orgId, managedMenus, managedAreas);
		});
	});

	/**
	 * 사용자의 관리 메뉴/그룹 정보 조회
	 */
	function getUserManagementInfo(userId, callback) {
		$.ajax({
			url: 'user_management/get_user_management_info',
			type: 'POST',
			data: { user_id: userId },
			dataType: 'json',
			success: function(response) {
				if (response.success) {
					callback(response.managed_menus || [], response.managed_areas || []);
				} else {
					callback([], []);
				}
			},
			error: function() {
				callback([], []);
			}
		});
	}

	// 사용자 수정 폼 제출
	$('#editUserForm').on('submit', function(e) {
		e.preventDefault();

		const formData = new FormData(this);
		formData.append('org_id', currentOrgId);

		const submitBtn = $(this).find('button[type="submit"]');
		const originalText = submitBtn.html();

		submitBtn.prop('disabled', true).html('<i class="spinner-border spinner-border-sm me-1"></i>수정중...');

		$.ajax({
			url: 'user_management/update_user',
			type: 'POST',
			data: Object.fromEntries(formData),
			dataType: 'json',
			success: function(response) {
				if (response.success) {
					showToast(response.message, 'success');
					$('#editUserModal').modal('hide');
					setTimeout(function() {
						location.reload();
					}, 1000);
				} else {
					showToast(response.message, 'error');
				}
			},
			error: function() {
				showToast('사용자 정보 수정 중 오류가 발생했습니다.', 'error');
			},
			complete: function() {
				submitBtn.prop('disabled', false).html(originalText);
			}
		});
	});

	// 사용자 삭제 버튼 클릭
	$(document).on('click', '.delete-user-btn', function() {
		const userId = $(this).data('user-id');
		const userName = $(this).data('user-name');

		$('#deleteUserMessage').html(
			'<strong>' + userName + '</strong> 사용자를 조직에서 제외하시겠습니까?'
		);

		$('#confirmDeleteUserBtn').data('user-id', userId);
		$('#deleteUserModal').modal('show');
	});

	// 사용자 삭제 확인
	$('#confirmDeleteUserBtn').on('click', function() {
		const userId = $(this).data('user-id');
		const deleteBtn = $(this);
		const originalText = deleteBtn.html();

		deleteBtn.prop('disabled', true).html('<i class="spinner-border spinner-border-sm me-1"></i>삭제중...');

		$.ajax({
			url: 'user_management/delete_user',
			type: 'POST',
			data: {
				target_user_id: userId,
				org_id: currentOrgId
			},
			dataType: 'json',
			success: function(response) {
				if (response.success) {
					showToast(response.message, 'success');
					$('#deleteUserModal').modal('hide');
					setTimeout(function() {
						location.reload();
					}, 1000);
				} else {
					showToast(response.message, 'error');
				}
			},
			error: function() {
				showToast('사용자 삭제 중 오류가 발생했습니다.', 'error');
			},
			complete: function() {
				deleteBtn.prop('disabled', false).html(originalText);
			}
		});
	});

	// 모달 초기화 이벤트
	$('#inviteUserModal').on('hidden.bs.modal', function() {
		$('#inviteUserForm')[0].reset();
	});

	$('#editUserModal').on('hidden.bs.modal', function() {
		$('#editUserForm')[0].reset();
	});

	// 연락처 입력 포맷팅
	$('#edit_user_hp').on('input', function() {
		let value = $(this).val().replace(/[^0-9]/g, '');
		if (value.length >= 11) {
			value = value.replace(/(\d{3})(\d{4})(\d{4})/, '$1-$2-$3');
		} else if (value.length >= 7) {
			value = value.replace(/(\d{3})(\d{4})(\d+)/, '$1-$2-$3');
		} else if (value.length >= 3) {
			value = value.replace(/(\d{3})(\d+)/, '$1-$2');
		}
		$(this).val(value);
	});

	/**
	 * 현재 조직 ID 가져오기
	 */
	function getCurrentOrgId() {
		const urlParams = new URLSearchParams(window.location.search);
		const orgIdFromUrl = urlParams.get('org_id');

		if (orgIdFromUrl) {
			return orgIdFromUrl;
		}

		// 활성화된 조직 목록에서 찾기
		const activeOrgItem = $('.org-selector-item.active');
		if (activeOrgItem.length > 0) {
			return activeOrgItem.data('org-id');
		}

		// 첫 번째 조직 선택
		const firstOrgItem = $('.org-selector-item').first();
		if (firstOrgItem.length > 0) {
			return firstOrgItem.data('org-id');
		}

		return null;
	}

	/**
	 * Toast 메시지 표시
	 */
	function showToast(message, type = 'info') {
		const toastEl = $('#userManagementToast');
		const toastBody = toastEl.find('.toast-body');

		// 타입에 따른 아이콘 추가
		let icon = '';
		switch(type) {
			case 'success':
				icon = '<i class="bi bi-check-circle text-success me-2"></i>';
				break;
			case 'error':
				icon = '<i class="bi bi-exclamation-circle text-danger me-2"></i>';
				break;
			case 'warning':
				icon = '<i class="bi bi-exclamation-triangle text-warning me-2"></i>';
				break;
			default:
				icon = '<i class="bi bi-info-circle text-primary me-2"></i>';
		}

		toastBody.html(icon + message);

		const toast = new bootstrap.Toast(toastEl);
		toast.show();
	}

	/**
	 * 권한 레벨에 따른 텍스트 반환
	 */
	function getLevelText(level) {
		if (level >= 10) return '최고관리자';
		if (level >= 9) return '관리자';
		if (level >= 5) return '부리더';
		if (level >= 1) return '회원';
		return '일반';
	}

	/**
	 * 권한 레벨에 따른 배지 클래스 반환
	 */
	function getLevelBadgeClass(level) {
		if (level >= 10) return 'bg-danger';
		if (level >= 9) return 'bg-warning';
		if (level >= 5) return 'bg-info';
		if (level >= 1) return 'bg-primary';
		return 'bg-secondary';
	}
});
