'use strict'

$(document).ready(function () {
	// ===== 전역 변수 영역 =====
	let memberGrid;                    // ParamQuery Grid 인스턴스
	let selectedOrgId = null;          // 선택된 조직 ID
	let selectedAreaIdx = null;        // 선택된 소그룹 ID
	let selectedType = null;           // 선택된 타입 ('org', 'area', 'unassigned')
	let croppieInstance = null;        // Croppie 인스턴스 (전역으로 이동)
	let splitInstance = null;          // Split.js 인스턴스

	// 메모 관련 전역 변수 추가
	let currentMemberIdx = null;       // 현재 선택된 회원 ID
	let editingMemoIdx = null;         // 현재 수정 중인 메모 ID
	let deletingMemoIdx = null;        // 현재 삭제 중인 메모 ID
	let memoTypes = [];                // 조직의 메모 항목 목록

	// 타임라인 관련 전역 변수 추가
	let currentMemberTimelineIdx = null;       // 현재 선택된 회원 ID (타임라인용)
	let editingTimelineIdx = null;             // 현재 수정 중인 타임라인 ID
	let timelineTypes = [];                    // 조직의 타임라인 호칭 목록

	let currentMemberMissionIdx = null;       // 현재 선택된 회원 ID (파송용)
	let editingTransferOrgIdx = null;       // 현재 수정 중인 파송교회 ID




	/**
	 * 회원에게 결연교회 추천 링크 전송
	 */
	let currentOfferMemberIdx = null;
	let currentOfferUrl = null;

	// 초기화 시도 (지연 로딩)
	setTimeout(function () {
		initializePage();
	}, 800);

	/**
	 * 페이지 초기화 메인 함수 (수정된 버전)
	 */
	function initializePage() {
		showAllSpinners();
		bindMissionTabEvents(); // 추가
		bindAutoMatchModalEvents(); // 추가

		if (typeof $.fn.pqGrid === 'undefined') {
			console.error('ParamQuery 라이브러리가 로드되지 않았습니다.');
			hideAllSpinners();
			showToast('ParamQuery 라이브러리 로드 실패', 'error');
			return;
		}

		if (typeof $.fn.fancytree === 'undefined') {
			console.error('Fancytree 라이브러리가 로드되지 않았습니다.');
			hideAllSpinners();
			showToast('Fancytree 라이브러리 로드 실패', 'error');
			return;
		}

		if (typeof Split === 'undefined') {
			console.error('Split.js 라이브러리가 로드되지 않았습니다.');
			hideAllSpinners();
			showToast('Split.js 라이브러리 로드 실패', 'error');
			return;
		}

		try {
			initializeSplitJS();
			initializeFancytree();
			// initializeParamQuery()는 여기서 호출하지 않고 loadMemberData()에서 처리
			bindGlobalEvents();
			setupCleanupEvents();
			initDetailTab();
			bindMemoTabEvents();
			bindTimelineTabEvents();
			bindMissionTabEvents();
		} catch (error) {
			console.error('초기화 중 오류:', error);
			hideAllSpinners();
			showToast('페이지 초기화 중 오류가 발생했습니다.', 'error');
		}
	}


	/**
	 * 파송 탭 이벤트 바인딩
	 */
	function bindMissionTabEvents() {
		// 파송 탭 클릭 시 파송교회 목록 로드
		$('#mission-tab').off('shown.bs.tab').on('shown.bs.tab', function() {
			const memberIdx = $('#member_idx').val();
			if (memberIdx) {
				currentMemberMissionIdx = memberIdx;
				loadTransferOrgList(memberIdx);
			}
		});

		// 1. 회원에게 교회추천 버튼 (변경된 ID)
		$(document).off('click', '#offerToMemberBtn').on('click', '#offerToMemberBtn', function() {
			if (!currentMemberMissionIdx) {
				showToast('회원 정보를 찾을 수 없습니다.', 'error');
				return;
			}
			sendOfferLinkToMember(currentMemberMissionIdx);
		});

		// 2. 파송교회에 회원정보 전달 버튼 (변경된 ID)
		$(document).off('click', '#offerToChurchBtn').on('click', '#offerToChurchBtn', function() {
			if (!currentMemberMissionIdx) {
				showToast('회원 정보를 찾을 수 없습니다.', 'error');
				return;
			}
			sendMemberInfoToChurch();
		});

		/**
		 * 파송교회에 회원정보 전달
		 */
		/**
		 * 파송교회에 회원정보 전달
		 */
		function sendMemberInfoToChurch() {
			// 1. 회원 정보 조회
			$.ajax({
				url: '/member/get_member_info_with_passcode',
				method: 'POST',
				data: {
					member_idx: currentMemberMissionIdx,
					org_id: selectedOrgId
				},
				dataType: 'json',
				success: function(response) {
					if (!response.success || !response.member) {
						showToast('회원 정보를 찾을 수 없습니다.', 'error');
						return;
					}

					const member = response.member;

					// 2. 선택된 파송교회 정보 조회
					$.ajax({
						url: '/member/get_selected_transfer_org',
						method: 'POST',
						data: {
							member_idx: currentMemberMissionIdx,
							org_id: selectedOrgId
						},
						dataType: 'json',
						success: function(churchResponse) {
							if (!churchResponse.success) {
								showToast('파송교회가 결정되지 않았습니다.', 'warning');
								return;
							}

							const church = churchResponse.church;

							// 3. 이메일 주소 확인 및 설정
							if (!church.transfer_org_email) {
								showToast('메일주소가 없습니다.', 'warning');
								return;
							}

							// 4. 회원 정보 URL 생성
							const memberInfoUrl = `${window.location.origin}/member_info/${selectedOrgId}/${currentMemberMissionIdx}`;

							// 5. 이메일 내용 생성 (패스코드 포함)
							const emailContent = generateEmailContent(member, church, memberInfoUrl);

							// 6. 모달에 내용 설정
							$('#emailMessage').val(emailContent);
							$('#churchEmail').val(church.transfer_org_email);

							// 7. 모달 표시
							$('#sendMemberInfoModal').modal('show');
						},
						error: function() {
							showToast('파송교회 정보 조회 중 오류가 발생했습니다.', 'error');
						}
					});
				},
				error: function() {
					showToast('회원 정보 조회 중 오류가 발생했습니다.', 'error');
				}
			});
		}


		/**
		 * 이메일 내용 생성 (패스코드 포함)
		 */
		function generateEmailContent(member, church, memberInfoUrl) {
			const churchName = church.transfer_org_name || '교회';
			const memberName = member.member_name || '회원';
			const contactPerson = church.transfer_org_manager || '담당자';
			const passcode = member.member_passcode || '';

			return `안녕하세요! ${contactPerson}님께, 군선교연합회를 통해 소중한 성도 한 분을 연결해 드립니다.

1. 성도 소개 및 간곡한 협조 요청
${memberName}님은 현재 신앙의 길에 간절함과 절실함을 품고 헌신할 교회를 찾고 계셨으며, ${churchName}에 큰 기대를 가지고 직접 선택하셨습니다.

저희는 ${memberName}님의 신앙생활이 목사님의 교회에서 흔들림 없이 굳건히 정착되기를 간절히 소망합니다. 부디 따뜻한 관심과 사랑으로 이 소중한 영혼을 품어주시고, 공동체에 잘 녹아들 수 있도록 각별히 지도하고 협력해 주시기를 간곡히 부탁드립니다.

2. 성도 정보 확인 및 정착 관리 기능 안내
아래의 URL을 클릭하시면 ${memberName}님의 기본적인 연락처 정보를 바로 확인하실 수 있습니다.
또한, 이 페이지를 통해 성도님과의 정착 과정을 기록하고 관리하실 수 있습니다.

정착 관리 기능: 해당 페이지에서 ${memberName}님이 교회에 잘 정착하고 계신지, 어떤 도움이 필요한지에 대한 메모(기록)를 남기실 수 있습니다.

활용의 중요성: 이 메모는 성도님의 영적 성장을 지속적으로 돕기 위한 중요한 자료가 되오니, 꼭 활용해 주시기를 부탁드립니다.

[성도 정보 및 정착 관리 URL]
${memberInfoUrl}

[접속 패스코드]
${passcode}

${memberName}님이 ${churchName} 공동체 안에서 믿음의 뿌리를 깊이 내리고 아름다운 신앙생활을 이어갈 수 있도록, 다시 한번 ${contactPerson}님의 협력에 깊이 감사드립니다.

주님의 은혜가 교회와 공동체 위에 늘 충만하시기를 기도합니다.`;
		}

		/**
		 * 이메일 전송 버튼 이벤트
		 */
		$(document).off('click', '#sendEmailBtn').on('click', '#sendEmailBtn', function() {
			const email = $('#churchEmail').val().trim();
			const message = $('#emailMessage').val();

			if (!email) {
				showToast('이메일 주소를 입력해주세요.', 'warning');
				return;
			}

			// 이메일 형식 검증
			const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
			if (!emailRegex.test(email)) {
				showToast('올바른 이메일 주소를 입력해주세요.', 'warning');
				return;
			}

			// 이메일 전송 요청
			$.ajax({
				url: '/member/send_member_info_email',
				method: 'POST',
				data: {
					member_idx: currentMemberMissionIdx,
					org_id: selectedOrgId,
					to_email: email,
					message: message
				},
				dataType: 'json',
				success: function(response) {
					if (response.success) {
						showToast('이메일이 전송되었습니다.', 'success');
						$('#sendMemberInfoModal').modal('hide');
					} else {
						showToast(response.message || '이메일 전송에 실패했습니다.', 'error');
					}
				},
				error: function() {
					showToast('이메일 전송 중 오류가 발생했습니다.', 'error');
				}
			});
		});


		// 3. 결연교회 자동매칭 버튼 - 모달 열기로 복원
		$(document).off('click', '#autoMatchChurchBtn').on('click', '#autoMatchChurchBtn', function() {
			if (!currentMemberMissionIdx) {
				showToast('회원 정보를 찾을 수 없습니다.', 'error');
				return;
			}
			autoMatchChurch(); // 모달 열기
		});

		// 4. 파송교회 수동추가 버튼
		$(document).off('click', '#addTransferOrgBtn').on('click', '#addTransferOrgBtn', function() {
			openTransferOrgModal('add');
		});

		// 파송교회 저장 버튼
		$(document).off('click', '#saveTransferOrgBtn').on('click', '#saveTransferOrgBtn', function() {
			saveTransferOrg();
		});

		// 파송교회 수정 버튼
		$(document).off('click', '.btn-mission-edit').on('click', '.btn-mission-edit', function() {
			const idx = $(this).data('idx');
			const churchItem = $(this).closest('.mission-church-item');
			const transferData = churchItem.data('church-data');

			if (transferData) {
				openTransferOrgModal('edit', idx, transferData);
			} else {
				showToast('파송교회 정보를 찾을 수 없습니다.', 'error');
			}
		});

		// 파송교회 삭제 버튼
		$(document).off('click', '.btn-mission-delete').on('click', '.btn-mission-delete', function() {
			const idx = $(this).data('idx');
			showDeleteTransferOrgModal(idx);
		});

		// 파송교회 삭제 확인
		$(document).off('click', '#confirmDeleteTransferOrgBtn').on('click', '#confirmDeleteTransferOrgBtn', function() {
			const idx = $(this).data('idx');
			deleteTransferOrg(idx);
		});

		// 자동매칭 모달 이벤트 바인딩
		bindAutoMatchModalEvents();
	}


	/**
	 * 파송교회 목록 로드 (선택 상태 포함)
	 */
	function loadTransferOrgList(memberIdx) {
		$.ajax({
			url: '/member/get_transfer_orgs',
			method: 'POST',
			data: {
				member_idx: memberIdx,
				org_id: selectedOrgId
			},
			dataType: 'json',
			success: function(response) {
				if (response.success) {
					renderTransferOrgList(response.data);
				} else {
					showToast(response.message || '파송교회 목록을 불러오는데 실패했습니다.', 'error');
				}
			},
			error: function() {
				showToast('파송교회 목록을 불러오는 중 오류가 발생했습니다.', 'error');
			}
		});
	}


	/**
	 * Split.js 초기화
	 */
	function initializeSplitJS() {


		try {
			splitInstance = Split(['#left-pane', '#right-pane'], {
				sizes: [15, 85],              // 초기 크기 비율 (왼쪽 25%, 오른쪽 75%)
				minSize: [50, 50],          // 최소 크기 (px)
				gutterSize: 7,                // divider 두께
				cursor: 'col-resize',         // 커서 스타일
				direction: 'horizontal',      // 수평 분할
				onDragEnd: function(sizes) {
					// 크기 조정 완료 시 그리드 리프레시
					if (memberGrid) {
						try {
							memberGrid.pqGrid("refresh");
						} catch (error) {
							console.error('그리드 리프레시 실패:', error);
						}
					}
					// 로컬스토리지에 크기 저장 (선택사항)
					localStorage.setItem('member-split-sizes', JSON.stringify(sizes));
				}
			});
			// 이전에 저장된 크기가 있다면 복원
			const savedSizes = localStorage.getItem('member-split-sizes');
			if (savedSizes) {
				try {
					const sizes = JSON.parse(savedSizes);
					splitInstance.setSizes(sizes);
				} catch (error) {
					console.error('저장된 크기 복원 실패:', error);
				}
			}


		} catch (error) {
			console.error('Split.js 초기화 실패:', error);
			showToast('화면 분할 기능 초기화에 실패했습니다.', 'error');
		}
	}


	/**
	 * 전역 이벤트 바인딩
	 */
	function bindGlobalEvents() {
		// 회원 삭제 버튼
		$('#btnDeleteMember').on('click', function () {
			const selectedMembers = getSelectedMembers();
			if (selectedMembers.length === 0) {
				showToast('삭제할 회원을 선택해주세요.', 'warning');
				return;
			}
			deleteSelectedMembers(selectedMembers);
		});

		// 회원 이동 버튼
		$('#btnMoveMember').on('click', function () {
			const selectedMembers = getSelectedMembers();
			if (selectedMembers.length === 0) {
				showToast('이동할 회원을 선택해주세요.', 'warning');
				return;
			}
			moveSelectedMembers(selectedMembers);
		});

		/**
		 * 엑셀편집 버튼 클릭 이벤트 바인딩 (bindGlobalEvents 함수 내부에 추가)
		 */
		$('#btnExcelEdit').on('click', function (e) {
			e.preventDefault();
			openExcelEditPopup();
		});

		// 회원 저장 버튼
		$('#btnSaveMember').on('click', function () {
			saveMember();
		});

		// 회원 추가 버튼 (이벤트 위임 사용)
		$(document).on('click', '#btnAddMember', function (e) {
			e.preventDefault();
			handleAddMemberClick();
		});

		// 엑셀 다운로드 버튼 이벤트 추가
		$(document).on('click', '#btnExcelDownload', function (e) {
			e.preventDefault();
			exportMemberToExcel();
		});

		// 회원 검색 기능 바인딩
		bindMemberSearchEvents();

		// 윈도우 리사이즈 이벤트
		$(window).on('resize', debounce(function() {
			if (memberGrid) {
				try {
					memberGrid.pqGrid("refresh");
				} catch (error) {
					console.error('윈도우 리사이즈 시 그리드 리프레시 실패:', error);
				}
			}
		}, 250));
	}

	/**
	 * 타임라인 탭 이벤트 바인딩
	 */
	function bindTimelineTabEvents() {
		// 타임라인 탭 클릭 시 타임라인 목록 로드
		$('#timeline-tab').off('shown.bs.tab').on('shown.bs.tab', function() {
			const memberIdx = $('#member_idx').val();
			if (memberIdx) {
				currentMemberTimelineIdx = memberIdx;
				loadTimelineTypes();
				loadTimelineList(memberIdx);
			}
		});

		// 타임라인 일괄추가 버튼 클릭
		$(document).off('click', '#addTimelineBtn').on('click', '#addTimelineBtn', function() {
			saveTimeline();
		});

		// 타임라인 목록 내 버튼 이벤트 (동적 요소용 이벤트 위임)
		$(document).off('click', '.btn-timeline-edit').on('click', '.btn-timeline-edit', function() {
			const idx = $(this).data('idx');
			const timelineData = getTimelineDataFromElement($(this).closest('.timeline-item'));
			startEditTimeline(idx, timelineData);
		});

		$(document).off('click', '.btn-timeline-delete').on('click', '.btn-timeline-delete', function() {
			const idx = $(this).data('idx');
			showDeleteTimelineModal(idx);
		});

		$(document).off('click', '.btn-timeline-save').on('click', '.btn-timeline-save', function() {
			const idx = $(this).data('idx');
			const timelineData = getTimelineDataFromEditForm($(this).closest('.timeline-item'));
			updateTimeline(idx, timelineData);
		});

		$(document).off('click', '.btn-timeline-cancel').on('click', '.btn-timeline-cancel', function() {
			cancelEditTimeline();
		});

		// Enter 키로 타임라인 일괄추가 기능
		$(document).off('keydown', '#newTimelineContent').on('keydown', '#newTimelineContent', function(e) {
			if (e.ctrlKey && e.keyCode === 13) {
				saveTimeline();
			}
		});

		// 타임라인 수정 중 ESC 키로 취소
		$(document).off('keydown', '.timeline-content-edit').on('keydown', '.timeline-content-edit', function(e) {
			if (e.keyCode === 27) {
				cancelEditTimeline();
			}
		});

		// 타임라인 수정 중 Ctrl + Enter로 저장
		$(document).off('keydown', '.timeline-content-edit').on('keydown', '.timeline-content-edit', function(e) {
			if (e.ctrlKey && e.keyCode === 13) {
				const idx = $(this).closest('.timeline-item').data('idx');
				const timelineData = getTimelineDataFromEditForm($(this).closest('.timeline-item'));
				updateTimeline(idx, timelineData);
			}
		});
	}

	/**
	 * 조직의 타임라인 호칭 목록 로드
	 */
	function loadTimelineTypes() {
		if (!selectedOrgId) return;

		$.ajax({
			url: '/member/get_timeline_types',
			method: 'POST',
			data: { org_id: selectedOrgId },
			dataType: 'json',
			success: function(response) {
				if (response.success) {
					timelineTypes = response.data || [];
					populateTimelineTypeSelect();
				} else {
					console.error('타임라인 호칭 로드 실패:', response.message);
					timelineTypes = [];
					populateTimelineTypeSelect();
				}
			},
			error: function() {
				console.error('타임라인 호칭 로드 중 오류 발생');
				timelineTypes = [];
				populateTimelineTypeSelect();
			}
		});
	}

	/**
	 * 타임라인 타입 선택박스 채우기
	 */
	function populateTimelineTypeSelect() {
		const select = $('#newTimelineType');
		select.html('<option value="">항목 선택</option>');

		timelineTypes.forEach(function(type) {
			select.append(`<option value="${escapeHtml(type)}">${escapeHtml(type)}</option>`);
		});
	}

	/**
	 * 타임라인 목록 로드
	 */
	function loadTimelineList(memberIdx) {
		if (!memberIdx) return;

		$.ajax({
			url: '/member/get_timeline_list',
			method: 'POST',
			data: {
				member_idx: memberIdx,
				org_id: selectedOrgId,
				page: 1,
				limit: 50
			},
			dataType: 'json',
			success: function(response) {
				if (response.success) {
					renderTimelineList(response.data);
				} else {
					showToast('타임라인 목록을 불러오는데 실패했습니다.', 'error');
				}
			},
			error: function() {
				showToast('타임라인 목록을 불러오는데 실패했습니다.', 'error');
			}
		});
	}

	/**
	 * 타임라인 목록 렌더링
	 */
	function renderTimelineList(timelineList) {
		const timelineListContainer = $('#timelineList');
		timelineListContainer.empty();

		if (!timelineList || timelineList.length === 0) {
			timelineListContainer.html('<div class="text-center text-muted py-3" id="emptyTimelineMessage">등록된 타임라인이 없습니다.</div>');
			return;
		}

		// 날짜별로 정렬 (최신순)
		timelineList.sort(function(a, b) {
			return new Date(b.timeline_date) - new Date(a.timeline_date);
		});

		timelineList.forEach(function(timeline, index) {
			const timelineHtml = createTimelineItemHtml(timeline);
			timelineListContainer.append(timelineHtml);

			// 타임라인 연결선 추가 (마지막 항목이 아닌 경우)
			if (index < timelineList.length - 1) {
				timelineListContainer.append('<div class="timeline-connector">|</div>');
			}
		});
	}

	/**
	 * 타임라인 아이템 HTML 생성
	 */
	function createTimelineItemHtml(timeline) {
		const formattedDate = formatTimelineDate(timeline.timeline_date);
		const regDate = formatMemoDateTime(timeline.regi_date);

		return `
			<div class="timeline-item" data-idx="${timeline.idx}">
				<div class="row align-items-center">				
					<div class="col-9">
						<div class="timeline-content">
							<span class="timeline-date">${formattedDate}</span>
							<span class="timeline-type">${escapeHtml(timeline.timeline_type)}</span>
							<span class="timeline-text">${escapeHtml(timeline.timeline_content || '')}</span>
						</div>						
					</div>
					<div class="col-3 d-flex justify-content-end">
						<div class="btn-group">
							<button type="button" class="btn btn-sm btn-outline-secondary btn-timeline-edit" data-idx="${timeline.idx}">수정</button>
							<button type="button" class="btn btn-sm btn-outline-danger btn-timeline-delete" data-idx="${timeline.idx}">삭제</button>
						</div>
					</div>
				</div>
			</div>
		`;
	}

	/**
	 * 타임라인 저장
	 */
	function saveTimeline() {
		const timelineType = $('#newTimelineType').val().trim();
		const timelineDate = $('#newTimelineDate').val();
		const timelineContent = $('#newTimelineContent').val().trim();

		if (!timelineType) {
			showToast('타임라인 항목을 선택해주세요.', 'warning');
			return;
		}

		if (!timelineDate) {
			showToast('날짜를 선택해주세요.', 'warning');
			return;
		}

		if (!currentMemberTimelineIdx) {
			showToast('회원 정보를 찾을 수 없습니다.', 'error');
			return;
		}

		$.ajax({
			url: '/member/save_timeline',
			method: 'POST',
			data: {
				member_idx: currentMemberTimelineIdx,
				timeline_type: timelineType,
				timeline_date: timelineDate,
				timeline_content: timelineContent,
				org_id: selectedOrgId
			},
			dataType: 'json',
			success: function(response) {
				if (response.success) {
					$('#newTimelineType').val('');
					$('#newTimelineDate').val('');
					$('#newTimelineContent').val('');
					loadTimelineList(currentMemberTimelineIdx);
					showToast('타임라인이 저장되었습니다.', 'success');
				} else {
					showToast(response.message || '타임라인 저장에 실패했습니다.', 'error');
				}
			},
			error: function() {
				showToast('타임라인 저장에 실패했습니다.', 'error');
			}
		});
	}

	/**
	 * 타임라인 수정 시작
	 */
	function startEditTimeline(idx, timelineData) {
		editingTimelineIdx = idx;
		const timelineItem = $(`.timeline-item[data-idx="${idx}"]`);

		const editHtml = createTimelineEditHtml(timelineData);
		timelineItem.find('.timeline-content').parent().replaceWith(editHtml);
		timelineItem.find('.btn-group').hide();
	}

	/**
	 * 타임라인 수정 폼 HTML 생성
	 */
	function createTimelineEditHtml(timelineData) {
		let typeOptions = '<option value="">타임라인 항목 선택</option>';
		timelineTypes.forEach(function(type) {
			const selected = type === timelineData.timeline_type ? 'selected' : '';
			typeOptions += `<option value="${escapeHtml(type)}" ${selected}>${escapeHtml(type)}</option>`;
		});

		return `
			<div class="col-8">
				<div class="timeline-edit-form">
					<div class="row mb-2">
						<div class="col-4">
							<select class="form-select form-select-sm timeline-type-edit">
								${typeOptions}
							</select>
						</div>
						<div class="col-4">
							<input type="date" class="form-control form-control-sm timeline-date-edit" value="${timelineData.timeline_date}">
						</div>
						<div class="col-4">
							<input type="text" class="form-control form-control-sm timeline-content-edit" value="${escapeHtml(timelineData.timeline_content || '')}" placeholder="내용 입력">
						</div>
					</div>
					<div class="text-end">
						<button type="button" class="btn btn-sm btn-success btn-timeline-save" data-idx="${editingTimelineIdx}">저장</button>
						<button type="button" class="btn btn-sm btn-secondary btn-timeline-cancel">취소</button>
					</div>
				</div>
			</div>
		`;
	}

	/**
	 * 타임라인 수정 취소
	 */
	function cancelEditTimeline() {
		if (editingTimelineIdx) {
			loadTimelineList(currentMemberTimelineIdx);
			editingTimelineIdx = null;
		}
	}

	/**
	 * 타임라인 업데이트
	 */
	function updateTimeline(idx, timelineData) {
		if (!timelineData.timeline_type.trim()) {
			showToast('타임라인 항목을 선택해주세요.', 'warning');
			return;
		}

		if (!timelineData.timeline_date) {
			showToast('날짜를 선택해주세요.', 'warning');
			return;
		}

		$.ajax({
			url: '/member/update_timeline',
			method: 'POST',
			data: {
				idx: idx,
				timeline_type: timelineData.timeline_type.trim(),
				timeline_date: timelineData.timeline_date,
				timeline_content: timelineData.timeline_content.trim(),
				org_id: selectedOrgId
			},
			dataType: 'json',
			success: function(response) {
				if (response.success) {
					loadTimelineList(currentMemberTimelineIdx);
					editingTimelineIdx = null;
					showToast('타임라인이 수정되었습니다.', 'success');
				} else {
					showToast(response.message || '타임라인 수정에 실패했습니다.', 'error');
				}
			},
			error: function() {
				showToast('타임라인 수정에 실패했습니다.', 'error');
			}
		});
	}

	/**
	 * 타임라인 삭제 확인 모달 표시
	 */
	function showDeleteTimelineModal(idx) {
		const modalHtml = `
			<div class="modal fade" id="deleteTimelineModal" tabindex="-1" aria-labelledby="deleteTimelineModalLabel" aria-hidden="true">
				<div class="modal-dialog">
					<div class="modal-content">
						<div class="modal-header">
							<h5 class="modal-title" id="deleteTimelineModalLabel">타임라인 삭제</h5>
							<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
						</div>
						<div class="modal-body">
							<p>이 타임라인을 삭제하시겠습니까?</p>
						</div>
						<div class="modal-footer">
							<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
							<button type="button" class="btn btn-danger" id="confirmDeleteTimelineBtn" data-idx="${idx}">삭제</button>
						</div>
					</div>
				</div>
			</div>
		`;

		$('#deleteTimelineModal').remove();
		$('body').append(modalHtml);

		$('#confirmDeleteTimelineBtn').on('click', function() {
			const timelineIdx = $(this).data('idx');
			deleteTimeline(timelineIdx);
		});

		$('#deleteTimelineModal').modal('show');
	}

	/**
	 * 타임라인 삭제 실행
	 */
	function deleteTimeline(idx) {
		$.ajax({
			url: '/member/delete_timeline',
			method: 'POST',
			data: {
				idx: idx,
				org_id: selectedOrgId
			},
			dataType: 'json',
			success: function(response) {
				if (response.success) {
					$('#deleteTimelineModal').modal('hide');
					loadTimelineList(currentMemberTimelineIdx);
					showToast('타임라인이 삭제되었습니다.', 'success');
				} else {
					showToast(response.message || '타임라인 삭제에 실패했습니다.', 'error');
				}
			},
			error: function() {
				showToast('타임라인 삭제에 실패했습니다.', 'error');
			}
		});
	}

	/**
	 * 타임라인용 날짜 형식화
	 */
	function formatTimelineDate(dateString) {
		if (!dateString) return '';

		const date = new Date(dateString);
		const year = date.getFullYear();
		const month = String(date.getMonth() + 1).padStart(2, '0');
		const day = String(date.getDate()).padStart(2, '0');

		return `${year}.${month}.${day}`;
	}

	/**
	 * 타임라인 요소에서 데이터 추출
	 */
	function getTimelineDataFromElement(element) {
		const content = element.find('.timeline-content');
		const timelineType = content.find('.timeline-type').text().replace(/[\[\]]/g, '');
		const timelineDate = content.find('.timeline-date').text().replace(/\./g, '-');
		const timelineText = content.find('.timeline-text').text();

		// 날짜 형식 변환 (YYYY.MM.DD -> YYYY-MM-DD)
		const dateParts = timelineDate.split('.');
		let formattedDate = timelineDate;
		if (dateParts.length === 3) {
			formattedDate = `${dateParts[0]}-${dateParts[1].padStart(2, '0')}-${dateParts[2].padStart(2, '0')}`;
		}

		return {
			timeline_type: timelineType,
			timeline_date: formattedDate,
			timeline_content: timelineText
		};
	}

	/**
	 * 수정 폼에서 타임라인 데이터 추출
	 */
	function getTimelineDataFromEditForm(element) {
		return {
			timeline_type: element.find('.timeline-type-edit').val(),
			timeline_date: element.find('.timeline-date-edit').val(),
			timeline_content: element.find('.timeline-content-edit').val()
		};
	}


	function exportMemberToExcel() {
		// 조직이 선택되지 않은 경우
		if (!selectedOrgId) {
			showToast('조직을 먼저 선택해주세요.', 'warning');
			return;
		}

		// 그리드가 초기화되지 않은 경우
		if (!memberGrid) {
			showToast('회원 데이터를 불러온 후 다시 시도해주세요.', 'warning');
			return;
		}

		try {
			// 현재 그리드 데이터 가져오기
			const gridData = memberGrid.pqGrid("option", "dataModel.data");

			if (!gridData || gridData.length === 0) {
				showToast('다운로드할 회원 데이터가 없습니다.', 'info');
				return;
			}

			// 현재 선택된 조직명 가져오기
			const selectedOrgName = $('#selectedOrgName').text().trim() || '회원목록';
			const currentDate = new Date();
			const dateStr = currentDate.getFullYear() +
				String(currentDate.getMonth() + 1).padStart(2, '0') +
				String(currentDate.getDate()).padStart(2, '0');

			const fileName = selectedOrgName.replace(/[^\w가-힣]/g, '_') + '_' + dateStr;

			// ParamQuery Grid의 내장 엑셀 익스포트 기능 사용
			const blob = memberGrid.pqGrid('exportData', {
				format: 'xlsx',
				render: true,
				type: 'blob',
				sheetName: '회원목록',
				noCols: getExcelExportColumns()
			});

			// FileSaver.js를 사용하여 파일 다운로드
			if (typeof saveAs !== 'undefined' && blob) {
				saveAs(blob, fileName + '.xlsx');
				showToast('엑셀 파일 다운로드가 시작되었습니다.', 'success');
			} else {
				// FileSaver가 없는 경우 대체 방법
				const url = URL.createObjectURL(blob);
				const a = document.createElement('a');
				a.href = url;
				a.download = fileName + '.xlsx';
				document.body.appendChild(a);
				a.click();
				document.body.removeChild(a);
				URL.revokeObjectURL(url);
				showToast('엑셀 파일 다운로드가 시작되었습니다.', 'success');
			}

		} catch (error) {
			console.error('엑셀 다운로드 오류:', error);
			showToast('엑셀 다운로드 중 오류가 발생했습니다.', 'error');
		}
	}


	/**
	 * 엑셀 다운로드용 컬럼 설정 (수정된 버전 - 직위/직책 컬럼 추가)
	 */
	function getExcelExportColumns() {
		return [
			{ dataIndx: "member_idx", title: "회원번호" },
			{ dataIndx: "area_name", title: "소그룹" },
			{ dataIndx: "member_name", title: "이름" },
			{ dataIndx: "member_sex", title: "성별" },
			{ dataIndx: "member_nick", title: "닉네임" },
			{ dataIndx: "position_name", title: "직위/직분" },
			{ dataIndx: "duty_name", title: "직책" },
			{ dataIndx: "member_phone", title: "휴대폰번호" },
			{ dataIndx: "member_birth", title: "생년월일" },
			{ dataIndx: "member_address", title: "주소" },
			{ dataIndx: "member_address_detail", title: "상세주소" },
			{ dataIndx: "member_etc", title: "특이사항" },
			{
				dataIndx: "regi_date",
				title: "등록일",
				exportRender: true
			},
			{
				dataIndx: "modi_date",
				title: "수정일",
				exportRender: true
			}
		];
	}

	/**
	 * 엑셀용 날짜 포맷팅
	 */
	function formatDateForExcel(dateTimeString) {
		if (!dateTimeString) return '';

		try {
			const date = new Date(dateTimeString);
			const year = date.getFullYear();
			const month = String(date.getMonth() + 1).padStart(2, '0');
			const day = String(date.getDate()).padStart(2, '0');
			const hours = String(date.getHours()).padStart(2, '0');
			const minutes = String(date.getMinutes()).padStart(2, '0');

			return `${year}-${month}-${day} ${hours}:${minutes}`;
		} catch (error) {
			return dateTimeString;
		}
	}

	/**
	 * 회원 검색 이벤트 바인딩
	 */
	function bindMemberSearchEvents() {
		const searchInput = $('.card-header input[type="text"]');
		const searchButton = $('#button-search');

		// 검색 버튼 클릭 이벤트만 활성화
		searchButton.on('click', function() {
			const searchText = searchInput.val().trim();
			if (searchText) {
				filterMemberGrid(searchText);
			} else {
				clearMemberGridFilter();
			}
		});

		// Enter 키 이벤트
		searchInput.on('keypress', function(e) {
			if (e.which === 13) {
				e.preventDefault();
				const searchText = $(this).val().trim();
				if (searchText) {
					filterMemberGrid(searchText);
				} else {
					clearMemberGridFilter();
				}
			}
		});

		// 입력 필드 초기화 시 필터 해제
		searchInput.on('input', function() {
			const searchText = $(this).val().trim();
			if (searchText === '') {
				clearMemberGridFilter();
			}
		});
	}

	/**
	 * 회원 그리드 필터링 (간단한 클라이언트 필터링)
	 */
	function filterMemberGrid(searchText) {
		if (!memberGrid) {
			return;
		}

		try {
			if (!searchText) {
				clearMemberGridFilter();
				return;
			}



			// 그리드 데이터 직접 필터링 방식 사용
			const allData = memberGrid.pqGrid("option", "dataModel.data");

			if (!allData || allData.length === 0) {
				showToast('검색할 데이터가 없습니다.', 'info');
				return;
			}

			// 원본 데이터 저장 (첫 검색 시에만)
			if (!window.originalGridData) {
				window.originalGridData = JSON.parse(JSON.stringify(allData));
			}

			// 이름과 휴대폰번호에서 검색
			const filteredData = window.originalGridData.filter(function(member) {
				const memberName = (member.member_name || '').toLowerCase();
				const memberPhone = (member.member_phone || '').toLowerCase();
				const searchLower = searchText.toLowerCase();

				return memberName.includes(searchLower) || memberPhone.includes(searchLower);
			});

			// 필터링된 데이터로 그리드 업데이트
			memberGrid.pqGrid("option", "dataModel.data", filteredData);
			memberGrid.pqGrid("refreshDataAndView");



			// 검색 결과에 따른 toast 메시지 표시
			if (filteredData.length === 0) {
				showToast(`'${searchText}'에 대한 검색 결과가 없습니다.`, 'info');
			} else {
				showToast(`'${searchText}' 검색결과: ${filteredData.length}명`, 'info');
			}

		} catch (error) {
			console.error('그리드 필터링 중 오류:', error);
			showToast('검색 중 오류가 발생했습니다.', 'error');
		}
	}

	/**
	 * 회원 그리드 필터 해제
	 */
	function clearMemberGridFilter() {
		if (!memberGrid) {
			return;
		}

		try {
			// 원본 데이터가 있으면 복원
			if (window.originalGridData) {
				memberGrid.pqGrid("option", "dataModel.data", window.originalGridData);
				memberGrid.pqGrid("refreshDataAndView");
			}

		} catch (error) {
			console.error('그리드 필터 해제 중 오류:', error);
		}
	}



	/**
	 * 검색 상태 초기화
	 */
	function resetMemberSearch() {
		const searchInput = $('.card-header input[type="text"]');
		searchInput.val('');
		clearMemberGridFilter();

		// 검색 타임아웃 초기화
		if (window.searchTimeout) {
			clearTimeout(window.searchTimeout);
		}
	}

	/**
	 * 정리 이벤트 설정
	 */
	function setupCleanupEvents() {
		// 페이지 떠날 때 정리
		$(window).on('beforeunload', function() {
			destroyCroppie();
			destroySplitJS();
		});

		// offcanvas 닫힐 때 정리
		$('#memberOffcanvas').on('hidden.bs.offcanvas', function() {
			destroyCroppie();
			// 메모 관련 정리
			currentMemberIdx = null;
			editingMemoIdx = null;
			$('#deleteMemoModal').remove(); // 메모 삭제 모달 제거
		});
	}



	/**
	 * Fancytree 초기화
	 */
	function initializeFancytree() {


		// 트리 스피너 표시
		showTreeSpinner();

		$.ajax({
			url: window.memberPageData.baseUrl + 'member/get_org_tree',
			method: 'POST',
			dataType: 'json',
			success: function (treeData) {


				if (!treeData || treeData.length === 0) {
					hideTreeSpinner();
					showToast('조직 데이터가 없습니다.', 'warning');
					return;
				}

				setupFancytreeInstance(treeData);
				restoreSelectedGroupFromStorage(treeData);

				// 트리 로딩 완료 후 스피너 숨김
				hideTreeSpinner();

			},
			error: function (xhr, status, error) {
				console.error('그룹 트리 로드 실패:', error);
				console.error('Response:', xhr.responseText);

				// 에러 발생 시 스피너 숨김
				hideTreeSpinner();
				showToast('그룹 정보를 불러오는데 실패했습니다.', 'error');
			}
		});
	}


	/**
	 * Fancytree 인스턴스 설정
	 */
	function setupFancytreeInstance(treeData) {
		$("#groupTree").fancytree({
			source: treeData,
			activate: function(event, data) {
				const node = data.node;
				const nodeKey = node.key;
				const nodeTitle = node.title;



				// 검색 상태 초기화
				resetMemberSearch();

				// 기존 노드 활성화 처리 함수 호출
				handleTreeNodeActivate(node);
			},
			autoScroll: true,
			keyboard: true,
			focusOnSelect: true
		});
	}


	/**
	 * 트리 노드 활성화 처리
	 */
	function handleTreeNodeActivate(node) {
		const nodeData = node.data;


		// 전역 변수 업데이트
		selectedType = nodeData.type;
		selectedOrgId = nodeData.org_id;
		selectedAreaIdx = nodeData.area_idx || null;

		// 상태 저장 및 UI 업데이트
		saveSelectedGroupToStorage(nodeData);
		updateSelectedOrgName(node.title, nodeData.type);
		loadMemberData();
	}

	/**
	 * localStorage에 선택된 그룹 저장
	 */
	function saveSelectedGroupToStorage(nodeData) {
		try {
			const selectedGroup = {
				type: nodeData.type,
				org_id: nodeData.org_id,
				area_idx: nodeData.area_idx || null,
				timestamp: Date.now()
			};

			localStorage.setItem('member_selected_group', JSON.stringify(selectedGroup));

		} catch (error) {
			console.error('localStorage 저장 실패:', error);
		}
	}

	/**
	 * localStorage에서 저장된 그룹 선택 상태 복원
	 */
	function restoreSelectedGroupFromStorage(treeData) {
		try {
			const savedGroup = localStorage.getItem('member_selected_group');

			if (!savedGroup) {
				selectFirstOrganization();
				return;
			}

			const groupData = JSON.parse(savedGroup);

			// 7일 이내의 데이터만 복원
			const sevenDaysAgo = Date.now() - (7 * 24 * 60 * 60 * 1000);
			if (groupData.timestamp < sevenDaysAgo) {
				localStorage.removeItem('member_selected_group');
				selectFirstOrganization();
				return;
			}

			// 저장된 노드 찾기 및 선택
			const tree = $("#groupTree").fancytree("getTree");
			const nodeToSelect = findSavedNode(tree, groupData);

			if (nodeToSelect) {
				nodeToSelect.setActive(true);
				nodeToSelect.setFocus(true);
				expandParentNodes(nodeToSelect, groupData);

			} else {

				selectFirstOrganization();
			}

		} catch (error) {
			console.error('localStorage 복원 실패:', error);
			selectFirstOrganization();
		}
	}

	/**
	 * 저장된 노드 찾기
	 */
	function findSavedNode(tree, groupData) {
		let nodeToSelect = null;

		if (groupData.type === 'unassigned' && groupData.org_id) {
			nodeToSelect = tree.getNodeByKey('unassigned_' + groupData.org_id);
		} else if (groupData.type === 'area' && groupData.area_idx) {
			nodeToSelect = tree.getNodeByKey('area_' + groupData.area_idx);
		}

		if (!nodeToSelect && groupData.org_id) {
			nodeToSelect = tree.getNodeByKey('org_' + groupData.org_id);
		}

		return nodeToSelect;
	}

	/**
	 * 부모 노드 확장
	 */
	function expandParentNodes(nodeToSelect, groupData) {
		if (groupData.type !== 'unassigned' && nodeToSelect.parent && !nodeToSelect.parent.isRootNode()) {
			nodeToSelect.parent.setExpanded(true);
		}
	}

	/**
	 * 첫 번째 조직 자동 선택
	 */
	function selectFirstOrganization() {
		const tree = $("#groupTree").fancytree("getTree");
		if (tree && tree.rootNode && tree.rootNode.children.length > 0) {
			const firstOrgNode = tree.rootNode.children[0];
			firstOrgNode.setActive(true);
			firstOrgNode.setFocus(true);
			firstOrgNode.setExpanded(true);
		}
	}




	/**
	 * ParamQuery Grid 초기화 (개선된 버전 - 컬럼 순서 변경 이벤트 추가)
	 */
	function initializeParamQuery(detailFields = []) {

		showGridSpinner();

		const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)
			|| window.innerWidth <= 768;

		const colModel = createColumnModel(detailFields);


		const gridOptions = {
			width: "100%",
			height: "100%",
			headerHeight: 35,
			dataModel: {
				data: []
			},
			colModel: colModel,
			selectionModel: {
				type: 'cell',
				mode: 'single'
			},
			scrollModel: {
				autoFit: false,
				horizontal: true,
				vertical: true
			},
			freezeCols: isMobile ? 0 : 4,
			numberCell: { show: false },
			title: false,
			strNoRows: '회원 정보가 없습니다',
			resizable: true,
			sortable: false,
			hoverMode: 'row',
			wrap: false,
			columnBorders: true,
			cellClick: function(event, ui) {
				handleGridCellClick(event, ui);
			},
			cellDblClick: function(event, ui) {
				handleGridCellClick(event, ui);
			},
			complete: function() {

				setTimeout(function() {
					removeDuplicateCheckboxes();
					bindMobileTouchEvents();
				}, 100);
			},
			// 컬럼 순서 변경 이벤트 추가
			columnOrder: function(evt, ui) {


				// 변경된 컬럼 순서 저장
				setTimeout(function() {
					if (memberGrid) {
						const currentColModel = memberGrid.pqGrid("option", "colModel");
						saveColumnOrder(currentColModel);
						showToast('컬럼 순서가 저장되었습니다.', 'success');
					}
				}, 100);
			}
		};

		try {
			// 기존 그리드 제거
			if (memberGrid) {
				try {

					memberGrid.pqGrid("destroy");
					memberGrid = null;
				} catch (e) {
					console.warn('기존 그리드 제거 중 경고:', e);
				}
			}

			$("#memberGrid").empty();

			memberGrid = $("#memberGrid").pqGrid(gridOptions);
			window.memberGrid = memberGrid;


			hideGridSpinner();
		} catch (error) {
			console.error('ParamQuery Grid 초기화 실패:', error);
			console.error('Error stack:', error.stack);
			hideGridSpinner();
			showToast('그리드 초기화에 실패했습니다.', 'error');
		}
	}

	/**
	 * 그리드 옵션 생성 (개선된 버전)
	 */
	function createGridOptions() {
		showGridSpinner();

		const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)
			|| window.innerWidth <= 768;

		return {
			width: "100%",
			height: "100%",
			headerHeight: 500,
			dataModel: {
				data: []
			},
			colModel: createColumnModel(),
			selectionModel: {
				type: 'cell',
				mode: 'single'
			},
			scrollModel: {
				autoFit: false,
				horizontal: true,
				vertical: true
			},
			freezeCols: isMobile ? 0 : 4,
			numberCell: { show: false },
			title: false,
			strNoRows: '회원 정보가 없습니다',
			resizable: true,
			sortable: false,
			hoverMode: 'row',
			wrap: false,
			columnBorders: true,
			cellClick: function(event, ui) {
				handleGridCellClick(event, ui);
			},
			// 모바일 터치 지원을 위한 추가 이벤트
			cellDblClick: function(event, ui) {
				// 더블클릭도 동일하게 처리
				handleGridCellClick(event, ui);
			},
			// 그리드 렌더링 완료 후 모바일 터치 이벤트 바인딩
			complete: function() {
				setTimeout(function() {
					removeDuplicateCheckboxes();
					bindMobileTouchEvents(); // 모바일 터치 이벤트 추가
				}, 100);
			}
		};
	}

	/**
	 * 모바일 터치 이벤트 바인딩 - ParamQuery Grid 우회 (수정된 버전)
	 */
	function bindMobileTouchEvents() {
		const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)
			|| window.innerWidth <= 768;

		if (!isMobile) return;

		// 그리드 컨테이너에 직접 터치 이벤트 바인딩 (수정된 버전)
		$('#memberGrid').off('touchend.mobile click.mobile')
			.on('touchend.mobile click.mobile', 'td', function(e) {
				e.preventDefault();
				e.stopPropagation();

				const $cell = $(this);
				const $row = $cell.closest('tr');

				// 헤더 행 제외
				if ($row.hasClass('pq-grid-header') || $row.find('th').length > 0) {
					return;
				}

				// 데이터 행인지 확인
				const dataRowIndex = $row.attr('pq-row-indx');
				if (dataRowIndex === undefined || dataRowIndex === null) {
					return;
				}

				const rowIndex = parseInt(dataRowIndex);
				if (rowIndex < 0 || isNaN(rowIndex)) return;

				// 컬럼 인덱스 찾기 (더 정확한 방법)
				const cellIndex = $cell.attr('pq-col-indx');
				if (cellIndex === undefined || cellIndex === null) {
					return;
				}

				const colIndex = parseInt(cellIndex);

				try {
					// 그리드 데이터에서 해당 행 정보 가져오기
					const gridData = memberGrid.pqGrid("option", "dataModel.data");
					const rowData = gridData[rowIndex];

					if (rowData) {


						// 체크박스 컬럼인 경우 (첫 번째 컬럼)
						if (colIndex === 0) {
							handleCheckboxColumnClick(e, rowData.member_idx);
						} else {
							// 일반 셀인 경우 회원 정보 수정 창 열기
							clearTimeout(window.memberCellClickTimeout);
							window.memberCellClickTimeout = setTimeout(function() {
								openMemberOffcanvas('edit', rowData);
							}, 200);
						}
					}
				} catch (error) {
					console.error('모바일 터치 이벤트 처리 오류:', error);
				}
			});


	}

	/**
	 * ParamQuery Grid 컬럼 모델 생성 (수정된 버전 - 컬럼 순서 복원 지원)
	 */
	function createColumnModel(detailFields = []) {


		const baseColumns = [
			{
				title: '<input type="checkbox" id="selectAllCheckbox" />',
				dataIndx: "pq_selected",
				width: 50,
				align: "center",
				resizable: false,
				sortable: false,
				editable: false,
				menuIcon: false,
				frozen: true,
				render: function (ui) {
					const checkboxId = 'member-checkbox-' + ui.rowData.member_idx;
					return '<input type="checkbox" class="member-checkbox" id="' + checkboxId + '" data-member-idx="' + ui.rowData.member_idx + '" />';
				}
			},
			{
				title: "소그룹",
				dataIndx: "area_name",
				width: 140,
				align: "center",
				editable: false,
				frozen: true
			},
			{
				title: "사진",
				dataIndx: "photo",
				width: 70,
				editable: false,
				align: "center",
				frozen: true,
				render: function (ui) {
					const photoUrl = ui.cellData || '/assets/images/photo_no.png';
					return '<img src="' + photoUrl + '" alt="사진" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">';
				}
			},
			{
				title: "이름",
				dataIndx: "member_name",
				width: 80,
				editable: false,
				align: "center",
				frozen: true
			},
			{
				title: "성별",
				dataIndx: "member_sex",
				width: 50,
				editable: false,
				align: "center",
				frozen: true,
				render: function (ui) {
					if (!ui.cellData) return '';
					if (ui.cellData === 'male') return '남';
					if (ui.cellData === 'female') return '여';
					return ui.cellData;
				}
			},
			{
				title: "직위/직분",
				dataIndx: "position_name",
				width: 100,
				editable: false,
				align: "center"
			},
			{
				title: "직책",
				dataIndx: "duty_name",
				width: 100,
				editable: false,
				align: "center"
			},
			{
				title: "회원번호",
				dataIndx: "member_idx",
				width: 80,
				editable: false,
				align: "center",
				hidden: true
			},
			{
				title: "닉네임",
				dataIndx: "member_nick",
				width: 100,
				editable: false,
				align: "center"
			},
			{
				title: "휴대폰번호",
				dataIndx: "member_phone",
				width: 140,
				editable: false,
				align: "center"
			},
			{
				title: "생년월일",
				dataIndx: "member_birth",
				width: 110,
				editable: false,
				align: "center"
			},
			{
				title: "주소",
				dataIndx: "member_address",
				width: 250,
				editable: false,
				align: "left"
			},
			{
				title: "상세주소",
				dataIndx: "member_address_detail",
				width: 250,
				editable: false,
				align: "left"
			}
		];

		// 상세필드 컬럼 동적 추가
		if (detailFields && Array.isArray(detailFields) && detailFields.length > 0) {

			detailFields.forEach(function(field) {
				if (field.is_active === 'Y') {
					const fieldColumn = {
						title: field.field_name,
						dataIndx: 'detail_' + field.field_idx,
						width: 150,
						editable: false,
						align: field.field_type === 'textarea' ? 'left' : 'center',
						render: function(ui) {
							return renderDetailFieldValue(ui.cellData, field.field_type);
						}
					};
					baseColumns.push(fieldColumn);

				}
			});
		}

		// 등록일/수정일 컬럼 추가
		baseColumns.push(
			{
				title: "등록일",
				dataIndx: "regi_date",
				width: 120,
				editable: false,
				align: "center",
				render: function (ui) {
					return formatDateTime(ui.cellData);
				}
			},
			{
				title: "수정일",
				dataIndx: "modi_date",
				width: 120,
				editable: false,
				align: "center",
				render: function (ui) {
					return formatDateTime(ui.cellData);
				}
			}
		);



		// 저장된 컬럼 순서가 있으면 재정렬
		const savedOrder = loadColumnOrder();
		if (savedOrder) {
			return reorderColumnModel(baseColumns, savedOrder);
		}

		return baseColumns;
	}

	/**
	 * 날짜시간 포맷팅 유틸리티
	 */
	function formatDateTime(dateTimeString) {
		if (!dateTimeString) return '';

		const date = new Date(dateTimeString);
		const year = date.getFullYear();
		const month = String(date.getMonth() + 1).padStart(2, '0');
		const day = String(date.getDate()).padStart(2, '0');
		const hours = String(date.getHours()).padStart(2, '0');
		const minutes = String(date.getMinutes()).padStart(2, '0');
		const seconds = String(date.getSeconds()).padStart(2, '0');

		return `${year}-${month}-${day}<br>${hours}:${minutes}:${seconds}`;
	}

	/**
	 * 그리드 셀 클릭 처리 - 모바일/PC 통합 개선
	 */
	function handleGridCellClick(event, ui) {


		const colIndx = ui.colIndx;
		const rowData = ui.rowData;
		const memberIdx = rowData.member_idx;

		// 모바일에서는 직접 터치 이벤트로 처리되므로 중복 실행 방지
		const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
		if (isMobile && event.originalEvent && event.originalEvent.type === 'touchend') {

			return;
		}

		// 체크박스 컬럼인 경우
		if (colIndx === 0) {
			handleCheckboxColumnClick(event, memberIdx);
			return;
		}

		// 기타 컬럼인 경우 - 회원 정보 수정 창 열기
		clearTimeout(window.memberCellClickTimeout);
		window.memberCellClickTimeout = setTimeout(function() {
			openMemberOffcanvas('edit', rowData);
		}, 200);
	}

	/**
	 * 체크박스 컬럼 클릭 처리 - 모바일 터치 지원 강화
	 */
	function handleCheckboxColumnClick(event, memberIdx) {


		// 직접 체크박스를 클릭한 경우가 아니라면 체크박스 토글
		const isDirectCheckboxClick = $(event.target).hasClass('member-checkbox') ||
			$(event.originalEvent?.target).hasClass('member-checkbox');

		if (!isDirectCheckboxClick) {
			const checkbox = $('.member-checkbox[data-member-idx="' + memberIdx + '"]').first();
			if (checkbox.length > 0) {
				const isCurrentlyChecked = checkbox.is(':checked');
				checkbox.prop('checked', !isCurrentlyChecked);

			}
		}

		// 체크박스 상태 업데이트
		updateSelectAllCheckbox();
		updateSelectedMemberButtons();
	}

	/**
	 * 체크박스 이벤트 바인딩 (최종 개선 버전)
	 */
	function bindCheckboxEvents() {


		// 기존 이벤트 완전히 제거
		$(document).off('change', '#selectAllCheckbox');
		$(document).off('change', '.member-checkbox');

		// 전체 선택 체크박스 이벤트
		$(document).on('change', '#selectAllCheckbox', function (e) {
			e.stopPropagation();

			const isChecked = $(this).is(':checked');


			// 실제 존재하는 고유 체크박스만 선택
			const uniqueCheckboxes = getUniqueCheckboxes();


			uniqueCheckboxes.forEach(function(checkbox) {
				$(checkbox).prop('checked', isChecked);
			});

			updateSelectedMemberButtons();
		});

		// 개별 체크박스 이벤트
		$(document).on('change', '.member-checkbox', function (e) {
			e.stopPropagation();
			updateSelectAllCheckbox();
			updateSelectedMemberButtons();
		});


	}


	/**
	 * 고유한 체크박스만 가져오기
	 */
	function getUniqueCheckboxes() {
		const uniqueCheckboxes = [];
		const seenMemberIds = new Set();

		$('.member-checkbox').each(function() {
			const memberIdx = $(this).data('member-idx');
			if (!seenMemberIds.has(memberIdx)) {
				seenMemberIds.add(memberIdx);
				uniqueCheckboxes.push(this);
			}
		});

		return uniqueCheckboxes;
	}

	/**
	 * 전체 선택 체크박스 상태 업데이트
	 */
	function updateSelectAllCheckbox() {
		const totalCheckboxes = $('.member-checkbox').length;
		const checkedCheckboxes = $('.member-checkbox:checked').length;
		const selectAllCheckbox = $('#selectAllCheckbox');

		if (checkedCheckboxes === 0) {
			selectAllCheckbox.prop('checked', false).prop('indeterminate', false);
		} else if (checkedCheckboxes === totalCheckboxes) {
			selectAllCheckbox.prop('checked', true).prop('indeterminate', false);
		} else {
			selectAllCheckbox.prop('checked', false).prop('indeterminate', true);
		}
	}


	/**
	 * 선택된 회원 기반 버튼 상태 업데이트 (최종 버전)
	 */
	function updateSelectedMemberButtons() {
		// 고유 체크박스 중 체크된 것만 카운트
		const uniqueCheckedBoxes = [];
		const seenMemberIds = new Set();

		$('.member-checkbox:checked').each(function() {
			const memberIdx = $(this).data('member-idx');
			if (!seenMemberIds.has(memberIdx)) {
				seenMemberIds.add(memberIdx);
				uniqueCheckedBoxes.push(this);
			}
		});

		const checkedCount = uniqueCheckedBoxes.length;

		$('#btnDeleteMember').prop('disabled', checkedCount === 0);
		$('#btnMoveMember').prop('disabled', checkedCount === 0);
	}


	/**
	 * 선택된 회원들 이동
	 */
	function moveSelectedMembers(selectedMembers) {
		const memberCount = selectedMembers.length;
		const tree = $("#groupTree").fancytree("getTree");
		const activeNode = tree.getActiveNode();

		if (!activeNode) {
			showToast('그룹을 선택해주세요.', 'warning');
			return;
		}


		// 이동 확인 메시지
		const message = `선택한 ${memberCount}명의 회원을 다른 소그룹으로 이동하시겠습니까?`;

		// 모달에 메시지 설정 및 소그룹 옵션 로드
		$('#moveMessage').text(message);
		loadMoveAreaOptions(selectedOrgId, function() {
			setupMoveConfirmButton(selectedMembers);
			$('#moveMemberModal').modal('show');
		});
	}

	/**
	 * 이동 모달용 소그룹 옵션 로드
	 */
	function loadMoveAreaOptions(orgId, callback) {
		const areaSelect = $('#moveToAreaIdx');
		areaSelect.html('<option value="">소그룹 선택</option>');

		try {
			const tree = $("#groupTree").fancytree("getTree");
			const groupNode = tree.getNodeByKey('org_' + orgId);

			if (groupNode && groupNode.children) {
				function addAreaOptionsRecursively(nodes, depth = 0) {
					nodes.forEach(function(node) {
						const areaData = node.data;
						if (areaData.type === 'area') {
							const indent = '　'.repeat(depth);
							const optionText = indent + node.title.replace(/\s*\(\d+명\)$/, '');
							areaSelect.append(`<option value="${areaData.area_idx}">${optionText}</option>`);

							if (node.children && node.children.length > 0) {
								addAreaOptionsRecursively(node.children, depth + 1);
							}
						}
					});
				}

				addAreaOptionsRecursively(groupNode.children);
			}

			if (typeof callback === 'function') {
				callback();
			}
		} catch (error) {
			console.error('이동용 소그룹 옵션 로드 오류:', error);
			if (typeof callback === 'function') {
				callback();
			}
		}
	}

	/**
	 * 이동 확인 버튼 설정
	 */
	function setupMoveConfirmButton(selectedMembers) {
		$('#confirmMoveBtn').off('click').on('click', function() {
			const moveToAreaIdx = $('#moveToAreaIdx').val();

			if (!moveToAreaIdx) {
				showToast('이동할 소그룹을 선택해주세요.', 'warning');
				return;
			}

			executeMemberMove(selectedMembers, moveToAreaIdx);
			$('#moveMemberModal').modal('hide');
		});
	}

	/**
	 * 실제 회원 이동 실행
	 */
	function executeMemberMove(selectedMembers, moveToAreaIdx) {
		const memberIndices = selectedMembers.map(member => member.member_idx);

		$('#confirmMoveBtn').prop('disabled', true).html('<i class="bi bi-hourglass-split"></i> 이동 중...');

		$.ajax({
			url: window.memberPageData.baseUrl + 'member/move_members',
			method: 'POST',
			data: {
				member_indices: memberIndices,
				move_to_area_idx: moveToAreaIdx,
				org_id: selectedOrgId
			},
			dataType: 'json',
			success: function (response) {
				const toastType = response.success ? 'success' : 'error';
				showToast(response.message, toastType);
				if (response.success) {
					loadMemberData();
					refreshGroupTree();
					$('.member-checkbox').prop('checked', false);
					updateSelectAllCheckbox();
					updateSelectedMemberButtons();
				}
			},
			error: function (xhr, status, error) {
				console.error('회원 이동 실패:', error);
				showToast('회원 이동에 실패했습니다.', 'error');
			},
			complete: function() {
				$('#confirmMoveBtn').prop('disabled', false).html('이동');
			}
		});
	}





	/**
	 * 회원 추가 버튼 클릭 처리
	 */
	function handleAddMemberClick() {
		const tree = $("#groupTree").fancytree("getTree");
		const activeNode = tree.getActiveNode();

		if (!activeNode) {
			showToast('그룹을 선택해주세요.', 'warning');
			return;
		}

		const nodeData = activeNode.data;

		if (nodeData.type === 'org') {
			showToast('가장 상위 그룹에서는 회원을 추가할 수 없습니다. 하위 그룹을 선택해주세요.', 'warning');
			return;
		}

		addNewMember(nodeData);
	}

	/**
	 * 새 회원 추가
	 */
	function addNewMember(nodeData) {
		const addData = {
			org_id: nodeData.org_id,
			area_idx: nodeData.area_idx || null
		};

		$.ajax({
			url: '/member/add_member',
			type: 'POST',
			data: addData,
			dataType: 'json',
			success: function (response) {
				if (response.success) {
					showToast('회원이 추가되었습니다: ' + response.member_name, 'success');
					loadMemberData();
					refreshGroupTree();
				} else {
					showToast(response.message || '회원 추가에 실패했습니다.', 'error');
				}
			},
			error: function (xhr, status, error) {
				console.error('회원 추가 오류:', error);
				showToast('회원 추가 중 오류가 발생했습니다.', 'error');
			}
		});
	}

	/**
	 * 선택된 회원들 삭제
	 */
	function deleteSelectedMembers(selectedMembers) {
		const memberCount = selectedMembers.length;
		const tree = $("#groupTree").fancytree("getTree");
		const activeNode = tree.getActiveNode();

		if (!activeNode) {
			showToast('그룹을 선택해주세요.', 'warning');
			return;
		}

		const nodeData = activeNode.data;
		const isUnassigned = nodeData.type === 'unassigned';

		// 삭제 확인 메시지
		const message = isUnassigned
			? `미분류에서의 삭제는 복원이 불가합니다. 정말로 ${memberCount}명의 회원을 삭제하시겠습니까?`
			: `정말로 ${memberCount}명의 회원을 삭제하시겠습니까?(미분류로 이동)`;

		// 모달에 메시지 설정 및 표시
		$('#deleteMessage').text(message);
		setupDeleteConfirmButton(selectedMembers, isUnassigned);
		$('#deleteMemberModal').modal('show');
	}

	/**
	 * 삭제 확인 버튼 설정
	 */
	function setupDeleteConfirmButton(selectedMembers, isUnassigned) {
		$('#confirmDeleteBtn').off('click').on('click', function() {
			const deleteType = isUnassigned ? 'unassigned' : 'area';
			executeMemberDelete(selectedMembers, deleteType);
			$('#deleteMemberModal').modal('hide');
		});
	}

	/**
	 * 실제 회원 삭제 실행
	 */
	function executeMemberDelete(selectedMembers, deleteType) {
		const memberIndices = selectedMembers.map(member => member.member_idx);

		$.ajax({
			url: window.memberPageData.baseUrl + 'member/delete_members',
			method: 'POST',
			data: {
				member_indices: memberIndices,
				delete_type: deleteType
			},
			dataType: 'json',
			success: function (response) {
				const toastType = response.success ? 'success' : 'error';
				showToast(response.message, toastType);
				if (response.success) {
					loadMemberData();
					refreshGroupTree();
				}
			},
			error: function (xhr, status, error) {
				console.error('회원 삭제 실패:', error);
				showToast('회원 삭제에 실패했습니다.', 'error');
			}
		});
	}



	/**
	 * 그룹 트리 새로고침
	 */
	function refreshGroupTree() {

		// 현재 선택된 트리 정보 저장
		const tree = $("#groupTree").fancytree("getTree");
		const activeNode = tree ? tree.getActiveNode() : null;
		const currentSelection = activeNode ? {
			key: activeNode.key,
			type: activeNode.data.type,
			org_id: activeNode.data.org_id,
			area_idx: activeNode.data.area_idx
		} : null;

		// 트리 새로고침 시 스피너 표시
		showTreeSpinner();

		$.ajax({
			url: window.memberPageData.baseUrl + 'member/get_org_tree',
			method: 'POST',
			dataType: 'json',
			success: function (treeData) {


				if (!treeData || treeData.length === 0) {
					hideTreeSpinner();
					showToast('조직 데이터가 없습니다.', 'warning');
					return;
				}

				$("#groupTree").fancytree("destroy");
				setupFancytreeInstance(treeData);

				if (currentSelection) {
					restoreTreeSelection(currentSelection);
				}

				// 트리 새로고침 완료 후 스피너 숨김
				hideTreeSpinner();

			},
			error: function (xhr, status, error) {
				console.error('그룹 트리 새로고침 실패:', error);

				// 에러 발생 시 트리 스피너 숨김
				hideTreeSpinner();
				showToast('그룹 정보 새로고침에 실패했습니다.', 'error');
			}
		});
	}

	/**
	 * 트리 선택 상태 복원
	 */
	function restoreTreeSelection(selection) {
		try {
			const tree = $("#groupTree").fancytree("getTree");
			let nodeToSelect = null;

			if (selection.key) {
				nodeToSelect = tree.getNodeByKey(selection.key);
			}

			if (!nodeToSelect) {
				if (selection.type === 'unassigned' && selection.org_id) {
					nodeToSelect = tree.getNodeByKey('unassigned_' + selection.org_id);
				} else if (selection.type === 'area' && selection.area_idx) {
					nodeToSelect = tree.getNodeByKey('area_' + selection.area_idx);
				} else if (selection.type === 'org' && selection.org_id) {
					nodeToSelect = tree.getNodeByKey('org_' + selection.org_id);
				}
			}

			if (nodeToSelect) {
				nodeToSelect.setActive(true);
				nodeToSelect.setFocus(true);

				if (nodeToSelect.parent && !nodeToSelect.parent.isRootNode()) {
					nodeToSelect.parent.setExpanded(true);
				}


			} else {

				selectFirstOrganization();
			}
		} catch (error) {
			console.error('트리 선택 상태 복원 실패:', error);
			selectFirstOrganization();
		}
	}



	/**
	 * 회원 데이터 로드 (상세필드 포함)
	 */
	function loadMemberData() {
		if (!selectedOrgId) return;

		showGridSpinner();

		$.ajax({
			url: window.memberPageData.baseUrl + 'member/get_members',
			method: 'POST',
			data: {
				type: selectedType,
				org_id: selectedOrgId,
				area_idx: selectedAreaIdx
			},
			dataType: 'json',
			success: function (response) {

				handleMemberDataResponse(response);
			},
			error: function (xhr, status, error) {
				console.error('회원 데이터 로드 실패:', error);
				hideGridSpinner();
				showToast('회원 데이터를 불러오는데 실패했습니다.', 'error');
			}
		});
	}


	/**
	 * 회원 데이터 응답 처리 (수정된 버전)
	 */
	function handleMemberDataResponse(response) {


		if (!response.success) {
			console.error('회원 데이터 로드 실패:', response.message);
			hideGridSpinner();
			showToast('회원 데이터를 불러오는데 실패했습니다.', 'error');
			return;
		}



		// 그리드가 없거나 상세필드 구조가 변경된 경우에만 재생성
		if (!memberGrid) {

			initializeParamQuery(response.detail_fields || []);
		} else if (response.detail_fields && response.detail_fields.length > 0) {
			// 기존 컬럼 구조와 비교
			const currentCols = memberGrid.pqGrid("option", "colModel");
			const needsRebuild = checkIfColumnsChanged(currentCols, response.detail_fields);

			if (needsRebuild) {

				initializeParamQuery(response.detail_fields || []);
			}
		}

		// 데이터 업데이트
		if (memberGrid) {
			try {

				// 먼저 데이터 초기화
				memberGrid.pqGrid("option", "dataModel.data", []);
				memberGrid.pqGrid("refreshDataAndView");

				// 잠시 대기 후 실제 데이터 설정
				setTimeout(function() {

					memberGrid.pqGrid("option", "dataModel.data", response.data || []);
					memberGrid.pqGrid("refreshDataAndView");

					// 체크박스 관련 처리
					setTimeout(function() {
						removeDuplicateCheckboxes();
						$('.member-checkbox').prop('checked', false);
						$('#selectAllCheckbox').prop('checked', false);
						bindCheckboxEvents();
						bindMobileTouchEvents();


					}, 100);
				}, 50);

			} catch (error) {
				console.error('그리드 데이터 업데이트 실패:', error);
				showToast('데이터 표시 중 오류가 발생했습니다.', 'error');
			}
		} else {
			console.error('memberGrid 인스턴스가 없습니다!');
			showToast('그리드 초기화 오류', 'error');
		}

		$('#btnDeleteMember').prop('disabled', true);
		hideGridSpinner();
	}

	/**
	 * 컬럼 구조 변경 확인
	 */
	function checkIfColumnsChanged(currentCols, detailFields) {
		if (!currentCols || !detailFields) return false;

		// 현재 그리드의 상세필드 컬럼 개수 확인
		const currentDetailCols = currentCols.filter(col =>
			col.dataIndx && col.dataIndx.startsWith('detail_')
		);

		// 활성화된 상세필드 개수 확인
		const activeDetailFields = detailFields.filter(field => field.is_active === 'Y');



		// 개수가 다르면 재생성 필요
		return currentDetailCols.length !== activeDetailFields.length;
	}


	/**
	 * 중복 체크박스 제거 함수
	 */
	function removeDuplicateCheckboxes() {
		const seenMemberIds = new Set();
		const checkboxesToRemove = [];

		$('.member-checkbox').each(function() {
			const memberIdx = $(this).data('member-idx');

			if (seenMemberIds.has(memberIdx)) {
				// 중복된 체크박스 발견
				checkboxesToRemove.push(this);

			} else {
				seenMemberIds.add(memberIdx);
			}
		});

		// 중복된 체크박스들 제거
		checkboxesToRemove.forEach(function(checkbox) {
			$(checkbox).closest('td').html(''); // 해당 셀을 비움
		});

	}

	/**
	 * 선택된 조직명 업데이트
	 */
	function updateSelectedOrgName(title, type) {
		const orgNameElement = $('#selectedOrgName');
		if (!orgNameElement.length) return;

		let displayText = '';
		switch(type) {
			case 'org':
				displayText = `${title} - 전체 회원`;
				break;
			case 'area':
				displayText = `${title} 소그룹`;
				break;
			case 'unassigned':
				displayText = `미분류 회원`;
				break;
		}

		orgNameElement.text(displayText);
	}


	/**
	 * 회원 정보 수정 모달 열기 (수정된 버전 - 상세필드 미리 로드)
	 */
	function openMemberOffcanvas(mode, memberData = null) {
		const offcanvas = $('#memberOffcanvas');
		const title = generateOffcanvasTitle(mode, memberData);

		$('#memberOffcanvasLabel').text(title);

		// 폼 및 UI 초기화
		resetOffcanvasForm();

		// 탭 초기화 - 항상 첫 번째 탭(회원정보)을 활성화
		resetTabsToFirst();

		if (mode === 'edit' && memberData) {
			// 소그룹 옵션과 직위/직책 옵션을 모두 로드한 후 데이터 채우기
			loadAreaOptionsWithCallback(selectedOrgId, function() {
				loadPositionsAndDuties(selectedOrgId, function() {
					populateFormData(memberData);
					// 메모 관련 초기화
					currentMemberIdx = memberData.member_idx;
					currentMemberTimelineIdx = memberData.member_idx;

					// 회원정보 탭에 표시될 상세필드 로드
					loadDetailFields(selectedOrgId, memberData.member_idx);
				});
			});
		} else {
			// 새 회원 추가 시에도 옵션들 로드
			loadAreaOptions(selectedOrgId);
			loadPositionsAndDuties(selectedOrgId);

			// 새 회원 추가 시에도 빈 상세필드 폼 로드
			if (selectedOrgId) {
				loadDetailFields(selectedOrgId, null);
			}
		}

		// 사진 이벤트 바인딩
		bindPhotoEvents();

		// offcanvas 표시 및 정리 이벤트 설정
		showOffcanvasWithCleanup(offcanvas);
	}

	/**
	 * 탭을 첫 번째 탭(회원정보)으로 초기화
	 */
	function resetTabsToFirst() {
		$('#memberTab .nav-link').removeClass('active');
		$('#memberTabContent .tab-pane').removeClass('show active');
		$('#profile-tab').addClass('active').attr('aria-selected', 'true');
		$('#profile-tab-pane').addClass('show active');
		$('#detail-tab, #memo-tab').attr('aria-selected', 'false');
		$('#detail-tab, #timeline-tab, #memo-tab').attr('aria-selected', 'false');
	}

	/**
	 * 콜백을 지원하는 소그룹 옵션 로드 함수
	 */
	function loadAreaOptionsWithCallback(orgId, callback) {
		const areaSelect = $('#area_idx');
		areaSelect.html('<option value="">소그룹 선택</option>');

		try {
			const tree = $("#groupTree").fancytree("getTree");
			const groupNode = tree.getNodeByKey('org_' + orgId);

			if (groupNode && groupNode.children) {
				function addAreaOptionsRecursively(nodes, depth = 0) {
					nodes.forEach(function(node) {
						const areaData = node.data;
						if (areaData.type === 'area') {
							const indent = '　'.repeat(depth);
							const optionText = indent + node.title.replace(/\s*\(\d+명\)$/, '');
							areaSelect.append(`<option value="${areaData.area_idx}">${optionText}</option>`);

							if (node.children && node.children.length > 0) {
								addAreaOptionsRecursively(node.children, depth + 1);
							}
						}
					});
				}

				addAreaOptionsRecursively(groupNode.children);
			}

			if (typeof callback === 'function') {
				callback();
			}
		} catch (error) {
			console.error('소그룹 옵션 로드 오류:', error);
			if (typeof callback === 'function') {
				callback();
			}
		}
	}

	/**
	 * 기존 소그룹 옵션 로드 함수 (기존 호환성 유지)
	 */
	function loadAreaOptions(orgId) {
		loadAreaOptionsWithCallback(orgId, null);
	}

	/**
	 * Offcanvas 제목 생성
	 */
	function generateOffcanvasTitle(mode, memberData) {
		if (mode === 'add') {
			return '회원 추가';
		}

		if (mode === 'edit' && memberData && memberData.member_name) {
			return memberData.member_name + ' 회원 정보 수정';
		}

		return '회원 정보 수정';
	}

	/**
	 * Offcanvas 폼 초기화 (수정된 버전)
	 */
	function resetOffcanvasForm() {
		$('#memberForm')[0].reset();
		$('#photoPreview').hide();
		$('#photoUpload').show();
		$('#cropContainer').hide();

		$('#delete_photo').remove();
		$('input[name="detail_field"]').remove(); // 상세필드 히든 필드도 제거
		$('#detail-front').empty().hide();
		$('#detailFieldsContainer').empty();

		// 메모 관련 초기화
		currentMemberIdx = null;
		editingMemoIdx = null;
		$('#newMemoContent').val('');
		$('#memoList').empty();

		// 타임라인 관련 초기화
		currentMemberTimelineIdx = null;
		editingTimelineIdx = null;
		$('#newTimelineType').val('');
		$('#newTimelineDate').val('');
		$('#newTimelineContent').val('');
		$('#timelineList').empty();
		timelineTypes = [];

		destroyCroppie();
	}


	/**
	 * 폼 데이터 채우기 (수정된 버전 - 직위/직책 필드 포함)
	 */
	function populateFormData(memberData) {
		const fieldMappings = {
			'member_idx': memberData.member_idx,
			'member_name': memberData.member_name,
			'member_sex': memberData.member_sex,
			'member_nick': memberData.member_nick || '',
			'position_name': memberData.position_name || '',
			'duty_name': memberData.duty_name || '',
			'member_phone': memberData.member_phone || '',
			'member_birth': memberData.member_birth || '',
			'member_address': memberData.member_address || '',
			'member_address_detail': memberData.member_address_detail || '',
			'member_etc': memberData.member_etc || '',
			'grade': memberData.grade || 0,
			'area_idx': memberData.area_idx || '',
			'org_id': memberData.org_id
		};

		Object.keys(fieldMappings).forEach(function(fieldName) {
			const element = $('#' + fieldName);
			if (element.length) {
				element.val(fieldMappings[fieldName]);
			}
		});

		$('#leader_yn').prop('checked', memberData.leader_yn === 'Y');
		$('#new_yn').prop('checked', memberData.new_yn === 'Y');

		if (memberData.photo && memberData.photo !== '/assets/images/photo_no.png') {
			$('#previewImage').attr('src', memberData.photo);
			$('#photoPreview').show();
			$('#photoUpload').hide();
		}
	}
	/**
	 * Offcanvas 표시 및 정리 이벤트 설정
	 */
	function showOffcanvasWithCleanup(offcanvas) {
		const offcanvasInstance = new bootstrap.Offcanvas(offcanvas[0]);
		offcanvasInstance.show();

		offcanvas.off('hidden.bs.offcanvas.croppie').on('hidden.bs.offcanvas.croppie', function() {
			destroyCroppie();
		});
	}


	/**
	 * 파일 위치: assets/js/member.js
	 * 역할: 메모 탭 이벤트 바인딩 (메모 항목 로드 추가)
	 */
	function bindMemoTabEvents() {
		// 메모 탭 클릭 시 메모 목록 로드
		$('#memo-tab').off('shown.bs.tab').on('shown.bs.tab', function() {
			const memberIdx = $('#member_idx').val();
			if (memberIdx) {
				currentMemberIdx = memberIdx;
				loadMemoTypes();  // 메모 항목 로드
				loadMemoList(memberIdx);  // 메모 목록 로드
			}
		});

		// 메모 추가 버튼 클릭
		$(document).off('click', '#addMemoBtn').on('click', '#addMemoBtn', function() {
			openMemoModal('add');
		});

		// 메모 저장 버튼 클릭
		$(document).off('click', '#saveMemoBtn').on('click', '#saveMemoBtn', function() {
			saveMemoFromModal();
		});

		// 메모 수정 버튼 클릭
		$(document).off('click', '.btn-memo-edit').on('click', '.btn-memo-edit', function() {
			const idx = $(this).data('idx');
			const memoItem = $(this).closest('.memo-item');
			const memoType = memoItem.find('.memo-type').text().trim();
			const content = memoItem.find('.memo-content').text().trim();
			const date = memoItem.data('date') || '';

			openMemoModal('edit', idx, memoType, content, date);
		});

		// 메모 삭제 버튼 클릭
		$(document).off('click', '.btn-memo-delete').on('click', '.btn-memo-delete', function() {
			const idx = $(this).data('idx');
			showDeleteMemoModal(idx);
		});
	}

	function saveMemoFromModal() {
		const mode = $('#memoModal').data('mode');
		const idx = $('#memoModal').data('idx');
		const memoType = $('#memoType').val().trim();
		const content = $('#memoContent').val().trim();
		const date = $('#memoDate').val();

		if (!content) {
			showToast('메모 내용을 입력해주세요.', 'warning');
			return;
		}

		if (!currentMemberIdx) {
			showToast('회원 정보를 찾을 수 없습니다.', 'error');
			return;
		}

		const url = mode === 'add' ? '/member/save_memo' : '/member/update_memo';
		const data = {
			member_idx: currentMemberIdx,
			memo_type: memoType,
			memo_content: content,
			att_date: date,
			org_id: selectedOrgId
		};

		if (mode === 'edit') {
			data.idx = idx;
		}

		$.ajax({
			url: url,
			method: 'POST',
			data: data,
			dataType: 'json',
			success: function(response) {
				if (response.success) {
					bootstrap.Modal.getInstance(document.getElementById('memoModal')).hide();
					loadMemoList(currentMemberIdx);
					showToast(response.message, 'success');
				} else {
					showToast(response.message || '처리에 실패했습니다.', 'error');
				}
			},
			error: function() {
				showToast('처리 중 오류가 발생했습니다.', 'error');
			}
		});
	}

	/**
	 * 파일 위치: assets/js/member.js
	 * 역할: 메모 아이템 HTML 생성 (항목별 배지 색상 적용)
	 */
	function createMemoItemHtml(memo) {
		const formattedDate = formatMemoDateTime(memo.regi_date);

		// 메모 항목 배지 생성 (각기 다른 색상)
		let memoTypeBadge = '';
		if (memo.memo_type) {
			const badgeClass = getMemoTypeBadgeClass(memo.memo_type);
			memoTypeBadge = `<span class="badge ${badgeClass} memo-type">${escapeHtml(memo.memo_type)}</span> `;
		}

		const attDate = memo.att_date ? `<span class="text-primary">${memo.att_date}</span> | ` : '';

		return `
		<div class="memo-item" data-idx="${memo.idx}" data-date="${memo.att_date || ''}">				
			<div class="row">
				<div class="col-9">
					<div class="mb-1">
						${memoTypeBadge}
					</div>
					<div class="memo-content">${escapeHtml(memo.memo_content)}</div>
					<span class="text-muted fs-6" style="font-size: 12px!important; color: #ff6400!important;">
						${attDate}${formattedDate}
					</span>
				</div>
				
				<div class="memo-actions col-3 d-flex align-items-center justify-content-end">
					<div class="btn-group">
						<button type="button" class="btn btn-sm btn-outline-secondary d-flex align-items-center justify-content-end btn-memo-edit" data-idx="${memo.idx}">수정</button>
						<button type="button" class="btn btn-sm btn-outline-danger d-flex align-items-center justify-content-end btn-memo-delete" data-idx="${memo.idx}">삭제</button>
					</div>
				</div>
			</div>				
		</div>
	`;
	}


	/**
	 * 파일 위치: assets/js/member.js
	 * 역할: 조직의 메모 항목 목록 로드
	 */
	function loadMemoTypes() {
		if (!selectedOrgId) return;

		$.ajax({
			url: '/member/get_memo_types',
			method: 'POST',
			data: {
				org_id: selectedOrgId
			},
			dataType: 'json',
			success: function(response) {
				if (response.success && response.data) {
					memoTypes = response.data;
					updateMemoTypeSelect();
				} else {
					memoTypes = [];
					updateMemoTypeSelect();
				}
			},
			error: function() {
				console.error('메모 항목 목록을 불러오는데 실패했습니다.');
				memoTypes = [];
				updateMemoTypeSelect();
			}
		});
	}

	/**
	 * 파일 위치: assets/js/member.js
	 * 역할: 메모 항목 셀렉트박스 업데이트
	 */
	function updateMemoTypeSelect() {
		let selectHtml = '<option value="">항목 선택</option>';

		if (memoTypes && memoTypes.length > 0) {
			selectHtml += memoTypes.map(function(type) {
				return `<option value="${escapeHtml(type)}">${escapeHtml(type)}</option>`;
			}).join('');
		}

		$('#memoType').html(selectHtml);
	}

	/**
	 * 파일 위치: assets/js/member.js
	 * 역할: 메모 모달 열기 (항목 선택 적용)
	 */
	function openMemoModal(mode, idx = null, memoType = '', content = '', date = '') {
		const modal = new bootstrap.Modal(document.getElementById('memoModal'));
		const modalTitle = mode === 'add' ? '메모 추가' : '메모 수정';

		$('#memoModalLabel').text(modalTitle);
		$('#memoType').val(memoType);
		$('#memoContent').val(content);
		$('#memoDate').val(date);

		// 모달에 모드와 idx 저장
		$('#memoModal').data('mode', mode);
		$('#memoModal').data('idx', idx);

		modal.show();
	}

	/**
	 * 파일 위치: assets/js/member.js
	 * 역할: 메모 목록 로드
	 */
	function loadMemoList(memberIdx) {
		if (!memberIdx) return;

		$.ajax({
			url: '/member/get_memo_list',
			method: 'POST',
			data: {
				member_idx: memberIdx,
				org_id: selectedOrgId,
				page: 1,
				limit: 20
			},
			dataType: 'json',
			success: function(response) {
				if (response.success) {
					renderMemoList(response.data);
				} else {
					showToast('메모 목록을 불러오는데 실패했습니다.', 'error');
				}
			},
			error: function() {
				showToast('메모 목록을 불러오는데 실패했습니다.', 'error');
			}
		});
	}

	/**
	 * 파일 위치: assets/js/member.js
	 * 역할: 메모 목록 렌더링
	 */
	function renderMemoList(memoList) {
		const memoListContainer = $('#memoList');
		memoListContainer.empty();

		if (!memoList || memoList.length === 0) {
			memoListContainer.html('<div class="text-center text-muted py-3" id="emptyMemoMessage">등록된 메모가 없습니다.</div>');
			return;
		}

		memoList.forEach(function(memo, index) {
			const memoHtml = createMemoItemHtml(memo);
			memoListContainer.append(memoHtml);

			if (index < memoList.length - 1) {
				memoListContainer.append('<div class="border-bottom my-2"></div>');
			}
		});
	}

	/**
	 * 파일 위치: assets/js/member.js
	 * 역할: 메모 아이템 HTML 생성 (배지 색상 적용)
	 */
	function createMemoItemHtml(memo) {
		const formattedDate = formatMemoDateTime(memo.regi_date);

		// 메모 항목 배지 생성 (각기 다른 색상)
		let memoTypeBadge = '';
		if (memo.memo_type && memo.memo_type.trim() !== '') {
			const badgeClass = getMemoTypeBadgeClass(memo.memo_type);
			memoTypeBadge = `<span class="badge ${badgeClass} memo-type me-1">${escapeHtml(memo.memo_type)}</span>`;
		}

		const attDate = memo.att_date ? `<span class="text-primary">${memo.att_date}</span> | ` : '';

		return `
		<div class="memo-item" data-idx="${memo.idx}" data-date="${memo.att_date || ''}">				
			<div class="row">
				<div class="col-9">
					<div class="mb-2">
						${memoTypeBadge}
					</div>
					<div class="memo-content mb-1">${escapeHtml(memo.memo_content)}</div>
					<div class="text-muted" style="font-size: 12px;">
						${attDate}${formattedDate}
					</div>
				</div>
				
				<div class="memo-actions col-3 d-flex align-items-center justify-content-end">
					<div class="btn-group-vertical btn-group-sm" role="group">
						<button type="button" class="btn btn-sm btn-outline-secondary btn-memo-edit" data-idx="${memo.idx}">수정</button>
						<button type="button" class="btn btn-sm btn-outline-danger btn-memo-delete" data-idx="${memo.idx}">삭제</button>
					</div>
				</div>
			</div>				
		</div>
	`;
	}

	/**
	 * 메모 저장
	 */
	function saveMemo() {
		const content = $('#newMemoContent').val().trim();

		if (!content) {
			showToast('메모 내용을 입력해주세요.', 'warning');
			return;
		}

		if (!currentMemberIdx) {
			showToast('회원 정보를 찾을 수 없습니다.', 'error');
			return;
		}

		$.ajax({
			url: '/member/save_memo',
			method: 'POST',
			data: {
				member_idx: currentMemberIdx,
				memo_content: content,
				memo_type: 1,
				org_id: selectedOrgId
			},
			dataType: 'json',
			success: function(response) {
				if (response.success) {
					$('#newMemoContent').val('');
					loadMemoList(currentMemberIdx);
					showToast('메모가 저장되었습니다.', 'success');
				} else {
					showToast(response.message || '메모 저장에 실패했습니다.', 'error');
				}
			},
			error: function() {
				showToast('메모 저장에 실패했습니다.', 'error');
			}
		});
	}

	/**
	 * 메모 수정 시작
	 */
	function startEditMemo(idx, currentContent) {
		editingMemoIdx = idx;
		const memoItem = $(`.memo-item[data-idx="${idx}"]`);

		const editHtml = `
			<div class="memo-content-edit-wrapper">
				<textarea class="form-control memo-content-edit" rows="3">${escapeHtml(currentContent)}</textarea>
				<div class="mt-2 text-end">
					<button type="button" class="btn btn-sm btn-success btn-memo-save" data-idx="${idx}">저장</button>
					<button type="button" class="btn btn-sm btn-secondary btn-memo-cancel">취소</button>
				</div>
			</div>
		`;

		memoItem.find('.memo-content').replaceWith(editHtml);
		memoItem.find('.memo-actions').hide();
	}

	/**
	 * 메모 수정 취소
	 */
	function cancelEditMemo() {
		if (editingMemoIdx) {
			loadMemoList(currentMemberIdx);
			editingMemoIdx = null;
		}
	}

	/**
	 * 메모 업데이트
	 */
	function updateMemo(idx, content) {
		if (!content.trim()) {
			showToast('메모 내용을 입력해주세요.', 'warning');
			return;
		}

		$.ajax({
			url: '/member/update_memo',
			method: 'POST',
			data: {
				idx: idx,
				memo_content: content.trim(),
				org_id: selectedOrgId
			},
			dataType: 'json',
			success: function(response) {
				if (response.success) {
					loadMemoList(currentMemberIdx);
					editingMemoIdx = null;
					showToast('메모가 수정되었습니다.', 'success');
				} else {
					showToast(response.message || '메모 수정에 실패했습니다.', 'error');
				}
			},
			error: function() {
				showToast('메모 수정에 실패했습니다.', 'error');
			}
		});
	}

	/**
	 * 메모 삭제 확인 모달 표시
	 */
	function showDeleteMemoModal(idx) {
		deletingMemoIdx = idx;
		const modal = new bootstrap.Modal(document.getElementById('deleteMemoModal'));
		modal.show();

		$('#confirmDeleteMemoBtn').off('click').on('click', function() {
			deleteMemo(deletingMemoIdx);
		});
	}

	/**
	 * 메모 삭제 실행
	 */
	function deleteMemo(idx) {
		if (!idx) {
			showToast('삭제할 메모를 선택해주세요.', 'error');
			return;
		}

		$.ajax({
			url: '/member/delete_memo',
			method: 'POST',
			data: {
				idx: idx,
				org_id: selectedOrgId
			},
			dataType: 'json',
			success: function(response) {
				if (response.success) {
					const modalElement = document.getElementById('deleteMemoModal');
					const modalInstance = bootstrap.Modal.getInstance(modalElement);
					if (modalInstance) {
						modalInstance.hide();
					}
					loadMemoList(currentMemberIdx);
					showToast(response.message || '메모가 삭제되었습니다.', 'success');
					deletingMemoIdx = null;
				} else {
					showToast(response.message || '메모 삭제에 실패했습니다.', 'error');
				}
			},
			error: function() {
				showToast('메모 삭제에 실패했습니다.', 'error');
			}
		});
	}

	/**
	 * 메모용 날짜 형식화
	 */
	function formatMemoDateTime(dateString) {
		const date = new Date(dateString);
		const year = date.getFullYear();
		const month = String(date.getMonth() + 1).padStart(2, '0');
		const day = String(date.getDate()).padStart(2, '0');
		const hours = String(date.getHours()).padStart(2, '0');
		const minutes = String(date.getMinutes()).padStart(2, '0');
		const seconds = String(date.getSeconds()).padStart(2, '0');

		return `${year}.${month}.${day} ${hours}:${minutes}:${seconds}`;
	}

	// ===== 사진 관련 함수들 =====

	/**
	 * 사진 관련 이벤트 바인딩 (Croppie 적용)
	 */
	function bindPhotoEvents() {
		$('#member_photo').off('change').on('change', handlePhotoFileSelect);
		$('#cropPhoto').off('click').on('click', handleCropButtonClick);
		$('#removePhoto').off('click').on('click', handleRemovePhotoClick);
		$('#saveCrop').off('click').on('click', saveCroppedImage);
		$('#cancelCrop').off('click').on('click', cancelCrop);
	}

	/**
	 * 사진 파일 선택 처리
	 */
	function handlePhotoFileSelect(e) {
		const file = e.target.files[0];
		if (!file) return;

		if (!validateImageFile(file)) {
			return;
		}

		$('#delete_photo').remove();

		const reader = new FileReader();
		reader.onload = function(e) {
			$('#previewImage').attr('src', e.target.result);
			$('#photoPreview').show();
			$('#photoUpload').hide();
		};
		reader.readAsDataURL(file);
	}

	/**
	 * 크롭 버튼 클릭 처리
	 */
	function handleCropButtonClick() {
		const imageSrc = $('#previewImage').attr('src');
		if (imageSrc) {
			initCroppie(imageSrc);
		}
	}

	/**
	 * 사진 삭제 버튼 처리
	 */
	function handleRemovePhotoClick() {
		$('#member_photo').val('');
		$('#photoPreview').hide();
		$('#photoUpload').show();
		destroyCroppie();

		let deletePhotoField = $('#delete_photo');
		if (deletePhotoField.length === 0) {
			$('#memberForm').append('<input type="hidden" id="delete_photo" name="delete_photo" value="">');
			deletePhotoField = $('#delete_photo');
		}
		deletePhotoField.val('Y');
	}

	/**
	 * Croppie 초기화
	 */
	function initCroppie(imageSrc) {
		destroyCroppie();

		$('#photoPreview').hide();
		$('#cropContainer').show();

		croppieInstance = new Croppie(document.getElementById('cropBox'), {
			viewport: {
				width: 150,
				height: 150,
				type: 'circle'
			},
			boundary: {
				width: 250,
				height: 250
			},
			showZoomer: true,
			enableResize: false,
			enableOrientation: true,
			mouseWheelZoom: 'ctrl'
		});

		croppieInstance.bind({
			url: imageSrc
		}).catch(function(error) {
			console.error('Croppie 바인딩 오류:', error);
			showToast('이미지 로드에 실패했습니다.', 'error');
			cancelCrop();
		});
	}

	/**
	 * 크롭된 이미지 저장
	 */
	function saveCroppedImage() {
		if (!croppieInstance) {
			showToast('크롭 인스턴스가 없습니다.', 'error');
			return;
		}

		croppieInstance.result({
			type: 'canvas',
			size: { width: 200, height: 200 },
			format: 'jpeg',
			quality: 0.9,
			circle: true
		}).then(function(croppedImage) {
			$('#previewImage').attr('src', croppedImage);

			dataURLtoFile(croppedImage, 'cropped_image.jpg').then(function(file) {
				const dt = new DataTransfer();
				dt.items.add(file);
				document.getElementById('member_photo').files = dt.files;

				$('#delete_photo').remove();
			});

			$('#cropContainer').hide();
			$('#photoPreview').show();
			destroyCroppie();

		}).catch(function(error) {
			console.error('크롭 처리 오류:', error);
			showToast('이미지 크롭에 실패했습니다.', 'error');
		});
	}

	/**
	 * 크롭 취소
	 */
	function cancelCrop() {
		$('#cropContainer').hide();
		$('#photoPreview').show();
		destroyCroppie();
	}

	/**
	 * Split.js 인스턴스 정리
	 */
	function destroySplitJS() {
		if (splitInstance) {
			try {
				splitInstance.destroy();
				splitInstance = null;
			} catch (error) {
				console.error('Split.js 정리 실패:', error);
			}
		}
	}

	/**
	 * Croppie 인스턴스 제거
	 */
	function destroyCroppie() {
		if (croppieInstance) {
			try {
				croppieInstance.destroy();
			} catch (error) {
				console.warn('Croppie 인스턴스 제거 중 오류:', error);
			}
			croppieInstance = null;
		}
	}

	/**
	 * 디바운스 함수
	 */
	function debounce(func, wait) {
		let timeout;
		return function executedFunction(...args) {
			const later = () => {
				clearTimeout(timeout);
				func(...args);
			};
			clearTimeout(timeout);
			timeout = setTimeout(later, wait);
		};
	}

	/**
	 * Base64 DataURL을 File 객체로 변환
	 */
	function dataURLtoFile(dataURL, filename) {
		return new Promise(function(resolve) {
			const arr = dataURL.split(',');
			const mime = arr[0].match(/:(.*?);/)[1];
			const bstr = atob(arr[1]);
			let n = bstr.length;
			const u8arr = new Uint8Array(n);

			while (n--) {
				u8arr[n] = bstr.charCodeAt(n);
			}

			const file = new File([u8arr], filename, { type: mime });
			resolve(file);
		});
	}

	/**
	 * 회원 저장 (수정된 버전)
	 */
	function saveMember() {
		if (!validateMemberForm()) {
			return;
		}

		if ($('#cropContainer').is(':visible')) {
			showToast('이미지 크롭을 완료하거나 취소해주세요.', 'warning');
			return;
		}

		// 상세정보 데이터 준비
		saveDetailData();

		const form = $('#memberForm')[0];
		const formData = new FormData(form);
		formData.append('org_id', selectedOrgId);

		const saveBtn = $('#btnSaveMember');
		const originalText = saveBtn.html();
		saveBtn.prop('disabled', true).html('<i class="bi bi-hourglass-split"></i> 저장 중...');

		const url = formData.get('member_idx') ?
			window.memberPageData.baseUrl + 'member/update_member' :
			window.memberPageData.baseUrl + 'member/add_member';

		$.ajax({
			url: url,
			method: 'POST',
			data: formData,
			processData: false,
			contentType: false,
			dataType: 'json',
			success: function(response) {
				handleSaveMemberResponse(response);
			},
			error: function(xhr, status, error) {
				console.error('회원 저장 실패:', error);
				showToast('회원 정보 저장에 실패했습니다.', 'error');
			},
			complete: function() {
				saveBtn.prop('disabled', false).html(originalText);
			}
		});
	}

	/**
	 * 회원 폼 유효성 검증
	 */
	function validateMemberForm() {
		const memberName = $('#member_name').val().trim();
		if (!memberName) {
			showToast('이름을 입력해주세요.', 'warning');
			$('#member_name').focus();
			return false;
		}
		return true;
	}

	/**
	 * 회원 저장 응답 처리
	 */
	function handleSaveMemberResponse(response) {
		const toastType = response.success ? 'success' : 'error';
		showToast(response.message, toastType);

		if (response.success) {
			const offcanvasInstance = bootstrap.Offcanvas.getInstance($('#memberOffcanvas')[0]);
			if (offcanvasInstance) {
				offcanvasInstance.hide();
			}
			// loadMemberData();
			refreshGroupTree();
		}
	}

	// ===== 상세정보 탭 관련 함수들 =====

	/**
	 * 상세정보 탭 초기화 (수정된 버전)
	 */
	function initDetailTab() {
		$('#detail-tab').on('click', function() {
			const orgId = $('#org_id').val();
			const memberIdx = $('#member_idx').val();

			// 이미 로드되었는지 확인
			const frontContainer = $('#detail-front');
			const backContainer = $('#detailFieldsContainer');

			// front나 back 컨테이너에 이미 필드가 있으면 다시 로드하지 않음
			if (frontContainer.children().length > 0 || backContainer.children().length > 0) {
				return;
			}

			if (orgId && memberIdx) {
				loadDetailFields(orgId, memberIdx);
			} else if (orgId) {
				loadDetailFields(orgId, null);
			}
		});
	}

	/**
	 * 상세필드 로드 및 폼 생성
	 */
	function loadDetailFields(orgId, memberIdx = null) {
		const frontContainer = $('#detail-front');
		const backContainer = $('#detailFieldsContainer');
		const loading = $('#detailFieldsLoading');
		const empty = $('#detailFieldsEmpty');

		loading.show();
		empty.hide();
		frontContainer.empty();
		backContainer.empty();

		$.ajax({
			url: '/member/get_detail_fields',
			type: 'POST',
			data: { org_id: orgId },
			dataType: 'json',
			success: function(response) {
				loading.hide();

				if (response.success && response.data.length > 0) {
					if (memberIdx) {
						getMemberDetailData(orgId, memberIdx, response.data);
					} else {
						generateDetailForm(response.data, {});
					}
				} else {
					empty.show();
					frontContainer.hide();
				}
			},
			error: function() {
				loading.hide();
				showToast('상세필드를 불러오는데 실패했습니다.', 'error');
				frontContainer.hide();
			}
		});
	}

	/**
	 * 회원 상세정보 데이터 가져오기
	 */
	function getMemberDetailData(orgId, memberIdx, fields) {
		$.ajax({
			url: '/member/get_member_detail',
			type: 'POST',
			data: {
				org_id: orgId,
				member_idx: memberIdx
			},
			dataType: 'json',
			success: function(response) {
				if (response.success) {
					generateDetailForm(fields, response.data);
				} else {
					generateDetailForm(fields, {});
				}
			},
			error: function() {
				generateDetailForm(fields, {});
			}
		});
	}

	/**
	 * 상세정보 폼 생성
	 */
	function generateDetailForm(fields, memberDetail) {
		const frontContainer = $('#detail-front');
		const backContainer = $('#detailFieldsContainer');

		frontContainer.empty();
		backContainer.empty();

		if (!fields || fields.length === 0) {
			return;
		}

		fields.forEach(function(field) {
			if (field.is_active !== 'Y') {
				return; // 비활성 필드는 건너뛰기
			}

			const fieldValue = memberDetail[field.field_idx] || '';
			const fieldHtml = generateFieldHtml(field, fieldValue);

			// field_position에 따라 다른 컨테이너에 추가
			if (field.field_position === 'front') {
				frontContainer.append(fieldHtml);
			} else {
				backContainer.append(fieldHtml);
			}
		});

		// front 영역에 필드가 있으면 보이도록 설정
		if (frontContainer.children().length > 0) {
			frontContainer.show();
		} else {
			frontContainer.hide();
		}
	}

	/**
	 * 필드별 HTML 생성
	 */
	/**
	 * 필드별 HTML 생성
	 */
	function generateFieldHtml(field, value) {
		const fieldId = 'detail_field_' + field.field_idx;
		const fieldName = 'detail_field[' + field.field_idx + ']';

		// field_size에 따라 col 클래스 결정 (수정된 버전)
		let colClass = 'col-12'; // 기본값
		if (field.field_size == 1) {
			colClass = 'col-6 col-sm-4';
		} else if (field.field_size == 2) {
			colClass = 'col-8 col-sm-6';
		} else if (field.field_size == 3) {
			colClass = 'col-12';
		}

		let inputHtml = '';

		switch (field.field_type) {
			case 'text':
				inputHtml = `<input type="text" class="form-control" id="${fieldId}" name="${fieldName}" value="${escapeHtml(value)}">`;
				break;

			case 'select':
				inputHtml = generateSelectHtml(field, value, fieldId, fieldName);
				break;

			case 'textarea':
				inputHtml = `<textarea class="form-control" id="${fieldId}" name="${fieldName}" rows="3">${escapeHtml(value)}</textarea>`;
				break;

			case 'checkbox':
				const checked = value === 'Y' ? 'checked' : '';
				inputHtml = `
			<div class="form-check">
				<input class="form-check-input" type="checkbox" id="${fieldId}" name="${fieldName}" value="Y" ${checked}>
				<label class="form-check-label" for="${fieldId}">${escapeHtml(field.field_name)}</label>
			</div>
		`;
				break;

			case 'date':
				inputHtml = `<input type="date" class="form-control" id="${fieldId}" name="${fieldName}" value="${value}">`;
				break;

			default:
				inputHtml = `<input type="text" class="form-control" id="${fieldId}" name="${fieldName}" value="${escapeHtml(value)}">`;
		}

		const labelHtml = field.field_type === 'checkbox' ? '' : `<label for="${fieldId}" class="form-label">${escapeHtml(field.field_name)}</label>`;

		return `
	<div class="${colClass} mb-3">
		${labelHtml}
		${inputHtml}
	</div>
`;
	}

	/**
	 * 선택박스 HTML 생성
	 */
	function generateSelectHtml(field, value, fieldId, fieldName) {
		let options = '<option value="">선택하세요</option>';

		if (field.field_settings && field.field_settings.options) {
			field.field_settings.options.forEach(function(option) {
				const selected = value === option ? 'selected' : '';
				options += `<option value="${escapeHtml(option)}" ${selected}>${escapeHtml(option)}</option>`;
			});
		}

		return `<select class="form-select" id="${fieldId}" name="${fieldName}">${options}</select>`;
	}


	/**
	 * 상세정보 저장 - member_detail 컬럼 활용 (수정된 버전)
	 */
	function saveDetailData() {
		const orgId = $('#org_id').val();
		const memberIdx = $('#member_idx').val();

		if (!orgId || !memberIdx) {
			return true;
		}

		const detailData = {};

		// front와 back 영역 모두에서 입력값 수집
		$('#detail-front input, #detail-front select, #detail-front textarea, #detailFieldsContainer input, #detailFieldsContainer select, #detailFieldsContainer textarea').each(function() {
			const name = $(this).attr('name');
			if (name && name.startsWith('detail_field[')) {
				const fieldIdx = name.match(/detail_field\[(\d+)\]/)[1];

				if ($(this).is(':checkbox')) {
					detailData[fieldIdx] = $(this).is(':checked') ? 'Y' : 'N';
				} else {
					detailData[fieldIdx] = $(this).val() || '';
				}
			}
		});

		// 기존 히든 필드 제거 (중복 방지)
		$('input[name="detail_field"]').remove();

		// 상세필드 데이터를 폼에 추가 (히든 필드로) - 폼의 직접 자식으로 추가
		$('#memberForm').append(
			'<input type="hidden" name="detail_field" value=\'' + JSON.stringify(detailData) + '\' />'
		);

		return true;
	}

	// ===== 유틸리티 함수들 =====

	/**
	 * 이미지 파일 유효성 검사
	 */
	function validateImageFile(file) {
		const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
		if (!allowedTypes.includes(file.type)) {
			showToast('지원하지 않는 이미지 형식입니다. (JPG, PNG, GIF만 가능)', 'error');
			return false;
		}

		const maxSize = 5 * 1024 * 1024;
		if (file.size > maxSize) {
			showToast('파일 크기는 5MB 이하만 가능합니다.', 'error');
			return false;
		}

		return true;
	}

	/**
	 * 이미지 로드 에러 처리
	 */
	function handleImageLoadError() {
		showToast('이미지를 불러올 수 없습니다.', 'error');
		$('#photoPreview').hide();
		$('#photoUpload').show();
		$('#member_photo').val('');
		destroyCroppie();
	}



	/**
	 * 엑셀편집 팝업 열기 (수정된 버전 - 전체 컬럼 데이터 전달)
	 */
	function openExcelEditPopup() {
		if (!memberGrid) {
			showToast('그리드가 초기화되지 않았습니다.', 'error');
			return;
		}

		try {
			// 현재 그리드의 모든 데이터 가져오기 (원본 그대로)
			const gridData = memberGrid.pqGrid("option", "dataModel.data");

			// if (!gridData || gridData.length === 0) {
			// 	showToast('편집할 데이터가 없습니다.', 'info');
			// 	return;
			// }

			// 현재 그리드의 컬럼 모델 가져오기
			const colModel = memberGrid.pqGrid("option", "colModel");

			// 세션 스토리지에 데이터와 컬럼 모델 저장
			sessionStorage.setItem('bulkEditData', JSON.stringify(gridData));
			sessionStorage.setItem('bulkEditColumns', JSON.stringify(colModel));

			// 팝업 창 열기
			const popupWidth = 1400;
			const popupHeight = 800;
			const left = (window.screen.width - popupWidth) / 2;
			const top = (window.screen.height - popupHeight) / 2;

			const popup = window.open(
				'/member/member_popup',
				'memberPopup',
				`width=${popupWidth},height=${popupHeight},left=${left},top=${top},scrollbars=yes,resizable=yes`
			);

			if (!popup) {
				showToast('팝업이 차단되었습니다. 팝업 차단을 해제해주세요.', 'error');
				return;
			}

			// postMessage 리스너 등록 (기존 리스너가 있으면 제거)
			window.removeEventListener('message', handleBulkEditMessage);
			window.addEventListener('message', handleBulkEditMessage);

		} catch (error) {
			console.error('엑셀편집 팝업 열기 오류:', error);
			showToast('엑셀편집 팝업을 여는 중 오류가 발생했습니다.', 'error');
		}
	}

	/**
	 * 팝업에서 전송된 메시지 처리
	 */
	function handleBulkEditMessage(event) {
		// 보안: 같은 origin인지 확인
		if (event.origin !== window.location.origin) {
			return;
		}

		// 메시지 타입 확인
		if (event.data && event.data.type === 'bulkEditComplete') {
			const editedData = event.data.data;

			if (!editedData || editedData.length === 0) {
				showToast('수정된 데이터가 없습니다.', 'warning');
				return;
			}

			// 수정된 데이터 저장
			saveBulkEditData(editedData);
		}
	}

	/**
	 * 일괄 편집된 데이터를 서버에 저장
	 */
	function saveBulkEditData(editedData) {
		if (!selectedOrgId) {
			showToast('조직 정보를 찾을 수 없습니다.', 'error');
			return;
		}

		// 확인 모달 표시
		showConfirmModal(
			'엑셀편집 저장',
			`총 ${editedData.length}건의 데이터를 저장하시겠습니까?`,
			function() {
				// 저장 실행
				executeBulkEditSave(editedData);
			}
		);
	}

	/**
	 * 일괄 편집 저장 실행
	 */
	function executeBulkEditSave(editedData) {
		const saveBtn = $('#btnExcelEdit');
		const originalText = saveBtn.text();

		saveBtn.prop('disabled', true).html('<i class="bi bi-hourglass-split"></i> 저장 중...');

		$.ajax({
			url: window.memberPageData.baseUrl + 'member/save_member_popup',
			method: 'POST',
			data: {
				org_id: selectedOrgId,
				members: JSON.stringify(editedData)
			},
			dataType: 'json',
			success: function(response) {
				const toastType = response.success ? 'success' : 'error';
				showToast(response.message, toastType);

				if (response.success) {
					// 그리드 새로고침
					// loadMemberData();
					// 트리 새로고침 (인원수 변경 가능성)
					refreshGroupTree();
				}
			},
			error: function(xhr, status, error) {
				console.error('일괄 편집 저장 실패:', error);
				showToast('데이터 저장 중 오류가 발생했습니다.', 'error');
			},
			complete: function() {
				saveBtn.prop('disabled', false).html(originalText);
			}
		});
	}



	// ===== 회원 저장 버튼 클릭 이벤트 수정 =====

	$(document).on('click', '#btnSaveMember', function() {
		const form = $('#memberForm')[0];
		const formData = new FormData(form);

		// 상세정보 저장
		saveDetailData();

		$.ajax({
			url: '/member/update_member',
			type: 'POST',
			data: formData,
			processData: false,
			contentType: false,
			dataType: 'json',
			success: function(response) {
				const toastType = response.success ? 'success' : 'error';
				showToast(response.message, toastType);
				if (response.success) {
					loadMemberData();
					$('#memberOffcanvas').offcanvas('hide');
				}
			},
			error: function() {
				showToast('회원 정보 저장 중 오류가 발생했습니다.', 'error');
			}
		});
	});


	// 파일 위치: assets/js/member.js
// 역할: 페이지 로딩 시 스피너 표시/숨김 관련 함수들

	/**
	 * 트리 스피너 표시
	 */
	function showTreeSpinner() {
		$('#treeSpinner').removeClass('d-none').addClass('d-flex');

	}

	/**
	 * 트리 스피너 숨김
	 */
	function hideTreeSpinner() {
		$('#treeSpinner').removeClass('d-flex').addClass('d-none');
	}

	/**
	 * 그리드 스피너 표시
	 */
	function showGridSpinner() {
		$('#gridSpinner').removeClass('d-none').addClass('d-flex');
	}

	/**
	 * 그리드 스피너 숨김
	 */
	function hideGridSpinner() {
		$('#gridSpinner').removeClass('d-flex').addClass('d-none');

	}

	/**
	 * 모든 스피너 표시 (초기 로딩 시)
	 */
	function showAllSpinners() {
		showTreeSpinner();
		showGridSpinner();

	}

	/**
	 * 모든 스피너 숨김
	 */
	function hideAllSpinners() {
		hideTreeSpinner();
		hideGridSpinner();

	}



// 선택QR인쇄 버튼 클릭 이벤트 (수정된 버전)
	$(document).on('click', '#btnSelectedQrPrint', function() {
		const selectedMembers = getSelectedMembers();



		if (selectedMembers.length === 0) {
			showToast('인쇄할 회원을 선택해주세요.', 'warning');
			return;
		}

		if (selectedMembers.length > 70) {
			showConfirmModal(
				'선택된 회원이 70명을 초과합니다',
				'선택된 회원(' + selectedMembers.length + '명)이 한 장의 라벨지 용량(70개)을 초과합니다. 여러 장에 나누어 인쇄됩니다. 계속하시겠습니까?',
				function() {
					openSelectedQrPrintModal(selectedMembers);
				}
			);
		} else {
			openSelectedQrPrintModal(selectedMembers);
		}
	});


	/**
	 * 선택된 회원 목록 가져오기 (최종 버전)
	 */
	function getSelectedMembers() {
		const selectedMembers = [];
		const processedMemberIds = new Set();

		// 고유한 체크된 체크박스만 처리
		$('.member-checkbox:checked').each(function() {
			const memberIdx = $(this).data('member-idx');

			// 중복 제거
			if (processedMemberIds.has(memberIdx)) {
				return true; // continue
			}

			processedMemberIds.add(memberIdx);

			// 그리드에서 해당 회원의 데이터 찾기
			if (memberGrid && memberGrid.length > 0) {
				const gridData = memberGrid.pqGrid('option', 'dataModel.data');
				const memberData = gridData.find(row => row.member_idx == memberIdx);

				if (memberData) {
					selectedMembers.push({
						member_idx: memberData.member_idx,
						member_name: memberData.member_name,
						area_name: memberData.area_name
					});
				}
			}
		});


		return selectedMembers;
	}

	/**
	 * 선택QR인쇄 모달 열기 (수정된 버전)
	 */
	function openSelectedQrPrintModal(selectedMembers) {


		$('#selectedMemberCount').val(selectedMembers.length + '명 선택됨');

		// 모달에 선택된 회원 정보 저장
		$('#selectedQrPrintModal').data('selectedMembers', selectedMembers);

		// 시작 위치 초기화
		$('#startPositionSelect').val('1');

		$('#selectedQrPrintModal').modal('show');
	}




	/**
	 * 인쇄하기 버튼 클릭 이벤트 (수정된 버전)
	 */
	$(document).on('click', '#executePrintSelectedQr', function() {
		const selectedMembers = $('#selectedQrPrintModal').data('selectedMembers');
		const startPosition = $('#startPositionSelect').val();



		if (!selectedMembers || selectedMembers.length === 0) {
			showToast('선택된 회원이 없습니다.', 'error');
			return;
		}

		// 회원 인덱스 배열 생성
		const memberIndices = selectedMembers.map(member => member.member_idx);

		// URL 생성
		const url = '/member/print_selected_qr?' +
			'members=' + memberIndices.join(',') +
			'&start_position=' + startPosition;



		// 새 창에서 인쇄 페이지 열기
		window.open(url, '_blank', 'width=1200,height=800,scrollbars=yes');

		// 모달 닫기
		$('#selectedQrPrintModal').modal('hide');
	});




	/**
	 * 조직별 직위/직분 및 직책 옵션 로드
	 */
	function loadPositionsAndDuties(orgId, callback) {
		if (!orgId) {
			if (typeof callback === 'function') {
				callback();
			}
			return;
		}

		$.ajax({
			url: window.memberPageData.baseUrl + 'member/get_org_positions_duties',
			method: 'POST',
			data: { org_id: orgId },
			dataType: 'json',
			success: function(response) {
				if (response.success) {
					populatePositionOptions(response.data.positions);
					populateDutyOptions(response.data.duties);
				} else {
					console.error('직위/직책 데이터 로드 실패:', response.message);
				}

				if (typeof callback === 'function') {
					callback();
				}
			},
			error: function(xhr, status, error) {
				console.error('직위/직책 데이터 로드 오류:', error);

				if (typeof callback === 'function') {
					callback();
				}
			}
		});
	}

	/**
	 * 직위/직분 선택 옵션 채우기
	 */
	function populatePositionOptions(positions) {
		const positionSelect = $('#position_name');
		positionSelect.html('<option value="">직위/직분 선택</option>');

		if (positions && Array.isArray(positions)) {
			positions.forEach(function(position) {
				positionSelect.append(`<option value="${escapeHtml(position)}">${escapeHtml(position)}</option>`);
			});
		}
	}

	/**
	 * 직책 선택 옵션 채우기
	 */
	function populateDutyOptions(duties) {
		const dutySelect = $('#duty_name');
		dutySelect.html('<option value="">직책 선택</option>');

		if (duties && Array.isArray(duties)) {
			duties.forEach(function(duty) {
				dutySelect.append(`<option value="${escapeHtml(duty)}">${escapeHtml(duty)}</option>`);
			});
		}
	}



	/**
	 * 컬럼 순서를 localStorage에 저장
	 */
	function saveColumnOrder(colModel) {
		if (!selectedOrgId) return;

		try {
			// 컬럼의 dataIndx만 순서대로 저장 (필수 컬럼 제외)
			const columnOrder = colModel
				.filter(col => col.dataIndx && col.dataIndx !== 'pq_selected')
				.map(col => col.dataIndx);

			const storageKey = 'member_column_order_' + selectedOrgId;
			localStorage.setItem(storageKey, JSON.stringify(columnOrder));


		} catch (error) {
			console.error('컬럼 순서 저장 실패:', error);
		}
	}

	/**
	 * localStorage에서 컬럼 순서 불러오기
	 */
	function loadColumnOrder() {
		if (!selectedOrgId) return null;

		try {
			const storageKey = 'member_column_order_' + selectedOrgId;
			const savedOrder = localStorage.getItem(storageKey);

			if (savedOrder) {
				const columnOrder = JSON.parse(savedOrder);

				return columnOrder;
			}
		} catch (error) {
			console.error('컬럼 순서 로드 실패:', error);
		}

		return null;
	}

	/**
	 * 저장된 순서에 따라 컬럼 모델 재정렬
	 */
	function reorderColumnModel(colModel, savedOrder) {
		if (!savedOrder || savedOrder.length === 0) {
			return colModel;
		}

		// 체크박스 컬럼은 항상 첫 번째에 고정
		const checkboxCol = colModel.find(col => col.dataIndx === 'pq_selected');
		const otherCols = colModel.filter(col => col.dataIndx !== 'pq_selected');

		// 저장된 순서대로 재정렬
		const reorderedCols = [];
		const colMap = new Map(otherCols.map(col => [col.dataIndx, col]));

		// 저장된 순서대로 먼저 추가
		savedOrder.forEach(dataIndx => {
			if (colMap.has(dataIndx)) {
				reorderedCols.push(colMap.get(dataIndx));
				colMap.delete(dataIndx);
			}
		});

		// 저장된 순서에 없는 새로운 컬럼들은 뒤에 추가 (상세필드가 추가된 경우)
		colMap.forEach(col => {
			reorderedCols.push(col);
		});

		// 체크박스 컬럼을 맨 앞에 추가
		return checkboxCol ? [checkboxCol, ...reorderedCols] : reorderedCols;
	}


	/**
	 * 파송교회 모달 열기
	 */
	/**
	 * 파송교회 모달 열기
	 */
	function openTransferOrgModal(mode, idx = null, transferData = null) {
		const modal = $('#transferOrgModal');
		const form = $('#transferOrgForm')[0];

		form.reset();
		$('#transfer_org_idx').val('');

		// Select2 초기화 및 태그 입력 설정
		if (!$('#org_tags').hasClass('select2-hidden-accessible')) {
			$('#org_tags').select2({
				tags: true,
				tokenSeparators: [' ', ','],
				placeholder: '태그를 입력하세요 (예: 병장 상병)',
				dropdownParent: $('#transferOrgModal'),
				createTag: function(params) {
					const term = $.trim(params.term);
					if (term === '') {
						return null;
					}
					const tagText = term.replace(/^#/, '');
					return {
						id: tagText,
						text: tagText,
						newTag: true
					};
				}
			});
		}

		if (mode === 'add') {
			$('#transferOrgModalLabel').text('파송교회 추가');
			$('#transfer_member_idx').val(currentMemberMissionIdx);
			$('#org_tags').val(null).trigger('change');

		} else if (mode === 'edit' && transferData) {
			$('#transferOrgModalLabel').text('파송교회 수정');

			$('#transfer_org_idx').val(idx);
			$('#transfer_member_idx').val(currentMemberMissionIdx);

			// 폼 데이터 채우기 (변경된 필드명)
			$('#transfer_region').val(transferData.transfer_org_address || '');
			$('#transfer_name').val(transferData.transfer_org_name || '');
			$('#pastor_name').val(transferData.transfer_org_rep || '');
			$('#contact_person').val(transferData.transfer_org_manager || '');
			$('#contact_phone').val(transferData.transfer_org_phone || '');
			$('#contact_email').val(transferData.transfer_org_email || '');
			$('#transfer_description').val(transferData.transfer_org_desc || '');
			$('#transfer_org_id').val(transferData.transfer_org_id || '');

			// 태그 데이터 파싱 및 설정
			if (transferData.transfer_org_tag) {
				const tags = transferData.transfer_org_tag.split(',').map(tag => tag.trim()).filter(tag => tag.length > 0);

				if (tags.length > 0) {
					tags.forEach(function(tag) {
						const displayTag = tag.replace(/^#/, '');
						if (!$('#org_tags').find(`option[value='${displayTag}']`).length) {
							const newOption = new Option(displayTag, displayTag, true, true);
							$('#org_tags').append(newOption);
						}
					});

					$('#org_tags').val(tags.map(tag => tag.replace(/^#/, ''))).trigger('change');
				}
			} else {
				$('#org_tags').val(null).trigger('change');
			}
		}

		modal.modal('show');
	}


	/**
	 * 상세필드 값 렌더링
	 */
	function renderDetailFieldValue(value, fieldType) {
		if (!value) return '';

		switch(fieldType) {
			case 'checkbox':
				return value === 'Y' ? '<i class="bi bi-check-circle-fill text-success"></i>' : '';
			case 'date':
				return formatDateOnly(value);
			case 'textarea':
				// 긴 텍스트는 일부만 표시
				return value.length > 50 ? value.substring(0, 50) + '...' : value;
			default:
				return escapeHtml(value);
		}
	}


	/**
	 * 날짜만 포맷팅 (yyyy-mm-dd)
	 */
	function formatDateOnly(dateString) {
		if (!dateString) return '';
		const date = new Date(dateString);
		const year = date.getFullYear();
		const month = String(date.getMonth() + 1).padStart(2, '0');
		const day = String(date.getDate()).padStart(2, '0');
		return `${year}-${month}-${day}`;
	}


	/**
	 * HTML 이스케이프 함수
	 */
	function escapeHtml(text) {
		if (!text) return '';
		const map = {
			'&': '&amp;',
			'<': '&lt;',
			'>': '&gt;',
			'"': '&quot;',
			"'": '&#039;'
		};
		return text.toString().replace(/[&<>"']/g, function(m) { return map[m]; });
	}


	/**
	 * 파송교회 목록 렌더링 (수정: 선택 상태 표시)
	 */
	function renderTransferOrgList(churches) {
		const listContainer = $('#transferOrgList');
		const emptyMessage = $('#emptyTransferOrgMessage');

		if (!churches || churches.length === 0) {
			emptyMessage.text('파송교회 정보가 없습니다.').show();
			listContainer.empty();
			return;
		}

		emptyMessage.hide();
		listContainer.empty();

		churches.forEach(function(church) {
			// 선택 상태 확인
			const isSelected = church.is_selected === 'selected';
			const selectedBadge = isSelected ? '<span class="badge bg-success ms-2">선택됨</span>' : '';
			const borderClass = isSelected ? 'border-success' : '';

			// 태그 파싱 및 표시
			let tagsHtml = '';
			if (church.transfer_org_tag) {
				const tags = church.transfer_org_tag.split(',').map(tag => tag.trim()).filter(tag => tag.length > 0);
				if (tags.length > 0) {
					tagsHtml = '<div class="mt-2">';
					tags.forEach(tag => {
						const displayTag = tag.replace(/^#/, '');
						tagsHtml += `<span class="badge bg-secondary me-1">${displayTag}</span>`;
					});
					tagsHtml += '</div>';
				}
			}

			const churchItem = `
        <div class="mission-church-item border rounded p-3 mb-3 ${borderClass}" data-church-data='${JSON.stringify(church)}'>
            <div class="d-flex justify-content-between align-items-center">
                <div class="flex-grow-1">
                    <h5 class="mb-2">
                        <strong>${church.transfer_org_name || '교회명 미등록'}</strong>                        
                        <small class="ms-2">${church.transfer_org_rep || ''} ${church.transfer_org_rep ? '담임목사' : ''}</small>
                        ${selectedBadge}
                    </h5>
                    <div class=""><i class="bi bi-geo-alt"></i> ${church.transfer_org_address || '지역 미등록'}</div>
                    <div class="text-muted d-flex justify-content-start align-items-center">
                        ${church.transfer_org_manager ? '<span class="me-3"><i class="bi bi-person-badge"></i> ' + church.transfer_org_manager + '</span>': ''}
                        ${church.transfer_org_phone ? '<span class="me-3"><i class="bi bi-telephone"></i> ' + church.transfer_org_phone + '</span>': ''}
                        ${church.transfer_org_email ? '<span class="me-3"><i class="bi bi-envelope"></i> ' + church.transfer_org_email + '</span>': ''}
                    </div>
                    ${church.transfer_org_desc ? '<div class="mt-2 text-muted small">' + church.transfer_org_desc + '</div>' : ''}
                    ${tagsHtml}
                </div>
                <div class="btn-group">
                    <button type="button" class="btn btn-sm btn-outline-primary btn-mission-edit" data-idx="${church.idx}">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger btn-mission-delete" data-idx="${church.idx}">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </div>
        </div>
        `;

			listContainer.append(churchItem);
		});
	}

	/**
	 * 파송교회 아이템 HTML 생성
	 */
	function createTransferOrgItemHtml(church) {
		// church.org_tags -> church.org_tag
		const tags = church.org_tag ? church.org_tag.split(' ').filter(tag => tag.trim().startsWith('#')) : [];
		const tagsHtml = tags.map(tag => `<span class="transfer-org-tag">${escapeHtml(tag)}</span>`).join('');

		return `
		<div class="transfer-org-item border p-3 rounded mb-2" data-idx="${church.idx}">
			<div class="transfer-org-header d-flex justify-content-between align-items-center pb-2">
				<h5 class="transfer-org-title mb-0">
					${escapeHtml(church.org_address || '')} <b>${escapeHtml(church.transfer_org_name || '')}</b> 
					${church.org_rep ? '(' + escapeHtml(church.org_rep) + ' 담임목사)' : ''} 
				</h5>
				<div class="transfer-org-actions">
					<button type="button" class="btn btn-xs btn-outline-secondary btn-mission-edit" data-idx="${church.idx}">수정</button>
					<button type="button" class="btn btn-xs btn-outline-danger btn-mission-delete" data-idx="${church.idx}">삭제</button>
				</div>
			</div>
			
			<div class="transfer-org-info d-flex justify-content-start align-items-center mb-1">
				${church.org_manager ? ` 
					<div class="transfer-org-info-item me-3">
						<i class="bi bi-person"></i>
						<span>${escapeHtml(church.org_manager)}</span>
					</div>
				` : ''}
				${church.org_phone ? ` 
					<div class="transfer-org-info-item me-3">
						<i class="bi bi-telephone"></i>
						<span>${escapeHtml(church.org_phone)}</span>
					</div>
				` : ''}
				${church.contact_email ? `
					<div class="transfer-org-info-item me-3">
						<i class="bi bi-envelope"></i>
						<span>${escapeHtml(church.contact_email)}</span>
					</div>
				` : ''}
			</div>
			
			${church.org_desc ? ` 
				<div class="transfer-org-description">
					${escapeHtml(church.org_desc)}
				</div>
			` : ''}
			
			${tagsHtml ? `
				<div class="transfer-org-tags text-secondary">
					${tagsHtml}
				</div>
			` : ''}
		</div>
	`;
	}



	/**
	 * 파송교회 삭제 확인 모달 표시
	 */
	function showDeleteTransferOrgModal(idx) {
		$('#confirmDeleteTransferOrgBtn').data('idx', idx);
		// FIX 4: member_idx를 confirm 버튼에 저장 (deleteTransferOrg에서 사용)
		$('#confirmDeleteTransferOrgBtn').data('member-idx', currentMemberMissionIdx);
		$('#deleteTransferOrgModal').modal('show');
	}

	/**
	 * 파송교회 삭제 실행
	 */
	function deleteTransferOrg(idx) {
		// FIX 5: confirmDeleteTransferOrgBtn에서 member_idx를 가져옵니다.
		const memberIdx = $('#confirmDeleteTransferOrgBtn').data('member-idx');

		$.ajax({
			url: '/member/delete_transfer_org',
			method: 'POST',
			data: {
				idx: idx,
				org_id: selectedOrgId,
				member_idx: memberIdx // FIX 6: member_idx 추가
			},
			dataType: 'json',
			success: function(response) {
				if (response.success) {
					$('#deleteTransferOrgModal').modal('hide');
					loadTransferOrgList(currentMemberMissionIdx);
					showToast('파송교회가 삭제되었습니다.', 'success');
				} else {
					showToast(response.message || '파송교회 삭제에 실패했습니다.', 'error');
				}
			},
			error: function() {
				showToast('파송교회 삭제에 실패했습니다.', 'error');
			}
		});
	}

	/**
	 * 요소에서 파송교회 데이터 추출
	 */

	function getTransferOrgDataFromElement(element) {
		// FIX: bindMissionTabEvents에서 직접 데이터를 가져오므로, 이 함수는 사용되지 않습니다.
		// 만약 호출된다면 저장된 데이터를 반환합니다.
		return element.data('transferData') || {};
	}
	/**
	 * 회원에게 이메일 전송
	 */
	function sendEmailToMember() {
		if (!currentMemberMissionIdx) {
			showToast('회원 정보를 찾을 수 없습니다.', 'error');
			return;
		}

		showToast('회원에게 이메일 전송 기능은 준비 중입니다.', 'info');
	}

	/**
	 * 결연교회 이메일 전송
	 */
	function sendEmailToChurch() {
		if (!currentMemberMissionIdx) {
			showToast('회원 정보를 찾을 수 없습니다.', 'error');
			return;
		}

		showToast('결연교회 이메일 전송 기능은 준비 중입니다.', 'info');
	}





	/**
	 * 자동매칭 모달 이벤트 바인딩
	 */
	function bindAutoMatchModalEvents() {
		// 전체 선택 체크박스
		$(document).off('change', '#selectAllMatchChurch').on('change', '#selectAllMatchChurch', function() {
			const isChecked = $(this).is(':checked');
			$('.match-church-checkbox').prop('checked', isChecked);
			updateMatchSelectionInfo();
		});

		// 개별 체크박스
		$(document).off('change', '.match-church-checkbox').on('change', '.match-church-checkbox', function() {
			updateMatchSelectAllCheckbox();
			updateMatchSelectionInfo();
		});

		// 검색 버튼
		$(document).off('click', '#searchMatchChurchBtn').on('click', '#searchMatchChurchBtn', function() {
			searchMatchChurch();
		});

		// 동일지역 자동매칭 버튼
		$(document).off('click', '#autoMatchByRegionBtn').on('click', '#autoMatchByRegionBtn', function() {
			autoMatchByRegion();
		});

		// 추가 확인 버튼
		$(document).off('click', '#confirmMatchChurchBtn').on('click', '#confirmMatchChurchBtn', function() {
			confirmAddMatchedChurches();
		});

		// Enter 키로 검색
		$(document).off('keypress', '#matchSearchKeyword').on('keypress', '#matchSearchKeyword', function(e) {
			if (e.which === 13) {
				e.preventDefault();
				searchMatchChurch();
			}
		});
	}


	/**
	 * 교회 검색
	 */
	function searchMatchChurch() {
		const searchType = $('#matchSearchType').val();
		const keyword = $('#matchSearchKeyword').val().trim();

		if (!keyword) {
			showToast('검색어를 입력하세요.', 'warning');
			return;
		}

		const searchData = {
			org_id: selectedOrgId,
			search_type: searchType,
			keyword: keyword
		};

		$.ajax({
			url: '/member/search_match_church',
			method: 'POST',
			data: searchData,
			dataType: 'json',
			beforeSend: function() {
				$('#matchChurchListBody').html(`
                <tr>
                    <td colspan="5" class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">검색 중...</span>
                        </div>
                    </td>
                </tr>
            `);
			},
			success: function(response) {
				if (response.success) {
					renderMatchChurchList(response.data);
					showToast(`${response.data.length}개의 교회를 찾았습니다.`, 'success');
				} else {
					showToast(response.message || '검색 결과가 없습니다.', 'info');
					$('#matchChurchListBody').html(`
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">
                            검색 결과가 없습니다.
                        </td>
                    </tr>
                `);
				}
			},
			error: function() {
				showToast('검색 중 오류가 발생했습니다.', 'error');
			}
		});
	}


	/**
	 * 동일지역 자동매칭
	 */
	function autoMatchByRegion() {
		const btn = $('#autoMatchByRegionBtn');
		const originalHtml = btn.html();

		btn.prop('disabled', true).html('<i class="bi bi-hourglass-split"></i> 매칭 중...');

		$.ajax({
			url: '/member/auto_match_by_region',
			method: 'POST',
			data: {
				member_idx: currentMemberMissionIdx,
				org_id: selectedOrgId
			},
			dataType: 'json',
			success: function(response) {
				if (response.success && response.data && response.data.length > 0) {
					renderMatchChurchList(response.data);
					showToast(`${response.data.length}개의 교회를 찾았습니다.`, 'success');
				} else {
					showToast(response.message || '동일 지역 교회를 찾을 수 없습니다.', 'info');
					$('#matchChurchListBody').html(`
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">
                            동일 지역 교회를 찾을 수 없습니다.
                        </td>
                    </tr>
                `);
				}
			},
			error: function() {
				showToast('자동매칭 중 오류가 발생했습니다.', 'error');
			},
			complete: function() {
				btn.prop('disabled', false).html(originalHtml);
			}
		});
	}


	/**
	 * 매칭 교회 목록 렌더링
	 */
	function renderMatchChurchList(churches) {
		const tbody = $('#matchChurchListBody');
		tbody.empty();

		if (!churches || churches.length === 0) {
			tbody.html(`
            <tr>
                <td colspan="5" class="text-center text-muted py-4">
                    검색 결과가 없습니다.
                </td>
            </tr>
        `);
			return;
		}

		churches.forEach(function(church) {
			const tags = church.org_tag ? church.org_tag.split(',').map(tag => tag.trim()).filter(tag => tag) : [];
			const tagsHtml = tags.map(tag => `<span class="badge bg-secondary me-1">${escapeHtml(tag)}</span>`).join('');

			const row = `
            <tr>
                <td>
                    <input type="checkbox" class="form-check-input match-church-checkbox" 
                        data-church='${JSON.stringify(church).replace(/'/g, "&apos;")}'>
                </td>
                <td>${escapeHtml(church.org_name || '')}</td>
                <td>${escapeHtml(church.org_rep || '')}</td>
                <td>${escapeHtml(church.org_address || '')}</td>
                <td>${tagsHtml}</td>
            </tr>
        `;
			tbody.append(row);
		});

		$('#selectAllMatchChurch').prop('checked', false);
		updateMatchSelectionInfo();
	}

	/**
	 * 전체 선택 체크박스 상태 업데이트
	 */
	function updateMatchSelectAllCheckbox() {
		const totalCheckboxes = $('.match-church-checkbox').length;
		const checkedCheckboxes = $('.match-church-checkbox:checked').length;
		const selectAllCheckbox = $('#selectAllMatchChurch');

		if (checkedCheckboxes === 0) {
			selectAllCheckbox.prop('checked', false).prop('indeterminate', false);
		} else if (checkedCheckboxes === totalCheckboxes) {
			selectAllCheckbox.prop('checked', true).prop('indeterminate', false);
		} else {
			selectAllCheckbox.prop('checked', false).prop('indeterminate', true);
		}
	}

	/**
	 * 선택 정보 업데이트
	 */
	function updateMatchSelectionInfo() {
		const checkedCount = $('.match-church-checkbox:checked').length;

		$('#matchSelectedCount').text(checkedCount);

		if (checkedCount > 0) {
			$('#matchSelectionInfo').show();
			$('#confirmMatchChurchBtn').prop('disabled', false);
		} else {
			$('#matchSelectionInfo').hide();
			$('#confirmMatchChurchBtn').prop('disabled', true);
		}
	}


	/**
	 * 선택된 교회 추가 확인
	 */
	function confirmAddMatchedChurches() {
		const selectedChurches = [];

		$('.match-church-checkbox:checked').each(function() {
			const churchData = $(this).data('church');
			selectedChurches.push(churchData);
		});

		if (selectedChurches.length === 0) {
			showToast('추가할 교회를 선택하세요.', 'warning');
			return;
		}

		// 확인 후 저장
		saveMatchedChurches(selectedChurches);
	}



	/**
	 * 선택된 교회 저장
	 */
	function saveMatchedChurches(churches) {
		const btn = $('#confirmMatchChurchBtn');
		const originalHtml = btn.html();

		btn.prop('disabled', true).html('<i class="bi bi-hourglass-split"></i> 추가 중...');

		$.ajax({
			url: '/member/save_matched_churches',
			method: 'POST',
			data: {
				member_idx: currentMemberMissionIdx,
				org_id: selectedOrgId,
				churches: JSON.stringify(churches)
			},
			dataType: 'json',
			success: function(response) {
				if (response.success) {
					showToast(response.message, 'success');

					// 모달 닫기
					const modal = bootstrap.Modal.getInstance(document.getElementById('autoMatchChurchModal'));
					if (modal) {
						modal.hide();
					}

					// 파송교회 목록 새로고침
					loadTransferOrgList(currentMemberMissionIdx);
				} else {
					showToast(response.message || '교회 추가에 실패했습니다.', 'error');
				}
			},
			error: function() {
				showToast('교회 추가 중 오류가 발생했습니다.', 'error');
			},
			complete: function() {
				btn.prop('disabled', false).html(originalHtml);
			}
		});
	}



	/**
	 * 교회 태그 Select2 초기화 (Tagging 및 해시태그 형식 처리)
	 */
	function initChurchTagsSelect2() {
		// 기존 Select2가 있다면 제거
		if ($('#org_tags').data('select2')) {
			$('#org_tags').select2('destroy');
		}

		// input 타입을 select로 변경
		const currentValue = $('#org_tags').val();
		$('#org_tags').replaceWith('<select class="form-select" id="org_tags" name="org_tags" multiple></select>');

		$('#org_tags').select2({
			width: '100%',
			tags: true,
			tokenSeparators: [' ', ','],
			placeholder: '태그를 입력하세요 (예: #이웃 #사랑)',
			allowClear: true,
			multiple: true,
			dropdownParent: $('#transferOrgModal'),
			createTag: function(params) {
				const term = $.trim(params.term);

				if (term === '') {
					return null;
				}

				// 입력한 그대로 태그로 사용 (해시태그 자동 추가 제거)
				return {
					id: term,
					text: term,
					newTag: true
				};
			},
			templateResult: function(data) {
				if (data.loading) {
					return '검색 중...';
				}

				// 해시태그 형식으로 표시
				const $result = $('<span></span>');
				$result.text(data.text);

				if (data.newTag) {
					$result.addClass('text-primary');
				}

				return $result;
			},
			templateSelection: function(data) {
				// 선택된 태그 표시
				return data.text;
			},
			language: {
				noResults: function() {
					return '태그를 입력하고 Enter를 누르세요';
				},
				searching: function() {
					return '검색 중...';
				}
			}
		});

		// 정렬 가능하게 설정
		$('#org_tags').select2Sortable();
	}


	/**
	 * 파송교회 저장
	 */
	function saveTransferOrg() {
		const form = $('#transferOrgForm');
		const transferOrgIdx = $('#transfer_org_idx').val();
		const memberIdx = $('#transfer_member_idx').val();

		// 필수 입력 체크
		if (!$('#transfer_name').val() || !$('#transfer_region').val()) {
			showToast('지역과 교회명은 필수 입력 항목입니다.', 'error');
			return;
		}

		// 태그 데이터에서 # 기호 제거 후 쉼표로 구분된 문자열로 변환
		const selectedTags = $('#org_tags').val() || [];
		const cleanTags = selectedTags.map(tag => tag.replace(/^#/, ''));
		const tagsString = cleanTags.join(','); // "병장,상병,일병"

		const formData = {
			member_idx: memberIdx,
			org_id: selectedOrgId,
			transfer_region: $('#transfer_region').val(),
			transfer_name: $('#transfer_name').val(),
			pastor_name: $('#pastor_name').val(),
			contact_person: $('#contact_person').val(),
			contact_phone: $('#contact_phone').val(),
			contact_email: $('#contact_email').val(),
			transfer_description: $('#transfer_description').val(),
			org_tag: tagsString
		};

		let url = '/member/save_transfer_org';
		if (transferOrgIdx) {
			url = '/member/update_transfer_org';
			formData.idx = transferOrgIdx;
		}

		$.ajax({
			url: url,
			method: 'POST',
			data: formData,
			dataType: 'json',
			success: function(response) {
				if (response.success) {
					showToast(response.message, 'success');
					$('#transferOrgModal').modal('hide');
					loadTransferOrgList(memberIdx);
				} else {
					showToast(response.message, 'error');
				}
			},
			error: function() {
				showToast('파송교회 저장에 실패했습니다.', 'error');
			}
		});
	}




	/**
	 * 결연교회 자동매칭 모달 열기 (복원)
	 */
	function autoMatchChurch() {
		if (!currentMemberMissionIdx) {
			showToast('회원 정보를 찾을 수 없습니다.', 'error');
			return;
		}

		if (!selectedOrgId) {
			showToast('조직 정보를 찾을 수 없습니다.', 'error');
			return;
		}

		// 모달 초기화
		$('#matchSearchKeyword').val('');
		$('#matchSearchType').val('church_name');
		$('#matchChurchListBody').html(`
        <tr>
            <td colspan="5" class="text-center text-muted py-4">
                검색 버튼을 클릭하거나 동일지역 자동매칭을 실행하세요.
            </td>
        </tr>
    `);
		$('#matchSelectionInfo').hide();
		$('#confirmMatchChurchBtn').prop('disabled', true);
		$('#selectAllMatchChurch').prop('checked', false);

		// 모달 표시
		const modal = new bootstrap.Modal(document.getElementById('autoMatchChurchModal'));
		modal.show();
	}



// 우클릭 메뉴에 '결연교회 추천' 항목 추가
// 기존 context menu 설정 부분에 추가
	function addOfferMenuItem() {
		// 기존 contextMenu 설정에 아래 항목 추가
		return {
			name: '결연교회 추천',
			icon: 'bi-chat-dots',
			callback: function(rowIndx, rowData) {
				sendOfferLinkToMember(rowData.member_idx);
			}
		};
	}


	/**
	 * 회원에게 결연교회 추천 링크 전송
	 */
	function sendOfferLinkToMember(memberIdx) {
		if (!memberIdx) {
			showToast('회원 정보를 찾을 수 없습니다.', 'error');
			return;
		}

		$.ajax({
			url: '/member/send_offer_link',
			method: 'POST',
			data: {
				member_idx: memberIdx,
				org_id: selectedOrgId
			},
			dataType: 'json',
			success: function(response) {
				if (response.success) {
					currentOfferMemberIdx = memberIdx;
					currentOfferUrl = response.offer_url;

					$('#offerUrlInput').val(response.offer_url);

					const modal = new bootstrap.Modal(document.getElementById('sendOfferModal'));
					modal.show();
				} else {
					showToast(response.message || '링크 생성에 실패했습니다.', 'error');
				}
			},
			error: function() {
				showToast('링크 생성 중 오류가 발생했습니다.', 'error');
			}
		});
	}

	/**
	 * 패스코드 갱신 버튼 이벤트
	 */
	$(document).on('click', '#copyPassResetBtn', function() {
		if (!currentOfferMemberIdx) {
			showToast('회원 정보를 찾을 수 없습니다.', 'error');
			return;
		}

		// 버튼 비활성화 및 로딩 표시
		const $btn = $(this);
		const originalHtml = $btn.html();
		$btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');

		$.ajax({
			url: '/member/refresh_member_passcode',
			method: 'POST',
			data: {
				member_idx: currentOfferMemberIdx,
				org_id: selectedOrgId
			},
			dataType: 'json',
			success: function(response) {
				if (response.success) {
					currentOfferUrl = response.offer_url;
					$('#offerUrlInput').val(response.offer_url);
					showToast(response.message, 'success');
				} else {
					showToast(response.message || '패스코드 갱신에 실패했습니다.', 'error');
				}
			},
			error: function() {
				showToast('패스코드 갱신 중 오류가 발생했습니다.', 'error');
			},
			complete: function() {
				// 버튼 복원
				$btn.prop('disabled', false).html(originalHtml);
			}
		});
	});


	/**
	 * URL 복사 버튼 이벤트
	 */
	$(document).on('click', '#copyOfferUrlBtn', function() {
		const urlInput = $('#offerUrlInput');
		urlInput.select();

		try {
			document.execCommand('copy');
			showToast('URL이 클립보드에 복사되었습니다.', 'success');
		} catch (err) {
			// Fallback for older browsers
			navigator.clipboard.writeText(urlInput.val()).then(function() {
				showToast('URL이 클립보드에 복사되었습니다.', 'success');
			}).catch(function() {
				showToast('URL 복사에 실패했습니다.', 'error');
			});
		}
	});

	/**
	 * 문자 발송 버튼 클릭
	 */
	$(document).on('click', '#sendOfferSmsBtn', function() {
		if (!currentOfferUrl) {
			showToast('전송할 URL이 없습니다.', 'error');
			return;
		}

		// 실제 문자 발송 기능은 SMS 서비스에 따라 구현
		showToast('문자 발송 기능은 SMS 서비스 연동 후 사용 가능합니다.', 'info');

		// 모달 닫기
		const modal = bootstrap.Modal.getInstance(document.getElementById('sendOfferModal'));
		if (modal) {
			modal.hide();
		}
	});




	/**
	 * 파송 교회 목록 로드 시 선택된 교회 표시
	 */
	function loadTransferOrgsWithSelection(memberIdx, orgId) {
		$.ajax({
			url: '/member/get_member_transfer_orgs',
			method: 'POST',
			data: {
				member_idx: memberIdx,
				org_id: orgId
			},
			dataType: 'json',
			success: function(response) {
				if (response.success) {
					const transferList = $('#transferOrgList');
					transferList.empty();

					if (response.data && response.data.length > 0) {
						response.data.forEach(function(org) {
							const isSelected = org.is_selected === 'selected' || org.status === 'selected';
							const badge = isSelected ? '<span class="badge bg-success ms-2">선택됨</span>' : '';

							const orgHtml = `
                            <div class="list-group-item ${isSelected ? 'border-success' : ''}">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1">
                                            ${org.transfer_org_name || '-'}
                                            ${badge}
                                        </h6>
                                        <p class="mb-1 small text-muted">
                                            <i class="bi bi-geo-alt"></i> ${org.transfer_org_address || '-'}
                                        </p>
                                        <p class="mb-0 small text-muted">
                                            <i class="bi bi-person"></i> ${org.transfer_org_rep || '-'}
                                        </p>
                                    </div>
                                    <div>
                                        <button type="button" class="btn btn-sm btn-outline-primary edit-transfer-btn"
                                                data-transfer-id="${org.idx}">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-danger delete-transfer-btn"
                                                data-transfer-id="${org.idx}">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        `;
							transferList.append(orgHtml);
						});
					} else {
						transferList.html(`
                        <div class="text-center text-muted py-4">
                            <i class="bi bi-inbox"></i>
                            <p class="mb-0">등록된 파송교회가 없습니다.</p>
                        </div>
                    `);
					}
				} else {
					showToast(response.message || '파송교회 목록을 불러오는데 실패했습니다.', 'error');
				}
			},
			error: function() {
				showToast('파송교회 목록을 불러오는 중 오류가 발생했습니다.', 'error');
			}
		});
	}


	/**
	 * 결연교회 추천 문자발송 버튼 이벤트
	 */
	$(document).on('click', '#sendOfferSmsBtn', function() {
		if (!currentOfferMemberIdx || !currentOfferUrl) {
			showToast('회원 정보 또는 URL을 찾을 수 없습니다.', 'error');
			return;
		}

		// 현재 회원 정보 조회
		$.ajax({
			url: '/member/get_member_info',
			method: 'POST',
			data: {
				member_idx: currentOfferMemberIdx,
				org_id: selectedOrgId
			},
			dataType: 'json',
			success: function(response) {
				if (response.success && response.member) {
					const member = response.member;

					// 전화번호 확인
					if (!member.member_phone || member.member_phone.trim() === '') {
						showToast('회원의 전화번호가 없어 문자를 발송할 수 없습니다.', 'warning');
						return;
					}

					// 메시지 내용
					const message = `결연교회 추천\n${currentOfferUrl}`;

					// localStorage에 데이터 저장
					const smsData = {
						memberIds: [member.member_idx],
						message: message,
						timestamp: new Date().getTime()
					};

					localStorage.setItem('pendingOfferSmsData', JSON.stringify(smsData));

					// 팝업으로 member_ids 전송
					openOfferSendPopup([member.member_idx]);
				} else {
					showToast('회원 정보를 불러오는데 실패했습니다.', 'error');
				}
			},
			error: function() {
				showToast('회원 정보 조회 중 오류가 발생했습니다.', 'error');
			}
		});
	});


	/**
	 * 결연교회 추천 문자발송 팝업 열기
	 */
	function openOfferSendPopup(memberIds) {
		const popupWidth = 1400;
		const popupHeight = 900;
		const left = (screen.width - popupWidth) / 2;
		const top = (screen.height - popupHeight) / 2;

		// 임시 폼 생성
		const form = document.createElement('form');
		form.method = 'POST';
		form.action = '/send/popup';
		form.target = 'sendPopup';
		form.style.display = 'none';

		// member_ids를 JSON 문자열로 전송
		const input = document.createElement('input');
		input.type = 'hidden';
		input.name = 'member_ids';
		input.value = JSON.stringify(memberIds);

		form.appendChild(input);
		document.body.appendChild(form);

		// 팝업 창 열기
		const popup = window.open('', 'sendPopup', `width=${popupWidth},height=${popupHeight},left=${left},top=${top},scrollbars=yes,resizable=yes`);

		if (!popup) {
			showToast('팝업이 차단되었습니다. 팝업 차단을 해제해주세요.', 'error');
			localStorage.removeItem('pendingOfferSmsData');
			document.body.removeChild(form);
			return;
		}

		form.submit();
		document.body.removeChild(form);

		// 모달 닫기
		$('#sendOfferModal').modal('hide');
	}


	/**
	 * 파일 위치: assets/js/member.js
	 * 역할: 메모 항목별 배지 색상 반환
	 */
	function getMemoTypeBadgeClass(memoType) {
		const badgeClasses = [
			'bg-primary',      // 파란색
			'bg-success',      // 초록색
			'bg-info',         // 하늘색
			'bg-warning',      // 노란색
			'bg-danger',       // 빨간색
			'bg-secondary',    // 회색
			'bg-dark'          // 검정색
		];

		// memoTypes 배열이 로드되어 있는 경우
		if (memoTypes && memoTypes.length > 0) {
			const typeIndex = memoTypes.indexOf(memoType);
			if (typeIndex >= 0) {
				return badgeClasses[typeIndex % badgeClasses.length];
			}
		}

		// memoTypes 배열이 없는 경우, 문자열 해시로 색상 결정
		let hash = 0;
		for (let i = 0; i < memoType.length; i++) {
			hash = memoType.charCodeAt(i) + ((hash << 5) - hash);
		}
		const index = Math.abs(hash) % badgeClasses.length;
		return badgeClasses[index];
	}


});




