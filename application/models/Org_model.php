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
		$this->db->select('wb_org.org_id, wb_org.org_name, wb_org.leader_name, wb_org.new_name, COUNT(wb_member.member_idx) as member_count');
		$this->db->from('wb_org');
		$this->db->join('wb_member', 'wb_org.org_id = wb_member.org_id AND wb_member.del_yn = "N"', 'left');
		$this->db->where('wb_org.del_yn', 'N');
		$this->db->group_by('wb_org.org_id');
		$query = $this->db->get();
//		print_r($this->db->last_query());
//		return false;
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
		$this->db->select('org_id, org_name, leader_name, new_name');
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

	public function update_org($org_id, $org_name, $leader_name, $new_name) {
		$this->db->where('org_id', $org_id);
		$this->db->update('wb_org', array(
			'org_name' => $org_name,
			'leader_name' => $leader_name,
			'new_name' => $new_name,
			'modi_date' => date('Y-m-d H:i:s')
		));
		return $this->db->affected_rows() > 0;
	}

	public function update_del_yn($org_id){
		$this->db->where('org_id', $org_id);
		$this->db->update('wb_org', array('del_yn' => 'Y', 'del_date' => date('Y-m-d H:i:s')));
		return $this->db->affected_rows() > 0;
	}
}
