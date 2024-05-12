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

        body {
            margin: 0;
            padding: 0;
        }

        #qrCodeContainer {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            grid-gap: 10px;
            padding: 10px;
        }

        .qr-code-item {
            width: 25mm;
            height: 30mm;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            border: 1px solid #ccc;
            page-break-inside: avoid;
        }

        .qr-code-element img {
            width: 100%;
            height: auto;
        }

        .qr-code-label {
            font-size: 8pt;
            text-align: center;
            margin-top: 5px;
        }

        @media print {
            #printBtn {
                display: none;
            }

            #qrCodeContainer {
                grid-template-columns: repeat(5, 1fr);
                grid-gap: 0;
                padding: 0;
            }

            .qr-code-item {
                width: 25mm;
                height: 30mm;
                border: none;
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

                    qrCodeLabel.text(member.area + ' ' + member.member_name);

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