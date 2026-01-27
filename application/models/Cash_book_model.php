<?php
/**
 * 파일 위치: application/models/Cash_book_model.php
 * 역할: 현금출납 장부 및 계정과목 데이터 처리 모델 (JSON 기반)
 */
defined('BASEPATH') or exit('No direct script access allowed');

class Cash_book_model extends CI_Model
{
	public function __construct()
	{
		parent::__construct();
		$this->load->database();
	}

	/**
	 * 장부 목록 조회
	 */
	public function get_book_list($org_id)
	{
		$this->db->select('book_idx, book_name, fiscal_base_month, is_active, regi_date');
		$this->db->from('wb_cash_book');
		$this->db->where('org_id', $org_id);
		$this->db->where('del_yn', 'N');
		$this->db->order_by('regi_date', 'ASC');
		$query = $this->db->get();
		return $query->result_array();
	}

	/**
	 * 장부 상세 조회
	 */
	public function get_book($book_idx)
	{
		$this->db->where('book_idx', $book_idx);
		$this->db->where('del_yn', 'N');
		$query = $this->db->get('wb_cash_book');
		return $query->row_array();
	}

	/**
	 * 장부 추가
	 */
	public function add_book($data)
	{
		$this->db->insert('wb_cash_book', $data);
		return $this->db->insert_id();
	}

	/**
	 * 장부 수정
	 */
	public function update_book($book_idx, $data)
	{
		$this->db->where('book_idx', $book_idx);
		return $this->db->update('wb_cash_book', $data);
	}

	/**
	 * 장부 삭제 (소프트 삭제)
	 */
	public function delete_book($book_idx)
	{
		$this->db->where('book_idx', $book_idx);
		return $this->db->update('wb_cash_book', array('del_yn' => 'Y'));
	}

	/**
	 * 파일 위치: application/models/Cash_book_model.php
	 * 역할: JSON 계정 데이터를 Fancytree 형식으로 변환 (수정)
	 */
	public function convert_to_fancytree($accounts)
	{
		$level_names = array(1 => '관', 2 => '항', 3 => '목', 4 => '세목', 5 => '세세목');
		$tree = array();

		if (empty($accounts)) {
			return $tree;
		}

		foreach ($accounts as $account) {
			$level = isset($account['level']) ? intval($account['level']) : 1;
			$level_name = isset($level_names[$level]) ? $level_names[$level] : '';

			$node = array(
				'key' => 'account_' . $account['id'],
				'title' => $account['code'] . '. ' . $account['name'],
				'expanded' => true,
				'data' => array(
					'id' => $account['id'],
					'code' => $account['code'],
					'name' => $account['name'],
					'level' => $level,
					'level_name' => $level_name
				)
			);

			if (!empty($account['children'])) {
				$node['folder'] = true;
				$node['children'] = $this->convert_to_fancytree($account['children']);
			}

			$tree[] = $node;
		}

		return $tree;
	}

	/**
	 * 계정 추가
	 */
	public function add_account($book_idx, $account_type, $parent_id, $account_name, $user_id)
	{
		$book = $this->get_book($book_idx);
		if (!$book) {
			return array('success' => false, 'message' => '장부를 찾을 수 없습니다.');
		}

		$field = $account_type === 'income' ? 'income_accounts' : 'expense_accounts';
		$accounts_data = json_decode($book[$field], true);

		if (!$accounts_data) {
			$accounts_data = array('last_id' => 0, 'accounts' => array());
		}

		// 새 ID 생성
		$new_id = $accounts_data['last_id'] + 1;
		$accounts_data['last_id'] = $new_id;

		// 부모 계정 찾기 및 새 계정 추가
		if (empty($parent_id)) {
			// 최상위 계정 추가
			$new_code = $this->generate_code($accounts_data['accounts']);
			$new_account = array(
				'id' => $new_id,
				'code' => $new_code,
				'name' => $account_name,
				'level' => 1,
				'children' => array()
			);
			$accounts_data['accounts'][] = $new_account;
		} else {
			// 하위 계정 추가
			$result = $this->add_child_account($accounts_data['accounts'], $parent_id, $new_id, $account_name);
			if (!$result['success']) {
				return $result;
			}
			$accounts_data['accounts'] = $result['accounts'];
		}

		// DB 업데이트
		$update_data = array(
			$field => json_encode($accounts_data, JSON_UNESCAPED_UNICODE),
			'modi_user_id' => $user_id
		);

		$this->db->where('book_idx', $book_idx);
		$this->db->update('wb_cash_book', $update_data);

		return array('success' => true, 'id' => $new_id);
	}

	/**
	 * 하위 계정 추가 (재귀)
	 */
	private function add_child_account(&$accounts, $parent_id, $new_id, $account_name)
	{
		foreach ($accounts as &$account) {
			if ($account['id'] == $parent_id) {
				// 레벨 체크 (최대 5레벨)
				if ($account['level'] >= 5) {
					return array('success' => false, 'message' => '더 이상 하위 계정을 생성할 수 없습니다.');
				}

				if (!isset($account['children'])) {
					$account['children'] = array();
				}

				$new_code = $this->generate_child_code($account['code'], $account['children']);
				$new_account = array(
					'id' => $new_id,
					'code' => $new_code,
					'name' => $account_name,
					'level' => $account['level'] + 1,
					'children' => array()
				);
				$account['children'][] = $new_account;

				return array('success' => true, 'accounts' => $accounts);
			}

			if (!empty($account['children'])) {
				$result = $this->add_child_account($account['children'], $parent_id, $new_id, $account_name);
				if ($result['success']) {
					$account['children'] = $result['accounts'];
					return array('success' => true, 'accounts' => $accounts);
				}
			}
		}

		return array('success' => false, 'message' => '상위 계정을 찾을 수 없습니다.');
	}

	/**
	 * 최상위 코드 생성
	 */
	private function generate_code($accounts)
	{
		$max_code = 0;
		foreach ($accounts as $account) {
			$code_num = intval($account['code']);
			if ($code_num > $max_code) {
				$max_code = $code_num;
			}
		}
		return str_pad($max_code + 1, 2, '0', STR_PAD_LEFT);
	}

	/**
	 * 하위 코드 생성
	 */
	private function generate_child_code($parent_code, $siblings)
	{
		$max_suffix = 0;
		foreach ($siblings as $sibling) {
			$parts = explode('-', $sibling['code']);
			$suffix = intval(end($parts));
			if ($suffix > $max_suffix) {
				$max_suffix = $suffix;
			}
		}
		return $parent_code . '-' . str_pad($max_suffix + 1, 2, '0', STR_PAD_LEFT);
	}

	/**
	 * 계정명 변경
	 */
	public function update_account_name($book_idx, $account_type, $account_id, $account_name, $user_id)
	{
		$book = $this->get_book($book_idx);
		if (!$book) {
			return false;
		}

		$field = $account_type === 'income' ? 'income_accounts' : 'expense_accounts';
		$accounts_data = json_decode($book[$field], true);

		if (!$accounts_data) {
			return false;
		}

		// 계정 찾아서 이름 변경
		$accounts_data['accounts'] = $this->update_name_recursive($accounts_data['accounts'], $account_id, $account_name);

		// DB 업데이트
		$update_data = array(
			$field => json_encode($accounts_data, JSON_UNESCAPED_UNICODE),
			'modi_user_id' => $user_id
		);

		$this->db->where('book_idx', $book_idx);
		return $this->db->update('wb_cash_book', $update_data);
	}

	/**
	 * 계정명 변경 (재귀)
	 */
	private function update_name_recursive($accounts, $account_id, $account_name)
	{
		foreach ($accounts as &$account) {
			if ($account['id'] == $account_id) {
				$account['name'] = $account_name;
				return $accounts;
			}
			if (!empty($account['children'])) {
				$account['children'] = $this->update_name_recursive($account['children'], $account_id, $account_name);
			}
		}
		return $accounts;
	}

	/**
	 * 계정 삭제
	 */
	public function delete_account($book_idx, $account_type, $account_id, $user_id)
	{
		$book = $this->get_book($book_idx);
		if (!$book) {
			return array('success' => false, 'message' => '장부를 찾을 수 없습니다.');
		}

		$field = $account_type === 'income' ? 'income_accounts' : 'expense_accounts';
		$accounts_data = json_decode($book[$field], true);

		if (!$accounts_data) {
			return array('success' => false, 'message' => '계정 데이터가 없습니다.');
		}

		// 하위 계정 존재 여부 확인
		$has_children = $this->check_has_children($accounts_data['accounts'], $account_id);
		if ($has_children) {
			return array('success' => false, 'message' => '하위 계정이 존재하여 삭제할 수 없습니다.');
		}

		// 계정 삭제
		$accounts_data['accounts'] = $this->delete_account_recursive($accounts_data['accounts'], $account_id);

		// DB 업데이트
		$update_data = array(
			$field => json_encode($accounts_data, JSON_UNESCAPED_UNICODE),
			'modi_user_id' => $user_id
		);

		$this->db->where('book_idx', $book_idx);
		$this->db->update('wb_cash_book', $update_data);

		return array('success' => true);
	}

	/**
	 * 하위 계정 존재 여부 확인 (재귀)
	 */
	private function check_has_children($accounts, $account_id)
	{
		foreach ($accounts as $account) {
			if ($account['id'] == $account_id) {
				return !empty($account['children']);
			}
			if (!empty($account['children'])) {
				$result = $this->check_has_children($account['children'], $account_id);
				if ($result !== null) {
					return $result;
				}
			}
		}
		return null;
	}

	/**
	 * 계정 삭제 (재귀)
	 */
	private function delete_account_recursive($accounts, $account_id)
	{
		$result = array();
		foreach ($accounts as $account) {
			if ($account['id'] == $account_id) {
				continue;
			}
			if (!empty($account['children'])) {
				$account['children'] = $this->delete_account_recursive($account['children'], $account_id);
			}
			$result[] = $account;
		}
		return $result;
	}

	/**
	 * 계정 이동
	 */
	public function move_account($book_idx, $account_type, $account_id, $new_parent_id, $new_index, $user_id)
	{
		$book = $this->get_book($book_idx);
		if (!$book) {
			return array('success' => false, 'message' => '장부를 찾을 수 없습니다.');
		}

		$field = $account_type === 'income' ? 'income_accounts' : 'expense_accounts';
		$accounts_data = json_decode($book[$field], true);

		if (!$accounts_data) {
			return array('success' => false, 'message' => '계정 데이터가 없습니다.');
		}

		// 이동할 계정 찾기 및 추출
		$moving_account = null;
		$accounts_data['accounts'] = $this->extract_account($accounts_data['accounts'], $account_id, $moving_account);

		if (!$moving_account) {
			return array('success' => false, 'message' => '이동할 계정을 찾을 수 없습니다.');
		}

		// 새 위치에 삽입
		if (empty($new_parent_id)) {
			// 최상위로 이동
			$moving_account['level'] = 1;
			$moving_account['code'] = $this->generate_code($accounts_data['accounts']);
			$this->update_children_codes($moving_account);

			if ($new_index !== null && $new_index >= 0) {
				array_splice($accounts_data['accounts'], $new_index, 0, array($moving_account));
			} else {
				$accounts_data['accounts'][] = $moving_account;
			}
		} else {
			// 특정 부모 아래로 이동
			$result = $this->insert_account_to_parent($accounts_data['accounts'], $new_parent_id, $moving_account, $new_index);
			if (!$result['success']) {
				return $result;
			}
			$accounts_data['accounts'] = $result['accounts'];
		}

		// DB 업데이트
		$update_data = array(
			$field => json_encode($accounts_data, JSON_UNESCAPED_UNICODE),
			'modi_user_id' => $user_id
		);

		$this->db->where('book_idx', $book_idx);
		$this->db->update('wb_cash_book', $update_data);

		return array('success' => true);
	}

	/**
	 * 계정 추출 (재귀)
	 */
	private function extract_account($accounts, $account_id, &$extracted)
	{
		$result = array();
		foreach ($accounts as $account) {
			if ($account['id'] == $account_id) {
				$extracted = $account;
				continue;
			}
			if (!empty($account['children'])) {
				$account['children'] = $this->extract_account($account['children'], $account_id, $extracted);
			}
			$result[] = $account;
		}
		return $result;
	}

	/**
	 * 부모 아래에 계정 삽입 (재귀)
	 */
	private function insert_account_to_parent(&$accounts, $parent_id, $moving_account, $new_index)
	{
		foreach ($accounts as &$account) {
			if ($account['id'] == $parent_id) {
				if ($account['level'] >= 5) {
					return array('success' => false, 'message' => '해당 위치로 이동할 수 없습니다.');
				}

				if (!isset($account['children'])) {
					$account['children'] = array();
				}

				$moving_account['level'] = $account['level'] + 1;
				$moving_account['code'] = $this->generate_child_code($account['code'], $account['children']);
				$this->update_children_codes($moving_account);

				if ($new_index !== null && $new_index >= 0) {
					array_splice($account['children'], $new_index, 0, array($moving_account));
				} else {
					$account['children'][] = $moving_account;
				}

				return array('success' => true, 'accounts' => $accounts);
			}

			if (!empty($account['children'])) {
				$result = $this->insert_account_to_parent($account['children'], $parent_id, $moving_account, $new_index);
				if ($result['success']) {
					$account['children'] = $result['accounts'];
					return array('success' => true, 'accounts' => $accounts);
				}
			}
		}

		return array('success' => false, 'message' => '대상 부모 계정을 찾을 수 없습니다.');
	}

	/**
	 * 하위 계정 코드 업데이트 (재귀)
	 */
	private function update_children_codes(&$account)
	{
		if (!empty($account['children'])) {
			$index = 1;
			foreach ($account['children'] as &$child) {
				$child['level'] = $account['level'] + 1;
				$child['code'] = $account['code'] . '-' . str_pad($index, 2, '0', STR_PAD_LEFT);
				$this->update_children_codes($child);
				$index++;
			}
		}
	}

	/**
	 * 기본 수입 계정과목
	 */
	public function get_default_income_accounts()
	{
		return array(
			'last_id' => 15,
			'accounts' => array(
				array(
					'id' => 1, 'code' => '01', 'name' => '헌금수입', 'level' => 1,
					'children' => array(
						array(
							'id' => 2, 'code' => '01-01', 'name' => '일반헌금', 'level' => 2,
							'children' => array(
								array('id' => 3, 'code' => '01-01-01', 'name' => '주일헌금', 'level' => 3, 'children' => array()),
								array('id' => 4, 'code' => '01-01-02', 'name' => '십일조헌금', 'level' => 3, 'children' => array()),
								array('id' => 5, 'code' => '01-01-03', 'name' => '감사헌금', 'level' => 3, 'children' => array()),
							)
						),
						array(
							'id' => 6, 'code' => '01-02', 'name' => '절기헌금', 'level' => 2,
							'children' => array(
								array('id' => 7, 'code' => '01-02-01', 'name' => '신년감사', 'level' => 3, 'children' => array()),
								array('id' => 8, 'code' => '01-02-02', 'name' => '부활절감사', 'level' => 3, 'children' => array()),
								array('id' => 9, 'code' => '01-02-03', 'name' => '맥추감사', 'level' => 3, 'children' => array()),
								array('id' => 10, 'code' => '01-02-04', 'name' => '추수감사', 'level' => 3, 'children' => array()),
								array('id' => 11, 'code' => '01-02-05', 'name' => '성탄감사', 'level' => 3, 'children' => array()),
								array('id' => 12, 'code' => '01-02-06', 'name' => '송구영신헌금', 'level' => 3, 'children' => array()),
							)
						),
						array(
							'id' => 13, 'code' => '01-03', 'name' => '목적헌금', 'level' => 2,
							'children' => array(
								array('id' => 14, 'code' => '01-03-01', 'name' => '건축헌금', 'level' => 3, 'children' => array()),
								array('id' => 15, 'code' => '01-03-02', 'name' => '선교헌금', 'level' => 3, 'children' => array()),
								array('id' => 16, 'code' => '01-03-03', 'name' => '구제헌금', 'level' => 3, 'children' => array()),
								array('id' => 17, 'code' => '01-03-04', 'name' => '장학헌금', 'level' => 3, 'children' => array()),
								array('id' => 18, 'code' => '01-03-05', 'name' => '차량헌금', 'level' => 3, 'children' => array()),
							)
						),
					)
				),
				array(
					'id' => 19, 'code' => '02', 'name' => '기타수입', 'level' => 1,
					'children' => array(
						array(
							'id' => 20, 'code' => '02-01', 'name' => '잡수입', 'level' => 2,
							'children' => array(
								array('id' => 21, 'code' => '02-01-01', 'name' => '예금이자', 'level' => 3, 'children' => array()),
								array('id' => 22, 'code' => '02-01-02', 'name' => '비품매각대금', 'level' => 3, 'children' => array()),
								array('id' => 23, 'code' => '02-01-03', 'name' => '카페/자판기 수익금', 'level' => 3, 'children' => array()),
								array('id' => 24, 'code' => '02-01-04', 'name' => '장소사용료', 'level' => 3, 'children' => array()),
							)
						),
					)
				),
				array(
					'id' => 25, 'code' => '03', 'name' => '이월금', 'level' => 1,
					'children' => array(
						array(
							'id' => 26, 'code' => '03-01', 'name' => '전기이월금', 'level' => 2,
							'children' => array(
								array('id' => 27, 'code' => '03-01-01', 'name' => '전년도 이월금', 'level' => 3, 'children' => array()),
							)
						),
					)
				),
			)
		);
	}

	/**
	 * 기본 지출 계정과목
	 */
	public function get_default_expense_accounts()
	{
		return array(
			'last_id' => 50,
			'accounts' => array(
				array(
					'id' => 1, 'code' => '01', 'name' => '예배사역비', 'level' => 1,
					'children' => array(
						array(
							'id' => 2, 'code' => '01-01', 'name' => '예배비', 'level' => 2,
							'children' => array(
								array('id' => 3, 'code' => '01-01-01', 'name' => '주보인쇄비', 'level' => 3, 'children' => array()),
								array('id' => 4, 'code' => '01-01-02', 'name' => '성단장식비(꽃꽂이)', 'level' => 3, 'children' => array()),
								array('id' => 5, 'code' => '01-01-03', 'name' => '예배용품', 'level' => 3, 'children' => array()),
								array('id' => 6, 'code' => '01-01-04', 'name' => '성찬비', 'level' => 3, 'children' => array()),
							)
						),
						array(
							'id' => 7, 'code' => '01-02', 'name' => '음악비', 'level' => 2,
							'children' => array(
								array('id' => 8, 'code' => '01-02-01', 'name' => '찬양대운영비', 'level' => 3, 'children' => array()),
								array('id' => 9, 'code' => '01-02-02', 'name' => '악기유지비', 'level' => 3, 'children' => array()),
								array('id' => 10, 'code' => '01-02-03', 'name' => '악보구입비', 'level' => 3, 'children' => array()),
							)
						),
					)
				),
				array(
					'id' => 11, 'code' => '02', 'name' => '교육사역비', 'level' => 1,
					'children' => array(
						array(
							'id' => 12, 'code' => '02-01', 'name' => '부서교육비', 'level' => 2,
							'children' => array(
								array('id' => 13, 'code' => '02-01-01', 'name' => '영유아부', 'level' => 3, 'children' => array()),
								array('id' => 14, 'code' => '02-01-02', 'name' => '유치부', 'level' => 3, 'children' => array()),
								array('id' => 15, 'code' => '02-01-03', 'name' => '초등부', 'level' => 3, 'children' => array()),
								array('id' => 16, 'code' => '02-01-04', 'name' => '청소년부', 'level' => 3, 'children' => array()),
								array('id' => 17, 'code' => '02-01-05', 'name' => '대학청년부 운영비', 'level' => 3, 'children' => array()),
							)
						),
						array(
							'id' => 18, 'code' => '02-02', 'name' => '교육행사비', 'level' => 2,
							'children' => array(
								array('id' => 19, 'code' => '02-02-01', 'name' => '여름/겨울 성경학교 및 수련회', 'level' => 3, 'children' => array()),
								array('id' => 20, 'code' => '02-02-02', 'name' => '졸업/입학 축하비', 'level' => 3, 'children' => array()),
							)
						),
					)
				),
				array(
					'id' => 21, 'code' => '03', 'name' => '선교 및 구제비', 'level' => 1,
					'children' => array(
						array(
							'id' => 22, 'code' => '03-01', 'name' => '선교비', 'level' => 2,
							'children' => array(
								array('id' => 23, 'code' => '03-01-01', 'name' => '국내외 선교사 후원', 'level' => 3, 'children' => array()),
								array('id' => 24, 'code' => '03-01-02', 'name' => '미자립교회 지원', 'level' => 3, 'children' => array()),
								array('id' => 25, 'code' => '03-01-03', 'name' => '군/교도소 선교', 'level' => 3, 'children' => array()),
							)
						),
						array(
							'id' => 26, 'code' => '03-02', 'name' => '구제비', 'level' => 2,
							'children' => array(
								array('id' => 27, 'code' => '03-02-01', 'name' => '지역사회 구제비', 'level' => 3, 'children' => array()),
								array('id' => 28, 'code' => '03-02-02', 'name' => '장학금 지급', 'level' => 3, 'children' => array()),
								array('id' => 29, 'code' => '03-02-03', 'name' => '경조비', 'level' => 3, 'children' => array()),
							)
						),
						array(
							'id' => 30, 'code' => '03-03', 'name' => '상회부담금', 'level' => 2,
							'children' => array(
								array('id' => 31, 'code' => '03-03-01', 'name' => '총회부담금', 'level' => 3, 'children' => array()),
								array('id' => 32, 'code' => '03-03-02', 'name' => '노회부담금', 'level' => 3, 'children' => array()),
							)
						),
					)
				),
				array(
					'id' => 33, 'code' => '04', 'name' => '목양행정비', 'level' => 1,
					'children' => array(
						array(
							'id' => 34, 'code' => '04-01', 'name' => '인건비', 'level' => 2,
							'children' => array(
								array('id' => 35, 'code' => '04-01-01', 'name' => '교역자 사례비', 'level' => 3, 'children' => array()),
								array('id' => 36, 'code' => '04-01-02', 'name' => '직원 사례비', 'level' => 3, 'children' => array()),
								array('id' => 37, 'code' => '04-01-03', 'name' => '상여금', 'level' => 3, 'children' => array()),
								array('id' => 38, 'code' => '04-01-04', 'name' => '퇴직적립금', 'level' => 3, 'children' => array()),
							)
						),
						array(
							'id' => 39, 'code' => '04-02', 'name' => '복리후생비', 'level' => 2,
							'children' => array(
								array('id' => 40, 'code' => '04-02-01', 'name' => '건강보험', 'level' => 3, 'children' => array()),
								array('id' => 41, 'code' => '04-02-02', 'name' => '국민연금', 'level' => 3, 'children' => array()),
								array('id' => 42, 'code' => '04-02-03', 'name' => '사택유지비', 'level' => 3, 'children' => array()),
							)
						),
						array(
							'id' => 43, 'code' => '04-03', 'name' => '사무관리비', 'level' => 2,
							'children' => array(
								array('id' => 44, 'code' => '04-03-01', 'name' => '사무용품비', 'level' => 3, 'children' => array()),
								array('id' => 45, 'code' => '04-03-02', 'name' => '통신비', 'level' => 3, 'children' => array()),
								array('id' => 46, 'code' => '04-03-03', 'name' => '우편료', 'level' => 3, 'children' => array()),
								array('id' => 47, 'code' => '04-03-04', 'name' => '인쇄비', 'level' => 3, 'children' => array()),
							)
						),
					)
				),
				array(
					'id' => 48, 'code' => '05', 'name' => '시설관리비', 'level' => 1,
					'children' => array(
						array(
							'id' => 49, 'code' => '05-01', 'name' => '유지관리비', 'level' => 2,
							'children' => array(
								array('id' => 50, 'code' => '05-01-01', 'name' => '공공요금(전기, 수도, 가스)', 'level' => 3, 'children' => array()),
								array('id' => 51, 'code' => '05-01-02', 'name' => '수선유지비', 'level' => 3, 'children' => array()),
								array('id' => 52, 'code' => '05-01-03', 'name' => '소방/안전점검비', 'level' => 3, 'children' => array()),
							)
						),
						array(
							'id' => 53, 'code' => '05-02', 'name' => '차량관리비', 'level' => 2,
							'children' => array(
								array('id' => 54, 'code' => '05-02-01', 'name' => '유류비', 'level' => 3, 'children' => array()),
								array('id' => 55, 'code' => '05-02-02', 'name' => '차량보험료', 'level' => 3, 'children' => array()),
								array('id' => 56, 'code' => '05-02-03', 'name' => '차량수리비', 'level' => 3, 'children' => array()),
							)
						),
					)
				),
				array(
					'id' => 57, 'code' => '06', 'name' => '예비비 및 기타', 'level' => 1,
					'children' => array(
						array(
							'id' => 58, 'code' => '06-01', 'name' => '예비비', 'level' => 2,
							'children' => array(
								array('id' => 59, 'code' => '06-01-01', 'name' => '예비비', 'level' => 3, 'children' => array()),
							)
						),
						array(
							'id' => 60, 'code' => '06-02', 'name' => '원리금 상환', 'level' => 2,
							'children' => array(
								array('id' => 61, 'code' => '06-02-01', 'name' => '차입금 원금 및 이자 상환', 'level' => 3, 'children' => array()),
							)
						),
					)
				),
			)
		);
	}
}
