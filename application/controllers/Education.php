<?php


defined('BASEPATH') or exit('No direct script access allowed');

class Education extends My_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->library('session');
		$this->load->helper('url');
		$this->load->model('Education_model');
		$this->load->model('Org_model');
		$this->load->model('User_model');

		// 메뉴 권한 체크
		$this->check_menu_access('EDUCATION_MANAGEMENT');
	}

	/**
	 * 교육관리 메인 페이지
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
			$this->handle_access_denied('해당 조직의 교육을 관리할 권한이 없습니다.');
			return;
		}

		// 현재 조직 정보를 JavaScript로 전달
		$data['orgs'] = array($data['current_org']);

		$this->load->view('education', $data);
	}

	/**
	 * 파일 위치: application/controllers/Education.php - get_category_tree() 함수 수정
	 * 역할: get_org_by_id를 get_org_detail_by_id로 변경
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
		$category_data = $this->Education_model->get_category_tree($active_org_id);

		// 카테고리가 없으면 기본 카테고리 생성
		if (empty($category_data)) {
			$default_result = $this->Education_model->create_default_categories($active_org_id, $user_id);

			if ($default_result) {
				// 다시 카테고리 조회
				$category_data = $this->Education_model->get_category_tree($active_org_id);
			}
		}

		// 교육 개수 카운트
		$category_counts = $this->Education_model->get_category_edu_counts($active_org_id);

		// Fancytree 노드 구성
		$build_fancytree_nodes = function ($categories) use (&$build_fancytree_nodes, $category_counts) {
			$nodes = array();

			foreach ($categories as $category) {
				$edu_count = isset($category_counts[$category['code']]) ? $category_counts[$category['code']] : 0;

				$title = $category['name'];
				if ($edu_count > 0) {
					$title .= ' (' . $edu_count . ')';
				}

				$node = array(
					'key' => 'category_' . $category['code'],
					'title' => $title,
					'folder' => true,
					'expanded' => false,
					'data' => array(
						'type' => 'category',
						'category_code' => $category['code']
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

			// 전체 교육 개수
			$total_edu_count = $this->Education_model->get_total_edu_count($active_org_id);

			// 루트 노드 (조직) - get_org_detail_by_id 사용
			$org_info = $this->Org_model->get_org_detail_by_id($active_org_id);
			$org_title = $org_info['org_name'];
			if ($total_edu_count > 0) {
				$org_title .= ' (' . $total_edu_count . ')';
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
	 * 교육 목록 조회 API
	 */
	public function get_edu_list()
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

		$edu_list = array();

		if ($type === 'org') {
			// 전체 교육 목록
			$edu_list = $this->Education_model->get_edu_list_by_org($org_id);
		} else if ($type === 'category') {
			// 카테고리별 교육 목록
			$edu_list = $this->Education_model->get_edu_list_by_category($org_id, $category_code);
		}

		echo json_encode(array(
			'success' => true,
			'data' => $edu_list,
			'total_count' => count($edu_list)
		));
	}

	/**
	 * 교육 상세 조회 API
	 */
	public function get_edu_detail()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$edu_idx = $this->input->post('edu_idx');

		if (!$edu_idx) {
			echo json_encode(array('success' => false, 'message' => '교육 정보가 필요합니다.'));
			return;
		}

		$edu_detail = $this->Education_model->get_edu_by_idx($edu_idx);

		if (!$edu_detail) {
			echo json_encode(array('success' => false, 'message' => '교육 정보를 찾을 수 없습니다.'));
			return;
		}

		// 권한 확인
		if (!$this->check_org_access($edu_detail['org_id'])) {
			echo json_encode(array('success' => false, 'message' => '권한이 없습니다.'));
			return;
		}

		echo json_encode(array(
			'success' => true,
			'data' => $edu_detail
		));
	}



	/**
	 * 파일 위치: application/controllers/Education.php
	 * 역할: 교육 등록/수정 - 정원, 계좌정보 처리 추가
	 */
	public function insert_edu()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$user_id = $this->session->userdata('user_id');
		$org_id = $this->input->post('org_id');

		if (!$this->check_org_access($org_id)) {
			echo json_encode(array('success' => false, 'message' => '권한이 없습니다.'));
			return;
		}

		$edu_data = array(
			'org_id' => $org_id,
			'category_code' => $this->input->post('category_code'),
			'edu_name' => $this->input->post('edu_name'),
			'edu_location' => $this->input->post('edu_location'),
			'edu_start_date' => $this->input->post('edu_start_date') ?: null,
			'edu_end_date' => $this->input->post('edu_end_date') ?: null,
			'edu_days' => $this->process_json_field($this->input->post('edu_days')),
			'edu_times' => $this->process_json_field($this->input->post('edu_times')),
			'edu_leader' => $this->input->post('edu_leader'),
			'edu_leader_phone' => $this->input->post('edu_leader_phone'),
			'edu_leader_age' => $this->input->post('edu_leader_age'),
			'edu_leader_gender' => $this->input->post('edu_leader_gender'),
			'edu_desc' => $this->input->post('edu_desc'),
			'public_yn' => $this->input->post('public_yn') ?: 'N',
			'online_yn' => $this->input->post('online_yn') ?: 'N',
			'youtube_url' => $this->input->post('youtube_url'),
			'edu_fee' => intval($this->input->post('edu_fee')),
			'edu_capacity' => intval($this->input->post('edu_capacity')),
			'bank_account' => $this->input->post('bank_account') ?: null,
			'user_id' => $user_id
		);

		// 포스터 이미지 처리
		if (!empty($_FILES['poster_img']['name'])) {
			$poster_path = $this->upload_poster_image($org_id);
			if ($poster_path) {
				$edu_data['poster_img'] = $poster_path;
			}
		}

		$result = $this->Education_model->insert_edu($edu_data);

		if ($result) {
			echo json_encode(array('success' => true, 'message' => '교육이 등록되었습니다.', 'edu_idx' => $result));
		} else {
			echo json_encode(array('success' => false, 'message' => '교육 등록에 실패했습니다.'));
		}
	}

	/**
	 * 회원 검색 (Select2용)
	 */
	public function search_members()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$keyword = $this->input->get('keyword');
		$org_id = $this->input->get('org_id');

		$this->load->model('Member_model');
		$members = $this->Member_model->search_members($org_id, $keyword, 20);

		echo json_encode(array('success' => true, 'data' => $members));
	}

	/**
	 * 신청자 목록 조회
	 */
	public function get_applicant_list()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$edu_idx = $this->input->post('edu_idx');
		$applicants = $this->Education_model->get_applicant_list($edu_idx);

		echo json_encode(array('success' => true, 'data' => $applicants));
	}

	/**
	 * 신청자 추가
	 */
	public function add_applicants()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$user_id = $this->session->userdata('user_id');
		$edu_idx = $this->input->post('edu_idx');
		$applicants_json = $this->input->post('applicants');

		// 교육 정보 조회
		$edu = $this->Education_model->get_edu_by_idx($edu_idx);
		if (!$edu) {
			echo json_encode(array('success' => false, 'message' => '교육 정보를 찾을 수 없습니다.'));
			return;
		}

		$applicants = json_decode($applicants_json, true);
		if (!$applicants || !is_array($applicants)) {
			echo json_encode(array('success' => false, 'message' => '신청자 정보가 올바르지 않습니다.'));
			return;
		}

		$success_count = 0;
		foreach ($applicants as $applicant) {
			$data = array(
				'edu_idx' => $edu_idx,
				'org_id' => $edu['org_id'],
				'member_idx' => !empty($applicant['member_idx']) && is_numeric($applicant['member_idx']) ? $applicant['member_idx'] : null,
				'applicant_name' => $applicant['name'],
				'applicant_phone' => $applicant['phone'] ?: null,
				'status' => '신청',
				'user_id' => $user_id
			);

			if ($this->Education_model->add_applicant($data)) {
				$success_count++;
			}
		}

		echo json_encode(array('success' => true, 'message' => $success_count . '명의 신청자가 추가되었습니다.'));
	}

	/**
	 * 신청자 수정
	 */
	public function update_applicant()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$applicant_idx = $this->input->post('applicant_idx');
		$data = array(
			'applicant_name' => $this->input->post('applicant_name'),
			'applicant_phone' => $this->input->post('applicant_phone'),
			'status' => $this->input->post('status')
		);

		$result = $this->Education_model->update_applicant($applicant_idx, $data);

		if ($result) {
			echo json_encode(array('success' => true, 'message' => '신청자 정보가 수정되었습니다.'));
		} else {
			echo json_encode(array('success' => false, 'message' => '신청자 수정에 실패했습니다.'));
		}
	}

	/**
	 * 신청자 삭제
	 */
	public function delete_applicant()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$applicant_idx = $this->input->post('applicant_idx');
		$result = $this->Education_model->delete_applicant($applicant_idx);

		if ($result) {
			echo json_encode(array('success' => true, 'message' => '신청자가 삭제되었습니다.'));
		} else {
			echo json_encode(array('success' => false, 'message' => '신청자 삭제에 실패했습니다.'));
		}
	}

	/**
	 * 상태 일괄변경
	 */
	public function bulk_update_status()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$edu_idx = $this->input->post('edu_idx');
		$status = $this->input->post('status');

		$result = $this->Education_model->bulk_update_applicant_status($edu_idx, $status);

		if ($result) {
			echo json_encode(array('success' => true, 'message' => '상태가 일괄 변경되었습니다.'));
		} else {
			echo json_encode(array('success' => false, 'message' => '상태 변경에 실패했습니다.'));
		}
	}

	/**
	 * 파일 위치: application/controllers/Education.php
	 * 역할: 교육 수정 - 정원, 계좌정보 처리 추가
	 */
	public function update_edu()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$user_id = $this->session->userdata('user_id');
		$edu_idx = $this->input->post('edu_idx');
		$org_id = $this->input->post('org_id');

		if (!$this->check_org_access($org_id)) {
			echo json_encode(array('success' => false, 'message' => '권한이 없습니다.'));
			return;
		}

		$edu_data = array(
			'category_code' => $this->input->post('category_code'),
			'edu_name' => $this->input->post('edu_name'),
			'edu_location' => $this->input->post('edu_location'),
			'edu_start_date' => $this->input->post('edu_start_date') ?: null,
			'edu_end_date' => $this->input->post('edu_end_date') ?: null,
			'edu_days' => $this->process_json_field($this->input->post('edu_days')),
			'edu_times' => $this->process_json_field($this->input->post('edu_times')),
			'edu_leader' => $this->input->post('edu_leader'),
			'edu_leader_phone' => $this->input->post('edu_leader_phone'),
			'edu_leader_age' => $this->input->post('edu_leader_age'),
			'edu_leader_gender' => $this->input->post('edu_leader_gender'),
			'edu_desc' => $this->input->post('edu_desc'),
			'public_yn' => $this->input->post('public_yn') ?: 'N',
			'online_yn' => $this->input->post('online_yn') ?: 'N',
			'youtube_url' => $this->input->post('youtube_url'),
			'edu_fee' => intval($this->input->post('edu_fee')),
			'edu_capacity' => intval($this->input->post('edu_capacity')),
			'bank_account' => $this->input->post('bank_account') ?: null,
			'user_id' => $user_id
		);

		// 포스터 삭제 플래그 처리
		if ($this->input->post('remove_poster') == '1') {
			$edu_data['poster_img'] = null;
		}

		// 포스터 이미지 처리
		if (!empty($_FILES['poster_img']['name'])) {
			$poster_path = $this->upload_poster_image($org_id);
			if ($poster_path) {
				$edu_data['poster_img'] = $poster_path;
			}
		}

		$result = $this->Education_model->update_edu($edu_idx, $edu_data);

		if ($result) {
			echo json_encode(array('success' => true, 'message' => '교육이 수정되었습니다.'));
		} else {
			echo json_encode(array('success' => false, 'message' => '교육 수정에 실패했습니다.'));
		}
	}

	/**
	 * JSON 필드 처리 (배열/문자열 -> JSON 문자열)
	 */
	private function process_json_field($data)
	{
		if (empty($data)) {
			return json_encode(array());
		}
		if (is_array($data)) {
			return json_encode($data, JSON_UNESCAPED_UNICODE);
		}
		// 이미 문자열인 경우 (쉼표로 구분된 문자열 등)
		if (is_string($data)) {
			// 쉼표로 구분된 문자열이면 배열로 변환 후 JSON 저장
			if (strpos($data, ',') !== false) {
				$array = array_map('trim', explode(',', $data));
				return json_encode($array, JSON_UNESCAPED_UNICODE);
			}

			// JSON 형식이 아닌 일반 문자열이면 배열로 감싸서 저장
			// (단, 이미 JSON 형식이면 그대로 반환하는 로직이 필요할 수도 있지만,
			// 현재 JS에서는 쉼표구분 문자열로 보내므로 배열로 변환하는 것이 안전)
			// 여기서는 단순히 배열 하나로 처리
			return json_encode(array($data), JSON_UNESCAPED_UNICODE);
		}
		return json_encode(array());
	}

	/**
	 * 포스터 이미지 업로드 및 리사이징 - edu_idx 파라미터 추가
	 */
	private function upload_poster_image($org_id, $edu_idx)
	{
		if (empty($_FILES['poster_img']['name'])) {
			return null;
		}

		$this->load->library('upload');
		$this->load->library('image_lib');

		// 업로드 디렉토리 설정
		$upload_path = './uploads/edu_img/' . $org_id . '/';

		// 디렉토리가 없으면 생성
		if (!is_dir($upload_path)) {
			mkdir($upload_path, 0755, true);
		}

		// 기존 파일 확장자 확인
		$original_name = $_FILES['poster_img']['name'];
		$file_extension = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
		$allowed_extensions = array('jpg', 'jpeg', 'png', 'gif');

		if (!in_array($file_extension, $allowed_extensions)) {
			return null;
		}

		// 파일명을 edu_{edu_idx}.{확장자} 형식으로 설정
		$new_filename = 'edu_' . $edu_idx . '.' . $file_extension;

		// 같은 edu_idx의 기존 이미지 파일들 삭제 (확장자가 다를 수 있으므로)
		$existing_files = glob($upload_path . 'edu_' . $edu_idx . '.*');
		foreach ($existing_files as $existing_file) {
			if (file_exists($existing_file)) {
				unlink($existing_file);
			}
		}

		// 업로드 설정
		$config['upload_path'] = $upload_path;
		$config['allowed_types'] = 'jpg|jpeg|png|gif';
		$config['max_size'] = 10240; // 10MB
		$config['file_name'] = 'edu_' . $edu_idx;
		$config['overwrite'] = TRUE; // 같은 이름 파일 덮어쓰기

		$this->upload->initialize($config);

		// 파일 업로드
		if (!$this->upload->do_upload('poster_img')) {
			log_message('error', '포스터 이미지 업로드 실패: ' . $this->upload->display_errors());
			return null;
		}

		$upload_data = $this->upload->data();
		$uploaded_file_path = $upload_data['full_path'];
		$file_name = $upload_data['file_name'];

		// 이미지 크기 확인 및 리사이징
		$image_info = getimagesize($uploaded_file_path);
		if ($image_info) {
			$original_width = $image_info[0];

			// 가로 1200px 초과 시 리사이징
			if ($original_width > 1200) {
				$resize_config['image_library'] = 'gd2';
				$resize_config['source_image'] = $uploaded_file_path;
				$resize_config['maintain_ratio'] = TRUE;
				$resize_config['width'] = 1200;
				$resize_config['quality'] = 90;

				$this->image_lib->initialize($resize_config);

				if (!$this->image_lib->resize()) {
					log_message('error', '이미지 리사이징 실패: ' . $this->image_lib->display_errors());
				}

				$this->image_lib->clear();
			}
		}

		// 상대 경로 반환
		return 'uploads/edu_img/' . $org_id . '/' . $file_name;
	}

	/**
	 * 교육 삭제 시 이미지도 함께 삭제하도록 수정
	 */
	public function delete_edu()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$edu_idx = $this->input->post('edu_idx');

		if (!$edu_idx) {
			echo json_encode(array('success' => false, 'message' => '교육 정보가 필요합니다.'));
			return;
		}

		// 기존 교육 정보 조회
		$existing_edu = $this->Education_model->get_edu_by_idx($edu_idx);

		if (!$existing_edu) {
			echo json_encode(array('success' => false, 'message' => '교육 정보를 찾을 수 없습니다.'));
			return;
		}

		// 권한 확인
		if (!$this->check_org_access($existing_edu['org_id'])) {
			echo json_encode(array('success' => false, 'message' => '권한이 없습니다.'));
			return;
		}

		// 포스터 이미지 삭제
		if (!empty($existing_edu['poster_img'])) {
			$file_path = FCPATH . $existing_edu['poster_img'];
			if (file_exists($file_path)) {
				unlink($file_path);
			}
		}

		$result = $this->Education_model->delete_edu($edu_idx);

		if ($result) {
			echo json_encode(array('success' => true, 'message' => '교육이 삭제되었습니다.'));
		} else {
			echo json_encode(array('success' => false, 'message' => '교육 삭제에 실패했습니다.'));
		}
	}

	/**
	 * 여러 교육 삭제 - 이미지도 함께 삭제
	 */
	public function delete_multiple_edu()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$edu_indexes = $this->input->post('edu_indexes');

		if (!$edu_indexes || !is_array($edu_indexes)) {
			echo json_encode(array('success' => false, 'message' => '삭제할 교육 정보가 필요합니다.'));
			return;
		}

		$success_count = 0;
		$fail_count = 0;

		foreach ($edu_indexes as $edu_idx) {
			// 교육 정보 조회
			$existing_edu = $this->Education_model->get_edu_by_idx($edu_idx);

			if (!$existing_edu) {
				$fail_count++;
				continue;
			}

			// 권한 확인
			if (!$this->check_org_access($existing_edu['org_id'])) {
				$fail_count++;
				continue;
			}

			// 포스터 이미지 삭제
			if (!empty($existing_edu['poster_img'])) {
				$file_path = FCPATH . $existing_edu['poster_img'];
				if (file_exists($file_path)) {
					unlink($file_path);
				}
			}

			// 삭제 실행
			$result = $this->Education_model->delete_edu($edu_idx);

			if ($result) {
				$success_count++;
			} else {
				$fail_count++;
			}
		}

		if ($success_count > 0) {
			$message = $success_count . '개의 교육이 삭제되었습니다.';
			if ($fail_count > 0) {
				$message .= ' (' . $fail_count . '개 실패)';
			}
			echo json_encode(array('success' => true, 'message' => $message));
		} else {
			echo json_encode(array('success' => false, 'message' => '교육 삭제에 실패했습니다.'));
		}
	}




	/**
	 * 카테고리 저장 API
	 */
	public function save_category()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$user_id = $this->session->userdata('user_id');
		$org_id = $this->input->post('org_id');
		$category_json = $this->input->post('category_json');

		// 권한 확인
		if (!$this->check_org_access($org_id)) {
			echo json_encode(array('success' => false, 'message' => '권한이 없습니다.'));
			return;
		}

		$category_data = array(
			'org_id' => $org_id,
			'category_type' => 'edu',
			'category_json' => $category_json,
			'user_id' => $user_id
		);

		$result = $this->Education_model->save_category($category_data);

		if ($result) {
			echo json_encode(array('success' => true, 'message' => '카테고리가 저장되었습니다.'));
		} else {
			echo json_encode(array('success' => false, 'message' => '카테고리 저장에 실패했습니다.'));
		}
	}
}
