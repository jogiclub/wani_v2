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
}
