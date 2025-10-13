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
	 * 역할: SMS 발송 (직접 로그 추가)
	 */
	public function send_sms($to, $text, $req_phone, $message_id = null)
	{
		$token = $this->get_access_token();
		if (!$token) {
			$this->debug_log('토큰 발급 실패');
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

		// 요청 로그
		$this->debug_log('SMS 발송 요청', $data);

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

		// 응답 로그
		$this->debug_log("SMS 응답 (HTTP {$http_code})", $response);

		$result = json_decode($response, true);

		// JSON 파싱 로그
		$this->debug_log('파싱된 결과', $result);

		// 응답 구조 확인
		if ($http_code == 200) {
			if (isset($result['code'])) {
				$this->debug_log("응답 코드: " . $result['code']);

				if ($result['code'] == '0000' || $result['code'] === 0000) {
					return array(
						'success' => true,
						'message' => '발송 성공',
						'api_message_id' => $message_id
					);
				} else {
					$error_msg = isset($result['message']) ? $result['message'] : '알 수 없는 오류';
					return array(
						'success' => false,
						'message' => $error_msg
					);
				}
			} else {
				$this->debug_log('응답에 code 필드 없음');

				// HTTP 200이고 에러가 없으면 성공으로 간주
				if (!isset($result['error'])) {
					return array(
						'success' => true,
						'message' => '발송 성공',
						'api_message_id' => $message_id
					);
				}

				return array(
					'success' => false,
					'message' => isset($result['message']) ? $result['message'] : '응답 형식 오류'
				);
			}
		} else {
			$error_msg = isset($result['message']) ? $result['message'] : 'HTTP ' . $http_code . ' 오류';
			return array(
				'success' => false,
				'message' => $error_msg
			);
		}
	}

	/**
	 * 역할: LMS 발송 (직접 로그 추가)
	 */
	public function send_lms($to, $subject, $text, $req_phone, $message_id = null)
	{
		$token = $this->get_access_token();
		if (!$token) {
			$this->debug_log('토큰 발급 실패');
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

		// 요청 로그
		$this->debug_log('LMS 발송 요청', $data);

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

		// 응답 로그
		$this->debug_log("LMS 응답 (HTTP {$http_code})", $response);

		$result = json_decode($response, true);

		// JSON 파싱 로그
		$this->debug_log('파싱된 결과', $result);

		// 응답 구조 확인
		if ($http_code == 200) {
			if (isset($result['code'])) {
				$this->debug_log("응답 코드: " . $result['code']);

				if ($result['code'] == '0000' || $result['code'] === 0000) {
					return array(
						'success' => true,
						'message' => '발송 성공',
						'api_message_id' => $message_id
					);
				} else {
					$error_msg = isset($result['message']) ? $result['message'] : '알 수 없는 오류';
					return array(
						'success' => false,
						'message' => $error_msg
					);
				}
			} else {
				$this->debug_log('응답에 code 필드 없음');

				// HTTP 200이고 에러가 없으면 성공으로 간주
				if (!isset($result['error'])) {
					return array(
						'success' => true,
						'message' => '발송 성공',
						'api_message_id' => $message_id
					);
				}

				return array(
					'success' => false,
					'message' => isset($result['message']) ? $result['message'] : '응답 형식 오류'
				);
			}
		} else {
			$error_msg = isset($result['message']) ? $result['message'] : 'HTTP ' . $http_code . ' 오류';
			return array(
				'success' => false,
				'message' => $error_msg
			);
		}
	}

	/**
	 * 역할: MMS 발송 (응답 로깅 추가)
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

		// 디버깅 로그 추가
		log_message('info', 'Surem MMS Request: ' . json_encode($data));
		log_message('info', 'Surem MMS Response (HTTP ' . $http_code . '): ' . $response);

		$result = json_decode($response, true);

		// 응답 구조 확인
		if ($http_code == 200) {
			if (isset($result['code'])) {
				if ($result['code'] == '0000' || $result['code'] === 0000) {
					return array(
						'success' => true,
						'message' => '발송 성공',
						'api_message_id' => $message_id
					);
				} else {
					$error_msg = isset($result['message']) ? $result['message'] : '알 수 없는 오류 (code: ' . $result['code'] . ')';
					log_message('error', 'Surem MMS send failed - Code: ' . $result['code'] . ', Message: ' . $error_msg);
					return array(
						'success' => false,
						'message' => $error_msg
					);
				}
			} else {
				log_message('error', 'Surem MMS response has no code field: ' . $response);

				if (!isset($result['error']) && !isset($result['message'])) {
					return array(
						'success' => true,
						'message' => '발송 성공',
						'api_message_id' => $message_id
					);
				}

				return array(
					'success' => false,
					'message' => isset($result['message']) ? $result['message'] : '응답 형식 오류'
				);
			}
		} else {
			$error_msg = isset($result['message']) ? $result['message'] : 'HTTP ' . $http_code . ' 오류';
			log_message('error', 'Surem MMS HTTP error: ' . $http_code . ' - ' . $response);
			return array(
				'success' => false,
				'message' => $error_msg
			);
		}
	}


	private function debug_log($message, $data = null)
	{
		$log_file = APPPATH . 'logs/surem_debug.log';
		$timestamp = date('Y-m-d H:i:s');
		$log_message = "[{$timestamp}] {$message}";

		if ($data !== null) {
			$log_message .= "\n" . print_r($data, true);
		}

		$log_message .= "\n" . str_repeat('-', 80) . "\n";

		file_put_contents($log_file, $log_message, FILE_APPEND);
	}



}
