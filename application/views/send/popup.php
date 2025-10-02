<?php
/**
 * 역할: 문자 발송 팝업 화면
 */
?>
<?php include APPPATH . 'views/noheader.php'; ?>
<link rel="stylesheet" href="/assets/css/send_popup.css?<?php echo WB_VERSION; ?>">
<link rel="stylesheet" href="/assets/css/custom/pqgrid.min.css?<?php echo WB_VERSION; ?>">

<div class="container send-popup">
	<div class="row">
		<div class="col-12 col-lg-6">
			<div class="card">
				<div class="card-body">
					<!-- 발송 타입 선택 -->
					<div class="mb-3">
						<div class="d-flex justify-content-between align-items-center">
							<label class="col-form-label">발송 타입</label>
							<div class="mb-2">
								<strong class="me-2" id="currentBalance">0원</strong>
								<a class="btn btn-xs btn-info" id="btnChargeModal">
									<i class="bi bi-arrow-clockwise"></i> 문자충전
								</a>
							</div>
						</div>
						<div class="btn-group p-0" role="group" id="sendTypeGroup" style="width: 100%">
							<input type="radio" class="btn-check" name="send_type" id="sms" value="sms" checked>
							<label class="btn btn-outline-primary" for="sms">SMS <small>(10원)</small></label>

							<input type="radio" class="btn-check" name="send_type" id="lms" value="lms">
							<label class="btn btn-outline-primary" for="lms">LMS <small>(20원)</small></label>

							<input type="radio" class="btn-check" name="send_type" id="mms" value="mms">
							<label class="btn btn-outline-primary" for="mms">MMS <small>(30원)</small></label>

							<input type="radio" class="btn-check" name="send_type" id="kakao" value="kakao">
							<label class="btn btn-outline-primary" for="kakao">카카오톡 <small>(20원)</small></label>
						</div>
					</div>

					<!-- 발신번호 선택 -->
					<div class="mb-3">
						<label for="senderSelect" class="col-form-label">발신번호</label>

						<div class="input-group">
							<?php
							// 인증된 발신번호만 필터링
							$verified_senders = array_filter($sender_numbers, function($sender) {
								return isset($sender['auth_status']) && $sender['auth_status'] === 'verified';
							});
							$has_verified_sender = count($verified_senders) > 0;
							?>

							<select class="form-select" id="senderSelect" name="sender_number" <?php echo !$has_verified_sender ? 'disabled' : ''; ?>>
								<?php if (!$has_verified_sender): ?>
									<option value="">인증된 발신번호가 없습니다. 발신번호 추가를 진행해주세요!</option>
								<?php else: ?>
									<option value="">발신번호를 선택하세요</option>
									<?php foreach ($verified_senders as $sender): ?>
										<option value="<?php echo $sender['sender_number']; ?>"
												data-name="<?php echo htmlspecialchars($sender['sender_name']); ?>"
											<?php echo $sender['is_default'] === 'Y' ? 'selected' : ''; ?>>
											<?php echo htmlspecialchars($sender['sender_name']); ?>
											(<?php echo $sender['sender_number']; ?>)
										</option>
									<?php endforeach; ?>
								<?php endif; ?>
							</select>
							<button class="btn btn-primary" id="btnAddSender">
								<i class="bi bi-telephone-plus"></i> 발신번호 관리
							</button>
						</div>
					</div>


					<!-- 수신자 번호 목록 -->
					<div class="mb-3">
						<label class="col-form-label">수신자</label>

						<div class="border rounded p-2">
							<div class="row">
								<div class="col-12 mb-2 d-flex justify-content-between align-items-center">
									<strong>선택된 회원 (<span id="receiverCount"><?php echo count($selected_members); ?></span>명)</strong>
									<div>
										<a class="btn btn-xs btn-danger" id="btn-remove-all"><i class="bi bi-x-square"></i> 전체삭제</a>
										<a class="btn btn-xs btn-primary" id="btn-save-addressbook"><i class="bi bi-bookmark-check"></i> 내 주소록에 저장</a>
										<a class="btn btn-xs btn-success" id="popup-edit"><i class="bi bi-pencil-square"></i> 전체편집</a>
									</div>
								</div>
							</div>

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
									<tbody id="receiverList">
									<?php if (empty($selected_members)): ?>
										<tr class="empty-message">
											<td colspan="7" class="text-center text-muted">선택된 회원이 없습니다.</td>
										</tr>
									<?php else: ?>
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
									<?php endif; ?>
									</tbody>
								</table>
							</div>
						</div>
					</div>


					

					<!-- 메시지 내용 -->
					<div class="mb-3">
						<div class="row">
							<div class="col-sm-6">
								<label for="messageContent" class="col-form-label">메시지 내용</label>
								<textarea class="form-control bg-dark text-white" id="messageContent" name="message_content" rows="4" placeholder="발송할 메시지를 입력하세요" required></textarea>
								<div class="form-text">
									<span id="charCount">0</span> / <span id="maxChar">70</span> 자
									<span id="messageTypeInfo" class="ms-3">SMS (70자 이하)</span>
								</div>
							</div>
							<div class="col-sm-6">
								<label class="col-form-label">발송 치환</label>
								<div>
									<a class="btn btn-sm btn-outline-secondary mb-1 btn-replace">이름</a>
									<a class="btn btn-sm btn-outline-secondary mb-1 btn-replace">직분</a>
									<a class="btn btn-sm btn-outline-secondary mb-1 btn-replace">연락처</a>
									<a class="btn btn-sm btn-outline-secondary mb-1 btn-replace">그룹</a>
									<a class="btn btn-sm btn-outline-secondary mb-1 btn-replace">임시1</a>
									<a class="btn btn-sm btn-outline-secondary mb-1 btn-replace">임시2</a>
								</div>
							</div>
						</div>
					</div>

					<div class="mb-3">
						<div class="row">
							<div class="col-sm-12">
								<label for="messageContent" class="col-form-label">발송시점</label>
								<div class="d-flex justify-content-between align-items-center">
									<div class="">
										<input type="radio" class="btn-check" name="send_schedule_type" id="sendNow" value="now" autocomplete="off" checked>
										<label class="btn" for="sendNow">즉시발송</label>
										<input type="radio" class="btn-check" name="send_schedule_type" id="sendScheduled" value="scheduled" autocomplete="off">
										<label class="btn" for="sendScheduled">시간지정발송</label>
									</div>
									<div class="input-group" style="width: 380px">
										<input type="datetime-local" class="form-control" id="scheduledTime" style="width: 180px" disabled >
										<select class="form-select" id="timeAddSelect" disabled >
											<option value="">시간 선택</option>
											<option value="1">1시간 후</option>
											<option value="3">3시간 후</option>
											<option value="6">6시간 후</option>
											<option value="12">12시간 후</option>
											<option value="24">24시간 후</option>
											<option value="48">48시간 후</option>
											<option value="48">다음 날 오전9시</option>
											<option value="48">다음 날 오전9시</option>
										</select>
									</div>
								</div>
							</div>
						</div>
					</div>

					<!-- MMS 첨부파일 (MMS 선택 시에만 표시) -->

					<div class="col-sm-12 d-none" id="mmsFileSection">
						<label for="mmsFile" class="col-form-label">첨부파일</label>

						<input type="file" class="form-control" id="mmsFile" name="mms_file" accept="image/*"/>
						<div class="form-text">이미지 파일만 첨부 가능합니다. (최대 300KB)</div>

					</div>
				</div>

				<div class="card-footer d-flex justify-content-between align-items-center">
					<!-- 예상 비용 (선택사항) -->
					<div class="d-flex align-items-center">
						<span  class="badge badge-sm text-bg-warning me-2">발송비용</span>
						<strong class="me-2" id="costTotal">1,530원</strong>
						<small>(153명 × 10원)</small>
					</div>
					<div>
						<button type="button" class="btn btn-secondary me-1" onclick="window.close();">취소</button>
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


							<ul class="nav nav-tabs" id="sendTab" role="tablist">
								<li class="nav-item" role="presentation">
									<button class="nav-link active" id="template-tab" data-bs-toggle="tab" data-bs-target="#template-tab-pane" type="button" role="tab" aria-controls="template-tab-pane" aria-selected="true">메시지 템플릿</button>
								</li>
								<li class="nav-item" role="presentation">
									<button class="nav-link" id="history-tab" data-bs-toggle="tab" data-bs-target="#history-tab-pane" type="button" role="tab" aria-controls="history-tab-pane" aria-selected="false">발송 히스토리</button>
								</li>
								<li class="nav-item" role="presentation">
									<button class="nav-link" id="reservation-tab" data-bs-toggle="tab" data-bs-target="#reservation-tab-pane" type="button" role="tab" aria-controls="reservation-tab-pane" aria-selected="false">예약발송 목록</button>
								</li>
								<li class="nav-item" role="presentation">
									<button class="nav-link" id="address-tab" data-bs-toggle="tab" data-bs-target="#address-tab-pane" type="button" role="tab" aria-controls="address-tab-pane" aria-selected="false">내 주소록</button>
								</li>
							</ul>
							<div class="tab-content" id="sendTabContent">
								<div class="tab-pane fade show active" id="template-tab-pane" role="tabpanel" aria-labelledby="template-tab" tabindex="0">
									<div class="d-flex justify-content-end my-2">
										<a class="btn btn-sm btn-outline-primary me-2" id="btnAddNewTemplate"><i class="bi bi-plus-lg"></i> 새 템플릿</a>
									</div>
									<div class="row" id="templateContainer">
										<!-- JavaScript로 동적 생성 -->
									</div>
								</div>
								<div class="tab-pane fade" id="history-tab-pane" role="tabpanel" aria-labelledby="history-tab" tabindex="0">
									<div class="d-flex justify-content-center">
										<div class="input-group my-2" style="width: 350px">
										<button type="button" class="btn btn-sm btn-outline-secondary" id="btnPrevMonth">
											<i class="bi bi-chevron-left"></i> 이전월
										</button>

										<select class="form-select form-select-sm" id="historyYear" >
											<!-- JavaScript로 동적 생성 -->
										</select>

										<select class="form-select form-select-sm" id="historyMonth">
											<option value="1">1월</option>
											<option value="2">2월</option>
											<option value="3">3월</option>
											<option value="4">4월</option>
											<option value="5">5월</option>
											<option value="6">6월</option>
											<option value="7">7월</option>
											<option value="8">8월</option>
											<option value="9">9월</option>
											<option value="10">10월</option>
											<option value="11">11월</option>
											<option value="12">12월</option>
										</select>

										<button type="button" class="btn btn-sm btn-outline-secondary" id="btnNextMonth">
											다음월 <i class="bi bi-chevron-right"></i>
										</button>
									</div>
									</div>
									<table class="table">
										<thead>
										<tr>
											<th scope="col">전송일시</th>
											<th scope="col">발신번호</th>
											<th scope="col">수신자</th>
											<th scope="col">결과확인</th>
										</tr>
										</thead>
										<tbody id="historyTableBody">
										<!-- JavaScript로 동적 생성 -->
										</tbody>
									</table>
								</div>
								<!-- 예약 발송 목록 탭의 버튼 수정 (205번째 줄 근처) -->
								<div class="tab-pane fade" id="reservation-tab-pane" role="tabpanel" aria-labelledby="reservation-tab" tabindex="0">
									<div class="d-flex justify-content-end my-2">
										<a class="btn btn-sm btn-outline-success" id="btnExcelReservationUpload">엑셀로 예약발송 추가</a>
									</div>
									<table class="table">
										<thead>
										<tr>
											<th scope="col">발송예정일시</th>
											<th scope="col">발신번호</th>
											<th scope="col">수신자</th>
											<th scope="col">내용확인</th>
										</tr>
										</thead>
										<tbody>
										<!-- JavaScript로 동적 생성 -->
										</tbody>
									</table>

								</div>
								<div class="tab-pane fade" id="address-tab-pane" role="tabpanel" aria-labelledby="address-tab" tabindex="0">

									<div class="d-flex justify-content-end my-2">
										<a class="btn btn-sm btn-success" id="address-download"><i class="bi bi-file-earmark-spreadsheet"></i> 엑셀 다운로드</a>
									</div>
									<table class="table">
										<thead>
										<tr>
											<th scope="col">주소록명</th>
											<th scope="col">수신자</th>
											<th scope="col">적용</th>
											<th scope="col">삭제</th>
										</tr>
										</thead>
										<tbody>
										<!-- JavaScript로 동적 생성 -->
										</tbody>
									</table>

								</div>
							</div>







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


<!-- 문자 충전 Offcanvas -->
<div class="offcanvas offcanvas-start" tabindex="-1" id="chargeOffcanvas" aria-labelledby="chargeOffcanvasLabel">
	<div class="offcanvas-header">
		<h5 class="offcanvas-title" id="chargeOffcanvasLabel">문자 충전</h5>
		<button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
	</div>
	<div class="offcanvas-body">
		<div class="mb-3">
			<label class="form-label mb-2">충전 패키지 선택</label>
			<div class="list-group" id="packageList">
				<!-- JavaScript로 동적 생성 -->
			</div>
		</div>

		<div class="mt-4 p-3 bg-light border rounded">
			<div class="d-flex justify-content-between align-items-center">
				<span class="text-muted">결제할 금액</span>
				<h4 class="mb-0 text-primary" id="selectedAmount">0원</h4>
			</div>
		</div>

		<div class="d-grid gap-2 mt-4">
			<button type="button" class="btn btn-primary" id="btnCharge">
				<i class="bi bi-credit-card"></i> 결제하기
			</button>
			<button type="button" class="btn btn-outline-secondary" data-bs-dismiss="offcanvas">취소</button>
		</div>
	</div>
</div>


<!-- 발신번호 관리 Offcanvas -->
<div class="offcanvas offcanvas-start" tabindex="-1" id="senderManageOffcanvas" aria-labelledby="senderManageOffcanvasLabel">
	<div class="offcanvas-header">
		<h5 class="offcanvas-title" id="senderManageOffcanvasLabel">발신번호 관리</h5>
		<button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
	</div>
	<div class="offcanvas-body">

		<table class="table table-hover">
			<thead>
			<tr>
				<th>이름</th>
				<th>발신번호</th>
				<th>인증</th>
				<th>삭제</th>
			</tr>
			</thead>
			<tbody id="senderTableBody">
			<!-- JavaScript로 동적 생성 -->
			</tbody>
		</table>

		<div class="d-grid gap-2 mt-4">
			<button class="btn btn-primary" id="btnAddSenderModal"><i class="bi bi-plus-lg"></i> 발신번호 추가</button>
			<button type="button" class="btn btn-outline-secondary" data-bs-dismiss="offcanvas">취소</button>
		</div>

	</div>
</div>

<!-- 발신번호 추가 모달 -->
<div class="modal fade" id="addSenderModal" tabindex="-1" aria-labelledby="addSenderModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-sm">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="addSenderModalLabel">발신번호 추가</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<div class="mb-3">
					<label for="newSenderName" class="form-label">이름</label>
					<input type="text" class="form-control" id="newSenderName" placeholder="이름을 입력하세요">
				</div>
				<div class="mb-3">
					<label for="newSenderNumber" class="form-label">발신번호</label>
					<input type="text" class="form-control" id="newSenderNumber" placeholder="발신번호를 입력하세요 (예: 010-1234-5678)">
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
				<button type="button" class="btn btn-primary" id="btnSaveSender">저장</button>
			</div>
		</div>
	</div>
</div>

<!-- 전체편집 모달 -->
<div class="modal fade" id="bulkEditModal" tabindex="-1" aria-labelledby="bulkEditModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-xl">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="bulkEditModalLabel">전체편집</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<div id="bulkEditGrid" style="height: 500px;"></div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
				<button type="button" class="btn btn-primary" id="btnSaveBulkEdit">저장</button>
			</div>
		</div>
	</div>
</div>



<!-- 발송 히스토리 상세 Offcanvas -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="historyDetailOffcanvas" aria-labelledby="historyDetailOffcanvasLabel">
	<div class="offcanvas-header">
		<h5 class="offcanvas-title" id="historyDetailOffcanvasLabel">발송 결과 상세</h5>
		<button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
	</div>
	<div class="offcanvas-body">
		<div class="mb-3">
			<label class="form-label fw-bold mb-2">발송 정보</label>
			<div class="border rounded p-3 bg-light">
				<div class="row mb-2">
					<div class="col-4 text-muted">발송일시</div>
					<div class="col-8" id="historyDetailSendDate"></div>
				</div>
				<div class="row mb-2">
					<div class="col-4 text-muted">발신번호</div>
					<div class="col-8" id="historyDetailSenderNumber"></div>
				</div>
				<div class="row mb-2">
					<div class="col-4 text-muted">발신자명</div>
					<div class="col-8" id="historyDetailSenderName"></div>
				</div>
				<div class="row mb-2">
					<div class="col-4 text-muted">발송타입</div>
					<div class="col-8" id="historyDetailSendType"></div>
				</div>
				<div class="row">
					<div class="col-4 text-muted">수신자 수</div>
					<div class="col-8" id="historyDetailReceiverCount"></div>
				</div>
			</div>
		</div>

		<div class="mb-3">
			<label class="form-label fw-bold mb-2">메시지 내용</label>
			<div class="border rounded p-3  bg-dark text-white" style="white-space: pre-wrap;" id="historyDetailMessage"></div>
		</div>

		<div class="mb-3">
			<label class="form-label fw-bold mb-2">수신자 목록</label>
			<div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
				<table class="table">
					<thead class="table-primary sticky-top">
					<tr>
						<th>이름</th>
						<th>연락처</th>
						<th>상태</th>
					</tr>
					</thead>
					<tbody id="historyDetailReceiverList">
					<!-- JavaScript로 동적 생성 -->
					</tbody>
				</table>
			</div>
		</div>

		<div class="d-grid gap-2">
			<button type="button" class="btn btn-secondary" data-bs-dismiss="offcanvas">닫기</button>
		</div>
	</div>
</div>

<!-- 예약 발송 상세 Offcanvas -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="reservationDetailOffcanvas" aria-labelledby="reservationDetailOffcanvasLabel">
	<div class="offcanvas-header">
		<h5 class="offcanvas-title" id="reservationDetailOffcanvasLabel">예약 발송 상세</h5>
		<button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
	</div>
	<div class="offcanvas-body">
		<div class="mb-3">
			<label class="form-label fw-bold mb-2">예약 정보</label>
			<div class="border rounded p-3 bg-light">
				<div class="row mb-2">
					<div class="col-4 text-muted">예약일시</div>
					<div class="col-8" id="reservationDetailScheduledTime"></div>
				</div>
				<div class="row mb-2">
					<div class="col-4 text-muted">발신번호</div>
					<div class="col-8" id="reservationDetailSenderNumber"></div>
				</div>
				<div class="row mb-2">
					<div class="col-4 text-muted">발신자명</div>
					<div class="col-8" id="reservationDetailSenderName"></div>
				</div>
				<div class="row mb-2">
					<div class="col-4 text-muted">발송타입</div>
					<div class="col-8" id="reservationDetailSendType"></div>
				</div>
				<div class="row">
					<div class="col-4 text-muted">수신자 수</div>
					<div class="col-8" id="reservationDetailReceiverCount"></div>
				</div>
			</div>
		</div>

		<div class="mb-3">
			<label class="form-label fw-bold mb-2">메시지 내용</label>
			<div class="border rounded p-3 bg-dark text-white" style="white-space: pre-wrap;" id="reservationDetailMessage"></div>
		</div>

		<div class="mb-3">
			<label class="form-label fw-bold mb-2">수신자 목록</label>
			<div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
				<table class="table">
					<thead class="table-primary sticky-top">
					<tr>
						<th>이름</th>
						<th>연락처</th>
						<th>직분</th>
						<th>그룹</th>
					</tr>
					</thead>
					<tbody id="reservationDetailReceiverList">
					<!-- JavaScript로 동적 생성 -->
					</tbody>
				</table>
			</div>
		</div>

		<div class="d-grid gap-2">
			<button type="button" class="btn btn-danger" id="btnCancelReservationDetail">예약 취소</button>
			<button type="button" class="btn btn-secondary" data-bs-dismiss="offcanvas">닫기</button>
		</div>
	</div>
</div>


<!-- 엑셀 예약발송 업로드 Offcanvas -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="excelReservationOffcanvas" aria-labelledby="excelReservationOffcanvasLabel">
	<div class="offcanvas-header">
		<h5 class="offcanvas-title" id="excelReservationOffcanvasLabel">엑셀로 예약발송 추가</h5>
		<button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
	</div>
	<div class="offcanvas-body">
		<div class="mb-3">
			<p>아래의 엑셀 서식을 다운 받아 파일을 업로드하면 예약문자 발송이 가능합니다.</p>
			<div class="d-grid gap-2 mb-3">
				<button type="button" class="btn btn-success" id="btnDownloadReservationTemplate">
					<i class="bi bi-file-earmark-spreadsheet"></i> 엑셀 서식 다운로드
				</button>
			</div>
		</div>

		<div class="mb-3">
			<label for="reservationExcelFile" class="form-label">엑셀 파일 선택</label>
			<input type="file" class="form-control" id="reservationExcelFile" accept=".csv">
			<div class="form-text">발송예정일시, 이름, 연락처, 메시지가 포함된 엑셀 파일을 업로드하세요.</div>
		</div>

		<div class="alert alert-info" role="alert">
			<small>
				<i class="bi bi-info-circle"></i> 주의사항<br>
				- 발송예정일시는 현재시간 이후여야 합니다.<br>
				- 엑셀 서식의 양식을 지켜주세요.
			</small>
		</div>

		<div class="d-grid gap-2">
			<button type="button" class="btn btn-primary" id="btnUploadReservationExcel">
				<i class="bi bi-upload"></i> 저장
			</button>
			<button type="button" class="btn btn-secondary" data-bs-dismiss="offcanvas">닫기</button>
		</div>
	</div>
</div>

<?php include APPPATH . 'views/nofooter.php'; ?>
<script src="/assets/js/custom/pqgrid.min.js?<?php echo WB_VERSION; ?>"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script>
	const SEND_ORG_ID = '<?php echo $org_id; ?>';
</script>
<script src="/assets/js/send_popup.js?<?php echo WB_VERSION; ?>"></script>
