

    <?php $this->load->view('header'); ?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <h2 class="text-center">회원가입</h2>
            <form action="<?php echo base_url('login/process'); ?>" method="post">


                <input type="hidden" class="form-control" id="user_id" name="user_id" value="<?php echo isset($user_id) ? $user_id : ''; ?>">
                <div class="mb-3">
                    <label for="user_name" class="form-label">이름</label>
                    <input type="text" class="form-control" id="user_name" name="user_name" value="<?php echo isset($user_name) ? $user_name : ''; ?>">
                </div>
                <div class="mb-3">
                    <label for="user_email" class="form-label">이메일</label>
                    <input type="email" class="form-control" id="user_email" name="user_email" value="<?php echo isset($user_email) ? $user_email : ''; ?>">
                </div>



                <div class="mb-3">


                    <input type="checkbox" class="form-check-input" id="agree_terms" name="agree_terms" required>
                    <label class="form-check-label" for="agree_terms"><a href="/login/terms">서비스 이용약관</a>에 동의합니다.</label>
                </div>
                <div class="mb-3">

                    <input type="checkbox" class="form-check-input" id="agree_privacy" name="agree_privacy" required>
                    <label class="form-check-label" for="agree_privacy"><a href="/login/privacy">개인정보처리방침</a>에 동의합니다.</label>
                </div>
                <button type="submit" class="btn btn-primary">회원가입</button>
            </form>
        </div>
    </div>
</div>

<?php $this->load->view('footer'); ?>
