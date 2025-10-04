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
	 * 타임라인 항목 삭제
	 */
	public function delete_timeline($idx) {
		$this->db->where('idx', $idx);
		$this->db->delete('wb_member_timeline');
		return $this->db->affected_rows() > 0;
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



	/**
	 * 타임라인 개수 조회
	 */
	public function get_timelines_count($org_id, $filters = array())
	{
		$this->db->select('COUNT(*) as count');
		$this->db->from('wb_member_timeline t');
		$this->db->join('wb_member m', 't.member_idx = m.member_idx', 'left');
		$this->db->where('m.org_id', $org_id);

		// 타임라인 타입 필터 (여러 개 선택 가능)
		if (!empty($filters['timeline_types']) && is_array($filters['timeline_types'])) {
			$this->db->where_in('t.timeline_type', $filters['timeline_types']);
		}

		// 검색어 필터
		if (!empty($filters['search_text'])) {
			$this->db->group_start();
			$this->db->like('m.member_name', $filters['search_text']);
			$this->db->or_like('t.timeline_content', $filters['search_text']);
			$this->db->group_end();
		}

		// 년/월 필터 (등록일 기준)
		if (!empty($filters['year']) && !empty($filters['month'])) {
			$year = $filters['year'];
			$month = str_pad($filters['month'], 2, '0', STR_PAD_LEFT);
			$this->db->where("DATE_FORMAT(t.regi_date, '%Y-%m') =", "{$year}-{$month}");
		}

		$query = $this->db->get();
		$result = $query->row_array();
		return $result['count'];
	}


	/**
	 * 타임라인 일괄추가 (여러 회원)
	 */
	public function add_timelines($member_idxs, $data)
	{
		$this->db->trans_start();

		foreach ($member_idxs as $member_idx) {
			$insert_data = array_merge(array('member_idx' => $member_idx), $data);
			$this->db->insert('wb_member_timeline', $insert_data);
		}

		$this->db->trans_complete();

		return $this->db->trans_status();
	}

	/**
	 * 타임라인 수정
	 */
	public function update_timeline($idx, $data)
	{
		$this->db->where('idx', $idx);
		return $this->db->update('wb_member_timeline', $data);
	}

	/**
	 * 타임라인 삭제
	 */
	public function delete_timelines($idxs)
	{
		$this->db->where_in('idx', $idxs);
		return $this->db->delete('wb_member_timeline');
	}

	/**
	 * 타임라인 상세 조회
	 */
	public function get_timeline_by_idx($idx)
	{
		$this->db->select('
			t.*,
			m.member_name,
			u.user_name as regi_user_name
		');
		$this->db->from('wb_member_timeline t');
		$this->db->join('wb_member m', 't.member_idx = m.member_idx', 'left');
		$this->db->join('wb_user u', 't.user_id = u.user_id', 'left');
		$this->db->where('t.idx', $idx);

		$query = $this->db->get();
		return $query->row_array();
	}

	/**
	 * 타임라인 목록 조회
	 */
	public function get_timelines($org_id, $filters = array())
	{
		$this->db->select('
		t.idx,
		t.timeline_type,
		t.timeline_date,
		t.timeline_content,
		t.regi_date,
		t.modi_date,
		m.member_name,
		u.user_name as regi_user_name
	');
		$this->db->from('wb_member_timeline t');
		$this->db->join('wb_member m', 't.member_idx = m.member_idx', 'left');
		$this->db->join('wb_user u', 't.user_id = u.user_id', 'left');
		$this->db->where('m.org_id', $org_id);

		// 타임라인 타입 필터 (여러 개 선택 가능)
		if (!empty($filters['timeline_types']) && is_array($filters['timeline_types'])) {
			$this->db->where_in('t.timeline_type', $filters['timeline_types']);
		}

		// 검색어 필터
		if (!empty($filters['search_text'])) {
			$this->db->group_start();
			$this->db->like('m.member_name', $filters['search_text']);
			$this->db->or_like('t.timeline_content', $filters['search_text']);
			$this->db->group_end();
		}

		// 년/월 필터 (등록일 기준)
		if (!empty($filters['year']) && !empty($filters['month'])) {
			$year = $filters['year'];
			$month = str_pad($filters['month'], 2, '0', STR_PAD_LEFT);
			$this->db->where("DATE_FORMAT(t.regi_date, '%Y-%m') =", "{$year}-{$month}");
		}

		$this->db->order_by('t.timeline_date', 'DESC');
		$this->db->order_by('t.regi_date', 'DESC');

		$query = $this->db->get();
		return $query->result_array();
	}

}
