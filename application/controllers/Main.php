<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Main extends My_Controller
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
			redirect('mypage');
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

		$this->load->view('main', $data);
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


	public function get_active_members()
	{
		if ($this->input->is_ajax_request()) {
			$org_id = $this->input->post('org_id');
			$five_weeks_ago = date('Y-m-d', strtotime('-5 weeks'));

			$this->load->model('Member_model');
			$active_members = $this->Member_model->get_active_members($org_id, $five_weeks_ago);

			echo json_encode($active_members);
		}
	}

	public function save_member_info()
	{
		if ($this->input->is_ajax_request()) {
			$member_idx = $this->input->post('member_idx');
			$grade = $this->input->post('grade');
			$member_name = $this->input->post('member_name');
			$member_nick = $this->input->post('member_nick');
			$member_phone = $this->input->post('member_phone');
			$member_birth = $this->input->post('member_birth');
			$school = $this->input->post('school');
			$member_address = $this->input->post('member_address');
			$member_etc = $this->input->post('member_etc');
			$leader_yn = $this->input->post('leader_yn') ? 'Y' : 'N';
			$area_idx = $this->input->post('area_idx');

			$new_yn = $this->input->post('new_yn') ? 'Y' : 'N';


			$data = array(
				'grade' => $grade,
				'member_name' => $member_name,
				'member_nick' => $member_nick,
				'member_phone' => $member_phone,
				'member_birth' => $member_birth,
				'school' => $school,
				'member_address' => $member_address,
				'member_etc' => $member_etc,
				'leader_yn' => $leader_yn,
				'area_idx' => $area_idx,

				'new_yn' => $new_yn
			);

			// 사진 업로드 처리
			if (!empty($_FILES['photo']['name'])) {
				$org_id = $this->input->post('org_id');
				$upload_path = './uploads/member_photos/' . $org_id . '/';

				// 1. 기존 파일 확인 및 삭제
				$existing_photo = $this->Member_model->get_member_by_idx($member_idx);
				if (!empty($existing_photo['photo'])) {
					$existing_file = $upload_path . $existing_photo['photo'];
					if (file_exists($existing_file)) {
						unlink($existing_file);
					}
				}

				// 2. 업로드 설정
				$config['upload_path'] = $upload_path;
				$config['allowed_types'] = 'gif|jpg|png';
				$config['max_size'] = 5120;
				$config['file_name'] = 'member_' . $member_idx . '_' . time(); // 타임스탬프 추가
				$config['overwrite'] = true;

				// 3. 업로드 경로 확인 및 생성
				if (!is_dir($config['upload_path'])) {
					if (!mkdir($config['upload_path'], 0777, true)) {
						$response = array('status' => 'error', 'message' => 'Failed to create upload directory');
						echo json_encode($response);
						return;
					}
					chmod($config['upload_path'], 0777);
				}

				$this->load->library('upload', $config);

				if ($this->upload->do_upload('photo')) {
					$upload_data = $this->upload->data();

					// 4. 이미지 리사이즈
					$this->load->library('image_lib');
					$image_config['image_library'] = 'gd2';
					$image_config['source_image'] = $upload_data['full_path'];
					$image_config['maintain_ratio'] = TRUE;
					$image_config['width'] = 200;

					$this->image_lib->clear();
					$this->image_lib->initialize($image_config);

					if (!$this->image_lib->resize()) {
						$response = array('status' => 'error', 'message' => $this->image_lib->display_errors());
						echo json_encode($response);
						return;
					}

					$data['photo'] = $upload_data['file_name'];
					$photo_url = base_url('uploads/member_photos/' . $org_id . '/' . $upload_data['file_name']);
					$response = array('status' => 'success', 'photo_url' => $photo_url);
				} else {
					$upload_error = $this->upload->display_errors();
					log_message('error', 'Image upload failed: ' . $upload_error);
					$response = array('status' => 'error', 'message' => $upload_error);
					echo json_encode($response);
					return;
				}
			}

			$this->load->model('Member_model');
			$result = $this->Member_model->update_member($member_idx, $data);

			if ($result) {
				if (!isset($response)) {
					$response = array('status' => 'success');
				}
			} else {
				$response = array('status' => 'error');
			}

			echo json_encode($response);
		}
	}

	public function save_memo()
	{
		if ($this->input->is_ajax_request()) {
			$member_idx = $this->input->post('member_idx');
			$memo_type = $this->input->post('memo_type');
			$memo_content = $this->input->post('memo_content');

			$data = array(
				'memo_type' => $memo_type,
				'memo_content' => $memo_content,
				'regi_date' => date('Y-m-d H:i:s'),
				'user_id' => $this->session->userdata('user_email'),
				'member_idx' => $member_idx
			);

			$this->load->model('Memo_model');
			$result = $this->Memo_model->save_memo($data);

			if ($result) {
				$response = array('status' => 'success');
			} else {
				$response = array('status' => 'error');
			}

			echo json_encode($response);
		}
	}

	public function get_memo_list()
	{
		if ($this->input->is_ajax_request()) {
			$member_idx = $this->input->post('member_idx');
			$page = $this->input->post('page');
			$limit = $this->input->post('limit');

			$offset = ($page - 1) * $limit;

			$this->load->model('Memo_model');
			$memo_list = $this->Memo_model->get_memo_list($member_idx, $limit, $offset);

			if ($memo_list) {
				$response = array('status' => 'success', 'data' => $memo_list);
			} else {
				$response = array('status' => 'error');
			}

			echo json_encode($response);
		}
	}


	public function delete_memo()
	{
		if ($this->input->is_ajax_request()) {
			$idx = $this->input->post('idx');

			$this->load->model('Memo_model');
			$result = $this->Memo_model->delete_memo($idx);

			if ($result) {
				$response = array('status' => 'success');
			} else {
				$response = array('status' => 'error');
			}

			echo json_encode($response);
		}
	}


	public function get_memo_counts()
	{
		if ($this->input->is_ajax_request()) {
			$org_id = $this->input->post('org_id');
			$start_date = $this->input->post('start_date');
			$end_date = $this->input->post('end_date');

			$this->load->model('Memo_model');
			$memo_counts = $this->Memo_model->get_memo_counts($org_id, $start_date, $end_date);

			echo json_encode($memo_counts);
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

//            print_r($level);
//            exit;
//            $level = 2;
			$start_date = $this->input->post('start_date');
			$end_date = $this->input->post('end_date');

			$this->load->model('Member_model');

			$members = $this->Member_model->get_org_members($org_id, $level, $start_date, $end_date);
			echo json_encode($members);
		}
	}

	public function delete_member()
	{
		if ($this->input->is_ajax_request()) {
			$member_idx = $this->input->post('member_idx');

			$data = array(
				'del_yn' => 'Y',
				'del_date' => date('Y-m-d H:i:s')
			);

			$this->load->model('Member_model');
			$result = $this->Member_model->update_member($member_idx, $data);

			if ($result) {
				$response = array('status' => 'success');
			} else {
				$response = array('status' => 'error');
			}

			echo json_encode($response);
		}
	}

	/*
		public function update_multiple_members() {
			if ($this->input->is_ajax_request()) {
				$member_idx = $this->input->post('memberIdx');
				$grade = $this->input->post('grade');
				$area = $this->input->post('area');
				$all_grade_check = $this->input->post('allGradeCheck') === 'true';
				$all_area_check = $this->input->post('allAreaCheck') === 'true';

				$data = array(
					'modi_date' => date('Y-m-d H:i:s')
				);

				if ($all_grade_check) {
					$data['grade'] = $grade;
				}

				if ($all_area_check) {
					$data['area'] = $area;
				}

				$this->load->model('Member_model');
				$result = $this->Member_model->update_multiple_members($member_idx, $data, $all_grade_check, $all_area_check);

				if ($result) {
					$response = array('status' => 'success');
				} else {
					$response = array('status' => 'error');
				}

				echo json_encode($response);
			}
		}

	*/

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
//            print_r($attendance_types);
//            exit;
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

			// 멤버별 출석 정보 그룹화
			$member_attendance_data = array();
			foreach ($attendance_data as $item) {
				$member_idx = $item['member_idx'];
				$att_date = $item['att_date'];
				$att_type_idx = $item['att_type_idx'];

				if (!isset($member_attendance_data[$member_idx])) {
					$member_attendance_data[$member_idx] = array();
				}

				$member_attendance_data[$member_idx][$att_date] = $att_type_idx;
			}

			$this->load->model('Attendance_model');
			$result = $this->Attendance_model->save_batch_attendance($member_attendance_data, $org_id, $start_date, $end_date);

			if ($result) {
				echo json_encode(array('status' => 'success', 'message' => '출석이 저장되었습니다.'));
			} else {
				echo json_encode(array('status' => 'error', 'message' => '출석 저장에 실패했습니다.'));
			}
		}
	}

// Main.php
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


	public function add_area()
	{
		if ($this->input->is_ajax_request()) {
			$area_name = $this->input->post('area_name');
			$org_id = $this->input->post('org_id');

			// Member_area_model 로드
			$this->load->model('Member_area_model');

			// 현재 최대 order 값 가져오기
			$max_order = $this->Member_area_model->get_max_order($org_id);
			$new_order = $max_order + 1;

			$data = array(
				'area_name' => $area_name,
				'area_order' => $new_order,
				'org_id' => $org_id
			);

			$result = $this->Member_area_model->add_area($data);

			if ($result) {
				$response = array('status' => 'success');
			} else {
				$response = array('status' => 'error');
			}

			echo json_encode($response);
		}
	}

	public function get_areas()
	{
		if ($this->input->is_ajax_request()) {
			$org_id = $this->input->post('org_id');

			$this->load->model('Member_area_model');
			$areas = $this->Member_area_model->get_member_areas($org_id);

			echo json_encode(['areas' => $areas]);
		}
	}

	public function update_areas()
	{
		if ($this->input->is_ajax_request()) {
			$areas = json_decode($this->input->post('areas'), true);
			$org_id = $this->input->post('org_id');

			$this->load->model('Member_area_model');
			$success = $this->Member_area_model->update_areas($areas);

			echo json_encode(['status' => $success ? 'success' : 'error']);
		}
	}

	public function delete_area()
	{
		if ($this->input->is_ajax_request()) {
			$area_idx = $this->input->post('area_idx');

			// Member_area_model 로드
			$this->load->model('Member_area_model');

			// 해당 area_idx를 사용하는 멤버가 있는지 확인
			$members_count = $this->Member_area_model->check_area_members($area_idx);

			if ($members_count > 0) {
				echo json_encode([
					'status' => 'error',
					'message' => '소속된 멤버가 있어 삭제가 불가합니다. 소속된 멤버를 삭제하거나 이동 후 진행하시기 바랍니다.'
				]);
				return;
			}

			// 멤버가 없는 경우 삭제 진행
			$result = $this->Member_area_model->delete_area($area_idx);

			if ($result) {
				echo json_encode([
					'status' => 'success',
					'message' => '소그룹이 삭제되었습니다.'
				]);
			} else {
				echo json_encode([
					'status' => 'error',
					'message' => '소그룹 삭제에 실패했습니다.'
				]);
			}
		}
	}


}
