<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * 파일 위치: application/controllers/Member.php
 * 역할: 회원 관리 페이지 및 API 처리 (그룹별 회원 관리)
 */
class Member extends My_Controller
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
	 * 회원 관리 메인 페이지
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

		// 사용자의 조직 접근 권한 및 관리 권한 확인
		if (!$this->check_org_access($currentOrgId)) {
			$this->handle_access_denied('해당 조직의 회원을 관리할 권한이 없습니다.');
			return;
		}



		// 현재 조직 정보를 JavaScript로 전달하기 위해 orgs 배열에 추가
		$data['orgs'] = array($data['current_org']);

		$this->load->view('member', $data);
	}


	/**
	 * 파일 위치: application/controllers/Member.php - get_org_tree() 함수
	 * 역할: 사용자 권한에 따라 관리 가능한 그룹만 표시하도록 필터링
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

		// 사용자의 권한 레벨과 관리 가능한 그룹 정보 가져오기
		$user_level = $this->User_model->get_org_user_level($user_id, $active_org_id);
		$accessible_areas = array();

		// 최고관리자(레벨 10)이거나 마스터가 아닌 경우 관리 그룹 권한 확인
		if ($user_level < 10 && $master_yn !== 'Y') {
			// 사용자의 관리 그룹과 모든 하위 그룹 정보 가져오기 (1학년 권한이 있으면 1반, 2반, 3반도 포함)
			$this->load->model('User_management_model');
			$accessible_areas = $this->User_management_model->get_user_managed_areas_with_children($user_id, $active_org_id);
		}

		// 현재 선택된 조직의 소그룹(area) 트리 구조로 가져오기
		$areas_tree = $this->Member_area_model->get_member_areas_tree($active_org_id);

		// 권한에 따른 그룹 필터링 함수
		$filter_areas_by_permission = function ($areas) use (&$filter_areas_by_permission, $user_level, $master_yn, $accessible_areas) {
			// 최고관리자이거나 마스터인 경우 모든 그룹 표시
			if ($user_level >= 10 || $master_yn === 'Y') {
				return $areas;
			}

			// 관리 권한이 없는 경우 빈 배열 반환
			if (empty($accessible_areas)) {
				return array();
			}

			$filtered_areas = array();

			foreach ($areas as $area) {
				// 현재 그룹이 접근 가능한 그룹에 포함되어 있는지 확인 (부모 그룹 권한 포함)
				if (in_array($area['area_idx'], $accessible_areas)) {
					// 하위 그룹도 재귀적으로 필터링
					if (!empty($area['children'])) {
						$area['children'] = $filter_areas_by_permission($area['children']);
					}
					$filtered_areas[] = $area;
				} else {
					// 현재 그룹은 직접 권한이 없지만 하위 그룹 중 접근 가능한 것이 있는지 확인
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

		// 권한에 따라 그룹 필터링 적용
		$filtered_areas_tree = $filter_areas_by_permission($areas_tree);

		$build_fancytree_nodes = function ($areas) use (&$build_fancytree_nodes, $active_org_id) {
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
					$node['expanded'] = true;
				}

				$nodes[] = $node;
			}

			return $nodes;
		};

		// Fancytree 형식의 자식 노드들 생성 (필터링된 그룹만)
		$children = $build_fancytree_nodes($filtered_areas_tree);

		// 조직 전체 회원 수 계산 (권한이 있는 그룹의 회원들만)
		if ($user_level >= 10 || $master_yn === 'Y') {
			// 최고관리자인 경우 전체 회원 수
			$org_total_members = $this->Member_model->get_org_member_count($active_org_id);
		} else {
			// 일반 관리자인 경우 접근 가능한 그룹의 회원 수만 (하위 그룹 포함)
			$org_total_members = 0;
			if (!empty($accessible_areas)) {
				// 중복 계산 방지를 위해 실제 관리 권한이 있는 최상위 그룹들만 계산
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

		// 현재 조직 노드 생성
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

		// 트리 데이터 배열 초기화
		$tree_data = array($org_node);

		// 미분류 그룹 처리 (최고관리자만 표시)
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
	 * 회원 목록 조회 (상세필드 포함) - 기존 로직 유지
	 */
	public function get_members() {
		$type = $this->input->post('type');
		$org_id = $this->input->post('org_id');
		$area_idx = $this->input->post('area_idx');

		if (!$org_id) {
			echo json_encode(array('success' => false, 'message' => '조직 정보가 필요합니다.'));
			return;
		}

		$user_id = $this->session->userdata('user_id');
		$master_yn = $this->session->userdata('master_yn');

		$this->load->model('User_model');
		$user_level = $this->User_model->get_org_user_level($user_id, $org_id);

		// 권한 확인을 위한 접근 가능한 영역 목록 가져오기
		$accessible_areas = array();
		if ($user_level < 10 && $master_yn !== 'Y') {
			$this->load->model('User_management_model');
			$accessible_areas = $this->User_management_model->get_user_managed_areas_with_children($user_id, $org_id);

			if (empty($accessible_areas)) {
				echo json_encode(array('success' => false, 'message' => '조회할 수 있는 그룹이 없습니다.'));
				return;
			}
		}

		// 타입에 따라 회원 데이터 가져오기 (기존 로직)
		$members = array();

		if ($type === 'unassigned') {
			if ($user_level < 10 && $master_yn !== 'Y') {
				echo json_encode(array('success' => false, 'message' => '미분류 그룹을 조회할 권한이 없습니다.'));
				return;
			}
			$members = $this->Member_model->get_unassigned_members($org_id);

		} else if ($type === 'area' && $area_idx) {
			if ($user_level < 10 && $master_yn !== 'Y') {
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
				$members = $unique_members;
			}
		}

		// 상세필드 정의 가져오기
		$this->load->model('Detail_field_model');
		$detail_fields = $this->Detail_field_model->get_detail_fields_by_org($org_id);

		// 각 회원의 상세필드 데이터 추가
		if (!empty($members) && !empty($detail_fields)) {
			foreach ($members as &$member) {
				$member_detail = $this->get_member_detail_data($org_id, $member['member_idx']);

				// 상세필드 데이터를 회원 정보에 병합
				foreach ($detail_fields as $field) {
					$field_key = 'detail_' . $field['field_idx'];
					$member[$field_key] = isset($member_detail[$field['field_idx']]) ? $member_detail[$field['field_idx']] : '';
				}
			}
		}

		// 사진 URL 처리 (기존 로직 유지)
		foreach ($members as &$member) {
			$photo_url = '/assets/images/photo_no.png';
			if (isset($member['photo']) && $member['photo']) {
				if (strpos($member['photo'], '/uploads/') === false) {
					$photo_url = '/uploads/member_photos/' . $org_id . '/' . $member['photo'];
				} else {
					$photo_url = $member['photo'];
				}
			}
			$member['photo'] = $photo_url;
		}

		echo json_encode(array(
			'success' => true,
			'data' => $members,
			'detail_fields' => $detail_fields
		));
	}

	/**
	 * 회원 상세필드 데이터 조회 (내부 함수)
	 */
	private function get_member_detail_data($org_id, $member_idx) {
		$this->db->select('member_detail');
		$this->db->from('wb_member');
		$this->db->where('org_id', $org_id);
		$this->db->where('member_idx', $member_idx);
		$query = $this->db->get();

		$row = $query->row_array();

		if ($row && !empty($row['member_detail'])) {
			$detail_data = json_decode($row['member_detail'], true);
			return is_array($detail_data) ? $detail_data : array();
		}

		return array();
	}




	/**
	 * 크롭된 이미지 업로드 처리
	 */
	private function handleCroppedImageUpload($member_idx, $org_id)
	{
		try {
			// 업로드 디렉토리 설정
			$upload_path = './uploads/member_photos/' . $org_id . '/';

			// 디렉토리가 없으면 생성
			if (!is_dir($upload_path)) {
				if (!mkdir($upload_path, 0755, true)) {
					return array('success' => false, 'message' => '업로드 디렉토리 생성에 실패했습니다.');
				}
			}

			// 기존 파일 삭제
			$existing_member = $this->Member_model->get_member_by_idx($member_idx);
			if (!empty($existing_member['photo'])) {
				$existing_file = $upload_path . $existing_member['photo'];
				if (file_exists($existing_file)) {
					unlink($existing_file);
				}
			}

			// 업로드 설정
			$config['upload_path'] = $upload_path;
			$config['allowed_types'] = 'gif|jpg|jpeg|png';
			$config['max_size'] = 5120; // 5MB
			$config['file_name'] = 'member_' . $member_idx . '_' . time();
			$config['overwrite'] = true;

			$this->load->library('upload', $config);

			if ($this->upload->do_upload('member_photo')) {
				$upload_data = $this->upload->data();

				// 크롭된 이미지는 이미 최적화되어 있으므로 추가 리사이즈는 선택적으로 적용
				$this->optimizeCroppedImage($upload_data['full_path']);

				return array(
					'success' => true,
					'file_name' => $upload_data['file_name'],
					'photo_url' => base_url('uploads/member_photos/' . $org_id . '/' . $upload_data['file_name'])
				);
			} else {
				$upload_error = $this->upload->display_errors();
				return array('success' => false, 'message' => '이미지 업로드에 실패했습니다: ' . strip_tags($upload_error));
			}

		} catch (Exception $e) {
			log_message('error', '이미지 업로드 오류: ' . $e->getMessage());
			return array('success' => false, 'message' => '이미지 업로드 중 오류가 발생했습니다.');
		}
	}

	/**
	 * 크롭된 이미지 최적화 (선택적 적용)
	 */
	private function optimizeCroppedImage($image_path)
	{
		try {
			$this->load->library('image_lib');

			// 이미지 정보 가져오기
			$image_info = getimagesize($image_path);

			// 이미지가 200x200보다 크면 리사이즈 (크롭된 이미지는 보통 이미 최적 크기)
			if ($image_info && ($image_info[0] > 200 || $image_info[1] > 200)) {
				$image_config = array(
					'image_library' => 'gd2',
					'source_image' => $image_path,
					'maintain_ratio' => FALSE,  // 크롭된 이미지는 이미 정사각형이므로 비율 유지 안함
					'width' => 200,
					'height' => 200,
					'quality' => 90
				);

				$this->image_lib->clear();
				$this->image_lib->initialize($image_config);

				if (!$this->image_lib->resize()) {
					log_message('error', '이미지 리사이즈 실패: ' . $this->image_lib->display_errors());
				}
			}

		} catch (Exception $e) {
			log_message('error', '이미지 최적화 오류: ' . $e->getMessage());
		}
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
	 * 역할: 회원 추가 (상세정보 포함, 직위/직책 필드 추가)
	 */

	public function add_member()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$org_id = $this->input->post('org_id');
		$member_name = $this->input->post('member_name');
		$member_sex = $this->input->post('member_sex');
		$area_idx = $this->input->post('area_idx');

		if (!$org_id) {
			echo json_encode(array('success' => false, 'message' => '필수 정보가 누락되었습니다.'));
			return;
		}

		// member_name이 없으면 임시 이름으로 설정 (나중에 업데이트)
		if (!$member_name || trim($member_name) === '') {
			$member_name = '새회원';
		}

		$data = array(
			'org_id' => $org_id,
			'member_name' => $member_name,
			'member_sex' => $member_sex ?: null,
			'area_idx' => $area_idx ?: null,
			'regi_date' => date('Y-m-d H:i:s'),
			'del_yn' => 'N',
			'leader_yn' => 'N',
			'new_yn' => 'Y',
			'grade' => 0
		);

		$this->load->model('Member_model');
		$result = $this->Member_model->add_member($data);

		if ($result) {
			// 방금 추가된 회원의 member_idx 가져오기
			$member_idx = $this->db->insert_id();

			// member_name이 "새회원"인 경우 member_idx를 포함한 이름으로 업데이트
			if ($member_name === '새회원') {
				$updated_name = '새회원_' . $member_idx;
				$this->db->where('member_idx', $member_idx);
				$this->db->update('wb_member', array('member_name' => $updated_name));
				$member_name = $updated_name;
			}

			echo json_encode(array(
				'success' => true,
				'message' => $member_name . '님이 추가되었습니다.',
				'member_name' => $member_name,
				'member_idx' => $member_idx
			));
		} else {
			echo json_encode(array('success' => false, 'message' => '회원 추가에 실패했습니다.'));
		}
	}

	/**
	 * 회원 정보 수정 (상세필드 포함)
	 */
	public function update_member() {
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$member_idx = $this->input->post('member_idx');
		$org_id = $this->input->post('org_id');

		if (!$member_idx || !$org_id) {
			echo json_encode(array('success' => false, 'message' => '필수 정보가 누락되었습니다.'));
			return;
		}

		$this->load->model('Member_model');

		// 기본 회원 정보 업데이트
		$update_data = array(
			'member_name' => $this->input->post('member_name'),
			'member_sex' => $this->input->post('member_sex') ?: null,
			'member_nick' => $this->input->post('member_nick'),
			'position_name' => $this->input->post('position_name'),
			'duty_name' => $this->input->post('duty_name'),
			'member_phone' => $this->input->post('member_phone'),
			'member_birth' => $this->input->post('member_birth'),
			'member_address' => $this->input->post('member_address'),
			'member_address_detail' => $this->input->post('member_address_detail'),
			'member_etc' => $this->input->post('member_etc'),
			'area_idx' => $this->input->post('area_idx'),
			'leader_yn' => $this->input->post('leader_yn') ? 'Y' : 'N',
			'new_yn' => $this->input->post('new_yn') ? 'Y' : 'N',
			'modi_date' => date('Y-m-d H:i:s')
		);

		// 사진 처리
		if ($this->input->post('delete_photo') === 'Y') {
			$update_data['photo'] = '';
		} elseif (!empty($_FILES['member_photo']['name'])) {
			$photo_path = $this->upload_member_photo($org_id, $member_idx);
			if ($photo_path) {
				$update_data['photo'] = $photo_path;
			}
		}

		// 상세필드 데이터 처리 (수정된 버전)
		$detail_data = $this->input->post('detail_field');
		if (!empty($detail_data)) {
			// JSON 문자열로 전달된 경우 디코딩
			if (is_string($detail_data)) {
				$detail_array = json_decode($detail_data, true);
				if (json_last_error() === JSON_ERROR_NONE && is_array($detail_array)) {
					// 빈 값들을 제거
					$filtered_detail = array();
					foreach ($detail_array as $field_idx => $value) {
						if ($value !== '' && $value !== null) {
							$filtered_detail[$field_idx] = $value;
						}
					}
					if (!empty($filtered_detail)) {
						$update_data['member_detail'] = json_encode($filtered_detail, JSON_UNESCAPED_UNICODE);
					} else {
						$update_data['member_detail'] = null;
					}
				}
			}
			// 배열로 전달된 경우 (직접 배열로 전송하는 경우)
			elseif (is_array($detail_data)) {
				// 빈 값들을 제거
				$filtered_detail = array();
				foreach ($detail_data as $field_idx => $value) {
					if ($value !== '' && $value !== null) {
						$filtered_detail[$field_idx] = $value;
					}
				}
				if (!empty($filtered_detail)) {
					$update_data['member_detail'] = json_encode($filtered_detail, JSON_UNESCAPED_UNICODE);
				} else {
					$update_data['member_detail'] = null;
				}
			}
		}

		$this->db->where('member_idx', $member_idx);
		$this->db->where('org_id', $org_id);
		$result = $this->db->update('wb_member', $update_data);

		if ($result) {
			echo json_encode(array(
				'success' => true,
				'message' => '회원 정보가 수정되었습니다.'
			));
		} else {
			echo json_encode(array(
				'success' => false,
				'message' => '회원 정보 수정에 실패했습니다.'
			));
		}
	}

	public function member_popup()
	{
		$user_id = $this->session->userdata('user_id');
		$org_id = $this->input->cookie('activeOrg');

		if (!$user_id || !$org_id) {
			echo '<script>alert("인증 정보가 없습니다."); window.close();</script>';
			return;
		}

		// 소그룹 목록 가져오기
		$this->load->model('Member_area_model');
		$member_areas = $this->Member_area_model->get_member_areas($org_id);

		$data = array(
			'member_areas' => $member_areas,
			'org_id' => $org_id
		);

		$this->load->view('member_popup', $data);
	}


	/**
	 * 일괄 편집 데이터 저장
	 */
	public function save_member_popup()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$org_id = $this->input->post('org_id');
		$members_json = $this->input->post('members');

		if (!$org_id || !$members_json) {
			echo json_encode([
				'success' => false,
				'message' => '필수 데이터가 누락되었습니다.'
			]);
			return;
		}

		$members = json_decode($members_json, true);

		if (!is_array($members) || empty($members)) {
			echo json_encode([
				'success' => false,
				'message' => '유효한 데이터가 없습니다.'
			]);
			return;
		}

		$this->load->model('Member_model');
		$this->load->model('Detail_field_model');

		$detail_fields = $this->Detail_field_model->get_detail_fields_by_org($org_id);
		$detail_field_indices = array();
		foreach ($detail_fields as $field) {
			$detail_field_indices[] = (string)$field['field_idx'];
		}

		$this->db->trans_start();

		$success_count = 0;
		$error_count = 0;
		$error_messages = array();

		foreach ($members as $member) {
			try {
				$member_idx = isset($member['member_idx']) && $member['member_idx'] !== ''
					? $member['member_idx']
					: null;

				$update_data = [
					'member_name' => trim($member['member_name']),
					'modi_date' => date('Y-m-d H:i:s')
				];

				// 기본 필드 처리 (member_sex 추가)
				$basic_fields = ['member_sex', 'position_name', 'member_phone', 'duty_name', 'member_nick',
					'member_birth', 'member_address', 'member_address_detail', 'member_etc'];

				foreach ($basic_fields as $field) {
					if (isset($member[$field])) {
						$update_data[$field] = trim($member[$field]);
					}
				}

				if (isset($member['area_idx']) && $member['area_idx'] !== '') {
					$update_data['area_idx'] = $member['area_idx'];
				}

				// member_detail 처리
				$detail_data = array();
				$has_detail_fields = false;

				foreach ($member as $key => $value) {
					if (strpos($key, 'detail_') === 0) {
						$has_detail_fields = true;
						$field_idx = str_replace('detail_', '', $key);

						if (in_array($field_idx, $detail_field_indices)) {
							if ($value !== '' && $value !== null) {
								$detail_data[$field_idx] = trim($value);
							}
						}
					}
				}

				if ($member_idx && $has_detail_fields) {
					$existing_member = $this->Member_model->get_member_by_idx($member_idx);
					if ($existing_member && !empty($existing_member['member_detail'])) {
						$existing_detail = json_decode($existing_member['member_detail'], true);
						if (is_array($existing_detail)) {
							foreach ($existing_detail as $existing_key => $existing_value) {
								if (!isset($member['detail_' . $existing_key])) {
									$detail_data[$existing_key] = $existing_value;
								}
							}
						}
					}
				}

				if (!empty($detail_data)) {
					$update_data['member_detail'] = json_encode($detail_data, JSON_UNESCAPED_UNICODE);
				} elseif ($has_detail_fields && $member_idx) {
					$update_data['member_detail'] = null;
				}

				if ($member_idx) {
					$result = $this->Member_model->update_member($member_idx, $update_data, $org_id);
				} else {
					$update_data['org_id'] = $org_id;
					$update_data['regi_date'] = date('Y-m-d H:i:s');
					$update_data['del_yn'] = 'N';
					$update_data['leader_yn'] = 'N';
					$update_data['new_yn'] = 'Y';
					$update_data['grade'] = 0;
					$result = $this->Member_model->add_member($update_data);
				}

				if ($result) {
					$success_count++;
				} else {
					$error_count++;
					$error_messages[] = $member['member_name'] . ' 저장 실패';
				}
			} catch (Exception $e) {
				$error_count++;
				$error_messages[] = $member['member_name'] . ': ' . $e->getMessage();
				log_message('error', '일괄 편집 저장 오류: ' . $e->getMessage());
			}
		}

		$this->db->trans_complete();

		if ($this->db->trans_status() === FALSE) {
			echo json_encode([
				'success' => false,
				'message' => '데이터 저장 중 오류가 발생했습니다.'
			]);
			return;
		}

		$message = "저장 완료: 성공 {$success_count}건";
		if ($error_count > 0) {
			$message .= ", 실패 {$error_count}건";
			if (!empty($error_messages)) {
				$message .= "\n오류 상세: " . implode(', ', array_slice($error_messages, 0, 3));
				if (count($error_messages) > 3) {
					$message .= ' 외 ' . (count($error_messages) - 3) . '건';
				}
			}
		}

		echo json_encode([
			'success' => $error_count === 0,
			'message' => $message,
			'success_count' => $success_count,
			'error_count' => $error_count
		]);
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

	/**
	 * 다중 회원 이동 (소그룹 변경)
	 */
	public function move_members()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$member_indices = $this->input->post('member_indices');
		$move_to_area_idx = $this->input->post('move_to_area_idx');
		$org_id = $this->input->post('org_id');

		if (!$member_indices || !is_array($member_indices)) {
			echo json_encode(array('success' => false, 'message' => '이동할 회원 정보가 없습니다.'));
			return;
		}

		if (!$move_to_area_idx) {
			echo json_encode(array('success' => false, 'message' => '이동할 소그룹을 선택해주세요.'));
			return;
		}

		if (!$org_id) {
			echo json_encode(array('success' => false, 'message' => '조직 정보가 필요합니다.'));
			return;
		}

		// 사용자 권한 확인
		$user_id = $this->session->userdata('user_id');
		$master_yn = $this->session->userdata('master_yn');

		// 조직 접근 권한 확인
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

		// 대상 소그룹이 해당 조직에 속하는지 확인
		$this->load->model('Member_area_model');
		$target_area = $this->Member_area_model->get_area_by_idx($move_to_area_idx);
		if (!$target_area || $target_area['org_id'] != $org_id) {
			echo json_encode(array('success' => false, 'message' => '유효하지 않은 소그룹입니다.'));
			return;
		}

		// 회원 이동 처리
		$update_data = array(
			'area_idx' => $move_to_area_idx,
			'modi_date' => date('Y-m-d H:i:s')
		);

		$this->db->where_in('member_idx', $member_indices);
		$this->db->where('org_id', $org_id); // 추가 보안 체크
		$result = $this->db->update('wb_member', $update_data);

		if ($result) {
			$count = count($member_indices);
			$target_area_name = $target_area['area_name'];
			echo json_encode(array(
				'success' => true,
				'message' => "{$count}명의 회원이 '{$target_area_name}' 소그룹으로 이동되었습니다."
			));
		} else {
			echo json_encode(array('success' => false, 'message' => '회원 이동에 실패했습니다.'));
		}
	}


	/**
	 * 파일 위치: application/controllers/Member.php
	 * 역할: 회원 상세정보 관련 AJAX 처리 메서드들 추가
	 */

	/**
	 * 조직의 활성화된 상세필드 목록 가져오기
	 */
	public function get_detail_fields() {
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$org_id = $this->input->post('org_id');

		if (!$org_id) {
			echo json_encode(array('success' => false, 'message' => '조직 정보가 필요합니다.'));
			return;
		}

		$this->load->model('Detail_field_model');
		$fields = $this->Detail_field_model->get_detail_fields_by_org($org_id);

		// field_settings JSON 디코딩
		foreach ($fields as &$field) {
			if (!empty($field['field_settings'])) {
				$field['field_settings'] = json_decode($field['field_settings'], true);
			}
		}

		echo json_encode(array(
			'success' => true,
			'data' => $fields
		));
	}

	/**
	 * 회원 상세정보 저장
	 */
	public function save_member_detail() {
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$org_id = $this->input->post('org_id');
		$member_idx = $this->input->post('member_idx');
		$detail_data = $this->input->post('detail_data');

		if (!$org_id || !$member_idx) {
			echo json_encode(array('success' => false, 'message' => '필수 정보가 누락되었습니다.'));
			return;
		}

		// JSON으로 변환하여 저장
		$detail_json = json_encode($detail_data, JSON_UNESCAPED_UNICODE);

		$this->db->where('member_idx', $member_idx);
		$this->db->where('org_id', $org_id);
		$result = $this->db->update('wb_member', array(
			'member_detail' => $detail_json,
			'modi_date' => date('Y-m-d H:i:s')
		));

		if ($result) {
			echo json_encode(array('success' => true, 'message' => '상세정보가 저장되었습니다.'));
		} else {
			echo json_encode(array('success' => false, 'message' => '상세정보 저장에 실패했습니다.'));
		}
	}

	/**
	 * 회원 상세정보 가져오기
	 */
	public function get_member_detail() {
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$org_id = $this->input->post('org_id');
		$member_idx = $this->input->post('member_idx');

		if (!$org_id || !$member_idx) {
			echo json_encode(array('success' => false, 'message' => '필수 정보가 누락되었습니다.'));
			return;
		}

		$detail_data = $this->get_member_detail_data($org_id, $member_idx);

		echo json_encode(array(
			'success' => true,
			'data' => $detail_data
		));
	}





	public function save_memo()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$member_idx = $this->input->post('member_idx');
		$memo_type = $this->input->post('memo_type');
		$memo_content = $this->input->post('memo_content');
		$att_date = $this->input->post('att_date');
		$org_id = $this->input->post('org_id');

		if (!$member_idx || !$memo_content) {
			echo json_encode(array('success' => false, 'message' => '필수 정보가 누락되었습니다.'));
			return;
		}

		if (!$this->check_org_access($org_id)) {
			echo json_encode(array('success' => false, 'message' => '권한이 없습니다.'));
			return;
		}

		$member = $this->Member_model->get_member_by_idx($member_idx);
		if (!$member || $member['org_id'] != $org_id) {
			echo json_encode(array('success' => false, 'message' => '유효하지 않은 회원입니다.'));
			return;
		}

		$data = array(
			'memo_type' => $memo_type ?: '',
			'memo_content' => $memo_content,
			'att_date' => $att_date ?: null,
			'regi_date' => date('Y-m-d H:i:s'),
			'user_id' => $this->session->userdata('user_email'),
			'member_idx' => $member_idx,
			'del_yn' => 'N'
		);

		$this->load->model('Memo_model');
		$result = $this->Memo_model->save_memo($data);

		if ($result) {
			echo json_encode(array('success' => true, 'message' => '메모가 저장되었습니다.'));
		} else {
			echo json_encode(array('success' => false, 'message' => '메모 저장에 실패했습니다.'));
		}
	}

	/**
	 * 파일 위치: application/controllers/Member.php
	 * 역할: 회원 메모 목록 조회 기능
	 */
	public function get_memo_list()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$member_idx = $this->input->post('member_idx');
		$page = $this->input->post('page') ?: 1;
		$limit = $this->input->post('limit') ?: 10;
		$org_id = $this->input->post('org_id');

		// 필수 데이터 확인
		if (!$member_idx || !$org_id) {
			echo json_encode(array('success' => false, 'message' => '필수 정보가 누락되었습니다.'));
			return;
		}

		// 권한 확인
		if (!$this->check_org_access($org_id)) {
			echo json_encode(array('success' => false, 'message' => '권한이 없습니다.'));
			return;
		}

		// 회원이 해당 조직에 속하는지 확인
		$member = $this->Member_model->get_member_by_idx($member_idx);
		if (!$member || $member['org_id'] != $org_id) {
			echo json_encode(array('success' => false, 'message' => '유효하지 않은 회원입니다.'));
			return;
		}

		$offset = ($page - 1) * $limit;

		$this->load->model('Memo_model');
		$memo_list = $this->Memo_model->get_memo_list($member_idx, $limit, $offset);

		if ($memo_list !== false) {
			echo json_encode(array('success' => true, 'data' => $memo_list));
		} else {
			echo json_encode(array('success' => false, 'message' => '메모 목록을 불러오는데 실패했습니다.'));
		}
	}

	/**
	 * 파일 위치: application/controllers/Member.php
	 * 역할: 회원 메모 삭제 기능
	 */
	public function delete_memo()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$idx = $this->input->post('idx');
		$org_id = $this->input->post('org_id');

		// 필수 데이터 확인
		if (!$idx || !$org_id) {
			echo json_encode(array('success' => false, 'message' => '필수 정보가 누락되었습니다.'));
			return;
		}

		// 권한 확인
		if (!$this->check_org_access($org_id)) {
			echo json_encode(array('success' => false, 'message' => '권한이 없습니다.'));
			return;
		}

		$this->load->model('Memo_model');
		$result = $this->Memo_model->delete_memo($idx);

		if ($result) {
			echo json_encode(array('success' => true, 'message' => '메모가 삭제되었습니다.'));
		} else {
			echo json_encode(array('success' => false, 'message' => '메모 삭제에 실패했습니다.'));
		}
	}

	/**
	 * 파일 위치: application/controllers/Member.php
	 * 역할: 회원 메모 수정 기능
	 */
	public function update_memo()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$idx = $this->input->post('idx');
		$memo_type = $this->input->post('memo_type');
		$memo_content = $this->input->post('memo_content');
		$att_date = $this->input->post('att_date');
		$org_id = $this->input->post('org_id');

		if (!$idx || !$memo_content) {
			echo json_encode(array('success' => false, 'message' => '필수 정보가 누락되었습니다.'));
			return;
		}

		if (!$this->check_org_access($org_id)) {
			echo json_encode(array('success' => false, 'message' => '권한이 없습니다.'));
			return;
		}

		$update_data = array(
			'memo_type' => $memo_type ?: '',
			'memo_content' => $memo_content,
			'att_date' => $att_date ?: null,
			'modi_date' => date('Y-m-d H:i:s')
		);

		$this->load->model('Memo_model');
		$result = $this->Memo_model->update_memo($idx, $update_data);

		if ($result) {
			echo json_encode(array('success' => true, 'message' => '메모가 수정되었습니다.'));
		} else {
			echo json_encode(array('success' => false, 'message' => '메모 수정에 실패했습니다.'));
		}
	}


	/**
	 * 선택된 회원들의 QR 코드 인쇄 페이지
	 */
	public function print_selected_qr()
	{
		$member_indices = $this->input->get('members');
		$start_position = $this->input->get('start_position', TRUE);

		if (!$member_indices) {
			show_404();
			return;
		}

		// 문자열을 배열로 변환
		$member_indices = explode(',', $member_indices);
		$member_indices = array_map('intval', $member_indices);

		// 시작 위치 기본값 설정 (1~70 범위)
		if (!$start_position || $start_position < 1 || $start_position > 70) {
			$start_position = 1;
		}

		$data = array(
			'member_indices' => $member_indices,
			'start_position' => $start_position
		);

		$this->load->view('selected_qr_print_view', $data);
	}

	/**
	 * 선택된 회원들의 정보 조회 API
	 */
	public function get_selected_members()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$member_indices = $this->input->post('member_indices');

		if (!$member_indices || !is_array($member_indices)) {
			echo json_encode(array('success' => false, 'message' => '회원 정보가 필요합니다.'));
			return;
		}

		// 권한 확인
		$user_id = $this->session->userdata('user_id');
		$master_yn = $this->session->userdata('master_yn');

		// 선택된 회원들의 정보 조회
		$members = array();
		foreach ($member_indices as $member_idx) {
			$member = $this->Member_model->get_member($member_idx);

			if (!$member) {
				continue;
			}

			// 권한 확인 - 사용자가 해당 회원을 관리할 수 있는지 확인
			if ($master_yn !== 'Y') {
				$user_level = $this->User_model->get_org_user_level($user_id, $member['org_id']);

				if ($user_level < 10) {
					// 관리 가능한 그룹 확인
					$this->load->model('User_management_model');
					$accessible_areas = $this->User_management_model->get_user_managed_areas_with_children($user_id, $member['org_id']);

					if (!in_array($member['area_idx'], $accessible_areas)) {
						continue; // 권한이 없는 회원은 제외
					}
				}
			}

			$members[] = $member;
		}

		echo json_encode(array('success' => true, 'members' => $members));
	}

	/**
	 * 조직의 직위/직분 및 직책 정보 가져오기
	 */
	public function get_org_positions_duties()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$org_id = $this->input->post('org_id');
		if (!$org_id) {
			echo json_encode(array('success' => false, 'message' => '조직 정보가 필요합니다.'));
			return;
		}

		// 권한 확인
		if (!$this->check_org_access($org_id)) {
			echo json_encode(array('success' => false, 'message' => '권한이 없습니다.'));
			return;
		}

		// 조직 상세 정보 가져오기
		$org_detail = $this->Org_model->get_org_detail_by_id($org_id);

		$positions = array();
		$duties = array();

		if ($org_detail) {
			// position_name JSON 파싱
			if (!empty($org_detail['position_name'])) {
				try {
					$decoded_positions = json_decode($org_detail['position_name'], true);
					if (is_array($decoded_positions)) {
						$positions = $decoded_positions;
					}
				} catch (Exception $e) {
					log_message('error', '직위/직분 JSON 파싱 오류: ' . $e->getMessage());
				}
			}

			// duty_name JSON 파싱
			if (!empty($org_detail['duty_name'])) {
				try {
					$decoded_duties = json_decode($org_detail['duty_name'], true);
					if (is_array($decoded_duties)) {
						$duties = $decoded_duties;
					}
				} catch (Exception $e) {
					log_message('error', '직책 JSON 파싱 오류: ' . $e->getMessage());
				}
			}
		}

		echo json_encode(array(
			'success' => true,
			'data' => array(
				'positions' => $positions,
				'duties' => $duties
			)
		));
	}



	/**
	 * 조직의 타임라인 호칭 목록 가져오기
	 */
	public function get_timeline_types()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$org_id = $this->input->post('org_id');
		if (!$org_id) {
			echo json_encode(array('success' => false, 'message' => '조직 정보가 필요합니다.'));
			return;
		}

		// 권한 확인
		if (!$this->check_org_access($org_id)) {
			echo json_encode(array('success' => false, 'message' => '권한이 없습니다.'));
			return;
		}

		// 조직의 타임라인 호칭 정보 가져오기
		$org_detail = $this->Org_model->get_org_detail_by_id($org_id);
		$timeline_types = array();

		if ($org_detail && !empty($org_detail['timeline_name'])) {
			try {
				$decoded_timeline = json_decode($org_detail['timeline_name'], true);
				if (is_array($decoded_timeline)) {
					$timeline_types = $decoded_timeline;
				}
			} catch (Exception $e) {
				log_message('error', '타임라인 호칭 JSON 파싱 오류: ' . $e->getMessage());
			}
		}

		echo json_encode(array(
			'success' => true,
			'data' => $timeline_types
		));
	}

	/**
	 * 회원 타임라인 목록 조회
	 */
	public function get_timeline_list()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$member_idx = $this->input->post('member_idx');
		$page = $this->input->post('page') ?: 1;
		$limit = $this->input->post('limit') ?: 20;
		$org_id = $this->input->post('org_id');

		// 필수 데이터 확인
		if (!$member_idx || !$org_id) {
			echo json_encode(array('success' => false, 'message' => '필수 정보가 누락되었습니다.'));
			return;
		}

		// 권한 확인
		if (!$this->check_org_access($org_id)) {
			echo json_encode(array('success' => false, 'message' => '권한이 없습니다.'));
			return;
		}

		// 회원이 해당 조직에 속하는지 확인
		$member = $this->Member_model->get_member_by_idx($member_idx);
		if (!$member || $member['org_id'] != $org_id) {
			echo json_encode(array('success' => false, 'message' => '유효하지 않은 회원입니다.'));
			return;
		}

		$offset = ($page - 1) * $limit;

		$this->load->model('Timeline_model');
		$timeline_list = $this->Timeline_model->get_member_timeline($member_idx, $limit, $offset);

		if ($timeline_list !== false) {
			echo json_encode(array('success' => true, 'data' => $timeline_list));
		} else {
			echo json_encode(array('success' => false, 'message' => '타임라인 목록을 불러오는데 실패했습니다.'));
		}
	}

	/**
	 * 타임라인 항목 저장
	 */
	public function save_timeline()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$member_idx = $this->input->post('member_idx');
		$timeline_type = $this->input->post('timeline_type');
		$timeline_date = $this->input->post('timeline_date');
		$timeline_content = $this->input->post('timeline_content');
		$org_id = $this->input->post('org_id');

		// 필수 데이터 확인
		if (!$member_idx || !$timeline_type || !$timeline_date || !$org_id) {
			echo json_encode(array('success' => false, 'message' => '필수 정보가 누락되었습니다.'));
			return;
		}

		// 권한 확인
		if (!$this->check_org_access($org_id)) {
			echo json_encode(array('success' => false, 'message' => '권한이 없습니다.'));
			return;
		}

		// 회원이 해당 조직에 속하는지 확인
		$member = $this->Member_model->get_member_by_idx($member_idx);
		if (!$member || $member['org_id'] != $org_id) {
			echo json_encode(array('success' => false, 'message' => '유효하지 않은 회원입니다.'));
			return;
		}

		$data = array(
			'member_idx' => $member_idx,
			'timeline_type' => $timeline_type,
			'timeline_date' => $timeline_date,
			'timeline_content' => $timeline_content ?: '',
			'regi_date' => date('Y-m-d H:i:s'),
			'user_id' => $this->session->userdata('user_email')
		);

		$this->load->model('Timeline_model');
		$result = $this->Timeline_model->save_timeline($data);

		if ($result) {
			echo json_encode(array('success' => true, 'message' => '타임라인이 저장되었습니다.'));
		} else {
			echo json_encode(array('success' => false, 'message' => '타임라인 저장에 실패했습니다.'));
		}
	}

	/**
	 * 타임라인 항목 수정
	 */
	public function update_timeline()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$idx = $this->input->post('idx');
		$timeline_type = $this->input->post('timeline_type');
		$timeline_date = $this->input->post('timeline_date');
		$timeline_content = $this->input->post('timeline_content');
		$org_id = $this->input->post('org_id');

		// 필수 데이터 확인
		if (!$idx || !$timeline_type || !$timeline_date || !$org_id) {
			echo json_encode(array('success' => false, 'message' => '필수 정보가 누락되었습니다.'));
			return;
		}

		// 권한 확인
		if (!$this->check_org_access($org_id)) {
			echo json_encode(array('success' => false, 'message' => '권한이 없습니다.'));
			return;
		}

		$update_data = array(
			'timeline_type' => $timeline_type,
			'timeline_date' => $timeline_date,
			'timeline_content' => $timeline_content ?: '',
			'modi_date' => date('Y-m-d H:i:s')
		);

		$this->load->model('Timeline_model');
		$result = $this->Timeline_model->update_timeline($idx, $update_data);

		if ($result) {
			echo json_encode(array('success' => true, 'message' => '타임라인이 수정되었습니다.'));
		} else {
			echo json_encode(array('success' => false, 'message' => '타임라인 수정에 실패했습니다.'));
		}
	}

	/**
	 * 타임라인 항목 삭제
	 */
	public function delete_timeline()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$idx = $this->input->post('idx');
		$org_id = $this->input->post('org_id');

		// 필수 데이터 확인
		if (!$idx || !$org_id) {
			echo json_encode(array('success' => false, 'message' => '필수 정보가 누락되었습니다.'));
			return;
		}

		// 권한 확인
		if (!$this->check_org_access($org_id)) {
			echo json_encode(array('success' => false, 'message' => '권한이 없습니다.'));
			return;
		}

		$this->load->model('Timeline_model');
		$result = $this->Timeline_model->delete_timeline($idx);

		if ($result) {
			echo json_encode(array('success' => true, 'message' => '타임라인이 삭제되었습니다.'));
		} else {
			echo json_encode(array('success' => false, 'message' => '타임라인 삭제에 실패했습니다.'));
		}
	}


	/**
	 * 파송교회 목록 조회
	 * POST /member/get_transfer_org_list
	 */
	public function get_transfer_org_list()
	{
		$this->output->set_content_type('application/json');

		try {
			$member_idx = $this->input->post('member_idx');
			$org_id = $this->input->post('org_id');

			if (empty($member_idx) || empty($org_id)) {
				echo json_encode([
					'success' => false,
					'message' => '필수 정보가 누락되었습니다.'
				]);
				return;
			}

			// Transfer_org_model 로드
			$this->load->model('Transfer_org_model');
			$transfer_orgs = $this->Transfer_org_model->get_member_transfer_orgs($member_idx, $org_id);

			echo json_encode([
				'success' => true,
				'data' => $transfer_orgs
			]);

		} catch (Exception $e) {
			log_message('error', '파송교회 목록 조회 오류: ' . $e->getMessage());
			echo json_encode([
				'success' => false,
				'message' => '파송교회 목록 조회 중 오류가 발생했습니다.'
			]);
		}
	}


	/**
	 * 파송교회 추가
	 * POST /member/save_transfer_org
	 */
	public function save_transfer_org()
	{
		$this->output->set_content_type('application/json');

		try {
			$member_idx = $this->input->post('member_idx');
			$org_id = $this->input->post('org_id');

			if (empty($member_idx) || empty($org_id)) {
				echo json_encode([
					'success' => false,
					'message' => '필수 정보가 누락되었습니다.'
				]);
				return;
			}

			$data = [
				'member_idx' => $member_idx,
				'org_id' => $org_id,
				'transfer_region' => $this->input->post('transfer_region'),
				'transfer_name' => $this->input->post('transfer_name'),
				'pastor_name' => $this->input->post('pastor_name'),
				'contact_person' => $this->input->post('contact_person'),
				'contact_phone' => $this->input->post('contact_phone'),
				'contact_email' => $this->input->post('contact_email'),
				'transfer_description' => $this->input->post('transfer_description'),
				'org_tag' => $this->input->post('org_tag'),
				'transfer_org_id' => $this->input->post('transfer_org_id'),
				'regi_date' => date('Y-m-d H:i:s'),
				'modi_date' => date('Y-m-d H:i:s'),
				'del_yn' => 'N'
			];

			// Transfer_org_model 로드
			$this->load->model('Transfer_org_model');
			$result = $this->Transfer_org_model->insert_transfer_org($data);

			if ($result) {
				echo json_encode([
					'success' => true,
					'message' => '파송교회가 저장되었습니다.',
					'transfer_org_id' => $result
				]);
			} else {
				echo json_encode([
					'success' => false,
					'message' => '파송교회 저장에 실패했습니다.'
				]);
			}

		} catch (Exception $e) {
			log_message('error', '파송교회 저장 오류: ' . $e->getMessage());
			echo json_encode([
				'success' => false,
				'message' => '파송교회 저장 중 오류가 발생했습니다.'
			]);
		}
	}




	/**
	 * 파송교회 수정
	 * POST /member/update_transfer_org
	 */
	public function update_transfer_org()
	{
		$this->output->set_content_type('application/json');

		try {
			$idx = $this->input->post('idx');
			$org_id = $this->input->post('org_id');
			$member_idx = $this->input->post('member_idx');

			if (empty($idx) || empty($org_id) || empty($member_idx)) {
				echo json_encode([
					'success' => false,
					'message' => '필수 정보가 누락되었습니다.'
				]);
				return;
			}

			$data = [
				'member_idx' => $member_idx,
				'transfer_region' => $this->input->post('transfer_region'),
				'transfer_name' => $this->input->post('transfer_name'),
				'pastor_name' => $this->input->post('pastor_name'),
				'contact_person' => $this->input->post('contact_person'),
				'contact_phone' => $this->input->post('contact_phone'),
				'contact_email' => $this->input->post('contact_email'),
				'transfer_description' => $this->input->post('transfer_description'),
				'org_tag' => $this->input->post('org_tag'),
				'modi_date' => date('Y-m-d H:i:s')
			];

			// Transfer_org_model 로드
			$this->load->model('Transfer_org_model');
			$result = $this->Transfer_org_model->update_transfer_org($idx, $org_id, $data);

			if ($result) {
				echo json_encode([
					'success' => true,
					'message' => '파송교회가 수정되었습니다.'
				]);
			} else {
				echo json_encode([
					'success' => false,
					'message' => '파송교회 수정에 실패했습니다.'
				]);
			}

		} catch (Exception $e) {
			log_message('error', '파송교회 수정 오류: ' . $e->getMessage());
			echo json_encode([
				'success' => false,
				'message' => '파송교회 수정 중 오류가 발생했습니다.'
			]);
		}
	}


	/**
	 * 파송교회 삭제
	 * POST /member/delete_transfer_org
	 */
	public function delete_transfer_org()
	{
		$this->output->set_content_type('application/json');

		try {
			$transfer_org_id = $this->input->post('idx');
			$org_id = $this->input->post('org_id');
			$member_idx = $this->input->post('member_idx');

			if (empty($transfer_org_id) || empty($org_id) || empty($member_idx)) {
				echo json_encode([
					'success' => false,
					'message' => '필수 정보가 누락되었습니다.'
				]);
				return;
			}

			// Transfer_org_model 로드
			$this->load->model('Transfer_org_model');
			$result = $this->Transfer_org_model->delete_transfer_org($transfer_org_id, $org_id, $member_idx);

			if ($result) {
				echo json_encode([
					'success' => true,
					'message' => '파송교회가 삭제되었습니다.'
				]);
			} else {
				echo json_encode([
					'success' => false,
					'message' => '파송교회 삭제에 실패했습니다.'
				]);
			}

		} catch (Exception $e) {
			log_message('error', '파송교회 삭제 오류: ' . $e->getMessage());
			echo json_encode([
				'success' => false,
				'message' => '파송교회 삭제 중 오류가 발생했습니다.'
			]);
		}
	}

	/**
	 * 결연교회 목록 조회
	 * POST /member/get_available_churches
	 */
	public function get_available_churches()
	{
		$this->output->set_content_type('application/json');

		try {
			$org_id = $this->input->post('org_id');

			if (empty($org_id)) {
				echo json_encode([
					'success' => false,
				   'message' => '조직 정보가 누락되었습니다.'
            ]);
            return;
        }

			// Transfer_org_model 로드
			$this->load->model('Transfer_org_model');
			$churches = $this->Transfer_org_model->get_available_churches();

			echo json_encode([
				'success' => true,
				'data' => $churches
			]);

		} catch (Exception $e) {
			log_message('error', '결연교회 목록 조회 오류: ' . $e->getMessage());
			echo json_encode([
				'success' => false,
				'message' => '결연교회 목록 조회 중 오류가 발생했습니다.'
			]);
		}
	}

	/**
	 * 결연교회 자동매칭
	 * POST /member/auto_match_church
	 */
	public function auto_match_church()
	{
		$this->output->set_content_type('application/json');

		try {
			$member_idx = $this->input->post('member_idx');
			$org_id = $this->input->post('org_id');

			if (empty($member_idx) || empty($org_id)) {
				echo json_encode([
					'success' => false,
					'message' => '필수 정보가 누락되었습니다.'
				]);
				return;
			}

			// Transfer_org_model 로드
			$this->load->model('Transfer_org_model');
			$result = $this->Transfer_org_model->auto_match_transfer_church($member_idx, $org_id);

			if ($result['success']) {
				echo json_encode([
					'success' => true,
					'message' => $result['message'],
					'matched_count' => $result['matched_count']
				]);
			} else {
				echo json_encode([
					'success' => false,
					'message' => $result['message']
				]);
			}

		} catch (Exception $e) {
			log_message('error', '결연교회 자동매칭 오류: ' . $e->getMessage());
			echo json_encode([
				'success' => false,
				'message' => '결연교회 자동매칭 중 오류가 발생했습니다.'
			]);
		}
	}

	/**
	 * 결연교회 검색
	 */
	public function search_match_church()
	{
		$this->output->set_content_type('application/json');

		try {
			$org_id = $this->input->post('org_id');
			$search_type = $this->input->post('search_type');
			$keyword = $this->input->post('keyword');

			if (empty($org_id) || empty($search_type) || empty($keyword)) {
				echo json_encode([
					'success' => false,
					'message' => '필수 파라미터가 누락되었습니다.'
				]);
				return;
			}

			$this->load->model('Transfer_org_model');
			$results = $this->Transfer_org_model->search_churches($org_id, $search_type, $keyword);

			echo json_encode([
				'success' => true,
				'data' => $results,
				'message' => count($results) . '개의 교회를 찾았습니다.'
			]);

		} catch (Exception $e) {
			log_message('error', '교회 검색 오류: ' . $e->getMessage());
			echo json_encode([
				'success' => false,
				'message' => '검색 중 오류가 발생했습니다.'
			]);
		}
	}

	/**
	 * 동일지역 자동매칭
	 */
	public function auto_match_by_region()
	{
		$this->output->set_content_type('application/json');

		try {
			$member_idx = $this->input->post('member_idx');
			$org_id = $this->input->post('org_id');

			if (empty($member_idx) || empty($org_id)) {
				echo json_encode([
					'success' => false,
					'message' => '필수 파라미터가 누락되었습니다.'
				]);
				return;
			}

			$this->load->model('Transfer_org_model');
			$results = $this->Transfer_org_model->search_churches_by_member_address($member_idx, $org_id);

			if (empty($results)) {
				echo json_encode([
					'success' => false,
					'message' => '동일 지역에서 교회를 찾을 수 없습니다.',
					'data' => []
				]);
				return;
			}

			echo json_encode([
				'success' => true,
				'data' => $results,
				'message' => count($results) . '개의 교회를 찾았습니다.'
			]);

		} catch (Exception $e) {
			log_message('error', '동일지역 매칭 오류: ' . $e->getMessage());
			echo json_encode([
				'success' => false,
				'message' => '자동매칭 중 오류가 발생했습니다.',
				'data' => []
			]);
		}
	}

	/**
	 * 선택된 교회 일괄 저장
	 */
	public function save_matched_churches()
	{
		$this->output->set_content_type('application/json');

		try {
			$member_idx = $this->input->post('member_idx');
			$org_id = $this->input->post('org_id');
			$churches_json = $this->input->post('churches');

			if (empty($member_idx) || empty($org_id) || empty($churches_json)) {
				echo json_encode([
					'success' => false,
					'message' => '필수 파라미터가 누락되었습니다.'
				]);
				return;
			}

			$churches = json_decode($churches_json, true);

			if (empty($churches) || !is_array($churches)) {
				echo json_encode([
					'success' => false,
					'message' => '추가할 교회 정보가 없습니다.'
				]);
				return;
			}

			$this->load->model('Transfer_org_model');
			$result = $this->Transfer_org_model->insert_matched_churches($member_idx, $org_id, $churches);

			if (!$result['success']) {
				echo json_encode([
					'success' => false,
					'message' => '교회 저장 중 오류가 발생했습니다.'
				]);
				return;
			}

			$message = $result['success_count'] . '개의 교회가 추가되었습니다.';
			if ($result['skip_count'] > 0) {
				$message .= ' (' . $result['skip_count'] . '개는 이미 등록된 교회입니다.)';
			}

			echo json_encode([
				'success' => true,
				'message' => $message
			]);

		} catch (Exception $e) {
			log_message('error', '교회 저장 오류: ' . $e->getMessage());
			echo json_encode([
				'success' => false,
				'message' => '교회 저장 중 오류가 발생했습니다.'
			]);
		}
	}




	/**
	 * 회원에게 결연교회 추천 링크 전송
	 */
	public function send_offer_link()
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

		$this->load->model('Member_model');

		// 회원 정보 조회
		$member = $this->Member_model->get_member_by_idx($member_idx);

		if (!$member || $member['org_id'] != $org_id) {
			echo json_encode(array('success' => false, 'message' => '회원 정보를 찾을 수 없습니다.'));
			return;
		}

		// 패스코드가 없으면 생성
		if (empty($member['member_passcode'])) {
			$passcode = $this->Member_model->generate_member_passcode();

			$this->db->where('member_idx', $member_idx);
			$this->db->update('wb_member', array(
				'member_passcode' => $passcode,
				'modi_date' => date('Y-m-d H:i:s')
			));

			$member['member_passcode'] = $passcode;
		}

		// Offer 링크 생성
		$offer_url = base_url('offer/' . $org_id . '/' . $member_idx . '/' . $member['member_passcode']);

		echo json_encode(array(
			'success' => true,
			'offer_url' => $offer_url,
			'member_name' => $member['member_name'],
			'member_phone' => $member['member_phone']
		));
	}



	/**
	 * 회원 패스코드 갱신
	 */
	public function refresh_member_passcode()
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

		$this->load->model('Member_model');

		// 회원 정보 조회
		$member = $this->Member_model->get_member_by_idx($member_idx);

		if (!$member || $member['org_id'] != $org_id) {
			echo json_encode(array('success' => false, 'message' => '회원 정보를 찾을 수 없습니다.'));
			return;
		}

		// 새 패스코드 생성
		$new_passcode = $this->Member_model->generate_member_passcode();

		$this->db->where('member_idx', $member_idx);
		$this->db->update('wb_member', array(
			'member_passcode' => $new_passcode,
			'modi_date' => date('Y-m-d H:i:s')
		));

		// 새 Offer 링크 생성
		$offer_url = base_url('offer/' . $org_id . '/' . $member_idx . '/' . $new_passcode);

		echo json_encode(array(
			'success' => true,
			'offer_url' => $offer_url,
			'message' => '패스코드가 갱신되었습니다.'
		));
	}


	/**
	 * 회원의 파송교회 목록 조회 (선택 상태 포함)
	 */
	public function get_member_transfer_orgs()
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

		$this->load->model('Transfer_org_model');

		$transfer_orgs = $this->Transfer_org_model->get_member_transfer_orgs($member_idx, $org_id);

		echo json_encode(array(
			'success' => true,
			'data' => $transfer_orgs
		));
	}

	/**
	 * 파송교회 목록 조회 (선택 상태 포함)
	 */
	public function get_transfer_orgs()
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

		$this->load->model('Transfer_org_model');

		// 선택 상태를 포함한 파송교회 목록 조회
		$transfer_orgs = $this->Transfer_org_model->get_member_transfer_orgs_with_selection($member_idx, $org_id);

		echo json_encode(array(
			'success' => true,
			'data' => $transfer_orgs
		));
	}

	/**
	 * 회원 정보 조회 (간단 버전)
	 */
	public function get_member_info()
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

		$this->load->model('Member_model');

		// 회원 정보 조회
		$member = $this->Member_model->get_member_by_idx($member_idx);

		if (!$member || $member['org_id'] != $org_id) {
			echo json_encode(array('success' => false, 'message' => '회원 정보를 찾을 수 없습니다.'));
			return;
		}

		echo json_encode(array(
			'success' => true,
			'member' => array(
				'member_idx' => $member['member_idx'],
				'member_name' => $member['member_name'],
				'member_phone' => $member['member_phone']
			)
		));
	}

	/**
	 * 선택된 파송교회 정보 조회
	 */
	public function get_selected_transfer_org()
	{
		$this->output->set_content_type('application/json');

		try {
			$member_idx = $this->input->post('member_idx');
			$org_id = $this->input->post('org_id');

			if (empty($member_idx) || empty($org_id)) {
				echo json_encode([
					'success' => false,
					'message' => '필수 정보가 누락되었습니다.'
				]);
				return;
			}

			// Transfer_org_model 로드
			$this->load->model('Transfer_org_model');
			$church = $this->Transfer_org_model->get_selected_transfer_org($member_idx, $org_id);

			if ($church) {
				echo json_encode([
					'success' => true,
					'church' => $church
				]);
			} else {
				echo json_encode([
					'success' => false,
					'message' => '선택된 파송교회가 없습니다.'
				]);
			}

		} catch (Exception $e) {
			log_message('error', '선택된 파송교회 조회 오류: ' . $e->getMessage());
			echo json_encode([
				'success' => false,
				'message' => '파송교회 조회 중 오류가 발생했습니다.'
			]);
		}
	}



	/**
	 * 파송교회에 회원정보 이메일 전송
	 */
	public function send_member_info_email()
	{
		$this->output->set_content_type('application/json');

		try {
			$member_idx = $this->input->post('member_idx');
			$org_id = $this->input->post('org_id');
			$to_email = $this->input->post('to_email');
			$message = $this->input->post('message');

			if (empty($member_idx) || empty($org_id) || empty($to_email) || empty($message)) {
				echo json_encode([
					'success' => false,
					'message' => '필수 정보가 누락되었습니다.'
				]);
				return;
			}

			// 이메일 형식 검증
			if (!filter_var($to_email, FILTER_VALIDATE_EMAIL)) {
				echo json_encode([
					'success' => false,
					'message' => '올바른 이메일 주소가 아닙니다.'
				]);
				return;
			}

			// 회원 정보 조회
			$this->load->model('Member_model');
			$member = $this->Member_model->get_member_by_idx($member_idx);

			if (!$member || $member['org_id'] != $org_id) {
				echo json_encode([
					'success' => false,
					'message' => '회원 정보를 찾을 수 없습니다.'
				]);
				return;
			}

			// 조직 정보 조회
			$this->load->model('Org_model');
			$org_info = $this->Org_model->get_org_detail_by_id($org_id);

			if (!$org_info) {
				echo json_encode([
					'success' => false,
					'message' => '조직 정보를 찾을 수 없습니다.'
				]);
				return;
			}

			// 이메일 전송 라이브러리 로드
			$this->load->library('email');

			// SMTP 설정 (User_management 컨트롤러와 동일한 설정 사용)
			$config = [
				'protocol' => 'smtp',
				'smtp_host' => 'smtp.gmail.com',
				'smtp_user' => 'hello@webhows.com',
				'smtp_pass' => 'hzeh kaik dyuh utty',
				'smtp_port' => 587,
				'smtp_crypto' => 'tls',
				'charset' => 'utf-8',
				'wordwrap' => TRUE,
				'mailtype' => 'text',
				'newline' => "\r\n",
				'crlf' => "\r\n"
			];

			$this->email->initialize($config);

			// 이메일 설정
			$this->email->from('hello@webhows.com', '군선교연합회');
			$this->email->to($to_email);
			$this->email->subject('[군선교연합회] 성도 정보 전달 - ' . $member['member_name']);
			$this->email->message($message);

			// 이메일 전송 시도
			if ($this->email->send()) {
				log_message('info', "이메일 전송 성공 - To: {$to_email}, Member: {$member['member_name']}");
				echo json_encode([
					'success' => true,
					'message' => '이메일이 전송되었습니다.'
				]);
			} else {
				// 상세한 에러 로그 기록
				$error_msg = $this->email->print_debugger();
				log_message('error', '이메일 전송 실패: ' . $error_msg);

				echo json_encode([
					'success' => false,
					'message' => '이메일 전송에 실패했습니다.',
					'debug' => $error_msg // 개발 환경에서만 사용
				]);
			}

		} catch (Exception $e) {
			log_message('error', '이메일 전송 오류: ' . $e->getMessage());
			echo json_encode([
				'success' => false,
				'message' => '이메일 전송 중 오류가 발생했습니다: ' . $e->getMessage()
			]);
		}
	}

	/**
	 * 회원 정보 조회 (패스코드 포함)
	 */
	public function get_member_info_with_passcode()
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

		$this->load->model('Member_model');

		// 회원 정보 조회
		$member = $this->Member_model->get_member_by_idx($member_idx);

		if (!$member || $member['org_id'] != $org_id) {
			echo json_encode(array('success' => false, 'message' => '회원 정보를 찾을 수 없습니다.'));
			return;
		}

		// 패스코드가 없으면 생성
		if (empty($member['member_passcode'])) {
			$passcode = $this->Member_model->generate_member_passcode();

			$this->db->where('member_idx', $member_idx);
			$this->db->update('wb_member', array(
				'member_passcode' => $passcode,
				'modi_date' => date('Y-m-d H:i:s')
			));

			$member['member_passcode'] = $passcode;
		}

		echo json_encode(array(
			'success' => true,
			'member' => array(
				'member_idx' => $member['member_idx'],
				'member_name' => $member['member_name'],
				'member_phone' => $member['member_phone'],
				'member_passcode' => $member['member_passcode']
			)
		));
	}

}
