/**
 * 파일 위치: assets/js/user_management.js
 * 역할: 사용자 관리 페이지의 JavaScript 기능
 */

// ========================= 전역 변수 =========================
let selectedUsers = [];

// ========================= 초기화 =========================
$(document).ready(function() {
	initializePage();
});

/**
 * 페이지 초기화 함수
 */
function initializePage() {
	updateSelectedCount();
}

// ========================= 체크박스 관련 함수 =========================

/**
 * 선택된 사용자 목록 업데이트
 */
function updateSelectedUsers() {
	selectedUsers = [];
	$('.user-checkbox:checked').each(function() {
		selectedUsers.push({
			userId: $(this).val(),
			userName: $(this).data('user-name')
		});
	});

	updateSelectedCount();
	toggleBulkEditButton();
}

/**
 * 선택된 사용자 수 표시 업데이트
 */
function updateSelectedCount() {
	$('#selectedCount').text(selectedUsers.length);
}

/**
 * 일괄 수정 버튼 활성화/비활성화
 */
function toggleBulkEditButton() {
	$('#bulkEditBtn').prop('disabled', selectedUsers.length === 0);
}

// ========================= 모달 관련 함수 =========================

/**
 * 개별 사용자 수정 모달 열기
 */
function openEditUserModal(userId, userName, userMail, userHp, level, orgId) {
	// 기본 정보 설정
	$('#edit_user_id').val(userId);
	$('#edit_org_id').val(orgId);
	$('#edit_user_name').val(userName);
	$('#edit_user_mail').val(userMail);
	$('#edit_user_hp').val(userHp);
	$('#edit_user_level').val(level);

	// 모달 표시
	$('#editUserModal').modal('show');

	// 모달이 완전히 열린 후 데이터 로드 (Select2 초기화는 옵션 로드 후 실행)
	$('#editUserModal').on('shown.bs.modal', function() {
		loadEditModalData(userId, orgId);
		$(this).off('shown.bs.modal');
	});
}

/**
 * 개별 수정 모달 데이터 로드
 */
function loadEditModalData(userId, orgId) {
	// 1단계: 관리 메뉴 옵션 로드
	loadManagedMenusForEdit(function() {
		// 2단계: 관리 그룹 옵션 로드
		loadManagedAreasForEdit(orgId, function() {
			// 3단계: Select2 초기화 (옵션이 모두 로드된 후)
			initializeEditModalSelect2();
			// 4단계: 사용자의 현재 값 설정
			loadUserManagementInfo(userId);
		});
	});
}

/**
 * 개별 수정용 관리 메뉴 데이터 로드
 */
function loadManagedMenusForEdit(callback) {
	$.ajax({
		url: 'user_management/get_managed_menus',
		type: 'POST',
		dataType: 'json',
		success: function(response) {
			if (response.success) {
				let options = '';
				response.menus.forEach(function(menu) {
					options += '<option value="' + menu.key + '">' + menu.name + '</option>';
				});
				$('#edit_managed_menus').html(options);
				if (typeof callback === 'function') {
					callback();
				}
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
 * 개별 수정용 관리 그룹 데이터 로드
 */
function loadManagedAreasForEdit(orgId, callback) {
	$.ajax({
		url: 'user_management/get_managed_areas',
		type: 'POST',
		data: { org_id: orgId },
		dataType: 'json',
		success: function(response) {
			if (response.success) {
				let options = '';
				response.areas.forEach(function(area) {
					options += '<option value="' + area.area_idx + '">' + area.area_name + '</option>';
				});
				$('#edit_managed_areas').html(options);
				if (typeof callback === 'function') {
					callback();
				}
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
 * 개별 수정 모달용 Select2 초기화
 */
function initializeEditModalSelect2() {
	// 기존 Select2 인스턴스 제거
	if ($('#edit_managed_menus').hasClass('select2-hidden-accessible')) {
		$('#edit_managed_menus').select2('destroy');
	}
	if ($('#edit_managed_areas').hasClass('select2-hidden-accessible')) {
		$('#edit_managed_areas').select2('destroy');
	}

	// 새로운 Select2 초기화
	$('#edit_managed_menus').select2({
		width: '100%',
		placeholder: '관리 메뉴 선택',
		allowClear: true,
		dropdownParent: $('#editUserModal')
	});

	$('#edit_managed_areas').select2({
		width: '100%',
		placeholder: '관리 그룹 선택',
		allowClear: true,
		dropdownParent: $('#editUserModal')
	});
}

/**
 * 사용자의 현재 관리 메뉴/그룹 정보 로드 및 선택
 */
function loadUserManagementInfo(userId) {
	$.ajax({
		url: 'user_management/get_user_management_info',
		type: 'POST',
		data: { user_id: userId },
		dataType: 'json',
		success: function(response) {
			if (response.success) {
				// 관리 메뉴 선택
				if (response.managed_menus && response.managed_menus.length > 0) {
					$('#edit_managed_menus').val(response.managed_menus).trigger('change');
				}

				// 관리 그룹 선택
				if (response.managed_areas && response.managed_areas.length > 0) {
					$('#edit_managed_areas').val(response.managed_areas).trigger('change');
				}
			}
		},
		error: function() {
			console.error('사용자 관리 정보를 불러오는데 실패했습니다.');
		}
	});
}

/**
 * 일괄 수정 모달 열기
 */
function openBulkEditModal() {
	if (selectedUsers.length === 0) {
		showToast('수정할 사용자를 선택해주세요.');
		return;
	}

	displaySelectedUsersList();
	setupBulkEditForm();

	$('#bulkEditModal').modal('show');

	// 모달이 완전히 열린 후 Select2 초기화 및 데이터 로드
	$('#bulkEditModal').on('shown.bs.modal', function() {
		loadBulkEditData();
		$(this).off('shown.bs.modal');
	});
}

/**
 * 선택된 사용자 목록 표시
 */
function displaySelectedUsersList() {
	let userListHtml = '';
	selectedUsers.forEach(function(user) {
		userListHtml += '<span class="badge bg-primary me-1 mb-1">' + user.userName + '</span>';
	});
	$('#selectedUsersList').html(userListHtml);
}

/**
 * 일괄 수정 폼 설정
 */
function setupBulkEditForm() {
	const orgId = getCookie('activeOrg') || $('#edit_org_id').val();
	$('#bulk_org_id').val(orgId);

	const userIds = selectedUsers.map(user => user.userId);
	$('#bulk_user_ids').val(JSON.stringify(userIds));

	// 폼 초기화
	$('#bulk_user_level').val('');
}

/**
 * 일괄 수정용 데이터 로드
 */
function loadBulkEditData() {
	const orgId = getCookie('activeOrg') || $('#edit_org_id').val();

	// 1단계: 관리 메뉴 로드
	loadManagedMenusForBulk(function() {
		// 2단계: 관리 그룹 로드
		loadManagedAreasForBulk(orgId, function() {
			// 3단계: Select2 초기화
			initializeBulkSelect2();
		});
	});
}

/**
 * 일괄 수정용 관리 메뉴 데이터 로드
 */
function loadManagedMenusForBulk(callback) {
	$.ajax({
		url: 'user_management/get_managed_menus',
		type: 'POST',
		dataType: 'json',
		success: function(response) {
			if (response.success) {
				let options = '';
				response.menus.forEach(function(menu) {
					options += '<option value="' + menu.key + '">' + menu.name + '</option>';
				});
				$('#bulk_managed_menus').html(options);
				if (typeof callback === 'function') {
					callback();
				}
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
 * 일괄 수정용 관리 그룹 데이터 로드
 */
function loadManagedAreasForBulk(orgId, callback) {
	$.ajax({
		url: 'user_management/get_managed_areas',
		type: 'POST',
		data: { org_id: orgId },
		dataType: 'json',
		success: function(response) {
			if (response.success) {
				let options = '';
				response.areas.forEach(function(area) {
					options += '<option value="' + area.area_idx + '">' + area.area_name + '</option>';
				});
				$('#bulk_managed_areas').html(options);
				if (typeof callback === 'function') {
					callback();
				}
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
 * 일괄 수정용 Select2 초기화
 */
function initializeBulkSelect2() {
	// 기존 Select2 인스턴스 제거
	if ($('#bulk_managed_menus').hasClass('select2-hidden-accessible')) {
		$('#bulk_managed_menus').select2('destroy');
	}
	if ($('#bulk_managed_areas').hasClass('select2-hidden-accessible')) {
		$('#bulk_managed_areas').select2('destroy');
	}

	// 새로운 Select2 초기화
	$('#bulk_managed_menus').select2({
		width: '100%',
		placeholder: '관리 메뉴를 선택하세요 (선택하지 않으면 변경안함)',
		allowClear: false,
		dropdownParent: $('#bulkEditModal')
	});

	$('#bulk_managed_areas').select2({
		width: '100%',
		placeholder: '관리 그룹을 선택하세요 (선택하지 않으면 변경안함)',
		allowClear: false,
		dropdownParent: $('#bulkEditModal')
	});
}

// ========================= 폼 처리 함수 =========================

/**
 * 일괄 수정 폼 데이터 처리 및 제출
 */
function processBulkEditForm() {
	const formData = new FormData($('#bulkEditForm')[0]);

	// Select2 다중 선택값 처리
	const managedMenus = $('#bulk_managed_menus').val();
	const managedAreas = $('#bulk_managed_areas').val();

	// 기존 항목 제거
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

	submitBulkEditForm(formData);
}

/**
 * 일괄 수정 폼 제출
 */
function submitBulkEditForm(formData) {
	$.ajax({
		url: 'user_management/bulk_update_users',
		type: 'POST',
		data: formData,
		processData: false,
		contentType: false,
		dataType: 'json',
		success: function(response) {
			if (response.success) {
				showToast(response.message);
				$('#bulkEditModal').modal('hide');
				location.reload();
			} else {
				showToast(response.message);
			}
		},
		error: function() {
			showToast('일괄 수정 중 오류가 발생했습니다.');
		}
	});
}

/**
 * 개별 사용자 수정 폼 제출
 */
function submitEditUserForm(formData) {
	// Select2 다중 선택값 처리
	const managedMenus = $('#edit_managed_menus').val();
	const managedAreas = $('#edit_managed_areas').val();

	// 기존 항목 제거
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
}

/**
 * 사용자 삭제 처리
 */
function deleteUser(userId, userName, orgId) {
	if (!userId || !orgId) {
		showToast('삭제할 사용자 정보가 올바르지 않습니다.');
		return;
	}

	$.ajax({
		url: 'user_management/delete_user',
		type: 'POST',
		data: {
			target_user_id: userId,
			org_id: orgId
		},
		dataType: 'json',
		success: function(response) {
			if (typeof response === 'string') {
				try {
					response = JSON.parse(response);
				} catch (e) {
					console.error('JSON 파싱 실패:', e);
					showToast('서버 응답을 처리할 수 없습니다.');
					return;
				}
			}

			if (response && typeof response === 'object') {
				if (response.success) {
					showToast(response.message || '사용자가 성공적으로 삭제되었습니다.');
					setTimeout(function() {
						location.reload();
					}, 1000);
				} else {
					showToast(response.message || '사용자 삭제에 실패했습니다.');
				}
			} else {
				console.error('예상하지 못한 응답 형식:', response);
				showToast('서버에서 예상하지 못한 응답을 받았습니다.');
			}
		},
		error: function(xhr, status, error) {
			console.error('AJAX 요청 실패:', {
				status: status,
				error: error,
				responseText: xhr.responseText
			});

			let errorMessage = '사용자 삭제 중 오류가 발생했습니다.';

			if (xhr.status === 403) {
				errorMessage = '사용자를 삭제할 권한이 없습니다.';
			} else if (xhr.status === 404) {
				errorMessage = '요청한 기능을 찾을 수 없습니다.';
			} else if (xhr.status >= 500) {
				errorMessage = '서버 오류가 발생했습니다. 잠시 후 다시 시도해주세요.';
			}

			showToast(errorMessage);
		}
	});
}

// ========================= 초대 관련 함수 =========================

/**
 * 이메일 주소 파싱 및 검증
 */
function parseEmailAddresses(rawEmails) {
	if (!rawEmails) return [];

	const emails = rawEmails.split(/[\r\n,;]+/)
		.map(email => email.trim())
		.filter(email => email.length > 0);

	return [...new Set(emails)];
}

/**
 * 이메일 형식 검증
 */
function validateEmail(email) {
	const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
	return emailRegex.test(email);
}

/**
 * 이메일 미리보기 업데이트
 */
function updateEmailPreview() {
	const rawEmails = $('#invite_emails').val();
	const emails = parseEmailAddresses(rawEmails);

	if (emails.length === 0) {
		$('#emailPreview').hide();
		return;
	}

	let previewHtml = '';
	let validCount = 0;
	let invalidCount = 0;

	emails.forEach(function(email) {
		if (validateEmail(email)) {
			previewHtml += '<span class="badge bg-success me-1 mb-1">' + email + '</span>';
			validCount++;
		} else {
			previewHtml += '<span class="badge bg-danger me-1 mb-1">' + email + ' (잘못된 형식)</span>';
			invalidCount++;
		}
	});

	previewHtml += '<div class="mt-2 small text-muted">';
	previewHtml += '총 ' + emails.length + '개 이메일';
	if (validCount > 0) previewHtml += ' | 유효: ' + validCount + '개';
	if (invalidCount > 0) previewHtml += ' | 오류: ' + invalidCount + '개';
	previewHtml += '</div>';

	$('#emailList').html(previewHtml);
	$('#emailPreview').show();
}

/**
 * 다중 사용자 초대 처리
 */
function inviteUsers(rawEmails, orgId) {
	const emails = parseEmailAddresses(rawEmails);

	if (emails.length === 0) {
		showToast('초대할 이메일 주소를 입력해주세요.');
		return;
	}

	const invalidEmails = emails.filter(email => !validateEmail(email));
	if (invalidEmails.length > 0) {
		showToast('잘못된 이메일 형식이 있습니다: ' + invalidEmails.slice(0, 3).join(', ') +
			(invalidEmails.length > 3 ? ' 외 ' + (invalidEmails.length - 3) + '개' : ''));
		return;
	}

	showInviteLoading(true);

	$.ajax({
		url: 'user_management/invite_users',
		type: 'POST',
		data: {
			invite_emails: rawEmails,
			org_id: orgId
		},
		dataType: 'json',
		timeout: 60000,
		success: function(response) {
			showInviteLoading(false);

			if (response.success) {
				showToast(response.message, 'success');
				$('#inviteUserModal').modal('hide');
				$('#inviteUserForm')[0].reset();
				$('#emailPreview').hide();
				refreshUserTable();
			} else {
				showToast(response.message, 'warning');
			}
		},
		error: function(xhr, status, error) {
			showInviteLoading(false);

			let errorMessage = '초대 메일 발송 중 오류가 발생했습니다.';

			if (status === 'timeout') {
				errorMessage = '요청 시간이 초과되었습니다. 잠시 후 다시 시도해주세요.';
			} else if (xhr.status >= 500) {
				errorMessage = '서버 오류가 발생했습니다. 잠시 후 다시 시도해주세요.';
			}

			showToast(errorMessage, 'error');
		}
	});
}

/**
 * 초대 로딩 상태 표시
 */
function showInviteLoading(show) {
	if (show) {
		$('#inviteSpinner').show();
		$('#inviteButtonText').text('발송 중...');
		$('#inviteSubmitBtn').prop('disabled', true);
		$('.modal-header .btn-close, .modal-footer .btn-secondary').prop('disabled', true);
	} else {
		$('#inviteSpinner').hide();
		$('#inviteButtonText').text('초대 메일 발송');
		$('#inviteSubmitBtn').prop('disabled', false);
		$('.modal-header .btn-close, .modal-footer .btn-secondary').prop('disabled', false);
	}
}

/**
 * 사용자 테이블 실시간 갱신
 */
function refreshUserTable() {
	const orgId = getCookie('activeOrg');

	if (!orgId) {
		console.error('조직 정보를 찾을 수 없습니다.');
		return;
	}

	$.ajax({
		url: 'user_management/get_org_users_ajax',
		type: 'POST',
		data: { org_id: orgId },
		dataType: 'json',
		success: function(response) {
			if (response.success) {
				updateUserTableHTML(response.users);
				updateSelectedCount();
			} else {
				console.error('사용자 목록 갱신 실패:', response.message);
			}
		},
		error: function(xhr, status, error) {
			console.error('사용자 목록 갱신 중 오류:', error);
		}
	});
}

/**
 * 사용자 테이블 HTML 업데이트
 */
function updateUserTableHTML(users) {
	const tbody = $('.table tbody');

	if (!users || users.length === 0) {
		tbody.html('<tr><td colspan="9" class="text-center py-4"><p class="text-muted">등록된 사용자가 없습니다.</p></td></tr>');
		return;
	}

	let html = '';

	users.forEach(function(user) {
		html += '<tr>';
		html += '<td><input type="checkbox" class="form-check-input user-checkbox" value="' + user.user_id + '" data-user-name="' + escapeHtml(user.user_name) + '"></td>';

		html += '<td class="text-center">';
		if (user.user_profile_image) {
			html += '<img src="' + user.user_profile_image + '" class="rounded-circle" style="width: 40px; height: 40px; object-fit: cover;" alt="프로필">';
		} else {
			html += '<div class="rounded-circle bg-light d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;"><i class="bi bi-person text-muted"></i></div>';
		}
		html += '</td>';

		html += '<td><strong>' + escapeHtml(user.user_name || '미입력') + '</strong>';
		if (user.master_yn === 'Y') {
			html += '<br><small class="text-primary">마스터</small>';
		}
		html += '</td>';

		html += '<td><span>' + escapeHtml(user.user_mail) + '</span></td>';
		html += '<td>' + getUserLevelBadge(user.level) + '</td>';
		html += '<td>' + getManagedMenusDisplay(user.managed_menus_display) + '</td>';
		html += '<td>' + getManagedAreasDisplay(user.managed_areas_display) + '</td>';
		html += '<td>' + getUserActionButtons(user) + '</td>';

		html += '</tr>';
	});

	tbody.html(html);
	$('#selectAllUsers').prop('checked', false).prop('indeterminate', false);
}

/**
 * 권한 레벨 뱃지 생성
 */
function getUserLevelBadge(level) {
	let badgeClass = 'bg-secondary';
	let text = '일반(' + level + ')';

	if (level >= 10) {
		badgeClass = 'bg-danger';
		text = '최고관리자';
	} else if (level >= 9) {
		badgeClass = 'bg-warning';
		text = '관리자';
	} else if (level === 0) {
		badgeClass = 'bg-secondary';
		text = '초대중';
	}

	return '<span class="badge ' + badgeClass + '">' + text + '</span>';
}

/**
 * 관리 메뉴 표시 생성
 */
function getManagedMenusDisplay(managedMenus) {
	if (!managedMenus || managedMenus.length === 0) {
		return '<span class="text-muted">-</span>';
	}

	let html = '';
	const displayMenus = managedMenus.slice(0, 2);

	displayMenus.forEach(function(menu) {
		html += '<span class="badge bg-info text-dark me-1 mb-1">' + escapeHtml(menu) + '</span>';
	});

	if (managedMenus.length > 2) {
		html += '<small class="text-muted">외 ' + (managedMenus.length - 2) + '개</small>';
	}

	return html;
}

/**
 * 관리 그룹 표시 생성
 */
function getManagedAreasDisplay(managedAreas) {
	if (!managedAreas || managedAreas.length === 0) {
		return '<span class="text-muted">-</span>';
	}

	let html = '';
	const displayAreas = managedAreas.slice(0, 2);

	displayAreas.forEach(function(area) {
		html += '<span class="badge bg-success me-1 mb-1">' + escapeHtml(area) + '</span>';
	});

	if (managedAreas.length > 2) {
		html += '<small class="text-muted">외 ' + (managedAreas.length - 2) + '개</small>';
	}

	return html;
}

/**
 * 사용자 액션 버튼 생성
 */
function getUserActionButtons(user) {
	let html = '';

	html += '<button type="button" class="btn btn-sm btn-outline-primary edit-user-btn" ';
	html += 'data-user-id="' + user.user_id + '" ';
	html += 'data-user-name="' + escapeHtml(user.user_name) + '" ';
	html += 'data-user-mail="' + escapeHtml(user.user_mail) + '" ';
	html += 'data-user-hp="' + escapeHtml(user.user_hp) + '" ';
	html += 'data-user-level="' + user.level + '" ';
	html += 'data-org-id="' + user.org_id + '">';
	html += '<i class="bi bi-pencil"></i></button> ';

	if (user.master_yn !== 'Y') {
		html += '<button type="button" class="btn btn-sm btn-outline-danger delete-user-btn" ';
		html += 'data-user-id="' + user.user_id + '" ';
		html += 'data-user-name="' + escapeHtml(user.user_name) + '" ';
		html += 'data-org-id="' + user.org_id + '">';
		html += '<i class="bi bi-trash"></i></button> ';
	}

	return html;
}

// ========================= 유틸리티 함수 =========================

/**
 * 쿠키 값 가져오기
 */
function getCookie(name) {
	const value = "; " + document.cookie;
	const parts = value.split("; " + name + "=");
	if (parts.length == 2) return parts.pop().split(";").shift();
}

/**
 * HTML 이스케이프 처리
 */
function escapeHtml(text) {
	if (!text) return '';
	const div = document.createElement('div');
	div.textContent = text;
	return div.innerHTML;
}

/**
 * 확인 모달 표시 함수
 */
function showConfirm(message, callback, title = '확인') {
	$('#confirmModal').remove();

	const modal = $('<div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalLabel" aria-hidden="true">' +
		'<div class="modal-dialog">' +
		'<div class="modal-content">' +
		'<div class="modal-header">' +
		'<h5 class="modal-title" id="confirmModalLabel">' + title + '</h5>' +
		'<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>' +
		'</div>' +
		'<div class="modal-body">' + message + '</div>' +
		'<div class="modal-footer">' +
		'<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>' +
		'<button type="button" class="btn btn-primary" id="confirmModalOkBtn">확인</button>' +
		'</div>' +
		'</div>' +
		'</div>' +
		'</div>');

	$('body').append(modal);

	$('#confirmModalOkBtn').on('click', function() {
		$('#confirmModal').modal('hide');
		if (typeof callback === 'function') {
			callback();
		}
	});

	$('#confirmModal').on('hidden.bs.modal', function() {
		$(this).remove();
	});

	$('#confirmModal').modal('show');
}

// ========================= 이벤트 핸들러 =========================

// 전체 선택 체크박스
$(document).on('change', '#selectAllUsers', function() {
	const isChecked = $(this).is(':checked');
	$('.user-checkbox').prop('checked', isChecked);
	updateSelectedUsers();
});

// 개별 체크박스
$(document).on('change', '.user-checkbox', function() {
	updateSelectedUsers();

	const totalCheckboxes = $('.user-checkbox').length;
	const checkedCheckboxes = $('.user-checkbox:checked').length;

	if (checkedCheckboxes === 0) {
		$('#selectAllUsers').prop('indeterminate', false).prop('checked', false);
	} else if (checkedCheckboxes === totalCheckboxes) {
		$('#selectAllUsers').prop('indeterminate', false).prop('checked', true);
	} else {
		$('#selectAllUsers').prop('indeterminate', true);
	}
});

// 일괄 수정 버튼 클릭
$(document).on('click', '#bulkEditBtn', function() {
	openBulkEditModal();
});

// 사용자 수정 버튼 클릭
$(document).on('click', '.edit-user-btn', function() {
	const userId = $(this).data('user-id');
	const userName = $(this).data('user-name');
	const userMail = $(this).data('user-mail');
	const userHp = $(this).data('user-hp');
	const level = $(this).data('user-level');
	const orgId = $(this).data('org-id');

	openEditUserModal(userId, userName, userMail, userHp, level, orgId);
});

// 사용자 수정 폼 제출
$(document).on('submit', '#editUserForm', function(e) {
	e.preventDefault();
	const formData = new FormData(this);
	submitEditUserForm(formData);
});

// 일괄 수정 폼 제출
$(document).on('submit', '#bulkEditForm', function(e) {
	e.preventDefault();

	if (selectedUsers.length === 0) {
		showToast('수정할 사용자를 선택해주세요.');
		return;
	}

	const userCount = selectedUsers.length;
	const userNames = selectedUsers.map(user => user.userName).join(', ');

	showConfirm(
		`선택한 ${userCount}명의 사용자(${userNames})의 권한을 일괄 수정하시겠습니까?`,
		function() {
			processBulkEditForm();
		}
	);
});

// 사용자 삭제 버튼 클릭
$(document).on('click', '.delete-user-btn', function() {
	const userId = $(this).data('user-id');
	const userName = $(this).data('user-name');
	const orgId = $(this).data('org-id');

	showConfirm(
		userName + ' 사용자를 조직에서 제외하시겠습니까?<br><br>이 작업은 되돌릴 수 없습니다.',
		function() {
			deleteUser(userId, userName, orgId);
		},
		'사용자 삭제 확인'
	);
});

// 사용자로 로그인 버튼 클릭
$(document).on('click', '.login-as-user-btn', function() {
	const userId = $(this).data('user-id');
	const userName = $(this).data('user-name');

	showConfirm(
		userName + ' 사용자로 로그인하시겠습니까?<br><br>현재 세션이 종료되고 해당 사용자로 로그인됩니다.',
		function() {
			$.ajax({
				url: 'user_management/login_as_user',
				type: 'POST',
				data: { target_user_id: userId },
				dataType: 'json',
				success: function(response) {
					if (response.success) {
						showToast(response.message);
						setTimeout(function() {
							window.location.href = '/';
						}, 1000);
					} else {
						showToast(response.message);
					}
				},
				error: function() {
					showToast('사용자 로그인 중 오류가 발생했습니다.');
				}
			});
		},
		'사용자 로그인 확인'
	);
});

// 이메일 입력 시 실시간 미리보기 업데이트
$(document).on('input paste keyup', '#invite_emails', function() {
	clearTimeout(this.previewTimeout);
	this.previewTimeout = setTimeout(updateEmailPreview, 300);
});

// 다중 사용자 초대 폼 제출
$(document).on('submit', '#inviteUserForm', function(e) {
	e.preventDefault();

	const rawEmails = $('#invite_emails').val();
	const orgId = getCookie('activeOrg');

	if (!rawEmails || rawEmails.trim().length === 0) {
		showToast('초대할 이메일 주소를 입력해주세요.');
		return;
	}

	if (!orgId) {
		showToast('조직 정보를 찾을 수 없습니다.');
		return;
	}

	inviteUsers(rawEmails, orgId);
});

// 초대 모달이 닫힐 때 초기화
$(document).on('hidden.bs.modal', '#inviteUserModal', function() {
	$('#inviteUserForm')[0].reset();
	$('#emailPreview').hide();
	showInviteLoading(false);
});

// 수정 모달이 닫힐 때 초기화
$(document).on('hidden.bs.modal', '#editUserModal', function() {
	$('#editUserForm')[0].reset();

	if ($('#edit_managed_menus').hasClass('select2-hidden-accessible')) {
		$('#edit_managed_menus').select2('destroy');
	}
	if ($('#edit_managed_areas').hasClass('select2-hidden-accessible')) {
		$('#edit_managed_areas').select2('destroy');
	}
});
