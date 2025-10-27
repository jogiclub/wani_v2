<?php
/**
 * 파일 위치: application/models/Payment_model.php
 * 역할: PG 결제 관련 데이터 처리 및 비즈니스 로직
 */
defined('BASEPATH') or exit('No direct script access allowed');

class Payment_model extends CI_Model
{
	private $pg_config;

	public function __construct()
	{
		parent::__construct();

		// PG 설정 로드
		$this->load->config('payment');
		$this->pg_config = $this->config->item('smartro_pg');
	}

	/**
	 * 파일 위치: application/models/Payment_model.php
	 * 역할: 결제 요청 파라미터 생성
	 */
	public function generate_payment_params($org_id, $user_id, $package_idx, $charge_amount, $goods_name, $user_info)
	{
		$edi_date = date('YmdHis');
		$moid = 'CHARGE_' . $org_id . '_' . time();

		// 암호화 데이터 생성
		$encrypt_data = $this->generate_encrypt_data($edi_date, $charge_amount);

		// 예약 정보 (조직ID, 사용자ID, 패키지IDX)
		$mall_reserved = json_encode(array(
			'org_id' => $org_id,
			'user_id' => $user_id,
			'package_idx' => $package_idx
		));

		$params = array(
			'PayMethod' => 'CARD',
			'GoodsCnt' => '1',
			'GoodsName' => $goods_name,
			'Amt' => strval($charge_amount),
			'Moid' => $moid,
			'Mid' => $this->pg_config['mid'],
			'ReturnUrl' => base_url('payment/return_url'),
			'StopUrl' => base_url('payment/stop_url'),
			'BuyerName' => $user_info['user_name'],
			'BuyerTel' => $user_info['user_phone'] ?? '01000000000',
			'BuyerEmail' => $user_info['user_email'],
			'EncryptData' => $encrypt_data,
			'EdiDate' => $edi_date,
			'MallReserved' => $mall_reserved,
			'TaxAmt' => '',
			'TaxFreeAmt' => '',
			'VatAmt' => ''
		);

		return $params;
	}

	/**
	 * 파일 위치: application/models/Payment_model.php
	 * 역할: 암호화 데이터 생성 (SHA256)
	 */
	private function generate_encrypt_data($edi_date, $amt)
	{
		$string = $edi_date . $this->pg_config['mid'] . $amt . $this->pg_config['merchant_key'];
		return base64_encode(hash('sha256', $string, true));
	}

	/**
	 * 파일 위치: application/models/Payment_model.php
	 * 역할: PG 승인 요청
	 */
	public function request_approval($tid, $tr_auth_key)
	{
		$url = $this->pg_config['approval_url'];

		$post_data = array(
			'Tid' => $tid,
			'TrAuthKey' => $tr_auth_key
		);

		try {
			$response = $this->call_pg_api($url, $post_data);

			if ($response && isset($response['ResultCode']) && $response['ResultCode'] === '3001') {
				return array(
					'success' => true,
					'data' => $response
				);
			} else {
				return array(
					'success' => false,
					'message' => $response['ResultMsg'] ?? '승인 요청 실패',
					'error_code' => $response['ResultCode'] ?? ''
				);
			}
		} catch (Exception $e) {
			log_message('error', 'PG approval request failed: ' . $e->getMessage());
			return array(
				'success' => false,
				'message' => '승인 요청 중 오류가 발생했습니다.'
			);
		}
	}

	/**
	 * 파일 위치: application/models/Payment_model.php
	 * 역할: PG API 호출 공통 함수
	 */
	private function call_pg_api($url, $post_data)
	{
		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post_data));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'Content-Type: application/json',
			'Accept: application/json'
		));
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
		curl_setopt($ch, CURLOPT_TIMEOUT, 30);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

		$response = curl_exec($ch);
		$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

		if (curl_errno($ch)) {
			$error = curl_error($ch);
			curl_close($ch);
			throw new Exception('cURL Error: ' . $error);
		}

		curl_close($ch);

		if ($http_code !== 200) {
			throw new Exception('HTTP Error: ' . $http_code);
		}

		$result = json_decode($response, true);

		if (json_last_error() !== JSON_ERROR_NONE) {
			throw new Exception('JSON Decode Error: ' . json_last_error_msg());
		}

		return $result;
	}

	/**
	 * 파일 위치: application/models/Payment_model.php
	 * 역할: 결제 상태 업데이트
	 */
	public function update_payment_status($tid, $status, $payment_info)
	{
		$this->db->trans_start();

		try {
			// 결제 정보에서 조직 정보 추출
			$reserved_data = json_decode($payment_info['MallReserved'], true);

			$data = array(
				'org_id' => $reserved_data['org_id'],
				'user_id' => $reserved_data['user_id'],
				'tid' => $tid,
				'moid' => $payment_info['Moid'],
				'amount' => intval($payment_info['Amt']),
				'payment_method' => $payment_info['PayMethod'],
				'payment_status' => $status,
				'auth_code' => $payment_info['AuthCode'] ?? '',
				'auth_date' => $payment_info['AuthDate'] ?? '',
				'card_name' => $payment_info['FnName'] ?? '',
				'card_num' => $payment_info['FnNm'] ?? '',
				'payment_data' => json_encode($payment_info),
				'created_at' => date('Y-m-d H:i:s')
			);

			$this->db->insert('wb_payment_log', $data);

			$this->db->trans_complete();

			return $this->db->trans_status();

		} catch (Exception $e) {
			$this->db->trans_rollback();
			log_message('error', 'Payment status update failed: ' . $e->getMessage());
			return false;
		}
	}

	/**
	 * 파일 위치: application/models/Payment_model.php
	 * 역할: 결제 내역 조회
	 */
	public function get_payment_history($org_id, $page = 1, $per_page = 10)
	{
		$offset = ($page - 1) * $per_page;

		// 전체 개수
		$this->db->from('wb_payment_log');
		$this->db->where('org_id', $org_id);
		$total_count = $this->db->count_all_results();

		// 목록 조회
		$this->db->select('p.*, u.user_name');
		$this->db->from('wb_payment_log p');
		$this->db->join('wb_user u', 'p.user_id = u.user_id', 'left');
		$this->db->where('p.org_id', $org_id);
		$this->db->order_by('p.created_at', 'DESC');
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
	 * 파일 위치: application/models/Payment_model.php
	 * 역할: SignValue 검증
	 */
	public function verify_sign_value($tid, $result_code, $sign_value)
	{
		$tid_part1 = substr($tid, 0, 10);
		$tid_part2 = substr($tid, 10, 5);
		$tid_part3 = substr($tid, 15);

		$verify_string = $tid_part1 . $result_code . $tid_part2 . $this->pg_config['merchant_key'] . $tid_part3;
		$calculated_sign = base64_encode(hash('sha256', $verify_string, true));

		return $calculated_sign === $sign_value;
	}
}
