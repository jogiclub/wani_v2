<?php $this->load->view('header'); ?>

<!-- Attendance CSS -->
<link rel="stylesheet" href="/assets/css/attendance.css?<?php echo date('Ymdhis'); ?>">

<!-- ParamQuery CSS -->
<link rel="stylesheet" href="http://cdnjs.cloudflare.com/ajax/libs/pqGrid/3.5.1/pqgrid.min.css">

<!-- Fancytree CSS -->
<link rel="stylesheet"
	  href="https://cdnjs.cloudflare.com/ajax/libs/jquery.fancytree/2.38.5/skin-vista/ui.fancytree.min.css">

<div class="container-fluid pt-2 pb-2">
	<nav class="mb-3" aria-label="breadcrumb">
		<ol class="breadcrumb mb-0">
			<li class="breadcrumb-item"><a href="#!">홈</a></li>
			<li class="breadcrumb-item"><a href="#!">MEMBER</a></li>
			<li class="breadcrumb-item active">출석관리</li>
		</ol>
	</nav>
	<div class="row align-items-center justify-content-between g-3 mb-3">
		<h3 class="page-title col-12 my-1">출석관리</h3>
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
					<div class="row">
						<div class="col-4 d-flex align-items-center">
							<h5 class="mb-0" id="selectedOrgName">
								<i class="bi bi-calendar-check"></i> 조직을 선택해주세요
							</h5>
						</div>

						<div class="col-3 d-flex justify-content-end">
							<div class="input-group input-group-sm">
								<input type="text" class="form-control" placeholder="회원명 검색" aria-label="Member's name" aria-describedby="button-search">
								<button class="btn btn-sm btn-outline-secondary" type="button" id="button-search"><i class="bi bi-search"></i> 검색</button>
							</div>
						</div>

						<div class="col-5 d-flex justify-content-end align-items-center">

							<!-- 점수재계산 버튼 추가 -->
							<button class="btn btn-sm btn-outline-warning me-3 justify-content-end" type="button" id="btnRecalculateStats">
								<i class="bi bi-arrow-clockwise"></i> 포인트재계산
							</button>

							<!-- 연도 선택 컨트롤 -->
							<div class="input-group input-group-sm year-selector justify-content-end">
								<button class="btn btn-sm btn-outline-primary" id="prevYear" type="button"><i class="bi bi-chevron-left"></i></button>
								<label class="input-group-text year-display " id="currentYear">2025</label>

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

<!-- 출석 상세 정보 offcanvas -->
<div class="offcanvas offcanvas-end" data-bs-scroll="true" data-bs-backdrop="false" tabindex="-1" id="attendanceOffcanvas" aria-labelledby="attendanceOffcanvasLabel">
	<div class="offcanvas-header text-start">
		<h5 class="offcanvas-title" id="attendanceOffcanvasLabel">출석 상세 정보</h5>
		<button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
	</div>
	<div class="offcanvas-body">
		<div id="attendanceDetailContent">
			<!-- 동적으로 출석 상세 내용이 로드됩니다 -->
			<div class="text-center py-5">
				<div class="spinner-border text-primary" role="status">
					<span class="visually-hidden">로딩중...</span>
				</div>
				<div class="mt-2 text-muted">출석 정보를 불러오는 중...</div>
			</div>
		</div>
	</div>

	<!-- 하단 고정 버튼 영역 -->
	<div class="offcanvas-footer">
		<div class="d-flex gap-2 p-3 border-top bg-light">
			<button type="button" class="btn btn-secondary flex-fill" data-bs-dismiss="offcanvas">닫기</button>
			<button type="button" class="btn btn-primary flex-fill" id="btnSaveAttendance">저장</button>
		</div>
	</div>
</div>


<?php $this->load->view('footer'); ?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/split.js/1.6.2/split.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.fancytree/2.38.5/jquery.fancytree-all-deps.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pqGrid/3.5.1/pqgrid.min.js"></script>

<script>
	window.attendancePageData = {
		baseUrl: '<?php echo base_url(); ?>',
		currentYear: <?php echo date('Y'); ?>
	};
</script>
<script src="/assets/js/attendance.js?<?php echo date('Ymdhis'); ?>"></script>
