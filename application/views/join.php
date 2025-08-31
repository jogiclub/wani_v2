<?php $this->load->view('noheader'); ?>




				<h2 class="text-center">회원가입</h2>
				<form action="<?php echo base_url('login/process'); ?>" method="post">
					<input type="hidden" class="form-control" id="user_id" name="user_id" value="<?php echo isset($user_id) ? $user_id : ''; ?>">

					<div class="mb-3">
						<label for="user_name" class="form-label">이름</label>
						<input type="text" class="form-control" id="user_name" name="user_name" value="<?php echo isset($user_name) ? $user_name : ''; ?>" required>
					</div>

					<div class="mb-3">
						<label for="user_email" class="form-label">이메일</label>
						<input type="email" class="form-control" id="user_email" name="user_email" value="<?php echo isset($user_email) ? $user_email : ''; ?>" required>
					</div>

					<div class="mb-3">
						<label for="invite_code" class="form-label">초대코드 <span class="text-muted">(선택사항)</span></label>
						<input type="text" class="form-control" id="invite_code" name="invite_code" placeholder="조직에서 받은 초대코드를 입력하세요" maxlength="5">
						<div class="form-text">초대코드가 있으시면 입력해주세요. 나중에 마이페이지에서도 추가할 수 있습니다.</div>
					</div>

					<div class="mb-3 form-check">
						<input type="checkbox" class="form-check-input" id="agree_terms" name="agree_terms" required>
						<label class="form-check-label" for="agree_terms">
							<a href="/login/terms" target="_blank">서비스 이용약관</a>에 동의합니다.
						</label>
					</div>

					<div class="mb-3 form-check">
						<input type="checkbox" class="form-check-input" id="agree_privacy" name="agree_privacy" required>
						<label class="form-check-label" for="agree_privacy">
							<a href="/login/privacy" target="_blank">개인정보처리방침</a>에 동의합니다.
						</label>
					</div>

					<button type="submit" class="btn btn-primary w-100">회원가입</button>
				</form>


<?php $this->load->view('footer'); ?>

<script src="/assets/js/login.js?<?php echo date('Ymdhis'); ?>"></script>






