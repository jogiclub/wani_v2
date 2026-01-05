/**
 * 파일 위치: assets/js/homepage_setting.js
 * 역할: 홈페이지 기본설정 페이지 JavaScript
 */



// 관련 링크 데이터 캐시
var relatedLinksCache = [];


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

	// 관련 링크 초기 로드
	loadRelatedLinks();

	// 링크 추가 버튼
	$('#btnAddRelatedLink').on('click', addRelatedLink);

	// 링크 저장 버튼
	$('#btnSaveRelatedLink').on('click', saveRelatedLink);

	// 삭제 확인 버튼
	$('#btnConfirmDeleteLink').on('click', confirmDeleteLink);

	// 아이콘 파일 변경 시 업로드
	$('#edit_icon_file').on('change', uploadIconFile);

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


/**
 * 관련 링크 목록 로드
 */
function loadRelatedLinks() {
	var orgId = $('#org_id').val();
	if (!orgId) return;

	$.ajax({
		url: '/homepage_setting/get_related_links',
		method: 'POST',
		data: { org_id: orgId },
		dataType: 'json',
		success: function(response) {
			if (response.success) {
				relatedLinksCache = response.data || [];
				renderRelatedLinks(relatedLinksCache);
			}
		}
	});
}

/**
 * 관련 링크 목록 렌더링
 */
function renderRelatedLinks(links) {
	var $tbody = $('#relatedLinkBody');
	var $noLinks = $('#noRelatedLinks');
	var $table = $('#relatedLinkTable');

	$tbody.empty();

	if (!links || links.length === 0) {
		$table.hide();
		$noLinks.show();
		return;
	}

	$table.show();
	$noLinks.hide();

	for (var i = 0; i < links.length; i++) {
		var link = links[i];
		var iconHtml = link.link_icon
			? '<img src="' + link.link_icon + '?t=' + Date.now() + '" alt="" style="width: 32px; height: 32px; object-fit: contain; border-radius: 4px;">'
			: '<div class="bg-light d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; border-radius: 4px;"><i class="bi bi-link-45deg text-muted"></i></div>';

		var urlDisplay = link.link_url
			? '<a href="' + escapeHtml(link.link_url) + '" target="_blank" class="text-truncate d-inline-block" style="max-width: 120px;" title="' + escapeHtml(link.link_url) + '">' + escapeHtml(link.link_url) + '</a>'
			: '<span class="text-muted">-</span>';

		var row = '<tr data-idx="' + link.idx + '">' +
			'<td class="align-middle text-center">' + iconHtml + '</td>' +
			'<td class="align-middle">' + escapeHtml(link.link_name || '') + '</td>' +
			'<td class="align-middle">' + urlDisplay + '</td>' +
			'<td class="align-middle">' +
			'<button type="button" class="btn btn-sm btn-outline-primary btn-edit-link me-1" data-idx="' + link.idx + '" title="수정">' +
			'<i class="bi bi-pencil"></i>' +
			'</button>' +
			'<button type="button" class="btn btn-sm btn-outline-danger btn-delete-link" data-idx="' + link.idx + '" title="삭제">' +
			'<i class="bi bi-trash"></i>' +
			'</button>' +
			'</td>' +
			'</tr>';
		$tbody.append(row);
	}

	// 수정 버튼 이벤트
	$tbody.find('.btn-edit-link').off('click').on('click', function() {
		var idx = $(this).data('idx');
		var link = findLinkByIdx(idx);
		if (link) openEditLinkModal(link);
	});

	// 삭제 버튼 이벤트
	$tbody.find('.btn-delete-link').off('click').on('click', function() {
		var idx = $(this).data('idx');
		openDeleteLinkModal(idx);
	});
}

/**
 * idx로 링크 찾기
 */
function findLinkByIdx(idx) {
	for (var i = 0; i < relatedLinksCache.length; i++) {
		if (relatedLinksCache[i].idx == idx) {
			return relatedLinksCache[i];
		}
	}
	return null;
}


/**
 * 링크 추가
 */
function addRelatedLink() {
	var orgId = $('#org_id').val();
	if (!orgId) {
		showToast('조직 정보가 없습니다.');
		return;
	}

	$('#btnAddRelatedLink').prop('disabled', true);

	$.ajax({
		url: '/homepage_setting/add_related_link',
		method: 'POST',
		data: { org_id: orgId },
		dataType: 'json',
		success: function(response) {
			if (response.success) {
				showToast('링크가 추가되었습니다.', 'success');
				loadRelatedLinks();
				// 모달 열기 제거 - 바로 목록에 추가만 함
			} else {
				showToast(response.message || '링크 추가에 실패했습니다.');
			}
		},
		error: function() {
			showToast('링크 추가 중 오류가 발생했습니다.');
		},
		complete: function() {
			$('#btnAddRelatedLink').prop('disabled', false);
		}
	});
}


/**
 * 수정 모달 열기
 */
function openEditLinkModal(link) {
	$('#edit_link_idx').val(link.idx);
	$('#edit_link_name').val(link.link_name || '');
	$('#edit_link_url').val(link.link_url || '');
	$('#edit_icon_file').val('');

	var $preview = $('#edit_icon_preview');
	if (link.link_icon) {
		$preview.html('<img src="' + link.link_icon + '?t=' + Date.now() + '" alt="" style="width: 100%; height: 100%; object-fit: contain;">');
	} else {
		$preview.html('<i class="bi bi-image text-muted"></i>');
	}

	new bootstrap.Modal(document.getElementById('relatedLinkModal')).show();
}

/**
 * 링크 저장
 */
function saveRelatedLink() {
	var orgId = $('#org_id').val();
	var idx = $('#edit_link_idx').val();
	var linkName = $('#edit_link_name').val().trim();
	var linkUrl = $('#edit_link_url').val().trim();

	if (!linkName) {
		showToast('링크명을 입력해주세요.');
		return;
	}

	var $btn = $('#btnSaveRelatedLink');
	$btn.prop('disabled', true).html('<i class="bi bi-hourglass-split"></i> 저장 중...');

	$.ajax({
		url: '/homepage_setting/update_related_link',
		method: 'POST',
		data: { org_id: orgId, idx: idx, link_name: linkName, link_url: linkUrl },
		dataType: 'json',
		success: function(response) {
			if (response.success) {
				showToast('링크가 수정되었습니다.', 'success');
				loadRelatedLinks();
				bootstrap.Modal.getInstance(document.getElementById('relatedLinkModal')).hide();
			} else {
				showToast(response.message || '링크 수정에 실패했습니다.');
			}
		},
		error: function() {
			showToast('링크 수정 중 오류가 발생했습니다.');
		},
		complete: function() {
			$btn.prop('disabled', false).html('저장');
		}
	});
}

/**
 * 삭제 모달 열기
 */
function openDeleteLinkModal(idx) {
	$('#delete_link_idx').val(idx);
	new bootstrap.Modal(document.getElementById('deleteLinkModal')).show();
}

/**
 * 링크 삭제 확인
 */
function confirmDeleteLink() {
	var orgId = $('#org_id').val();
	var idx = $('#delete_link_idx').val();

	$('#btnConfirmDeleteLink').prop('disabled', true);

	$.ajax({
		url: '/homepage_setting/delete_related_link',
		method: 'POST',
		data: { org_id: orgId, idx: idx },
		dataType: 'json',
		success: function(response) {
			if (response.success) {
				showToast('링크가 삭제되었습니다.', 'success');
				loadRelatedLinks();
				bootstrap.Modal.getInstance(document.getElementById('deleteLinkModal')).hide();
			} else {
				showToast(response.message || '링크 삭제에 실패했습니다.');
			}
		},
		error: function() {
			showToast('링크 삭제 중 오류가 발생했습니다.');
		},
		complete: function() {
			$('#btnConfirmDeleteLink').prop('disabled', false);
		}
	});
}

/**
 * 아이콘 파일 업로드
 */
function uploadIconFile() {
	var fileInput = document.getElementById('edit_icon_file');
	var file = fileInput.files[0];
	var idx = $('#edit_link_idx').val();
	var orgId = $('#org_id').val();

	if (!file) return;

	var allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/svg+xml'];
	if (allowedTypes.indexOf(file.type) === -1) {
		showToast('JPG, PNG, GIF, SVG 파일만 업로드 가능합니다.');
		fileInput.value = '';
		return;
	}

	if (file.size > 1 * 1024 * 1024) {
		showToast('파일 크기는 1MB 이하여야 합니다.');
		fileInput.value = '';
		return;
	}

	var formData = new FormData();
	formData.append('icon_file', file);
	formData.append('org_id', orgId);
	formData.append('idx', idx);

	var $preview = $('#edit_icon_preview');
	$preview.html('<div class="spinner-border spinner-border-sm text-primary" role="status"></div>');

	$.ajax({
		url: '/homepage_setting/upload_related_link_icon',
		method: 'POST',
		data: formData,
		processData: false,
		contentType: false,
		dataType: 'json',
		success: function(response) {
			if (response.success && response.icon_url) {
				$preview.html('<img src="' + response.icon_url + '?t=' + Date.now() + '" alt="" style="width: 100%; height: 100%; object-fit: contain;">');
				showToast('아이콘이 업로드되었습니다.', 'success');
				loadRelatedLinks();
			} else {
				$preview.html('<i class="bi bi-image text-muted"></i>');
				showToast(response.message || '아이콘 업로드에 실패했습니다.');
			}
		},
		error: function() {
			$preview.html('<i class="bi bi-image text-muted"></i>');
			showToast('아이콘 업로드 중 오류가 발생했습니다.');
		}
	});

	fileInput.value = '';
}

/**
 * HTML 이스케이프
 */
function escapeHtml(text) {
	if (!text) return '';
	var div = document.createElement('div');
	div.textContent = text;
	return div.innerHTML;
}
