
<?php $this->load->view('header'); ?>
<!-- Select2 CSS 추가 -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />


<div class="container pt-2 pb-2">
	<nav class="mb-1" aria-label="breadcrumb">
		<ol class="breadcrumb mb-0">
			<li class="breadcrumb-item"><a href="#!">홈</a></li>
			<li class="breadcrumb-item"><a href="#!">SETTING</a></li>
			<li class="breadcrumb-item active">조직설정</li>
		</ol>
	</nav>

	<form id="orgSettingForm">

		<div class="col-12 my-1 d-flex align-items-center justify-content-between mb-3">
			<h3 class="page-title mb-0">조직설정</h3>
			<div class="col-6 my-1">
				<div class="text-end" role="group" aria-label="Basic example">
					<button type="submit" class="btn btn-primary">
						<i class="bi bi-check-circle"></i> 저장
					</button>
				</div>
			</div>
		</div>

		<?php if (isset($selected_org_detail) && $selected_org_detail): ?>
			<div class="row">
				<div class="col-lg-4">
					<div class="card">
						<div class="card-header">
							<h5 class="card-title mb-0">								
								기본 정보
							</h5>
						</div>
						<div class="card-body card-height">

							<input type="hidden" id="org_id" name="org_id" value="<?php echo $selected_org_detail['org_id']; ?>">

							<!-- 조직 아이콘 -->
							<div class="row mb-4">
								<div class="col-12">
									<label class="form-label">조직 아이콘</label>
									<div class="d-flex align-items-center gap-3">
										<div class="row">
										<div class="org-icon-preview col-md-12">
											<?php if ($selected_org_detail['org_icon']): ?>
												<img src="<?php echo $selected_org_detail['org_icon']; ?>" alt="조직 아이콘" class="circle" width="100" height="100" style="object-fit: cover; border: 1px solid #ddd;" id="iconPreview">
											<?php else: ?>
												<div class="bg-light border circle d-flex align-items-center justify-content-center" style="width: 100px; height: 100px;" id="iconPreview">
													<i class="bi bi-image text-muted fs-1"></i>
												</div>
											<?php endif; ?>
										</div>
										<div class="col-md-12 mt-3">
											<div class="input-group">
												<input type="file" class="form-control" id="orgIconFile" accept=".jpg,.jpeg,.png">
												<button type="button" class="btn btn-primary btn-sm" id="uploadIconBtn"><i class="bi bi-upload"></i> 아이콘 업로드</button>
											</div>
											<div class="form-text d-block">
												* JPG 또는 PNG 파일만 가능합니다.<br> * 100x100 픽셀 크기를 권장합니다.
											</div>
										</div>
										</div>
									</div>

								</div>
							</div>

							<!-- 조직 아이콘 섹션 다음에 추가 -->
							<!-- 조직 직인 -->
							<div class="row mb-4">
								<div class="col-12">
									<label class="form-label">조직 직인</label>
									<div class="d-flex align-items-center gap-3">
										<div class="row">
											<div class="org-seal-preview col-md-12">
												<?php if (!empty($selected_org_detail['org_seal'])): ?>
													<img src="<?php echo $selected_org_detail['org_seal']; ?>" alt="조직 직인" class="circle" width="100" height="100" style="object-fit: cover; border: 1px solid #ddd;" id="sealPreview">
												<?php else: ?>
													<div class="bg-light border circle d-flex align-items-center justify-content-center" style="width: 100px; height: 100px;" id="sealPreview">
														<i class="bi bi-image text-muted fs-1"></i>
													</div>
												<?php endif; ?>
											</div>
											<div class="col-md-12 mt-3">
												<div class="input-group">
													<input type="file" class="form-control" id="orgSealFile" accept=".jpg,.jpeg,.png">
													<button type="button" class="btn btn-primary btn-sm" id="uploadSealBtn"><i class="bi bi-upload"></i> 직인 업로드</button>
												</div>
												<div class="form-text d-block">
													* JPG 또는 PNG 파일만 가능합니다.<br> * 100x100 픽셀 크기를 권장합니다.
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>

							<div class="row mb-3">
								<div class="col-md-6">
									<label for="org_name" class="form-label">조직명</label>
									<input type="text" class="form-control" id="org_name" name="org_name" value="<?php echo htmlspecialchars($selected_org_detail['org_name']); ?>" required>
								</div>
								<div class="col-md-6">
									<label for="org_type" class="form-label">조직 유형</label>
									<select class="form-select" id="org_type" name="org_type" required>
										<option value="church" <?php echo ($selected_org_detail['org_type'] == 'church') ? 'selected' : ''; ?>>교회</option>
										<option value="school" <?php echo ($selected_org_detail['org_type'] == 'school') ? 'selected' : ''; ?>>학교</option>
										<option value="company" <?php echo ($selected_org_detail['org_type'] == 'company') ? 'selected' : ''; ?>>회사</option>
										<option value="club" <?php echo ($selected_org_detail['org_type'] == 'club') ? 'selected' : ''; ?>>동아리</option>
										<option value="community" <?php echo ($selected_org_detail['org_type'] == 'community') ? 'selected' : ''; ?>>커뮤니티</option>
										<option value="other" <?php echo ($selected_org_detail['org_type'] == 'other') ? 'selected' : ''; ?>>기타</option>
									</select>
								</div>
							</div>

							<div class="mb-3">
								<label for="org_rep" class="form-label">조직장</label>
								<input type="text" class="form-control" id="org_rep" name="org_rep" value="<?php echo htmlspecialchars($selected_org_detail['org_rep'] ?? ''); ?>" placeholder="조직장 이름을 입력하세요">
							</div>

							<div class="mb-3">
								<label for="org_desc" class="form-label">조직 설명</label>
								<textarea class="form-control" id="org_desc" name="org_desc" rows="3" placeholder="조직에 대한 설명을 입력하세요..."><?php echo htmlspecialchars($selected_org_detail['org_desc'] ?? ''); ?></textarea>
							</div>

							<!-- 최고관리자 정보 -->
							<?php if (isset($org_admin) && $org_admin): ?>
								<div class="row mb-3">
									<div class="col-12">
										<label class="form-label">최고관리자</label>
										<div class="box border-light bg-light">
											<div class="box-body p-3">
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
								<div class="col-md-12">
									<label class="form-label">초대 코드</label>
									<div class="input-group">
										<input type="text" class="form-control" id="inviteCodeInput" readonly
											   value="<?php echo htmlspecialchars($selected_org_detail['invite_code']); ?>">
										<button class="btn btn-outline-secondary" type="button" id="copyInviteCode">
											<i class="bi bi-clipboard"></i> 복사
										</button>
										<button class="btn btn-outline-primary" type="button" id="refreshInviteCode">
											<i class="bi bi-arrow-clockwise"></i> 코드갱신
										</button>
									</div>
									<div class="form-text">* 다른 사용자가 이 조직에 참여할 때 사용하는 코드입니다.<br/>
									* 보안을 위해 해당 코드는 자주 갱신해주세요!
									</div>
								</div>
								<div class="col-md-12 mt-3">
									<label class="form-label">생성일</label>
									<input type="text" class="form-control" readonly
										   value="<?php echo date('Y-m-d H:i', strtotime($selected_org_detail['regi_date'])); ?>">
								</div>
							</div>


						</div>
					</div>
				</div>
				<div class="col-lg-4 mt-4 mt-md-0">
					<div class="card">
						<div class="card-header">
							<h5 class="card-title mb-0">
								호칭설정
							</h5>
						</div>
						<div class="card-body card-height">
							<!-- 조직 아이콘 -->
							<div class="row mb-3">
								<div class="col-md-6">
									<label for="leader_name" class="form-label">리더 호칭</label>
									<input type="text" class="form-control" id="leader_name" name="leader_name" value="<?php echo htmlspecialchars($selected_org_detail['leader_name']); ?>" placeholder="예: 리더, 팀장, 회장">
								</div>
								<div class="col-md-6">
									<label for="new_name" class="form-label">신규 회원 호칭</label>
									<input type="text" class="form-control" id="new_name" name="new_name" value="<?php echo htmlspecialchars($selected_org_detail['new_name']); ?>" placeholder="예: 새가족, 신입생, 신입사원">
								</div>
							</div>

							<!-- 직위/직분, 직책, 타임라인 설정 추가 -->
							<div class="row mb-3">
								<div class="col-md-12 mb-3">
									<label for="position_names" class="form-label">직위/직분 호칭</label><small class="text-warning"> * 쉼표로 구분</small>
									<select class="form-select" id="position_names" name="position_names[]" multiple>
										<!-- JavaScript로 기존 데이터 로드 -->
									</select>
									<div class="form-text">예: 이병, 병장, 대리, 과장, 장로, 권사, 집사 등</div>
								</div>
								<div class="col-md-12 mb-3">
									<label for="duty_names" class="form-label">직책(그룹직책) 호칭</label><small class="text-warning"> * 쉼표로 구분</small>
									<select class="form-select" id="duty_names" name="duty_names[]" multiple>
										<!-- JavaScript로 기존 데이터 로드 -->
									</select>
									<div class="form-text">예: 반장, 회장, 조장, 팀장, 총무 등</div>
								</div>
								<div class="col-md-12 mb-3">
									<label for="timeline_names" class="form-label">타임라인 호칭</label><small class="text-warning"> * 쉼표로 구분</small>
									<select class="form-select" id="timeline_names" name="timeline_names[]" multiple>
										<!-- JavaScript로 기존 데이터 로드 -->
									</select>
									<div class="form-text">예: 입대, 진급, 전역, 파송, 입교, 세례, 결혼, 직분임명 등</div>
								</div>
								<div class="col-md-12 mb-3">
									<label for="memos_names" class="form-label">메모 호칭</label><small class="text-warning"> * 쉼표로 구분</small>
									<select class="form-select" id="memos_names" name="memos_names[]" multiple>
										<!-- JavaScript로 기존 데이터 로드 -->
									</select>
									<div class="form-text">예: 심방메모, 출석메모, 생활메모, 기도제목 등</div>
								</div>
							</div>





						</div>
					</div>
				</div>
				<div class="col-lg-4 mt-4 mt-md-0">
					<div class="card">
						<div class="card-header">
							<h5 class="card-title mb-0">
								알림메시지 설정
							</h5>
						</div>
						<div class="card-body card-height">



							<div class="alert alert-warning d-flex align-items-center" role="alert">
								<i class="bi bi-info-square-fill me-2"></i>

								<div>
									미분류 회원들은 대상에서 제외됩니다.
								</div>
							</div>


							<div class="d-flex justify-content-between align-items-center mb-3">
								<div class="form-check form-switch ">
									<input class="form-check-input me-1" type="checkbox" role="switch" id="switchCheck-birth7">
									<label class="form-check-label" for="switchCheck-birth7">7일 이내 생일인 회원 알림</label>
								</div>
								<small class="text-danger">매주 월요일 오전 2시</small>
							</div>

							<div class="d-flex justify-content-between align-items-center mb-3">
								<div class="form-check form-switch ">
									<input class="form-check-input me-1" type="checkbox" role="switch" id="switchCheck-birthToday">
									<label class="form-check-label" for="switchCheck-birthToday">오늘 생일인 회원 알림</label>
								</div>
								<small class="text-danger">매일 오전 2시 30분</small>
							</div>

							<div class="d-flex justify-content-between align-items-center mb-3">
								<div class="form-check form-switch ">
									<input class="form-check-input me-1" type="checkbox" role="switch" id="switchCheck-promotion">
									<label class="form-check-label" for="switchCheck-promotion">입대일 기준 진급 대상자 알림</label>
								</div>
								<small class="text-danger">매일 오전 3시</small>
							</div>

							<div class="d-flex justify-content-between align-items-center mb-3">
								<div class="form-check form-switch ">
									<input class="form-check-input me-1" type="checkbox" role="switch" id="switchCheck-Absence1Week">
									<label class="form-check-label" for="switchCheck-Absence1Week">금주 미출석 회원 알림</label>
								</div>
								<small class="text-danger">매주 일요일 오후 12시</small>
							</div>

							<div class="d-flex justify-content-between align-items-center mb-3">
								<div class="form-check form-switch ">
									<input class="form-check-input me-1" type="checkbox" role="switch" id="switchCheck-Absence2Week">
									<label class="form-check-label" for="switchCheck-Absence2Week">2주간 미출석 회원 알림</label>
								</div>
								<small class="text-danger">매주 일요일 오후 12시</small>
							</div>


							<div class="d-flex justify-content-between align-items-center mb-3">
								<div class="form-check form-switch ">
									<input class="form-check-input me-1" type="checkbox" role="switch" id="switchCheck-Absence5Week">
									<label class="form-check-label" for="switchCheck-Absence5Week">5주간 미출석 회원 알림</label>
								</div>
								<small class="text-danger">매주 일요일 오후 12시</small>
							</div>


						</div>
					</div>
				</div>
			</div>
		<?php endif; ?>
	</form>
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
						<div class="form-text">해당 사용자는 이미 이 조직의 회원여야 합니다.</div>
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



<?php $this->load->view('footer'); ?>
<script>
	/*출석관리 메뉴 active*/
	$('.menu-41').addClass('active');
</script>

<!-- Select2 JS 추가 -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="/assets/js/custom/select2.sortable.min.js?<?php echo WB_VERSION; ?>"></script>
<script src="/assets/js/org_setting.js?<?php echo WB_VERSION; ?>"></script>


