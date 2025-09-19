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
$route['default_controller'] = 'qrcheck';
$route['404_override'] = '';
$route['translate_uri_dashes'] = FALSE;

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

// Member 컨트롤러 관련 라우팅 (새로 추가)
$route['member'] = 'member/index';
$route['member/index'] = 'member/index';
$route['member/get_group_tree'] = 'member/get_group_tree';
$route['member/get_members'] = 'member/get_members';
$route['member/add_member'] = 'member/add_member';
$route['member/update_member'] = 'member/update_member';
$route['member/delete_member'] = 'member/delete_member';

// Main 컨트롤러 관련 라우팅
$route['main/profile'] = 'main/profile';
$route['login/logout'] = 'login/logout';
$route['main/get_members'] = 'main/get_members';
$route['main/save_attendance'] = 'main/save_attendance';
$route['main/get_attendance_types'] = 'main/get_attendance_types';
$route['main/get_member_attendance'] = 'main/get_member_attendance';
$route['main/get_attendance_data'] = 'main/get_attendance_data';
$route['main/add_member '] = 'main/add_member ';

// Week 컨트롤러 관련 라우팅
$route['week/profile'] = 'week/profile';
$route['week/logout'] = 'week/logout';
$route['week/get_members'] = 'week/get_members';
$route['week/save_attendance'] = 'week/save_attendance';
$route['week/get_attendance_types'] = 'week/get_attendance_types';
$route['week/get_member_attendance'] = 'week/get_member_attendance';
$route['week/get_attendance_data'] = 'week/get_attendance_data';
$route['week/add_member'] = 'week/add_member';
$route['week/save_member_info'] = 'week/save_member_info';
$route['week/save_memo'] = 'week/save_memo';

// Week 컨트롤러 관련 라우팅
$route['week'] = 'week';
$route['week'] = 'week/index';
$route['week/(:any)'] = 'week/index/$1';
$route['week/(:any)/(:num)'] = 'week/index/$1/$2';
$route['week/(:any)/(:num)/(:num)'] = 'week/index/$1/$2/$3';

// 직접 group_code/year/week 형식으로 접근하는 경우도 처리
$route['(:any)/(:num)/(:num)'] = 'week/index/$1/$2/$3';


// Org 컨트롤러 관련 라우팅 추가
$route['org/update_org_info'] = 'org/update_org_info';
$route['org/upload_org_icon'] = 'org/upload_org_icon';
$route['org/delegate_admin'] = 'org/delegate_admin';
$route['org/get_org_detail'] = 'org/get_org_detail';

// Detail_field 컨트롤러
$route['detail_field'] = 'detail_field/index';
$route['detail_field/index'] = 'detail_field/index';
$route['detail_field/add_field'] = 'detail_field/add_field';
$route['detail_field/update_field'] = 'detail_field/update_field';
$route['detail_field/delete_field'] = 'detail_field/delete_field';
$route['detail_field/toggle_field'] = 'detail_field/toggle_field';
$route['detail_field/update_orders'] = 'detail_field/update_orders';


// Attendance_setting 컨트롤러 관련 라우팅 추가
$route['attendance_setting'] = 'attendance_setting/index';
$route['attendance_setting/index'] = 'attendance_setting/index';
$route['attendance_setting/add_attendance_type'] = 'attendance_setting/add_attendance_type';
$route['attendance_setting/update_attendance_type'] = 'attendance_setting/update_attendance_type';
$route['attendance_setting/delete_attendance_type'] = 'attendance_setting/delete_attendance_type';
$route['attendance_setting/update_orders'] = 'attendance_setting/update_orders';


// 관리자 페이지 라우팅
$route['mng'] = 'mng/mng_org';
$route['mng/mng_org'] = 'mng/mng_org';
$route['mng/mng_org/(:any)'] = 'mng/mng_org/$1';
