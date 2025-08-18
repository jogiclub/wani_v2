


<div class="modal fade" id="qrPrintModal" tabindex="-1" aria-labelledby="qrPrintModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">

            <div class="modal-body">
                <div id="qrCodeContainerModal"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="printQR">인쇄</button>
            </div>
        </div>
    </div>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js" integrity="sha512-CNgIRecGo7nphbeZ04Sc13ka07paqdeTu0WR1IM4kNcpmBAUSHSQX0FslNhTDadL4O5SAGapGt4FodqL8My0mA==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.js" integrity="sha512-is1ls2rgwpFZyixqKFEExPHVUUL+pPkBEPw47s/6NDQ4n1m6T/ySeDW3p54jp45z2EJ0RSOgilqee1WhtelXfA==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script>
    $(document).ready(function() {
        $('#qrPrintModal').on('show.bs.modal', function(event) {
            var button = $(event.relatedTarget);
            var groupId = button.data('group-id');

            $.ajax({
                url: '/mypage/get_group_members',
                type: 'POST',
                data: { group_id: groupId },
                dataType: 'json',
                success: function(response) {
                    var qrCodeContainer = $('#qrCodeContainerModal');
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

                        // qrCodeLabel.text(member.area + ' ' + member.member_name);
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
        });

        $('#printQR').click(function() {
            var printContents = $('#qrPrintModal .modal-body').html();
            var originalContents = $('body').html();
            $('body').html(printContents);
            window.print();
            $('body').html(originalContents);
            $('#qrPrintModal').modal('hide');
        });
    });
</script>



<div id="qrcode"></div>
<script type="text/javascript">
    new QRCode(document.getElementById("qrcode"), "http://jindo.dev.naver.com/collie");
</script>
