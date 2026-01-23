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
	let searchStatusTags = [];  // 선택된 태그 배열
	let searchKeyword = '';
	let allStatusTags = [];     // 전체 태그 목록

// 조직일괄변경 관련 변수
	let orgChangeSelectedMembers = [];
	let orgChangeDroppedData = {}; // { org_id: [member, member, ...] }
	let categoryList = [];
	let currentCategoryOrgs = [];
	let orgChangeMode = 'move'; // 'move' 또는 'copy'


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
		initSearchControls();  // 검색 컨트롤 초기화 추가
		bindGlobalEvents();

		console.log('회원관리 페이지 초기화 완료');
	}




	/**
	 * 파일 위치: assets/js/mng_member.js
	 * 역할: 검색 컨트롤 초기화 - Dropdown Menu 방식
	 */
	function initSearchControls() {
		// 관리tag 드롭다운 초기화
		loadStatusTagsForDropdown();

		// 검색 버튼 클릭 이벤트
		$('#btnSearch').off('click').on('click', function() {
			executeSearch();
		});

		// 검색 초기화 버튼 클릭 이벤트
		$('#btnSearchReset').off('click').on('click', function() {
			resetSearchConditions();
			loadMemberList();
		});

		// 엔터키로 검색
		$('#searchKeyword').off('keypress').on('keypress', function(e) {
			if (e.which === 13) {
				executeSearch();
			}
		});

		// 드롭다운 이벤트 바인딩
		bindTagDropdownEvents();
	}


	/**
	 * 파일 위치: assets/js/mng_member.js
	 * 역할: 검색 조건 초기화
	 */
	function resetSearchConditions() {
		searchKeyword = '';
		searchStatusTags = [];
		$('#searchKeyword').val('');
		$('#searchTag_all').prop('checked', true);
		$('.status-tag-checkbox').prop('checked', false);
		updateTagButtonText();
	}


	/**
	 * 파일 위치: assets/js/mng_member.js
	 * 역할: 관리tag 목록 로드 및 드롭다운 메뉴 구성
	 */
	function loadStatusTagsForDropdown() {
		$.ajax({
			url: '/mng/mng_member/get_existing_status_tags',
			type: 'GET',
			dataType: 'json',
			success: function(response) {
				if (response && response.success && response.data) {
					allStatusTags = response.data;
					updateTagDropdownMenu();
				}
			},
			error: function() {
				console.warn('관리tag 목록 로드 실패');
			}
		});
	}

	/**
	 * 파일 위치: assets/js/mng_member.js
	 * 역할: 관리tag 드롭다운 메뉴 업데이트
	 */
	function updateTagDropdownMenu() {
		const $menu = $('#searchTagMenu');

		// 기존 항목 제거 (전체와 구분선은 유지)
		$menu.find('li:gt(1)').remove();

		// 관리tag 항목들 추가
		allStatusTags.forEach(function(tag) {
			const itemId = 'searchTag_' + tag.replace(/\s+/g, '_').replace(/[()]/g, '');
			const $li = $('<li></li>');
			const $div = $('<div class="dropdown-item"></div>');
			const $checkbox = $('<input type="checkbox" class="form-check-input me-2 status-tag-checkbox">');
			$checkbox.attr('id', itemId);
			$checkbox.attr('value', tag);
			const $label = $('<label class="form-check-label"></label>');
			$label.attr('for', itemId);
			$label.text(tag);

			$div.append($checkbox);
			$div.append($label);
			$li.append($div);
			$menu.append($li);
		});

		// 이벤트 재바인딩
		bindTagDropdownEvents();
	}

	/**
	 * 파일 위치: assets/js/mng_member.js
	 * 역할: 관리tag 드롭다운 이벤트 바인딩
	 */
	function bindTagDropdownEvents() {
		// 전체 체크박스 클릭
		$('#searchTag_all').off('change').on('change', function() {
			const isChecked = $(this).is(':checked');

			if (isChecked) {
				// 전체 선택 시 다른 체크박스 해제
				$('.status-tag-checkbox').prop('checked', false);
				searchStatusTags = [];
				updateTagButtonText();
				executeSearch();
			}
		});

		// 개별 태그 체크박스 클릭
		$('.status-tag-checkbox').off('change').on('change', function() {
			const value = $(this).val();
			const isChecked = $(this).is(':checked');

			if (isChecked) {
				// 전체 체크 해제
				$('#searchTag_all').prop('checked', false);
				// 배열에 추가
				if (!searchStatusTags.includes(value)) {
					searchStatusTags.push(value);
				}
			} else {
				// 배열에서 제거
				searchStatusTags = searchStatusTags.filter(function(tag) {
					return tag !== value;
				});

				// 모두 해제되면 전체 체크
				if (searchStatusTags.length === 0) {
					$('#searchTag_all').prop('checked', true);
				}
			}

			updateTagButtonText();
			executeSearch();
		});

		// 드롭다운 아이템 클릭 시 드롭다운 닫히지 않도록
		$('#searchTagMenu .dropdown-item').off('click').on('click', function(e) {
			e.stopPropagation();
		});
	}

	/**
	 * 파일 위치: assets/js/mng_member.js
	 * 역할: 관리tag 버튼 텍스트 업데이트
	 */
	function updateTagButtonText() {
		const $btn = $('#searchTagText');

		if (searchStatusTags.length === 0) {
			$btn.text('관리tag 전체');
		} else if (searchStatusTags.length === 1) {
			$btn.text(searchStatusTags[0]);
		} else {
			$btn.text(searchStatusTags[0] + ' 외 ' + (searchStatusTags.length - 1) + '개');
		}
	}

	/**
	 * 파일 위치: assets/js/mng_member.js
	 * 역할: 검색용 관리tag Select2 초기화 (다중 선택 + 태그 입력)
	 */
	function initSearchStatusTagSelect() {
		// 기존 Select2 인스턴스 제거
		if ($('#searchStatusTag').hasClass('select2-hidden-accessible')) {
			$('#searchStatusTag').select2('destroy');
		}

		// 옵션 초기화
		$('#searchStatusTag').empty();

		// 기존 태그 목록 로드
		$.ajax({
			url: '/mng/mng_member/get_existing_status_tags',
			type: 'GET',
			dataType: 'json',
			success: function(response) {
				if (response && response.success && response.data) {
					response.data.forEach(function(tag) {
						$('#searchStatusTag').append('<option value="' + tag + '">' + tag + '</option>');
					});
				}

				// Select2 초기화 (다중 선택 + 태그 입력 가능)
				$('#searchStatusTag').select2({
					width: '100%',
					placeholder: '관리tag 검색',
					allowClear: true,
					multiple: true,
					tags: true,
					tokenSeparators: [','],
					minimumResultsForSearch: 0,
					createTag: function(params) {
						const term = $.trim(params.term);
						if (term === '') {
							return null;
						}
						return {
							id: term,
							text: term,
							newTag: true
						};
					},
					templateResult: function(tag) {
						if (!tag.id) {
							return tag.text;
						}
						if (tag.newTag) {
							return $('<span><i class="bi bi-plus-circle me-1"></i>' + tag.text + ' (검색)</span>');
						}
						return tag.text;
					},
					templateSelection: function(tag) {
						return tag.text;
					}
				});
			},
			error: function() {
				// Select2 기본 초기화
				$('#searchStatusTag').select2({
					width: '100%',
					placeholder: '관리tag 검색',
					allowClear: true,
					multiple: true,
					tags: true
				});
			}
		});
	}

	/**
	 * 파일 위치: assets/js/mng_member.js
	 * 역할: 검색 실행
	 */
	function executeSearch() {
		searchKeyword = $('#searchKeyword').val().trim();
		loadMemberList();
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


		// 트리 선택 상태 저장
		saveSelectedTreeNode(nodeData);

		updateSelectedTitle();
		// resetSearchConditions();  // 트리 변경 시 검색 조건 초기화하려면 주석 해제
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

		// 버튼 상태 업데이트 (엑셀다운로드는 항상 enabled)
		const isDisabled = count === 0;
		$('#btnStatusChange').prop('disabled', isDisabled);
		$('#btnOrgChange').prop('disabled', isDisabled);
		$('#btnSendMember').prop('disabled', isDisabled);
	}

	/**
	 * 파일 위치: assets/js/mng_member.js
	 * 역할: 회원 목록 로드
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

		// 검색 조건 추가
		if (searchStatusTags && searchStatusTags.length > 0) {
			params.status_tags = searchStatusTags.join(',');
		}
		if (searchKeyword) {
			params.keyword = searchKeyword;
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
	 * 파일 위치: assets/js/mng_member.js
	 * 역할: 선택문자 발송 처리
	 */
	function handleSendMember() {
		const selectedMembers = getSelectedMembers();

		if (selectedMembers.length === 0) {
			showToast('발송할 회원을 선택해주세요.', 'warning');
			return;
		}

		// 전화번호가 없는 회원 체크
		const membersWithoutPhone = selectedMembers.filter(function(member) {
			return !member.member_phone || member.member_phone.trim() === '';
		});

		if (membersWithoutPhone.length > 0) {
			const memberNames = membersWithoutPhone.map(function(member) {
				return member.member_name || '이름없음';
			}).join(', ');

			showConfirmModal(
				'전화번호 누락 회원 확인',
				'다음 회원들은 전화번호가 없어 발송 대상에서 제외됩니다.\n' + memberNames + '\n\n계속 진행하시겠습니까?',
				function() {
					const validMembers = selectedMembers.filter(function(member) {
						return member.member_phone && member.member_phone.trim() !== '';
					});
					if (validMembers.length === 0) {
						showToast('발송 가능한 회원이 없습니다.', 'warning');
						return;
					}
					openSendPopup(validMembers);
				}
			);
		} else {
			openSendPopup(selectedMembers);
		}
	}

	/**
	 * 파일 위치: assets/js/mng_member.js
	 * 역할: 문자 발송 팝업 열기 - member_idx 배열을 POST로 전달 (마스터 모드)
	 */
	function openSendPopup(selectedMembers) {
		if (!selectedMembers || selectedMembers.length === 0) {
			showToast('발송할 회원이 없습니다.', 'warning');
			return;
		}

		const popupWidth = 1400;
		const popupHeight = 850;
		const left = (screen.width - popupWidth) / 2;
		const top = (screen.height - popupHeight) / 2;

		// 팝업 창 열기
		const popupWindow = window.open('', 'sendPopup',
			'width=' + popupWidth + ',height=' + popupHeight + ',left=' + left + ',top=' + top + ',scrollbars=yes,resizable=yes');

		if (!popupWindow) {
			showToast('팝업이 차단되었습니다. 팝업 차단을 해제해주세요.', 'error');
			return;
		}

		// 임시 폼 생성하여 POST로 데이터 발송
		const tempForm = $('<form>', {
			'method': 'POST',
			'action': '/send/popup',
			'target': 'sendPopup'
		});

		// 마스터 모드 플래그 추가
		tempForm.append($('<input>', {
			'type': 'hidden',
			'name': 'master_mode',
			'value': 'Y'
		}));

		// member_idx 배열로 전송
		selectedMembers.forEach(function(member) {
			tempForm.append($('<input>', {
				'type': 'hidden',
				'name': 'member_ids[]',
				'value': member.member_idx
			}));
		});

		$('body').append(tempForm);
		tempForm.submit();
		tempForm.remove();

		// 팝업이 닫힐 때까지 포커스 유지
		const checkClosed = setInterval(function() {
			if (popupWindow.closed) {
				clearInterval(checkClosed);
			}
		}, 1000);
	}


	/**
	 * 파일 위치: assets/js/mng_member.js
	 * 역할: 선택된 회원 목록 가져오기
	 */
	function getSelectedMembers() {
		const selectedMembers = [];

		if (!memberGrid) {
			console.warn('memberGrid가 초기화되지 않았습니다.');
			return selectedMembers;
		}

		try {
			const gridData = memberGrid.pqGrid('option', 'dataModel.data');

			if (!gridData || !Array.isArray(gridData)) {
				console.warn('그리드 데이터가 없습니다.');
				return selectedMembers;
			}

			// checkedMemberIds Set에서 체크된 회원 ID 가져오기
			checkedMemberIds.forEach(function(checkedMemberIdx) {
				const memberData = gridData.find(function(row) {
					return parseInt(row.member_idx) === parseInt(checkedMemberIdx);
				});

				if (memberData) {
					selectedMembers.push({
						member_idx: memberData.member_idx,
						member_name: memberData.member_name || '',
						member_phone: memberData.member_phone || '',
						org_name: memberData.org_name || ''
					});
				}
			});

			console.log('선택된 회원:', selectedMembers.length + '명');

		} catch (error) {
			console.error('getSelectedMembers 오류:', error);
		}

		return selectedMembers;
	}


	/**
	 * 파일 위치: assets/js/mng_member.js
	 * 역할: 글로벌 이벤트 바인딩 - 이벤트 위임 방식으로 수정
	 * 위치: bindGlobalEvents() 함수
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

		// 선택문자 버튼
		$('#btnSendMember').off('click').on('click', function() {
			handleSendMember();
		});

		// 엑셀다운로드 버튼
		$('#btnExcelDownload').off('click').on('click', function() {
			exportMemberToExcel();
		});

		// 윈도우 리사이즈 이벤트
		$(window).on('resize', debounce(function() {
			if (memberGrid) {
				try {
					memberGrid.pqGrid("refresh");
				} catch(error) {
					console.warn('그리드 리사이즈 실패:', error);
				}
			}
		}, 250));


		// 조직일괄변경 버튼
		$('#btnOrgChange').on('click', function() {
			handleOrgChange();
		});

		// 조직일괄변경 확인 버튼
		$(document).on('click', '#confirmOrgChangeBtn', function() {
			showOrgChangeConfirmModal();
		});

		// 조직일괄변경 실행 버튼
		$(document).on('click', '#executeOrgChangeBtn', function() {
			executeOrgChange();
		});

		// 조직일괄변경 회원 검색
		$(document).on('click', '#btnOrgChangeMemberSearch', function() {
			filterOrgChangeMembers();
		});

		$(document).on('keypress', '#orgChangeMemberSearch', function(e) {
			if (e.which === 13) {
				filterOrgChangeMembers();
			}
		});

		// 그룹(카테고리) 선택 변경 - 이벤트 위임 방식
		$(document).on('change', '#orgChangeTargetCategory', function() {
			console.log('그룹 선택 변경됨:', $(this).val());
			handleCategoryChange();
		});

		// 초기화 버튼
		$(document).on('click', '#btnOrgChangeReset', function() {
			resetOrgChangeAll();
		});
	}


	/**
	 * 파일 위치: assets/js/mng_member.js
	 * 역할: 조직변경 실행 (회원 복사)
	 */
	function executeOrgChange() {
		const copyData = [];
		const modeText = orgChangeMode === 'move' ? '이동' : '복사';

		for (const orgId in orgChangeDroppedData) {
			const members = orgChangeDroppedData[orgId];
			if (members.length === 0) continue;

			copyData.push({
				target_org_id: orgId,
				member_idx_list: members.map(m => m.member_idx)
			});
		}

		if (copyData.length === 0) {
			showToast(modeText + '할 데이터가 없습니다.', 'warning');
			return;
		}

		// 버튼 비활성화
		$('#executeOrgChangeBtn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>처리 중...');

		$.ajax({
			url: '/mng/mng_member/copy_members_to_orgs',
			type: 'POST',
			data: {
				copy_data: JSON.stringify(copyData),
				mode: orgChangeMode  // 'move' 또는 'copy'
			},
			dataType: 'json',
			success: function(response) {
				$('#executeOrgChangeBtn').prop('disabled', false).html(modeText + ' 실행');

				if (response.success) {
					showToast(response.message, 'success');

					// 모달 닫기
					$('#orgChangeConfirmModal').modal('hide');
					$('#orgChangeModal').modal('hide');

					// 그리드 새로고침
					loadMemberList();
				} else {
					showToast(response.message || '회원 복사에 실패했습니다.', 'error');
				}
			},
			error: function() {
				$('#executeOrgChangeBtn').prop('disabled', false).html('복사 실행');
				showToast('회원 복사 중 오류가 발생했습니다.', 'error');
			}
		});
	}

	/**
	 * 파일 위치: assets/js/mng_member.js
	 * 역할: 전체 초기화
	 */
	function resetOrgChangeAll() {
		orgChangeDroppedData = {};
		$('#orgChangeTargetCategory').val('');
		resetOrgChangeOrgArea();
		renderOrgChangeMemberList(orgChangeSelectedMembers);
		$('#orgChangeTotalCount').text('0');
		$('#confirmOrgChangeBtn').prop('disabled', true);
	}


	/**
	 * 파일 위치: assets/js/mng_member.js
	 * 역할: 조직일괄변경 버튼 클릭 핸들러
	 */
	function handleOrgChange() {
		const selectedMembers = getSelectedMembers();

		if (selectedMembers.length === 0) {
			showToast('조직을 변경할 회원을 선택해주세요.', 'warning');
			return;
		}

		// 조직일괄변경 모달 열기
		openOrgChangeModal(selectedMembers);
	}

	/**
	 * 파일 위치: assets/js/mng_member.js
	 * 역할: 조직일괄변경 모달 열기 및 초기화 - 모달 열린 후 데이터 로드
	 */
	function openOrgChangeModal(selectedMembers) {
		// 변수 초기화
		orgChangeSelectedMembers = selectedMembers.slice();
		orgChangeDroppedData = {};
		currentCategoryOrgs = [];
		orgChangeMode = 'move'; // 기본값: 이동

		// 모드 선택 초기화
		$('#orgChangeMode').val('move');
		updateOrgChangeModeUI();

		// 회원 목록 렌더링
		renderOrgChangeMemberList(orgChangeSelectedMembers);
		$('#orgChangeMemberCount').text(orgChangeSelectedMembers.length);

		// 조직 영역 초기화
		resetOrgChangeOrgArea();

		// 검색 필드 초기화
		$('#orgChangeMemberSearch').val('');

		// 저장 버튼 비활성화
		$('#confirmOrgChangeBtn').prop('disabled', true);

		// 총 인원 표시 초기화
		$('#orgChangeTotalCount').text('0');

		// select 초기화
		$('#orgChangeTargetCategory').empty().append('<option value="">그룹을 선택하세요</option>');

		// 모달 표시
		$('#orgChangeModal').modal('show');

		// 모달이 완전히 열린 후 데이터 로드 및 이벤트 바인딩
		$('#orgChangeModal').off('shown.bs.modal').on('shown.bs.modal', function() {
			console.log('조직일괄변경 모달 열림 - 데이터 로드 시작');

			// 그룹(카테고리) 목록 로드
			loadCategoryListForChange();

			// 그룹 선택 이벤트 바인딩
			$('#orgChangeTargetCategory').off('change').on('change', function() {
				console.log('그룹 선택 변경됨:', $(this).val());
				handleCategoryChange();
			});

			// 모드 변경 이벤트 바인딩
			$('#orgChangeMode').off('change').on('change', function() {
				orgChangeMode = $(this).val();
				updateOrgChangeModeUI();
			});
		});
	}

	/**
	 * 파일 위치: assets/js/mng_member.js
	 * 역할: 조직변경 모드(이동/복사) UI 업데이트
	 */
	function updateOrgChangeModeUI() {
		const modeText = orgChangeMode === 'move' ? '이동' : '복사';
		$('#orgChangeMemberLabel').text(modeText);
		$('#orgChangeModeText').text(modeText);
	}

	/**
	 * 파일 위치: assets/js/mng_member.js
	 * 역할: 회원 목록 렌더링 (드래그 가능)
	 */
	function renderOrgChangeMemberList(members) {
		const $container = $('#orgChangeMemberList');
		$container.empty();

		if (members.length === 0) {
			$container.html('<div class="text-center text-muted py-3">회원이 없습니다</div>');
			return;
		}

		members.forEach(function(member) {
			// 이미 어딘가에 드롭된 회원인지 확인
			const droppedOrgId = getMemberDroppedOrg(member.member_idx);

			const $item = $(`
			<div class="org-change-member-item p-2 mb-1 rounded border bg-white ${droppedOrgId ? 'opacity-50' : ''}" 
				 data-member-idx="${member.member_idx}"
				 data-member-name="${member.member_name || ''}"
				 data-member-phone="${member.member_phone || ''}"
				 data-org-name="${member.org_name || ''}"
				 draggable="${droppedOrgId ? 'false' : 'true'}">
				<div class="d-flex align-items-center">
					<i class="bi bi-grip-vertical text-muted me-2 drag-handle"></i>
					<div class="flex-grow-1">
						<div class="fw-semibold small">${member.member_name || '이름없음'}<small class="ms-2 text-muted" style="font-size: 0.75rem;">${member.org_name || '소속없음'}</small></div>						
					</div>
					${droppedOrgId ? '<span class="badge bg-success" style="font-size: 0.65rem;">추가됨</span>' : ''}
				</div>
			</div>
		`);

			if (!droppedOrgId) {
				// 드래그 이벤트 바인딩
				$item.on('dragstart', function(e) {
					e.originalEvent.dataTransfer.setData('text/plain', JSON.stringify({
						member_idx: member.member_idx,
						member_name: member.member_name,
						member_phone: member.member_phone,
						org_name: member.org_name
					}));
					$(this).addClass('dragging');
				});

				$item.on('dragend', function() {
					$(this).removeClass('dragging');
				});
			}

			$container.append($item);
		});
	}

	/**
	 * 파일 위치: assets/js/mng_member.js
	 * 역할: 회원이 드롭된 조직 ID 반환
	 */
	function getMemberDroppedOrg(memberIdx) {
		const memberIdxInt = parseInt(memberIdx);
		for (const orgId in orgChangeDroppedData) {
			if (orgChangeDroppedData[orgId].some(function(m) { return parseInt(m.member_idx) === memberIdxInt; })) {
				return orgId;  // 문자열 orgId 반환
			}
		}
		return null;
	}
	/**
	 * 파일 위치: assets/js/mng_member.js
	 * 역할: 그룹(카테고리) 목록 로드
	 */
	function loadCategoryListForChange() {
		$.ajax({
			url: '/mng/mng_member/get_category_list_for_change',
			type: 'GET',
			dataType: 'json',
			success: function(response) {
				if (response.success) {
					categoryList = response.data || [];
					renderCategorySelectOptions(categoryList);
				} else {
					showToast(response.message || '그룹 목록을 불러오는데 실패했습니다', 'error');
				}
			},
			error: function() {
				showToast('그룹 목록을 불러오는데 실패했습니다', 'error');
			}
		});
	}


	/**
	 * 파일 위치: assets/js/mng_member.js
	 * 역할: 그룹(카테고리) 선택 옵션 렌더링 (계층 구조)
	 */
	function renderCategorySelectOptions(categories) {
		const $select = $('#orgChangeTargetCategory');
		$select.empty();
		$select.append('<option value="">그룹을 선택하세요</option>');

		categories.forEach(function(cat) {
			// category_name에 이미 들여쓰기가 포함되어 있음
			$select.append(`<option value="${cat.category_idx}" data-category-name="${cat.category_name}">${cat.category_name}</option>`);
		});
	}

	/**
	 * 파일 위치: assets/js/mng_member.js
	 * 역할: 그룹(카테고리) 선택 변경 핸들러
	 */
	function handleCategoryChange() {
		const selectedCategoryIdx = $('#orgChangeTargetCategory').val();

		if (!selectedCategoryIdx) {
			resetOrgChangeOrgArea();
			return;
		}

		// 선택된 그룹의 조직 목록 로드
		loadOrgsByCategory(selectedCategoryIdx);
	}


	/**
	 * 파일 위치: assets/js/mng_member.js
	 * 역할: 그룹(카테고리)에 속한 조직 목록 로드 - 디버깅 추가
	 */
	function loadOrgsByCategory(categoryIdx) {
		console.log('loadOrgsByCategory 호출 - categoryIdx:', categoryIdx);

		if (!categoryIdx) {
			console.warn('categoryIdx가 없습니다');
			return;
		}

		$.ajax({
			url: '/mng/mng_member/get_orgs_by_category',
			type: 'GET',
			data: { category_idx: categoryIdx },
			dataType: 'json',
			success: function(response) {
				console.log('get_orgs_by_category 응답:', response);

				if (response.success) {
					currentCategoryOrgs = response.data || [];
					console.log('조직 목록 수:', currentCategoryOrgs.length);
					renderOrgListForDrop(currentCategoryOrgs);
					$('#orgChangeOrgCount').text(currentCategoryOrgs.length);
				} else {
					showToast(response.message || '조직 목록을 불러오는데 실패했습니다', 'error');
				}
			},
			error: function(xhr, status, error) {
				console.error('get_orgs_by_category 오류:', status, error);
				console.error('응답:', xhr.responseText);
				showToast('조직 목록을 불러오는데 실패했습니다', 'error');
			}
		});
	}

	/**
	 * 파일 위치: assets/js/mng_member.js
	 * 역할: 조직 목록 렌더링 (드롭존) - 드롭된 회원도 드래그하여 다른 조직으로 이동 가능
	 */
	function renderOrgListForDrop(orgs) {
		const $container = $('#orgChangeOrgList');
		$container.empty();

		if (orgs.length === 0) {
			$container.html(`
		<div class="text-center text-muted py-5">
			<i class="bi bi-building-x fs-1"></i>
			<p class="mt-2 mb-0">선택한 그룹에 조직이 없습니다</p>
		</div>
	`);
			return;
		}

		orgs.forEach(function(org) {
			const droppedMembers = orgChangeDroppedData[org.org_id] || [];
			const memberCount = droppedMembers.length;

			const $orgCard = $(`			
			<div class="org-drop-card border rounded p-2 mb-2 bg-light" data-org-id="${org.org_id}" data-org-name="${org.org_name}">
				<div class="d-flex align-items-center justify-content-between mb-2">
					<div class="d-flex align-items-center justify-content-between">
						<div class="fw-semibold"><i class="bi bi-building"></i> ${org.org_name}</div>						
					</div>
					<span class="badge ${memberCount > 0 ? 'bg-primary' : 'bg-secondary'}">${memberCount}명 추가</span>
				</div>
				<div class="org-drop-zone border-2 border-dashed rounded p-2 bg-white text-center" data-org-id="${org.org_id}" style="min-height: 60px;">
					${memberCount > 0 ? '' : '<small class="text-muted">회원을 여기에 드롭하세요</small>'}
					<div class="dropped-members-list"></div>
				</div>
			</div>			
	`);

			// 드롭된 회원 목록 표시 (드래그 가능하도록 설정)
			if (memberCount > 0) {
				const $droppedList = $orgCard.find('.dropped-members-list');
				droppedMembers.forEach(function(member) {
					const $memberItem = $(`
				<div class="dropped-member-in-org d-flex align-items-center justify-content-between p-1 mb-1 bg-success bg-opacity-10 rounded small"
					 draggable="true"
					 data-member-idx="${member.member_idx}"
					 data-member-name="${member.member_name || ''}"
					 data-member-phone="${member.member_phone || ''}"
					 data-org-name="${member.org_name || ''}"
					 data-source-org-id="${org.org_id}"
					 style="cursor: grab;">
					<div class="d-flex align-items-center">
						<i class="bi bi-grip-vertical text-muted me-1"></i>
						<span>${member.member_name}</span>
					</div>
					<button type="button" class="btn btn-sm btn-link text-danger p-0 btn-remove-from-org" 
							data-org-id="${org.org_id}" data-member-idx="${member.member_idx}">
						<i class="bi bi-x"></i>
					</button>
				</div>
			`);

					// 드롭된 회원에게 드래그 이벤트 바인딩 (다른 조직으로 이동 가능)
					$memberItem.on('dragstart', function(e) {
						e.originalEvent.dataTransfer.setData('text/plain', JSON.stringify({
							member_idx: member.member_idx,
							member_name: member.member_name,
							member_phone: member.member_phone,
							org_name: member.org_name,
							source_org_id: org.org_id  // 현재 드롭되어 있는 조직 ID
						}));
						$(this).addClass('dragging opacity-50');
					});

					$memberItem.on('dragend', function() {
						$(this).removeClass('dragging opacity-50');
					});

					$droppedList.append($memberItem);
				});
			}

			// 드롭존 이벤트 바인딩
			initOrgDropZoneEvents($orgCard.find('.org-drop-zone'));

			$container.append($orgCard);
		});

		// 이벤트 위임 방식으로 삭제 버튼 이벤트 바인딩 (동적 요소에 대응)
		$container.off('click', '.btn-remove-from-org').on('click', '.btn-remove-from-org', function(e) {
			e.preventDefault();
			e.stopPropagation();
			const orgId = String($(this).data('org-id'));
			const memberIdx = parseInt($(this).data('member-idx'));
			console.log('삭제 버튼 클릭 - orgId:', orgId, 'memberIdx:', memberIdx);
			removeMemberFromOrg(orgId, memberIdx);
		});
	}

	/**
	 * 파일 위치: assets/js/mng_member.js
	 * 역할: 조직에서 회원 제거
	 */
	function removeMemberFromOrg(orgId, memberIdx) {
		// 타입 변환하여 비교
		const orgIdStr = String(orgId);
		const memberIdxInt = parseInt(memberIdx);

		console.log('removeMemberFromOrg 호출 - orgId:', orgIdStr, 'memberIdx:', memberIdxInt);
		console.log('현재 orgChangeDroppedData:', orgChangeDroppedData);

		if (orgChangeDroppedData[orgIdStr]) {
			orgChangeDroppedData[orgIdStr] = orgChangeDroppedData[orgIdStr].filter(function(m) {
				return parseInt(m.member_idx) !== memberIdxInt;
			});

			// 빈 배열이면 키 삭제
			if (orgChangeDroppedData[orgIdStr].length === 0) {
				delete orgChangeDroppedData[orgIdStr];
			}
		}

		// UI 업데이트
		renderOrgListForDrop(currentCategoryOrgs);
		renderOrgChangeMemberList(orgChangeSelectedMembers);
		updateOrgChangeSummary();
		updateOrgChangeConfirmButton();
	}

	/**
	 * 파일 위치: assets/js/mng_member.js
	 * 역할: 복사 현황 요약 업데이트
	 */
	function updateOrgChangeSummary() {
		const totalMembers = getTotalDroppedMemberCount();
		$('#orgChangeTotalCount').text(totalMembers);
	}

	/**
	 * 파일 위치: assets/js/mng_member.js
	 * 역할: 드롭된 총 회원 수 계산
	 */
	function getTotalDroppedMemberCount() {
		let total = 0;
		for (const orgId in orgChangeDroppedData) {
			total += orgChangeDroppedData[orgId].length;
		}
		return total;
	}



	/**
	 * 파일 위치: assets/js/mng_member.js
	 * 역할: 조직 드롭존 이벤트 초기화
	 */
	function initOrgDropZoneEvents($dropZone) {
		$dropZone.off('dragover dragenter dragleave drop');

		$dropZone.on('dragover', function(e) {
			e.preventDefault();
			$(this).addClass('border-primary bg-primary bg-opacity-10');
		});

		$dropZone.on('dragenter', function(e) {
			e.preventDefault();
			$(this).addClass('border-primary bg-primary bg-opacity-10');
		});

		$dropZone.on('dragleave', function(e) {
			e.preventDefault();
			$(this).removeClass('border-primary bg-primary bg-opacity-10');
		});

		$dropZone.on('drop', function(e) {
			e.preventDefault();
			$(this).removeClass('border-primary bg-primary bg-opacity-10');

			const orgId = $(this).data('org-id');
			const data = e.originalEvent.dataTransfer.getData('text/plain');

			if (data) {
				try {
					const memberData = JSON.parse(data);
					addMemberToOrg(orgId, memberData);
				} catch (err) {
					console.error('드롭 데이터 파싱 오류:', err);
				}
			}
		});
	}

	/**
	 * 파일 위치: assets/js/mng_member.js
	 * 역할: 조직 영역 초기화
	 */
	function resetOrgChangeOrgArea() {
		currentCategoryOrgs = [];
		$('#orgChangeOrgCount').text('0');
		$('#orgChangeOrgList').html(`
		<div class="text-center text-muted py-5" id="orgChangeOrgPlaceholder">
			<i class="bi bi-diagram-3 fs-1"></i>
			<p class="mt-2 mb-0">위에서 그룹을 선택하면<br>해당 그룹의 조직 목록이 표시됩니다</p>
		</div>
	`);
	}

	/**
	 * 파일 위치: assets/js/mng_member.js
	 * 역할: 조직에 회원 추가 - 다른 조직에서 드래그해온 경우 기존 조직에서 제거 후 추가
	 */
	function addMemberToOrg(orgId, memberData) {
		// 타입 일관성을 위해 문자열로 변환
		const orgIdStr = String(orgId);
		const memberIdxInt = parseInt(memberData.member_idx);

		// 이미 다른 조직에 추가된 회원인지 확인
		const existingOrgId = getMemberDroppedOrg(memberIdxInt);

		// source_org_id가 있으면 드롭된 회원에서 드래그해온 것임
		const sourceOrgId = memberData.source_org_id;

		if (existingOrgId) {
			// 같은 조직으로 드롭하는 경우 무시
			if (existingOrgId === orgIdStr) {
				showToast('이미 해당 조직에 추가된 회원입니다.', 'warning');
				return;
			}

			// 다른 조직으로 이동하는 경우: 기존 조직에서 제거
			if (orgChangeDroppedData[existingOrgId]) {
				orgChangeDroppedData[existingOrgId] = orgChangeDroppedData[existingOrgId].filter(function(m) {
					return parseInt(m.member_idx) !== memberIdxInt;
				});

				// 빈 배열이면 키 삭제
				if (orgChangeDroppedData[existingOrgId].length === 0) {
					delete orgChangeDroppedData[existingOrgId];
				}
			}
		}

		// 데이터 구조 초기화
		if (!orgChangeDroppedData[orgIdStr]) {
			orgChangeDroppedData[orgIdStr] = [];
		}

		// 이미 같은 조직에 추가되었는지 다시 확인 (이중 방지)
		if (orgChangeDroppedData[orgIdStr].some(function(m) { return parseInt(m.member_idx) === memberIdxInt; })) {
			showToast('이미 추가된 회원입니다.', 'warning');
			return;
		}

		// 회원 추가 (source_org_id 제거하고 저장)
		const memberToAdd = {
			member_idx: memberIdxInt,
			member_name: memberData.member_name,
			member_phone: memberData.member_phone,
			org_name: memberData.org_name
		};
		orgChangeDroppedData[orgIdStr].push(memberToAdd);

		// UI 업데이트
		renderOrgListForDrop(currentCategoryOrgs);
		renderOrgChangeMemberList(orgChangeSelectedMembers);
		updateOrgChangeSummary();
		updateOrgChangeConfirmButton();
	}

	/**
	 * 파일 위치: assets/js/mng_member.js
	 * 역할: 조직 목록 로드 (변경 대상)
	 */
	function loadOrgListForChange() {
		$.ajax({
			url: '/mng/mng_member/get_org_list_for_change',
			type: 'GET',
			dataType: 'json',
			success: function(response) {
				if (response.success) {
					orgList = response.data || [];
					renderOrgSelectOptions(orgList);
				} else {
					showToast(response.message || '조직 목록을 불러오는데 실패했습니다', 'error');
				}
			},
			error: function() {
				showToast('조직 목록을 불러오는데 실패했습니다', 'error');
			}
		});
	}

	/**
	 * 파일 위치: assets/js/mng_member.js
	 * 역할: 조직 선택 옵션 렌더링
	 */
	function renderOrgSelectOptions(orgs) {
		const $select = $('#orgChangeTargetOrg');
		$select.empty();
		$select.append('<option value="">조직을 선택하세요</option>');

		orgs.forEach(function(org) {
			// 카테고리명이 있으면 함께 표시
			const categoryPrefix = org.category_name ? `[${org.category_name}] ` : '';
			$select.append(`<option value="${org.org_id}" data-org-name="${org.org_name}">${categoryPrefix}${org.org_name}</option>`);
		});
	}

	/**
	 * 파일 위치: assets/js/mng_member.js
	 * 역할: 대상 조직 선택 변경 핸들러
	 */
	function handleTargetOrgChange() {
		const selectedOrgId = $('#orgChangeTargetOrg').val();
		const $dropZone = $('#orgChangeDropZone');
		const $placeholder = $('#orgChangeDropPlaceholder');
		const $droppedContainer = $('#orgChangeDroppedMembers');

		if (!selectedOrgId) {
			$placeholder.removeClass('d-none');
			$droppedContainer.addClass('d-none');
			$('#confirmOrgChangeBtn').prop('disabled', true);
			return;
		}

		// 드롭 영역 활성화
		$placeholder.addClass('d-none');
		$droppedContainer.removeClass('d-none').empty();

		// 드롭존에 안내 메시지 추가
		$droppedContainer.html(`
		<div class="text-center text-muted py-4 drop-guide">
			<i class="bi bi-box-arrow-in-down fs-3"></i>
			<p class="mt-2 mb-0">왼쪽에서 회원을 드래그하여 놓으세요</p>
		</div>
	`);

		// 드래그 앤 드롭 이벤트 바인딩
		initDropZoneEvents();

		updateOrgChangeConfirmButton();
	}

	/**
	 * 파일 위치: assets/js/mng_member.js
	 * 역할: 드롭존 이벤트 초기화
	 */
	function initDropZoneEvents() {
		const $dropZone = $('#orgChangeDropZone');

		$dropZone.off('dragover dragenter dragleave drop');

		$dropZone.on('dragover', function(e) {
			e.preventDefault();
			$(this).addClass('border-primary bg-light');
		});

		$dropZone.on('dragenter', function(e) {
			e.preventDefault();
			$(this).addClass('border-primary bg-light');
		});

		$dropZone.on('dragleave', function(e) {
			e.preventDefault();
			$(this).removeClass('border-primary bg-light');
		});

		$dropZone.on('drop', function(e) {
			e.preventDefault();
			$(this).removeClass('border-primary bg-light');

			const data = e.originalEvent.dataTransfer.getData('text/plain');
			if (data) {
				try {
					const memberData = JSON.parse(data);
					addMemberToDropZone(memberData);
				} catch (err) {
					console.error('드롭 데이터 파싱 오류:', err);
				}
			}
		});
	}

	/**
	 * 파일 위치: assets/js/mng_member.js
	 * 역할: 드롭존에 회원 추가
	 */
	function addMemberToDropZone(memberData) {
		// 이미 추가된 회원인지 확인
		if (orgChangeDroppedMembers.some(m => m.member_idx === memberData.member_idx)) {
			showToast('이미 추가된 회원입니다.', 'warning');
			return;
		}

		// 드롭된 회원 목록에 추가
		orgChangeDroppedMembers.push(memberData);

		// 드롭존 UI 업데이트
		renderDroppedMembers();

		// 왼쪽 회원 목록 업데이트 (드래그 비활성화)
		renderOrgChangeMemberList(orgChangeSelectedMembers);

		// 저장 버튼 상태 업데이트
		updateOrgChangeConfirmButton();
	}


	/**
	 * 파일 위치: assets/js/mng_member.js
	 * 역할: 드롭된 회원 목록 렌더링
	 */
	function renderDroppedMembers() {
		const $container = $('#orgChangeDroppedMembers');
		$container.empty();

		if (orgChangeDroppedMembers.length === 0) {
			$container.html(`
			<div class="text-center text-muted py-4 drop-guide">
				<i class="bi bi-box-arrow-in-down fs-3"></i>
				<p class="mt-2 mb-0">왼쪽에서 회원을 드래그하여 놓으세요</p>
			</div>
		`);
			return;
		}

		orgChangeDroppedMembers.forEach(function(member, index) {
			const $item = $(`
			<div class="dropped-member-item p-2 mb-1 rounded border bg-success bg-opacity-10">
				<div class="d-flex align-items-center">
					<div class="flex-grow-1">
						<div class="fw-semibold">${member.member_name || '이름없음'}</div>
						<small class="text-muted">${member.org_name || '소속없음'}</small>
					</div>
					<button type="button" class="btn btn-sm btn-outline-danger btn-remove-dropped" data-index="${index}">
						<i class="bi bi-x"></i>
					</button>
				</div>
			</div>
		`);

			$item.find('.btn-remove-dropped').on('click', function() {
				removeMemberFromDropZone(index);
			});

			$container.append($item);
		});
	}

	function removeMemberFromDropZone(index) {
		orgChangeDroppedMembers.splice(index, 1);

		// UI 업데이트
		renderDroppedMembers();
		renderOrgChangeMemberList(orgChangeSelectedMembers);
		updateOrgChangeConfirmButton();
	}

	/**
	 * 파일 위치: assets/js/mng_member.js
	 * 역할: 드롭존 초기화
	 */
	function resetOrgChangeDropZone() {
		orgChangeDroppedMembers = [];
		$('#orgChangeTargetOrg').val('');
		$('#orgChangeDropPlaceholder').removeClass('d-none');
		$('#orgChangeDroppedMembers').addClass('d-none').empty();
		$('#confirmOrgChangeBtn').prop('disabled', true);

		// 회원 목록도 초기화
		renderOrgChangeMemberList(orgChangeSelectedMembers);
	}

	/**
	 * 파일 위치: assets/js/mng_member.js
	 * 역할: 저장 버튼 상태 업데이트
	 */
	function updateOrgChangeConfirmButton() {
		const totalMembers = getTotalDroppedMemberCount();
		$('#confirmOrgChangeBtn').prop('disabled', totalMembers === 0);
	}

	/**
	 * 파일 위치: assets/js/mng_member.js
	 * 역할: 회원 검색 필터
	 */
	function filterOrgChangeMembers() {
		const keyword = $('#orgChangeMemberSearch').val().trim().toLowerCase();

		if (!keyword) {
			renderOrgChangeMemberList(orgChangeSelectedMembers);
			return;
		}

		const filteredMembers = orgChangeSelectedMembers.filter(function(member) {
			const name = (member.member_name || '').toLowerCase();
			return name.includes(keyword);
		});

		renderOrgChangeMemberList(filteredMembers);
	}


	/**
	 * 파일 위치: assets/js/mng_member.js
	 * 역할: 조직변경 확인 모달 표시
	 */
	function showOrgChangeConfirmModal() {
		const totalMembers = getTotalDroppedMemberCount();
		const modeText = orgChangeMode === 'move' ? '이동' : '복사';

		if (totalMembers === 0) {
			showToast(modeText + '할 회원을 조직에 드롭해주세요.', 'warning');
			return;
		}

		// 모드에 따라 확인 모달 제목/내용 변경
		if (orgChangeMode === 'move') {
			$('#confirmOrgChangeTitle').text('선택한 회원을 다른 조직으로 이동합니다.');
			$('#confirmOrgChangeWarningText').html('회원이 기존 조직에서 삭제되고 새 조직으로 이동됩니다.');
			$('#executeOrgChangeBtn').text('이동 실행');
		} else {
			$('#confirmOrgChangeTitle').text('선택한 회원을 다른 조직으로 복사합니다.');
			$('#confirmOrgChangeWarningText').html('회원 정보가 복사되며, 원본 조직의 회원 정보는 유지됩니다.<br>복사된 회원은 별도의 회원으로 관리되며, 이후 변경사항은 동기화되지 않습니다.');
			$('#executeOrgChangeBtn').text('복사 실행');
		}

		// 상세 내용 생성
		let detailHtml = '<ul class="mb-0">';

		for (const orgId in orgChangeDroppedData) {
			const members = orgChangeDroppedData[orgId];
			if (members.length === 0) continue;

			const org = currentCategoryOrgs.find(o => o.org_id == orgId);
			const orgName = org ? org.org_name : '알 수 없음';
			const memberNames = members.map(m => m.member_name).join(', ');

			detailHtml += `<li><strong>${orgName}</strong>: ${members.length}명 (${memberNames})</li>`;
		}

		detailHtml += '</ul>';

		$('#confirmOrgChangeDetail').html(detailHtml);

		// 확인 모달 표시
		$('#orgChangeConfirmModal').modal('show');
	}

	/**
	 * 파일 위치: assets/js/mng_member.js
	 * 역할: 회원 목록 엑셀 다운로드 (HTML 태그 제거 및 데이터 정제)
	 */
	function exportMemberToExcel() {
		// 그리드가 초기화되지 않은 경우
		if (!memberGrid) {
			showToast('회원 데이터를 불러온 후 다시 시도해주세요.', 'warning');
			return;
		}

		try {
			// 현재 그리드 데이터 가져오기
			const gridData = memberGrid.pqGrid("option", "dataModel.data");

			if (!gridData || gridData.length === 0) {
				showToast('다운로드할 회원 데이터가 없습니다.', 'info');
				return;
			}

			// 파일명 생성
			const currentDate = new Date();
			const dateStr = currentDate.getFullYear() +
				String(currentDate.getMonth() + 1).padStart(2, '0') +
				String(currentDate.getDate()).padStart(2, '0');

			const fileName = (selectedNodeName || '회원목록').replace(/[^\w가-힣]/g, '_') + '_' + dateStr;

			// 제외할 컬럼 인덱스 (체크박스, 사진)
			const excludeCols = getExcelExcludeColumns();

			// ParamQuery Grid의 내장 엑셀 익스포트 기능 사용
			const blob = memberGrid.pqGrid('exportData', {
				format: 'xlsx',
				render: false,
				type: 'blob',
				sheetName: '회원목록',
				noCols: excludeCols,
				// 셀 값 변환 함수
				cellConvert: function(data) {
					return convertCellValue(data);
				}
			});

			// 파일 다운로드
			if (blob) {
				if (typeof saveAs !== 'undefined') {
					saveAs(blob, fileName + '.xlsx');
				} else {
					const url = URL.createObjectURL(blob);
					const a = document.createElement('a');
					a.href = url;
					a.download = fileName + '.xlsx';
					document.body.appendChild(a);
					a.click();
					document.body.removeChild(a);
					URL.revokeObjectURL(url);
				}
				showToast('엑셀 파일 다운로드가 시작되었습니다.', 'success');
			} else {
				showToast('엑셀 파일 생성에 실패했습니다.', 'error');
			}

		} catch (error) {
			console.error('엑셀 다운로드 오류:', error);
			showToast('엑셀 다운로드 중 오류가 발생했습니다.', 'error');
		}
	}

	/**
	 * 파일 위치: assets/js/mng_member.js
	 * 역할: 엑셀 다운로드 시 제외할 컬럼 인덱스 반환
	 */
	function getExcelExcludeColumns() {
		const excludeIndices = [];
		const colModel = memberGrid.pqGrid("option", "colModel");

		colModel.forEach(function(col, index) {
			// 체크박스 컬럼 제외
			if (col.dataIndx === 'checkbox') {
				excludeIndices.push(index);
			}
			// 사진 컬럼 제외
			if (col.dataIndx === 'member_photo' || col.dataIndx === 'photo') {
				excludeIndices.push(index);
			}
		});

		return excludeIndices;
	}

	/**
	 * 파일 위치: assets/js/mng_member.js
	 * 역할: 엑셀 셀 값 변환 (HTML 태그 제거 및 데이터 정제)
	 */
	function convertCellValue(data) {
		const dataIndx = data.dataIndx;
		let value = data.rowData[dataIndx];

		if (value === null || value === undefined) {
			return '';
		}

		// 문자열로 변환
		value = String(value);

		// 관리tag (member_status) - badge HTML에서 텍스트만 추출하여 쉼표로 구분
		if (dataIndx === 'member_status') {
			return extractTagsFromHtml(value);
		}

		// 소속조직 (org_name) - HTML 태그 제거
		if (dataIndx === 'org_name') {
			return stripHtmlTags(value);
		}

		// 카테고리 (category_name) - HTML 태그 제거
		if (dataIndx === 'category_name') {
			return stripHtmlTags(value);
		}

		// 기타 필드 - HTML 태그가 있으면 제거
		if (value.indexOf('<') !== -1) {
			return stripHtmlTags(value);
		}

		return value;
	}

	/**
	 * 파일 위치: assets/js/mng_member.js
	 * 역할: HTML에서 태그 텍스트 추출 (badge 등에서 태그명만 추출하여 쉼표로 구분)
	 */
	function extractTagsFromHtml(html) {
		if (!html || html.trim() === '') {
			return '';
		}

		// 임시 DOM 엘리먼트 생성
		const tempDiv = document.createElement('div');
		tempDiv.innerHTML = html;

		// badge나 span 태그에서 텍스트 추출
		const badges = tempDiv.querySelectorAll('.badge, span');
		const tags = [];

		if (badges.length > 0) {
			badges.forEach(function(badge) {
				const text = badge.textContent.trim();
				if (text) {
					tags.push(text);
				}
			});
		} else {
			// badge가 없으면 전체 텍스트 추출
			const text = tempDiv.textContent.trim();
			if (text) {
				tags.push(text);
			}
		}

		return tags.join(', ');
	}

	/**
	 * 파일 위치: assets/js/mng_member.js
	 * 역할: HTML 태그 제거
	 */
	function stripHtmlTags(html) {
		if (!html || html.trim() === '') {
			return '';
		}

		const tempDiv = document.createElement('div');
		tempDiv.innerHTML = html;
		return tempDiv.textContent.trim() || tempDiv.innerText.trim() || '';
	}



	/**
	 * 파일 위치: assets/js/mng_member.js
	 * 역할: debounce 유틸리티 함수
	 */
	function debounce(func, wait) {
		let timeout;
		return function executedFunction() {
			const context = this;
			const args = arguments;
			const later = function() {
				timeout = null;
				func.apply(context, args);
			};
			clearTimeout(timeout);
			timeout = setTimeout(later, wait);
		};
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
