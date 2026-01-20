<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * 회원 가족 관계 모델
 *
 * 관계 저장 규칙:
 * - spouse: 양방향 저장 (A->B, B->A)
 * - parent: 단방향 (자녀->부모로 저장), 역방향은 child로 조회
 * - child: 단방향 (부모->자녀로 저장), 역방향은 parent로 조회
 */
class Member_family_model extends CI_Model {

	private $table = 'wb_member_family';

	public function __construct() {
		parent::__construct();
	}

	/**
	 * 가족 관계 추가
	 */
	public function add_relation($org_id, $member_idx, $related_member_idx, $relation_type) {
		// 이미 존재하는지 확인
		if ($this->relation_exists($member_idx, $related_member_idx, $relation_type)) {
			return true;
		}

		$data = [
			'org_id' => $org_id,
			'member_idx' => $member_idx,
			'related_member_idx' => $related_member_idx,
			'relation_type' => $relation_type,
			'regi_date' => date('Y-m-d H:i:s')
		];

		$result = $this->db->insert($this->table, $data);

		// 배우자 관계는 양방향 저장
		if ($result && $relation_type === 'spouse') {
			if (!$this->relation_exists($related_member_idx, $member_idx, 'spouse')) {
				$reverse_data = [
					'org_id' => $org_id,
					'member_idx' => $related_member_idx,
					'related_member_idx' => $member_idx,
					'relation_type' => 'spouse',
					'regi_date' => date('Y-m-d H:i:s')
				];
				$this->db->insert($this->table, $reverse_data);
			}
		}

		// 부모-자녀 관계도 양방향 저장
		if ($result && $relation_type === 'parent') {
			// 역방향: 부모 입장에서는 자녀
			if (!$this->relation_exists($related_member_idx, $member_idx, 'child')) {
				$reverse_data = [
					'org_id' => $org_id,
					'member_idx' => $related_member_idx,
					'related_member_idx' => $member_idx,
					'relation_type' => 'child',
					'regi_date' => date('Y-m-d H:i:s')
				];
				$this->db->insert($this->table, $reverse_data);
			}
		}

		if ($result && $relation_type === 'child') {
			// 역방향: 자녀 입장에서는 부모
			if (!$this->relation_exists($related_member_idx, $member_idx, 'parent')) {
				$reverse_data = [
					'org_id' => $org_id,
					'member_idx' => $related_member_idx,
					'related_member_idx' => $member_idx,
					'relation_type' => 'parent',
					'regi_date' => date('Y-m-d H:i:s')
				];
				$this->db->insert($this->table, $reverse_data);
			}
		}

		return $result;
	}

	/**
	 * 관계 존재 여부 확인
	 */
	public function relation_exists($member_idx, $related_member_idx, $relation_type) {
		$this->db->where('member_idx', $member_idx);
		$this->db->where('related_member_idx', $related_member_idx);
		$this->db->where('relation_type', $relation_type);
		return $this->db->count_all_results($this->table) > 0;
	}

	/**
	 * 특정 회원의 모든 가족 관계 조회
	 */
	public function get_family_relations($member_idx) {
		$this->db->select('f.*, m.member_name, m.member_sex, m.member_birth, m.photo, m.org_id');
		$this->db->from($this->table . ' f');
		$this->db->join('wb_member m', 'f.related_member_idx = m.member_idx', 'left');
		$this->db->where('f.member_idx', $member_idx);
		$this->db->where('m.del_yn', 'N');

		return $this->db->get()->result_array();
	}

	/**
	 * 특정 회원의 배우자 조회
	 */
	public function get_spouses($member_idx) {
		return $this->get_relations_by_type($member_idx, 'spouse');
	}

	/**
	 * 특정 회원의 부모 조회
	 */
	public function get_parents($member_idx) {
		return $this->get_relations_by_type($member_idx, 'parent');
	}

	/**
	 * 특정 회원의 자녀 조회
	 */
	public function get_children($member_idx) {
		return $this->get_relations_by_type($member_idx, 'child');
	}

	/**
	 * 관계 유형별 조회
	 */
	private function get_relations_by_type($member_idx, $relation_type) {
		$this->db->select('f.*, m.member_name, m.member_sex, m.member_birth, m.photo, m.org_id');
		$this->db->from($this->table . ' f');
		$this->db->join('wb_member m', 'f.related_member_idx = m.member_idx', 'left');
		$this->db->where('f.member_idx', $member_idx);
		$this->db->where('f.relation_type', $relation_type);
		$this->db->where('m.del_yn', 'N');

		return $this->db->get()->result_array();
	}

	/**
	 * 가족 관계 삭제
	 */
	public function remove_relation($member_idx, $related_member_idx, $relation_type = null) {
		// 정방향 삭제
		$this->db->where('member_idx', $member_idx);
		$this->db->where('related_member_idx', $related_member_idx);
		if ($relation_type) {
			$this->db->where('relation_type', $relation_type);
		}
		$this->db->delete($this->table);

		// 역방향 삭제
		$reverse_type = $relation_type;
		if ($relation_type === 'parent') {
			$reverse_type = 'child';
		} else if ($relation_type === 'child') {
			$reverse_type = 'parent';
		}

		$this->db->where('member_idx', $related_member_idx);
		$this->db->where('related_member_idx', $member_idx);
		if ($reverse_type) {
			$this->db->where('relation_type', $reverse_type);
		}
		$this->db->delete($this->table);

		return true;
	}

	/**
	 * 특정 회원의 모든 가족 관계 삭제
	 */
	public function remove_all_relations($member_idx) {
		// 해당 회원이 member_idx인 관계 삭제
		$this->db->where('member_idx', $member_idx);
		$this->db->delete($this->table);

		// 해당 회원이 related_member_idx인 관계 삭제
		$this->db->where('related_member_idx', $member_idx);
		$this->db->delete($this->table);

		return true;
	}

	/**
	 * family-chart.js용 데이터 구조 생성
	 */
	public function build_family_chart_data($member_idx) {
		$this->load->model('Member_model');

		// 본인 정보
		$main_member = $this->Member_model->get_member_by_idx($member_idx);
		if (!$main_member) {
			return [];
		}

		$chart_data = [];
		$processed_ids = [];

		// 본인 노드 추가
		$chart_data[] = $this->create_chart_node($main_member, '0', $member_idx);
		$processed_ids[$member_idx] = '0';

		// 관계 조회 및 노드 추가 (재귀적으로 확장)
		$this->add_related_nodes($member_idx, $chart_data, $processed_ids, 0, 2);

		return $chart_data;
	}

	/**
	 * 관계된 노드들 추가 (재귀)
	 */
	private function add_related_nodes($member_idx, &$chart_data, &$processed_ids, $depth, $max_depth) {
		if ($depth >= $max_depth) {
			return;
		}

		$relations = $this->get_family_relations($member_idx);
		$current_node_id = $processed_ids[$member_idx];

		foreach ($relations as $relation) {
			$related_idx = $relation['related_member_idx'];

			// 이미 처리된 회원이면 관계만 연결
			if (isset($processed_ids[$related_idx])) {
				$this->link_existing_node($chart_data, $current_node_id, $processed_ids[$related_idx], $relation['relation_type']);
				continue;
			}

			// 새 노드 ID 생성
			$new_node_id = $this->generate_uuid();
			$processed_ids[$related_idx] = $new_node_id;

			// 새 노드 추가
			$node = $this->create_chart_node($relation, $new_node_id, $related_idx);
			$chart_data[] = $node;

			// 관계 연결
			$this->link_nodes($chart_data, $current_node_id, $new_node_id, $relation['relation_type']);

			// 재귀적으로 관계 확장
			$this->add_related_nodes($related_idx, $chart_data, $processed_ids, $depth + 1, $max_depth);
		}
	}

	/**
	 * 차트 노드 생성
	 */
	private function create_chart_node($member_data, $node_id, $member_idx) {
		$photo = '';
		if (!empty($member_data['photo'])) {
			$photo = $member_data['photo'];
			if (strpos($photo, '/') !== 0 && strpos($photo, 'http') !== 0) {
				$photo = '/uploads/member_photos/' . $member_data['org_id'] . '/' . $photo;
			}
		}

		return [
			'id' => $node_id,
			'member_idx' => (int)$member_idx,
			'data' => [
				'first name' => $member_data['member_name'] ?? '',
				'last name' => '',
				'birthday' => $member_data['member_birth'] ?? '',
				'avatar' => $photo,
				'gender' => ($member_data['member_sex'] === 'female') ? 'F' : 'M'
			],
			'rels' => [
				'spouses' => [],
				'children' => [],
				'parents' => []
			]
		];
	}

	/**
	 * 노드 간 관계 연결
	 */
	private function link_nodes(&$chart_data, $node_id_1, $node_id_2, $relation_type) {
		foreach ($chart_data as &$node) {
			if ($node['id'] === $node_id_1) {
				switch ($relation_type) {
					case 'spouse':
						if (!in_array($node_id_2, $node['rels']['spouses'])) {
							$node['rels']['spouses'][] = $node_id_2;
						}
						break;
					case 'parent':
						if (!in_array($node_id_2, $node['rels']['parents'])) {
							$node['rels']['parents'][] = $node_id_2;
						}
						break;
					case 'child':
						if (!in_array($node_id_2, $node['rels']['children'])) {
							$node['rels']['children'][] = $node_id_2;
						}
						break;
				}
			}

			// 역방향 연결
			if ($node['id'] === $node_id_2) {
				switch ($relation_type) {
					case 'spouse':
						if (!in_array($node_id_1, $node['rels']['spouses'])) {
							$node['rels']['spouses'][] = $node_id_1;
						}
						break;
					case 'parent':
						// 상대방 입장에서는 자녀
						if (!in_array($node_id_1, $node['rels']['children'])) {
							$node['rels']['children'][] = $node_id_1;
						}
						break;
					case 'child':
						// 상대방 입장에서는 부모
						if (!in_array($node_id_1, $node['rels']['parents'])) {
							$node['rels']['parents'][] = $node_id_1;
						}
						break;
				}
			}
		}
	}

	/**
	 * 기존 노드에 관계 연결
	 */
	private function link_existing_node(&$chart_data, $node_id_1, $node_id_2, $relation_type) {
		$this->link_nodes($chart_data, $node_id_1, $node_id_2, $relation_type);
	}

	/**
	 * UUID 생성
	 */
	private function generate_uuid() {
		return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
			mt_rand(0, 0xffff), mt_rand(0, 0xffff),
			mt_rand(0, 0xffff),
			mt_rand(0, 0x0fff) | 0x4000,
			mt_rand(0, 0x3fff) | 0x8000,
			mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
		);
	}
}
