// noinspection JSCheckFunctionSignatures,JSUnresolvedReference

// INITIAL CONFIGURATIONS
$(document).ready(function () {
    let nmt = '-' + $('figure').height() / 2 + 'px';
    $('header').css('margin-top', nmt)
    $('#actions').css('margin-top', nmt)
})

// SYNCHRONIZATION
let crawler = null

function syncEnded() {
    if (crawler === null) return
    crawler.close()
    crawler = null
    $('#sync').removeClass('spinning')
    $('#crawlHalt').addClass('disabled')
}

$('#sync').click(function () {
    $(this).addClass('spinning')
    $('#crawler').show()
    crawler = new EventSource('crawler.php?t=' + $(this).attr('data-t'))
    crawler.onmessage = (event) => {
        $('#crawlEvents').append(event.data + '</br>')
        if (event.data === 'DONE') syncEnded()
    }
    $('#crawlOK').click(function () {
        syncEnded()
        $('#crawler').hide()
        location.reload()  // $('#crawlOK, #crawlHalt').click(null)
    })
    $('#crawlHalt').click(() => syncEnded())
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
