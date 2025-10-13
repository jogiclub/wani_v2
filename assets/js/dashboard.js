// 파일 위치: assets/js/dashboard.js
// 역할: 대시보드 차트 및 통계 관리

'use strict';

// ========== 전역 변수 ==========
let currentOrgId = null;

// 차트 인스턴스
let memberChartInstance = null;
let attendanceChartInstance = null;
let timelineChartInstance = null;
let memoChartInstance = null;

// 차트 데이터
let attendanceRawData = [];
let attendanceTypes = [];
let timelineRawData = [];
let timelineTypes = [];
let memoRawData = [];
let memoTypes = [];

// ========== 유틸리티 함수 ==========

/**
 * HTML 이스케이프 처리
 */
function escapeHtml(text) {
	const map = {
		'&': '&amp;',
		'<': '&lt;',
		'>': '&gt;',
		'"': '&quot;',
		"'": '&#039;'
	};
	return String(text).replace(/[&<>"']/g, function(m) { return map[m]; });
}


/**
 * 로딩 스피너 표시
 */
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

/**
 * 차트 캔버스 복원
 */
function restoreChartCanvas(chartId) {
	const parentDiv = document.querySelector(`[data-chart-id="${chartId}"]`);
	if (parentDiv) {
		parentDiv.innerHTML = `<canvas id="${chartId}"></canvas>`;
		parentDiv.removeAttribute('data-chart-id');
	}
}

/**
 * 에러 메시지 표시
 */
function showChartError(chartId, message) {
	const parentDiv = document.querySelector(`[data-chart-id="${chartId}"]`);
	if (parentDiv) {
		parentDiv.innerHTML = `<div class="text-center text-danger py-5">${escapeHtml(message)}</div>`;
		parentDiv.removeAttribute('data-chart-id');
	}
}

/**
 * 빈 데이터 메시지 표시
 */
function showChartEmpty(chartId, message) {
	const parentDiv = document.querySelector(`[data-chart-id="${chartId}"]`);
	if (parentDiv) {
		parentDiv.innerHTML = `<div class="text-center text-muted py-5">${escapeHtml(message)}</div>`;
		parentDiv.removeAttribute('data-chart-id');
	}
}

// ========== 회원현황 차트 ==========

/**
 * 회원현황 데이터 로드
 */
function loadMemberChart(orgId) {
	showChartLoading('memberChart');

	$.ajax({
		url: '/dashboard/get_member_stats',
		method: 'POST',
		data: { org_id: orgId },
		dataType: 'json',
		success: function(response) {
			if (response.success && response.data && response.data.length > 0) {
				restoreChartCanvas('memberChart');
				const labels = response.data.map(item => item.week_label);
				const data = response.data.map(item => item.count);
				renderMemberChart(labels, data);
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

/**
 * 회원현황 차트 렌더링
 */
function renderMemberChart(labels, data) {
	const canvas = document.getElementById('memberChart');
	if (!canvas) return;

	if (memberChartInstance) {
		memberChartInstance.destroy();
	}

	memberChartInstance = new Chart(canvas, {
		type: 'bar',
		data: {
			labels: labels,
			datasets: [{
				label: '신규회원',
				data: data,
				borderWidth: 1,
				backgroundColor: 'rgba(54, 162, 235, 0.5)',
				borderColor: 'rgba(54, 162, 235, 1)'
			}]
		},
		options: {
			responsive: true,
			maintainAspectRatio: true,
			plugins: {
				legend: {
					position: 'right',
				},
				title: {
					display: false
				}
			},
			scales: {
				y: {
					beginAtZero: true,
					ticks: {
						stepSize: 1
					}
				}
			}
		}
	});
}

// ========== 출석현황 차트 ==========

/**
 * 출석현황 데이터 로드
 */
function loadAttendanceChart(orgId) {
	showChartLoading('attendanceChart');

	$.ajax({
		url: '/dashboard/get_attendance_stats',
		method: 'POST',
		data: { org_id: orgId },
		dataType: 'json',
		success: function(response) {
			if (response.success && response.data) {
				restoreChartCanvas('attendanceChart');
				attendanceRawData = response.data.weekly_data || [];
				attendanceTypes = response.data.att_types || [];

				if (attendanceTypes.length > 0) {
					updateAttendanceModal(attendanceTypes);
					renderAttendanceChart();
				} else {
					showChartEmpty('attendanceChart', '출석 타입을 먼저 설정해주세요.');
				}
			} else {
				showChartEmpty('attendanceChart', '출석현황 데이터가 없습니다.');
			}
		},
		error: function(xhr, status, error) {
			console.error('출석현황 AJAX 오류:', error);
			showChartError('attendanceChart', '데이터를 불러오는데 실패했습니다.');
		}
	});
}

/**
 * 출석현황 설정 모달 업데이트
 */
function updateAttendanceModal(attTypes) {
	const container = document.getElementById('attendanceTypeCheckboxes');
	if (!container) return;

	if (!attTypes || attTypes.length === 0) {
		container.innerHTML = '<p class="text-muted">출석 타입이 없습니다.</p>';
		return;
	}

	let html = '';
	attTypes.forEach(function(attType) {
		const escapedNickname = escapeHtml(attType.att_type_nickname);
		html += `
            <div class="form-check mb-2">
                <input class="form-check-input attendance-type-check" 
                       type="checkbox" 
                       value="${attType.att_type_idx}"
                       id="attType_${attType.att_type_idx}"
                       checked>
                <label class="form-check-label" for="attType_${attType.att_type_idx}">
                    <span class="badge" style="background-color: #${attType.att_type_color};">
                        ${escapedNickname}
                    </span>
                </label>
            </div>
        `;
	});

	container.innerHTML = html;
}

/**
 * 출석현황 차트 렌더링
 */
function renderAttendanceChart() {
	if (!attendanceRawData || attendanceRawData.length === 0) {
		console.log('출석 데이터가 없습니다.');
		return;
	}

	const canvas = document.getElementById('attendanceChart');
	if (!canvas) return;

	// localStorage에서 저장된 설정 불러오기
	const savedSettings = getAttendanceSettings();
	let selectedTypes = [];

	if (savedSettings && Array.isArray(savedSettings)) {
		selectedTypes = savedSettings;
	} else {
		selectedTypes = attendanceTypes.map(type => type.att_type_idx.toString());
	}

	// 체크박스 상태 업데이트
	document.querySelectorAll('.attendance-type-check').forEach(checkbox => {
		checkbox.checked = selectedTypes.includes(checkbox.value);
	});

	// 라벨 추출
	const labels = attendanceRawData.map(week => week.week_label);

	// 데이터셋 구성
	const datasets = [];
	attendanceTypes.forEach(attType => {
		if (selectedTypes.includes(attType.att_type_idx.toString())) {
			const data = attendanceRawData.map(week => {
				return week.types[attType.att_type_idx] ? week.types[attType.att_type_idx].count : 0;
			});

			datasets.push({
				label: attType.att_type_nickname,
				data: data,
				backgroundColor: `#${attType.att_type_color}80`,
				borderColor: `#${attType.att_type_color}`,
				borderWidth: 1
			});
		}
	});

	// 기존 차트 파괴
	if (attendanceChartInstance) {
		attendanceChartInstance.destroy();
	}

	// 새 차트 생성
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
					position: 'right',
				},
				title: {
					display: false
				}
			},
			scales: {
				y: {
					beginAtZero: true,
					ticks: {
						stepSize: 1
					}
				}
			}
		}
	});
}

/**
 * 출석현황 설정 가져오기
 */
function getAttendanceSettings() {
	const saved = localStorage.getItem('dashboard_attendance_settings');
	if (saved) {
		try {
			return JSON.parse(saved);
		} catch (e) {
			return null;
		}
	}
	return null;
}

/**
 * 출석현황 설정 저장
 */
function saveAttendanceSettings(selectedTypes) {
	localStorage.setItem('dashboard_attendance_settings', JSON.stringify(selectedTypes));
}

// ========== 타임라인현황 차트 ==========

/**
 * 타임라인현황 데이터 로드
 */
function loadTimelineChart(orgId) {
	showChartLoading('timelineChart');

	$.ajax({
		url: '/dashboard/get_timeline_stats',
		method: 'POST',
		data: { org_id: orgId },
		dataType: 'json',
		success: function(response) {
			if (response.success && response.data) {
				restoreChartCanvas('timelineChart');
				timelineRawData = response.data.weekly_data || [];
				timelineTypes = response.data.timeline_types || [];

				if (timelineTypes.length > 0) {
					updateTimelineModal(timelineTypes);
					renderTimelineChart();
				} else {
					showChartEmpty('timelineChart', '타임라인 타입을 먼저 설정해주세요.');
				}
			} else {
				showChartEmpty('timelineChart', '타임라인현황 데이터가 없습니다.');
			}
		},
		error: function(xhr, status, error) {
			console.error('타임라인현황 AJAX 오류:', error);
			showChartError('timelineChart', '데이터를 불러오는데 실패했습니다.');
		}
	});
}

/**
 * 타임라인현황 설정 모달 업데이트
 */
function updateTimelineModal(types) {
	const container = document.getElementById('timelineTypeCheckboxes');
	if (!container) return;

	if (!types || types.length === 0) {
		container.innerHTML = '<p class="text-muted">타임라인 타입이 없습니다.</p>';
		return;
	}

	let html = '';
	types.forEach(function(type, index) {
		const typeId = 'timelineType_' + index;
		const escapedType = escapeHtml(type);
		html += `
            <div class="form-check mb-2">
                <input class="form-check-input timeline-type-check" 
                       type="checkbox" 
                       value="${escapedType}"
                       id="${typeId}"
                       checked>
                <label class="form-check-label" for="${typeId}">
                    ${escapedType}
                </label>
            </div>
        `;
	});

	container.innerHTML = html;
}

/**
 * 타임라인현황 차트 렌더링
 */
function renderTimelineChart() {
	if (!timelineRawData || timelineRawData.length === 0) {
		console.log('타임라인 데이터가 없습니다.');
		return;
	}

	const canvas = document.getElementById('timelineChart');
	if (!canvas) return;

	// localStorage에서 저장된 설정 불러오기
	const savedSettings = getTimelineSettings();
	let selectedTypes = [];

	if (savedSettings && Array.isArray(savedSettings)) {
		selectedTypes = savedSettings;
	} else {
		selectedTypes = timelineTypes.slice();
	}

	// 체크박스 상태 업데이트
	document.querySelectorAll('.timeline-type-check').forEach(checkbox => {
		checkbox.checked = selectedTypes.includes(checkbox.value);
	});

	// 라벨 추출
	const labels = timelineRawData.map(week => week.week_label);

	// 데이터셋 구성
	const datasets = [];
	const colors = [
		'#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF',
		'#FF9F40', '#E7E9ED', '#C9CBCF', '#8E5EA2', '#3CB371'
	];

	let colorIndex = 0;
	timelineTypes.forEach(timelineType => {
		if (selectedTypes.includes(timelineType)) {
			const data = timelineRawData.map(week => {
				return week.types[timelineType] || 0;
			});

			const color = colors[colorIndex % colors.length];
			datasets.push({
				label: timelineType,
				data: data,
				backgroundColor: color + '80',
				borderColor: color,
				borderWidth: 1
			});
			colorIndex++;
		}
	});

	// 기존 차트 파괴
	if (timelineChartInstance) {
		timelineChartInstance.destroy();
	}

	// 새 차트 생성
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
					position: 'right',
				},
				title: {
					display: false
				}
			},
			scales: {
				y: {
					beginAtZero: true,
					ticks: {
						stepSize: 1
					}
				}
			}
		}
	});
}

/**
 * 타임라인현황 설정 가져오기
 */
function getTimelineSettings() {
	const saved = localStorage.getItem('dashboard_timeline_settings');
	if (saved) {
		try {
			return JSON.parse(saved);
		} catch (e) {
			return null;
		}
	}
	return null;
}

/**
 * 타임라인현황 설정 저장
 */
function saveTimelineSettings(selectedTypes) {
	localStorage.setItem('dashboard_timeline_settings', JSON.stringify(selectedTypes));
}

// ========== 메모현황 차트 ==========

/**
 * 메모현황 데이터 로드
 */
function loadMemoChart(orgId) {
	showChartLoading('memoChart');

	$.ajax({
		url: '/dashboard/get_memo_stats',
		method: 'POST',
		data: { org_id: orgId },
		dataType: 'json',
		success: function(response) {
			if (response.success && response.data) {
				restoreChartCanvas('memoChart');
				memoRawData = response.data.weekly_data || [];
				memoTypes = response.data.memo_types || [];

				if (memoTypes.length > 0) {
					updateMemoModal(memoTypes);
					renderMemoChart();
				} else {
					showChartEmpty('memoChart', '메모 타입을 먼저 설정해주세요.');
				}
			} else {
				showChartEmpty('memoChart', '메모현황 데이터가 없습니다.');
			}
		},
		error: function(xhr, status, error) {
			console.error('메모현황 AJAX 오류:', error);
			showChartError('memoChart', '데이터를 불러오는데 실패했습니다.');
		}
	});
}

/**
 * 메모현황 설정 모달 업데이트
 */
function updateMemoModal(types) {
	const container = document.getElementById('memoTypeCheckboxes');
	if (!container) return;

	if (!types || types.length === 0) {
		container.innerHTML = '<p class="text-muted">메모 타입이 없습니다.</p>';
		return;
	}

	let html = '';
	types.forEach(function(type, index) {
		const typeId = 'memoType_' + index;
		const escapedType = escapeHtml(type);
		html += `
            <div class="form-check mb-2">
                <input class="form-check-input memo-type-check" 
                       type="checkbox" 
                       value="${escapedType}"
                       id="${typeId}"
                       checked>
                <label class="form-check-label" for="${typeId}">
                    ${escapedType}
                </label>
            </div>
        `;
	});

	container.innerHTML = html;
}

/**
 * 메모현황 차트 렌더링
 */
function renderMemoChart() {
	if (!memoRawData || memoRawData.length === 0) {
		console.log('메모 데이터가 없습니다.');
		return;
	}

	const canvas = document.getElementById('memoChart');
	if (!canvas) return;

	// localStorage에서 저장된 설정 불러오기
	const savedSettings = getMemoSettings();
	let selectedTypes = [];

	if (savedSettings && Array.isArray(savedSettings)) {
		selectedTypes = savedSettings;
	} else {
		selectedTypes = memoTypes.slice();
	}

	// 체크박스 상태 업데이트
	document.querySelectorAll('.memo-type-check').forEach(checkbox => {
		checkbox.checked = selectedTypes.includes(checkbox.value);
	});

	// 라벨 추출
	const labels = memoRawData.map(week => week.week_label);

	// 데이터셋 구성
	const datasets = [];
	const colors = [
		'#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF',
		'#FF9F40', '#E7E9ED', '#C9CBCF', '#8E5EA2', '#3CB371'
	];

	let colorIndex = 0;
	memoTypes.forEach(memoType => {
		if (selectedTypes.includes(memoType)) {
			const data = memoRawData.map(week => {
				return week.types[memoType] || 0;
			});

			const color = colors[colorIndex % colors.length];
			datasets.push({
				label: memoType,
				data: data,
				backgroundColor: color + '80',
				borderColor: color,
				borderWidth: 1
			});
			colorIndex++;
		}
	});

	// 기존 차트 파괴
	if (memoChartInstance) {
		memoChartInstance.destroy();
	}

	// 새 차트 생성
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
					position: 'right',
				},
				title: {
					display: false
				}
			},
			scales: {
				y: {
					beginAtZero: true,
					ticks: {
						stepSize: 1
					}
				}
			}
		}
	});
}

/**
 * 메모현황 설정 가져오기
 */
function getMemoSettings() {
	const saved = localStorage.getItem('dashboard_memo_settings');
	if (saved) {
		try {
			return JSON.parse(saved);
		} catch (e) {
			return null;
		}
	}
	return null;
}

/**
 * 메모현황 설정 저장
 */
function saveMemoSettings(selectedTypes) {
	localStorage.setItem('dashboard_memo_settings', JSON.stringify(selectedTypes));
}

// ========== 이벤트 초기화 ==========

/**
 * 출석현황 설정 이벤트
 */
function initAttendanceSettingEvents() {
	const settingBtn = document.getElementById('attendanceSettingBtn');
	const saveBtn = document.getElementById('saveAttendanceSettingBtn');

	if (settingBtn) {
		settingBtn.addEventListener('click', function(e) {
			e.preventDefault();
			const modal = new bootstrap.Modal(document.getElementById('attendanceSettingModal'));
			modal.show();
		});
	}

	if (saveBtn) {
		saveBtn.addEventListener('click', function() {
			const selectedTypes = [];
			document.querySelectorAll('.attendance-type-check:checked').forEach(checkbox => {
				selectedTypes.push(checkbox.value);
			});

			saveAttendanceSettings(selectedTypes);
			renderAttendanceChart();

			const modal = bootstrap.Modal.getInstance(document.getElementById('attendanceSettingModal'));
			if (modal) modal.hide();

			showToast('출석현황 설정이 저장되었습니다.');
		});
	}
}

/**
 * 타임라인현황 설정 이벤트
 */
function initTimelineSettingEvents() {
	const settingBtn = document.getElementById('timelineSettingBtn');
	const saveBtn = document.getElementById('saveTimelineSettingBtn');

	if (settingBtn) {
		settingBtn.addEventListener('click', function(e) {
			e.preventDefault();
			const modal = new bootstrap.Modal(document.getElementById('timelineSettingModal'));
			modal.show();
		});
	}

	if (saveBtn) {
		saveBtn.addEventListener('click', function() {
			const selectedTypes = [];
			document.querySelectorAll('.timeline-type-check:checked').forEach(checkbox => {
				selectedTypes.push(checkbox.value);
			});

			saveTimelineSettings(selectedTypes);
			renderTimelineChart();

			const modal = bootstrap.Modal.getInstance(document.getElementById('timelineSettingModal'));
			if (modal) modal.hide();

			showToast('타임라인현황 설정이 저장되었습니다.');
		});
	}
}

/**
 * 메모현황 설정 이벤트
 */
function initMemoSettingEvents() {
	const settingBtn = document.getElementById('memoSettingBtn');
	const saveBtn = document.getElementById('saveMemoSettingBtn');

	if (settingBtn) {
		settingBtn.addEventListener('click', function(e) {
			e.preventDefault();
			const modal = new bootstrap.Modal(document.getElementById('memoSettingModal'));
			modal.show();
		});
	}

	if (saveBtn) {
		saveBtn.addEventListener('click', function() {
			const selectedTypes = [];
			document.querySelectorAll('.memo-type-check:checked').forEach(checkbox => {
				selectedTypes.push(checkbox.value);
			});

			saveMemoSettings(selectedTypes);
			renderMemoChart();

			const modal = bootstrap.Modal.getInstance(document.getElementById('memoSettingModal'));
			if (modal) modal.hide();

			showToast('메모현황 설정이 저장되었습니다.');
		});
	}
}

/**
 * 모든 차트 로드
 */
function loadAllCharts(orgId) {
	currentOrgId = orgId;

	loadMemberChart(orgId);
	loadAttendanceChart(orgId);
	loadTimelineChart(orgId);
	loadMemoChart(orgId);
}

// ========== 페이지 로드 시 초기화 ==========

document.addEventListener('DOMContentLoaded', function() {
	// 설정 이벤트 초기화
	initAttendanceSettingEvents();
	initTimelineSettingEvents();
	initMemoSettingEvents();
});
