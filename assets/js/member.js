/**
 * 파일 위치: assets/js/member.js
 * 역할: 회원 관리 페이지 JavaScript - Fancytree와 ParamQuery 연동
 */

$(document).ready(function () {
	// 전역 변수
	let memberGrid;
	let selectedOrgId = null;
	let selectedAreaIdx = null;
	let selectedType = null;

	// 디버깅: 라이브러리 로드 상태 확인
	console.log('jQuery:', typeof $);
	console.log('Fancytree:', typeof $.fn.fancytree);
	console.log('ParamQuery pqGrid:', typeof $.fn.pqGrid);

	// 초기화 시도
	setTimeout(function () {
		initializePage();
	}, 800); // 800ms 지연 후 초기화

	/**
	 * 페이지 초기화
	 */
	function initializePage() {
		console.log('페이지 초기화 시작');

		// ParamQuery 체크
		if (typeof $.fn.pqGrid === 'undefined') {
			console.error('ParamQuery 라이브러리가 로드되지 않았습니다.');
			showToast('ParamQuery 라이브러리 로드 실패');
			return;
		}

		// Fancytree 체크
		if (typeof $.fn.fancytree === 'undefined') {
			console.error('Fancytree 라이브러리가 로드되지 않았습니다.');
			showToast('Fancytree 라이브러리 로드 실패');
			return;
		}

		try {
			initializeFancytree();
			initializeParamQuery();
			bindEvents();
			console.log('페이지 초기화 완료');
		} catch (error) {
			console.error('초기화 중 오류:', error);
			showToast('페이지 초기화 중 오류가 발생했습니다.');
		}
	}

	/**
	 * Fancytree 초기화
	 */
	function initializeFancytree() {
		console.log('Fancytree 초기화 시작');

		$.ajax({
			url: window.memberPageData.baseUrl + 'member/get_org_tree',
			method: 'POST',
			dataType: 'json',
			success: function (treeData) {
				console.log('트리 데이터:', treeData);

				if (!treeData || treeData.length === 0) {
					showToast('조직 데이터가 없습니다.');
					return;
				}

				// Fancytree 설정
				$("#groupTree").fancytree({
					source: treeData,
					activate: function (event, data) {
						const node = data.node;
						const nodeData = node.data;

						console.log('선택된 노드:', nodeData);

						selectedType = nodeData.type;
						selectedOrgId = nodeData.org_id;
						selectedAreaIdx = nodeData.area_idx || null;

						// localStorage에 선택된 소그룹 저장
						saveSelectedGroupToStorage(nodeData);

						// 선택된 노드에 따라 제목 업데이트
						updateSelectedOrgName(node.title, nodeData.type);

						// 회원 데이터 로드
						loadMemberData();
					},
					autoScroll: true,
					keyboard: true,
					focusOnSelect: true
				});

				// 트리가 로드된 후 저장된 선택 상태 복원
				restoreSelectedGroupFromStorage(treeData);

				console.log('Fancytree 초기화 완료');
			},
			error: function (xhr, status, error) {
				console.error('그룹 트리 로드 실패:', error);
				console.error('Response:', xhr.responseText);
				showToast('그룹 정보를 불러오는데 실패했습니다.');
			}
		});
	}

	/**
	 * 선택된 그룹을 localStorage에 저장
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
			console.log('선택된 그룹 저장됨:', selectedGroup);
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

			const tree = $("#groupTree").fancytree("getTree");
			let nodeToSelect = null;

			if (groupData.type === 'unassigned' && groupData.org_id) {
				nodeToSelect = tree.getNodeByKey('unassigned_' + groupData.org_id);
			} else if (groupData.type === 'area' && groupData.area_idx) {
				nodeToSelect = tree.getNodeByKey('area_' + groupData.area_idx);
			}

			if (!nodeToSelect && groupData.org_id) {
				nodeToSelect = tree.getNodeByKey('org_' + groupData.org_id);
			}

			if (nodeToSelect) {
				nodeToSelect.setActive(true);
				nodeToSelect.setFocus(true);

				if (groupData.type !== 'unassigned' && nodeToSelect.parent && nodeToSelect.parent.isRootNode() === false) {
					nodeToSelect.parent.setExpanded(true);
				}

				console.log('저장된 그룹 선택 복원됨:', groupData);
			} else {
				console.log('저장된 그룹을 찾을 수 없음, 첫 번째 조직 선택');
				selectFirstOrganization();
			}

		} catch (error) {
			console.error('localStorage 복원 실패:', error);
			selectFirstOrganization();
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
	 * ParamQuery Grid 초기화 (이벤트 분리된 버전)
	 */
	function initializeParamQuery() {
		console.log('ParamQuery 초기화 시작');

		const colModel = [
			{
				title: '<input type="checkbox" id="selectAllCheckbox" />',
				dataIndx: "pq_selected",
				width: 50,
				align: "center",
				resizable: false,
				sortable: false,
				menuIcon: false,
				frozen: true,
				render: function (ui) {
					return '<input type="checkbox" class="member-checkbox" data-member-idx="' + ui.rowData.member_idx + '" />';
				}
			},
			{
				title: "소그룹",
				dataIndx: "area_name",
				width: 140,
				align: "center",
				frozen: true
			},
			{
				title: "사진",
				dataIndx: "photo",
				width: 70,
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
				align: "center",
				frozen: true
			},
			{
				title: "회원번호",
				dataIndx: "member_idx",
				width: 80,
				align: "center",
				hidden: true
			},
			{
				title: "휴대폰번호",
				dataIndx: "member_phone",
				width: 140,
				align: "center"
			},
			{
				title: "생년월일",
				dataIndx: "member_birth",
				width: 110,
				align: "center"
			},
			{
				title: "주소",
				dataIndx: "member_address",
				width: 250,
				align: "left"
			},
			{
				title: "등급",
				dataIndx: "grade",
				width: 80,
				align: "center"
			},
			{
				title: "리더",
				dataIndx: "leader_yn",
				width: 60,
				align: "center",
				render: function (ui) {
					return ui.cellData === 'Y' ? '<i class="bi bi-check-circle-fill text-success"></i>' : '';
				}
			},
			{
				title: "신규",
				dataIndx: "new_yn",
				width: 60,
				align: "center",
				render: function (ui) {
					return ui.cellData === 'Y' ? '<i class="bi bi-star-fill text-warning"></i>' : '';
				}
			},
			{
				title: "등록일",
				dataIndx: "regi_date",
				width: 120,  // 시분초 표시를 위해 너비 증가
				align: "center",
				render: function (ui) {
					if (ui.cellData) {
						const date = new Date(ui.cellData);
						// YYYY-MM-DD HH:MM:SS 형태로 포맷
						const year = date.getFullYear();
						const month = String(date.getMonth() + 1).padStart(2, '0');
						const day = String(date.getDate()).padStart(2, '0');
						const hours = String(date.getHours()).padStart(2, '0');
						const minutes = String(date.getMinutes()).padStart(2, '0');
						const seconds = String(date.getSeconds()).padStart(2, '0');

						return `${year}-${month}-${day}<br>${hours}:${minutes}:${seconds}`;
					}
					return '';
				}
			},
			{
				title: "수정일",
				dataIndx: "modi_date",
				width: 120,  // 시분초 표시를 위해 너비 증가
				align: "center",
				render: function (ui) {
					if (ui.cellData) {
						const date = new Date(ui.cellData);
						// YYYY-MM-DD HH:MM:SS 형태로 포맷
						const year = date.getFullYear();
						const month = String(date.getMonth() + 1).padStart(2, '0');
						const day = String(date.getDate()).padStart(2, '0');
						const hours = String(date.getHours()).padStart(2, '0');
						const minutes = String(date.getMinutes()).padStart(2, '0');
						const seconds = String(date.getSeconds()).padStart(2, '0');

						return `${year}-${month}-${day}<br>${hours}:${minutes}:${seconds}`;
					}
					return '';
				}
			},
			{
				title: "소그룹번호",
				dataIndx: "area_idx",
				width: 80,
				align: "center",
				hidden: true
			}
		];

		const gridOptions = {
			width: "100%",
			height: "100%",
			dataModel: {
				data: []
			},
			colModel: colModel,
			selectionModel: {
				type: 'row',
				mode: 'single'
			},
			scrollModel: {
				autoFit: false,
				horizontal: true,
				vertical: true
			},
			freezeCols: 4,
			numberCell: {
				show: false
			},
			title: false,
			resizable: true,
			sortable: false,
			hoverMode: 'row',
			wrap: false,
			columnBorders: true,
			// 셀 클릭 이벤트 - 체크박스와 수정 이벤트 분리
			cellClick: function(event, ui) {
				const colIndx = ui.colIndx;
				const rowData = ui.rowData;
				const memberIdx = rowData.member_idx;

				// 첫 번째 컬럼(체크박스 컬럼)인 경우
				if (colIndx === 0) {
					// 체크박스를 직접 클릭한 경우가 아니라면 체크박스 토글
					if (!$(event.originalEvent.target).hasClass('member-checkbox')) {
						const checkbox = $('.member-checkbox[data-member-idx="' + memberIdx + '"]');
						const isCurrentlyChecked = checkbox.is(':checked');
						checkbox.prop('checked', !isCurrentlyChecked);

						// 체크박스 상태 업데이트
						updateSelectAllCheckbox();
						updateSelectedMemberButtons();
					}
					// 체크박스 컬럼에서는 수정 창을 열지 않음
					return;
				}

				// 체크박스 컬럼이 아닌 경우 - 수정 offcanvas 열기
				clearTimeout(window.memberCellClickTimeout);
				window.memberCellClickTimeout = setTimeout(function() {
					openMemberOffcanvas('edit', rowData);
				}, 200);
			},
			refresh: function () {
				bindCheckboxEvents();
			}
		};

		try {
			memberGrid = $("#memberGrid").pqGrid(gridOptions);
			console.log('ParamQuery 초기화 완료');

			bindCheckboxEvents();
		} catch (error) {
			console.error('ParamQuery Grid 초기화 실패:', error);
			showToast('그리드 초기화에 실패했습니다.');
		}
	}

	/**
	 * 체크박스 이벤트 바인딩
	 */
	function bindCheckboxEvents() {
		// 전체 선택 체크박스 이벤트
		$(document).off('change', '#selectAllCheckbox').on('change', '#selectAllCheckbox', function () {
			const isChecked = $(this).is(':checked');
			$('.member-checkbox').prop('checked', isChecked);
			updateSelectedMemberButtons();
		});

		// 개별 체크박스 이벤트
		$(document).off('change', '.member-checkbox').on('change', '.member-checkbox', function () {
			updateSelectAllCheckbox();
			updateSelectedMemberButtons();
		});
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
	 * 선택된 회원 기반 버튼 상태 업데이트 (수정 버튼 제거)
	 */
	function updateSelectedMemberButtons() {
		const checkedCheckboxes = $('.member-checkbox:checked').length;

		if (checkedCheckboxes > 0) {
			$('#btnDeleteMember').prop('disabled', false);
		} else {
			$('#btnDeleteMember').prop('disabled', true);
		}
	}

	/**
	 * 선택된 회원 데이터 가져오기
	 */
	function getSelectedMembers() {
		const selectedMembers = [];
		$('.member-checkbox:checked').each(function () {
			const memberIdx = $(this).data('member-idx');
			const gridData = memberGrid.pqGrid("option", "dataModel.data");
			const memberData = gridData.find(member => member.member_idx == memberIdx);
			if (memberData) {
				selectedMembers.push(memberData);
			}
		});
		return selectedMembers;
	}

	/**
	 * 이벤트 바인딩 (수정된 버전 - 모달 사용)
	 */
	function bindEvents() {
		// 회원 삭제 버튼
		$('#btnDeleteMember').on('click', function () {
			const selectedMembers = getSelectedMembers();
			if (selectedMembers.length === 0) {
				showToast('삭제할 회원을 선택해주세요.');
				return;
			}
			deleteSelectedMembers(selectedMembers);
		});

		// 회원 저장 버튼
		$('#btnSaveMember').on('click', function () {
			saveMember();
		});
	}

	// 회원 추가 버튼 이벤트
	$(document).on('click', '#btnAddMember', function (e) {
		e.preventDefault();

		// 현재 선택된 그룹 정보 확인
		const tree = $("#groupTree").fancytree("getTree");
		const activeNode = tree.getActiveNode();

		if (!activeNode) {
			showToast('그룹을 선택해주세요.');
			return;
		}

		const nodeData = activeNode.data;

		// 가장 상위 그룹(조직)에서는 추가 불가
		if (nodeData.type === 'org') {
			showToast('가장 상위 그룹에서는 회원을 추가할 수 없습니다. 하위 그룹을 선택해주세요.');
			return;
		}

		// 바로 회원 추가 실행
		addNewMember(nodeData);
	});

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
					showToast('회원이 추가되었습니다: ' + response.member_name);
					loadMemberData();
				} else {
					showToast(response.message || '회원 추가에 실패했습니다.');
				}
			},
			error: function (xhr, status, error) {
				console.error('회원 추가 오류:', error);
				showToast('회원 추가 중 오류가 발생했습니다.');
			}
		});
	}

	/**
	 * 선택된 회원들 삭제
	 */
	function deleteSelectedMembers(selectedMembers) {
		const memberCount = selectedMembers.length;

		// 현재 선택된 그룹 타입 확인
		const tree = $("#groupTree").fancytree("getTree");
		const activeNode = tree.getActiveNode();

		if (!activeNode) {
			showToast('그룹을 선택해주세요.');
			return;
		}

		const nodeData = activeNode.data;
		const isUnassigned = nodeData.type === 'unassigned';

		// 메시지 설정
		let message;
		if (isUnassigned) {
			message = `미분류에서의 삭제는 복원이 불가합니다. 정말로 ${memberCount}명의 회원을 삭제하시겠습니까?`;
		} else {
			message = `정말로 ${memberCount}명의 회원을 삭제하시겠습니까?(미분류로 이동)`;
		}

		// 모달에 메시지 설정
		$('#deleteMessage').text(message);

		// 삭제 확인 버튼 이벤트 설정
		$('#confirmDeleteBtn').off('click').on('click', function() {
			executeMemberDelete(selectedMembers, isUnassigned ? 'unassigned' : 'area');
			$('#deleteMemberModal').modal('hide');
		});

		// 모달 표시
		$('#deleteMemberModal').modal('show');
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
				if (response.success) {
					showToast(response.message);
					loadMemberData();
				} else {
					showToast(response.message);
				}
			},
			error: function (xhr, status, error) {
				console.error('회원 삭제 실패:', error);
				showToast('회원 삭제에 실패했습니다.');
			}
		});
	}
	/**
	 * 선택된 조직명 업데이트
	 */
	function updateSelectedOrgName(title, type) {
		const orgNameElement = $('#selectedOrgName');
		if (orgNameElement.length) {
			if (type === 'org') {
				orgNameElement.text(`${title} - 전체 회원`);
			} else if (type === 'area') {
				orgNameElement.text(`${title} 소그룹`);
			} else if (type === 'unassigned') {
				orgNameElement.text(`미분류 회원`);
			}
		}
	}

	/**
	 * 회원 데이터 로드
	 */
	function loadMemberData() {
		if (!selectedOrgId) return;

		console.log('회원 데이터 로드:', {
			type: selectedType,
			org_id: selectedOrgId,
			area_idx: selectedAreaIdx
		});

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
				console.log('회원 데이터 응답:', response);

				if (response.success) {
					if (memberGrid) {
						try {
							memberGrid.pqGrid("option", "dataModel.data", response.data || []);
							memberGrid.pqGrid("refreshDataAndView");
						} catch (error) {
							console.error('그리드 데이터 업데이트 실패:', error);
						}
					}

					// 버튼 상태 초기화
					$('#btnDeleteMember').prop('disabled', true);
				} else {
					console.error('회원 데이터 로드 실패:', response.message);
					showToast('회원 데이터를 불러오는데 실패했습니다.');
				}
			},
			error: function (xhr, status, error) {
				console.error('회원 데이터 로드 실패:', error);
				console.error('Response:', xhr.responseText);
				showToast('회원 데이터를 불러오는데 실패했습니다.');
			}
		});
	}

	/**
	 * 회원 offcanvas 열기
	 */
	function openMemberOffcanvas(mode, memberData = null) {
		const offcanvas = $('#memberOffcanvas');
		const title = mode === 'add' ? '회원 추가' : '회원 정보 수정';

		$('#memberOffcanvasLabel').text(title);

		// 폼 초기화
		$('#memberForm')[0].reset();
		$('#photoPreview').hide();
		$('#photoUpload').show();

		// 소그룹 옵션 로드
		loadAreaOptions(selectedOrgId);

		if (mode === 'edit' && memberData) {
			// 수정 모드일 때 기존 데이터 채우기
			$('#member_idx').val(memberData.member_idx);
			$('#member_name').val(memberData.member_name);
			$('#member_phone').val(memberData.member_phone);
			$('#member_birth').val(memberData.member_birth);
			$('#member_address').val(memberData.member_address);
			$('#grade').val(memberData.grade);
			$('#area_idx').val(memberData.area_idx);
			$('#org_id').val(memberData.org_id);
			$('#leader_yn').prop('checked', memberData.leader_yn === 'Y');
			$('#new_yn').prop('checked', memberData.new_yn === 'Y');

			// 기존 사진이 있으면 미리보기 표시
			if (memberData.photo && memberData.photo !== '/assets/images/photo_no.png') {
				$('#previewImage').attr('src', memberData.photo);
				$('#photoPreview').show();
				$('#photoUpload').hide();
			}
		}

		// 사진 이벤트 바인딩
		bindPhotoEvents();

		// offcanvas 표시
		const offcanvasInstance = new bootstrap.Offcanvas(offcanvas[0]);
		offcanvasInstance.show();
	}

	/**
	 * 사진 관련 이벤트 바인딩
	 */
	function bindPhotoEvents() {
		// 파일 선택 이벤트
		$('#member_photo').off('change').on('change', function(e) {
			const file = e.target.files[0];
			if (file) {
				if (!file.type.startsWith('image/')) {
					showToast('이미지 파일만 선택 가능합니다.');
					return;
				}

				const reader = new FileReader();
				reader.onload = function(e) {
					$('#previewImage').attr('src', e.target.result);
					$('#photoPreview').show();
					$('#photoUpload').hide();
				};
				reader.readAsDataURL(file);
			}
		});

		// 사진 삭제 버튼
		$('#removePhoto').off('click').on('click', function() {
			$('#member_photo').val('');
			$('#photoPreview').hide();
			$('#photoUpload').show();
		});
	}

	/**
	 * 회원 저장
	 */
	function saveMember() {
		const form = $('#memberForm')[0];
		const formData = new FormData(form);

		// 필수 필드 검증
		const memberName = $('#member_name').val().trim();
		if (!memberName) {
			showToast('이름을 입력해주세요.');
			$('#member_name').focus();
			return;
		}

		// 조직 ID 설정
		formData.append('org_id', selectedOrgId);

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
				if (response.success) {
					showToast(response.message);

					// offcanvas 닫기
					const offcanvasInstance = bootstrap.Offcanvas.getInstance($('#memberOffcanvas')[0]);
					if (offcanvasInstance) {
						offcanvasInstance.hide();
					}

					// 그리드 새로고침
					loadMemberData();
				} else {
					showToast(response.message);
				}
			},
			error: function(xhr, status, error) {
				console.error('회원 저장 실패:', error);
				showToast('회원 정보 저장에 실패했습니다.');
			}
		});
	}

	/**
	 * 소그룹 옵션 로드
	 */
	function loadAreaOptions(orgId) {
		const areaSelect = $('#area_idx');
		areaSelect.html('<option value="">소그룹 선택</option>');

		// 현재 선택된 그룹의 소그룹 목록을 트리에서 찾기
		try {
			const tree = $("#groupTree").fancytree("getTree");
			const groupNode = tree.getNodeByKey('org_' + orgId);

			if (groupNode && groupNode.children) {
				groupNode.children.forEach(function (child) {
					const areaData = child.data;
					// 미분류 그룹은 제외하고 일반 소그룹만 추가
					if (areaData.type === 'area') {
						areaSelect.append(`<option value="${areaData.area_idx}">${child.title}</option>`);
					}
				});
			}
		} catch (error) {
			console.error('소그룹 옵션 로드 오류:', error);
		}
	}

	/**
	 * Toast 메시지 표시
	 */
	function showToast(message) {
		const toastElement = $('#memberToast');
		const toastBody = toastElement.find('.toast-body');

		toastBody.text(message);

		const toast = new bootstrap.Toast(toastElement[0], {
			autohide: true,
			delay: 3000
		});

		toast.show();
	}

	/**
	 * 트리 초기화 (계층적 구조 지원)
	 */
	function initializeTree() {
		console.log('트리 초기화 중...');

		// 기존 트리가 있다면 제거
		if ($("#groupTree").fancytree("getTree")) {
			$("#groupTree").fancytree("destroy");
		}

		// 트리 데이터 가져오기
		$.ajax({
			url: window.memberPageData.baseUrl + 'member/get_org_tree',
			method: 'POST',
			dataType: 'json',
			success: function(data) {
				console.log('트리 데이터 로드됨:', data);

				$("#groupTree").fancytree({
					checkbox: false,
					selectMode: 1,
					source: data,

					// 계층적 구조 설정
					extensions: ["wide"],
					wide: {
						iconWidth: "1em",
						iconSpacing: "0.5em",
						levelOfs: "1.5em"
					},

					// 트리 확장/축소 설정
					autoCollapse: false,
					clickFolderMode: 1, // 폴더 클릭 시 확장/축소

					activate: function(event, data) {
						const node = data.node;
						console.log('노드 선택됨:', node.data);

						// 선택된 그룹 저장
						saveSelectedGroupToStorage(node.data);

						// 선택된 조직/그룹 제목 업데이트
						updateSelectedOrgTitle(node);

						// 회원 목록 로드
						loadMemberList(node.data);
					},

					expand: function(event, data) {
						console.log('노드 확장됨:', data.node.title);
					},

					collapse: function(event, data) {
						console.log('노드 축소됨:', data.node.title);
					},

					renderNode: function(event, data) {
						const node = data.node;
						const $span = $(node.span);

						// 조직 노드에 아이콘 추가
						if (node.data && node.data.type === 'org') {
							$span.find('.fancytree-title').prepend('<i class="bi bi-building me-1"></i>');
						}
						// 영역 노드에 아이콘 추가
						else if (node.data && node.data.type === 'area') {
							const level = node.getLevel();
							if (level === 2) { // 1차 하위 그룹
								$span.find('.fancytree-title').prepend('<i class="bi bi-folder me-1"></i>');
							} else if (level >= 3) { // 2차 이상 하위 그룹
								$span.find('.fancytree-title').prepend('<i class="bi bi-folder2-open me-1"></i>');
							}
						}
						// 미분류 노드에 아이콘 추가
						else if (node.data && node.data.type === 'unassigned') {
							$span.find('.fancytree-title').prepend('<i class="bi bi-question-circle me-1"></i>');
						}
					}
				});

				// 저장된 선택 상태 복원 또는 첫 번째 조직 선택
				restoreSelectedGroupFromStorage(data);
			},
			error: function(xhr, status, error) {
				console.error('트리 데이터 로드 실패:', error);
				showToast('트리 데이터를 불러오는데 실패했습니다.', 'error');
			}
		});
	}

	/**
	 * 선택된 조직/그룹 제목 업데이트 (계층 구조 고려)
	 */
	function updateSelectedOrgTitle(node) {
		let title = '';
		let icon = '';

		if (node.data.type === 'org') {
			icon = '<i class="bi bi-building"></i>';
			title = node.title;
		} else if (node.data.type === 'area') {
			icon = '<i class="bi bi-people"></i>';

			// 계층 구조에 따른 제목 생성
			const breadcrumb = [];
			let currentNode = node;

			// 상위 노드들을 역순으로 수집
			while (currentNode && currentNode.data.type !== 'org') {
				breadcrumb.unshift(currentNode.title);
				currentNode = currentNode.parent;
			}

			// 조직명 추가
			if (currentNode && currentNode.data.type === 'org') {
				breadcrumb.unshift(currentNode.title);
			}

			title = breadcrumb.join(' > ');
		} else if (node.data.type === 'unassigned') {
			icon = '<i class="bi bi-question-circle"></i>';
			title = node.title;
		}

		$('#selectedOrgName').html(icon + ' ' + title);
	}

});
