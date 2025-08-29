<?php
class Attendance_model extends CI_Model {
    public function __construct() {
        parent::__construct();
        $this->load->database();
    }

    public function get_attendance_types($org_id) {
        $this->db->select('att_type_idx, att_type_category_name, att_type_category_idx, att_type_nickname, att_type_name, att_type_color, att_type_order');
        $this->db->from('wb_att_type');
        $this->db->where('org_id', $org_id);
        $this->db->order_by('att_type_order', 'ASC');
        $this->db->order_by('att_type_idx', 'ASC');


        $query = $this->db->get();

//        print_r($this->db->last_query());
//        exit;


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
			// 멤버별로 그룹화된 데이터에서 멤버 인덱스 추출
			$member_indices = array_keys($member_attendance_data);

			if (!empty($member_indices)) {
				// 해당 멤버들의 해당 기간 출석 정보 삭제
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

    public function get_member_attendance($member_idx, $start_date, $end_date) {
        $this->db->select('att_type_idx, att_date');
        $this->db->from('wb_member_att');
        $this->db->where('member_idx', $member_idx);
        $this->db->where('att_date >=', $start_date);
        $this->db->where('att_date <=', $end_date);
        $this->db->order_by('att_date', 'ASC');

        $query = $this->db->get();
        $result = $query->result_array();

        $attendance_data = array();
        foreach ($result as $row) {
            $attendance_data[$row['att_date']][] = strval($row['att_type_idx']);
        }

        return $attendance_data;
    }


// Attendance_model.php
    public function get_org_member_attendance($org_id, $area_idx, $start_date, $end_date) {
        $this->db->select('ma.member_idx, GROUP_CONCAT(ma.att_type_idx ORDER BY ma.att_type_idx SEPARATOR ",") AS att_type_idxs', false);
        $this->db->from('wb_member_att ma');
        $this->db->join('wb_member m', 'ma.member_idx = m.member_idx', 'inner');
        $this->db->where('m.org_id', $org_id);
        $this->db->where('m.area_idx', $area_idx);
        $this->db->where('ma.att_date >=', $start_date);
        $this->db->where('ma.att_date <=', $end_date);
        $this->db->group_by('ma.member_idx');
        $query = $this->db->get();
        $result = $query->result_array();

        $attendance_data = array();
        foreach ($result as $row) {
            $attendance_data[$row['member_idx']] = explode(',', $row['att_type_idxs']);
        }

        return $attendance_data;
    }

    public function delete_attendance_by_date($member_idx, $att_date) {
        $this->db->where('member_idx', $member_idx);
        $this->db->where('att_date', $att_date);
        $this->db->delete('wb_member_att');
    }












    public function get_org_attendance_data($org_id, $start_date, $end_date) {
        $this->db->select("a.member_idx, GROUP_CONCAT(CONCAT(at.att_type_nickname, '|', at.att_type_idx, '|', at.att_type_category_idx, '|', at.att_type_color) ORDER BY at.att_type_order, at.att_type_idx SEPARATOR ',') AS att_type_nicknames");
        $this->db->from('wb_member_att a');
        $this->db->join('wb_att_type at', 'a.att_type_idx = at.att_type_idx', 'left');
        $this->db->where('a.org_id', $org_id);
        $this->db->where('a.att_date >=', $start_date);
        $this->db->where('a.att_date <=', $end_date);
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



    public function save_single_attendance($data) {
        $this->db->insert('wb_member_att', $data);
        return $this->db->affected_rows() > 0;
    }

    public function delete_attendance_by_category($member_idx, $att_type_category_idx, $att_date) {
        $this->db->where('member_idx', $member_idx);
        $this->db->where('att_date', $att_date);

        if (!empty($att_type_category_idx)) {
            $this->db->where('att_type_idx IN (SELECT att_type_idx FROM wb_att_type WHERE att_type_category_idx = ' . $this->db->escape($att_type_category_idx) . ')', NULL, FALSE);
        }

        $this->db->delete('wb_member_att');
    }


    public function delete_attendance_by_date_range($member_idx, $start_date, $end_date) {
        $this->db->where('member_idx', $member_idx);
        $this->db->where('att_date >=', $start_date);
        $this->db->where('att_date <=', $end_date);
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

    public function get_member_attendance_summery($org_id, $att_type_idx) {
        $this->db->select('member_idx, WEEK(att_date, 0) as week_number, COUNT(*) as count');
        $this->db->from('wb_member_att');
        $this->db->where('org_id', $org_id);
        $this->db->where('att_type_idx', $att_type_idx);
        $this->db->group_by('member_idx, WEEK(att_date, 0)');
        $query = $this->db->get();

        $result = array();
        foreach ($query->result_array() as $row) {
            $member_idx = $row['member_idx'];
            $week_number = $row['week_number'];
            $count = $row['count'];
            $result[$member_idx][$week_number] = $count;
        }

//        print_r($this->db->last_query());
//        exit;

        return $result;
    }









	/**
	 * 특정 주간의 출석 기록 가져오기
	 */
	public function get_week_attendance_records($org_id, $member_indices, $start_date, $end_date)
	{
		$this->db->select('ma.member_idx, ma.att_date, ma.att_type_idx, at.att_type_name, at.att_type_nickname, at.att_type_color, at.att_type_order');
		$this->db->from('wb_member_att ma');
		$this->db->join('wb_att_type at', 'ma.att_type_idx = at.att_type_idx', 'left');
		$this->db->where('ma.org_id', $org_id);
		$this->db->where_in('ma.member_idx', $member_indices);
		$this->db->where('ma.att_date >=', $start_date);
		$this->db->where('ma.att_date <=', $end_date);
		$this->db->order_by('ma.member_idx, ma.att_date, at.att_type_order');

		$query = $this->db->get();
		return $query->result_array();
	}

	/**
	 * 특정 회원의 주간 출석 데이터 가져오기
	 */
	public function get_member_week_attendance($member_idx, $org_id, $start_date, $end_date)
	{
		$this->db->select('ma.att_date, ma.att_type_idx, at.att_type_name, at.att_type_nickname, at.att_type_color, at.att_type_order');
		$this->db->from('wb_member_att ma');
		$this->db->join('wb_att_type at', 'ma.att_type_idx = at.att_type_idx', 'left');
		$this->db->where('ma.member_idx', $member_idx);
		$this->db->where('ma.org_id', $org_id);
		$this->db->where('ma.att_date >=', $start_date);
		$this->db->where('ma.att_date <=', $end_date);
		$this->db->order_by('ma.att_date, at.att_type_order');

		$query = $this->db->get();
		$results = $query->result_array();

		// 날짜별로 출석 유형들을 그룹핑
		$attendance_by_date = array();
		foreach ($results as $row) {
			$date = $row['att_date'];
			if (!isset($attendance_by_date[$date])) {
				$attendance_by_date[$date] = array();
			}
			$attendance_by_date[$date][] = array(
				'att_type_idx' => $row['att_type_idx'],
				'att_type_name' => $row['att_type_name'],
				'att_type_nickname' => $row['att_type_nickname'],
				'att_type_color' => $row['att_type_color'],
				'att_type_order' => $row['att_type_order']
			);
		}

		return $attendance_by_date;
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
	 * 출석 데이터 일괄 저장
	 */
	public function save_attendance_batch($org_id, $attendance_data)
	{
		$this->db->trans_start();

		try {
			// attendance_data 구조:
			// [
			//   {
			//     member_idx: 123,
			//     att_date: '2025-01-05',
			//     att_type_indices: [1, 2, 3] // 체크된 출석 유형들
			//   },
			//   ...
			// ]

			foreach ($attendance_data as $data) {
				$member_idx = $data['member_idx'];
				$att_date = $data['att_date'];
				$att_type_indices = $data['att_type_indices'];

				// 해당 회원의 해당 날짜 출석 기록 삭제
				$this->db->where('member_idx', $member_idx);
				$this->db->where('att_date', $att_date);
				$this->db->where('org_id', $org_id);
				$this->db->delete('wb_member_att');

				// 새로운 출석 기록 삽입
				if (!empty($att_type_indices)) {
					foreach ($att_type_indices as $att_type_idx) {
						if (!empty($att_type_idx)) {
							$insert_data = array(
								'member_idx' => $member_idx,
								'att_date' => $att_date,
								'att_type_idx' => $att_type_idx,
								'org_id' => $org_id
							);
							$this->db->insert('wb_member_att', $insert_data);
						}
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
			log_message('error', 'Attendance batch save error: ' . $e->getMessage());
			return false;
		}
	}

	/**
	 * 주별 출석 통계 가져오기 (점수 계산 포함)
	 */
	public function get_weekly_attendance_stats($org_id, $member_indices, $sunday_dates)
	{
		$stats = array();

		foreach ($member_indices as $member_idx) {
			$stats[$member_idx] = array();

			foreach ($sunday_dates as $sunday) {
				// 해당 주의 날짜 범위 계산
				$start_date = $sunday;
				$end_date = date('Y-m-d', strtotime($sunday . ' +6 days'));

				// 해당 주간의 출석 기록 가져오기
				$this->db->select('ma.att_type_idx, at.att_type_name, at.att_type_nickname, at.att_type_color, at.att_type_order');
				$this->db->from('wb_member_att ma');
				$this->db->join('wb_att_type at', 'ma.att_type_idx = at.att_type_idx', 'left');
				$this->db->where('ma.member_idx', $member_idx);
				$this->db->where('ma.org_id', $org_id);
				$this->db->where('ma.att_date >=', $start_date);
				$this->db->where('ma.att_date <=', $end_date);
				$this->db->order_by('at.att_type_order');

				$query = $this->db->get();
				$week_attendance = $query->result_array();

				// 점수 계산 (예시: 각 출석 유형당 기본 점수를 부여)
				$total_score = 0;
				$attendance_types = array();

				foreach ($week_attendance as $att) {
					if (!in_array($att['att_type_idx'], $attendance_types)) {
						$attendance_types[] = $att['att_type_idx'];

						// 출석 유형별 점수 계산 (예시)
						$score = $this->get_attendance_type_score($att['att_type_idx']);
						$total_score += $score;
					}
				}

				$stats[$member_idx][$sunday] = array(
					'total_score' => $total_score,
					'attendance_count' => count($attendance_types),
					'attendance_types' => $attendance_types
				);
			}
		}

		return $stats;
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
}
