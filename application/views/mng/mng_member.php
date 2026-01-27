<?php
/**
 * 파일 위치: application/views/mng/mng_member.php
 * 역할: 마스터 회원관리 화면 - 기존 조직관리와 동일한 SplitJS + Fancytree + ParamQuery 구조
 */
$this->load->view('mng/header');
?>
<!-- ParamQuery CSS -->
<link rel="stylesheet" href="/assets/css/custom/pqgrid.min.css">
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
						<div class="col-12 col-lg-3 d-flex align-items-center">
							<h5 class="mb-0 text-truncate" id="selectedNodeName">
								<i class="bi bi-people"></i> 조직을 선택해주세요
							</h5>
							<small class="ms-3 text-muted">총 <span id="totalMemberCount">0</span>명</small>
						</div>

						<div class="col-12 col-lg-4 d-flex align-items-center">
							<div class="input-group input-group-sm">
								<button type="button" class="btn btn-outline-secondary" id="searchTagText">관리tag 전체</button>
								<button type="button" class="btn btn-outline-secondary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false" data-bs-auto-close="outside" id="searchTagDropdown">
									<span class="visually-hidden">Toggle Dropdown</span>
								</button>
								<ul class="dropdown-menu" id="searchTagMenu" style="max-height: 300px; overflow-y: auto;">
									<li>
										<div class="dropdown-item">
											<input type="checkbox" class="form-check-input me-2" id="searchTag_all" value="" checked>
											<label class="form-check-label" for="searchTag_all">전체</label>
										</div>
									</li>
									<li><hr class="dropdown-divider"></li>
								</ul>
								<input type="text" class="form-control" id="searchKeyword" placeholder="이름 또는 연락처" style="max-width: 150px;">
								<button type="button" class="btn btn-outline-primary" id="btnSearch"><i class="bi bi-search"></i> 검색</button>
								<button type="button" class="btn btn-outline-secondary" id="btnSearchReset"><i class="bi bi-x-circle"></i> 검색초기화</button>
							</div>
						</div>

						<div class="col-12 col-lg-5 d-flex justify-content-start justify-content-lg-end mt-2 mt-lg-0 align-items-center gap-2">

							<small class="text-muted me-2">선택된 회원 <span id="selectedCount">0</span>명</small>
							<div class="btn-group" role="group">
								<button type="button" class="btn btn-sm btn-primary" id="btnStatusChange" disabled>
									<i class="bi bi-arrow-repeat"></i> 관리tag일괄처리
								</button>
								<button type="button" class="btn btn-sm btn-outline-primary" id="btnOrgChange" disabled>
									<i class="bi bi-arrow-repeat"></i> 조직일괄변경
								</button>
								<button type="button" class="btn btn-sm btn-outline-danger" id="btnSendMember" disabled>
									<i class="bi bi-chat-dots"></i> 선택문자
								</button>


								<div class="btn-group" role="group">
									<button type="button" class="btn btn-sm btn-outline-dark dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
										더보기
									</button>
									<ul class="dropdown-menu">
										<li><a class="dropdown-item d-block" href="#" id="btnExcelDownload"><i class="bi bi-file-spreadsheet"></i> 엑셀다운로드</a></li>
									</ul>
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


<!-- 조직일괄변경 모달 -->
<div class="modal fade" id="orgChangeModal" tabindex="-1" aria-labelledby="orgChangeModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-xl modal-dialog-centered">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title d-flex align-items-center" id="orgChangeModalLabel">
					조직일괄변경
					<select class="form-select form-select-sm ms-3" id="orgChangeMode" style="width: auto;">
						<option value="move" selected>이동</option>
						<option value="copy">복사</option>
					</select>
				</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">


				<!-- 메인: 회원 목록 / 조직 목록 -->
				<div class="row" style="min-height: 400px;">
					<!-- 왼쪽: 회원 목록 -->
					<div class="col-3 border-end">

						<div class="fw-semibold mb-2 text-muted small">
							<i class="bi bi-people"></i> <span id="orgChangeMemberLabel">이동</span>할 회원 (<span id="orgChangeMemberCount">0</span>명)
						</div>

						<div class="input-group input-group-sm mb-2">
							<input type="text" class="form-control" id="orgChangeMemberSearch" placeholder="이름으로 검색">
							<button type="button" class="btn btn-outline-secondary" id="btnOrgChangeMemberSearch">
								<i class="bi bi-search"></i>
							</button>
						</div>

						<div class="border rounded p-2 bg-light" style="height: 360px; overflow-y: auto;" id="orgChangeMemberList">
							<!-- 드래그 가능한 회원 목록 -->
						</div>
					</div>

					<!-- 오른쪽: 조직 목록 (드롭존) -->
					<div class="col-9">
						<div class="fw-semibold mb-2 text-muted small">
							<i class="bi bi-building"></i> 대상 조직 (<span id="orgChangeOrgCount">0</span>개)
						</div>

						<div class="input-group input-group-sm mb-2">
							<select class="form-select" id="orgChangeTargetCategory">
								<option value="">그룹을 선택하세요</option>
							</select>
							<button type="button" class="btn btn-outline-secondary" id="btnOrgChangeReset" title="초기화">
								<i class="bi bi-arrow-counterclockwise"></i>
							</button>
						</div>

						<div class="border rounded p-2 bg-white" style="height: 360px; overflow-y: auto;" id="orgChangeOrgList">
							<div class="text-center text-muted py-5" id="orgChangeOrgPlaceholder">
								<i class="bi bi-diagram-3 fs-1"></i>
								<p class="mt-2 mb-0">위에서 그룹을 선택하면<br>해당 그룹의 조직 목록이 표시됩니다</p>
							</div>
						</div>
					</div>
				</div>

			</div>
			<div class="modal-footer justify-content-between">
				<div class="text-muted small" id="orgChangeTotalInfo">총 <span id="orgChangeTotalCount">0</span>명 <span id="orgChangeModeText">이동</span> 예정</div>
				<div>
					<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
					<button type="button" class="btn btn-primary" id="confirmOrgChangeBtn" disabled>저장</button>
				</div>
			</div>
		</div>
	</div>
</div>

<!-- 조직변경 확인 모달 -->
<div class="modal fade" id="orgChangeConfirmModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
	<div class="modal-dialog modal-dialog-centered">
		<div class="modal-content">
			<div class="modal-header bg-warning">
				<h5 class="modal-title"><i class="bi bi-exclamation-triangle"></i> 확인</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<p class="mb-2"><strong id="confirmOrgChangeTitle">선택한 회원을 다른 조직으로 이동합니다.</strong></p>
				<div id="confirmOrgChangeDetail" class="mb-3"></div>
				<div class="alert alert-warning mb-0" id="confirmOrgChangeWarning">
					<i class="bi bi-info-circle"></i> <strong>이 작업은 되돌릴 수 없습니다.</strong><br>
					<span id="confirmOrgChangeWarningText">회원이 기존 조직에서 삭제되고 새 조직으로 이동됩니다.</span>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
				<button type="button" class="btn btn-warning" id="executeOrgChangeBtn">실행</button>
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
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/2.5.0/jszip.min.js"></script>
<!-- 회원관리 JavaScript -->
<script src="/assets/js/mng_member.js?<?php echo WB_VERSION; ?>"></script>
