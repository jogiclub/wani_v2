<?php
/**
 * 파일 위치: application/libraries/Surem_api.php
 * 역할: 슈어엠 API 연동 라이브러리
 */
defined('BASEPATH') or exit('No direct script access allowed');

class Surem_api
{
	private $CI;
	private $user_code;
	private $secret_key;
	private $access_token = null;
	private $token_expires_at = null;

	// API 기본 URL 상수 정의
	const API_BASE_URL = 'https://rest.surem.com';

	public function __construct()
	{
		$this->CI =& get_instance();
		$this->CI->load->config('surem');

		$this->user_code = $this->CI->config->item('surem_user_code');
		$this->secret_key = $this->CI->config->item('surem_secret_key');
	}

	/**
	 * 역할: 액세스 토큰 발급 또는 재사용
	 */
	private function get_access_token()
	{
		// 기존 토큰이 유효하면 재사용
		if ($this->access_token && $this->token_expires_at && time() < $this->token_expires_at - 300) {
			return $this->access_token;
		}

		// 새 토큰 발급
		$url = self::API_BASE_URL . '/api/v1/auth/token';

		$data = array(
			'userCode' => $this->user_code,
			'secretKey' => $this->secret_key
		);

		$ch = curl_init();

		curl_setopt_array($ch, array(
			CURLOPT_URL => $url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_POST => true,
			CURLOPT_POSTFIELDS => json_encode($data),
			CURLOPT_HTTPHEADER => array(
				'Content-Type: application/json'
			),
			CURLOPT_TIMEOUT => 30
		));

		$response = curl_exec($ch);
		$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$error = curl_error($ch);
		curl_close($ch);

		if ($error) {
			log_message('error', 'Surem token API cURL error: ' . $error);
			return false;
		}

		$result = json_decode($response, true);

		if ($http_code !== 200 || !isset($result['data']['accessToken'])) {
			log_message('error', 'Surem token API failed: ' . $response);
			return false;
		}

		// 토큰 저장
		$this->access_token = $result['data']['accessToken'];
		$this->token_expires_at = time() + $result['data']['expiresIn'];

		return $this->access_token;
	}

	/**
	 * 역할: MMS 이미지 업로드
	 */
	public function upload_mms_image($image_path)
	{
		if (!file_exists($image_path)) {
			return array('success' => false, 'message' => '이미지 파일이 존재하지 않습니다.');
		}

		$token = $this->get_access_token();

		if (!$token) {
			return array('success' => false, 'message' => '인증 토큰 발급 실패');
		}

		$url = self::API_BASE_URL . '/api/v1/image';

		$ch = curl_init();

		// CURLFile 객체 생성
		$cfile = new CURLFile($image_path, 'image/jpeg', basename($image_path));

		$post_data = array(
			'image1' => $cfile
		);

		curl_setopt_array($ch, array(
			CURLOPT_URL => $url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_POST => true,
			CURLOPT_POSTFIELDS => $post_data,
			CURLOPT_HTTPHEADER => array(
				'Authorization: Bearer ' . $token
			),
			CURLOPT_TIMEOUT => 30
		));

		$response = curl_exec($ch);
		$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$error = curl_error($ch);
		curl_close($ch);

		if ($error) {
			log_message('error', 'Surem image upload cURL error: ' . $error);
			return array('success' => false, 'message' => 'cURL 오류: ' . $error);
		}

		$result = json_decode($response, true);

		if ($http_code !== 200 || !isset($result['data']['imageKey'])) {
			$message = isset($result['message']) ? $result['message'] : '이미지 업로드 실패';
			log_message('error', 'Surem image upload failed: ' . $response);
			return array('success' => false, 'message' => $message);
		}

		return array(
			'success' => true,
			'image_key' => $result['data']['imageKey'],
			'expiry_date' => isset($result['data']['expiryDate']) ? $result['data']['expiryDate'] : null
		);
	}

	/**
	 * 역할: SMS 발송
	 */
	public function send_sms($to, $text, $req_phone, $message_id = null, $reserved_time = null)
	{
		$token = $this->get_access_token();

		if (!$token) {
			return array('success' => false, 'message' => '인증 토큰 발급 실패');
		}

		$url = self::API_BASE_URL . '/api/v1/send/sms';

		$data = array(
			'to' => $to,
			'text' => $text,
			'reqPhone' => $req_phone
		);

		if ($message_id) {
			$data['messageId'] = $message_id;
		}

		if ($reserved_time) {
			$data['reservedTime'] = $reserved_time;
		}

		$ch = curl_init();

		curl_setopt_array($ch, array(
			CURLOPT_URL => $url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_POST => true,
			CURLOPT_POSTFIELDS => json_encode($data),
			CURLOPT_HTTPHEADER => array(
				'Content-Type: application/json',
				'Authorization: Bearer ' . $token
			),
			CURLOPT_TIMEOUT => 30
		));

		$response = curl_exec($ch);
		$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$error = curl_error($ch);
		curl_close($ch);

		if ($error) {
			log_message('error', 'Surem SMS API cURL error: ' . $error);
			return array('success' => false, 'message' => 'cURL 오류: ' . $error);
		}

		$result = json_decode($response, true);

		if ($http_code !== 200 || (isset($result['code']) && $result['code'] !== '200')) {
			$message = isset($result['message']) ? $result['message'] : 'SMS 발송 실패';
			log_message('error', 'Surem SMS send failed: ' . $response);
			return array('success' => false, 'message' => $message);
		}

		return array('success' => true, 'message' => 'SMS 발송 성공');
	}

	/**
	 * 역할: LMS 발송
	 */
	public function send_lms($to, $subject, $text, $req_phone, $message_id = null, $reserved_time = null)
	{
		$token = $this->get_access_token();

		if (!$token) {
			return array('success' => false, 'message' => '인증 토큰 발급 실패');
		}

		$url = self::API_BASE_URL . '/api/v1/send/mms';

		$data = array(
			'to' => $to,
			'subject' => $subject,
			'text' => $text,
			'reqPhone' => $req_phone
		);

		if ($message_id) {
			$data['messageId'] = $message_id;
		}

		if ($reserved_time) {
			$data['reservedTime'] = $reserved_time;
		}

		$ch = curl_init();

		curl_setopt_array($ch, array(
			CURLOPT_URL => $url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_POST => true,
			CURLOPT_POSTFIELDS => json_encode($data),
			CURLOPT_HTTPHEADER => array(
				'Content-Type: application/json',
				'Authorization: Bearer ' . $token
			),
			CURLOPT_TIMEOUT => 30
		));

		$response = curl_exec($ch);
		$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$error = curl_error($ch);
		curl_close($ch);

		if ($error) {
			log_message('error', 'Surem LMS API cURL error: ' . $error);
			return array('success' => false, 'message' => 'cURL 오류: ' . $error);
		}

		$result = json_decode($response, true);

		if ($http_code !== 200 || (isset($result['code']) && $result['code'] !== '200')) {
			$message = isset($result['message']) ? $result['message'] : 'LMS 발송 실패';
			log_message('error', 'Surem LMS send failed: ' . $response);
			return array('success' => false, 'message' => $message);
		}

		return array('success' => true, 'message' => 'LMS 발송 성공');
	}

	/**
	 * 역할: MMS 발송 (이미지 포함)
	 */
	public function send_mms($to, $text, $req_phone, $image_key = null, $subject = '', $message_id = null, $reserved_time = null)
	{
		$token = $this->get_access_token();

		if (!$token) {
			return array('success' => false, 'message' => '인증 토큰 발급 실패');
		}

		$url = self::API_BASE_URL . '/api/v1/send/mms';

		$data = array(
			'to' => $to,
			'text' => $text,
			'reqPhone' => $req_phone
		);

		if ($subject) {
			$data['subject'] = $subject;
		}

		if ($image_key) {
			$data['imageKey'] = $image_key;
		}

		if ($message_id) {
			$data['messageId'] = $message_id;
		}

		if ($reserved_time) {
			$data['reservedTime'] = $reserved_time;
		}

		// 로그 기록
		log_message('debug', '--------------------------------------------------------------------------------');
		log_message('debug', 'MMS 발송 요청');
		log_message('debug', print_r($data, true));

		$ch = curl_init();

		curl_setopt_array($ch, array(
			CURLOPT_URL => $url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_POST => true,
			CURLOPT_POSTFIELDS => json_encode($data),
			CURLOPT_HTTPHEADER => array(
				'Content-Type: application/json',
				'Authorization: Bearer ' . $token
			),
			CURLOPT_TIMEOUT => 30
		));

		$response = curl_exec($ch);
		$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$error = curl_error($ch);
		curl_close($ch);

		// 응답 로그
		log_message('debug', '--------------------------------------------------------------------------------');
		log_message('debug', 'MMS 응답 (HTTP ' . $http_code . ')');
		log_message('debug', $response);

		if ($error) {
			log_message('error', 'Surem MMS API cURL error: ' . $error);
			return array('success' => false, 'message' => 'cURL 오류: ' . $error);
		}

		$result = json_decode($response, true);

		// 파싱 결과 로그
		log_message('debug', '--------------------------------------------------------------------------------');
		log_message('debug', '파싱된 결과');
		log_message('debug', print_r($result, true));
		log_message('debug', '--------------------------------------------------------------------------------');
		log_message('debug', '응답 코드: ' . (isset($result['code']) ? $result['code'] : 'N/A'));
		log_message('debug', '--------------------------------------------------------------------------------');

		if ($http_code !== 200 || (isset($result['code']) && $result['code'] !== 'A0000')) {
			$message = isset($result['message']) ? $result['message'] : 'MMS 발송 실패';
			log_message('error', 'Surem MMS send failed: ' . $response);
			return array('success' => false, 'message' => $message);
		}

		return array('success' => true, 'message' => 'MMS 발송 성공');
	}

	/**
	 * 역할: 발송 결과 조회
	 */
	public function get_send_results($msg_type = 'S')
	{
		$token = $this->get_access_token();

		if (!$token) {
			return array('success' => false, 'message' => '인증 토큰 발급 실패');
		}

		$url = self::API_BASE_URL . '/api/v1/result/' . $msg_type;

		$ch = curl_init();

		curl_setopt_array($ch, array(
			CURLOPT_URL => $url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_HTTPHEADER => array(
				'Authorization: Bearer ' . $token
			),
			CURLOPT_TIMEOUT => 30
		));

		$response = curl_exec($ch);
		$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$error = curl_error($ch);
		curl_close($ch);

		if ($error) {
			log_message('error', 'Surem result API cURL error: ' . $error);
			return array('success' => false, 'message' => 'cURL 오류: ' . $error);
		}

		$result = json_decode($response, true);

		if ($http_code !== 200 || (isset($result['code']) && $result['code'] !== '200')) {
			$message = isset($result['message']) ? $result['message'] : '결과 조회 실패';
			return array('success' => false, 'message' => $message);
		}

		return array(
			'success' => true,
			'data' => isset($result['data']) ? $result['data'] : array(),
			'checksum' => isset($result['checksum']) ? $result['checksum'] : null
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

		$url = self::API_BASE_URL . '/api/v1/result/complete';

		$data = array('checksum' => $checksum);

		$ch = curl_init();

		curl_setopt_array($ch, array(
			CURLOPT_URL => $url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_POST => true,
			CURLOPT_POSTFIELDS => json_encode($data),
			CURLOPT_HTTPHEADER => array(
				'Content-Type: application/json',
				'Authorization: Bearer ' . $token
			),
			CURLOPT_TIMEOUT => 30
		));

		$response = curl_exec($ch);
		$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);

		return ($http_code === 200);
	}
}
