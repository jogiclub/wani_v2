/**
 * assets/js/user_management.js
 * 사용자 관리 페이지 JavaScript - 사용자 초대, 수정, 삭제 기능
 */

$(document).ready(function() {
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

	// 사용자 수정 버튼 클릭
	$(document).on('click', '.edit-user-btn', function() {
		const userId = $(this).data('user-id');
		const userName = $(this).data('user-name');
		const userHp = $(this).data('user-hp');
		const userLevel = $(this).data('user-level');

		$('#edit_user_id').val(userId);
		$('#edit_user_name').val(userName);
		$('#edit_user_hp').val(userHp);
		$('#edit_user_level').val(userLevel);

		$('#editUserModal').modal('show');
	});

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
