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
}