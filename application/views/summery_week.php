<!DOCTYPE html>
<html lang="ko">
<head>
    <?php $this->load->view('header'); ?>
</head>
<body>
<div class="table-responsive-xl table-summery-week">
<table class="table">
    <thead>
    <tr>
        <th>기간</th>
        <?php foreach ($attendance_types as $type): ?>
            <th><?php echo $type['att_type_name']; ?></th>
        <?php endforeach; ?>
    </tr>
    </thead>
    <tbody class="table-org-divider">
    <?php foreach ($weeks as $week): ?>
        <tr>
            <td><?php echo $week['start_date']; ?> ~ <?php echo $week['end_date']; ?> (<?php echo $week['week_number']; ?>주차)</td>
            <?php foreach ($attendance_types as $type): ?>
                <td>
                    <?php
                    $att_type_idx = $type['att_type_idx'];
                    $week_number = $week['week_number'];
                    if (isset($attendance_data[$week_number][$att_type_idx])) {
                        echo $attendance_data[$week_number][$att_type_idx];
                    } else {
                        echo '0';
                    }
                    ?>
                </td>
            <?php endforeach; ?>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>


</body>
</html>


<?php $this->load->view('footer'); ?>
