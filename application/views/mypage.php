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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css" integrity="sha512-jnSuA4Ss2PkkikSOLtYs8BlYIeeIK1h99ty4YfvRPAlzr377vr3CXDb7sb7eEEBYjDtcYj+AjBH3FLv5uSJuXg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.13.3/themes/base/jquery-ui.min.css" integrity="sha512-8PjjnSP8Bw/WNPxF6wkklW6qlQJdWJc/3w/ZQPvZ/1bjVDkrrSqLe9mfPYrMxtnzsXFPc434+u4FHLnLjXTSsg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css" integrity="sha512-dPXYcDub/aeb08c63jRq/k6GaKccl256JQy/AnOq7CAnEZ9FzSL9wSbcZkMp4R26vBsMLFYH4kQ67/bbV8XaCQ==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="/assets/css/common.css?<?php echo date('Ymdhis');?>">

</head>
<body>
<header>

</header>
<main>
    <div class="container-xl">







        <div class="table-responsive-xl">
        <table class="table align-middle">

        <thead>
            <tr>
                <th scope="col">그룹명</th>

                <th scope="col">그룹수정</th>
                <th scope="col">회원수</th>
                <th scope="col">QR인쇄</th>
                <th scope="col">사용자수</th>

                <th scope="col">사용자설정</th>
                <th scope="col">출석타입설정</th>
                <th scope="col">그룹복사</th>
                <th scope="col">그룹삭제</th>
            </tr>
        </thead>
            <tbody class="table-group-divider">

            <?php if (empty($groups)): ?>
                <td colspan="20" style="padding: 20px 0">개설된 그룹이 없습니다.<br/>오른쪽 하단의 그룹 추가 버튼을 선택하여 첫번째 그룹을 만들어보세요!</td>
            <?php else: ?>
                <?php foreach ($groups as $group): ?>
                    <tr data-group-id="<?php echo $group['group_id']; ?>">
                        <td>
                            <a class="btn btn-light btn-sm open-group-main d-block"><?php echo $group['group_name']; ?></a>
                        </td>
                        <td>
                            <a class="btn btn-secondary btn-sm btn-setting" data-group-id="<?php echo $group['group_id']; ?>" data-group-name="<?php echo $group['group_name']; ?>" data-leader-name="<?php echo $group['leader_name']; ?>" data-new-name="<?php echo $group['new_name']; ?>">그룹수정</a>
                        </td>
                        <td><?php echo $group['member_count']; ?>명</td>
                        <td><a href="" class="btn btn-light btn-sm">QR인쇄</a></td>
                        <td><a class="btn btn-secondary btn-sm btn-user-setting" data-group-id="<?php echo $group['group_id']; ?>"><?php echo $group['user_count']; ?>명</a></td>
                        <td><a href="" class="btn btn-light btn-sm">사용자설정</a></td>
                        <td>
                            <a href="#" class="btn btn-light btn-sm btn-attendance-type-setting" data-group-id="<?php echo $group['group_id']; ?>" onclick="attendanceTypeSetting(<?php echo $group['group_id']; ?>)">
                                <?php echo $group['att_count']; ?> 개
                            </a>
                        </td>

                        <td><a href="" class="btn btn-light btn-sm">그룹복사</a></td>
                        <td><a href="#" class="btn btn-danger btn-sm btn-del-group" data-group-id="<?php echo $group['group_id']; ?>">그룹삭제</a></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>

        </tbody>
        </table>
        </div>





    </div>
</main>
<footer>
    <button class="btn btn-primary btn-sm add-group"><i class="bi bi-folder-plus"></i></button>
</footer>



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
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                <button type="button" class="btn btn-primary" id="updateGroup">저장</button>
            </div>
        </div>
    </div>
</div>

<!--출석 타입 설정 기능-->
<div class="modal fade" id="attendanceTypeModal" tabindex="-1" aria-labelledby="attendanceTypeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="attendanceTypeModalLabel">출석 타입 설정</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">


                <div class="input-group mb-3">
                    <input type="text" class="form-control" id="inputAttendanceTypeCategory" placeholder="새 카테고리 이름">
                    <button type="button" class="btn btn-primary" id="addAttendanceTypeCategory">새 카테고리 추가</button>
                </div>

                <div class="input-group mb-3 add-category-wrap">
                    <select class="form-select selectAttendanceTypeCategory" aria-label="selectAttendanceTypeCategory" id="selectAttendanceTypeCategory">
                        <?php foreach ($attendance_type_categories as $category): ?>
                            <option value="<?php echo $category['att_type_category_idx']; ?>"><?php echo $category['att_type_category_name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" class="btn btn-primary" id="addAttendanceType">출석타입 추가</button>
                </div>


                <div class="table-responsive-xl">
                    <table class="table align-middle">
                        <colgroup>
                            <col style="width: 120px">
                            <col>
                            <col style="width: 50px">
                            <col style="width: 50px">
                            <col style="width: 55px">
                            <col style="width: 55px">
                        </colgroup>
                    <thead>
                    <tr>
                        <th>카테고리명</th>
                        <th>출석타입명</th>
                        <th>출력</th>
                        <th>색상</th>
                        <th>수정</th>
                        <th>삭제</th>
                    </tr>
                    </thead>


                    <tbody class="table-group-divider" id="attendanceTypeTableBody">
                    <!-- 출석 타입 목록이 동적으로 생성됩니다 -->
                    </tbody>
                    </table>
                </div>




                <input type="hidden" id="attendanceTypeGroupId" name="group_id" value="">
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
<!-- 사용자 목록 모달 -->
<div class="modal fade" id="userListModal" tabindex="-1" aria-labelledby="userListModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="userListModalLabel">사용자 목록</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <table class="table">
                    <thead>
                    <tr>
                        <th>아이디</th>
                        <th>이름</th>
                        <th>권한</th>
                        <th>이메일</th>
                        <th>휴대폰번호</th>
                    </tr>
                    </thead>
                    <tbody id="userListTableBody">
                    <!-- 사용자 목록이 여기에 동적으로 추가됩니다. -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>


<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/js/bootstrap.min.js" integrity="sha512-ykZ1QQr0Jy/4ZkvKuqWn4iF3lqPZyij9iRv6sGqLRdTPkY69YX6+7wvVGmsdBbiIfN/8OdsI7HABjvEok6ZopQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js" integrity="sha512-v2CJ7UaYy4JwqLDIrZUI/4hqeoQieOmAZNXBeQyjo21dadnwR+8ZaIJVT8EE2iyI61OV8e6M8PP2/4hpQINQ/g==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.13.3/jquery-ui.min.js" integrity="sha512-Ww1y9OuQ2kehgVWSD/3nhgfrb424O3802QYP/A5gPXoM4+rRjiKrjHdGxQKrMGQykmsJ/86oGdHszfcVgUr4hA==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/masonry/4.2.2/masonry.pkgd.min.js" integrity="sha512-JRlcvSZAXT8+5SQQAvklXGJuxXTouyq8oIMaYERZQasB8SBDHZaUbeASsJWpk0UUrf89DP3/aefPPrlMR1h1yQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<!--<script src="https://cdnjs.cloudflare.com/ajax/libs/croppie/2.6.5/croppie.min.js"></script>-->
<script src="/assets/js/common.js?<?php echo date('Ymdhis');?>"></script>
<script src="/assets/js/mypage.js?<?php echo date('Ymdhis');?>"></script>

<script>


</script>