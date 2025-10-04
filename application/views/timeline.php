<?php
/**
 * 역할: 타임라인 관리 메인 화면
 */
$this->load->view('header');
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css">
<link rel="stylesheet" href="/assets/css/custom/pqgrid.min.css?<?php echo WB_VERSION; ?>">




<div class="container-fluid pt-2 pb-2">
	<nav class="mb-3" aria-label="breadcrumb">
		<ol class="breadcrumb mb-0">
			<li class="breadcrumb-item"><a href="#!">홈</a></li>
			<li class="breadcrumb-item"><a href="#!">MEMBER</a></li>
			<li class="breadcrumb-item active">타임라인관리</li>
		</ol>
	</nav>
	
	<div class="row align-items-center justify-content-between g-3 mb-3">
		<h3 class="page-title col-12 my-1">타임라인관리</h3>
	</div>



	<!-- PQGrid 영역 -->
	<div class="card">
		<div class="card-header">
			<div class="row flex-column flex-lg-row">
				<div class="d-flex justify-content-between flex-lg-row">

						<div class="input-group input-group-sm" style="width: 300px;">
							<label for="searchType" class="form-label d-none">항목</label>
							<select class="form-select" id="searchType">
								<option value="">전체</option>
							</select>
							<label for="searchText" class="form-label d-none">검색</label>
							<input type="text" class="form-control" id="searchText" placeholder="이름 또는 내용 검색">
							<button type="button" class="btn btn-sm btn-outline-primary" id="btnSearch"><i class="bi bi-search"></i> 검색</button>
						</div>

					<div class="btn-group">
						<button type="button" class="btn btn-sm btn-outline-primary" id="btnAdd"><i class="bi bi-plus-lg"></i> 추가</button>
						<button type="button" class="btn btn-sm btn-outline-warning" id="btnEdit"><i class="bi bi-pencil"></i> 선택수정</button>
						<button type="button" class="btn btn-sm btn-outline-danger" id="btnDelete"><i class="bi bi-trash"></i> 선택삭제</button>
					</div>
				</div>
			</div>
		</div>

		<div class="card-body p-0">
			<div id="timelineGrid"></div>
		</div>
	</div>
</div>



<!-- 타임라인 추가/수정 Offcanvas -->
<div class="offcanvas offcanvas-end" data-bs-scroll="true" data-bs-backdrop="true" tabindex="-1" id="timelineOffcanvas" aria-labelledby="timelineOffcanvasLabel" style="width: 500px;">
	<div class="offcanvas-header text-start">
		<h5 class="offcanvas-title" id="timelineOffcanvasLabel">타임라인 추가</h5>
		<button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
	</div>
	<div class="offcanvas-body">
		<form id="timelineForm">
			<input type="hidden" id="timeline_idx" name="idx">
			<input type="hidden" id="timeline_mode" value="add">

			<div class="mb-3">
				<label for="timeline_type" class="form-label">항목 <span class="text-danger">*</span></label>
				<select class="form-select" id="timeline_type" name="timeline_type" required>
					<option value="">항목을 선택하세요</option>
				</select>
			</div>

			<div class="mb-3">
				<label for="timeline_date" class="form-label">날짜 <span class="text-danger">*</span></label>
				<input type="date" class="form-control" id="timeline_date" name="timeline_date" required>
			</div>

			<div class="mb-3">
				<label for="timeline_content" class="form-label">내용</label>
				<textarea class="form-control" id="timeline_content" name="timeline_content" rows="3" placeholder="내용을 입력하세요"></textarea>
			</div>

			<div class="mb-3" id="memberSelectDiv">
				<label for="member_select" class="form-label">이름 <span class="text-danger">*</span></label>
				<select class="form-select" id="member_select" name="member_idxs[]" multiple required>
				</select>
				<div class="form-text">여러 명을 선택할 수 있습니다.</div>
			</div>

			<div class="mb-3" id="memberNameDiv" style="display: none;">
				<label class="form-label">회원명</label>
				<input type="text" class="form-control" id="member_name_display" readonly>
			</div>
		</form>
	</div>

	<div class="offcanvas-footer">
		<div class="d-flex gap-2 p-3 border-top bg-light">
			<button type="button" class="btn btn-secondary flex-fill" data-bs-dismiss="offcanvas">취소</button>
			<button type="button" class="btn btn-primary flex-fill" id="btnSaveTimeline">저장</button>
		</div>
	</div>
</div>

<!-- 삭제 확인 모달 -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="deleteModalLabel">타임라인 삭제</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<p>선택한 타임라인을 삭제하시겠습니까?</p>
				<p class="text-danger">이 작업은 되돌릴 수 없습니다.</p>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
				<button type="button" class="btn btn-danger" id="confirmDeleteBtn">삭제</button>
			</div>
		</div>
	</div>
</div>

<?php $this->load->view('footer'); ?>

<script src="/assets/js/custom/pqgrid.min.js?<?php echo WB_VERSION; ?>"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
	window.timelinePageData = {
		baseUrl: '<?php echo base_url(); ?>',
		currentOrgId: '<?php echo isset($current_org['org_id']) ? $current_org['org_id'] : ''; ?>'
	};

	$('.menu-24').addClass('active');
</script>
<script src="/assets/js/timeline.js?<?php echo WB_VERSION; ?>"></script>
