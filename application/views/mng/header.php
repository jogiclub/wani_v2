<!DOCTYPE html>
<html lang="ko">
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="description" content="왔니 관리자"/>
	<meta name="keywords" content="왔니, 관리자, 조직관리"/>
	<meta name="author" content="wani.im"/>

	<title>왔니 관리자</title>

	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css" integrity="sha512-jnSuA4Ss2PkkikSOLtYs8BlYIeeIK1h99ty4YfvRPAlzr377vr3CXDb7sb7eEEBYjDtcYj+AjBH3FLv5uSJuXg==" crossorigin="anonymous" referrerpolicy="no-referrer"/>
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.13.3/themes/base/jquery-ui.min.css" integrity="sha512-8PjjnSP8Bw/WNPxF6wkklW6qlQJdWJc/3w/ZQPvZ/1bjVDkrrSqLe9mfPYrMxtnzsXFPc434+u4FHLnLjXTSsg==" crossorigin="anonymous" referrerpolicy="no-referrer"/>
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css" integrity="sha512-dPXYcDub/aeb08c63jRq/k6GaKccl256JQy/AnOq7CAnEZ9FzSL9wSbcZkMp4R26vBsMLFYH4kQ67/bbV8XaCQ==" crossorigin="anonymous" referrerpolicy="no-referrer"/>
	<link rel="stylesheet" as="style" crossorigin href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard@v1.3.9/dist/web/variable/pretendardvariable.min.css" />

	<link rel="stylesheet" href="/assets/css/common.css?<?php echo WB_VERSION; ?>">
	<link rel="stylesheet" href="/assets/css/mng_common.css?<?php echo WB_VERSION; ?>">

</head>

<body>
<!-- 상단 네비게이션 -->
<header class="navbar sticky-top flex-xl-nowrap p-0 justify-content-start shadow bg-dark">
	<div class="header-start col-xl-6 col-10">
		<a class="navbar-brand col-md-3 col-lg-2 me-0 px-3 fs-4 d-flex align-items-center text-white" href="/mng/mng_org">
			<img src="/assets/images/logo.png" alt="왔니" class="me-2" style="height: 20px"> 관리자
		</a>
	</div>

	<div class="header-end col-xl-6 col-2 d-flex justify-content-end px-3 gap-3 align-items-center">
		<ul class="navbar-nav flex-row d-xl-none fs-1">
			<li class="nav-item text-nowrap">
				<button class="nav-link " type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarMenu" aria-controls="sidebarMenu" aria-expanded="false" aria-label="Toggle navigation">
					<i class="bi bi-list"></i>
				</button>
			</li>
		</ul>

		<div id="navbarProfile" class="profile-area d-xl-flex align-items-center gap-3 d-none">
			<img src="<?php if ($user['user_profile_image']) {echo $user['user_profile_image'];} else {echo '/assets/images/photo_no.png?3';} ?>" class="rounded-circle profile-img" width="40" height="40">
			<div class="profile-name text-white">
				<small><a class="dropdown-item" href="#"><?php if ($user['user_name']) {echo $user['user_name'];} ?></a></small>
				<small class="profile-mail"><a class="dropdown-item" href="#"><?php if ($user['user_mail']) {echo $user['user_mail'];} ?></a></small>
			</div>
		</div>
	</div>
</header>

<div class="wrapper d-flex bg-light"></div>
<div class="sidebar border border-right p-0 bg-body-tertiary">
	<div class="offcanvas-xl offcanvas-end bg-body-tertiary" tabindex="-1" id="sidebarMenu" aria-labelledby="sidebarMenuLabel">
		<div class="offcanvas-header">
			<button type="button" class="btn-close" data-bs-dismiss="offcanvas" data-bs-target="#sidebarMenu" aria-label="Close"></button>
		</div>
		<div class="offcanvas-body d-xl-flex flex-column p-0 pt-xl-3 overflow-y-auto">
				<h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted text-uppercase">
					<span>OVERVIEW</span>
				</h6>
				<ul class="nav flex-column">
					<li class="nav-item">
						<a class="nav-link" href="#" onclick="showToast('준비 중입니다.', 'info')">
							<i class="bi bi-file-earmark-ruled"></i> 대시보드 <span class="badge badge-sm text-bg-warning">준비중</span>
						</a>
					</li>
				</ul>
				<h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted text-uppercase">
					<span>ORG</span>
				</h6>
				<ul class="nav flex-column">
					<li class="nav-item">
						<a class="nav-link <?php echo (uri_string() == 'mng/mng_org' || strpos(uri_string(), 'mng/mng_org') === 0) ? 'active' : ''; ?>" href="/mng/mng_org">
							<i class="bi bi-building"></i> 조직관리
						</a>
					</li>
				</ul>
				<h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted text-uppercase">
					<span>MEAK</span>
				</h6>
				<ul class="nav flex-column">
					<li class="nav-item">
						<a class="nav-link" href="#" onclick="showToast('준비 중입니다.', 'info')">
							<i class="bi bi-people"></i> 결연교회관리
						</a>
					</li>

				</ul>
		</div>
	</div>
</div>




<!-- 메인 콘텐츠 -->
<main class="main mt-3">
