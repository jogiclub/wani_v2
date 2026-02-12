<?php
/**
 * 파일 위치: application/models/Moim_model.php
 * 역할: 소모임 관리 데이터베이스 작업 처리
 */

class Moim_model extends CI_Model
{
	public function __construct()
	{
		parent::__construct();
		$this->load->database();
	}

	/**
	 * 카테고리 트리 조회
	 */
	public function get_category_tree($org_id)
	{
		$this->db->select('category_json');
		$this->db->from('wb_moim_category');
		$this->db->where('org_id', $org_id);
		$this->db->where('category_type', 'moim');
		$query = $this->db->get();

		if ($query->num_rows() > 0) {
			$result = $query->row_array();
			$category_json = json_decode($result['category_json'], true);

			if (isset($category_json['categories']) && is_array($category_json['categories'])) {
				return $category_json['categories'];
			}
		}

		return array();
	}

	/**
	 * 기본 카테고리 생성
	 */
	public function create_default_categories($org_id, $user_id)
	{
		// 기본 카테고리 구조
		$default_categories = array(
			'categories' => array(
				array(
					'code' => 'MOIM_CLUB',
					'name' => '동아리모임',
					'order' => 1,
					'positions' => array('회장', '부회장', '총무', '회계', '회원'),
					'children' => array()
				),
				array(
					'code' => 'MOIM_MISSION',
					'name' => '선교회모임',
					'order' => 2,
					'positions' => array('회장', '부회장', '총무', '회계', '회원'),
					'children' => array()
				),
				array(
					'code' => 'MOIM_TEACHER',
					'name' => '교사모임',
					'order' => 3,
					'positions' => array('회장', '부회장', '총무', '회계', '회원'),
					'children' => array()
				)
			)
		);

		$category_json = json_encode($default_categories, JSON_UNESCAPED_UNICODE);

		$insert_data = array(
			'org_id' => $org_id,
			'category_type' => 'moim',
			'category_json' => $category_json,
			'user_id' => $user_id,
			'regi_date' => date('Y-m-d H:i:s')
		);

		$result = $this->db->insert('wb_moim_category', $insert_data);

		if ($result) {
			log_message('info', "소모임 기본 카테고리 생성 완료 - org_id: {$org_id}");
		} else {
			log_message('error', "소모임 기본 카테고리 생성 실패 - org_id: {$org_id}");
		}

		return $result;
	}

	/**
	 * 카테고리별 회원 수 조회
	 */
	public function get_category_member_counts($org_id)
	{
		$this->db->select('category_code, COUNT(*) as count');
		$this->db->from('wb_moim');
		$this->db->where('org_id', $org_id);
		$this->db->where('del_yn', 'N');
		$this->db->group_by('category_code');
		$query = $this->db->get();

		$counts = array();
		foreach ($query->result_array() as $row) {
			if (!empty($row['category_code'])) {
				$counts[$row['category_code']] = (int)$row['count'];
			}
		}

		return $counts;
	}

	/**
	 * 전체 소모임 회원 수 조회
	 */
	public function get_total_member_count($org_id)
	{
		$this->db->from('wb_moim');
		$this->db->where('org_id', $org_id);
		$this->db->where('del_yn', 'N');
		return $this->db->count_all_results();
	}

	/**
	 * 조직별 소모임 회원 목록 조회
	 */
	public function get_moim_members_by_org($org_id)
	{
		$this->db->select('
			mo.moim_idx,
			mo.org_id,
			mo.category_code,
			mo.member_idx,
			mo.moim_position,
			mo.moim_desc,
			mo.regi_date,
			m.member_name,
			m.member_sex,
			m.member_phone,
			m.member_birth,
			m.position_name,
			m.duty_name,
			m.address,
			m.member_address,
			m.member_address_detail,
			m.photo
		');
		$this->db->from('wb_moim mo');
		$this->db->join('wb_member m', 'mo.member_idx = m.member_idx', 'left');
		$this->db->where('mo.org_id', $org_id);
		$this->db->where('mo.del_yn', 'N');
		$this->db->where('m.del_yn', 'N');
		$this->db->order_by('mo.category_code', 'ASC');
		$this->db->order_by('mo.regi_date', 'ASC');
		$query = $this->db->get();

		return $this->process_member_list($query->result_array());
	}

	/**
	 * 카테고리별 소모임 회원 목록 조회
	 */
	public function get_moim_members_by_category($org_id, $category_code)
	{
		$this->db->select('
			mo.moim_idx,
			mo.org_id,
			mo.category_code,
			mo.member_idx,
			mo.moim_position,
			mo.moim_desc,
			mo.regi_date,
			m.member_name,
			m.member_sex,
			m.member_phone,
			m.member_birth,
			m.position_name,
			m.duty_name,
			m.address,
			m.member_address,
			m.member_address_detail,
			m.photo
		');
		$this->db->from('wb_moim mo');
		$this->db->join('wb_member m', 'mo.member_idx = m.member_idx', 'left');
		$this->db->where('mo.org_id', $org_id);
		$this->db->where('mo.category_code', $category_code);
		$this->db->where('mo.del_yn', 'N');
		$this->db->where('m.del_yn', 'N');
		$this->db->order_by('mo.regi_date', 'ASC');
		$query = $this->db->get();

		return $this->process_member_list($query->result_array());
	}

	/**
	 * 회원 목록 데이터 가공
	 */
	private function process_member_list($member_list)
	{
		foreach ($member_list as &$member) {
			// 성별 한글 변환
			$gender_map = array(
				'male' => '남',
				'female' => '여'
			);
			$member['member_sex_str'] = isset($gender_map[$member['member_sex']]) ? $gender_map[$member['member_sex']] : '';

			// 생년월일 포맷
			if (!empty($member['member_birth'])) {
				$member['member_birth_formatted'] = substr($member['member_birth'], 0, 4) . '-' .
					substr($member['member_birth'], 4, 2) . '-' .
					substr($member['member_birth'], 6, 2);
			} else {
				$member['member_birth_formatted'] = '';
			}

			// 주소 조합
			$address_parts = array();
			if (!empty($member['address'])) {
				$address_parts[] = $member['address'];
			}
			if (!empty($member['member_address'])) {
				$address_parts[] = $member['member_address'];
			}
			$member['address_full'] = implode(' ', $address_parts);

			// 이미지 경로 처리
			if (!empty($member['photo'])) {
				$photo = $member['photo'];
				if (strpos($photo, '/') === 0 || strpos($photo, 'http') === 0) {
					// 이미 전체 경로
				} else {
					$member['photo'] = '/uploads/member_photos/' . $member['org_id'] . '/' . $photo;
				}
			} else {
				$member['photo'] = '/assets/images/photo_no.png';
			}
		}

		return $member_list;
	}

	/**
	 * 소모임 정보 조회
	 */
	public function get_moim_by_idx($moim_idx)
	{
		$this->db->select('*');
		$this->db->from('wb_moim');
		$this->db->where('moim_idx', $moim_idx);
		$this->db->where('del_yn', 'N');
		$query = $this->db->get();

		return $query->row_array();
	}

	/**
	 * 중복 회원 체크
	 */
	public function check_duplicate_member($org_id, $category_code, $member_idx)
	{
		$this->db->from('wb_moim');
		$this->db->where('org_id', $org_id);
		$this->db->where('category_code', $category_code);
		$this->db->where('member_idx', $member_idx);
		$this->db->where('del_yn', 'N');

		return $this->db->count_all_results() > 0;
	}

	/**
	 * 소모임 회원 추가
	 */
	public function insert_moim_member($moim_data)
	{
		$insert_data = array_merge($moim_data, array(
			'regi_date' => date('Y-m-d H:i:s')
		));

		return $this->db->insert('wb_moim', $insert_data);
	}

	/**
	 * 소모임 회원 정보 수정
	 */
	public function update_moim_member($moim_idx, $moim_data)
	{
		$update_data = array_merge($moim_data, array(
			'modi_date' => date('Y-m-d H:i:s')
		));

		$this->db->where('moim_idx', $moim_idx);
		return $this->db->update('wb_moim', $update_data);
	}

	/**
	 * 소모임 회원 삭제 (소프트 삭제)
	 */
	public function delete_moim_member($moim_idx)
	{
		$delete_data = array(
			'del_yn' => 'Y',
			'del_date' => date('Y-m-d H:i:s'),
			'modi_date' => date('Y-m-d H:i:s')
		);

		$this->db->where('moim_idx', $moim_idx);
		return $this->db->update('wb_moim', $delete_data);
	}

	/**
	 * 선택된 소모임 회원 일괄 삭제 (소프트 삭제)
	 */
	public function delete_moim_members($moim_indices)
	{
		if (empty($moim_indices)) {
			return 0;
		}

		$delete_data = array(
			'del_yn' => 'Y',
			'del_date' => date('Y-m-d H:i:s'),
			'modi_date' => date('Y-m-d H:i:s')
		);

		$this->db->where_in('moim_idx', $moim_indices);
		$this->db->update('wb_moim', $delete_data);

		return $this->db->affected_rows();
	}

	/**
	 * 카테고리 추가
	 */
	public function add_category($org_id, $user_id, $category_name, $parent_code = null)
	{
		$categories_data = $this->_get_categories_data($org_id);
		if (!$categories_data) return false;

		$new_code = 'MOIM_' . strtoupper(substr(md5(uniqid(rand(), true)), 0, 8));
		$new_category = [
			'code' => $new_code,
			'name' => $category_name,
			'order' => 999, // 순서는 마지막으로
			'positions' => ['회장', '부회장', '총무', '회계', '회원'], // 기본 직책
			'children' => []
		];

		if ($parent_code) {
			$parent_node = null;
			$this->_find_node($categories_data['categories'], $parent_code, $parent_node);
			if ($parent_node) {
				$parent_node['children'][] = $new_category;
				$this->_update_node($categories_data['categories'], $parent_code, $parent_node);
			} else {
				return false; // 부모 노드 없음
			}
		} else {
			$categories_data['categories'][] = $new_category;
		}

		return $this->_save_categories_data($org_id, $user_id, $categories_data);
	}

	/**
	 * 카테고리명 변경
	 */
	public function rename_category($org_id, $user_id, $category_code, $new_name)
	{
		$categories_data = $this->_get_categories_data($org_id);
		if (!$categories_data) return false;

		$node = null;
		$this->_find_node($categories_data['categories'], $category_code, $node);

		if ($node) {
			$node['name'] = $new_name;
			$this->_update_node($categories_data['categories'], $category_code, $node);
			return $this->_save_categories_data($org_id, $user_id, $categories_data);
		}

		return false;
	}

	/**
	 * 카테고리 삭제
	 */
	public function delete_category($org_id, $user_id, $category_code)
	{
		$categories_data = $this->_get_categories_data($org_id);
		if (!$categories_data) return false;

		$this->_remove_node($categories_data['categories'], $category_code);

		return $this->_save_categories_data($org_id, $user_id, $categories_data);
	}

	/**
	 * 카테고리 이동
	 */
	public function move_category($org_id, $user_id, $source_code, $target_code, $hit_mode)
	{
		$categories_data = $this->_get_categories_data($org_id);
		if (!$categories_data) return false;

		$source_node = null;
		$this->_find_node($categories_data['categories'], $source_code, $source_node);

		if (!$source_node) return false;

		// 1. 원본 노드 삭제
		$this->_remove_node($categories_data['categories'], $source_code);

		// 2. 대상 위치에 노드 삽입
		$this->_insert_node($categories_data['categories'], $source_node, $target_code, $hit_mode);

		return $this->_save_categories_data($org_id, $user_id, $categories_data);
	}

	/**
	 * 하위 카테고리 또는 소속된 회원이 있는지 확인
	 */
	public function has_children_or_members($org_id, $category_code)
	{
		// 1. 하위 카테고리 확인
		$categories_data = $this->_get_categories_data($org_id);
		$node = null;
		$this->_find_node($categories_data['categories'], $category_code, $node);

		if ($node && !empty($node['children'])) {
			return true;
		}

		// 2. 소속된 회원 확인
		$this->db->where('org_id', $org_id);
		$this->db->where('category_code', $category_code);
		$this->db->where('del_yn', 'N');
		$count = $this->db->count_all_results('wb_moim');

		return $count > 0;
	}


	// =======================================================================
	// Private Helper Functions for Category JSON Manipulation
	// =======================================================================

	private function _get_categories_data($org_id)
	{
		$this->db->select('category_id, category_json');
		$this->db->from('wb_moim_category');
		$this->db->where('org_id', $org_id);
		$this->db->where('category_type', 'moim');
		$query = $this->db->get();

		if ($query->num_rows() > 0) {
			$row = $query->row_array();
			return [
				'category_id' => $row['category_id'],
				'categories' => json_decode($row['category_json'], true)['categories'] ?? []
			];
		}
		return null;
	}

	private function _save_categories_data($org_id, $user_id, $categories_data)
	{
		$this->_reorder_nodes($categories_data['categories']);
		$json_to_save = json_encode(['categories' => $categories_data['categories']], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

		$data = [
			'category_json' => $json_to_save,
			'user_id' => $user_id,
			'modi_date' => date('Y-m-d H:i:s')
		];

		$this->db->where('category_id', $categories_data['category_id']);
		return $this->db->update('wb_moim_category', $data);
	}

	private function _find_node(&$nodes, $code, &$found_node)
	{
		foreach ($nodes as &$node) {
			if ($node['code'] === $code) {
				$found_node = $node;
				return true;
			}
			if (!empty($node['children'])) {
				if ($this->_find_node($node['children'], $code, $found_node)) {
					return true;
				}
			}
		}
		return false;
	}

	private function _update_node(&$nodes, $code, $updated_node)
	{
		foreach ($nodes as &$node) {
			if ($node['code'] === $code) {
				$node = $updated_node;
				return true;
			}
			if (!empty($node['children'])) {
				if ($this->_update_node($node['children'], $code, $updated_node)) {
					return true;
				}
			}
		}
		return false;
	}

	private function _remove_node(&$nodes, $code)
	{
		foreach ($nodes as $i => &$node) {
			if ($node['code'] === $code) {
				array_splice($nodes, $i, 1);
				return true;
			}
			if (!empty($node['children'])) {
				if ($this->_remove_node($node['children'], $code)) {
					return true;
				}
			}
		}
		return false;
	}

	private function _insert_node(&$nodes, $source_node, $target_code, $hit_mode)
	{
		if ($hit_mode === 'over') {
			if ($target_code === null) { // 최상위
				$nodes[] = $source_node;
			} else {
				$target_parent = null;
				$this->_find_node($nodes, $target_code, $target_parent);
				if ($target_parent) {
					$target_parent['children'][] = $source_node;
					$this->_update_node($nodes, $target_code, $target_parent);
				}
			}
		} else { // before, after
			$inserted = false;
			foreach ($nodes as $i => &$node) {
				if ($node['code'] === $target_code) {
					if ($hit_mode === 'before') {
						array_splice($nodes, $i, 0, [$source_node]);
					} else { // after
						array_splice($nodes, $i + 1, 0, [$source_node]);
					}
					$inserted = true;
					break;
				}
			}

			if (!$inserted) { // 대상이 하위 레벨에 있을 경우
				foreach ($nodes as &$node) {
					if (!empty($node['children'])) {
						if ($this->_insert_node($node['children'], $source_node, $target_code, $hit_mode)) {
							$inserted = true;
							break;
						}
					}
				}
			}
			return $inserted;
		}
	}

	private function _reorder_nodes(&$nodes)
	{
		$order = 1;
		foreach ($nodes as &$node) {
			$node['order'] = $order++;
			if (!empty($node['children'])) {
				$this->_reorder_nodes($node['children']);
			}
		}
	}
}
