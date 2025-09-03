'use strict'

function go_url(url) {
	location.href = url;
}

function open_url(url) {
	window.open(url, "_blank");
}

function heyToast(message, header = '알림') {
	// liveToast가 있으면 사용
	if ($('#liveToast').length > 0) {
		$('#liveToast .toast-header strong').text(header);
		$('#liveToast .toast-body').text(message);
		const toast = new bootstrap.Toast($('#liveToast')[0], {
			autohide: true,
			delay: 3000
		});
		toast.show();
		return;
	}

	// liveToast가 없으면 동적 생성
	createAndShowToast(message, header);
}


function createAndShowToast(message, header = '알림') {
	// Toast 컨테이너가 없으면 생성
	if ($('.toast-container').length === 0) {
		$('body').append('<div class="toast-container position-fixed bottom-0 end-0 p-3"></div>');
	}

	// Toast 요소 생성
	const toastHtml = `
		<div class="toast" role="alert" aria-live="assertive" aria-atomic="true">
			<div class="toast-header">
				<strong class="me-auto">${header}</strong>
				<button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
			</div>
			<div class="toast-body">${message}</div>
		</div>
	`;

	const $toast = $(toastHtml);
	$('.toast-container').append($toast);

	// Toast 표시
	const toast = new bootstrap.Toast($toast[0], {
		autohide: true,
		delay: 3000
	});
	toast.show();

	// Toast가 숨겨진 후 DOM에서 제거
	$toast.on('hidden.bs.toast', function() {
		$(this).remove();
	});
}

/**
 * Toast 메시지 표시
 */
function showToast(message, type = 'info') {
	// 1. common.js의 heyToast 함수가 있으면 사용
	if (typeof heyToast === 'function') {
		heyToast(message, '회원관리');
		return;
	}

	// 2. 공통 liveToast 사용
	if ($('#liveToast').length > 0) {
		// 타입별 아이콘 및 헤더 클래스 설정
		let icon = '';
		let headerClass = 'bg-primary text-white';

		switch(type) {
			case 'success':
				icon = '<i class="bi bi-check-circle-fill text-success me-2"></i>';
				headerClass = 'bg-success text-white';
				break;
			case 'error':
				icon = '<i class="bi bi-exclamation-triangle-fill text-danger me-2"></i>';
				headerClass = 'bg-danger text-white';
				break;
			case 'warning':
				icon = '<i class="bi bi-exclamation-triangle-fill text-warning me-2"></i>';
				headerClass = 'bg-warning text-dark';
				break;
			case 'info':
			default:
				icon = '<i class="bi bi-info-circle-fill text-info me-2"></i>';
				headerClass = 'bg-info text-white';
				break;
		}

		$('#liveToast .toast-header').removeClass().addClass('toast-header ' + headerClass);
		$('#liveToast .toast-header strong').text('회원관리');
		$('#liveToast .toast-body').html(icon + message);

		const toast = new bootstrap.Toast($('#liveToast')[0], {
			autohide: true,
			delay: 3000
		});
		toast.show();
		return;
	}

	// 3. 동적으로 toast 생성
	createAndShowToast(message, '회원관리', type);
}

// 확인 모달 표시 함수
function showConfirmModal(title, message, confirmCallback, cancelCallback = null) {
	// Bootstrap 모달이 있으면 사용
	if ($('#confirmModal').length > 0) {
		$('#confirmModal .modal-title').text(title);
		$('#confirmModal .modal-body').text(message);

		$('#confirmModal .btn-primary').off('click').on('click', function() {
			$('#confirmModal').modal('hide');
			if (typeof confirmCallback === 'function') {
				confirmCallback();
			}
		});

		$('#confirmModal .btn-secondary').off('click').on('click', function() {
			$('#confirmModal').modal('hide');
			if (typeof cancelCallback === 'function') {
				cancelCallback();
			}
		});

		$('#confirmModal').modal('show');
	} else {
		// 모달이 없으면 confirm 사용
		if (confirm(title + '\n\n' + message)) {
			if (typeof confirmCallback === 'function') {
				confirmCallback();
			}
		} else {
			if (typeof cancelCallback === 'function') {
				cancelCallback();
			}
		}
	}
}

$(document).ready(function() {
	// 통합 조직 선택 이벤트 처리 (.org-selector와 .org-selector-item 모두 처리)
	$(document).on('click', '.org-selector, .org-selector-item', function(e) {
		e.preventDefault();

		const orgId = $(this).data('org-id');
		const orgName = $(this).data('org-name');

		if (!orgId) {
			showToast('조직 정보를 가져올 수 없습니다.');
			return;
		}

		// 현재 선택된 조직과 동일한 경우 처리하지 않음
		const currentOrgBtn = $('#current-org-btn');
		if (currentOrgBtn.text().trim() === orgName || $(this).hasClass('active')) {
			return;
		}

		// 로딩 상태 표시
		const originalText = currentOrgBtn.text();
		currentOrgBtn.text('변경 중...');

		// 조직 변경을 위한 폼 생성 및 제출
		const form = $('<form>', {
			'method': 'POST',
			'action': window.location.href
		});

		// CSRF 토큰이 있으면 추가
		const csrfToken = $('meta[name="csrf-token"]').attr('content');
		if (csrfToken) {
			form.append($('<input>', {
				'type': 'hidden',
				'name': 'csrf_token',
				'value': csrfToken
			}));
		}

		form.append($('<input>', {
			'type': 'hidden',
			'name': 'org_id',
			'value': orgId
		}));

		// 페이지 새로고침 전 상태 저장
		try {
			sessionStorage.setItem('org_change_timestamp', Date.now());
			sessionStorage.setItem('selected_org_id', orgId);
			sessionStorage.setItem('selected_org_name', orgName);
		} catch (error) {
			console.warn('sessionStorage 저장 실패:', error);
		}

		// 폼을 body에 추가하고 제출
		$('body').append(form);

		// 오류 발생 시 복구를 위한 타이머
		const timeoutId = setTimeout(function() {
			currentOrgBtn.text(originalText);
			showToast('조직 변경이 지연되고 있습니다. 페이지를 새로고침해주세요.');
		}, 10000);

		// 폼 제출 전 이벤트 리스너 등록
		form.on('submit', function() {
			clearTimeout(timeoutId);
		});

		form.submit();
	});

	// 페이지 로드 시 조직 변경 성공 여부 확인
	try {
		const changeTimestamp = sessionStorage.getItem('org_change_timestamp');
		if (changeTimestamp) {
			const timeDiff = Date.now() - parseInt(changeTimestamp);
			// 5초 이내의 변경이면 성공으로 간주
			if (timeDiff < 5000) {
				const orgName = sessionStorage.getItem('selected_org_name');
				if (orgName) {
					showToast(orgName + ' 조직으로 변경되었습니다.');
				}
			}
			// 세션 스토리지 정리
			sessionStorage.removeItem('org_change_timestamp');
			sessionStorage.removeItem('selected_org_id');
			sessionStorage.removeItem('selected_org_name');
		}
	} catch (error) {
		console.warn('sessionStorage 확인 실패:', error);
	}
});
