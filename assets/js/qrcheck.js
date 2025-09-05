'use strict'

// 첫 번째 출석 유형으로 기본값 설정
var firstAttType = $('.dropdown-att-type .dropdown-item:first');
var attTypeName = firstAttType.text();
var attTypeIdx = firstAttType.data('att-type-idx');
$('#dropdown-toggle-att-type').text(attTypeName).data('att-type-idx', attTypeIdx);

// dropdown-att-type 항목 클릭 이벤트
$('.dropdown-att-type .dropdown-item').click(function(e) {
	e.preventDefault();
	var attTypeName = $(this).text();
	var attTypeIdx = $(this).data('att-type-idx');
	$('#dropdown-toggle-att-type').text(attTypeName).data('att-type-idx', attTypeIdx);
});


function applyModeConfig(mode) {
	const config = modeConfig[mode];
	$('.att-stamp-warp').toggleClass('hidden', config.attStampWarp === 'addClass');
	$('#input-search').prop('disabled', config.inputSearch.disabled)
		.val(config.inputSearch.val)
		.attr('placeholder', config.inputSearch.placeholder);
	if (config.inputSearch.focus) {
		$('#input-search').focus();
	}
	if (config.resetMemberList) {
		resetMemberList();
	}
	$('.offcanvas').offcanvas('hide');

	// 모드에 따라 updateFunction 호출
	var currentWeekRange = $('.current-week').text();
	var currentDate = getDateFromWeekRange(currentWeekRange);
	var startDate = getWeekStartDate(currentDate);
	var endDate = getWeekEndDate(currentDate);
	var activeOrgId = getCookie('activeOrg');

	if (mode === 'mode-1') {
		updateAttStamps(activeOrgId, startDate, endDate);
	}

	// .total-att-list 표시/숨김 처리
	if (mode === 'mode-1') {
		$('.total-att-list').show();
	} else {
		$('.total-att-list').hide();
	}

	if (config.hideNonLeaderMembers) {
		$('.grid-item:not(:has(.leader))').hide();
	} else {
		$('.grid-item').show();
	}

	// masonry 레이아웃 업데이트 (지연 실행)
	setTimeout(function() {
		if ($('.grid').data('masonry')) {
			$('.grid').masonry('layout');
		}
	}, 100);

	// 페이지 로딩 시 mode-4인 경우 처리
	if (mode === 'mode-4') {
		$('.grid-item:not(:has(.leader))').hide();
		setTimeout(function() {
			if ($('.grid').data('masonry')) {
				$('.grid').masonry('layout');
			}
		}, 100);
	}
}

const modeConfig = {
	'mode-1': {
		attStampWarp: 'removeClass',
		inputSearch: {
			disabled: false,
			val: '',
			placeholder: '이름검색 또는 QR체크!',
			focus: true
		}
	},
	'mode-4': {
		attStampWarp: 'addClass',
		inputSearch: {
			disabled: true,
			val: '출석모드 사용 중...',
			placeholder: '',
			focus: false
		},
		resetMemberList: true,
		hideNonLeaderMembers: true
	}
};


// 회원 카드 클릭 이벤트에서 권한 체크 강화
$(document).on('click', '.member-card', function() {
	var memberIdx = $(this).attr('member-idx');
	var memberName = $(this).find('.member-name').text().trim();

	var selectedMode = $('.mode-list .btn-check:checked').attr('id');


		// QR모드
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

});


// 같은 그룹 회원들을 불러올 때 권한 체크
function loadSameMembersInAttendanceOffcanvas(memberIdx, memberName, orgId, areaIdx) {
	var currentWeekRange = $('.current-week').text();
	var currentDate = getDateFromWeekRange(currentWeekRange);
	var startDate = getWeekStartDate(currentDate);
	var endDate = getWeekEndDate(currentDate);

	$.ajax({
		url: '/qrcheck/get_same_members',
		method: 'POST',
		data: {
			member_idx: memberIdx,
			org_id: orgId,
			area_idx: areaIdx,
			start_date: startDate,
			end_date: endDate
		},
		dataType: 'json',
		success: function(response) {
			console.log(response);

			if (response.status === 'success') {
				var members = response.members;
				var attTypes = response.att_types;

				var offcanvasTitle = $('#attendanceOffcanvasLabel');
				var offcanvasBody = $('#attendanceOffcanvas .offcanvas-body');
				offcanvasTitle.text(memberName + ' 목장의 출석체크');
				offcanvasBody.empty();

				var tableHtml = '<table class="table align-middle" style="min-width: 650px"><thead><tr><th>이름</th>';

				// 그룹에 세팅된 att_type_category 헤더 추가
				if (attTypes && attTypes.length > 0) {
					var attTypeCategories = {};
					attTypes.forEach(function(attType) {
						if (!attTypeCategories[attType.att_type_category_idx]) {
							attTypeCategories[attType.att_type_category_idx] = attType.att_type_category_name;
						}
					});

					for (var categoryIdx in attTypeCategories) {
						tableHtml += '<th>' + attTypeCategories[categoryIdx] + '</th>';
					}
				}

				tableHtml += '</tr></thead><tbody class="table-group-divider">';

				if (members && members.length > 0) {
					members.forEach(function(member) {
						tableHtml += '<tr><td style="width: 100px">' + member.member_name + '</td>';

						// 각 회원의 att_type selectbox 추가
						if (attTypes && attTypes.length > 0) {
							var attTypeCategoryIdxs = Object.keys(attTypeCategories);
							attTypeCategoryIdxs.forEach(function(categoryIdx) {
								var attTypesByCategoryIdx = attTypes.filter(function(attType) {
									return attType.att_type_category_idx == categoryIdx;
								});

								tableHtml += '<td  style="width: 100px"><select class="form-select att-type-select" data-member-idx="' + member.member_idx + '" data-att-type-category-idx="' + categoryIdx + '">';
								tableHtml += '<option value="">-</option>';

								attTypesByCategoryIdx.forEach(function(attType) {
									var selectedValue = '';
									if (member.attendance) {
										var attendanceArr = member.attendance.split('|');
										attendanceArr.forEach(function(attendance) {
											var attData = attendance.split(',');
											var attTypeIdx = attData[0].trim();
											if (attTypeIdx == attType.att_type_idx) {
												selectedValue = attTypeIdx;
											}
										});
									}
									var isSelected = selectedValue == attType.att_type_idx ? ' selected' : '';
									tableHtml += '<option value="' + attType.att_type_idx + '"' + isSelected + '>' + attType.att_type_nickname + '</option>';
								});

								tableHtml += '</select></td>';
							});
						}

						tableHtml += '</tr>';
					});
				}

				tableHtml += '</tbody></table>';
				offcanvasBody.append(tableHtml);

				$('#attendanceOffcanvas').offcanvas('show');
			} else {
				var errorMessage = response.message || '회원 정보를 가져오는데 실패했습니다.';
				showToast(errorMessage, 'error');
			}
		},
		error: function(xhr, status, error) {
			var errorMessage = '회원 정보 조회 중 오류가 발생했습니다.';
			if (xhr.responseJSON && xhr.responseJSON.message) {
				errorMessage = xhr.responseJSON.message;
			}
			showToast(errorMessage, 'error');
		}
	});

	$('#loadLastWeekBtn').data('member-idx', memberIdx).data('area-idx', areaIdx);
}

// 모드 버튼 클릭 이벤트 처리
$('.mode-list .btn-check').on('click', function() {
	var selectedMode = $(this).attr('id');
	applyModeConfig(selectedMode);
	setCookie('selectedMode', selectedMode, 7);
});

// 권한 체크 함수 추가
function checkUserPermission(requiredAction, targetData) {
	// 사용자 레벨과 마스터 여부를 전역 변수나 서버에서 받아온 데이터로 확인
	// 이 부분은 페이지 로드 시 서버에서 전달받은 데이터를 사용
	if (typeof userLevel === 'undefined' || typeof masterYn === 'undefined') {
		return false;
	}

	// 최고관리자 또는 마스터인 경우
	if (userLevel >= 10 || masterYn === 'Y') {
		return true;
	}

	// 일반 관리자인 경우는 서버에서 권한 체크 필요
	return false;
}


// 새로운 회원를 추가할 때 권한 체크
$('#saveNewMember').click(function() {
	var member_name = $('#member_name').val();
	var area_idx = $('#newMemberAreaIdx').val();
	var activeOrgId = getCookie('activeOrg');

	// 입력값 검증
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

				// 현재 선택된 주차 범위 가져오기
				var currentWeekRange = $('.current-week').text();
				var currentDate = getDateFromWeekRange(currentWeekRange);
				var startDate = getWeekStartDate(currentDate);
				var endDate = getWeekEndDate(currentDate);

				// 회원 목록 업데이트
				loadMembers(activeOrgId, userLevel, startDate, endDate);
				showToast('새 회원이 추가되었습니다.', '회원 추가 완료');
			} else {
				showToast(response.message || '회원 추가에 실패했습니다.', 'error');
			}
		},
		error: function(xhr, status, error) {
			var errorMessage = '회원 추가 중 오류가 발생했습니다.';
			if (xhr.responseJSON && xhr.responseJSON.message) {
				errorMessage = xhr.responseJSON.message;
			}
			showToast(errorMessage, 'error');
		}
	});
});

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
			// 출석 유형 로드 및 기존 출석 정보 체크
			loadAttendanceTypes(memberIdx, attendanceData);
		}
	});
}

// 출석 유형 로드 및 표시
function loadAttendanceTypes(memberIdx, attendanceData) {
	var html = '';

	var currentClass = null;
	for (var i = 0; i < attendanceTypes.length; i++) {
		var type = attendanceTypes[i];

		if (type.att_type_category_idx !== currentClass) {
			if (currentClass !== null) {
				html += '</div></div>';
			}
			html += '<label class="mb-1">' + type.att_type_category_name + '</label>';
			html += '<div class="att-btn-list"><div class="btn-group" role="group" aria-label="Attendance Type">';
			currentClass = type.att_type_category_idx;
		}

		// 현재 선택된 주차 범위 내의 모든 날짜에 대해 출석 정보 확인
		var isChecked = false;
		for (var date in attendanceData) {
			if (attendanceData.hasOwnProperty(date) && attendanceData[date].includes(type.att_type_idx.toString())) {
				isChecked = true;
				break;
			}
		}

		html += '<input type="radio" class="btn-check" name="att_type_' + type.att_type_category_idx + '" id="att_type_' + type.att_type_idx + '" value="' + type.att_type_idx + '" ' + (isChecked ? 'checked' : '') + ' autocomplete="off">';
		html += '<label class="btn btn-outline-primary" for="att_type_' + type.att_type_idx + '">' + type.att_type_name + '</label>';
	}

	if (currentClass !== null) {
		html += '</div></div>';
	}

	$('#attendanceTypes').html(html);
	$('#attendanceModal').modal('show');
}

// 출석 정보 저장
$('#saveAttendance').click(function() {
	var memberIdx = $('#selectedMemberIdx').val();
	var attendanceData = [];
	var activeOrgId = getCookie('activeOrg');

	// 현재 선택된 주차 범위 가져오기
	var currentWeekRange = $('.current-week').text();
	var currentDate = getDateFromWeekRange(currentWeekRange);
	var startDate = getWeekStartDate(currentDate);
	var endDate = getWeekEndDate(currentDate);

	// 오늘 날짜가 현재 선택된 주차 범위에 속하는지 확인
	var today = new Date();
	var formattedToday = formatDate(today);
	var attDate = (formattedToday >= startDate && formattedToday <= endDate) ? formattedToday : startDate;

	$('#attendanceTypes .btn-group').each(function() {
		var selectedType = $(this).find('input[type="radio"]:checked').val();
		if (selectedType) {
			attendanceData.push(selectedType);
		}
	});

	$.ajax({
		url: '/qrcheck/save_attendance',
		method: 'POST',
		data: {
			member_idx: memberIdx,
			attendance_data: JSON.stringify(attendanceData),
			org_id: activeOrgId,
			att_date: attDate,
			start_date: startDate,
			end_date: endDate
		},
		dataType: 'json',
		success: function(response) {
			if (response.status === 'success') {
				$('#attendanceModal').modal('hide');

				// 출석 정보 업데이트
				updateAttStamps(activeOrgId, startDate, endDate);

				// 모달이 완전히 닫힌 후 input-search에 포커스
				$('#attendanceModal').on('hidden.bs.modal', function () {
					$('#input-search').focus();
					// 이벤트 리스너를 한 번만 실행하고 제거
					$(this).off('hidden.bs.modal');
				});
				// Masonry 초기화 및 지연 실행
				initializeMasonry();
			} else {
				alert('출석 정보 저장에 실패했습니다.');
			}
		}
	});
});

// initialize 버튼을 클릭하면 모든 btn-check가 unchecked
$('#initialize').on('click', function() {
	var checkboxes = document.querySelectorAll('#attendanceTypes .btn-check');
	checkboxes.forEach(function(checkbox) {
		checkbox.checked = false;
	});
});

// 쿠키 설정 함수
function setCookie(name, value, days) {
	var expires = "";
	if (days) {
		var date = new Date();
		date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
		expires = "; expires=" + date.toUTCString();
	}
	document.cookie = name + "=" + (value || "") + expires + "; path=/";
}

// 쿠키 가져오기 함수
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


function displayMembers(members) {
	var memberList = $('.member-list .grid');
	memberList.empty();

	if (members.length === 0) {
		memberList.append('<div class="no-member">조회된 회원이 없습니다.<br>오른쪽 하단의 회원 추가 버튼을 선택하여 첫번째 회원을 추가해보세요!</div>');
	} else {
		memberList.append('<div class="grid-sizer"></div>');

		var prevAreaName = null;
		var currentGroupMembers = [];

		// 소그룹별로 그룹화
		var membersByArea = {};
		$.each(members, function(index, member) {
			var areaName = member.area_name || '미분류';
			if (!membersByArea[areaName]) {
				membersByArea[areaName] = [];
			}
			membersByArea[areaName].push(member);
		});

		// 소그룹 순서대로 표시 (area_order 기준)
		var sortedAreas = Object.keys(membersByArea).sort(function(a, b) {
			var areaA = membersByArea[a][0];
			var areaB = membersByArea[b][0];
			var orderA = areaA.area_order || 999;
			var orderB = areaB.area_order || 999;
			return orderA - orderB;
		});

		$.each(sortedAreas, function(areaIndex, areaName) {
			var areaMembers = membersByArea[areaName];

			// 첫 번째 회원의 area_idx 가져오기 (버튼 이벤트에 필요)
			var areaIdx = areaMembers[0].area_idx || null;

			// 소그룹명 헤더 + 버튼들 추가
			var areaHeader = $('<div class="grid-item grid-item--width100 area-header">' +
				'<div class="area-title">' +
				'<span class="area-name">' + areaName + '</span>' +
				'<div class="area-buttons">' +
				'<button class="btn btn-sm btn-outline-primary btn-area-attendance-memo" data-area-idx="' + areaIdx + '" data-area-name="' + areaName + '">' +
				'<i class="bi bi-clipboard-check"></i> 목장출석' +
				'</button>' +
				'</div>' +
				'</div>' +
				'</div>');
			memberList.append(areaHeader);

			// 해당 소그룹의 회원들 추가
			$.each(areaMembers, function(memberIndex, member) {
				var memberCard = $('<div class="grid-item"><div class="member-card" member-idx="' + member.member_idx + '" data-birth="' + member.member_birth + '"><div class="member-wrap"><span class="member-name">' + member.member_name + '</span><span class="att-stamp-warp"></span></div></div></div>');

				// 모든 회원에게 사진 영역 추가
				if (member.photo) {
					var photoUrl = '/uploads/member_photos/' + member.org_id + '/' + member.photo;
					memberCard.find('.member-card').prepend('<span class="photo" style="background: url(' + photoUrl + ') center center/cover"></span>');
				} else {
					memberCard.find('.member-card').prepend('<span class="photo"></span>');
				}

				// 리더 배지 추가
				if (member.leader_yn === 'Y') {
					memberCard.find('.member-card').addClass('leader');
					memberCard.find('.member-card .member-wrap').prepend('<span class="badge"><i class="bi bi-star-fill"></i></span>');
				}

				if (member.area_idx) {
					memberCard.find('.member-card').addClass('area-idx-' + member.area_idx);
				}

				// 새가족 배지 추가
				if (member.new_yn === 'Y') {
					memberCard.find('.member-card').addClass('new');
					memberCard.find('.member-card .member-wrap').prepend('<span class="badge"><i class="bi bi-asterisk"></i></span>');
				}

				// 출석 스탬프 추가 (서버에서 받은 데이터)
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

				memberList.append(memberCard);
			});

			// 소그룹 구분선 추가 (마지막 소그룹이 아닌 경우)
			if (areaIndex < sortedAreas.length - 1) {
				var areaDivider = $('<div class="grid-item grid-item--width100 area-divider"></div>');
				memberList.append(areaDivider);
			}
		});
	}

	// total-list 합산 계산 (화면에 표시된 회원들만 계산)
	var totalMembers = $('.grid-item .member-card').length;
	var totalNewMembers = $('.member-card.new').length;
	var totalAttMembers = totalMembers - totalNewMembers;
	var totalLeaderMembers = $('.member-card.leader').length;

	$('.total-list dd').eq(0).text(totalMembers);
	$('.total-list dd').eq(3).text(totalNewMembers);
	$('.total-list dd').eq(1).text(totalAttMembers);
	$('.total-list dd').eq(2).text(totalLeaderMembers);

	// 출석 타입별 숫자 계산 (화면에 표시된 스탬프들만 계산)
	updateAttTypeCountFromDOM();

	// Masonry 초기화 및 지연 실행
	initializeMasonry();
}

/**
 * 역할: 화면에 표시된 출석 스탬프를 기반으로 출석 타입별 개수 계산
 */
function updateAttTypeCountFromDOM() {
	var attTypeCount = {};

	// 화면에 표시된 모든 출석 스탬프 카운트
	$('.att-stamp').each(function() {
		var attType = $(this).text().trim();
		if (attTypeCount[attType]) {
			attTypeCount[attType]++;
		} else {
			attTypeCount[attType] = 1;
		}
	});

	// .total-att-list 업데이트
	var totalAttList = $('.total-att-list');
	totalAttList.empty();

	if (Object.keys(attTypeCount).length === 0) {
		// 출석 데이터가 없는 경우 기본 표시
		totalAttList.append('<dt>출석</dt><dd>0</dd>');
		return;
	}

	// 출석 타입을 att_type_order 순으로 정렬
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


// 목장출석 버튼 클릭 이벤트 (출석관리화면의 상세 offcanvas 표시)
$(document).on('click', '.btn-area-attendance-memo', function() {
	var areaIdx = $(this).data('area-idx');
	var areaName = $(this).data('area-name');

	if (!areaIdx) {
		showToast('소그룹 정보를 찾을 수 없습니다.', 'error');
		return;
	}

	var orgId = getCookie('activeOrg');

	// 출석관리화면의 상세 offcanvas 호출
	loadAreaMembersForDetailedManagement(areaIdx, areaName, orgId);
});

// 출석관리화면의 상세 offcanvas 로드 함수
function loadAreaMembersForDetailedManagement(areaIdx, areaName, orgId) {
	var currentWeekRange = $('.current-week').text();
	var currentDate = getDateFromWeekRange(currentWeekRange);
	var startDate = getWeekStartDate(currentDate);
	var endDate = getWeekEndDate(currentDate);

	$.ajax({
		url: '/qrcheck/get_same_members', // 동일한 엔드포인트 사용
		method: 'POST',
		data: {
			member_idx: 0, // 임시값
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
				offcanvasTitle.text(areaName + ' 목장출석');
				offcanvasBody.empty();

				// 상세 관리용 테이블 생성 (메모를 이름 옆으로 이동)
				var tableHtml = '<table class="table align-middle" style="min-width: 850px"><thead><tr>' +
					'<th style="width: 100px">이름</th>' +
					'<th style="width: 200px">한줄메모</th>';

				// 출석 타입 헤더 추가
				if (attTypes && attTypes.length > 0) {
					var attTypeCategories = {};
					attTypes.forEach(function(attType) {
						if (!attTypeCategories[attType.att_type_category_idx]) {
							attTypeCategories[attType.att_type_category_idx] = attType.att_type_category_name;
						}
					});

					for (var categoryIdx in attTypeCategories) {
						tableHtml += '<th style="width: 120px">' + attTypeCategories[categoryIdx] + '</th>';
					}
				}

				tableHtml += '</tr></thead><tbody class="table-group-divider">';

				if (members && members.length > 0) {
					members.forEach(function(member) {
						tableHtml += '<tr><td>' + member.member_name + '</td>';

						// 메모 입력 필드 추가 (이름 바로 옆)
						var existingMemo = member.memo_content || ''; // 기존 메모가 있으면 표시
						tableHtml += '<td><input type="text" class="form-control memo-input" data-member-idx="' + member.member_idx + '" placeholder="메모 입력..." value="' + existingMemo.replace(/"/g, '&quot;') + '"></td>';

						// 각 회원의 출석 타입 selectbox 추가
						if (attTypes && attTypes.length > 0) {
							var attTypeCategoryIdxs = Object.keys(attTypeCategories);
							attTypeCategoryIdxs.forEach(function(categoryIdx) {
								var attTypesByCategoryIdx = attTypes.filter(function(attType) {
									return attType.att_type_category_idx == categoryIdx;
								});

								tableHtml += '<td><select class="form-select att-type-select" data-member-idx="' + member.member_idx + '" data-att-type-category-idx="' + categoryIdx + '">';
								tableHtml += '<option value="">-</option>';

								attTypesByCategoryIdx.forEach(function(attType) {
									var selectedValue = '';
									if (member.attendance) {
										var attendanceArr = member.attendance.split('|');
										attendanceArr.forEach(function(attendance) {
											var attData = attendance.split(',');
											var attTypeIdx = attData[0].trim();
											if (attTypeIdx == attType.att_type_idx) {
												selectedValue = attTypeIdx;
											}
										});
									}
									var isSelected = selectedValue == attType.att_type_idx ? ' selected' : '';
									tableHtml += '<option value="' + attType.att_type_idx + '"' + isSelected + '>' + attType.att_type_nickname + '</option>';
								});

								tableHtml += '</select></td>';
							});
						}

						tableHtml += '</tr>';
					});
				}

				tableHtml += '</tbody></table>';
				offcanvasBody.append(tableHtml);

				// offcanvas 버튼 텍스트 변경
				$('#saveAttendanceBtn').text('저장');

				$('#attendanceOffcanvas').offcanvas('show');
			} else {
				var errorMessage = response.message || '회원 정보를 가져오는데 실패했습니다.';
				showToast(errorMessage, 'error');
			}
		},
		error: function(xhr, status, error) {
			var errorMessage = '회원 정보 조회 중 오류가 발생했습니다.';
			if (xhr.responseJSON && xhr.responseJSON.message) {
				errorMessage = xhr.responseJSON.message;
			}
			showToast(errorMessage, 'error');
		}
	});
}


/**
 * 역할: 출석+메모 저장 시 att_date 포함하여 저장
 */
function saveAttendanceAndMemo() {
	console.log('출석 및 메모 저장 시작');

	var currentWeekRange = $('.current-week').text();
	var currentDate = getDateFromWeekRange(currentWeekRange);
	var startDate = getWeekStartDate(currentDate);
	var endDate = getWeekEndDate(currentDate);
	var activeOrgId = getCookie('activeOrg');

	// 오늘 날짜가 현재 주차에 속하는지 확인하여 출석 날짜 결정
	var today = new Date();
	var formattedToday = formatDate(today);
	var attDate = (formattedToday >= startDate && formattedToday <= endDate) ? formattedToday : startDate;

	// 일요일 날짜 계산
	var sundayDate = getSundayOfWeek(attDate);

	// 저장할 데이터 수집
	var attendanceData = [];
	var memoData = [];
	var allMemberIndices = [];

	// 1. 먼저 모든 회원 인덱스를 수집
	$('.att-type-select, .memo-input').each(function() {
		var memberIdx = $(this).data('member-idx');
		if (memberIdx && allMemberIndices.indexOf(memberIdx) === -1) {
			allMemberIndices.push(memberIdx);
		}
	});

	console.log('전체 회원 인덱스:', allMemberIndices);

	// 2. 각 회원별로 출석 데이터와 메모 데이터 수집
	allMemberIndices.forEach(function(memberIdx) {
		// 출석 데이터 수집
		var memberAttendanceData = {};
		var hasAttendanceData = false;

		$('.att-type-select[data-member-idx="' + memberIdx + '"]').each(function() {
			var attTypeIdx = $(this).val();
			var attTypeCategoryIdx = $(this).data('att-type-category-idx');

			if (attTypeIdx && attTypeIdx.trim() !== '') {
				memberAttendanceData[attTypeCategoryIdx] = attTypeIdx;
				hasAttendanceData = true;
			}
		});

		// 출석 데이터를 배열로 변환 (값이 있든 없든 해당 회원 정보는 포함)
		for (var categoryIdx in memberAttendanceData) {
			var attTypeIdx = memberAttendanceData[categoryIdx];
			attendanceData.push({
				member_idx: memberIdx,
				att_type_idx: attTypeIdx,
				att_date: sundayDate, // 일요일 날짜로 저장
				has_data: true
			});
		}

		// 출석 데이터가 없는 회원도 삭제 처리를 위해 포함
		if (!hasAttendanceData) {
			attendanceData.push({
				member_idx: memberIdx,
				att_type_idx: null,
				att_date: sundayDate, // 일요일 날짜로 저장
				has_data: false
			});
		}

		// 메모 데이터 수집 (모든 회원 포함) - att_date 포함
		var memoInput = $('.memo-input[data-member-idx="' + memberIdx + '"]');
		if (memoInput.length > 0) {
			var memoContent = memoInput.val().trim();
			memoData.push({
				member_idx: memberIdx,
				memo_content: memoContent,
				memo_date: sundayDate, // 일요일 날짜로 저장 (att_date로 사용됨)
				org_id: activeOrgId
			});
		}
	});

	console.log('수정된 출석 데이터:', attendanceData);
	console.log('메모 데이터:', memoData);

	// 저장 버튼 상태 변경
	var $saveBtn = $('#saveAttendanceBtn');
	var originalText = $saveBtn.text();
	$saveBtn.prop('disabled', true).text('저장 중...');

	// 출석 데이터 저장
	$.ajax({
		url: '/qrcheck/save_attendance_data_with_cleanup',
		method: 'POST',
		data: {
			attendance_data: JSON.stringify(attendanceData),
			org_id: activeOrgId,
			start_date: startDate,
			end_date: endDate,
			att_date: sundayDate // 일요일 날짜로 저장
		},
		dataType: 'json',
		success: function(response) {
			console.log('출석 저장 응답:', response);

			// 출석 저장 성공 후 메모 저장
			if (response.status === 'success') {
				saveMemoData(memoData, activeOrgId, startDate, endDate, $saveBtn, originalText);
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
 * 역할: 일요일 날짜 계산 유틸리티 함수
 */
function getSundayOfWeek(date) {
	var dateObj = new Date(date);
	var day = dateObj.getDay(); // 0=일요일, 1=월요일...
	var diff = dateObj.getDate() - day;
	var sunday = new Date(dateObj.setDate(diff));
	return formatDate(sunday);
}

// 메모 데이터 저장 함수
function saveMemoData(memoData, activeOrgId, startDate, endDate, $saveBtn, originalText) {
	if (memoData.length === 0) {
		$saveBtn.prop('disabled', false).text(originalText);
		showToast('저장할 데이터가 없습니다.', 'success');
		return;
	}

	$.ajax({
		url: '/qrcheck/save_memo_data', // 메모 저장 전용 엔드포인트
		method: 'POST',
		data: {
			memo_data: JSON.stringify(memoData),
			org_id: activeOrgId
		},
		dataType: 'json',
		success: function(response) {
			console.log('메모 저장 응답:', response);

			if (response.status === 'success') {
				$('#attendanceOffcanvas').offcanvas('hide');
				updateAttStamps(activeOrgId, startDate, endDate);
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

// Masonry 초기화 함수 분리
function initializeMasonry() {
	var $grid = $('.grid');

	// 기존 masonry 인스턴스 제거
	if ($grid.data('masonry')) {
		$grid.masonry('destroy');
	}

	// DOM 렌더링 완료를 위한 지연 실행
	setTimeout(function() {
		$grid.masonry({
			itemSelector: '.grid-item',
			columnWidth: '.grid-sizer',
			stamp: '.stamp',
			percentPosition: true,
			transitionDuration: '0.3s'
		});

		// 이미지 로딩 완료 후 재배치 (사진이 있는 경우)
		$grid.imagesLoaded(function() {
			$grid.masonry('layout');
		});

		// 추가 안전장치: 한번 더 재배치
		setTimeout(function() {
			$grid.masonry('layout');
		}, 200);

	}, 100); // 50ms 지연
}

function applySelectedMode() {
	var selectedMode = getCookie('selectedMode');
	if (selectedMode) {
		$('.mode-list .btn-check').prop('checked', false);
		$('#' + selectedMode).prop('checked', true);
		applyModeConfig(selectedMode);
	} else {
		$('.mode-list .btn-check').prop('checked', false);
		$('#mode-1').prop('checked', true);
		applyModeConfig('mode-1');
	}
}

function getWeekStartDate(date) {
	if (typeof date === 'string') {
		date = new Date(date);
	}
	var day = date.getDay();
	var diff = date.getDate() - day;
	var startDate = new Date(date.setDate(diff));
	return formatDate(startDate);
}

function getWeekEndDate(date) {
	if (typeof date === 'string') {
		date = new Date(date);
	}
	var day = date.getDay();
	var diff = date.getDate() - day + 6;
	var endDate = new Date(date.setDate(diff));
	return formatDate(endDate);
}

function getWeekNumber(date) {
	var currentDate = new Date(date.getTime());
	var startDate = new Date(currentDate.getFullYear(), 0, 1);
	var days = Math.floor((currentDate - startDate) / (24 * 60 * 60 * 1000));
	var weekNumber = Math.ceil(days / 7);
	return weekNumber;
}

function getDateFromWeekRange(weekRange) {
	var parts = weekRange.split('~');
	var startDateStr = parts[0].trim().replace('년', '-').replace('월', '-').replace('일', '').replace(/\(\d+주차\)/, '').trim();
	return new Date(startDateStr);
}

function getWeekRangeFromDate(date) {
	var currentDate = new Date(date);
	var sundayTimestamp = getSunday(currentDate).getTime();
	var nextSundayTimestamp = sundayTimestamp + (7 * 24 * 60 * 60 * 1000);
	var week = getWeekNumber(currentDate);
	var startDate = formatDate(new Date(sundayTimestamp));
	var endDate = formatDate(new Date(nextSundayTimestamp - (24 * 60 * 60 * 1000)));
	return `${startDate}~${endDate} (${week}주차)`;
}

function generateAllWeekRanges() {
	var allWeekRanges = [];
	var startDate = new Date(new Date().getFullYear(), 0, 1);
	var endDate = new Date(new Date().getFullYear(), 11, 31);

	while (startDate <= endDate) {
		var weekRange = getWeekRangeFromDate(startDate);
		allWeekRanges.push(weekRange);
		startDate.setDate(startDate.getDate() + 7);
	}

	return allWeekRanges.reverse();
}

function getSunday(date) {
	var day = date.getDay();
	var diff = date.getDate() - day;
	return new Date(date.setDate(diff));
}

function formatDate(date) {
	var year = date.getFullYear();
	var month = String(date.getMonth() + 1).padStart(2, '0');
	var day = String(date.getDate()).padStart(2, '0');
	return `${year}.${month}.${day}`;
}


// 회원 로드 함수에서 권한 정보 포함
function loadMembers(orgId, level, startDate, endDate, initialLoad = true) {

	// Call the function to delete the 'selectedMode' cookie
	deleteCookie('selectedMode');

	$.ajax({
		url: '/qrcheck/get_members',
		method: 'POST',
		data: {
			org_id: orgId,
			level: level,
			start_date: startDate,
			end_date: endDate
		},
		dataType: 'json',
		success: function(members) {
			displayMembers(members);

			// 현재 선택된 주차 범위 가져오기
			var currentWeekRange = $('.current-week').text();
			var currentDate = getDateFromWeekRange(currentWeekRange);
			var startDate = getWeekStartDate(currentDate);
			var endDate = getWeekEndDate(currentDate);
			updateBirthBg(startDate, endDate);

			// 초기 로드 시에만 applySelectedMode 호출
			if (initialLoad) {
				applySelectedMode();
			}
		},
		error: function(xhr, status, error) {
			console.error('회원 로드 실패:', error);
			if (xhr.status === 403 || (xhr.responseJSON && xhr.responseJSON.message)) {
				var errorMessage = xhr.responseJSON ? xhr.responseJSON.message : '권한이 없습니다.';
				showToast(errorMessage, 'warning');
				// 회원 목록을 비우고 권한 없음 메시지 표시
				displayMembers([]);
			} else {
				showToast('회원 목록을 불러오는 중 오류가 발생했습니다.', 'error');
			}
		}
	});
}

// 새로운 함수 추가
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

			if (formattedBirthDate >= startDate && formattedBirthDate <= endDate) {
				if (!memberCard.find('.birth').length) {
					memberCard.addClass('birth');
				}
			} else {
				memberCard.removeClass('birth');
			}
		}
	});
}

// 한글자 이상 입력하면 검색, 숫자 입력 시 검색 기능 비활성화
$('#input-search').on('input', function() {
	var searchText = $(this).val().trim();
	if (/^\d+$/.test(searchText)) {
		// 숫자만 입력된 경우 검색 기능 비활성화
		return;
	}

	if (searchText.length >= 1) {
		searchMembers(searchText);
	} else {
		resetMemberList();
	}
});

function searchMembers(searchText) {
	$('.grid-item').each(function() {
		var memberName = $(this).find('.member-wrap').text().trim();

		if (memberName.includes(searchText)) {
			$(this).show();
		} else {
			$(this).hide();
		}
	});

	// Masonry 레이아웃 업데이트 (지연 실행)
	setTimeout(function() {
		if ($('.grid').data('masonry')) {
			$('.grid').masonry('layout');
		}
	}, 50);
}

function resetMemberList() {
	$('.grid-item').show();

	// Masonry 레이아웃 업데이트 (지연 실행)
	setTimeout(function() {
		if ($('.grid').data('masonry')) {
			$('.grid').masonry('layout');
		}
	}, 50);
}

$('#input-search').on('keypress', function(e) {
	if (e.which === 13) {
		addAttStamp();
	}
});

$('#btn-submit').on('click', function() {
	addAttStamp();
});

function addAttStamp() {
	var memberIdx = $('#input-search').val().trim();
	if (/^\d+$/.test(memberIdx)) {
		var attTypeIdx = $('#dropdown-toggle-att-type').data('att-type-idx');
		var attTypeNickname = $('.dropdown-att-type .dropdown-item[data-att-type-idx="' + attTypeIdx + '"]').data('att-type-nickname');
		var attTypeCategoryIdx = $('.dropdown-att-type .dropdown-item[data-att-type-idx="' + attTypeIdx + '"]').data('att-type-category-idx');

		// 해당 member-idx를 가진 회원에게 att-stamp 추가
		var memberCard = $('.member-card[member-idx="' + memberIdx + '"]');
		if (memberCard.length > 0) {
			var existingAttStamp = memberCard.find('.att-stamp[data-att-type-idx="' + attTypeIdx + '"]');
			if (existingAttStamp.length > 0) {
				playNoSound();
				showToast('이미 출석체크 하였습니다.', 'warning');
				$('#input-search').val('').focus();
				return;
			}

			// 동일한 att_type_category_idx를 가진 모든 att-stamp 삭제
			var existingCategoryStamps = memberCard.find('.att-stamp[data-att-type-category-idx="' + attTypeCategoryIdx + '"]');
			if (existingCategoryStamps.length > 0) {
				existingCategoryStamps.remove();
			}

			var attStamp = '<span class="att-stamp" data-att-type-idx="' + attTypeIdx + '" data-att-type-category-idx="' + attTypeCategoryIdx + '">' + attTypeNickname + '</span>';
			memberCard.find('.att-stamp-warp').append(attStamp);

			// 서버에 출석 정보 저장
			saveAttendance(memberIdx, attTypeIdx, attTypeCategoryIdx);

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
			playNoSound()
			showToast('등록된 회원이 아닙니다.', 'error');
		}
	}
	$('#input-search').val('').focus();
}

function playOkSound() {
	var audio = document.getElementById('sound-ok');
	audio.play();
}

function playNoSound() {
	var audio = document.getElementById('sound-no');
	audio.play();
}

function playBirthSound() {
	var audio = document.getElementById('sound-birth');
	audio.play();
}



function saveAttendance(memberIdx, attTypeIdx, attTypeCategoryIdx, selectedValue) {
	var activeOrgId = getCookie('activeOrg');
	var today = new Date();
	var attDate = formatDate(today);

	// 해당 member-card로 스크롤 이동 및 'now' 클래스 추가
	var memberCard = $('.member-card[member-idx="' + memberIdx + '"]');
	if (memberCard.length > 0) {
		// 기존의 'now' 클래스를 모든 member-card에서 제거
		$('.member-card').removeClass('now');
		// 현재 member-card에 'now' 클래스 추가
		memberCard.addClass('now');

		// 해당 회원 카드로 스크롤 이동
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
			att_type_category_idx: attTypeCategoryIdx,
			org_id: activeOrgId,
			att_date: attDate,
			selected_value: selectedValue
		},
		dataType: 'json',
		success: function(response) {
			if (response.status === 'success') {
				// 출석 정보 업데이트
				var currentWeekRange = $('.current-week').text();
				var currentDate = getDateFromWeekRange(currentWeekRange);
				var startDate = getWeekStartDate(currentDate);
				var endDate = getWeekEndDate(currentDate);

				updateAttStamps(activeOrgId, startDate, endDate);
				initializeMasonry()
			} else {
				console.log('출석 정보 저장 실패');
			}
		}
	});
}

// 전역변수 정의
var attend_type_color_map = {};
var attend_type_order_map = {};

/**
 * 역할: 출석 스탬프 업데이트 함수 수정 - 화면 기반 카운트 적용
 */
function updateAttStamps(orgId, startDate, endDate) {
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
			$('.member-card .att-stamp-warp .att-stamp').remove();

			$.each(attendanceData, function(memberIdx, attTypeNicknames) {
				var memberCard = $('.member-card[member-idx="' + memberIdx + '"]');
				var attStampsContainer = memberCard.find('.att-stamp-warp');

				if (attTypeNicknames) {
					var attStamps = attTypeNicknames.split(',').map(function(attTypeData) {
						var attTypeArr = attTypeData.split('|');
						var attTypeNickname = attTypeArr[0].trim();
						var attTypeIdx = attTypeArr[1].trim();
						var attTypeCategoryIdx = attTypeArr[2].trim();
						var attTypeColor = attTypeArr[3].trim();

						return '<span class="att-stamp" data-att-type-idx="' + attTypeIdx + '" data-att-type-category-idx="' + attTypeCategoryIdx + '" style="background-color: #' + attTypeColor + '">' + attTypeNickname + '</span>';
					}).join(' ');

					attStampsContainer.append(attStamps);
				}
			});

			// 화면에 표시된 스탬프를 기반으로 카운트 업데이트
			updateAttTypeCountFromDOM();
		}
	});
}

// 주차 범위 업데이트 함수 수정
function updateWeekRange(weekRange) {
	$('.current-week').text(weekRange);
	var currentDate = getDateFromWeekRange(weekRange);
	var startDate = getWeekStartDate(currentDate);
	var endDate = getWeekEndDate(currentDate);
	var activeOrgId = getCookie('activeOrg');

	// 좌우버튼클릭시 실행
	if (activeOrgId) {
		updateBirthBg(startDate, endDate);
		var selectedMode = $('.mode-list .btn-check:checked').attr('id');
		if (selectedMode === 'mode-1') {
			updateAttStamps(activeOrgId, startDate, endDate);
		}
	}

	updateInputSearchState();
}

// 오늘 날짜가 current-week의 기간 안에 있는지 확인하고 input-search 활성화/비활성화
function updateInputSearchState() {
	var currentWeekRange = $('.current-week').text();
	var currentDate = getDateFromWeekRange(currentWeekRange);
	var startDate = getWeekStartDate(currentDate);
	var endDate = getWeekEndDate(currentDate);
	var today = new Date();
	var formattedToday = formatDate(today);

	if (formattedToday >= startDate && formattedToday <= endDate) {
		$('#input-search').prop('disabled', false).val('').attr('placeholder', '이름검색 또는 QR체크!').focus();
	} else {
		$('#input-search').prop('disabled', true).val('검색중...').attr('placeholder', '');
		resetMemberList();
	}
}

// saveAttendanceBtn 클릭 이벤트 수정
$('#saveAttendanceBtn').off('click').on('click', function() {
	const buttonText = $(this).text();

		saveAttendanceAndMemo();

});


/**
 * 역할: 출석 데이터 저장 함수 수정 - 일요일 날짜로 저장
 */
function saveAttendanceData(attendanceData) {
	var currentWeekRange = $('.current-week').text();
	var currentDate = getDateFromWeekRange(currentWeekRange);
	var startDate = getWeekStartDate(currentDate);
	var endDate = getWeekEndDate(currentDate);
	var activeOrgId = getCookie('activeOrg');

	// 오늘 날짜가 현재 주차에 속하는지 확인하여 출석 날짜 결정
	var today = new Date();
	var formattedToday = formatDate(today);
	var attDate = (formattedToday >= startDate && formattedToday <= endDate) ? formattedToday : startDate;

	// 일요일 날짜 계산
	var sundayDate = getSundayOfWeek(attDate);

	// 회원별로 출석 데이터 정리 (중복 제거 및 빈 값 필터링)
	var memberAttendanceMap = {};

	attendanceData.forEach(function(item) {
		var memberIdx = item.member_idx;
		var attTypeIdx = item.att_type_idx;

		// 빈 값이 아닌 경우만 처리
		if (attTypeIdx && attTypeIdx.trim() !== '') {
			if (!memberAttendanceMap[memberIdx]) {
				memberAttendanceMap[memberIdx] = [];
			}

			// 중복 체크 후 추가
			if (memberAttendanceMap[memberIdx].indexOf(attTypeIdx) === -1) {
				memberAttendanceMap[memberIdx].push(attTypeIdx);
			}
		}
	});

	// 정리된 데이터를 배열로 변환
	var processedAttendanceData = [];
	for (var memberIdx in memberAttendanceMap) {
		memberAttendanceMap[memberIdx].forEach(function(attTypeIdx) {
			processedAttendanceData.push({
				member_idx: memberIdx,
				att_type_idx: attTypeIdx,
				att_date: sundayDate // 일요일 날짜로 저장
			});
		});
	}

	$.ajax({
		url: '/qrcheck/save_attendance_data',
		method: 'POST',
		data: {
			attendance_data: JSON.stringify(processedAttendanceData),
			org_id: activeOrgId,
			start_date: startDate,
			end_date: endDate
		},
		dataType: 'json',
		success: function(response) {
			if (response.status === 'success') {
				$('#attendanceOffcanvas').offcanvas('hide');
				updateAttStamps(activeOrgId, startDate, endDate);
				var memberName = $('#attendanceOffcanvasLabel').text().split(' ')[0];
				showToast(memberName + ' 목장의 출석체크를 완료하였습니다.', 'success');
			} else {
				showToast('출석 정보 저장에 실패했습니다.', 'error');
			}
		},
		error: function(xhr, status, error) {
			console.log(xhr.responseText);
			showToast('출석 정보 저장 중 오류가 발생했습니다.', 'error');
		}
	});
}

function updateAttendanceTypes(orgId) {
	$.ajax({
		url: '/qrcheck/get_attendance_types',
		method: 'POST',
		data: { org_id: orgId },
		dataType: 'json',
		success: function(response) {
			var attendanceTypes = response.attendance_types;
			var dropdownAttType = $('.dropdown-att-type');
			dropdownAttType.empty();

			var prevCategoryIdx = null;
			$.each(attendanceTypes, function(index, type) {
				if (prevCategoryIdx !== null && prevCategoryIdx !== type.att_type_category_idx) {
					dropdownAttType.append('<li><hr class="dropdown-divider"></li>');
				}
				dropdownAttType.append('<li><a class="dropdown-item" href="#" data-att-type-idx="' + type.att_type_idx + '" data-att-type-nickname="' + type.att_type_nickname + '">' + type.att_type_name + '</a></li>');
				prevCategoryIdx = type.att_type_category_idx;
			});

			// data-att-type-idx가 가장 작은 값으로 설정
			var firstAttType = $('.dropdown-att-type .dropdown-item:first');
			var attTypeName = firstAttType.text();
			var attTypeIdx = firstAttType.data('att-type-idx');

			// dropdown-toggle-att-type 텍스트와 data-att-type-idx 값을 직접 설정
			$('#dropdown-toggle-att-type').text(attTypeName);
			$('#dropdown-toggle-att-type').data('att-type-idx', attTypeIdx);
		}
	});
}

// dropdown-att-type 항목 클릭 이벤트
$('.dropdown-att-type').on('click', '.dropdown-item', function(e) {
	e.preventDefault();
	var attTypeName = $(this).text();
	var attTypeIdx = $(this).data('att-type-idx');
	$('#dropdown-toggle-att-type').text(attTypeName).data('att-type-idx', attTypeIdx);
});

// 쿠키 삭제 함수
function deleteCookie(name) {
	document.cookie = name + '=; expires=Thu, 01 Jan 1970 00:00:01 GMT; path=/;';
}


function attComplete(memberIdx) {
	$.ajax({
		url: '/qrcheck/get_member_info',
		method: 'POST',
		data: { member_idx: memberIdx },
		dataType: 'json',
		success: function(response) {
			var memberName = response.member_name;
			var memberNick = response.member_nick || (memberName + '님 환영합니다!');

			// 메시지를 문자열로 직접 생성
			var message = memberName + '님 출석완료';


			// showToast 호출 (message가 첫 번째 매개변수, memberNick이 헤더)
			showToast(memberNick, 'success', message);
		}
	});
}


var attendanceTypes = [];

$(document).ready(function() {
	// 페이지 로드 시 input-search에 포커스 설정
	$('#input-search').focus();

	// 모든 주차 범위 생성
	var allWeekRanges = generateAllWeekRanges();
	var weekRangeDropdown = $('.dropdown-current-week');
	allWeekRanges.forEach(function(weekRange) {
		var li = $('<li>').append($('<a>').addClass('dropdown-item').attr('href', '#').text(weekRange));
		weekRangeDropdown.append(li);
	});

	// 현재 주차 범위 설정
	var today = new Date();
	var formattedToday = formatDate(today);
	var currentWeekRange = getWeekRangeFromDate(formattedToday);
	updateWeekRange(currentWeekRange);

	$('.current-week').text(currentWeekRange);

	// 이전 주 버튼 클릭 이벤트
	$('.prev-week').click(function() {
		var currentWeekRange = $('.current-week').text();
		var currentDate = getDateFromWeekRange(currentWeekRange);
		var prevDate = new Date(currentDate.setDate(currentDate.getDate() - 7));
		var prevWeekRange = getWeekRangeFromDate(prevDate);
		updateWeekRange(prevWeekRange);
	});

	// 다음 주 버튼 클릭 이벤트
	$('.next-week').click(function() {
		var currentWeekRange = $('.current-week').text();
		var currentDate = getDateFromWeekRange(currentWeekRange);
		var nextDate = new Date(currentDate.setDate(currentDate.getDate() + 7));
		var nextWeekRange = getWeekRangeFromDate(nextDate);
		updateWeekRange(nextWeekRange);
	});

	// 드롭다운 메뉴 항목 클릭 이벤트
	$('.dropdown-current-week .dropdown-item').click(function(e) {
		e.preventDefault();
		var weekRange = $(this).text();
		updateWeekRange(weekRange);
	});

	// 페이지 로드 시 input-search 상태 업데이트
	updateInputSearchState();

	// 지난주 데이터 불러오기 권한 체크
	$('#loadLastWeekBtn').on('click', function() {
		var memberIdx = $(this).data('member-idx');
		var orgId = getCookie('activeOrg');
		var areaIdx = $(this).data('area-idx');
		var memberName = $('#attendanceOffcanvasLabel').text().split(' ')[0];
		loadLastWeekData(memberIdx, orgId, areaIdx, memberName);
	});


	function loadLastWeekData(memberIdx, orgId, areaIdx, memberName) {
		var currentWeekRange = $('.current-week').text();
		var currentDate = getDateFromWeekRange(currentWeekRange);
		var lastWeekStartDate = getWeekStartDate(new Date(currentDate.setDate(currentDate.getDate() - 7)));
		var lastWeekEndDate = getWeekEndDate(new Date(currentDate.setDate(currentDate.getDate())));

		$.ajax({
			url: '/qrcheck/get_last_week_attendance',
			method: 'POST',
			data: {
				member_idx: memberIdx,
				org_id: orgId,
				area_idx: areaIdx,
				start_date: lastWeekStartDate,
				end_date: lastWeekEndDate
			},
			dataType: 'json',
			success: function(response) {
				if (response.status === 'success') {
					var attendanceData = response.attendance_data;
					var attTypes = response.att_types;
					updateAttendanceSelectbox(attendanceData, attTypes);
					showToast(memberName + ' 목장의 지난 주 정보를 불러왔습니다.', 'success');
				} else {
					var errorMessage = response.message || '지난주 데이터를 가져오는데 실패했습니다.';
					showToast(errorMessage, 'error');
				}
			},
			error: function(xhr, status, error) {
				var errorMessage = '지난주 데이터 조회 중 오류가 발생했습니다.';
				if (xhr.responseJSON && xhr.responseJSON.message) {
					errorMessage = xhr.responseJSON.message;
				}
				showToast(errorMessage, 'error');
			}
		});
	}

	function updateAttendanceSelectbox(attendanceData, attTypes) {
		$('.att-type-select').each(function() {
			var memberIdx = $(this).data('member-idx');
			var attTypeCategoryIdx = parseInt($(this).data('att-type-category-idx'));
			var attTypeIdxs = attendanceData[memberIdx] || [];

			// 현재 selectbox의 att_type_category_idx에 해당하는 att_type_idx 찾기
			var selectedAttTypeIdx = '';
			for (var i = 0; i < attTypeIdxs.length; i++) {
				var attTypeIdx = parseInt(attTypeIdxs[i].trim());
				var attType = attTypes.find(function(type) {
					return parseInt(type.att_type_idx) === attTypeIdx;
				});
				if (attType && parseInt(attType.att_type_category_idx) === attTypeCategoryIdx) {
					selectedAttTypeIdx = attTypeIdx.toString();
					break;
				}
			}

			$(this).val(selectedAttTypeIdx);
		});
	}
});
