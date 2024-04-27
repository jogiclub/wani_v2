<html lang="ko">
<head>
    <meta charset="utf-8">

    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title></title>
    <meta name="viewport" content="width=device-width, initial-scale=0.8">
    <meta name="description" content="심플체크" />
    <meta name="keywords" content="출석 체크, 교적, 메모, 심방" />
    <meta name="author" content="WEBHOWS.COM" />

    <!-- Facebook and Twitter integration -->
    <meta property="og:title" content="심플체크"/>
    <meta property="og:image" content=""/>
    <meta property="og:url" content=""/>
    <meta property="og:site_name" content="심플체크"/>
    <meta property="og:description" content="심플체크"/>
    <meta name="twitter:title" content="심플체크" />
    <meta name="twitter:image" content="" />
    <meta name="twitter:url" content="simplechk" />
    <meta name="twitter:card" content="심플체크" />

    <title>심플체크</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="//code.jquery.com/ui/1.13.1/themes/base/jquery-ui.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="/assets/css/common.css?<?php echo date('Ymdhis');?>">

</head>
<body>

<div class="header pt-3 pb-3">
    <div class="container-xl">
        <div class="row">

            <div class="col-12 text-center position-relative">
                <h2 class="mb-1"><?php echo $group_name; ?></h2>
                <button class="btn-gnb" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasRight" aria-controls="offcanvasRight"><i class="bi bi-list"></i></button>
            </div>


            <div class="col-xl-12 text-center mb-3 mode">
                <div class="btn-group" role="group" aria-label="Vertical radio toggle button group">
                    <input type="radio" class="btn-check" name="vbtn-radio" id="mode-1" autocomplete="off" checked>
                    <label class="btn btn-outline-primary" for="mode-1"><i class="bi bi-clipboard-check"></i> 출석모드</label>
                    <input type="radio" class="btn-check" name="vbtn-radio" id="mode-2" autocomplete="off">
                    <label class="btn btn-outline-primary" for="mode-2"><i class="bi bi-journals"></i> 심방모드</label>
                    <input type="radio" class="btn-check" name="vbtn-radio" id="mode-3" autocomplete="off">
                    <label class="btn btn-outline-primary" for="mode-3"><i class="bi bi-person-badge"></i> 관리모드</label>
                </div>
            </div>

            <div class="col-xl-12">
                <div class="input-group">
                    <input type="text" class="form-control" placeholder="QR코드 또는 이름을 입력하세요!" aria-label="QR코드 또는 이름을 입력하세요!" aria-describedby="basic-addon2">
                    <button class="input-group-text" id="btn-submit"><i class="bi bi-check2-square"></i> 출석</button>
                    <button class="input-group-text" id="btn-print"><i class="bi bi-printer"></i> QR인쇄</button>
                    <button class="input-group-text" id="btn-birth"><i class="bi bi-cake"></i> 월별생일자</button>
                </div>
            </div>



            <div class="col-xl-12 text-center">
                <div class="btn-group week-list mt-1" role="group">
                    <button type="button" class="btn btn-primary prev-week"><i class="bi bi-chevron-left"></i></button>
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-outline-primary dropdown-toggle current-week" data-bs-toggle="dropdown" aria-expanded="false">
                            <?php echo $current_week_range; ?>
                        </button>
                        <ul class="dropdown-menu">
                            <?php foreach ($all_week_ranges as $week_range): ?>
                                <li><a class="dropdown-item" href="#"><?php echo $week_range; ?></a></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <button type="button" class="btn btn-primary next-week"><i class="bi bi-chevron-right"></i></button>
                </div>
            </div>

            <div class="col-xl-12 text-center total-list-wrap">

                <dl class="mt-2 mb-2 total-list">
                    <dt>재적</dt><dd>00</dd>
                    <dt>새가족</dt><dd>00</dd>
                    <dt>출석</dt><dd>00</dd>
                </dl>
                <dl class="mt-2 mb-2 total-att-list">
                    <dt>출</dt><dd>00</dd>
                    <dt>온</dt><dd>00</dd>
                    <dt>장</dt><dd>00</dd>
                </dl>

            </div>

        </div>
    </div>
</div>
<main>
    <div class="container-xl">
        <div class="row">
        <div class="member-list">
            <div class="grid">
                <div class="grid-sizer"></div>
                <div class="grid-item">

                </div>
            </div>
        </div>
        </div>
    </div>

</main>
<footer>
    <button class="input-group-text btn-new" id="basic-addon2" data-bs-toggle="modal" data-bs-target="#newMemberModal"><i class="bi bi-person-plus"></i></button>
</footer>



<!-- 그룹 추가 모달 -->
<div class="modal fade" id="groupModal" tabindex="-1" aria-labelledby="groupModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="groupModalLabel">그룹 추가</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="group_name" class="form-label">그룹명</label>
                    <input type="text" class="form-control" id="group_name" name="group_name" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                <button type="button" class="btn btn-primary" id="saveGroup">저장</button>
            </div>
        </div>
    </div>
</div>

<!-- 사이드 메뉴 -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasRight" aria-labelledby="offcanvasRightLabel">
    <div class="offcanvas-header">


        <img src="<?php echo $user['picture']; ?>" class="rounded-circle mb-3" width="50" height="50">
        <h5 class="card-title"><?php echo $user['name']; ?></h5>
        <p class="card-text"><?php echo $user['email']; ?></p>
        <a href="<?php echo base_url('main/logout'); ?>" class="btn btn-danger">로그아웃</a>


        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        <div class="group-list-wrap">
            <a href="#" class="btn btn-primary btn-sm add-group" data-bs-toggle="modal" data-bs-target="#groupModal">그룹추가</a>
            <div class="group-list mt-3">
                <ul>
                    <?php if (empty($groups)): ?>
                        <li>개설된 그룹이 없습니다.</li>
                    <?php else: ?>
                        <?php foreach ($groups as $group): ?>
                            <li class="mt-1 mb-1">
                                <?php echo $group['group_name']; ?>

                                <a class="btn-setting" data-group-id="<?php echo $group['group_id']; ?>" data-group-name="<?php echo $group['group_name']; ?>"><i class="bi bi-gear"></i></a>

                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </div>
        </div>

    </div>
    <div class="offcanvas-body">
        ...
    </div>
</div>




<!-- 그룹 수정 모달 -->
<div class="modal fade" id="settingGroupModal" tabindex="-1" aria-labelledby="settingGroupModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="settingGroupModalLabel">그룹 설정</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="edit_group_name" class="form-label">그룹명</label>
                    <input type="text" class="form-control" id="edit_group_name" name="edit_group_name" required>
                    <input type="hidden" id="edit_group_id" name="edit_group_id">
                </div>



                <div class="mb-3">
                    <label for="edit_group_name" class="form-label">출석종류</label>
                    <div class="check-category-wrap">
                        <button type="button" class="btn btn-sm btn-primary add-attendance-type">출석종류추가</button>
                        <div class="check-category">
                            <!-- 출석종류 목록을 동적으로 생성 -->
                            <?php foreach ($attendance_types as $type): ?>
                                <!-- 출석종류 항목 생성 -->
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>



            </div>
            <div class="modal-footer">
                <button class="btn btn-danger btn-sm float-end btn-del">삭제</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                <button type="button" class="btn btn-primary" id="updateGroup">저장</button>
            </div>
        </div>
    </div>
</div>



<!-- 출석 체크 모달 -->
<div class="modal fade" id="attendanceModal" tabindex="-1" aria-labelledby="attendanceModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="attendanceModalLabel"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="selectedMemberIdx" value="">
                <div id="attendanceTypes"></div>
            </div>
            <div class="modal-footer">
                <div class="row width-100">
                    <div class="col-6">
                        <button type="button" class="btn btn-secondary" id="initialize">다시체크</button>
                    </div>
                    <div class="col-6 text-end">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                        <button type="button" class="btn btn-primary" id="saveAttendance">저장</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 새 멤버 추가 모달 -->
<div class="modal fade" id="newMemberModal" tabindex="-1" aria-labelledby="newMemberModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="newMemberModalLabel">새 멤버 추가</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="member_name" class="form-label">이름</label>
                    <input type="text" class="form-control" id="member_name" name="member_name" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                <button type="button" class="btn btn-primary" id="saveNewMember">저장</button>
            </div>
        </div>
    </div>
</div>



<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://code.jquery.com/ui/1.13.1/jquery-ui.js"></script>
<script src="https://unpkg.com/masonry-layout@4/dist/masonry.pkgd.min.js"></script>
<script src="/assets/js/common.js?2"></script>
<script>

    // 수정 버튼 클릭 이벤트
    $(document).on('click', '.btn-setting', function() {
        var groupId = $(this).data('group-id');
        var groupName = $(this).data('group-name');

        $('#edit_group_id').val(groupId);
        $('#edit_group_name').val(groupName);

        // 해당 그룹의 출석 종류 데이터 가져오기
        $.ajax({
            url: '<?php echo base_url("main/get_attendance_types_by_group"); ?>',
            method: 'POST',
            data: { group_id: groupId },
            dataType: 'json',
            success: function(response) {
                var attendanceTypes = response.attendance_types;
                var checkCategory = $('.check-category');
                checkCategory.empty();

        

                $('#settingGroupModal').modal('show');
            }
        });
    });






    //새로운 멤버를 추가
    $('#saveNewMember').click(function() {
        var member_name = $('#member_name').val();
        var activeGroupId = $('.group-list li.active .btn-setting').data('group-id');

        $.ajax({
            url: '<?php echo base_url("main/add_member"); ?>',
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
                    var startDate = getWeekStartDate(currentWeekRange);
                    var endDate = getWeekEndDate(currentWeekRange);

                    // 멤버 목록 업데이트
                    loadMembers(activeGroupId, startDate, endDate);
                } else {
                    alert('멤버 추가에 실패했습니다.');
                }
            }
        });
    });


    // 멤버 카드 클릭 이벤트 처리
    $(document).on('click', '.member-card', function() {
        var memberIdx = $(this).attr('member-idx');
        var memberName = $(this).find('.member-name').text().trim();
        var today = new Date();
        var formattedDate = today.getFullYear() + '.' + ('0' + (today.getMonth() + 1)).slice(-2) + '.' + ('0' + today.getDate()).slice(-2);
        var modalTitle = memberName + ' 님의 ' + formattedDate + ' 출석 체크';

        $('#selectedMemberIdx').val(memberIdx);
        $('#attendanceModalLabel').text(modalTitle);

        // 현재 선택된 주차 범위 가져오기
        var currentWeekRange = $('.current-week').text();
        var startDate = getWeekStartDate(currentWeekRange);
        var endDate = getWeekEndDate(currentWeekRange);

        // 멤버의 출석 데이터 불러오기
        loadMemberAttendance(memberIdx, startDate, endDate);
    });



    function loadMemberAttendance(memberIdx, startDate, endDate) {
        $.ajax({
            url: '<?php echo base_url("main/get_member_attendance"); ?>',
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
        var groupId = $('.group-list li.active .btn-setting').data('group-id');

        $.ajax({
            url: '<?php echo base_url("main/get_attendance_types"); ?>',
            method: 'POST',
            data: { group_id: groupId },
            dataType: 'json',
            success: function(response) {
                var attendanceTypes = response.attendance_types;
                var html = '';

                var currentClass = null;
                for (var i = 0; i < attendanceTypes.length; i++) {
                    var type = attendanceTypes[i];

                    if (type.att_type_category_idx !== currentClass) {
                        if (currentClass !== null) {
                            html += '</div></div>';
                        }
                        html += '<label>' + type.att_type_category_name + '</label>';
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
                $('#attendanceModal').modal('show');
            }
        });
    }



    // 출석 정보 저장
    $('#saveAttendance').click(function() {
        var memberIdx = $('#selectedMemberIdx').val();
        var attendanceData = [];
        var activeGroupId = $('.group-list li.active .btn-setting').data('group-id');

        // 현재 선택된 주차 범위 가져오기
        var currentWeekRange = $('.current-week').text();
        var startDate = getWeekStartDate(currentWeekRange);
        var endDate = getWeekEndDate(currentWeekRange);

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
            url: '<?php echo base_url("main/save_attendance"); ?>',
            method: 'POST',
            data: {
                member_idx: memberIdx,
                attendance_data: JSON.stringify(attendanceData),
                group_id: activeGroupId,
                att_date: attDate
            },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    $('#attendanceModal').modal('hide');
                    // 출석 정보 업데이트
                    loadMembers(activeGroupId, startDate, endDate);
                } else {
                    alert('출석 정보 저장에 실패했습니다.');
                }
            }
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
            memberList.append('<div class="grid-item"><div class="member-card">조회된 멤버가 없습니다.</div></div>');
        } else {
            memberList.append('<div class="grid-sizer"></div>');

            // 출석 유형별 숫자 초기화
            var attTypeCount = {};

            $.each(members, function(index, member) {
                var memberCard = $('<div class="grid-item"><div class="member-card" member-idx="' + member.member_idx + '"><div class="member-wrap"><span class="member-name">' + member.member_name + '</span></div></div></div>');

                if (member.leader_yn === 'Y') {
                    memberCard.find('.member-card').addClass('leader');
                    memberCard.find('.member-card .member-wrap').prepend('<span class="badge text-bg-primary">목자</span>');
                    memberCard.find('.member-card').prepend('<span class="photo"></span>');
                }

                if (member.new_yn === 'Y') {
                    memberCard.find('.member-card').addClass('new');
                    memberCard.find('.member-card').prepend('<span class="badge text-bg-warning">새가족</span>');
                }


                if (member.att_type_nicknames) {
                    var attTypes = member.att_type_nicknames.split(',');
                    var attTypesHtml = attTypes.map(function(attType) {
                        // 출석 유형별 숫자 카운트
                        if (attTypeCount[attType.trim()]) {
                            attTypeCount[attType.trim()]++;
                        } else {
                            attTypeCount[attType.trim()] = 1;
                        }
                        return '<span class="att-stamp">' + attType.trim() + '</span>';
                    }).join(' ');
                    memberCard.find('.member-wrap').append(attTypesHtml);
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
        var isFirstNew = true;
        $('.grid-item').each(function() {
            if ($(this).find('.leader').length > 0) {
                $(this).before('<div class="grid-item grid-item--width100"></div>');
            }

            if ($(this).find('.new').length > 0 && isFirstNew) {
                $(this).before('<div class="grid-item grid-item--width100"></div>');
                isFirstNew = false;
            }
        });

        if ($('.grid').data('masonry')) {
            $('.grid').masonry('destroy');
        }
        $('.grid').masonry({
            itemSelector: '.grid-item',
            columnWidth: '.grid-sizer',
            stamp: '.stamp',
            percentPosition: true
        });

        // total-list 합산 계산
        var totalMembers = $('.grid-item .member-card').length;
        var totalNewMembers = $('.member-card.new').length;
        var totalAttMembers = totalMembers - totalNewMembers;

        $('.total-list dd').eq(0).text(totalMembers);
        $('.total-list dd').eq(1).text(totalNewMembers);
        $('.total-list dd').eq(2).text(totalAttMembers);
    }


    // 그룹추가 등 이후에 리프래시
    function refreshGroupList(callback) {
        $.ajax({
            url: '<?php echo base_url("main/get_groups"); ?>',
            method: 'GET',
            dataType: 'json',
            success: function(groups) {
                var groupList = $('.group-list ul');
                var activeGroupId = getCookie('activeGroup');

                groupList.empty();

                if (groups.length === 0) {
                    groupList.append('<li>개설된 그룹이 없습니다.</li>');
                } else {
                    $.each(groups, function(index, group) {
                        var listItem = $('<li class="mt-1 mb-1">' + group.group_name + ' <a class="btn-setting" data-group-id="' + group.group_id + '" data-group-name="' + group.group_name + '"><i class="bi bi-gear-fill"></i></a></li>');
                        groupList.append(listItem);

                        if (group.group_id == activeGroupId) {
                            listItem.addClass('active');
                            $('h2.mb-1').text(group.group_name);
                            loadMembers(group.group_id, getWeekStartDate(getCurrentWeekRange()), getWeekEndDate(getCurrentWeekRange()));
                        }
                    });

                    if (!activeGroupId || $('.group-list ul li.active').length === 0) {
                        var firstGroup = $('.group-list ul li:first-child');
                        firstGroup.addClass('active');
                        setCookie('activeGroup', firstGroup.find('.btn-setting').data('group-id'), 7);
                        $('h2.mb-1').text(firstGroup.text().trim());
                        loadMembers(firstGroup.find('.btn-setting').data('group-id'), getWeekStartDate(getCurrentWeekRange()), getWeekEndDate(getCurrentWeekRange()));
                    }
                }

                if (callback && typeof callback === 'function') {
                    callback();
                }
            }
        });
    }





    function getWeekStartDate(weekRange) {
        var parts = weekRange.split('~');
        var startDateStr = parts[0].trim().replace('년', '-').replace('월', '-').replace('일', '').replace(/\(\d+주차\)/, '').trim();
        // console.log(startDateStr);
        // return false;
        var startDate = new Date(startDateStr);
        return formatDate(startDate);
    }

    function getWeekEndDate(weekRange) {
        var parts = weekRange.split('~');
        var endDateStr = parts[1].trim().replace('년', '-').replace('월', '-').replace('일', '').replace(/\(\d+주차\)/, '').trim();
        var endDate = new Date(endDateStr);
        return formatDate(endDate);
    }

    function formatDate(date) {
        var year = date.getFullYear();
        var month = String(date.getMonth() + 1).padStart(2, '0');
        var day = String(date.getDate()).padStart(2, '0');
        return `${year}.${month}.${day}`;
    }

    function loadMembers(groupId, startDate, endDate, initialLoad = true) {
        $.ajax({
            url: '<?php echo base_url("main/get_members"); ?>',
            method: 'POST',
            data: {
                group_id: groupId,
                start_date: startDate,
                end_date: endDate
            },
            dataType: 'json',
            success: function(members) {
                if (initialLoad) {
                    displayMembers(members);
                } else {
                    updateAttStamps(groupId, startDate, endDate);
                }

                // $("[data-group-id='1']").parent().addClass("active");

                // $('[data-group-id="' + groupId + '"]').parent('li').addClass('active');
                // console.log('3123');

            }

        });



    }




    function updateAttStamps(groupId, startDate, endDate) {
        $.ajax({
            url: '<?php echo base_url("main/get_attendance_data"); ?>',
            method: 'POST',
            data: {
                group_id: groupId,
                start_date: startDate,
                end_date: endDate
            },
            dataType: 'json',
            success: function(attendanceData) {
                // 출석 유형별 숫자 초기화
                var attTypeCount = {};

                // 모든 멤버의 att-stamp 제거
                $('.member-card .member-wrap .att-stamp').remove();

                $.each(attendanceData, function(memberIdx, attTypeNicknames) {
                    var memberCard = $('.member-card[member-idx="' + memberIdx + '"]');
                    var attStampsContainer = memberCard.find('.member-wrap');

                    if (attTypeNicknames) {
                        var attTypes = attTypeNicknames.split(',');
                        var attTypesHtml = attTypes.map(function(attType) {
                            // 출석 유형별 숫자 카운트
                            if (attTypeCount[attType.trim()]) {
                                attTypeCount[attType.trim()]++;
                            } else {
                                attTypeCount[attType.trim()] = 1;
                            }
                            return '<span class="att-stamp">' + attType.trim() + '</span>';
                        }).join(' ');
                        attStampsContainer.append(attTypesHtml);
                    }
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
        });
    }




    $(document).ready(function() {



        var attendanceTypeCount = 0;

        // 출석종류 추가 버튼 클릭 이벤트
        $(document).on('click', '.add-attendance-type', function() {
            var parentElement = $(this).closest('.check-category');
            attendanceTypeCount++;
            var newAttendanceType = `
            <div class="input-group">
                <input type="text" class="form-control attendance-type-name" value="출석종류${attendanceTypeCount}">
                <button type="button" class="btn btn-sm btn-danger delete-attendance-type">삭제</button>
                <button type="button" class="btn btn-sm btn-primary add-sub-attendance-type">하위추가</button>
            </div>
            <ul class="list-group attendance-types-list"></ul>
        `;
            parentElement.append(newAttendanceType);
        });

        // 하위 출석종류 추가 버튼 클릭 이벤트
        $(document).on('click', '.add-sub-attendance-type', function() {
            var parentElement = $(this).closest('.input-group').next('.attendance-types-list');
            var newSubAttendanceType = `
            <li class="list-group-item ui-sortable-handle">
                <div class="input-group">
                    <input type="text" class="form-control attendance-type-class-name" value="출석항목">
                    <input type="text" class="form-control attendance-type-nickname" value="출">
                    <button type="button" class="btn btn-sm btn-danger delete-sub-attendance-type">삭제</button>
                </div>
            </li>
        `;
            parentElement.append(newSubAttendanceType);
        });

        // 출석종류 삭제 버튼 클릭 이벤트
        $(document).on('click', '.delete-attendance-type', function() {
            $(this).closest('.input-group').next('.attendance-types-list').remove();
            $(this).closest('.input-group').remove();
        });

        // 하위 출석종류 삭제 버튼 클릭 이벤트
        $(document).on('click', '.delete-sub-attendance-type', function() {
            $(this).closest('.list-group-item').remove();
        });


        // 그룹 수정 저장 버튼 클릭 이벤트
        $('#updateGroup').click(function() {
            var groupId = $('#edit_group_id').val();
            var groupName = $('#edit_group_name').val();
            var attendanceTypes = [];

            $('.check-category .input-group').each(function() {
                var attendanceTypeName = $(this).find('.attendance-type-name').val();
                var subAttendanceTypes = [];

                $(this).next('.attendance-types-list').find('.list-group-item').each(function() {
                    var className = $(this).find('.attendance-type-class-name').val();
                    var nickname = $(this).find('.attendance-type-nickname').val();
                    subAttendanceTypes.push({
                        class_name: className,
                        nickname: nickname
                    });
                });

                attendanceTypes.push({
                    name: attendanceTypeName,
                    sub_types: subAttendanceTypes
                });
            });

            // 서버로 데이터 전송
            $.ajax({
                url: '/main/update_group',
                method: 'POST',
                data: {
                    group_id: groupId,
                    group_name: groupName
                },
                success: function(response) {
                    // 성공 처리
                    $('#settingGroupModal').modal('hide');
                    // 그룹 목록 새로고침 등의 작업 수행
                    refreshGroupList();
                },
                error: function(xhr, status, error) {
                    // 실패 처리
                    console.log(error);
                }
            });
        });



        // 이전 주 버튼 클릭 이벤트
        $('.prev-week').click(function() {
            var currentWeekRange = $('.current-week').text();
            var currentDate = getDateFromWeekRange(currentWeekRange);

            var prevDate = new Date(currentDate.setDate(currentDate.getDate() - 7));
            var prevWeekRange = getWeekRangeFromDate(prevDate);

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
        $('.dropdown-menu .dropdown-item').click(function(e) {
            e.preventDefault();
            var weekRange = $(this).text();
            updateWeekRange(weekRange);
        });









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


        function getSunday(date) {
            var day = date.getDay();
            var diff = date.getDate() - day;
            return new Date(date.setDate(diff));
        }


        function getWeekNumber(date) {
            var currentDate = new Date(date.getTime());
            var startDate = new Date(currentDate.getFullYear(), 0, 1); // 현재 연도의 1월 1일
            var days = Math.floor((currentDate - startDate) / (24 * 60 * 60 * 1000));
            var weekNumber = Math.ceil(days / 7);
            return weekNumber;
        }























    });



    $('#initialize').click(function() {
        // 출석 유형 초기화
        $('#attendanceTypes input[type="radio"]').prop('checked', false);
    });


    $('#saveGroup').click(function() {
        var group_name = $('#group_name').val();

        $.ajax({
            url: '<?php echo base_url("main/add_group"); ?>',
            method: 'POST',
            data: { group_name: group_name },
            dataType: 'json',
            success: function(response) {
                if (response.status == 'success') {
                    $('#groupModal').modal('hide');
                    $('#group_name').val('');
                    refreshGroupList();
                } else {
                    alert('그룹 추가에 실패했습니다.');
                }
            }
        });
    });





    // 삭제 버튼 클릭 이벤트
    $(document).on('click', '.btn-del', function() {
        var groupId = $('#edit_group_id').val();

        if (confirm('정말 삭제하시겠습니까?')) {
            $.ajax({
                url: '<?php echo base_url("main/update_del_yn"); ?>',
                method: 'POST',
                data: { group_id: groupId },
                dataType: 'json',
                success: function(response) {
                    if (response.status == 'success') {
                        $('#settingGroupModal').modal('hide');
                        refreshGroupList(function() {
                            var firstGroup = $('.group-list ul li:first-child');
                            if (firstGroup.length > 0) {
                                var firstGroupId = firstGroup.find('.btn-setting').data('group-id');
                                firstGroup.addClass('active');
                                setCookie('activeGroup', firstGroupId, 7);
                                $('h2.mb-1').text(firstGroup.text().trim());
                                loadMembers(firstGroupId, getWeekStartDate(getCurrentWeekRange()), getWeekEndDate(getCurrentWeekRange()));
                            }
                        });
                    } else {
                        alert('그룹 삭제에 실패했습니다.');
                    }
                }
            });
        }
    });


    // 현재 선택된 주차 범위 가져오기 함수 추가
    function getCurrentWeekRange() {
        return $('.current-week').text();
    }


    // 페이지 최초 로드 시 쿠키에 저장된 활성화된 그룹 확인
    var activeGroupId = getCookie('activeGroup');
    var groups = $('.group-list ul li');

    if (activeGroupId && $('a[data-group-id="' + activeGroupId + '"]').length > 0) {
        $('a[data-group-id="' + activeGroupId + '"]').parent('li').addClass('active');

        var currentWeekRange = $('.current-week').text();
        var startDate = getWeekStartDate(currentWeekRange);
        var endDate = getWeekEndDate(currentWeekRange);

        loadMembers(activeGroupId, startDate, endDate);
    } else {
        // 활성화된 그룹이 없거나 쿠키에 저장된 그룹이 없는 경우 첫 번째 그룹 활성화
        if (groups.length > 0) {
            var firstGroup = groups.first();
            var firstGroupId = firstGroup.find('.btn-setting').data('group-id');
            firstGroup.addClass('active');
            setCookie('activeGroup', firstGroupId, 7);

            var currentWeekRange = $('.current-week').text();
            var startDate = getWeekStartDate(currentWeekRange);
            var endDate = getWeekEndDate(currentWeekRange);

            loadMembers(firstGroupId, startDate, endDate);
        }
    }


    // 그룹 클릭 이벤트 핸들러 수정
    $(document).on('click', '.group-list li', function() {
        var groupId = $(this).find('.btn-setting').data('group-id');
        var groupName = $(this).find('.btn-setting').data('group-name');

        // 모든 group-list 항목에서 active 클래스 제거
        $('.group-list li').removeClass('active');
        // 클릭한 group-list 항목에 active 클래스 추가
        $(this).addClass('active');

        // 쿠키에 활성화된 그룹 ID 저장
        setCookie('activeGroup', groupId, 7);

        // <h2> 태그의 내용을 선택된 그룹의 group_name으로 변경
        $('h2.mb-1').text(groupName);

        // 현재 선택된 주차 범위 가져오기
        var currentWeekRange = $('.current-week').text();
        var startDate = getWeekStartDate(currentWeekRange);
        var endDate = getWeekEndDate(currentWeekRange);

        loadMembers(groupId, startDate, endDate);
    });


    // 주차 범위 업데이트 함수 수정
    function updateWeekRange(weekRange) {
        $('.current-week').text(weekRange);
        var startDate = getWeekStartDate(weekRange);
        var endDate = getWeekEndDate(weekRange);
        var activeGroupId = $('.group-list li.active .btn-setting').data('group-id');
        if (activeGroupId) {
            loadMembers(activeGroupId, startDate, endDate, false);
        }
    }

</script>

</body>
</html>