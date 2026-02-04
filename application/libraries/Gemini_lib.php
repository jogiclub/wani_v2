
<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Gemini_lib {

	private $CI;
	private $api_key;
	private $api_url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent';
	private $unsplash_access_key;

	public function __construct()
	{
		$this->CI =& get_instance();

		// Gemini Config 로드
		$this->CI->config->load('gemini', FALSE, TRUE);
		$this->api_key = $this->CI->config->item('gemini_api_key');

		// Unsplash Config 로드
		$this->CI->config->load('unsplash', FALSE, TRUE);
		$this->unsplash_access_key = $this->CI->config->item('unsplash_access_key');

		// 디버깅용 로그
		log_message('debug', 'Gemini API Key loaded: ' . ($this->api_key ? 'Yes' : 'No'));
		log_message('debug', 'Unsplash Access Key loaded: ' . ($this->unsplash_access_key ? 'Yes' : 'No'));
	}

	/**
	 * Unsplash 랜덤 이미지 URL 생성
	 */
	public function get_unsplash_image_url($keyword = '', $width = 1200, $height = 800)
	{
		if (empty($this->unsplash_access_key)) {
			return 'https://via.placeholder.com/' . $width . 'x' . $height;
		}

		$query_params = array(
			'client_id' => $this->unsplash_access_key,
			'orientation' => 'landscape'
		);

		if (!empty($keyword)) {
			$query_params['query'] = $keyword;
		}

		$url = 'https://api.unsplash.com/photos/random?' . http_build_query($query_params);

		return $url;
	}

	/**
	 * Unsplash에서 실제 이미지 정보 가져오기
	 */
	public function fetch_unsplash_image($keyword = '')
	{
		if (empty($this->unsplash_access_key)) {
			return [
				'success' => FALSE,
				'message' => 'Unsplash API 키가 설정되지 않았습니다.'
			];
		}

		$query_params = array(
			'client_id' => $this->unsplash_access_key,
			'orientation' => 'landscape'
		);

		if (!empty($keyword)) {
			$query_params['query'] = $keyword;
		}

		$url = 'https://api.unsplash.com/photos/random?' . http_build_query($query_params);

		$ch = curl_init();
		curl_setopt_array($ch, [
			CURLOPT_URL => $url,
			CURLOPT_RETURNTRANSFER => TRUE,
			CURLOPT_TIMEOUT => 10,
			CURLOPT_SSL_VERIFYPEER => TRUE,
			CURLOPT_HTTPHEADER => [
				'Accept: application/json'
			]
		]);

		$response = curl_exec($ch);
		$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$error = curl_error($ch);
		curl_close($ch);

		if ($error) {
			log_message('error', 'Unsplash API cURL Error: ' . $error);
			return [
				'success' => FALSE,
				'message' => 'API 통신 오류: ' . $error
			];
		}

		if ($http_code !== 200) {
			log_message('error', 'Unsplash API HTTP Error: ' . $http_code . ' Response: ' . $response);
			return [
				'success' => FALSE,
				'message' => 'API 오류 (HTTP ' . $http_code . ')'
			];
		}

		$result = json_decode($response, TRUE);

		if (json_last_error() !== JSON_ERROR_NONE) {
			return [
				'success' => FALSE,
				'message' => 'API 응답 파싱 오류'
			];
		}

		if (isset($result['urls'])) {
			return [
				'success' => TRUE,
				'data' => [
					'regular' => $result['urls']['regular'],
					'small' => $result['urls']['small'],
					'thumb' => $result['urls']['thumb'],
					'full' => $result['urls']['full'],
					'raw' => $result['urls']['raw'],
					'alt_description' => $result['alt_description'] ?? '',
					'description' => $result['description'] ?? ''
				]
			];
		}

		return [
			'success' => FALSE,
			'message' => 'API 응답 형식 오류'
		];
	}

	/**
	 * Gemini API로 텍스트 생성 요청
	 */
	public function generate($prompt)
	{
		if (empty($this->api_key)) {
			return ['success' => FALSE, 'message' => 'Gemini API 키가 설정되지 않았습니다.'];
		}

		$url = $this->api_url . '?key=' . $this->api_key;

		$request_body = [
			'contents' => [
				[
					'parts' => [
						['text' => $prompt]
					]
				]
			],
			'generationConfig' => [
				'temperature' => 0.7,
				'maxOutputTokens' => 8192,
			]
		];

		$ch = curl_init();

		curl_setopt_array($ch, [
			CURLOPT_URL => $url,
			CURLOPT_RETURNTRANSFER => TRUE,
			CURLOPT_POST => TRUE,
			CURLOPT_POSTFIELDS => json_encode($request_body),
			CURLOPT_HTTPHEADER => [
				'Content-Type: application/json'
			],
			CURLOPT_TIMEOUT => 60,
			CURLOPT_SSL_VERIFYPEER => TRUE
		]);

		$response = curl_exec($ch);
		$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$error = curl_error($ch);

		curl_close($ch);

		if ($error) {
			log_message('error', 'Gemini API cURL Error: ' . $error);
			return ['success' => FALSE, 'message' => 'API 통신 오류: ' . $error];
		}

		if ($http_code !== 200) {
			$error_detail = '';
			$result = json_decode($response, TRUE);
			if (isset($result['error']['message'])) {
				$error_detail = $result['error']['message'];
			}

			log_message('error', 'Gemini API HTTP Error: ' . $http_code . ' Response: ' . $response);

			if ($http_code === 403) {
				$message = 'API 접근 거부 (HTTP 403)';
				if ($error_detail) {
					$message .= ' - ' . $error_detail;
				} else {
					$message .= ' - API 키 확인 또는 지역/할당량 제한 확인 필요';
				}
				return ['success' => FALSE, 'message' => $message];
			}

			return ['success' => FALSE, 'message' => 'API 오류 (HTTP ' . $http_code . ')' . ($error_detail ? ' - ' . $error_detail : '')];
		}

		$result = json_decode($response, TRUE);

		if (json_last_error() !== JSON_ERROR_NONE) {
			return ['success' => FALSE, 'message' => 'API 응답 파싱 오류'];
		}

		if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
			return [
				'success' => TRUE,
				'data' => $result['candidates'][0]['content']['parts'][0]['text']
			];
		}

		return ['success' => FALSE, 'message' => 'API 응답 형식 오류'];
	}
}
