'use strict'


// 그룹 수정 버튼 클릭 이벤트
$(document).on('click', '.btn-setting', function() {
    var groupId = $(this).data('group-id');
    var groupName = $(this).data('group-name');
    var leaderName = $(this).data('leader-name');
    var newName = $(this).data('new-name');

    $('#edit_group_id').val(groupId);
    $('#edit_group_name').val(groupName);
    $('#edit_leader_name').val(leaderName);
    $('#edit_new_name').val(newName);

    $('#settingGroupModal').modal('show');
});




// 그룹명 클릭 이벤트
$(document).on('click', '.open-group-main', function() {
    var groupId = $(this).closest('tr').data('group-id');
    var groupName = $(this).closest('tr').find('td:first-child').text().trim();
    var form = $('<form></form>');
    form.attr('method', 'post');
    form.attr('action', '/main/index');

    var groupIdField = $('<input></input>');
    groupIdField.attr('type', 'hidden');
    groupIdField.attr('name', 'group_id');
    groupIdField.attr('value', groupId);

    var groupNameField = $('<input></input>');
    groupNameField.attr('type', 'hidden');
    groupNameField.attr('name', 'group_name');
    groupNameField.attr('value', groupName);

    form.append(groupIdField);
    form.append(groupNameField);
    $(document.body).append(form);
    form.submit();
});





// 그룹 수정 저장 버튼 클릭 이벤트
$(document).on('click', '#updateGroup', function() {
    var groupId = $('#edit_group_id').val();
    var groupName = $('#edit_group_name').val();
    var leaderName = $('#edit_leader_name').val();
    var newName = $('#edit_new_name').val();

    $.ajax({
        url: '/mypage/update_group',
        type: 'POST',
        data: {
        group_id: groupId,
            group_name: groupName,
            leader_name: leaderName,
            new_name: newName
    },
    dataType: 'json',
        success: function(response) {
        if (response.status === 'success') {
            $('tr[data-group-id="' + groupId + '"] td:nth-child(1)').text(groupName);
            $('#settingGroupModal').modal('hide');
        } else {
            alert('그룹 수정에 실패했습니다.');
        }
    },
    error: function() {
        alert('서버 오류가 발생했습니다.');
    }
});
});



$(document).on('click', '#saveGroup', function() {
    var groupName = $('#group_name').val();

    if (groupName.trim() === '') {
        alert('그룹명을 입력해주세요.');
        return;
    }

    $.ajax({
        url: '/mypage/add_group',
        type: 'POST',
        data: { group_name: groupName },
    dataType: 'json',
        success: function(response) {
        if (response.status === 'success') {
            location.reload();
        } else {
            alert('그룹 추가에 실패했습니다.');
        }
    },
    error: function() {
        alert('서버 오류가 발생했습니다.');
    }
});
});

$(document).on('click', '.btn-del-group', function(e) {
    e.preventDefault();
    var groupId = $(this).data('group-id');

    if (confirm('정말로 그룹을 삭제하시겠습니까?')) {
        $.ajax({
            url: '/mypage/update_del_yn',
            type: 'POST',
            data: { group_id: groupId },
        dataType: 'json',
            success: function(response) {
            if (response.status === 'success') {
                $('tr[data-group-id="' + groupId + '"]').remove();
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

$(document).on('click', '.add-group', function() {
    $.ajax({
        url: '/mypage/add_group',
        type: 'POST',
        data: { group_name: '새그룹' },
    dataType: 'json',
        success: function(response) {
        if (response.status === 'success') {
            location.reload();
        } else {
            alert('그룹 추가에 실패했습니다.');
        }
    },
    error: function() {
        alert('서버 오류가 발생했습니다.');
    }
});
});




// 출석 타입 설정 버튼 클릭 이벤트
$(document).on('click', '.btn-attendance-type-setting', function() {
    var groupId = $(this).data('group-id');

    // 출석 타입 목록 가져오기
    $.ajax({
        url: '/mypage/get_attendance_types',
        type: 'POST',
        data: { group_id: groupId },
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
    var groupId = $('#attendanceTypeGroupId').val();

    $.ajax({
        url: '/mypage/add_attendance_type',
        type: 'POST',
        data: {
            att_type_name: '새출석타입',
            att_type_category_idx: selectedCategoryIdx,
            att_type_category_name: selectedCategoryName,
            att_type_nickname: '출',
            group_id: groupId
        },
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                alert('출석 타입이 추가되었습니다.');
                // 출석 타입 목록 다시 로드
                $('.btn-attendance-type-setting[data-group-id="' + groupId + '"]').trigger('click');
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
    var groupId = $('.btn-attendance-type-setting').data('group-id');
    var attTypeCategoryName = row.find('input[name="att_type_category_name"]').val();
    var attTypeName = row.find('input[name="att_type_name"]').val();
    var attTypeNickname = row.find('input[name="att_type_nickname"]').val();
    var attTypeColor = row.find('input[name="att_type_color"]').val().replace('#', '');

    $.ajax({
        url: '/mypage/save_attendance_type',
        type: 'POST',
        data: {
            group_id: groupId,
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
                $('.btn-attendance-type-setting[data-group-id="' + groupId + '"]').trigger('click');
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
                var groupId = $('#attendanceTypeGroupId').val();
                $('.btn-attendance-type-setting[data-group-id="' + groupId + '"]').trigger('click');
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
    // console.log(groupId);

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
                    var groupId = $('#attendanceTypeGroupId').val();
                    $('.btn-attendance-type-setting[data-group-id="' + groupId + '"]').trigger('click');
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






function attendanceTypeSetting(groupId) {
    $.ajax({
        url: '/mypage/attendance_type_setting',
        type: 'POST',
        data: { group_id: groupId },
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
                $('#attendanceTypeGroupId').val(groupId);
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
    var groupId = $('#attendanceTypeGroupId').val();
    $.ajax({
        url: '/mypage/add_attendance_type_category',
        type: 'POST',
        data: {
            att_type_category_name: newCategoryName,
            group_id : groupId
        },
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                alert('카테고리와 출석 타입이 추가되었습니다.');
                // 출석 타입 목록 다시 로드
                $('.btn-attendance-type-setting[data-group-id="' + groupId + '"]').trigger('click');
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
    var groupId = $(this).data('group-id');

    // 사용자 목록 가져오기
    $.ajax({
        url: '/mypage/get_group_users',
        type: 'POST',
        data: { group_id: groupId },
        dataType: 'json',
        success: function(response) {
            var tableBody = $('#userListTableBody');
            tableBody.empty();

            $.each(response, function(index, user) {
                var row = '<tr>';
                row += '<td>' + user.user_id + '</td>';
                row += '<td>' + user.user_name + '</td>';
                row += '<td>' + user.user_grade + '</td>';
                row += '<td>' + user.user_mail + '</td>';
                row += '<td>' + user.user_hp + '</td>';
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