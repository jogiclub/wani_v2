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
	 * 조직의 전체 문자 잔액 조회 (모든 패키지의 잔액 합계)
	 */
	public function get_org_total_balance($org_id)
	{
		$this->db->select_sum('remaining_balance');
		$this->db->from('wb_sms_charge_history');
		$this->db->where('org_id', $org_id);
		$this->db->where('payment_status', 'completed');
		$this->db->where('remaining_balance >', 0);

		$query = $this->db->get();
		$result = $query->row_array();

		return $result['remaining_balance'] ? $result['remaining_balance'] : 0;
	}


	/**
	 * 발송 타입별 사용 가능한 잔액 조회 (패키지별 단가 기준)
	 */
	public function get_available_balance_by_type($org_id, $send_type)
	{
		// 먼저 잔액이 있는 충전 내역 조회 (package_idx가 작은 순서대로)
		$this->db->select('h.history_idx, h.package_idx, h.remaining_balance, p.sms_price, p.lms_price, p.mms_price, p.kakao_price');
		$this->db->from('wb_sms_charge_history h');
		$this->db->join('wb_sms_package p', 'h.package_idx = p.package_idx', 'left');
		$this->db->where('h.org_id', $org_id);
		$this->db->where('h.payment_status', 'completed');
		$this->db->where('h.remaining_balance >', 0);
		$this->db->order_by('h.package_idx', 'ASC');
		$this->db->order_by('h.history_idx', 'ASC');

		$query = $this->db->get();
		$charge_histories = $query->result_array();

		$total_available = 0;
		$price_field = $send_type . '_price';

		foreach ($charge_histories as $history) {
			$unit_price = $history[$price_field];
			if ($unit_price > 0) {
				// 해당 패키지로 발송 가능한 건수 계산
				$available_count = floor($history['remaining_balance'] / $unit_price);
				$total_available += $available_count;
			}
		}

		return $total_available;
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
			// 충전 내역 저장 (remaining_balance를 charge_amount와 동일하게 설정)
			$history_data = array(
				'org_id' => (int)$org_id,
				'user_id' => $user_id,
				'package_idx' => (int)$package_idx,
				'charge_amount' => (int)$charge_amount,
				'remaining_balance' => (int)$charge_amount,
				'payment_method' => 'card',
				'payment_status' => 'completed',
				'charge_date' => date('Y-m-d H:i:s')
			);

			$insert_result = $this->db->insert('wb_sms_charge_history', $history_data);

			if (!$insert_result) {
				log_message('error', 'Charge history insert failed: ' . $this->db->error()['message']);
				throw new Exception('충전 내역 저장 실패');
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
	 * 문자 발송 시 잔액 차감 (패키지 우선순위에 따라)
	 */
	public function deduct_balance($org_id, $send_type, $receiver_count)
	{
		// 트랜잭션 시작
		$this->db->trans_begin();

		try {
			// 잔액이 있는 충전 내역 조회 (package_idx가 작은 순서대로)
			$this->db->select('h.history_idx, h.package_idx, h.remaining_balance, p.sms_price, p.lms_price, p.mms_price, p.kakao_price');
			$this->db->from('wb_sms_charge_history h');
			$this->db->join('wb_sms_package p', 'h.package_idx = p.package_idx', 'left');
			$this->db->where('h.org_id', $org_id);
			$this->db->where('h.payment_status', 'completed');
			$this->db->where('h.remaining_balance >', 0);
			$this->db->order_by('h.package_idx', 'ASC');
			$this->db->order_by('h.history_idx', 'ASC');

			$query = $this->db->get();
			$charge_histories = $query->result_array();

			if (empty($charge_histories)) {
				throw new Exception('사용 가능한 잔액이 없습니다.');
			}

			$price_field = $send_type . '_price';
			$remaining_count = $receiver_count;
			$deduction_log = array();

			// 각 충전 패키지에서 순서대로 차감
			foreach ($charge_histories as $history) {
				if ($remaining_count <= 0) {
					break;
				}

				$unit_price = $history[$price_field];
				if ($unit_price <= 0) {
					continue;
				}

				// 이 패키지로 발송 가능한 최대 건수
				$available_count = floor($history['remaining_balance'] / $unit_price);

				// 실제 차감할 건수
				$deduct_count = min($remaining_count, $available_count);
				$deduct_amount = $deduct_count * $unit_price;

				// 잔액 차감
				$this->db->set('remaining_balance', 'remaining_balance - ' . $deduct_amount, FALSE);
				$this->db->where('history_idx', $history['history_idx']);
				$update_result = $this->db->update('wb_sms_charge_history');

				if (!$update_result) {
					throw new Exception('잔액 차감 실패');
				}

				$deduction_log[] = array(
					'history_idx' => $history['history_idx'],
					'package_idx' => $history['package_idx'],
					'deduct_count' => $deduct_count,
					'deduct_amount' => $deduct_amount,
					'unit_price' => $unit_price
				);

				$remaining_count -= $deduct_count;
			}

			// 차감 후에도 발송할 건수가 남아있으면 잔액 부족
			if ($remaining_count > 0) {
				throw new Exception('잔액이 부족합니다. (부족한 건수: ' . $remaining_count . '건)');
			}

			// 트랜잭션 커밋
			$this->db->trans_commit();

			return array(
				'success' => true,
				'deduction_log' => $deduction_log
			);

		} catch (Exception $e) {
			// 트랜잭션 롤백
			$this->db->trans_rollback();
			log_message('error', 'Balance deduction failed: ' . $e->getMessage());

			return array(
				'success' => false,
				'message' => $e->getMessage()
			);
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
	 * 조직의 잔액이 있는 패키지별 단가 조회
	 */
	public function get_available_package_prices($org_id)
	{
		$this->db->select('p.sms_price, p.lms_price, p.mms_price, p.kakao_price');
		$this->db->from('wb_sms_charge_history h');
		$this->db->join('wb_sms_package p', 'h.package_idx = p.package_idx', 'left');
		$this->db->where('h.org_id', $org_id);
		$this->db->where('h.payment_status', 'completed');
		$this->db->where('h.remaining_balance >', 0);
		$this->db->order_by('h.package_idx', 'ASC');
		$this->db->limit(1); // 가장 먼저 사용될 패키지의 단가만 조회

		$query = $this->db->get();
		$result = $query->row_array();

		if ($result) {
			return array(
				'sms' => $result['sms_price'],
				'lms' => $result['lms_price'],
				'mms' => $result['mms_price'],
				'kakao' => $result['kakao_price']
			);
		}

		// 잔액이 없으면 기본값 반환
		return array(
			'sms' => 10,
			'lms' => 20,
			'mms' => 30,
			'kakao' => 20
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


	/**
	 * 주소록 저장
	 */
	public function save_address_book($data)
	{
		return $this->db->insert('wb_address_book', $data);
	}

	/**
	 * 주소록 목록 조회
	 */
	public function get_address_book_list($org_id, $user_id)
	{
		$this->db->select('ab.*, COUNT(DISTINCT abm.member_idx) as member_count');
		$this->db->from('wb_address_book ab');
		$this->db->join('wb_address_book_member abm', 'ab.address_book_idx = abm.address_book_idx', 'left');
		$this->db->where('ab.org_id', $org_id);
		$this->db->where('ab.active_yn', 'Y');
		$this->db->group_by('ab.address_book_idx');
		$this->db->order_by('ab.created_date', 'DESC');

		$query = $this->db->get();
		return $query->result_array();
	}

	/**
	 * 주소록 삭제 (소프트 삭제)
	 */
	public function delete_address_book($address_book_idx, $org_id)
	{
		$this->db->where('address_book_idx', $address_book_idx);
		$this->db->where('org_id', $org_id);
		return $this->db->update('wb_address_book', array('active_yn' => 'N'));
	}

	/**
	 * 주소록 회원 목록 조회
	 */
	public function get_address_book_members($address_book_idx)
	{
		$this->db->select('m.*, ma.area_name, abm.address_book_idx');
		$this->db->from('wb_address_book_member abm');
		$this->db->join('wb_member m', 'abm.member_idx = m.member_idx');
		$this->db->join('wb_member_area ma', 'm.area_idx = ma.area_idx', 'left');
		$this->db->where('abm.address_book_idx', $address_book_idx);
		$this->db->where('m.del_yn', 'N');

		$query = $this->db->get();
		return $query->result_array();
	}

}
