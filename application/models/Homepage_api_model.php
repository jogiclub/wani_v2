<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Homepage_api_model extends CI_Model
{
	public function __construct()
	{
		parent::__construct();
		$this->load->database();
	}


	/**
	 * 조직 코드로 메뉴 데이터 조회
	 */
	public function get_menu_by_org_code($org_code)
	{
		$this->db->select('homepage_menu');
		$this->db->from('wb_org');
		$this->db->where('org_code', $org_code);
		$this->db->where('del_yn', 'N');
		$query = $this->db->get();

		$result = $query->row_array();

		if ($result && !empty($result['homepage_menu'])) {
			$menu = json_decode($result['homepage_menu'], true);
			return is_array($menu) ? $menu : [];
		}

		return [];
	}

	/**
	 * 조직 코드와 메뉴 ID로 페이지 내용 조회
	 */
	public function get_page_by_org_code_and_menu_id($org_code, $menu_id)
	{
		$org_id = $this->get_org_id_by_code($org_code);

		if ($org_id === false) {
			return false;
		}

		$this->db->select('page_content, reg_date, modi_date');
		$this->db->from('wb_homepage_page');
		$this->db->where('org_id', $org_id);
		$this->db->where('menu_id', $menu_id);
		$query = $this->db->get();

		return $query->row_array();
	}

	/**
	 * 조직 코드와 메뉴 ID로 링크 정보 조회
	 */
	public function get_link_by_org_code_and_menu_id($org_code, $menu_id)
	{
		$org_id = $this->get_org_id_by_code($org_code);

		if ($org_id === false) {
			return false;
		}

		$this->db->select('link_url, link_target');
		$this->db->from('wb_homepage_link');
		$this->db->where('org_id', $org_id);
		$this->db->where('menu_id', $menu_id);
		$query = $this->db->get();

		return $query->row_array();
	}

	/**
	 * 조직 코드와 메뉴 ID로 게시판 목록 조회 (검색 포함)
	 */
	public function get_board_list_by_org_code_and_menu_id($org_code, $menu_id, $search_keyword = '', $page = 1, $limit = 20)
	{
		$org_id = $this->get_org_id_by_code($org_code);

		if ($org_id === false) {
			return false;
		}

		$offset = ($page - 1) * $limit;

		$this->db->where('org_id', $org_id);
		$this->db->where('menu_id', $menu_id);

		if (!empty($search_keyword)) {
			$this->db->group_start();
			$this->db->like('board_title', $search_keyword);
			$this->db->or_like('board_content', $search_keyword);
			$this->db->or_like('writer_name', $search_keyword);
			$this->db->group_end();
		}

		$total_query = clone $this->db;
		$total = $total_query->count_all_results('wb_homepage_board');

		// file_path 필드 추가
		$this->db->select('idx, board_title, view_count, writer_name, reg_date, modi_date, file_path');
		$this->db->order_by('reg_date', 'DESC');
		$this->db->limit($limit, $offset);
		$query = $this->db->get('wb_homepage_board');

		return [
			'list' => $query->result_array(),
			'total' => $total
		];
	}

	/**
	 * 조직 코드와 게시글 번호로 게시글 상세 조회
	 */
	public function get_board_detail_by_org_code($org_code, $idx)
	{
		$org_id = $this->get_org_id_by_code($org_code);

		if ($org_id === false) {
			return false;
		}

		$this->db->where('org_id', $org_id);
		$this->db->where('idx', $idx);
		$query = $this->db->get('wb_homepage_board');

		$board = $query->row_array();

		if ($board) {
			$this->db->set('view_count', 'view_count+1', FALSE);
			$this->db->where('idx', $idx);
			$this->db->update('wb_homepage_board');
		}

		return $board;
	}

	/**
	 * 조직 코드로 조직 기본 정보 조회 (홈페이지 설정 포함)
	 */
	public function get_org_info_by_org_code($org_code)
	{
		$this->db->select('org_id, org_code, org_name, homepage_setting');
		$this->db->from('wb_org');
		$this->db->where('org_code', $org_code);
		$this->db->where('del_yn', 'N');
		$query = $this->db->get();

		$result = $query->row_array();

		if ($result && !empty($result['homepage_setting'])) {
			$result['homepage_setting'] = json_decode($result['homepage_setting'], true);
		}

		return $result;
	}


	/**
	 * 게시판 블록용 게시물 목록 조회
	 */
	public function get_board_list_for_block($org_code, $menu_id, $limit = 5)
	{
		$this->db->select('hb.idx, hb.board_title, hb.reg_date');
		$this->db->from('wb_homepage_board hb');
		$this->db->join('wb_org org', 'org.org_id = hb.org_id');
		$this->db->where('org.org_code', $org_code);
		$this->db->where('hb.menu_id', $menu_id);
		$this->db->where('hb.del_yn', 'N');
		$this->db->order_by('hb.idx', 'DESC');
		$this->db->limit($limit);

		$query = $this->db->get();

		if ($query === false) {
			log_message('error', 'get_board_list_for_block 쿼리 실행 실패: ' . $this->db->error()['message']);
			return [];
		}

		return $query->result_array();
	}

	/**
	 * 메뉴 정보 조회
	 */
	public function get_menu_info($org_code, $menu_id)
	{
		$this->db->select('homepage_menu');
		$this->db->from('wb_org');
		$this->db->where('org_code', $org_code);

		$query = $this->db->get();

		if ($query === false || $query->num_rows() === 0) {
			log_message('error', 'get_menu_info 조회 실패: org_code=' . $org_code);
			return null;
		}

		$row = $query->row_array();
		$menu_json = json_decode($row['homepage_menu'], true);

		if (!$menu_json || !is_array($menu_json)) {
			log_message('error', 'get_menu_info JSON 파싱 실패: org_code=' . $org_code);
			return null;
		}

		// 메뉴 트리에서 해당 menu_id 찾기
		return $this->find_menu_by_id($menu_json, $menu_id);
	}

	/**
	 * 메뉴 트리에서 특정 메뉴 찾기 (재귀)
	 */
	private function find_menu_by_id($menus, $menu_id)
	{
		if (!is_array($menus)) {
			return null;
		}

		foreach ($menus as $menu) {
			if (isset($menu['id']) && $menu['id'] == $menu_id) {
				return ['menu_name' => $menu['name']];
			}

			if (isset($menu['children']) && is_array($menu['children']) && !empty($menu['children'])) {
				$found = $this->find_menu_by_id($menu['children'], $menu_id);
				if ($found) {
					return $found;
				}
			}
		}

		return null;
	}


	/**
	 * 유튜브 URL이 있는 게시판 게시물 조회 (게시판 블록용)
	 */
	public function get_youtube_board_list($org_code, $menu_id, $limit = 10)
	{
		$this->db->select('hb.idx, hb.board_title, hb.youtube_url, hb.reg_date');
		$this->db->from('wb_homepage_board hb');
		$this->db->join('wb_org org', 'org.org_id = hb.org_id');
		$this->db->where('org.org_code', $org_code);
		$this->db->where('hb.menu_id', $menu_id);
		$this->db->where('hb.del_yn', 'N');
		$this->db->where('hb.youtube_url IS NOT NULL');
		$this->db->where('hb.youtube_url !=', '');
		$this->db->order_by('hb.reg_date', 'DESC');
		$this->db->limit($limit);

		$query = $this->db->get();

		if ($query === false) {
			log_message('error', 'get_youtube_board_list 쿼리 실행 실패: ' . $this->db->error()['message']);
			return [];
		}

		return $query->result_array();
	}



	/**
	 * 파일 위치: application/models/Homepage_api_model.php
	 * 역할: get_image_board_list 함수 디버깅 강화
	 */

	public function get_image_board_list($org_code, $menu_id, $limit = 10)
	{
		$org_id = $this->get_org_id_by_code($org_code);

		if ($org_id === false) {
			return [];
		}

		$this->db->select('hb.idx, hb.board_title, hb.reg_date, hb.file_path');
		$this->db->from('wb_homepage_board hb');
		$this->db->where('hb.org_id', $org_id);
		$this->db->where('hb.menu_id', $menu_id);
		$this->db->where('hb.del_yn', 'N');
		$this->db->where('hb.file_path IS NOT NULL');
		$this->db->where('hb.file_path !=', '');
		$this->db->where('hb.file_path !=', '[]');
		$this->db->order_by('hb.idx', 'DESC');
		$this->db->limit($limit * 2);

		$query = $this->db->get();

		if ($query === false) {
			log_message('error', 'get_image_board_list 쿼리 실행 실패: ' . $this->db->error()['message']);
			return [];
		}

		$results = $query->result_array();

		// 쿼리 결과 전체 로그
		log_message('debug', '[get_image_board_list] === 쿼리 결과 시작 ===');
		foreach ($results as $idx => $post) {
			log_message('debug', '[get_image_board_list] Query Result #' . $idx . ' - idx=' . $post['idx'] . ', title=' . $post['board_title']);
		}
		log_message('debug', '[get_image_board_list] === 쿼리 결과 끝 ===');

		$filtered_results = [];
		$added_idx = [];

		foreach ($results as $post) {
			log_message('debug', '[get_image_board_list] 처리 중 - idx=' . $post['idx'] . ', 이미 추가됨=' . (in_array($post['idx'], $added_idx) ? 'YES' : 'NO'));

			// 이미 추가된 게시물은 스킵
			if (in_array($post['idx'], $added_idx)) {
				log_message('debug', '[get_image_board_list] !!! 중복 스킵 !!! - idx=' . $post['idx']);
				continue;
			}

			if (!empty($post['file_path'])) {
				$files = json_decode($post['file_path'], true);

				if (is_array($files) && !empty($files)) {
					$has_image = false;
					foreach ($files as $file) {
						// type이 "image" 또는 "image/"로 시작하는지 체크
						if (isset($file['type']) && ($file['type'] === 'image' || strpos($file['type'], 'image/') === 0)) {
							$has_image = true;
							log_message('debug', '[get_image_board_list] 이미지 발견 - idx=' . $post['idx'] . ', type=' . $file['type']);
							break;
						}
					}

					if ($has_image) {
						$filtered_results[] = $post;
						$added_idx[] = $post['idx'];
						log_message('debug', '[get_image_board_list] >>> 추가 완료 <<< - idx=' . $post['idx'] . ', 현재 개수=' . count($filtered_results));
					}
				}
			}

			if (count($filtered_results) >= $limit) {
				log_message('debug', '[get_image_board_list] 목표 개수 도달, 중단');
				break;
			}
		}

		log_message('debug', '[get_image_board_list] === 최종 결과 ===');
		log_message('debug', '[get_image_board_list] 필터링 후 게시물 수: ' . count($filtered_results));
		log_message('debug', '[get_image_board_list] 추가된 idx 목록: ' . json_encode($added_idx));

		foreach ($filtered_results as $idx => $post) {
			log_message('debug', '[get_image_board_list] Final #' . $idx . ' - idx=' . $post['idx'] . ', title=' . $post['board_title']);
		}

		return array_slice($filtered_results, 0, $limit);
	}


	/**
	 * 메뉴 ID로 메뉴 정보 조회 (이름, 카테고리, 부모 메뉴)
	 */
	public function get_menu_info_by_id($org_code, $menu_id)
	{
		$this->db->select('homepage_menu');
		$this->db->from('wb_org');
		$this->db->where('org_code', $org_code);
		$this->db->where('del_yn', 'N');

		$query = $this->db->get();

		if ($query === false || $query->num_rows() === 0) {
			log_message('error', 'get_menu_info_by_id 조회 실패: org_code=' . $org_code);
			return null;
		}

		$row = $query->row_array();
		$menu_json = json_decode($row['homepage_menu'], true);

		if (!$menu_json || !is_array($menu_json)) {
			log_message('error', 'get_menu_info_by_id JSON 파싱 실패: org_code=' . $org_code);
			return null;
		}

		// 메뉴 트리에서 해당 menu_id 찾기
		return $this->find_menu_with_category($menu_json, $menu_id);
	}


	/**
	 * 메뉴 트리에서 특정 메뉴 찾기 (카테고리 정보 포함) - 재귀
	 */
	private function find_menu_with_category($menus, $menu_id, $parent_name = '', $category_name = '')
	{
		if (!is_array($menus)) {
			return null;
		}

		foreach ($menus as $menu) {
			// 현재 메뉴가 대상인지 확인
			if (isset($menu['id']) && $menu['id'] == $menu_id) {
				return [
					'menu_name' => $menu['name'] ?? '',
					'category_name' => $category_name,
					'parent_menu_name' => $parent_name
				];
			}

			// 자식 메뉴가 있으면 재귀 탐색
			if (isset($menu['children']) && is_array($menu['children']) && !empty($menu['children'])) {
				// 부모가 없으면 현재 메뉴가 카테고리
				$next_category = empty($parent_name) ? ($menu['name'] ?? '') : $category_name;
				$next_parent = $menu['name'] ?? '';

				$found = $this->find_menu_with_category($menu['children'], $menu_id, $next_parent, $next_category);
				if ($found) {
					return $found;
				}
			}
		}

		return null;
	}
	/**
	 * 파일 위치: application/models/Homepage_api_model.php
	 * 역할: 회원 확인 함수 (이름과 휴대폰번호로 조직 회원인지 확인)
	 */
	public function verify_member($org_code, $member_name, $member_phone)
	{
		$org_id = $this->get_org_id_by_code($org_code);

		if ($org_id === false) {
			return [
				'success' => false,
				'message' => '조직 정보를 찾을 수 없습니다.',
				'data' => ['is_member' => false]
			];
		}

		// 휴대폰번호 정규화 (하이픈 제거)
		$member_phone = preg_replace('/[^0-9]/', '', $member_phone);

		$this->db->select('member_idx, member_name, member_phone');
		$this->db->from('wb_member');
		$this->db->where('org_id', $org_id);
		$this->db->where('member_name', $member_name);
		$this->db->where('del_yn', 'N');

		$query = $this->db->get();

		if ($query->num_rows() > 0) {
			$members = $query->result_array();

			// 휴대폰번호 일치 확인
			foreach ($members as $member) {
				$db_phone = preg_replace('/[^0-9]/', '', $member['member_phone']);
				if ($db_phone === $member_phone) {
					return [
						'success' => true,
						'message' => '회원 확인 완료',
						'data' => [
							'is_member' => true,
							'member_idx' => $member['member_idx']
						]
					];
				}
			}
		}

		return [
			'success' => true,
			'message' => '회원이 아닙니다',
			'data' => ['is_member' => false]
		];
	}

	/**
	 * 파일 위치: application/models/Homepage_api_model.php
	 * 역할: 게시글 저장 함수 (프론트엔드용)
	 */
	public function save_board($data)
	{
		if (isset($data['idx']) && !empty($data['idx'])) {
			// 수정
			$idx = $data['idx'];
			unset($data['idx']);
			$this->db->where('idx', $idx);
			return $this->db->update('wb_homepage_board', $data);
		} else {
			// 신규 등록
			return $this->db->insert('wb_homepage_board', $data);
		}
	}

	/**
	 * 파일 위치: application/models/Homepage_api_model.php
	 * 역할: org_code로 org_id 조회 (public으로 변경)
	 */
	public function get_org_id_by_code($org_code)
	{
		$this->db->select('org_id');
		$this->db->from('wb_org');
		$this->db->where('org_code', $org_code);
		$this->db->where('del_yn', 'N');
		$query = $this->db->get();

		$result = $query->row_array();
		return $result ? $result['org_id'] : false;
	}
}
