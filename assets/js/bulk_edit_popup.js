let bulkEditGridInstance = null;

$(document).ready(function() {
	// 세션 스토리지에서 데이터 가져오기
	const dataString = sessionStorage.getItem('bulkEditData');
	if (!dataString) {
		showToast('데이터를 불러올 수 없습니다.', 'error');
		window.close();
		return;
	}

	const data = JSON.parse(dataString);
	initBulkEditGrid(data);

	// 저장 버튼 클릭
	$('#btnSaveBulkEdit').on('click', function() {
		saveBulkEdit();
	});

	// 셀 추가 버튼 클릭
	$('#addCell').on('click', function() {
		addEmptyCells();
	});

	// Enter 키로도 셀 추가 가능
	$('#addCellCount').on('keypress', function(e) {
		if (e.which === 13) {
			addEmptyCells();
		}
	});
});

function initBulkEditGrid(data) {
	const $grid = $('#bulkEditGrid');

	const colModel = [
		{
			title: '이름',
			dataIndx: 'member_name',
			width: 120,
			editable: true,
			align: 'center'
		},
		{
			title: '직분',
			dataIndx: 'position_name',
			width: 150,
			editable: true,
			align: 'center'
		},
		{
			title: '연락처',
			dataIndx: 'member_phone',
			width: 150,
			editable: true,
			align: 'center'
		},
		{
			title: '그룹',
			dataIndx: 'area_name',
			width: 150,
			editable: true,
			align: 'center'
		},
		{
			title: '임시1',
			dataIndx: 'tmp01',
			width: 150,
			editable: true,
			align: 'center'
		},
		{
			title: '임시2',
			dataIndx: 'tmp02',
			width: 150,
			editable: true,
			align: 'center'
		}
	];

	const gridOptions = {
		width: '100%',
		height: '100%',
		colModel: colModel,
		dataModel: {
			data: data
		},
		editable: true,
		strNoRows: '회원 정보가 없습니다',
		editModel: {
			clicksToEdit: 2,
			saveKey: ''
		},
		selectionModel: {
			type: 'cell',
			mode: 'block'
		},
		numberCell: {
			show: true,
			title: '#',
			width: 40,
			resizable: false,
			align: 'center'
		},
		scrollModel: {
			autoFit: true
		},
		showTop: false,
		showTitle: false,
		showToolbar: false,
		wrap: false,
		hwrap: false,
		columnBorders: true,
		rowBorders: true,
		hoverMode: 'row',
		stripeRows: true,
		rowHeight: 30,
		headerHeight: 30
	};

	bulkEditGridInstance = $grid.pqGrid(gridOptions);

	setTimeout(function() {
		$grid.pqGrid('focus');
	}, 100);
}

function addEmptyCells() {
	const count = parseInt($('#addCellCount').val());

	if (!count || count < 1) {
		showToast('추가할 개수를 입력해주세요.', 'warning');
		$('#addCellCount').focus();
		return;
	}

	if (count > 100) {
		showToast('한 번에 최대 100개까지만 추가할 수 있습니다.', 'warning');
		return;
	}

	const $grid = $('#bulkEditGrid');
	const currentData = $grid.pqGrid('option', 'dataModel.data');

	// 빈 행 데이터 생성
	const newRows = [];
	for (let i = 0; i < count; i++) {
		newRows.push({
			member_idx: '',
			member_name: '',
			position_name: '',
			member_phone: '',
			area_name: '',
			tmp01: '',
			tmp02: ''
		});
	}

	// 기존 데이터에 추가
	const updatedData = currentData.concat(newRows);

	// 그리드 데이터 업데이트
	$grid.pqGrid('option', 'dataModel.data', updatedData);
	$grid.pqGrid('refreshDataAndView');

	// 입력 필드 초기화
	$('#addCellCount').val('1');

	// 추가된 첫 번째 행으로 스크롤
	const newRowIndex = currentData.length;
	$grid.pqGrid('scrollRow', {rowIndxPage: newRowIndex});

	showToast(count + '개의 행이 추가되었습니다.', 'success');
}

function saveBulkEdit() {
	if (!bulkEditGridInstance) {
		showToast('그리드가 초기화되지 않았습니다.', 'error');
		return;
	}

	try {
		$('#bulkEditGrid').pqGrid('saveEditCell');
		const data = $('#bulkEditGrid').pqGrid('option', 'dataModel.data');

		if (!data || data.length === 0) {
			showToast('저장할 데이터가 없습니다.', 'warning');
			return;
		}

		// 빈 행 제거 (이름과 전화번호가 모두 비어있는 행)
		const filteredData = data.filter(function(row) {
			// 문자열로 변환 후 체크
			const memberName = String(row.member_name || '').trim();
			const memberPhone = String(row.member_phone || '').trim();

			return memberName !== '' && memberPhone !== '';
		});

		if (filteredData.length === 0) {
			showToast('저장할 수 있는 유효한 데이터가 없습니다.\n이름과 연락처는 필수 항목입니다.', 'warning');
			return;
		}

		// 데이터 정규화 (모든 필드를 문자열로 변환)
		const normalizedData = filteredData.map(function(row) {
			return {
				member_idx: row.member_idx || '',
				member_name: String(row.member_name || '').trim(),
				position_name: String(row.position_name || '').trim(),
				member_phone: String(row.member_phone || '').trim(),
				area_name: String(row.area_name || '').trim(),
				tmp01: String(row.tmp01 || '').trim(),
				tmp02: String(row.tmp02 || '').trim()
			};
		});

		showConfirmModal(
			'전체편집 저장',
			'수정된 내용을 저장하시겠습니까?',
			function() {
				// 부모 창으로 데이터 전달
				if (window.opener) {
					window.opener.postMessage({
						type: 'bulkEditComplete',
						data: normalizedData
					}, '*');
					window.close();
				}
			}
		);
	} catch(e) {
		console.error('저장 중 오류:', e);
		showToast('저장 중 오류가 발생했습니다.', 'error');
	}
}

