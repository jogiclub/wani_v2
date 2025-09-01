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

<!-- 내용전달용 토스트 -->
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

<script src="/assets/js/qrcheck.js?<?php echo date('Ymdhis');?>"></script>
<script>

	// 페이지 최초 로드 시 그룹 정보 확인
	var postOrgId = '<?php echo isset($postOrg['org_id']) ? $postOrg['org_id'] : ''; ?>';
	var postOrgName = '<?php echo isset($postOrg['org_name']) ? $postOrg['org_name'] : ''; ?>';
	var logoImg = '<img src="/assets/images/logo_speech.png">';
	$('.org-name b').text(postOrgName);
	$('.org-name b').prepend(logoImg);
	var activeOrgId = getCookie('activeOrg');
	var userLevel = '<?php echo $user_level; ?>';

	if (postOrgId) {
		loadMembers(postOrgId, userLevel);
		setCookie('activeOrg', postOrgId, 7);
	} else if (activeOrgId) {
		loadMembers(activeOrgId, userLevel);
	} else {
		alert('잘못된 경로로 접근하셨습니다. 다시 접속 바랍니다.')
	}

	$.ajax({
		url: '/main/get_attendance_types',
		method: 'POST',
		data: { org_id: activeOrgId, level: userLevel },
		dataType: 'json',
		success: function(response) {
			attendanceTypes = response.attendance_types;
		}
	});

	var initialMode = '<?php echo $mode; ?>';

	$(document).ready(function() {
		$('[data-bs-toggle="popover"]').each(function() {
			$(this).popover({
				container: 'body'
			});
		});
	});

</script>

</body>
</html>
