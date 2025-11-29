<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| -------------------------------------------------------------------------
| URI ROUTING
| -------------------------------------------------------------------------
| This file lets you re-map URI requests to specific controller functions.
|
| Typically there is a one-to-one relationship between a URL string
| and its corresponding controller class/method. The segments in a
| URL normally follow this pattern:
|
|	example.com/class/method/id/
|
| In some instances, however, you may want to remap this relationship
| so that a different class/function is called than the one
| corresponding to the URL.
|
| Please see the user guide for complete details:
|
|	https://codeigniter.com/userguide3/general/routing.html
|
| -------------------------------------------------------------------------
| RESERVED ROUTES
| -------------------------------------------------------------------------
|
| There are three reserved routes:
|
|	$route['default_controller'] = 'welcome';
|
| This route indicates which controller class should be loaded if the
| URI contains no data. In the above example, the "welcome" class
| would be loaded.
|
|	$route['404_override'] = 'errors/page_missing';
|
| This route will tell the Router which controller/method to use if those
| provided in the URL cannot be matched to a valid route.
|
|	$route['translate_uri_dashes'] = FALSE;
|
| This is not exactly a route, but allows you to automatically route
| controller and method names that contain dashes. '-' isn't a valid
| class or method name character, so it requires translation.
| When you set this option to TRUE, it will replace ALL dashes in the
| controller and method URI segments.
|
| Examples:	my-controller/index	-> my_controller/index
|		my-controller/my-method	-> my_controller/my_method
*/

$route['default_controller'] = 'login/logout';
$route['404_override'] = '';
$route['translate_uri_dashes'] = FALSE;

// 관리자 페이지 라우팅
$route['mng'] = 'mng/mng_org';
$route['mng/mng_org'] = 'mng/mng_org';
$route['mng/mng_org/(:any)'] = 'mng/mng_org/$1';


// 회원 정보 조회 라우트 (최상단에 배치 - 중요!)
$route['member_info/add_settlement_memo'] = 'member_info/add_settlement_memo';
$route['member_info/update_settlement_memo'] = 'member_info/update_settlement_memo';
$route['member_info/delete_settlement_memo'] = 'member_info/delete_settlement_memo';
$route['member_info/(:num)/(:num)'] = 'member_info/index/$1/$2';

// Offer 컨트롤러 관련 라우팅 (결연교회 추천)
$route['offer'] = 'offer/index';
$route['offer/(:num)/(:num)/(:any)'] = 'offer/index/$1/$2/$3';
$route['offer/select_church'] = 'offer/select_church';

// Member_card 컨트롤러 관련 라우팅
$route['member_card/register/(:num)/(:num)/(:any)'] = 'member_card/register/$1/$2/$3';
$route['member_card/save_member'] = 'member_card/save_member';

// Login 컨트롤러 관련 라우팅
$route['login/index'] = 'login/index';
$route['login/google_login'] = 'login/google_login';

// Mypage 컨트롤러 관련 라우팅
$route['mypage/add_group'] = 'mypage/add_group';
$route['mypage/get_groups'] = 'mypage/get_groups';
$route['mypage/delete_group'] = 'mypage/delete_group';
$route['mypage/update_del_yn'] = 'mypage/update_del_yn';
$route['mypage/add_attendance_type'] = 'mypage/add_attendance_type';
$route['mypage/add_attendance_type_category'] = 'mypage/add_attendance_type_category';

// Member 컨트롤러 관련 라우팅
$route['member'] = 'member/index';
$route['member/index'] = 'member/index';
$route['member/get_group_tree'] = 'member/get_group_tree';
$route['member/get_org_tree'] = 'member/get_org_tree';
$route['member/get_members'] = 'member/get_members';
$route['member/add_member'] = 'member/add_member';
$route['member/update_member'] = 'member/update_member';
$route['member/delete_member'] = 'member/delete_member';
$route['member/delete_members'] = 'member/delete_members';
$route['member/move_members'] = 'member/move_members';
$route['member/get_detail_fields'] = 'member/get_detail_fields';
$route['member/get_member_detail'] = 'member/get_member_detail';
$route['member/save_memo'] = 'member/save_memo';
$route['member/get_memo_list'] = 'member/get_memo_list';
$route['member/update_memo'] = 'member/update_memo';
$route['member/delete_memo'] = 'member/delete_memo';
$route['member/get_timeline_types'] = 'member/get_timeline_types';
$route['member/get_timeline_list'] = 'member/get_timeline_list';
$route['member/save_timeline'] = 'member/save_timeline';
$route['member/update_timeline'] = 'member/update_timeline';
$route['member/delete_timeline'] = 'member/delete_timeline';
$route['member/get_org_positions_duties'] = 'member/get_org_positions_duties';
$route['member/print_selected_qr'] = 'member/print_selected_qr';
$route['member/member_popup'] = 'member/member_popup';
$route['member/save_member_popup'] = 'member/save_member_popup';

// ... (나머지 라우트들은 기존 그대로)

// Week 컨트롤러 관련 라우팅 (넓은 범위의 라우트는 하단에 배치)
$route['week'] = 'week';
$route['week'] = 'week/index';
$route['week/(:any)'] = 'week/index/$1';
$route['week/(:any)/(:num)'] = 'week/index/$1/$2';
$route['week/(:any)/(:num)/(:num)'] = 'week/index/$1/$2/$3';

// 직접 group_code/year/week 형식으로 접근하는 경우도 처리
$route['(:any)/(:num)/(:num)'] = 'week/index/$1/$2/$3';


if (defined('STDIN')) {
	$route['cron/poll_sms_results'] = 'cron/poll_sms_results';
	$route['cron/process_scheduled_messages'] = 'cron/process_scheduled_messages';
}

// 파일 다운로드 라우트 추가
$route['api/download'] = 'api/homepage_api/download_file';

// 홈페이지 API 라우팅
$route['api/homepage/menu/(:any)'] = 'api/homepage_api/get_menu/$1';
$route['api/homepage/page/(:any)/(:any)'] = 'api/homepage_api/get_page/$1/$2';
$route['api/homepage/link/(:any)/(:any)'] = 'api/homepage_api/get_link/$1/$2';
$route['api/homepage/board/(:any)/(:any)'] = 'api/homepage_api/get_board_list/$1/$2';
$route['api/homepage/board/detail/(:any)/(:num)'] = 'api/homepage_api/get_board_detail/$1/$2';
$route['api/homepage/org/(:any)'] = 'api/homepage_api/get_org_info/$1';

// 아래 라우트를 routes.php 파일의 홈페이지 API 라우팅 섹션에 추가하세요
$route['api/homepage/board/verify_writer'] = 'api/homepage_api/verify_writer';
$route['api/homepage/board/save'] = 'api/homepage_api/save_board';

// 파일 업로드 API 라우트 추가 (이 부분을 추가!)
// 파일 업로드 API 라우트 추가
$route['api/homepage_api/upload_file'] = 'api/homepage_api/upload_file';
$route['api/homepage/upload'] = 'api/homepage_api/upload_file';  // 대체 경로 (선택사항)
