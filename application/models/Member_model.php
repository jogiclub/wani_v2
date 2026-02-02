<?php

class Member_model extends CI_Model
{
	public function __construct()
	{
		parent::__construct();
		$this->load->database();
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

		// 먼저 모든 그룹 정보를 조회
		$all_areas = $this->get_all_areas_for_hierarchy($org_id);

		// 조직의 duty_name 순서 조회
		$duty_order = $this->get_duty_order($org_id);

		// 회원 조회
		$this->db->select('m.member_idx, m.org_id, m.member_name, m.member_sex, m.member_nick, m.photo, m.member_phone, m.member_address, m.member_address_detail, m.member_etc, m.leader_yn, m.new_yn, m.member_birth, m.grade, m.position_name, m.duty_name, m.regi_date, m.modi_date, a.area_idx, a.area_name, a.area_order, a.parent_idx');
		$this->db->from('wb_member m');
		$this->db->join('wb_member_area a', 'm.area_idx = a.area_idx', 'left');
		$this->db->where('m.org_id', $org_id);
		$this->db->where_in('m.area_idx', $area_ids);
		$this->db->where('m.del_yn', 'N');

		$query = $this->db->get();
		$members = $query->result_array();

		// 각 회원에 계층 구조 정보 추가
		foreach ($members as &$member) {
			if ($member['area_idx']) {
				$hierarchy = $this->build_area_hierarchy($member['area_idx'], $all_areas);
				$member['area_sort_path'] = $hierarchy['sort_path'];
				$member['area_full_path'] = $hierarchy['name_path'];
			} else {
				$member['area_sort_path'] = '999';
				$member['area_full_path'] = '';
			}
		}

		// 계층 구조 경로로 정렬
		usort($members, function($a, $b) use ($duty_order) {
			// 1. area_sort_path로 그룹 정렬
			$pathCompare = strcmp($a['area_sort_path'], $b['area_sort_path']);
			if ($pathCompare !== 0) return $pathCompare;

			// 2. duty_name 있는 회원 우선 (있으면 0, 없으면 1)
			$posA = empty($a['duty_name']) ? 1 : 0;
			$posB = empty($b['duty_name']) ? 1 : 0;
			if ($posA !== $posB) return $posA - $posB;

			// 3. duty_name이 둘 다 있으면 duty_order 순서대로
			if (!empty($a['duty_name']) && !empty($b['duty_name'])) {
				$orderA = isset($duty_order[$a['duty_name']]) ? $duty_order[$a['duty_name']] : 9999;
				$orderB = isset($duty_order[$b['duty_name']]) ? $duty_order[$b['duty_name']] : 9999;
				if ($orderA !== $orderB) return $orderA - $orderB;
			}

			// 4. member_idx 순
			return (int)$a['member_idx'] - (int)$b['member_idx'];
		});

		return $members;
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
	 * 역할: QR출석 화면용 조직 회원 조회 최적화 - parent_idx 추가
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
		a.area_order,
		a.parent_idx
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
 * 파일 위치: application/models/Member_model.php
 * 역할: QR출석 화면용 권한별 회원 조회 최적화 - parent_idx 추가
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
		a.area_order,
		a.parent_idx
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




	/**
	 * 셀렉트박스용 회원 목록 조회
	 * @param int $org_id 조직 ID
	 * @return array 회원 목록 (member_idx, member_name, member_phone)
	 */
	public function get_members_for_select($org_id)
	{
		$this->db->select('member_idx, member_name, member_phone');
		$this->db->from('wb_member');
		$this->db->where('org_id', $org_id);
		$this->db->where('del_yn', 'N');
		$this->db->order_by('member_name', 'ASC');

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

		if ($this->db->affected_rows() > 0) {
			return $this->db->insert_id();
		}

		return false;
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


	/**
	 * 월별 주차별 조직별 신규 회원 통계 조회
	 */
	public function get_monthly_new_members_by_orgs($orgs, $year, $month)
	{
		if (empty($orgs)) {
			return array(
				'orgs' => array(),
				'weekly_data' => array()
			);
		}

		$org_ids = array_column($orgs, 'org_id');
		$sundays = $this->get_sundays_in_month($year, $month);

		$weekly_data = array();

		foreach ($sundays as $sunday_info) {
			$sunday_date = $sunday_info['sunday_date'];

			$this->db->select("org_id, new_member_count");
			$this->db->from('wb_member_weekly_stats');
			$this->db->where_in('org_id', $org_ids);
			$this->db->where('att_year', $year);
			$this->db->where('sunday_date', $sunday_date);

			$query = $this->db->get();
			$results = $query->result_array();

			$org_data_map = array();
			foreach ($results as $row) {
				$org_data_map[$row['org_id']] = (int)$row['new_member_count'];
			}

			$week_data = array(
				'week_label' => $sunday_info['label'],
				'orgs' => array()
			);

			foreach ($orgs as $org) {
				$week_data['orgs'][$org['org_id']] = isset($org_data_map[$org['org_id']])
					? $org_data_map[$org['org_id']]
					: 0;
			}

			$weekly_data[] = $week_data;
		}

		// 조직 정보 간소화 (ID와 이름만)
		$simplified_orgs = array();
		foreach ($orgs as $org) {
			$simplified_orgs[] = array(
				'org_id' => $org['org_id'],
				'org_name' => $org['org_name']
			);
		}

		return array(
			'orgs' => $simplified_orgs,
			'weekly_data' => $weekly_data
		);
	}

	/**
	 * 해당 월의 일요일 날짜 목록 조회
	 */
	public function get_sundays_in_month($year, $month)
	{
		$sundays = array();
		$first_day = new DateTime("{$year}-{$month}-01");
		$last_day = new DateTime($first_day->format('Y-m-t'));

		$current = clone $first_day;
		$day_of_week = (int)$current->format('w');

		if ($day_of_week != 0) {
			$current->add(new DateInterval('P' . (7 - $day_of_week) . 'D'));
		}

		while ($current <= $last_day) {
			$sundays[] = array(
				'sunday_date' => $current->format('Y-m-d'),
				'label' => $current->format('n/j')
			);
			$current->add(new DateInterval('P7D'));
		}

		return $sundays;
	}




	/**
	 * 파일 위치: application/models/Member_model.php
	 * 역할: 회원 정보 조회 - 계층 구조 정렬 경로 추가
	 */
	public function get_org_members($org_id, $level = null, $start_date = null, $end_date = null)
	{
		$user_id = $this->session->userdata('user_id');

		// 먼저 모든 그룹 정보를 조회
		$all_areas = $this->get_all_areas_for_hierarchy($org_id);

		// 조직의 duty_name 순서 조회
		$duty_order = $this->get_duty_order($org_id);

		$this->db->select('m.member_idx, m.org_id, m.member_name, m.member_sex, m.member_nick, m.photo, m.member_phone, m.member_address, m.member_address_detail, m.member_etc, m.leader_yn, m.new_yn, m.member_birth, m.position_name, m.duty_name, m.regi_date, m.modi_date, a.area_idx, a.area_name, a.area_order, a.parent_idx');
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
		$this->db->order_by('m.leader_yn', 'ASC');
		$this->db->order_by('m.member_name', 'ASC');

		$query = $this->db->get();
		$members = $query->result_array();

		// 각 회원에 계층 구조 정보 추가
		foreach ($members as &$member) {
			if ($member['area_idx']) {
				$hierarchy = $this->build_area_hierarchy($member['area_idx'], $all_areas);
				$member['area_sort_path'] = $hierarchy['sort_path'];
				$member['area_full_path'] = $hierarchy['name_path'];
			} else {
				$member['area_sort_path'] = '999';
				$member['area_full_path'] = '';
			}
		}

		// 계층 구조 경로로 정렬
		usort($members, function($a, $b) use ($duty_order) {
			// 1. area_sort_path로 그룹 정렬
			$pathCompare = strcmp($a['area_sort_path'], $b['area_sort_path']);
			if ($pathCompare !== 0) return $pathCompare;

			// 2. duty_name 있는 회원 우선 (있으면 0, 없으면 1)
			$posA = empty($a['duty_name']) ? 1 : 0;
			$posB = empty($b['duty_name']) ? 1 : 0;
			if ($posA !== $posB) return $posA - $posB;

			// 3. duty_name이 둘 다 있으면 duty_order 순서대로
			if (!empty($a['duty_name']) && !empty($b['duty_name'])) {
				$orderA = isset($duty_order[$a['duty_name']]) ? $duty_order[$a['duty_name']] : 9999;
				$orderB = isset($duty_order[$b['duty_name']]) ? $duty_order[$b['duty_name']] : 9999;
				if ($orderA !== $orderB) return $orderA - $orderB;
			}

			// 4. member_idx 순
			return (int)$a['member_idx'] - (int)$b['member_idx'];
		});

		return $members;
	}

	/**
	 * 역할: 특정 그룹들의 회원만 조회 - 계층 구조 정렬 경로 추가
	 */
	public function get_org_members_by_areas($org_id, $area_indices, $level = null, $start_date = null, $end_date = null)
	{
		if (empty($area_indices)) {
			return array();
		}

		// 먼저 모든 그룹 정보를 조회
		$all_areas = $this->get_all_areas_for_hierarchy($org_id);

		// 조직의 duty_name 순서 조회
		$duty_order = $this->get_duty_order($org_id);

		$this->db->select('m.member_idx, m.org_id, m.member_name, m.member_sex, m.member_nick, m.photo, m.member_phone, m.member_address, m.member_address_detail, m.member_etc, m.leader_yn, m.new_yn, m.member_birth, m.position_name, m.duty_name, m.regi_date, m.modi_date, a.area_idx, a.area_name, a.area_order, a.parent_idx');
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
		$members = $query->result_array();

		// 각 회원에 계층 구조 정보 추가
		foreach ($members as &$member) {
			if ($member['area_idx']) {
				$hierarchy = $this->build_area_hierarchy($member['area_idx'], $all_areas);
				$member['area_sort_path'] = $hierarchy['sort_path'];
				$member['area_full_path'] = $hierarchy['name_path'];
			} else {
				$member['area_sort_path'] = '999';
				$member['area_full_path'] = '';
			}
		}

		// 계층 구조 경로로 정렬
		usort($members, function($a, $b) use ($duty_order) {
			// 1. area_sort_path로 그룹 정렬
			$pathCompare = strcmp($a['area_sort_path'], $b['area_sort_path']);
			if ($pathCompare !== 0) return $pathCompare;

			// 2. duty_name 있는 회원 우선 (있으면 0, 없으면 1)
			$posA = empty($a['duty_name']) ? 1 : 0;
			$posB = empty($b['duty_name']) ? 1 : 0;
			if ($posA !== $posB) return $posA - $posB;

			// 3. duty_name이 둘 다 있으면 duty_order 순서대로
			if (!empty($a['duty_name']) && !empty($b['duty_name'])) {
				$orderA = isset($duty_order[$a['duty_name']]) ? $duty_order[$a['duty_name']] : 9999;
				$orderB = isset($duty_order[$b['duty_name']]) ? $duty_order[$b['duty_name']] : 9999;
				if ($orderA !== $orderB) return $orderA - $orderB;
			}

			// 4. member_idx 순
			return (int)$a['member_idx'] - (int)$b['member_idx'];
		});

		return $members;
	}


	/**
	 * 역할: 조직의 duty_name 순서 조회 (설정된 순서대로 인덱스 반환)
	 */
	private function get_duty_order($org_id)
	{
		$duty_order = array();

		// wb_org 테이블에서 duty_name JSON 조회
		$this->db->select('duty_name');
		$this->db->from('wb_org');
		$this->db->where('org_id', $org_id);
		$query = $this->db->get();
		$result = $query->row_array();

		if ($result && !empty($result['duty_name'])) {
			$duties = json_decode($result['duty_name'], true);
			if (is_array($duties)) {
				foreach ($duties as $index => $duty) {
					$duty_order[$duty] = $index;
				}
			}
		}

		return $duty_order;
	}

	/**
	 * 파일 위치: application/models/Member_model.php
	 * 역할: 조직의 모든 그룹 정보 조회 (계층 구조 계산용)
	 */
	private function get_all_areas_for_hierarchy($org_id)
	{
		$this->db->select('area_idx, area_name, area_order, parent_idx');
		$this->db->from('wb_member_area');
		$this->db->where('org_id', $org_id);
		$query = $this->db->get();

		$areas = array();
		foreach ($query->result_array() as $area) {
			$areas[$area['area_idx']] = $area;
		}
		return $areas;
	}

	/**
	 * 파일 위치: application/models/Member_model.php
	 * 역할: 특정 그룹의 계층 구조 경로 생성 (정렬용 경로 + 표시용 경로)
	 */
	private function build_area_hierarchy($area_idx, $all_areas, $max_depth = 10)
	{
		$sort_parts = array();
		$name_parts = array();
		$current_idx = $area_idx;
		$depth = 0;

		// 최상위까지 역순으로 올라가며 경로 수집
		while ($current_idx && isset($all_areas[$current_idx]) && $depth < $max_depth) {
			$area = $all_areas[$current_idx];
			// 정렬용: area_order를 3자리 숫자로 패딩 (예: 4 -> "004")
			array_unshift($sort_parts, str_pad($area['area_order'], 3, '0', STR_PAD_LEFT));
			// 표시용: area_name
			array_unshift($name_parts, $area['area_name']);

			$parent_idx = $area['parent_idx'];
			// parent_idx가 0이거나 빈 값이면 최상위
			if (empty($parent_idx) || $parent_idx == '0') {
				break;
			}
			$current_idx = $parent_idx;
			$depth++;
		}

		return array(
			'sort_path' => implode('-', $sort_parts),  // 예: "004-001-001"
			'name_path' => implode(' <i class="bi bi-chevron-right" style="font-size: 12px"></i> ', $name_parts)
		);
	}


	/**
	 * 파일 위치: application/models/Member_model.php
	 * 역할: 회원 상태 업데이트
	 */
	public function update_member_status($member_idx, $member_status)
	{
		$this->db->where('member_idx', $member_idx);
		$this->db->update('wb_member', array(
			'member_status' => $member_status,
			'modi_date' => date('Y-m-d H:i:s')
		));

		return $this->db->affected_rows() > 0;
	}


	public function get_master_members_all($category_ids = array(), $status_tag = '', $keyword = '')
	{
		$this->db->select('m.*, o.org_name, c.category_name');
		$this->db->from('wb_member m');
		$this->db->join('wb_org o', 'm.org_id = o.org_id');
		$this->db->join('wb_org_category c', 'o.category_idx = c.category_idx', 'left');
		$this->db->where('m.del_yn', 'N');
		$this->db->where('o.del_yn', 'N');

		// 카테고리 필터
		if (!empty($category_ids)) {
			$this->db->where_in('o.category_idx', $category_ids);
		}

		// 검색 조건 적용
		$this->apply_master_search_conditions($status_tag, $keyword);

		$this->db->order_by('m.regi_date', 'DESC');
		$query = $this->db->get();
		return $query->result_array();
	}

	/**
	 * 파일 위치: application/models/Member_model.php
	 * 역할: 마스터 회원관리 - 카테고리별 회원 조회 (하위 카테고리 포함)
	 * @param array $category_ids 조회할 카테고리 ID 배열 (하위 포함된 상태)
	 * @param string $status_tag 관리tag 검색 조건
	 * @param string $keyword 이름/연락처 검색 조건
	 */
	public function get_master_members_by_category($category_ids, $status_tag = '', $keyword = '')
	{
		if (empty($category_ids)) {
			return array();
		}

		$this->db->select('m.*, o.org_name, c.category_name');
		$this->db->from('wb_member m');
		$this->db->join('wb_org o', 'm.org_id = o.org_id');
		$this->db->join('wb_org_category c', 'o.category_idx = c.category_idx', 'left');
		$this->db->where('m.del_yn', 'N');
		$this->db->where('o.del_yn', 'N');
		$this->db->where_in('o.category_idx', $category_ids);

		// 검색 조건 적용
		$this->apply_master_search_conditions($status_tag, $keyword);

		$this->db->order_by('m.regi_date', 'DESC');
		$query = $this->db->get();
		return $query->result_array();
	}

	/**
	 * 파일 위치: application/models/Member_model.php
	 * 역할: 마스터 회원관리 - 조직별 회원 조회
	 * @param string $org_id 조직 ID
	 * @param string $status_tag 관리tag 검색 조건
	 * @param string $keyword 이름/연락처 검색 조건
	 */
	public function get_master_members_by_org($org_id, $status_tag = '', $keyword = '')
	{
		$this->db->select('m.*, o.org_name, c.category_name');
		$this->db->from('wb_member m');
		$this->db->join('wb_org o', 'm.org_id = o.org_id');
		$this->db->join('wb_org_category c', 'o.category_idx = c.category_idx', 'left');
		$this->db->where('m.org_id', $org_id);
		$this->db->where('m.del_yn', 'N');
		$this->db->where('o.del_yn', 'N');

		// 검색 조건 적용
		$this->apply_master_search_conditions($status_tag, $keyword);

		$this->db->order_by('m.regi_date', 'DESC');
		$query = $this->db->get();
		return $query->result_array();
	}

	/**
	 * 파일 위치: application/models/Member_model.php
	 * 역할: 마스터 회원관리 - 미분류 조직의 회원 조회
	 * @param string $status_tag 관리tag 검색 조건
	 * @param string $keyword 이름/연락처 검색 조건
	 */
	public function get_master_members_uncategorized($status_tag = '', $keyword = '')
	{
		$this->db->select('m.*, o.org_name, "" as category_name');
		$this->db->from('wb_member m');
		$this->db->join('wb_org o', 'm.org_id = o.org_id');
		$this->db->where('m.del_yn', 'N');
		$this->db->where('o.del_yn', 'N');
		$this->db->group_start();
		$this->db->where('o.category_idx IS NULL');
		$this->db->or_where('o.category_idx', 0);
		$this->db->group_end();

		// 검색 조건 적용
		$this->apply_master_search_conditions($status_tag, $keyword);

		$this->db->order_by('m.regi_date', 'DESC');
		$query = $this->db->get();
		return $query->result_array();
	}

	/**
	 * 파일 위치: application/models/Member_model.php
	 * 역할: 마스터 회원관리 검색 조건 적용 (다중 태그 지원)
	 * @param mixed $status_tags 관리tag 검색 조건 (문자열 또는 배열)
	 * @param string $keyword 이름/연락처 검색 조건
	 */
	private function apply_master_search_conditions($status_tags = '', $keyword = '')
	{
		// 관리tag 검색 (다중 태그 - OR 조건)
		if (!empty($status_tags)) {
			// 문자열인 경우 배열로 변환
			if (is_string($status_tags)) {
				$tags_array = array_filter(array_map('trim', explode(',', $status_tags)));
			} else {
				$tags_array = $status_tags;
			}

			if (!empty($tags_array)) {
				$this->db->group_start();
				foreach ($tags_array as $index => $tag) {
					if ($index === 0) {
						$this->db->like('m.member_status', $tag);
					} else {
						$this->db->or_like('m.member_status', $tag);
					}
				}
				$this->db->group_end();
			}
		}

		// 이름 또는 연락처 검색
		if (!empty($keyword)) {
			$this->db->group_start();
			$this->db->like('m.member_name', $keyword);
			$this->db->or_like('m.member_phone', $keyword);
			$this->db->group_end();
		}
	}

	/**
	 * 파일 위치: application/models/Member_model.php
	 * 역할: 마스터 회원관리 - 기존 관리tag 태그 목록 조회
	 */
	public function get_existing_status_tags()
	{
		$this->db->select('DISTINCT(member_status) as status');
		$this->db->from('wb_member');
		$this->db->where('member_status IS NOT NULL');
		$this->db->where('member_status !=', '');
		$this->db->where('del_yn', 'N');
		$query = $this->db->get();

		$tags = array();
		foreach ($query->result_array() as $row) {
			// 쉼표로 구분된 태그 파싱
			$status_tags = explode(',', $row['status']);
			foreach ($status_tags as $tag) {
				$tag = trim($tag);
				if (!empty($tag) && !in_array($tag, $tags)) {
					$tags[] = $tag;
				}
			}
		}

		sort($tags);
		return $tags;
	}

	/**
	 * 파일 위치: application/models/Member_model.php
	 * 역할: 회원 사진 URL 처리
	 * @param array $members 회원 목록 배열
	 * @return array 사진 URL이 추가된 회원 목록
	 */
	public function process_member_photo_urls($members)
	{
		foreach ($members as &$member) {
			$photo_url = '/assets/images/photo_no.png';
			if (!empty($member['photo'])) {
				if (strpos($member['photo'], '/uploads/') === false) {
					$photo_url = '/uploads/member_photos/' . $member['org_id'] . '/' . $member['photo'];
				} else {
					$photo_url = $member['photo'];
				}
			}
			$member['photo_url'] = $photo_url;
		}
		return $members;
	}






	/**
	 * 회원의 초기 가족 데이터 생성 (본인 노드)
	 *
	 * @param int $member_idx 회원 번호
	 * @param array $member_info 회원 정보
	 * @return array 초기 가족 데이터
	 */
	public function create_initial_family_data($member_idx, $member_info) {
		$gender = 'M';
		if (isset($member_info['member_sex'])) {
			$gender = ($member_info['member_sex'] === 'female') ? 'F' : 'M';
		}

		$initial_data = [
			[
				'id' => '0',
				'data' => [
					'first name' => $member_info['member_name'] ?? '',
					'last name' => '',
					'birthday' => $member_info['member_birth'] ?? '',
					'avatar' => $member_info['photo'] ?? '',
					'gender' => $gender,
					'member_idx' => $member_idx
				],
				'rels' => [
					'spouses' => [],
					'children' => [],
					'parents' => []
				]
			]
		];

		return $initial_data;
	}

	/**
	 * 회원 검색 (이름 또는 전화번호)
	 */
	public function search_members($org_id, $keyword)
	{
		$this->db->select('member_idx, member_name, member_phone');
		$this->db->from('wb_member');
		$this->db->where('org_id', $org_id);
		$this->db->where('del_yn', 'N');

		if ($keyword) {
			$this->db->group_start();
			$this->db->like('member_name', $keyword);
			$this->db->or_like('member_phone', $keyword);
			$this->db->group_end();
		}


		$this->db->order_by('member_name', 'ASC');

		$query = $this->db->get();
		return $query->result_array();
	}
}
