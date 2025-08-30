<?php
$this->load->view('header');
?>

<!-- Member CSS 재사용 -->
<link rel="stylesheet" href="/assets/css/member.css?<?php echo date('Ymdhis'); ?>">

<!-- Fancytree CSS -->
<link rel="stylesheet"
	  href="https://cdnjs.cloudflare.com/ajax/libs/jquery.fancytree/2.38.5/skin-vista/ui.fancytree.min.css">

<div class="container pt-2 pb-2">
	<nav class="mb-3" aria-label="breadcrumb">
		<ol class="breadcrumb mb-0">
			<li class="breadcrumb-item"><a href="#!">홈</a></li>
			<li class="breadcrumb-item"><a href="#!">SETTING</a></li>
			<li class="breadcrumb-item active">그룹설정</li>
		</ol>
	</nav>
	<div class="row align-items-center justify-content-between g-3 mb-3">
		<h3 class="page-title col-12 my-1">그룹설정</h3>
	</div>

	<!-- Split.js를 위한 단일 컨테이너 -->
	<div class="split-container">
		<!-- 왼쪽: 그룹 트리 -->
		<div class="split-pane" id="left-pane">
			<div class="card h-100">
				<div class="card-body p-0 position-relative">
					<!-- 트리 스피너 -->
					<div id="treeSpinner"
						 class="d-flex justify-content-center align-items-center position-absolute h-100"
						 style="z-index: 1000; background: rgba(255, 255, 255, 0.8);">
						<div class="text-center">
							<div class="spinner-border text-primary mb-2" role="status">
								<span class="visually-hidden">로딩 중...</span>
							</div>
							<div class="small text-muted">그룹 정보 로딩 중...</div>
						</div>
					</div>
					<div id="groupTree" class="tree-container"></div>
				</div>
			</div>
		</div>

		<!-- 오른쪽: 관리 버튼들 -->
		<div class="split-pane" id="right-pane">
			<div class="card h-100">
				<div class="card-header">
					<div class="row">
						<div class="col-12 d-flex align-items-center">
							<h5 class="mb-0" id="selectedGroupName">
								<i class="bi bi-folder"></i> 그룹을 선택해주세요
							</h5>
						</div>
					</div>
				</div>
				<div class="card-body d-flex flex-column justify-content-center align-items-center">

						<div class="btn-group-vertical" role="group" aria-label="Vertical button group" id="groupManagementButtons" style="display: none;">
							<div class="btn-group" role="group">
								<button type="button" class="btn btn-primary " id="btnAddGroup">
									<i class="bi bi-folder-plus"></i> 그룹생성
								</button>

								<button type="button" class="btn btn-warning " id="btnRenameGroup" disabled>
									<i class="bi bi-pencil-square"></i> 그룹명변경
								</button>
								<button type="button" class="btn btn-danger " id="btnDeleteGroup" disabled>
									<i class="bi bi-folder-minus"></i> 그룹삭제
								</button>

								<button type="button" class="btn btn-success " id="btnMoveGroup" disabled>
									<i class="bi bi-arrow-right-square"></i> 그룹이동
								</button>
							</div>

					</div>

					<div class="text-center text-muted" id="noSelectionMessage">
						<i class="bi bi-folder" style="font-size: 4rem; opacity: 0.3;"></i>
						<p class="mt-3">왼쪽에서 그룹을 선택하면<br>관리 버튼이 나타납니다.</p>
					</div>
				</div>


			</div>
		</div>
	</div>
</div>
</div>

<!-- 그룹명 변경 모달 -->
<div class="modal fade" id="renameGroupModal" tabindex="-1" aria-labelledby="renameGroupModalLabel" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="renameGroupModalLabel">그룹명 변경</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<div class="mb-3">
					<label for="newGroupName" class="form-label">그룹명</label>
					<input type="text" class="form-control" id="newGroupName" name="newGroupName" maxlength="50"
						   required>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
				<button type="button" class="btn btn-warning" id="confirmRenameGroupBtn">저장</button>
			</div>
		</div>
	</div>
</div>

<!-- 그룹 삭제 확인 모달 -->
<div class="modal fade" id="deleteGroupModal" tabindex="-1" aria-labelledby="deleteGroupModalLabel" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="deleteGroupModalLabel">그룹 삭제 확인</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<p id="deleteGroupMessage"></p>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
				<button type="button" class="btn btn-danger" id="confirmDeleteGroupBtn">삭제</button>
			</div>
		</div>
	</div>
</div>

<!-- 그룹 이동 모달 -->
<div class="modal fade" id="moveGroupModal" tabindex="-1" aria-labelledby="moveGroupModalLabel" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="moveGroupModalLabel">그룹 이동</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<p id="moveGroupMessage"></p>
				<div class="mb-3">
					<label for="moveToGroupIdx" class="form-label">이동할 상위그룹 선택</label>
					<select class="form-select" id="moveToGroupIdx" name="moveToGroupIdx">
						<option value="">최상위로 이동</option>
					</select>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
				<button type="button" class="btn btn-primary" id="confirmMoveGroupBtn">이동</button>
			</div>
		</div>
	</div>
</div>

<!-- Toast 컨테이너 -->
<div id="toastContainer" class="position-fixed top-0 end-0 p-3"></div>

<?php $this->load->view('footer'); ?>

<!-- Split.js -->
<script src="https://unpkg.com/split.js@1.6.2/dist/split.min.js"></script>

<!-- Fancytree -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.fancytree/2.38.5/jquery.fancytree-all-deps.min.js"></script>

<!-- Group Setting JS -->
<script src="/assets/js/group_setting.js?<?php echo date('Ymdhis'); ?>"></script>

<script>
	// PHP 데이터를 JavaScript로 전달
	window.groupSettingPageData = {
		baseUrl: '<?php echo base_url(); ?>',
		currentOrgId: <?php echo $current_org['org_id']; ?>,
		currentOrgName: '<?php echo addslashes($current_org['org_name']); ?>'
	};
</script>
