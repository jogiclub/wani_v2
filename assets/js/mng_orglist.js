'use strict'

let categoryTree;
let selectedCategoryIdx = null;
let selectedCategoryName = '';

$(document).ready(function() {
	initializeCategoryTree();
	bindEvents();
});

/**
 * 카테고리 트리 초기화
 */
function initializeCategoryTree() {
	$.ajax({
		url: '/mng/mng_org/get_category_tree',
		type: 'GET',
		dataType: 'json',
		success: function(response) {
			categoryTree = $('#categoryTree').fancytree({
				source: response,
				activate: function(event, data) {
					const node = data.node;
					if (node.data.type === 'category' || node.data.type === 'uncategorized') {
						selectedCategoryIdx = node.data.category_idx;
						selectedCategoryName = node.data.type === 'uncategorized' ? '미분류' : node.data.category_name;
						loadOrgList(selectedCategoryIdx);
					}
				},
				renderNode: function(event, data) {
					const node = data.node;
					const $span = $(node.span);

					// 카테고리에 컨텍스트 메뉴 추가
					if (node.data.type === 'category') {
						$span.attr('data-category-idx', node.data.category_idx);
						$span.attr('data-category-name', node.data.category_name);
					}
				}
			});

			// 컨텍스트 메뉴 설정
			$('#categoryTree').on('contextmenu', 'span.fancytree-title', function(e) {
				e.preventDefault();

				const categoryIdx = $(this).attr('data-category-idx');
				const categoryName = $(this).attr('data-category-name');

				if (!categoryIdx) return;

				showCategoryContextMenu(e.pageX, e.pageY, categoryIdx, categoryName);
			});
		},
		error: function() {
			showToast('카테고리 목록을 불러오는데 실패했습니다.', 'error');
		}
	});
}

/**
 * 조직 목록 로드
 */
function loadOrgList(categoryIdx) {
	$.ajax({
		url: '/mng/mng_org/get_org_list',
		type: 'GET',
		data: { category_idx: categoryIdx },
		dataType: 'json',
		success: function(response) {
			if (response.success) {
				updateOrgTable(response.data);
				$('#categoryTitle').text(selectedCategoryName + ' 조직 목록');
			} else {
				showToast(response.message || '조직 목록을 불러오는데 실패했습니다.', 'error');
			}
		},
		error: function() {
			showToast('조직 목록을 불러오는데 실패했습니다.', 'error');
		}
	});
}

/**
 * 조직 테이블 업데이트
 */
function updateOrgTable(orgs) {
	const tbody = $('#orgTableBody');
	tbody.empty();

	if (orgs.length === 0) {
		tbody.append(`
			<tr>
				<td colspan="7" class="text-center text-muted py-5">
					등록된 조직이 없습니다.
				</td>
			</tr>
		`);
		return;
	}

	orgs.forEach(function(org) {
		const iconHtml = org.org_icon ?
			`<img src="${org.org_icon}" class="rounded" width="40" height="40">` :
			`<div class="bg-secondary rounded d-flex align-items-center justify-content-center" style="width:40px;height:40px;">
				<i class="bi bi-building text-white"></i>
			</div>`;

		const typeText = getOrgTypeText(org.org_type);
		const regDate = new Date(org.regi_date).toLocaleDateString();

		tbody.append(`
			<tr>
				<td>${iconHtml}</td>
				<td><code>${org.org_code}</code></td>
				<td>
					<strong>${org.org_name}</strong>
					${org.org_desc ? `<br><small class="text-muted">${org.org_desc}</small>` : ''}
				</td>
				<td>${typeText}</td>
				<td><span class="badge bg-info">${org.member_count}명</span></td>
				<td>${regDate}</td>
				<td>
					<button type="button" class="btn btn-sm btn-outline-primary me-1" onclick="showOrgDetail(${org.org_id})">
						<i class="bi bi-eye"></i>
					</button>
					<button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteOrg(${org.org_id}, '${org.org_name}')">
						<i class="bi bi-trash"></i>
					</button>
				</td>
			</tr>
		`);
	});
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
	return types[orgType] || orgType;
}

/**
 * 이벤트 바인딩
 */
function bindEvents() {
	// 카테고리 추가 버튼
	$('#addCategoryBtn').click(function() {
		loadParentCategories();
		$('#addCategoryModal').modal('show');
	});

	// 카테고리 저장
	$('#saveCategoryBtn').click(function() {
		const categoryName = $('#categoryName').val().trim();
		const parentIdx = $('#parentCategory').val() || null;

		if (!categoryName) {
			showToast('카테고리명을 입력해주세요.', 'error');
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
					showToast(response.message, 'success');
					$('#addCategoryModal').modal('hide');
					$('#categoryName').val('');
					$('#parentCategory').val('');
					initializeCategoryTree(); // 트리 새로고침
				} else {
					showToast(response.message, 'error');
				}
			},
			error: function() {
				showToast('카테고리 추가에 실패했습니다.', 'error');
			}
		});
	});

	// 카테고리명 수정 저장
	$('#saveRenameBtn').click(function() {
		const categoryIdx = $('#renameCategoryModal').data('category-idx');
		const newName = $('#renameCategoryName').val().trim();

		if (!newName) {
			showToast('카테고리명을 입력해주세요.', 'error');
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
					showToast(response.message, 'success');
					$('#renameCategoryModal').modal('hide');
					initializeCategoryTree(); // 트리 새로고침
				} else {
					showToast(response.message, 'error');
				}
			},
			error: function() {
				showToast('카테고리명 수정에 실패했습니다.', 'error');
			}
		});
	});

	// 새로고침 버튼
	$('#refreshBtn').click(function() {
		if (selectedCategoryIdx !== null) {
			loadOrgList(selectedCategoryIdx);
		}
	});
}

/**
 * 상위 카테고리 목록 로드
 */
function loadParentCategories() {
	$.ajax({
		url: '/mng/mng_org/get_category_tree',
		type: 'GET',
		dataType: 'json',
		success: function(response) {
			const select = $('#parentCategory');
			select.empty().append('<option value="">최상위 카테고리</option>');

			function addOptions(categories, depth = 0) {
				categories.forEach(function(category) {
					if (category.data.type === 'category') {
						const indent = '　'.repeat(depth);
						select.append(`<option value="${category.data.category_idx}">${indent}${category.data.category_name}</option>`);

						if (category.children && category.children.length > 0) {
							addOptions(category.children, depth + 1);
						}
					}
				});
			}

			addOptions(response);
		}
	});
}

/**
 * 카테고리 컨텍스트 메뉴 표시
 */
function showCategoryContextMenu(x, y, categoryIdx, categoryName) {
	// 기존 컨텍스트 메뉴 제거
	$('.context-menu').remove();

	const contextMenu = $(`
		<div class="context-menu" style="position: fixed; top: ${y}px; left: ${x}px; z-index: 1000;">
			<div class="card shadow-sm">
				<div class="list-group list-group-flush">
					<a href="#" class="list-group-item list-group-item-action" onclick="renameCategory(${categoryIdx}, '${categoryName}')">
						<i class="bi bi-pencil me-2"></i>이름 변경
					</a>
					<a href="#" class="list-group-item list-group-item-action text-danger" onclick="deleteCategory(${categoryIdx}, '${categoryName}')">
						<i class="bi bi-trash me-2"></i>삭제
					</a>
				</div>
			</div>
		</div>
	`);

	$('body').append(contextMenu);

	// 외부 클릭시 메뉴 닫기
	$(document).one('click', function() {
		$('.context-menu').remove();
	});
}

/**
 * 카테고리명 변경
 */
function renameCategory(categoryIdx, categoryName) {
	$('.context-menu').remove();

	$('#renameCategoryModal').data('category-idx', categoryIdx);
	$('#renameCategoryName').val(categoryName);
	$('#renameCategoryModal').modal('show');
}

/**
 * 카테고리 삭제
 */
function deleteCategory(categoryIdx, categoryName) {
	$('.context-menu').remove();

	showConfirm(
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
						showToast(response.message, 'success');
						initializeCategoryTree(); // 트리 새로고침

						// 삭제된 카테고리가 선택되어 있었다면 초기화
						if (selectedCategoryIdx == categoryIdx) {
							selectedCategoryIdx = null;
							selectedCategoryName = '';
							$('#categoryTitle').text('조직 목록');
							$('#orgTableBody').html(`
								<tr>
									<td colspan="7" class="text-center text-muted py-5">
										왼쪽에서 카테고리를 선택해주세요.
									</td>
								</tr>
							`);
						}
					} else {
						showToast(response.message, 'error');
					}
				},
				error: function() {
					showToast('카테고리 삭제에 실패했습니다.', 'error');
				}
			});
		}
	);
}

/**
 * 조직 상세 정보 표시
 */
function showOrgDetail(orgId) {
	$.ajax({
		url: '/mng/mng_org/get_org_detail',
		type: 'GET',
		data: { org_id: orgId },
		dataType: 'json',
		success: function(response) {
			if (response.success) {
				const org = response.data;
				const adminInfo = org.admin_info;

				const iconHtml = org.org_icon ?
					`<img src="${org.org_icon}" class="rounded me-3" width="60" height="60">` :
					`<div class="bg-secondary rounded d-flex align-items-center justify-content-center me-3" style="width:60px;height:60px;">
						<i class="bi bi-building text-white fs-4"></i>
					</div>`;

				const content = `
					<div class="d-flex align-items-center mb-4">
						${iconHtml}
						<div>
							<h4 class="mb-1">${org.org_name}</h4>
							<p class="text-muted mb-0">${org.org_desc || '설명이 없습니다.'}</p>
						</div>
					</div>

					<div class="row">
						<div class="col-md-6">
							<h6>기본 정보</h6>
							<table class="table table-sm">
								<tr><th width="120">조직 코드</th><td><code>${org.org_code}</code></td></tr>
								<tr><th>조직 유형</th><td>${getOrgTypeText(org.org_type)}</td></tr>
								<tr><th>초대 코드</th><td><code>${org.invite_code}</code></td></tr>
								<tr><th>리더 호칭</th><td>${org.leader_name}</td></tr>
								<tr><th>새가족 호칭</th><td>${org.new_name}</td></tr>
								<tr><th>회원 수</th><td><span class="badge bg-info">${org.member_count}명</span></td></tr>
								<tr><th>등록일</th><td>${new Date(org.regi_date).toLocaleString()}</td></tr>
							</table>
						</div>
						<div class="col-md-6">
							<h6>관리자 정보</h6>
							${adminInfo ? `
								<div class="d-flex align-items-center">
									<img src="${adminInfo.user_profile_image || '/assets/images/photo_no.png'}" class="rounded-circle me-2" width="40" height="40">
									<div>
										<div><strong>${adminInfo.user_name}</strong></div>
										<small class="text-muted">${adminInfo.user_mail}</small>
									</div>
								</div>
							` : `
								<p class="text-muted">관리자 정보가 없습니다.</p>
							`}
						</div>
					</div>
				`;

				$('#orgDetailContent').html(content);
				$('#orgDetailModal').modal('show');
			} else {
				showToast(response.message, 'error');
			}
		},
		error: function() {
			showToast('조직 정보를 불러오는데 실패했습니다.', 'error');
		}
	});
}

/**
 * 조직 삭제
 */
function deleteOrg(orgId, orgName) {
	showConfirm(
		'조직 삭제',
		`'${orgName}' 조직을 삭제하시겠습니까?<br><small class="text-muted">조직에 회원이 있으면 삭제할 수 없습니다.</small>`,
		function() {
			$.ajax({
				url: '/mng/mng_org/delete_org',
				type: 'POST',
				data: { org_id: orgId },
				dataType: 'json',
				success: function(response) {
					if (response.success) {
						showToast(response.message, 'success');
						loadOrgList(selectedCategoryIdx); // 조직 목록 새로고침
						initializeCategoryTree(); // 카테고리 트리도 새로고침 (카운트 업데이트)
					} else {
						showToast(response.message, 'error');
					}
				},
				error: function() {
					showToast('조직 삭제에 실패했습니다.', 'error');
				}
			});
		}
	);
}

/**
 * Toast 메시지 표시
 */
function showToast(message, type = 'info') {
	const toast = $('#liveToast');
	const toastBody = toast.find('.toast-body');

	// 타입에 따른 스타일 설정
	toast.removeClass('text-bg-success text-bg-danger text-bg-info');
	if (type === 'success') {
		toast.addClass('text-bg-success');
	} else if (type === 'error') {
		toast.addClass('text-bg-danger');
	} else {
		toast.addClass('text-bg-info');
	}

	toastBody.text(message);

	const bsToast = new bootstrap.Toast(toast[0]);
	bsToast.show();
}

/**
 * 확인 모달 표시
 */
function showConfirm(title, message, callback) {
	const modal = $(`
		<div class="modal fade" tabindex="-1">
			<div class="modal-dialog">
				<div class="modal-content">
					<div class="modal-header">
						<h5 class="modal-title">${title}</h5>
						<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
					</div>
					<div class="modal-body">
						${message}
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
						<button type="button" class="btn btn-danger" id="confirmBtn">확인</button>
					</div>
				</div>
			</div>
		</div>
	`);

	modal.find('#confirmBtn').click(function() {
		modal.modal('hide');
		if (callback) callback();
	});

	modal.on('hidden.bs.modal', function() {
		modal.remove();
	});

	$('body').append(modal);
	modal.modal('show');
}
