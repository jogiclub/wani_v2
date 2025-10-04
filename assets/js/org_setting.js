'use strict'

$(document).ready(function () {

	// Select2 초기화
	initializeSelect2();

	// 조직 선택 이벤트 (조직목록에서 클릭 시 조직 변경)
	$(document).on('click', '.org-selector-item', function (e) {
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

	/**
	 * Select2 초기화
	 */
	function initializeSelect2() {
		// 직위/직분 설정
		$('#position_names').select2({
			width: '100%',
			placeholder: '직위/직분을 입력하거나 선택하세요',
			tags: true,
			allowClear: false,
			tokenSeparators: [',', ' '],
			createTag: function (params) {
				const term = $.trim(params.term);
				if (term === '') {
					return null;
				}
				return {
					id: term,
					text: term,
					newTag: true
				};
			}
		});
		$('#position_names').select2Sortable();


		// 직책(그룹직책) 설정
		$('#duty_names').select2({
			width: '100%',
			placeholder: '직책을 입력하거나 선택하세요',
			tags: true,
			allowClear: false,
			tokenSeparators: [',', ' '],
			createTag: function (params) {
				const term = $.trim(params.term);
				if (term === '') {
					return null;
				}
				return {
					id: term,
					text: term,
					newTag: true
				};
			}
		});
		$('#duty_names').select2Sortable();

		// 타임라인 설정
		$('#timeline_names').select2({
			width: '100%',
			placeholder: '타임라인 이벤트를 입력하거나 선택하세요',
			tags: true,
			allowClear: false,
			tokenSeparators: [',', ' '],
			createTag: function (params) {
				const term = $.trim(params.term);
				if (term === '') {
					return null;
				}
				return {
					id: term,
					text: term,
					newTag: true
				};
			}
		});
		$('#timeline_names').select2Sortable();	// 타임라인 설정


		$('#memos_names').select2({
			width: '100%',
			placeholder: '메모 이벤트를 입력하거나 선택하세요',
			tags: true,
			allowClear: false,
			tokenSeparators: [',', ' '],
			createTag: function (params) {
				const term = $.trim(params.term);
				if (term === '') {
					return null;
				}
				return {
					id: term,
					text: term,
					newTag: true
				};
			}
		});
		$('#memos_names').select2Sortable();

		// 기존 데이터 로드
		loadExistingData();
	}

	/**
	 * 기존 데이터 로드
	 */
	function loadExistingData() {
		const orgId = $('#org_id').val();
		if (!orgId) return;

		$.ajax({
			url: '/org/get_org_detail',
			method: 'POST',
			data: {org_id: orgId},
			dataType: 'json',
			success: function (response) {
				if (response.success && response.data) {
					const data = response.data;

					// 직위/직분 데이터 로드
					if (data.position_name) {
						try {
							const positions = JSON.parse(data.position_name);
							if (Array.isArray(positions)) {
								positions.forEach(function (position) {
									const option = new Option(position, position, true, true);
									$('#position_names').append(option);
								});
								$('#position_names').trigger('change');
							}
						} catch (e) {
							console.error('직위/직분 데이터 파싱 오류:', e);
						}
					}

					// 직책 데이터 로드
					if (data.duty_name) {
						try {
							const duties = JSON.parse(data.duty_name);
							if (Array.isArray(duties)) {
								duties.forEach(function (duty) {
									const option = new Option(duty, duty, true, true);
									$('#duty_names').append(option);
								});
								$('#duty_names').trigger('change');
							}
						} catch (e) {
							console.error('직책 데이터 파싱 오류:', e);
						}
					}

					// 타임라인 데이터 로드
					if (data.timeline_name) {
						try {
							const timelines = JSON.parse(data.timeline_name);
							if (Array.isArray(timelines)) {
								timelines.forEach(function (timeline) {
									const option = new Option(timeline, timeline, true, true);
									$('#timeline_names').append(option);
								});
								$('#timeline_names').trigger('change');
							}
						} catch (e) {
							console.error('타임라인 데이터 파싱 오류:', e);
						}
					}
					// 메모 데이터 로드
					if (data.memo_name) {
						try {
							const memo_name = JSON.parse(data.memo_name);
							if (Array.isArray(memos)) {
								memos.forEach(function (memo) {
									const option = new Option(memo, memo, true, true);
									$('#memos_name').append(option);
								});
								$('#memos_name').trigger('change');
							}
						} catch (e) {
							console.error('타임라인 데이터 파싱 오류:', e);
						}
					}
				}
			},
			error: function (xhr, status, error) {
				console.error('데이터 로드 오류:', error);
			}
		});
	}

	// 조직 정보 저장
	$('#orgSettingForm').on('submit', function (e) {
		e.preventDefault();

		const formData = {
			org_id: $('#org_id').val(),
			org_name: $('#org_name').val().trim(),
			org_type: $('#org_type').val(),
			org_desc: $('#org_desc').val().trim(),
			leader_name: $('#leader_name').val().trim(),
			new_name: $('#new_name').val().trim(),
			position_names: $('#position_names').val() || [],
			duty_names: $('#duty_names').val() || [],
			timeline_names: $('#timeline_names').val() || [],
			memo_names: $('#memo_names').val() || []
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
			success: function (response) {
				if (response.success) {
					showToast('조직 정보가 저장되었습니다.');
					// 헤더의 조직명 업데이트
					$('#current-org-btn').text(formData.org_name);
				} else {
					showToast(response.message || '조직 정보 저장에 실패했습니다.');
				}
			},
			error: function (xhr, status, error) {
				console.error('Error:', error);
				showToast('조직 정보 저장 중 오류가 발생했습니다.');
			},
			complete: function () {
				submitBtn.prop('disabled', false).html(originalText);
			}
		});
	});

	// 아이콘 업로드
	$('#uploadIconBtn').on('click', function () {
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
			success: function (response) {
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
			error: function (xhr, status, error) {
				console.error('Error:', error);
				showToast('아이콘 업로드 중 오류가 발생했습니다.');
			},
			complete: function () {
				uploadBtn.prop('disabled', false).html(originalText);
			}
		});
	});

	// 파일 선택 시 미리보기
	$('#orgIconFile').on('change', function () {
		const file = this.files[0];
		if (file) {
			const reader = new FileReader();
			reader.onload = function (e) {
				$('#orgIconPreview').attr('src', e.target.result);
			};
			reader.readAsDataURL(file);
		}
	});

	// 아이콘 파일 선택 시 미리보기 (새로운 코드 블록)
	$('#orgIconFile').on('change', function () {
		const file = this.files[0];
		if (file) {
			const reader = new FileReader();
			reader.onload = function (e) {
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
	$('#delegateAdminBtn').on('click', function () {
		$('#delegateModal').modal('show');
	});

	// 관리자 위임 실행
	$('#confirmDelegateBtn').on('click', function () {
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

		// 확인 모달 대신 Bootstrap 모달 사용
		showConfirmModal(
			'관리자 권한 위임',
			'정말로 관리자 권한을 위임하시겠습니까?<br>위임 후 귀하의 권한은 일반 관리자로 변경됩니다.',
			function () {
				executeDelegateAdmin(delegateEmail);
			}
		);
	});

	/**
	 * 관리자 위임 실행
	 */
	function executeDelegateAdmin(delegateEmail) {
		// 위임 중 상태 표시
		const delegateBtn = $('#confirmDelegateBtn');
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
			success: function (response) {
				if (response.success) {
					showToast('관리자 권한이 위임되었습니다.');
					$('#delegateModal').modal('hide');

					// 페이지 새로고침하여 변경사항 반영
					setTimeout(function () {
						location.reload();
					}, 1500);
				} else {
					showToast(response.message || '관리자 위임에 실패했습니다.');
				}
			},
			error: function (xhr, status, error) {
				console.error('Error:', error);
				showToast('관리자 위임 중 오류가 발생했습니다.');
			},
			complete: function () {
				delegateBtn.prop('disabled', false).html(originalText);
			}
		});
	}

	// 위임 모달이 닫힐 때 폼 초기화
	$('#delegateModal').on('hidden.bs.modal', function () {
		$('#delegate_email').val('');
	});

	// 폼 초기화
	$('#resetForm').on('click', function () {
		showConfirmModal(
			'폼 초기화',
			'입력한 내용을 초기화하시겠습니까?',
			function () {
				location.reload();
			}
		);
	});

	// 초대 코드 복사
	$('#copyInviteCode').on('click', function () {
		const inviteCode = $(this).siblings('input').val();

		if (navigator.clipboard) {
			navigator.clipboard.writeText(inviteCode).then(function () {
				showToast('초대 코드가 클립보드에 복사되었습니다.');
			}).catch(function (err) {
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

	/**
	 * 확인 모달 표시 (Bootstrap 모달 사용)
	 */
	function showConfirmModal(title, message, onConfirm) {
		// 기존 확인 모달이 있으면 제거
		$('#confirmModal').remove();

		const modalHtml = `
			<div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalLabel" aria-hidden="true">
				<div class="modal-dialog">
					<div class="modal-content">
						<div class="modal-header">
							<h5 class="modal-title" id="confirmModalLabel">${title}</h5>
							<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
						</div>
						<div class="modal-body">
							${message}
						</div>
						<div class="modal-footer">
							<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
							<button type="button" class="btn btn-primary" id="confirmModalBtn">확인</button>
						</div>
					</div>
				</div>
			</div>
		`;

		$('body').append(modalHtml);

		// 확인 버튼 이벤트
		$('#confirmModalBtn').on('click', function () {
			$('#confirmModal').modal('hide');
			if (typeof onConfirm === 'function') {
				onConfirm();
			}
		});

		// 모달 표시
		$('#confirmModal').modal('show');

		// 모달이 닫힌 후 DOM에서 제거
		$('#confirmModal').on('hidden.bs.modal', function () {
			$(this).remove();
		});
	}


	/**
	 * 조직의 직위/직분 목록 가져오기 (다른 화면에서 사용)
	 */
	function getOrgPositions(orgId, callback) {
		$.ajax({
			url: '/org/get_org_detail',
			method: 'POST',
			data: { org_id: orgId },
			dataType: 'json',
			success: function(response) {
				if (response.success && response.data && response.data.position_name) {
					try {
						const positions = JSON.parse(response.data.position_name);
						if (typeof callback === 'function') {
							callback(positions);
						}
					} catch (e) {
						console.error('직위/직분 데이터 파싱 오류:', e);
						if (typeof callback === 'function') {
							callback([]);
						}
					}
				} else {
					if (typeof callback === 'function') {
						callback([]);
					}
				}
			},
			error: function() {
				if (typeof callback === 'function') {
					callback([]);
				}
			}
		});
	}

	/**
	 * 조직의 직책 목록 가져오기 (다른 화면에서 사용)
	 */
	function getOrgDuties(orgId, callback) {
		$.ajax({
			url: '/org/get_org_detail',
			method: 'POST',
			data: { org_id: orgId },
			dataType: 'json',
			success: function(response) {
				if (response.success && response.data && response.data.duty_name) {
					try {
						const duties = JSON.parse(response.data.duty_name);
						if (typeof callback === 'function') {
							callback(duties);
						}
					} catch (e) {
						console.error('직책 데이터 파싱 오류:', e);
						if (typeof callback === 'function') {
							callback([]);
						}
					}
				} else {
					if (typeof callback === 'function') {
						callback([]);
					}
				}
			},
			error: function() {
				if (typeof callback === 'function') {
					callback([]);
				}
			}
		});
	}

	/**
	 * 조직의 타임라인 목록 가져오기 (다른 화면에서 사용)
	 */
	function getOrgTimelines(orgId, callback) {
		$.ajax({
			url: '/org/get_org_detail',
			method: 'POST',
			data: { org_id: orgId },
			dataType: 'json',
			success: function(response) {
				if (response.success && response.data && response.data.timeline_name) {
					try {
						const timelines = JSON.parse(response.data.timeline_name);
						if (typeof callback === 'function') {
							callback(timelines);
						}
					} catch (e) {
						console.error('타임라인 데이터 파싱 오류:', e);
						if (typeof callback === 'function') {
							callback([]);
						}
					}
				} else {
					if (typeof callback === 'function') {
						callback([]);
					}
				}
			},
			error: function() {
				if (typeof callback === 'function') {
					callback([]);
				}
			}
		});
	}
function getOrgMemos(orgId, callback) {
		$.ajax({
			url: '/org/get_org_detail',
			method: 'POST',
			data: { org_id: orgId },
			dataType: 'json',
			success: function(response) {
				if (response.success && response.data && response.data.memo_name) {
					try {
						const memos = JSON.parse(response.data.memo_name);
						if (typeof callback === 'function') {
							callback(memoa);
						}
					} catch (e) {
						console.error('타임라인 데이터 파싱 오류:', e);
						if (typeof callback === 'function') {
							callback([]);
						}
					}
				} else {
					if (typeof callback === 'function') {
						callback([]);
					}
				}
			},
			error: function() {
				if (typeof callback === 'function') {
					callback([]);
				}
			}
		});
	}

	// 초대 코드 갱신
	$('#refreshInviteCode').on('click', function () {
		showConfirmModal(
			'초대 코드 갱신',
			'초대 코드를 갱신하시겠습니까?<br>기존 코드는 더 이상 사용할 수 없게 됩니다.',
			function () {
				executeRefreshInviteCode();
			}
		);
	});


	/**
	 * 초대 코드 갱신 실행
	 */
	function executeRefreshInviteCode() {
		const orgId = $('#org_id').val();

		if (!orgId) {
			showToast('조직 정보를 가져올 수 없습니다.');
			return;
		}

		const refreshBtn = $('#refreshInviteCode');
		const originalText = refreshBtn.html();
		refreshBtn.prop('disabled', true).html('<i class="bi bi-hourglass-split"></i> 갱신 중...');

		$.ajax({
			url: '/org/refresh_invite_code',
			method: 'POST',
			data: {
				org_id: orgId
			},
			dataType: 'json',
			success: function (response) {
				if (response.success) {
					showToast('초대 코드가 갱신되었습니다.');
					// 초대 코드 입력란 업데이트
					$('#inviteCodeInput').val(response.invite_code);
				} else {
					showToast(response.message || '초대 코드 갱신에 실패했습니다.');
				}
			},
			error: function (xhr, status, error) {
				console.error('Error:', error);
				showToast('초대 코드 갱신 중 오류가 발생했습니다.');
			},
			complete: function () {
				refreshBtn.prop('disabled', false).html(originalText);
			}
		});
	}

	// 전역으로 사용할 수 있도록 window 객체에 추가
	window.getOrgPositions = getOrgPositions;
	window.getOrgDuties = getOrgDuties;
	window.getOrgTimelines = getOrgTimelines;
	window.getOrgMemos = getOrgMemos;

});
