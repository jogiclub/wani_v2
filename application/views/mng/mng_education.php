<?php
$this->load->view('mng/header');
?>
<!-- ParamQuery CSS -->
<link rel="stylesheet" href="/assets/css/custom/pqgrid.min.css">
<!-- Fancytree CSS -->
<link rel="stylesheet" href="/assets/css/custom/ui.fancytree.min.css?<?php echo WB_VERSION; ?>">

<div class="container-fluid pt-2 pb-2">
	<nav class="mb-1" aria-label="breadcrumb">
		<ol class="breadcrumb mb-0">
			<li class="breadcrumb-item"><a href="/mng">관리자홈</a></li>
			<li class="breadcrumb-item"><a href="#!">ORG</a></li>
			<li class="breadcrumb-item active">양육관리</li>
		</ol>
	</nav>
	<div class="col-12 my-1 d-flex align-items-center justify-content-between mb-3">
		<h3 class="page-title col-12 my-1">양육관리</h3>
	</div>

	<!-- Split.js를 위한 단일 컨테이너 -->
	<div class="split-container">
		<!-- 왼쪽: 카테고리 + 조직 트리 -->
		<div class="split-pane" id="left-pane">
			<div class="card">
				<div class="card-body card-height p-0 position-relative">
					<!-- 트리 스피너 -->
					<div id="treeSpinner" class="d-flex justify-content-center align-items-center position-absolute w-100 h-100" style="z-index: 1000; background: rgba(255, 255, 255, 0.8);">
						<div class="text-center">
							<div class="spinner-border text-primary mb-2" role="status">
								<span class="visually-hidden">로딩 중...</span>
							</div>
							<div class="small text-muted">트리 로딩 중...</div>
						</div>
					</div>
					<div id="categoryTree" class="tree-container"></div>
				</div>
			</div>
		</div>

		<!-- 오른쪽: 양육 목록 -->
		<div class="split-pane" id="right-pane">
			<div class="card">
				<div class="card-header">
					<div class="row flex-column flex-lg-row">
						<div class="col-12 col-lg-6 d-flex align-items-center">
							<h5 class="mb-0 text-truncate" id="selectedNodeName">
								<i class="bi bi-book"></i> 조직을 선택해주세요
							</h5>
							<small class="ms-3 text-muted">총 <span id="totalEduCount">0</span>개</small>
						</div>
						<div class="col-12 col-lg-6 d-flex align-items-center justify-content-end">
							<!-- 검색 기능 UI -->
							<div class="row g-1">
								<div class="col-auto">
									<input type="date" id="search_date" class="form-control form-control-sm" style="width: 130px;">
								</div>
								<!-- 진행요일 Dropdown -->
								<div class="col-auto dropdown">
									<button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="search_day_btn" data-bs-toggle="dropdown" aria-expanded="false">
										진행요일
									</button>
									<ul class="dropdown-menu" id="search_day_menu" aria-labelledby="search_day_btn">
										<li><a class="dropdown-item" href="#"><input type="checkbox" value="월요일" class="form-check-input me-2">월요일</a></li>
										<li><a class="dropdown-item" href="#"><input type="checkbox" value="화요일" class="form-check-input me-2">화요일</a></li>
										<li><a class="dropdown-item" href="#"><input type="checkbox" value="수요일" class="form-check-input me-2">수요일</a></li>
										<li><a class="dropdown-item" href="#"><input type="checkbox" value="목요일" class="form-check-input me-2">목요일</a></li>
										<li><a class="dropdown-item" href="#"><input type="checkbox" value="금요일" class="form-check-input me-2">금요일</a></li>
										<li><a class="dropdown-item" href="#"><input type="checkbox" value="토요일" class="form-check-input me-2">토요일</a></li>
										<li><a class="dropdown-item" href="#"><input type="checkbox" value="주일" class="form-check-input me-2">주일</a></li>
									</ul>
								</div>
								<!-- 진행시간 Dropdown -->
								<div class="col-auto dropdown">
									<button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="search_time_btn" data-bs-toggle="dropdown" aria-expanded="false">
										진행시간
									</button>
									<ul class="dropdown-menu" id="search_time_menu" aria-labelledby="search_time_btn">
										<!-- 자바스크립트로 동적 생성 -->
									</ul>
								</div>
								<!-- 연령대 Dropdown -->
								<div class="col-auto dropdown">
									<button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="search_age_btn" data-bs-toggle="dropdown" aria-expanded="false">
										연령대
									</button>
									<ul class="dropdown-menu" id="search_age_menu" aria-labelledby="search_age_btn">
										<li><a class="dropdown-item" href="#"><input type="checkbox" value="10s" class="form-check-input me-2">10대</a></li>
										<li><a class="dropdown-item" href="#"><input type="checkbox" value="20s" class="form-check-input me-2">20대</a></li>
										<li><a class="dropdown-item" href="#"><input type="checkbox" value="30s" class="form-check-input me-2">30대</a></li>
										<li><a class="dropdown-item" href="#"><input type="checkbox" value="40s" class="form-check-input me-2">40대</a></li>
										<li><a class="dropdown-item" href="#"><input type="checkbox" value="50s" class="form-check-input me-2">50대</a></li>
										<li><a class="dropdown-item" href="#"><input type="checkbox" value="60s" class="form-check-input me-2">60대 이상</a></li>
									</ul>
								</div>
								<!-- 성별 Dropdown -->
								<div class="col-auto dropdown">
									<button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="search_gender_btn" data-bs-toggle="dropdown" aria-expanded="false">
										성별
									</button>
									<ul class="dropdown-menu" id="search_gender_menu" aria-labelledby="search_gender_btn">
										<li><a class="dropdown-item" href="#"><input type="checkbox" value="male" class="form-check-input me-2">남</a></li>
										<li><a class="dropdown-item" href="#"><input type="checkbox" value="female" class="form-check-input me-2">여</a></li>
									</ul>
								</div>
								<div class="col-auto">
									<input type="text" id="search_keyword" class="form-control form-control-sm" placeholder="카테고리, 장소, 담당자, 양육명">
								</div>
								<div class="col-auto">
									<button id="btn_search" class="btn btn-sm btn-primary">검색</button>
								</div>
								<div class="col-auto">
									<button id="btn_reset" class="btn btn-sm btn-outline-secondary">초기화</button>
								</div>
							</div>
						</div>
					</div>
				</div>

				<div class="card-body card-height p-0 position-relative">
					<!-- 그리드 스피너 -->
					<div id="gridSpinner" class="d-none justify-content-center align-items-center position-absolute w-100 h-100" style="z-index: 1000; background: rgba(255, 255, 255, 0.8);">
						<div class="text-center">
							<div class="spinner-border text-primary mb-2" role="status">
								<span class="visually-hidden">로딩 중...</span>
							</div>
							<div class="small text-muted">양육 정보 로딩 중...</div>
						</div>
					</div>
					<!-- ParamQuery Grid -->
					<div id="eduGrid"></div>
				</div>
			</div>
		</div>
	</div>
</div>

<!-- 양육 상세 Offcanvas -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="eduDetailOffcanvas" style="width: 600px;">
	<div class="offcanvas-header">
		<h5 class="offcanvas-title">양육 상세</h5>
		<button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
	</div>
	<div class="offcanvas-body">
		<div id="eduDetailContent">
			<div class="mb-3">
				<label class="form-label fw-bold">양육명</label>
				<p class="form-control-plaintext" id="detail_edu_name"></p>
			</div>
			<div class="mb-3">
				<label class="form-label fw-bold">소속 조직</label>
				<p class="form-control-plaintext" id="detail_org_name"></p>
			</div>
			<div class="mb-3">
				<label class="form-label fw-bold">카테고리</label>
				<p class="form-control-plaintext" id="detail_category_name"></p>
			</div>
			<div class="mb-3">
				<label class="form-label fw-bold">장소</label>
				<p class="form-control-plaintext" id="detail_edu_location"></p>
			</div>
			<div class="row mb-3">
				<div class="col-md-6">
					<label class="form-label fw-bold">시작일</label>
					<p class="form-control-plaintext" id="detail_edu_start_date"></p>
				</div>
				<div class="col-md-6">
					<label class="form-label fw-bold">종료일</label>
					<p class="form-control-plaintext" id="detail_edu_end_date"></p>
				</div>
			</div>
			<div class="mb-3">
				<label class="form-label fw-bold">진행 요일</label>
				<p class="form-control-plaintext" id="detail_edu_days"></p>
			</div>
			<div class="mb-3">
				<label class="form-label fw-bold">진행 시간</label>
				<p class="form-control-plaintext" id="detail_edu_times"></p>
			</div>
			<div class="mb-3">
				<label class="form-label fw-bold">담당자</label>
				<p class="form-control-plaintext" id="detail_edu_leader"></p>
			</div>
			<div class="row mb-3">
				<div class="col-md-6">
					<label class="form-label fw-bold">연락처</label>
					<p class="form-control-plaintext" id="detail_edu_leader_phone"></p>
				</div>
				<div class="col-md-3">
					<label class="form-label fw-bold">연령대</label>
					<p class="form-control-plaintext" id="detail_edu_leader_age"></p>
				</div>
				<div class="col-md-3">
					<label class="form-label fw-bold">성별</label>
					<p class="form-control-plaintext" id="detail_edu_leader_gender"></p>
				</div>
			</div>
			<div class="mb-3">
				<label class="form-label fw-bold">설명</label>
				<p class="form-control-plaintext" id="detail_edu_desc"></p>
			</div>
			<div class="row mb-3">
				<div class="col-md-6">
					<label class="form-label fw-bold">수강료</label>
					<p class="form-control-plaintext" id="detail_edu_fee"></p>
				</div>
				<div class="col-md-6">
					<label class="form-label fw-bold">정원</label>
					<p class="form-control-plaintext" id="detail_edu_capacity"></p>
				</div>
			</div>
			<div class="mb-3">
				<label class="form-label fw-bold">공개 여부</label>
				<p class="form-control-plaintext" id="detail_public_yn"></p>
			</div>
			<div class="mb-3" id="detail_zoom_section" style="display: none;">
				<label class="form-label fw-bold">ZOOM URL</label>
				<p class="form-control-plaintext"><a href="#" id="detail_zoom_url" target="_blank"></a></p>
			</div>
			<div class="mb-3" id="detail_youtube_section" style="display: none;">
				<label class="form-label fw-bold">YouTube URL</label>
				<p class="form-control-plaintext"><a href="#" id="detail_youtube_url" target="_blank"></a></p>
			</div>
		</div>
	</div>
</div>

<!-- 신청자 목록 Offcanvas -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="applicantOffcanvas" style="width: 700px;">
	<div class="offcanvas-header">
		<h5 class="offcanvas-title">신청자 목록</h5>
		<button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
	</div>
	<div class="offcanvas-body">
		<div class="mb-3">
			<h6 id="applicant_edu_name"></h6>
			<p class="text-muted mb-0">총 <span id="applicant_total_count">0</span>명</p>
		</div>
		<div id="applicantGrid"></div>
	</div>
</div>

<?php $this->load->view('mng/footer'); ?>

<!-- Split.js -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/split.js/1.6.5/split.min.js"></script>
<!-- Fancytree -->
<script src="/assets/js/custom/jquery.fancytree-all-deps.min.js"></script>
<!-- ParamQuery Grid -->
<script src="/assets/js/custom/pqgrid.min.js?<?php echo WB_VERSION; ?>"></script>
<!-- 양육관리 JavaScript -->
<script src="/assets/js/mng_education.js?<?php echo WB_VERSION; ?>"></script>
