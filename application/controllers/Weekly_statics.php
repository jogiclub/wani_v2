<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * 파일 위치: application/controllers/Weekly_statics.php
 * 역할: 주별통계 페이지 컨트롤러 - 출석유형별 count 기반 통계 제공
 */

class Weekly_statics extends My_Controller
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
	 * 주별통계 메인 페이지
	 */
	public function index()
	{


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
			$this->handle_access_denied('해당 조직의 출석통계를 조회할 권한이 없습니다.');
			return;
		}

		// 현재 조직 정보를 JavaScript로 전달
		$data['orgs'] = array($data['current_org']);

		$this->load->view('weekly_statics', $data);
	}

	/**
	 * 조직 트리 데이터 가져오기
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

		// 권한에 따른 영역 필터링
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

		// Fancytree 노드 구성
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

		// 조직 전체 회원 수 계산
		if ($user_level >= 10 || $master_yn === 'Y') {
			$org_total_members = $this->Member_model->get_org_member_count($active_org_id);
		} else {
			$org_total_members = 0;
			if (!empty($accessible_areas)) {
				$this->load->model('User_management_model');
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

		// 미분류 노드 추가 (관리자만)
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
	 * 출석유형 목록 가져오기
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

		$this->db->select('att_type_idx, att_type_name, att_type_nickname, att_type_color, att_type_order');
		$this->db->from('wb_att_type');
		$this->db->where('org_id', $org_id);
		$this->db->order_by('att_type_order', 'ASC');

		$query = $this->db->get();
		$attendance_types = $query->result_array();

		echo json_encode(array(
			'success' => true,
			'data' => $attendance_types
		));
	}

	/**
	 * 출석데이터 가져오기 (주별, count 기반)
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

		// 권한에 따른 회원 목록 가져오기
		$members = $this->get_members_by_type($type, $org_id, $area_idx);

		if ($members === false) {
			echo json_encode(array('success' => false, 'message' => '해당 그룹을 조회할 권한이 없습니다.'));
			return;
		}

		// 해당 연도의 일요일 날짜들 생성
		$sunday_dates = $this->get_sunday_dates($year);

		// 각 회원의 주별 출석데이터 가져오기 (count 기반)
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
	 * 타입에 따른 회원 목록 가져오기
	 */
	private function get_members_by_type($type, $org_id, $area_idx)
	{
		$user_id = $this->session->userdata('user_id');
		$master_yn = $this->session->userdata('master_yn');
		$user_level = $this->User_model->get_org_user_level($user_id, $org_id);

		if ($type === 'unassigned') {
			if ($user_level < 10 && $master_yn !== 'Y') {
				return false;
			}
			return $this->Member_model->get_unassigned_members($org_id);
		}
		else if ($type === 'area' && $area_idx) {
			if ($user_level < 10 && $master_yn !== 'Y') {
				$this->load->model('User_management_model');
				$accessible_areas = $this->User_management_model->get_user_managed_areas_with_children($user_id, $org_id);
				if (!in_array($area_idx, $accessible_areas)) {
					return false;
				}
			}
			return $this->Member_model->get_area_members_with_children($org_id, $area_idx);
		}
		else if ($type === 'org') {
			if ($user_level >= 10 || $master_yn === 'Y') {
				return $this->Member_model->get_org_members($org_id);
			} else {
				$this->load->model('User_management_model');
				$members = array();
				$managed_areas = $this->User_management_model->get_user_managed_areas($user_id);
				foreach ($managed_areas as $managed_area_idx) {
					$area_members = $this->Member_model->get_area_members_with_children($org_id, $managed_area_idx);
					$members = array_merge($members, $area_members);
				}

				// 중복 제거
				$unique_members = array();
				$member_ids = array();
				foreach ($members as $member) {
					if (!in_array($member['member_idx'], $member_ids)) {
						$unique_members[] = $member;
						$member_ids[] = $member['member_idx'];
					}
				}
				return $unique_members;
			}
		}

		return array();
	}

	/**
	 * 특정 연도의 일요일 날짜들을 가져오기 (현재 주까지만)
	 */
	private function get_sunday_dates($year)
	{
		$sunday_dates = array();

		// 해당 연도 1월 1일부터 시작하여 첫 번째 일요일 찾기
		$date = new DateTime($year . '-01-01');
		while ($date->format('w') != 0) { // 0 = 일요일
			$date->add(new DateInterval('P1D'));
		}

		// 현재 주의 일요일까지만 포함
		$today = new DateTime();
		$current_week_sunday = clone $today;
		$days_from_sunday = $today->format('w');
		if ($days_from_sunday > 0) {
			$current_week_sunday->sub(new DateInterval('P' . $days_from_sunday . 'D'));
		}

		// 해당 연도의 일요일 수집 (현재 주까지만)
		while ($date->format('Y') == $year && $date <= $current_week_sunday) {
			$sunday_dates[] = $date->format('Y-m-d');
			$date->add(new DateInterval('P7D')); // 7일 후
		}

		return $sunday_dates;
	}

	/**
	 * 주별 출석데이터 가져오기 - count 기반으로 변경
	 */
	private function get_weekly_attendance_data($members, $org_id, $sunday_dates, $year)
	{
		$member_indices = array_column($members, 'member_idx');

		if (empty($member_indices) || empty($sunday_dates)) {
			return array();
		}

		// 각 회원, 각 주별로 출석유형별 count 계산
		$stats = array();

		foreach ($member_indices as $member_idx) {
			$stats[$member_idx] = array();

			foreach ($sunday_dates as $sunday) {
				// 해당 주의 날짜 범위 계산
				$start_date = $sunday;
				$end_date = date('Y-m-d', strtotime($sunday . ' +6 days'));

				// 해당 주간의 출석유형별 기록 가져오기
				$this->db->select('att_type_idx, COUNT(*) as count');
				$this->db->from('wb_member_att');
				$this->db->where('member_idx', $member_idx);
				$this->db->where('org_id', $org_id);
				$this->db->where('att_date >=', $start_date);
				$this->db->where('att_date <=', $end_date);
				$this->db->where('att_year', $year);
				$this->db->group_by('att_type_idx');

				$query = $this->db->get();
				$records = $query->result_array();

				// 출석유형별로 정리
				$attendance_types_data = array();
				foreach ($records as $record) {
					$attendance_types_data[$record['att_type_idx']] = array(
						'count' => intval($record['count'])
					);
				}

				$stats[$member_idx][$sunday] = array(
					'attendance_types' => $attendance_types_data
				);
			}
		}

		return $stats;
	}

	/**
	 * 통계 재계산 (선택된 그룹 대상)
	 */
	public function rebuild_stats()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$org_id = $this->input->post('org_id');
		$year = $this->input->post('year', true) ?: date('Y');
		$type = $this->input->post('type');
		$area_idx = $this->input->post('area_idx');

		if (!$org_id) {
			echo json_encode(array('success' => false, 'message' => '조직 ID가 필요합니다.'));
			return;
		}

		if (!$this->check_org_access($org_id)) {
			echo json_encode(array('success' => false, 'message' => '권한이 없습니다.'));
			return;
		}

		// 권한에 따른 회원 목록 가져오기
		$members = $this->get_members_by_type($type, $org_id, $area_idx);

		if ($members === false) {
			echo json_encode(array('success' => false, 'message' => '해당 그룹을 관리할 권한이 없습니다.'));
			return;
		}

		if (empty($members)) {
			echo json_encode(array('success' => false, 'message' => '재계산할 회원이 없습니다.'));
			return;
		}

		$member_indices = array_column($members, 'member_idx');

		// 그룹명 결정
		$group_name = $this->get_group_name($type, $area_idx);

		try {
			// 통계 재계산 실행
			$result = $this->Attendance_model->rebuild_attendance_stats_with_date_normalization($org_id, $year, $member_indices);

			if ($result) {
				$normalized_count = isset($result['normalized_count']) ? $result['normalized_count'] : 0;
				$members_processed = isset($result['members_processed']) ? $result['members_processed'] : count($members);

				$message = $group_name . '의 ' . $members_processed . '명 출석통계가 재계산되었습니다.';
				if ($normalized_count > 0) {
					$message .= ' (출석일자 ' . $normalized_count . '건 정리 완료)';
				}

				echo json_encode(array(
					'success' => true,
					'message' => $message,
					'details' => array(
						'member_count' => $members_processed,
						'normalized_count' => $normalized_count,
						'group_name' => $group_name
					)
				));
			} else {
				echo json_encode(array('success' => false, 'message' => '출석통계 재계산 중 오류가 발생했습니다.'));
			}
		} catch (Exception $e) {
			log_message('error', 'Weekly stats rebuild error: ' . $e->getMessage());
			echo json_encode(array('success' => false, 'message' => '출석통계 재계산 중 오류가 발생했습니다.'));
		}
	}

	/**
	 * 그룹명 가져오기
	 */
	private function get_group_name($type, $area_idx)
	{
		switch ($type) {
			case 'unassigned':
				return '미분류 그룹';
			case 'area':
				if ($area_idx) {
					$this->db->select('area_name');
					$this->db->from('wb_member_area');
					$this->db->where('area_idx', $area_idx);
					$area_info = $this->db->get()->row_array();
					return $area_info ? $area_info['area_name'] . ' 그룹' : '선택된 그룹';
				}
				return '선택된 그룹';
			case 'org':
				return '전체 조직';
			default:
				return '선택된 그룹';
		}
	}
}
