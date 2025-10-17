<?php
/**
 * 파일 위치: application/controllers/Homepage_setting.php
 * 역할: 홈페이지 기본설정 관리 컨트롤러
 */
defined('BASEPATH') or exit('No direct script access allowed');

class Homepage_setting extends My_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model('Homepage_setting_model');
		$this->load->model('User_model');
		$this->load->library('session');
		$this->load->library('upload');
		$this->load->helper('url');
	}


	/**
	 * 홈페이지 설정 메인 페이지
	 */
	public function index()
	{
		$user_id = $this->session->userdata('user_id');
		if (!$user_id) {
			redirect('login');
			return;
		}

		$header_data = $this->prepare_header_data();
		if (empty($header_data['user_orgs'])) {
			redirect('dashboard');
			return;
		}

		$data = $header_data;
		$this->handle_org_change($data);

		$currentOrgId = $data['current_org']['org_id'];

		// 조직 상세 정보 조회 (org_code 포함)
		$this->load->model('Org_model');
		$data['selected_org_detail'] = $this->Org_model->get_org_detail_by_id($currentOrgId);

		// 홈페이지 설정 조회
		$data['homepage_setting'] = $this->Homepage_setting_model->get_homepage_setting($currentOrgId);

		$this->load->view('homepage_setting', $data);
	}



	/**
	 * 홈페이지 설정 저장
	 */
	public function save()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$user_id = $this->session->userdata('user_id');
		$org_id = $this->input->post('org_id');

		if (!$org_id) {
			echo json_encode(array('success' => false, 'message' => '조직 정보를 찾을 수 없습니다.'));
			return;
		}

		// 권한 검증
		$user_level = $this->User_model->get_org_user_level($user_id, $org_id);
		if ($user_level < 9 && $this->session->userdata('master_yn') !== 'Y') {
			echo json_encode(array('success' => false, 'message' => '홈페이지 설정을 수정할 권한이 없습니다.'));
			return;
		}

		$homepage_name = $this->input->post('homepage_name');
		$homepage_domain = $this->input->post('homepage_domain');
		$theme = $this->input->post('theme');
		$logo1 = $this->input->post('logo1_current');
		$logo2 = $this->input->post('logo2_current');

		// 조직 정보 조회
		$this->load->model('Org_model');
		$org_detail = $this->Org_model->get_org_detail_by_id($org_id);

		if (!$org_detail) {
			echo json_encode(array('success' => false, 'message' => '조직 정보를 찾을 수 없습니다.'));
			return;
		}

		$org_code = $org_detail['org_code'];
		$org_name = $org_detail['org_name'];

		$homepage_setting = array(
			'homepage_name' => $homepage_name,
			'homepage_domain' => $homepage_domain,
			'logo1' => $logo1,
			'logo2' => $logo2,
			'theme' => $theme
		);

		// DB 저장
		$result = $this->Homepage_setting_model->update_homepage_setting($org_id, $homepage_setting);

		if (!$result) {
			echo json_encode(array('success' => false, 'message' => '홈페이지 설정 저장에 실패했습니다.'));
			return;
		}

		// 라이브러리 로드
		$this->load->library('nginx_manager');

		$nginx_result = array('success' => true);
		$dir_result = array('success' => true);

		// 도메인이 있는 경우에만 Nginx 설정 파일 생성
		if (!empty($homepage_domain)) {
			$nginx_result = $this->nginx_manager->create_nginx_config($org_code, $homepage_domain, $org_name);

			if (!$nginx_result['success']) {
				log_message('error', 'Nginx 설정 생성 실패: ' . $nginx_result['message']);
			}
		}

		// 홈페이지 디렉토리 및 index.html은 항상 생성 (테마 변경 반영)
		$dir_result = $this->nginx_manager->create_homepage_directory($org_code, $org_name, $homepage_setting, $org_id);

		if (!$dir_result['success']) {
			log_message('error', '홈페이지 디렉토리 생성 실패: ' . $dir_result['message']);
		}

		$message = '홈페이지 설정이 저장되었습니다.';
		if ($nginx_result['success'] && $dir_result['success']) {
			if (!empty($homepage_domain)) {
				$message .= ' Nginx 설정 및 홈페이지가 생성되었습니다.';
			} else {
				$message .= ' 홈페이지가 업데이트되었습니다.';
			}
		}

		echo json_encode(array(
			'success' => true,
			'message' => $message,
			'nginx_result' => $nginx_result,
			'directory_result' => $dir_result
		));
	}

	/**
	 * 로고 업로드
	 */
	public function upload_logo()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$org_id = $this->input->post('org_id');
		$logo_type = $this->input->post('logo_type');

		if (!$org_id || !in_array($logo_type, array('logo1', 'logo2'))) {
			echo json_encode(array('success' => false, 'message' => '잘못된 요청입니다.'));
			return;
		}

		// 권한 검증
		$user_id = $this->session->userdata('user_id');
		$user_level = $this->User_model->get_org_user_level($user_id, $org_id);

		if ($user_level < 9 && $this->session->userdata('master_yn') !== 'Y') {
			echo json_encode(array('success' => false, 'message' => '로고를 업로드할 권한이 없습니다.'));
			return;
		}

		// 업로드 디렉토리 설정
		$upload_path = './uploads/homepage_logos/';
		if (!is_dir($upload_path)) {
			mkdir($upload_path, 0755, true);
		}

		// 업로드 설정
		$config['upload_path'] = $upload_path;
		$config['allowed_types'] = 'jpg|jpeg|png|gif';
		$config['max_size'] = 2048;
		$config['file_name'] = 'logo_' . $org_id . '_' . $logo_type . '_' . time();
		$config['overwrite'] = TRUE;

		$this->upload->initialize($config);

		if ($this->upload->do_upload('logo_file')) {
			$upload_data = $this->upload->data();
			$file_path = '/uploads/homepage_logos/' . $upload_data['file_name'];

			// 기존 로고 파일 삭제
			$current_setting = $this->Homepage_setting_model->get_homepage_setting($org_id);
			if ($current_setting && !empty($current_setting[$logo_type]) && file_exists('.' . $current_setting[$logo_type])) {
				unlink('.' . $current_setting[$logo_type]);
			}

			echo json_encode(array(
				'success' => true,
				'message' => '로고가 업로드되었습니다.',
				'logo_url' => $file_path
			));
		} else {
			$error = $this->upload->display_errors('', '');
			echo json_encode(array('success' => false, 'message' => '파일 업로드 실패: ' . $error));
		}
	}
}
