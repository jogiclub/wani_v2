/**
 * 파일 위치: assets/js/custom/pqgrid.fix.js
 * 역할: pqGrid 내 input 요소가 모바일에서 정상 동작하도록 수정
 * 사용법: pqgrid.min.js 로드 후 이 파일을 로드
 */

(function($) {
	'use strict';

	// pqGrid 초기화 여부 확인
	if (typeof $.fn.pqGrid === 'undefined') {
		console.warn('pqGrid가 로드되지 않았습니다. pqgrid.fix.js는 pqgrid.min.js 이후에 로드해야 합니다.');
		return;
	}

	/**
	 * pqGrid input 요소 터치/클릭 이벤트 fix 초기화
	 */
	function initPqGridInputFix() {
		// 이벤트 위임을 사용하여 동적으로 생성된 그리드에도 적용
		$(document)
			// 기존 이벤트 제거
			.off('.pqgrid-input-fix')

			// 모바일 터치 시 input 포커스 처리
			.on('touchstart.pqgrid-input-fix', '.pq-grid input, .pq-grid textarea, .pq-grid select', function(e) {
				e.stopPropagation();
				var $input = $(this);

				// 체크박스/라디오는 별도 처리
				if ($input.is(':checkbox') || $input.is(':radio')) {
					return;
				}

				// 약간의 지연 후 포커스 (pqGrid 이벤트 우회)
				setTimeout(function() {
					$input.focus();
				}, 10);
			})

			// PC 클릭 시 포커스 처리
			.on('click.pqgrid-input-fix', '.pq-grid input, .pq-grid textarea, .pq-grid select', function(e) {
				e.stopPropagation();
				var $input = $(this);

				// 체크박스/라디오는 별도 처리
				if ($input.is(':checkbox') || $input.is(':radio')) {
					return;
				}

				$input.focus();
			})

			// 체크박스 터치 이벤트 (모바일)
			.on('touchend.pqgrid-input-fix', '.pq-grid input:checkbox', function(e) {
				e.stopPropagation();
				e.preventDefault();

				var $checkbox = $(this);
				var newChecked = !$checkbox.prop('checked');
				$checkbox.prop('checked', newChecked).trigger('change');
			})

			// 라디오 터치 이벤트 (모바일)
			.on('touchend.pqgrid-input-fix', '.pq-grid input:radio', function(e) {
				e.stopPropagation();
				e.preventDefault();

				var $radio = $(this);
				$radio.prop('checked', true).trigger('change');
			})

			// input 내부 이벤트가 pqGrid로 전파되지 않도록 처리
			.on('mousedown.pqgrid-input-fix touchstart.pqgrid-input-fix', '.pq-grid input, .pq-grid textarea, .pq-grid select', function(e) {
				e.stopPropagation();
			});
	}

	/**
	 * pqGrid 원본 메서드 확장 - 셀 클릭 시 input 요소 처리
	 */
	var originalPqGrid = $.fn.pqGrid;

	$.fn.pqGrid = function(options) {
		// 새로운 그리드 초기화인 경우
		if (typeof options === 'object' && options !== null) {
			// cellClick 이벤트 래핑
			var originalCellClick = options.cellClick;

			options.cellClick = function(event, ui) {
				var $target = $(event.originalEvent ? event.originalEvent.target : event.target);

				// input 요소가 클릭된 경우 pqGrid 기본 동작 방지
				if ($target.is('input, textarea, select')) {
					if (event.preventDefault) {
						event.preventDefault();
					}
					if (event.stopPropagation) {
						event.stopPropagation();
					}
					return false;
				}

				// 원본 cellClick 호출
				if (typeof originalCellClick === 'function') {
					return originalCellClick.call(this, event, ui);
				}
			};

			// cellTouch 이벤트 래핑 (모바일용)
			var originalCellTouch = options.cellTouch;

			options.cellTouch = function(event, ui) {
				var $target = $(event.originalEvent ? event.originalEvent.target : event.target);

				if ($target.is('input, textarea, select')) {
					if (event.preventDefault) {
						event.preventDefault();
					}
					if (event.stopPropagation) {
						event.stopPropagation();
					}
					return false;
				}

				if (typeof originalCellTouch === 'function') {
					return originalCellTouch.call(this, event, ui);
				}
			};
		}

		return originalPqGrid.apply(this, arguments);
	};

	// 원본 메서드의 속성 복사
	$.extend($.fn.pqGrid, originalPqGrid);

	/**
	 * 셀 선택 비활성화 헬퍼 함수
	 * 사용법: $.fn.pqGrid.disableSelection($('#myGrid'));
	 */
	$.fn.pqGrid.disableSelection = function($grid) {
		if ($grid && $grid.length) {
			$grid.addClass('pq-no-selection');

			try {
				$grid.pqGrid('option', 'selectionModel', { type: 'none' });
				$grid.pqGrid('option', 'hoverMode', 'null');
			} catch (e) {
				console.warn('그리드 선택 비활성화 실패:', e);
			}
		}
	};

	/**
	 * 그리드에 input fix 수동 적용
	 * 사용법: $.fn.pqGrid.applyInputFix($('#myGrid'));
	 */
	$.fn.pqGrid.applyInputFix = function($grid) {
		if (!$grid || !$grid.length) return;

		$grid
			.off('.pqgrid-input-fix-local')
			.on('touchstart.pqgrid-input-fix-local', 'input, textarea, select', function(e) {
				e.stopPropagation();
				var $input = $(this);

				if (!$input.is(':checkbox') && !$input.is(':radio')) {
					setTimeout(function() {
						$input.focus();
					}, 10);
				}
			})
			.on('click.pqgrid-input-fix-local', 'input, textarea, select', function(e) {
				e.stopPropagation();
				var $input = $(this);

				if (!$input.is(':checkbox') && !$input.is(':radio')) {
					$input.focus();
				}
			})
			.on('touchend.pqgrid-input-fix-local', 'input:checkbox', function(e) {
				e.stopPropagation();
				e.preventDefault();

				var $checkbox = $(this);
				$checkbox.prop('checked', !$checkbox.prop('checked')).trigger('change');
			});
	};

	// DOM Ready 시 초기화
	$(document).ready(function() {
		initPqGridInputFix();
	});

	// 전역 함수로도 노출
	window.pqGridInputFix = {
		init: initPqGridInputFix,
		disableSelection: $.fn.pqGrid.disableSelection,
		applyInputFix: $.fn.pqGrid.applyInputFix
	};

})(jQuery);
