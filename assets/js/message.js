/**
 * 파일 위치: assets/js/message.js
 * 역할: 메시지 관련 JavaScript 기능
 */

$(document).ready(function() {
	// 메시지 아코디언 토글 시 읽음 처리
	$(document).on('click', '.message-toggle', function() {
		const messageIdx = $(this).data('message-idx');
		const accordionItem = $(this).closest('.accordion-item');

		// 이미 읽음 처리된 메시지인지 확인
		if (accordionItem.hasClass('message-read')) {
			return;
		}

		// 아코디언이 열릴 때만 읽음 처리
		setTimeout(function() {
			const targetCollapse = $('#flush-collapse-' + messageIdx);
			if (targetCollapse.hasClass('show')) {
				markMessageAsRead(messageIdx, accordionItem);
			}
		}, 100);
	});

	// 메시지 읽음 처리 함수
	function markMessageAsRead(messageIdx, accordionItem) {
		$.ajax({
			url: '/message/mark_as_read',
			type: 'POST',
			data: {
				message_idx: messageIdx
			},
			dataType: 'json',
			success: function(response) {
				if (response.success) {
					// 읽음 표시 추가
					accordionItem.addClass('message-read');

					// 아이콘을 읽음 상태로 변경
					accordionItem.find('.bi-envelope-fill')
						.removeClass('bi-envelope-fill text-warning')
						.addClass('bi-envelope-open-fill text-secondary');

					// 읽음 버튼 숨기기
					accordionItem.find('.mark-as-read').hide();

					// 읽지 않은 메시지 수 업데이트
					updateUnreadCount();
				}
			},
			error: function() {
				console.error('메시지 읽음 처리 중 오류가 발생했습니다.');
			}
		});
	}

	// 읽음 버튼 클릭 이벤트
	$(document).on('click', '.mark-as-read', function(e) {
		e.stopPropagation();

		const messageIdx = $(this).data('message-idx');
		const accordionItem = $(this).closest('.accordion-item');

		// 이미 읽음 처리된 메시지인지 확인
		if (accordionItem.hasClass('message-read')) {
			return;
		}

		$.ajax({
			url: '/message/mark_as_read',
			type: 'POST',
			data: {
				message_idx: messageIdx
			},
			dataType: 'json',
			success: function(response) {
				if (response.success) {
					showToast('메시지가 읽음으로 처리되었습니다.', 'success');

					// 읽음 표시 추가
					accordionItem.addClass('message-read');

					// 아이콘을 읽음 상태로 변경
					accordionItem.find('.bi-envelope-fill')
						.removeClass('bi-envelope-fill text-warning')
						.addClass('bi-envelope-open-fill text-secondary');

					// 읽음 버튼 숨기기
					$(e.target).closest('.mark-as-read').hide();

					// 읽지 않은 메시지 수 업데이트
					updateUnreadCount();
				} else {
					showToast(response.message, 'error');
				}
			},
			error: function() {
				showToast('읽음 처리 중 오류가 발생했습니다.', 'error');
			}
		});
	});

	// 모든 메시지 읽음 처리
	$(document).on('click', '#markAllAsRead', function() {
		showConfirmModal(
			'확인',
			'모든 메시지를 읽음으로 처리하시겠습니까?',
			function() {
				$.ajax({
					url: '/message/mark_all_as_read',
					type: 'POST',
					dataType: 'json',
					success: function(response) {
						if (response.success) {
							showToast(response.message, 'success');

							// 모든 메시지를 읽음 상태로 변경
							$('.accordion-item').addClass('message-read');
							$('.bi-envelope-fill')
								.removeClass('bi-envelope-fill text-warning')
								.addClass('bi-envelope-open-fill text-secondary');

							// 모든 읽음 버튼 숨기기
							$('.mark-as-read').hide();

							// 읽지 않은 메시지 수 업데이트
							updateUnreadCount();

							// 모든 읽음 처리 버튼 숨기기
							$('#markAllAsRead').hide();
						} else {
							showToast(response.message, 'error');
						}
					},
					error: function() {
						showToast('처리 중 오류가 발생했습니다.', 'error');
					}
				});
			}
		);
	});

	// 메시지 삭제
	$(document).on('click', '.delete-message', function(e) {
		e.stopPropagation();

		const messageIdx = $(this).data('message-idx');
		const accordionItem = $(this).closest('.accordion-item');

		showConfirmModal(
			'확인',
			'이 메시지를 삭제하시겠습니까?',
			function() {
				$.ajax({
					url: '/message/delete_message',
					type: 'POST',
					data: {
						message_idx: messageIdx
					},
					dataType: 'json',
					success: function(response) {
						if (response.success) {
							showToast(response.message, 'success');

							// 메시지 아이템 제거
							accordionItem.fadeOut(300, function() {
								$(this).remove();

								// 메시지가 모두 삭제되었는지 확인
								if ($('#msgList .accordion-item').length === 0) {
									$('#msgList').html(
										'<div class="text-center text-muted py-5">' +
										'<i class="bi bi-envelope-open display-1"></i>' +
										'<p class="mt-3">수신된 메시지가 없습니다.</p>' +
										'</div>'
									);
								}

								// 읽지 않은 메시지 수 업데이트
								updateUnreadCount();
							});
						} else {
							showToast(response.message, 'error');
						}
					},
					error: function() {
						showToast('삭제 중 오류가 발생했습니다.', 'error');
					}
				});
			}
		);
	});

	// 읽지 않은 메시지 수 업데이트
	function updateUnreadCount() {
		$.ajax({
			url: '/message/get_messages',
			type: 'GET',
			dataType: 'json',
			success: function(response) {
				if (response.success) {
					const unreadCount = response.unread_count;

					// 헤더의 배지 업데이트
					const badge = $('#unread-message-badge');
					if (unreadCount > 0) {
						badge.text(unreadCount).show();
					} else {
						badge.hide();
					}

					// 사이드바 헤더 업데이트
					const sidebarTitle = $('#msgSidebarLabel');
					if (unreadCount > 0) {
						sidebarTitle.html('총 <b class="text-primary">' + unreadCount + '</b>개의 읽지 않은 메시지');
					} else {
						sidebarTitle.text('메시지');
						$('#markAllAsRead').hide();
					}
				}
			},
			error: function() {
				console.error('메시지 수 업데이트 중 오류가 발생했습니다.');
			}
		});
	}

	// 페이지 로드 시 메시지 목록 새로고침 (필요한 경우)
	function refreshMessages() {
		$.ajax({
			url: '/message/get_messages',
			type: 'GET',
			dataType: 'json',
			success: function(response) {
				if (response.success) {
					// 메시지 목록이 변경된 경우 페이지 새로고침
					const currentCount = $('.accordion-item').length;
					if (currentCount !== response.messages.length) {
						location.reload();
					}
				}
			},
			error: function() {
				console.error('메시지 새로고침 중 오류가 발생했습니다.');
			}
		});
	}

	// 메시지 사이드바가 열릴 때 최신 메시지 로드
	$('#msgSidebar').on('show.bs.offcanvas', function () {
		refreshMessageList();
	});

	// 메시지 목록 새로고침
	function refreshMessageList() {
		$.ajax({
			url: '/message/get_messages',
			type: 'GET',
			dataType: 'json',
			success: function(response) {
				if (response.success) {
					updateMessageList(response.messages, response.unread_count);
				}
			},
			error: function() {
				console.error('메시지 목록 새로고침 중 오류가 발생했습니다.');
			}
		});
	}

	// 메시지 목록 UI 업데이트
	function updateMessageList(messages, unreadCount) {
		const messageContent = $('#message-content');

		if (messages && messages.length > 0) {
			let html = '<div class="accordion accordion-flush" id="msgList">';

			messages.forEach(function(message) {
				const messageDate = new Date(message.message_date);
				const now = new Date();
				const diffTime = Math.abs(now - messageDate);
				const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) - 1;

				let timeText = 'TODAY';
				if (diffDays === 1) {
					timeText = '1일전';
				} else if (diffDays > 1) {
					timeText = diffDays + '일전';
				}

				html += `
                    <div class="accordion-item" data-message-idx="${message.idx}">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed message-toggle" 
                                    type="button" 
                                    data-bs-toggle="collapse" 
                                    data-bs-target="#flush-collapse-${message.idx}" 
                                    aria-expanded="false" 
                                    aria-controls="flush-collapse-${message.idx}"
                                    data-message-idx="${message.idx}">
                                <span class="fs-5 text-warning me-2">
                                    <i class="bi bi-envelope-fill"></i>
                                </span> 
                                ${escapeHtml(message.message_title)}
                                <small class="badge text-secondary ms-auto">${timeText}</small>
                            </button>
                        </h2>
                        <div id="flush-collapse-${message.idx}" class="accordion-collapse collapse" data-bs-parent="#msgList">
                            <div class="accordion-body">
                                <span class="msg-comment">${escapeHtml(message.message_content).replace(/\n/g, '<br>')}</span><br/>
                                <div class="mt-2 d-flex gap-2">
                                    ${message.read_yn === 'N' ? `
                                    <button class="btn btn-sm btn-outline-primary mark-as-read" data-message-idx="${message.idx}">
                                        <i class="bi bi-check-circle me-1"></i>읽음
                                    </button>
                                    ` : ''}
                                    <button class="btn btn-sm btn-outline-danger delete-message" data-message-idx="${message.idx}">
                                        <i class="bi bi-trash me-1"></i>삭제
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
			});

			html += '</div>';

			if (unreadCount > 0) {
				html += `
                    <div class="text-center mt-3">
                        <button class="btn btn-outline-primary btn-sm" id="markAllAsRead">
                            모든 메시지 읽음 처리
                        </button>
                    </div>
                `;
			}

			messageContent.html(html);
		} else {
			messageContent.html(`
                <div class="text-center text-muted py-5">
                    <i class="bi bi-envelope-open display-1"></i>
                    <p class="mt-3">수신된 메시지가 없습니다.</p>
                </div>
            `);
		}

		// 헤더 타이틀 업데이트
		const sidebarTitle = $('#msgSidebarLabel');
		if (unreadCount > 0) {
			sidebarTitle.html(`총 <b class="text-primary">${unreadCount}</b>개의 읽지 않은 메시지`);
		} else {
			sidebarTitle.text('메시지');
		}

		// 배지 업데이트
		const badge = $('#unread-message-badge');
		if (unreadCount > 0) {
			badge.text(unreadCount).show();
		} else {
			badge.hide();
		}
	}

	// HTML 이스케이프 함수
	function escapeHtml(text) {
		const map = {
			'&': '&amp;',
			'<': '&lt;',
			'>': '&gt;',
			'"': '&quot;',
			"'": '&#039;'
		};
		return text.replace(/[&<>"']/g, function(m) { return map[m]; });
	}

	// 주기적으로 메시지 확인 (30초마다)
	setInterval(function() {
		updateUnreadCount();
	}, 30000); // 30초 = 30,000ms
});

