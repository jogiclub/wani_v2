<?php
class User_model extends CI_Model {
    public function __construct() {
        parent::__construct();
        $this->load->database();
    }

    public function check_user($user_id) {
        $query = $this->db->get_where('wb_user', array('user_id' => $user_id));
        return $query->num_rows() > 0;
    }

    public function insert_user($data) {
        $this->db->insert('wb_user', $data);
    }

    public function get_user_groups($user_id) {
        $this->db->select('wb_group.idx as group_id, wb_group.group_name');
        $this->db->from('wb_group');
        $this->db->join('wb_group_user', 'wb_group.idx = wb_group_user.group_id');
        $this->db->where('wb_group_user.user_id', $user_id);
        $query = $this->db->get();
        return $query->result_array();
    }

}