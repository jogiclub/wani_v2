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
        $this->db->select('m.member_idx, m.group_id, m.member_name, m.photo, m.leader_yn, m.new_yn, m.member_birth, m.grade, m.area');
        $this->db->from('wb_member m');
        $this->db->where('m.group_id', $group_id);
        $this->db->where('m.del_yn', 'N');

        if ($start_date && $end_date) {
            $this->db->select('GROUP_CONCAT(CONCAT(at.att_type_nickname, ",", at.att_type_idx, ",", at.att_type_category_idx, ",", at.att_type_color) SEPARATOR "|") AS att_type_data');
            $this->db->join('wb_member_att a', 'm.member_idx = a.member_idx AND a.att_date >= "' . $start_date . '" AND a.att_date <= "' . $end_date . '"', 'left');
            $this->db->join('wb_att_type at', 'a.att_type_idx = at.att_type_idx', 'left');
            $this->db->group_by('m.member_idx');
        }
        $this->db->order_by('m.grade ASC');
//        $this->db->order_by('m.area ASC');
        $this->db->order_by('m.leader_yn ASC');
        $this->db->order_by('m.member_name ASC');

        $query = $this->db->get();
//        print_r($this->db->last_query());
//        exit;
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

    public function update_multiple_members($member_idx, $data, $all_grade_check, $all_area_check) {
        $this->db->where('member_idx', $member_idx);
        $this->db->where('del_yn', 'N');

        $member = $this->db->get('wb_member')->row_array();
        $prev_grade = $member['grade'];
        $prev_area = $member['area'];

        if ($all_grade_check && isset($data['grade'])) {
            $this->db->where('grade', $prev_grade);
            $this->db->where('del_yn', 'N');
            $this->db->update('wb_member', array('grade' => $data['grade'], 'modi_date' => $data['modi_date']));
        }

        if ($all_area_check && isset($data['area'])) {
            $this->db->where('area', $prev_area);
            $this->db->where('del_yn', 'N');
            $this->db->update('wb_member', array('area' => $data['area'], 'modi_date' => $data['modi_date']));
        }

        $result = $this->db->update('wb_member', $data, array('member_idx' => $member_idx));




        return $result;
    }


    public function get_active_members($group_id, $five_weeks_ago) {
        $this->db->select('member_idx');
        $this->db->from('wb_member_att');
        $this->db->where('group_id', $group_id);
        $this->db->where('att_date >=', $five_weeks_ago);
        $this->db->distinct();

        $query = $this->db->get();

//        print_r($this->db->last_query());
//        exit;

        return $query->result_array();
    }


}


