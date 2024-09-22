
function setGetParams(params) {
    let search = location.search
    for (let [param, value] of Object.entries(params))
        search = setGetParam(search, param, value)
    return location.origin + location.pathname + search
}

function setGetParam(search, param, value) {
    let qm = search.indexOf('?' + param + '=')
    if (qm !== -1) {
        let px = qm + ('?' + param + '=').length
        return search.substring(0, px) + value + restoreSucceedingGetParams(search, px)
    } else {
        let am = search.indexOf('&' + param + '=')
        if (am !== -1) {
            let px = am + ('?' + param + '=').length
            return search.substring(0, px) + value + restoreSucceedingGetParams(search, px)
        } else if (search === '')
            return '?' + param + '=' + value
        else
            return search + '&' + param + '=' + value
    }
}

function restoreSucceedingGetParams(search, px) {
    let after = search.substring(px)
    if (after.includes('&'))
        after = '&' + after.split('&', 2)[1]
    else
        after = ''
    return after
}
