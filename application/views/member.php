<!-- 파일 위치: application/views/member.php -->
<!-- 역할: Split.js 라이브러리를 적용한 회원관리 화면 레이아웃 -->

<?php $this->load->view('header'); ?>

<!-- Member CSS -->
<link rel="stylesheet" href="/assets/css/member.css?<?php echo date('Ymdhis'); ?>">

<!-- ParamQuery CSS -->
<link rel="stylesheet" href="http://cdnjs.cloudflare.com/ajax/libs/pqGrid/3.5.1/pqgrid.min.css">

<!-- Fancytree CSS - Vista 스킨 사용 (더 안정적) -->
<link rel="stylesheet"
	  href="https://cdnjs.cloudflare.com/ajax/libs/jquery.fancytree/2.38.5/skin-vista/ui.fancytree.min.css">

<!-- Croppie CSS -->
<link href="https://cdn.jsdelivr.net/npm/croppie@2.6.5/croppie.min.css" rel="stylesheet">

<div class="container-fluid pt-2 pb-2">
	<nav class="mb-3" aria-label="breadcrumb">
		<ol class="breadcrumb mb-0">
			<li class="breadcrumb-item"><a href="#!">홈</a></li>
			<li class="breadcrumb-item"><a href="#!">MEMBER</a></li>
			<li class="breadcrumb-item active">회원관리</li>
		</ol>
	</nav>
	<div class="row align-items-center justify-content-between g-3 mb-3">
		<h3 class="page-title col-12 my-1">회원관리</h3>
	</div>

	<!-- Split.js를 위한 단일 컨테이너로 변경 -->
	<div class="split-container">
		<!-- 왼쪽: 그룹 트리 -->
		<div class="split-pane" id="left-pane">
			<div class="card h-100">
				<div class="card-body p-0">
					<div id="groupTree" class="tree-container"></div>
				</div>
			</div>
		</div>

		<!-- 오른쪽: 회원 목록 -->
		<div class="split-pane" id="right-pane">
			<div class="card h-100">
				<div class="card-header d-flex justify-content-between align-items-center">
					<h5 class="mb-0" id="selectedOrgName">
						<i class="bi bi-people"></i> 조직을 선택해주세요
					</h5>


					<div class="input-group input-group-sm" style="width: 300px;">
						<input type="text" class="form-control" placeholder="회원명, 휴대폰번호 등" aria-label="Member's name" aria-describedby="button-search">
						<button class="btn btn-sm btn-outline-secondary" type="button" id="button-search"><i class="bi bi-search"></i> 검색</button>
					</div>


					<div class="btn-group" role="group">
						<button type="button" class="btn btn-sm btn-outline-primary" id="btnAddMember">
							<i class="bi bi-person-plus"></i> 회원 추가
						</button>
						<button type="button" class="btn btn-sm btn-outline-success" id="btnMoveMember" disabled>
							<i class="bi bi-arrow-right-square"></i> 이동
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


			<ul class="nav nav-tabs" id="memberTab" role="tablist">

				<li class="nav-item" role="presentation">
					<button class="nav-link active" id="profile-tab" data-bs-toggle="tab" data-bs-target="#profile-tab-pane" type="button" role="tab" aria-controls="profile-tab-pane" aria-selected="false">회원정보</button>
				</li>
				<li class="nav-item" role="presentation">
					<button class="nav-link" id="detail-tab" data-bs-toggle="tab" data-bs-target="#detail-tab-pane" type="button" role="tab" aria-controls="home-tab-pane" aria-selected="true">상세정보</button>
				</li>
				<li class="nav-item" role="presentation">
					<button class="nav-link" id="memo-tab" data-bs-toggle="tab" data-bs-target="#memo-tab-pane" type="button" role="tab" aria-controls="contact-tab-pane" aria-selected="false">회원메모</button>
				</li>

			</ul>

			<div class="tab-content" id="memberTabContent">
				<div class="tab-pane fade show active" id="profile-tab-pane" role="tabpanel" aria-labelledby="profile-tab" tabindex="0">
					<div class="row mt-3">
						<div class="d-flex justify-content-end text-end">
							<div class="form-check form-switch me-3">
								<input type="checkbox" class="form-check-input" id="leader_yn" name="leader_yn">
								<label class="form-check-label" for="leader_yn">리더</label>
							</div>
							<div class="form-check form-switch">
								<input type="checkbox" class="form-check-input" id="new_yn" name="new_yn">
								<label class="form-check-label" for="new_yn">새가족</label>
							</div>
						</div>
					</div>

					<!-- 사진 업로드 영역 -->
					<div class="row mb-4">
						<div class="col-12 text-center">
							<!-- 사진 업로드 버튼 -->
							<div id="photoUpload">
								<label for="member_photo" class="d-inline-block">
									<div class="border border-2 border-dashed rounded-circle d-flex align-items-center justify-content-center" style="width: 120px; height: 120px; cursor: pointer;">
										<i class="bi bi-person-plus fs-1 text-muted"></i>
									</div>
								</label>
								<input type="file" id="member_photo" name="member_photo" accept="image/*" class="d-none">
								<div class="mt-2 text-muted small">사진을 선택하세요</div>
							</div>

							<!-- 사진 미리보기 -->
							<div id="photoPreview" style="display: none;">
								<img id="previewImage" src="" alt="미리보기" class="rounded-circle" style="width: 120px; height: 120px; object-fit: cover;">
								<div class="mt-2">
									<button type="button" class="btn btn-sm btn-outline-primary" id="cropPhoto">
										<i class="bi bi-crop"></i> 크롭
									</button>
									<button type="button" class="btn btn-sm btn-outline-danger" id="removePhoto">
										<i class="bi bi-trash"></i> 삭제
									</button>
								</div>
							</div>

							<!-- Croppie 영역 -->
							<div id="cropContainer" style="display: none;">
								<div id="cropBox" style="width: 300px; height: 300px; margin: 0 auto;"></div>
								<div class="mt-3">
									<button type="button" class="btn btn-sm btn-success" id="saveCrop">
										<i class="bi bi-check"></i> 적용
									</button>
									<button type="button" class="btn btn-sm btn-secondary" id="cancelCrop">
										<i class="bi bi-x"></i> 취소
									</button>
								</div>
							</div>
						</div>
					</div>

					<div class="row">
						<!-- 개인정보 입력 필드들 -->
						<div class="col-6 mb-3">
							<label for="member_name" class="form-label">이름 <span class="text-danger">*</span></label>
							<input type="text" class="form-control" id="member_name" name="member_name" required>
						</div>

						<div class="col-6 mb-3">
							<label for="member_nick" class="form-label">별명</label>
							<input type="text" class="form-control" id="member_nick" name="member_nick">
						</div>

						<div class="col-6 mb-3">
							<label for="member_phone" class="form-label">연락처</label>
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



						<div class="col-6 mb-3">
							<label for="area_idx" class="form-label">소그룹</label>
							<select class="form-select" id="area_idx" name="area_idx">
								<option value="">소그룹 선택</option>
							</select>
						</div>

						<div class="col-12 mb-3">
							<label for="member_etc" class="form-label">메모</label>
							<textarea class="form-control" id="member_etc" name="member_etc" rows="3"></textarea>
						</div>
					</div>
				</div>
				<div class="tab-pane fade" id="detail-tab-pane" role="tabpanel" aria-labelledby="detail-tab" tabindex="0">
					<div class="row mt-3" id="detailFieldsContainer">
						<!-- 동적으로 생성될 상세필드들 -->
					</div>
					<div class="text-center mt-3">
						<div class="spinner-border text-primary" role="status" id="detailFieldsLoading" style="display: none;">
							<span class="visually-hidden">로딩중...</span>
						</div>
						<div class="text-muted" id="detailFieldsEmpty" style="display: none;">
							<i class="bi bi-info-circle me-2"></i>설정된 상세필드가 없습니다.
						</div>
					</div>
				</div>
				<div class="tab-pane fade" id="memo-tab-pane" role="tabpanel" aria-labelledby="memo-tab" tabindex="0">
					<div class="row mt-3">
						<div class="col-12">
							<!-- 새 메모 입력 영역 -->
							<div class="memo-input-section pb-3 mb-3">
								<div class="row">
									<div class="col-9">
										<textarea class="form-control" id="newMemoContent" rows="2" placeholder="새로운 메모를 입력하세요."></textarea>
									</div>
									<div class="col-3 d-flex align-items-center justify-content-end">
										<button type="button" class="btn btn-sm btn-outline-primary" id="addMemoBtn"><i class="bi bi-plus-lg"></i> 메모추가</button>
									</div>
								</div>
							</div>

							<!-- 기존 메모 목록 영역 -->
							<div class="memo-list-section">
								<div id="memoList">
									<div class="text-center text-muted py-3" id="emptyMemoMessage">
										메모를 불러오고 있습니다...
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>






		</form>


	</div>

	<!-- 하단 고정 버튼 영역 -->
	<div class="offcanvas-footer">
		<div class="d-flex gap-2 p-3 border-top bg-light">
			<button type="button" class="btn btn-secondary flex-fill" data-bs-dismiss="offcanvas">취소</button>
			<button type="button" class="btn btn-primary flex-fill" id="btnSaveMember">저장</button>
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

<!-- 회원 이동 확인 모달 -->
<div class="modal fade" id="moveMemberModal" tabindex="-1" aria-labelledby="moveMemberModalLabel" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="moveMemberModalLabel">회원 이동</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<p id="moveMessage"></p>
				<div class="mb-3">
					<label for="moveToAreaIdx" class="form-label">이동할 소그룹 선택</label>
					<select class="form-select" id="moveToAreaIdx" name="moveToAreaIdx">
						<option value="">소그룹 선택</option>
					</select>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
				<button type="button" class="btn btn-primary" id="confirmMoveBtn">이동</button>
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


<script src="https://cdnjs.cloudflare.com/ajax/libs/split.js/1.6.2/split.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.fancytree/2.38.5/jquery.fancytree-all-deps.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pqGrid/3.5.1/pqgrid.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/croppie@2.6.5/croppie.min.js"></script>

<script>
	window.memberPageData = {
		baseUrl: '<?php echo base_url(); ?>'
	};
</script>
<script src="/assets/js/member.js?<?php echo date('Ymdhis'); ?>"></script>
