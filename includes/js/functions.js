function lpad(str, l, chr) {
	if (typeof l === 'undefined') l = 2;
	if (typeof chr === 'undefined') chr = '0';
	var ret = str.toString();
	while (ret.length < l) ret = chr + ret;
	return ret;
}

function LightDate() {
    return this;
}
LightDate.LANG = 'EN';
LightDate.__LANG = {
    'EN': {
        'date-short-mos' : ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
        'date-short-wkd' : ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
        'date-long-mos' : ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
        'date-long-wkd' : ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
        'date-chrreg' : {
            'Y':/^\d{4,}/, 'm':/^\d{2}/, 'd':/^\d{2}/, 'H':/^\d{2}/, 'i':/^\d{2}/, 's':/^\d{2}/, 'j':/^\d{1,2}/, 'S':/^st|nd|rd|th/,
            'D':/^Sun|Mon|Tue|Wed|Thu|Fri|Sat/,
            'M':/^Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec/,
            'l':/^Sunday|Monday|Tuesday|Wednesday|Thursday|Friday|Saturday/,
            'F':/^January|February|March|April|May|June|July|August|September|October|November|December/
        }
    }
};

LightDate.ensure_date = function(d) {
	if (typeof d === 'string') { return new Date(parseInt(d, 10));
	} else if (typeof d === 'number' && !isNaN(d.valueOf())) { return new Date(d);
	} else if (typeof d === 'undefined') {
	   return new Date();
	} else if (d instanceof Date)
	   return d;
	
	return new Date(d);
};

LightDate.date = function(format, ts) {
    ts = LightDate.ensure_date(ts);
	
	var ret = '';
	for (var i = 0; i<format.length; i++) {
		if (format[i] == '\\') ret += format[(i++) + 1];
		else if (format[i] == 'Y') ret += lpad(ts.getFullYear(), 4, '0');
		else if (format[i] == 'm') ret += lpad(ts.getMonth()+1, 2, '0');
		else if (format[i] == 'd') ret += lpad(ts.getDate(), 2, '0');
		else if (format[i] == 'H') ret += lpad(ts.getHours(), 2, '0');
		else if (format[i] == 'i') ret += lpad(ts.getMinutes(), 2, '0');
		else if (format[i] == 's') ret += lpad(ts.getSeconds(), 2, '0');
		else if (format[i] == 'D') ret += LightDate['__LANG'][LightDate.LANG]['date-short-wkd'][ts.getDay()];
		else if (format[i] == 'M') ret += LightDate['__LANG'][LightDate.LANG]['date-short-mos'][ts.getMonth()];
		else if (format[i] == 'l') ret += LightDate['__LANG'][LightDate.LANG]['date-long-wkd'][ts.getDay()];
		else if (format[i] == 'F') ret += LightDate['__LANG'][LightDate.LANG]['date-long-mos'][ts.getMonth()];
		else if (format[i] == 'j') ret += ts.getDate();
		else if (format[i] == 'S') {
			var tday = ts.getDate();
			if (tday == 1 || tday == 21 || tday == 31) ret += 'st';
			else if (tday == 2 || tday == 22) ret += 'nd';
			else if (tday == 3 || tday == 23) ret += 'rd';
			else { ret += 'th'; }
		} else ret += format[i];
	}
	
	return ret;
};

LightDate.__do_parse_match = function(str, off, format) {
	var match = str.substr(off).match(LightDate['__LANG'][LightDate.LANG]['date-chrreg'][format]);
	if (match !== null) return match[0];
	return false;
};

LightDate.parse_date_format = function(format, datestr) {
	var now = new Date(), sp = 0, mat = 0;
	var retArr = [now.getFullYear(), now.getMonth(), now.getDate(), now.getHours(), now.getMinutes(), now.getSeconds(), now.getMilliseconds()];
	
	for (var i=0; i<format.length; i++) {
		if (format[i] == '\\') { i++; continue; }
		else if (format[i] == 'Y') { if ((mat = LightDate.__do_parse_match(datestr, sp, format[i])) !== false) { sp+=mat.length; retArr[0] = parseInt(mat,10); } }
		else if (format[i] == 'm') { if ((mat = LightDate.__do_parse_match(datestr, sp, format[i])) !== false) { sp+=mat.length; retArr[1] = parseInt(mat,10)-1; } }
		else if (format[i] == 'd') { if ((mat = LightDate.__do_parse_match(datestr, sp, format[i])) !== false) { sp+=mat.length; retArr[2] = parseInt(mat,10); } }
		else if (format[i] == 'H') { if ((mat = LightDate.__do_parse_match(datestr, sp, format[i])) !== false) { sp+=mat.length; retArr[3] = parseInt(mat,10); } }
		else if (format[i] == 'i') { if ((mat = LightDate.__do_parse_match(datestr, sp, format[i])) !== false) { sp+=mat.length; retArr[4] = parseInt(mat,10); } }
		else if (format[i] == 's') { if ((mat = LightDate.__do_parse_match(datestr, sp, format[i])) !== false) { sp+=mat.length; retArr[5] = parseInt(mat,10); } }
		else if (format[i] == 'D') { if ((mat = LightDate.__do_parse_match(datestr, sp, format[i])) !== false) { sp+=mat.length; } }
		else if (format[i] == 'M') { if ((mat = LightDate.__do_parse_match(datestr, sp, format[i])) !== false) { sp+=mat.length; retArr[1] = LightDate['__LANG'][LightDate.LANG]['date-short-mos'].indexOf(mat); } }
		else if (format[i] == 'l') { if ((mat = LightDate.__do_parse_match(datestr, sp, format[i])) !== false) { sp+=mat.length; } }
		else if (format[i] == 'F') { if ((mat = LightDate.__do_parse_match(datestr, sp, format[i])) !== false) { sp+=mat.length; retArr[1] = LightDate['__LANG'][LightDate.LANG]['date-long-mos'].indexOf(mat); } }
		else if (format[i] == 'j') { if ((mat = LightDate.__do_parse_match(datestr, sp, format[i])) !== false) { sp+=mat.length; retArr[2] = parseInt(mat,10); } }
		else if (format[i] == 'S') { if ((mat = LightDate.__do_parse_match(datestr, sp, format[i])) !== false) { sp+=mat.length; } }
		else { sp += 1; }
	}
	
	return new Date(retArr[0], retArr[1], retArr[2], retArr[3], retArr[4], retArr[5], retArr[6]);
};

LightDate.date_timezone_adjust = function(date) {
    date = LightDate.ensure_date(date);
	return new Date(date.getTime()+(date.getTimezoneOffset()*60*1000));
};

function ordinal_str(n) {
	if (n < 0) {
		return 'negative ' + ordinal_str(Math.abs(n));
	} else if (n <= 20) {
		switch (n) {
			case 0:
				return 'zeroth'; break;
			case 1 :
				return 'first'; break;
			case 2:
				return 'second'; break;
			case 3:
				return 'third'; break;
			case 4:
				return 'fourth'; break;
			case 5:
				return 'fifth'; break;
			case 6:
				return 'sixth'; break;
			case 7:
				return 'seventh'; break;
			case 8:
				return 'eigth'; break;
			case 9:
				return 'ninth'; break;
			case 10:
				return 'tenth'; break;
			case 11:
				return 'eleventh'; break;
			case 12:
				return 'twelfth'; break;
			case 13:
				return 'thirteenth'; break;
			case 14:
				return 'fourteenth'; break;
			case 15:
				return 'fifteenth'; break;
			case 16:
				return 'sixteenth'; break;
			case 17:
				return 'seventeenth'; break;
			case 18:
				return 'eightteenth'; break;
			case 19:
				return 'ninteenth'; break;
			case 20:
				return 'twentieth'; break;
		}
	} else {
		return "Number too large. Tell your local dev to add support for more.";
	}
}

function append_array(a, b) {
	for (var i in b) {
		a[i] = b[i];
	}
	
	return a;
}

function form_arr_to_map(arr) {
	var ret = {};
	
	for (var i in arr) {
		ret[arr[i]['name']] = arr[i]['value'];
	}
	
	return ret;
}

function get_URL_params(url) {
	if (typeof url == "undefined") return null;
	
	var re = /(?:\?|&)([^=&#]+)(?:=?([^&#]*))/g,
		match, params = {},
		decode = function (s) {return decodeURIComponent(s.replace(/\+/g, " "));};
	
	if (typeof url == "undefined") url = document.location.href;
	
	while (match = re.exec(url)) {
		params[decode(match[1])] = decode(match[2]);
	}
	
	return params;
}

function url_to_location_obj(url) {
	var a_el = document.createElement('a');
	a_el.href = url;
	
	return {
		'hash': a_el.hash,
		'host': a_el.host,
		'hostname': a_el.hostname,
		'href': a_el.href,
		'origin': a_el.origin,
		'pathname': a_el.pathname,
		'port': a_el.port,
		'protocol': a_el.protocol,
		'search': a_el.search
	};
}

function map_to_url(map) {
	var str = '', f = true;
	for (i in map)  {
		if (map.hasOwnProperty(i)) {
			str += (f ? '' : '&') + encodeURIComponent(i) + '=' + encodeURIComponent(map[i]);
		}
		f = false;
	}
	return str;
}

function invert_bool(val, t, f) {
	return val === t ? f : t;
}

function obj_size(obj) {
	var c = 0;
	for (var i in obj) {
		if (obj.hasOwnProperty(i)) {
			c++;
		}
	}
	
	return c;
}

function array_indexOf(arr, el) {
	for (var i = (arguments[2] || 0); i < arr.length; i++) {
		if (arr[i] == el) {
			return i;
		}
	}
	
	return -1;
}

function array_lastIndexOf(arr, el) {
	for (var i = (arguments[2] || arr.length); i >= 0; i--) {
		if (arr[i] == el) {
			return i;
		}
	}
	
	return -1;
}

function array_map(arr, callback) {
    var ret = [];
    for (var i in arr) {
        ret[i] = callback.apply(arr[i], [i, arr[i]]);
    }
    return ret;
}

function array_filter(arr, callback) {
    var ret = [];
    for (var i in arr) {
    	if (callback.apply(arr[i], [i, arr[i]])) {
        	ret.push(arr[i]);
        }
    }
    return ret;
}

function path_split(path) {
	return array_filter(path.split('/'), function(i, el) { return el.length > 0; });
}
