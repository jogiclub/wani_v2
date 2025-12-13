<?php
/**
 * 역할: 조직 지도 표시 팝업
 */
?>
<?php include APPPATH . 'views/header_noframe.php'; ?>
<style>
	body {
		margin: 0;
		padding: 0;
		overflow: hidden;
	}

	.map-container {
		width: 100vw;
		height: 100vh;
		position: relative;
	}

	#kakaoMap {
		width: 100%;
		height: calc(100% - 60px);
	}

	.map-header {
		height: 60px;
		background-color: #fff;
		border-bottom: 1px solid #dee2e6;
		display: flex;
		align-items: center;
		justify-content: space-between;
		padding: 0 20px;
	}

	.map-info {
		font-size: 14px;
		color: #6c757d;
	}

	.map-info .count {
		color: #0d6efd;
		font-weight: bold;
	}

	.custom-overlay {
		position: relative;
		background: #fff;
		border: 2px solid #0d6efd;
		border-radius: 8px;
		padding: 8px 12px;
		font-size: 13px;
		box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
		min-width: 150px;
	}

	.custom-overlay::after {
		content: '';
		position: absolute;
		bottom: -12px;
		left: 50%;
		margin-left: -6px;
		border: 6px solid transparent;
		border-top-color: #0d6efd;
	}

	.custom-overlay .title {
		font-weight: bold;
		color: #212529;
		margin-bottom: 2px;
	}

	.custom-overlay .address {
		font-size: 11px;
		color: #6c757d;
	}
</style>

<div class="map-container">
	<div class="map-header">
		<div>
			<h6 class="mb-0">
				<i class="bi bi-geo-alt-fill text-primary"></i>
				조직 위치 지도
			</h6>
		</div>
		<div class="map-info">
			표시된 조직: <span class="count" id="markerCount">0</span>개
		</div>
		<div>
			<button type="button" class="btn btn-sm btn-secondary" onclick="window.close()">
				<i class="bi bi-x-lg"></i> 닫기
			</button>
		</div>
	</div>
	<div id="kakaoMap"></div>
</div>

<?php include APPPATH . 'views/footer_noframe.php'; ?>
<script type="text/javascript" src="//dapi.kakao.com/v2/maps/sdk.js?appkey=88bf34f13ff4bacc608dae27bf68e6e6&libraries=services"></script>
<script src="/assets/js/org_map_popup.js?<?php echo WB_VERSION; ?>"></script>
