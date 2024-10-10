// noinspection JSCheckFunctionSignatures,JSUnresolvedReference

function isBlank(value) {
    if (value === null || value === undefined) return true;
    if (typeof value === 'string') return value.trim().length === 0;
}

function changeConfig(query, method, doAlert = true, doReload = true) {
    $.ajax({
        url: 'config.php?' + query,
        method: method,
        cache: false,
        dataType: 'text',
        timeout: 5000,
        success: (result/*, textStatus, jqXHR*/) => {
            if (doAlert) alert(result)
            if (doReload) location.reload()
        },
        error: (jqXHR, textStatus, errorThrown) => {
            if (doAlert) alert(errorThrown)
            if (doReload) location.reload()
        }
    })
}

// INSERT
$('#put').click(function () {
    let newId = $('#newId').val(), newName = $('#newName').val()
    if (isBlank(newId) || isNaN(newId) || isNaN(parseFloat(newId))) {
        alert('Twitter ID is not valid!')
        return
    }
    if (isBlank(newName)) {
        alert('Person Name is not valid!')
        return
    }
    changeConfig('id=' + newId + '&name=' + newName, 'PUT')
})

// UPDATE
$('.name').on('blur', function () {
    changeConfig(
        'id=' + $(this).parent().parent().find(">:first-child").text() +
        '&name=' + $(this).val(),
        'PUT',
        false, false)
})

// DELETE
$('.delete').click(function () {
    if (!confirm("Are you sure you want to delete this account from this table? " +
        "(the database and media will NOT be deleted.)")) return
    changeConfig(
        't=' + $(this).parent().parent().find(">:first-child").text(),
        'DELETE')
})
