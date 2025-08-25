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
											<th>타입명</th>
											<th style="width: 100px;">별칭</th>
											<th style="width: 120px;">카테고리</th>
											<th style="width: 80px;">포인트</th>
											<th style="width: 90px;">입력타입</th>
											<th style="width: 80px;">색상</th>
											<th style="width: 120px;">관리</th>
										</tr>
										</thead>
										<tbody id="attendanceTypeTableBody" class="sortable">
										<?php
										$current_category = '';
										foreach ($attendance_types as $attendance_type):
											?>
											<tr data-att-type-idx="<?php echo $attendance_type['att_type_idx']; ?>">
												<td class="text-center">
													<i class="bi bi-grip-vertical text-muted handle"
													   style="cursor: move;"></i>
													<span class="order-number"><?php echo $attendance_type['att_type_order']; ?></span>
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
		<div class="modal-dialog modal-lg">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title" id="addAttendanceTypeModalLabel">새 출석타입 추가</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<form id="addAttendanceTypeForm">
					<div class="modal-body">
						<div class="row">
							<div class="col-md-6">
								<div class="mb-3">
									<label for="att_type_name" class="form-label">출석타입명 <span class="text-danger">*</span></label>
									<input type="text" class="form-control" id="att_type_name" name="att_type_name" required>
								</div>
							</div>
							<div class="col-md-6">
								<div class="mb-3">
									<label for="att_type_nickname" class="form-label">별칭 <span class="text-danger">*</span></label>
									<input type="text" class="form-control" id="att_type_nickname" name="att_type_nickname" required>
									<div class="form-text">출석 화면에 표시되는 짧은 이름</div>
								</div>
							</div>
						</div>
						<div class="row">
							<div class="col-md-6">
								<div class="mb-3">
									<label for="att_type_category_idx" class="form-label">카테고리</label>
									<select class="form-select" id="att_type_category_idx" name="att_type_category_idx">
										<option value="">새 카테고리 생성</option>
										<?php if (isset($attendance_categories) && !empty($attendance_categories)): ?>
											<?php foreach ($attendance_categories as $category): ?>
												<option value="<?php echo $category['att_type_category_idx']; ?>">
													<?php echo htmlspecialchars($category['att_type_category_name']); ?>
												</option>
											<?php endforeach; ?>
										<?php endif; ?>
									</select>
								</div>
							</div>
							<div class="col-md-6">
								<div class="mb-3" id="newCategoryDiv">
									<label for="att_type_category_name" class="form-label">새 카테고리명</label>
									<input type="text" class="form-control" id="att_type_category_name" name="att_type_category_name">
									<div class="form-text">카테고리를 선택하지 않으면 새로 생성됩니다</div>
								</div>
							</div>
						</div>
						<div class="row">
							<div class="col-md-4">
								<div class="mb-3">
									<label for="att_type_point" class="form-label">포인트</label>
									<input type="number" class="form-control" id="att_type_point" name="att_type_point" value="0" min="0">
								</div>
							</div>
							<div class="col-md-4">
								<div class="mb-3">
									<label for="att_type_input" class="form-label">입력타입</label>
									<select class="form-select" id="att_type_input" name="att_type_input">
										<option value="check">체크박스</option>
										<option value="text">텍스트입력</option>
									</select>
								</div>
							</div>
							<div class="col-md-4">
								<div class="mb-3">
									<label for="att_type_color" class="form-label">색상</label>
									<input type="color" class="form-control form-control-color" id="att_type_color" name="att_type_color" value="#CB3227">
								</div>
							</div>
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
						<div class="row">
							<div class="col-md-6">
								<div class="mb-3">
									<label for="edit_att_type_name" class="form-label">출석타입명 <span class="text-danger">*</span></label>
									<input type="text" class="form-control" id="edit_att_type_name" name="att_type_name" required>
								</div>
							</div>
							<div class="col-md-6">
								<div class="mb-3">
									<label for="edit_att_type_nickname" class="form-label">별칭 <span class="text-danger">*</span></label>
									<input type="text" class="form-control" id="edit_att_type_nickname" name="att_type_nickname" required>
								</div>
							</div>
						</div>
						<div class="row">
							<div class="col-md-4">
								<div class="mb-3">
									<label for="edit_att_type_point" class="form-label">포인트</label>
									<input type="number" class="form-control" id="edit_att_type_point" name="att_type_point" min="0">
								</div>
							</div>
							<div class="col-md-4">
								<div class="mb-3">
									<label for="edit_att_type_input" class="form-label">입력타입</label>
									<select class="form-select" id="edit_att_type_input" name="att_type_input">
										<option value="check">체크박스</option>
										<option value="text">텍스트입력</option>
									</select>
								</div>
							</div>
							<div class="col-md-4">
								<div class="mb-3">
									<label for="edit_att_type_color" class="form-label">색상</label>
									<input type="color" class="form-control form-control-color" id="edit_att_type_color" name="att_type_color">
								</div>
							</div>
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

	<script src="<?php echo base_url('assets/js/attendance_setting.js'); ?>"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.14.0/Sortable.min.js"></script>

<?php $this->load->view('footer'); ?>
