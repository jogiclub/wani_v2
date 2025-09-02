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

		// 선택된 모드 설정 (기본값: mode-1)
		$data['mode'] = $this->input->post('mode') ?? 'mode-1';

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

			// 권한 확인 - 해당 그룹에 멤버를 추가할 권한이 있는지 확인
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
	 * 파일 위치: application/controllers/Qrcheck.php - get_members() 함수
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



	/**
	 * 파일 위치: application/controllers/Qrcheck.php - save_memo_data() 함수 수정
	 * 역할: att_idx 연결 및 메모 저장 개선
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
					$member_area = $this->Member_model->get_member_area($member_idx);
					if (!$member_area || !in_array($member_area['area_idx'], $accessible_areas)) {
						continue; // 권한이 없는 회원은 건너뛰기
					}
				}

				$att_idx = null;

				// 메모 내용이 있는 경우에만 att_idx 생성/조회
				if (!empty($memo_content)) {
					// 해당 날짜의 기존 출석 레코드 확인
					$this->db->select('att_idx');
					$this->db->from('wb_member_att');
					$this->db->where('member_idx', $member_idx);
					$this->db->where('att_date', $memo_date);
					$this->db->where('org_id', $org_id);
					$this->db->order_by('att_idx', 'DESC');
					$this->db->limit(1);
					$att_query = $this->db->get();
					$att_record = $att_query->row_array();

					if ($att_record) {
						$att_idx = $att_record['att_idx'];
					} else {
						// 출석 레코드가 없으면 메모용 더미 레코드 생성
						$att_data = array(
							'member_idx' => $member_idx,
							'att_date' => $memo_date,
							'att_type_idx' => 0, // 메모 전용 더미 타입
							'org_id' => $org_id
						);
						$this->db->insert('wb_member_att', $att_data);
						$att_idx = $this->db->insert_id();
					}
				}

				// 기존 메모 확인 (해당 날짜의 메모)
				$existing_memo = $this->Memo_model->get_member_memo_by_date($member_idx, $memo_date);

				if (!empty($memo_content)) {
					// 메모 내용이 있는 경우
					if ($existing_memo) {
						// 기존 메모 수정 (att_idx도 업데이트)
						$update_data = array(
							'memo_content' => $memo_content,
							'att_idx' => $att_idx,
							'modi_date' => date('Y-m-d H:i:s')
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
							'att_idx' => $att_idx,
							'del_yn' => 'N'
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
	 * 파일 위치: application/controllers/Qrcheck.php - get_same_members() 함수 수정
	 * 역할: 메모 정보도 함께 조회하도록 개선
	 */
	public function get_same_members()
	{
		if ($this->input->is_ajax_request()) {
			$member_idx = $this->input->post('member_idx');
			$org_id = $this->input->post('org_id');
			$area_idx = $this->input->post('area_idx');
			$start_date = $this->input->post('start_date');
			$end_date = $this->input->post('end_date');

			// 권한 확인 - 해당 그룹에 접근할 권한이 있는지 확인
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
			$this->load->model('Memo_model');

			// 해당 그룹의 회원들과 출석 정보 조회
			$same_members = $this->Member_model->get_same_members($member_idx, $org_id, $area_idx, $start_date, $end_date);

			// 각 회원에 대해 메모 정보도 추가
			if ($same_members) {
				foreach ($same_members as &$member) {
					// 해당 기간 내 메모 조회 (가장 최근 메모)
					$memo_info = $this->Memo_model->get_member_memo_by_date_range($member['member_idx'], $start_date, $end_date);
					$member['memo_content'] = $memo_info ? $memo_info['memo_content'] : '';
					$member['att_idx'] = $memo_info ? $memo_info['att_idx'] : null;
				}
			}

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


	/**
	 * 파일 위치: application/controllers/Qrcheck.php - get_last_week_attendance() 함수 디버깅 수정
	 * 역할: 지난 주 출석 정보를 올바르게 조회하여 반환 (디버깅 강화)
	 */
	public function get_last_week_attendance()
	{
		if ($this->input->is_ajax_request()) {
			$org_id = $this->input->post('org_id');
			$area_idx = $this->input->post('area_idx');
			$start_date = $this->input->post('start_date');
			$end_date = $this->input->post('end_date');

			// 디버깅용 로그
			log_message('debug', 'get_last_week_attendance called with params: org_id=' . $org_id . ', area_idx=' . $area_idx . ', start_date=' . $start_date . ', end_date=' . $end_date);

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

			// 단계적 디버깅을 위한 쿼리 분리

			// 1단계: 해당 그룹의 회원들 먼저 조회
			$this->db->select('member_idx, member_name');
			$this->db->from('wb_member');
			$this->db->where('org_id', $org_id);
			$this->db->where('area_idx', $area_idx);
			$this->db->where('del_yn', 'N');
			$members_query = $this->db->get();
			$members = $members_query->result_array();

			log_message('debug', 'Found ' . count($members) . ' members in area_idx: ' . $area_idx);

			if (empty($members)) {
				echo json_encode(array('status' => 'error', 'message' => '해당 그룹에 회원이 없습니다.'));
				return;
			}

			$member_indices = array_column($members, 'member_idx');
			log_message('debug', 'Member indices: ' . implode(',', $member_indices));

			// 2단계: 해당 회원들의 지난주 출석 정보 조회
			$this->db->select('member_idx, att_type_idx, att_date');
			$this->db->from('wb_member_att');
			$this->db->where_in('member_idx', $member_indices);
			$this->db->where('att_date >=', $start_date);
			$this->db->where('att_date <=', $end_date);
			$this->db->where('att_type_idx >', 0); // 메모용 더미 레코드 제외
			$this->db->order_by('member_idx, att_type_idx');

			$attendance_query = $this->db->get();
			$attendance_records = $attendance_query->result_array();

			log_message('debug', 'Attendance query: ' . $this->db->last_query());
			log_message('debug', 'Found ' . count($attendance_records) . ' attendance records');

			// 3단계: 회원별로 출석 타입 인덱스 그룹핑
			$attendance_data = array();
			foreach ($attendance_records as $record) {
				$member_idx = $record['member_idx'];
				if (!isset($attendance_data[$member_idx])) {
					$attendance_data[$member_idx] = array();
				}
				$attendance_data[$member_idx][] = $record['att_type_idx'];
			}

			log_message('debug', 'Processed attendance data: ' . print_r($attendance_data, true));

			// 4단계: 출석 타입 정보 가져오기
			$this->load->model('Attendance_model');
			$att_types = $this->Attendance_model->get_attendance_types($org_id);

			$response = array(
				'status' => 'success',
				'attendance_data' => $attendance_data,
				'att_types' => $att_types,
				'debug_info' => array(
					'members_count' => count($members),
					'member_indices' => $member_indices,
					'attendance_records_count' => count($attendance_records),
					'date_range' => $start_date . ' to ' . $end_date,
					'query' => $this->db->last_query()
				)
			);

			log_message('debug', 'Final response: ' . json_encode($response));
			echo json_encode($response);
		}
	}

	/**
	 * 파일 위치: application/controllers/Qrcheck.php
	 * 역할: 모든 회원의 출석 데이터를 처리하고 불필요한 데이터 정리
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

			$this->db->trans_start();

			try {
				// 전체 회원 목록 추출
				$all_member_indices = array_unique(array_column($attendance_data, 'member_idx'));

				if (!empty($all_member_indices)) {
					// 1. 해당 날짜의 모든 회원 출석 정보 삭제 (메모용 더미 제외)
					$this->db->where_in('member_idx', $all_member_indices);
					$this->db->where('org_id', $org_id);
					$this->db->where('att_date', $att_date);
					$this->db->where('att_type_idx >', 0); // 메모용 더미(att_type_idx = 0) 제외
					$delete_result = $this->db->delete('wb_member_att');

					log_message('info', 'Deleted attendance records for members: ' . implode(',', $all_member_indices) . ' on date: ' . $att_date);

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
							'att_date' => $att_date,
							'att_type_idx' => $att_type_idx,
							'org_id' => $org_id
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

}
