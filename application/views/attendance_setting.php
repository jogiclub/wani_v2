<?php $this->load->view('header'); ?>

<div class="container pt-2 pb-2">
	<nav class="mb-3" aria-label="breadcrumb">
		<ol class="breadcrumb mb-0">
			<li class="breadcrumb-item"><a href="#!">홈</a></li>
			<li class="breadcrumb-item"><a href="#!">SETTING</a></li>
			<li class="breadcrumb-item active">출석설정</li>
		</ol>
	</nav>
	<div class="row align-items-center justify-content-between g-3 mb-4">
		<h3 class="page-title col-6 my-1">출석설정</h3>
		<div class="col-6 my-1">
			<div class="text-end" role="group" aria-label="Basic example">
				<button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addAttendanceTypeModal">
					<i class="bi bi-plus-circle"></i> 출석타입 추가
				</button>
				<button type="button" class="btn btn-primary ms-2" id="saveOrderBtn" style="display: none;">
					<i class="bi bi-save"></i> 순서 저장
				</button>
			</div>
		</div>
	</div>

	<?php if (isset($selected_org_detail) && $selected_org_detail): ?>
		<div class="row">
			<div class="col-lg-12">
				<div class="card">
					<div class="card-header">
						<h5 class="card-title mb-0">
							<i class="bi bi-sliders2-vertical"></i> <?php echo htmlspecialchars($selected_org_detail['org_name']); ?>
							출석타입 관리
						</h5>
					</div>
					<div class="card-body">
						<?php if (isset($attendance_types) && !empty($attendance_types)): ?>
							<div class="table-responsive">
								<table class="table align-middle">
									<thead>
									<tr>
										<th style="width: 50px;">순서</th>
										<th style="width: 50px;">번호</th>
										<th>타입명</th>
										<th style="width: 100px;">별칭</th>
										<th style="width: 120px;">카테고리</th>
										<th style="width: 80px;">포인트</th>
										<th style="width: 90px;">입력타입</th>
										<th style="width: 80px;">색상</th>
										<th style="width: 120px;">관리</th>
									</tr>
									</thead>
									<tbody id="attendanceTypeTableBody" class="sortable-tbody">
									<?php foreach ($attendance_types as $index => $attendance_type): ?>
										<tr class="sortable-row ui-sortable-handle" data-att-type-idx="<?php echo $attendance_type['att_type_idx']; ?>">
											<td class="text-center">
												<i class="bi bi-grip-vertical text-muted handle" style="cursor: move; font-size: 1.2em;"></i>
											</td>
											<td class="text-center">
												<span class="order-number ms-2"><?php echo $attendance_type['att_type_order']; ?></span>
											</td>
											<td>
												<strong><?php echo htmlspecialchars($attendance_type['att_type_name']); ?></strong>
											</td>
											<td>
												<span class="badge"
													  style="background-color: #<?php echo $attendance_type['att_type_color']; ?>; color: white;">
													<?php echo htmlspecialchars($attendance_type['att_type_nickname']); ?>
												</span>
											</td>
											<td>
												<small class="text-muted">
													<?php echo htmlspecialchars($attendance_type['att_type_category_name'] ?: '기본'); ?>
												</small>
											</td>
											<td class="text-center">
												<?php echo $attendance_type['att_type_point'] ?: 0; ?>
											</td>
											<td class="text-center">
												<span class="badge bg-<?php echo $attendance_type['att_type_input'] === 'text' ? 'info' : 'secondary'; ?>">
													<?php echo $attendance_type['att_type_input'] === 'text' ? '텍스트' : '체크'; ?>
												</span>
											</td>
											<td class="text-center">
												<div class="color-preview"
													 style="width: 30px; height: 30px; background-color: #<?php echo $attendance_type['att_type_color']; ?>; border-radius: 4px; border: 1px solid #ddd; margin: auto;">
												</div>
											</td>
											<td>
												<button type="button"
														class="btn btn-sm btn-outline-primary edit-attendance-type-btn"
														data-att-type-idx="<?php echo $attendance_type['att_type_idx']; ?>"
														data-att-type-name="<?php echo htmlspecialchars($attendance_type['att_type_name']); ?>"
														data-att-type-nickname="<?php echo htmlspecialchars($attendance_type['att_type_nickname']); ?>"
														data-att-type-point="<?php echo $attendance_type['att_type_point']; ?>"
														data-att-type-input="<?php echo $attendance_type['att_type_input']; ?>"
														data-att-type-color="<?php echo $attendance_type['att_type_color']; ?>">
													<i class="bi bi-pencil"></i>
												</button>
												<button type="button"
														class="btn btn-sm btn-outline-danger delete-attendance-type-btn"
														data-att-type-idx="<?php echo $attendance_type['att_type_idx']; ?>"
														data-att-type-name="<?php echo htmlspecialchars($attendance_type['att_type_name']); ?>">
													<i class="bi bi-trash"></i>
												</button>
											</td>
										</tr>
									<?php endforeach; ?>
									</tbody>
								</table>
							</div>
						<?php else: ?>
							<div class="text-center py-5">
								<i class="bi bi-sliders2-vertical text-muted" style="font-size: 3rem;"></i>
								<p class="text-muted mt-3">등록된 출석타입이 없습니다.<br>새로운 출석타입을 추가해보세요.</p>
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

<!-- 출석타입 추가 모달 -->
<div class="modal fade" id="addAttendanceTypeModal" tabindex="-1" aria-labelledby="addAttendanceTypeModalLabel" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="addAttendanceTypeModalLabel">새 출석타입 추가</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<form id="addAttendanceTypeForm">
				<div class="modal-body">
					<div class="mb-3">
						<label for="att_type_name" class="form-label">출석타입명 <span class="text-danger">*</span></label>
						<input type="text" class="form-control" id="att_type_name" name="att_type_name" required>
					</div>
					<div class="mb-3">
						<label for="att_type_nickname" class="form-label">별칭 <span class="text-danger">*</span></label>
						<input type="text" class="form-control" id="att_type_nickname" name="att_type_nickname" required>
					</div>
					<div class="mb-3">
						<label for="att_type_point" class="form-label">포인트</label>
						<input type="number" class="form-control" id="att_type_point" name="att_type_point" value="0">
					</div>
					<div class="mb-3">
						<label for="att_type_input" class="form-label">입력타입</label>
						<select class="form-select" id="att_type_input" name="att_type_input">
							<option value="check">체크</option>
							<option value="text">텍스트</option>
						</select>
					</div>
					<div class="mb-3">
						<label for="att_type_color" class="form-label">색상</label>
						<input type="color" class="form-control form-control-color" id="att_type_color" name="att_type_color" value="#CB3227">
					</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
					<button type="submit" class="btn btn-primary">추가</button>
				</div>
			</form>
		</div>
	</div>
</div>

<!-- 출석타입 수정 모달 -->
<div class="modal fade" id="editAttendanceTypeModal" tabindex="-1" aria-labelledby="editAttendanceTypeModalLabel" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="editAttendanceTypeModalLabel">출석타입 수정</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<form id="editAttendanceTypeForm">
				<input type="hidden" id="edit_att_type_idx" name="att_type_idx">
				<div class="modal-body">
					<div class="mb-3">
						<label for="edit_att_type_name" class="form-label">출석타입명 <span class="text-danger">*</span></label>
						<input type="text" class="form-control" id="edit_att_type_name" name="att_type_name" required>
					</div>
					<div class="mb-3">
						<label for="edit_att_type_nickname" class="form-label">별칭 <span class="text-danger">*</span></label>
						<input type="text" class="form-control" id="edit_att_type_nickname" name="att_type_nickname" required>
					</div>
					<div class="mb-3">
						<label for="edit_att_type_point" class="form-label">포인트</label>
						<input type="number" class="form-control" id="edit_att_type_point" name="att_type_point" value="0">
					</div>
					<div class="mb-3">
						<label for="edit_att_type_input" class="form-label">입력타입</label>
						<select class="form-select" id="edit_att_type_input" name="att_type_input">
							<option value="check">체크</option>
							<option value="text">텍스트</option>
						</select>
					</div>
					<div class="mb-3">
						<label for="edit_att_type_color" class="form-label">색상</label>
						<input type="color" class="form-control form-control-color" id="edit_att_type_color" name="att_type_color" value="#CB3227">
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


<style>
	/* 드래그앤드롭 스타일 */
	.sortable-tbody .sortable-row {
		transition: all 0.3s ease;
	}

	.sortable-tbody .ui-sortable-placeholder {
		background-color: #f8f9fa;
		border: 2px dashed #dee2e6;
		visibility: visible !important;
		height: 60px;
	}

	.sortable-tbody .ui-sortable-helper {
		background-color: #fff;
		border: 1px solid #dee2e6;
		box-shadow: 0 2px 8px rgba(0,0,0,0.1);
		opacity: 0.8;
	}

	.handle:hover {
		color: #0d6efd !important;
		transform: scale(1.1);
		transition: all 0.2s ease;
	}

	.sortable-row.ui-sortable-helper td {
		background-color: #fff;
	}

	/* 삭제 컨펌 모달 스타일 */
	#deleteConfirmModal .modal-header {
		border-bottom: 1px solid #dc3545;
		background-color: #fdf2f2;
	}

	#deleteConfirmModal .modal-title {
		color: #dc3545;
	}

	#deleteConfirmModal .alert-warning {
		border-left: 4px solid #ffc107;
	}
</style>



<?php $this->load->view('footer'); ?>
<script>
	/*출석관리 메뉴 active*/
	$('.menu-44').addClass('active');
</script>
<script src="/assets/js/attendance_setting.js?20250906"></script>
