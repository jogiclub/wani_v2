<?php
$this->load->view('header');
?>
<link rel="stylesheet" href="/assets/css/custom/pqgrid.min.css?<?php echo WB_VERSION; ?>">
<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css"/>


<div class="container-fluid pt-2 pb-2">
	<nav class="mb-1" aria-label="breadcrumb">
		<ol class="breadcrumb mb-0">
			<li class="breadcrumb-item"><a href="#">홈</a></li>
			<li class="breadcrumb-item"><a href="#">CASH</a></li>
			<li class="breadcrumb-item active">수입/지출</li>
		</ol>
	</nav>

	<div class="col-12 my-1 d-flex align-items-center justify-content-between mb-3">
		<h3 class="page-title mb-0">수입/지출</h3>
		<!-- 장부 선택 -->
		<div class="">
			<select id="selectBook" class="form-select">
				<option value="">장부를 선택하세요</option>
			</select>
		</div>
	</div>

	<div class="card">
		<div class="card-body pt-3">


			<!-- 검색 영역 -->
			<div class="row g-2 mb-3" id="searchArea" style="display:none;">
				<!-- 구분 -->
				<div class="col-auto">
					<select id="searchIncomeType" class="form-select form-select-sm">
						<option value="">구분 전체</option>
						<option value="income">수입</option>
						<option value="expense">지출</option>
					</select>
				</div>
				<!-- 계좌 (멀티 셀렉트) -->
				<div class="col-auto multi-select-dropdown">
					<button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" id="bankDropdown"
							data-bs-toggle="dropdown" aria-expanded="false">
						계좌 전체
					</button>
					<ul class="dropdown-menu" id="bankDropdownMenu" aria-labelledby="bankDropdown">
					</ul>
				</div>
				<!-- 계정 (멀티 셀렉트) -->
				<div class="col-auto multi-select-dropdown">
					<button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" id="accountDropdown"
							data-bs-toggle="dropdown" aria-expanded="false">
						계정 전체
					</button>
					<ul class="dropdown-menu" id="accountDropdownMenu" aria-labelledby="accountDropdown">
					</ul>
				</div>
				<!-- TAG (멀티 셀렉트) -->
				<div class="col-auto multi-select-dropdown">
					<button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" id="tagDropdown"
							data-bs-toggle="dropdown" aria-expanded="false">
						TAG 전체
					</button>
					<ul class="dropdown-menu" id="tagDropdownMenu" aria-labelledby="tagDropdown">
					</ul>
				</div>
				<!-- 기간검색 -->
				<div class="col-auto">
					<input type="text" id="searchDateRange" class="form-control form-control-sm" placeholder="기간 선택"
						   style="width: 200px;">
				</div>
				<!-- 검색/초기화 버튼 -->
				<div class="col-auto">
					<button type="button" id="btnSearch" class="btn btn-primary btn-sm">
						<i class="bi bi-search"></i> 검색
					</button>
					<button type="button" id="btnReset" class="btn btn-secondary btn-sm">
						<i class="bi bi-arrow-counterclockwise"></i> 초기화
					</button>
				</div>
				<!-- 우측 버튼 그룹 -->
				<div class="col-auto ms-auto">
					<button type="button" id="btnAddIncome" class="btn btn-primary btn-sm">
						<i class="bi bi-plus-lg"></i> 수입입력
					</button>
					<button type="button" id="btnAddExpense" class="btn btn-danger btn-sm">
						<i class="bi bi-plus-lg"></i> 지출입력
					</button>
					<div class="btn-group">
						<button type="button" class="btn btn-secondary btn-sm dropdown-toggle" data-bs-toggle="dropdown"
								aria-expanded="false">
							<i class="bi bi-three-dots"></i>
						</button>
						<ul class="dropdown-menu dropdown-menu-end">
							<li><a class="dropdown-item" href="#" id="btnDeleteSelected">선택 삭제</a></li>
							<li><a class="dropdown-item" href="#" id="btnChangeSelected">선택 변경</a></li>
						</ul>
					</div>
				</div>
			</div>

			<!-- pqGrid -->
			<div id="incomeGrid" style="height: 500px;"></div>
		</div>
	</div>
</div>

<!-- 수입/지출 입력 Offcanvas -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasEntry" aria-labelledby="offcanvasEntryLabel"
	 style="width: 450px;">
	<div class="offcanvas-header">
		<h5 class="offcanvas-title" id="offcanvasEntryLabel">수입 입력</h5>
		<button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
	</div>
	<div class="offcanvas-body">
		<form id="entryForm">
			<input type="hidden" id="entryIdx" name="idx">
			<input type="hidden" id="entryType" name="income_type">

			<div class="mb-3">
				<label class="form-label">거래일 <span class="text-danger">*</span></label>
				<input type="date" class="form-control" id="entryDate" name="transaction_date" required>
			</div>

			<div class="mb-3">
				<label class="form-label">계좌</label>
				<select class="form-select" id="entryBank" name="bank">
					<option value="">선택</option>
				</select>
			</div>

			<div class="mb-3">
				<label class="form-label">계정 <span class="text-danger">*</span></label>
				<select class="form-select" id="entryAccount" name="account_code" required>
					<option value="">선택</option>
				</select>
			</div>

			<div class="row mb-3">
				<div class="col-4">
					<label class="form-label">건수</label>
					<input type="number" class="form-control" id="entryCnt" name="transaction_cnt" value="1" min="1">
				</div>
				<div class="col-8">
					<label class="form-label">금액 <span class="text-danger">*</span></label>
					<input type="text" class="form-control text-end" id="entryAmount" name="amount" required>
				</div>
			</div>

			<div class="mb-3">
				<label class="form-label">TAG</label>
				<input type="text" class="form-control" id="entryTags" name="tags" placeholder="쉼표로 구분하여 입력">
			</div>

			<div class="mb-3">
				<label class="form-label">메모</label>
				<textarea class="form-control" id="entryMemo" name="memo" rows="3"></textarea>
			</div>

			<div class="d-grid gap-2">
				<button type="submit" class="btn btn-primary" id="btnSaveEntry">저장</button>
			</div>
		</form>
	</div>
</div>

<!-- 삭제 확인 모달 -->
<div class="modal fade" id="deleteConfirmModal" tabindex="-1">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title">삭제 확인</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
			</div>
			<div class="modal-body">
				<p id="deleteConfirmMessage">선택한 항목을 삭제하시겠습니까?</p>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
				<button type="button" class="btn btn-danger" id="btnConfirmDelete">삭제</button>
			</div>
		</div>
	</div>
</div>


<?php $this->load->view('footer'); ?>
<script src="/assets/js/custom/pqgrid.min.js?<?php echo WB_VERSION; ?>"></script>
<script type="text/javascript" src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
<script>
	window.incomePageData = {
		baseUrl: '<?= base_url() ?>',
		orgId: '<?= $current_org['org_id'] ?? '' ?>'
	};
</script>
<script src="<?= base_url('assets/js/income.js') ?>"></script>
