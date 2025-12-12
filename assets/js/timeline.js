/**
 * 역할: 타임라인 관리 화면 동작 제어
 */

$(document).ready(function() {
	const baseUrl = window.timelinePageData.baseUrl;
	const currentOrgId = window.timelinePageData.currentOrgId;
	let timelineGrid;
	let timelineTypes = [];

	// 전송 히스토리 년월 초기화
	initHistoryYearMonth();

	// PQGrid 초기화
	initPQGrid();

	// 타임라인 항목 로드
	loadTimelineTypes();

	// 이벤트 바인딩
	bindEvents();



	/**
	 * 역할: PQGrid 초기화 - cellClick 이벤트 추가
	 */
	function initPQGrid() {
		const colModel = [
			{
				title: '<input type="checkbox" id="selectAllCheckbox" />',
				dataIndx: "pq_selected",
				width: 50,
				align: "center",
				resizable: false,
				sortable: false,
				editable: false,
				menuIcon: false,
				render: function (ui) {
					const checkboxId = 'timeline-checkbox-' + ui.rowData.idx;
					return '<input type="checkbox" class="timeline-checkbox" id="' + checkboxId + '" data-idx="' + ui.rowData.idx + '" />';
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
				width: 120,
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
				// 선택된 타임라인 항목들 가져오기
				const selectedTypes = [];
				if (!$('#searchType_all').is(':checked')) {
					$('.timeline-type-checkbox:checked').each(function() {
						selectedTypes.push($(this).val());
					});
				}

				return {
					org_id: currentOrgId,
					timeline_types: selectedTypes,
					search_text: $('#searchText').val(),
					year: $('#historyYear').val(),
					month: $('#historyMonth').val()
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
			height: "100%",
			colModel: colModel,
			dataModel: dataModel,
			editable: false,
			pageModel: { type: null },
			selectionModel: { type: 'row', mode: 'block' },
			numberCell: { show: false },
			showTitle: false,
			showToolbar: false,
			showBottom: false,
			showTop: false,
			showHeader: true,
			scrollModel: { autoFit: false },
			strNoRows: '조회된 타임라인이 없습니다.',
			loading: { show: false },
			cellClick: function(event, ui) {
				if (ui.dataIndx === 'pq_selected') {
					handleCheckboxColumnClick(event, ui.rowData.idx);
				} else {
					// 체크박스 컬럼이 아닌 경우 수정 폼 열기
					editTimeline(ui.rowData);
				}
			},
			beforeRemoteRequest: function(event, ui) {
				// 데이터 로드 시작 시 스피너 표시
				showGridLoading();
			},
			load: function(event, ui) {
				// 데이터 로드 완료 시 스피너 숨김
				hideGridLoading();
			}
		};

		timelineGrid = pq.grid('#timelineGrid', gridOptions);
		loadTimelineStatistics();
	}

	/**
	 * 그리드 로딩 스피너 표시
	 */
	function showGridLoading() {
		$('#timelineGridLoading').show();
	}

	/**
	 * 그리드 로딩 스피너 숨김
	 */
	function hideGridLoading() {
		$('#timelineGridLoading').hide();

	}




	/**
	 * 역할: 히스토리 년월 선택 초기화
	 */
	function initHistoryYearMonth() {
		const now = new Date();
		const currentYear = now.getFullYear();
		const currentMonth = now.getMonth() + 1;

		// 년도 옵션 생성 (현재년도 기준 ±5년)
		const yearSelect = $('#historyYear');
		yearSelect.empty();

		for (let year = currentYear - 5; year <= currentYear + 1; year++) {
			const option = `<option value="${year}">${year}년</option>`;
			yearSelect.append(option);
		}

		// 현재 년월로 설정
		$('#historyYear').val(currentYear);
		$('#historyMonth').val(currentMonth);
	}

	/**
	 * 역할: 체크박스 컬럼 클릭 핸들러
	 */
	function handleCheckboxColumnClick(event, idx) {
		// 직접 체크박스를 클릭한 경우가 아니라면 체크박스 토글
		const isDirectCheckboxClick = $(event.target).hasClass('timeline-checkbox') ||
			$(event.originalEvent?.target).hasClass('timeline-checkbox');

		if (!isDirectCheckboxClick) {
			const checkbox = $('.timeline-checkbox[data-idx="' + idx + '"]').first();
			if (checkbox.length > 0) {
				const isCurrentlyChecked = checkbox.is(':checked');
				checkbox.prop('checked', !isCurrentlyChecked);
			}
		}

		// 체크박스 상태 업데이트
		updateSelectAllCheckbox();
		updateSelectedTimelineButtons();
	}

	/**
	 
	 * 역할: 전체 선택 체크박스 상태 업데이트
	 */
	function updateSelectAllCheckbox() {
		const totalCheckboxes = $('.timeline-checkbox').length;
		const checkedCheckboxes = $('.timeline-checkbox:checked').length;

		if (totalCheckboxes === 0) {
			$('#selectAllCheckbox').prop('checked', false);
			$('#selectAllCheckbox').prop('indeterminate', false);
		} else if (checkedCheckboxes === 0) {
			$('#selectAllCheckbox').prop('checked', false);
			$('#selectAllCheckbox').prop('indeterminate', false);
		} else if (checkedCheckboxes === totalCheckboxes) {
			$('#selectAllCheckbox').prop('checked', true);
			$('#selectAllCheckbox').prop('indeterminate', false);
		} else {
			$('#selectAllCheckbox').prop('checked', false);
			$('#selectAllCheckbox').prop('indeterminate', true);
		}
	}

	/**
	 
	 * 역할: 선택된 타임라인에 따라 버튼 활성화/비활성화
	 */
	function updateSelectedTimelineButtons() {
		const checkedCount = $('.timeline-checkbox:checked').length;

		if (checkedCount === 0) {
			$('#btnDelete').prop('disabled', true);
		} else {
			$('#btnDelete').prop('disabled', false);
		}
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
		// 검색 드롭다운 메뉴 업데이트
		const $searchMenu = $('#searchTypeMenu');

		// 기존 항목 제거 (전체와 구분선은 유지)
		$searchMenu.find('li:gt(1)').remove();

		// 타임라인 항목들 추가
		timelineTypes.forEach(function(type) {
			const itemId = 'searchType_' + type.replace(/\s+/g, '_');
			const $li = $('<li></li>');
			const $div = $('<div class="dropdown-item"></div>');
			const $checkbox = $('<input type="checkbox" class="form-check-input me-2 timeline-type-checkbox">');
			$checkbox.attr('id', itemId);
			$checkbox.attr('value', type);
			const $label = $('<label class="form-check-label"></label>');
			$label.attr('for', itemId);
			$label.text(type);

			$div.append($checkbox);
			$div.append($label);
			$li.append($div);
			$searchMenu.append($li);
		});

		// offcanvas의 항목 셀렉트 업데이트
		$('#timeline_type').empty();
		$('#timeline_type').append('<option value="">항목을 선택하세요</option>');
		timelineTypes.forEach(function(type) {
			$('#timeline_type').append(`<option value="${type}">${type}</option>`);
		});

		// 드롭다운 이벤트 바인딩
		bindDropdownEvents();
	}

	/**
	 * 드롭다운 이벤트 바인딩
	 */
	function bindDropdownEvents() {
		// 전체 체크박스 클릭 이벤트
		$('#searchType_all').on('change', function() {
			const isChecked = $(this).is(':checked');
			$('.timeline-type-checkbox').prop('checked', false);
			if (isChecked) {
				updateSearchTypeText();
			}
		});

		// 개별 항목 체크박스 클릭 이벤트
		$(document).off('change', '.timeline-type-checkbox').on('change', '.timeline-type-checkbox', function() {
			// 개별 항목이 체크되면 전체 체크 해제
			if ($(this).is(':checked')) {
				$('#searchType_all').prop('checked', false);
			}

			// 모든 개별 항목이 체크 해제되면 전체 체크
			if ($('.timeline-type-checkbox:checked').length === 0) {
				$('#searchType_all').prop('checked', true);
			}

			updateSearchTypeText();
		});

		// 드롭다운 아이템 클릭 시 닫히지 않도록 방지
		$('#searchTypeMenu .dropdown-item').on('click', function(e) {
			e.stopPropagation();
		});
	}

	/**
	 * 선택된 타임라인 항목 텍스트 업데이트
	 */
	function updateSearchTypeText() {
		const $allCheckbox = $('#searchType_all');
		const $typeCheckboxes = $('.timeline-type-checkbox:checked');

		if ($allCheckbox.is(':checked') || $typeCheckboxes.length === 0) {
			$('#searchTypeText').text('전체');
		} else if ($typeCheckboxes.length === 1) {
			$('#searchTypeText').text($typeCheckboxes.first().val());
		} else {
			$('#searchTypeText').text($typeCheckboxes.length + '개 항목 선택');
		}
	}


	/**
	 * Select2 초기화
	 */
	function initMemberSelect2() {
		$('#member_select').select2({
			width: '100%',
			placeholder: '회원을 선택하세요',
			allowClear: true,
			multiple: true,
			closeOnSelect: false,
			language: {
				noResults: function() {
					return '검색 결과가 없습니다.';
				},
				searching: function() {
					return '검색 중...';
				},
				inputTooShort: function() {
					return '검색어를 입력하세요.';
				}
			},
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
			minimumInputLength: 0,
			templateResult: function(data) {
				if (!data.id) {
					return data.text;
				}
				return $('<span>' + data.text + '</span>');
			},
			templateSelection: function(data) {
				return data.text;
			}
		});

		// Select2 드래그앤드롭 기능 적용
		$('#member_select').select2Sortable();
	}



	/**
	 * 역할: 이벤트 바인딩 - 체크박스 이벤트 수정
	 */
	function bindEvents() {
		$('#btnSearch').on('click', searchTimelines);
		$('#searchText').on('keypress', function(e) {
			if (e.which === 13) {
				searchTimelines();
			}
		});

		$('#btnAdd').on('click', showAddForm);
		$('#btnDelete').on('click', showDeleteConfirm);

		$('#btnSaveTimeline').on('click', saveTimeline);
		$('#confirmDeleteBtn').on('click', deleteTimelines);

		$('#timelineOffcanvas').on('hidden.bs.offcanvas', resetForm);

		// 전체 선택 체크박스 이벤트
		$(document).on('change', '#selectAllCheckbox', function() {
			const isChecked = $(this).prop('checked');
			$('.timeline-checkbox').prop('checked', isChecked);
			updateSelectedTimelineButtons();
		});

		// 개별 체크박스 이벤트
		$(document).on('change', '.timeline-checkbox', function() {
			updateSelectAllCheckbox();
			updateSelectedTimelineButtons();
		});

		// 연/월 변경 이벤트
		$('#historyYear, #historyMonth').on('change', function() {
			searchTimelines();
			loadTimelineStatistics(); // 통계도 함께 갱신
		});

		// 이전월 버튼
		$('#btnPrevMonth').on('click', function() {
			let year = parseInt($('#historyYear').val());
			let month = parseInt($('#historyMonth').val());

			month--;
			if (month < 1) {
				month = 12;
				year--;
			}

			$('#historyYear').val(year);
			$('#historyMonth').val(month);
			searchTimelines();
		});

		// 다음월 버튼
		$('#btnNextMonth').on('click', function() {
			let year = parseInt($('#historyYear').val());
			let month = parseInt($('#historyMonth').val());

			month++;
			if (month > 12) {
				month = 1;
				year++;
			}

			$('#historyYear').val(year);
			$('#historyMonth').val(month);
			searchTimelines();
		});


		// 수료증 인쇄 버튼
		$('#btnCertificate').on('click', handleCertificateClick);
		$('#btnCertificatePreview').on('click', handleCertificatePreview);
		$('#btnCertificatePrint').on('click', handleCertificatePrint);

	}

	/**
	 * 수료증 인쇄 버튼 클릭 처리
	 */
	function handleCertificateClick() {
		const selectedCheckboxes = $('.timeline-checkbox:checked');

		if (selectedCheckboxes.length === 0) {
			showToast('수료증을 인쇄할 항목을 선택해주세요.', 'warning');
			return;
		}

		// 선택된 모든 idx 가져오기
		const selectedIdxs = [];
		selectedCheckboxes.each(function() {
			selectedIdxs.push($(this).data('idx'));
		});

		console.log('선택된 idxs:', selectedIdxs);
		console.log('선택된 idxs 타입:', selectedIdxs.map(id => typeof id));

		// PQGrid에서 데이터 가져오기
		const allData = timelineGrid.getData();
		console.log('전체 데이터 개수:', allData.length);

		if (allData.length > 0) {
			console.log('첫 번째 행 idx:', allData[0].idx, '타입:', typeof allData[0].idx);
		}

		// 타입 불일치 해결: == 비교 사용
		const selectedRows = allData.filter(row => {
			const isIncluded = selectedIdxs.some(selectedIdx => selectedIdx == row.idx);
			return isIncluded;
		});

		console.log('찾은 데이터 개수:', selectedRows.length);

		if (selectedRows.length > 0) {
			openCertificateOffcanvas(selectedRows);
		} else {
			showToast('선택한 타임라인 정보를 찾을 수 없습니다.', 'error');
		}
	}

	/**
	 * 수료증 Offcanvas 열기
	 */
	function openCertificateOffcanvas(selectedRows) {
		// 첫 번째 항목을 예시로 표시
		const firstRow = selectedRows[0];

		// 선택된 항목 개수 표시
		const selectedCount = selectedRows.length;
		if (selectedCount > 1) {
			$('#certificateOffcanvasLabel').text(`수료증 인쇄 (${selectedCount}개 선택)`);
		} else {
			$('#certificateOffcanvasLabel').text('수료증 인쇄');
		}

		// 제목 초기화
		$('#cert_title_main').val('수료증');
		$('#cert_title_sub').val('Certificate of Completion');

		// 라벨 초기화
		$('#cert_label_name').val('성명');
		$('#cert_label_subject').val('과목');
		$('#cert_label_period').val('기간');

		// 성명
		$('#cert_member_name').text(firstRow.member_name || '');

		// 과목
		$('#cert_timeline_type').text(firstRow.timeline_type || '');

		// 기간 (시작일과 종료일 동일하게 설정)
		const timelineDate = firstRow.timeline_date || '';
		$('#cert_period_start').val(timelineDate);
		$('#cert_period_end').val(timelineDate);

		// 수료일
		$('#cert_date').val(timelineDate);

		// 조직명과 조직장
		const orgName = window.timelinePageData.currentOrgName || '';
		const orgRep = window.timelinePageData.currentOrgRep || '';
		$('#cert_org_name').text(orgName);
		$('#cert_org_rep').text(orgRep);

		// 수료 내용
		const defaultContent = `위 사람은 ${orgName}에서 진행한 ${firstRow.timeline_type || ''} 과정을\n성실히 수료하였기에 이 증서를 드립니다.`;
		$('#cert_content').val(defaultContent);

		// 선택된 데이터를 전역 변수에 저장 (미리보기/인쇄 시 사용)
		window.selectedCertificateData = selectedRows;

		// Offcanvas 열기
		const certificateOffcanvas = new bootstrap.Offcanvas(document.getElementById('certificateOffcanvas'));
		certificateOffcanvas.show();
	}

	/**
	 * 수료증 미리보기
	 */
	function handleCertificatePreview() {
		const printWindow = window.open('', '_blank', 'width=800,height=600');
		const certificateHtml = generateCertificateHtml();

		printWindow.document.write(certificateHtml);
		printWindow.document.close();
	}

	/**
	 * 수료증 인쇄
	 */
	function handleCertificatePrint() {
		const printWindow = window.open('', '_blank', 'width=800,height=600');
		const certificateHtml = generateCertificateHtml();

		printWindow.document.write(certificateHtml);
		printWindow.document.close();

		setTimeout(() => {
			printWindow.print();
		}, 250);
	}

	/**
	 * 수료증 HTML 생성 (선택된 모든 항목)
	 */
	function generateCertificateHtml() {
		const selectedData = window.selectedCertificateData || [];
		const titleMain = $('#cert_title_main').val() || '수료증';
		const titleSub = $('#cert_title_sub').val() || 'Certificate of Completion';
		const labelName = $('#cert_label_name').val() || '성명';
		const labelSubject = $('#cert_label_subject').val() || '과목';
		const labelPeriod = $('#cert_label_period').val() || '기간';
		const periodStart = $('#cert_period_start').val();
		const periodEnd = $('#cert_period_end').val();
		const period = `${periodStart} ~ ${periodEnd}`;
		const contentTemplate = $('#cert_content').val();
		const certDate = $('#cert_date').val();
		const orgName = $('#cert_org_name').text();
		const orgRep = $('#cert_org_rep').text();
		const orgSeal = window.timelinePageData.currentOrgSeal || '';
		const baseUrl = window.timelinePageData.baseUrl || '';

		let certificatesHtml = '';

		selectedData.forEach((row, index) => {
			const memberName = row.member_name || '';
			const timelineType = row.timeline_type || '';

			// 수료 내용에서 성명과 과목명을 동적으로 치환
			const content = contentTemplate
				.replace(/\{성명\}/g, memberName)
				.replace(/\{항목명\}/g, timelineType)
				.replace(/\{과목\}/g, timelineType)
				.replace(/\{조직명\}/g, orgName)
				.replace(/\n/g, '<br>');

			// 페이지 구분을 위한 클래스 추가 (첫 번째 제외하고 페이지 브레이크)
			const pageBreakClass = index > 0 ? ' page-break' : '';

			// 직인 이미지 HTML (있는 경우에만)
			let sealHtml = '';
			if (orgSeal) {
				sealHtml = `<img src="${baseUrl}${orgSeal}" alt="조직 직인" class="org-seal">`;
			}

			certificatesHtml += `
        <div class="certificate${pageBreakClass}">
            <div class="certificate-header">
                <div class="certificate-title">${titleMain}</div>
                <div class="certificate-subtitle">${titleSub}</div>
            </div>
            
            <div class="certificate-body">
                <div class="certificate-row">
                    <div class="certificate-label">${labelName}</div>
                    <div class="certificate-value">${memberName}</div>
                </div>
                <div class="certificate-row">
                    <div class="certificate-label">${labelSubject}</div>
                    <div class="certificate-value">${timelineType}</div>
                </div>
                <div class="certificate-row">
                    <div class="certificate-label">${labelPeriod}</div>
                    <div class="certificate-value">${period}</div>
                </div>
            </div>
            
            <div class="certificate-content">
                ${content}
            </div>
            
            <div class="certificate-footer">
                <div class="certificate-date">${certDate}</div>
                <div class="certificate-signature">
                    <span class="certificate-org">${orgName}</span>
                    <span class="certificate-rep">${orgRep}</span>
                    ${sealHtml}
                </div>
            </div>
        </div>
        `;
		});

		return `
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>수료증</title>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+KR:wght@100..900&family=Noto+Serif+KR:wght@200..900&display=swap" rel="stylesheet">
    <style>
        @media print {
            @page {
                size: A4;
                margin: 20mm;
            }
            
            .page-break {
                page-break-before: always;
            }
        }
        
        body {
            font-family: "Noto Sans KR", sans-serif;
            margin: 0;
            padding: 0;
        }
        
        .certificate {
            width: 100%;
            max-width: 700px;
            padding: 0;                        
            margin: 0 auto 40px auto;
            background: white;
            min-height: calc(100vh - 80px);
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .certificate-header {
            text-align: center;
            margin-bottom: 60px;
        }
        
        .certificate-title {
			font-size: 48px;
			font-weight: bold;
			margin-bottom: 20px;
			letter-spacing: 40px;
			font-family: "Noto Serif KR", serif;
			margin-left: 40px;
        }
        
        .certificate-subtitle {
            font-size: 20px;
            color: #666;            
        }
        
        .certificate-body {
            margin-bottom: 50px;
        }
        
        .certificate-row {
            display: flex;
            margin-bottom: 25px;
            font-size: 20px;
        }
        
        .certificate-label {
            font-weight: bold;
            width: 160px;
            text-align: left;
            margin-right: 20px;
            letter-spacing: 20px;
        }
        
        .certificate-value {
            flex: 1;
            border-bottom: 1px solid #999;
            padding-bottom: 5px;
        }
        
        .certificate-content {
            margin: 70px 0;
            padding: 0;            
            line-height: 2;
            font-size: 20px;
        }
        
        .certificate-footer {
            text-align: right;
            margin-top: 60px;
        }
        
        .certificate-date {
            font-size: 18px;
            margin-bottom: 15px;
        }
        
        .certificate-signature {
            position: relative;
            display: inline-block;
            text-align: right;
            
        }
        
        .certificate-org {
        	position: relative;
            font-size: 24px;
            font-weight: 500;
            margin-right: 30px;
            z-index: 10;
        }
        
        .certificate-rep {
			position: relative;
			font-size: 32px;
			font-weight: 500;
			z-index: 10;
			margin-right: 10px;
			font-family: 'Noto Serif KR';
			letter-spacing: 14px;
        }
        
        .org-seal {
            position: absolute;
            right: -20px;
            bottom: -40px;
            width: 100px;
            height: 100px;
            opacity: 0.9;
            z-index: 9;
        }
        
        @media screen {
            .page-break {
                margin-top: 60px;
            }
        }
    </style>
</head>
<body>
    ${certificatesHtml}
</body>
</html>
    `;
	}

	/**
	 * 역할: 타임라인 검색 - 검색 후 버튼 상태 초기화
	 */
	function searchTimelines() {
		showGridLoading();
		timelineGrid.refreshDataAndView();

		// 검색 후 버튼 비활성화
		setTimeout(function() {
			updateSelectAllCheckbox();
			updateSelectedTimelineButtons();
			hideGridLoading();
		}, 100);
	}


	/**
	 * 추가 폼 표시
	 */
	function showAddForm() {
		resetForm();
		$('#timeline_mode').val('add');
		$('#timelineOffcanvasLabel').text('타임라인 일괄추가');
		$('#memberSelectDiv').show();
		$('#memberNameDiv').hide();

		// 오늘 날짜를 기본값으로 설정
		const today = new Date();
		const year = today.getFullYear();
		const month = String(today.getMonth() + 1).padStart(2, '0');
		const day = String(today.getDate()).padStart(2, '0');
		$('#timeline_date').val(`${year}-${month}-${day}`);

		initMemberSelect2();

		const offcanvas = new bootstrap.Offcanvas(document.getElementById('timelineOffcanvas'));
		offcanvas.show();
	}

	/**
	 * 수정 폼 표시
	 */
	function showEditForm() {
		const selectedCheckboxes = $('.timeline-checkbox:checked');

		if (selectedCheckboxes.length === 0) {
			showToast('수정할 항목을 선택해주세요.', 'warning');
			return;
		}

		if (selectedCheckboxes.length > 1) {
			showToast('한 개의 항목만 선택해주세요.', 'warning');
			return;
		}

		const idx = selectedCheckboxes.first().data('idx');
		const allData = timelineGrid.getData();
		const rowData = allData.find(row => row.idx === idx);

		if (rowData) {
			editTimeline(rowData);
		}
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

			console.log('선택된 타임라인 타입:', timeline_type);
			console.log('선택된 회원 수:', member_idxs.length);

			// 입대 항목인 경우 진급/전역 항목 존재 여부 체크
			if (timeline_type === '입대') {
				checkPromotionTypesExist(member_idxs, timeline_date);
			} else {
				addTimeline();
			}
		} else {
			updateTimeline();
		}
	}

	/**
	 * 타임라인 설정에 진급/전역 항목이 존재하는지 체크
	 */
	function checkPromotionTypesExist(member_idxs, enlistment_date) {
		const memberCount = member_idxs.length;

		// timelineTypes 배열에서 진급/전역 항목이 있는지 확인
		const promotionTypes = ['진급(일병)', '진급(상병)', '진급(병장)', '전역'];
		const hasPromotionTypes = promotionTypes.some(type => timelineTypes.includes(type));

		console.log('타임라인 타입 목록:', timelineTypes);
		console.log('진급/전역 항목 존재 여부:', hasPromotionTypes);

		if (hasPromotionTypes) {
			// 진급/전역 항목이 존재하면 모달 표시
			showPromotionConfirmModal(member_idxs, enlistment_date, memberCount);
		} else {
			// 진급/전역 항목이 없으면 바로 저장
			console.log('진급/전역 항목이 없어서 입대만 저장');
			addTimeline();
		}
	}

	/**
	 * 진급/전역 항목 존재 여부 체크 및 모달 표시
	 */
	function checkAndShowPromotionModal(member_idxs, enlistment_date) {
		// 선택된 회원 수 확인
		const memberCount = member_idxs.length;

		$.ajax({
			url: baseUrl + 'timeline/check_promotion_exists',
			type: 'POST',
			dataType: 'json',
			data: {
				member_idxs: member_idxs
			},
			success: function(response) {
				if (response.success) {
					if (response.has_promotion) {
						// 진급/전역 항목이 하나라도 존재하면 모달 표시
						showPromotionConfirmModal(member_idxs, enlistment_date, memberCount);
					} else {
						// 진급/전역 항목이 없으면 바로 저장
						addTimeline();
					}
				} else {
					showToast(response.message || '항목 확인에 실패했습니다.', 'error');
				}
			},
			error: function() {
				showToast('항목 확인에 실패했습니다.', 'error');
			}
		});
	}

	/**
	 * 진급/전역 자동 생성 확인 모달 표시
	 */
	function showPromotionConfirmModal(member_idxs, enlistment_date, memberCount) {
		console.log('모달 표시 - 회원 수:', memberCount);

		// 모달 메시지 업데이트
		const modalMessage = memberCount > 1
			? `선택된 ${memberCount}명의 회원에게 진급(일병), 진급(상병), 진급(병장), 전역 항목도 함께 생성하시겠습니까?`
			: '진급(일병), 진급(상병), 진급(병장), 전역 항목도 함께 생성하시겠습니까?';

		$('#promotionModal .modal-body p').first().text(modalMessage);

		const modal = new bootstrap.Modal(document.getElementById('promotionModal'));

		// 확인 버튼 이벤트 (기존 이벤트 제거 후 새로 바인딩)
		$('#confirmPromotionBtn').off('click').on('click', function() {
			console.log('확인 버튼 클릭 - 진급/전역 포함 저장');
			modal.hide();
			// 모달 확인으로 닫혔음을 표시
			$('#promotionModal').data('confirmed', true);
			addTimelineWithPromotion();
		});

		// 모달이 완전히 닫힌 후 처리
		$('#promotionModal').off('hidden.bs.modal').on('hidden.bs.modal', function(e) {
			const isConfirmed = $(this).data('confirmed');
			$(this).removeData('confirmed');

			// 확인 버튼으로 닫힌 경우가 아니면 입대 항목만 저장
			if (!isConfirmed) {
				console.log('취소 버튼 클릭 - 입대만 저장');
				addTimeline();
			}
		});

		modal.show();
	}

	/**
	 * 입대 및 진급/전역 항목 일괄 저장
	 */
	function addTimelineWithPromotion() {
		const member_idxs = $('#member_select').val();

		if (!member_idxs || member_idxs.length === 0) {
			showToast('회원을 선택해주세요.', 'warning');
			return;
		}

		const formData = {
			org_id: currentOrgId,
			member_idxs: member_idxs,
			timeline_type: $('#timeline_type').val(),
			timeline_date: $('#timeline_date').val(),
			timeline_content: $('#timeline_content').val(),
			include_promotion: true
		};

		console.log('진급 포함 저장 요청:', formData);

		$.ajax({
			url: baseUrl + 'timeline/add_timeline_with_promotion',
			type: 'POST',
			dataType: 'json',
			data: formData,
			success: function(response) {
				console.log('진급 포함 저장 응답:', response);
				if (response.success) {
					showToast(response.message, 'success');
					bootstrap.Offcanvas.getInstance(document.getElementById('timelineOffcanvas')).hide();
					searchTimelines();
					loadTimelineStatistics();
				} else {
					showToast(response.message || '타임라인 일괄추가에 실패했습니다.', 'error');
				}
			},
			error: function(xhr, status, error) {
				console.error('진급 포함 저장 에러:', error, xhr.responseText);
				showToast('타임라인 일괄추가에 실패했습니다.', 'error');
			}
		});
	}

	/**
	 * 날짜에 개월 수 더하기
	 */
	function addMonths(dateString, months) {
		const date = new Date(dateString);
		date.setMonth(date.getMonth() + months);

		const year = date.getFullYear();
		const month = String(date.getMonth() + 1).padStart(2, '0');
		const day = String(date.getDate()).padStart(2, '0');

		return `${year}-${month}-${day}`;
	}


	/**
	 * 타임라인 일괄추가
	 */
	function addTimeline() {
		const formData = {
			org_id: currentOrgId,
			member_idxs: $('#member_select').val(),
			timeline_type: $('#timeline_type').val(),
			timeline_date: $('#timeline_date').val(),
			timeline_content: $('#timeline_content').val()
		};

		console.log('타임라인 저장 요청:', formData);

		$.ajax({
			url: baseUrl + 'timeline/add_timeline',
			type: 'POST',
			dataType: 'json',
			data: formData,
			success: function(response) {
				console.log('저장 응답:', response);
				if (response.success) {
					showToast(response.message, 'success');
					bootstrap.Offcanvas.getInstance(document.getElementById('timelineOffcanvas')).hide();
					searchTimelines();
					loadTimelineStatistics(); // 통계 갱신 추가
				} else {
					showToast(response.message || '타임라인 일괄추가에 실패했습니다.', 'error');
				}
			},
			error: function(xhr, status, error) {
				console.error('저장 에러:', error);
				showToast('타임라인 일괄추가에 실패했습니다.', 'error');
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
		const selectedIdxs = $('.timeline-checkbox:checked');

		if (selectedIdxs.length === 0) {
			showToast('삭제할 항목을 선택해주세요.', 'warning');
			return;
		}

		const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
		modal.show();
	}

	/**
	 
	 * 역할: 타임라인 삭제 - 체크박스 방식에 맞게 수정
	 */
	function deleteTimelines() {
		const selectedIdxs = [];
		$('.timeline-checkbox:checked').each(function() {
			selectedIdxs.push($(this).data('idx'));
		});

		$.ajax({
			url: baseUrl + 'timeline/delete_timelines',
			type: 'POST',
			dataType: 'json',
			data: { idxs: selectedIdxs },
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

	/**
	 * 타임라인 통계 로드 (전체 데이터 기준)
	 */
	function loadTimelineStatistics() {
		var orgId = window.timelinePageData.currentOrgId;

		if (!orgId) {
			return;
		}

		// 로딩 표시
		$('#timelineStaticsLoading').show();
		$('#timelineStatics').empty();

		$.ajax({
			url: window.timelinePageData.baseUrl + 'timeline/get_timeline_statistics',
			type: 'POST',
			dataType: 'json',
			data: {
				org_id: orgId
			},
			success: function(response) {
				$('#timelineStaticsLoading').hide();

				if (response.success) {
					renderTimelineStatistics(response.data);
				} else {
					showToast('통계 조회 실패', response.message || '통계를 불러오는데 실패했습니다.', 'error');
				}
			},
			error: function() {
				$('#timelineStaticsLoading').hide();
				showToast('통계 조회 실패', '통계를 불러오는데 실패했습니다.', 'error');
			}
		});
	}

	/**
	 * 타임라인 통계 렌더링 (Bootstrap Progress 사용, 순서 유지)
	 */
	function renderTimelineStatistics(data) {
		var statistics = data.statistics || [];
		var totalMembers = data.total_members || 0;
		var timelineTypes = data.timeline_types || [];

		var html = '';

		if (statistics.length === 0 || totalMembers === 0) {
			html = '<div class="text-center text-muted py-4">통계 데이터가 없습니다.</div>';
		} else {
			// 타입명 매핑
			var typeMap = {};
			timelineTypes.forEach(function(type) {
				typeMap[type] = type;
			});

			// Progress bar 색상 배열
			var progressColors = ['primary', 'success', 'info', 'warning', 'danger', 'secondary'];

			// 이미 순서대로 정렬된 statistics 사용
			statistics.forEach(function(stat, index) {
				var typeName = typeMap[stat.timeline_type] || stat.timeline_type;
				var memberCount = parseInt(stat.member_count) || 0;

				// 소수점 2자리까지 계산
				var percentage = totalMembers > 0 ? ((memberCount / totalMembers) * 100).toFixed(2) : '0.00';
				var color = progressColors[index % progressColors.length];

				html += '<div class="mb-3">';
				html += '<div class="d-flex justify-content-between align-items-center mb-1">';
				html += '<span class="fw-bold">' + typeName + '</span>';
				html += '<span class="text-muted small">' + percentage + '% (' + memberCount + '명/' + totalMembers + '명)</span>';
				html += '</div>';
				html += '<div class="progress" role="progressbar" aria-label="' + typeName + '" aria-valuenow="' + percentage + '" aria-valuemin="0" aria-valuemax="100">';
				html += '<div class="progress-bar progress-bar-striped progress-bar-animated bg-' + color + '" style="width: ' + percentage + '%"></div>';
				html += '</div>';
				html += '</div>';
			});
		}

		$('#timelineStatics').html(html);
	}


});
