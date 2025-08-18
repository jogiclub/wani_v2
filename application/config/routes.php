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
$route['default_controller'] = 'main';
$route['404_override'] = '';
$route['translate_uri_dashes'] = FALSE;

$route['login/index'] = 'login/index';
$route['login/google_login'] = 'login/google_login';

$route['mypage/add_group'] = 'mypage/add_group';
$route['mypage/get_groups'] = 'mypage/get_groups';
$route['mypage/delete_group'] = 'mypage/delete_group';
$route['mypage/update_del_yn'] = 'mypage/update_del_yn';
$route['mypage/add_attendance_type'] = 'mypage/add_attendance_type';
$route['mypage/add_attendance_type_category'] = 'mypage/add_attendance_type_category';




$route['main/profile'] = 'main/profile';
$route['main/logout'] = 'main/logout';
$route['main/get_members'] = 'main/get_members';
$route['main/save_attendance'] = 'main/save_attendance';
$route['main/get_attendance_types'] = 'main/get_attendance_types';
$route['main/get_member_attendance'] = 'main/get_member_attendance';
$route['main/get_attendance_data'] = 'main/get_attendance_data';
$route['main/add_member '] = 'main/add_member ';


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