<?php $this->load->view('header'); ?>
<!-- Member CSS -->
<link rel="stylesheet" href="/assets/css/qrcheck.css?<?php echo WB_VERSION; ?>">

<!-- ParamQuery CSS 및 JavaScript 추가 -->
<link rel="stylesheet" href="/assets/css/custom/pqgrid.min.css">

<div class="pt-3">
	<div class="container-fluid">
		<div class="row">
			<div class="col-lg-5 mb-2">
				<div class="input-group">
					<button type="button" class="input-group-text prev-week"><i class="bi bi-chevron-left"></i></button>
					<!-- 드롭다운에서 단순 텍스트박스로 변경 -->
					<input type="text" class="form-control current-week text-center" readonly>
					<button type="button" class="input-group-text next-week"><i class="bi bi-chevron-right"></i></button>
				</div>
			</div>
			<div class="col-lg-7 mb-2 d-flex align-items-between justify-content-end">
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
				<div class="form-check form-switch d-flex align-items-center ms-2" style="width: 100px">
					<input class="form-check-input" type="checkbox" role="switch" id="switchCheckCamera">
					<label class="form-check-label" for="switchCheckCamera">카메라</label>
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


<div class="container-fluid">
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



<footer>
<!--	<button class="input-group-text btn-new" id="basic-addon2" data-bs-toggle="modal" data-bs-target="#newMemberModal"><i class="bi bi-person-plus"></i></button>-->
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

<!-- 새 회원 추가 모달 -->
<div class="modal fade" id="newMemberModal" tabindex="-1" aria-labelledby="newMemberModalLabel" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="newMemberModalLabel">새 회원 추가</h5>
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




<!-- QR 카메라 스캔 offcanvas -->
<div class="offcanvas offcanvas-bottom z-2" tabindex="-1" id="qrCameraOffcanvas" data-bs-scroll="true" data-bs-backdrop="false" aria-labelledby="qrCameraOffcanvasLabel" style="height: 270px;">
	<div class="offcanvas-header">
		<div class="d-flex align-items-center">
		<h5 class="offcanvas-title" id="qrCameraOffcanvasLabel">QR 코드 스캔</h5>
		<div class="d-flex align-items-center">
			<div class="form-check form-switch ms-3">
				<input class="form-check-input" type="checkbox" role="switch" id="switchCameraFacing">
				<label class="form-check-label" for="switchCameraFacing">전면카메라</label>
			</div>
			<button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
		</div>
		</div>
	</div>
	<div class="offcanvas-body p-1 bg-dark">
		<div id="qr-reader" style="height: 200px; width: 200px"></div>
	</div>
</div>


<!-- 출석체크 offcanvas (pqgrid용으로 수정) -->
<div class="offcanvas offcanvas-end" data-bs-scroll="true" data-bs-backdrop="static" tabindex="-1" id="attendanceOffcanvas" aria-labelledby="attendanceOffcanvasLabel" style="width: 800px;">
	<div class="offcanvas-header text-start">
		<h5 class="offcanvas-title" id="attendanceOffcanvasLabel"></h5>
		<button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
	</div>
	<div class="offcanvas-body">
		<!-- pqgrid가 여기에 동적으로 추가됩니다 -->
	</div>
	<div class="offcanvas-footer">
		<div class="input-group">
			<button type="button" class="btn btn-secondary" data-bs-dismiss="offcanvas" style="width: 33.33%">취소</button>
			<button type="button" class="btn btn-warning" id="loadLastWeekBtn" style="width: 33.33%">지난 주 출석</button>
			<button type="button" class="btn btn-primary" id="saveAttendanceBtn" style="width: 33.33%">저장</button>
		</div>
	</div>
</div>

<audio id="sound-ok" src="/assets/sound/sound_ok.mp3"></audio>
<audio id="sound-no" src="/assets/sound/sound_no.mp3"></audio>
<audio id="sound-birth" src="/assets/sound/sound_birth.mp3"></audio>

<?php $this->load->view('footer'); ?>

<!-- ParamQuery JavaScript 라이브러리 추가 -->
<script src="/assets/js/custom/pqgrid.min.js?<?php echo WB_VERSION; ?>"></script>
<!--<script src="/assets/js/custom/pqgrid.fix.js?--><?php //echo WB_VERSION; ?><!--"></script>-->

<!-- html5-qrcode 라이브러리 추가 -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html5-qrcode/2.3.8/html5-qrcode.min.js" integrity="sha512-r6rDA7W6ZeQhvl8S7yRVQUKVHdexq+GAlNkNNqVC7YyIV+NwqCTJe2hDWCiffTyRNOeGEzRRJ9ifvRm/HCzGYg==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>

<script src="https://unpkg.com/imagesloaded@4/imagesloaded.pkgd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/masonry/4.2.2/masonry.pkgd.min.js" integrity="sha512-JRlcvSZAXT8+5SQQAvklXGJuxXTouyq8oIMaYERZQasB8SBDHZaUbeASsJWpk0UUrf89DP3/aefPPrlMR1h1yQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script src="/assets/js/qrcheck.js?<?php echo WB_VERSION; ?>"></script>
<script>

	/*출석관리 메뉴 active*/
	$('.menu-23').addClass('active');

	// 페이지 최초 로드 시 그룹 정보 확인
	var postOrgId = '<?php echo isset($postOrg['org_id']) ? $postOrg['org_id'] : ''; ?>';
	var postOrgName = '<?php echo isset($postOrg['org_name']) ? $postOrg['org_name'] : ''; ?>';
	var logoImg = '<img src="/assets/images/logo_speech.png">';
	$('.org-name b').text(postOrgName);
	$('.org-name b').prepend(logoImg);
	var activeOrgId = getCookie('activeOrg');

	// 사용자 권한 정보를 JavaScript 전역 변수로 설정
	var userLevel = parseInt('<?php echo isset($user_level) ? $user_level : 1; ?>');
	var masterYn = '<?php echo isset($master_yn) ? $master_yn : 'N'; ?>';

	// 관리 가능한 그룹이 있는지 확인
	var hasManagementPermission = <?php echo (isset($member_areas) && !empty($member_areas)) ? 'true' : 'false'; ?>;

	// 권한이 없는 경우 알림 표시
	if (!hasManagementPermission && userLevel < 10 && masterYn !== 'Y') {
		console.warn('관리 권한이 없는 사용자입니다.');
		// 새 회원 추가 버튼 숨김
		$('.btn-new').hide();
		// displayMembers 함수에서 권한 없음 메시지 표시
		setTimeout(function() {
			if ($('.member-list .grid .grid-item').length === 0) {
				$('.member-list .grid').append('<div class="no-member">회원을 조회할 권한이 없습니다.<br>관리자에게 권한을 요청해주세요.</div>');
			}
		}, 1000);
	}

	if (postOrgId) {
		loadMembers(postOrgId, userLevel);
		setCookie('activeOrg', postOrgId, 7);
	} else if (activeOrgId) {
		loadMembers(activeOrgId, userLevel);
	} else {
		alert('잘못된 경로로 접근하셨습니다. 다시 접속 바랍니다.')
	}

	// 출석 타입 정보 로드
	$.ajax({
		url: '/qrcheck/get_attendance_types',
		method: 'POST',
		data: { org_id: activeOrgId, level: userLevel },
		dataType: 'json',
		success: function(response) {
			attendanceTypes = response.attendance_types;
		},
		error: function(xhr, status, error) {
			console.error('출석 타입 로드 실패:', error);
		}
	});

	

	$(document).ready(function() {
		$('[data-bs-toggle="popover"]').each(function() {
			$(this).popover({
				container: 'body'
			});
		});

		// 권한에 따른 UI 제어
		if (userLevel < 10 && masterYn !== 'Y') {
			// 일반 관리자인 경우
			if (!hasManagementPermission) {
				// 관리 권한이 없는 경우 모든 관리 기능 비활성화
				$('#input-search').prop('disabled', true).attr('placeholder', '관리 권한이 필요합니다');
				$('.att-dropdown-wrap').hide();
				$('#btn-submit').prop('disabled', true);
			}
		}
	});

</script>
