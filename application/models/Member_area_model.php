<?php
class Member_area_model extends CI_Model {
    public function __construct() {
        parent::__construct();
        $this->load->database();
    }

    public function get_member_areas($group_id) {
        $this->db->select('area_idx, area_name');
        $this->db->from('wb_member_area');
        $this->db->where('group_id', $group_id);
        $this->db->order_by('area_order', 'ASC');

        $query = $this->db->get();
        return $query->result_array();
    }


	public function get_max_order($group_id) {
		$this->db->select_max('area_order');
		$this->db->where('group_id', $group_id);
		$query = $this->db->get('wb_member_area');
		$result = $query->row_array();
		return $result['area_order'] ?? 0;
	}

	public function add_area($data) {
		$this->db->insert('wb_member_area', $data);
		return $this->db->affected_rows() > 0;
	}


	public function update_areas($areas) {
		$success = true;

		foreach ($areas as $area) {
			$this->db->where('area_idx', $area['area_idx']);
			if (!$this->db->update('wb_member_area', ['area_name' => $area['area_name']])) {
				$success = false;
				break;
			}
		}

		return $success;
	}
	public function check_area_members($area_idx) {
		$this->db->where('area_idx', $area_idx);
		$this->db->where('del_yn', 'N');
		return $this->db->count_all_results('wb_member');
	}

	public function delete_area($area_idx) {
		$this->db->where('area_idx', $area_idx);
		return $this->db->delete('wb_member_area');
	}
}
