<?php
/**
 * 파일 위치: application/controllers/Moim.php
 * 역할: 소모임 관리 컨트롤러
 */

defined('BASEPATH') or exit('No direct script access allowed');

class Moim extends My_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->library('session');
		$this->load->helper('url');
		$this->load->model('Moim_model');
		$this->load->model('Org_model');
		$this->load->model('User_model');
		$this->load->model('Member_model');

		// 메뉴 권한 체크
		$this->check_menu_access('MOIM_MANAGEMENT');
	}

	/**
	 * 소모임 관리 메인 페이지
	 */
	public function index()
	{
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
			$this->handle_access_denied('해당 조직의 소모임을 관리할 권한이 없습니다.');
			return;
		}

		// 현재 조직 정보를 JavaScript로 전달
		$data['orgs'] = array($data['current_org']);

		$this->load->view('moim', $data);
	}

	/**
	 * 카테고리 트리 데이터 가져오기
	 */
	public function get_category_tree()
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
		foreach ($user_orgs as $org) {
			if ($org['org_id'] == $active_org_id) {
				$has_access = true;
				break;
			}
		}

		if (!$has_access) {
			echo json_encode(array());
			return;
		}

		// 카테고리 데이터 조회
		$category_data = $this->Moim_model->get_category_tree($active_org_id);

		// 카테고리가 없으면 기본 카테고리 생성
		if (empty($category_data)) {
			$default_result = $this->Moim_model->create_default_categories($active_org_id, $user_id);

			if ($default_result) {
				// 다시 카테고리 조회
				$category_data = $this->Moim_model->get_category_tree($active_org_id);
			}
		}

		// 소모임 회원 수 카운트
		$category_counts = $this->Moim_model->get_category_member_counts($active_org_id);

		// Fancytree 노드 구성
		$build_fancytree_nodes = function ($categories) use (&$build_fancytree_nodes, $category_counts) {
			$nodes = array();

			foreach ($categories as $category) {
				$member_count = isset($category_counts[$category['code']]) ? $category_counts[$category['code']] : 0;

				$title = $category['name'];
				if ($member_count > 0) {
					$title .= ' (' . $member_count . '명)';
				}

				$node = array(
					'key' => 'category_' . $category['code'],
					'title' => $title,
					'folder' => true,
					'expanded' => false,
					'data' => array(
						'type' => 'category',
						'category_code' => $category['code'],
						'positions' => isset($category['positions']) ? $category['positions'] : array()
					)
				);

				if (!empty($category['children'])) {
					$node['children'] = $build_fancytree_nodes($category['children']);
				}

				$nodes[] = $node;
			}

			return $nodes;
		};

		$tree_data = array();

		if (!empty($category_data)) {
			// 카테고리 노드들
			$category_nodes = $build_fancytree_nodes($category_data);

			// 전체 소모임 회원 수
			$total_member_count = $this->Moim_model->get_total_member_count($active_org_id);

			// 루트 노드 (조직)
			$org_info = $this->Org_model->get_org_detail_by_id($active_org_id);
			$org_title = $org_info['org_name'];
			if ($total_member_count > 0) {
				$org_title .= ' (' . $total_member_count . '명)';
			}

			$tree_data[] = array(
				'key' => 'org_' . $active_org_id,
				'title' => $org_title,
				'folder' => true,
				'expanded' => true,
				'data' => array(
					'type' => 'org',
					'org_id' => $active_org_id
				),
				'children' => $category_nodes
			);
		}

		echo json_encode($tree_data);
	}

	/**
	 * 소모임 회원 목록 조회 API
	 */
	public function get_moim_members()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$org_id = $this->input->post('org_id');
		$category_code = $this->input->post('category_code');
		$type = $this->input->post('type');

		if (!$org_id) {
			echo json_encode(array('success' => false, 'message' => '조직 정보가 필요합니다.'));
			return;
		}

		// 권한 확인
		if (!$this->check_org_access($org_id)) {
			echo json_encode(array('success' => false, 'message' => '권한이 없습니다.'));
			return;
		}

		$member_list = array();

		if ($type === 'org') {
			// 전체 소모임 회원 목록
			$member_list = $this->Moim_model->get_moim_members_by_org($org_id);
		} else if ($type === 'category') {
			// 카테고리별 소모임 회원 목록
			$member_list = $this->Moim_model->get_moim_members_by_category($org_id, $category_code);
		}

		echo json_encode(array(
			'success' => true,
			'data' => $member_list,
			'total_count' => count($member_list)
		));
	}

	/**
	 * 소모임 회원 추가 API
	 */
	public function add_moim_members()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$user_id = $this->session->userdata('user_id');
		$org_id = $this->input->post('org_id');
		$category_code = $this->input->post('category_code');
		$member_indices = $this->input->post('member_indices');
		$moim_position = $this->input->post('moim_position');

		// 권한 확인
		if (!$this->check_org_access($org_id)) {
			echo json_encode(array('success' => false, 'message' => '권한이 없습니다.'));
			return;
		}

		if (!$category_code || empty($member_indices)) {
			echo json_encode(array('success' => false, 'message' => '필수 정보가 누락되었습니다.'));
			return;
		}

		$success_count = 0;
		$duplicate_count = 0;

		foreach ($member_indices as $member_idx) {
			// 중복 체크
			if ($this->Moim_model->check_duplicate_member($org_id, $category_code, $member_idx)) {
				$duplicate_count++;
				continue;
			}

			$moim_data = array(
				'org_id' => $org_id,
				'category_code' => $category_code,
				'member_idx' => $member_idx,
				'moim_position' => $moim_position,
				'user_id' => $user_id
			);

			if ($this->Moim_model->insert_moim_member($moim_data)) {
				$success_count++;
			}
		}

		$message = '';
		if ($success_count > 0) {
			$message .= $success_count . '명이 추가되었습니다.';
		}
		if ($duplicate_count > 0) {
			$message .= ' ' . $duplicate_count . '명은 이미 등록되어 있습니다.';
		}

		echo json_encode(array(
			'success' => true,
			'message' => $message,
			'added_count' => $success_count,
			'duplicate_count' => $duplicate_count
		));
	}

	/**
	 * 소모임 회원 직책 수정 API
	 */
	public function update_moim_position()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$moim_idx = $this->input->post('moim_idx');
		$moim_position = $this->input->post('moim_position');

		if (!$moim_idx) {
			echo json_encode(array('success' => false, 'message' => '소모임 정보가 필요합니다.'));
			return;
		}

		// 기존 소모임 정보 조회
		$existing_moim = $this->Moim_model->get_moim_by_idx($moim_idx);

		if (!$existing_moim) {
			echo json_encode(array('success' => false, 'message' => '소모임 정보를 찾을 수 없습니다.'));
			return;
		}

		// 권한 확인
		if (!$this->check_org_access($existing_moim['org_id'])) {
			echo json_encode(array('success' => false, 'message' => '권한이 없습니다.'));
			return;
		}

		$moim_data = array(
			'moim_position' => $moim_position
		);

		$result = $this->Moim_model->update_moim_member($moim_idx, $moim_data);

		if ($result) {
			echo json_encode(array('success' => true, 'message' => '직책이 수정되었습니다.'));
		} else {
			echo json_encode(array('success' => false, 'message' => '직책 수정에 실패했습니다.'));
		}
	}

	/**
	 * 소모임 회원 삭제 API
	 */
	public function delete_moim_member()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$moim_idx = $this->input->post('moim_idx');

		if (!$moim_idx) {
			echo json_encode(array('success' => false, 'message' => '소모임 정보가 필요합니다.'));
			return;
		}

		// 기존 소모임 정보 조회
		$existing_moim = $this->Moim_model->get_moim_by_idx($moim_idx);

		if (!$existing_moim) {
			echo json_encode(array('success' => false, 'message' => '소모임 정보를 찾을 수 없습니다.'));
			return;
		}

		// 권한 확인
		if (!$this->check_org_access($existing_moim['org_id'])) {
			echo json_encode(array('success' => false, 'message' => '권한이 없습니다.'));
			return;
		}

		$result = $this->Moim_model->delete_moim_member($moim_idx);

		if ($result) {
			echo json_encode(array('success' => true, 'message' => '소모임 회원이 삭제되었습니다.'));
		} else {
			echo json_encode(array('success' => false, 'message' => '삭제에 실패했습니다.'));
		}
	}

	/**
	 * 회원 검색 API
	 */
	public function search_members()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$org_id = $this->input->post('org_id');
		$keyword = $this->input->post('keyword');

		if (!$org_id) {
			echo json_encode(array('success' => false, 'message' => '조직 정보가 필요합니다.'));
			return;
		}

		// 권한 확인
		if (!$this->check_org_access($org_id)) {
			echo json_encode(array('success' => false, 'message' => '권한이 없습니다.'));
			return;
		}

		$members = $this->Member_model->search_members($org_id, $keyword);

		echo json_encode(array(
			'success' => true,
			'data' => $members
		));
	}

	/**
	 * 카테고리 추가 API
	 */
	public function add_category()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$user_id = $this->session->userdata('user_id');
		$org_id = $this->input->post('org_id');
		$parent_code = $this->input->post('parent_code'); // null이면 최상위
		$category_name = $this->input->post('category_name');

		if (!$org_id || !$category_name) {
			echo json_encode(['success' => false, 'message' => '필수 정보가 누락되었습니다.']);
			return;
		}
		if (!$this->check_org_access($org_id)) {
			echo json_encode(['success' => false, 'message' => '권한이 없습니다.']);
			return;
		}

		$result = $this->Moim_model->add_category($org_id, $user_id, $category_name, $parent_code);

		if ($result) {
			echo json_encode(['success' => true, 'message' => '카테고리가 생성되었습니다.']);
		} else {
			echo json_encode(['success' => false, 'message' => '카테고리 생성에 실패했습니다.']);
		}
	}

	/**
	 * 카테고리명 변경 API
	 */
	public function rename_category()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$user_id = $this->session->userdata('user_id');
		$org_id = $this->input->post('org_id');
		$category_code = $this->input->post('category_code');
		$new_name = $this->input->post('new_name');

		if (!$org_id || !$category_code || !$new_name) {
			echo json_encode(['success' => false, 'message' => '필수 정보가 누락되었습니다.']);
			return;
		}
		if (!$this->check_org_access($org_id)) {
			echo json_encode(['success' => false, 'message' => '권한이 없습니다.']);
			return;
		}

		$result = $this->Moim_model->rename_category($org_id, $user_id, $category_code, $new_name);

		if ($result) {
			echo json_encode(['success' => true, 'message' => '카테고리명이 변경되었습니다.']);
		} else {
			echo json_encode(['success' => false, 'message' => '카테고리명 변경에 실패했습니다.']);
		}
	}

	/**
	 * 카테고리 삭제 API
	 */
	public function delete_category()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$user_id = $this->session->userdata('user_id');
		$org_id = $this->input->post('org_id');
		$category_code = $this->input->post('category_code');

		if (!$org_id || !$category_code) {
			echo json_encode(['success' => false, 'message' => '필수 정보가 누락되었습니다.']);
			return;
		}
		if (!$this->check_org_access($org_id)) {
			echo json_encode(['success' => false, 'message' => '권한이 없습니다.']);
			return;
		}

		// 하위 카테고리 또는 소속된 소모임원이 있는지 확인
		if ($this->Moim_model->has_children_or_members($org_id, $category_code)) {
			echo json_encode(['success' => false, 'message' => '하위 카테고리 또는 소속된 회원이 있어 삭제할 수 없습니다.']);
			return;
		}

		$result = $this->Moim_model->delete_category($org_id, $user_id, $category_code);

		if ($result) {
			echo json_encode(['success' => true, 'message' => '카테고리가 삭제되었습니다.']);
		} else {
			echo json_encode(['success' => false, 'message' => '카테고리 삭제에 실패했습니다.']);
		}
	}

	/**
	 * 카테고리 이동 API
	 */
	public function move_category()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$user_id = $this->session->userdata('user_id');
		$org_id = $this->input->post('org_id');
		$source_code = $this->input->post('source_code');
		$target_code = $this->input->post('target_code'); // null이면 최상위로 이동
		$hit_mode = $this->input->post('hit_mode'); // before, after, over

		if (!$org_id || !$source_code || !$hit_mode) {
			echo json_encode(['success' => false, 'message' => '필수 정보가 누락되었습니다.']);
			return;
		}
		if (!$this->check_org_access($org_id)) {
			echo json_encode(['success' => false, 'message' => '권한이 없습니다.']);
			return;
		}

		$result = $this->Moim_model->move_category($org_id, $user_id, $source_code, $target_code, $hit_mode);

		if ($result) {
			echo json_encode(['success' => true, 'message' => '카테고리가 이동되었습니다.']);
		} else {
			echo json_encode(['success' => false, 'message' => '카테고리 이동에 실패했습니다.']);
		}
	}
}
