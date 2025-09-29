<?php
/**
 * 역할: 문자 발송 관련 데이터베이스 작업을 처리하는 모델
 */
defined('BASEPATH') or exit('No direct script access allowed');

class Send_model extends CI_Model
{
	public function __construct()
	{
		parent::__construct();
		$this->load->database();
	}

	/**
	 * 선택된 회원들의 정보 조회
	 */
	public function get_selected_members($member_ids, $org_id)
	{
		if (empty($member_ids)) {
			return array();
		}

		$this->db->select('m.member_idx, m.member_name, m.member_phone, a.area_name');
		$this->db->from('wb_member m');
		$this->db->join('wb_member_area a', 'm.area_idx = a.area_idx', 'left');
		$this->db->where('m.org_id', $org_id);
		$this->db->where_in('m.member_idx', $member_ids);
		$this->db->where('m.del_yn', 'N');
		$this->db->where('m.member_phone IS NOT NULL');
		$this->db->where('m.member_phone !=', '');
		$this->db->order_by('m.member_name', 'ASC');

		$query = $this->db->get();
		return $query->result_array();
	}

	/**
	 * 발신번호 목록 조회
	 */
	public function get_sender_numbers($org_id)
	{
		$this->db->select('sender_idx, sender_name, sender_number, is_default');
		$this->db->from('wb_sender_number');
		$this->db->where('org_id', $org_id);
		$this->db->where('active_yn', 'Y');
		$this->db->order_by('is_default', 'DESC');
		$this->db->order_by('sender_name', 'ASC');

		$query = $this->db->get();
		return $query->result_array();
	}

	/**
	 * 메시지 템플릿 목록 조회
	 */
	public function get_message_templates($org_id)
	{
		$this->db->select('template_idx, template_name, template_content');
		$this->db->from('wb_message_template');
		$this->db->where('org_id', $org_id);
		$this->db->where('active_yn', 'Y');
		$this->db->order_by('template_name', 'ASC');

		$query = $this->db->get();
		return $query->result_array();
	}

	/**
	 * 문자 발송 로그 저장
	 */
	public function save_send_log($data)
	{
		return $this->db->insert('wb_send_log', $data);
	}

	/**
	 * 발송 이력 조회
	 */
	public function get_send_history($org_id, $page = 1, $per_page = 20)
	{
		$offset = ($page - 1) * $per_page;

		// 전체 개수 조회
		$this->db->from('wb_send_log');
		$this->db->where('org_id', $org_id);
		$total_count = $this->db->count_all_results();

		// 목록 조회
		$this->db->select('sl.*, m.member_name, u.user_name as sender_name');
		$this->db->from('wb_send_log sl');
		$this->db->join('wb_member m', 'sl.member_idx = m.member_idx', 'left');
		$this->db->join('wb_user u', 'sl.sender_id = u.user_id', 'left');
		$this->db->where('sl.org_id', $org_id);
		$this->db->order_by('sl.send_date', 'DESC');
		$this->db->limit($per_page, $offset);

		$query = $this->db->get();
		$list = $query->result_array();

		return array(
			'list' => $list,
			'total_count' => $total_count,
			'current_page' => $page,
			'per_page' => $per_page,
			'total_pages' => ceil($total_count / $per_page)
		);
	}

	/**
	 * 메시지 템플릿 저장
	 */
	public function save_message_template($data)
	{
		return $this->db->insert('wb_message_template', $data);
	}

	/**
	 * 메시지 템플릿 수정
	 */
	public function update_message_template($template_idx, $data)
	{
		$this->db->where('template_idx', $template_idx);
		return $this->db->update('wb_message_template', $data);
	}

	/**
	 * 메시지 템플릿 삭제
	 */
	public function delete_message_template($template_idx, $org_id)
	{
		$this->db->where('template_idx', $template_idx);
		$this->db->where('org_id', $org_id);
		return $this->db->update('wb_message_template', array('active_yn' => 'N'));
	}

	/**
	 * 발신번호 저장
	 */
	public function save_sender_number($data)
	{
		return $this->db->insert('wb_sender_number', $data);
	}

	/**
	 * 발신번호 수정
	 */
	public function update_sender_number($sender_idx, $data)
	{
		$this->db->where('sender_idx', $sender_idx);
		return $this->db->update('wb_sender_number', $data);
	}

	/**
	 * 발신번호 삭제
	 */
	public function delete_sender_number($sender_idx, $org_id)
	{
		$this->db->where('sender_idx', $sender_idx);
		$this->db->where('org_id', $org_id);
		return $this->db->update('wb_sender_number', array('active_yn' => 'N'));
	}

	/**
	 * 기본 발신번호 설정
	 */
	public function set_default_sender_number($sender_idx, $org_id)
	{
		// 모든 발신번호의 기본값 해제
		$this->db->where('org_id', $org_id);
		$this->db->update('wb_sender_number', array('is_default' => 'N'));

		// 선택된 발신번호를 기본값으로 설정
		$this->db->where('sender_idx', $sender_idx);
		$this->db->where('org_id', $org_id);
		return $this->db->update('wb_sender_number', array('is_default' => 'Y'));
	}

	/**
	 * 발송 통계 조회
	 */
	public function get_send_statistics($org_id, $start_date = null, $end_date = null)
	{
		$this->db->select('
            send_type,
            send_status,
            COUNT(*) as count,
            DATE(send_date) as send_date
        ');
		$this->db->from('wb_send_log');
		$this->db->where('org_id', $org_id);

		if ($start_date) {
			$this->db->where('send_date >=', $start_date);
		}
		if ($end_date) {
			$this->db->where('send_date <=', $end_date);
		}

		$this->db->group_by(array('send_type', 'send_status', 'DATE(send_date)'));
		$this->db->order_by('send_date', 'DESC');

		$query = $this->db->get();
		return $query->result_array();
	}


	/**
	 * 충전 패키지 목록 조회
	 */
	public function get_charge_packages()
	{
		$this->db->select('*');
		$this->db->from('wb_sms_package');
		$this->db->where('active_yn', 'Y');
		$this->db->order_by('display_order', 'ASC');

		$query = $this->db->get();
		return $query->result_array();
	}

	/**
	 * 조직 문자 잔액 조회
	 */
	public function get_org_balance($org_id)
	{
		$this->db->select('balance');
		$this->db->from('wb_org_sms_balance');
		$this->db->where('org_id', $org_id);

		$query = $this->db->get();
		$result = $query->row_array();

		if (!$result) {
			// 잔액 레코드가 없으면 생성
			$this->db->insert('wb_org_sms_balance', array(
				'org_id' => $org_id,
				'balance' => 0
			));
			return 0;
		}

		return $result['balance'];
	}

	/**
	 * 문자 충전 처리
	 */
	public function charge_sms($org_id, $user_id, $package_idx, $charge_amount)
	{
		// 트랜잭션 시작
		$this->db->trans_begin();

		try {
			// 충전 내역 저장
			$history_data = array(
				'org_id' => (int)$org_id,
				'user_id' => $user_id,
				'package_idx' => (int)$package_idx,
				'charge_amount' => (int)$charge_amount,
				'payment_method' => 'card',
				'payment_status' => 'completed',
				'charge_date' => date('Y-m-d H:i:s')
			);

			// 디버깅 로그
			log_message('debug', 'Charge SMS Model - Data: ' . json_encode($history_data));

			$insert_result = $this->db->insert('wb_sms_charge_history', $history_data);

			if (!$insert_result) {
				log_message('error', 'Charge history insert failed: ' . $this->db->error()['message']);
				throw new Exception('충전 내역 저장 실패');
			}

			// 잔액 업데이트 - 기존 레코드가 있는지 확인
			$this->db->select('balance_idx');
			$this->db->from('wb_org_sms_balance');
			$this->db->where('org_id', (int)$org_id);
			$balance_exists = $this->db->get()->row_array();

			if ($balance_exists) {
				// 기존 레코드가 있으면 업데이트
				$this->db->set('balance', 'balance + ' . (int)$charge_amount, FALSE);
				$this->db->set('updated_date', date('Y-m-d H:i:s'));
				$this->db->where('org_id', (int)$org_id);
				$update_result = $this->db->update('wb_org_sms_balance');

				if (!$update_result) {
					log_message('error', 'Balance update failed: ' . $this->db->error()['message']);
					throw new Exception('잔액 업데이트 실패');
				}
			} else {
				// 레코드가 없으면 새로 생성
				$balance_data = array(
					'org_id' => (int)$org_id,
					'balance' => (int)$charge_amount,
					'updated_date' => date('Y-m-d H:i:s')
				);
				$insert_balance = $this->db->insert('wb_org_sms_balance', $balance_data);

				if (!$insert_balance) {
					log_message('error', 'Balance insert failed: ' . $this->db->error()['message']);
					throw new Exception('잔액 생성 실패');
				}
			}

			// 트랜잭션 커밋
			$this->db->trans_commit();
			return TRUE;

		} catch (Exception $e) {
			// 트랜잭션 롤백
			$this->db->trans_rollback();
			log_message('error', 'Charge SMS failed: ' . $e->getMessage());
			return FALSE;
		}
	}

	/**
	 * 충전 내역 조회
	 */
	public function get_charge_history($org_id, $page = 1, $per_page = 10)
	{
		$offset = ($page - 1) * $per_page;

		// 전체 개수
		$this->db->from('wb_sms_charge_history');
		$this->db->where('org_id', $org_id);
		$total_count = $this->db->count_all_results();

		// 목록 조회
		$this->db->select('h.*, u.user_name, p.package_name');
		$this->db->from('wb_sms_charge_history h');
		$this->db->join('wb_user u', 'h.user_id = u.user_id', 'left');
		$this->db->join('wb_sms_package p', 'h.package_idx = p.package_idx', 'left');
		$this->db->where('h.org_id', $org_id);
		$this->db->order_by('h.charge_date', 'DESC');
		$this->db->limit($per_page, $offset);

		$query = $this->db->get();
		$list = $query->result_array();

		return array(
			'list' => $list,
			'total_count' => $total_count,
			'current_page' => $page,
			'per_page' => $per_page,
			'total_pages' => ceil($total_count / $per_page)
		);
	}

	/**
	 * 발신번호 목록 조회 (인증 상태 포함)
	 */
	public function get_sender_numbers_with_auth($org_id)
	{
		$this->db->select('sender_idx, sender_name, sender_number, is_default, auth_status');
		$this->db->from('wb_sender_number');
		$this->db->where('org_id', $org_id);
		$this->db->where('active_yn', 'Y');
		$this->db->order_by('is_default', 'DESC');
		$this->db->order_by('sender_name', 'ASC');

		$query = $this->db->get();
		return $query->result_array();
	}

	/**
	 * 인증번호 저장
	 */
	public function save_auth_code($sender_idx, $auth_code, $expires)
	{
		$data = array(
			'auth_code' => $auth_code,
			'auth_code_expires' => $expires,
			'updated_date' => date('Y-m-d H:i:s')
		);

		$this->db->where('sender_idx', $sender_idx);
		return $this->db->update('wb_sender_number', $data);
	}

	/**
	 * 인증번호 확인
	 */
	public function verify_auth_code($sender_idx, $auth_code)
	{
		$this->db->select('auth_code, auth_code_expires');
		$this->db->from('wb_sender_number');
		$this->db->where('sender_idx', $sender_idx);
		$query = $this->db->get();
		$sender = $query->row_array();

		if (!$sender) {
			return array('success' => false, 'message' => '발신번호를 찾을 수 없습니다.');
		}

		// 인증번호 만료 확인
		if (strtotime($sender['auth_code_expires']) < time()) {
			return array('success' => false, 'message' => '인증번호가 만료되었습니다.');
		}

		// 인증번호 일치 확인
		if ($sender['auth_code'] !== $auth_code) {
			return array('success' => false, 'message' => '인증번호가 일치하지 않습니다.');
		}

		// 인증 완료 처리
		$update_data = array(
			'auth_status' => 'verified',
			'auth_code' => null,
			'auth_code_expires' => null,
			'updated_date' => date('Y-m-d H:i:s')
		);

		$this->db->where('sender_idx', $sender_idx);
		$this->db->update('wb_sender_number', $update_data);

		return array('success' => true, 'message' => '인증이 완료되었습니다.');
	}

}
