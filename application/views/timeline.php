<?php
/**
 * 역할: 타임라인 관리 메인 화면
 */
$this->load->view('header');
?>


<link rel="stylesheet" href="/assets/css/custom/pqgrid.min.css?<?php echo WB_VERSION; ?>">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />



<div class="container-fluid pt-2 pb-2">
	<nav class="mb-1" aria-label="breadcrumb">
		<ol class="breadcrumb mb-0">
			<li class="breadcrumb-item"><a href="#!">홈</a></li>
			<li class="breadcrumb-item"><a href="#!">MEMBER</a></li>
			<li class="breadcrumb-item active">타임라인관리</li>
		</ol>
	</nav>

	<div class="col-12 my-1 d-flex align-items-center justify-content-between mb-3">
		<h3 class="page-title mb-0">타임라인관리</h3>
	</div>


	<div class="row">
		<!-- PQGrid 영역 -->
		<div class="col-lg-9">
			<div class="card col-12">
				<div class="card-header">
					<div class="row flex-column flex-lg-row">
						<div class="d-flex justify-content-between flex-lg-row">

							<div class="input-group" style="width: 350px">
								<button type="button" class="btn btn-sm btn-outline-secondary" id="btnPrevMonth">
									<i class="bi bi-chevron-left"></i> 이전월
								</button>

								<select class="form-select form-select-sm" id="historyYear">

								</select>

								<select class="form-select form-select-sm" id="historyMonth">
									<option value="1">1월</option>
									<option value="2">2월</option>
									<option value="3">3월</option>
									<option value="4">4월</option>
									<option value="5">5월</option>
									<option value="6">6월</option>
									<option value="7">7월</option>
									<option value="8">8월</option>
									<option value="9">9월</option>
									<option value="10">10월</option>
									<option value="11">11월</option>
									<option value="12">12월</option>
								</select>

								<button type="button" class="btn btn-sm btn-outline-secondary" id="btnNextMonth">
									다음월 <i class="bi bi-chevron-right"></i>
								</button>
							</div>

							<div class="input-group input-group-sm" style="width: 350px;">

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


								<div class="btn-group" role="group">
									<button type="button" class="btn btn-sm btn-outline-dark dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
										더보기
									</button>
									<ul class="dropdown-menu">
										<li><a class="dropdown-item" href="#" id="btnCertificate"><i class="bi bi-printer"></i> 수료증 인쇄</a></li>
									</ul>
								</div>


							</div>
						</div>
					</div>
				</div>

				<div class="card-body card-height p-0 position-relative">
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
		<div class="col-lg-3">
			<div class="card">
				<div class="card-header d-flex justify-content-between align-items-center">
					<div>타임라인 통계</div>
					<div>
						<select class="form-select form-select-sm d-inline-block" aria-label="Default select example" id="timelineStaticsType">
							<option selected>전체 달성율</option>
						</select></div>
					</div>
				<div class="card-body card-height">
					<div id="timelineStaticsLoading" class="position-absolute top-50 start-50 translate-middle" style="z-index: 1000; display: none;">
						<div class="spinner-border text-primary" role="status">
							<span class="visually-hidden">로딩중...</span>
						</div>
					</div>
					<div id="timelineStatics"></div>
				</div>
			</div>
		</div>
	</div>
</div>

<!-- 수료증 인쇄 Offcanvas -->
<div class="offcanvas offcanvas-end" data-bs-scroll="true" data-bs-backdrop="true" tabindex="-1" id="certificateOffcanvas" aria-labelledby="certificateOffcanvasLabel" style="width: 600px;">
	<div class="offcanvas-header text-start">
		<h5 class="offcanvas-title" id="certificateOffcanvasLabel">수료증 인쇄</h5>
		<button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
	</div>
	<div class="offcanvas-body">
		<div class="alert alert-info mb-3">
			<small>여러 time을 선택한 경우 첫 번째 항목의 정보가 표시됩니다.<br/>
				미리보기/인쇄 시 선택한 모든 항목이 출력됩니다.<br/>
				조직설정에서 조직 로고, 조직 직인, 조직명, 조직장 등의 정보 수정이 가능합니다.
			</small>
		</div>

		<div id="certificatePreview" class="border rounded p-4 bg-white" style="min-height: 500px;">
			<div class="text-center mb-5">
				<h2 class="mb-2">수료증</h2>
				<h5 class="text-muted">Certificate of Completion</h5>
			</div>

			<div class="mb-4">
				<div class="row mb-3">
					<div class="col-3 text-end fw-bold">성명</div>
					<div class="col-9" id="cert_member_name"></div>
				</div>
				<div class="row mb-3">
					<div class="col-3 text-end fw-bold">과목</div>
					<div class="col-9" id="cert_timeline_type"></div>
				</div>
				<div class="row mb-3">
					<div class="col-3 text-end fw-bold">기간</div>
					<div class="col-9">
						<div class="input-group input-group-sm">
							<input type="date" class="form-control" id="cert_period_start" style="width: auto;">
							<span class="input-group-text">~</span>
							<input type="date" class="form-control" id="cert_period_end" style="width: auto;">
						</div>
					</div>
				</div>
			</div>

			<div class="mb-4">
				<textarea class="form-control" id="cert_content" rows="4"></textarea>
			</div>

			<div class="text-end mt-5">
				<div class="mb-2">
					<input type="date" class="form-control form-control-sm d-inline-block text-center" id="cert_date" style="width: 180px;">
				</div>
				<div class="d-flex justify-content-end align-items-center gap-3">
					<div class="fw-bold" id="cert_org_name"></div>
					<div class="fw-bold" id="cert_org_rep"></div>
				</div>
			</div>
		</div>
	</div>

	<div class="offcanvas-footer">
		<div class="d-flex gap-2 p-3 border-top bg-light">
			<button type="button" class="btn btn-secondary flex-fill" data-bs-dismiss="offcanvas">취소</button>
			<button type="button" class="btn btn-info flex-fill" id="btnCertificatePreview">미리보기</button>
			<button type="button" class="btn btn-primary flex-fill" id="btnCertificatePrint">인쇄</button>
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
				<div class="form-text">
					여러 명을 선택할 수 있습니다. 선택된 항목을 드래그하여 순서를 변경할 수 있습니다.<br/>
					입대 항목을 입력하면 진급 및 전역 항목을 자동으로 추가합니다.
				</div>
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


<!-- 진급/전역 자동 생성 확인 모달 -->
<div class="modal fade" id="promotionModal" tabindex="-1" aria-labelledby="promotionModalLabel" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="promotionModalLabel">진급 및 전역 항목 생성</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<p>진급(일병), 진급(상병), 진급(병장), 전역 항목도 함께 생성하시겠습니까?</p>
				<div class="alert alert-info mb-0">
					<small>
						입대일 기준으로 자동 계산됩니다.<br>
						- 일병 진급: 입대일 + 2개월<br>
						- 상병 진급: 입대일 + 8개월<br>
						- 병장 진급: 입대일 + 14개월<br>
						- 전역: 입대일 + 18개월
					</small>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">아니오</button>
				<button type="button" class="btn btn-primary" id="confirmPromotionBtn">예, 함께 생성</button>
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
		currentOrgId: '<?php echo isset($current_org['org_id']) ? $current_org['org_id'] : ''; ?>',
		currentOrgName: '<?php echo isset($current_org['org_name']) ? $current_org['org_name'] : ''; ?>',
		currentOrgRep: '<?php echo isset($current_org['org_rep']) ? $current_org['org_rep'] : ''; ?>',
		currentOrgSeal: '<?php echo isset($current_org['org_seal']) ? $current_org['org_seal'] : ''; ?>'
	};

	$('.menu-24').addClass('active');
</script>
<script src="/assets/js/timeline.js?<?php echo WB_VERSION; ?>"></script>
