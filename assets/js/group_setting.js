/**
 * 파일 위치: assets/js/group_setting.js
 * 역할: 그룹설정 화면의 JavaScript 처리
 */

// 전역 변수 선언
let selectedOrgId;
let selectedGroupData = null;

$(document).ready(function() {
	// 전역 변수 초기화
	selectedOrgId = window.groupSettingPageData.currentOrgId;

	// Split.js 초기화
	initializeSplitPanes();

	// 그룹 트리 로드
	loadGroupTree();

	// 이벤트 리스너 등록
	registerEventListeners();
});

// ================================
// 초기화 함수들
// ================================

/**
 * Split.js 패널 초기화
 */
function initializeSplitPanes() {
	Split(['#left-pane', '#right-pane'], {
		sizes: [30, 70],
		minSize: [250, 400],
		gutterSize: 10,
		cursor: 'col-resize'
	});
}

/**
 * 이벤트 리스너 등록
 */
function registerEventListeners() {
	// 그룹생성 버튼
	$('#btnAddGroup').on('click', handleAddGroup);

	// 그룹명변경 버튼
	$('#btnRenameGroup').on('click', handleRenameGroup);

	// 그룹삭제 버튼
	$('#btnDeleteGroup').on('click', handleDeleteGroup);

	// 그룹이동 버튼
	$('#btnMoveGroup').on('click', handleMoveGroup);

	// 그룹이동(최상위) 버튼
	$('#btnMoveGroupToTop').on('click', handleMoveGroupToTop);

	// 모달 확인 버튼들
	$('#confirmRenameGroupBtn').on('click', executeGroupRename);
	$('#confirmDeleteGroupBtn').on('click', executeGroupDelete);
	$('#confirmMoveGroupBtn').on('click', executeGroupMove);

	// 그룹명변경 모달에서 Enter 키 처리
	$('#newGroupName').on('keypress', function(e) {
		if (e.which === 13) {
			executeGroupRename();
		}
	});
}

// ================================
// 트리 관련 함수들
// ================================

/**
 * 그룹 트리 로드
 */
function loadGroupTree() {
	showTreeSpinner();

	$.ajax({
		url: window.groupSettingPageData.baseUrl + 'group_setting/get_group_tree',
		method: 'POST',
		data: { org_id: selectedOrgId },
		dataType: 'json',
		success: function(response) {
			hideTreeSpinner();
			if (response && response.length > 0) {
				setupFancytreeInstance(response);
			} else {
				showToast('그룹 데이터가 없습니다.', 'warning');
			}
		},
		error: function(xhr, status, error) {
			console.error('그룹 트리 로드 실패:', error);
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
		extensions: ["wide"],
		activate: function(event, data) {
			const node = data.node;
			selectedGroupData = node.data;
			updateSelectedGroupName(node.title, selectedGroupData.type);
			updateManagementButtons(selectedGroupData);
		},
		init: function(event, data) {
			// 트리 로딩 완료 후 모든 노드 확장
			data.tree.expandAll();
		}
	});
}

/**
 * 트리 로드 후 특정 노드 선택
 */
function loadGroupTreeAndSelectNode(areaIdxToSelect) {
	showTreeSpinner();

	$.ajax({
		url: window.groupSettingPageData.baseUrl + 'group_setting/get_group_tree',
		method: 'POST',
		data: { org_id: selectedOrgId },
		dataType: 'json',
		success: function(response) {
			hideTreeSpinner();
			if (response && response.length > 0) {
				$("#groupTree").fancytree("destroy");
				setupFancytreeInstance(response);

				if (areaIdxToSelect) {
					selectNodeByAreaIdx(areaIdxToSelect);
				}
			} else {
				showToast('그룹 데이터가 없습니다.', 'warning');
				resetGroupSelection();
			}
		},
		error: function(xhr, status, error) {
			console.error('그룹 트리 로드 실패:', error);
			hideTreeSpinner();
			showToast('그룹 정보를 불러오는데 실패했습니다.', 'error');
			resetGroupSelection();
		}
	});
}

/**
 * 그룹 삭제 후 트리 로드 및 적절한 노드 선택
 */
function loadGroupTreeAfterDelete(deletedParentIdx, orgId) {
	showTreeSpinner();

	$.ajax({
		url: window.groupSettingPageData.baseUrl + 'group_setting/get_group_tree',
		method: 'POST',
		data: { org_id: selectedOrgId },
		dataType: 'json',
		success: function(response) {
			hideTreeSpinner();
			if (response && response.length > 0) {
				$("#groupTree").fancytree("destroy");
				setupFancytreeInstance(response);
				selectAppropriateNodeAfterDelete(deletedParentIdx, orgId);
			} else {
				showToast('그룹 데이터가 없습니다.', 'warning');
				resetGroupSelection();
			}
		},
		error: function(xhr, status, error) {
			console.error('그룹 트리 로드 실패:', error);
			hideTreeSpinner();
			showToast('그룹 정보를 불러오는데 실패했습니다.', 'error');
			resetGroupSelection();
		}
	});
}

/**
 * area_idx로 노드 선택
 */
function selectNodeByAreaIdx(areaIdx) {
	try {
		const tree = $("#groupTree").fancytree("getTree");
		const nodeToSelect = tree.getNodeByKey('area_' + areaIdx);

		if (nodeToSelect) {
			nodeToSelect.setActive(true);
			expandParentNodes(nodeToSelect);
			console.log('노드 선택됨:', nodeToSelect.title);
		} else {
			console.log('선택할 노드를 찾을 수 없음:', areaIdx);
			selectOrgNode();
		}
	} catch (error) {
		console.error('노드 선택 실패:', error);
		resetGroupSelection();
	}
}

/**
 * 삭제 후 적절한 노드 선택
 */
function selectAppropriateNodeAfterDelete(deletedParentIdx, orgId) {
	const tree = $("#groupTree").fancytree("getTree");
	let nodeToSelect = null;

	try {
		// 1. 삭제된 그룹의 부모가 있었다면 부모 선택
		if (deletedParentIdx) {
			nodeToSelect = tree.getNodeByKey('area_' + deletedParentIdx);
		}

		// 2. 부모가 없거나 찾을 수 없다면 조직 노드 선택
		if (!nodeToSelect) {
			nodeToSelect = tree.getNodeByKey('org_' + orgId);
		}

		// 3. 조직 노드도 찾을 수 없다면 첫 번째 노드 선택
		if (!nodeToSelect) {
			const rootNodes = tree.getRootNode().children;
			if (rootNodes && rootNodes.length > 0) {
				nodeToSelect = rootNodes[0];
			}
		}

		if (nodeToSelect) {
			nodeToSelect.setActive(true);
			console.log('삭제 후 노드 선택됨:', nodeToSelect.title);
		} else {
			console.log('선택할 노드가 없음, 초기 상태로 리셋');
			resetGroupSelection();
		}
	} catch (error) {
		console.error('삭제 후 노드 선택 실패:', error);
		resetGroupSelection();
	}
}

/**
 * 조직 노드 선택
 */
function selectOrgNode() {
	try {
		const tree = $("#groupTree").fancytree("getTree");
		const orgNode = tree.getNodeByKey('org_' + selectedOrgId);
		if (orgNode) {
			orgNode.setActive(true);
		}
	} catch (error) {
		console.error('조직 노드 선택 실패:', error);
	}
}

/**
 * 부모 노드들을 확장하여 선택된 노드가 보이도록 함
 */
function expandParentNodes(node) {
	let parent = node.parent;
	while (parent && !parent.isRootNode()) {
		parent.setExpanded(true);
		parent = parent.parent;
	}
}

// ================================
// UI 업데이트 함수들
// ================================

/**
 * 선택된 그룹명 업데이트
 */
function updateSelectedGroupName(title, type) {
	const groupNameElement = $('#selectedGroupName');
	let displayText = '';
	let icon = '';

	switch(type) {
		case 'org':
			icon = 'bi-building';
			displayText = title;
			break;
		case 'area':
			icon = 'bi-folder';
			displayText = title;
			break;
		case 'unassigned':
			icon = 'bi-question-circle';
			displayText = title;
			break;
		default:
			icon = 'bi-folder';
			displayText = title;
	}

	groupNameElement.html(`<i class="bi ${icon}"></i> ${displayText}`);
}

/**
 * 관리 버튼들 상태 업데이트
 */
function updateManagementButtons(groupData) {
	if (!groupData) {
		$('#groupManagementButtons').hide();
		$('#noSelectionMessage').show();
		return;
	}

	$('#noSelectionMessage').hide();
	$('#groupManagementButtons').show();

	// 버튼 상태 초기화
	$('#btnRenameGroup').prop('disabled', true);
	$('#btnDeleteGroup').prop('disabled', true);
	$('#btnMoveGroup').prop('disabled', true);
	$('#btnMoveGroupToTop').prop('disabled', true);

	if (groupData.type === 'org') {
		// 조직 레벨: 그룹생성만 가능
		$('#btnAddGroup').prop('disabled', false);
	} else if (groupData.type === 'area') {
		// 그룹 레벨: 모든 버튼 활성화
		$('#btnAddGroup').prop('disabled', false);
		$('#btnRenameGroup').prop('disabled', false);
		$('#btnDeleteGroup').prop('disabled', false);
		$('#btnMoveGroup').prop('disabled', false);
		$('#btnMoveGroupToTop').prop('disabled', false);
	} else if (groupData.type === 'unassigned') {
		// 미분류: 그룹생성만 가능
		$('#btnAddGroup').prop('disabled', false);
	}
}

/**
 * 그룹 선택 상태 초기화
 */
function resetGroupSelection() {
	selectedGroupData = null;
	$('#selectedGroupName').html('<i class="bi bi-folder"></i> 그룹을 선택해주세요');
	$('#groupManagementButtons').hide();
	$('#noSelectionMessage').show();
}

// ================================
// 그룹 작업 핸들러 함수들
// ================================

/**
 * 그룹생성 처리
 */
function handleAddGroup() {
	if (!selectedGroupData) {
		showToast('그룹을 선택해주세요.', 'warning');
		return;
	}

	let parentIdx = null;

	if (selectedGroupData.type === 'area') {
		parentIdx = selectedGroupData.area_idx;
	} else if (selectedGroupData.type === 'org' || selectedGroupData.type === 'unassigned') {
		parentIdx = null; // 최상위 레벨
	}

	$.ajax({
		url: window.groupSettingPageData.baseUrl + 'group_setting/add_group',
		method: 'POST',
		data: {
			org_id: selectedOrgId,
			parent_idx: parentIdx
		},
		dataType: 'json',
		success: function(response) {
			const toastType = response.success ? 'success' : 'error';
			showToast(response.message, toastType);
			if (response.success && response.new_area_idx) {
				// 새로 생성된 그룹을 선택하도록 트리 새로고침
				loadGroupTreeAndSelectNode(response.new_area_idx);
			} else if (response.success) {
				// new_area_idx가 없는 경우 기본 트리 새로고침
				loadGroupTree();
			}
		},
		error: function(xhr, status, error) {
			console.error('그룹 생성 실패:', error);
			showToast('그룹 생성에 실패했습니다.', 'error');
		}
	});
}

/**
 * 그룹명변경 처리
 */
function handleRenameGroup() {
	if (!selectedGroupData || selectedGroupData.type !== 'area') {
		showToast('변경할 그룹을 선택해주세요.', 'warning');
		return;
	}

	$('#newGroupName').val(selectedGroupData.area_name);
	$('#renameGroupModal').modal('show');

	$('#renameGroupModal').on('shown.bs.modal', function() {
		$('#newGroupName').select();
	});
}

/**
 * 그룹삭제 처리
 */
function handleDeleteGroup() {
	if (!selectedGroupData || selectedGroupData.type !== 'area') {
		showToast('삭제할 그룹을 선택해주세요.', 'warning');
		return;
	}

	const memberCount = selectedGroupData.member_count || 0;
	const groupName = selectedGroupData.area_name;

	let message = '';
	if (memberCount > 0) {
		message = `'${groupName}' 그룹을 삭제하시겠습니까?\n${memberCount}명의 회원이 포함되어 있습니다. 삭제 시 미분류 폴더로 이동됩니다.`;
	} else {
		message = `'${groupName}' 그룹을 삭제하시겠습니까?`;
	}

	$('#deleteGroupMessage').text(message);
	$('#deleteGroupModal').modal('show');
}

/**
 * 그룹이동 처리
 */
function handleMoveGroup() {
	if (!selectedGroupData || selectedGroupData.type !== 'area') {
		showToast('이동할 그룹을 선택해주세요.', 'warning');
		return;
	}

	const groupName = selectedGroupData.area_name;
	$('#moveGroupMessage').text(`'${groupName}' 그룹을 다른 위치로 이동하시겠습니까?`);

	loadMoveTargetGroups();
}

/**
 * 그룹이동(최상위) 처리
 */
function handleMoveGroupToTop() {
	if (!selectedGroupData || selectedGroupData.type !== 'area') {
		showToast('이동할 그룹을 선택해주세요.', 'warning');
		return;
	}

	const currentAreaIdx = selectedGroupData.area_idx;

	$.ajax({
		url: window.groupSettingPageData.baseUrl + 'group_setting/move_group_to_top',
		method: 'POST',
		data: {
			area_idx: selectedGroupData.area_idx,
			org_id: selectedOrgId
		},
		dataType: 'json',
		success: function(response) {
			const toastType = response.success ? 'success' : 'error';
			showToast(response.message, toastType);
			if (response.success) {
				loadGroupTreeAndSelectNode(currentAreaIdx);
			}
		},
		error: function(xhr, status, error) {
			console.error('그룹 최상위 이동 실패:', error);
			showToast('그룹 이동에 실패했습니다.', 'error');
		}
	});
}

// ================================
// 실행 함수들 (모달에서 확인 버튼 클릭 시)
// ================================

/**
 * 실제 그룹명변경 실행
 */
function executeGroupRename() {
	const newGroupName = $('#newGroupName').val().trim();

	if (!newGroupName) {
		showToast('그룹명을 입력해주세요.', 'warning');
		$('#newGroupName').focus();
		return;
	}

	if (newGroupName === selectedGroupData.area_name) {
		showToast('기존 그룹명과 동일합니다.', 'warning');
		$('#newGroupName').focus();
		return;
	}

	const currentAreaIdx = selectedGroupData.area_idx;

	$('#confirmRenameGroupBtn').prop('disabled', true).text('변경 중...');

	$.ajax({
		url: window.groupSettingPageData.baseUrl + 'group_setting/rename_group',
		method: 'POST',
		data: {
			area_idx: selectedGroupData.area_idx,
			org_id: selectedOrgId,
			new_name: newGroupName
		},
		dataType: 'json',
		success: function(response) {
			const toastType = response.success ? 'success' : 'error';
			showToast(response.message, toastType);
			if (response.success) {
				$('#renameGroupModal').modal('hide');
				loadGroupTreeAndSelectNode(currentAreaIdx);
			}
		},
		error: function(xhr, status, error) {
			console.error('그룹명 변경 실패:', error);
			showToast('그룹명 변경에 실패했습니다.', 'error');
		},
		complete: function() {
			$('#confirmRenameGroupBtn').prop('disabled', false).text('저장');
		}
	});
}

/**
 * 실제 그룹삭제 실행
 */
function executeGroupDelete() {
	if (!selectedGroupData || selectedGroupData.type !== 'area') {
		return;
	}

	const deletedGroupParent = selectedGroupData.parent_idx;
	const deletedGroupOrgId = selectedGroupData.org_id;

	$('#confirmDeleteGroupBtn').prop('disabled', true).text('삭제 중...');

	$.ajax({
		url: window.groupSettingPageData.baseUrl + 'group_setting/delete_group',
		method: 'POST',
		data: {
			area_idx: selectedGroupData.area_idx,
			org_id: selectedOrgId
		},
		dataType: 'json',
		success: function(response) {
			const toastType = response.success ? 'success' : 'error';
			showToast(response.message, toastType);
			if (response.success) {
				$('#deleteGroupModal').modal('hide');
				selectedGroupData = null;
				loadGroupTreeAfterDelete(deletedGroupParent, deletedGroupOrgId);
			}
		},
		error: function(xhr, status, error) {
			console.error('그룹 삭제 실패:', error);
			showToast('그룹 삭제에 실패했습니다.', 'error');
		},
		complete: function() {
			$('#confirmDeleteGroupBtn').prop('disabled', false).text('삭제');
		}
	});
}

/**
 * 실제 그룹이동 실행
 */
function executeGroupMove() {
	const targetParentIdx = $('#moveToGroupIdx').val() || null;
	const currentAreaIdx = selectedGroupData.area_idx;

	$('#confirmMoveGroupBtn').prop('disabled', true).text('이동 중...');

	$.ajax({
		url: window.groupSettingPageData.baseUrl + 'group_setting/move_group',
		method: 'POST',
		data: {
			area_idx: selectedGroupData.area_idx,
			target_parent_idx: targetParentIdx,
			org_id: selectedOrgId
		},
		dataType: 'json',
		success: function(response) {
			const toastType = response.success ? 'success' : 'error';
			showToast(response.message, toastType);
			if (response.success) {
				$('#moveGroupModal').modal('hide');
				loadGroupTreeAndSelectNode(currentAreaIdx);
			}
		},
		error: function(xhr, status, error) {
			console.error('그룹 이동 실패:', error);
			showToast('그룹 이동에 실패했습니다.', 'error');
		},
		complete: function() {
			$('#confirmMoveGroupBtn').prop('disabled', false).text('이동');
		}
	});
}

// ================================
// 헬퍼 함수들
// ================================

/**
 * 이동 대상 그룹 목록 로드
 */
function loadMoveTargetGroups() {
	$.ajax({
		url: window.groupSettingPageData.baseUrl + 'group_setting/get_move_target_groups',
		method: 'POST',
		data: {
			org_id: selectedOrgId,
			current_area_idx: selectedGroupData.area_idx
		},
		dataType: 'json',
		success: function(response) {
			if (response.success) {
				const select = $('#moveToGroupIdx');
				select.html('<option value="">최상위로 이동</option>');

				response.data.forEach(function(group) {
					select.append(`<option value="${group.area_idx}">${group.area_name}</option>`);
				});

				$('#moveGroupModal').modal('show');
			} else {
				showToast(response.message || '이동 대상 목록을 불러오는데 실패했습니다.', 'error');
			}
		},
		error: function(xhr, status, error) {
			console.error('이동 대상 목록 로드 실패:', error);
			showToast('이동 대상 목록을 불러오는데 실패했습니다.', 'error');
		}
	});
}

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

