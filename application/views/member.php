<?php $this->load->view('header'); ?>

<!-- Member CSS -->
<link rel="stylesheet" href="/assets/css/member.css?<?php echo WB_VERSION; ?>">

<!-- ParamQuery CSS -->
<link rel="stylesheet" href="/assets/css/custom/pqgrid.min.css?<?php echo WB_VERSION; ?>">

<!-- Fancytree CSS - Vista 스킨 사용 (더 안정적) -->
<link rel="stylesheet" href="/assets/css/custom/ui.fancytree.min.css?<?php echo WB_VERSION; ?>">

<!-- Croppie CSS -->
<link href="https://cdn.jsdelivr.net/npm/croppie@2.6.5/croppie.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

<div class="container-fluid pt-2 pb-2">
	<nav class="mb-1" aria-label="breadcrumb">
		<ol class="breadcrumb mb-0">
			<li class="breadcrumb-item"><a href="#!">홈</a></li>
			<li class="breadcrumb-item"><a href="#!">MEMBER</a></li>
			<li class="breadcrumb-item active">회원관리</li>
		</ol>
	</nav>
	<div class="col-12 my-1 d-flex align-items-center justify-content-between mb-3">
		<h3 class="page-title col-12 mb-0">회원관리</h3>
	</div>



	<!-- Split.js를 위한 단일 컨테이너로 변경 -->
	<div class="split-container">
		<!-- 왼쪽: 그룹 트리 -->
		<div class="split-pane" id="left-pane">
			<div class="card">
				<div class="card-body card-height p-0 position-relative">
					<!-- 트리 스피너 -->
					<div id="treeSpinner" class="d-flex justify-content-center align-items-center position-absolute w-100 h-100" style="z-index: 1000; background: rgba(255, 255, 255, 0.8);">
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

		<!-- 오른쪽: 회원 목록 -->
		<div class="split-pane" id="right-pane">
			<div class="card">
				<div class="card-header">
					<div class="row flex-column flex-lg-row">
						<div class="col-12 col-lg-4 d-flex align-items-center">
							<h5 class="mb-0 text-truncate" id="selectedOrgName">
								<i class="bi bi-people"></i> 조직을 선택해주세요
							</h5>
						</div>

						<div class="col-12 col-lg-3 d-flex justify-content-start justify-content-lg-end mt-2 mt-lg-0">
							<div class="input-group input-group-sm">
								<input type="text" class="form-control" placeholder="회원명, 휴대폰번호 등" aria-label="Member's name" aria-describedby="button-search">
								<button class="btn btn-sm btn-outline-secondary" type="button" id="button-search"><i class="bi bi-search"></i> 검색</button>
							</div>
						</div>

						<div class="col-12 col-lg-5 d-flex justify-content-start justify-content-lg-end mt-2 mt-lg-0">
							<div class="btn-group" role="group">
								<button type="button" class="btn btn-sm btn-outline-primary" id="btnAddMember"><i class="bi bi-person-plus"></i> 회원추가</button>
								<button type="button" class="btn btn-sm btn-outline-danger d-none d-md-block" id="btnSendMember" disabled><i class="bi bi-chat-dots"></i> 선택문자</button>
								<button type="button" class="btn btn-sm btn-outline-success d-none d-md-block" id="btnMoveMember" disabled><i class="bi bi-arrow-right-square"></i> 선택이동</button>

								<div class="btn-group" role="group">
									<button type="button" class="btn btn-sm btn-outline-dark dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
										더보기
									</button>
									<ul class="dropdown-menu">
										<li><a class="dropdown-item d-block d-md-none" href="#" id="btnMoveMember">선택이동</a></li>
										<li><a class="dropdown-item d-block d-md-none" href="#" id="btnSendMember">선택문자</a></li>
										<li><a class="dropdown-item d-block" href="#" id="btnDeleteMember">선택삭제</a></li>
										<li><a class="dropdown-item" href="#" id="btnSelectedQrPrint">선택QR인쇄</a></li>
										<li><a class="dropdown-item" href="#" id="btnExcelDownload">엑셀다운로드</a></li>
										<li><a class="dropdown-item" href="#" id="btnExcelEdit">엑셀편집</a></li>
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
					<button class="nav-link active" id="profile-tab" data-bs-toggle="tab" data-bs-target="#profile-tab-pane" type="button" role="tab" aria-controls="profile-tab-pane" aria-selected="false">기본정보</button>
				</li>
				<li class="nav-item" role="presentation">
					<button class="nav-link" id="detail-tab" data-bs-toggle="tab" data-bs-target="#detail-tab-pane" type="button" role="tab" aria-controls="home-tab-pane" aria-selected="true">상세정보</button>
				</li>
				<li class="nav-item" role="presentation">
					<button class="nav-link" id="timeline-tab" data-bs-toggle="tab" data-bs-target="#timeline-tab-pane" type="button" role="tab" aria-controls="timeline-tab-pane" aria-selected="false">타임라인</button>
				</li>
				<li class="nav-item" role="presentation">
					<button class="nav-link" id="memo-tab" data-bs-toggle="tab" data-bs-target="#memo-tab-pane" type="button" role="tab" aria-controls="contact-tab-pane" aria-selected="false">회원메모</button>
				</li>
				<li class="nav-item" role="presentation">
					<button class="nav-link" id="mission-tab" data-bs-toggle="tab" data-bs-target="#mission-tab-pane" type="button" role="tab" aria-controls="mission-tab-pane" aria-selected="false">파송</button>
				</li>
				<li class="nav-item" role="presentation">
					<button class="nav-link" id="editing-tab" data-bs-toggle="tab" data-bs-target="#editing-tab-pane" type="button" role="tab" aria-controls="timeline-tab-pane" aria-selected="false">수정내역 <span class="badge badge-sm text-bg-warning">준비중</span></button>
				</li>

			</ul>

			<div class="tab-content" id="memberTabContent">
				<div class="tab-pane fade show active" id="profile-tab-pane" role="tabpanel" aria-labelledby="profile-tab" tabindex="0">
					<div class="row mt-3">
						<div class="d-flex justify-content-end text-end">
							<!--
							<div class="form-check form-switch me-3">
								<input type="checkbox" class="form-check-input" id="leader_yn" name="leader_yn">
								<label class="form-check-label" for="leader_yn">리더</label>
							</div>
							<div class="form-check form-switch">
								<input type="checkbox" class="form-check-input" id="new_yn" name="new_yn">
								<label class="form-check-label" for="new_yn">새가족</label>
							</div>-->
						</div>
					</div>

					<!-- 사진 업로드 영역 -->
					<div class="row">
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
									<button type="button" class="btn btn-xs btn-outline-primary" id="cropPhoto">
										<i class="bi bi-crop"></i> 크롭
									</button>
									<button type="button" class="btn btn-xs btn-outline-danger" id="removePhoto">
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

					<!-- 역할: 회원정보 수정 모달에 직위/직분, 직책 필드 추가 -->

					<div class="row">
						<!-- 개인정보 입력 필드들 -->
						<div class="col-6 col-sm-4 mb-3">
							<label for="member_name" class="form-label">이름 <span class="text-danger">*</span></label>
							<div class="input-group">
								<input type="text" class="form-control" id="member_name" name="member_name" required>
								<select class="form-select" aria-label="member sex" id="member_sex" name="member_sex" style=" max-width: 80px;">
									<option selected>성별</option>
									<option value="male">남</option>
									<option value="female">여</option>
								</select>
							</div>
						</div>

						<div class="col-6 col-sm-4 mb-3">
							<label for="member_nick" class="form-label">별명</label>
							<input type="text" class="form-control" id="member_nick" name="member_nick">
						</div>

						<!-- 새로 추가되는 직위/직분 필드 -->
						<div class="col-6 col-sm-4 mb-3">
							<label for="position_name" class="form-label">직위/직분</label>
							<select class="form-select" id="position_name" name="position_name">
								<option value="">직위/직분 선택</option>
							</select>
						</div>

						<!-- 새로 추가되는 직책 필드 -->
						<div class="col-6 col-sm-4 mb-3">
							<label for="duty_name" class="form-label">직책(그룹직책)</label>
							<select class="form-select" id="duty_name" name="duty_name">
								<option value="">직책 선택</option>
							</select>
						</div>

						<div class="col-6 col-sm-4 mb-3">
							<label for="member_phone" class="form-label">연락처</label>
							<input type="tel" class="form-control" id="member_phone" name="member_phone">
						</div>

						<div class="col-6 col-sm-4 mb-3">
							<label for="member_birth" class="form-label">생년월일</label>
							<input type="date" class="form-control" id="member_birth" name="member_birth">
						</div>

						<div class="col-6 col-sm-4 mb-3">
							<label for="member_address" class="form-label">주소</label>
							<input type="text" class="form-control" id="member_address" name="member_address">
						</div>

						<div class="col-6 col-sm-4 mb-3">
							<label for="member_address_detail" class="form-label">상세주소</label>
							<input type="text" class="form-control" id="member_address_detail" name="member_address_detail">
						</div>
						<div class="col-6 col-sm-4 mb-3">
							<label for="area_idx" class="form-label">소그룹</label>
							<select class="form-select" id="area_idx" name="area_idx">
								<option value="">소그룹 선택</option>
							</select>
						</div>
					</div>

						<!-- front 위치 상세필드 영역 (동적 생성) -->
					<div class="row row-lg pt-3 pb-1 mb-3" id="detail-front" style="display: none; background:#eee;"></div>

					<div class="row">
						<div class="col-12 mb-3">
							<label for="member_etc" class="form-label">특이사항</label>
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
				<div class="tab-pane fade" id="timeline-tab-pane" role="tabpanel" aria-labelledby="timeline-tab" tabindex="0">
					<div class="row mt-3">
						<div class="col-12">
							<!-- 새 타임라인 입력 영역 -->
							<div class="timeline-input-section pb-3 mb-3 border-bottom">
								<div class="row align-items-end">
									<div class="col-3">
										<select class="form-select form-select-sm" id="newTimelineType">
											<option value="">항목 선택</option>
										</select>
									</div>
									<div class="col-3">

										<input type="date" class="form-control form-control-sm" id="newTimelineDate">
									</div>
									<div class="col-4">

										<input type="text" class="form-control form-control-sm" id="newTimelineContent" placeholder="내용을 입력하세요">
									</div>
									<div class="col-2 d-flex align-items-end">
										<button type="button" class="btn btn-sm btn-outline-primary w-100" id="addTimelineBtn">
											<i class="bi bi-plus-lg"></i> 추가
										</button>
									</div>
								</div>
							</div>

							<!-- 타임라인 목록 영역 -->
							<div class="timeline-list-section">
								<div id="timelineList">
									<div class="text-center text-muted py-3" id="emptyTimelineMessage">
										타임라인을 불러오고 있습니다...
									</div>
								</div>
							</div>
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

				<div class="tab-pane fade" id="mission-tab-pane" role="tabpanel" aria-labelledby="mission-tab" tabindex="0">
					<div class="row mt-3">
						<div class="col-12">
							<!-- 상단 버튼 영역 -->
							<div class="mission-button-section pb-3 mb-3 border-bottom">
								<div class="d-flex justify-content-end flex-wrap gap-2">
									<div class="btn-group">
										<button type="button" class="btn btn-sm btn-outline-primary" id="sendEmailToMemberBtn">
											<i class="bi bi-envelope"></i> 회원에게 이메일전송
										</button>
										<button type="button" class="btn btn-sm btn-outline-info" id="sendEmailToChurchBtn">
											<i class="bi bi-envelope"></i> 파송교회 이메일전송
										</button>
										<button type="button" class="btn btn-sm btn-outline-success" id="autoMatchChurchBtn">
											<i class="bi bi-link-45deg"></i> 결연교회 자동매칭
										</button>
										<button type="button" class="btn btn-sm btn-outline-dark" id="addTransferOrgBtn">
											<i class="bi bi-plus-lg"></i> 파송교회 수동추가
										</button>
									</div>
								</div>
							</div>

							<!-- 파송 교회 목록 영역 -->
							<div class="transfer-org-list-section">
								<div id="transferOrgList">
									<div class="text-center text-muted py-3" id="emptyTransferOrgMessage">
										파송 교회 정보를 불러오고 있습니다...
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>


				<div class="tab-pane fade" id="editing-tab-pane" role="tabpanel" aria-labelledby="editing-tab" tabindex="0">
					<div class="row mt-3">
						<div class="col-12">
							수정내역
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

<!-- 선택QR인쇄 모달 -->
<div class="modal fade" id="selectedQrPrintModal" tabindex="-1" aria-labelledby="selectedQrPrintModalLabel" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="selectedQrPrintModalLabel">선택 QR 인쇄</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<div class="alert alert-info">
					<i class="bi bi-info-circle"></i>
					<strong>안내:</strong> 선택한 회원들의 QR 코드를 A4 라벨지(7x10, 총 70개)에 인쇄할 수 있습니다.
				</div>

				<div class="mb-3">
					<label for="selectedMemberCount" class="form-label">선택된 회원 수</label>
					<input type="text" class="form-control" id="selectedMemberCount" readonly>
				</div>

				<div class="mb-3">
					<label for="startPositionSelect" class="form-label">시작 위치</label>
					<select class="form-select" id="startPositionSelect">
						<?php for ($i = 1; $i <= 70; $i++): ?>
							<option value="<?php echo $i; ?>"><?php echo $i; ?>번째 라벨부터</option>
						<?php endfor; ?>
					</select>
					<div class="form-text">
						라벨지의 몇 번째 위치부터 인쇄할지 선택하세요. (1번: 왼쪽 상단)
					</div>
				</div>

				<div class="alert alert-warning">
					<i class="bi bi-exclamation-triangle"></i>
					<strong>주의사항:</strong>
					<ul class="mb-0 mt-2">
						<li>A4 라벨지 규격: 7열 x 10행 (총 70개)</li>
						<li>라벨 크기: 25mm x 30mm</li>
						<li>선택된 회원 수가 남은 라벨 수보다 많으면 다음 장에 인쇄됩니다</li>
					</ul>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
				<button type="button" class="btn btn-primary" id="executePrintSelectedQr">인쇄하기</button>
			</div>
		</div>
	</div>
</div>

<!-- 선택된 회원 정보 표시 영역 (선택사항) -->
<div class="row mt-3" id="selectedMemberInfo" style="display: none;">
	<div class="col-12">
		<div class="alert alert-info d-flex justify-content-between align-items-center">
            <span>
                <i class="bi bi-info-circle"></i>
                <span id="selectedMemberCount">0</span>명이 선택되었습니다.
                <small class="text-muted">(<span id="selectedMemberWithPhone">0</span>명이 문자 발송 가능)</small>
            </span>
			<button type="button" class="btn btn-sm btn-outline-secondary" onclick="clearAllSelection()">
				선택 해제
			</button>
		</div>
	</div>
</div>

<!-- 파송교회 추가/수정 모달 -->
<div class="modal fade" id="transferOrgModal" tabindex="-1" aria-labelledby="transferOrgModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="transferOrgModalLabel">파송교회 추가</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<form id="transferOrgForm">
					<input type="hidden" id="transfer_org_idx" name="transfer_org_idx">
					<input type="hidden" id="transfer_member_idx" name="transfer_member_idx">

					<div class="row">
						<div class="col-md-4 mb-3">
							<label for="transfer_region" class="form-label">지역</label>
							<input type="text" class="form-control" id="transfer_region" name="transfer_region" placeholder="예: 경기 광명" required>
						</div>
						<div class="col-md-4 mb-3">
							<label for="transfer_name" class="form-label">교회명</label>
							<input type="text" class="form-control" id="transfer_name" name="transfer_name" placeholder="예: 오병이어교회" required>
						</div>
						<div class="col-md-4 mb-3">
							<label for="pastor_name" class="form-label">담임목사명</label>
							<input type="text" class="form-control" id="pastor_name" name="pastor_name" placeholder="예: 권영구">
						</div>
					</div>

					<div class="row">
						<div class="col-md-4 mb-3">
							<label for="contact_person" class="form-label">담당자</label>
							<input type="text" class="form-control" id="contact_person" name="contact_person" placeholder="예: 홍길동">
						</div>
						<div class="col-md-4 mb-3">
							<label for="contact_phone" class="form-label">연락처</label>
							<input type="tel" class="form-control" id="contact_phone" name="contact_phone" placeholder="예: 010-2313-1234">
						</div>
						<div class="col-md-4 mb-3">
							<label for="contact_email" class="form-label">이메일</label>
							<input type="email" class="form-control" id="contact_email" name="contact_email" placeholder="예: jogiclub@gmail.com">
						</div>
					</div>

					<div class="row">
						<div class="col-12 mb-3">
							<label for="transfer_description" class="form-label">교회소개</label>
							<textarea class="form-control" id="transfer_description" name="transfer_description" rows="3" placeholder="교회에 대한 간단한 소개를 입력하세요"></textarea>
						</div>
					</div>

					<div class="row">
						<div class="col-12 mb-3">
							<label for="org_tags" class="form-label">태그</label>
							<select class="form-select" id="org_tags" name="org_tags" multiple style="width: 100%"></select>
							<div class="form-text">
								입력 후 스페이스바를 클릭하세요!
							</div>
						</div>
					</div>
				</form>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
				<button type="button" class="btn btn-primary" id="saveTransferOrgBtn">저장</button>
			</div>
		</div>
	</div>
</div>

<!-- 파송교회 삭제 확인 모달 -->
<div class="modal fade" id="deleteTransferOrgModal" tabindex="-1" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title">파송교회 삭제</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
			</div>
			<div class="modal-body">
				<p>이 파송교회 정보를 삭제하시겠습니까?</p>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
				<button type="button" class="btn btn-danger" id="confirmDeleteTransferOrgBtn">삭제</button>
			</div>
		</div>
	</div>
</div>


<?php $this->load->view('footer'); ?>


<!-- 엑셀 다운로드를 위한 추가 라이브러리 (JSZip 2.x 버전 사용) -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/2.5.0/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/FileSaver.js/2.0.5/FileSaver.min.js"></script>

<script src="/assets/js/custom/split.min.js"></script>
<script src="/assets/js/custom/jquery.fancytree-all-deps.min.js"></script>
<script src="/assets/js/custom/pqgrid.min.js?<?php echo WB_VERSION; ?>"></script>
<script src="https://cdn.jsdelivr.net/npm/croppie@2.6.5/croppie.min.js"></script>

<script>
	window.memberPageData = {
		baseUrl: '<?php echo base_url(); ?>'
	};

	/*출석관리 메뉴 active*/
	$('.menu-21').addClass('active');
</script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="/assets/js/custom/select2.sortable.min.js?<?php echo WB_VERSION; ?>"></script>
<script src="/assets/js/member.js?<?php echo WB_VERSION; ?>"></script>
