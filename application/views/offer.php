<!DOCTYPE html>
<html lang="ko">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>결연교회 추천</title>
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css" integrity="sha512-jnSuA4Ss2PkkikSOLtYs8BlYIeeIK1h99ty4YfvRPAlzr377vr3CXDb7sb7eEEBYjDtcYj+AjBH3FLv5uSJuXg==" crossorigin="anonymous" referrerpolicy="no-referrer"/>
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css" integrity="sha512-dPXYcDub/aeb08c63jRq/k6GaKccl256JQy/AnOq7CAnEZ9FzSL9wSbcZkMp4R26vBsMLFYH4kQ67/bbV8XaCQ==" crossorigin="anonymous" referrerpolicy="no-referrer"/>
	<link rel="stylesheet" as="style" crossorigin href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard@v1.3.9/dist/web/static/pretendard.min.css" />
	<style>
		.church-card {transition: all 0.3s ease;border: 2px solid transparent;}
		.church-card:hover {transform: translateY(-5px);box-shadow: 0 8px 20px rgba(0,0,0,0.15);border-color: #667eea;}
		.timer-badge {font-size: 0.9rem;background: rgba(255,255,255,0.2);backdrop-filter: blur(10px);}
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
						<span><i class="bi bi-clock"></i> 유효시간 약 <?php echo $remaining_hours; ?>시간 남음</span>
					</p>
				</div>
				<div class="card-body p-4">
					<!-- 개인정보 제3자 제공 동의 -->
					<h4 class="mt-2 mb-4 lh-sm">
						<strong><?php echo htmlspecialchars($member_info['member_name']); ?></strong>님의 신앙 여정에 힘이 되고자,<br/>
						저희 군선교연합회와 간절한 마음으로 결연을 맺은<br/>
						귀한 교회들을 소개합니다.
					</h4>
					<p class="mb-3">
						신앙생활의 첫걸음을 어디서부터 시작해야 할지, 혹은 지금의 자리에서 더 깊은 영적 성장을 어떻게 이룰 수 있을지 절실히 고민하고 계실 줄 압니다.<br/>
						저희가 추천하는 하단의 교회 목록을 살펴보신 후, <strong><?php echo htmlspecialchars($member_info['member_name']); ?></strong>님의 마음이 이끄는 한 곳을 간절히 택하여 주십시오.<br/>
						동의를 완료하고 교회를 선택하시면, 해당 교회의 담당자분께서 <strong><?php echo htmlspecialchars($member_info['member_name']); ?></strong>님께 따뜻한 연락을 드려 신앙생활에 절실히 필요한 도움과 든든한 공동체를 제공해 드릴 것입니다.<br/><br/>
						<strong><?php echo htmlspecialchars($member_info['member_name']); ?></strong>님의 이 간절한 결정이 귀한 신앙의 열매로 이어지기를 기도하며, 기다리겠습니다.
					</p>

					<!-- 개인정보 제3자 제공 동의 -->
					<div class="mb-4">
						<h5 class="mb-3">개인정보 제3자 제공 동의</h5>
						<div class="border rounded p-3 bg-light" style="max-height: 200px; overflow-y: auto;">
							<div class="small">
								<p class="mb-1"><strong>1. 개인정보를 제공받는 자</strong></p>
								<p class="mb-2 ps-3">귀하가 선택하신 결연교회</p>

								<p class="mb-1"><strong>2. 제공하는 개인정보 항목</strong></p>
								<p class="mb-2 ps-3">이름, 연락처, 주소 등 기본 연락 정보</p>

								<p class="mb-1"><strong>3. 개인정보 제공 목적</strong></p>
								<p class="mb-2 ps-3">선택하신 결연교회의 담당자가 귀하에게 연락하기 위한 목적</p>

								<p class="mb-1"><strong>4. 개인정보 보유 및 이용 기간</strong></p>
								<p class="mb-0 ps-3">목적 달성 후 즉시 파기 (최대 1년)</p>
							</div>
							<hr>
							<p class="mb-0 text-muted small">
								<i class="bi bi-info-circle"></i>
								귀하는 위의 개인정보 제공에 대한 동의를 거부할 권리가 있으며,
								동의를 거부할 경우 결연교회 선택 서비스 이용이 제한될 수 있습니다.
							</p>
						</div>
						<div class="form-check mt-3">
							<input class="form-check-input" type="checkbox" id="agreePrivacy" required>
							<label class="form-check-label" for="agreePrivacy">
								개인정보 제3자 제공에 동의합니다. <span class="text-danger">*</span>
							</label>
						</div>
					</div>

					<!-- 안내 메시지 -->
					<h5 class="mb-3">군선교연합회에서 추천하는 교회</h5>

					<!-- 추천 교회 목록 -->
					<?php if (!empty($recommended_churches)): ?>
						<div class="row g-3">
							<?php foreach ($recommended_churches as $church): ?>
								<div class="col-12">
									<div class="card church-card h-100">
										<div class="card-body">
											<div class="row align-items-center">
												<div class="col-md-8">
													<h5 class="card-title mb-2">
														<i class="bi bi-building text-primary"></i>
														<?php echo htmlspecialchars($church['transfer_org_name']); ?>
													</h5>
													<p class="card-text mb-1">
														<i class="bi bi-person text-secondary"></i>
														<strong>담임목사:</strong> <?php echo htmlspecialchars($church['transfer_org_rep'] ?: '-'); ?>
													</p>
													<p class="card-text mb-1">
														<i class="bi bi-geo-alt text-danger"></i>
														<strong>지역:</strong> <?php echo htmlspecialchars($church['transfer_org_address']); ?>
													</p>
													<?php if (!empty($church['transfer_org_phone'])): ?>
														<p class="card-text mb-1">
															<i class="bi bi-telephone text-success"></i>
															<strong>연락처:</strong> <?php echo htmlspecialchars($church['transfer_org_phone']); ?>
														</p>
													<?php endif; ?>
													<?php if (!empty($church['transfer_org_tag'])): ?>
														<p class="card-text mb-0">
															<i class="bi bi-tags text-info"></i>
															<strong>태그:</strong>
															<?php
															$tags = [];
															$tagData = $church['transfer_org_tag'];

															// JSON 문자열인지 확인 (예: "[\"기성\",\"청년부활성화\"]")
															if (is_string($tagData) && strpos(trim($tagData), '[') === 0) {
																$decoded = json_decode($tagData, true);
																if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
																	$tags = $decoded;
																} else {
																	// JSON 파싱 실패 시 일반 문자열로 처리
																	$tags = array_map('trim', explode(',', $tagData));
																}
															}
															// 이미 배열인 경우
															else if (is_array($tagData)) {
																$tags = $tagData;
															}
															// 일반 쉼표 구분 문자열인 경우
															else if (is_string($tagData)) {
																$tags = array_map('trim', explode(',', $tagData));
															}

															// 태그 출력
															foreach ($tags as $tag) {
																$tag = trim($tag);
																if (!empty($tag)) {
																	echo '<span class="badge bg-secondary me-1">' . htmlspecialchars($tag) . '</span>';
																}
															}
															?>
														</p>
													<?php endif; ?>
												</div>
												<div class="col-md-4 text-md-end mt-3 mt-md-0">
													<button type="button"
															class="btn btn-primary select-church-btn"
															data-church-id="<?php echo $church['transfer_org_id']; ?>"
															data-church-name="<?php echo htmlspecialchars($church['transfer_org_name']); ?>">
														<i class="bi bi-check-circle"></i> 선택
													</button>
												</div>
											</div>
										</div>
									</div>
								</div>
							<?php endforeach; ?>
						</div>
					<?php else: ?>
						<div class="alert alert-warning">
							<i class="bi bi-exclamation-triangle"></i>
							현재 추천 가능한 교회가 없습니다.
						</div>
					<?php endif; ?>
				</div>
				<div class="card-footer text-center text-muted">
					<small>
						<i class="bi bi-info-circle"></i>
						선택하신 정보는 안전하게 관리되며, 선택하신 교회의 담당자만 확인할 수 있습니다.
					</small>
				</div>
			</div>
		</div>
	</div>
</div>

<!-- Confirm 모달 -->
<div class="modal fade" id="confirmModal" tabindex="-1" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title">교회 선택 확인</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<p id="confirmMessage"></p>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
				<button type="button" class="btn btn-primary" id="confirmSelectBtn">선택</button>
			</div>
		</div>
	</div>
</div>

<!-- Toast 컨테이너 -->
<div class="toast-container position-fixed top-0 end-0 p-3">
	<div id="liveToast" class="toast align-items-center border-0" role="alert" aria-live="assertive"
		 aria-atomic="true">
		<div class="d-flex">
			<div class="toast-body" id="toastMessage"></div>
			<button type="button" class="btn-close btn-close-white me-2 m-auto"
					data-bs-dismiss="toast"></button>
		</div>
	</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="/assets/js/common.js?<?php echo WB_VERSION; ?>"></script>
<script>
	$(document).ready(function () {
		let selectedChurchId = null;
		let selectedChurchName = null;
		let clickedButton = null;

		// 교회 선택 버튼 클릭
		$('.select-church-btn').on('click', function () {
			const churchId = $(this).data('church-id');
			const churchName = $(this).data('church-name');
			const button = $(this);

			// 개인정보 동의 체크박스 확인
			const agreePrivacyChecked = $('#agreePrivacy').is(':checked');

			if (!agreePrivacyChecked) {
				showToast('개인정보 제3자 제공에 동의해주세요.', 'warning');
				return;
			}

			// 모달에 정보 설정
			selectedChurchId = churchId;
			selectedChurchName = churchName;
			clickedButton = button;

			$('#confirmMessage').text(churchName + '을(를) 선택하시겠습니까?');

			const confirmModal = new bootstrap.Modal(document.getElementById('confirmModal'));
			confirmModal.show();
		});

		// 모달에서 선택 확인 버튼 클릭
		$('#confirmSelectBtn').on('click', function () {
			if (!selectedChurchId || !clickedButton) return;

			// 모달 닫기
			const confirmModal = bootstrap.Modal.getInstance(document.getElementById('confirmModal'));
			confirmModal.hide();

			// 버튼 비활성화 및 로딩 표시
			clickedButton.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>처리 중...');

			$.ajax({
				url: '<?php echo base_url('offer/select_church'); ?>',
				method: 'POST',
				data: {
					org_id: '<?php echo $org_id; ?>',
					member_idx: '<?php echo $member_idx; ?>',
					member_passcode: '<?php echo $member_passcode; ?>',
					selected_church_id: selectedChurchId
				},
				dataType: 'json',
				success: function (response) {
					if (response.success) {
						showToast(response.message, 'success');

						// 2초 후 페이지 종료
						setTimeout(function () {
							window.close();
							// 창이 닫히지 않는 경우를 대비한 메시지
							$('body').html(`
								<div class="container mt-5 text-center">
									<div class="card">
										<div class="card-body">
											<i class="bi bi-check-circle text-success" style="font-size: 4rem;"></i>
											<h3 class="mt-3">선택이 완료되었습니다!</h3>
											<p class="text-muted">이 창을 닫아주세요.</p>
										</div>
									</div>
								</div>
							`);
						}, 2000);
					} else {
						showToast(response.message || '교회 선택에 실패했습니다.', 'error');
						clickedButton.prop('disabled', false).html('<i class="bi bi-check-circle"></i> 선택');
					}
				},
				error: function () {
					showToast('교회 선택 중 오류가 발생했습니다.', 'error');
					clickedButton.prop('disabled', false).html('<i class="bi bi-check-circle"></i> 선택');
				}
			});
		});


	});
</script>

</body>
</html>
