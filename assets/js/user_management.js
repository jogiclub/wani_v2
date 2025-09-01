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
 * 사용자 삭제 처리 - 개선된 버전
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
		beforeSend: function() {
			// 로딩 상태 표시 (선택사항)
			console.log('사용자 삭제 요청 시작:', userId);
		},
		success: function(response) {
			// 응답이 문자열인 경우 JSON 파싱 시도
			if (typeof response === 'string') {
				try {
					response = JSON.parse(response);
				} catch (e) {
					console.error('JSON 파싱 실패:', e);
					console.error('서버 응답:', response);
					showToast('서버 응답을 처리할 수 없습니다.');
					return;
				}
			}

			// 응답 검증
			if (response && typeof response === 'object') {
				if (response.success) {
					showToast(response.message || '사용자가 성공적으로 삭제되었습니다.');
					// 페이지 새로고침으로 목록 갱신
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

			// HTTP 상태 코드별 에러 메시지
			if (xhr.status === 403) {
				errorMessage = '사용자를 삭제할 권한이 없습니다.';
			} else if (xhr.status === 404) {
				errorMessage = '요청한 기능을 찾을 수 없습니다.';
			} else if (xhr.status >= 500) {
				errorMessage = '서버 오류가 발생했습니다. 잠시 후 다시 시도해주세요.';
			}

			showToast(errorMessage);
		},
		complete: function() {
			console.log('사용자 삭제 요청 완료:', userId);
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


/**
 * 파일 위치: assets/js/user_management.js
 * 역할: 다중 사용자 초대 및 실시간 테이블 갱신 기능
 */

// ========================= 초대 관련 함수 =========================

/**
 * 이메일 주소 파싱 및 검증
 */
function parseEmailAddresses(rawEmails) {
	if (!rawEmails) return [];

	// 줄바꿈, 쉼표, 세미콜론을 기준으로 분할
	const emails = rawEmails.split(/[\r\n,;]+/)
		.map(email => email.trim())
		.filter(email => email.length > 0);

	// 중복 제거
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

	// 통계 정보 추가
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

	// 유효하지 않은 이메일 확인
	const invalidEmails = emails.filter(email => !validateEmail(email));
	if (invalidEmails.length > 0) {
		showToast('잘못된 이메일 형식이 있습니다: ' + invalidEmails.slice(0, 3).join(', ') +
			(invalidEmails.length > 3 ? ' 외 ' + (invalidEmails.length - 3) + '개' : ''));
		return;
	}

	// 스피너 표시
	showInviteLoading(true);

	$.ajax({
		url: 'user_management/invite_users',
		type: 'POST',
		data: {
			invite_emails: rawEmails,
			org_id: orgId
		},
		dataType: 'json',
		timeout: 60000, // 60초 타임아웃 (메일 발송 시간 고려)
		success: function(response) {
			showInviteLoading(false);

			if (response.success) {
				showToast(response.message, 'success');
				$('#inviteUserModal').modal('hide');
				$('#inviteUserForm')[0].reset();
				$('#emailPreview').hide();

				// 테이블 실시간 갱신
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
		// 모달 닫기 버튼도 비활성화
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
				updateSelectedCount(); // 체크박스 상태 초기화
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

		// 체크박스
		html += '<td><input type="checkbox" class="form-check-input user-checkbox" value="' + user.user_id + '" data-user-name="' + escapeHtml(user.user_name) + '"></td>';

		// 프로필 이미지
		html += '<td class="text-center">';
		if (user.user_profile_image) {
			html += '<img src="' + user.user_profile_image + '" class="rounded-circle" style="width: 40px; height: 40px; object-fit: cover;" alt="프로필">';
		} else {
			html += '<div class="rounded-circle bg-light d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;"><i class="bi bi-person text-muted"></i></div>';
		}
		html += '</td>';

		// 이름
		html += '<td><strong>' + escapeHtml(user.user_name || '미입력') + '</strong>';
		if (user.master_yn === 'Y') {
			html += '<br><small class="text-primary">마스터</small>';
		}
		html += '</td>';

		// 이메일
		html += '<td><span>' + escapeHtml(user.user_mail) + '</span></td>';

		// 연락처
		// html += '<td><span>' + escapeHtml(user.user_hp || '미입력') + '</span></td>';

		// 권한
		html += '<td>' + getUserLevelBadge(user.level) + '</td>';

		// 관리메뉴
		html += '<td>' + getManagedMenusDisplay(user.managed_menus_display) + '</td>';

		// 관리그룹
		html += '<td>' + getManagedAreasDisplay(user.managed_areas_display) + '</td>';

		// 관리 버튼
		html += '<td>' + getUserActionButtons(user) + '</td>';

		html += '</tr>';
	});

	tbody.html(html);

	// 전체 선택 체크박스 초기화
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

	// 수정 버튼
	html += '<button type="button" class="btn btn-sm btn-outline-primary edit-user-btn" ';
	html += 'data-user-id="' + user.user_id + '" ';
	html += 'data-user-name="' + escapeHtml(user.user_name) + '" ';
	html += 'data-user-mail="' + escapeHtml(user.user_mail) + '" ';
	html += 'data-user-hp="' + escapeHtml(user.user_hp) + '" ';
	html += 'data-user-level="' + user.level + '" ';
	html += 'data-org-id="' + user.org_id + '">';
	html += '<i class="bi bi-pencil"></i></button> ';

	// 삭제 버튼 (마스터가 아닌 경우)
	if (user.master_yn !== 'Y') {
		html += '<button type="button" class="btn btn-sm btn-outline-danger delete-user-btn" ';
		html += 'data-user-id="' + user.user_id + '" ';
		html += 'data-user-name="' + escapeHtml(user.user_name) + '" ';
		html += 'data-org-id="' + user.org_id + '">';
		html += '<i class="bi bi-trash"></i></button> ';
	}

	// 로그인 버튼 (마스터 전용)
	// 세션 정보는 서버에서 확인해야 하므로 여기서는 생략

	return html;
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
 * Toast 메시지 표시 (타입별 색상)
 */
function showToast(message, type = 'info') {
	let toastContainer = $('.toast-container');
	if (toastContainer.length === 0) {
		$('body').append('<div class="toast-container position-fixed bottom-0 end-0 p-3"></div>');
		toastContainer = $('.toast-container');
	}

	let headerClass = 'bg-primary text-white';
	let icon = 'bi-info-circle';

	switch(type) {
		case 'success':
			headerClass = 'bg-success text-white';
			icon = 'bi-check-circle';
			break;
		case 'warning':
			headerClass = 'bg-warning text-dark';
			icon = 'bi-exclamation-triangle';
			break;
		case 'error':
			headerClass = 'bg-danger text-white';
			icon = 'bi-x-circle';
			break;
	}

	const toast = $('<div class="toast" role="alert" aria-live="assertive" aria-atomic="true">' +
		'<div class="toast-header ' + headerClass + '">' +
		'<i class="bi ' + icon + ' me-2"></i>' +
		'<strong class="me-auto">알림</strong>' +
		'<button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>' +
		'</div>' +
		'<div class="toast-body">' + message + '</div>' +
		'</div>');

	toastContainer.append(toast);

	const bsToast = new bootstrap.Toast(toast[0], {
		autohide: true,
		delay: 5000
	});
	bsToast.show();

	toast.on('hidden.bs.toast', function() {
		$(this).remove();
	});
}

// ========================= 이벤트 핸들러 추가 =========================

// 이메일 입력 시 실시간 미리보기 업데이트
$(document).on('input paste keyup', '#invite_emails', function() {
	// 짧은 지연 후 업데이트 (타이핑 중 너무 자주 업데이트 방지)
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

// 기존 단일 초대 함수는 호환성을 위해 유지하되 내부적으로 다중 초대 함수 호출
function inviteUser(inviteEmail, orgId) {
	inviteUsers(inviteEmail, orgId);
}
