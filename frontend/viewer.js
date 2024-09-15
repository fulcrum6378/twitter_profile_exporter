$(document).ready(function () {
    $('header').css('margin-top', '-' + $('figure').height() / 2 + 'px')
})

function restoreSucceedingGetParams(px) {
    let after = location.search.substring(px)
    if (after.includes('&'))
        after = after.split('&', 2)[1]
    else
        after = ''
    return after
}

function setGetParam(param, value) {
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

$("#tweets").click(function () {
    location.assign(setGetParam('section', '0'))
});
$("#replies").click(function () {
    location.assign(setGetParam('section', '1'))
});
$("#media").click(function () {
    location.assign(setGetParam('section', '2'))
});
