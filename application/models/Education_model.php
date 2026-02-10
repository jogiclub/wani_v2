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

	private function process_edu_list($edu_list)
	{
		foreach ($edu_list as &$edu) {
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

			$edu['edu_days_str'] = !empty($edu['edu_days']) && is_array($edu['edu_days'])
				? implode(', ', $edu['edu_days'])
				: '';

			$edu['edu_times_str'] = !empty($edu['edu_times']) && is_array($edu['edu_times'])
				? implode(', ', $edu['edu_times'])
				: '';

			$edu['edu_period_str'] = $edu['edu_start_date'] . ' ~ ' . $edu['edu_end_date'];

			if (!empty($edu['thumbnail_img'])) {
				$edu['thumbnail_img'] = '/uploads/edu_img/' . $edu['edu_idx'] . '/' . $edu['thumbnail_img'];
			} else {
				$edu['thumbnail_img'] = '/assets/img/default_edu_thumbnail.png';
			}

			if (!empty($edu['poster_img'])) {
				if (!filter_var($edu['poster_img'], FILTER_VALIDATE_URL)) {
					$edu['poster_img'] = site_url($edu['poster_img']);
				}
			}
		}
		return $edu_list;
	}

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
			$edu['edu_days'] = json_decode($edu['edu_days'], true);
			$edu['edu_times'] = json_decode($edu['edu_times'], true);

			$this->db->where('edu_idx', $edu_idx);
			$this->db->where('del_yn', 'N');
			$edu['files'] = $this->db->get('wb_edu_file')->result_array();
		}

		return $edu;
	}

	public function save_education($data, $edu_idx = null)
	{
		if (isset($data['edu_days'])) {
			$data['edu_days'] = json_encode($data['edu_days'], JSON_UNESCAPED_UNICODE);
		}
		if (isset($data['edu_times'])) {
			$data['edu_times'] = json_encode($data['edu_times'], JSON_UNESCAPED_UNICODE);
		}

		if ($edu_idx) {
			$this->db->where('edu_idx', $edu_idx);
			$this->db->update('wb_edu', $data);
			return $edu_idx;
		} else {
			$this->db->insert('wb_edu', $data);
			return $this->db->insert_id();
		}
	}

	public function delete_education($edu_idx)
	{
		$this->db->where('edu_idx', $edu_idx);
		$this->db->update('wb_edu', array('del_yn' => 'Y'));
	}

	public function save_file($data)
	{
		$this->db->insert('wb_edu_file', $data);
	}

	public function delete_file($file_idx)
	{
		$this->db->where('file_idx', $file_idx);
		$this->db->update('wb_edu_file', array('del_yn' => 'Y'));
	}

	public function get_category_list($org_id)
	{
		$this->db->where('org_id', $org_id);
		$this->db->where('category_type', 'edu');
		$query = $this->db->get('wb_edu_category');
		$result = $query->row_array();
		if ($result && !empty($result['category_json'])) {
			return json_decode($result['category_json'], true);
		}
		return [];
	}

	public function apply_education($data)
	{
		$this->db->where('edu_idx', $data['edu_idx']);
		$this->db->where('user_id', $data['user_id']);
		$this->db->where('del_yn', 'N');
		$count = $this->db->count_all_results('wb_edu_applicant');

		if ($count > 0) {
			return array('status' => 'error', 'message' => '이미 신청한 양육입니다.');
		}

		$edu = $this->get_education_detail($data['edu_idx']);
		$this->db->where('edu_idx', $data['edu_idx']);
		$this->db->where('del_yn', 'N');
		$applicant_count = $this->db->count_all_results('wb_edu_applicant');

		if ($edu['edu_personnel'] > 0 && $applicant_count >= $edu['edu_personnel']) {
			return array('status' => 'error', 'message' => '모집이 마감되었습니다.');
		}

		$this->db->insert('wb_edu_applicant', $data);
		return array('status' => 'success', 'message' => '신청이 완료되었습니다.');
	}

	public function cancel_application($edu_idx, $user_id)
	{
		$this->db->where('edu_idx', $edu_idx);
		$this->db->where('user_id', $user_id);
		$this->db->update('wb_edu_applicant', array('del_yn' => 'Y'));
		return array('status' => 'success', 'message' => '신청이 취소되었습니다.');
	}

	public function get_user_application($edu_idx, $user_id)
	{
		$this->db->where('edu_idx', $edu_idx);
		$this->db->where('user_id', $user_id);
		$this->db->where('del_yn', 'N');
		$query = $this->db->get('wb_edu_applicant');
		return $query->row_array();
	}

	// --- Mng_education_model methods (modified for wb_edu_category) ---

	public function get_categories_as_tree($org_id)
	{
		$this->db->where('org_id', $org_id);
		$this->db->where('category_type', 'edu');
		$query = $this->db->get('wb_edu_category');
		$result = $query->row_array();

		if ($result && !empty($result['category_json'])) {
			log_message('debug', 'Category JSON for org ' . $org_id . ': ' . $result['category_json']);
			$categories = json_decode($result['category_json'], true);
			return $categories;
		}

		return array();
	}

	public function create_default_categories($org_id, $user_id)
	{
		$default_categories = array(
			array('id' => 1, 'parent_id' => 0, 'name' => '정회원', 'code' => 'CAT_REGULAR'),
			array('id' => 2, 'parent_id' => 0, 'name' => '준회원', 'code' => 'CAT_ASSOCIATE'),
		);

		$data = array(
			'org_id' => $org_id,
			'category_type' => 'edu',
			'category_json' => json_encode($default_categories, JSON_UNESCAPED_UNICODE),
			'user_id' => $user_id,
			'regi_date' => date('Y-m-d H:i:s')
		);

		return $this->db->insert('wb_edu_category', $data);
	}

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
			$counts[$row['category_code']] = $row['count'];
		}
		return $counts;
	}

	public function get_total_edu_count($org_id)
	{
		$this->db->where('org_id', $org_id);
		$this->db->where('del_yn', 'N');
		return $this->db->count_all_results('wb_edu');
	}

	public function get_edu_list_by_org($org_id, $search_params = array())
	{
		$this->db->select('e.*, (SELECT COUNT(*) FROM wb_edu_applicant WHERE edu_idx = e.edu_idx AND del_yn = "N") as applicant_count');
		$this->db->from('wb_edu e');
		$this->db->where('e.org_id', $org_id);
		$this->db->where('e.del_yn', 'N');
		$this->apply_mng_search_filters($search_params);
		$this->db->order_by('e.regi_date', 'DESC');
		$query = $this->db->get();
		return $query->result_array();
	}

	public function get_edu_list_by_category($org_id, $category_code)
	{
		$this->db->select('e.*, (SELECT COUNT(*) FROM wb_edu_applicant WHERE edu_idx = e.edu_idx AND del_yn = "N") as applicant_count');
		$this->db->from('wb_edu e');
		$this->db->where('e.org_id', $org_id);
		$this->db->where('e.category_code', $category_code);
		$this->db->where('e.del_yn', 'N');
		$this->db->order_by('e.regi_date', 'DESC');
		$query = $this->db->get();
		return $this->process_edu_list($query->result_array());
	}

	public function get_edu_by_idx($edu_idx)
	{
		$this->db->where('edu_idx', $edu_idx);
		$query = $this->db->get('wb_edu');
		return $query->row_array();
	}

	public function insert_edu($data)
	{
		$this->db->insert('wb_edu', $data);
		return $this->db->insert_id();
	}

	public function update_edu($edu_idx, $data)
	{
		$this->db->where('edu_idx', $edu_idx);
		return $this->db->update('wb_edu', $data);
	}

	public function delete_edu($edu_idx)
	{
		$this->db->where('edu_idx', $edu_idx);
		return $this->db->update('wb_edu', array('del_yn' => 'Y', 'modi_date' => date('Y-m-d H:i:s')));
	}

	public function save_category($data)
	{
		$this->db->where('org_id', $data['org_id']);
		$this->db->where('category_type', 'edu');
		$query = $this->db->get('wb_edu_category');

		if ($query->num_rows() > 0) {
			$this->db->where('org_id', $data['org_id']);
			$this->db->where('category_type', 'edu');
			return $this->db->update('wb_edu_category', array(
				'category_json' => $data['category_json'],
				'user_id' => $data['user_id'],
				'modi_date' => date('Y-m-d H:i:s')
			));
		} else {
			$data['regi_date'] = date('Y-m-d H:i:s');
			return $this->db->insert('wb_edu_category', $data);
		}
	}

	public function get_applicant_list($edu_idx)
	{
		$this->db->select('a.*, m.user_name as member_name, m.phone as member_phone');
		$this->db->from('wb_edu_applicant a');
		$this->db->join('wb_member m', 'a.member_idx = m.member_idx', 'left');
		$this->db->where('a.edu_idx', $edu_idx);
		$this->db->where('a.del_yn', 'N');
		$this->db->order_by('a.regi_date', 'ASC');
		$query = $this->db->get();
		return $query->result_array();
	}

	public function add_applicant($data)
	{
		$this->db->insert('wb_edu_applicant', $data);
		return $this->db->insert_id();
	}

	public function update_applicant($applicant_idx, $data)
	{
		$this->db->where('applicant_idx', $applicant_idx);
		return $this->db->update('wb_edu_applicant', $data);
	}

	public function delete_applicant($applicant_idx)
	{
		$this->db->where('applicant_idx', $applicant_idx);
		return $this->db->update('wb_edu_applicant', array('del_yn' => 'Y'));
	}

	public function save_external_url($data)
	{
		$this->db->where('edu_idx', $data['edu_idx']);
		$query = $this->db->get('wb_edu_external_url');
		if ($query->num_rows() > 0) {
			$this->db->where('edu_idx', $data['edu_idx']);
			return $this->db->update('wb_edu_external_url', $data);
		} else {
			return $this->db->insert('wb_edu_external_url', $data);
		}
	}

	public function get_external_url($edu_idx, $access_code)
	{
		$this->db->where('edu_idx', $edu_idx);
		$this->db->where('access_code', $access_code);
		$query = $this->db->get('wb_edu_external_url');
		return $query->row_array();
	}

	public function get_external_url_by_edu($edu_idx)
	{
		$this->db->where('edu_idx', $edu_idx);
		$this->db->where('expired_at >', date('Y-m-d H:i:s'));
		$query = $this->db->get('wb_edu_external_url');
		return $query->row_array();
	}

	public function check_duplicate_applicant($edu_idx, $name, $phone)
	{
		$this->db->where('edu_idx', $edu_idx);
		$this->db->where('applicant_name', $name);
		$this->db->where('applicant_phone', $phone);
		$this->db->where('del_yn', 'N');
		$query = $this->db->get('wb_edu_applicant');
		return $query->row_array();
	}

	public function get_applicant_count($edu_idx)
	{
		$this->db->where('edu_idx', $edu_idx);
		$this->db->where('del_yn', 'N');
		return $this->db->count_all_results('wb_edu_applicant');
	}

	public function get_applicant_by_idx($applicant_idx)
	{
		$this->db->where('applicant_idx', $applicant_idx);
		$query = $this->db->get('wb_edu_applicant');
		return $query->row_array();
	}

	// --- For Mng_education Controller ---

	public function get_mng_total_edu_count($category_ids = array())
	{
		$this->db->from('wb_edu e');
		$this->db->join('wb_org o', 'e.org_id = o.org_id');
		$this->db->where('e.del_yn', 'N');
		$this->db->where('o.del_yn', 'N');
		if (!empty($category_ids)) {
			$this->db->where_in('o.category_idx', $category_ids);
		}
		return $this->db->count_all_results();
	}

	public function get_mng_category_edu_count($category_idx)
	{
		$this->load->model('Org_category_model');
		$category_ids = $this->Org_category_model->get_category_with_descendants_public(array($category_idx));

		if (empty($category_ids)) {
			return 0;
		}

		$this->db->from('wb_edu e');
		$this->db->join('wb_org o', 'e.org_id = o.org_id');
		$this->db->where('e.del_yn', 'N');
		$this->db->where('o.del_yn', 'N');
		$this->db->where_in('o.category_idx', $category_ids);
		return $this->db->count_all_results();
	}

	public function get_mng_uncategorized_edu_count()
	{
		$this->db->from('wb_edu e');
		$this->db->join('wb_org o', 'e.org_id = o.org_id');
		$this->db->where('e.del_yn', 'N');
		$this->db->where('o.del_yn', 'N');
		$this->db->group_start();
		$this->db->where('o.category_idx IS NULL');
		$this->db->or_where('o.category_idx', 0);
		$this->db->group_end();
		return $this->db->count_all_results();
	}

	public function get_mng_org_edu_count($org_id)
	{
		$this->db->from('wb_edu');
		$this->db->where('org_id', $org_id);
		$this->db->where('del_yn', 'N');
		return $this->db->count_all_results();
	}

	public function get_mng_edu_list_by_categories($category_ids, $search_params = array())
	{
		$this->db->select('e.*, o.org_name');
		$this->db->from('wb_edu e');
		$this->db->join('wb_org o', 'e.org_id = o.org_id');
		$this->db->where('e.del_yn', 'N');
		$this->db->where('o.del_yn', 'N');
		$this->db->where_in('o.category_idx', $category_ids);
		$this->apply_mng_search_filters($search_params);
		$this->db->order_by('e.regi_date', 'DESC');
		$query = $this->db->get();
		return $query->result_array();
	}

	public function get_mng_all_edu_list($search_params = array())
	{
		$this->db->select('e.*, o.org_name');
		$this->db->from('wb_edu e');
		$this->db->join('wb_org o', 'e.org_id = o.org_id');
		$this->db->where('e.del_yn', 'N');
		$this->db->where('o.del_yn', 'N');
		$this->apply_mng_search_filters($search_params);
		$this->db->order_by('e.regi_date', 'DESC');
		$query = $this->db->get();
		return $query->result_array();
	}

	public function apply_mng_search_filters($params)
	{
		if (!empty($params['date'])) {
			$this->db->where('e.edu_start_date <=', $params['date']);
			$this->db->where('e.edu_end_date >=', $params['date']);
		}

		if (!empty($params['days']) && is_array($params['days'])) {
			$day_conditions = array();
			foreach ($params['days'] as $day) {
				$day_conditions[] = "e.edu_days LIKE '%\"" . $this->db->escape_like_str($day) . "\"%'";
			}
			if (!empty($day_conditions)) {
				$this->db->where('(' . implode(' OR ', $day_conditions) . ')');
			}
		}

		if (!empty($params['times']) && is_array($params['times'])) {
			$time_conditions = array();
			foreach ($params['times'] as $time) {
				$time_conditions[] = "e.edu_times LIKE '%\"" . $this->db->escape_like_str($time) . "\"%'";
			}
			if (!empty($time_conditions)) {
				$this->db->where('(' . implode(' OR ', $time_conditions) . ')');
			}
		}

		if (!empty($params['ages']) && is_array($params['ages'])) {
			$this->db->where_in('e.edu_leader_age', $params['ages']);
		}

		if (!empty($params['genders']) && is_array($params['genders'])) {
			$this->db->where_in('e.edu_leader_gender', $params['genders']);
		}

		if (!empty($params['keyword'])) {
			$this->db->group_start();
			$this->db->like('e.edu_name', $params['keyword']);
			$this->db->or_like('e.edu_location', $params['keyword']);
			$this->db->or_like('e.edu_leader', $params['keyword']);
			$this->db->group_end();
		}
	}
}
