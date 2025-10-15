<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Dashboard extends My_Controller
{

	public function __construct()
	{
		parent::__construct();
		$this->load->library('session');
		$this->load->helper('url');
	}



	public function index()
	{
		if (!$this->session->userdata('user_id')) {
			redirect('login/index');
			return;
		}

		$user_id = $this->session->userdata('user_id');

		// 헤더 데이터 준비
		$header_data = $this->prepare_header_data();
		$data = $header_data;

		// POST로 조직 변경 요청 처리
		$this->handle_org_change($data);

		$currentOrgId = $data['current_org']['org_id'];

		// 사용자가 접근 가능한 메뉴 목록 가져오기
		$data['accessible_menus'] = $this->get_accessible_menus($user_id, $currentOrgId);

		$this->load->view('dashboard', $data);
	}

	/**
	 * 사용자가 접근 가능한 메뉴 목록 조회
	 */
	private function get_accessible_menus($user_id, $org_id)
	{
		$master_yn = $this->session->userdata('master_yn');

		// 메뉴 헬퍼 로드
		$this->load->helper('menu');
		$system_menus = get_system_menus();

		// User_management_model 로드
		$this->load->model('User_management_model');

		// 현재 조직에서의 사용자 권한 레벨
		$user_level = $this->User_management_model->get_org_user_level($user_id, $org_id);

		// 마스터이거나 최고관리자인 경우 모든 메뉴 접근 가능
		if ($master_yn === 'Y' || $user_level >= 10) {
			return $this->format_menu_list($system_menus);
		}

		// 사용자의 관리 메뉴 조회
		$user_managed_menus = $this->User_management_model->get_user_managed_menus($user_id);

		// 관리 메뉴가 없으면 빈 배열 반환
		if (empty($user_managed_menus)) {
			return array();
		}

		// 접근 가능한 메뉴만 필터링
		$accessible_menus = array();
		foreach ($system_menus as $menu_key => $menu_info) {
			if (in_array($menu_key, $user_managed_menus)) {
				$accessible_menus[$menu_key] = $menu_info;
			}
		}

		return $this->format_menu_list($accessible_menus);
	}

	/**
	 * 메뉴 목록을 카테고리별로 포맷팅
	 */
	private function format_menu_list($menus)
	{
		$this->load->helper('menu');
		$categories = get_menu_categories();

		$formatted_menus = array();

		foreach ($categories as $category_name => $menu_keys) {
			$category_menus = array();

			foreach ($menu_keys as $menu_key) {
				if (isset($menus[$menu_key])) {
					$category_menus[] = array(
						'key' => $menu_key,
						'name' => $menus[$menu_key]['name'],
						'url' => $menus[$menu_key]['url'],
						'icon' => $menus[$menu_key]['icon']
					);
				}
			}

			if (!empty($category_menus)) {
				$formatted_menus[$category_name] = $category_menus;
			}
		}

		return $formatted_menus;
	}

	/**
	 * 회원현황 통계 조회 (AJAX)
	 */
	public function get_member_stats()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$org_id = $this->input->post('org_id');

		if (!$org_id) {
			echo json_encode(array(
				'success' => false,
				'message' => '조직 ID가 필요합니다.'
			));
			return;
		}

		try {
			$this->load->model('Member_model');
			$weekly_new_members = $this->Member_model->get_weekly_new_members($org_id);

			echo json_encode(array(
				'success' => true,
				'data' => $weekly_new_members
			));
		} catch (Exception $e) {
			log_message('error', 'Member stats error: ' . $e->getMessage());
			echo json_encode(array(
				'success' => false,
				'message' => '회원현황 통계 조회 중 오류가 발생했습니다.'
			));
		}
	}

	/**
	 * 출석현황 통계 조회 (AJAX)
	 */
	public function get_attendance_stats()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$org_id = $this->input->post('org_id');

		if (!$org_id) {
			echo json_encode(array(
				'success' => false,
				'message' => '조직 ID가 필요합니다.'
			));
			return;
		}

		try {
			$this->load->model('Attendance_model');
			$attendance_stats = $this->Attendance_model->get_weekly_attendance_stats_by_type($org_id);

			echo json_encode(array(
				'success' => true,
				'data' => $attendance_stats
			));
		} catch (Exception $e) {
			log_message('error', 'Attendance stats error: ' . $e->getMessage());
			echo json_encode(array(
				'success' => false,
				'message' => '출석현황 통계 조회 중 오류가 발생했습니다.'
			));
		}
	}

	/**
	 * 타임라인현황 통계 조회 (AJAX)
	 */
	public function get_timeline_stats()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$org_id = $this->input->post('org_id');

		if (!$org_id) {
			echo json_encode(array(
				'success' => false,
				'message' => '조직 ID가 필요합니다.'
			));
			return;
		}

		try {
			$this->load->model('Timeline_model');
			$timeline_stats = $this->Timeline_model->get_weekly_timeline_stats_by_type($org_id);

			echo json_encode(array(
				'success' => true,
				'data' => $timeline_stats
			));
		} catch (Exception $e) {
			log_message('error', 'Timeline stats error: ' . $e->getMessage());
			echo json_encode(array(
				'success' => false,
				'message' => '타임라인현황 통계 조회 중 오류가 발생했습니다.'
			));
		}
	}

	/**
	 * 메모현황 통계 조회 (AJAX)
	 */
	public function get_memo_stats()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$org_id = $this->input->post('org_id');

		if (!$org_id) {
			echo json_encode(array(
				'success' => false,
				'message' => '조직 ID가 필요합니다.'
			));
			return;
		}

		try {
			$this->load->model('Memo_model');
			$memo_stats = $this->Memo_model->get_weekly_memo_stats_by_type($org_id);

			echo json_encode(array(
				'success' => true,
				'data' => $memo_stats
			));
		} catch (Exception $e) {
			log_message('error', 'Memo stats error: ' . $e->getMessage());
			echo json_encode(array(
				'success' => false,
				'message' => '메모현황 통계 조회 중 오류가 발생했습니다.'
			));
		}
	}

}
