<?php

class Member_model extends CI_Model
{
	public function __construct()
	{
		parent::__construct();
		$this->load->database();
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
	 * 회원 패스코드 생성 (6자리 영문+숫자)
	 */
	public function generate_member_passcode()
	{
		$characters = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // 혼동되는 문자 제외 (I, O, 0, 1 등)
		$passcode = '';

		for ($i = 0; $i < 6; $i++) {
			$passcode .= $characters[rand(0, strlen($characters) - 1)];
		}

		// 중복 확인
		$this->db->where('member_passcode', $passcode);
		$query = $this->db->get('wb_member');

		if ($query->num_rows() > 0) {
			return $this->generate_member_passcode(); // 중복 시 재생성
		}

		return $passcode;
	}
	/**
	 * 회원 패스코드로 회원 정보 조회
	 */
	public function get_member_by_passcode($member_idx, $passcode)
	{
		$this->db->select('m.*, a.area_name');
		$this->db->from('wb_member m');
		$this->db->join('wb_member_area a', 'm.area_idx = a.area_idx', 'left');
		$this->db->where('m.member_idx', $member_idx);
		$this->db->where('m.member_passcode', $passcode);
		$this->db->where('m.del_yn', 'N');

		$query = $this->db->get();
		return $query->row_array();
	}

	/**
	 * 회원 추가 시 패스코드 자동 생성 (기존 add_member 함수 수정)
	 */
	public function add_member($data)
	{
		// 패스코드가 없으면 자동 생성
		if (empty($data['member_passcode'])) {
			$data['member_passcode'] = $this->generate_member_passcode();
		}

		$this->db->insert('wb_member', $data);
		return $this->db->affected_rows() > 0;
	}

	/**
	 * 최근 8주간 주별 신규 회원 수 조회
	 * @param int $org_id 조직 ID
	 * @return array 주별 신규 회원 수 데이터
	 */
	public function get_weekly_new_members($org_id)
	{
		$weekly_data = array();

		// 현재 날짜에서 요일 계산 (0=일요일, 6=토요일)
		$today = new DateTime();
		$day_of_week = (int)$today->format('w');

		// 이번 주 일요일 계산
		if ($day_of_week == 0) {
			$current_sunday = clone $today;
		} else {
			$current_sunday = clone $today;
			$current_sunday->sub(new DateInterval('P' . $day_of_week . 'D'));
		}

		// 최근 8주 데이터 조회
		for ($i = 7; $i >= 0; $i--) {
			$week_sunday = clone $current_sunday;
			$week_sunday->sub(new DateInterval('P' . ($i * 7) . 'D'));

			$week_saturday = clone $week_sunday;
			$week_saturday->add(new DateInterval('P6D'));

			$start_date = $week_sunday->format('Y-m-d') . ' 00:00:00';
			$end_date = $week_saturday->format('Y-m-d') . ' 23:59:59';

			// 해당 주의 신규 회원 수 조회
			$this->db->select('COUNT(*) as count');
			$this->db->from('wb_member');
			$this->db->where('org_id', $org_id);
			$this->db->where('del_yn', 'N');
			$this->db->where('regi_date >=', $start_date);
			$this->db->where('regi_date <=', $end_date);

			$query = $this->db->get();
			$result = $query->row_array();

			$weekly_data[] = array(
				'week_label' => $week_sunday->format('n/j'),  // 예: 8/17
				'count' => (int)$result['count']
			);
		}

		return $weekly_data;
	}


}
