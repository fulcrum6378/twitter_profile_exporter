// noinspection JSUnresolvedReference

import './query.js'

$(document).ready(function () {
    let nmt = '-' + $('figure').height() / 2 + 'px';
    $('header').css('margin-top', nmt)
    $('#actions').css('margin-top', nmt)
})

let syncing = false
$('#sync').click(function () {
    if (syncing) return
    syncing = true
    $(this).addClass('spinning')
    $.ajax({
        url: 'crawler.php?t=' + $(this).attr('data-target'),
        cache: false,
        dataType: 'text',
        timeout: 5 * 60 * 1000,
        success: function (result/*, textStatus, jqXHR*/) {
            alert(result)
            syncing = false
            $('#sync').removeClass('spinning')
        },
        error: function (jqXHR, textStatus, errorThrown) {
            alert(errorThrown)
            syncing = false
            $('#sync').removeClass('spinning')
        }
    });

})

const PARAM_SECTION = 'sect'
const PARAM_PAGE = 'p'

$('#tweets').click(function () {
    let search = location.search
    search = setGetParam(search, PARAM_SECTION, '0')
    setGetParams()
    location.assign(location.origin + location.pathname + search)
});
$('#replies').click(function () {
    location.assign(setGetParam(PARAM_SECTION, '1'))
});
$('#media').click(function () {
    location.assign(setGetParam(PARAM_SECTION, '2'))
});
$('.page-link[href]').click(function () {
    let pAttr = $(this).attr("data-p");
    if (typeof pAttr === 'undefined' || pAttr === false)
        location.assign(setGetParam(PARAM_PAGE, parseInt($(this).html())))
    else
        location.assign(setGetParam(PARAM_PAGE, pAttr))
});
