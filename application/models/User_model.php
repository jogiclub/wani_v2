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
        return $this->db->insert_id();
    }

    public function get_user_groups($user_id) {
        $this->db->select('wb_group.idx as org_id, wb_group.group_name');
        $this->db->from('wb_group');
        $this->db->join('wb_group_user', 'wb_group.idx = wb_group_user.org_id');
        $this->db->where('wb_group_user.user_id', $user_id);
        $query = $this->db->get();
        return $query->result_array();
    }


    public function get_group_user_count($org_id) {
        $this->db->where('wb_group_user.org_id', $org_id);
        $this->db->from('wb_group_user');
        $this->db->join('wb_user', 'wb_user.user_id = wb_group_user.user_id');
        $this->db->where('wb_user.del_yn', 'N');
        return $this->db->count_all_results();
    }


    public function get_group_user_level($user_id, $org_id) {
        $this->db->select('level');
        $this->db->where('user_id', $user_id);
        $this->db->where('org_id', $org_id);
        $query = $this->db->get('wb_group_user');
        $result = $query->row_array();
        return $result ? $result['level'] : 0;
    }


    public function get_group_users($org_id) {
        $this->db->select('wb_user.idx, wb_user.user_id, wb_user.user_name, wb_group_user.level, wb_user.user_mail, wb_user.user_hp, wb_user.master_yn');
        $this->db->from('wb_user');
        $this->db->join('wb_group_user', 'wb_user.user_id = wb_group_user.user_id');
        $this->db->where('wb_group_user.org_id', $org_id);
        $this->db->where('wb_user.del_yn', 'N');
        $query = $this->db->get();
        return $query->result_array();
    }




    public function save_user($user_id, $user_name, $user_hp, $level, $org_id) {
        $data = array(
            'user_name' => $user_name,
            'user_hp' => $user_hp
        );

        $this->db->where('user_id', $user_id);
        $this->db->update('wb_user', $data);

        $group_user_data = array(
            'level' => $level
        );

        $this->db->where('user_id', $user_id);
        $this->db->where('org_id', $org_id);
        $result = $this->db->update('wb_group_user', $group_user_data);

        return $result;
    }


    public function delete_user($user_id, $org_id) {
        $data = array(
            'del_yn' => 'Y',
            'del_date' => date('Y-m-d H:i:s')
        );

        $this->db->where('user_id', $user_id);
        $this->db->update('wb_user', $data);

        $this->db->where('user_id', $user_id);
        $this->db->where('org_id', $org_id);
        $this->db->delete('wb_group_user');

        return $this->db->affected_rows() > 0;
    }





    public function get_user_by_id($user_id) {
        $this->db->where('user_id', $user_id);
        $query = $this->db->get('wb_user');
        return $query->row_array();
    }


    public function update_user($user_id, $data) {
        $this->db->where('user_id', $user_id);
        $this->db->update('wb_user', $data);
    }


    public function insert_group_user($data) {
        $this->db->insert('wb_group_user', $data);
        return $this->db->insert_id();
    }

    public function get_group_user($user_id, $org_id) {
        $this->db->where('user_id', $user_id);
        $this->db->where('org_id', $org_id);
        $query = $this->db->get('wb_group_user');
        return $query->row_array();
    }

    public function get_user_by_email($email) {
        $this->db->where('user_mail', $email);
        $query = $this->db->get('wb_user');
        return $query->row_array();
    }

}
