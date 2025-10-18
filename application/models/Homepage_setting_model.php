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
}
