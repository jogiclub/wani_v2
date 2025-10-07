<?php
/**
 * 파일 위치: application/models/Message_model.php
 * 역할: 메시지 관련 데이터베이스 처리 (JSON 기반)
 */

defined('BASEPATH') or exit('No direct script access allowed');

class Message_model extends CI_Model
{
	public function __construct()
	{
		parent::__construct();
		$this->load->database();
	}

	/**
	 * 조직의 메시지에 사용자 동기화 (user_json에 없는 사용자 추가)
	 */
	public function sync_user_to_org_messages($user_id, $org_id)
	{
		// 해당 조직의 모든 메시지 조회
		$this->db->select('idx, user_json');
		$this->db->from('wb_message');
		$this->db->where('org_id', $org_id);
		$query = $this->db->get();
		$messages = $query->result_array();

		if (empty($messages)) {
			return true;
		}

		$this->db->trans_start();

		foreach ($messages as $message) {
			// user_json이 null이거나 빈 경우 빈 배열로 초기화
			if (empty($message['user_json'])) {
				$users = [];
			} else {
				$users = json_decode($message['user_json'], true);
				if (!is_array($users)) {
					$users = [];
				}
			}

			// 사용자가 이미 목록에 있는지 확인
			$user_exists = false;
			foreach ($users as $user) {
				if ($user['user_id'] == $user_id) {
					$user_exists = true;
					break;
				}
			}

			// 사용자가 없으면 추가
			if (!$user_exists) {
				$users[] = [
					'user_id' => $user_id,
					'read_yn' => 'N',
					'read_date' => '0000-00-00 00:00:00',
					'del_yn' => 'N'
				];

				// 업데이트
				$update_data = [
					'user_json' => json_encode($users, JSON_UNESCAPED_UNICODE),
					'modi_date' => date('Y-m-d H:i:s')
				];

				$this->db->where('idx', $message['idx']);
				$this->db->update('wb_message', $update_data);
			}
		}

		$this->db->trans_complete();

		return $this->db->trans_status();
	}

	/**
	 * 사용자의 읽지 않은 메시지 목록 조회
	 */
	public function get_unread_messages($user_id, $org_id, $limit = 20)
	{
		$this->db->select("
			idx,
			message_date,
			message_type,
			message_title,
			message_content,
			org_id,
			user_json,
			regi_date,
			modi_date
		");
		$this->db->from('wb_message');
		$this->db->where('org_id', $org_id);
		$this->db->order_by('message_date', 'DESC');
		$this->db->limit($limit);

		$query = $this->db->get();
		$messages = $query->result_array();

		// 각 메시지에서 해당 사용자의 읽음 상태 추출
		$result = [];
		foreach ($messages as $message) {
			$user_data = $this->get_user_data_from_message_with_null_check($message, $user_id);
			if ($user_data && $user_data['read_yn'] === 'N' && $user_data['del_yn'] === 'N') {
				$message['read_yn'] = $user_data['read_yn'];
				$message['read_date'] = $user_data['read_date'];
				unset($message['user_json']); // 불필요한 데이터 제거
				$result[] = $message;
			}
		}

		return $result;
	}

	/**
	 * 사용자의 최근 메시지 목록 조회 (읽음/안읽음 포함, 삭제되지 않은 메시지만)
	 */
	public function get_recent_messages($user_id, $org_id, $limit = 20)
	{
		$this->db->select("
			idx,
			message_date,
			message_type,
			message_title,
			message_content,
			org_id,
			user_json,
			regi_date,
			modi_date
		");
		$this->db->from('wb_message');
		$this->db->where('org_id', $org_id);
		$this->db->order_by('message_date', 'DESC');
		$this->db->limit($limit);

		$query = $this->db->get();
		$messages = $query->result_array();

		// 각 메시지에서 해당 사용자의 상태 추출 (삭제되지 않은 메시지만)
		$result = [];
		foreach ($messages as $message) {
			$user_data = $this->get_user_data_from_message_with_null_check($message, $user_id);
			if ($user_data && $user_data['del_yn'] === 'N') {
				$message['read_yn'] = $user_data['read_yn'];
				$message['read_date'] = $user_data['read_date'];
				unset($message['user_json']); // 불필요한 데이터 제거
				$result[] = $message;
			}
		}

		return $result;
	}

	/**
	 * 읽지 않은 메시지 수 조회
	 */
	public function get_unread_count($user_id, $org_id)
	{
		$this->db->select('idx, user_json');
		$this->db->from('wb_message');
		$this->db->where('org_id', $org_id);

		$query = $this->db->get();
		$messages = $query->result_array();

		$count = 0;
		foreach ($messages as $message) {
			$user_data = $this->get_user_data_from_message_with_null_check($message, $user_id);
			if ($user_data && $user_data['read_yn'] === 'N' && $user_data['del_yn'] === 'N') {
				$count++;
			}
		}

		return $count;
	}

	/**
	 * 메시지에서 특정 사용자의 데이터 추출 (user_json null 체크 포함)
	 */
	private function get_user_data_from_message_with_null_check($message, $user_id)
	{
		// user_json이 null이거나 빈 경우 기본값 반환 (모든 사용자가 읽지 않은 상태)
		if (empty($message['user_json'])) {
			return [
				'user_id' => $user_id,
				'read_yn' => 'N',
				'read_date' => '0000-00-00 00:00:00',
				'del_yn' => 'N'
			];
		}

		$users = json_decode($message['user_json'], true);
		if (!is_array($users)) {
			return [
				'user_id' => $user_id,
				'read_yn' => 'N',
				'read_date' => '0000-00-00 00:00:00',
				'del_yn' => 'N'
			];
		}

		// 사용자가 user_json에 있는지 확인
		foreach ($users as $user) {
			if ($user['user_id'] == $user_id) {
				return $user;
			}
		}

		// 사용자가 없으면 기본값 반환
		return [
			'user_id' => $user_id,
			'read_yn' => 'N',
			'read_date' => '0000-00-00 00:00:00',
			'del_yn' => 'N'
		];
	}

	/**
	 * 메시지에서 특정 사용자의 데이터 추출 (기존 함수 - 하위 호환성)
	 */
	private function get_user_data_from_message($message_idx, $user_id)
	{
		$this->db->select("user_json");
		$this->db->from('wb_message');
		$this->db->where('idx', $message_idx);

		$query = $this->db->get();
		$result = $query->row_array();

		if (!$result) {
			return null;
		}

		// user_json이 null이거나 빈 경우 기본값 반환
		if (empty($result['user_json'])) {
			return [
				'user_id' => $user_id,
				'read_yn' => 'N',
				'read_date' => '0000-00-00 00:00:00',
				'del_yn' => 'N'
			];
		}

		$users = json_decode($result['user_json'], true);
		if (!is_array($users)) {
			return [
				'user_id' => $user_id,
				'read_yn' => 'N',
				'read_date' => '0000-00-00 00:00:00',
				'del_yn' => 'N'
			];
		}

		foreach ($users as $user) {
			if ($user['user_id'] == $user_id) {
				return $user;
			}
		}

		// 사용자가 없으면 기본값 반환
		return [
			'user_id' => $user_id,
			'read_yn' => 'N',
			'read_date' => '0000-00-00 00:00:00',
			'del_yn' => 'N'
		];
	}

	/**
	 * 메시지를 읽음으로 표시
	 */
	public function mark_as_read($idx, $user_id)
	{
		// 현재 user_json 가져오기
		$this->db->select('user_json');
		$this->db->from('wb_message');
		$this->db->where('idx', $idx);
		$query = $this->db->get();
		$result = $query->row_array();

		if (!$result) {
			return false;
		}

		// user_json이 null이거나 빈 경우 새로 초기화
		if (empty($result['user_json'])) {
			$users = [];
		} else {
			$users = json_decode($result['user_json'], true);
			if (!is_array($users)) {
				$users = [];
			}
		}

		$updated = false;

		// 해당 사용자의 read_yn과 read_date 업데이트
		foreach ($users as &$user) {
			if ($user['user_id'] == $user_id) {
				$user['read_yn'] = 'Y';
				$user['read_date'] = date('Y-m-d H:i:s');
				$updated = true;
				break;
			}
		}

		// 사용자가 없으면 새로 추가
		if (!$updated) {
			$users[] = [
				'user_id' => $user_id,
				'read_yn' => 'Y',
				'read_date' => date('Y-m-d H:i:s'),
				'del_yn' => 'N'
			];
		}

		// 업데이트된 JSON 저장
		$update_data = array(
			'user_json' => json_encode($users, JSON_UNESCAPED_UNICODE),
			'modi_date' => date('Y-m-d H:i:s')
		);

		$this->db->where('idx', $idx);
		return $this->db->update('wb_message', $update_data);
	}

	/**
	 * 여러 메시지를 읽음으로 표시
	 */
	public function mark_multiple_as_read($message_ids, $user_id)
	{
		if (empty($message_ids)) {
			return false;
		}

		$this->db->trans_start();

		foreach ($message_ids as $message_idx) {
			$this->mark_as_read($message_idx, $user_id);
		}

		$this->db->trans_complete();

		return $this->db->trans_status();
	}

	/**
	 * 메시지 상세 조회
	 */
	public function get_message_by_idx($idx, $user_id = null)
	{
		$this->db->select('*');
		$this->db->from('wb_message');
		$this->db->where('idx', $idx);

		$query = $this->db->get();
		$message = $query->row_array();

		if (!$message) {
			return null;
		}

		// 특정 사용자의 정보 추가
		if ($user_id) {
			$user_data = $this->get_user_data_from_message($idx, $user_id);
			if ($user_data) {
				// 해당 사용자가 삭제한 메시지는 조회 불가
				if ($user_data['del_yn'] === 'Y') {
					return null;
				}
				$message['read_yn'] = $user_data['read_yn'];
				$message['read_date'] = $user_data['read_date'];
				$message['del_yn'] = $user_data['del_yn'];
			}
		}

		return $message;
	}

	/**
	 * 메시지 삭제 (소프트 삭제 - JSON 내 del_yn 업데이트)
	 */
	public function delete_message($idx, $user_id)
	{
		// 현재 user_json 가져오기
		$this->db->select('user_json');
		$this->db->from('wb_message');
		$this->db->where('idx', $idx);
		$query = $this->db->get();
		$result = $query->row_array();

		if (!$result) {
			return false;
		}

		// user_json이 null이거나 빈 경우 새로 초기화
		if (empty($result['user_json'])) {
			$users = [];
		} else {
			$users = json_decode($result['user_json'], true);
			if (!is_array($users)) {
				$users = [];
			}
		}

		$updated = false;

		// 해당 사용자의 del_yn 업데이트
		foreach ($users as &$user) {
			if ($user['user_id'] == $user_id) {
				$user['del_yn'] = 'Y';
				$updated = true;
				break;
			}
		}

		// 사용자가 없으면 새로 추가 (삭제 상태로)
		if (!$updated) {
			$users[] = [
				'user_id' => $user_id,
				'read_yn' => 'N',
				'read_date' => '0000-00-00 00:00:00',
				'del_yn' => 'Y'
			];
		}

		// 업데이트된 JSON 저장
		$update_data = array(
			'user_json' => json_encode($users, JSON_UNESCAPED_UNICODE),
			'modi_date' => date('Y-m-d H:i:s')
		);

		$this->db->where('idx', $idx);
		return $this->db->update('wb_message', $update_data);
	}

	/**
	 * 사용자의 모든 메시지 삭제 (소프트 삭제)
	 */
	public function delete_all_messages($user_id, $org_id)
	{
		// 해당 조직의 모든 메시지 조회
		$this->db->select('idx, user_json');
		$this->db->from('wb_message');
		$this->db->where('org_id', $org_id);

		$query = $this->db->get();
		$messages = $query->result_array();

		if (empty($messages)) {
			return true;
		}

		$this->db->trans_start();

		foreach ($messages as $message) {
			// 해당 사용자가 삭제하지 않은 메시지만 삭제 처리
			$user_data = $this->get_user_data_from_message_with_null_check($message, $user_id);
			if ($user_data && $user_data['del_yn'] === 'N') {
				$this->delete_message($message['idx'], $user_id);
			}
		}

		$this->db->trans_complete();

		return $this->db->trans_status();
	}

	/**
	 * 역할: 메시지 생성 (member_idx_list 필드 지원)
	 */
	public function create_message($message_data)
	{
		$message_data['message_date'] = isset($message_data['message_date']) ?
			$message_data['message_date'] : date('Y-m-d H:i:s');

		// user_json이 배열로 전달된 경우 JSON으로 변환
		if (isset($message_data['user_json']) && is_array($message_data['user_json'])) {
			$message_data['user_json'] = json_encode($message_data['user_json'], JSON_UNESCAPED_UNICODE);
		}

		// member_idx_list가 배열로 전달된 경우 JSON으로 변환
		if (isset($message_data['member_idx_list']) && is_array($message_data['member_idx_list'])) {
			$message_data['member_idx_list'] = json_encode($message_data['member_idx_list'], JSON_UNESCAPED_UNICODE);
		}

		return $this->db->insert('wb_message', $message_data);
	}

	/**
	 * 조직의 모든 사용자에게 메시지 발송
	 */
	public function send_message_to_org($org_id, $message_data)
	{
		// 조직의 모든 활성 사용자 조회
		$this->db->select('wou.user_id');
		$this->db->from('wb_org_user wou');
		$this->db->join('wb_user wu', 'wou.user_id = wu.user_id');
		$this->db->where('wou.org_id', $org_id);
		$this->db->where('wu.del_yn', 'N');
		$query = $this->db->get();
		$users = $query->result_array();

		if (empty($users)) {
			return false;
		}

		// user_json 생성
		$user_json = [];
		foreach ($users as $user) {
			$user_json[] = [
				'user_id' => $user['user_id'],
				'read_yn' => 'N',
				'read_date' => '0000-00-00 00:00:00',
				'del_yn' => 'N'
			];
		}

		$message_data['user_json'] = $user_json;
		$message_data['org_id'] = $org_id;

		return $this->create_message($message_data);
	}

	/**
	 * 특정 그룹(area)의 사용자들에게 메시지 발송
	 */
	public function send_message_to_group($org_id, $area_idx, $message_data)
	{
		// 해당 그룹의 모든 회원의 등록 사용자 조회
		$this->db->select('DISTINCT wou.user_id');
		$this->db->from('wb_member wm');
		$this->db->join('wb_org_user wou', 'wm.regi_user_id = wou.user_id AND wm.org_id = wou.org_id');
		$this->db->join('wb_user wu', 'wou.user_id = wu.user_id');
		$this->db->where('wm.org_id', $org_id);
		$this->db->where('wm.area_idx', $area_idx);
		$this->db->where('wm.del_yn', 'N');
		$this->db->where('wu.del_yn', 'N');
		$query = $this->db->get();
		$users = $query->result_array();

		if (empty($users)) {
			return false;
		}

		// user_json 생성
		$user_json = [];
		foreach ($users as $user) {
			$user_json[] = [
				'user_id' => $user['user_id'],
				'read_yn' => 'N',
				'read_date' => '0000-00-00 00:00:00',
				'del_yn' => 'N'
			];
		}

		$message_data['user_json'] = $user_json;
		$message_data['org_id'] = $org_id;

		return $this->create_message($message_data);
	}

	/**
	 * 특정 사용자들에게 메시지 발송
	 */
	public function send_message_to_users($org_id, $user_ids, $message_data)
	{
		if (empty($user_ids)) {
			return false;
		}

		// user_json 생성
		$user_json = [];
		foreach ($user_ids as $user_id) {
			$user_json[] = [
				'user_id' => $user_id,
				'read_yn' => 'N',
				'read_date' => '0000-00-00 00:00:00',
				'del_yn' => 'N'
			];
		}

		$message_data['user_json'] = $user_json;
		$message_data['org_id'] = $org_id;

		return $this->create_message($message_data);
	}

	/**
	 * 메시지 통계 조회
	 */
	public function get_message_statistics($org_id, $start_date = null, $end_date = null)
	{
		$this->db->select('
			COUNT(*) as total_messages,
			message_type,
			DATE(message_date) as message_date
		');
		$this->db->from('wb_message');
		$this->db->where('org_id', $org_id);

		if ($start_date) {
			$this->db->where('DATE(message_date) >=', $start_date);
		}

		if ($end_date) {
			$this->db->where('DATE(message_date) <=', $end_date);
		}

		$this->db->group_by('message_type, DATE(message_date)');
		$this->db->order_by('message_date', 'DESC');

		$query = $this->db->get();
		return $query->result_array();
	}

	/**
	 * 메시지 타입별 메시지 수 조회
	 */
	public function get_message_count_by_type($user_id, $org_id, $message_type = null)
	{
		$this->db->select('message_type, COUNT(*) as count');
		$this->db->from('wb_message');
		$this->db->where('org_id', $org_id);

		if ($message_type) {
			$this->db->where('message_type', $message_type);
		}

		$this->db->group_by('message_type');
		$query = $this->db->get();
		$messages = $query->result_array();

		// 읽지 않고 삭제되지 않은 메시지만 카운트
		$result = [];
		foreach ($messages as $msg_type) {
			$this->db->select('idx, user_json');
			$this->db->from('wb_message');
			$this->db->where('org_id', $org_id);
			$this->db->where('message_type', $msg_type['message_type']);

			$query2 = $this->db->get();
			$type_messages = $query2->result_array();

			$unread_count = 0;
			foreach ($type_messages as $message) {
				$user_data = $this->get_user_data_from_message_with_null_check($message, $user_id);
				if ($user_data && $user_data['read_yn'] === 'N' && $user_data['del_yn'] === 'N') {
					$unread_count++;
				}
			}

			if ($unread_count > 0) {
				$result[] = [
					'message_type' => $msg_type['message_type'],
					'count' => $unread_count
				];
			}
		}

		return $result;
	}

	/**
	 * 메시지 물리적 삭제 (모든 수신자가 삭제한 경우에만 실제 삭제)
	 */
	public function clean_deleted_messages($org_id)
	{
		// 조직의 모든 메시지 조회
		$this->db->select('idx, user_json');
		$this->db->from('wb_message');
		$this->db->where('org_id', $org_id);
		$query = $this->db->get();
		$messages = $query->result_array();

		$delete_ids = [];

		foreach ($messages as $message) {
			// user_json이 null이거나 빈 경우는 삭제하지 않음
			if (empty($message['user_json'])) {
				continue;
			}

			$users = json_decode($message['user_json'], true);
			if (!is_array($users) || empty($users)) {
				continue;
			}

			// 모든 사용자가 삭제했는지 확인
			$all_deleted = true;
			foreach ($users as $user) {
				if ($user['del_yn'] === 'N') {
					$all_deleted = false;
					break;
				}
			}

			if ($all_deleted) {
				$delete_ids[] = $message['idx'];
			}
		}

		// 실제 삭제
		if (!empty($delete_ids)) {
			$this->db->where_in('idx', $delete_ids);
			return $this->db->delete('wb_message');
		}

		return true;
	}
}
