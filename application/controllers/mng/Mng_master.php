<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Mng_master extends CI_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->library('session');
		$this->load->helper('url');
		$this->load->model('Org_model');
		$this->load->model('Org_category_model');
		$this->load->model('User_model');

		// 로그인 확인
		if (!$this->session->userdata('user_id')) {
			redirect('login');
		}

		// 마스터 권한 확인
		if ($this->session->userdata('master_yn') !== 'Y') {
			show_error('접근 권한이 없습니다.', 403);
		}

		// 메뉴 접근 권한 확인
		$this->check_menu_access('mng_master');
	}

	/**
	 * 마스터 관리 메인 페이지
	 */
	public function index()
	{
		$data['user'] = $this->User_model->get_user_by_id($this->session->userdata('user_id'));
		$this->load->view('mng/mng_master', $data);
	}

	/**
	 * 메뉴 접근 권한 확인
	 */
	private function check_menu_access($menu_key)
	{
		$user_id = $this->session->userdata('user_id');
		$user = $this->User_model->get_user_by_id($user_id);

		$user_managed_menus = array();
		if (!empty($user['managed_menus'])) {
			$user_managed_menus = json_decode($user['managed_menus'], true);
			if (!is_array($user_managed_menus)) {
				$user_managed_menus = array();
			}
		}

		// managed_menus가 비어있으면 모든 메뉴 접근 가능
		if (empty($user_managed_menus)) {
			return;
		}

		// 접근 권한이 없는 경우
		if (!in_array($menu_key, $user_managed_menus)) {
			show_error('해당 메뉴에 접근할 권한이 없습니다.', 403);
		}
	}

	/**
	 * 마스터 사용자 목록 조회 (AJAX)
	 */
	public function get_master_list()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		try {
			$masters = $this->User_model->get_master_users();

			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array(
				'success' => true,
				'data' => $masters
			));
		} catch (Exception $e) {
			log_message('error', '마스터 목록 조회 오류: ' . $e->getMessage());
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array(
				'success' => false,
				'message' => '마스터 목록 조회 중 오류가 발생했습니다.'
			));
		}
	}

	/**
	 * 마스터 사용자 상세 정보 조회 (AJAX)
	 */
	public function get_master_detail()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$user_id = $this->input->get('user_id');

		if (!$user_id) {
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array('success' => false, 'message' => '사용자 ID가 누락되었습니다.'));
			return;
		}

		try {
			$user = $this->User_model->get_user_by_id($user_id);

			if (!$user) {
				header('Content-Type: application/json; charset=utf-8');
				echo json_encode(array('success' => false, 'message' => '사용자 정보를 찾을 수 없습니다.'));
				return;
			}

			// master_managed_menus와 master_managed_category JSON 파싱
			if (!empty($user['master_managed_menus'])) {
				$user['master_managed_menus'] = json_decode($user['master_managed_menus'], true);
			} else {
				$user['master_managed_menus'] = array();
			}

			if (!empty($user['master_managed_category'])) {
				$user['master_managed_category'] = json_decode($user['master_managed_category'], true);
			} else {
				$user['master_managed_category'] = array();
			}

			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array(
				'success' => true,
				'data' => $user
			));
		} catch (Exception $e) {
			log_message('error', '마스터 상세 조회 오류: ' . $e->getMessage());
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array('success' => false, 'message' => '서버 오류가 발생했습니다.'));
		}
	}

	/**
	 * 마스터 사용자 정보 업데이트 (AJAX)
	 */
	public function update_master()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$user_id = $this->input->post('user_id');

		if (!$user_id) {
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array('success' => false, 'message' => '사용자 ID가 누락되었습니다.'));
			return;
		}

		$data = array(
			'user_name' => trim($this->input->post('user_name')),
			'user_mail' => trim($this->input->post('user_mail')),
			'user_hp' => trim($this->input->post('user_hp'))
		);

		// master_managed_menus 처리
		$master_managed_menus = $this->input->post('master_managed_menus');
		if ($master_managed_menus !== null) {
			if (is_string($master_managed_menus)) {
				$master_managed_menus = json_decode($master_managed_menus, true);
			}
			$data['master_managed_menus'] = is_array($master_managed_menus) ? $master_managed_menus : array();
		}

		// master_managed_category 처리
		$master_managed_category = $this->input->post('master_managed_category');
		if ($master_managed_category !== null) {
			if (is_string($master_managed_category)) {
				$master_managed_category = json_decode($master_managed_category, true);
			}
			$data['master_managed_category'] = is_array($master_managed_category) ? $master_managed_category : array();
		}

		try {
			$result = $this->User_model->update_master_user($user_id, $data);

			if ($result) {
				header('Content-Type: application/json; charset=utf-8');
				echo json_encode(array(
					'success' => true,
					'message' => '마스터 정보가 성공적으로 업데이트되었습니다.'
				));
			} else {
				header('Content-Type: application/json; charset=utf-8');
				echo json_encode(array(
					'success' => false,
					'message' => '마스터 정보 업데이트에 실패했습니다.'
				));
			}
		} catch (Exception $e) {
			log_message('error', '마스터 업데이트 오류: ' . $e->getMessage());
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array(
				'success' => false,
				'message' => '마스터 정보 업데이트 중 오류가 발생했습니다.'
			));
		}
	}

	/**
	 * 전체 카테고리 목록 조회 (AJAX)
	 */
	public function get_all_categories()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		try {
			$categories = $this->Org_category_model->get_categories_for_select();

			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array(
				'success' => true,
				'data' => $categories
			));
		} catch (Exception $e) {
			log_message('error', '카테고리 목록 조회 오류: ' . $e->getMessage());
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array(
				'success' => false,
				'message' => '카테고리 목록 조회 중 오류가 발생했습니다.'
			));
		}
	}



	/**
	 * 최상위 카테고리 목록 조회 (AJAX)
	 */
	public function get_top_categories()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		try {
			$categories = $this->Org_category_model->get_top_level_categories();

			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array(
				'success' => true,
				'data' => $categories
			));
		} catch (Exception $e) {
			log_message('error', '최상위 카테고리 조회 오류: ' . $e->getMessage());
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array(
				'success' => false,
				'message' => '카테고리 목록 조회 중 오류가 발생했습니다.'
			));
		}
	}


	/**
	 * 파일 위치: application/controllers/mng/Mng_master.php
	 * 역할: 시스템 메뉴 목록 조회 API
	 */
	public function get_system_menus()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		try {
			// 메뉴 상수 파일 로드
			$this->config->load('menu_constants');
			$system_menus = $this->config->item('system_menus');

			if (!$system_menus) {
				throw new Exception('시스템 메뉴를 불러올 수 없습니다.');
			}

			// 메뉴 데이터를 배열로 변환
			$menus = array();
			foreach ($system_menus as $menu_key => $menu_info) {
				$menus[] = array(
					'key' => $menu_key,
					'name' => $menu_info['name'],
					'icon' => $menu_info['icon'],
					'level' => $menu_info['level']
				);
			}

			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array(
				'success' => true,
				'data' => $menus
			));
		} catch (Exception $e) {
			log_message('error', '시스템 메뉴 조회 오류: ' . $e->getMessage());
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array(
				'success' => false,
				'message' => '시스템 메뉴 조회 중 오류가 발생했습니다.'
			));
		}
	}


}
