<?php
class Invite_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    public function insert_invite($data) {
        $this->db->insert('wb_invite', $data);
    }

    public function delete_invite($invite_code) {
        $this->db->delete('wb_invite', array('invite_code' => $invite_code));
    }


    public function update_invite($invite_code, $data) {
        $this->db->where('invite_code', $invite_code);
        $this->db->update('wb_invite', $data);
    }


    public function get_invite_by_code($invite_code) {
        $query = $this->db->get_where('wb_invite', array('invite_code' => $invite_code));
//        print_r($this->db->last_query());

//        return $query->$this->db->last_query();
        return $query->row_array();
    }

}
