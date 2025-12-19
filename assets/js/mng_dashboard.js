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
	$('#memberRefreshBtn').on('click', () => refreshStats('member'));

	$('#attendancePrevMonth').on('click', () => changeMonth('attendance', -1));
	$('#attendanceNextMonth').on('click', () => changeMonth('attendance', 1));
	$('#attendanceRefreshBtn').on('click', () => refreshStats('attendance'));

	$('#timelinePrevMonth').on('click', () => changeMonth('timeline', -1));
	$('#timelineNextMonth').on('click', () => changeMonth('timeline', 1));
	$('#timelineRefreshBtn').on('click', () => refreshStats('timeline'));

	$('#memoPrevMonth').on('click', () => changeMonth('memo', -1));
	$('#memoNextMonth').on('click', () => changeMonth('memo', 1));
	$('#memoRefreshBtn').on('click', () => refreshStats('memo'));

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
		const selectedTypes = getSelectedAttendanceTypes();
		saveAttendanceSettings(selectedTypes);
		if (attendanceRawData) {
			renderAttendanceChart();
		}
		$('#attendanceSettingModal').modal('hide');
	});

	$('#saveTimelineSettingBtn').on('click', function() {
		const selectedTypes = getSelectedTimelineTypes();
		saveTimelineSettings(selectedTypes);
		if (timelineRawData) {
			renderTimelineChart();
		}
		$('#timelineSettingModal').modal('hide');
	});

	$('#saveMemoSettingBtn').on('click', function() {
		const selectedTypes = getSelectedMemoTypes();
		saveMemoSettings(selectedTypes);
		if (memoRawData) {
			renderMemoChart();
		}
		$('#memoSettingModal').modal('hide');
	});
}

function refreshStats(type) {
	let year, month;

	switch(type) {
		case 'member':
			year = memberYear;
			month = memberMonth;
			break;
		case 'attendance':
			year = attendanceYear;
			month = attendanceMonth;
			break;
		case 'timeline':
			year = timelineYear;
			month = timelineMonth;
			break;
		case 'memo':
			year = memoYear;
			month = memoMonth;
			break;
		default:
			return;
	}

	const confirmMessage = `${year}년 ${month}월 ${getTypeKoreanName(type)} 통계를 갱신하시겠습니까?`;

	showConfirmModal(confirmMessage, function() {
		executeStatsRefresh(type, year, month);
	});
}

function getTypeKoreanName(type) {
	const names = {
		'member': '회원',
		'attendance': '출석',
		'timeline': '타임라인',
		'memo': '메모'
	};
	return names[type] || '';
}

function executeStatsRefresh(type, year, month) {
	const $btn = $(`#${type}RefreshBtn`);
	const originalHtml = $btn.html();

	$btn.prop('disabled', true);
	$btn.html('<span class="spinner-border spinner-border-sm me-1"></span>갱신중...');

	$.ajax({
		url: '/mng/mng_dashboard/refresh_stats',
		method: 'POST',
		data: {
			type: type,
			year: year,
			month: month
		},
		dataType: 'json',
		success: function(response) {
			if (response.success) {
				showToast('통계가 성공적으로 갱신되었습니다.', 'success');

				switch(type) {
					case 'member':
						loadMemberChart();
						break;
					case 'attendance':
						loadAttendanceChart();
						break;
					case 'timeline':
						loadTimelineChart();
						break;
					case 'memo':
						loadMemoChart();
						break;
				}
			} else {
				showToast(response.message || '통계 갱신에 실패했습니다.', 'error');
			}
		},
		error: function(xhr, status, error) {
			console.error('통계 갱신 오류:', error);
			console.error('응답 상태:', xhr.status);
			console.error('응답 내용:', xhr.responseText);
			showToast('통계 갱신 중 오류가 발생했습니다.', 'error');
		},
		complete: function() {
			$btn.prop('disabled', false);
			$btn.html(originalHtml);
		}
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




function showConfirmModal(message, onConfirm) {
	const modalHtml = `
		<div class="modal fade" id="confirmModal" tabindex="-1">
			<div class="modal-dialog">
				<div class="modal-content">
					<div class="modal-header">
						<h5 class="modal-title">확인</h5>
						<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
					</div>
					<div class="modal-body">
						<p>${escapeHtml(message)}</p>
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
						<button type="button" class="btn btn-primary" id="confirmBtn">확인</button>
					</div>
				</div>
			</div>
		</div>
	`;

	$('#confirmModal').remove();
	$('body').append(modalHtml);

	const modal = new bootstrap.Modal(document.getElementById('confirmModal'));

	$('#confirmBtn').on('click', function() {
		modal.hide();
		if (typeof onConfirm === 'function') {
			onConfirm();
		}
	});

	$('#confirmModal').on('hidden.bs.modal', function() {
		$(this).remove();
	});

	modal.show();
}

function showToast(message, type = 'info') {
	const bgClass = type === 'success' ? 'bg-success' : type === 'error' ? 'bg-danger' : 'bg-info';
	const toastHtml = `
		<div class="toast align-items-center text-white ${bgClass} border-0" role="alert" style="position: fixed; top: 20px; right: 20px; z-index: 9999;">
			<div class="d-flex">
				<div class="toast-body">
					${escapeHtml(message)}
				</div>
				<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
			</div>
		</div>
	`;

	$('body').append(toastHtml);
	const toastElement = $('.toast').last()[0];
	const toast = new bootstrap.Toast(toastElement, { delay: 3000 });
	toast.show();

	$(toastElement).on('hidden.bs.toast', function() {
		$(this).remove();
	});
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

	console.log('회원현황 조회:', {year: memberYear, month: memberMonth});

	$.ajax({
		url: '/mng/mng_dashboard/get_member_stats',
		method: 'POST',
		data: {
			year: memberYear,
			month: memberMonth
		},
		dataType: 'json',
		success: function(response) {
			console.log('회원현황 응답:', response);

			// 조회하려는 일요일 날짜들 출력
			if (response.success && response.data && response.data.weekly_data) {
				const sundayDates = response.data.weekly_data.map(w => w.sunday_date);
				console.log('조회된 일요일 날짜들:', sundayDates);
			}

			if (response.success && response.data) {
				restoreChartCanvas('memberChart');
				renderMemberChart(response.data);
			} else {
				showChartEmpty('memberChart', '회원현황 데이터가 없습니다.');
			}
		},
		error: function(xhr, status, error) {
			console.error('회원현황 AJAX 오류:', error);
			console.error('응답 상태:', xhr.status);
			console.error('응답 내용:', xhr.responseText);
			showChartError('memberChart', '데이터를 불러오는데 실패했습니다.');
		}
	});
}


function loadAttendanceChart() {
	showChartLoading('attendanceChart');
	updateMonthDisplay('attendance', attendanceYear, attendanceMonth);

	console.log('출석현황 조회:', {year: attendanceYear, month: attendanceMonth});

	$.ajax({
		url: '/mng/mng_dashboard/get_attendance_stats',
		method: 'POST',
		data: {
			year: attendanceYear,
			month: attendanceMonth
		},
		dataType: 'json',
		success: function(response) {
			console.log('출석현황 응답:', response);
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
			console.error('응답 상태:', xhr.status);
			console.error('응답 내용:', xhr.responseText);
			attendanceRawData = null;
			showChartError('attendanceChart', '데이터를 불러오는데 실패했습니다.');
		}
	});
}

function loadAttendanceTypes() {
	console.log('출석 타입 조회 시작');

	$.ajax({
		url: '/mng/mng_dashboard/get_attendance_types',
		method: 'POST',
		dataType: 'json',
		success: function(response) {
			console.log('출석 타입 응답:', response);
			if (response.success && response.data) {
				updateAttendanceTypeCheckboxes(response.data);
			}
		},
		error: function(xhr, status, error) {
			console.error('출석 타입 조회 오류:', error);
			console.error('응답 상태:', xhr.status);
			console.error('응답 내용:', xhr.responseText);
		}
	});
}

function updateAttendanceTypeCheckboxes(attTypes) {
	const $container = $('#attendanceTypeCheckboxes');
	$container.empty();

	console.log('출석 타입 데이터:', attTypes);

	if (attTypes.length === 0) {
		$container.html('<p class="text-muted">출석 타입이 없습니다.</p>');
		return;
	}

	const savedSettings = getAttendanceSettings();

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
		const typeValue = typeof type === 'string' ? type : (type.att_type_name || type.toString());
		const isChecked = savedSettings ? savedSettings.includes(typeValue) : true;
		const checkboxHtml = `
			<div class="form-check mb-2">
				<input class="form-check-input attendance-type-checkbox" 
					   type="checkbox" 
					   value="${escapeHtml(typeValue)}" 
					   id="attendance_type_${escapeHtml(typeValue)}" 
					   ${isChecked ? 'checked' : ''}>
				<label class="form-check-label" for="attendance_type_${escapeHtml(typeValue)}">
					${escapeHtml(typeValue)}
				</label>
			</div>
		`;
		$container.append(checkboxHtml);
	});

	updateAttendanceSelectAllState();

	$('#attendance_select_all').on('change', function() {
		$('.attendance-type-checkbox').prop('checked', $(this).is(':checked'));
	});

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



function loadTimelineChart() {
	showChartLoading('timelineChart');
	updateMonthDisplay('timeline', timelineYear, timelineMonth);

	console.log('타임라인현황 조회:', {year: timelineYear, month: timelineMonth});

	$.ajax({
		url: '/mng/mng_dashboard/get_timeline_stats',
		method: 'POST',
		data: {
			year: timelineYear,
			month: timelineMonth
		},
		dataType: 'json',
		success: function(response) {
			console.log('타임라인현황 응답:', response);
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
			console.error('응답 상태:', xhr.status);
			console.error('응답 내용:', xhr.responseText);
			timelineRawData = null;
			showChartError('timelineChart', '데이터를 불러오는데 실패했습니다.');
		}
	});
}

function loadTimelineTypes() {
	console.log('타임라인 타입 조회 시작');

	$.ajax({
		url: '/mng/mng_dashboard/get_timeline_types',
		method: 'POST',
		dataType: 'json',
		success: function(response) {
			console.log('타임라인 타입 응답:', response);
			if (response.success && response.data) {
				updateTimelineTypeCheckboxes(response.data);
			}
		},
		error: function(xhr, status, error) {
			console.error('타임라인 타입 조회 오류:', error);
			console.error('응답 상태:', xhr.status);
			console.error('응답 내용:', xhr.responseText);
		}
	});
}

function updateTimelineTypeCheckboxes(timelineTypes) {
	const $container = $('#timelineTypeCheckboxes');
	$container.empty();

	console.log('타임라인 타입 데이터:', timelineTypes);

	if (timelineTypes.length === 0) {
		$container.html('<p class="text-muted">타임라인 타입이 없습니다.</p>');
		return;
	}

	const savedSettings = getTimelineSettings();

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
		const typeValue = typeof type === 'string' ? type : (type.timeline_type || type.toString());
		const isChecked = savedSettings ? savedSettings.includes(typeValue) : true;
		const checkboxHtml = `
			<div class="form-check mb-2">
				<input class="form-check-input timeline-type-checkbox" 
					   type="checkbox" 
					   value="${escapeHtml(typeValue)}" 
					   id="timeline_type_${escapeHtml(typeValue)}" 
					   ${isChecked ? 'checked' : ''}>
				<label class="form-check-label" for="timeline_type_${escapeHtml(typeValue)}">
					${escapeHtml(typeValue)}
				</label>
			</div>
		`;
		$container.append(checkboxHtml);
	});

	updateTimelineSelectAllState();

	$('#timeline_select_all').on('change', function() {
		$('.timeline-type-checkbox').prop('checked', $(this).is(':checked'));
	});

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



function loadMemoChart() {
	showChartLoading('memoChart');
	updateMonthDisplay('memo', memoYear, memoMonth);

	console.log('메모현황 조회:', {year: memoYear, month: memoMonth});

	$.ajax({
		url: '/mng/mng_dashboard/get_memo_stats',
		method: 'POST',
		data: {
			year: memoYear,
			month: memoMonth
		},
		dataType: 'json',
		success: function(response) {
			console.log('메모현황 응답:', response);
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
			console.error('응답 상태:', xhr.status);
			console.error('응답 내용:', xhr.responseText);
			memoRawData = null;
			showChartError('memoChart', '데이터를 불러오는데 실패했습니다.');
		}
	});
}

function loadMemoTypes() {
	console.log('메모 타입 조회 시작');

	$.ajax({
		url: '/mng/mng_dashboard/get_memo_types',
		method: 'POST',
		dataType: 'json',
		success: function(response) {
			console.log('메모 타입 응답:', response);
			if (response.success && response.data) {
				updateMemoTypeCheckboxes(response.data);
			}
		},
		error: function(xhr, status, error) {
			console.error('메모 타입 조회 오류:', error);
			console.error('응답 상태:', xhr.status);
			console.error('응답 내용:', xhr.responseText);
		}
	});
}

function updateMemoTypeCheckboxes(memoTypes) {
	const $container = $('#memoTypeCheckboxes');
	$container.empty();

	console.log('메모 타입 데이터:', memoTypes);

	if (memoTypes.length === 0) {
		$container.html('<p class="text-muted">메모 타입이 없습니다.</p>');
		return;
	}

	const savedSettings = getMemoSettings();

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
		const typeValue = typeof type === 'string' ? type : (type.memo_type || type.toString());
		const isChecked = savedSettings ? savedSettings.includes(typeValue) : true;
		const checkboxHtml = `
			<div class="form-check mb-2">
				<input class="form-check-input memo-type-checkbox" 
					   type="checkbox" 
					   value="${escapeHtml(typeValue)}" 
					   id="memo_type_${escapeHtml(typeValue)}" 
					   ${isChecked ? 'checked' : ''}>
				<label class="form-check-label" for="memo_type_${escapeHtml(typeValue)}">
					${escapeHtml(typeValue)}
				</label>
			</div>
		`;
		$container.append(checkboxHtml);
	});

	updateMemoSelectAllState();

	$('#memo_select_all').on('change', function() {
		$('.memo-type-checkbox').prop('checked', $(this).is(':checked'));
	});

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


function renderMemberChart(data) {
	console.log('renderMemberChart 호출됨');
	console.log('전달받은 데이터:', data);

	const canvas = document.getElementById('memberChart');
	if (!canvas) {
		console.error('memberChart 캔버스를 찾을 수 없음');
		return;
	}

	console.log('캔버스 존재 확인 완료');

	if (memberChartInstance) {
		console.log('기존 차트 인스턴스 파괴');
		memberChartInstance.destroy();
	}

	if (!data || !data.orgs || !data.weekly_data) {
		console.log('데이터 구조 오류:', {
			hasData: !!data,
			hasOrgs: data ? !!data.orgs : false,
			hasWeeklyData: data ? !!data.weekly_data : false
		});

		// 빈 차트 표시
		memberChartInstance = new Chart(canvas, {
			type: 'bar',
			data: {
				labels: [],
				datasets: []
			},
			options: {
				responsive: true,
				maintainAspectRatio: true,
				plugins: {
					legend: {
						position: 'top',
					},
					title: {
						display: true,
						text: '표시할 데이터가 없습니다.'
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
		return;
	}

	console.log('조직 수:', data.orgs.length);
	console.log('주차 수:', data.weekly_data.length);

	const labels = data.weekly_data.map(week => week.week_label);
	console.log('레이블:', labels);

	const datasets = [];

	// 각 조직을 하나의 데이터셋으로 생성 (모두 같은 스택)
	// 데이터가 있는 조직만 포함
	data.orgs.forEach((org, index) => {
		const orgData = data.weekly_data.map(week => week.orgs[org.org_id] || 0);

		// 데이터 합계가 0보다 큰 경우만 추가
		const hasData = orgData.some(val => val > 0);

		if (!hasData) {
			console.log(`${org.org_name} - 데이터 없음, 스킵`);
			return;
		}

		const color = chartColors[index % chartColors.length];

		console.log(`${org.org_name} 데이터:`, orgData);

		datasets.push({
			label: org.org_name,
			data: orgData,
			backgroundColor: color,
			borderColor: color,
			borderWidth: 1,
			stack: 'Stack 0'  // 모든 조직을 같은 스택으로
		});
	});

	console.log('데이터셋 개수:', datasets.length);

	if (datasets.length === 0) {
		console.log('표시할 데이터가 없음 - 빈 차트 표시');
		memberChartInstance = new Chart(canvas, {
			type: 'bar',
			data: {
				labels: labels,
				datasets: []
			},
			options: {
				responsive: true,
				maintainAspectRatio: true,
				plugins: {
					legend: {
						position: 'top',
					},
					title: {
						display: true,
						text: '표시할 데이터가 없습니다.'
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
		return;
	}

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
				},
				tooltip: {
					callbacks: {
						footer: function(tooltipItems) {
							let sum = 0;
							tooltipItems.forEach(function(tooltipItem) {
								sum += tooltipItem.parsed.y;
							});
							return '전체: ' + sum + '명';
						}
					}
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

	console.log('차트 생성 완료');
}


function renderAttendanceChart() {
	console.log('renderAttendanceChart 호출됨');
	console.log('attendanceRawData:', attendanceRawData);

	const canvas = document.getElementById('attendanceChart');
	if (!canvas) {
		console.error('attendanceChart 캔버스를 찾을 수 없음');
		return;
	}

	if (attendanceChartInstance) {
		attendanceChartInstance.destroy();
	}

	if (!attendanceRawData) {
		console.log('출석 데이터 없음 - 빈 차트 표시');
		attendanceChartInstance = new Chart(canvas, {
			type: 'bar',
			data: {
				labels: [],
				datasets: []
			},
			options: {
				responsive: true,
				maintainAspectRatio: true,
				plugins: {
					legend: {
						position: 'top',
					},
					title: {
						display: true,
						text: '표시할 데이터가 없습니다.'
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
		return;
	}

	const data = attendanceRawData;

	console.log('출석 차트 데이터:', data);

	if (!data.orgs || !data.weekly_data || !data.att_types) {
		console.log('출석 데이터 구조 오류');
		attendanceChartInstance = new Chart(canvas, {
			type: 'bar',
			data: {
				labels: [],
				datasets: []
			},
			options: {
				responsive: true,
				maintainAspectRatio: true,
				plugins: {
					legend: {
						position: 'top',
					},
					title: {
						display: true,
						text: '표시할 데이터가 없습니다.'
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
		return;
	}

	const savedSettings = getAttendanceSettings();
	const selectedTypes = savedSettings && savedSettings.length > 0
		? savedSettings
		: data.att_types;

	const filteredTypes = selectedTypes.length > 0
		? data.att_types.filter(t => selectedTypes.includes(t))
		: data.att_types;

	if (filteredTypes.length === 0) {
		console.log('필터링된 출석 타입 없음');
		attendanceChartInstance = new Chart(canvas, {
			type: 'bar',
			data: {
				labels: [],
				datasets: []
			},
			options: {
				responsive: true,
				maintainAspectRatio: true,
				plugins: {
					legend: {
						position: 'top',
					},
					title: {
						display: true,
						text: '선택된 출석 타입이 없습니다.'
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
		return;
	}

	const labels = data.weekly_data.map(d => d.week_label);
	const datasets = [];

	filteredTypes.forEach((attType, typeIndex) => {
		const baseColor = chartColors[typeIndex % chartColors.length];

		data.orgs.forEach((org, orgIndex) => {
			const orgTypeData = data.weekly_data.map(week => {
				return week.orgs[org.org_id] && week.orgs[org.org_id][attType]
					? week.orgs[org.org_id][attType]
					: 0;
			});

			const hasData = orgTypeData.some(val => val > 0);
			if (!hasData) return;

			const rgb = baseColor.match(/\d+/g);
			const alpha = 0.4 + (orgIndex * 0.15);
			const adjustedColor = `rgba(${rgb[0]}, ${rgb[1]}, ${rgb[2]}, ${Math.min(alpha, 1)})`;

			datasets.push({
				label: `${attType} - ${org.org_name}`,
				data: orgTypeData,
				backgroundColor: adjustedColor,
				borderColor: baseColor,
				borderWidth: 1,
				stack: `Stack ${typeIndex}`
			});
		});
	});

	if (datasets.length === 0) {
		console.log('출석 데이터셋 없음 - 빈 차트 표시');
		attendanceChartInstance = new Chart(canvas, {
			type: 'bar',
			data: {
				labels: labels,
				datasets: []
			},
			options: {
				responsive: true,
				maintainAspectRatio: true,
				plugins: {
					legend: {
						position: 'top',
					},
					title: {
						display: true,
						text: '표시할 데이터가 없습니다.'
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
		return;
	}

	console.log('출석 차트 생성:', datasets.length + '개 데이터셋');

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

function renderTimelineChart() {
	console.log('renderTimelineChart 호출됨');
	console.log('timelineRawData:', timelineRawData);

	const canvas = document.getElementById('timelineChart');
	if (!canvas) {
		console.error('timelineChart 캔버스를 찾을 수 없음');
		return;
	}

	if (timelineChartInstance) {
		timelineChartInstance.destroy();
	}

	if (!timelineRawData) {
		console.log('타임라인 데이터 없음 - 빈 차트 표시');
		timelineChartInstance = new Chart(canvas, {
			type: 'bar',
			data: {
				labels: [],
				datasets: []
			},
			options: {
				responsive: true,
				maintainAspectRatio: true,
				plugins: {
					legend: {
						position: 'top',
					},
					title: {
						display: true,
						text: '표시할 데이터가 없습니다.'
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
		return;
	}

	const data = timelineRawData;

	if (!data.orgs || !data.weekly_data || !data.timeline_types) {
		console.log('타임라인 데이터 구조 오류');
		timelineChartInstance = new Chart(canvas, {
			type: 'bar',
			data: {
				labels: [],
				datasets: []
			},
			options: {
				responsive: true,
				maintainAspectRatio: true,
				plugins: {
					legend: {
						position: 'top',
					},
					title: {
						display: true,
						text: '표시할 데이터가 없습니다.'
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
		return;
	}

	const savedSettings = getTimelineSettings();
	const selectedTypes = savedSettings && savedSettings.length > 0
		? savedSettings
		: data.timeline_types;

	const filteredTypes = selectedTypes.length > 0
		? data.timeline_types.filter(t => selectedTypes.includes(t))
		: data.timeline_types;

	if (filteredTypes.length === 0) {
		console.log('필터링된 타임라인 타입 없음');
		timelineChartInstance = new Chart(canvas, {
			type: 'bar',
			data: {
				labels: [],
				datasets: []
			},
			options: {
				responsive: true,
				maintainAspectRatio: true,
				plugins: {
					legend: {
						position: 'top',
					},
					title: {
						display: true,
						text: '선택된 타임라인 타입이 없습니다.'
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
		return;
	}

	const labels = data.weekly_data.map(d => d.week_label);
	const datasets = [];

	filteredTypes.forEach((timelineType, typeIndex) => {
		const baseColor = chartColors[typeIndex % chartColors.length];

		data.orgs.forEach((org, orgIndex) => {
			const orgTypeData = data.weekly_data.map(week => {
				return week.orgs[org.org_id] && week.orgs[org.org_id][timelineType]
					? week.orgs[org.org_id][timelineType]
					: 0;
			});

			const hasData = orgTypeData.some(val => val > 0);
			if (!hasData) return;

			const rgb = baseColor.match(/\d+/g);
			const alpha = 0.4 + (orgIndex * 0.15);
			const adjustedColor = `rgba(${rgb[0]}, ${rgb[1]}, ${rgb[2]}, ${Math.min(alpha, 1)})`;

			datasets.push({
				label: `${timelineType} - ${org.org_name}`,
				data: orgTypeData,
				backgroundColor: adjustedColor,
				borderColor: baseColor,
				borderWidth: 1,
				stack: `Stack ${typeIndex}`
			});
		});
	});

	if (datasets.length === 0) {
		console.log('타임라인 데이터셋 없음 - 빈 차트 표시');
		timelineChartInstance = new Chart(canvas, {
			type: 'bar',
			data: {
				labels: labels,
				datasets: []
			},
			options: {
				responsive: true,
				maintainAspectRatio: true,
				plugins: {
					legend: {
						position: 'top',
					},
					title: {
						display: true,
						text: '표시할 데이터가 없습니다.'
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

function renderMemoChart() {
	console.log('renderMemoChart 호출됨');
	console.log('memoRawData:', memoRawData);

	const canvas = document.getElementById('memoChart');
	if (!canvas) {
		console.error('memoChart 캔버스를 찾을 수 없음');
		return;
	}

	if (memoChartInstance) {
		memoChartInstance.destroy();
	}

	if (!memoRawData) {
		console.log('메모 데이터 없음 - 빈 차트 표시');
		memoChartInstance = new Chart(canvas, {
			type: 'bar',
			data: {
				labels: [],
				datasets: []
			},
			options: {
				responsive: true,
				maintainAspectRatio: true,
				plugins: {
					legend: {
						position: 'top',
					},
					title: {
						display: true,
						text: '표시할 데이터가 없습니다.'
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
		return;
	}

	const data = memoRawData;

	if (!data.orgs || !data.weekly_data || !data.memo_types) {
		console.log('메모 데이터 구조 오류');
		memoChartInstance = new Chart(canvas, {
			type: 'bar',
			data: {
				labels: [],
				datasets: []
			},
			options: {
				responsive: true,
				maintainAspectRatio: true,
				plugins: {
					legend: {
						position: 'top',
					},
					title: {
						display: true,
						text: '표시할 데이터가 없습니다.'
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
		return;
	}

	const savedSettings = getMemoSettings();
	const selectedTypes = savedSettings && savedSettings.length > 0
		? savedSettings
		: data.memo_types;

	const filteredTypes = selectedTypes.length > 0
		? data.memo_types.filter(t => selectedTypes.includes(t))
		: data.memo_types;

	if (filteredTypes.length === 0) {
		console.log('필터링된 메모 타입 없음');
		memoChartInstance = new Chart(canvas, {
			type: 'bar',
			data: {
				labels: [],
				datasets: []
			},
			options: {
				responsive: true,
				maintainAspectRatio: true,
				plugins: {
					legend: {
						position: 'top',
					},
					title: {
						display: true,
						text: '선택된 메모 타입이 없습니다.'
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
		return;
	}

	const labels = data.weekly_data.map(d => d.week_label);
	const datasets = [];

	filteredTypes.forEach((memoType, typeIndex) => {
		const baseColor = chartColors[typeIndex % chartColors.length];

		data.orgs.forEach((org, orgIndex) => {
			const orgTypeData = data.weekly_data.map(week => {
				return week.orgs[org.org_id] && week.orgs[org.org_id][memoType]
					? week.orgs[org.org_id][memoType]
					: 0;
			});

			const hasData = orgTypeData.some(val => val > 0);
			if (!hasData) return;

			const rgb = baseColor.match(/\d+/g);
			const alpha = 0.4 + (orgIndex * 0.15);
			const adjustedColor = `rgba(${rgb[0]}, ${rgb[1]}, ${rgb[2]}, ${Math.min(alpha, 1)})`;

			datasets.push({
				label: `${memoType} - ${org.org_name}`,
				data: orgTypeData,
				backgroundColor: adjustedColor,
				borderColor: baseColor,
				borderWidth: 1,
				stack: `Stack ${typeIndex}`
			});
		});
	});

	if (datasets.length === 0) {
		console.log('메모 데이터셋 없음 - 빈 차트 표시');
		memoChartInstance = new Chart(canvas, {
			type: 'bar',
			data: {
				labels: labels,
				datasets: []
			},
			options: {
				responsive: true,
				maintainAspectRatio: true,
				plugins: {
					legend: {
						position: 'top',
					},
					title: {
						display: true,
						text: '표시할 데이터가 없습니다.'
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
