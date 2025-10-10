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
	 * 카테고리 트리 구조 생성 (전체 조직 수 별도 계산)
	 */
	public function get_category_tree()
	{
		$categories = $this->get_all_categories();

		if (empty($categories)) {
			$tree_data = array();
		} else {
			// 각 카테고리별 조직 수 계산 (직접 속한 조직만)
			$category_org_counts = $this->get_category_org_counts();
			// 하위 포함 조직 수 계산
			$category_total_counts = $this->calculate_category_total_counts($categories, $category_org_counts);

			$tree_data = $this->build_category_tree($categories, $category_org_counts, $category_total_counts, null);
		}

		// 미분류 조직을 최상위 레벨에 항상 추가
		$uncategorized_count = $this->get_uncategorized_org_count();
		$uncategorized_node = array(
			'key' => 'uncategorized',
			'title' => '미분류 (' . $uncategorized_count . '개)',
			'folder' => false,
			'data' => array(
				'type' => 'uncategorized',
				'category_idx' => 'uncategorized',
				'category_name' => '미분류',
				'org_count' => $uncategorized_count
			)
		);

		$tree_data[] = $uncategorized_node;

		return $tree_data;
	}

	/**
	 * 전체 조직 수 계산 (미분류 제외)
	 */
	public function get_total_categorized_org_count()
	{
		$this->db->from('wb_org');
		$this->db->where('del_yn', 'N');
		$this->db->where('category_idx IS NOT NULL');
		$this->db->where('category_idx >', 0);
		return $this->db->count_all_results();
	}

	/**
	 * 카테고리별 하위 포함 총 조직 수 계산
	 */
	private function calculate_category_total_counts($categories, $direct_counts)
	{
		$total_counts = array();

		// 모든 카테고리에 대해 하위 포함 조직 수 계산
		foreach ($categories as $category) {
			$total_counts[$category['category_idx']] = $this->get_category_total_org_count_recursive(
				$category['category_idx'],
				$categories,
				$direct_counts
			);
		}

		return $total_counts;
	}


	/**
	 * 특정 카테고리의 하위 포함 총 조직 수 재귀 계산
	 */
	private function get_category_total_org_count_recursive($category_idx, $categories, $direct_counts)
	{
		// 현재 카테고리의 직접 조직 수
		$total = isset($direct_counts[$category_idx]) ? $direct_counts[$category_idx] : 0;

		// 하위 카테고리들의 조직 수 합산
		foreach ($categories as $child_category) {
			if ($child_category['parent_idx'] == $category_idx) {
				$total += $this->get_category_total_org_count_recursive(
					$child_category['category_idx'],
					$categories,
					$direct_counts
				);
			}
		}

		return $total;
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

	/**
	 * 전체 카테고리 목록 조회 (플랫)
	 */
	public function get_all_categories_flat()
	{
		$this->db->select('category_idx, category_name, parent_idx');
		$this->db->from('wb_org_category');
		$this->db->order_by('category_order', 'ASC');
		$query = $this->db->get();
		return $query->result_array();
	}

	/**
	 * 카테고리 선택박스용 계층구조 플랫 목록 조회 (depth 표시)
	 */
	public function get_categories_for_select()
	{
		$categories = $this->get_all_categories();

		if (empty($categories)) {
			return array();
		}

		return $this->build_category_select_options($categories, null, 0);
	}



	/**
	 * 카테고리 선택박스 옵션 재귀적 생성 (depth 표현)
	 */
	private function build_category_select_options($categories, $parent_idx, $depth = 0)
	{
		$options = array();

		foreach ($categories as $category) {
			$category_parent_idx = empty($category['parent_idx']) ? null : $category['parent_idx'];

			if ($category_parent_idx == $parent_idx) {
				// depth에 따른 들여쓰기 생성
				$indent = str_repeat('&nbsp;&nbsp;&nbsp;', $depth); // 3칸 공백으로 들여쓰기

				$option = array(
					'category_idx' => $category['category_idx'],
					'category_name' => $indent . $category['category_name'],
					'parent_idx' => $category['parent_idx'],
					'depth' => $depth
				);

				$options[] = $option;

				// 하위 카테고리 재귀적 추가
				$children = $this->build_category_select_options($categories, $category['category_idx'], $depth + 1);
				$options = array_merge($options, $children);
			}
		}

		return $options;
	}

	/**
	 * 특정 카테고리의 모든 하위 카테고리 ID 조회 (재귀)
	 */
	public function get_all_child_category_ids($parent_category_idx)
	{
		$child_ids = array();

		// 직접 하위 카테고리 조회
		$this->db->select('category_idx');
		$this->db->from('wb_org_category');
		$this->db->where('parent_idx', $parent_category_idx);
		$query = $this->db->get();
		$direct_children = $query->result_array();

		foreach ($direct_children as $child) {
			$child_ids[] = $child['category_idx'];

			// 재귀적으로 하위의 하위 카테고리들도 조회
			$grandchildren = $this->get_all_child_category_ids($child['category_idx']);
			$child_ids = array_merge($child_ids, $grandchildren);
		}

		return $child_ids;
	}


	/**
	 * 재귀적으로 카테고리 트리 생성 (직접 조직 수와 하위 포함 조직 수 구분)
	 */
	private function build_category_tree($categories, $direct_counts, $total_counts, $parent_idx, $depth = 0)
	{
		$tree = array();

		foreach ($categories as $category) {
			$category_parent_idx = empty($category['parent_idx']) ? null : $category['parent_idx'];

			if ($category_parent_idx == $parent_idx) {
				// 하위 카테고리 찾기
				$children = $this->build_category_tree($categories, $direct_counts, $total_counts, $category['category_idx'], $depth + 1);

				// 현재 카테고리의 직접 조직 수
				$direct_org_count = isset($direct_counts[$category['category_idx']]) ? $direct_counts[$category['category_idx']] : 0;

				// 현재 카테고리의 하위 포함 총 조직 수
				$total_org_count = isset($total_counts[$category['category_idx']]) ? $total_counts[$category['category_idx']] : 0;

				// 제목에 총 조직 수 표시 (하위 포함)
				$title = $category['category_name'] . ' (' . $total_org_count . '개)';

				$node = array(
					'key' => 'category_' . $category['category_idx'],
					'title' => $title,
					'folder' => true,
					'data' => array(
						'type' => 'category',
						'category_idx' => $category['category_idx'],
						'category_name' => $category['category_name'],
						'parent_idx' => $category['parent_idx'],
						'org_count' => $total_org_count, // 하위 포함 총 조직 수 (선택 시 사용)
						'direct_org_count' => $direct_org_count, // 직접 조직 수 (전체 계산 시 사용)
						'total_org_count' => $total_org_count // 명시적으로 구분
					),
					'children' => $children
				);

				$tree[] = $node;
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

	/**
	 * 최상위 카테고리만 조회 (parent_idx가 null인 것)
	 */
	public function get_top_level_categories()
	{
		$this->db->select('category_idx, category_name, category_order');
		$this->db->from('wb_org_category');
		$this->db->where('(parent_idx IS NULL OR parent_idx = 0)', null, false);
		$this->db->order_by('category_order', 'ASC');
		$query = $this->db->get();
		return $query->result_array();
	}



	/**
	 * 특정 카테고리들을 제외한 트리 생성
	 */
	public function get_category_tree_excluding($excluded_category_ids = array())
	{
		$categories = $this->get_all_categories();

		if (empty($categories)) {
			$tree_data = array();
		} else {
			// 제외할 카테고리가 있으면 필터링
			if (!empty($excluded_category_ids)) {
				$categories = array_filter($categories, function($category) use ($excluded_category_ids) {
					return !in_array($category['category_idx'], $excluded_category_ids);
				});
			}

			// 각 카테고리별 조직 수 계산
			$category_org_counts = $this->get_category_org_counts();
			$category_total_counts = $this->calculate_category_total_counts($categories, $category_org_counts);

			$tree_data = $this->build_category_tree($categories, $category_org_counts, $category_total_counts, null);
		}

		// 미분류 조직 추가
		$uncategorized_count = $this->get_uncategorized_org_count();
		$uncategorized_node = array(
			'key' => 'uncategorized',
			'title' => '미분류 (' . $uncategorized_count . '개)',
			'folder' => false,
			'data' => array(
				'type' => 'uncategorized',
				'category_idx' => 'uncategorized',
				'category_name' => '미분류',
				'org_count' => $uncategorized_count
			)
		);

		$tree_data[] = $uncategorized_node;

		return $tree_data;
	}


	/**
	 * 마스터가 볼 수 있는 카테고리 트리 조회 (체크된 항목만 표시)
	 */
	public function get_category_tree_for_master($visible_categories = array())
	{
		// visible_categories가 비어있으면 전체 트리 반환
		if (empty($visible_categories)) {
			return $this->get_category_tree();
		}

		// visible_categories에 포함된 카테고리와 그 하위만 표시
		$all_categories = $this->get_all_categories();

		// visible_categories에 포함된 카테고리들과 그 하위 카테고리들의 ID 수집
		$visible_category_ids = $this->get_category_with_descendants($visible_categories);

		// 필터링된 카테고리
		$filtered_categories = array_filter($all_categories, function($cat) use ($visible_category_ids) {
			return in_array($cat['category_idx'], $visible_category_ids);
		});

		if (empty($filtered_categories)) {
			$tree_data = array();
		} else {
			// 각 카테고리별 조직 수 계산
			$category_org_counts = $this->get_category_org_counts();
			$category_total_counts = $this->calculate_category_total_counts($filtered_categories, $category_org_counts);

			$tree_data = $this->build_category_tree($filtered_categories, $category_org_counts, $category_total_counts, null);
		}

		// 미분류 조직을 최상위 레벨에 항상 추가
		$uncategorized_count = $this->get_uncategorized_org_count();
		$uncategorized_node = array(
			'key' => 'uncategorized',
			'title' => '미분류 (' . $uncategorized_count . '개)',
			'folder' => false,
			'data' => array(
				'type' => 'uncategorized',
				'category_idx' => 'uncategorized',
				'category_name' => '미분류',
				'org_count' => $uncategorized_count
			)
		);

		$tree_data[] = $uncategorized_node;

		return $tree_data;
	}


	/**
	 * 특정 카테고리들과 그 모든 하위 카테고리 ID 조회
	 */
	private function get_category_with_descendants($category_ids)
	{
		if (empty($category_ids)) {
			return array();
		}

		$result_ids = $category_ids;

		foreach ($category_ids as $category_idx) {
			$descendants = $this->get_all_child_category_ids($category_idx);
			$result_ids = array_merge($result_ids, $descendants);
		}

		return array_unique($result_ids);
	}


	/**
	 * 특정 카테고리들에 속한 조직 수 계산 (하위 카테고리 포함)
	 */
	public function get_filtered_org_count($visible_categories)
	{
		if (empty($visible_categories)) {
			return 0;
		}

		// visible_categories와 그 하위 카테고리들의 ID 수집
		$category_ids = $this->get_category_with_descendants($visible_categories);

		if (empty($category_ids)) {
			return 0;
		}

		// 해당 카테고리들에 속한 조직 수 계산
		$this->db->from('wb_org');
		$this->db->where('del_yn', 'N');
		$this->db->where_in('category_idx', $category_ids);

		return $this->db->count_all_results();
	}

	/**
	 * 특정 카테고리의 모든 하위 카테고리 ID 조회 (재귀)
	 */
	private function get_all_descendant_ids($category_idx)
	{
		$this->db->select('category_idx');
		$this->db->from('wb_org_category');
		$this->db->where('parent_idx', $category_idx);
		$query = $this->db->get();
		$children = $query->result_array();

		$descendant_ids = array();

		foreach ($children as $child) {
			$descendant_ids[] = $child['category_idx'];
			// 재귀적으로 하위 카테고리의 하위도 조회
			$sub_descendants = $this->get_all_descendant_ids($child['category_idx']);
			$descendant_ids = array_merge($descendant_ids, $sub_descendants);
		}

		return $descendant_ids;
	}

	/**
	 * 특정 카테고리들과 그 모든 하위 카테고리 ID 조회 (public)
	 */
	public function get_category_with_descendants_public($category_indices)
	{
		if (empty($category_indices)) {
			return array();
		}

		// 모든 카테고리 조회
		$all_categories = $this->get_all_categories_flat();

		$result = array();
		foreach ($category_indices as $category_idx) {
			// 자기 자신 추가
			$result[] = $category_idx;

			// 하위 카테고리 재귀적으로 추가
			$descendants = $this->get_descendants_recursive($category_idx, $all_categories);
			$result = array_merge($result, $descendants);
		}

		return array_unique($result);
	}


	/**
	 * 하위 카테고리 재귀 조회
	 */
	private function get_descendants_recursive($parent_idx, $all_categories)
	{
		$descendants = array();

		foreach ($all_categories as $category) {
			if ($category['parent_idx'] == $parent_idx) {
				$descendants[] = $category['category_idx'];

				// 재귀적으로 하위 카테고리 조회
				$child_descendants = $this->get_descendants_recursive($category['category_idx'], $all_categories);
				$descendants = array_merge($descendants, $child_descendants);
			}
		}

		return $descendants;
	}

	public function get_categories_for_select_filtered($visible_categories)
	{
		if (empty($visible_categories)) {
			return $this->get_categories_for_select();
		}

		// visible_categories와 그 하위 카테고리들의 ID 수집
		$category_ids = $this->get_category_with_descendants_public($visible_categories);

		if (empty($category_ids)) {
			return array();
		}

		// 전체 카테고리 조회
		$categories = $this->get_all_categories();

		// 필터링: category_ids에 포함된 카테고리만
		$filtered_categories = array_filter($categories, function($cat) use ($category_ids) {
			return in_array($cat['category_idx'], $category_ids);
		});

		if (empty($filtered_categories)) {
			return array();
		}

		// 계층구조 옵션 생성
		return $this->build_category_select_options($filtered_categories, null, 0);
	}

}
