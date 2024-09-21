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

function restoreSucceedingGetParams(px) {
    let after = location.search.substring(px)
    if (after.includes('&'))
        after = '&' + after.split('&', 2)[1]
    else
        after = ''
    return after
}

function setGetParam(param, value) { // FIXME
    let qm = location.search.indexOf('?' + param + '=')
    if (qm !== -1) {
        let px = qm + ('?' + param + '=').length
        return location.origin + location.pathname + location.search.substring(0, px) + value +
            restoreSucceedingGetParams(px)
    } else {
        let am = location.search.indexOf('&' + param + '=')
        if (am !== -1) {
            let px = am + ('?' + param + '=').length
            return location.origin + location.pathname + location.search.substring(0, px) + value +
                restoreSucceedingGetParams(px)
        } else if (location.search === '')
            return location.origin + location.pathname + '?' + param + '=' + value
        else
            return location.origin + location.pathname + location.search + '&' + param + '=' + value
    }
}

const PARAM_SECTION = 'section'
const PARAM_PAGE = 'p'

$('#tweets').click(function () {
    location.assign(setGetParam(PARAM_SECTION, '0'))
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
