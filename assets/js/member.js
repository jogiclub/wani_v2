/**
 * 파일 위치: assets/js/member.js
 * 역할: 회원 관리 페이지 JavaScript - 전역/지역 변수 정리된 버전
 */

$(document).ready(function () {
	// ===== 전역 변수 영역 =====
	let memberGrid;                    // ParamQuery Grid 인스턴스
	let selectedOrgId = null;          // 선택된 조직 ID
	let selectedAreaIdx = null;        // 선택된 소그룹 ID
	let selectedType = null;           // 선택된 타입 ('org', 'area', 'unassigned')
	let croppieInstance = null;        // Croppie 인스턴스 (전역으로 이동)

	// ===== 디버깅 및 초기화 =====
	console.log('jQuery:', typeof $);
	console.log('Fancytree:', typeof $.fn.fancytree);
	console.log('ParamQuery pqGrid:', typeof $.fn.pqGrid);

	// 초기화 시도 (지연 로딩)
	setTimeout(function () {
		initializePage();
	}, 800);

	/**
	 * 페이지 초기화 메인 함수
	 */
	function initializePage() {
		console.log('페이지 초기화 시작');

		// 라이브러리 검증
		if (typeof $.fn.pqGrid === 'undefined') {
			console.error('ParamQuery 라이브러리가 로드되지 않았습니다.');
			showToast('ParamQuery 라이브러리 로드 실패', 'error');
			return;
		}

		if (typeof $.fn.fancytree === 'undefined') {
			console.error('Fancytree 라이브러리가 로드되지 않았습니다.');
			showToast('Fancytree 라이브러리 로드 실패', 'error');
			return;
		}

		try {
			initializeFancytree();
			initializeParamQuery();
			bindGlobalEvents();
			setupCleanupEvents();
			console.log('페이지 초기화 완료');
		} catch (error) {
			console.error('초기화 중 오류:', error);
			showToast('페이지 초기화 중 오류가 발생했습니다.', 'error');
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

		// 회원 저장 버튼
		$('#btnSaveMember').on('click', function () {
			saveMember();
		});

		// 회원 추가 버튼 (이벤트 위임 사용)
		$(document).on('click', '#btnAddMember', function (e) {
			e.preventDefault();
			handleAddMemberClick();
		});
	}

	/**
	 * 정리 이벤트 설정
	 */
	function setupCleanupEvents() {
		// 페이지 떠날 때 정리
		$(window).on('beforeunload', function() {
			destroyCroppie();
		});

		// offcanvas 닫힐 때 정리
		$('#memberOffcanvas').on('hidden.bs.offcanvas', function() {
			destroyCroppie();
		});
	}

	// ===== FANCYTREE 관련 함수들 =====

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
					showToast('조직 데이터가 없습니다.', 'warning');
					return;
				}

				setupFancytreeInstance(treeData);
				restoreSelectedGroupFromStorage(treeData);
				console.log('Fancytree 초기화 완료');
			},
			error: function (xhr, status, error) {
				console.error('그룹 트리 로드 실패:', error);
				console.error('Response:', xhr.responseText);
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
			activate: function (event, data) {
				handleTreeNodeActivate(data.node);
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
		console.log('선택된 노드:', nodeData);

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

			// 저장된 노드 찾기 및 선택
			const tree = $("#groupTree").fancytree("getTree");
			const nodeToSelect = findSavedNode(tree, groupData);

			if (nodeToSelect) {
				nodeToSelect.setActive(true);
				nodeToSelect.setFocus(true);
				expandParentNodes(nodeToSelect, groupData);
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

	// ===== PARAMQUERY GRID 관련 함수들 =====

	/**
	 * ParamQuery Grid 초기화
	 */
	function initializeParamQuery() {
		console.log('ParamQuery 초기화 시작');

		const gridOptions = createGridOptions();

		try {
			memberGrid = $("#memberGrid").pqGrid(gridOptions);
			console.log('ParamQuery 초기화 완료');
			bindCheckboxEvents();
		} catch (error) {
			console.error('ParamQuery Grid 초기화 실패:', error);
			showToast('그리드 초기화에 실패했습니다.', 'error');
		}
	}

	/**
	 * 그리드 옵션 생성
	 */
	function createGridOptions() {
		return {
			width: "100%",
			height: "100%",
			dataModel: {
				data: []
			},
			colModel: createColumnModel(),
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
			numberCell: { show: false },
			title: false,
			resizable: true,
			sortable: false,
			hoverMode: 'row',
			wrap: false,
			columnBorders: true,
			cellClick: function(event, ui) {
				handleGridCellClick(event, ui);
			},
			refresh: function () {
				bindCheckboxEvents();
			}
		};
	}

	/**
	 * 컬럼 모델 생성
	 */
	function createColumnModel() {
		return [
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
			// ... 기타 컬럼들
			{
				title: "회원번호",
				dataIndx: "member_idx",
				width: 80,
				align: "center",
				hidden: true
			},
			{
				title: "닉네임",
				dataIndx: "member_nick",
				width: 100,
				align: "center"
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
				title: "상세주소",
				dataIndx: "member_address_detail",
				width: 250,
				align: "left"
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
				width: 120,
				align: "center",
				render: function (ui) {
					return formatDateTime(ui.cellData);
				}
			},
			{
				title: "수정일",
				dataIndx: "modi_date",
				width: 120,
				align: "center",
				render: function (ui) {
					return formatDateTime(ui.cellData);
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
	 * 그리드 셀 클릭 처리
	 */
	function handleGridCellClick(event, ui) {
		const colIndx = ui.colIndx;
		const rowData = ui.rowData;
		const memberIdx = rowData.member_idx;

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
	 * 체크박스 컬럼 클릭 처리
	 */
	function handleCheckboxColumnClick(event, memberIdx) {
		// 직접 체크박스를 클릭한 경우가 아니라면 체크박스 토글
		if (!$(event.originalEvent.target).hasClass('member-checkbox')) {
			const checkbox = $('.member-checkbox[data-member-idx="' + memberIdx + '"]');
			const isCurrentlyChecked = checkbox.is(':checked');
			checkbox.prop('checked', !isCurrentlyChecked);

			// 체크박스 상태 업데이트
			updateSelectAllCheckbox();
			updateSelectedMemberButtons();
		}
	}

	// ===== 체크박스 관련 함수들 =====

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
	 * 선택된 회원 기반 버튼 상태 업데이트
	 */
	function updateSelectedMemberButtons() {
		const checkedCheckboxes = $('.member-checkbox:checked').length;
		$('#btnDeleteMember').prop('disabled', checkedCheckboxes === 0);
		$('#btnMoveMember').prop('disabled', checkedCheckboxes === 0);
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

		const nodeData = activeNode.data;

		// 미분류 그룹에서는 이동만 가능 (삭제 불가)
		const currentGroupName = activeNode.title.replace(/\s*\(\d+명\)$/, '');

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
				// 재귀적으로 모든 하위 노드를 처리하는 함수
				function addAreaOptionsRecursively(nodes, depth = 0) {
					nodes.forEach(function(node) {
						const areaData = node.data;
						// 미분류 그룹은 제외하고 일반 소그룹만 추가
						if (areaData.type === 'area') {
							// depth에 따라 들여쓰기 표시
							const indent = '　'.repeat(depth); // 전각 공백으로 들여쓰기
							const optionText = indent + node.title.replace(/\s*\(\d+명\)$/, ''); // 회원 수 표시 제거

							areaSelect.append(`<option value="${areaData.area_idx}">${optionText}</option>`);

							// 하위 노드가 있으면 재귀적으로 처리
							if (node.children && node.children.length > 0) {
								addAreaOptionsRecursively(node.children, depth + 1);
							}
						}
					});
				}

				// 재귀적으로 모든 소그룹 옵션 추가
				addAreaOptionsRecursively(groupNode.children);
			}

			// 콜백 함수가 있으면 실행
			if (typeof callback === 'function') {
				callback();
			}
		} catch (error) {
			console.error('이동용 소그룹 옵션 로드 오류:', error);
			// 에러가 있어도 콜백 실행
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

		// 로딩 상태 표시
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
					refreshGroupTree(); // 트리 새로고침 추가
					// 체크박스 해제
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
				// 로딩 상태 해제
				$('#confirmMoveBtn').prop('disabled', false).html('이동');
			}
		});
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

	// ===== 회원 관련 CRUD 함수들 =====

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

		// 가장 상위 그룹(조직)에서는 추가 불가
		if (nodeData.type === 'org') {
			showToast('가장 상위 그룹에서는 회원을 추가할 수 없습니다. 하위 그룹을 선택해주세요.', 'warning');
			return;
		}

		// 바로 회원 추가 실행
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
					refreshGroupTree(); // 트리 새로고침 추가
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
					refreshGroupTree(); // 트리 새로고침 추가
				}
			},
			error: function (xhr, status, error) {
				console.error('회원 삭제 실패:', error);
				showToast('회원 삭제에 실패했습니다.', 'error');
			}
		});
	}


	/**
	 * 그룹 트리 새로고침 (현재 선택 상태 유지)
	 */
	function refreshGroupTree() {
		console.log('그룹 트리 새로고침 시작');

		// 현재 선택된 노드 정보 저장
		const tree = $("#groupTree").fancytree("getTree");
		const activeNode = tree.getActiveNode();
		let currentSelection = null;

		if (activeNode) {
			currentSelection = {
				key: activeNode.key,
				type: activeNode.data.type,
				org_id: activeNode.data.org_id,
				area_idx: activeNode.data.area_idx || null
			};
		}

		$.ajax({
			url: window.memberPageData.baseUrl + 'member/get_org_tree',
			method: 'POST',
			dataType: 'json',
			success: function (treeData) {
				console.log('트리 새로고침 데이터:', treeData);

				if (!treeData || treeData.length === 0) {
					showToast('조직 데이터가 없습니다.', 'warning');
					return;
				}

				// 기존 트리 제거
				$("#groupTree").fancytree("destroy");

				// 새 트리 생성
				setupFancytreeInstance(treeData);

				// 이전 선택 상태 복원
				if (currentSelection) {
					restoreTreeSelection(currentSelection);
				}

				console.log('그룹 트리 새로고침 완료');
			},
			error: function (xhr, status, error) {
				console.error('그룹 트리 새로고침 실패:', error);
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

			// 저장된 키로 노드 찾기
			if (selection.key) {
				nodeToSelect = tree.getNodeByKey(selection.key);
			}

			// 키로 찾지 못했을 경우 타입과 ID로 찾기
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

				// 부모 노드가 있으면 확장
				if (nodeToSelect.parent && !nodeToSelect.parent.isRootNode()) {
					nodeToSelect.parent.setExpanded(true);
				}

				console.log('트리 선택 상태 복원됨:', selection);
			} else {
				console.log('복원할 노드를 찾을 수 없음, 첫 번째 조직 선택');
				selectFirstOrganization();
			}
		} catch (error) {
			console.error('트리 선택 상태 복원 실패:', error);
			selectFirstOrganization();
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
				handleMemberDataResponse(response);
			},
			error: function (xhr, status, error) {
				console.error('회원 데이터 로드 실패:', error);
				console.error('Response:', xhr.responseText);
				showToast('회원 데이터를 불러오는데 실패했습니다.', 'error');
			}
		});
	}

	/**
	 * 회원 데이터 응답 처리
	 */
	function handleMemberDataResponse(response) {
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
			showToast('회원 데이터를 불러오는데 실패했습니다.', 'error');
		}
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
	 * 파일 위치: assets/js/member.js
	 * 역할: 회원 offcanvas 열기 함수 수정 (소그룹 선택 문제 해결)
	 */
	function openMemberOffcanvas(mode, memberData = null) {
		const offcanvas = $('#memberOffcanvas');
		const title = generateOffcanvasTitle(mode, memberData);

		$('#memberOffcanvasLabel').text(title);

		// 폼 및 UI 초기화
		resetOffcanvasForm();

		if (mode === 'edit' && memberData) {
			// 소그룹 옵션 로드 완료 후 폼 데이터 채우기
			loadAreaOptionsWithCallback(selectedOrgId, function() {
				populateFormData(memberData);
			});
		} else {
			// 추가 모드일 때는 그냥 소그룹 옵션만 로드
			loadAreaOptions(selectedOrgId);
		}

		// 사진 이벤트 바인딩
		bindPhotoEvents();

		// offcanvas 표시 및 정리 이벤트 설정
		showOffcanvasWithCleanup(offcanvas);
	}

	/**
	 * 파일 위치: assets/js/member.js
	 * 역할: 콜백을 지원하는 소그룹 옵션 로드 함수
	 */
	function loadAreaOptionsWithCallback(orgId, callback) {
		const areaSelect = $('#area_idx');
		areaSelect.html('<option value="">소그룹 선택</option>');

		try {
			const tree = $("#groupTree").fancytree("getTree");
			const groupNode = tree.getNodeByKey('org_' + orgId);

			if (groupNode && groupNode.children) {
				// 재귀적으로 모든 하위 노드를 처리하는 함수
				function addAreaOptionsRecursively(nodes, depth = 0) {
					nodes.forEach(function(node) {
						const areaData = node.data;
						// 미분류 그룹은 제외하고 일반 소그룹만 추가
						if (areaData.type === 'area') {
							// depth에 따라 들여쓰기 표시
							const indent = '　'.repeat(depth); // 전각 공백으로 들여쓰기
							const optionText = indent + node.title.replace(/\s*\(\d+명\)$/, ''); // 회원 수 표시 제거

							areaSelect.append(`<option value="${areaData.area_idx}">${optionText}</option>`);

							// 하위 노드가 있으면 재귀적으로 처리
							if (node.children && node.children.length > 0) {
								addAreaOptionsRecursively(node.children, depth + 1);
							}
						}
					});
				}

				// 재귀적으로 모든 소그룹 옵션 추가
				addAreaOptionsRecursively(groupNode.children);
			}

			// 콜백 함수가 있으면 실행
			if (typeof callback === 'function') {
				callback();
			}
		} catch (error) {
			console.error('소그룹 옵션 로드 오류:', error);
			// 에러가 있어도 콜백 실행
			if (typeof callback === 'function') {
				callback();
			}
		}
	}

	/**
	 * 파일 위치: assets/js/member.js
	 * 역할: 기존 소그룹 옵션 로드 함수 (기존 호환성 유지)
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
	 * Offcanvas 폼 초기화
	 */
	function resetOffcanvasForm() {
		$('#memberForm')[0].reset();
		$('#photoPreview').hide();
		$('#photoUpload').show();
		$('#cropContainer').hide();

		// 사진 삭제 플래그 초기화
		$('#delete_photo').remove();

		destroyCroppie();
	}

	/**
	 * 폼 데이터 채우기
	 */
	function populateFormData(memberData) {
		const fieldMappings = {
			'member_idx': memberData.member_idx,
			'member_name': memberData.member_name,
			'member_nick': memberData.member_nick || '',
			'member_phone': memberData.member_phone || '',
			'member_birth': memberData.member_birth || '',
			'member_address': memberData.member_address || '',
			'member_address_detail': memberData.member_address_detail || '',
			'member_etc': memberData.member_etc || '',
			'grade': memberData.grade || '0',
			'area_idx': memberData.area_idx || '',
			'org_id': memberData.org_id
		};

		// 일반 필드 채우기
		Object.keys(fieldMappings).forEach(fieldName => {
			$('#' + fieldName).val(fieldMappings[fieldName]);
		});

		// 체크박스 필드 설정
		$('#leader_yn').prop('checked', memberData.leader_yn === 'Y');
		$('#new_yn').prop('checked', memberData.new_yn === 'Y');

		// 기존 사진이 있으면 미리보기 표시
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

		// offcanvas가 닫힐 때 croppie 인스턴스 정리
		offcanvas.off('hidden.bs.offcanvas.croppie').on('hidden.bs.offcanvas.croppie', function() {
			destroyCroppie();
		});
	}

	/**
	 * 사진 관련 이벤트 바인딩 (Croppie 적용)
	 */
	function bindPhotoEvents() {
		// 파일 선택 이벤트
		$('#member_photo').off('change').on('change', handlePhotoFileSelect);

		// 크롭 버튼 클릭 이벤트
		$('#cropPhoto').off('click').on('click', handleCropButtonClick);

		// 사진 삭제 버튼
		$('#removePhoto').off('click').on('click', handleRemovePhotoClick);

		// 크롭 저장 버튼
		$('#saveCrop').off('click').on('click', saveCroppedImage);

		// 크롭 취소 버튼
		$('#cancelCrop').off('click').on('click', cancelCrop);
	}

	/**
	 * 사진 파일 선택 처리 (삭제 플래그 해제 추가)
	 */
	function handlePhotoFileSelect(e) {
		const file = e.target.files[0];
		if (!file) return;

		if (!validateImageFile(file)) {
			return;
		}

		// 새 파일이 선택되면 삭제 플래그 해제
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

		// 기존 사진 삭제를 위한 hidden 필드 추가/업데이트
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
		// 기존 croppie 인스턴스가 있다면 제거
		destroyCroppie();

		$('#photoPreview').hide();
		$('#cropContainer').show();

		// Croppie 인스턴스 생성
		croppieInstance = new Croppie(document.getElementById('cropBox'), {
			viewport: {
				width: 150,
				height: 150,
				type: 'circle'  // 원형 크롭
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

		// 이미지 바인딩
		croppieInstance.bind({
			url: imageSrc
		}).catch(function(error) {
			console.error('Croppie 바인딩 오류:', error);
			showToast('이미지 로드에 실패했습니다.', 'error');
			cancelCrop();
		});
	}


	/**
	 * 크롭된 이미지 저장 (삭제 플래그 해제 추가)
	 */
	function saveCroppedImage() {
		if (!croppieInstance) {
			showToast('크롭 인스턴스가 없습니다.', 'error');
			return;
		}

		// 크롭된 결과 가져오기
		croppieInstance.result({
			type: 'canvas',
			size: { width: 200, height: 200 },
			format: 'jpeg',
			quality: 0.9,
			circle: true  // 원형 결과
		}).then(function(croppedImage) {
			// 미리보기 이미지 업데이트
			$('#previewImage').attr('src', croppedImage);

			// Base64를 File 객체로 변환하여 폼에 설정
			dataURLtoFile(croppedImage, 'cropped_image.jpg').then(function(file) {
				// 파일 입력 필드에 새 파일 설정
				const dt = new DataTransfer();
				dt.items.add(file);
				document.getElementById('member_photo').files = dt.files;

				// 새 이미지가 설정되었으므로 삭제 플래그 해제
				$('#delete_photo').remove();
			});

			// UI 상태 변경
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
	 * 회원 저장 (Croppie 적용)
	 */
	function saveMember() {
		// 필수 필드 검증
		if (!validateMemberForm()) {
			return;
		}

		// 크롭 진행 중인 경우 저장 방지
		if ($('#cropContainer').is(':visible')) {
			showToast('이미지 크롭을 완료하거나 취소해주세요.', 'warning');
			return;
		}

		const form = $('#memberForm')[0];
		const formData = new FormData(form);
		formData.append('org_id', selectedOrgId);

		// 로딩 상태 표시
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
				// 로딩 상태 해제
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
			// offcanvas 닫기
			const offcanvasInstance = bootstrap.Offcanvas.getInstance($('#memberOffcanvas')[0]);
			if (offcanvasInstance) {
				offcanvasInstance.hide();
			}
			// 그리드 새로고침
			loadMemberData();
			refreshGroupTree(); // 트리 새로고침 추가
		}
	}

	/**
	 * 파일 위치: assets/js/member.js
	 * 역할: 소그룹 옵션 로드 함수 (전체 depth의 소그룹을 재귀적으로 처리)
	 */
	function loadAreaOptions(orgId) {
		const areaSelect = $('#area_idx');
		areaSelect.html('<option value="">소그룹 선택</option>');

		try {
			const tree = $("#groupTree").fancytree("getTree");
			const groupNode = tree.getNodeByKey('org_' + orgId);

			if (groupNode && groupNode.children) {
				// 재귀적으로 모든 하위 노드를 처리하는 함수
				function addAreaOptionsRecursively(nodes, depth = 0) {
					nodes.forEach(function(node) {
						const areaData = node.data;
						// 미분류 그룹은 제외하고 일반 소그룹만 추가
						if (areaData.type === 'area') {
							// depth에 따라 들여쓰기 표시
							const indent = '　'.repeat(depth); // 전각 공백으로 들여쓰기
							const optionText = indent + node.title.replace(/\s*\(\d+명\)$/, ''); // 회원 수 표시 제거

							areaSelect.append(`<option value="${areaData.area_idx}">${optionText}</option>`);

							// 하위 노드가 있으면 재귀적으로 처리
							if (node.children && node.children.length > 0) {
								addAreaOptionsRecursively(node.children, depth + 1);
							}
						}
					});
				}

				// 재귀적으로 모든 소그룹 옵션 추가
				addAreaOptionsRecursively(groupNode.children);
			}
		} catch (error) {
			console.error('소그룹 옵션 로드 오류:', error);
		}
	}

	// ===== 유틸리티 함수들 =====

	/**
	 * 이미지 파일 유효성 검사
	 */
	function validateImageFile(file) {
		// 파일 타입 검사
		const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
		if (!allowedTypes.includes(file.type)) {
			showToast('지원하지 않는 이미지 형식입니다. (JPG, PNG, GIF만 가능)', 'error');
			return false;
		}

		// 파일 크기 검사 (5MB)
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
	 * Toast 메시지 표시 (개선된 버전)
	 */
	function showToast(message, type = 'info') {
		const toastElement = $('#memberToast');
		const toastBody = toastElement.find('.toast-body');

		// 타입별 아이콘 설정
		const iconMap = {
			'success': '<i class="bi bi-check-circle text-success me-2"></i>',
			'error': '<i class="bi bi-exclamation-triangle text-danger me-2"></i>',
			'warning': '<i class="bi bi-exclamation-circle text-warning me-2"></i>',
			'info': '<i class="bi bi-info-circle text-primary me-2"></i>'
		};

		const icon = iconMap[type] || iconMap['info'];
		toastBody.html(icon + message);

		const toast = new bootstrap.Toast(toastElement[0], {
			autohide: true,
			delay: type === 'error' ? 5000 : 3000  // 에러 메시지는 더 길게 표시
		});

		toast.show();
	}

});
