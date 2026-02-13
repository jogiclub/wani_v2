<?php include APPPATH . 'views/header_noframe.php'; ?>
<link rel="stylesheet" href="/assets/css/member.css?<?php echo WB_VERSION; ?>">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/croppie@2.6.5/croppie.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/family-chart@0.9.0/dist/styles/family-chart.min.css">

<style>
	.member-popup-container {
		padding: 12px;
	}

	.member-popup-card {
		height: calc(100vh - 24px);
		display: flex;
		flex-direction: column;
	}

	.member-popup-header {
		padding: 12px 16px;
		border-bottom: 1px solid #dee2e6;
		display: flex;
		justify-content: space-between;
		align-items: center;
	}

	.member-popup-body {
		flex: 1;
		overflow-y: auto;
	}

	.member-popup-footer {
		padding: 12px;
		border-top: 1px solid #dee2e6;
		background: #f8f9fa;
	}
</style>

<div class="container-fluid member-popup-container">
	<div class="card member-popup-card">
		<div class="member-popup-header">
			<h5 class="mb-0" id="memberOffcanvasLabel">회원 정보 수정</h5>
			<button type="button" class="btn-close" aria-label="Close" onclick="window.close()"></button>
		</div>
		<div class="member-popup-body">
		<form id="memberForm" enctype="multipart/form-data">
			<input type="hidden" id="member_idx" name="member_idx">
			<input type="hidden" id="org_id" name="org_id">


			<ul class="nav nav-tabs" id="memberTab" role="tablist">

				<li class="nav-item" role="presentation">
					<button class="nav-link active" id="profile-tab" data-bs-toggle="tab" data-bs-target="#profile-tab-pane" type="button" role="tab" aria-controls="profile-tab-pane" aria-selected="false">湲곕낯?뺣낫</button>
				</li>
				<li class="nav-item" role="presentation">
					<button class="nav-link" id="detail-tab" data-bs-toggle="tab" data-bs-target="#detail-tab-pane" type="button" role="tab" aria-controls="home-tab-pane" aria-selected="true">?곸꽭?뺣낫</button>
				</li>
				<li class="nav-item" role="presentation">
					<button class="nav-link" id="family-tab" data-bs-toggle="tab" data-bs-target="#family-tab-pane" type="button" role="tab" aria-controls="home-tab-pane" aria-selected="true">媛議?/button>
				</li>
				<li class="nav-item" role="presentation">
					<button class="nav-link" id="timeline-tab" data-bs-toggle="tab" data-bs-target="#timeline-tab-pane" type="button" role="tab" aria-controls="timeline-tab-pane" aria-selected="false">??꾨씪??/button>
				</li>
				<li class="nav-item" role="presentation">
					<button class="nav-link" id="memo-tab" data-bs-toggle="tab" data-bs-target="#memo-tab-pane" type="button" role="tab" aria-controls="contact-tab-pane" aria-selected="false">?뚯썝硫붾え</button>
				</li>
				<li class="nav-item" role="presentation">
					<button class="nav-link" id="mission-tab" data-bs-toggle="tab" data-bs-target="#mission-tab-pane" type="button" role="tab" aria-controls="mission-tab-pane" aria-selected="false">?뚯넚</button>
				</li>
				<li class="nav-item" role="presentation">
					<button class="nav-link" id="editing-tab" data-bs-toggle="tab" data-bs-target="#editing-tab-pane" type="button" role="tab" aria-controls="editing-tab-pane" aria-selected="false">?섏젙?댁뿭</button>
				</li>

			</ul>

			<div class="tab-content" id="memberTabContent">
				<div class="tab-pane fade show active" id="profile-tab-pane" role="tabpanel" aria-labelledby="profile-tab" tabindex="0">
					<div class="row mt-3">
						<div class="d-flex justify-content-end text-end">
							<!--
							<div class="form-check form-switch me-3">
								<input type="checkbox" class="form-check-input" id="leader_yn" name="leader_yn">
								<label class="form-check-label" for="leader_yn">由щ뜑</label>
							</div>
							<div class="form-check form-switch">
								<input type="checkbox" class="form-check-input" id="new_yn" name="new_yn">
								<label class="form-check-label" for="new_yn">?덇?議?/label>
							</div>-->
						</div>
					</div>

					<!-- ?ъ쭊 ?낅줈???곸뿭 -->
					<div class="row">
						<div class="col-12 text-center">
							<!-- ?ъ쭊 ?낅줈??踰꾪듉 -->
							<div id="photoUpload">
								<label for="member_photo" class="d-inline-block">
									<div class="border border-2 border-dashed rounded-circle d-flex align-items-center justify-content-center" style="width: 120px; height: 120px; cursor: pointer;">
										<i class="bi bi-person-plus fs-1 text-muted"></i>
									</div>
								</label>
								<input type="file" id="member_photo" name="member_photo" accept="image/*" class="d-none">
								<div class="mt-2 text-muted small">?ъ쭊???좏깮?섏꽭??/div>
							</div>

							<!-- ?ъ쭊 誘몃━蹂닿린 -->
							<div id="photoPreview" style="display: none;">
								<img id="previewImage" src="" alt="誘몃━蹂닿린" class="rounded-circle" style="width: 120px; height: 120px; object-fit: cover;">
								<div class="mt-2">
									<button type="button" class="btn btn-xs btn-outline-primary" id="cropPhoto">
										<i class="bi bi-crop"></i> ?щ∼
									</button>
									<button type="button" class="btn btn-xs btn-outline-danger" id="removePhoto">
										<i class="bi bi-trash"></i> ??젣
									</button>
								</div>
							</div>

							<!-- Croppie ?곸뿭 -->
							<div id="cropContainer" style="display: none;">
								<div id="cropBox" style="width: 300px; height: 300px; margin: 0 auto;"></div>
								<div class="mt-3">
									<button type="button" class="btn btn-sm btn-success" id="saveCrop">
										<i class="bi bi-check"></i> ?곸슜
									</button>
									<button type="button" class="btn btn-sm btn-secondary" id="cancelCrop">
										<i class="bi bi-x"></i> 痍⑥냼
									</button>
								</div>
							</div>
						</div>
					</div>

					<!-- ??븷: ?뚯썝?뺣낫 ?섏젙 紐⑤떖??吏곸쐞/吏곷텇, 吏곸콉 ?꾨뱶 異붽? -->

					<div class="row">
						<!-- 媛쒖씤?뺣낫 ?낅젰 ?꾨뱶??-->
						<div class="col-6 col-sm-4 mb-3">
							<label for="member_name" class="form-label">?대쫫 <span class="text-danger">*</span></label>
							<div class="input-group">
								<input type="text" class="form-control" id="member_name" name="member_name" required>
								<select class="form-select" aria-label="member sex" id="member_sex" name="member_sex" style=" max-width: 80px;">
									<option selected>?깅퀎</option>
									<option value="male">??/option>
									<option value="female">??/option>
								</select>
							</div>
						</div>

						<div class="col-6 col-sm-4 mb-3">
							<label for="member_nick" class="form-label">蹂꾨챸</label>
							<input type="text" class="form-control" id="member_nick" name="member_nick">
						</div>

						<!-- ?덈줈 異붽??섎뒗 吏곸쐞/吏곷텇 ?꾨뱶 -->
						<div class="col-6 col-sm-4 mb-3">
							<label for="position_name" class="form-label">吏곸쐞/吏곷텇</label>
							<select class="form-select" id="position_name" name="position_name">
								<option value="">吏곸쐞/吏곷텇 ?좏깮</option>
							</select>
						</div>

						<!-- ?덈줈 異붽??섎뒗 吏곸콉 ?꾨뱶 -->
						<div class="col-6 col-sm-4 mb-3">
							<label for="duty_name" class="form-label">吏곸콉(洹몃９吏곸콉)</label>
							<select class="form-select" id="duty_name" name="duty_name">
								<option value="">吏곸콉 ?좏깮</option>
							</select>
						</div>

						<div class="col-6 col-sm-4 mb-3">
							<label for="member_phone" class="form-label">?곕씫泥?/label>
							<input type="tel" class="form-control" id="member_phone" name="member_phone">
						</div>

						<div class="col-6 col-sm-4 mb-3">
							<label for="member_birth" class="form-label">?앸뀈?붿씪</label>
							<input type="date" class="form-control" id="member_birth" name="member_birth">
						</div>

						<div class="col-6 col-sm-4 mb-3">
							<label for="member_address" class="form-label">二쇱냼</label>
							<input type="text" class="form-control" id="member_address" name="member_address">
						</div>

						<div class="col-6 col-sm-4 mb-3">
							<label for="member_address_detail" class="form-label">?곸꽭二쇱냼</label>
							<input type="text" class="form-control" id="member_address_detail" name="member_address_detail">
						</div>
						<div class="col-6 col-sm-4 mb-3">
							<label for="area_idx" class="form-label">?뚭렇猷?/label>
							<select class="form-select" id="area_idx" name="area_idx">
								<option value="">?뚭렇猷??좏깮</option>
							</select>
						</div>
					</div>

						<!-- front ?꾩튂 ?곸꽭?꾨뱶 ?곸뿭 (?숈쟻 ?앹꽦) -->
					<div class="row row-lg pt-3 pb-1 mb-3" id="detail-front" style="display: none; background:#eee;"></div>

					<div class="row">
						<div class="col-12 mb-3">
							<label for="member_etc" class="form-label">?뱀씠?ы빆</label>
							<textarea class="form-control" id="member_etc" name="member_etc" rows="3"></textarea>
						</div>
					</div>
				</div>
				<div class="tab-pane fade" id="detail-tab-pane" role="tabpanel" aria-labelledby="detail-tab" tabindex="0">
					<div class="row mt-3" id="detailFieldsContainer">
						<!-- ?숈쟻?쇰줈 ?앹꽦???곸꽭?꾨뱶??-->
					</div>
					<div class="text-center mt-3">
						<div class="spinner-border text-primary" role="status" id="detailFieldsLoading" style="display: none;">
							<span class="visually-hidden">濡쒕뵫以?..</span>
						</div>
						<div class="text-muted" id="detailFieldsEmpty" style="display: none;">
							<i class="bi bi-info-circle me-2"></i>?ㅼ젙???곸꽭?꾨뱶媛 ?놁뒿?덈떎.
						</div>
					</div>
				</div>

				<div class="tab-pane fade" id="family-tab-pane" role="tabpanel" aria-labelledby="family-tab" tabindex="0">
					<div class="row mt-3">
						<div class="col-12">
							<!-- ?곷떒 踰꾪듉 ?곸뿭 -->
							<div class="family-button-section pb-2 mb-2">
								<div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
									<div class="btn-group btn-group-sm">
										<button type="button" class="btn btn-outline-primary" id="addFamilyMemberBtn">
											<i class="bi bi-person-plus"></i> 媛議?異붽?
										</button>
										<button type="button" class="btn btn-outline-success" id="linkExistingMemberBtn">
											<i class="bi bi-link-45deg"></i> 湲곗〈?뚯썝 ?곌껐
										</button>
									</div>

								</div>
							</div>

							<!-- 媛怨꾨룄 李⑦듃 ?곸뿭 -->
							<div id="familyChartContainer" class="f3" style="width:100%; height:400px; background-color:#f8f9fa; border-radius:8px; position:relative; overflow:hidden;">
								<div id="familyChartLoading" class="d-flex justify-content-center align-items-center position-absolute w-100 h-100" style="z-index:10;">
									<div class="text-center text-muted">
										<i class="bi bi-diagram-3 fs-1 mb-2 d-block"></i>
										<span>媛議??뺣낫媛 ?놁뒿?덈떎</span>
									</div>
								</div>
							</div>

							<!-- 媛議?紐⑸줉 (?띿뒪???뺥깭) -->
							<div class="mt-3" id="familyListContainer">
								<h6 class="text-muted mb-2"><i class="bi bi-people"></i> 媛議?紐⑸줉</h6>
								<div id="familyList" class="list-group list-group-flush">
									<!-- ?숈쟻?쇰줈 ?앹꽦 -->
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="tab-pane fade" id="timeline-tab-pane" role="tabpanel" aria-labelledby="timeline-tab" tabindex="0">
					<div class="row mt-3">
						<div class="col-12">
							<!-- 異붽? 踰꾪듉 -->
							<div class="d-flex justify-content-end mb-3">
								<button type="button" class="btn btn-sm btn-primary" id="addTimelineBtn">
									<i class="bi bi-plus-lg"></i> 異붽?
								</button>
							</div>

							<!-- ??꾨씪??紐⑸줉 ?곸뿭 -->
							<div class="timeline-list-section">
								<div id="timelineList">
									<div class="text-center text-muted py-3" id="emptyTimelineMessage">
										??꾨씪?몄쓣 遺덈윭?ㅺ퀬 ?덉뒿?덈떎...
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="tab-pane fade" id="memo-tab-pane" role="tabpanel" aria-labelledby="memo-tab" tabindex="0">
					<div class="row mt-3">
						<div class="col-12">
							<!-- 硫붾え 異붽? 踰꾪듉 ?곸뿭 -->
							<div class="memo-input-section pb-3 mb-3 border-bottom">
								<div class="d-flex justify-content-end">
									<button type="button" class="btn btn-sm btn-primary" id="addMemoBtn">
										<i class="bi bi-plus-lg"></i> 硫붾え 異붽?
									</button>
								</div>
							</div>

							<!-- 湲곗〈 硫붾え 紐⑸줉 ?곸뿭 -->
							<div class="memo-list-section">
								<div id="memoList">
									<div class="text-center text-muted py-3" id="emptyMemoMessage">
										硫붾え瑜?遺덈윭?ㅺ퀬 ?덉뒿?덈떎...
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>

				<div class="tab-pane fade" id="mission-tab-pane" role="tabpanel" aria-labelledby="mission-tab" tabindex="0">
					<div class="row mt-3">
						<div class="col-12">
							<!-- ?곷떒 踰꾪듉 ?곸뿭 -->
							<div class="mission-button-section pb-3 mb-3 border-bottom">
								<div class="d-flex justify-content-end flex-wrap gap-2">
									<div class="btn-group">
										<button type="button" class="btn btn-sm btn-outline-primary" id="offerToMemberBtn">
											<i class="bi bi-envelope"></i> ?뚯썝?먭쾶 援먰쉶異붿쿇
										</button>
										<button type="button" class="btn btn-sm btn-outline-info" id="offerToChurchBtn">
											<i class="bi bi-envelope"></i> ?뚯넚援먰쉶???뚯썝?뺣낫 ?꾨떖
										</button>
										<button type="button" class="btn btn-sm btn-outline-success" id="autoMatchChurchBtn">
											<i class="bi bi-link-45deg"></i> 寃곗뿰援먰쉶 ?먮룞留ㅼ묶
										</button>
										<button type="button" class="btn btn-sm btn-outline-dark" id="addTransferOrgBtn">
											<i class="bi bi-plus-lg"></i> ?뚯넚援먰쉶 ?섎룞異붽?
										</button>
									</div>
								</div>
							</div>

							<!-- ?뚯넚 援먰쉶 紐⑸줉 ?곸뿭 -->
							<div class="transfer-org-list-section">
								<div id="transferOrgList">
									<div class="text-center text-muted py-3" id="emptyTransferOrgMessage">
										?뚯넚 援먰쉶 ?뺣낫瑜?遺덈윭?ㅺ퀬 ?덉뒿?덈떎...
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>


				<!-- ?섏젙??肄붾뱶 -->
				<div class="tab-pane fade" id="editing-tab-pane" role="tabpanel" aria-labelledby="editing-tab" tabindex="0">
					<div class="row mt-3">
						<div class="col-12">
							<!-- ?섏젙?댁뿭 濡쒕뵫 ?쒖떆 -->
							<div id="revisionLoading" class="text-center py-4 d-none">
								<div class="spinner-border spinner-border-sm text-primary" role="status">
									<span class="visually-hidden">濡쒕뵫以?..</span>
								</div>
								<span class="ms-2 text-muted">?섏젙?댁뿭??遺덈윭?ㅻ뒗 以?..</span>
							</div>

							<!-- ?섏젙?댁뿭 ?놁쓬 ?쒖떆 -->
							<div id="revisionEmpty" class="text-center py-5 d-none">
								<i class="bi bi-clock-history fs-1 text-muted"></i>
								<p class="text-muted mt-2 mb-0">?섏젙?댁뿭???놁뒿?덈떎.</p>
							</div>

							<!-- ?섏젙?댁뿭 紐⑸줉 而⑦뀒?대꼫 -->
							<div id="revisionListContainer" style="max-height: 500px; overflow-y: auto;">
								<div id="revisionList">
									<!-- ?숈쟻?쇰줈 ?앹꽦??-->
								</div>

								<!-- ?붾낫湲?濡쒕뵫 ?쒖떆 -->
								<div id="revisionLoadMore" class="text-center py-3 d-none">
									<div class="spinner-border spinner-border-sm text-secondary" role="status">
										<span class="visually-hidden">濡쒕뵫以?..</span>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</form>
		</div>
		<div class="member-popup-footer">
			<div class="d-flex gap-2">
				<button type="button" class="btn btn-secondary flex-fill" onclick="window.close()">취소</button>
				<button type="button" class="btn btn-primary flex-fill" id="btnSaveMember">저장</button>
			</div>
		</div>
	</div>
</div>

<div class="modal fade" id="transferOrgModal" tabindex="-1" aria-labelledby="transferOrgModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="transferOrgModalLabel">?뚯넚援먰쉶 異붽?</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<form id="transferOrgForm">
					<input type="hidden" id="transfer_org_idx" name="transfer_org_idx">
					<input type="hidden" id="transfer_member_idx" name="transfer_member_idx">

					<div class="row">
						<div class="col-md-4 mb-3">
							<label for="transfer_region" class="form-label">吏??/label>
							<input type="text" class="form-control" id="transfer_region" name="transfer_region" placeholder="" required>
						</div>
						<div class="col-md-4 mb-3">
							<label for="transfer_name" class="form-label">援먰쉶紐?/label>
							<input type="text" class="form-control" id="transfer_name" name="transfer_name" placeholder="" required>
						</div>
						<div class="col-md-4 mb-3">
							<label for="pastor_name" class="form-label">?댁엫紐⑹궗紐?/label>
							<input type="text" class="form-control" id="pastor_name" name="pastor_name" placeholder="">
						</div>
					</div>

					<div class="row">
						<div class="col-md-4 mb-3">
							<label for="contact_person" class="form-label">?대떦??/label>
							<input type="text" class="form-control" id="contact_person" name="contact_person" placeholder="">
						</div>
						<div class="col-md-4 mb-3">
							<label for="contact_phone" class="form-label">?곕씫泥?/label>
							<input type="tel" class="form-control" id="contact_phone" name="contact_phone" placeholder="010-0000-0000">
						</div>
						<div class="col-md-4 mb-3">
							<label for="contact_email" class="form-label">?대찓??/label>
							<input type="email" class="form-control" id="contact_email" name="contact_email" placeholder="OOO@OOO.com">
						</div>
					</div>

					<div class="row">
						<div class="col-12 mb-3">
							<label for="transfer_description" class="form-label">援먰쉶?뚭컻</label>
							<textarea class="form-control" id="transfer_description" name="transfer_description" rows="3" placeholder="援먰쉶?????媛꾨떒???뚭컻瑜??낅젰?섏꽭??></textarea>
						</div>
					</div>

					<div class="row">
						<div class="col-12 mb-3">
							<label for="org_tags" class="form-label">?쒓렇</label>
							<select class="form-select" id="org_tags" name="org_tags" multiple style="width: 100%"></select>
							<div class="form-text">
								?낅젰 ???쇳몴(')瑜??낅젰?섏꽭??
							</div>
						</div>
					</div>
				</form>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">痍⑥냼</button>
				<button type="button" class="btn btn-primary" id="saveTransferOrgBtn">???/button>
			</div>
		</div>
	</div>
</div>

<!-- ?뚯넚援먰쉶 ??젣 ?뺤씤 紐⑤떖 -->
<div class="modal fade" id="deleteTransferOrgModal" tabindex="-1" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title">?뚯넚援먰쉶 ??젣</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
			</div>
			<div class="modal-body">
				<p>???뚯넚援먰쉶 ?뺣낫瑜???젣?섏떆寃좎뒿?덇퉴?</p>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">痍⑥냼</button>
				<button type="button" class="btn btn-danger" id="confirmDeleteTransferOrgBtn">??젣</button>
			</div>
		</div>
	</div>
</div>

<!-- 寃곗뿰援먰쉶 ?먮룞留ㅼ묶 紐⑤떖 -->
<div class="modal fade" id="autoMatchChurchModal" tabindex="-1" aria-labelledby="autoMatchChurchModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-xl">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="autoMatchChurchModalLabel">寃곗뿰援먰쉶 ?먮룞留ㅼ묶</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<!-- 寃???곸뿭 -->
				<div class="row mb-3">
					<div class="col-md-3">
						<select class="form-select" id="matchSearchType">
							<option value="church_name">援먰쉶紐?/option>
							<option value="pastor_name">?댁엫紐⑹궗</option>
							<option value="region">吏??/option>
							<option value="tag">?쒓렇</option>
						</select>
					</div>
					<div class="col-md-6">
						<input type="text" class="form-control" id="matchSearchKeyword" placeholder="寃?됱뼱瑜??낅젰?섏꽭??>
					</div>
					<div class="col-md-3">
						<div class="btn-group w-100">
							<button type="button" class="btn btn-primary" id="searchMatchChurchBtn">
								<i class="bi bi-search"></i> 寃??
							</button>
							<button type="button" class="btn btn-success" id="autoMatchByRegionBtn">
								<i class="bi bi-geo-alt"></i> ?숈씪吏???먮룞留ㅼ묶
							</button>
						</div>
					</div>
				</div>

				<!-- 寃곌낵 紐⑸줉 ?곸뿭 -->
				<div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
					<table class="table table-hover">
						<thead class="sticky-top bg-white">
						<tr>
							<th style="width: 50px;">
								<input type="checkbox" id="selectAllMatchChurch" class="form-check-input">
							</th>
							<th>援먰쉶紐?/th>
							<th>?댁엫紐⑹궗</th>
							<th>吏??/th>
							<th>?쒓렇</th>
						</tr>
						</thead>
						<tbody id="matchChurchListBody">
						<tr>
							<td colspan="5" class="text-center text-muted py-4">
								寃??踰꾪듉???대┃?섍굅???숈씪吏???먮룞留ㅼ묶???ㅽ뻾?섏꽭??
							</td>
						</tr>
						</tbody>
					</table>
				</div>

				<!-- ?좏깮 ?뺣낫 -->
				<div class="alert alert-info mt-3" id="matchSelectionInfo" style="display: none;">
					<i class="bi bi-info-circle"></i>
					<span id="matchSelectedCount">0</span>媛쒖쓽 援먰쉶媛 ?좏깮?섏뿀?듬땲??
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">痍⑥냼</button>
				<button type="button" class="btn btn-primary" id="confirmMatchChurchBtn" disabled>
					<i class="bi bi-plus-lg"></i> 異붽?
				</button>
			</div>
		</div>
	</div>
</div>
<!-- 寃곗뿰援먰쉶 異붿쿇 臾몄옄 ?꾩넚 紐⑤떖 -->
<div class="modal fade" id="sendOfferModal" tabindex="-1" aria-labelledby="sendOfferModalLabel" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="sendOfferModalLabel">
					<i class="bi bi-chat-dots"></i> ?뚯썝?먭쾶 援먰쉶異붿쿇
				</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<div class="alert alert-info mb-3">
					<small>
						?꾨옒??URL???ы븿???덈궡 硫붿떆吏瑜??꾨떖?⑸땲??
					</small>
				</div>

				<div class="border rounded p-3 bg-light">
					<p class="mb-2"><strong>寃곗뿰援먰쉶 異붿쿇</strong></p>
					<div class="input-group mb-2">
						<input type="text" class="form-control" id="offerUrlInput" readonly>
						<button class="btn btn-outline-secondary" type="button" id="copyPassResetBtn">
							<i class="bi bi-arrow-clockwise"></i> 媛깆떊
						</button>
						<button class="btn btn-outline-secondary" type="button" id="copyOfferUrlBtn">
							<i class="bi bi-clipboard"></i> 蹂듭궗
						</button>
					</div>
				</div>

				<div class="mt-3 text-muted small">
					<i class="bi bi-info-circle"></i>
					??留곹겕??72?쒓컙 ?숈븞 ?좏슚?⑸땲??
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">?リ린</button>
				<button type="button" class="btn btn-primary" id="sendOfferSmsBtn">
					<i class="bi bi-send"></i> 臾몄옄諛쒖넚
				</button>
			</div>
		</div>
	</div>
</div>

<!-- ?뚯넚援먰쉶???뚯썝?뺣낫 ?꾨떖 紐⑤떖 -->
<div class="modal fade" id="sendMemberInfoModal" tabindex="-1" aria-labelledby="sendMemberInfoModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="sendMemberInfoModalLabel">
					<i class="bi bi-envelope"></i> ?뚯넚援먰쉶???뚯썝?뺣낫 ?꾨떖
				</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<div class="mb-3">
					<label for="emailMessage" class="form-label">?꾩넚 ?댁슜</label> <small class="text-danger">* 硫붿씪 ?댁슜 ?섏젙 媛??/small>
					<textarea class="form-control" id="emailMessage" rows="20"></textarea>
				</div>
				<div class="row">
					<div class="col-9">
						<input type="text" class="form-control" id="churchEmail" placeholder="?대찓??二쇱냼瑜??낅젰?섏꽭??>
					</div>
					<div class="col-3">
						<button type="button" class="btn btn-primary w-100" id="sendEmailBtn">
							<i class="bi bi-send"></i> 硫붿씪?꾩넚
						</button>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<!-- 硫붾え 異붽?/?섏젙 紐⑤떖 -->
<div class="modal fade" id="memoModal" tabindex="-1" aria-labelledby="memoModalLabel" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="memoModalLabel">硫붾え 異붽?</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<div class="mb-3">
					<label for="memoType" class="form-label">??ぉ</label>
					<select class="form-select" id="memoType" required>
						<option value="">??ぉ ?좏깮</option>
					</select>
				</div>
				<div class="mb-3">
					<label for="memoContent" class="form-label">?댁슜</label>
					<textarea class="form-control" id="memoContent" rows="5" placeholder="?댁슜???낅젰?섏꽭?? required></textarea>
				</div>
				<div class="mb-3">
					<label for="memoDate" class="form-label">?좎쭨 (?좏깮?ы빆)</label>
					<input type="date" class="form-control" id="memoDate">
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">痍⑥냼</button>
				<button type="button" class="btn btn-primary" id="saveMemoBtn">???/button>
			</div>
		</div>
	</div>
</div>

<!-- 硫붾え ??젣 ?뺤씤 紐⑤떖 -->
<div class="modal fade" id="deleteMemoModal" tabindex="-1" aria-labelledby="deleteMemoModalLabel" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="deleteMemoModalLabel">硫붾え ??젣</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<p>??硫붾え瑜???젣?섏떆寃좎뒿?덇퉴?</p>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">痍⑥냼</button>
				<button type="button" class="btn btn-danger" id="confirmDeleteMemoBtn">??젣</button>
			</div>
		</div>
	</div>
</div>

<!-- ??꾨씪??異붽?/?섏젙 紐⑤떖 -->
<div class="modal fade" id="timelineModal" tabindex="-1" aria-labelledby="timelineModalLabel" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="timelineModalLabel">??꾨씪??異붽?</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<form id="timelineForm">
					<input type="hidden" id="timeline_idx" name="idx">
					<input type="hidden" id="timeline_member_idx" name="member_idx">

					<div class="mb-3">
						<label for="timeline_type" class="form-label">??ぉ <span class="text-danger">*</span></label>
						<select class="form-select" id="timeline_type" name="timeline_type" required>
							<option value="">??ぉ???좏깮?섏꽭??/option>
						</select>
					</div>

					<div class="mb-3">
						<label for="timeline_date" class="form-label">?좎쭨 <span class="text-danger">*</span></label>
						<input type="date" class="form-control" id="timeline_date" name="timeline_date" required>
					</div>

					<div class="mb-3">
						<label for="timeline_content" class="form-label">?댁슜</label>
						<textarea class="form-control" id="timeline_content" name="timeline_content" rows="3" placeholder="?댁슜???낅젰?섏꽭??></textarea>
					</div>
				</form>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">痍⑥냼</button>
				<button type="button" class="btn btn-primary" id="saveTimelineBtn">???/button>
			</div>
		</div>
	</div>
</div>

<!-- ??꾨씪????젣 ?뺤씤 紐⑤떖 -->
<div class="modal fade" id="deleteTimelineModal" tabindex="-1" aria-labelledby="deleteTimelineModalLabel" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="deleteTimelineModalLabel">??꾨씪????젣</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<p>????꾨씪?몄쓣 ??젣?섏떆寃좎뒿?덇퉴?</p>
				<p class="text-danger">???묒뾽? ?섎룎由????놁뒿?덈떎.</p>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">痍⑥냼</button>
				<button type="button" class="btn btn-danger" id="confirmDeleteTimelineBtn">??젣</button>
			</div>
		</div>
	</div>
</div>

<?php include APPPATH . 'views/footer_noframe.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/croppie@2.6.5/croppie.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="/assets/js/custom/select2.sortable.min.js?<?php echo WB_VERSION; ?>"></script>
<script src="https://unpkg.com/d3@7"></script>
<script src="https://cdn.jsdelivr.net/npm/family-chart@0.9.0/dist/family-chart.min.js"></script>

<script>
	window.isMemberPopup = true;
	window.memberPageData = {
		baseUrl: '<?php echo base_url(); ?>'
	};
	window.popupMemberIdx = '<?php echo $member_idx; ?>';
	window.popupOrgId = '<?php echo $org_id; ?>';
	window.popupMemberAreas = <?php echo json_encode($member_areas); ?>;
</script>
<script src="/assets/js/member.js?<?php echo WB_VERSION; ?>"></script>
<script src="/assets/js/member-family.js?<?php echo WB_VERSION; ?>"></script>
