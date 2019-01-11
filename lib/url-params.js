
// Taken from https://stackoverflow.com/questions/901115/how-can-i-get-query-string-values-in-javascript
function getUrlParameter(name, url) {
    if (!url) {
        url = window.location.href;
    }

    name = name.replace(/[\[\]]/g, '\\$&');

    var regex = new RegExp('[?&]' + name + '(=([^&#]*)|&|#|$)');
    var results = regex.exec(url);

    if (!results) {
        return null;
    }

    if (!results[2]) {
        return '';
    }

    return decodeURIComponent(results[2].replace(/\+/g, ' '));
}

function getBooleanUrlParameter(name, url)
{
    var paramValue = getUrlParameter(name, url);
    if (!paramValue) {
        return false;
    }
    paramValue = paramValue.toLowerCase();
    return paramValue === 'true' || paramValue === '1' || paramValue === 'on';
}
