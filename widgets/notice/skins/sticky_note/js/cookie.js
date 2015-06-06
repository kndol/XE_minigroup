function setCookie(key, value, expires, path, domain)
{
	if (value === null || value === 'undefined') {
		expires = -1;
	}
	
	if (typeof expires === 'number') {
		var days = expires, t = expires = new Date();
		t.setDate(t.getDate() + days);
	}
	
	value = String(value);
	
	document.cookie = encodeURIComponent(key) + "=" + encodeURIComponent(value) + "; expires=" + expires.toGMTString() + (path ? "; path=" + path : "") + (domain ? "; domain=" + domain : "") + ";"
}

function getCookie(key)
{
	var value=null, search=key+"=";
	if (document.cookie.length > 0) {
		var offset = document.cookie.indexOf(search);
		if (offset != -1) {
			offset += search.length;
			var end = document.cookie.indexOf(";", offset);
			if (end == -1) end = document.cookie.length;
			value = unescape(document.cookie.substring(offset, end));
		}
	}
	return value;
}
