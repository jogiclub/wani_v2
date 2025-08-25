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
	 * 그룹 트리 데이터 가져오기 (Fancytree용) - 계층적 구조 및 회원 수 표시
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

		// 현재 선택된 조직의 소그룹(area) 트리 구조로 가져오기
		$areas_tree = $this->Member_area_model->get_member_areas_tree($active_org_id);

		/**
		 * 파일 위치: application/controllers/Member.php - get_org_tree() 함수
		 * 역할: 트리 노드 생성 시 모든 노드를 기본 펼침 상태로 설정
		 */

		$build_fancytree_nodes = function($areas) use (&$build_fancytree_nodes, $active_org_id) {
			$nodes = array();

			foreach ($areas as $area) {
				// 해당 영역과 하위 영역들의 총 회원 수 계산
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

				// 자식 노드가 있으면 재귀적으로 처리
				if (!empty($area['children'])) {
					$node['children'] = $build_fancytree_nodes($area['children']);
					$node['expanded'] = true; // 모든 노드를 기본 펼침 상태로 변경
				}

				$nodes[] = $node;
			}

			return $nodes;
		};

		// Fancytree 형식의 자식 노드들 생성
		$children = $build_fancytree_nodes($areas_tree);

		// 조직 전체 회원 수 계산
		$org_total_members = $this->Member_model->get_org_member_count($active_org_id);
		$org_title = $current_org['org_name'];
		if ($org_total_members > 0) {
			$org_title .= ' (' . $org_total_members . '명)';
		}

		// 현재 조직 노드 생성
		$org_node = array(
			'key' => 'org_' . $current_org['org_id'],
			'title' => $org_title,
			'data' => array(
				'type' => 'org',
				'org_id' => $current_org['org_id'],
				'member_count' => $org_total_members
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
	 * 회원 목록 조회 (ParamQuery Grid용) - 계층적 구조 지원
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
			// 특정 소그룹과 그 하위 그룹들의 회원들 (계층적 조회)
			$members = $this->Member_model->get_area_members_with_children($org_id, $area_idx);
		} else if ($type === 'org') {
			// 조직의 모든 회원 가져오기
			$members = $this->Member_model->get_org_members($org_id);
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
				'member_address' => isset($member['member_address']) ? $member['member_address'] : '',
				'leader_yn' => isset($member['leader_yn']) ? $member['leader_yn'] : 'N',
				'new_yn' => isset($member['new_yn']) ? $member['new_yn'] : 'N',
				'member_birth' => isset($member['member_birth']) ? $member['member_birth'] : '',
				'grade' => isset($member['grade']) ? $member['grade'] : '',
				'area_name' => isset($member['area_name']) ? $member['area_name'] : '',
				'area_idx' => isset($member['area_idx']) ? $member['area_idx'] : '',
				'regi_date' => isset($member['regi_date']) ? $member['regi_date'] : '',
				'modi_date' => isset($member['modi_date']) ? $member['modi_date'] : ''
			);
		}

		echo json_encode(array(
			'success' => true,
			'data' => $formatted_members,
			'totalRecords' => count($formatted_members)
		));
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

		$org_id = $this->input->post('org_id');
		$area_idx = $this->input->post('area_idx');

		// 유효성 검증
		if (!$org_id) {
			echo json_encode(array('success' => false, 'message' => '조직 정보가 필요합니다.'));
			return;
		}

		// 가장 상위 그룹에서는 추가 불가 (area_idx가 없거나 빈 값인 경우)
		if (!$area_idx) {
			echo json_encode(array('success' => false, 'message' => '가장 상위 그룹에서는 회원을 추가할 수 없습니다.\n하위 그룹을 선택해주세요.'));
			return;
		}

		// 조직 정보 가져오기 (신규 회원 호칭 확인)
		$org_info = $this->Org_model->get_org_detail_by_id($org_id);
		if (!$org_info) {
			echo json_encode(array('success' => false, 'message' => '조직 정보를 찾을 수 없습니다.'));
			return;
		}

		$new_name = $org_info['new_name'] ? $org_info['new_name'] : '새가족';

		// 다음 회원 번호 생성
		$next_member_idx = $this->Member_model->get_next_member_idx($org_id);
		$member_name = $new_name . $next_member_idx;

		$insert_data = array(
			'member_name' => $member_name,
			'member_nick' => $member_name,
			'org_id' => $org_id,
			'area_idx' => $area_idx,
			'grade' => 0,
			'leader_yn' => 'N',
			'new_yn' => 'Y',
			'del_yn' => 'N',
			'regi_date' => date('Y-m-d H:i:s'),
			'modi_date' => date('Y-m-d H:i:s')
		);

		$result = $this->Member_model->add_member($insert_data);

		if ($result) {
			echo json_encode(array(
				'success' => true,
				'message' => '회원이 추가되었습니다.',
				'member_name' => $member_name
			));
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
	 * 다중 회원 삭제 (미분류: del_yn = 'Y', 소그룹: area_idx = null)
	 */
	public function delete_members()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$member_indices = $this->input->post('member_indices');
		$delete_type = $this->input->post('delete_type'); // 'unassigned' 또는 'area'

		if (!$member_indices || !is_array($member_indices)) {
			echo json_encode(array('success' => false, 'message' => '삭제할 회원 정보가 없습니다.'));
			return;
		}

		if ($delete_type === 'unassigned') {
			// 미분류에서의 삭제 - del_yn을 'Y'로 변경 (완전 삭제)
			$update_data = array(
				'del_yn' => 'Y',
				'del_date' => date('Y-m-d H:i:s')
			);
		} else {
			// 일반 소그룹에서의 삭제 - area_idx를 null로 변경 (미분류로 이동)
			$update_data = array(
				'area_idx' => null,
				'modi_date' => date('Y-m-d H:i:s')
			);
		}

		$this->db->where_in('member_idx', $member_indices);
		$result = $this->db->update('wb_member', $update_data);

		if ($result) {
			$count = count($member_indices);
			if ($delete_type === 'unassigned') {
				echo json_encode(array('success' => true, 'message' => "{$count}명의 회원이 삭제되었습니다."));
			} else {
				echo json_encode(array('success' => true, 'message' => "{$count}명의 회원이 미분류로 이동되었습니다."));
			}
		} else {
			echo json_encode(array('success' => false, 'message' => '회원 삭제에 실패했습니다.'));
		}
	}



}
