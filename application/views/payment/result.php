<?php
/**
 * 파일 위치: application/views/payment/result.php
 * 역할: PG 결제 결과 화면
 */
?>
<!DOCTYPE html>
<html lang="ko">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>결제 결과</title>
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css">
</head>
<body>
<div class="container mt-5">
	<div class="row justify-content-center">
		<div class="col-md-6">
			<div class="card">
				<div class="card-body text-center py-5">
					<?php if ($success): ?>
						<i class="bi bi-check-circle text-success" style="font-size: 4rem;"></i>
						<h4 class="mt-3">결제가 완료되었습니다</h4>
						<p class="text-muted mt-3"><?php echo $message; ?></p>

						<?php if (isset($payment_info)): ?>
							<div class="mt-4 text-start">
								<div class="border rounded p-3 bg-light">
									<div class="row mb-2">
										<div class="col-4 text-muted">거래번호</div>
										<div class="col-8"><?php echo $payment_info['Tid']; ?></div>
									</div>
									<div class="row mb-2">
										<div class="col-4 text-muted">승인번호</div>
										<div class="col-8"><?php echo $payment_info['AuthCode'] ?? '-'; ?></div>
									</div>
									<div class="row mb-2">
										<div class="col-4 text-muted">결제금액</div>
										<div class="col-8"><strong><?php echo number_format($payment_info['Amt']); ?>원</strong></div>
									</div>
									<div class="row">
										<div class="col-4 text-muted">결제일시</div>
										<div class="col-8"><?php echo date('Y-m-d H:i:s'); ?></div>
									</div>
								</div>
							</div>
						<?php endif; ?>
					<?php else: ?>
						<i class="bi bi-x-circle text-danger" style="font-size: 4rem;"></i>
						<h4 class="mt-3">결제가 실패했습니다</h4>
						<p class="text-muted mt-3"><?php echo $message; ?></p>
						<?php if (isset($error_code) && $error_code): ?>
							<p class="text-muted small">오류 코드: <?php echo $error_code; ?></p>
						<?php endif; ?>
					<?php endif; ?>

					<div class="mt-4">
						<button type="button" class="btn btn-primary" onclick="closeAndReload()">확인</button>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script>
	function closeAndReload() {
		// 부모 창이 있으면 부모 창 새로고침 후 현재 창 닫기
		if (window.opener) {
			window.opener.location.reload();
			window.close();
		} else {
			// 부모 창이 없으면 현재 창에서 리다이렉트
			window.location.href = '<?php echo base_url('send/popup'); ?>';
		}
	}

	<?php if ($success): ?>
	// 결제 성공 시 자동으로 3초 후 창 닫기
	setTimeout(function() {
		closeAndReload();
	}, 3000);
	<?php endif; ?>
</script>
</body>
</html>
