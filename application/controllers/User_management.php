<?php
/**
 * 파일 위치: application/controllers/User_management.php
 * 역할: 사용자 관리 컨트롤러 - 조직 내 사용자 목록 조회 및 관리
 */

defined('BASEPATH') or exit('No direct script access allowed');

class User_management extends My_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->library('session');
		$this->load->helper('url');
		$this->load->model('User_management_model');

		// 로그인 확인
		if (!$this->session->userdata('user_id')) {
			redirect('login');
		}
	}

	/**
	 * 사용자 관리 메인 화면
	 */
	public function index()
	{
		$user_id = $this->session->userdata('user_id');

		// 헤더 데이터 준비
		$header_data = $this->prepare_header_data();
		if (empty($header_data['user_orgs'])) {
			redirect('dashboard');
			return;
		}

		$data = $header_data;

		// org_id 가져오기
		$org_id = $this->input->get('org_id');
		if (!$org_id) {
			$org_id = $this->input->cookie('activeOrg');
		}
		if (!$org_id) {
			$org_id = $this->session->userdata('current_org_id');
		}

		// 현재 조직이 없으면 헤더 데이터의 current_org 사용
		if (!$org_id && isset($data['current_org']['org_id'])) {
			$org_id = $data['current_org']['org_id'];
		}

		// POST로 조직 변경 요청 처리 (My_Controller의 메소드 사용)
		if ($this->input->post('org_id')) {
			$this->handle_org_change($data);
			$org_id = $data['current_org']['org_id'];
		}

		// 여전히 조직이 없으면 첫 번째 조직 선택
		if (!$org_id) {
			$master_yn = $this->session->userdata('master_yn');
			if ($master_yn === "N") {
				$user_orgs = $this->User_management_model->get_user_orgs($user_id);
			} else {
				$user_orgs = $this->User_management_model->get_user_orgs_master($user_id);
			}

			if (!empty($user_orgs)) {
				$org_id = $user_orgs[0]['org_id'];
				$this->input->set_cookie('activeOrg', $org_id, 86400);
				// 헤더 데이터 업데이트
				$data['current_org'] = $user_orgs[0];
			} else {
				show_error('접근 가능한 조직이 없습니다.', 403);
				return;
			}
		}

		// 권한 검증 - 사용자 관리는 최소 레벨 9 이상 필요
		$user_level = $this->User_management_model->get_org_user_level($user_id, $org_id);
		if ($user_level < 9 && $this->session->userdata('master_yn') !== 'Y') {
			show_error('사용자를 관리할 권한이 없습니다.', 403);
			return;
		}

		$master_yn = $this->session->userdata('master_yn');

		// 사용자가 접근 가능한 모든 조직 목록 (권한 레벨 포함)
		if ($master_yn === "N") {
			$orgs = $this->User_management_model->get_user_orgs($user_id);
		} else {
			$orgs = $this->User_management_model->get_user_orgs_master($user_id);
		}

		foreach ($orgs as &$org) {
			$org['user_level'] = $this->User_management_model->get_org_user_level($user_id, $org['org_id']);
			$org['user_count'] = $this->User_management_model->get_org_user_count($org['org_id']);
		}

		$data['orgs'] = $orgs;
		$data['selected_org_detail'] = $this->User_management_model->get_org_detail_by_id($org_id);
		$data['org_users'] = $this->User_management_model->get_org_users($org_id);

		$this->load->view('user_management', $data);
	}



    /**
     * 관리 메뉴 목록 조회 API
     */
    public function get_managed_menus() {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }

        // 메뉴 헬퍼 로드
        $this->load->helper('menu');
        $system_menus = get_system_menus();

        $menus = array();
        if ($system_menus && is_array($system_menus)) {
            foreach ($system_menus as $menu_key => $menu_info) {
                $menus[] = array(
                    'key' => $menu_key,
                    'name' => $menu_info['name']
                );
            }
        }

        echo json_encode(array('success' => true, 'menus' => $menus));
    }

    /**
     * 관리 그룹 목록 조회 API
     */
    public function get_managed_areas() {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }

        // POST와 GET 모두 처리
        $org_id = $this->input->post('org_id');
        if (!$org_id) {
            $org_id = $this->input->get('org_id');
        }

        // 쿠키에서 현재 조직 정보 가져오기
        if (!$org_id) {
            $org_id = $this->input->cookie('activeOrg');
        }

        if (!$org_id) {
            echo json_encode(array('success' => false, 'message' => '조직 정보가 필요합니다.'));
            return;
        }

        // 조직의 그룹 목록 조회
        $areas = $this->User_management_model->get_org_areas($org_id);

        echo json_encode(array('success' => true, 'areas' => $areas));
    }

    /**
     * 사용자 관리 정보 조회 API
     */
    public function get_user_management_info() {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }

        $user_id = $this->input->post('user_id');
        if (!$user_id) {
            echo json_encode(array('success' => false, 'message' => '사용자 ID가 필요합니다.'));
            return;
        }

        $managed_menus = $this->User_management_model->get_user_managed_menus($user_id);
        $managed_areas = $this->User_management_model->get_user_managed_areas($user_id);

        echo json_encode(array(
            'success' => true,
            'managed_menus' => $managed_menus,
            'managed_areas' => $managed_areas
        ));
    }

    /**
     * 사용자 정보 수정 (관리 메뉴 및 그룹 포함)
     */
	public function update_user() {
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$user_id = $this->session->userdata('user_id');
		$target_user_id = $this->input->post('target_user_id');
		$org_id = $this->input->post('org_id');

		// 권한 검증
		$user_level = $this->User_management_model->get_org_user_level($user_id, $org_id);
		if ($user_level < 9 && $this->session->userdata('master_yn') !== 'Y') {
			echo json_encode(array('success' => false, 'message' => '사용자 정보를 수정할 권한이 없습니다.'));
			return;
		}

		$user_name = $this->input->post('user_name');
		$user_mail = $this->input->post('user_mail');
		$user_hp = $this->input->post('user_hp');

		if (empty($target_user_id) || empty($user_name) || empty($user_mail) || empty($user_hp)) {
			echo json_encode(array('success' => false, 'message' => '필수 입력 항목이 누락되었습니다.'));
			return;
		}

		// 이메일 형식 검증
		if (!filter_var($user_mail, FILTER_VALIDATE_EMAIL)) {
			echo json_encode(array('success' => false, 'message' => '올바른 이메일 형식이 아닙니다.'));
			return;
		}

		// 사용자 정보 업데이트
		$result = $this->User_management_model->update_user_basic_info($target_user_id, $user_name, $user_mail, $user_hp);

		if ($result) {
			echo json_encode(array('success' => true, 'message' => '사용자 정보가 수정되었습니다.'));
		} else {
			echo json_encode(array('success' => false, 'message' => '사용자 정보 수정에 실패했습니다.'));
		}
	}

	/**
	 * 선택된 사용자들의 권한 일괄 수정
	 */
	public function bulk_update_users() {
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$user_id = $this->session->userdata('user_id');
		$org_id = $this->input->post('org_id');
		$user_ids_json = $this->input->post('user_ids');

		// 권한 검증
		$user_level = $this->User_management_model->get_org_user_level($user_id, $org_id);
		if ($user_level < 9 && $this->session->userdata('master_yn') !== 'Y') {
			echo json_encode(array('success' => false, 'message' => '권한을 수정할 권한이 없습니다.'));
			return;
		}

		// 대상 사용자 ID 배열 파싱
		$user_ids = json_decode($user_ids_json, true);
		if (!is_array($user_ids) || empty($user_ids)) {
			echo json_encode(array('success' => false, 'message' => '수정할 사용자를 선택해주세요.'));
			return;
		}

		// 입력값 검증 및 처리
		$level = $this->input->post('level');
		$managed_menus = $this->input->post('managed_menus');
		$managed_areas = $this->input->post('managed_areas');

		// 레벨 값이 있는 경우에만 검증
		if ($level !== '' && (!is_numeric($level) || $level < 0 || $level > 10)) {
			echo json_encode(array('success' => false, 'message' => '올바르지 않은 권한 레벨입니다.'));
			return;
		}

		// 관리 메뉴/그룹 JSON 변환
		$managed_menus_json = null;
		$managed_areas_json = null;

		if (!empty($managed_menus) && is_array($managed_menus)) {
			$managed_menus_json = json_encode($managed_menus);
		}

		if (!empty($managed_areas) && is_array($managed_areas)) {
			$managed_areas_json = json_encode($managed_areas);
		}

		// 일괄 업데이트 실행
		$success_count = 0;
		$total_count = count($user_ids);

		foreach ($user_ids as $target_user_id) {
			// 각 사용자별로 권한 업데이트
			$result = $this->User_management_model->bulk_update_user_permissions(
				$target_user_id,
				$org_id,
				$level !== '' ? $level : null,
				$managed_menus_json,
				$managed_areas_json
			);

			if ($result) {
				$success_count++;
			}
		}

		if ($success_count === $total_count) {
			echo json_encode(array('success' => true, 'message' => "{$success_count}명의 사용자 권한이 수정되었습니다."));
		} else {
			$failed_count = $total_count - $success_count;
			echo json_encode(array('success' => false, 'message' => "{$success_count}명 수정 완료, {$failed_count}명 실패했습니다."));
		}
	}


	/**
	 * 사용자 삭제 (조직에서 제외)
	 */

	public function delete_user()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		// Content-Type 헤더 설정
		header('Content-Type: application/json; charset=utf-8');

		$user_id = $this->session->userdata('user_id');
		$target_user_id = $this->input->post('target_user_id');
		$org_id = $this->input->post('org_id');

		log_message('debug', "=== DELETE USER REQUEST START ===");
		log_message('debug', "Requester: {$user_id}, Target: {$target_user_id}, Org: {$org_id}");

		// 입력값 검증
		if (empty($target_user_id) || empty($org_id)) {
			log_message('error', "Delete user failed: Missing required parameters");
			$response = array('success' => false, 'message' => '필수 정보가 누락되었습니다.');
			echo json_encode($response);
			return;
		}

		// 권한 검증
		$user_level = $this->User_management_model->get_org_user_level($user_id, $org_id);
		log_message('debug', "Requester level: {$user_level}");

		if ($user_level < 9 && $this->session->userdata('master_yn') !== 'Y') {
			log_message('error', "Delete user permission denied: User {$user_id} (level {$user_level}) trying to delete {$target_user_id} in org {$org_id}");
			$response = array('success' => false, 'message' => '사용자를 삭제할 권한이 없습니다.');
			echo json_encode($response);
			return;
		}

		// 자기 자신 삭제 방지
		if ($user_id === $target_user_id) {
			log_message('error', "Delete user failed: User trying to delete self");
			$response = array('success' => false, 'message' => '본인 계정은 삭제할 수 없습니다.');
			echo json_encode($response);
			return;
		}

		// 삭제 대상 사용자 존재 여부 확인
		$user_exists = $this->User_management_model->check_org_user_exists($target_user_id, $org_id);
		log_message('debug', "Target user exists: " . ($user_exists ? 'YES' : 'NO'));

		if (!$user_exists) {
			log_message('error', "Delete user failed: Target user {$target_user_id} not found in org {$org_id}");
			$response = array('success' => false, 'message' => '삭제할 사용자를 조직에서 찾을 수 없습니다.');
			echo json_encode($response);
			return;
		}

		// 삭제 대상 사용자 상세 정보 확인
		$target_user_info = $this->User_management_model->get_org_user_info($target_user_id, $org_id);
		if (!$target_user_info) {
			log_message('error', "Delete user failed: Unable to get target user info {$target_user_id} in org {$org_id}");
			$response = array('success' => false, 'message' => '사용자 정보를 확인할 수 없습니다.');
			echo json_encode($response);
			return;
		}

		// 마스터 사용자 삭제 방지
		if (isset($target_user_info['master_yn']) && $target_user_info['master_yn'] === 'Y') {
			log_message('error', "Delete user failed: Attempting to delete master user {$target_user_id}");
			$response = array('success' => false, 'message' => '마스터 사용자는 삭제할 수 없습니다.');
			echo json_encode($response);
			return;
		}

		log_message('debug', "All validations passed. Proceeding with deletion...");

		// 사용자 삭제 실행
		$result = $this->User_management_model->delete_org_user($target_user_id, $org_id);

		log_message('debug', "Delete operation result: " . ($result ? 'SUCCESS' : 'FAILED'));
		log_message('debug', "=== DELETE USER REQUEST END ===");

		if ($result) {
			log_message('info', "User deleted successfully: {$target_user_id} from org: {$org_id} by user: {$user_id}");
			$response = array('success' => true, 'message' => '사용자가 조직에서 제외되었습니다.');
		} else {
			log_message('error', "User deletion failed: {$target_user_id} from org: {$org_id} by user: {$user_id}");
			$response = array('success' => false, 'message' => '사용자 삭제에 실패했습니다. 다시 시도해주세요.');
		}

		echo json_encode($response);
	}

	/**
	 * 사용자 초대 메일 발송
	 */
	public function invite_user()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$user_id = $this->session->userdata('user_id');
		$invite_email = $this->input->post('invite_email');
		$org_id = $this->input->post('org_id');

		// 권한 검증
		$user_level = $this->User_management_model->get_org_user_level($user_id, $org_id);
		if ($user_level < 9 && $this->session->userdata('master_yn') !== 'Y') {
			echo json_encode(array('success' => false, 'message' => '사용자를 초대할 권한이 없습니다.'));
			return;
		}

		if (empty($invite_email)) {
			echo json_encode(array('success' => false, 'message' => '초대할 이메일 주소를 입력해주세요.'));
			return;
		}

		// 이메일 형식 검증
		if (!filter_var($invite_email, FILTER_VALIDATE_EMAIL)) {
			echo json_encode(array('success' => false, 'message' => '올바른 이메일 주소를 입력해주세요.'));
			return;
		}

		// 이미 해당 조직에 속한 사용자인지 확인 (level > 0)
		$existing_org_user = $this->User_management_model->get_org_user($invite_email, $org_id);
		if ($existing_org_user && $existing_org_user['level'] > 0) {
			echo json_encode(array('success' => false, 'message' => '이미 해당 조직에 가입된 사용자입니다.'));
			return;
		}

		// 이미 초대된 사용자인지 확인 (level = 0)
		if ($existing_org_user && $existing_org_user['level'] == 0) {
			echo json_encode(array('success' => false, 'message' => '이미 초대된 사용자입니다. 로그인을 기다려주세요.'));
			return;
		}

		// 데이터베이스 트랜잭션 시작
		$this->db->trans_start();

		try {
			// 1. 사용자 생성 (없는 경우)
			$invited_user_id = $this->User_management_model->create_invited_user($invite_email);
			if (!$invited_user_id) {
				throw new Exception('사용자 생성에 실패했습니다.');
			}

			// 2. 조직에 사용자 추가 (level=0)
			$org_added = $this->User_management_model->add_invited_user_to_org($invited_user_id, $org_id);
			if (!$org_added) {
				throw new Exception('조직에 사용자 추가에 실패했습니다.');
			}

			// 3. 초대 토큰 생성
			$invite_token = bin2hex(random_bytes(32));

			// 4. 초대 메일 발송
			$mail_result = $this->send_invite_email($invite_email, $org_id, $user_id, $invite_token);

			if (!$mail_result['success']) {
				throw new Exception($mail_result['message']);
			}

			// 5. 발송 로그 저장
			$invite_data = array(
				'invite_email' => $invite_email,
				'org_id' => $org_id,
				'inviter_id' => $user_id,
				'invite_token' => $invite_token,
				'status' => 'sent',
				'sent_date' => date('Y-m-d H:i:s')
			);

			$log_saved = $this->User_management_model->save_invite_log($invite_data);
			if (!$log_saved) {
				log_message('warning', '초대 메일 발송 로그 저장 실패: ' . $invite_email);
			}

			// 트랜잭션 커밋
			$this->db->trans_complete();

			if ($this->db->trans_status() === FALSE) {
				throw new Exception('데이터베이스 트랜잭션 실패');
			}

			echo json_encode(array('success' => true, 'message' => '초대 메일이 발송되고 사용자가 조직에 추가되었습니다.'));

		} catch (Exception $e) {
			// 트랜잭션 롤백
			$this->db->trans_rollback();

			log_message('error', '사용자 초대 처리 중 오류: ' . $e->getMessage());
			echo json_encode(array('success' => false, 'message' => $e->getMessage()));
		}
	}


	/**
	 * 실제 메일 발송 처리
	 */
	private function send_invite_email($invite_email, $org_id, $inviter_id, $invite_token)
	{
		try {
			// 조직 정보 가져오기
			$org_info = $this->User_management_model->get_org_detail_by_id($org_id);
			if (!$org_info) {
				log_message('error', '조직 정보를 찾을 수 없음: ' . $org_id);
				return array('success' => false, 'message' => '조직 정보를 찾을 수 없습니다.');
			}

			// 초대자 정보 가져오기
			$inviter_info = $this->User_management_model->get_user_info($inviter_id);
			if (!$inviter_info) {
				log_message('error', '초대자 정보를 찾을 수 없음: ' . $inviter_id);
				return array('success' => false, 'message' => '초대자 정보를 찾을 수 없습니다.');
			}

			// 이메일 라이브러리 로드
			$this->load->library('email');

			// SMTP 설정
			$config['protocol'] = 'smtp';
			$config['smtp_host'] = 'smtp.gmail.com';
			$config['smtp_user'] = 'jogiclub@gmail.com'; // 실제 Gmail 주소로 변경
			$config['smtp_pass'] = 'zdlt driw epud yoym'; // Gmail 앱 비밀번호로 변경
			$config['smtp_port'] = 587;
			$config['smtp_crypto'] = 'tls';
			$config['charset'] = 'utf-8';
			$config['wordwrap'] = TRUE;
			$config['mailtype'] = 'html';
			$config['newline'] = "\r\n";
			$config['crlf'] = "\r\n";

			$this->email->initialize($config);

			// 초대 링크 생성
			$invite_url = base_url('login/invite/' . $invite_token);

			$this->email->from('your-email@gmail.com', '왔니'); // 실제 이메일로 변경
			$this->email->to($invite_email);
			$this->email->subject($org_info['org_name'] . ' 조직 초대');

			$message = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                <h3 style='color: #333;'>" . $org_info['org_name'] . "에 초대되었습니다</h3>
                <p style='font-size: 16px; color: #555;'>
                    " . $inviter_info['user_name'] . "님이 회원님을 " . $org_info['org_name'] . " 조직에 초대하였습니다.
                </p>
                <div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                    <p style='margin: 0; font-size: 14px; color: #666;'>초대코드</p>
                    <p style='margin: 5px 0 0 0; font-size: 18px; font-weight: bold; color: #007bff;'>" . $org_info['invite_code'] . "</p>
                </div>
                <p style='text-align: center; margin: 30px 0;'>
                    <a href='" . $invite_url . "' style='background: #007bff; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block;'>
                        초대 수락하기
                    </a>
                </p>
                <p style='font-size: 14px; color: #666; text-align: center;'>
                    또는 다음 링크를 복사하여 브라우저에 붙여넣으세요:<br>
                    <span style='font-size: 12px; color: #999;'>" . $invite_url . "</span>
                </p>
                <hr style='border: none; border-top: 1px solid #eee; margin: 30px 0;'>
                <p style='font-size: 12px; color: #999; text-align: center;'>
                    이 메일은 왔니 시스템에서 자동으로 발송된 메일입니다.
                </p>
            </div>
        ";

			$this->email->message($message);

			// 메일 발송 시도
			$result = $this->email->send();

			if (!$result) {
				$error = $this->email->print_debugger();
				log_message('error', '메일 발송 실패: ' . $invite_email . ' - ' . $error);
				return array('success' => false, 'message' => '메일 발송에 실패했습니다. 관리자에게 문의해주세요.');
			}

			log_message('info', '초대 메일 발송 성공: ' . $invite_email . ' (조직: ' . $org_id . ')');
			return array('success' => true, 'message' => '초대 메일이 성공적으로 발송되었습니다.');

		} catch (Exception $e) {
			log_message('error', '메일 발송 중 예외 발생: ' . $e->getMessage());
			return array('success' => false, 'message' => '메일 발송 중 오류가 발생했습니다.');
		}
	}

	/**
	 * 관리 그룹 트리 구조 목록 조회 API
	 */
	public function get_managed_areas_tree() {
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		// POST와 GET 모두 처리
		$org_id = $this->input->post('org_id');
		if (!$org_id) {
			$org_id = $this->input->get('org_id');
		}

		// 쿠키에서 현재 조직 정보 가져오기
		if (!$org_id) {
			$org_id = $this->input->cookie('activeOrg');
		}

		if (!$org_id) {
			echo json_encode(array('success' => false, 'message' => '조직 정보가 필요합니다.'));
			return;
		}

		// Member_area_model 로드 (트리 구조 조회를 위해)
		$this->load->model('Member_area_model');

		// 트리 구조로 그룹 목록 조회
		$areas_tree = $this->Member_area_model->get_member_areas_tree($org_id);

		echo json_encode(array('success' => true, 'areas' => $areas_tree));
	}

	/**
	 * 사용자로 로그인 (마스터 전용)
	 */
	public function login_as_user()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		// Content-Type 헤더 설정
		header('Content-Type: application/json; charset=utf-8');

		// 마스터 권한 확인
		if ($this->session->userdata('master_yn') !== 'Y') {
			$response = array('success' => false, 'message' => '권한이 없습니다.');
			echo json_encode($response);
			return;
		}

		$current_user_id = $this->session->userdata('user_id');
		$target_user_id = $this->input->post('target_user_id');

		// 입력값 검증
		if (empty($target_user_id)) {
			$response = array('success' => false, 'message' => '사용자 정보가 누락되었습니다.');
			echo json_encode($response);
			return;
		}

		// 자기 자신으로 로그인 방지
		if ($current_user_id === $target_user_id) {
			$response = array('success' => false, 'message' => '본인 계정으로는 로그인할 수 없습니다.');
			echo json_encode($response);
			return;
		}

		// 대상 사용자 정보 확인
		$target_user = $this->User_management_model->get_user_info($target_user_id);
		if (!$target_user) {
			$response = array('success' => false, 'message' => '사용자 정보를 찾을 수 없습니다.');
			echo json_encode($response);
			return;
		}

		// 로그 기록
		log_message('info', "Admin login as user: {$current_user_id} -> {$target_user_id}");

		// 세션 데이터 업데이트 (원래 관리자 정보는 별도로 저장)
		$session_data = array(
			'user_id' => $target_user['user_id'],
			'user_name' => $target_user['user_name'],
			'user_mail' => $target_user['user_mail'],
			'user_hp' => $target_user['user_hp'],
			'master_yn' => 'N', // 로그인한 사용자는 손님로 설정
			'original_admin_id' => $current_user_id, // 원래 관리자 ID 저장
			'is_admin_login' => true // 관리자 로그인 플래그
		);

		$this->session->set_userdata($session_data);

		$response = array(
			'success' => true,
			'message' => $target_user['user_name'] . '님으로 로그인되었습니다.'
		);
		echo json_encode($response);
	}

	/**
	 * 관리자 계정으로 돌아가기
	 */
	public function return_to_admin()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		// Content-Type 헤더 설정
		header('Content-Type: application/json; charset=utf-8');

		$original_admin_id = $this->session->userdata('original_admin_id');
		$is_admin_login = $this->session->userdata('is_admin_login');

		if (!$original_admin_id || !$is_admin_login) {
			$response = array('success' => false, 'message' => '관리자 로그인 상태가 아닙니다.');
			echo json_encode($response);
			return;
		}

		// 원래 관리자 정보 복원
		$admin_user = $this->User_management_model->get_user_info($original_admin_id);
		if (!$admin_user) {
			$response = array('success' => false, 'message' => '관리자 정보를 찾을 수 없습니다.');
			echo json_encode($response);
			return;
		}

		// 세션 데이터 복원
		$session_data = array(
			'user_id' => $admin_user['user_id'],
			'user_name' => $admin_user['user_name'],
			'user_mail' => $admin_user['user_mail'],
			'user_hp' => $admin_user['user_hp'],
			'master_yn' => 'Y',
			'original_admin_id' => null,
			'is_admin_login' => false
		);

		$this->session->set_userdata($session_data);

		$response = array('success' => true, 'message' => '관리자 계정으로 돌아왔습니다.');
		echo json_encode($response);
	}




	/**
	 * 초대상태 사용자 승인
	 */
	public function approve_invited_user()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$target_user_id = $this->input->post('target_user_id');
		$org_id = $this->input->post('org_id');

		if (!$target_user_id || !$org_id) {
			echo json_encode(array('success' => false, 'message' => '필수 정보가 누락되었습니다.'));
			return;
		}

		$user_id = $this->session->userdata('user_id');
		$user_level = $this->User_management_model->get_org_user_level($user_id, $org_id);

		// 권한 검증
		if ($user_level < 9 && $this->session->userdata('master_yn') !== 'Y') {
			echo json_encode(array('success' => false, 'message' => '초대상태 사용자를 승인할 권한이 없습니다.'));
			return;
		}

		$this->load->model('Member_model');
		$result = $this->Member_model->approve_invited_user($target_user_id, $org_id);

		if ($result) {
			echo json_encode(array('success' => true, 'message' => '사용자가 승인되었습니다.'));
		} else {
			echo json_encode(array('success' => false, 'message' => '사용자 승인에 실패했습니다.'));
		}
	}

	/**
	 * 초대상태 사용자 거절 (삭제)
	 */
	public function reject_invited_user()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$target_user_id = $this->input->post('target_user_id');
		$org_id = $this->input->post('org_id');

		if (!$target_user_id || !$org_id) {
			echo json_encode(array('success' => false, 'message' => '필수 정보가 누락되었습니다.'));
			return;
		}

		$user_id = $this->session->userdata('user_id');
		$user_level = $this->User_management_model->get_org_user_level($user_id, $org_id);

		// 권한 검증
		if ($user_level < 9 && $this->session->userdata('master_yn') !== 'Y') {
			echo json_encode(array('success' => false, 'message' => '초대상태 사용자를 거절할 권한이 없습니다.'));
			return;
		}

		$this->load->model('Member_model');
		$result = $this->Member_model->reject_invited_user($target_user_id, $org_id);

		if ($result) {
			echo json_encode(array('success' => true, 'message' => '초대가 거절되었습니다.'));
		} else {
			echo json_encode(array('success' => false, 'message' => '초대 거절에 실패했습니다.'));
		}
	}



	/**
	 * 파일 위치: application/controllers/User_management.php
	 * 역할: 다중 사용자 초대 메일 발송 처리
	 */

	/**
	 * 다중 사용자 초대 메일 발송
	 */
	public function invite_users()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$user_id = $this->session->userdata('user_id');
		$invite_emails_raw = $this->input->post('invite_emails');
		$org_id = $this->input->post('org_id');

		// 권한 검증
		$user_level = $this->User_management_model->get_org_user_level($user_id, $org_id);
		if ($user_level < 9 && $this->session->userdata('master_yn') !== 'Y') {
			echo json_encode(array('success' => false, 'message' => '사용자를 초대할 권한이 없습니다.'));
			return;
		}

		if (empty($invite_emails_raw)) {
			echo json_encode(array('success' => false, 'message' => '초대할 이메일 주소를 입력해주세요.'));
			return;
		}

		// 이메일 주소 파싱 (줄바꿈, 쉼표, 세미콜론으로 구분)
		$invite_emails = $this->parse_email_addresses($invite_emails_raw);

		if (empty($invite_emails)) {
			echo json_encode(array('success' => false, 'message' => '유효한 이메일 주소를 입력해주세요.'));
			return;
		}

		// 이메일 개수 제한 (한 번에 최대 20개)
		if (count($invite_emails) > 20) {
			echo json_encode(array('success' => false, 'message' => '한 번에 최대 20명까지 초대할 수 있습니다.'));
			return;
		}

		$results = array(
			'success_count' => 0,
			'failed_count' => 0,
			'failed_emails' => array(),
			'success_emails' => array(),
			'duplicate_emails' => array(),
			'invalid_emails' => array()
		);

		// 각 이메일별로 초대 처리
		foreach ($invite_emails as $email) {
			$email = trim($email);

			// 이메일 형식 검증
			if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
				$results['failed_count']++;
				$results['invalid_emails'][] = $email;
				continue;
			}

			// 중복 확인
			$existing_org_user = $this->User_management_model->get_org_user($email, $org_id);
			if ($existing_org_user && $existing_org_user['level'] > 0) {
				$results['failed_count']++;
				$results['duplicate_emails'][] = $email;
				continue;
			}

			// 이미 초대된 사용자 확인
			if ($existing_org_user && $existing_org_user['level'] == 0) {
				$results['failed_count']++;
				$results['duplicate_emails'][] = $email;
				continue;
			}

			// 개별 초대 처리
			$invite_result = $this->process_single_invite($email, $org_id, $user_id);

			if ($invite_result['success']) {
				$results['success_count']++;
				$results['success_emails'][] = $email;
			} else {
				$results['failed_count']++;
				$results['failed_emails'][] = array(
					'email' => $email,
					'message' => $invite_result['message']
				);
			}
		}

		// 결과 메시지 생성
		$message = $this->generate_invite_result_message($results);

		echo json_encode(array(
			'success' => $results['success_count'] > 0,
			'message' => $message,
			'results' => $results
		));
	}

	/**
	 * 이메일 주소 파싱 (줄바꿈, 쉼표, 세미콜론으로 구분)
	 */
	private function parse_email_addresses($raw_emails)
	{
		// 줄바꿈, 쉼표, 세미콜론을 기준으로 분할
		$emails = preg_split('/[\r\n,;]+/', $raw_emails);

		$parsed_emails = array();
		foreach ($emails as $email) {
			$email = trim($email);
			if (!empty($email)) {
				$parsed_emails[] = $email;
			}
		}

		// 중복 제거
		return array_unique($parsed_emails);
	}

	/**
	 * 개별 초대 처리
	 */
	private function process_single_invite($email, $org_id, $inviter_id)
	{
		try {
			// 데이터베이스 트랜잭션 시작
			$this->db->trans_start();

			// 1. 사용자 생성 (없는 경우)
			$invited_user_id = $this->User_management_model->create_invited_user($email);
			if (!$invited_user_id) {
				throw new Exception('사용자 생성에 실패했습니다.');
			}

			// 2. 조직에 사용자 추가 (level=0)
			$org_added = $this->User_management_model->add_invited_user_to_org($invited_user_id, $org_id);
			if (!$org_added) {
				throw new Exception('조직에 사용자 추가에 실패했습니다.');
			}

			// 3. 초대 토큰 생성
			$invite_token = bin2hex(random_bytes(32));

			// 4. 초대 메일 발송
			$mail_result = $this->send_invite_email($email, $org_id, $inviter_id, $invite_token);

			if (!$mail_result['success']) {
				throw new Exception($mail_result['message']);
			}

			// 5. 발송 로그 저장
			$invite_data = array(
				'invite_email' => $email,
				'org_id' => $org_id,
				'inviter_id' => $inviter_id,
				'invite_token' => $invite_token,
				'status' => 'sent',
				'sent_date' => date('Y-m-d H:i:s')
			);

			$log_saved = $this->User_management_model->save_invite_log($invite_data);

			// 트랜잭션 커밋
			$this->db->trans_complete();

			if ($this->db->trans_status() === FALSE) {
				throw new Exception('데이터베이스 트랜잭션 실패');
			}

			return array('success' => true, 'message' => '초대 성공');

		} catch (Exception $e) {
			// 트랜잭션 롤백
			$this->db->trans_rollback();

			log_message('error', "개별 초대 실패 ({$email}): " . $e->getMessage());
			return array('success' => false, 'message' => $e->getMessage());
		}
	}

	/**
	 * 초대 결과 메시지 생성
	 */
	private function generate_invite_result_message($results)
	{
		$messages = array();

		if ($results['success_count'] > 0) {
			$messages[] = "{$results['success_count']}명에게 초대 메일을 발송했습니다.";
		}

		if ($results['failed_count'] > 0) {
			$messages[] = "{$results['failed_count']}명 초대에 실패했습니다.";
		}

		if (!empty($results['duplicate_emails'])) {
			$messages[] = "이미 가입되었거나 초대된 사용자: " . implode(', ', array_slice($results['duplicate_emails'], 0, 3)) .
				(count($results['duplicate_emails']) > 3 ? " 외 " . (count($results['duplicate_emails']) - 3) . "명" : "");
		}

		if (!empty($results['invalid_emails'])) {
			$messages[] = "잘못된 이메일 형식: " . implode(', ', array_slice($results['invalid_emails'], 0, 3)) .
				(count($results['invalid_emails']) > 3 ? " 외 " . (count($results['invalid_emails']) - 3) . "명" : "");
		}

		return implode("\n", $messages);
	}

	/**
	 * 조직의 사용자 목록 API (AJAX용 - 실시간 갱신용)
	 */
	public function get_org_users_ajax()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$org_id = $this->input->post('org_id');

		if (!$org_id) {
			echo json_encode(array('success' => false, 'message' => '조직 정보가 필요합니다.'));
			return;
		}

		$user_id = $this->session->userdata('user_id');

		// 권한 검증
		$user_level = $this->User_management_model->get_org_user_level($user_id, $org_id);
		if ($user_level < 9 && $this->session->userdata('master_yn') !== 'Y') {
			echo json_encode(array('success' => false, 'message' => '권한이 없습니다.'));
			return;
		}

		// 사용자 목록 조회
		$org_users = $this->User_management_model->get_org_users($org_id);

		echo json_encode(array(
			'success' => true,
			'users' => $org_users,
			'total_count' => count($org_users)
		));
	}


}
