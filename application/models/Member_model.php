<?php
class Member_model extends CI_Model {
    public function __construct() {
        parent::__construct();
        $this->load->database();
    }



    public function add_member($data) {
        $this->db->insert('wb_member', $data);
        return $this->db->affected_rows() > 0;
    }

    public function get_group_members($group_id, $start_date = null, $end_date = null) {
        $this->db->select('m.member_idx, m.group_id, m.member_name, m.photo, m.leader_yn, m.new_yn');
        $this->db->from('wb_member m');
        $this->db->where('m.group_id', $group_id);
        $this->db->where('m.del_yn', 'N');

        if ($start_date && $end_date) {
            $this->db->select('GROUP_CONCAT(CONCAT(at.att_type_nickname, ",", at.att_type_idx, ",", at.att_type_category_idx, ",", at.att_type_color) SEPARATOR "|") AS att_type_data');
            $this->db->join('wb_member_att a', 'm.member_idx = a.member_idx AND a.att_date >= "' . $start_date . '" AND a.att_date <= "' . $end_date . '"', 'left');
            $this->db->join('wb_att_type at', 'a.att_type_idx = at.att_type_idx', 'left');
            $this->db->group_by('m.member_idx');
        }

        $this->db->order_by('m.area ASC');
        $this->db->order_by('m.leader_yn ASC');
        $this->db->order_by('m.member_idx ASC');

        $query = $this->db->get();

        return $query->result_array();
    }

    public function get_member_by_idx($member_idx) {
        $this->db->select('*');
        $this->db->from('wb_member');
        $this->db->where('member_idx', $member_idx);

        $query = $this->db->get();

        return $query->row_array();
    }

    public function update_member($member_idx, $data) {
        $this->db->where('member_idx', $member_idx);
        $this->db->update('wb_member', $data);

        return $this->db->affected_rows() > 0;
    }

}


