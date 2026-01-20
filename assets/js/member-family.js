/**
 * 파일 위치: assets/js/member-family.js
 * 역할: 회원 가족 가계도 관리 기능
 *
 * JSON 구조 (간소화):
 * [
 *   {
 *     "id": "0",
 *     "member_idx": 123,
 *     "rels": { "spouses": [], "children": [], "parents": [] }
 *   }
 * ]
 *
 * 이름, 생년월일, avatar, gender 등은 member_idx를 통해 조회하여 표시
 */

(function() {
	'use strict';

	// 전역 변수
	let familyChart = null;
	let currentFamilyData = [];         // DB에 저장되는 간소화된 데이터
	let currentFamilyDisplayData = [];  // 화면 표시용 데이터 (회원정보 포함)
	let currentFamilyMemberIdx = null;
	let currentZoomLevel = 1;
	let areaOptions = [];               // 소그룹 옵션

	const ZOOM_STEP = 0.2;
	const ZOOM_MIN = 0.3;
	const ZOOM_MAX = 2;

	/**
	 * 가족 탭 이벤트 바인딩
	 */
	function bindFamilyTabEvents() {
		// 가족 탭 클릭 시 가계도 로드
		$('#family-tab').off('shown.bs.tab').on('shown.bs.tab', function() {
			const memberIdx = $('#member_idx').val();
			if (memberIdx) {
				currentFamilyMemberIdx = memberIdx;
				loadFamilyData(memberIdx);
			}
		});

		// 가족 추가 버튼
		$(document).off('click', '#addFamilyMemberBtn').on('click', '#addFamilyMemberBtn', function() {
			if (!currentFamilyMemberIdx) {
				showToast('회원 정보를 먼저 저장해주세요.', 'warning');
				return;
			}
			openAddFamilyModal();
		});

		// 기존회원 연결 버튼
		$(document).off('click', '#linkExistingMemberBtn').on('click', '#linkExistingMemberBtn', function() {
			if (!currentFamilyMemberIdx) {
				showToast('회원 정보를 먼저 저장해주세요.', 'warning');
				return;
			}
			openLinkMemberModal();
		});


		// 가족 삭제
		$(document).off('click', '.btn-family-delete').on('click', '.btn-family-delete', function() {
			const familyId = $(this).data('id');
			const familyName = $(this).closest('.list-group-item').find('.family-name').text();
			confirmDeleteFamily(familyId, familyName);
		});

		// 연결된 회원 보기 버튼 클릭
		$(document).off('click', '.btn-family-view').on('click', '.btn-family-view', function() {
			const memberIdx = $(this).data('member-idx');
			if (!memberIdx) {
				showToast('회원 정보를 찾을 수 없습니다.', 'error');
				return;
			}

			// 현재 offcanvas 닫기
			const currentOffcanvas = bootstrap.Offcanvas.getInstance($('#memberOffcanvas')[0]);
			if (currentOffcanvas) {
				// offcanvas가 완전히 닫힌 후 새 회원 정보 열기
				$('#memberOffcanvas').one('hidden.bs.offcanvas', function() {
					openFamilyMemberOffcanvas(memberIdx);
				});
				currentOffcanvas.hide();
			} else {
				openFamilyMemberOffcanvas(memberIdx);
			}
		});
	}

	/**
	 * 가족 회원 정보 열기
	 */
	function openFamilyMemberOffcanvas(memberIdx) {
		// member.js의 전역 함수 사용
		if (typeof window.loadMemberDetail === 'function') {
			// offcanvas 열기
			const offcanvasEl = document.getElementById('memberOffcanvas');
			const offcanvas = new bootstrap.Offcanvas(offcanvasEl);
			offcanvas.show();

			// 회원 정보 로드
			window.loadMemberDetail(memberIdx);
		} else if (typeof window.openMemberOffcanvas === 'function') {
			window.openMemberOffcanvas('edit', memberIdx);

			// openMemberOffcanvas가 데이터를 로드하지 않는다면 별도로 로드
			setTimeout(function() {
				if (typeof window.loadMemberDetail === 'function') {
					window.loadMemberDetail(memberIdx);
				}
			}, 100);
		} else {
			// 직접 로드
			loadMemberDataDirect(memberIdx);
		}
	}

	/**
	 * 회원 데이터 직접 로드
	 */
	function loadMemberDataDirect(memberIdx) {
		const offcanvasEl = document.getElementById('memberOffcanvas');
		const offcanvas = new bootstrap.Offcanvas(offcanvasEl);

		$('#memberForm')[0].reset();
		$('#member_idx').val(memberIdx);
		$('#memberOffcanvasLabel').text('회원 정보 수정');

		$.ajax({
			url: '/member/get_member_detail',
			type: 'POST',
			data: { member_idx: memberIdx },
			dataType: 'json',
			success: function(response) {
				if (response.success && response.data) {
					const member = response.data;

					// 폼에 데이터 채우기
					Object.keys(member).forEach(function(key) {
						const $field = $('#' + key);
						if ($field.length > 0) {
							$field.val(member[key] || '');
						}
					});

					// 프로필 사진
					if (member.photo) {
						let photoUrl = member.photo;
						if (photoUrl.indexOf('/') !== 0 && photoUrl.indexOf('http') !== 0) {
							photoUrl = '/uploads/member_photos/' + member.org_id + '/' + photoUrl;
						}
						$('#previewImage').attr('src', photoUrl);
					} else {
						$('#previewImage').attr('src', '/assets/images/photo_no.png');
					}

					// 기본정보 탭으로
					const basicTab = document.querySelector('#basic-tab');
					if (basicTab) {
						new bootstrap.Tab(basicTab).show();
					}

					offcanvas.show();
				} else {
					showToast('회원 정보를 불러올 수 없습니다.', 'error');
				}
			},
			error: function() {
				showToast('회원 정보 조회 중 오류가 발생했습니다.', 'error');
			}
		});
	}



	/**
	 * 가족 데이터 로드
	 */
	function loadFamilyData(memberIdx) {
		$('#familyChartLoading').show();
		$('#familyList').empty();

		$.ajax({
			url: '/member/get_family_data',
			type: 'POST',
			data: { member_idx: memberIdx },
			dataType: 'json',
			success: function(response) {
				if (response.success && response.display_data && response.display_data.length > 0) {
					currentFamilyData = response.data || [];
					currentFamilyDisplayData = response.display_data || [];

					renderFamilyChart(currentFamilyDisplayData);
					renderFamilyList(currentFamilyDisplayData);
				} else {
					// 데이터가 없는 경우 빈 상태 표시
					$('#familyChartLoading').html('<div class="text-center text-muted"><i class="bi bi-diagram-3 fs-1 mb-2 d-block"></i><span>가족 정보가 없습니다</span></div>').show();
					$('#familyList').html('<div class="text-center text-muted py-3">가족 정보가 없습니다.</div>');
				}
			},
			error: function() {
				showToast('가족 정보를 불러오는데 실패했습니다.', 'error');
				$('#familyChartLoading').html('<div class="text-center text-muted"><i class="bi bi-diagram-3 fs-1 mb-2 d-block"></i><span>가족 정보를 불러올 수 없습니다</span></div>').show();
			}
		});
	}


	/**
	 * 가계도 차트 렌더링
	 */
	function renderFamilyChart(data) {
		$('#familyChartLoading').hide();
		currentZoomLevel = 1;

		if (!data || data.length === 0) {
			$('#familyChartLoading').html('<div class="text-center text-muted"><i class="bi bi-diagram-3 fs-1 mb-2 d-block"></i><span>가족 정보가 없습니다</span></div>').show();
			return;
		}

		data.forEach(function(member) {
			if (member.data) {
				if (!member.data.avatar || member.data.avatar.trim() === '') {
					member.data.avatar = '/assets/images/photo_no.png';
				}
			}
		});

		$('#familyChartContainer').html('<div id="FamilyChart" style="width:100%;height:100%;"></div>');

		try {
			if (typeof f3 !== 'undefined') {
				familyChart = f3.createChart('#FamilyChart', data)
					.setTransitionTime(500)
					.setCardXSpacing(220)
					.setCardYSpacing(120)
					.setSingleParentEmptyCard(false);  // ADD 카드 생성 안함

				familyChart.setCardHtml()
					.setCardDisplay([['first name', 'last name'], ['birthday']]);

				familyChart.updateTree({initial: true});

			} else {
				setTimeout(function() {
					renderFamilyChart(data);
				}, 500);
			}
		} catch (e) {
			console.error('가계도 렌더링 오류:', e);
			$('#familyChartLoading').html('<div class="text-center text-danger"><i class="bi bi-exclamation-triangle fs-1 mb-2 d-block"></i><span>가계도 표시 오류</span></div>').show();
		}
	}

	/**
	 * 가족 목록 렌더링
	 */
	/**
	 * 가족 목록 렌더링
	 */
	function renderFamilyList(data) {
		const container = $('#familyList');
		container.empty();

		if (!data || data.length === 0) {
			container.html('<div class="text-center text-muted py-3">가족 정보가 없습니다.</div>');
			return;
		}

		data.forEach(function(member) {
			const isMe = member.id === '0';
			const name = ((member.data && member.data['first name']) || '') + ' ' + ((member.data && member.data['last name']) || '');
			const gender = (member.data && member.data.gender === 'F') ? '여' : '남';
			const birthday = (member.data && member.data.birthday) || '';
			const relation = getRelationLabel(member, data);
			const linkedMemberIdx = member.member_idx || null;

			// 이미지 경로 처리
			let avatar = '/assets/images/photo_no.png';
			if (member.data && member.data.avatar && member.data.avatar !== '') {
				avatar = member.data.avatar;
			}

			const html = `
            <div class="list-group-item d-flex justify-content-between align-items-center py-2">
                <div class="d-flex align-items-center">
                    <img src="${avatar}" alt="사진" class="me-2" style="width:40px; height:40px; border-radius:50%; object-fit:cover;" onerror="this.src='/assets/images/photo_no.png'">
                    <div>
                        <span class="family-name fw-bold">${name.trim() || '이름없음'}</span>
                        <span class="badge bg-secondary ms-1">${gender}</span>
                        ${isMe ? '<span class="badge bg-primary ms-1">본인</span>' : ''}
                        <div class="small text-muted">
                            ${relation ? '<span class="me-2">' + relation + '</span>' : ''}
                            ${birthday ? '<span>' + birthday + '</span>' : ''}
                        </div>
                    </div>
                </div>
                <div class="btn-group btn-group-sm">
                    ${!isMe && linkedMemberIdx ? `
                        <button type="button" class="btn btn-outline-info btn-family-view" data-member-idx="${linkedMemberIdx}" title="회원정보 보기">
                            <i class="bi bi-zoom-in"></i>
                        </button>
                    ` : ''}
                    ${!isMe ? `
                        <button type="button" class="btn btn-outline-danger btn-family-delete" data-id="${member.id}" data-member-idx="${linkedMemberIdx}" title="관계 삭제">
                            <i class="bi bi-person-dash-fill"></i>
                        </button>
                    ` : ''}
                </div>
            </div>
        `;

			container.append(html);
		});
	}

	/**
	 * 관계 라벨 가져오기
	 */
	function getRelationLabel(member, allData) {
		if (member.id === '0') return '';

		const me = allData.find(m => m.id === '0');
		if (!me || !me.rels) return '';

		if (me.rels.spouses && me.rels.spouses.includes(member.id)) return '배우자';
		if (me.rels.children && me.rels.children.includes(member.id)) return '자녀';
		if (me.rels.parents && me.rels.parents.includes(member.id)) return '부모';

		// 손자녀
		if (me.rels.children) {
			for (const childId of me.rels.children) {
				const child = allData.find(m => m.id === childId);
				if (child && child.rels && child.rels.children && child.rels.children.includes(member.id)) {
					return '손자녀';
				}
			}
		}

		// 조부모
		if (me.rels.parents) {
			for (const parentId of me.rels.parents) {
				const parent = allData.find(m => m.id === parentId);
				if (parent && parent.rels && parent.rels.parents && parent.rels.parents.includes(member.id)) {
					return '조부모';
				}
			}
		}

		// 형제
		if (me.rels.parents && member.rels && member.rels.parents) {
			const commonParent = me.rels.parents.find(p => member.rels.parents.includes(p));
			if (commonParent) return '형제';
		}

		return '가족';
	}

	/**
	 * 가족 추가 모달 열기
	 */
	function openAddFamilyModal() {
		if ($('#addFamilyModal').length === 0) {
			createAddFamilyModal();
		}

		// 관계 대상 선택 옵션 설정
		const targetSelect = $('#familyTargetId');
		targetSelect.empty();

		currentFamilyDisplayData.forEach(function(member) {
			const name = ((member.data && member.data['first name']) || '') + ' ' + ((member.data && member.data['last name']) || '');
			const isMe = member.id === '0';
			targetSelect.append(`<option value="${member.id}">${name.trim() || '이름없음'}${isMe ? ' (본인)' : ''}</option>`);
		});

		// 소그룹 옵션 로드
		loadAreaOptionsForFamily();

		// 폼 초기화
		$('#addFamilyForm')[0].reset();
		$('#familyTargetId').val('0');

		const modal = new bootstrap.Modal(document.getElementById('addFamilyModal'));
		modal.show();
	}

	/**
	 * 가족 추가 모달 생성
	 */
	function createAddFamilyModal() {
		const modalHtml = `
        <div class="modal fade" id="addFamilyModal" tabindex="-1" aria-labelledby="addFamilyModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addFamilyModalLabel">가족 추가</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="addFamilyForm">
                            <div class="mb-3">
                                <label for="familyTargetId" class="form-label">기준 가족 <span class="text-danger">*</span></label>
                                <select class="form-select" id="familyTargetId" required>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="familyRelationType" class="form-label">관계 <span class="text-danger">*</span></label>
                                <select class="form-select" id="familyRelationType" required>
                                    <option value="">관계 선택</option>
                                    <option value="spouse">배우자</option>
                                    <option value="child">자녀</option>
                                    <option value="parent">부모</option>
                                </select>
                            </div>
                            <hr>
                            <div class="mb-3">
                                <label for="familyAreaIdx" class="form-label">소그룹</label>
                                <select class="form-select" id="familyAreaIdx">
                                    <option value="">소그룹 선택</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="familyMemberName" class="form-label">이름 <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="familyMemberName" required>
                            </div>
                            <div class="mb-3">
                                <label for="familyMemberGender" class="form-label">성별</label>
                                <select class="form-select" id="familyMemberGender">
                                    <option value="male">남</option>
                                    <option value="female">여</option>
                                </select>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                        <button type="button" class="btn btn-primary" id="saveFamilyMemberBtn">저장</button>
                    </div>
                </div>
            </div>
        </div>
        `;

		$('body').append(modalHtml);

		// 저장 버튼 이벤트
		$(document).off('click', '#saveFamilyMemberBtn').on('click', '#saveFamilyMemberBtn', function() {
			saveFamilyMember();
		});
	}

	/**
	 * 소그룹 옵션 로드
	 */
	function loadAreaOptionsForFamily() {
		const areaSelect = $('#familyAreaIdx');
		areaSelect.html('<option value="">소그룹 선택</option>');

		try {
			// 기존 그룹 트리에서 옵션 가져오기
			const tree = $("#groupTree").fancytree("getTree");
			const orgId = $('#org_id').val() || selectedOrgId;
			const groupNode = tree.getNodeByKey('org_' + orgId);

			if (groupNode && groupNode.children) {
				function addOptions(nodes, depth) {
					nodes.forEach(function(node) {
						const areaData = node.data;
						if (areaData.type === 'area') {
							const indent = '\u3000'.repeat(depth);
							const optionText = indent + node.title.replace(/\s*\(\d+명\)$/, '');
							areaSelect.append(`<option value="${areaData.area_idx}">${optionText}</option>`);

							if (node.children && node.children.length > 0) {
								addOptions(node.children, depth + 1);
							}
						}
					});
				}
				addOptions(groupNode.children, 0);
			}
		} catch (e) {
			console.error('소그룹 옵션 로드 오류:', e);
		}
	}

	/**
	 * 가족 구성원 저장 (새 회원 생성)
	 */
	function saveFamilyMember() {
		const targetId = $('#familyTargetId').val();
		const relationType = $('#familyRelationType').val();
		const areaIdx = $('#familyAreaIdx').val();
		const name = $('#familyMemberName').val().trim();
		const gender = $('#familyMemberGender').val();

		if (!relationType) {
			showToast('관계를 선택해주세요.', 'warning');
			return;
		}

		if (!name) {
			showToast('이름을 입력해주세요.', 'warning');
			return;
		}

		const orgId = $('#org_id').val() || selectedOrgId;

		$.ajax({
			url: '/member/add_family_member_new',
			type: 'POST',
			data: {
				member_idx: currentFamilyMemberIdx,
				org_id: orgId,
				relation_type: relationType,
				target_id: targetId,
				area_idx: areaIdx,
				member_name: name,
				member_sex: gender
			},
			dataType: 'json',
			success: function(response) {
				if (response.success) {
					bootstrap.Modal.getInstance(document.getElementById('addFamilyModal')).hide();
					currentFamilyData = response.data;
					currentFamilyDisplayData = response.display_data;
					renderFamilyChart(response.display_data);
					renderFamilyList(response.display_data);
					showToast(response.message, 'success');

					// 그룹 트리 새로고침
					if (typeof refreshGroupTree === 'function') {
						refreshGroupTree();
					}
				} else {
					showToast(response.message || '저장에 실패했습니다.', 'error');
				}
			},
			error: function() {
				showToast('저장 중 오류가 발생했습니다.', 'error');
			}
		});
	}

	/**
	 * 기존회원 연결 모달 열기
	 */
	function openLinkMemberModal() {
		if ($('#linkMemberModal').length === 0) {
			createLinkMemberModal();
		}

		// 관계 대상 선택 옵션 설정
		const targetSelect = $('#linkTargetId');
		targetSelect.empty();

		currentFamilyDisplayData.forEach(function(member) {
			const name = ((member.data && member.data['first name']) || '') + ' ' + ((member.data && member.data['last name']) || '');
			const isMe = member.id === '0';
			targetSelect.append(`<option value="${member.id}">${name.trim() || '이름없음'}${isMe ? ' (본인)' : ''}</option>`);
		});

		// 폼 초기화
		$('#linkMemberForm')[0].reset();
		$('#linkTargetId').val('0');
		$('#linkMemberSelect').val(null).trigger('change');
		$('#selectedLinkMember').hide();
		$('#selectedMemberIdx').val('');

		const modal = new bootstrap.Modal(document.getElementById('linkMemberModal'));
		modal.show();

		initLinkMemberSelect2();
	}

	/**
	 * 기존회원 연결 모달 생성
	 */
	function createLinkMemberModal() {
		const modalHtml = `
        <div class="modal fade" id="linkMemberModal" tabindex="-1" aria-labelledby="linkMemberModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="linkMemberModalLabel"><i class="bi bi-link-45deg"></i> 기존회원 연결</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="linkMemberForm">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="linkTargetId" class="form-label">기준 가족 <span class="text-danger">*</span></label>
                                        <select class="form-select" id="linkTargetId" required></select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="linkRelationType" class="form-label">관계 <span class="text-danger">*</span></label>
                                        <select class="form-select" id="linkRelationType" required>
                                            <option value="">관계 선택</option>
                                            <option value="spouse">배우자</option>
                                            <option value="child">자녀</option>
                                            <option value="parent">부모</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <hr>
                            <div class="mb-3">
                                <label for="linkMemberSelect" class="form-label">연결할 회원 검색 <span class="text-danger">*</span></label>
                                <select class="form-select" id="linkMemberSelect" style="width:100%">
                                    <option value="">회원 이름으로 검색...</option>
                                </select>
                            </div>
                            <div id="selectedLinkMember" class="card bg-light mb-3" style="display:none;">
                                <div class="card-body py-2">
                                    <div class="d-flex align-items-center">
                                        <div class="me-3">
                                            <img id="selectedMemberPhoto" src="" class="rounded-circle" style="width:50px;height:50px;object-fit:cover;" onerror="this.src='/assets/images/photo_no.png'">
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="fw-bold" id="selectedMemberName"></div>
                                            <div class="small text-muted">
                                                <span id="selectedMemberPhone"></span>
                                                <span class="ms-2" id="selectedMemberGroup"></span>
                                            </div>
                                        </div>
                                        <div>
                                            <button type="button" class="btn btn-sm btn-outline-secondary" id="clearSelectedMember">
                                                <i class="bi bi-x"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" id="selectedMemberIdx">
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                        <button type="button" class="btn btn-primary" id="saveLinkMemberBtn"><i class="bi bi-link-45deg"></i> 연결</button>
                    </div>
                </div>
            </div>
        </div>
        `;

		$('body').append(modalHtml);

		$(document).off('click', '#saveLinkMemberBtn').on('click', '#saveLinkMemberBtn', saveLinkMember);
		$(document).off('click', '#clearSelectedMember').on('click', '#clearSelectedMember', function() {
			$('#linkMemberSelect').val(null).trigger('change');
			$('#selectedLinkMember').hide();
			$('#selectedMemberIdx').val('');
		});
	}

	/**
	 * 회원 검색 Select2 초기화
	 */
	function initLinkMemberSelect2() {
		const orgId = $('#org_id').val() || selectedOrgId;

		$('#linkMemberSelect').select2({
			dropdownParent: $('#linkMemberModal'),
			placeholder: '회원 이름 또는 연락처로 검색...',
			allowClear: true,
			minimumInputLength: 1,
			ajax: {
				url: '/member/search_members_for_family',
				type: 'POST',
				dataType: 'json',
				delay: 300,
				data: function(params) {
					return {
						keyword: params.term,
						org_id: orgId,
						exclude_member_idx: currentFamilyMemberIdx
					};
				},
				processResults: function(response) {
					if (response.success) {
						return {
							results: response.data.map(function(member) {
								return {
									id: member.member_idx,
									text: member.member_name + (member.member_phone ? ' (' + member.member_phone + ')' : ''),
									member: member
								};
							})
						};
					}
					return { results: [] };
				},
				cache: true
			},
			templateResult: function(member) {
				if (member.loading) return member.text;
				const data = member.member;
				if (!data) return member.text;
				console.log(data.photo);
				const photo = data.photo || '/assets/images/photo_no.png';
				return $(`
                    <div class="d-flex align-items-center">
                        <img src="${photo}" class="rounded-circle me-2" style="width:32px;height:32px;object-fit:cover;" onerror="this.src='/assets/images/photo_no.png'">
                        <div>
                            <div>${data.member_name}</div>
                            <div class="small text-muted">${data.member_phone || ''} ${data.area_name ? '/ ' + data.area_name : ''}</div>
                        </div>
                    </div>
                `);
			}
		});

		$('#linkMemberSelect').off('select2:select').on('select2:select', function(e) {
			const member = e.params.data.member;
			if (member) {
				$('#selectedMemberIdx').val(member.member_idx);
				$('#selectedMemberPhoto').attr('src', member.photo || '/assets/images/photo_no.png');
				$('#selectedMemberName').text(member.member_name);
				$('#selectedMemberPhone').text(member.member_phone || '');
				$('#selectedMemberGroup').text(member.area_name || '');
				$('#selectedLinkMember').show();
			}
		});

		$('#linkMemberSelect').off('select2:clear').on('select2:clear', function() {
			$('#selectedLinkMember').hide();
			$('#selectedMemberIdx').val('');
		});
	}

	/**
	 * 기존회원 연결 저장
	 */
	function saveLinkMember() {
		const targetId = $('#linkTargetId').val();
		const relationType = $('#linkRelationType').val();
		const selectedMemberIdx = $('#selectedMemberIdx').val();

		if (!relationType) {
			showToast('관계를 선택해주세요.', 'warning');
			return;
		}

		if (!selectedMemberIdx) {
			showToast('연결할 회원을 선택해주세요.', 'warning');
			return;
		}

		// 이미 연결된 회원인지 확인
		const alreadyLinked = currentFamilyData.find(m => m.member_idx == selectedMemberIdx);
		if (alreadyLinked) {
			showToast('이미 가족으로 연결된 회원입니다.', 'warning');
			return;
		}

		$.ajax({
			url: '/member/link_existing_member',
			type: 'POST',
			data: {
				member_idx: currentFamilyMemberIdx,
				relation_type: relationType,
				target_id: targetId,
				link_member_idx: selectedMemberIdx
			},
			dataType: 'json',
			success: function(response) {
				if (response.success) {
					bootstrap.Modal.getInstance(document.getElementById('linkMemberModal')).hide();
					currentFamilyData = response.data;
					currentFamilyDisplayData = response.display_data;
					renderFamilyChart(response.display_data);
					renderFamilyList(response.display_data);
					showToast(response.message, 'success');
				} else {
					showToast(response.message || '연결에 실패했습니다.', 'error');
				}
			},
			error: function() {
				showToast('연결 중 오류가 발생했습니다.', 'error');
			}
		});
	}

	/**
	 * 가족 수정 모달 열기 (관계 수정)
	 */
	function openEditFamilyModal(familyId) {
		const member = currentFamilyDisplayData.find(m => m.id === familyId);
		if (!member) {
			showToast('가족 정보를 찾을 수 없습니다.', 'error');
			return;
		}

		// 본인은 수정 불가
		if (familyId === '0') {
			showToast('본인 정보는 기본정보 탭에서 수정해주세요.', 'info');
			return;
		}

		// 연결된 회원은 해당 회원 정보에서 수정하도록 안내
		if (member.member_idx) {
			showToast('연결된 회원의 정보는 해당 회원 정보에서 수정해주세요.', 'info');
			return;
		}
	}

	/**
	 * 가족 삭제 확인
	 */
	function confirmDeleteFamily(familyId, familyName) {
		if ($('#deleteFamilyModal').length === 0) {
			const modalHtml = `
            <div class="modal fade" id="deleteFamilyModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">가족 삭제</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p><span id="deleteFamilyName"></span> 님을 가족 목록에서 삭제하시겠습니까?</p>
                            <p class="text-muted small">가족 관계만 해제되며, 회원 정보는 유지됩니다.</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                            <button type="button" class="btn btn-danger" id="confirmDeleteFamilyBtn">삭제</button>
                        </div>
                    </div>
                </div>
            </div>
            `;
			$('body').append(modalHtml);
		}

		$('#deleteFamilyName').text(familyName);
		$('#confirmDeleteFamilyBtn').data('id', familyId);

		$(document).off('click', '#confirmDeleteFamilyBtn').on('click', '#confirmDeleteFamilyBtn', function() {
			deleteFamilyMember($(this).data('id'));
		});

		new bootstrap.Modal(document.getElementById('deleteFamilyModal')).show();
	}



	// 초기화
	$(document).ready(function() {
		bindFamilyTabEvents();
	});

	// 전역 접근용
	window.FamilyModule = {
		loadFamilyData: loadFamilyData,
		renderFamilyChart: renderFamilyChart,
		getCurrentFamilyData: function() { return currentFamilyData; },

	};



	/**
	 * 가족 구성원 삭제
	 */
	function deleteFamilyMember(familyId) {
		// familyId로 해당 회원의 member_idx 찾기
		const member = currentFamilyDisplayData.find(m => m.id === familyId);
		if (!member || !member.member_idx) {
			showToast('삭제할 가족 정보를 찾을 수 없습니다.', 'error');
			return;
		}

		$.ajax({
			url: '/member/delete_family_member',
			type: 'POST',
			data: {
				member_idx: currentFamilyMemberIdx,
				related_member_idx: member.member_idx  // 변경: family_member_id 대신 related_member_idx
			},
			dataType: 'json',
			success: function(response) {
				bootstrap.Modal.getInstance(document.getElementById('deleteFamilyModal')).hide();

				if (response.success) {
					currentFamilyData = response.data;
					currentFamilyDisplayData = response.display_data;
					renderFamilyChart(response.display_data);
					renderFamilyList(response.display_data);
					showToast(response.message, 'success');
				} else {
					showToast(response.message || '삭제에 실패했습니다.', 'error');
				}
			},
			error: function() {
				showToast('삭제 중 오류가 발생했습니다.', 'error');
			}
		});
	}
})();
