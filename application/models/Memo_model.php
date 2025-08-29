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

}
