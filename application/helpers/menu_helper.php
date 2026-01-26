<?php
/**
 * 파일 위치: application/helpers/menu_helper.php
 * 역할: 메뉴 관련 헬퍼 함수
 */

if (!function_exists('get_system_menus')) {
	/**
	 * 시스템 메뉴 배열 반환
	 * - menu_class: 사이드바 메뉴 클래스 (menu-XX 형식)
	 * - level: 메뉴 접근 최소 레벨 (0: 모든 사용자, 1: 일반 권한, 9: 설정 권한)
	 */
	function get_system_menus() {
		return array(
			'OVERVIEW' => array(
				'name' => '대시보드',
				'key' => 'OVERVIEW',
				'url' => 'dashboard',
				'icon' => 'bi bi-file-earmark-ruled',
				'level' => 0,
				'menu_class' => 'menu-11'
			),
			'MEMBER_MANAGEMENT' => array(
				'name' => '회원관리',
				'key' => 'MEMBER_MANAGEMENT',
				'url' => 'member',
				'icon' => 'bi bi-person-vcard',
				'level' => 1,
				'menu_class' => 'menu-21'
			),
			'ATTENDANCE_MANAGEMENT' => array(
				'name' => '출석관리',
				'key' => 'ATTENDANCE_MANAGEMENT',
				'url' => 'attendance',
				'icon' => 'bi bi-clipboard-check',
				'level' => 1,
				'menu_class' => 'menu-22'
			),
			'ATTENDANCE_BOARD' => array(
				'name' => 'QR출석',
				'key' => 'ATTENDANCE_BOARD',
				'url' => 'qrcheck',
				'icon' => 'bi bi-qr-code-scan',
				'level' => 1,
				'menu_class' => 'menu-23'
			),
			'EDU_MANAGEMENT' => array(
				'name' => '교육관리',
				'key' => 'EDU_MANAGEMENT',
				'url' => 'edu',
				'icon' => 'bi bi-mortarboard',
				'level' => 1,
				'menu_class' => 'menu-26'
			),
			'MOIM_MANAGEMENT' => array(
				'name' => '소모임',
				'key' => 'MOIM_MANAGEMENT',
				'url' => 'moim',
				'icon' => 'bi bi-people',
				'level' => 1,
				'menu_class' => 'menu-27'
			),
			'TIMELINE_MANAGEMENT' => array(
				'name' => '타임라인관리',
				'key' => 'TIMELINE_MANAGEMENT',
				'url' => 'timeline',
				'icon' => 'bi bi-clock-history',
				'level' => 1,
				'menu_class' => 'menu-24'
			),
			'MEMO_MANAGEMENT' => array(
				'name' => '메모관리',
				'key' => 'MEMO_MANAGEMENT',
				'url' => 'memos',
				'icon' => 'bi bi-journal-bookmark',
				'level' => 1,
				'menu_class' => 'menu-25'
			),
			'ACCOUNT_MANAGEMENT' => array(
				'name' => '계정관리',
				'key' => 'ACCOUNT_MANAGEMENT',
				'url' => 'account',
				'icon' => 'bi bi-tags',
				'level' => 1,
				'menu_class' => 'menu-51'
			),
			'HOMEPAGE_BASIC' => array(
				'name' => '홈페이지 기본설정',
				'key' => 'HOMEPAGE_BASIC',
				'url' => 'homepage_setting',
				'icon' => 'bi bi-house-gear',
				'level' => 1,
				'menu_class' => 'menu-31'
			),
			'HOMEPAGE_MENU' => array(
				'name' => '홈페이지 메뉴설정',
				'key' => 'HOMEPAGE_MENU',
				'url' => 'homepage_menu',
				'icon' => 'bi bi-view-stacked',
				'level' => 1,
				'menu_class' => 'menu-32'
			),

			'ORG_SETTING' => array(
				'name' => '조직설정',
				'key' => 'ORG_SETTING',
				'url' => 'org',
				'icon' => 'bi bi-building-gear',
				'level' => 9,
				'menu_class' => 'menu-41'
			),
			'GROUP_SETTING' => array(
				'name' => '그룹설정',
				'key' => 'GROUP_SETTING',
				'url' => 'group_setting',
				'icon' => 'bi bi-diagram-3',
				'level' => 9,
				'menu_class' => 'menu-42'
			),
			'DETAIL_FIELD_SETTING' => array(
				'name' => '상세필드설정',
				'key' => 'DETAIL_FIELD_SETTING',
				'url' => 'detail_field',
				'icon' => 'bi bi-input-cursor-text',
				'level' => 9,
				'menu_class' => 'menu-43'
			),
			'ATTENDANCE_SETTING' => array(
				'name' => '출석설정',
				'key' => 'ATTENDANCE_SETTING',
				'url' => 'attendance_setting',
				'icon' => 'bi bi-sliders2-vertical',
				'level' => 9,
				'menu_class' => 'menu-44'
			),
			'USER_MANAGEMENT' => array(
				'name' => '사용자관리',
				'key' => 'USER_MANAGEMENT',
				'url' => 'user_management',
				'icon' => 'bi bi-person-video',
				'level' => 9,
				'menu_class' => 'menu-45'
			),
			'FEE_MANAGEMENT' => array(
				'name' => '요금관리',
				'key' => 'FEE_MANAGEMENT',
				'url' => 'fee_management',
				'icon' => 'bi bi-person-video',
				'level' => 9,
				'menu_class' => 'menu-46'
			)
		);
	}
}

if (!function_exists('get_menu_categories')) {
	/**
	 * 메뉴 카테고리 반환
	 * - name: 카테고리 표시명
	 * - menus: 해당 카테고리에 속한 메뉴 키 배열
	 * - show_always: true인 경우 권한 체크 없이 항상 표시
	 */
	function get_menu_categories() {
		return array(
			'OVERVIEW' => array(
				'name' => 'OVERVIEW',
				'menus' => array('OVERVIEW'),
				'show_always' => true
			),
			'MEMBER' => array(
				'name' => 'MEMBER',
				'menus' => array(
					'MEMBER_MANAGEMENT',
					'ATTENDANCE_MANAGEMENT',
					'ATTENDANCE_BOARD',
					'EDU_MANAGEMENT',
					'MOIM_MANAGEMENT',
					'TIMELINE_MANAGEMENT',
					'MEMO_MANAGEMENT'
				),
				'show_always' => false
			),
			'HOMEPAGE' => array(
				'name' => 'HOMEPAGE',
				'menus' => array(
					'HOMEPAGE_BASIC',
					'HOMEPAGE_MENU'
				),
				'show_always' => true
			),
			'CASH' => array(
				'name' => 'CASH',
				'menus' => array('ACCOUNT_MANAGEMENT'),
				'show_always' => false
			),
			'SETTING' => array(
				'name' => 'SETTING',
				'menus' => array(
					'ORG_SETTING',
					'GROUP_SETTING',
					'DETAIL_FIELD_SETTING',
					'ATTENDANCE_SETTING',
					'USER_MANAGEMENT'
				),
				'show_always' => false
			)
		);
	}
}

if (!function_exists('can_access_menu')) {
	/**
	 * 메뉴 접근 권한 확인 함수
	 *
	 * @param string $menu_key 메뉴 키
	 * @param array $user_managed_menus 사용자 관리 메뉴 배열
	 * @param string $is_master 마스터 여부 ('Y' / 'N')
	 * @param int $user_level 사용자 레벨
	 * @return bool 접근 가능 여부
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
}

if (!function_exists('check_category_visible')) {
	/**
	 * 카테고리 표시 여부 확인
	 *
	 * @param array $category 카테고리 정보
	 * @param array $user_managed_menus 사용자 관리 메뉴 배열
	 * @param string $is_master 마스터 여부
	 * @param int $user_level 사용자 레벨
	 * @return bool 카테고리 표시 여부
	 */
	function check_category_visible($category, $user_managed_menus, $is_master, $user_level)
	{
		// 항상 표시하는 카테고리
		if (!empty($category['show_always'])) {
			return true;
		}

		// 최고관리자이거나 조직 레벨 10인 경우
		if ($is_master === 'Y' || $user_level >= 10) {
			return true;
		}

		// 카테고리 내 메뉴 중 하나라도 접근 가능하면 표시
		foreach ($category['menus'] as $menu_key) {
			if (can_access_menu($menu_key, $user_managed_menus, $is_master, $user_level)) {
				return true;
			}
		}

		return false;
	}
}
