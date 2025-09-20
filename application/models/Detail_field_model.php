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
		$this->db->select('field_idx, field_name, field_type, field_size, display_order, is_active, field_settings, regi_date, modi_date');
		$this->db->from('wb_detail_field');
		$this->db->where('org_id', $org_id);
		$this->db->order_by('display_order', 'ASC');
		$query = $this->db->get();
		return $query->result_array();
	}

	/**
	 * 상세필드 상세 정보 가져오기
	 */
	public function get_detail_field_by_id($field_idx) {
		$this->db->select('field_idx, field_name, org_id, display_order, field_type, field_size, field_settings, is_active, regi_date, modi_date');
		$this->db->from('wb_detail_field');
		$this->db->where('field_idx', $field_idx);
		$query = $this->db->get();
		return $query->row_array();
	}

	/**
	 * 새로운 상세필드 추가
	 */
	public function insert_detail_field($data) {
		// UTF-8 인코딩 보장
		if (isset($data['field_settings']) && is_string($data['field_settings'])) {
			// 이미 JSON 문자열인 경우 그대로 사용
			if (!$this->is_json($data['field_settings'])) {
				$data['field_settings'] = json_encode($data['field_settings'], JSON_UNESCAPED_UNICODE);
			}
		}

		$data['regi_date'] = date('Y-m-d H:i:s');
		$data['modi_date'] = date('Y-m-d H:i:s');
		return $this->db->insert('wb_detail_field', $data);
	}

	/**
	 * 상세필드 정보 업데이트
	 */
	public function update_detail_field($field_idx, $data) {
		// UTF-8 인코딩 보장
		if (isset($data['field_settings']) && is_string($data['field_settings'])) {
			// 이미 JSON 문자열인 경우 그대로 사용
			if (!$this->is_json($data['field_settings'])) {
				$data['field_settings'] = json_encode($data['field_settings'], JSON_UNESCAPED_UNICODE);
			}
		}

		$data['modi_date'] = date('Y-m-d H:i:s');
		$this->db->where('field_idx', $field_idx);
		return $this->db->update('wb_detail_field', $data);
	}

	/**
	 * 문자열이 유효한 JSON인지 확인
	 */
	private function is_json($string) {
		json_decode($string);
		return (json_last_error() == JSON_ERROR_NONE);
	}

	/**
	 * 상세필드 삭제
	 */
	public function delete_detail_field($field_idx) {
		$this->db->where('field_idx', $field_idx);
		return $this->db->delete('wb_detail_field');
	}

	/**
	 * 표시 순서 업데이트
	 */
	public function update_display_orders($orders) {
		$this->db->trans_start();

		foreach ($orders as $field_idx => $order) {
			$this->db->where('field_idx', $field_idx);
			$this->db->update('wb_detail_field', array(
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
		$this->db->from('wb_detail_field');
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

	/**
	 * 사용자가 접근 가능한 조직 목록 가져오기
	 */
	public function get_user_orgs($user_id) {
		$this->db->select('wb_org.org_id, wb_org.org_name, wb_org.org_type, wb_org.org_icon, wb_org_user.level');
		$this->db->from('wb_org');
		$this->db->join('wb_org_user', 'wb_org.org_id = wb_org_user.org_id');
		$this->db->where('wb_org_user.user_id', $user_id);
		$this->db->where('wb_org.del_yn', 'N');
		$this->db->order_by('wb_org.org_name', 'ASC');
		$query = $this->db->get();
		$result = $query->result_array();

		// 회원 수 추가
		foreach ($result as &$org) {
			$org['member_count'] = $this->get_org_member_count($org['org_id']);
		}

		return $result;
	}

	/**
	 * 사용자가 접근 가능한 조직 목록 가져오기 (마스터 사용자)
	 */
	public function get_user_orgs_master($user_id) {
		$this->db->select('wb_org.org_id, wb_org.org_name, wb_org.org_type, wb_org.org_icon, 10 as level');
		$this->db->from('wb_org');
		$this->db->where('wb_org.del_yn', 'N');
		$this->db->order_by('wb_org.org_name', 'ASC');
		$query = $this->db->get();
		$result = $query->result_array();

		// 회원 수 추가
		foreach ($result as &$org) {
			$org['member_count'] = $this->get_org_member_count($org['org_id']);
		}

		return $result;
	}

	/**
	 * 조직의 회원 수 가져오기
	 */
	public function get_org_member_count($org_id) {
		$this->db->from('wb_member');
		$this->db->where('org_id', $org_id);
		$this->db->where('del_yn', 'N');
		return $this->db->count_all_results();
	}

	/**
	 * 조직 상세 정보 가져오기
	 */
	public function get_org_detail_by_id($org_id) {
		$this->db->select('org_id, org_code, org_name, org_type, org_desc, org_rep, org_manager, org_phone, org_address_postno, org_address, org_address_detail, org_tag, org_icon, leader_name, new_name, invite_code, position_name, duty_name, timeline_name, category_idx, regi_date, modi_date');
		$this->db->from('wb_org');
		$this->db->where('org_id', $org_id);
		$this->db->where('del_yn', 'N');
		$query = $this->db->get();
		return $query->row_array();
	}

	/**
	 * 사용자의 조직 내 권한 레벨 가져오기
	 */
	public function get_org_user_level($user_id, $org_id) {
		$this->db->select('level');
		$this->db->where('user_id', $user_id);
		$this->db->where('org_id', $org_id);
		$query = $this->db->get('wb_org_user');
		$result = $query->row_array();
		return $result ? $result['level'] : 0;
	}



}
