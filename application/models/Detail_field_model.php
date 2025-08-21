<?php
/**
 * 파일 위치: E:\SynologyDrive\Example\wani\application\models\Detail_field_model.php
 * 역할: 상세필드 설정 관련 데이터베이스 작업을 처리하는 모델
 */
class Detail_field_model extends CI_Model {

	public function __construct() {
		parent::__construct();
		$this->load->database();
	}

	/**
	 * 조직의 상세필드 목록 가져오기
	 */
	public function get_detail_fields_by_org($org_id) {
		$this->db->select('field_idx, field_name, field_type, display_order, is_active, field_settings, regi_date, modi_date');
		$this->db->from('wb_detail_field_settings');
		$this->db->where('org_id', $org_id);
		$this->db->order_by('display_order', 'ASC');
		$query = $this->db->get();
		return $query->result_array();
	}

	/**
	 * 상세필드 상세 정보 가져오기
	 */
	public function get_detail_field_by_id($field_idx) {
		$this->db->select('field_idx, field_name, org_id, display_order, field_type, field_settings, is_active, regi_date, modi_date');
		$this->db->from('wb_detail_field_settings');
		$this->db->where('field_idx', $field_idx);
		$query = $this->db->get();
		return $query->row_array();
	}

	/**
	 * 새로운 상세필드 추가
	 */
	public function insert_detail_field($data) {
		$data['regi_date'] = date('Y-m-d H:i:s');
		$data['modi_date'] = date('Y-m-d H:i:s');
		return $this->db->insert('wb_detail_field_settings', $data);
	}

	/**
	 * 상세필드 정보 업데이트
	 */
	public function update_detail_field($field_idx, $data) {
		$data['modi_date'] = date('Y-m-d H:i:s');
		$this->db->where('field_idx', $field_idx);
		return $this->db->update('wb_detail_field_settings', $data);
	}

	/**
	 * 상세필드 삭제
	 */
	public function delete_detail_field($field_idx) {
		$this->db->where('field_idx', $field_idx);
		return $this->db->delete('wb_detail_field_settings');
	}

	/**
	 * 표시 순서 업데이트
	 */
	public function update_display_orders($orders) {
		$this->db->trans_start();

		foreach ($orders as $field_idx => $order) {
			$this->db->where('field_idx', $field_idx);
			$this->db->update('wb_detail_field_settings', array(
				'display_order' => $order,
				'modi_date' => date('Y-m-d H:i:s')
			));
		}

		$this->db->trans_complete();
		return $this->db->trans_status();
	}

	/**
	 * 다음 표시 순서 가져오기
	 */
	public function get_next_display_order($org_id) {
		$this->db->select_max('display_order');
		$this->db->from('wb_detail_field_settings');
		$this->db->where('org_id', $org_id);
		$query = $this->db->get();
		$result = $query->row_array();
		return ($result['display_order'] ?? 0) + 1;
	}

	/**
	 * 필드 활성화/비활성화 토글
	 */
	public function toggle_field_active($field_idx) {
		$field = $this->get_detail_field_by_id($field_idx);
		if (!$field) {
			return false;
		}

		$new_status = ($field['is_active'] === 'Y') ? 'N' : 'Y';
		return $this->update_detail_field($field_idx, array('is_active' => $new_status));
	}
}
