'use strict';



// 회원현황 차트 초기화
function initMemberChart(labels, data) {
	const member_chart = document.getElementById('memberChart');
	if (!member_chart) return;

	new Chart(member_chart, {
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

// 출석현황 차트 관련
let attendanceChartInstance = null;
let attendanceRawData = [];
let attendanceTypes = [];

// localStorage에서 출석현황 설정 불러오기
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

// localStorage에 출석현황 설정 저장
function saveAttendanceSettings(selectedTypes) {
	localStorage.setItem('dashboard_attendance_settings', JSON.stringify(selectedTypes));
}

// 출석현황 차트 렌더링
function renderAttendanceChart() {
	if (!attendanceRawData || attendanceRawData.length === 0) {
		console.log('출석 데이터가 없습니다.');
		return;
	}

	const savedSettings = getAttendanceSettings();
	let selectedTypes = [];

	// 저장된 설정이 있으면 사용, 없으면 전체 선택
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
	const attendance_chart = document.getElementById('attendanceChart');
	if (!attendance_chart) return;

	attendanceChartInstance = new Chart(attendance_chart, {
		type: 'bar',
		data: {
			labels: labels,
			datasets: datasets
		},
		options: {
			responsive: true,
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

// 출석현황 데이터 설정
function setAttendanceData(rawData, types) {
	attendanceRawData = rawData;
	attendanceTypes = types;
}

// 출석현황 설정 모달 이벤트
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
			modal.hide();

			showToast('출석현황 설정이 저장되었습니다.');
		});
	}
}

// 페이지 로드 시 초기화
document.addEventListener('DOMContentLoaded', function() {
	// 출석현황 설정 이벤트 초기화
	initAttendanceSettingEvents();
});



// 타임라인현황 차트 관련
let timelineChartInstance = null;
let timelineRawData = [];
let timelineTypes = [];

// localStorage에서 타임라인현황 설정 불러오기
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

// localStorage에 타임라인현황 설정 저장
function saveTimelineSettings(selectedTypes) {
	localStorage.setItem('dashboard_timeline_settings', JSON.stringify(selectedTypes));
}

// 타임라인현황 차트 렌더링
function renderTimelineChart() {
	if (!timelineRawData || timelineRawData.length === 0) {
		console.log('타임라인 데이터가 없습니다.');
		return;
	}

	const savedSettings = getTimelineSettings();
	let selectedTypes = [];

	// 저장된 설정이 있으면 사용, 없으면 전체 선택
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
		'#FF9F40', '#FF6384', '#C9CBCF', '#4BC0C0', '#FF6384'
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
	const timeline_chart = document.getElementById('timelineChart');
	if (!timeline_chart) return;

	timelineChartInstance = new Chart(timeline_chart, {
		type: 'bar',
		data: {
			labels: labels,
			datasets: datasets
		},
		options: {
			responsive: true,
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

// 타임라인현황 데이터 설정
function setTimelineData(rawData, types) {
	timelineRawData = rawData;
	timelineTypes = types;
}

// 타임라인현황 설정 모달 이벤트
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
			modal.hide();

			showToast('타임라인현황 설정이 저장되었습니다.');
		});
	}
}

// 메모현황 차트 관련
let memoChartInstance = null;
let memoRawData = [];
let memoTypes = [];

// localStorage에서 메모현황 설정 불러오기
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

// localStorage에 메모현황 설정 저장
function saveMemoSettings(selectedTypes) {
	localStorage.setItem('dashboard_memo_settings', JSON.stringify(selectedTypes));
}

// 메모현황 차트 렌더링
function renderMemoChart() {
	if (!memoRawData || memoRawData.length === 0) {
		console.log('메모 데이터가 없습니다.');
		return;
	}

	const savedSettings = getMemoSettings();
	let selectedTypes = [];

	// 저장된 설정이 있으면 사용, 없으면 전체 선택
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
		'#FF9F40', '#FF6384', '#C9CBCF', '#4BC0C0', '#FF6384'
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
	const memo_chart = document.getElementById('memoChart');
	if (!memo_chart) return;

	memoChartInstance = new Chart(memo_chart, {
		type: 'bar',
		data: {
			labels: labels,
			datasets: datasets
		},
		options: {
			responsive: true,
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

// 메모현황 데이터 설정
function setMemoData(rawData, types) {
	memoRawData = rawData;
	memoTypes = types;
}

// 메모현황 설정 모달 이벤트
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
			modal.hide();

			showToast('메모현황 설정이 저장되었습니다.');
		});
	}
}

// 페이지 로드 시 초기화 (기존 DOMContentLoaded에 추가)
document.addEventListener('DOMContentLoaded', function() {
	// 출석현황 설정 이벤트 초기화
	initAttendanceSettingEvents();

	// 타임라인현황 설정 이벤트 초기화
	initTimelineSettingEvents();

	// 메모현황 설정 이벤트 초기화
	initMemoSettingEvents();
});
