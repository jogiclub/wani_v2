<html lang="ko">
<head>
    <?php $this->load->view('header'); ?>
</head>
<body>
<header class="pt-3 pb-3 border-bottom d-flex justify-content-center">
    <div class="container-xl">
        <div class="row">
            <div class="col-12 text-center position-relative">
                <div class="logo"><img src="/assets/images/logo.png?2"></div>
                <a class="btn-profile dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" style="top: -4px">
                    <img src="<?php if($user['user_profile_image']){echo $user['user_profile_image'];} else {echo '/assets/images/photo_no.png?3';} ?>" class="rounded-circle" width="40" height="40">
                </a>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="#"><?php if($user['user_name']){echo $user['user_name'];} ?></a></li>
                    <li><a class="dropdown-item" href="#"><?php if($user['user_mail']){echo $user['user_mail'];} ?></a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="<?php echo base_url('main/logout'); ?>">로그아웃</a></li>
                </ul>
            </div>
        </div>
    </div>
</header>
<main>






    <div class="container-xl pt-5 pb-5">
        <div class="table-responsive-xl mypage-group-list">
        <table class="table align-middle" style="min-width: 1000px">

        <thead>
            <tr>
                <th scope="col">바로가기</th>
                <th scope="col">회원수</th>
                <th scope="col">사용자</th>
                <th scope="col">그룹수정</th>
                <th scope="col">통계</th>
                <th scope="col">QR인쇄</th>
                <th scope="col">출석타입설정</th>
                <th scope="col">회원엑셀업로드</th>
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
                            <a class="btn btn-primary btn-sm open-group-main"><?php echo $group['group_name']; ?> <i class="bi bi-chevron-right"></i></a>
                        </td>

                        <td><?php echo $group['member_count']; ?>명</td>
                        <?php if ($group['user_level'] == 10): ?>
                            <td><a class="btn btn-light btn-sm btn-user-setting" data-group-id="<?php echo $group['group_id']; ?>"><?php echo $group['user_count']; ?>명</a></td>
                        <?php else: ?>
                            <td><?php echo $group['user_count']; ?>명</td>
                        <?php endif; ?>

                        <?php if ($group['user_level'] == 10): ?>
                        <td><a class="btn btn-light btn-sm btn-setting" data-group-id="<?php echo $group['group_id']; ?>" data-group-name="<?php echo $group['group_name']; ?>" data-leader-name="<?php echo $group['leader_name']; ?>" data-new-name="<?php echo $group['new_name']; ?>">그룹수정</a></td>
                        <td><a class="btn btn-light btn-sm btn-summery" data-group-id="<?php echo $group['group_id']; ?>">통계</a></td>
                        <td><a class="btn btn-light btn-sm btn-print-qr" data-group-id="<?php echo $group['group_id']; ?>">QR인쇄</a></td>
                        <td><a href="#" class="btn btn-light btn-sm btn-attendance-type-setting" data-group-id="<?php echo $group['group_id']; ?>" onclick="attendanceTypeSetting(<?php echo $group['group_id']; ?>)">출석타입설정</a>
                        </td>
                        <td><a href="#" class="btn btn-light btn-sm btn-member-excel-upload" data-group-id="<?php echo $group['group_id']; ?>">회원엑셀업로드</a></td>
                        <td><a href="#" class="btn btn-danger btn-sm btn-del-group" data-group-id="<?php echo $group['group_id']; ?>">그룹삭제</a></td>
                        <?php else: ?>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
        </div>
        <div class="comment">
            <ul>
                <li>바로가기 버튼을 클릭하시면 해당 그룹으로 이동합니다.</li>
                <li>오른쪽 하단의 그룹추가 버튼을 클릭하시면 내가 관리할 수 있는 그룹이 생성됩니다.</li>
                <li>'엑셀업로드' 기능으로 회원을 일괄 등록할 수 있습니다.</li>
            </ul>
        </div>
    </div>
</main>
<footer>
    <button class="btn btn-primary btn-sm btn-add-group"><i class="bi bi-folder-plus"></i></button>
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
<div class="modal fade" id="addGroupModal" tabindex="-1" aria-labelledby="groupModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="groupModalLabel">그룹 추가</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="group_name" class="form-label">그룹명</label>
                    <div class="input-group">
                        <input type="text" class="form-control" id="group_name" name="group_name"  required>
                        <button type="button" class="btn btn-primary" id="saveGroup">저장</button>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="invite_code" class="form-label">초대코드</label>
                    <span>(리더에게 받은 초대코드를 입력하세요!)</span>
                    <div class="input-group">
                        <input type="text" class="form-control" id="invite_code" name="invite_code" placeholder="5자리 초대코드 입력" required>
                        <button type="button" class="btn btn-primary" id="saveInvate">저장</button>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
<!-- 사용자 목록 모달 -->
<div class="modal fade" id="userListModal" tabindex="-1" aria-labelledby="userListModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="userListModalLabel">사용자 목록</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="input-group mb-3">
                    <input type="text" class="form-control" placeholder="메일주소를 입력하세요!" aria-label="invite-email" aria-describedby="invite-user">
                    <button class="btn btn-outline-secondary" type="button" id="invite-user">초대메일 전송</button>
                </div>
                <div class="table-responsive-xl" style="overflow-y: scroll">
                    <table class="table align-middle" style="min-width: 900px">
                        <colgroup>
                            <col style="width: 120px">
                            <col style="width: 120px">
                            <col style="width: 120px">
                            <col style="">
                            <col style="width: 100px">
                            <col style="width: 70px">
                            <col style="width: 70px">

                        </colgroup>
                        <thead>
                        <tr>
                            <th>아이디</th>
                            <th>이름</th>
                            <th>이메일</th>
                            <th>휴대폰번호</th>
                            <th>권한</th>
                            <th>저장</th>
                            <th>삭제</th>
                            <th class="master-hidden">로그인</th>
                        </tr>
                        </thead>
                        <tbody class="table-group-divider" id="userListTableBody">
                        <!-- 사용자 목록이 여기에 동적으로 추가됩니다. -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer d-block">
                <ul class="help-list">
                    <li>권한이 2이며, 휴대폰번호가 입력되어 있다면 로그인한 리더 본인의 정보만 확인이 가능합니다.</li>
                </ul>
            </div>
        </div>
    </div>
</div>




<!-- 엑셀 업로드 모달 -->
<div class="modal fade" id="excelUploadModal" tabindex="-1" aria-labelledby="excelUploadModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="excelUploadModalLabel">회원 엑셀 업로드</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <span><a href="/assets/excel/sample.xlsx" download style="color:blue">여기</a>를 클릭하여 샘플엑셀파일을 다운 받으세요!<br/>
                작성된 엑셀 파일을 아래에 업로드 하세요!<br/><br/>
                </span>

                <input type="file" id="excelFile" accept=".xlsx, .xls">
                <div class="progress mt-3" style="display: none;">
                    <div class="progress-bar" role="progressbar" style="width: 0;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                <button type="button" class="btn btn-primary" id="startUpload">업로드 시작</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="qrPrintModal" tabindex="-1" aria-labelledby="qrPrintModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="qrPrintModalLabel">QR 코드 인쇄</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <button type="button" class="btn btn-primary mb-1 width-100" id="printLabel01">A4(7x8)/규격코드:978</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="summeryModal" tabindex="-1" aria-labelledby="summeryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="summeryModalLabel">통계</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <button type="button" class="btn btn-primary mb-1 width-100" id="summery01">주별 출석 통계</button>
                <button type="button" class="btn btn-primary mb-1 width-100" id="summery02">회원별 출석 통계</button>
            </div>
        </div>
    </div>
</div>


<?php $this->load->view('footer'); ?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js" integrity="sha512-CNgIRecGo7nphbeZ04Sc13ka07paqdeTu0WR1IM4kNcpmBAUSHSQX0FslNhTDadL4O5SAGapGt4FodqL8My0mA==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.js" integrity="sha512-is1ls2rgwpFZyixqKFEExPHVUUL+pPkBEPw47s/6NDQ4n1m6T/ySeDW3p54jp45z2EJ0RSOgilqee1WhtelXfA==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script>
    var userInfo = {
        currentUserId: '<?php echo $this->session->userdata('user_id'); ?>',
        currentUserMasterYn: '<?php echo $this->session->userdata('master_yn'); ?>'
    };
</script>
<script src="/assets/js/mypage.js?<?php echo date('Ymdhis');?>"></script>




