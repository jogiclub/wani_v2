<?php
class Memo_model extends CI_Model {
    public function __construct() {
        parent::__construct();
        $this->load->database();
    }

    public function save_memo($data) {
        $this->db->insert('wb_memo', $data);
        return $this->db->affected_rows() > 0;
    }

    public function get_memo_list($member_idx, $limit, $offset) {
        $this->db->select('*');
        $this->db->from('wb_memo');
        $this->db->where('member_idx', $member_idx);
        $this->db->order_by('regi_date', 'DESC');
        $this->db->limit($limit, $offset);

        $query = $this->db->get();

        return $query->result_array();
    }


    public function delete_memo($idx) {
        $this->db->where('idx', $idx);
        $this->db->delete('wb_memo');
        return $this->db->affected_rows() > 0;
    }

}