<html lang="ko">
<head>
    <?php $this->load->view('header'); ?>
</head>
<body>

<div class="pt-3 pb-2 ">
    <div class="container">
        <div class="row">



            <div class="col-xl-12 text-center mt-1 mb-3 mode-list">
                <div class="btn-group" role="group" aria-label="Vertical radio toggle button group">
                    <input type="radio" class="btn-check" name="vbtn-radio" id="mode-1" autocomplete="off" >
                    <label class="btn btn-outline-secondary" for="mode-1"><i class="bi bi-qr-code"></i> QR모드</label>
                    <input type="radio" class="btn-check" name="vbtn-radio" id="mode-2" autocomplete="off">
                    <label class="btn btn-outline-secondary" for="mode-2"><i class="bi bi-person-badge"></i> 관리모드</label>
                    <input type="radio" class="btn-check" name="vbtn-radio" id="mode-3" autocomplete="off">
                    <label class="btn btn-outline-secondary" for="mode-3"><i class="bi bi-journals"></i> 메모모드</label>
                    <input type="radio" class="btn-check" name="vbtn-radio" id="mode-4" autocomplete="off">
                    <label class="btn btn-outline-secondary" for="mode-4"><i class="bi bi-clipboard-check"></i> 출석모드</label>
                </div>
            </div>

            <div class="col-lg-5 mb-2">
                <div class="input-group">
                    <button type="button" class="input-group-text prev-week"><i class="bi bi-chevron-left"></i></button>
                    <button type="button" class="input-group-text dropdown-toggle current-week" data-bs-toggle="dropdown" aria-expanded="false">
                        <!-- 현재 주차 범위는 프론트엔드에서 동적으로 설정됩니다. -->
                    </button>
                    <ul class="dropdown-menu dropdown-current-week">
                        <!-- 주차 범위 드롭다운 메뉴는 프론트엔드에서 동적으로 생성됩니다. -->
                    </ul>
                    <button type="button" class="input-group-text next-week"><i class="bi bi-chevron-right"></i></button>
                </div>
            </div>
            <div class="col-lg-7 mb-2">
                <div class="input-group">
                    <input type="text" class="form-control" placeholder="검색중..." aria-label="검색중..." aria-describedby="basic-addon2" id="input-search" value="검색중..." autocomplete="off"  disabled>
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





            <div class="text-center total-list-wrap">

                <dl class="total-list">
                    <dt>총재적</dt><dd>00</dd>
                    <dt>재적</dt><dd>00</dd>
                    <dt>목자</dt><dd>00</dd>
                    <dt>새가족</dt><dd>00</dd>
                </dl>

                <dl class="total-att-list">
                    <dt>출</dt><dd>00</dd>
                    <dt>온</dt><dd>00</dd>
                    <dt>장</dt><dd>00</dd>
                </dl>


                <dl class="mt-2 mb-2 total-memo-list">
                    <dt>메모</dt><dd>00</dd>
                </dl>

            </div>

        </div>
    </div>
</div>
<main>
    <div class="container-xl">
        <div class="row">
        <div class="member-list">
            <div class="d-flex justify-content-end hide5weekAgo-warp">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" role="switch" id="hide5weekAgo">
                    <label class="form-check-label" for="hide5weekAgo">5주 이전 출석자 숨기기</label>
                </div>
            </div>

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
	<button class="input-group-text btn-area" id="basic-addon3" data-bs-toggle="modal" data-bs-target="#newAreaModal"><i class="bi bi-folder"></i></button>
    <button class="input-group-text btn-new" id="basic-addon2" data-bs-toggle="modal" data-bs-target="#newMemberModal"><i class="bi bi-person-plus"></i></button>
</footer>





<!-- 소그룹 관리 모달 -->
<div class="modal fade" id="newAreaModal" tabindex="-1" aria-labelledby="newAreaModalLabel" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="newAreaModalLabel">소그룹 관리</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<div class="mb-4">
					<h6>소그룹 추가</h6>
					<div class="input-group">
						<input type="text" class="form-control" id="area_name" name="area_name" placeholder="소그룹명" required>
						<button type="button" class="btn btn-primary" id="addNewArea">추가</button>
					</div>
				</div>
				<div>
					<h6>소그룹 목록</h6>
					<div id="areaList" class="mb-3 area-list">
						<!-- 소그룹 목록이 동적으로 추가됨 -->
					</div>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
				<button type="button" class="btn btn-primary" id="saveAreaChanges">저장</button>
			</div>
		</div>
	</div>
</div>

<!-- 토스트 메시지 -->
<div class="toast-container position-fixed bottom-0 end-0 p-3">
	<div id="areaToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
		<div class="toast-header">
			<strong class="me-auto">알림</strong>
			<button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
		</div>
		<div class="toast-body"></div>
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
                <div class="mb-3">
                    <label for="newMemberAreaIdx" class="form-label">소그룹</label>
                    <select class="form-select" id="newMemberAreaIdx" name="area_idx" required>
                        <?php foreach ($member_areas as $area): ?>
                            <option value="<?php echo $area['area_idx']; ?>"><?php echo $area['area_name']; ?></option>
                        <?php endforeach; ?>
                    </select>
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





                <!--
                                <div class="col-6 mb-1">
                                    <label for="grade" class="form-label">소그룹ID</label>
                                    <input type="number" class="form-control" id="grade" name="grade">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" value="" id="allGradeCheck">
                                        <label class="form-check-label" for="allGroupCheck">
                                            동일 소그룹ID 함께 수정
                                        </label>
                                    </div>
                </div>
                <div class="col-6 mb-1">
                    <label for="area" class="form-label">소그룹</label>
                    <input type="text" class="form-control" id="area" name="area">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="" id="allAreaCheck">
                        <label class="form-check-label" for="allGroupCheck">
                            동일 소그룹 함께 수정
                        </label>
                    </div>
                </div>-->
                <input type="hidden" class="form-control" id="areaName">
                <div class="col-6 mb-1">
                    <label for="area-idx" class="form-label">소그룹</label>
                    <select class="form-select" aria-label="소그룹선택" id="areaIdx" name="area_idx">
                        <?php foreach ($member_areas as $area): ?>
                            <option value="<?php echo $area['area_idx']; ?>"><?php echo $area['area_name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-6 mb-1">
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




            </div>
        </form>
    </div>


    <div class="offcanvas-footer">
        <div class="input-group">
            <button type="button" class="btn btn-danger" id="delMember" style="width: 33.33%">삭제</button>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="offcanvas" style="width: 33.33%">취소</button>
            <button type="button" class="btn btn-primary" id="saveMember" style="width: 33.33%">저장</button>
        </div>
    </div>
</div>



<!-- 메모 추가 offcanvas -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="addMemoOffcanvas" aria-labelledby="addMemoOffcanvasLabel" data-bs-scroll="true" data-bs-backdrop="false">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title" id="addMemoOffcanvasLabel">메모 추가</h5>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
        <div class="memo-wrap">
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

            <div class="input-group">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="offcanvas" style="width: 50%">취소</button>
            <button type="button" class="btn btn-primary" id="saveMemo" style="width: 50%">저장</button>



            </div>
        </div>
        <div class="memo-list">
            <ul>
                <li></li>
            </ul>
        </div>
    </div>

</div>


<!-- 출석체크 offcanvas -->
<div class="offcanvas offcanvas-end" data-bs-scroll="true" data-bs-backdrop="false" tabindex="-1" id="attendanceOffcanvas" aria-labelledby="attendanceOffcanvasLabel">
    <div class="offcanvas-header text-start">
        <h5 class="offcanvas-title" id="attendanceOffcanvasLabel"></h5>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
        <!-- 출석체크 내용 -->
    </div>
    <div class="offcanvas-footer">
        <div class="input-group">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="offcanvas" style="width: 33.33%">취소</button>

            <button type="button" class="btn btn-warning" id="loadLastWeekBtn" style="width: 33.33%">지난 주 정보 불러오기</button>
            <button type="button" class="btn btn-primary" id="saveAttendanceBtn" style="width: 33.33%">저장</button>
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

<?php $this->load->view('footer'); ?>

<script src="/assets/js/main.js?<?php echo date('Ymdhis');?>"></script>
<script>

    // 페이지 최초 로드 시 그룹 정보 확인
    var postGroupId = '<?php echo isset($postGroup['group_id']) ? $postGroup['group_id'] : ''; ?>';
    var postGroupName = '<?php echo isset($postGroup['group_name']) ? $postGroup['group_name'] : ''; ?>';
    var logoImg = '<img src="/assets/images/logo_speech.png">';
    $('.group-name b').text(postGroupName);
    $('.group-name b').prepend(logoImg);
    // $('.group-name').prepend('<img src="/assets/images/logo.png" style="height: 34px; margin-right: 10px">');
    var activeGroupId = getCookie('activeGroup');
    var userLevel = '<?php echo $user_level; ?>';

    // console.log(userLevel);



    if (postGroupId) {
        // postGroup이 있는 경우 해당 그룹 정보 사용
        loadMembers(postGroupId, userLevel);
        setCookie('activeGroup', postGroupId, 7);
    } else if (activeGroupId) {
        // postGroup이 없고 쿠키에 저장된 그룹이 있는 경우 해당 그룹 정보 사용
        loadMembers(activeGroupId, userLevel);
    } else {
        alert('잘못된 경로로 접근하셨습니다. 다시 접속 바랍니다.')
    }




    $.ajax({
        url: '/main/get_attendance_types',
        method: 'POST',
        data: { group_id: activeGroupId, level: userLevel },
        dataType: 'json',
        success: function(response) {
            attendanceTypes = response.attendance_types;
        }
    });


    var initialMode = '<?php echo $mode; ?>'; // PHP에서 전달받은 mode 값을 JavaScript 변수에 할당

    $(document).ready(function() {
        // 초기 모드 설정
        // $('.mode-list .btn-check[value="' + initialMode + '"]').prop('checked', true);
        // applyModeConfig(initialMode);





        $('[data-bs-toggle="popover"]').each(function() {
            $(this).popover({
                container: 'body' // 또는 Modal의 ID 또는 클래스 선택자를 지정할 수 있습니다.
            });
        });



    });



</script>

</body>
</html>
