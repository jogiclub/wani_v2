<?php
/**
 * 파일 위치: application/config/menu_constants.php
 * 역할: 시스템 메뉴 설정 파일
 */

if (!defined('BASEPATH'))
	exit('No direct script access allowed');

// 시스템 메뉴 정의
$config['system_menus'] = array(
	'OVERVIEW' => array(
		'name' => '대시보드',
		'key' => 'OVERVIEW',
		'url' => 'dashboard',
		'icon' => 'bi bi-file-earmark-ruled',
		'level' => 0
	),
	'MEMBER_MANAGEMENT' => array(
		'name' => '회원관리',
		'key' => 'MEMBER_MANAGEMENT',
		'url' => 'member',
		'icon' => 'bi bi-people',
		'level' => 1
	),
	'ATTENDANCE_MANAGEMENT' => array(
		'name' => '출석관리',
		'key' => 'ATTENDANCE_MANAGEMENT',
		'url' => 'attendance',
		'icon' => 'bi bi-clipboard-check',
		'level' => 1
	),
	'ATTENDANCE_BOARD' => array(
		'name' => 'QR출석',
		'key' => 'ATTENDANCE_BOARD',
		'url' => 'qrcheck',
		'icon' => 'bi bi-person-check',
		'level' => 1
	),
	'WEEKLY_STATISTICS' => array(
		'name' => '주별통계',
		'key' => 'WEEKLY_STATISTICS',
		'url' => 'statistics/weekly',
		'icon' => 'bi bi-graph-up-arrow',
		'level' => 1
	),
	'MEMBER_STATISTICS' => array(
		'name' => '회원별통계',
		'key' => 'MEMBER_STATISTICS',
		'url' => 'statistics/member',
		'icon' => 'bi bi-clipboard-data',
		'level' => 1
	),
	'ORG_SETTING' => array(
		'name' => '조직설정',
		'key' => 'ORG_SETTING',
		'url' => 'org',
		'icon' => 'bi bi-building-gear',
		'level' => 9
	),
	'GROUP_SETTING' => array(
		'name' => '그룹설정',
		'key' => 'GROUP_SETTING',
		'url' => 'member_area',
		'icon' => 'bi bi-diagram-3',
		'level' => 9
	),
	'DETAIL_FIELD_SETTING' => array(
		'name' => '상세필드설정',
		'key' => 'DETAIL_FIELD_SETTING',
		'url' => 'detail_field',
		'icon' => 'bi bi-input-cursor-text',
		'level' => 9
	),
	'ATTENDANCE_SETTING' => array(
		'name' => '출석설정',
		'key' => 'ATTENDANCE_SETTING',
		'url' => 'attendance_setting',
		'icon' => 'bi bi-sliders2-vertical',
		'level' => 9
	),
	'USER_MANAGEMENT' => array(
		'name' => '사용자관리',
		'key' => 'USER_MANAGEMENT',
		'url' => 'user_management',
		'icon' => 'bi bi-person-video',
		'level' => 9
	)
);

/**
 * 메뉴 카테고리별 분류
 */
$config['menu_categories'] = array(
	'OVERVIEW' => array('OVERVIEW'),
	'MEMBER' => array('MEMBER_MANAGEMENT', 'ATTENDANCE_MANAGEMENT', 'ATTENDANCE_BOARD'),
	'STATICS' => array('WEEKLY_STATISTICS', 'MEMBER_STATISTICS'),
	'SETTING' => array('ORG_SETTING', 'GROUP_SETTING', 'DETAIL_FIELD_SETTING', 'ATTENDANCE_SETTING', 'USER_MANAGEMENT')
);
