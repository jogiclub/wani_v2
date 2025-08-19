<head>
	<?php $this->load->view('header'); ?>
</head>


<div class="container pt-2 pb-2">
	<nav class="mb-3" aria-label="breadcrumb">
		<ol class="breadcrumb mb-0">
			<li class="breadcrumb-item"><a href="#!">홈</a></li>
			<li class="breadcrumb-item"><a href="#!">SETTING</a></li>
			<li class="breadcrumb-item active">조직설정</li>
		</ol>
	</nav>
	<div class="row align-items-center justify-content-between g-3 mb-4">
		<h3 class="col-6 my-1">조직설정</h3>
		<div class="col-6 my-1">
			<div class="text-end" role="group" aria-label="Basic example">
				<button type="button" class="btn btn-sm btn-primary"><i class="bi bi-save"></i> 저장</button>
			</div>
		</div>
	</div>
	<div class="row g-3 mb-6">
		<div class="col-12 col-md-4">
			<div class="card h-100">
				<div class="card-body">

					<div class="row align-items-center g-3 g-sm-5">
						<div class="col-12 col-sm-auto text-center ">

							<label class="cursor-pointer avatar avatar-5xl" for="avatarFile">
								<img src="/assets/images/photo_no.png" class="rounded-circle" width="100" height="100">
							</label>
							<div class="mb-3">
								<label for="formFile" class="form-label">100×100 사이즈 이미지<br/>jpg/png 아이콘 가능</label>
								<input class="form-control" type="file" id="formFile">
							</div>
						</div>
					</div>

				</div>
			</div>
		</div>
		<div class="col-12 col-md-8">
			<div class="card">
				<div class="card-body">
					<div class="border-bottom border-dashed">
						<h4 class="mb-3">기본정보</h4>
					</div>


					<div class="pt-4 mb-7 mb-lg-4 mb-xl-7">

						<div class="form-floating mb-3">
							<input type="email" class="form-control" id="floatingInput" placeholder="name@example.com">
							<label for="floatingInput">조직명</label>
						</div>

						<div class="form-floating">
							<input type="password" class="form-control" id="floatingPassword" placeholder="Password">
							<label for="floatingPassword">Password</label>
						</div>

					</div>
					<div class="border-bottom border-dashed">
						<h4 class="mb-3">로케일</h4>
					</div>


					<div class="pt-4 mb-7 mb-lg-4 mb-xl-7">

						<div class="form-floating mb-3">
							<input type="email" class="form-control" id="floatingInput" placeholder="리더, 목자">
							<label for="floatingInput">국가설정</label>
						</div>
						<div class="form-floating mb-3">
							<input type="email" class="form-control" id="floatingInput" placeholder="리더, 목자">
							<label for="floatingInput">타임존설정</label>
						</div>
						<div class="form-floating mb-3">
							<input type="email" class="form-control" id="floatingInput" placeholder="리더, 목자">
							<label for="floatingInput">언어설정</label>
						</div>
					</div>
					<div class="border-bottom border-dashed">
						<h4 class="mb-3">설정</h4>
					</div>
					<div class="pt-4 mb-7 mb-lg-4 mb-xl-7">

						<div class="form-floating mb-3">
							<input type="email" class="form-control" id="floatingInput" placeholder="리더, 목자">
							<label for="floatingInput">리더명칭</label>
						</div>
						<div class="form-floating mb-3">
							<input type="email" class="form-control" id="floatingInput" placeholder="새가족">
							<label for="floatingInput">새회원명칭</label>
						</div>


					</div>

				</div>
			</div>
		</div>
	</div>


</div>


<?php $this->load->view('footer'); ?>


<script src="/assets/js/mypage.js?<?php echo date('Ymdhis'); ?>"></script>




