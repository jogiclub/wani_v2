<?php
$this->load->view('mng/header');
?>
<link rel="stylesheet" href="/assets/css/mng_common.css?<?php echo WB_VERSION; ?>">
<!-- ParamQuery CSS -->
<link rel="stylesheet" href="/assets/css/custom/pqgrid.min.css?<?php echo WB_VERSION; ?>">

<!-- Fancytree CSS - Vista 스킨 사용 (더 안정적) -->
<link rel="stylesheet" href="/assets/css/custom/ui.fancytree.min.css?<?php echo WB_VERSION; ?>">


<div class="container-fluid pt-2 pb-2">
	<nav class="mb-3" aria-label="breadcrumb">
		<ol class="breadcrumb mb-0">
			<li class="breadcrumb-item"><a href="/mng">관리자홈</a></li>
			<li class="breadcrumb-item"><a href="#!">ORG</a></li>
			<li class="breadcrumb-item active">조직관리</li>
		</ol>
	</nav>
	<div class="row align-items-center justify-content-between g-3 mb-3">
		<h3 class="page-title col-12 my-1">조직관리</h3>
	</div>

	<!-- Split.js를 위한 단일 컨테이너로 변경 -->
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
							<div class="small text-muted">카테고리 로딩 중...</div>
						</div>
					</div>
					<div id="categoryTree" class="tree-container"></div>
				</div>
			</div>
		</div>

		<!-- 오른쪽 조직 목록 -->
		<div class="split-pane" id="right-pane">
			<div class="card h-100">
				<div class="card-header">
					<div class="row flex-column flex-lg-row">
						<div class="col-12 col-lg-4 d-flex align-items-center">
							<h5 class="mb-0 text-truncate" id="selectedOrgName">
								<i class="bi bi-building"></i> 카테고리를 선택해주세요
							</h5>
						</div>
						<div class="col-12 col-lg-8 d-flex justify-content-start justify-content-lg-end mt-2 mt-lg-0 align-items-center gap-2">
							<!-- 선택된 조직 수 표시 -->
							<small class="text-muted me-2">선택된 조직 <span id="selectedCount">0</span>개</small>

							<!-- 선택삭제 버튼 -->
							<button type="button" class="btn btn-sm btn-outline-danger" id="btnDeleteOrg" disabled>
								<i class="bi bi-trash"></i> 선택삭제
							</button>
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
							<div class="small text-muted">조직 정보 로딩 중...</div>
						</div>
					</div>
					<!-- ParamQuery Grid가 여기에 렌더링됩니다 -->
					<div id="orgGrid"></div>
				</div>
			</div>
		</div>
	</div>
</div>

<!-- 카테고리 추가 모달 -->
<div class="modal fade" id="addCategoryModal" tabindex="-1">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title">카테고리 추가</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
			</div>
			<div class="modal-body">
				<div class="mb-3">
					<label for="categoryName" class="form-label">카테고리명</label>
					<input type="text" class="form-control" id="categoryName" placeholder="카테고리명을 입력하세요" maxlength="50">
				</div>
				<div class="mb-3">
					<label for="parentCategory" class="form-label">상위 카테고리</label>
					<select class="form-select" id="parentCategory">
						<option value="">최상위 카테고리</option>
					</select>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
				<button type="button" class="btn btn-primary" id="saveCategoryBtn">추가</button>
			</div>
		</div>
	</div>
</div>

<!-- 카테고리명 수정 모달 -->
<div class="modal fade" id="renameCategoryModal" tabindex="-1">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title">카테고리명 수정</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
			</div>
			<div class="modal-body">
				<div class="mb-3">
					<label for="renameCategoryName" class="form-label">카테고리명</label>
					<input type="text" class="form-control" id="renameCategoryName" maxlength="50">
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
				<button type="button" class="btn btn-primary" id="saveRenameBtn">수정</button>
			</div>
		</div>
	</div>
</div>

<!-- 조직 상세 정보 모달 -->
<div class="modal fade" id="orgDetailModal" tabindex="-1">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title">조직 상세 정보</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
			</div>
			<div class="modal-body">
				<div id="orgDetailContent">
					<!-- 조직 상세 정보가 여기에 표시됩니다 -->
				</div>
			</div>
		</div>
	</div>
</div>

<!-- 조직 삭제 확인 모달 -->
<div class="modal fade" id="deleteOrgModal" tabindex="-1" aria-labelledby="deleteOrgModalLabel" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="deleteOrgModalLabel">조직 삭제 확인</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<div class="alert alert-warning">
					<i class="bi bi-exclamation-triangle"></i>
					선택한 조직을 삭제하시겠습니까?
				</div>
				<div id="deleteOrgList" class="mb-3">
					<!-- 삭제할 조직 목록이 여기에 표시됩니다 -->
				</div>
				<div class="alert alert-danger">
					<strong>주의:</strong> 이 작업은 되돌릴 수 없습니다. 삭제된 조직은 복구할 수 없습니다.
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
				<button type="button" class="btn btn-danger" id="confirmDeleteOrgBtn">삭제</button>
			</div>
		</div>
	</div>
</div>

<?php
$this->load->view('mng/footer');
?>
<!-- 엑셀 다운로드를 위한 추가 라이브러리 (JSZip 2.x 버전 사용) -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/2.5.0/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/FileSaver.js/2.0.5/FileSaver.min.js"></script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/split.js/1.6.2/split.min.js"></script>
<!-- Fancytree 라이브러리를 무결성 검증 없이 로드 -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.fancytree/2.38.5/jquery.fancytree-all-deps.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pqGrid/3.5.1/pqgrid.min.js"></script>

<!-- baseUrl 설정을 위한 스크립트 -->
<script>
	window.memberPageData = {
		baseUrl: '<?php echo base_url(); ?>'
	};
</script>

<script src="/assets/js/mng_orglist.js?<?php echo WB_VERSION; ?>"></script>
