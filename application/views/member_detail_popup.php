<?php
include APPPATH . 'views/header_noframe.php';

$member_view_path = APPPATH . 'views/member.php';
$member_view_html = file_exists($member_view_path) ? file_get_contents($member_view_path) : '';

$extract_html = function ($pattern) use ($member_view_html) {
	if (empty($member_view_html)) {
		return '';
	}
	if (preg_match($pattern, $member_view_html, $matches)) {
		return $matches[0];
	}
	return '';
};

$member_form_html = $extract_html('/<form id="memberForm"[\s\S]*?<\/form>/u');

$modal_ids = array(
	'transferOrgModal',
	'deleteTransferOrgModal',
	'autoMatchChurchModal',
	'sendOfferModal',
	'sendMemberInfoModal',
	'memoModal',
	'deleteMemoModal',
	'timelineModal',
	'deleteTimelineModal'
);

$modal_html = '';
foreach ($modal_ids as $modal_id) {
	$pattern = '/<div class="modal fade" id="' . preg_quote($modal_id, '/') . '"[\s\S]*?<\/div>\s*<\/div>\s*<\/div>/u';
	$modal_html .= $extract_html($pattern) . PHP_EOL;
}
?>
<link rel="stylesheet" href="/assets/css/member.css?<?php echo WB_VERSION; ?>">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/croppie@2.6.5/croppie.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/family-chart@0.9.0/dist/styles/family-chart.min.css">

<style>
	.member-popup-container { padding: 12px; }
	.member-popup-card { height: calc(100vh - 24px); display: flex; flex-direction: column; }
	.member-popup-header { padding: 12px 16px; border-bottom: 1px solid #dee2e6; display: flex; justify-content: space-between; align-items: center; }
	.member-popup-body { flex: 1; overflow-y: auto; }
	.member-popup-footer { padding: 12px; border-top: 1px solid #dee2e6; background: #f8f9fa; }
</style>

<div class="container-fluid member-popup-container">
	<div class="card member-popup-card">
		<div class="member-popup-header">
			<h5 class="mb-0" id="memberOffcanvasLabel">회원 정보 수정</h5>
			<button type="button" class="btn-close" aria-label="Close" onclick="window.close()"></button>
		</div>
		<div class="member-popup-body">
			<?php if (!empty($member_form_html)): ?>
				<?php echo $member_form_html; ?>
			<?php else: ?>
				<div class="alert alert-danger m-3">회원 폼을 불러오지 못했습니다.</div>
			<?php endif; ?>
		</div>
		<div class="member-popup-footer">
			<div class="d-flex gap-2">
				<button type="button" class="btn btn-secondary flex-fill" onclick="window.close()">닫기</button>
				<button type="button" class="btn btn-primary flex-fill" id="btnSaveMember">저장</button>
			</div>
		</div>
	</div>
</div>

<?php echo $modal_html; ?>

<?php include APPPATH . 'views/footer_noframe.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/croppie@2.6.5/croppie.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="/assets/js/custom/select2.sortable.min.js?<?php echo WB_VERSION; ?>"></script>
<script src="https://unpkg.com/d3@7"></script>
<script src="https://cdn.jsdelivr.net/npm/family-chart@0.9.0/dist/family-chart.min.js"></script>

<script>
	window.isMemberPopup = true;
	window.memberPageData = {
		baseUrl: '<?php echo base_url(); ?>'
	};
	window.popupMemberIdx = '<?php echo $member_idx; ?>';
	window.popupOrgId = '<?php echo $org_id; ?>';
	window.popupMemberAreas = <?php echo json_encode($member_areas); ?>;
</script>
<script src="/assets/js/member.js?<?php echo WB_VERSION; ?>"></script>
<script src="/assets/js/member-family.js?<?php echo WB_VERSION; ?>"></script>
