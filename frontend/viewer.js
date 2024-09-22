// noinspection JSCheckFunctionSignatures,JSUnresolvedReference

// EARLY CONFIGURATIONS
$(document).ready(function () {
    let nmt = '-' + $('figure').height() / 2 + 'px';
    $('header').css('margin-top', nmt)
    $('#actions').css('margin-top', nmt)
})

// SYNCHRONIZATION
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

// PAGINATION
const PARAM_SECTION = 'sect'
const PARAM_PAGE = 'p'
const tabs = ['tweets', 'replies', 'media']
tabs.forEach(function (value, index) {
    $('#' + value).click(function () {
        let params = parseParams()
        params[PARAM_SECTION] = (index + 1).toString()
        delete params[PARAM_PAGE]
        location.assign(location.origin + location.pathname + arrangeParams(params))
    });
})
$('.page-link[href]').click(function () {
    let pAttr = $(this).attr("data-p")
    let params = parseParams()
    params[PARAM_PAGE] = (typeof pAttr === 'undefined' || pAttr === false)
        ? parseInt($(this).html())
        : pAttr
    location.assign(location.origin + location.pathname + arrangeParams(params))
});
