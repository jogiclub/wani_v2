<?php $this->load->view('header'); ?>

<link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.min.css">
<link rel="stylesheet" href="/assets/css/homepage_menu.css?<?php echo WB_VERSION; ?>">

<div class="container-fluid pt-2 pb-2">
	<nav class="mb-1" aria-label="breadcrumb">
		<ol class="breadcrumb mb-0">
			<li class="breadcrumb-item"><a href="#!">홈</a></li>
			<li class="breadcrumb-item"><a href="#!">HOMEPAGE</a></li>
			<li class="breadcrumb-item active">홈페이지 메뉴설정</li>
		</ol>
	</nav>
	<div class="col-12 my-1 d-flex align-items-center justify-content-between mb-3">
		<h3 class="page-title col-12 mb-0">홈페이지 메뉴설정</h3>
	</div>

	<?php if (isset($current_org) && $current_org): ?>
		<input type="hidden" id="current_org_id" value="<?php echo $current_org['org_id']; ?>">

		<div class="split-container">
			<!-- 왼쪽: 메뉴 관리 -->
			<div class="split-pane" id="left-pane">
				<div class="card">
					<div class="card-header d-flex justify-content-between align-items-center">
						<h5 class="mb-0">메뉴관리</h5>
						<button type="button" class="btn btn-sm btn-primary" id="btnAddMenu">
							<i class="bi bi-plus-circle"></i>
						</button>
					</div>
					<div class="card-body p-0" style="min-height: 500px;">
						<ul id="menuList" class="menu-sortable list-group list-group-flush">
						</ul>
					</div>
				</div>
			</div>

			<!-- 오른쪽: 컨텐츠 -->
			<div class="split-pane" id="right-pane">
				<div class="card">
					<div class="card-body" style="min-height: 500px;">
						<div id="contentArea">
							<div class="text-center text-muted py-5">
								<i class="bi bi-hand-index display-1"></i>
								<p class="mt-3">왼쪽에서 메뉴를 선택해주세요.</p>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	<?php else: ?>
		<div class="alert alert-warning">
			<i class="bi bi-exclamation-triangle"></i> 조직을 먼저 선택해주세요.
		</div>
	<?php endif; ?>
</div>

<!-- 메뉴 수정 모달 -->
<div class="modal fade" id="menuEditModal" tabindex="-1" aria-labelledby="menuEditModalLabel" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="menuEditModalLabel">메뉴 수정</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<input type="hidden" id="edit_menu_id">
				<input type="hidden" id="edit_parent_id">

				<div class="mb-3">
					<label for="edit_menu_type" class="form-label">메뉴 구분</label>
					<select class="form-select" id="edit_menu_type">
						<option value="link">링크</option>
						<option value="page">페이지</option>
						<option value="board">게시판</option>
					</select>
				</div>

				<div class="mb-3">
					<label for="edit_menu_name" class="form-label">메뉴 이름</label>
					<input type="text" class="form-control" id="edit_menu_name" placeholder="메뉴 이름을 입력하세요">
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
				<button type="button" class="btn btn-primary" id="btnSaveMenuEdit">저장</button>
			</div>
		</div>
	</div>
</div>

<!-- 게시글 작성/수정 Offcanvas -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="boardOffcanvas" aria-labelledby="boardOffcanvasLabel" style="width: 600px;">
	<div class="offcanvas-header">
		<h5 class="offcanvas-title" id="boardOffcanvasLabel">게시글 작성</h5>
		<button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
	</div>
	<div class="offcanvas-body">
		<input type="hidden" id="board_idx">

		<div class="mb-3">
			<label for="board_title" class="form-label">제목</label>
			<input type="text" class="form-control" id="board_title" placeholder="제목을 입력하세요">
		</div>

		<div class="mb-3">
			<label for="board_content" class="form-label">내용</label>
			<textarea class="form-control" id="board_content" rows="15" placeholder="내용을 입력하세요"></textarea>
		</div>

		<div class="d-flex gap-2">
			<button type="button" class="btn btn-primary flex-fill" id="btnSaveBoard">저장</button>
			<button type="button" class="btn btn-secondary" data-bs-dismiss="offcanvas">취소</button>
		</div>
	</div>
</div>

<?php $this->load->view('footer'); ?>

<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/split.js/1.6.5/split.min.js"></script>
<script src="/assets/js/homepage_menu.js?<?php echo WB_VERSION; ?>"></script>
