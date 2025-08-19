'use strict'


function go_url(url) {
    location.href = url;
}
function open_url(url) {
    window.open(url, "_blank");
}


function heyToast(message, header) {
    $('.toast-header strong').text(header);
    $('.toast-body').text(message);
    $('#liveToast').toast('show');
}


$(document).ready(function() {
	// 조직 선택 이벤트 처리
	$(document).on('click', '.org-selector', function(e) {
		e.preventDefault();

		const orgId = $(this).data('org-id');
		const orgName = $(this).data('org-name');

		if (!orgId) {
			alert('조직 정보를 가져올 수 없습니다.');
			return;
		}

		// 현재 선택된 조직과 동일한 경우 처리하지 않음
		const currentOrgBtn = $('#current-org-btn');
		if (currentOrgBtn.text().trim() === orgName) {
			return;
		}

		// 로딩 상태 표시
		const originalText = currentOrgBtn.text();
		currentOrgBtn.text('변경 중...');

		// CSRF 토큰 가져오기 (있는 경우)
		const csrfToken = $('meta[name="csrf-token"]').attr('content');

		// 폼 데이터 생성
		const formData = new FormData();
		formData.append('org_id', orgId);
		if (csrfToken) {
			formData.append('csrf_token', csrfToken);
		}

		// 현재 페이지로 POST 요청
		fetch(window.location.href, {
			method: 'POST',
			body: formData,
			credentials: 'same-origin'
		})
			.then(response => {
				if (response.ok) {
					// 페이지 새로고침
					window.location.reload();
				} else {
					throw new Error('조직 변경에 실패했습니다.');
				}
			})
			.catch(error => {
				console.error('Error:', error);
				alert('조직 변경 중 오류가 발생했습니다.');
				currentOrgBtn.text(originalText);
			});
	});
});
