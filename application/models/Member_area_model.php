<?php
class Member_area_model extends CI_Model {
	public function __construct() {
		parent::__construct();
		$this->load->database();
	}

	/**
	 * 조직의 모든 멤버 영역 가져오기 (트리 구조용)
	 */
	public function get_member_areas($org_id) {
		$this->db->select('area_idx, area_name, parent_idx, area_order');
		$this->db->from('wb_member_area');
		$this->db->where('org_id', $org_id);
		$this->db->order_by('area_order', 'ASC');

		$query = $this->db->get();
		return $query->result_array();
	}

	/**
	 * 계층적 트리 구조로 영역 데이터 변환
	 */
	public function get_member_areas_tree($org_id) {
		// 모든 영역 가져오기
		$areas = $this->get_member_areas($org_id);

		// 트리 구조로 변환
		return $this->build_tree($areas);
	}

	/**
	 * 배열을 트리 구조로 변환하는 재귀 함수
	 */
	private function build_tree($areas, $parent_idx = null) {
		$tree = array();

		foreach ($areas as $area) {
			// parent_idx가 null이면 최상위, 아니면 해당 부모의 자식
			$area_parent_idx = empty($area['parent_idx']) ? null : $area['parent_idx'];

			if ($area_parent_idx == $parent_idx) {
				// 현재 영역의 자식들을 찾아서 추가
				$children = $this->build_tree($areas, $area['area_idx']);

				$tree_node = array(
					'area_idx' => $area['area_idx'],
					'area_name' => $area['area_name'],
					'parent_idx' => $area['parent_idx'],
					'area_order' => $area['area_order'],
					'children' => $children
				);

				$tree[] = $tree_node;
			}
		}

		return $tree;
	}

	/**
	 * 특정 영역의 자식 영역들 가져오기
	 */
	public function get_child_areas($parent_area_idx, $org_id) {
		$this->db->select('area_idx, area_name, parent_idx, area_order');
		$this->db->from('wb_member_area');
		$this->db->where('org_id', $org_id);
		$this->db->where('parent_idx', $parent_area_idx);
		$this->db->order_by('area_order', 'ASC');

		$query = $this->db->get();
		return $query->result_array();
	}

	/**
	 * 최상위 영역들(parent_idx가 null인 영역들) 가져오기
	 */
	public function get_root_areas($org_id) {
		$this->db->select('area_idx, area_name, parent_idx, area_order');
		$this->db->from('wb_member_area');
		$this->db->where('org_id', $org_id);
		$this->db->where('(parent_idx IS NULL OR parent_idx = "" OR parent_idx = 0)', null, false);
		$this->db->order_by('area_order', 'ASC');

		$query = $this->db->get();
		return $query->result_array();
	}

	public function get_max_order($org_id) {
		$this->db->select_max('area_order');
		$this->db->where('org_id', $org_id);
		$query = $this->db->get('wb_member_area');
		$result = $query->row_array();
		return $result['area_order'] ?? 0;
	}

	public function add_area($data) {
		$this->db->insert('wb_member_area', $data);
		return $this->db->affected_rows() > 0;
	}

	public function update_areas($areas) {
		$success = true;

		foreach ($areas as $area) {
			$this->db->where('area_idx', $area['area_idx']);
			if (!$this->db->update('wb_member_area', ['area_name' => $area['area_name']])) {
				$success = false;
				break;
			}
		}

		return $success;
	}

	public function check_area_members($area_idx) {
		$this->db->where('area_idx', $area_idx);
		$this->db->where('del_yn', 'N');
		return $this->db->count_all_results('wb_member');
	}

	public function delete_area($area_idx) {
		$this->db->where('area_idx', $area_idx);
		return $this->db->delete('wb_member_area');
	}

	/**
	 * 특정 area_idx로 소그룹 정보 조회
	 */
	public function get_area_by_idx($area_idx)
	{
		$this->db->select('area_idx, area_name, parent_idx, area_order, org_id');
		$this->db->from('wb_member_area');
		$this->db->where('area_idx', $area_idx);

		$query = $this->db->get();
		return $query->row_array();
	}




	/**
	 * 역할: 특정 area_idx 목록으로 그룹 정보 조회 (권한 필터링용)
	 */
	public function get_member_areas_by_idx($org_id, $area_indices)
	{
		if (empty($area_indices)) {
			return array();
		}

		$this->db->select('area_idx, area_name, area_order, parent_idx');
		$this->db->from('wb_member_area');
		$this->db->where('org_id', $org_id);
		$this->db->where_in('area_idx', $area_indices);
		$this->db->order_by('area_order', 'ASC');

		$query = $this->db->get();
		return $query->result_array();
	}

	/**
	 * 역할: 사용자가 접근 가능한 모든 그룹 정보 조회 (계층 구조 포함)
	 */
	public function get_user_accessible_areas($user_id, $org_id)
	{
		// User_management_model 로드
		$CI =& get_instance();
		$CI->load->model('User_management_model');
		$CI->load->model('User_model');

		$master_yn = $CI->session->userdata('master_yn');
		$user_level = $CI->User_model->get_org_user_level($user_id, $org_id);

		// 최고관리자 또는 마스터인 경우 모든 그룹 반환
		if ($user_level >= 10 || $master_yn === 'Y') {
			return $this->get_member_areas($org_id);
		}

		// 일반 관리자인 경우 관리 가능한 그룹만 반환
		$accessible_area_indices = $CI->User_management_model->get_user_managed_areas_with_children($user_id, $org_id);

		if (empty($accessible_area_indices)) {
			return array();
		}

		return $this->get_member_areas_by_idx($org_id, $accessible_area_indices);
	}

}
