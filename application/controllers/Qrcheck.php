<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Qrcheck extends My_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->library('session');
		$this->load->helper('url');
		$this->load->model('User_model');
		$this->load->model('Org_model');
	}

	public function index()
	{
		// 사용자가 로그인되어 있는지 확인
		if (!$this->session->userdata('user_id')) {
			redirect('login/index');
			return;
		}

		$user_id = $this->session->userdata('user_id');

		// 헤더 데이터 준비
		$header_data = $this->prepare_header_data();
		if (empty($header_data['user_orgs'])) {
			redirect('dashboard');
			return;
		}

		$data = $header_data;

		// POST로 조직 변경 요청 처리 (My_Controller의 메소드 사용)
		$this->handle_org_change($data);

		$currentOrgId = $data['current_org']['org_id'];

		// 해당 그룹의 area_name 목록 가져오기
		$this->load->model('Member_area_model');
		$data['member_areas'] = $this->Member_area_model->get_member_areas($currentOrgId);

		// 활성화된 그룹의 출석타입 정보 가져오기
		$this->load->model('Attendance_model');
		$data['attendance_types'] = $this->Attendance_model->get_attendance_types($currentOrgId);

		// 선택된 모드 설정 (기본값: mode-1)
		$data['mode'] = $this->input->post('mode') ?? 'mode-1';

		// 사용자의 그룹 레벨 가져오기
		$user_group = $this->User_model->get_org_user($user_id, $currentOrgId);
		$user_level = $user_group ? $user_group['level'] : 1;
		$data['user_level'] = $user_level;

		$this->load->view('qrcheck', $data);
	}

	public function save_single_attendance()
	{
		if ($this->input->is_ajax_request()) {
			$member_idx = $this->input->post('member_idx');
			$att_type_idx = $this->input->post('att_type_idx');
			$att_date = $this->input->post('att_date');
			$org_id = $this->input->post('org_id');

			$this->load->model('Attendance_model');
			$result = $this->Attendance_model->save_single_attendance($member_idx, $att_type_idx, $att_date, $org_id);

			if ($result) {
				echo json_encode(array('status' => 'success', 'message' => '출석이 저장되었습니다.'));
			} else {
				echo json_encode(array('status' => 'error', 'message' => '출석 저장에 실패했습니다.'));
			}
		}
	}

	public function add_member()
	{
		if ($this->input->is_ajax_request()) {
			$org_id = $this->input->post('org_id');
			$member_name = $this->input->post('member_name');
			$area_idx = $this->input->post('area_idx');

			$data = array(
				'org_id' => $org_id,
				'grade' => 0,
				'area_idx' => $area_idx,
				'member_name' => $member_name,
				'member_nick' => $member_name,
				'new_yn' => 'Y',
				'del_yn' => 'N',
				'regi_date' => date('Y-m-d H:i:s'),
				'modi_date' => date('Y-m-d H:i:s')
			);

			$this->load->model('Member_model');
			$result = $this->Member_model->add_member($data);

			if ($result) {
				$response = array('status' => 'success');
			} else {
				$response = array('status' => 'error');
			}

			echo json_encode($response);
		}
	}

	public function get_member_info()
	{
		if ($this->input->is_ajax_request()) {
			$member_idx = $this->input->post('member_idx');

			$this->load->model('Member_model');
			$member_info = $this->Member_model->get_member_by_idx($member_idx);

			echo json_encode($member_info);
		}
	}

	public function get_attendance_data()
	{
		if ($this->input->is_ajax_request()) {
			$org_id = $this->input->post('org_id');
			$start_date = $this->input->post('start_date');
			$end_date = $this->input->post('end_date');

			$this->load->model('Attendance_model');
			$attendance_data = $this->Attendance_model->get_org_attendance_data($org_id, $start_date, $end_date);

			echo json_encode($attendance_data);
		}
	}

	public function get_members()
	{
		if ($this->input->is_ajax_request()) {
			$org_id = $this->input->post('org_id');
			$level = $this->input->post('level');
			$start_date = $this->input->post('start_date');
			$end_date = $this->input->post('end_date');

			$this->load->model('Member_model');

			$members = $this->Member_model->get_org_members($org_id, $level, $start_date, $end_date);
			echo json_encode($members);
		}
	}

	function getWeekRange($date, $includeWeek = false)
	{
		// 입력된 날짜의 타임스탬프 가져오기
		$timestamp = strtotime($date);
		// 현재 주의 일요일 타임스탬프 구하기
		$sundayTimestamp = strtotime('sunday this week', $timestamp);
		// 다음 주 일요일 타임스탬프 구하기
		$nextSundayTimestamp = strtotime('+1 week', $sundayTimestamp);
		// 주차 계산
		$week = date('W', $sundayTimestamp);
		// 시작일과 종료일 가져오기
		$startDate = date('Y.m.d', $sundayTimestamp);
		$endDate = date('Y.m.d', $nextSundayTimestamp - (24 * 60 * 60)); // 1일을 빼서 토요일로 만듦
		// 출력 형식 지정
		$output = "{$startDate}~{$endDate}";
		if ($includeWeek) {
			$output .= " ({$week}주차)";
		}
		return $output;
	}

	public function get_attendance_types()
	{
		if ($this->input->is_ajax_request()) {
			$org_id = $this->input->post('org_id');
			$this->load->model('Attendance_model');
			$attendance_types = $this->Attendance_model->get_attendance_types($org_id);

			$response = array(
				'attendance_types' => $attendance_types
			);

			echo json_encode($response);
		}
	}

	public function save_attendance()
	{
		if ($this->input->is_ajax_request()) {
			$member_idx = $this->input->post('member_idx');
			$attendance_data = json_decode($this->input->post('attendance_data'), true);
			$org_id = $this->input->post('org_id');
			$att_date = $this->input->post('att_date');
			$start_date = $this->input->post('start_date');
			$end_date = $this->input->post('end_date');

			$this->load->model('Attendance_model');

			// 해당 날짜의 모든 출석 정보 삭제
			$this->Attendance_model->delete_attendance_by_date_range($member_idx, $start_date, $end_date);

			if (!empty($attendance_data) && is_array($attendance_data)) {
				foreach ($attendance_data as $att_type_idx) {
					$data = array(
						'att_date' => $att_date,
						'att_type_idx' => $att_type_idx,
						'member_idx' => $member_idx,
						'org_id' => $org_id
					);

					$this->db->insert('wb_member_att', $data);
				}

				$response = array('status' => 'success');
			} else {
				$response = array('status' => 'success', 'message' => 'Attendance data deleted successfully.');
			}

			echo json_encode($response);
		}
	}

	public function get_member_attendance()
	{
		if ($this->input->is_ajax_request()) {
			$member_idx = $this->input->post('member_idx');
			$start_date = $this->input->post('start_date');
			$end_date = $this->input->post('end_date');

			$this->load->model('Attendance_model');
			$attendance_data = $this->Attendance_model->get_member_attendance($member_idx, $start_date, $end_date);

			$response = array(
				'attendance_data' => $attendance_data
			);

			echo json_encode($response);
		}
	}

	public function get_orgs()
	{
		if ($this->input->is_ajax_request()) {
			$user_id = $this->session->userdata('email');

			$this->load->model('Org_model');
			$orgs = $this->Org_model->get_user_orgs($user_id);

			$org_data = array();
			foreach ($orgs as $org) {
				$org_data[] = array(
					'org_id' => $org['org_id'],
					'org_name' => $org['org_name']
				);
			}

			echo json_encode($org_data);
		}
	}

	public function logout()
	{
		// 세션 삭제
		$this->session->sess_destroy();

		// 브라우저 캐시 방지 헤더
		$this->output->set_header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
		$this->output->set_header('Pragma: no-cache');

		// 쿠키 삭제 (CodeIgniter3 방식)
		$this->input->set_cookie('ci_session', '', time() - 3600);
		$this->input->set_cookie('activeOrg', '', time() - 3600);

		redirect('login');
	}

	public function get_same_members()
	{
		if ($this->input->is_ajax_request()) {
			$member_idx = $this->input->post('member_idx');
			$org_id = $this->input->post('org_id');
			$area_idx = $this->input->post('area_idx');
			$start_date = $this->input->post('start_date');
			$end_date = $this->input->post('end_date');

			$this->load->model('Member_model');
			$same_members = $this->Member_model->get_same_members($member_idx, $org_id, $area_idx, $start_date, $end_date);

			// 출석 유형 정보 가져오기
			$this->load->model('Attendance_model');
			$att_types = $this->Attendance_model->get_attendance_types($org_id);

			if ($same_members) {
				$response = array('status' => 'success', 'members' => $same_members, 'att_types' => $att_types);
			} else {
				$response = array('status' => 'error');
			}

			echo json_encode($response);
		}
	}

	public function save_attendance_data()
	{
		if ($this->input->is_ajax_request()) {
			$attendance_data = json_decode($this->input->post('attendance_data'), true);
			$org_id = $this->input->post('org_id');
			$start_date = $this->input->post('start_date');
			$end_date = $this->input->post('end_date');

			$this->load->model('Attendance_model');

			// 해당 기간의 모든 멤버 출석 정보를 먼저 삭제
			$member_indices = array_unique(array_column($attendance_data, 'member_idx'));

			if (!empty($member_indices)) {
				$this->db->where_in('member_idx', $member_indices);
				$this->db->where('org_id', $org_id);
				$this->db->where('att_date >=', $start_date);
				$this->db->where('att_date <=', $end_date);
				$this->db->delete('wb_member_att');
			}

			// 새로운 출석 정보를 직접 삽입
			$insert_success = true;
			foreach ($attendance_data as $item) {
				$member_idx = $item['member_idx'];
				$att_date = $item['att_date'];
				$att_type_idx = $item['att_type_idx'];

				if (!empty($att_type_idx)) {
					$data = array(
						'member_idx' => $member_idx,
						'att_date' => $att_date,
						'att_type_idx' => $att_type_idx,
						'org_id' => $org_id
					);

					$result = $this->db->insert('wb_member_att', $data);
					if (!$result) {
						$insert_success = false;
						break;
					}
				}
			}

			if ($insert_success) {
				echo json_encode(array('status' => 'success', 'message' => '출석이 저장되었습니다.'));
			} else {
				echo json_encode(array('status' => 'error', 'message' => '출석 저장에 실패했습니다.'));
			}
		}
	}

	public function get_last_week_attendance()
	{
		if ($this->input->is_ajax_request()) {
			$org_id = $this->input->post('org_id');
			$area_idx = $this->input->post('area_idx');
			$start_date = $this->input->post('start_date');
			$end_date = $this->input->post('end_date');

			$this->load->model('Attendance_model');
			$attendance_data = $this->Attendance_model->get_org_member_attendance($org_id, $area_idx, $start_date, $end_date);
			$att_types = $this->Attendance_model->get_attendance_types($org_id);

			$response = array(
				'status' => 'success',
				'attendance_data' => $attendance_data,
				'att_types' => $att_types
			);

			echo json_encode($response);
		}
	}
}
