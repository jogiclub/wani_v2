<?php
/**
 * 역할: 슈어엠 SMS API 연동 라이브러리
 */
defined('BASEPATH') or exit('No direct script access allowed');

class Surem_api
{
	private $ci;
	private $base_url = 'https://rest.surem.com';
	private $user_code;
	private $secret_key;
	private $access_token;
	private $token_expires_at;

	public function __construct()
	{
		$this->ci =& get_instance();

		// 설정 파일에서 API 인증 정보 로드
		$this->ci->config->load('surem');
		$this->user_code = $this->ci->config->item('surem_user_code');
		$this->secret_key = $this->ci->config->item('surem_secret_key');
	}

	/**
	 * 역할: 액세스 토큰 발급 (1시간 유효)
	 */
	private function get_access_token()
	{
		// 토큰이 유효하면 재사용
		if ($this->access_token && $this->token_expires_at && time() < $this->token_expires_at) {
			return $this->access_token;
		}

		$url = $this->base_url . '/api/v1/auth/token';

		$data = array(
			'userCode' => $this->user_code,
			'secretKey' => $this->secret_key
		);

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'Content-Type: application/json'
		));
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

		$response = curl_exec($ch);
		$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);

		if ($http_code != 200) {
			log_message('error', 'Surem token API failed: ' . $response);
			return false;
		}

		$result = json_decode($response, true);

		if (!isset($result['data']['accessToken'])) {
			log_message('error', 'Surem token response invalid: ' . $response);
			return false;
		}

		$this->access_token = $result['data']['accessToken'];
		$this->token_expires_at = time() + ($result['data']['expiresIn'] - 300); // 5분 여유

		return $this->access_token;
	}

	/**
	 * 역할: SMS 발송
	 */
	public function send_sms($to, $text, $req_phone, $message_id = null)
	{
		$token = $this->get_access_token();
		if (!$token) {
			return array(
				'success' => false,
				'message' => 'API 토큰 발급 실패'
			);
		}

		$url = $this->base_url . '/api/v1/send/sms';

		$data = array(
			'to' => $to,
			'text' => $text,
			'reqPhone' => $req_phone
		);

		if ($message_id) {
			$data['messageId'] = $message_id;
		}

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'Content-Type: application/json',
			'Authorization: Bearer ' . $token
		));
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

		$response = curl_exec($ch);
		$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);

		$result = json_decode($response, true);

		if ($http_code == 200 && isset($result['code']) && $result['code'] == '0000') {
			return array(
				'success' => true,
				'message' => '발송 성공',
				'api_message_id' => $message_id
			);
		} else {
			$error_msg = isset($result['message']) ? $result['message'] : '알 수 없는 오류';
			log_message('error', 'Surem SMS send failed: ' . $response);
			return array(
				'success' => false,
				'message' => $error_msg
			);
		}
	}

	/**
	 * 역할: LMS 발송
	 */
	public function send_lms($to, $subject, $text, $req_phone, $message_id = null)
	{
		$token = $this->get_access_token();
		if (!$token) {
			return array(
				'success' => false,
				'message' => 'API 토큰 발급 실패'
			);
		}

		$url = $this->base_url . '/api/v1/send/mms';

		$data = array(
			'to' => $to,
			'subject' => $subject,
			'text' => $text,
			'reqPhone' => $req_phone
		);

		if ($message_id) {
			$data['messageId'] = $message_id;
		}

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'Content-Type: application/json',
			'Authorization: Bearer ' . $token
		));
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

		$response = curl_exec($ch);
		$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);

		$result = json_decode($response, true);

		if ($http_code == 200 && isset($result['code']) && $result['code'] == '0000') {
			return array(
				'success' => true,
				'message' => '발송 성공',
				'api_message_id' => $message_id
			);
		} else {
			$error_msg = isset($result['message']) ? $result['message'] : '알 수 없는 오류';
			log_message('error', 'Surem LMS send failed: ' . $response);
			return array(
				'success' => false,
				'message' => $error_msg
			);
		}
	}

	/**
	 * 역할: MMS 발송 (이미지 포함)
	 */
	public function send_mms($to, $subject, $text, $req_phone, $image_key, $message_id = null)
	{
		$token = $this->get_access_token();
		if (!$token) {
			return array(
				'success' => false,
				'message' => 'API 토큰 발급 실패'
			);
		}

		$url = $this->base_url . '/api/v1/send/mms';

		$data = array(
			'to' => $to,
			'subject' => $subject,
			'text' => $text,
			'reqPhone' => $req_phone,
			'imageKey' => $image_key
		);

		if ($message_id) {
			$data['messageId'] = $message_id;
		}

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'Content-Type: application/json',
			'Authorization: Bearer ' . $token
		));
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

		$response = curl_exec($ch);
		$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);

		$result = json_decode($response, true);

		if ($http_code == 200 && isset($result['code']) && $result['code'] == '0000') {
			return array(
				'success' => true,
				'message' => '발송 성공',
				'api_message_id' => $message_id
			);
		} else {
			$error_msg = isset($result['message']) ? $result['message'] : '알 수 없는 오류';
			log_message('error', 'Surem MMS send failed: ' . $response);
			return array(
				'success' => false,
				'message' => $error_msg
			);
		}
	}

	/**
	 * 역할: 발송 결과 조회 (Polling)
	 */
	public function get_send_results($type = 'S')
	{
		$token = $this->get_access_token();
		if (!$token) {
			return array(
				'success' => false,
				'message' => 'API 토큰 발급 실패'
			);
		}

		$url = $this->base_url . '/api/v2/report/responseAll?type=' . $type;

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'Authorization: Bearer ' . $token
		));
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

		$response = curl_exec($ch);
		$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);

		if ($http_code != 200) {
			log_message('error', 'Surem result polling failed: ' . $response);
			return array(
				'success' => false,
				'message' => '결과 조회 실패'
			);
		}

		$result = json_decode($response, true);

		if (!isset($result['code']) || $result['code'] != '0000') {
			return array(
				'success' => false,
				'message' => isset($result['message']) ? $result['message'] : '결과 조회 실패'
			);
		}

		return array(
			'success' => true,
			'checksum' => isset($result['checksum']) ? $result['checksum'] : null,
			'data' => isset($result['data']) ? $result['data'] : array()
		);
	}

	/**
	 * 역할: 결과 완료 처리
	 */
	public function complete_results($checksum)
	{
		$token = $this->get_access_token();
		if (!$token) {
			return false;
		}

		$url = $this->base_url . '/api/v2/report/complete';

		$data = array(
			'checksum' => $checksum
		);

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'Content-Type: application/json',
			'Authorization: Bearer ' . $token
		));
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

		$response = curl_exec($ch);
		$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);

		$result = json_decode($response, true);

		return ($http_code == 200 && isset($result['code']) && $result['code'] == '0000');
	}
}
