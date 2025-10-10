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

	public function get_user_by_id($user_id) {
		$this->db->where('user_id', $user_id);
		$query = $this->db->get('wb_user');
		return $query->row_array();
	}

//	public function get_user_orgs($user_id) {
//		$this->db->select('wb_org.org_id as org_id, wb_org.org_name');
//		$this->db->from('wb_org');
//		$this->db->join('wb_org_user', 'wb_org.org_id = wb_org_user.org_id');
//		$this->db->where('wb_org_user.user_id', $user_id);
//		$query = $this->db->get();
//		return $query->result_array();
//	}

	public function get_org_user_count($org_id) {
		$this->db->where('wb_org_user.org_id', $org_id);
		$this->db->from('wb_org_user');
		$this->db->join('wb_user', 'wb_user.user_id = wb_org_user.user_id');
		$this->db->where('wb_user.del_yn', 'N');
		return $this->db->count_all_results();
	}

	public function get_org_user_level($user_id, $org_id) {
		$this->db->select('level');
		$this->db->where('user_id', $user_id);
		$this->db->where('org_id', $org_id);
		$query = $this->db->get('wb_org_user');
		$result = $query->row_array();
		return $result ? $result['level'] : 0;
	}

	public function get_org_users($org_id) {
		$this->db->select('wb_user.idx, wb_user.user_id, wb_user.user_name, wb_org_user.level, wb_user.user_mail, wb_user.user_hp, wb_user.master_yn');
		$this->db->from('wb_user');
		$this->db->join('wb_org_user', 'wb_user.user_id = wb_org_user.user_id');
		$this->db->where('wb_org_user.org_id', $org_id);
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

		$org_user_data = array(
			'level' => $level
		);

		$this->db->where('user_id', $user_id);
		$this->db->where('org_id', $org_id);
		$result = $this->db->update('wb_org_user', $org_user_data);

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
		$this->db->delete('wb_org_user');

		return $this->db->affected_rows() > 0;
	}

	/**
	 * 사용자 정보 업데이트
	 */
	public function update_user($user_id, $update_data)
	{
		$this->db->where('user_id', $user_id);
		$result = $this->db->update('wb_user', $update_data);

		if ($result && $this->db->affected_rows() > 0) {
			log_message('info', "사용자 {$user_id} 정보 업데이트 완료");
			return true;
		}

		return false;
	}

	public function insert_org_user($data) {
		$this->db->insert('wb_org_user', $data);
		return $this->db->insert_id();
	}

	public function get_org_user($user_id, $org_id) {
		$this->db->where('user_id', $user_id);
		$this->db->where('org_id', $org_id);
		$query = $this->db->get('wb_org_user');
		return $query->row_array();
	}

	public function get_user_by_email($email) {
		$this->db->where('user_mail', $email);
		$query = $this->db->get('wb_user');
		return $query->row_array();
	}


	/**
	 * 마스터 사용자 목록 조회
	 */
	public function get_master_users()
	{
		$this->db->select('
        idx,
        user_id,
        user_name,
        user_mail,
        user_hp,
        user_profile_image,
        managed_menus,
        managed_areas,
        regi_date,
        modi_date
    ');
		$this->db->from('wb_user');
		$this->db->where('master_yn', 'Y');
		$this->db->where('del_yn', 'N');
		$this->db->order_by('regi_date', 'DESC');

		$query = $this->db->get();
		$users = $query->result_array();

		// managed_menus와 managed_areas를 배열로 변환
		foreach ($users as &$user) {
			if (!empty($user['managed_menus'])) {
				$user['managed_menus'] = json_decode($user['managed_menus'], true);
			} else {
				$user['managed_menus'] = array();
			}

			if (!empty($user['managed_areas'])) {
				$user['managed_areas'] = json_decode($user['managed_areas'], true);
			} else {
				$user['managed_areas'] = array();
			}
		}

		return $users;
	}

	/**
	 * 마스터 사용자 정보 업데이트
	 */
	public function update_master_user($user_id, $data)
	{
		$this->db->trans_start();

		$update_data = array(
			'user_name' => $data['user_name'],
			'user_mail' => $data['user_mail'],
			'user_hp' => $data['user_hp'],
			'modi_date' => date('Y-m-d H:i:s')
		);

		// managed_menus 처리
		if (isset($data['managed_menus'])) {
			if (is_array($data['managed_menus']) && !empty($data['managed_menus'])) {
				$update_data['managed_menus'] = json_encode($data['managed_menus']);
			} else {
				$update_data['managed_menus'] = null;
			}
		}

		// managed_areas 처리
		if (isset($data['managed_areas'])) {
			if (is_array($data['managed_areas']) && !empty($data['managed_areas'])) {
				$update_data['managed_areas'] = json_encode($data['managed_areas']);
			} else {
				$update_data['managed_areas'] = null;
			}
		}

		$this->db->where('user_id', $user_id);
		$this->db->where('master_yn', 'Y');
		$this->db->update('wb_user', $update_data);

		$this->db->trans_complete();

		return $this->db->trans_status();
	}

}
