<?php
$this->load->view('mng/header');
?>

<div class="container-fluid pt-2 pb-2">
	<nav class="mb-1" aria-label="breadcrumb">
		<ol class="breadcrumb mb-0">
			<li class="breadcrumb-item"><a href="/mng">관리자홈</a></li>
			<li class="breadcrumb-item"><a href="#!">OVERVIEW</a></li>
			<li class="breadcrumb-item active">대시보드</li>
		</ol>
	</nav>
	<div class="col-12 my-1 d-flex align-items-center justify-content-between mb-3">
		<h3 class="page-title col-12 my-1">대시보드</h3>
	</div>

	<div class="row">
		<div class="col-12">
			<div class="card mb-3">
				<div class="card-header d-flex align-items-center justify-content-between">
					<h5 class="card-title mb-0 d-flex">
						<i class="bi bi-person-video3 me-2"></i> 전체 회원현황
					</h5>
					<div class="d-flex align-items-center gap-2">
						<button type="button" class="btn btn-sm btn-outline-secondary" id="memberPrevMonth">
							<i class="bi bi-chevron-left"></i>
						</button>
						<span id="memberCurrentMonth" class="fw-bold" style="min-width: 100px; text-align: center;"></span>
						<button type="button" class="btn btn-sm btn-outline-secondary" id="memberNextMonth">
							<i class="bi bi-chevron-right"></i>
						</button>
					</div>
				</div>
				<div class="card-body">
					<canvas id="memberChart"></canvas>
				</div>
			</div>

			<div class="card mb-3">
				<div class="card-header d-flex align-items-center justify-content-between">
					<h5 class="card-title mb-0 d-flex">
						<i class="bi bi-calendar-check me-2"></i> 전체 출석현황
					</h5>
					<div class="d-flex align-items-center gap-3">
						<a href="#" id="attendanceSettingBtn">
							<small><i class="bi bi-gear"></i> 설정</small>
						</a>
						<div class="d-flex align-items-center gap-2">
							<button type="button" class="btn btn-sm btn-outline-secondary" id="attendancePrevMonth">
								<i class="bi bi-chevron-left"></i>
							</button>
							<span id="attendanceCurrentMonth" class="fw-bold" style="min-width: 100px; text-align: center;"></span>
							<button type="button" class="btn btn-sm btn-outline-secondary" id="attendanceNextMonth">
								<i class="bi bi-chevron-right"></i>
							</button>
						</div>
					</div>
				</div>
				<div class="card-body">
					<canvas id="attendanceChart"></canvas>
				</div>
			</div>

			<div class="card mb-3">
				<div class="card-header d-flex align-items-center justify-content-between">
					<h5 class="card-title mb-0 d-flex">
						<i class="bi bi-watch me-2"></i> 전체 타임라인현황
					</h5>
					<div class="d-flex align-items-center gap-3">
						<a href="#" id="timelineSettingBtn">
							<small><i class="bi bi-gear"></i> 설정</small>
						</a>
						<div class="d-flex align-items-center gap-2">
							<button type="button" class="btn btn-sm btn-outline-secondary" id="timelinePrevMonth">
								<i class="bi bi-chevron-left"></i>
							</button>
							<span id="timelineCurrentMonth" class="fw-bold" style="min-width: 100px; text-align: center;"></span>
							<button type="button" class="btn btn-sm btn-outline-secondary" id="timelineNextMonth">
								<i class="bi bi-chevron-right"></i>
							</button>
						</div>
					</div>
				</div>
				<div class="card-body">
					<canvas id="timelineChart"></canvas>
				</div>
			</div>

			<div class="card mb-3">
				<div class="card-header d-flex align-items-center justify-content-between">
					<h5 class="card-title mb-0 d-flex">
						<i class="bi bi-journals me-2"></i> 전체 메모현황
					</h5>
					<div class="d-flex align-items-center gap-3">
						<a href="#" id="memoSettingBtn">
							<small><i class="bi bi-gear"></i> 설정</small>
						</a>
						<div class="d-flex align-items-center gap-2">
							<button type="button" class="btn btn-sm btn-outline-secondary" id="memoPrevMonth">
								<i class="bi bi-chevron-left"></i>
							</button>
							<span id="memoCurrentMonth" class="fw-bold" style="min-width: 100px; text-align: center;"></span>
							<button type="button" class="btn btn-sm btn-outline-secondary" id="memoNextMonth">
								<i class="bi bi-chevron-right"></i>
							</button>
						</div>
					</div>
				</div>
				<div class="card-body">
					<canvas id="memoChart"></canvas>
				</div>
			</div>
		</div>
	</div>
</div>
<!-- 출석현황 설정 모달 -->
<div class="modal fade" id="attendanceSettingModal" tabindex="-1" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title">출석현황 표시 설정</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<div id="attendanceTypeCheckboxes">
					<p class="text-muted">데이터를 불러오는 중...</p>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
				<button type="button" class="btn btn-primary" id="saveAttendanceSettingBtn">저장</button>
			</div>
		</div>
	</div>
</div>

<!-- 타임라인현황 설정 모달 -->
<div class="modal fade" id="timelineSettingModal" tabindex="-1" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title">타임라인현황 표시 설정</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<div id="timelineTypeCheckboxes">
					<p class="text-muted">데이터를 불러오는 중...</p>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
				<button type="button" class="btn btn-primary" id="saveTimelineSettingBtn">저장</button>
			</div>
		</div>
	</div>
</div>

<!-- 메모현황 설정 모달 -->
<div class="modal fade" id="memoSettingModal" tabindex="-1" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title">메모현황 표시 설정</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<div id="memoTypeCheckboxes">
					<p class="text-muted">데이터를 불러오는 중...</p>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
				<button type="button" class="btn btn-primary" id="saveMemoSettingBtn">저장</button>
			</div>
		</div>
	</div>
</div>

<?php
$this->load->view('mng/footer');
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="/assets/js/mng_dashboard.js?<?php echo WB_VERSION; ?>"></script>
