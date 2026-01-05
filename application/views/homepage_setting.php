<?php $this->load->view('header'); ?>

<div class="container pt-2 pb-2">
	<nav class="mb-1" aria-label="breadcrumb">
		<ol class="breadcrumb mb-0">
			<li class="breadcrumb-item"><a href="#!">홈</a></li>
			<li class="breadcrumb-item"><a href="#!">HOMEPAGE</a></li>
			<li class="breadcrumb-item active">홈페이지 기본설정</li>
		</ol>
	</nav>

	<form id="homepageSettingForm">
		<div class="col-12 my-1 d-flex align-items-center justify-content-between mb-3">
			<h3 class="page-title mb-0">홈페이지 기본설정</h3>
			<div class="col-6 my-1">
				<div class="text-end">
					<button type="submit" class="btn btn-primary">
						<i class="bi bi-check-circle"></i> 저장 및 홈페이지적용
					</button>
				</div>
			</div>
		</div>

		<?php if (isset($current_org) && $current_org): ?>
			<div class="row">
				<div class="col-lg-8">
					<div class="card">
						<div class="card-header">
							<h5 class="card-title mb-0">기본 정보</h5>
						</div>
						<div class="card-body">
							<input type="hidden" id="org_id" name="org_id" value="<?php echo $current_org['org_id']; ?>">

							<!-- 조직 코드 (읽기 전용) -->
							<div class="mb-3">
								<label for="org_code_display" class="form-label">조직 코드</label>
								<input type="text" class="form-control" id="org_code_display"
									   value="<?php echo htmlspecialchars($selected_org_detail['org_code'] ?? ''); ?>" readonly>
								<div class="form-text">조직코드가 없는 경우, <a href="#" class="kakao-channel">채널</a>을 통해 문의바랍니다.</div>
							</div>

							<!-- 홈페이지 이름 -->
							<div class="mb-3">
								<label for="homepage_name" class="form-label">홈페이지 이름</label>
								<input type="text" class="form-control" id="homepage_name" name="homepage_name"
									   value="<?php echo htmlspecialchars($homepage_setting['homepage_name'] ?? ''); ?>"
									   placeholder="예) 와니교회, 와니학교">
							</div>

							<!-- 홈페이지 도메인주소 -->
							<div class="mb-3">
								<label for="homepage_domain" class="form-label">홈페이지 도메인주소</label>
								<input type="text" class="form-control" id="homepage_domain" name="homepage_domain"
									   value="<?php echo htmlspecialchars($homepage_setting['homepage_domain'] ?? ''); ?>"
									   placeholder="예) www.wani.im, wani.im, my.wani.im">
								<div class="form-text">도메인 주소만 입력해주세요 (http:// 또는 https:// 제외)</div>
							</div>

							<div class="mb-3">
								<label for="homepage_domain" class="form-label">홈페이지 URL</label>
								<ul>
									<li><a href="http://<?php echo htmlspecialchars($selected_org_detail['org_code'] ?? ''); ?>.wani.im" target="_blank"><?php echo htmlspecialchars($selected_org_detail['org_code'] ?? ''); ?>.wani.im</a></li>
									<li><a href="https://<?php echo htmlspecialchars($homepage_setting['homepage_domain'] ?? ''); ?>" target="_blank"><?php echo htmlspecialchars($homepage_setting['homepage_domain'] ?? ''); ?></a></li>
								</ul>
							</div>

							<!-- 홈페이지 로고 -->
							<div class="mb-4">
								<label class="form-label">홈페이지 로고</label>
								<div class="d-flex align-items-center gap-3">
									<div class="logo-preview">
										<?php if (!empty($homepage_setting['logo1'])): ?>
											<img src="<?php echo $homepage_setting['logo1']; ?>?v=<?php echo time(); ?>" alt="로고" class="border" style="max-width: 200px; max-height: 100px; object-fit: contain;" id="logo1Preview">
										<?php else: ?>
											<div class="bg-light border d-flex align-items-center justify-content-center"
												 style="width: 200px; height: 100px;" id="logo1Preview">
												<i class="bi bi-image text-muted fs-1"></i>
											</div>
										<?php endif; ?>
									</div>
									<div class="flex-grow-1">
										<div class="input-group">
											<input type="file" class="form-control" id="logo1File" accept=".jpg,.jpeg,.png,.gif">
											<button type="button" class="btn btn-primary" id="uploadLogo1Btn">
												<i class="bi bi-upload"></i> 업로드
											</button>
										</div>
										<div class="form-check my-2">
											<input class="form-check-input" type="checkbox" value="Y" id="checkLogoColor"
												<?php echo (!empty($homepage_setting['logo_color_change']) && $homepage_setting['logo_color_change'] === 'Y') ? 'checked' : ''; ?>>
											<label class="form-check-label" for="checkLogoColor">
												스크롤에 따라 로고 색상 변경
											</label>
										</div>
										<div class="form-text">JPG, PNG, GIF 파일 (최대 2MB)</div>
										<input type="hidden" id="logo1_current" name="logo1_current" value="<?php echo htmlspecialchars($homepage_setting['logo1'] ?? ''); ?>">
									</div>
								</div>
							</div>

							<!-- 파비콘 -->
							<div class="mb-4">
								<label class="form-label">파비콘</label>
								<div class="d-flex align-items-center gap-3">
									<div class="logo-preview">
										<?php if (!empty($homepage_setting['logo2'])): ?>
											<img src="<?php echo $homepage_setting['logo2']; ?>?v=<?php echo time(); ?>" alt="파비콘"
												 class="border" style="max-width: 200px; max-height: 100px; object-fit: contain;" id="logo2Preview">
										<?php else: ?>
											<div class="bg-light border d-flex align-items-center justify-content-center"
												 style="width: 200px; height: 100px;" id="logo2Preview">
												<i class="bi bi-image text-muted fs-1"></i>
											</div>
										<?php endif; ?>
									</div>
									<div class="flex-grow-1">
										<div class="input-group">
											<input type="file" class="form-control" id="logo2File" accept=".jpg,.jpeg,.png,.gif">
											<button type="button" class="btn btn-primary" id="uploadLogo2Btn">
												<i class="bi bi-upload"></i> 업로드
											</button>
										</div>
										<div class="form-text">PNG파일, 투명이미지, 정사각형 필요</div>
										<input type="hidden" id="logo2_current" name="logo2_current" value="<?php echo htmlspecialchars($homepage_setting['logo2'] ?? ''); ?>">
									</div>
								</div>
							</div>

							<!-- 카드이미지 -->
							<div class="mb-4">
								<label class="form-label">카드이미지</label>
								<div class="d-flex align-items-center gap-3">
									<div class="logo-preview">
										<?php if (!empty($homepage_setting['logo3'])): ?>
											<img src="<?php echo $homepage_setting['logo3']; ?>?v=<?php echo time(); ?>" alt="카드이미지"
												 class="border" style="max-width: 200px; max-height: 100px; object-fit: contain;" id="logo3Preview">
										<?php else: ?>
											<div class="bg-light border d-flex align-items-center justify-content-center"
												 style="width: 200px; height: 100px;" id="logo3Preview">
												<i class="bi bi-image text-muted fs-1"></i>
											</div>
										<?php endif; ?>
									</div>
									<div class="flex-grow-1">
										<div class="input-group">
											<input type="file" class="form-control" id="logo3File" accept=".jpg,.jpeg,.png,.gif">
											<button type="button" class="btn btn-primary" id="uploadLogo3Btn">
												<i class="bi bi-upload"></i> 업로드
											</button>
										</div>
										<div class="form-text">SNS 공유 시 표시되는 이미지 (1200x630 권장)</div>
										<input type="hidden" id="logo3_current" name="logo3_current" value="<?php echo htmlspecialchars($homepage_setting['logo3'] ?? ''); ?>">
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="col-lg-4 mt-4 mt-lg-0">
					<div class="card">
						<div class="card-header d-flex justify-content-between align-items-center">
							<h5 class="card-title mb-0">관련 링크</h5>
							<button type="button" class="btn btn-sm btn-primary" id="btnAddRelatedLink">
								<i class="bi bi-plus-lg"></i> 추가
							</button>
						</div>
						<div class="card-body">
							<div class="table-responsive">
								<table class="table table-hover table-sm" id="relatedLinkTable">
									<thead class="">
									<tr>
										<th style="width: 50px;">아이콘</th>
										<th>링크명</th>
										<th>URL</th>
										<th style="width: 100px;">관리</th>
									</tr>
									</thead>
									<tbody id="relatedLinkBody">
									</tbody>
								</table>
							</div>
							<div id="noRelatedLinks" class="text-center text-muted py-4" style="display: none;">
								<i class="bi bi-link-45deg fs-1"></i>
								<p class="mb-0 mt-2">등록된 링크가 없습니다.</p>
							</div>
						</div>
					</div>
				</div>


				<div class="col-lg-12 mt-4">
					<div class="card">
						<div class="card-header">
							<h5 class="card-title mb-0">테마 선택</h5>
						</div>
						<div class="card-body">
							<div class="row g-3">
								<div class="col-12 col-lg-4">
									<input type="radio" class="btn-check" name="theme" id="theme1" value="1"
										<?php echo (empty($homepage_setting['theme']) || $homepage_setting['theme'] == '1') ? 'checked' : ''; ?>>
									<label class="btn btn-outline-primary w-100 d-flex flex-column align-items-center p-3" for="theme1">
										<i class="bi bi-1-circle fs-1 mb-2"></i>
										<span>테마 1</span>
										<small class="text-muted">클래식 스타일</small>
									</label>
								</div>
								<div class="col-12 col-lg-4">
									<input type="radio" class="btn-check" name="theme" id="theme2" value="2"
										<?php echo (!empty($homepage_setting['theme']) && $homepage_setting['theme'] == '2') ? 'checked' : ''; ?>>
									<label class="btn btn-outline-primary w-100 d-flex flex-column align-items-center p-3" for="theme2">
										<i class="bi bi-2-circle fs-1 mb-2"></i>
										<span>테마 2</span>
										<small class="text-muted">모던 스타일</small>
									</label>
								</div>
								<div class="col-12 col-lg-4">
									<input type="radio" class="btn-check" name="theme" id="theme3" value="3"
										<?php echo (!empty($homepage_setting['theme']) && $homepage_setting['theme'] == '3') ? 'checked' : ''; ?>>
									<label class="btn btn-outline-primary w-100 d-flex flex-column align-items-center p-3" for="theme3">
										<i class="bi bi-3-circle fs-1 mb-2"></i>
										<span>테마 3</span>
										<small class="text-muted">미니멀 스타일</small>
									</label>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		<?php else: ?>
			<div class="alert alert-warning">
				<i class="bi bi-exclamation-triangle"></i> 조직을 먼저 선택해주세요.
			</div>
		<?php endif; ?>
	</form>
</div>

<!-- 관련 링크 수정 모달 -->
<div class="modal fade" id="relatedLinkModal" tabindex="-1" aria-labelledby="relatedLinkModalLabel" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="relatedLinkModalLabel">링크 수정</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<input type="hidden" id="edit_link_idx">
				<div class="mb-3">
					<label for="edit_link_name" class="form-label">링크명</label>
					<input type="text" class="form-control" id="edit_link_name" placeholder="예) 인스타그램, 유튜브, 네이버 블로그">
				</div>
				<div class="mb-3">
					<label for="edit_link_url" class="form-label">URL</label>
					<input type="text" class="form-control" id="edit_link_url" placeholder="https://instagram.com/yourpage">
				</div>
				<div class="mb-3">
					<label class="form-label">아이콘 이미지</label>
					<div class="d-flex align-items-center gap-3">
						<div id="edit_icon_preview" class="bg-light border d-flex align-items-center justify-content-center" style="width: 48px; height: 48px; border-radius: 8px;">
							<i class="bi bi-image text-muted"></i>
						</div>
						<div class="flex-grow-1">
							<input type="file" class="form-control form-control-sm" id="edit_icon_file" accept=".jpg,.jpeg,.png,.gif,.svg">
							<div class="form-text">JPG, PNG, GIF, SVG (최대 1MB)</div>
						</div>
					</div>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
				<button type="button" class="btn btn-primary" id="btnSaveRelatedLink">저장</button>
			</div>
		</div>
	</div>
</div>

<!-- 삭제 확인 모달 -->
<div class="modal fade" id="deleteLinkModal" tabindex="-1" aria-hidden="true">
	<div class="modal-dialog modal-sm">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title">링크 삭제</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<p>이 링크를 삭제하시겠습니까?</p>
				<input type="hidden" id="delete_link_idx">
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
				<button type="button" class="btn btn-danger" id="btnConfirmDeleteLink">삭제</button>
			</div>
		</div>
	</div>
</div>


<?php $this->load->view('footer'); ?>
<script src="/assets/js/homepage_setting.js?<?php echo WB_VERSION; ?>"></script>
