<?php
/**
 * 파일 위치: application/models/Income_expense_model.php
 * 역할: 수입지출 데이터 처리 모델
 */
defined('BASEPATH') or exit('No direct script access allowed');

class Income_expense_model extends CI_Model
{
	public function __construct()
	{
		parent::__construct();
		$this->load->database();
	}

	/**
	 * 수입지출 목록 조회
	 */
	public function get_list($params)
	{
		$this->db->select('ie.*, 
            DATE_FORMAT(ie.transaction_date, "%Y-%m-%d") as transaction_date_fmt,
            DATE_FORMAT(ie.regi_date, "%Y-%m-%d %H:%i") as regi_date_fmt,
            DATE_FORMAT(ie.modi_date, "%Y-%m-%d %H:%i") as modi_date_fmt');
		$this->db->from('wb_income_expense ie');
		$this->db->where('ie.del_yn', 'N');

		if (!empty($params['book_idx'])) {
			$this->db->where('ie.book_idx', $params['book_idx']);
		}

		if (!empty($params['org_id'])) {
			$this->db->where('ie.org_id', $params['org_id']);
		}

		if (!empty($params['income_type'])) {
			$this->db->where('ie.income_type', $params['income_type']);
		}

		if (!empty($params['bank'])) {
			$banks = is_array($params['bank']) ? $params['bank'] : array($params['bank']);
			$this->db->group_start();
			foreach ($banks as $bank) {
				$this->db->or_like('ie.bank', $bank);
			}
			$this->db->group_end();
		}

		if (!empty($params['account_codes'])) {
			$codes = is_array($params['account_codes']) ? $params['account_codes'] : array($params['account_codes']);
			$this->db->where_in('ie.account_code', $codes);
		}

		if (!empty($params['tags'])) {
			$tags = is_array($params['tags']) ? $params['tags'] : array($params['tags']);
			$this->db->group_start();
			foreach ($tags as $tag) {
				$this->db->or_like('ie.tags', $tag);
			}
			$this->db->group_end();
		}

		if (!empty($params['start_date'])) {
			$this->db->where('ie.transaction_date >=', $params['start_date']);
		}

		if (!empty($params['end_date'])) {
			$this->db->where('ie.transaction_date <=', $params['end_date']);
		}

		if (!empty($params['keyword'])) {
			$this->db->group_start();
			$this->db->like('ie.account_name', $params['keyword']);
			$this->db->or_like('ie.memo', $params['keyword']);
			$this->db->group_end();
		}

		$this->db->order_by('ie.transaction_date', 'DESC');
		$this->db->order_by('ie.idx', 'DESC');

		$query = $this->db->get();
		return $query->result_array();
	}

	/**
	 * 수입지출 상세 조회
	 */
	public function get_detail($idx)
	{
		$this->db->where('idx', $idx);
		$this->db->where('del_yn', 'N');
		$query = $this->db->get('wb_income_expense');
		return $query->row_array();
	}

	/**
	 * 수입지출 등록
	 */
	public function add($data)
	{
		$this->db->insert('wb_income_expense', $data);
		return $this->db->insert_id();
	}

	/**
	 * 수입지출 수정
	 */
	public function update($idx, $data)
	{
		$this->db->where('idx', $idx);
		return $this->db->update('wb_income_expense', $data);
	}

	/**
	 * 수입지출 삭제 (소프트 삭제)
	 */
	public function delete($idx_list)
	{
		$this->db->where_in('idx', $idx_list);
		return $this->db->update('wb_income_expense', array('del_yn' => 'Y'));
	}

	/**
	 * 사용된 태그 목록 조회
	 */
	public function get_used_tags($book_idx, $org_id)
	{
		$this->db->select('tags');
		$this->db->from('wb_income_expense');
		$this->db->where('org_id', $org_id);
		$this->db->where('del_yn', 'N');
		$this->db->where('tags IS NOT NULL');
		$this->db->where('tags !=', '');

		if (!empty($book_idx)) {
			$this->db->where('book_idx', $book_idx);
		}

		$query = $this->db->get();
		$results = $query->result_array();

		$all_tags = array();
		foreach ($results as $row) {
			$tags = json_decode($row['tags'], true);
			if (is_array($tags)) {
				$all_tags = array_merge($all_tags, $tags);
			}
		}

		return array_values(array_unique($all_tags));
	}
}
