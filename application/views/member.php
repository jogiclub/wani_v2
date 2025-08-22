<!DOCTYPE html>
<html lang="ko">
<head>
	<?php $this->load->view('header'); ?>
	<!-- Fancytree CSS - Vista 스킨 사용 (더 안정적) -->
	<link rel="stylesheet"
		  href="https://cdnjs.cloudflare.com/ajax/libs/jquery.fancytree/2.38.5/skin-vista/ui.fancytree.min.css">
	<!-- ParamQuery CSS -->
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/pqGrid/3.5.1/pqgrid.min.css">
	<!-- Member CSS -->
	<link rel="stylesheet" href="/assets/css/member.css?<?php echo date('Ymdhis'); ?>">
</head>
<body>

<div class="container-fluid pt-2 pb-2">
	<nav class="mb-3" aria-label="breadcrumb">
		<ol class="breadcrumb mb-0">
			<li class="breadcrumb-item"><a href="#!">홈</a></li>
			<li class="breadcrumb-item"><a href="#!">MEMBER</a></li>
			<li class="breadcrumb-item active">회원관리</li>
		</ol>
	</nav>
	<div class="row align-items-center justify-content-between g-3 mb-4">
		<h3 class="page-title col-6 my-1">회원관리</h3>
		<div class="col-6 my-1">

		</div>
	</div>

	<div class="row">
		<!-- 왼쪽: 그룹 트리 -->
		<div class="col-md-4 col-lg-3">
			<div class="card">
				<div class="card-header">
					<h5 class="mb-0">조직 구조</h5>
				</div>
				<div class="card-body p-0">
					<div id="groupTree" class="tree-container"></div>
				</div>
			</div>
		</div>

		<!-- 오른쪽: 회원 목록 -->
		<div class="col-md-8 col-lg-9">
			<div class="card">
				<div class="card-header d-flex justify-content-between align-items-center">
					<h5 class="mb-0" id="selectedOrgName">
						<i class="bi bi-people"></i> 조직을 선택해주세요
					</h5>
					<div class="btn-group" role="group">
						<button type="button" class="btn btn-sm btn-outline-primary" id="btnAddMember">
							<i class="bi bi-person-plus"></i> 회원 추가
						</button>
						<button type="button" class="btn btn-sm btn-outline-success" id="btnEditMember" disabled>
							<i class="bi bi-pencil"></i> 수정
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

<!-- 회원 정보 수정 모달 -->
<div class="modal fade" id="memberModal" tabindex="-1" aria-labelledby="memberModalLabel" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="memberModalLabel">회원 정보</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<form id="memberForm">
					<input type="hidden" id="member_idx" name="member_idx">

					<div class="mb-3">
						<label for="member_name" class="form-label">이름</label>
						<input type="text" class="form-control" id="member_name" name="member_name" required>
					</div>

					<div class="row">
						<div class="col-md-6">
							<div class="mb-3">
								<label for="member_birth" class="form-label">생년월일</label>
								<input type="date" class="form-control" id="member_birth" name="member_birth">
							</div>
						</div>
						<div class="col-md-6">
							<div class="mb-3">
								<label for="grade" class="form-label">학년</label>
								<input type="number" class="form-control" id="grade" name="grade">
							</div>
						</div>
					</div>

					<div class="mb-3">
						<label for="area_idx" class="form-label">소그룹</label>
						<select class="form-select" id="area_idx" name="area_idx">
							<option value="">소그룹 선택</option>
						</select>
					</div>

					<div class="row">
						<div class="col-md-6">
							<div class="form-check mb-3">
								<input class="form-check-input" type="checkbox" id="leader_yn" name="leader_yn"
									   value="Y">
								<label class="form-check-label" for="leader_yn">
									리더 여부
								</label>
							</div>
						</div>
						<div class="col-md-6">
							<div class="form-check mb-3">
								<input class="form-check-input" type="checkbox" id="new_yn" name="new_yn" value="Y">
								<label class="form-check-label" for="new_yn">
									신규 회원
								</label>
							</div>
						</div>
					</div>
				</form>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
				<button type="button" class="btn btn-primary" id="btnSaveMember">저장</button>
			</div>
		</div>
	</div>
</div>

<!-- JavaScript 라이브러리 로드 -->
<?php $this->load->view('footer'); ?>

<!-- jQuery UI (Fancytree 의존성) -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.13.3/jquery-ui.min.js"></script>
<!-- Fancytree JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.fancytree/2.38.5/jquery.fancytree-all.min.js"></script>
<!-- ParamQuery JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/pqGrid/3.5.1/pqgrid.min.js"></script>

<script>
	// PHP 데이터를 JavaScript로 전달
	window.memberPageData = {
		orgs: <?php echo json_encode($orgs); ?>,
		baseUrl: '<?php echo base_url(); ?>'
	};
</script>

<!-- Member JS (라이브러리들이 로드된 후에 실행) -->
<script src="/assets/js/member.js?<?php echo date('Ymdhis'); ?>"></script>

</body>
</html>
