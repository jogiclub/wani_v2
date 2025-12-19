<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Mng_Dashboard extends CI_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->library('session');
		$this->load->helper('url');
		$this->load->model('Org_model');
		$this->load->model('Org_category_model');
		$this->load->model('User_model');
		$this->load->model('Member_model');
		$this->load->model('Attendance_model');
		$this->load->model('Timeline_model');
		$this->load->model('Memo_model');

		if ($this->session->userdata('master_yn') !== 'Y') {
			show_error('접근 권한이 없습니다.', 403);
		}
	}

	public function index()
	{
		$data['user'] = $this->User_model->get_user_by_id($this->session->userdata('user_id'));
		$this->load->view('mng/mng_dashboard', $data);
	}

	/**
	 * 마스터가 권한을 가진 조직 목록 조회 (ID와 이름만)
	 */
	private function get_accessible_orgs()
	{
		$user_id = $this->session->userdata('user_id');
		$user = $this->User_model->get_user_by_id($user_id);

		$master_managed_category = !empty($user['master_managed_category'])
			? json_decode($user['master_managed_category'], true)
			: array();

		if (empty($master_managed_category)) {
			// 전체 조직의 ID와 이름만 조회
			$this->db->select('org_id, org_name');
			$this->db->from('wb_org');
			$this->db->where('del_yn', 'N');
			$this->db->order_by('org_name', 'ASC');
			$query = $this->db->get();
			return $query->result_array();
		} else {
			// 특정 카테고리의 조직 ID와 이름만 조회
			$this->db->select('org_id, org_name');
			$this->db->from('wb_org');
			$this->db->where_in('category_idx', $master_managed_category);
			$this->db->where('del_yn', 'N');
			$this->db->order_by('org_name', 'ASC');
			$query = $this->db->get();
			return $query->result_array();
		}
	}

	/**
	 * 회원현황 통계 조회 (월별, 조직별)
	 */
	public function get_member_stats()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$year = $this->input->post('year');
		$month = $this->input->post('month');

		if (!$year || !$month) {
			echo json_encode(array(
				'success' => false,
				'message' => '년도와 월이 필요합니다.'
			));
			return;
		}

		try {
			$orgs = $this->get_accessible_orgs();

			if (empty($orgs)) {
				echo json_encode(array(
					'success' => true,
					'data' => array(
						'orgs' => array(),
						'weekly_data' => array()
					)
				));
				return;
			}

			$daily_data = $this->Member_model->get_monthly_new_members_by_orgs($orgs, $year, $month);

			// 응답 데이터 크기 줄이기 - 조직 정보 간소화
			echo json_encode(array(
				'success' => true,
				'data' => $daily_data
			), JSON_UNESCAPED_UNICODE);
		} catch (Exception $e) {
			log_message('error', 'Master dashboard member stats error: ' . $e->getMessage());
			echo json_encode(array(
				'success' => false,
				'message' => '회원현황 통계 조회 중 오류가 발생했습니다.'
			));
		}
	}




	/**
	 * 출석 타입 목록 조회
	 */
	public function get_attendance_types()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		try {
			$orgs = $this->get_accessible_orgs();
			$org_ids = array_column($orgs, 'org_id');

			if (empty($org_ids)) {
				echo json_encode(array(
					'success' => true,
					'data' => array()
				));
				return;
			}

			$att_types = $this->Org_model->get_all_attendance_types_by_orgs($org_ids);

			echo json_encode(array(
				'success' => true,
				'data' => $att_types
			));
		} catch (Exception $e) {
			log_message('error', 'Get attendance types error: ' . $e->getMessage());
			echo json_encode(array(
				'success' => false,
				'message' => '출석 타입 조회 중 오류가 발생했습니다.'
			));
		}
	}

	/**
	 * 타임라인 타입 목록 조회
	 */
	public function get_timeline_types()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		try {
			$orgs = $this->get_accessible_orgs();
			$org_ids = array_column($orgs, 'org_id');

			if (empty($org_ids)) {
				echo json_encode(array(
					'success' => true,
					'data' => array()
				));
				return;
			}

			$timeline_types = $this->Org_model->get_all_timeline_types_by_orgs($org_ids);

			echo json_encode(array(
				'success' => true,
				'data' => $timeline_types
			));
		} catch (Exception $e) {
			log_message('error', 'Get timeline types error: ' . $e->getMessage());
			echo json_encode(array(
				'success' => false,
				'message' => '타임라인 타입 조회 중 오류가 발생했습니다.'
			));
		}
	}

	/**
	 * 메모 타입 목록 조회
	 */
	public function get_memo_types()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		try {
			$orgs = $this->get_accessible_orgs();
			$org_ids = array_column($orgs, 'org_id');

			if (empty($org_ids)) {
				echo json_encode(array(
					'success' => true,
					'data' => array()
				));
				return;
			}

			$memo_types = $this->Org_model->get_all_memo_types_by_orgs($org_ids);

			echo json_encode(array(
				'success' => true,
				'data' => $memo_types
			));
		} catch (Exception $e) {
			log_message('error', 'Get memo types error: ' . $e->getMessage());
			echo json_encode(array(
				'success' => false,
				'message' => '메모 타입 조회 중 오류가 발생했습니다.'
			));
		}
	}

	/**
	 * 출석현황 통계 조회 (월별, 조직별, 타입별)
	 */
	public function get_attendance_stats()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$year = $this->input->post('year');
		$month = $this->input->post('month');

		if (!$year || !$month) {
			echo json_encode(array(
				'success' => false,
				'message' => '년도와 월이 필요합니다.'
			));
			return;
		}

		try {
			$orgs = $this->get_accessible_orgs();

			if (empty($orgs)) {
				echo json_encode(array(
					'success' => true,
					'data' => array(
						'orgs' => array(),
						'weekly_data' => array(),
						'att_types' => array()
					)
				));
				return;
			}

			$daily_data = $this->Attendance_model->get_monthly_attendance_by_orgs_and_types($orgs, $year, $month);

			echo json_encode(array(
				'success' => true,
				'data' => $daily_data
			), JSON_UNESCAPED_UNICODE);
		} catch (Exception $e) {
			log_message('error', 'Master dashboard attendance stats error: ' . $e->getMessage());
			echo json_encode(array(
				'success' => false,
				'message' => '출석현황 통계 조회 중 오류가 발생했습니다.'
			));
		}
	}

	/**
	 * 타임라인현황 통계 조회 (월별, 조직별, 타입별)
	 */
	public function get_timeline_stats()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$year = $this->input->post('year');
		$month = $this->input->post('month');

		if (!$year || !$month) {
			echo json_encode(array(
				'success' => false,
				'message' => '년도와 월이 필요합니다.'
			));
			return;
		}

		try {
			$orgs = $this->get_accessible_orgs();

			if (empty($orgs)) {
				echo json_encode(array(
					'success' => true,
					'data' => array(
						'orgs' => array(),
						'weekly_data' => array(),
						'timeline_types' => array()
					)
				));
				return;
			}

			$daily_data = $this->Timeline_model->get_monthly_timeline_by_orgs_and_types($orgs, $year, $month);

			echo json_encode(array(
				'success' => true,
				'data' => $daily_data
			), JSON_UNESCAPED_UNICODE);
		} catch (Exception $e) {
			log_message('error', 'Master dashboard timeline stats error: ' . $e->getMessage());
			echo json_encode(array(
				'success' => false,
				'message' => '타임라인현황 통계 조회 중 오류가 발생했습니다.'
			));
		}
	}

	/**
	 * 메모현황 통계 조회 (월별, 조직별, 타입별)
	 */
	public function get_memo_stats()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$year = $this->input->post('year');
		$month = $this->input->post('month');

		if (!$year || !$month) {
			echo json_encode(array(
				'success' => false,
				'message' => '년도와 월이 필요합니다.'
			));
			return;
		}

		try {
			$orgs = $this->get_accessible_orgs();

			if (empty($orgs)) {
				echo json_encode(array(
					'success' => true,
					'data' => array(
						'orgs' => array(),
						'weekly_data' => array(),
						'memo_types' => array()
					)
				));
				return;
			}

			$daily_data = $this->Memo_model->get_monthly_memo_by_orgs_and_types($orgs, $year, $month);

			echo json_encode(array(
				'success' => true,
				'data' => $daily_data
			), JSON_UNESCAPED_UNICODE);
		} catch (Exception $e) {
			log_message('error', 'Master dashboard memo stats error: ' . $e->getMessage());
			echo json_encode(array(
				'success' => false,
				'message' => '메모현황 통계 조회 중 오류가 발생했습니다.'
			));
		}
	}

	/**
	 * 통계 데이터 갱신
	 */
	public function refresh_stats()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$type = $this->input->post('type');
		$year = $this->input->post('year');
		$month = $this->input->post('month');

		if (!$type || !$year || !$month) {
			echo json_encode(array(
				'success' => false,
				'message' => '필수 파라미터가 누락되었습니다.'
			));
			return;
		}

		try {
			$orgs = $this->get_accessible_orgs();

			if (empty($orgs)) {
				echo json_encode(array(
					'success' => false,
					'message' => '권한이 있는 조직이 없습니다.'
				));
				return;
			}

			switch ($type) {
				case 'member':
					$result = $this->rebuild_member_stats($orgs, $year, $month);
					break;
				case 'attendance':
					$result = $this->rebuild_attendance_stats($orgs, $year, $month);
					break;
				case 'timeline':
					$result = $this->rebuild_timeline_stats($orgs, $year, $month);
					break;
				case 'memo':
					$result = $this->rebuild_memo_stats($orgs, $year, $month);
					break;
				default:
					echo json_encode(array(
						'success' => false,
						'message' => '잘못된 통계 타입입니다.'
					));
					return;
			}

			if ($result) {
				echo json_encode(array(
					'success' => true,
					'message' => '통계가 성공적으로 갱신되었습니다.'
				));
			} else {
				echo json_encode(array(
					'success' => false,
					'message' => '통계 갱신 중 오류가 발생했습니다.'
				));
			}
		} catch (Exception $e) {
			log_message('error', 'Stats refresh error: ' . $e->getMessage());
			echo json_encode(array(
				'success' => false,
				'message' => '통계 갱신 중 오류가 발생했습니다.'
			));
		}
	}

	/**
	 * 회원 통계 재구성
	 */
	private function rebuild_member_stats($orgs, $year, $month)
	{
		$this->load->model('Member_model');
		$sundays = $this->Member_model->get_sundays_in_month($year, $month);

		$this->db->trans_start();

		try {
			foreach ($orgs as $org) {
				$org_id = $org['org_id'];

				foreach ($sundays as $sunday_info) {
					$sunday_date = $sunday_info['sunday_date'];
					$week_end = date('Y-m-d', strtotime($sunday_date . ' +6 days'));

					$this->db->select('COUNT(*) as count');
					$this->db->from('wb_member');
					$this->db->where('org_id', $org_id);
					$this->db->where('regi_date >=', $sunday_date . ' 00:00:00');
					$this->db->where('regi_date <=', $week_end . ' 23:59:59');
					$this->db->where('del_yn', 'N');

					$query = $this->db->get();
					$result = $query->row_array();
					$new_member_count = (int)$result['count'];

					$data = array(
						'org_id' => $org_id,
						'att_year' => $year,
						'sunday_date' => $sunday_date,
						'new_member_count' => $new_member_count
					);

					$this->db->replace('wb_member_weekly_stats', $data);
				}
			}

			$this->db->trans_complete();

			if ($this->db->trans_status() === FALSE) {
				return false;
			}

			return true;
		} catch (Exception $e) {
			$this->db->trans_rollback();
			log_message('error', 'Rebuild member stats error: ' . $e->getMessage());
			return false;
		}
	}

	/**
	 * 출석 통계 재구성
	 */
	private function rebuild_attendance_stats($orgs, $year, $month)
	{
		$this->load->model('Member_model');
		$sundays = $this->Member_model->get_sundays_in_month($year, $month);

		$this->db->trans_start();

		try {
			foreach ($orgs as $org) {
				$org_id = $org['org_id'];

				foreach ($sundays as $sunday_info) {
					$sunday_date = $sunday_info['sunday_date'];
					$week_end = date('Y-m-d', strtotime($sunday_date . ' +6 days'));

					$this->db->select("att.att_type_idx, t.att_type_name, COUNT(*) as count");
					$this->db->from('wb_member_att att');
					$this->db->join('wb_att_type t', 'att.att_type_idx = t.att_type_idx', 'inner');
					$this->db->where('att.org_id', $org_id);
					$this->db->where('att.att_date >=', $sunday_date);
					$this->db->where('att.att_date <=', $week_end);
					$this->db->where('att.att_year', $year);
					$this->db->group_by('att.att_type_idx, t.att_type_name');

					$query = $this->db->get();
					$results = $query->result_array();

					foreach ($results as $row) {
						$data = array(
							'org_id' => $org_id,
							'att_year' => $year,
							'sunday_date' => $sunday_date,
							'att_type_name' => $row['att_type_name'],
							'attendance_count' => (int)$row['count']
						);

						$this->db->replace('wb_attendance_weekly_type_stats', $data);
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
			log_message('error', 'Rebuild attendance stats error: ' . $e->getMessage());
			return false;
		}
	}

	/**
	 * 타임라인 통계 재구성
	 */
	private function rebuild_timeline_stats($orgs, $year, $month)
	{
		$this->load->model('Member_model');
		$sundays = $this->Member_model->get_sundays_in_month($year, $month);

		$this->db->trans_start();

		try {
			foreach ($orgs as $org) {
				$org_id = $org['org_id'];

				foreach ($sundays as $sunday_info) {
					$sunday_date = $sunday_info['sunday_date'];
					$week_end = date('Y-m-d', strtotime($sunday_date . ' +6 days'));

					$this->db->select("t.timeline_type, COUNT(*) as count");
					$this->db->from('wb_member_timeline t');
					$this->db->join('wb_member m', 't.member_idx = m.member_idx', 'inner');
					$this->db->where('m.org_id', $org_id);
					$this->db->where('m.del_yn', 'N');
					$this->db->where('t.timeline_date >=', $sunday_date);
					$this->db->where('t.timeline_date <=', $week_end);
					$this->db->group_by('t.timeline_type');

					$query = $this->db->get();
					$results = $query->result_array();

					foreach ($results as $row) {
						$data = array(
							'org_id' => $org_id,
							'att_year' => $year,
							'sunday_date' => $sunday_date,
							'timeline_type' => $row['timeline_type'],
							'timeline_count' => (int)$row['count']
						);

						$this->db->replace('wb_timeline_weekly_type_stats', $data);
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
			log_message('error', 'Rebuild timeline stats error: ' . $e->getMessage());
			return false;
		}
	}

	/**
	 * 메모 통계 재구성
	 */
	private function rebuild_memo_stats($orgs, $year, $month)
	{
		$this->load->model('Member_model');
		$sundays = $this->Member_model->get_sundays_in_month($year, $month);

		$this->db->trans_start();

		try {
			foreach ($orgs as $org) {
				$org_id = $org['org_id'];

				foreach ($sundays as $sunday_info) {
					$sunday_date = $sunday_info['sunday_date'];
					$week_end = date('Y-m-d', strtotime($sunday_date . ' +6 days'));

					$this->db->select("memo.memo_type, COUNT(*) as count");
					$this->db->from('wb_memo memo');
					$this->db->join('wb_member m', 'memo.member_idx = m.member_idx', 'inner');
					$this->db->where('m.org_id', $org_id);
					$this->db->where('m.del_yn', 'N');
					$this->db->where('memo.del_yn', 'N');
					$this->db->where('memo.regi_date >=', $sunday_date . ' 00:00:00');
					$this->db->where('memo.regi_date <=', $week_end . ' 23:59:59');
					$this->db->group_by('memo.memo_type');

					$query = $this->db->get();
					$results = $query->result_array();

					foreach ($results as $row) {
						$data = array(
							'org_id' => $org_id,
							'att_year' => $year,
							'sunday_date' => $sunday_date,
							'memo_type' => $row['memo_type'],
							'memo_count' => (int)$row['count']
						);

						$this->db->replace('wb_memo_weekly_type_stats', $data);
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
			log_message('error', 'Rebuild memo stats error: ' . $e->getMessage());
			return false;
		}
	}


}
