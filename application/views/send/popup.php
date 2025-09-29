<?php
/**
 * 역할: 문자 발송 팝업 화면
 */
?>
<?php include APPPATH . 'views/noheader.php'; ?>
<link rel="stylesheet" href="/assets/css/send_popup.css?<?php echo WB_VERSION; ?>">

<div class="container send-popup">
	<div class="row">
		<div class="col-12 col-lg-6">
			<div class="card">
				<div class="card-body">
					<!-- 발송 타입 선택 -->
					<div class="mb-3">
						<label class="col-form-label">발송 타입</label>
						<div class="btn-group p-0" role="group" id="sendTypeGroup" style="width: 100%">
							<input type="radio" class="btn-check" name="send_type" id="sms" value="sms" checked>
							<label class="btn btn-outline-primary" for="sms">SMS</label>

							<input type="radio" class="btn-check" name="send_type" id="lms" value="lms">
							<label class="btn btn-outline-primary" for="lms">LMS</label>

							<input type="radio" class="btn-check" name="send_type" id="mms" value="mms">
							<label class="btn btn-outline-primary" for="mms">MMS</label>

							<input type="radio" class="btn-check" name="send_type" id="kakao" value="kakao">
							<label class="btn btn-outline-primary" for="kakao">카카오톡</label>
						</div>

					</div>

					<!-- 발신번호 선택 -->
					<div class="mb-3">
						<label for="senderSelect" class="col-form-label">발신번호</label>

						<select class="form-select" id="senderSelect" name="sender_number">
							<option value="">발신번호를 선택하세요</option>
							<?php foreach ($sender_numbers as $sender): ?>
								<option value="<?php echo $sender['sender_number']; ?>"
										data-name="<?php echo htmlspecialchars($sender['sender_name']); ?>"
									<?php echo $sender['is_default'] === 'Y' ? 'selected' : ''; ?>>
									<?php echo htmlspecialchars($sender['sender_name']); ?>
									(<?php echo $sender['sender_number']; ?>)
								</option>
							<?php endforeach; ?>
						</select>
					</div>


					<!-- 수신번호 목록 -->
					<div class="mb-3">
						<label class="col-form-label">수신자</label>

						<div class="border rounded p-2">
							<div class="row">
								<div class="col-12 mb-2 d-flex justify-content-between align-items-center">
									<strong>선택된 회원 (<?php echo count($selected_members); ?>명)</strong>
									<div>
									<a class="btn btn-xs btn-danger"><i class="bi bi-x-square"></i> 전체삭제</a>
									<a class="btn btn-xs btn-success"><i class="bi bi-pencil-square"></i> 전체편집</a>
									</div>
								</div>
							</div>
							<?php if (empty($selected_members)): ?>
								<div class="text-muted">선택된 회원이 없습니다.</div>
							<?php else: ?>
							<div class="send-table">
								<table class="table table-striped">
									<thead>
									<tr>
										<th scope="col">이름</th>
										<th scope="col">직분</th>
										<th scope="col">연락처</th>
										<th scope="col">그룹</th>
										<th scope="col">임시1</th>
										<th scope="col">임시2</th>
										<th scope="col">삭제</th>
									</tr>
									</thead>
									<tbody  id="receiverList">
									<?php foreach ($selected_members as $member): ?>
										<tr class="receiver-item"
											 data-member-idx="<?php echo $member['member_idx']; ?>"
											 data-phone="<?php echo $member['member_phone']; ?>"
											 data-name="<?php echo htmlspecialchars($member['member_name']); ?>">

												<td><?php echo htmlspecialchars($member['member_name']); ?></td>
											<td></td>
												<td><?php echo $member['member_phone']; ?></td>
												<td><?php echo htmlspecialchars($member['area_name']); ?></td>
											<td></td>
											<td></td>
												<td><a class="remove-receiver"><i class="bi bi-x-lg"></i></a></td>

										</tr>
									<?php endforeach; ?>
									</tbody>
								</table>
							</div>
							<?php endif; ?>
						</div>

					</div>


					<!-- 메시지 내용 -->
					<div class="mb-3">

						<div class="row">
							<div class="col-sm-6">
								<label for="messageContent" class="col-form-label">메시지 내용</label>
								<textarea class="form-control" id="messageContent" name="message_content" rows="4" placeholder="전송할 메시지를 입력하세요" required></textarea>
								<div class="form-text">
									<span id="charCount">0</span> / <span id="maxChar">90</span> 자
									<span id="messageTypeInfo" class="ms-3">SMS (90자 이하)</span>
								</div>
							</div>
							<div class="col-sm-6">
								<label class="col-form-label">발송 치환</label>
								<div>
									<a class="btn btn-sm btn-outline-secondary mb-1">이름</a>
									<a class="btn btn-sm btn-outline-secondary mb-1">직분</a>
									<a class="btn btn-sm btn-outline-secondary mb-1">연락처</a>
									<a class="btn btn-sm btn-outline-secondary mb-1">그룹</a>
									<a class="btn btn-sm btn-outline-secondary mb-1">임시1</a>
									<a class="btn btn-sm btn-outline-secondary mb-1">임시2</a>
								</div>
							</div>
						</div>
					</div>

					<!-- MMS 첨부파일 (MMS 선택 시에만 표시) -->
					<div class="mb-3 d-none" id="mmsFileSection">
						<label for="mmsFile" class="col-form-label">첨부파일</label>

						<input type="file" class="form-control" id="mmsFile" name="mms_file"
							   accept="image/*"/>
						<div class="form-text">이미지 파일만 첨부 가능합니다. (최대 300KB)</div>

					</div>
				</div>

				<div class="card-footer d-flex justify-content-between align-items-center">
					<!-- 예상 비용 (선택사항) -->
					<div class="d-flex align-items-center">
						<span class="badge badge-sm text-bg-info me-2">예상비용</span>
						<span  class="badge badge-sm text-bg-warning me-2" id="costInfo">SMS</span>
							<strong class="me-2">1,530원</strong>
							<small>(153명 × 10원)</small>
					</div>
					<div>
						<button type="button" class="btn btn-secondary me-2" onclick="window.close();">취소</button>
						<button type="button" class="btn btn-primary" id="sendBtn">
							<i class="bi bi-send"></i> 발송하기
						</button>
					</div>
				</div>
			</div>
		</div>
		<div class="col-12 col-lg-6">
			<div class="card">
				<div class="card-body">
					<!-- 메시지 템플릿 선택 -->
					<div class="row mb-3">
						<div class="col-12">
						<label for="templateSelect" class="col-form-label">메시지 템플릿</label>
						</div>
						<div class="col-6 col-md-4">
							<figure class="figure rounded bg-dark text-white p-2"
									style="width: 100%; cursor: pointer; min-height: 150px">
								<small>{이름} 회원님 생일축하합니다.</small>
							</figure>
						</div>
						<div class="col-6 col-md-4">
							<figure class="figure rounded bg-dark text-white p-2"
									style="width: 100%; cursor: pointer; min-height: 150px">
								<small>{이름} 회원님 생일축하합니다.</small>
							</figure>
						</div>
						<div class="col-6 col-md-4">
							<figure class="figure rounded bg-dark text-white p-2"
									style="width: 100%; cursor: pointer; min-height: 150px">
								<small>{이름} 회원님 생일축하합니다.</small>
							</figure>
						</div>
						<div class="col-6 col-md-4">
							<figure class="figure rounded bg-dark text-white p-2"
									style="width: 100%; cursor: pointer; min-height: 150px">
								<small>{이름} 회원님 생일축하합니다.</small>
							</figure>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<!-- 발송 진행 모달 -->
<div class="modal fade" id="sendProgressModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title">문자 발송 중</h5>
			</div>
			<div class="modal-body text-center">
				<div class="spinner-border text-primary mb-3" role="status">
					<span class="visually-hidden">발송 중...</span>
				</div>
				<p>문자를 발송하고 있습니다. 잠시만 기다려 주세요.</p>
				<div class="progress">
					<div class="progress-bar" role="progressbar" style="width: 0%"></div>
				</div>
			</div>
		</div>
	</div>
</div>


<?php include APPPATH . 'views/footer.php'; ?>
<script src="/assets/js/send_popup.js?<?php echo WB_VERSION; ?>"></script>
