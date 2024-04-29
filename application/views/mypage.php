<html lang="ko">
<head>
    <meta charset="utf-8">

    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title></title>
    <meta name="viewport" content="width=device-width, initial-scale=0.8">
    <meta name="description" content="심플체크" />
    <meta name="keywords" content="출석 체크, 교적, 메모, 심방" />
    <meta name="author" content="WEBHOWS.COM" />

    <!-- Facebook and Twitter integration -->
    <meta property="og:title" content="심플체크"/>
    <meta property="og:image" content=""/>
    <meta property="og:url" content=""/>
    <meta property="og:site_name" content="심플체크"/>
    <meta property="og:description" content="심플체크"/>
    <meta name="twitter:title" content="심플체크" />
    <meta name="twitter:image" content="" />
    <meta name="twitter:url" content="simplechk" />
    <meta name="twitter:card" content="심플체크" />

    <title>심플체크</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="//code.jquery.com/ui/1.13.1/themes/base/jquery-ui.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="/assets/css/common.css?<?php echo date('Ymdhis');?>">

</head>
<body>
<header>

</header>
<main>
    <div class="container-xl">


        <button href="#" class="btn btn-primary btn-sm add-group" data-bs-toggle="modal" data-bs-target="#groupModal">그룹추가</button>




        <div class="table-responsive-xl">
        <table class="table align-middle">

        <thead>
            <tr>
                <th scope="col">그룹명</th>
                <th scope="col">회원수</th>
                <th scope="col">사용자수</th>
                <th scope="col">기본설정</th>
                <th scope="col">사용자설정</th>
                <th scope="col">출석타입설정</th>
                <th scope="col">그룹복사</th>
                <th scope="col">그룹삭제</th>
            </tr>
        </thead>
            <tbody class="table-group-divider">

            <?php if (empty($groups)): ?>
                <td>개설된 그룹이 없습니다.</td>
            <?php else: ?>
                <?php foreach ($groups as $group): ?>
                    <tr>
                        <td>
                            <?php echo $group['group_name']; ?>
                        </td>
                        <td>10명</td>
                        <td>10명</td>
                        <td>
                            <a class="btn btn-light btn-sm" data-group-id="<?php echo $group['group_id']; ?>" data-group-name="<?php echo $group['group_name']; ?>" data-leader-name="<?php echo $group['leader_name']; ?>" data-new-name="<?php echo $group['new_name']; ?>">기본설정</a>
                        </td>
                        <td><a href="" class="btn btn-light btn-sm">사용자설정</a></td>
                        <td><a href="" class="btn btn-light btn-sm">출석타입설정</a></td>
                        <td><a href="" class="btn btn-light btn-sm">그룹복사</a></td>
                        <td><a href="" class="btn btn-danger btn-sm">그룹삭제</a></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>

        </tbody>
        </table>
        </div>





    </div>
</main>


<!-- 그룹 수정 모달 -->
<div class="modal fade" id="settingGroupModal" tabindex="-1" aria-labelledby="settingGroupModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="settingGroupModalLabel">그룹 설정</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="edit_group_name" class="form-label">그룹명</label>
                    <input type="text" class="form-control" id="edit_group_name" name="edit_group_name" required>
                    <input type="hidden" id="edit_group_id" name="edit_group_id">
                </div>

                <div class="mb-3">
                    <label for="edit_leader_name" class="form-label">리더명</label>
                    <input type="text" class="form-control" id="edit_leader_name" name="edit_leader_name" required>
                </div>

                <div class="mb-3">
                    <label for="edit_new_name" class="form-label">새방문자명</label>
                    <input type="text" class="form-control" id="edit_new_name" name="edit_new_name" required>
                </div>


            </div>
            <div class="modal-footer">
                <button class="btn btn-danger btn-sm float-end btn-del">삭제</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                <button type="button" class="btn btn-primary" id="updateGroup">저장</button>
            </div>
        </div>
    </div>
</div>

<!-- 그룹 추가 모달 -->
<div class="modal fade" id="groupModal" tabindex="-1" aria-labelledby="groupModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="groupModalLabel">그룹 추가</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="group_name" class="form-label">그룹명</label>
                    <input type="text" class="form-control" id="group_name" name="group_name" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                <button type="button" class="btn btn-primary" id="saveGroup">저장</button>
            </div>
        </div>
    </div>
</div>



<script>
    // 그룹 수정 버튼 클릭 이벤트
    $(document).on('click', '.btn-setting', function() {
        var groupId = $(this).data('group-id');
        var groupName = $(this).data('group-name');
        var leaderName = $(this).data('leader-name');
        var newName = $(this).data('new-name');

        $('#edit_group_id').val(groupId);
        $('#edit_group_name').val(groupName);
        $('#edit_leader_name').val(leaderName);
        $('#edit_new_name').val(newName);

        $('#settingGroupModal').modal('show');
    });


    // 그룹 클릭 이벤트 핸들러 수정
    $(document).on('click', '.group-list li', function() {
        var groupId = $(this).find('.btn-setting').data('group-id');
        var groupName = $(this).find('.btn-setting').data('group-name');

        // 모든 group-list 항목에서 active 클래스 제거
        $('.group-list li').removeClass('active');
        // 클릭한 group-list 항목에 active 클래스 추가
        $(this).addClass('active');

        // 쿠키에 활성화된 그룹 ID 저장
        setCookie('activeGroup', groupId, 7);

        // <h2> 태그의 내용을 선택된 그룹의 group_name으로 변경
        $('h2.mb-1').text(groupName);

        // 현재 선택된 주차 범위 가져오기
        var currentWeekRange = $('.current-week').text();
        var startDate = getWeekStartDate(currentWeekRange);
        var endDate = getWeekEndDate(currentWeekRange);

        // 선택된 그룹에 맞게 dropdown-toggle-att-type 갱신
        updateAttendanceTypes(groupId);

        // 멤버 목록 로드
        loadMembers(groupId, startDate, endDate);
    });
</script>