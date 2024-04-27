<?php
class Attendance_model extends CI_Model {
    public function __construct() {
        parent::__construct();
        $this->load->database();
    }

    public function get_attendance_types($group_id) {
        $this->db->select('att_type_idx, att_type_category_name, att_type_category_idx, att_type_nickname, att_type_name');
        $this->db->from('wb_att_type');
        $this->db->where('group_id', $group_id);
        $this->db->order_by('att_type_category_idx', 'ASC');
        $this->db->order_by('att_type_idx', 'ASC');

        $query = $this->db->get();
        return $query->result();
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




    public function delete_attendance_by_date($member_idx, $att_date) {
        $this->db->where('member_idx', $member_idx);
        $this->db->where('att_date', $att_date);
        $this->db->delete('wb_member_att');
    }



    public function get_group_attendance_data($group_id, $start_date, $end_date) {
        $this->db->select("a.member_idx, GROUP_CONCAT(at.att_type_nickname ORDER BY at.att_type_category_idx, at.att_type_idx SEPARATOR ',') AS att_type_nicknames");
        $this->db->from('wb_member_att a');
        $this->db->join('wb_att_type at', 'a.att_type_idx = at.att_type_idx', 'left');
        $this->db->where('a.group_id', $group_id);
        $this->db->where('a.att_date >=', $start_date);
        $this->db->where('a.att_date <=', $end_date);
        $this->db->group_by('a.member_idx');
        $this->db->having('COUNT(a.att_type_idx) > 0'); // 실제로 출석한 멤버들만 가져오도록 조건 추가

        $query = $this->db->get();
        $result = $query->result_array();

        $attendance_data = array();
        foreach ($result as $row) {
            $attendance_data[$row['member_idx']] = $row['att_type_nicknames'];
        }

        return $attendance_data;
    }


    public function get_attendance_types_by_group($group_id) {
        // 출석 종류 카테고리 가져오기
        $this->db->select('att_type_category_name');
        $this->db->from('wb_att_type');
        $this->db->where('group_id', $group_id);
        $this->db->group_by('att_type_category_name');
        $this->db->order_by('att_type_category_idx', 'ASC');
        $query = $this->db->get();
//        print_r($this->db->last_query());
//        exit;
        $categories = $query->result_array();




        // 출석 종류 가져오기
        $this->db->select('att_type_idx, att_type_category_name, att_type_category_idx, att_type_name, att_type_nickname');
        $this->db->from('wb_att_type');
        $this->db->where('group_id', $group_id);
        $this->db->order_by('att_type_category_idx', 'ASC');
        $this->db->order_by('att_type_idx', 'ASC');
        $query = $this->db->get();
        $attendance_types = $query->result_array();

        return array(
            'categories' => $categories,
            'attendance_types' => $attendance_types
        );
    }





}