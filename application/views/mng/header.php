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

	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.13.3/themes/base/jquery-ui.min.css" integrity="sha512-8PjjnSP8Bw/WNPxF6wkklW6qlQJdWJc/3w/ZQPvZ/1bjVDkrrSqLe9mfPYrMxtnzsXFPc434+u4FHLnLjXTSsg==" crossorigin="anonymous" referrerpolicy="no-referrer"/>
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css" integrity="sha512-dPXYcDub/aeb08c63jRq/k6GaKccl256JQy/AnOq7CAnEZ9FzSL9wSbcZkMp4R26vBsMLFYH4kQ67/bbV8XaCQ==" crossorigin="anonymous" referrerpolicy="no-referrer"/>
	<link href="https://cdn.jsdelivr.net/npm/pretendard@1.3.9/dist/web/static/pretendard.min.css" rel="stylesheet">


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
<?php
// 마스터 사용자의 관리 메뉴 조회
$master_managed_menus = array();
$user_id = $this->session->userdata('user_id');

if ($user_id) {
	$this->load->model('User_model');
	$user = $this->User_model->get_user_by_id($user_id);

	if (!empty($user['master_managed_menus'])) {
		$master_managed_menus = json_decode($user['master_managed_menus'], true);
		if (!is_array($master_managed_menus)) {
			$master_managed_menus = array();
		}
	}
}

/**
 * 마스터 메뉴 접근 권한 확인 함수
 */
function can_access_master_menu($menu_key, $master_managed_menus) {
	// master_managed_menus가 비어있으면 모든 메뉴 접근 가능 (기본값)
	if (empty($master_managed_menus)) {
		return true;
	}

	// 관리 메뉴에 포함된 경우 접근 가능
	return in_array($menu_key, $master_managed_menus);
}
?>

<div class="wrapper d-flex bg-light"></div>
<div class="sidebar border border-right p-0 bg-primary-subtle">
	<div class="offcanvas-xl offcanvas-end bg-primary-subtle" tabindex="-1" id="sidebarMenu" aria-labelledby="sidebarMenuLabel">
		<div class="offcanvas-header">
			<button type="button" class="btn-close" data-bs-dismiss="offcanvas" data-bs-target="#sidebarMenu" aria-label="Close"></button>
		</div>
		<div class="offcanvas-body d-xl-flex flex-column p-0 pt-xl-3 overflow-y-auto">
			<!-- 대시보드 (항상 표시) -->
			<h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted text-uppercase">
				<span>OVERVIEW</span>
			</h6>
			<ul class="nav flex-column">
				<li class="nav-item">
					<a class="nav-link <?php echo (uri_string() == 'mng/mng_dashboard' || strpos(uri_string(), 'mng/mng_dashboard') === 0) ? 'active' : ''; ?>" href="/mng/mng_dashboard">
						<i class="bi bi-file-earmark-ruled"></i> 대시보드
					</a>
				</li>
			</ul>

			<!-- 조직관리 (권한 확인) -->
			<?php if (can_access_master_menu('mng_org', $master_managed_menus)): ?>
				<h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted text-uppercase">
					<span>ORG</span>
				</h6>
				<ul class="nav flex-column">
					<li class="nav-item">
						<a class="nav-link <?php echo (uri_string() == 'mng/mng_org' || strpos(uri_string(), 'mng/mng_org') === 0) ? 'active' : ''; ?>" href="/mng/mng_org">
							<i class="bi bi-building"></i> 조직관리
						</a>
					</li>
					<li class="nav-item">
						<a class="nav-link <?php echo (uri_string() == 'mng/mng_member' || strpos(uri_string(), 'mng/mng_member') === 0) ? 'active' : ''; ?>" href="/mng/mng_member">
							<i class="bi bi-person"></i> 회원관리
						</a>
					</li>
					<li class="nav-item">
						<a class="nav-link <?php echo (uri_string() == 'mng/mng_education' || strpos(uri_string(), 'mng/mng_education') === 0) ? 'active' : ''; ?>" href="/mng/mng_education">
							<i class="bi bi-person"></i> 양육관리
						</a>
					</li>
				</ul>
			<?php endif; ?>

			<!-- 마스터관리 (권한 확인) -->
			<?php if (can_access_master_menu('mng_master', $master_managed_menus)): ?>
				<h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted text-uppercase">
					<span>MASTER</span>
				</h6>
				<ul class="nav flex-column">
					<li class="nav-item">
						<a class="nav-link <?php echo (uri_string() == 'mng/mng_master' || strpos(uri_string(), 'mng/mng_master') === 0) ? 'active' : ''; ?>" href="/mng/mng_master">
							<i class="bi bi-people"></i> 마스터관리
						</a>
					</li>
					<li class="nav-item">
						<a class="nav-link <?php echo (uri_string() == 'mng/mng_cost' || strpos(uri_string(), 'mng/mng_cost') === 0) ? 'active' : ''; ?>" href="/mng/mng_cost">
							<i class="bi bi-cash-coin"></i> 비용관리
						</a>
					</li>
					<li class="nav-item">
						<a class="nav-link <?php echo (uri_string() == 'mng/mng_homepage' || strpos(uri_string(), 'mng/mng_homepage') === 0) ? 'active' : ''; ?>" href="/mng/mng_homepage">
							<i class="bi bi-globe"></i> 홈페이지관리
						</a>
					</li>
				</ul>
			<?php endif; ?>
		</div>
	</div>
</div>




<!-- 메인 콘텐츠 -->
<main class="main mt-3">
