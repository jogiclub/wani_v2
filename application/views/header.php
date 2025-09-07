<?php
/**
 * 파일 위치: application/views/header.php
 * 역할: 공통 헤더 및 사이드바 메뉴 (사용자 권한에 따른 메뉴 필터링 포함)
 */
?>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">

<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="description" content="왔니, 세상에서 가장 따뜻한 한마디!"/>
<meta name="keywords" content="출석체크, 교회, 메모, 심방"/>
<meta name="author" content="wani.im"/>

<!-- Facebook and Twitter integration -->
<meta property="og:title" content="왔니"/>
<meta property="og:image" content=""/>
<meta property="og:url" content=""/>
<meta property="og:site_name" content="왔니"/>
<meta property="og:description" content="왔니"/>

<meta name="twitter:title" content="왔니"/>
<meta name="twitter:image" content=""/>
<meta name="twitter:url" content="wani.im"/>
<meta name="twitter:card" content="왔니"/>

<title>왔니, 세상에서 가장 따뜻한 한마디!</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css"
	  integrity="sha512-jnSuA4Ss2PkkikSOLtYs8BlYIeeIK1h99ty4YfvRPAlzr377vr3CXDb7sb7eEEBYjDtcYj+AjBH3FLv5uSJuXg=="
	  crossorigin="anonymous" referrerpolicy="no-referrer"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.13.3/themes/base/jquery-ui.min.css"
	  integrity="sha512-8PjjnSP8Bw/WNPxF6wkklW6qlQJdWJc/3w/ZQPvZ/1bjVDkrrSqLe9mfPYrMxtnzsXFPc434+u4FHLnLjXTSsg=="
	  crossorigin="anonymous" referrerpolicy="no-referrer"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css"
	  integrity="sha512-dPXYcDub/aeb08c63jRq/k6GaKccl256JQy/AnOq7CAnEZ9FzSL9wSbcZkMp4R26vBsMLFYH4kQ67/bbV8XaCQ=="
	  crossorigin="anonymous" referrerpolicy="no-referrer"/>
<link rel="stylesheet" as="style" crossorigin
	  href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard@v1.3.9/dist/web/variable/pretendardvariable.min.css"/>
<!--    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/croppie/2.6.5/croppie.min.css">-->

<link rel="stylesheet" href="/assets/css/common.css?<?php echo WB_VERSION; ?>">

<?php if ($this->session->userdata('user_id')): ?>
<header class="navbar sticky-top flex-xl-nowrap p-0 justify-content-start shadow bg-white">
	<div class="header-start col-xl-6 col-10">
		<a class="navbar-brand col-6 col-xl-1 me-0 px-3 fs-6 logo pe-0" href="#">
			<img src="/assets/images/logo.png">
		</a>

		<div class="btn-group col-auto ms-2">
			<?php if (isset($current_org) && $current_org): ?>
				<button type="button" class="btn btn-light text-truncate text-start" id="current-org-btn">
					<?php echo htmlspecialchars($current_org['org_name']); ?>
				</button>
			<?php else: ?>
				<button type="button" class="btn btn-light text-truncate text-start" id="current-org-btn">
					조직을 선택하세요
				</button>
			<?php endif; ?>

			<button type="button" class="btn btn-light dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown"
					aria-expanded="false">
				<span class="visually-hidden">Toggle Dropdown</span>
			</button>

			<ul class="dropdown-menu" id="org-dropdown-menu">
				<?php if (isset($user_orgs) && !empty($user_orgs)): ?>
					<?php foreach ($user_orgs as $org): ?>
						<li>
							<a class="dropdown-item org-selector"
							   href="#"
							   data-org-id="<?php echo $org['org_id']; ?>"
							   data-org-name="<?php echo htmlspecialchars($org['org_name']); ?>"
								<?php if (isset($current_org) && $current_org['org_id'] == $org['org_id']): ?>
									data-current="true"
								<?php endif; ?>>
								<?php echo htmlspecialchars($org['org_name']); ?>
								<?php if (isset($current_org) && $current_org['org_id'] == $org['org_id']): ?>
									<i class="bi bi-check-circle-fill text-primary ms-2"></i>
								<?php endif; ?>
							</a>
						</li>
					<?php endforeach; ?>
					<li>
						<hr class="dropdown-divider">
					</li>
				<?php endif; ?>
				<li>
					<a class="dropdown-item" href="<?php echo base_url('org'); ?>">
						<i class="bi bi-gear me-2"></i>조직 관리
					</a>
				</li>
			</ul>
		</div>
	</div>
	<div class="header-end col-xl-6 col-2 d-flex justify-content-end px-3 gap-3 align-items-center">
		<ul class="navbar-nav flex-row d-xl-none fs-1">
			<li class="nav-item text-nowrap">
				<button class="nav-link " type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarMenu" aria-controls="sidebarMenu" aria-expanded="false" aria-label="Toggle navigation">
					<i class="bi bi-list"></i>
				</button>
			</li>
		</ul>

		<div id="navbarSearch" class="navbar-search collapse">
			<input class="form-control rounded-0 border-0" type="text" placeholder="Search" aria-label="Search">
		</div>

		<div id="navbarProfile" class="profile-area d-xl-flex align-items-center gap-3 d-none">
			<img src="<?php if ($user['user_profile_image']) {echo $user['user_profile_image'];} else {echo '/assets/images/photo_no.png?3';} ?>" class="rounded-circle profile-img" width="40" height="40">

			<div class="profile-name">
				<span><a class="dropdown-item" href="#"><?php if ($user['user_name']) {
							echo $user['user_name'];
						} ?></a></span>
				<span class="profile-mail"><a class="dropdown-item" href="#"><?php if ($user['user_mail']) {
							echo $user['user_mail'];
						} ?></a></span>
			</div>


		</div>

		<!-- 기존 헤더 내용 중 사용자 정보 드롭다운 부분에 추가 -->
		<?php if ($this->session->userdata('is_admin_login')): ?>
			<div class="alert alert-warning alert-dismissible fade show mb-3 master-login" role="alert">
				<i class="bi bi-exclamation-triangle-fill"></i>
				<strong><?php echo $this->session->userdata('user_name'); ?></strong>님으로 로그인 중입니다.
				<button type="button" class="btn btn-sm btn-outline-dark ms-2" onclick="returnToAdmin()">
					<i class="bi bi-arrow-return-left"></i> 관리자 계정으로 돌아가기
				</button>
				<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
			</div>
		<?php endif; ?>

	</div>
</header>

<?php
/**
 * 사용자 권한에 따른 메뉴 표시 로직
 */


// 메뉴 헬퍼 로드
$this->load->helper('menu');

// 현재 조직에서의 사용자 권한 레벨 확인
$current_user_level = 0;
if (isset($current_org) && $current_org) {
	$this->load->model('User_management_model');
	$current_user_level = $this->User_management_model->get_org_user_level($this->session->userdata('user_id'), $current_org['org_id']);
}

// 사용자의 관리 메뉴 조회
$user_managed_menus = array();
if ($this->session->userdata('master_yn') !== 'Y' && $current_user_level < 10) {
// 최고관리자가 아니고 조직 레벨 10이 아닌 경우 관리 메뉴 조회
	if (!isset($this->User_management_model)) {
		$this->load->model('User_management_model');
	}
	$user_managed_menus = $this->User_management_model->get_user_managed_menus($this->session->userdata('user_id'));
}

/**
 * 메뉴 접근 권한 확인 함수
 */
function can_access_menu($menu_key, $user_managed_menus, $is_master, $user_level)
{
// 최고관리자이거나 조직 레벨 10인 경우 모든 메뉴 접근 가능
	if ($is_master === 'Y' || $user_level >= 10) {
		return true;
	}

// 관리 메뉴가 설정되지 않은 경우 접근 불가
	if (empty($user_managed_menus)) {
		return false;
	}

// 관리 메뉴에 포함된 경우 접근 가능
	return in_array($menu_key, $user_managed_menus);
}

$is_master = $this->session->userdata('master_yn');
?>

<div class="container-fluid gnb-menu">
	<div class="row">
		<div class="sidebar border border-right col-xl-2 p-0 bg-body-tertiary">
			<div class="offcanvas-xl offcanvas-end bg-body-tertiary" tabindex="-1" id="sidebarMenu" aria-labelledby="sidebarMenuLabel">
				<div class="offcanvas-header">
					<button type="button" class="btn-close" data-bs-dismiss="offcanvas" data-bs-target="#sidebarMenu" aria-label="Close"></button>
				</div>
				<div class="offcanvas-body d-xl-flex flex-column p-0 pt-xl-3 overflow-y-auto">

					<!-- OVERVIEW 섹션 -->

						<h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-body-secondary text-uppercase">
							OVERVIEW
						</h6>
						<ul class="nav flex-column">
							<li class="nav-item">
								<a class="nav-link d-flex align-items-center gap-1 menu-11" aria-current="page"
								   href="<?php echo base_url('dashboard'); ?>">
									<i class="bi bi-file-earmark-ruled"></i> 대시보드 <span class="badge badge-sm text-bg-warning">준비중</span>
								</a>
							</li>
						</ul>


					<!-- MEMBER 섹션 -->
					<?php
					$show_member_section = $is_master === 'Y' || $current_user_level >= 10 ||
						can_access_menu('MEMBER_MANAGEMENT', $user_managed_menus, $is_master, $current_user_level) ||
						can_access_menu('ATTENDANCE_MANAGEMENT', $user_managed_menus, $is_master, $current_user_level) ||
						can_access_menu('ATTENDANCE_BOARD', $user_managed_menus, $is_master, $current_user_level);
					?>
					<?php if ($show_member_section): ?>
						<h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-body-secondary text-uppercase">
							MEMBER
						</h6>
						<ul class="nav flex-column mb-auto">
							<?php if ($is_master === 'Y' || $current_user_level >= 10 || can_access_menu('MEMBER_MANAGEMENT', $user_managed_menus, $is_master, $current_user_level)): ?>
								<li class="nav-item">
									<a class="nav-link d-flex align-items-center gap-1 menu-21"
									   href="<?php echo base_url('member'); ?>">
										<i class="bi bi-people"></i> 회원관리
									</a>
								</li>
							<?php endif; ?>
							<?php if ($is_master === 'Y' || $current_user_level >= 10 || can_access_menu('ATTENDANCE_MANAGEMENT', $user_managed_menus, $is_master, $current_user_level)): ?>
								<li class="nav-item">
									<a class="nav-link d-flex align-items-center gap-1 menu-22"
									   href="<?php echo base_url('attendance'); ?>">
										<i class="bi bi-clipboard-check"></i> 출석관리
									</a>
								</li>
							<?php endif; ?>
							<?php if ($is_master === 'Y' || $current_user_level >= 10 || can_access_menu('ATTENDANCE_BOARD', $user_managed_menus, $is_master, $current_user_level)): ?>
								<li class="nav-item">
									<a class="nav-link d-flex align-items-center gap-1 menu-23"
									   href="<?php echo base_url('qrcheck'); ?>">
										<i class="bi bi-qr-code-scan"></i> QR출석
									</a>
								</li>
							<?php endif; ?>
						</ul>
					<?php endif; ?>

					<!-- STATICS 섹션 -->
					<?php
					$show_statics_section = $is_master === 'Y' || $current_user_level >= 10 ||
						can_access_menu('WEEKLY_STATISTICS', $user_managed_menus, $is_master, $current_user_level) ||
						can_access_menu('MEMBER_STATISTICS', $user_managed_menus, $is_master, $current_user_level);
					?>
					<?php if ($show_statics_section): ?>
						<h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-body-secondary text-uppercase">
							STATICS
						</h6>
						<ul class="nav flex-column mb-auto">
							<?php if ($is_master === 'Y' || $current_user_level >= 10 || can_access_menu('WEEKLY_STATISTICS', $user_managed_menus, $is_master, $current_user_level)): ?>
								<li class="nav-item">
									<a class="nav-link d-flex align-items-center gap-1 menu-31" href="#">
										<i class="bi bi-graph-up-arrow"></i> 주별통계 <span class="badge badge-sm text-bg-warning">준비중</span>
									</a>
								</li>
							<?php endif; ?>
							<?php if ($is_master === 'Y' || $current_user_level >= 10 || can_access_menu('MEMBER_STATISTICS', $user_managed_menus, $is_master, $current_user_level)): ?>
								<li class="nav-item">
									<a class="nav-link d-flex align-items-center gap-1 menu-32" href="#">
										<i class="bi bi-clipboard-data"></i> 회원별통계 <span class="badge badge-sm text-bg-warning">준비중</span>
									</a>
								</li>
							<?php endif; ?>
						</ul>
					<?php endif; ?>

					<!-- SETTING 섹션 -->
					<?php
					$show_setting_section = $is_master === 'Y' || $current_user_level >= 10 ||
						can_access_menu('ORG_SETTING', $user_managed_menus, $is_master, $current_user_level) ||
						can_access_menu('GROUP_SETTING', $user_managed_menus, $is_master, $current_user_level) ||
						can_access_menu('DETAIL_FIELD_SETTING', $user_managed_menus, $is_master, $current_user_level) ||
						can_access_menu('ATTENDANCE_SETTING', $user_managed_menus, $is_master, $current_user_level) ||
						can_access_menu('USER_MANAGEMENT', $user_managed_menus, $is_master, $current_user_level);
					?>
					<?php if ($show_setting_section): ?>
						<h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-body-secondary text-uppercase">
							SETTING
						</h6>
						<ul class="nav flex-column mb-auto">
							<?php if ($is_master === 'Y' || $current_user_level >= 10 || can_access_menu('ORG_SETTING', $user_managed_menus, $is_master, $current_user_level)): ?>
								<li class="nav-item">
									<a class="nav-link d-flex align-items-center gap-1 menu-41"
									   href="<?php echo base_url('org'); ?>">
										<i class="bi bi-building-gear"></i> 조직설정
									</a>
								</li>
							<?php endif; ?>

							<?php if ($is_master === 'Y' || $current_user_level >= 10 || can_access_menu('GROUP_SETTING', $user_managed_menus, $is_master, $current_user_level)): ?>
								<li class="nav-item">
									<a class="nav-link d-flex align-items-center gap-1 menu-42"
									   href="<?php echo base_url('group_setting'); ?>">
										<i class="bi bi-diagram-3"></i> 그룹설정
									</a>
								</li>
							<?php endif; ?>
							<?php if ($is_master === 'Y' || $current_user_level >= 10 || can_access_menu('DETAIL_FIELD_SETTING', $user_managed_menus, $is_master, $current_user_level)): ?>
								<li class="nav-item">
									<a class="nav-link d-flex align-items-center gap-1 menu-43"
									   href="<?php echo base_url('detail_field'); ?>">
										<i class="bi bi-input-cursor-text"></i> 상세필드설정
									</a>
								</li>
							<?php endif; ?>
							<?php if ($is_master === 'Y' || $current_user_level >= 10 || can_access_menu('ATTENDANCE_SETTING', $user_managed_menus, $is_master, $current_user_level)): ?>
								<li class="nav-item">
									<a class="nav-link d-flex align-items-center gap-1 menu-44"
									   href="<?php echo base_url('attendance_setting'); ?>">
										<i class="bi bi-sliders2-vertical"></i> 출석설정
									</a>
								</li>
							<?php endif; ?>
							<?php if ($is_master === 'Y' || $current_user_level >= 10 || can_access_menu('USER_MANAGEMENT', $user_managed_menus, $is_master, $current_user_level)): ?>
								<li class="nav-item">
									<a class="nav-link d-flex align-items-center gap-1 menu-45"
									   href="<?php echo base_url('user_management'); ?>">
										<i class="bi bi-person-video"></i> 사용자관리
									</a>
								</li>
							<?php endif; ?>
						</ul>
					<?php endif; ?>

					<hr class="my-3">
					<ul class="nav flex-column mb-auto">
						<li class="nav-item">
							<a class="nav-link d-flex align-items-center gap-1 menu-51"
							   href="<?php echo base_url('login/logout'); ?>">
								<i class="bi bi-box-arrow-right"></i> 로그아웃
							</a>
						</li>
					</ul>
				</div>
			</div>
		</div>
		<main class="ms-sm-auto mt-3">
			<?php endif; ?>


			<script>
				function returnToAdmin() {
					if (confirm('관리자 계정으로 돌아가시겠습니까?')) {
						$.ajax({
							url: '/user_management/return_to_admin',
							type: 'POST',
							dataType: 'json',
							success: function (response) {
								if (response.success) {
									location.reload();
								} else {
									alert('오류가 발생했습니다.');
								}
							},
							error: function () {
								alert('오류가 발생했습니다.');
							}
						});
					}
				}
			</script>
