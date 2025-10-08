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

		$this->db->select('m.member_idx, m.org_id, m.member_name, m.member_sex, m.member_nick, m.photo, m.member_phone, m.member_address, m.member_address_detail, m.member_etc, m.leader_yn, m.new_yn, m.member_birth, m.position_name, m.duty_name, m.regi_date, m.modi_date, a.area_idx, a.area_name, a.area_order');
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
		$this->db->select('m.member_idx, m.org_id, m.member_name, m.member_sex, m.member_nick, m.photo, m.member_phone, m.member_address,m.member_address_detail, m.member_etc, m.leader_yn, m.new_yn, m.member_birth, m.grade, m.position_name, m.duty_name, m.regi_date, m.modi_date, a.area_idx, a.area_name, a.area_order');
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

		$this->db->select('m.member_idx, m.org_id, m.member_name, m.member_sex, m.member_nick, m.photo, m.member_phone, m.member_address, m.member_address_detail, m.member_etc, m.leader_yn, m.new_yn, m.member_birth, m.grade, m.position_name, m.duty_name, m.regi_date, m.modi_date, a.area_idx, a.area_name, a.area_order');
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

		$this->db->select('m.member_idx, m.org_id, m.member_name, m.member_sex, m.member_nick, m.photo, m.member_phone, m.member_address, m.member_address_detail, m.member_etc, m.leader_yn, m.new_yn, m.member_birth, m.regi_date, m.modi_date, a.area_idx, a.area_name, a.area_order');
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
        m.member_sex,
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
        m.member_sex,
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
        m.member_sex,
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


	/**
	 * 파일 위치: application/models/Member_model.php
	 * 역할: 7일 이내 생일인 회원 조회 (미분류 제외)
	 */
	public function get_upcoming_birthday_members($org_id, $days = 7)
	{
		$dates = array();
		for ($i = 0; $i <= $days; $i++) {
			$date = date('m-d', strtotime("+{$i} days"));
			$dates[] = $date;
		}

		$this->db->select('
        m.member_idx,
        m.member_name,
        m.member_birth,
        m.org_id,
        a.area_name
    ');
		$this->db->from('wb_member m');
		$this->db->join('wb_member_area a', 'm.area_idx = a.area_idx', 'left');
		$this->db->where('m.org_id', $org_id);
		$this->db->where('m.del_yn', 'N');
		$this->db->where('m.member_birth IS NOT NULL');
		$this->db->where('m.member_birth !=', '');
		// 미분류 그룹 제외
		$this->db->where('(a.area_name IS NULL OR a.area_name != "미분류")');
		$this->db->order_by('m.member_birth', 'ASC');

		$query = $this->db->get();
		$all_members = $query->result_array();

		$birthday_members = array();

		foreach ($all_members as $member) {
			$birth = $member['member_birth'];
			$birth_md = null;

			if (preg_match('/^\d{4}[-\/]\d{2}[-\/]\d{2}$/', $birth)) {
				$birth_md = date('m-d', strtotime($birth));
			} else if (preg_match('/^\d{2}[-\/]\d{2}$/', $birth)) {
				$birth_md = date('m-d', strtotime('2000-' . str_replace('/', '-', $birth)));
			} else if (preg_match('/^\d{8}$/', $birth)) {
				$birth_md = substr($birth, 4, 2) . '-' . substr($birth, 6, 2);
				$birth_md = date('m-d', strtotime('2000-' . $birth_md));
			}

			if ($birth_md && in_array($birth_md, $dates)) {
				$birthday_members[] = array(
					'member_idx' => $member['member_idx'],
					'member_name' => $member['member_name'],
					'member_birth' => $member['member_birth'],
					'birth_md' => $birth_md,
					'area_name' => $member['area_name'],
					'org_id' => $member['org_id']
				);
			}
		}

		return $birthday_members;
	}

	/**
	 * 파일 위치: application/models/Member_model.php
	 * 역할: 오늘 생일인 회원 조회 (미분류 제외)
	 */
	public function get_today_birthday_members($org_id)
	{
		$today = date('m-d');

		$this->db->select('
        m.member_idx,
        m.member_name,
        m.member_birth,
        m.org_id,
        a.area_name
    ');
		$this->db->from('wb_member m');
		$this->db->join('wb_member_area a', 'm.area_idx = a.area_idx', 'left');
		$this->db->where('m.org_id', $org_id);
		$this->db->where('m.del_yn', 'N');
		$this->db->where('m.member_birth IS NOT NULL');
		$this->db->where('m.member_birth !=', '');
		// 미분류 그룹 제외
		$this->db->where('(a.area_name IS NULL OR a.area_name != "미분류")');
		$this->db->order_by('m.member_birth', 'ASC');

		$query = $this->db->get();
		$all_members = $query->result_array();

		$birthday_members = array();

		foreach ($all_members as $member) {
			$birth = $member['member_birth'];
			$birth_md = null;

			if (preg_match('/^\d{4}[-\/]\d{2}[-\/]\d{2}$/', $birth)) {
				$birth_md = date('m-d', strtotime($birth));
			} else if (preg_match('/^\d{2}[-\/]\d{2}$/', $birth)) {
				$birth_md = date('m-d', strtotime('2000-' . str_replace('/', '-', $birth)));
			} else if (preg_match('/^\d{8}$/', $birth)) {
				$birth_md = substr($birth, 4, 2) . '-' . substr($birth, 6, 2);
				$birth_md = date('m-d', strtotime('2000-' . $birth_md));
			}

			if ($birth_md && $birth_md === $today) {
				$birthday_members[] = array(
					'member_idx' => $member['member_idx'],
					'member_name' => $member['member_name'],
					'member_birth' => $member['member_birth'],
					'birth_md' => $birth_md,
					'area_name' => $member['area_name'],
					'org_id' => $member['org_id']
				);
			}
		}

		return $birthday_members;
	}

	/**
	 * 파일 위치: application/models/Member_model.php
	 * 역할: 금주 미출석 회원 조회 (미분류 제외)
	 */
	public function get_absent_members_this_week($org_id)
	{
		// 가장 상단의 출석 타입 조회
		$this->db->select('att_type_idx');
		$this->db->from('wb_att_type');
		$this->db->where('org_id', $org_id);
		$this->db->order_by('att_type_order', 'ASC');
		$this->db->limit(1);
		$query = $this->db->get();
		$att_type = $query->row_array();

		if (!$att_type) {
			return array();
		}

		$att_type_idx = $att_type['att_type_idx'];

		// 이번 주 일요일 날짜 계산
		$today = new DateTime();
		$days_from_sunday = $today->format('w');
		$this_sunday = clone $today;
		if ($days_from_sunday > 0) {
			$this_sunday->sub(new DateInterval('P' . $days_from_sunday . 'D'));
		}
		$sunday_date = $this_sunday->format('Y-m-d');

		// 모든 활성 회원 조회 (미분류 제외)
		$this->db->select('m.member_idx, m.member_name, a.area_name');
		$this->db->from('wb_member m');
		$this->db->join('wb_member_area a', 'm.area_idx = a.area_idx', 'left');
		$this->db->where('m.org_id', $org_id);
		$this->db->where('m.del_yn', 'N');
		// 미분류 그룹 제외
		$this->db->where('(a.area_name IS NULL OR a.area_name != "미분류")');
		$query = $this->db->get();
		$all_members = $query->result_array();

		$absent_members = array();

		foreach ($all_members as $member) {
			// 이번 주 출석 기록 확인
			$this->db->select('att_idx');
			$this->db->from('wb_member_att');
			$this->db->where('member_idx', $member['member_idx']);
			$this->db->where('org_id', $org_id);
			$this->db->where('att_date', $sunday_date);
			$this->db->where('att_type_idx', $att_type_idx);
			$query = $this->db->get();

			if ($query->num_rows() == 0) {
				$absent_members[] = $member;
			}
		}

		return $absent_members;
	}

	/**
	 * 파일 위치: application/models/Member_model.php
	 * 역할: 연속 미출석 회원 조회 (미분류 제외)
	 */
	public function get_absent_members_consecutive_weeks($org_id, $weeks = 2)
	{
		// 가장 상단의 출석 타입 조회
		$this->db->select('att_type_idx');
		$this->db->from('wb_att_type');
		$this->db->where('org_id', $org_id);
		$this->db->order_by('att_type_order', 'ASC');
		$this->db->limit(1);
		$query = $this->db->get();
		$att_type = $query->row_array();

		if (!$att_type) {
			return array();
		}

		$att_type_idx = $att_type['att_type_idx'];

		// 최근 N주의 일요일 날짜들 계산
		$sunday_dates = array();
		$today = new DateTime();
		$days_from_sunday = $today->format('w');
		$current_sunday = clone $today;
		if ($days_from_sunday > 0) {
			$current_sunday->sub(new DateInterval('P' . $days_from_sunday . 'D'));
		}

		for ($i = 0; $i < $weeks; $i++) {
			$sunday_dates[] = $current_sunday->format('Y-m-d');
			$current_sunday->sub(new DateInterval('P7D'));
		}

		// 모든 활성 회원 조회 (미분류 제외)
		$this->db->select('m.member_idx, m.member_name, a.area_name');
		$this->db->from('wb_member m');
		$this->db->join('wb_member_area a', 'm.area_idx = a.area_idx', 'left');
		$this->db->where('m.org_id', $org_id);
		$this->db->where('m.del_yn', 'N');
		// 미분류 그룹 제외
		$this->db->where('(a.area_name IS NULL OR a.area_name != "미분류")');
		$query = $this->db->get();
		$all_members = $query->result_array();

		$absent_members = array();

		foreach ($all_members as $member) {
			$consecutive_absent = true;

			// 모든 주차에 대해 출석 기록 확인
			foreach ($sunday_dates as $sunday_date) {
				$this->db->select('att_idx');
				$this->db->from('wb_member_att');
				$this->db->where('member_idx', $member['member_idx']);
				$this->db->where('org_id', $org_id);
				$this->db->where('att_date', $sunday_date);
				$this->db->where('att_type_idx', $att_type_idx);
				$query = $this->db->get();

				// 한 주라도 출석 기록이 있으면 제외
				if ($query->num_rows() > 0) {
					$consecutive_absent = false;
					break;
				}
			}

			if ($consecutive_absent) {
				$absent_members[] = $member;
			}
		}

		return $absent_members;
	}

	/**
	 * (내부 함수) wb_member.transfer_org_json 필드를 업데이트합니다.
	 * 해당 회원의 파송교회 ID 목록을 JSON 배열로 저장합니다.
	 *
	 * @param int $member_idx 회원 ID
	 * @param int $org_id 조직 ID
	 * @param int $id_to_link wb_transfer_org의 PK (transfer_org_id)
	 * @param string $action 'add' 또는 'delete'
	 * @return bool 업데이트 성공 여부
	 */
	private function _update_member_transfer_orgs_json_link($member_idx, $org_id, $id_to_link, $action) {
		// 1. 현재 JSON 데이터 가져오기
		$this->db->select('transfer_org_json');
		$this->db->from('wb_member');
		$this->db->where('member_idx', $member_idx);
		$query = $this->db->get();
		$row = $query->row_array();

		$current_ids = [];
		if ($row && !empty($row['transfer_org_json'])) {
			$ids_from_json = json_decode($row['transfer_org_json'], true);
			if (is_array($ids_from_json)) {
				// 배열 내의 모든 항목을 문자열로 변환하고 고유값만 유지
				$current_ids = array_map('strval', array_unique(array_filter($ids_from_json)));
			}
		}

		$id_to_link_str = (string)$id_to_link;

		// 2. ID 목록 수정
		if ($action === 'add') {
			if (!in_array($id_to_link_str, $current_ids)) {
				$current_ids[] = $id_to_link_str;
			}
		} elseif ($action === 'delete') {
			$current_ids = array_values(array_diff($current_ids, [$id_to_link_str]));
		}

		// 3. JSON으로 인코딩 후 저장 (예: ['11', '15'])
		$json_data = json_encode($current_ids, JSON_NUMERIC_CHECK | JSON_UNESCAPED_UNICODE);

		$this->db->where('member_idx', $member_idx);
		$this->db->where('org_id', $org_id);
		return $this->db->update('wb_member', [
			'transfer_org_json' => $json_data,
			'modi_date' => date('Y-m-d H:i:s')
		]);
	}

	/**
	 * 회원의 파송교회 목록 조회 (wb_transfer_org에서 조회)
	 * wb_member.transfer_org_json 필드를 참조하여 조회합니다.
	 */
	public function get_member_transfer_orgs($member_idx, $org_id)
	{
		// 1. wb_member.transfer_org_json에서 연결된 transfer_org_id 목록을 가져옵니다.
		$this->db->select('transfer_org_json');
		$this->db->from('wb_member');
		$this->db->where('member_idx', $member_idx);
		$query = $this->db->get();
		$row = $query->row_array();

		$transfer_org_ids = [];
		if ($row && !empty($row['transfer_org_json'])) {
			$ids_from_json = json_decode($row['transfer_org_json'], true);
			if (is_array($ids_from_json)) {
				$transfer_org_ids = array_map('strval', array_unique(array_filter($ids_from_json)));
			}
		}

		if (empty($transfer_org_ids)) {
			return array();
		}

		// 2. wb_transfer_org 테이블에서 해당 ID 목록을 조회합니다.
		$this->db->select('transfer_org_id AS idx, transfer_org_name, transfer_org_address, transfer_org_phone, transfer_org_rep, transfer_org_manager, transfer_org_email, transfer_org_desc, transfer_org_tag, regi_date, modi_date');
		$this->db->from('wb_transfer_org');
		$this->db->where_in('transfer_org_id', $transfer_org_ids);
		$this->db->where('del_yn', 'N');
		$this->db->order_by('regi_date', 'DESC');

		$query = $this->db->get();
		return $query->result_array();
	}

	public function insert_transfer_org($data)
	{
		$insert_data = [
			'transfer_org_name' => $data['transfer_name'] ?? null,
			'transfer_org_address' => $data['transfer_region'] ?? null,
			'transfer_org_phone' => $data['contact_phone'] ?? null,
			'transfer_org_rep' => $data['pastor_name'] ?? null,
			'transfer_org_manager' => $data['contact_person'] ?? null,
			'transfer_org_email' => $data['contact_email'] ?? null,
			'transfer_org_desc' => $data['transfer_description'] ?? null,
			'transfer_org_tag' => $data['org_tag'] ?? null,
			'regi_date' => $data['regi_date'],
			'modi_date' => $data['modi_date'],
			'del_yn' => $data['del_yn']
		];

		$this->db->trans_start();

		// 1. wb_transfer_org에 상세 정보 저장
		$this->db->insert('wb_transfer_org', $insert_data);
		$insert_id = $this->db->insert_id();

		if ($insert_id) {
			// 2. wb_member.transfer_org_json 필드 업데이트 (새 ID 연결)
			$this->_update_member_transfer_orgs_json_link($data['member_idx'], $data['org_id'], $insert_id, 'add');
		}

		$this->db->trans_complete();

		return $this->db->trans_status() === TRUE ? $insert_id : false;
	}

	/**
	 * 파송교회 수정 (wb_transfer_org의 상세 정보를 수정)
	 */
	public function update_transfer_org($transfer_org_id, $org_id, $data)
	{
		$member_idx = $data['member_idx'];

		// 수정할 데이터 매핑
		$update_data = [
			'transfer_org_name' => $data['transfer_name'] ?? null,
			'transfer_org_address' => $data['transfer_region'] ?? null,
			'transfer_org_phone' => $data['contact_phone'] ?? null,
			'transfer_org_rep' => $data['pastor_name'] ?? null,
			'transfer_org_manager' => $data['contact_person'] ?? null,
			'transfer_org_email' => $data['contact_email'] ?? null,
			'transfer_org_desc' => $data['transfer_description'] ?? null,
			'transfer_org_tag' => $data['org_tag'] ?? null,
			'modi_date' => $data['modi_date']
		];

		// PK가 SET 절에 포함되지 않도록 확인
		if (isset($update_data['transfer_org_id'])) {
			unset($update_data['transfer_org_id']);
		}

		$this->db->trans_start();

		// 1. wb_transfer_org 상세 정보 수정
		$this->db->where('transfer_org_id', $transfer_org_id);
		$this->db->where('del_yn', 'N');
		$result = $this->db->update('wb_transfer_org', $update_data);

		if ($result) {
			// 2. wb_member.transfer_org_json 필드 업데이트
			$this->_update_member_transfer_orgs_json_link($member_idx, $org_id, $transfer_org_id, 'add');
		}

		$this->db->trans_complete();

		return $this->db->trans_status() === TRUE ? $result : false;
	}

	/**
	 * 파송교회 삭제 (wb_transfer_org에서 논리적 삭제 및 wb_member.transfer_org_json 업데이트)
	 */
	public function delete_transfer_org($transfer_org_id, $org_id, $member_idx)
	{
		$data = [
			'del_yn' => 'Y',
			'del_date' => date('Y-m-d H:i:s'),
			'modi_date' => date('Y-m-d H:i:s')
		];

		$this->db->trans_start();

		// 1. wb_transfer_org 논리적 삭제. transfer_org_id (PK)로만 조회합니다.
		$this->db->where('transfer_org_id', $transfer_org_id);
		$result = $this->db->update('wb_transfer_org', $data);

		if ($result) {
			// 2. wb_member.transfer_org_json 필드에서 해당 ID 제거
			$this->_update_member_transfer_orgs_json_link($member_idx, $org_id, $transfer_org_id, 'delete');
		}

		$this->db->trans_complete();

		return $this->db->trans_status() === TRUE ? $result : false;
	}


	/**
	 * 선택 가능한 결연교회 목록 조회
	 * wb_org_category에서 '결연교회' 카테고리 하위의 모든 교회 조회
	 */
	public function get_available_churches()
	{
		// 1. '결연교회' 카테고리 찾기
		$this->db->select('category_idx');
		$this->db->from('wb_org_category');
		$this->db->where('category_name', '결연교회');
		$category_query = $this->db->get();

		if ($category_query->num_rows() == 0) {
			return [];
		}

		$category_idx = $category_query->row()->category_idx;

		// 2. 하위 카테고리들 재귀적으로 찾기
		$category_indices = $this->get_child_categories($category_idx);
		$category_indices[] = $category_idx; // 자기 자신도 포함

		// 3. 해당 카테고리들의 교회 조회
		$this->db->select('org_id, org_name, org_address, org_phone, org_rep');
		$this->db->from('wb_org');
		$this->db->where_in('category_idx', $category_indices);
		$this->db->where('del_yn', 'N');
		$this->db->order_by('org_name', 'ASC');

		$query = $this->db->get();
		return $query->result_array();
	}

	/**
	 * 하위 카테고리 재귀 조회
	 */
	private function get_child_categories($parent_idx)
	{
		$result = [];

		$this->db->select('category_idx');
		$this->db->from('wb_org_category');
		$this->db->where('parent_idx', $parent_idx);
		$query = $this->db->get();

		foreach ($query->result_array() as $row) {
			$result[] = $row['category_idx'];
			// 재귀적으로 하위 카테고리 조회
			$children = $this->get_child_categories($row['category_idx']);
			$result = array_merge($result, $children);
		}

		return $result;
	}

}
