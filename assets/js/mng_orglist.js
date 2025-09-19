(function() {
	'use strict';

	// 즉시실행함수로 스코프 분리하여 변수 충돌 방지
	const MngOrgList = {
		tree: null,
		grid: null,
		selectedCategoryIdx: null,
		selectedCategoryName: '',

		init: function() {
			this.setupSplitView();
			this.initCategoryTree();
			this.initOrgGrid();
			this.bindEvents();
		},

		setupSplitView: function() {
			// 기존 Split 인스턴스가 있으면 제거
			if (this.splitInstance) {
				this.splitInstance.destroy();
			}

			// 기존 gutter 요소들 제거
			$('.gutter').remove();

			this.splitInstance = Split(['#left-pane', '#right-pane'], {
				sizes: [15, 85],
				minSize: [50, 50],
				gutterSize: 7,
				cursor: 'col-resize',
				direction: 'horizontal'
			});
		},

		initCategoryTree: function() {
			const self = this;
			this.showTreeSpinner(true);

			$.ajax({
				url: '/mng/mng_org/get_category_tree',
				type: 'GET',
				dataType: 'json',
				success: function(response) {
					// 전체 조직 수 계산
					const totalOrgCount = self.calculateTotalOrgs(response);

					// 전체 노드를 맨 위에 추가하고, 기존 카테고리들을 전체의 하위로 구성
					const allNode = {
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
					};

					const treeData = [allNode];

					// Fancytree 초기화
					self.tree = $('#categoryTree').fancytree({
						source: treeData,
						activate: function(event, data) {
							self.onTreeNodeActivate(event, data);
						},
						renderNode: function(event, data) {
							self.onTreeNodeRender(event, data);
						},
						selectMode: 1
					});

					self.showTreeSpinner(false);

					// 기본으로 전체 선택
					const tree = $.ui.fancytree.getTree('#categoryTree');
					const allTreeNode = tree.getNodeByKey('all');
					if (allTreeNode) {
						allTreeNode.setActive();
					}
				},
				error: function() {
					self.showTreeSpinner(false);
					self.showToast('카테고리 목록 로딩에 실패했습니다', 'error');
				}
			});
		},

		calculateTotalOrgs: function(categories) {
			let total = 0;

			function countOrgs(items) {
				for (let item of items) {
					if (item.data && item.data.org_count) {
						total += parseInt(item.data.org_count);
					}
					if (item.children && item.children.length >= 0) {
						countOrgs(item.children);
					}
				}
			}
			countOrgs(categories);

			return total;
		},

		onTreeNodeActivate: function(event, data) {
			const node = data.node;
			const nodeData = node.data;

			if (nodeData.type === 'category' || nodeData.type === 'all') {
				this.selectedCategoryIdx = nodeData.category_idx;
				this.selectedCategoryName = nodeData.type === 'all' ? '전체' : nodeData.category_name;
				this.updateSelectedTitle();
				this.loadOrgList();
			}
		},

		onTreeNodeRender: function(event, data) {
			const node = data.node;
			const $span = $(node.span);

			// 카테고리 노드에만 컨텍스트 메뉴 데이터 추가
			if (node.data.type === 'category') {
				$span.attr('data-category-idx', node.data.category_idx);
				$span.attr('data-category-name', node.data.category_name);
			}
		},

		initOrgGrid: function() {
			const self = this;
			const colModel = [

				{
					dataIndx: 'category_name',
					title: '카테고리',
					width: 120,
					render: function(ui) {
						const data = ui.rowData;
						if (data.category_name) {
							return `<span class="badge bg-secondary">${data.category_name}</span>`;
						} else {
							return `<span class="badge bg-light text-dark">미분류</span>`;
						}
					}
				},
				{
					dataIndx: 'org_icon',
					title: '아이콘',
					width: 60,
					align: 'center',
					render: function(ui) {
						const data = ui.rowData;
						if (data.org_icon) {
							return `<img src="${data.org_icon}" class="rounded" width="40" height="40">`;
						}
						return `<div class="d-inline-block" style="width:40px;height:40px; border-radius: 20px;padding: 5px; color: #ccc; background: #eee">
                            <i class="bi bi-people-fill" style="font-size: 20px"></i>
                        </div>`;
					}
				},
				{
					dataIndx: 'org_name',
					title: '조직명',
					minWidth: 200,
					render: function(ui) {
						return `<strong>${ui.cellData}</strong>`;
					}
				},

				{
					dataIndx: 'org_code',
					title: '조직코드',
					width: 200,
					render: function(ui) {
						return `<code>${ui.cellData}</code>`;
					}
				},
				{
					dataIndx: 'org_desc',
					title: '설명',
					minWidth: 300,
					render: function(ui) {
						return ui.cellData || '<span class="text-muted">설명 없음</span>';
					}
				},
				{
					dataIndx: 'org_type',
					title: '유형',
					width: 100,
					align: 'center',
					render: function(ui) {
						return self.getOrgTypeText(ui.cellData);
					}
				},
				{
					dataIndx: 'member_count',
					title: '회원수',
					width: 80,
					align: 'center',
					render: function(ui) {
						return `<span class="badge bg-info">${ui.cellData}명</span>`;
					}
				},
				{
					dataIndx: 'regi_date',
					title: '등록일',
					width: 120,
					align: 'center',
					render: function(ui) {
						return new Date(ui.cellData).toLocaleDateString();
					}
				},
			];

			this.grid = $('#orgGrid').pqGrid({
				width: '100%',
				height: '100%',
				dataModel: { data: [] },
				colModel: colModel,
				freezeCols: 3,
				numberCell: { show: false },
				hoverMode: 'row',
				selectionModel: { type: 'row', mode: 'single' },
				// pageModel: { type: 'local', rPP: 20 },
				resizable: true,
				wrap: false,
				hwrap: false,
				strNoRows: '조직 정보가 없습니다'
			});
		},

		loadOrgList: function() {
			const self = this;
			this.showGridSpinner(true);

			// 전송할 파라미터 명시적으로 설정
			const requestData = {};
			if (this.selectedCategoryIdx !== null) {
				requestData.category_idx = this.selectedCategoryIdx;
			}



			$.ajax({
				url: '/mng/mng_org/get_org_list',
				type: 'GET',
				data: requestData,
				dataType: 'json',
				success: function(response) {
					self.showGridSpinner(false);
					if (response.success) {
						self.grid.pqGrid('option', 'dataModel.data', response.data);
						self.grid.pqGrid('refreshDataAndView');
					} else {
						self.showToast('조직 목록 로딩에 실패했습니다', 'error');
					}
				},
				error: function(xhr, status, error) {
					self.showGridSpinner(false);
					console.error('Ajax Error:', status, error, xhr.responseText); // 디버깅용
					self.showToast('조직 목록 로딩에 실패했습니다', 'error');
				}
			});
		},

		bindEvents: function() {
			const self = this;
		},

		updateSelectedTitle: function() {
			$('#selectedOrgName').html(`${this.selectedCategoryName}`);
		},

		getOrgTypeText: function(orgType) {
			const types = {
				'church': '교회',
				'school': '학교',
				'company': '회사',
				'organization': '단체'
			};
			return types[orgType] || orgType;
		},







		// 유틸리티 함수들
		showTreeSpinner: function(show) {
			if (show) {
				$('#treeSpinner').removeClass('d-none').addClass('d-flex');
			} else {
				$('#treeSpinner').removeClass('d-flex').addClass('d-none');
			}
		},

		showGridSpinner: function(show) {
			if (show) {
				$('#gridSpinner').removeClass('d-none').addClass('d-flex');
			} else {
				$('#gridSpinner').removeClass('d-flex').addClass('d-none');
			}
		},
	};

	// 전역 인스턴스 생성 (onclick 이벤트에서 사용하기 위해)
	window.MngOrgListInstance = MngOrgList;

	// 문서 준비 완료 시 초기화
	$(document).ready(function() {
		MngOrgList.init();
	});

})();
