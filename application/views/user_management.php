<?php
// application/views/user_management.php
// 사용자 관리 화면 - Bootstrap5.3 기반 사용자 목록 및 관리 UI

$this->load->view('header'); ?>

<!-- Select2 CSS 추가 -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

<div class="container pt-2 pb-2">
	<nav class="mb-3" aria-label="breadcrumb">
		<ol class="breadcrumb mb-0">
			<li class="breadcrumb-item"><a href="#!">홈</a></li>
			<li class="breadcrumb-item"><a href="#!">관리</a></li>
			<li class="breadcrumb-item active">사용자관리</li>
		</ol>
	</nav>

	<div class="row align-items-center justify-content-between g-3 mb-4">
		<h3 class="page-title my-1">사용자관리</h3>

	</div>

	<?php if (isset($selected_org_detail) && $selected_org_detail): ?>
		<div class="row">
			<div class="col-lg-12">
				<div class="card">
					<div class="card-header">
						<div class="row">
							<h5 class="card-title col-6 mb-0 d-flex align-items-center">
								<i class="bi bi-people"></i> <?php echo htmlspecialchars($selected_org_detail['org_name']); ?>
								사용자 목록
							</h5>
							<!-- 선택 권한 수정 버튼 -->
							<div class="col-6 d-flex justify-content-end align-items-center">
								<div class="me-3"><small class="text-muted ">선택된 사용자 <span id="selectedCount">0</span>명</small></div>
								<button type="button" class="btn btn-outline-primary me-3" id="bulkEditBtn" disabled><i class="bi bi-pencil-square"></i> 선택 권한 수정</button>
								<button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#inviteUserModal"><i class="bi bi-person-plus"></i> 사용자 초대</button>
							</div>
						</div>
					</div>
					<div class="card-body">

						<?php if (isset($org_users) && !empty($org_users)): ?>


							<div class="table-responsive">
								<table class="table align-middle">
									<thead>
									<tr>
										<th style="width: 40px;">
											<input type="checkbox" class="form-check-input" id="selectAllUsers">
										</th>
										<th style="width: 60px;">프로필</th>
										<th style="width: 120px;">이름</th>
										<th style="width: 180px;">이메일</th>
										<th style="width: 140px;">연락처</th>
										<th style="width: 80px;">권한</th>
										<th>관리메뉴</th>
										<th>관리그룹</th>
										<th style="width: 140px;">관리</th>
									</tr>
									</thead>
									<tbody>
									<?php foreach ($org_users as $user): ?>
										<tr>
											<td>
												<input type="checkbox" class="form-check-input user-checkbox"
													   value="<?php echo $user['user_id']; ?>"
													   data-user-name="<?php echo htmlspecialchars($user['user_name']); ?>">
											</td>
											<td class="text-center">
												<?php if (!empty($user['user_profile_image'])): ?>
													<img src="<?php echo $user['user_profile_image']; ?>"
														 class="rounded-circle"
														 style="width: 40px; height: 40px; object-fit: cover;"
														 alt="프로필">
												<?php else: ?>
													<div class="rounded-circle bg-light d-flex align-items-center justify-content-center"
														 style="width: 40px; height: 40px;">
														<i class="bi bi-person text-muted"></i>
													</div>
												<?php endif; ?>
											</td>
											<td>
												<strong><?php echo htmlspecialchars($user['user_name']); ?></strong>
												<?php if ($user['master_yn'] === 'Y'): ?>
													<br><small class="text-primary">마스터</small>
												<?php endif; ?>
											</td>
											<td>
												<span><?php echo htmlspecialchars($user['user_mail']); ?></span>
											</td>
											<td>
												<span><?php echo htmlspecialchars($user['user_hp']); ?></span>
											</td>
											<td>
						<span class="badge bg-<?php
						if ($user['level'] >= 10) echo 'danger';
						elseif ($user['level'] >= 9) echo 'warning';
						else echo 'secondary';
						?>">
							<?php
							if ($user['level'] >= 10) echo '최고관리자';
							elseif ($user['level'] >= 9) echo '관리자';
							else echo '일반(' . $user['level'] . ')';
							?>
						</span>
											</td>
											<td>
												<?php if (!empty($user['managed_menus_display'])): ?>
													<?php foreach (array_slice($user['managed_menus_display'], 0, 2) as $menu): ?>
														<span class="badge bg-info text-dark me-1 mb-1"><?php echo htmlspecialchars($menu); ?></span>
													<?php endforeach; ?>
													<?php if (count($user['managed_menus_display']) > 2): ?>
														<small class="text-muted">외 <?php echo count($user['managed_menus_display']) - 2; ?>개</small>
													<?php endif; ?>
												<?php else: ?>
													<span class="text-muted">-</span>
												<?php endif; ?>
											</td>
											<td>
												<?php if (!empty($user['managed_areas_display'])): ?>
													<?php foreach (array_slice($user['managed_areas_display'], 0, 2) as $area): ?>
														<span class="badge bg-success me-1 mb-1"><?php echo htmlspecialchars($area); ?></span>
													<?php endforeach; ?>
													<?php if (count($user['managed_areas_display']) > 2): ?>
														<small class="text-muted">외 <?php echo count($user['managed_areas_display']) - 2; ?>개</small>
													<?php endif; ?>
												<?php else: ?>
													<span class="text-muted">-</span>
												<?php endif; ?>
											</td>
											<td>
												<button type="button" class="btn btn-sm btn-outline-primary edit-user-btn"
														data-user-id="<?php echo $user['user_id']; ?>"
														data-user-name="<?php echo htmlspecialchars($user['user_name']); ?>"
														data-user-mail="<?php echo htmlspecialchars($user['user_mail']); ?>"
														data-user-hp="<?php echo htmlspecialchars($user['user_hp']); ?>"
														data-user-level="<?php echo $user['level']; ?>"
														data-org-id="<?php echo $user['org_id']; ?>">
													<i class="bi bi-pencil"></i>
												</button>
												<?php if ($user['master_yn'] !== 'Y'): ?>
													<button type="button" class="btn btn-sm btn-outline-danger delete-user-btn"
															data-user-id="<?php echo $user['user_id']; ?>"
															data-user-name="<?php echo htmlspecialchars($user['user_name']); ?>"
															data-org-id="<?php echo $user['org_id']; ?>">
														<i class="bi bi-trash"></i>
													</button>
												<?php endif; ?>
												<?php if ($this->session->userdata('master_yn') === 'Y' && $user['user_id'] !== $this->session->userdata('user_id')): ?>
													<button type="button"
															class="btn btn-sm btn-outline-info login-as-user-btn"
															data-user-id="<?php echo $user['user_id']; ?>"
															data-user-name="<?php echo htmlspecialchars($user['user_name']); ?>"
															title="<?php echo htmlspecialchars($user['user_name']); ?>님으로 로그인">
														<i class="bi bi-box-arrow-in-right"></i>
													</button>
												<?php endif; ?>
											</td>
										</tr>
									<?php endforeach; ?>
									</tbody>
								</table>
							</div>
						<?php else: ?>
							<div class="text-center py-4">
								<p class="text-muted">등록된 사용자가 없습니다.</p>
							</div>
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

<!-- 사용자 초대 모달 - 개선된 버전 -->
<div class="modal fade" id="inviteUserModal" tabindex="-1" aria-labelledby="inviteUserModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="inviteUserModalLabel">사용자 초대</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<form id="inviteUserForm">
				<div class="modal-body">
					<div class="mb-3">
						<label for="invite_emails" class="form-label">이메일 주소 <span class="text-danger">*</span></label>
						<textarea class="form-control" id="invite_emails" name="invite_emails" rows="4"
								  placeholder="초대할 사용자의 이메일 주소를 입력하세요&#10;여러 명을 초대하려면 줄바꿈 또는 쉼표(,)로 구분하세요&#10;&#10;예시:&#10;user1@example.com&#10;user2@example.com, user3@example.com" required></textarea>
						<div class="form-text">
							<i class="bi bi-info-circle"></i>
							여러 명을 초대하려면 줄바꿈 또는 쉼표(,)로 구분하여 입력하세요. 각 이메일로 초대 메일이 발송됩니다.
						</div>
					</div>

					<!-- 파싱된 이메일 목록 미리보기 -->
					<div id="emailPreview" class="mb-3" style="display: none;">
						<label class="form-label">초대할 사용자 목록:</label>
						<div id="emailList" class="p-2 bg-light border rounded">
							<!-- JavaScript로 동적 생성 -->
						</div>
					</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
					<button type="submit" class="btn btn-primary" id="inviteSubmitBtn">
						<span id="inviteSpinner" class="spinner-border spinner-border-sm me-2" style="display: none;"></span>
						<span id="inviteButtonText">초대 메일 발송</span>
					</button>
				</div>
			</form>
		</div>
	</div>
</div>

<!-- 사용자 수정 모달 -->
<div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="editUserModalLabel">사용자 정보 수정</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<form id="editUserForm">
				<input type="hidden" id="edit_user_id" name="target_user_id">
				<input type="hidden" id="edit_org_id" name="org_id">
				<div class="modal-body">
					<div class="mb-3">
						<label for="edit_user_name" class="form-label">이름 <span class="text-danger">*</span></label>
						<input type="text" class="form-control" id="edit_user_name" name="user_name" required>
					</div>
					<div class="mb-3">
						<label for="edit_user_mail" class="form-label">이메일 <span class="text-danger">*</span></label>
						<input type="email" class="form-control" id="edit_user_mail" name="user_mail" required>
					</div>
					<div class="mb-3">
						<label for="edit_user_hp" class="form-label">연락처 <span class="text-danger">*</span></label>
						<input type="text" class="form-control" id="edit_user_hp" name="user_hp"
							   placeholder="010-0000-0000" required>
					</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
					<button type="submit" class="btn btn-primary">수정</button>
				</div>
			</form>
		</div>
	</div>
</div>

<!-- 사용자 삭제 확인 모달 -->
<div class="modal fade" id="deleteUserModal" tabindex="-1" aria-labelledby="deleteUserModalLabel" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="deleteUserModalLabel">사용자 삭제 확인</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<p id="deleteUserMessage"></p>
				<div class="alert alert-warning">
					<i class="bi bi-exclamation-triangle"></i>
					이 작업은 되돌릴 수 없습니다. 사용자는 조직에서 완전히 제외됩니다.
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
				<button type="button" class="btn btn-danger" id="confirmDeleteUserBtn">삭제</button>
			</div>
		</div>
	</div>
</div>

<!-- 일괄 권한 수정 모달 -->
<div class="modal fade" id="bulkEditModal" tabindex="-1" aria-labelledby="bulkEditModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="bulkEditModalLabel">선택 권한 수정</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<form id="bulkEditForm">
				<input type="hidden" id="bulk_org_id" name="org_id">
				<input type="hidden" id="bulk_user_ids" name="user_ids">
				<div class="modal-body">
					<div class="mb-3">
						<h6>선택된 사용자</h6>
						<div id="selectedUsersList" class="p-2 bg-light rounded">
							<!-- JavaScript로 동적 생성 -->
						</div>
					</div>

					<div class="row">
						<div class="col-md-4 mb-3">
							<label for="bulk_user_level" class="form-label">권한 레벨</label>
							<select class="form-select" id="bulk_user_level" name="level">
								<option value="">변경하지 않음</option>
								<option value="0">초대중 (0)</option>
								<option value="1">회원 (1)</option>
								<option value="2">리더 (2)</option>
								<option value="5">부관리자 (5)</option>
								<option value="9">관리자 (9)</option>
								<option value="10">최고관리자 (10)</option>
							</select>
						</div>

						<div class="col-md-4 mb-3">
							<label for="bulk_managed_menus" class="form-label">관리 메뉴</label>
							<select class="form-select" id="bulk_managed_menus" name="managed_menus[]" multiple>
								<!-- JavaScript로 동적 로드 -->
							</select>
							<div class="form-text">선택하지 않으면 변경하지 않습니다.</div>
						</div>

						<div class="col-md-4 mb-3">
							<label for="bulk_managed_areas" class="form-label">관리 그룹</label>
							<select class="form-select" id="bulk_managed_areas" name="managed_areas[]" multiple>
								<!-- JavaScript로 동적 로드 -->
							</select>
							<div class="form-text">선택하지 않으면 변경하지 않습니다.</div>
						</div>
					</div>

					<div class="alert alert-warning">
						<i class="bi bi-exclamation-triangle"></i>
						선택된 모든 사용자에게 동일한 권한이 적용됩니다. 신중하게 확인하세요.
					</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
					<button type="submit" class="btn btn-primary">일괄 수정</button>
				</div>
			</form>
		</div>
	</div>
</div>



<?php $this->load->view('footer'); ?>

<script>
	/*출석관리 메뉴 active*/
	$('.menu-45').addClass('active');
</script>
<script src="/assets/js/user_management.js?<?php echo date('Ymdhis'); ?>"></script>






<!-- Select2 JS 추가 (jQuery 이후에 로드) -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
