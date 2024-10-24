// noinspection JSCheckFunctionSignatures,JSUnresolvedReference,JSValidateTypes

// INITIAL CONFIGURATIONS
function setHeaderSizes() {
    let nmt = '-' + $('figure').height() / 2 + 'px';
    $('header').css('margin-top', nmt)
    $('#actions').css('margin-top', nmt)

    $('div#header').css('min-height', $('body').width() / 3 + 'px')
}

$(document).ready(setHeaderSizes)
window.onresize = setHeaderSizes


// CRAWLER
let crawler = null
let crawled = false
$('#crawlForm [name=sect]').change(function () {
    if ($(this).hasClass('crwSc')) $('#crwSearch').removeAttr("disabled")
    else $('#crwSearch').attr("disabled", true)
    if ($(this).hasClass('crwUnsorted')) $('#crwUpdateOnly').attr("disabled", true)
    else $('#crwUpdateOnly').removeAttr("disabled")
})
$('#crawl').click(function () {
    $('#crawlCancel').text('Cancel')
    $('#crawlEvents').empty()
    $('#crawlCancel').show()
    $('#crawlHalt').hide()
    $('#crawlGo').show()
    $('#crawler').fadeIn()
})
$('#crawlGo').click(function () {
    //alert($('#crawlForm').serialize()); return
    $('#crawlCancel').hide()
    $('#crawlGo').hide()
    $('#crawlHalt').show()
    $('#crawlForm').hide()
    $(this).addClass('spinning')

    crawler = new EventSource('crawler.php?' + $('#crawlForm').serialize() + '&sse=1')
    crawler.onmessage = (event) => {
        $('#crawlEvents').append(event.data + '</br>')
        $('#crawler .modal-body').scrollTop($('#crawler .modal-body')[0].scrollHeight)
        if (event.data === 'DONE') crawlEnded($(this))
    }
    crawler.onerror = (event) => {
        $('#crawlEvents').append(event.data + '</br>')
        $('#crawler .modal-body').scrollTop($('#crawler .modal-body')[0].scrollHeight)
        crawlEnded($(this))
    }
    crawled = true
})
$('#crawlHalt').click(() => crawlEnded($(this)))
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
    $('#crawlCancel').text('OK')
    $('#crawlCancel').show()
    $('#crawlHalt').hide()
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
