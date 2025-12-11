<?php
/**
 * 역할: 타임라인 관리 컨트롤러 - 회원 타임라인 이력 조회 및 관리
 */

defined('BASEPATH') or exit('No direct script access allowed');

class Timeline extends My_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->library('session');
		$this->load->helper('url');
		$this->load->model('Timeline_model');
		$this->load->model('Member_model');
		$this->load->model('Org_model');

		// 메뉴 권한 체크
		$this->check_menu_access('TIMELINE_MANAGEMENT');


	}


	/**
	 * 타임라인 관리 메인 페이지
	 */
	public function index()
	{
		$user_id = $this->session->userdata('user_id');

		$header_data = $this->prepare_header_data();
		if (empty($header_data['user_orgs'])) {
			redirect('dashboard');
			return;
		}

		$data = $header_data;
		$this->handle_org_change($data);

		$currentOrgId = $data['current_org']['org_id'];

		if (!$this->check_org_access($currentOrgId)) {
			$this->handle_access_denied('해당 조직의 타임라인을 관리할 권한이 없습니다.');
			return;
		}

		// 조직 정보 상세 조회 (org_rep, org_seal 포함)
		$this->db->select('org_id, org_name, org_rep, org_seal');
		$this->db->from('wb_org');
		$this->db->where('org_id', $currentOrgId);
		$org_detail = $this->db->get()->row_array();

		if ($org_detail) {
			$data['current_org'] = array_merge($data['current_org'], $org_detail);
		}

		$data['orgs'] = array($data['current_org']);

		$this->load->view('timeline', $data);
	}

	/**
	 * 타임라인 목록 조회
	 */
	public function get_timelines()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$org_id = $this->input->post('org_id');
		$timeline_types = $this->input->post('timeline_types'); // 배열로 받음
		$search_text = $this->input->post('search_text');
		$year = $this->input->post('year');
		$month = $this->input->post('month');

		if (!$org_id) {
			echo json_encode(array(
				'success' => false,
				'message' => '조직 ID가 필요합니다.'
			));
			return;
		}

		// 필터 조건 설정
		$filters = array(
			'timeline_types' => $timeline_types,
			'search_text' => $search_text,
			'year' => $year,
			'month' => $month
		);

		$timelines = $this->Timeline_model->get_timelines($org_id, $filters);
		$total_count = $this->Timeline_model->get_timelines_count($org_id, $filters);

		echo json_encode(array(
			'success' => true,
			'curPage' => 1,
			'totalRecords' => $total_count,
			'data' => $timelines
		));
	}

	/**
	 * 타임라인 항목 조회
	 */
	public function get_timeline_types()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$org_id = $this->input->post('org_id');

		if (!$org_id) {
			echo json_encode(array('success' => false, 'message' => '조직 ID가 필요합니다.'));
			return;
		}

		$timeline_types = $this->Org_model->get_timeline_types($org_id);

		echo json_encode(array(
			'success' => true,
			'data' => $timeline_types
		));
	}

	/**
	 * 회원 목록 조회 (Select2용)
	 */
	public function get_members_for_select()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$org_id = $this->input->get('org_id');
		$search = $this->input->get('search');

		if (!$org_id) {
			echo json_encode(array('success' => false, 'message' => '조직 ID가 필요합니다.'));
			return;
		}

		$members = $this->Member_model->get_members_for_select($org_id, $search);

		echo json_encode(array(
			'success' => true,
			'data' => $members
		));
	}

	/**
	 * 타임라인 일괄추가
	 */
	public function add_timeline()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$org_id = $this->input->post('org_id');
		$member_idxs = $this->input->post('member_idxs');
		$timeline_type = $this->input->post('timeline_type');
		$timeline_date = $this->input->post('timeline_date');
		$timeline_content = $this->input->post('timeline_content');
		$user_id = $this->session->userdata('user_id');

		if (!$org_id || !$member_idxs || !$timeline_type || !$timeline_date) {
			echo json_encode(array('success' => false, 'message' => '필수 항목을 입력해주세요.'));
			return;
		}

		if (!is_array($member_idxs)) {
			$member_idxs = array($member_idxs);
		}

		$data = array(
			'timeline_type' => $timeline_type,
			'timeline_date' => $timeline_date,
			'timeline_content' => $timeline_content,
			'user_id' => $user_id
		);

		$result = $this->Timeline_model->add_timelines($member_idxs, $data);

		if ($result) {
			echo json_encode(array('success' => true, 'message' => '타임라인이 추가되었습니다.'));
		} else {
			echo json_encode(array('success' => false, 'message' => '타임라인 일괄추가에 실패했습니다.'));
		}
	}

	/**
	 * 타임라인 수정
	 */
	public function update_timeline()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$idx = $this->input->post('idx');
		$timeline_type = $this->input->post('timeline_type');
		$timeline_date = $this->input->post('timeline_date');
		$timeline_content = $this->input->post('timeline_content');

		if (!$idx || !$timeline_type || !$timeline_date) {
			echo json_encode(array('success' => false, 'message' => '필수 항목을 입력해주세요.'));
			return;
		}

		$data = array(
			'timeline_type' => $timeline_type,
			'timeline_date' => $timeline_date,
			'timeline_content' => $timeline_content
		);

		$result = $this->Timeline_model->update_timeline($idx, $data);

		if ($result) {
			echo json_encode(array('success' => true, 'message' => '타임라인이 수정되었습니다.'));
		} else {
			echo json_encode(array('success' => false, 'message' => '타임라인 수정에 실패했습니다.'));
		}
	}

	/**
	 * 타임라인 삭제
	 */
	public function delete_timelines()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$idxs = $this->input->post('idxs');

		if (!$idxs || !is_array($idxs) || count($idxs) === 0) {
			echo json_encode(array('success' => false, 'message' => '삭제할 항목을 선택해주세요.'));
			return;
		}

		$result = $this->Timeline_model->delete_timelines($idxs);

		if ($result) {
			echo json_encode(array('success' => true, 'message' => count($idxs) . '개의 타임라인이 삭제되었습니다.'));
		} else {
			echo json_encode(array('success' => false, 'message' => '타임라인 삭제에 실패했습니다.'));
		}
	}

	/**
	 * 타임라인 상세 조회
	 */
	public function get_timeline_detail()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$idx = $this->input->post('idx');

		if (!$idx) {
			echo json_encode(array('success' => false, 'message' => 'IDX가 필요합니다.'));
			return;
		}

		$timeline = $this->Timeline_model->get_timeline_by_idx($idx);

		if ($timeline) {
			echo json_encode(array('success' => true, 'data' => $timeline));
		} else {
			echo json_encode(array('success' => false, 'message' => '타임라인을 찾을 수 없습니다.'));
		}
	}


	/**
	 * 타임라인 통계 조회 (전체 데이터 기준)
	 */
	public function get_timeline_statistics()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$org_id = $this->input->post('org_id');

		if (!$org_id) {
			echo json_encode(array(
				'success' => false,
				'message' => '조직 ID가 필요합니다.'
			));
			return;
		}

		// 타임라인 통계 조회 (전체 데이터)
		$statistics = $this->Timeline_model->get_timeline_statistics($org_id);

		// 전체 회원 수 조회
		$total_members = $this->Timeline_model->get_org_total_member_count($org_id);

		// 타입별 이름 조회 (순서 정보 포함)
		$timeline_types = $this->Org_model->get_timeline_types($org_id);

		// 통계 데이터를 타입 순서에 맞게 정렬
		$ordered_statistics = array();
		foreach ($timeline_types as $type) {
			foreach ($statistics as $stat) {
				if ($stat['timeline_type'] === $type) {
					$ordered_statistics[] = $stat;
					break;
				}
			}
		}

		echo json_encode(array(
			'success' => true,
			'data' => array(
				'statistics' => $ordered_statistics,
				'total_members' => $total_members,
				'timeline_types' => $timeline_types
			)
		));
	}


	/**
	 * 회원들의 진급/전역 항목 존재 여부 체크
	 */
	public function check_promotion_exists()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$member_idxs = $this->input->post('member_idxs');

		if (!$member_idxs || !is_array($member_idxs)) {
			echo json_encode(array('success' => false, 'message' => '회원 정보가 필요합니다.'));
			return;
		}

		$promotion_types = array('진급(일병)', '진급(상병)', '진급(병장)', '전역');
		$has_promotion = $this->Timeline_model->check_timeline_exists($member_idxs, $promotion_types);

		echo json_encode(array(
			'success' => true,
			'has_promotion' => $has_promotion
		));
	}

	/**
	 * 입대 타임라인 및 진급/전역 타임라인 일괄 생성
	 */
	public function add_timeline_with_promotion()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$org_id = $this->input->post('org_id');
		$member_idxs = $this->input->post('member_idxs');
		$timeline_type = $this->input->post('timeline_type');
		$timeline_date = $this->input->post('timeline_date');
		$timeline_content = $this->input->post('timeline_content');
		$include_promotion = $this->input->post('include_promotion');
		$user_id = $this->session->userdata('user_id');

		if (!$org_id || !$member_idxs || !$timeline_type || !$timeline_date) {
			echo json_encode(array('success' => false, 'message' => '필수 항목을 입력해주세요.'));
			return;
		}

		if (!is_array($member_idxs)) {
			$member_idxs = array($member_idxs);
		}

		$member_count = count($member_idxs);
		$this->db->trans_start();

		try {
			// 1. 입대 타임라인 추가 (선택된 모든 회원에게)
			$enlistment_data = array(
				'timeline_type' => $timeline_type,
				'timeline_date' => $timeline_date,
				'timeline_content' => $timeline_content,
				'user_id' => $user_id
			);
			$this->Timeline_model->add_timelines($member_idxs, $enlistment_data);

			$total_added = $member_count;

			// 2. 진급 및 전역 타임라인 추가 (선택된 모든 회원에게 동일한 날짜로)
			if ($include_promotion) {
				$promotion_timelines = $this->calculate_promotion_dates($timeline_date);

				foreach ($promotion_timelines as $promotion) {
					$promotion_data = array(
						'timeline_type' => $promotion['type'],
						'timeline_date' => $promotion['date'],
						'timeline_content' => '',
						'user_id' => $user_id
					);
					// 각 진급 타입을 모든 선택된 회원에게 추가
					$this->Timeline_model->add_timelines($member_idxs, $promotion_data);
				}

				// 입대 + 진급 4개 = 총 5개 타임라인 * 회원 수
				$total_added = $member_count * 5;
			}

			$this->db->trans_complete();

			if ($this->db->trans_status()) {
				$message = sprintf(
					'%d명의 회원에게 총 %d개의 타임라인이 추가되었습니다.',
					$member_count,
					$total_added
				);
				echo json_encode(array(
					'success' => true,
					'message' => $message
				));
			} else {
				throw new Exception('트랜잭션 실패');
			}
		} catch (Exception $e) {
			$this->db->trans_rollback();
			echo json_encode(array(
				'success' => false,
				'message' => '타임라인 일괄추가에 실패했습니다: ' . $e->getMessage()
			));
		}
	}

	/**
	 * 입대일 기준 진급 및 전역 날짜 계산
	 */
	private function calculate_promotion_dates($enlistment_date)
	{
		$date = new DateTime($enlistment_date);
		$promotions = array();

		// 일병 진급 (2개월 후)
		$private_date = clone $date;
		$private_date->modify('+2 months');
		$promotions[] = array(
			'type' => '진급(일병)',
			'date' => $private_date->format('Y-m-d')
		);

		// 상병 진급 (8개월 후)
		$corporal_date = clone $date;
		$corporal_date->modify('+8 months');
		$promotions[] = array(
			'type' => '진급(상병)',
			'date' => $corporal_date->format('Y-m-d')
		);

		// 병장 진급 (14개월 후)
		$sergeant_date = clone $date;
		$sergeant_date->modify('+14 months');
		$promotions[] = array(
			'type' => '진급(병장)',
			'date' => $sergeant_date->format('Y-m-d')
		);

		// 전역 (18개월 후)
		$discharge_date = clone $date;
		$discharge_date->modify('+18 months');
		$promotions[] = array(
			'type' => '전역',
			'date' => $discharge_date->format('Y-m-d')
		);

		return $promotions;
	}



}
