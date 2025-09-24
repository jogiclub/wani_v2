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
				splitInstance = Split(['#left-pane', '#right-pane'], {
					sizes: [20, 80],
					minSize: [200, 400],
					gutterSize: 8,
					cursor: 'col-resize',
					direction: 'horizontal',
					onDragEnd: function(sizes) {
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
				console.log('Split.js 초기화 완료');
			} catch(error) {
				console.error('Split.js 초기화 실패:', error);
			}
		}, 200);
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

				const tree = $.ui.fancytree.getTree('#categoryTree');
				const firstNode = tree.getNodeByKey('all');
				if (firstNode) {
					firstNode.setActive();
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
			const colModel = createColumnModel();

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
				align: 'center',
				editable: false,
				render: function(ui) {
					return getOrgTypeText(ui.cellData);
				}
			},
			{
				dataIndx: 'org_rep',
				title: '대표자',
				width: 80,
				align: 'center',
				editable: false,
				render: function(ui) {
					return getOrgTypeText(ui.cellData);
				}
			},
			{
				dataIndx: 'org_manager',
				title: '담당자',
				width: 80,
				align: 'center',
				editable: false,
				render: function(ui) {
					return getOrgTypeText(ui.cellData);
				}
			},
			{
				dataIndx: 'org_phone',
				title: '연락처',
				width: 80,
				align: 'center',
				editable: false,
				render: function(ui) {
					return getOrgTypeText(ui.cellData);
				}
			},
			{
				dataIndx: 'org_address_postno',
				title: '우편번호',
				width: 80,
				align: 'center',
				editable: false,
				render: function(ui) {
					return getOrgTypeText(ui.cellData);
				}
			},
			{
				dataIndx: 'org_address',
				title: '주소',
				width: 150,
				align: 'center',
				editable: false,
				render: function(ui) {
					return getOrgTypeText(ui.cellData);
				}
			},
			{
				dataIndx: 'org_address_detail',
				title: '상세주소',
				width: 150,
				align: 'center',
				editable: false,
				render: function(ui) {
					return getOrgTypeText(ui.cellData);
				}
			},
			{
				dataIndx: 'member_count',
				title: '회원수',
				width: 80,
				align: 'center',
				editable: false,
				render: function(ui) {
					const count = ui.cellData || 0;
					return `<span class="badge bg-info">${count}명</span>`;
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
						allowClear: true,
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
		const orgName = $('#orgDashboardBtn').attr('title').replace(' 대시보드 바로가기', '');

		if (!orgId) {
			console.error('조직 ID를 찾을 수 없음');
			showToast('조직 정보를 찾을 수 없습니다', 'error');
			return;
		}

		// 조직 변경 후 대시보드로 이동하는 폼 생성
		const form = $('<form>', {
			'method': 'POST',
			'action': '/dashboard',
			'target': '_blank'
		});

		// CSRF 토큰이 있으면 추가
		const csrfToken = $('meta[name="csrf-token"]').attr('content');
		if (csrfToken) {
			form.append($('<input>', {
				'type': 'hidden',
				'name': 'csrf_token',
				'value': csrfToken
			}));
		}

		// 조직 ID 추가
		form.append($('<input>', {
			'type': 'hidden',
			'name': 'org_id',
			'value': orgId
		}));

		// 폼을 body에 추가하고 제출
		$('body').append(form);
		form.submit();
		form.remove();

		console.log('조직 변경 후 대시보드 이동:', orgId, orgName);
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
	 * 전역 이벤트 바인딩 (기존 함수에 추가)
	 */
	function bindGlobalEvents() {
		$('#btnDeleteOrg').on('click', showDeleteModal);
		$('#confirmDeleteOrgBtn').on('click', executeDelete);
		$('#saveOrgBtn').on('click', saveOrgInfo);

		// 조직 이동 이벤트 - 데스크톱과 모바일 버튼 모두
		$('#btnMoveOrg, #btnMoveOrgMobile').on('click', showMoveModal);
		$('#confirmMoveOrgBtn').on('click', executeMoveOrgs);

		// 삭제 이벤트도 모바일 버튼 추가
		$('#btnDeleteOrgMobile').on('click', showDeleteModal);

		// 바로가기 버튼 이벤트 추가
		$('#orgDashboardBtn').on('click', handleDashboardButtonClick);

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
	 * Toast 메시지 표시
	 */
	function showToast(message, type = 'info') {
		const toast = $('#liveToast');
		const toastBody = toast.find('.toast-body');

		// 기존 클래스 제거
		toast.removeClass('text-bg-success text-bg-danger text-bg-warning text-bg-info');

		// 타입별 클래스 추가
		switch(type) {
			case 'success':
				toast.addClass('text-bg-success');
				break;
			case 'error':
			case 'danger':
				toast.addClass('text-bg-danger');
				break;
			case 'warning':
				toast.addClass('text-bg-warning');
				break;
			default:
				toast.addClass('text-bg-info');
		}

		toastBody.text(message);

		try {
			const bsToast = new bootstrap.Toast(toast[0]);
			bsToast.show();
		} catch(e) {
			console.warn('Toast 표시 실패:', e);
		}
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

})(); // 즉시 실행 함수 종료
