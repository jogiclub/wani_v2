<?php
/**
 * 파일 위치: application/views/login_redirect.php
 * 역할: 로그인 후 localStorage 확인 및 조직 자동 선택
 */
?>
<!DOCTYPE html>
<html lang="ko">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>로그인 중...</title>
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css">
	<link rel="stylesheet" as="style" crossorigin href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard@v1.3.9/dist/web/static/pretendard.min.css" />
	<style>
		body {
			display: flex;
			justify-content: center;
			align-items: center;
			height: 100vh;
			background-color: #f8f9fa;
			font-family: 'Pretendard Variable', sans-serif;
		}
		.loading-container {
			text-align: center;
		}
	</style>
</head>
<body>
<div class="loading-container">
	<div class="spinner-border text-primary mb-3" role="status" style="width: 3rem; height: 3rem;">
		<span class="visually-hidden">로딩 중...</span>
	</div>
	<h5>로그인 중입니다...</h5>
	<p class="text-muted">잠시만 기다려주세요.</p>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script>
	$(document).ready(function() {
		// localStorage에서 마지막 선택 조직 확인
		const lastSelectedOrgId = localStorage.getItem('lastSelectedOrgId');

		if (lastSelectedOrgId) {
			// localStorage에 조직 정보가 있으면 해당 조직으로 전환
			$.ajax({
				url: '/login/set_default_org',
				type: 'POST',
				data: { org_id: lastSelectedOrgId },
				dataType: 'json',
				success: function(response) {
					if (response.success) {
						// 조직 설정 성공 시 최종 목적지로 이동
						window.location.href = '<?php echo isset($redirect_url) ? $redirect_url : "/qrcheck"; ?>';
					} else {
						// 실패 시에도 기본 페이지로 이동
						window.location.href = '<?php echo isset($redirect_url) ? $redirect_url : "/qrcheck"; ?>';
					}
				},
				error: function() {
					// 에러 발생 시에도 기본 페이지로 이동
					window.location.href = '<?php echo isset($redirect_url) ? $redirect_url : "/qrcheck"; ?>';
				}
			});
		} else {
			// localStorage에 조직 정보가 없으면 바로 이동
			window.location.href = '<?php echo isset($redirect_url) ? $redirect_url : "/qrcheck"; ?>';
		}
	});
</script>
</body>
</html>
