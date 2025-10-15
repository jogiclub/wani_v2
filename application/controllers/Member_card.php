<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Member_card extends CI_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model('Member_model');
		$this->load->model('Member_area_model');
		$this->load->model('Detail_field_model');
		$this->load->model('Org_model');
		$this->load->model('Message_model');
		$this->load->library('session');
		$this->load->helper('url');
	}

	/**
	 * 파일 위치: application/controllers/Member_card.php - register() 함수
	 * 역할: 회원 등록 페이지 표시 (로그인 불필요)
	 */
	public function register($org_id = null, $area_idx = null, $invite_code = null)
	{
		if (!$org_id || !$area_idx || !$invite_code) {
			show_404();
			return;
		}

		// 조직 정보 조회 및 초대코드 검증
		$org_info = $this->Org_model->get_org_detail_by_id($org_id);

		if (!$org_info || $org_info['invite_code'] !== $invite_code) {
			$this->load->view('member_card_error', array(
				'error_message' => '유효하지 않은 초대 링크입니다.'
			));
			return;
		}

		// 그룹 정보 조회
		$area_info = $this->Member_area_model->get_area_by_idx($area_idx);

		if (!$area_info || $area_info['org_id'] != $org_id) {
			$this->load->view('member_card_error', array(
				'error_message' => '그룹 정보를 찾을 수 없습니다.'
			));
			return;
		}

		// 직위/직책 정보
		$position_names = array();
		$duty_names = array();

		if (!empty($org_info['position_name'])) {
			$position_names = json_decode($org_info['position_name'], true);
			if (!is_array($position_names)) {
				$position_names = array();
			}
		}

		if (!empty($org_info['duty_name'])) {
			$duty_names = json_decode($org_info['duty_name'], true);
			if (!is_array($duty_names)) {
				$duty_names = array();
			}
		}

		// 상세필드 정보 조회
		$detail_fields = $this->Detail_field_model->get_detail_fields_by_org($org_id);
		$active_fields = array_filter($detail_fields, function($field) {
			return $field['is_active'] === 'Y';
		});

		$data = array(
			'org_info' => $org_info,
			'area_info' => $area_info,
			'org_id' => $org_id,
			'area_idx' => $area_idx,
			'invite_code' => $invite_code,
			'position_names' => $position_names,
			'duty_names' => $duty_names,
			'detail_fields' => $active_fields
		);

		$this->load->view('member_card', $data);
	}

	/**
	 * 파일 위치: application/controllers/Member_card.php - save_member() 함수
	 * 역할: 회원 등록 처리
	 */
	public function save_member()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$org_id = $this->input->post('org_id');
		$area_idx = $this->input->post('area_idx');
		$invite_code = $this->input->post('invite_code');

		// 조직 정보 조회 및 초대코드 재검증
		$org_info = $this->Org_model->get_org_detail_by_id($org_id);

		if (!$org_info || $org_info['invite_code'] !== $invite_code) {
			echo json_encode(array('success' => false, 'message' => '유효하지 않은 초대코드입니다.'));
			return;
		}

		// 그룹 정보 확인
		$area_info = $this->Member_area_model->get_area_by_idx($area_idx);

		if (!$area_info || $area_info['org_id'] != $org_id) {
			echo json_encode(array('success' => false, 'message' => '그룹 정보가 올바르지 않습니다.'));
			return;
		}

		// 필수 필드 확인
		$member_name = trim($this->input->post('member_name'));
		if (empty($member_name)) {
			echo json_encode(array('success' => false, 'message' => '이름은 필수 입력 항목입니다.'));
			return;
		}

		// 기본 회원 정보
		$member_data = array(
			'org_id' => $org_id,
			'area_idx' => $area_idx,
			'member_name' => $member_name,
			'member_sex' => $this->input->post('member_sex') ?: null,
			'member_nick' => $this->input->post('member_nick'),
			'position_name' => $this->input->post('position_name'),
			'duty_name' => $this->input->post('duty_name'),
			'member_phone' => $this->input->post('member_phone'),
			'member_birth' => $this->input->post('member_birth'),
			'member_address' => $this->input->post('member_address'),
			'member_address_detail' => $this->input->post('member_address_detail'),
			'regi_date' => date('Y-m-d H:i:s'),
			'del_yn' => 'N',
			'leader_yn' => 'N',
			'new_yn' => 'Y',
			'grade' => 0
		);

		// 상세필드 데이터 수집
		$detail_fields = $this->Detail_field_model->get_detail_fields_by_org($org_id);
		$member_detail = array();

		foreach ($detail_fields as $field) {
			if ($field['is_active'] === 'Y') {
				$field_value = $this->input->post('detail_' . $field['field_idx']);
				if ($field_value !== null && $field_value !== '') {
					$member_detail[$field['field_idx']] = $field_value;
				}
			}
		}

		// 상세필드 데이터를 JSON으로 저장
		if (!empty($member_detail)) {
			$member_data['member_detail'] = json_encode($member_detail, JSON_UNESCAPED_UNICODE);
		}

		// 회원 등록 및 메시지 추가
		$this->db->trans_start();

		$result = $this->Member_model->add_member($member_data);

		// 회원 등록 성공 시 메시지 추가
		if ($result) {
			$message_content = "{$member_name}님께서 온라인으로 {$area_info['area_name']}에 회원 등록이 되었습니다.";

			$message_data = array(
				'message_type' => 'online_signup',
				'message_title' => '온라인 회원 가입',
				'message_content' => $message_content,
				'message_date' => date('Y-m-d H:i:s'),
				'member_idx_list' => json_encode(array($result), JSON_UNESCAPED_UNICODE)
			);

			// 조직의 모든 사용자에게 메시지 발송
			$this->Message_model->send_message_to_org($org_id, $message_data);
		}

		$this->db->trans_complete();

		if ($this->db->trans_status() === FALSE || !$result) {
			echo json_encode(array('success' => false, 'message' => '회원 등록에 실패했습니다.'));
		} else {
			echo json_encode(array('success' => true, 'message' => '회원 등록이 완료되었습니다.'));
		}
	}
}
