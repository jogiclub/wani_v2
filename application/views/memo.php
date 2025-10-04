<?php
/**
 * 역할: 타임라인 관리 메인 화면
 */
$this->load->view('header');
?>


<link rel="stylesheet" href="/assets/css/custom/pqgrid.min.css?<?php echo WB_VERSION; ?>">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />



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

					<div class="input-group input-group-sm" style="width: 400px;">

						<button type="button" class="btn btn-secondary" id="searchTypeText">전체 타임라인</button>
						<button type="button" class="btn btn-secondary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false" data-bs-reference="parent"  id="searchTypeDropdown" >
							<span class="visually-hidden"></span>
						</button>

						
						


						<ul class="dropdown-menu" aria-labelledby="searchTypeDropdown" id="searchTypeMenu" style="max-height: 300px; overflow-y: auto;">
							<li>
								<div class="dropdown-item">
									<input type="checkbox" class="form-check-input me-2" id="searchType_all" value="" checked>
									<label class="form-check-label" for="searchType_all">전체</label>
								</div>
							</li>
							<li><hr class="dropdown-divider"></li>
							<!-- 타임라인 항목들이 여기에 동적으로 추가됩니다 -->
						</ul>

						<label for="searchText" class="form-label d-none">검색</label>
						<input type="text" class="form-control" id="searchText" placeholder="이름 또는 내용 검색">
						<button type="button" class="btn btn-sm btn-outline-primary" id="btnSearch"><i class="bi bi-search"></i> 검색</button>
					</div>

					<div class="btn-group">
						<button type="button" class="btn btn-sm btn-outline-primary" id="btnAdd"><i class="bi bi-plus-lg"></i> 일괄추가</button>

						<button type="button" class="btn btn-sm btn-outline-danger" id="btnDelete"><i class="bi bi-trash"></i> 선택삭제</button>
					</div>
				</div>
			</div>
		</div>

		<div class="card-body p-0 position-relative">
			<!-- 로딩 스피너 -->
			<div id="timelineGridLoading" class="position-absolute top-50 start-50 translate-middle" style="z-index: 1000; display: none;">
				<div class="spinner-border text-primary" role="status">
					<span class="visually-hidden">로딩중...</span>
				</div>
			</div>
			<div id="timelineGrid"></div>
		</div>
	</div>
</div>



<!-- 타임라인 일괄추가/수정 Offcanvas -->
<div class="offcanvas offcanvas-end" data-bs-scroll="true" data-bs-backdrop="true" tabindex="-1" id="timelineOffcanvas" aria-labelledby="timelineOffcanvasLabel" style="width: 500px;">
	<div class="offcanvas-header text-start">
		<h5 class="offcanvas-title" id="timelineOffcanvasLabel">타임라인 일괄추가</h5>
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
				<div class="form-text">여러 명을 선택할 수 있습니다. 선택된 항목을 드래그하여 순서를 변경할 수 있습니다.</div>
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
<script src="/assets/js/custom/select2.sortable.min.js?<?php echo WB_VERSION; ?>"></script>


<script>
	window.timelinePageData = {
		baseUrl: '<?php echo base_url(); ?>',
		currentOrgId: '<?php echo isset($current_org['org_id']) ? $current_org['org_id'] : ''; ?>'
	};

	$('.menu-24').addClass('active');
</script>
<script src="/assets/js/timeline.js?<?php echo WB_VERSION; ?>"></script>
