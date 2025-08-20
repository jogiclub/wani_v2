
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

	public function get_org_by_invite_code($invite_code) {
		$this->db->select('org_id');
		$this->db->from('wb_org');
		$this->db->where('invite_code', $invite_code);
		$query = $this->db->get();
		return $query->row_array();
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
		$this->db->select('org_id, org_name, org_type, org_desc, org_icon, leader_name, new_name, invite_code, regi_date, modi_date');
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


}
