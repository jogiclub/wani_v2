
<?php
class Org_model extends CI_Model {
	public function __construct() {
		parent::__construct();
		$this->load->database();
	}

	public function create_org($org_name) {
		$data = array(
			'org_name' => $org_name,
			'regi_date' => date('Y-m-d H:i:s'),
			'modi_date' => date('Y-m-d H:i:s')
		);
		$this->db->insert('wb_org', $data);
		return $this->db->insert_id();
	}

	public function add_user_to_org($user_id, $org_id) {
		$data = array(
			'user_id' => $user_id,
			'org_id' => $org_id,
			'level' => '10'
		);
		$this->db->insert('wb_org_user', $data);
	}

	public function get_org_user($user_id, $org_id) {
		$this->db->where('user_id', $user_id);
		$this->db->where('org_id', $org_id);
		$query = $this->db->get('wb_org_user');
		return $query->row_array();
	}

	public function get_user_orgs_master($user_id) {
		$this->db->select('wb_org.org_id, wb_org.org_name, wb_org.org_type, wb_org.org_icon, wb_org.leader_name, wb_org.new_name, COUNT(wb_member.member_idx) as member_count');
		$this->db->from('wb_org');
		$this->db->join('wb_member', 'wb_org.org_id = wb_member.org_id AND wb_member.del_yn = "N"', 'left');
		$this->db->where('wb_org.del_yn', 'N');
		$this->db->group_by('wb_org.org_id');
		$query = $this->db->get();
		return $query->result_array();
	}

	public function get_user_orgs($user_id) {
		$this->db->select('wb_org.org_id, wb_org.org_name, wb_org.org_type, wb_org.org_icon, wb_org.leader_name, wb_org.new_name, COUNT(wb_member.member_idx) as member_count');
		$this->db->from('wb_org');
		$this->db->join('wb_org_user', 'wb_org.org_id = wb_org_user.org_id');
		$this->db->join('wb_member', 'wb_org.org_id = wb_member.org_id AND wb_member.del_yn = "N"', 'left');
		$this->db->where('wb_org_user.user_id', $user_id);
		$this->db->where('wb_org.del_yn', 'N');
		$this->db->group_by('wb_org.org_id');
		$query = $this->db->get();
		return $query->result_array();
	}

	/**
	 * 역할: 새로운 조직 정보 저장
	 */
	public function insert_organization($data)
	{
		return $this->db->insert('wb_org', $data);
	}

	/**
	 * 역할: 초대코드로 조직 정보 조회
	 */
	public function get_org_by_invite_code($invite_code)
	{
		$this->db->select('org_id, org_name, org_type, org_desc, invite_code, del_yn');
		$this->db->from('wb_org');
		$this->db->where('invite_code', $invite_code);
		$query = $this->db->get();
		return $query->row_array();
	}

	/**
	 * 역할: 사용자의 조직 가입 여부 확인
	 */
	public function check_user_org_membership($user_id, $org_id)
	{
		$this->db->select('idx');
		$this->db->from('wb_org_user');
		$this->db->where('user_id', $user_id);
		$this->db->where('org_id', $org_id);
		$query = $this->db->get();
		return $query->row_array();
	}


	/**
	 * 조직 사용자 정보 저장
	 */
	public function insert_org_user($data)
	{
		return $this->db->insert('wb_org_user', $data);
	}

	/**
	 * 조직 사용자 상태 업데이트 (초대 승인 등)
	 */
	public function update_org_user_status($user_id, $org_id, $data)
	{
		$this->db->where('user_id', $user_id);
		$this->db->where('org_id', $org_id);
		return $this->db->update('wb_org_user', $data);
	}

	public function get_org_by_id($org_id) {
		$this->db->select('org_id, org_name, org_type, org_icon, leader_name, new_name');
		$this->db->from('wb_org');
		$this->db->where('org_id', $org_id);
		$this->db->where('wb_org.del_yn', 'N');
		$query = $this->db->get();
		return $query->row_array();
	}

	public function get_min_org_id($user_id) {
		$this->db->select_min('wb_org_user.org_id');
		$this->db->from('wb_org_user');
		$this->db->join('wb_org', 'wb_org_user.org_id = wb_org.org_id');
		$this->db->where('wb_org_user.user_id', $user_id);
		$this->db->where('wb_org.del_yn', 'N');
		$query = $this->db->get();
		$result = $query->row_array();

		return $result['org_id'] ?? null;
	}

	/**
	 * 조직 상세 정보 가져오기 (아이콘 필드 포함)
	 */
	public function get_org_detail_by_id($org_id) {
		$this->db->select('org_id, org_name, org_type, org_desc, org_icon, leader_name, new_name, invite_code, position_name, duty_name, timeline_name, regi_date, modi_date');
		$this->db->from('wb_org');
		$this->db->where('org_id', $org_id);
		$this->db->where('del_yn', 'N');
		$query = $this->db->get();
		return $query->row_array();
	}

	/**
	 * 조직 정보 업데이트
	 */
	public function update_org_info($org_id, $data) {
		// JSON 데이터 처리
		if (isset($data['position_name']) && is_array($data['position_name'])) {
			$data['position_name'] = json_encode($data['position_name'], JSON_UNESCAPED_UNICODE);
		}

		if (isset($data['duty_name']) && is_array($data['duty_name'])) {
			$data['duty_name'] = json_encode($data['duty_name'], JSON_UNESCAPED_UNICODE);
		}

		if (isset($data['timeline_name']) && is_array($data['timeline_name'])) {
			$data['timeline_name'] = json_encode($data['timeline_name'], JSON_UNESCAPED_UNICODE);
		}

		$this->db->where('org_id', $org_id);
		$this->db->where('del_yn', 'N');
		return $this->db->update('wb_org', $data);
	}

	/**
	 * 조직의 최고관리자 정보 가져오기
	 */
	public function get_org_admin($org_id) {
		$this->db->select('wb_user.user_id, wb_user.user_name, wb_user.user_mail, wb_user.user_profile_image');
		$this->db->from('wb_user');
		$this->db->join('wb_org_user', 'wb_user.user_id = wb_org_user.user_id');
		$this->db->where('wb_org_user.org_id', $org_id);
		$this->db->where('wb_org_user.level', 10);
		$this->db->where('wb_user.del_yn', 'N');
		$this->db->limit(1);
		$query = $this->db->get();
		return $query->row_array();
	}

	/**
	 * 관리자 권한 위임
	 */
	public function delegate_admin($current_admin_id, $new_admin_id, $org_id) {
		$this->db->trans_start();

		// 현재 관리자를 레벨 9로 변경
		$this->db->where('user_id', $current_admin_id);
		$this->db->where('org_id', $org_id);
		$this->db->update('wb_org_user', array('level' => 9));

		// 새 관리자를 레벨 10으로 변경
		$this->db->where('user_id', $new_admin_id);
		$this->db->where('org_id', $org_id);
		$this->db->update('wb_org_user', array('level' => 10));

		$this->db->trans_complete();

		return $this->db->trans_status();
	}


	/**
	 * 카테고리별 조직 목록 조회
	 */
	public function get_orgs_by_category($category_idx = null)
	{
		$this->db->select('o.org_id, o.org_code, o.org_name, o.org_type, o.org_desc, o.leader_name, o.new_name, o.org_icon, o.regi_date, o.invite_code, COUNT(ou.user_id) as member_count');
		$this->db->from('wb_org o');
		$this->db->join('wb_org_user ou', 'o.org_id = ou.org_id', 'left');

		if ($category_idx === null || $category_idx === 'uncategorized') {
			// 미분류 조직 조회
			$this->db->where('(o.category_idx IS NULL OR o.category_idx = 0)', null, false);
		} else {
			$this->db->where('o.category_idx', $category_idx);
		}

		$this->db->where('o.del_yn', 'N');
		$this->db->group_by('o.org_id');
		$this->db->order_by('o.regi_date', 'DESC');

		$query = $this->db->get();
		return $query->result_array();
	}

	/**
	 * 조직 회원 수 조회
	 */
	public function get_org_member_count($org_id)
	{
		$this->db->from('wb_org_user');
		$this->db->where('org_id', $org_id);
		return $this->db->count_all_results();
	}

	/**
	 * 카테고리에 조직이 있는지 확인
	 */
	public function has_orgs_in_category($category_idx)
	{
		$this->db->from('wb_org');
		$this->db->where('category_idx', $category_idx);
		$this->db->where('del_yn', 'N');
		return $this->db->count_all_results() > 0;
	}

	/**
	 * 조직 삭제 (soft delete)
	 */
	public function delete_org($org_id)
	{
		$data = array(
			'del_yn' => 'Y',
			'del_date' => date('Y-m-d H:i:s'),
			'modi_date' => date('Y-m-d H:i:s')
		);

		$this->db->where('org_id', $org_id);
		return $this->db->update('wb_org', $data);
	}

	/**
	 * 조직을 카테고리에 할당
	 */
	public function assign_org_to_category($org_id, $category_idx)
	{
		$data = array(
			'category_idx' => $category_idx,
			'modi_date' => date('Y-m-d H:i:s')
		);

		$this->db->where('org_id', $org_id);
		return $this->db->update('wb_org', $data);
	}

	/**
	 * 조직의 카테고리 할당 해제
	 */
	public function unassign_org_from_category($org_id)
	{
		$data = array(
			'category_idx' => null,
			'modi_date' => date('Y-m-d H:i:s')
		);

		$this->db->where('org_id', $org_id);
		return $this->db->update('wb_org', $data);
	}

	/**
	 * 특정 카테고리의 조직 목록 조회 (상세 정보 포함)
	 */
	public function get_orgs_by_category_detailed($category_idx = null)
	{
		$this->db->select('o.org_id, o.org_code, o.org_name, o.org_type, o.org_desc, o.leader_name, o.new_name, o.org_icon, o.regi_date, o.invite_code, o.category_idx');
		$this->db->select('c.category_name');
		$this->db->select('COUNT(DISTINCT ou.user_id) as member_count');
		$this->db->from('wb_org o');
		$this->db->join('wb_org_user ou', 'o.org_id = ou.org_id', 'left');
		$this->db->join('wb_org_category c', 'o.category_idx = c.category_idx', 'left');

		// 카테고리 필터링 (빈 문자열도 전체로 처리)
		if (empty($category_idx)) {
			// null이거나 빈 문자열이면 전체 조직 조회 (카테고리 조건 없음)
		} else if ($category_idx === 'uncategorized') {
			// 미분류 조직만 조회
			$this->db->where('(o.category_idx IS NULL OR o.category_idx = 0 OR o.category_idx = "")', null, false);
		} else {
			// 특정 카테고리의 조직만 조회
			$this->db->where('o.category_idx', $category_idx);
		}

		$this->db->where('o.del_yn', 'N');
		$this->db->group_by('o.org_id, c.category_name');
		$this->db->order_by('c.category_name', 'ASC');
		$this->db->order_by('o.org_name', 'ASC');

		$query = $this->db->get();
		return $query->result_array();
	}

	/**
	 * 조직 검색
	 */
	public function search_orgs($keyword, $category_idx = null)
	{
		$this->db->select('o.org_id, o.org_code, o.org_name, o.org_type, o.org_desc, o.leader_name, o.new_name, o.org_icon, o.regi_date, o.invite_code');
		$this->db->select('COUNT(ou.user_id) as member_count');
		$this->db->from('wb_org o');
		$this->db->join('wb_org_user ou', 'o.org_id = ou.org_id', 'left');

		// 검색 조건
		$this->db->group_start();
		$this->db->like('o.org_name', $keyword);
		$this->db->or_like('o.org_code', $keyword);
		$this->db->or_like('o.org_desc', $keyword);
		$this->db->group_end();

		// 카테고리 필터
		if ($category_idx !== null && $category_idx !== 'all') {
			if ($category_idx === 'uncategorized') {
				$this->db->where('(o.category_idx IS NULL OR o.category_idx = 0)', null, false);
			} else {
				$this->db->where('o.category_idx', $category_idx);
			}
		}

		$this->db->where('o.del_yn', 'N');
		$this->db->group_by('o.org_id');
		$this->db->order_by('o.org_name', 'ASC');

		$query = $this->db->get();
		return $query->result_array();
	}
}
