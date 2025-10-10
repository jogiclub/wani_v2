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

			// managed_menus와 managed_areas JSON 파싱
			if (!empty($user['managed_menus'])) {
				$user['managed_menus'] = json_decode($user['managed_menus'], true);
			} else {
				$user['managed_menus'] = array();
			}

			if (!empty($user['managed_areas'])) {
				$user['managed_areas'] = json_decode($user['managed_areas'], true);
			} else {
				$user['managed_areas'] = array();
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

		// managed_menus 처리
		$managed_menus = $this->input->post('managed_menus');
		if ($managed_menus !== null) {
			if (is_string($managed_menus)) {
				$managed_menus = json_decode($managed_menus, true);
			}
			$data['managed_menus'] = is_array($managed_menus) ? $managed_menus : array();
		}

		// managed_areas 처리
		$managed_areas = $this->input->post('managed_areas');
		if ($managed_areas !== null) {
			if (is_string($managed_areas)) {
				$managed_areas = json_decode($managed_areas, true);
			}
			$data['managed_areas'] = is_array($managed_areas) ? $managed_areas : array();
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


}
