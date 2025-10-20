
<!DOCTYPE html>
<html lang="ko">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>회원 등록 - <?php echo htmlspecialchars($org_info['org_name']); ?></title>

	<!-- Bootstrap 5.3 -->
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css" integrity="sha512-jnSuA4Ss2PkkikSOLtYs8BlYIeeIK1h99ty4YfvRPAlzr377vr3CXDb7sb7eEEBYjDtcYj+AjBH3FLv5uSJuXg==" crossorigin="anonymous" referrerpolicy="no-referrer"/>
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css" integrity="sha512-dPXYcDub/aeb08c63jRq/k6GaKccl256JQy/AnOq7CAnEZ9FzSL9wSbcZkMp4R26vBsMLFYH4kQ67/bbV8XaCQ==" crossorigin="anonymous" referrerpolicy="no-referrer"/>
	<link rel="stylesheet" as="style" crossorigin href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard@v1.3.9/dist/web/static/pretendard.min.css" />
	<style>
		body {background-color: #f8f9fa;min-height: 100vh;display: flex;align-items: center;padding: 20px 0;font-family: "Pretendard", Pretendard, -apple-system, BlinkMacSystemFont, system-ui, Roboto, "Helvetica Neue", "Segoe UI", "Apple SD Gothic Neo", "Noto Sans KR", "Malgun Gothic", "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", sans-serif;}
		.card {box-shadow: 0 0 20px rgba(0,0,0,0.1);}
		.org-header {background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);color: white;padding: 30px;border-radius: 0.375rem 0.375rem 0 0;}
		.org-logo {width: 80px;height: 80px;border-radius: 50%;background: white;display: flex;align-items: center;justify-content: center;margin: 0 auto 15px;overflow: hidden;}
		.org-logo img {width: 100%;height: 100%;object-fit: cover;}
		.org-logo-text {font-size: 32px;font-weight: bold;color: #667eea;}
	</style>
</head>
<body>
<div class="container">
	<div class="row justify-content-center">
		<div class="col-12 col-md-8 col-lg-6">
			<div class="card">
				<!-- 조직 헤더 -->
				<div class="org-header text-center">
					<div class="org-logo">
						<?php if (!empty($org_info['org_icon'])): ?>
							<img src="<?php echo base_url($org_info['org_icon']); ?>"
								 alt="<?php echo htmlspecialchars($org_info['org_name']); ?>">
						<?php else: ?>
							<span class="org-logo-text">
									<?php echo mb_substr($org_info['org_name'], 0, 1); ?>
								</span>
						<?php endif; ?>
					</div>
					<h4 class="mb-2"><?php echo htmlspecialchars($org_info['org_name']); ?></h4>
					<p class="mb-0 small">
						<span class="me-3"><i class="bi bi-calendar3"></i> <?php echo date('Y-m-d'); ?></span>
						<span><i class="bi bi-folder"></i> <?php echo htmlspecialchars($area_info['area_name']); ?></span>
					</p>
				</div>

				<!-- 등록 폼 -->
				<div class="card-body p-4">

					<!-- 개인정보 수집 및 이용 동의 -->
					<div class="mb-4">
						<h5 class="mb-3">개인정보 수집 및 이용 동의</h5>
						<div class="border rounded p-3 bg-light" style="max-height: 200px; overflow-y: auto;">
							<div class="small">
								<p class="mb-2"><strong>1. 개인정보의 수집 및 이용 목적</strong></p>
								<p class="mb-3">- 조직 회원 관리, 교육 및 활동 안내, 출석 관리, 연락처 관리</p>

								<p class="mb-2"><strong>2. 수집하는 개인정보의 항목</strong></p>
								<p class="mb-3">- 필수항목: 이름, 성별, 연락처, 생년월일, 주소<br>
									- 선택항목: 별명, 직위/직분, 직책, 기타 추가 정보</p>

								<p class="mb-2"><strong>3. 개인정보의 보유 및 이용 기간</strong></p>
								<p class="mb-3">- 회원 탈퇴 시까지 또는 수집 및 이용목적 달성 시까지<br>
									- 관련 법령에 따라 보존할 필요가 있는 경우 해당 기간까지 보관</p>

								<p class="mb-2"><strong>4. 동의를 거부할 권리 및 동의 거부에 따른 불이익</strong></p>
								<p class="mb-0">- 개인정보 수집 및 이용에 대한 동의를 거부할 수 있으며, 거부 시 회원 등록이 제한됩니다.</p>
							</div>
						</div>
						<div class="form-check mt-3">
							<input class="form-check-input" type="checkbox" id="agreePrivacy" required>
							<label class="form-check-label" for="agreePrivacy">
								개인정보 수집 및 이용에 동의합니다. <span class="text-danger">*</span>
							</label>
						</div>
					</div>

					<h5 class="mb-3 pb-2 border-bottom">기본정보</h5>

					<form id="memberCardForm">
						<input type="hidden" name="org_id" value="<?php echo $org_id; ?>">
						<input type="hidden" name="area_idx" value="<?php echo $area_idx; ?>">
						<input type="hidden" name="invite_code" value="<?php echo $invite_code; ?>">

						<div class="row">
							<!-- 이름 & 성별 -->
							<div class="col-6 col-sm-4 mb-3">
								<label for="member_name" class="form-label">이름 <span class="text-danger">*</span></label>
								<div class="input-group">
									<input type="text" class="form-control" id="member_name" name="member_name" required>
									<select class="form-select" id="member_sex" name="member_sex" style="max-width: 80px;">
										<option value="">성별</option>
										<option value="male">남</option>
										<option value="female">여</option>
									</select>
								</div>
							</div>

							<!-- 별명 -->
							<div class="col-6 col-sm-4 mb-3">
								<label for="member_nick" class="form-label">별명</label>
								<input type="text" class="form-control" id="member_nick" name="member_nick">
							</div>

							<!-- 직위/직분 -->
							<div class="col-6 col-sm-4 mb-3">
								<label for="position_name" class="form-label">직위/직분</label>
								<select class="form-select" id="position_name" name="position_name">
									<option value="">직위/직분 선택</option>
									<?php foreach ($position_names as $position): ?>
										<option value="<?php echo htmlspecialchars($position); ?>">
											<?php echo htmlspecialchars($position); ?>
										</option>
									<?php endforeach; ?>
								</select>
							</div>

							<!-- 직책 -->
							<div class="col-6 col-sm-4 mb-3">
								<label for="duty_name" class="form-label">직책</label>
								<select class="form-select" id="duty_name" name="duty_name">
									<option value="">직책 선택</option>
									<?php foreach ($duty_names as $duty): ?>
										<option value="<?php echo htmlspecialchars($duty); ?>">
											<?php echo htmlspecialchars($duty); ?>
										</option>
									<?php endforeach; ?>
								</select>
							</div>

							<!-- 연락처 -->
							<div class="col-6 col-sm-4 mb-3">
								<label for="member_phone" class="form-label">연락처</label>
								<input type="tel" class="form-control" id="member_phone" name="member_phone">
							</div>

							<!-- 생년월일 -->
							<div class="col-6 col-sm-4 mb-3">
								<label for="member_birth" class="form-label">생년월일</label>
								<input type="date" class="form-control" id="member_birth" name="member_birth">
							</div>

							<!-- 주소 -->
							<div class="col-6 col-sm-4 mb-3">
								<label for="member_address" class="form-label">주소</label>
								<input type="text" class="form-control" id="member_address" name="member_address">
							</div>

							<!-- 상세주소 -->
							<div class="col-6 col-sm-4 mb-3">
								<label for="member_address_detail" class="form-label">상세주소</label>
								<input type="text" class="form-control" id="member_address_detail" name="member_address_detail">
							</div>
						</div>

						<!-- 상세필드 -->
						<?php if (!empty($detail_fields)): ?>
							<h5 class="mt-4 mb-3 pb-2 border-bottom">추가 정보</h5>
							<div class="row">
								<?php foreach ($detail_fields as $field): ?>
									<?php
									$col_class = 'col-12';
									switch($field['field_size']) {
										case '1':
											$col_class = 'col-sm-4';
											break;
										case '2':
											$col_class = 'col-sm-8';
											break;
										case '3':
											$col_class = 'col-12';
											break;
									}
									?>
									<div class="<?php echo $col_class; ?> mb-3">
										<label for="detail_<?php echo $field['field_idx']; ?>" class="form-label">
											<?php echo htmlspecialchars($field['field_name']); ?>
										</label>

										<?php if ($field['field_type'] === 'text'): ?>
											<input type="text" class="form-control"
												   id="detail_<?php echo $field['field_idx']; ?>"
												   name="detail_<?php echo $field['field_idx']; ?>">

										<?php elseif ($field['field_type'] === 'textarea'): ?>
											<textarea class="form-control"
													  id="detail_<?php echo $field['field_idx']; ?>"
													  name="detail_<?php echo $field['field_idx']; ?>"
													  rows="3"></textarea>

										<?php elseif ($field['field_type'] === 'select'): ?>
											<?php
											$settings = json_decode($field['field_settings'], true);
											$options = isset($settings['options']) ? $settings['options'] : array();
											?>
											<select class="form-select"
													id="detail_<?php echo $field['field_idx']; ?>"
													name="detail_<?php echo $field['field_idx']; ?>">
												<option value="">선택하세요</option>
												<?php foreach ($options as $option): ?>
													<option value="<?php echo htmlspecialchars($option); ?>">
														<?php echo htmlspecialchars($option); ?>
													</option>
												<?php endforeach; ?>
											</select>

										<?php elseif ($field['field_type'] === 'date'): ?>
											<input type="date" class="form-control"
												   id="detail_<?php echo $field['field_idx']; ?>"
												   name="detail_<?php echo $field['field_idx']; ?>">

										<?php elseif ($field['field_type'] === 'checkbox'): ?>
											<div class="form-check">
												<input class="form-check-input" type="checkbox"
													   id="detail_<?php echo $field['field_idx']; ?>"
													   name="detail_<?php echo $field['field_idx']; ?>"
													   value="Y">
												<label class="form-check-label" for="detail_<?php echo $field['field_idx']; ?>">
													동의
												</label>
											</div>
										<?php endif; ?>
									</div>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>

						<!-- 제출 버튼 -->
						<div class="d-grid gap-2 mt-4">
							<button type="submit" class="btn btn-primary btn-lg" id="submitBtn">
								<i class="bi bi-check-circle"></i> 등록하기
							</button>
						</div>
					</form>
				</div>
			</div>

			<!-- 안내 문구 -->
			<div class="text-center mt-3 text-muted small">
				<p class="mb-0">
					<i class="bi bi-info-circle"></i>
					등록하신 정보는 조직 관리 목적으로만 사용됩니다.
				</p>
			</div>
		</div>
	</div>
</div>

<!-- Toast 컨테이너 -->
<div class="toast-container position-fixed top-0 end-0 p-3">
	<div id="successToast" class="toast align-items-center text-white bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
		<div class="d-flex">
			<div class="toast-body" id="toastMessage"></div>
			<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
		</div>
	</div>
</div>

<!-- Scripts -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/js/bootstrap.min.js" integrity="sha512-ykZ1QQr0Jy/4ZkvKuqWn4iF3lqPZyij9iRv6sGqLRdTPkY69YX6+7wvVGmsdBbiIfN/8OdsI7HABjvEok6ZopQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js" integrity="sha512-v2CJ7UaYy4JwqLDIrZUI/4hqeoQieOmAZNXBeQyjo21dadnwR+8ZaIJVT8EE2iyI61OV8e6M8PP2/4hpQINQ/g==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>

<script>

		$(document).ready(function() {
		// 폼 제출 처리
		$('#memberCardForm').on('submit', function(e) {
			e.preventDefault();

			// 개인정보 동의 확인
			if (!$('#agreePrivacy').is(':checked')) {
				alert('개인정보 수집 및 이용에 동의해주세요.');
				$('#agreePrivacy').focus();
				return false;
			}

			const submitBtn = $('#submitBtn');
			const originalBtnText = submitBtn.html();

			submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>처리 중...');

			$.ajax({
				url: '<?php echo base_url('member_card/save_member'); ?>',
				method: 'POST',
				data: $(this).serialize(),
				dataType: 'json',
				success: function(response) {
					if (response.success) {
						showSuccessMessage(response.message || '회원 등록이 완료되었습니다.');

						// 3초 후 폼 초기화
						setTimeout(function() {
							$('#memberCardForm')[0].reset();
							$('#agreePrivacy').prop('checked', false);
							submitBtn.prop('disabled', false).html(originalBtnText);
						}, 3000);
					} else {
						alert(response.message || '회원 등록에 실패했습니다.');
						submitBtn.prop('disabled', false).html(originalBtnText);
					}
				},
				error: function() {
					alert('회원 등록 중 오류가 발생했습니다.');
					submitBtn.prop('disabled', false).html(originalBtnText);
				}
			});
		});

		// 성공 메시지 표시
		function showSuccessMessage(message) {
		$('#toastMessage').text(message);
		const toast = new bootstrap.Toast(document.getElementById('successToast'));
		toast.show();
	}
	});
</script>

</body>
</html>
