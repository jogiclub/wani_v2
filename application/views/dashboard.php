
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
				</div>
				<div class="card-body">
					<canvas id="attendanceChart"></canvas>
				</div>
			</div>
			<div class="card mb-3">
				<div class="card-header d-flex align-items-center justify-content-between align-items-center">
					<h5 class="card-title mb-0 d-flex ">
						<i class="bi bi-watch me-2"></i> 타임라인현황
					</h5>
				</div>
				<div class="card-body">
					<canvas id="timelineChart"></canvas>
				</div>
			</div>
			<div class="card mb-3">
				<div class="card-header d-flex align-items-center justify-content-between align-items-center">
					<h5 class="card-title mb-0 d-flex ">
						<i class="bi bi-journals me-2"></i> 메모현황
					</h5>
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




<?php $this->load->view('footer'); ?>

<script>
	/*출석관리 메뉴 active*/
	$('.menu-11').addClass('active');
</script>
<script src="/assets/js/dashboard.js?<?php echo WB_VERSION; ?>"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
	const member_chart = document.getElementById('memberChart');
	new Chart(member_chart, {
		type: 'bar',
		data: {
			labels: ['8/17','8/24', '8/31', '9/7', '9/14', '9/21', '9/28', '10/5'],
			datasets: [{
				label: '신규회원',
				data: [1,9, 3, 2, 1, 4, 5, 3],
				borderWidth: 1
			}]
		},
		options: {
			responsive: true,
			plugins: {
				legend: {
					position: 'right',
				},
				title: {
					display: false,
					text: 'Chart.js Bar Chart'
				}
			}
		},
	});

	const attendance_chart = document.getElementById('attendanceChart');
	new Chart(attendance_chart, {
		type: 'bar',
		data: {
			labels: ['8/17','8/24', '8/31', '9/7', '9/14', '9/21', '9/28', '10/5'],
			datasets: [
				{
				label: '주일',
				data: [1,2, 3, 2, 1, 3, 5, 3],
				borderWidth: 1
				},
				{
					label: '온라인',
					data: [4,9, 1, 2, 1, 7, 5, 3],
					borderWidth: 1
				},
				{
					label: '장년',
					data: [1,2, 5, 2, 1, 4, 5, 3],
					borderWidth: 1
				},
				{
					label: '무단',
					data: [3,9, 3, 2, 17, 4, 5, 3],
					borderWidth: 1
				}
			]
		},
		options: {
			responsive: true,
			plugins: {
				legend: {
					position: 'right',
				},
				title: {
					display: false,
					text: 'Chart.js Bar Chart'
				}
			}
		},
	});




	const memo_chart = document.getElementById('memoChart');
	new Chart(memo_chart, {
		type: 'bar',
		data: {
			labels: ['8/17','8/24', '8/31', '9/7', '9/14', '9/21', '9/28', '10/5'],
			datasets: [
				{
					label: '주일',
					data: [1,2, 3, 2, 1, 3, 5, 3],
					borderWidth: 1
				},
				{
					label: '온라인',
					data: [4,9, 1, 2, 1, 7, 5, 3],
					borderWidth: 1
				},
				{
					label: '장년',
					data: [1,2, 5, 2, 1, 4, 5, 3],
					borderWidth: 1
				},
				{
					label: '무단',
					data: [3,9, 3, 2, 17, 4, 5, 3],
					borderWidth: 1
				}
			]
		},
		options: {
			responsive: true,
			plugins: {
				legend: {
					position: 'right',
				},
				title: {
					display: false,
					text: 'Chart.js Bar Chart'
				}
			}
		},
	});



	const timeline_chart = document.getElementById('timelineChart');
	new Chart(timeline_chart, {
		type: 'bar',
		data: {
			labels: ['8/17','8/24', '8/31', '9/7', '9/14', '9/21', '9/28', '10/5'],
			datasets: [
				{
					label: '주일',
					data: [1,2, 3, 2, 1, 3, 5, 3],
					borderWidth: 1
				},
				{
					label: '온라인',
					data: [4,9, 1, 2, 1, 7, 5, 3],
					borderWidth: 1
				},
				{
					label: '장년',
					data: [1,2, 5, 2, 1, 4, 5, 3],
					borderWidth: 1
				},
				{
					label: '무단',
					data: [3,9, 3, 2, 17, 4, 5, 3],
					borderWidth: 1
				}
			]
		},
		options: {
			responsive: true,
			plugins: {
				legend: {
					position: 'right',
				},
				title: {
					display: false,
					text: 'Chart.js Bar Chart'
				}
			}
		},
	});

</script>
