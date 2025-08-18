<html lang="ko">
<head>
    <?php
//	$this->load->view('header');
	?>
</head>
<body>

<header class="pt-3 pb-3 border-bottom d-flex justify-content-center">
    <div class="logo"><img src="/assets/images/logo.png?2"></div>
</header>
<main>
    <div class="container mt-5">
        <div class="row justify-content-center">


            <div class="col-md-6">
                <h2 class="text-center">로그인</h2>
                <div class="d-grid">
                    <a href="<?php echo base_url('login/google_login'); ?>" class="btn btn-danger mt-2">구글계정으로 시작하기</a>
                    <a href="<?php echo base_url('login/naver_login'); ?>" class="btn btn-success mt-2">네이버로 계정으로 시작하기</a>
                    <a href="<?php echo base_url('login/kakao_login'); ?>" class="btn btn-warning mt-2">카카오톡 계정으로 시작하기</a>
                </div>
            </div>


        </div>
    </div>
</main>
<footer>

</footer>
<?php //$this->load->view('footer'); ?>
<script type="text/javascript" src="https://static.nid.naver.com/js/naveridlogin_js_sdk_2.0.0.js"charset="utf-8"></script>
<script src="/assets/js/login.js?<?php echo date('Ymdhis');?>"></script>
<script>

</body>
</html>
