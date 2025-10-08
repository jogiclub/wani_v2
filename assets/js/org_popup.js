/**
 * 파일 위치: assets/js/org_popup.js
 * 역할: 조직 대량 편집 팝업 JavaScript
 */

'use strict';

let bulkEditOrgGridInstance = null;
let orgCategoriesMap = {};

$(document).ready(function() {
	const dataString = sessionStorage.getItem('bulkEditOrgData');
	const columnsString = sessionStorage.getItem('bulkEditOrgColumns');

	if (!dataString || !columnsString) {
		showToast('데이터를 불러올 수 없습니다', 'error');
		window.close();
		return;
	}

	const data = JSON.parse(dataString);
	const originalColumns = JSON.parse(columnsString);

	// 카테고리 매핑 생성
	if (window.orgCategories && window.orgCategories.length > 0) {
		window.orgCategories.forEach(function(category) {
			orgCategoriesMap[category.category_idx] = category.category_name;
		});
	}

	initBulkEditOrgGrid(data, originalColumns);

	$('#btnSaveBulkEdit').on('click', function() {
		saveBulkEdit();
	});

	$('#addCell').on('click', function() {
		addEmptyCells();
	});

	$('#addCellCount').on('keypress', function(e) {
		if (e.which === 13) {
			addEmptyCells();
		}
	});
});

/**
 * 그리드 초기화
 */
function initBulkEditOrgGrid(data, originalColumns) {
	const $grid = $('#bulkEditOrgGrid');

	// 제외할 컬럼 목록
	const excludeColumns = ['checkbox', 'org_icon', 'member_count', 'regi_date', 'org_id'];

	// 조직 타입 옵션
	const orgTypeOptions = [
		{ '': '선택' },
		{ 'church': '교회' },
		{ 'school': '학교' },
		{ 'company': '회사' },
		{ 'organization': '단체' }
	];

	// 카테고리 옵션 생성
	const categoryOptions = [{ '': '카테고리 선택' }];
	if (window.orgCategories && window.orgCategories.length > 0) {
		window.orgCategories.forEach(function(category) {
			categoryOptions.push({
				[category.category_idx]: category.category_name
			});
		});
	}

	// 편집 가능한 컬럼 모델 생성
	const colModel = originalColumns
		.filter(col => !excludeColumns.includes(col.dataIndx))
		.map(col => {
			const columnConfig = {
				title: col.title,
				dataIndx: col.dataIndx,
				width: col.width || 120,
				editable: true,
				align: col.align || 'center'
			};

			// 카테고리 컬럼을 select로 변경
			if (col.dataIndx === 'category_name') {
				columnConfig.editor = {
					type: 'select',
					options: categoryOptions,
					style: 'text-align: left;'
				};
				columnConfig.align = 'left';

				// 렌더링: category_idx를 category_name으로 표시
				columnConfig.render = function(ui) {
					const rowData = ui.rowData;
					if (rowData.category_idx) {
						return orgCategoriesMap[rowData.category_idx] || rowData.category_name || '';
					}
					return rowData.category_name || '';
				};
			}
			// 조직 타입 컬럼을 select로 변경
			else if (col.dataIndx === 'org_type') {
				columnConfig.editor = {
					type: 'select',
					options: orgTypeOptions
				};
				columnConfig.render = function(ui) {
					const typeMap = {
						'church': '교회',
						'school': '학교',
						'company': '회사',
						'organization': '단체'
					};
					return typeMap[ui.cellData] || ui.cellData || '';
				};
			}
			// 태그는 텍스트로 편집
			else if (col.dataIndx === 'org_tag') {
				columnConfig.render = function(ui) {
					if (ui.cellData) {
						let tags = [];
						try {
							const parsed = JSON.parse(ui.cellData);
							if (Array.isArray(parsed)) {
								tags = parsed;
							} else {
								tags = ui.cellData.split(',').map(tag => tag.trim()).filter(tag => tag);
							}
						} catch(e) {
							tags = ui.cellData.split(',').map(tag => tag.trim()).filter(tag => tag);
						}
						return tags.join(', ');
					}
					return '';
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
		strNoRows: '조직 정보가 없습니다',
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
		cellBeforeSave: function(evt, ui) {
			if (ui.dataIndx === 'category_name') {
				if (!ui.newVal || ui.newVal === '' || ui.newVal === '카테고리 선택') {
					return false;
				}
			}
		},
		cellSave: function(evt, ui) {
			if (ui.dataIndx === 'category_name') {
				const selectedCategoryIdx = ui.newVal;
				if (selectedCategoryIdx && orgCategoriesMap[selectedCategoryIdx]) {
					ui.rowData.category_idx = selectedCategoryIdx;
					ui.rowData.category_name = orgCategoriesMap[selectedCategoryIdx];
				} else if (selectedCategoryIdx === '' || selectedCategoryIdx === '카테고리 선택') {
					ui.rowData.category_idx = '';
					ui.rowData.category_name = '';
				}
				$grid.pqGrid('refreshCell', { rowIndx: ui.rowIndx, dataIndx: 'category_name' });
			}
		}
	};

	bulkEditOrgGridInstance = $grid.pqGrid(gridOptions);

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
		showToast('추가할 개수를 입력해주세요', 'warning');
		$('#addCellCount').focus();
		return;
	}

	if (count > 100) {
		showToast('한 번에 최대 100개까지만 추가할 수 있습니다', 'warning');
		return;
	}

	const $grid = $('#bulkEditOrgGrid');
	const currentData = $grid.pqGrid('option', 'dataModel.data');
	const colModel = $grid.pqGrid('option', 'colModel');

	const newRows = [];
	for (let i = 0; i < count; i++) {
		const newRow = {};
		colModel.forEach(col => {
			newRow[col.dataIndx] = '';
		});
		newRow.category_idx = '';
		newRow.org_type = 'church';
		newRows.push(newRow);
	}

	const updatedData = currentData.concat(newRows);

	$grid.pqGrid('option', 'dataModel.data', updatedData);
	$grid.pqGrid('refreshDataAndView');

	$('#addCellCount').val('1');

	const newRowIndex = currentData.length;
	$grid.pqGrid('scrollRow', {rowIndxPage: newRowIndex});

	showToast(count + '개의 행이 추가되었습니다', 'success');
}

/**
 * 수정된 데이터 저장
 */
function saveBulkEdit() {
	if (!bulkEditOrgGridInstance) {
		showToast('그리드가 초기화되지 않았습니다', 'error');
		return;
	}

	try {
		$('#bulkEditOrgGrid').pqGrid('saveEditCell');
		const data = $('#bulkEditOrgGrid').pqGrid('option', 'dataModel.data');

		if (!data || data.length === 0) {
			showToast('저장할 데이터가 없습니다', 'warning');
			return;
		}

		// 빈 행 제거 (조직명이 비어있는 행)
		const filteredData = data.filter(function(row) {
			const orgName = String(row.org_name || '').trim();
			return orgName !== '';
		});

		if (filteredData.length === 0) {
			showToast('저장할 수 있는 유효한 데이터가 없습니다. 조직명은 필수 항목입니다', 'warning');
			return;
		}

		// 데이터 정규화
		const normalizedData = filteredData.map(function(row) {
			const normalizedRow = {};

			Object.keys(row).forEach(key => {
				normalizedRow[key] = String(row[key] || '').trim();
			});

			// category_idx 확인
			if (!normalizedRow.category_idx && normalizedRow.category_name) {
				const category = window.orgCategories.find(c => c.category_name === normalizedRow.category_name);
				if (category) {
					normalizedRow.category_idx = String(category.category_idx);
				}
			}

			if (normalizedRow.category_idx && orgCategoriesMap[normalizedRow.category_idx]) {
				normalizedRow.category_name = orgCategoriesMap[normalizedRow.category_idx];
			}

			// 태그 처리
			if (normalizedRow.org_tag) {
				const tags = normalizedRow.org_tag.split(',').map(t => t.trim()).filter(t => t);
				normalizedRow.org_tag = JSON.stringify(tags);
			}

			return normalizedRow;
		});

		showConfirmModal(
			'대량편집 저장',
			'수정된 내용을 저장하시겠습니까?',
			function() {
				if (window.opener) {
					window.opener.postMessage({
						type: 'bulkEditOrgComplete',
						data: normalizedData
					}, '*');
					window.close();
				}
			}
		);
	} catch(e) {
		console.error('저장 중 오류:', e);
		showToast('저장 중 오류가 발생했습니다', 'error');
	}
}

