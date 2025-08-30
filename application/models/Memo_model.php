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
        $this->db->select('m.*, u.user_name');
        $this->db->from('wb_memo m');
        $this->db->join('wb_user u', 'm.user_id = u.user_id', 'left');
        $this->db->where('m.member_idx', $member_idx);
        $this->db->order_by('m.regi_date', 'DESC');
        $this->db->limit($limit, $offset);

        $query = $this->db->get();

        return $query->result_array();
    }


    public function delete_memo($idx) {
        $this->db->where('idx', $idx);
        $this->db->delete('wb_memo');
        return $this->db->affected_rows() > 0;
    }


    public function get_memo_counts($org_id, $start_date, $end_date) {
        $this->db->select('m.member_idx, COUNT(memo.idx) AS memo_count');
        $this->db->from('wb_member m');
        $this->db->join('wb_memo memo', 'm.member_idx = memo.member_idx AND memo.regi_date >= "' . $start_date . '" AND memo.regi_date <= "' . $end_date . '"', 'left');
        $this->db->where('m.org_id', $org_id);
        $this->db->group_by('m.member_idx');

        $query = $this->db->get();
//        print_r($this->db->last_query());
//        exit;
        $result = $query->result_array();



        $memo_counts = array();
        foreach ($result as $row) {
            $memo_counts[$row['member_idx']] = $row['memo_count'];
        }

        return $memo_counts;
    }



	/**
	 * 파일 위치: application/models/Memo_model.php
	 * 역할: 메모 수정 기능 추가
	 */
	public function update_memo($idx, $data)
	{
		$this->db->where('idx', $idx);
		$this->db->update('wb_memo', $data);
		return $this->db->affected_rows() > 0;
	}

	/**
	 * 역할: 특정 메모 정보 가져오기
	 */
	public function get_memo_by_idx($idx)
	{
		$this->db->select('*');
		$this->db->from('wb_memo');
		$this->db->where('idx', $idx);

		$query = $this->db->get();
		return $query->row_array();
	}



	/**
	 * 파일 위치: application/models/Memo_model.php
	 * 역할: att_idx를 기준으로 한 메모 관리 기능 추가
	 */

	/**
	 * att_idx를 기준으로 메모 조회
	 */
	public function get_memo_by_att_idx($att_idx)
	{
		$this->db->select('*');
		$this->db->from('wb_memo');
		$this->db->where('att_idx', $att_idx);
		$this->db->limit(1);

		$query = $this->db->get();
		return $query->row_array();
	}

	/**
	 * 특정 주차의 회원들 메모 일괄 조회 (att_idx 기준)
	 */
	public function get_attendance_memo_by_week($member_indices, $sunday_date)
	{
		if (empty($member_indices)) {
			return array();
		}

		// 해당 주의 날짜 범위 계산
		$start_date = $sunday_date;
		$end_date = date('Y-m-d', strtotime($sunday_date . ' +6 days'));

		$this->db->select('m.idx, m.memo_content, m.member_idx, m.att_idx, ma.att_date');
		$this->db->from('wb_memo m');
		$this->db->join('wb_member_att ma', 'm.att_idx = ma.att_idx', 'inner');
		$this->db->where_in('m.member_idx', $member_indices);
		$this->db->where('ma.att_date >=', $start_date);
		$this->db->where('ma.att_date <=', $end_date);
		$this->db->where('m.att_idx IS NOT NULL');

		$query = $this->db->get();
		$results = $query->result_array();

		// 회원별로 그룹핑하여 반환
		$memo_by_member = array();
		foreach ($results as $row) {
			$member_idx = $row['member_idx'];
			if (!isset($memo_by_member[$member_idx])) {
				$memo_by_member[$member_idx] = array(
					'memo_content' => $row['memo_content'],
					'att_idx' => $row['att_idx'],
					'att_date' => $row['att_date']
				);
			}
		}

		return $memo_by_member;
	}

	/**
	 * att_idx 기준으로 메모 삭제
	 */
	public function delete_memo_by_att_idx($att_idx)
	{
		$this->db->where('att_idx', $att_idx);
		$this->db->delete('wb_memo');
		return $this->db->affected_rows() > 0;
	}

	/**
	 * 회원의 출석 관련 메모 목록 조회 (페이징)
	 */
	public function get_attendance_memo_list($member_idx, $limit, $offset)
	{
		$this->db->select('m.*, u.user_name, ma.att_date');
		$this->db->from('wb_memo m');
		$this->db->join('wb_user u', 'm.user_id = u.user_id', 'left');
		$this->db->join('wb_member_att ma', 'm.att_idx = ma.att_idx', 'left');
		$this->db->where('m.member_idx', $member_idx);
		$this->db->where('m.att_idx IS NOT NULL');
		$this->db->order_by('m.regi_date', 'DESC');
		$this->db->limit($limit, $offset);

		$query = $this->db->get();
		return $query->result_array();
	}
	

	public function save_attendance_memo($data)
	{
		// att_idx가 필수
		if (!isset($data['att_idx']) || !$data['att_idx']) {
			return false;
		}

		$this->db->insert('wb_memo', $data);
		return $this->db->affected_rows() > 0;
	}

	/**
	 * att_idx 기준으로 메모 수정
	 */
	public function update_attendance_memo_by_att_idx($att_idx, $data)
	{
		$this->db->where('att_idx', $att_idx);
		$this->db->update('wb_memo', $data);
		return $this->db->affected_rows() > 0;
	}


}
