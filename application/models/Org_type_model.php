<?php
/**
 * 파일 위치: application/models/Org_type_model.php
 * 역할: 조직 유형 관리 모델
 */

class Org_type_model extends CI_Model
{
	public function __construct()
	{
		parent::__construct();
		$this->load->database();
	}

	/**
	 * 모든 유형 조회
	 */
	public function get_all_types()
	{
		$this->db->select('type_idx, type_name, parent_idx, type_order');
		$this->db->from('wb_org_type');
		$this->db->order_by('type_order', 'ASC');
		$query = $this->db->get();
		return $query->result_array();
	}

	/**
	 * 유형 트리 구조 생성 (전체 조직 수 별도 계산)
	 */
	public function get_type_tree()
	{
		$types = $this->get_all_types();

		if (empty($types)) {
			$tree_data = array();
		} else {
			// 각 유형별 조직 수 계산 (직접 속한 조직만)
			$type_org_counts = $this->get_type_org_counts();
			// 하위 포함 조직 수 계산
			$type_total_counts = $this->calculate_type_total_counts($types, $type_org_counts);

			$tree_data = $this->build_type_tree($types, $type_org_counts, $type_total_counts, null);
		}

		// 미분류 조직을 최상위 레벨에 항상 추가
		$uncategorized_count = $this->get_uncategorized_org_count();
		$uncategorized_node = array(
			'key' => 'uncategorized',
			'title' => '미분류 (' . $uncategorized_count . '개)',
			'folder' => false,
			'data' => array(
				'type' => 'uncategorized',
				'type_idx' => 'uncategorized',
				'type_name' => '미분류',
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
		$this->db->where('type_idx IS NOT NULL');
		$this->db->where('type_idx >', 0);
		return $this->db->count_all_results();
	}

	/**
	 * 유형별 하위 포함 총 조직 수 계산
	 */
	private function calculate_type_total_counts($types, $direct_counts)
	{
		$total_counts = array();

		// 모든 유형에 대해 하위 포함 조직 수 계산
		foreach ($types as $type) {
			$total_counts[$type['type_idx']] = $this->get_type_total_org_count_recursive(
				$type['type_idx'],
				$types,
				$direct_counts
			);
		}

		return $total_counts;
	}


	/**
	 * 특정 유형의 하위 포함 총 조직 수 재귀 계산
	 */
	private function get_type_total_org_count_recursive($type_idx, $types, $direct_counts)
	{
		// 현재 유형의 직접 조직 수
		$total = isset($direct_counts[$type_idx]) ? $direct_counts[$type_idx] : 0;

		// 하위 유형들의 조직 수 합산
		foreach ($types as $child_type) {
			if ($child_type['parent_idx'] == $type_idx) {
				$total += $this->get_type_total_org_count_recursive(
					$child_type['type_idx'],
					$types,
					$direct_counts
				);
			}
		}

		return $total;
	}

	/**
	 * 각 유형별 조직 수 계산
	 */
	private function get_type_org_counts()
	{
		// 유형별 직접 속한 조직 수 조회
		$this->db->select('type_idx, COUNT(*) as org_count');
		$this->db->from('wb_org');
		$this->db->where('del_yn', 'N');
		$this->db->group_by('type_idx');
		$query = $this->db->get();
		$direct_counts = $query->result_array();

		$counts = array();
		foreach ($direct_counts as $count) {
			$counts[$count['type_idx']] = $count['org_count'];
		}

		return $counts;
	}

	/**
	 * 전체 유형 목록 조회 (플랫)
	 */
	public function get_all_types_flat()
	{
		$this->db->select('type_idx, type_name, parent_idx');
		$this->db->from('wb_org_type');
		$this->db->order_by('type_order', 'ASC');
		$query = $this->db->get();
		return $query->result_array();
	}

	/**
	 * 유형 선택박스용 계층구조 플랫 목록 조회 (depth 표시)
	 */
	public function get_types_for_select()
	{
		$types = $this->get_all_types();

		if (empty($types)) {
			return array();
		}

		return $this->build_type_select_options($types, null, 0);
	}



	/**
	 * 유형 선택박스 옵션 재귀적 생성 (depth 표현)
	 */
	private function build_type_select_options($types, $parent_idx, $depth = 0)
	{
		$options = array();

		foreach ($types as $type) {
			$type_parent_idx = empty($type['parent_idx']) ? null : $type['parent_idx'];

			if ($type_parent_idx == $parent_idx) {
				// depth에 따른 들여쓰기 생성
				$indent = str_repeat('&nbsp;&nbsp;&nbsp;', $depth); // 3칸 공백으로 들여쓰기

				$option = array(
					'type_idx' => $type['type_idx'],
					'type_name' => $indent . $type['type_name'],
					'parent_idx' => $type['parent_idx'],
					'depth' => $depth
				);

				$options[] = $option;

				// 하위 유형 재귀적 추가
				$children = $this->build_type_select_options($types, $type['type_idx'], $depth + 1);
				$options = array_merge($options, $children);
			}
		}

		return $options;
	}

	/**
	 * 특정 유형의 모든 하위 유형 ID 조회 (재귀)
	 */
	public function get_all_child_type_ids($parent_type_idx)
	{
		$child_ids = array();

		// 직접 하위 유형 조회
		$this->db->select('type_idx');
		$this->db->from('wb_org_type');
		$this->db->where('parent_idx', $parent_type_idx);
		$query = $this->db->get();
		$direct_children = $query->result_array();

		foreach ($direct_children as $child) {
			$child_ids[] = $child['type_idx'];

			// 재귀적으로 하위의 하위 유형들도 조회
			$grandchildren = $this->get_all_child_type_ids($child['type_idx']);
			$child_ids = array_merge($child_ids, $grandchildren);
		}

		return $child_ids;
	}


	/**
	 * 재귀적으로 유형 트리 생성 (직접 조직 수와 하위 포함 조직 수 구분)
	 */
	private function build_type_tree($types, $direct_counts, $total_counts, $parent_idx, $depth = 0)
	{
		$tree = array();

		foreach ($types as $type) {
			$type_parent_idx = empty($type['parent_idx']) ? null : $type['parent_idx'];

			if ($type_parent_idx == $parent_idx) {
				// 하위 유형 찾기
				$children = $this->build_type_tree($types, $direct_counts, $total_counts, $type['type_idx'], $depth + 1);

				// 현재 유형의 직접 조직 수
				$direct_org_count = isset($direct_counts[$type['type_idx']]) ? $direct_counts[$type['type_idx']] : 0;

				// 현재 유형의 하위 포함 총 조직 수
				$total_org_count = isset($total_counts[$type['type_idx']]) ? $total_counts[$type['type_idx']] : 0;

				// 제목에 총 조직 수 표시 (하위 포함)
				$title = $type['type_name'] . ' (' . $total_org_count . '개)';

				$node = array(
					'key' => 'type_' . $type['type_idx'],
					'title' => $title,
					'folder' => true,
					'data' => array(
						'type' => 'type',
						'type_idx' => $type['type_idx'],
						'type_name' => $type['type_name'],
						'parent_idx' => $type['parent_idx'],
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
		$this->db->where('(type_idx IS NULL OR type_idx = 0)', null, false);
		return $this->db->count_all_results();
	}

	/**
	 * 다음 순서 번호 가져오기
	 */
	public function get_next_order($parent_idx = null)
	{
		$this->db->select_max('type_order');
		$this->db->from('wb_org_type');

		if ($parent_idx === null) {
			$this->db->where('(parent_idx IS NULL OR parent_idx = 0)', null, false);
		} else {
			$this->db->where('parent_idx', $parent_idx);
		}

		$query = $this->db->get();
		$result = $query->row_array();
		return ($result['type_order'] ?? 0) + 1;
	}

	/**
	 * 유형 추가
	 */
	public function insert_type($data)
	{
		return $this->db->insert('wb_org_type', $data);
	}

	/**
	 * 유형 수정
	 */
	public function update_type($type_idx, $data)
	{
		$this->db->where('type_idx', $type_idx);
		return $this->db->update('wb_org_type', $data);
	}

	/**
	 * 유형 삭제
	 */
	public function delete_type($type_idx)
	{
		$this->db->where('type_idx', $type_idx);
		return $this->db->delete('wb_org_type');
	}

	/**
	 * 하위 유형 존재 여부 확인
	 */
	public function has_children($type_idx)
	{
		$this->db->from('wb_org_type');
		$this->db->where('parent_idx', $type_idx);
		return $this->db->count_all_results() > 0;
	}

	/**
	 * 특정 유형 정보 조회
	 */
	public function get_type_by_id($type_idx)
	{
		$this->db->select('type_idx, type_name, parent_idx, type_order');
		$this->db->from('wb_org_type');
		$this->db->where('type_idx', $type_idx);
		$query = $this->db->get();
		return $query->row_array();
	}

	/**
	 * 최상위 유형만 조회 (parent_idx가 null인 것)
	 */
	public function get_top_level_types()
	{
		$this->db->select('type_idx, type_name, type_order');
		$this->db->from('wb_org_type');
		$this->db->where('(parent_idx IS NULL OR parent_idx = 0)', null, false);
		$this->db->order_by('type_order', 'ASC');
		$query = $this->db->get();
		return $query->result_array();
	}



	/**
	 * 특정 유형들을 제외한 트리 생성
	 */
	public function get_type_tree_excluding($excluded_type_ids = array())
	{
		$types = $this->get_all_types();

		if (empty($types)) {
			$tree_data = array();
		} else {
			// 제외할 유형이 있으면 필터링
			if (!empty($excluded_type_ids)) {
				$types = array_filter($types, function($type) use ($excluded_type_ids) {
					return !in_array($type['type_idx'], $excluded_type_ids);
				});
			}

			// 각 유형별 조직 수 계산
			$type_org_counts = $this->get_type_org_counts();
			$type_total_counts = $this->calculate_type_total_counts($types, $type_org_counts);

			$tree_data = $this->build_type_tree($types, $type_org_counts, $type_total_counts, null);
		}

		// 미분류 조직 추가
		$uncategorized_count = $this->get_uncategorized_org_count();
		$uncategorized_node = array(
			'key' => 'uncategorized',
			'title' => '미분류 (' . $uncategorized_count . '개)',
			'folder' => false,
			'data' => array(
				'type' => 'uncategorized',
				'type_idx' => 'uncategorized',
				'type_name' => '미분류',
				'org_count' => $uncategorized_count
			)
		);

		$tree_data[] = $uncategorized_node;

		return $tree_data;
	}


	/**
	 * 마스터가 볼 수 있는 유형 트리 조회 (체크된 항목만 표시)
	 */
	public function get_type_tree_for_master($visible_types = array())
	{
		// visible_types가 비어있으면 전체 트리 반환
		if (empty($visible_types)) {
			return $this->get_type_tree();
		}

		// visible_types에 포함된 유형과 그 하위만 표시
		$all_types = $this->get_all_types();

		// visible_types에 포함된 유형들과 그 하위 유형들의 ID 수집
		$visible_type_ids = $this->get_type_with_descendants($visible_types);

		// 필터링된 유형
		$filtered_types = array_filter($all_types, function($cat) use ($visible_type_ids) {
			return in_array($cat['type_idx'], $visible_type_ids);
		});

		if (empty($filtered_types)) {
			$tree_data = array();
		} else {
			// 각 유형별 조직 수 계산
			$type_org_counts = $this->get_type_org_counts();
			$type_total_counts = $this->calculate_type_total_counts($filtered_types, $type_org_counts);

			$tree_data = $this->build_type_tree($filtered_types, $type_org_counts, $type_total_counts, null);
		}

		// 미분류 조직을 최상위 레벨에 항상 추가
		$uncategorized_count = $this->get_uncategorized_org_count();
		$uncategorized_node = array(
			'key' => 'uncategorized',
			'title' => '미분류 (' . $uncategorized_count . '개)',
			'folder' => false,
			'data' => array(
				'type' => 'uncategorized',
				'type_idx' => 'uncategorized',
				'type_name' => '미분류',
				'org_count' => $uncategorized_count
			)
		);

		$tree_data[] = $uncategorized_node;

		return $tree_data;
	}


	/**
	 * 특정 유형들과 그 모든 하위 유형 ID 조회
	 */
	private function get_type_with_descendants($type_ids)
	{
		if (empty($type_ids)) {
			return array();
		}

		$result_ids = $type_ids;

		foreach ($type_ids as $type_idx) {
			$descendants = $this->get_all_child_type_ids($type_idx);
			$result_ids = array_merge($result_ids, $descendants);
		}

		return array_unique($result_ids);
	}


	/**
	 * 특정 유형들에 속한 조직 수 계산 (하위 유형 포함)
	 */
	public function get_filtered_org_count($visible_types)
	{
		if (empty($visible_types)) {
			return 0;
		}

		// visible_types와 그 하위 유형들의 ID 수집
		$type_ids = $this->get_type_with_descendants($visible_types);

		if (empty($type_ids)) {
			return 0;
		}

		// 해당 유형들에 속한 조직 수 계산
		$this->db->from('wb_org');
		$this->db->where('del_yn', 'N');
		$this->db->where_in('type_idx', $type_ids);

		return $this->db->count_all_results();
	}

	/**
	 * 특정 유형의 모든 하위 유형 ID 조회 (재귀)
	 */
	private function get_all_descendant_ids($type_idx)
	{
		$this->db->select('type_idx');
		$this->db->from('wb_org_type');
		$this->db->where('parent_idx', $type_idx);
		$query = $this->db->get();
		$children = $query->result_array();

		$descendant_ids = array();

		foreach ($children as $child) {
			$descendant_ids[] = $child['type_idx'];
			// 재귀적으로 하위 유형의 하위도 조회
			$sub_descendants = $this->get_all_descendant_ids($child['type_idx']);
			$descendant_ids = array_merge($descendant_ids, $sub_descendants);
		}

		return $descendant_ids;
	}

	/**
	 * 특정 유형들과 그 모든 하위 유형 ID 조회 (public)
	 */
	public function get_type_with_descendants_public($type_indices)
	{
		if (empty($type_indices)) {
			return array();
		}

		// 모든 유형 조회
		$all_types = $this->get_all_types_flat();

		$result = array();
		foreach ($type_indices as $type_idx) {
			// 자기 자신 추가
			$result[] = $type_idx;

			// 하위 유형 재귀적으로 추가
			$descendants = $this->get_descendants_recursive($type_idx, $all_types);
			$result = array_merge($result, $descendants);
		}

		return array_unique($result);
	}


	/**
	 * 하위 유형 재귀 조회
	 */
	private function get_descendants_recursive($parent_idx, $all_types)
	{
		$descendants = array();

		foreach ($all_types as $type) {
			if ($type['parent_idx'] == $parent_idx) {
				$descendants[] = $type['type_idx'];

				// 재귀적으로 하위 유형 조회
				$child_descendants = $this->get_descendants_recursive($type['type_idx'], $all_types);
				$descendants = array_merge($descendants, $child_descendants);
			}
		}

		return $descendants;
	}

	public function get_types_for_select_filtered($visible_types)
	{
		if (empty($visible_types)) {
			return $this->get_types_for_select();
		}

		// visible_types와 그 하위 유형들의 ID 수집
		$type_ids = $this->get_type_with_descendants_public($visible_types);

		if (empty($type_ids)) {
			return array();
		}

		// 전체 유형 조회
		$types = $this->get_all_types();

		// 필터링: type_ids에 포함된 유형만
		$filtered_types = array_filter($types, function($cat) use ($type_ids) {
			return in_array($cat['type_idx'], $type_ids);
		});

		if (empty($filtered_types)) {
			return array();
		}

		// 계층구조 옵션 생성
		return $this->build_type_select_options($filtered_types, null, 0);
	}

}
