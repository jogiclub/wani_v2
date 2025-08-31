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
	 * 파일 위치: application/controllers/Member.php - get_members() 함수
	 * 역할: 사용자 권한에 따라 관리 가능한 그룹의 회원만 조회
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

		// 사용자의 권한 레벨과 관리 가능한 그룹 정보 가져오기
		$user_level = $this->User_model->get_org_user_level($user_id, $org_id);
		$accessible_areas = array();

		// 최고관리자(레벨 10)이거나 마스터가 아닌 경우 관리 그룹 권한 확인
		if ($user_level < 10 && $master_yn !== 'Y') {
			$this->load->model('User_management_model');
			$accessible_areas = $this->User_management_model->get_user_managed_areas_with_children($user_id, $org_id);

			// 관리 권한이 없는 경우
			if (empty($accessible_areas)) {
				echo json_encode(array('success' => false, 'message' => '회원을 조회할 권한이 없습니다.'));
				return;
			}
		}

		// 타입에 따라 회원 데이터 가져오기
		if ($type === 'unassigned') {
			// 미분류 그룹은 최고관리자만 접근 가능
			if ($user_level < 10 && $master_yn !== 'Y') {
				echo json_encode(array('success' => false, 'message' => '미분류 그룹을 조회할 권한이 없습니다.'));
				return;
			}
			$members = $this->Member_model->get_unassigned_members($org_id);
		} else if ($type === 'area' && $area_idx) {
			// 특정 소그룹 접근 권한 확인 (부모 그룹 권한 포함)
			if ($user_level < 10 && $master_yn !== 'Y') {
				if (!in_array($area_idx, $accessible_areas)) {
					echo json_encode(array('success' => false, 'message' => '해당 그룹을 조회할 권한이 없습니다.'));
					return;
				}
			}
			$members = $this->Member_model->get_area_members_with_children($org_id, $area_idx);
		} else if ($type === 'org') {
			// 조직 전체 회원 조회
			if ($user_level >= 10 || $master_yn === 'Y') {
				// 최고관리자인 경우 전체 회원
				$members = $this->Member_model->get_org_members($org_id);
			} else {
				// 일반 관리자인 경우 접근 가능한 그룹의 회원들만 (하위 그룹 포함)
				$members = array();
				$managed_areas = $this->User_management_model->get_user_managed_areas($user_id);
				foreach ($managed_areas as $managed_area_idx) {
					$area_members = $this->Member_model->get_area_members_with_children($org_id, $managed_area_idx);
					$members = array_merge($members, $area_members);
				}

				// 중복 제거 (같은 회원이 여러 그룹에 속할 수 있음)
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
			// 기본적으로 권한이 있는 그룹의 회원들만 (하위 그룹 포함)
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

		// ParamQuery 형식으로 데이터 가공
		$formatted_members = array();
		foreach ($members as $member) {
			// 사진 URL 처리
			$photo_url = '/assets/images/photo_no.png'; // 기본 이미지
			if (isset($member['photo']) && $member['photo']) {
				if (strpos($member['photo'], '/uploads/') === false) {
					$photo_url = '/uploads/member_photos/' . $org_id . '/' . $member['photo'];
				} else {
					$photo_url = $member['photo'];
				}
			}

			// 생년월일 처리 (YYYY-MM-DD 또는 YYYYMMDD 형식을 YYYY-MM-DD로 변환)
			$formatted_birth = '';
			if (isset($member['member_birth']) && $member['member_birth']) {
				$birth_date = $member['member_birth'];
				if (strlen($birth_date) == 8) {
					// YYYYMMDD 형식인 경우 YYYY-MM-DD로 변환
					$formatted_birth = substr($birth_date, 0, 4) . '-' . substr($birth_date, 4, 2) . '-' . substr($birth_date, 6, 2);
				} else {
					$formatted_birth = $birth_date;
				}
			}

			$area_name = '미분류';
			if (!empty($member['area_name']) && $member['area_name'] !== '') {
				$area_name = $member['area_name'];
			}

			$formatted_member = array(
				'member_idx' => $member['member_idx'],
				'org_id' => $member['org_id'],
				'area_idx' => isset($member['area_idx']) ? $member['area_idx'] : null,
				'area_name' => $area_name,
				'member_name' => $member['member_name'],
				'member_nick' => $member['member_nick'],
				'photo' => $photo_url,
				'member_phone' => isset($member['member_phone']) ? $member['member_phone'] : '',
				'member_birth' => isset($member['member_birth']) ? $member['member_birth'] : '',
				'member_address' => isset($member['member_address']) ? $member['member_address'] : '',
				'member_address_detail' => isset($member['member_address_detail']) ? $member['member_address_detail'] : '',
				'member_etc' => isset($member['member_etc']) ? $member['member_etc'] : '',
				'grade' => isset($member['grade']) ? $member['grade'] : 0,
				'leader_yn' => isset($member['leader_yn']) ? $member['leader_yn'] : 'N',
				'new_yn' => isset($member['new_yn']) ? $member['new_yn'] : 'N',
				'regi_date' => isset($member['regi_date']) ? $member['regi_date'] : '',
				'modi_date' => isset($member['modi_date']) ? $member['modi_date'] : ''
			);

			$formatted_members[] = $formatted_member;
		}

		echo json_encode(array(
			'success' => true,
			'data' => $formatted_members,
			'total' => count($formatted_members)
		));
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
	 * 파일 위치: application/controllers/Member.php
	 * 역할: 회원 추가 (상세정보 포함)
	 */
	public function add_member()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$org_id = $this->input->post('org_id');
		$area_idx = $this->input->post('area_idx');

		if (!$org_id) {
			echo json_encode(array('success' => false, 'message' => '조직 정보가 필요합니다.'));
			return;
		}

		// 권한 확인
		if (!$this->check_org_access($org_id)) {
			echo json_encode(array('success' => false, 'message' => '권한이 없습니다.'));
			return;
		}

		// 기본 회원명 생성
		$member_name = '새회원_' . date('md_His');

		$data = array(
			'org_id' => $org_id,
			'area_idx' => $area_idx ?: null,
			'member_name' => $member_name,
			'member_nick' => '',
			'member_phone' => '',
			'member_birth' => '',
			'member_address' => '',
			'member_address_detail' => '',
			'member_etc' => '',
			'grade' => 0,
			'leader_yn' => 'N',
			'new_yn' => 'N',
			'del_yn' => 'N',
			'regi_date' => date('Y-m-d H:i:s'),
			'modi_date' => date('Y-m-d H:i:s')
		);

		// 상세정보 처리
		$detail_data = $this->input->post('detail_field');
		if ($detail_data && is_array($detail_data)) {
			// 빈 값들을 제거하고 JSON으로 저장
			$filtered_detail = array();
			foreach ($detail_data as $field_idx => $value) {
				if ($value !== '' && $value !== null) {
					$filtered_detail[$field_idx] = $value;
				}
			}
			if (!empty($filtered_detail)) {
				$data['member_detail'] = json_encode($filtered_detail, JSON_UNESCAPED_UNICODE);
			}
		}

		$result = $this->Member_model->add_member($data);

		if ($result) {
			echo json_encode(array(
				'success' => true,
				'message' => '회원이 추가되었습니다: ' . $member_name,
				'member_name' => $member_name
			));
		} else {
			echo json_encode(array('success' => false, 'message' => '회원 추가에 실패했습니다.'));
		}
	}

	/**
	 * 파일 위치: application/controllers/Member.php
	 * 역할: 회원 정보 수정 (상세정보 포함, 사진 삭제 기능 추가)
	 */
	public function update_member()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$member_idx = $this->input->post('member_idx');
		if (!$member_idx) {
			echo json_encode(array('success' => false, 'message' => '회원 정보가 없습니다.'));
			return;
		}

		$update_data = array(
			'member_name' => $this->input->post('member_name'),
			'member_nick' => $this->input->post('member_nick'),
			'member_phone' => $this->input->post('member_phone'),
			'member_birth' => $this->input->post('member_birth'),
			'member_address' => $this->input->post('member_address'),
			'member_address_detail' => $this->input->post('member_address_detail'),
			'member_etc' => $this->input->post('member_etc'),
			'grade' => $this->input->post('grade') ?: 0,
			'area_idx' => $this->input->post('area_idx'),
			'leader_yn' => $this->input->post('leader_yn') ? 'Y' : 'N',
			'new_yn' => $this->input->post('new_yn') ? 'Y' : 'N',
			'modi_date' => date('Y-m-d H:i:s')
		);

		$org_id = $this->input->post('org_id');

		// 상세정보 처리
		$detail_data = $this->input->post('detail_field');
		if ($detail_data && is_array($detail_data)) {
			// 빈 값들을 제거하고 JSON으로 저장
			$filtered_detail = array();
			foreach ($detail_data as $field_idx => $value) {
				if ($value !== '' && $value !== null) {
					$filtered_detail[$field_idx] = $value;
				}
			}
			$update_data['member_detail'] = json_encode($filtered_detail, JSON_UNESCAPED_UNICODE);
		}

		// 사진 삭제 처리
		$delete_photo = $this->input->post('delete_photo');
		if ($delete_photo === 'Y') {
			// 기존 사진 파일 삭제
			$existing_member = $this->Member_model->get_member_by_idx($member_idx);
			if (!empty($existing_member['photo'])) {
				$existing_file = './uploads/member_photos/' . $org_id . '/' . $existing_member['photo'];
				if (file_exists($existing_file)) {
					unlink($existing_file);
				}
			}
			// 데이터베이스에서 사진 정보 제거
			$update_data['photo'] = null;
		}
		// 새 이미지 업로드 처리
		else if (!empty($_FILES['member_photo']['name'])) {
			$upload_result = $this->handleCroppedImageUpload($member_idx, $org_id);

			if ($upload_result['success']) {
				$update_data['photo'] = $upload_result['file_name'];
			} else {
				echo json_encode(array('success' => false, 'message' => $upload_result['message']));
				return;
			}
		}

		$result = $this->Member_model->update_member($member_idx, $update_data);

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
	public function get_detail_fields()
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

		$this->load->model('Detail_field_model');
		$fields = $this->Detail_field_model->get_detail_fields_by_org($org_id);

		// 활성화된 필드만 필터링
		$active_fields = array();
		foreach ($fields as $field) {
			if ($field['is_active'] === 'Y') {
				// field_settings JSON 디코딩
				$field['field_settings'] = json_decode($field['field_settings'], true);
				$active_fields[] = $field;
			}
		}

		echo json_encode(array(
			'success' => true,
			'data' => $active_fields
		));
	}

	/**
	 * 회원 상세정보 저장
	 */
	public function save_member_detail()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$member_idx = $this->input->post('member_idx');
		$detail_data = $this->input->post('detail_data');
		$org_id = $this->input->post('org_id');

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

		// 상세정보 JSON 형태로 저장
		$member_detail_json = json_encode($detail_data, JSON_UNESCAPED_UNICODE);

		$update_data = array(
			'member_detail' => $member_detail_json,
			'modi_date' => date('Y-m-d H:i:s')
		);

		$result = $this->Member_model->update_member($member_idx, $update_data);

		if ($result) {
			echo json_encode(array('success' => true, 'message' => '회원 상세정보가 저장되었습니다.'));
		} else {
			echo json_encode(array('success' => false, 'message' => '회원 상세정보 저장에 실패했습니다.'));
		}
	}

	/**
	 * 회원 상세정보 가져오기
	 */
	public function get_member_detail()
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

		// 권한 확인
		if (!$this->check_org_access($org_id)) {
			echo json_encode(array('success' => false, 'message' => '권한이 없습니다.'));
			return;
		}

		// 회원 정보 가져오기
		$member = $this->Member_model->get_member_by_idx($member_idx);
		if (!$member || $member['org_id'] != $org_id) {
			echo json_encode(array('success' => false, 'message' => '유효하지 않은 회원입니다.'));
			return;
		}

		// 상세정보 JSON 디코딩
		$member_detail = array();
		if (!empty($member['member_detail'])) {
			$member_detail = json_decode($member['member_detail'], true);
			if (!$member_detail) {
				$member_detail = array(); // JSON 파싱 실패시 빈 배열
			}
		}

		echo json_encode(array(
			'success' => true,
			'data' => $member_detail
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
		$org_id = $this->input->post('org_id');

		// 필수 데이터 확인
		if (!$member_idx || !$memo_content) {
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
			'memo_type' => $memo_type ?: 1,
			'memo_content' => $memo_content,
			'regi_date' => date('Y-m-d H:i:s'),
			'user_id' => $this->session->userdata('user_email'),
			'member_idx' => $member_idx
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
		$memo_content = $this->input->post('memo_content');
		$org_id = $this->input->post('org_id');

		// 필수 데이터 확인
		if (!$idx || !$memo_content || !$org_id) {
			echo json_encode(array('success' => false, 'message' => '필수 정보가 누락되었습니다.'));
			return;
		}

		// 권한 확인
		if (!$this->check_org_access($org_id)) {
			echo json_encode(array('success' => false, 'message' => '권한이 없습니다.'));
			return;
		}

		$update_data = array(
			'memo_content' => $memo_content,
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

}
