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
		<h3 class="page-title col-6 my-1">사용자관리</h3>
		<div class="col-6 my-1">
			<div class="text-end" role="group" aria-label="Basic example">
				<button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#inviteUserModal">
					<i class="bi bi-person-plus"></i> 사용자 초대
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
							<i class="bi bi-people"></i> <?php echo htmlspecialchars($selected_org_detail['org_name']); ?> 사용자 목록
						</h5>
					</div>
					<div class="card-body">
						<?php if (isset($org_users) && !empty($org_users)): ?>
							<div class="table-responsive">
								<table class="table align-middle">
									<thead>
									<tr>
										<th style="width: 60px;">프로필</th>
										<th>이름</th>
										<th>이메일</th>
										<th style="width: 120px;">연락처</th>
										<th style="width: 80px;">권한</th>
										<th style="width: 120px;">관리</th>
									</tr>
									</thead>
									<tbody>
									<?php foreach ($org_users as $user): ?>
										<tr>
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
													<br><small class="text-primary">시스템 관리자</small>
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
												<button type="button"
														class="btn btn-sm btn-outline-primary edit-user-btn"
														data-user-id="<?php echo $user['user_id']; ?>"
														data-user-name="<?php echo htmlspecialchars($user['user_name']); ?>"
														data-user-hp="<?php echo htmlspecialchars($user['user_hp']); ?>"
														data-user-level="<?php echo $user['level']; ?>">
													<i class="bi bi-pencil"></i>
												</button>
												<?php if ($user['user_id'] !== $this->session->userdata('user_id')): ?>
													<button type="button"
															class="btn btn-sm btn-outline-danger delete-user-btn"
															data-user-id="<?php echo $user['user_id']; ?>"
															data-user-name="<?php echo htmlspecialchars($user['user_name']); ?>">
														<i class="bi bi-trash"></i>
													</button>
												<?php endif; ?>
											</td>
										</tr>
									<?php endforeach; ?>
									</tbody>
								</table>
							</div>
						<?php else: ?>
							<div class="text-center py-5">
								<i class="bi bi-person-x text-muted" style="font-size: 3rem;"></i>
								<p class="text-muted mt-3">등록된 사용자가 없습니다.</p>
							</div>
						<?php endif; ?>
					</div>
				</div>
			</div>

			<div class="col-lg-5">
				<div class="card">
					<div class="card-header">
						<h5 class="card-title mb-0">
							<i class="bi bi-diagram-3-fill"></i> 조직 목록
						</h5>
					</div>
					<div class="card-body p-0">
						<div class="list-group list-group-flush" id="orgList">
							<?php if (isset($orgs) && !empty($orgs)): ?>
								<?php foreach ($orgs as $org): ?>
									<a href="?org_id=<?php echo $org['org_id']; ?>"
									   class="list-group-item list-group-item-action org-selector-item <?php echo ($org['org_id'] == $selected_org_detail['org_id']) ? 'active' : ''; ?>"
									   data-org-id="<?php echo $org['org_id']; ?>">
										<div class="d-flex w-100 justify-content-between align-items-center">
											<div class="flex-grow-1">
												<h6 class="mb-1"><?php echo htmlspecialchars($org['org_name']); ?></h6>
												<small class="text-muted">
													사용자 <?php echo $org['user_count']; ?>명 |
													권한 <?php
													if ($org['level'] >= 10) echo '최고관리자';
													elseif ($org['level'] >= 9) echo '관리자';
													else echo '일반';
													?>
												</small>
											</div>
										</div>
									</a>
								<?php endforeach; ?>
							<?php else: ?>
								<p class="text-muted p-3">접근 가능한 조직이 없습니다.</p>
							<?php endif; ?>
						</div>
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

<!-- 사용자 초대 모달 -->
<div class="modal fade" id="inviteUserModal" tabindex="-1" aria-labelledby="inviteUserModalLabel" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="inviteUserModalLabel">사용자 초대</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<form id="inviteUserForm">
				<div class="modal-body">
					<div class="mb-3">
						<label for="invite_email" class="form-label">이메일 주소 <span class="text-danger">*</span></label>
						<input type="email" class="form-control" id="invite_email" name="invite_email"
							   placeholder="초대할 사용자의 이메일 주소를 입력하세요" required>
						<div class="form-text">입력한 이메일로 초대 메일이 발송됩니다.</div>
					</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
					<button type="submit" class="btn btn-primary">초대 메일 발송</button>
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
						<label for="edit_user_hp" class="form-label">연락처 <span class="text-danger">*</span></label>
						<input type="text" class="form-control" id="edit_user_hp" name="user_hp"
							   placeholder="010-0000-0000" required>
					</div>
					<div class="mb-3">
						<label for="edit_user_level" class="form-label">권한 레벨</label>
						<select class="form-select" id="edit_user_level" name="level">
							<option value="0">일반 사용자 (0)</option>
							<option value="1">회원 (1)</option>
							<option value="2">구역장 (2)</option>
							<option value="5">부관리자 (5)</option>
							<option value="9">관리자 (9)</option>
							<option value="10">최고관리자 (10)</option>
						</select>
					</div>
					<!-- 관리 메뉴 필드 -->
					<div class="mb-3" id="managed_menus_group">
						<label for="edit_managed_menus" class="form-label">관리 메뉴</label>
						<select class="form-select" id="edit_managed_menus" name="managed_menus[]" multiple>
							<!-- JavaScript로 동적 로드 -->
						</select>
						<div class="form-text">사용자가 접근할 수 있는 메뉴를 선택하세요. (최고관리자는 모든 메뉴에 접근 가능)</div>
					</div>
					<!-- 관리 그룹 필드 -->
					<div class="mb-3" id="managed_areas_group">
						<label for="edit_managed_areas" class="form-label">관리 그룹</label>
						<select class="form-select" id="edit_managed_areas" name="managed_areas[]" multiple>
							<!-- JavaScript로 동적 로드 -->
						</select>
						<div class="form-text">사용자가 관리할 수 있는 그룹을 선택하세요.</div>
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

<!-- Toast 알림 -->
<div class="toast-container position-fixed bottom-0 end-0 p-3">
	<div id="userManagementToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
		<div class="toast-header">
			<strong class="me-auto">사용자 관리</strong>
			<button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
		</div>
		<div class="toast-body"></div>
	</div>
</div>

<?php $this->load->view('footer'); ?>
<script src="/assets/js/user_management.js?<?php echo date('Ymdhis'); ?>"></script>






<!-- Select2 JS 추가 (jQuery 이후에 로드) -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
