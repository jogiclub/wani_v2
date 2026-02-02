<?php
/**
 * 파일 위치: application/models/Education_model.php
 * 역할: 교육관리 데이터베이스 작업 처리
 */

class Education_model extends CI_Model
{
	public function __construct()
	{
		parent::__construct();
		$this->load->database();
	}

	/**
	 * 카테고리 트리 조회
	 */
	public function get_category_tree($org_id)
	{
		$this->db->select('category_json');
		$this->db->from('wb_edu_category');
		$this->db->where('org_id', $org_id);
		$this->db->where('category_type', 'edu');
		$query = $this->db->get();

		if ($query->num_rows() > 0) {
			$result = $query->row_array();
			$category_json = json_decode($result['category_json'], true);

			if (isset($category_json['categories']) && is_array($category_json['categories'])) {
				return $category_json['categories'];
			}
		}

		return array();
	}

	/**
	 * 카테고리별 교육 개수 조회
	 */
	public function get_category_edu_counts($org_id)
	{
		$this->db->select('category_code, COUNT(*) as count');
		$this->db->from('wb_edu');
		$this->db->where('org_id', $org_id);
		$this->db->where('del_yn', 'N');
		$this->db->group_by('category_code');
		$query = $this->db->get();

		$counts = array();
		foreach ($query->result_array() as $row) {
			if (!empty($row['category_code'])) {
				$counts[$row['category_code']] = (int)$row['count'];
			}
		}

		return $counts;
	}

	/**
	 * 전체 교육 개수 조회
	 */
	public function get_total_edu_count($org_id)
	{
		$this->db->from('wb_edu');
		$this->db->where('org_id', $org_id);
		$this->db->where('del_yn', 'N');
		return $this->db->count_all_results();
	}

	/**
	 * 파일 위치: application/models/Education_model.php
	 * 역할: 교육 목록 조회 - 신청자 수 포함
	 */
	public function get_edu_list_by_org($org_id)
	{
		$this->db->select('e.*, (SELECT COUNT(*) FROM wb_edu_applicant WHERE edu_idx = e.edu_idx AND del_yn = "N") as applicant_count');
		$this->db->from('wb_edu e');
		$this->db->where('e.org_id', $org_id);
		$this->db->where('e.del_yn', 'N');
		$this->db->order_by('e.edu_start_date', 'DESC');
		$this->db->order_by('e.edu_idx', 'DESC');
		$query = $this->db->get();

		return $this->process_edu_list($query->result_array());
	}

	/**
	 * 카테고리별 교육 목록 조회
	 */
	public function get_edu_list_by_category($org_id, $category_code)
	{
		$this->db->select('e.*, (SELECT COUNT(*) FROM wb_edu_applicant WHERE edu_idx = e.edu_idx AND del_yn = "N") as applicant_count');
		$this->db->from('wb_edu e');
		$this->db->where('e.org_id', $org_id);
		$this->db->where('e.category_code', $category_code);
		$this->db->where('e.del_yn', 'N');
		$this->db->order_by('e.edu_start_date', 'DESC');
		$this->db->order_by('e.edu_idx', 'DESC');
		$query = $this->db->get();

		return $this->process_edu_list($query->result_array());
	}

	/**
	 * 파일 위치: application/models/Education_model.php
	 * 역할: 교육 목록 데이터 가공 - implode 오류 수정
	 */

	private function process_edu_list($edu_list)
	{
		foreach ($edu_list as &$edu) {
			// JSON 필드 파싱
			if (!empty($edu['edu_days'])) {
				$parsed_days = json_decode($edu['edu_days'], true);
				$edu['edu_days'] = is_array($parsed_days) ? $parsed_days : array();
			} else {
				$edu['edu_days'] = array();
			}

			if (!empty($edu['edu_times'])) {
				$parsed_times = json_decode($edu['edu_times'], true);
				$edu['edu_times'] = is_array($parsed_times) ? $parsed_times : array();
			} else {
				$edu['edu_times'] = array();
			}

			// 요일 문자열 생성
			$edu['edu_days_str'] = !empty($edu['edu_days']) && is_array($edu['edu_days'])
				? implode(', ', $edu['edu_days'])
				: '';

			// 시간대 문자열 생성
			$edu['edu_times_str'] = !empty($edu['edu_times']) && is_array($edu['edu_times'])
				? implode(', ', $edu['edu_times'])
				: '';

			// 교육 기간 문자열 생성
			if (!empty($edu['edu_start_date']) && $edu['edu_start_date'] != '0000-00-00') {
				$start_date = $edu['edu_start_date'];
				$end_date = !empty($edu['edu_end_date']) && $edu['edu_end_date'] != '0000-00-00'
					? $edu['edu_end_date']
					: $start_date;
				$edu['edu_period_str'] = $start_date . ' ~ ' . $end_date;
			} else {
				$edu['edu_period_str'] = '';
			}

			// 인도자 연령대 문자열 변환
			$age_map = array(
				'10s' => '10대',
				'20s' => '20대',
				'30s' => '30대',
				'40s' => '40대',
				'50s' => '50대',
				'60s' => '60대'
			);
			$edu['edu_leader_age_str'] = isset($age_map[$edu['edu_leader_age']])
				? $age_map[$edu['edu_leader_age']]
				: '';

			// 인도자 성별 문자열 변환
			$gender_map = array(
				'male' => '남',
				'female' => '여'
			);
			$edu['edu_leader_gender_str'] = isset($gender_map[$edu['edu_leader_gender']])
				? $gender_map[$edu['edu_leader_gender']]
				: '';
		}

		return $edu_list;
	}

	/**
	 * 교육 상세 조회
	 */
	public function get_edu_by_idx($edu_idx)
	{
		$this->db->select('*');
		$this->db->from('wb_edu');
		$this->db->where('edu_idx', $edu_idx);
		$this->db->where('del_yn', 'N');
		$query = $this->db->get();

		if ($query->num_rows() > 0) {
			$edu = $query->row_array();

			// JSON 필드 파싱
			$edu['edu_days'] = !empty($edu['edu_days']) ? json_decode($edu['edu_days'], true) : array();
			$edu['edu_times'] = !empty($edu['edu_times']) ? json_decode($edu['edu_times'], true) : array();

			return $edu;
		}

		return null;
	}

	/**
	 * 교육 등록
	 */
	public function insert_edu($edu_data)
	{
		$insert_data = array_merge($edu_data, array(
			'regi_date' => date('Y-m-d H:i:s')
		));

		if ($this->db->insert('wb_edu', $insert_data)) {
			return $this->db->insert_id();
		}

		return false;
	}

	/**
	 * 교육 수정
	 */
	public function update_edu($edu_idx, $edu_data)
	{
		$update_data = array_merge($edu_data, array(
			'modi_date' => date('Y-m-d H:i:s')
		));

		$this->db->where('edu_idx', $edu_idx);
		return $this->db->update('wb_edu', $update_data);
	}

	/**
	 * 교육 삭제 (소프트 삭제)
	 */
	public function delete_edu($edu_idx)
	{
		$delete_data = array(
			'del_yn' => 'Y',
			'del_date' => date('Y-m-d H:i:s'),
			'modi_date' => date('Y-m-d H:i:s')
		);

		$this->db->where('edu_idx', $edu_idx);
		return $this->db->update('wb_edu', $delete_data);
	}

	/**
	 * 카테고리 저장
	 */
	public function save_category($category_data)
	{
		// 기존 카테고리 확인
		$this->db->select('category_id');
		$this->db->from('wb_edu_category');
		$this->db->where('org_id', $category_data['org_id']);
		$this->db->where('category_type', 'edu');
		$query = $this->db->get();

		if ($query->num_rows() > 0) {
			// 업데이트
			$row = $query->row_array();
			$update_data = array(
				'category_json' => $category_data['category_json'],
				'modi_date' => date('Y-m-d H:i:s'),
				'user_id' => $category_data['user_id']
			);

			$this->db->where('category_id', $row['category_id']);
			return $this->db->update('wb_edu_category', $update_data);
		} else {
			// 신규 등록
			$insert_data = array_merge($category_data, array(
				'regi_date' => date('Y-m-d H:i:s')
			));

			return $this->db->insert('wb_edu_category', $insert_data);
		}
	}


	/**
	 * 파일 위치: application/models/Education_model.php - create_default_categories() 함수
	 * 역할: 기본 카테고리 생성 (초급과정, 중급과정, 고급과정)
	 */
	public function create_default_categories($org_id, $user_id)
	{
		// 기본 카테고리 구조
		$default_categories = array(
			'categories' => array(
				array(
					'code' => 'EDU_BASIC',
					'name' => '초급과정',
					'order' => 1,
					'children' => array()
				),
				array(
					'code' => 'EDU_INTERMEDIATE',
					'name' => '중급과정',
					'order' => 2,
					'children' => array()
				),
				array(
					'code' => 'EDU_ADVANCED',
					'name' => '고급과정',
					'order' => 3,
					'children' => array()
				)
			)
		);

		$category_json = json_encode($default_categories, JSON_UNESCAPED_UNICODE);

		$insert_data = array(
			'org_id' => $org_id,
			'category_type' => 'edu',
			'category_json' => $category_json,
			'user_id' => $user_id,
			'regi_date' => date('Y-m-d H:i:s')
		);

		$result = $this->db->insert('wb_edu_category', $insert_data);

		if ($result) {
			log_message('info', "교육 기본 카테고리 생성 완료 - org_id: {$org_id}");
		} else {
			log_message('error', "교육 기본 카테고리 생성 실패 - org_id: {$org_id}");
		}

		return $result;
	}


	/**
	 * 파일 위치: application/models/Education_model.php
	 * 역할: 신청자 관리 함수
	 */

	/**
	 * 신청자 목록 조회
	 */
	public function get_applicant_list($edu_idx)
	{
		$this->db->select('*');
		$this->db->from('wb_edu_applicant');
		$this->db->where('edu_idx', $edu_idx);
		$this->db->where('del_yn', 'N');
		$this->db->order_by('regi_date', 'DESC');
		$query = $this->db->get();

		return $query->result_array();
	}

	/**
	 * 신청자 추가
	 */
	public function add_applicant($data)
	{
		$insert_data = array_merge($data, array(
			'regi_date' => date('Y-m-d H:i:s')
		));

		return $this->db->insert('wb_edu_applicant', $insert_data);
	}

	/**
	 * 신청자 수정
	 */
	public function update_applicant($applicant_idx, $data)
	{
		$update_data = array_merge($data, array(
			'modi_date' => date('Y-m-d H:i:s')
		));

		$this->db->where('applicant_idx', $applicant_idx);
		return $this->db->update('wb_edu_applicant', $update_data);
	}

	/**
	 * 신청자 삭제 (소프트 삭제)
	 */
	public function delete_applicant($applicant_idx)
	{
		$this->db->where('applicant_idx', $applicant_idx);
		return $this->db->update('wb_edu_applicant', array('del_yn' => 'Y'));
	}

	/**
	 * 신청자 상태 일괄변경
	 */
	public function bulk_update_applicant_status($edu_idx, $status)
	{
		$this->db->where('edu_idx', $edu_idx);
		$this->db->where('del_yn', 'N');
		return $this->db->update('wb_edu_applicant', array(
			'status' => $status,
			'modi_date' => date('Y-m-d H:i:s')
		));
	}

	/**
	 * 교육별 신청자 수 조회
	 */
	public function get_applicant_count($edu_idx)
	{
		$this->db->from('wb_edu_applicant');
		$this->db->where('edu_idx', $edu_idx);
		$this->db->where('del_yn', 'N');
		return $this->db->count_all_results();
	}

}
