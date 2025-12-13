<?php
/**
 * 파일 위치: application/controllers/Payment.php
 * 역할: PG 결제 처리를 담당하는 컨트롤러
 */
defined('BASEPATH') or exit('No direct script access allowed');

class Payment extends MY_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model('Payment_model');
		$this->load->model('Send_model');
		$this->load->library('session');
		$this->load->helper('url');
	}

	/**
	 * 파일 위치: application/controllers/Payment.php
	 * 역할: 결제 요청 페이지 표시
	 */
	public function request()
	{
		// 현재 PG 설정 확인
		$this->load->config('payment');
		$pg_config = $this->config->item('smartro_pg');

		// 디버깅 (개발 시에만 사용)
		if ($pg_config['environment'] === 'development') {
			log_message('debug', '=== PG Environment Info ===');
			log_message('debug', 'Host: ' . $pg_config['current_host']);
			log_message('debug', 'Mode: ' . $pg_config['mode']);
			log_message('debug', 'MID: ' . $pg_config['mid']);
		}

		$user_id = $this->session->userdata('user_id');
		if (!$user_id) {
			redirect('login');
			return;
		}

		$org_id = $this->input->cookie('activeOrg');
		if (!$org_id) {
			$this->session->set_flashdata('error', '조직 정보를 찾을 수 없습니다.');
			redirect('dashboard');
			return;
		}

		$package_idx = $this->input->post('package_idx');

		if (!$package_idx) {
			$this->session->set_flashdata('error', '결제 정보가 올바르지 않습니다.');
			redirect('send/popup');
			return;
		}

		// 패키지 정보 조회
		$package_info = $this->Send_model->get_package_info($package_idx);
		if (!$package_info) {
			$this->session->set_flashdata('error', '패키지 정보를 찾을 수 없습니다.');
			redirect('send/popup');
			return;
		}

		// 패키지 정보에서 금액 가져오기 (올바른 필드명: package_amount)
		$charge_amount = intval($package_info['package_amount']);

		if ($charge_amount <= 0) {
			$this->session->set_flashdata('error', '결제 금액 정보가 올바르지 않습니다.');
			redirect('send/popup');
			return;
		}

		// 사용자 정보 조회
		$this->load->model('User_management_model');
		$user_info = $this->User_management_model->get_user_info($user_id);

		if (!$user_info) {
			$this->session->set_flashdata('error', '사용자 정보를 찾을 수 없습니다.');
			redirect('send/popup');
			return;
		}

		// 결제 파라미터 생성
		$payment_data = $this->Payment_model->generate_payment_params(
			$org_id,
			$user_id,
			$package_idx,
			$charge_amount,
			$package_info['package_name'],
			$user_info
		);

		$data = array(
			'payment_data' => $payment_data,
			'package_info' => $package_info,
			'charge_amount' => $charge_amount
		);

		$this->load->view('payment/request', $data);
	}

	public function return_url()
	{
		$tid = $this->input->post('Tid');
		$tr_auth_key = $this->input->post('TrAuthKey');

		// 디버깅: 전체 POST 데이터 로그
		log_message('debug', 'Return URL - POST data: ' . json_encode($this->input->post()));

		if (!$tid || !$tr_auth_key) {
			$this->session->set_flashdata('error', '결제 정보가 올바르지 않습니다.');
			redirect('send/popup');
			return;
		}

		// 승인 요청
		$approval_result = $this->Payment_model->request_approval($tid, $tr_auth_key);

		// 디버깅: 승인 결과 로그
		log_message('debug', 'Approval result: ' . json_encode($approval_result));

		if ($approval_result['success']) {
			// 결제 성공 - 충전 처리
			$payment_info = $approval_result['data'];

			// 디버깅: payment_info 전체 로그
			log_message('debug', 'Payment info: ' . json_encode($payment_info));

			// MerchantReserved에서 조직ID, 사용자ID, 패키지IDX 추출
			// 스마트로페이는 MerchantReserved로 반환함
			$merchant_reserved = isset($payment_info['MerchantReserved'])
				? $payment_info['MerchantReserved']
				: (isset($payment_info['MallReserved']) ? $payment_info['MallReserved'] : '');

			// 디버깅: MerchantReserved 원본 로그
			log_message('debug', 'MerchantReserved raw: ' . $merchant_reserved);

			// Base64 디코딩 시도
			$reserved_decoded = base64_decode($merchant_reserved);

			// 디버깅: 디코딩된 데이터 로그
			log_message('debug', 'MerchantReserved decoded: ' . $reserved_decoded);

			// JSON 파싱
			$reserved_data = json_decode($reserved_decoded, true);

			// 디버깅: 파싱 결과 로그
			log_message('debug', 'Reserved data: ' . json_encode($reserved_data));
			log_message('debug', 'JSON last error: ' . json_last_error_msg());

			// 파싱 실패 시 에러 처리
			if (!$reserved_data || !isset($reserved_data['org_id']) || !isset($reserved_data['user_id']) || !isset($reserved_data['package_idx'])) {
				log_message('error', 'MerchantReserved parsing failed. Raw: ' . $merchant_reserved . ', Decoded: ' . $reserved_decoded);

				$data = array(
					'success' => false,
					'message' => '결제 정보 파싱에 실패했습니다. 고객센터에 문의해주세요. (TID: ' . $tid . ')',
					'payment_info' => $payment_info
				);
				$this->load->view('payment/result', $data);
				return;
			}

			$org_id = intval($reserved_data['org_id']);
			$user_id = $reserved_data['user_id'];
			$package_idx = intval($reserved_data['package_idx']);
			$charge_amount = intval($payment_info['Amt']);

			// 디버깅: 추출된 데이터 로그
			log_message('debug', "Extracted - org_id: $org_id, user_id: $user_id, package_idx: $package_idx, amount: $charge_amount");

			// 충전 처리
			$charge_result = $this->Send_model->charge_sms($org_id, $user_id, $package_idx, $charge_amount);

			if ($charge_result) {
				// 결제 내역 업데이트
				$this->Payment_model->update_payment_status(
					$tid,
					'completed',
					$payment_info
				);

				$data = array(
					'success' => true,
					'message' => number_format($charge_amount) . '원이 충전되었습니다.',
					'payment_info' => $payment_info
				);
			} else {
				$data = array(
					'success' => false,
					'message' => '충전 처리 중 오류가 발생했습니다.',
					'payment_info' => $payment_info
				);
			}
		} else {
			// 결제 실패
			$data = array(
				'success' => false,
				'message' => $approval_result['message'],
				'error_code' => $approval_result['error_code'] ?? ''
			);
		}

		$this->load->view('payment/result', $data);
	}

	/**
	 * 파일 위치: application/controllers/Payment.php
	 * 역할: 결제 중단 처리 (StopUrl)
	 */
	public function stop_url()
	{
		$data = array(
			'success' => false,
			'message' => '결제가 취소되었습니다.'
		);

		$this->load->view('payment/result', $data);
	}

	/**
	 * 파일 위치: application/controllers/Payment.php
	 * 역할: 결제 내역 조회 (AJAX)
	 */
	public function get_payment_history()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$user_id = $this->session->userdata('user_id');
		if (!$user_id) {
			echo json_encode(array('success' => false, 'message' => '인증이 필요합니다.'));
			return;
		}

		$org_id = $this->input->post('org_id');
		$page = intval($this->input->post('page')) ?: 1;
		$per_page = intval($this->input->post('per_page')) ?: 10;

		if (!$org_id) {
			echo json_encode(array('success' => false, 'message' => '조직 정보가 필요합니다.'));
			return;
		}

		$result = $this->Payment_model->get_payment_history($org_id, $page, $per_page);

		echo json_encode(array(
			'success' => true,
			'data' => $result
		));
	}


	/**
	 * 파일 위치: application/controllers/Payment.php
	 * 역할: 결제 취소 요청 (AJAX)
	 */
	public function cancel()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$user_id = $this->session->userdata('user_id');
		if (!$user_id) {
			echo json_encode(array('success' => false, 'message' => '인증이 필요합니다.'));
			return;
		}

		$tid = $this->input->post('tid');
		$cancel_amount = $this->input->post('cancel_amount');
		$cancel_reason = $this->input->post('cancel_reason');

		if (!$tid || !$cancel_amount) {
			echo json_encode(array('success' => false, 'message' => '취소 정보가 올바르지 않습니다.'));
			return;
		}

		// 결제 정보 조회
		$payment_info = $this->Payment_model->get_payment_by_tid($tid);

		if (!$payment_info) {
			echo json_encode(array('success' => false, 'message' => '결제 정보를 찾을 수 없습니다.'));
			return;
		}

		// 권한 확인 (해당 조직의 사용자인지)
		$org_id = $this->input->cookie('activeOrg');
		if ($payment_info['org_id'] != $org_id) {
			echo json_encode(array('success' => false, 'message' => '권한이 없습니다.'));
			return;
		}

		// 취소 요청
		$cancel_result = $this->Payment_model->request_cancel(
			$tid,
			$cancel_amount,
			$cancel_reason,
			$user_id
		);

		if ($cancel_result['success']) {
			// 취소 성공 - 충전 내역도 처리
			$this->Send_model->cancel_charge($payment_info['org_id'], $cancel_amount);

			echo json_encode(array(
				'success' => true,
				'message' => '결제가 취소되었습니다.',
				'data' => $cancel_result['data']
			));
		} else {
			echo json_encode(array(
				'success' => false,
				'message' => $cancel_result['message']
			));
		}
	}

}
