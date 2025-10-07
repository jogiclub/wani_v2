<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Batch extends CI_Controller
{
	public function __construct()
	{
		parent::__construct();

		// CLI 모드가 아니면 실행 중단
		if (!$this->input->is_cli_request()) {
			echo "CLI 모드에서만 실행 가능합니다.\n";
			exit(1);
		}

		$this->load->model('Message_model');
		$this->load->model('Org_model');
	}

	/**
	 * 삭제된 메시지 정리 배치
	 * 실행: php index.php cli/batch clean_messages
	 */
	public function clean_messages()
	{
		echo "[" . date('Y-m-d H:i:s') . "] 메시지 정리 배치 시작\n";

		try {
			// 모든 조직 목록 가져오기
			$orgs = $this->Org_model->get_all_orgs();

			if (empty($orgs)) {
				echo "처리할 조직이 없습니다.\n";
				return;
			}

			$total_count = 0;
			$success_count = 0;

			foreach ($orgs as $org) {
				$org_id = $org['org_id'];
				$total_count++;

				echo "조직 {$org['org_name']}({$org_id}) 메시지 정리 중...\n";

				$result = $this->Message_model->clean_deleted_messages($org_id);

				if ($result) {
					$success_count++;
					echo "성공\n";
				} else {
					echo "실패\n";
					log_message('error', "조직 {$org_id} 메시지 정리 실패");
				}
			}

			echo "\n총 {$total_count}개 조직 중 {$success_count}개 조직 메시지 정리 완료\n";
			echo "[" . date('Y-m-d H:i:s') . "] 메시지 정리 배치 종료\n";

		} catch (Exception $e) {
			echo "오류 발생: " . $e->getMessage() . "\n";
			log_message('error', '메시지 정리 배치 오류: ' . $e->getMessage());
			exit(1);
		}
	}

	/**
	 * 테스트용 메서드
	 * 실행: php index.php cli/batch test
	 */
	public function test()
	{
		echo "배치 작업 테스트 실행\n";
		echo "현재 시간: " . date('Y-m-d H:i:s') . "\n";
		echo "환경: " . ENVIRONMENT . "\n";
	}

	/**
	 * 생일 알림 메시지 생성 배치
	 * 실행: php index.php cli/batch send_birthday_messages
	 */
	public function send_birthday_messages()
	{
		echo "[" . date('Y-m-d H:i:s') . "] 생일 알림 메시지 배치 시작\n";

		$this->load->model('Member_model');

		try {
			// 모든 조직 목록 가져오기 (auto_message 포함)
			$orgs = $this->Org_model->get_all_orgs();

			if (empty($orgs)) {
				echo "처리할 조직이 없습니다.\n";
				return;
			}

			$total_orgs = 0;
			$active_orgs = 0;
			$success_count = 0;
			$message_sent_count = 0;

			foreach ($orgs as $org) {
				$org_id = $org['org_id'];
				$org_name = $org['org_name'];
				$auto_message_json = $org['auto_message'];
				$total_orgs++;

				// auto_message가 null이거나 비어있으면 스킵
				if (empty($auto_message_json) || $auto_message_json === 'null') {
					echo "조직 {$org_name}({$org_id}): 자동 메시지 설정 없음 - 스킵\n";
					continue;
				}

				// auto_message JSON 파싱
				$auto_message = json_decode($auto_message_json, true);

				// JSON 파싱 실패 시 스킵
				if (!is_array($auto_message)) {
					echo "조직 {$org_name}({$org_id}): 자동 메시지 설정 파싱 실패 - 스킵\n";
					log_message('error', "조직 {$org_id} auto_message JSON 파싱 실패");
					continue;
				}

				// birth7 설정이 true가 아니면 스킵
				if (!isset($auto_message['birth7']) || $auto_message['birth7'] !== true) {
					echo "조직 {$org_name}({$org_id}): 7일 이내 생일 알림 비활성화 - 스킵\n";
					continue;
				}

				$active_orgs++;
				echo "조직 {$org_name}({$org_id}) 생일 회원 확인 중...\n";

				// 7일 이내 생일인 회원 조회
				$birthday_members = $this->Member_model->get_upcoming_birthday_members($org_id, 7);

				// 생일자가 없으면 메시지 생성하지 않음
				if (empty($birthday_members)) {
					echo "생일자 없음\n";
					continue;
				}

				// 생일자 정보 정리
				$member_count = count($birthday_members);
				$member_list = array();

				foreach ($birthday_members as $member) {
					$birth_date = $member['birth_md'];
					$formatted_date = date('n/j', strtotime('2000-' . $birth_date));
					$member_list[] = "{$member['member_name']}({$formatted_date})";
				}

				// 메시지 제목 및 내용 생성
				$message_title = "7일 이내 {$member_count}명의 생일 회원이 있습니다.";
				$message_content = "{$member_count}명의 회원에게 축하메시지를 보내주세요!\n" .
					implode(', ', $member_list) . "님";

				// 메시지 데이터 준비
				$message_data = array(
					'message_date' => date('Y-m-d H:i:s'),
					'message_type' => 'BIRTHDAY',
					'message_title' => $message_title,
					'message_content' => $message_content
				);

				// 조직의 모든 사용자에게 메시지 발송
				$result = $this->Message_model->send_message_to_org($org_id, $message_data);

				if ($result) {
					$success_count++;
					$message_sent_count += $member_count;
					echo "생일 알림 메시지 생성 완료 ({$member_count}명)\n";

					// 상세 로그
					log_message('info', "조직 {$org_id}에 생일 알림 메시지 생성: " . implode(', ', $member_list));
				} else {
					echo "메시지 생성 실패\n";
					log_message('error', "조직 {$org_id} 생일 알림 메시지 생성 실패");
				}
			}

			echo "\n=== 배치 실행 결과 ===\n";
			echo "총 조직 수: {$total_orgs}개\n";
			echo "자동 메시지 활성화 조직: {$active_orgs}개\n";
			echo "생일 알림 메시지 생성 완료: {$success_count}개 조직\n";
			echo "총 생일자 알림 발송: {$message_sent_count}명\n";
			echo "[" . date('Y-m-d H:i:s') . "] 생일 알림 메시지 배치 종료\n";

		} catch (Exception $e) {
			echo "오류 발생: " . $e->getMessage() . "\n";
			log_message('error', '생일 알림 메시지 배치 오류: ' . $e->getMessage());
			exit(1);
		}
	}

	/**
	 * 파일 위치: application/controllers/cli/Batch.php
	 * 역할: 오늘 생일인 회원 알림 배치
	 * 실행: php index.php cli/batch send_today_birthday_messages
	 */
	public function send_today_birthday_messages()
	{
		echo "[" . date('Y-m-d H:i:s') . "] 오늘 생일 알림 메시지 배치 시작\n";

		$this->load->model('Member_model');

		try {
			$orgs = $this->Org_model->get_all_orgs();

			if (empty($orgs)) {
				echo "처리할 조직이 없습니다.\n";
				return;
			}

			$total_orgs = 0;
			$active_orgs = 0;
			$success_count = 0;
			$message_sent_count = 0;

			foreach ($orgs as $org) {
				$org_id = $org['org_id'];
				$org_name = $org['org_name'];
				$auto_message_json = $org['auto_message'];
				$total_orgs++;

				if (empty($auto_message_json) || $auto_message_json === 'null') {
					echo "조직 {$org_name}({$org_id}): 자동 메시지 설정 없음 - 스킵\n";
					continue;
				}

				$auto_message = json_decode($auto_message_json, true);

				if (!is_array($auto_message)) {
					echo "조직 {$org_name}({$org_id}): 자동 메시지 설정 파싱 실패 - 스킵\n";
					continue;
				}

				if (!isset($auto_message['birthToday']) || $auto_message['birthToday'] !== true) {
					echo "조직 {$org_name}({$org_id}): 오늘 생일 알림 비활성화 - 스킵\n";
					continue;
				}

				$active_orgs++;
				echo "조직 {$org_name}({$org_id}) 오늘 생일 회원 확인 중...\n";

				$birthday_members = $this->Member_model->get_today_birthday_members($org_id);

				if (empty($birthday_members)) {
					echo "오늘 생일자 없음\n";
					continue;
				}

				$member_count = count($birthday_members);
				$member_list = array();

				foreach ($birthday_members as $member) {
					$member_list[] = $member['member_name'];
				}

				$message_title = "오늘 {$member_count}명의 생일입니다!";
				$message_content = "오늘은 " . implode(', ', $member_list) . "님의 생일입니다.\n축하 메시지를 보내주세요!";

				$message_data = array(
					'message_date' => date('Y-m-d H:i:s'),
					'message_type' => 'BIRTHDAY_TODAY',
					'message_title' => $message_title,
					'message_content' => $message_content
				);

				$result = $this->Message_model->send_message_to_org($org_id, $message_data);

				if ($result) {
					$success_count++;
					$message_sent_count += $member_count;
					echo "오늘 생일 알림 메시지 생성 완료 ({$member_count}명)\n";
					log_message('info', "조직 {$org_id}에 오늘 생일 알림 메시지 생성: " . implode(', ', $member_list));
				} else {
					echo "메시지 생성 실패\n";
					log_message('error', "조직 {$org_id} 오늘 생일 알림 메시지 생성 실패");
				}
			}

			echo "\n=== 배치 실행 결과 ===\n";
			echo "총 조직 수: {$total_orgs}개\n";
			echo "자동 메시지 활성화 조직: {$active_orgs}개\n";
			echo "생일 알림 메시지 생성 완료: {$success_count}개 조직\n";
			echo "총 생일자: {$message_sent_count}명\n";
			echo "[" . date('Y-m-d H:i:s') . "] 오늘 생일 알림 메시지 배치 종료\n";

		} catch (Exception $e) {
			echo "오류 발생: " . $e->getMessage() . "\n";
			log_message('error', '오늘 생일 알림 메시지 배치 오류: ' . $e->getMessage());
			exit(1);
		}
	}

	/**
	 * 파일 위치: application/controllers/cli/Batch.php
	 * 역할: 진급 대상자 알림 배치
	 * 실행: php index.php cli/batch send_promotion_messages
	 */
	public function send_promotion_messages()
	{
		echo "[" . date('Y-m-d H:i:s') . "] 진급 대상자 알림 메시지 배치 시작\n";

		$this->load->model('Timeline_model');

		try {
			$orgs = $this->Org_model->get_all_orgs();

			if (empty($orgs)) {
				echo "처리할 조직이 없습니다.\n";
				return;
			}

			$total_orgs = 0;
			$active_orgs = 0;
			$success_count = 0;
			$message_sent_count = 0;

			foreach ($orgs as $org) {
				$org_id = $org['org_id'];
				$org_name = $org['org_name'];
				$auto_message_json = $org['auto_message'];
				$total_orgs++;

				if (empty($auto_message_json) || $auto_message_json === 'null') {
					echo "조직 {$org_name}({$org_id}): 자동 메시지 설정 없음 - 스킵\n";
					continue;
				}

				$auto_message = json_decode($auto_message_json, true);

				if (!is_array($auto_message)) {
					echo "조직 {$org_name}({$org_id}): 자동 메시지 설정 파싱 실패 - 스킵\n";
					continue;
				}

				if (!isset($auto_message['promotion']) || $auto_message['promotion'] !== true) {
					echo "조직 {$org_name}({$org_id}): 진급 알림 비활성화 - 스킵\n";
					continue;
				}

				// 조직의 timeline_name 확인
				$org_detail = $this->Org_model->get_org_detail_by_id($org_id);
				if (!$org_detail || empty($org_detail['timeline_name'])) {
					echo "조직 {$org_name}({$org_id}): timeline_name 설정 없음 - 스킵\n";
					continue;
				}

				$timeline_names = json_decode($org_detail['timeline_name'], true);
				if (!is_array($timeline_names)) {
					echo "조직 {$org_name}({$org_id}): timeline_name 파싱 실패 - 스킵\n";
					continue;
				}

				// 진급 관련 타임라인 타입 추출
				$promotion_types = array();
				foreach ($timeline_names as $timeline_name) {
					if (strpos($timeline_name, '진급') !== false) {
						$promotion_types[] = $timeline_name;
					}
				}

				if (empty($promotion_types)) {
					echo "조직 {$org_name}({$org_id}): 진급 타입 없음 - 스킵\n";
					continue;
				}

				$active_orgs++;
				echo "조직 {$org_name}({$org_id}) 진급 대상자 확인 중...\n";

				$promotion_members = $this->Timeline_model->get_upcoming_promotion_members($org_id, $promotion_types, 7);

				if (empty($promotion_members)) {
					echo "진급 대상자 없음\n";
					continue;
				}

				$member_count = count($promotion_members);
				$member_list = array();

				foreach ($promotion_members as $member) {
					$formatted_date = date('n/j', strtotime($member['timeline_date']));
					$member_list[] = "{$member['member_name']}({$member['timeline_type']}, {$formatted_date})";
				}

				$message_title = "{$member_count}명의 진급 대상자가 있습니다.";
				$message_content = "7일 이내 진급 예정:\n" . implode(', ', $member_list) . "님";

				$message_data = array(
					'message_date' => date('Y-m-d H:i:s'),
					'message_type' => 'PROMOTION',
					'message_title' => $message_title,
					'message_content' => $message_content
				);

				$result = $this->Message_model->send_message_to_org($org_id, $message_data);

				if ($result) {
					$success_count++;
					$message_sent_count += $member_count;
					echo "진급 알림 메시지 생성 완료 ({$member_count}명)\n";
					log_message('info', "조직 {$org_id}에 진급 알림 메시지 생성: " . implode(', ', $member_list));
				} else {
					echo "메시지 생성 실패\n";
					log_message('error', "조직 {$org_id} 진급 알림 메시지 생성 실패");
				}
			}

			echo "\n=== 배치 실행 결과 ===\n";
			echo "총 조직 수: {$total_orgs}개\n";
			echo "자동 메시지 활성화 조직: {$active_orgs}개\n";
			echo "진급 알림 메시지 생성 완료: {$success_count}개 조직\n";
			echo "총 진급 대상자: {$message_sent_count}명\n";
			echo "[" . date('Y-m-d H:i:s') . "] 진급 대상자 알림 메시지 배치 종료\n";

		} catch (Exception $e) {
			echo "오류 발생: " . $e->getMessage() . "\n";
			log_message('error', '진급 대상자 알림 메시지 배치 오류: ' . $e->getMessage());
			exit(1);
		}
	}

	/**
	 * 파일 위치: application/controllers/cli/Batch.php
	 * 역할: 금주 미출석 회원 알림 배치
	 * 실행: php index.php cli/batch send_absence_1week_messages
	 */
	public function send_absence_1week_messages()
	{
		echo "[" . date('Y-m-d H:i:s') . "] 금주 미출석 회원 알림 메시지 배치 시작\n";

		$this->load->model('Member_model');

		try {
			$orgs = $this->Org_model->get_all_orgs();

			if (empty($orgs)) {
				echo "처리할 조직이 없습니다.\n";
				return;
			}

			$total_orgs = 0;
			$active_orgs = 0;
			$success_count = 0;
			$message_sent_count = 0;

			foreach ($orgs as $org) {
				$org_id = $org['org_id'];
				$org_name = $org['org_name'];
				$auto_message_json = $org['auto_message'];
				$total_orgs++;

				if (empty($auto_message_json) || $auto_message_json === 'null') {
					echo "조직 {$org_name}({$org_id}): 자동 메시지 설정 없음 - 스킵\n";
					continue;
				}

				$auto_message = json_decode($auto_message_json, true);

				if (!is_array($auto_message)) {
					echo "조직 {$org_name}({$org_id}): 자동 메시지 설정 파싱 실패 - 스킵\n";
					continue;
				}

				if (!isset($auto_message['absence1Week']) || $auto_message['absence1Week'] !== true) {
					echo "조직 {$org_name}({$org_id}): 금주 미출석 알림 비활성화 - 스킵\n";
					continue;
				}

				$active_orgs++;
				echo "조직 {$org_name}({$org_id}) 금주 미출석 회원 확인 중...\n";

				$absent_members = $this->Member_model->get_absent_members_this_week($org_id);

				if (empty($absent_members)) {
					echo "금주 미출석 회원 없음\n";
					continue;
				}

				$member_count = count($absent_members);
				$member_list = array();

				foreach ($absent_members as $member) {
					$member_list[] = $member['member_name'];
				}

				$message_title = "금주 미출석 회원 {$member_count}명";
				$message_content = "금주에 출석하지 않은 회원:\n" . implode(', ', $member_list) . "님";

				$message_data = array(
					'message_date' => date('Y-m-d H:i:s'),
					'message_type' => 'ABSENCE_1WEEK',
					'message_title' => $message_title,
					'message_content' => $message_content
				);

				$result = $this->Message_model->send_message_to_org($org_id, $message_data);

				if ($result) {
					$success_count++;
					$message_sent_count += $member_count;
					echo "금주 미출석 알림 메시지 생성 완료 ({$member_count}명)\n";
					log_message('info', "조직 {$org_id}에 금주 미출석 알림 메시지 생성: " . implode(', ', $member_list));
				} else {
					echo "메시지 생성 실패\n";
					log_message('error', "조직 {$org_id} 금주 미출석 알림 메시지 생성 실패");
				}
			}

			echo "\n=== 배치 실행 결과 ===\n";
			echo "총 조직 수: {$total_orgs}개\n";
			echo "자동 메시지 활성화 조직: {$active_orgs}개\n";
			echo "금주 미출석 알림 메시지 생성 완료: {$success_count}개 조직\n";
			echo "총 미출석 회원: {$message_sent_count}명\n";
			echo "[" . date('Y-m-d H:i:s') . "] 금주 미출석 회원 알림 메시지 배치 종료\n";

		} catch (Exception $e) {
			echo "오류 발생: " . $e->getMessage() . "\n";
			log_message('error', '금주 미출석 회원 알림 메시지 배치 오류: ' . $e->getMessage());
			exit(1);
		}
	}

	/**
	 * 파일 위치: application/controllers/cli/Batch.php
	 * 역할: 2주간 미출석 회원 알림 배치
	 * 실행: php index.php cli/batch send_absence_2week_messages
	 */
	public function send_absence_2week_messages()
	{
		echo "[" . date('Y-m-d H:i:s') . "] 2주간 미출석 회원 알림 메시지 배치 시작\n";

		$this->load->model('Member_model');

		try {
			$orgs = $this->Org_model->get_all_orgs();

			if (empty($orgs)) {
				echo "처리할 조직이 없습니다.\n";
				return;
			}

			$total_orgs = 0;
			$active_orgs = 0;
			$success_count = 0;
			$message_sent_count = 0;

			foreach ($orgs as $org) {
				$org_id = $org['org_id'];
				$org_name = $org['org_name'];
				$auto_message_json = $org['auto_message'];
				$total_orgs++;

				if (empty($auto_message_json) || $auto_message_json === 'null') {
					echo "조직 {$org_name}({$org_id}): 자동 메시지 설정 없음 - 스킵\n";
					continue;
				}

				$auto_message = json_decode($auto_message_json, true);

				if (!is_array($auto_message)) {
					echo "조직 {$org_name}({$org_id}): 자동 메시지 설정 파싱 실패 - 스킵\n";
					continue;
				}

				if (!isset($auto_message['absence2Week']) || $auto_message['absence2Week'] !== true) {
					echo "조직 {$org_name}({$org_id}): 2주간 미출석 알림 비활성화 - 스킵\n";
					continue;
				}

				$active_orgs++;
				echo "조직 {$org_name}({$org_id}) 2주간 미출석 회원 확인 중...\n";

				$absent_members = $this->Member_model->get_absent_members_consecutive_weeks($org_id, 2);

				if (empty($absent_members)) {
					echo "2주간 미출석 회원 없음\n";
					continue;
				}

				$member_count = count($absent_members);
				$member_list = array();

				foreach ($absent_members as $member) {
					$member_list[] = $member['member_name'];
				}

				$message_title = "2주간 연속 미출석 회원 {$member_count}명";
				$message_content = "2주간 연속 출석하지 않은 회원:\n" . implode(', ', $member_list) . "님";

				$message_data = array(
					'message_date' => date('Y-m-d H:i:s'),
					'message_type' => 'ABSENCE_2WEEK',
					'message_title' => $message_title,
					'message_content' => $message_content
				);

				$result = $this->Message_model->send_message_to_org($org_id, $message_data);

				if ($result) {
					$success_count++;
					$message_sent_count += $member_count;
					echo "2주간 미출석 알림 메시지 생성 완료 ({$member_count}명)\n";
					log_message('info', "조직 {$org_id}에 2주간 미출석 알림 메시지 생성: " . implode(', ', $member_list));
				} else {
					echo "메시지 생성 실패\n";
					log_message('error', "조직 {$org_id} 2주간 미출석 알림 메시지 생성 실패");
				}
			}

			echo "\n=== 배치 실행 결과 ===\n";
			echo "총 조직 수: {$total_orgs}개\n";
			echo "자동 메시지 활성화 조직: {$active_orgs}개\n";
			echo "2주간 미출석 알림 메시지 생성 완료: {$success_count}개 조직\n";
			echo "총 미출석 회원: {$message_sent_count}명\n";
			echo "[" . date('Y-m-d H:i:s') . "] 2주간 미출석 회원 알림 메시지 배치 종료\n";

		} catch (Exception $e) {
			echo "오류 발생: " . $e->getMessage() . "\n";
			log_message('error', '2주간 미출석 회원 알림 메시지 배치 오류: ' . $e->getMessage());
			exit(1);
		}
	}

	/**
	 * 파일 위치: application/controllers/cli/Batch.php
	 * 역할: 5주간 미출석 회원 알림 배치
	 * 실행: php index.php cli/batch send_absence_5week_messages
	 */
	public function send_absence_5week_messages()
	{
		echo "[" . date('Y-m-d H:i:s') . "] 5주간 미출석 회원 알림 메시지 배치 시작\n";

		$this->load->model('Member_model');

		try {
			$orgs = $this->Org_model->get_all_orgs();

			if (empty($orgs)) {
				echo "처리할 조직이 없습니다.\n";
				return;
			}

			$total_orgs = 0;
			$active_orgs = 0;
			$success_count = 0;
			$message_sent_count = 0;

			foreach ($orgs as $org) {
				$org_id = $org['org_id'];
				$org_name = $org['org_name'];
				$auto_message_json = $org['auto_message'];
				$total_orgs++;

				if (empty($auto_message_json) || $auto_message_json === 'null') {
					echo "조직 {$org_name}({$org_id}): 자동 메시지 설정 없음 - 스킵\n";
					continue;
				}

				$auto_message = json_decode($auto_message_json, true);

				if (!is_array($auto_message)) {
					echo "조직 {$org_name}({$org_id}): 자동 메시지 설정 파싱 실패 - 스킵\n";
					continue;
				}

				if (!isset($auto_message['absence5Week']) || $auto_message['absence5Week'] !== true) {
					echo "조직 {$org_name}({$org_id}): 5주간 미출석 알림 비활성화 - 스킵\n";
					continue;
				}

				$active_orgs++;
				echo "조직 {$org_name}({$org_id}) 5주간 미출석 회원 확인 중...\n";

				$absent_members = $this->Member_model->get_absent_members_consecutive_weeks($org_id, 5);

				if (empty($absent_members)) {
					echo "5주간 미출석 회원 없음\n";
					continue;
				}

				$member_count = count($absent_members);
				$member_list = array();

				foreach ($absent_members as $member) {
					$member_list[] = $member['member_name'];
				}

				$message_title = "5주간 연속 미출석 회원 {$member_count}명";
				$message_content = "5주간 연속 출석하지 않은 회원:\n" . implode(', ', $member_list) . "님\n관심이 필요합니다.";

				$message_data = array(
					'message_date' => date('Y-m-d H:i:s'),
					'message_type' => 'ABSENCE_5WEEK',
					'message_title' => $message_title,
					'message_content' => $message_content
				);

				$result = $this->Message_model->send_message_to_org($org_id, $message_data);

				if ($result) {
					$success_count++;
					$message_sent_count += $member_count;
					echo "5주간 미출석 알림 메시지 생성 완료 ({$member_count}명)\n";
					log_message('info', "조직 {$org_id}에 5주간 미출석 알림 메시지 생성: " . implode(', ', $member_list));
				} else {
					echo "메시지 생성 실패\n";
					log_message('error', "조직 {$org_id} 5주간 미출석 알림 메시지 생성 실패");
				}
			}

			echo "\n=== 배치 실행 결과 ===\n";
			echo "총 조직 수: {$total_orgs}개\n";
			echo "자동 메시지 활성화 조직: {$active_orgs}개\n";
			echo "5주간 미출석 알림 메시지 생성 완료: {$success_count}개 조직\n";
			echo "총 미출석 회원: {$message_sent_count}명\n";
			echo "[" . date('Y-m-d H:i:s') . "] 5주간 미출석 회원 알림 메시지 배치 종료\n";

		} catch (Exception $e) {
			echo "오류 발생: " . $e->getMessage() . "\n";
			log_message('error', '5주간 미출석 회원 알림 메시지 배치 오류: ' . $e->getMessage());
			exit(1);
		}
	}

}
