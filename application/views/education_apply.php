<!DOCTYPE html>
<html lang="ko">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?php echo htmlspecialchars($edu['edu_name']); ?> - 교육 신청</title>
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
			background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
			font-weight: 600;
			color: #495057;
			margin-bottom: 0.25rem;
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
	</style>
</head>
<body>

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

					<!-- 교육 카테고리 -->
					<?php if (!empty($edu['category_name'])): ?>
						<div class="mb-2">
							<span class="badge bg-secondary badge-info"><?php echo htmlspecialchars($edu['category_name']); ?></span>
						</div>
					<?php endif; ?>

					<!-- 교육명 -->
					<h2 class="mb-4"><?php echo htmlspecialchars($edu['edu_name']); ?></h2>

					<!-- 교육 정보 -->
					<div class="mb-4">
						<!-- 교육 기간 -->
						<?php if (!empty($edu['edu_start_date']) || !empty($edu['edu_end_date'])): ?>
							<div class="info-row">
								<div class="info-label"><i class="bi bi-calendar-range"></i> 교육 기간</div>
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
						<?php endif; ?>

						<!-- 요일 및 시간대 -->
						<?php if (!empty($edu['edu_days_display']) || !empty($edu['edu_times_display'])): ?>
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
						<?php endif; ?>

						<!-- 교육 지역 -->
						<?php if (!empty($edu['edu_location'])): ?>
							<div class="info-row">
								<div class="info-label"><i class="bi bi-geo-alt"></i> 교육 지역</div>
								<div class="info-value"><?php echo htmlspecialchars($edu['edu_location']); ?></div>
							</div>
						<?php endif; ?>

						<!-- 신청자 / 정원 -->
						<div class="info-row">
							<div class="info-label"><i class="bi bi-people"></i> 신청 현황</div>
							<div class="info-value">
								현재 <?php echo $applicant_count; ?>명
								<?php if ($edu['edu_capacity'] > 0): ?>
									/ 정원 <?php echo number_format($edu['edu_capacity']); ?>명
								<?php endif; ?>
							</div>
						</div>

						<!-- 수강료 및 계좌정보 -->
						<?php if ($edu['edu_fee'] > 0): ?>
							<div class="info-row">
								<div class="info-label"><i class="bi bi-credit-card"></i> 수강료</div>
								<div class="info-value"><?php echo number_format($edu['edu_fee']); ?>원</div>
							</div>
							<?php if (!empty($edu['bank_account'])): ?>
								<div class="info-row">
									<div class="info-label"><i class="bi bi-bank"></i> 계좌정보</div>
									<div class="info-value"><?php echo htmlspecialchars($edu['bank_account']); ?></div>
								</div>
							<?php endif; ?>
						<?php endif; ?>

						<!-- 인도자 정보 -->
						<?php if (!empty($edu['edu_leader'])): ?>
							<div class="info-row">
								<div class="info-label"><i class="bi bi-person-badge"></i> 인도자</div>
								<div class="info-value">
									<?php echo htmlspecialchars($edu['edu_leader']); ?>
									<?php if (!empty($edu['edu_leader_phone'])): ?>
										/ <?php echo htmlspecialchars($edu['edu_leader_phone']); ?>
									<?php endif; ?>
								</div>
							</div>
						<?php endif; ?>

						<!-- ZOOM 주소 (교육 중이고 해당 요일/시간대인 경우만 표시) -->
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

						// 교육 기간 확인
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

							// 현재 요일이 교육 요일에 포함되는지 확인
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

							// 현재 시간이 교육 시간대에 포함되는지 확인
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

						// ZOOM URL이 있고, 교육 기간이고, 해당 요일이고, 해당 시간대인 경우에만 표시
						$show_zoom = !empty($edu['zoom_url']) && $is_in_period && $is_correct_day && $is_correct_time;
						?>
						<?php if ($show_zoom): ?>
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
						<?php endif; ?>
					</div>

					<!-- YouTube 영상 (교육 중이고 해당 요일/시간대인 경우만 표시) -->
					<?php if ($show_zoom && !empty($edu['youtube_url'])): ?>
						<div class="mb-4">
							<h5 class="mb-3"><i class="bi bi-youtube"></i> 교육 영상</h5>
							<div class="youtube-container">
								<?php
								$youtube_url = $edu['youtube_url'];
								$video_id = '';
								if (preg_match('/[?&]v=([^&]+)/', $youtube_url, $matches)) {
									$video_id = $matches[1];
								} else if (preg_match('/youtu\.be\/([^?]+)/', $youtube_url, $matches)) {
									$video_id = $matches[1];
								}
								if ($video_id):
									?>
									<iframe src="https://www.youtube.com/embed/<?php echo $video_id; ?>" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
								<?php endif; ?>
							</div>
						</div>
					<?php endif; ?>

					<!-- 교육 설명 -->
					<?php if (!empty($edu['edu_desc'])): ?>
						<div class="mb-4">
							<h5 class="mb-3">교육 안내</h5>
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
								<p class="mb-2 ps-3">교육 신청 관리 및 연락을 위한 목적</p>

								<p class="mb-1"><strong>4. 개인정보 보유 및 이용 기간</strong></p>
								<p class="mb-0 ps-3">목적 달성 후 즉시 파기 (최대 1년)</p>
							</div>
							<hr>
							<p class="mb-0 text-muted small">
								<i class="bi bi-info-circle"></i>
								귀하는 위의 개인정보 제공에 대한 동의를 거부할 권리가 있으며,
								동의를 거부할 경우 교육 신청이 제한될 수 있습니다.
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

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/js/bootstrap.bundle.min.js"></script>
<script>
	$(document).ready(function() {
		$('#applicationForm').on('submit', function(e) {
			e.preventDefault();

			// 동의 확인
			if (!$('#agreePrivacy').is(':checked')) {
				alert('개인정보 제3자 제공에 동의해주세요.');
				return;
			}

			var name = $('#applicantName').val().trim();
			var phone = $('#applicantPhone').val().trim();

			if (!name || !phone) {
				alert('이름과 연락처를 입력해주세요.');
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
						alert('신청이 완료되었습니다.');
						$('#applicationForm')[0].reset();
						$('#agreePrivacy').prop('checked', false);
					} else {
						alert(response.message || '신청 처리 중 오류가 발생했습니다.');
					}
				},
				error: function() {
					alert('신청 처리 중 오류가 발생했습니다.');
				}
			});
		});
	});
</script>

</body>
</html>
