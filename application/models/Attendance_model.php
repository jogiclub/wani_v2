<?php
class Attendance_model extends CI_Model {
    public function __construct() {
        parent::__construct();
        $this->load->database();
    }

	/**
	 * 역할: get_attendance_types 함수에서 att_type_input 필드 추가
	 */

	public function get_attendance_types($org_id) {
		$this->db->select('att_type_idx, att_type_category_name, att_type_category_idx, att_type_nickname, att_type_name, att_type_color, att_type_order, att_type_point, att_type_input');
		$this->db->from('wb_att_type');
		$this->db->where('org_id', $org_id);
		$this->db->order_by('att_type_order', 'ASC');
		$this->db->order_by('att_type_idx', 'ASC');

		$query = $this->db->get();
		return $query->result_array();
	}





	// Attendance_model에 save_batch_attendance 메소드 추가 (기존 클래스 끝에 추가)

	/**
	 * 일괄 출석 정보 저장
	 */
	public function save_batch_attendance($member_attendance_data, $org_id, $start_date, $end_date)
	{
		$this->db->trans_start();

		try {
			// 회원별로 그룹화된 데이터에서 회원 인덱스 추출
			$member_indices = array_keys($member_attendance_data);

			if (!empty($member_indices)) {
				// 해당 회원들의 해당 기간 출석 정보 삭제
				$this->db->where_in('member_idx', $member_indices);
				$this->db->where('org_id', $org_id);
				$this->db->where('att_date >=', $start_date);
				$this->db->where('att_date <=', $end_date);
				$this->db->delete('wb_member_att');
			}

			// 새로운 출석 정보 삽입
			foreach ($member_attendance_data as $member_idx => $attendance_dates) {
				foreach ($attendance_dates as $att_date => $att_type_idx) {
					if (!empty($att_type_idx)) {
						$data = array(
							'member_idx' => $member_idx,
							'att_date' => $att_date,
							'att_type_idx' => $att_type_idx,
							'org_id' => $org_id
						);
						$this->db->insert('wb_member_att', $data);
					}
				}
			}

			$this->db->trans_complete();

			if ($this->db->trans_status() === FALSE) {
				return false;
			}

			return true;

		} catch (Exception $e) {
			$this->db->trans_rollback();
			return false;
		}
	}

    public function save_attendance($member_idx, $attendance_data) {
        $att_date = date('Y-m-d');
        $org_id = $this->session->userdata('org_id');

        foreach ($attendance_data as $att_type_idx) {
            $data = array(
                'att_date' => $att_date,
                'att_type_idx' => $att_type_idx,
                'member_idx' => $member_idx,
                'org_id' => $org_id
            );

            $this->db->insert('wb_member_att', $data);
        }

        return $this->db->affected_rows() > 0;
    }

    public function save_attendance_data($attendance_data, $org_id, $start_date, $end_date) {
        foreach ($attendance_data as $data) {
            $member_idx = $data['member_idx'];
            $att_type_idx = $data['att_type_idx'];

            // 새로운 출석 정보 저장
            $att_data = array(
                'att_date' => $start_date,
                'att_type_idx' => $att_type_idx,
                'member_idx' => $member_idx,
                'org_id' => $org_id
            );
            $this->db->insert('wb_member_att', $att_data);
        }

        return $this->db->affected_rows() > 0;
    }

    public function update_attendance_type($att_type_idx, $att_type_name, $att_type_nickname, $att_type_color)
    {
        $data = array(
            'att_type_name' => $att_type_name,
            'att_type_nickname' => $att_type_nickname,
            'att_type_color' => $att_type_color
        );
        $this->db->where('att_type_idx', $att_type_idx);
        $this->db->update('wb_att_type', $data);
        return $this->db->affected_rows() > 0;
    }

    public function get_max_category_idx($org_id) {
        $this->db->select_max('att_type_category_idx');
        $this->db->where('org_id', $org_id);
        $query = $this->db->get('wb_att_type');
        $result = $query->row_array();
        return $result['att_type_category_idx'] ?? 0;
    }

    public function save_attendance_type($org_id, $att_type_category_name, $att_type_name, $att_type_nickname, $att_type_color, $att_type_category_idx) {
        $data = array(
            'org_id' => $org_id,
            'att_type_category_name' => $att_type_category_name,
            'att_type_name' => $att_type_name,
            'att_type_nickname' => $att_type_nickname,
            'att_type_color' => $att_type_color,
            'att_type_category_idx' => $att_type_category_idx
        );
        $this->db->insert('wb_att_type', $data);
        return $this->db->affected_rows() > 0;
    }


	/**
	 * 역할: 개별 출석 정보 저장
	 */
    public function save_single_attendance($data) {
        $this->db->insert('wb_member_att', $data);
        return $this->db->affected_rows() > 0;
    }
	/**
	 * 역할: 카테고리별 출석 데이터 삭제 - att_year 조건 추가
	 */
	public function delete_attendance_by_category($member_idx, $att_type_category_idx, $att_date, $att_year)
	{
		$this->db->where('member_idx', $member_idx);
		$this->db->where('att_date', $att_date);
		$this->db->where('att_year', $att_year); // att_year 조건 추가

		if (!empty($att_type_category_idx)) {
			$this->db->where('att_type_idx IN (SELECT att_type_idx FROM wb_att_type WHERE att_type_category_idx = ' . $this->db->escape($att_type_category_idx) . ')');
		}

		$this->db->delete('wb_member_att');
		return $this->db->affected_rows() > 0;
	}

	/**
	 * 역할: 특정 날짜 출석 삭제 - att_year 조건 추가
	 */
	public function delete_attendance_by_date($member_idx, $att_date)
	{
		$att_year = date('Y', strtotime($att_date));

		$this->db->where('member_idx', $member_idx);
		$this->db->where('att_date', $att_date);
		$this->db->where('att_year', $att_year); // att_year 조건 추가
		$this->db->delete('wb_member_att');
	}

    public function get_attendance_type_categories($org_id) {
        $this->db->select('att_type_category_idx, att_type_category_name');
        $this->db->from('wb_att_type');
        $this->db->where('org_id', $org_id);
        $this->db->group_by('att_type_category_idx');
        $query = $this->db->get();
//        print_r($this->db->last_query());
//        exit;
        return $query->result_array();
    }

    public function add_attendance_type($data) {
        $this->db->insert('wb_att_type', $data);
        return $this->db->affected_rows() > 0;
    }


    public function add_attendance_type_category($data) {
        $this->db->insert('wb_att_type', $data);
        return $this->db->affected_rows() > 0;
    }


    public function delete_attendance_type($att_type_idx) {
        $this->db->where('att_type_idx', $att_type_idx);
        $this->db->delete('wb_att_type');
        return $this->db->affected_rows() > 0;
    }

/*
    public function get_attendance_type_count($org_id) {
        $this->db->where('org_id', $org_id);
        $this->db->from('wb_att_type');
        return $this->db->count_all_results();
    }
*/

    public function get_week_attendance_sum($org_id, $start_date, $end_date) {
        $this->db->select('att_type_idx, COUNT(*) as sum');
        $this->db->from('wb_member_att');
        $this->db->where('org_id', $org_id);
        $this->db->where('att_date >=', $start_date);
        $this->db->where('att_date <=', $end_date);
        $this->db->group_by('att_type_idx');
        $query = $this->db->get();

        $result = array();
        foreach ($query->result_array() as $row) {
            $att_type_idx = $row['att_type_idx'];
            $result[$att_type_idx] = $row['sum'];
        }

        return $result;
    }

	/**
	 * 역할: 회원 출석 정보 조회 - 일요일 날짜로 조회하도록 수정
	 */
	public function get_member_attendance($member_idx, $start_date, $end_date)
	{
		// 해당 주의 일요일 날짜 계산
		$sunday_date = $this->get_sunday_of_week($start_date);
		$att_year = date('Y', strtotime($sunday_date));

		$this->db->select('att_type_idx, att_date');
		$this->db->from('wb_member_att');
		$this->db->where('member_idx', $member_idx);
		$this->db->where('att_date', $sunday_date); // 일요일 날짜로 조회
		$this->db->where('att_year', $att_year);
		$this->db->order_by('att_date', 'ASC');

		$query = $this->db->get();
		$result = $query->result_array();

		$attendance_data = array();
		foreach ($result as $row) {
			$attendance_data[$row['att_date']][] = strval($row['att_type_idx']);
		}

		return $attendance_data;
	}













	/**
	 * 회원 인덱스 배열로 회원 정보 가져오기
	 */
	public function get_members_by_indices($member_indices)
	{
		$this->db->select('m.member_idx, m.member_name, m.member_nick, m.photo, m.area_idx, a.area_name');
		$this->db->from('wb_member m');
		$this->db->join('wb_member_area a', 'm.area_idx = a.area_idx', 'left');
		$this->db->where_in('m.member_idx', $member_indices);
		$this->db->where('m.del_yn', 'N');
		$this->db->order_by('m.member_name');

		$query = $this->db->get();
		return $query->result_array();
	}
	/**
	 * 특정 날짜가 포함된 주의 일요일 날짜 반환
	 */
	private function get_sunday_of_week($date)
	{
		// 날짜 형식 변환 처리 (2025.08.31 -> 2025-08-31)
		$formatted_date = str_replace('.', '-', $date);

		try {
			$dt = new DateTime($formatted_date);
			$days_from_sunday = $dt->format('w'); // 0=일요일, 1=월요일...

			if ($days_from_sunday > 0) {
				$dt->sub(new DateInterval('P' . $days_from_sunday . 'D'));
			}

			return $dt->format('Y-m-d');
		} catch (Exception $e) {
			log_message('error', 'Date parsing error in get_sunday_of_week: ' . $e->getMessage() . ' for date: ' . $date);
			// 기본값으로 현재 주의 일요일 반환
			$dt = new DateTime();
			$days_from_sunday = $dt->format('w');
			if ($days_from_sunday > 0) {
				$dt->sub(new DateInterval('P' . $days_from_sunday . 'D'));
			}
			return $dt->format('Y-m-d');
		}
	}




	/**
	 * 출석 유형별 점수 가져오기
	 */
	public function get_attendance_type_score($att_type_idx)
	{
		// 기본 점수 설정 (실제로는 wb_att_type 테이블에 점수 필드가 있어야 함)
		$scores = array(
			'40' => 10,   // 주일 10점
			'444' => 10,  // 온라인 10점
			// 추가 출석 유형별 점수 설정
		);

		return isset($scores[$att_type_idx]) ? $scores[$att_type_idx] : 1;
	}

	/**
	 * 특정 회원의 특정 날짜 출석 기록 삭제
	 */
	public function delete_member_date_attendance($member_idx, $att_date, $org_id)
	{
		$this->db->where('member_idx', $member_idx);
		$this->db->where('att_date', $att_date);
		$this->db->where('org_id', $org_id);
		$result = $this->db->delete('wb_member_att');

		return $this->db->affected_rows() > 0;
	}




	/**
	 * 연간 출석 통계 업데이트
	 */
	public function update_yearly_attendance_stats($org_id, $member_idx, $att_year)
	{
		// 해당 회원의 해당 연도 전체 출석 횟수 계산
		$this->db->select('SUM(attendance_count) as total_count');
		$this->db->from('wb_attendance_weekly_stats');
		$this->db->where('org_id', $org_id);
		$this->db->where('member_idx', $member_idx);
		$this->db->where('att_year', $att_year);

		$query = $this->db->get();
		$result = $query->row_array();
		$total_count = intval($result['total_count'] ?? 0);

		// 연간 통계 테이블 업데이트
		$yearly_data = array(
			'org_id' => $org_id,
			'member_idx' => $member_idx,
			'att_year' => $att_year,
			'total_attendance_count' => $total_count
		);

		$this->db->replace('wb_attendance_yearly_stats', $yearly_data);

		return $total_count;
	}



	/**
	 * 연간 합계 데이터 빠른 조회
	 */
	public function get_yearly_attendance_totals($org_id, $member_indices, $year)
	{
		if (empty($member_indices)) {
			return array();
		}

		$this->db->select('member_idx, total_attendance_count');
		$this->db->from('wb_attendance_yearly_stats');
		$this->db->where('org_id', $org_id);
		$this->db->where('att_year', $year);
		$this->db->where_in('member_idx', $member_indices);

		$query = $this->db->get();
		$results = $query->result_array();

		$totals = array();
		foreach ($results as $row) {
			$totals[$row['member_idx']] = intval($row['total_attendance_count']);
		}

		// 누락된 회원은 0으로 설정
		foreach ($member_indices as $member_idx) {
			if (!isset($totals[$member_idx])) {
				$totals[$member_idx] = 0;
			}
		}

		return $totals;
	}



	/**
	 * 특정 회원들의 출석 통계 재계산
	 */
	public function rebuild_attendance_stats_for_members($org_id, $year, $member_indices)
	{
		if (empty($member_indices)) {
			return false;
		}

		$this->db->trans_start();

		try {
			// 해당 연도의 일요일 날짜들 생성
			$sunday_dates = $this->get_sunday_dates_for_year($year);

			// 해당 회원들의 기존 주별 통계만 삭제 (중요: WHERE 조건 순서 변경)
			$this->db->where_in('member_idx', $member_indices);
			$this->db->where('org_id', $org_id);
			$this->db->where('att_year', $year);
			$this->db->delete('wb_attendance_weekly_stats');

			// 해당 회원들의 기존 연간 통계만 삭제
			$this->db->where_in('member_idx', $member_indices);
			$this->db->where('org_id', $org_id);
			$this->db->where('att_year', $year);
			$this->db->delete('wb_attendance_yearly_stats');

			// 로그로 삭제된 회원 확인
			log_message('debug', 'Rebuilding stats for members: ' . implode(',', $member_indices) . ' in org: ' . $org_id . ' year: ' . $year);

			// 각 회원, 각 주차별로 통계 생성
			$total_updated = 0;
			foreach ($member_indices as $member_idx) {
				foreach ($sunday_dates as $sunday_date) {
					$count = $this->update_weekly_attendance_stats($org_id, $member_idx, $year, $sunday_date);
					$total_updated++;
				}
			}

			$this->db->trans_complete();

			if ($this->db->trans_status() === FALSE) {
				return false;
			}

			log_message('debug', 'Stats rebuild completed. Total records updated: ' . $total_updated);
			return true;

		} catch (Exception $e) {
			$this->db->trans_rollback();
			log_message('error', 'Rebuild stats for members error: ' . $e->getMessage());
			return false;
		}
	}

	/**
	 * 특정 조직의 특정 연도 통계 일괄 재생성
	 */
	public function rebuild_attendance_stats($org_id, $year)
	{
		// 해당 연도의 일요일 날짜들 생성
		$sunday_dates = $this->get_sunday_dates_for_year($year);

		// 해당 조직의 모든 회원 조회
		$this->db->select('member_idx');
		$this->db->from('wb_member');
		$this->db->where('org_id', $org_id);
		$this->db->where('del_yn', 'N');
		$query = $this->db->get();
		$members = $query->result_array();

		// 기존 통계 삭제
		$this->db->where('org_id', $org_id);
		$this->db->where('att_year', $year);
		$this->db->delete('wb_attendance_weekly_stats');

		$this->db->where('org_id', $org_id);
		$this->db->where('att_year', $year);
		$this->db->delete('wb_attendance_yearly_stats');

		// 각 회원, 각 주차별로 통계 생성
		foreach ($members as $member) {
			$member_idx = $member['member_idx'];

			foreach ($sunday_dates as $sunday_date) {
				$this->update_weekly_attendance_stats($org_id, $member_idx, $year, $sunday_date);
			}
		}

		return true;
	}

	/**
	 * 특정 연도의 모든 일요일 날짜 반환
	 */
	private function get_sunday_dates_for_year($year)
	{
		$sunday_dates = array();
		$date = new DateTime($year . '-01-01');

		// 첫 번째 일요일로 이동
		while ($date->format('w') != 0) {
			$date->add(new DateInterval('P1D'));
		}

		// 해당 연도의 모든 일요일 수집
		while ($date->format('Y') == $year) {
			$sunday_dates[] = $date->format('Y-m-d');
			$date->add(new DateInterval('P7D'));
		}

		return $sunday_dates;
	}


	/**
	 * 파일 위치: application/models/Attendance_model.php
	 * 역할: 출석 데이터 일괄 저장 - att_date를 일요일로 저장하고 att_year, regi_date, modi_date 추가
	 */
	public function save_attendance_batch($org_id, $attendance_data)
	{
		$this->db->trans_start();

		try {
			$affected_stats = array(); // 영향받는 통계 정보 수집

			foreach ($attendance_data as $data) {
				$member_idx = $data['member_idx'];
				$att_date = $data['att_date'];
				$att_type_indices = $data['att_type_indices'];

				// 해당 날짜의 일요일 계산
				$sunday_date = $this->get_sunday_of_week($att_date);
				$att_year = date('Y', strtotime($sunday_date));

				// 해당 회원의 해당 일요일 날짜 출석기록 삭제
				$this->db->where('member_idx', $member_idx);
				$this->db->where('att_date', $sunday_date);
				$this->db->where('org_id', $org_id);
				$this->db->where('att_year', $att_year);
				$this->db->delete('wb_member_att');

				// 새로운 출석기록 삽입
				if (!empty($att_type_indices)) {
					foreach ($att_type_indices as $att_type_idx) {
						if (!empty($att_type_idx)) {
							$insert_data = array(
								'member_idx' => $member_idx,
								'att_date' => $sunday_date, // 일요일 날짜로 저장
								'att_type_idx' => $att_type_idx,
								'org_id' => $org_id,
								'att_year' => $att_year, // att_year 추가
								'regi_date' => date('Y-m-d H:i:s'), // 등록일 추가
								'modi_date' => date('Y-m-d') // 변경일 추가
							);
							$this->db->insert('wb_member_att', $insert_data);
						}
					}
				}

				// 통계 업데이트 대상 수집
				$affected_stats[] = array(
					'org_id' => $org_id,
					'member_idx' => $member_idx,
					'att_year' => $att_year,
					'sunday_date' => $sunday_date
				);
			}

			$this->db->trans_complete();

			if ($this->db->trans_status() === FALSE) {
				return false;
			}

			// 트랜잭션 성공 후 통계 업데이트
			foreach ($affected_stats as $stat) {
				$this->update_weekly_attendance_stats(
					$stat['org_id'],
					$stat['member_idx'],
					$stat['att_year'],
					$stat['sunday_date']
				);
			}

			return true;

		} catch (Exception $e) {
			$this->db->trans_rollback();
			log_message('error', 'Attendance batch save error: ' . $e->getMessage());
			return false;
		}
	}


	/**
	 * 파일 위치: application/models/Attendance_model.php
	 * 역할: 특정 주간의 출석기록 가져오기 - att_date가 일요일 날짜로 변경됨에 따라 조회 로직 수정
	 */
	public function get_week_attendance_records($org_id, $member_indices, $start_date, $end_date, $year)
	{
		// 해당 주의 일요일 날짜 계산
		$sunday_date = $this->get_sunday_of_week($start_date);

		$this->db->select('ma.member_idx, ma.att_date, ma.att_type_idx, ma.att_value, at.att_type_name, at.att_type_nickname, at.att_type_color, at.att_type_order, at.att_type_input, at.att_type_point');
		$this->db->from('wb_member_att ma');
		$this->db->join('wb_att_type at', 'ma.att_type_idx = at.att_type_idx', 'left');
		$this->db->where('ma.org_id', $org_id);
		$this->db->where_in('ma.member_idx', $member_indices);
		$this->db->where('ma.att_date', $sunday_date); // 일요일 날짜로 조회
		$this->db->where('ma.att_year', $year);
		$this->db->order_by('ma.member_idx, at.att_type_order');

		$query = $this->db->get();
		return $query->result_array();
	}

	/**
	 * 파일 위치: application/models/Attendance_model.php
	 * 역할: 회원의 특정 주 출석 정보 조회 - 일요일 날짜로 조회
	 */
	public function get_member_week_attendance($member_idx, $org_id, $start_date, $end_date, $year)
	{
		// 해당 주의 일요일 날짜 계산
		$sunday_date = $this->get_sunday_of_week($start_date);

		$this->db->select('COUNT(*) as attendance_count');
		$this->db->from('wb_member_att');
		$this->db->where('member_idx', $member_idx);
		$this->db->where('org_id', $org_id);
		$this->db->where('att_date', $sunday_date); // 일요일 날짜로 조회
		$this->db->where('att_year', $year);

		$query = $this->db->get();
		$result = $query->row_array();

		return array(
			'total_score' => $result['attendance_count'] ?? 0
		);
	}

	/**
	 * 파일 위치: application/models/Attendance_model.php
	 * 역할: 조직의 출석 데이터 조회 - att_date가 일요일 날짜로 변경됨에 따라 조회 로직 수정
	 */
	public function get_org_member_attendance($org_id, $area_idx, $start_date, $end_date)
	{
		// 해당 주의 일요일 날짜 계산
		$sunday_date = $this->get_sunday_of_week($start_date);
		$att_year = date('Y', strtotime($sunday_date));

		$this->db->select('ma.member_idx, GROUP_CONCAT(ma.att_type_idx ORDER BY ma.att_type_idx SEPARATOR ",") AS att_type_idxs', false);
		$this->db->from('wb_member_att ma');
		$this->db->join('wb_member m', 'ma.member_idx = m.member_idx', 'inner');
		$this->db->where('m.org_id', $org_id);
		$this->db->where('m.area_idx', $area_idx);
		$this->db->where('ma.att_date', $sunday_date); // 일요일 날짜로 조회
		$this->db->where('ma.att_year', $att_year);
		$this->db->group_by('ma.member_idx');
		$query = $this->db->get();
		$result = $query->result_array();

		$attendance_data = array();
		foreach ($result as $row) {
			$attendance_data[$row['member_idx']] = explode(',', $row['att_type_idxs']);
		}

		return $attendance_data;
	}

	/**
	 * 파일 위치: application/models/Attendance_model.php
	 * 역할: 조직 출석 데이터 조회 - 일요일 날짜로 조회
	 */
	public function get_org_attendance_data($org_id, $start_date, $end_date)
	{
		// 해당 주의 일요일 날짜 계산
		$sunday_date = $this->get_sunday_of_week($start_date);
		$att_year = date('Y', strtotime($sunday_date));

		$this->db->select("a.member_idx, GROUP_CONCAT(CONCAT(at.att_type_nickname, '|', at.att_type_idx, '|', at.att_type_category_idx, '|', at.att_type_color) ORDER BY at.att_type_order, at.att_type_idx SEPARATOR ',') AS att_type_nicknames");
		$this->db->from('wb_member_att a');
		$this->db->join('wb_att_type at', 'a.att_type_idx = at.att_type_idx', 'left');
		$this->db->where('a.org_id', $org_id);
		$this->db->where('a.att_date', $sunday_date); // 일요일 날짜로 조회
		$this->db->where('a.att_year', $att_year);
		$this->db->group_by('a.member_idx');
		$this->db->having('COUNT(a.att_type_idx) > 0');
		$query = $this->db->get();
		$result = $query->result_array();

		$attendance_data = array();
		foreach ($result as $row) {
			$attendance_data[$row['member_idx']] = $row['att_type_nicknames'];
		}

		return $attendance_data;
	}

	/**
	 * 파일 위치: application/models/Attendance_model.php
	 * 역할: 주간 출석 통계 조회 - 일요일 날짜로 조회
	 */
	public function get_weekly_attendance_stats($org_id, $member_indices, $sunday_dates, $year)
	{
		$stats = array();

		foreach ($member_indices as $member_idx) {
			$stats[$member_idx] = array();

			foreach ($sunday_dates as $sunday) {
				// 해당 주간의 출석 개수만 카운트 - 일요일 날짜로 조회
				$this->db->select('COUNT(*) as attendance_count');
				$this->db->from('wb_member_att');
				$this->db->where('member_idx', $member_idx);
				$this->db->where('org_id', $org_id);
				$this->db->where('att_date', $sunday); // 일요일 날짜로 조회
				$this->db->where('att_year', $year);

				$query = $this->db->get();
				$result = $query->row_array();

				$attendance_count = $result['attendance_count'] ?? 0;

				$stats[$member_idx][$sunday] = array(
					'total_score' => $attendance_count,
					'attendance_count' => $attendance_count
				);
			}
		}

		return $stats;
	}


	/**
	 * 파일 위치: application/models/Attendance_model.php - get_weekly_attendance_stats_fast() 함수 수정
	 * 역할: att_value 컬럼 존재 여부 확인하여 오류 방지
	 */
	public function get_weekly_attendance_stats_fast($org_id, $member_indices, $sunday_dates, $year)
	{
		if (empty($member_indices) || empty($sunday_dates)) {
			return array();
		}

		// 출석유형별 포인트 정보 가져오기
		$attendance_types = $this->get_attendance_types($org_id);
		$type_points = array();

		foreach ($attendance_types as $type) {
			$type_points[$type['att_type_idx']] = array(
				'point' => intval($type['att_type_point']) ?: 10,
				'input_type' => $type['att_type_input'] ?: 'check'
			);
		}

		// att_value 컬럼 존재 여부 확인
		$has_att_value = $this->db->field_exists('att_value', 'wb_member_att');

		// 각 회원별, 주별 실제 포인트 계산
		$stats = array();

		foreach ($member_indices as $member_idx) {
			$stats[$member_idx] = array();

			foreach ($sunday_dates as $sunday_date) {
				// 해당 주의 날짜 범위 계산
				$start_date = $sunday_date;
				$end_date = date('Y-m-d', strtotime($sunday_date . ' +6 days'));

				// SELECT 필드 동적 설정
				$select_fields = 'att_type_idx';
				if ($has_att_value) {
					$select_fields .= ', att_value';
				}

				$this->db->select($select_fields);
				$this->db->from('wb_member_att');
				$this->db->where('member_idx', $member_idx);
				$this->db->where('org_id', $org_id);
				$this->db->where('att_date >=', $start_date);
				$this->db->where('att_date <=', $end_date);
				$this->db->where('att_year', $year);

				$query = $this->db->get();
				$records = $query->result_array();

				// 실제 포인트 계산
				$total_points = 0;
				foreach ($records as $record) {
					$att_type_idx = $record['att_type_idx'];
					$att_value = $has_att_value && isset($record['att_value']) ? $record['att_value'] : null;

					if (isset($type_points[$att_type_idx])) {
						$type_info = $type_points[$att_type_idx];

						if ($type_info['input_type'] === 'text' && !empty($att_value)) {
							// 텍스트박스인 경우 실제 입력값 사용
							$total_points += intval($att_value);
						} else {
							// 체크박스인 경우 출석유형의 기본 포인트 사용
							$total_points += $type_info['point'];
						}
					} else {
						// 출석유형 정보가 없으면 기본 10점
						$total_points += 10;
					}
				}

				$stats[$member_idx][$sunday_date] = array(
					'total_score' => $total_points
				);
			}
		}

		// 누락된 데이터는 0으로 채우기
		foreach ($member_indices as $member_idx) {
			if (!isset($stats[$member_idx])) {
				$stats[$member_idx] = array();
			}

			foreach ($sunday_dates as $sunday_date) {
				if (!isset($stats[$member_idx][$sunday_date])) {
					$stats[$member_idx][$sunday_date] = array(
						'total_score' => 0
					);
				}
			}
		}

		return $stats;
	}

	/**
	 * 파일 위치: application/models/Attendance_model.php - save_attendance_with_values() 함수 추가
	 * 역할: att_value 필드를 포함한 출석 데이터 저장
	 */
	public function save_attendance_with_values($org_id, $attendance_data, $att_date, $year)
	{
		$this->db->trans_start();

		try {
			// att_value 컬럼 존재 여부 확인
			$has_att_value = $this->db->field_exists('att_value', 'wb_member_att');

			foreach ($attendance_data as $member_data) {
				$member_idx = $member_data['member_idx'];
				$attendance_types = $member_data['attendance_types'];

				// 해당 회원의 해당 날짜 기존 출석 기록 삭제
				$this->db->where('member_idx', $member_idx);
				$this->db->where('att_date', $att_date);
				$this->db->where('org_id', $org_id);
				$this->db->where('att_year', $year);
				$this->db->delete('wb_member_att');

				// 새로운 출석 기록 삽입
				foreach ($attendance_types as $att_type_data) {
					$insert_data = array(
						'member_idx' => $member_idx,
						'att_date' => $att_date,
						'att_type_idx' => $att_type_data['att_type_idx'],
						'org_id' => $org_id,
						'att_year' => $year,
						'regi_date' => date('Y-m-d H:i:s'),
						'modi_date' => date('Y-m-d')
					);

					// att_value 컬럼이 존재하고 값이 있으면 추가
					if ($has_att_value && isset($att_type_data['att_value'])) {
						$insert_data['att_value'] = intval($att_type_data['att_value']);
					}

					$this->db->insert('wb_member_att', $insert_data);
				}
			}

			$this->db->trans_complete();

			if ($this->db->trans_status() === FALSE) {
				return false;
			}

			// 통계 업데이트
			$sunday_date = $this->get_sunday_of_week($att_date);
			$member_indices = array_unique(array_column($attendance_data, 'member_idx'));

			foreach ($member_indices as $member_idx) {
				$this->update_weekly_attendance_stats($org_id, $member_idx, $year, $sunday_date);
			}

			return true;

		} catch (Exception $e) {
			$this->db->trans_rollback();
			log_message('error', 'Save attendance with values error: ' . $e->getMessage());
			return false;
		}
	}

	/**
	 * 파일 위치: application/models/Attendance_model.php - update_weekly_attendance_stats() 함수 수정
	 * 역할: att_value 컬럼 존재 여부 확인하여 통계 계산
	 */
	public function update_weekly_attendance_stats($org_id, $member_idx, $att_year, $sunday_date)
	{
		// 해당 주의 날짜 범위 계산
		$start_date = $sunday_date;
		$end_date = date('Y-m-d', strtotime($sunday_date . ' +6 days'));

		// att_value 컬럼 존재 여부 확인
		$has_att_value = $this->db->field_exists('att_value', 'wb_member_att');

		// 실제 출석 값 합계 계산
		if ($has_att_value) {
			$this->db->select('SUM(COALESCE(att_value, 10)) as total_score'); // att_value가 없으면 기본 10점
		} else {
			$this->db->select('COUNT(*) * 10 as total_score'); // att_value 컬럼이 없으면 개수 * 10점
		}

		$this->db->from('wb_member_att');
		$this->db->where('member_idx', $member_idx);
		$this->db->where('org_id', $org_id);
		$this->db->where('att_date >=', $start_date);
		$this->db->where('att_date <=', $end_date);
		$this->db->where('att_year', $att_year);

		$query = $this->db->get();
		$result = $query->row_array();
		$total_score = intval($result['total_score'] ?? 0);

		// 출석 횟수도 계산
		$this->db->select('COUNT(*) as attendance_count');
		$this->db->from('wb_member_att');
		$this->db->where('member_idx', $member_idx);
		$this->db->where('org_id', $org_id);
		$this->db->where('att_date >=', $start_date);
		$this->db->where('att_date <=', $end_date);
		$this->db->where('att_year', $att_year);

		$query = $this->db->get();
		$result = $query->row_array();
		$attendance_count = intval($result['attendance_count'] ?? 0);

		// 통계 테이블에 업데이트 또는 삽입
		$stats_data = array(
			'org_id' => $org_id,
			'member_idx' => $member_idx,
			'att_year' => $att_year,
			'sunday_date' => $sunday_date,
			'attendance_count' => $attendance_count,
			'total_score' => $total_score
		);

		$this->db->replace('wb_attendance_weekly_stats', $stats_data);

		// 연간 통계도 업데이트
		$this->update_yearly_attendance_stats($org_id, $member_idx, $att_year);

		return $total_score;
	}

	/**
	 * 역할: 특정 회원의 날짜 범위별 출석 데이터 삭제
	 */
	public function delete_attendance_by_date_range($member_idx, $start_date, $end_date)
	{
		// 시작일에 해당하는 일요일 날짜 계산
		$sunday_date = $this->get_sunday_of_week($start_date);
		$att_year = date('Y', strtotime($sunday_date));

		$this->db->where('member_idx', $member_idx);
		$this->db->where('att_date', $sunday_date); // 일요일 날짜로 삭제
		$this->db->where('att_year', $att_year); // att_year 조건 추가

		$result = $this->db->delete('wb_member_att');
		return $this->db->affected_rows() > 0;
	}

}
