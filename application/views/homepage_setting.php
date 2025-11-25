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
						<i class="bi bi-check-circle"></i> 저장
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
							<!-- 홈페이지 로고 #1 -->
							<div class="mb-4">
								<label class="form-label">홈페이지 로고 #1</label>
								<div class="d-flex align-items-center gap-3">
									<div class="logo-preview">
										<?php if (!empty($homepage_setting['logo1'])): ?>
											<img src="<?php echo $homepage_setting['logo1']; ?>" alt="로고 1"
												 class="border" style="max-width: 200px; max-height: 100px; object-fit: contain;" id="logo1Preview">
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
										<div class="form-text">JPG, PNG, GIF 파일 (최대 2MB)</div>
										<input type="hidden" id="logo1_current" name="logo1_current"
											   value="<?php echo htmlspecialchars($homepage_setting['logo1'] ?? ''); ?>">
									</div>
								</div>
							</div>

							<!-- 홈페이지 로고 #2 -->
							<div class="mb-4">
								<label class="form-label">홈페이지 로고 #2</label>
								<div class="d-flex align-items-center gap-3">
									<div class="logo-preview">
										<?php if (!empty($homepage_setting['logo2'])): ?>
											<img src="<?php echo $homepage_setting['logo2']; ?>" alt="로고 2"
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
										<div class="form-text">JPG, PNG, GIF 파일 (최대 2MB)</div>
										<input type="hidden" id="logo2_current" name="logo2_current"
											   value="<?php echo htmlspecialchars($homepage_setting['logo2'] ?? ''); ?>">
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>

				<div class="col-lg-4 mt-4 mt-lg-0">
					<div class="card">
						<div class="card-header">
							<h5 class="card-title mb-0">테마 선택</h5>
						</div>
						<div class="card-body">
							<div class="row g-3">
								<div class="col-12">
									<input type="radio" class="btn-check" name="theme" id="theme1" value="1"
										<?php echo (empty($homepage_setting['theme']) || $homepage_setting['theme'] == '1') ? 'checked' : ''; ?>>
									<label class="btn btn-outline-primary w-100 d-flex flex-column align-items-center p-3" for="theme1">
										<i class="bi bi-1-circle fs-1 mb-2"></i>
										<span>테마 1</span>
										<small class="text-muted">클래식 스타일</small>
									</label>
								</div>
								<div class="col-12">
									<input type="radio" class="btn-check" name="theme" id="theme2" value="2"
										<?php echo (!empty($homepage_setting['theme']) && $homepage_setting['theme'] == '2') ? 'checked' : ''; ?>>
									<label class="btn btn-outline-primary w-100 d-flex flex-column align-items-center p-3" for="theme2">
										<i class="bi bi-2-circle fs-1 mb-2"></i>
										<span>테마 2</span>
										<small class="text-muted">모던 스타일</small>
									</label>
								</div>
								<div class="col-12">
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



<?php $this->load->view('footer'); ?>
<script src="/assets/js/homepage_setting.js?<?php echo WB_VERSION; ?>"></script>
