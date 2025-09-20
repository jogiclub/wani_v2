<?php
/**
 * 파일 위치: application/models/Org_category_model.php
 * 역할: 조직 카테고리 관리 모델
 */

class Org_category_model extends CI_Model
{
	public function __construct()
	{
		parent::__construct();
		$this->load->database();
	}

	/**
	 * 모든 카테고리 조회
	 */
	public function get_all_categories()
	{
		$this->db->select('category_idx, category_name, parent_idx, category_order');
		$this->db->from('wb_org_category');
		$this->db->order_by('category_order', 'ASC');
		$query = $this->db->get();
		return $query->result_array();
	}

	/**
	 * 카테고리 트리 구조 생성
	 */
	public function get_category_tree()
	{
		$categories = $this->get_all_categories();

		if (empty($categories)) {
			return array();
		}

		// 각 카테고리별 조직 수 계산
		$category_org_counts = $this->get_category_org_counts();

		return $this->build_category_tree($categories, $category_org_counts, null);
	}

	/**
	 * 각 카테고리별 조직 수 계산
	 */
	private function get_category_org_counts()
	{
		// 카테고리별 직접 속한 조직 수 조회
		$this->db->select('category_idx, COUNT(*) as org_count');
		$this->db->from('wb_org');
		$this->db->where('del_yn', 'N');
		$this->db->group_by('category_idx');
		$query = $this->db->get();
		$direct_counts = $query->result_array();

		$counts = array();
		foreach ($direct_counts as $count) {
			$counts[$count['category_idx']] = $count['org_count'];
		}

		return $counts;
	}

	public function get_all_categories_flat()
	{
		$this->db->select('category_idx, category_name, parent_idx');
		$this->db->from('wb_org_category');
		$this->db->where('del_yn', 'N');
		$this->db->order_by('category_order', 'ASC');
		$query = $this->db->get();
		return $query->result_array();
	}

	/**
	 * 재귀적으로 카테고리 트리 생성
	 */
	private function build_category_tree($categories, $category_org_counts, $parent_idx, $depth = 0)
	{
		$tree = array();

		foreach ($categories as $category) {
			$category_parent_idx = empty($category['parent_idx']) ? null : $category['parent_idx'];

			if ($category_parent_idx == $parent_idx) {
				// 하위 카테고리 찾기
				$children = $this->build_category_tree($categories, $category_org_counts, $category['category_idx'], $depth + 1);

				// 현재 카테고리의 조직 수 (직접 속한 조직만)
				$org_count = isset($category_org_counts[$category['category_idx']]) ? $category_org_counts[$category['category_idx']] : 0;

				$title = $category['category_name'];
				if ($org_count > 0) {
					$title .= ' (' . $org_count . '개)';
				}

				$node = array(
					'key' => 'category_' . $category['category_idx'],
					'title' => $title,
					'folder' => true,
					'data' => array(
						'type' => 'category',
						'category_idx' => $category['category_idx'],
						'category_name' => $category['category_name'],
						'parent_idx' => $category['parent_idx'],
						'org_count' => $org_count
					),
					'children' => $children
				);

				$tree[] = $node;
			}
		}

		// 최상위 레벨에만 미분류 조직 추가
		if ($parent_idx === null) {
			$uncategorized_count = $this->get_uncategorized_org_count();
			if ($uncategorized_count > 0) {
				$tree[] = array(
					'key' => 'uncategorized',
					'title' => '미분류 (' . $uncategorized_count . '개)',
					'folder' => false,
					'data' => array(
						'type' => 'uncategorized',
						'category_idx' => null,
						'org_count' => $uncategorized_count
					)
				);
			}
		}

		return $tree;
	}

	/**
	 * 미분류 조직 수 조회
	 */
	private function get_uncategorized_org_count()
	{
		$this->db->from('wb_org');
		$this->db->where('del_yn', 'N');
		$this->db->where('(category_idx IS NULL OR category_idx = 0)', null, false);
		return $this->db->count_all_results();
	}

	/**
	 * 다음 순서 번호 가져오기
	 */
	public function get_next_order($parent_idx = null)
	{
		$this->db->select_max('category_order');
		$this->db->from('wb_org_category');

		if ($parent_idx === null) {
			$this->db->where('(parent_idx IS NULL OR parent_idx = 0)', null, false);
		} else {
			$this->db->where('parent_idx', $parent_idx);
		}

		$query = $this->db->get();
		$result = $query->row_array();
		return ($result['category_order'] ?? 0) + 1;
	}

	/**
	 * 카테고리 추가
	 */
	public function insert_category($data)
	{
		return $this->db->insert('wb_org_category', $data);
	}

	/**
	 * 카테고리 수정
	 */
	public function update_category($category_idx, $data)
	{
		$this->db->where('category_idx', $category_idx);
		return $this->db->update('wb_org_category', $data);
	}

	/**
	 * 카테고리 삭제
	 */
	public function delete_category($category_idx)
	{
		$this->db->where('category_idx', $category_idx);
		return $this->db->delete('wb_org_category');
	}

	/**
	 * 하위 카테고리 존재 여부 확인
	 */
	public function has_children($category_idx)
	{
		$this->db->from('wb_org_category');
		$this->db->where('parent_idx', $category_idx);
		return $this->db->count_all_results() > 0;
	}

	/**
	 * 특정 카테고리 정보 조회
	 */
	public function get_category_by_id($category_idx)
	{
		$this->db->select('category_idx, category_name, parent_idx, category_order');
		$this->db->from('wb_org_category');
		$this->db->where('category_idx', $category_idx);
		$query = $this->db->get();
		return $query->row_array();
	}
}
