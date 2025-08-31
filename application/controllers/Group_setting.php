<?php
/**
 * 파일 위치: application/controllers/Group_setting.php
 * 역할: 그룹설정 페이지의 컨트롤러
 */
defined('BASEPATH') or exit('No direct script access allowed');

class Group_setting extends My_Controller
{

	public function __construct()
	{
		parent::__construct();
		$this->load->model('Group_setting_model');
		$this->load->model('User_model');
		$this->load->library('session');
		$this->load->helper('url');
	}

	/**
	 * 그룹설정 메인 페이지
	 */
	public function index()
	{
		$user_id = $this->session->userdata('user_id');
		if (!$user_id) {
			redirect('login');
			return;
		}

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



		// 선택된 조직의 상세 정보 가져오기
		$this->load->model('Org_model');
		$data['selected_org_detail'] = $this->Org_model->get_org_detail_by_id($currentOrgId);

		// 현재 조직 정보를 JavaScript로 전달하기 위해 orgs 배열에 추가
		$data['orgs'] = array($data['current_org']);

		$this->load->view('group_setting', $data);
	}

	/**
	 * 조직 그룹 트리 데이터 조회 (AJAX)
	 */
	public function get_group_tree()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$user_id = $this->session->userdata('user_id');
		$org_id = $this->input->post('org_id');

		if (!$org_id) {
			$org_id = $this->input->cookie('activeOrg');
		}

		if (!$org_id) {
			echo json_encode(array('success' => false, 'message' => '조직 정보를 찾을 수 없습니다.'));
			return;
		}

		// 권한 검증
		$user_level = $this->Group_setting_model->get_org_user_level($user_id, $org_id);
		if ($user_level < 8 && $this->session->userdata('master_yn') !== 'Y') {
			echo json_encode(array('success' => false, 'message' => '그룹을 조회할 권한이 없습니다.'));
			return;
		}

		$tree_data = $this->Group_setting_model->get_org_group_tree($org_id);
		echo json_encode($tree_data);
	}

	/**
	 * 새로운 그룹 생성 (AJAX)
	 */
	public function add_group()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$user_id = $this->session->userdata('user_id');
		$org_id = $this->input->post('org_id');
		$parent_idx = $this->input->post('parent_idx');

		if (!$org_id) {
			echo json_encode(array('success' => false, 'message' => '조직 정보를 찾을 수 없습니다.'));
			return;
		}

		// 권한 검증
		$user_level = $this->Group_setting_model->get_org_user_level($user_id, $org_id);
		if ($user_level < 8 && $this->session->userdata('master_yn') !== 'Y') {
			echo json_encode(array('success' => false, 'message' => '그룹을 생성할 권한이 없습니다.'));
			return;
		}

		// parent_idx 처리: 빈 문자열이나 '0'인 경우 null로 변경
		if (empty($parent_idx) || $parent_idx === '0' || $parent_idx === 0) {
			$parent_idx = null;
		}

		// 새로운 순서 계산
		$area_order = $this->Group_setting_model->get_next_area_order($org_id, $parent_idx);

		$data = array(
			'area_name' => '새그룹',
			'org_id' => $org_id,
			'parent_idx' => $parent_idx,
			'area_order' => $area_order
		);

		$result = $this->Group_setting_model->insert_group($data);

		if ($result) {
			// 새로 생성된 그룹의 area_idx를 반환
			$new_area_idx = $this->db->insert_id();
			echo json_encode(array(
				'success' => true,
				'message' => '새그룹이 생성되었습니다.',
				'new_area_idx' => $new_area_idx
			));
		} else {
			echo json_encode(array('success' => false, 'message' => '그룹 생성에 실패했습니다.'));
		}
	}


	/**
	 * 그룹 삭제 (AJAX)
	 */
	public function delete_group()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$user_id = $this->session->userdata('user_id');
		$area_idx = $this->input->post('area_idx');
		$org_id = $this->input->post('org_id');

		if (!$area_idx || !$org_id) {
			echo json_encode(array('success' => false, 'message' => '필수 정보가 누락되었습니다.'));
			return;
		}

		// 권한 검증
		$user_level = $this->Group_setting_model->get_org_user_level($user_id, $org_id);
		if ($user_level < 8 && $this->session->userdata('master_yn') !== 'Y') {
			echo json_encode(array('success' => false, 'message' => '그룹을 삭제할 권한이 없습니다.'));
			return;
		}

		// 그룹에 포함된 회원 수 확인
		$member_count = $this->Group_setting_model->get_group_member_count($area_idx);

		// 그룹 삭제 실행 (회원들은 미분류로 이동)
		$result = $this->Group_setting_model->delete_group_with_members($area_idx, $org_id);

		if ($result) {
			$message = $member_count > 0 ?
				"그룹이 삭제되었습니다. {$member_count}명의 회원이 미분류로 이동되었습니다." :
				'그룹이 삭제되었습니다.';
			echo json_encode(array('success' => true, 'message' => $message));
		} else {
			echo json_encode(array('success' => false, 'message' => '그룹 삭제에 실패했습니다.'));
		}
	}

	/**
	 * 그룹 이동 (AJAX)
	 */
	public function move_group()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$user_id = $this->session->userdata('user_id');
		$area_idx = $this->input->post('area_idx');
		$target_parent_idx = $this->input->post('target_parent_idx');
		$org_id = $this->input->post('org_id');

		if (!$area_idx || !$org_id) {
			echo json_encode(array('success' => false, 'message' => '필수 정보가 누락되었습니다.'));
			return;
		}

		// 권한 검증
		$user_level = $this->Group_setting_model->get_org_user_level($user_id, $org_id);
		if ($user_level < 8 && $this->session->userdata('master_yn') !== 'Y') {
			echo json_encode(array('success' => false, 'message' => '그룹을 이동할 권한이 없습니다.'));
			return;
		}

		// 자기 자신이나 하위 그룹으로 이동하는 것 방지
		if ($this->Group_setting_model->is_descendant_group($area_idx, $target_parent_idx)) {
			echo json_encode(array('success' => false, 'message' => '자기 자신이나 하위 그룹으로는 이동할 수 없습니다.'));
			return;
		}

		// 새로운 순서 계산
		$area_order = $this->Group_setting_model->get_next_area_order($org_id, $target_parent_idx);

		$result = $this->Group_setting_model->move_group($area_idx, $target_parent_idx, $area_order);

		if ($result) {
			echo json_encode(array('success' => true, 'message' => '그룹이 이동되었습니다.'));
		} else {
			echo json_encode(array('success' => false, 'message' => '그룹 이동에 실패했습니다.'));
		}
	}

	/**
	 * 그룹 최상위 이동 (AJAX)
	 */
	public function move_group_to_top()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$user_id = $this->session->userdata('user_id');
		$area_idx = $this->input->post('area_idx');
		$org_id = $this->input->post('org_id');

		if (!$area_idx || !$org_id) {
			echo json_encode(array('success' => false, 'message' => '필수 정보가 누락되었습니다.'));
			return;
		}

		// 권한 검증
		$user_level = $this->Group_setting_model->get_org_user_level($user_id, $org_id);
		if ($user_level < 8 && $this->session->userdata('master_yn') !== 'Y') {
			echo json_encode(array('success' => false, 'message' => '그룹을 이동할 권한이 없습니다.'));
			return;
		}

		// 1depth 순서 계산 (parent_idx = null)
		$area_order = $this->Group_setting_model->get_next_area_order($org_id, null);

		$result = $this->Group_setting_model->move_group($area_idx, null, $area_order);

		if ($result) {
			echo json_encode(array('success' => true, 'message' => '그룹이 최상위로 이동되었습니다.'));
		} else {
			echo json_encode(array('success' => false, 'message' => '그룹 이동에 실패했습니다.'));
		}
	}

	/**
	 * 그룹 이동용 목록 조회 (AJAX)
	 */
	public function get_move_target_groups()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$user_id = $this->session->userdata('user_id');
		$org_id = $this->input->post('org_id');
		$current_area_idx = $this->input->post('current_area_idx');

		if (!$org_id) {
			echo json_encode(array('success' => false, 'message' => '조직 정보를 찾을 수 없습니다.'));
			return;
		}

		// 권한 검증
		$user_level = $this->Group_setting_model->get_org_user_level($user_id, $org_id);
		if ($user_level < 8 && $this->session->userdata('master_yn') !== 'Y') {
			echo json_encode(array('success' => false, 'message' => '그룹 목록을 조회할 권한이 없습니다.'));
			return;
		}

		$target_groups = $this->Group_setting_model->get_move_target_groups($org_id, $current_area_idx);
		echo json_encode(array('success' => true, 'data' => $target_groups));
	}



	/**
	 * 그룹명 변경 (AJAX)
	 */
	public function rename_group()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$user_id = $this->session->userdata('user_id');
		$area_idx = $this->input->post('area_idx');
		$org_id = $this->input->post('org_id');
		$new_name = trim($this->input->post('new_name'));

		if (!$area_idx || !$org_id || !$new_name) {
			echo json_encode(array('success' => false, 'message' => '필수 정보가 누락되었습니다.'));
			return;
		}

		// 권한 검증
		$user_level = $this->Group_setting_model->get_org_user_level($user_id, $org_id);
		if ($user_level < 8 && $this->session->userdata('master_yn') !== 'Y') {
			echo json_encode(array('success' => false, 'message' => '그룹명을 변경할 권한이 없습니다.'));
			return;
		}

		// 그룹명 길이 검증
		if (strlen($new_name) > 50) {
			echo json_encode(array('success' => false, 'message' => '그룹명은 50자 이내로 입력해주세요.'));
			return;
		}

		// 동일한 조직 내에서 같은 레벨에 동일한 그룹명이 있는지 확인
		$current_group = $this->Group_setting_model->get_group_info($area_idx);
		if (!$current_group) {
			echo json_encode(array('success' => false, 'message' => '그룹 정보를 찾을 수 없습니다.'));
			return;
		}

		$duplicate_check = $this->Group_setting_model->check_duplicate_group_name($org_id, $new_name, $current_group['parent_idx'], $area_idx);
		if ($duplicate_check) {
			echo json_encode(array('success' => false, 'message' => '동일한 위치에 같은 이름의 그룹이 이미 존재합니다.'));
			return;
		}

		// 그룹명 변경 실행
		$result = $this->Group_setting_model->update_group_name($area_idx, $new_name);

		if ($result) {
			echo json_encode(array('success' => true, 'message' => '그룹명이 변경되었습니다.'));
		} else {
			echo json_encode(array('success' => false, 'message' => '그룹명 변경에 실패했습니다.'));
		}
	}


}
