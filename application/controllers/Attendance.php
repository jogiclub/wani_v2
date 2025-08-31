<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * 역할: 출석 관리 페이지 및 API 처리
 */

class Attendance extends My_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->library('session');
		$this->load->helper('url');
		$this->load->model('Org_model');
		$this->load->model('Member_model');
		$this->load->model('Member_area_model');
		$this->load->model('User_model');
		$this->load->model('Attendance_model');
	}

	/**
	 * 출석 관리 메인 페이지
	 */
	public function index()
	{
		// 로그인 체크
		if (!$this->session->userdata('user_id')) {
			redirect('login');
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

		// POST로 조직 변경 요청 처리
		$this->handle_org_change($data);

		$currentOrgId = $data['current_org']['org_id'];

		// 권한 확인
		if (!$this->check_org_access($currentOrgId)) {
			$this->handle_access_denied('해당 조직의 출석을 관리할 권한이 없습니다.');
			return;
		}



		// 현재 조직 정보를 JavaScript로 전달하기 위해 orgs 배열에 추가
		$data['orgs'] = array($data['current_org']);

		$this->load->view('attendance', $data);
	}

	/**
	 * 조직 트리 데이터 가져오기 (회원관리와 동일)
	 */
	public function get_org_tree()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$user_id = $this->session->userdata('user_id');
		$active_org_id = $this->input->cookie('activeOrg');

		if (!$active_org_id) {
			$master_yn = $this->session->userdata('master_yn');
			if ($master_yn === "N") {
				$orgs = $this->Org_model->get_user_orgs($user_id);
			} else {
				$orgs = $this->Org_model->get_user_orgs_master($user_id);
			}

			if (!empty($orgs)) {
				$active_org_id = $orgs[0]['org_id'];
				$this->input->set_cookie('activeOrg', $active_org_id, 86400);
			} else {
				echo json_encode(array());
				return;
			}
		}

		// 권한 확인
		$master_yn = $this->session->userdata('master_yn');
		if ($master_yn === "N") {
			$user_orgs = $this->Org_model->get_user_orgs($user_id);
		} else {
			$user_orgs = $this->Org_model->get_user_orgs_master($user_id);
		}

		$has_access = false;
		$current_org = null;
		foreach ($user_orgs as $org) {
			if ($org['org_id'] == $active_org_id) {
				$has_access = true;
				$current_org = $org;
				break;
			}
		}

		if (!$has_access) {
			echo json_encode(array());
			return;
		}

		$user_level = $this->User_model->get_org_user_level($user_id, $active_org_id);
		$accessible_areas = array();

		if ($user_level < 10 && $master_yn !== 'Y') {
			$this->load->model('User_management_model');
			$accessible_areas = $this->User_management_model->get_user_managed_areas_with_children($user_id, $active_org_id);
		}

		$areas_tree = $this->Member_area_model->get_member_areas_tree($active_org_id);

		$filter_areas_by_permission = function ($areas) use (&$filter_areas_by_permission, $user_level, $master_yn, $accessible_areas) {
			if ($user_level >= 10 || $master_yn === 'Y') {
				return $areas;
			}

			if (empty($accessible_areas)) {
				return array();
			}

			$filtered_areas = array();

			foreach ($areas as $area) {
				if (in_array($area['area_idx'], $accessible_areas)) {
					if (!empty($area['children'])) {
						$area['children'] = $filter_areas_by_permission($area['children']);
					}
					$filtered_areas[] = $area;
				} else {
					if (!empty($area['children'])) {
						$filtered_children = $filter_areas_by_permission($area['children']);
						if (!empty($filtered_children)) {
							$area['children'] = $filtered_children;
							$filtered_areas[] = $area;
						}
					}
				}
			}

			return $filtered_areas;
		};

		$filtered_areas_tree = $filter_areas_by_permission($areas_tree);

		$build_fancytree_nodes = function ($areas) use (&$build_fancytree_nodes, $active_org_id) {
			$nodes = array();

			foreach ($areas as $area) {
				$member_count = $this->Member_model->get_area_members_count_with_children($active_org_id, $area['area_idx']);

				$title = $area['area_name'];
				if ($member_count > 0) {
					$title .= ' (' . $member_count . '명)';
				}

				$node = array(
					'key' => 'area_' . $area['area_idx'],
					'title' => $title,
					'data' => array(
						'type' => 'area',
						'org_id' => $active_org_id,
						'area_idx' => $area['area_idx'],
						'parent_idx' => $area['parent_idx'],
						'member_count' => $member_count
					)
				);

				if (!empty($area['children'])) {
					$node['children'] = $build_fancytree_nodes($area['children']);
					$node['expanded'] = true;
				}

				$nodes[] = $node;
			}

			return $nodes;
		};

		$children = $build_fancytree_nodes($filtered_areas_tree);

		if ($user_level >= 10 || $master_yn === 'Y') {
			$org_total_members = $this->Member_model->get_org_member_count($active_org_id);
		} else {
			$org_total_members = 0;
			if (!empty($accessible_areas)) {
				$managed_areas = $this->User_management_model->get_user_managed_areas($user_id);
				foreach ($managed_areas as $area_idx) {
					$org_total_members += $this->Member_model->get_area_members_count_with_children($active_org_id, $area_idx);
				}
			}
		}

		$org_title = $current_org['org_name'];
		if ($org_total_members > 0) {
			$org_title .= ' (' . $org_total_members . '명)';
		}

		$org_node = array(
			'key' => 'org_' . $current_org['org_id'],
			'title' => $org_title,
			'data' => array(
				'type' => 'org',
				'org_id' => $current_org['org_id'],
				'member_count' => $org_total_members
			),
			'expanded' => true,
			'children' => $children
		);

		$tree_data = array($org_node);

		if ($user_level >= 10 || $master_yn === 'Y') {
			$unassigned_members_count = $this->Member_model->get_unassigned_members_count($active_org_id);

			$unassigned_node = array(
				'key' => 'unassigned_' . $active_org_id,
				'title' => '미분류 (' . $unassigned_members_count . '명)',
				'data' => array(
					'type' => 'unassigned',
					'org_id' => $active_org_id,
					'area_idx' => null,
					'member_count' => $unassigned_members_count
				)
			);
			$tree_data[] = $unassigned_node;
		}

		header('Content-Type: application/json');
		echo json_encode($tree_data);
	}

	/**
	 * 출석 유형 가져오기
	 */
	public function get_attendance_types()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$org_id = $this->input->post('org_id');
		if (!$org_id) {
			echo json_encode(array('success' => false, 'message' => '조직 ID가 필요합니다.'));
			return;
		}

		if (!$this->check_org_access($org_id)) {
			echo json_encode(array('success' => false, 'message' => '권한이 없습니다.'));
			return;
		}

		$attendance_types = $this->Attendance_model->get_attendance_types($org_id);

		echo json_encode(array(
			'success' => true,
			'data' => $attendance_types
		));
	}

	/**
	 * 출석 데이터 가져오기 (주별) - 수정된 함수 호출
	 */
	public function get_attendance_data()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$type = $this->input->post('type');
		$org_id = $this->input->post('org_id');
		$area_idx = $this->input->post('area_idx');
		$year = $this->input->post('year', true) ?: date('Y');

		if (!$org_id) {
			echo json_encode(array('success' => false, 'message' => '조직 ID가 필요합니다.'));
			return;
		}

		if (!$this->check_org_access($org_id)) {
			echo json_encode(array('success' => false, 'message' => '권한이 없습니다.'));
			return;
		}

		// 권한에 따른 회원 목록 가져오기 (기존 코드와 동일)
		$user_id = $this->session->userdata('user_id');
		$master_yn = $this->session->userdata('master_yn');
		$user_level = $this->User_model->get_org_user_level($user_id, $org_id);

		if ($type === 'unassigned') {
			if ($user_level < 10 && $master_yn !== 'Y') {
				echo json_encode(array('success' => false, 'message' => '미분류 그룹을 조회할 권한이 없습니다.'));
				return;
			}
			$members = $this->Member_model->get_unassigned_members($org_id);
		} else if ($type === 'area' && $area_idx) {
			if ($user_level < 10 && $master_yn !== 'Y') {
				$this->load->model('User_management_model');
				$accessible_areas = $this->User_management_model->get_user_managed_areas_with_children($user_id, $org_id);
				if (!in_array($area_idx, $accessible_areas)) {
					echo json_encode(array('success' => false, 'message' => '해당 그룹을 조회할 권한이 없습니다.'));
					return;
				}
			}
			$members = $this->Member_model->get_area_members_with_children($org_id, $area_idx);
		} else if ($type === 'org') {
			if ($user_level >= 10 || $master_yn === 'Y') {
				$members = $this->Member_model->get_org_members($org_id);
			} else {
				$this->load->model('User_management_model');
				$members = array();
				$managed_areas = $this->User_management_model->get_user_managed_areas($user_id);
				foreach ($managed_areas as $managed_area_idx) {
					$area_members = $this->Member_model->get_area_members_with_children($org_id, $managed_area_idx);
					$members = array_merge($members, $area_members);
				}

				$unique_members = array();
				$member_ids = array();
				foreach ($members as $member) {
					if (!in_array($member['member_idx'], $member_ids)) {
						$unique_members[] = $member;
						$member_ids[] = $member['member_idx'];
					}
				}
				$members = $unique_members;
			}
		} else {
			$members = array();
		}

		// 해당 연도의 일요일 날짜들 생성
		$sunday_dates = $this->get_sunday_dates($year);

		// 각 회원의 주별 출석데이터 가져오기 (연도 추가)
		$attendance_data = $this->get_weekly_attendance_data($members, $org_id, $sunday_dates, $year);

		echo json_encode(array(
			'success' => true,
			'data' => array(
				'members' => $members,
				'attendance_data' => $attendance_data,
				'sunday_dates' => $sunday_dates,
				'year' => $year
			)
		));
	}

	/**
	 * 주별 출석 상세 정보 조회 - 메모 정보 포함
	 */
	public function get_week_attendance_detail()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$org_id = $this->input->post('org_id');
		$sunday_date = $this->input->post('sunday_date');
		$member_indices = $this->input->post('member_indices');

		if (!$org_id || !$sunday_date || !$member_indices) {
			echo json_encode(array('success' => false, 'message' => '필수 정보가 누락되었습니다.'));
			return;
		}

		if (!$this->check_org_access($org_id)) {
			echo json_encode(array('success' => false, 'message' => '권한이 없습니다.'));
			return;
		}

		// 해당 주의 날짜 범위 계산 (일요일부터 토요일까지)
		$week_dates = $this->get_week_date_range($sunday_date);
		$year = date('Y', strtotime($sunday_date));

		// 출석유형 가져오기
		$attendance_types = $this->Attendance_model->get_attendance_types($org_id);

		// 해당 주간의 출석데이터 가져오기 (연도 조건 추가)
		$attendance_records = $this->Attendance_model->get_week_attendance_records(
			$org_id,
			$member_indices,
			$week_dates['start'],
			$week_dates['end'],
			$year
		);

		// 회원 정보 가져오기
		$members_info = $this->Member_model->get_members_by_indices($member_indices);

		// 메모 정보 가져오기 (att_idx 기준)
		$this->load->model('Memo_model');
		$memo_records = $this->Memo_model->get_attendance_memo_by_week($member_indices, $sunday_date);

		echo json_encode(array(
			'success' => true,
			'data' => array(
				'attendance_types' => $attendance_types,
				'attendance_records' => $attendance_records,
				'members_info' => $members_info,
				'week_dates' => $week_dates,
				'memo_records' => $memo_records
			)
		));
	}

	/**
	 * 출석 및 메모 데이터 통합 저장 함수 수정
	 */
	public function save_attendance_with_memo()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$org_id = $this->input->post('org_id');
		$attendance_data_json = $this->input->post('attendance_data');
		$memo_data_json = $this->input->post('memo_data');
		$att_date = $this->input->post('att_date');
		$year = $this->input->post('year') ?: date('Y');

		// 로깅 추가
		log_message('debug', 'Save attendance with memo - Org ID: ' . $org_id);
		log_message('debug', 'Attendance data: ' . $attendance_data_json);
		log_message('debug', 'Memo data: ' . $memo_data_json);
		log_message('debug', 'Att date: ' . $att_date);
		log_message('debug', 'Year: ' . $year);

		if (!$org_id || !$att_date) {
			echo json_encode(array('success' => false, 'message' => '필수 정보가 누락되었습니다.'));
			return;
		}

		if (!$this->check_org_access($org_id)) {
			echo json_encode(array('success' => false, 'message' => '권한이 없습니다.'));
			return;
		}

		$this->db->trans_start();

		try {
			$result = true;

			// 출석 데이터 저장
			if ($attendance_data_json) {
				$attendance_data = json_decode($attendance_data_json, true);
				if ($attendance_data) {
					$result = $this->Attendance_model->save_attendance_with_values($org_id, $attendance_data, $att_date, $year);
					log_message('debug', 'Attendance save result: ' . ($result ? 'success' : 'failed'));
				}
			}

			// 메모 데이터 저장 - 모든 메모를 처리하도록 수정
			if ($result && $memo_data_json) {
				$memo_data = json_decode($memo_data_json, true);
				log_message('debug', 'Decoded memo data: ' . print_r($memo_data, true));

				if ($memo_data && is_array($memo_data)) {
					$this->load->model('Memo_model');
					$user_id = $this->session->userdata('user_id');

					foreach ($memo_data as $memo_item) {
						if (!isset($memo_item['member_idx']) || !$memo_item['member_idx']) {
							continue;
						}

						$member_idx = $memo_item['member_idx'];
						$memo_content = isset($memo_item['memo_content']) ? trim($memo_item['memo_content']) : '';
						$att_idx = isset($memo_item['att_idx']) && $memo_item['att_idx'] ? $memo_item['att_idx'] : null;

						log_message('debug', "Processing memo for member {$member_idx}: '{$memo_content}', att_idx: {$att_idx}");

						// att_idx가 없고 메모 내용이 있으면 출석 레코드 생성/조회
						if (!$att_idx && $memo_content) {
							$att_idx = $this->get_or_create_attendance_idx($org_id, $member_idx, $att_date, $year);
							log_message('debug', "Created/found att_idx: {$att_idx}");
						}

						// 기존 메모가 있는지 확인 (att_idx 기준 또는 member_idx + date 기준)
						$existing_memo = null;

						if ($att_idx) {
							$existing_memo = $this->Memo_model->get_memo_by_att_idx($att_idx);
						}

						// att_idx가 없는 경우 member_idx와 날짜로 찾기 (일반 메모)
						if (!$existing_memo) {
							$this->db->select('*');
							$this->db->from('wb_memo');
							$this->db->where('member_idx', $member_idx);
							$this->db->where('DATE(regi_date)', $att_date);
							$this->db->where('att_idx IS NULL');
							$this->db->order_by('regi_date', 'DESC');
							$this->db->limit(1);
							$query = $this->db->get();
							$existing_memo = $query->row_array();
						}

						if ($memo_content) {
							// 메모 내용이 있는 경우
							if ($existing_memo) {
								// 기존 메모 수정
								$update_data = array(
									'memo_content' => $memo_content,
									'modi_date' => date('Y-m-d H:i:s')
								);
								$update_result = $this->Memo_model->update_memo($existing_memo['idx'], $update_data);
								log_message('debug', "Updated existing memo: " . ($update_result ? 'success' : 'failed'));
							} else {
								// 새 메모 추가
								$insert_data = array(
									'memo_type' => 1,
									'memo_content' => $memo_content,
									'regi_date' => date('Y-m-d H:i:s'),
									'user_id' => $user_id,
									'member_idx' => $member_idx,
									'att_idx' => $att_idx
								);
								$insert_result = $this->Memo_model->save_memo($insert_data);
								log_message('debug', "Inserted new memo: " . ($insert_result ? 'success' : 'failed'));
							}
						} else {
							// 메모 내용이 비어있으면 기존 메모 삭제
							if ($existing_memo) {
								$delete_result = $this->Memo_model->delete_memo($existing_memo['idx']);
								log_message('debug', "Deleted memo: " . ($delete_result ? 'success' : 'failed'));
							}
						}
					}
				}
			}

			$this->db->trans_complete();

			if ($this->db->trans_status() === FALSE) {
				log_message('error', 'Transaction failed');
				echo json_encode(array('success' => false, 'message' => '저장에 실패했습니다.'));
				return;
			}

			log_message('debug', 'Save completed successfully');
			echo json_encode(array('success' => true, 'message' => '출석 및 메모가 저장되었습니다.'));

		} catch (Exception $e) {
			$this->db->trans_rollback();
			log_message('error', 'Attendance with memo save error: ' . $e->getMessage());
			echo json_encode(array('success' => false, 'message' => '저장 중 오류가 발생했습니다.'));
		}
	}

	/**
	 * 출석 IDX 조회 또는 생성 함수 수정
	 */
	private function get_or_create_attendance_idx($org_id, $member_idx, $att_date, $year)
	{
		// 해당 회원의 해당 날짜 출석 기록 조회
		$this->db->select('att_idx as idx');
		$this->db->from('wb_member_att');
		$this->db->where('org_id', $org_id);
		$this->db->where('member_idx', $member_idx);
		$this->db->where('att_date', $att_date);
		$this->db->where('att_year', $year);
		$this->db->limit(1);

		$query = $this->db->get();
		$result = $query->row_array();

		if ($result) {
			return $result['idx'];
		}

		// 출석 기록이 없으면 더미 레코드 생성 (메모용)
		$insert_data = array(
			'member_idx' => $member_idx,
			'att_date' => $att_date,
			'att_type_idx' => 0, // 메모 전용 더미 타입
			'att_value' => 0,
			'org_id' => $org_id,
			'att_year' => $year
		);

		$this->db->insert('wb_member_att', $insert_data);
		return $this->db->insert_id();
	}

	/**
	 * 출석 데이터 저장
	 */
	public function save_attendance()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$org_id = $this->input->post('org_id');
		$attendance_data = $this->input->post('attendance_data');

		if (!$org_id || !$attendance_data) {
			echo json_encode(array('success' => false, 'message' => '필수 정보가 누락되었습니다.'));
			return;
		}

		if (!$this->check_org_access($org_id)) {
			echo json_encode(array('success' => false, 'message' => '권한이 없습니다.'));
			return;
		}

		$result = $this->Attendance_model->save_attendance_batch($org_id, $attendance_data);

		if ($result) {
			echo json_encode(array('success' => true, 'message' => '출석이 저장되었습니다.'));
		} else {
			echo json_encode(array('success' => false, 'message' => '출석 저장에 실패했습니다.'));
		}
	}

	/**
	 * 특정 연도의 일요일 날짜들을 가져오기
	 */
	private function get_sunday_dates($year)
	{
		$sunday_dates = array();

		// 해당 연도 1월 1일부터 시작하여 첫 번째 일요일 찾기
		$date = new DateTime($year . '-01-01');

		// 첫 번째 일요일로 이동
		while ($date->format('w') != 0) { // 0 = 일요일
			$date->add(new DateInterval('P1D'));
		}

		// 해당 연도의 모든 일요일 수집
		while ($date->format('Y') == $year) {
			$sunday_dates[] = $date->format('Y-m-d');
			$date->add(new DateInterval('P7D')); // 7일 후
		}

		return $sunday_dates;
	}



	/**
	 * 주별 출석데이터 가져오기 - 실제 포인트 계산 포함
	 */
	private function get_weekly_attendance_data($members, $org_id, $sunday_dates, $year)
	{
		$member_indices = array_column($members, 'member_idx');

		if (empty($member_indices) || empty($sunday_dates)) {
			return array();
		}

		// 출석유형별 포인트 정보 가져오기
		$attendance_types = $this->Attendance_model->get_attendance_types($org_id);
		$type_points = array();

		foreach ($attendance_types as $type) {
			$type_points[$type['att_type_idx']] = array(
				'point' => intval($type['att_type_point']) ?: 10,
				'input_type' => $type['att_type_input'] ?: 'check'
			);
		}

		// 각 회원, 각 주별로 실제 포인트 계산
		$stats = array();

		foreach ($member_indices as $member_idx) {
			$stats[$member_idx] = array();

			foreach ($sunday_dates as $sunday) {
				// 해당 주의 날짜 범위 계산
				$start_date = $sunday;
				$end_date = date('Y-m-d', strtotime($sunday . ' +6 days'));

				// 해당 주간의 실제 출석 기록 가져오기
				$this->db->select('att_type_idx, att_value');
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
					$att_value = $record['att_value'];

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

				$stats[$member_idx][$sunday] = array(
					'total_score' => $total_points,
					'attendance_count' => count($records)
				);
			}
		}

		return $stats;
	}

	/**
	 * 일요일 기준으로 주 날짜 범위 계산
	 */
	private function get_week_date_range($sunday_date)
	{
		$start = new DateTime($sunday_date);
		$end = clone $start;
		$end->add(new DateInterval('P6D')); // 6일 후 (토요일)

		return array(
			'start' => $start->format('Y-m-d'),
			'end' => $end->format('Y-m-d')
		);
	}


	/**
	 * 통계 재계산 (선택된 그룹 대상) - 디버깅 강화
	 */
	public function rebuild_stats()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$user_id = $this->session->userdata('user_id');
		$org_id = $this->input->post('org_id');
		$year = $this->input->post('year', true) ?: date('Y');
		$type = $this->input->post('type');
		$area_idx = $this->input->post('area_idx');

		log_message('debug', "Rebuild stats request - Org: {$org_id}, Year: {$year}, Type: {$type}, Area: {$area_idx}");

		if (!$org_id) {
			echo json_encode(array('success' => false, 'message' => '조직 ID가 필요합니다.'));
			return;
		}

		if (!$this->check_org_access($org_id)) {
			echo json_encode(array('success' => false, 'message' => '권한이 없습니다.'));
			return;
		}

		// 권한에 따른 회원 목록 가져오기
		$master_yn = $this->session->userdata('master_yn');
		$user_level = $this->User_model->get_org_user_level($user_id, $org_id);

		$members = array();
		$group_name = '';

		if ($type === 'unassigned') {
			if ($user_level < 10 && $master_yn !== 'Y') {
				echo json_encode(array('success' => false, 'message' => '미분류 그룹을 관리할 권한이 없습니다.'));
				return;
			}
			$members = $this->Member_model->get_unassigned_members($org_id);
			$group_name = '미분류 그룹';

		} else if ($type === 'area' && $area_idx) {
			if ($user_level < 10 && $master_yn !== 'Y') {
				$this->load->model('User_management_model');
				$accessible_areas = $this->User_management_model->get_user_managed_areas_with_children($user_id, $org_id);
				if (!in_array($area_idx, $accessible_areas)) {
					echo json_encode(array('success' => false, 'message' => '해당 그룹을 관리할 권한이 없습니다.'));
					return;
				}
			}
			$members = $this->Member_model->get_area_members_with_children($org_id, $area_idx);

			// 그룹명 가져오기
			$this->db->select('area_name');
			$this->db->from('wb_member_area');
			$this->db->where('area_idx', $area_idx);
			$area_info = $this->db->get()->row_array();
			$group_name = $area_info ? $area_info['area_name'] . ' 그룹' : '선택된 그룹';

		} else if ($type === 'org') {
			if ($user_level >= 10 || $master_yn === 'Y') {
				$members = $this->Member_model->get_org_members($org_id);
				$group_name = '전체 조직';
			} else {
				$this->load->model('User_management_model');
				$members = array();
				$managed_areas = $this->User_management_model->get_user_managed_areas($user_id);
				foreach ($managed_areas as $managed_area_idx) {
					$area_members = $this->Member_model->get_area_members_with_children($org_id, $managed_area_idx);
					$members = array_merge($members, $area_members);
				}

				$unique_members = array();
				$member_ids = array();
				foreach ($members as $member) {
					if (!in_array($member['member_idx'], $member_ids)) {
						$unique_members[] = $member;
						$member_ids[] = $member['member_idx'];
					}
				}
				$members = $unique_members;
				$group_name = '관리 가능한 그룹';
			}
		} else {
			echo json_encode(array('success' => false, 'message' => '유효하지 않은 그룹 타입입니다.'));
			return;
		}

		if (empty($members)) {
			echo json_encode(array('success' => false, 'message' => '재계산할 회원이 없습니다.'));
			return;
		}

		$member_indices = array_column($members, 'member_idx');
		log_message('debug', "Members to rebuild: " . implode(',', $member_indices));

		try {
			$result = $this->Attendance_model->rebuild_attendance_stats_for_members($org_id, $year, $member_indices);

			if ($result) {
				echo json_encode(array(
					'success' => true,
					'message' => $group_name . '의 ' . count($members) . '명 출석 통계가 재계산되었습니다.',
					'member_count' => count($members),
					'group_name' => $group_name
				));
			} else {
				echo json_encode(array('success' => false, 'message' => '통계 재계산 중 오류가 발생했습니다.'));
			}
		} catch (Exception $e) {
			log_message('error', 'Stats rebuild error: ' . $e->getMessage());
			echo json_encode(array('success' => false, 'message' => '통계 재계산 중 오류가 발생했습니다.'));
		}
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

			// 해당 회원들의 기존 통계 삭제
			$this->db->where('org_id', $org_id);
			$this->db->where('att_year', $year);
			$this->db->where_in('member_idx', $member_indices);
			$this->db->delete('wb_attendance_weekly_stats');

			$this->db->where('org_id', $org_id);
			$this->db->where('att_year', $year);
			$this->db->where_in('member_idx', $member_indices);
			$this->db->delete('wb_attendance_yearly_stats');

			// 각 회원, 각 주차별로 통계 생성
			foreach ($member_indices as $member_idx) {
				foreach ($sunday_dates as $sunday_date) {
					$this->update_weekly_attendance_stats($org_id, $member_idx, $year, $sunday_date);
				}
			}

			$this->db->trans_complete();

			if ($this->db->trans_status() === FALSE) {
				return false;
			}

			return true;

		} catch (Exception $e) {
			$this->db->trans_rollback();
			log_message('error', 'Rebuild stats for members error: ' . $e->getMessage());
			return false;
		}
	}



	/**
	 * 출석 데이터 저장 (값 포함) - checkbox/textbox 모두 처리
	 */
	public function save_attendance_with_values()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$org_id = $this->input->post('org_id');
		$attendance_data_json = $this->input->post('attendance_data');
		$att_date = $this->input->post('att_date');
		$year = $this->input->post('year', true) ?: date('Y');

		if (!$org_id || !$attendance_data_json || !$att_date) {
			echo json_encode(array('success' => false, 'message' => '필수 정보가 누락되었습니다.'));
			return;
		}

		if (!$this->check_org_access($org_id)) {
			echo json_encode(array('success' => false, 'message' => '권한이 없습니다.'));
			return;
		}

		try {
			$attendance_data = json_decode($attendance_data_json, true);
			if (!$attendance_data) {
				echo json_encode(array('success' => false, 'message' => '출석 데이터 형식이 올바르지 않습니다.'));
				return;
			}

			$result = $this->Attendance_model->save_attendance_with_values($org_id, $attendance_data, $att_date, $year);

			if ($result) {
				echo json_encode(array('success' => true, 'message' => '출석이 저장되었습니다.'));
			} else {
				echo json_encode(array('success' => false, 'message' => '출석 저장에 실패했습니다.'));
			}
		} catch (Exception $e) {
			log_message('error', 'Attendance save with values error: ' . $e->getMessage());
			echo json_encode(array('success' => false, 'message' => '출석 저장 중 오류가 발생했습니다.'));
		}
	}








}
