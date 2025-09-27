<?php
/**
 * 파일 위치: application/models/Message_model.php
 * 역할: 메시지 관련 데이터베이스 처리
 */

defined('BASEPATH') or exit('No direct script access allowed');

class Message_model extends CI_Model
{
	public function __construct()
	{
		parent::__construct();
		$this->load->database();
	}

	/**
	 * 사용자의 읽지 않은 메시지 목록 조회
	 */
	public function get_unread_messages($user_id, $org_id)
	{
		$this->db->select('*');
		$this->db->from('wb_message');
		$this->db->where('user_id', $user_id);
		$this->db->where('org_id', $org_id);
		$this->db->where('read_yn', 'N');
		$this->db->where('del_yn', 'N');
		$this->db->order_by('message_date', 'DESC');
		$query = $this->db->get();
		return $query->result_array();
	}

	/**
	 * 사용자의 최근 메시지 목록 조회 (읽음/안읽음 포함, 삭제되지 않은 메시지만)
	 */
	public function get_recent_messages($user_id, $org_id, $limit = 20)
	{
		$this->db->select('*');
		$this->db->from('wb_message');
		$this->db->where('user_id', $user_id);
		$this->db->where('org_id', $org_id);
		$this->db->where('del_yn', 'N');
		$this->db->order_by('message_date', 'DESC');
		$this->db->limit($limit);
		$query = $this->db->get();
		return $query->result_array();
	}

	/**
	 * 사용자의 모든 메시지 목록 조회 (읽음/안읽음 포함)
	 */
	public function get_all_messages($user_id, $org_id, $limit = null)
	{
		$this->db->select('*');
		$this->db->from('wb_message');
		$this->db->where('user_id', $user_id);
		$this->db->where('org_id', $org_id);
		$this->db->where('del_yn', 'N');
		$this->db->order_by('message_date', 'DESC');

		if ($limit) {
			$this->db->limit($limit);
		}

		$query = $this->db->get();
		return $query->result_array();
	}

	/**
	 * 읽지 않은 메시지 수 조회
	 */
	public function get_unread_count($user_id, $org_id)
	{
		$this->db->select('COUNT(*) as count');
		$this->db->from('wb_message');
		$this->db->where('user_id', $user_id);
		$this->db->where('org_id', $org_id);
		$this->db->where('read_yn', 'N');
		$this->db->where('del_yn', 'N');
		$query = $this->db->get();
		$result = $query->row_array();
		return $result['count'];
	}

	/**
	 * 메시지를 읽음으로 표시
	 */
	public function mark_as_read($idx, $user_id = null)
	{
		$update_data = array(
			'read_yn' => 'Y',
			'modi_date' => date('Y-m-d H:i:s')
		);

		$this->db->where('idx', $idx);

		// 보안을 위해 user_id 확인
		if ($user_id) {
			$this->db->where('user_id', $user_id);
		}

		return $this->db->update('wb_message', $update_data);
	}

	/**
	 * 여러 메시지를 읽음으로 표시
	 */
	public function mark_multiple_as_read($message_ids, $user_id)
	{
		if (empty($message_ids)) {
			return false;
		}

		$update_data = array(
			'read_yn' => 'Y',
			'modi_date' => date('Y-m-d H:i:s')
		);

		$this->db->where_in('idx', $message_ids);
		$this->db->where('user_id', $user_id);

		return $this->db->update('wb_message', $update_data);
	}

	/**
	 * 메시지 상세 조회
	 */
	public function get_message_by_idx($idx, $user_id = null)
	{
		$this->db->select('*');
		$this->db->from('wb_message');
		$this->db->where('idx', $idx);
		$this->db->where('del_yn', 'N');

		// 보안을 위해 user_id 확인
		if ($user_id) {
			$this->db->where('user_id', $user_id);
		}

		$query = $this->db->get();
		return $query->row_array();
	}

	/**
	 * 메시지 삭제 (소프트 삭제)
	 */
	public function delete_message($idx, $user_id)
	{
		$update_data = array(
			'del_yn' => 'Y',
			'modi_date' => date('Y-m-d H:i:s')
		);

		$this->db->where('idx', $idx);
		$this->db->where('user_id', $user_id);

		return $this->db->update('wb_message', $update_data);
	}

	/**
	 * 사용자의 모든 메시지 삭제 (소프트 삭제)
	 */
	public function delete_all_messages($user_id, $org_id)
	{
		$update_data = array(
			'del_yn' => 'Y',
			'modi_date' => date('Y-m-d H:i:s')
		);

		$this->db->where('user_id', $user_id);
		$this->db->where('org_id', $org_id);
		$this->db->where('del_yn', 'N');

		return $this->db->update('wb_message', $update_data);
	}

	/**
	 * 메시지 타입별 메시지 수 조회
	 */
	public function get_message_count_by_type($user_id, $org_id, $message_type = null)
	{
		$this->db->select('message_type, COUNT(*) as count');
		$this->db->from('wb_message');
		$this->db->where('user_id', $user_id);
		$this->db->where('org_id', $org_id);
		$this->db->where('read_yn', 'N');
		$this->db->where('del_yn', 'N');

		if ($message_type) {
			$this->db->where('message_type', $message_type);
		}

		$this->db->group_by('message_type');
		$query = $this->db->get();
		return $query->result_array();
	}

	/**
	 * 메시지 생성
	 */
	public function create_message($message_data)
	{
		$message_data['regi_date'] = date('Y-m-d H:i:s');
		$message_data['message_date'] = isset($message_data['message_date']) ? $message_data['message_date'] : date('Y-m-d H:i:s');

		return $this->db->insert('wb_message', $message_data);
	}

	/**
	 * 메시지 일괄 생성 (다수 사용자에게 동일 메시지 발송)
	 */
	public function create_messages_bulk($message_base_data, $user_org_list)
	{
		if (empty($user_org_list)) {
			return false;
		}

		$insert_data = array();
		$current_time = date('Y-m-d H:i:s');

		foreach ($user_org_list as $user_org) {
			$message_data = $message_base_data;
			$message_data['user_id'] = $user_org['user_id'];
			$message_data['org_id'] = $user_org['org_id'];
			$message_data['regi_date'] = $current_time;
			$message_data['message_date'] = isset($message_base_data['message_date']) ? $message_base_data['message_date'] : $current_time;
			$message_data['read_yn'] = 'N';
			$message_data['del_yn'] = 'N';

			$insert_data[] = $message_data;
		}

		return $this->db->insert_batch('wb_message', $insert_data);
	}

	/**
	 * 조직의 모든 사용자에게 메시지 발송
	 */
	public function send_message_to_org($org_id, $message_data)
	{
		// 조직의 모든 활성 사용자 조회
		$this->db->select('wou.user_id, wou.org_id');
		$this->db->from('wb_org_user wou');
		$this->db->join('wb_user wu', 'wou.user_id = wu.user_id');
		$this->db->where('wou.org_id', $org_id);
		$this->db->where('wu.del_yn', 'N');
		$query = $this->db->get();
		$user_org_list = $query->result_array();

		if (empty($user_org_list)) {
			return false;
		}

		return $this->create_messages_bulk($message_data, $user_org_list);
	}

	/**
	 * 특정 그룹(member_area)의 사용자들에게 메시지 발송
	 */
	public function send_message_to_group($org_id, $area_idx, $message_data)
	{
		// 해당 그룹의 모든 회원 조회
		$this->db->select('DISTINCT wou.user_id, wou.org_id');
		$this->db->from('wb_member wm');
		$this->db->join('wb_org_user wou', 'wm.regi_user_id = wou.user_id AND wm.org_id = wou.org_id');
		$this->db->join('wb_user wu', 'wou.user_id = wu.user_id');
		$this->db->where('wm.org_id', $org_id);
		$this->db->where('wm.area_idx', $area_idx);
		$this->db->where('wm.del_yn', 'N');
		$this->db->where('wu.del_yn', 'N');
		$query = $this->db->get();
		$user_org_list = $query->result_array();

		if (empty($user_org_list)) {
			return false;
		}

		return $this->create_messages_bulk($message_data, $user_org_list);
	}

	/**
	 * 메시지 통계 조회
	 */
	public function get_message_statistics($org_id, $start_date = null, $end_date = null)
	{
		$this->db->select('
            COUNT(*) as total_messages,
            SUM(CASE WHEN read_yn = "Y" THEN 1 ELSE 0 END) as read_messages,
            SUM(CASE WHEN read_yn = "N" THEN 1 ELSE 0 END) as unread_messages,
            message_type,
            DATE(message_date) as message_date
        ');
		$this->db->from('wb_message');
		$this->db->where('org_id', $org_id);
		$this->db->where('del_yn', 'N');

		if ($start_date) {
			$this->db->where('DATE(message_date) >=', $start_date);
		}

		if ($end_date) {
			$this->db->where('DATE(message_date) <=', $end_date);
		}

		$this->db->group_by('message_type, DATE(message_date)');
		$this->db->order_by('message_date', 'DESC');

		$query = $this->db->get();
		return $query->result_array();
	}
}
?>
