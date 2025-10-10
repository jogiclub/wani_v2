<?php $this->load->view('mng/header'); ?>
<!-- ParamQuery CSS -->
<link rel="stylesheet" href="/assets/css/custom/pqgrid.min.css?<?php echo WB_VERSION; ?>">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

<div class="container-fluid pt-2 pb-2">
	<nav class="mb-1" aria-label="breadcrumb">
		<ol class="breadcrumb mb-0">
			<li class="breadcrumb-item"><a href="/mng">관리자홈</a></li>
			<li class="breadcrumb-item"><a href="#!">ORG</a></li>
			<li class="breadcrumb-item active">마스터관리</li>
		</ol>
	</nav>

	<div class="col-12 my-1 d-flex align-items-center justify-content-between mb-3">
		<h3 class="page-title col-12 my-1">마스터관리</h3>
	</div>
	<div class="card">
		<div class="card-body p-0 position-relative" style="height: calc(100vh - 200px);">
					<!-- 그리드 스피너 -->
					<div id="gridSpinner" class="d-none justify-content-center align-items-center position-absolute w-100 h-100" style="z-index: 1000; background: rgba(255, 255, 255, 0.8);">
						<div class="text-center">
							<div class="spinner-border text-primary mb-2" role="status">
								<span class="visually-hidden">로딩 중...</span>
							</div>
							<div class="small text-muted">마스터 정보 로딩 중...</div>
						</div>
					</div>

					<!-- ParamQuery Grid -->
					<div id="masterGrid"></div>
				</div>
	</div>


</div>

<!-- 마스터 정보 수정 offcanvas -->
<div class="offcanvas offcanvas-end" data-bs-scroll="true" data-bs-backdrop="false" tabindex="-1" id="masterOffcanvas" aria-labelledby="masterOffcanvasLabel" style="width: 600px;">
	<div class="offcanvas-header text-start">
		<div class="d-flex align-items-center gap-2 flex-grow-1">
			<h5 class="offcanvas-title mb-0" id="masterOffcanvasLabel">마스터 정보</h5>
		</div>
		<button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
	</div>

	<div class="offcanvas-body">
		<form id="masterForm">
			<input type="hidden" id="edit_user_id" name="user_id">

			<!-- 기본 정보 -->
			<div class="mb-4">
				<h6 class="border-bottom pb-2 mb-3">기본 정보</h6>

				<div class="mb-3">
					<label for="edit_user_name" class="form-label">이름 <span class="text-danger">*</span></label>
					<input type="text" class="form-control" id="edit_user_name" name="user_name" required>
				</div>

				<div class="mb-3">
					<label for="edit_user_mail" class="form-label">이메일 <span class="text-danger">*</span></label>
					<input type="email" class="form-control" id="edit_user_mail" name="user_mail" required>
				</div>

				<div class="mb-3">
					<label for="edit_user_hp" class="form-label">연락처</label>
					<input type="text" class="form-control" id="edit_user_hp" name="user_hp">
				</div>
			</div>

			<!-- 마스터 메뉴 권한 -->
			<div class="mb-4">
				<h6 class="border-bottom pb-2 mb-3">마스터 메뉴 권한</h6>
				<div class="alert alert-info">
					<small>체크된 메뉴만 마스터가 접근할 수 있습니다.</small>
				</div>
				<div id="menu_permissions">
					<!-- 메뉴 체크박스가 동적으로 로드됩니다 -->
				</div>
			</div>

			<!-- 마스터 카테고리 권한 -->
			<div class="mb-4">
				<h6 class="border-bottom pb-2 mb-3">마스터 카테고리 권한</h6>
				<div class="alert alert-info">
					<small>체크된 카테고리만 조직관리 화면의 트리에서 보여집니다. 체크하지 않은 카테고리는 해당 조직과 모든 하위 노드가 숨겨집니다.</small>
				</div>
				<div id="category_permissions">
					<!-- 카테고리 체크박스가 동적으로 로드됩니다 -->
				</div>
			</div>
		</form>
	</div>

	<div class="offcanvas-footer border-top p-3">
		<div class="d-flex gap-2">
			<button type="button" class="btn btn-secondary flex-fill" data-bs-dismiss="offcanvas">취소</button>
			<button type="button" class="btn btn-primary flex-fill" id="saveMasterBtn">저장</button>
		</div>
	</div>
</div>

<?php $this->load->view('mng/footer'); ?>

<!-- ParamQuery Grid 라이브러리 -->
<script src="/assets/js/custom/pqgrid.min.js?<?php echo WB_VERSION; ?>"></script>
<script src="/assets/js/mng_master.js?<?php echo WB_VERSION; ?>"></script>

<script>

</script>
