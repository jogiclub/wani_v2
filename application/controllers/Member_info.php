<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Member_info extends CI_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model('Member_model');
		$this->load->model('Org_model');
		$this->load->model('Memo_model');
		$this->load->model('Detail_field_model');
		$this->load->helper('url');
	}

	/**
	 * 회원 정보 조회 페이지 (패스코드 입력 필요)
	 */
	public function index($org_id = null, $member_idx = null)
	{
		if (!$org_id || !$member_idx) {
			show_404();
			return;
		}

		// POST 요청 - 패스코드 검증
		if ($this->input->post('passcode')) {
			$this->verify_and_show_member_info($org_id, $member_idx);
			return;
		}

		// GET 요청 - 패스코드 입력 페이지 표시
		$data = [
			'org_id' => $org_id,
			'member_idx' => $member_idx
		];
		$this->load->view('member_info_passcode', $data);
	}

	/**
	 * 패스코드 검증 및 회원 정보 표시
	 */
	private function verify_and_show_member_info($org_id, $member_idx)
	{
		$passcode = $this->input->post('passcode');

		if (!$passcode) {
			$this->show_error_page('패스코드를 입력해주세요.');
			return;
		}

		// 회원 정보 조회 및 패스코드 검증
		$member_info = $this->Member_model->get_member_by_passcode($member_idx, $passcode);

		if (!$member_info || $member_info['org_id'] != $org_id) {
			$this->show_error_page('올바르지 않은 패스코드입니다.');
			return;
		}

		// 조직 정보 조회
		$org_info = $this->Org_model->get_org_detail_by_id($org_id);

		if (!$org_info) {
			$this->show_error_page('조직 정보를 찾을 수 없습니다.');
			return;
		}

		// 상세필드 조회
		$detail_fields = $this->Detail_field_model->get_detail_fields_by_org($org_id);

		// 회원 상세정보 조회
		$member_detail = [];
		if (!empty($member_info['member_detail'])) {
			$member_detail = json_decode($member_info['member_detail'], true) ?: [];
		}

		// 정착메모 타입 확인 및 추가
		$settlement_memo_type = $this->ensure_settlement_memo_type($org_id);

		// 정착메모 목록 조회
		$settlement_memos = $this->get_settlement_memos($member_idx, $settlement_memo_type);

		$data = [
			'org_id' => $org_id,
			'org_info' => $org_info,
			'member_info' => $member_info,
			'detail_fields' => $detail_fields,
			'member_detail' => $member_detail,
			'settlement_memos' => $settlement_memos,
			'passcode' => $passcode
		];

		$this->load->view('member_info', $data);
	}

	/**
	 * 정착메모 타입 확인 및 추가
	 */
	private function ensure_settlement_memo_type($org_id)
	{
		// memo_name 필드에서 메모 타입 조회
		$this->db->select('memo_name');
		$this->db->from('wb_org');
		$this->db->where('org_id', $org_id);
		$query = $this->db->get();
		$result = $query->row_array();

		$memo_types = [];
		if ($result && !empty($result['memo_name'])) {
			$decoded = json_decode($result['memo_name'], true);
			if (is_array($decoded)) {
				$memo_types = $decoded;
			}
		}

		// '정착메모'가 있는지 확인
		if (!in_array('정착메모', $memo_types)) {
			// 정착메모 타입 추가
			$memo_types[] = '정착메모';

			// DB 업데이트 (memo_name 필드 사용)
			$this->db->where('org_id', $org_id);
			$this->db->update('wb_org', [
				'memo_name' => json_encode($memo_types, JSON_UNESCAPED_UNICODE),
				'modi_date' => date('Y-m-d H:i:s')
			]);
		}

		return '정착메모';
	}


	/**
	 * 정착메모 목록 조회
	 */
	private function get_settlement_memos($member_idx, $memo_type)
	{
		$this->db->select('idx, memo_content, regi_date');
		$this->db->from('wb_memo');
		$this->db->where('member_idx', $member_idx);
		$this->db->where('memo_type', '정착메모');
		$this->db->where('del_yn', 'N');
		$this->db->order_by('regi_date', 'DESC');

		$query = $this->db->get();
		return $query->result_array();
	}

	/**
	 * 오류 페이지 표시
	 */
	private function show_error_page($message)
	{
		$data = ['error_message' => $message];
		$this->load->view('member_card_error', $data);
	}


	/**
	 * 정착메모 추가 (AJAX)
	 */
	public function add_settlement_memo()
	{
		$this->output->set_content_type('application/json');

		$member_idx = $this->input->post('member_idx');
		$org_id = $this->input->post('org_id');
		$passcode = $this->input->post('passcode');
		$memo_content = $this->input->post('memo_content');

		// 패스코드 검증
		$member_info = $this->Member_model->get_member_by_passcode($member_idx, $passcode);

		if (!$member_info || $member_info['org_id'] != $org_id) {
			echo json_encode(['success' => false, 'message' => '권한이 없습니다.']);
			return;
		}

		if (empty($memo_content)) {
			echo json_encode(['success' => false, 'message' => '메모 내용을 입력해주세요.']);
			return;
		}

		try {
			// 정착메모 타입 확인 및 추가
			$this->ensure_settlement_memo_type($org_id);

			// 메모 저장 - memo_type을 텍스트로 저장
			$data = [
				'member_idx' => $member_idx,
				'memo_type' => '정착메모',
				'memo_content' => $memo_content,
				'att_date' => date('Y-m-d'),
				'user_id' => 'church_staff',
				'regi_date' => date('Y-m-d H:i:s'),
				'del_yn' => 'N'
			];

			$this->db->trans_start();
			$this->db->insert('wb_memo', $data);
			$insert_id = $this->db->insert_id();
			$this->db->trans_complete();

			if ($this->db->trans_status() === TRUE && $insert_id) {
				log_message('info', "정착메모 추가 성공 - member_idx: {$member_idx}, memo_type: 정착메모, insert_id: {$insert_id}");
				echo json_encode(['success' => true, 'message' => '메모가 추가되었습니다.']);
			} else {
				log_message('error', '정착메모 추가 실패 - Data: ' . print_r($data, true));
				echo json_encode(['success' => false, 'message' => '메모 추가에 실패했습니다.']);
			}

		} catch (Exception $e) {
			log_message('error', '정착메모 추가 오류: ' . $e->getMessage());
			echo json_encode(['success' => false, 'message' => '메모 추가 중 오류가 발생했습니다.']);
		}
	}

	/**
	 * 정착메모 수정 (AJAX)
	 */
	public function update_settlement_memo()
	{
		$this->output->set_content_type('application/json');

		$memo_idx = $this->input->post('memo_idx');
		$member_idx = $this->input->post('member_idx');
		$org_id = $this->input->post('org_id');
		$passcode = $this->input->post('passcode');
		$memo_content = $this->input->post('memo_content');

		// 패스코드 검증
		$member_info = $this->Member_model->get_member_by_passcode($member_idx, $passcode);

		if (!$member_info || $member_info['org_id'] != $org_id) {
			echo json_encode(['success' => false, 'message' => '권한이 없습니다.']);
			return;
		}

		if (empty($memo_content)) {
			echo json_encode(['success' => false, 'message' => '메모 내용을 입력해주세요.']);
			return;
		}

		// 메모 수정
		$this->db->where('idx', $memo_idx);
		$this->db->where('member_idx', $member_idx);
		$result = $this->db->update('wb_memo', [
			'memo_content' => $memo_content,
			'modi_date' => date('Y-m-d H:i:s')
		]);

		if ($result) {
			echo json_encode(['success' => true, 'message' => '메모가 수정되었습니다.']);
		} else {
			echo json_encode(['success' => false, 'message' => '메모 수정에 실패했습니다.']);
		}
	}

	/**
	 * 정착메모 삭제 (AJAX)
	 */
	public function delete_settlement_memo()
	{
		$this->output->set_content_type('application/json');

		$memo_idx = $this->input->post('memo_idx');
		$member_idx = $this->input->post('member_idx');
		$org_id = $this->input->post('org_id');
		$passcode = $this->input->post('passcode');

		// 패스코드 검증
		$member_info = $this->Member_model->get_member_by_passcode($member_idx, $passcode);

		if (!$member_info || $member_info['org_id'] != $org_id) {
			echo json_encode(['success' => false, 'message' => '권한이 없습니다.']);
			return;
		}

		// 메모 삭제 (del_yn = 'Y')
		$this->db->where('idx', $memo_idx);
		$this->db->where('member_idx', $member_idx);
		$result = $this->db->update('wb_memo', [
			'del_yn' => 'Y',
			'modi_date' => date('Y-m-d H:i:s')
		]);

		if ($result) {
			echo json_encode(['success' => true, 'message' => '메모가 삭제되었습니다.']);
		} else {
			echo json_encode(['success' => false, 'message' => '메모 삭제에 실패했습니다.']);
		}
	}
}
