'use strict';

/**
 * 파일 위치: assets/js/mng_member.js
 * 역할: 마스터 회원관리 화면의 메인 JavaScript 파일
 */

(function() {
	// 전역 변수
	let memberGrid = null;
	let treeInstance = null;
	let splitInstance = null;
	let selectedNodeType = null;
	let selectedNodeId = null;
	let selectedNodeName = '';
	let memberOffcanvas = null;
	let checkedMemberIds = new Set();

	// 상태 목록 정의
	const STATUS_LIST = [
		{ value: 'enlisted', label: '입소' },
		{ value: 'assigned', label: '자대' },
		{ value: 'settled', label: '정착' },
		{ value: 'nurturing', label: '양육' },
		{ value: 'dispatched', label: '파송' }
	];

	// DOM 준비 완료 시 초기화
	$(document).ready(function() {
		initializePage();
	});

	/**
	 * 페이지 전체 초기화
	 */
	function initializePage() {
		console.log('회원관리 페이지 초기화 시작');

		// Offcanvas 초기화
		const offcanvasEl = document.getElementById('memberOffcanvas');
		if (offcanvasEl) {
			memberOffcanvas = new bootstrap.Offcanvas(offcanvasEl);
		}

		cleanupExistingInstances();
		initSplitJS();
		initFancytree();
		initParamQueryGrid();
		bindGlobalEvents();

		console.log('회원관리 페이지 초기화 완료');
	}

	/**
	 * 기존 인스턴스 정리
	 */
	function cleanupExistingInstances() {
		if (splitInstance) {
			try {
				splitInstance.destroy();
			} catch(e) {
				console.warn('Split 인스턴스 제거 실패:', e);
			}
			splitInstance = null;
		}

		$('.gutter, .gutter-horizontal, .gutter-vertical').remove();
		$('[class*="gutter"]').remove();

		if (treeInstance) {
			try {
				$("#categoryTree").fancytree("destroy");
			} catch(e) {
				console.warn('Fancytree 인스턴스 제거 실패:', e);
			}
			treeInstance = null;
		}

		if (memberGrid) {
			try {
				memberGrid.pqGrid("destroy");
			} catch(e) {
				console.warn('Grid 인스턴스 제거 실패:', e);
			}
			memberGrid = null;
		}

		checkedMemberIds.clear();
	}

	/**
	 * Split.js 초기화
	 */
	function initSplitJS() {
		setTimeout(function() {
			try {
				const savedSizes = loadSplitSizes();
				const initialSizes = savedSizes || [20, 80];

				splitInstance = Split(['#left-pane', '#right-pane'], {
					sizes: initialSizes,
					minSize: [200, 400],
					gutterSize: 8,
					cursor: 'col-resize',
					direction: 'horizontal',
					onDragEnd: function(sizes) {
						saveSplitSizes(sizes);

						if (memberGrid) {
							setTimeout(function() {
								try {
									memberGrid.pqGrid("refresh");
								} catch(e) {
									console.warn('그리드 리프레시 실패:', e);
								}
							}, 100);
						}
					}
				});
				console.log('Split.js 초기화 완료', initialSizes);
			} catch(error) {
				console.error('Split.js 초기화 실패:', error);
			}
		}, 200);
	}

	/**
	 * Split.js 크기를 localStorage에 저장
	 */
	function saveSplitSizes(sizes) {
		try {
			localStorage.setItem('mng_member_split_sizes', JSON.stringify(sizes));
		} catch (error) {
			console.error('Split 크기 저장 실패:', error);
		}
	}

	/**
	 * localStorage에서 Split.js 크기 불러오기
	 */
	function loadSplitSizes() {
		try {
			const savedSizes = localStorage.getItem('mng_member_split_sizes');
			if (savedSizes) {
				return JSON.parse(savedSizes);
			}
		} catch (error) {
			console.error('Split 크기 로드 실패:', error);
		}
		return null;
	}

	/**
	 * Fancytree 초기화
	 */
	function initFancytree() {
		console.log('Fancytree 초기화 시작');
		showTreeSpinner();

		// 카테고리+조직 트리와 전체 회원수를 별도로 조회
		Promise.all([
			$.get('/mng/mng_member/get_category_org_tree'),
			$.get('/mng/mng_member/get_total_member_count')
		]).then(function(results) {
			const treeResponse = results[0];
			const totalCountResponse = results[1];

			console.log('트리 응답:', treeResponse);
			console.log('전체 카운트 응답:', totalCountResponse);

			try {
				// 미분류 노드를 별도로 추출
				const uncategorizedNode = treeResponse.find(node => node.data && node.data.type === 'uncategorized');
				const categoryNodes = treeResponse.filter(node => !node.data || node.data.type !== 'uncategorized');

				// 전체 회원 수
				const totalMemberCount = totalCountResponse.total_count || 0;
				console.log('총 회원 수:', totalMemberCount);

				// 트리 데이터 구성
				const treeData = [
					{
						key: 'all',
						title: `전체 (${totalMemberCount}명)`,
						folder: true,
						expanded: true,
						data: {
							type: 'all',
							member_count: totalMemberCount
						},
						children: categoryNodes
					}
				];

				// 미분류 노드 추가
				if (uncategorizedNode) {
					treeData.push(uncategorizedNode);
					console.log('미분류 노드 추가:', uncategorizedNode.title);
				}

				treeInstance = $("#categoryTree").fancytree({
					source: treeData,
					activate: function(event, data) {
						handleTreeNodeActivate(data.node);
					},
					selectMode: 1
				});

				hideTreeSpinner();

				// 저장된 트리 노드 복원 시도
				const savedTreeState = loadSelectedTreeNode();
				const tree = $.ui.fancytree.getTree('#categoryTree');

				if (savedTreeState && restoreSelectedTreeNode(savedTreeState)) {
					console.log('저장된 트리 노드 복원 성공');
				} else {
					// 복원 실패 시 전체 노드 선택
					const firstNode = tree.getNodeByKey('all');
					if (firstNode) {
						firstNode.setActive();
					}
				}

				console.log('Fancytree 초기화 완료');
			} catch(error) {
				hideTreeSpinner();
				console.error('Fancytree 데이터 처리 실패:', error);
				showToast('카테고리 트리 초기화에 실패했습니다', 'error');
			}
		}).catch(function(error) {
			hideTreeSpinner();
			console.error('카테고리 트리 로드 실패:', error);
			showToast('카테고리 목록을 불러올 수 없습니다', 'error');
		});
	}

	/**
	 * 선택된 트리 노드 상태를 localStorage에 저장
	 */
	function saveSelectedTreeNode(nodeData) {
		try {
			const treeState = {
				type: nodeData.type,
				id: nodeData.type === 'category' ? nodeData.category_idx : (nodeData.type === 'org' ? nodeData.org_id : null),
				name: nodeData.type === 'org' ? nodeData.org_name : (nodeData.category_name || ''),
				timestamp: Date.now()
			};

			localStorage.setItem('mng_member_selected_tree_node', JSON.stringify(treeState));
			console.log('트리 선택 상태 저장:', treeState);
		} catch (error) {
			console.error('트리 상태 저장 실패:', error);
		}
	}

	/**
	 * localStorage에서 트리 선택 상태 불러오기
	 */
	function loadSelectedTreeNode() {
		try {
			const savedState = localStorage.getItem('mng_member_selected_tree_node');

			if (savedState) {
				const treeState = JSON.parse(savedState);

				// 7일 이내의 데이터만 복원
				const sevenDaysAgo = Date.now() - (7 * 24 * 60 * 60 * 1000);
				if (treeState.timestamp < sevenDaysAgo) {
					localStorage.removeItem('mng_member_selected_tree_node');
					return null;
				}

				console.log('저장된 트리 상태 로드:', treeState);
				return treeState;
			}
		} catch (error) {
			console.error('트리 상태 로드 실패:', error);
		}

		return null;
	}

	/**
	 * 저장된 트리 노드 복원
	 */
	function restoreSelectedTreeNode(treeState) {
		if (!treeState) {
			return false;
		}

		const tree = $.ui.fancytree.getTree('#categoryTree');
		if (!tree) {
			return false;
		}

		let nodeToActivate = null;

		if (treeState.type === 'uncategorized') {
			nodeToActivate = tree.getNodeByKey('uncategorized');
		} else if (treeState.type === 'all') {
			nodeToActivate = tree.getNodeByKey('all');
		} else if (treeState.type === 'category' && treeState.id) {
			nodeToActivate = tree.getNodeByKey('category_' + treeState.id);
		} else if (treeState.type === 'org' && treeState.id) {
			nodeToActivate = tree.getNodeByKey('org_' + treeState.id);
		}

		if (nodeToActivate) {
			nodeToActivate.setActive();
			console.log('트리 노드 복원:', nodeToActivate.title);
			return true;
		}

		return false;
	}

	/**
	 * 트리 노드 활성화 처리
	 */
	function handleTreeNodeActivate(node) {
		const nodeData = node.data || {};
		const nodeType = nodeData.type;

		selectedNodeType = nodeType;

		if (nodeType === 'all') {
			selectedNodeId = null;
			selectedNodeName = '전체';
		} else if (nodeType === 'category') {
			selectedNodeId = nodeData.category_idx;
			selectedNodeName = nodeData.category_name || '';
		} else if (nodeType === 'org') {
			selectedNodeId = nodeData.org_id;
			selectedNodeName = nodeData.org_name || '';
		} else if (nodeType === 'uncategorized') {
			selectedNodeId = null;
			selectedNodeName = '미분류';
		}

		// 트리 선택 상태 저장
		saveSelectedTreeNode(nodeData);

		updateSelectedTitle();
		loadMemberList();
	}

	/**
	 * 선택된 제목 업데이트
	 */
	function updateSelectedTitle() {
		let iconClass = 'bi-people';
		if (selectedNodeType === 'org') {
			iconClass = 'bi-building';
		} else if (selectedNodeType === 'category') {
			iconClass = 'bi-folder';
		}

		$('#selectedNodeName').html(`<i class="bi ${iconClass}"></i> ${selectedNodeName}`);
	}

	/**
	 * 파일 위치: assets/js/mng_member.js
	 * 역할: ParamQuery Grid 초기화 (체크박스 선택 기능 포함)
	 */
	function initParamQueryGrid() {
		showGridSpinner();

		try {
			const colModel = createColumnModel();

			memberGrid = $("#memberGrid").pqGrid({
				width: "100%",
				height: "100%",
				dataModel: { data: [] },
				colModel: colModel,
				freezeCols: 4,
				numberCell: { show: false },
				hoverMode: 'row',
				selectionModel: { type: 'cell', mode: 'single' },
				resizable: true,
				wrap: false,
				hwrap: false,
				strNoRows: '회원 정보가 없습니다',
				cellClick: function(event, ui) {
					handleCellClick(event, ui);
				},
				complete: function() {
					setTimeout(function() {
						bindCheckboxEvents();
						updateCheckboxStates();
					}, 100);
				}
			});

			hideGridSpinner();
			console.log('ParamQuery Grid 초기화 완료');

		} catch(error) {
			hideGridSpinner();
			console.error('Grid 초기화 실패:', error);
			showToast('그리드 초기화에 실패했습니다', 'error');
		}
	}

	/**
	 * 파일 위치: assets/js/mng_member.js
	 * 역할: 그리드 컬럼 모델 생성
	 * 순서: 체크박스, 상태, 사진, 이름, 소속조직, 카테고리, 연락처, 직위/직분, 직책, 성별, 생년월일, 주소, 상세주소, 등록일, 수정일
	 */
	function createColumnModel() {
		return [
			{
				title: '<input type="checkbox" id="selectAllMembers" />',
				dataIndx: "checkbox",
				width: 40,
				align: "center",
				resizable: false,
				sortable: false,
				editable: false,
				menuIcon: false,
				frozen: true,
				render: function(ui) {
					const memberIdx = ui.rowData.member_idx;
					const isChecked = checkedMemberIds.has(memberIdx);
					return '<input type="checkbox" class="member-checkbox" data-member-idx="' + memberIdx + '" ' + (isChecked ? 'checked' : '') + ' />';
				}
			},
			{
				dataIndx: 'member_status',
				title: '상태',
				width: 70,
				align: 'center',
				frozen: true,
				render: function(ui) {
					const status = ui.cellData;
					if (!status) {
						return '<span class="badge bg-light text-dark">-</span>';
					}
					const statusObj = STATUS_LIST.find(s => s.value === status);
					if (statusObj) {
						let badgeClass = 'bg-secondary';
						switch(status) {
							case 'enlisted': badgeClass = 'bg-info'; break;
							case 'assigned': badgeClass = 'bg-primary'; break;
							case 'settled': badgeClass = 'bg-success'; break;
							case 'nurturing': badgeClass = 'bg-warning text-dark'; break;
							case 'dispatched': badgeClass = 'bg-danger'; break;
						}
						return '<span class="badge ' + badgeClass + '">' + statusObj.label + '</span>';
					}
					return '<span class="badge bg-light text-dark">' + status + '</span>';
				},
				filter: {
					type: 'select',
					options: [
						{ '': '전체' },
						{ 'enlisted': '입소' },
						{ 'assigned': '자대' },
						{ 'settled': '정착' },
						{ 'nurturing': '양육' },
						{ 'dispatched': '파송' }
					]
				}
			},
			{
				dataIndx: 'photo_url',
				title: '사진',
				width: 60,
				align: 'center',
				sortable: false,
				editable: false,
				frozen: true,
				render: function(ui) {
					const photoUrl = ui.cellData || '/assets/images/photo_no.png';
					return '<img src="' + photoUrl + '" class="rounded-circle" width="40" height="40" style="object-fit: cover;" onerror="this.src=\'/assets/images/photo_no.png\'">';
				}
			},
			{
				dataIndx: 'member_name',
				title: '이름',
				width: 100,
				filter: { type: 'textbox', condition: 'contain' }
			},
			{
				dataIndx: 'org_name',
				title: '소속조직',
				width: 150,
				filter: { type: 'textbox', condition: 'contain' },
				render: function(ui) {
					if (ui.cellData && ui.rowData.org_id) {
						return '<span class="badge bg-primary org-dashboard-link" data-org-id="' + ui.rowData.org_id + '" data-org-name="' + ui.cellData + '" style="cursor: pointer;" title="' + ui.cellData + ' 대시보드 바로가기">' +
							ui.cellData + ' <i class="bi bi-box-arrow-up-right"></i></span>';
					}
					return '<span class="badge bg-light text-dark">미지정</span>';
				}
			},
			{
				dataIndx: 'category_name',
				title: '카테고리',
				width: 120,
				filter: { type: 'textbox', condition: 'contain' },
				render: function(ui) {
					if (ui.cellData) {
						return '<span class="badge bg-secondary">' + ui.cellData + '</span>';
					}
					return '<span class="badge bg-light text-dark">미분류</span>';
				}
			},
			{
				dataIndx: 'member_phone',
				title: '연락처',
				width: 130,
				filter: { type: 'textbox', condition: 'contain' }
			},
			{
				dataIndx: 'position_name',
				title: '직위/직분',
				width: 100,
				filter: { type: 'textbox', condition: 'contain' }
			},
			{
				dataIndx: 'duty_name',
				title: '직책',
				width: 100,
				filter: { type: 'textbox', condition: 'contain' }
			},
			{
				dataIndx: 'member_sex',
				title: '성별',
				width: 60,
				align: 'center',
				render: function(ui) {
					if (ui.cellData === 'male') return '남';
					if (ui.cellData === 'female') return '여';
					return '-';
				},
				filter: {
					type: 'select',
					options: [
						{ '': '전체' },
						{ 'male': '남' },
						{ 'female': '여' }
					]
				}
			},
			{
				dataIndx: 'member_birth',
				title: '생년월일',
				width: 100,
				align: 'center'
			},
			{
				dataIndx: 'member_address',
				title: '주소',
				width: 200,
				filter: { type: 'textbox', condition: 'contain' }
			},
			{
				dataIndx: 'member_address_detail',
				title: '상세주소',
				width: 150,
				filter: { type: 'textbox', condition: 'contain' }
			},
			{
				dataIndx: 'regi_date',
				title: '등록일',
				width: 100,
				align: 'center',
				render: function(ui) {
					if (ui.cellData) {
						return ui.cellData.substring(0, 10);
					}
					return '-';
				}
			},
			{
				dataIndx: 'modi_date',
				title: '수정일',
				width: 100,
				align: 'center',
				render: function(ui) {
					if (ui.cellData) {
						return ui.cellData.substring(0, 10);
					}
					return '-';
				}
			}
		];
	}

	/**
	 * 셀 클릭 처리
	 */
	function handleCellClick(event, ui) {
		const target = $(event.originalEvent.target);

		// 체크박스 컬럼 클릭
		if (ui.colIndx === 0) {
			if (!target.hasClass('member-checkbox')) {
				const memberIdx = ui.rowData.member_idx;
				const checkbox = $('.member-checkbox[data-member-idx="' + memberIdx + '"]');
				if (checkbox.length > 0) {
					checkbox.prop('checked', !checkbox.prop('checked')).trigger('change');
				}
			}
			return;
		}

		// 조직 대시보드 링크 클릭 처리
		const orgLink = target.closest('.org-dashboard-link');
		if (orgLink.length > 0) {
			const orgId = orgLink.data('org-id');
			const orgName = orgLink.data('org-name');
			if (orgId) {
				goToOrgDashboard(orgId, orgName);
			}
			return;
		}
	}


	/**
	 * 파일 위치: assets/js/mng_member.js
	 * 역할: 조직 대시보드로 이동 (새 탭) - 조직관리 바로가기와 동일한 로직
	 */
	function goToOrgDashboard(orgId, orgName) {
		if (!orgId) {
			showToast('조직 정보를 찾을 수 없습니다.', 'warning');
			return;
		}

		// 로컬스토리지에 조직 정보 저장
		try {
			localStorage.setItem('lastSelectedOrgId', orgId);
			localStorage.setItem('lastSelectedOrgName', orgName || '');
		} catch (e) {
			console.warn('로컬스토리지 저장 실패:', e);
		}

		// 서버에 조직 전환 요청 후 대시보드로 이동
		$.ajax({
			url: '/login/set_default_org',
			type: 'POST',
			data: { org_id: orgId },
			dataType: 'json',
			success: function(response) {
				if (response.success) {
					// 새 탭에서 대시보드 열기
					window.open('/dashboard', '_blank');
					showToast((orgName || '조직') + '(으)로 전환되었습니다', 'success');
				} else {
					showToast(response.message || '조직 전환에 실패했습니다', 'error');
				}
			},
			error: function() {
				showToast('조직 전환 중 오류가 발생했습니다', 'error');
			}
		});
	}

	/**
	 * 체크박스 이벤트 바인딩
	 */
	function bindCheckboxEvents() {
		// 기존 이벤트 제거
		$(document).off('change', '#selectAllMembers');
		$(document).off('change', '.member-checkbox');

		// 전체 선택 체크박스
		$(document).on('change', '#selectAllMembers', function(e) {
			e.stopPropagation();
			const isChecked = $(this).is(':checked');
			const wasIndeterminate = $(this).prop('indeterminate');

			if (wasIndeterminate) {
				$(this).prop('indeterminate', false);
				$(this).prop('checked', true);
			}

			$('.member-checkbox').each(function() {
				const memberIdx = parseInt($(this).data('member-idx'));
				const shouldCheck = wasIndeterminate || isChecked;
				$(this).prop('checked', shouldCheck);

				if (shouldCheck) {
					checkedMemberIds.add(memberIdx);
				} else {
					checkedMemberIds.delete(memberIdx);
				}
			});

			updateSelectedCount();
		});

		// 개별 체크박스
		$(document).on('change', '.member-checkbox', function(e) {
			e.stopPropagation();
			const memberIdx = parseInt($(this).data('member-idx'));
			const isChecked = $(this).is(':checked');

			if (isChecked) {
				checkedMemberIds.add(memberIdx);
			} else {
				checkedMemberIds.delete(memberIdx);
			}

			updateSelectAllCheckboxState();
			updateSelectedCount();
		});
	}

	/**
	 * 전체 선택 체크박스 상태 업데이트
	 */
	function updateSelectAllCheckboxState() {
		const totalCheckboxes = $('.member-checkbox').length;
		const checkedCount = checkedMemberIds.size;
		const selectAllCheckbox = $('#selectAllMembers');

		if (checkedCount === 0) {
			selectAllCheckbox.prop('checked', false);
			selectAllCheckbox.prop('indeterminate', false);
		} else if (checkedCount === totalCheckboxes) {
			selectAllCheckbox.prop('checked', true);
			selectAllCheckbox.prop('indeterminate', false);
		} else {
			selectAllCheckbox.prop('checked', false);
			selectAllCheckbox.prop('indeterminate', true);
		}
	}

	/**
	 * 체크박스 상태 업데이트
	 */
	function updateCheckboxStates() {
		updateSelectAllCheckboxState();
		updateSelectedCount();
	}

	/**
	 * 선택된 회원 수 업데이트
	 */
	function updateSelectedCount() {
		const count = checkedMemberIds.size;
		$('#selectedCount').text(count);

		// 버튼 상태 업데이트
		const isDisabled = count === 0;
		$('#btnStatusChange').prop('disabled', isDisabled);
	}

	/**
	 * 회원 목록 로드
	 */
	function loadMemberList() {
		showGridSpinner();
		checkedMemberIds.clear();

		const params = {
			type: selectedNodeType || 'all'
		};

		if (selectedNodeType === 'category' && selectedNodeId) {
			params.id = selectedNodeId;
		} else if (selectedNodeType === 'org' && selectedNodeId) {
			params.id = selectedNodeId;
		}

		$.ajax({
			url: '/mng/mng_member/get_member_list',
			type: 'GET',
			data: params,
			dataType: 'json',
			success: function(response) {
				hideGridSpinner();

				if (response.success) {
					const data = response.data || [];

					memberGrid.pqGrid("option", "dataModel.data", data);
					memberGrid.pqGrid("refreshDataAndView");

					$('#totalMemberCount').text(data.length);
					console.log('회원 목록 로드 완료:', data.length, '명');

					// 그리드 로드 완료 후 체크박스 이벤트 재바인딩
					setTimeout(function() {
						$('#selectAllMembers').prop('checked', false).prop('indeterminate', false);
						bindCheckboxEvents();
						updateSelectedCount();
					}, 200);
				} else {
					showToast(response.message || '회원 목록을 불러오는데 실패했습니다', 'error');
				}
			},
			error: function(xhr, status, error) {
				hideGridSpinner();
				console.error('회원 목록 로드 오류:', error);
				showToast('회원 목록을 불러오는데 실패했습니다', 'error');
			}
		});
	}

	/**
	 * 선택된 회원 목록 가져오기
	 */
	function getSelectedMembers() {
		const selectedMembers = [];

		if (!memberGrid) {
			return selectedMembers;
		}

		try {
			const gridData = memberGrid.pqGrid('option', 'dataModel.data');

			if (!gridData || !Array.isArray(gridData)) {
				return selectedMembers;
			}

			// 체크된 각 ID에 대해 그리드에서 해당 데이터 찾기
			checkedMemberIds.forEach(checkedMemberIdx => {
				const memberData = gridData.find(row => {
					const rowMemberIdx = parseInt(row.member_idx);
					return rowMemberIdx === checkedMemberIdx;
				});

				if (memberData) {
					selectedMembers.push(memberData);
				}
			});

		} catch (error) {
			console.error('getSelectedMembers 오류:', error);
		}

		return selectedMembers;
	}

	/**
	 * 상태 변경 버튼 클릭 핸들러
	 */
	function handleStatusChange() {
		const selectedMembers = getSelectedMembers();

		if (selectedMembers.length === 0) {
			showToast('상태를 변경할 회원을 선택해주세요.', 'warning');
			return;
		}

		// 상태 변경 모달 열기
		openStatusChangeModal(selectedMembers);
	}

	/**
	 * 상태 변경 모달 열기
	 */
	function openStatusChangeModal(selectedMembers) {
		// 선택된 회원 목록 표시
		const memberListHtml = selectedMembers.map(member => {
			const currentStatus = member.member_status ?
				(STATUS_LIST.find(s => s.value === member.member_status)?.label || member.member_status) : '없음';
			return '<li class="list-group-item d-flex justify-content-between align-items-center">' +
				'<div>' +
				'<strong>' + (member.member_name || '이름 없음') + '</strong>' +
				'<br><small class="text-muted">' + (member.org_name || '소속 없음') + '</small>' +
				'</div>' +
				'<span class="badge bg-secondary">' + currentStatus + '</span>' +
				'</li>';
		}).join('');

		$('#statusChangeMemberList').html('<ul class="list-group list-group-flush">' + memberListHtml + '</ul>');

		// 상태 선택 라디오 버튼 생성
		const statusOptionsHtml = STATUS_LIST.map(status => {
			return '<div class="form-check form-check-inline">' +
				'<input class="form-check-input" type="radio" name="newStatus" id="status_' + status.value + '" value="' + status.value + '">' +
				'<label class="form-check-label" for="status_' + status.value + '">' + status.label + '</label>' +
				'</div>';
		}).join('');

		$('#statusOptions').html(statusOptionsHtml);

		// 선택 개수 표시
		$('#statusChangeCount').text(selectedMembers.length);

		// 모달 열기
		$('#statusChangeModal').modal('show');
	}

	/**
	 * 상태 변경 실행
	 */
	function executeStatusChange() {
		const newStatus = $('input[name="newStatus"]:checked').val();

		if (!newStatus) {
			showToast('변경할 상태를 선택해주세요.', 'warning');
			return;
		}

		const memberIdxList = Array.from(checkedMemberIds);

		if (memberIdxList.length === 0) {
			showToast('상태를 변경할 회원이 없습니다.', 'warning');
			return;
		}

		// 버튼 비활성화
		$('#confirmStatusChangeBtn').prop('disabled', true);

		$.ajax({
			url: '/mng/mng_member/update_member_status',
			type: 'POST',
			data: {
				member_idx_list: memberIdxList,
				member_status: newStatus
			},
			dataType: 'json',
			success: function(response) {
				$('#confirmStatusChangeBtn').prop('disabled', false);
				$('#statusChangeModal').modal('hide');

				showToast(response.message, response.success ? 'success' : 'error');

				if (response.success) {
					// 회원 목록 새로고침
					loadMemberList();
				}
			},
			error: function() {
				$('#confirmStatusChangeBtn').prop('disabled', false);
				$('#statusChangeModal').modal('hide');
				showToast('상태 변경 중 오류가 발생했습니다', 'error');
			}
		});
	}



	/**
	 * 트리와 그리드 새로고침
	 */
	function refreshTreeAndGrid() {
		console.log('트리와 그리드 새로고침 시작');

		// 체크된 회원 ID 초기화
		checkedMemberIds.clear();

		// 현재 선택된 정보 임시 저장
		const currentSelectedType = selectedNodeType;
		const currentSelectedId = selectedNodeId;

		// 트리 새로고침
		showTreeSpinner();

		Promise.all([
			$.get('/mng/mng_member/get_category_org_tree'),
			$.get('/mng/mng_member/get_total_member_count')
		]).then(function(results) {
			const treeResponse = results[0];
			const totalCountResponse = results[1];

			try {
				// 기존 트리 인스턴스 제거
				if (treeInstance) {
					try {
						$("#categoryTree").fancytree("destroy");
					} catch(e) {
						console.warn('기존 Fancytree 제거 실패:', e);
					}
					treeInstance = null;
				}

				// 미분류 노드를 별도로 추출
				const uncategorizedNode = treeResponse.find(node => node.data && node.data.type === 'uncategorized');
				const categoryNodes = treeResponse.filter(node => !node.data || node.data.type !== 'uncategorized');

				const totalMemberCount = totalCountResponse.total_count || 0;

				// 트리 데이터 구성
				const treeData = [
					{
						key: 'all',
						title: `전체 (${totalMemberCount}명)`,
						folder: true,
						expanded: true,
						data: {
							type: 'all',
							member_count: totalMemberCount
						},
						children: categoryNodes
					}
				];

				if (uncategorizedNode) {
					treeData.push(uncategorizedNode);
				}

				// 새 트리 인스턴스 생성
				treeInstance = $("#categoryTree").fancytree({
					source: treeData,
					activate: function(event, data) {
						handleTreeNodeActivate(data.node);
					},
					selectMode: 1
				});

				hideTreeSpinner();

				// 이전에 선택되어 있던 노드 복원
				setTimeout(function() {
					const tree = $.ui.fancytree.getTree('#categoryTree');
					let nodeToActivate = null;

					if (currentSelectedType === 'uncategorized') {
						nodeToActivate = tree.getNodeByKey('uncategorized');
					} else if (currentSelectedType === 'all' || currentSelectedType === null) {
						nodeToActivate = tree.getNodeByKey('all');
					} else if (currentSelectedType === 'category') {
						nodeToActivate = tree.getNodeByKey('category_' + currentSelectedId);
					} else if (currentSelectedType === 'org') {
						nodeToActivate = tree.getNodeByKey('org_' + currentSelectedId);
					}

					if (nodeToActivate) {
						nodeToActivate.setActive();
						console.log('이전 선택 노드 복원:', nodeToActivate.title);
					} else {
						const allNode = tree.getNodeByKey('all');
						if (allNode) {
							allNode.setActive();
						}
					}
				}, 100);

				console.log('트리 새로고침 완료');

			} catch(error) {
				hideTreeSpinner();
				console.error('트리 새로고침 실패:', error);
				showToast('트리 새로고침 중 오류가 발생했습니다', 'error');
			}
		}).catch(function(error) {
			hideTreeSpinner();
			console.error('트리 데이터 로드 실패:', error);
			showToast('트리 데이터를 새로고침할 수 없습니다', 'error');

			// 실패 시 현재 그리드만이라도 새로고침
			loadMemberList();
		});
	}

	/**
	 * 파일 위치: assets/js/mng_member.js
	 * 역할: 글로벌 이벤트 바인딩 (상태변경 버튼 추가)
	 */
	function bindGlobalEvents() {
		// 새로고침 버튼
		$('#btnRefresh').on('click', function() {
			refreshTreeAndGrid();
		});

		// 상태 변경 버튼
		$('#btnStatusChange').on('click', function() {
			handleStatusChange();
		});

		// 상태 변경 확인 버튼
		$('#confirmStatusChangeBtn').on('click', function() {
			executeStatusChange();
		});
	}

	/**
	 * 스피너 관련 함수들
	 */
	function showTreeSpinner() {
		$('#treeSpinner').removeClass('d-none').addClass('d-flex');
	}

	function hideTreeSpinner() {
		$('#treeSpinner').removeClass('d-flex').addClass('d-none');
	}

	function showGridSpinner() {
		$('#gridSpinner').removeClass('d-none').addClass('d-flex');
	}

	function hideGridSpinner() {
		$('#gridSpinner').removeClass('d-flex').addClass('d-none');
	}

})();
