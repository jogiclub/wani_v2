<?php
class Org_model extends CI_Model {
    public function __construct() {
        parent::__construct();
        $this->load->database();
    }

    public function create_group($group_name) {
        $data = array(
            'group_name' => $group_name,
            'regi_date' => date('Y-m-d H:i:s'),
            'modi_date' => date('Y-m-d H:i:s')
        );
        $this->db->insert('wb_group', $data);
        return $this->db->insert_id();
    }

    public function add_user_to_group($user_id, $org_id) {
        $data = array(
            'user_id' => $user_id,
            'org_id' => $org_id,
            'level' => '10'
        );
        $this->db->insert('wb_group_user', $data);
    }

    public function get_group_user($user_id, $org_id) {
        $this->db->where('user_id', $user_id);
        $this->db->where('org_id', $org_id);
        $query = $this->db->get('wb_group_user');
        return $query->row_array();
    }

    public function get_user_groups($user_id) {
        $this->db->select('wb_group.org_id, wb_group.group_name, wb_group.leader_name, wb_group.new_name, COUNT(wb_member.member_idx) as member_count');
        $this->db->from('wb_group');
        $this->db->join('wb_group_user', 'wb_group.org_id = wb_group_user.org_id');
        $this->db->join('wb_member', 'wb_group.org_id = wb_member.org_id AND wb_member.del_yn = "N"', 'left');
        $this->db->where('wb_group_user.user_id', $user_id);
        $this->db->where('wb_group.del_yn', 'N');
        $this->db->group_by('wb_group.org_id');
        $query = $this->db->get();
        return $query->result_array();
    }

    public function get_user_groups_master($user_id) {
        $this->db->select('wb_group.org_id, wb_group.group_name, wb_group.leader_name, wb_group.new_name, COUNT(wb_member.member_idx) as member_count');
        $this->db->from('wb_group');
        $this->db->join('wb_member', 'wb_group.org_id = wb_member.org_id AND wb_member.del_yn = "N"', 'left');
        $this->db->where('wb_group.del_yn', 'N');
        $this->db->group_by('wb_group.org_id');
        $query = $this->db->get();
        return $query->result_array();
    }

    public function get_group_by_invite_code($invite_code) {
        $this->db->select('org_id');
        $this->db->from('wb_group');
        $this->db->where('invite_code', $invite_code);
        $query = $this->db->get();
        return $query->row_array();
    }

    public function get_group_by_id($org_id) {
        $this->db->select('org_id, group_name, leader_name, new_name');
        $this->db->from('wb_group');
        $this->db->where('org_id', $org_id);
        $this->db->where('wb_group.del_yn', 'N');
        $query = $this->db->get();
        return $query->row_array();
    }

    public function get_min_org_id($user_id) {
        $this->db->select_min('wb_group_user.org_id');
        $this->db->from('wb_group_user');
        $this->db->join('wb_group', 'wb_group_user.org_id = wb_group.org_id');
        $this->db->where('wb_group_user.user_id', $user_id);
        $this->db->where('wb_group.del_yn', 'N');
        $query = $this->db->get();
        $result = $query->row_array();

        return $result['org_id'] ?? null;
    }

    public function update_group($org_id, $group_name, $leader_name, $new_name) {
        $this->db->where('org_id', $org_id);
        $this->db->update('wb_group', array(
            'group_name' => $group_name,
            'leader_name' => $leader_name,
            'new_name' => $new_name,
            'modi_date' => date('Y-m-d H:i:s')
        ));
        return $this->db->affected_rows() > 0;
    }



    public function update_del_yn($org_id){
        $this->db->where('org_id', $org_id);
        $this->db->update('wb_group', array('del_yn' => 'Y', 'del_date' => date('Y-m-d H:i:s')));
        return $this->db->affected_rows() > 0;
    }







}

