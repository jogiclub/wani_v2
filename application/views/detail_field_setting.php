<?php $this->load->view('header'); ?>

<div class="container pt-2 pb-2">
	<nav class="mb-3" aria-label="breadcrumb">
		<ol class="breadcrumb mb-0">
			<li class="breadcrumb-item"><a href="#!">홈</a></li>
			<li class="breadcrumb-item"><a href="#!">SETTING</a></li>
			<li class="breadcrumb-item active">상세필드설정</li>
		</ol>
	</nav>
	<div class="row align-items-center justify-content-between g-3 mb-4">
		<h3 class="page-title col-6 my-1">상세필드설정</h3>
		<div class="col-6 my-1">
			<div class="text-end" role="group" aria-label="Basic example">
				<button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addFieldModal">
					<i class="bi bi-plus-circle"></i> 필드 추가
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
							<i class="bi bi-input-cursor-text"></i> <?php echo htmlspecialchars($selected_org_detail['org_name']); ?>
							상세필드 관리
						</h5>
					</div>
					<div class="card-body">
						<?php if (isset($detail_fields) && !empty($detail_fields)): ?>
							<div class="table-responsive">
								<table class="table align-middle">
									<thead>
									<tr>
										<th style="width: 50px;">순서</th>
										<th style="width: 50px;">번호</th>
										<th style="width: 150px;">필드명</th>
										<th style="width: 100px;">타입</th>
										<th>옵션</th>
										<th style="width: 70px;">사이즈</th>
										<th style="width: 50px;">상태</th>
										<th style="width: 120px;">관리</th>
									</tr>
									</thead>
									<tbody id="fieldTableBody" class="sortable-tbody">
									<?php foreach ($detail_fields as $index => $field): ?>
										<tr class="sortable-row ui-sortable-handle"
											data-field-idx="<?php echo $field['field_idx']; ?>">
											<td class="text-center">
												<i class="bi bi-grip-vertical text-muted handle"
												   style="cursor: move; font-size: 1.2em;"></i>
											</td>
											<td class="text-center">
												<span
													class="order-number ms-2"><?php echo $field['display_order']; ?></span>
											</td>
											<td>
												<strong><?php echo htmlspecialchars($field['field_name']); ?></strong>
											</td>
											<td>
						<span class="badge bg-secondary">
							<?php
							$type_names = array(
								'text' => '텍스트',
								'select' => '선택박스',
								'textarea' => '긴텍스트',
								'checkbox' => '체크박스',
								'date' => '날짜'
							);
							echo $type_names[$field['field_type']] ?? $field['field_type'];
							?>
						</span>
											</td>
											<td>
												<?php if ($field['field_settings'] && $field['field_settings'] !== '{}'): ?>
													<small class="text-muted">
														<?php
														$settings = json_decode($field['field_settings'], true);
														if ($field['field_type'] === 'select' && isset($settings['options'])) {
															echo '옵션: ' . implode(', ', $settings['options']);
														}
														?>
													</small>
												<?php endif; ?>
											</td>
											<td>
												<?php echo $field['field_size']; ?>
											</td>
											<td>
												<div class="form-check form-switch">
													<input class="form-check-input toggle-field" type="checkbox"
														   data-field-idx="<?php echo $field['field_idx']; ?>"
														<?php echo ($field['is_active'] === 'Y') ? 'checked' : ''; ?>>
												</div>
											</td>
											<td>
												<button type="button"
														class="btn btn-sm btn-outline-primary edit-field-btn"
														data-field-idx="<?php echo $field['field_idx']; ?>"
														data-field-name="<?php echo htmlspecialchars($field['field_name']); ?>"
														data-field-type="<?php echo $field['field_type']; ?>"
														data-field-size="<?php echo $field['field_size']; ?>"
														data-field-settings="<?php echo htmlspecialchars($field['field_settings']); ?>">
													<i class="bi bi-pencil"></i>
												</button>
												<button type="button"
														class="btn btn-sm btn-outline-danger delete-field-btn"
														data-field-idx="<?php echo $field['field_idx']; ?>"
														data-field-name="<?php echo htmlspecialchars($field['field_name']); ?>">
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
								<i class="bi bi-inbox text-muted" style="font-size: 3rem;"></i>
								<p class="text-muted mt-3">등록된 상세필드가 없습니다.<br>새로운 필드를 추가해보세요.</p>
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

<!-- 필드 추가 모달 -->
<div class="modal fade" id="addFieldModal" tabindex="-1" aria-labelledby="addFieldModalLabel" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="addFieldModalLabel">새 상세필드 추가</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<form id="addFieldForm">
				<div class="modal-body">
					<div class="mb-3">
						<label for="field_name" class="form-label">필드명 <span class="text-danger">*</span></label>
						<input type="text" class="form-control" id="field_name" name="field_name" required>
					</div>
					<div class="mb-3">
						<label for="field_type" class="form-label">필드 타입 <span class="text-danger">*</span></label>
						<select class="form-select" id="field_type" name="field_type" required>
							<option value="">타입을 선택하세요</option>
							<option value="text">텍스트</option>
							<option value="select">선택박스</option>
							<option value="textarea">긴텍스트</option>
							<option value="checkbox">체크박스</option>
							<option value="date">날짜</option>
						</select>
					</div>

					<div class="mb-3" id="selectOptionsDiv" style="display: none;">
						<label for="select_options" class="form-label">선택 옵션 (한 줄에 하나씩)</label>
						<textarea class="form-control" id="select_options" name="select_options" rows="4"
								  placeholder="옵션1&#10;옵션2&#10;옵션3"></textarea>
					</div>
					<div class="mb-3">
						<label for="field_size" class="form-label">필드 사이즈 <span class="text-danger">*</span></label>
						<select class="form-select" id="field_size" name="field_size" required>
							<option value="">필드 사이즈를 선택하세요!</option>
							<option value="1">1</option>
							<option value="2">2</option>
						</select>
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

<!-- 필드 수정 모달 -->
<div class="modal fade" id="editFieldModal" tabindex="-1" aria-labelledby="editFieldModalLabel" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="editFieldModalLabel">상세필드 수정</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<form id="editFieldForm">
				<input type="hidden" id="edit_field_idx" name="field_idx">
				<div class="modal-body">
					<div class="mb-3">
						<label for="edit_field_name" class="form-label">필드명 <span class="text-danger">*</span></label>
						<input type="text" class="form-control" id="edit_field_name" name="field_name" required>
					</div>
					<div class="mb-3">
						<label for="edit_field_type" class="form-label">필드 타입 <span class="text-danger">*</span></label>
						<select class="form-select" id="edit_field_type" name="field_type" required>
							<option value="">타입을 선택하세요</option>
							<option value="text">텍스트</option>
							<option value="select">선택박스</option>
							<option value="textarea">긴텍스트</option>
							<option value="checkbox">체크박스</option>
							<option value="date">날짜</option>
						</select>
					</div>

					<div class="mb-3" id="editSelectOptionsDiv" style="display: none;">
						<label for="edit_select_options" class="form-label">선택 옵션 (한 줄에 하나씩)</label>
						<textarea class="form-control" id="edit_select_options" name="select_options" rows="4"
								  placeholder="옵션1&#10;옵션2&#10;옵션3"></textarea>
					</div>
					<div class="mb-3">
						<label for="edit_field_size" class="form-label">필드 사이즈 <span class="text-danger">*</span></label>
						<select class="form-select" id="edit_field_size" name="field_size" required>
							<option value="">필드 사이즈를 선택하세요!</option>
							<option value="1">1</option>
							<option value="2">2</option>
						</select>
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


<!-- 필드 추가 모달 -->
<div class="modal fade" id="addFieldModal" tabindex="-1" aria-labelledby="addFieldModalLabel" aria-hidden="true">
	<!-- 기존 모달 내용 -->
</div>

<!-- 필드 수정 모달 -->
<div class="modal fade" id="editFieldModal" tabindex="-1" aria-labelledby="editFieldModalLabel" aria-hidden="true">
	<!-- 기존 모달 내용 -->
</div>


<?php $this->load->view('footer'); ?>
<script>
	/*출석관리 메뉴 active*/
	$('.menu-43').addClass('active');
</script>
<script src="/assets/js/detail_field.js?<?php echo WB_VERSION; ?>"></script>


