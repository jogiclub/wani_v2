<?php
// 파일 위치: application/models/Transfer_org_model.php
// 역할: 파송교회(결연교회) 관련 데이터베이스 작업 처리

class Transfer_org_model extends CI_Model
{
	public function __construct()
	{
		parent::__construct();
		$this->load->database();
	}

	/**
	 * 파송교회 목록 조회
	 */
	public function get_member_transfer_orgs($member_idx, $org_id)
	{
		$this->db->select('transfer_org_json');
		$this->db->from('wb_member');
		$this->db->where('member_idx', $member_idx);
		$this->db->where('org_id', $org_id);
		$query = $this->db->get();
		$row = $query->row_array();

		$transfer_org_ids = [];
		if ($row && !empty($row['transfer_org_json'])) {
			$ids_from_json = json_decode($row['transfer_org_json'], true);
			if (is_array($ids_from_json)) {
				$transfer_org_ids = array_map('strval', array_unique(array_filter($ids_from_json)));
			}
		}

		if (empty($transfer_org_ids)) {
			return array();
		}

		$this->db->select('transfer_org_id AS idx, transfer_org_name, transfer_org_address, transfer_org_phone, transfer_org_rep, transfer_org_manager, transfer_org_email, transfer_org_desc, transfer_org_tag, regi_date, modi_date');
		$this->db->from('wb_transfer_org');
		$this->db->where_in('transfer_org_id', $transfer_org_ids);
		$this->db->where('del_yn', 'N');
		$this->db->order_by('regi_date', 'DESC');

		$query = $this->db->get();
		return $query->result_array();
	}

	/**
	 * 파송교회 추가
	 */
	public function insert_transfer_org($data)
	{
		$insert_data = [
			'transfer_org_name' => $data['transfer_name'] ?? null,
			'transfer_org_address' => $data['transfer_region'] ?? null,
			'transfer_org_phone' => $data['contact_phone'] ?? null,
			'transfer_org_rep' => $data['pastor_name'] ?? null,
			'transfer_org_manager' => $data['contact_person'] ?? null,
			'transfer_org_email' => $data['contact_email'] ?? null,
			'transfer_org_desc' => $data['transfer_description'] ?? null,
			'transfer_org_tag' => $data['org_tag'] ?? null,
			'regi_date' => $data['regi_date'],
			'modi_date' => $data['modi_date'],
			'del_yn' => $data['del_yn']
		];

		$this->db->trans_start();

		$this->db->insert('wb_transfer_org', $insert_data);
		$insert_id = $this->db->insert_id();

		if ($insert_id) {
			$this->update_member_transfer_orgs_json_link($data['member_idx'], $data['org_id'], $insert_id, 'add');
		}

		$this->db->trans_complete();

		return $this->db->trans_status() === TRUE ? $insert_id : false;
	}

	/**
	 * 파송교회 수정
	 */
	public function update_transfer_org($transfer_org_id, $org_id, $data)
	{
		$member_idx = $data['member_idx'];

		$update_data = [
			'transfer_org_name' => $data['transfer_name'] ?? null,
			'transfer_org_address' => $data['transfer_region'] ?? null,
			'transfer_org_phone' => $data['contact_phone'] ?? null,
			'transfer_org_rep' => $data['pastor_name'] ?? null,
			'transfer_org_manager' => $data['contact_person'] ?? null,
			'transfer_org_email' => $data['contact_email'] ?? null,
			'transfer_org_desc' => $data['transfer_description'] ?? null,
			'transfer_org_tag' => $data['org_tag'] ?? null,
			'modi_date' => $data['modi_date']
		];

		if (isset($update_data['transfer_org_id'])) {
			unset($update_data['transfer_org_id']);
		}

		$this->db->trans_start();

		$this->db->where('transfer_org_id', $transfer_org_id);
		$this->db->where('del_yn', 'N');
		$result = $this->db->update('wb_transfer_org', $update_data);

		if ($result) {
			$this->update_member_transfer_orgs_json_link($member_idx, $org_id, $transfer_org_id, 'add');
		}

		$this->db->trans_complete();

		return $this->db->trans_status() === TRUE ? $result : false;
	}

	/**
	 * 파송교회 삭제 (논리적 삭제)
	 */
	public function delete_transfer_org($transfer_org_id, $org_id, $member_idx)
	{
		$data = [
			'del_yn' => 'Y',
			'del_date' => date('Y-m-d H:i:s'),
			'modi_date' => date('Y-m-d H:i:s')
		];

		$this->db->trans_start();

		$this->db->where('transfer_org_id', $transfer_org_id);
		$result = $this->db->update('wb_transfer_org', $data);

		if ($result) {
			$this->update_member_transfer_orgs_json_link($member_idx, $org_id, $transfer_org_id, 'delete');
		}

		$this->db->trans_complete();

		return $this->db->trans_status() === TRUE ? $result : false;
	}

	/**
	 * 교회 검색 (교회명, 담임목사, 지역, 태그)
	 */
	public function search_churches($org_id, $search_type, $keyword)
	{
		// wb_org 테이블의 실제 필드명 사용
		$this->db->select('org_id AS idx, org_name, org_address, org_phone, org_rep, org_manager, org_desc, org_tag');
		$this->db->from('wb_org');
		$this->db->where('org_id !=', $org_id); // org_id로 수정
		$this->db->where('del_yn', 'N');

		switch ($search_type) {
			case 'church_name':
				$this->db->like('org_name', $keyword);
				break;
			case 'pastor_name':
				$this->db->like('org_rep', $keyword);
				break;
			case 'region':
				$this->db->like('org_address', $keyword);
				break;
			case 'tag':
				$this->db->like('org_tag', $keyword);
				break;
		}

		$this->db->limit(50);
		$query = $this->db->get();

		return $query->result_array();
	}

	/**
	 * 회원 주소 기반 동일 지역 교회 검색
	 */
	public function search_churches_by_member_address($member_idx, $org_id)
	{
		// 회원 주소 정보 가져오기
		$this->db->select('member_address');
		$this->db->from('wb_member');
		$this->db->where('member_idx', $member_idx);
		$member_query = $this->db->get();
		$member = $member_query->row_array();

		if (empty($member) || empty($member['member_address'])) {
			return [];
		}

		$address = $member['member_address'];
		$address_parts = explode(' ', $address);

		$search_keywords = [];
		if (isset($address_parts[0])) {
			$search_keywords[] = $address_parts[0];
		}
		if (isset($address_parts[1])) {
			$search_keywords[] = $address_parts[0] . ' ' . $address_parts[1];
		}

		if (empty($search_keywords)) {
			return [];
		}

		// wb_org 테이블의 실제 필드명 사용
		$this->db->select('org_id AS idx, org_name, org_address, org_phone, org_rep, org_manager, org_desc, org_tag');
		$this->db->from('wb_org');
		$this->db->where('org_id !=', $org_id); // org_id로 수정
		$this->db->where('del_yn', 'N');

		$this->db->group_start();
		foreach ($search_keywords as $keyword) {
			$this->db->or_like('org_address', $keyword);
		}
		$this->db->group_end();

		$this->db->limit(20);
		$query = $this->db->get();

		return $query->result_array();
	}

	/**
	 * 선택된 교회들을 파송교회로 일괄 추가
	 */
	public function insert_matched_churches($member_idx, $org_id, $churches)
	{
		$this->db->trans_start();

		$success_count = 0;
		$skip_count = 0;

		foreach ($churches as $church) {
			// 중복 체크 - idx가 org_id를 의미함
			if ($this->is_already_linked($member_idx, $church['idx'])) {
				$skip_count++;
				continue;
			}

			// wb_transfer_org에 삽입 - 실제 필드명 사용
			$insert_data = [
				'transfer_org_name' => $church['org_name'] ?? '',
				'transfer_org_address' => $church['org_address'] ?? '',
				'transfer_org_phone' => $church['org_phone'] ?? '',
				'transfer_org_rep' => $church['org_rep'] ?? '',
				'transfer_org_manager' => $church['org_manager'] ?? '',
				'transfer_org_desc' => $church['org_desc'] ?? '',
				'transfer_org_tag' => $church['org_tag'] ?? '',
				'regi_date' => date('Y-m-d H:i:s'),
				'modi_date' => date('Y-m-d H:i:s'),
				'del_yn' => 'N'
			];

			$this->db->insert('wb_transfer_org', $insert_data);
			$new_transfer_org_id = $this->db->insert_id();

			if ($new_transfer_org_id) {
				$this->update_member_transfer_orgs_json_link($member_idx, $org_id, $new_transfer_org_id, 'add');
				$success_count++;
			}
		}

		$this->db->trans_complete();

		return [
			'success' => $this->db->trans_status() === TRUE,
			'success_count' => $success_count,
			'skip_count' => $skip_count
		];
	}

	/**
	 * 이미 연결된 교회인지 확인
	 */
	private function is_already_linked($member_idx, $org_idx)
	{
		$this->db->select('transfer_org_json');
		$this->db->from('wb_member');
		$this->db->where('member_idx', $member_idx);
		$query = $this->db->get();
		$row = $query->row_array();

		if (empty($row) || empty($row['transfer_org_json'])) {
			return false;
		}

		$linked_ids = json_decode($row['transfer_org_json'], true);
		if (!is_array($linked_ids)) {
			return false;
		}

		// wb_org에서 org_id로 주소 조회
		$this->db->select('org_address');
		$this->db->from('wb_org');
		$this->db->where('org_id', $org_idx); // org_id 사용
		$org_query = $this->db->get();
		$org = $org_query->row_array();

		if (empty($org)) {
			return false;
		}

		// 동일한 주소의 파송교회가 이미 있는지 확인
		$this->db->select('transfer_org_id');
		$this->db->from('wb_transfer_org');
		$this->db->where_in('transfer_org_id', $linked_ids);
		$this->db->where('transfer_org_address', $org['org_address']);
		$this->db->where('del_yn', 'N');
		$existing = $this->db->get();

		return $existing->num_rows() > 0;
	}

	/**
	 * wb_member.transfer_org_json 필드 업데이트
	 */
	public function update_member_transfer_orgs_json_link($member_idx, $org_id, $id_to_link, $action)
	{
		$this->db->select('transfer_org_json');
		$this->db->from('wb_member');
		$this->db->where('member_idx', $member_idx);
		$query = $this->db->get();
		$row = $query->row_array();

		$current_ids = [];
		if ($row && !empty($row['transfer_org_json'])) {
			$ids_from_json = json_decode($row['transfer_org_json'], true);
			if (is_array($ids_from_json)) {
				$current_ids = array_map('strval', array_unique(array_filter($ids_from_json)));
			}
		}

		$id_to_link_str = (string)$id_to_link;

		if ($action === 'add') {
			if (!in_array($id_to_link_str, $current_ids)) {
				$current_ids[] = $id_to_link_str;
			}
		} elseif ($action === 'delete') {
			$current_ids = array_values(array_diff($current_ids, [$id_to_link_str]));
		}

		$json_data = json_encode($current_ids, JSON_NUMERIC_CHECK | JSON_UNESCAPED_UNICODE);

		$this->db->where('member_idx', $member_idx);
		$this->db->where('org_id', $org_id);
		return $this->db->update('wb_member', [
			'transfer_org_json' => $json_data,
			'modi_date' => date('Y-m-d H:i:s')
		]);
	}

	/**
	 * 선택 가능한 결연교회 목록 조회
	 */
	public function get_available_churches()
	{
		$this->db->select('category_idx');
		$this->db->from('wb_org_category');
		$this->db->where('category_idx', '160');
		$category_query = $this->db->get();

		if ($category_query->num_rows() == 0) {
			return [];
		}

		$category_idx = $category_query->row()->category_idx;

		$category_indices = $this->get_child_categories($category_idx);
		$category_indices[] = $category_idx;

		// wb_org 테이블의 실제 필드명 사용
		$this->db->select('org_id, org_name, org_address, org_phone, org_rep');
		$this->db->from('wb_org');
		$this->db->where_in('category_idx', $category_indices);
		$this->db->where('del_yn', 'N');
		$this->db->order_by('org_name', 'ASC');

		$query = $this->db->get();
		return $query->result_array();
	}

	/**
	 * 하위 카테고리 재귀 조회
	 */
	private function get_child_categories($parent_idx)
	{
		$result = [];

		$this->db->select('category_idx');
		$this->db->from('wb_org_category');
		$this->db->where('parent_idx', $parent_idx);
		$query = $this->db->get();

		foreach ($query->result_array() as $row) {
			$result[] = $row['category_idx'];
			$children = $this->get_child_categories($row['category_idx']);
			$result = array_merge($result, $children);
		}

		return $result;
	}

	/**
	 * 회원의 파송교회 주소 목록 조회
	 */
	private function get_member_transfer_org_addresses($member_idx, $org_id)
	{
		$this->db->select('transfer_org_json');
		$this->db->from('wb_member');
		$this->db->where('member_idx', $member_idx);
		$this->db->where('org_id', $org_id);
		$query = $this->db->get();

		if ($query->num_rows() == 0) {
			return [];
		}

		$row = $query->row_array();
		$addresses = [];

		if (!empty($row['transfer_org_json'])) {
			$ids_from_json = json_decode($row['transfer_org_json'], true);
			if (is_array($ids_from_json) && !empty($ids_from_json)) {
				$this->db->select('transfer_org_address');
				$this->db->from('wb_transfer_org');
				$this->db->where_in('transfer_org_id', $ids_from_json);
				$this->db->where('del_yn', 'N');
				$query2 = $this->db->get();

				foreach ($query2->result_array() as $row2) {
					if (!empty($row2['transfer_org_address'])) {
						$addresses[] = $row2['transfer_org_address'];
					}
				}
			}
		}

		return $addresses;
	}
}
