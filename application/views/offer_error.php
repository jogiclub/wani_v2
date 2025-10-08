<?php
/**
 * 파일 위치: application/views/offer_error.php
 * 역할: Offer 페이지 오류 표시
 */
?>
<!DOCTYPE html>
<html lang="ko">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>오류</title>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
	<style>
		body {
			background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
			min-height: 100vh;
			display: flex;
			align-items: center;
			justify-content: center;
		}
		.error-card {
			max-width: 500px;
			box-shadow: 0 10px 40px rgba(0,0,0,0.2);
		}
	</style>
</head>
<body>

<div class="container">
	<div class="card error-card">
		<div class="card-body text-center p-5">
			<i class="bi bi-exclamation-triangle text-warning" style="font-size: 4rem;"></i>
			<h3 class="mt-4 mb-3">
				<?php echo isset($is_expired) && $is_expired ? '링크 만료' : '오류 발생'; ?>
			</h3>
			<p class="text-muted mb-4">
				<?php echo htmlspecialchars($error_message); ?>
			</p>
			<button type="button" class="btn btn-primary" onclick="window.close()">
				<i class="bi bi-x-circle"></i> 닫기
			</button>
		</div>
	</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
