<?php
// application/models/User_management_model.php
// 사용자 관리 모델 - 사용자 정보 조회 및 관리 기능

defined('BASEPATH') or exit('No direct script access allowed');

class User_management_model extends CI_Model
{

	public function __construct()
	{
		parent::__construct();
		$this->load->database();
	}

	/**
	 * 사용자가 접근 가능한 조직 목록 가져오기 (손님)
	 */
	public function get_user_orgs($user_id)
	{
		$this->db->select('wb_org.org_id, wb_org.org_name, wb_org.org_type, wb_org.org_icon, wb_org_user.level');
		$this->db->from('wb_org');
		$this->db->join('wb_org_user', 'wb_org.org_id = wb_org_user.org_id');
		$this->db->where('wb_org_user.user_id', $user_id);
		$this->db->where('wb_org.del_yn', 'N');
		$this->db->order_by('wb_org.org_name', 'ASC');
		$query = $this->db->get();
		return $query->result_array();
	}

	/**
	 * 사용자가 접근 가능한 조직 목록 가져오기 (마스터 사용자)
	 */
	public function get_user_orgs_master($user_id)
	{
		$this->db->select('wb_org.org_id, wb_org.org_name, wb_org.org_type, wb_org.org_icon, 10 as level');
		$this->db->from('wb_org');
		$this->db->where('wb_org.del_yn', 'N');
		$this->db->order_by('wb_org.org_name', 'ASC');
		$query = $this->db->get();
		return $query->result_array();
	}

	/**
	 * 조직 상세 정보 가져오기
	 */
	public function get_org_detail_by_id($org_id)
	{
		$this->db->select('org_id, org_name, org_type, org_desc, org_icon, leader_name, new_name, invite_code');
		$this->db->from('wb_org');
		$this->db->where('org_id', $org_id);
		$this->db->where('del_yn', 'N');
		$query = $this->db->get();
		return $query->row_array();
	}

	/**
	 * 사용자의 조직 내 권한 레벨 가져오기
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
	 * 조직의 사용자 수 가져오기
	 */
	public function get_org_user_count($org_id)
	{
		$this->db->from('wb_org_user');
		$this->db->join('wb_user', 'wb_org_user.user_id = wb_user.user_id');
		$this->db->where('wb_org_user.org_id', $org_id);
		$this->db->where('wb_user.del_yn', 'N');
		return $this->db->count_all_results();
	}


	/**
	 * 파일 위치: application/models/User_management_model.php
	 * 역할: 조직의 사용자 목록 조회 (관리 메뉴/그룹 표시 정보 포함)
	 */
	public function get_org_users($org_id)
	{
		$this->db->select('
            wb_user.user_id,
            wb_user.user_name,
            wb_user.user_mail,
            wb_user.user_hp,
            wb_user.user_profile_image,
            wb_user.master_yn,
            wb_user.managed_menus,
            wb_user.managed_areas,
            wb_org_user.level,
            wb_org_user.org_id,
            wb_org_user.join_date
        ');
		$this->db->from('wb_org_user');
		$this->db->join('wb_user', 'wb_org_user.user_id = wb_user.user_id');
		$this->db->where('wb_org_user.org_id', $org_id);
		$this->db->where('wb_user.del_yn', 'N');
		$this->db->order_by('wb_org_user.level', 'DESC');
		$this->db->order_by('wb_user.user_name', 'ASC');

		$query = $this->db->get();
		$users = $query->result_array();

		// 각 사용자의 관리 메뉴/그룹 표시 정보 생성
		foreach ($users as &$user) {
			$user['managed_menus_display'] = $this->get_managed_menus_display($user['managed_menus']);
			$user['managed_areas_display'] = $this->get_managed_areas_display($user['managed_areas'], $org_id);
		}

		return $users;
	}


	/**
	 * 관리메뉴 표시 정보 생성
	 */
	private function get_managed_menus_display($managed_menus_json)
	{
		if (empty($managed_menus_json)) {
			return array();
		}

		$managed_menus = json_decode($managed_menus_json, true);
		if (!is_array($managed_menus)) {
			return array();
		}

		// 메뉴 헬퍼 로드
		$this->load->helper('menu');
		$system_menus = get_system_menus();

		$display_menus = array();
		foreach ($managed_menus as $menu_key) {
			if (isset($system_menus[$menu_key]['name'])) {
				$display_menus[] = $system_menus[$menu_key]['name'];
			} else {
				$display_menus[] = $menu_key; // 메뉴 정보가 없으면 키 값 자체를 표시
			}
		}

		return $display_menus;
	}

	/**
	 * 관리그룹 표시 정보 생성
	 */
	private function get_managed_areas_display($managed_areas_json, $org_id)
	{
		if (empty($managed_areas_json)) {
			return array();
		}

		$managed_areas = json_decode($managed_areas_json, true);
		if (!is_array($managed_areas)) {
			return array();
		}

		// 관리그룹 이름 조회
		if (!empty($managed_areas)) {
			$this->db->select('area_idx, area_name');
			$this->db->from('wb_member_area');
			$this->db->where('org_id', $org_id);
			$this->db->where_in('area_idx', $managed_areas);
			$this->db->order_by('area_order', 'ASC');
			$query = $this->db->get();
			$areas = $query->result_array();

			$display_areas = array();
			foreach ($areas as $area) {
				$display_areas[] = $area['area_name'];
			}

			return $display_areas;
		}

		return array();
	}

	/**
	 * 파일 위치: application/models/User_management_model.php
	 * 역할: 사용자 정보 업데이트 (관리 메뉴 및 그룹 포함)
	 */
	public function update_user_info($target_user_id, $org_id, $user_name, $user_hp, $level, $managed_menus = null, $managed_areas = null)
	{
		$this->db->trans_start();

		// wb_org_user 테이블 업데이트
		$org_user_data = array(
			'level' => $level
		);

		$this->db->where('user_id', $target_user_id);
		$this->db->where('org_id', $org_id);
		$this->db->update('wb_org_user', $org_user_data);

		// wb_user 테이블 업데이트
		$user_data = array(
			'user_name' => $user_name,
			'user_hp' => $user_hp,
			'modi_date' => date('Y-m-d H:i:s')
		);

		// 최고관리자가 아닌 경우에만 관리 메뉴/그룹 필드 업데이트
		if ($managed_menus !== null) {
			$user_data['managed_menus'] = $managed_menus;
		}
		if ($managed_areas !== null) {
			$user_data['managed_areas'] = $managed_areas;
		}

		$this->db->where('user_id', $target_user_id);
		$this->db->update('wb_user', $user_data);

		$this->db->trans_complete();

		return $this->db->trans_status();
	}

	/**
	 * 조직에서 사용자 제외 (안전한 버전)
	 */
	public function delete_org_user($target_user_id, $org_id)
	{
		// 삭제 전 사용자 존재 여부 확인 (별도 쿼리로)
		$this->db->select('user_id');
		$this->db->where('user_id', $target_user_id);
		$this->db->where('org_id', $org_id);
		$exists_query = $this->db->get('wb_org_user');

		if ($exists_query->num_rows() == 0) {
			log_message('error', "Delete user failed: User {$target_user_id} not found in org {$org_id}");
			return false;
		}

		// 트랜잭션 시작
		$this->db->trans_start();

		// wb_org_user 테이블에서 해당 사용자 삭제
		$this->db->where('user_id', $target_user_id);
		$this->db->where('org_id', $org_id);
		$delete_result = $this->db->delete('wb_org_user');

		// 트랜잭션 완료
		$this->db->trans_complete();

		// 트랜잭션 상태 확인
		if ($this->db->trans_status() === FALSE) {
			log_message('error', "Delete user transaction failed: User {$target_user_id} in org {$org_id}");
			return false;
		}

		// 실제 삭제된 행 수 확인
		$affected_rows = $this->db->affected_rows();
		log_message('debug', "Delete user result: User {$target_user_id} in org {$org_id}, affected rows: {$affected_rows}");

		return $affected_rows > 0;
	}



	/**
	 * 사용자가 조직에 속해있는지 확인
	 */
	public function check_user_in_org($user_email, $org_id)
	{
		$this->db->select('wb_org_user.idx');
		$this->db->from('wb_user');
		$this->db->join('wb_org_user', 'wb_user.user_id = wb_org_user.user_id');
		$this->db->where('wb_user.user_mail', $user_email);
		$this->db->where('wb_org_user.org_id', $org_id);
		$this->db->where('wb_user.del_yn', 'N');
		$query = $this->db->get();
		return $query->row_array();
	}

	/**
	 * 초대 메일 발송 (실제 메일 발송 로직은 별도 구현 필요)
	 */
	public function send_invite_email($invite_email, $org_id, $inviter_id)
	{
		// 조직 정보 가져오기
		$org_info = $this->get_org_detail_by_id($org_id);
		if (!$org_info) {
			return false;
		}

		// 초대자 정보 가져오기
		$inviter_info = $this->get_user_info($inviter_id);
		if (!$inviter_info) {
			return false;
		}

		// 초대 로그 저장 (필요한 경우)
		$invite_data = array(
			'invite_email' => $invite_email,
			'org_id' => $org_id,
			'inviter_id' => $inviter_id,
			'invite_date' => date('Y-m-d H:i:s'),
			'status' => 'sent'
		);

		// wb_invite 테이블이 있다면 저장 (없으면 주석 처리)
		// $this->db->insert('wb_invite', $invite_data);

		// 실제 메일 발송 로직
		$this->load->library('email');

		$config['protocol'] = 'smtp';
		$config['smtp_host'] = 'smtp.example.com'; // SMTP 서버 설정 필요
		$config['smtp_user'] = 'noreply@example.com'; // 발송 이메일 설정 필요
		$config['smtp_pass'] = 'password'; // 비밀번호 설정 필요
		$config['smtp_port'] = 587;
		$config['charset'] = 'utf-8';
		$config['wordwrap'] = TRUE;
		$config['mailtype'] = 'html';

		$this->email->initialize($config);

		$this->email->from('noreply@example.com', '왔니');
		$this->email->to($invite_email);
		$this->email->subject($org_info['org_name'] . ' 조직 초대');

		$message = "
			<h3>" . $org_info['org_name'] . "에 초대되었습니다</h3>
			<p>" . $inviter_info['user_name'] . "님이 회원님을 " . $org_info['org_name'] . " 조직에 초대하였습니다.</p>
			<p>초대코드: <strong>" . $org_info['invite_code'] . "</strong></p>
			<p><a href='" . base_url('login') . "'>로그인하여 참여하기</a></p>
		";

		$this->email->message($message);

		// 개발 환경에서는 실제 메일 발송 대신 로그만 기록
		if (ENVIRONMENT === 'development') {
			log_message('info', 'Invite email would be sent to: ' . $invite_email);
			return true;
		}

		return $this->email->send();
	}

	/**
	 * 사용자 정보 가져오기
	 */
	public function get_user_info($user_id)
	{
		$this->db->select('user_id, user_name, user_mail, user_hp');
		$this->db->from('wb_user');
		$this->db->where('user_id', $user_id);
		$this->db->where('del_yn', 'N');
		$query = $this->db->get();
		return $query->row_array();
	}

	/**
	 * 파일 위치: application/models/User_management_model.php
	 * 역할: 조직의 관리 그룹(영역) 목록 조회
	 */
	public function get_org_areas($org_id)
	{
		$this->db->select('area_idx, area_name');
		$this->db->from('wb_member_area');
		$this->db->where('org_id', $org_id);
		$this->db->order_by('area_order', 'ASC');
		$query = $this->db->get();

		return $query->result_array();
	}


	/**
	 * 파일 위치: application/models/User_management_model.php
	 * 역할: 사용자의 관리 메뉴 조회
	 */
	public function get_user_managed_menus($user_id)
	{
		$this->db->select('managed_menus');
		$this->db->from('wb_user');
		$this->db->where('user_id', $user_id);
		$query = $this->db->get();
		$result = $query->row_array();

		if ($result && !empty($result['managed_menus'])) {
			$menus = json_decode($result['managed_menus'], true);
			return is_array($menus) ? $menus : array();
		}

		return array();
	}

	/**
	 * 파일 위치: application/models/User_management_model.php
	 * 역할: 사용자의 관리 그룹 정보 조회 (JSON 배열 반환)
	 */
	public function get_user_managed_areas($user_id)
	{
		$this->db->select('managed_areas');
		$this->db->from('wb_user');
		$this->db->where('user_id', $user_id);
		$query = $this->db->get();
		$result = $query->row_array();

		if ($result && !empty($result['managed_areas'])) {
			$areas = json_decode($result['managed_areas'], true);
			return is_array($areas) ? $areas : array();
		}

		return array();
	}

	/**
	 * 파일 위치: application/models/User_management_model.php
	 * 역할: 사용자 권한에 따라 필터링된 조직 그룹 목록 조회
	 */
	public function get_user_accessible_areas($user_id, $org_id)
	{
		// 사용자 권한 레벨 확인
		$user_level = $this->get_org_user_level($user_id, $org_id);

		// 최고관리자(레벨 10) 또는 마스터인 경우 모든 그룹 반환
		if ($user_level >= 10) {
			return $this->get_org_areas($org_id);
		}

		// 일반 관리자인 경우 관리 권한이 있는 그룹만 반환
		$managed_areas = $this->get_user_managed_areas($user_id);

		if (empty($managed_areas)) {
			return array();
		}

		// 관리 권한이 있는 그룹만 필터링
		$this->db->select('area_idx, area_name');
		$this->db->from('wb_member_area');
		$this->db->where('org_id', $org_id);
		$this->db->where_in('area_idx', $managed_areas);
		$this->db->order_by('area_order', 'ASC');
		$query = $this->db->get();
		return $query->result_array();
	}

	/**
	 * 파일 위치: application/models/User_management_model.php
	 * 역할: 사용자가 특정 그룹에 대한 권한이 있는지 확인
	 */
	public function check_user_area_permission($user_id, $org_id, $area_idx)
	{
		// 사용자 권한 레벨 확인
		$user_level = $this->get_org_user_level($user_id, $org_id);

		// 최고관리자(레벨 10)인 경우 모든 권한
		if ($user_level >= 10) {
			return true;
		}

		// 일반 관리자인 경우 관리 권한이 있는 그룹인지 확인
		$managed_areas = $this->get_user_managed_areas($user_id);

		return in_array($area_idx, $managed_areas);
	}



	/**
	 * 파일 위치: application/models/User_management_model.php
	 * 역할: 사용자의 관리 그룹과 모든 하위 그룹 ID 조회 (1학년 권한이 있으면 1반, 2반, 3반도 포함)
	 */
	public function get_user_managed_areas_with_children($user_id, $org_id)
	{
		// 사용자의 직접 관리 그룹 가져오기
		$managed_areas = $this->get_user_managed_areas($user_id);

		if (empty($managed_areas)) {
			return array();
		}

		$all_accessible_areas = $managed_areas;

		// 각 관리 그룹의 모든 하위 그룹도 포함
		foreach ($managed_areas as $area_idx) {
			$child_areas = $this->get_all_child_area_ids($area_idx, $org_id);
			$all_accessible_areas = array_merge($all_accessible_areas, $child_areas);
		}

		// 중복 제거
		return array_unique($all_accessible_areas);
	}


	/**
	 * 파일 위치: application/models/User_management_model.php
	 * 역할: 특정 그룹의 모든 하위 그룹 ID를 재귀적으로 가져오기
	 */
	private function get_all_child_area_ids($parent_area_idx, $org_id)
	{
		$area_ids = array();

		// 직접 하위 영역들 조회
		$this->db->select('area_idx');
		$this->db->from('wb_member_area');
		$this->db->where('org_id', $org_id);
		$this->db->where('parent_idx', $parent_area_idx);

		$query = $this->db->get();
		$child_areas = $query->result_array();

		foreach ($child_areas as $child_area) {
			$area_ids[] = $child_area['area_idx'];

			// 재귀적으로 하위의 하위 영역들도 가져오기
			$grandchild_ids = $this->get_all_child_area_ids($child_area['area_idx'], $org_id);
			$area_ids = array_merge($area_ids, $grandchild_ids);
		}

		return $area_ids;
	}



	/**
	 * 파일 위치: application/models/User_management_model.php
	 * 역할: 사용자 기본 정보 업데이트 (이름, 이메일, 연락처)
	 */
	public function update_user_basic_info($target_user_id, $user_name, $user_mail, $user_hp)
	{
		$this->db->trans_start();

		// wb_user 테이블 업데이트
		$user_data = array(
			'user_name' => $user_name,
			'user_mail' => $user_mail,
			'user_hp' => $user_hp,
			'modi_date' => date('Y-m-d H:i:s')
		);

		$this->db->where('user_id', $target_user_id);
		$this->db->update('wb_user', $user_data);

		$this->db->trans_complete();

		return $this->db->trans_status();
	}


	/**
	 * 파일 위치: application/models/User_management_model.php
	 * 역할: 사용자 권한 일괄 수정 (레벨, 관리메뉴, 관리그룹)
	 */
	public function bulk_update_user_permissions($target_user_id, $org_id, $level = null, $managed_menus = null, $managed_areas = null)
	{
		$this->db->trans_start();

		// 레벨 업데이트 (값이 있는 경우에만)
		if ($level !== null) {
			$org_user_data = array('level' => $level);
			$this->db->where('user_id', $target_user_id);
			$this->db->where('org_id', $org_id);
			$this->db->update('wb_org_user', $org_user_data);
		}

		// 관리 메뉴/그룹 업데이트 (값이 있는 경우에만)
		$user_data = array();

		if ($managed_menus !== null) {
			$user_data['managed_menus'] = $managed_menus;
		}

		if ($managed_areas !== null) {
			$user_data['managed_areas'] = $managed_areas;
		}

		// 업데이트할 데이터가 있는 경우에만 실행
		if (!empty($user_data)) {
			$user_data['modi_date'] = date('Y-m-d H:i:s');
			$this->db->where('user_id', $target_user_id);
			$this->db->update('wb_user', $user_data);
		}

		$this->db->trans_complete();

		return $this->db->trans_status();
	}

	/**
	 * 파일 위치: application/models/User_management_model.php
	 * 역할: 사용자가 특정 그룹에 대한 권한이 있는지 확인 (부모 그룹 권한 포함)
	 */
	public function check_user_area_permission_with_parents($user_id, $org_id, $area_idx)
	{
		// 사용자 권한 레벨 확인
		$user_level = $this->get_org_user_level($user_id, $org_id);

		// 최고관리자(레벨 10)인 경우 모든 권한
		if ($user_level >= 10) {
			return true;
		}

		// 사용자의 관리 가능한 모든 그룹 (하위 그룹 포함)
		$accessible_areas = $this->get_user_managed_areas_with_children($user_id, $org_id);

		return in_array($area_idx, $accessible_areas);
	}


	/**
	 * 조직 내 특정 사용자 정보 가져오기
	 */
	public function get_org_user_info($user_id, $org_id)
	{
		$this->db->select('wb_user.user_id, wb_user.user_name, wb_user.user_mail, wb_org_user.level');
		$this->db->from('wb_user');
		$this->db->join('wb_org_user', 'wb_user.user_id = wb_org_user.user_id');
		$this->db->where('wb_user.user_id', $user_id);
		$this->db->where('wb_org_user.org_id', $org_id);
		$this->db->where('wb_user.del_yn', 'N');
		$query = $this->db->get();
		return $query->row_array();
	}

}
