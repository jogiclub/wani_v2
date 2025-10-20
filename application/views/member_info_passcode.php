<!DOCTYPE html>
<html lang="ko">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>회원 정보 조회</title>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
	<link rel="stylesheet" as="style" crossorigin href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard@v1.3.9/dist/web/static/pretendard.min.css" />
	<style>
		body {
			background-color: #f8f9fa;
			min-height: 100vh;
			display: flex;
			align-items: center;
			font-family: "Pretendard", Pretendard, -apple-system, BlinkMacSystemFont, system-ui, Roboto, "Helvetica Neue", "Segoe UI", sans-serif;
		}
		.card {
			box-shadow: 0 0 20px rgba(0,0,0,0.1);
		}
	</style>
</head>
<body>
<div class="container">
	<div class="row justify-content-center">
		<div class="col-12 col-md-6 col-lg-4">
			<div class="card">
				<div class="card-body p-5 text-center">
					<i class="bi bi-shield-lock text-primary" style="font-size: 3rem;"></i>
					<h4 class="mt-3 mb-4">회원 정보 조회</h4>
					<p class="text-muted mb-4">패스코드를 입력해주세요</p>

					<form method="POST" id="passcodeForm">
						<div class="mb-3">
							<input type="text" class="form-control form-control-lg text-center"
								   id="passcode" name="passcode"
								   placeholder="패스코드 입력"
								   maxlength="6"
								   required
								   style="letter-spacing: 0.5em;">
						</div>
						<button type="submit" class="btn btn-primary btn-lg w-100">
							<i class="bi bi-check-circle"></i> 확인
						</button>
					</form>
				</div>
			</div>
		</div>
	</div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
	$(document).ready(function() {
		// 패스코드 입력 시 자동 대문자 변환
		$('#passcode').on('input', function() {
			this.value = this.value.toUpperCase();
		});

		// 자동 포커스
		$('#passcode').focus();
	});
</script>
</body>
</html>
