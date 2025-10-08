<?php
/**
 * 파일 위치: application/controllers/Offer.php
 * 역할: 결연교회 추천 및 선택 처리 (수정)
 */
defined('BASEPATH') or exit('No direct script access allowed');

class Offer extends CI_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model('Member_model');
		$this->load->model('Org_model');
		$this->load->model('Transfer_org_model');
		$this->load->library('session');
		$this->load->helper('url');
	}

	/**
	 * 결연교회 추천 페이지 (로그인 불필요)
	 */
	public function index($org_id = null, $member_idx = null, $member_passcode = null)
	{
		if (!$org_id || !$member_idx || !$member_passcode) {
			show_404();
			return;
		}

		// 회원 정보 조회 및 패스코드 검증
		$member_info = $this->Member_model->get_member_by_passcode($member_idx, $member_passcode);

		if (!$member_info || $member_info['org_id'] != $org_id) {
			$this->load->view('offer_error', array(
				'error_message' => '유효하지 않은 링크입니다.'
			));
			return;
		}

		// 조직 정보 조회
		$org_info = $this->Org_model->get_org_detail_by_id($org_id);

		if (!$org_info) {
			$this->load->view('offer_error', array(
				'error_message' => '조직 정보를 찾을 수 없습니다.'
			));
			return;
		}

		// 링크 생성 시간 확인 (72시간 유효)
		$current_time = new DateTime();
		$link_created_time = new DateTime($member_info['modi_date'] ?: $member_info['regi_date']);
		$time_diff = $current_time->getTimestamp() - $link_created_time->getTimestamp();

		$valid_hours = 72;
		if ($time_diff > ($valid_hours * 3600)) {
			$this->load->view('offer_error', array(
				'error_message' => '링크 유효기간이 만료되었습니다. (72시간 경과)',
				'is_expired' => true
			));
			return;
		}

		// transfer_org_json에서 추천 교회 목록 조회 (수정됨)
		$recommended_churches = $this->get_recommended_churches_from_transfer_json($member_info);

		$data = array(
			'org_info' => $org_info,
			'member_info' => $member_info,
			'org_id' => $org_id,
			'member_idx' => $member_idx,
			'member_passcode' => $member_passcode,
			'recommended_churches' => $recommended_churches,
			'remaining_hours' => ceil(($valid_hours * 3600 - $time_diff) / 3600)
		);

		$this->load->view('offer', $data);
	}

	/**
	 * transfer_org_json에서 추천 교회 목록 조회 (새로 추가)
	 */
	private function get_recommended_churches_from_transfer_json($member_info)
	{
		// transfer_org_json 파싱
		if (empty($member_info['transfer_org_json'])) {
			return array();
		}

		$transfer_org_ids = json_decode($member_info['transfer_org_json'], true);

		if (!is_array($transfer_org_ids) || empty($transfer_org_ids)) {
			return array();
		}

		// 숫자형 ID만 추출 (selected 상태 제외)
		$church_ids = array();
		foreach ($transfer_org_ids as $key => $value) {
			if (is_numeric($value)) {
				$church_ids[] = $value;
			} elseif (is_numeric($key) && $value !== 'selected') {
				$church_ids[] = $key;
			}
		}

		if (empty($church_ids)) {
			return array();
		}

		// wb_transfer_org에서 교회 정보 조회
		$this->db->select('transfer_org_id, transfer_org_name, transfer_org_address, transfer_org_phone, transfer_org_rep, transfer_org_manager, transfer_org_tag');
		$this->db->from('wb_transfer_org');
		$this->db->where_in('transfer_org_id', $church_ids);
		$this->db->where('del_yn', 'N');
		$this->db->order_by('transfer_org_name', 'ASC');

		$query = $this->db->get();
		return $query->result_array();
	}

	/**
	 * 교회 선택 처리
	 */
	public function select_church()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$org_id = $this->input->post('org_id');
		$member_idx = $this->input->post('member_idx');
		$member_passcode = $this->input->post('member_passcode');
		$selected_church_id = $this->input->post('selected_church_id');

		// 회원 정보 및 패스코드 재검증
		$member_info = $this->Member_model->get_member_by_passcode($member_idx, $member_passcode);

		if (!$member_info || $member_info['org_id'] != $org_id) {
			echo json_encode(array('success' => false, 'message' => '유효하지 않은 요청입니다.'));
			return;
		}

		// 선택된 교회 정보 조회 (wb_transfer_org에서)
		$this->db->select('transfer_org_id, transfer_org_name, transfer_org_address');
		$this->db->from('wb_transfer_org');
		$this->db->where('transfer_org_id', $selected_church_id);
		$this->db->where('del_yn', 'N');
		$query = $this->db->get();
		$selected_church = $query->row_array();

		if (!$selected_church) {
			echo json_encode(array('success' => false, 'message' => '선택하신 교회 정보를 찾을 수 없습니다.'));
			return;
		}

		// 기존 transfer_org_json 조회
		$existing_json = $member_info['transfer_org_json'];
		$transfer_data = array();

		if (!empty($existing_json)) {
			$transfer_data = json_decode($existing_json, true);
			if (!is_array($transfer_data)) {
				$transfer_data = array();
			}
		}



		// 선택된 교회 정보 추가
		// 형식: [교회ID: "selected", 나머지는 기존 데이터]
		$transfer_data[$selected_church_id] = "selected";

		// JSON으로 변환하여 저장
		$update_data = array(
			'transfer_org_json' => json_encode($transfer_data, JSON_UNESCAPED_UNICODE),
			'modi_date' => date('Y-m-d H:i:s')
		);

		$this->db->trans_start();

		$this->db->where('member_idx', $member_idx);
		$this->db->where('org_id', $org_id);
		$this->db->update('wb_member', $update_data);

		$this->db->trans_complete();

		if ($this->db->trans_status() === FALSE) {
			echo json_encode(array('success' => false, 'message' => '교회 선택 처리에 실패했습니다.'));
		} else {
			echo json_encode(array(
				'success' => true,
				'message' => '선택 감사합니다.',
				'church_name' => $selected_church['transfer_org_name']
			));
		}
	}
}
