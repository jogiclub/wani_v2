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
			// 모든 조직 목록 가져오기
			$orgs = $this->Org_model->get_all_orgs();

			if (empty($orgs)) {
				echo "처리할 조직이 없습니다.\n";
				return;
			}

			$total_orgs = 0;
			$success_count = 0;
			$message_sent_count = 0;

			foreach ($orgs as $org) {
				$org_id = $org['org_id'];
				$org_name = $org['org_name'];
				$total_orgs++;

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
				$message_title = "{$member_count}명의 생일 회원이 있습니다.";
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

			echo "\n총 {$total_orgs}개 조직 중 {$success_count}개 조직에 생일 알림 메시지 생성 완료\n";
			echo "총 {$message_sent_count}명의 생일자 알림 발송\n";
			echo "[" . date('Y-m-d H:i:s') . "] 생일 알림 메시지 배치 종료\n";

		} catch (Exception $e) {
			echo "오류 발생: " . $e->getMessage() . "\n";
			log_message('error', '생일 알림 메시지 배치 오류: ' . $e->getMessage());
			exit(1);
		}
	}

}
