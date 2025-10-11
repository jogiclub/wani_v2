<?php $this->load->view('noheader'); ?>

<div class="col-12 col-sm-5 col-md-4 col-lg-3 mx-auto">
	<h2 class="text-center">회원가입</h2>
	<form id="joinForm" action="<?php echo base_url('login/process'); ?>" method="post">
		<input type="hidden" class="form-control" id="user_id" name="user_id" value="<?php echo isset($user_id) ? $user_id : ''; ?>">

		<div class="mb-3">
			<label for="user_name" class="form-label">이름</label>
			<input type="text" class="form-control" id="user_name" name="user_name" value="<?php echo isset($user_name) ? $user_name : ''; ?>" required>
		</div>

		<div class="mb-3">
			<label for="user_email" class="form-label">이메일</label>
			<input type="email" class="form-control" id="user_email" name="user_email" value="<?php echo isset($user_email) ? $user_email : ''; ?>" disabled>
		</div>

		<div class="mb-3">
			<label for="invite_code" class="form-label">초대코드 <span class="text-danger">*</span></label>
			<input type="text" class="form-control" id="invite_code" name="invite_code" placeholder="초대코드를 입력하세요" maxlength="5" required>
			<div class="form-text">회원가입을 위해서는 초대코드가 필요합니다.<br/>초대코드는 해당 조직의 관리자에게 요청바랍니다.</div>
		</div>

		<div class="mb-1 form-check">
			<input type="checkbox" class="form-check-input" id="agree_terms" name="agree_terms" required>
			<label class="form-check-label" for="agree_terms">
				<a href="/login/terms" target="_blank" class="text-primary">서비스 이용약관</a>에 동의합니다.
			</label>
		</div>

		<div class="mb-3 form-check">
			<input type="checkbox" class="form-check-input" id="agree_privacy" name="agree_privacy" required>
			<label class="form-check-label" for="agree_privacy">
				<a href="/login/privacy" target="_blank" class="text-primary">개인정보처리방침</a>에 동의합니다.
			</label>
		</div>

		<div class="d-grid gap-2">
			<button type="submit" class="btn btn-primary">시작하기 <i class="bi bi-chevron-right"></i></button>
			<button type="button" class="btn btn-outline-primary" id="createOrgBtn">새로운 조직 생성하기 <i class="bi bi-chevron-right"></i></button>
		</div>
	</form>
</div>

<!-- 새로운 조직 생성 모달 -->
<div class="modal fade" id="createOrgModal" tabindex="-1" aria-labelledby="createOrgModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="createOrgModalLabel">새로운 조직 생성</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<form id="createOrgForm">
					<div class="mb-3">
						<label for="org_name" class="form-label">조직명 <span class="text-danger">*</span></label>
						<input type="text" class="form-control" id="org_name" name="org_name" placeholder="조직명을 입력하세요" maxlength="50" required>
					</div>

					<div class="mb-3">
						<label for="org_type" class="form-label">조직유형 <span class="text-danger">*</span></label>
						<select class="form-select" id="org_type" name="org_type" required>
							<option value="">조직유형을 선택하세요</option>
							<option value="교회">교회</option>
							<option value="단체">단체</option>
							<option value="기업">기업</option>
							<option value="학교">학교</option>
							<option value="기타">기타</option>
						</select>
					</div>

					<div class="mb-3">
						<label for="org_desc" class="form-label">조직설명</label>
						<textarea class="form-control" id="org_desc" name="org_desc" rows="3" placeholder="조직에 대한 설명을 입력하세요 (선택사항)" maxlength="500"></textarea>
						<div class="form-text">최대 500자까지 입력 가능합니다.</div>
					</div>
				</form>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
				<button type="button" class="btn btn-primary" id="saveOrgBtn">저장</button>
			</div>
		</div>
	</div>
</div>

<?php $this->load->view('nofooter'); ?>

<script src="/assets/js/login.js?<?php echo WB_VERSION; ?>"></script>
