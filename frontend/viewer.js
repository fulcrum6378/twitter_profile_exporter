// noinspection JSCheckFunctionSignatures,JSUnresolvedReference

// INITIAL CONFIGURATIONS
$(document).ready(function () {
    let nmt = '-' + $('figure').height() / 2 + 'px';
    $('header').css('margin-top', nmt)
    $('#actions').css('margin-top', nmt)
})
$('a:not(.page-link):not(.nav-link):not(#link)')
    .addClass('link-body-emphasis link-underline-opacity-0')


// CRAWLER
let crawler = null
let crawled = false
$('#crawlForm [name=sect]').change(function () {
    if ($(this).hasClass('crwSc')) $('#crwSearch').removeAttr("disabled")
    else $('#crwSearch').attr("disabled", true)
})
$('#crawl').click(function () {
    $('#crawlEvents').empty()
    $('#crawlHalt').hide()
    $('#crawlGo').show()
    $('#crawler').fadeIn()
})
$('#crawlGo').click(function () {
    $('#crawlGo').hide()
    $('#crawlForm').hide()
    $('#crawlHalt').show()
    $(this).addClass('spinning')

    crawler = new EventSource('crawler.php?t=' + target +
        '&update_only=' + ($(this).is($('#sync')) ? '1' : '0') + '&sse=1')
    crawler.onmessage = (event) => {
        $('#crawlEvents').append(event.data + '</br>')
        if (event.data === 'DONE') crawlEnded($(this))
    }
    //crawler.onerror = (err) => alert(err)
    crawled = true
})
$('#crawlHalt').click(() => {
    crawlEnded($(this))
    $('#crawlHalt').hide()
    $('#crawlOK').show()
})
$('#crawlCancel').click(function () {
    crawlEnded($(this))
    $('#crawler').fadeOut()
    if (crawled) location.reload()
})

function crawlEnded(button) {
    if (crawler === null) return
    crawler.close()
    crawler = null
    button.removeClass('spinning')
    $('#crawlHalt').addClass('disabled')
}


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
