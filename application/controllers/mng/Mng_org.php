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
	 * 조직 목록 조회 (AJAX) - 하위 카테고리 포함
	 */
	public function get_org_list()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$category_idx = $this->input->get('category_idx');

		// 전체 선택인 경우 (category_idx가 없음) - 미분류 제외하고 모든 조직 조회
		if ($category_idx === null || $category_idx === '') {
			$orgs = $this->Org_model->get_all_orgs_except_uncategorized();
		}
		// 미분류 선택인 경우
		else if ($category_idx === 'uncategorized') {
			$orgs = $this->Org_model->get_orgs_by_category_detailed(null);
		}
		// 특정 카테고리 선택인 경우 - 하위 카테고리 포함
		else {
			$orgs = $this->Org_model->get_orgs_by_category_with_children($category_idx);
		}

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
	 * 카테고리 목록 조회 (AJAX) - 선택박스용 계층구조
	 */
	public function get_category_list()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		try {
			// 계층구조가 표현된 카테고리 목록 조회
			$categories = $this->Org_category_model->get_categories_for_select();

			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array(
				'success' => true,
				'data' => $categories
			));
		} catch (Exception $e) {
			log_message('error', '카테고리 목록 조회 오류: ' . $e->getMessage());
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array(
				'success' => false,
				'message' => '카테고리 목록 조회 중 오류가 발생했습니다.'
			));
		}
	}


	/**
	 * 새 조직 생성 폼 표시
	 */
	public function create()
	{
		$data['user'] = $this->User_model->get_user_by_id($this->session->userdata('user_id'));
		$data['categories'] = $this->Org_category_model->get_all_categories_flat();
		$this->load->view('mng/mng_org_create', $data);
	}


	/**
	 * 새 조직 저장 (AJAX)
	 */
	public function store()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		// 폼 데이터 수집
		$data = array(
			'org_name' => trim($this->input->post('org_name')),
			'org_type' => $this->input->post('org_type') ?: 'church',
			'org_desc' => trim($this->input->post('org_desc')),
			'org_rep' => trim($this->input->post('org_rep')),
			'org_manager' => trim($this->input->post('org_manager')),
			'org_phone' => trim($this->input->post('org_phone')),
			'org_address_postno' => trim($this->input->post('org_address_postno')),
			'org_address' => trim($this->input->post('org_address')),
			'org_address_detail' => trim($this->input->post('org_address_detail')),
			'category_idx' => $this->input->post('category_idx') ?: null,
			'leader_name' => $this->input->post('leader_name') ?: '리더',
			'new_name' => $this->input->post('new_name') ?: '새가족'
		);

		// 필수 필드 검증
		if (empty($data['org_name'])) {
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array('success' => false, 'message' => '조직명은 필수입니다.'));
			return;
		}

		// 태그 처리
		$org_tag = $this->input->post('org_tag');
		if (!empty($org_tag)) {
			if (is_string($org_tag)) {
				$org_tag = json_decode($org_tag, true);
			}
			$data['org_tag'] = $org_tag;
		}

		// 빈 값 제거
		$data = array_filter($data, function($value) {
			return $value !== '' && $value !== null;
		});

		try {
			$result = $this->Org_model->insert_org($data);

			if ($result) {
				header('Content-Type: application/json; charset=utf-8');
				echo json_encode(array(
					'success' => true,
					'message' => '조직이 성공적으로 생성되었습니다.'
				));
			} else {
				header('Content-Type: application/json; charset=utf-8');
				echo json_encode(array(
					'success' => false,
					'message' => '조직 생성에 실패했습니다.'
				));
			}
		} catch (Exception $e) {
			log_message('error', '조직 생성 오류: ' . $e->getMessage());
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array(
				'success' => false,
				'message' => '조직 생성 중 오류가 발생했습니다.'
			));
		}
	}


	/**
	 * 단일 조직 삭제 (AJAX)
	 */
	public function delete()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$org_id = $this->input->post('org_id');

		if (!$org_id) {
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array('success' => false, 'message' => '삭제할 조직 ID가 누락되었습니다.'));
			return;
		}

		try {
			$result = $this->Org_model->delete_org($org_id);

			if ($result) {
				header('Content-Type: application/json; charset=utf-8');
				echo json_encode(array(
					'success' => true,
					'message' => '조직이 성공적으로 삭제되었습니다.'
				));
			} else {
				header('Content-Type: application/json; charset=utf-8');
				echo json_encode(array(
					'success' => false,
					'message' => '조직 삭제에 실패했습니다.'
				));
			}
		} catch (Exception $e) {
			log_message('error', '조직 삭제 오류: ' . $e->getMessage());
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array(
				'success' => false,
				'message' => '조직 삭제 중 오류가 발생했습니다.'
			));
		}
	}



	/**
	 * 조직 검색 (AJAX)
	 */
	public function search()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$keyword = trim($this->input->get('keyword'));
		$category_idx = $this->input->get('category_idx');

		if (empty($keyword)) {
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array('success' => false, 'message' => '검색어를 입력해주세요.'));
			return;
		}

		try {
			$orgs = $this->Org_model->search_orgs($keyword, $category_idx);

			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array(
				'success' => true,
				'data' => $orgs
			));
		} catch (Exception $e) {
			log_message('error', '조직 검색 오류: ' . $e->getMessage());
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array(
				'success' => false,
				'message' => '조직 검색 중 오류가 발생했습니다.'
			));
		}
	}


	/**
	 * 조직 카테고리 이동 (AJAX)
	 */
	public function move_to_category()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$org_ids = $this->input->post('org_ids');
		$category_idx = $this->input->post('category_idx');

		if (!is_array($org_ids) || empty($org_ids)) {
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array('success' => false, 'message' => '이동할 조직을 선택해주세요.'));
			return;
		}

		try {
			$success_count = 0;
			foreach ($org_ids as $org_id) {
				if ($category_idx === 'uncategorized' || $category_idx === '') {
					$result = $this->Org_model->unassign_org_from_category($org_id);
				} else {
					$result = $this->Org_model->assign_org_to_category($org_id, $category_idx);
				}

				if ($result) {
					$success_count++;
				}
			}

			if ($success_count > 0) {
				header('Content-Type: application/json; charset=utf-8');
				echo json_encode(array(
					'success' => true,
					'message' => "{$success_count}개의 조직이 성공적으로 이동되었습니다."
				));
			} else {
				header('Content-Type: application/json; charset=utf-8');
				echo json_encode(array(
					'success' => false,
					'message' => '조직 이동에 실패했습니다.'
				));
			}
		} catch (Exception $e) {
			log_message('error', '조직 이동 오류: ' . $e->getMessage());
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array(
				'success' => false,
				'message' => '조직 이동 중 오류가 발생했습니다.'
			));
		}
	}

	/**
	 * 엑셀 다운로드
	 */
	public function excel_download()
	{
		$category_idx = $this->input->get('category_idx');

		// 미분류 타입 처리
		if ($category_idx === 'uncategorized') {
			$category_idx = null;
		}

		$orgs = $this->Org_model->get_orgs_by_category_detailed($category_idx);

		// PHPSpreadsheet 라이브러리 로드 (라이브러리가 설치되어 있다고 가정)
		$this->load->library('excel');

		$filename = '조직목록_' . date('Y-m-d_H-i-s') . '.xlsx';

		$this->excel->download_orgs($orgs, $filename);
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
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array('success' => false, 'message' => '조직 ID가 누락되었습니다.'));
			return;
		}

		// 폼 데이터 수집
		$data = array(
			'org_name' => trim($this->input->post('org_name')),
			'org_type' => $this->input->post('org_type'),
			'org_desc' => trim($this->input->post('org_desc')),
			'org_rep' => trim($this->input->post('org_rep')),
			'org_manager' => trim($this->input->post('org_manager')),
			'org_phone' => trim($this->input->post('org_phone')),
			'org_address_postno' => trim($this->input->post('org_address_postno')),
			'org_address' => trim($this->input->post('org_address')),
			'org_address_detail' => trim($this->input->post('org_address_detail')),
			'category_idx' => $this->input->post('category_idx') ?: null
		);

		// 태그 처리
		$org_tag = $this->input->post('org_tag');
		if (!empty($org_tag)) {
			if (is_string($org_tag)) {
				$org_tag = json_decode($org_tag, true);
			}
			$data['org_tag'] = $org_tag;
		} else {
			$data['org_tag'] = null;
		}

		// 빈 값 제거
		$data = array_filter($data, function($value) {
			return $value !== '';
		});

		try {
			$result = $this->Org_model->update_org($org_id, $data);

			if ($result) {
				header('Content-Type: application/json; charset=utf-8');
				echo json_encode(array(
					'success' => true,
					'message' => '조직 정보가 성공적으로 업데이트되었습니다.'
				));
			} else {
				header('Content-Type: application/json; charset=utf-8');
				echo json_encode(array(
					'success' => false,
					'message' => '조직 정보 업데이트에 실패했습니다.'
				));
			}
		} catch (Exception $e) {
			log_message('error', '조직 업데이트 오류: ' . $e->getMessage());
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array(
				'success' => false,
				'message' => '조직 정보 업데이트 중 오류가 발생했습니다.'
			));
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

		if (!is_array($org_ids) || empty($org_ids)) {
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array('success' => false, 'message' => '삭제할 조직을 선택해주세요.'));
			return;
		}

		try {
			$result = $this->Org_model->bulk_delete_orgs($org_ids);

			if ($result) {
				$count = count($org_ids);
				header('Content-Type: application/json; charset=utf-8');
				echo json_encode(array(
					'success' => true,
					'message' => "{$count}개의 조직이 성공적으로 삭제되었습니다."
				));
			} else {
				header('Content-Type: application/json; charset=utf-8');
				echo json_encode(array(
					'success' => false,
					'message' => '조직 삭제에 실패했습니다.'
				));
			}
		} catch (Exception $e) {
			log_message('error', '조직 삭제 오류: ' . $e->getMessage());
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array(
				'success' => false,
				'message' => '조직 삭제 중 오류가 발생했습니다.'
			));
		}
	}

	/**
	 * 전체 조직 수 조회 (AJAX)
	 */
	public function get_total_org_count()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$total_count = $this->Org_category_model->get_total_categorized_org_count();

		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(array(
			'success' => true,
			'total_count' => $total_count
		));
	}

}
