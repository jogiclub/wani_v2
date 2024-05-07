<?php
class Invite_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }


    public function invite($invite_code) {
        $this->load->model('Invite_model');
        $invite = $this->Invite_model->get_invite_by_code($invite_code);

        if ($invite) {
            $this->load->model('User_model');
            $user_data = array(
                'user_grade' => 1
            );
            $this->User_model->update_user($invite['email'], $user_data);

            // 초대 코드 삭제
            $this->Invite_model->delete_invite($invite_code);

            // 로그인 페이지로 리다이렉트
            redirect('login');
        } else {
            // 초대 코드가 유효하지 않은 경우 에러 페이지로 리다이렉트
            redirect('error');
        }
    }



    public function delete_invite($invite_code) {
        $this->db->delete('wb_invite', array('invite_code' => $invite_code));
    }


}
