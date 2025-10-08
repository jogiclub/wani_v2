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
	 *
	 * @param int $member_idx 회원 ID
	 * @param int $org_id 조직 ID
	 * @return array 파송교회 목록
	 */
	public function get_member_transfer_orgs($member_idx, $org_id)
	{
		// 1. wb_member.transfer_org_json에서 연결된 transfer_org_id 목록을 가져옵니다.
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

		// 2. wb_transfer_org 테이블에서 해당 ID 목록을 조회합니다.
		$this->db->select('transfer_org_id AS idx, transfer_org_name, transfer_org_address, transfer_org_phone, transfer_org_rep, transfer_org_manager, transfer_org_email, transfer_org_desc, transfer_org_tag, transfer_org_id, regi_date, modi_date');
		$this->db->from('wb_transfer_org');
		$this->db->where_in('transfer_org_id', $transfer_org_ids);
		$this->db->where('del_yn', 'N');
		$this->db->order_by('regi_date', 'DESC');

		$query = $this->db->get();
		return $query->result_array();
	}

	/**
	 * 파송교회 추가
	 *
	 * @param array $data 파송교회 데이터
	 * @return int|false 생성된 transfer_org_id 또는 false
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
			'transfer_org_id' => $data['transfer_org_id'] ?? null,
			'regi_date' => $data['regi_date'],
			'modi_date' => $data['modi_date'],
			'del_yn' => $data['del_yn']
		];

		$this->db->trans_start();

		// 1. wb_transfer_org에 상세 정보 저장
		$this->db->insert('wb_transfer_org', $insert_data);
		$insert_id = $this->db->insert_id();

		if ($insert_id) {
			// 2. wb_member.transfer_org_json 필드 업데이트 (새 ID 연결)
			$this->update_member_transfer_orgs_json_link($data['member_idx'], $data['org_id'], $insert_id, 'add');
		}

		$this->db->trans_complete();

		return $this->db->trans_status() === TRUE ? $insert_id : false;
	}

	/**
	 * 파송교회 수정
	 *
	 * @param int $transfer_org_id 파송교회 ID
	 * @param int $org_id 조직 ID
	 * @param array $data 수정할 데이터
	 * @return bool 수정 성공 여부
	 */
	public function update_transfer_org($transfer_org_id, $org_id, $data)
	{
		$member_idx = $data['member_idx'];

		// 수정할 데이터 매핑
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

		// PK가 SET 절에 포함되지 않도록 확인
		if (isset($update_data['transfer_org_id'])) {
			unset($update_data['transfer_org_id']);
		}

		$this->db->trans_start();

		// 1. wb_transfer_org 상세 정보 수정
		$this->db->where('transfer_org_id', $transfer_org_id);
		$this->db->where('del_yn', 'N');
		$result = $this->db->update('wb_transfer_org', $update_data);

		if ($result) {
			// 2. wb_member.transfer_org_json 필드 업데이트
			$this->update_member_transfer_orgs_json_link($member_idx, $org_id, $transfer_org_id, 'add');
		}

		$this->db->trans_complete();

		return $this->db->trans_status() === TRUE ? $result : false;
	}

	/**
	 * 파송교회 삭제 (논리적 삭제)
	 *
	 * @param int $transfer_org_id 파송교회 ID
	 * @param int $org_id 조직 ID
	 * @param int $member_idx 회원 ID
	 * @return bool 삭제 성공 여부
	 */
	public function delete_transfer_org($transfer_org_id, $org_id, $member_idx)
	{
		$data = [
			'del_yn' => 'Y',
			'del_date' => date('Y-m-d H:i:s'),
			'modi_date' => date('Y-m-d H:i:s')
		];

		$this->db->trans_start();

		// 1. wb_transfer_org 논리적 삭제
		$this->db->where('transfer_org_id', $transfer_org_id);
		$result = $this->db->update('wb_transfer_org', $data);

		if ($result) {
			// 2. wb_member.transfer_org_json 필드에서 해당 ID 제거
			$this->update_member_transfer_orgs_json_link($member_idx, $org_id, $transfer_org_id, 'delete');
		}

		$this->db->trans_complete();

		return $this->db->trans_status() === TRUE ? $result : false;
	}

	/**
	 * 선택 가능한 결연교회 목록 조회
	 *
	 * @return array 결연교회 목록
	 */
	public function get_available_churches()
	{
		// 1. '결연교회' 카테고리 찾기
		$this->db->select('category_idx');
		$this->db->from('wb_org_category');
		$this->db->where('category_name', '결연교회');
		$category_query = $this->db->get();

		if ($category_query->num_rows() == 0) {
			return [];
		}

		$category_idx = $category_query->row()->category_idx;

		// 2. 하위 카테고리들 재귀적으로 찾기
		$category_indices = $this->get_child_categories($category_idx);
		$category_indices[] = $category_idx; // 자기 자신도 포함

		// 3. 해당 카테고리들의 교회 조회
		$this->db->select('org_id, org_name, org_address, org_phone, org_rep');
		$this->db->from('wb_org');
		$this->db->where_in('category_idx', $category_indices);
		$this->db->where('del_yn', 'N');
		$this->db->order_by('org_name', 'ASC');

		$query = $this->db->get();
		return $query->result_array();
	}

	/**
	 * 결연교회 자동매칭
	 *
	 * @param int $member_idx 회원 ID
	 * @param int $org_id 조직 ID
	 * @return array ['success' => bool, 'message' => string, 'matched_count' => int]
	 */
	public function auto_match_transfer_church($member_idx, $org_id)
	{
		// 1. 회원 정보 조회
		$this->db->select('member_address, member_name');
		$this->db->from('wb_member');
		$this->db->where('member_idx', $member_idx);
		$this->db->where('org_id', $org_id);
		$this->db->where('del_yn', 'N');
		$query = $this->db->get();

		if ($query->num_rows() == 0) {
			return [
				'success' => false,
				'message' => '회원 정보를 찾을 수 없습니다.',
				'matched_count' => 0
			];
		}

		$member = $query->row_array();
		$member_address = trim($member['member_address']);

		if (empty($member_address)) {
			return [
				'success' => false,
				'message' => '회원의 주소 정보가 없습니다.',
				'matched_count' => 0
			];
		}

		// 2. 회원 주소에서 앞 두 단어 추출
		$member_address_parts = preg_split('/\s+/', $member_address);
		if (count($member_address_parts) < 2) {
			return [
				'success' => false,
				'message' => '회원 주소 형식이 올바르지 않습니다.',
				'matched_count' => 0
			];
		}

		$member_region = $member_address_parts[0] . ' ' . $member_address_parts[1];

		// 3. 결연교회 목록 조회
		$available_churches = $this->get_available_churches();

		if (empty($available_churches)) {
			return [
				'success' => false,
				'message' => '등록된 결연교회가 없습니다.',
				'matched_count' => 0
			];
		}

		// 4. 이미 등록된 파송교회 org_id 목록 조회
		$existing_transfer_org_ids = $this->get_member_transfer_org_ids($member_idx, $org_id);

		// 5. 주소 매칭 및 자동 추가
		$matched_count = 0;
		$this->db->trans_start();

		foreach ($available_churches as $church) {
			$church_address = trim($church['org_address']);

			if (empty($church_address)) {
				continue;
			}

			// 교회 주소에서 앞 두 단어 추출
			$church_address_parts = preg_split('/\s+/', $church_address);
			if (count($church_address_parts) < 2) {
				continue;
			}

			$church_region = $church_address_parts[0] . ' ' . $church_address_parts[1];

			// 앞 두 단어가 일치하는지 확인
			if ($member_region === $church_region) {
				// 이미 등록된 교회인지 확인 (org_id 기반)
				if (!in_array($church['org_id'], $existing_transfer_org_ids)) {
					// wb_transfer_org에 추가
					$transfer_data = [
						'transfer_org_address' => $church['org_address'],
						'transfer_org_name' => $church['org_name'],
						'transfer_org_phone' => $church['org_phone'],
						'transfer_org_rep' => $church['org_rep'],
						'transfer_org_id' => $church['org_id'],
						'regi_date' => date('Y-m-d H:i:s'),
						'modi_date' => date('Y-m-d H:i:s'),
						'del_yn' => 'N'
					];

					$this->db->insert('wb_transfer_org', $transfer_data);
					$new_transfer_org_id = $this->db->insert_id();

					if ($new_transfer_org_id) {
						// wb_member.transfer_org_json 업데이트
						$this->update_member_transfer_orgs_json_link($member_idx, $org_id, $new_transfer_org_id, 'add');
						$matched_count++;
					}
				}
			}
		}

		$this->db->trans_complete();

		if ($this->db->trans_status() === FALSE) {
			return [
				'success' => false,
				'message' => '자동매칭 중 오류가 발생했습니다.',
				'matched_count' => 0
			];
		}

		if ($matched_count > 0) {
			return [
				'success' => true,
				'message' => $matched_count . '개의 결연교회가 자동으로 매칭되었습니다.',
				'matched_count' => $matched_count
			];
		} else {
			return [
				'success' => false,
				'message' => '매칭되는 결연교회가 없습니다.',
				'matched_count' => 0
			];
		}
	}

	/**
	 * 회원의 파송교회 org_id 목록 조회 (중복 방지용)
	 *
	 * @param int $member_idx 회원 ID
	 * @param int $org_id 조직 ID
	 * @return array org_id 배열
	 */
	private function get_member_transfer_org_ids($member_idx, $org_id)
	{
		// wb_member.transfer_org_json에서 해당 회원의 파송교회 ID 목록을 가져옴
		$this->db->select('transfer_org_json');
		$this->db->from('wb_member');
		$this->db->where('member_idx', $member_idx);
		$this->db->where('org_id', $org_id);
		$query = $this->db->get();

		if ($query->num_rows() == 0) {
			return [];
		}

		$row = $query->row_array();
		$transfer_org_ids = [];

		if (!empty($row['transfer_org_json'])) {
			$ids_from_json = json_decode($row['transfer_org_json'], true);
			if (is_array($ids_from_json)) {
				// transfer_org_id 목록에서 실제 org_id를 가져옴
				$this->db->select('transfer_org_id');
				$this->db->from('wb_transfer_org');
				$this->db->where_in('transfer_org_id', $ids_from_json);
				$this->db->where('del_yn', 'N');
				$query2 = $this->db->get();

				foreach ($query2->result_array() as $row2) {
					if (!empty($row2['transfer_org_id'])) {
						$transfer_org_ids[] = $row2['transfer_org_id'];
					}
				}
			}
		}

		return $transfer_org_ids;
	}

	/**
	 * wb_member.transfer_org_json 필드 업데이트
	 *
	 * @param int $member_idx 회원 ID
	 * @param int $org_id 조직 ID
	 * @param int $id_to_link wb_transfer_org의 PK
	 * @param string $action 'add' 또는 'delete'
	 * @return bool 업데이트 성공 여부
	 */
	public function update_member_transfer_orgs_json_link($member_idx, $org_id, $id_to_link, $action)
	{
		// 1. 현재 JSON 데이터 가져오기
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

		// 2. ID 목록 수정
		if ($action === 'add') {
			if (!in_array($id_to_link_str, $current_ids)) {
				$current_ids[] = $id_to_link_str;
			}
		} elseif ($action === 'delete') {
			$current_ids = array_values(array_diff($current_ids, [$id_to_link_str]));
		}

		// 3. JSON으로 인코딩 후 저장
		$json_data = json_encode($current_ids, JSON_NUMERIC_CHECK | JSON_UNESCAPED_UNICODE);

		$this->db->where('member_idx', $member_idx);
		$this->db->where('org_id', $org_id);
		return $this->db->update('wb_member', [
			'transfer_org_json' => $json_data,
			'modi_date' => date('Y-m-d H:i:s')
		]);
	}

	/**
	 * 하위 카테고리 재귀 조회
	 *
	 * @param int $parent_idx 부모 카테고리 ID
	 * @return array 하위 카테고리 ID 배열
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
			// 재귀적으로 하위 카테고리 조회
			$children = $this->get_child_categories($row['category_idx']);
			$result = array_merge($result, $children);
		}

		return $result;
	}
}
