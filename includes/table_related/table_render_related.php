<?php

function IEML_render_tables($info, $exp_des_call = NULL, $filter_call = NULL) {
    ob_start();
    $heads = $info['headers'];
    $body = $info['body'];
    $hor_tally = array_fill(0, max(1, $info['hor_header_depth']), array(0, 0));
    
    echo '<table class="relation"><tbody>';
    echo '<tr>';
    
    if ($info['hor_header_depth'] > 0) {
        echo '<td class="empty_cell" rowspan="'.($info['ver_header_depth'] + 1).'" '.($info['hor_header_depth'] > 1 ? 'colspan="'.$info['hor_header_depth'].'"' : '').'></td>';
    }
    
    echo '<td'.($info['length'] > 1 ? ' colspan="'.$info['length'].'"' : '').'>';
    if ($exp_des_call != NULL) echo call_user_func($exp_des_call, $info['top']);
    else echo '<strong class="table_title"><'.$info['top'].'</strong>';
    
    echo '</td>';
    echo '</tr>';
    
    for ($i=count($heads[1])-1; $i>=0; $i--) {
        echo '<tr>';
        
        for ($j=0; $j<count($heads[1][$i]); $j++) {
            $el_ref = $heads[1][$i][$j][0];
            $l_count = $heads[1][$i][$j][1];
            
            if (isset($el_ref)) {
            	$cur_class = '';
	            if ($filter_call != NULL && !(call_user_func($filter_call, $el_ref))) {
	            	$cur_class .= ' hide nonExistentCell';
	            }
	            
                echo '<td'.($l_count > 1 ? ' colspan="'.$l_count.'"':'').($cur_class != '' ? ' class="'.$cur_class.'"' : '').'>';
                
                if ($exp_des_call != NULL) echo call_user_func($exp_des_call, $el_ref);
                else echo '<strong class="table_ver_title">'.$el_ref.'</strong>';
                
                echo '</td>';
            }
        }
        echo '</tr>';
    }
    
    for ($r=0; $r<count($body); $r++) {
        echo '<tr>';
        for ($h = count($heads[0]) - 1; $h >= 0; $h--) {
            if ($hor_tally[$h][0] == 0) {
                $h_count = $heads[0][$h][$hor_tally[$h][1]][1];
                $el_ref = $heads[0][$h][$hor_tally[$h][1]][0];
                
                if (isset($el_ref)) {
	            	$cur_class = '';
		            if ($filter_call != NULL && !(call_user_func($filter_call, $el_ref))) {
		            	$cur_class .= ' hide nonExistentCell';
		            }
		            
                    echo '<td'.($h_count > 1 ? ' rowspan="'.$h_count.'"':'').($cur_class != '' ? ' class="'.$cur_class.'"' : '').'>';
                    
                    if ($exp_des_call != NULL) echo call_user_func($exp_des_call, $el_ref);
                    else echo '<strong class="table_hor_title">'.$el_ref.'</strong>';
                    
                    echo '</td>';
                }
            }
            
            $hor_tally[$h][0]++;
            if ($hor_tally[$h][0] >= $heads[0][$h][$hor_tally[$h][1]][1]) {
                $hor_tally[$h][0] = 0;
                $hor_tally[$h][1]++;
            }
        }
        for ($c=0; $c<$info['length']; $c++) {
            $el_ref = $body[$r][$c];
            if (isset($el_ref)) {
                echo '<td'.($filter_call != NULL && !(call_user_func($filter_call, $el_ref)) ? ' class="hide nonExistentCell"' : '').'>';
                
                if ($exp_des_call != NULL) echo call_user_func($exp_des_call, $el_ref);
                else echo $el_ref;
                
                echo '</td>';
            }
        }
        echo '</tr>';
    }

    echo '</tbody></table>';
    return ob_get_clean();
}

function IEML_postprocess_table($tab_info, $callback) {
    for ($i=0; $i<count($tab_info['headers']); $i++) {
        for ($j=0; $j<count($tab_info['headers'][$i]); $j++) {
            for ($k=0; $k<count($tab_info['headers'][$i][$j]); $k++) {
                $tab_info['headers'][$i][$j][$k][0] = call_user_func($callback, $tab_info['headers'][$i][$j][$k][0]);
            }
        }
    }
    
    for ($i=0; $i<count($tab_info['body']); $i++) {
        for ($j=0; $j<count($tab_info['body'][$i]); $j++) {
            $tab_info['body'][$i][$j] = call_user_func($callback, $tab_info['body'][$i][$j]);
        }
    }
    
    return $tab_info;
}

?>
