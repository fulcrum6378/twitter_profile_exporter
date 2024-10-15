// noinspection JSCheckFunctionSignatures,JSUnresolvedReference

function isBlank(value) {
    if (value === null || value === undefined) return true;
    if (typeof value === 'string') return value.trim().length === 0;
}

function changeConfig(query, method, doReload = false) {
    $.ajax({
        url: 'modules/config.php?' + query,
        method: method,
        cache: false,
        dataType: 'text',
        timeout: 10000,
        success: (result/*, textStatus, jqXHR*/) => {
            alert(result)
            if (result === 'Done' || doReload) location.reload()
        },
        error: (jqXHR, textStatus, errorThrown) => {
            alert(errorThrown)
            if (doReload) location.reload()
        }
    })
}

// PUT
$('#put').click(function () {
    let newUser = $('#newUser').val()
    if (isBlank(newUser)) {
        alert('Username is not valid!')
        return
    }
    changeConfig('u=' + newUser, 'PUT')
})

// DELETE
$('.delete').click(function () {
    if (!confirm("Are you sure you want to delete this account from this table? " +
        "(the database and media will NOT be deleted.)")) return
    changeConfig(
        't=' + $(this).parent().parent().attr('data-id'),
        'DELETE', true)
})
