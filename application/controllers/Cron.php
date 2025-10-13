<?php
/**
 * 파일 위치: application/controllers/Cron.php
 * 역할: Cron 작업 처리 (Excel 자동 로드 방지)
 */
defined('BASEPATH') or exit('No direct script access allowed');

class Cron extends CI_Controller
{
	public function __construct()
	{
		parent::__construct();

		// CLI에서만 실행 가능하도록 제한
		if (!$this->input->is_cli_request()) {
			show_404();
		}

		// PHPExcel deprecated 오류 무시 (Cron에서는 Excel 사용 안 함)
		error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);

		$this->load->library('Surem_api');
		$this->load->model('Send_model');
	}

	/**
	 * 역할: SMS/LMS/MMS 발송 결과 조회 및 업데이트
	 * 실행: php index.php cron poll_sms_results
	 */
	public function poll_sms_results()
	{
		echo "=== SMS 발송 결과 조회 시작 ===\n";
		echo date('Y-m-d H:i:s') . "\n";

		// SMS 결과 조회
		$sms_result = $this->surem_api->get_send_results('S');
		$this->process_results($sms_result, 'SMS');

		// MMS 결과 조회 (LMS 포함)
		$mms_result = $this->surem_api->get_send_results('M');
		$this->process_results($mms_result, 'MMS');

		echo "=== 발송 결과 조회 완료 ===\n\n";
	}

	/**
	 * 역할: 결과 처리 및 DB 업데이트
	 */
	private function process_results($api_result, $type)
	{
		if (!$api_result['success']) {
			echo "[{$type}] 결과 조회 실패: " . $api_result['message'] . "\n";
			return;
		}

		$data = $api_result['data'];
		$checksum = $api_result['checksum'];

		if (empty($data)) {
			echo "[{$type}] 조회할 결과 없음\n";
			return;
		}

		echo "[{$type}] " . count($data) . "건의 결과 조회\n";

		// DB 업데이트
		$update_success = $this->Send_model->update_send_results($data);

		if ($update_success) {
			echo "[{$type}] DB 업데이트 성공\n";

			// 결과 완료 처리
			if ($checksum) {
				$complete_success = $this->surem_api->complete_results($checksum);
				if ($complete_success) {
					echo "[{$type}] 결과 완료 처리 성공\n";
				} else {
					echo "[{$type}] 결과 완료 처리 실패\n";
				}
			}
		} else {
			echo "[{$type}] DB 업데이트 실패\n";
		}
	}




	/**
	 * 역할: 예약 발송 처리 (1분마다 실행)
	 * 실행: php index.php cron process_scheduled_messages
	 */
	public function process_scheduled_messages()
	{
		echo "=== 예약 발송 처리 시작 ===\n";
		echo date('Y-m-d H:i:s') . "\n";

		try {
			// 발송 시간이 된 예약 목록 조회
			$pending_messages = $this->Send_model->get_pending_scheduled_messages();

			if (empty($pending_messages)) {
				echo "처리할 예약 발송이 없습니다.\n";
				echo "=== 예약 발송 처리 완료 ===\n\n";
				return;
			}

			echo "처리할 예약 발송: " . count($pending_messages) . "건\n";

			foreach ($pending_messages as $reservation) {
				echo "\n--- 예약번호 {$reservation['reservation_idx']} 처리 시작 ---\n";

				// 예약 상태를 'processing'으로 변경 (중복 처리 방지)
				$this->Send_model->update_reservation_status($reservation['reservation_idx'], 'processing');

				// 예약 메시지 발송
				$this->process_scheduled_message($reservation);

				echo "--- 예약번호 {$reservation['reservation_idx']} 처리 완료 ---\n";
			}

			echo "\n=== 예약 발송 처리 완료 ===\n";
			echo "총 처리 건수: " . count($pending_messages) . "건\n\n";

		} catch (Exception $e) {
			echo "오류 발생: " . $e->getMessage() . "\n";
			log_message('error', '예약 발송 처리 오류: ' . $e->getMessage());
			exit(1);
		}
	}

	/**
	 * 역할: 개별 예약 메시지 발송 처리
	 */
	private function process_scheduled_message($reservation)
	{
		$receiver_list = json_decode($reservation['receiver_list'], true);

		if (empty($receiver_list)) {
			echo "수신자 없음\n";
			$this->Send_model->update_reservation_status($reservation['reservation_idx'], 'failed');
			return;
		}

		echo "수신자 수: " . count($receiver_list) . "명\n";

		// 잔액 확인
		$package_prices = $this->Send_model->get_available_package_prices($reservation['org_id']);
		$cost_per_message = isset($package_prices[$reservation['send_type']]) ? $package_prices[$reservation['send_type']] : 0;
		$total_cost = $cost_per_message * count($receiver_list);

		$org_balance = $this->Send_model->get_org_total_balance($reservation['org_id']);

		if ($org_balance < $total_cost) {
			echo "잔액 부족 (필요: {$total_cost}원, 보유: {$org_balance}원)\n";
			$this->Send_model->update_reservation_status($reservation['reservation_idx'], 'failed');
			return;
		}

		$success_count = 0;
		$fail_count = 0;

		foreach ($receiver_list as $receiver) {
			// 메시지 치환
			$message = $this->replace_fields($reservation['message_content'], $receiver);

			// API 메시지 ID 생성
			$api_message_id = $this->generate_api_message_id();

			// 발송 로그 저장
			$send_data = array(
				'org_id' => $reservation['org_id'],
				'sender_id' => 0,
				'member_idx' => isset($receiver['member_idx']) ? $receiver['member_idx'] : null,
				'send_type' => $reservation['send_type'],
				'sender_number' => $reservation['sender_number'],
				'sender_name' => $reservation['sender_name'],
				'receiver_number' => $receiver['member_phone'],
				'receiver_name' => $receiver['member_name'],
				'message_content' => $message,
				'send_status' => 'pending',
				'send_date' => date('Y-m-d H:i:s'),
				'cost' => $cost_per_message,
				'api_message_id' => $api_message_id
			);

			$send_idx = $this->Send_model->save_send_log($send_data);

			if (!$send_idx) {
				$fail_count++;
				echo "발송 로그 저장 실패: {$receiver['member_name']}\n";
				continue;
			}

			// 잔액 차감
			$deduct_result = $this->Send_model->deduct_balance(
				$reservation['org_id'],
				$reservation['send_type'],
				1
			);

			if (!$deduct_result) {
				$this->Send_model->update_send_log_status($send_idx, 'failed', '잔액 차감 실패');
				$fail_count++;
				echo "잔액 차감 실패: {$receiver['member_name']}\n";
				continue;
			}

			// 실제 발송
			$clean_receiver = str_replace('-', '', $receiver['member_phone']);
			$clean_sender = str_replace('-', '', $reservation['sender_number']);

			$result = $this->send_message(
				$reservation['send_type'],
				$clean_receiver,
				$message,
				$clean_sender,
				$api_message_id
			);

			if ($result['success']) {
				$success_count++;
				echo "발송 성공: {$receiver['member_name']} ({$receiver['member_phone']})\n";
			} else {
				$this->Send_model->update_send_log_status($send_idx, 'failed', $result['message']);
				$fail_count++;
				echo "발송 실패: {$receiver['member_name']} - {$result['message']}\n";
			}
		}

		// 예약 상태 업데이트
		$status = 'sent';
		if ($fail_count > 0 && $success_count == 0) {
			$status = 'failed';
		}

		$this->Send_model->update_reservation_status($reservation['reservation_idx'], $status);

		echo "발송 완료 - 성공: {$success_count}건, 실패: {$fail_count}건\n";
	}

	/**
	 * 역할: 메시지 발송
	 */
	private function send_message($send_type, $to, $message, $req_phone, $api_message_id)
	{
		try {
			$result = null;

			if ($send_type === 'sms') {
				$text = mb_substr($message, 0, 90);
				$result = $this->surem_api->send_sms($to, $text, $req_phone, $api_message_id);
			} elseif ($send_type === 'lms' || $send_type === 'mms') {
				$subject = mb_substr($message, 0, 30);
				$text = mb_substr($message, 0, 2000);
				$result = $this->surem_api->send_lms($to, $subject, $text, $req_phone, $api_message_id);
			}

			if ($result && $result['success']) {
				return array(
					'success' => true,
					'message' => '발송 성공'
				);
			} else {
				return array(
					'success' => false,
					'message' => $result['message'] ?? '발송 실패'
				);
			}

		} catch (Exception $e) {
			log_message('error', '메시지 발송 오류: ' . $e->getMessage());
			return array(
				'success' => false,
				'message' => '발송 중 오류 발생'
			);
		}
	}

	/**
	 * 역할: 메시지 필드 치환
	 */
	private function replace_fields($message, $receiver)
	{
		$replacements = array(
			'{이름}' => isset($receiver['member_name']) ? $receiver['member_name'] : '',
			'{직분}' => isset($receiver['position_name']) ? $receiver['position_name'] : '',
			'{연락처}' => isset($receiver['member_phone']) ? $receiver['member_phone'] : '',
			'{그룹}' => isset($receiver['area_name']) ? $receiver['area_name'] : '',
			'{임시1}' => isset($receiver['tmp01']) ? $receiver['tmp01'] : '',
			'{임시2}' => isset($receiver['tmp02']) ? $receiver['tmp02'] : ''
		);

		foreach ($replacements as $key => $value) {
			$message = str_replace($key, $value, $message);
		}

		return $message;
	}

	/**
	 * 역할: API 메시지 ID 생성 (9자리 숫자)
	 */
	private function generate_api_message_id()
	{
		return rand(100000000, 999999999);
	}

}
