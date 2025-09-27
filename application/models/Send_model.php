<?php
/**
 * 파일 위치: application/models/Send_model.php
 * 역할: 문자 발송 관련 데이터베이스 작업을 처리하는 모델
 */
defined('BASEPATH') or exit('No direct script access allowed');

class Send_model extends CI_Model
{
	public function __construct()
	{
		parent::__construct();
		$this->load->database();
	}

	/**
	 * 선택된 회원들의 정보 조회
	 */
	public function get_selected_members($member_ids, $org_id)
	{
		if (empty($member_ids)) {
			return array();
		}

		$this->db->select('m.member_idx, m.member_name, m.member_phone, a.area_name');
		$this->db->from('wb_member m');
		$this->db->join('wb_member_area a', 'm.area_idx = a.area_idx', 'left');
		$this->db->where('m.org_id', $org_id);
		$this->db->where_in('m.member_idx', $member_ids);
		$this->db->where('m.del_yn', 'N');
		$this->db->where('m.member_phone IS NOT NULL');
		$this->db->where('m.member_phone !=', '');
		$this->db->order_by('m.member_name', 'ASC');

		$query = $this->db->get();
		return $query->result_array();
	}

	/**
	 * 발신번호 목록 조회
	 */
	public function get_sender_numbers($org_id)
	{
		$this->db->select('sender_idx, sender_name, sender_number, is_default');
		$this->db->from('wb_sender_number');
		$this->db->where('org_id', $org_id);
		$this->db->where('active_yn', 'Y');
		$this->db->order_by('is_default', 'DESC');
		$this->db->order_by('sender_name', 'ASC');

		$query = $this->db->get();
		return $query->result_array();
	}

	/**
	 * 메시지 템플릿 목록 조회
	 */
	public function get_message_templates($org_id)
	{
		$this->db->select('template_idx, template_name, template_content');
		$this->db->from('wb_message_template');
		$this->db->where('org_id', $org_id);
		$this->db->where('active_yn', 'Y');
		$this->db->order_by('template_name', 'ASC');

		$query = $this->db->get();
		return $query->result_array();
	}

	/**
	 * 문자 발송 로그 저장
	 */
	public function save_send_log($data)
	{
		return $this->db->insert('wb_send_log', $data);
	}

	/**
	 * 발송 이력 조회
	 */
	public function get_send_history($org_id, $page = 1, $per_page = 20)
	{
		$offset = ($page - 1) * $per_page;

		// 전체 개수 조회
		$this->db->from('wb_send_log');
		$this->db->where('org_id', $org_id);
		$total_count = $this->db->count_all_results();

		// 목록 조회
		$this->db->select('sl.*, m.member_name, u.user_name as sender_name');
		$this->db->from('wb_send_log sl');
		$this->db->join('wb_member m', 'sl.member_idx = m.member_idx', 'left');
		$this->db->join('wb_user u', 'sl.sender_id = u.user_id', 'left');
		$this->db->where('sl.org_id', $org_id);
		$this->db->order_by('sl.send_date', 'DESC');
		$this->db->limit($per_page, $offset);

		$query = $this->db->get();
		$list = $query->result_array();

		return array(
			'list' => $list,
			'total_count' => $total_count,
			'current_page' => $page,
			'per_page' => $per_page,
			'total_pages' => ceil($total_count / $per_page)
		);
	}

	/**
	 * 메시지 템플릿 저장
	 */
	public function save_message_template($data)
	{
		return $this->db->insert('wb_message_template', $data);
	}

	/**
	 * 메시지 템플릿 수정
	 */
	public function update_message_template($template_idx, $data)
	{
		$this->db->where('template_idx', $template_idx);
		return $this->db->update('wb_message_template', $data);
	}

	/**
	 * 메시지 템플릿 삭제
	 */
	public function delete_message_template($template_idx, $org_id)
	{
		$this->db->where('template_idx', $template_idx);
		$this->db->where('org_id', $org_id);
		return $this->db->update('wb_message_template', array('active_yn' => 'N'));
	}

	/**
	 * 발신번호 저장
	 */
	public function save_sender_number($data)
	{
		return $this->db->insert('wb_sender_number', $data);
	}

	/**
	 * 발신번호 수정
	 */
	public function update_sender_number($sender_idx, $data)
	{
		$this->db->where('sender_idx', $sender_idx);
		return $this->db->update('wb_sender_number', $data);
	}

	/**
	 * 발신번호 삭제
	 */
	public function delete_sender_number($sender_idx, $org_id)
	{
		$this->db->where('sender_idx', $sender_idx);
		$this->db->where('org_id', $org_id);
		return $this->db->update('wb_sender_number', array('active_yn' => 'N'));
	}

	/**
	 * 기본 발신번호 설정
	 */
	public function set_default_sender_number($sender_idx, $org_id)
	{
		// 모든 발신번호의 기본값 해제
		$this->db->where('org_id', $org_id);
		$this->db->update('wb_sender_number', array('is_default' => 'N'));

		// 선택된 발신번호를 기본값으로 설정
		$this->db->where('sender_idx', $sender_idx);
		$this->db->where('org_id', $org_id);
		return $this->db->update('wb_sender_number', array('is_default' => 'Y'));
	}

	/**
	 * 발송 통계 조회
	 */
	public function get_send_statistics($org_id, $start_date = null, $end_date = null)
	{
		$this->db->select('
            send_type,
            send_status,
            COUNT(*) as count,
            DATE(send_date) as send_date
        ');
		$this->db->from('wb_send_log');
		$this->db->where('org_id', $org_id);

		if ($start_date) {
			$this->db->where('send_date >=', $start_date);
		}
		if ($end_date) {
			$this->db->where('send_date <=', $end_date);
		}

		$this->db->group_by(array('send_type', 'send_status', 'DATE(send_date)'));
		$this->db->order_by('send_date', 'DESC');

		$query = $this->db->get();
		return $query->result_array();
	}
}
