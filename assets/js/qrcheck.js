'use strict';

// 전역 변수
var pastoralGrid = null;
var pastoralGridData = [];
var attendanceTypes = [];
// 전역 변수 섹션에 추가
var html5QrcodeScanner = null;
var currentFacingMode = "environment"; // 기본값: 후면 카메라


/**
 * 파일 위치: assets/js/qrcheck.js
 * 역할: QR출석 화면 JavaScript - 모드 제거 및 최적화된 버전
 */

$(document).ready(function() {
	// 페이지 로드 시 초기화
	initializePage();

	// 이벤트 바인딩
	bindEvents();
});

/**
 * 페이지 초기화
 */
function initializePage() {
	// 현재 주차 범위 설정
	var today = new Date();
	var formattedToday = formatDate(today);
	var currentWeekRange = getWeekRangeFromDate(formattedToday);
	updateWeekRange(currentWeekRange);
	$('.current-week').val(currentWeekRange);

	// 입력 상태 업데이트
	updateInputSearchState();

	// 첫 번째 출석 유형으로 기본값 설정
	setDefaultAttendanceType();

	// 페이지 로드 시 input-search에 포커스
	$('#input-search').focus();
}

/**
 * 이벤트 바인딩
 */

function bindEvents() {
	// 주차 이동 버튼
	$('.prev-week').off('click').on('click', navigateToPrevWeek);
	$('.next-week').off('click').on('click', navigateToNextWeek);

	// 검색 및 출석 체크
	$('#input-search').on('input', handleSearchInput);
	$('#input-search').on('keypress', handleSearchKeypress);
	$('#btn-submit').on('click', addAttStamp);

	// QR 카메라 토글 이벤트 추가
	$('#switchCheckCamera').on('change', handleQrCameraToggle);

	// QR 카메라 offcanvas 이벤트 바인딩 (카메라 전환 포함)
	bindQrCameraOffcanvasEvents();

	// 회원 카드 클릭
	$(document).on('click', '.member-card', handleMemberCardClick);

	// 출석 타입 드롭다운
	$('.dropdown-att-type').on('click', '.dropdown-item', handleAttendanceTypeSelect);

	// 그룹출석 버튼
	$(document).on('click', '.btn-area-attendance-memo', handlePastoralAttendanceClick);

	// 새 회원 추가
	$('#saveNewMember').click(handleSaveNewMember);

	// 모달 관련
	$('#saveAttendance').off('click').on('click', handleSaveAttendance);
	$('#initialize').off('click').on('click', handleInitializeAttendance);

	// 그룹출석 관련
	$('#saveAttendanceBtn').off('click').on('click', saveAttendanceAndMemoFromGrid);
	$('#loadLastWeekBtn').on('click', handleLoadLastWeek);

	// Offcanvas 정리
	$('#attendanceOffcanvas').on('hidden.bs.offcanvas', cleanupPastoralGrid);
}

/**
 * 회원 목록 로드 - 최적화된 버전 (출석 데이터 분리)
 */
function loadMembers(orgId, level, initialLoad = true) {
	$.ajax({
		url: '/qrcheck/get_members',
		method: 'POST',
		data: {
			org_id: orgId,
			level: level
		},
		dataType: 'json',
		success: function(members) {
			displayMembers(members);

			// 회원 목록 로드 후 출석 데이터 별도 로드
			if (initialLoad) {
				loadCurrentWeekAttendance(orgId);
			}
		},
		error: function(xhr, status, error) {
			console.error('회원 로드 실패:', error);
			handleAjaxError(xhr, '회원 목록을 불러오는 중 오류가 발생했습니다.');
		}
	});
}

/**
 * 현재 주차 출석 데이터만 로드
 */
function loadCurrentWeekAttendance(orgId) {
	var currentWeekRange = $('.current-week').val();
	var currentDate = getDateFromWeekRange(currentWeekRange);
	var startDate = getWeekStartDate(currentDate);
	var endDate = getWeekEndDate(currentDate);

	// 시작일에 해당하는 일요일 날짜 계산
	var sundayDate = getSundayOfWeek(startDate);

	$.ajax({
		url: '/qrcheck/get_attendance_data',
		method: 'POST',
		data: {
			org_id: orgId,
			start_date: sundayDate,
			end_date: sundayDate
		},
		dataType: 'json',
		success: function(attendanceData) {
			updateAttendanceStamps(attendanceData);
			updateBirthBg(startDate, endDate);
			updateAttTypeCountFromDOM();
		},
		error: function(xhr, status, error) {
			console.error('출석 데이터 로드 실패:', error);
		}
	});
}

/**
 * 출석 스탬프 업데이트 (분리된 함수)
 */
function updateAttendanceStamps(attendanceData) {
	// 기존 출석 스탬프 제거
	$('.member-card .att-stamp-warp .att-stamp').remove();

	$.each(attendanceData, function(memberIdx, attTypeNicknames) {
		var memberCard = $('.member-card[member-idx="' + memberIdx + '"]');
		var attStampsContainer = memberCard.find('.att-stamp-warp');

		if (attTypeNicknames) {
			var attStamps = attTypeNicknames.split(',').map(function(attTypeData) {
				var attTypeArr = attTypeData.split('|');
				var attTypeNickname = attTypeArr[0].trim();
				var attTypeIdx = attTypeArr[1].trim();
				var attTypeColor = attTypeArr[3].trim();
				return '<span class="att-stamp" data-att-type-idx="' + attTypeIdx + '" style="background-color: #' + attTypeColor + '">' + attTypeNickname + '</span>';
			}).join(' ');
			attStampsContainer.append(attStamps);
		}
	});
}

/**
 * 역할: 회원 목록 표시 - area_full_path 사용
 */
function displayMembers(members) {
	var memberList = $('.member-list .grid');
	memberList.empty();

	if (members.length === 0) {
		memberList.append('<div class="no-member">조회된 회원이 없습니다.</div>');
		return;
	}

	memberList.append('<div class="grid-sizer"></div>');

	// 소그룹별로 그룹화
	var membersByArea = groupMembersByArea(members);
	var sortedAreas = sortAreasByOrder(membersByArea);

	// 소그룹별 회원 표시
	$.each(sortedAreas, function(areaIndex, areaName) {
		var areaMembers = membersByArea[areaName];
		var areaIdx = areaMembers[0].area_idx || null;
		var areaFullPath = areaMembers[0].area_full_path || areaName;

		// 소그룹 헤더 추가 (전체 경로 표시)
		addAreaHeader(memberList, areaName, areaIdx, areaFullPath);

		// 회원 카드들 추가
		addMemberCards(memberList, areaMembers);

		// 구분선 추가 (마지막 소그룹이 아닌 경우)
		if (areaIndex < sortedAreas.length - 1) {
			memberList.append('<div class="grid-item grid-item--width100 area-divider"></div>');
		}
	});

	// 통계 업데이트
	updateMemberStatistics();
	updateAttTypeCountFromDOM();

	// Masonry 레이아웃 초기화
	initializeMasonry();
}

/**
 * 역할: 소그룹별 회원 그룹화 - 미분류 회원 제외
 */
function groupMembersByArea(members) {
	var membersByArea = {};
	$.each(members, function(index, member) {
		// 미분류 회원 제외 (area_idx가 없거나 area_name이 없는 경우)
		if (!member.area_idx || !member.area_name) {
			return true; // continue
		}

		var areaName = member.area_name;
		if (!membersByArea[areaName]) {
			membersByArea[areaName] = [];
		}
		membersByArea[areaName].push(member);
	});
	return membersByArea;
}



/**
 * 역할: 소그룹 정렬 - area_sort_path 사용
 */
function sortAreasByOrder(membersByArea) {
	// 각 area의 정보를 수집
	var areaInfoList = [];

	$.each(membersByArea, function(areaName, members) {
		if (members.length > 0) {
			var member = members[0];
			areaInfoList.push({
				name: areaName,
				sort_path: member.area_sort_path || '999'
			});
		}
	});

	// area_sort_path로 정렬
	areaInfoList.sort(function(a, b) {
		return a.sort_path.localeCompare(b.sort_path);
	});

	// 정렬된 area 이름 배열 반환
	return areaInfoList.map(function(item) {
		return item.name;
	});
}



/**
 * 소그룹 헤더 추가
 */
function addAreaHeader(memberList, areaName, areaIdx, areaFullPath) {
	var areaHeader = $('<div class="grid-item grid-item--width100 area-header">' +
		'<div class="area-title">' +
		'<span class="area-name">' + areaFullPath + '</span>' +
		'<div class="area-buttons">' +
		'<button class="btn btn-sm btn-outline-primary btn-area-attendance-memo" data-area-idx="' + areaIdx + '" data-area-name="' + areaName + '">' +
		'<i class="bi bi-clipboard-check"></i> 그룹출석' +
		'</button>' +
		'</div>' +
		'</div>' +
		'</div>');
	memberList.append(areaHeader);
}

/**
 * 회원 카드들 추가
 */
function addMemberCards(memberList, areaMembers) {
	$.each(areaMembers, function(memberIndex, member) {
		var memberCard = createMemberCard(member);
		memberList.append(memberCard);
	});
}

/**
 * 개별 회원 카드 생성
 */
function createMemberCard(member) {
	var memberCard = $('<div class="grid-item"><div class="member-card" member-idx="' + member.member_idx + '" data-birth="' + member.member_birth + '"><div class="member-wrap"><span class="member-name">' + member.member_name + '</span><span class="att-stamp-warp"></span></div></div></div>');

	// 사진 추가
	addMemberPhoto(memberCard, member);

	// 배지 추가
	addMemberBadges(memberCard, member);

	// 출석 스탬프 추가
	addAttendanceStamps(memberCard, member);

	return memberCard;
}

/**
 * 회원 사진 추가
 */
function addMemberPhoto(memberCard, member) {
	if (member.photo) {
		var photoUrl = '/uploads/member_photos/' + member.org_id + '/' + member.photo;
		memberCard.find('.member-card').prepend('<span class="photo" style="background: url(' + photoUrl + ') center center/cover"></span>');
	} else {
		memberCard.find('.member-card').prepend('<span class="photo"></span>');
	}
}

/**
 * 회원 배지 추가
 */
function addMemberBadges(memberCard, member) {


	if (member.position_name) {
		var badgeColor = getPositionBadgeColor(member.position_name);
		memberCard.find('.member-card .member-wrap .member-name').prepend('<span class="badge" style="background-color: ' + badgeColor + ';">' + member.position_name + '</span>');
	}


	if (member.area_idx) {
		memberCard.find('.member-card').addClass('area-idx-' + member.area_idx);
	}
}

/**
 * 역할: position_name에 따른 고정 배지 색상 반환
 */
function getPositionBadgeColor(positionName) {
	// 문자열을 해시값으로 변환하여 고정된 색상 생성
	var hash = 0;
	for (var i = 0; i < positionName.length; i++) {
		hash = positionName.charCodeAt(i) + ((hash << 5) - hash);
	}

	// 미리 정의된 색상 팔레트 (가독성 좋은 색상들)
	var colors = [
		'#f97316', // orange
		'#14b8a6', // teal
		'#22c55e', // green
		'#6366f1', // indigo
		'#8b5cf6', // violet
		'#ec4899', // pink
		'#ef4444', // red
		'#eab308', // yellow
		'#06b6d4', // cyan
		'#3b82f6', // blue
		'#a855f7', // purple
		'#f43f5e'  // rose
	];

	// 해시값을 색상 인덱스로 변환
	var index = Math.abs(hash) % colors.length;
	return colors[index];
}

/**
 * 출석 스탬프 추가 (기존 att_type_data 없이 동적 추가용)
 */
function addAttendanceStamps(memberCard, member) {
	// 이 함수는 서버에서 받은 att_type_data를 처리하는 것이므로
	// 새로운 loadCurrentWeekAttendance와 중복되지 않도록 유지
	if (member.att_type_data) {
		var attTypeData = member.att_type_data.split('|');
		var attStamps = attTypeData.map(function(attData) {
			var attDataArr = attData.split(',');
			var attType = attDataArr[0].trim();
			var attTypeIdx = attDataArr[1].trim();
			var attTypeCategoryIdx = attDataArr[2].trim();
			var attTypeColor = attDataArr[3].trim();

			return '<span class="att-stamp" data-att-type-idx="' + attTypeIdx + '" data-att-type-category-idx="' + attTypeCategoryIdx + '" style="background-color: #' + attTypeColor + '">' + attType + '</span>';
		}).join(' ');

		memberCard.find('.att-stamp-warp').append(attStamps);
	}
}

/**
 * 회원 통계 업데이트
 */
function updateMemberStatistics() {
	var totalMembers = $('.grid-item .member-card').length;
	var totalNewMembers = $('.member-card.new').length;
	var totalAttMembers = totalMembers - totalNewMembers;
	var totalLeaderMembers = $('.member-card.leader').length;

	$('.total-list dd').eq(0).text(totalMembers);
	$('.total-list dd').eq(1).text(totalAttMembers);
	$('.total-list dd').eq(2).text(totalLeaderMembers);
	$('.total-list dd').eq(3).text(totalNewMembers);
}

/**
 * 출석 타입별 개수 업데이트
 */
function updateAttTypeCountFromDOM() {
	var attTypeCount = {};

	$('.att-stamp').each(function() {
		var attType = $(this).text().trim();
		attTypeCount[attType] = (attTypeCount[attType] || 0) + 1;
	});

	var totalAttList = $('.total-att-list');
	totalAttList.empty();

	if (Object.keys(attTypeCount).length === 0) {
		totalAttList.append('<dt>출석</dt><dd>0</dd>');
		return;
	}

	// 출석 타입을 순서대로 정렬
	var sortedAttTypes = Object.keys(attTypeCount).sort(function(a, b) {
		var attTypeA = attendanceTypes.find(function(type) {
			return type.att_type_nickname === a;
		});
		var attTypeB = attendanceTypes.find(function(type) {
			return type.att_type_nickname === b;
		});

		if (!attTypeA || !attTypeB) return 0;
		return attTypeA.att_type_order - attTypeB.att_type_order;
	});

	sortedAttTypes.forEach(function(attType) {
		var attTypeInfo = attendanceTypes.find(function(type) {
			return type.att_type_nickname === attType;
		});
		var attTypeColor = attTypeInfo ? attTypeInfo.att_type_color : 'CB3227';
		totalAttList.append('<dt style="background-color: #' + attTypeColor + '">' + attType + '</dt><dd>' + attTypeCount[attType] + '</dd>');
	});
}

/**
 * 이벤트 핸들러들
 */
function navigateToPrevWeek() {
	var currentWeekRange = $('.current-week').val();
	var currentDate = getDateFromWeekRange(currentWeekRange);
	var prevDate = new Date(currentDate.setDate(currentDate.getDate() - 7));
	var prevWeekRange = getWeekRangeFromDate(prevDate);
	updateWeekRange(prevWeekRange);
}

function navigateToNextWeek() {
	var currentWeekRange = $('.current-week').val();
	var currentDate = getDateFromWeekRange(currentWeekRange);
	var nextDate = new Date(currentDate.setDate(currentDate.getDate() + 7));
	var nextWeekRange = getWeekRangeFromDate(nextDate);
	updateWeekRange(nextWeekRange);
}

function handleSearchInput() {
	var searchText = $(this).val().trim();
	if (/^\d+$/.test(searchText)) {
		return; // 숫자만 입력된 경우 검색 기능 비활성화
	}

	if (searchText.length >= 1) {
		searchMembers(searchText);
	} else {
		resetMemberList();
	}
}

function handleSearchKeypress(e) {
	if (e.which === 13) {
		addAttStamp();
	}
}

function handleMemberCardClick() {
	var memberIdx = $(this).attr('member-idx');
	var memberName = $(this).find('.member-name').text().trim();
	var modalTitle = memberName + ' 님의 출석';

	$('#selectedMemberIdx').val(memberIdx);
	$('#attendanceModalLabel').text(modalTitle);

	// 현재 선택된 주차 범위 가져오기
	var currentWeekRange = $('.current-week').text();
	var currentDate = getDateFromWeekRange(currentWeekRange);
	var startDate = getWeekStartDate(currentDate);
	var endDate = getWeekEndDate(currentDate);

	// 회원의 출석 데이터 불러오기
	loadMemberAttendance(memberIdx, startDate, endDate);
}

function handleAttendanceTypeSelect(e) {
	e.preventDefault();
	var attTypeName = $(this).text();
	var attTypeIdx = $(this).data('att-type-idx');
	$('#dropdown-toggle-att-type').text(attTypeName).data('att-type-idx', attTypeIdx);
}

function handlePastoralAttendanceClick() {
	var areaIdx = $(this).data('area-idx');
	var areaName = $(this).data('area-name');

	if (!areaIdx) {
		showToast('소그룹 정보를 찾을 수 없습니다.', 'error');
		return;
	}

	var orgId = getCookie('activeOrg');
	loadAreaMembersForDetailedManagement(areaIdx, areaName, orgId);
}

function handleSaveNewMember() {
	var member_name = $('#member_name').val();
	var area_idx = $('#newMemberAreaIdx').val();
	var activeOrgId = getCookie('activeOrg');

	if (!member_name.trim()) {
		showToast('회원 이름을 입력해주세요.', 'warning');
		return;
	}

	if (!area_idx) {
		showToast('소그룹을 선택해주세요.', 'warning');
		return;
	}

	$.ajax({
		url: '/qrcheck/add_member',
		method: 'POST',
		data: {
			org_id: activeOrgId,
			member_name: member_name,
			area_idx: area_idx
		},
		dataType: 'json',
		success: function(response) {
			if (response.status == 'success') {
				$('#newMemberModal').modal('hide');
				$('#member_name').val('');
				$('#newMemberAreaIdx').val($('#newMemberAreaIdx option:first').val());

				// 회원 목록만 업데이트 (출석 데이터는 그대로 유지)
				loadMembers(activeOrgId, userLevel, false);
				showToast('새 회원이 추가되었습니다.', '회원 추가 완료');
			} else {
				showToast(response.message || '회원 추가에 실패했습니다.', 'error');
			}
		},
		error: function(xhr, status, error) {
			handleAjaxError(xhr, '회원 추가 중 오류가 발생했습니다.');
		}
	});
}

function handleSaveAttendance() {
	var memberIdx = $('#selectedMemberIdx').val();
	var attendanceData = [];
	var activeOrgId = getCookie('activeOrg');

	var currentWeekRange = $('.current-week').text();
	var currentDate = getDateFromWeekRange(currentWeekRange);
	var startDate = getWeekStartDate(currentDate);
	var endDate = getWeekEndDate(currentDate);

	var today = new Date();
	var formattedToday = formatDate(today);
	var attDate = (formattedToday >= startDate && formattedToday <= endDate) ? formattedToday : startDate;

	// 체크된 모든 checkbox의 값을 수집
	$('#attendanceTypes input[type="checkbox"]:checked').each(function() {
		var selectedValue = $(this).val();
		if (selectedValue) {
			attendanceData.push(selectedValue);
		}
	});

	var requestData = {
		member_idx: memberIdx,
		attendance_data: JSON.stringify(attendanceData),
		org_id: activeOrgId,
		att_date: attDate,
		start_date: startDate,
		end_date: endDate
	};

	$.ajax({
		url: '/qrcheck/save_attendance',
		method: 'POST',
		data: requestData,
		dataType: 'json',
		success: function(response) {
			if (response.status === 'success') {
				$('#attendanceModal').modal('hide');

				// 회원 데이터 다시 로드하여 최신 출석 정보 반영
				loadMembers(activeOrgId, userLevel, startDate, endDate, false);

				$('#attendanceModal').on('hidden.bs.modal', function () {
					$('#input-search').focus();
					$(this).off('hidden.bs.modal');
				});

				initializeMasonry();
			} else {
				showToast('출석 정보 저장에 실패했습니다.', 'error');
			}
		},
		error: function(xhr, status, error) {
			console.error('AJAX 요청 실패:', {status: status, error: error, responseText: xhr.responseText});
			showToast('출석 정보 저장 중 오류가 발생했습니다.', 'error');
		}
	});
}

function handleInitializeAttendance() {
	$('#attendanceTypes input[type="checkbox"]').prop('checked', false);
}

function handleLoadLastWeek() {
	var memberIdx = $(this).data('member-idx');
	var orgId = getCookie('activeOrg');
	var areaIdx = $(this).data('area-idx');
	var memberName = $('#attendanceOffcanvasLabel').text().split(' ')[0];

	loadLastWeekData(memberIdx, orgId, areaIdx, memberName);
}

/**
 * QR 출석 체크 추가
 */
function addAttStamp() {
	var memberIdx = $('#input-search').val().trim();
	if (!/^\d+$/.test(memberIdx)) {
		$('#input-search').val('').focus();
		return;
	}

	var attTypeIdx = $('#dropdown-toggle-att-type').data('att-type-idx');
	var attTypeNickname = $('.dropdown-att-type .dropdown-item[data-att-type-idx="' + attTypeIdx + '"]').data('att-type-nickname');

	var memberCard = $('.member-card[member-idx="' + memberIdx + '"]');
	if (memberCard.length > 0) {
		var existingAttStamp = memberCard.find('.att-stamp[data-att-type-idx="' + attTypeIdx + '"]');
		if (existingAttStamp.length > 0) {
			playNoSound();
			showToast('이미 출석체크 하였습니다.', 'warning');
			$('#input-search').val('').focus();
			return;
		}

		var attStamp = '<span class="att-stamp" data-att-type-idx="' + attTypeIdx + '">' + attTypeNickname + '</span>';
		memberCard.find('.att-stamp-warp').append(attStamp);

		// 서버에 출석 정보 저장
		saveAttendance(memberIdx, attTypeIdx);

		// 토스트 띄우기
		attComplete(memberIdx);

		// 생일 여부 확인 후 사운드 재생
		var isBirthday = memberCard.hasClass('birth');
		if (isBirthday) {
			playBirthSound();
		} else {
			playOkSound();
		}
	} else {
		playNoSound();
		showToast('등록된 회원이 아닙니다.', 'error');
	}

	$('#input-search').val('').focus();
}

/**
 * 출석 정보 서버 저장
 */
function saveAttendance(memberIdx, attTypeIdx, selectedValue) {
	var activeOrgId = getCookie('activeOrg');
	var today = new Date();
	var attDate = formatDate(today);

	// 해당 member-card로 스크롤 이동 및 'now' 클래스 추가
	var memberCard = $('.member-card[member-idx="' + memberIdx + '"]');
	if (memberCard.length > 0) {
		$('.member-card').removeClass('now');
		memberCard.addClass('now');

		$('html, body').animate({
			scrollTop: memberCard.offset().top - 100
		}, 500);
	}

	$.ajax({
		url: '/qrcheck/save_single_attendance',
		method: 'POST',
		data: {
			member_idx: memberIdx,
			att_type_idx: attTypeIdx,
			org_id: activeOrgId,
			att_date: attDate,
			selected_value: selectedValue
		},
		dataType: 'json',
		success: function(response) {
			if (response.status === 'success') {
				// 기존에 추가된 스탬프에 배경색 적용
				applyStampBackgroundColor(memberIdx, attTypeIdx);

				// 출석 타입별 개수 업데이트
				updateAttTypeCountFromDOM();
			}
		}
	});
}


/**
 * 역할: 기존 스탬프에 배경색 적용 (중복 생성 방지)
 */
function applyStampBackgroundColor(memberIdx, attTypeIdx) {
	var memberCard = $('.member-card[member-idx="' + memberIdx + '"]');
	if (memberCard.length === 0) return;

	// 해당 출석 타입 정보 찾기
	var attTypeInfo = attendanceTypes.find(function(type) {
		return type.att_type_idx == attTypeIdx;
	});

	if (!attTypeInfo) return;

	// 기존에 추가된 스탬프 중 배경색이 없는 것에만 스타일 적용
	var existingStamp = memberCard.find('.att-stamp[data-att-type-idx="' + attTypeIdx + '"]').filter(function() {
		return !$(this).attr('style') || $(this).attr('style').indexOf('background-color') === -1;
	}).first();

	if (existingStamp.length > 0) {
		existingStamp.css('background-color', '#' + attTypeInfo.att_type_color);
	}
}

/**
 * 역할: UI에서 출석 스탬프 업데이트 (서버 호출 없이)
 */
function updateAttendanceStampInUI(memberIdx, attTypeIdx) {
	var memberCard = $('.member-card[member-idx="' + memberIdx + '"]');
	if (memberCard.length === 0) return;

	// 해당 출석 타입 정보 찾기
	var attTypeInfo = attendanceTypes.find(function(type) {
		return type.att_type_idx == attTypeIdx;
	});

	if (!attTypeInfo) return;

	// 새로운 출석 스탬프 생성
	var attStamp = '<span class="att-stamp" data-att-type-idx="' + attTypeIdx + '" style="background-color: #' + attTypeInfo.att_type_color + '">' + attTypeInfo.att_type_nickname + '</span>';

	// 출석 스탬프 컨테이너에 추가
	memberCard.find('.att-stamp-warp').append(attStamp);
}



/**
 * 주차 범위 업데이트
 */
function updateWeekRange(weekRange) {
	$('.current-week').val(weekRange);

	var currentDate = getDateFromWeekRange(weekRange);
	var startDate = getWeekStartDate(currentDate);
	var endDate = getWeekEndDate(currentDate);
	var activeOrgId = getCookie('activeOrg');

	if (activeOrgId) {
		updateBirthBg(startDate, endDate);
		// 출석 데이터만 다시 로드 (회원 목록은 그대로 유지)
		loadCurrentWeekAttendance(activeOrgId);
	}
	updateInputSearchState();
}

/**
 * 입력 상태 업데이트
 */
function updateInputSearchState() {
	var currentWeekRange = $('.current-week').val();
	var currentDate = getDateFromWeekRange(currentWeekRange);
	var startDate = getWeekStartDate(currentDate);
	var endDate = getWeekEndDate(currentDate);
	var today = new Date();
	var formattedToday = formatDate(today);

	if (formattedToday >= startDate && formattedToday <= endDate) {
		$('#input-search').prop('disabled', false).val('').attr('placeholder', '이름검색 또는 QR체크!');
	} else {
		$('#input-search').prop('disabled', true).val('검색중...').attr('placeholder', '');
		resetMemberList();
	}
}

/**
 * 검색 기능
 */
function searchMembers(searchText) {
	$('.grid-item').each(function() {
		var memberName = $(this).find('.member-wrap').text().trim();
		$(this).toggle(memberName.includes(searchText));
	});

	// Masonry 레이아웃 업데이트
	setTimeout(function() {
		if ($('.grid').data('masonry')) {
			$('.grid').masonry('layout');
		}
	}, 50);
}

function resetMemberList() {
	$('.grid-item').show();

	// Masonry 레이아웃 업데이트
	setTimeout(function() {
		if ($('.grid').data('masonry')) {
			$('.grid').masonry('layout');
		}
	}, 50);
}

/**
 * 생일 배경 업데이트
 */
function updateBirthBg(startDate, endDate) {
	$('.member-card').each(function() {
		var memberCard = $(this);
		var memberBirthDate = memberCard.data('birth');

		if (memberBirthDate) {
			var currentYear = new Date().getFullYear();
			var birthDate = new Date(memberBirthDate);
			var birthMonth = String(birthDate.getMonth() + 1).padStart(2, '0');
			var birthDay = String(birthDate.getDate()).padStart(2, '0');

			var formattedBirthDate = currentYear + '.' + birthMonth + '.' + birthDay;

			memberCard.toggleClass('birth', formattedBirthDate >= startDate && formattedBirthDate <= endDate);
		}
	});
}

/**
 * 기본 출석 타입 설정
 */
function setDefaultAttendanceType() {
	var firstAttType = $('.dropdown-att-type .dropdown-item:first');
	if (firstAttType.length > 0) {
		var attTypeName = firstAttType.text();
		var attTypeIdx = firstAttType.data('att-type-idx');
		$('#dropdown-toggle-att-type').text(attTypeName).data('att-type-idx', attTypeIdx);
	}
}

/**
 * 회원 출석 데이터 로드
 */
function loadMemberAttendance(memberIdx, startDate, endDate) {
	$.ajax({
		url: '/qrcheck/get_member_attendance',
		method: 'POST',
		data: {
			member_idx: memberIdx,
			start_date: startDate,
			end_date: endDate
		},
		dataType: 'json',
		success: function(response) {
			var attendanceData = response.attendance_data;
			loadAttendanceTypes(memberIdx, attendanceData);
		}
	});
}

/**
 * 출석 타입 로드
 */
function loadAttendanceTypes(memberIdx, attendanceData) {
	if (!attendanceTypes || attendanceTypes.length === 0) {
		console.error('attendanceTypes가 비어있습니다.');
		return;
	}

	var html = '';

	for (var i = 0; i < attendanceTypes.length; i++) {
		var type = attendanceTypes[i];

		// 현재 선택된 주차 범위 내의 모든 날짜에 대해 출석 정보 확인
		var isChecked = false;
		for (var date in attendanceData) {
			if (attendanceData.hasOwnProperty(date) && attendanceData[date].includes(type.att_type_idx.toString())) {
				isChecked = true;
				break;
			}
		}

		html += '<div class="form-check form-check-inline mb-2 ps-0 me-2">';
		html += '<input type="checkbox" class="btn-check" id="att_type_' + type.att_type_idx + '" value="' + type.att_type_idx + '" ' + (isChecked ? 'checked' : '') + ' autocomplete="off">';
		html += '<label class="btn btn-outline-primary" for="att_type_' + type.att_type_idx + '">' + type.att_type_name + '</label>';
		html += '</div>';
	}

	$('#attendanceTypes').html(html);
	$('#attendanceModal').modal('show');
}



/**
 * 그룹출석 pqgrid 초기화 (개선된 버전)
 */
function initializePastoralGrid() {
	if (pastoralGrid) {
		return;
	}

	var colModel = [
		{
			title: "이름",
			dataIndx: "member_name",
			width: 100,
			minWidth: 100,
			maxWidth: 100,
			editable: false,
			align: "center",
			frozen: true,
			resizable: false
		},
		{
			title: "한줄메모",
			dataIndx: "memo_content",
			width: 180,
			minWidth: 120,
			maxWidth: 250,
			editable: true,
			align: "left",
			resizable: true,
			editor: {
				type: "textbox",
				attr: "placeholder='메모 입력...'"
			}
		}
	];

	// 출석타입별 컬럼 동적 추가
	if (attendanceTypes && attendanceTypes.length > 0) {
		attendanceTypes.forEach(function(attType) {
			var columnConfig = {
				title: attType.att_type_name,
				dataIndx: "att_type_" + attType.att_type_idx,
				width: 50,
				minWidth: 20,
				maxWidth: 80,
				align: "center",
				resizable: true
			};

			if (attType.att_type_input === 'check') {
				columnConfig.editable = false;
				columnConfig.render = function(ui) {
					const isChecked = ui.cellData === true || ui.cellData === 'Y' || ui.cellData === 1;
					const checkedAttr = isChecked ? 'checked' : '';

					// pq-row-indx 속성 사용하여 정확한 행 인덱스 전달
					const checkboxId = 'checkbox_' + ui.rowIndx + '_' + attType.att_type_idx;

					return '<div style="text-align: center; padding: 8px;">' +
						'<input type="checkbox" ' + checkedAttr + ' ' +
						'id="' + checkboxId + '" ' +
						'class="pastoral-checkbox" ' +
						'data-row-indx="' + ui.rowIndx + '" ' +
						'data-att-type-idx="' + attType.att_type_idx + '" ' +
						'data-member-idx="' + ui.rowData.member_idx + '" ' +
						'style="transform: scale(1.3); cursor: pointer; pointer-events: all;">' +
						'</div>';
				};
			} else {
				columnConfig.editable = true;
				var options = [];
				options.push({value: '', text: '-'});
				options.push({
					value: attType.att_type_idx,
					text: attType.att_type_nickname
				});

				columnConfig.editor = {
					type: "select",
					options: options
				};
				columnConfig.render = function(ui) {
					if (!ui.cellData) return '-';
					var selectedType = attendanceTypes.find(function(type) {
						return type.att_type_idx == ui.cellData;
					});
					return selectedType ? selectedType.att_type_nickname : '-';
				};
			}

			colModel.push(columnConfig);
		});
	}

	var gridOptions = {
		width: "100%",
		dataModel: {
			data: []
		},
		colModel: colModel,
		selectionModel: {
			type: 'cell'
		},
		strNoRows: '출석 정보가 없습니다',
		scrollModel: {
			autoFit: false,
			horizontal: true,
			vertical: true
		},
		freezeCols: 1,
		numberCell: { show: false },
		title: false,
		resizable: false,
		sortable: false,
		wrap: false,
		columnBorders: true,
		editable: true,
		editModel: {
			clicksToEdit: 2,
			saveKey: $.ui.keyCode.ENTER
		},
		complete: function() {
			// 그리드 렌더링 완료 후 이벤트 바인딩 (지연 실행으로 안정성 확보)
			setTimeout(function() {
				bindPastoralCheckboxEvents();
			}, 200);
		}
	};

	pastoralGrid = $("#pastoralAttendanceGrid").pqGrid(gridOptions);
}

/**
 * 그룹출석 체크박스 이벤트 바인딩 - PC/모바일 통합 개선
 */
function bindPastoralCheckboxEvents() {
	// 기존 이벤트 제거
	$(document).off('change.pastoral click.pastoral touchend.pastoral', '.pastoral-checkbox');

	// 다중 이벤트 바인딩으로 모바일 호환성 개선
	$(document).on('change.pastoral click.pastoral touchend.pastoral', '.pastoral-checkbox', function(e) {
		e.stopPropagation();

		const $checkbox = $(this);
		const $row = $checkbox.closest('tr');

		// pqgrid 속성에서 정확한 행 인덱스 가져오기
		let rowIndx = parseInt($row.attr('pq-row-indx'));

		// pq-row-indx가 없으면 data 속성 사용
		if (isNaN(rowIndx)) {
			rowIndx = parseInt($checkbox.data('row-indx'));
		}

		const memberIdx = $checkbox.data('member-idx');
		const attTypeIdx = $checkbox.data('att-type-idx');

		// 행 인덱스 유효성 검증
		if (isNaN(rowIndx) || rowIndx < 0) {
			console.error('유효하지 않은 행 인덱스:', rowIndx);
			return;
		}

		// PC/모바일 이벤트 타입별 처리
		let shouldProcess = false;
		let checked = $checkbox.is(':checked');

		if (e.type === 'change') {
			// PC: change 이벤트
			shouldProcess = true;
		} else if (e.type === 'touchend') {
			// 모바일: touchend 이벤트
			e.preventDefault();
			shouldProcess = true;
			checked = !checked; // 터치 이벤트는 상태를 토글
		} else if (e.type === 'click' && !e.originalEvent?.isTrusted) {
			// 프로그래매틱 클릭은 무시
			return;
		}

		if (shouldProcess) {
			// 체크박스 상태 직접 변경 (터치 이벤트용)
			if (e.type === 'touchend') {
				setTimeout(function() {
					$checkbox.prop('checked', checked);
				}, 10);
			}

			try {
				var gridData = pastoralGrid.pqGrid("option", "dataModel.data");
				if (gridData && gridData[rowIndx]) {
					gridData[rowIndx]["att_type_" + attTypeIdx] = checked;
					pastoralGrid.pqGrid("option", "dataModel.data", gridData);

					// 그리드 새로고침 (모바일에서 UI 업데이트 보장)
					setTimeout(function() {
						pastoralGrid.pqGrid("refreshDataAndView");
					}, 50);
				}
			} catch (error) {
				console.error('체크박스 데이터 업데이트 실패:', error);
			}
		}
	});

	console.log('Pastoral checkbox events bound for PC/Mobile');
}

/**
 * 그룹출석 pqgrid용 데이터 준비
 */
function preparePastoralGridData(members, attTypes) {
	var gridData = [];

	if (!members || members.length === 0) {
		return gridData;
	}

	members.forEach(function(member) {
		var rowData = {
			member_idx: member.member_idx,
			member_name: member.member_name,
			memo_content: member.memo_content || ''
		};

		if (attTypes && attTypes.length > 0) {
			attTypes.forEach(function(attType) {
				var dataIndx = "att_type_" + attType.att_type_idx;
				var selectedValue = null;

				if (member.attendance) {
					var attendanceArr = member.attendance.split('|');
					attendanceArr.forEach(function(attendance) {
						var attData = attendance.split(',');
						var attTypeIdx = attData[0].trim();

						if (attTypeIdx == attType.att_type_idx) {
							if (attType.att_type_input === 'check') {
								selectedValue = true;
							} else {
								selectedValue = attTypeIdx;
							}
						}
					});
				}

				if (attType.att_type_input === 'check') {
					rowData[dataIndx] = selectedValue === true;
				} else {
					rowData[dataIndx] = selectedValue || '';
				}
			});
		}

		gridData.push(rowData);
	});

	return gridData;
}

/**
 * 그룹출석 영역 회원 로드
 */
function loadAreaMembersForDetailedManagement(areaIdx, areaName, orgId) {
	var currentWeekRange = $('.current-week').text();
	var currentDate = getDateFromWeekRange(currentWeekRange);
	var startDate = getWeekStartDate(currentDate);
	var endDate = getWeekEndDate(currentDate);

	$.ajax({
		url: '/qrcheck/get_same_members',
		method: 'POST',
		data: {
			member_idx: 0,
			org_id: orgId,
			area_idx: areaIdx,
			start_date: startDate,
			end_date: endDate
		},
		dataType: 'json',
		success: function(response) {
			if (response.status === 'success') {
				var members = response.members;
				var attTypes = response.att_types;

				var offcanvasTitle = $('#attendanceOffcanvasLabel');
				var offcanvasBody = $('#attendanceOffcanvas .offcanvas-body');

				offcanvasTitle.text(areaName + ' 그룹출석');
				offcanvasBody.empty();

				var gridHtml = '<div id="pastoralAttendanceGrid"></div>';
				offcanvasBody.append(gridHtml);

				initializePastoralGrid();

				pastoralGridData = preparePastoralGridData(members, attTypes);

				if (pastoralGrid) {
					pastoralGrid.pqGrid("option", "dataModel.data", pastoralGridData);
					pastoralGrid.pqGrid("refreshDataAndView");
				}

				$('#saveAttendanceBtn').text('저장');

				$('#loadLastWeekBtn')
					.data('member-idx', 0)
					.data('area-idx', areaIdx)
					.data('area-name', areaName);

				$('#attendanceOffcanvas').offcanvas('show');
			} else {
				var errorMessage = response.message || '회원 정보를 가져오는데 실패했습니다.';
				showToast(errorMessage, 'error');
			}
		},
		error: function(xhr, status, error) {
			handleAjaxError(xhr, '회원 정보 조회 중 오류가 발생했습니다.');
		}
	});
}

/**
 * pqgrid에서 출석+메모 데이터 수집 및 저장
 */
function saveAttendanceAndMemoFromGrid() {
	if (!pastoralGrid) {
		console.error('pqgrid가 초기화되지 않았습니다.');
		return;
	}

	var currentWeekRange = $('.current-week').text();
	var currentDate = getDateFromWeekRange(currentWeekRange);
	var startDate = getWeekStartDate(currentDate);
	var endDate = getWeekEndDate(currentDate);
	var activeOrgId = getCookie('activeOrg');

	var today = new Date();
	var formattedToday = formatDate(today);
	var attDate = (formattedToday >= startDate && formattedToday <= endDate) ? formattedToday : startDate;

	var sundayDate = getSundayOfWeek(attDate);
	var gridData = pastoralGrid.pqGrid("option", "dataModel.data");

	if (!gridData || gridData.length === 0) {
		showToast('저장할 데이터가 없습니다.', 'warning');
		return;
	}

	var attendanceData = [];
	var memoData = [];

	gridData.forEach(function(row) {
		var memberIdx = row.member_idx;
		var memoContent = row.memo_content ? row.memo_content.trim() : '';

		var hasAttendanceData = false;

		if (attendanceTypes && attendanceTypes.length > 0) {
			attendanceTypes.forEach(function(attType) {
				var dataIndx = "att_type_" + attType.att_type_idx;
				var cellValue = row[dataIndx];

				var hasValue = false;
				if (attType.att_type_input === 'check') {
					hasValue = cellValue === true || cellValue === 'true';
				} else {
					hasValue = cellValue && cellValue.trim() !== '';
				}

				if (hasValue) {
					attendanceData.push({
						member_idx: memberIdx,
						att_type_idx: attType.att_type_idx,
						att_date: sundayDate,
						has_data: true
					});
					hasAttendanceData = true;
				}
			});
		}

		if (!hasAttendanceData) {
			attendanceData.push({
				member_idx: memberIdx,
				att_type_idx: null,
				att_date: sundayDate,
				has_data: false
			});
		}

		memoData.push({
			member_idx: memberIdx,
			memo_content: memoContent,
			memo_date: sundayDate,
			org_id: activeOrgId
		});
	});

	var $saveBtn = $('#saveAttendanceBtn');
	var originalText = $saveBtn.text();
	$saveBtn.prop('disabled', true).text('저장 중...');

	$.ajax({
		url: '/qrcheck/save_attendance_data_with_cleanup',
		method: 'POST',
		data: {
			attendance_data: JSON.stringify(attendanceData),
			org_id: activeOrgId,
			start_date: startDate,
			end_date: endDate,
			att_date: sundayDate
		},
		dataType: 'json',
		success: function(response) {
			if (response.status === 'success') {
				saveMemoDataFromGrid(memoData, activeOrgId, startDate, endDate, $saveBtn, originalText);
			} else {
				$saveBtn.prop('disabled', false).text(originalText);
				showToast('출석 정보 저장에 실패했습니다.', 'error');
			}
		},
		error: function(xhr, status, error) {
			console.error('출석 저장 오류:', xhr.responseText);
			$saveBtn.prop('disabled', false).text(originalText);
			showToast('출석 정보 저장 중 오류가 발생했습니다.', 'error');
		}
	});
}

/**
 * 메모 데이터 저장
 */
function saveMemoDataFromGrid(memoData, activeOrgId, startDate, endDate, $saveBtn, originalText) {
	if (memoData.length === 0) {
		$saveBtn.prop('disabled', false).text(originalText);
		showToast('저장할 데이터가 없습니다.', 'success');
		return;
	}

	$.ajax({
		url: '/qrcheck/save_memo_data',
		method: 'POST',
		data: {
			memo_data: JSON.stringify(memoData),
			org_id: activeOrgId
		},
		dataType: 'json',
		success: function(response) {
			if (response.status === 'success') {
				$('#attendanceOffcanvas').offcanvas('hide');

				// 회원 데이터 다시 로드
				loadMembers(activeOrgId, userLevel, startDate, endDate, false);

				var areaName = $('#attendanceOffcanvasLabel').text().split(' ')[0];
				showToast(areaName + ' 목장의 출석을 성공적으로 저장했습니다.', 'success');
			} else {
				showToast(response.message || '메모 저장에 실패했습니다.', 'error');
			}
		},
		error: function(xhr, status, error) {
			console.error('메모 저장 오류:', xhr.responseText);
			showToast('메모 저장 중 오류가 발생했습니다.', 'error');
		},
		complete: function() {
			$saveBtn.prop('disabled', false).text(originalText);
		}
	});
}

/**
 * 지난주 출석 데이터 로드
 */
function loadLastWeekData(memberIdx, orgId, areaIdx, memberName) {
	if (!orgId || !areaIdx) {
		showToast('필수 정보가 누락되었습니다.', 'error');
		return;
	}

	var currentWeekRange = $('.current-week').val();
	if (!currentWeekRange) {
		showToast('현재 주차 정보를 가져올 수 없습니다.', 'error');
		return;
	}

	var currentDate = getDateFromWeekRange(currentWeekRange);
	var currentSunday = getSunday(new Date(currentDate));
	var lastWeekSunday = new Date(currentSunday);
	lastWeekSunday.setDate(lastWeekSunday.getDate() - 7);
	var lastWeekSundayFormatted = formatDate(lastWeekSunday);

	var requestData = {
		member_idx: memberIdx || 0,
		org_id: orgId,
		area_idx: areaIdx,
		att_date: lastWeekSundayFormatted
	};

	$.ajax({
		url: '/qrcheck/get_last_week_attendance',
		method: 'POST',
		data: requestData,
		dataType: 'json',
		success: function(response) {
			if (response.status === 'success') {
				var attendanceData = response.attendance_data;
				var attTypes = response.att_types;

				if (Object.keys(attendanceData).length === 0) {
					showToast('지난주 출석 데이터가 없습니다.', 'warning');
				} else {
					updateAttendanceSelectboxForGrid(attendanceData, attTypes);
					showToast(memberName + ' 목장의 지난 주 정보를 불러왔습니다.', 'success');
				}
			} else {
				var errorMessage = response.message || '지난주 데이터를 가져오는데 실패했습니다.';
				showToast(errorMessage, 'error');
			}
		},
		error: function(xhr, status, error) {
			handleAjaxError(xhr, '지난주 데이터 조회 중 오류가 발생했습니다.');
		}
	});
}

/**
 * 지난주 출석 데이터 적용
 */
function updateAttendanceSelectboxForGrid(attendanceData, attTypes) {
	if (!pastoralGrid || !attendanceData || !attTypes) {
		return;
	}

	try {
		var gridData = pastoralGrid.pqGrid("option", "dataModel.data");

		if (!gridData || gridData.length === 0) {
			return;
		}

		gridData.forEach(function(row, index) {
			var memberIdx = row.member_idx;
			var memberAttendance = attendanceData[memberIdx] || [];

			if (attTypes && attTypes.length > 0) {
				attTypes.forEach(function(attType) {
					var dataIndx = "att_type_" + attType.att_type_idx;
					var hasAttendance = false;

					for (var i = 0; i < memberAttendance.length; i++) {
						var attTypeIdx = parseInt(memberAttendance[i].trim());
						if (attTypeIdx === parseInt(attType.att_type_idx)) {
							hasAttendance = true;
							break;
						}
					}

					if (attType.att_type_input === 'check') {
						gridData[index][dataIndx] = hasAttendance;
					} else {
						gridData[index][dataIndx] = hasAttendance ? attType.att_type_idx.toString() : '';
					}
				});
			}
		});

		pastoralGrid.pqGrid("option", "dataModel.data", gridData);
		pastoralGrid.pqGrid("refreshDataAndView");

	} catch (error) {
		console.error('지난주 출석 데이터 적용 실패:', error);
	}
}

/**
 * pqgrid 정리
 */
function cleanupPastoralGrid() {
	if (pastoralGrid) {
		try {
			pastoralGrid.pqGrid("destroy");
			pastoralGrid = null;
			pastoralGridData = [];
		} catch (error) {
			console.error('pqgrid 정리 실패:', error);
		}
	}
}

/**
 * 유틸리티 함수들
 */

/**
 * Masonry 초기화
 */
function initializeMasonry() {
	var $grid = $('.grid');

	// 기존 masonry 인스턴스 제거
	if ($grid.data('masonry')) {
		$grid.masonry('destroy');
	}

	// DOM 렌더링 완료를 위한 지연 실행
	setTimeout(function() {
		// Masonry 초기화
		$grid.masonry({
			itemSelector: '.grid-item',
			columnWidth: '.grid-sizer',
			stamp: '.stamp',
			percentPosition: true,
			transitionDuration: '0.3s'
		});

		// 이미지 로딩 완료 후 재배치
		if (typeof $.fn.imagesLoaded !== 'undefined') {
			$grid.imagesLoaded(function() {
				$grid.masonry('layout');
			});
		}

		// 추가 안전장치: 한번 더 재배치
		setTimeout(function() {
			$grid.masonry('layout');
		}, 200);

		// 한 번 더 안전장치 (총 3번 시도)
		setTimeout(function() {
			$grid.masonry('layout');
		}, 500);
	}, 100);
}

/**
 * 출석 완료 토스트
 */
function attComplete(memberIdx) {
	// 회원 카드에서 직접 이름 가져오기 (API 호출 제거)
	var memberCard = $('.member-card[member-idx="' + memberIdx + '"]');
	if (memberCard.length > 0) {
		var memberName = memberCard.find('.member-name').text().trim();
		var welcomeMessage = memberName + '님 환영합니다!';

		showToast(welcomeMessage, 'success');
	}
}

/**
 * 사운드 재생
 */
function playOkSound() {
	var audio = document.getElementById('sound-ok');
	if (audio) audio.play();
}

function playNoSound() {
	var audio = document.getElementById('sound-no');
	if (audio) audio.play();
}

function playBirthSound() {
	var audio = document.getElementById('sound-birth');
	if (audio) audio.play();
}

/**
 * 쿠키 관련
 */
function setCookie(name, value, days) {
	var expires = "";
	if (days) {
		var date = new Date();
		date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
		expires = "; expires=" + date.toUTCString();
	}
	document.cookie = name + "=" + (value || "") + expires + "; path=/";
}

function getCookie(name) {
	var nameEQ = name + "=";
	var ca = document.cookie.split(';');
	for (var i = 0; i < ca.length; i++) {
		var c = ca[i];
		while (c.charAt(0) == ' ') c = c.substring(1, c.length);
		if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length, c.length);
	}
	return null;
}

function deleteCookie(name) {
	document.cookie = name + '=; expires=Thu, 01 Jan 1970 00:00:01 GMT; path=/;';
}

/**
 * 날짜 관련 유틸리티
 */
function formatDate(date) {
	if (!date || isNaN(date.getTime())) {
		console.warn('Invalid date for formatting:', date, '-> fallback to today');
		date = new Date();
	}

	try {
		var year = date.getFullYear();
		var month = String(date.getMonth() + 1).padStart(2, '0');
		var day = String(date.getDate()).padStart(2, '0');

		if (isNaN(year) || isNaN(month) || isNaN(day)) {
			console.error('Date formatting failed, using fallback');
			var fallback = new Date();
			return formatDate(fallback);
		}

		return `${year}.${month}.${day}`;
	} catch (error) {
		console.error('Error formatting date:', error);
		var fallback = new Date();
		var year = fallback.getFullYear();
		var month = String(fallback.getMonth() + 1).padStart(2, '0');
		var day = String(fallback.getDate()).padStart(2, '0');
		return `${year}.${month}.${day}`;
	}
}

function parseCompatibleDate(dateStr) {
	if (!dateStr) {
		return new Date();
	}

	var normalizedDate = dateStr.replace(/\./g, '-');
	var date = new Date(normalizedDate);

	if (isNaN(date.getTime())) {
		console.warn('Invalid date string:', dateStr, '-> fallback to today');
		return new Date();
	}

	return date;
}

function getSunday(date) {
	try {
		if (!date || isNaN(date.getTime())) {
			date = new Date();
		}

		var workingDate = new Date(date.getTime());
		var day = workingDate.getDay();
		var diff = workingDate.getDate() - day;
		var sunday = new Date(workingDate.setDate(diff));

		if (isNaN(sunday.getTime())) {
			console.error('Failed to calculate Sunday');
			return new Date();
		}

		return sunday;
	} catch (error) {
		console.error('Error getting Sunday:', error);
		return new Date();
	}
}

function getSundayOfWeek(date) {
	try {
		var dateObj;
		if (typeof date === 'string') {
			dateObj = parseCompatibleDate(date);
		} else if (date instanceof Date) {
			dateObj = new Date(date.getTime());
		} else {
			dateObj = new Date();
		}

		var day = dateObj.getDay();
		var diff = dateObj.getDate() - day;
		var sunday = new Date(dateObj.setDate(diff));

		return formatDate(sunday);
	} catch (error) {
		console.error('Error getting Sunday of week:', error);
		return formatDate(new Date());
	}
}

function getWeekStartDate(date) {
	try {
		var workingDate;

		if (!date) {
			workingDate = new Date();
		} else if (typeof date === 'string') {
			workingDate = parseCompatibleDate(date);
		} else if (date instanceof Date) {
			workingDate = new Date(date.getTime());
		} else {
			workingDate = new Date();
		}

		var day = workingDate.getDay();
		var diff = workingDate.getDate() - day;
		var startDate = new Date(workingDate.setDate(diff));

		return formatDate(startDate);
	} catch (error) {
		console.error('Error getting week start date:', error);
		return formatDate(new Date());
	}
}

function getWeekEndDate(date) {
	try {
		var workingDate;

		if (!date) {
			workingDate = new Date();
		} else if (typeof date === 'string') {
			workingDate = parseCompatibleDate(date);
		} else if (date instanceof Date) {
			workingDate = new Date(date.getTime());
		} else {
			workingDate = new Date();
		}

		var day = workingDate.getDay();
		var diff = workingDate.getDate() - day + 6;
		var endDate = new Date(workingDate.setDate(diff));

		return formatDate(endDate);
	} catch (error) {
		console.error('Error getting week end date:', error);
		return formatDate(new Date());
	}
}

function getDateFromWeekRange(weekRange) {
	if (!weekRange || typeof weekRange !== 'string') {
		console.warn('Invalid weekRange:', weekRange, '-> fallback to today');
		return new Date();
	}

	try {
		var parts = weekRange.split('~');
		if (parts.length < 2) {
			console.warn('Invalid weekRange format:', weekRange);
			return new Date();
		}

		var startDateStr = parts[0].trim()
			.replace('년', '')
			.replace('월', '')
			.replace('일', '')
			.replace(/\(\d+주차\)/, '')
			.trim();

		return parseCompatibleDate(startDateStr);
	} catch (error) {
		console.error('Error parsing weekRange:', weekRange, error);
		return new Date();
	}
}

function getWeekRangeFromDate(date) {
	try {
		var workingDate;

		if (!date) {
			workingDate = new Date();
		} else if (typeof date === 'string') {
			workingDate = parseCompatibleDate(date);
		} else if (date instanceof Date) {
			if (isNaN(date.getTime())) {
				console.warn('Invalid Date object:', date);
				workingDate = new Date();
			} else {
				workingDate = new Date(date.getTime());
			}
		} else {
			console.warn('Unknown date type:', typeof date, date);
			workingDate = new Date();
		}

		var sunday = getSunday(new Date(workingDate.getTime()));

		if (!sunday || isNaN(sunday.getTime())) {
			console.error('Failed to get Sunday date from:', workingDate);
			sunday = new Date();
		}

		var sundayTimestamp = sunday.getTime();
		var nextSundayTimestamp = sundayTimestamp + (7 * 24 * 60 * 60 * 1000);

		var startDate = formatDate(new Date(sundayTimestamp));
		var endDate = formatDate(new Date(nextSundayTimestamp - (24 * 60 * 60 * 1000)));

		return startDate + '~' + endDate;
	} catch (error) {
		console.error('Error creating week range:', error);
		var today = new Date();
		var sunday = getSunday(new Date(today.getTime()));
		var startDate = formatDate(sunday);
		var endDate = formatDate(new Date(sunday.getTime() + 6 * 24 * 60 * 60 * 1000));
		return startDate + '~' + endDate;
	}
}

/**
 * 에러 핸들링
 */
function handleAjaxError(xhr, defaultMessage) {
	var errorMessage = defaultMessage;

	if (xhr.status === 403 || (xhr.responseJSON && xhr.responseJSON.message)) {
		errorMessage = xhr.responseJSON ? xhr.responseJSON.message : '권한이 없습니다.';
		showToast(errorMessage, 'warning');

		// 권한 오류인 경우 회원 목록 비우기
		if (xhr.status === 403) {
			displayMembers([]);
		}
	} else {
		showToast(errorMessage, 'error');
	}
}



/**
 * QR 카메라 스캐너 초기화
 */
function initQrScanner() {
	if (html5QrcodeScanner) {
		return;
	}

	html5QrcodeScanner = new Html5Qrcode("qr-reader");

	const config = {
		fps: 10,
		qrbox: { width: 250, height: 250 },
		aspectRatio: 1.0
	};

	html5QrcodeScanner.start(
		{ facingMode: currentFacingMode },
		config,
		onQrScanSuccess,
		onQrScanFailure
	).catch(err => {
		console.error("QR 스캐너 시작 실패:", err);
		showToast('카메라를 시작할 수 없습니다.', 'error');
		$('#switchCheckCamera').prop('checked', false);
		$('#qrCameraOffcanvas').offcanvas('hide');
	});
}

/**
 * QR 스캔 성공 핸들러
 */
function onQrScanSuccess(decodedText, decodedResult) {
	// 입력란에 값 설정 및 출석 체크 실행
	$('#input-search').val(decodedText);
	$('#btn-submit').click();

	// 입력란 초기화 (다음 스캔 준비)
	setTimeout(function() {
		$('#input-search').val('');
	}, 1000);
}

/**
 * QR 스캔 실패 핸들러 (선택사항 - 에러 메시지를 표시하지 않음)
 */
function onQrScanFailure(error) {
	// 스캔 실패는 정상적인 상황이므로 로그만 남김
}

/**
 * QR 카메라 스캐너 중지
 */
function stopQrScanner() {
	if (html5QrcodeScanner) {
		html5QrcodeScanner.stop().then(() => {
			html5QrcodeScanner = null;
		}).catch(err => {
			console.error("QR 스캐너 중지 실패:", err);
		});
	}
}

/**
 * QR 카메라 토글 핸들러
 */
function handleQrCameraToggle() {
	const isChecked = $('#switchCheckCamera').is(':checked');

	if (isChecked) {
		// offcanvas 열기
		$('#qrCameraOffcanvas').offcanvas('show');
	} else {
		// offcanvas 닫기
		$('#qrCameraOffcanvas').offcanvas('hide');
	}
}

/**
 * 카메라 전환
 */
function switchCamera() {
	if (!html5QrcodeScanner) {
		return;
	}

	// 현재 카메라 중지
	html5QrcodeScanner.stop().then(() => {
		html5QrcodeScanner = null;

		// 카메라 모드 전환
		currentFacingMode = currentFacingMode === "environment" ? "user" : "environment";

		// 새로운 카메라로 재시작
		setTimeout(() => {
			initQrScanner();
		}, 100);
	}).catch(err => {
		console.error("카메라 전환 실패:", err);
		showToast('카메라 전환에 실패했습니다.', 'error');
	});
}


/**
 * QR 카메라 offcanvas 이벤트 핸들러
 */
function bindQrCameraOffcanvasEvents() {
	// offcanvas가 완전히 열린 후 카메라 시작
	$('#qrCameraOffcanvas').on('shown.bs.offcanvas', function () {
		// 카메라 모드 초기화 (후면 카메라)
		currentFacingMode = "environment";
		$('#switchCameraFacing').prop('checked', false);
		initQrScanner();
	});

	// offcanvas가 닫힐 때 카메라 정지 및 체크박스 해제
	$('#qrCameraOffcanvas').on('hidden.bs.offcanvas', function () {
		stopQrScanner();
		$('#switchCheckCamera').prop('checked', false);
	});

	// offcanvas의 X 버튼 클릭 시에도 체크박스 해제
	$('#qrCameraOffcanvas .btn-close').on('click', function() {
		$('#switchCheckCamera').prop('checked', false);
	});

	// 카메라 전환 토글 이벤트
	$('#switchCameraFacing').on('change', function() {
		switchCamera();
	});
}
