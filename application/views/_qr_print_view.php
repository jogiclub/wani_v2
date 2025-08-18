<!DOCTYPE html>
<html>
<head>
    <title>QR 코드 인쇄</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <style>
        @media print {
            #printBtn {
                display: none;
            }
        }
    </style>
</head>
<body>
<div id="qrCodeContainer"></div>
<button id="printBtn">인쇄</button>

<script>
    $(document).ready(function() {
        var groupId = <?php echo $group_id; ?>;

        $.ajax({
            url: '/mypage/get_group_members',
            type: 'POST',
            data: { group_id: groupId },
            dataType: 'json',
            success: function(response) {
                var qrCodeContainer = $('#qrCodeContainer');
                qrCodeContainer.empty();

                response.forEach(function(member) {
                    var qrCodeItem = $('<div class="qr-code-item"></div>');
                    var qrCodeElement = $('<div class="qr-code-element"></div>');
                    var qrCodeLabel = $('<div class="qr-code-label"></div>');

                    new QRCode(qrCodeElement[0], {
                        text: member.member_idx.toString(),
                        width: 200,
                        height: 200
                    });

                    // qrCodeLabel.text('<span class="area">' + member.area + '</span><br>' + member.member_name);

                    qrCodeItem.append(qrCodeElement);
                    qrCodeItem.append(qrCodeLabel);
                    qrCodeContainer.append(qrCodeItem);
                });
            },
            error: function() {
                alert('회원 목록을 가져오는데 실패했습니다.');
            }
        });

        $('#printBtn').click(function() {
            window.print();
        });
    });
</script>
</body>
</html>
