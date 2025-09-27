<?php
/**
 * 파일 위치: application/views/send/popup.php
 * 역할: 문자 발송 팝업 화면
 */
?>
<?php include APPPATH . 'views/noheader.php'; ?>

	<div class="container-fluid">
		<div class="row">
			<div class="col-12">
				<div class="card">
					<div class="card-header">
						<h5 class="card-title mb-0">
							<i class="bi bi-chat-dots"></i> 문자 발송
						</h5>
					</div>
					<div class="card-body">
						<!-- 발송 타입 선택 -->
						<div class="row mb-3">
							<label class="col-sm-3 col-form-label">발송 타입</label>
							<div class="col-sm-9">
								<div class="btn-group w-100" role="group" id="sendTypeGroup">
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
						</div>

						<!-- 발신번호 선택 -->
						<div class="row mb-3">
							<label for="senderSelect" class="col-sm-3 col-form-label">발신번호</label>
							<div class="col-sm-9">
								<select class="form-select" id="senderSelect" name="sender_number">
									<option value="">발신번호를 선택하세요</option>
									<?php foreach ($sender_numbers as $sender): ?>
										<option value="<?php echo $sender['sender_number']; ?>"
												data-name="<?php echo htmlspecialchars($sender['sender_name']); ?>"
											<?php echo $sender['is_default'] === 'Y' ? 'selected' : ''; ?>>
											<?php echo htmlspecialchars($sender['sender_name']); ?> (<?php echo $sender['sender_number']; ?>)
										</option>
									<?php endforeach; ?>
								</select>
							</div>
						</div>

						<!-- 수신번호 목록 -->
						<div class="row mb-3">
							<label class="col-sm-3 col-form-label">수신자</label>
							<div class="col-sm-9">
								<div class="border rounded p-3" style="max-height: 200px; overflow-y: auto;">
									<div class="row">
										<div class="col-12 mb-2">
											<strong>선택된 회원 (<?php echo count($selected_members); ?>명)</strong>
										</div>
									</div>
									<?php if (empty($selected_members)): ?>
										<div class="text-muted">선택된 회원이 없습니다.</div>
									<?php else: ?>
										<div id="receiverList">
											<?php foreach ($selected_members as $member): ?>
												<div class="receiver-item mb-2 p-2 bg-light rounded"
													 data-member-idx="<?php echo $member['member_idx']; ?>"
													 data-phone="<?php echo $member['member_phone']; ?>"
													 data-name="<?php echo htmlspecialchars($member['member_name']); ?>">
													<div class="d-flex justify-content-between align-items-center">
														<div>
															<strong><?php echo htmlspecialchars($member['member_name']); ?></strong>
															<span class="text-muted">(<?php echo $member['member_phone']; ?>)</span>
															<?php if (!empty($member['area_name'])): ?>
																<small class="text-secondary">- <?php echo htmlspecialchars($member['area_name']); ?></small>
															<?php endif; ?>
														</div>
														<button type="button" class="btn btn-sm btn-outline-danger remove-receiver">
															<i class="bi bi-x"></i>
														</button>
													</div>
												</div>
											<?php endforeach; ?>
										</div>
									<?php endif; ?>
								</div>
							</div>
						</div>

						<!-- 메시지 템플릿 선택 -->
						<div class="row mb-3">
							<label for="templateSelect" class="col-sm-3 col-form-label">메시지 템플릿</label>
							<div class="col-sm-9">
								<select class="form-select" id="templateSelect">
									<option value="">직접 입력 또는 템플릿 선택</option>
									<?php foreach ($message_templates as $template): ?>
										<option value="<?php echo htmlspecialchars($template['template_content']); ?>">
											<?php echo htmlspecialchars($template['template_name']); ?>
										</option>
									<?php endforeach; ?>
								</select>
							</div>
						</div>

						<!-- 메시지 내용 -->
						<div class="row mb-3">
							<label for="messageContent" class="col-sm-3 col-form-label">메시지 내용</label>
							<div class="col-sm-9">
                            <textarea class="form-control" id="messageContent" name="message_content"
									  rows="6" placeholder="전송할 메시지를 입력하세요" required></textarea>
								<div class="form-text">
									<span id="charCount">0</span> / <span id="maxChar">90</span> 자
									<span id="messageTypeInfo" class="ms-3">SMS (90자 이하)</span>
								</div>
							</div>
						</div>

						<!-- MMS 첨부파일 (MMS 선택 시에만 표시) -->
						<div class="row mb-3 d-none" id="mmsFileSection">
							<label for="mmsFile" class="col-sm-3 col-form-label">첨부파일</label>
							<div class="col-sm-9">
								<input type="file" class="form-control" id="mmsFile" name="mms_file"
									   accept="image/*" />
								<div class="form-text">이미지 파일만 첨부 가능합니다. (최대 300KB)</div>
							</div>
						</div>

						<!-- 예상 비용 (선택사항) -->
						<div class="row mb-3">
							<label class="col-sm-3 col-form-label">예상 비용</label>
							<div class="col-sm-9">
								<div class="alert alert-info">
									<i class="bi bi-info-circle"></i>
									<span id="costInfo">SMS: 약 원 (명 × 원)</span>
								</div>
							</div>
						</div>
					</div>

					<div class="card-footer text-end">
						<button type="button" class="btn btn-secondary me-2" onclick="window.close();">취소</button>
						<button type="button" class="btn btn-primary" id="sendBtn">
							<i class="bi bi-send"></i> 발송하기
						</button>
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
