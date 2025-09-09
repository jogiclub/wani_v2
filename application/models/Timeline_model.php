<?php
/**
 * 역할: 회원 타임라인 데이터 관리 모델
 */

class Timeline_model extends CI_Model {

	public function __construct() {
		parent::__construct();
		$this->load->database();
	}

	/**
	 * 회원 타임라인 목록 조회
	 */
	public function get_member_timeline($member_idx, $limit = 20, $offset = 0) {
		$this->db->select('idx, member_idx, timeline_type, timeline_date, timeline_content, regi_date, modi_date, user_id');
		$this->db->from('wb_member_timeline');
		$this->db->where('member_idx', $member_idx);
		$this->db->order_by('timeline_date', 'DESC');
		$this->db->order_by('regi_date', 'DESC');
		$this->db->limit($limit, $offset);

		$query = $this->db->get();
		return $query->result_array();
	}

	/**
	 * 타임라인 항목 저장
	 */
	public function save_timeline($data) {
		$this->db->insert('wb_member_timeline', $data);
		return $this->db->affected_rows() > 0;
	}

	/**
	 * 타임라인 항목 수정
	 */
	public function update_timeline($idx, $data) {
		$this->db->where('idx', $idx);
		$this->db->update('wb_member_timeline', $data);
		return $this->db->affected_rows() > 0;
	}

	/**
	 * 타임라인 항목 삭제
	 */
	public function delete_timeline($idx) {
		$this->db->where('idx', $idx);
		$this->db->delete('wb_member_timeline');
		return $this->db->affected_rows() > 0;
	}

	/**
	 * 특정 타임라인 항목 조회
	 */
	public function get_timeline_by_idx($idx) {
		$this->db->select('idx, member_idx, timeline_type, timeline_date, timeline_content, regi_date, modi_date, user_id');
		$this->db->from('wb_member_timeline');
		$this->db->where('idx', $idx);

		$query = $this->db->get();
		return $query->row_array();
	}

	/**
	 * 회원의 타임라인 개수 조회
	 */
	public function get_member_timeline_count($member_idx) {
		$this->db->from('wb_member_timeline');
		$this->db->where('member_idx', $member_idx);
		return $this->db->count_all_results();
	}

	/**
	 * 특정 기간의 타임라인 조회
	 */
	public function get_timeline_by_period($member_idx, $start_date, $end_date) {
		$this->db->select('idx, member_idx, timeline_type, timeline_date, timeline_content, regi_date, modi_date, user_id');
		$this->db->from('wb_member_timeline');
		$this->db->where('member_idx', $member_idx);
		$this->db->where('timeline_date >=', $start_date);
		$this->db->where('timeline_date <=', $end_date);
		$this->db->order_by('timeline_date', 'DESC');

		$query = $this->db->get();
		return $query->result_array();
	}
}
