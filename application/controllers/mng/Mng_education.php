<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Mng_education extends CI_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->library('session');
		$this->load->helper('url');
		$this->load->model('Education_model');
		$this->load->model('Org_model');
		$this->load->model('Org_category_model');
		$this->load->model('User_model');

		// 마스터 권한 확인
		if ($this->session->userdata('master_yn') !== 'Y') {
			show_error('접근 권한이 없습니다.', 403);
		}

		// 메뉴 접근 권한 확인 (조직관리 권한으로 통합)
		$this->check_menu_access('mng_org');
	}

	/**
	 * 메뉴 접근 권한 확인
	 */
	private function check_menu_access($menu_key)
	{
		$user_id = $this->session->userdata('user_id');
		$user = $this->User_model->get_user_by_id($user_id);

		$master_managed_menus = array();
		if (!empty($user['master_managed_menus'])) {
			$master_managed_menus = json_decode($user['master_managed_menus'], true);
			if (!is_array($master_managed_menus)) {
				$master_managed_menus = array();
			}
		}

		// master_managed_menus가 비어있으면 모든 메뉴 접근 가능
		if (empty($master_managed_menus)) {
			return;
		}

		// 접근 권한이 없는 경우
		if (!in_array($menu_key, $master_managed_menus)) {
			show_error('해당 메뉴에 접근할 권한이 없습니다.', 403);
		}
	}

	/**
	 * 관리자 양육관리 메인 페이지
	 */
	public function index()
	{
		$data['user'] = $this->User_model->get_user_by_id($this->session->userdata('user_id'));
		$data['total_edu_count'] = $this->Education_model->get_total_edu_count_for_all();
		$this->load->view('mng/mng_education', $data);
	}

	/**
	 * 마스터가 볼 수 있는 카테고리 목록 가져오기
	 */
	private function get_visible_categories()
	{
		$user_id = $this->session->userdata('user_id');
		$user = $this->User_model->get_user_by_id($user_id);

		$visible_categories = array();
		if (!empty($user['master_managed_category'])) {
			$master_managed_category = json_decode($user['master_managed_category'], true);
			if (is_array($master_managed_category) && !empty($master_managed_category)) {
				$visible_categories = $master_managed_category;
			}
		}

		return $visible_categories;
	}

	/**
	 * 카테고리 + 조직 트리 데이터 조회 (AJAX)
	 * 각 조직에 양육 개수 표시
	 */
	public function get_category_org_tree()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$visible_categories = $this->get_visible_categories();

		// 카테고리 트리 조회 (권한 적용)
		if (!empty($visible_categories)) {
			$category_tree = $this->Org_category_model->get_category_tree_for_master($visible_categories);
		} else {
			$category_tree = $this->Org_category_model->get_category_tree();
		}

		// 카테고리 트리에 조직 노드 추가
		$tree_data = $this->add_org_nodes_to_tree($category_tree, $visible_categories);

		header('Content-Type: application/json; charset=utf-8');
		echo json_encode($tree_data, JSON_UNESCAPED_UNICODE);
	}

	/**
	 * 카테고리 트리에 조직 노드 추가
	 */
	private function add_org_nodes_to_tree($category_tree, $visible_categories)
	{
		$result = array();

		foreach ($category_tree as $node) {
			$new_node = $node;

			if (isset($node['data']) && $node['data']['type'] === 'category') {
				// 해당 카테고리에 직접 속한 조직 조회
				$category_idx = $node['data']['category_idx'];
				$orgs = $this->Education_model->get_orgs_by_category_direct($category_idx);

				// 기존 children이 있으면 재귀 처리
				$children = array();
				if (!empty($node['children'])) {
					$children = $this->add_org_nodes_to_tree($node['children'], $visible_categories);
				}

				// 조직 노드 추가
				foreach ($orgs as $org) {
					$edu_count = $this->Education_model->get_org_edu_count($org['org_id']);
					$children[] = array(
						'key' => 'org_' . $org['org_id'],
						'title' => $org['org_name'] . ' (' . $edu_count . '개)',
						'folder' => false,
						'icon' => 'bi bi-building',
						'data' => array(
							'type' => 'org',
							'org_id' => $org['org_id'],
							'org_name' => $org['org_name'],
							'edu_count' => $edu_count
						)
					);
				}

				// 카테고리의 양육 수 재계산 (하위 포함)
				$category_edu_count = $this->Education_model->get_category_edu_count($category_idx);
				$new_node['title'] = $node['data']['category_name'] . ' (' . $category_edu_count . '개)';
				$new_node['children'] = $children;

			} else if (isset($node['data']) && $node['data']['type'] === 'uncategorized') {
				// 미분류 조직 처리
				$orgs = $this->Education_model->get_uncategorized_orgs();
				$children = array();

				foreach ($orgs as $org) {
					$edu_count = $this->Education_model->get_org_edu_count($org['org_id']);
					$children[] = array(
						'key' => 'org_' . $org['org_id'],
						'title' => $org['org_name'] . ' (' . $edu_count . '개)',
						'folder' => false,
						'icon' => 'bi bi-building',
						'data' => array(
							'type' => 'org',
							'org_id' => $org['org_id'],
							'org_name' => $org['org_name'],
							'edu_count' => $edu_count
						)
					);
				}

				$uncategorized_edu_count = $this->Education_model->get_uncategorized_edu_count();
				$new_node['title'] = '미분류 (' . $uncategorized_edu_count . '개)';
				$new_node['folder'] = true;
				$new_node['children'] = $children;
			}

			$result[] = $new_node;
		}

		return $result;
	}

	/**
	 * 전체 양육 수 조회 (AJAX)
	 */
	public function get_total_edu_count()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$visible_categories = $this->get_visible_categories();

		if (!empty($visible_categories)) {
			$category_ids = $this->Org_category_model->get_category_with_descendants_public($visible_categories);
			$total_count = $this->Education_model->get_total_edu_count_by_categories($category_ids);
		} else {
			$total_count = $this->Education_model->get_total_edu_count_for_all();
		}

		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(array('total_count' => $total_count));
	}

	/**
	 * 양육 목록 조회 (AJAX)
	 * type: 'all' (전체), 'category' (카테고리별), 'org' (조직별)
	 */
	public function get_edu_list()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$type = $this->input->post('type');
		$org_id = $this->input->post('org_id');
		$category_idx = $this->input->post('category_idx');

		$visible_categories = $this->get_visible_categories();

		$edu_list = array();

		if ($type === 'org' && $org_id) {
			// 특정 조직의 양육 목록
			$edu_list = $this->Education_model->get_edu_list_by_org($org_id);
		} else if ($type === 'category' && $category_idx) {
			// 카테고리별 양육 목록 (하위 카테고리 포함)
			$category_ids = $this->Org_category_model->get_category_with_descendants_public(array($category_idx));

			if (!empty($category_ids)) {
				$edu_list = $this->Education_model->get_edu_list_by_categories($category_ids);
			}
		} else {
			// 전체 양육 목록
			if (!empty($visible_categories)) {
				$category_ids = $this->Org_category_model->get_category_with_descendants_public($visible_categories);
				if (!empty($category_ids)) {
					$edu_list = $this->Education_model->get_edu_list_by_categories($category_ids);
				}
			} else {
				$edu_list = $this->Education_model->get_all_edu_list();
			}
		}

		echo json_encode(array(
			'success' => true,
			'data' => $edu_list,
			'total_count' => count($edu_list)
		));
	}

	/**
	 * 양육 상세 조회 API
	 */
	public function get_edu_detail()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$edu_idx = $this->input->post('edu_idx');

		if (!$edu_idx) {
			echo json_encode(array('success' => false, 'message' => '양육 정보가 필요합니다.'));
			return;
		}

		$edu_detail = $this->Education_model->get_edu_by_idx($edu_idx);

		if (!$edu_detail) {
			echo json_encode(array('success' => false, 'message' => '양육 정보를 찾을 수 없습니다.'));
			return;
		}

		echo json_encode(array(
			'success' => true,
			'data' => $edu_detail
		));
	}

	/**
	 * 신청자 목록 조회
	 */
	public function get_applicant_list()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$edu_idx = $this->input->post('edu_idx');

		if (!$edu_idx) {
			echo json_encode(array('success' => false, 'message' => '양육 정보가 필요합니다.'));
			return;
		}

		$applicants = $this->Education_model->get_applicants_by_edu($edu_idx);

		echo json_encode(array(
			'success' => true,
			'data' => $applicants,
			'total_count' => count($applicants)
		));
	}

	/**
	 * 신청자 상태 업데이트
	 */
	public function update_applicant_status()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$applicant_idx = $this->input->post('applicant_idx');
		$status = $this->input->post('status');

		if (!$applicant_idx || !$status) {
			echo json_encode(array('success' => false, 'message' => '필수 정보가 누락되었습니다.'));
			return;
		}

		$update_data = array(
			'status' => $status,
			'modi_date' => date('Y-m-d H:i:s')
		);

		if ($this->Education_model->update_applicant($applicant_idx, $update_data)) {
			echo json_encode(array(
				'success' => true,
				'message' => '신청자 상태가 업데이트되었습니다.'
			));
		} else {
			echo json_encode(array('success' => false, 'message' => '업데이트 중 오류가 발생했습니다.'));
		}
	}
}
