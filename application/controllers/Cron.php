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


	private function process_scheduled_message($reservation)
	{
		$receiver_list = json_decode($reservation['receiver_list'], true);

		if (empty($receiver_list)) {
			echo "예약번호 {$reservation['reservation_idx']}: 수신자 없음\n";
			return;
		}

		echo "예약번호 {$reservation['reservation_idx']}: " . count($receiver_list) . "명 발송 시작\n";

		$success_count = 0;
		$fail_count = 0;

		foreach ($receiver_list as $receiver) {
			// 메시지 치환
			$message = $this->replace_fields($reservation['message_content'], $receiver);

			// 발송 로그 저장 (api_message_id 자동 생성됨)
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
				'send_date' => date('Y-m-d H:i:s')
			);

			$send_idx = $this->Send_model->save_send_log($send_data);

			if (!$send_idx) {
				$fail_count++;
				continue;
			}

			// 저장된 로그에서 api_message_id 조회
			$log = $this->Send_model->get_send_log_by_idx($send_idx);
			$api_message_id = $log['api_message_id'];

			// 실제 발송
			$clean_receiver = str_replace('-', '', $receiver['member_phone']);
			$clean_sender = str_replace('-', '', $reservation['sender_number']);

			$result = null;

			if ($reservation['send_type'] == 'sms') {
				$text = mb_substr($message, 0, 90);
				$result = $this->surem_api->send_sms($clean_receiver, $text, $clean_sender, $api_message_id);
			} else {
				$subject = mb_substr($message, 0, 30);
				$text = mb_substr($message, 0, 2000);
				$result = $this->surem_api->send_lms($clean_receiver, $subject, $text, $clean_sender, $api_message_id);
			}

			if ($result['success']) {
				$success_count++;
			} else {
				$this->Send_model->update_send_log($send_idx, array(
					'send_status' => 'failed',
					'result_message' => $result['message']
				));
				$fail_count++;
			}
		}

		// 예약 상태 업데이트
		$status = ($fail_count == 0) ? 'sent' : (($success_count == 0) ? 'failed' : 'sent');
		$this->Send_model->update_reservation_status($reservation['reservation_idx'], $status);

		echo "예약번호 {$reservation['reservation_idx']}: 성공 {$success_count}건, 실패 {$fail_count}건\n";
	}

	/**
	 * 역할: 메시지 치환
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

		return str_replace(array_keys($replacements), array_values($replacements), $message);
	}
}
