<?php
/**
 * 파일 위치: application/views/account.php
 * 역할: 현금출납 계정관리 화면
 */
?>
<!DOCTYPE html>
<html lang="ko">
<head>
	<?php $this->load->view('header'); ?>
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/jquery.fancytree@2.38.3/dist/skin-win8/ui.fancytree.min.css">

</head>
<body>
<div class="container-fluid px-4 py-3">
	<nav aria-label="breadcrumb">
		<ol class="breadcrumb mb-0">
			<li class="breadcrumb-item"><a href="/">홈</a></li>
			<li class="breadcrumb-item"><a href="#">CASH</a></li>
			<li class="breadcrumb-item active">계정관리</li>
		</ol>
	</nav>

	<div class="d-flex align-items-center justify-content-between mb-3 mt-2">
		<h3 class="page-title mb-0">계정관리</h3>
	</div>

	<div class="row">
		<!-- 장부 목록 -->
		<div class="col-lg-3 col-md-4">
			<div class="card">
				<div class="card-header d-flex justify-content-between align-items-center">
					<h6 class="mb-0">장부 선택</h6>
					<button type="button" class="btn btn-sm btn-primary" id="btnAddBook">
						<i class="bi bi-plus-lg"></i> 추가
					</button>
				</div>
				<div class="card-body p-0">
					<div id="bookList" class="list-group book-list p-3">

					</div>
					<div id="noBookMessage" class="text-center py-4 text-muted" style="display: none;">
						<i class="bi bi-journal-text fs-1"></i>
						<p class="mb-0 mt-2">등록된 장부가 없습니다.</p>
					</div>
				</div>
			</div>
		</div>

		<!-- 계정 트리 -->
		<div class="col-lg-6 col-md-5">
			<div class="card">
				<div class="card-header">
					<ul class="nav nav-tabs card-header-tabs" id="accountTabs" role="tablist">
						<li class="nav-item" role="presentation">
							<button class="nav-link active" id="income-tab" data-bs-toggle="tab"
									data-bs-target="#incomePane" type="button" role="tab"
									aria-controls="incomePane" aria-selected="true">수입</button>
						</li>
						<li class="nav-item" role="presentation">
							<button class="nav-link" id="expense-tab" data-bs-toggle="tab"
									data-bs-target="#expensePane" type="button" role="tab"
									aria-controls="expensePane" aria-selected="false">지출</button>
						</li>
					</ul>
				</div>
				<div class="card-body">
					<div class="tab-content" id="accountTabsContent">
						<div class="tab-pane fade show active" id="incomePane" role="tabpanel" aria-labelledby="income-tab">
							<div class="account-tree-container">
								<div id="incomeTree"></div>
							</div>
						</div>
						<div class="tab-pane fade" id="expensePane" role="tabpanel" aria-labelledby="expense-tab">
							<div class="account-tree-container">
								<div id="expenseTree"></div>
							</div>
						</div>
					</div>
					<div id="noAccountMessage" class="text-center py-5 text-muted">
						<i class="bi bi-diagram-3 fs-1"></i>
						<p class="mb-0 mt-2">장부를 선택해주세요.</p>
					</div>
				</div>
			</div>
		</div>

		<!-- 관리 버튼 -->
		<div class="col-lg-3 col-md-3">
			<div class="card">
				<div class="card-header">
					<h6 class="mb-0">계정 관리</h6>
				</div>
				<div class="card-body">
					<div id="selectedAccountInfo" class="mb-3 p-3 bg-light rounded" style="display: none;">
						<small class="text-muted">선택된 계정</small>
						<div id="selectedAccountName" class="fw-bold"></div>
						<small id="selectedAccountLevel" class="text-muted"></small>
					</div>
					<div class="action-buttons d-grid gap-2">
						<button type="button" class="btn btn-primary" id="btnAddSubAccount" disabled>
							<i class="bi bi-plus-circle"></i> 하위계정 생성
						</button>
						<button type="button" class="btn btn-secondary" id="btnRenameAccount" disabled>
							<i class="bi bi-pencil"></i> 계정명 변경
						</button>
						<button type="button" class="btn btn-danger" id="btnDeleteAccount" disabled>
							<i class="bi bi-trash"></i> 계정 삭제
						</button>
						<button type="button" class="btn btn-info" id="btnMoveAccount" disabled>
							<i class="bi bi-arrows-move"></i> 계정 이동
						</button>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<!-- 장부 추가/수정 모달 -->
<div class="modal fade" id="bookModal" tabindex="-1" aria-labelledby="bookModalLabel" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="bookModalLabel">장부 추가</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<form id="bookForm">
					<input type="hidden" id="edit_book_idx" name="book_idx">
					<div class="mb-3">
						<label for="book_name" class="form-label">장부명 <span class="text-danger">*</span></label>
						<input type="text" class="form-control" id="book_name" name="book_name" required placeholder="예: 일반회계">
					</div>
					<div class="mb-3">
						<label for="fiscal_base_month" class="form-label">회계기수 기준월 <span class="text-danger">*</span></label>
						<select class="form-select" id="fiscal_base_month" name="fiscal_base_month" required>
							<option value="1">1월 기준 (1월~12월)</option>
							<option value="12">12월 기준 (12월~11월)</option>
						</select>
						<div class="form-text">
							회계연도의 시작 월을 선택합니다.<br>
							예) 1월 기준: 2024.1.1~2024.12.31 (2024기수)<br>
							예) 12월 기준: 2023.12.1~2024.11.30 (2024기수)
						</div>
					</div>
				</form>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
				<button type="button" class="btn btn-primary" id="btnSaveBook">저장</button>
			</div>
		</div>
	</div>
</div>

<!-- 계정 추가 모달 -->
<div class="modal fade" id="accountModal" tabindex="-1" aria-labelledby="accountModalLabel" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="accountModalLabel">하위 계정 추가</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<form id="accountForm">
					<input type="hidden" id="account_parent_id" name="parent_id">
					<input type="hidden" id="account_type" name="account_type">
					<div class="mb-3">
						<label class="form-label">상위 계정</label>
						<input type="text" class="form-control" id="parent_account_name" readonly>
					</div>
					<div class="mb-3">
						<label for="new_account_name" class="form-label">계정명 <span class="text-danger">*</span></label>
						<input type="text" class="form-control" id="new_account_name" name="account_name" required placeholder="계정명을 입력하세요">
					</div>
				</form>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
				<button type="button" class="btn btn-primary" id="btnSaveAccount">저장</button>
			</div>
		</div>
	</div>
</div>

<!-- 계정명 변경 모달 -->
<div class="modal fade" id="renameModal" tabindex="-1" aria-labelledby="renameModalLabel" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="renameModalLabel">계정명 변경</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<form id="renameForm">
					<input type="hidden" id="rename_account_id" name="account_id">
					<div class="mb-3">
						<label for="rename_account_name" class="form-label">계정명 <span class="text-danger">*</span></label>
						<input type="text" class="form-control" id="rename_account_name" name="account_name" required>
					</div>
				</form>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
				<button type="button" class="btn btn-primary" id="btnConfirmRename">변경</button>
			</div>
		</div>
	</div>
</div>

<!-- 삭제 확인 모달 -->
<div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title">삭제 확인</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<p id="deleteConfirmMessage"></p>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
				<button type="button" class="btn btn-danger" id="btnConfirmDelete">삭제</button>
			</div>
		</div>
	</div>
</div>

<!-- 계정 이동 모달 -->
<div class="modal fade" id="moveAccountModal" tabindex="-1" aria-labelledby="moveAccountModalLabel" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="moveAccountModalLabel">계정 이동</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<p id="moveAccountMessage"></p>
				<div class="mb-3">
					<label for="moveToAccountId" class="form-label">이동할 상위계정 선택</label>
					<select class="form-select" id="moveToAccountId" name="moveToAccountId">
						<option value="">최상위로 이동</option>
					</select>
					<div class="form-text">선택한 계정의 하위로 이동합니다. 최상위로 이동하면 관(1레벨)이 됩니다.</div>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
				<button type="button" class="btn btn-primary" id="btnConfirmMove">이동</button>
			</div>
		</div>
	</div>
</div>

<?php $this->load->view('footer'); ?>

<!-- Fancytree -->
<script src="/assets/js/custom/jquery.fancytree-all-deps.min.js"></script>
<script src="/assets/js/account.js?<?php echo WB_VERSION; ?>"></script>
<script>
	window.accountPageData = {
		baseUrl: '<?php echo base_url(); ?>',
		orgId: '<?php echo isset($current_org['org_id']) ? $current_org['org_id'] : ''; ?>'
	};
</script>
</body>
</html>

