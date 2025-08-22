<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * 파일 위치: application/controllers/Member.php
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
	 * 헤더에서 사용할 조직 데이터 준비
	 */
	private function prepare_header_data() {
		if (!$this->session->userdata('user_id')) {
			return array();
		}

		$user_id = $this->session->userdata('user_id');
		$master_yn = $this->session->userdata('master_yn');

		// 사용자 정보 가져오기
		$user_data = $this->User_model->get_user_by_id($user_id);

		// 사용자가 접근 가능한 조직 목록 가져오기
		if ($master_yn === "N") {
			$user_orgs = $this->Org_model->get_user_orgs($user_id);
		} else {
			$user_orgs = $this->Org_model->get_user_orgs_master($user_id);
		}

		// 현재 활성화된 조직 정보
		$active_org_id = $this->input->cookie('activeOrg');
		$current_org = null;

		if ($active_org_id) {
			foreach ($user_orgs as $org) {
				if ($org['org_id'] == $active_org_id) {
					$current_org = $org;
					break;
				}
			}
		}

		// 활성화된 조직이 없거나 유효하지 않으면 첫 번째 조직을 기본값으로 설정
		if (!$current_org && !empty($user_orgs)) {
			$current_org = $user_orgs[0];
			$this->input->set_cookie('activeOrg', $current_org['org_id'], 86400);
		}

		return array(
			'user' => $user_data,
			'user_orgs' => $user_orgs,
			'current_org' => $current_org
		);
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

		// 헤더 데이터 준비
		$header_data = $this->prepare_header_data();
		if (empty($header_data['user_orgs'])) {
			redirect('mypage');
			return;
		}

		$data = $header_data;
		$currentOrgId = $data['current_org']['org_id'];

		// POST로 조직 변경 요청이 있는 경우 처리
		$postOrgId = $this->input->post('org_id');
		if ($postOrgId) {
			// 사용자가 해당 조직에 접근 권한이 있는지 확인
			$has_access = false;
			foreach ($data['user_orgs'] as $org) {
				if ($org['org_id'] == $postOrgId) {
					$has_access = true;
					$data['current_org'] = $org;
					$currentOrgId = $postOrgId;
					$this->input->set_cookie('activeOrg', $postOrgId, 86400);
					break;
				}
			}

			if (!$has_access) {
				show_error('접근 권한이 없는 조직입니다.', 403);
				return;
			}
		}

		// 현재 조직 정보를 JavaScript로 전달하기 위해 orgs 배열에 추가
		$data['orgs'] = array($data['current_org']);

		$this->load->view('member', $data);
	}

	/**
	 * 그룹 트리 데이터 가져오기 (Fancytree용) - 현재 선택된 조직만
	 */
	public function get_org_tree() {
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$user_id = $this->session->userdata('user_id');

		// 현재 활성화된 조직 ID 가져오기
		$active_org_id = $this->input->cookie('activeOrg');

		if (!$active_org_id) {
			// 쿠키가 없으면 사용자의 첫 번째 조직 사용
			$master_yn = $this->session->userdata('master_yn');
			if($master_yn === "N"){
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

		// 권한 확인 - 사용자가 해당 조직에 접근 권한이 있는지 확인
		$master_yn = $this->session->userdata('master_yn');
		if($master_yn === "N"){
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

		// 현재 선택된 조직의 소그룹(area) 가져오기
		$areas = $this->Member_area_model->get_member_areas($active_org_id);

		$children = array();
		foreach ($areas as $area) {
			$children[] = array(
				'key' => 'area_' . $area['area_idx'],
				'title' => $area['area_name'],
				'data' => array(
					'type' => 'area',
					'org_id' => $active_org_id,
					'area_idx' => $area['area_idx']
				)
			);
		}

		// 현재 조직만 포함된 트리 데이터
		$tree_data = array(
			array(
				'key' => 'org_' . $current_org['org_id'],
				'title' => $current_org['org_name'],
				'data' => array(
					'type' => 'org',
					'org_id' => $current_org['org_id']
				),
				'expanded' => true, // 기본적으로 펼쳐진 상태
				'children' => $children
			)
		);

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

		// 사용자 권한 확인
		$user_id = $this->session->userdata('user_id');
		$master_yn = $this->session->userdata('master_yn');

		if($master_yn === "N"){
			$user_orgs = $this->Org_model->get_user_orgs($user_id);
		} else {
			$user_orgs = $this->Org_model->get_user_orgs_master($user_id);
		}

		$has_access = false;
		foreach ($user_orgs as $org) {
			if ($org['org_id'] == $org_id) {
				$has_access = true;
				break;
			}
		}

		if (!$has_access) {
			echo json_encode(array('success' => false, 'message' => '접근 권한이 없습니다.'));
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
	 * 회원 추가
	 */
	public function add_member() {
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$member_name = $this->input->post('member_name');
		$member_birth = $this->input->post('member_birth');
		$grade = $this->input->post('grade');
		$area_idx = $this->input->post('area_idx');
		$leader_yn = $this->input->post('leader_yn');
		$new_yn = $this->input->post('new_yn');
		$org_id = $this->input->post('org_id');

		if (!$member_name || !$org_id) {
			echo json_encode(array('success' => false, 'message' => '필수 정보가 누락되었습니다.'));
			return;
		}

		$insert_data = array(
			'member_name' => $member_name,
			'member_birth' => $member_birth,
			'grade' => $grade,
			'area_idx' => $area_idx,
			'leader_yn' => $leader_yn,
			'new_yn' => $new_yn,
			'org_id' => $org_id,
			'regi_date' => date('Y-m-d H:i:s'),
			'del_yn' => 'N'
		);

		$result = $this->db->insert('wb_member', $insert_data);

		if ($result) {
			echo json_encode(array('success' => true, 'message' => '회원이 추가되었습니다.'));
		} else {
			echo json_encode(array('success' => false, 'message' => '회원 추가에 실패했습니다.'));
		}
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
