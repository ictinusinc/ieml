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

function IEML_render_table_header(heads) {
	var out = '';
	
	for (var i = heads[1].length-1; i>=0; i--) {
        out += '<tr>';
        
        for (var j=0; j<heads[1][i].length; j++) {
            var l_count = heads[1][i][j][1];
            
            out += '<td colspan="' + Math.max(1, l_count) + '">' + heads[1][i][j][0] + '</td>';
        }
        
        out += '</tr>';
    }
    
    return out;
}

function IEML_render_table_body(info) {
	var body = info['body'], heads = info['headers'], out = '',
		hor_tally = array_fill(Math.max(1, info['hor_header_depth']), function() { return [0, 0] });
	
	for (var r=0; r<body.length; r++) {
        out += '<tr>';
        
        if (heads) {
	        for (var h = heads[0].length - 1; h >= 0; h--) {
	            if (hor_tally[h][0] == 0) {
	                out += '<td rowspan="' + Math.max(1, heads[0][h][hor_tally[h][1]][1]) + '">';
	                
	                out += heads[0][h][hor_tally[h][1]][0];
	                
	                out += '</td>';
	            }
	            
	            ++hor_tally[h][0];
	            if (hor_tally[h][0] >= heads[0][h][hor_tally[h][1]][1]) {
	                hor_tally[h][0] = 0;
	                ++hor_tally[h][1];
	            }
	        }
        }
        
        for (var c=0; c<body[r].length; c++) {
            out += '<td>';
            
            out += body[r][c];
            
            out += '</td>';
        }
        
        out += '</tr>';
    }
    
    return out;
}

function IEML_render_table(info) {
	var out = '';
    
    out += '<table class="relation"><tbody>'
    	+ '<tr>';
    
    if (info['hor_header_depth'] > 0) {
        out += '<td class="empty_cell" rowspan="' + (info['ver_header_depth'] + 1) + '" ' + (info['hor_header_depth'] > 1 ? 'colspan="' + info['hor_header_depth'] + '"' : '') + '></td>';
    }
    
    out += '<td' + (info['length'] > 1 ? ' colspan="' + info['length'] + '"' : '') + '>' + info['top'] + '<strong class="table_title"><' + info['top'] + '</strong>' + '</td>'
    	+ '</tr>';
    
    if (info['headers']) {
    	out += IEML_render_table_header(info['headers']);
    }
    
    out += IEML_render_table_body(info);

    out += '</tbody></table>';
    
    return out;
}

function IEML_render_only_body(info) {
    return '<table class="relation"><tbody>'
    
    	+ IEML_render_table_body(info)

    	+ '</tbody></table>';
}
