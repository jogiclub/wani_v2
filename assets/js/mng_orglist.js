'use strict';

/**
 * 파일 위치: assets/js/mng_orglist.js
 * 역할: 관리자 조직관리 화면의 메인 JavaScript 파일
 */

// 즉시 실행 함수로 전역 오염 방지


// 전역 변수들
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

		// 순서대로 초기화
		cleanupExistingInstances();
		initSplitJS();
		initFancytree();
		initParamQueryGrid();
		bindGlobalEvents();

		console.log('조직관리 페이지 초기화 완료');
	}

	/**
	 * 기존 인스턴스들 완전 정리
	 */
	function cleanupExistingInstances() {
		// Split.js 정리
		if (splitInstance) {
			try {
				splitInstance.destroy();
			} catch(e) {
				console.warn('Split 인스턴스 제거 실패:', e);
			}
			splitInstance = null;
		}

		// 모든 gutter 요소 제거
		$('.gutter, .gutter-horizontal, .gutter-vertical').remove();
		$('[class*="gutter"]').remove();

		// Fancytree 정리
		if (treeInstance) {
			try {
				$("#categoryTree").fancytree("destroy");
			} catch(e) {
				console.warn('Fancytree 인스턴스 제거 실패:', e);
			}
			treeInstance = null;
		}

		// ParamQuery Grid 정리
		if (orgGrid) {
			try {
				orgGrid.pqGrid("destroy");
			} catch(e) {
				console.warn('Grid 인스턴스 제거 실패:', e);
			}
			orgGrid = null;
		}

		// 체크박스 상태 초기화
		checkedOrgIds.clear();
	}

	/**
	 * Split.js 초기화 (완전 새로운 방식)
	 */
	function initSplitJS() {
		// 약간의 지연 후 초기화 (DOM 정리 시간 확보)



		setTimeout(function() {
			try {
				splitInstance = Split(['#left-pane', '#right-pane'], {
					sizes: [20, 80],
					minSize: [200, 400],
					gutterSize: 8,
					cursor: 'col-resize',
					direction: 'horizontal',
					onDragEnd: function(sizes) {
						// 크기 조정 후 그리드 리프레시
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
	function initFancytree() {
		showTreeSpinner();

		$.ajax({
			url: '/mng/mng_org/get_category_tree',
			type: 'GET',
			dataType: 'json',
			success: function(response) {
				try {
					const totalOrgCount = calculateTotalOrgs(response);
					const treeData = [{
						key: 'all',
						title: `전체 (${totalOrgCount}개)`,
						folder: true,
						expanded: true,
						data: {
							type: 'all',
							category_idx: null,
							category_name: '전체'
						},
						children: response
					}];

					treeInstance = $("#categoryTree").fancytree({
						source: treeData,
						activate: function(event, data) {
							handleTreeNodeActivate(data.node);
						},
						selectMode: 1
					});

					hideTreeSpinner();

					// 첫 번째 노드 활성화
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
			},
			error: function(xhr, status, error) {
				hideTreeSpinner();
				console.error('카테고리 트리 로드 실패:', error);
				showToast('카테고리 목록을 불러올 수 없습니다', 'error');
			}
		});
	}

	/**
	 * 전체 조직 수 계산
	 */
	function calculateTotalOrgs(categories) {
		let total = 0;
		function countOrgs(items) {
			if (!items || !Array.isArray(items)) return;
			items.forEach(item => {
				if (item.data && item.data.org_count) {
					total += parseInt(item.data.org_count) || 0;
				}
				if (item.children) {
					countOrgs(item.children);
				}
			});
		}
		countOrgs(categories);
		return total;
	}

	/**
	 * 트리 노드 활성화 처리
	 */
	function handleTreeNodeActivate(node) {
		const nodeData = node.data;

		if (nodeData.type === 'category' || nodeData.type === 'all') {
			selectedCategoryIdx = nodeData.category_idx;
			selectedCategoryName = nodeData.type === 'all' ? '전체' : nodeData.category_name;
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
				freezeCols: 3,
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
					// 렌더링 완료 후 체크박스 이벤트 바인딩
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
				dataIndx: 'org_code',
				title: '조직코드',
				width: 150,
				editable: false,
				render: function(ui) {
					return `<code>${ui.cellData || ''}</code>`;
				}
			},
			{
				dataIndx: 'org_rep',
				title: '대표자',
				width: 100,
				align: 'center',
				editable: false,
				render: function(ui) {
					return ui.cellData || '<span class="text-muted">-</span>';
				}
			},
			{
				dataIndx: 'org_manager',
				title: '담당자',
				width: 100,
				align: 'center',
				editable: false,
				render: function(ui) {
					return ui.cellData || '<span class="text-muted">-</span>';
				}
			},
			{
				dataIndx: 'org_phone',
				title: '연락처',
				width: 120,
				align: 'center',
				editable: false,
				render: function(ui) {
					return ui.cellData || '<span class="text-muted">-</span>';
				}
			},
			{
				dataIndx: 'org_address',
				title: '주소',
				width: 280,
				editable: false,
				render: function(ui) {
					const address = ui.cellData || '';
					const addressDetail = ui.rowData.org_address_detail || '';
					const postNo = ui.rowData.org_address_postno || '';

					if (!address && !addressDetail && !postNo) {
						return '<span class="text-muted">주소 정보 없음</span>';
					}

					let fullAddress = '';
					if (postNo) fullAddress += `(${postNo}) `;
					if (address) fullAddress += address;
					if (addressDetail) fullAddress += ` ${addressDetail}`;

					return `<small title="${fullAddress}">${fullAddress}</small>`;
				}
			},
			{
				dataIndx: 'org_tag',
				title: '태그',
				width: 150,
				editable: false,
				render: function(ui) {
					if (ui.cellData) {
						const tags = ui.cellData.split(',').map(tag => tag.trim()).filter(tag => tag);
						if (tags.length > 0) {
							return tags.map(tag => `<span class="badge bg-primary me-1">${tag}</span>`).join('');
						}
					}
					return '<span class="text-muted">-</span>';
				}
			},
			{
				dataIndx: 'org_desc',
				title: '설명',
				width: 200,
				editable: false,
				render: function(ui) {
					if (ui.cellData) {
						const shortDesc = ui.cellData.length > 50 ? ui.cellData.substring(0, 50) + '...' : ui.cellData;
						return `<span title="${ui.cellData}">${shortDesc}</span>`;
					}
					return '<span class="text-muted">설명 없음</span>';
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
		// 체크박스 컬럼(첫 번째 컬럼) 클릭 시
		if (ui.colIndx === 0) {
			const target = event.originalEvent.target;

			// 체크박스를 직접 클릭하지 않은 경우 체크박스 토글
			if (!$(target).hasClass('org-checkbox')) {
				const orgId = ui.rowData.org_id;
				const checkbox = $(`.org-checkbox[data-org-id="${orgId}"]`);
				if (checkbox.length > 0) {
					checkbox.prop('checked', !checkbox.prop('checked')).trigger('change');
				}
			}
		}
	}

	/**
	 * 체크박스 이벤트 바인딩
	 */
	function bindCheckboxEvents() {
		// 기존 이벤트 제거
		$(document).off('change', '#selectAllOrgs');
		$(document).off('change', '.org-checkbox');

		// 전체 선택 체크박스 이벤트
		$(document).on('change', '#selectAllOrgs', function(e) {
			e.stopPropagation();

			const isChecked = $(this).is(':checked');
			const wasIndeterminate = $(this).prop('indeterminate');

			// indeterminate 상태에서는 전체 선택으로
			if (wasIndeterminate) {
				$(this).prop('indeterminate', false);
				$(this).prop('checked', true);
			}

			// 모든 개별 체크박스 상태 변경
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

		// 개별 체크박스 이벤트
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
	 * 전체 선택 체크박스 상태 업데이트 (indeterminate 포함)
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
	 * 체크박스 상태들 업데이트
	 */
	function updateCheckboxStates() {
		updateSelectAllCheckboxState();
		updateSelectedCount();
	}

	/**
	 * 선택된 조직 수 업데이트
	 */
	function updateSelectedCount() {
		const count = checkedOrgIds.size;
		$('#selectedCount').text(count);
		$('#btnDeleteOrg').prop('disabled', count === 0);
	}

	/**
	 * 조직 목록 로드
	 */
	function loadOrgList() {
		showGridSpinner();

		// 체크박스 상태 초기화
		checkedOrgIds.clear();

		const requestData = {};
		if (selectedCategoryIdx !== null) {
			requestData.category_idx = selectedCategoryIdx;
		}

		$.ajax({
			url: '/mng/mng_org/get_org_list',
			type: 'GET',
			data: requestData,
			dataType: 'json',
			success: function(response) {
				hideGridSpinner();

				if (response.success) {
					// 데이터 업데이트
					orgGrid.pqGrid("option", "dataModel.data", response.data || []);
					orgGrid.pqGrid("refreshDataAndView");

					// 체크박스 상태 초기화
					setTimeout(function() {
						$('#selectAllOrgs').prop('checked', false).prop('indeterminate', false);
						updateSelectedCount();
					}, 100);

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
	 * 전역 이벤트 바인딩
	 */
	function bindGlobalEvents() {
		// 선택삭제 버튼
		$('#btnDeleteOrg').on('click', showDeleteModal);

		// 삭제 확인 버튼
		$('#confirmDeleteOrgBtn').on('click', executeDelete);

		// 윈도우 리사이즈 이벤트
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
	 * 삭제 확인 모달 표시
	 */
	function showDeleteModal() {
		const selectedOrgs = getSelectedOrgs();

		if (selectedOrgs.length === 0) {
			showToast('삭제할 조직을 선택해주세요', 'warning');
			return;
		}

		// 삭제할 조직 목록 HTML 생성
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

		if (orgGrid) {
			const gridData = orgGrid.pqGrid('option', 'dataModel.data');

			checkedOrgIds.forEach(orgId => {
				const orgData = gridData.find(row => row.org_id === orgId);
				if (orgData) {
					selectedOrgs.push(orgData);
				}
			});
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
					loadOrgList(); // 목록 새로고침
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
		$('#selectedOrgName').html(`<i class="bi bi-building"></i> ${selectedCategoryName}`);
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

