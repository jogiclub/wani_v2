
<?php
class Org_model extends CI_Model {
	public function __construct() {
		parent::__construct();
		$this->load->database();
	}

	public function create_org($org_name) {
		$data = array(
			'org_name' => $org_name,
			'regi_date' => date('Y-m-d H:i:s'),
			'modi_date' => date('Y-m-d H:i:s')
		);
		$this->db->insert('wb_org', $data);
		return $this->db->insert_id();
	}



	public function get_org_user($user_id, $org_id) {
		$this->db->where('user_id', $user_id);
		$this->db->where('org_id', $org_id);
		$query = $this->db->get('wb_org_user');
		return $query->row_array();
	}





	/**
	 * 역할: 새로운 조직 정보 저장
	 */
	public function insert_organization($data)
	{
		return $this->db->insert('wb_org', $data);
	}




	/**
	 * 조직 사용자 정보 저장
	 */
	public function insert_org_user($data)
	{
		return $this->db->insert('wb_org_user', $data);
	}

	/**
	 * 조직 사용자 상태 업데이트 (초대 승인 등)
	 */
	public function update_org_user_status($user_id, $org_id, $data)
	{
		$this->db->where('user_id', $user_id);
		$this->db->where('org_id', $org_id);
		return $this->db->update('wb_org_user', $data);
	}



	public function get_min_org_id($user_id) {
		$this->db->select_min('wb_org_user.org_id');
		$this->db->from('wb_org_user');
		$this->db->join('wb_org', 'wb_org_user.org_id = wb_org.org_id');
		$this->db->where('wb_org_user.user_id', $user_id);
		$this->db->where('wb_org.del_yn', 'N');
		$query = $this->db->get();
		$result = $query->row_array();

		return $result['org_id'] ?? null;
	}



	/**
	 * 조직 정보 업데이트
	 */
	public function update_org_info($org_id, $data) {
		// JSON 데이터 처리
		if (isset($data['position_name']) && is_array($data['position_name'])) {
			$data['position_name'] = json_encode($data['position_name'], JSON_UNESCAPED_UNICODE);
		}

		if (isset($data['duty_name']) && is_array($data['duty_name'])) {
			$data['duty_name'] = json_encode($data['duty_name'], JSON_UNESCAPED_UNICODE);
		}

		if (isset($data['timeline_name']) && is_array($data['timeline_name'])) {
			$data['timeline_name'] = json_encode($data['timeline_name'], JSON_UNESCAPED_UNICODE);
		}

		$this->db->where('org_id', $org_id);
		$this->db->where('del_yn', 'N');
		return $this->db->update('wb_org', $data);
	}

	/**
	 * 조직의 최고관리자 정보 가져오기
	 */
	public function get_org_admin($org_id) {
		$this->db->select('wb_user.user_id, wb_user.user_name, wb_user.user_mail, wb_user.user_profile_image');
		$this->db->from('wb_user');
		$this->db->join('wb_org_user', 'wb_user.user_id = wb_org_user.user_id');
		$this->db->where('wb_org_user.org_id', $org_id);
		$this->db->where('wb_org_user.level', 10);
		$this->db->where('wb_user.del_yn', 'N');
		$this->db->limit(1);
		$query = $this->db->get();
		return $query->row_array();
	}

	/**
	 * 관리자 권한 위임
	 */
	public function delegate_admin($current_admin_id, $new_admin_id, $org_id) {
		$this->db->trans_start();

		// 현재 관리자를 레벨 9로 변경
		$this->db->where('user_id', $current_admin_id);
		$this->db->where('org_id', $org_id);
		$this->db->update('wb_org_user', array('level' => 9));

		// 새 관리자를 레벨 10으로 변경
		$this->db->where('user_id', $new_admin_id);
		$this->db->where('org_id', $org_id);
		$this->db->update('wb_org_user', array('level' => 10));

		$this->db->trans_complete();

		return $this->db->trans_status();
	}


	/**
	 * 카테고리별 조직 목록 조회
	 */
	public function get_orgs_by_category($category_idx = null)
	{
		$this->db->select('o.org_id, o.org_code, o.org_name, o.org_type, o.org_desc, o.leader_name, o.new_name, o.org_icon, o.regi_date, o.invite_code, COUNT(ou.user_id) as member_count');
		$this->db->from('wb_org o');
		$this->db->join('wb_org_user ou', 'o.org_id = ou.org_id', 'left');

		if ($category_idx === null || $category_idx === 'uncategorized') {
			// 미분류 조직 조회
			$this->db->where('(o.category_idx IS NULL OR o.category_idx = 0)', null, false);
		} else {
			$this->db->where('o.category_idx', $category_idx);
		}

		$this->db->where('o.del_yn', 'N');
		$this->db->group_by('o.org_id');
		$this->db->order_by('o.regi_date', 'DESC');

		$query = $this->db->get();
		return $query->result_array();
	}


	/**
	 * 카테고리에 조직이 있는지 확인
	 */
	public function has_orgs_in_category($category_idx)
	{
		$this->db->from('wb_org');
		$this->db->where('category_idx', $category_idx);
		$this->db->where('del_yn', 'N');
		return $this->db->count_all_results() > 0;
	}




	/**
	 * 미분류를 제외한 모든 조직 조회 (전체 선택용)
	 */
	public function get_all_orgs_except_uncategorized()
	{
		$this->db->select('
        o.org_id,
        o.org_code,
        o.org_name,
        o.org_type,
        o.org_desc,
        o.org_rep,
        o.org_manager,
        o.org_phone,
        o.org_address_postno,
        o.org_address,
        o.org_address_detail,
        o.org_tag,
        o.org_icon,
        o.category_idx,
        o.regi_date,
        o.modi_date,
        oc.category_name,
        COUNT(m.member_idx) as member_count
    ');

		$this->db->from('wb_org o');
		$this->db->join('wb_org_category oc', 'o.category_idx = oc.category_idx', 'left');
		$this->db->join('wb_member m', 'o.org_id = m.org_id AND m.del_yn = "N"', 'left');
		$this->db->where('o.del_yn', 'N');

		// 미분류 제외 (category_idx가 null이 아닌 조직들만)
		$this->db->where('o.category_idx IS NOT NULL');
		$this->db->where('o.category_idx >', 0);

		$this->db->group_by('o.org_id');
		$this->db->order_by('o.regi_date', 'DESC');

		$query = $this->db->get();
		return $query->result_array();
	}



	/**
	 * 특정 카테고리와 모든 하위 카테고리의 조직 목록 조회
	 */
	public function get_orgs_by_category_with_children($category_idx)
	{
		// 모든 하위 카테고리 ID 조회
		$this->load->model('Org_category_model');
		$child_category_ids = $this->Org_category_model->get_all_child_category_ids($category_idx);

		// 현재 카테고리도 포함
		$category_ids = array_merge(array($category_idx), $child_category_ids);

		$this->db->select('
        o.org_id,
        o.org_code,
        o.org_name,
        o.org_type,
        o.org_desc,
        o.org_rep,
        o.org_manager,
        o.org_phone,
        o.org_address_postno,
        o.org_address,
        o.org_address_detail,
        o.org_tag,
        o.org_icon,
        o.category_idx,
        o.regi_date,
        o.modi_date,
        oc.category_name,
        COUNT(m.member_idx) as member_count
    ');

		$this->db->from('wb_org o');
		$this->db->join('wb_org_category oc', 'o.category_idx = oc.category_idx', 'left');
		$this->db->join('wb_member m', 'o.org_id = m.org_id AND m.del_yn = "N"', 'left');
		$this->db->where('o.del_yn', 'N');
		$this->db->where_in('o.category_idx', $category_ids);

		$this->db->group_by('o.org_id');
		$this->db->order_by('o.regi_date', 'DESC');

		$query = $this->db->get();
		return $query->result_array();
	}


	/**
	 * 카테고리별 조직 목록 조회 (상세 정보 포함)
	 */
	public function get_orgs_by_category_detailed($category_idx = null)
	{
		$this->db->select('
            o.org_id,
            o.org_code,
            o.org_name,
            o.org_type,
            o.org_desc,
            o.org_rep,
            o.org_manager,
            o.org_phone,
            o.org_address_postno,
            o.org_address,
            o.org_address_detail,
            o.org_tag,
            o.org_icon,
            o.category_idx,
            o.regi_date,
            oc.category_name,
            (SELECT COUNT(*) FROM wb_member m WHERE m.org_id = o.org_id AND m.del_yn = "N") as member_count
        ');
		$this->db->from('wb_org o');
		$this->db->join('wb_org_category oc', 'o.category_idx = oc.category_idx', 'left');
		$this->db->where('o.del_yn', 'N');

		// 카테고리 필터링
		if ($category_idx === null || $category_idx === '' || $category_idx === 'uncategorized') {
			$this->db->where('(o.category_idx IS NULL OR o.category_idx = 0)', null, false);
		} else {
			$this->db->where('o.category_idx', $category_idx);
		}

		$this->db->order_by('o.regi_date', 'DESC');
		$query = $this->db->get();

		return $query->result_array();
	}

	/**
	 * 조직 상세 정보 조회
	 */
	public function get_org_detail_by_id($org_id)
	{
		$this->db->select('
            org_id,
            org_code,
            org_name,
            org_type,
            org_desc,
            org_rep,
            org_manager,
            org_phone,
            org_address_postno,
            org_address,
            org_address_detail,
            org_tag,
            org_icon,
            leader_name,
            new_name,
            invite_code,
            position_name,
            duty_name,
            timeline_name,
            category_idx,
            regi_date,
            modi_date
        ');
		$this->db->from('wb_org');
		$this->db->where('org_id', $org_id);
		$this->db->where('del_yn', 'N');
		$query = $this->db->get();
		return $query->row_array();
	}

	/**
	 * 조직 정보 업데이트
	 */
	public function update_org($org_id, $data)
	{
		// JSON 데이터 처리
		if (isset($data['org_tag']) && is_array($data['org_tag'])) {
			$data['org_tag'] = json_encode($data['org_tag'], JSON_UNESCAPED_UNICODE);
		}

		if (isset($data['position_name']) && is_array($data['position_name'])) {
			$data['position_name'] = json_encode($data['position_name'], JSON_UNESCAPED_UNICODE);
		}

		if (isset($data['duty_name']) && is_array($data['duty_name'])) {
			$data['duty_name'] = json_encode($data['duty_name'], JSON_UNESCAPED_UNICODE);
		}

		if (isset($data['timeline_name']) && is_array($data['timeline_name'])) {
			$data['timeline_name'] = json_encode($data['timeline_name'], JSON_UNESCAPED_UNICODE);
		}

		$data['modi_date'] = date('Y-m-d H:i:s');

		$this->db->where('org_id', $org_id);
		$this->db->where('del_yn', 'N');
		return $this->db->update('wb_org', $data);
	}

	/**
	 * 조직 생성
	 */
	public function insert_org($data)
	{
		// JSON 데이터 처리
		if (isset($data['org_tag']) && is_array($data['org_tag'])) {
			$data['org_tag'] = json_encode($data['org_tag'], JSON_UNESCAPED_UNICODE);
		}

		if (isset($data['position_name']) && is_array($data['position_name'])) {
			$data['position_name'] = json_encode($data['position_name'], JSON_UNESCAPED_UNICODE);
		}

		if (isset($data['duty_name']) && is_array($data['duty_name'])) {
			$data['duty_name'] = json_encode($data['duty_name'], JSON_UNESCAPED_UNICODE);
		}

		if (isset($data['timeline_name']) && is_array($data['timeline_name'])) {
			$data['timeline_name'] = json_encode($data['timeline_name'], JSON_UNESCAPED_UNICODE);
		}

		$data['regi_date'] = date('Y-m-d H:i:s');
		$data['modi_date'] = date('Y-m-d H:i:s');
		$data['del_yn'] = 'N';

		// 초대코드 생성
		if (!isset($data['invite_code']) || empty($data['invite_code'])) {
			$data['invite_code'] = $this->generate_invite_code();
		}

		// 조직코드 생성
		if (!isset($data['org_code']) || empty($data['org_code'])) {
			$data['org_code'] = $this->generate_org_code();
		}

		return $this->db->insert('wb_org', $data);
	}

	/**
	 * 조직 삭제 (소프트 삭제)
	 */
	public function delete_org($org_id)
	{
		$data = array(
			'del_yn' => 'Y',
			'del_date' => date('Y-m-d H:i:s'),
			'modi_date' => date('Y-m-d H:i:s')
		);

		$this->db->where('org_id', $org_id);
		return $this->db->update('wb_org', $data);
	}

	/**
	 * 조직 다중 삭제 (소프트 삭제)
	 */
	public function bulk_delete_orgs($org_ids)
	{
		if (!is_array($org_ids) || empty($org_ids)) {
			return false;
		}

		$data = array(
			'del_yn' => 'Y',
			'del_date' => date('Y-m-d H:i:s'),
			'modi_date' => date('Y-m-d H:i:s')
		);

		$this->db->where_in('org_id', $org_ids);
		return $this->db->update('wb_org', $data);
	}

	/**
	 * 기존 태그 목록 조회
	 */
	public function get_existing_tags()
	{
		$this->db->select('org_tag');
		$this->db->from('wb_org');
		$this->db->where('del_yn', 'N');
		$this->db->where('org_tag IS NOT NULL');
		$this->db->where('org_tag != ""');
		$query = $this->db->get();

		$tags = array();
		$results = $query->result_array();

		foreach ($results as $row) {
			$orgTags = $row['org_tag'];

			// JSON 형태인지 확인
			$decoded = json_decode($orgTags, true);
			if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
				$tags = array_merge($tags, $decoded);
			} else {
				// JSON이 아닌 경우 쉼표로 분리
				$splitTags = explode(',', $orgTags);
				$splitTags = array_map('trim', $splitTags);
				$splitTags = array_filter($splitTags);
				$tags = array_merge($tags, $splitTags);
			}
		}

		// 중복 제거 및 정렬
		$tags = array_unique($tags);
		sort($tags);

		return array_values($tags);
	}

	/**
	 * 조직 회원 수 조회
	 */
	public function get_org_member_count($org_id)
	{
		$this->db->from('wb_member');
		$this->db->where('org_id', $org_id);
		$this->db->where('del_yn', 'N');
		return $this->db->count_all_results();
	}

	/**
	 * 사용자의 조직 목록 조회 (일반 사용자)
	 */
	public function get_user_orgs($user_id)
	{
		$this->db->select('
            o.org_id,
            o.org_name,
            o.org_type,
            o.org_icon,
            o.leader_name,
            o.new_name,
            ou.level,
            (SELECT COUNT(*) FROM wb_member m WHERE m.org_id = o.org_id AND m.del_yn = "N") as member_count
        ');
		$this->db->from('wb_org o');
		$this->db->join('wb_org_user ou', 'o.org_id = ou.org_id');
		$this->db->where('ou.user_id', $user_id);
		$this->db->where('o.del_yn', 'N');
		$this->db->order_by('o.org_name', 'ASC');
		$query = $this->db->get();
		return $query->result_array();
	}

	/**
	 * 사용자의 조직 목록 조회 (마스터 사용자)
	 */
	public function get_user_orgs_master($user_id)
	{
		$this->db->select('
            o.org_id,
            o.org_name,
            o.org_type,
            o.org_icon,
            o.leader_name,
            o.new_name,
            10 as level,
            (SELECT COUNT(*) FROM wb_member m WHERE m.org_id = o.org_id AND m.del_yn = "N") as member_count
        ');
		$this->db->from('wb_org o');
		$this->db->where('o.del_yn', 'N');
		$this->db->order_by('o.org_name', 'ASC');
		$query = $this->db->get();
		return $query->result_array();
	}

	/**
	 * 초대코드로 조직 조회
	 */
	public function get_org_by_invite_code($invite_code)
	{
		$this->db->select('org_id, org_name, org_type, org_desc, invite_code, del_yn');
		$this->db->from('wb_org');
		$this->db->where('invite_code', $invite_code);
		$query = $this->db->get();
		return $query->row_array();
	}

	/**
	 * 조직 ID로 조직 정보 조회
	 */
	public function get_org_by_id($org_id)
	{
		$this->db->select('org_id, org_name, org_type, org_icon, leader_name, new_name');
		$this->db->from('wb_org');
		$this->db->where('org_id', $org_id);
		$this->db->where('del_yn', 'N');
		$query = $this->db->get();
		return $query->row_array();
	}

	/**
	 * 사용자의 조직 멤버십 확인
	 */
	public function check_user_org_membership($user_id, $org_id)
	{
		$this->db->select('idx');
		$this->db->from('wb_org_user');
		$this->db->where('user_id', $user_id);
		$this->db->where('org_id', $org_id);
		$query = $this->db->get();
		return $query->row_array();
	}

	/**
	 * 조직에 사용자 추가
	 */
	public function add_user_to_org($user_id, $org_id, $level = 1)
	{
		$data = array(
			'user_id' => $user_id,
			'org_id' => $org_id,
			'level' => $level
		);
		return $this->db->insert('wb_org_user', $data);
	}

	/**
	 * 조직 사용자 권한 레벨 조회
	 */
	public function get_org_user_level($user_id, $org_id)
	{
		$this->db->select('level');
		$this->db->from('wb_org_user');
		$this->db->where('user_id', $user_id);
		$this->db->where('org_id', $org_id);
		$query = $this->db->get();
		$result = $query->row_array();
		return $result ? $result['level'] : 0;
	}

	/**
	 * 초대코드 생성
	 */
	private function generate_invite_code()
	{
		do {
			$code = strtoupper(substr(str_shuffle('ABCDEFGHIJKLMNPQRSTUVWXYZ123456789'), 0, 5));

			$this->db->where('invite_code', $code);
			$exists = $this->db->count_all_results('wb_org') > 0;
		} while ($exists);

		return $code;
	}

	/**
	 * 조직코드 생성
	 */
	private function generate_org_code()
	{
		// 현재 등록된 조직 수 + 1로 코드 생성
		$this->db->from('wb_org');
		$count = $this->db->count_all_results();

		return 'ORG' . str_pad($count + 1, 4, '0', STR_PAD_LEFT);
	}

	/**
	 * 조직을 카테고리에 할당
	 */
	public function assign_org_to_category($org_id, $category_idx)
	{
		$data = array(
			'category_idx' => $category_idx,
			'modi_date' => date('Y-m-d H:i:s')
		);

		$this->db->where('org_id', $org_id);
		return $this->db->update('wb_org', $data);
	}

	/**
	 * 조직의 카테고리 할당 해제
	 */
	public function unassign_org_from_category($org_id)
	{
		$data = array(
			'category_idx' => null,
			'modi_date' => date('Y-m-d H:i:s')
		);

		$this->db->where('org_id', $org_id);
		return $this->db->update('wb_org', $data);
	}

	/**
	 * 조직 검색
	 */
	public function search_orgs($keyword, $category_idx = null)
	{
		$this->db->select('
            o.org_id,
            o.org_code,
            o.org_name,
            o.org_type,
            o.org_desc,
            o.org_rep,
            o.org_manager,
            o.org_phone,
            o.org_address_postno,
            o.org_address,
            o.org_address_detail,
            o.org_tag,
            o.org_icon,
            o.category_idx,
            o.regi_date,
            oc.category_name,
            (SELECT COUNT(*) FROM wb_member m WHERE m.org_id = o.org_id AND m.del_yn = "N") as member_count
        ');
		$this->db->from('wb_org o');
		$this->db->join('wb_org_category oc', 'o.category_idx = oc.category_idx', 'left');

		// 검색 조건
		$this->db->group_start();
		$this->db->like('o.org_name', $keyword);
		$this->db->or_like('o.org_code', $keyword);
		$this->db->or_like('o.org_desc', $keyword);
		$this->db->or_like('o.org_rep', $keyword);
		$this->db->or_like('o.org_manager', $keyword);
		$this->db->group_end();

		// 카테고리 필터
		if ($category_idx !== null && $category_idx !== 'all') {
			if ($category_idx === 'uncategorized') {
				$this->db->where('(o.category_idx IS NULL OR o.category_idx = 0)', null, false);
			} else {
				$this->db->where('o.category_idx', $category_idx);
			}
		}

		$this->db->where('o.del_yn', 'N');
		$this->db->order_by('o.org_name', 'ASC');

		$query = $this->db->get();
		return $query->result_array();
	}

	/**
	 * 조직 ID로 조직명 배열 가져오기
	 */
	public function get_org_names_by_ids($org_ids)
	{
		if (!is_array($org_ids) || empty($org_ids)) {
			return array();
		}

		$this->db->select('org_id, org_name');
		$this->db->from('wb_org');
		$this->db->where_in('org_id', $org_ids);
		$this->db->where('del_yn', 'N');

		$query = $this->db->get();
		$result = array();

		foreach ($query->result_array() as $row) {
			$result[$row['org_id']] = $row['org_name'];
		}

		return $result;
	}

	/**
	 * 조직 코드 중복 체크
	 */
	public function check_org_code_exists($org_code, $exclude_org_id = null)
	{
		$this->db->from('wb_org');
		$this->db->where('org_code', $org_code);
		$this->db->where('del_yn', 'N');

		if ($exclude_org_id) {
			$this->db->where('org_id !=', $exclude_org_id);
		}

		return $this->db->count_all_results() > 0;
	}

	/**
	 * 초대코드 중복 체크
	 */
	public function check_invite_code_exists($invite_code, $exclude_org_id = null)
	{
		$this->db->from('wb_org');
		$this->db->where('invite_code', $invite_code);
		$this->db->where('del_yn', 'N');

		if ($exclude_org_id) {
			$this->db->where('org_id !=', $exclude_org_id);
		}

		return $this->db->count_all_results() > 0;
	}


	/**
	 * 초대 코드 갱신
	 */
	public function refresh_invite_code($org_id) {
		// 새로운 초대코드 생성
		$new_invite_code = $this->generate_new_invite_code();

		// DB 업데이트
		$data = array(
			'invite_code' => $new_invite_code,
			'modi_date' => date('Y-m-d H:i:s')
		);

		$this->db->where('org_id', $org_id);
		$this->db->where('del_yn', 'N');
		$result = $this->db->update('wb_org', $data);

		if ($result) {
			log_message('info', "조직 {$org_id}의 초대코드가 {$new_invite_code}로 갱신되었습니다.");
			return $new_invite_code;
		}

		return false;
	}

	/**
	 * 중복되지 않는 초대코드 생성 (public 메서드)
	 */
	public function generate_new_invite_code() {
		$max_attempts = 100;
		$attempt = 0;

		do {
			$code = strtoupper(substr(str_shuffle('ABCDEFGHIJKLMNPQRSTUVWXYZ123456789'), 0, 5));

			$this->db->where('invite_code', $code);
			$this->db->where('del_yn', 'N');
			$exists = $this->db->count_all_results('wb_org') > 0;

			$attempt++;

			if ($attempt >= $max_attempts) {
				log_message('error', '초대코드 생성 시도 한계 도달');
				break;
			}
		} while ($exists);

		return $code;
	}

	/**
	 * 조직의 타임라인 항목 조회
	 */
	public function get_timeline_types($org_id)
	{
		$this->db->select('timeline_name');
		$this->db->from('wb_org');
		$this->db->where('org_id', $org_id);

		$query = $this->db->get();
		$result = $query->row_array();

		if ($result && !empty($result['timeline_name'])) {
			$timeline_data = json_decode($result['timeline_name'], true);

			if (is_array($timeline_data)) {
				return $timeline_data;
			}

			// JSON이 아닌 경우 쉼표로 구분된 문자열로 처리
			$timeline_names = explode(',', $result['timeline_name']);
			return array_map('trim', $timeline_names);
		}

		return array();
	}


	/**
	 * 조직의 메모 타입 목록 조회
	 */
	public function get_memo_types($org_id)
	{
		$org_detail = $this->get_org_detail_by_id($org_id);
		$memo_types = array();

		if ($org_detail && !empty($org_detail['memo_type_name'])) {
			try {
				$decoded_types = json_decode($org_detail['memo_type_name'], true);
				if (is_array($decoded_types)) {
					$memo_types = $decoded_types;
				}
			} catch (Exception $e) {
				log_message('error', '메모 타입 JSON 파싱 오류: ' . $e->getMessage());
			}
		}

		return $memo_types;
	}

}
