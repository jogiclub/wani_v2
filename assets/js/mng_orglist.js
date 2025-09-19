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
						total += parseInt(item.data.org_count) || 0;
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

			console.log('Loading org list with data:', requestData); // 디버깅용

			$.ajax({
				url: '/mng/mng_org/get_org_list',
				type: 'GET',
				data: requestData,
				dataType: 'json',
				success: function(response) {
					self.showGridSpinner(false);

					console.log('API Response:', response); // 디버깅용

					if (response.success) {
						console.log('Received org count:', response.data.length); // 디버깅용
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

			// 새로고침 버튼
			// $('#refreshBtn').on('click', function() {
			// 	if (self.selectedCategoryIdx !== null) {
			// 		self.loadOrgList();
			// 	}
			// });

			// 트리 컨텍스트 메뉴
			$('#categoryTree').on('contextmenu', 'span.fancytree-title', function(e) {
				const categoryIdx = $(this).attr('data-category-idx');
				const categoryName = $(this).attr('data-category-name');

				if (categoryIdx) {
					e.preventDefault();
					self.showContextMenu(e.pageX, e.pageY, categoryIdx, categoryName);
				}
			});

			// 카테고리 추가/수정 모달 이벤트
			$('#saveCategoryBtn').on('click', function() { self.saveCategory(); });
			$('#saveRenameBtn').on('click', function() { self.saveRenameCategory(); });
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

		showContextMenu: function(x, y, categoryIdx, categoryName) {
			const self = this;
			$('.context-menu').remove();

			const contextMenu = $(`
                <div class="context-menu" style="position: fixed; top: ${y}px; left: ${x}px; z-index: 1000;">
                    <div class="card shadow-sm">
                        <div class="list-group list-group-flush">
                            <a href="#" class="list-group-item list-group-item-action" onclick="MngOrgListInstance.renameCategory(${categoryIdx}, '${categoryName}')">
                                <i class="bi bi-pencil me-2"></i>이름 변경
                            </a>
                            <a href="#" class="list-group-item list-group-item-action text-danger" onclick="MngOrgListInstance.deleteCategory(${categoryIdx}, '${categoryName}')">
                                <i class="bi bi-trash me-2"></i>삭제
                            </a>
                        </div>
                    </div>
                </div>
            `);

			$('body').append(contextMenu);

			$(document).one('click', function() {
				$('.context-menu').remove();
			});
		},

		renameCategory: function(categoryIdx, categoryName) {
			$('.context-menu').remove();
			$('#renameCategoryModal').data('category-idx', categoryIdx);
			$('#renameCategoryName').val(categoryName);
			$('#renameCategoryModal').modal('show');
		},

		deleteCategory: function(categoryIdx, categoryName) {
			const self = this;
			$('.context-menu').remove();

			this.showConfirm(
				'카테고리 삭제',
				`'${categoryName}' 카테고리를 삭제하시겠습니까?<br><small class="text-muted">하위 카테고리나 조직이 있으면 삭제할 수 없습니다.</small>`,
				function() {
					$.ajax({
						url: '/mng/mng_org/delete_category',
						type: 'POST',
						data: { category_idx: categoryIdx },
						dataType: 'json',
						success: function(response) {
							if (response.success) {
								self.showToast(response.message, 'success');
								self.initCategoryTree();

								if (self.selectedCategoryIdx == categoryIdx) {
									self.selectedCategoryIdx = null;
									self.selectedCategoryName = '';
									self.updateSelectedTitle();
									self.grid.pqGrid('option', 'dataModel.data', []);
									self.grid.pqGrid('refreshDataAndView');
								}
							} else {
								self.showToast(response.message, 'error');
							}
						},
						error: function() {
							self.showToast('카테고리 삭제에 실패했습니다', 'error');
						}
					});
				}
			);
		},

		saveCategory: function() {
			const self = this;
			const categoryName = $('#categoryName').val().trim();
			const parentIdx = $('#parentCategory').val() || null;

			if (!categoryName) {
				this.showToast('카테고리명을 입력해주세요', 'error');
				return;
			}

			$.ajax({
				url: '/mng/mng_org/add_category',
				type: 'POST',
				data: {
					category_name: categoryName,
					parent_idx: parentIdx
				},
				dataType: 'json',
				success: function(response) {
					if (response.success) {
						self.showToast(response.message, 'success');
						$('#addCategoryModal').modal('hide');
						$('#categoryName').val('');
						$('#parentCategory').val('');
						self.initCategoryTree();
					} else {
						self.showToast(response.message, 'error');
					}
				},
				error: function() {
					self.showToast('카테고리 추가에 실패했습니다', 'error');
				}
			});
		},

		saveRenameCategory: function() {
			const self = this;
			const categoryIdx = $('#renameCategoryModal').data('category-idx');
			const newName = $('#renameCategoryName').val().trim();

			if (!newName) {
				this.showToast('카테고리명을 입력해주세요', 'error');
				return;
			}

			$.ajax({
				url: '/mng/mng_org/rename_category',
				type: 'POST',
				data: {
					category_idx: categoryIdx,
					category_name: newName
				},
				dataType: 'json',
				success: function(response) {
					if (response.success) {
						self.showToast(response.message, 'success');
						$('#renameCategoryModal').modal('hide');
						self.initCategoryTree();
					} else {
						self.showToast(response.message, 'error');
					}
				},
				error: function() {
					self.showToast('카테고리명 수정에 실패했습니다', 'error');
				}
			});
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

		showToast: function(message, type = 'info') {
			if ($('#toastContainer').length === 0) {
				$('body').append('<div id="toastContainer" class="position-fixed top-0 end-0 p-3"></div>');
			}

			const toastId = 'toast_' + Date.now();
			let bgClass = 'bg-info';

			if (type === 'success') {
				bgClass = 'bg-success';
			} else if (type === 'error') {
				bgClass = 'bg-danger';
			}

			const toastHtml = `
                <div id="${toastId}" class="toast align-items-center text-white ${bgClass} border-0" role="alert">
                    <div class="d-flex">
                        <div class="toast-body">${message}</div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                    </div>
                </div>
            `;

			$('#toastContainer').append(toastHtml);

			const toastElement = document.getElementById(toastId);
			const toast = new bootstrap.Toast(toastElement);
			toast.show();

			toastElement.addEventListener('hidden.bs.toast', function() {
				$(toastElement).remove();
			});
		},

		showConfirm: function(title, message, callback) {
			const modalId = 'confirmModal_' + Date.now();

			const modal = $(`
                <div class="modal fade" id="${modalId}" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">${title}</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">${message}</div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                                <button type="button" class="btn btn-danger" id="confirmBtn">확인</button>
                            </div>
                        </div>
                    </div>
                </div>
            `);

			modal.find('#confirmBtn').on('click', function() {
				modal.modal('hide');
				if (callback) callback();
			});

			modal.on('hidden.bs.modal', function() {
				modal.remove();
			});

			$('body').append(modal);
			modal.modal('show');
		}
	};

	// 전역 인스턴스 생성 (onclick 이벤트에서 사용하기 위해)
	window.MngOrgListInstance = MngOrgList;

	// 문서 준비 완료 시 초기화
	$(document).ready(function() {
		MngOrgList.init();
	});

})();
