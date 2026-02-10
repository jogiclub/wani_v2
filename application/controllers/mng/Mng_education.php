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
				$orgs = $this->get_orgs_by_category_direct($category_idx);

				// 기존 children이 있으면 재귀 처리
				$children = array();
				if (!empty($node['children'])) {
					$children = $this->add_org_nodes_to_tree($node['children'], $visible_categories);
				}

				// 조직 노드 추가
				foreach ($orgs as $org) {
					$edu_count = $this->get_org_edu_count($org['org_id']);
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
				$category_edu_count = $this->get_category_edu_count($category_idx);
				$new_node['title'] = $node['data']['category_name'] . ' (' . $category_edu_count . '개)';
				$new_node['children'] = $children;

			} else if (isset($node['data']) && $node['data']['type'] === 'uncategorized') {
				// 미분류 조직 처리
				$orgs = $this->get_uncategorized_orgs();
				$children = array();

				foreach ($orgs as $org) {
					$edu_count = $this->get_org_edu_count($org['org_id']);
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

				$uncategorized_edu_count = $this->get_uncategorized_edu_count();
				$new_node['title'] = '미분류 (' . $uncategorized_edu_count . '개)';
				$new_node['folder'] = true;
				$new_node['children'] = $children;
			}

			$result[] = $new_node;
		}

		return $result;
	}

	/**
	 * 특정 카테고리에 직접 속한 조직 조회
	 */
	private function get_orgs_by_category_direct($category_idx)
	{
		$this->db->select('org_id, org_name');
		$this->db->from('wb_org');
		$this->db->where('category_idx', $category_idx);
		$this->db->where('del_yn', 'N');
		$this->db->order_by('org_name', 'ASC');
		$query = $this->db->get();
		return $query->result_array();
	}

	/**
	 * 미분류 조직 조회
	 */
	private function get_uncategorized_orgs()
	{
		$this->db->select('org_id, org_name');
		$this->db->from('wb_org');
		$this->db->where('del_yn', 'N');
		$this->db->group_start();
		$this->db->where('category_idx IS NULL');
		$this->db->or_where('category_idx', 0);
		$this->db->group_end();
		$this->db->order_by('org_name', 'ASC');
		$query = $this->db->get();
		return $query->result_array();
	}

	/**
	 * 조직별 양육 수 조회
	 */
	private function get_org_edu_count($org_id)
	{
		$this->db->from('wb_edu');
		$this->db->where('org_id', $org_id);
//		$this->db->where('del_yn', 'N');
		return $this->db->count_all_results();
	}

	/**
	 * 카테고리의 전체 양육 수 조회 (하위 카테고리 포함)
	 */
	private function get_category_edu_count($category_idx)
	{
		$category_ids = $this->Org_category_model->get_category_with_descendants_public(array($category_idx));

		if (empty($category_ids)) {
			return 0;
		}

		$this->db->from('wb_edu e');
		$this->db->join('wb_org o', 'e.org_id = o.org_id');
//		$this->db->where('e.del_yn', 'N');
		$this->db->where('o.del_yn', 'N');
		$this->db->where_in('o.category_idx', $category_ids);

		return $this->db->count_all_results();
	}

	/**
	 * 미분류 조직의 양육 수 조회
	 */
	private function get_uncategorized_edu_count()
	{
		$this->db->from('wb_edu e');
		$this->db->join('wb_org o', 'e.org_id = o.org_id');
		$this->db->where('e.del_yn', 'N');
		$this->db->where('o.del_yn', 'N');
		$this->db->group_start();
		$this->db->where('o.category_idx IS NULL');
		$this->db->or_where('o.category_idx', 0);
		$this->db->group_end();

		return $this->db->count_all_results();
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

			if (empty($category_ids)) {
				$total_count = 0;
			} else {
				$this->db->from('wb_edu e');
				$this->db->join('wb_org o', 'e.org_id = o.org_id');
				$this->db->where('e.del_yn', 'N');
				$this->db->where('o.del_yn', 'N');
				$this->db->where_in('o.category_idx', $category_ids);
				$total_count = $this->db->count_all_results();
			}
		} else {
			$this->db->from('wb_edu e');
			$this->db->join('wb_org o', 'e.org_id = o.org_id');
			$this->db->where('e.del_yn', 'N');
			$this->db->where('o.del_yn', 'N');
			$total_count = $this->db->count_all_results();
		}

		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(array('total_count' => $total_count));
	}

	/**
	 * 양육 목록 조회 (AJAX)
	 */
	public function get_edu_list()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$type = $this->input->post('type');
		$org_id = $this->input->post('org_id');
		$category_idx = $this->input->post('category_idx');

		// 검색 파라미터
		$search_params = array(
			'date' => $this->input->post('date'),
			'days' => $this->input->post('days'),
			'times' => $this->input->post('times'),
			'ages' => $this->input->post('ages'),
			'genders' => $this->input->post('genders'),
			'keyword' => $this->input->post('keyword')
		);

		$visible_categories = $this->get_visible_categories();
		$edu_list = array();

		if ($type === 'org' && $org_id) {
			$edu_list = $this->Education_model->get_edu_list_by_org($org_id, $search_params);
		} else if ($type === 'category' && $category_idx) {
			$category_ids = $this->Org_category_model->get_category_with_descendants_public(array($category_idx));
			if (!empty($category_ids)) {
				$edu_list = $this->get_edu_list_by_categories($category_ids, $search_params);
			}
		} else {
			if (!empty($visible_categories)) {
				$category_ids = $this->Org_category_model->get_category_with_descendants_public($visible_categories);
				if (!empty($category_ids)) {
					$edu_list = $this->get_edu_list_by_categories($category_ids, $search_params);
				}
			} else {
				$edu_list = $this->get_all_edu_list($search_params);
			}
		}

		$edu_list = $this->process_edu_category_names($edu_list);

		echo json_encode(array(
			'success' => true,
			'data' => $edu_list,
			'total_count' => count($edu_list)
		));
	}

	/**
	 * 카테고리별 양육 목록 조회
	 */
	private function get_edu_list_by_categories($category_ids, $search_params = array())
	{
		$this->db->select('e.*, o.org_name');
		$this->db->from('wb_edu e');
		$this->db->join('wb_org o', 'e.org_id = o.org_id');
		$this->db->where('e.del_yn', 'N');
		$this->db->where('o.del_yn', 'N');
		$this->db->where_in('o.category_idx', $category_ids);
		$this->apply_search_filters($search_params);
		$this->db->order_by('e.regi_date', 'DESC');
		$query = $this->db->get();
		return $query->result_array();
	}

	/**
	 * 전체 양육 목록 조회
	 */
	private function get_all_edu_list($search_params = array())
	{
		$this->db->select('e.*, o.org_name');
		$this->db->from('wb_edu e');
		$this->db->join('wb_org o', 'e.org_id = o.org_id');
		$this->db->where('e.del_yn', 'N');
		$this->db->where('o.del_yn', 'N');
		$this->apply_search_filters($search_params);
		$this->db->order_by('e.regi_date', 'DESC');
		$query = $this->db->get();
		return $query->result_array();
	}

	/**
	 * 검색 필터 적용
	 */
	private function apply_search_filters($params)
	{
		if (!empty($params['date'])) {
			$this->db->where('e.edu_start_date <=', $params['date']);
			$this->db->where('e.edu_end_date >=', $params['date']);
		}

		if (!empty($params['days']) && is_array($params['days'])) {
			$day_conditions = array();
			foreach ($params['days'] as $day) {
				$day_conditions[] = "e.edu_days LIKE '%\"" . $this->db->escape_like_str($day) . "\"%'";
			}
			if (!empty($day_conditions)) {
				$this->db->where('(' . implode(' OR ', $day_conditions) . ')');
			}
		}

		if (!empty($params['times']) && is_array($params['times'])) {
			$time_conditions = array();
			foreach ($params['times'] as $time) {
				$time_conditions[] = "e.edu_times LIKE '%\"" . $this->db->escape_like_str($time) . "\"%'";
			}
			if (!empty($time_conditions)) {
				$this->db->where('(' . implode(' OR ', $time_conditions) . ')');
			}
		}

		if (!empty($params['ages']) && is_array($params['ages'])) {
			$this->db->where_in('e.edu_leader_age', $params['ages']);
		}

		if (!empty($params['genders']) && is_array($params['genders'])) {
			$this->db->where_in('e.edu_leader_gender', $params['genders']);
		}

		if (!empty($params['keyword'])) {
			$this->db->group_start();
			$this->db->like('e.edu_name', $params['keyword']);
			$this->db->or_like('e.edu_location', $params['keyword']);
			$this->db->or_like('e.edu_leader', $params['keyword']);
			// 카테고리명 검색은 PHP에서 처리 후 필터링
			$this->db->group_end();
		}
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

    /**
	 * 양육 목록에 카테고리 이름 추가
	 */
	private function process_edu_category_names($edu_list)
	{
		if (empty($edu_list)) {
			return array();
		}

		$org_ids = array_unique(array_column($edu_list, 'org_id'));

		if (empty($org_ids)) {
			foreach ($edu_list as &$edu) {
				$edu['category_name'] = '';
			}
			unset($edu);
			return $edu_list;
		}

		// 각 조직(org_id)별 카테고리 JSON 조회
		$this->db->select('org_id, category_json');
		$this->db->from('wb_edu_category');
		$this->db->where_in('org_id', $org_ids);
		$category_jsons_raw = $this->db->get()->result_array();

		// org_id를 key로, [code => name] 맵을 value로 하는 맵 생성
		$org_category_map = array();
		foreach ($category_jsons_raw as $row) {
			$json_data = json_decode($row['category_json'], true);
			if (is_array($json_data) && isset($json_data['categories'])) {
				$category_lookup = array();
				$this->build_category_lookup($json_data['categories'], $category_lookup);
				$org_category_map[$row['org_id']] = $category_lookup;
			}
		}

		// edu_list를 순회하며 category_name 채우기
		foreach ($edu_list as &$edu) {
			$edu['category_name'] = ''; // 기본값
			if (!empty($edu['org_id']) && !empty($edu['category_code'])) {
				$org_id = $edu['org_id'];
				$category_code = $edu['category_code'];

				if (isset($org_category_map[$org_id]) && isset($org_category_map[$org_id][$category_code])) {
					$edu['category_name'] = $org_category_map[$org_id][$category_code];
				}
			}
		}
		unset($edu);

		return $edu_list;
	}

	/**
	 * 재귀적으로 카테고리 조회 맵 생성
	 */
	private function build_category_lookup($categories, &$lookup)
	{
		foreach ($categories as $category) {
			if (isset($category['code']) && isset($category['name'])) {
				$lookup[$category['code']] = $category['name'];
			}
			if (isset($category['children']) && is_array($category['children']) && !empty($category['children'])) {
				$this->build_category_lookup($category['children'], $lookup);
			}
		}
	}

	/**
	 * 고유한 진행시간 목록 조회
	 */
	public function get_distinct_edu_times()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$this->db->select('edu_times');
		$this->db->from('wb_edu');
		$this->db->where('del_yn', 'N');
		$this->db->where('edu_times IS NOT NULL');
		$this->db->where("edu_times != '[]'");
		$this->db->where("edu_times != ''");
		$query = $this->db->get();
		$results = $query->result_array();

		$all_times = array();
		foreach ($results as $row) {
			$times = json_decode($row['edu_times'], true);
			if (is_array($times)) {
				foreach ($times as $time) {
					$all_times[] = $time;
				}
			}
		}

		$distinct_times = array_unique($all_times);
		sort($distinct_times, SORT_NATURAL);

		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(array(
			'success' => true,
			'data' => array_values($distinct_times)
		));
	}
}
