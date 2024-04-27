<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="utf-8">
    <title>교회 출석체크</title>
    <link rel="stylesheet" href="/assets/css/common.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" crossorigin="anonymous">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.13.1/jquery-ui.min.js"></script>
</head>
<body>
<div class="school-wrapper">
    <h3><?php echo $group_name; ?></h3>

    <form id="qr_check">
        <input type="hidden" name="mode" value="submit">
        <input type="hidden" name="group_id" value="<?php echo $group_id; ?>">
        <input type="text" name="member_idx" class="form-default main-input" autocomplete="off" placeholder="QR코드를 입력하세요">
        <button type="submit">출석</button>
        <button type="button" class="btn-new" data-bs-toggle="modal" data-bs-target="#memberAdd">새가족</button>
    </form>

    <div class="toast">
        <div class="toast-body"></div>
        <button class="btn-close" data-bs-dismiss="toast"></button>
    </div>

    <div class="status">
        <div class="row">
            <div class="period-area">
                <span class="today-date"><?php echo date('Y-m-d', strtotime($today_date . '-7 days')) . "~" . date('Y-m-d', strtotime($today_date)); ?></span>
            </div>
            <div class="count-area">
                <span class="title">재적</span><span class="total-count">0</span>
                <span class="title">새가족</span><span class="new-count">0</span>
                <span class="title">출석</span><span class="sum-count">0</span>
                <span class="title title-1">출</span><span class="type1-count">0</span>
                <span class="title title-2">온</span><span class="type2-count">0</span>
                <span class="title title-3">장</span><span class="type3-count">0</span>
            </div>
        </div>
    </div>

    <ul class="att-list">
        <?php
        $members = $this->member_model->get_member_list($group_id, $today_date);
        foreach ($members as $row) :
            ?>
            <li>
                <div class="card card-<?php echo $row['member_idx']; ?>"
                    <?php if ($row['att_idx']) : ?>
                        onclick="submit_data('del', '<?php echo $row['member_idx']; ?>', '<?php echo $row['member_name']; ?>', '<?php echo $today_date; ?>', '<?php echo $group_id; ?>', '<?php echo $row['att_idx']; ?>')"
                    <?php else : ?>
                        data-bs-toggle="modal" data-bs-target="#memberAtt"
                        data-member-idx="<?php echo $row['member_idx']; ?>"
                        data-member-name="<?php echo $row['member_name']; ?>"
                        data-group-id="<?php echo $group_id; ?>"
                    <?php endif; ?>>
                    <?php echo $row['member_name']; ?>
                </div>
            </li>
        <?php endforeach; ?>
    </ul>
</div>

<!--회원추가 모달-->
<div class="modal" id="memberAdd">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">회원 추가</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="member_add">
                <input type="hidden" name="mode" value="add">
                <input type="hidden" name="grade" value="0">
                <input type="hidden" name="area" value="새가족">
                <input type="hidden" name="member_phone" value="010-0000-0000">
                <input type="hidden" name="school" value="">
                <input type="hidden" name="address" value="">
                <input type="hidden" name="photo" value="">
                <input type="hidden" name="member_birth" value="0000-00-00">
                <input type="hidden" name="leader_yn" value="N">
                <input type="hidden" name="new_yn" value="Y">
                <input type="hidden" name="group_id" value="<?php echo $group_id; ?>">
                <div class="modal-body">
                    <div>
                        <label for="member_name">이름</label>
                        <input type="text" id="member_name" name="member_name" required>
                    </div>
                    <div>
                        <label for="member_etc">메모</label>
                        <textarea id="member_etc" name="member_etc"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit">저장</button>
                    <button type="button" data-bs-dismiss="modal">닫기</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!--회원정보 모달-->
<div class="modal" id="memberDetail">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="member_save">
                <input type="hidden" name="mode" value="save">
                <div class="modal-body"></div>
                <div class="modal-footer">
                    <button type="submit">저장</button>
                    <button type="button" class="btn-del">회원삭제</button>
                    <button type="button" data-bs-dismiss="modal">닫기</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!--출석 모달-->
<div class="modal" id="memberAtt">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <button class="btn-att">출석</button>
                <button class="btn-online">비대면출석</button>
                <button class="btn-parent">장년예배출석</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ka7Sk0Gln4gmtz2MlQnikT1wXgYsOg+OMhuP+IlRH9sENBO0LRn5q+8nbTov4+1p" crossorigin="anonymous"></script>
<script src="/assets/js/common.js"></script>
</body>
</html>