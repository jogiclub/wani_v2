<head>
	<?php $this->load->view('header'); ?>
</head>
<body>

<div class="container pt-2 pb-2">
	<nav class="mb-3" aria-label="breadcrumb">
		<ol class="breadcrumb mb-0">
			<li class="breadcrumb-item"><a href="#!">홈</a></li>
			<li class="breadcrumb-item"><a href="#!">SETTING</a></li>
			<li class="breadcrumb-item active">조직설정</li>
		</ol>
	</nav>
	<div class="row align-items-center justify-content-between g-3 mb-4">
		<h3 class="page-title col-6 my-1">조직설정</h3>
		<div class="col-6 my-1">
			<div class="text-end" role="group" aria-label="Basic example">

				<form id="orgSettingForm">

					<button type="submit" class="btn btn-primary">
						<i class="bi bi-check-circle"></i> 저장
					</button>
					<button type="button" class="btn btn-secondary" id="resetForm">
						<i class="bi bi-arrow-clockwise"></i> 초기화
					</button>


			</div>
		</div>
	</div>

	<?php if (isset($selected_org_detail) && $selected_org_detail): ?>
		<div class="row">
			<div class="col-lg-7">
				<div class="card">
					<div class="card-header">
						<h5 class="card-title mb-0">
							<i class="bi bi-people-fill"></i> <?php echo htmlspecialchars($selected_org_detail['org_name']); ?>
							정보 수정
						</h5>
					</div>
					<div class="card-body">

						<input type="hidden" id="org_id" name="org_id"
							   value="<?php echo $selected_org_detail['org_id']; ?>">

						<!-- 조직 아이콘 -->
						<div class="row mb-4">
							<div class="col-12">
								<label class="form-label">조직 아이콘</label>
								<div class="d-flex align-items-center gap-3">
									<div class="org-icon-preview">
										<?php if ($selected_org_detail['org_icon']): ?>
											<img src="<?php echo $selected_org_detail['org_icon']; ?>"
												 alt="조직 아이콘"
												 class="circle"
												 width="100"
												 height="100"
												 style="object-fit: cover; border: 1px solid #ddd;"
												 id="iconPreview">
										<?php else: ?>
											<div
												class="bg-light border circle d-flex align-items-center justify-content-center"
												style="width: 100px; height: 100px;"
												id="iconPreview">
												<i class="bi bi-image text-muted fs-1"></i>
											</div>
										<?php endif; ?>
									</div>
									<div>
										<div class="input-group">
											<input type="file" class="form-control" id="orgIconFile"
												   accept=".jpg,.jpeg,.png">
											<button type="button" class="btn btn-primary btn-sm" id="uploadIconBtn">
												<i class="bi bi-upload"></i> 아이콘 업로드
											</button>
										</div>
										<div class="form-text d-block">
											* JPG 또는 PNG 파일만 가능합니다.<br> * 100x100 픽셀 크기를 권장합니다.
										</div>
									</div>

								</div>

							</div>
						</div>

						<div class="row mb-3">
							<div class="col-md-6">
								<label for="org_name" class="form-label">조직명</label>
								<input type="text" class="form-control" id="org_name" name="org_name"
									   value="<?php echo htmlspecialchars($selected_org_detail['org_name']); ?>"
									   required>
							</div>
							<div class="col-md-6">
								<label for="org_type" class="form-label">조직 유형</label>
								<select class="form-select" id="org_type" name="org_type" required>
									<option
										value="church" <?php echo ($selected_org_detail['org_type'] == 'church') ? 'selected' : ''; ?>>
										교회
									</option>
									<option
										value="school" <?php echo ($selected_org_detail['org_type'] == 'school') ? 'selected' : ''; ?>>
										학교
									</option>
									<option
										value="company" <?php echo ($selected_org_detail['org_type'] == 'company') ? 'selected' : ''; ?>>
										회사
									</option>
									<option
										value="club" <?php echo ($selected_org_detail['org_type'] == 'club') ? 'selected' : ''; ?>>
										동아리
									</option>
									<option
										value="community" <?php echo ($selected_org_detail['org_type'] == 'community') ? 'selected' : ''; ?>>
										커뮤니티
									</option>
									<option
										value="other" <?php echo ($selected_org_detail['org_type'] == 'other') ? 'selected' : ''; ?>>
										기타
									</option>
								</select>
							</div>
						</div>

						<div class="mb-3">
							<label for="org_desc" class="form-label">조직 설명</label>
							<textarea class="form-control" id="org_desc" name="org_desc" rows="3"
									  placeholder="조직에 대한 설명을 입력하세요..."><?php echo htmlspecialchars($selected_org_detail['org_desc'] ?? ''); ?></textarea>
						</div>

						<div class="row mb-3">
							<div class="col-md-6">
								<label for="leader_name" class="form-label">리더 호칭</label>
								<input type="text" class="form-control" id="leader_name" name="leader_name"
									   value="<?php echo htmlspecialchars($selected_org_detail['leader_name']); ?>"
									   placeholder="예: 리더, 팀장, 회장">
							</div>
							<div class="col-md-6">
								<label for="new_name" class="form-label">신규 회원 호칭</label>
								<input type="text" class="form-control" id="new_name" name="new_name"
									   value="<?php echo htmlspecialchars($selected_org_detail['new_name']); ?>"
									   placeholder="예: 새가족, 신입생, 신입사원">
							</div>
						</div>

						<!-- 최고관리자 정보 -->
						<?php if (isset($org_admin) && $org_admin): ?>
							<div class="row mb-3">
								<div class="col-12">
									<label class="form-label">최고관리자</label>
									<div class="card border-light bg-light">
										<div class="card-body py-2">
											<div class="d-flex align-items-center justify-content-between">
												<div class="d-flex align-items-center gap-3">
													<img
														src="<?php echo $org_admin['user_profile_image'] ?: '/assets/images/photo_no.png'; ?>"
														alt="프로필"
														class="rounded-circle"
														width="40"
														height="40"
														style="object-fit: cover;">
													<div>
														<div
															class="fw-bold"><?php echo htmlspecialchars($org_admin['user_name']); ?></div>
														<small
															class="text-muted"><?php echo htmlspecialchars($org_admin['user_mail']); ?></small>
													</div>
												</div>
												<?php if ($this->session->userdata('user_id') == $org_admin['user_id'] || $this->session->userdata('master_yn') == 'Y'): ?>
													<button type="button" class="btn btn-outline-warning btn-sm"
															id="delegateAdminBtn">
														<i class="bi bi-person-check"></i> 위임
													</button>
												<?php endif; ?>
											</div>
										</div>
									</div>
								</div>
							</div>
						<?php endif; ?>

						<div class="row mb-3">
							<div class="col-md-6">
								<label class="form-label">초대 코드</label>
								<div class="input-group">
									<input type="text" class="form-control" readonly
										   value="<?php echo htmlspecialchars($selected_org_detail['invite_code']); ?>">
									<button class="btn btn-outline-secondary" type="button" id="copyInviteCode">
										<i class="bi bi-clipboard"></i> 복사
									</button>
								</div>
								<div class="form-text">* 다른 사용자가 이 조직에 참여할 때 사용하는 코드입니다.</div>
							</div>
							<div class="col-md-6">
								<label class="form-label">생성일</label>
								<input type="text" class="form-control" readonly
									   value="<?php echo date('Y-m-d H:i', strtotime($selected_org_detail['regi_date'])); ?>">
							</div>
						</div>


						</form>
					</div>
				</div>
			</div>

			<div class="col-lg-5">
				<div class="card">
					<div class="card-header">

						<h5 class="card-title mb-0 "><i class="bi bi-person-lines-fill"></i> 조직 목록</h5>


					</div>
					<div class="card-body">
						<?php if (isset($orgs) && !empty($orgs)): ?>
							<div class="list-group">
								<?php foreach ($orgs as $org): ?>
									<a href="#" class="list-group-item list-group-item-action org-selector-item
                                   <?php echo (isset($selected_org_detail) && $selected_org_detail['org_id'] == $org['org_id']) ? 'active' : ''; ?>"
									   data-org-id="<?php echo $org['org_id']; ?>"
									   data-org-name="<?php echo htmlspecialchars($org['org_name']); ?>">
										<div class="d-flex w-100 justify-content-between align-items-center">
											<div class="d-flex align-items-center gap-2">
												<?php if ($org['org_icon']): ?>
													<img src="<?php echo $org['org_icon']; ?>" alt="아이콘" class="circle"
														 width="40" height="40" style="object-fit: cover;">
												<?php else: ?>
													<div
														class="bg-warning circle d-flex align-items-center justify-content-center"
														style="width: 40px; height: 40px;">
														<i class="bi bi-building-fill text-white"
														   style="font-size: 20px;"></i>

													</div>
												<?php endif; ?>
												<div>
													<h6 class="mb-0"><?php echo htmlspecialchars($org['org_name']); ?></h6>
												</div>
											</div>
											<div class="text-end">
												<small class="text-muted">
                                                        <?php
														$type_names = array(
															'church' => '교회',
															'school' => '학교',
															'company' => '회사',
															'club' => '동아리',
															'community' => '커뮤니티',
															'other' => '기타'
														);
														echo $type_names[$org['org_type']] ?? $org['org_type'];
														?>
                                                    </small>

												<small class="text-muted">(<?php echo number_format($org['member_count']); ?>
													명)</small>
												<small class="text-muted">
													<?php echo ($org['user_level'] >= 9) ? '최고관리자' : '일반'; ?>
												</small>
											</div>
										</div>
									</a>
								<?php endforeach; ?>
							</div>
						<?php else: ?>
							<p class="text-muted">접근 가능한 조직이 없습니다.</p>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</div>
	<?php else: ?>
		<div class="alert alert-warning">
			선택된 조직이 없습니다. 조직을 선택해주세요.
		</div>
	<?php endif; ?>
</div>

<!-- 관리자 위임 모달 -->
<div class="modal fade" id="delegateModal" tabindex="-1" aria-labelledby="delegateModalLabel" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="delegateModalLabel">관리자 권한 위임</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<div class="alert alert-warning">
					<i class="bi bi-exclamation-triangle"></i>
					<strong>주의:</strong> 관리자 권한을 위임하면 귀하의 권한은 일반 관리자로 변경됩니다.
				</div>
				<form id="delegateForm">
					<div class="mb-3">
						<label for="delegate_email" class="form-label">위임받을 사용자의 이메일</label>
						<input type="email" class="form-control" id="delegate_email" name="delegate_email"
							   placeholder="example@domain.com" required>
						<div class="form-text">해당 사용자는 이미 이 조직의 멤버여야 합니다.</div>
					</div>
				</form>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
				<button type="button" class="btn btn-warning" id="confirmDelegateBtn">위임하기</button>
			</div>
		</div>
	</div>
</div>

<!-- Toast 알림 -->
<div class="toast-container position-fixed bottom-0 end-0 p-3">
	<div id="orgToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
		<div class="toast-header">
			<strong class="me-auto">알림</strong>
			<button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
		</div>
		<div class="toast-body"></div>
	</div>
</div>


<?php $this->load->view('footer'); ?>
<script src="/assets/js/org_setting.js?<?php echo date('Ymdhis'); ?>"></script>

