<?php $this->load->view('header'); ?>

<!-- Member CSS -->
<link rel="stylesheet" href="/assets/css/member.css?<?php echo date('Ymdhis'); ?>">

<!-- ParamQuery CSS -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/pqGrid/3.5.1/pqgrid.min.css">

<!-- Fancytree CSS - Vista 스킨 사용 (더 안정적) -->
<link rel="stylesheet"
	  href="https://cdnjs.cloudflare.com/ajax/libs/jquery.fancytree/2.38.5/skin-vista/ui.fancytree.min.css">




<div class="container-fluid pt-2 pb-2">
	<nav class="mb-3" aria-label="breadcrumb">
		<ol class="breadcrumb mb-0">
			<li class="breadcrumb-item"><a href="#!">홈</a></li>
			<li class="breadcrumb-item"><a href="#!">MEMBER</a></li>
			<li class="breadcrumb-item active">회원관리</li>
		</ol>
	</nav>
	<div class="row align-items-center justify-content-between g-3 mb-4">
		<h3 class="page-title col-12 my-1">회원관리</h3>
	</div>

	<div class="row">
		<!-- 왼쪽: 그룹 트리 -->
		<div class="col-md-3 col-lg-2">
			<div class="card">
				<div class="card-body p-0">
					<div id="groupTree" class="tree-container"></div>
				</div>
			</div>
		</div>

		<!-- 오른쪽: 회원 목록 -->
		<div class="col-md-9 col-lg-10">
			<div class="card">
				<div class="card-header d-flex justify-content-between align-items-center">
					<h5 class="mb-0" id="selectedOrgName">
						<i class="bi bi-people"></i> 조직을 선택해주세요
					</h5>
					<div class="btn-group" role="group">
						<button type="button" class="btn btn-sm btn-outline-primary" id="btnAddMember">
							<i class="bi bi-person-plus"></i> 회원 추가
						</button>
						<button type="button" class="btn btn-sm btn-outline-danger" id="btnDeleteMember" disabled>
							<i class="bi bi-trash"></i> 삭제
						</button>
					</div>
				</div>
				<div class="card-body p-0">
					<!-- ParamQuery Grid가 여기에 렌더링됩니다 -->
					<div id="memberGrid"></div>
				</div>
			</div>
		</div>
	</div>
</div>

<!-- 회원 정보 수정 offcanvas -->
<div class="offcanvas offcanvas-end" data-bs-scroll="true" data-bs-backdrop="false" tabindex="-1" id="memberOffcanvas" aria-labelledby="memberOffcanvasLabel">
	<div class="offcanvas-header text-start">
		<h5 class="offcanvas-title" id="memberOffcanvasLabel">회원 정보 수정</h5>
		<button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
	</div>
	<div class="offcanvas-body">
		<form id="memberForm" enctype="multipart/form-data">
			<input type="hidden" id="member_idx" name="member_idx">
			<input type="hidden" id="org_id" name="org_id">

			<div class="row">
				<!-- 스위치 버튼들 -->
				<div class="d-flex justify-content-end text-end mb-4">
					<div class="form-check form-switch">
						<input type="checkbox" class="form-check-input" id="leader_yn" name="leader_yn">
						<label class="form-check-label" for="leader_yn">리더</label>
					</div>
					<div class="form-check form-switch ms-3">
						<input type="checkbox" class="form-check-input" id="new_yn" name="new_yn">
						<label class="form-check-label" for="new_yn">새가족</label>
					</div>
				</div>

				<!-- 사진 영역 -->
				<div class="col-12 mb-3 text-center">
					<div id="photoPreview" style="display: none;">
						<img id="previewImage" src="" alt="미리보기" style="width: 150px; height: 150px; object-fit: cover; border-radius: 50%;">
						<div class="mt-2">
							<button type="button" class="btn btn-sm btn-danger" id="removePhoto">사진 삭제</button>
						</div>
					</div>
					<div id="photoUpload">
						<label for="member_photo" class="form-label">
							<div style="width: 150px; height: 150px; border: 2px dashed #ccc; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto; cursor: pointer;">
								<i class="bi bi-camera" style="font-size: 2rem; color: #ccc;"></i>
							</div>
						</label>
						<input type="file" class="form-control d-none" id="member_photo" name="member_photo" accept="image/*">
						<div class="mt-2">
							<small class="text-muted">클릭하여 사진 선택</small>
						</div>
					</div>
				</div>

				<!-- 개인정보 입력 필드들 -->
				<div class="col-6 mb-3">
					<label for="member_name" class="form-label">이름 <span class="text-danger">*</span></label>
					<input type="text" class="form-control" id="member_name" name="member_name" required>
				</div>

				<div class="col-6 mb-3">
					<label for="member_nick" class="form-label">별명 </label>
					<input type="text" class="form-control" id="member_nick" name="member_nick">
				</div>

				<div class="col-6 mb-3">
					<label for="member_phone" class="form-label">휴대폰번호 <span class="text-danger">*</span></label>
					<input type="tel" class="form-control" id="member_phone" name="member_phone">
				</div>

				<div class="col-6 mb-3">
					<label for="member_birth" class="form-label">생년월일</label>
					<input type="date" class="form-control" id="member_birth" name="member_birth">
				</div>

				<div class="col-6 mb-3">
					<label for="member_address" class="form-label">주소</label>
					<input type="text" class="form-control" id="member_address" name="member_address">
				</div>
				<div class="col-6 mb-3">
					<label for="member_address_detail" class="form-label">상세주소</label>
					<input type="text" class="form-control" id="member_address_detail" name="member_address_detail">
				</div>
				<div class="col-12">
					<label for="member_etc" class="form-label">메모</label>
					<input type="text" class="form-control" id="member_etc" name="member_etc">
				</div>


			</div>
		</form>

		<!-- 버튼 영역 -->
		<div class="d-flex gap-2 mt-4">
			<button type="button" class="btn btn-secondary" data-bs-dismiss="offcanvas" style="flex: 1">취소</button>
			<button type="button" class="btn btn-primary" id="btnSaveMember" style="flex: 1">저장</button>
		</div>
	</div>
</div>

<!-- 회원 삭제 확인 모달 -->
<div class="modal fade" id="deleteMemberModal" tabindex="-1" aria-labelledby="deleteMemberModalLabel" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="deleteMemberModalLabel">회원 삭제 확인</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<p id="deleteMessage"></p>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
				<button type="button" class="btn btn-danger" id="confirmDeleteBtn">삭제</button>
			</div>
		</div>
	</div>
</div>


<!-- Toast 메시지 -->
<div class="toast-container position-fixed bottom-0 end-0 p-3">
	<div id="memberToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
		<div class="toast-header">
			<strong class="me-auto">회원관리</strong>
			<button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
		</div>
		<div class="toast-body"></div>
	</div>
</div>

<?php $this->load->view('footer'); ?>
<!-- Fancytree JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.fancytree/2.38.5/jquery.fancytree-all-deps.min.js"></script>
<!-- ParamQuery Grid JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/pqGrid/3.5.1/pqgrid.min.js"></script>
<!-- Member JS -->
<script>
	window.memberPageData = {
		baseUrl: '<?php echo base_url(); ?>'
	};
</script>
<script src="/assets/js/member.js?<?php echo date('Ymdhis'); ?>"></script>
