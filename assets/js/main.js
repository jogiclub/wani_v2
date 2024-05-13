'use strict'







// 현재 선택된 주차 범위 가져오기 함수 추가
/*
function getCurrentWeekRange() {

    var currentDate = new Date();
    var startDate = getWeekStartDate(currentDate);
    var endDate = getWeekEndDate(currentDate);
    var weekNumber = getWeekNumber(currentDate);
    return `${startDate}~${endDate} (${weekNumber}주차)`;
}
*/






// 쿠키에 저장된 att-type-idx 값 가져오기
// var attTypeIdxCookie = getCookie('att-type-idx');

// if (attTypeIdxCookie) {
// 쿠키 값이 있으면 해당 값으로 dropdown-toggle-att-type 설정
// var attTypeName = $('.dropdown-att-type .dropdown-item[data-att-type-idx="' + attTypeIdxCookie + '"]').text();
// $('#dropdown-toggle-att-type').text(attTypeName).data('att-type-idx', attTypeIdxCookie);
// } else {
// 쿠키 값이 없으면 첫 번째 출석 유형으로 설정
var firstAttType = $('.dropdown-att-type .dropdown-item:first');
var attTypeName = firstAttType.text();
var attTypeIdx = firstAttType.data('att-type-idx');
$('#dropdown-toggle-att-type').text(attTypeName).data('att-type-idx', attTypeIdx);
// setCookie('att-type-idx', attTypeIdx, 7);
// }

// dropdown-att-type 항목 클릭 이벤트
$('.dropdown-att-type .dropdown-item').click(function(e) {
    e.preventDefault();
    var attTypeName = $(this).text();
    var attTypeIdx = $(this).data('att-type-idx');
    $('#dropdown-toggle-att-type').text(attTypeName).data('att-type-idx', attTypeIdx);
    // setCookie('att-type-idx', attTypeIdx, 7);
});



// 라디오 버튼 그룹에 변경 이벤트 리스너 추가



function applyModeConfig(mode) {
    const config = modeConfig[mode];
    $('.att-stamp-warp').toggleClass('hidden', config.attStampWarp === 'addClass');
    $('.memo-stamp-warp').toggleClass('hidden', config.memoStampWarp === 'addClass');
    $('#input-search').prop('disabled', config.inputSearch.disabled)
        .val(config.inputSearch.val)
        .attr('placeholder', config.inputSearch.placeholder);
    if (config.inputSearch.focus) {
        $('#input-search').focus();
    }
    if (config.resetMemberList) {
        resetMemberList();
    }
    $('.offcanvas').offcanvas('hide'); // 모든 offcanvas 숨기기

    // 모드에 따라 updateFunction 호출
    var currentWeekRange = $('.current-week').text();
    var currentDate = getDateFromWeekRange(currentWeekRange);
    var startDate = getWeekStartDate(currentDate);
    var endDate = getWeekEndDate(currentDate);
    var activeGroupId = getCookie('activeGroup');

    if (mode === 'mode-1') {
        updateAttStamps(activeGroupId, startDate, endDate);
    } else if (mode === 'mode-3') {
        updateMemoStamps(activeGroupId, startDate, endDate);
    }

    // .total-att-list 표시/숨김 처리
    if (mode === 'mode-1') {
        $('.total-att-list').show();
    } else {
        $('.total-att-list').hide();
    }

    // .total-memo-list 표시/숨김 처리
    if (mode === 'mode-3') {
        $('.total-memo-list').show();
    } else {
        $('.total-memo-list').hide();
    }

}

const modeConfig = {
    'mode-1': {
        attStampWarp: 'removeClass',
        memoStampWarp: 'addClass',
        inputSearch: {
            disabled: false,
            val: '',
            placeholder: '이름검색 또는 QR체크!',
            focus: true
        },
        updateFunction: function(activeGroupId, startDate, endDate) {
            updateAttStamps(activeGroupId, startDate, endDate);
        }
    },
    'mode-2': {
        attStampWarp: 'addClass',
        memoStampWarp: 'addClass',
        inputSearch: {
            disabled: true,
            val: '관리모드 사용 중...',
            placeholder: '',
            focus: false
        },
        resetMemberList: true
    },
    'mode-3': {
        attStampWarp: 'addClass',
        memoStampWarp: 'removeClass',
        inputSearch: {
            disabled: true,
            val: '메모모드 사용 중...',
            placeholder: '',
            focus: false
        },
        resetMemberList: true,
        updateFunction: function(activeGroupId, startDate, endDate) {
            updateMemoStamps(activeGroupId, startDate, endDate);
        }
    },
    'mode-4': {
        attStampWarp: 'addClass',
        memoStampWarp: 'addClass',
        inputSearch: {
            disabled: true,
            val: '생일모드 사용 중...',
            placeholder: '',
            focus: false
        },
        resetMemberList: true
    }
};






// 멤버 카드 클릭 이벤트 처리
$(document).on('click', '.member-card', function() {
    var memberIdx = $(this).attr('member-idx');
    var memberName = $(this).find('.member-name').text().trim();

    var selectedMode = $('.mode-list .btn-check:checked').attr('id');

    if (selectedMode === 'mode-1') {
        // 출석모드
        var memberIdx = $(this).attr('member-idx');
        var memberName = $(this).find('.member-name').text().trim();

        var modalTitle = memberName + ' 님의 출석';

        $('#selectedMemberIdx').val(memberIdx);
        $('#attendanceModalLabel').text(modalTitle);

        // 현재 선택된 주차 범위 가져오기
        var currentWeekRange = $('.current-week').text();
        var currentDate = getDateFromWeekRange(currentWeekRange);
        var startDate = getWeekStartDate(currentDate);
        var endDate = getWeekEndDate(currentDate);

        // 멤버의 출석 데이터 불러오기
        loadMemberAttendance(memberIdx, startDate, endDate);
    } else if (selectedMode === 'mode-2') {
        // 관리모드
        $('#memberIdx').val(memberIdx);
        $('#memberOffcanvasLabel').text(memberName + ' 님의 정보 수정');
        loadMemberInfo(memberIdx);
        $('#memberOffcanvas').offcanvas('show');
    } else if (selectedMode === 'mode-3') {
        // 메모모드
        memoPage = 1;
        $('.memo-list ul').empty();
        $('#memoMemberIdx').val(memberIdx);
        $('#addMemoOffcanvasLabel').text(memberName + ' 님의 메모 추가');
        $('#addMemoOffcanvas').offcanvas('show');
        loadMemoList(memberIdx);
    }
});



$('#saveMember').click(function() {
    var formData = new FormData($('#memberForm')[0]);
    var allGradeCheck = $('#allGradeCheck').prop('checked');
    var allAreaCheck = $('#allAreaCheck').prop('checked');

    if (allGradeCheck || allAreaCheck) {
        updateMultipleMembers(formData, allGradeCheck, allAreaCheck);
    } else {
        saveMemberInfo(formData);
    }
});


function updateMultipleMembers(formData, allGradeCheck, allAreaCheck) {
    var memberIdx = $('#memberIdx').val();
    formData.append('memberIdx', memberIdx);
    formData.append('allGradeCheck', allGradeCheck);
    formData.append('allAreaCheck', allAreaCheck);

    $.ajax({
        url: '/main/update_multiple_members',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                $('#memberOffcanvas').offcanvas('hide');
                // 멤버 정보 업데이트 후 필요한 작업 수행
                var currentWeekRange = $('.current-week').text();
                var currentDate = getDateFromWeekRange(currentWeekRange);
                var startDate = getWeekStartDate(currentDate);
                var endDate = getWeekEndDate(currentDate);
                var activeGroupId = getCookie('activeGroup');
                loadMembers(activeGroupId, startDate, endDate);
                $('#allGradeCheck').prop('checked', false);
                $('#allAreaCheck').prop('checked', false);
            } else {
                alert('멤버 정보 업데이트에 실패했습니다.');
            }
        }
    });
}




// 멤버 삭제 버튼 클릭 이벤트
$('#delMember').click(function() {
    var memberIdx = $('#memberIdx').val();
    var memberName = $('#memberName').val();

    if (confirm('정말 ' + memberName + '님을 삭제하시겠습니까?')) {
        deleteMember(memberIdx);
    }
});

function deleteMember(memberIdx) {
    $.ajax({
        url: '/main/delete_member',
        method: 'POST',
        data: { member_idx: memberIdx },
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                $('#memberOffcanvas').offcanvas('hide');
                // 멤버 삭제 후 필요한 작업 수행
                var currentWeekRange = $('.current-week').text();
                var currentDate = getDateFromWeekRange(currentWeekRange);
                var startDate = getWeekStartDate(currentDate);
                var endDate = getWeekEndDate(currentDate);
                var activeGroupId = getCookie('activeGroup');
                loadMembers(activeGroupId, startDate, endDate);
            } else {
                alert('멤버 삭제에 실패했습니다.');
            }
        }
    });
}


/// 모드 버튼 클릭 이벤트 처리
$('.mode-list .btn-check').on('click', function() {
    var selectedMode = $(this).attr('id');
    applyModeConfig(selectedMode);
    setCookie('selectedMode', selectedMode, 7); // 쿠키에 선택한 모드 저장 (유효기간 7일)
});




//새로운 멤버를 추가
$('#saveNewMember').click(function() {
    var member_name = $('#member_name').val();
    var activeGroupId = getCookie('activeGroup');

    $.ajax({
        url: '/main/add_member',
        method: 'POST',
        data: {
            group_id: activeGroupId,
            member_name: member_name
        },
        dataType: 'json',
        success: function(response) {
            if (response.status == 'success') {
                $('#newMemberModal').modal('hide');
                $('#member_name').val('');

                // 현재 선택된 주차 범위 가져오기
                var currentWeekRange = $('.current-week').text();
                var currentDate = getDateFromWeekRange(currentWeekRange);
                var startDate = getWeekStartDate(currentDate);
                var endDate = getWeekEndDate(currentDate);

                // 멤버 목록 업데이트
                loadMembers(activeGroupId, startDate, endDate);
            } else {
                alert('멤버 추가에 실패했습니다.');
            }
        }
    });
});

// 멤버 정보 로드
function loadMemberInfo(memberIdx) {
    $.ajax({
        url: '/main/get_member_info',
        method: 'POST',
        data: { member_idx: memberIdx },
        dataType: 'json',
        success: function(response) {
            $('#groupId').val(response.group_id);
            $('#grade').val(response.grade);
            $('#area').val(response.area);
            $('#memberName').val(response.member_name);
            $('#memberNick').val(response.member_nick);
            $('#memberPhone').val(response.member_phone);
            $('#memberBirth').val(response.member_birth);
            $('#school').val(response.school);
            $('#address').val(response.address);
            $('#memberEtc').val(response.member_etc);
            $('#leaderYn').prop('checked', response.leader_yn === 'Y');
            $('#newYn').prop('checked', response.new_yn === 'Y');

            // 이미지 초기화
            $('#photo').val('');
            $('#image-preview').empty();

            // 기존 이미지 표시
            if (response.photo) {
                var photoUrl = '/uploads/member_photos/' + response.group_id + '/' + response.photo;
                $('.member-photo').css('background', 'url(' + photoUrl + ') center center/cover');
            } else {
                $('.member-photo').css('background', '');
            }
        }
    });
}

function saveMemberInfo(formData) {
    var file = $('#photo')[0].files[0];

    if (file) {
        var reader = new FileReader();
        reader.onload = function (e) {
            var img = new Image();
            img.onload = function () {
                var canvas = document.createElement('canvas');
                var ctx = canvas.getContext('2d');
                var maxWidth = 200; // 원하는 최대 너비로 설정
                var maxHeight = 200; // 원하는 최대 높이로 설정
                var width = img.width;
                var height = img.height;

                if (width > maxWidth) {
                    height *= maxWidth / width;
                    width = maxWidth;
                }
                if (height > maxHeight) {
                    width *= maxHeight / height;
                    height = maxHeight;
                }

                canvas.width = width;
                canvas.height = height;
                ctx.drawImage(img, 0, 0, width, height);

                canvas.toBlob(function (blob) {
                    formData.delete('photo');
                    formData.append('photo', blob, file.name);

                    $.ajax({
                        url: '/main/save_member_info',
                        method: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        dataType: 'json',
                        success: function (response) {
                            if (response.status === 'success') {
                                $('#memberOffcanvas').offcanvas('hide');
                                // 멤버 정보 업데이트 후 필요한 작업 수행
                                var memberIdx = $('#memberIdx').val();
                                var memberName = $('#memberName').val();
                                var photoUrl = response.photo_url;

                                // member-card 업데이트
                                var memberCard = $('.member-card[member-idx="' + memberIdx + '"]');
                                memberCard.find('.member-name').text(memberName);
                                if (photoUrl) {
                                    memberCard.find('.photo').css('background-image', 'url(' + photoUrl + ')');
                                } else {
                                    memberCard.find('.photo').css('background-image', '');
                                }

                                // memberOffcanvas 내부의 .member-photo 업데이트
                                if (photoUrl) {
                                    $('.member-photo').css('background-image', 'url(' + photoUrl + ')');
                                } else {
                                    $('.member-photo').css('background-image', '');
                                }
                            } else {
                                alert('멤버 정보 저장에 실패했습니다.');
                            }
                        }
                    });
                }, file.type);
            };
            img.src = e.target.result;
        };
        reader.readAsDataURL(file);
    } else {
        $.ajax({
            url: '/main/save_member_info',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function (response) {
                if (response.status === 'success') {
                    $('#memberOffcanvas').offcanvas('hide');
                    // 멤버 정보 업데이트 후 필요한 작업 수행
                    var memberIdx = $('#memberIdx').val();
                    var memberName = $('#memberName').val();
                    var photoUrl = response.photo_url;

                    // member-card 업데이트
                    var memberCard = $('.member-card[member-idx="' + memberIdx + '"]');
                    memberCard.find('.member-name').text(memberName);
                    if (photoUrl) {
                        memberCard.find('.photo').css('background-image', 'url(' + photoUrl + ')');
                    } else {
                        memberCard.find('.photo').css('background-image', '');
                    }

                    // memberOffcanvas 내부의 .member-photo 업데이트
                    if (photoUrl) {
                        $('.member-photo').css('background-image', 'url(' + photoUrl + ')');
                    } else {
                        $('.member-photo').css('background-image', '');
                    }
                } else {
                    alert('멤버 정보 저장에 실패했습니다.');
                }
            }
        });
    }
}


// 메모 저장
// 메모 저장 버튼 클릭 이벤트 수정
$('#saveMemo').click(function() {
    var formData = $('#memoForm').serialize();
    var memberIdx = $('#memoMemberIdx').val();

    $.ajax({
        url: '/main/save_memo',
        method: 'POST',
        data: formData,
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                $('#addmemoModal').modal('hide');
                // 메모 저장 후 필요한 작업 수행
                memoPage = 1;
                $('.memo-list ul').empty();
                loadMemoList(memberIdx);

                // 메모 내용 비우기
                $('#memoContent').val('');

                // 해당 멤버의 memo-count 업데이트
                updateMemoCountForMember(memberIdx);
            } else {
                alert('메모 저장에 실패했습니다.');
            }
        }
    });
});


// 특정 멤버의 memo-count 업데이트 함수 추가
function updateMemoCountForMember(memberIdx) {
    var memberCard = $('.member-card[member-idx="' + memberIdx + '"]');
    var memoStampsContainer = memberCard.find('.member-wrap');
    var memoCountElement = memberCard.find('.memo-count');

    if (memoCountElement.length > 0) {
        var currentCount = parseInt(memoCountElement.text());
        memoCountElement.text(currentCount + 1);
    } else {
        var memoStampsHtml = '<span class="memo-stamp-warp">' +
            '<span class="memo-stamp"><i class="bi bi-journal-check"></i><span class="memo-count">1</span></span>' +
            '</span>';
        memoStampsContainer.append(memoStampsHtml);
    }

    // .total-memo-list 숫자 업데이트
    updateTotalMemoList();
}




// 메모 삭제 버튼 클릭 이벤트
$('.memo-list').on('click', '.btn-memo-del', function() {
    var confirmDelete = confirm('정말 삭제하시겠습니까?');
    if (confirmDelete) {
        var idx = $(this).data('idx');  // memo_idx 대신 idx 사용
        // console.log(idx);
        deleteMemo(idx);
    }
});

function deleteMemo(idx) {
    $.ajax({
        url: '/main/delete_memo',
        method: 'POST',
        data: { idx: idx },  // memo_idx 대신 idx 사용
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                memoPage = 1;
                $('.memo-list ul').empty();
                var memberIdx = $('#memoMemberIdx').val();
                loadMemoList(memberIdx);
            } else {
                alert('메모 삭제에 실패했습니다.');
            }
        }
    });
}


var memoPage = 1;
var memoLimit = 10;

// 메모 목록 로드
function loadMemoList(memberIdx) {
    $.ajax({
        url: '/main/get_memo_list',
        method: 'POST',
        data: {
            member_idx: memberIdx,
            page: memoPage,
            limit: memoLimit
        },
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                var memoList = response.data;
                var memoListHtml = '';

                for (var i = 0; i < memoList.length; i++) {
                    var memo = memoList[i];
                    memoListHtml += '<li><span class="memo-date">' + memo.regi_date + '</span>' +
                        '<span class="memo-id">' + memo.user_id + '</span>' +
                        '<span class="memo-content">' + memo.memo_content + '</span>' +
                        '<a class="btn-memo-del" data-idx="' + memo.idx + '"><i class="bi bi-trash3"></i></a></li>';  // memo_idx 대신 idx 사용
                }

                $('.memo-list ul').append(memoListHtml);
            }
        }
    });
}

// 메모 목록 스크롤 이벤트
$('.memo-list').on('scroll', function() {
    var memoList = $(this);
    var scrollTop = memoList.scrollTop();
    var scrollHeight = memoList[0].scrollHeight;
    var offsetHeight = memoList[0].offsetHeight;

    if (scrollTop + offsetHeight >= scrollHeight) {
        memoPage++;
        var memberIdx = $('#memoMemberIdx').val();
        loadMemoList(memberIdx);
    }
});





function updateMemoStamps(groupId, startDate, endDate) {
    $.ajax({
        url: '/main/get_memo_counts',
        method: 'POST',
        data: {
            group_id: groupId,
            start_date: startDate,
            end_date: endDate
        },
        dataType: 'json',
        success: function(memoCounts) {
            $('.member-card .memo-stamp-warp').remove();

            $.each(memoCounts, function(memberIdx, count) {
                var memberCard = $('.member-card[member-idx="' + memberIdx + '"]');
                var memoStampsContainer = memberCard.find('.member-wrap');

                if (count > 0) {
                    var memoStampsHtml = '<span class="memo-stamp-warp">' +
                        '<span class="memo-stamp"><i class="bi bi-journal-check"></i><span class="memo-count">' + count + '</span></span>' +
                        '</span>';
                    memoStampsContainer.append(memoStampsHtml);
                }
            });

            //메모 카운트 업데이트
            updateTotalMemoList();
        }

    });
}












function loadMemberAttendance(memberIdx, startDate, endDate) {
    $.ajax({
        url: '/main/get_member_attendance',
        method: 'POST',
        data: {
            member_idx: memberIdx,
            start_date: startDate,
            end_date: endDate
        },
        dataType: 'json',
        success: function(response) {
            var attendanceData = response.attendance_data;
            // 출석 유형 로드 및 기존 출석 정보 체크
            loadAttendanceTypes(memberIdx, attendanceData);
        }
    });
}

// 출석 유형 로드 및 표시
function loadAttendanceTypes(memberIdx, attendanceData) {
    var html = '';

    var currentClass = null;
    for (var i = 0; i < attendanceTypes.length; i++) {
        var type = attendanceTypes[i];

        if (type.att_type_category_idx !== currentClass) {
            if (currentClass !== null) {
                html += '</div></div>';
            }
            html += '<label class="mb-1">' + type.att_type_category_name + '</label>';
            html += '<div class="att-btn-list"><div class="btn-group" role="group" aria-label="Attendance Type">';
            currentClass = type.att_type_category_idx;
        }

        // 현재 선택된 주차 범위 내의 모든 날짜에 대해 출석 정보 확인
        var isChecked = false;
        for (var date in attendanceData) {
            if (attendanceData.hasOwnProperty(date) && attendanceData[date].includes(type.att_type_idx.toString())) {
                isChecked = true;
                break;
            }
        }

        html += '<input type="radio" class="btn-check" name="att_type_' + type.att_type_category_idx + '" id="att_type_' + type.att_type_idx + '" value="' + type.att_type_idx + '" ' + (isChecked ? 'checked' : '') + ' autocomplete="off">';
        html += '<label class="btn btn-outline-primary" for="att_type_' + type.att_type_idx + '">' + type.att_type_name + '</label>';
    }

    if (currentClass !== null) {
        html += '</div></div>';
    }

    $('#attendanceTypes').html(html);
    // 버튼 클릭 이벤트 추가

    $('#attendanceModal').modal('show');


}

// 출석 정보 저장
$('#saveAttendance').click(function() {


    var memberIdx = $('#selectedMemberIdx').val();
    var attendanceData = [];
    var activeGroupId = getCookie('activeGroup');

    // 현재 선택된 주차 범위 가져오기
    var currentWeekRange = $('.current-week').text();
    var currentDate = getDateFromWeekRange(currentWeekRange);
    var startDate = getWeekStartDate(currentDate);
    var endDate = getWeekEndDate(currentDate);




    // 오늘 날짜가 현재 선택된 주차 범위에 속하는지 확인
    var today = new Date();
    var formattedToday = formatDate(today);
    var attDate = (formattedToday >= startDate && formattedToday <= endDate) ? formattedToday : startDate;



    $('#attendanceTypes .btn-group').each(function() {
        var selectedType = $(this).find('input[type="radio"]:checked').val();
        if (selectedType) {
            attendanceData.push(selectedType);
        }
    });

    $.ajax({
        url: '/main/save_attendance',
        method: 'POST',
        data: {
            member_idx: memberIdx,
            attendance_data: JSON.stringify(attendanceData),
            group_id: activeGroupId,
            att_date: attDate,
            start_date: startDate,
            end_date: endDate
        },
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                $('#attendanceModal').modal('hide');


                // 출석 정보 업데이트
                updateAttStamps(activeGroupId, startDate, endDate);




            } else {
                alert('출석 정보 저장에 실패했습니다.');
            }
        }
    });
});






// initialize 버튼을 클릭하면 모든 btn-check가 unchecked
$('#initialize').on('click', function() {
    var checkboxes = document.querySelectorAll('#attendanceTypes .btn-check');
    checkboxes.forEach(function(checkbox) {
        checkbox.checked = false;
    });
});


// 쿠키 설정 함수
function setCookie(name, value, days) {
    var expires = "";
    if (days) {
        var date = new Date();
        date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
        expires = "; expires=" + date.toUTCString();
    }
    document.cookie = name + "=" + (value || "") + expires + "; path=/";
}

// 쿠키 가져오기 함수
function getCookie(name) {
    var nameEQ = name + "=";
    var ca = document.cookie.split(';');
    for (var i = 0; i < ca.length; i++) {
        var c = ca[i];
        while (c.charAt(0) == ' ') c = c.substring(1, c.length);
        if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length, c.length);
    }
    return null;
}

function displayMembers(members) {
    var memberList = $('.member-list .grid');
    memberList.empty();

    if (members.length === 0) {
        memberList.append('<div class="no-member">조회된 회원이 없습니다.<br>오른쪽 하단의 회원 추가 버튼을 선택하여 첫번째 회원을 추가해보세요!</div>');
    } else {
        memberList.append('<div class="grid-sizer"></div>');

        // 출석 유형별 숫자 초기화
        var attTypeCount = {};

        $.each(members, function(index, member) {

            var memberCard = $('<div class="grid-item"><div class="member-card" member-idx="' + member.member_idx + '" data-birth="' + member.member_birth + '"><div class="member-wrap"><span class="member-name">' + member.member_name + '</span><span class="att-stamp-warp"></span></div></div></div>');

            if (member.leader_yn === 'Y') {
                memberCard.find('.member-card').addClass('leader');
                memberCard.find('.member-card .member-wrap').prepend('<span class="badge"><i class="bi bi-bookmark-star-fill"></i></span>');

                if (member.photo) {
                    var photoUrl = '/uploads/member_photos/' + member.group_id + '/' + member.photo;
                    memberCard.find('.member-card').prepend('<span class="photo" style="background: url(' + photoUrl + ') center center/cover"></span>');
                } else {
                    memberCard.find('.member-card').prepend('<span class="photo"></span>');
                }

            }


            if (member.area) {
                memberCard.find('.member-card').prepend('<span class="area"> '+ member.area +' </span> ');
            }

            if (member.grade) {
                memberCard.find('.member-card').addClass('grade-'+member.grade)
            }

            if (member.new_yn === 'Y') {
                memberCard.find('.member-card').addClass('new');
                memberCard.find('.member-card .member-wrap').prepend('<span class="badge"><i class="bi bi-bookmark-heart-fill"></i></span>');
            }




            if (member.att_type_data) {
                var attTypeData = member.att_type_data.split('|');
                // console.log(attTypeData);

                var attStamps = attTypeData.map(function(attData) {
                    var attDataArr = attData.split(',');
                    var attType = attDataArr[0].trim();
                    var attTypeIdx = attDataArr[1].trim();
                    var attTypeCategoryIdx = attDataArr[2].trim();
                    var attTypeColor = attDataArr[3].trim();


                    // 출석 유형별 숫자 카운트
                    if (attTypeCount[attType]) {
                        attTypeCount[attType]++;
                    } else {
                        attTypeCount[attType] = 1;
                    }

                    return {
                        attType: attType,
                        attTypeIdx: attTypeIdx,
                        attTypeCategoryIdx: attTypeCategoryIdx,
                        attTypeColor: attTypeColor
                    };
                });

                /*


                    // att-stamp를 data-att-type-idx 순서로 정렬
                    attStamps.sort(function(a, b) {
                        return a.attTypeIdx - b.attTypeIdx;
                    });

                    var attTypesHtml = attStamps.map(function(attStamp) {
                        return '<span class="att-stamp" data-att-type-idx="' + attStamp.attTypeIdx + '" data-att-type-category-idx="' + attStamp.attTypeCategoryIdx + '" style="background-color: ' + attStamp.attTypeColor + '">' + attStamp.attType + '</span>';
                    }).join(' ');

                    memberCard.find('.att-stamp-warp').append(attTypesHtml);

                */


            }



            memberList.append(memberCard);
        });

        // 출석 유형별 숫자 출력
        var totalAttList = $('.total-att-list');
        totalAttList.empty();

        // 출석 유형을 att_type_category_idx와 att_type_idx 순으로 정렬
        var sortedAttTypes = Object.keys(attTypeCount).sort(function(a, b) {
            var attTypeA = a.split('_');
            var attTypeB = b.split('_');
            var classCompare = parseInt(attTypeA[1]) - parseInt(attTypeB[1]);
            if (classCompare === 0) {
                return parseInt(attTypeA[2]) - parseInt(attTypeB[2]);
            }
            return classCompare;
        });

        sortedAttTypes.forEach(function(attType) {
            totalAttList.append('<dt>' + attType + '</dt><dd>' + attTypeCount[attType] + '</dd>');
        });

    }

    // Masonry 레이아웃 업데이트
    // var isFirstNew = true;
    var prevArea = null;
    $('.grid-item').each(function() {

        var memberCard = $(this).find('.member-card');
        var currentArea = memberCard.attr('class').match(/grade-\d+/);
        if (currentArea && currentArea[0] !== prevArea) {
            $(this).before('<div class="grid-item grid-item--width100"></div>');
            prevArea = currentArea[0];
        }
        /*
        if ($(this).find('.leader').length > 0) {
            $(this).before('<div class="grid-item grid-item--width100"></div>');
        }

        if ($(this).find('.new').length > 0 && isFirstNew) {
            $(this).before('<div class="grid-item grid-item--width100"></div>');
            isFirstNew = false;
        }*/
    });



    // total-list 합산 계산
    var totalMembers = $('.grid-item .member-card').length;
    var totalNewMembers = $('.member-card.new').length;
    var totalAttMembers = totalMembers - totalNewMembers;
    var totalLeaderMembers = $('.member-card.leader').length;

    $('.total-list dd').eq(0).text(totalMembers);
    $('.total-list dd').eq(3).text(totalNewMembers);
    $('.total-list dd').eq(1).text(totalAttMembers);
    $('.total-list dd').eq(2).text(totalLeaderMembers);


    if ($('.grid').data('masonry')) {
        $('.grid').masonry('destroy');
    }
    $('.grid').masonry({
        itemSelector: '.grid-item',
        columnWidth: '.grid-sizer',
        stamp: '.stamp',
        percentPosition: true
    });



}




function applySelectedMode() {
    var selectedMode = getCookie('selectedMode');
    if (selectedMode) {
        $('.mode-list .btn-check').prop('checked', false);
        $('#' + selectedMode).prop('checked', true);
        applyModeConfig(selectedMode);
    } else {
        $('.mode-list .btn-check').prop('checked', false);
        $('#mode-1').prop('checked', true);
        applyModeConfig('mode-1');
    }
}



function getWeekStartDate(date) {
    if (typeof date === 'string') {
        date = new Date(date);
    }
    var day = date.getDay();
    var diff = date.getDate() - day;
    var startDate = new Date(date.setDate(diff));
    return formatDate(startDate);
}

function getWeekEndDate(date) {
    if (typeof date === 'string') {
        date = new Date(date);
    }
    var day = date.getDay();
    var diff = date.getDate() - day + 6;
    var endDate = new Date(date.setDate(diff));
    return formatDate(endDate);
}

function getWeekNumber(date) {
    var currentDate = new Date(date.getTime());
    var startDate = new Date(currentDate.getFullYear(), 0, 1); // 현재 연도의 1월 1일
    var days = Math.floor((currentDate - startDate) / (24 * 60 * 60 * 1000));
    var weekNumber = Math.ceil(days / 7);
    return weekNumber;
}


function getDateFromWeekRange(weekRange) {
    var parts = weekRange.split('~');
    var startDateStr = parts[0].trim().replace('년', '-').replace('월', '-').replace('일', '').replace(/\(\d+주차\)/, '').trim();
    return new Date(startDateStr);
}



function getWeekRangeFromDate(date) {
    var currentDate = new Date(date);
    var sundayTimestamp = getSunday(currentDate).getTime();
    var nextSundayTimestamp = sundayTimestamp + (7 * 24 * 60 * 60 * 1000);
    var week = getWeekNumber(currentDate);
    var startDate = formatDate(new Date(sundayTimestamp));
    var endDate = formatDate(new Date(nextSundayTimestamp - (24 * 60 * 60 * 1000)));
    return `${startDate}~${endDate} (${week}주차)`;
}


function generateAllWeekRanges() {
    var allWeekRanges = [];
    var startDate = new Date(new Date().getFullYear(), 0, 1); // 현재 연도의 1월 1일
    var endDate = new Date(new Date().getFullYear(), 11, 31); // 현재 연도의 12월 31일

    while (startDate <= endDate) {
        var weekRange = getWeekRangeFromDate(startDate);
        allWeekRanges.push(weekRange);
        startDate.setDate(startDate.getDate() + 7);
    }

    return allWeekRanges.reverse();
}



function getSunday(date) {
    var day = date.getDay();
    var diff = date.getDate() - day;
    return new Date(date.setDate(diff));
}




function formatDate(date) {
    var year = date.getFullYear();
    var month = String(date.getMonth() + 1).padStart(2, '0');
    var day = String(date.getDate()).padStart(2, '0');
    return `${year}.${month}.${day}`;
}

function loadMembers(groupId, level, startDate, endDate, initialLoad = true) {

    $.ajax({
        url: '/main/get_members',
        method: 'POST',
        data: {
            group_id: groupId,
            level: level,
            start_date: startDate,
            end_date: endDate
        },
        dataType: 'json',
        success: function(members) {
            displayMembers(members);

            // 초기 로드 시에만 applySelectedMode 호출
            if (initialLoad) {
                applySelectedMode();
            }

            // 최근 5주 이전까지 출석이 없는 사람 숨기기
            var hideFiveWeeksAgo = getCookie('hideFiveWeeksAgo') === 'true';
            if (hideFiveWeeksAgo) {
                $.ajax({
                    url: '/main/get_active_members',
                    method: 'POST',
                    data: {
                        group_id: groupId
                    },
                    dataType: 'json',
                    success: function(activeMembers) {
                        var activeMemberIndexes = activeMembers.map(function(member) {
                            return member.member_idx;
                        });
                        hideInactiveMembersForFiveWeeks(activeMemberIndexes);
                    }
                });
            } else {
                // hideFiveWeeksAgo가 false인 경우 모든 멤버 보이기
                $('.grid-item').show();
                // Masonry 레이아웃 업데이트
                if ($('.grid').data('masonry')) {
                    $('.grid').masonry('layout');
                }
            }
        }
    });
}

function hideInactiveMembersForFiveWeeks(activeMembers) {
    // 모든 .grid-item 요소 숨기기
    $('.grid-item:not(.grid-item--width100)').hide();

    // activeMembers에 포함된 멤버들의 .grid-item 요소만 보이기
    activeMembers.forEach(function(memberIdx) {
        $('.member-card[member-idx="' + memberIdx + '"]').closest('.grid-item').show();
    });

    // Masonry 레이아웃 업데이트
    if ($('.grid').data('masonry')) {
        $('.grid').masonry('layout');
    }
}

function updateTotalMemoList() {
    var totalMemos = 0;
    $('.memo-count').each(function() {
        totalMemos += parseInt($(this).text());
    });
    $('.total-memo-list dd').text(totalMemos);
}



function updateAttStamps(groupId, startDate, endDate) {
    var selectedMode = $('.mode-list .btn-check:checked').attr('id');
    if (selectedMode === 'mode-1') {
        $.ajax({
            url: '/main/get_attendance_data',
            method: 'POST',
            data: {
                group_id: groupId,
                start_date: startDate,
                end_date: endDate
            },
            dataType: 'json',
            success: function (attendanceData) {
                // 출석 유형별 숫자 초기화
                var attTypeCount = {};

                // 모든 멤버의 att-stamp 제거
                $('.member-card .member-wrap .att-stamp-warp .att-stamp').remove();


                $.each(attendanceData, function (memberIdx, attTypeNicknames) {
                    var memberCard = $('.member-card[member-idx="' + memberIdx + '"]');
                    var attStampsContainer = memberCard.find('.member-wrap .att-stamp-warp');

                    if (attTypeNicknames) {
                        var attStamps = attTypeNicknames.split(',').map(function (attTypeData) {
                            var attTypeArr = attTypeData.split('|');
                            var attTypeNickname = attTypeArr[0].trim();
                            var attTypeIdx = attTypeArr[1].trim();
                            var attTypeCategoryIdx = attTypeArr[2].trim();
                            var attTypeColor = attTypeArr[3].trim();

                            // console.log(attTypeNickname);


                            // 출석 유형별 숫자 카운트
                            if (attTypeCount[attTypeNickname]) {
                                attTypeCount[attTypeNickname]++;
                            } else {
                                attTypeCount[attTypeNickname] = 1;
                            }

                            return {
                                attTypeNickname: attTypeNickname,
                                attTypeIdx: attTypeIdx,
                                attTypeCategoryIdx: attTypeCategoryIdx,
                                attTypeColor: attTypeColor
                            };
                        });

                        // att-stamp를 data-att-type-idx 순서로 정렬
                        attStamps.sort(function (a, b) {
                            return a.attTypeIdx - b.attTypeIdx;
                        });


                        var attTypesHtml = attStamps.map(function (attStamp) {
                            return '<span class="att-stamp" data-att-type-idx="' + attStamp.attTypeIdx + '" data-att-type-category-idx="' + attStamp.attTypeCategoryIdx + '" style="background-color: ' + attStamp.attTypeColor + '">' + attStamp.attTypeNickname + '</span>';
                        }).join(' ');


                        attStampsContainer.append(attTypesHtml);
                    }
                });


                // 출석 유형별 숫자 출력
                var totalAttList = $('.total-att-list');
                totalAttList.empty();

                // 출석 유형을 att_type_category_idx와 att_type_idx 순으로 정렬
                var sortedAttTypes = Object.keys(attTypeCount).sort(function (a, b) {
                    var attTypeA = a.split('_');
                    var attTypeB = b.split('_');
                    var classCompare = parseInt(attTypeA[1]) - parseInt(attTypeB[1]);
                    if (classCompare === 0) {
                        return parseInt(attTypeA[2]) - parseInt(attTypeB[2]);
                    }
                    return classCompare;
                });

                sortedAttTypes.forEach(function (attType) {
                    totalAttList.append('<dt>' + attType + '</dt><dd>' + attTypeCount[attType] + '</dd>');
                });
            }
        });
    }
}








// 주차 범위 업데이트 함수 수정
function updateWeekRange(weekRange) {
    $('.current-week').text(weekRange);
    var currentDate = getDateFromWeekRange(weekRange);
    var startDate = getWeekStartDate(currentDate);
    var endDate = getWeekEndDate(currentDate);
    var activeGroupId = getCookie('activeGroup');

    // 좌우버튼클릭시 실행
    if (activeGroupId) {
        updateBirthBg(startDate, endDate); // 추가
        var selectedMode = $('.mode-list .btn-check:checked').attr('id');
        if (selectedMode === 'mode-3') {
            updateMemoStamps(activeGroupId, startDate, endDate);
        } else if (selectedMode === 'mode-1') {
            updateAttStamps(activeGroupId, userLevel, startDate, endDate);
        }
    }

    updateInputSearchState();
}


// 새로운 함수 추가
function updateBirthBg(startDate, endDate) {
    $('.member-card').each(function() {
        var memberCard = $(this);
        // var memberIdx = memberCard.attr('member-idx');
        var memberBirthDate = memberCard.data('birth');

        if (memberBirthDate) {
            var currentYear = new Date().getFullYear();
            var birthDate = new Date(memberBirthDate);
            var birthMonth = String(birthDate.getMonth() + 1).padStart(2, '0');
            var birthDay = String(birthDate.getDate()).padStart(2, '0');

            var formattedBirthDate = currentYear + '.' + birthMonth + '.' + birthDay;

            if (formattedBirthDate >= startDate && formattedBirthDate <= endDate) {
                if (!memberCard.find('.birth').length) {
                    memberCard.addClass('birth');
                }
            } else {
                memberCard.removeClass('birth');
            }
        }



    });
}

// 한글자 이상 입력하면 검색,  숫자 입력 시 검색 기능 비활성화
$('#input-search').on('input', function() {
    var searchText = $(this).val().trim();
    if (/^\d+$/.test(searchText)) {
        // 숫자만 입력된 경우 검색 기능 비활성화
        return;
    }

    if (searchText.length >= 1) {
        searchMembers(searchText);
    } else {
        resetMemberList();
    }
});


function searchMembers(searchText) {
    $('.grid-item').each(function() {
        var memberName = $(this).find('.member-wrap').text().trim();

        if (memberName.includes(searchText)) {
            $(this).show();
        } else {
            $(this).hide();
        }
    });

    // Masonry 레이아웃 업데이트
    if ($('.grid').data('masonry')) {
        $('.grid').masonry('layout');
    }
}
function resetMemberList() {
    $('.grid-item').show();

    // Masonry 레이아웃 업데이트
    if ($('.grid').data('masonry')) {
        $('.grid').masonry('layout');
    }
}

$('#input-search').on('keypress', function(e) {
    if (e.which === 13) {
        addAttStamp();
    }
});




$('#btn-submit').on('click', function() {
    addAttStamp();
});






function addAttStamp() {
    var memberIdx = $('#input-search').val().trim();
    if (/^\d+$/.test(memberIdx)) {
        var attTypeIdx = $('#dropdown-toggle-att-type').data('att-type-idx');
        var attTypeNickname = $('.dropdown-att-type .dropdown-item[data-att-type-idx="' + attTypeIdx + '"]').data('att-type-nickname');
        var attTypeCategoryIdx = $('.dropdown-att-type .dropdown-item[data-att-type-idx="' + attTypeIdx + '"]').data('att-type-category-idx');

        // 해당 member-idx를 가진 멤버에게 att-stamp 추가
        var memberCard = $('.member-card[member-idx="' + memberIdx + '"]');
        if (memberCard.length > 0) {
            var existingAttStamp = memberCard.find('.att-stamp[data-att-type-idx="' + attTypeIdx + '"]');
            if (existingAttStamp.length > 0) {
                playNoSound();
                heyToast('이미 출석체크 하였습니다.', '중복출석체크');
                $('#input-search').val('').focus();
                return;
            }

            // 동일한 att_type_category_idx를 가진 모든 att-stamp 삭제
            var existingCategoryStamps = memberCard.find('.att-stamp[data-att-type-category-idx="' + attTypeCategoryIdx + '"]');
            // console.log(existingCategoryStamps);
            if (existingCategoryStamps.length > 0) {
                existingCategoryStamps.remove();
            }

            var attStamp = '<span class="att-stamp" data-att-type-idx="' + attTypeIdx + '" data-att-type-category-idx="' + attTypeCategoryIdx + '">' + attTypeNickname + '</span>';
            memberCard.find('.att-stamp-warp').append(attStamp);
            // memberCard.removeClass('off')

            // 서버에 출석 정보 저장
            saveAttendance(memberIdx, attTypeIdx, attTypeCategoryIdx);

            // 토스트 띄우기
            showToast(memberIdx);

            // 생일 여부 확인 후 사운드 재생
            var isBirthday = memberCard.hasClass('birth');
            if (isBirthday) {
                playBirthSound();
            } else {
                playOkSound();
            }

        } else {
            playNoSound()
            heyToast('등록된 회원이 아닙니다.', '등록회원 없음');

        }
    }
    $('#input-search').val('').focus();
}



function playOkSound() {
    var audio = document.getElementById('sound-ok');
    audio.play();
}

function playNoSound() {
    var audio = document.getElementById('sound-no');
    audio.play();
}

function playBirthSound() {
    var audio = document.getElementById('sound-birth');
    audio.play();
}


function showToast(memberIdx) {
    $.ajax({
        url: '/main/get_member_info',
        method: 'POST',
        data: { member_idx: memberIdx },
        dataType: 'json',
        success: function(response) {
            var memberName = response.member_name;
            var memberNick = response.member_nick;
            $('.toast-header strong').text(memberName + '님 출석완료!');
            $('.toast-body').text(memberNick);
            $('#liveToast').toast('show');
        }
    });
}




function saveAttendance(memberIdx, attTypeIdx, attTypeCategoryIdx) {
    var activeGroupId = getCookie('activeGroup');
    var today = new Date();
    var attDate = formatDate(today);

    // console.log(memberIdx);
    // 해당 member-card로 스크롤 이동
    var memberCard = $('.member-card[member-idx="' + memberIdx + '"]');
    if (memberCard.length > 0) {
        $('html, body').animate({
            scrollTop: memberCard.offset().top - 100
        }, 500);
    }


    $.ajax({
        url: '/main/save_single_attendance',
        method: 'POST',
        data: {
            member_idx: memberIdx,
            att_type_idx: attTypeIdx,
            att_type_category_idx: attTypeCategoryIdx,
            group_id: activeGroupId,
            att_date: attDate
        },
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                console.log('출석 정보 저장 완료');
            } else {
                console.log('출석 정보 저장 실패');
            }
        }
    });
}


// dropdown-att-type 항목 클릭 이벤트
$('.dropdown-att-type').on('click', '.dropdown-item', function(e) {
    e.preventDefault();
    var attTypeName = $(this).text();
    var attTypeIdx = $(this).data('att-type-idx');
    $('#dropdown-toggle-att-type').text(attTypeName).data('att-type-idx', attTypeIdx);

    // var activeGroupId = getCookie('activeGroup');
    // setCookie('att-type-idx_' + activeGroupId, attTypeIdx, 7);
});


function updateAttendanceTypes(groupId) {
    // 그룹별 att-type-idx 쿠키 삭제
    // deleteCookie('att-type-idx_' + groupId);

    $.ajax({
        url: '/main/get_attendance_types',
        method: 'POST',
        data: { group_id: groupId },
        dataType: 'json',
        success: function(response) {
            var attendanceTypes = response.attendance_types;
            var dropdownAttType = $('.dropdown-att-type');
            dropdownAttType.empty();

            var prevCategoryIdx = null;
            $.each(attendanceTypes, function(index, type) {
                if (prevCategoryIdx !== null && prevCategoryIdx !== type.att_type_category_idx) {
                    dropdownAttType.append('<li><hr class="dropdown-divider"></li>');
                }
                dropdownAttType.append('<li><a class="dropdown-item" href="#" data-att-type-idx="' + type.att_type_idx + '" data-att-type-nickname="' + type.att_type_nickname + '">' + type.att_type_name + '</a></li>');
                prevCategoryIdx = type.att_type_category_idx;
            });

            // data-att-type-idx가 가장 작은 값으로 설정
            var firstAttType = $('.dropdown-att-type .dropdown-item:first');
            var attTypeName = firstAttType.text();
            var attTypeIdx = firstAttType.data('att-type-idx');

            // dropdown-toggle-att-type 텍스트와 data-att-type-idx 값을 직접 설정
            $('#dropdown-toggle-att-type').text(attTypeName);
            $('#dropdown-toggle-att-type').data('att-type-idx', attTypeIdx);

            // 선택된 출석 유형 쿠키에 저장
            // setCookie('att-type-idx_' + groupId, attTypeIdx, 7);
        }
    });
}



// 쿠키 삭제 함수
function deleteCookie(name) {
    document.cookie = name + '=; expires=Thu, 01 Jan 1970 00:00:01 GMT; path=/;';
}









// 오늘 날짜가 current-week의 기간 안에 있는지 확인하고 input-search 활성화/비활성화
function updateInputSearchState() {
    var currentWeekRange = $('.current-week').text();
    var currentDate = getDateFromWeekRange(currentWeekRange);
    var startDate = getWeekStartDate(currentDate);
    var endDate = getWeekEndDate(currentDate);
    var today = new Date();
    var formattedToday = formatDate(today); // 수정

    // console.log(formattedToday);
    // console.log(startDate);
    // console.log(endDate);

    if (formattedToday >= startDate && formattedToday <= endDate) {
        $('#input-search').prop('disabled', false).val('').attr('placeholder', '이름검색 또는 QR체크!').focus();
    } else {
        $('#input-search').prop('disabled', true).val('검색중...').attr('placeholder', '');
        resetMemberList();
    }
}

var attendanceTypes = [];

$(document).ready(function() {

    // 페이지 로드 시 input-search에 포커스 설정
    $('#input-search').focus();







    // 모든 주차 범위 생성
    var allWeekRanges = generateAllWeekRanges();
    var weekRangeDropdown = $('.dropdown-current-week');
    allWeekRanges.forEach(function(weekRange) {
        var li = $('<li>').append($('<a>').addClass('dropdown-item').attr('href', '#').text(weekRange));
        weekRangeDropdown.append(li);
    });


    // 현재 주차 범위 설정
    // var currentWeekRange = getCurrentWeekRange();


    var today = new Date();
    var formattedToday = formatDate(today);
    var currentWeekRange = getWeekRangeFromDate(formattedToday);
    updateWeekRange(currentWeekRange);

    $('.current-week').text(currentWeekRange);



    // 이전 주 버튼 클릭 이벤트
    $('.prev-week').click(function() {
        var currentWeekRange = $('.current-week').text();
        var currentDate = getDateFromWeekRange(currentWeekRange);
        var prevDate = new Date(currentDate.setDate(currentDate.getDate() - 7));
        var prevWeekRange = getWeekRangeFromDate(prevDate);
        // console.log(prevWeekRange);
        updateWeekRange(prevWeekRange);
    });

    // 다음 주 버튼 클릭 이벤트
    $('.next-week').click(function() {
        var currentWeekRange = $('.current-week').text();
        var currentDate = getDateFromWeekRange(currentWeekRange);
        var nextDate = new Date(currentDate.setDate(currentDate.getDate() + 7));
        var nextWeekRange = getWeekRangeFromDate(nextDate);
        updateWeekRange(nextWeekRange);
    });

    // 드롭다운 메뉴 항목 클릭 이벤트
    $('.dropdown-current-week .dropdown-item').click(function(e) {
        e.preventDefault();
        var weekRange = $(this).text();
        updateWeekRange(weekRange);
    });



    // 최근 5주 이전까지 출석이 없는 사람 숨기기 스위치 변경 이벤트
    $('#hide5weekAgo').change(function() {
        var isChecked = $(this).is(':checked');
        setCookie('hideFiveWeeksAgo', isChecked, 7);

        var currentWeekRange = $('.current-week').text();
        var currentDate = getDateFromWeekRange(currentWeekRange);
        var startDate = getWeekStartDate(currentDate);
        var endDate = getWeekEndDate(currentDate);
        var activeGroupId = getCookie('activeGroup');

        if (isChecked) {
            $.ajax({
                url: '/main/get_active_members',
                method: 'POST',
                data: {
                    group_id: activeGroupId
                },
                dataType: 'json',
                success: function(activeMembers) {

                    // console.log(activeMembers);


                    var activeMemberIndexes = activeMembers.map(function(member) {
                        return member.member_idx;
                    });
                    hideInactiveMembersForFiveWeeks(activeMemberIndexes);
                }
            });
        } else {
            $('.grid-item').show();
            // Masonry 레이아웃 업데이트
            if ($('.grid').data('masonry')) {
                $('.grid').masonry('layout');
            }
        }
    });


    // 페이지 로드 시 input-search 상태 업데이트
    updateInputSearchState();







});
