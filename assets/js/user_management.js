/**
 * 파일 위치: assets/js/user_management.js
 * 역할: 사용자 관리 페이지의 JavaScript 기능
 */

// ========================= 전역 변수 =========================
let selectedUsers = []; // 체크박스로 선택된 사용자 목록

// ========================= 초기화 =========================
$(document).ready(function() {
	initializePage();
});

/**
 * 페이지 초기화 함수
 */
function initializePage() {
	initializeSelect2Elements();
	loadInitialData();
	updateSelectedCount();
}

/**
 * Select2 요소 초기화
 */
function initializeSelect2Elements() {
	// 개별 수정 모달용 Select2 초기화
	$('#edit_managed_menus, #edit_managed_areas').select2({
		width: '100%',
		placeholder: '선택해주세요',
		allowClear: true,
		dropdownParent: function() {
			return $(this).closest('.modal-body');
		}
	});

	// 일괄 수정 모달용 Select2는 모달이 열릴 때 초기화
}

/**
 * 초기 데이터 로드
 */
function loadInitialData() {
	loadManagedMenus();
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
	$('#edit_user_id').val(userId);
	$('#edit_org_id').val(orgId);
	$('#edit_user_name').val(userName);
	$('#edit_user_mail').val(userMail);
	$('#edit_user_hp').val(userHp);

	$('#editUserModal').modal('show');
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
		initializeBulkSelect2();
		loadBulkEditData();
		$(this).off('shown.bs.modal'); // 중복 실행 방지
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
		allowClear: true,
		dropdownParent: $('#bulkEditModal')
	});

	$('#bulk_managed_areas').select2({
		width: '100%',
		placeholder: '관리 그룹을 선택하세요 (선택하지 않으면 변경안함)',
		allowClear: true,
		dropdownParent: $('#bulkEditModal')
	});
}

/**
 * 일괄 수정용 데이터 로드
 */
function loadBulkEditData() {
	const orgId = getCookie('activeOrg') || $('#edit_org_id').val();
	loadManagedMenusForBulk();
	loadManagedAreasForBulk(orgId);
}

// ========================= 데이터 로드 함수 =========================

/**
 * 관리 메뉴 데이터 로드 (개별 수정용)
 */
function loadManagedMenus() {
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
 * 일괄 수정용 관리 메뉴 데이터 로드
 */
function loadManagedMenusForBulk() {
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
				$('#bulk_managed_menus').html(options).trigger('change');
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
 * 관리 그룹 데이터 로드 (개별 수정용)
 */
function loadManagedAreas(orgId) {
	if (!orgId) {
		orgId = getCookie('activeOrg');
	}

	if (!orgId) {
		showToast('조직 정보를 찾을 수 없습니다.');
		return;
	}

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
 * 일괄 수정용 관리 그룹 데이터 로드
 */
function loadManagedAreasForBulk(orgId) {
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
				$('#bulk_managed_areas').html(options).trigger('change');
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
 * 사용자 관리 정보 조회
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

			if (response && response.success) {
				showToast(response.message);
				location.reload();
			} else {
				showToast(response ? response.message : '사용자 삭제에 실패했습니다.');
			}
		},
		error: function(xhr, status, error) {
			console.error('AJAX 에러:', {xhr, status, error});
			showToast('사용자 삭제 중 오류가 발생했습니다.');
		}
	});
}

/**
 * 사용자 초대 처리
 */
function inviteUser(inviteEmail, orgId) {
	$.ajax({
		url: 'user_management/invite_user',
		type: 'POST',
		data: {
			invite_email: inviteEmail,
			org_id: orgId
		},
		dataType: 'json',
		success: function(response) {
			if (response.success) {
				showToast(response.message);
				$('#inviteUserModal').modal('hide');
				$('#inviteUserForm')[0].reset();
			} else {
				showToast(response.message);
			}
		},
		error: function() {
			showToast('초대 메일 발송 중 오류가 발생했습니다.');
		}
	});
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
 * 권한 레벨에 따른 관리 필드 표시/숨김
 */
function toggleManagementFields(level) {
	if (level == 10) {
		$('#managed_menus_group').hide();
		$('#managed_areas_group').hide();
	} else {
		$('#managed_menus_group').show();
		$('#managed_areas_group').show();
	}
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

/**
 * Toast 메시지 표시
 */
function showToast(message) {
	let toastContainer = $('.toast-container');
	if (toastContainer.length === 0) {
		$('body').append('<div class="toast-container position-fixed bottom-0 end-0 p-3"></div>');
		toastContainer = $('.toast-container');
	}

	const toast = $('<div class="toast" role="alert" aria-live="assertive" aria-atomic="true">' +
		'<div class="toast-header">' +
		'<strong class="me-auto">알림</strong>' +
		'<button type="button" class="btn-close" data-bs-dismiss="toast"></button>' +
		'</div>' +
		'<div class="toast-body">' + message + '</div>' +
		'</div>');

	toastContainer.append(toast);

	const bsToast = new bootstrap.Toast(toast[0]);
	bsToast.show();

	toast.on('hidden.bs.toast', function() {
		$(this).remove();
	});
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

	// 전체 선택 체크박스 상태 업데이트
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

// 권한 레벨 변경 시 필드 표시/숨김
$(document).on('change', '#edit_user_level', function() {
	const level = $(this).val();
	toggleManagementFields(level);
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

// 사용자 초대 폼 제출
$(document).on('submit', '#inviteUserForm', function(e) {
	e.preventDefault();

	const inviteEmail = $('#invite_email').val();
	const orgId = getCookie('activeOrg');

	if (!inviteEmail) {
		showToast('초대할 이메일 주소를 입력해주세요.');
		return;
	}

	inviteUser(inviteEmail, orgId);
});


// 사용자로 로그인 버튼 클릭 이벤트
$(document).on('click', '.login-as-user-btn', function() {
	var userId = $(this).data('user-id');
	var userName = $(this).data('user-name');

	showConfirm(
		userName + ' 사용자로 로그인하시겠습니까?<br><br>현재 세션이 종료되고 해당 사용자로 로그인됩니다.',
		function() {
			$.ajax({
				url: 'user_management/login_as_user',
				type: 'POST',
				data: {
					target_user_id: userId
				},
				dataType: 'json',
				success: function(response) {
					if (response.success) {
						showToast(response.message);
						// 잠시 후 메인 페이지로 이동
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
