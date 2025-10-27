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
		$charge_amount = $this->input->post('charge_amount');

		if (!$package_idx || !$charge_amount) {
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

		// 사용자 정보 조회
		$this->load->model('User_model');
		$user_info = $this->User_model->get_user_info($user_id);

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

	/**
	 * 파일 위치: application/controllers/Payment.php
	 * 역할: 결제 승인 처리 (ReturnUrl)
	 */
	public function return_url()
	{
		$tid = $this->input->post('Tid');
		$tr_auth_key = $this->input->post('TrAuthKey');

		if (!$tid || !$tr_auth_key) {
			$this->session->set_flashdata('error', '결제 정보가 올바르지 않습니다.');
			redirect('send/popup');
			return;
		}

		// 승인 요청
		$approval_result = $this->Payment_model->request_approval($tid, $tr_auth_key);

		if ($approval_result['success']) {
			// 결제 성공 - 충전 처리
			$payment_info = $approval_result['data'];

			// MallReserved에서 조직ID, 사용자ID, 패키지IDX 추출
			$reserved_data = json_decode($payment_info['MallReserved'], true);

			$org_id = $reserved_data['org_id'];
			$user_id = $reserved_data['user_id'];
			$package_idx = $reserved_data['package_idx'];
			$charge_amount = intval($payment_info['Amt']);

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
}
