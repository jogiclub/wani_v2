<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Org extends CI_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->library('session');
		$this->load->helper('url');
		$this->load->model('User_model');
		$this->load->model('Org_model');
		$this->load->helper('file');
		$this->load->library('upload');
		$this->load->library('email');
	}

	/**
	 * 헤더에서 사용할 조직 데이터 준비
	 */
	private function prepare_header_data() {
		if (!$this->session->userdata('user_id')) {
			return array();
		}

		$user_id = $this->session->userdata('user_id');
		$master_yn = $this->session->userdata('master_yn');

		$user_data = $this->User_model->get_user_by_id($user_id);

		if ($master_yn === "N") {
			$user_orgs = $this->Org_model->get_user_orgs($user_id);
		} else {
			$user_orgs = $this->Org_model->get_user_orgs_master($user_id);
		}

		$active_org_id = $this->input->cookie('activeOrg');
		$current_org = null;

		if ($active_org_id) {
			foreach ($user_orgs as $org) {
				if ($org['org_id'] == $active_org_id) {
					$current_org = $org;
					break;
				}
			}
		}

		if (!$current_org && !empty($user_orgs)) {
			$current_org = $user_orgs[0];
			$this->input->set_cookie('activeOrg', $current_org['org_id'], 86400);
		}

		return array(
			'user' => $user_data,
			'user_orgs' => $user_orgs,
			'current_org' => $current_org
		);
	}

	public function index() {
		if (!$this->session->userdata('user_id')) {
			redirect('login');
			return;
		}

		$user_id = $this->session->userdata('user_id');

		// 헤더 데이터 준비
		$header_data = $this->prepare_header_data();
		if (empty($header_data['user_orgs'])) {
			redirect('mypage');
			return;
		}

		$data = $header_data;
		$currentOrgId = $data['current_org']['org_id'];

		// POST로 조직 변경 요청이 있는 경우 처리
		$postOrgId = $this->input->post('org_id');
		if ($postOrgId) {
			$has_access = false;
			foreach ($data['user_orgs'] as $org) {
				if ($org['org_id'] == $postOrgId) {
					$has_access = true;
					$data['current_org'] = $org;
					$currentOrgId = $postOrgId;
					$this->input->set_cookie('activeOrg', $postOrgId, 86400);
					break;
				}
			}

			if (!$has_access) {
				show_error('접근 권한이 없는 조직입니다.', 403);
				return;
			}
		}

		$master_yn = $this->session->userdata('master_yn');

		// 사용자가 접근 가능한 모든 조직 목록
		if ($master_yn === "N") {
			$orgs = $this->Org_model->get_user_orgs($user_id);
		} else {
			$orgs = $this->Org_model->get_user_orgs_master($user_id);
		}

		foreach ($orgs as &$org) {
			$org['user_count'] = $this->User_model->get_org_user_count($org['org_id']);
			$org['user_level'] = $this->User_model->get_org_user_level($user_id, $org['org_id']);
			$org['user_master_yn'] = $this->session->userdata('master_yn');
		}

		$data['orgs'] = $orgs;

		// 현재 선택된 조직의 상세 정보 가져오기
		$data['selected_org_detail'] = $this->Org_model->get_org_detail_by_id($currentOrgId);

		// 현재 선택된 조직의 최고관리자 정보 가져오기
		$data['org_admin'] = $this->Org_model->get_org_admin($currentOrgId);

		$this->load->view('org_setting', $data);
	}

	/**
	 * 조직 정보 업데이트
	 */
	public function update_org_info() {
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$org_id = $this->input->post('org_id');
		$org_name = $this->input->post('org_name');
		$org_type = $this->input->post('org_type');
		$org_desc = $this->input->post('org_desc');
		$leader_name = $this->input->post('leader_name');
		$new_name = $this->input->post('new_name');

		if (!$org_id) {
			echo json_encode(array('success' => false, 'message' => '조직 ID가 필요합니다.'));
			return;
		}

		// 권한 검증
		$user_id = $this->session->userdata('user_id');
		$user_level = $this->User_model->get_org_user_level($user_id, $org_id);

		if ($user_level < 9 && $this->session->userdata('master_yn') !== 'Y') {
			echo json_encode(array('success' => false, 'message' => '조직 정보를 수정할 권한이 없습니다.'));
			return;
		}

		$update_data = array(
			'org_name' => $org_name,
			'org_type' => $org_type,
			'org_desc' => $org_desc,
			'leader_name' => $leader_name,
			'new_name' => $new_name,
			'modi_date' => date('Y-m-d H:i:s')
		);

		$result = $this->Org_model->update_org_info($org_id, $update_data);

		if ($result) {
			echo json_encode(array('success' => true, 'message' => '조직 정보가 수정되었습니다.'));
		} else {
			echo json_encode(array('success' => false, 'message' => '조직 정보 수정에 실패했습니다.'));
		}
	}

	/**
	 * 조직 아이콘 업로드
	 */
	public function upload_org_icon() {
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$org_id = $this->input->post('org_id');

		if (!$org_id) {
			echo json_encode(array('success' => false, 'message' => '조직 ID가 필요합니다.'));
			return;
		}

		// 권한 검증
		$user_id = $this->session->userdata('user_id');
		$user_level = $this->User_model->get_org_user_level($user_id, $org_id);

		if ($user_level < 9 && $this->session->userdata('master_yn') !== 'Y') {
			echo json_encode(array('success' => false, 'message' => '조직 아이콘을 변경할 권한이 없습니다.'));
			return;
		}

		// 업로드 디렉토리 설정
		$upload_path = './assets/uploads/org_icons/';
		if (!is_dir($upload_path)) {
			mkdir($upload_path, 0755, true);
		}

		// 업로드 설정
		$config['upload_path'] = $upload_path;
		$config['allowed_types'] = 'jpg|jpeg|png';
		$config['max_size'] = 2048; // 2MB
		$config['file_name'] = 'org_' . $org_id . '_' . time();
		$config['overwrite'] = TRUE;

		$this->upload->initialize($config);

		if ($this->upload->do_upload('org_icon')) {
			$upload_data = $this->upload->data();
			$file_path = '/assets/uploads/org_icons/' . $upload_data['file_name'];

			// 기존 아이콘 파일 삭제
			$current_org = $this->Org_model->get_org_detail_by_id($org_id);
			if ($current_org && $current_org['org_icon'] && file_exists('.' . $current_org['org_icon'])) {
				unlink('.' . $current_org['org_icon']);
			}

			// DB에 파일 경로 저장
			$update_data = array('org_icon' => $file_path);
			$result = $this->Org_model->update_org_info($org_id, $update_data);

			if ($result) {
				echo json_encode(array(
					'success' => true,
					'message' => '조직 아이콘이 업로드되었습니다.',
					'file_path' => $file_path
				));
			} else {
				echo json_encode(array('success' => false, 'message' => '아이콘 정보 저장에 실패했습니다.'));
			}
		} else {
			$error = $this->upload->display_errors('', '');
			echo json_encode(array('success' => false, 'message' => '파일 업로드 실패: ' . $error));
		}
	}

	/**
	 * 조직 관리자 위임
	 */
	public function delegate_admin() {
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$org_id = $this->input->post('org_id');
		$delegate_email = $this->input->post('delegate_email');

		if (!$org_id || !$delegate_email) {
			echo json_encode(array('success' => false, 'message' => '필수 정보가 누락되었습니다.'));
			return;
		}

		// 권한 검증 - 현재 최고관리자만 위임 가능
		$user_id = $this->session->userdata('user_id');
		$user_level = $this->User_model->get_org_user_level($user_id, $org_id);

		if ($user_level != 10 && $this->session->userdata('master_yn') !== 'Y') {
			echo json_encode(array('success' => false, 'message' => '최고관리자만 위임할 수 있습니다.'));
			return;
		}

		// 위임받을 사용자가 존재하는지 확인
		$delegate_user = $this->User_model->get_user_by_email($delegate_email);
		if (!$delegate_user) {
			echo json_encode(array('success' => false, 'message' => '해당 이메일의 사용자를 찾을 수 없습니다.'));
			return;
		}

		// 해당 사용자가 이미 조직 멤버인지 확인
		$existing_org_user = $this->Org_model->get_org_user($delegate_user['user_id'], $org_id);
		if (!$existing_org_user) {
			echo json_encode(array('success' => false, 'message' => '해당 사용자는 이 조직의 멤버가 아닙니다.'));
			return;
		}

		// 위임 처리
		$result = $this->Org_model->delegate_admin($user_id, $delegate_user['user_id'], $org_id);

		if ($result) {
			// 위임 알림 메일 발송
			$this->send_delegation_email($delegate_user, $org_id);

			echo json_encode(array('success' => true, 'message' => '관리자 권한이 위임되었습니다.'));
		} else {
			echo json_encode(array('success' => false, 'message' => '관리자 위임에 실패했습니다.'));
		}
	}

	/**
	 * 위임 알림 메일 발송
	 */
	private function send_delegation_email($delegate_user, $org_id) {
		$org_info = $this->Org_model->get_org_detail_by_id($org_id);

		$config['protocol'] = 'smtp';
		$config['smtp_host'] = 'your-smtp-host';
		$config['smtp_port'] = 587;
		$config['smtp_user'] = 'your-email@domain.com';
		$config['smtp_pass'] = 'your-password';
		$config['charset'] = 'utf-8';
		$config['mailtype'] = 'html';
		$config['newline'] = "\r\n";

		$this->email->initialize($config);

		$this->email->from('no-reply@wani.im', '와니 시스템');
		$this->email->to($delegate_user['user_mail']);
		$this->email->subject('[와니] 조직 관리자 권한 위임 알림');

		$message = "
        <h3>조직 관리자 권한 위임 알림</h3>
        <p>안녕하세요, {$delegate_user['user_name']}님</p>
        <p><strong>{$org_info['org_name']}</strong> 조직의 최고관리자 권한이 귀하에게 위임되었습니다.</p>
        <p>이제 해당 조직의 모든 설정을 관리할 수 있습니다.</p>
        <p><a href='" . base_url('org') . "'>조직 설정 페이지로 이동</a></p>
        <br>
        <p>감사합니다.</p>
        <p>와니 시스템</p>
        ";

		$this->email->message($message);
		$this->email->send();
	}

	/**
	 * 조직 상세 정보 가져오기
	 */
	public function get_org_detail() {
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$org_id = $this->input->post('org_id');

		if (!$org_id) {
			echo json_encode(array('success' => false, 'message' => '조직 ID가 필요합니다.'));
			return;
		}

		$org_detail = $this->Org_model->get_org_detail_by_id($org_id);

		if ($org_detail) {
			echo json_encode(array('success' => true, 'data' => $org_detail));
		} else {
			echo json_encode(array('success' => false, 'message' => '조직 정보를 찾을 수 없습니다.'));
		}
	}
}
