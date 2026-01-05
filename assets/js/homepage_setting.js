/**
 * 파일 위치: assets/js/homepage_setting.js
 * 역할: 홈페이지 기본설정 페이지 JavaScript
 */

$(document).ready(function() {
	// 로고 1 업로드
	$('#uploadLogo1Btn').on('click', function() {
		uploadLogo('logo1');
	});

	// 로고 2 (파비콘) 업로드
	$('#uploadLogo2Btn').on('click', function() {
		uploadLogo('logo2');
	});

	// 로고 3 (카드이미지) 업로드
	$('#uploadLogo3Btn').on('click', function() {
		uploadLogo('logo3');
	});

	// 폼 제출
	$('#homepageSettingForm').on('submit', function(e) {
		e.preventDefault();
		saveHomepageSetting();
	});
});

/**
 * 로고 업로드
 */
function uploadLogo(logoType) {
	const fileInput = $('#' + logoType + 'File')[0];
	const file = fileInput.files[0];

	if (!file) {
		showToast('파일을 선택해주세요.');
		return;
	}

	// 파일 형식 검증
	const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
	if (!allowedTypes.includes(file.type)) {
		showToast('JPG, PNG, GIF 파일만 업로드 가능합니다.');
		return;
	}

	// 파일 크기 검증 (2MB)
	if (file.size > 2 * 1024 * 1024) {
		showToast('파일 크기는 2MB 이하여야 합니다.');
		return;
	}

	const formData = new FormData();
	formData.append('logo_file', file);
	formData.append('org_id', $('#org_id').val());
	formData.append('logo_type', logoType);

	const uploadBtn = $('#uploadLogo' + logoType.charAt(0).toUpperCase() + logoType.slice(1) + 'Btn');
	const originalText = uploadBtn.html();
	uploadBtn.prop('disabled', true).html('<i class="bi bi-hourglass-split"></i> 업로드 중...');

	$.ajax({
		url: '/homepage_setting/upload_logo',
		method: 'POST',
		data: formData,
		processData: false,
		contentType: false,
		dataType: 'json',
		success: function(response) {
			if (response.success) {
				showToast('이미지가 업로드되었습니다.', 'success');
				if (response.logo_url) {
					// 캐시 방지를 위한 타임스탬프 추가
					const imageUrl = response.logo_url + '?v=' + new Date().getTime();
					const preview = $('#' + logoType + 'Preview');

					if (preview.is('img')) {
						preview.attr('src', imageUrl);
					} else {
						preview.replaceWith(
							'<img src="' + imageUrl + '" alt="이미지" class="border" ' +
							'style="max-width: 200px; max-height: 100px; object-fit: contain;" id="' + logoType + 'Preview">'
						);
					}
					$('#' + logoType + '_current').val(response.logo_url);
				}

				// 파일 input 초기화
				fileInput.value = '';
			} else {
				showToast(response.message || '이미지 업로드에 실패했습니다.');
			}
		},
		error: function(xhr, status, error) {
			console.error('Upload error:', error);
			showToast('이미지 업로드 중 오류가 발생했습니다.');
		},
		complete: function() {
			uploadBtn.prop('disabled', false).html(originalText);
		}
	});
}

/**
 * 홈페이지 설정 저장
 */
function saveHomepageSetting() {
	const formData = {
		org_id: $('#org_id').val(),
		homepage_name: $('#homepage_name').val(),
		homepage_domain: $('#homepage_domain').val(),
		logo1_current: $('#logo1_current').val(),
		logo2_current: $('#logo2_current').val(),
		logo3_current: $('#logo3_current').val(),
		logo_color_change: $('#checkLogoColor').is(':checked') ? 'Y' : 'N',
		theme: $('input[name="theme"]:checked').val()
	};

	const submitBtn = $('#homepageSettingForm button[type="submit"]');
	const originalText = submitBtn.html();
	submitBtn.prop('disabled', true).html('<i class="bi bi-hourglass-split"></i> 저장 중...');

	$.ajax({
		url: '/homepage_setting/save',
		method: 'POST',
		data: formData,
		dataType: 'json',
		success: function(response) {
			if (response.success) {
				showToast('홈페이지 설정이 저장되었습니다.', 'success');
			} else {
				showToast(response.message || '홈페이지 설정 저장에 실패했습니다.');
			}
		},
		error: function(xhr, status, error) {
			console.error('Save error:', error);
			showToast('홈페이지 설정 저장 중 오류가 발생했습니다.');
		},
		complete: function() {
			submitBtn.prop('disabled', false).html(originalText);
		}
	});
}
