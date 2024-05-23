<!DOCTYPE html>
<html lang="ko">
<head>
    <?php $this->load->view('header'); ?>


</head>
<body>






<div class="table-responsive-xl table-summery-member">
<table class="table">
    <thead>
    <tr>
        <th colspan="3" rowspan="2">
            <div class="btn-group">
                <select id="att_type_select" class="form-select">
                    <option value="">--출석타입--</option>
                    <?php foreach ($attendance_types as $type): ?>
                        <option value="<?php echo $type['att_type_idx']; ?>" <?php echo ($this->input->post('att_type_idx') == $type['att_type_idx']) ? 'selected' : ''; ?>><?php echo $type['att_type_name']; ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="button" class="btn btn-primary" id="searchBtn">조회</button>
                <button type="button" class="btn btn-success" id="excelDownloadBtn">엑셀</button>
            </div>
        </th>
        <?php foreach ($weeks as $week): ?>
            <th>
                <?php echo $week['week_number']; ?>주차<br>
            </th>
        <?php endforeach; ?>
    </tr>
    <tr>

        <?php foreach ($weeks as $week): ?>
            <th>
                <?php echo date('m/d', strtotime($week['start_date'])); ?><br>
            </th>
        <?php endforeach; ?>
    </tr>
    <tr>
        <th rowspan="2">이름</th>
        <th rowspan="2">구역</th>
        <th rowspan="2">소계</th>
        <?php foreach ($weeks as $week): ?>
            <th>
                <?php echo date('m/d', strtotime($week['end_date'])); ?>
            </th>
        <?php endforeach; ?>
    </tr>
    <tr>

        <?php foreach ($weeks as $week): ?>
            <th>
                <span id="week_sum"></span>
            </th>
        <?php endforeach; ?>
    </tr>
    </thead>
    <tbody class="table-group-divider">
    <?php foreach ($members as $member): ?>
        <tr>
            <td><?php echo $member['member_name']; ?></td>
            <td><?php echo $member['area']; ?></td>
            <td><span id="member_sum"></span></td>
            <?php foreach ($weeks as $week): ?>
                <td id="attendance-<?php echo $member['member_idx']; ?>-<?php echo $week['week_number']; ?>">0</td>
            <?php endforeach; ?>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>



</body>
</html>


<?php $this->load->view('footer'); ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.16.9/xlsx.full.min.js"></script>


<script>
    $(document).ready(function() {
        var groupId = '<?php echo $group_id; ?>';

        $('#searchBtn').click(function() {
            var selectedAttTypeIdx = $('#att_type_select').val();
            searchAttendance(groupId, selectedAttTypeIdx);
        });

        $('#att_type_select').change(function() {
            var selectedAttTypeIdx = $(this).val();
            searchAttendance(groupId, selectedAttTypeIdx);
        });

        function searchAttendance(groupId, attTypeIdx) {
            // 테이블 초기화
            $('table tbody td:not(:first-child):not(:nth-child(2)):not(:nth-child(3))').text('0');
            $('table thead #week_sum').text('0');
            $('table tbody span[id^="member_sum_"]').text('0');

            $.ajax({
                url: '/mypage/summery_member',
                type: 'POST',
                data: {
                    group_id: groupId,
                    att_type_idx: attTypeIdx
                },
                dataType: 'json',
                success: function(response) {
                    $.each(response, function(memberIdx, weekData) {
                        var memberSum = 0;
                        $.each(weekData, function(weekNumber, count) {
                            var cellId = '#attendance-' + memberIdx + '-' + weekNumber;
                            $(cellId).text(count);
                            memberSum += parseInt(count);
                        });
                        $('#member_sum_' + memberIdx).text(memberSum);
                    });

                    // 주차별 합계 계산
                    var weekSums = [];
                    $('table thead th:gt(2)').each(function(index) {
                        var sum = 0;
                        $('table tbody td:nth-child(' + (index + 4) + ')').each(function() {
                            sum += parseInt($(this).text());
                        });
                        weekSums.push(sum);
                    });

                    // 주차별 합계 표시
                    $('table thead #week_sum').each(function(index) {
                        $(this).text(weekSums[index]);
                    });


                    // 회원별 합계 계산 및 표시
                    $('table tbody tr').each(function() {
                        var memberSum = 0;
                        $(this).find('td:gt(2)').each(function() {
                            memberSum += parseInt($(this).text());
                        });
                        $(this).find('#member_sum').text(memberSum);
                    });
                },
                error: function() {
                    alert('출석 데이터를 가져오는데 실패했습니다.');
                }
            });
        }

        $('#excelDownloadBtn').click(function() {
            var table = document.querySelector('table');
            var wb = XLSX.utils.table_to_book(table);
            XLSX.writeFile(wb, 'attendance_summary.xlsx');
        });
    });
</script>
