<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$route['default_controller'] = 'login/logout';
$route['404_override'] = '';
$route['translate_uri_dashes'] = FALSE;

// 관리자 페이지 라우팅
$route['mng'] = 'mng/mng_dashboard';
$route['mng/mng_dashboard'] = 'mng/mng_dashboard';
$route['mng/mng_dashboard/(:any)'] = 'mng/mng_dashboard/$1';
$route['mng/mng_org'] = 'mng/mng_org';
$route['mng/mng_org/(:any)'] = 'mng/mng_org/$1';
$route['mng/mng_master'] = 'mng/mng_master';
$route['mng/mng_master/(:any)'] = 'mng/mng_master/$1';

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



// Week 컨트롤러 관련 라우팅 (넓은 범위의 라우트는 하단에 배치)
$route['week'] = 'week';
$route['week'] = 'week/index';
$route['week/(:any)'] = 'week/index/$1';
$route['week/(:any)/(:num)'] = 'week/index/$1/$2';
$route['week/(:any)/(:num)/(:num)'] = 'week/index/$1/$2/$3';

$route['income'] = 'income';
$route['income'] = 'income/index';

// Education 컨트롤러 관련 라우팅 (기존 코드 교체)
$route['education'] = 'education/index';
$route['education/index'] = 'education/index';
$route['education/get_category_tree'] = 'education/get_category_tree';
$route['education/get_edu_list'] = 'education/get_edu_list';
$route['education/get_edu_detail'] = 'education/get_edu_detail';
$route['education/insert_edu'] = 'education/insert_edu';
$route['education/update_edu'] = 'education/update_edu';
$route['education/delete_edu'] = 'education/delete_edu';
$route['education/delete_multiple_edu'] = 'education/delete_multiple_edu';
$route['education/save_category'] = 'education/save_category';


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
// 게시글 수정 API 라우트 추가
$route['api/homepage/board/update'] = 'api/homepage_api/update_board';

// 관리자 확인 및 수정 권한 확인 API
$route['api/homepage/check_admin'] = 'api/homepage_api/check_admin';
$route['api/homepage/verify_edit_permission'] = 'api/homepage_api/verify_edit_permission';
