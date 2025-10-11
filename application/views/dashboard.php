
<?php $this->load->view('header'); ?>



<div class="container pt-2 pb-2">

	<nav class="mb-1" aria-label="breadcrumb">
		<ol class="breadcrumb mb-0">
			<li class="breadcrumb-item"><a href="#!">홈</a></li>
			<li class="breadcrumb-item"><a href="#!">OVERVIEW</a></li>
			<li class="breadcrumb-item active">대시보드</li>
		</ol>
	</nav>
	<div class="col-12 my-1 d-flex align-items-center justify-content-between mb-3">
		<h3 class="page-title col-12 mb-0">대시보드</h3>
	</div>

	<div class="row">
		<div class="col-lg-8">
			<div class="card mb-3">
				<div class="card-header d-flex align-items-center justify-content-between align-items-center">
					<h5 class="card-title mb-0 d-flex ">
						<i class="bi bi-person-video3 me-2"></i> 회원현황
					</h5>
				</div>
				<div class="card-body">
					<canvas id="memberChart"></canvas>
				</div>
			</div>
			<div class="card mb-3">
				<div class="card-header d-flex align-items-center justify-content-between align-items-center">
					<h5 class="card-title mb-0 d-flex ">
						<i class="bi bi-calendar-check me-2"></i> 출석현황
					</h5>
					<a href="#" id="attendanceSettingBtn">
						<small><i class="bi bi-gear"></i> 설정</small>
					</a>
				</div>
				<div class="card-body">
					<canvas id="attendanceChart"></canvas>
				</div>
			</div>
			<div class="card mb-3">
				<div class="card-header d-flex align-items-center justify-content-between">
					<h5 class="card-title mb-0 d-flex">
						<i class="bi bi-watch me-2"></i> 타임라인현황
					</h5>
					<a href="#" id="timelineSettingBtn">
						<small><i class="bi bi-gear"></i> 설정</small>
					</a>
				</div>
				<div class="card-body">
					<canvas id="timelineChart"></canvas>
				</div>
			</div>
			<div class="card mb-3">
				<div class="card-header d-flex align-items-center justify-content-between">
					<h5 class="card-title mb-0 d-flex">
						<i class="bi bi-journals me-2"></i> 메모현황
					</h5>
					<a href="#" id="memoSettingBtn">
						<small><i class="bi bi-gear"></i> 설정</small>
					</a>
				</div>
				<div class="card-body">
					<canvas id="memoChart"></canvas>
				</div>
			</div>

		</div>
		<div class="col-lg-4">
			<div class="card mb-3">
				<div class="card-header d-flex align-items-center justify-content-between align-items-center">
					<h5 class="card-title mb-0 d-flex ">
						<i class="bi bi-send-check me-2"></i> 공지사항
					</h5>
					<a href="#">
						<small><i class="bi bi-plus-lg"></i> 더보기</small>
					</a>
				</div>
				<div class="card-body">


						<div class="list-group">
							<a href="#" class="list-group-item list-group-item-action">
								<div class="d-flex w-100 justify-content-between align-items-center">
									<div class="mb-1">5/13 워크숍 안내</div>
									<small class="text-danger">오늘</small>
								</div>
							</a>
							<a href="#" class="list-group-item list-group-item-action">
								<div class="d-flex w-100 justify-content-between align-items-center">
									<div>합동세례식 안내</div>
									<small class="text-secondary">2일전</small>
								</div>

							</a>
							<a href="#" class="list-group-item list-group-item-action align-items-center">
								<div class="d-flex w-100 justify-content-between">
									<div>환절기 난방기구 사용 시 유의점</div>
									<small class="text-secondary">3일전</small>
								</div>
							</a>
							<a href="#" class="list-group-item list-group-item-action align-items-center">
								<div class="d-flex w-100 justify-content-between">
									<div>왔니 서비스 서비스 중단 안내</div>
									<small class="text-secondary">2025.10.02</small>
								</div>
							</a>
							<a href="#" class="list-group-item list-group-item-action align-items-center">
								<div class="d-flex w-100 justify-content-between">
									<div>왔니 서비스 사용 안내</div>
									<small class="text-secondary">2025.10.01</small>
								</div>
							</a>
						</div>


				</div>
			</div>
			<div class="card">
				<div class="card-header d-flex align-items-center justify-content-between align-items-center">
					<h5 class="card-title mb-0 d-flex ">
						<i class="bi bi-calendar3 me-2"></i> 일정관리
					</h5>
					<a href="#">
						<small><i class="bi bi-plus-lg"></i> 더보기</small>
					</a>
				</div>
				<div class="card-body">
					<div class="list-group">
						<a href="#" class="list-group-item list-group-item-action align-items-center">
							<div class="d-flex w-100 justify-content-start">
								<span  class="badge text-bg-danger d-flex justify-content-center align-items-center" style="width: 50px">오늘</span >
								<div class="ms-2">5/13 워크숍 안내</div>
							</div>
						</a>
						<a href="#" class="list-group-item list-group-item-action align-items-center">
							<div class="d-flex w-100 justify-content-start">
								<span  class="badge text-bg-secondary d-flex justify-content-center align-items-center" style="width: 50px">2일후</span >
								<div class="ms-2">합동세례식 안내</div>
							</div>

						</a>
						<a href="#" class="list-group-item list-group-item-action align-items-center">
							<div class="d-flex w-100 justify-content-start">
								<span  class="badge text-bg-secondary d-flex justify-content-center align-items-center" style="width: 50px">3일후</span >
								<div class="ms-2">환절기 난방기구 사용 시 유의점</div>
							</div>
						</a>
						<a href="#" class="list-group-item list-group-item-action align-items-center">
							<div class="d-flex w-100 justify-content-start">
								<span  class="badge text-bg-secondary d-flex justify-content-center align-items-center" style="width: 50px">5일후</span >
								<div class="ms-2">왔니 서비스 서비스 중단 안내</div>
							</div>
						</a>
						<a href="#" class="list-group-item list-group-item-action align-items-center">
							<div class="d-flex w-100 justify-content-start">
								<span  class="badge text-bg-secondary d-flex justify-content-center align-items-center" style="width: 50px">999일후</span >
								<div class="ms-2">왔니 서비스 사용 안내</div>
							</div>
						</a>
					</div>
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
				<h5 class="modal-title">출석현황 표시</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<div id="attendanceTypeCheckboxes">
					<?php if (!empty($attendance_stats['att_types'])): ?>
						<?php foreach ($attendance_stats['att_types'] as $att_type): ?>
							<div class="form-check mb-2">
								<input class="form-check-input attendance-type-check"
									   type="checkbox"
									   value="<?php echo $att_type['att_type_idx']; ?>"
									   id="attType_<?php echo $att_type['att_type_idx']; ?>"
									   data-name="<?php echo htmlspecialchars($att_type['att_type_nickname']); ?>"
									   data-color="<?php echo $att_type['att_type_color']; ?>"
									   checked>
								<label class="form-check-label" for="attType_<?php echo $att_type['att_type_idx']; ?>">
                                    <span class="badge" style="background-color: #<?php echo $att_type['att_type_color']; ?>;">
                                        <?php echo htmlspecialchars($att_type['att_type_nickname']); ?>
                                    </span>
								</label>
							</div>
						<?php endforeach; ?>
					<?php else: ?>
						<p class="text-muted">출석 타입이 없습니다.</p>
					<?php endif; ?>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
				<button type="button" class="btn btn-primary" id="saveAttendanceSettingBtn">저장</button>
			</div>
		</div>
	</div>
</div>



<!-- 타임라인현황 설정 모달 (출석현황 모달 다음에 추가) -->
<div class="modal fade" id="timelineSettingModal" tabindex="-1" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title">타임라인현황 표시</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<div id="timelineTypeCheckboxes">
					<?php if (!empty($timeline_stats['timeline_types'])): ?>
						<?php foreach ($timeline_stats['timeline_types'] as $timeline_type): ?>
							<div class="form-check mb-2">
								<input class="form-check-input timeline-type-check"
									   type="checkbox"
									   value="<?php echo htmlspecialchars($timeline_type); ?>"
									   id="timelineType_<?php echo md5($timeline_type); ?>"
									   checked>
								<label class="form-check-label" for="timelineType_<?php echo md5($timeline_type); ?>">
									<?php echo htmlspecialchars($timeline_type); ?>
								</label>
							</div>
						<?php endforeach; ?>
					<?php else: ?>
						<p class="text-muted">타임라인 타입이 없습니다.</p>
					<?php endif; ?>
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
				<h5 class="modal-title">메모현황 표시</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<div id="memoTypeCheckboxes">
					<?php if (!empty($memo_stats['memo_types'])): ?>
						<?php foreach ($memo_stats['memo_types'] as $memo_type): ?>
							<div class="form-check mb-2">
								<input class="form-check-input memo-type-check"
									   type="checkbox"
									   value="<?php echo htmlspecialchars($memo_type); ?>"
									   id="memoType_<?php echo md5($memo_type); ?>"
									   checked>
								<label class="form-check-label" for="memoType_<?php echo md5($memo_type); ?>">
									<?php echo htmlspecialchars($memo_type); ?>
								</label>
							</div>
						<?php endforeach; ?>
					<?php else: ?>
						<p class="text-muted">메모 타입이 없습니다.</p>
					<?php endif; ?>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
				<button type="button" class="btn btn-primary" id="saveMemoSettingBtn">저장</button>
			</div>
		</div>
	</div>
</div>

<?php $this->load->view('footer'); ?>

<script>
	/* 출석관리 메뉴 active */
	$('.menu-11').addClass('active');
</script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="/assets/js/dashboard.js?<?php echo WB_VERSION; ?>"></script>
<script>
	// 회원현황 차트 데이터
	<?php if (!empty($weekly_new_members)): ?>
	const memberLabels = <?php echo json_encode(array_column($weekly_new_members, 'week_label')); ?>;
	const memberData = <?php echo json_encode(array_column($weekly_new_members, 'count')); ?>;
	<?php else: ?>
	const memberLabels = [];
	const memberData = [];
	<?php endif; ?>

	// 출석현황 데이터
	<?php if (!empty($attendance_stats)): ?>
	const attendanceRawDataFromPHP = <?php echo json_encode($attendance_stats['weekly_data']); ?>;
	const attendanceTypesFromPHP = <?php echo json_encode($attendance_stats['att_types']); ?>;
	<?php else: ?>
	const attendanceRawDataFromPHP = [];
	const attendanceTypesFromPHP = [];
	<?php endif; ?>

	// 타임라인현황 데이터
	<?php if (!empty($timeline_stats)): ?>
	const timelineRawDataFromPHP = <?php echo json_encode($timeline_stats['weekly_data']); ?>;
	const timelineTypesFromPHP = <?php echo json_encode($timeline_stats['timeline_types']); ?>;
	console.log('Timeline data loaded:', {
		dataCount: timelineRawDataFromPHP.length,
		typesCount: timelineTypesFromPHP.length,
		types: timelineTypesFromPHP
	});
	<?php else: ?>
	const timelineRawDataFromPHP = [];
	const timelineTypesFromPHP = [];
	console.log('Timeline data: empty');
	<?php endif; ?>

	// 메모현황 데이터
	<?php if (!empty($memo_stats)): ?>
	const memoRawDataFromPHP = <?php echo json_encode($memo_stats['weekly_data']); ?>;
	const memoTypesFromPHP = <?php echo json_encode($memo_stats['memo_types']); ?>;
	console.log('Memo data loaded:', {
		dataCount: memoRawDataFromPHP.length,
		typesCount: memoTypesFromPHP.length,
		types: memoTypesFromPHP
	});
	<?php else: ?>
	const memoRawDataFromPHP = [];
	const memoTypesFromPHP = [];
	console.log('Memo data: empty');
	<?php endif; ?>

	// 페이지 로드 후 차트 초기화
	document.addEventListener('DOMContentLoaded', function() {
		// 회원현황 차트 초기화
		if (memberLabels.length > 0) {
			initMemberChart(memberLabels, memberData);
		}

		// 출석현황 데이터 설정 및 차트 렌더링
		if (attendanceRawDataFromPHP.length > 0) {
			setAttendanceData(attendanceRawDataFromPHP, attendanceTypesFromPHP);
			renderAttendanceChart();
		}

		// 타임라인현황 데이터 설정 및 차트 렌더링
		if (timelineRawDataFromPHP.length > 0 && timelineTypesFromPHP.length > 0) {
			setTimelineData(timelineRawDataFromPHP, timelineTypesFromPHP);
			renderTimelineChart();
		} else {
			console.warn('타임라인 데이터가 없거나 타입이 설정되지 않았습니다.');
		}

		// 메모현황 데이터 설정 및 차트 렌더링
		if (memoRawDataFromPHP.length > 0 && memoTypesFromPHP.length > 0) {
			setMemoData(memoRawDataFromPHP, memoTypesFromPHP);
			renderMemoChart();
		} else {
			console.warn('메모 데이터가 없거나 타입이 설정되지 않았습니다.');
		}
	});
</script>
