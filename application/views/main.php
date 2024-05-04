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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css" integrity="sha512-jnSuA4Ss2PkkikSOLtYs8BlYIeeIK1h99ty4YfvRPAlzr377vr3CXDb7sb7eEEBYjDtcYj+AjBH3FLv5uSJuXg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.13.3/themes/base/jquery-ui.min.css" integrity="sha512-8PjjnSP8Bw/WNPxF6wkklW6qlQJdWJc/3w/ZQPvZ/1bjVDkrrSqLe9mfPYrMxtnzsXFPc434+u4FHLnLjXTSsg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css" integrity="sha512-dPXYcDub/aeb08c63jRq/k6GaKccl256JQy/AnOq7CAnEZ9FzSL9wSbcZkMp4R26vBsMLFYH4kQ67/bbV8XaCQ==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/pretendard/1.3.9/static/Pretendard-Medium.min.css" integrity="sha512-kGIhgYqdeB+e4PO0Ipx+D4jNIKPVkdcLHOfT107f/MwZavLS+zhOPKa2vD7kTQHB16mkcwh4MBXsfPF2ODadyQ==" crossorigin="anonymous" referrerpolicy="no-referrer" />
<!--    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/croppie/2.6.5/croppie.min.css">-->


    <link rel="stylesheet" href="/assets/css/common.css?<?php echo date('Ymdhis');?>">





</head>
<body>

<div class="header pt-3 pb-3">
    <div class="container-xl">
        <div class="row">
            <div class="col-12 text-center position-relative">
                <h2 class="mb-1 group-name"></h2>
                <button class="btn-gnb" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasRight" aria-controls="offcanvasRight"><i class="bi bi-list"></i></button>
                <button class="btn-home" type="button" onclick="go_url('/mypage')"><i class="bi bi-arrow-left-short"></i></button>
            </div>


            <div class="col-xl-12 text-center mt-3 mb-3 mode-list">
                <div class="btn-group" role="group" aria-label="Vertical radio toggle button group">
                    <input type="radio" class="btn-check" name="vbtn-radio" id="mode-1" autocomplete="off" checked>
                    <label class="btn btn-outline-secondary" for="mode-1"><i class="bi bi-clipboard-check"></i> 출석모드</label>
                    <input type="radio" class="btn-check" name="vbtn-radio" id="mode-2" autocomplete="off">
                    <label class="btn btn-outline-secondary" for="mode-2"><i class="bi bi-person-badge"></i> 관리모드</label>
                    <input type="radio" class="btn-check" name="vbtn-radio" id="mode-3" autocomplete="off">
                    <label class="btn btn-outline-secondary" for="mode-3"><i class="bi bi-journals"></i> 메모모드</label>
                    <input type="radio" class="btn-check" name="vbtn-radio" id="mode-4" autocomplete="off">
                    <label class="btn btn-outline-secondary" for="mode-4"><i class="bi bi-cake2"></i> 생일모드</label>
                </div>
            </div>

            <div class="col-xl-12">
                <div class="input-group">
                    <button type="button" class="input-group-text prev-week"><i class="bi bi-chevron-left"></i></button>
                    <button type="button" class="input-group-text dropdown-toggle current-week" data-bs-toggle="dropdown" aria-expanded="false">
                        <?php echo $current_week_range; ?>
                    </button>
                    <ul class="dropdown-menu dropdown-current-week">
                        <?php foreach ($all_week_ranges as $week_range): ?>
                        <li><a class="dropdown-item" href="#"><?php echo $week_range; ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                    <button type="button" class="input-group-text next-week"><i class="bi bi-chevron-right"></i></button>
                    <input type="text" class="form-control" placeholder="검색중..." aria-label="검색중..." aria-describedby="basic-addon2" id="input-search" value="검색중..." disabled>
                    <div class="att-dropdown-wrap">
                        <button class="input-group-text dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" id="dropdown-toggle-att-type"></button>
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
                    </div>
                    <button class="btn btn-primary" id="btn-submit"><i class="bi bi-check2-square"></i> 출석</button>


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
                    <div class="col-6 gap-0">
                        <button type="button" class="btn btn-secondary" id="initialize">초기화</button>
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

<!-- 멤버 정보 수정 offcanvas -->
<div class="offcanvas offcanvas-end" data-bs-scroll="true" data-bs-backdrop="false" tabindex="-1" id="memberOffcanvas" aria-labelledby="memberOffcanvasLabel">
    <div class="offcanvas-header text-start">
        <h5 class="offcanvas-title" id="memberOffcanvasLabel">멤버 정보 수정</h5>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
        <form id="memberForm" enctype="multipart/form-data">
            <input type="hidden" id="memberIdx" name="member_idx">
            <input type="hidden" id="groupId" name="group_id">
            <div class="row">
                <div class="d-flex justify-content-end text-end mb-4">
                    <div class="form-check form-switch">
                        <input type="checkbox" class="form-check-input" id="leaderYn" name="leader_yn">
                        <label class="form-check-label" for="leaderYn">리더</label>
                    </div>
                    <div class="form-check form-switch ms-3">
                        <input type="checkbox" class="form-check-input" id="newYn" name="new_yn">
                        <label class="form-check-label" for="newYn">새가족</label>
                    </div>
                </div>


                <div class="col-6 mb-1 text-center">
                <?php if (!empty($member['photo'])): ?>
                    <span class="member-photo" style="background: url('<?php echo base_url('/uploads/member_photos/' . $member['photo']); ?>') center center/cover"></span>
                <?php else: ?>
                    <span class="member-photo"></span>
                <?php endif; ?>
                </div>
                <div class="col-6 mb-1">
                    <label for="memberName" class="form-label">이름</label>
                    <input type="text" class="form-control" id="memberName" name="member_name">

                    <label for="memberPhone" class="form-label">연락처</label>
                    <input type="text" class="form-control" id="memberPhone" name="member_phone">
                </div>
                <div class="col-6 mb-1">
                    <label for="grade" class="form-label">학년</label>
                    <input type="number" class="form-control" id="grade" name="grade">
                </div>
                <div class="col-6 mb-1">
                    <label for="area" class="form-label">목장</label>
                    <input type="text" class="form-control" id="area" name="area">
                </div>


                <div class="col-12 mb-1">
                    <label for="memberNick" class="form-label">별명</label>
                    <input type="text" class="form-control" id="memberNick" name="member_nick">
                </div>

                <div class="col-6 mb-1">
                    <label for="memberBirth" class="form-label">생년월일</label>
                    <input type="date" class="form-control" id="memberBirth" name="member_birth">
                </div>
                <div class="col-6 mb-1">
                    <label for="school" class="form-label">학교</label>
                    <input type="text" class="form-control" id="school" name="school">
                </div>
                <div class="mb-1">
                    <label for="address" class="form-label">주소</label>
                    <input type="text" class="form-control" id="address" name="address">
                </div>
                <div class="mb-1">
                    <label for="photo" class="form-label">사진</label>
                    <input type="file" class="form-control" id="photo" name="photo" accept="image/*">
                </div>



                <div class="mb-1">
                    <label for="memberEtc" class="form-label">특이사항</label>
                    <textarea class="form-control" id="memberEtc" name="member_etc" rows="3"></textarea>
                </div>


            </div>
        </form>
    </div>


    <div class="offcanvas-footer">
        <div class="d-grid gap-2">
            <button type="button" class="btn btn-primary" id="saveMember">저장</button>
        </div>
    </div>
</div>



<!-- 메모 추가 offcanvas -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="addMemoOffcanvas" aria-labelledby="addMemoOffcanvasLabel">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title" id="addMemoOffcanvasLabel">메모 추가</h5>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
        <form id="memoForm">
            <input type="hidden" id="memoMemberIdx" name="member_idx">
            <div class="mb-1">
                <label for="memoType" class="form-label">메모 유형</label>
                <select class="form-control" id="memoType" name="memo_type">
                    <option value="1">일반 메모</option>
                    <option value="2">심방 메모</option>
                </select>
            </div>
            <div class="mb-1">
                <label for="memoContent" class="form-label">메모 내용</label>
                <textarea class="form-control" id="memoContent" name="memo_content" rows="3"></textarea>
            </div>
        </form>
        <div class="btn-group">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="offcanvas">취소</button>
        <button type="button" class="btn btn-primary" id="saveMemo">저장</button>
        </div>
123
        <div class="memo-list">
            <ul>
                <li></li>
            </ul>
        </div>
    </div>

</div>

<!--내용전달용 토스트-->
<div class="toast-container position-fixed bottom-0 end-0 p-3">
    <div id="liveToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header">
            <i class="bi bi-chat-heart-fill"></i>
            <strong class="me-auto"> 출석체크 완료!</strong>
            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body">
        </div>
    </div>
</div>

<audio id="sound-ok" src="/assets/sound/sound_ok.mp3"></audio>
<audio id="sound-no" src="/assets/sound/sound_no.mp3"></audio>
<audio id="sound-birth" src="/assets/sound/sound_birth.mp3"></audio>

<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/2.11.8/umd/popper.min.js" integrity="sha512-TPh2Oxlg1zp+kz3nFA0C5vVC6leG/6mm1z9+mA81MI5eaUVqasPLO8Cuk4gMF4gUfP5etR73rgU/8PNMsSesoQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/js/bootstrap.min.js" integrity="sha512-ykZ1QQr0Jy/4ZkvKuqWn4iF3lqPZyij9iRv6sGqLRdTPkY69YX6+7wvVGmsdBbiIfN/8OdsI7HABjvEok6ZopQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js" integrity="sha512-v2CJ7UaYy4JwqLDIrZUI/4hqeoQieOmAZNXBeQyjo21dadnwR+8ZaIJVT8EE2iyI61OV8e6M8PP2/4hpQINQ/g==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.13.3/jquery-ui.min.js" integrity="sha512-Ww1y9OuQ2kehgVWSD/3nhgfrb424O3802QYP/A5gPXoM4+rRjiKrjHdGxQKrMGQykmsJ/86oGdHszfcVgUr4hA==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/masonry/4.2.2/masonry.pkgd.min.js" integrity="sha512-JRlcvSZAXT8+5SQQAvklXGJuxXTouyq8oIMaYERZQasB8SBDHZaUbeASsJWpk0UUrf89DP3/aefPPrlMR1h1yQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<!--<script src="https://cdnjs.cloudflare.com/ajax/libs/croppie/2.6.5/croppie.min.js"></script>-->
<script src="/assets/js/common.js?<?php echo date('Ymdhis');?>"></script>
<script src="/assets/js/main.js?<?php echo date('Ymdhis');?>"></script>
<script>

    // 페이지 최초 로드 시 그룹 정보 확인
    var postGroupId = '<?php echo isset($postGroup['group_id']) ? $postGroup['group_id'] : ''; ?>';
    var postGroupName = '<?php echo isset($postGroup['group_name']) ? $postGroup['group_name'] : ''; ?>';
    $('.group-name').text(postGroupName);
    // $('.group-name').prepend('<img src="/assets/images/logo.png" style="height: 34px; margin-right: 10px">');
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


</script>

</body>
</html>