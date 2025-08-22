/**
 * 파일 위치: assets/js/member.js
 * 역할: 회원 관리 페이지 JavaScript - Fancytree와 ParamQuery 연동
 */

$(document).ready(function() {
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
	setTimeout(function() {
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
			success: function(treeData) {
				console.log('트리 데이터:', treeData);

				if (!treeData || treeData.length === 0) {
					showToast('조직 데이터가 없습니다.');
					return;
				}

				// 가장 단순한 Fancytree 설정
				$("#groupTree").fancytree({
					source: treeData,
					activate: function(event, data) {
						const node = data.node;
						const nodeData = node.data;

						console.log('선택된 노드:', nodeData);

						selectedType = nodeData.type;
						selectedOrgId = nodeData.org_id;
						selectedAreaIdx = nodeData.area_idx || null;

						// 선택된 노드에 따라 제목 업데이트
						updateSelectedOrgName(node.title, nodeData.type);

						// 회원 데이터 로드
						loadMemberData();
					},
					// 기본 설정만 사용, 확장 기능 제거
					autoScroll: true,
					keyboard: true,
					focusOnSelect: true
				});

				// 트리가 로드된 후 첫 번째 조직 자동 선택
				const tree = $("#groupTree").fancytree("getTree");
				if (tree && tree.rootNode && tree.rootNode.children.length > 0) {
					const firstOrgNode = tree.rootNode.children[0];
					firstOrgNode.setActive(true);
				}

				console.log('Fancytree 초기화 완료');
			},
			error: function(xhr, status, error) {
				console.error('그룹 트리 로드 실패:', error);
				console.error('Response:', xhr.responseText);
				showToast('그룹 정보를 불러오는데 실패했습니다.');
			}
		});
	}

	/**
	 * ParamQuery Grid 초기화 (3.5.1 버전 호환)
	 */
	function initializeParamQuery() {
		console.log('ParamQuery 초기화 시작');

		const colModel = [
			{
				title: "회원번호",
				dataIndx: "member_idx",
				width: 80,
				align: "center",
				hidden: true
			},
			{
				title: "이름",
				dataIndx: "member_name",
				width: 120,
				align: "center"
			},
			{
				title: "소그룹",
				dataIndx: "area_name",
				width: 100,
				align: "center"
			},
			{
				title: "학년",
				dataIndx: "grade",
				width: 60,
				align: "center"
			},
			{
				title: "생년월일",
				dataIndx: "member_birth",
				width: 100,
				align: "center"
			},
			{
				title: "리더",
				dataIndx: "leader_yn",
				width: 60,
				align: "center",
				render: function(ui) {
					return ui.cellData === 'Y' ? '<i class="bi bi-check-circle-fill text-success"></i>' : '';
				}
			},
			{
				title: "신규",
				dataIndx: "new_yn",
				width: 60,
				align: "center",
				render: function(ui) {
					return ui.cellData === 'Y' ? '<i class="bi bi-star-fill text-warning"></i>' : '';
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
			height: 500,
			dataModel: {
				data: []
			},
			colModel: colModel,
			selectionModel: {
				type: 'row',
				mode: 'single'
			},
			scrollModel: {
				autoFit: true
			},
			numberCell: {
				show: false
			},
			title: false,
			resizable: true,
			sortable: false,
			hoverMode: 'row',
			selectEnd: function(event, ui) {
				// 선택 이벤트 처리
				const hasSelection = ui.selection && ui.selection.length > 0;
				$('#btnEditMember, #btnDeleteMember').prop('disabled', !hasSelection);
			}
		};

		try {
			memberGrid = $("#memberGrid").pqGrid(gridOptions);
			console.log('ParamQuery 초기화 완료');
		} catch (error) {
			console.error('ParamQuery Grid 초기화 실패:', error);
			showToast('그리드 초기화에 실패했습니다.');
		}
	}

	/**
	 * 이벤트 바인딩
	 */
	function bindEvents() {
		// 회원 추가 버튼
		$('#btnAddMember').on('click', function() {
			if (!selectedOrgId) {
				showToast('먼저 그룹을 선택해주세요.');
				return;
			}
			openMemberModal('add');
		});

		// 회원 수정 버튼
		$('#btnEditMember').on('click', function() {
			try {
				const selectedData = memberGrid.pqGrid("getSelection");
				if (!selectedData || selectedData.length === 0) {
					showToast('수정할 회원을 선택해주세요.');
					return;
				}
				openMemberModal('edit', selectedData[0]);
			} catch (error) {
				console.error('선택 데이터 가져오기 실패:', error);
				showToast('선택된 데이터를 가져올 수 없습니다.');
			}
		});

		// 회원 삭제 버튼
		$('#btnDeleteMember').on('click', function() {
			try {
				const selectedData = memberGrid.pqGrid("getSelection");
				if (!selectedData || selectedData.length === 0) {
					showToast('삭제할 회원을 선택해주세요.');
					return;
				}
				deleteMember(selectedData[0].member_idx);
			} catch (error) {
				console.error('선택 데이터 가져오기 실패:', error);
				showToast('선택된 데이터를 가져올 수 없습니다.');
			}
		});

		// 회원 저장 버튼
		$('#btnSaveMember').on('click', function() {
			saveMember();
		});
	}

	/**
	 * 선택된 그룹명 업데이트
	 */
	function updateSelectedOrgName(title, type) {
		const icon = type === 'org' ? '<i class="bi bi-diagram-3"></i>' : '<i class="bi bi-people"></i>';
		$('#selectedOrgName').html(icon + ' ' + title);
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
			success: function(response) {
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

					showToast(`${response.data ? response.data.length : 0}명의 회원을 로드했습니다.`);
				} else {
					console.error('회원 데이터 로드 실패:', response.message);
					showToast('회원 데이터를 불러오는데 실패했습니다.');
				}
			},
			error: function(xhr, status, error) {
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

		// 소그룹 옵션 로드
		loadAreaOptions(selectedOrgId);

		if (mode === 'edit' && memberData) {
			// 수정 모드일 때 기존 데이터 채우기
			$('#member_idx').val(memberData.member_idx);
			$('#member_name').val(memberData.member_name);
			$('#member_birth').val(memberData.member_birth);
			$('#grade').val(memberData.grade);
			$('#area_idx').val(memberData.area_idx);
			$('#leader_yn').prop('checked', memberData.leader_yn === 'Y');
			$('#new_yn').prop('checked', memberData.new_yn === 'Y');
		}

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
				groupNode.children.forEach(function(child) {
					const areaData = child.data;
					areaSelect.append(`<option value="${areaData.area_idx}">${child.title}</option>`);
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
			member_birth: $('#member_birth').val(),
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
			success: function(response) {
				if (response.success) {
					showToast(response.message);
					$('#memberModal').modal('hide');
					loadMemberData();
				} else {
					showToast(response.message);
				}
			},
			error: function(xhr, status, error) {
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
			success: function(response) {
				if (response.success) {
					showToast(response.message);
					loadMemberData();
				} else {
					showToast(response.message);
				}
			},
			error: function(xhr, status, error) {
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
		toastElement.addEventListener('hidden.bs.toast', function() {
			$(this).remove();
		});
	}
});
