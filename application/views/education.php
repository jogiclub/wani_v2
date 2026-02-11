<?php
/**
 * 파일 위치: application/views/education.php
 * 역할: 양육관리 화면 레이아웃 (Offcanvas 적용)
 */
$this->load->view('header');
?>

<!-- ParamQuery CSS -->
<link rel="stylesheet" href="/assets/css/custom/pqgrid.min.css">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

<!-- Fancytree CSS -->
<link rel="stylesheet" href="/assets/css/custom/ui.fancytree.min.css?<?php echo WB_VERSION; ?>">

<!-- Flatpickr CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

<div class="container-fluid pt-2 pb-2">
	<nav class="mb-1" aria-label="breadcrumb">
		<ol class="breadcrumb mb-0">
			<li class="breadcrumb-item"><a href="#!">홈</a></li>
			<li class="breadcrumb-item"><a href="#!">EDUCATION</a></li>
			<li class="breadcrumb-item active">양육</li>
		</ol>
	</nav>

	<div class="col-12 my-1 d-flex align-items-center justify-content-between">
		<h3 class="page-title mb-0">양육</h3>
	</div>


	<!-- Split.js를 위한 단일 컨테이너 -->
	<div class="split-container">
		<!-- 왼쪽: 카테고리 트리 -->
		<div class="split-pane" id="left-pane">
			<div class="card h-100 d-flex flex-column">
				<div class="card-body p-0 position-relative flex-grow-1" style="overflow-y: auto;">
					<!-- 트리 스피너 -->
					<div id="treeSpinner" class="d-flex justify-content-center align-items-center position-absolute w-100 h-100" style="z-index: 1000; background: rgba(255, 255, 255, 0.8);">
						<div class="text-center">
							<div class="spinner-border text-primary mb-2" role="status">
								<span class="visually-hidden">로딩 중...</span>
							</div>
							<div class="small text-muted">카테고리 로딩 중...</div>
						</div>
					</div>
					<div id="categoryTree" class="tree-container"></div>
				</div>
				<div class="card-footer p-2 bg-white">
					<div class="btn-group-vertical w-100" role="group" aria-label="Vertical button group" id="categoryManagementButtons">
						<button type="button" class="btn btn-sm btn-outline-secondary" id="btnAddCategory">
							<i class="bi bi-folder-plus"></i> 카테고리 생성
						</button>
						<button type="button" class="btn btn-sm btn-outline-secondary" id="btnRenameCategory" disabled>
							<i class="bi bi-pencil-square"></i> 카테고리명 변경
						</button>
						<button type="button" class="btn btn-sm btn-outline-secondary" id="btnDeleteCategory" disabled>
							<i class="bi bi-folder-minus"></i> 카테고리 삭제
						</button>
						<button type="button" class="btn btn-sm btn-outline-secondary" id="btnMoveCategory" disabled>
							<i class="bi bi-arrow-right-square"></i> 카테고리 이동
						</button>
					</div>
				</div>
			</div>
		</div>

		<!-- 오른쪽: 양육 목록 -->
		<div class="split-pane" id="right-pane">
			<div class="card">
				<div class="card-header">
					<div class="row flex-column flex-lg-row">
						<div class="col-12 col-lg-3 d-flex align-items-center">
							<h5 class="mb-0 text-truncate" id="selectedCategoryName">
								<i class="bi bi-list-ul"></i> 카테고리를 선택해주세요
							</h5>
							<small class="ms-3 text-muted">총 <span id="totalEduCount">0</span>개</small>
						</div>


						<div class="col-12 col-lg-4 d-flex justify-content-start justify-content-lg-end mt-2 mt-lg-0 gap-2">

							<div class="input-group input-group-sm" style="max-width: 300px;">
								<input type="text" class="form-control" placeholder="양육명 검색" id="searchKeyword">
								<button class="btn btn-sm btn-outline-secondary" type="button" id="btnSearch">
									<i class="bi bi-search"></i> 검색
								</button>
							</div>
						</div>

						<div class="col-12 col-lg-5 d-flex justify-content-start justify-content-lg-end mt-2 mt-lg-0 gap-2">
							<div class="btn-group">
								<button type="button" class="btn btn-sm btn-primary" id="btnAddEdu">
									<i class="bi bi-plus-lg"></i> 양육 등록
								</button>
								<button type="button" class="btn btn-sm btn-outline-danger" id="btnDeleteSelected">
									<i class="bi bi-trash"></i> 선택 삭제
								</button>
								<button type="button" class="btn btn-sm btn-outline-success" id="btnManageApplicant">
									<i class="bi bi-people"></i> 신청자 관리
								</button>

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
							<div class="small text-muted">양육 목록 로딩 중...</div>
						</div>
					</div>
					<!-- ParamQuery Grid가 여기에 렌더링됩니다 -->
					<div id="eduGrid"></div>
				</div>
			</div>
		</div>
	</div>
</div>

<!-- 양육 등록/수정 Offcanvas -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="eduOffcanvas" aria-labelledby="eduOffcanvasLabel" style="width: 600px;">
	<div class="offcanvas-header">
		<h5 class="offcanvas-title" id="eduOffcanvasTitle">양육 등록</h5>
		<button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
	</div>
	<div class="offcanvas-body">
		<form id="eduForm">
			<input type="hidden" id="eduIdx" name="edu_idx">
			<input type="hidden" id="eduOrgId" name="org_id">
			<input type="hidden" id="removePosterFlag" value="0">


			<div class="row mb-3">
				<div class="col-6 ">
					<label class="form-label">양육카테고리 <span class="text-danger">*</span></label>
					<select class="form-select" id="eduCategoryCode" name="category_code" required>
						<option value="">카테고리 선택</option>
					</select>
				</div>

				<div class="col-6">
					<label class="form-label">양육명 <span class="text-danger">*</span></label>
					<input type="text" class="form-control" id="eduName" name="edu_name" placeholder="예: 하나님께 받는 복" required>
				</div>
			</div>



			<div class="row mb-3">
				<div class="col-6">
					<label class="form-label">양육 시작일</label>
					<input type="text" class="form-control" id="eduStartDate" name="edu_start_date" placeholder="시작일 선택">
				</div>
				<div class="col-6">
					<label class="form-label">양육 종료일</label>
					<input type="text" class="form-control" id="eduEndDate" name="edu_end_date" placeholder="종료일 선택">
				</div>
			</div>

			<div class="row mb-3">

				<div class="col-6">
					<label class="form-label">요일</label>
					<div class="dropdown w-100">
						<button class="btn btn-outline-secondary dropdown-toggle w-100 text-start" type="button" id="eduDaysDropdown" data-bs-toggle="dropdown" aria-expanded="false" data-bs-auto-close="outside">
							<span id="eduDaysText">요일 선택</span>
						</button>
						<ul class="dropdown-menu w-100" aria-labelledby="eduDaysDropdown" id="eduDaysMenu">
							<li>
								<div class="dropdown-item">
									<input type="checkbox" class="form-check-input me-2 edu-day-checkbox" id="day_mon" value="월요일">
									<label class="form-check-label" for="day_mon">월요일</label>
								</div>
							</li>
							<li>
								<div class="dropdown-item">
									<input type="checkbox" class="form-check-input me-2 edu-day-checkbox" id="day_tue" value="화요일">
									<label class="form-check-label" for="day_tue">화요일</label>
								</div>
							</li>
							<li>
								<div class="dropdown-item">
									<input type="checkbox" class="form-check-input me-2 edu-day-checkbox" id="day_wed" value="수요일">
									<label class="form-check-label" for="day_wed">수요일</label>
								</div>
							</li>
							<li>
								<div class="dropdown-item">
									<input type="checkbox" class="form-check-input me-2 edu-day-checkbox" id="day_thu" value="목요일">
									<label class="form-check-label" for="day_thu">목요일</label>
								</div>
							</li>
							<li>
								<div class="dropdown-item">
									<input type="checkbox" class="form-check-input me-2 edu-day-checkbox" id="day_fri" value="금요일">
									<label class="form-check-label" for="day_fri">금요일</label>
								</div>
							</li>
							<li>
								<div class="dropdown-item">
									<input type="checkbox" class="form-check-input me-2 edu-day-checkbox" id="day_sat" value="토요일">
									<label class="form-check-label" for="day_sat">토요일</label>
								</div>
							</li>
							<li>
								<div class="dropdown-item">
									<input type="checkbox" class="form-check-input me-2 edu-day-checkbox" id="day_sun" value="일요일">
									<label class="form-check-label" for="day_sun">일요일</label>
								</div>
							</li>
						</ul>
					</div>
					<input type="hidden" id="eduDays" name="edu_days">
				</div>
				<div class="col-6">
					<label class="form-label">시간대</label>
					<div class="dropdown w-100">
						<button class="btn btn-outline-secondary dropdown-toggle w-100 text-start" type="button" id="eduTimesDropdown" data-bs-toggle="dropdown" aria-expanded="false" data-bs-auto-close="outside">
							<span id="eduTimesText">시간대 선택</span>
						</button>
						<ul class="dropdown-menu w-100" aria-labelledby="eduTimesDropdown" id="eduTimesMenu" style="max-height: 300px; overflow-y: auto;">
							<li>
								<div class="dropdown-item">
									<input type="checkbox" class="form-check-input me-2 edu-time-checkbox" id="time_06" value="06시">
									<label class="form-check-label" for="time_06">06시</label>
								</div>
							</li>
							<li>
								<div class="dropdown-item">
									<input type="checkbox" class="form-check-input me-2 edu-time-checkbox" id="time_07" value="07시">
									<label class="form-check-label" for="time_07">07시</label>
								</div>
							</li>
							<li>
								<div class="dropdown-item">
									<input type="checkbox" class="form-check-input me-2 edu-time-checkbox" id="time_08" value="08시">
									<label class="form-check-label" for="time_08">08시</label>
								</div>
							</li>
							<li>
								<div class="dropdown-item">
									<input type="checkbox" class="form-check-input me-2 edu-time-checkbox" id="time_09" value="09시">
									<label class="form-check-label" for="time_09">09시</label>
								</div>
							</li>
							<li>
								<div class="dropdown-item">
									<input type="checkbox" class="form-check-input me-2 edu-time-checkbox" id="time_10" value="10시">
									<label class="form-check-label" for="time_10">10시</label>
								</div>
							</li>
							<li>
								<div class="dropdown-item">
									<input type="checkbox" class="form-check-input me-2 edu-time-checkbox" id="time_11" value="11시">
									<label class="form-check-label" for="time_11">11시</label>
								</div>
							</li>
							<li>
								<div class="dropdown-item">
									<input type="checkbox" class="form-check-input me-2 edu-time-checkbox" id="time_12" value="12시">
									<label class="form-check-label" for="time_12">12시</label>
								</div>
							</li>
							<li>
								<div class="dropdown-item">
									<input type="checkbox" class="form-check-input me-2 edu-time-checkbox" id="time_13" value="13시">
									<label class="form-check-label" for="time_13">13시</label>
								</div>
							</li>
							<li>
								<div class="dropdown-item">
									<input type="checkbox" class="form-check-input me-2 edu-time-checkbox" id="time_14" value="14시">
									<label class="form-check-label" for="time_14">14시</label>
								</div>
							</li>
							<li>
								<div class="dropdown-item">
									<input type="checkbox" class="form-check-input me-2 edu-time-checkbox" id="time_15" value="15시">
									<label class="form-check-label" for="time_15">15시</label>
								</div>
							</li>
							<li>
								<div class="dropdown-item">
									<input type="checkbox" class="form-check-input me-2 edu-time-checkbox" id="time_16" value="16시">
									<label class="form-check-label" for="time_16">16시</label>
								</div>
							</li>
							<li>
								<div class="dropdown-item">
									<input type="checkbox" class="form-check-input me-2 edu-time-checkbox" id="time_17" value="17시">
									<label class="form-check-label" for="time_17">17시</label>
								</div>
							</li>
							<li>
								<div class="dropdown-item">
									<input type="checkbox" class="form-check-input me-2 edu-time-checkbox" id="time_18" value="18시">
									<label class="form-check-label" for="time_18">18시</label>
								</div>
							</li>
							<li>
								<div class="dropdown-item">
									<input type="checkbox" class="form-check-input me-2 edu-time-checkbox" id="time_19" value="19시">
									<label class="form-check-label" for="time_19">19시</label>
								</div>
							</li>
							<li>
								<div class="dropdown-item">
									<input type="checkbox" class="form-check-input me-2 edu-time-checkbox" id="time_20" value="20시">
									<label class="form-check-label" for="time_20">20시</label>
								</div>
							</li>
							<li>
								<div class="dropdown-item">
									<input type="checkbox" class="form-check-input me-2 edu-time-checkbox" id="time_21" value="21시">
									<label class="form-check-label" for="time_21">21시</label>
								</div>
							</li>
							<li>
								<div class="dropdown-item">
									<input type="checkbox" class="form-check-input me-2 edu-time-checkbox" id="time_22" value="22시">
									<label class="form-check-label" for="time_22">22시</label>
								</div>
							</li>
						</ul>
					</div>
					<input type="hidden" id="eduTimes" name="edu_times">
				</div>
			</div>

			<div class="row mb-3">
				<div class="col-6">
					<label class="form-label">정원</label>
					<input type="number" class="form-control" id="eduCapacity" name="edu_capacity" placeholder="정원 입력 (0: 무제한)" min="0">
				</div>
				<div class="col-6">
					<label class="form-label">양육지역</label>
					<input type="text" class="form-control" id="eduLocation" name="edu_location" placeholder="예: 경기 광명 지역">
				</div>
			</div>

			<div class="row mb-3">

				<div class="col-6">
					<label class="form-label">수강료</label>
					<div class="input-group">
						<input type="text" class="form-control" id="eduFee" name="edu_fee" placeholder="0" value="0">
						<span class="input-group-text">원</span>
					</div>
					<div class="form-text">수강료를 입력하세요 (무료인 경우 0)</div>
				</div>

				<div class="col-6">
					<label class="form-label">외부 공개</label>
					<select class="form-select" id="eduPublicYn" name="public_yn">
						<option value="N">비공개</option>
						<option value="Y">공개</option>
					</select>
				</div>


			</div>

<div class="row mb-3">
	<div class="mb-3">
		<label class="form-label">계좌정보</label>
		<div class="input-group mb-2">
			<input type="text" class="form-control" id="eduBankName" placeholder="은행명">
			<input type="text" class="form-control" id="eduAccountNumber" placeholder="계좌번호">
		</div>
		<input type="hidden" id="eduBankAccount" name="bank_account">
	</div>
</div>


			<div class="row mb-3">
				<div class="col-6">
					<label class="form-label">인도자</label>
					<input type="text" class="form-control" id="eduLeader" name="edu_leader" placeholder="예: 손용일 집사">
				</div>
				<div class="col-6">
					<label class="form-label">인도자 연락처</label>
					<input type="text" class="form-control" id="eduLeaderPhone" name="edu_leader_phone" placeholder="010-1234-5678">
				</div>
			</div>
			<div class="row mb-3">
				<div class="col-6">
					<label class="form-label">인도자 연령대</label>
					<select class="form-select" id="eduLeaderAge" name="edu_leader_age">
						<option value="">선택</option>
						<option value="10s">10대</option>
						<option value="20s">20대</option>
						<option value="30s">30대</option>
						<option value="40s">40대</option>
						<option value="50s">50대</option>
						<option value="60s">60대</option>
					</select>
				</div>
				<div class="col-6">
					<label class="form-label">인도자 성별</label>
					<select class="form-select" id="eduLeaderGender" name="edu_leader_gender">
						<option value="">선택</option>
						<option value="male">남</option>
						<option value="female">여</option>
					</select>
				</div>
			</div>

			<!-- 새로 추가되는 필드들 -->
			<div class="row mb-3">
				<div class="col-6">
					<label class="form-label">ZOOM URL</label>
					<input type="text" class="form-control" id="eduZoomUrl" name="zoom_url" placeholder="https://zoom.us/j/...">
					<div class="form-text">온라인 양육 시 ZOOM 링크를 입력하세요</div>
				</div>

				<div class="col-6">
					<label class="form-label">유튜브 URL</label>
					<input type="url" class="form-control" id="eduYoutubeUrl" name="youtube_url" placeholder="https://www.youtube.com/watch?v=...">
					<div class="form-text">유튜브 동영상 링크를 입력하세요</div>
				</div>
			</div>




			<div class="mb-3">
				<label class="form-label">포스터 이미지</label>
				<div class="border rounded p-3 text-center d-flex align-items-center justify-content-center bg-light" style="min-height: 200px;">
					<div id="posterPlaceholder">
						<i class="bi bi-image" style="font-size: 3rem; color: #dee2e6;"></i>
						<p class="text-muted mb-0">포스터 이미지를 선택하세요</p>
						<p class="text-muted small">권장 크기: 가로 1200px (자동 리사이징)</p>
					</div>
					<img id="posterPreview" src="" class="img-fluid" style="max-height: 300px; display: none;" alt="포스터 미리보기">
				</div>
				<input type="file" class="form-control mt-2" id="eduPosterImg" name="poster_img" accept="image/*">
				<button type="button" class="btn btn-sm btn-outline-danger mt-2" id="btnRemovePoster" style="display: none;">
					<i class="bi bi-trash"></i> 이미지 제거
				</button>
				<div class="form-text">JPG, PNG 형식 지원 (최대 10MB)</div>
			</div>

			<div class="mb-3">
				<label class="form-label">양육 설명</label>
				<textarea class="form-control" id="eduDesc" name="edu_desc" rows="4" placeholder="양육에 대한 상세 설명을 입력하세요"></textarea>
			</div>
		</form>
	</div>
	<div class="offcanvas-footer">
		<div class="d-flex gap-2 p-3 border-top bg-light">
			<button type="button" class="btn btn-secondary flex-fill" data-bs-dismiss="offcanvas">취소</button>
			<button type="button" class="btn btn-primary flex-fill" id="btnSaveEdu">저장</button>
		</div>
	</div>
</div>
<!-- 카테고리 관리 Modal -->
<div class="modal fade" id="categoryModal" tabindex="-1" aria-hidden="true">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title">카테고리 관리</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<div class="alert alert-info">
					<i class="bi bi-info-circle"></i> 카테고리는 JSON 형태로 관리됩니다. 아래 예시를 참고하여 수정하세요.
				</div>
				<textarea class="form-control font-monospace" id="categoryJson" rows="15" placeholder='{"categories": []}'></textarea>
				<div class="mt-3">
					<strong>예시:</strong>
					<pre class="bg-light p-2 rounded"><code>{
  "categories": [
    {
      "code": "EDU001",
      "name": "성경공부",
      "order": 1,
      "children": [
        {
          "code": "EDU001-001",
          "name": "신약성경",
          "order": 1
        }
      ]
    }
  ]
}</code></pre>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
				<button type="button" class="btn btn-primary" id="btnSaveCategory">저장</button>
			</div>
		</div>
	</div>
</div>


<!-- 신청자 관리 Offcanvas -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="applicantOffcanvas" aria-labelledby="applicantOffcanvasLabel" style="width: 600px;">
	<div class="offcanvas-header">
		<h5 class="offcanvas-title" id="applicantOffcanvasTitle">신청자 관리</h5>
		<button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
	</div>
	<div class="offcanvas-body">
		<div class="d-flex justify-content-between align-items-center pb-2">
			<small class="text-muted">총 <span id="applicantTotalCount">0</span>명</small>
			<div class="btn-group">

				<button type="button" class="btn btn-sm btn-primary" id="btnAddApplicant">
					<i class="bi bi-plus-lg"></i> 신청자 추가
				</button>
				<button type="button" class="btn btn-sm btn-outline-danger" id="btnDeleteSelectedApplicant">
					<i class="bi bi-trash"></i> 선택 삭제
				</button>

				<button type="button" class="btn btn-sm btn-outline-secondary" id="btnChangeStatusBulk">
					<i class="bi bi-arrow-repeat"></i> 선택 상태변경
				</button>

				<button type="button" class="btn btn-sm btn-outline-info" id="btnExternalUrl">
					<i class="bi bi-link-45deg"></i> 외부URL
				</button>
			</div>
		</div>
		<div class="flex-grow-1 position-relative">
			<div id="applicantGridSpinner" class="d-none justify-content-center align-items-center position-absolute w-100 h-100" style="z-index: 1000; background: rgba(255, 255, 255, 0.8);">
				<div class="text-center">
					<div class="spinner-border text-primary mb-2" role="status">
						<span class="visually-hidden">로딩 중...</span>
					</div>
					<div class="small text-muted">신청자 목록 로딩 중...</div>
				</div>
			</div>
			<div id="applicantGrid" style="height: 100%;"></div>
		</div>
	</div>
</div>

<!-- 신청자 추가 모달 -->
<div class="modal fade" id="addApplicantModal" tabindex="-1" aria-labelledby="addApplicantModalLabel" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="addApplicantModalTitle">신청자 추가</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<div class="mb-3">
					<label class="form-label">신청자</label>
					<select class="form-select" id="applicantMemberSelect" multiple="multiple" style="width: 100%;"></select>
					<small class="text-muted">회원 목록에서 선택하거나 직접 입력할 수 있습니다.</small>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
				<button type="button" class="btn btn-primary" id="btnSaveApplicant">저장</button>
			</div>
		</div>
	</div>
</div>

<!-- 신청자 수정 모달 -->
<div class="modal fade" id="editApplicantModal" tabindex="-1" aria-labelledby="editApplicantModalLabel" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="editApplicantModalTitle">신청자 수정</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<input type="hidden" id="editApplicantIdx">
				<div class="mb-3">
					<label class="form-label">신청자</label>
					<input type="text" class="form-control" id="editApplicantName">
				</div>
				<div class="mb-3">
					<label class="form-label">연락처</label>
					<input type="text" class="form-control" id="editApplicantPhone">
				</div>
				<div class="mb-3">
					<label class="form-label">상태</label>
					<select class="form-select" id="editApplicantStatus">
						<option value="신청">신청</option>
						<option value="신청(외부)">신청(외부)</option>
						<option value="양육중">양육중</option>
						<option value="수료">수료</option>
					</select>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
				<button type="button" class="btn btn-primary" id="btnUpdateApplicant">저장</button>
			</div>
		</div>
	</div>
</div>

<!-- 상태 일괄변경 모달 -->
<div class="modal fade" id="bulkStatusModal" tabindex="-1" aria-labelledby="bulkStatusModalLabel" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="bulkStatusModalTitle">상태 일괄변경</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<div class="mb-3">
					<label class="form-label">변경할 상태</label>
					<select class="form-select" id="bulkStatusSelect">
						<option value="신청">신청</option>
						<option value="신청(외부)">신청(외부)</option>
						<option value="양육중">양육중</option>
						<option value="수료">수료</option>
					</select>
				</div>
				<p class="text-muted small">전체 신청자의 상태가 선택한 상태로 변경됩니다.</p>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
				<button type="button" class="btn btn-primary" id="btnApplyBulkStatus">적용</button>
			</div>
		</div>
	</div>
</div>

<!-- 삭제 확인 모달 -->
<div class="modal fade" id="deleteApplicantModal" tabindex="-1" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title">신청자 삭제</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<p>선택한 신청자를 삭제하시겠습니까?</p>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
				<button type="button" class="btn btn-danger" id="btnConfirmDeleteApplicant">삭제</button>
			</div>
		</div>
	</div>
</div>
<!-- 외부URL로 양육신청 모달 -->
<div class="modal fade" id="externalUrlModal" tabindex="-1" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="externalUrlModalLabel">
					<i class="bi bi-link-45deg"></i> 외부URL로 양육신청
				</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<div class="alert alert-info mb-3">
					<small>
						아래의 URL을 외부 신청자에게 전달하여 양육 신청을 받을 수 있습니다.
					</small>
				</div>

				<div class="border rounded p-3 bg-light">
					<p class="mb-2"><strong>양육 신청 URL</strong></p>
					<div class="input-group mb-2">
						<input type="text" class="form-control" id="externalUrlInput" readonly>
						<button class="btn btn-outline-secondary" type="button" id="btnRefreshExternalUrl">
							<i class="bi bi-arrow-clockwise"></i> 갱신
						</button>
						<button class="btn btn-outline-secondary" type="button" id="btnCopyExternalUrl">
							<i class="bi bi-clipboard"></i> 복사
						</button>
					</div>
				</div>

				<div class="mt-3 text-muted small">
					<i class="bi bi-info-circle"></i>
					이 링크는 72시간 동안 유효합니다.
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">닫기</button>
			</div>
		</div>
	</div>
</div>

<!-- 카테고리명 변경 모달 -->
<div class="modal fade" id="renameCategoryModal" tabindex="-1" aria-labelledby="renameCategoryModalLabel" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="renameCategoryModalLabel">카테고리명 변경</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<div class="mb-3">
					<label for="newCategoryName" class="form-label">카테고리명</label>
					<input type="text" class="form-control" id="newCategoryName" name="newCategoryName" maxlength="50" required>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
				<button type="button" class="btn btn-warning" id="confirmRenameCategoryBtn">저장</button>
			</div>
		</div>
	</div>
</div>

<!-- 카테고리 삭제 확인 모달 -->
<div class="modal fade" id="deleteCategoryModal" tabindex="-1" aria-labelledby="deleteCategoryModalLabel" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="deleteCategoryModalLabel">카테고리 삭제 확인</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<p id="deleteCategoryMessage"></p>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
				<button type="button" class="btn btn-danger" id="confirmDeleteCategoryBtn">삭제</button>
			</div>
		</div>
	</div>
</div>

<!-- 카테고리 이동 모달 -->
<div class="modal fade" id="moveCategoryModal" tabindex="-1" aria-labelledby="moveCategoryModalLabel" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="moveCategoryModalLabel">카테고리 이동</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<p id="moveCategoryMessage"></p>
				<div class="mb-3">
					<label for="moveToCategoryCode" class="form-label">이동할 상위 카테고리 선택</label>
					<select class="form-select" id="moveToCategoryCode" name="moveToCategoryCode">
						<option value="">최상위로 이동</option>
					</select>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
				<button type="button" class="btn btn-primary" id="confirmMoveCategoryBtn">이동</button>
			</div>
		</div>
	</div>
</div>

<?php $this->load->view('footer'); ?>

<!-- Split.js -->
<script src="/assets/js/custom/split.min.js?<?php echo WB_VERSION; ?>"></script>

<!-- Fancytree -->
<script src="/assets/js/custom/jquery.fancytree-all-deps.min.js?<?php echo WB_VERSION; ?>"></script>

<!-- ParamQuery -->
<script src="/assets/js/custom/pqgrid.min.js?<?php echo WB_VERSION; ?>"></script>

<!-- Flatpickr -->
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/ko.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="/assets/js/custom/select2.sortable.min.js?<?php echo WB_VERSION; ?>"></script>
<script>
	window.educationPageData = {
		baseUrl: '<?php echo base_url(); ?>',
		currentOrgId: <?php echo isset($current_org['org_id']) ? $current_org['org_id'] : 0; ?>
	};
</script>

<script src="/assets/js/education.js?<?php echo WB_VERSION; ?>"></script>
