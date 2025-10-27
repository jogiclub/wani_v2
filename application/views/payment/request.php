<?php
/**
 * 파일 위치: application/views/payment/request.php
 * 역할: PG 결제 요청 화면
 */
?>
<!DOCTYPE html>
<html lang="ko">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>결제하기</title>
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css">
	<script src="<?php echo $payment_data['sdk_url']; ?>?version=<?php echo date('YmdHis'); ?>"></script>
</head>
<body>
<div class="container mt-5">
	<div class="row justify-content-center">
		<div class="col-md-6">
			<div class="card">
				<div class="card-header">
					<h5 class="mb-0">결제 정보 확인</h5>
				</div>
				<div class="card-body">
					<div class="mb-3">
						<label class="form-label">상품명</label>
						<p class="form-control-plaintext"><?php echo htmlspecialchars($package_info['package_name']); ?></p>
					</div>
					<div class="mb-3">
						<label class="form-label">결제 금액</label>
						<p class="form-control-plaintext"><strong><?php echo number_format($charge_amount); ?>원</strong></p>
					</div>
				</div>
				<div class="card-footer text-end">
					<button type="button" class="btn btn-secondary" onclick="window.close();">취소</button>
					<button type="button" class="btn btn-primary" id="btnPayment">결제하기</button>
				</div>
			</div>
		</div>
	</div>
</div>

<!-- 결제 요청 폼 (hidden) -->
<form id="paymentForm" name="paymentForm" method="post" style="display: none;">
	<input type="hidden" name="PayMethod" value="<?php echo $payment_data['PayMethod']; ?>">
	<input type="hidden" name="GoodsCnt" value="<?php echo $payment_data['GoodsCnt']; ?>">
	<input type="hidden" name="GoodsName" value="<?php echo $payment_data['GoodsName']; ?>">
	<input type="hidden" name="Amt" value="<?php echo $payment_data['Amt']; ?>">
	<input type="hidden" name="Moid" value="<?php echo $payment_data['Moid']; ?>">
	<input type="hidden" name="Mid" value="<?php echo $payment_data['Mid']; ?>">
	<input type="hidden" name="ReturnUrl" value="<?php echo $payment_data['ReturnUrl']; ?>">
	<input type="hidden" name="StopUrl" value="<?php echo $payment_data['StopUrl']; ?>">
	<input type="hidden" name="BuyerName" value="<?php echo $payment_data['BuyerName']; ?>">
	<input type="hidden" name="BuyerTel" value="<?php echo $payment_data['BuyerTel']; ?>">
	<input type="hidden" name="BuyerEmail" value="<?php echo $payment_data['BuyerEmail']; ?>">
	<input type="hidden" name="EncryptData" value="<?php echo $payment_data['EncryptData']; ?>">
	<input type="hidden" name="EdiDate" value="<?php echo $payment_data['EdiDate']; ?>">
	<input type="hidden" name="MallReserved" value="<?php echo htmlspecialchars($payment_data['MallReserved']); ?>">
	<input type="hidden" name="TaxAmt" value="<?php echo $payment_data['TaxAmt']; ?>">
	<input type="hidden" name="TaxFreeAmt" value="<?php echo $payment_data['TaxFreeAmt']; ?>">
	<input type="hidden" name="VatAmt" value="<?php echo $payment_data['VatAmt']; ?>">
</form>

<!-- PC용 승인 폼 -->
<form id="approvalForm" name="approvalForm" method="post" style="display: none;">
	<input type="hidden" id="Tid" name="Tid">
	<input type="hidden" id="TrAuthKey" name="TrAuthKey">
</form>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script>
	const PG_MODE = '<?php echo $this->config->item('smartro_pg')['mode']; ?>';
	const RETURN_URL = '<?php echo $payment_data['ReturnUrl']; ?>';

	// 결제하기 버튼 클릭
	document.getElementById('btnPayment').addEventListener('click', function() {
		goPay();
	});

	// PG 결제 요청 함수
	function goPay() {
		smartropay.init({
			mode: PG_MODE
		});

		// 모바일 여부 확인
		const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);

		if (isMobile) {
			// 모바일 연동
			smartropay.payment({
				FormId: 'paymentForm'
			});
		} else {
			// PC 연동 (Callback 함수 사용)
			smartropay.payment({
				FormId: 'paymentForm',
				Callback: function(res) {
					const approvalForm = document.approvalForm;
					approvalForm.Tid.value = res.Tid;
					approvalForm.TrAuthKey.value = res.TrAuthKey;
					approvalForm.action = RETURN_URL;
					approvalForm.submit();
				}
			});
		}
	}
</script>
</body>
</html>
