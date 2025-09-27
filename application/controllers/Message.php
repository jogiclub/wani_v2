<?php
/**
 * 파일 위치: application/controllers/Message.php
 * 역할: 메시지 관련 처리 컨트롤러
 */

defined('BASEPATH') or exit('No direct script access allowed');

class Message extends MY_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model('Message_model');

		// 로그인 확인
		if (!$this->session->userdata('user_id')) {
			redirect('login');
		}
	}

	/**
	 * 메시지 목록 조회 (AJAX)
	 */
	public function get_messages()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$user_id = $this->session->userdata('user_id');
		$org_id = $this->get_active_org_id();

		if (!$org_id) {
			echo json_encode(array(
				'success' => false,
				'message' => '조직 정보를 찾을 수 없습니다.'
			));
			return;
		}

		// 읽지 않은 메시지 목록 조회
		$unread_messages = $this->Message_model->get_unread_messages($user_id, $org_id);
		$unread_count = $this->Message_model->get_unread_count($user_id, $org_id);

		echo json_encode(array(
			'success' => true,
			'messages' => $unread_messages,
			'unread_count' => $unread_count
		));
	}

	/**
	 * 메시지 읽음 처리 (AJAX)
	 */
	public function mark_as_read()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$message_idx = $this->input->post('message_idx');
		$user_id = $this->session->userdata('user_id');

		if (!$message_idx) {
			echo json_encode(array(
				'success' => false,
				'message' => '메시지 정보가 없습니다.'
			));
			return;
		}

		// 메시지 소유권 확인
		$message = $this->Message_model->get_message_by_idx($message_idx, $user_id);
		if (!$message) {
			echo json_encode(array(
				'success' => false,
				'message' => '메시지를 찾을 수 없습니다.'
			));
			return;
		}

		// 읽음 처리
		$result = $this->Message_model->mark_as_read($message_idx, $user_id);

		if ($result) {
			echo json_encode(array(
				'success' => true,
				'message' => '메시지가 읽음으로 처리되었습니다.'
			));
		} else {
			echo json_encode(array(
				'success' => false,
				'message' => '메시지 처리 중 오류가 발생했습니다.'
			));
		}
	}

	/**
	 * 모든 메시지 읽음 처리 (AJAX)
	 */
	public function mark_all_as_read()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$user_id = $this->session->userdata('user_id');
		$org_id = $this->get_active_org_id();

		if (!$org_id) {
			echo json_encode(array(
				'success' => false,
				'message' => '조직 정보를 찾을 수 없습니다.'
			));
			return;
		}

		// 읽지 않은 메시지 목록 조회
		$unread_messages = $this->Message_model->get_unread_messages($user_id, $org_id);
		$message_ids = array_column($unread_messages, 'idx');

		if (empty($message_ids)) {
			echo json_encode(array(
				'success' => true,
				'message' => '읽지 않은 메시지가 없습니다.'
			));
			return;
		}

		// 모든 메시지 읽음 처리
		$result = $this->Message_model->mark_multiple_as_read($message_ids, $user_id);

		if ($result) {
			echo json_encode(array(
				'success' => true,
				'message' => '모든 메시지가 읽음으로 처리되었습니다.'
			));
		} else {
			echo json_encode(array(
				'success' => false,
				'message' => '메시지 처리 중 오류가 발생했습니다.'
			));
		}
	}

	/**
	 * 메시지 삭제 (AJAX)
	 */
	public function delete_message()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$message_idx = $this->input->post('message_idx');
		$user_id = $this->session->userdata('user_id');

		if (!$message_idx) {
			echo json_encode(array(
				'success' => false,
				'message' => '메시지 정보가 없습니다.'
			));
			return;
		}

		// 메시지 소유권 확인
		$message = $this->Message_model->get_message_by_idx($message_idx, $user_id);
		if (!$message) {
			echo json_encode(array(
				'success' => false,
				'message' => '메시지를 찾을 수 없습니다.'
			));
			return;
		}

		// 메시지 삭제
		$result = $this->Message_model->delete_message($message_idx, $user_id);

		if ($result) {
			echo json_encode(array(
				'success' => true,
				'message' => '메시지가 삭제되었습니다.'
			));
		} else {
			echo json_encode(array(
				'success' => false,
				'message' => '메시지 삭제 중 오류가 발생했습니다.'
			));
		}
	}
}
?>
