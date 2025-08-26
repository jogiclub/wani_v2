<?php
/**
 * 파일 위치: application/models/Attendance_setting_model.php
 * 역할: 출석설정 관련 데이터베이스 작업을 처리하는 모델
 */
class Attendance_setting_model extends CI_Model
{

	public function __construct()
	{
		parent::__construct();
		$this->load->database();
	}

	/**
	 * 조직의 출석타입 목록 가져오기
	 */
	public function get_attendance_types_by_org($org_id)
	{
		$this->db->select('att_type_idx, att_type_name, att_type_nickname, att_type_category_idx, att_type_category_name, att_type_point, att_type_input, att_type_color, att_type_order');
		$this->db->from('wb_att_type');
		$this->db->where('org_id', $org_id);
		$this->db->order_by('att_type_order', 'ASC');
		$this->db->order_by('att_type_idx', 'ASC');
		$query = $this->db->get();
		return $query->result_array();
	}

	/**
	 * 조직의 출석타입 카테고리 목록 가져오기
	 */
	public function get_attendance_categories_by_org($org_id)
	{
		$this->db->select('att_type_category_idx, att_type_category_name');
		$this->db->from('wb_att_type');
		$this->db->where('org_id', $org_id);
		$this->db->where('att_type_category_idx IS NOT NULL');
		$this->db->group_by('att_type_category_idx');
		$this->db->order_by('att_type_category_idx', 'ASC');
		$query = $this->db->get();
		return $query->result_array();
	}

	/**
	 * 출석타입 상세 정보 가져오기
	 */
	public function get_attendance_type_by_id($att_type_idx)
	{
		$this->db->select('att_type_idx, att_type_name, att_type_nickname, att_type_category_idx, att_type_category_name, att_type_point, att_type_input, att_type_color, org_id, att_type_order');
		$this->db->from('wb_att_type');
		$this->db->where('att_type_idx', $att_type_idx);
		$query = $this->db->get();
		return $query->row_array();
	}

	/**
	 * 새로운 출석타입 추가
	 */
	public function insert_attendance_type($data)
	{
		return $this->db->insert('wb_att_type', $data);
	}

	/**
	 * 출석타입 정보 업데이트
	 */
	public function update_attendance_type($att_type_idx, $data)
	{
		$this->db->where('att_type_idx', $att_type_idx);
		return $this->db->update('wb_att_type', $data);
	}

	/**
	 * 출석타입 삭제
	 */
	public function delete_attendance_type($att_type_idx)
	{
		$this->db->where('att_type_idx', $att_type_idx);
		return $this->db->delete('wb_att_type');
	}

	/**
	 * 최대 카테고리 인덱스 가져오기
	 */
	public function get_max_category_idx($org_id)
	{
		$this->db->select_max('att_type_category_idx');
		$this->db->where('org_id', $org_id);
		$query = $this->db->get('wb_att_type');
		$result = $query->row_array();
		return $result['att_type_category_idx'] ?: 0;
	}

	/**
	 * 다음 순서 번호 가져오기 (수정된 버전)
	 */
	public function get_next_order($org_id)
	{
		$this->db->select_max('att_type_order');
		$this->db->where('org_id', $org_id);
		$query = $this->db->get('wb_att_type');
		$result = $query->row_array();
		return ($result['att_type_order'] ?: 0) + 1;
	}

	/**
	 * 출석타입 순서 업데이트
	 */
	public function update_attendance_type_orders($orders)
	{
		$this->db->trans_start();

		foreach ($orders as $index => $att_type_idx) {
			$order = $index + 1;
			$this->db->where('att_type_idx', $att_type_idx);
			$this->db->update('wb_att_type', array('att_type_order' => $order));
		}

		$this->db->trans_complete();
		return $this->db->trans_status();
	}

	/**
	 * 해당 출석타입을 사용하는 출석 기록이 있는지 확인
	 */
	public function has_attendance_records($att_type_idx)
	{
		$this->db->where('att_type_idx', $att_type_idx);
		$this->db->from('wb_attendance');
		return $this->db->count_all_results() > 0;
	}

	/**
	 * 조직의 출석타입 개수 가져오기
	 */
	public function get_attendance_type_count($org_id)
	{
		$this->db->where('org_id', $org_id);
		$this->db->from('wb_att_type');
		return $this->db->count_all_results();
	}
}
