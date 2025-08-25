/**
 * 파일 위치: assets/js/user_management.js
 * 역할: 사용자 관리 페이지의 JavaScript 기능 (관리 메뉴/그룹 포함)
 */

$(document).ready(function() {
    // Select2 초기화
    initializeSelect2();

    // 관리 메뉴 데이터 로드
    loadManagedMenus();
});

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
    // 현재 선택된 조직 ID가 없으면 쿠키에서 가져오기
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
 * 쿠키 값 가져오기
 */
function getCookie(name) {
    var value = "; " + document.cookie;
    var parts = value.split("; " + name + "=");
    if (parts.length == 2) return parts.pop().split(";").shift();
}

/**
 * 사용자 수정 모달 열기
 */
function openEditUserModal(userId, userName, userHp, level, orgId, managedMenus, managedAreas) {
    $('#edit_user_id').val(userId);
    $('#edit_org_id').val(orgId);
    $('#edit_user_name').val(userName);
    $('#edit_user_hp').val(userHp);

    // level 값을 명시적으로 설정
    $('#edit_user_level').val(level).trigger('change');

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

    // 기존 관리 그룹 선택값 설정 (데이터 로드 후 설정)
    if (managedAreas && Array.isArray(managedAreas)) {
        setTimeout(function() {
            $('#edit_managed_areas').val(managedAreas).trigger('change');
        }, 500);
    } else {
        setTimeout(function() {
            $('#edit_managed_areas').val([]).trigger('change');
        }, 500);
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

/**
 * 확인 모달 표시 함수
 */
function showConfirm(message, callback, title = '확인') {
    // 기존 확인 모달이 있다면 제거
    $('#confirmModal').remove();

    var modal = $('<div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalLabel" aria-hidden="true">' +
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

    // 확인 버튼 클릭 시 콜백 실행
    $('#confirmModalOkBtn').on('click', function() {
        $('#confirmModal').modal('hide');
        if (typeof callback === 'function') {
            callback();
        }
    });

    // 모달 숨김 후 제거
    $('#confirmModal').on('hidden.bs.modal', function() {
        $(this).remove();
    });

    $('#confirmModal').modal('show');
}

/**
 * Toast 메시지 표시
 */
function showToast(message) {
    var toastContainer = $('.toast-container');
    if (toastContainer.length === 0) {
        $('body').append('<div class="toast-container position-fixed bottom-0 end-0 p-3"></div>');
        toastContainer = $('.toast-container');
    }

    var toast = $('<div class="toast" role="alert" aria-live="assertive" aria-atomic="true">' +
        '<div class="toast-header">' +
        '<strong class="me-auto">알림</strong>' +
        '<button type="button" class="btn-close" data-bs-dismiss="toast"></button>' +
        '</div>' +
        '<div class="toast-body">' + message + '</div>' +
        '</div>');

    toastContainer.append(toast);

    var bsToast = new bootstrap.Toast(toast[0]);
    bsToast.show();

    toast.on('hidden.bs.toast', function() {
        $(this).remove();
    });
}

// ========================= 이벤트 핸들러 =========================

// 권한 레벨 변경 시 필드 표시/숨김 처리
$(document).on('change', '#edit_user_level', function() {
    var level = $(this).val();
    toggleManagementFields(level);
});

// 사용자 수정 버튼 클릭 이벤트
$(document).on('click', '.edit-user-btn', function() {
    var userId = $(this).data('user-id');
    var userName = $(this).data('user-name');
    var userHp = $(this).data('user-hp');
    var level = $(this).data('user-level');
    var orgId = $(this).data('org-id');

    // 관리 메뉴와 관리 그룹 정보 조회
    getUserManagementInfo(userId, function(managedMenus, managedAreas) {
        openEditUserModal(userId, userName, userHp, level, orgId, managedMenus, managedAreas);
    });
});

// 사용자 수정 폼 제출
$(document).on('submit', '#editUserForm', function(e) {
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

// 사용자 삭제 버튼 클릭 이벤트
$(document).on('click', '.delete-user-btn', function() {
    var userId = $(this).data('user-id');
    var userName = $(this).data('user-name');
    var orgId = $(this).data('org-id');

    showConfirm(
        userName + ' 사용자를 조직에서 제외하시겠습니까?<br><br>이 작업은 되돌릴 수 없습니다.',
        function() {
            $.ajax({
                url: 'user_management/delete_user',
                type: 'POST',
                data: {
                    target_user_id: userId,
                    org_id: orgId
                },
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
                    showToast('사용자 삭제 중 오류가 발생했습니다.');
                }
            });
        },
        '사용자 삭제 확인'
    );
});

// 사용자 초대 폼 제출
$(document).on('submit', '#inviteUserForm', function(e) {
    e.preventDefault();

    var inviteEmail = $('#invite_email').val();
    var orgId = getCookie('activeOrg');

    if (!inviteEmail) {
        showToast('초대할 이메일 주소를 입력해주세요.');
        return;
    }

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
});