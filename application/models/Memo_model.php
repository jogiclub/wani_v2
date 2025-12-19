<?php
class Memo_model extends CI_Model {
	public function __construct() {
		parent::__construct();
		$this->load->database();
	}



	/**
	 * 파일 위치: application/models/Memo_model.php
	 * 역할: 회원의 메모 목록 조회 (페이징, 삭제된 메모 제외)
	 */
	public function get_memo_list($member_idx, $limit = 10, $offset = 0)
	{
		$this->db->select('
		m.idx,
		m.memo_type,
		m.memo_content,
		m.att_date,
		m.regi_date,
		m.modi_date,
		u.user_name
	');
		$this->db->from('wb_memo m');
		$this->db->join('wb_user u', 'm.user_id = u.user_id', 'left');
		$this->db->where('m.member_idx', $member_idx);
		$this->db->where('m.del_yn', 'N');  // 삭제되지 않은 메모만 조회
		$this->db->order_by('m.regi_date', 'DESC');
		$this->db->limit($limit, $offset);

		$query = $this->db->get();
		return $query->result_array();
	}


	public function delete_memo($memo_idx)
	{
		$data = array(
			'del_yn' => 'Y',
			'modi_date' => date('Y-m-d H:i:s')
		);

		$this->db->where('idx', $memo_idx);
		return $this->db->update('wb_memo', $data);
	}


	public function get_memo_counts($org_id, $start_date, $end_date) {
		$this->db->select('m.member_idx, COUNT(memo.idx) AS memo_count');
		$this->db->from('wb_member m');
		$this->db->join('wb_memo memo', 'm.member_idx = memo.member_idx AND memo.regi_date >= "' . $start_date . '" AND memo.regi_date <= "' . $end_date . '"', 'left');
		$this->db->where('m.org_id', $org_id);
		$this->db->group_by('m.member_idx');

		$query = $this->db->get();
//        print_r($this->db->last_query());
//        exit;
		$result = $query->result_array();



		$memo_counts = array();
		foreach ($result as $row) {
			$memo_counts[$row['member_idx']] = $row['memo_count'];
		}

		return $memo_counts;
	}





	/**
	 * 역할: 특정 메모 정보 가져오기
	 */
	public function get_memo_by_idx($idx)
	{
		$this->db->select('*');
		$this->db->from('wb_memo');
		$this->db->where('idx', $idx);

		$query = $this->db->get();
		return $query->row_array();
	}




	/**
	 * att_idx로 메모 조회 (기존 호환성 유지)
	 */
	public function get_memo_by_att_idx($att_idx)
	{
		$this->db->select('*');
		$this->db->from('wb_memo');
		$this->db->where('att_idx', $att_idx);
		$this->db->where('del_yn', 'N');
		$this->db->order_by('regi_date', 'DESC');
		$this->db->limit(1);

		$query = $this->db->get();
		return $query->row_array();
	}

	/**
	 * 특정 주차의 회원들 메모 일괄 조회 (att_idx 기준)
	 */
	public function get_attendance_memo_by_week($member_indices, $sunday_date)
	{
		if (empty($member_indices)) {
			return array();
		}

		// 해당 주의 날짜 범위 계산
		$start_date = $sunday_date;
		$end_date = date('Y-m-d', strtotime($sunday_date . ' +6 days'));

		$this->db->select('m.idx, m.memo_content, m.member_idx, m.att_idx, ma.att_date');
		$this->db->from('wb_memo m');
		$this->db->join('wb_member_att ma', 'm.att_idx = ma.att_idx', 'inner');
		$this->db->where_in('m.member_idx', $member_indices);
		$this->db->where('ma.att_date >=', $start_date);
		$this->db->where('ma.att_date <=', $end_date);
		$this->db->where('m.att_idx IS NOT NULL');

		$query = $this->db->get();
		$results = $query->result_array();

		// 회원별로 그룹핑하여 반환
		$memo_by_member = array();
		foreach ($results as $row) {
			$member_idx = $row['member_idx'];
			if (!isset($memo_by_member[$member_idx])) {
				$memo_by_member[$member_idx] = array(
					'memo_content' => $row['memo_content'],
					'att_idx' => $row['att_idx'],
					'att_date' => $row['att_date']
				);
			}
		}

		return $memo_by_member;
	}

	/**
	 * att_idx 기준으로 메모 삭제
	 */
	public function delete_memo_by_att_idx($att_idx)
	{
		$this->db->where('att_idx', $att_idx);
		$this->db->delete('wb_memo');
		return $this->db->affected_rows() > 0;
	}

	/**
	 * 회원의 출석 관련 메모 목록 조회 (페이징)
	 */
	public function get_attendance_memo_list($member_idx, $limit, $offset)
	{
		$this->db->select('m.*, u.user_name, ma.att_date');
		$this->db->from('wb_memo m');
		$this->db->join('wb_user u', 'm.user_id = u.user_id', 'left');
		$this->db->join('wb_member_att ma', 'm.att_idx = ma.att_idx', 'left');
		$this->db->where('m.member_idx', $member_idx);
		$this->db->where('m.att_idx IS NOT NULL');
		$this->db->order_by('m.regi_date', 'DESC');
		$this->db->limit($limit, $offset);

		$query = $this->db->get();
		return $query->result_array();
	}


	public function save_attendance_memo($data)
	{
		// att_idx가 필수
		if (!isset($data['att_idx']) || !$data['att_idx']) {
			return false;
		}

		$this->db->insert('wb_memo', $data);
		return $this->db->affected_rows() > 0;
	}

	/**
	 * att_idx 기준으로 메모 수정
	 */
	public function update_attendance_memo_by_att_idx($att_idx, $data)
	{
		$this->db->where('att_idx', $att_idx);
		$this->db->update('wb_memo', $data);
		return $this->db->affected_rows() > 0;
	}



	/**
	 * 특정 회원의 특정 날짜 메모 조회 (기존 메소드와 동일하지만 명시적 추가)
	 */
	public function get_member_memo_by_date($member_idx, $att_date)
	{
		$this->db->select('*');
		$this->db->from('wb_memo');
		$this->db->where('member_idx', $member_idx);
		$this->db->where('att_date', $att_date);
		$this->db->where('del_yn', 'N');
		$this->db->order_by('regi_date', 'DESC');
		$this->db->limit(1);

		$query = $this->db->get();
		return $query->row_array();
	}

	/**
	 * 역할: 특정 회원의 특정 주차 메모 조회 (att_date 기준)
	 */
	public function get_member_memos_by_week($member_idx, $start_date, $end_date)
	{
		$this->db->select('*');
		$this->db->from('wb_memo');
		$this->db->where('member_idx', $member_idx);
		$this->db->where('att_date >=', $start_date);
		$this->db->where('att_date <=', $end_date);
		$this->db->where('del_yn', 'N');
		$this->db->order_by('att_date', 'ASC');

		$query = $this->db->get();
		return $query->result_array();
	}

	/**
	 * 역할: 특정 그룹의 특정 주차 메모 조회 (att_date 기준)
	 */
	public function get_area_memos_by_week($area_idx, $start_date, $end_date)
	{
		$this->db->select('m.*, mem.member_name, mem.area_idx');
		$this->db->from('wb_memo m');
		$this->db->join('wb_member mem', 'm.member_idx = mem.member_idx');
		$this->db->where('mem.area_idx', $area_idx);
		$this->db->where('m.att_date >=', $start_date);
		$this->db->where('m.att_date <=', $end_date);
		$this->db->where('m.del_yn', 'N');
		$this->db->where('mem.del_yn', 'N');
		$this->db->order_by('m.att_date', 'ASC');
		$this->db->order_by('mem.member_name', 'ASC');

		$query = $this->db->get();
		return $query->result_array();
	}

	/**
	 * 역할: 메모 수정 시 att_date 포함
	 */
	public function update_memo($memo_idx, $data)
	{
		$this->db->where('idx', $memo_idx);
		return $this->db->update('wb_memo', $data);
	}

	/**
	 * 역할: 메모 저장 시 att_date 포함
	 */
	public function save_memo($data)
	{
		return $this->db->insert('wb_memo', $data);
	}

	/**
	 * 여러 회원의 특정 날짜 메모 조회 (출석관리 화면용)
	 */
	public function get_members_memo_by_date($member_indices, $att_date)
	{
		if (empty($member_indices)) {
			return array();
		}

		$this->db->select('member_idx, memo_content, att_idx, idx');
		$this->db->from('wb_memo');
		$this->db->where_in('member_idx', $member_indices);
		$this->db->where('att_date', $att_date);
		$this->db->where('del_yn', 'N');
		$this->db->order_by('member_idx', 'ASC');

		$query = $this->db->get();
		$results = $query->result_array();

		// member_idx를 키로 하는 배열로 재구성
		$memo_records = array();
		foreach ($results as $row) {
			$memo_records[$row['member_idx']] = $row;
		}

		return $memo_records;
	}


	/**
	 * 메모 목록 조회 (필터링 포함)
	 */
	public function get_memos($org_id, $filters = array())
	{
		$this->db->select('
		m.idx,
		m.memo_type,
		m.att_date as memo_date,
		m.memo_content,
		m.regi_date,
		m.modi_date,
		mem.member_name,
		u.user_name as regi_user_name
	');
		$this->db->from('wb_memo m');
		$this->db->join('wb_member mem', 'm.member_idx = mem.member_idx', 'left');
		$this->db->join('wb_user u', 'm.user_id = u.user_id', 'left');
		$this->db->where('mem.org_id', $org_id);
		$this->db->where('m.del_yn', 'N');

		// 메모 타입 필터
		if (!empty($filters['memo_types']) && is_array($filters['memo_types'])) {
			$this->db->where_in('m.memo_type', $filters['memo_types']);
		}

		// 검색어 필터
		if (!empty($filters['search_text'])) {
			$this->db->group_start();
			$this->db->like('mem.member_name', $filters['search_text']);
			$this->db->or_like('m.memo_content', $filters['search_text']);
			$this->db->group_end();
		}

		// 연/월 필터 (등록일 기준)
		if (!empty($filters['year']) && !empty($filters['month'])) {
			$year = intval($filters['year']);
			$month = intval($filters['month']);

			$start_date = sprintf('%04d-%02d-01 00:00:00', $year, $month);
			$last_day = date('t', strtotime($start_date));
			$end_date = sprintf('%04d-%02d-%02d 23:59:59', $year, $month, $last_day);

			$this->db->where('m.regi_date >=', $start_date);
			$this->db->where('m.regi_date <=', $end_date);
		}

		$this->db->order_by('m.att_date', 'DESC');
		$this->db->order_by('m.regi_date', 'DESC');

		$query = $this->db->get();
		return $query->result_array();
	}

	/**
	 * 메모 개수 조회
	 */
	public function get_memos_count($org_id, $filters = array())
	{
		$this->db->select('COUNT(*) as count');
		$this->db->from('wb_memo m');
		$this->db->join('wb_member mem', 'm.member_idx = mem.member_idx', 'left');
		$this->db->where('mem.org_id', $org_id);
		$this->db->where('m.del_yn', 'N');

		// 메모 타입 필터
		if (!empty($filters['memo_types']) && is_array($filters['memo_types'])) {
			$this->db->where_in('m.memo_type', $filters['memo_types']);
		}

		// 검색어 필터
		if (!empty($filters['search_text'])) {
			$this->db->group_start();
			$this->db->like('mem.member_name', $filters['search_text']);
			$this->db->or_like('m.memo_content', $filters['search_text']);
			$this->db->group_end();
		}

		// 연/월 필터 (등록일 기준)
		if (!empty($filters['year']) && !empty($filters['month'])) {
			$year = intval($filters['year']);
			$month = intval($filters['month']);

			$start_date = sprintf('%04d-%02d-01 00:00:00', $year, $month);
			$last_day = date('t', strtotime($start_date));
			$end_date = sprintf('%04d-%02d-%02d 23:59:59', $year, $month, $last_day);

			$this->db->where('m.regi_date >=', $start_date);
			$this->db->where('m.regi_date <=', $end_date);
		}

		$query = $this->db->get();
		$result = $query->row_array();
		return $result['count'];
	}

	/**
	 * 메모 통계 조회 (최근 3개월간 1회 이상 작성한 회원 수)
	 */
	public function get_memo_statistics($org_id)
	{
		// 3개월 전 날짜 계산
		$three_months_ago = date('Y-m-d', strtotime('-3 months'));

		// 조직의 전체 회원 수 조회
		$this->db->select('COUNT(DISTINCT m.member_idx) as total_members');
		$this->db->from('wb_member m');
		$this->db->where('m.org_id', $org_id);
		$this->db->where('m.del_yn', 'N');
		$query = $this->db->get();
		$total_result = $query->row_array();
		$total_members = $total_result['total_members'];

		// 메모 타입 목록 조회
		$this->load->model('Org_model');
		$memo_types = $this->Org_model->get_memo_types($org_id);

		// 각 메모 타입별로 최근 3개월간 작성한 회원 수 조회
		$statistics = array();

		foreach ($memo_types as $memo_type) {
			$this->db->select('COUNT(DISTINCT memo.member_idx) as member_count');
			$this->db->from('wb_memo memo');
			$this->db->join('wb_member m', 'memo.member_idx = m.member_idx', 'inner');
			$this->db->where('m.org_id', $org_id);
			$this->db->where('memo.memo_type', $memo_type);
			$this->db->where('memo.regi_date >=', $three_months_ago);
			$this->db->where('memo.del_yn', 'N');
			$this->db->where('m.del_yn', 'N');

			$query = $this->db->get();
			$result = $query->row_array();

			$statistics[] = array(
				'memo_type' => $memo_type,
				'member_count' => $result['member_count']
			);
		}

		return array(
			'statistics' => $statistics,
			'total_members' => $total_members,
			'memo_types' => $memo_types
		);
	}

	/**
	 * 메모 일괄추가
	 */
	public function add_memos($member_idxs, $data)
	{
		if (empty($member_idxs) || !is_array($member_idxs)) {
			return false;
		}

		$this->db->trans_start();

		foreach ($member_idxs as $member_idx) {
			$insert_data = array_merge($data, array(
				'member_idx' => $member_idx,
				'del_yn' => 'N',
				'regi_date' => date('Y-m-d H:i:s')
			));
			$this->db->insert('wb_memo', $insert_data);
		}

		$this->db->trans_complete();

		return $this->db->trans_status();
	}

	/**
	 * 메모 삭제 (여러 개)
	 */
	public function delete_memos($idxs)
	{
		if (empty($idxs) || !is_array($idxs)) {
			return false;
		}

		$data = array(
			'del_yn' => 'Y',
			'modi_date' => date('Y-m-d H:i:s')
		);

		$this->db->where_in('idx', $idxs);
		return $this->db->update('wb_memo', $data);
	}

	/**
	 * 메모 상세 조회 (회원명 포함)
	 */
	public function get_memo_detail_by_idx($idx)
	{
		$this->db->select('
		m.*,
		mem.member_name,
		m.att_date as memo_date
	');
		$this->db->from('wb_memo m');
		$this->db->join('wb_member mem', 'm.member_idx = mem.member_idx', 'left');
		$this->db->where('m.idx', $idx);
		$this->db->where('m.del_yn', 'N');

		$query = $this->db->get();
		return $query->row_array();
	}

	/**
	 * 최근 8주간 메모 타입별 통계 조회
	 * @param int $org_id 조직 ID
	 * @return array 주별, 메모 타입별 통계 데이터
	 */
	public function get_weekly_memo_stats_by_type($org_id)
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

		// 메모 타입 목록 조회
		$this->load->model('Org_model');
		$memo_types = $this->Org_model->get_memo_types($org_id);

		// 타입이 없으면 빈 배열 반환
		if (empty($memo_types)) {
			log_message('debug', 'No memo types found for org_id: ' . $org_id);
			return array(
				'weekly_data' => array(),
				'memo_types' => array()
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

			// 각 메모 타입별 통계 조회
			foreach ($memo_types as $memo_type) {
				$this->db->select('COUNT(*) as count');
				$this->db->from('wb_memo m');
				$this->db->join('wb_member mem', 'm.member_idx = mem.member_idx', 'inner');
				$this->db->where('mem.org_id', $org_id);
				$this->db->where('mem.del_yn', 'N');
				$this->db->where('m.del_yn', 'N');
				$this->db->where('m.memo_type', $memo_type);
				$this->db->where('m.regi_date >=', $start_date);
				$this->db->where('m.regi_date <=', $end_date);

				$query = $this->db->get();
				$result = $query->row_array();

				$week_stats['types'][$memo_type] = (int)$result['count'];
			}

			$weekly_data[] = $week_stats;
		}

		return array(
			'weekly_data' => $weekly_data,
			'memo_types' => $memo_types
		);
	}




	/**
	 * 월별 주차별 조직별 타입별 메모 통계 조회
	 */
	public function get_monthly_memo_by_orgs_and_types($orgs, $year, $month)
	{
		if (empty($orgs)) {
			return array(
				'orgs' => array(),
				'weekly_data' => array(),
				'memo_types' => array()
			);
		}

		$org_ids = array_column($orgs, 'org_id');

		$this->load->model('Org_model');
		$memo_types_raw = $this->Org_model->get_all_memo_types_by_orgs($org_ids);

		$memo_types = array();
		if (!empty($memo_types_raw)) {
			foreach ($memo_types_raw as $type) {
				if (is_array($type)) {
					if (isset($type['memo_type'])) {
						$memo_types[] = $type['memo_type'];
					}
				} else {
					$memo_types[] = $type;
				}
			}
		}

		$memo_types = array_unique($memo_types);

		$this->load->model('Member_model');
		$sundays = $this->Member_model->get_sundays_in_month($year, $month);

		$weekly_data = array();

		foreach ($sundays as $sunday_info) {
			$sunday_date = $sunday_info['sunday_date'];

			$this->db->select("org_id, memo_type, memo_count");
			$this->db->from('wb_memo_weekly_type_stats');
			$this->db->where_in('org_id', $org_ids);
			$this->db->where('att_year', $year);
			$this->db->where('sunday_date', $sunday_date);

			$query = $this->db->get();
			$results = $query->result_array();

			$org_type_data_map = array();
			foreach ($results as $row) {
				$key = $row['org_id'] . '_' . $row['memo_type'];
				$org_type_data_map[$key] = (int)$row['memo_count'];
			}

			$week_data = array(
				'week_label' => $sunday_info['label'],
				'orgs' => array()
			);

			foreach ($orgs as $org) {
				$org_id = $org['org_id'];
				$week_data['orgs'][$org_id] = array();

				foreach ($memo_types as $memo_type) {
					$type_name = is_string($memo_type) ? $memo_type : (string)$memo_type;
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
			'memo_types' => $memo_types
		);
	}
}
