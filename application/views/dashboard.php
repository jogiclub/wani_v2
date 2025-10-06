
<?php $this->load->view('header'); ?>



<div class="container pt-2 pb-2">

	<nav class="mb-1" aria-label="breadcrumb">
		<ol class="breadcrumb mb-0">
			<li class="breadcrumb-item"><a href="#!">홈</a></li>
			<li class="breadcrumb-item"><a href="#!">OVERVIEW</a></li>
			<li class="breadcrumb-item active">대시보드</li>
		</ol>
	</nav>
	<div class="col-12 my-1 d-flex align-items-center justify-content-between mb-3">
		<h3 class="page-title col-12 mb-0">대시보드</h3>
	</div>
</div>




<?php $this->load->view('footer'); ?>

<script>
	/*출석관리 메뉴 active*/
	$('.menu-11').addClass('active');
</script>
<script src="/assets/js/dashboard.js?<?php echo WB_VERSION; ?>"></script>

