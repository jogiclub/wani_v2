'use strict';

/**
 
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
	let existingStatusTags = [];


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
	 * 선택된 트리 노드 관리tag를 localStorage에 저장
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
			console.log('트리 선택 관리tag 저장:', treeState);
		} catch (error) {
			console.error('트리 관리tag 저장 실패:', error);
		}
	}

	/**
	 * localStorage에서 트리 선택 관리tag 불러오기
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

				console.log('저장된 트리 관리tag 로드:', treeState);
				return treeState;
			}
		} catch (error) {
			console.error('트리 관리tag 로드 실패:', error);
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

		// 트리 선택 관리tag 저장
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
	 
	 * 역할: 그리드 컬럼 모델 생성
	 * 순서: 체크박스, 관리tag, 사진, 이름, 소속조직, 카테고리, 연락처, 직위/직분, 직책, 성별, 생년월일, 주소, 상세주소, 등록일, 수정일
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
				title: '관리tag',
				width: 150,
				align: 'center',
				frozen: true,
				render: function(ui) {
					const status = ui.cellData;
					if (!status) {
						return '<span class="badge bg-light text-dark">-</span>';
					}

					// 쉼표로 구분된 태그 파싱
					const tags = status.split(',').map(function(tag) {
						return tag.trim();
					}).filter(function(tag) {
						return tag !== '';
					});

					if (tags.length === 0) {
						return '<span class="badge bg-light text-dark">-</span>';
					}

					// 태그별 색상 지정 (해시 기반)
					const badgeHtml = tags.map(function(tag) {
						const colorStyle = getTagColorClass(tag);
						return '<span class="badge me-1" style="' + colorStyle + '">' + tag + '</span>';
					}).join('');

					return badgeHtml;
				},
				filter: { type: 'textbox', condition: 'contain' }
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
	 * 역할: 셀 클릭 처리 - 관리tag 클릭 제거, 조직 링크만 처리
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
	 * 전체 선택 체크박스 관리tag 업데이트
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
	 * 체크박스 관리tag 업데이트
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

		// 버튼 관리tag 업데이트
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
	 * 관리tag 변경 버튼 클릭 핸들러
	 */
	function handleStatusChange() {
		const selectedMembers = getSelectedMembers();

		if (selectedMembers.length === 0) {
			showToast('관리tag를 변경할 회원을 선택해주세요.', 'warning');
			return;
		}

		// 관리tag 변경 모달 열기
		openStatusChangeModal(selectedMembers);
	}

	/**
	 * 파일 위치: assets/js/mng_member.js
	 * 역할: 관리tag 변경 모달 열기 - 단일/일괄 모드에 따라 다른 UI 표시
	 */
	function openStatusChangeModal(selectedMembers) {
		const count = selectedMembers.length;
		const isSingleMode = count === 1;

		// 선택된 회원 목록 표시
		let memberListHtml = '';

		if (isSingleMode) {
			// 단일 회원: 상세 정보 표시
			const member = selectedMembers[0];
			memberListHtml = '<div class="p-2">' +
				'<strong>' + (member.member_name || '이름 없음') + '</strong>' +
				'<br><small class="text-muted">' + (member.org_name || '소속 없음') + '</small>' +
				'</div>';
		} else {
			// 복수 회원: 요약 정보 표시
			const firstMember = selectedMembers[0];
			const otherCount = count - 1;

			// 고유 조직 목록 추출
			const uniqueOrgs = [...new Set(selectedMembers.map(m => m.org_name).filter(Boolean))];
			const orgText = uniqueOrgs.length === 1
				? uniqueOrgs[0]
				: uniqueOrgs[0] + ' 외 ' + (uniqueOrgs.length - 1) + '개';

			memberListHtml = '<div class="p-2">' +
				'<strong>' + (firstMember.member_name || '이름 없음') + '</strong> 외 ' + otherCount + '명' +
				'<br><small class="text-muted">' + orgText + '</small>' +
				'</div>';
		}

		$('#statusChangeMemberList').html(memberListHtml);
		$('#statusChangeCount').text(count);

		// 모드에 따라 섹션 표시/숨김
		if (isSingleMode) {
			$('#singleModeSection').removeClass('d-none');
			$('#bulkModeSection').addClass('d-none');

			// 현재 태그 파싱
			const member = selectedMembers[0];
			let currentTags = [];
			if (member.member_status) {
				currentTags = member.member_status.split(',').map(function(tag) {
					return tag.trim();
				}).filter(function(tag) {
					return tag !== '';
				});
			}

			// 기존 태그 목록 로드 후 Select2 초기화
			loadExistingStatusTags().then(function() {
				initializeSingleModeSelect(currentTags);
			});
		} else {
			$('#singleModeSection').addClass('d-none');
			$('#bulkModeSection').removeClass('d-none');

			// 선택된 회원들의 모든 태그 수집 (삭제용)
			const allCurrentTags = new Set();
			selectedMembers.forEach(function(member) {
				if (member.member_status) {
					member.member_status.split(',').forEach(function(tag) {
						const trimmed = tag.trim();
						if (trimmed) allCurrentTags.add(trimmed);
					});
				}
			});

			// 기존 태그 목록 로드 후 Select2 초기화
			loadExistingStatusTags().then(function() {
				initializeBulkModeSelect(Array.from(allCurrentTags));
			});
		}

		// 모드 저장
		$('#statusChangeModal').data('mode', isSingleMode ? 'single' : 'bulk');
		$('#statusChangeModal').modal('show');
	}


	/**
	 * 파일 위치: assets/js/mng_member.js
	 * 역할: 상태 변경 실행 - 대량 처리를 위해 JSON 문자열로 전송
	 */
	function executeStatusChange() {
		const mode = $('#statusChangeModal').data('mode');
		const memberIdxList = Array.from(checkedMemberIds);

		if (memberIdxList.length === 0) {
			showToast('상태를 변경할 회원이 없습니다.', 'warning');
			return;
		}

		let requestData = {
			member_idx_list: JSON.stringify(memberIdxList),
			mode: mode
		};

		if (mode === 'single') {
			// 단일 모드: 태그 전체 교체
			const selectedTags = $('#statusTagSelect').val() || [];
			requestData.member_status = selectedTags.join(',');
		} else {
			// 일괄 모드: 태그 추가/삭제
			const addTags = $('#addTagSelect').val() || [];
			const removeTags = $('#removeTagSelect').val() || [];

			if (addTags.length === 0 && removeTags.length === 0) {
				showToast('추가하거나 삭제할 태그를 선택해주세요.', 'warning');
				return;
			}

			requestData.add_tags = JSON.stringify(addTags);
			requestData.remove_tags = JSON.stringify(removeTags);
		}

		// 버튼 비활성화 및 로딩 표시
		const $btn = $('#confirmStatusChangeBtn');
		const originalText = $btn.text();
		$btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>처리 중...');

		$.ajax({
			url: '/mng/mng_member/update_member_status',
			type: 'POST',
			data: requestData,
			dataType: 'json',
			timeout: 300000, // 5분 타임아웃
			success: function(response) {
				$btn.prop('disabled', false).text(originalText);
				$('#statusChangeModal').modal('hide');

				showToast(response.message, response.success ? 'success' : 'error');

				if (response.success) {
					loadMemberList();
				}
			},
			error: function(xhr, status, error) {
				$btn.prop('disabled', false).text(originalText);
				$('#statusChangeModal').modal('hide');

				if (status === 'timeout') {
					showToast('처리 시간이 초과되었습니다. 잠시 후 다시 시도해주세요.', 'error');
				} else {
					showToast('상태 변경 중 오류가 발생했습니다', 'error');
				}
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
	 
	 * 역할: 글로벌 이벤트 바인딩 (관리tag변경 버튼 추가)
	 */
	function bindGlobalEvents() {
		// 새로고침 버튼
		$('#btnRefresh').on('click', function() {
			refreshTreeAndGrid();
		});

		// 관리tag 변경 버튼
		$('#btnStatusChange').on('click', function() {
			handleStatusChange();
		});

		// 관리tag 변경 확인 버튼
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

	/**
	 
	 * 역할: 태그 문자열 기반으로 일관된 색상 클래스 반환
	 */
	function getTagColorClass(tag) {
		const colors = [
			'bg-primary',
			'bg-success',
			'bg-info',
			'bg-warning text-dark',
			'bg-danger',
			'bg-secondary',
			'bg-dark'
		];

		// 문자열 해시 계산
		let hash = 0;
		for (let i = 0; i < tag.length; i++) {
			hash = tag.charCodeAt(i) + ((hash << 5) - hash);
		}

		const index = Math.abs(hash) % colors.length;
		return colors[index];
	}


	/**
	 * 역할: 기존에 사용된 관리tag 태그 목록 로드
	 */
	function loadExistingStatusTags() {
		return new Promise(function(resolve, reject) {
			$.ajax({
				url: '/mng/mng_member/get_existing_status_tags',
				type: 'GET',
				dataType: 'json',
				success: function(response) {
					if (response && response.success && response.data) {
						existingStatusTags = response.data;
					}
					resolve();
				},
				error: function() {
					console.warn('기존 관리tag 태그 목록 로드 실패');
					resolve();
				}
			});
		});
	}

	/**
	 * 파일 위치: assets/js/mng_member.js
	 * 역할: 관리tag 태그 Select2 초기화
	 */
	function initializeStatusTagSelect(currentTags) {
		// 기존 Select2 인스턴스 제거
		if ($('#statusTagSelect').hasClass('select2-hidden-accessible')) {
			$('#statusTagSelect').select2('destroy');
		}

		// 옵션 초기화
		$('#statusTagSelect').empty();

		// 기존 태그 목록 추가
		existingStatusTags.forEach(function(tag) {
			$('#statusTagSelect').append('<option value="' + tag + '">' + tag + '</option>');
		});

		// Select2 초기화
		$('#statusTagSelect').select2({
			theme: 'bootstrap-5',
			width: '100%',
			placeholder: '관리tag 태그를 선택하거나 입력하세요',
			allowClear: true,
			tags: true,
			tokenSeparators: [','],
			dropdownParent: $('#statusChangeModal'),
			createTag: function(params) {
				const term = $.trim(params.term);
				if (term === '' || term.length < 1) {
					return null;
				}
				return {
					id: term,
					text: term,
					newTag: true
				};
			},
			templateResult: function(tag) {
				if (tag.newTag) {
					return $('<span class="text-primary"><i class="bi bi-plus-circle me-1"></i>' + tag.text + ' (새 태그)</span>');
				}
				return tag.text;
			},
			templateSelection: function(tag) {
				return tag.text;
			}
		});

		// 현재 태그 설정
		if (currentTags && currentTags.length > 0) {
			currentTags.forEach(function(tag) {
				// 옵션이 없으면 추가
				if ($('#statusTagSelect option[value="' + tag + '"]').length === 0) {
					$('#statusTagSelect').append('<option value="' + tag + '" selected>' + tag + '</option>');
				}
			});
			$('#statusTagSelect').val(currentTags).trigger('change');
		}
	}

	/**
	 * 파일 위치: assets/js/mng_member.js
	 * 역할: 개별 회원 관리tag 수정 모달 열기
	 */
	function openSingleMemberStatusModal(memberIdx) {
		// 그리드에서 회원 정보 찾기
		const gridData = memberGrid.pqGrid('option', 'dataModel.data');
		const memberData = gridData.find(function(row) {
			return parseInt(row.member_idx) === parseInt(memberIdx);
		});

		if (!memberData) {
			showToast('회원 정보를 찾을 수 없습니다.', 'error');
			return;
		}

		// 선택된 회원 목록 표시 (1명)
		const memberListHtml = '<ul class="list-group list-group-flush">' +
			'<li class="list-group-item d-flex justify-content-between align-items-center">' +
			'<div>' +
			'<strong>' + (memberData.member_name || '이름 없음') + '</strong>' +
			'<br><small class="text-muted">' + (memberData.org_name || '소속 없음') + '</small>' +
			'</div>' +
			'</li></ul>';

		$('#statusChangeMemberList').html(memberListHtml);
		$('#statusChangeCount').text('1');

		// 현재 관리tag 태그 파싱
		let currentTags = [];
		if (memberData.member_status) {
			currentTags = memberData.member_status.split(',').map(function(tag) {
				return tag.trim();
			}).filter(function(tag) {
				return tag !== '';
			});
		}

		// 기존 태그 목록 로드 후 Select2 초기화
		loadExistingStatusTags().then(function() {
			initializeStatusTagSelect(currentTags);

			// 단일 회원 모드 표시
			$('#statusChangeModal').data('mode', 'single');
			$('#statusChangeModal').data('member-idx', memberIdx);
			$('#statusChangeModal').modal('show');
		});
	}


	/**
	 * 파일 위치: assets/js/mng_member.js
	 * 역할: 단일 회원 모드 Select2 초기화
	 */
	function initializeSingleModeSelect(currentTags) {
		// 기존 Select2 인스턴스 제거
		if ($('#statusTagSelect').hasClass('select2-hidden-accessible')) {
			$('#statusTagSelect').select2('destroy');
		}

		// 옵션 초기화
		$('#statusTagSelect').empty();

		// 기존 태그 목록 추가
		existingStatusTags.forEach(function(tag) {
			$('#statusTagSelect').append('<option value="' + tag + '">' + tag + '</option>');
		});

		// Select2 초기화
		$('#statusTagSelect').select2({
			width: '100%',
			placeholder: '태그를 선택하거나 입력하세요',
			allowClear: true,
			tags: true,
			tokenSeparators: [','],
			dropdownParent: $('#statusChangeModal'),
			createTag: function(params) {
				const term = $.trim(params.term);
				if (term === '' || term.length < 1) {
					return null;
				}
				return {
					id: term,
					text: term,
					newTag: true
				};
			},
			templateResult: function(tag) {
				if (tag.newTag) {
					return $('<span class="text-primary"><i class="bi bi-plus-circle me-1"></i>' + tag.text + ' (새 태그)</span>');
				}
				return tag.text;
			},
			templateSelection: function(tag) {
				return tag.text;
			}
		});

		// 현재 태그 설정
		if (currentTags && currentTags.length > 0) {
			currentTags.forEach(function(tag) {
				if ($('#statusTagSelect option[value="' + tag + '"]').length === 0) {
					$('#statusTagSelect').append('<option value="' + tag + '" selected>' + tag + '</option>');
				}
			});
			$('#statusTagSelect').val(currentTags).trigger('change');
		}
	}

	/**
	 * 파일 위치: assets/js/mng_member.js
	 * 역할: 일괄 변경 모드 Select2 초기화
	 */
	function initializeBulkModeSelect(currentMemberTags) {
		// 추가 태그 Select2 초기화
		if ($('#addTagSelect').hasClass('select2-hidden-accessible')) {
			$('#addTagSelect').select2('destroy');
		}
		$('#addTagSelect').empty();

		existingStatusTags.forEach(function(tag) {
			$('#addTagSelect').append('<option value="' + tag + '">' + tag + '</option>');
		});

		$('#addTagSelect').select2({
			width: '100%',
			placeholder: '추가할 태그 선택 또는 입력',
			allowClear: true,
			tags: true,
			tokenSeparators: [','],
			dropdownParent: $('#statusChangeModal'),
			createTag: function(params) {
				const term = $.trim(params.term);
				if (term === '' || term.length < 1) {
					return null;
				}
				return {
					id: term,
					text: term,
					newTag: true
				};
			},
			templateResult: function(tag) {
				if (tag.newTag) {
					return $('<span class="text-primary"><i class="bi bi-plus-circle me-1"></i>' + tag.text + ' (새 태그)</span>');
				}
				return tag.text;
			},
			templateSelection: function(tag) {
				return tag.text;
			}
		});

		// 삭제 태그 Select2 초기화 (선택된 회원들의 현재 태그만 표시)
		if ($('#removeTagSelect').hasClass('select2-hidden-accessible')) {
			$('#removeTagSelect').select2('destroy');
		}
		$('#removeTagSelect').empty();

		currentMemberTags.forEach(function(tag) {
			$('#removeTagSelect').append('<option value="' + tag + '">' + tag + '</option>');
		});

		$('#removeTagSelect').select2({
			width: '100%',
			placeholder: '삭제할 태그 선택',
			allowClear: true,
			tags: false,
			dropdownParent: $('#statusChangeModal')
		});
	}

	/**
	 * 파일 위치: assets/js/mng_member.js
	 * 역할: 태그 문자열 기반으로 일관된 색상 클래스 반환 (30가지 색상)
	 */
	function getTagColorClass(tag) {
		const colors = [
			// 파란 계열
			'background-color: #2563eb; color: #fff;',  // 파랑
			'background-color: #3b82f6; color: #fff;',  // 밝은 파랑
			'background-color: #1d4ed8; color: #fff;',  // 진한 파랑
			'background-color: #0ea5e9; color: #fff;',  // 하늘색
			'background-color: #06b6d4; color: #fff;',  // 청록

			// 초록 계열
			'background-color: #16a34a; color: #fff;',  // 초록
			'background-color: #22c55e; color: #fff;',  // 밝은 초록
			'background-color: #15803d; color: #fff;',  // 진한 초록
			'background-color: #84cc16; color: #fff;',  // 라임
			'background-color: #10b981; color: #fff;',  // 에메랄드

			// 빨강/주황 계열
			'background-color: #dc2626; color: #fff;',  // 빨강
			'background-color: #ef4444; color: #fff;',  // 밝은 빨강
			'background-color: #f97316; color: #fff;',  // 주황
			'background-color: #ea580c; color: #fff;',  // 진한 주황
			'background-color: #f59e0b; color: #000;',  // 호박색

			// 보라/핑크 계열
			'background-color: #7c3aed; color: #fff;',  // 보라
			'background-color: #8b5cf6; color: #fff;',  // 밝은 보라
			'background-color: #a855f7; color: #fff;',  // 퍼플
			'background-color: #d946ef; color: #fff;',  // 마젠타
			'background-color: #ec4899; color: #fff;',  // 핑크

			// 노랑/갈색 계열
			'background-color: #eab308; color: #000;',  // 노랑
			'background-color: #ca8a04; color: #fff;',  // 진한 노랑
			'background-color: #a16207; color: #fff;',  // 갈색
			'background-color: #92400e; color: #fff;',  // 진한 갈색
			'background-color: #78716c; color: #fff;',  // 웜그레이

			// 기타
			'background-color: #475569; color: #fff;',  // 슬레이트
			'background-color: #64748b; color: #fff;',  // 밝은 슬레이트
			'background-color: #0f766e; color: #fff;',  // 틸
			'background-color: #be185d; color: #fff;',  // 로즈
			'background-color: #4f46e5; color: #fff;'   // 인디고
		];

		// 문자열 해시 계산
		let hash = 0;
		for (let i = 0; i < tag.length; i++) {
			hash = tag.charCodeAt(i) + ((hash << 5) - hash);
			hash = hash & hash;
		}

		const index = Math.abs(hash) % colors.length;
		return colors[index];
	}

})();
