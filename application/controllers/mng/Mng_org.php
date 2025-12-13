<?php
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



		// 마스터 권한 확인
		if ($this->session->userdata('master_yn') !== 'Y') {
			show_error('접근 권한이 없습니다.', 403);
		}

		// 메뉴 접근 권한 확인
		$this->check_menu_access('mng_org');
	}


	/**
	 * 메뉴 접근 권한 확인
	 */
	private function check_menu_access($menu_key)
	{
		$user_id = $this->session->userdata('user_id');
		$user = $this->User_model->get_user_by_id($user_id);

		$master_managed_menus = array();
		if (!empty($user['master_managed_menus'])) {
			$master_managed_menus = json_decode($user['master_managed_menus'], true);
			if (!is_array($master_managed_menus)) {
				$master_managed_menus = array();
			}
		}

		// master_managed_menus가 비어있으면 모든 메뉴 접근 가능
		if (empty($master_managed_menus)) {
			return;
		}

		// 접근 권한이 없는 경우
		if (!in_array($menu_key, $master_managed_menus)) {
			show_error('해당 메뉴에 접근할 권한이 없습니다.', 403);
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
	 * 조직 목록 조회 (AJAX) - 하위 카테고리 포함
	 */
	public function get_org_list()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$category_idx = $this->input->get('category_idx');

		// 사용자 정보 조회
		$user_id = $this->session->userdata('user_id');
		$user = $this->User_model->get_user_by_id($user_id);

		$visible_categories = array();

		// master_managed_category 확인
		if (!empty($user['master_managed_category'])) {
			$master_managed_category = json_decode($user['master_managed_category'], true);
			if (is_array($master_managed_category) && !empty($master_managed_category)) {
				$visible_categories = $master_managed_category;
			}
		}

		// 전체 선택인 경우
		if ($category_idx === null || $category_idx === '') {
			// 필터링된 카테고리가 있으면 해당 카테고리들의 조직만 조회
			if (!empty($visible_categories)) {
				$orgs = $this->Org_model->get_orgs_by_filtered_categories($visible_categories);
			} else {
				// 필터링 없으면 미분류 제외한 전체 조직 조회
				$orgs = $this->Org_model->get_all_orgs_except_uncategorized();
			}
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
			// 사용자 정보 조회
			$user_id = $this->session->userdata('user_id');
			$user = $this->User_model->get_user_by_id($user_id);

			$visible_categories = array();

			// master_managed_category 확인
			if (!empty($user['master_managed_category'])) {
				$master_managed_category = json_decode($user['master_managed_category'], true);
				if (is_array($master_managed_category) && !empty($master_managed_category)) {
					$visible_categories = $master_managed_category;
				}
			}

			// 필터링된 카테고리가 있으면 해당 카테고리만 조회
			if (!empty($visible_categories)) {
				$categories = $this->Org_category_model->get_categories_for_select_filtered($visible_categories);
			} else {
				// 필터링 없으면 전체 카테고리 조회
				$categories = $this->Org_category_model->get_categories_for_select();
			}

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
			echo json_encode(array(
				'success' => false,
				'message' => '조직 ID가 누락되었습니다.'
			), JSON_UNESCAPED_UNICODE);
			return;
		}

		// 업데이트할 데이터 수집
		$data = array();

		if ($this->input->post('org_name') !== null) {
			$data['org_name'] = trim($this->input->post('org_name'));
		}
		if ($this->input->post('org_type') !== null) {
			$data['org_type'] = $this->input->post('org_type');
		}
		if ($this->input->post('org_desc') !== null) {
			$data['org_desc'] = $this->input->post('org_desc');
		}
		if ($this->input->post('org_rep') !== null) {
			$data['org_rep'] = $this->input->post('org_rep');
		}
		if ($this->input->post('org_manager') !== null) {
			$data['org_manager'] = $this->input->post('org_manager');
		}
		if ($this->input->post('org_phone') !== null) {
			$data['org_phone'] = $this->input->post('org_phone');
		}
		if ($this->input->post('org_address_postno') !== null) {
			$data['org_address_postno'] = $this->input->post('org_address_postno');
		}
		if ($this->input->post('org_address') !== null) {
			$data['org_address'] = $this->input->post('org_address');
		}
		if ($this->input->post('org_address_detail') !== null) {
			$data['org_address_detail'] = $this->input->post('org_address_detail');
		}
		if ($this->input->post('category_idx') !== null) {
			$category_idx = $this->input->post('category_idx');
			$data['category_idx'] = ($category_idx === '' || $category_idx === 'uncategorized') ? null : $category_idx;
		}

		// 태그 처리 - JSON_UNESCAPED_UNICODE 옵션 사용
		$org_tag = $this->input->post('org_tag');
		if (!empty($org_tag)) {
			if (is_string($org_tag)) {
				$org_tag = json_decode($org_tag, true);
			}
			if (is_array($org_tag) && count($org_tag) > 0) {
				// JSON_UNESCAPED_UNICODE 옵션으로 한글 유지
				$data['org_tag'] = json_encode($org_tag, JSON_UNESCAPED_UNICODE);
			} else {
				$data['org_tag'] = null;
			}
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
				), JSON_UNESCAPED_UNICODE);
			} else {
				header('Content-Type: application/json; charset=utf-8');
				echo json_encode(array(
					'success' => false,
					'message' => '조직 정보 업데이트에 실패했습니다.'
				), JSON_UNESCAPED_UNICODE);
			}
		} catch (Exception $e) {
			log_message('error', '조직 업데이트 오류: ' . $e->getMessage());
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array(
				'success' => false,
				'message' => '조직 정보 업데이트 중 오류가 발생했습니다.'
			), JSON_UNESCAPED_UNICODE);
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
	 * 전체 조직 수 조회 (AJAX) - 마스터 권한 필터링 적용
	 */
	public function get_total_org_count()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$user_id = $this->session->userdata('user_id');
		$user = $this->User_model->get_user_by_id($user_id);

		$visible_categories = array();

		// 사용자의 master_managed_category 확인 (마스터용 필드 사용)
		if (!empty($user['master_managed_category'])) {
			$master_managed_category = json_decode($user['master_managed_category'], true);
			if (is_array($master_managed_category) && !empty($master_managed_category)) {
				$visible_categories = $master_managed_category;
			}
		}

		// 필터링된 카테고리의 조직 수만 계산
		if (!empty($visible_categories)) {
			$total_count = $this->Org_category_model->get_filtered_org_count($visible_categories);
		} else {
			// 빈 배열인 경우 모든 조직 수 표시 (기본값)
			$total_count = $this->Org_category_model->get_total_categorized_org_count();
		}

		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(array('total_count' => $total_count));
	}


	/**
	 * 조직 대량 편집 팝업 페이지
	 */
	public function org_popup()
	{
		$data['user'] = $this->User_model->get_user_by_id($this->session->userdata('user_id'));

		// 카테고리 목록 조회 (선택박스용)
		$data['categories'] = $this->Org_category_model->get_categories_for_select();

		$this->load->view('mng/org_popup', $data);
	}

	/**
	 * 조직 대량 업데이트 (AJAX)
	 */
	public function bulk_update_orgs()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$json_data = $this->input->raw_input_stream;
		$request_data = json_decode($json_data, true);

		if (!isset($request_data['orgs']) || !is_array($request_data['orgs'])) {
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array(
				'success' => false,
				'message' => '유효하지 않은 데이터 형식입니다'
			), JSON_UNESCAPED_UNICODE);
			return;
		}

		$orgs = $request_data['orgs'];

		if (empty($orgs)) {
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array(
				'success' => false,
				'message' => '저장할 데이터가 없습니다'
			), JSON_UNESCAPED_UNICODE);
			return;
		}

		try {
			$this->db->trans_start();

			$update_count = 0;
			$insert_count = 0;
			$error_count = 0;

			foreach ($orgs as $org_data) {
				// 조직명 필수 체크
				if (empty($org_data['org_name'])) {
					$error_count++;
					continue;
				}

				// 데이터 정리
				$data = array(
					'org_name' => trim($org_data['org_name']),
					'org_type' => !empty($org_data['org_type']) ? $org_data['org_type'] : 'church',
					'org_desc' => !empty($org_data['org_desc']) ? trim($org_data['org_desc']) : null,
					'org_rep' => !empty($org_data['org_rep']) ? trim($org_data['org_rep']) : null,
					'org_manager' => !empty($org_data['org_manager']) ? trim($org_data['org_manager']) : null,
					'org_phone' => !empty($org_data['org_phone']) ? trim($org_data['org_phone']) : null,
					'org_address_postno' => !empty($org_data['org_address_postno']) ? trim($org_data['org_address_postno']) : null,
					'org_address' => !empty($org_data['org_address']) ? trim($org_data['org_address']) : null,
					'org_address_detail' => !empty($org_data['org_address_detail']) ? trim($org_data['org_address_detail']) : null,
					'category_idx' => !empty($org_data['category_idx']) ? $org_data['category_idx'] : null
				);

				// 태그 처리 - JSON_UNESCAPED_UNICODE 옵션 추가
				if (!empty($org_data['org_tag'])) {
					$tags = $org_data['org_tag'];
					if (is_string($tags)) {
						$tags = json_decode($tags, true);
					}
					if (is_array($tags) && count($tags) > 0) {
						// JSON_UNESCAPED_UNICODE 옵션으로 한글이 정상적으로 저장되도록 수정
						$data['org_tag'] = json_encode($tags, JSON_UNESCAPED_UNICODE);
					} else {
						$data['org_tag'] = null;
					}
				} else {
					$data['org_tag'] = null;
				}

				// org_id가 있으면 업데이트, 없으면 신규 생성
				if (!empty($org_data['org_id'])) {
					// 기존 조직 업데이트
					$org_id = $org_data['org_id'];
					$result = $this->Org_model->update_org($org_id, $data);

					if ($result) {
						$update_count++;
					} else {
						$error_count++;
					}
				} else {
					// 새 조직 생성
					$data['org_code'] = $this->Org_model->generate_org_code();
					$data['regi_date'] = date('Y-m-d H:i:s');

					$new_org_id = $this->Org_model->create_org($data);

					if ($new_org_id) {
						$insert_count++;
					} else {
						$error_count++;
					}
				}
			}

			$this->db->trans_complete();

			if ($this->db->trans_status() === FALSE) {
				throw new Exception('트랜잭션 실패');
			}

			$message = sprintf(
				'저장 완료: 업데이트 %d개, 신규 추가 %d개',
				$update_count,
				$insert_count
			);

			if ($error_count > 0) {
				$message .= sprintf(', 실패 %d개', $error_count);
			}

			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array(
				'success' => true,
				'message' => $message,
				'update_count' => $update_count,
				'insert_count' => $insert_count,
				'error_count' => $error_count
			), JSON_UNESCAPED_UNICODE);

		} catch (Exception $e) {
			log_message('error', '조직 대량 업데이트 오류: ' . $e->getMessage());
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array(
				'success' => false,
				'message' => '저장 중 오류가 발생했습니다: ' . $e->getMessage()
			), JSON_UNESCAPED_UNICODE);
		}
	}


	/**
	 * 조직 카테고리 트리 데이터 조회 (AJAX) - 마스터 권한 적용
	 */
	public function get_category_tree()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$user_id = $this->session->userdata('user_id');
		$user = $this->User_model->get_user_by_id($user_id);

		$visible_categories = array();

		// 사용자의 master_managed_category 확인 (체크된 카테고리만 보임)
		if (!empty($user['master_managed_category'])) {
			$master_managed_category = json_decode($user['master_managed_category'], true);
			if (is_array($master_managed_category) && !empty($master_managed_category)) {
				$visible_categories = $master_managed_category;
			}
		}

		// visible_categories가 비어있으면 전체 트리, 있으면 체크된 항목만
		if (!empty($visible_categories)) {
			$tree_data = $this->Org_category_model->get_category_tree_for_master($visible_categories);
		} else {
			// 빈 배열인 경우 모든 카테고리 표시 (기본값)
			$tree_data = $this->Org_category_model->get_category_tree();
		}

		header('Content-Type: application/json; charset=utf-8');
		echo json_encode($tree_data);
	}


}
