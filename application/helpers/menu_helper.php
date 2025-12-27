<?php
/**
 * 파일 위치: application/helpers/menu_helper.php
 * 역할: 메뉴 관련 헬퍼 함수
 */

if (!function_exists('get_system_menus')) {
	/**
	 * 시스템 메뉴 배열 반환
	 */
	function get_system_menus() {
		return array(
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
			'TIMELINE_MANAGEMENT' => array(
				'name' => '타임라인관리',
				'key' => 'TIMELINE_MANAGEMENT',
				'url' => 'timeline',
				'icon' => 'bi bi-clock-history',
				'level' => 1
			),
			'MEMO_MANAGEMENT' => array(
				'name' => '메모관리',
				'key' => 'MEMO_MANAGEMENT',
				'url' => 'memos',
				'icon' => 'bi bi-journal-bookmark',
				'level' => 1
			),
			'HOMEPAGE_BASIC' => array(
				'name' => '홈페이지 기본 설정',
				'key' => 'HOMEPAGE_BASIC',
				'url' => 'memos',
				'icon' => 'bi bi-house-gear',
				'level' => 1
			),
			'HOMEPAGE_MENU' => array(
				'name' => '홈페이지 메뉴 설정',
				'key' => 'HOMEPAGE_MENU',
				'url' => 'memos',
				'icon' => 'bi bi-view-stacked',
				'level' => 1
			),

			/*'WEEKLY_STATISTICS' => array(
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
			),*/
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
	}
}

if (!function_exists('get_menu_categories')) {
	/**
	 * 메뉴 카테고리 반환
	 */
	function get_menu_categories() {
		return array(
			'OVERVIEW' => array('OVERVIEW'),
			'MEMBER' => array(
				'MEMBER_MANAGEMENT',
				'ATTENDANCE_MANAGEMENT',
				'ATTENDANCE_BOARD',
				'TIMELINE_MANAGEMENT',
				'MEMO_MANAGEMENT'
			),
			'STATICS' => array('WEEKLY_STATISTICS', 'MEMBER_STATISTICS'),
			'SETTING' => array(
				'ORG_SETTING',
				'GROUP_SETTING',
				'DETAIL_FIELD_SETTING',
				'ATTENDANCE_SETTING',
				'USER_MANAGEMENT'
			)
		);
	}
}
