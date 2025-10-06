<?php
/**
 * 파일 위치: application/models/Group_setting_model.php
 * 역할: 그룹설정 관련 데이터베이스 작업을 처리하는 모델
 */
class Group_setting_model extends CI_Model {

	public function __construct() {
		parent::__construct();
		$this->load->database();
	}

	/**
	 * 사용자의 조직 권한 레벨 조회
	 */
	public function get_org_user_level($user_id, $org_id) {
		$this->db->select('level');
		$this->db->from('wb_org_user');
		$this->db->where('user_id', $user_id);
		$this->db->where('org_id', $org_id);
		$query = $this->db->get();
		$result = $query->row_array();
		return $result ? $result['level'] : 0;
	}



	/**
	 * 특정 그룹과 하위 그룹들의 회원 수 합계 계산 (Member.php와 동일한 로직)
	 */
	public function get_area_members_count_with_children($org_id, $area_idx) {
		// 현재 그룹의 직접 회원 수
		$this->db->from('wb_member');
		$this->db->where('area_idx', $area_idx);
		$this->db->where('org_id', $org_id);
		$this->db->where('del_yn', 'N');
		$direct_count = $this->db->count_all_results();

		// 하위 그룹들의 회원 수 (재귀적 계산)
		$children_count = 0;
		$this->db->select('area_idx');
		$this->db->from('wb_member_area');
		$this->db->where('parent_idx', $area_idx);
		$this->db->where('org_id', $org_id);
		$children_query = $this->db->get();
		$children = $children_query->result_array();

		foreach ($children as $child) {
			$children_count += $this->get_area_members_count_with_children($org_id, $child['area_idx']);
		}

		return $direct_count + $children_count;
	}


	/**
	 * 파일 위치: application/models/Group_setting_model.php - get_org_group_tree() 함수
	 * 역할: 조직 그룹 트리 데이터 조회 시 회원수 중복 계산 문제 해결
	 */
	public function get_org_group_tree($org_id) {
		// 조직 정보 조회
		$this->db->select('org_id, org_name');
		$this->db->from('wb_org');
		$this->db->where('org_id', $org_id);
		$org_query = $this->db->get();
		$org_data = $org_query->row_array();

		if (!$org_data) {
			return array();
		}

		// 조직의 모든 그룹 조회
		$this->db->select('area_idx, area_name, parent_idx, area_order');
		$this->db->from('wb_member_area');
		$this->db->where('org_id', $org_id);
		$this->db->order_by('area_order', 'ASC');
		$area_query = $this->db->get();
		$areas = $area_query->result_array();

		// 각 그룹의 하위 그룹 포함 회원 수 조회 (Member.php 방식 적용)
		$area_member_counts = array();
		foreach ($areas as $area) {
			$area_member_counts[$area['area_idx']] = $this->get_area_members_count_with_children($org_id, $area['area_idx']);
		}

		// 미분류 회원 수 조회
		$this->db->from('wb_member');
		$this->db->where('org_id', $org_id);
		$this->db->where('area_idx IS NULL');
		$this->db->where('del_yn', 'N');
		$unassigned_count = $this->db->count_all_results();

		// 조직 전체 회원 수 계산 (중복 제거된 방식)
		$this->db->from('wb_member');
		$this->db->where('org_id', $org_id);
		$this->db->where('del_yn', 'N');
		$org_total_members = $this->db->count_all_results();

		$org_title = $org_data['org_name'];
		if ($org_total_members > 0) {
			$org_title .= ' (' . $org_total_members . '명)';
		}

		// 트리 구조 구성
		$tree_data = array(
			array(
				'key' => 'org_' . $org_id,
				'title' => $org_title,
				'folder' => true,
				'data' => array(
					'type' => 'org',
					'org_id' => $org_id,
					'area_idx' => null,
					'member_count' => $org_total_members
				),
				'children' => $this->build_group_tree($areas, $area_member_counts, null, $org_id, $unassigned_count)
			)
		);

		return $tree_data;
	}

	/**
	 * 그룹 트리 구조 재귀적 구성 (회원수 계산 방식 수정)
	 */
	private function build_group_tree($areas, $area_member_counts, $parent_idx, $org_id, $unassigned_count = 0) {
		$children = array();

		// 최상위 레벨인 경우 미분류 그룹 추가
		if ($parent_idx === null && $unassigned_count > 0) {
			$children[] = array(
				'key' => 'unassigned_' . $org_id,
				'title' => "미분류 ({$unassigned_count}명)",
				'folder' => false,
				'data' => array(
					'type' => 'unassigned',
					'org_id' => $org_id,
					'area_idx' => null,
					'member_count' => $unassigned_count
				)
			);
		}

		// 해당 parent_idx를 가진 그룹들 찾기
		foreach ($areas as $area) {
			// parent_idx 비교 시 null, 0, 빈 문자열을 모두 같은 것으로 처리
			$area_parent_idx = $area['parent_idx'];
			if (empty($area_parent_idx) || $area_parent_idx === '0' || $area_parent_idx === 0) {
				$area_parent_idx = null;
			}

			if ($area_parent_idx == $parent_idx) {
				// 하위 그룹 포함 회원 수 사용
				$member_count = isset($area_member_counts[$area['area_idx']]) ? $area_member_counts[$area['area_idx']] : 0;
				$child_areas = $this->build_group_tree($areas, $area_member_counts, $area['area_idx'], $org_id);

				$children[] = array(
					'key' => 'area_' . $area['area_idx'],
					'title' => $area['area_name'] . " ({$member_count}명)",
					'folder' => !empty($child_areas),
					'data' => array(
						'type' => 'area',
						'org_id' => $org_id,
						'area_idx' => $area['area_idx'],
						'area_name' => $area['area_name'],
						'parent_idx' => $area['parent_idx'],
						'member_count' => $member_count
					),
					'children' => $child_areas
				);
			}
		}

		return $children;
	}

	/**
	 * 새 그룹 삽입
	 */
	public function insert_group($data) {
		// parent_idx가 빈 값이거나 0인 경우 null로 변경
		if (isset($data['parent_idx']) && (empty($data['parent_idx']) || $data['parent_idx'] === '0' || $data['parent_idx'] === 0)) {
			$data['parent_idx'] = null;
		}

		return $this->db->insert('wb_member_area', $data);
	}

	/**
	 * 다음 순서 번호 조회
	 */
	public function get_next_area_order($org_id, $parent_idx) {
		$this->db->select_max('area_order');
		$this->db->from('wb_member_area');
		$this->db->where('org_id', $org_id);

		if ($parent_idx === null) {
			$this->db->where('parent_idx IS NULL');
		} else {
			$this->db->where('parent_idx', $parent_idx);
		}

		$query = $this->db->get();
		$result = $query->row_array();
		$max_order = $result['area_order'] ? $result['area_order'] : 0;

		return $max_order + 1;
	}

	/**
	 * 그룹에 포함된 회원 수 조회
	 */
	public function get_group_member_count($area_idx) {
		$this->db->from('wb_member');
		$this->db->where('area_idx', $area_idx);
		$this->db->where('del_yn', 'N');
		return $this->db->count_all_results();
	}

	/**
	 * 그룹 삭제 및 회원 미분류로 이동
	 */
	public function delete_group_with_members($area_idx, $org_id) {
		$this->db->trans_start();

		// 해당 그룹의 회원들을 미분류로 이동 (area_idx = null)
		$this->db->where('area_idx', $area_idx);
		$this->db->where('org_id', $org_id);
		$this->db->update('wb_member', array('area_idx' => null));

		// 하위 그룹들도 함께 삭제 (재귀적)
		$this->delete_group_and_children($area_idx, $org_id);

		// 그룹 자체 삭제
		$this->db->where('area_idx', $area_idx);
		$this->db->delete('wb_member_area');

		$this->db->trans_complete();
		return $this->db->trans_status();
	}

	/**
	 * 하위 그룹들 재귀적 삭제
	 */
	private function delete_group_and_children($area_idx, $org_id) {
		// 하위 그룹 조회
		$this->db->select('area_idx');
		$this->db->from('wb_member_area');
		$this->db->where('parent_idx', $area_idx);
		$this->db->where('org_id', $org_id);
		$query = $this->db->get();
		$children = $query->result_array();

		foreach ($children as $child) {
			// 하위 그룹의 회원들을 미분류로 이동
			$this->db->where('area_idx', $child['area_idx']);
			$this->db->where('org_id', $org_id);
			$this->db->update('wb_member', array('area_idx' => null));

			// 재귀적으로 하위 그룹 삭제
			$this->delete_group_and_children($child['area_idx'], $org_id);

			// 하위 그룹 삭제
			$this->db->where('area_idx', $child['area_idx']);
			$this->db->delete('wb_member_area');
		}
	}

	/**
	 * 그룹 이동
	 */
	public function move_group($area_idx, $target_parent_idx, $area_order) {
		$data = array(
			'parent_idx' => $target_parent_idx,
			'area_order' => $area_order
		);

		$this->db->where('area_idx', $area_idx);
		return $this->db->update('wb_member_area', $data);
	}

	/**
	 * 자기 자신이나 하위 그룹 여부 확인 (순환 참조 방지)
	 */
	public function is_descendant_group($area_idx, $target_parent_idx) {
		if ($area_idx == $target_parent_idx) {
			return true; // 자기 자신
		}

		if ($target_parent_idx === null) {
			return false; // 최상위로 이동하는 경우는 허용
		}

		// 대상이 현재 그룹의 하위 그룹인지 재귀적으로 확인
		return $this->check_descendant_recursive($area_idx, $target_parent_idx);
	}

	/**
	 * 하위 그룹 여부 재귀적 확인
	 */
	private function check_descendant_recursive($ancestor_idx, $current_idx) {
		$this->db->select('parent_idx');
		$this->db->from('wb_member_area');
		$this->db->where('area_idx', $current_idx);
		$query = $this->db->get();
		$result = $query->row_array();

		if (!$result || $result['parent_idx'] === null) {
			return false;
		}

		if ($result['parent_idx'] == $ancestor_idx) {
			return true; // 직접 하위 그룹
		}

		// 상위 그룹으로 올라가면서 재귀적 확인
		return $this->check_descendant_recursive($ancestor_idx, $result['parent_idx']);
	}

	/**
	 * 그룹 이동 대상 목록 조회 (현재 그룹과 그 하위 그룹 제외)
	 */
	public function get_move_target_groups($org_id, $current_area_idx) {
		$this->db->select('area_idx, area_name, parent_idx');
		$this->db->from('wb_member_area');
		$this->db->where('org_id', $org_id);
		$this->db->where('area_idx !=', $current_area_idx);
		$this->db->order_by('area_order', 'ASC');
		$query = $this->db->get();
		$all_areas = $query->result_array();

		// 현재 그룹의 모든 하위 그룹 ID 수집
		$descendant_ids = $this->get_descendant_ids($current_area_idx, $all_areas);
		$descendant_ids[] = $current_area_idx; // 자기 자신도 제외

		// 하위 그룹들을 제외한 그룹들만 반환
		$target_groups = array();
		foreach ($all_areas as $area) {
			if (!in_array($area['area_idx'], $descendant_ids)) {
				$target_groups[] = $area;
			}
		}

		return $this->build_hierarchical_group_list($target_groups);
	}

	/**
	 * 하위 그룹 ID들 재귀적 수집
	 */
	private function get_descendant_ids($area_idx, $all_areas) {
		$descendant_ids = array();

		foreach ($all_areas as $area) {
			if ($area['parent_idx'] == $area_idx) {
				$descendant_ids[] = $area['area_idx'];
				$sub_descendants = $this->get_descendant_ids($area['area_idx'], $all_areas);
				$descendant_ids = array_merge($descendant_ids, $sub_descendants);
			}
		}

		return $descendant_ids;
	}

	/**
	 * 계층적 그룹 목록 구성 (들여쓰기 포함)
	 */
	private function build_hierarchical_group_list($areas, $parent_idx = null, $depth = 0) {
		$result = array();

		foreach ($areas as $area) {
			if ($area['parent_idx'] == $parent_idx) {
				$indent = str_repeat('　', $depth); // 전각 공백으로 들여쓰기
				$result[] = array(
					'area_idx' => $area['area_idx'],
					'area_name' => $indent . $area['area_name'],
					'depth' => $depth
				);

				// 하위 그룹 재귀적 추가
				$children = $this->build_hierarchical_group_list($areas, $area['area_idx'], $depth + 1);
				$result = array_merge($result, $children);
			}
		}

		return $result;
	}


	/**
	 * 그룹명 변경 (AJAX)
	 */
	public function rename_group()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$user_id = $this->session->userdata('user_id');
		$area_idx = $this->input->post('area_idx');
		$org_id = $this->input->post('org_id');
		$new_name = trim($this->input->post('new_name'));

		if (!$area_idx || !$org_id || !$new_name) {
			echo json_encode(array('success' => false, 'message' => '필수 정보가 누락되었습니다.'));
			return;
		}

		// 권한 검증
		$user_level = $this->Group_setting_model->get_org_user_level($user_id, $org_id);
		if ($user_level < 8 && $this->session->userdata('master_yn') !== 'Y') {
			echo json_encode(array('success' => false, 'message' => '그룹명을 변경할 권한이 없습니다.'));
			return;
		}

		// 그룹명 길이 검증
		if (strlen($new_name) > 50) {
			echo json_encode(array('success' => false, 'message' => '그룹명은 50자 이내로 입력해주세요.'));
			return;
		}

		// 동일한 조직 내에서 같은 레벨에 동일한 그룹명이 있는지 확인
		$current_group = $this->Group_setting_model->get_group_info($area_idx);
		if (!$current_group) {
			echo json_encode(array('success' => false, 'message' => '그룹 정보를 찾을 수 없습니다.'));
			return;
		}

		$duplicate_check = $this->Group_setting_model->check_duplicate_group_name($org_id, $new_name, $current_group['parent_idx'], $area_idx);
		if ($duplicate_check) {
			echo json_encode(array('success' => false, 'message' => '동일한 위치에 같은 이름의 그룹이 이미 존재합니다.'));
			return;
		}

		// 그룹명 변경 실행
		$result = $this->Group_setting_model->update_group_name($area_idx, $new_name);

		if ($result) {
			echo json_encode(array('success' => true, 'message' => '그룹명이 변경되었습니다.'));
		} else {
			echo json_encode(array('success' => false, 'message' => '그룹명 변경에 실패했습니다.'));
		}
	}

	/**
	 * 그룹 정보 조회
	 */
	public function get_group_info($area_idx) {
		$this->db->select('area_idx, area_name, parent_idx, org_id');
		$this->db->from('wb_member_area');
		$this->db->where('area_idx', $area_idx);
		$query = $this->db->get();
		return $query->row_array();
	}

	/**
	 * 중복 그룹명 확인
	 */
	public function check_duplicate_group_name($org_id, $group_name, $parent_idx, $exclude_area_idx = null) {
		$this->db->select('area_idx');
		$this->db->from('wb_member_area');
		$this->db->where('org_id', $org_id);
		$this->db->where('area_name', $group_name);

		if ($parent_idx === null) {
			$this->db->where('parent_idx IS NULL');
		} else {
			$this->db->where('parent_idx', $parent_idx);
		}

		if ($exclude_area_idx !== null) {
			$this->db->where('area_idx !=', $exclude_area_idx);
		}

		$query = $this->db->get();
		return $query->num_rows() > 0;
	}

	/**
	 * 그룹명 변경
	 */
	public function update_group_name($area_idx, $new_name) {
		$data = array(
			'area_name' => $new_name
		);

		$this->db->where('area_idx', $area_idx);
		return $this->db->update('wb_member_area', $data);
	}

	/**
	 * 파일 위치: application/models/Group_setting_model.php
	 * 역할: 그룹 초대코드 조회
	 */
	public function get_area_invite_code($area_idx)
	{
		$this->db->select('invite_code');
		$this->db->from('wb_member_area');
		$this->db->where('area_idx', $area_idx);
		$query = $this->db->get();
		$result = $query->row_array();
		return $result ? $result['invite_code'] : null;
	}

	/**
	 * 파일 위치: application/models/Group_setting_model.php
	 * 역할: 고유한 그룹 초대코드 생성
	 */
	public function generate_unique_area_invite_code($org_id)
	{
		$max_attempts = 100;
		$attempt = 0;

		do {
			$code = strtoupper(substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZ123456789'), 0, 8));

			$this->db->where('invite_code', $code);
			$this->db->where('org_id', $org_id);
			$exists = $this->db->count_all_results('wb_member_area') > 0;

			$attempt++;

			if ($attempt >= $max_attempts) {
				log_message('error', '그룹 초대코드 생성 시도 한계 도달');
				break;
			}
		} while ($exists);

		return $code;
	}

	/**
	 * 파일 위치: application/models/Group_setting_model.php
	 * 역할: 그룹 초대코드 저장
	 */
	public function save_area_invite_code($area_idx, $invite_code)
	{
		$data = array(
			'invite_code' => $invite_code
		);

		$this->db->where('area_idx', $area_idx);
		return $this->db->update('wb_member_area', $data);
	}

	/**
	 * 파일 위치: application/models/Group_setting_model.php
	 * 역할: 초대코드로 그룹 정보 조회
	 */
	public function get_area_by_invite_code($invite_code, $org_id)
	{
		$this->db->select('area_idx, area_name, org_id');
		$this->db->from('wb_member_area');
		$this->db->where('invite_code', $invite_code);
		$this->db->where('org_id', $org_id);
		$query = $this->db->get();
		return $query->row_array();
	}


}
