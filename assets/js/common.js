/**
 * 파일 위치: assets/js/common.js
 * 역할: 전역 공통 JavaScript 함수 및 유틸리티
 */

// ========================================
// 1. Toast 알림 함수
// ========================================

/**
 * Toast 메시지 표시
 * @param {string} message - 표시할 메시지
 * @param {string} type - 메시지 타입 (success, error, warning, info)
 * @param {number} duration - 표시 시간 (밀리초, 기본값: 3000)
 */
function showToast(message, type = 'info', duration = 3000) {
	const toastEl = document.getElementById('liveToast');
	if (!toastEl) {
		console.error('Toast 요소를 찾을 수 없습니다.');
		return;
	}

	const toastBody = toastEl.querySelector('.toast-body');
	const toastHeader = toastEl.querySelector('.toast-header strong');

	// 메시지 타입에 따른 스타일 및 제목 설정
	const typeConfig = {
		success: { bg: 'bg-success', text: 'text-white', title: '성공', icon: 'bi-check-circle-fill' },
		error: { bg: 'bg-danger', text: 'text-white', title: '오류', icon: 'bi-x-circle-fill' },
		warning: { bg: 'bg-warning', text: 'text-dark', title: '경고', icon: 'bi-exclamation-triangle-fill' },
		info: { bg: 'bg-info', text: 'text-white', title: '알림', icon: 'bi-info-circle-fill' }
	};

	const config = typeConfig[type] || typeConfig.info;

	// 기존 클래스 제거
	toastEl.classList.remove('bg-success', 'bg-danger', 'bg-warning', 'bg-info', 'text-white', 'text-dark');
	toastEl.classList.add(config.bg, config.text);

	// 메시지 및 제목 설정
	if (toastHeader) {
		toastHeader.textContent = config.title;
	}
	if (toastBody) {
		toastBody.textContent = message;
	}

	// Toast 표시
	const toast = new bootstrap.Toast(toastEl, {
		autohide: true,
		delay: duration
	});
	toast.show();
}

// ========================================
// 2. Confirm 모달 함수
// ========================================

/**
 * Confirm 모달 표시
 * @param {string} message - 확인 메시지
 * @param {function} onConfirm - 확인 버튼 클릭 시 실행할 콜백 함수
 * @param {string} title - 모달 제목 (기본값: '확인')
 * @param {string} confirmText - 확인 버튼 텍스트 (기본값: '확인')
 * @param {string} cancelText - 취소 버튼 텍스트 (기본값: '취소')
 */

/**
 * Confirm 모달 표시 함수 (공통 함수)
 */
function showConfirmModal(title, message, onConfirm, onCancel) {
	// 기존 확인 모달이 있으면 제거
	$('#confirmModal').remove();
	const modalHtml = `
        <div class="modal fade" id="confirmModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">${title}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>${message}</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                        <button type="button" class="btn btn-primary" id="confirmYes">확인</button>
                    </div>
                </div>
            </div>
        </div>
    `;


	$('body').append(modalHtml);
	$('body').find('.modal-body').html(message.replace(/\n/g, '<br>'));



	// 확인 버튼 클릭 이벤트
	$('#confirmYes').on('click', function() {
		$('#confirmModal').modal('hide');
		if (typeof onConfirm === 'function') {
			onConfirm();
		}
	});

	// 모달 닫힘 이벤트
	$('#confirmModal').on('hidden.bs.modal', function() {
		$(this).remove();
		if (typeof onCancel === 'function') {
			onCancel();
		}
	});

	$('#confirmModal').modal('show');
}



// ========================================
// 3. 조직 관리 기능
// ========================================

/**
 * 조직 선택 및 localStorage 저장
 */
function initOrgSelector() {
	// 조직 선택 이벤트
	$(document).on('click', '.org-selector', function (e) {
		e.preventDefault();

		const orgId = $(this).data('org-id');
		const orgName = $(this).data('org-name');
		const orgIcon = $(this).data('org-icon') || '';

		// localStorage에 마지막 선택 조직 저장
		localStorage.setItem('lastSelectedOrgId', orgId);
		localStorage.setItem('lastSelectedOrgName', orgName);
		localStorage.setItem('lastSelectedOrgIcon', orgIcon);

		// 서버에 조직 전환 요청
		$.ajax({
			url: window.location.href,
			type: 'POST',
			data: { org_id: orgId },
			success: function () {
				location.reload();
			},
			error: function () {
				showToast('조직 전환 중 오류가 발생했습니다.', 'error');
			}
		});
	});

	// 페이지 로드 시 localStorage의 조직이 현재 선택된 조직과 다른 경우 자동 전환
	const lastSelectedOrgId = localStorage.getItem('lastSelectedOrgId');
	const currentOrgId = $('.org-selector[data-current="true"]').data('org-id');

	if (lastSelectedOrgId && currentOrgId && lastSelectedOrgId != currentOrgId) {
		// localStorage의 조직이 사용자의 조직 목록에 있는지 확인
		const orgExists = $('.org-selector[data-org-id="' + lastSelectedOrgId + '"]').length > 0;

		if (orgExists) {
			// 자동으로 마지막 선택한 조직으로 전환 (조용히)
			$.ajax({
				url: window.location.href,
				type: 'POST',
				data: { org_id: lastSelectedOrgId },
				success: function () {
					// 필요시 페이지 새로고침
					// location.reload();
				}
			});
		}
	}
}

/**
 * 숫자 포맷팅 함수
 */
function numberFormat(number) {
	return new Intl.NumberFormat('ko-KR').format(number);
}

/**
 * 조직 검색 기능 초기화
 */
function initOrgSearch() {
	// 검색 입력 시 필터링
	$('#org-search-input').on('input', function (e) {
		e.stopPropagation();

		const searchText = $(this).val().trim();
		const $orgItems = $('#org-dropdown-menu .org-item');

		// 2자 미만이면 전체 표시
		if (searchText.length < 2) {
			$orgItems.show();
			return;
		}

		// 2자 이상일 때 필터링
		$orgItems.each(function () {
			const orgName = $(this).find('.org-selector').data('org-name');
			if (!orgName) return;

			const isMatch = orgName.toLowerCase().includes(searchText.toLowerCase());
			$(this).toggle(isMatch);
		});
	});

	// 드롭다운이 열릴 때 검색창 초기화
	$('.dropdown-toggle-split').on('click', function () {
		setTimeout(function () {
			$('#org-search-input').val('');
			$('#org-dropdown-menu .org-item').show();
		}, 100);
	});

	// 검색 입력란 클릭 시 드롭다운 닫힘 방지
	$('#org-search-input').on('click', function (e) {
		e.stopPropagation();
	});

	// 검색 입력란에서 Enter 키 방지
	$('#org-search-input').on('keydown', function (e) {
		if (e.key === 'Enter') {
			e.preventDefault();
		}
	});
}

// ========================================
// 4. 메시지 관리 기능
// ========================================

/**
 * 메시지 읽음 처리
 */
function markMessageAsRead(messageIdx) {
	$.ajax({
		url: '/message/mark_as_read',
		type: 'POST',
		data: { message_idx: messageIdx },
		dataType: 'json',
		success: function (response) {
			if (response.success) {
				// 메시지 항목 읽음 표시 업데이트
				const $messageItem = $('[data-message-idx="' + messageIdx + '"]');
				$messageItem.addClass('message-read');
				$messageItem.find('.bi-envelope-fill').removeClass('bi-envelope-fill text-warning')
					.addClass('bi-envelope-open-fill text-secondary');

				// 읽지 않은 메시지 수 업데이트
				updateUnreadMessageCount();
			} else {
				showToast(response.message || '메시지 읽음 처리 실패', 'error');
			}
		},
		error: function () {
			showToast('메시지 읽음 처리 중 오류가 발생했습니다.', 'error');
		}
	});
}

/**
 * 읽지 않은 메시지 수 업데이트
 */
function updateUnreadMessageCount() {
	$.ajax({
		url: '/message/get_unread_count',
		type: 'GET',
		dataType: 'json',
		success: function (response) {
			if (response.success) {
				const count = response.unread_count;
				const $badge = $('#unread-message-badge');
				const $navMessage = $('#navbarMessage');

				if (count > 0) {
					$badge.text(count).show();
					$navMessage.removeClass('border-secondary')
						.addClass('bg-warning');
					$navMessage.find('i').removeClass('text-secondary')
						.addClass('text-white');
				} else {
					$badge.hide();
					$navMessage.removeClass('bg-warning')
						.addClass('border border-secondary');
					$navMessage.find('i').removeClass('text-white')
						.addClass('text-secondary');
				}
			}
		}
	});
}

// ========================================
// 5. 유틸리티 함수
// ========================================

/**
 * 날짜 포맷팅 (YYYY-MM-DD)
 * @param {Date|string} date - 포맷팅할 날짜
 * @returns {string} - 포맷된 날짜 문자열
 */
function formatDate(date) {
	const d = new Date(date);
	const year = d.getFullYear();
	const month = String(d.getMonth() + 1).padStart(2, '0');
	const day = String(d.getDate()).padStart(2, '0');
	return `${year}-${month}-${day}`;
}

/**
 * 날짜 포맷팅 (YYYY-MM-DD HH:mm:ss)
 * @param {Date|string} date - 포맷팅할 날짜
 * @returns {string} - 포맷된 날짜시간 문자열
 */
function formatDateTime(date) {
	const d = new Date(date);
	const year = d.getFullYear();
	const month = String(d.getMonth() + 1).padStart(2, '0');
	const day = String(d.getDate()).padStart(2, '0');
	const hours = String(d.getHours()).padStart(2, '0');
	const minutes = String(d.getMinutes()).padStart(2, '0');
	const seconds = String(d.getSeconds()).padStart(2, '0');
	return `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
}

/**
 * 전화번호 포맷팅
 * @param {string} phone - 전화번호
 * @returns {string} - 포맷된 전화번호
 */
function formatPhone(phone) {
	if (!phone) return '';
	const cleaned = String(phone).replace(/\D/g, '');

	if (cleaned.length === 10) {
		return cleaned.replace(/(\d{3})(\d{3})(\d{4})/, '$1-$2-$3');
	} else if (cleaned.length === 11) {
		return cleaned.replace(/(\d{3})(\d{4})(\d{4})/, '$1-$2-$3');
	}
	return phone;
}

/**
 * 숫자 천단위 콤마
 * @param {number} num - 숫자
 * @returns {string} - 포맷된 숫자 문자열
 */
function numberWithCommas(num) {
	if (!num && num !== 0) return '';
	return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
}

/**
 * 문자열 자르기 (말줄임표 추가)
 * @param {string} str - 문자열
 * @param {number} maxLength - 최대 길이
 * @returns {string} - 잘린 문자열
 */
function truncateString(str, maxLength) {
	if (!str) return '';
	if (str.length <= maxLength) return str;
	return str.substring(0, maxLength) + '...';
}

/**
 * 로딩 스피너 표시/숨김
 * @param {string} elementId - 스피너 요소 ID
 * @param {boolean} show - 표시 여부
 */
function toggleSpinner(elementId, show) {
	const spinner = document.getElementById(elementId);
	if (spinner) {
		spinner.style.display = show ? 'flex' : 'none';
	}
}

/**
 * 현재 메뉴 활성화 표시
 */
function setActiveMenu() {
	const currentPath = window.location.pathname;
	$('.nav-link').each(function () {
		const href = $(this).attr('href');
		if (href && currentPath.includes(href)) {
			$(this).addClass('active');
		}
	});
}

/**
 * 이미지 미리보기
 * @param {HTMLInputElement} input - 파일 입력 요소
 * @param {string} previewId - 미리보기 이미지 요소 ID
 */
function previewImage(input, previewId) {
	if (input.files && input.files[0]) {
		const reader = new FileReader();
		reader.onload = function (e) {
			const preview = document.getElementById(previewId);
			if (preview) {
				if (preview.tagName === 'IMG') {
					preview.src = e.target.result;
				} else {
					preview.style.backgroundImage = `url(${e.target.result})`;
				}
			}
		};
		reader.readAsDataURL(input.files[0]);
	}
}

// ========================================
// 6. 관리자 로그인 기능
// ========================================

/**
 * 관리자 계정으로 돌아가기
 */
function returnToAdmin() {
	showConfirmModal('돌아가기', '관리자 계정으로 돌아가시겠습니까?', function () {
		$.ajax({
			url: '/user_management/return_to_admin',
			type: 'POST',
			dataType: 'json',
			success: function (response) {
				if (response.success) {
					location.reload();
				} else {
					showToast(response.message || '오류가 발생했습니다.', 'error');
				}
			},
			error: function () {
				showToast('오류가 발생했습니다.', 'error');
			}
		});
	});
}

// ========================================
// 7. 문서 준비 완료 시 실행
// ========================================

$(document).ready(function () {
	// 조직 관리 기능 초기화
	initOrgSelector();
	initOrgSearch();

	// 현재 메뉴 활성화
	setActiveMenu();

	// 툴팁 초기화
	const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
	tooltipTriggerList.map(function (tooltipTriggerEl) {
		return new bootstrap.Tooltip(tooltipTriggerEl);
	});

	// 팝오버 초기화
	const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
	popoverTriggerList.map(function (popoverTriggerEl) {
		return new bootstrap.Popover(popoverTriggerEl);
	});

	// AJAX 전역 설정
	$.ajaxSetup({
		headers: {
			'X-Requested-With': 'XMLHttpRequest'
		},
		error: function (xhr, status, error) {
			if (xhr.status === 401) {
				showToast('로그인이 필요합니다.', 'warning');
				setTimeout(function () {
					location.href = '/login';
				}, 1500);
			} else if (xhr.status === 403) {
				showToast('접근 권한이 없습니다.', 'error');
			} else if (xhr.status === 500) {
				showToast('서버 오류가 발생했습니다.', 'error');
			}
		}
	});

	// 헤더의 문자전송 버튼 클릭 이벤트
	$('#navbarSend, #buttonSend').on('click', function(e) {
		e.preventDefault();
		handleNavbarSend();
	});

	// 헤더의 문자전송 버튼 클릭 이벤트 https://pf.kakao.com/_KGxhtn
	$('#navbarKakao').on('click', function(e) {
		e.preventDefault();
		open('https://pf.kakao.com/_KGxhtn', 'sendPopup', 'width=500,height=850,scrollbars=yes,resizable=yes');
	});


	/**
	 * 헤더의 문자전송 버튼 클릭 처리
	 */
	function handleNavbarSend() {
		// 회원 관리 페이지인지 확인
		openSendPopup([]);
	}

	// 체크박스 선택 변경 시 버튼 상태 업데이트
	$(document).on('change', '.member-checkbox, #selectAllCheckbox', function() {
		updateSendButtonState();
	});

	// member.js의 체크박스 이벤트와 연동하기 위한 추가 이벤트 리스너
	$(document).on('DOMSubtreeModified propertychange', function() {
		// DOM 변경 감지 후 약간의 지연을 두고 버튼 상태 업데이트
		setTimeout(updateSendButtonState, 100);
	});

	// 그리드 새로고침 후 버튼 상태 업데이트를 위한 MutationObserver
	if (window.MutationObserver) {
		const observer = new MutationObserver(function(mutations) {
			let shouldUpdate = false;
			mutations.forEach(function(mutation) {
				if (mutation.type === 'childList' &&
					(mutation.target.id === 'memberGrid' ||
						mutation.target.closest('#memberGrid'))) {
					shouldUpdate = true;
				}
			});
			if (shouldUpdate) {
				setTimeout(updateSendButtonState, 200);
			}
		});

		observer.observe(document.body, {
			childList: true,
			subtree: true
		});
	}

	// 초기 버튼 상태 설정
	updateSendButtonState();


	$('#btnSendMember').on('click', function() {
		handleSendMember();
	});


});




/**
 * 선택된 회원 정보 수집 (ParamQuery Grid 기반으로 수정)
 */
function getSelectedMembers() {
	const selectedMembers = [];
	const processedMemberIds = new Set();

	// 고유한 체크된 체크박스만 처리
	$('.member-checkbox:checked').each(function() {
		const memberIdx = $(this).data('member-idx');

		// 중복 제거
		if (processedMemberIds.has(memberIdx)) {
			return true; // continue
		}

		processedMemberIds.add(memberIdx);

		// 그리드에서 해당 회원의 데이터 찾기
		if (window.memberGrid && window.memberGrid.length > 0) {
			const gridData = window.memberGrid.pqGrid('option', 'dataModel.data');
			const memberData = gridData.find(row => row.member_idx == memberIdx);

			if (memberData) {
				selectedMembers.push({
					member_idx: memberData.member_idx,
					name: memberData.member_name || '',
					phone: memberData.member_phone || '',
					area_name: memberData.area_name || ''
				});
			}
		}
	});

	return selectedMembers;
}



/**
 * 선택문자 버튼 상태 업데이트 (개선된 버전)
 */
function updateSendButtonState() {
	// DOM이 완전히 로드될 때까지 잠시 대기
	setTimeout(function() {
		// 고유한 체크된 체크박스 수 계산
		const uniqueCheckedBoxes = [];
		const seenMemberIds = new Set();

		$('.member-checkbox:checked').each(function() {
			const memberIdx = $(this).data('member-idx');
			if (memberIdx && !seenMemberIds.has(memberIdx)) {
				seenMemberIds.add(memberIdx);
				uniqueCheckedBoxes.push(this);
			}
		});

		const selectedCount = uniqueCheckedBoxes.length;
		const sendButton = $('#btnSendMember');

		if (selectedCount > 0) {
			sendButton.prop('disabled', false);
			sendButton.html('<i class="bi bi-chat-dots"></i> 선택문자');
		} else {
			sendButton.prop('disabled', true);
			sendButton.html('<i class="bi bi-chat-dots"></i> 선택문자');
		}
	}, 50);
}



/**
 * 선택문자 발송 처리
 */
function handleSendMember() {
	const selectedMembers = getSelectedMembers();

	if (selectedMembers.length === 0) {
		showToast('발송할 회원을 선택해주세요.', 'warning');
		return;
	}

	// 전화번호가 없는 회원 체크
	const membersWithoutPhone = selectedMembers.filter(member => !member.phone || member.phone.trim() === '');

	if (membersWithoutPhone.length > 0) {
		const memberNames = membersWithoutPhone.map(member => member.name).join(', ');
		showConfirmModal(
			'전화번호 누락 회원 확인',
			`다음 회원들은 전화번호가 없어 발송 대상에서 제외됩니다.\n${memberNames}\n\n계속 진행하시겠습니까?`,
			function() {
				openSendPopup(selectedMembers.filter(member => member.phone && member.phone.trim() !== ''));
			}
		);
	} else {
		openSendPopup(selectedMembers);
	}
}

/**
 * 문자 발송 팝업 열기
 */
function openSendPopup(selectedMembers) {
	// 팝업 창 열기
	const popupWindow = window.open('', 'sendPopup', 'width=1400,height=850,scrollbars=yes,resizable=yes');

	// 임시 폼 생성하여 POST로 데이터 발송
	const tempForm = $('<form>', {
		'method': 'POST',
		'action': '/send/popup',
		'target': 'sendPopup'
	});

	// 선택된 회원이 있는 경우에만 member_ids 전송
	if (selectedMembers && selectedMembers.length > 0) {
		const memberIds = selectedMembers.map(member => member.member_idx);
		memberIds.forEach(id => {
			tempForm.append($('<input>', {
				'type': 'hidden',
				'name': 'member_ids[]',
				'value': id
			}));
		});
	}

	$('body').append(tempForm);
	tempForm.submit();
	tempForm.remove();

	// 팝업이 닫힐 때까지 포커스 유지
	const checkClosed = setInterval(function() {
		if (popupWindow.closed) {
			clearInterval(checkClosed);
			// 팝업이 닫힌 후 필요한 작업 수행 (예: 목록 새로고침)
			// location.reload(); // 필요시 주석 해제
		}
	}, 1000);
}
