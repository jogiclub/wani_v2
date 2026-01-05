<?php
/**
 * 파일 위치: application/models/Homepage_setting_model.php
 * 역할: 홈페이지 설정 데이터 처리 모델
 */
class Homepage_setting_model extends CI_Model
{
	public function __construct()
	{
		parent::__construct();
		$this->load->database();
	}

	/**
	 * 조직의 홈페이지 설정 조회
	 */
	public function get_homepage_setting($org_id)
	{
		$this->db->select('homepage_setting');
		$this->db->from('wb_org');
		$this->db->where('org_id', $org_id);
		$query = $this->db->get();
		$result = $query->row_array();

		if ($result && !empty($result['homepage_setting'])) {
			$setting = json_decode($result['homepage_setting'], true);
			return is_array($setting) ? $setting : array();
		}

		return array(
			'homepage_name' => '',
			'homepage_domain' => '',
			'logo1' => '',
			'logo2' => '',
			'theme' => ''
		);
	}

	/**
	 * 홈페이지 설정 저장
	 */
	public function update_homepage_setting($org_id, $setting_data)
	{
		$json_data = json_encode($setting_data, JSON_UNESCAPED_UNICODE);

		$data = array(
			'homepage_setting' => $json_data,
			'modi_date' => date('Y-m-d H:i:s')
		);

		$this->db->where('org_id', $org_id);
		return $this->db->update('wb_org', $data);
	}


	/**
	 * 관련 링크 목록 조회
	 */
	public function get_related_links($org_id)
	{
		$setting = $this->get_homepage_setting($org_id);

		if (!empty($setting['related_links']) && is_array($setting['related_links'])) {
			// display_order로 정렬
			usort($setting['related_links'], function($a, $b) {
				return ($a['display_order'] ?? 0) - ($b['display_order'] ?? 0);
			});
			return $setting['related_links'];
		}

		return [];
	}

	/**
	 * 관련 링크 추가
	 */
	public function add_related_link($org_id, $link_data)
	{
		$setting = $this->get_homepage_setting($org_id);

		if (!isset($setting['related_links']) || !is_array($setting['related_links'])) {
			$setting['related_links'] = [];
		}

		// 새 idx 생성 (기존 최대값 + 1)
		$max_idx = 0;
		$max_order = 0;
		foreach ($setting['related_links'] as $link) {
			if (isset($link['idx']) && $link['idx'] > $max_idx) {
				$max_idx = $link['idx'];
			}
			if (isset($link['display_order']) && $link['display_order'] > $max_order) {
				$max_order = $link['display_order'];
			}
		}

		$link_data['idx'] = $max_idx + 1;
		$link_data['display_order'] = $max_order + 1;
		$link_data['reg_date'] = date('Y-m-d H:i:s');

		$setting['related_links'][] = $link_data;

		$this->update_homepage_setting($org_id, $setting);

		return $link_data['idx'];
	}

	/**
	 * 관련 링크 단일 조회
	 */
	public function get_related_link($org_id, $idx)
	{
		$links = $this->get_related_links($org_id);

		foreach ($links as $link) {
			if (isset($link['idx']) && $link['idx'] == $idx) {
				return $link;
			}
		}

		return null;
	}

	/**
	 * 관련 링크 수정
	 */
	public function update_related_link($org_id, $idx, $update_data)
	{
		$setting = $this->get_homepage_setting($org_id);

		if (!isset($setting['related_links']) || !is_array($setting['related_links'])) {
			return false;
		}

		$found = false;
		foreach ($setting['related_links'] as &$link) {
			if (isset($link['idx']) && $link['idx'] == $idx) {
				foreach ($update_data as $key => $value) {
					$link[$key] = $value;
				}
				$link['modi_date'] = date('Y-m-d H:i:s');
				$found = true;
				break;
			}
		}

		if ($found) {
			return $this->update_homepage_setting($org_id, $setting);
		}

		return false;
	}

	/**
	 * 관련 링크 삭제
	 */
	public function delete_related_link($org_id, $idx)
	{
		$setting = $this->get_homepage_setting($org_id);

		if (!isset($setting['related_links']) || !is_array($setting['related_links'])) {
			return false;
		}

		$new_links = [];
		$deleted_link = null;

		foreach ($setting['related_links'] as $link) {
			if (isset($link['idx']) && $link['idx'] == $idx) {
				$deleted_link = $link;
			} else {
				$new_links[] = $link;
			}
		}

		if ($deleted_link) {
			$setting['related_links'] = $new_links;
			$this->update_homepage_setting($org_id, $setting);
			return $deleted_link;
		}

		return false;
	}

}




