<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">

<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="description" content="왔니" />
<meta name="keywords" content="출석 체크, 교적, 메모, 심방" />
<meta name="author" content="wani.im" />

<!-- Facebook and Twitter integration -->
<meta property="og:title" content="왔니"/>
<meta property="og:image" content=""/>
<meta property="og:url" content=""/>
<meta property="og:site_name" content="왔니"/>
<meta property="og:description" content="왔니"/>

<meta name="twitter:title" content="왔니" />
<meta name="twitter:image" content="" />
<meta name="twitter:url" content="wani.im" />
<meta name="twitter:card" content="왔니" />

<title>왔니</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css" integrity="sha512-jnSuA4Ss2PkkikSOLtYs8BlYIeeIK1h99ty4YfvRPAlzr377vr3CXDb7sb7eEEBYjDtcYj+AjBH3FLv5uSJuXg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.13.3/themes/base/jquery-ui.min.css" integrity="sha512-8PjjnSP8Bw/WNPxF6wkklW6qlQJdWJc/3w/ZQPvZ/1bjVDkrrSqLe9mfPYrMxtnzsXFPc434+u4FHLnLjXTSsg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css" integrity="sha512-dPXYcDub/aeb08c63jRq/k6GaKccl256JQy/AnOq7CAnEZ9FzSL9wSbcZkMp4R26vBsMLFYH4kQ67/bbV8XaCQ==" crossorigin="anonymous" referrerpolicy="no-referrer" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/pretendard/1.3.9/static/Pretendard-Medium.min.css" integrity="sha512-kGIhgYqdeB+e4PO0Ipx+D4jNIKPVkdcLHOfT107f/MwZavLS+zhOPKa2vD7kTQHB16mkcwh4MBXsfPF2ODadyQ==" crossorigin="anonymous" referrerpolicy="no-referrer" />
<!--    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/croppie/2.6.5/croppie.min.css">-->


<link rel="stylesheet" href="/assets/css/common.css?<?php echo date('Ymdhis');?>">



<?php if($this->session->userdata('user_id')): ?>
<header class="navbar sticky-top flex-md-nowrap p-0 justify-content-start shadow bg-white">
	<div class="header-start col-md-6 col-10">
		<a class="navbar-brand col-6 col-lg-1 me-0 px-3 fs-6 logo pe-0" href="#">
			<img src="/assets/images/logo.png">
		</a>

		<div class="btn-group col-md-4 col-8">
			<button type="button" class="btn btn-light text-truncate text-start">오병이어교회 고등부</button>
			<button type="button" class="btn btn-light dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false">
				<span class="visually-hidden">Toggle Dropdown</span>
			</button>
			<ul class="dropdown-menu">
				<li><a class="dropdown-item" href="#">오병이어교회 고등부</a></li>
				<li><a class="dropdown-item" href="#">오병이어교회 1교구 1지역</a></li>
				<li><a class="dropdown-item" href="#">오병이어교회 1교구 3지역</a></li>
				<li><hr class="dropdown-divider"></li>
				<li><a class="dropdown-item" href="#"><i class="bi bi-plus-square"></i> 조직 추가</a></li>
			</ul>
		</div>
	</div>
	<div class="header-end col-md-6 col-2 d-flex justify-content-end px-3 gap-3 align-items-center">
		<ul class="navbar-nav flex-row d-md-none fs-1">
			<li class="nav-item text-nowrap">
				<button class="nav-link " type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarMenu" aria-controls="sidebarMenu" aria-expanded="false" aria-label="Toggle navigation">
					<i class="bi bi-list"></i>
				</button>
			</li>
		</ul>

		<div id="navbarSearch" class="navbar-search w-100 collapse">
			<input class="form-control w-100 rounded-0 border-0" type="text" placeholder="Search" aria-label="Search">
		</div>


		<div id="navbarProfile" class="profile-area d-md-flex align-items-center gap-3 d-none">
			<img src="<?php if($user['user_profile_image']){echo $user['user_profile_image'];} else {echo '/assets/images/photo_no.png?3';} ?>" class="rounded-circle profile-img" width="40" height="40">
			<div class="profile-name">
				<span><a class="dropdown-item" href="#"><?php if($user['user_name']){echo $user['user_name'];} ?></a></span>
				<span class="profile-mail"><a class="dropdown-item" href="#"><?php if($user['user_mail']){echo $user['user_mail'];} ?></a></span>
			</div>
		</div>
	</div>


</header>


<div class="container-fluid gnb-menu">
	<div class="row">
		<div class="sidebar border border-right col-md-3 col-lg-2 p-0 bg-body-tertiary">
			<div class="offcanvas-md offcanvas-end bg-body-tertiary" tabindex="-1" id="sidebarMenu" aria-labelledby="sidebarMenuLabel">
				<div class="offcanvas-header">
					<button type="button" class="btn-close" data-bs-dismiss="offcanvas" data-bs-target="#sidebarMenu" aria-label="Close"></button>
				</div>
				<div class="offcanvas-body d-md-flex flex-column p-0 pt-lg-3 overflow-y-auto">
					<h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-body-secondary text-uppercase">
						OVERVIEW
					</h6>					
					<ul class="nav flex-column">
						<li class="nav-item"><a class="nav-link d-flex align-items-center gap-1 active" aria-current="page" href="#"><i class="bi bi-file-earmark-ruled"></i> 대시보드</a></li>
					</ul>
					<h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-body-secondary text-uppercase">
					MEMBER
					</h6>
					<ul class="nav flex-column mb-auto">
						<li class="nav-item"><a class="nav-link d-flex align-items-center gap-1" href="#"><i class="bi bi-people"></i> 회원관리</a></li>
						<li class="nav-item"><a class="nav-link d-flex align-items-center gap-1" href="#"><i class="bi bi-clipboard-check"></i> 출석관리</a></li>
						<li class="nav-item"><a class="nav-link d-flex align-items-center gap-1" href="#"><i class="bi bi-person-check"></i> 판</a></li>
					</ul>
					<h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-body-secondary text-uppercase">
						STATICS
					</h6>
					<ul class="nav flex-column mb-auto">
						<li class="nav-item"><a class="nav-link d-flex align-items-center gap-1" href="#"><i class="bi bi-graph-up-arrow"></i> 주별통계</a></li>
						<li class="nav-item"><a class="nav-link d-flex align-items-center gap-1" href="#"><i class="bi bi-clipboard-data"></i> 회원별통계</a></li>
					</ul>
					<h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-body-secondary text-uppercase">
						SETTING
					</h6>
					<ul class="nav flex-column mb-auto">
						<li class="nav-item"><a class="nav-link d-flex align-items-center gap-1" href="<?php echo base_url('org'); ?>"><i class="bi bi-diagram-3-fill"></i> 조직설정</a></li>
						<li class="nav-item"><a class="nav-link d-flex align-items-center gap-1" href="#"><i class="bi bi-input-cursor-text"></i> 상세필드설정</a></li>
						<li class="nav-item"><a class="nav-link d-flex align-items-center gap-1" href="#"><i class="bi bi-sliders2-vertical"></i> 출석설정</a></li>
						<li class="nav-item"><a class="nav-link d-flex align-items-center gap-1" href="#"><i class="bi bi-person-video"></i> 사용자관리</a></li>
					</ul>
					<hr class="my-3">
					<ul class="nav flex-column mb-auto">						
						<li class="nav-item"><a class="nav-link d-flex align-items-center gap-1" href="<?php echo base_url('main/logout'); ?>"><i class="bi bi-box-arrow-right"></i> 로그아웃</a></li>
					</ul>
				</div>
			</div>
		</div>
		<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
<?php endif; ?>

