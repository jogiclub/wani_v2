<?php
/**
 * 파일 위치: application/models/Homepage_menu_model.php
 * 역할: 홈페이지 메뉴 설정 데이터 처리 모델
 */
defined('BASEPATH') or exit('No direct script access allowed');

class Homepage_menu_model extends CI_Model
{
	public function __construct()
	{
		parent::__construct();
		$this->load->database();
	}

	/**
	 * 메뉴 목록 조회
	 */
	public function get_menu_list($org_id)
	{
		$this->db->select('homepage_menu');
		$this->db->from('wb_org');
		$this->db->where('org_id', $org_id);
		$query = $this->db->get();
		$result = $query->row_array();

		if ($result && !empty($result['homepage_menu'])) {
			$menu = json_decode($result['homepage_menu'], true);
			return is_array($menu) ? $menu : [];
		}

		return [];
	}

	/**
	 * 메뉴 저장
	 */
	public function save_menu($org_id, $menu_json)
	{
		$data = [
			'homepage_menu' => $menu_json,
			'modi_date' => date('Y-m-d H:i:s')
		];

		$this->db->where('org_id', $org_id);
		return $this->db->update('wb_org', $data);
	}

	/**
	 * 링크 정보 조회
	 */
	public function get_link_info($org_id, $menu_id)
	{
		$this->db->where('org_id', $org_id);
		$this->db->where('menu_id', $menu_id);
		$query = $this->db->get('wb_homepage_link');
		return $query->row_array();
	}

	/**
	 * 링크 정보 저장
	 */
	public function save_link($data)
	{
		$existing = $this->get_link_info($data['org_id'], $data['menu_id']);

		if ($existing) {
			$this->db->where('org_id', $data['org_id']);
			$this->db->where('menu_id', $data['menu_id']);
			return $this->db->update('wb_homepage_link', $data);
		} else {
			return $this->db->insert('wb_homepage_link', $data);
		}
	}

	/**
	 * 페이지 정보 조회
	 */
	public function get_page_info($org_id, $menu_id)
	{
		$this->db->where('org_id', $org_id);
		$this->db->where('menu_id', $menu_id);
		$query = $this->db->get('wb_homepage_page');
		return $query->row_array();
	}

	/**
	 * 페이지 정보 저장
	 */
	public function save_page($data)
	{
		$existing = $this->get_page_info($data['org_id'], $data['menu_id']);

		if ($existing) {
			$this->db->where('org_id', $data['org_id']);
			$this->db->where('menu_id', $data['menu_id']);
			return $this->db->update('wb_homepage_page', $data);
		} else {
			return $this->db->insert('wb_homepage_page', $data);
		}
	}

	/**
	 * 페이지 정보 삭제
	 */
	public function delete_page($org_id, $menu_id)
	{
		$this->db->where('org_id', $org_id);
		$this->db->where('menu_id', $menu_id);
		return $this->db->delete('wb_homepage_page');
	}

	/**
	 * 게시판 목록 조회
	 */
	public function get_board_list($org_id, $menu_id, $search_keyword = '', $page = 1, $limit = 5)
	{
		$offset = ($page - 1) * $limit;

		$this->db->where('org_id', $org_id);
		$this->db->where('menu_id', $menu_id);
		$this->db->where('del_yn', 'N');

		if (!empty($search_keyword)) {
			$this->db->group_start();
			$this->db->like('board_title', $search_keyword);
			$this->db->or_like('board_content', $search_keyword);
			$this->db->or_like('writer_name', $search_keyword);
			$this->db->group_end();
		}

		// total 계산 전에 쿼리 복제
		$total_query = clone $this->db;
		$total = $total_query->count_all_results('wb_homepage_board');

		// 목록 조회
		$this->db->select('idx, board_title, view_count, writer_name, modifier_name, reg_date, modi_date, youtube_url, file_path');
		$this->db->order_by('idx', 'DESC');
		$this->db->limit($limit, $offset);
		$query = $this->db->get('wb_homepage_board');

		return [
			'list' => $query->result_array(),
			'total' => $total
		];
	}

	/**
	 * 게시글 상세 조회
	 */
	public function get_board_detail($idx)
	{
		$this->db->where('idx', $idx);
		$query = $this->db->get('wb_homepage_board');

		$board = $query->row_array();

		if ($board) {
			// 조회수 증가는 하지 않음 (관리자 페이지)
			// $this->db->set('view_count', 'view_count+1', FALSE);
			// $this->db->where('idx', $idx);
			// $this->db->update('wb_homepage_board');
		}

		return $board;
	}

	/**
	 * 게시글 저장
	 */
	public function save_board($data)
	{
		if (isset($data['idx']) && !empty($data['idx'])) {
			$idx = $data['idx'];
			unset($data['idx']);
			$this->db->where('idx', $idx);
			return $this->db->update('wb_homepage_board', $data);
		} else {
			return $this->db->insert('wb_homepage_board', $data);
		}
	}

	/**
	 * 게시글 삭제
	 */
	public function delete_board($idx)
	{
		$this->db->where('idx', $idx);
		return $this->db->delete('wb_homepage_board');
	}

	/**
	 * 메뉴의 모든 게시글 조회
	 */
	public function get_all_boards_by_menu($org_id, $menu_id)
	{
		$this->db->select('idx, file_path, board_content');
		$this->db->where('org_id', $org_id);
		$this->db->where('menu_id', $menu_id);
		$query = $this->db->get('wb_homepage_board');
		return $query->result_array();
	}

	/**
	 * 메뉴의 모든 게시글 삭제
	 */
	public function delete_all_boards_by_menu($org_id, $menu_id)
	{
		$this->db->where('org_id', $org_id);
		$this->db->where('menu_id', $menu_id);
		return $this->db->delete('wb_homepage_board');
	}

	/**
	 * 링크 정보 삭제
	 */
	public function delete_link($org_id, $menu_id)
	{
		$this->db->where('org_id', $org_id);
		$this->db->where('menu_id', $menu_id);
		return $this->db->delete('wb_homepage_link');
	}





}
