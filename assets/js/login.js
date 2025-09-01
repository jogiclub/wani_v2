/**
 * 파일 위치: assets/js/login.js
 * 역할: 로그인 및 회원가입, 새로운 조직 생성 처리
 */

$(document).ready(function() {
	// 회원가입 폼 제출 처리
	$('#joinForm').on('submit', function(e) {
		e.preventDefault();

		const formData = $(this).serialize();

		$.ajax({
			url: '/login/process',
			type: 'POST',
			data: formData,
			dataType: 'json',
			success: function(response) {
				if (response.success) {
					showToast(response.message, 'success');
					setTimeout(function() {
						window.location.href = '/main';
					}, 1500);
				} else {
					showToast(response.message, 'error');
				}
			},
			error: function(xhr, status, error) {
				showToast('서버 오류가 발생했습니다. 다시 시도해주세요.', 'error');
				console.error('Error:', error);
			}
		});
	});

	// 새로운 조직 생성 버튼 클릭
	$('#createOrgBtn').on('click', function() {
		$('#createOrgModal').modal('show');
	});

	// 조직 생성 폼 초기화
	$('#createOrgModal').on('show.bs.modal', function() {
		$('#createOrgForm')[0].reset();
	});

	// 조직 저장 버튼 클릭
	$('#saveOrgBtn').on('click', function() {
		const orgName = $('#org_name').val().trim();
		const orgType = $('#org_type').val();
		const orgDesc = $('#org_desc').val().trim();

		// 유효성 검증
		if (!orgName) {
			showToast('조직명을 입력해주세요.', 'error');
			$('#org_name').focus();
			return;
		}

		if (!orgType) {
			showToast('조직유형을 선택해주세요.', 'error');
			$('#org_type').focus();
			return;
		}

		// 저장 버튼 비활성화 및 로딩 상태
		const saveBtn = $(this);
		const originalText = saveBtn.text();
		saveBtn.prop('disabled', true).text('저장 중...');

		const formData = {
			org_name: orgName,
			org_type: orgType,
			org_desc: orgDesc
		};

		$.ajax({
			url: '/login/create_organization',
			type: 'POST',
			data: formData,
			dataType: 'json',
			success: function(response) {
				if (response.success) {
					showToast(response.message, 'success');
					$('#createOrgModal').modal('hide');

					// 조직설정 페이지로 이동
					setTimeout(function() {
						window.location.href = response.redirect_url;
					}, 1500);
				} else {
					showToast(response.message, 'error');
				}
			},
			error: function(xhr, status, error) {
				showToast('서버 오류가 발생했습니다. 다시 시도해주세요.', 'error');
				console.error('Error:', error);
			},
			complete: function() {
				// 저장 버튼 복원
				saveBtn.prop('disabled', false).text(originalText);
			}
		});
	});

	// 조직명 입력 시 글자수 제한
	$('#org_name').on('input', function() {
		const maxLength = 50;
		const currentLength = $(this).val().length;

		if (currentLength > maxLength) {
			$(this).val($(this).val().substring(0, maxLength));
		}
	});

	// 조직설명 입력 시 글자수 제한
	$('#org_desc').on('input', function() {
		const maxLength = 500;
		const currentLength = $(this).val().length;

		if (currentLength > maxLength) {
			$(this).val($(this).val().substring(0, maxLength));
		}
	});
});

/**
 * Toast 메시지 표시 함수
 */
function showToast(message, type = 'info') {
	// 기존 toast가 있다면 제거
	$('.toast').remove();

	const toastType = type === 'success' ? 'text-bg-success' :
		type === 'error' ? 'text-bg-danger' : 'text-bg-primary';

	const toastHtml = `
        <div class="toast align-items-center ${toastType} border-0 position-fixed" 
             style="top: 20px; right: 20px; z-index: 9999;" 
             role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body text-white">
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" 
                        data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    `;

	$('body').append(toastHtml);

	const toastElement = $('.toast').last()[0];
	const bsToast = new bootstrap.Toast(toastElement, {
		autohide: true,
		delay: 3000
	});

	bsToast.show();
}
