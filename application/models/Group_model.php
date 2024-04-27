<?php
class Group_model extends CI_Model {
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

    public function add_user_to_group($user_id, $group_id) {
        $data = array(
            'user_id' => $user_id,
            'group_id' => $group_id
        );
        $this->db->insert('wb_group_user', $data);
    }



    public function get_group_by_id($group_id) {
        $this->db->select('group_name, leader_name, new_name');
        $this->db->from('wb_group');
        $this->db->where('group_id', $group_id);
        $this->db->where('wb_group.del_yn', 'N');
        $query = $this->db->get();
        return $query->row_array();
    }

    public function get_min_group_id($user_id) {
        $this->db->select_min('group_id');
        $this->db->from('wb_group_user');
        $this->db->where('user_id', $user_id);
        $this->db->where('wb_group.del_yn', 'N');
        $query = $this->db->get();
        $result = $query->row_array();

        return $result['group_id'] ?? null;
    }

    public function update_group($group_id, $group_name, $leader_name, $new_name) {
        $this->db->where('group_id', $group_id);
        $this->db->update('wb_group', array(
            'group_name' => $group_name,
            'leader_name' => $leader_name,
            'new_name' => $new_name,
            'modi_date' => date('Y-m-d H:i:s')
        ));
        return $this->db->affected_rows() > 0;
    }



    public function update_del_yn($group_id){
        $this->db->where('group_id', $group_id);
        $this->db->update('wb_group', array('del_yn' => 'Y', 'del_date' => date('Y-m-d H:i:s')));
        return $this->db->affected_rows() > 0;
    }

    public function get_user_groups($user_id) {
        $this->db->select('wb_group.group_id as group_id, wb_group.group_name, wb_group.leader_name, wb_group.new_name');
        $this->db->from('wb_group');
        $this->db->join('wb_group_user', 'wb_group.group_id = wb_group_user.group_id');
        $this->db->where('wb_group_user.user_id', $user_id);
        $this->db->where('wb_group.del_yn', 'N');
        $query = $this->db->get();
        return $query->result_array();
    }





}

