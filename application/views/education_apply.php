

<!DOCTYPE html>
<html lang="ko">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?php echo htmlspecialchars($edu['edu_name']); ?> - 양육 신청</title>
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css" integrity="sha512-jnSuA4Ss2PkkikSOLtYs8BlYIeeIK1h99ty4YfvRPAlzr377vr3CXDb7sb7eEEBYjDtcYj+AjBH3FLv5uSJuXg==" crossorigin="anonymous" referrerpolicy="no-referrer"/>
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css" integrity="sha512-dPXYcDub/aeb08c63jRq/k6GaKccl256JQy/AnOq7CAnEZ9FzSL9wSbcZkMp4R26vBsMLFYH4kQ67/bbV8XaCQ==" crossorigin="anonymous" referrerpolicy="no-referrer"/>
	<link rel="stylesheet" as="style" crossorigin href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard@v1.3.9/dist/web/static/pretendard.min.css" />
	<style>
		body {
			background-color: #f8f9fa;
			min-height: 100vh;
			display: flex;
			align-items: center;
			padding: 20px 0;
			font-family: "Pretendard", Pretendard, -apple-system, BlinkMacSystemFont, system-ui, Roboto, "Helvetica Neue", "Segoe UI", "Apple SD Gothic Neo", "Noto Sans KR", "Malgun Gothic", sans-serif;
		}
		.card {
			box-shadow: 0 0 20px rgba(0,0,0,0.1);
		}
		.org-header {
			background: linear-gradient(135deg, #ffc107 0%, #912901 100%);
			color: white;
			padding: 30px;
			border-radius: 0.375rem 0.375rem 0 0;
		}
		.org-logo {
			width: 80px;
			height: 80px;
			border-radius: 50%;
			background: white;
			display: flex;
			align-items: center;
			justify-content: center;
			margin: 0 auto 15px;
			overflow: hidden;
		}
		.org-logo img {
			width: 100%;
			height: 100%;
			object-fit: cover;
		}
		.org-logo-text {
			font-size: 32px;
			font-weight: bold;
			color: #667eea;
		}
		.poster-img {
			width: 100%;
			max-height: 400px;
			object-fit: cover;
			border-radius: 0.375rem;
			margin-bottom: 20px;
		}
		.badge-info {
			font-size: 0.9rem;
			padding: 0.5rem 1rem;
		}
		.info-row {
			padding: 0.75rem 0;
			border-bottom: 1px solid #e9ecef;
		}
		.info-row:last-child {
			border-bottom: none;
		}
		.info-label {
			font-weight: 500;
			color: #999;
			margin-bottom: 0.25rem;
			font-size: 14px;
		}
		.info-value {
			color: #212529;
		}
		.youtube-container {
			position: relative;
			padding-bottom: 56.25%;
			height: 0;
			overflow: hidden;
			margin: 20px 0;
		}
		.youtube-container iframe {
			position: absolute;
			top: 0;
			left: 0;
			width: 100%;
			height: 100%;
		}
		.zoom-link {
			background: #f8f9fa;
			padding: 1rem;
			border-radius: 0.375rem;
			border: 1px solid #dee2e6;
		}

		/* Toast 스타일 */
		.toast-container {
			position: fixed;
			top: 20px;
			right: 20px;
			z-index: 9999;
		}
		.toast {
			min-width: 300px;
		}

		/* YouTube Player Custom Controls (진행 바 숨김) */
		.youtube-player-wrapper {
			position: relative;
			width: 100%;
			padding-bottom: 56.25%;
			height: 0;
		}

		.youtube-player-wrapper iframe {
			position: absolute;
			top: 0;
			left: 0;
			width: 100%;
			height: 100%;
		}

		/* 진행 바 오버레이 (클릭 방지) */
		.progress-overlay {
			position: absolute;
			bottom: 0;
			left: 0;
			width: 100%;
			height: 40px;
			background: transparent;
			z-index: 10;
			cursor: not-allowed;
		}
	</style>
</head>
<body>

<!-- Toast Container -->
<div class="toast-container"></div>

<div class="container">
	<div class="row justify-content-center">
		<div class="col-12 col-md-10 col-lg-8">
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
					<!-- 포스터 이미지 -->
					<?php if (!empty($edu['poster_img'])): ?>
						<img src="<?php echo base_url($edu['poster_img']); ?>"
							 alt="<?php echo htmlspecialchars($edu['edu_name']); ?>"
							 class="poster-img">
					<?php endif; ?>

					<!-- 양육 카테고리 -->
					<?php if (!empty($edu['category_name'])): ?>
						<div class="mb-2">
							<span class="badge bg-secondary badge-info"><?php echo htmlspecialchars($edu['category_name']); ?></span>
						</div>
					<?php endif; ?>

					<!-- 양육명 -->
					<h2 class="mb-4"><?php echo htmlspecialchars($edu['edu_name']); ?></h2>

					<!-- 양육 정보 -->
					<div class="row mb-4">
						<!-- 양육 기간 -->
						<?php if (!empty($edu['edu_start_date']) || !empty($edu['edu_end_date'])): ?>
							<div class="col-12 col-lg-6">
								<div class="info-row">
									<div class="info-label"><i class="bi bi-calendar-range"></i> 양육 기간</div>
									<div class="info-value">
										<?php
										if ($edu['edu_start_date'] && $edu['edu_end_date']) {
											echo date('Y-m-d', strtotime($edu['edu_start_date'])) . ' ~ ' . date('Y-m-d', strtotime($edu['edu_end_date']));
										} else if ($edu['edu_start_date']) {
											echo date('Y-m-d', strtotime($edu['edu_start_date'])) . ' ~';
										} else if ($edu['edu_end_date']) {
											echo '~ ' . date('Y-m-d', strtotime($edu['edu_end_date']));
										}
										?>
									</div>
								</div>
							</div>
						<?php endif; ?>

						<!-- 요일 및 시간대 -->
						<?php if (!empty($edu['edu_days_display']) || !empty($edu['edu_times_display'])): ?>
							<div class="col-12 col-lg-6">
								<div class="info-row">
									<div class="info-label"><i class="bi bi-clock"></i> 요일 / 시간대</div>
									<div class="info-value">
										<?php
										$info_parts = array();
										if (!empty($edu['edu_days_display'])) {
											$info_parts[] = $edu['edu_days_display'];
										}
										if (!empty($edu['edu_times_display'])) {
											$info_parts[] = $edu['edu_times_display'];
										}
										echo implode(' / ', $info_parts);
										?>
									</div>
								</div>
							</div>
						<?php endif; ?>

						<!-- 양육 지역 -->
						<?php if (!empty($edu['edu_location'])): ?>
							<div class="col-12 col-lg-6">
								<div class="info-row">
									<div class="info-label"><i class="bi bi-geo-alt"></i> 양육 지역</div>
									<div class="info-value"><?php echo htmlspecialchars($edu['edu_location']); ?></div>
								</div>
							</div>
						<?php endif; ?>

						<!-- 신청자 / 정원 -->
						<div class="col-12 col-lg-6">
							<div class="info-row">
								<div class="info-label"><i class="bi bi-people"></i> 신청 현황</div>
								<div class="info-value">
									현재 <?php echo $applicant_count; ?>명
									<?php if ($edu['edu_capacity'] > 0): ?>
										/ 정원 <?php echo number_format($edu['edu_capacity']); ?>명
									<?php endif; ?>
								</div>
							</div>
						</div>

						<!-- 수강료 및 계좌정보 -->
						<?php if ($edu['edu_fee'] > 0): ?>
							<div class="col-12 col-lg-6">
								<div class="info-row">
									<div class="info-label"><i class="bi bi-credit-card"></i> 수강료</div>
									<div class="info-value"><?php echo number_format($edu['edu_fee']); ?>원</div>
								</div>
							</div>
							<?php if (!empty($edu['bank_account'])): ?>
								<div class="col-12 col-lg-6">
									<div class="info-row">
										<div class="info-label"><i class="bi bi-bank"></i> 계좌정보</div>
										<div class="info-value"><?php echo htmlspecialchars($edu['bank_account']); ?></div>
									</div>
								</div>
							<?php endif; ?>

						<?php endif; ?>

						<!-- 인도자 정보 -->
						<?php if (!empty($edu['edu_leader'])): ?>
							<div class="col-12 col-lg-6">
								<div class="info-row">
									<div class="info-label"><i class="bi bi-person-badge"></i> 인도자</div>
									<div class="info-value">
										<?php echo htmlspecialchars($edu['edu_leader']); ?>
										<?php if (!empty($edu['edu_leader_phone'])): ?>
											/ <?php echo htmlspecialchars($edu['edu_leader_phone']); ?>
										<?php endif; ?>
									</div>
								</div>
							</div>
						<?php endif; ?>

						<!-- ZOOM 주소 (양육 중이고 해당 요일/시간대인 경우만 표시) -->
						<?php
						$now = time();
						$current_date = date('Y-m-d');
						$current_day = date('w'); // 0(일) ~ 6(토)
						$current_time = date('H:i');

						// 요일 매핑
						$day_map = array(
							0 => '일요일',
							1 => '월요일',
							2 => '화요일',
							3 => '수요일',
							4 => '목요일',
							5 => '금요일',
							6 => '토요일'
						);
						$current_day_name = $day_map[$current_day];

						// 양육 기간 확인
						$start_date = $edu['edu_start_date'] ? $edu['edu_start_date'] : null;
						$end_date = $edu['edu_end_date'] ? $edu['edu_end_date'] : null;

						$is_in_period = true;
						if ($start_date && $current_date < $start_date) {
							$is_in_period = false;
						}
						if ($end_date && $current_date > $end_date) {
							$is_in_period = false;
						}

						// 요일 확인 (원본 JSON 데이터 사용)
						$is_correct_day = false;
						if (!empty($edu['edu_days'])) {
							$edu_days_raw = $edu['edu_days'];

							// JSON 문자열인 경우 파싱
							if (is_string($edu_days_raw) && strpos($edu_days_raw, '[') === 0) {
								$days_array = json_decode($edu_days_raw, true);
							} else if (is_string($edu_days_raw)) {
								// 이미 파싱된 문자열 (예: "월요일, 화요일")
								$days_array = array_map('trim', explode(',', $edu_days_raw));
							} else if (is_array($edu_days_raw)) {
								$days_array = $edu_days_raw;
							} else {
								$days_array = array();
							}

							// 현재 요일이 양육 요일에 포함되는지 확인
							if (in_array($current_day_name, $days_array)) {
								$is_correct_day = true;
							}
						} else {
							// 요일 설정이 없으면 모든 요일 허용
							$is_correct_day = true;
						}

						// 시간대 확인 (원본 JSON 데이터 사용)
						$is_correct_time = false;
						if (!empty($edu['edu_times'])) {
							$edu_times_raw = $edu['edu_times'];

							// JSON 문자열인 경우 파싱
							if (is_string($edu_times_raw) && strpos($edu_times_raw, '[') === 0) {
								$times_array = json_decode($edu_times_raw, true);
							} else if (is_string($edu_times_raw)) {
								// 이미 파싱된 문자열 (예: "오전, 오후")
								$times_array = array_map('trim', explode(',', $edu_times_raw));
							} else if (is_array($edu_times_raw)) {
								$times_array = $edu_times_raw;
							} else {
								$times_array = array();
							}

							// 현재 시간이 양육 시간대에 포함되는지 확인
							foreach ($times_array as $time_slot) {
								if ($time_slot === '오전' && $current_time >= '06:00' && $current_time < '12:00') {
									$is_correct_time = true;
									break;
								} else if ($time_slot === '오후' && $current_time >= '12:00' && $current_time < '18:00') {
									$is_correct_time = true;
									break;
								} else if ($time_slot === '저녁' && $current_time >= '18:00' && $current_time < '22:00') {
									$is_correct_time = true;
									break;
								} else if ($time_slot === '새벽' && ($current_time >= '00:00' && $current_time < '06:00')) {
									$is_correct_time = true;
									break;
								}
							}
						} else {
							// 시간대 설정이 없으면 모든 시간 허용
							$is_correct_time = true;
						}

						// ZOOM URL이 있고, 양육 기간이고, 해당 요일이고, 해당 시간대인 경우에만 표시
						$show_zoom = !empty($edu['zoom_url']) && $is_in_period && $is_correct_day && $is_correct_time;
						?>
						<?php if ($show_zoom): ?>
							<div class="col-12">
								<div class="info-row">
									<div class="info-label"><i class="bi bi-camera-video"></i> ZOOM 주소</div>
									<div class="info-value">
										<div class="zoom-link">
											<a href="<?php echo htmlspecialchars($edu['zoom_url']); ?>" target="_blank" class="text-primary">
												<i class="bi bi-box-arrow-up-right"></i> ZOOM 참여하기
											</a>
										</div>
									</div>
								</div>
							</div>
						<?php endif; ?>

					</div>

					<!-- 양육 설명 -->
					<?php if (!empty($edu['edu_desc'])): ?>
						<div class="mb-4">
							<h5 class="mb-3">양육 안내</h5>
							<div class="border rounded p-3 bg-light">
								<?php echo nl2br(htmlspecialchars($edu['edu_desc'])); ?>
							</div>
						</div>
					<?php endif; ?>

					<!-- 개인정보 제3자 제공 동의 -->
					<div class="mb-4">
						<h5 class="mb-3">개인정보 제3자 제공 동의</h5>
						<div class="border rounded p-3 bg-light" style="max-height: 200px; overflow-y: auto;">
							<div class="small">
								<p class="mb-1"><strong>1. 개인정보를 제공받는 자</strong></p>
								<p class="mb-2 ps-3"><?php echo htmlspecialchars($org_info['org_name']); ?></p>

								<p class="mb-1"><strong>2. 제공하는 개인정보 항목</strong></p>
								<p class="mb-2 ps-3">이름, 연락처</p>

								<p class="mb-1"><strong>3. 개인정보 제공 목적</strong></p>
								<p class="mb-2 ps-3">양육 신청 관리 및 연락을 위한 목적</p>

								<p class="mb-1"><strong>4. 개인정보 보유 및 이용 기간</strong></p>
								<p class="mb-0 ps-3">목적 달성 후 즉시 파기 (최대 1년)</p>
							</div>
							<hr>
							<p class="mb-0 text-muted small">
								<i class="bi bi-info-circle"></i>
								귀하는 위의 개인정보 제공에 대한 동의를 거부할 권리가 있으며,
								동의를 거부할 경우 양육 신청이 제한될 수 있습니다.
							</p>
						</div>
						<div class="form-check mt-3">
							<input class="form-check-input" type="checkbox" id="agreePrivacy" required>
							<label class="form-check-label" for="agreePrivacy">
								개인정보 제3자 제공에 동의합니다. <span class="text-danger">*</span>
							</label>
						</div>
					</div>

					<!-- 신청 폼 -->
					<form id="applicationForm">
						<input type="hidden" id="eduIdx" value="<?php echo $edu['edu_idx']; ?>">
						<input type="hidden" id="accessCode" value="<?php echo $access_code; ?>">

						<div class="row">
							<div class="col-md-6 mb-3">
								<label class="form-label">이름 <span class="text-danger">*</span></label>
								<input type="text" class="form-control" id="applicantName" required placeholder="홍길동">
							</div>
							<div class="col-md-6 mb-3">
								<label class="form-label">연락처 <span class="text-danger">*</span></label>
								<input type="text" class="form-control" id="applicantPhone" required placeholder="010-1234-5678">
							</div>
						</div>

						<div class="d-grid">
							<button type="submit" class="btn btn-primary btn-lg">
								<i class="bi bi-check-circle"></i> 신청하기
							</button>
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>
</div>

<!-- YouTube 시청 모달 -->
<div class="modal fade" id="youtubeModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
	<div class="modal-dialog modal-xl">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title"><i class="bi bi-youtube"></i> 양육 영상 시청</h5>
			</div>
			<div class="modal-body">
				<div class="youtube-player-wrapper" id="youtubePlayerWrapper">
					<div id="youtubePlayer"></div>
					<div class="progress-overlay" title="빨리감기가 제한되어 있습니다."></div>
				</div>
				<div class="alert alert-info mt-3">
					<i class="bi bi-info-circle"></i>
					영상을 끝까지 시청하시면 자동으로 수료 처리됩니다. (빨리감기 불가)
				</div>
			</div>
		</div>
	</div>
</div>

<!-- 기존 신청자 확인 모달 -->
<div class="modal fade" id="existingApplicantModal" tabindex="-1" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title"><i class="bi bi-person-check"></i> 이미 신청되었습니다</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<p>입력하신 정보로 이미 신청되어 있습니다.</p>
				<div id="existingApplicantInfo" class="border rounded p-3 bg-light mb-3">
					<!-- 신청 정보가 여기에 표시됩니다 -->
				</div>
				<p class="text-muted small mb-0">아래 버튼을 통해 양육에 참여하실 수 있습니다.</p>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">닫기</button>
				<button type="button" class="btn btn-success" id="btnWatchYoutube" style="display:none;">
					<i class="bi bi-youtube"></i> 동영상 시청
				</button>
				<button type="button" class="btn btn-primary" id="btnJoinZoom" style="display:none;">
					<i class="bi bi-camera-video"></i> ZOOM 참여
				</button>
			</div>
		</div>
	</div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/js/bootstrap.bundle.min.js"></script>
<script>
	// 전역 변수
	var youtubePlayer = null;
	var applicantIdx = null;
	var videoWatched = false;
	var maxWatchedTime = 0;
	var videoDuration = 0;
	var existingApplicantData = null;

	// PHP 변수를 JavaScript로 전달
	var eduData = {
		youtubeUrl: '<?php echo !empty($edu['youtube_url']) ? $edu['youtube_url'] : ''; ?>',
		zoomUrl: '<?php echo !empty($edu['zoom_url']) ? $edu['zoom_url'] : ''; ?>',
		isInPeriod: <?php echo $is_in_period ? 'true' : 'false'; ?>,
		isCorrectDay: <?php echo $is_correct_day ? 'true' : 'false'; ?>,
		isCorrectTime: <?php echo $is_correct_time ? 'true' : 'false'; ?>
	};

	// YouTube IFrame API 로드
	var tag = document.createElement('script');
	tag.src = "https://www.youtube.com/iframe_api";
	var firstScriptTag = document.getElementsByTagName('script')[0];
	firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);

	$(document).ready(function() {
		// 신청 폼 제출
		$('#applicationForm').on('submit', function(e) {
			e.preventDefault();

			// 동의 확인
			if (!$('#agreePrivacy').is(':checked')) {
				showToast('개인정보 제3자 제공에 동의해주세요.', 'warning');
				return;
			}

			var name = $('#applicantName').val().trim();
			var phone = $('#applicantPhone').val().trim();

			if (!name || !phone) {
				showToast('이름과 연락처를 입력해주세요.', 'warning');
				return;
			}

			// 신청 처리
			$.ajax({
				url: '<?php echo base_url('education/submit_external_application'); ?>',
				method: 'POST',
				data: {
					edu_idx: $('#eduIdx').val(),
					access_code: $('#accessCode').val(),
					applicant_name: name,
					applicant_phone: phone,
					agree_privacy: 'Y'
				},
				dataType: 'json',
				success: function(response) {
					if (response.success) {
						applicantIdx = response.applicant_idx;

						if (eduData.youtubeUrl) {
							// 양육 시간 확인
							var isValidTime = checkEducationTime();

							if (isValidTime) {
								showToast('신청이 완료되었습니다. 양육 영상을 시청해주세요.', 'success');
								// YouTube 모달 열기
								openYoutubeModal(eduData.youtubeUrl);
							} else {
								showToast('신청이 완료되었습니다. 현재 시청 시간이 아닙니다.', 'info');
								$('#applicationForm')[0].reset();
								$('#agreePrivacy').prop('checked', false);
							}
						} else {
							showToast('신청이 완료되었습니다.', 'success');
							$('#applicationForm')[0].reset();
							$('#agreePrivacy').prop('checked', false);
						}
					} else if (response.already_exists) {
						// 이미 신청된 경우
						existingApplicantData = response.applicant_data;
						showExistingApplicantModal(response.applicant_data);
					} else {
						showToast(response.message || '신청 처리 중 오류가 발생했습니다.', 'error');
					}
				},
				error: function() {
					showToast('신청 처리 중 오류가 발생했습니다.', 'error');
				}
			});
		});

		// 기존 신청자 - YouTube 시청 버튼
		$('#btnWatchYoutube').on('click', function() {
			$('#existingApplicantModal').modal('hide');

			if (existingApplicantData && existingApplicantData.applicant_idx) {
				applicantIdx = existingApplicantData.applicant_idx;

				var isValidTime = checkEducationTime();

				if (isValidTime) {
					openYoutubeModal(eduData.youtubeUrl);
				} else {
					showToast('현재 시청 시간이 아닙니다.', 'warning');
				}
			}
		});

		// 기존 신청자 - ZOOM 참여 버튼
		$('#btnJoinZoom').on('click', function() {
			if (eduData.zoomUrl) {
				window.open(eduData.zoomUrl, '_blank');
			}
		});
	});

	/**
	 * 기존 신청자 모달 표시
	 */
	function showExistingApplicantModal(applicantData) {
		var statusBadge = getStatusBadgeHtml(applicantData.status);

		var infoHtml = `
			<div class="row">
				<div class="col-6 mb-2">
					<small class="text-muted">신청자</small>
					<div><strong>${applicantData.applicant_name}</strong></div>
				</div>
				<div class="col-6 mb-2">
					<small class="text-muted">연락처</small>
					<div>${applicantData.applicant_phone}</div>
				</div>
				<div class="col-6">
					<small class="text-muted">신청일</small>
					<div>${applicantData.regi_date}</div>
				</div>
				<div class="col-6">
					<small class="text-muted">상태</small>
					<div>${statusBadge}</div>
				</div>
			</div>
		`;

		$('#existingApplicantInfo').html(infoHtml);

		// 버튼 표시/숨김
		var isValidTime = checkEducationTime();

		if (eduData.youtubeUrl && isValidTime && applicantData.status !== '수료') {
			$('#btnWatchYoutube').show();
		} else {
			$('#btnWatchYoutube').hide();
		}

		if (eduData.zoomUrl && isValidTime) {
			$('#btnJoinZoom').show();
		} else {
			$('#btnJoinZoom').hide();
		}

		$('#existingApplicantModal').modal('show');
	}

	/**
	 * 상태 뱃지 HTML 생성
	 */
	function getStatusBadgeHtml(status) {
		var badgeClass = 'bg-secondary';

		switch(status) {
			case '신청':
			case '신청(외부)':
				badgeClass = 'bg-primary';
				break;
			case '양육중':
				badgeClass = 'bg-info';
				break;
			case '수료':
				badgeClass = 'bg-success';
				break;
		}

		return '<span class="badge ' + badgeClass + '">' + status + '</span>';
	}

	/**
	 * 양육 시간 확인
	 */
	function checkEducationTime() {
		return eduData.isInPeriod && eduData.isCorrectDay;
	}

	/**
	 * YouTube 모달 열기
	 */
	function openYoutubeModal(youtubeUrl) {
		// Video ID 추출
		var videoId = extractVideoId(youtubeUrl);

		if (!videoId) {
			showToast('YouTube 영상 정보를 찾을 수 없습니다.', 'error');
			return;
		}

		// 초기화
		maxWatchedTime = 0;
		videoDuration = 0;
		videoWatched = false;

		// 모달 열기
		$('#youtubeModal').modal('show');

		// YouTube Player 초기화 (API가 로드되면 실행됨)
		if (typeof YT !== 'undefined' && YT.Player) {
			initYoutubePlayer(videoId);
		} else {
			// API 로드 대기
			window.onYouTubeIframeAPIReady = function() {
				initYoutubePlayer(videoId);
			};
		}
	}

	/**
	 * YouTube Video ID 추출
	 */
	function extractVideoId(url) {
		var videoId = '';

		// https://www.youtube.com/watch?v=VIDEO_ID
		var match = url.match(/[?&]v=([^&]+)/);
		if (match) {
			return match[1];
		}

		// https://youtu.be/VIDEO_ID
		match = url.match(/youtu\.be\/([^?]+)/);
		if (match) {
			return match[1];
		}

		return null;
	}

	/**
	 * YouTube Player 초기화
	 */
	function initYoutubePlayer(videoId) {
		youtubePlayer = new YT.Player('youtubePlayer', {
			videoId: videoId,
			width: '100%',
			height: '500',
			playerVars: {
				'autoplay': 1,
				'controls': 0,  // 모든 컨트롤 제거
				'rel': 0,
				'modestbranding': 1,
				'fs': 0,  // 전체화면 버튼 제거
				'disablekb': 1,  // 키보드 컨트롤 비활성화
				'iv_load_policy': 3  // 어노테이션 숨김
			},
			events: {
				'onReady': onPlayerReady,
				'onStateChange': onPlayerStateChange
			}
		});
	}

	/**
	 * YouTube Player 준비 완료
	 */
	function onPlayerReady(event) {
		videoDuration = event.target.getDuration();

		// 재생 시간 추적 (1초마다)
		setInterval(function() {
			if (youtubePlayer && youtubePlayer.getPlayerState() === YT.PlayerState.PLAYING) {
				var currentTime = youtubePlayer.getCurrentTime();

				// 최대 시청 시간 업데이트
				if (currentTime > maxWatchedTime) {
					maxWatchedTime = currentTime;
				}

				// 빨리감기 방지: 현재 시간이 최대 시청 시간보다 5초 이상 앞서가면 되돌림
				if (currentTime > maxWatchedTime + 5) {
					youtubePlayer.seekTo(maxWatchedTime, true);
					showToast('빨리감기는 허용되지 않습니다.', 'warning');
				}
			}
		}, 1000);
	}

	/**
	 * YouTube Player 상태 변경 이벤트
	 */
	function onPlayerStateChange(event) {
		// 영상 종료 (YT.PlayerState.ENDED = 0)
		if (event.data === YT.PlayerState.ENDED && !videoWatched) {
			videoWatched = true;

			// 수료 처리
			completeEducation();
		}
	}

	/**
	 * 양육 수료 처리
	 */
	function completeEducation() {
		if (!applicantIdx) {
			showToast('신청자 정보를 찾을 수 없습니다.', 'error');
			return;
		}

		$.ajax({
			url: '<?php echo base_url('education/complete_external_education'); ?>',
			method: 'POST',
			data: {
				applicant_idx: applicantIdx,
				access_code: $('#accessCode').val()
			},
			dataType: 'json',
			success: function(response) {
				if (response.success) {
					showToast('양육이 수료되었습니다. 수고하셨습니다!', 'success');

					// 3초 후 모달 닫기 및 폼 초기화
					setTimeout(function() {
						$('#youtubeModal').modal('hide');
						$('#applicationForm')[0].reset();
						$('#agreePrivacy').prop('checked', false);

						// YouTube Player 정리
						if (youtubePlayer) {
							youtubePlayer.destroy();
							youtubePlayer = null;
						}

						applicantIdx = null;
						videoWatched = false;
						maxWatchedTime = 0;
						videoDuration = 0;
					}, 3000);
				} else {
					showToast(response.message || '수료 처리 중 오류가 발생했습니다.', 'error');
				}
			},
			error: function() {
				showToast('수료 처리 중 오류가 발생했습니다.', 'error');
			}
		});
	}

	/**
	 * Toast 메시지 표시
	 */
	function showToast(message, type) {
		type = type || 'info';

		var bgColor = '';
		var icon = '';

		switch(type) {
			case 'success':
				bgColor = 'bg-success';
				icon = 'bi-check-circle-fill';
				break;
			case 'error':
				bgColor = 'bg-danger';
				icon = 'bi-x-circle-fill';
				break;
			case 'warning':
				bgColor = 'bg-warning';
				icon = 'bi-exclamation-triangle-fill';
				break;
			default:
				bgColor = 'bg-info';
				icon = 'bi-info-circle-fill';
		}

		var toastHtml = `
			<div class="toast align-items-center text-white ${bgColor} border-0" role="alert" aria-live="assertive" aria-atomic="true">
				<div class="d-flex">
					<div class="toast-body">
						<i class="bi ${icon} me-2"></i>${message}
					</div>
					<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
				</div>
			</div>
		`;

		var $toast = $(toastHtml);
		$('.toast-container').append($toast);

		var toast = new bootstrap.Toast($toast[0], {
			autohide: true,
			delay: 3000
		});

		toast.show();

		// Toast가 숨겨진 후 DOM에서 제거
		$toast.on('hidden.bs.toast', function() {
			$(this).remove();
		});
	}
</script>

</body>
</html>
