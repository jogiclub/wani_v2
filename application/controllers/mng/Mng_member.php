<?php
/**
 
 * 역할: 마스터 회원관리 컨트롤러 - 카테고리+조직 트리, 회원 목록 관리
 */
defined('BASEPATH') or exit('No direct script access allowed');

class Mng_member extends CI_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->library('session');
		$this->load->helper('url');
		$this->load->model('Org_model');
		$this->load->model('Org_category_model');
		$this->load->model('User_model');
		$this->load->model('Member_model');

		// 마스터 권한 확인
		if ($this->session->userdata('master_yn') !== 'Y') {
			show_error('접근 권한이 없습니다.', 403);
		}

		// 메뉴 접근 권한 확인
		$this->check_menu_access('mng_member');
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
	 * 회원관리 메인 페이지
	 */
	public function index()
	{
		$data['user'] = $this->User_model->get_user_by_id($this->session->userdata('user_id'));
		$this->load->view('mng/mng_member', $data);
	}

	/**
	 * 마스터가 볼 수 있는 카테고리 목록 가져오기
	 */
	private function get_visible_categories()
	{
		$user_id = $this->session->userdata('user_id');
		$user = $this->User_model->get_user_by_id($user_id);

		$visible_categories = array();
		if (!empty($user['master_managed_category'])) {
			$master_managed_category = json_decode($user['master_managed_category'], true);
			if (is_array($master_managed_category) && !empty($master_managed_category)) {
				$visible_categories = $master_managed_category;
			}
		}

		return $visible_categories;
	}

	/**
	 * 카테고리 + 조직 트리 데이터 조회 (AJAX)
	 */
	public function get_category_org_tree()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$visible_categories = $this->get_visible_categories();

		// 카테고리 트리 조회 (권한 적용)
		if (!empty($visible_categories)) {
			$category_tree = $this->Org_category_model->get_category_tree_for_master($visible_categories);
		} else {
			$category_tree = $this->Org_category_model->get_category_tree();
		}

		// 카테고리 트리에 조직 노드 추가
		$tree_data = $this->add_org_nodes_to_tree($category_tree, $visible_categories);

		header('Content-Type: application/json; charset=utf-8');
		echo json_encode($tree_data, JSON_UNESCAPED_UNICODE);
	}

	/**
	 * 카테고리 트리에 조직 노드 추가
	 */
	private function add_org_nodes_to_tree($category_tree, $visible_categories)
	{
		$result = array();

		foreach ($category_tree as $node) {
			$new_node = $node;

			if (isset($node['data']) && $node['data']['type'] === 'category') {
				// 해당 카테고리에 직접 속한 조직 조회
				$category_idx = $node['data']['category_idx'];
				$orgs = $this->get_orgs_by_category_direct($category_idx);

				// 기존 children이 있으면 재귀 처리
				$children = array();
				if (!empty($node['children'])) {
					$children = $this->add_org_nodes_to_tree($node['children'], $visible_categories);
				}

				// 조직 노드 추가
				foreach ($orgs as $org) {
					$member_count = $this->get_org_member_count($org['org_id']);
					$children[] = array(
						'key' => 'org_' . $org['org_id'],
						'title' => $org['org_name'] . ' (' . $member_count . '명)',
						'folder' => false,
						'icon' => 'bi bi-building',
						'data' => array(
							'type' => 'org',
							'org_id' => $org['org_id'],
							'org_name' => $org['org_name'],
							'member_count' => $member_count
						)
					);
				}

				// 카테고리의 회원 수 재계산 (하위 포함)
				$category_member_count = $this->get_category_member_count($category_idx);
				$new_node['title'] = $node['data']['category_name'] . ' (' . $category_member_count . '명)';
				$new_node['children'] = $children;

			} else if (isset($node['data']) && $node['data']['type'] === 'uncategorized') {
				// 미분류 조직 처리
				$orgs = $this->get_uncategorized_orgs();
				$children = array();

				foreach ($orgs as $org) {
					$member_count = $this->get_org_member_count($org['org_id']);
					$children[] = array(
						'key' => 'org_' . $org['org_id'],
						'title' => $org['org_name'] . ' (' . $member_count . '명)',
						'folder' => false,
						'icon' => 'bi bi-building',
						'data' => array(
							'type' => 'org',
							'org_id' => $org['org_id'],
							'org_name' => $org['org_name'],
							'member_count' => $member_count
						)
					);
				}

				$uncategorized_member_count = $this->get_uncategorized_member_count();
				$new_node['title'] = '미분류 (' . $uncategorized_member_count . '명)';
				$new_node['folder'] = true;
				$new_node['children'] = $children;
			}

			$result[] = $new_node;
		}

		return $result;
	}

	/**
	 * 특정 카테고리에 직접 속한 조직 조회
	 */
	private function get_orgs_by_category_direct($category_idx)
	{
		$this->db->select('org_id, org_name');
		$this->db->from('wb_org');
		$this->db->where('category_idx', $category_idx);
		$this->db->where('del_yn', 'N');
		$this->db->order_by('org_name', 'ASC');
		$query = $this->db->get();
		return $query->result_array();
	}

	/**
	 * 미분류 조직 조회
	 */
	private function get_uncategorized_orgs()
	{
		$this->db->select('org_id, org_name');
		$this->db->from('wb_org');
		$this->db->where('del_yn', 'N');
		$this->db->group_start();
		$this->db->where('category_idx IS NULL');
		$this->db->or_where('category_idx', 0);
		$this->db->group_end();
		$this->db->order_by('org_name', 'ASC');
		$query = $this->db->get();
		return $query->result_array();
	}

	/**
	 * 조직별 회원 수 조회
	 */
	private function get_org_member_count($org_id)
	{
		$this->db->from('wb_member');
		$this->db->where('org_id', $org_id);
		$this->db->where('del_yn', 'N');
		return $this->db->count_all_results();
	}

	/**
	 * 카테고리의 전체 회원 수 조회 (하위 카테고리 포함)
	 */
	private function get_category_member_count($category_idx)
	{
		$category_ids = $this->Org_category_model->get_category_with_descendants_public(array($category_idx));

		if (empty($category_ids)) {
			return 0;
		}

		$this->db->from('wb_member m');
		$this->db->join('wb_org o', 'm.org_id = o.org_id');
		$this->db->where('m.del_yn', 'N');
		$this->db->where('o.del_yn', 'N');
		$this->db->where_in('o.category_idx', $category_ids);

		return $this->db->count_all_results();
	}

	/**
	 * 미분류 조직의 회원 수 조회
	 */
	private function get_uncategorized_member_count()
	{
		$this->db->from('wb_member m');
		$this->db->join('wb_org o', 'm.org_id = o.org_id');
		$this->db->where('m.del_yn', 'N');
		$this->db->where('o.del_yn', 'N');
		$this->db->group_start();
		$this->db->where('o.category_idx IS NULL');
		$this->db->or_where('o.category_idx', 0);
		$this->db->group_end();

		return $this->db->count_all_results();
	}

	/**
	 * 전체 회원 수 조회 (AJAX)
	 */
	public function get_total_member_count()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$visible_categories = $this->get_visible_categories();

		if (!empty($visible_categories)) {
			$category_ids = $this->Org_category_model->get_category_with_descendants_public($visible_categories);

			if (empty($category_ids)) {
				$total_count = 0;
			} else {
				$this->db->from('wb_member m');
				$this->db->join('wb_org o', 'm.org_id = o.org_id');
				$this->db->where('m.del_yn', 'N');
				$this->db->where('o.del_yn', 'N');
				$this->db->where_in('o.category_idx', $category_ids);
				$total_count = $this->db->count_all_results();
			}
		} else {
			$this->db->from('wb_member m');
			$this->db->join('wb_org o', 'm.org_id = o.org_id');
			$this->db->where('m.del_yn', 'N');
			$this->db->where('o.del_yn', 'N');
			$total_count = $this->db->count_all_results();
		}

		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(array('total_count' => $total_count));
	}



	/**
	 * 전체 회원 조회
	 * @param array $visible_categories 접근 가능한 카테고리 목록
	 * @param string $status_tag 관리tag 검색 조건
	 * @param string $keyword 이름/연락처 검색 조건
	 */
	private function get_all_members($visible_categories, $status_tag = '', $keyword = '')
	{
		$this->db->select('m.*, o.org_name, c.category_name');
		$this->db->from('wb_member m');
		$this->db->join('wb_org o', 'm.org_id = o.org_id');
		$this->db->join('wb_org_category c', 'o.category_idx = c.category_idx', 'left');
		$this->db->where('m.del_yn', 'N');
		$this->db->where('o.del_yn', 'N');

		if (!empty($visible_categories)) {
			$category_ids = $this->Org_category_model->get_category_with_descendants_public($visible_categories);
			if (!empty($category_ids)) {
				$this->db->where_in('o.category_idx', $category_ids);
			}
		}

		// 검색 조건 적용
		$this->apply_search_conditions($status_tag, $keyword);

		$this->db->order_by('m.regi_date', 'DESC');
		$query = $this->db->get();
		return $query->result_array();
	}

	/**
	 * 카테고리별 회원 조회 (하위 카테고리 포함)
	 * @param int $category_idx 카테고리 ID
	 * @param string $status_tag 관리tag 검색 조건
	 * @param string $keyword 이름/연락처 검색 조건
	 */
	private function get_category_members($category_idx, $status_tag = '', $keyword = '')
	{
		$category_ids = $this->Org_category_model->get_category_with_descendants_public(array($category_idx));

		if (empty($category_ids)) {
			return array();
		}

		$this->db->select('m.*, o.org_name, c.category_name');
		$this->db->from('wb_member m');
		$this->db->join('wb_org o', 'm.org_id = o.org_id');
		$this->db->join('wb_org_category c', 'o.category_idx = c.category_idx', 'left');
		$this->db->where('m.del_yn', 'N');
		$this->db->where('o.del_yn', 'N');
		$this->db->where_in('o.category_idx', $category_ids);

		// 검색 조건 적용
		$this->apply_search_conditions($status_tag, $keyword);

		$this->db->order_by('m.regi_date', 'DESC');
		$query = $this->db->get();
		return $query->result_array();
	}

	/**
	 * 조직별 회원 조회
	 * @param string $org_id 조직 ID
	 * @param string $status_tag 관리tag 검색 조건
	 * @param string $keyword 이름/연락처 검색 조건
	 */
	private function get_org_members_list($org_id, $status_tag = '', $keyword = '')
	{
		$this->db->select('m.*, o.org_name, c.category_name');
		$this->db->from('wb_member m');
		$this->db->join('wb_org o', 'm.org_id = o.org_id');
		$this->db->join('wb_org_category c', 'o.category_idx = c.category_idx', 'left');
		$this->db->where('m.org_id', $org_id);
		$this->db->where('m.del_yn', 'N');
		$this->db->where('o.del_yn', 'N');

		// 검색 조건 적용
		$this->apply_search_conditions($status_tag, $keyword);

		$this->db->order_by('m.regi_date', 'DESC');
		$query = $this->db->get();
		return $query->result_array();
	}

	/**
	 * 미분류 조직의 회원 조회
	 * @param string $status_tag 관리tag 검색 조건
	 * @param string $keyword 이름/연락처 검색 조건
	 */
	private function get_uncategorized_members($status_tag = '', $keyword = '')
	{
		$this->db->select('m.*, o.org_name, "" as category_name');
		$this->db->from('wb_member m');
		$this->db->join('wb_org o', 'm.org_id = o.org_id');
		$this->db->where('m.del_yn', 'N');
		$this->db->where('o.del_yn', 'N');
		$this->db->group_start();
		$this->db->where('o.category_idx IS NULL');
		$this->db->or_where('o.category_idx', 0);
		$this->db->group_end();

		// 검색 조건 적용
		$this->apply_search_conditions($status_tag, $keyword);

		$this->db->order_by('m.regi_date', 'DESC');
		$query = $this->db->get();
		return $query->result_array();
	}


	/**
	 * 파일 위치: application/controllers/mng/Mng_member.php
	 * 역할: 검색 조건 적용 공통 함수
	 * @param string $status_tag 관리tag 검색 조건
	 * @param string $keyword 이름/연락처 검색 조건
	 */
	private function apply_search_conditions($status_tag = '', $keyword = '')
	{
		// 관리tag 검색
		if (!empty($status_tag)) {
			$this->db->like('m.member_status', $status_tag);
		}

		// 이름 또는 연락처 검색
		if (!empty($keyword)) {
			$this->db->group_start();
			$this->db->like('m.member_name', $keyword);
			$this->db->or_like('m.member_phone', $keyword);
			$this->db->group_end();
		}
	}

	/**
	 * 회원 상세 정보 조회 (AJAX)
	 */
	public function get_member_detail()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$member_idx = $this->input->get('member_idx');

		if (!$member_idx) {
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array('success' => false, 'message' => '회원 ID가 필요합니다.'));
			return;
		}

		$this->db->select('m.*, o.org_name');
		$this->db->from('wb_member m');
		$this->db->join('wb_org o', 'm.org_id = o.org_id', 'left');
		$this->db->where('m.member_idx', $member_idx);
		$query = $this->db->get();
		$member = $query->row_array();

		if (!$member) {
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array('success' => false, 'message' => '회원 정보를 찾을 수 없습니다.'));
			return;
		}

		// 사진 URL 처리
		$photo_url = '/assets/images/photo_no.png';
		if (!empty($member['photo'])) {
			if (strpos($member['photo'], '/uploads/') === false) {
				$photo_url = '/uploads/member_photos/' . $member['org_id'] . '/' . $member['photo'];
			} else {
				$photo_url = $member['photo'];
			}
		}
		$member['photo_url'] = $photo_url;

		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(array(
			'success' => true,
			'data' => $member
		), JSON_UNESCAPED_UNICODE);
	}




	/**
	 * 파일 위치: application/controllers/mng/Mng_member.php
	 * 역할: 회원 상태 변경 (대량 처리 최적화)
	 */
	public function update_member_status()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		// 실행 시간 제한 늘리기
		set_time_limit(300);

		$member_idx_list = $this->input->post('member_idx_list');
		$mode = $this->input->post('mode');

		// 문자열로 전달된 경우 배열로 변환
		if (is_string($member_idx_list)) {
			$member_idx_list = json_decode($member_idx_list, true);
		}

		if (empty($member_idx_list) || !is_array($member_idx_list)) {
			echo json_encode(array(
				'success' => false,
				'message' => '변경할 회원을 선택해주세요.'
			));
			return;
		}

		$affected_count = 0;

		if ($mode === 'replace') {
			// 단일 모드: 태그 전체 교체 (일괄 UPDATE)
			$member_status = trim($this->input->post('member_status'));

			$this->db->where_in('member_idx', $member_idx_list);
			$this->db->update('wb_member', array(
				'member_status' => $member_status,
				'modi_date' => date('Y-m-d H:i:s')
			));

			$affected_count = count($member_idx_list);

		} else if ($mode === 'bulk') {
			// 일괄 모드: 태그 추가/삭제
			$add_tags = $this->input->post('add_tags');
			$remove_tags = $this->input->post('remove_tags');

			if (is_string($add_tags)) {
				$add_tags = json_decode($add_tags, true);
			}
			if (is_string($remove_tags)) {
				$remove_tags = json_decode($remove_tags, true);
			}

			if (!is_array($add_tags)) $add_tags = array();
			if (!is_array($remove_tags)) $remove_tags = array();

			// 대량 처리를 위해 청크 단위로 분할
			$chunks = array_chunk($member_idx_list, 100);

			foreach ($chunks as $chunk) {
				// 현재 청크의 회원 상태 일괄 조회
				$this->db->select('member_idx, member_status');
				$this->db->where_in('member_idx', $chunk);
				$query = $this->db->get('wb_member');
				$members = $query->result_array();

				// 각 회원별 태그 처리
				foreach ($members as $member) {
					$member_idx = $member['member_idx'];

					// 현재 태그 파싱
					$current_tags = array();
					if (!empty($member['member_status'])) {
						$current_tags = array_map('trim', explode(',', $member['member_status']));
						$current_tags = array_filter($current_tags, function($v) { return $v !== ''; });
					}

					// 삭제할 태그 제거
					foreach ($remove_tags as $tag) {
						$tag = trim($tag);
						$key = array_search($tag, $current_tags);
						if ($key !== false) {
							unset($current_tags[$key]);
						}
					}

					// 추가할 태그 추가 (중복 방지)
					foreach ($add_tags as $tag) {
						$tag = trim($tag);
						if ($tag !== '' && !in_array($tag, $current_tags)) {
							$current_tags[] = $tag;
						}
					}

					// 업데이트
					$new_status = implode(',', array_values($current_tags));

					$this->db->where('member_idx', $member_idx);
					$this->db->update('wb_member', array(
						'member_status' => $new_status,
						'modi_date' => date('Y-m-d H:i:s')
					));

					$affected_count++;
				}
			}
		}

		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(array(
			'success' => true,
			'message' => $affected_count . '명의 회원 상태가 변경되었습니다.'
		), JSON_UNESCAPED_UNICODE);
	}


	/**
	 * 파일 위치: application/controllers/mng/Mng_member.php
	 * 역할: 회원 목록 조회 (AJAX) - Model 사용
	 */
	public function get_member_list()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$type = $this->input->get('type');
		$id = $this->input->get('id');

		// 검색 조건 (status_tags는 쉼표 구분 문자열)
		$status_tags = $this->input->get('status_tags');
		$keyword = $this->input->get('keyword');

		$visible_categories = $this->get_visible_categories();
		$members = array();

		switch ($type) {
			case 'all':
				$category_ids = array();
				if (!empty($visible_categories)) {
					$category_ids = $this->Org_category_model->get_category_with_descendants_public($visible_categories);
				}
				$members = $this->Member_model->get_master_members_all($category_ids, $status_tags, $keyword);
				break;

			case 'category':
				if ($id) {
					$category_ids = $this->Org_category_model->get_category_with_descendants_public(array($id));
					$members = $this->Member_model->get_master_members_by_category($category_ids, $status_tags, $keyword);
				}
				break;

			case 'org':
				if ($id) {
					$members = $this->Member_model->get_master_members_by_org($id, $status_tags, $keyword);
				}
				break;

			case 'uncategorized':
				$members = $this->Member_model->get_master_members_uncategorized($status_tags, $keyword);
				break;

			default:
				$category_ids = array();
				if (!empty($visible_categories)) {
					$category_ids = $this->Org_category_model->get_category_with_descendants_public($visible_categories);
				}
				$members = $this->Member_model->get_master_members_all($category_ids, $status_tags, $keyword);
				break;
		}

		// 사진 URL 처리
		$members = $this->Member_model->process_member_photo_urls($members);

		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(array(
			'success' => true,
			'data' => $members
		), JSON_UNESCAPED_UNICODE);
	}

	/**
	 * 파일 위치: application/controllers/mng/Mng_member.php
	 * 역할: 기존 관리tag 태그 목록 조회 (AJAX)
	 */
	public function get_existing_status_tags()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$tags = $this->Member_model->get_existing_status_tags();

		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(array(
			'success' => true,
			'data' => $tags
		), JSON_UNESCAPED_UNICODE);
	}


	/**
	 * 파일 위치: application/controllers/mng/Mng_member.php
	 * 역할: 조직변경용 조직 목록 조회
	 */
	public function get_org_list_for_change()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$visible_categories = $this->get_visible_categories();

		// 조직 목록 조회
		$orgs = $this->Org_model->get_org_list_with_category($visible_categories);

		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(array(
			'success' => true,
			'data' => $orgs
		), JSON_UNESCAPED_UNICODE);
	}
	/**
	 * 파일 위치: application/controllers/mng/Mng_member.php
	 * 역할: 회원을 다른 조직으로 복사
	 */
	public function copy_members_to_org()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$target_org_id = $this->input->post('target_org_id');
		$member_idx_list_json = $this->input->post('member_idx_list');

		if (!$target_org_id || !$member_idx_list_json) {
			echo json_encode(array(
				'success' => false,
				'message' => '필수 정보가 누락되었습니다.'
			));
			return;
		}

		$member_idx_list = json_decode($member_idx_list_json, true);
		if (!is_array($member_idx_list) || empty($member_idx_list)) {
			echo json_encode(array(
				'success' => false,
				'message' => '복사할 회원을 선택해주세요.'
			));
			return;
		}

		// 대상 조직 존재 여부 확인
		$target_org = $this->Org_model->get_org_detail_by_id($target_org_id);
		if (!$target_org) {
			echo json_encode(array(
				'success' => false,
				'message' => '대상 조직을 찾을 수 없습니다.'
			));
			return;
		}

		// 트랜잭션 시작
		$this->db->trans_start();

		$success_count = 0;
		$error_count = 0;

		foreach ($member_idx_list as $member_idx) {
			// 원본 회원 정보 조회
			$original_member = $this->Member_model->get_member_by_idx($member_idx);

			if (!$original_member) {
				$error_count++;
				continue;
			}

			// 복사할 회원 데이터 준비
			$new_member_data = array(
				'org_id' => $target_org_id,
				'member_name' => $original_member['member_name'],
				'member_nick' => $original_member['member_nick'],
				'member_sex' => $original_member['member_sex'],
				'member_phone' => $original_member['member_phone'],
				'member_birth' => $original_member['member_birth'],
				'member_address' => $original_member['member_address'],
				'member_address_detail' => $original_member['member_address_detail'],
				'member_status' => $original_member['member_status'],
				'area_idx' => null, // 새 조직에서는 미분류로 시작
				'regi_date' => date('Y-m-d H:i:s'),
				'del_yn' => 'N',
				'leader_yn' => 'N',
				'new_yn' => 'Y',
				'grade' => 0
			);

			// 회원 추가
			$result = $this->Member_model->add_member($new_member_data);

			if ($result) {
				$success_count++;
			} else {
				$error_count++;
			}
		}

		$this->db->trans_complete();

		if ($this->db->trans_status() === FALSE) {
			echo json_encode(array(
				'success' => false,
				'message' => '회원 복사 중 오류가 발생했습니다.'
			));
			return;
		}

		$target_org_name = $target_org['org_name'];
		$message = "{$success_count}명의 회원이 '{$target_org_name}' 조직으로 복사되었습니다.";

		if ($error_count > 0) {
			$message .= " (실패: {$error_count}명)";
		}

		echo json_encode(array(
			'success' => true,
			'message' => $message,
			'success_count' => $success_count,
			'error_count' => $error_count
		));
	}

	/**
	 * 파일 위치: application/controllers/mng/Mng_member.php
	 * 역할: 그룹(카테고리) 목록 조회 - 계층 구조
	 */
	public function get_category_list_for_change()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$visible_categories = $this->get_visible_categories();

		// 카테고리 목록 조회 (계층 구조)
		if (!empty($visible_categories)) {
			$categories = $this->Org_category_model->get_categories_for_select_filtered($visible_categories);
		} else {
			$categories = $this->Org_category_model->get_categories_for_select();
		}

		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(array(
			'success' => true,
			'data' => $categories
		), JSON_UNESCAPED_UNICODE);
	}

	/**
	 * 파일 위치: application/controllers/mng/Mng_member.php
	 * 역할: 특정 그룹(카테고리)의 조직 목록 조회 (하위 카테고리 포함) - 디버깅 추가
	 */
	public function get_orgs_by_category()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$category_idx = $this->input->get('category_idx');

		// 디버깅 로그
		log_message('debug', 'get_orgs_by_category - category_idx: ' . $category_idx);

		if (!$category_idx || $category_idx === '') {
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array(
				'success' => false,
				'message' => '그룹을 선택해주세요.'
			));
			return;
		}

		// 정수로 변환
		$category_idx = intval($category_idx);

		if ($category_idx <= 0) {
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array(
				'success' => false,
				'message' => '유효하지 않은 그룹입니다.'
			));
			return;
		}

		// 하위 카테고리 포함 조직 목록 조회
		$orgs = $this->Org_model->get_orgs_by_category_with_children($category_idx);

		// 디버깅 로그
		log_message('debug', 'get_orgs_by_category - orgs count: ' . count($orgs));

		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(array(
			'success' => true,
			'data' => $orgs
		), JSON_UNESCAPED_UNICODE);
	}


	/**
	 * 파일 위치: application/controllers/mng/Mng_member.php
	 * 역할: 회원을 여러 조직으로 복사 (일괄 처리)
	 */
	public function copy_members_to_orgs()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$copy_data_json = $this->input->post('copy_data');

		if (!$copy_data_json) {
			echo json_encode(array(
				'success' => false,
				'message' => '복사할 데이터가 없습니다.'
			));
			return;
		}

		$copy_data = json_decode($copy_data_json, true);

		if (!is_array($copy_data) || empty($copy_data)) {
			echo json_encode(array(
				'success' => false,
				'message' => '유효하지 않은 데이터 형식입니다.'
			));
			return;
		}

		// 트랜잭션 시작
		$this->db->trans_start();

		$total_success = 0;
		$total_error = 0;
		$org_results = array();

		foreach ($copy_data as $item) {
			$target_org_id = $item['target_org_id'];
			$member_idx_list = $item['member_idx_list'];

			if (!$target_org_id || !is_array($member_idx_list) || empty($member_idx_list)) {
				continue;
			}

			// 대상 조직 존재 여부 확인 (수정됨)
			$target_org = $this->Org_model->get_org_detail_by_id($target_org_id);
			if (!$target_org) {
				$total_error += count($member_idx_list);
				continue;
			}

			$success_count = 0;
			$error_count = 0;

			foreach ($member_idx_list as $member_idx) {
				// 원본 회원 정보 조회
				$original_member = $this->Member_model->get_member_by_idx($member_idx);

				if (!$original_member) {
					$error_count++;
					continue;
				}

				// 복사할 회원 데이터 준비
				$new_member_data = array(
					'org_id' => $target_org_id,
					'member_name' => $original_member['member_name'],
					'member_nick' => $original_member['member_nick'],
					'member_sex' => $original_member['member_sex'],
					'member_phone' => $original_member['member_phone'],
					'member_birth' => $original_member['member_birth'],
					'member_address' => $original_member['member_address'],
					'member_address_detail' => $original_member['member_address_detail'],
					'member_status' => $original_member['member_status'],
					'area_idx' => null, // 새 조직에서는 미분류로 시작
					'regi_date' => date('Y-m-d H:i:s'),
					'del_yn' => 'N',
					'leader_yn' => 'N',
					'new_yn' => 'Y',
					'grade' => 0
				);

				// 회원 추가
				$result = $this->Member_model->add_member($new_member_data);

				if ($result) {
					$success_count++;
				} else {
					$error_count++;
				}
			}

			$total_success += $success_count;
			$total_error += $error_count;

			if ($success_count > 0) {
				$org_results[] = $target_org['org_name'] . ': ' . $success_count . '명';
			}
		}

		$this->db->trans_complete();

		if ($this->db->trans_status() === FALSE) {
			echo json_encode(array(
				'success' => false,
				'message' => '회원 복사 중 오류가 발생했습니다.'
			));
			return;
		}

		$message = "총 {$total_success}명의 회원이 복사되었습니다.";
		if (!empty($org_results)) {
			$message .= ' (' . implode(', ', $org_results) . ')';
		}
		if ($total_error > 0) {
			$message .= " (실패: {$total_error}명)";
		}

		echo json_encode(array(
			'success' => true,
			'message' => $message,
			'total_success' => $total_success,
			'total_error' => $total_error
		));
	}



}
