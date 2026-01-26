<?php
/**
 * 파일 위치: application/models/Revision_model.php
 * 역할: 회원 수정내역 관리 모델
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class Revision_model extends CI_Model {

	public function __construct() {
		parent::__construct();
	}

	/**
	 * 수정내역 저장
	 */
	public function add_revision($data) {
		return $this->db->insert('wb_revision', $data);
	}

	/**
	 * 회원의 수정내역 목록 조회 (페이징)
	 */
	public function get_revisions($member_idx, $org_id, $limit = 10, $offset = 0) {
		$this->db->select('r.*, u.user_name');
		$this->db->from('wb_revision r');
		$this->db->join('wb_user u', 'r.user_id = u.user_id', 'left');
		$this->db->where('r.member_idx', $member_idx);
		$this->db->where('r.org_id', $org_id);
		$this->db->order_by('r.regi_date', 'DESC');
		$this->db->limit($limit, $offset);

		$query = $this->db->get();
		return $query->result_array();
	}

	/**
	 * 회원의 수정내역 총 개수
	 */
	public function count_revisions($member_idx, $org_id) {
		$this->db->where('member_idx', $member_idx);
		$this->db->where('org_id', $org_id);
		return $this->db->count_all_results('wb_revision');
	}

	/**
	 * 파일 위치: application/models/Revision_model.php
	 * 역할: 변경사항 비교 시 member_detail 필드 제외
	 */
	public function compare_changes($old_data, $new_data, $field_labels) {
		$changes = array();

		foreach ($new_data as $field => $new_value) {
			// 비교에서 제외할 필드 (member_detail 추가)
			if (in_array($field, array('modi_date', 'member_idx', 'org_id', 'member_detail'))) {
				continue;
			}

			$old_value = isset($old_data[$field]) ? $old_data[$field] : '';

			$old_normalized = $this->normalize_value($old_value);
			$new_normalized = $this->normalize_value($new_value);

			if ($old_normalized !== $new_normalized) {
				$label = isset($field_labels[$field]) ? $field_labels[$field] : $field;
				$changes[] = array(
					'field' => $field,
					'label' => $label,
					'old_value' => $old_normalized,
					'new_value' => $new_normalized
				);
			}
		}

		return !empty($changes) ? $changes : null;
	}

	/**
	 * 값 정규화
	 */
	private function normalize_value($value) {
		if ($value === null || $value === '') {
			return '';
		}
		return trim((string)$value);
	}

	/**
	 * 상세필드 변경사항 비교
	 */
	public function compare_detail_changes($old_detail, $new_detail, $org_id) {
		$changes = array();

		$field_labels = $this->get_detail_field_labels($org_id);

		$old_array = !empty($old_detail) ? json_decode($old_detail, true) : array();
		$new_array = !empty($new_detail) ? json_decode($new_detail, true) : array();

		if (!is_array($old_array)) $old_array = array();
		if (!is_array($new_array)) $new_array = array();

		$all_keys = array_unique(array_merge(array_keys($old_array), array_keys($new_array)));

		foreach ($all_keys as $field_idx) {
			$old_value = isset($old_array[$field_idx]) ? $old_array[$field_idx] : '';
			$new_value = isset($new_array[$field_idx]) ? $new_array[$field_idx] : '';

			$old_normalized = $this->normalize_value($old_value);
			$new_normalized = $this->normalize_value($new_value);

			if ($old_normalized !== $new_normalized) {
				$label = isset($field_labels[$field_idx]) ? $field_labels[$field_idx] : '상세필드_' . $field_idx;
				$changes[] = array(
					'field' => 'detail_' . $field_idx,
					'label' => $label,
					'old_value' => $old_normalized,
					'new_value' => $new_normalized
				);
			}
		}

		return $changes;
	}

	/**
	 * 상세필드 라벨 조회
	 */
	private function get_detail_field_labels($org_id) {
		$this->db->select('field_idx, field_name');
		$this->db->from('wb_detail_field');
		$this->db->where('org_id', $org_id);
		$this->db->where('is_active', 'Y');

		$query = $this->db->get();
		$result = $query->result_array();

		$labels = array();
		foreach ($result as $row) {
			$labels[$row['field_idx']] = $row['field_name'];
		}

		return $labels;
	}
}
