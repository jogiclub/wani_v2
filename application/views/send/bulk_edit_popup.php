<?php
/**
 * 역할: 문자 발송 팝업 화면
 */
?>
<?php include APPPATH . 'views/noheader.php'; ?>
<link rel="stylesheet" href="/assets/css/custom/pqgrid.min.css?<?php echo WB_VERSION; ?>">
<style>
	#bulkEditGrid {
		width: 100%;
		height: calc(100vh - 150px);
	}

	#bulkEditGrid .pq-grid-cell, #bulkEditGrid .pq-grid-number-cell {
		text-align: center;
		height: 30px
	}
</style>
<div class="container send-popup">
	<div class="row">
		<div class="col-12">
			<div class="alert alert-warning alert-dismissible fade show" role="alert">
				엑셀에서 복사하여 붙여 넣기가 가능하며 하단의 '셀추가' 이용이 가능합니다.
				<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
			</div>
			<div class="card">
				<div class="card-body card-height">
					<div id="bulkEditGrid"></div>
				</div>
				<div class="card-footer d-flex justify-content-between align-items-center">
					<div class="input-group" style="width: 250px;">
						<input type="number" class="form-control form-control-sm" id="addCellCount" placeholder="추가할 개수" min="1" max="100" value="1">
						<button type="button" class="btn btn-sm btn-outline-primary" id="addCell"><i class="bi bi-plus-square-dotted"></i> 셀추가</button>
					</div>
					<div>
						<button type="button" class="btn btn-secondary me-2" onclick="window.close()">취소</button>
						<button type="button" class="btn btn-primary" id="btnSaveBulkEdit">저장</button>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<?php include APPPATH . 'views/nofooter.php'; ?>
<script src="/assets/js/custom/pqgrid.min.js?<?php echo WB_VERSION; ?>"></script>
<script src="/assets/js/bulk_edit_popup.js?<?php echo WB_VERSION; ?>"></script>
