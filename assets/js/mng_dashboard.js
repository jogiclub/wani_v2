'use strict';

let memberChartInstance = null;
let attendanceChartInstance = null;
let timelineChartInstance = null;
let memoChartInstance = null;

let currentDate = new Date();
let memberYear = currentDate.getFullYear();
let memberMonth = currentDate.getMonth() + 1;
let attendanceYear = currentDate.getFullYear();
let attendanceMonth = currentDate.getMonth() + 1;
let timelineYear = currentDate.getFullYear();
let timelineMonth = currentDate.getMonth() + 1;
let memoYear = currentDate.getFullYear();
let memoMonth = currentDate.getMonth() + 1;

let attendanceRawData = null;
let timelineRawData = null;
let memoRawData = null;

const chartColors = [
	'rgb(255, 99, 132)',
	'rgb(54, 162, 235)',
	'rgb(255, 206, 86)',
	'rgb(75, 192, 192)',
	'rgb(153, 102, 255)',
	'rgb(255, 159, 64)',
	'rgb(201, 203, 207)',
	'rgb(255, 99, 71)',
	'rgb(0, 128, 128)',
	'rgb(128, 0, 128)'
];

$(document).ready(function() {
	initEventHandlers();
	loadAllCharts();
});

function initEventHandlers() {
	$('#memberPrevMonth').on('click', () => changeMonth('member', -1));
	$('#memberNextMonth').on('click', () => changeMonth('member', 1));

	$('#attendancePrevMonth').on('click', () => changeMonth('attendance', -1));
	$('#attendanceNextMonth').on('click', () => changeMonth('attendance', 1));

	$('#timelinePrevMonth').on('click', () => changeMonth('timeline', -1));
	$('#timelineNextMonth').on('click', () => changeMonth('timeline', 1));

	$('#memoPrevMonth').on('click', () => changeMonth('memo', -1));
	$('#memoNextMonth').on('click', () => changeMonth('memo', 1));

	$('#attendanceSettingBtn').on('click', function(e) {
		e.preventDefault();
		loadAttendanceTypes();
		$('#attendanceSettingModal').modal('show');
	});

	$('#timelineSettingBtn').on('click', function(e) {
		e.preventDefault();
		loadTimelineTypes();
		$('#timelineSettingModal').modal('show');
	});

	$('#memoSettingBtn').on('click', function(e) {
		e.preventDefault();
		loadMemoTypes();
		$('#memoSettingModal').modal('show');
	});

	$('#saveAttendanceSettingBtn').on('click', function() {
		if (attendanceRawData) {
			renderAttendanceChart();
		}
		$('#attendanceSettingModal').modal('hide');
	});

	$('#saveTimelineSettingBtn').on('click', function() {
		if (timelineRawData) {
			renderTimelineChart();
		}
		$('#timelineSettingModal').modal('hide');
	});

	$('#saveMemoSettingBtn').on('click', function() {
		if (memoRawData) {
			renderMemoChart();
		}
		$('#memoSettingModal').modal('hide');
	});
}

function changeMonth(type, delta) {
	if (type === 'member') {
		const newDate = new Date(memberYear, memberMonth - 1 + delta, 1);
		memberYear = newDate.getFullYear();
		memberMonth = newDate.getMonth() + 1;
		loadMemberChart();
	} else if (type === 'attendance') {
		const newDate = new Date(attendanceYear, attendanceMonth - 1 + delta, 1);
		attendanceYear = newDate.getFullYear();
		attendanceMonth = newDate.getMonth() + 1;
		loadAttendanceChart();
	} else if (type === 'timeline') {
		const newDate = new Date(timelineYear, timelineMonth - 1 + delta, 1);
		timelineYear = newDate.getFullYear();
		timelineMonth = newDate.getMonth() + 1;
		loadTimelineChart();
	} else if (type === 'memo') {
		const newDate = new Date(memoYear, memoMonth - 1 + delta, 1);
		memoYear = newDate.getFullYear();
		memoMonth = newDate.getMonth() + 1;
		loadMemoChart();
	}
}

function updateMonthDisplay(type, year, month) {
	$(`#${type}CurrentMonth`).text(`${year}년 ${month}월`);
}

function loadAllCharts() {
	loadMemberChart();
	loadAttendanceChart();
	loadTimelineChart();
	loadMemoChart();
}

function showChartLoading(chartId) {
	const chartElement = document.getElementById(chartId);
	if (chartElement && chartElement.parentElement) {
		const parentDiv = chartElement.parentElement;
		parentDiv.setAttribute('data-chart-id', chartId);
		parentDiv.innerHTML = `
			<div class="text-center py-5">
				<div class="spinner-border text-primary" role="status">
					<span class="visually-hidden">로딩중...</span>
				</div>
				<div class="mt-2 text-muted">데이터를 불러오는 중...</div>
			</div>
		`;
	}
}

function restoreChartCanvas(chartId) {
	const parentDiv = document.querySelector(`[data-chart-id="${chartId}"]`);
	if (parentDiv) {
		parentDiv.innerHTML = `<canvas id="${chartId}"></canvas>`;
		parentDiv.removeAttribute('data-chart-id');
	}
}

function showChartError(chartId, message) {
	const parentDiv = document.querySelector(`[data-chart-id="${chartId}"]`);
	if (parentDiv) {
		parentDiv.innerHTML = `<div class="text-center text-danger py-5">${escapeHtml(message)}</div>`;
		parentDiv.removeAttribute('data-chart-id');
	}
}

function showChartEmpty(chartId, message) {
	const parentDiv = document.querySelector(`[data-chart-id="${chartId}"]`);
	if (parentDiv) {
		parentDiv.innerHTML = `<div class="text-center text-muted py-5">${escapeHtml(message)}</div>`;
		parentDiv.removeAttribute('data-chart-id');
	}
}

function escapeHtml(text) {
	const map = {
		'&': '&amp;',
		'<': '&lt;',
		'>': '&gt;',
		'"': '&quot;',
		"'": '&#039;'
	};
	return text.replace(/[&<>"']/g, m => map[m]);
}

function loadMemberChart() {
	showChartLoading('memberChart');
	updateMonthDisplay('member', memberYear, memberMonth);

	$.ajax({
		url: '/mng/mng_dashboard/get_member_stats',
		method: 'POST',
		data: {
			year: memberYear,
			month: memberMonth
		},
		dataType: 'json',
		success: function(response) {
			if (response.success && response.data) {
				restoreChartCanvas('memberChart');
				renderMemberChart(response.data);
			} else {
				showChartEmpty('memberChart', '회원현황 데이터가 없습니다.');
			}
		},
		error: function(xhr, status, error) {
			console.error('회원현황 AJAX 오류:', error);
			showChartError('memberChart', '데이터를 불러오는데 실패했습니다.');
		}
	});
}

function renderMemberChart(data) {
	const canvas = document.getElementById('memberChart');
	if (!canvas || !data || !data.orgs || !data.weekly_data) return;

	if (memberChartInstance) {
		memberChartInstance.destroy();
	}

	// 데이터가 있는 조직만 필터링
	const orgsWithData = data.orgs.filter(org => {
		return data.weekly_data.some(week => (week.orgs[org.org_id] || 0) > 0);
	});

	if (orgsWithData.length === 0) {
		const parentDiv = canvas.parentElement;
		parentDiv.innerHTML = '<div class="text-center text-muted py-5">표시할 데이터가 없습니다.</div>';
		return;
	}

	const labels = data.weekly_data.map(week => week.week_label);
	const datasets = [];

	// 각 조직별로 데이터셋 생성 (각 조직이 하나의 스택)
	orgsWithData.forEach((org, index) => {
		const orgData = data.weekly_data.map(week => week.orgs[org.org_id] || 0);
		const color = chartColors[index % chartColors.length];

		datasets.push({
			label: org.org_name,
			data: orgData,
			backgroundColor: color,
			borderColor: color,
			borderWidth: 1,
			stack: `Stack ${index}` // 각 조직이 별도의 스택
		});
	});

	memberChartInstance = new Chart(canvas, {
		type: 'bar',
		data: {
			labels: labels,
			datasets: datasets
		},
		options: {
			responsive: true,
			maintainAspectRatio: true,
			plugins: {
				legend: {
					position: 'top',
				},
				title: {
					display: false
				}
			},
			scales: {
				x: {
					stacked: true,
				},
				y: {
					stacked: true,
					beginAtZero: true,
					ticks: {
						stepSize: 1
					}
				}
			}
		}
	});
}

function loadAttendanceChart() {
	showChartLoading('attendanceChart');
	updateMonthDisplay('attendance', attendanceYear, attendanceMonth);

	$.ajax({
		url: '/mng/mng_dashboard/get_attendance_stats',
		method: 'POST',
		data: {
			year: attendanceYear,
			month: attendanceMonth
		},
		dataType: 'json',
		success: function(response) {
			if (response.success && response.data) {
				attendanceRawData = response.data;
				restoreChartCanvas('attendanceChart');
				renderAttendanceChart();
			} else {
				attendanceRawData = null;
				showChartEmpty('attendanceChart', '출석현황 데이터가 없습니다.');
			}
		},
		error: function(xhr, status, error) {
			console.error('출석현황 AJAX 오류:', error);
			attendanceRawData = null;
			showChartError('attendanceChart', '데이터를 불러오는데 실패했습니다.');
		}
	});
}

function loadAttendanceTypes() {
	$.ajax({
		url: '/mng/mng_dashboard/get_attendance_types',
		method: 'POST',
		dataType: 'json',
		success: function(response) {
			if (response.success && response.data) {
				updateAttendanceTypeCheckboxes(response.data);
			}
		},
		error: function(xhr, status, error) {
			console.error('출석 타입 조회 오류:', error);
		}
	});
}

function updateAttendanceTypeCheckboxes(attTypes) {
	const $container = $('#attendanceTypeCheckboxes');
	$container.empty();

	if (attTypes.length === 0) {
		$container.html('<p class="text-muted">출석 타입이 없습니다.</p>');
		return;
	}

	const savedSettings = getAttendanceSettings();

	// 전체 선택 체크박스 추가
	const selectAllHtml = `
		<div class="form-check mb-3 pb-2 border-bottom">
			<input class="form-check-input" type="checkbox" id="attendance_select_all">
			<label class="form-check-label fw-bold" for="attendance_select_all">
				전체 선택
			</label>
		</div>
	`;
	$container.append(selectAllHtml);

	attTypes.forEach(function(type) {
		const isChecked = savedSettings ? savedSettings.includes(type.att_type_idx) : true;
		const checkboxHtml = `
			<div class="form-check mb-2">
				<input class="form-check-input attendance-type-checkbox" 
					   type="checkbox" 
					   value="${type.att_type_idx}" 
					   id="att_type_${type.att_type_idx}" 
					   ${isChecked ? 'checked' : ''}>
				<label class="form-check-label" for="att_type_${type.att_type_idx}">
					<span class="badge" style="background-color: #${type.att_type_color}">${type.att_type_nickname}</span>
				</label>
			</div>
		`;
		$container.append(checkboxHtml);
	});

	// 전체 선택 상태 업데이트
	updateAttendanceSelectAllState();

	// 전체 선택 체크박스 이벤트
	$('#attendance_select_all').on('change', function() {
		$('.attendance-type-checkbox').prop('checked', $(this).is(':checked'));
	});

	// 개별 체크박스 이벤트
	$('.attendance-type-checkbox').on('change', function() {
		updateAttendanceSelectAllState();
	});
}

function updateAttendanceSelectAllState() {
	const total = $('.attendance-type-checkbox').length;
	const checked = $('.attendance-type-checkbox:checked').length;
	const selectAll = $('#attendance_select_all');

	if (checked === 0) {
		selectAll.prop('checked', false);
		selectAll.prop('indeterminate', false);
	} else if (checked === total) {
		selectAll.prop('checked', true);
		selectAll.prop('indeterminate', false);
	} else {
		selectAll.prop('checked', false);
		selectAll.prop('indeterminate', true);
	}
}

function getSelectedAttendanceTypes() {
	const selected = [];
	$('.attendance-type-checkbox:checked').each(function() {
		selected.push($(this).val());
	});
	return selected;
}

function getAttendanceSettings() {
	const saved = localStorage.getItem('mng_dashboard_attendance_types');
	if (saved) {
		try {
			return JSON.parse(saved);
		} catch (e) {
			return null;
		}
	}
	return null;
}

function saveAttendanceSettings(selectedTypes) {
	localStorage.setItem('mng_dashboard_attendance_types', JSON.stringify(selectedTypes));
}

function renderAttendanceChart() {
	const canvas = document.getElementById('attendanceChart');
	if (!canvas || !attendanceRawData) return;

	if (attendanceChartInstance) {
		attendanceChartInstance.destroy();
	}

	const data = attendanceRawData;
	const selectedTypes = getSelectedAttendanceTypes();
	saveAttendanceSettings(selectedTypes);

	if (!data.orgs || !data.weekly_data || !data.att_types) {
		const parentDiv = canvas.parentElement;
		parentDiv.innerHTML = '<div class="text-center text-muted py-5">표시할 데이터가 없습니다.</div>';
		return;
	}

	const filteredTypes = selectedTypes.length > 0
		? data.att_types.filter(t => selectedTypes.includes(t.att_type_idx))
		: data.att_types;

	if (filteredTypes.length === 0) {
		const parentDiv = canvas.parentElement;
		parentDiv.innerHTML = '<div class="text-center text-muted py-5">선택된 출석 타입이 없습니다.</div>';
		return;
	}

	const labels = data.weekly_data.map(d => d.week_label);
	const datasets = [];

	// 각 타입별로 스택 생성
	filteredTypes.forEach((attType, typeIndex) => {
		const baseColor = chartColors[typeIndex % chartColors.length];

		// 각 조직별로 데이터셋 생성 (같은 타입은 같은 스택)
		data.orgs.forEach((org, orgIndex) => {
			const orgTypeData = data.weekly_data.map(week => {
				return week.orgs[org.org_id] && week.orgs[org.org_id][attType.att_type_idx]
					? week.orgs[org.org_id][attType.att_type_idx]
					: 0;
			});

			const hasData = orgTypeData.some(val => val > 0);
			if (!hasData) return;

			// 조직별로 색상 농도 조절
			const rgb = baseColor.match(/\d+/g);
			const alpha = 0.4 + (orgIndex * 0.15);
			const adjustedColor = `rgba(${rgb[0]}, ${rgb[1]}, ${rgb[2]}, ${Math.min(alpha, 1)})`;

			datasets.push({
				label: `${attType.att_type_nickname} - ${org.org_name}`,
				data: orgTypeData,
				backgroundColor: adjustedColor,
				borderColor: baseColor,
				borderWidth: 1,
				stack: `Stack ${typeIndex}` // 같은 타입은 같은 스택
			});
		});
	});

	if (datasets.length === 0) {
		const parentDiv = canvas.parentElement;
		parentDiv.innerHTML = '<div class="text-center text-muted py-5">표시할 데이터가 없습니다.</div>';
		return;
	}

	attendanceChartInstance = new Chart(canvas, {
		type: 'bar',
		data: {
			labels: labels,
			datasets: datasets
		},
		options: {
			responsive: true,
			maintainAspectRatio: true,
			plugins: {
				legend: {
					position: 'top',
				},
				title: {
					display: false
				}
			},
			scales: {
				x: {
					stacked: true,
				},
				y: {
					stacked: true,
					beginAtZero: true,
					ticks: {
						stepSize: 1
					}
				}
			}
		}
	});
}

function loadTimelineChart() {
	showChartLoading('timelineChart');
	updateMonthDisplay('timeline', timelineYear, timelineMonth);

	$.ajax({
		url: '/mng/mng_dashboard/get_timeline_stats',
		method: 'POST',
		data: {
			year: timelineYear,
			month: timelineMonth
		},
		dataType: 'json',
		success: function(response) {
			if (response.success && response.data) {
				timelineRawData = response.data;
				restoreChartCanvas('timelineChart');
				renderTimelineChart();
			} else {
				timelineRawData = null;
				showChartEmpty('timelineChart', '타임라인현황 데이터가 없습니다.');
			}
		},
		error: function(xhr, status, error) {
			console.error('타임라인현황 AJAX 오류:', error);
			timelineRawData = null;
			showChartError('timelineChart', '데이터를 불러오는데 실패했습니다.');
		}
	});
}

function loadTimelineTypes() {
	$.ajax({
		url: '/mng/mng_dashboard/get_timeline_types',
		method: 'POST',
		dataType: 'json',
		success: function(response) {
			if (response.success && response.data) {
				updateTimelineTypeCheckboxes(response.data);
			}
		},
		error: function(xhr, status, error) {
			console.error('타임라인 타입 조회 오류:', error);
		}
	});
}

function updateTimelineTypeCheckboxes(timelineTypes) {
	const $container = $('#timelineTypeCheckboxes');
	$container.empty();

	if (timelineTypes.length === 0) {
		$container.html('<p class="text-muted">타임라인 타입이 없습니다.</p>');
		return;
	}

	const savedSettings = getTimelineSettings();

	// 전체 선택 체크박스 추가
	const selectAllHtml = `
		<div class="form-check mb-3 pb-2 border-bottom">
			<input class="form-check-input" type="checkbox" id="timeline_select_all">
			<label class="form-check-label fw-bold" for="timeline_select_all">
				전체 선택
			</label>
		</div>
	`;
	$container.append(selectAllHtml);

	timelineTypes.forEach(function(type) {
		const isChecked = savedSettings ? savedSettings.includes(type) : true;
		const checkboxHtml = `
			<div class="form-check mb-2">
				<input class="form-check-input timeline-type-checkbox" 
					   type="checkbox" 
					   value="${type}" 
					   id="timeline_type_${type}" 
					   ${isChecked ? 'checked' : ''}>
				<label class="form-check-label" for="timeline_type_${type}">
					${type}
				</label>
			</div>
		`;
		$container.append(checkboxHtml);
	});

	// 전체 선택 상태 업데이트
	updateTimelineSelectAllState();

	// 전체 선택 체크박스 이벤트
	$('#timeline_select_all').on('change', function() {
		$('.timeline-type-checkbox').prop('checked', $(this).is(':checked'));
	});

	// 개별 체크박스 이벤트
	$('.timeline-type-checkbox').on('change', function() {
		updateTimelineSelectAllState();
	});
}

function updateTimelineSelectAllState() {
	const total = $('.timeline-type-checkbox').length;
	const checked = $('.timeline-type-checkbox:checked').length;
	const selectAll = $('#timeline_select_all');

	if (checked === 0) {
		selectAll.prop('checked', false);
		selectAll.prop('indeterminate', false);
	} else if (checked === total) {
		selectAll.prop('checked', true);
		selectAll.prop('indeterminate', false);
	} else {
		selectAll.prop('checked', false);
		selectAll.prop('indeterminate', true);
	}
}

function getSelectedTimelineTypes() {
	const selected = [];
	$('.timeline-type-checkbox:checked').each(function() {
		selected.push($(this).val());
	});
	return selected;
}

function getTimelineSettings() {
	const saved = localStorage.getItem('mng_dashboard_timeline_types');
	if (saved) {
		try {
			return JSON.parse(saved);
		} catch (e) {
			return null;
		}
	}
	return null;
}

function saveTimelineSettings(selectedTypes) {
	localStorage.setItem('mng_dashboard_timeline_types', JSON.stringify(selectedTypes));
}

function renderTimelineChart() {
	const canvas = document.getElementById('timelineChart');
	if (!canvas || !timelineRawData) return;

	if (timelineChartInstance) {
		timelineChartInstance.destroy();
	}

	const data = timelineRawData;
	const selectedTypes = getSelectedTimelineTypes();
	saveTimelineSettings(selectedTypes);

	if (!data.orgs || !data.weekly_data || !data.timeline_types) {
		const parentDiv = canvas.parentElement;
		parentDiv.innerHTML = '<div class="text-center text-muted py-5">표시할 데이터가 없습니다.</div>';
		return;
	}

	const filteredTypes = selectedTypes.length > 0
		? data.timeline_types.filter(t => selectedTypes.includes(t))
		: data.timeline_types;

	if (filteredTypes.length === 0) {
		const parentDiv = canvas.parentElement;
		parentDiv.innerHTML = '<div class="text-center text-muted py-5">선택된 타임라인 타입이 없습니다.</div>';
		return;
	}

	const labels = data.weekly_data.map(d => d.week_label);
	const datasets = [];

	// 각 타입별로 스택 생성
	filteredTypes.forEach((timelineType, typeIndex) => {
		const baseColor = chartColors[typeIndex % chartColors.length];

		// 각 조직별로 데이터셋 생성 (같은 타입은 같은 스택)
		data.orgs.forEach((org, orgIndex) => {
			const orgTypeData = data.weekly_data.map(week => {
				return week.orgs[org.org_id] && week.orgs[org.org_id][timelineType]
					? week.orgs[org.org_id][timelineType]
					: 0;
			});

			const hasData = orgTypeData.some(val => val > 0);
			if (!hasData) return;

			// 조직별로 색상 농도 조절
			const rgb = baseColor.match(/\d+/g);
			const alpha = 0.4 + (orgIndex * 0.15);
			const adjustedColor = `rgba(${rgb[0]}, ${rgb[1]}, ${rgb[2]}, ${Math.min(alpha, 1)})`;

			datasets.push({
				label: `${timelineType} - ${org.org_name}`,
				data: orgTypeData,
				backgroundColor: adjustedColor,
				borderColor: baseColor,
				borderWidth: 1,
				stack: `Stack ${typeIndex}` // 같은 타입은 같은 스택
			});
		});
	});

	if (datasets.length === 0) {
		const parentDiv = canvas.parentElement;
		parentDiv.innerHTML = '<div class="text-center text-muted py-5">표시할 데이터가 없습니다.</div>';
		return;
	}

	timelineChartInstance = new Chart(canvas, {
		type: 'bar',
		data: {
			labels: labels,
			datasets: datasets
		},
		options: {
			responsive: true,
			maintainAspectRatio: true,
			plugins: {
				legend: {
					position: 'top',
				},
				title: {
					display: false
				}
			},
			scales: {
				x: {
					stacked: true,
				},
				y: {
					stacked: true,
					beginAtZero: true,
					ticks: {
						stepSize: 1
					}
				}
			}
		}
	});
}

function loadMemoChart() {
	showChartLoading('memoChart');
	updateMonthDisplay('memo', memoYear, memoMonth);

	$.ajax({
		url: '/mng/mng_dashboard/get_memo_stats',
		method: 'POST',
		data: {
			year: memoYear,
			month: memoMonth
		},
		dataType: 'json',
		success: function(response) {
			if (response.success && response.data) {
				memoRawData = response.data;
				restoreChartCanvas('memoChart');
				renderMemoChart();
			} else {
				memoRawData = null;
				showChartEmpty('memoChart', '메모현황 데이터가 없습니다.');
			}
		},
		error: function(xhr, status, error) {
			console.error('메모현황 AJAX 오류:', error);
			memoRawData = null;
			showChartError('memoChart', '데이터를 불러오는데 실패했습니다.');
		}
	});
}

function loadMemoTypes() {
	$.ajax({
		url: '/mng/mng_dashboard/get_memo_types',
		method: 'POST',
		dataType: 'json',
		success: function(response) {
			if (response.success && response.data) {
				updateMemoTypeCheckboxes(response.data);
			}
		},
		error: function(xhr, status, error) {
			console.error('메모 타입 조회 오류:', error);
		}
	});
}

function updateMemoTypeCheckboxes(memoTypes) {
	const $container = $('#memoTypeCheckboxes');
	$container.empty();

	if (memoTypes.length === 0) {
		$container.html('<p class="text-muted">메모 타입이 없습니다.</p>');
		return;
	}

	const savedSettings = getMemoSettings();

	// 전체 선택 체크박스 추가
	const selectAllHtml = `
		<div class="form-check mb-3 pb-2 border-bottom">
			<input class="form-check-input" type="checkbox" id="memo_select_all">
			<label class="form-check-label fw-bold" for="memo_select_all">
				전체 선택
			</label>
		</div>
	`;
	$container.append(selectAllHtml);

	memoTypes.forEach(function(type) {
		const isChecked = savedSettings ? savedSettings.includes(type) : true;
		const checkboxHtml = `
			<div class="form-check mb-2">
				<input class="form-check-input memo-type-checkbox" 
					   type="checkbox" 
					   value="${type}" 
					   id="memo_type_${type}" 
					   ${isChecked ? 'checked' : ''}>
				<label class="form-check-label" for="memo_type_${type}">
					${type}
				</label>
			</div>
		`;
		$container.append(checkboxHtml);
	});

	// 전체 선택 상태 업데이트
	updateMemoSelectAllState();

	// 전체 선택 체크박스 이벤트
	$('#memo_select_all').on('change', function() {
		$('.memo-type-checkbox').prop('checked', $(this).is(':checked'));
	});

	// 개별 체크박스 이벤트
	$('.memo-type-checkbox').on('change', function() {
		updateMemoSelectAllState();
	});
}

function updateMemoSelectAllState() {
	const total = $('.memo-type-checkbox').length;
	const checked = $('.memo-type-checkbox:checked').length;
	const selectAll = $('#memo_select_all');

	if (checked === 0) {
		selectAll.prop('checked', false);
		selectAll.prop('indeterminate', false);
	} else if (checked === total) {
		selectAll.prop('checked', true);
		selectAll.prop('indeterminate', false);
	} else {
		selectAll.prop('checked', false);
		selectAll.prop('indeterminate', true);
	}
}

function getSelectedMemoTypes() {
	const selected = [];
	$('.memo-type-checkbox:checked').each(function() {
		selected.push($(this).val());
	});
	return selected;
}

function getMemoSettings() {
	const saved = localStorage.getItem('mng_dashboard_memo_types');
	if (saved) {
		try {
			return JSON.parse(saved);
		} catch (e) {
			return null;
		}
	}
	return null;
}

function saveMemoSettings(selectedTypes) {
	localStorage.setItem('mng_dashboard_memo_types', JSON.stringify(selectedTypes));
}

function renderMemoChart() {
	const canvas = document.getElementById('memoChart');
	if (!canvas || !memoRawData) return;

	if (memoChartInstance) {
		memoChartInstance.destroy();
	}

	const data = memoRawData;
	const selectedTypes = getSelectedMemoTypes();
	saveMemoSettings(selectedTypes);

	if (!data.orgs || !data.weekly_data || !data.memo_types) {
		const parentDiv = canvas.parentElement;
		parentDiv.innerHTML = '<div class="text-center text-muted py-5">표시할 데이터가 없습니다.</div>';
		return;
	}

	const filteredTypes = selectedTypes.length > 0
		? data.memo_types.filter(t => selectedTypes.includes(t))
		: data.memo_types;

	if (filteredTypes.length === 0) {
		const parentDiv = canvas.parentElement;
		parentDiv.innerHTML = '<div class="text-center text-muted py-5">선택된 메모 타입이 없습니다.</div>';
		return;
	}

	const labels = data.weekly_data.map(d => d.week_label);
	const datasets = [];

	// 각 타입별로 스택 생성
	filteredTypes.forEach((memoType, typeIndex) => {
		const baseColor = chartColors[typeIndex % chartColors.length];

		// 각 조직별로 데이터셋 생성 (같은 타입은 같은 스택)
		data.orgs.forEach((org, orgIndex) => {
			const orgTypeData = data.weekly_data.map(week => {
				return week.orgs[org.org_id] && week.orgs[org.org_id][memoType]
					? week.orgs[org.org_id][memoType]
					: 0;
			});

			const hasData = orgTypeData.some(val => val > 0);
			if (!hasData) return;

			// 조직별로 색상 농도 조절
			const rgb = baseColor.match(/\d+/g);
			const alpha = 0.4 + (orgIndex * 0.15);
			const adjustedColor = `rgba(${rgb[0]}, ${rgb[1]}, ${rgb[2]}, ${Math.min(alpha, 1)})`;

			datasets.push({
				label: `${memoType} - ${org.org_name}`,
				data: orgTypeData,
				backgroundColor: adjustedColor,
				borderColor: baseColor,
				borderWidth: 1,
				stack: `Stack ${typeIndex}` // 같은 타입은 같은 스택
			});
		});
	});

	if (datasets.length === 0) {
		const parentDiv = canvas.parentElement;
		parentDiv.innerHTML = '<div class="text-center text-muted py-5">표시할 데이터가 없습니다.</div>';
		return;
	}

	memoChartInstance = new Chart(canvas, {
		type: 'bar',
		data: {
			labels: labels,
			datasets: datasets
		},
		options: {
			responsive: true,
			maintainAspectRatio: true,
			plugins: {
				legend: {
					position: 'top',
				},
				title: {
					display: false
				}
			},
			scales: {
				x: {
					stacked: true,
				},
				y: {
					stacked: true,
					beginAtZero: true,
					ticks: {
						stepSize: 1
					}
				}
			}
		}
	});
}
