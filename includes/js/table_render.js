'use strict';
	
function array_fill(len, val) {
	var ret = new Array(len);
	
	for (var i=0; i<len; i++) {
		if (typeof val == 'function') {
			ret[i] = val(i);
		} else {
			ret[i] = val;
		}
	}
	
	return ret;
}

function IEML_render_table_header(heads, render_callback) {
	var out = '';
	
	for (var i = heads[1].length-1; i>=0; i--) {
        out += '<tr>';
        
        for (var j=0; j<heads[1][i].length; j++) {
            var l_count = heads[1][i][j][1];
            
            out += '<th colspan="' + Math.max(1, l_count) + '">' + render_callback(heads[1][i][j][0]) + '</th>';
        }
        
        out += '</tr>';
    }
    
    return out;
}

function IEML_render_table_body(info, render_callback) {
	var body = info['body'], heads = info['headers'], out = '',
		hor_tally = array_fill(Math.max(1, info['hor_header_depth']), function() { return [0, 0] });
	
	for (var r=0; r<body.length; r++) {
        out += '<tr>';
        
        if (heads) {
	        for (var h = heads[0].length - 1; h >= 0; h--) {
	            if (hor_tally[h][0] == 0) {
	                out += '<th rowspan="' + Math.max(1, heads[0][h][hor_tally[h][1]][1]) + '">'
		                + render_callback(heads[0][h][hor_tally[h][1]][0])
		                + '</th>';
	            }
	            
	            ++hor_tally[h][0];
	            if (hor_tally[h][0] >= heads[0][h][hor_tally[h][1]][1]) {
	                hor_tally[h][0] = 0;
	                ++hor_tally[h][1];
	            }
	        }
        }
        
        for (var c=0; c<body[r].length; c++) {
            out += '<td>'
            	+ render_callback(body[r][c])
            	+ '</td>';
        }
        
        out += '</tr>';
    }
    
    return out;
}

function IEML_render_table(info, render_callback) {
	var out = '';
    
    out += '<table class="relation"><tbody>'
    
    if (info['hor_header_depth'] > 0) {
        out += '<tr>'
        	+ '<th class="empty_cell" rowspan="' + (info['ver_header_depth'] + 1) + '" '
        	+ (info['hor_header_depth'] > 1 ? 'colspan="' + info['hor_header_depth'] + '"' : '') + '>&nbsp;</th>'
        	+ '</tr>';
    }
    
    if (info['headers']) {
    	out += IEML_render_table_header(info['headers'], render_callback);
    }
    
    out += IEML_render_table_body(info, render_callback);

    out += '</tbody></table>';
    
    return out;
}
