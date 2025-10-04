/**
 * 파일 위치: assets/js/timeline.js
 * 역할: 타임라인 관리 화면 동작 제어
 */

$(document).ready(function() {
	const baseUrl = window.timelinePageData.baseUrl;
	const currentOrgId = window.timelinePageData.currentOrgId;
	let timelineGrid;
	let timelineTypes = [];

	// PQGrid 초기화
	initPQGrid();

	// 타임라인 항목 로드
	loadTimelineTypes();

	// 이벤트 바인딩
	bindEvents();

	/**
	 * PQGrid 초기화
	 */
	function initPQGrid() {
		const colModel = [
			{
				type: 'checkbox',
				dataIndx: 'pq_select',
				width: 40,
				align: 'center',
				editable: true,
				cb: {
					all: true,
					header: true,
					check: true,
					uncheck: false
				}
			},
			{
				title: '항목',
				dataIndx: 'timeline_type',
				dataType: 'string',
				width: 120
			},
			{
				title: '날짜',
				dataIndx: 'timeline_date',
				dataType: 'string',
				width: 120,
				render: function(ui) {
					if (ui.cellData) {
						const date = new Date(ui.cellData);
						const year = date.getFullYear();
						const month = String(date.getMonth() + 1).padStart(2, '0');
						const day = String(date.getDate()).padStart(2, '0');
						return `${year}-${month}-${day}`;
					}
					return '';
				}
			},
			{
				title: '이름',
				dataIndx: 'member_name',
				dataType: 'string',
				width: 100
			},
			{
				title: '내용',
				dataIndx: 'timeline_content',
				dataType: 'string',
				width: 250
			},
			{
				title: '등록일',
				dataIndx: 'regi_date',
				dataType: 'string',
				width: 150,
				render: function(ui) {
					if (ui.cellData) {
						const date = new Date(ui.cellData);
						const year = date.getFullYear();
						const month = String(date.getMonth() + 1).padStart(2, '0');
						const day = String(date.getDate()).padStart(2, '0');
						const hours = String(date.getHours()).padStart(2, '0');
						const minutes = String(date.getMinutes()).padStart(2, '0');
						const seconds = String(date.getSeconds()).padStart(2, '0');
						return `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
					}
					return '';
				}
			},
			{
				title: '등록자',
				dataIndx: 'regi_user_name',
				dataType: 'string',
				width: 100
			},
			{
				title: '수정일',
				dataIndx: 'modi_date',
				dataType: 'string',
				width: 150,
				render: function(ui) {
					if (ui.cellData) {
						const date = new Date(ui.cellData);
						const year = date.getFullYear();
						const month = String(date.getMonth() + 1).padStart(2, '0');
						const day = String(date.getDate()).padStart(2, '0');
						const hours = String(date.getHours()).padStart(2, '0');
						const minutes = String(date.getMinutes()).padStart(2, '0');
						const seconds = String(date.getSeconds()).padStart(2, '0');
						return `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
					}
					return '';
				}
			}
		];

		const dataModel = {
			location: 'remote',
			dataType: 'JSON',
			method: 'POST',
			url: baseUrl + 'timeline/get_timelines',
			postData: function() {
				return {
					org_id: currentOrgId,
					timeline_type: $('#searchType').val(),
					search_text: $('#searchText').val()
				};
			},
			getData: function(response) {
				return {
					curPage: response.curPage,
					totalRecords: response.totalRecords,
					data: response.data
				};
			}
		};

		const gridOptions = {
			width: '100%',
			height: 600,
			colModel: colModel,
			dataModel: dataModel,
			editable: true,
			pageModel: {
				type: 'remote',
				rPP: 20,
				rPPOptions: [10, 20, 50, 100]
			},
			selectionModel: { type: 'row', mode: 'block' },
			numberCell: { show: false },
			showTitle: false,
			showToolbar: false,
			showBottom: true,
			scrollModel: { autoFit: true },
			strNoRows: '조회된 타임라인이 없습니다.',
			rowDblClick: function(event, ui) {
				editTimeline(ui.rowData);
			}
		};

		timelineGrid = pq.grid('#timelineGrid', gridOptions);
	}

	/**
	 * 타임라인 항목 로드
	 */
	function loadTimelineTypes() {
		$.ajax({
			url: baseUrl + 'timeline/get_timeline_types',
			type: 'POST',
			dataType: 'json',
			data: { org_id: currentOrgId },
			success: function(response) {
				if (response.success) {
					timelineTypes = response.data;
					updateTimelineTypeSelect();
				}
			},
			error: function() {
				showToast('타임라인 항목 로드에 실패했습니다.', 'error');
			}
		});
	}

	/**
	 * 타임라인 항목 셀렉트 업데이트
	 */
	function updateTimelineTypeSelect() {
		$('#searchType, #timeline_type').empty();
		$('#searchType').append('<option value="">전체</option>');
		$('#timeline_type').append('<option value="">항목을 선택하세요</option>');

		timelineTypes.forEach(function(type) {
			$('#searchType').append(`<option value="${type}">${type}</option>`);
			$('#timeline_type').append(`<option value="${type}">${type}</option>`);
		});
	}

	/**
	 * Select2 초기화
	 */
	function initMemberSelect2() {
		$('#member_select').select2({
			theme: 'bootstrap-5',
			width: '100%',
			placeholder: '회원을 선택하세요',
			allowClear: true,
			multiple: true,
			ajax: {
				url: baseUrl + 'timeline/get_members_for_select',
				dataType: 'json',
				delay: 250,
				data: function(params) {
					return {
						org_id: currentOrgId,
						search: params.term
					};
				},
				processResults: function(response) {
					if (response.success) {
						return {
							results: response.data.map(function(member) {
								return {
									id: member.member_idx,
									text: member.member_name
								};
							})
						};
					}
					return { results: [] };
				},
				cache: true
			},
			minimumInputLength: 0
		});
	}

	/**
	 * 이벤트 바인딩
	 */
	function bindEvents() {
		$('#btnSearch').on('click', searchTimelines);
		$('#searchText').on('keypress', function(e) {
			if (e.which === 13) {
				searchTimelines();
			}
		});

		$('#btnAdd').on('click', showAddForm);
		$('#btnEdit').on('click', showEditForm);
		$('#btnDelete').on('click', showDeleteConfirm);

		$('#btnSaveTimeline').on('click', saveTimeline);
		$('#confirmDeleteBtn').on('click', deleteTimelines);

		$('#timelineOffcanvas').on('hidden.bs.offcanvas', resetForm);
	}

	/**
	 * 타임라인 검색
	 */
	function searchTimelines() {
		timelineGrid.refreshDataAndView();
	}

	/**
	 * 추가 폼 표시
	 */
	function showAddForm() {
		resetForm();
		$('#timeline_mode').val('add');
		$('#timelineOffcanvasLabel').text('타임라인 추가');
		$('#memberSelectDiv').show();
		$('#memberNameDiv').hide();

		initMemberSelect2();

		const offcanvas = new bootstrap.Offcanvas(document.getElementById('timelineOffcanvas'));
		offcanvas.show();
	}

	/**
	 * 수정 폼 표시
	 */
	function showEditForm() {
		const allData = timelineGrid.getData();
		const selectedRows = allData.filter(row => row.pq_select === true);

		if (selectedRows.length === 0) {
			showToast('수정할 항목을 선택해주세요.', 'warning');
			return;
		}

		if (selectedRows.length > 1) {
			showToast('한 개의 항목만 선택해주세요.', 'warning');
			return;
		}

		editTimeline(selectedRows[0]);
	}

	/**
	 * 타임라인 수정
	 */
	function editTimeline(rowData) {
		$.ajax({
			url: baseUrl + 'timeline/get_timeline_detail',
			type: 'POST',
			dataType: 'json',
			data: { idx: rowData.idx },
			success: function(response) {
				if (response.success) {
					const timeline = response.data;
					$('#timeline_mode').val('edit');
					$('#timeline_idx').val(timeline.idx);
					$('#timeline_type').val(timeline.timeline_type);
					$('#timeline_date').val(timeline.timeline_date);
					$('#timeline_content').val(timeline.timeline_content);
					$('#member_name_display').val(timeline.member_name);

					$('#timelineOffcanvasLabel').text('타임라인 수정');
					$('#memberSelectDiv').hide();
					$('#memberNameDiv').show();

					const offcanvas = new bootstrap.Offcanvas(document.getElementById('timelineOffcanvas'));
					offcanvas.show();
				} else {
					showToast(response.message || '타임라인 정보를 불러올 수 없습니다.', 'error');
				}
			},
			error: function() {
				showToast('타임라인 정보 로드에 실패했습니다.', 'error');
			}
		});
	}

	/**
	 * 타임라인 저장
	 */
	function saveTimeline() {
		const mode = $('#timeline_mode').val();
		const timeline_type = $('#timeline_type').val();
		const timeline_date = $('#timeline_date').val();

		if (!timeline_type) {
			showToast('항목을 선택해주세요.', 'warning');
			return;
		}

		if (!timeline_date) {
			showToast('날짜를 입력해주세요.', 'warning');
			return;
		}

		if (mode === 'add') {
			const member_idxs = $('#member_select').val();
			if (!member_idxs || member_idxs.length === 0) {
				showToast('회원을 선택해주세요.', 'warning');
				return;
			}

			addTimeline();
		} else {
			updateTimeline();
		}
	}

	/**
	 * 타임라인 추가
	 */
	function addTimeline() {
		const formData = {
			org_id: currentOrgId,
			member_idxs: $('#member_select').val(),
			timeline_type: $('#timeline_type').val(),
			timeline_date: $('#timeline_date').val(),
			timeline_content: $('#timeline_content').val()
		};

		$.ajax({
			url: baseUrl + 'timeline/add_timeline',
			type: 'POST',
			dataType: 'json',
			data: formData,
			success: function(response) {
				if (response.success) {
					showToast(response.message, 'success');
					bootstrap.Offcanvas.getInstance(document.getElementById('timelineOffcanvas')).hide();
					searchTimelines();
				} else {
					showToast(response.message || '타임라인 추가에 실패했습니다.', 'error');
				}
			},
			error: function() {
				showToast('타임라인 추가에 실패했습니다.', 'error');
			}
		});
	}

	/**
	 * 타임라인 수정
	 */
	function updateTimeline() {
		const formData = {
			idx: $('#timeline_idx').val(),
			timeline_type: $('#timeline_type').val(),
			timeline_date: $('#timeline_date').val(),
			timeline_content: $('#timeline_content').val()
		};

		$.ajax({
			url: baseUrl + 'timeline/update_timeline',
			type: 'POST',
			dataType: 'json',
			data: formData,
			success: function(response) {
				if (response.success) {
					showToast(response.message, 'success');
					bootstrap.Offcanvas.getInstance(document.getElementById('timelineOffcanvas')).hide();
					searchTimelines();
				} else {
					showToast(response.message || '타임라인 수정에 실패했습니다.', 'error');
				}
			},
			error: function() {
				showToast('타임라인 수정에 실패했습니다.', 'error');
			}
		});
	}

	/**
	 * 삭제 확인 모달 표시
	 */
	function showDeleteConfirm() {
		const allData = timelineGrid.getData();
		const selectedRows = allData.filter(row => row.pq_select === true);

		if (selectedRows.length === 0) {
			showToast('삭제할 항목을 선택해주세요.', 'warning');
			return;
		}

		const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
		modal.show();
	}

	/**
	 * 타임라인 삭제
	 */
	function deleteTimelines() {
		const allData = memberGrid.getData();
		const selectedRows = allData.filter(row => row.pq_select === true);
		const idxs = selectedRows.map(row => row.idx);

		$.ajax({
			url: baseUrl + 'timeline/delete_timelines',
			type: 'POST',
			dataType: 'json',
			data: { idxs: idxs },
			success: function(response) {
				if (response.success) {
					showToast(response.message, 'success');
					bootstrap.Modal.getInstance(document.getElementById('deleteModal')).hide();
					searchTimelines();
				} else {
					showToast(response.message || '타임라인 삭제에 실패했습니다.', 'error');
				}
			},
			error: function() {
				showToast('타임라인 삭제에 실패했습니다.', 'error');
			}
		});
	}

	/**
	 * 폼 초기화
	 */
	function resetForm() {
		$('#timelineForm')[0].reset();
		$('#timeline_idx').val('');
		$('#timeline_mode').val('add');

		if ($('#member_select').data('select2')) {
			$('#member_select').val(null).trigger('change');
		}
	}
});
