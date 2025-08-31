/**
  * 역할: 회원가입 폼 처리 및 초대코드 검증
 */

$(document).ready(function() {
	// 회원가입 폼 제출 처리
	$('#joinForm').on('submit', function(e) {
		e.preventDefault();

		const formData = $(this).serialize();

		$.ajax({
			url: $(this).attr('action'),
			type: 'POST',
			data: formData,
			dataType: 'json',
			success: function(response) {
				if (response.success) {
					showToast(response.message, 'success');
					setTimeout(function() {
						window.location.href = '/login';
					}, 1500);
				} else {
					showToast(response.message, 'error');
				}
			},
			error: function() {
				showToast('서버 오류가 발생했습니다. 잠시 후 다시 시도해주세요.', 'error');
			}
		});
	});
});

/**
 * Toast 메시지 표시 함수
 */
function showToast(message, type = 'info') {
	const toastContainer = $('#toast-container');
	if (toastContainer.length === 0) {
		$('body').append('<div id="toast-container" class="toast-container position-fixed top-0 end-0 p-3"></div>');
	}

	const toastId = 'toast-' + Date.now();
	const bgClass = type === 'success' ? 'bg-success' : (type === 'error' ? 'bg-danger' : 'bg-primary');

	const toastHtml = `
        <div id="${toastId}" class="toast ${bgClass} text-white" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-body">
                ${message}
            </div>
        </div>
    `;

	$('#toast-container').append(toastHtml);
	const toastElement = new bootstrap.Toast(document.getElementById(toastId));
	toastElement.show();

	// Toast가 숨겨진 후 DOM에서 제거
	document.getElementById(toastId).addEventListener('hidden.bs.toast', function() {
		$(this).remove();
	});
}
