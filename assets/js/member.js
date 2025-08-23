/**
 * 파일 위치: assets/js/member.js
 * 역할: 회원 관리 페이지 JavaScript - Fancytree와 ParamQuery 연동
 */

$(document).ready(function () {
	// 전역 변수
	let memberGrid;
	let selectedOrgId = null;
	let selectedAreaIdx = null;
	let selectedType = null;

	// 디버깅: 라이브러리 로드 상태 확인
	console.log('jQuery:', typeof $);
	console.log('Fancytree:', typeof $.fn.fancytree);
	console.log('ParamQuery pqGrid:', typeof $.fn.pqGrid);

	// 초기화 시도
	setTimeout(function () {
		initializePage();
	}, 800); // 800ms 지연 후 초기화

	/**
	 * 페이지 초기화
	 */
	function initializePage() {
		console.log('페이지 초기화 시작');

		// ParamQuery 체크
		if (typeof $.fn.pqGrid === 'undefined') {
			console.error('ParamQuery 라이브러리가 로드되지 않았습니다.');
			showToast('ParamQuery 라이브러리 로드 실패');
			return;
		}

		// Fancytree 체크
		if (typeof $.fn.fancytree === 'undefined') {
			console.error('Fancytree 라이브러리가 로드되지 않았습니다.');
			showToast('Fancytree 라이브러리 로드 실패');
			return;
		}

		try {
			initializeFancytree();
			initializeParamQuery();
			bindEvents();
			bindPhotoEvents();
			console.log('페이지 초기화 완료');
		} catch (error) {
			console.error('초기화 중 오류:', error);
			showToast('페이지 초기화 중 오류가 발생했습니다.');
		}
	}

	/**
	 * Fancytree 초기화
	 */
	function initializeFancytree() {
		console.log('Fancytree 초기화 시작');

		$.ajax({
			url: window.memberPageData.baseUrl + 'member/get_org_tree',
			method: 'POST',
			dataType: 'json',
			success: function (treeData) {
				console.log('트리 데이터:', treeData);

				if (!treeData || treeData.length === 0) {
					showToast('조직 데이터가 없습니다.');
					return;
				}

				// Fancytree 설정
				$("#groupTree").fancytree({
					source: treeData,
					activate: function (event, data) {
						const node = data.node;
						const nodeData = node.data;

						console.log('선택된 노드:', nodeData);

						selectedType = nodeData.type;
						selectedOrgId = nodeData.org_id;
						selectedAreaIdx = nodeData.area_idx || null;

						// localStorage에 선택된 소그룹 저장
						saveSelectedGroupToStorage(nodeData);

						// 선택된 노드에 따라 제목 업데이트
						updateSelectedOrgName(node.title, nodeData.type);

						// 회원 데이터 로드
						loadMemberData();
					},
					autoScroll: true,
					keyboard: true,
					focusOnSelect: true
				});

				// 트리가 로드된 후 저장된 선택 상태 복원
				restoreSelectedGroupFromStorage(treeData);

				console.log('Fancytree 초기화 완료');
			},
			error: function (xhr, status, error) {
				console.error('그룹 트리 로드 실패:', error);
				console.error('Response:', xhr.responseText);
				showToast('그룹 정보를 불러오는데 실패했습니다.');
			}
		});
	}


	/**
	 * 선택된 그룹을 localStorage에 저장
	 */
	function saveSelectedGroupToStorage(nodeData) {
		try {
			const selectedGroup = {
				type: nodeData.type,
				org_id: nodeData.org_id,
				area_idx: nodeData.area_idx || null,
				timestamp: Date.now()
			};

			localStorage.setItem('member_selected_group', JSON.stringify(selectedGroup));
			console.log('선택된 그룹 저장됨:', selectedGroup);
		} catch (error) {
			console.error('localStorage 저장 실패:', error);
		}
	}


	/**
	 * localStorage에서 저장된 그룹 선택 상태 복원
	 */
	function restoreSelectedGroupFromStorage(treeData) {
		try {
			const savedGroup = localStorage.getItem('member_selected_group');

			if (!savedGroup) {
				// 저장된 선택이 없으면 첫 번째 조직 자동 선택
				selectFirstOrganization();
				return;
			}

			const groupData = JSON.parse(savedGroup);

			// 7일 이내의 데이터만 복원 (선택적)
			const sevenDaysAgo = Date.now() - (7 * 24 * 60 * 60 * 1000);
			if (groupData.timestamp < sevenDaysAgo) {
				localStorage.removeItem('member_selected_group');
				selectFirstOrganization();
				return;
			}

			const tree = $("#groupTree").fancytree("getTree");
			let nodeToSelect = null;

			// 저장된 그룹이 미분류인 경우 (root 레벨에서 찾기)
			if (groupData.type === 'unassigned' && groupData.org_id) {
				nodeToSelect = tree.getNodeByKey('unassigned_' + groupData.org_id);
			}
			// 저장된 그룹이 소그룹(area)인 경우
			else if (groupData.type === 'area' && groupData.area_idx) {
				nodeToSelect = tree.getNodeByKey('area_' + groupData.area_idx);
			}
			// 저장된 그룹이 조직(org)인 경우 또는 다른 그룹을 찾을 수 없는 경우
			if (!nodeToSelect && groupData.org_id) {
				nodeToSelect = tree.getNodeByKey('org_' + groupData.org_id);
			}

			if (nodeToSelect) {
				nodeToSelect.setActive(true);
				nodeToSelect.setFocus(true);

				// 미분류가 아닌 경우에만 상위 노드 확장 (미분류는 root 레벨이므로 상위 노드가 없음)
				if (groupData.type !== 'unassigned' && nodeToSelect.parent && nodeToSelect.parent.isRootNode() === false) {
					nodeToSelect.parent.setExpanded(true);
				}

				console.log('저장된 그룹 선택 복원됨:', groupData);
			} else {
				console.log('저장된 그룹을 찾을 수 없음, 첫 번째 조직 선택');
				selectFirstOrganization();
			}

		} catch (error) {
			console.error('localStorage 복원 실패:', error);
			selectFirstOrganization();
		}
	}

	/**
	 * 첫 번째 조직 자동 선택
	 */
	function selectFirstOrganization() {
		const tree = $("#groupTree").fancytree("getTree");
		if (tree && tree.rootNode && tree.rootNode.children.length > 0) {
			const firstOrgNode = tree.rootNode.children[0];
			firstOrgNode.setActive(true);
			firstOrgNode.setFocus(true);
			firstOrgNode.setExpanded(true);
		}
	}

	/**
	 * ParamQuery Grid 초기화 (3.5.1 버전 호환)
	 */
	function initializeParamQuery() {
		console.log('ParamQuery 초기화 시작');

		const colModel = [
			{
				title: '<input type="checkbox" id="selectAllCheckbox" />',
				dataIndx: "pq_selected",
				width: 50,
				align: "center",
				resizable: false,
				sortable: false,
				menuIcon: false,
				frozen: true,  // 체크박스 컬럼 고정
				render: function (ui) {
					return '<input type="checkbox" class="member-checkbox" data-member-idx="' + ui.rowData.member_idx + '" />';
				}
			},
			{
				title: "소그룹",
				dataIndx: "area_name",
				width: 100,
				align: "center",
				frozen: true  // 소그룹 컬럼 고정
			},
			{
				title: "사진",
				dataIndx: "photo",
				width: 80,
				align: "center",
				frozen: true,  // 사진 컬럼 고정
				render: function (ui) {
					const photoUrl = ui.cellData || '/assets/images/photo_no.png';
					return '<img src="' + photoUrl + '" alt="사진" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">';
				}
			},
			{
				title: "이름",
				dataIndx: "member_name",
				width: 120,
				align: "center",
				frozen: true  // 이름 컬럼 고정
			},
			{
				title: "회원번호",
				dataIndx: "member_idx",
				width: 80,
				align: "center",
				hidden: true
			},
			{
				title: "휴대폰번호",
				dataIndx: "member_phone",
				width: 140,
				align: "center"
			},
			{
				title: "생년월일",
				dataIndx: "member_birth",
				width: 110,
				align: "center"
			},
			{
				title: "주소",
				dataIndx: "member_address",
				width: 250,
				align: "left"
			},
			{
				title: "등급",
				dataIndx: "grade",
				width: 80,
				align: "center"
			},
			{
				title: "리더",
				dataIndx: "leader_yn",
				width: 60,
				align: "center",
				render: function (ui) {
					return ui.cellData === 'Y' ? '<i class="bi bi-check-circle-fill text-success"></i>' : '';
				}
			},
			{
				title: "신규",
				dataIndx: "new_yn",
				width: 60,
				align: "center",
				render: function (ui) {
					return ui.cellData === 'Y' ? '<i class="bi bi-star-fill text-warning"></i>' : '';
				}
			},
			{
				title: "등록일",
				dataIndx: "regi_date",
				width: 120,
				align: "center",
				render: function (ui) {
					if (ui.cellData) {
						const date = new Date(ui.cellData);
						return date.toLocaleDateString('ko-KR');
					}
					return '';
				}
			},
			{
				title: "수정일",
				dataIndx: "modi_date",
				width: 120,
				align: "center",
				render: function (ui) {
					if (ui.cellData) {
						const date = new Date(ui.cellData);
						return date.toLocaleDateString('ko-KR');
					}
					return '';
				}
			},
			{
				title: "소그룹번호",
				dataIndx: "area_idx",
				width: 80,
				align: "center",
				hidden: true
			}
		];

		const gridOptions = {
			width: "100%",
			height: "100%",
			dataModel: {
				data: []
			},
			colModel: colModel,
			selectionModel: {
				type: 'row',
				mode: 'single'
			},
			scrollModel: {
				autoFit: false,
				horizontal: true,
				vertical: true
			},
			freezeCols: 4,  // 첫 4개 컬럼 고정 (체크박스, 소그룹, 사진, 이름)
			numberCell: {
				show: false
			},
			title: false,
			resizable: true,
			sortable: false,
			hoverMode: 'row',
			wrap: false,
			columnBorders: true,
			selectEnd: function (event, ui) {
				const hasSelection = ui.selection && ui.selection.length > 0;
				$('#btnEditMember, #btnDeleteMember').prop('disabled', !hasSelection);
			},
			refresh: function () {
				bindCheckboxEvents();
			}
		};

		try {
			memberGrid = $("#memberGrid").pqGrid(gridOptions);
			console.log('ParamQuery 초기화 완료');

			bindCheckboxEvents();
		} catch (error) {
			console.error('ParamQuery Grid 초기화 실패:', error);
			showToast('그리드 초기화에 실패했습니다.');
		}
	}


	/**
	 * 체크박스 이벤트 바인딩
	 */
	function bindCheckboxEvents() {
		// 전체 선택 체크박스 이벤트
		$(document).off('change', '#selectAllCheckbox').on('change', '#selectAllCheckbox', function () {
			const isChecked = $(this).is(':checked');
			$('.member-checkbox').prop('checked', isChecked);
			updateSelectedMemberButtons();
		});

		// 개별 체크박스 이벤트
		$(document).off('change', '.member-checkbox').on('change', '.member-checkbox', function () {
			updateSelectAllCheckbox();
			updateSelectedMemberButtons();
		});
	}

	/**
	 * 전체 선택 체크박스 상태 업데이트
	 */
	function updateSelectAllCheckbox() {
		const totalCheckboxes = $('.member-checkbox').length;
		const checkedCheckboxes = $('.member-checkbox:checked').length;

		const selectAllCheckbox = $('#selectAllCheckbox');

		if (checkedCheckboxes === 0) {
			selectAllCheckbox.prop('checked', false).prop('indeterminate', false);
		} else if (checkedCheckboxes === totalCheckboxes) {
			selectAllCheckbox.prop('checked', true).prop('indeterminate', false);
		} else {
			selectAllCheckbox.prop('checked', false).prop('indeterminate', true);
		}
	}

	/**
	 * 선택된 회원 기반 버튼 상태 업데이트
	 */
	function updateSelectedMemberButtons() {
		const checkedCheckboxes = $('.member-checkbox:checked').length;

		if (checkedCheckboxes > 0) {
			$('#btnDeleteMember').prop('disabled', false);
			if (checkedCheckboxes === 1) {
				$('#btnEditMember').prop('disabled', false);
			} else {
				$('#btnEditMember').prop('disabled', true);
			}
		} else {
			$('#btnEditMember, #btnDeleteMember').prop('disabled', true);
		}
	}

	/**
	 * 선택된 회원 데이터 가져오기
	 */
	function getSelectedMembers() {
		const selectedMembers = [];
		$('.member-checkbox:checked').each(function () {
			const memberIdx = $(this).data('member-idx');
			// 그리드에서 해당 회원 데이터 찾기
			const gridData = memberGrid.pqGrid("option", "dataModel.data");
			const memberData = gridData.find(member => member.member_idx == memberIdx);
			if (memberData) {
				selectedMembers.push(memberData);
			}
		});
		return selectedMembers;
	}




	/**
	 * 회원 그리드 새로고침
	 */
	function refreshMemberGrid(nodeData) {
		if (!currentGridData) return;

		// 현재 선택된 그룹의 회원 목록 다시 로드
		loadMembersForGroup(nodeData);
	}

	/**
	 * 이벤트 바인딩
	 */
	function bindEvents() {
		// 기존 회원 추가 모달 관련 코드 제거 - 이 부분을 삭제
		// $('#btnAddMember').on('click', function () {
		//     if (!selectedOrgId) {
		//         showToast('먼저 그룹을 선택해주세요.');
		//         return;
		//     }
		//     openMemberModal('add');
		// });

		// 회원 수정 버튼
		$('#btnEditMember').on('click', function () {
			const selectedMembers = getSelectedMembers();
			if (selectedMembers.length === 0) {
				showToast('수정할 회원을 선택해주세요.');
				return;
			}
			if (selectedMembers.length > 1) {
				showToast('수정은 한 명씩만 가능합니다.');
				return;
			}
			openMemberModal('edit', selectedMembers[0]);
		});

		// 회원 삭제 버튼
		$('#btnDeleteMember').on('click', function () {
			const selectedMembers = getSelectedMembers();
			if (selectedMembers.length === 0) {
				showToast('삭제할 회원을 선택해주세요.');
				return;
			}
			deleteSelectedMembers(selectedMembers);
		});

		// 회원 저장 버튼
		$('#btnSaveMember').on('click', function () {
			saveMember();
		});
	}

	// 회원 추가 버튼 이벤트 - 이 부분은 유지
	$(document).on('click', '#btnAddMember', function (e) {
		e.preventDefault();

		// 현재 선택된 그룹 정보 확인
		const tree = $("#groupTree").fancytree("getTree");
		const activeNode = tree.getActiveNode();

		if (!activeNode) {
			showToast('그룹을 선택해주세요.');
			return;
		}

		const nodeData = activeNode.data;

		// 가장 상위 그룹(조직)에서는 추가 불가
		if (nodeData.type === 'org') {
			showToast('가장 상위 그룹에서는 회원을 추가할 수 없습니다. 하위 그룹을 선택해주세요.');
			return;
		}

		// 바로 회원 추가 실행
		addNewMember(nodeData);
	});

	/**
	 * 새 회원 추가
	 */
	function addNewMember(nodeData) {
		const addData = {
			org_id: nodeData.org_id,
			area_idx: nodeData.area_idx || null
		};

		$.ajax({
			url: '/member/add_member',
			type: 'POST',
			data: addData,
			dataType: 'json',
			success: function (response) {
				if (response.success) {
					showToast('회원이 추가되었습니다: ' + response.member_name);

					// 그리드 새로고침 - 새로 추가된 회원이 가장 상단에 표시되도록
					loadMemberData();

				} else {
					showToast(response.message || '회원 추가에 실패했습니다.');
				}
			},
			error: function (xhr, status, error) {
				console.error('회원 추가 오류:', error);
				showToast('회원 추가 중 오류가 발생했습니다.');
			}
		});
	}


	/**
	 * 선택된 회원들 삭제
	 */
	function deleteSelectedMembers(selectedMembers) {
		const memberCount = selectedMembers.length;
		const memberNames = selectedMembers.map(member => member.member_name).join(', ');

		if (!confirm(`정말로 ${memberCount}명의 회원(${memberNames})을 삭제하시겠습니까?`)) {
			return;
		}

		const memberIndices = selectedMembers.map(member => member.member_idx);

		$.ajax({
			url: window.memberPageData.baseUrl + 'member/delete_members',
			method: 'POST',
			data: {
				member_indices: memberIndices
			},
			dataType: 'json',
			success: function (response) {
				if (response.success) {
					showToast(response.message);
					loadMemberData();
				} else {
					showToast(response.message);
				}
			},
			error: function (xhr, status, error) {
				console.error('회원 삭제 실패:', error);
				showToast('회원 삭제에 실패했습니다.');
			}
		});
	}


	/**
	 * 선택된 조직명 업데이트
	 */
	function updateSelectedOrgName(title, type) {
		const orgNameElement = $('#selectedOrgName');
		if (orgNameElement.length) {
			if (type === 'org') {
				orgNameElement.text(`${title} - 전체 회원`);
			} else if (type === 'area') {
				orgNameElement.text(`${title} 소그룹`);
			} else if (type === 'unassigned') {
				orgNameElement.text(`미분류 회원`);
			}
		}
	}

	/**
	 * 회원 데이터 로드
	 */
	function loadMemberData() {
		if (!selectedOrgId) return;

		console.log('회원 데이터 로드:', {
			type: selectedType,
			org_id: selectedOrgId,
			area_idx: selectedAreaIdx
		});

		$.ajax({
			url: window.memberPageData.baseUrl + 'member/get_members',
			method: 'POST',
			data: {
				type: selectedType,
				org_id: selectedOrgId,
				area_idx: selectedAreaIdx
			},
			dataType: 'json',
			success: function (response) {
				console.log('회원 데이터 응답:', response);

				if (response.success) {
					if (memberGrid) {
						try {
							memberGrid.pqGrid("option", "dataModel.data", response.data || []);
							memberGrid.pqGrid("refreshDataAndView");
						} catch (error) {
							console.error('그리드 데이터 업데이트 실패:', error);
						}
					}

					// 버튼 상태 초기화
					$('#btnEditMember, #btnDeleteMember').prop('disabled', true);

					// Toast 메시지 제거
					// showToast(`${response.data ? response.data.length : 0}명의 회원을 로드했습니다.`);
				} else {
					console.error('회원 데이터 로드 실패:', response.message);
					showToast('회원 데이터를 불러오는데 실패했습니다.');
				}
			},
			error: function (xhr, status, error) {
				console.error('회원 데이터 로드 실패:', error);
				console.error('Response:', xhr.responseText);
				showToast('회원 데이터를 불러오는데 실패했습니다.');
			}
		});
	}

	/**
	 * 회원 모달 열기
	 */
	function openMemberModal(mode, memberData = null) {
		const modal = $('#memberModal');
		const title = mode === 'add' ? '회원 추가' : '회원 정보 수정';

		$('#memberModalLabel').text(title);

		// 폼 초기화
		$('#memberForm')[0].reset();
		$('#photoPreview').hide();

		// 소그룹 옵션 로드
		loadAreaOptions(selectedOrgId);

		if (mode === 'edit' && memberData) {
			// 수정 모드일 때 기존 데이터 채우기
			$('#member_idx').val(memberData.member_idx);
			$('#member_name').val(memberData.member_name);
			$('#member_phone').val(memberData.member_phone);
			$('#member_birth').val(memberData.member_birth);
			$('#member_address').val(memberData.member_address);
			$('#grade').val(memberData.grade);
			$('#area_idx').val(memberData.area_idx);
			$('#leader_yn').prop('checked', memberData.leader_yn === 'Y');
			$('#new_yn').prop('checked', memberData.new_yn === 'Y');

			// 기존 사진이 있으면 미리보기 표시
			if (memberData.photo && memberData.photo !== '/assets/images/photo_no.png') {
				$('#previewImage').attr('src', memberData.photo);
				$('#photoPreview').show();
			}
		}

		// 사진 이벤트 바인딩
		bindPhotoEvents();

		modal.modal('show');
	}

	/**
	 * 소그룹 옵션 로드
	 */
	function loadAreaOptions(orgId) {
		const areaSelect = $('#area_idx');
		areaSelect.html('<option value="">소그룹 선택</option>');

		// 현재 선택된 그룹의 소그룹 목록을 트리에서 찾기
		try {
			const tree = $("#groupTree").fancytree("getTree");
			const groupNode = tree.getNodeByKey('org_' + orgId);

			if (groupNode && groupNode.children) {
				groupNode.children.forEach(function (child) {
					const areaData = child.data;
					// 미분류 그룹은 제외하고 일반 소그룹만 추가
					if (areaData.type === 'area') {
						areaSelect.append(`<option value="${areaData.area_idx}">${child.title}</option>`);
					}
				});
			}
		} catch (error) {
			console.error('소그룹 옵션 로드 오류:', error);
		}
	}

	/**
	 * 회원 저장
	 */
	function saveMember() {
		const formData = {
			member_idx: $('#member_idx').val(),
			member_name: $('#member_name').val(),
			member_phone: $('#member_phone').val(),
			member_birth: $('#member_birth').val(),
			member_address: $('#member_address').val(),
			grade: $('#grade').val(),
			area_idx: $('#area_idx').val(),
			leader_yn: $('#leader_yn').is(':checked') ? 'Y' : 'N',
			new_yn: $('#new_yn').is(':checked') ? 'Y' : 'N',
			org_id: selectedOrgId
		};

		const url = formData.member_idx ?
			window.memberPageData.baseUrl + 'member/update_member' :
			window.memberPageData.baseUrl + 'member/add_member';

		$.ajax({
			url: url,
			method: 'POST',
			data: formData,
			dataType: 'json',
			success: function (response) {
				if (response.success) {
					const memberIdx = formData.member_idx || response.member_idx;

					// 사진이 선택되었으면 업로드 처리
					const photoFile = $('#photo')[0].files[0];
					if (photoFile && memberIdx) {
						uploadMemberPhoto(memberIdx, function (photoSuccess, photoUrl) {
							if (photoSuccess) {
								showToast('회원 정보와 사진이 저장되었습니다.');
							} else {
								showToast('회원 정보는 저장되었으나 사진 업로드에 실패했습니다.');
							}
							$('#memberModal').modal('hide');
							loadMemberData();
						});
					} else {
						showToast(response.message);
						$('#memberModal').modal('hide');
						loadMemberData();
					}
				} else {
					showToast(response.message);
				}
			},
			error: function (xhr, status, error) {
				console.error('회원 저장 실패:', error);
				showToast('회원 정보 저장에 실패했습니다.');
			}
		});
	}

	/**
	 * 회원 삭제
	 */
	function deleteMember(memberIdx) {
		if (!confirm('정말로 이 회원을 삭제하시겠습니까?')) {
			return;
		}

		$.ajax({
			url: window.memberPageData.baseUrl + 'member/delete_member',
			method: 'POST',
			data: {
				member_idx: memberIdx
			},
			dataType: 'json',
			success: function (response) {
				if (response.success) {
					showToast(response.message);
					loadMemberData();
				} else {
					showToast(response.message);
				}
			},
			error: function (xhr, status, error) {
				console.error('회원 삭제 실패:', error);
				showToast('회원 삭제에 실패했습니다.');
			}
		});
	}

	/**
	 * Toast 메시지 표시
	 */
	function showToast(message) {
		// Bootstrap Toast 컨테이너가 없으면 생성
		if ($('#toastContainer').length === 0) {
			$('body').append(`
				<div id="toastContainer" class="toast-container position-fixed top-0 end-0 p-3">
				</div>
			`);
		}

		// Toast 요소 생성
		const toastId = 'toast_' + Date.now();
		const toastHtml = `
			<div id="${toastId}" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
				<div class="toast-header">
					<i class="bi bi-info-circle-fill text-primary me-2"></i>
					<strong class="me-auto">알림</strong>
					<button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
				</div>
				<div class="toast-body">
					${message}
				</div>
			</div>
		`;

		$('#toastContainer').append(toastHtml);

		// Bootstrap Toast 초기화 및 표시
		const toastElement = document.getElementById(toastId);
		const toast = new bootstrap.Toast(toastElement, {
			delay: 3000
		});
		toast.show();

		// Toast가 숨겨진 후 DOM에서 제거
		toastElement.addEventListener('hidden.bs.toast', function () {
			$(this).remove();
		});
	}

	/**
	 * 사진 미리보기 이벤트 바인딩
	 */
	function bindPhotoEvents() {
		// 사진 파일 선택 시 미리보기
		$('#photo').on('change', function (e) {
			const file = e.target.files[0];
			if (file) {
				const reader = new FileReader();
				reader.onload = function (e) {
					$('#previewImage').attr('src', e.target.result);
					$('#photoPreview').show();
				};
				reader.readAsDataURL(file);
			} else {
				$('#photoPreview').hide();
			}
		});
	}

	/**
	 * 사진 업로드 처리
	 */
	function uploadMemberPhoto(memberIdx, callback) {
		const photoFile = $('#photo')[0].files[0];

		if (!photoFile) {
			if (callback) callback(true, null);
			return;
		}

		const formData = new FormData();
		formData.append('photo', photoFile);
		formData.append('member_idx', memberIdx);
		formData.append('org_id', selectedOrgId);

		$.ajax({
			url: window.memberPageData.baseUrl + 'member/upload_member_photo',
			method: 'POST',
			data: formData,
			processData: false,
			contentType: false,
			dataType: 'json',
			success: function (response) {
				if (response.success) {
					if (callback) callback(true, response.photo_url);
				} else {
					showToast(response.message);
					if (callback) callback(false, null);
				}
			},
			error: function (xhr, status, error) {
				console.error('사진 업로드 실패:', error);
				showToast('사진 업로드에 실패했습니다.');
				if (callback) callback(false, null);
			}
		});
	}

});
