<?php
/**
 * 파일 위치: application/views/moim.php
 * 역할: 소모임 관리 화면 레이아웃
 */
$this->load->view('header');
?>

<!-- Moim CSS -->
<link rel="stylesheet" href="/assets/css/moim.css?<?php echo WB_VERSION; ?>">

<!-- ParamQuery CSS -->
<link rel="stylesheet" href="/assets/css/custom/pqgrid.min.css">

<!-- Fancytree CSS -->
<link rel="stylesheet" href="/assets/css/custom/ui.fancytree.min.css?<?php echo WB_VERSION; ?>">

<!-- Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

<div class="container-fluid pt-2 pb-2">
	<nav class="mb-1" aria-label="breadcrumb">
		<ol class="breadcrumb mb-0">
			<li class="breadcrumb-item"><a href="#!">홈</a></li>
			<li class="breadcrumb-item"><a href="#!">MEMBER</a></li>
			<li class="breadcrumb-item active">소모임</li>
		</ol>
	</nav>
	<div class="row align-items-center justify-content-between g-3 mb-3">
		<div class="col-12 my-1 d-flex align-items-center justify-content-between">
			<h3 class="page-title mb-0">소모임</h3>
			<div class="d-flex gap-2">
				<button type="button" class="btn btn-sm btn-outline-secondary" id="btnManageCategory">
					<i class="bi bi-folder"></i> 카테고리 관리
				</button>
				<button type="button" class="btn btn-sm btn-primary" id="btnAddMembers">
					<i class="bi bi-plus-lg"></i> 회원 추가
				</button>
			</div>
		</div>
	</div>

	<!-- Split.js를 위한 단일 컨테이너 -->
	<div class="split-container">
		<!-- 왼쪽: 카테고리 트리 -->
		<div class="split-pane" id="left-pane">
			<div class="card">
				<div class="card-body card-height p-0 position-relative">
					<!-- 트리 스피너 -->
					<div id="treeSpinner" class="d-flex justify-content-center align-items-center position-absolute w-100 h-100" style="z-index: 1000; background: rgba(255, 255, 255, 0.8);">
						<div class="text-center">
							<div class="spinner-border text-primary mb-2" role="status">
								<span class="visually-hidden">로딩 중...</span>
							</div>
							<div class="small text-muted">카테고리 로딩 중...</div>
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
							<h5 class="mb-0 text-truncate" id="selectedCategoryName">
								<i class="bi bi-people"></i> 소모임을 선택해주세요
							</h5>
							<small class="ms-3 text-muted">총 <span id="totalMemberCount">0</span>명</small>
						</div>

						<div class="col-12 col-lg-6 d-flex justify-content-start justify-content-lg-end mt-2 mt-lg-0">
							<div class="input-group input-group-sm" style="max-width: 300px;">
								<input type="text" class="form-control" placeholder="회원명 검색" id="searchKeyword">
								<button class="btn btn-sm btn-outline-secondary" type="button" id="btnSearch">
									<i class="bi bi-search"></i> 검색
								</button>
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
							<div class="small text-muted">회원 목록 로딩 중...</div>
						</div>
					</div>
					<!-- ParamQuery Grid가 여기에 렌더링됩니다 -->
					<div id="moimGrid"></div>
				</div>
			</div>
		</div>
	</div>
</div>

<!-- 회원 추가 Modal -->
<div class="modal fade" id="addMemberModal" tabindex="-1" aria-hidden="true">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title">소모임 회원 추가</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<form id="addMemberForm">
					<input type="hidden" id="addOrgId" name="org_id">
					<input type="hidden" id="addCategoryCode" name="category_code">

					<div class="mb-3">
						<label class="form-label">소모임 카테고리</label>
						<input type="text" class="form-control" id="addCategoryName" readonly>
					</div>

					<div class="mb-3">
						<label class="form-label">회원 선택 <span class="text-danger">*</span></label>
						<select class="form-select" id="memberSelect" name="member_indices[]" multiple required>
						</select>
						<small class="text-muted">여러 회원을 선택할 수 있습니다</small>
					</div>

					<div class="mb-3">
						<label class="form-label">모임 직책</label>
						<select class="form-select" id="moimPosition" name="moim_position">
							<option value="">선택</option>
						</select>
					</div>
				</form>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
				<button type="button" class="btn btn-primary" id="btnSaveMembers">추가</button>
			</div>
		</div>
	</div>
</div>

<!-- 카테고리 관리 Modal -->
<div class="modal fade" id="categoryModal" tabindex="-1" aria-hidden="true">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title">카테고리 관리</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<div class="alert alert-info">
					<i class="bi bi-info-circle"></i> 카테고리는 JSON 형태로 관리됩니다. positions 배열에 직책 목록을 추가할 수 있습니다.
				</div>
				<textarea class="form-control font-monospace" id="categoryJson" rows="20" placeholder='{"categories": []}'></textarea>
				<div class="mt-3">
					<strong>예시:</strong>
					<pre class="bg-light p-2 rounded"><code>{
  "categories": [
    {
      "code": "MOIM001",
      "name": "동아리모임",
      "order": 1,
      "positions": ["회장", "부회장", "총무", "회계", "회원"],
      "children": []
    }
  ]
}</code></pre>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
				<button type="button" class="btn btn-primary" id="btnSaveCategory">저장</button>
			</div>
		</div>
	</div>
</div>

<?php $this->load->view('footer'); ?>

<!-- Split.js -->
<script src="/assets/js/custom/split.min.js?<?php echo WB_VERSION; ?>"></script>

<!-- Fancytree -->
<script src="/assets/js/custom/jquery.fancytree-all-deps.min.js?<?php echo WB_VERSION; ?>"></script>

<!-- ParamQuery -->
<script src="/assets/js/custom/pqgrid.min.js?<?php echo WB_VERSION; ?>"></script>

<!-- Select2 -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
	window.moimPageData = {
		baseUrl: '<?php echo base_url(); ?>',
		currentOrgId: <?php echo isset($current_org['org_id']) ? $current_org['org_id'] : 0; ?>
	};
</script>

<script src="/assets/js/moim.js?<?php echo WB_VERSION; ?>"></script>
