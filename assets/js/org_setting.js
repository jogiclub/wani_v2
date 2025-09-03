'use strict'

$(document).ready(function() {

	// 조직 선택 이벤트 (조직목록에서 클릭 시 조직 변경)
	$(document).on('click', '.org-selector-item', function(e) {
		e.preventDefault();

		const orgId = $(this).data('org-id');
		const orgName = $(this).data('org-name');

		if (!orgId) {
			showToast('조직 정보를 가져올 수 없습니다.');
			return;
		}

		// 이미 선택된 조직인 경우 처리하지 않음
		if ($(this).hasClass('active')) {
			return;
		}

		// 조직 변경을 위한 폼 제출
		const form = $('<form>', {
			'method': 'POST',
			'action': window.location.href
		});

		form.append($('<input>', {
			'type': 'hidden',
			'name': 'org_id',
			'value': orgId
		}));

		$('body').append(form);
		form.submit();
	});

// 조직 정보 저장
	$('#orgSettingForm').on('submit', function(e) {
		e.preventDefault();

		const formData = {
			org_id: $('#org_id').val(),
			org_name: $('#org_name').val().trim(),
			org_type: $('#org_type').val(),
			org_desc: $('#org_desc').val().trim(),
			leader_name: $('#leader_name').val().trim(),
			new_name: $('#new_name').val().trim()
		};

		// 필수 항목 검증
		if (!formData.org_name) {
			showToast('조직명을 입력해주세요.');
			$('#org_name').focus();
			return;
		}

		if (!formData.leader_name) {
			showToast('리더 호칭을 입력해주세요.');
			$('#leader_name').focus();
			return;
		}

		if (!formData.new_name) {
			showToast('신규 회원 호칭을 입력해주세요.');
			$('#new_name').focus();
			return;
		}

		// 저장 중 상태 표시
		const submitBtn = $(this).find('button[type="submit"]');
		const originalText = submitBtn.html();
		submitBtn.prop('disabled', true).html('<i class="bi bi-hourglass-split"></i> 저장 중...');

		$.ajax({
			url: '/org/update_org_info',
			method: 'POST',
			data: formData,
			dataType: 'json',
			success: function(response) {
				if (response.success) {
					showToast('조직 정보가 저장되었습니다.');
					// 헤더의 조직명 업데이트
					$('#current-org-btn').text(formData.org_name);
				} else {
					showToast(response.message || '조직 정보 저장에 실패했습니다.');
				}
			},
			error: function(xhr, status, error) {
				console.error('Error:', error);
				showToast('조직 정보 저장 중 오류가 발생했습니다.');
			},
			complete: function() {
				submitBtn.prop('disabled', false).html(originalText);
			}
		});
	});

	// 아이콘 업로드
	$('#uploadIconBtn').on('click', function() {
		const fileInput = $('#orgIconFile')[0];
		const file = fileInput.files[0];

		if (!file) {
			showToast('아이콘 파일을 선택해주세요.');
			return;
		}

		// 파일 형식 검증
		const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
		if (!allowedTypes.includes(file.type)) {
			showToast('JPG 또는 PNG 파일만 업로드 가능합니다.');
			return;
		}

		// 파일 크기 검증 (2MB)
		if (file.size > 2 * 1024 * 1024) {
			showToast('파일 크기는 2MB 이하여야 합니다.');
			return;
		}

		const formData = new FormData();
		formData.append('org_icon', file);
		formData.append('org_id', $('#org_id').val());

		const uploadBtn = $(this);
		const originalText = uploadBtn.html();
		uploadBtn.prop('disabled', true).html('<i class="bi bi-hourglass-split"></i> 업로드 중...');

		$.ajax({
			url: '/org/upload_org_icon',
			method: 'POST',
			data: formData,
			processData: false,
			contentType: false,
			dataType: 'json',
			success: function(response) {
				if (response.success) {
					showToast('아이콘이 업로드되었습니다.');
					// 아이콘 미리보기 업데이트
					if (response.icon_url) {
						$('#orgIconPreview').attr('src', response.icon_url);
					}
				} else {
					showToast(response.message || '아이콘 업로드에 실패했습니다.');
				}
			},
			error: function(xhr, status, error) {
				console.error('Error:', error);
				showToast('아이콘 업로드 중 오류가 발생했습니다.');
			},
			complete: function() {
				uploadBtn.prop('disabled', false).html(originalText);
			}
		});
	});

	// 파일 선택 시 미리보기
	$('#orgIconFile').on('change', function() {
		const file = this.files[0];
		if (file) {
			const reader = new FileReader();
			reader.onload = function(e) {
				$('#orgIconPreview').attr('src', e.target.result);
			};
			reader.readAsDataURL(file);
		}
	});

	// 아이콘 파일 선택 시 미리보기 (새로운 코드 블록)
	$('#orgIconFile').on('change', function() {
		const file = this.files[0];
		if (file) {
			const reader = new FileReader();
			reader.onload = function(e) {
				const preview = $('#iconPreview');
				if (preview.is('img')) {
					preview.attr('src', e.target.result);
				} else {
					preview.replaceWith(
						`<img src="${e.target.result}" 
                              alt="조직 아이콘" 
                              class="circle" 
                              width="100" 
                              height="100" 
                              style="object-fit: cover; border: 1px solid #ddd;" 
                              id="iconPreview">`
					);
				}
			};
			reader.readAsDataURL(file);
		}
	});

	// 관리자 위임 모달 열기
	$('#delegateAdminBtn').on('click', function() {
		$('#delegateModal').modal('show');
	});

	// 관리자 위임 실행
	$('#confirmDelegateBtn').on('click', function() {
		const delegateEmail = $('#delegate_email').val().trim();

		if (!delegateEmail) {
			showToast('위임받을 사용자의 이메일을 입력해주세요.');
			$('#delegate_email').focus();
			return;
		}

		// 이메일 형식 검증
		const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
		if (!emailRegex.test(delegateEmail)) {
			showToast('올바른 이메일 형식을 입력해주세요.');
			$('#delegate_email').focus();
			return;
		}

		if (!confirm('정말로 관리자 권한을 위임하시겠습니까?\n위임 후 귀하의 권한은 일반 관리자로 변경됩니다.')) {
			return;
		}

		// 위임 중 상태 표시
		const delegateBtn = $(this);
		const originalText = delegateBtn.html();
		delegateBtn.prop('disabled', true).html('<i class="bi bi-hourglass-split"></i> 위임 중...');

		$.ajax({
			url: '/org/delegate_admin',
			method: 'POST',
			data: {
				org_id: $('#org_id').val(),
				delegate_email: delegateEmail
			},
			dataType: 'json',
			success: function(response) {
				if (response.success) {
					showToast('관리자 권한이 위임되었습니다.');
					$('#delegateModal').modal('hide');

					// 페이지 새로고침하여 변경사항 반영
					setTimeout(function() {
						location.reload();
					}, 1500);
				} else {
					showToast(response.message || '관리자 위임에 실패했습니다.');
				}
			},
			error: function(xhr, status, error) {
				console.error('Error:', error);
				showToast('관리자 위임 중 오류가 발생했습니다.');
			},
			complete: function() {
				delegateBtn.prop('disabled', false).html(originalText);
			}
		});
	});

	// 위임 모달이 닫힐 때 폼 초기화
	$('#delegateModal').on('hidden.bs.modal', function() {
		$('#delegate_email').val('');
	});

	// 폼 초기화
	$('#resetForm').on('click', function() {
		if (confirm('입력한 내용을 초기화하시겠습니까?')) {
			location.reload();
		}
	});

	// 초대 코드 복사
	$('#copyInviteCode').on('click', function() {
		const inviteCode = $(this).siblings('input').val();

		if (navigator.clipboard) {
			navigator.clipboard.writeText(inviteCode).then(function() {
				showToast('초대 코드가 클립보드에 복사되었습니다.');
			}).catch(function(err) {
				console.error('클립보드 복사 실패:', err);
				fallbackCopyText(inviteCode);
			});
		} else {
			fallbackCopyText(inviteCode);
		}
	});

	// 클립보드 API를 지원하지 않는 브라우저용 대체 함수
	function fallbackCopyText(text) {
		const textArea = document.createElement('textarea');
		textArea.value = text;
		textArea.style.position = 'fixed';
		textArea.style.left = '-999999px';
		textArea.style.top = '-999999px';
		document.body.appendChild(textArea);
		textArea.focus();
		textArea.select();

		try {
			document.execCommand('copy');
			showToast('초대 코드가 클립보드에 복사되었습니다.');
		} catch (err) {
			console.error('복사 실패:', err);
			showToast('복사에 실패했습니다. 수동으로 복사해주세요.');
		}

		document.body.removeChild(textArea);
	}


});
