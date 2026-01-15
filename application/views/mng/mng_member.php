<?php
/**
 * 파일 위치: application/views/mng/mng_member.php
 * 역할: 마스터 회원관리 화면 - 기존 조직관리와 동일한 SplitJS + Fancytree + ParamQuery 구조
 */
$this->load->view('mng/header');
?>
<!-- ParamQuery CSS -->
<link rel="stylesheet" href="/assets/css/custom/pqgrid.min.css?<?php echo WB_VERSION; ?>">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
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
						<div class="col-12 col-lg-4 d-flex align-items-center">
							<h5 class="mb-0 text-truncate" id="selectedNodeName">
								<i class="bi bi-people"></i> 조직을 선택해주세요
							</h5>
							<small class="ms-3 text-muted">총 <span id="totalMemberCount">0</span>명</small>
						</div>

						<div class="col-12 col-lg-4 d-flex align-items-center">
							<!--검색영역-->
						</div>
						
						<div class="col-12 col-lg-4 d-flex justify-content-start justify-content-lg-end mt-2 mt-lg-0 align-items-center gap-2">
						
							<small class="text-muted me-2">선택된 회원 <span id="selectedCount">0</span>명</small>

							<button type="button" class="btn btn-outline-primary" id="btnStatusChange" disabled>
								<i class="bi bi-arrow-repeat"></i> 관리tag변경
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


<!-- 관리tag 변경 모달 -->
<div class="modal fade" id="statusChangeModal" tabindex="-1" aria-labelledby="statusChangeModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="statusChangeModalLabel">회원 태그 변경</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<div class="mb-3">
					<label class="form-label fw-bold">선택된 회원 (<span id="statusChangeCount">0</span>명)</label>
					<div id="statusChangeMemberList"></div>
				</div>
				<hr>

				<!-- 단일 회원 모드 -->
				<div id="singleModeSection" class="d-none">
					<div class="mb-3">
						<label for="statusTagSelect" class="form-label fw-bold">회원 태그</label>
						<select class="form-select" id="statusTagSelect" name="status_tags" multiple="multiple">
						</select>
						<small class="text-muted">태그를 선택하거나 새로 입력할 수 있습니다.</small>
					</div>
				</div>

				<!-- 일괄 변경 모드 -->
				<div id="bulkModeSection" class="d-none">
					<div class="mb-3">
						<label for="addTagSelect" class="form-label fw-bold">추가할 회원 태그</label>
						<select class="form-select" id="addTagSelect" name="add_tags" multiple="multiple">
						</select>
						<small class="text-muted">선택한 회원들에게 추가할 태그</small>
					</div>
					<div class="mb-3">
						<label for="removeTagSelect" class="form-label fw-bold">삭제할 회원 태그</label>
						<select class="form-select" id="removeTagSelect" name="remove_tags" multiple="multiple">
						</select>
						<small class="text-muted">선택한 회원들에게서 삭제할 태그</small>
					</div>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
				<button type="button" class="btn btn-primary" id="confirmStatusChangeBtn">저장</button>
			</div>
		</div>
	</div>
</div>

<?php $this->load->view('mng/footer'); ?>

<!-- Split.js -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/split.js/1.6.5/split.min.js"></script>
<!-- Fancytree -->
<script src="/assets/js/custom/jquery.fancytree-all-deps.min.js"></script>
<!-- ParamQuery Grid -->
<script src="/assets/js/custom/pqgrid.min.js?<?php echo WB_VERSION; ?>"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="/assets/js/custom/select2.sortable.min.js?<?php echo WB_VERSION; ?>"></script>
<!-- 회원관리 JavaScript -->
<script src="/assets/js/mng_member.js?<?php echo WB_VERSION; ?>"></script>
