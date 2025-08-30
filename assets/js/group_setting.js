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
 * Fancytree 인스턴스 설정 - 회원관리와 동일한 스타일 적용
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
	$('#btnDeleteGroup').prop('disabled', true);
	$('#btnMoveGroup').prop('disabled', true);
	$('#btnMoveGroupToTop').prop('disabled', true);

	if (groupData.type === 'org') {
		// 조직 레벨: 그룹생성만 가능
		$('#btnAddGroup').prop('disabled', false);
	} else if (groupData.type === 'area') {
		// 그룹 레벨: 모든 버튼 활성화
		$('#btnAddGroup').prop('disabled', false);
		$('#btnDeleteGroup').prop('disabled', false);
		$('#btnMoveGroup').prop('disabled', false);
		$('#btnMoveGroupToTop').prop('disabled', false);
	} else if (groupData.type === 'unassigned') {
		// 미분류: 그룹생성만 가능
		$('#btnAddGroup').prop('disabled', false);
	}
}

/**
 * 이벤트 리스너 등록
 */
function registerEventListeners() {
	// 그룹생성 버튼
	$('#btnAddGroup').on('click', handleAddGroup);

	// 그룹삭제 버튼
	$('#btnDeleteGroup').on('click', handleDeleteGroup);

	// 그룹이동 버튼
	$('#btnMoveGroup').on('click', handleMoveGroup);

	// 그룹이동(최상위) 버튼
	$('#btnMoveGroupToTop').on('click', handleMoveGroupToTop);

	// 삭제 확인 버튼
	$('#confirmDeleteGroupBtn').on('click', executeGroupDelete);

	// 이동 확인 버튼
	$('#confirmMoveGroupBtn').on('click', executeGroupMove);
}

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
			if (response.success) {
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
 * 실제 그룹삭제 실행
 */
function executeGroupDelete() {
	if (!selectedGroupData || selectedGroupData.type !== 'area') {
		return;
	}

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
				loadGroupTree();
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
 * 그룹이동 처리
 */
function handleMoveGroup() {
	if (!selectedGroupData || selectedGroupData.type !== 'area') {
		showToast('이동할 그룹을 선택해주세요.', 'warning');
		return;
	}

	const groupName = selectedGroupData.area_name;
	$('#moveGroupMessage').text(`'${groupName}' 그룹을 다른 위치로 이동하시겠습니까?`);

	// 이동 대상 그룹 목록 로드
	loadMoveTargetGroups();
}

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
 * 실제 그룹이동 실행
 */
function executeGroupMove() {
	const targetParentIdx = $('#moveToGroupIdx').val() || null;

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
				loadGroupTree();
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

/**
 * 그룹이동(최상위) 처리
 */
function handleMoveGroupToTop() {
	if (!selectedGroupData || selectedGroupData.type !== 'area') {
		showToast('이동할 그룹을 선택해주세요.', 'warning');
		return;
	}

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
				loadGroupTree();
			}
		},
		error: function(xhr, status, error) {
			console.error('그룹 최상위 이동 실패:', error);
			showToast('그룹 이동에 실패했습니다.', 'error');
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

/**
 * Toast 메시지 표시
 */
function showToast(message, type = 'info') {
	const toastId = 'toast_' + Date.now();
	const iconClass = {
		'success': 'bi-check-circle-fill text-success',
		'error': 'bi-x-circle-fill text-danger',
		'warning': 'bi-exclamation-triangle-fill text-warning',
		'info': 'bi-info-circle-fill text-info'
	}[type] || 'bi-info-circle-fill text-info';

	const toastHtml = `
		<div class="toast align-items-center border-0" id="${toastId}" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="4000">
			<div class="d-flex">
				<div class="toast-body">
					<i class="${iconClass} me-2"></i>${message}
				</div>
				<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
			</div>
		</div>
	`;

	$('#toastContainer').append(toastHtml);
	$(`#${toastId}`).toast('show');

	// Toast가 숨겨진 후 DOM에서 제거
	$(`#${toastId}`).on('hidden.bs.toast', function() {
		$(this).remove();
	});
}
