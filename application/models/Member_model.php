<?php

class Member_model extends CI_Model
{
	public function __construct()
	{
		parent::__construct();
		$this->load->database();
	}


	public function add_member($data)
	{
		$this->db->insert('wb_member', $data);
		return $this->db->affected_rows() > 0;
	}


	/**
	 * 소그룹명으로 area_idx 찾기
	 */
	public function get_area_by_name($org_id, $area_name)
	{
		$this->db->select('area_idx, area_name');
		$this->db->from('tb_area');
		$this->db->where('org_id', $org_id);
		$this->db->where('area_name', $area_name);
		$this->db->where('del_yn', 'N');
		$query = $this->db->get();

		return $query->row_array();
	}


	/**
	 * 파일 위치: application/models/Member_model.php
	 * 역할: 회원 정보 조회 시 position_name, duty_name 필드 추가
	 */

	public function get_org_members($org_id, $level = null, $start_date = null, $end_date = null)
	{
		$user_id = $this->session->userdata('user_id');

		$this->db->select('m.member_idx, m.org_id, m.member_name,m.member_nick, m.photo, m.member_phone, m.member_address, m.member_address_detail, m.member_etc, m.leader_yn, m.new_yn, m.member_birth, m.position_name, m.duty_name, m.regi_date, m.modi_date, a.area_idx, a.area_name, a.area_order');
		$this->db->from('wb_member m');
		$this->db->join('wb_member_area a', 'm.area_idx = a.area_idx', 'left');

		if ($start_date && $end_date) {
			$this->db->select('GROUP_CONCAT(CONCAT(at.att_type_nickname, ", ", at.att_type_idx, ", ", at.att_type_category_idx, ", ", at.att_type_color) SEPARATOR "|") AS att_type_data', false);
			$this->db->join('wb_member_att ma', 'm.member_idx = ma.member_idx AND ma.att_date >= "' . $start_date . '" AND ma.att_date <= "' . $end_date . '"', 'left');
			$this->db->join('wb_att_type at', 'ma.att_type_idx = at.att_type_idx', 'left');
		}

		$this->db->where('m.org_id', $org_id);
		$this->db->where('m.del_yn', 'N');

		if ($level == 2) {
			$this->db->join('wb_member mm', 'mm.member_phone = (SELECT user_hp FROM wb_user WHERE user_id = "' . $user_id . '") AND mm.area_idx = m.area_idx', 'inner');
		}

		$this->db->group_by('m.member_idx');
		$this->db->order_by('a.area_order', 'ASC');
		$this->db->order_by('m.leader_yn ASC');
		$this->db->order_by('m.member_name ASC');

		$query = $this->db->get();
		return $query->result_array();
	}


	public function get_same_members($member_idx, $org_id, $area_idx, $start_date, $end_date)
	{
		$date_between = " ma.att_date BETWEEN '" . $start_date . "' AND '" . $end_date . "'";
		$this->db->select(' m.*, GROUP_CONCAT(CONCAT(ma.att_type_idx, ",", ma.att_date) SEPARATOR "|") AS attendance', false);
		$this->db->from('wb_member m');
		$this->db->join('wb_member_att ma', 'm.member_idx = ma.member_idx AND' . $date_between, 'left');
		$this->db->where('m.org_id', $org_id);
		$this->db->where('m.area_idx', $area_idx);

		$this->db->where('m.del_yn', 'N');
		$this->db->group_by('m.member_idx');
		$query = $this->db->get();
		return $query->result_array();
	}


	/**
	 * 회원 인덱스로 회원 정보 가져오기 (position_name, duty_name 필드 포함)
	 */
	public function get_member_by_idx($member_idx)
	{
		$this->db->select('m.*, a.area_name');
		$this->db->from('wb_member m');
		$this->db->join('wb_member_area a', 'm.area_idx = a.area_idx', 'left');
		$this->db->where('m.member_idx', $member_idx);

		$query = $this->db->get();

		return $query->row_array();
	}

	public function update_member($member_idx, $data, $org_id = null)
	{
		$this->db->where('member_idx', $member_idx);

		// org_id가 제공된 경우 추가 보안 체크
		if ($org_id !== null) {
			$this->db->where('org_id', $org_id);
		}

		$this->db->update('wb_member', $data);

		return $this->db->affected_rows() > 0;
	}

	/*
		public function update_multiple_members($member_idx, $data, $all_grade_check, $all_area_check) {
			$this->db->where('member_idx', $member_idx);
			$this->db->where('del_yn', 'N');

			$member = $this->db->get('wb_member')->row_array();
			$prev_grade = $member['grade'];
			$prev_area = $member['area'];

			if ($all_grade_check && isset($data['grade'])) {
				$this->db->where('grade', $prev_grade);
				$this->db->where('del_yn', 'N');
				$this->db->update('wb_member', array('grade' => $data['grade'], 'modi_date' => $data['modi_date']));
			}

			if ($all_area_check && isset($data['area'])) {
				$this->db->where('area', $prev_area);
				$this->db->where('del_yn', 'N');
				$this->db->update('wb_member', array('area' => $data['area'], 'modi_date' => $data['modi_date']));
			}

			$result = $this->db->update('wb_member', $data, array('member_idx' => $member_idx));




			return $result;
		}
	*/


	public function get_active_members($org_id, $five_weeks_ago)
	{
		$this->db->select('member_idx');
		$this->db->from('wb_member_att');
		$this->db->where('org_id', $org_id);
		$this->db->where('att_date >=', $five_weeks_ago);
		$this->db->distinct();

		$query = $this->db->get();

//        print_r($this->db->last_query());
//        exit;

		return $query->result_array();
	}

	/**
	 * 소속 그룹이 없는 회원 목록 조회 (position_name, duty_name 필드 포함)
	 */
	public function get_unassigned_members($org_id)
	{
		$this->db->select('m.*, "" as area_name');
		$this->db->from('wb_member m');
		$this->db->where('m.org_id', $org_id);
		$this->db->where('(m.area_idx IS NULL OR m.area_idx = "" OR m.area_idx = 0)', null, false);
		$this->db->where('m.del_yn', 'N');
		$this->db->order_by('m.regi_date', 'DESC'); // 최신 등록순으로 정렬
		$this->db->order_by('m.member_name', 'ASC');

		$query = $this->db->get();
		return $query->result_array();
	}



	/**
	 * 소속 그룹이 없는 회원 수 조회
	 */
	public function get_unassigned_members_count($org_id)
	{
		$this->db->from('wb_member');
		$this->db->where('org_id', $org_id);
		$this->db->where('(area_idx IS NULL OR area_idx = "" OR area_idx = 0)', null, false);
		$this->db->where('del_yn', 'N');
		return $this->db->count_all_results();
	}

	/**
	 * 조직의 다음 회원 번호 가져오기
	 */
	public function get_next_member_idx($org_id)
	{
		$this->db->select_max('member_idx');
		$this->db->where('org_id', $org_id);
		$this->db->where('del_yn', 'N');
		$query = $this->db->get('wb_member');
		$result = $query->row_array();

		return ($result['member_idx'] ? $result['member_idx'] : 0) + 1;
	}


	/**
	 * 조직의 전체 회원 수 조회
	 */
	public function get_org_member_count($org_id)
	{
		$this->db->from('wb_member');
		$this->db->where('org_id', $org_id);
		$this->db->where('del_yn', 'N');
		return $this->db->count_all_results();
	}


	/**
	 * 특정 영역과 그 하위 영역들의 모든 회원 조회 (position_name, duty_name 필드 포함)
	 */
	public function get_area_members_with_children($org_id, $area_idx)
	{
		// Member_area_model 로드
		$CI =& get_instance();
		$CI->load->model('Member_area_model');

		// 해당 영역과 모든 하위 영역들의 area_idx 수집
		$area_ids = $this->get_all_child_area_ids($area_idx, $org_id);
		$area_ids[] = $area_idx; // 자기 자신도 포함

		// 회원 조회
		$this->db->select('m.member_idx, m.org_id, m.member_name, m.member_nick, m.photo, m.member_phone, m.member_address,m.member_address_detail, m.member_etc, m.leader_yn, m.new_yn, m.member_birth, m.grade, m.position_name, m.duty_name, m.regi_date, m.modi_date, a.area_idx, a.area_name, a.area_order');
		$this->db->from('wb_member m');
		$this->db->join('wb_member_area a', 'm.area_idx = a.area_idx', 'left');
		$this->db->where('m.org_id', $org_id);
		$this->db->where_in('m.area_idx', $area_ids);
		$this->db->where('m.del_yn', 'N');
		$this->db->order_by('a.area_order', 'ASC');
		$this->db->order_by('m.leader_yn', 'ASC');
		$this->db->order_by('m.member_name', 'ASC');

		$query = $this->db->get();
		return $query->result_array();
	}
	/**
	 * 특정 영역과 그 하위 영역들의 회원 수 조회
	 */
	public function get_area_members_count_with_children($org_id, $area_idx)
	{
		// 해당 영역과 모든 하위 영역들의 area_idx 수집
		$area_ids = $this->get_all_child_area_ids($area_idx, $org_id);
		$area_ids[] = $area_idx; // 자기 자신도 포함

		$this->db->from('wb_member');
		$this->db->where('org_id', $org_id);
		$this->db->where_in('area_idx', $area_ids);
		$this->db->where('del_yn', 'N');

		return $this->db->count_all_results();
	}


	/**
	 * 파일 위치: application/models/User_management_model.php
	 * 역할: 특정 그룹의 모든 하위 그룹 ID를 재귀적으로 가져오기
	 */
	private function get_all_child_area_ids($parent_area_idx, $org_id)
	{
		$area_ids = array();

		// 직접 하위 영역들 조회
		$this->db->select('area_idx');
		$this->db->from('wb_member_area');
		$this->db->where('org_id', $org_id);
		$this->db->where('parent_idx', $parent_area_idx);

		$query = $this->db->get();
		$child_areas = $query->result_array();

		foreach ($child_areas as $child_area) {
			$area_ids[] = $child_area['area_idx'];

			// 재귀적으로 하위의 하위 영역들도 가져오기
			$grandchild_ids = $this->get_all_child_area_ids($child_area['area_idx'], $org_id);
			$area_ids = array_merge($area_ids, $grandchild_ids);
		}

		return $area_ids;
	}


	/**
	 * 회원 인덱스 배열로 회원 정보 가져오기 (position_name, duty_name 필드 포함)
	 */
	public function get_members_by_indices($member_indices)
	{
		if (empty($member_indices)) {
			return array();
		}

		$this->db->select('m.member_idx, m.org_id, m.member_name, m.member_nick, m.photo, m.member_phone, m.member_address, m.member_address_detail, m.member_etc, m.leader_yn, m.new_yn, m.member_birth, m.grade, m.position_name, m.duty_name, m.regi_date, m.modi_date, a.area_idx, a.area_name, a.area_order');
		$this->db->from('wb_member m');
		$this->db->join('wb_member_area a', 'm.area_idx = a.area_idx', 'left');
		$this->db->where_in('m.member_idx', $member_indices);
		$this->db->where('m.del_yn', 'N');
		$this->db->order_by('a.area_order', 'ASC');
		$this->db->order_by('m.leader_yn', 'ASC');
		$this->db->order_by('m.member_name', 'ASC');

		$query = $this->db->get();
		return $query->result_array();
	}


	/**
	 * 초대상태 사용자 거절 (삭제)
	 */
	public function reject_invited_user($user_id, $org_id)
	{
		$this->db->where('user_id', $user_id);
		$this->db->where('org_id', $org_id);
		return $this->db->delete('wb_org_user');
	}


	/**
	 * 역할: 특정 그룹들의 회원만 조회 (관리 권한에 따른 필터링용)
	 */
	/**
	 * 역할: 특정 그룹들의 회원만 조회 (관리 권한에 따른 필터링용)
	 */
	public function get_org_members_by_areas($org_id, $area_indices, $level = null, $start_date = null, $end_date = null)
	{
		if (empty($area_indices)) {
			return array();
		}

		$this->db->select('m.member_idx, m.org_id, m.member_name, m.member_nick, m.photo, m.member_phone, m.member_address, m.member_address_detail, m.member_etc, m.leader_yn, m.new_yn, m.member_birth, m.regi_date, m.modi_date, a.area_idx, a.area_name, a.area_order');
		$this->db->from('wb_member m');
		$this->db->join('wb_member_area a', 'm.area_idx = a.area_idx', 'left');

		if ($start_date && $end_date) {
			$this->db->select('GROUP_CONCAT(CONCAT(at.att_type_nickname, ", ", at.att_type_idx, ", ", at.att_type_category_idx, ", ", at.att_type_color) SEPARATOR "|") AS att_type_data', false);
			$this->db->join('wb_member_att ma', 'm.member_idx = ma.member_idx AND ma.att_date >= "' . $start_date . '" AND ma.att_date <= "' . $end_date . '"', 'left');
			$this->db->join('wb_att_type at', 'ma.att_type_idx = at.att_type_idx', 'left');
		}

		$this->db->where('m.org_id', $org_id);
		$this->db->where('m.del_yn', 'N');
		$this->db->where_in('m.area_idx', $area_indices);



		$this->db->group_by('m.member_idx');
		$this->db->order_by('a.area_order', 'ASC');
		$this->db->order_by('m.leader_yn', 'ASC');
		$this->db->order_by('m.member_name', 'ASC');

		$query = $this->db->get();
		return $query->result_array();
	}
	/**
	 * 역할: 특정 area_idx 목록으로 그룹 정보 조회 (권한 필터링용)
	 */
	public function get_member_areas_by_idx($org_id, $area_indices)
	{
		if (empty($area_indices)) {
			return array();
		}

		$this->db->select('area_idx, area_name, area_order, parent_idx');
		$this->db->from('wb_member_area');
		$this->db->where('org_id', $org_id);
		$this->db->where_in('area_idx', $area_indices);
		$this->db->order_by('area_order', 'ASC');

		$query = $this->db->get();
		return $query->result_array();
	}

	/**
	 * 역할: 특정 회원에 대한 접근 권한 확인
	 */
	public function check_member_access_permission($user_id, $member_idx)
	{
		// 회원 정보 조회
		$this->db->select('m.org_id, m.area_idx');
		$this->db->from('wb_member m');
		$this->db->where('m.member_idx', $member_idx);
		$this->db->where('m.del_yn', 'N');
		$query = $this->db->get();
		$member_info = $query->row_array();

		if (!$member_info) {
			return false;
		}

		// 사용자 권한 확인
		$this->load->model('User_model');
		$this->load->model('User_management_model');

		$master_yn = $this->session->userdata('master_yn');
		$user_level = $this->User_model->get_org_user_level($user_id, $member_info['org_id']);

		// 최고관리자 또는 마스터인 경우
		if ($user_level >= 10 || $master_yn === 'Y') {
			return true;
		}

		// 일반 관리자인 경우 관리 가능한 그룹 확인
		$accessible_areas = $this->User_management_model->get_user_managed_areas_with_children($user_id, $member_info['org_id']);
		return in_array($member_info['area_idx'], $accessible_areas);
	}


	public function get_member_area($member_idx)
	{
		$this->db->select('m.area_idx, ma.area_name');
		$this->db->from('wb_member m');
		$this->db->join('wb_member_area ma', 'm.area_idx = ma.area_idx', 'left');
		$this->db->where('m.member_idx', $member_idx);
		$this->db->where('m.del_yn', 'N');

		$query = $this->db->get();
		return $query->row_array();
	}

	public function get_member($member_idx)
	{
		$this->db->select('
        m.member_idx,
        m.member_name,
        m.member_phone,
        m.member_birth,
        m.org_id,
        m.area_idx,
        m.photo,
        COALESCE(ma.area_name, "미분류") as area_name
    ');
		$this->db->from('wb_member m');
		$this->db->join('wb_member_area ma', 'm.area_idx = ma.area_idx', 'left');
		$this->db->where('m.member_idx', $member_idx);
		$this->db->where('m.del_yn', 'N');

		$query = $this->db->get();
		return $query->row_array();
	}



	/**
	 * 역할: QR출석 화면용 조직 회원 조회 최적화 - 필요한 필드만 조회
	 */
	public function get_org_members_optimized($org_id, $level = null, $start_date = null, $end_date = null)
	{
		$this->db->select('
        m.member_idx,
        m.org_id,
        m.member_name,
        m.member_nick,
        m.photo,
        m.leader_yn,
        m.new_yn,
        m.member_birth,
        m.area_idx,
        a.area_name,
        a.area_order
    ');
		$this->db->from('wb_member m');
		$this->db->join('wb_member_area a', 'm.area_idx = a.area_idx', 'left');

		// 출석 데이터 조인 (날짜 범위가 있을 때만)
		if ($start_date && $end_date) {
			$sunday_date = $this->get_sunday_of_week($start_date);
			$att_year = date('Y', strtotime($sunday_date));

			$this->db->select('
            GROUP_CONCAT(DISTINCT CONCAT(at.att_type_nickname, ",", at.att_type_idx, ",", at.att_type_category_idx, ",", at.att_type_color) ORDER BY at.att_type_order SEPARATOR "|") as att_type_data
        ', false);
			$this->db->join('wb_member_att ma', 'm.member_idx = ma.member_idx AND ma.att_date = "' . $sunday_date . '" AND ma.att_year = ' . $att_year, 'left');
			$this->db->join('wb_att_type at', 'ma.att_type_idx = at.att_type_idx', 'left');
		}

		$this->db->where('m.org_id', $org_id);
		$this->db->where('m.del_yn', 'N');

		if ($level !== null) {
			$this->db->where('m.grade', $level);
		}

		$this->db->group_by('m.member_idx');
		$this->db->order_by('a.area_order', 'ASC');
		$this->db->order_by('m.leader_yn', 'ASC');
		$this->db->order_by('m.member_name', 'ASC');

		$query = $this->db->get();
		return $query->result_array();
	}

	/**
	 * 역할: QR출석 화면용 권한별 회원 조회 최적화 - 관리 가능한 그룹만
	 */
	public function get_org_members_by_areas_optimized($org_id, $area_indices, $level = null, $start_date = null, $end_date = null)
	{
		if (empty($area_indices)) {
			return array();
		}

		$this->db->select('
        m.member_idx,
        m.org_id,
        m.member_name,
        m.member_nick,
        m.photo,
        m.leader_yn,
        m.new_yn,
        m.member_birth,
        m.area_idx,
        a.area_name,
        a.area_order
    ');
		$this->db->from('wb_member m');
		$this->db->join('wb_member_area a', 'm.area_idx = a.area_idx', 'left');

		// 출석 데이터 조인 (날짜 범위가 있을 때만)
		if ($start_date && $end_date) {
			$sunday_date = $this->get_sunday_of_week($start_date);
			$att_year = date('Y', strtotime($sunday_date));

			$this->db->select('
            GROUP_CONCAT(DISTINCT CONCAT(at.att_type_nickname, ",", at.att_type_idx, ",", at.att_type_category_idx, ",", at.att_type_color) ORDER BY at.att_type_order SEPARATOR "|") as att_type_data
        ', false);
			$this->db->join('wb_member_att ma', 'm.member_idx = ma.member_idx AND ma.att_date = "' . $sunday_date . '" AND ma.att_year = ' . $att_year, 'left');
			$this->db->join('wb_att_type at', 'ma.att_type_idx = at.att_type_idx', 'left');
		}

		$this->db->where('m.org_id', $org_id);
		$this->db->where('m.del_yn', 'N');
		$this->db->where_in('m.area_idx', $area_indices);

		if ($level !== null) {
			$this->db->where('m.grade', $level);
		}

		$this->db->group_by('m.member_idx');
		$this->db->order_by('a.area_order', 'ASC');
		$this->db->order_by('m.leader_yn', 'ASC');
		$this->db->order_by('m.member_name', 'ASC');

		$query = $this->db->get();
		return $query->result_array();
	}

	/**
	 * 역할: 일요일 날짜 계산을 위한 공통 함수
	 */
	private function get_sunday_of_week($date)
	{
		$formatted_date = str_replace('.', '-', $date);
		$dt = new DateTime($formatted_date);
		$days_from_sunday = $dt->format('w');

		if ($days_from_sunday > 0) {
			$dt->sub(new DateInterval('P' . $days_from_sunday . 'D'));
		}

		return $dt->format('Y-m-d');
	}




	public function get_members_for_select($org_id, $search = '')
	{
		$this->db->select('member_idx, member_name');
		$this->db->from('wb_member');
		$this->db->where('org_id', $org_id);
		$this->db->where('del_yn', 'N');

		if (!empty($search)) {
			$this->db->like('member_name', $search);
		}

		$this->db->order_by('member_name', 'ASC');
		$this->db->limit(50);

		$query = $this->db->get();
		return $query->result_array();
	}








}




