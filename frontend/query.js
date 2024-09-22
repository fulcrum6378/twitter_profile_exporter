function parseParams(search = location.search) {
    let o = {}
    if (search.length !== 0)
        search.substring(1).split('&').forEach(function (value) {
            let pair = value.split('=')
            if (pair.length !== 2) return
            o[pair[0]] = pair[1]
        })
    return o
}

function arrangeParams(params) {
    let keys = Object.keys(params)
    if (keys.length === 0) return
    params = keys.sort().reduce(
        (obj, key) => {
            obj[key] = params[key];
            return obj;
        }, {})

    let search = ''
    let first = true
    for (let [key, value] of Object.entries(params)) {
        search += (first ? '?' : '&') + key + '=' + value
        first = false
    }
    return search
}
