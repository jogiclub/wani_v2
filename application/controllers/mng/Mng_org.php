<?php
/**
 * 파일 위치: application/controllers/mng/Mng_org.php
 * 역할: 마스터 권한 조직 관리 컨트롤러
 */

defined('BASEPATH') or exit('No direct script access allowed');

class Mng_org extends CI_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->library('session');
		$this->load->helper('url');
		$this->load->model('Org_model');
		$this->load->model('Org_category_model');
		$this->load->model('User_model');

		// 로그인 확인
		if (!$this->session->userdata('user_id')) {
			redirect('login');
		}

		// 마스터 권한 확인
		if ($this->session->userdata('master_yn') !== 'Y') {
			show_error('접근 권한이 없습니다.', 403);
		}
	}

	/**
	 * 조직관리 메인 페이지
	 */
	public function index()
	{
		$data['user'] = $this->User_model->get_user_by_id($this->session->userdata('user_id'));
		$this->load->view('mng/mng_orglist', $data);
	}

	/**
	 * 조직 카테고리 트리 데이터 조회 (AJAX)
	 */
	public function get_category_tree()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$tree_data = $this->Org_category_model->get_category_tree();

		header('Content-Type: application/json; charset=utf-8');
		echo json_encode($tree_data);
	}

	/**
	 * 조직 목록 조회 (AJAX)
	 */
	public function get_org_list()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$category_idx = $this->input->get('category_idx');
		$orgs = $this->Org_model->get_orgs_by_category_detailed($category_idx);

		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(array(
			'success' => true,
			'data' => $orgs
		));
	}

	/**
	 * 기존 태그 목록 조회 (AJAX)
	 */
	public function get_existing_tags()
	{
		log_message('debug', 'get_existing_tags 호출됨');

		if (!$this->input->is_ajax_request()) {
			log_message('error', 'get_existing_tags: AJAX 요청이 아님');
			show_404();
		}

		try {
			$tags = $this->Org_model->get_existing_tags();
			log_message('debug', 'get_existing_tags 결과: ' . print_r($tags, true));

			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array(
				'success' => true,
				'data' => $tags
			));

		} catch (Exception $e) {
			log_message('error', 'get_existing_tags 오류: ' . $e->getMessage());
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array('success' => false, 'message' => '태그 목록 조회 중 오류가 발생했습니다.'));
		}
	}

	/**
	 * 조직 상세 정보 조회 (AJAX)
	 */
	public function get_org_detail()
	{
		// 디버깅을 위한 로그
		log_message('debug', 'get_org_detail 호출됨');

		if (!$this->input->is_ajax_request()) {
			log_message('error', 'get_org_detail: AJAX 요청이 아님');
			show_404();
		}

		$org_id = $this->input->get('org_id');
		log_message('debug', 'get_org_detail org_id: ' . $org_id);

		if (!$org_id) {
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array('success' => false, 'message' => '조직 ID가 누락되었습니다.'));
			return;
		}

		try {
			$org_detail = $this->Org_model->get_org_detail_by_id($org_id);
			log_message('debug', 'get_org_detail 조직 정보: ' . print_r($org_detail, true));

			if (!$org_detail) {
				header('Content-Type: application/json; charset=utf-8');
				echo json_encode(array('success' => false, 'message' => '조직 정보를 찾을 수 없습니다.'));
				return;
			}

			// 조직 회원 수 조회
			$member_count = $this->Org_model->get_org_member_count($org_id);
			$org_detail['member_count'] = $member_count;

			// 조직 관리자 정보 조회
			$org_admin = $this->Org_model->get_org_admin($org_id);
			$org_detail['admin_info'] = $org_admin;

			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array(
				'success' => true,
				'data' => $org_detail
			));

		} catch (Exception $e) {
			log_message('error', 'get_org_detail 오류: ' . $e->getMessage());
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array('success' => false, 'message' => '서버 오류가 발생했습니다.'));
		}
	}

	/**
	 * 새 카테고리 추가 (AJAX)
	 */
	public function add_category()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$category_name = trim($this->input->post('category_name'));
		$parent_idx = $this->input->post('parent_idx');

		if (empty($category_name)) {
			echo json_encode(array('success' => false, 'message' => '카테고리명을 입력해주세요.'));
			return;
		}

		// parent_idx가 빈 값이면 null로 설정
		if (empty($parent_idx)) {
			$parent_idx = null;
		}

		$data = array(
			'category_name' => $category_name,
			'parent_idx' => $parent_idx,
			'category_order' => $this->Org_category_model->get_next_order($parent_idx)
		);

		$result = $this->Org_category_model->insert_category($data);

		if ($result) {
			echo json_encode(array('success' => true, 'message' => '카테고리가 추가되었습니다.'));
		} else {
			echo json_encode(array('success' => false, 'message' => '카테고리 추가에 실패했습니다.'));
		}
	}

	/**
	 * 카테고리명 수정 (AJAX)
	 */
	public function rename_category()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$category_idx = $this->input->post('category_idx');
		$category_name = trim($this->input->post('category_name'));

		if (!$category_idx || empty($category_name)) {
			echo json_encode(array('success' => false, 'message' => '필수 정보가 누락되었습니다.'));
			return;
		}

		$result = $this->Org_category_model->update_category($category_idx, array('category_name' => $category_name));

		if ($result) {
			echo json_encode(array('success' => true, 'message' => '카테고리명이 수정되었습니다.'));
		} else {
			echo json_encode(array('success' => false, 'message' => '카테고리명 수정에 실패했습니다.'));
		}
	}

	/**
	 * 카테고리 삭제 (AJAX)
	 */
	public function delete_category()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$category_idx = $this->input->post('category_idx');

		if (!$category_idx) {
			echo json_encode(array('success' => false, 'message' => '카테고리 ID가 누락되었습니다.'));
			return;
		}

		// 하위 카테고리 또는 조직이 있는지 확인
		if ($this->Org_category_model->has_children($category_idx) || $this->Org_model->has_orgs_in_category($category_idx)) {
			echo json_encode(array('success' => false, 'message' => '하위 카테고리나 조직이 있어서 삭제할 수 없습니다.'));
			return;
		}

		$result = $this->Org_category_model->delete_category($category_idx);

		if ($result) {
			echo json_encode(array('success' => true, 'message' => '카테고리가 삭제되었습니다.'));
		} else {
			echo json_encode(array('success' => false, 'message' => '카테고리 삭제에 실패했습니다.'));
		}
	}

	/**
	 * 조직 삭제 (AJAX)
	 */
	public function delete_org()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$org_id = $this->input->post('org_id');

		if (!$org_id) {
			echo json_encode(array('success' => false, 'message' => '조직 ID가 누락되었습니다.'));
			return;
		}

		// 조직에 회원이 있는지 확인
		$member_count = $this->Org_model->get_org_member_count($org_id);
		if ($member_count > 0) {
			echo json_encode(array('success' => false, 'message' => '조직에 회원이 있어서 삭제할 수 없습니다.'));
			return;
		}

		$result = $this->Org_model->delete_org($org_id);

		if ($result) {
			echo json_encode(array('success' => true, 'message' => '조직이 삭제되었습니다.'));
		} else {
			echo json_encode(array('success' => false, 'message' => '조직 삭제에 실패했습니다.'));
		}
	}

	/**
	 * 카테고리 목록 조회 (AJAX)
	 */
	public function get_category_list()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$categories = $this->Org_category_model->get_all_categories();

		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(array(
			'success' => true,
			'data' => $categories
		));
	}


	/**
	 * 조직 정보 업데이트 (AJAX)
	 */
	public function update_org()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$org_id = $this->input->post('org_id');
		if (!$org_id) {
			echo json_encode(array('success' => false, 'message' => '조직 ID가 누락되었습니다.'));
			return;
		}

		// 업데이트할 데이터 준비
		$update_data = array();

		// 각 필드가 존재하고 공백이 아닌 경우에만 업데이트 데이터에 추가
		$fields = array(
			'org_name', 'org_type', 'org_desc', 'org_rep', 'org_manager',
			'org_phone', 'org_address_postno', 'org_address', 'org_address_detail',
			'category_idx'
		);

		foreach ($fields as $field) {
			$value = $this->input->post($field);
			if ($value !== null && $value !== '') {
				$update_data[$field] = $value;
			}
		}

		// 태그 처리 (JSON 형태로 저장)
		$org_tag = $this->input->post('org_tag');
		if ($org_tag !== null) {
			// 이미 JSON 문자열인지 확인
			if (is_string($org_tag)) {
				// JSON 유효성 검사
				$decoded = json_decode($org_tag, true);
				if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
					$update_data['org_tag'] = $org_tag; // 이미 유효한 JSON
				} else {
					// JSON이 아닌 경우 빈 배열로 저장
					$update_data['org_tag'] = '[]';
				}
			} else {
				// 배열인 경우 JSON으로 변환
				$update_data['org_tag'] = json_encode($org_tag, JSON_UNESCAPED_UNICODE);
			}
		}

		// category_idx가 빈 문자열인 경우 null로 설정
		if (isset($update_data['category_idx']) && $update_data['category_idx'] === '') {
			$update_data['category_idx'] = null;
		}

		// 수정일시 추가
		$update_data['modi_date'] = date('Y-m-d H:i:s');

		$result = $this->Org_model->update_org($org_id, $update_data);

		if ($result) {
			echo json_encode(array('success' => true, 'message' => '조직 정보가 수정되었습니다.'));
		} else {
			echo json_encode(array('success' => false, 'message' => '조직 정보 수정에 실패했습니다.'));
		}
	}

	/**
	 * 조직 다중 삭제 (AJAX)
	 */
	public function bulk_delete_orgs()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$org_ids = $this->input->post('org_ids');

		if (!$org_ids || !is_array($org_ids)) {
			echo json_encode(array('success' => false, 'message' => '삭제할 조직을 선택해주세요.'));
			return;
		}

		$success_count = 0;
		$error_messages = array();

		foreach ($org_ids as $org_id) {
			// 조직에 회원이 있는지 확인
			$member_count = $this->Org_model->get_org_member_count($org_id);
			if ($member_count > 0) {
				// 조직명 가져오기
				$org_detail = $this->Org_model->get_org_detail_by_id($org_id);
				$org_name = $org_detail ? $org_detail['org_name'] : "조직 ID: {$org_id}";
				$error_messages[] = "{$org_name}에 {$member_count}명의 회원이 있어서 삭제할 수 없습니다.";
				continue;
			}

			$result = $this->Org_model->delete_org($org_id);
			if ($result) {
				$success_count++;
			}
		}

		$total_count = count($org_ids);
		$failed_count = $total_count - $success_count;

		if ($success_count > 0 && $failed_count == 0) {
			echo json_encode(array(
				'success' => true,
				'message' => "{$success_count}개의 조직이 삭제되었습니다."
			));
		} else if ($success_count > 0 && $failed_count > 0) {
			$message = "{$success_count}개 삭제 완료, {$failed_count}개 실패";
			if (!empty($error_messages)) {
				$message .= "\n실패 사유:\n" . implode("\n", $error_messages);
			}
			echo json_encode(array(
				'success' => false,
				'message' => $message
			));
		} else {
			$message = "조직 삭제에 실패했습니다.";
			if (!empty($error_messages)) {
				$message .= "\n" . implode("\n", $error_messages);
			}
			echo json_encode(array(
				'success' => false,
				'message' => $message
			));
		}
	}

}
