<?php
class Attendance_model extends CI_Model {
    public function __construct() {
        parent::__construct();
        $this->load->database();
    }

    public function get_attendance_types($group_id) {
        $this->db->select('att_type_idx, att_type_category_name, att_type_category_idx, att_type_nickname, att_type_name, att_type_color');
        $this->db->from('wb_att_type');
        $this->db->where('group_id', $group_id);
        $this->db->order_by('att_type_category_idx', 'ASC');
        $this->db->order_by('att_type_idx', 'ASC');


        $query = $this->db->get();

//        print_r($this->db->last_query());
//        exit;


        return $query->result_array();
    }



    public function save_attendance($member_idx, $attendance_data) {
        $att_date = date('Y-m-d');
        $group_id = $this->session->userdata('group_id');

        foreach ($attendance_data as $att_type_idx) {
            $data = array(
                'att_date' => $att_date,
                'att_type_idx' => $att_type_idx,
                'member_idx' => $member_idx,
                'group_id' => $group_id
            );

            $this->db->insert('wb_member_att', $data);
        }

        return $this->db->affected_rows() > 0;
    }

    public function save_attendance_data($attendance_data, $group_id, $start_date, $end_date) {
        foreach ($attendance_data as $data) {
            $member_idx = $data['member_idx'];
            $att_type_idx = $data['att_type_idx'];

            // 새로운 출석 정보 저장
            $att_data = array(
                'att_date' => $start_date,
                'att_type_idx' => $att_type_idx,
                'member_idx' => $member_idx,
                'group_id' => $group_id
            );
            $this->db->insert('wb_member_att', $att_data);
        }

        return $this->db->affected_rows() > 0;
    }

    public function update_attendance_type($att_type_idx, $att_type_name, $att_type_nickname, $att_type_color)
    {
        $data = array(
            'att_type_name' => $att_type_name,
            'att_type_nickname' => $att_type_nickname,
            'att_type_color' => $att_type_color
        );
        $this->db->where('att_type_idx', $att_type_idx);
        $this->db->update('wb_att_type', $data);
        return $this->db->affected_rows() > 0;
    }

    public function get_max_category_idx($group_id) {
        $this->db->select_max('att_type_category_idx');
        $this->db->where('group_id', $group_id);
        $query = $this->db->get('wb_att_type');
        $result = $query->row_array();
        return $result['att_type_category_idx'] ?? 0;
    }

    public function save_attendance_type($group_id, $att_type_category_name, $att_type_name, $att_type_nickname, $att_type_color, $att_type_category_idx) {
        $data = array(
            'group_id' => $group_id,
            'att_type_category_name' => $att_type_category_name,
            'att_type_name' => $att_type_name,
            'att_type_nickname' => $att_type_nickname,
            'att_type_color' => $att_type_color,
            'att_type_category_idx' => $att_type_category_idx
        );
        $this->db->insert('wb_att_type', $data);
        return $this->db->affected_rows() > 0;
    }

    public function get_member_attendance($member_idx, $start_date, $end_date) {
        $this->db->select('att_type_idx, att_date');
        $this->db->from('wb_member_att');
        $this->db->where('member_idx', $member_idx);
        $this->db->where('att_date >=', $start_date);
        $this->db->where('att_date <=', $end_date);
        $this->db->order_by('att_date', 'ASC');

        $query = $this->db->get();
        $result = $query->result_array();

        $attendance_data = array();
        foreach ($result as $row) {
            $attendance_data[$row['att_date']][] = strval($row['att_type_idx']);
        }

        return $attendance_data;
    }


// Attendance_model.php
    public function get_group_member_attendance($group_id, $grade, $start_date, $end_date) {
        $this->db->select('ma.member_idx, GROUP_CONCAT(ma.att_type_idx ORDER BY ma.att_type_idx SEPARATOR ",") AS att_type_idxs', false);
        $this->db->from('wb_member_att ma');
        $this->db->join('wb_member m', 'ma.member_idx = m.member_idx', 'inner');
        $this->db->where('m.group_id', $group_id);
        $this->db->where('m.grade', $grade);
        $this->db->where('ma.att_date >=', $start_date);
        $this->db->where('ma.att_date <=', $end_date);
        $this->db->group_by('ma.member_idx');
        $query = $this->db->get();
        $result = $query->result_array();

        $attendance_data = array();
        foreach ($result as $row) {
            $attendance_data[$row['member_idx']] = explode(',', $row['att_type_idxs']);
        }

        return $attendance_data;
    }

    public function delete_attendance_by_date($member_idx, $att_date) {
        $this->db->where('member_idx', $member_idx);
        $this->db->where('att_date', $att_date);
        $this->db->delete('wb_member_att');
    }












    public function get_group_attendance_data($group_id, $start_date, $end_date) {
        $this->db->select("a.member_idx, GROUP_CONCAT(CONCAT(at.att_type_nickname, '|', at.att_type_idx, '|', at.att_type_category_idx, '|', at.att_type_color) ORDER BY at.att_type_category_idx, at.att_type_idx SEPARATOR ',') AS att_type_nicknames");
        $this->db->from('wb_member_att a');
        $this->db->join('wb_att_type at', 'a.att_type_idx = at.att_type_idx', 'left');
        $this->db->where('a.group_id', $group_id);
        $this->db->where('a.att_date >=', $start_date);
        $this->db->where('a.att_date <=', $end_date);
        $this->db->group_by('a.member_idx');
        $this->db->having('COUNT(a.att_type_idx) > 0');

        $query = $this->db->get();
        $result = $query->result_array();



        $attendance_data = array();
        foreach ($result as $row) {
            $attendance_data[$row['member_idx']] = $row['att_type_nicknames'];
        }

        return $attendance_data;
    }



    public function save_single_attendance($data) {
        $this->db->insert('wb_member_att', $data);
        return $this->db->affected_rows() > 0;
    }

    public function delete_attendance_by_category($member_idx, $att_type_category_idx, $att_date) {
        $this->db->where('member_idx', $member_idx);
        $this->db->where('att_date', $att_date);

        if (!empty($att_type_category_idx)) {
            $this->db->where('att_type_idx IN (SELECT att_type_idx FROM wb_att_type WHERE att_type_category_idx = ' . $this->db->escape($att_type_category_idx) . ')', NULL, FALSE);
        }

        $this->db->delete('wb_member_att');
    }


    public function delete_attendance_by_date_range($member_idx, $start_date, $end_date) {
        $this->db->where('member_idx', $member_idx);
        $this->db->where('att_date >=', $start_date);
        $this->db->where('att_date <=', $end_date);
        $this->db->delete('wb_member_att');
    }

    public function get_attendance_type_categories($group_id) {
        $this->db->select('att_type_category_idx, att_type_category_name');
        $this->db->from('wb_att_type');
        $this->db->where('group_id', $group_id);
        $this->db->group_by('att_type_category_idx');
        $query = $this->db->get();
//        print_r($this->db->last_query());
//        exit;
        return $query->result_array();
    }

    public function add_attendance_type($data) {
        $this->db->insert('wb_att_type', $data);
        return $this->db->affected_rows() > 0;
    }


    public function add_attendance_type_category($data) {
        $this->db->insert('wb_att_type', $data);
        return $this->db->affected_rows() > 0;
    }


    public function delete_attendance_type($att_type_idx) {
        $this->db->where('att_type_idx', $att_type_idx);
        $this->db->delete('wb_att_type');
        return $this->db->affected_rows() > 0;
    }

/*
    public function get_attendance_type_count($group_id) {
        $this->db->where('group_id', $group_id);
        $this->db->from('wb_att_type');
        return $this->db->count_all_results();
    }
*/
}