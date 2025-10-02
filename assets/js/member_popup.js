'use strict';

let bulkEditGridInstance = null;
let memberAreasMap = {}; // area_idx -> area_name 매핑

$(document).ready(function() {
	// 세션 스토리지에서 데이터 및 컬럼 모델 가져오기
	const dataString = sessionStorage.getItem('bulkEditData');
	const columnsString = sessionStorage.getItem('bulkEditColumns');

	if (!dataString || !columnsString) {
		showToast('데이터를 불러올 수 없습니다.', 'error');
		window.close();
		return;
	}

	const data = JSON.parse(dataString);
	const originalColumns = JSON.parse(columnsString);

	// 소그룹 매핑 생성
	if (window.memberAreas && window.memberAreas.length > 0) {
		window.memberAreas.forEach(function(area) {
			memberAreasMap[area.area_idx] = area.area_name;
		});
	}

	initBulkEditGrid(data, originalColumns);

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

/**
 * 그리드 초기화 - 원본 컬럼 구조 사용
 */
function initBulkEditGrid(data, originalColumns) {
	const $grid = $('#bulkEditGrid');

	// 제외할 컬럼 목록 (회원번호 추가)
	const excludeColumns = ['pq_selected', 'photo', 'regi_date', 'modi_date', 'member_idx'];

	// 데이터 전처리: area_name만 있는 경우 area_idx 찾기
	data.forEach(function(row) {
		if (row.area_name && !row.area_idx) {
			// area_name으로 area_idx 찾기
			const area = window.memberAreas.find(a => a.area_name === row.area_name);
			if (area) {
				row.area_idx = area.area_idx;
			}
		} else if (row.area_idx && !row.area_name) {
			// area_idx로 area_name 찾기
			row.area_name = memberAreasMap[row.area_idx] || '';
		}
	});

	// 편집 가능한 컬럼 모델 생성 (제외 컬럼 필터링)
	const colModel = originalColumns
		.filter(col => {
			return !excludeColumns.includes(col.dataIndx);
		})
		.map(col => {
			// 기본 컬럼 설정
			const columnConfig = {
				title: col.title,
				dataIndx: col.dataIndx,
				width: col.width || 120,
				editable: true,
				align: col.align || 'center'
			};

			// area_name 컬럼을 select로 변경
			if (col.dataIndx === 'area_name' && window.memberAreas && window.memberAreas.length > 0) {
				// select options 생성
				const selectOptions = [{ '': '소그룹 선택' }];
				window.memberAreas.forEach(function(area) {
					selectOptions.push({
						[area.area_idx]: area.area_name
					});
				});

				columnConfig.editor = {
					type: 'select',
					options: selectOptions
				};

				// 렌더링: area_idx를 area_name으로 표시
				columnConfig.render = function(ui) {
					const rowData = ui.rowData;

					// rowData에서 실제 값 가져오기
					if (rowData.area_idx) {
						return memberAreasMap[rowData.area_idx] || rowData.area_name || '';
					}

					return rowData.area_name || '';
				};
			} else {
				columnConfig.render = col.render;
			}

			return columnConfig;
		});

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
			clicksToEdit: 1,
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
			autoFit: false,
			horizontal: true,
			vertical: true
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
		headerHeight: 35,
		// 셀 저장 전에 값 검증
		cellBeforeSave: function(evt, ui) {
			if (ui.dataIndx === 'area_name') {
				// 새 값이 비어있거나 '소그룹 선택'인 경우 이전 값 유지
				if (!ui.newVal || ui.newVal === '' || ui.newVal === '소그룹 선택') {
					return false; // 저장 취소 (이전 값 유지)
				}
			}
		},
		// 셀 편집 완료 시 area_idx와 area_name 동기화
		cellSave: function(evt, ui) {
			if (ui.dataIndx === 'area_name') {
				// 선택된 area_idx로 area_name 설정
				const selectedAreaIdx = ui.newVal;

				if (selectedAreaIdx && memberAreasMap[selectedAreaIdx]) {
					// area_idx와 area_name 모두 업데이트
					ui.rowData.area_idx = selectedAreaIdx;
					ui.rowData.area_name = memberAreasMap[selectedAreaIdx];
				} else if (selectedAreaIdx === '' || selectedAreaIdx === '소그룹 선택') {
					// 빈 값 선택 시 - 실제로는 cellBeforeSave에서 막히므로 여기 도달 안함
					ui.rowData.area_idx = '';
					ui.rowData.area_name = '';
				}

				// 그리드 새로고침
				$grid.pqGrid('refreshCell', { rowIndx: ui.rowIndx, dataIndx: 'area_name' });
			}
		}
	};

	bulkEditGridInstance = $grid.pqGrid(gridOptions);

	setTimeout(function() {
		$grid.pqGrid('focus');
	}, 100);
}

/**
 * 빈 행 추가
 */
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
	const colModel = $grid.pqGrid('option', 'colModel');

	// 빈 행 데이터 생성 (컬럼 모델 기반)
	const newRows = [];
	for (let i = 0; i < count; i++) {
		const newRow = {};
		colModel.forEach(col => {
			newRow[col.dataIndx] = '';
		});
		// area_idx도 빈 값으로 초기화
		newRow.area_idx = '';
		newRows.push(newRow);
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

/**
 * 수정된 데이터 저장
 */
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

		// 빈 행 제거 (이름이 비어있는 행)
		const filteredData = data.filter(function(row) {
			const memberName = String(row.member_name || '').trim();
			return memberName !== '';
		});

		if (filteredData.length === 0) {
			showToast('저장할 수 있는 유효한 데이터가 없습니다. 이름은 필수 항목입니다.', 'warning');
			return;
		}

		// 데이터 정규화 및 area_idx 확인
		const normalizedData = filteredData.map(function(row) {
			const normalizedRow = {};

			Object.keys(row).forEach(key => {
				normalizedRow[key] = String(row[key] || '').trim();
			});

			// area_idx가 없으면 area_name으로 찾기
			if (!normalizedRow.area_idx && normalizedRow.area_name) {
				const area = window.memberAreas.find(a => a.area_name === normalizedRow.area_name);
				if (area) {
					normalizedRow.area_idx = String(area.area_idx);
				}
			}

			// area_idx가 있으면 area_name도 설정
			if (normalizedRow.area_idx && memberAreasMap[normalizedRow.area_idx]) {
				normalizedRow.area_name = memberAreasMap[normalizedRow.area_idx];
			}

			return normalizedRow;
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

// 유틸리티 함수
function showToast(message, type = 'info') {
	let toastContainer = $('#toastContainer');
	if (toastContainer.length === 0) {
		toastContainer = $('<div id="toastContainer" class="toast-container position-fixed bottom-0 end-0 p-3"></div>');
		$('body').append(toastContainer);
	}

	const bgColor = {
		'success': 'bg-success',
		'error': 'bg-danger',
		'warning': 'bg-warning',
		'info': 'bg-info'
	}[type] || 'bg-info';

	const toastId = 'toast-' + Date.now();
	const toastHtml = `
		<div id="${toastId}" class="toast ${bgColor} text-white" role="alert" aria-live="assertive" aria-atomic="true">
			<div class="toast-body d-flex justify-content-between align-items-center">
				<span>${message}</span>
				<button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
			</div>
		</div>
	`;

	toastContainer.append(toastHtml);
	const toastElement = document.getElementById(toastId);
	const toast = new bootstrap.Toast(toastElement, {
		animation: true,
		autohide: true,
		delay: 3000
	});

	toast.show();

	toastElement.addEventListener('hidden.bs.toast', function() {
		toastElement.remove();
	});
}

function showConfirmModal(title, message, confirmCallback, cancelCallback = null) {
	let confirmModal = $('#dynamicConfirmModal');
	if (confirmModal.length === 0) {
		confirmModal = $(`
			<div class="modal fade" id="dynamicConfirmModal" tabindex="-1" aria-labelledby="dynamicConfirmModalLabel" aria-hidden="true">
				<div class="modal-dialog">
					<div class="modal-content">
						<div class="modal-header">
							<h5 class="modal-title" id="dynamicConfirmModalLabel"></h5>
							<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
						</div>
						<div class="modal-body"></div>
						<div class="modal-footer">
							<button type="button" class="btn btn-secondary" data-bs-dismiss="modal" id="modalCancelBtn">취소</button>
							<button type="button" class="btn btn-primary" id="modalConfirmBtn">확인</button>
						</div>
					</div>
				</div>
			</div>
		`);
		$('body').append(confirmModal);
	}

	confirmModal.find('.modal-title').text(title);
	confirmModal.find('.modal-body').html(message.replace(/\n/g, '<br>'));

	confirmModal.find('#modalConfirmBtn').off('click').on('click', function() {
		const modalInstance = bootstrap.Modal.getInstance(confirmModal[0]);
		if (modalInstance) {
			modalInstance.hide();
		} else {
			confirmModal.modal('hide');
		}
		if (confirmCallback) {
			confirmCallback();
		}
	});

	confirmModal.find('#modalCancelBtn').off('click').on('click', function() {
		const modalInstance = bootstrap.Modal.getInstance(confirmModal[0]);
		if (modalInstance) {
			modalInstance.hide();
		} else {
			confirmModal.modal('hide');
		}
		if (cancelCallback) {
			cancelCallback();
		}
	});

	const modalInstance = new bootstrap.Modal(confirmModal[0]);
	modalInstance.show();
}
