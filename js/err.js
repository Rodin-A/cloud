function ErrMsg(msg) {
    $('#err-content').append('<hr><p>'+msg+'</p>');
    $('#err').css('visibility','visible');
    var err = getCookie('err');
    if (err === undefined) {
        err = [msg];
    } else {
        err = JSON.parse(err);
        err.push(msg);
    }
    setCookie('err', JSON.stringify(err));
}

function CloseErr() {
    $('#err').css('visibility','hidden');
    $('#err-content p').remove();
    deleteCookie('err');
}

$(document).ready(function(){
    $('#err h4 a').on("click", CloseErr);
    var err = getCookie('err');
    if (err !== undefined) {
        err = JSON.parse(err);
        err.forEach(function(entry) {
            $('#err-content').append('<hr><p>'+entry+'</p>');
        });
        $('#err').css('visibility','visible');
        deleteCookie('err');
    }
});

function getCookie(name) {
    var matches = document.cookie.match(new RegExp(
        "(?:^|; )" + name.replace(/([\.$?*|{}\(\)\[\]\\\/\+^])/g, '\\$1') + "=([^;]*)"
    ));
    return matches ? decodeURIComponent(matches[1]) : undefined;
}

function setCookie(name, value, options) {

    options = options || {path: '/'};

    if (options.expires instanceof Date) {
        options.expires = options.expires.toUTCString();
    }

    if(options.path === undefined) {
        options.path = '/';
    }

    var updatedCookie = encodeURIComponent(name) + "=" + encodeURIComponent(value);

    for (var optionKey in options) {
        updatedCookie += "; " + optionKey;
        var optionValue = options[optionKey];
        if (optionValue !== true) {
            updatedCookie += "=" + optionValue;
        }
    }

    document.cookie = updatedCookie;
}

function deleteCookie(name) {
    setCookie(name, "", {
        'max-age': -1
    })
}