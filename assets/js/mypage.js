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


