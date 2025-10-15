<?php
// 파일 위치: application/controllers/Qrcheck.php
// 역할: 사용자 관리 > 관리그룹에서 설정한 회원들만 조회하도록 수정

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
		$this->load->model('User_management_model'); // 사용자 권한 관리 모델 추가

		// 메뉴 권한 체크
		$this->check_menu_access('ATTENDANCE_BOARD');
	}

	public function index()
	{


		$user_id = $this->session->userdata('user_id');

		// 헤더 데이터 준비 (조직이 없으면 MY_Controller에서 이미 리다이렉트됨)
		$header_data = $this->prepare_header_data();
		$data = $header_data;

		// POST로 조직 변경 요청 처리
		$this->handle_org_change($data);

		$currentOrgId = $data['current_org']['org_id'];

		// 사용자의 권한 레벨과 관리 권한 확인
		$master_yn = $this->session->userdata('master_yn');
		$user_level = $this->User_model->get_org_user_level($user_id, $currentOrgId);
		$data['user_level'] = $user_level;
		$data['master_yn'] = $master_yn;

		// 해당 그룹의 area_name 목록 가져오기 (권한 필터링 적용)
		$this->load->model('Member_area_model');
		if ($user_level >= 10 || $master_yn === 'Y') {
			// 최고관리자인 경우 모든 그룹 조회
			$data['member_areas'] = $this->Member_area_model->get_member_areas($currentOrgId);
		} else {
			// 일반 관리자인 경우 관리 가능한 그룹만 조회
			$accessible_areas = $this->User_management_model->get_user_managed_areas_with_children($user_id, $currentOrgId);
			if (!empty($accessible_areas)) {
				$data['member_areas'] = $this->Member_area_model->get_member_areas_by_idx($currentOrgId, $accessible_areas);
			} else {
				$data['member_areas'] = array();
			}
		}

		// 활성화된 그룹의 출석타입 정보 가져오기
		$this->load->model('Attendance_model');
		$data['attendance_types'] = $this->Attendance_model->get_attendance_types($currentOrgId);


		$this->load->view('qrcheck', $data);
	}

	public function save_single_attendance()
	{
		if ($this->input->is_ajax_request()) {
			$member_idx = $this->input->post('member_idx');
			$att_type_idx = $this->input->post('att_type_idx');
			$org_id = $this->input->post('org_id');
			$att_date = $this->input->post('att_date');
			$selected_value = $this->input->post('selected_value');

			// att_date에 해당하는 일요일 날짜 계산
			$sunday_date = $this->get_sunday_of_week($att_date);
			$att_year = date('Y', strtotime($sunday_date));

			// 기존 출석 데이터 삭제 (필요시)
			$this->load->model('Attendance_model');
			$this->Attendance_model->delete_attendance_by_member_and_type($member_idx, $att_type_idx, $sunday_date, $att_year);

			// 새로운 출석 데이터 저장
			$data = array(
				'att_date' => $sunday_date,
				'att_type_idx' => $att_type_idx,
				'member_idx' => $member_idx,
				'org_id' => $org_id,
				'att_year' => $att_year,
				'att_value' => $selected_value ?: 0,
				'regi_date' => date('Y-m-d H:i:s'),
				'modi_date' => date('Y-m-d')
			);

			$result = $this->Attendance_model->save_single_attendance($data);

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

			// 권한 확인 - 해당 그룹에 회원를 추가할 권한이 있는지 확인
			$user_id = $this->session->userdata('user_id');
			$master_yn = $this->session->userdata('master_yn');
			$user_level = $this->User_model->get_org_user_level($user_id, $org_id);

			if ($user_level < 10 && $master_yn !== 'Y') {
				$accessible_areas = $this->User_management_model->get_user_managed_areas_with_children($user_id, $org_id);
				if (!in_array($area_idx, $accessible_areas)) {
					echo json_encode(array('status' => 'error', 'message' => '해당 그룹에 회원을 추가할 권한이 없습니다.'));
					return;
				}
			}

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

	/**
	 * 역할: 사용자 권한에 따라 관리 가능한 그룹의 회원만 조회
	 */
	public function get_members()
	{
		if ($this->input->is_ajax_request()) {
			$org_id = $this->input->post('org_id');
			$level = $this->input->post('level');
			$start_date = $this->input->post('start_date');
			$end_date = $this->input->post('end_date');

			$user_id = $this->session->userdata('user_id');
			$master_yn = $this->session->userdata('master_yn');
			$user_level = $this->User_model->get_org_user_level($user_id, $org_id);

			$this->load->model('Member_model');

			// 최고관리자 또는 마스터인 경우 전체 회원 조회
			if ($user_level >= 10 || $master_yn === 'Y') {
				$members = $this->Member_model->get_org_members($org_id, $level, $start_date, $end_date);
			} else {
				// 일반 관리자인 경우 관리 가능한 그룹의 회원만 조회
				$accessible_areas = $this->User_management_model->get_user_managed_areas_with_children($user_id, $org_id);


				if (!empty($accessible_areas)) {
					$members = $this->Member_model->get_org_members_by_areas($org_id, $accessible_areas, $level, $start_date, $end_date);
				} else {
					// 관리 권한이 없는 경우 빈 배열 반환
					$members = array();
				}
			}

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

			// att_date를 일요일 날짜로 계산
			$sunday_date = $this->get_sunday_of_week($att_date);
			$att_year = date('Y', strtotime($sunday_date));

			$this->load->model('Attendance_model');

			// 해당 날짜의 모든 출석 정보 삭제
			$this->Attendance_model->delete_attendance_by_date_range($member_idx, $start_date, $end_date);

			if (!empty($attendance_data) && is_array($attendance_data)) {
				foreach ($attendance_data as $att_type_idx) {
					$data = array(
						'att_date' => $sunday_date, // 일요일 날짜로 저장
						'att_type_idx' => $att_type_idx,
						'member_idx' => $member_idx,
						'org_id' => $org_id,
						'att_year' => $att_year, // att_year 추가
						'regi_date' => date('Y-m-d H:i:s'), // 등록일 추가
						'modi_date' => date('Y-m-d') // 변경일 추가
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


	/**
	 * get_same_members 함수 수정 - category_idx 제거
	 */
	public function get_same_members()
	{
		if ($this->input->is_ajax_request()) {
			$member_idx = $this->input->post('member_idx');
			$org_id = $this->input->post('org_id');
			$area_idx = $this->input->post('area_idx');
			$start_date = $this->input->post('start_date');
			$end_date = $this->input->post('end_date');

			// 권한 확인
			$user_id = $this->session->userdata('user_id');
			$master_yn = $this->session->userdata('master_yn');
			$user_level = $this->User_model->get_org_user_level($user_id, $org_id);

			if ($user_level < 10 && $master_yn !== 'Y') {
				$accessible_areas = $this->User_management_model->get_user_managed_areas_with_children($user_id, $org_id);
				if (!in_array($area_idx, $accessible_areas)) {
					echo json_encode(array('status' => 'error', 'message' => '해당 그룹을 조회할 권한이 없습니다.'));
					return;
				}
			}

			$this->load->model('Member_model');
			$same_members = $this->Member_model->get_same_members($member_idx, $org_id, $area_idx, $start_date, $end_date);

			// 출석 유형 정보 가져오기
			$this->load->model('Attendance_model');
			$att_types = $this->Attendance_model->get_attendance_types($org_id);

			// 메모 정보 가져오기 - 일요일 날짜로 조회
			if ($same_members) {
				$sunday_date = $this->get_sunday_of_week($start_date);

				$this->load->model('Memo_model');

				foreach ($same_members as &$member) {
					// 해당 회원의 해당 주차 메모 조회
					$memo_data = $this->Memo_model->get_member_memo_by_date($member['member_idx'], $sunday_date);
					$member['memo_content'] = $memo_data ? $memo_data['memo_content'] : '';
				}
				unset($member); // 참조 해제
			}

			if ($same_members) {
				$response = array('status' => 'success', 'members' => $same_members, 'att_types' => $att_types);
			} else {
				$response = array('status' => 'error');
			}

			echo json_encode($response);
		}
	}


	/**
	 * 역할: 메모 데이터 저장 시 att_date 포함하여 CRUD 처리
	 */
	public function save_memo_data()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$memo_data_json = $this->input->post('memo_data');
		$org_id = $this->input->post('org_id');

		if (!$memo_data_json || !$org_id) {
			echo json_encode(array('status' => 'error', 'message' => '필수 정보가 누락되었습니다.'));
			return;
		}

		// 권한 확인
		$user_id = $this->session->userdata('user_id');
		$master_yn = $this->session->userdata('master_yn');
		$user_level = $this->User_model->get_org_user_level($user_id, $org_id);

		if ($user_level < 10 && $master_yn !== 'Y') {
			// 일반 관리자는 관리 가능한 그룹의 회원들만 메모 작성 가능
			$accessible_areas = $this->User_management_model->get_user_managed_areas_with_children($user_id, $org_id);
			if (empty($accessible_areas)) {
				echo json_encode(array('status' => 'error', 'message' => '메모를 작성할 권한이 없습니다.'));
				return;
			}
		}

		$memo_data = json_decode($memo_data_json, true);
		if (!$memo_data || !is_array($memo_data)) {
			echo json_encode(array('status' => 'error', 'message' => '메모 데이터가 올바르지 않습니다.'));
			return;
		}

		$this->load->model('Memo_model');
		$this->db->trans_start();

		try {
			foreach ($memo_data as $memo_item) {
				if (!isset($memo_item['member_idx']) || !$memo_item['member_idx']) {
					continue;
				}

				$member_idx = $memo_item['member_idx'];
				$memo_content = isset($memo_item['memo_content']) ? trim($memo_item['memo_content']) : '';
				$memo_date = isset($memo_item['memo_date']) ? $memo_item['memo_date'] : date('Y-m-d');

				// 권한 확인 - 해당 회원이 관리 가능한 그룹에 속하는지 확인
				if ($user_level < 10 && $master_yn !== 'Y') {
					$this->load->model('Member_model');
					$member_area = $this->Member_model->get_member_area($member_idx);
					if (!$member_area || !in_array($member_area['area_idx'], $accessible_areas)) {
						continue; // 권한이 없는 회원은 건너뛰기
					}
				}

				// 기존 메모 확인 (해당 날짜의 메모)
				$existing_memo = $this->Memo_model->get_member_memo_by_date($member_idx, $memo_date);

				if (!empty($memo_content)) {
					// 메모 내용이 있는 경우
					if ($existing_memo) {
						// 기존 메모 수정
						$update_data = array(
							'memo_content' => $memo_content,
							'modi_date' => date('Y-m-d H:i:s'),
							'att_date' => $memo_date  // att_date 업데이트
						);
						$this->Memo_model->update_memo($existing_memo['idx'], $update_data);
					} else {
						// 새 메모 추가
						$insert_data = array(
							'memo_type' => 1, // 일반 메모
							'memo_content' => $memo_content,
							'regi_date' => $memo_date . ' ' . date('H:i:s'),
							'user_id' => $user_id,
							'member_idx' => $member_idx,
							'att_date' => $memo_date  // att_date 포함하여 저장
						);
						$this->Memo_model->save_memo($insert_data);
					}
				} else {
					// 메모 내용이 비어있으면 기존 메모 삭제
					if ($existing_memo) {
						$this->Memo_model->delete_memo($existing_memo['idx']);
					}
				}
			}

			$this->db->trans_complete();

			if ($this->db->trans_status() === FALSE) {
				echo json_encode(array('status' => 'error', 'message' => '메모 저장에 실패했습니다.'));
				return;
			}

			echo json_encode(array('status' => 'success', 'message' => '메모가 저장되었습니다.'));

		} catch (Exception $e) {
			$this->db->trans_rollback();
			log_message('error', 'Memo save error: ' . $e->getMessage());
			echo json_encode(array('status' => 'error', 'message' => '메모 저장 중 오류가 발생했습니다.'));
		}
	}


	/**
	 * 파일 위치: application/controllers/Qrcheck.php
	 * 역할: 지난주 출석 데이터 조회 함수 - 권한 체크 제거
	 */
	public function get_last_week_attendance()
	{
		if ($this->input->is_ajax_request()) {
			$org_id = $this->input->post('org_id');
			$area_idx = $this->input->post('area_idx');
			$att_date = $this->input->post('att_date'); // 지난주 일요일 날짜를 직접 받기

			// 기본 파라미터 검증
			if (!$org_id || !$area_idx || !$att_date) {
				echo json_encode(array('status' => 'error', 'message' => '필수 정보가 누락되었습니다.'));
				return;
			}

			// 권한 확인 제거 - 그룹출석 화면에 접근할 수 있는 사용자는 누구나 지난주 출석 조회 가능
			// 기존 권한 체크 코드 주석처리 또는 제거
			/*
			$user_id = $this->session->userdata('user_id');
			$master_yn = $this->session->userdata('master_yn');
			$user_level = $this->User_model->get_org_user_level($user_id, $org_id);

			if ($user_level < 10 && $master_yn !== 'Y') {
				$accessible_areas = $this->User_management_model->get_user_managed_areas_with_children($user_id, $org_id);
				if (!in_array($area_idx, $accessible_areas)) {
					echo json_encode(array('status' => 'error', 'message' => '해당 그룹을 조회할 권한이 없습니다.'));
					return;
				}
			}
			*/

			$this->load->model('Attendance_model');

			// 정확한 지난주 일요일 날짜로 조회
			$attendance_data = $this->Attendance_model->get_org_member_attendance($org_id, $area_idx, $att_date, $att_date);
			$att_types = $this->Attendance_model->get_attendance_types($org_id);

			$response = array(
				'status' => 'success',
				'attendance_data' => $attendance_data,
				'att_types' => $att_types
			);

			echo json_encode($response);
		}
	}

	/**
	 * save_attendance_data_with_cleanup 함수 수정
	 */
	public function save_attendance_data_with_cleanup()
	{
		if ($this->input->is_ajax_request()) {
			$attendance_data = json_decode($this->input->post('attendance_data'), true);
			$org_id = $this->input->post('org_id');
			$start_date = $this->input->post('start_date');
			$end_date = $this->input->post('end_date');
			$att_date = $this->input->post('att_date');

			if (!$attendance_data || !$org_id || !$att_date) {
				echo json_encode(array('status' => 'error', 'message' => '필수 정보가 누락되었습니다.'));
				return;
			}

			// 권한 확인
			$user_id = $this->session->userdata('user_id');
			$master_yn = $this->session->userdata('master_yn');
			$user_level = $this->User_model->get_org_user_level($user_id, $org_id);

			if ($user_level < 10 && $master_yn !== 'Y') {
				$accessible_areas = $this->User_management_model->get_user_managed_areas_with_children($user_id, $org_id);
				if (empty($accessible_areas)) {
					echo json_encode(array('status' => 'error', 'message' => '출석 정보를 수정할 권한이 없습니다.'));
					return;
				}
			}

			// att_date에 해당하는 일요일 날짜 계산
			$sunday_date = $this->get_sunday_of_week($att_date);
			$att_year = date('Y', strtotime($sunday_date));

			$this->db->trans_start();

			try {
				// 전체 회원 목록 추출
				$all_member_indices = array_unique(array_column($attendance_data, 'member_idx'));

				if (!empty($all_member_indices)) {
					// 1. 해당 일요일 날짜의 모든 회원 출석 정보 삭제 (메모용 더미 제외)
					$this->db->where_in('member_idx', $all_member_indices);
					$this->db->where('org_id', $org_id);
					$this->db->where('att_date', $sunday_date);
					$this->db->where('att_year', $att_year);
					$this->db->where('att_type_idx >', 0); // 메모용 더미(att_type_idx = 0) 제외
					$delete_result = $this->db->delete('wb_member_att');

					log_message('info', 'Deleted attendance records for members: ' . implode(',', $all_member_indices) . ' on Sunday date: ' . $sunday_date);

					// 2. 새로운 출석 정보 삽입 (has_data가 true인 것만)
					$insert_success = true;
					foreach ($attendance_data as $item) {
						// has_data가 false이거나 att_type_idx가 없으면 건너뛰기 (삭제만 처리됨)
						if (!isset($item['has_data']) || !$item['has_data'] || empty($item['att_type_idx'])) {
							continue;
						}

						$member_idx = $item['member_idx'];
						$att_type_idx = $item['att_type_idx'];

						$data = array(
							'member_idx' => $member_idx,
							'att_date' => $sunday_date,
							'att_type_idx' => $att_type_idx,
							'org_id' => $org_id,
							'att_year' => $att_year,
							'regi_date' => date('Y-m-d H:i:s'),
							'modi_date' => date('Y-m-d')
						);

						$result = $this->db->insert('wb_member_att', $data);
						if (!$result) {
							$insert_success = false;
							log_message('error', 'Failed to insert attendance for member: ' . $member_idx . ', att_type: ' . $att_type_idx);
							break;
						}
					}

					if (!$insert_success) {
						$this->db->trans_rollback();
						echo json_encode(array('status' => 'error', 'message' => '출석 정보 저장 중 오류가 발생했습니다.'));
						return;
					}
				}

				$this->db->trans_complete();

				if ($this->db->trans_status() === FALSE) {
					echo json_encode(array('status' => 'error', 'message' => '출석 저장에 실패했습니다.'));
					return;
				}

				echo json_encode(array('status' => 'success', 'message' => '출석이 저장되었습니다.'));

			} catch (Exception $e) {
				$this->db->trans_rollback();
				log_message('error', 'Attendance save with cleanup error: ' . $e->getMessage());
				echo json_encode(array('status' => 'error', 'message' => '출석 저장 중 오류가 발생했습니다.'));
			}
		}
	}

	/**
	 * 역할: 일요일 날짜 계산을 위한 공통 함수
	 */
	private function get_sunday_of_week($date)
	{
		$formatted_date = str_replace('.', '-', $date);
		$dt = new DateTime($formatted_date);
		$days_from_sunday = $dt->format('w'); // 0=일요일, 1=월요일...

		if ($days_from_sunday > 0) {
			$dt->sub(new DateInterval('P' . $days_from_sunday . 'D'));
		}

		return $dt->format('Y-m-d');
	}

	/**
	 * 역할: 기본 출석 데이터 저장 함수 - 일요일 날짜로 저장
	 */
	public function save_attendance_data()
	{
		if ($this->input->is_ajax_request()) {
			$attendance_data = json_decode($this->input->post('attendance_data'), true);
			$org_id = $this->input->post('org_id');
			$start_date = $this->input->post('start_date');
			$end_date = $this->input->post('end_date');

			if (!$attendance_data || !$org_id) {
				echo json_encode(array('status' => 'error', 'message' => '필수 정보가 누락되었습니다.'));
				return;
			}

			// 권한 확인
			$user_id = $this->session->userdata('user_id');
			$master_yn = $this->session->userdata('master_yn');
			$user_level = $this->User_model->get_org_user_level($user_id, $org_id);

			if ($user_level < 10 && $master_yn !== 'Y') {
				$accessible_areas = $this->User_management_model->get_user_managed_areas_with_children($user_id, $org_id);
				if (empty($accessible_areas)) {
					echo json_encode(array('status' => 'error', 'message' => '출석 정보를 수정할 권한이 없습니다.'));
					return;
				}
			}

			$this->db->trans_start();

			try {
				// 회원별로 그룹화
				$member_attendance_data = array();

				foreach ($attendance_data as $item) {
					$member_idx = $item['member_idx'];
					$att_type_idx = $item['att_type_idx'];
					$att_date = $item['att_date'];

					// att_date에 해당하는 일요일 날짜 계산
					$sunday_date = $this->get_sunday_of_week($att_date);
					$att_year = date('Y', strtotime($sunday_date));

					if (!isset($member_attendance_data[$member_idx])) {
						$member_attendance_data[$member_idx] = array();
					}

					// 일요일 날짜로 저장
					$member_attendance_data[$member_idx][] = array(
						'att_date' => $sunday_date,
						'att_type_idx' => $att_type_idx,
						'att_year' => $att_year
					);
				}

				// 기존 출석 데이터 삭제 및 새로운 데이터 삽입
				foreach ($member_attendance_data as $member_idx => $attendance_list) {
					// 해당 회원의 해당 기간 출석기록 삭제
					foreach ($attendance_list as $attendance) {
						$sunday_date = $attendance['att_date'];
						$att_year = $attendance['att_year'];

						$this->db->where('member_idx', $member_idx);
						$this->db->where('org_id', $org_id);
						$this->db->where('att_date', $sunday_date);
						$this->db->where('att_year', $att_year);
						$this->db->delete('wb_member_att');
					}

					// 새로운 출석기록 삽입
					foreach ($attendance_list as $attendance) {
						$data = array(
							'member_idx' => $member_idx,
							'att_date' => $attendance['att_date'],
							'att_type_idx' => $attendance['att_type_idx'],
							'org_id' => $org_id,
							'att_year' => $attendance['att_year'],
							'regi_date' => date('Y-m-d H:i:s'),
							'modi_date' => date('Y-m-d')
						);

						$this->db->insert('wb_member_att', $data);
					}
				}

				$this->db->trans_complete();

				if ($this->db->trans_status() === FALSE) {
					echo json_encode(array('status' => 'error', 'message' => '출석 저장에 실패했습니다.'));
					return;
				}

				echo json_encode(array('status' => 'success', 'message' => '출석이 저장되었습니다.'));

			} catch (Exception $e) {
				$this->db->trans_rollback();
				log_message('error', 'Attendance save error: ' . $e->getMessage());
				echo json_encode(array('status' => 'error', 'message' => '출석 저장 중 오류가 발생했습니다.'));
			}
		}
	}


}
