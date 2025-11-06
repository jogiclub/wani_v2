<?php $this->load->view('header'); ?>

<link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.min.css">
<!-- Dropzone CSS -->
<link rel="stylesheet" href="https://unpkg.com/dropzone@5/dist/min/dropzone.min.css" type="text/css" />
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
							<i class="bi bi-plus-lg"></i>
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

<!-- 파일 위치: application/views/homepage_menu.php -->
<!-- 역할: 게시글 작성/수정 Offcanvas - Dropzone 자동 초기화 방지 버전 -->

<!-- 게시글 작성/수정 Offcanvas -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="boardOffcanvas" aria-labelledby="boardOffcanvasLabel" style="width: 600px;">
	<div class="offcanvas-header">
		<h5 class="offcanvas-title" id="boardOffcanvasLabel">게시글 작성</h5>
		<button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
	</div>
	<div class="offcanvas-body">
		<input type="hidden" id="board_idx">

		<div class="mb-3">
			<label for="board_title" class="form-label mb-1">제목</label>
			<input type="text" class="form-control" id="board_title" placeholder="제목을 입력하세요">
		</div>

		<div class="mb-3">
			<label for="board_content" class="form-label mb-1">내용</label>
			<textarea class="form-control" id="board_content" rows="10" placeholder="내용을 입력하세요"></textarea>
		</div>

		<div class="mb-3">
			<label for="youtube_url" class="form-label mb-1">YouTube URL</label>
			<input type="url" class="form-control" id="youtube_url" placeholder="https://www.youtube.com/watch?v=...">
			<small class="text-muted">YouTube 동영상 URL을 입력하세요</small>
		</div>

		<div class="mb-3">
			<label class="form-label mb-1">파일 첨부</label>
			<div id="dropzoneArea"></div>
			<input type="hidden" id="uploaded_files" value="">
		</div>

		<div class="d-flex gap-2">
			<button type="button" class="btn btn-danger" id="btnDeleteBoard" style="display: none;">
				<i class="bi bi-trash"></i> 삭제
			</button>
			<button type="button" class="btn btn-primary flex-fill" id="btnSaveBoard">저장</button>
		</div>
	</div>
</div>


<!-- Dropzone JS - autoDiscover 비활성화 스크립트 추가 -->
<script src="https://unpkg.com/dropzone@5/dist/min/dropzone.min.js"></script>
<script>
	// Dropzone 자동 초기화 방지 (전역 설정)
	Dropzone.autoDiscover = false;
</script>


<?php $this->load->view('footer'); ?>


<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/split.js/1.6.5/split.min.js"></script>

<!-- Dropzone JS -->
<script src="https://unpkg.com/dropzone@5/dist/min/dropzone.min.js"></script>

<!-- Editor.js Core -->
<script src="https://cdn.jsdelivr.net/npm/@editorjs/editorjs@latest"></script>

<!-- Editor.js Basic Tools -->
<script src="https://cdn.jsdelivr.net/npm/@editorjs/header@latest"></script>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/paragraph@latest"></script>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/list@latest"></script>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/quote@latest"></script>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/code@latest"></script>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/delimiter@latest"></script>

<!-- Editor.js Media Tools -->
<script src="https://cdn.jsdelivr.net/npm/@editorjs/image@latest"></script>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/embed@latest"></script>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/link@latest"></script>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/attaches@latest"></script>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/raw@latest"></script>

<!-- Editor.js Table & Layout Tools -->
<script src="https://cdn.jsdelivr.net/npm/@editorjs/table@latest"></script>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/warning@latest"></script>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/checklist@latest"></script>

<!-- Editor.js Text Styling Tools -->
<script src="https://cdn.jsdelivr.net/npm/@editorjs/marker@latest"></script>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/inline-code@latest"></script>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/underline@latest"></script>

<!-- Editor.js Advanced Tools -->
<script src="https://cdn.jsdelivr.net/npm/@editorjs/nested-list@latest"></script>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/personality@latest"></script>


<!-- 게시판 블록 커스텀 플러그인 -->
<script src="/assets/js/wani-preach.js?<?php echo WB_VERSION; ?>"></script>
<script src="/assets/js/homepage_menu.js?<?php echo WB_VERSION; ?>"></script>
