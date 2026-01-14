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
	 * ParamQuery Grid 초기화
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
				freezeCols: 3,
				numberCell: { show: true, width: 40 },
				hoverMode: 'row',
				selectionModel: { type: 'row', mode: 'single' },
				resizable: true,
				wrap: false,
				hwrap: false,
				strNoRows: '회원 정보가 없습니다',
				rowDblClick: function(event, ui) {
					const rowData = ui.rowData;
					if (rowData && rowData.member_idx) {
						openMemberOffcanvas(rowData.member_idx);
					}
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
	 * 그리드 컬럼 모델 생성
	 */
	function createColumnModel() {
		return [
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
					return `<img src="${photoUrl}" class="rounded-circle" width="40" height="40" style="object-fit: cover;" onerror="this.src='/assets/images/photo_no.png'">`;
				}
			},
			{
				dataIndx: 'member_name',
				title: '이름',
				width: 100,
				frozen: true,
				filter: { type: 'textbox', condition: 'contain' }
			},
			{
				dataIndx: 'org_name',
				title: '소속 조직',
				width: 150,
				frozen: true,
				filter: { type: 'textbox', condition: 'contain' },
				render: function(ui) {
					if (ui.cellData) {
						return `<span class="badge bg-primary">${ui.cellData}</span>`;
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
						return `<span class="badge bg-secondary">${ui.cellData}</span>`;
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
			}
		];
	}

	/**
	 * 회원 목록 로드
	 */
	function loadMemberList() {
		showGridSpinner();

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
	 * 회원 상세 정보 Offcanvas 열기
	 */
	function openMemberOffcanvas(memberIdx) {
		// 스피너 표시
		$('#memberOffcanvasSpinner').show();
		$('#memberDetailForm').hide();

		// Offcanvas 열기
		if (memberOffcanvas) {
			memberOffcanvas.show();
		}

		$.ajax({
			url: '/mng/mng_member/get_member_detail',
			type: 'GET',
			data: { member_idx: memberIdx },
			dataType: 'json',
			success: function(response) {
				$('#memberOffcanvasSpinner').hide();
				$('#memberDetailForm').show();

				if (response.success) {
					const member = response.data;

					// 기본 정보 설정
					$('#detail_photo').attr('src', member.photo_url || '/assets/images/photo_no.png');
					$('#detail_member_name').text(member.member_name || '-');
					$('#detail_org_name').text(member.org_name || '-');
					$('#detail_member_phone').text(member.member_phone || '-');
					$('#detail_member_birth').text(member.member_birth || '-');

					// 성별
					let sexText = '-';
					if (member.member_sex === 'male') sexText = '남';
					else if (member.member_sex === 'female') sexText = '여';
					$('#detail_member_sex').text(sexText);

					$('#detail_position_name').text(member.position_name || '-');
					$('#detail_duty_name').text(member.duty_name || '-');

					// 주소
					let address = member.member_address || '';
					if (member.member_address_detail) {
						address += ' ' + member.member_address_detail;
					}
					$('#detail_address').text(address || '-');

					// 등록일
					let regiDate = member.regi_date ? member.regi_date.substring(0, 10) : '-';
					$('#detail_regi_date').text(regiDate);

					// 메모
					$('#detail_member_etc').text(member.member_etc || '-');
				} else {
					showToast(response.message || '회원 정보를 불러오는데 실패했습니다', 'error');
				}
			},
			error: function(xhr, status, error) {
				$('#memberOffcanvasSpinner').hide();
				console.error('회원 상세 조회 오류:', error);
				showToast('회원 정보를 불러오는데 실패했습니다', 'error');
			}
		});
	}

	/**
	 * 트리와 그리드 새로고침
	 */
	function refreshTreeAndGrid() {
		console.log('트리와 그리드 새로고침 시작');

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
	 * 글로벌 이벤트 바인딩
	 */
	function bindGlobalEvents() {
		// 새로고침 버튼
		$('#btnRefresh').on('click', function() {
			refreshTreeAndGrid();
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
