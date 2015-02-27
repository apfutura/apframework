function htmlToText(text) {	
	return $('<div />').html(text).text();
}

function isNumber(n) {
	  return !isNaN(parseFloat(n)) && isFinite(n);
}

function getQueryStringParameter(name){
	var qry = unescape(window.location.href);

    if(typeof qry !== undefined && qry !== ""){
        var keyValueArray = qry.split("&");
        for ( var i = 0; i < keyValueArray.length; i++) {
            if(keyValueArray[i].indexOf(name)>-1){
                return decodeURI(keyValueArray[i].split("=")[1]);
            }
        }
    }
    return "";
}