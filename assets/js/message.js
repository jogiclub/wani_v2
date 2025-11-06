'use strict'

$(document).ready(function() {

	// 이전 읽지 않은 메시지 개수 저장 (새 메시지 감지용)
	let previousUnreadCount = null;

	// 오디오 unlock 상태
	let audioUnlocked = false;

	// 소리 재생 활성화 상태
	let soundEnabled = true;

	// 오디오 요소 미리 생성 및 로드
	let soundElement = null;

	function initAudioElement() {
		if (!soundElement) {
			soundElement = document.createElement('audio');
			soundElement.id = 'sound-ok';
			soundElement.src = '/assets/sound/sound_message.mp3';
			soundElement.preload = 'auto';
			document.body.appendChild(soundElement);
		}
		return soundElement;
	}

	// 오디오 unlock 함수 (사용자의 첫 상호작용 시 실행)
	function unlockAudio() {
		if (audioUnlocked) return;

		const audio = initAudioElement();

		// 볼륨을 0으로 설정하고 재생 시도
		const originalVolume = audio.volume;
		audio.volume = 0;

		audio.play().then(function() {
			audio.pause();
			audio.currentTime = 0;
			audio.volume = originalVolume;
			audioUnlocked = true;
			// console.log('오디오 unlock 성공');

			// unlock 이벤트 리스너 제거
			document.removeEventListener('click', unlockAudio);
			document.removeEventListener('touchstart', unlockAudio);
		}).catch(function(error) {
			console.warn('오디오 unlock 실패:', error);
		});
	}

	// 페이지 로드 시 오디오 요소 초기화
	initAudioElement();

	// 사용자의 첫 클릭/터치 시 오디오 unlock
	document.addEventListener('click', unlockAudio, { once: true });
	document.addEventListener('touchstart', unlockAudio, { once: true });

	/**
	 * 소리 재생 토글 이벤트 핸들러
	 */
	$(document).on('change', '#messageSoundToggle', function() {
		soundEnabled = $(this).is(':checked');

		const soundIcon = $('#soundIcon');

		if (soundEnabled) {
			soundIcon.removeClass('bi-volume-mute-fill').addClass('bi-volume-up-fill');

			// 토글을 켰을 때 오디오 unlock 시도
			if (!audioUnlocked) {
				unlockAudio();
			}

			if (typeof showToast === 'function') {
				showToast('메시지 알림음이 활성화되었습니다.', 'success');
			}
		} else {
			soundIcon.removeClass('bi-volume-up-fill').addClass('bi-volume-mute-fill');

			if (typeof showToast === 'function') {
				showToast('메시지 알림음이 비활성화되었습니다.', 'info');
			}
		}
	});

	// 안전한 HTML 이스케이프 함수
	function safeEscapeHtml(text) {
		if (text === null || text === undefined || text === '') {
			return '';
		}

		const str = String(text);

		const entityMap = {
			'&': '&amp;',
			'<': '&lt;',
			'>': '&gt;',
			'"': '&quot;',
			"'": '&#39;',
			'/': '&#x2F;'
		};

		return str.replace(/[&<>"'\/]/g, function(s) {
			return entityMap[s];
		});
	}

	// 안전한 메시지 데이터 검증
	function validateMessageData(message) {
		if (!message || typeof message !== 'object') {
			return false;
		}

		if (!message.idx || isNaN(parseInt(message.idx))) {
			return false;
		}

		return true;
	}

	// 상대적 시간 계산
	function getRelativeTime(messageDate) {
		try {
			const date = new Date(messageDate);
			const now = new Date();
			const diffTime = Math.abs(now - date);
			const diffDays = Math.floor(diffTime / (1000 * 60 * 60 * 24));

			if (diffDays === 0) {
				return 'TODAY';
			} else if (diffDays === 1) {
				return '1일전';
			} else {
				return diffDays + '일전';
			}
		} catch (error) {
			console.warn('Date parsing error:', error);
			return '알 수 없음';
		}
	}

	/**
	 * 새 메시지 알림 사운드 재생
	 */
	function playMessageSound() {
		// 소리 재생이 비활성화되어 있으면 재생하지 않음
		if (!soundEnabled) {
			console.log('소리 재생이 비활성화되어 있습니다.');
			return;
		}

		if (!audioUnlocked) {
			console.warn('오디오가 아직 unlock되지 않았습니다. 사용자 상호작용이 필요합니다.');
			return;
		}

		try {
			const audio = soundElement || initAudioElement();

			// 사운드 재생
			audio.currentTime = 0;
			audio.volume = 1.0;
			audio.play().catch(function(error) {
				console.warn('사운드 재생 실패:', error);
			});
		} catch (error) {
			console.error('사운드 재생 오류:', error);
		}
	}

	// 안전한 HTML 이스케이프 함수
	function safeEscapeHtml(text) {
		if (text === null || text === undefined || text === '') {
			return '';
		}

		const str = String(text);

		const entityMap = {
			'&': '&amp;',
			'<': '&lt;',
			'>': '&gt;',
			'"': '&quot;',
			"'": '&#39;',
			'/': '&#x2F;'
		};

		return str.replace(/[&<>"'\/]/g, function(s) {
			return entityMap[s];
		});
	}


	// 새 메시지 알림 사운드 재생
	function playMessageSound() {
		try {
			const audio = soundElement || initAudioElement();

			// unlock이 안 되어 있다면 자동으로 unlock 시도
			if (!audioUnlocked) {
				const originalVolume = audio.volume;
				audio.volume = 0;

				audio.play().then(function() {
					audio.pause();
					audio.currentTime = 0;
					audio.volume = originalVolume;
					audioUnlocked = true;
					console.log('오디오 자동 unlock 성공');

					// 이제 실제 사운드 재생
					audio.currentTime = 0;
					audio.volume = 1.0;
					audio.play().catch(function(error) {
						console.warn('사운드 재생 실패:', error);
					});
				}).catch(function(error) {
					console.warn('오디오 자동 unlock 실패:', error);
				});
			} else {
				// 이미 unlock 되어 있으면 바로 재생
				audio.currentTime = 0;
				audio.volume = 1.0;
				audio.play().catch(function(error) {
					console.warn('사운드 재생 실패:', error);
				});
			}
		} catch (error) {
			console.error('사운드 재생 오류:', error);
		}
	}

	// 안전한 메시지 데이터 검증
	function validateMessageData(message) {
		if (!message || typeof message !== 'object') {
			return false;
		}

		if (!message.idx || isNaN(parseInt(message.idx))) {
			return false;
		}

		return true;
	}

	// 상대적 시간 계산
	function getRelativeTime(messageDate) {
		try {
			const date = new Date(messageDate);
			const now = new Date();
			const diffTime = Math.abs(now - date);
			const diffDays = Math.floor(diffTime / (1000 * 60 * 60 * 24));

			if (diffDays === 0) {
				return 'TODAY';
			} else if (diffDays === 1) {
				return '1일전';
			} else {
				return diffDays + '일전';
			}
		} catch (error) {
			console.warn('Date parsing error:', error);
			return '알 수 없음';
		}
	}


	// 메시지 읽음 처리 함수
	function markMessageAsRead(messageIdx, accordionItem) {
		if (!messageIdx || !accordionItem) {
			console.error('Invalid parameters for markMessageAsRead');
			return;
		}

		$.ajax({
			url: '/message/mark_as_read',
			type: 'POST',
			data: { message_idx: messageIdx },
			dataType: 'json',
			timeout: 10000,
			success: function(response) {
				if (response && response.success) {
					accordionItem.addClass('message-read');

					const icon = accordionItem.find('.message-icon i');
					icon.removeClass('bi-envelope-fill text-warning')
						.addClass('bi-envelope-open-fill text-secondary');

					accordionItem.find('.mark-as-read').hide();

					updateUnreadCount();
				} else {
					console.error('Mark as read failed:', response);
				}
			},
			error: function(xhr, status, error) {
				console.error('AJAX error in markMessageAsRead:', error);
			}
		});
	}

	// 메시지 목록 업데이트
	function updateMessageList(messages, unreadCount) {
		const messageContent = $('#message-content');

		if (!messageContent.length) {
			console.error('Message content container not found');
			return;
		}

		if (!messages || !Array.isArray(messages) || messages.length === 0) {
			messageContent.html(`
                <div class="text-center text-muted py-5">
                    <i class="bi bi-envelope-open display-1"></i>
                    <p class="mt-3">수신된 메시지가 없습니다.</p>
                </div>
            `);
			updateSidebarTitle(0);
			updateBadgeCount(0);
			return;
		}

		let html = '<div class="accordion accordion-flush" id="msgList">';

		messages.forEach(function(message) {
			if (!validateMessageData(message)) {
				console.warn('Invalid message data:', message);
				return;
			}

			const messageIdx = parseInt(message.idx);
			const messageTitle = safeEscapeHtml(message.message_title || '제목 없음');
			const messageContent = safeEscapeHtml(message.message_content || '내용 없음');
			const isRead = message.read_yn === 'Y';
			const timeText = getRelativeTime(message.message_date);

			const itemClass = isRead ? 'message-read' : '';
			const iconClass = isRead ? 'bi-envelope-open-fill text-secondary' : 'bi-envelope-fill text-warning';

			html += `
                <div class="accordion-item ${itemClass}" data-message-idx="${messageIdx}">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed message-toggle d-flex" 
                                type="button" 
                                data-bs-toggle="collapse" 
                                data-bs-target="#flush-collapse-${messageIdx}" 
                                aria-expanded="false" 
                                aria-controls="flush-collapse-${messageIdx}"
                                data-message-idx="${messageIdx}">
                            <span class="message-icon">
                                <i class="bi ${iconClass} fs-5 me-2"></i>
                                ${messageTitle}
                                <small class="badge text-secondary ms-auto">${timeText}</small>
                            </span> 
                        </button>
                    </h2>
                    <div id="flush-collapse-${messageIdx}" class="accordion-collapse collapse" data-bs-parent="#msgList">
                        <div class="accordion-body">
                            <div class="msg-comment">${messageContent.replace(/\n/g, '<br>')}</div>
                            <div class="mt-3 d-flex gap-2">
                                ${!isRead ? `
                                <button class="btn btn-sm btn-outline-primary mark-as-read" data-message-idx="${messageIdx}">
                                    <i class="bi bi-check-circle me-1"></i>읽음
                                </button>
                                ` : ''}
                                <button class="btn btn-sm btn-outline-success send-message" data-message-idx="${messageIdx}">
                                	<i class="bi bi-chat-text me-1"></i>문자전송
                                	</button>
                                <button class="btn btn-sm btn-outline-danger delete-message" data-message-idx="${messageIdx}">
                                    <i class="bi bi-trash me-1"></i>삭제
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
		});

		html += '</div>';

		if (unreadCount > 0 || messages.length > 0) {
			html += '<div class="text-center mt-3 d-flex gap-2 justify-content-center">';

			if (unreadCount > 0) {
				html += `
                    <button class="btn btn-outline-primary btn-sm" id="markAllAsRead">
                        <i class="bi bi-check-circle me-1"></i>모든 메시지 읽음
                    </button>
                `;
			}

			if (messages.length > 0) {
				html += `
                    <button class="btn btn-outline-danger btn-sm" id="deleteAllMessages">
                        <i class="bi bi-trash me-1"></i>모든 메시지 삭제
                    </button>
                `;
			}

			html += '</div>';
		}

		messageContent.html(html);
		updateSidebarTitle(unreadCount);
		updateBadgeCount(unreadCount);
	}

	// 사이드바 제목 업데이트
	function updateSidebarTitle(unreadCount) {
		const sidebarTitle = $('#msgSidebarLabel');
		if (sidebarTitle.length) {
			if (unreadCount > 0) {
				sidebarTitle.html(`총 <b class="text-primary">${unreadCount}</b>개의 읽지 않은 메시지`);
			} else {
				sidebarTitle.text('메시지');
			}
		}
	}

	// 배지 카운트 업데이트
	function updateBadgeCount(count) {
		const badge = $('#unread-message-badge');
		if (badge.length) {
			if (count > 0) {
				badge.text(count).show();
			} else {
				badge.hide();
			}
		}
	}

	// 읽지 않은 메시지 수 업데이트 (새 메시지 감지 및 사운드 재생 포함)
	function updateUnreadCount() {
		$.ajax({
			url: '/message/get_messages',
			type: 'GET',
			dataType: 'json',
			timeout: 10000,
			success: function(response) {
				if (response && response.success) {
					const unreadCount = parseInt(response.unread_count) || 0;

					// 새로운 메시지가 도착했는지 확인
					if (previousUnreadCount !== null && unreadCount > previousUnreadCount) {
						// 새 메시지가 있으면 사운드 재생
						playMessageSound();
					}

					// 현재 개수를 이전 개수로 저장
					previousUnreadCount = unreadCount;

					updateBadgeCount(unreadCount);
					updateSidebarTitle(unreadCount);

					if (unreadCount === 0) {
						$('#markAllAsRead').hide();
					}
				}
			},
			error: function(xhr, status, error) {
				console.error('Failed to update unread count:', error);
			}
		});
	}

	// 메시지 목록 새로고침
	function refreshMessageList() {
		$.ajax({
			url: '/message/get_messages',
			type: 'GET',
			dataType: 'json',
			timeout: 15000,
			success: function(response) {
				if (response && response.success) {
					const messages = response.messages || [];
					const unreadCount = parseInt(response.unread_count) || 0;
					updateMessageList(messages, unreadCount);
				} else {
					console.error('Failed to get messages:', response);
				}
			},
			error: function(xhr, status, error) {
				console.error('AJAX error in refreshMessageList:', error);
				$('#message-content').html(`
                    <div class="text-center text-muted py-5">
                        <i class="bi bi-exclamation-triangle display-1"></i>
                        <p class="mt-3">메시지를 불러오는 중 오류가 발생했습니다.</p>
                        <button class="btn btn-outline-primary btn-sm" onclick="location.reload()">
                            새로고침
                        </button>
                    </div>
                `);
			}
		});
	}

	// 이벤트 핸들러 등록

	// 메시지 사이드바 열림 이벤트
	$(document).on('show.bs.offcanvas', '#msgSidebar', function() {
		refreshMessageList();
	});

	// 메시지 토글 클릭 이벤트
	$(document).on('click', '.message-toggle', function() {
		const messageIdx = $(this).data('message-idx');
		const accordionItem = $(this).closest('.accordion-item');

		if (!messageIdx || accordionItem.hasClass('message-read')) {
			return;
		}

		setTimeout(function() {
			const targetCollapse = $('#flush-collapse-' + messageIdx);
			if (targetCollapse.hasClass('show')) {
				markMessageAsRead(messageIdx, accordionItem);
			}
		}, 200);
	});

	// 읽음 버튼 클릭 이벤트
	$(document).on('click', '.mark-as-read', function(e) {
		e.stopPropagation();

		const messageIdx = $(this).data('message-idx');
		const accordionItem = $(this).closest('.accordion-item');

		if (!messageIdx || accordionItem.hasClass('message-read')) {
			return;
		}

		$.ajax({
			url: '/message/mark_as_read',
			type: 'POST',
			data: { message_idx: messageIdx },
			dataType: 'json',
			timeout: 10000,
			success: function(response) {
				if (response && response.success) {
					if (typeof showToast === 'function') {
						showToast('메시지가 읽음으로 처리되었습니다.', 'success');
					}

					accordionItem.addClass('message-read');
					const icon = accordionItem.find('.message-icon i');
					icon.removeClass('bi-envelope-fill text-warning')
						.addClass('bi-envelope-open-fill text-secondary');
					$(e.target).closest('.mark-as-read').hide();

					updateUnreadCount();
				} else {
					if (typeof showToast === 'function') {
						showToast(response.message || '처리 중 오류가 발생했습니다.', 'error');
					}
				}
			},
			error: function() {
				if (typeof showToast === 'function') {
					showToast('읽음 처리 중 오류가 발생했습니다.', 'error');
				}
			}
		});
	});

	// 모든 메시지 읽음 처리
	$(document).on('click', '#markAllAsRead', function() {
		const confirmFunction = function() {
			$.ajax({
				url: '/message/mark_all_as_read',
				type: 'POST',
				dataType: 'json',
				timeout: 15000,
				success: function(response) {
					if (response && response.success) {
						if (typeof showToast === 'function') {
							showToast(response.message || '모든 메시지가 읽음으로 처리되었습니다.', 'success');
						}

						$('.accordion-item').addClass('message-read');
						$('.message-icon i').removeClass('bi-envelope-fill text-warning')
							.addClass('bi-envelope-open-fill text-secondary');
						$('.mark-as-read').hide();
						$('#markAllAsRead').hide();

						updateUnreadCount();
					} else {
						if (typeof showToast === 'function') {
							showToast(response.message || '처리 중 오류가 발생했습니다.', 'error');
						}
					}
				},
				error: function() {
					if (typeof showToast === 'function') {
						showToast('처리 중 오류가 발생했습니다.', 'error');
					}
				}
			});
		};

		if (typeof showConfirmModal === 'function') {
			showConfirmModal('확인', '모든 메시지를 읽음으로 처리하시겠습니까?', confirmFunction);
		} else if (confirm('모든 메시지를 읽음으로 처리하시겠습니까?')) {
			confirmFunction();
		}
	});

	// 모든 메시지 삭제 처리
	$(document).on('click', '#deleteAllMessages', function() {
		const confirmFunction = function() {
			$.ajax({
				url: '/message/delete_all_messages',
				type: 'POST',
				dataType: 'json',
				timeout: 15000,
				success: function(response) {
					if (response && response.success) {
						if (typeof showToast === 'function') {
							showToast(response.message || '모든 메시지가 삭제되었습니다.', 'success');
						}

						$('#message-content').html(`
                            <div class="text-center text-muted py-5">
                                <i class="bi bi-envelope-open display-1"></i>
                                <p class="mt-3">수신된 메시지가 없습니다.</p>
                            </div>
                        `);

						updateBadgeCount(0);
						updateSidebarTitle(0);
					} else {
						if (typeof showToast === 'function') {
							showToast(response.message || '삭제 중 오류가 발생했습니다.', 'error');
						}
					}
				},
				error: function() {
					if (typeof showToast === 'function') {
						showToast('삭제 중 오류가 발생했습니다.', 'error');
					}
				}
			});
		};

		if (typeof showConfirmModal === 'function') {
			showConfirmModal(
				'경고',
				'모든 메시지를 삭제하시겠습니까?\n\n이 작업은 되돌릴 수 없습니다.',
				confirmFunction
			);
		} else if (confirm('모든 메시지를 삭제하시겠습니까?\n\n이 작업은 되돌릴 수 없습니다.')) {
			confirmFunction();
		}
	});

	// 메시지 삭제
	$(document).on('click', '.delete-message', function(e) {
		e.stopPropagation();

		const messageIdx = $(this).data('message-idx');
		const accordionItem = $(this).closest('.accordion-item');

		if (!messageIdx) {
			return;
		}

		const deleteFunction = function() {
			$.ajax({
				url: '/message/delete_message',
				type: 'POST',
				data: { message_idx: messageIdx },
				dataType: 'json',
				timeout: 10000,
				success: function(response) {
					if (response && response.success) {
						if (typeof showToast === 'function') {
							showToast(response.message || '메시지가 삭제되었습니다.', 'success');
						}

						accordionItem.fadeOut(300, function() {
							$(this).remove();

							if ($('#msgList .accordion-item').length === 0) {
								$('#message-content').html(`
                                    <div class="text-center text-muted py-5">
                                        <i class="bi bi-envelope-open display-1"></i>
                                        <p class="mt-3">수신된 메시지가 없습니다.</p>
                                    </div>
                                `);
							}

							updateUnreadCount();
						});
					} else {
						if (typeof showToast === 'function') {
							showToast(response.message || '삭제 중 오류가 발생했습니다.', 'error');
						}
					}
				},
				error: function() {
					if (typeof showToast === 'function') {
						showToast('삭제 중 오류가 발생했습니다.', 'error');
					}
				}
			});
		};

		if (typeof showConfirmModal === 'function') {
			showConfirmModal('확인', '이 메시지를 삭제하시겠습니까?', deleteFunction);
		} else if (confirm('이 메시지를 삭제하시겠습니까?')) {
			deleteFunction();
		}
	});

	// 문자전송 버튼 클릭 이벤트
	$(document).on('click', '.send-message', function(e) {
		e.stopPropagation();

		const messageIdx = $(this).data('message-idx');

		if (!messageIdx) {
			if (typeof showToast === 'function') {
				showToast('메시지 정보를 찾을 수 없습니다.', 'error');
			}
			return;
		}

		$.ajax({
			url: '/message/get_message_members',
			type: 'POST',
			data: { message_idx: messageIdx },
			dataType: 'json',
			timeout: 10000,
			success: function(response) {
				if (response && response.success) {
					if (response.members && response.members.length > 0) {
						const memberIds = response.members.map(m => m.member_idx);
						openSendPopup(memberIds);
					} else {
						if (typeof showToast === 'function') {
							showToast('해당 메시지와 연결된 회원 정보가 없습니다.', 'warning');
						}
					}
				} else {
					if (typeof showToast === 'function') {
						showToast(response.message || '회원 정보를 불러올 수 없습니다.', 'error');
					}
				}
			},
			error: function() {
				if (typeof showToast === 'function') {
					showToast('회원 정보 조회 중 오류가 발생했습니다.', 'error');
				}
			}
		});
	});

	/**
	 * 문자전송 팝업 열기 함수
	 */
	function openSendPopup(memberIds) {
		const popupWidth = 1400;
		const popupHeight = 900;
		const left = (screen.width - popupWidth) / 2;
		const top = (screen.height - popupHeight) / 2;

		const form = document.createElement('form');
		form.method = 'POST';
		form.action = 'https://wani.im/send/popup';
		form.target = 'sendPopup';
		form.style.display = 'none';

		const input = document.createElement('input');
		input.type = 'hidden';
		input.name = 'member_ids';
		input.value = JSON.stringify(memberIds);

		form.appendChild(input);
		document.body.appendChild(form);

		window.open('', 'sendPopup', `width=${popupWidth},height=${popupHeight},left=${left},top=${top},scrollbars=yes,resizable=yes`);

		form.submit();
		document.body.removeChild(form);
	}

	// 주기적 메시지 확인 (100초마다)
	setInterval(function() {
		updateUnreadCount();
	}, 100000);

	// 초기 로드 시 배지 상태 확인
	setTimeout(function() {
		updateUnreadCount();
	}, 1000);

});
