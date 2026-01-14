<?php
/**
 * 파일 위치: application/controllers/mng/Mng_member.php
 * 역할: 마스터 회원관리 컨트롤러 - 카테고리+조직 트리, 회원 목록 관리
 */
defined('BASEPATH') or exit('No direct script access allowed');

class Mng_member extends CI_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->library('session');
		$this->load->helper('url');
		$this->load->model('Org_model');
		$this->load->model('Org_category_model');
		$this->load->model('User_model');
		$this->load->model('Member_model');

		// 마스터 권한 확인
		if ($this->session->userdata('master_yn') !== 'Y') {
			show_error('접근 권한이 없습니다.', 403);
		}

		// 메뉴 접근 권한 확인
		$this->check_menu_access('mng_member');
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
	 * 회원관리 메인 페이지
	 */
	public function index()
	{
		$data['user'] = $this->User_model->get_user_by_id($this->session->userdata('user_id'));
		$this->load->view('mng/mng_member', $data);
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
					$member_count = $this->get_org_member_count($org['org_id']);
					$children[] = array(
						'key' => 'org_' . $org['org_id'],
						'title' => $org['org_name'] . ' (' . $member_count . '명)',
						'folder' => false,
						'icon' => 'bi bi-building',
						'data' => array(
							'type' => 'org',
							'org_id' => $org['org_id'],
							'org_name' => $org['org_name'],
							'member_count' => $member_count
						)
					);
				}

				// 카테고리의 회원 수 재계산 (하위 포함)
				$category_member_count = $this->get_category_member_count($category_idx);
				$new_node['title'] = $node['data']['category_name'] . ' (' . $category_member_count . '명)';
				$new_node['children'] = $children;

			} else if (isset($node['data']) && $node['data']['type'] === 'uncategorized') {
				// 미분류 조직 처리
				$orgs = $this->get_uncategorized_orgs();
				$children = array();

				foreach ($orgs as $org) {
					$member_count = $this->get_org_member_count($org['org_id']);
					$children[] = array(
						'key' => 'org_' . $org['org_id'],
						'title' => $org['org_name'] . ' (' . $member_count . '명)',
						'folder' => false,
						'icon' => 'bi bi-building',
						'data' => array(
							'type' => 'org',
							'org_id' => $org['org_id'],
							'org_name' => $org['org_name'],
							'member_count' => $member_count
						)
					);
				}

				$uncategorized_member_count = $this->get_uncategorized_member_count();
				$new_node['title'] = '미분류 (' . $uncategorized_member_count . '명)';
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
	 * 조직별 회원 수 조회
	 */
	private function get_org_member_count($org_id)
	{
		$this->db->from('wb_member');
		$this->db->where('org_id', $org_id);
		$this->db->where('del_yn', 'N');
		return $this->db->count_all_results();
	}

	/**
	 * 카테고리의 전체 회원 수 조회 (하위 카테고리 포함)
	 */
	private function get_category_member_count($category_idx)
	{
		$category_ids = $this->Org_category_model->get_category_with_descendants_public(array($category_idx));

		if (empty($category_ids)) {
			return 0;
		}

		$this->db->from('wb_member m');
		$this->db->join('wb_org o', 'm.org_id = o.org_id');
		$this->db->where('m.del_yn', 'N');
		$this->db->where('o.del_yn', 'N');
		$this->db->where_in('o.category_idx', $category_ids);

		return $this->db->count_all_results();
	}

	/**
	 * 미분류 조직의 회원 수 조회
	 */
	private function get_uncategorized_member_count()
	{
		$this->db->from('wb_member m');
		$this->db->join('wb_org o', 'm.org_id = o.org_id');
		$this->db->where('m.del_yn', 'N');
		$this->db->where('o.del_yn', 'N');
		$this->db->group_start();
		$this->db->where('o.category_idx IS NULL');
		$this->db->or_where('o.category_idx', 0);
		$this->db->group_end();

		return $this->db->count_all_results();
	}

	/**
	 * 전체 회원 수 조회 (AJAX)
	 */
	public function get_total_member_count()
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
				$this->db->from('wb_member m');
				$this->db->join('wb_org o', 'm.org_id = o.org_id');
				$this->db->where('m.del_yn', 'N');
				$this->db->where('o.del_yn', 'N');
				$this->db->where_in('o.category_idx', $category_ids);
				$total_count = $this->db->count_all_results();
			}
		} else {
			$this->db->from('wb_member m');
			$this->db->join('wb_org o', 'm.org_id = o.org_id');
			$this->db->where('m.del_yn', 'N');
			$this->db->where('o.del_yn', 'N');
			$total_count = $this->db->count_all_results();
		}

		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(array('total_count' => $total_count));
	}

	/**
	 * 회원 목록 조회 (AJAX)
	 */
	public function get_member_list()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$type = $this->input->get('type'); // all, category, org, uncategorized
		$id = $this->input->get('id');

		$visible_categories = $this->get_visible_categories();

		$members = array();

		switch ($type) {
			case 'all':
				$members = $this->get_all_members($visible_categories);
				break;

			case 'category':
				$members = $this->get_category_members($id);
				break;

			case 'org':
				$members = $this->get_org_members_list($id);
				break;

			case 'uncategorized':
				$members = $this->get_uncategorized_members();
				break;

			default:
				$members = array();
		}

		// 사진 URL 처리
		foreach ($members as &$member) {
			$photo_url = '/assets/images/photo_no.png';
			if (!empty($member['photo'])) {
				if (strpos($member['photo'], '/uploads/') === false) {
					$photo_url = '/uploads/member_photos/' . $member['org_id'] . '/' . $member['photo'];
				} else {
					$photo_url = $member['photo'];
				}
			}
			$member['photo_url'] = $photo_url;
		}

		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(array(
			'success' => true,
			'data' => $members
		), JSON_UNESCAPED_UNICODE);
	}

	/**
	 * 전체 회원 조회
	 */
	private function get_all_members($visible_categories)
	{
		$this->db->select('m.*, o.org_name, c.category_name');
		$this->db->from('wb_member m');
		$this->db->join('wb_org o', 'm.org_id = o.org_id');
		$this->db->join('wb_org_category c', 'o.category_idx = c.category_idx', 'left');
		$this->db->where('m.del_yn', 'N');
		$this->db->where('o.del_yn', 'N');

		if (!empty($visible_categories)) {
			$category_ids = $this->Org_category_model->get_category_with_descendants_public($visible_categories);
			if (!empty($category_ids)) {
				$this->db->where_in('o.category_idx', $category_ids);
			}
		}

		$this->db->order_by('m.regi_date', 'DESC');
		$query = $this->db->get();
		return $query->result_array();
	}

	/**
	 * 카테고리별 회원 조회 (하위 카테고리 포함)
	 */
	private function get_category_members($category_idx)
	{
		$category_ids = $this->Org_category_model->get_category_with_descendants_public(array($category_idx));

		if (empty($category_ids)) {
			return array();
		}

		$this->db->select('m.*, o.org_name, c.category_name');
		$this->db->from('wb_member m');
		$this->db->join('wb_org o', 'm.org_id = o.org_id');
		$this->db->join('wb_org_category c', 'o.category_idx = c.category_idx', 'left');
		$this->db->where('m.del_yn', 'N');
		$this->db->where('o.del_yn', 'N');
		$this->db->where_in('o.category_idx', $category_ids);
		$this->db->order_by('m.regi_date', 'DESC');
		$query = $this->db->get();
		return $query->result_array();
	}

	/**
	 * 조직별 회원 조회
	 */
	private function get_org_members_list($org_id)
	{
		$this->db->select('m.*, o.org_name, c.category_name');
		$this->db->from('wb_member m');
		$this->db->join('wb_org o', 'm.org_id = o.org_id');
		$this->db->join('wb_org_category c', 'o.category_idx = c.category_idx', 'left');
		$this->db->where('m.org_id', $org_id);
		$this->db->where('m.del_yn', 'N');
		$this->db->where('o.del_yn', 'N');
		$this->db->order_by('m.regi_date', 'DESC');
		$query = $this->db->get();
		return $query->result_array();
	}

	/**
	 * 미분류 조직의 회원 조회
	 */
	private function get_uncategorized_members()
	{
		$this->db->select('m.*, o.org_name, "" as category_name');
		$this->db->from('wb_member m');
		$this->db->join('wb_org o', 'm.org_id = o.org_id');
		$this->db->where('m.del_yn', 'N');
		$this->db->where('o.del_yn', 'N');
		$this->db->group_start();
		$this->db->where('o.category_idx IS NULL');
		$this->db->or_where('o.category_idx', 0);
		$this->db->group_end();
		$this->db->order_by('m.regi_date', 'DESC');
		$query = $this->db->get();
		return $query->result_array();
	}

	/**
	 * 회원 상세 정보 조회 (AJAX)
	 */
	public function get_member_detail()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$member_idx = $this->input->get('member_idx');

		if (!$member_idx) {
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array('success' => false, 'message' => '회원 ID가 필요합니다.'));
			return;
		}

		$this->db->select('m.*, o.org_name');
		$this->db->from('wb_member m');
		$this->db->join('wb_org o', 'm.org_id = o.org_id', 'left');
		$this->db->where('m.member_idx', $member_idx);
		$query = $this->db->get();
		$member = $query->row_array();

		if (!$member) {
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array('success' => false, 'message' => '회원 정보를 찾을 수 없습니다.'));
			return;
		}

		// 사진 URL 처리
		$photo_url = '/assets/images/photo_no.png';
		if (!empty($member['photo'])) {
			if (strpos($member['photo'], '/uploads/') === false) {
				$photo_url = '/uploads/member_photos/' . $member['org_id'] . '/' . $member['photo'];
			} else {
				$photo_url = $member['photo'];
			}
		}
		$member['photo_url'] = $photo_url;

		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(array(
			'success' => true,
			'data' => $member
		), JSON_UNESCAPED_UNICODE);
	}

	/**
	 * 파일 위치: application/controllers/mng/Mng_member.php
	 * 역할: 회원 상태 일괄 변경
	 */
	public function update_member_status()
	{
		$member_idx_list = $this->input->post('member_idx_list');
		$member_status = $this->input->post('member_status');

		if (empty($member_idx_list) || !is_array($member_idx_list)) {
			echo json_encode(array(
				'success' => false,
				'message' => '변경할 회원을 선택해주세요.'
			));
			return;
		}

		// 허용된 상태값 검증
		$allowed_statuses = array('enlisted', 'assigned', 'settled', 'nurturing', 'dispatched');
		if (!in_array($member_status, $allowed_statuses)) {
			echo json_encode(array(
				'success' => false,
				'message' => '유효하지 않은 상태값입니다.'
			));
			return;
		}

		$this->load->model('Member_model');

		$success_count = 0;
		$error_count = 0;

		foreach ($member_idx_list as $member_idx) {
			$result = $this->Member_model->update_member_status($member_idx, $member_status);
			if ($result) {
				$success_count++;
			} else {
				$error_count++;
			}
		}

		if ($success_count > 0) {
			echo json_encode(array(
				'success' => true,
				'message' => $success_count . '명의 상태가 변경되었습니다.'
			));
		} else {
			echo json_encode(array(
				'success' => false,
				'message' => '상태 변경에 실패했습니다.'
			));
		}
	}
}
