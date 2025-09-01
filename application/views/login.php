<?php $this->load->view('noheader'); ?>

<div class="col-3 mx-auto">
	<h2 class="text-center">로그인</h2>
	<div class="d-grid">
		<a href="<?php echo base_url('login/google_login'); ?>" class="btn btn-danger mt-2"><i class="bi bi-google"></i> 구글계정으로 시작하기</a>
		<a href="<?php echo base_url('login/naver_login'); ?>" class="btn btn-success mt-2"><span style="font-weight: 900">N</span> 네이버계정으로 시작하기</a>
		<a href="<?php echo base_url('login/kakao_login'); ?>" class="btn btn-warning mt-2"><i class="bi bi-chat-fill"></i> 카카오톡 계정으로 시작하기</a>
	</div>
</div>




<?php $this->load->view('footer'); ?>
<script type="text/javascript" src="https://static.nid.naver.com/js/naveridlogin_js_sdk_2.0.0.js" charset="utf-8"></script>
<script src="/assets/js/login.js?<?php echo date('Ymdhis'); ?>"></script>


