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

                <h2 class="mb-1 group-name"></h2>


                <button class="btn-gnb" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasRight" aria-controls="offcanvasRight"><i class="bi bi-list"></i></button>
            </div>


            <div class="col-xl-12 text-center mb-3 mode">
                <div class="btn-group" role="group" aria-label="Vertical radio toggle button group">
                    <input type="radio" class="btn-check" name="vbtn-radio" id="mode-1" autocomplete="off" checked>
                    <label class="btn btn-outline-primary" for="mode-1"><i class="bi bi-clipboard-check"></i> 출석모드</label>
                    <input type="radio" class="btn-check" name="vbtn-radio" id="mode-2" autocomplete="off">
                    <label class="btn btn-outline-primary" for="mode-2"><i class="bi bi-person-badge"></i> 관리모드</label>
                    <input type="radio" class="btn-check" name="vbtn-radio" id="mode-3" autocomplete="off">
                    <label class="btn btn-outline-primary" for="mode-3"><i class="bi bi-journals"></i> 메모모드</label>
                    <input type="radio" class="btn-check" name="vbtn-radio" id="mode-4" autocomplete="off">
                    <label class="btn btn-outline-primary" for="mode-4"><i class="bi bi-cake2"></i> 생일모드</label>
                </div>
            </div>

            <div class="col-xl-12">
                <div class="input-group">


                    <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" id="dropdown-toggle-att-type"><?php echo $default_attendance_type; ?></button>
                    <ul class="dropdown-menu dropdown-att-type">
                        <?php $prev_category_idx = null; ?>
                        <?php foreach ($attendance_types as $type): ?>
                            <?php if ($prev_category_idx !== null && $prev_category_idx !== $type['att_type_category_idx']): ?>
                                <li><hr class="dropdown-divider"></li>
                            <?php endif; ?>
                            <li>
                                <a class="dropdown-item" href="#" data-att-type-idx="<?php echo $type['att_type_idx']; ?>" data-att-type-nickname="<?php echo $type['att_type_nickname']; ?>" data-att-type-category-idx="<?php echo $type['att_type_category_idx']; ?>">
                                    <?php echo $type['att_type_name']; ?>
                                </a>
                            </li>
                            <?php $prev_category_idx = $type['att_type_category_idx']; ?>
                        <?php endforeach; ?>
                    </ul>
                    <input type="text" class="form-control" placeholder="QR코드 또는 이름을 입력하세요!" aria-label="QR코드 또는 이름을 입력하세요!" aria-describedby="basic-addon2" id="input-search" value="검색중..." disabled>
                    <button class="input-group-text" id="btn-submit"><i class="bi bi-check2-square"></i> 출석</button>

                </div>
            </div>



            <div class="col-xl-12 text-center">
                <div class="btn-group week-list mt-1" role="group">
                    <button type="button" class="btn btn-primary prev-week"><i class="bi bi-chevron-left"></i></button>
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-outline-primary dropdown-toggle current-week" data-bs-toggle="dropdown" aria-expanded="false">
                            <?php echo $current_week_range; ?>
                        </button>
                        <ul class="dropdown-menu dropdown-current-week">
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




<!-- 사이드 메뉴 -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasRight" aria-labelledby="offcanvasRightLabel">
    <div class="offcanvas-header">


        <img src="<?php echo $user['picture']; ?>" class="rounded-circle mb-3" width="50" height="50">
        <h5 class="card-title"><?php echo $user['name']; ?></h5>
        <p class="card-text"><?php echo $user['email']; ?></p>
        <a href="<?php echo base_url('main/logout'); ?>" class="btn btn-danger">로그아웃</a>


        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>


    </div>
    <div class="offcanvas-body">
        ...
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

    // 페이지 최초 로드 시 그룹 정보 확인
    var postGroupId = '<?php echo isset($postGroup['group_id']) ? $postGroup['group_id'] : ''; ?>';
    var postGroupName = '<?php echo isset($postGroup['group_name']) ? $postGroup['group_name'] : ''; ?>';
    $('.group-name').text(postGroupName);
    var activeGroupId = getCookie('activeGroup');

    if (postGroupId) {
        // postGroup이 있는 경우 해당 그룹 정보 사용
        var currentWeekRange = $('.current-week').text();
        var startDate = getWeekStartDate(currentWeekRange);
        var endDate = getWeekEndDate(currentWeekRange);

        loadMembers(postGroupId, startDate, endDate);
        setCookie('activeGroup', postGroupId, 7);
    } else if (activeGroupId) {
        // postGroup이 없고 쿠키에 저장된 그룹이 있는 경우 해당 그룹 정보 사용
        var currentWeekRange = $('.current-week').text();
        var startDate = getWeekStartDate(currentWeekRange);
        var endDate = getWeekEndDate(currentWeekRange);
        loadMembers(activeGroupId, startDate, endDate);
    } else {
        // postGroup도 없고 쿠키에 저장된 그룹도 없는 경우 첫 번째 그룹 활성화
        var firstGroupId = '<?php echo $groups[0]['group_id'] ?? ''; ?>';
        if (firstGroupId) {
            setCookie('activeGroup', firstGroupId, 7);
            var currentWeekRange = $('.current-week').text();
            var startDate = getWeekStartDate(currentWeekRange);
            var endDate = getWeekEndDate(currentWeekRange);
            loadMembers(firstGroupId, startDate, endDate);
        } else {
            alert('활성화 된 그룹이 없습니다! 그룹 생성 후 다시 시도해주세요!');
        }

    }



    // 현재 선택된 주차 범위 가져오기 함수 추가
    function getCurrentWeekRange() {
        return $('.current-week').text();
    }







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








    //새로운 멤버를 추가
    $('#saveNewMember').click(function() {
        var member_name = $('#member_name').val();
        var activeGroupId = getCookie('activeGroup');

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
        var groupId = getCookie('activeGroup');

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
                // 버튼 클릭 이벤트 추가

                $('#attendanceModal').modal('show');
            }
        });
    }



    // 출석 정보 저장
    $('#saveAttendance').click(function() {
        var memberIdx = $('#selectedMemberIdx').val();
        var attendanceData = [];
        var activeGroupId = getCookie('activeGroup');

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
                att_date: attDate,
                start_date: startDate,
                end_date: endDate
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
                    memberCard.find('.member-card .member-wrap').prepend('<span class="badge text-bg-primary"><?php echo $leader_name; ?></span>');
                    memberCard.find('.member-card').prepend('<span class="photo"></span>');
                }

                if (member.new_yn === 'Y') {
                    memberCard.find('.member-card').addClass('new');
                    memberCard.find('.member-card').prepend('<span class="badge text-bg-warning"><?php echo $new_name; ?></span>');
                }

                if (member.att_type_data) {
                    var attTypeData = member.att_type_data.split('|');
                    var attStamps = attTypeData.map(function(attData) {
                        var attDataArr = attData.split(',');
                        var attType = attDataArr[0].trim();
                        var attTypeIdx = attDataArr[1].trim();
                        var attTypeCategoryIdx = attDataArr[2].trim();

                        // 출석 유형별 숫자 카운트
                        if (attTypeCount[attType]) {
                            attTypeCount[attType]++;
                        } else {
                            attTypeCount[attType] = 1;
                        }

                        return {
                            attType: attType,
                            attTypeIdx: attTypeIdx,
                            attTypeCategoryIdx: attTypeCategoryIdx
                        };
                    });

                    // att-stamp를 data-att-type-idx 순서로 정렬
                    attStamps.sort(function(a, b) {
                        return a.attTypeIdx - b.attTypeIdx;
                    });

                    var attTypesHtml = attStamps.map(function(attStamp) {
                        return '<span class="att-stamp" data-att-type-idx="' + attStamp.attTypeIdx + '" data-att-type-category-idx="' + attStamp.attTypeCategoryIdx + '">' + attStamp.attType + '</span>';
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
                        var attStamps = attTypeNicknames.split(',').map(function(attTypeData) {
                            var attTypeArr = attTypeData.split('|');
                            var attTypeNickname = attTypeArr[0].trim();
                            var attTypeIdx = attTypeArr[1].trim();
                            var attTypeCategoryIdx = attTypeArr[2].trim();

                            // 출석 유형별 숫자 카운트
                            if (attTypeCount[attTypeNickname]) {
                                attTypeCount[attTypeNickname]++;
                            } else {
                                attTypeCount[attTypeNickname] = 1;
                            }

                            return {
                                attTypeNickname: attTypeNickname,
                                attTypeIdx: attTypeIdx,
                                attTypeCategoryIdx: attTypeCategoryIdx
                            };
                        });

                        // att-stamp를 data-att-type-idx 순서로 정렬
                        attStamps.sort(function(a, b) {
                            return a.attTypeIdx - b.attTypeIdx;
                        });

                        var attTypesHtml = attStamps.map(function(attStamp) {
                            return '<span class="att-stamp" data-att-type-idx="' + attStamp.attTypeIdx + '" data-att-type-category-idx="' + attStamp.attTypeCategoryIdx + '">' + attStamp.attTypeNickname + '</span>';
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








    // 주차 범위 업데이트 함수 수정
    function updateWeekRange(weekRange) {
        $('.current-week').text(weekRange);
        var startDate = getWeekStartDate(weekRange);
        var endDate = getWeekEndDate(weekRange);
        var activeGroupId = getCookie('activeGroup');
        if (activeGroupId) {
            updateAttStamps(activeGroupId, startDate, endDate);
        }
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
        $('.grid').masonry('layout');
    }

    function resetMemberList() {
        $('.grid-item').show();

        // Masonry 레이아웃 업데이트
        $('.grid').masonry('layout');
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
                    alert('이미 출석체크를 하였습니다.');
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
                memberCard.find('.member-wrap').append(attStamp);

                // 서버에 출석 정보 저장
                saveAttendance(memberIdx, attTypeIdx, attTypeCategoryIdx);
            } else {
                alert('해당하는 정보가 없습니다.');
            }
        }
        $('#input-search').val('').focus();
    }







    function saveAttendance(memberIdx, attTypeIdx, attTypeCategoryIdx) {
        var activeGroupId = getCookie('activeGroup');
        var today = new Date();
        var attDate = formatDate(today);

        $.ajax({
            url: '<?php echo base_url("main/save_single_attendance"); ?>',
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
            url: '<?php echo base_url("main/get_attendance_types"); ?>',
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












    $(document).ready(function() {

        // 페이지 로드 시 input-search에 포커스 설정
        $('#input-search').focus();





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
        $('.dropdown-current-week .dropdown-item').click(function(e) {
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





        // 오늘 날짜가 current-week의 기간 안에 있는지 확인하고 input-search 활성화/비활성화
        function updateInputSearchState() {
            var currentWeekRange = $('.current-week').text();
            var startDate = getWeekStartDate(currentWeekRange);
            var endDate = getWeekEndDate(currentWeekRange);
            var today = new Date();
            var formattedToday = formatDate(today);

            if (formattedToday >= startDate && formattedToday <= endDate) {
                $('#input-search').prop('disabled', false).val('').attr('placeholder', 'QR코드 또는 이름을 입력하세요!').focus();
            } else {
                $('#input-search').prop('disabled', true).val('검색중...').attr('placeholder', '');
                resetMemberList();
            }
        }

        // 페이지 로드 시 input-search 상태 업데이트
        updateInputSearchState();

        // 주차 범위 업데이트 시 input-search 상태 업데이트
        function updateWeekRange(weekRange) {
            $('.current-week').text(weekRange);
            var startDate = getWeekStartDate(weekRange);
            var endDate = getWeekEndDate(weekRange);
            var activeGroupId = getCookie('activeGroup');
            if (activeGroupId) {
                updateAttStamps(activeGroupId, startDate, endDate);
            }
            updateInputSearchState();
        }

















    });


</script>

</body>
</html>