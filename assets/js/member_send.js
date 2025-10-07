/**
 * 파일 위치: assets/js/member_send.js
 * 역할: 회원 목록에서 문자 발송 기능을 처리하는 JavaScript
 */

$(document).ready(function() {
	// 선택문자 버튼 클릭 이벤트

	$('#btnSendMember').on('click', function() {
		handleSendMember();
	});

	// 헤더의 문자전송 버튼 클릭 이벤트
	$('#navbarSend, #buttonSend').on('click', function(e) {
		e.preventDefault();
		handleNavbarSend();
	});

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
});


/**
 * 헤더의 문자전송 버튼 클릭 처리
 */
function handleNavbarSend() {
	// 회원 관리 페이지인지 확인
	if (window.location.pathname.includes('/member')) {
		// 회원 관리 페이지에서는 선택된 회원이 있으면 선택문자, 없으면 전체 발송 팝업
		const selectedMembers = getSelectedMembers();

		if (selectedMembers.length > 0) {
			handleSendMember();
		} else {
			// 선택된 회원이 없으면 팝업만 열기
			openSendPopup([]);
		}
	} else {
		// 다른 페이지에서는 그냥 팝업 열기
		openSendPopup([]);
	}
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
 * 확인 모달 표시
 */
function showConfirmModal(title, message, confirmCallback, cancelCallback = null) {
	// 모달이 없으면 생성
	let confirmModal = $('#dynamicConfirmModal');
	if (confirmModal.length === 0) {
		confirmModal = $(`
            <div class="modal fade" id="dynamicConfirmModal" tabindex="-1" aria-labelledby="dynamicConfirmModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="dynamicConfirmModalLabel"></h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body"></div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" id="modalCancelBtn">취소</button>
                            <button type="button" class="btn btn-primary" id="modalConfirmBtn">확인</button>
                        </div>
                    </div>
                </div>
            </div>
        `);
		$('body').append(confirmModal);
	}

	confirmModal.find('.modal-title').text(title);
	confirmModal.find('.modal-body').html(message.replace(/\n/g, '<br>'));

	// 버튼 이벤트 재설정
	confirmModal.find('#modalConfirmBtn').off('click').on('click', function() {
		confirmModal.modal('hide');
		if (confirmCallback) {
			confirmCallback();
		}
	});

	confirmModal.find('#modalCancelBtn').off('click').on('click', function() {
		confirmModal.modal('hide');
		if (cancelCallback) {
			cancelCallback();
		}
	});

	confirmModal.modal('show');
}
