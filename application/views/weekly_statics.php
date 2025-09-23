<?php $this->load->view('header'); ?>

<!-- Attendance CSS -->
<link rel="stylesheet" href="/assets/css/weekly_statics.css?<?php echo WB_VERSION; ?>">

<!-- ParamQuery CSS -->
<link rel="stylesheet" href="/assets/css/custom/pqgrid.min.css?<?php echo WB_VERSION; ?>">

<!-- Fancytree CSS -->
<link rel="stylesheet" href="/assets/css/custom/ui.fancytree.min.css?<?php echo WB_VERSION; ?>">

<div class="container-fluid pt-2 pb-2">
	<nav class="mb-3" aria-label="breadcrumb">
		<ol class="breadcrumb mb-0">
			<li class="breadcrumb-item"><a href="#!">홈</a></li>
			<li class="breadcrumb-item"><a href="#!">MEMBER</a></li>
			<li class="breadcrumb-item active">주별통계</li>
		</ol>
	</nav>
	<div class="row align-items-center justify-content-between g-3 mb-3">
		<div class="col-12 my-1 d-flex align-items-center justify-content-between">
			<h3 class="page-title  mb-0">주별통계</h3>

			<div class="page-tabs ">
				<ul class="nav nav-pills" id="attendance-type-tabs" role="tablist">
					<li class="nav-item" role="presentation">
						<button class="nav-link active" id="tab-all" data-bs-toggle="pill" data-att-type-idx="all" type="button" role="tab" aria-selected="true">전체</button>
					</li>
					<!-- 동적으로 출석유형 탭이 여기에 추가됩니다 -->
				</ul>
			</div>
		</div>
	</div>

	<!-- Split.js를 위한 단일 컨테이너 -->
	<div class="split-container">
		<!-- 왼쪽: 그룹 트리 -->
		<div class="split-pane" id="left-pane">
			<div class="card h-100">
				<div class="card-body p-0 position-relative">
					<!-- 트리 스피너 -->
					<div id="treeSpinner" class="d-flex justify-content-center align-items-center position-absolute w-100 h-100" style="z-index: 1000; background: rgba(255, 255, 255, 0.8);">
						<div class="text-center">
							<div class="spinner-border text-primary mb-2" role="status">
								<span class="visually-hidden">로딩 중...</span>
							</div>
							<div class="small text-muted">그룹 정보 로딩 중...</div>
						</div>
					</div>
					<div id="groupTree" class="tree-container"></div>
				</div>
			</div>
		</div>

		<!-- 오른쪽: 출석 목록 -->
		<div class="split-pane" id="right-pane">
			<div class="card h-100">
				<div class="card-header">
					<div class="row flex-column flex-lg-row">
						<div class="col-12 col-lg-4 d-flex align-items-center">
							<h5 class="mb-0 text-truncate" id="selectedOrgName">
								조직을 선택해주세요
							</h5>
						</div>

						<div class="col-12 col-lg-3 d-flex justify-content-start justify-content-lg-end mt-2 mt-lg-0">
							<div class="input-group input-group-sm">
								<input type="text" class="form-control" placeholder="회원명 검색" aria-label="Member's name" aria-describedby="button-search">
								<button class="btn btn-sm btn-outline-secondary" type="button" id="button-search"><i class="bi bi-search"></i> 검색</button>
							</div>
						</div>

						<div class="col-12 col-lg-5 d-flex justify-content-start justify-content-lg-end mt-2 mt-lg-0">



							<!-- 연도 선택 컨트롤 -->
							<div class="input-group input-group-sm year-selector justify-content-start justify-content-lg-end">
								<button class="btn btn-sm btn-outline-primary" id="prevYear" type="button"><i class="bi bi-chevron-left"></i></button>
								<label class="input-group-text year-display" id="currentYear">2025</label>
								<button class="btn btn-sm btn-outline-primary" id="nextYear" type="button"><i class="bi bi-chevron-right"></i></button>
							</div>
						</div>
					</div>
				</div>
				<div class="card-body p-0 position-relative">
					<!-- 그리드 스피너 -->
					<div id="gridSpinner" class="d-none justify-content-center align-items-center position-absolute w-100 h-100" style="z-index: 1000; background: rgba(255, 255, 255, 0.8);">
						<div class="text-center">
							<div class="spinner-border text-primary mb-2" role="status">
								<span class="visually-hidden">로딩 중...</span>
							</div>
							<div class="small text-muted">출석 정보 로딩 중...</div>
						</div>
					</div>
					<!-- ParamQuery Grid가 여기에 렌더링됩니다 -->
					<div id="attendanceGrid"></div>
				</div>
			</div>
		</div>
	</div>
</div>

<?php $this->load->view('footer'); ?>

<script src="/assets/js/custom/split.min.js?<?php echo WB_VERSION; ?>"></script>
<script src="/assets/js/custom/jquery.fancytree-all-deps.min.js?<?php echo WB_VERSION; ?>"></script>
<script src="/assets/js/custom/pqgrid.min.js?<?php echo WB_VERSION; ?>"></script>

<script>
	window.attendancePageData = {
		baseUrl: '<?php echo base_url(); ?>',
		currentYear: <?php echo date('Y'); ?>
	};

	// 주별통계 메뉴 active
	$('.menu-31').addClass('active');
</script>
<script src="/assets/js/weekly_statics.js?<?php echo WB_VERSION; ?>"></script>
