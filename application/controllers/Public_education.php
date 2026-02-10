<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Public_education extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->helper('url');
        $this->load->model('Education_model');
        $this->load->model('Org_model'); // org_name 조인을 위해 필요
    }

    public function index()
    {
        $this->load->view('public_education_list');
    }

    public function get_edu_list()
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }

        $search_params = array(
            'date' => $this->input->post('date'),
            'days' => $this->input->post('days'),
            'times' => $this->input->post('times'),
            'ages' => $this->input->post('ages'),
            'genders' => $this->input->post('genders'),
            'keyword' => $this->input->post('keyword')
        );

        $edu_list = $this->get_all_public_edu_list($search_params);
        $edu_list = $this->process_edu_category_names($edu_list);

        // 포스터 이미지 경로 처리
        foreach ($edu_list as &$edu) {
            if (!empty($edu['poster_img']) && !filter_var($edu['poster_img'], FILTER_VALIDATE_URL)) {
                $edu['poster_img'] = site_url($edu['poster_img']);
            }
        }

        echo json_encode(array(
            'success' => true,
            'data' => $edu_list,
            'total_count' => count($edu_list)
        ));
    }

    private function get_all_public_edu_list($search_params = array())
    {
        $this->db->select('e.*, o.org_name');
        $this->db->from('wb_edu e');
        $this->db->join('wb_org o', 'e.org_id = o.org_id');
        $this->db->where('e.del_yn', 'N');
        $this->db->where('o.del_yn', 'N');
        $this->db->where('e.public_yn', 'Y');
        $this->apply_search_filters($search_params);
        $this->db->order_by('e.regi_date', 'DESC');
        $query = $this->db->get();
        return $query->result_array();
    }

    private function apply_search_filters($params)
    {
        if (!empty($params['date'])) {
            $this->db->where('e.edu_start_date <=', $params['date']);
            $this->db->where('e.edu_end_date >=', $params['date']);
        }

        if (!empty($params['days']) && is_array($params['days'])) {
            $day_conditions = array();
            foreach ($params['days'] as $day) {
                $day_conditions[] = "e.edu_days LIKE '%\"" . $this->db->escape_like_str($day) . "\"%'";
            }
            if (!empty($day_conditions)) {
                $this->db->where('(' . implode(' OR ', $day_conditions) . ')');
            }
        }

        if (!empty($params['times']) && is_array($params['times'])) {
            $time_conditions = array();
            foreach ($params['times'] as $time) {
                $time_conditions[] = "e.edu_times LIKE '%\"" . $this->db->escape_like_str($time) . "\"%'";
            }
            if (!empty($time_conditions)) {
                $this->db->where('(' . implode(' OR ', $time_conditions) . ')');
            }
        }

        if (!empty($params['ages']) && is_array($params['ages'])) {
            $this->db->where_in('e.edu_leader_age', $params['ages']);
        }

        if (!empty($params['genders']) && is_array($params['genders'])) {
            $this->db->where_in('e.edu_leader_gender', $params['genders']);
        }

        if (!empty($params['keyword'])) {
            $this->db->group_start();
            $this->db->like('e.edu_name', $params['keyword']);
            $this->db->or_like('e.edu_location', $params['keyword']);
            $this->db->or_like('e.edu_leader', $params['keyword']);
            $this->db->group_end();
        }
    }

    private function process_edu_category_names($edu_list)
    {
        if (empty($edu_list)) {
            return array();
        }

        $org_ids = array_unique(array_column($edu_list, 'org_id'));

        if (empty($org_ids)) {
            foreach ($edu_list as &$edu) {
                $edu['category_name'] = '';
            }
            unset($edu);
            return $edu_list;
        }

        $this->db->select('org_id, category_json');
        $this->db->from('wb_edu_category');
        $this->db->where_in('org_id', $org_ids);
        $category_jsons_raw = $this->db->get()->result_array();

        $org_category_map = array();
        foreach ($category_jsons_raw as $row) {
            $json_data = json_decode($row['category_json'], true);
            if (is_array($json_data) && isset($json_data['categories'])) {
                $category_lookup = array();
                $this->build_category_lookup($json_data['categories'], $category_lookup);
                $org_category_map[$row['org_id']] = $category_lookup;
            }
        }

        foreach ($edu_list as &$edu) {
            $edu['category_name'] = '';
            if (!empty($edu['org_id']) && !empty($edu['category_code'])) {
                $org_id = $edu['org_id'];
                $category_code = $edu['category_code'];

                if (isset($org_category_map[$org_id]) && isset($org_category_map[$org_id][$category_code])) {
                    $edu['category_name'] = $org_category_map[$org_id][$category_code];
                }
            }
        }
        unset($edu);

        return $edu_list;
    }

    private function build_category_lookup($categories, &$lookup)
    {
        foreach ($categories as $category) {
            if (isset($category['code']) && isset($category['name'])) {
                $lookup[$category['code']] = $category['name'];
            }
            if (isset($category['children']) && is_array($category['children']) && !empty($category['children'])) {
                $this->build_category_lookup($category['children'], $lookup);
            }
        }
    }

    public function get_distinct_edu_times()
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }

        $this->db->select('edu_times');
        $this->db->from('wb_edu');
        $this->db->where('del_yn', 'N');
        $this->db->where('public_yn', 'Y');
        $this->db->where('edu_times IS NOT NULL');
        $this->db->where("edu_times != '[]'");
        $this->db->where("edu_times != ''");
        $query = $this->db->get();
        $results = $query->result_array();

        $all_times = array();
        foreach ($results as $row) {
            $times = json_decode($row['edu_times'], true);
            if (is_array($times)) {
                foreach ($times as $time) {
                    $all_times[] = $time;
                }
            }
        }

        $distinct_times = array_unique($all_times);
        sort($distinct_times, SORT_NATURAL);

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(array(
            'success' => true,
            'data' => array_values($distinct_times)
        ));
    }

    public function get_total_public_edu_count()
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }

        $this->db->from('wb_edu e');
        $this->db->where('e.del_yn', 'N');
        $this->db->where('e.public_yn', 'Y');
        $total_count = $this->db->count_all_results();

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(array('total_count' => $total_count));
    }
}
