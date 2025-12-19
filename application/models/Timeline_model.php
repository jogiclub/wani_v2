<?php
/**
 * 역할: 회원 타임라인 데이터 관리 모델
 */

class Timeline_model extends CI_Model {

	public function __construct() {
		parent::__construct();
		$this->load->database();
	}

	/**
	 * 회원 타임라인 목록 조회
	 */
	public function get_member_timeline($member_idx, $limit = 20, $offset = 0) {
		$this->db->select('idx, member_idx, timeline_type, timeline_date, timeline_content, regi_date, modi_date, user_id');
		$this->db->from('wb_member_timeline');
		$this->db->where('member_idx', $member_idx);
		$this->db->order_by('timeline_date', 'DESC');
		$this->db->order_by('regi_date', 'DESC');
		$this->db->limit($limit, $offset);

		$query = $this->db->get();
		return $query->result_array();
	}

	/**
	 * 타임라인 항목 저장
	 */
	public function save_timeline($data) {
		$this->db->insert('wb_member_timeline', $data);
		return $this->db->affected_rows() > 0;
	}


	/**
	 * 타임라인 항목 삭제
	 */
	public function delete_timeline($idx) {
		$this->db->where('idx', $idx);
		$this->db->delete('wb_member_timeline');
		return $this->db->affected_rows() > 0;
	}



	/**
	 * 회원의 타임라인 개수 조회
	 */
	public function get_member_timeline_count($member_idx) {
		$this->db->from('wb_member_timeline');
		$this->db->where('member_idx', $member_idx);
		return $this->db->count_all_results();
	}

	/**
	 * 특정 기간의 타임라인 조회
	 */
	public function get_timeline_by_period($member_idx, $start_date, $end_date) {
		$this->db->select('idx, member_idx, timeline_type, timeline_date, timeline_content, regi_date, modi_date, user_id');
		$this->db->from('wb_member_timeline');
		$this->db->where('member_idx', $member_idx);
		$this->db->where('timeline_date >=', $start_date);
		$this->db->where('timeline_date <=', $end_date);
		$this->db->order_by('timeline_date', 'DESC');

		$query = $this->db->get();
		return $query->result_array();
	}



	/**
	 * 타임라인 개수 조회
	 */
	public function get_timelines_count($org_id, $filters = array())
	{
		$this->db->select('COUNT(*) as count');
		$this->db->from('wb_member_timeline t');
		$this->db->join('wb_member m', 't.member_idx = m.member_idx', 'left');
		$this->db->where('m.org_id', $org_id);

		// 타임라인 타입 필터 (여러 개 선택 가능)
		if (!empty($filters['timeline_types']) && is_array($filters['timeline_types'])) {
			$this->db->where_in('t.timeline_type', $filters['timeline_types']);
		}

		// 검색어 필터
		if (!empty($filters['search_text'])) {
			$this->db->group_start();
			$this->db->like('m.member_name', $filters['search_text']);
			$this->db->or_like('t.timeline_content', $filters['search_text']);
			$this->db->group_end();
		}

		// 년/월 필터 (등록일 기준)
		if (!empty($filters['year']) && !empty($filters['month'])) {
			$year = $filters['year'];
			$month = str_pad($filters['month'], 2, '0', STR_PAD_LEFT);
			$this->db->where("DATE_FORMAT(t.regi_date, '%Y-%m') =", "{$year}-{$month}");
		}

		$query = $this->db->get();
		$result = $query->row_array();
		return $result['count'];
	}


	/**
	 * 타임라인 일괄추가 (여러 회원)
	 */
	public function add_timelines($member_idxs, $data)
	{
		$this->db->trans_start();

		foreach ($member_idxs as $member_idx) {
			$insert_data = array_merge(array('member_idx' => $member_idx), $data);
			$this->db->insert('wb_member_timeline', $insert_data);
		}

		$this->db->trans_complete();

		return $this->db->trans_status();
	}

	/**
	 * 타임라인 수정
	 */
	public function update_timeline($idx, $data)
	{
		$this->db->where('idx', $idx);
		return $this->db->update('wb_member_timeline', $data);
	}

	/**
	 * 타임라인 삭제
	 */
	public function delete_timelines($idxs)
	{
		$this->db->where_in('idx', $idxs);
		return $this->db->delete('wb_member_timeline');
	}

	/**
	 * 타임라인 상세 조회
	 */
	public function get_timeline_by_idx($idx)
	{
		$this->db->select('
			t.*,
			m.member_name,
			u.user_name as regi_user_name
		');
		$this->db->from('wb_member_timeline t');
		$this->db->join('wb_member m', 't.member_idx = m.member_idx', 'left');
		$this->db->join('wb_user u', 't.user_id = u.user_id', 'left');
		$this->db->where('t.idx', $idx);

		$query = $this->db->get();
		return $query->row_array();
	}

	/**
	 * 타임라인 목록 조회
	 */
	public function get_timelines($org_id, $filters = array())
	{
		$this->db->select('
		t.idx,
		t.timeline_type,
		t.timeline_date,
		t.timeline_content,
		t.regi_date,
		t.modi_date,
		m.member_name,
		u.user_name as regi_user_name
	');
		$this->db->from('wb_member_timeline t');
		$this->db->join('wb_member m', 't.member_idx = m.member_idx', 'left');
		$this->db->join('wb_user u', 't.user_id = u.user_id', 'left');
		$this->db->where('m.org_id', $org_id);

		// 타임라인 타입 필터 (여러 개 선택 가능)
		if (!empty($filters['timeline_types']) && is_array($filters['timeline_types'])) {
			$this->db->where_in('t.timeline_type', $filters['timeline_types']);
		}

		// 검색어 필터
		if (!empty($filters['search_text'])) {
			$this->db->group_start();
			$this->db->like('m.member_name', $filters['search_text']);
			$this->db->or_like('t.timeline_content', $filters['search_text']);
			$this->db->group_end();
		}

		// 년/월 필터 (등록일 기준)
		if (!empty($filters['year']) && !empty($filters['month'])) {
			$year = $filters['year'];
			$month = str_pad($filters['month'], 2, '0', STR_PAD_LEFT);
			$this->db->where("DATE_FORMAT(t.regi_date, '%Y-%m') =", "{$year}-{$month}");
		}

		$this->db->order_by('t.timeline_date', 'DESC');
		$this->db->order_by('t.regi_date', 'DESC');

		$query = $this->db->get();
		return $query->result_array();
	}

	/**
	 * 타임라인 타입별 통계 조회 (전체 데이터 기준)
	 */
	public function get_timeline_statistics($org_id)
	{
		$this->db->select('t.timeline_type, COUNT(DISTINCT t.member_idx) as member_count');
		$this->db->from('wb_member_timeline t');
		$this->db->join('wb_member m', 't.member_idx = m.member_idx', 'left');
		$this->db->where('m.org_id', $org_id);
		$this->db->where('m.del_yn', 'N');

		$this->db->group_by('t.timeline_type');
		$query = $this->db->get();

		return $query->result_array();
	}

	/**
	 * 조직의 전체 회원 수 조회 (타임라인 통계용)
	 */
	public function get_org_total_member_count($org_id)
	{
		$this->db->from('wb_member');
		$this->db->where('org_id', $org_id);
		$this->db->where('del_yn', 'N');

		return $this->db->count_all_results();
	}

	/**
	 * 파일 위치: application/models/Timeline_model.php
	 * 역할: 진급 대상자 조회 (미분류 제외)
	 */
	public function get_upcoming_promotion_members($org_id, $promotion_types, $days = 7)
	{
		if (empty($promotion_types)) {
			return array();
		}

		// 오늘부터 N일 후까지
		$start_date = date('Y-m-d');
		$end_date = date('Y-m-d', strtotime("+{$days} days"));

		$this->db->select('
        t.idx,
        t.member_idx,
        t.timeline_type,
        t.timeline_date,
        m.member_name,
        a.area_name
    ');
		$this->db->from('wb_member_timeline t');
		$this->db->join('wb_member m', 't.member_idx = m.member_idx');
		$this->db->join('wb_member_area a', 'm.area_idx = a.area_idx', 'left');
		$this->db->where('m.org_id', $org_id);
		$this->db->where('m.del_yn', 'N');
		$this->db->where_in('t.timeline_type', $promotion_types);
		$this->db->where('t.timeline_date >=', $start_date);
		$this->db->where('t.timeline_date <=', $end_date);
		// 미분류 그룹 제외
		$this->db->where('(a.area_name IS NULL OR a.area_name != "미분류")');
		$this->db->order_by('t.timeline_date', 'ASC');

		$query = $this->db->get();
		return $query->result_array();
	}

	/**
	 * 특정 회원들의 특정 타임라인 타입 존재 여부 확인
	 */
	public function check_timeline_exists($member_idxs, $timeline_types)
	{
		if (empty($member_idxs) || empty($timeline_types)) {
			return false;
		}

		$this->db->select('COUNT(*) as count');
		$this->db->from('wb_member_timeline');
		$this->db->where_in('member_idx', $member_idxs);
		$this->db->where_in('timeline_type', $timeline_types);

		$query = $this->db->get();
		$result = $query->row_array();

		return isset($result['count']) && $result['count'] > 0;
	}


	/**
	 * 최근 8주간 타임라인 타입별 통계 조회
	 * @param int $org_id 조직 ID
	 * @return array 주별, 타임라인 타입별 통계 데이터
	 */
	public function get_weekly_timeline_stats_by_type($org_id)
	{
		$weekly_data = array();

		// 현재 날짜에서 요일 계산
		$today = new DateTime();
		$day_of_week = (int)$today->format('w');

		// 이번 주 일요일 계산
		if ($day_of_week == 0) {
			$current_sunday = clone $today;
		} else {
			$current_sunday = clone $today;
			$current_sunday->sub(new DateInterval('P' . $day_of_week . 'D'));
		}

		// 타임라인 타입 목록 조회
		$this->load->model('Org_model');
		$timeline_types = $this->Org_model->get_timeline_types($org_id);

		// 타입이 없으면 빈 배열 반환
		if (empty($timeline_types)) {
			log_message('debug', 'No timeline types found for org_id: ' . $org_id);
			return array(
				'weekly_data' => array(),
				'timeline_types' => array()
			);
		}

		// 최근 8주 데이터 조회
		for ($i = 7; $i >= 0; $i--) {
			$week_sunday = clone $current_sunday;
			$week_sunday->sub(new DateInterval('P' . ($i * 7) . 'D'));

			$week_saturday = clone $week_sunday;
			$week_saturday->add(new DateInterval('P6D'));

			$start_date = $week_sunday->format('Y-m-d') . ' 00:00:00';
			$end_date = $week_saturday->format('Y-m-d') . ' 23:59:59';
			$week_label = $week_sunday->format('n/j');

			$week_stats = array(
				'week_label' => $week_label,
				'types' => array()
			);

			// 각 타임라인 타입별 통계 조회
			foreach ($timeline_types as $timeline_type) {
				$this->db->select('COUNT(*) as count');
				$this->db->from('wb_member_timeline t');
				$this->db->join('wb_member m', 't.member_idx = m.member_idx', 'inner');
				$this->db->where('m.org_id', $org_id);
				$this->db->where('m.del_yn', 'N');
				$this->db->where('t.timeline_type', $timeline_type);
				$this->db->where('t.regi_date >=', $start_date);
				$this->db->where('t.regi_date <=', $end_date);

				$query = $this->db->get();
				$result = $query->row_array();

				$week_stats['types'][$timeline_type] = (int)$result['count'];
			}

			$weekly_data[] = $week_stats;
		}

		return array(
			'weekly_data' => $weekly_data,
			'timeline_types' => $timeline_types
		);
	}

	/**
	 * 월별 주차별 조직별 타입별 타임라인 통계 조회
	 */
	public function get_monthly_timeline_by_orgs_and_types($orgs, $year, $month)
	{
		if (empty($orgs)) {
			return array(
				'orgs' => array(),
				'weekly_data' => array(),
				'timeline_types' => array()
			);
		}

		$org_ids = array_column($orgs, 'org_id');

		$this->load->model('Org_model');
		$timeline_types_raw = $this->Org_model->get_all_timeline_types_by_orgs($org_ids);

		$timeline_types = array();
		if (!empty($timeline_types_raw)) {
			foreach ($timeline_types_raw as $type) {
				if (is_array($type)) {
					if (isset($type['timeline_type'])) {
						$timeline_types[] = $type['timeline_type'];
					}
				} else {
					$timeline_types[] = $type;
				}
			}
		}

		$timeline_types = array_unique($timeline_types);

		$this->load->model('Member_model');
		$sundays = $this->Member_model->get_sundays_in_month($year, $month);

		$weekly_data = array();

		foreach ($sundays as $sunday_info) {
			$sunday_date = $sunday_info['sunday_date'];

			$this->db->select("org_id, timeline_type, timeline_count");
			$this->db->from('wb_timeline_weekly_type_stats');
			$this->db->where_in('org_id', $org_ids);
			$this->db->where('att_year', $year);
			$this->db->where('sunday_date', $sunday_date);

			$query = $this->db->get();
			$results = $query->result_array();

			$org_type_data_map = array();
			foreach ($results as $row) {
				$key = $row['org_id'] . '_' . $row['timeline_type'];
				$org_type_data_map[$key] = (int)$row['timeline_count'];
			}

			$week_data = array(
				'week_label' => $sunday_info['label'],
				'orgs' => array()
			);

			foreach ($orgs as $org) {
				$org_id = $org['org_id'];
				$week_data['orgs'][$org_id] = array();

				foreach ($timeline_types as $timeline_type) {
					$type_name = is_string($timeline_type) ? $timeline_type : (string)$timeline_type;
					$key = $org_id . '_' . $type_name;
					$week_data['orgs'][$org_id][$type_name] = isset($org_type_data_map[$key])
						? $org_type_data_map[$key]
						: 0;
				}
			}

			$weekly_data[] = $week_data;
		}

		// 조직 정보 간소화
		$simplified_orgs = array();
		foreach ($orgs as $org) {
			$simplified_orgs[] = array(
				'org_id' => $org['org_id'],
				'org_name' => $org['org_name']
			);
		}

		return array(
			'orgs' => $simplified_orgs,
			'weekly_data' => $weekly_data,
			'timeline_types' => $timeline_types
		);
	}

}
