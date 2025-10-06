<?php
/**
 * 파일 위치: application/views/member_card_error.php
 * 역할: 회원카드 URL 오류 페이지
 */
?>
<!DOCTYPE html>
<html lang="ko">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>오류 - 회원 등록</title>

	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">

	<style>
		body {
			background-color: #f8f9fa;
			min-height: 100vh;
			display: flex;
			align-items: center;
		}
	</style>
</head>
<body>
<div class="container">
	<div class="row justify-content-center">
		<div class="col-12 col-md-6">
			<div class="card text-center">
				<div class="card-body py-5">
					<i class="bi bi-exclamation-triangle text-danger" style="font-size: 4rem;"></i>
					<h3 class="mt-4 mb-3">접근할 수 없습니다</h3>
					<p class="text-muted">
						<?php echo isset($error_message) ? htmlspecialchars($error_message) : '잘못된 접근입니다.'; ?>
					</p>
				</div>
			</div>
		</div>
	</div>
</div>
</body>
</html>
