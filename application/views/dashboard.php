
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

	<!-- 접근 가능한 메뉴 표시 -->
	<?php if (!empty($accessible_menus)): ?>
		<div class="card mb-4">
			<div class="card-header">
				<h5 class="card-title mb-0 d-flex align-items-center">
					<i class="bi bi-grid-3x3-gap me-2"></i> 빠른 메뉴
				</h5>
			</div>
			<div class="card-body">
				<div class="row">
				<?php foreach ($accessible_menus as $category_name => $category_menus): ?>
					<?php if ($category_name !== 'OVERVIEW'): // 대시보드 자체는 제외 ?>



								<div class="col-12 col-xl-6 mb-2">
									<h6 class="text-muted text-uppercase mb-2"><?php echo $category_name; ?></h6>
									<div class="row g-2">
								<?php foreach ($category_menus as $menu): ?>

									<div class="col-3 col-xl-2">
										<a href="<?php echo base_url($menu['url']); ?>" class="text-decoration-none">
											<div class="card h-100 quick-menu-card">
												<div class="card-body text-center px-1 py-3">
													<i class="<?php echo $menu['icon']; ?> fs-3 text-primary mb-2"></i>
													<div class="small fw-medium text-dark mt-2 lh-1"><?php echo $menu['name']; ?></div>
												</div>
											</div>
										</a>
									</div>

								<?php endforeach; ?>
									</div>
								</div>


					<?php endif; ?>
				<?php endforeach; ?>
				</div>
			</div>
		</div>
	<?php endif; ?>

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
									<div class="text-truncate">5/13 워크숍 안내</div>
									<small class="text-danger" style="word-break: keep-all">오늘</small>
								</div>
							</a>
							<a href="#" class="list-group-item list-group-item-action">
								<div class="d-flex w-100 justify-content-between align-items-center">
									<div class="text-truncate">합동세례식 안내</div>
									<small class="text-secondary" style="word-break: keep-all">2일전</small>
								</div>

							</a>
							<a href="#" class="list-group-item list-group-item-action align-items-center">
								<div class="d-flex w-100 justify-content-between">
									<div class="text-truncate ">환절기 난방기구 사용 시 유의점</div>
									<small class="text-secondary" style="word-break: keep-all">3일전</small>
								</div>
							</a>
							<a href="#" class="list-group-item list-group-item-action align-items-center">
								<div class="d-flex w-100 justify-content-between">
									<div class="text-truncate">왔니 서비스 서비스 중단 안내</div>
									<small class="text-secondary" style="word-break: keep-all">2025.10.02</small>
								</div>
							</a>
							<a href="#" class="list-group-item list-group-item-action align-items-center">
								<div class="d-flex w-100 justify-content-between">
									<div class="text-truncate">왔니 서비스 사용 안내</div>
									<small class="text-secondary" style="word-break: keep-all">2025.10.01</small>
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
								<div class="ms-2 text-truncate">5/13 워크숍 안내</div>
							</div>
						</a>
						<a href="#" class="list-group-item list-group-item-action align-items-center">
							<div class="d-flex w-100 justify-content-start">
								<span  class="badge text-bg-secondary d-flex justify-content-center align-items-center" style="width: 50px">2일후</span >
								<div class="ms-2 text-truncate">합동세례식 안내</div>
							</div>

						</a>
						<a href="#" class="list-group-item list-group-item-action align-items-center">
							<div class="d-flex w-100 justify-content-start">
								<span  class="badge text-bg-secondary d-flex justify-content-center align-items-center" style="width: 50px">3일후</span >
								<div class="ms-2 text-truncate">환절기 난방기구 사용 시 유의점</div>
							</div>
						</a>
						<a href="#" class="list-group-item list-group-item-action align-items-center">
							<div class="d-flex w-100 justify-content-start">
								<span  class="badge text-bg-secondary d-flex justify-content-center align-items-center" style="width: 50px">5일후</span >
								<div class="ms-2 text-truncate">왔니 서비스 서비스 중단 안내</div>
							</div>
						</a>
						<a href="#" class="list-group-item list-group-item-action align-items-center">
							<div class="d-flex w-100 justify-content-start">
								<span  class="badge text-bg-secondary d-flex justify-content-center align-items-center" style="width: 50px">999일후</span >
								<div class="ms-2 text-truncate">왔니 서비스 사용 안내</div>
							</div>
						</a>
					</div>
				</div>
			</div>
		</div>
	</div>

</div>




<!-- 타임라인현황 설정 모달 -->
<div class="modal fade" id="timelineSettingModal" tabindex="-1" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title">타임라인현황 표시</h5>
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
				<h5 class="modal-title">메모현황 표시</h5>
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

<!-- 출석현황 설정 모달도 동일하게 수정 -->
<div class="modal fade" id="attendanceSettingModal" tabindex="-1" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title">출석현황 표시</h5>
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

<?php $this->load->view('footer'); ?>

<script>
	/* 출석관리 메뉴 active */
	$('.menu-11').addClass('active');
</script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="/assets/js/dashboard.js?<?php echo WB_VERSION; ?>"></script>
<script>
	// 현재 조직 ID를 JavaScript 전역 변수로 설정
	const currentOrgIdFromPHP = <?php echo $current_org['org_id']; ?>;

	// 페이지 로드 후 모든 차트 로드
	document.addEventListener('DOMContentLoaded', function() {
		// 모든 차트를 AJAX로 비동기 로드
		loadAllCharts(currentOrgIdFromPHP);
	});
</script>
