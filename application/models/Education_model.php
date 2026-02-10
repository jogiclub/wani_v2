<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Education_model extends CI_Model
{
	public function __construct()
	{
		parent::__construct();
		$this->load->database();
	}

	/**
	 * 공개 양육 목록 페이지 데이터 조회
	 */
	public function get_public_education_data($edu_idx)
	{
		if (empty($edu_idx)) {
			return null;
		}

		$this->db->from('wb_edu');
		$this->db->where('edu_idx', $edu_idx);
		$this->db->where('del_yn', 'N');
		$query = $this->db->get();
		$education = $query->row();

		if ($education && !empty($education->poster_img)) {
			// `poster_img`에 전체 URL이 포함되어 있지 않은 경우, `site_url`을 사용하여 전체 URL을 만들어줍니다.
			if (!filter_var($education->poster_img, FILTER_VALIDATE_URL)) {
				$education->poster_img = site_url($education->poster_img);
			}
		}

		return $education;
	}

	/**
	 * 양육 목록 조회
	 */
	public function get_education_list($org_id, $params = array())
	{
		$this->db->select('e.*, c.category_name, (SELECT COUNT(*) FROM wb_edu_applicant WHERE edu_idx = e.edu_idx AND del_yn = "N") as applicant_count');
		$this->db->from('wb_edu e');
		$this->db->join('wb_edu_category c', 'e.category_code = c.category_code', 'left');
		$this->db->where('e.org_id', $org_id);
		$this->db->where('e.del_yn', 'N');

		// 검색 조건 추가
		if (!empty($params['search_date'])) {
			$this->db->where('e.edu_start_date <=', $params['search_date']);
			$this->db->where('e.edu_end_date >=', $params['search_date']);
		}
		if (!empty($params['search_days'])) {
			$day_conditions = array();
			foreach ($params['search_days'] as $day) {
				$day_conditions[] = "JSON_CONTAINS(e.edu_days, '\"" . $day . "\"')";
			}
			if (!empty($day_conditions)) {
				$this->db->where('(' . implode(' OR ', $day_conditions) . ')');
			}
		}
		if (!empty($params['search_times'])) {
			$time_conditions = array();
			foreach ($params['search_times'] as $time) {
				$time_conditions[] = "JSON_CONTAINS(e.edu_times, '\"" . $time . "\"')";
			}
			if (!empty($time_conditions)) {
				$this->db->where('(' . implode(' OR ', $time_conditions) . ')');
			}
		}
		if (!empty($params['search_ages'])) {
			$this->db->where_in('e.target_age', $params['search_ages']);
		}
		if (!empty($params['search_genders'])) {
			$this->db->where_in('e.target_gender', $params['search_genders']);
		}
		if (!empty($params['search_keyword'])) {
			$this->db->group_start();
			$this->db->like('e.category_code', $params['search_keyword']);
			$this->db->or_like('e.edu_place', $params['search_keyword']);
			$this->db->or_like('e.edu_tutor', $params['search_keyword']);
			$this->db->or_like('e.edu_name', $params['search_keyword']);
			$this->db->group_end();
		}

		$this->db->order_by('e.edu_start_date', 'DESC');
		$this->db->order_by('e.edu_idx', 'DESC');
		$query = $this->db->get();

		return $this->process_edu_list($query->result_array());
	}

	/**
	 * 카테고리별 양육 목록 조회
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
	 * 역할: 양육 목록 데이터 가공 - implode 오류 수정
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

			// 양육 기간 문자열 생성
			$edu['edu_period_str'] = $edu['edu_start_date'] . ' ~ ' . $edu['edu_end_date'];

			// 썸네일 이미지 경로
			if (!empty($edu['thumbnail_img'])) {
				$edu['thumbnail_img'] = '/uploads/edu_img/' . $edu['edu_idx'] . '/' . $edu['thumbnail_img'];
			} else {
				$edu['thumbnail_img'] = '/assets/img/default_edu_thumbnail.png'; // 기본 이미지
			}
            
            // 포스터 이미지 경로
            if (!empty($edu['poster_img'])) {
                if (!filter_var($edu['poster_img'], FILTER_VALIDATE_URL)) {
                    $edu['poster_img'] = site_url($edu['poster_img']);
                }
            }
		}
		return $edu_list;
	}


	/**
	 * 양육 상세 정보 조회
	 */
	public function get_education_detail($edu_idx)
	{
		$this->db->select('e.*, c.category_name');
		$this->db->from('wb_edu e');
		$this->db->join('wb_edu_category c', 'e.category_code = c.category_code', 'left');
		$this->db->where('e.edu_idx', $edu_idx);
		$this->db->where('e.del_yn', 'N');
		$query = $this->db->get();
		$edu = $query->row_array();

		if ($edu) {
			// JSON 디코딩
			$edu['edu_days'] = json_decode($edu['edu_days'], true);
			$edu['edu_times'] = json_decode($edu['edu_times'], true);

			// 파일 목록 조회
			$this->db->where('edu_idx', $edu_idx);
			$this->db->where('del_yn', 'N');
			$edu['files'] = $this->db->get('wb_edu_file')->result_array();
		}

		return $edu;
	}

	/**
	 * 양육 신청자 목록 조회
	 */
	public function get_applicant_list($edu_idx)
	{
		$this->db->select('a.*, m.user_name, m.birth, m.gender, m.phone, m.email, d.dept_name');
		$this->db->from('wb_edu_applicant a');
		$this->db->join('wb_member m', 'a.user_id = m.user_id', 'left');
		$this->db->join('wb_dept d', 'm.dept_code = d.dept_code', 'left');
		$this->db->where('a.edu_idx', $edu_idx);
		$this->db->where('a.del_yn', 'N');
		$this->db->order_by('a.reg_date', 'DESC');
		$query = $this->db->get();
		return $query->result_array();
	}

	/**
	 * 양육 정보 저장 (등록/수정)
	 */
	public function save_education($data, $edu_idx = null)
	{
		// JSON 인코딩
		if (isset($data['edu_days'])) {
			$data['edu_days'] = json_encode($data['edu_days'], JSON_UNESCAPED_UNICODE);
		}
		if (isset($data['edu_times'])) {
			$data['edu_times'] = json_encode($data['edu_times'], JSON_UNESCAPED_UNICODE);
		}

		if ($edu_idx) {
			// 수정
			$this->db->where('edu_idx', $edu_idx);
			$this->db->update('wb_edu', $data);
			return $edu_idx;
		} else {
			// 등록
			$this->db->insert('wb_edu', $data);
			return $this->db->insert_id();
		}
	}

	/**
	 * 양육 삭제 (del_yn = 'Y' 업데이트)
	 */
	public function delete_education($edu_idx)
	{
		$this->db->where('edu_idx', $edu_idx);
		$this->db->update('wb_edu', array('del_yn' => 'Y'));
	}

	/**
	 * 파일 정보 저장
	 */
	public function save_file($data)
	{
		$this->db->insert('wb_edu_file', $data);
	}

	/**
	 * 파일 정보 삭제
	 */
	public function delete_file($file_idx)
	{
		$this->db->where('file_idx', $file_idx);
		$this->db->update('wb_edu_file', array('del_yn' => 'Y'));
	}


	/**
	 * 양육 카테고리 목록 조회
	 */
	public function get_category_list($org_id)
	{
		$this->db->where('org_id', $org_id);
		$this->db->where('del_yn', 'N');
		$this->db->order_by('sort_order', 'ASC');
		$query = $this->db->get('wb_edu_category');
		return $query->result_array();
	}

	/**
	 * 양육 신청
	 */
	public function apply_education($data)
	{
		// 중복 신청 확인
		$this->db->where('edu_idx', $data['edu_idx']);
		$this->db->where('user_id', $data['user_id']);
		$this->db->where('del_yn', 'N');
		$count = $this->db->count_all_results('wb_edu_applicant');

		if ($count > 0) {
			return array('status' => 'error', 'message' => '이미 신청한 양육입니다.');
		}

		// 신청자 수 확인
		$edu = $this->get_education_detail($data['edu_idx']);
		$this->db->where('edu_idx', $data['edu_idx']);
		$this->db->where('del_yn', 'N');
		$applicant_count = $this->db->count_all_results('wb_edu_applicant');

		if ($edu['edu_personnel'] > 0 && $applicant_count >= $edu['edu_personnel']) {
			return array('status' => 'error', 'message' => '모집이 마감되었습니다.');
		}

		// 신청 처리
		$this->db->insert('wb_edu_applicant', $data);
		return array('status' => 'success', 'message' => '신청이 완료되었습니다.');
	}

	/**
	 * 양육 신청 취소
	 */
	public function cancel_application($edu_idx, $user_id)
	{
		$this->db->where('edu_idx', $edu_idx);
		$this->db->where('user_id', $user_id);
		$this->db->update('wb_edu_applicant', array('del_yn' => 'Y'));
		return array('status' => 'success', 'message' => '신청이 취소되었습니다.');
	}

	/**
	 * 특정 사용자의 양육 신청 정보 조회
	 */
	public function get_user_application($edu_idx, $user_id)
	{
		$this->db->where('edu_idx', $edu_idx);
		$this->db->where('user_id', $user_id);
		$this->db->where('del_yn', 'N');
		$query = $this->db->get('wb_edu_applicant');
		return $query->row_array();
	}
}
