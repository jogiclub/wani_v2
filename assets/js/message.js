/**
 * 파일 위치: assets/js/message.js
 * 역할: 메시지 관련 JavaScript 기능
 */

$(document).ready(function() {

	// 안전한 HTML 이스케이프 함수
	function safeEscapeHtml(text) {
		if (text === null || text === undefined || text === '') {
			return '';
		}

		// 문자열로 강제 변환
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

		// 필수 필드 확인
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
					// 읽음 상태로 변경
					accordionItem.addClass('message-read');

					// 아이콘 변경
					const icon = accordionItem.find('.message-icon i');
					icon.removeClass('bi-envelope-fill text-warning')
						.addClass('bi-envelope-open-fill text-secondary');

					// 읽음 버튼 숨기기
					accordionItem.find('.mark-as-read').hide();

					// 카운트 업데이트
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

		// 메시지가 없는 경우
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

		// 메시지 목록 HTML 생성
		let html = '<div class="accordion accordion-flush" id="msgList">';

		messages.forEach(function(message) {
			// 메시지 데이터 검증
			if (!validateMessageData(message)) {
				console.warn('Invalid message data:', message);
				return;
			}

			// 안전한 데이터 추출
			const messageIdx = parseInt(message.idx);
			const messageTitle = safeEscapeHtml(message.message_title || '제목 없음');
			const messageContent = safeEscapeHtml(message.message_content || '내용 없음');
			const isRead = message.read_yn === 'Y';
			const timeText = getRelativeTime(message.message_date);

			// CSS 클래스 설정
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

		// 모든 읽음 처리 및 삭제 버튼
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

	// 읽지 않은 메시지 수 업데이트
	function updateUnreadCount() {
		$.ajax({
			url: '/message/get_messages',
			type: 'GET',
			dataType: 'json',
			timeout: 10000,
			success: function(response) {
				if (response && response.success) {
					const unreadCount = parseInt(response.unread_count) || 0;
					updateBadgeCount(unreadCount);
					updateSidebarTitle(unreadCount);

					// 모든 읽음 처리 버튼 상태 업데이트
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
				// 오류 시 기본 메시지 표시
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

		// 아코디언이 열릴 때만 읽음 처리
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

					// UI 업데이트
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

						// 모든 메시지 읽음 상태로 변경
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

		// 확인 모달 또는 기본 confirm 사용
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

						// 메시지 목록 초기화
						$('#message-content').html(`
                            <div class="text-center text-muted py-5">
                                <i class="bi bi-envelope-open display-1"></i>
                                <p class="mt-3">수신된 메시지가 없습니다.</p>
                            </div>
                        `);

						// 배지 및 타이틀 업데이트
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

		// 확인 모달 또는 기본 confirm 사용
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

						// 메시지 아이템 제거
						accordionItem.fadeOut(300, function() {
							$(this).remove();

							// 모든 메시지가 삭제되었는지 확인
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

		// 확인 모달 또는 기본 confirm 사용
		if (typeof showConfirmModal === 'function') {
			showConfirmModal('확인', '이 메시지를 삭제하시겠습니까?', deleteFunction);
		} else if (confirm('이 메시지를 삭제하시겠습니까?')) {
			deleteFunction();
		}
	});

	// 주기적 메시지 확인 (5분마다)
	setInterval(function() {
		updateUnreadCount();
	}, 60000); //60,000 = 1분

	// 초기 로드 시 배지 상태 확인
	setTimeout(function() {
		updateUnreadCount();
	}, 1000);
});
