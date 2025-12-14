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
	 * 마스터가 권한을 가진 조직 목록 조회 (ID와 이름 포함)
	 */
	private function get_accessible_orgs()
	{
		$user_id = $this->session->userdata('user_id');
		$user = $this->User_model->get_user_by_id($user_id);

		$master_managed_category = !empty($user['master_managed_category'])
			? json_decode($user['master_managed_category'], true)
			: array();

		if (empty($master_managed_category)) {
			return $this->Org_model->get_all_orgs();
		} else {
			return $this->Org_model->get_orgs_by_categories($master_managed_category);
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
						'daily_data' => array()
					)
				));
				return;
			}

			$daily_data = $this->Member_model->get_monthly_new_members_by_orgs($orgs, $year, $month);

			echo json_encode(array(
				'success' => true,
				'data' => array(
					'orgs' => $orgs,
					'daily_data' => $daily_data
				)
			));
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
						'daily_data' => array(),
						'att_types' => array()
					)
				));
				return;
			}

			$daily_data = $this->Attendance_model->get_monthly_attendance_by_orgs_and_types($orgs, $year, $month);

			echo json_encode(array(
				'success' => true,
				'data' => $daily_data
			));
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
						'daily_data' => array(),
						'timeline_types' => array()
					)
				));
				return;
			}

			$daily_data = $this->Timeline_model->get_monthly_timeline_by_orgs_and_types($orgs, $year, $month);

			echo json_encode(array(
				'success' => true,
				'data' => $daily_data
			));
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
						'daily_data' => array(),
						'memo_types' => array()
					)
				));
				return;
			}

			$daily_data = $this->Memo_model->get_monthly_memo_by_orgs_and_types($orgs, $year, $month);

			echo json_encode(array(
				'success' => true,
				'data' => $daily_data
			));
		} catch (Exception $e) {
			log_message('error', 'Master dashboard memo stats error: ' . $e->getMessage());
			echo json_encode(array(
				'success' => false,
				'message' => '메모현황 통계 조회 중 오류가 발생했습니다.'
			));
		}
	}
}
