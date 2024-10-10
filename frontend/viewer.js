// noinspection JSCheckFunctionSignatures,JSUnresolvedReference

// INITIAL CONFIGURATIONS
$(document).ready(function () {
    let nmt = '-' + $('figure').height() / 2 + 'px';
    $('header').css('margin-top', nmt)
    $('#actions').css('margin-top', nmt)
})
$('a:not(.page-link):not(.nav-link):not(#link)')
    .addClass('link-body-emphasis link-underline-opacity-0')

// SYNCHRONIZATION
const target = $('#target').val()
let crawler = null

function syncEnded(button) {
    if (crawler === null) return
    crawler.close()
    crawler = null
    button.removeClass('spinning')
    $('#crawlHalt').addClass('disabled')
}

$('#sync, #syncAll').click(function () {
    $(this).addClass('spinning')
    $('#crawler').show()
    crawler = new EventSource('crawler.php?t=' + target +
        '&update_only=' + ($(this).is($('#sync')) ? '1' : '0') + '&sse=1')
    crawler.onmessage = (event) => {
        $('#crawlEvents').append(event.data + '</br>')
        if (event.data === 'DONE') syncEnded($(this))
    }
    $('#crawlOK').click(function () {
        syncEnded($(this))
        $('#crawler').hide()
        location.reload()  // $('#crawlOK, #crawlHalt').click(null)
    })
    $('#crawlHalt').click(() => syncEnded($(this)))
    //crawler.onerror = (err) => alert(err)
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
