'use strict';

/**
 * 파일 위치: assets/js/mng_orglist.js
 * 역할: 관리자 조직관리 화면의 메인 JavaScript 파일
 */

(function() {
	// 전역 변수
	let orgGrid = null;
	let treeInstance = null;
	let splitInstance = null;
	let selectedCategoryIdx = null;
	let selectedCategoryName = '';
	let checkedOrgIds = new Set();

	// DOM 준비 완료 시 초기화
	$(document).ready(function() {
		initializePage();
	});

	/**
	 * 페이지 전체 초기화
	 */
	function initializePage() {
		console.log('조직관리 페이지 초기화 시작');

		cleanupExistingInstances();
		initSplitJS();
		initFancytree();
		initParamQueryGrid();
		bindGlobalEvents();

		console.log('조직관리 페이지 초기화 완료');
	}


	/**
	 * 카테고리 관리 이벤트 바인딩 (신규 추가)
	 */
	function bindCategoryManagementEvents() {
		// 카테고리 생성
		$('#btnAddCategory').on('click', function () {
			const tree = $.ui.fancytree.getTree('#categoryTree');
			const node = tree.getActiveNode();
			let parentIdx = null;

			if (node && node.data.type === 'category') {
				parentIdx = node.data.category_idx;
			}

			$.ajax({
				url: '/mng/mng_org/create_category',
				method: 'POST',
				data: {
					parent_idx: parentIdx,
					category_name: '새 카테고리'
				},
				dataType: 'json',
				success: function (response) {
					if (response.success) {
						showToast('새 카테고리가 생성되었습니다.', 'success');
						refreshTreeAndGrid();
					} else {
						showToast(response.message || '카테고리 생성에 실패했습니다.', 'error');
					}
				},
				error: function () {
					showToast('카테고리 생성 중 오류가 발생했습니다.', 'error');
				}
			});
		});

		// 카테고리명 변경
		$('#btnRenameCategory').on('click', function () {
			const tree = $.ui.fancytree.getTree('#categoryTree');
			const node = tree.getActiveNode();

			if (!node || node.data.type !== 'category') {
				showToast('변경할 카테고리를 선택해주세요.', 'warning');
				return;
			}

			const currentName = node.title.split(' (')[0];
			$('#newCategoryName').val(currentName);
			$('#renameCategoryModal').modal('show');
		});

		$('#confirmRenameCategoryBtn').on('click', function () {
			const tree = $.ui.fancytree.getTree('#categoryTree');
			const node = tree.getActiveNode();
			const newName = $('#newCategoryName').val().trim();

			if (!newName) {
				showToast('카테고리명을 입력해주세요.', 'warning');
				return;
			}

			$.ajax({
				url: '/mng/mng_org/rename_category',
				method: 'POST',
				data: {
					category_idx: node.data.category_idx,
					category_name: newName
				},
				dataType: 'json',
				success: function (response) {
					if (response.success) {
						showToast('카테고리명이 변경되었습니다.', 'success');
						$('#renameCategoryModal').modal('hide');
						refreshTreeAndGrid();
					} else {
						showToast(response.message || '카테고리명 변경에 실패했습니다.', 'error');
					}
				},
				error: function () {
					showToast('카테고리명 변경 중 오류가 발생했습니다.', 'error');
				}
			});
		});

		// 카테고리 삭제
		$('#btnDeleteCategory').on('click', function () {
			const tree = $.ui.fancytree.getTree('#categoryTree');
			const node = tree.getActiveNode();

			if (!node || node.data.type !== 'category') {
				showToast('삭제할 카테고리를 선택해주세요.', 'warning');
				return;
			}

			const message = `'${node.title.split(' (')[0]}' 카테고리를 삭제하시겠습니까? 하위 카테고리와 포함된 모든 조직이 '미분류'로 이동됩니다.`;
			$('#deleteCategoryMessage').text(message);
			$('#deleteCategoryModal').modal('show');
		});

		$('#confirmDeleteCategoryBtn').on('click', function () {
			const tree = $.ui.fancytree.getTree('#categoryTree');
			const node = tree.getActiveNode();

			$.ajax({
				url: '/mng/mng_org/delete_category',
				method: 'POST',
				data: {
					category_idx: node.data.category_idx
				},
				dataType: 'json',
				success: function (response) {
					if (response.success) {
						showToast('카테고리가 삭제되었습니다.', 'success');
						$('#deleteCategoryModal').modal('hide');
						refreshTreeAndGrid();
					} else {
						showToast(response.message || '카테고리 삭제에 실패했습니다.', 'error');
					}
				},
				error: function () {
					showToast('카테고리 삭제 중 오류가 발생했습니다.', 'error');
				}
			});
		});

		// 카테고리 이동
		$('#btnMoveCategory').on('click', function () {
			const tree = $.ui.fancytree.getTree('#categoryTree');
			const node = tree.getActiveNode();

			if (!node || node.data.type !== 'category') {
				showToast('이동할 카테고리를 선택해주세요.', 'warning');
				return;
			}

			const $select = $('#moveToCategoryCode');
			$select.empty().append('<option value="">최상위로 이동</option>');

			tree.visit(function (n) {
				if (n.data.type === 'category' && n.key !== node.key && !n.isDescendantOf(node)) {
					const level = n.getLevel() - 1;
					const indent = '&nbsp;'.repeat(level * 4);
					$select.append(
						$('<option></option>')
							.val(n.data.category_idx)
							.html(indent + n.title.split(' (')[0])
					);
				}
			});

			$('#moveCategoryMessage').text(`'${node.title.split(' (')[0]}' 카테고리를 어디로 이동하시겠습니까?`);
			$('#moveCategoryModal').modal('show');
		});

		$('#confirmMoveCategoryBtn').on('click', function () {
			const tree = $.ui.fancytree.getTree('#categoryTree');
			const node = tree.getActiveNode();
			const targetParentIdx = $('#moveToCategoryCode').val();

			$.ajax({
				url: '/mng/mng_org/move_category',
				method: 'POST',
				data: {
					source_idx: node.data.category_idx,
					target_parent_idx: targetParentIdx
				},
				dataType: 'json',
				success: function (response) {
					if (response.success) {
						showToast('카테고리가 이동되었습니다.', 'success');
						$('#moveCategoryModal').modal('hide');
						refreshTreeAndGrid();
					} else {
						showToast(response.message || '카테고리 이동에 실패했습니다.', 'error');
					}
				},
				error: function () {
					showToast('카테고리 이동 중 오류가 발생했습니다.', 'error');
				}
			});
		});
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

		if (orgGrid) {
			try {
				orgGrid.pqGrid("destroy");
			} catch(e) {
				console.warn('Grid 인스턴스 제거 실패:', e);
			}
			orgGrid = null;
		}

		checkedOrgIds.clear();
	}

	/**
	 * Split.js 초기화
	 */
	function initSplitJS() {
		setTimeout(function() {
			try {
				// 저장된 크기 불러오기
				const savedSizes = loadSplitSizes();
				const initialSizes = savedSizes || [20, 80];

				splitInstance = Split(['#left-pane', '#right-pane'], {
					sizes: initialSizes,
					minSize: [200, 400],
					gutterSize: 8,
					cursor: 'col-resize',
					direction: 'horizontal',
					onDragEnd: function(sizes) {
						// 크기 변경 시 저장
						saveSplitSizes(sizes);

						if (orgGrid) {
							setTimeout(function() {
								try {
									orgGrid.pqGrid("refresh");
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
			localStorage.setItem('orglist_split_sizes', JSON.stringify(sizes));
			console.log('Split 크기 저장:', sizes);
		} catch (error) {
			console.error('Split 크기 저장 실패:', error);
		}
	}

	/**
	 * localStorage에서 Split.js 크기 불러오기
	 */
	function loadSplitSizes() {
		try {
			const savedSizes = localStorage.getItem('orglist_split_sizes');
			if (savedSizes) {
				const sizes = JSON.parse(savedSizes);
				console.log('저장된 Split 크기 로드:', sizes);
				return sizes;
			}
		} catch (error) {
			console.error('Split 크기 로드 실패:', error);
		}
		return null;
	}

	/**
	 * Fancytree 초기화
	 */
	/**
	 * 파일 위치: assets/js/mng_orglist.js
	 * 역할: 트리 카운트 업데이트 수정
	 */

	/**
	 * 트리와 그리드 새로고침 (수정된 버전)
	 */
	function refreshTreeAndGrid() {
		console.log('트리와 그리드 새로고침 시작');

		// 체크된 조직 ID 초기화
		checkedOrgIds.clear();

		// 현재 선택된 카테고리 정보 임시 저장
		const currentSelectedIdx = selectedCategoryIdx;
		const currentSelectedName = selectedCategoryName;

		// 트리 새로고침 - 서버에서 최신 데이터 가져와서 재구성
		showTreeSpinner();

		Promise.all([
			$.get('/mng/mng_org/get_category_tree'),
			$.get('/mng/mng_org/get_total_org_count')
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

				// 서버에서 계산된 전체 조직 수 사용
				const totalOrgCount = totalCountResponse.total_count || 0;

				// 트리 데이터 구성
				const treeData = [
					{
						key: 'all',
						title: `전체 (${totalOrgCount}개)`,
						folder: true,
						expanded: true,
						data: {
							type: 'all',
							category_idx: null,
							category_name: '전체'
						},
						children: categoryNodes
					}
				];

				// 미분류는 항상 추가
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

					if (currentSelectedIdx === 'uncategorized') {
						// 미분류였다면 미분류 노드 찾기
						nodeToActivate = tree.getNodeByKey('uncategorized');
					} else if (currentSelectedIdx === null) {
						// 전체였다면 전체 노드 찾기
						nodeToActivate = tree.getNodeByKey('all');
					} else {
						// 특정 카테고리였다면 해당 노드 찾기
						nodeToActivate = tree.getNodeByKey('category_' + currentSelectedIdx);
					}

					if (nodeToActivate) {
						nodeToActivate.setActive();
						console.log('이전 선택 노드 복원:', nodeToActivate.title);
					} else {
						// 노드를 찾지 못했다면 전체 선택
						const allNode = tree.getNodeByKey('all');
						if (allNode) {
							allNode.setActive();
						}
					}
				}, 100);

				console.log('트리 새로고침 완료 - 카운트 업데이트됨');

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
			loadOrgList();
		});
	}

	/**
	 * 조직 이동 실행 (성공 후 처리 개선)
	 */
	function executeMoveOrgs() {
		const selectedOrgs = getSelectedOrgs();
		const targetCategoryIdx = $('#moveToCategory').val();

		if (selectedOrgs.length === 0) {
			showToast('이동할 조직을 선택해주세요', 'warning');
			return;
		}

		if (!targetCategoryIdx) {
			showToast('이동할 카테고리를 선택해주세요', 'warning');
			$('#moveToCategory').focus();
			return;
		}

		const orgIds = selectedOrgs.map(org => org.org_id);

		// 이동 버튼 비활성화
		$('#confirmMoveOrgBtn').prop('disabled', true);

		$.ajax({
			url: '/mng/mng_org/move_to_category',
			type: 'POST',
			data: {
				org_ids: orgIds,
				category_idx: targetCategoryIdx === 'uncategorized' ? 'uncategorized' : targetCategoryIdx
			},
			dataType: 'json',
			success: function(response) {
				$('#confirmMoveOrgBtn').prop('disabled', false);
				$('#moveOrgModal').modal('hide');

				showToast(response.message, response.success ? 'success' : 'error');

				if (response.success) {
					console.log('조직 이동 성공 - 트리와 그리드 새로고침 시작');

					// 트리와 그리드 새로고침 - 카운트 업데이트 포함
					refreshTreeAndGrid();
				}
			},
			error: function() {
				$('#confirmMoveOrgBtn').prop('disabled', false);
				$('#moveOrgModal').modal('hide');
				showToast('조직 이동 중 오류가 발생했습니다', 'error');
			}
		});
	}

	/**
	 * Fancytree 초기화 함수에서도 로그 추가 (디버깅용)
	 */
	function initFancytree() {
		console.log('Fancytree 초기화 시작');
		showTreeSpinner();

		// 전체 조직 수와 카테고리 트리를 별도로 조회
		Promise.all([
			$.get('/mng/mng_org/get_category_tree'),
			$.get('/mng/mng_org/get_total_org_count')
		]).then(function(results) {
			const treeResponse = results[0];
			const totalCountResponse = results[1];

			console.log('트리 응답:', treeResponse);
			console.log('전체 카운트 응답:', totalCountResponse);

			try {
				// 미분류 노드를 별도로 추출
				const uncategorizedNode = treeResponse.find(node => node.data && node.data.type === 'uncategorized');
				const categoryNodes = treeResponse.filter(node => !node.data || node.data.type !== 'uncategorized');

				// 서버에서 계산된 전체 조직 수 사용
				const totalOrgCount = totalCountResponse.total_count || 0;
				console.log('총 조직 수:', totalOrgCount);

				// 트리 데이터 구성
				const treeData = [
					{
						key: 'all',
						title: `전체 (${totalOrgCount}개)`,
						folder: true,
						expanded: true,
						data: {
							type: 'all',
							category_idx: null,
							category_name: '전체'
						},
						children: categoryNodes
					}
				];

				// 미분류는 항상 추가
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
				category_idx: nodeData.category_idx,
				category_name: nodeData.category_name,
				timestamp: Date.now()
			};

			localStorage.setItem('orglist_selected_tree_node', JSON.stringify(treeState));
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
			const savedState = localStorage.getItem('orglist_selected_tree_node');

			if (savedState) {
				const treeState = JSON.parse(savedState);

				// 7일 이내의 데이터만 복원
				const sevenDaysAgo = Date.now() - (7 * 24 * 60 * 60 * 1000);
				if (treeState.timestamp < sevenDaysAgo) {
					localStorage.removeItem('orglist_selected_tree_node');
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
		} else if (treeState.type === 'category' && treeState.category_idx) {
			nodeToActivate = tree.getNodeByKey('category_' + treeState.category_idx);
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
		const nodeData = node.data;

		if (nodeData.type === 'category' || nodeData.type === 'all' || nodeData.type === 'uncategorized') {
			if (nodeData.type === 'uncategorized') {
				selectedCategoryIdx = 'uncategorized';
				selectedCategoryName = '미분류';
			} else if (nodeData.type === 'all') {
				selectedCategoryIdx = null; // 전체 선택 시 null로 설정 (미분류 제외)
				selectedCategoryName = '전체';
			} else {
				selectedCategoryIdx = nodeData.category_idx;
				selectedCategoryName = nodeData.category_name;
			}

			// 트리 선택 상태 저장
			saveSelectedTreeNode(nodeData);

			// 조직추가 버튼 활성화 (카테고리 선택 시)
			$('#btnAddOrg').prop('disabled', false);


			updateSelectedTitle();
			loadOrgList();



		}
	}

	/**
	 * ParamQuery Grid 초기화
	 */
	function initParamQueryGrid() {
		showGridSpinner();

		try {
			// 초기 컬럼 모델 생성
			let colModel = createColumnModel();

			// 저장된 컬럼 순서가 있으면 적용
			const savedOrder = loadColumnOrder();
			if (savedOrder) {
				colModel = reorderColumnModel(colModel, savedOrder);
			}

			orgGrid = $("#orgGrid").pqGrid({
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
				strNoRows: '조직 정보가 없습니다',
				cellClick: function(event, ui) {
					handleCellClick(event, ui);
				},
				complete: function() {
					setTimeout(function() {
						bindCheckboxEvents();
						updateCheckboxStates();
					}, 100);
				},
				// 컬럼 순서 변경 이벤트
				columnOrder: function(event, ui) {
					console.log('컬럼 순서 변경됨');
					const currentColModel = orgGrid.pqGrid('option', 'colModel');
					saveColumnOrder(currentColModel);
				},
				// 컬럼 리사이즈 이벤트
				columnResize: function(event, ui) {
					console.log('컬럼 리사이즈됨');
					const currentColModel = orgGrid.pqGrid('option', 'colModel');
					saveColumnOrder(currentColModel);
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
	 * 컬럼 순서와 너비를 localStorage에 저장
	 */
	function saveColumnOrder(colModel) {
		try {
			// 컬럼의 dataIndx와 width를 순서대로 저장 (체크박스 컬럼 제외)
			const columnData = colModel
				.filter(col => col.dataIndx && col.dataIndx !== 'checkbox')
				.map(col => ({
					dataIndx: col.dataIndx,
					width: col.width
				}));

			const storageKey = 'orglist_column_order';
			localStorage.setItem(storageKey, JSON.stringify(columnData));

			console.log('컬럼 순서 및 너비 저장:', columnData);
		} catch (error) {
			console.error('컬럼 순서 저장 실패:', error);
		}
	}

	/**
	 * localStorage에서 컬럼 순서 불러오기
	 */
	function loadColumnOrder() {
		try {
			const storageKey = 'orglist_column_order';
			const savedOrder = localStorage.getItem(storageKey);

			if (savedOrder) {
				const columnOrder = JSON.parse(savedOrder);
				console.log('저장된 컬럼 순서 로드:', columnOrder);
				return columnOrder;
			}
		} catch (error) {
			console.error('컬럼 순서 로드 실패:', error);
		}

		return null;
	}

	/**
	 * 저장된 순서와 width에 따라 컬럼 모델 재정렬
	 */
	function reorderColumnModel(colModel, savedData) {
		if (!savedData || savedData.length === 0) {
			return colModel;
		}

		// 체크박스 컬럼은 항상 첫 번째에 고정
		const checkboxCol = colModel.find(col => col.dataIndx === 'checkbox');
		const otherCols = colModel.filter(col => col.dataIndx !== 'checkbox');

		// 저장된 순서대로 재정렬
		const reorderedCols = [];
		const colMap = new Map(otherCols.map(col => [col.dataIndx, col]));

		// 저장된 순서대로 먼저 추가하고 width 적용
		savedData.forEach(savedCol => {
			if (colMap.has(savedCol.dataIndx)) {
				const col = colMap.get(savedCol.dataIndx);
				// 저장된 width 적용
				col.width = savedCol.width;
				reorderedCols.push(col);
				colMap.delete(savedCol.dataIndx);
			}
		});

		// 저장된 순서에 없는 새로운 컬럼들은 뒤에 추가
		colMap.forEach(col => {
			reorderedCols.push(col);
		});

		// 체크박스 컬럼을 맨 앞에 추가
		return checkboxCol ? [checkboxCol, ...reorderedCols] : reorderedCols;
	}

	/**
	 * 그리드 컬럼 모델 생성
	 */
	/**
	 * 그리드 컬럼 모델 생성 (render 함수 수정)
	 */
	function createColumnModel() {
		return [
			{
				title: '<input type="checkbox" id="selectAllOrgs" />',
				dataIndx: "checkbox",
				width: 50,
				align: "center",
				resizable: false,
				sortable: false,
				editable: false,
				menuIcon: false,
				frozen: true,
				render: function(ui) {
					const orgId = ui.rowData.org_id;
					const isChecked = checkedOrgIds.has(orgId);
					return `<input type="checkbox" class="org-checkbox" data-org-id="${orgId}" ${isChecked ? 'checked' : ''} />`;
				}
			},
			{
				dataIndx: 'category_name',
				title: '카테고리',
				width: 120,
				frozen: true,
				editable: false,
				render: function(ui) {
					if (ui.cellData) {
						return `<span class="badge bg-secondary">${ui.cellData}</span>`;
					}
					return '<span class="badge bg-light text-dark">미분류</span>';
				}
			},
			{
				dataIndx: 'org_icon',
				title: '아이콘',
				width: 60,
				align: 'center',
				editable: false,
				frozen: true,
				render: function(ui) {
					if (ui.rowData.org_icon) {
						return `<img src="${ui.rowData.org_icon}" class="rounded" width="40" height="40" alt="조직 아이콘">`;
					}
					return `<div class="d-inline-block" style="width:40px;height:40px; border-radius: 20px;padding: 5px; color: #ccc; background: #eee">
                        <i class="bi bi-people-fill" style="font-size: 20px"></i>
                    </div>`;
				}
			},
			{
				dataIndx: 'org_name',
				title: '조직명',
				width: 150,
				editable: false,
				render: function(ui) {
					return `<strong>${ui.cellData || ''}</strong>`;
				}
			},
			{
				dataIndx: 'org_tag',
				title: '태그',
				width: 150,
				editable: false,
				render: function(ui) {
					if (ui.cellData) {
						let tags = [];
						try {
							const parsed = JSON.parse(ui.cellData);
							if (Array.isArray(parsed)) {
								tags = parsed;
							} else {
								tags = ui.cellData.split(',').map(tag => tag.trim()).filter(tag => tag);
							}
						} catch(e) {
							tags = ui.cellData.split(',').map(tag => tag.trim()).filter(tag => tag);
						}

						if (tags.length > 0) {
							return tags.map(tag => `<span class="badge bg-primary me-1">${tag}</span>`).join('');
						}
					}
					return '<span class="text-muted">-</span>';
				}
			},
			{
				dataIndx: 'org_code',
				title: '조직코드',
				width: 150,
				editable: false,
				render: function(ui) {
					return `<code>${ui.cellData || ''}</code>`;
				}
			},
			{
				dataIndx: 'org_type',
				title: '유형',
				width: 100,
				align: 'center',
				editable: false,
				render: function(ui) {
					return getOrgTypeText(ui.cellData);
				}
			},
			{
				dataIndx: 'org_desc',
				title: '조직설명',
				width: 200,
				editable: false,
				render: function(ui) {
					return ui.cellData ? ui.cellData : '<span class="text-muted">-</span>';
				}
			},
			{
				dataIndx: 'org_rep',
				title: '대표자',
				width: 80,
				align: 'center',
				editable: false,
				render: function(ui) {
					return ui.cellData ? ui.cellData : '<span class="text-muted">-</span>';
				}
			},
			{
				dataIndx: 'org_manager',
				title: '담당자',
				width: 80,
				align: 'center',
				editable: false,
				render: function(ui) {
					return ui.cellData ? ui.cellData : '<span class="text-muted">-</span>';
				}
			},
			{
				dataIndx: 'org_phone',
				title: '연락처',
				width: 120,
				align: 'center',
				editable: false,
				render: function(ui) {
					return ui.cellData ? ui.cellData : '<span class="text-muted">-</span>';
				}
			},
			{
				dataIndx: 'org_address_postno',
				title: '우편번호',
				width: 80,
				align: 'center',
				editable: false,
				render: function(ui) {
					return ui.cellData ? ui.cellData : '<span class="text-muted">-</span>';
				}
			},
			{
				dataIndx: 'org_address',
				title: '주소',
				width: 150,
				editable: false,
				render: function(ui) {
					return ui.cellData ? ui.cellData : '<span class="text-muted">-</span>';
				}
			},
			{
				dataIndx: 'org_address_detail',
				title: '상세주소',
				width: 150,
				editable: false,
				render: function(ui) {
					return ui.cellData ? ui.cellData : '<span class="text-muted">-</span>';
				}
			},
			{
				dataIndx: 'member_count',
				title: '회원수',
				width: 80,
				align: 'center',
				editable: false,
				dataType: 'integer',
				render: function(ui) {
					const count = ui.cellData || 0;
					return `<span class="badge bg-info">${count}명</span>`;
				}
			},
			{
				dataIndx: 'user_count',
				title: '사용자수',
				width: 80,
				align: 'center',
				editable: false,
				dataType: 'integer',
				render: function(ui) {
					const count = ui.cellData || 0;
					return `<span class="badge bg-success">${count}명</span>`;
				}
			},
			{
				dataIndx: 'regi_date',
				title: '등록일',
				width: 120,
				align: 'center',
				editable: false,
				render: function(ui) {
					if (ui.cellData) {
						return new Date(ui.cellData).toLocaleDateString();
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
		if (ui.colIndx === 0) {
			const target = event.originalEvent.target;
			if (!$(target).hasClass('org-checkbox')) {
				const orgId = ui.rowData.org_id;
				const checkbox = $(`.org-checkbox[data-org-id="${orgId}"]`);
				if (checkbox.length > 0) {
					checkbox.prop('checked', !checkbox.prop('checked')).trigger('change');
				}
			}
		} else {
			const orgId = ui.rowData.org_id;
			if (orgId) {
				openOrgOffcanvas(orgId);
			}
		}
	}


	/**
	 * 조직 정보 수정 offcanvas 열기
	 */
	function openOrgOffcanvas(orgId) {
		console.log('openOrgOffcanvas 호출, orgId:', orgId);
		showOffcanvasSpinner();

		// 단계별로 처리
		loadOrgDetail(orgId)
			.then(function(orgData) {
				console.log('조직 데이터 로드 완료:', orgData);
				return Promise.all([
					loadCategoryOptions(),
					initializeTagSelect(),
					Promise.resolve(orgData)
				]);
			})
			.then(function(results) {
				console.log('모든 옵션 로드 완료');
				const orgData = results[2];

				// 폼 데이터 설정
				populateOrgForm(orgData);

				// 스피너 숨기고 offcanvas 표시
				hideOffcanvasSpinner();
				$('#orgOffcanvas').offcanvas('show');

				console.log('offcanvas 표시 완료');
			})
			.catch(function(error) {
				console.error('offcanvas 로드 실패:', error);
				hideOffcanvasSpinner();
				showToast('조직 정보를 불러오는 중 오류가 발생했습니다', 'error');
			});
	}

	/**
	 * 조직 상세 정보 로드
	 */
	function loadOrgDetail(orgId) {
		return new Promise(function(resolve, reject) {
			$.ajax({
				url: '/mng/mng_org/get_org_detail',
				type: 'GET',
				data: { org_id: orgId },
				dataType: 'json',
				success: function(response) {
					console.log('조직 상세 정보 응답:', response);
					if (response && response.success && response.data) {
						resolve(response.data);
					} else {
						reject(new Error(response.message || '조직 정보를 불러올 수 없습니다'));
					}
				},
				error: function(xhr, status, error) {
					console.error('조직 상세 정보 로드 실패:', {
						status: status,
						error: error,
						responseText: xhr.responseText,
						statusCode: xhr.status
					});
					reject(new Error('조직 정보 로드 중 서버 오류가 발생했습니다'));
				}
			});
		});
	}

	/**
	 * 카테고리 옵션 로드
	 */
	function loadCategoryOptions() {
		return new Promise(function(resolve, reject) {
			$.ajax({
				url: '/mng/mng_org/get_category_list',
				type: 'GET',
				dataType: 'json',
				success: function(response) {
					console.log('카테고리 목록 응답:', response);
					const categorySelect = $('#edit_category_idx');
					categorySelect.empty().append('<option value="">카테고리 선택</option>');

					if (response && response.success && response.data && Array.isArray(response.data)) {
						response.data.forEach(function(category) {
							// 계층구조가 이미 category_name에 포함되어 있음 (들여쓰기 적용됨)
							categorySelect.append(`<option value="${category.category_idx}">${category.category_name}</option>`);
						});
					}
					resolve();
				},
				error: function(xhr, status, error) {
					console.warn('카테고리 목록 로드 실패:', error);
					resolve(); // 카테고리 로드 실패해도 계속 진행
				}
			});
		});
	}

	/**
	 * 태그 Select2 초기화
	 */
	function initializeTagSelect() {
		return new Promise(function(resolve, reject) {
			// 기존 Select2 인스턴스 제거
			if ($('#edit_org_tag').hasClass('select2-hidden-accessible')) {
				$('#edit_org_tag').select2('destroy');
			}

			// 기존 태그 목록 로드
			loadExistingTags()
				.then(function() {
					// Select2 초기화
					$('#edit_org_tag').select2({
						width: '100%',
						placeholder: '태그를 선택하거나 입력하세요',
						allowClear: false,
						tags: true,
						tokenSeparators: [',', ' '],
						createTag: function(params) {
							const term = $.trim(params.term);
							if (term === '' || term.length < 2) {
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
					resolve();
				})
				.catch(function(error) {
					console.warn('태그 초기화 실패:', error);
					resolve(); // 태그 초기화 실패해도 계속 진행
				});
		});
	}

	/**
	 * 기존 태그 목록 로드
	 */
	function loadExistingTags() {
		return new Promise(function(resolve, reject) {
			$.ajax({
				url: '/mng/mng_org/get_existing_tags',
				type: 'GET',
				dataType: 'json',
				success: function(response) {
					console.log('태그 목록 응답:', response);
					if (response && response.success && response.data) {
						const tagSelect = $('#edit_org_tag');
						tagSelect.empty();

						response.data.forEach(function(tag) {
							tagSelect.append(`<option value="${tag}">${tag}</option>`);
						});
					}
					resolve();
				},
				error: function(xhr, status, error) {
					console.warn('기존 태그 목록 로드 실패:', error);
					resolve(); // 태그 로드 실패해도 계속 진행
				}
			});
		});
	}

	/**
	 * 조직 정보를 폼에 채우기 (수정된 버전)
	 */
	function populateOrgForm(orgData) {
		console.log('populateOrgForm 시작:', orgData);

		try {
			// 기본 필드들 - 안전하게 값 설정
			const setFieldValue = (id, value) => {
				const element = $(`#${id}`);
				if (element.length > 0) {
					element.val(value || '');
					console.log(`${id} 설정:`, value);
				} else {
					console.warn(`요소를 찾을 수 없음: ${id}`);
				}
			};

			// 기본 정보 설정
			setFieldValue('edit_org_id', orgData.org_id);
			setFieldValue('edit_org_name', orgData.org_name);
			setFieldValue('edit_org_code', orgData.org_code);
			setFieldValue('edit_org_type', orgData.org_type);
			setFieldValue('edit_org_desc', orgData.org_desc);

			// 담당자 정보 - API 응답에 있는 필드들만 설정
			setFieldValue('edit_org_rep', orgData.org_rep);
			setFieldValue('edit_org_manager', orgData.org_manager);
			setFieldValue('edit_org_phone', orgData.org_phone);

			// 주소 정보
			setFieldValue('edit_org_address_postno', orgData.org_address_postno);
			setFieldValue('edit_org_address', orgData.org_address);
			setFieldValue('edit_org_address_detail', orgData.org_address_detail);

			// 카테고리 선택 - 지연 처리
			setTimeout(function() {
				if (orgData.category_idx) {
					$('#edit_category_idx').val(orgData.category_idx);
					console.log('카테고리 설정:', orgData.category_idx);
				}
			}, 100);

			// 태그 처리 - 지연 처리
			setTimeout(function() {
				handleTagPopulation(orgData.org_tag);
			}, 300);

			// 제목 업데이트 및 바로가기 버튼 설정
			updateOffcanvasHeader(orgData);

			console.log('populateOrgForm 완료');

		} catch (error) {
			console.error('populateOrgForm 오류:', error);
			showToast('폼 데이터 설정 중 오류가 발생했습니다', 'error');
		}
	}


	/**
	 * offcanvas 헤더 영역 업데이트 (제목 및 바로가기 버튼)
	 */
	function updateOffcanvasHeader(orgData) {
		try {
			const orgName = orgData.org_name || '알 수 없음';
			const orgId = orgData.org_id;

			// 제목 업데이트
			$('#orgOffcanvasLabel').text(`조직 정보 수정 - ${orgName}`);

			// 바로가기 버튼 설정
			const dashboardBtn = $('#orgDashboardBtn');

			if (orgId) {
				// 버튼 표시 및 데이터 설정
				dashboardBtn
					.removeClass('d-none')
					.attr('data-org-id', orgId)
					.attr('title', `${orgName} 대시보드 바로가기`);

				console.log('바로가기 버튼 설정:', orgId, orgName);
			} else {
				// 버튼 숨기기
				dashboardBtn.addClass('d-none');
			}

		} catch (error) {
			console.error('updateOffcanvasHeader 오류:', error);
		}
	}


	/**
	 * 바로가기 버튼 클릭 처리
	 */
	function handleDashboardButtonClick() {
		const orgId = $('#orgDashboardBtn').attr('data-org-id');
		const orgName = $('#edit_org_name').val() || $('#orgDashboardBtn').attr('title').replace(' 대시보드 바로가기', '');
		const orgIcon = $('#edit_org_icon').val() || ''; // 조직 아이콘 정보가 있다면

		if (!orgId) {
			console.error('조직 ID를 찾을 수 없음');
			showToast('조직 정보를 찾을 수 없습니다', 'error');
			return;
		}

		console.log('바로가기 클릭:', { orgId, orgName, orgIcon });

		// 버튼 비활성화 및 로딩 표시
		const $btn = $('#orgDashboardBtn');
		const originalHtml = $btn.html();
		$btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>이동 중...');

		// 1. 로컬스토리지에 조직 정보 저장 (다음 로그인 시 사용)
		try {
			localStorage.setItem('lastSelectedOrgId', orgId);
			localStorage.setItem('lastSelectedOrgName', orgName);
			if (orgIcon) {
				localStorage.setItem('lastSelectedOrgIcon', orgIcon);
			}
			console.log('로컬스토리지에 조직 정보 저장 완료');
		} catch (e) {
			console.warn('로컬스토리지 저장 실패:', e);
		}

		// 2. 서버에 조직 전환 요청 (새 탭에서 사용할 세션 변경)
		$.ajax({
			url: '/login/set_default_org',
			type: 'POST',
			data: { org_id: orgId },
			dataType: 'json',
			success: function(response) {
				if (response.success) {
					console.log('서버 조직 전환 성공:', response);

					// 3. 새 탭에서 대시보드 열기
					window.open('/dashboard', '_blank');

					// 버튼 원상복구
					$btn.prop('disabled', false).html(originalHtml);

					showToast(`${orgName}(으)로 전환되었습니다`, 'success');
				} else {
					showToast(response.message || '조직 전환에 실패했습니다', 'error');
					$btn.prop('disabled', false).html(originalHtml);
				}
			},
			error: function(xhr, status, error) {
				console.error('조직 전환 실패:', status, error);
				showToast('조직 전환 중 오류가 발생했습니다', 'error');
				$btn.prop('disabled', false).html(originalHtml);
			}
		});
	}
	/**
	 * 태그 설정 처리
	 */
	function handleTagPopulation(orgTagData) {
		console.log('태그 설정 시작:', orgTagData);

		try {
			if (!orgTagData) {
				console.log('태그 데이터 없음');
				return;
			}

			let tags = [];

			// JSON 문자열인 경우 파싱
			if (typeof orgTagData === 'string') {
				try {
					const parsed = JSON.parse(orgTagData);
					if (Array.isArray(parsed)) {
						tags = parsed;
					} else {
						// 콤마로 분리된 문자열인 경우
						tags = orgTagData.split(',').map(tag => tag.trim()).filter(tag => tag);
					}
				} catch(e) {
					console.warn('JSON 파싱 실패, 문자열로 처리:', e);
					tags = orgTagData.split(',').map(tag => tag.trim()).filter(tag => tag);
				}
			} else if (Array.isArray(orgTagData)) {
				tags = orgTagData;
			}

			console.log('처리된 태그 배열:', tags);

			if (tags.length > 0) {
				// 존재하지 않는 태그들을 옵션에 추가
				tags.forEach(function(tag) {
					if ($(`#edit_org_tag option[value="${tag}"]`).length === 0) {
						$('#edit_org_tag').append(`<option value="${tag}">${tag}</option>`);
						console.log('태그 옵션 추가:', tag);
					}
				});

				// 태그 선택
				$('#edit_org_tag').val(tags).trigger('change');
				console.log('태그 선택 완료:', tags);
			}

		} catch (error) {
			console.error('태그 설정 오류:', error);
		}
	}

	/**
	 * 조직 정보 저장
	 */
	function saveOrgInfo() {
		const orgName = $('#edit_org_name').val().trim();
		if (!orgName) {
			showToast('조직명을 입력해주세요', 'warning');
			$('#edit_org_name').focus();
			return;
		}

		const formData = new FormData();
		formData.append('org_id', $('#edit_org_id').val());
		formData.append('org_name', $('#edit_org_name').val());
		formData.append('org_code', $('#edit_org_code').val());
		formData.append('org_type', $('#edit_org_type').val());
		formData.append('org_desc', $('#edit_org_desc').val());
		formData.append('category_idx', $('#edit_category_idx').val());
		formData.append('org_rep', $('#edit_org_rep').val());
		formData.append('org_manager', $('#edit_org_manager').val());
		formData.append('org_phone', $('#edit_org_phone').val());
		formData.append('org_address_postno', $('#edit_org_address_postno').val());
		formData.append('org_address', $('#edit_org_address').val());
		formData.append('org_address_detail', $('#edit_org_address_detail').val());

		const selectedTags = $('#edit_org_tag').val() || [];
		formData.append('org_tag', JSON.stringify(selectedTags));

		$('#saveOrgBtn').prop('disabled', true);

		$.ajax({
			url: '/mng/mng_org/update_org',
			type: 'POST',
			data: formData,
			processData: false,
			contentType: false,
			dataType: 'json',
			success: function(response) {
				$('#saveOrgBtn').prop('disabled', false);
				showToast(response.message, response.success ? 'success' : 'error');

				if (response.success) {
					$('#orgOffcanvas').offcanvas('hide');
					loadOrgList();
				}
			},
			error: function() {
				$('#saveOrgBtn').prop('disabled', false);
				showToast('조직 정보 저장 중 오류가 발생했습니다', 'error');
			}
		});
	}

	/**
	 * 체크박스 이벤트 바인딩
	 */
	function bindCheckboxEvents() {
		// 기존 이벤트 제거
		$(document).off('change', '#selectAllOrgs');
		$(document).off('change', '.org-checkbox');

		// 전체 선택 체크박스
		$(document).on('change', '#selectAllOrgs', function(e) {
			e.stopPropagation();
			const isChecked = $(this).is(':checked');
			const wasIndeterminate = $(this).prop('indeterminate');

			if (wasIndeterminate) {
				$(this).prop('indeterminate', false);
				$(this).prop('checked', true);
			}

			$('.org-checkbox').each(function() {
				const orgId = parseInt($(this).data('org-id'));
				const shouldCheck = wasIndeterminate || isChecked;
				$(this).prop('checked', shouldCheck);

				if (shouldCheck) {
					checkedOrgIds.add(orgId);
				} else {
					checkedOrgIds.delete(orgId);
				}
			});

			updateSelectedCount();
		});

		// 개별 체크박스
		$(document).on('change', '.org-checkbox', function(e) {
			e.stopPropagation();
			const orgId = parseInt($(this).data('org-id'));
			const isChecked = $(this).is(':checked');

			if (isChecked) {
				checkedOrgIds.add(orgId);
			} else {
				checkedOrgIds.delete(orgId);
			}

			updateSelectAllCheckboxState();
			updateSelectedCount();
		});
	}

	/**
	 * 전체 선택 체크박스 상태 업데이트
	 */
	function updateSelectAllCheckboxState() {
		const totalCheckboxes = $('.org-checkbox').length;
		const checkedCount = checkedOrgIds.size;
		const selectAllCheckbox = $('#selectAllOrgs');

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
	 * 조직 목록 로드
	 */
	function loadOrgList() {
		showGridSpinner();
		checkedOrgIds.clear();

		const requestData = {};

		if (selectedCategoryIdx !== null && selectedCategoryIdx !== 'uncategorized') {
			requestData.category_idx = selectedCategoryIdx;
		} else if (selectedCategoryIdx === 'uncategorized') {
			requestData.category_idx = 'uncategorized';
		}

		$.ajax({
			url: '/mng/mng_org/get_org_list',
			type: 'GET',
			data: requestData,
			dataType: 'json',
			success: function(response) {
				hideGridSpinner();

				if (response.success) {
					orgGrid.pqGrid("option", "dataModel.data", response.data || []);
					orgGrid.pqGrid("refreshDataAndView");

					// 그리드 로드 완료 후 체크박스 이벤트 재바인딩
					setTimeout(function() {
						$('#selectAllOrgs').prop('checked', false).prop('indeterminate', false);
						bindCheckboxEvents(); // 이벤트 재바인딩
						updateSelectedCount();
					}, 200);
				} else {
					showToast('조직 목록 로딩에 실패했습니다', 'error');
				}
			},
			error: function(xhr, status, error) {
				hideGridSpinner();
				console.error('조직 목록 로드 실패:', status, error);
				showToast('조직 목록을 불러올 수 없습니다', 'error');
			}
		});
	}


	/**
	 * 전역 이벤트 바인딩 함수에 추가
	 */
	function bindGlobalEvents() {
		$('#btnDeleteOrg').on('click', showDeleteModal);
		$('#confirmDeleteOrgBtn').on('click', executeDelete);
		$('#saveOrgBtn').on('click', saveOrgInfo);

		// 조직 이동 이벤트
		$('#btnMoveOrg, #btnMoveOrgMobile').on('click', showMoveModal);
		$('#confirmMoveOrgBtn').on('click', executeMoveOrgs);

		// 삭제 이벤트
		$('#btnDeleteOrgMobile').on('click', showDeleteModal);

		// 바로가기 버튼 이벤트
		$('#orgDashboardBtn').on('click', handleDashboardButtonClick);

		// 지도 버튼 이벤트 추가
		$('#btnOrgMap').on('click', openOrgMapPopup);

		// 엑셀편집 버튼 이벤트 추가
		$('#btnExcelEdit').on('click', openExcelEditPopup);

		// 조직추가 버튼 이벤트
		$('#btnAddOrg').on('click', addQuickOrg);

		// 카테고리 관리 이벤트 바인딩 추가
		bindCategoryManagementEvents();

		$(window).on('resize', debounce(function() {
			if (orgGrid) {
				try {
					orgGrid.pqGrid("refresh");
				} catch(e) {
					console.warn('윈도우 리사이즈 시 그리드 리프레시 실패:', e);
				}
			}
		}, 250));
	}

	/**
	 * 빠른 조직 추가
	 * 선택된 카테고리에 '새조직_{org_id}' 이름으로 기본 조직 생성
	 */
	function addQuickOrg() {
		if (selectedCategoryIdx === null) {
			showToast('조직을 추가할 카테고리를 선택해주세요', 'warning');
			return;
		}

		// 미분류 선택 시에도 추가 가능
		const categoryIdx = selectedCategoryIdx === 'uncategorized' ? '' : selectedCategoryIdx;

		// 버튼 비활성화 (중복 클릭 방지)
		$('#btnAddOrg').prop('disabled', true);

		$.ajax({
			url: '/mng/mng_org/quick_add_org',
			type: 'POST',
			data: {
				category_idx: categoryIdx
			},
			dataType: 'json',
			success: function(response) {
				$('#btnAddOrg').prop('disabled', false);

				if (response.success) {
					showToast(response.message, 'success');
					// 트리와 그리드 새로고침
					refreshTreeAndGrid();
				} else {
					showToast(response.message || '조직 추가에 실패했습니다', 'error');
				}
			},
			error: function(xhr, status, error) {
				$('#btnAddOrg').prop('disabled', false);
				console.error('조직 추가 실패:', error);
				showToast('조직 추가 중 오류가 발생했습니다', 'error');
			}
		});
	}


	/**
	 * 엑셀편집 팝업 열기
	 */
	function openExcelEditPopup() {
		if (!orgGrid) {
			showToast('그리드가 초기화되지 않았습니다', 'error');
			return;
		}

		try {
			// 현재 그리드 데이터 가져오기
			const gridData = orgGrid.pqGrid('option', 'dataModel.data');

			if (!gridData || gridData.length === 0) {
				showToast('편집할 조직 데이터가 없습니다', 'warning');
				return;
			}

			// 컬럼 모델 가져오기
			const colModel = orgGrid.pqGrid('option', 'colModel');

			// 세션 스토리지에 데이터 저장
			sessionStorage.setItem('bulkEditOrgData', JSON.stringify(gridData));
			sessionStorage.setItem('bulkEditOrgColumns', JSON.stringify(colModel));
			sessionStorage.setItem('bulkEditOrgCategoryIdx', selectedCategoryIdx || '');

			// 팝업 열기
			const popupWidth = 1400;
			const popupHeight = 800;
			const left = (screen.width - popupWidth) / 2;
			const top = (screen.height - popupHeight) / 2;

			const popup = window.open(
				'/mng/mng_org/org_popup',
				'orgBulkEdit',
				`width=${popupWidth},height=${popupHeight},left=${left},top=${top},resizable=yes,scrollbars=yes`
			);

			if (!popup) {
				showToast('팝업 차단을 해제해주세요', 'warning');
				return;
			}

			// 팝업에서 메시지 수신 대기
			window.addEventListener('message', handleBulkEditMessage);

		} catch(error) {
			console.error('엑셀편집 팝업 열기 오류:', error);
			showToast('팝업을 열 수 없습니다', 'error');
		}
	}

	/**
	 * 팝업에서 전송된 메시지 처리
	 */
	function handleBulkEditMessage(event) {
		if (event.data.type === 'bulkEditOrgComplete') {
			console.log('대량 편집 완료 데이터 수신:', event.data.data);

			// 서버로 데이터 전송
			saveBulkEditData(event.data.data);

			// 이벤트 리스너 제거
			window.removeEventListener('message', handleBulkEditMessage);
		}
	}



	/**
	 * 대량 편집 데이터 저장
	 */
	function saveBulkEditData(data) {
		if (!data || data.length === 0) {
			showToast('저장할 데이터가 없습니다', 'warning');
			return;
		}

		// 로딩 표시
		showGridSpinner();

		$.ajax({
			url: '/mng/mng_org/bulk_update_orgs',
			type: 'POST',
			contentType: 'application/json',
			data: JSON.stringify({ orgs: data }),
			dataType: 'json',
			success: function(response) {
				hideGridSpinner();

				if (response.success) {
					showToast(response.message, 'success');

					// 트리와 그리드 새로고침
					refreshTreeAndGrid();
				} else {
					showToast(response.message || '저장에 실패했습니다', 'error');
				}
			},
			error: function(xhr, status, error) {
				hideGridSpinner();
				console.error('대량 편집 저장 오류:', error);
				showToast('저장 중 오류가 발생했습니다', 'error');
			}
		});
	}

	/**
	 * 조직 이동 모달 표시
	 */
	function showMoveModal() {
		// 체크된 조직 개수 확인
		if (checkedOrgIds.size === 0) {
			showToast('이동할 조직을 선택해주세요', 'warning');
			return;
		}

		// 실제 조직 데이터 가져오기
		const selectedOrgs = getSelectedOrgs();

		if (selectedOrgs.length === 0) {
			showToast('선택된 조직 데이터를 찾을 수 없습니다', 'error');
			return;
		}

		// 메시지 업데이트
		const message = selectedOrgs.length === 1
			? '선택한 1개의 조직을 다른 카테고리로 이동하시겠습니까?'
			: `선택한 ${selectedOrgs.length}개의 조직을 다른 카테고리로 이동하시겠습니까?`;

		$('#moveOrgMessage').text(message);

		// 이동 가능한 카테고리 목록 로드
		loadMovableCategoryOptions();

		$('#moveOrgModal').modal('show');
	}

	/**
	 * 이동 가능한 카테고리 목록 로드 (최상위, 미분류 제외)
	 */
	function loadMovableCategoryOptions() {
		$.ajax({
			url: '/mng/mng_org/get_category_list',
			type: 'GET',
			dataType: 'json',
			success: function(response) {
				const categorySelect = $('#moveToCategory');
				categorySelect.empty().append('<option value="">카테고리 선택</option>');

				if (response && response.success && response.data && Array.isArray(response.data)) {
					// 미분류 옵션 추가
					categorySelect.append('<option value="uncategorized">미분류</option>');

					// 일반 카테고리 옵션들 추가 (최상위는 제외)
					response.data.forEach(function(category) {
						// 최상위나 전체 카테고리는 제외
						if (category.category_idx && category.category_name) {
							categorySelect.append(`<option value="${category.category_idx}">${category.category_name}</option>`);
						}
					});
				}
			},
			error: function() {
				showToast('카테고리 목록을 불러올 수 없습니다', 'error');
			}
		});
	}

	/**
	 * 조직 이동 실행
	 */
	function executeMoveOrgs() {
		const selectedOrgs = getSelectedOrgs();
		const targetCategoryIdx = $('#moveToCategory').val();

		if (selectedOrgs.length === 0) {
			showToast('이동할 조직을 선택해주세요', 'warning');
			return;
		}

		if (!targetCategoryIdx) {
			showToast('이동할 카테고리를 선택해주세요', 'warning');
			$('#moveToCategory').focus();
			return;
		}

		const orgIds = selectedOrgs.map(org => org.org_id);

		// 이동 버튼 비활성화
		$('#confirmMoveOrgBtn').prop('disabled', true);

		$.ajax({
			url: '/mng/mng_org/move_to_category',
			type: 'POST',
			data: {
				org_ids: orgIds,
				category_idx: targetCategoryIdx === 'uncategorized' ? 'uncategorized' : targetCategoryIdx
			},
			dataType: 'json',
			success: function(response) {
				$('#confirmMoveOrgBtn').prop('disabled', false);
				$('#moveOrgModal').modal('hide');

				showToast(response.message, response.success ? 'success' : 'error');

				if (response.success) {
					console.log('조직 이동 성공 - 트리와 그리드 새로고침 시작');

					// 트리와 그리드 새로고침 - 카운트 업데이트 포함
					refreshTreeAndGrid();
				}
			},
			error: function() {
				$('#confirmMoveOrgBtn').prop('disabled', false);
				$('#moveOrgModal').modal('hide');
				showToast('조직 이동 중 오류가 발생했습니다', 'error');
			}
		});
	}

	/**
	 * 트리와 그리드 새로고침
	 */
	function refreshTreeAndGrid() {
		console.log('트리와 그리드 새로고침 시작');

		// 체크된 조직 ID 초기화
		checkedOrgIds.clear();

		// 현재 선택된 카테고리 정보 임시 저장
		const currentSelectedIdx = selectedCategoryIdx;
		const currentSelectedName = selectedCategoryName;

		// 트리 새로고침 - 서버에서 최신 데이터 가져와서 재구성
		showTreeSpinner();

		Promise.all([
			$.get('/mng/mng_org/get_category_tree'),
			$.get('/mng/mng_org/get_total_org_count')
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

				// 서버에서 계산된 전체 조직 수 사용
				const totalOrgCount = totalCountResponse.total_count || 0;

				// 트리 데이터 구성
				const treeData = [
					{
						key: 'all',
						title: `전체 (${totalOrgCount}개)`,
						folder: true,
						expanded: true,
						data: {
							type: 'all',
							category_idx: null,
							category_name: '전체'
						},
						children: categoryNodes
					}
				];

				// 미분류는 항상 추가
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

					if (currentSelectedIdx === 'uncategorized') {
						// 미분류였다면 미분류 노드 찾기
						nodeToActivate = tree.getNodeByKey('uncategorized');
					} else if (currentSelectedIdx === null) {
						// 전체였다면 전체 노드 찾기
						nodeToActivate = tree.getNodeByKey('all');
					} else {
						// 특정 카테고리였다면 해당 노드 찾기
						nodeToActivate = tree.getNodeByKey('category_' + currentSelectedIdx);
					}

					if (nodeToActivate) {
						nodeToActivate.setActive();
						console.log('이전 선택 노드 복원:', nodeToActivate.title);
					} else {
						// 노드를 찾지 못했다면 전체 선택
						const allNode = tree.getNodeByKey('all');
						if (allNode) {
							allNode.setActive();
						}
					}
				}, 100);

				console.log('트리 새로고침 완료 - 카운트 업데이트됨');

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
			loadOrgList();
		});
	}

	/**
	 * 선택된 조직 수 업데이트 (기존 함수 수정)
	 */
	function updateSelectedCount() {
		const count = checkedOrgIds.size;
		$('#selectedCount').text(count);

		// 모든 관련 버튼들의 상태 업데이트
		const isDisabled = count === 0;
		$('#btnOrgMap, #btnOrgMapMobile').prop('disabled', isDisabled);
		$('#btnDeleteOrg, #btnDeleteOrgMobile').prop('disabled', isDisabled);
		$('#btnMoveOrg, #btnMoveOrgMobile').prop('disabled', isDisabled);
	}
	/**
	 * 삭제 확인 모달 표시
	 */
	function showDeleteModal() {
		const selectedOrgs = getSelectedOrgs();

		if (selectedOrgs.length === 0) {
			showToast('삭제할 조직을 선택해주세요', 'warning');
			return;
		}

		const deleteListHtml = selectedOrgs.map(org => `
          <li class="list-group-item d-flex justify-content-between align-items-center">
             <div>
                <strong>${org.org_name || '이름 없음'}</strong>
                <br><small class="text-muted">${org.org_code || '코드 없음'}</small>
             </div>
             <span class="badge bg-info rounded-pill">${org.member_count || 0}명</span>
          </li>
       `).join('');

		$('#deleteOrgList').html(`<ul class="list-group list-group-flush">${deleteListHtml}</ul>`);
		$('#deleteOrgModal').modal('show');
	}

	/**
	 * 선택된 조직 정보 반환
	 */
	function getSelectedOrgs() {
		const selectedOrgs = [];

		if (!orgGrid) {
			return selectedOrgs;
		}

		try {
			const gridData = orgGrid.pqGrid('option', 'dataModel.data');

			if (!gridData || !Array.isArray(gridData)) {
				return selectedOrgs;
			}

			// 체크된 각 ID에 대해 그리드에서 해당 데이터 찾기
			checkedOrgIds.forEach(checkedOrgId => {
				const orgData = gridData.find(row => {
					const rowOrgId = parseInt(row.org_id);
					return rowOrgId === checkedOrgId;
				});

				if (orgData) {
					selectedOrgs.push(orgData);
				}
			});

		} catch (error) {
			console.error('getSelectedOrgs 오류:', error);
		}

		return selectedOrgs;
	}

	/**
	 * 삭제 실행
	 */
	function executeDelete() {
		const orgIds = Array.from(checkedOrgIds);

		if (orgIds.length === 0) {
			showToast('삭제할 조직을 선택해주세요', 'warning');
			return;
		}

		$.ajax({
			url: '/mng/mng_org/bulk_delete_orgs',
			type: 'POST',
			data: { org_ids: orgIds },
			dataType: 'json',
			success: function(response) {
				$('#deleteOrgModal').modal('hide');
				showToast(response.message, response.success ? 'success' : 'error');

				if (response.success) {
					loadOrgList();
				}
			},
			error: function() {
				$('#deleteOrgModal').modal('hide');
				showToast('조직 삭제 중 오류가 발생했습니다', 'error');
			}
		});
	}

	/**
	 * 선택된 제목 업데이트
	 */
	function updateSelectedTitle() {
		$('#selectedOrgName').html(`${selectedCategoryName}`);
	}

	/**
	 * 조직 유형 텍스트 변환
	 */
	function getOrgTypeText(orgType) {
		const types = {
			'church': '교회',
			'school': '학교',
			'company': '회사',
			'organization': '단체'
		};
		return types[orgType] || orgType || '미분류';
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

	function showOffcanvasSpinner() {
		$('#orgOffcanvasSpinner').show();
		$('#orgForm').hide();
	}

	function hideOffcanvasSpinner() {
		$('#orgOffcanvasSpinner').hide();
		$('#orgForm').show();
	}

	/**
	 * 디바운스 유틸리티 함수
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
	 * 조직 지도 팝업 열기
	 */
	function openOrgMapPopup() {
		// 체크된 조직 개수 확인
		if (checkedOrgIds.size === 0) {
			showToast('지도에 표시할 조직을 선택해주세요', 'warning');
			return;
		}

		// 선택된 조직 데이터 가져오기
		const selectedOrgs = getSelectedOrgs();

		if (selectedOrgs.length === 0) {
			showToast('선택된 조직 데이터를 찾을 수 없습니다', 'error');
			return;
		}

		// 주소가 있는 조직만 필터링
		const orgsWithAddress = selectedOrgs.filter(org => {
			return org.org_address && org.org_address.trim() !== '';
		});

		if (orgsWithAddress.length === 0) {
			showToast('주소 정보가 있는 조직이 없습니다', 'warning');
			return;
		}

		// 세션 스토리지에 데이터 저장
		sessionStorage.setItem('orgMapData', JSON.stringify(orgsWithAddress));

		// 팝업 열기
		const popupWidth = 1200;
		const popupHeight = 800;
		const left = (screen.width - popupWidth) / 2;
		const top = (screen.height - popupHeight) / 2;

		const popup = window.open(
			'/mng/mng_org/org_map_popup',
			'orgMapPopup',
			`width=${popupWidth},height=${popupHeight},left=${left},top=${top},resizable=yes,scrollbars=yes`
		);

		if (!popup) {
			showToast('팝업 차단을 해제해주세요', 'error');
			return;
		}
	}

})(); // 즉시 실행 함수 종료
