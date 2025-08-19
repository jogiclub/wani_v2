/**
 * 파일 위치: E:\SynologyDrive\Example\wani\assets\js\member.js
 * 역할: 회원 관리 페이지 JavaScript - Fancytree와 ParamQuery 연동
 */

$(document).ready(function() {
	// 전역 변수
	let memberGrid;
	let selectedOrgId = null;
	let selectedAreaIdx = null;
	let selectedType = null;

	// 페이지 초기화
	initializePage();

	/**
	 * 페이지 초기화
	 */
	function initializePage() {
		initializeFancytree();
		initializeParamQuery();
		bindEvents();
	}

	/**
	 * Fancytree 초기화
	 */
	function initializeFancytree() {
		$.ajax({
			url: window.memberPageData.baseUrl + 'member/get_org_tree',
			method: 'POST',
			dataType: 'json',
			success: function(treeData) {
				$("#groupTree").fancytree({
					source: treeData,
					activate: function(event, data) {
						const node = data.node;
						const nodeData = node.data;

						selectedType = nodeData.type;
						selectedOrgId = nodeData.org_id;
						selectedAreaIdx = nodeData.area_idx || null;

						// 선택된 노드에 따라 제목 업데이트
						updateSelectedOrgName(node.title, nodeData.type);

						// 회원 데이터 로드
						loadMemberData();
					},
					extensions: ["wide"],
					wide: {
						iconWidth: "1em",
						iconSpacing: "0.5em",
						levelOfs: "1.5em"
					}
				});
			},
			error: function(xhr, status, error) {
				console.error('그룹 트리 로드 실패:', error);
				alert('그룹 정보를 불러오는데 실패했습니다.');
			}
		});
	}

	/**
	 * ParamQuery Grid 초기화
	 */
	function initializeParamQuery() {
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
				const selectedRow = ui.selection.length > 0;
				$('#btnEditMember, #btnDeleteMember').prop('disabled', !selectedRow);
			}
		};

		memberGrid = pq.grid("#memberGrid", gridOptions);
	}

	/**
	 * 이벤트 바인딩
	 */
	function bindEvents() {
		// 회원 추가 버튼
		$('#btnAddMember').on('click', function() {
			if (!selectedOrgId) {
				alert('먼저 그룹을 선택해주세요.');
				return;
			}
			openMemberModal('add');
		});

		// 회원 수정 버튼
		$('#btnEditMember').on('click', function() {
			const selectedData = memberGrid.getSelection();
			if (selectedData.length === 0) {
				alert('수정할 회원을 선택해주세요.');
				return;
			}
			openMemberModal('edit', selectedData[0]);
		});

		// 회원 삭제 버튼
		$('#btnDeleteMember').on('click', function() {
			const selectedData = memberGrid.getSelection();
			if (selectedData.length === 0) {
				alert('삭제할 회원을 선택해주세요.');
				return;
			}
			deleteMember(selectedData[0].member_idx);
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
		const icon = type === 'group' ? '<i class="bi bi-diagram-3"></i>' : '<i class="bi bi-people"></i>';
		$('#selectedOrgName').html(icon + ' ' + title);
	}

	/**
	 * 회원 데이터 로드
	 */
	function loadMemberData() {
		if (!selectedOrgId) return;

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
				if (response.success) {
					memberGrid.option("dataModel.data", response.data);
					memberGrid.refreshDataAndView();

					// 버튼 상태 초기화
					$('#btnEditMember, #btnDeleteMember').prop('disabled', true);
				} else {
					console.error('회원 데이터 로드 실패:', response.message);
					alert('회원 데이터를 불러오는데 실패했습니다.');
				}
			},
			error: function(xhr, status, error) {
				console.error('회원 데이터 로드 실패:', error);
				alert('회원 데이터를 불러오는데 실패했습니다.');
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
		const tree = $("#groupTree").fancytree("getTree");
		const groupNode = tree.getNodeByKey('org_' + orgId);

		if (groupNode && groupNode.children) {
			groupNode.children.forEach(function(child) {
				const areaData = child.data;
				areaSelect.append(`<option value="${areaData.area_idx}">${child.title}</option>`);
			});
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
					alert(response.message);
					$('#memberModal').modal('hide');
					loadMemberData();
				} else {
					alert(response.message);
				}
			},
			error: function(xhr, status, error) {
				console.error('회원 저장 실패:', error);
				alert('회원 정보 저장에 실패했습니다.');
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
					alert(response.message);
					loadMemberData();
				} else {
					alert(response.message);
				}
			},
			error: function(xhr, status, error) {
				console.error('회원 삭제 실패:', error);
				alert('회원 삭제에 실패했습니다.');
			}
		});
	}
});
