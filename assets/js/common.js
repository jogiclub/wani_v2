'use strict'


function go_url(url) {
    location.href = url;
}
function open_url(url) {
    window.open(url, "_blank");
}


function heyToast(message, header) {
    $('.toast-header strong').text(header);
    $('.toast-body').text(message);
    $('#liveToast').toast('show');
}