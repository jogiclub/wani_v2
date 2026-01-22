
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
	 * 파일 위치: application/models/Org_model.php
	 * 역할: 조직 정보 업데이트 (알림 메시지 설정을 JSON으로 저장)
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

		if (isset($data['memo_name']) && is_array($data['memo_name'])) {
			$data['memo_name'] = json_encode($data['memo_name'], JSON_UNESCAPED_UNICODE);
		}

		// 알림 메시지 설정 JSON 처리 (추가)
		if (isset($data['auto_message'])) {
			// 이미 JSON 문자열인 경우 그대로 사용, 배열인 경우 JSON으로 변환
			if (is_array($data['auto_message'])) {
				$data['auto_message'] = json_encode($data['auto_message'], JSON_UNESCAPED_UNICODE);
			}
			// JSON 유효성 검증
			if (is_string($data['auto_message'])) {
				$decoded = json_decode($data['auto_message'], true);
				if (json_last_error() !== JSON_ERROR_NONE) {
					log_message('error', '알림 메시지 JSON 파싱 오류: ' . json_last_error_msg());
					unset($data['auto_message']);
				}
			}
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
	 * 미분류 제외한 전체 조직 목록 조회
	 * 수정 사항: user_count 컬럼 추가
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
        COUNT(DISTINCT m.member_idx) as member_count,
        COUNT(DISTINCT ou.user_id) as user_count
    ');

		$this->db->from('wb_org o');
		$this->db->join('wb_org_category oc', 'o.category_idx = oc.category_idx', 'left');
		$this->db->join('wb_member m', 'o.org_id = m.org_id AND m.del_yn = "N"', 'left');
		$this->db->join('wb_org_user ou', 'o.org_id = ou.org_id', 'left');
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
	 * 파일 위치: application/models/Org_model.php
	 * 역할: 특정 카테고리와 모든 하위 카테고리의 조직 목록 조회 (타입 캐스팅 추가)
	 */
	public function get_orgs_by_category_with_children($category_idx)
	{
		// category_idx를 정수로 변환
		$category_idx = intval($category_idx);

		if ($category_idx <= 0) {
			return array();
		}

		// 모든 하위 카테고리 ID 조회
		$this->load->model('Org_category_model');
		$child_category_ids = $this->Org_category_model->get_all_child_category_ids($category_idx);

		// 현재 카테고리도 포함 (정수 타입으로 통일)
		$category_ids = array($category_idx);

		if (!empty($child_category_ids)) {
			foreach ($child_category_ids as $child_id) {
				$category_ids[] = intval($child_id);
			}
		}

		// 디버깅용 로그
		log_message('debug', 'get_orgs_by_category_with_children - category_ids: ' . print_r($category_ids, true));

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
		COUNT(DISTINCT m.member_idx) as member_count,
		COUNT(DISTINCT ou.user_id) as user_count
	');

		$this->db->from('wb_org o');
		$this->db->join('wb_org_category oc', 'o.category_idx = oc.category_idx', 'left');
		$this->db->join('wb_member m', 'o.org_id = m.org_id AND m.del_yn = "N"', 'left');
		$this->db->join('wb_org_user ou', 'o.org_id = ou.org_id', 'left');
		$this->db->where('o.del_yn', 'N');
		$this->db->where_in('o.category_idx', $category_ids);

		$this->db->group_by('o.org_id');
		$this->db->order_by('oc.category_name', 'ASC');
		$this->db->order_by('o.org_name', 'ASC');

		$query = $this->db->get();

		// 디버깅용 로그
		log_message('debug', 'get_orgs_by_category_with_children - SQL: ' . $this->db->last_query());

		return $query->result_array();
	}


	/**
	 * 카테고리별 조직 목록 조회 (상세 정보 포함)
	 * 수정 사항: user_count 컬럼 추가
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
        o.modi_date,
        oc.category_name,
        COUNT(DISTINCT m.member_idx) as member_count,
        COUNT(DISTINCT ou.user_id) as user_count
    ');

		$this->db->from('wb_org o');
		$this->db->join('wb_org_category oc', 'o.category_idx = oc.category_idx', 'left');
		$this->db->join('wb_member m', 'o.org_id = m.org_id AND m.del_yn = "N"', 'left');
		$this->db->join('wb_org_user ou', 'o.org_id = ou.org_id', 'left');
		$this->db->where('o.del_yn', 'N');

		if ($category_idx === null) {
			// 미분류 조직 조회 (category_idx가 NULL이거나 0인 경우)
			$this->db->where('(o.category_idx IS NULL OR o.category_idx = 0)', null, false);
		} else {
			$this->db->where('o.category_idx', $category_idx);
		}

		$this->db->group_by('o.org_id');
		$this->db->order_by('o.regi_date', 'DESC');

		$query = $this->db->get();
		return $query->result_array();
	}

	/**
	 * 역할: 조직 상세 정보 조회 (auto_message 컬럼 추가)
	 */
	/**
	 * 역할: 조직 상세 정보 조회 (auto_message, org_seal 컬럼 추가)
	 */
	public function get_org_detail_by_id($org_id)
	{
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
		$this->db->where('ou.level >=', 1);  // level >= 1인 조직만 반환
		$this->db->order_by('o.org_type', 'ASC');
		$this->db->order_by('o.org_name', 'ASC');
		$query = $this->db->get();
		return $query->result_array();
	}

	/**
	 * 사용자의 조직 목록 조회 (마스터 사용자)
	 */
	public function get_user_orgs_master($user_id)
	{
		// 사용자 정보 조회
		$this->load->model('User_model');
		$user = $this->User_model->get_user_by_id($user_id);

		$visible_categories = array();

		// master_managed_category 확인
		if (!empty($user['master_managed_category'])) {
			$master_managed_category = json_decode($user['master_managed_category'], true);
			if (is_array($master_managed_category) && !empty($master_managed_category)) {
				$visible_categories = $master_managed_category;
			}
		}

		// 필터링된 카테고리가 있으면 필터링 적용
		if (!empty($visible_categories)) {
			return $this->get_user_orgs_master_filtered($user_id, $visible_categories);
		}

		// 필터링 없으면 전체 조직 반환
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
		$this->db->order_by('o.org_type', 'ASC');
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
	 * 수정 사항: user_count 컬럼 추가
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
        COUNT(DISTINCT m.member_idx) as member_count,
        COUNT(DISTINCT ou.user_id) as user_count
    ');
		$this->db->from('wb_org o');
		$this->db->join('wb_org_category oc', 'o.category_idx = oc.category_idx', 'left');
		$this->db->join('wb_member m', 'o.org_id = m.org_id AND m.del_yn = "N"', 'left');
		$this->db->join('wb_org_user ou', 'o.org_id = ou.org_id', 'left');

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
		$this->db->group_by('o.org_id');
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
		$this->db->select('memo_name');
		$this->db->from('wb_org');
		$this->db->where('org_id', $org_id);

		$query = $this->db->get();
		$result = $query->row_array();

		if ($result && !empty($result['memo_name'])) {
			$memo_data = json_decode($result['memo_name'], true);

			if (is_array($memo_data)) {
				return $memo_data;
			}

			// JSON이 아닌 경우 쉼표로 구분된 문자열로 처리
			$memo_names = explode(',', $result['memo_name']);
			return array_map('trim', $memo_names);
		}

		return array();
	}

	/**
	 * 마스터 사용자의 조직 목록 조회 (카테고리 필터링 적용)
	 */
	public function get_user_orgs_master_filtered($user_id, $visible_categories)
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

		// 카테고리 필터링
		if (!empty($visible_categories)) {
			$this->db->where_in('o.category_idx', $visible_categories);
		}

		$this->db->order_by('o.org_type', 'ASC');
		$this->db->order_by('o.org_name', 'ASC');
		$query = $this->db->get();
		return $query->result_array();
	}

	/**
	 * 필터링된 카테고리들의 조직 목록 조회 (하위 카테고리 포함)
	 * 수정 사항: user_count 컬럼 추가
	 */
	public function get_orgs_by_filtered_categories($visible_categories)
	{
		if (empty($visible_categories)) {
			return $this->get_all_orgs_except_uncategorized();
		}

		// visible_categories와 그 하위 카테고리들의 ID 수집
		$this->load->model('Org_category_model');
		$category_ids = $this->Org_category_model->get_category_with_descendants_public($visible_categories);

		if (empty($category_ids)) {
			return array();
		}

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
        COUNT(DISTINCT m.member_idx) as member_count,
        COUNT(DISTINCT ou.user_id) as user_count
    ');

		$this->db->from('wb_org o');
		$this->db->join('wb_org_category oc', 'o.category_idx = oc.category_idx', 'left');
		$this->db->join('wb_member m', 'o.org_id = m.org_id AND m.del_yn = "N"', 'left');
		$this->db->join('wb_org_user ou', 'o.org_id = ou.org_id', 'left');
		$this->db->where('o.del_yn', 'N');
		$this->db->where_in('o.category_idx', $category_ids);

		$this->db->group_by('o.org_id');
		$this->db->order_by('o.regi_date', 'DESC');

		$query = $this->db->get();
		return $query->result_array();
	}




	/**
	 * 여러 카테고리에 속한 조직 목록 조회
	 */
	public function get_orgs_by_categories($category_idxs)
	{
		if (empty($category_idxs) || !is_array($category_idxs)) {
			return array();
		}

		$this->db->select('*');
		$this->db->from('wb_org');
		$this->db->where_in('org_category', $category_idxs);
		$this->db->where('del_yn', 'N');
		$this->db->order_by('org_name', 'ASC');

		$query = $this->db->get();
		return $query->result_array();
	}

	/**
	 * 전체 조직 목록 조회
	 */
	public function get_all_orgs()
	{
		$this->db->select('*');
		$this->db->from('wb_org');
		$this->db->where('del_yn', 'N');
		$this->db->order_by('org_name', 'ASC');

		$query = $this->db->get();
		return $query->result_array();
	}

	/**
	 * 여러 조직의 출석 타입 목록 조회 (중복 제거)
	 */
	public function get_all_attendance_types_by_orgs($org_ids)
	{
		if (empty($org_ids) || !is_array($org_ids)) {
			return array();
		}

		$this->db->select('att_type_idx, att_type_name, att_type_nickname, att_type_color, att_type_order');
		$this->db->from('wb_att_type');
		$this->db->where_in('org_id', $org_ids);
//		$this->db->where('del_yn', 'N');
		$this->db->group_by('att_type_nickname');
		$this->db->order_by('att_type_order', 'ASC');

		$query = $this->db->get();
		return $query->result_array();
	}

	/**
	 * 여러 조직의 타임라인 타입 목록 조회 (중복 제거)
	 */
	public function get_all_timeline_types_by_orgs($org_ids)
	{
		if (empty($org_ids) || !is_array($org_ids)) {
			return array();
		}

		$types = array();

		foreach ($org_ids as $org_id) {
			$org_types = $this->get_timeline_types($org_id);
			$types = array_merge($types, $org_types);
		}

		return array_values(array_unique($types));
	}

	/**
	 * 여러 조직의 메모 타입 목록 조회 (중복 제거)
	 */
	public function get_all_memo_types_by_orgs($org_ids)
	{
		if (empty($org_ids) || !is_array($org_ids)) {
			return array();
		}

		$types = array();

		foreach ($org_ids as $org_id) {
			$org_types = $this->get_memo_types($org_id);
			$types = array_merge($types, $org_types);
		}

		return array_values(array_unique($types));
	}

	/**
	 * 파일 위치: application/models/Org_model.php
	 * 역할: 카테고리와 함께 조직 목록 조회
	 */
	public function get_org_list_with_category($visible_categories = array())
	{
		$this->db->select('o.org_id, o.org_name, o.category_idx, c.category_name');
		$this->db->from('wb_org o');
		$this->db->join('wb_org_category c', 'o.category_idx = c.category_idx', 'left');
		$this->db->where('o.del_yn', 'N');

		// 권한이 있는 카테고리만 표시
		if (!empty($visible_categories)) {
			// 하위 카테고리도 포함
			$this->load->model('Org_category_model');
			$all_category_ids = array();

			foreach ($visible_categories as $cat_id) {
				$all_category_ids[] = $cat_id;
				$children = $this->Org_category_model->get_all_child_category_ids($cat_id);
				$all_category_ids = array_merge($all_category_ids, $children);
			}

			$all_category_ids = array_unique($all_category_ids);

			if (!empty($all_category_ids)) {
				$this->db->where_in('o.category_idx', $all_category_ids);
			}
		}

		$this->db->order_by('c.category_name', 'ASC');
		$this->db->order_by('o.org_name', 'ASC');

		$query = $this->db->get();
		return $query->result_array();
	}

}
