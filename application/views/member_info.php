<!DOCTYPE html>
<html lang="ko">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>회원 정보 - <?php echo htmlspecialchars($member_info['member_name']); ?></title>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
	<link rel="stylesheet" as="style" crossorigin href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard@v1.3.9/dist/web/static/pretendard.min.css" />
	<style>
		body {
			background-color: #f8f9fa;
			font-family: "Pretendard", Pretendard, -apple-system, BlinkMacSystemFont, system-ui, Roboto, "Helvetica Neue", "Segoe UI", sans-serif;
		}
		.org-header {
			background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
			color: white;
			padding: 30px;
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
		.section-title {
			font-weight: 600;
			color: #495057;

			padding-bottom: 10px;
			margin-bottom: 20px;
		}
		.info-label {
			font-weight: 600;
			color: #6c757d;
			margin-bottom: 5px;
			font-size: 0.9rem;
		}
		.info-value {
			color: #212529;
		}
		.memo-item {
			border: 1px solid #dee2e6;
			border-radius: 8px;
			padding: 15px;
			margin-bottom: 10px;
			background: white;
		}
		.memo-content {
			margin-bottom: 10px;
			white-space: pre-wrap;
			word-break: break-word;
		}
		.memo-date {
			font-size: 0.85rem;
			color: #6c757d;
		}
	</style>
</head>
<body>
<div class="container py-4">
	<div class="row justify-content-center">
		<div class="col-12 col-lg-10">
			<!-- 조직 헤더 -->
			<div class="card mb-4">
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
					<h4 class="mb-0"><?php echo htmlspecialchars($org_info['org_name']); ?></h4>
				</div>
			</div>

			<!-- 기본 정보 -->
			<div class="card mb-4">
				<div class="card-body">
					<h5 class="section-title border-bottom">기본정보</h5>
					<div class="row">
						<div class="col-md-4 mb-3">
							<div class="info-label">이름</div>
							<div class="info-value"><?php echo htmlspecialchars($member_info['member_name']); ?></div>
						</div>
						<div class="col-md-4 mb-3">
							<div class="info-label">성별</div>
							<div class="info-value">
								<?php
								if ($member_info['member_sex'] === 'male') echo '남';
								else if ($member_info['member_sex'] === 'female') echo '여';
								else echo '-';
								?>
							</div>
						</div>
						<?php if (!empty($member_info['member_nick'])): ?>
							<div class="col-md-4 mb-3">
								<div class="info-label">별명</div>
								<div class="info-value"><?php echo htmlspecialchars($member_info['member_nick']); ?></div>
							</div>
						<?php endif; ?>
						<?php if (!empty($member_info['position_name'])): ?>
							<div class="col-md-4 mb-3">
								<div class="info-label">직위/직분</div>
								<div class="info-value"><?php echo htmlspecialchars($member_info['position_name']); ?></div>
							</div>
						<?php endif; ?>
						<?php if (!empty($member_info['duty_name'])): ?>
							<div class="col-md-4 mb-3">
								<div class="info-label">직책</div>
								<div class="info-value"><?php echo htmlspecialchars($member_info['duty_name']); ?></div>
							</div>
						<?php endif; ?>
						<?php if (!empty($member_info['member_phone'])): ?>
							<div class="col-md-4 mb-3">
								<div class="info-label">연락처</div>
								<div class="info-value"><?php echo htmlspecialchars($member_info['member_phone']); ?></div>
							</div>
						<?php endif; ?>
						<?php if (!empty($member_info['member_birth'])): ?>
							<div class="col-md-4 mb-3">
								<div class="info-label">생년월일</div>
								<div class="info-value"><?php echo htmlspecialchars($member_info['member_birth']); ?></div>
							</div>
						<?php endif; ?>
						<?php if (!empty($member_info['member_address'])): ?>
							<div class="col-md-8 mb-3">
								<div class="info-label">주소</div>
								<div class="info-value">
									<?php echo htmlspecialchars($member_info['member_address']); ?>
									<?php if (!empty($member_info['member_address_detail'])): ?>
										<?php echo htmlspecialchars($member_info['member_address_detail']); ?>
									<?php endif; ?>
								</div>
							</div>
						<?php endif; ?>
					</div>
				</div>
			</div>

			<!-- 추가 정보 -->
			<?php if (!empty($detail_fields)): ?>
				<div class="card mb-4">
					<div class="card-body">
						<h5 class="section-title border-bottom">추가정보</h5>
						<div class="row">
							<?php foreach ($detail_fields as $field): ?>
								<?php
								$field_value = isset($member_detail[$field['field_idx']]) ? $member_detail[$field['field_idx']] : '';
								if (empty($field_value)) continue;
								?>
								<div class="col-md-6 mb-3">
									<div class="info-label"><?php echo htmlspecialchars($field['field_name']); ?></div>
									<div class="info-value">
										<?php
										if ($field['field_type'] === 'checkbox') {
											echo $field_value === 'Y' ? '<i class="bi bi-check-circle-fill text-success"></i>' : '-';
										} else {
											echo htmlspecialchars($field_value);
										}
										?>
									</div>
								</div>
							<?php endforeach; ?>
						</div>
					</div>
				</div>
			<?php endif; ?>

			<!-- 정착 메모 -->
			<div class="card">
				<div class="card-body">

						<h5 class="section-title border-bottom mb-2">정착메모</h5>

					<div class="d-flex justify-content-between align-items-center mb-2">
						<div class="text-muted">
							전체 <strong><?php echo count($settlement_memos); ?></strong>건
						</div>
						<button type="button" class="btn btn-sm btn-primary" id="addMemoBtn">
							<i class="bi bi-plus-lg"></i> 메모추가
						</button>
					</div>

					<div id="memoList">
						<?php if (empty($settlement_memos)): ?>
							<div class="text-center text-muted py-4">
								<i class="bi bi-inbox"></i>
								<p class="mb-0 mt-2">등록된 메모가 없습니다.</p>
							</div>
						<?php else: ?>
							<?php foreach ($settlement_memos as $memo): ?>
								<div class="memo-item" data-memo-idx="<?php echo $memo['idx']; ?>">

									<div class="d-flex justify-content-between align-items-center">
										<div class="memo-content"><?php echo nl2br(htmlspecialchars($memo['memo_content'])); ?></div>

										<div>
											<button type="button" class="btn btn-sm btn-outline-primary edit-memo-btn" data-memo-idx="<?php echo $memo['idx']; ?>">
												<i class="bi bi-pencil"></i> 수정
											</button>
											<button type="button" class="btn btn-sm btn-outline-danger delete-memo-btn" data-memo-idx="<?php echo $memo['idx']; ?>">
												<i class="bi bi-trash"></i> 삭제
											</button>
										</div>
									</div>
									<div class="memo-date"><?php echo $memo['regi_date']; ?></div>
								</div>
							<?php endforeach; ?>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<!-- 메모 추가/수정 모달 -->
<div class="modal fade" id="memoModal" tabindex="-1" aria-labelledby="memoModalLabel" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="memoModalLabel">정착메모 추가</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<div class="mb-3">
					<label for="memoContent" class="form-label">메모 내용</label>
					<textarea class="form-control" id="memoContent" rows="5" required></textarea>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
				<button type="button" class="btn btn-primary" id="saveMemoBtn">저장</button>
			</div>
		</div>
	</div>
</div>

<!-- 삭제 확인 모달 -->
<div class="modal fade" id="deleteMemoModal" tabindex="-1" aria-labelledby="deleteMemoModalLabel" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="deleteMemoModalLabel">메모 삭제</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<p>이 메모를 삭제하시겠습니까?</p>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
				<button type="button" class="btn btn-danger" id="confirmDeleteMemoBtn">삭제</button>
			</div>
		</div>
	</div>
</div>

<!-- Toast 컨테이너 -->
<div class="toast-container position-fixed top-0 end-0 p-3">
	<div id="liveToast" class="toast align-items-center border-0" role="alert" aria-live="assertive" aria-atomic="true">
		<div class="d-flex">
			<div class="toast-body" id="toastMessage"></div>
			<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
		</div>
	</div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
	$(document).ready(function() {
		const baseUrl = '<?php echo base_url(); ?>';
		const orgId = <?php echo $org_id; ?>;
		const memberIdx = <?php echo $member_info['member_idx']; ?>;
		const passcode = '<?php echo $passcode; ?>';
		let currentMemoIdx = null;
		let isEditMode = false;

		// 메모 추가 버튼
		$('#addMemoBtn').on('click', function() {
			isEditMode = false;
			currentMemoIdx = null;
			$('#memoModalLabel').text('정착메모 추가');
			$('#memoContent').val('');
			$('#memoModal').modal('show');
		});

		// 메모 수정 버튼
		$(document).on('click', '.edit-memo-btn', function() {
			isEditMode = true;
			currentMemoIdx = $(this).data('memo-idx');
			const memoContent = $(this).closest('.memo-item').find('.memo-content').text().trim();

			$('#memoModalLabel').text('정착메모 수정');
			$('#memoContent').val(memoContent);
			$('#memoModal').modal('show');
		});

		// 메모 저장
		$('#saveMemoBtn').on('click', function() {
			const memoContent = $('#memoContent').val().trim();

			if (!memoContent) {
				showToast('메모 내용을 입력해주세요.', 'warning');
				return;
			}

			const url = isEditMode
				? baseUrl + 'member_info/update_settlement_memo'
				: baseUrl + 'member_info/add_settlement_memo';

			const data = {
				org_id: orgId,
				member_idx: memberIdx,
				passcode: passcode,
				memo_content: memoContent
			};

			if (isEditMode) {
				data.memo_idx = currentMemoIdx;
			}

			$.ajax({
				url: url,
				method: 'POST',
				data: data,
				dataType: 'json',
				success: function(response) {
					if (response.success) {
						showToast(response.message, 'success');
						$('#memoModal').modal('hide');
						location.reload();
					} else {
						showToast(response.message || '처리 중 오류가 발생했습니다.', 'error');
					}
				},
				error: function(xhr, status, error) {
					console.error('AJAX Error:', status, error);
					console.error('Response:', xhr.responseText);
					showToast('처리 중 오류가 발생했습니다.', 'error');
				}
			});
		});

		// 메모 삭제 버튼
		$(document).on('click', '.delete-memo-btn', function() {
			currentMemoIdx = $(this).data('memo-idx');
			$('#deleteMemoModal').modal('show');
		});

		// 메모 삭제 확인
		$('#confirmDeleteMemoBtn').on('click', function() {
			$.ajax({
				url: baseUrl + 'member_info/delete_settlement_memo',
				method: 'POST',
				data: {
					org_id: orgId,
					member_idx: memberIdx,
					passcode: passcode,
					memo_idx: currentMemoIdx
				},
				dataType: 'json',
				success: function(response) {
					if (response.success) {
						showToast(response.message, 'success');
						$('#deleteMemoModal').modal('hide');
						location.reload();
					} else {
						showToast(response.message || '삭제 중 오류가 발생했습니다.', 'error');
					}
				},
				error: function(xhr, status, error) {
					console.error('AJAX Error:', status, error);
					console.error('Response:', xhr.responseText);
					showToast('삭제 중 오류가 발생했습니다.', 'error');
				}
			});
		});


	});
</script>
</body>
</html>
