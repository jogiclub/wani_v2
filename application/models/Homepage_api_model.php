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
	 * 조직 코드로 조직 ID 조회
	 */
	private function get_org_id_by_code($org_code)
	{
		$this->db->select('org_id');
		$this->db->from('wb_org');
		$this->db->where('org_code', $org_code);
		$this->db->where('del_yn', 'N');
		$query = $this->db->get();

		$result = $query->row_array();
		return $result ? $result['org_id'] : false;
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
	 * 조직 코드와 메뉴 ID로 게시판 목록 조회
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

		$this->db->select('idx, board_title, view_count, writer_name, reg_date, modi_date');
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

}
