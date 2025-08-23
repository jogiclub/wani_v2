<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * 파일 위치: application/controllers/Member.php
 * 역할: 회원 관리 페이지 및 API 처리 (그룹별 회원 관리)
 */
class Member extends CI_Controller
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
	}

	/**
	 * 헤더에서 사용할 조직 데이터 준비
	 */
	private function prepare_header_data()
	{
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
	public function index()
	{
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
	public function get_org_tree()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$user_id = $this->session->userdata('user_id');

		// 현재 활성화된 조직 ID 가져오기
		$active_org_id = $this->input->cookie('activeOrg');

		if (!$active_org_id) {
			// 쿠키가 없으면 사용자의 첫 번째 조직 사용
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

		// 권한 확인 - 사용자가 해당 조직에 접근 권한이 있는지 확인
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

		// 현재 조직 노드 생성
		$org_node = array(
			'key' => 'org_' . $current_org['org_id'],
			'title' => $current_org['org_name'],
			'data' => array(
				'type' => 'org',
				'org_id' => $current_org['org_id']
			),
			'expanded' => true, // 기본적으로 펼쳐진 상태
			'children' => $children
		);

		// 트리 데이터 배열 초기화
		$tree_data = array($org_node);

		// 소속 그룹이 없는 회원 수 확인
		$unassigned_members_count = $this->Member_model->get_unassigned_members_count($active_org_id);

		// 미분류 그룹이 있는 경우 root 레벨에 추가 (조직과 동일한 depth)
		if ($unassigned_members_count > 0) {
			$unassigned_node = array(
				'key' => 'unassigned_' . $active_org_id,
				'title' => '미분류 (' . $unassigned_members_count . '명)',
				'data' => array(
					'type' => 'unassigned',
					'org_id' => $active_org_id,
					'area_idx' => null
				)
			);
			$tree_data[] = $unassigned_node;
		}

		header('Content-Type: application/json');
		echo json_encode($tree_data);
	}
	/**
	 * 회원 목록 조회 (ParamQuery Grid용)
	 */
	public function get_members()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$type = $this->input->post('type');
		$org_id = $this->input->post('org_id');
		$area_idx = $this->input->post('area_idx');

		if (!$org_id) {
			echo json_encode(array('success' => false, 'message' => '그룹 ID가 필요합니다.'));
			return;
		}

		// 사용자 권한 확인
		$user_id = $this->session->userdata('user_id');
		$master_yn = $this->session->userdata('master_yn');

		if ($master_yn === "N") {
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

		// 타입에 따라 회원 데이터 가져오기
		if ($type === 'unassigned') {
			// 소속 그룹이 없는 회원들
			$members = $this->Member_model->get_unassigned_members($org_id);
		} else if ($type === 'area' && $area_idx) {
			// 특정 소그룹의 회원들
			$members = $this->Member_model->get_org_members($org_id);
			$members = array_filter($members, function ($member) use ($area_idx) {
				return $member['area_idx'] == $area_idx;
			});
			$members = array_values($members); // 인덱스 재정렬
		} else {
			// 기본적으로 그룹의 모든 회원 가져오기
			$members = $this->Member_model->get_org_members($org_id);
		}

		// ParamQuery 형식으로 데이터 가공 - 안전한 배열 접근
		$formatted_members = array();
		foreach ($members as $member) {
			// 사진 URL 처리
			$photo_url = '/assets/images/photo_no.png'; // 기본 이미지
			if (isset($member['photo']) && $member['photo']) {
				// photo 필드에 파일명만 있는 경우 전체 경로 구성
				if (strpos($member['photo'], '/uploads/') === false) {
					$photo_url = '/uploads/member_photos/' . $org_id . '/' . $member['photo'];
				} else {
					// 이미 전체 경로가 있는 경우 그대로 사용
					$photo_url = $member['photo'];
				}
			}

			$formatted_members[] = array(
				'member_idx' => isset($member['member_idx']) ? $member['member_idx'] : '',
				'member_name' => isset($member['member_name']) ? $member['member_name'] : '',
				'photo' => $photo_url,
				'member_phone' => isset($member['member_phone']) ? $member['member_phone'] : '',
				'member_birth' => isset($member['member_birth']) ? $member['member_birth'] : '',
				'member_address' => isset($member['member_address']) ? $member['member_address'] : '',
				'leader_yn' => isset($member['leader_yn']) ? $member['leader_yn'] : 'N',
				'new_yn' => isset($member['new_yn']) ? $member['new_yn'] : 'N',
				'regi_date' => isset($member['regi_date']) ? $member['regi_date'] : '',
				'modi_date' => isset($member['modi_date']) ? $member['modi_date'] : '',
				'area_name' => isset($member['area_name']) ? $member['area_name'] : '',
				'area_idx' => isset($member['area_idx']) ? $member['area_idx'] : '',
				'grade' => isset($member['grade']) ? $member['grade'] : ''
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
	 * 회원 사진 업로드
	 */
	public function upload_member_photo()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$member_idx = $this->input->post('member_idx');
		$org_id = $this->input->post('org_id');

		if (!$member_idx || !$org_id) {
			echo json_encode(array('success' => false, 'message' => '필수 정보가 누락되었습니다.'));
			return;
		}

		// 업로드 디렉토리 설정
		$upload_path = './uploads/member_photos/' . $org_id . '/';

		// 디렉토리가 없으면 생성
		if (!is_dir($upload_path)) {
			mkdir($upload_path, 0755, true);
		}

		$config['upload_path'] = $upload_path;
		$config['allowed_types'] = 'gif|jpg|png|jpeg';
		$config['max_size'] = 2048; // 2MB
		$config['file_name'] = 'member_' . $member_idx . '_' . time();

		$this->load->library('upload', $config);

		if ($this->upload->do_upload('photo')) {
			$upload_data = $this->upload->data();
			$file_name = $upload_data['file_name'];

			// 데이터베이스에 파일명 저장
			$update_data = array(
				'photo' => $file_name,
				'modi_date' => date('Y-m-d H:i:s')
			);

			$this->db->where('member_idx', $member_idx);
			$result = $this->db->update('wb_member', $update_data);

			if ($result) {
				$photo_url = '/uploads/member_photos/' . $org_id . '/' . $file_name;
				echo json_encode(array(
					'success' => true,
					'message' => '사진이 업로드되었습니다.',
					'photo_url' => $photo_url
				));
			} else {
				echo json_encode(array('success' => false, 'message' => '데이터베이스 업데이트에 실패했습니다.'));
			}
		} else {
			$error = $this->upload->display_errors();
			echo json_encode(array('success' => false, 'message' => '사진 업로드에 실패했습니다: ' . $error));
		}
	}

	/**
	 * 회원 추가
	 */
	public function add_member()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$member_name = $this->input->post('member_name');
		$member_phone = $this->input->post('member_phone');
		$member_birth = $this->input->post('member_birth');
		$member_address = $this->input->post('member_address');
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
			'member_phone' => $member_phone ? $member_phone : null,
			'member_birth' => $member_birth ? $member_birth : null,
			'member_address' => $member_address ? $member_address : null,
			'grade' => $grade ? $grade : null,
			'area_idx' => $area_idx ? $area_idx : null,
			'leader_yn' => $leader_yn ? $leader_yn : 'N',
			'new_yn' => $new_yn ? $new_yn : 'N',
			'org_id' => $org_id,
			'regi_date' => date('Y-m-d H:i:s'),
			'modi_date' => date('Y-m-d H:i:s'),
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
	public function update_member()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$member_idx = $this->input->post('member_idx');
		$member_name = $this->input->post('member_name');
		$member_phone = $this->input->post('member_phone');
		$member_birth = $this->input->post('member_birth');
		$member_address = $this->input->post('member_address');
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
			'member_phone' => $member_phone ? $member_phone : null,
			'member_birth' => $member_birth ? $member_birth : null,
			'member_address' => $member_address ? $member_address : null,
			'grade' => $grade ? $grade : null,
			'area_idx' => $area_idx ? $area_idx : null,
			'leader_yn' => $leader_yn ? $leader_yn : 'N',
			'new_yn' => $new_yn ? $new_yn : 'N',
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
	 * 다중 회원 삭제 (del_yn = 'Y')
	 */
	public function delete_members()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$member_indices = $this->input->post('member_indices');

		if (!$member_indices || !is_array($member_indices)) {
			echo json_encode(array('success' => false, 'message' => '삭제할 회원 정보가 없습니다.'));
			return;
		}

		$update_data = array(
			'del_yn' => 'Y',
			'del_date' => date('Y-m-d H:i:s')
		);

		$this->db->where_in('member_idx', $member_indices);
		$result = $this->db->update('wb_member', $update_data);

		if ($result) {
			$count = count($member_indices);
			echo json_encode(array('success' => true, 'message' => "{$count}명의 회원이 삭제되었습니다."));
		} else {
			echo json_encode(array('success' => false, 'message' => '회원 삭제에 실패했습니다.'));
		}
	}

}
