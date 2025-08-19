'use strict'


// 그룹 수정 버튼 클릭 이벤트
$(document).on('click', '.btn-setting', function() {
    var orgId = $(this).data('org-id');
    var orgName = $(this).data('org-name');
    var leaderName = $(this).data('leader-name');
    var newName = $(this).data('new-name');

    $('#edit_org_id').val(orgId);
    $('#edit_org_name').val(orgName);
    $('#edit_leader_name').val(leaderName);
    $('#edit_new_name').val(newName);

    $('#settingOrgModal').modal('show');
});




// 그룹명 클릭 이벤트
$(document).on('click', '.open-org-main', function() {
    var orgId = $(this).closest('tr').data('org-id');
    var orgName = $(this).closest('tr').find('td:first-child').text().trim();
    var form = $('<form></form>');
    form.attr('method', 'post');
    form.attr('action', '/main/index');

    var orgIdField = $('<input></input>');
    orgIdField.attr('type', 'hidden');
    orgIdField.attr('name', 'org_id');
    orgIdField.attr('value', orgId);

    var orgNameField = $('<input></input>');
    orgNameField.attr('type', 'hidden');
    orgNameField.attr('name', 'org_name');
    orgNameField.attr('value', orgName);

    form.append(orgIdField);
    form.append(orgNameField);
    $(document.body).append(form);
    form.submit();
});





// 그룹 수정 저장 버튼 클릭 이벤트
$(document).on('click', '#updateOrg', function() {
    var orgId = $('#edit_org_id').val();
    var orgName = $('#edit_org_name').val();
    var leaderName = $('#edit_leader_name').val();
    var newName = $('#edit_new_name').val();

    $.ajax({
        url: '/mypage/update_org',
        type: 'POST',
        data: {
        org_id: orgId,
            org_name: orgName,
            leader_name: leaderName,
            new_name: newName
    },
    dataType: 'json',
        success: function(response) {
        if (response.status === 'success') {
            $('tr[data-org-id="' + orgId + '"] td:nth-child(1)').text(orgName);
            $('#settingOrgModal').modal('hide');
        } else {
            alert('그룹 수정에 실패했습니다.');
        }
    },
    error: function() {
        alert('서버 오류가 발생했습니다.');
    }
});
});


$(document).on('click', '#saveOrg', function () {
    var orgName = $('#org_name').val();

    if (orgName.trim() === '') {
        alert('그룹명을 입력해주세요.');
        return;
    }

    $.ajax({
        url: '/mypage/add_org',
        type: 'POST',
        data: {org_name: orgName},
        dataType: 'json',
        success: function (response) {
            if (response.status === 'success') {
                location.reload();
            } else {
                alert('그룹 추가에 실패했습니다.');
            }
        },
        error: function () {
            alert('서버 오류가 발생했습니다.');
        }
    });
});



$(document).on('click', '#saveInvate', function () {
    var inviteCode = $('#invite_code').val();
    var userId = userInfo.currentUserId;

    if (inviteCode.trim() === '') {
        alert('그룹리더에게 받은 초대코드를 입력해주세요.');
        return;
    }

    $.ajax({
        url: '/mypage/join_org_by_invite_code',
        type: 'POST',
        data: {
            invite_code: inviteCode,
            user_id: userId
        },
        dataType: 'json',
        success: function (response) {
            if (response.status === 'success') {
                alert('초대코드로 그룹에 가입되었습니다.');
                location.reload();
            } else if (response.status === 'already_joined') {
                alert('이미 해당 그룹에 가입되어 있습니다.');
            } else if (response.status === 'invalid_code') {
                alert('유효하지 않은 초대코드입니다.');
            } else {
                alert('그룹 가입에 실패했습니다.');
            }
        },
        error: function () {
            alert('서버 오류가 발생했습니다.');
        }
    });

});


$(document).on('click', '.btn-del-org', function(e) {
    e.preventDefault();
    var orgId = $(this).data('org-id');

    if (confirm('삭제 후 복구가 불가합니다.\n정말로 그룹을 삭제하시겠습니까?')) {
        $.ajax({
            url: '/mypage/update_del_yn',
            type: 'POST',
            data: { org_id: orgId },
        dataType: 'json',
            success: function(response) {
            if (response.status === 'success') {
                $('tr[data-org-id="' + orgId + '"]').remove();
            } else {
                alert('그룹 삭제에 실패했습니다.');
            }
        },
        error: function() {
            alert('서버 오류가 발생했습니다.');
        }
    });
    }
});

$(document).on('click', '.add-org', function () {
    if (confirm('그룹을 추가하시겠습니까?')) {
        $.ajax({
            url: '/mypage/add_org',
            type: 'POST',
            data: {org_name: '새그룹'},
            dataType: 'json',
            success: function (response) {
                if (response.status === 'success') {
                    location.reload();
                } else {
                    alert('그룹 추가에 실패했습니다.');
                }
            },
            error: function () {
                alert('서버 오류가 발생했습니다.');
            }
        });
    }
});

$(document).on('click', '.btn-add-org', function () {
    $('#addOrgModal').modal('show');
});


// 출석 타입 설정 버튼 클릭 이벤트
$(document).on('click', '.btn-attendance-type-setting', function() {
    var orgId = $(this).data('org-id');

    // 출석 타입 목록 가져오기
    $.ajax({
        url: '/mypage/get_attendance_types',
        type: 'POST',
        data: { org_id: orgId },
        dataType: 'json',
        success: function(response) {
            var tableBody = $('#attendanceTypeTableBody');
            tableBody.empty();

            if (response.length === 0) {
                var row = '<tr><td colspan="6" style="height: 60px">출석타입설정이 없습니다.</td></tr>';
                tableBody.append(row);
                $('.add-category-wrap').addClass('off'); // .add-category 요소 숨기기
            } else {
                $.each(response, function(index, attendanceType) {
                    var row = '<tr>';
                    row += '<td>' + attendanceType.att_type_category_name + '</td>';
                    row += '<td>' + attendanceType.att_type_name + '</td>';
                    row += '<td>' + attendanceType.att_type_nickname + '</td>';
                    row += '<td><input type="color" class="btn-color" value="#' + attendanceType.att_type_color + '"></td>';
                    row += '<td><button type="button" class="btn btn-xs btn-primary btn-edit-attendance-type" data-attendance-type-idx="' + attendanceType.att_type_idx + '">수정</button></td>';
                    row += '<td><button type="button" class="btn btn-xs btn-danger btn-delete-attendance-type" data-attendance-type-idx="' + attendanceType.att_type_idx + '">삭제</button></td>';
                    row += '</tr>';
                    if (index > 0 && attendanceType.att_type_category_idx !== response[index - 1].att_type_category_idx) {
                        tableBody.append('<tr style="border-bottom: 1px solid #666"></tr>');
                    }
                    tableBody.append(row);
                });
                $('.add-category-wrap').removeClass('off') // .add-category 요소 보이기
            }

            $('#attendanceTypeModal').modal('show');
        },
        error: function() {
            alert('출석 타입 목록을 가져오는데 실패했습니다.');
        }
    });
});

// 출석 타입 추가 버튼 클릭 이벤트
$(document).on('click', '#addAttendanceType', function() {
    var selectedCategoryIdx = $('#selectAttendanceTypeCategory').val();
    var selectedCategoryName = $('#selectAttendanceTypeCategory option:selected').text();
    var orgId = $('#attendanceTypeOrgId').val();

    $.ajax({
        url: '/mypage/add_attendance_type',
        type: 'POST',
        data: {
            att_type_name: '새출석타입',
            att_type_category_idx: selectedCategoryIdx,
            att_type_category_name: selectedCategoryName,
            att_type_nickname: '출',
            org_id: orgId
        },
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                alert('출석 타입이 추가되었습니다.');
                // 출석 타입 목록 다시 로드
                $('.btn-attendance-type-setting[data-org-id="' + orgId + '"]').trigger('click');
            } else {
                alert('출석 타입 추가에 실패했습니다.');
            }
        },
        error: function() {
            alert('출석 타입 추가 중 오류가 발생했습니다.');
        }
    });
});

// 출석 타입 저장 버튼 클릭 이벤트
$(document).on('click', '.btn-save-attendance-type', function() {
    var row = $(this).closest('tr');
    var orgId = $('.btn-attendance-type-setting').data('org-id');
    var attTypeCategoryName = row.find('input[name="att_type_category_name"]').val();
    var attTypeName = row.find('input[name="att_type_name"]').val();
    var attTypeNickname = row.find('input[name="att_type_nickname"]').val();
    var attTypeColor = row.find('input[name="att_type_color"]').val().replace('#', '');

    $.ajax({
        url: '/mypage/save_attendance_type',
        type: 'POST',
        data: {
            org_id: orgId,
            att_type_category_name: attTypeCategoryName,
            att_type_name: attTypeName,
            att_type_nickname: attTypeNickname,
            att_type_color: attTypeColor
        },
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                alert('출석 타입이 저장되었습니다.');
                // 출석 타입 목록 다시 로드
                $('.btn-attendance-type-setting[data-org-id="' + orgId + '"]').trigger('click');
            } else {
                alert('출석 타입 저장에 실패했습니다.');
            }
        },
        error: function() {
            alert('출석 타입 저장 중 오류가 발생했습니다.');
        }
    });
});



//모달 닫힐 때 입력 필드 초기화 코드
$('#attendanceTypeModal').on('hidden.bs.modal', function () {
    $('#attendanceTypeTableBody').empty();
});

// 출석 타입 수정 버튼 클릭 이벤트
$(document).on('click', '.btn-edit-attendance-type', function() {
    var row = $(this).closest('tr');
    var attTypeIdx = $(this).data('attendance-type-idx');
    var attTypeName = row.find('td:eq(1)').text();
    var attTypeNickname = row.find('td:eq(2)').text();
    var attTypeColor = row.find('input[type="color"]').val();

    row.find('td:eq(1)').html('<input type="text" class="form-control form-control-sm" name="att_type_name" value="' + attTypeName + '">');
    row.find('td:eq(2)').html('<input type="text" class="form-control form-control-sm" name="att_type_nickname" value="' + attTypeNickname + '">');
    row.find('td:eq(3)').html('<input type="color" class="btn-color" name="att_type_color" value="' + attTypeColor + '">');
    row.find('td:eq(4)').html('<button type="button" class="btn btn-primary btn-xs btn-update-attendance-type" data-attendance-type-idx="' + attTypeIdx + '">저장</button>');
});

// 출석 타입 수정 버튼 클릭 이벤트 (수정 후)
$(document).on('click', '.btn-update-attendance-type', function() {
    var row = $(this).closest('tr');
    var attTypeIdx = $(this).data('attendance-type-idx');
    var attTypeName = row.find('input[name="att_type_name"]').val();
    var attTypeNickname = row.find('input[name="att_type_nickname"]').val();
    var attTypeColor = row.find('input[name="att_type_color"]').val().replace('#', '');

    $.ajax({
        url: '/mypage/update_attendance_type',
        type: 'POST',
        data: {
            att_type_idx: attTypeIdx,
            att_type_name: attTypeName,
            att_type_nickname: attTypeNickname,
            att_type_color: attTypeColor
        },
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                alert('출석 타입이 수정되었습니다.');
                // 출석 타입 목록 다시 로드
                var orgId = $('#attendanceTypeOrgId').val();
                $('.btn-attendance-type-setting[data-org-id="' + orgId + '"]').trigger('click');
            } else {
                alert('출석 타입 수정에 실패했습니다.');
            }
        },
        error: function() {
            alert('출석 타입 수정 중 오류가 발생했습니다.');
        }
    });
});

// 출석 타입 삭제 버튼 클릭 이벤트
$(document).on('click', '.btn-delete-attendance-type', function() {
    var attTypeIdx = $(this).data('attendance-type-idx');
    // console.log(orgId);

    if (confirm('정말로 출석 타입을 삭제하시겠습니까?')) {
        $.ajax({
            url: '/mypage/delete_attendance_type',
            type: 'POST',
            data: { att_type_idx: attTypeIdx },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    alert('출석 타입이 삭제되었습니다.');
                    // 출석 타입 목록 다시 로드
                    var orgId = $('#attendanceTypeOrgId').val();
                    $('.btn-attendance-type-setting[data-org-id="' + orgId + '"]').trigger('click');
                } else {
                    alert('출석 타입 삭제에 실패했습니다.');
                }
            },
            error: function() {
                alert('출석 타입 삭제 중 오류가 발생했습니다.');
            }
        });
    }
});






function attendanceTypeSetting(orgId) {
    $.ajax({
        url: '/mypage/attendance_type_setting',
        type: 'POST',
        data: { org_id: orgId },
        dataType: 'json',
        success: function(response) {

            // console.log(response);


            if (response.status === 'success') {
                var modalBody = $('#attendanceTypeModal .modal-body');
                // modalBody.empty();

                // 출석 타입 카테고리 선택 옵션 생성
                var selectOptions = '';
                response.attendance_type_categories.forEach(function(category) {
                    selectOptions += '<option value="' + category.att_type_category_idx + '">' + category.att_type_category_name + '</option>';
                });
                $('#selectAttendanceTypeCategory').html(selectOptions);
                $('#attendanceTypeOrgId').val(orgId);
                // 모달 표시
                $('#attendanceTypeModal').modal('show');
            } else {
                alert('출석 타입 설정을 가져오는데 실패했습니다.');
            }
        },
        error: function() {
            alert('출석 타입 설정을 가져오는데 실패했습니다.');
        }
    });
}


$(document).on('click', '#addAttendanceTypeCategory', function() {
    var newCategoryName = $('#inputAttendanceTypeCategory').val();
    var orgId = $('#attendanceTypeOrgId').val();
    $.ajax({
        url: '/mypage/add_attendance_type_category',
        type: 'POST',
        data: {
            att_type_category_name: newCategoryName,
            org_id : orgId
        },
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                alert('카테고리와 출석 타입이 추가되었습니다.');
                // 출석 타입 목록 다시 로드
                $('.btn-attendance-type-setting[data-org-id="' + orgId + '"]').trigger('click');
            } else {
                alert('카테고리와 출석 타입 추가에 실패했습니다.');
            }
        },
        error: function() {
            alert('카테고리와 출석 타입 추가 중 오류가 발생했습니다.');
        }
    });
});




// 사용자 목록 버튼 클릭 이벤트
$(document).on('click', '.btn-user-setting', function() {
    var orgId = $(this).data('org-id');
    $('#userListModal').data('org-id', orgId);

    // 사용자 목록 가져오기
    $.ajax({
        url: '/mypage/get_org_users',
        type: 'POST',
        data: { org_id: orgId },
        dataType: 'json',
        success: function(response) {
            var tableBody = $('#userListTableBody');
            tableBody.empty();



            $.each(response, function(index, user) {
                var row = '<tr data-user-idx="' + user.idx + '">';
                row += '<td>' + user.user_id + '</td>';
                row += '<td><input type="text" class="form-control" name="user_name" value="' + user.user_name + '"></td>';
                row += '<td>' + user.user_mail + '</td>';
                row += '<td><input type="text" class="form-control" name="user_hp" value="' + user.user_hp + '"></td>';
                row += '<td><select class="form-select" name="level">';
                for (var i = 1; i <= 10; i++) {
                    row += '<option value="' + i + '"' + (user.level == i ? ' selected' : '') + '>' + i + '</option>';
                }
                row += '</select></td>';
                row += '<td><button type="button" class="btn btn-sm btn-primary btn-save-user">저장</button></td>';
                row += '<td><button type="button" class="btn btn-sm btn-danger btn-delete-user">삭제</button></td>';

                if (userInfo.currentUserMasterYn === 'Y') {
                    row += '<td><button type="button" class="btn btn-sm btn-primary btn-login-user">로그인</button></td>';
                    $('.master-hidden').removeClass('master-hidden');
                }

                row += '</tr>';

                tableBody.append(row);
            });

            $('#userListModal').modal('show');
        },
        error: function() {
            alert('사용자 목록을 가져오는데 실패했습니다.');
        }
    });
});

// 엑셀 업로드 버튼 클릭 이벤트
$(document).on('click', '.btn-member-excel-upload', function(e) {
    e.stopPropagation();
    var orgId = $(this).closest('tr').data('org-id');
    $('#excelUploadModal').data('org-id', orgId);
    $('#excelUploadModal').modal('show');
});



// 업로드 시작 버튼 클릭 이벤트
$(document).on('click', '#startUpload', function() {
    var orgId = $('#excelUploadModal').data('org-id');
    var file = $('#excelFile')[0].files[0];

    if (file) {
        var formData = new FormData();
        formData.append('excel_file', file);
        formData.append('org_id', orgId);

        $('.progress').show(); // 프로그레스 바 표시

        $.ajax({
            url: '/mypage/excel_upload',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json', // 응답 데이터를 JSON으로 파싱
            xhr: function() {
                var xhr = new window.XMLHttpRequest();
                xhr.upload.addEventListener('progress', function(evt) {
                    if (evt.lengthComputable) {
                        var percentComplete = evt.loaded / evt.total * 100;
                        $('.progress-bar').css('width', percentComplete + '%');
                        $('.progress-bar').attr('aria-valuenow', percentComplete);
                    }
                }, false);
                return xhr;
            },


            success: function(response) {
                console.log(response);
                if (response.status === 'success') {

                    alert('엑셀 업로드에 성공하였습니다.');
                    $('#excelUploadModal').modal('hide');
                } else {
                    alert('엑셀 업로드에 실패했습니다: ' + response.message);
                }
                $('.progress').hide(); // 프로그레스 바 숨김
            },
            error: function(xhr, status, error) {
                alert('서버 오류가 발생했습니다: ' + error);
                $('.progress').hide(); // 프로그레스 바 숨김
            }
        });
    } else {
        alert('엑셀 파일을 선택해주세요.');
    }



});




$(document).on('click', '.btn-print-qr', function() {
    var orgId = $(this).data('org-id');
    $('#printLabel01').data('org-id', orgId);
    $('#qrPrintModal').modal('show');
});

$(document).on('click', '#printLabel01', function() {
    var orgId = $(this).data('org-id');
    var url = '/mypage/print_qr?org_id=' + orgId;
    window.open(url, '_blank', 'width=800,height=600');
});


$(document).on('click', '.btn-summery', function() {
    var orgId = $(this).data('org-id');
    $('#summery01').data('org-id', orgId);
    $('#summery02').data('org-id', orgId);
    $('#summeryModal').modal('show');
});

$(document).on('click', '#summery01', function() {
    var orgId = $(this).data('org-id');
    var url = '/mypage/summery_week?org_id=' + orgId;
    window.open(url, '_blank', 'width=800,height=600');
});
$(document).on('click', '#summery02', function() {
    var orgId = $(this).data('org-id');
    var url = '/mypage/summery_member?org_id=' + orgId;
    window.open(url, '_blank', 'width=800,height=600');
});




$(document).ready(function () {



    // 사용자 정보 저장 버튼 클릭 이벤트
    $(document).on('click', '.btn-save-user', function() {
        var row = $(this).closest('tr');
        var userId = row.find('td:eq(0)').text();
        var userName = row.find('input[name="user_name"]').val();
        var userHp = row.find('input[name="user_hp"]').val();
        var level = row.find('select[name="level"]').val();
        var orgId = $('#userListModal').data('org-id');

        $.ajax({
            url: '/mypage/save_user',
            type: 'POST',
            data: {
                user_id: userId,
                user_name: userName,
                user_hp: userHp,
                level: level,
                org_id: orgId
            },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    alert('사용자 정보가 저장되었습니다.');
                    // 사용자 목록 다시 로드
                    $('.btn-user-setting[data-org-id="' + orgId + '"]').trigger('click');
                } else {
                    alert('사용자 정보 저장에 실패했습니다.');
                }
            },
            error: function() {
                alert('사용자 정보 저장 중 오류가 발생했습니다.');
            }
        });
    });


    // 사용자 삭제 버튼 클릭 이벤트
    $(document).on('click', '.btn-delete-user', function() {
        var userId = $(this).closest('tr').find('td:eq(0)').text();
        var orgId = $('#userListModal').data('org-id');

        if (confirm('정말로 사용자를 삭제하시겠습니까?')) {
            $.ajax({
                url: '/mypage/delete_user',
                type: 'POST',
                data: {
                    user_id: userId,
                    org_id: orgId
                },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        alert('사용자가 삭제되었습니다.');
                        // 사용자 목록 다시 로드
                        var orgId = $('#userListModal').data('org-id');
                        $('.btn-user-setting[data-org-id="' + orgId + '"]').trigger('click');
                    } else {
                        alert('사용자 삭제에 실패했습니다.');
                    }
                },
                error: function() {
                    alert('사용자 삭제 중 오류가 발생했습니다.');
                }
            });
        }
    });

    // 초대 메일 발송 버튼 클릭 이벤트
    $(document).on('click', '#invite-user', function() {
        var email = $('.form-control[aria-label="invite-email"]').val();
        var orgId = $('#userListModal').data('org-id');

        if (email.trim() === '') {
            alert('메일 주소를 입력해주세요.');
            return;
        }

        $.ajax({
            url: '/mypage/invite_user',
            type: 'POST',
            data: {
                email: email,
                org_id: orgId
            },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    alert('초대 메일이 발송되었습니다.');
                    $('input[name="invite-email"]').val('');
                    // 사용자 목록에 새로운 사용자 추가
                    var newRow = '<tr>';
                    newRow += '<td></td>';
                    newRow += '<td><input type="text" class="form-control" name="user_name" value="새사용자"></td>';
                    newRow += '<td><select class="form-select" name="user_grade"><option value="0" selected>0</option></select></td>';
                    newRow += '<td><button type="button" class="btn btn-xs btn-primary btn-save-user">저장</button></td>';
                    newRow += '<td><button type="button" class="btn btn-xs btn-danger btn-delete-user">삭제</button></td>';
                    newRow += '<td>' + email + '</td>';
                    newRow += '<td></td>';
                    newRow += '</tr>';
                    $('#userListTableBody').append(newRow);
                } else if (response.status === 'exists') {
                    alert('이미 등록된 메일입니다.');
                } else {
                    alert('초대 메일 발송에 실패했습니다.');
                }
            },
            error: function() {
                alert('초대 메일 발송 중 오류가 발생했습니다.');
            }
        });
    });


    $(document).on('click', '.btn-login-user', function() {
        var userId = $(this).closest('tr').find('td:eq(0)').text();

        $.ajax({
            url: '/mypage/login_as_user',
            type: 'POST',
            data: { user_id: userId },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    alert('해당 사용자로 로그인되었습니다.');
                    location.reload(); // 페이지 리로드
                } else {
                    alert('사용자 로그인에 실패했습니다.');
                }
            },
            error: function() {
                alert('사용자 로그인 중 오류가 발생했습니다.');
            }
        });
    });


})
