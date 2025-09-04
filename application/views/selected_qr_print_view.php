<!DOCTYPE html>
<html>
<head>
	<title>선택 QR 코드 인쇄</title>
	<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
	<style>
		body {
			margin: 0;
			padding: 0;
		}

		#controlPanel {
			background: #f8f9fa;
			padding: 15px;
			border-bottom: 2px solid #dee2e6;
			position: fixed;
			top: 0;
			left: 0;
			right: 0;
			z-index: 1000;
		}

		.control-item {
			display: inline-block;
			margin-right: 20px;
			margin-bottom: 10px;
		}

		.control-item label {
			font-weight: 500;
			margin-right: 8px;
		}

		#qrCodeContainer {
			display: grid;
			grid-template-columns: repeat(7, 1fr);
			grid-gap: 10px;
			padding: 11mm 10mm;
			justify-items: center;
			margin-top: 80px; /* 컨트롤 패널 높이만큼 여백 */
		}

		.qr-code-item {
			width: 25mm;
			height: 30mm;
			display: flex;
			flex-direction: column;
			align-items: center;
			justify-content: center;
			page-break-inside: avoid;
			position: relative;
		}

		.qr-code-element img {
			width: 17mm;
			height: auto;
		}

		.qr-code-label b {
			font-size: 10pt;
			text-align: center;
			margin-top: 5px;
			font-weight: 400;
			color: #999;
		}

		.qr-code-label {
			font-size: 11pt;
			text-align: center;
			margin-top: 5px;
			font-weight: 600;
		}

		.empty-slot {
			width: 25mm;
			height: 30mm;
			border: 1px dashed #ccc;
			background: #f8f9fa;
		}

		.position-number {
			position: absolute;
			top: 2px;
			left: 2px;
			font-size: 8pt;
			color: #999;
			background: rgba(255, 255, 255, 0.8);
			padding: 1px 3px;
			border-radius: 2px;
		}

		@media print {
			#controlPanel {
				display: none;
			}
			#qrCodeContainer {
				grid-template-columns: repeat(7, 1fr);
				grid-gap: 0;
				padding: 0;
				margin-top: 0;
			}
			.qr-code-item {
				width: 25mm;
				height: 30mm;
				border: none;
			}
			.empty-slot {
				border: none;
				background: transparent;
			}
			.position-number {
				display: none;
			}
		}
	</style>
</head>
<body>
<!-- 컨트롤 패널 -->
<div id="controlPanel">
	<div class="control-item">
		<label for="startPosition">시작 위치:</label>
		<select id="startPosition" class="form-select" style="width: 100px; display: inline-block;">
			<?php for ($i = 1; $i <= 70; $i++): ?>
				<option value="<?php echo $i; ?>" <?php echo ($i == $start_position) ? 'selected' : ''; ?>>
					<?php echo $i; ?>번
				</option>
			<?php endfor; ?>
		</select>
	</div>

	<div class="control-item">
		<button id="applyPosition" class="btn btn-primary">위치 적용</button>
	</div>

	<div class="control-item">
		<button id="printBtn" class="btn btn-success">인쇄</button>
	</div>

	<div class="control-item">
		<span id="memberCount">총 <?php echo count($member_indices); ?>명</span>
	</div>
</div>

<!-- QR 코드 컨테이너 -->
<div id="qrCodeContainer"></div>

<script>
	$(document).ready(function() {
		var memberIndices = <?php echo json_encode($member_indices); ?>;
		var startPosition = <?php echo $start_position; ?>;
		var memberData = [];

		// 회원 정보 불러오기
		loadMemberData();

		// 시작 위치 변경 이벤트
		$('#startPosition').on('change', function() {
			startPosition = parseInt($(this).val());
		});

		// 위치 적용 버튼 클릭
		$('#applyPosition').on('click', function() {
			generateQRCodes();
		});

		// 인쇄 버튼 클릭
		$('#printBtn').on('click', function() {
			window.print();
		});

		/**
		 * 회원 데이터 로드
		 */
		function loadMemberData() {
			$.ajax({
				url: '/member/get_selected_members',
				type: 'POST',
				data: { member_indices: memberIndices },
				dataType: 'json',
				success: function(response) {
					if (response.success) {
						memberData = response.members;
						generateQRCodes();
					} else {
						alert('회원 정보를 불러오는데 실패했습니다: ' + response.message);
					}
				},
				error: function() {
					alert('회원 정보를 불러오는데 실패했습니다');
				}
			});
		}

		/**
		 * QR 코드 생성 및 배치
		 */
		function generateQRCodes() {
			var qrCodeContainer = $('#qrCodeContainer');
			qrCodeContainer.empty();

			// A4 7x10 라벨지 기준 (총 70개 위치)
			var totalSlots = 70;
			var currentSlot = 1;

			// 시작 위치까지 빈 슬롯 생성
			for (var i = 1; i < startPosition; i++) {
				var emptySlot = $('<div class="qr-code-item empty-slot">' +
					'<div class="position-number">' + i + '</div>' +
					'</div>');
				qrCodeContainer.append(emptySlot);
				currentSlot++;
			}

			// 회원 QR 코드 생성
			memberData.forEach(function(member, index) {
				if (currentSlot > totalSlots) {
					return; // 라벨지 범위를 초과하면 중단
				}

				var qrCodeItem = $('<div class="qr-code-item">' +
					'<div class="position-number">' + currentSlot + '</div>' +
					'</div>');
				var qrCodeElement = $('<div class="qr-code-element"></div>');
				var qrCodeLabel = $('<div class="qr-code-label"></div>');

				// QR 코드 생성
				new QRCode(qrCodeElement[0], {
					text: member.member_idx.toString(),
					width: 200,
					height: 200,
					correctLevel: QRCode.CorrectLevel.M
				});

				// 라벨 설정 (그룹명 + 회원명)
				var areaName = member.area_name || '미분류';
				qrCodeLabel.html('<b>' + areaName + '</b><br>' + member.member_name);

				qrCodeItem.append(qrCodeElement);
				qrCodeItem.append(qrCodeLabel);
				qrCodeContainer.append(qrCodeItem);

				currentSlot++;
			});

			// 남은 슬롯을 빈 슬롯으로 채우기 (선택사항 - 레이아웃 확인용)
			for (var j = currentSlot; j <= totalSlots; j++) {
				var emptySlot = $('<div class="qr-code-item empty-slot">' +
					'<div class="position-number">' + j + '</div>' +
					'</div>');
				qrCodeContainer.append(emptySlot);
			}
		}
	});
</script>
</body>
</html>
