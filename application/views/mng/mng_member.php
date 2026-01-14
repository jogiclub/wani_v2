<?php
/**
 * 파일 위치: application/views/mng/mng_member.php
 * 역할: 마스터 회원관리 화면 - 기존 조직관리와 동일한 SplitJS + Fancytree + ParamQuery 구조
 */
$this->load->view('mng/header');
?>
<!-- ParamQuery CSS -->
<link rel="stylesheet" href="/assets/css/custom/pqgrid.min.css?<?php echo WB_VERSION; ?>">

<!-- Fancytree CSS -->
<link rel="stylesheet" href="/assets/css/custom/ui.fancytree.min.css?<?php echo WB_VERSION; ?>">

<div class="container-fluid pt-2 pb-2">
	<nav class="mb-1" aria-label="breadcrumb">
		<ol class="breadcrumb mb-0">
			<li class="breadcrumb-item"><a href="/mng">관리자홈</a></li>
			<li class="breadcrumb-item"><a href="#!">ORG</a></li>
			<li class="breadcrumb-item active">회원관리</li>
		</ol>
	</nav>
	<div class="col-12 my-1 d-flex align-items-center justify-content-between mb-3">
		<h3 class="page-title col-12 my-1">회원관리</h3>
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

		<!-- 오른쪽: 회원 목록 -->
		<div class="split-pane" id="right-pane">
			<div class="card">
				<div class="card-header">
					<div class="row flex-column flex-lg-row">
						<div class="col-12 col-lg-6 d-flex align-items-center">
							<h5 class="mb-0 text-truncate" id="selectedNodeName">
								<i class="bi bi-people"></i> 조직을 선택해주세요
							</h5>
						</div>
						<div class="col-12 col-lg-6 d-flex justify-content-start justify-content-lg-end mt-2 mt-lg-0 align-items-center gap-2">
							<small class="text-muted">총 <span id="totalMemberCount">0</span>명</small>
							<button type="button" class="btn btn-sm btn-outline-secondary" id="btnRefresh">
								<i class="bi bi-arrow-clockwise"></i> 새로고침
							</button>
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
							<div class="small text-muted">회원 정보 로딩 중...</div>
						</div>
					</div>
					<!-- ParamQuery Grid -->
					<div id="memberGrid"></div>
				</div>
			</div>
		</div>
	</div>
</div>

<!-- 회원 상세 정보 Offcanvas -->
<div class="offcanvas offcanvas-end" data-bs-scroll="true" data-bs-backdrop="false" tabindex="-1" id="memberOffcanvas" aria-labelledby="memberOffcanvasLabel" style="width: 500px;">
	<div class="offcanvas-header">
		<h5 class="offcanvas-title" id="memberOffcanvasLabel">회원 상세정보</h5>
		<button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
	</div>
	<div class="offcanvas-body">
		<!-- 로딩 스피너 -->
		<div id="memberOffcanvasSpinner" class="d-flex justify-content-center align-items-center" style="height: 200px;">
			<div class="text-center">
				<div class="spinner-border text-primary mb-2" role="status">
					<span class="visually-hidden">로딩 중...</span>
				</div>
				<div class="small text-muted">회원 정보를 불러오는 중...</div>
			</div>
		</div>

		<!-- 회원 정보 폼 -->
		<div id="memberDetailForm" style="display: none;">
			<div class="text-center mb-4">
				<img id="detail_photo" src="/assets/images/photo_no.png" class="rounded-circle" width="120" height="120" style="object-fit: cover;">
			</div>

			<div class="mb-4">
				<h6 class="border-bottom pb-2 mb-3">기본 정보</h6>
				<table class="table table-sm">
					<tr>
						<th class="bg-light" width="30%">이름</th>
						<td id="detail_member_name">-</td>
					</tr>
					<tr>
						<th class="bg-light">소속 조직</th>
						<td id="detail_org_name">-</td>
					</tr>
					<tr>
						<th class="bg-light">연락처</th>
						<td id="detail_member_phone">-</td>
					</tr>
					<tr>
						<th class="bg-light">생년월일</th>
						<td id="detail_member_birth">-</td>
					</tr>
					<tr>
						<th class="bg-light">성별</th>
						<td id="detail_member_sex">-</td>
					</tr>
					<tr>
						<th class="bg-light">직위/직분</th>
						<td id="detail_position_name">-</td>
					</tr>
					<tr>
						<th class="bg-light">직책</th>
						<td id="detail_duty_name">-</td>
					</tr>
					<tr>
						<th class="bg-light">주소</th>
						<td id="detail_address">-</td>
					</tr>
					<tr>
						<th class="bg-light">등록일</th>
						<td id="detail_regi_date">-</td>
					</tr>
				</table>
			</div>

			<div class="mb-4">
				<h6 class="border-bottom pb-2 mb-3">메모</h6>
				<div id="detail_member_etc" class="p-2 bg-light rounded" style="min-height: 60px;">-</div>
			</div>
		</div>
	</div>
</div>

<?php $this->load->view('mng/footer'); ?>

<!-- Split.js -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/split.js/1.6.5/split.min.js"></script>
<!-- Fancytree -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.fancytree/2.38.5/jquery.fancytree-all-deps.min.js"></script>
<!-- ParamQuery Grid -->
<script src="/assets/js/custom/pqgrid.min.js?<?php echo WB_VERSION; ?>"></script>
<!-- 회원관리 JavaScript -->
<script src="/assets/js/mng_member.js?<?php echo WB_VERSION; ?>"></script>
