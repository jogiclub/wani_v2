<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * 파일 위치: E:\SynologyDrive\Example\wani\application\controllers\Member.php
 * 역할: 회원 관리 페이지 및 API 처리 (그룹별 회원 관리)
 */
class Member extends CI_Controller
{
	public function __construct(){
		parent::__construct();
		$this->load->library('session');
		$this->load->helper('url');
		$this->load->model('Org_model');
		$this->load->model('Member_model');
		$this->load->model('Member_area_model');
		$this->load->model('User_model');
	}

	/**
	 * 회원 관리 메인 페이지
	 */
	public function index() {
		// 로그인 체크
		if (!$this->session->userdata('user_id')) {
			redirect('login');
		}

		$user_id = $this->session->userdata('user_id');
		$master_yn = $this->session->userdata('master_yn');

		// 사용자 정보 및 그룹 정보 가져오기
		$data['user'] = $this->User_model->get_user_by_id($user_id);

		// 사용자가 접근 가능한 그룹 목록
		if($master_yn === "N"){
			$data['orgs'] = $this->Org_model->get_user_orgs($user_id);
		} else {
			$data['orgs'] = $this->Org_model->get_user_orgs_master($user_id);
		}

		$this->load->view('member', $data);
	}

	/**
	 * 그룹 트리 데이터 가져오기 (Fancytree용)
	 */
	public function get_org_tree() {
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$user_id = $this->session->userdata('user_id');
		$master_yn = $this->session->userdata('master_yn');

		// 사용자가 접근 가능한 그룹 목록 가져오기
		if($master_yn === "N"){
			$orgs = $this->Org_model->get_user_orgs($user_id);
		} else {
			$orgs = $this->Org_model->get_user_orgs_master($user_id);
		}

		$tree_data = array();

		foreach ($orgs as $org) {
			// 각 그룹의 소그룹(area) 가져오기
			$areas = $this->Member_area_model->get_member_areas($org['org_id']);

			$children = array();
			foreach ($areas as $area) {
				$children[] = array(
					'key' => 'area_' . $area['area_idx'],
					'title' => $area['area_name'],
					'type' => 'area',
					'org_id' => $org['org_id'],
					'area_idx' => $area['area_idx']
				);
			}

			$tree_data[] = array(
				'key' => 'org_' . $org['org_id'],
				'title' => $org['org_name'],
				'type' => 'org',
				'org_id' => $org['org_id'],
				'expanded' => false,
				'children' => $children
			);
		}

		header('Content-Type: application/json');
		echo json_encode($tree_data);
	}

	/**
	 * 선택된 그룹/소그룹의 회원 데이터 가져오기 (ParamQuery용)
	 */
	public function get_members() {
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$type = $this->input->post('type'); // 'org' 또는 'area'
		$org_id = $this->input->post('org_id');
		$area_idx = $this->input->post('area_idx');

		if (!$org_id) {
			echo json_encode(array('success' => false, 'message' => '그룹 ID가 필요합니다.'));
			return;
		}

		// 기본적으로 그룹의 모든 회원 가져오기
		$members = $this->Member_model->get_org_members($org_id);

		// 특정 소그룹이 선택된 경우 필터링
		if ($type === 'area' && $area_idx) {
			$members = array_filter($members, function($member) use ($area_idx) {
				return $member['area_idx'] == $area_idx;
			});
			$members = array_values($members); // 인덱스 재정렬
		}

		// ParamQuery 형식으로 데이터 가공
		$formatted_members = array();
		foreach ($members as $member) {
			$formatted_members[] = array(
				'member_idx' => $member['member_idx'],
				'member_name' => $member['member_name'],
				'photo' => $member['photo'] ? $member['photo'] : '',
				'leader_yn' => $member['leader_yn'],
				'new_yn' => $member['new_yn'],
				'member_birth' => $member['member_birth'],
				'grade' => $member['grade'],
				'area_name' => $member['area_name'] ? $member['area_name'] : '',
				'area_idx' => $member['area_idx']
			);
		}

		$response = array(
			'success' => true,
			'data' => $formatted_members,
			'totalRecords' => count($formatted_members)
		);

		header('Content-Type: application/json');
		echo json_encode($response);
	}

	/**
	 * 회원 정보 수정
	 */
	public function update_member() {
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$member_idx = $this->input->post('member_idx');
		$member_name = $this->input->post('member_name');
		$member_birth = $this->input->post('member_birth');
		$grade = $this->input->post('grade');
		$area_idx = $this->input->post('area_idx');
		$leader_yn = $this->input->post('leader_yn');
		$new_yn = $this->input->post('new_yn');

		if (!$member_idx) {
			echo json_encode(array('success' => false, 'message' => '회원 정보가 없습니다.'));
			return;
		}

		$update_data = array(
			'member_name' => $member_name,
			'member_birth' => $member_birth,
			'grade' => $grade,
			'area_idx' => $area_idx,
			'leader_yn' => $leader_yn,
			'new_yn' => $new_yn,
			'modi_date' => date('Y-m-d H:i:s')
		);

		$this->db->where('member_idx', $member_idx);
		$result = $this->db->update('wb_member', $update_data);

		if ($result) {
			echo json_encode(array('success' => true, 'message' => '회원 정보가 수정되었습니다.'));
		} else {
			echo json_encode(array('success' => false, 'message' => '회원 정보 수정에 실패했습니다.'));
		}
	}

	/**
	 * 회원 삭제 (del_yn = 'Y')
	 */
	public function delete_member() {
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$member_idx = $this->input->post('member_idx');

		if (!$member_idx) {
			echo json_encode(array('success' => false, 'message' => '회원 정보가 없습니다.'));
			return;
		}

		$update_data = array(
			'del_yn' => 'Y',
			'del_date' => date('Y-m-d H:i:s')
		);

		$this->db->where('member_idx', $member_idx);
		$result = $this->db->update('wb_member', $update_data);

		if ($result) {
			echo json_encode(array('success' => true, 'message' => '회원이 삭제되었습니다.'));
		} else {
			echo json_encode(array('success' => false, 'message' => '회원 삭제에 실패했습니다.'));
		}
	}
}
