<?php

function IEML_gen_var($str, &$var_exp, &$vars) {
    $pos_res = loc_first_occ($str, $vars);
    if (FALSE !== $pos_res) {
        list($pos, $ind) = $pos_res;
        $ret = array();
        $sub = IEML_gen_var(substr($str, $pos+1), $var_exp, $vars);
        if (FALSE !== $sub) {
            foreach ($var_exp[$vars[$ind]] as $var) {
                for($i=0; $i<count($sub); $i++)
                    array_push($ret, substr($str, 0, $pos).$var.$sub[$i]);
            }
        } else {
            foreach ($var_exp[$vars[$ind]] as $var) {
                $subzero = str_splice($str, $pos, 1, $var);
                $sub_subzero = IEML_gen_var($subzero, $var_exp, $vars);
                
                if (FALSE !== $sub_subzero) {
                    for($i=0; $i<count($sub_subzero); $i++)
                        array_push($ret, $sub_subzero[$i]);
                } else {
                    array_push($ret, $subzero);
                }
            }
        }
        return $ret;
    }
    return FALSE;
}

function IEML_sub_gen_header($str, $splice_str, $splice_off, &$var_exp, &$prim) {
    $pos_res = loc_first_occ($str, $prim['vars']);
    //echo pre_dump($str, $pos_res);
    
    if (FALSE !== $pos_res) {
        list($pos, $ind) = $pos_res;
        $ret = array('head' => array(), 'rest' => array(), 'body' => array());
        
        foreach ($var_exp[$prim['vars'][$ind]] as $var) {
            $str[$pos] = $var;
            $spliced = str_splice($splice_str, $splice_off, strlen($str), $str);
            $span = 0;
            
            $sub = IEML_sub_gen_header($str, $splice_str, $splice_off, $var_exp, $prim);
            if (FALSE !== $sub) {
                array_push($ret['rest'], $sub);
                if (array_key_exists('head', $sub)) {
                    for ($i=0; $i<count($sub['head']); $i++)
                        $span += $sub['head'][$i][1];
                }
            } else {
                $gen_var = IEML_gen_var($spliced, $var_exp, $prim['vars']);
                if (FALSE !== $gen_var)
                    array_push($ret['body'], $gen_var);
            }
            
            array_push($ret['head'], array($spliced, max(1, $span)));
        }
        
        $str[$pos] = $prim['vars'][$ind];
        
        if (count($ret['rest']) == 0) unset($ret['rest']);
        if (count($ret['body']) == 0) unset($ret['body']);
        
        return $ret;
    }
    return FALSE;
}

function IEML_gen_header($str, &$lev_reg, &$lev_syms, &$var_exp, &$prim, $splice_str = "", $splice_off = 0) {
    $mat = NULL;
    $lev = IEML_get_lev($str, $lev_syms);
    
    if ($splice_str === "") {
        $splice_str = $str;
        $splice_off = 0;
    }
    
    $num_var = loc_num_occ($splice_str, $prim['vars']);
    
    if ($num_var == 1) {
        return array(array('body' => array_2d_transpose(array(IEML_gen_var($splice_str, $var_exp, $prim['vars'])))));
    } else if ($num_var >= 2) {
        if (FALSE !== preg_match($lev_reg[$lev], $str, $mat, PREG_OFFSET_CAPTURE) && count($mat) > 0) {
            $dim = count($mat) - 1;
            $pre_c = array(); $pre_out = array(); $pre_len = 0;
            for ($i=0; $i<$dim; $i++) {
                $tsub = loc_first_occ($mat[$i+1][0], $prim['vars']);
                if (FALSE !== $tsub) {
                    array_push($pre_c, $i);
                } else {
                    array_push($pre_out, $i);
                }
            }
            $pre_len = count($pre_c); //count how many things need to be varied in the string
            
            if ($dim == 1) {
                return IEML_gen_header($mat[1][0], $lev_reg, $lev_syms, $var_exp, $prim, $splice_str, $splice_off + $mat[1][1]);
            } else {
                
                if ($pre_len != $dim) { //if the number of things to be varied doesnt match the number of (parts of the current expression)
                    if ($pre_len == 1) { //if there's only one thing to vary
                    	//call this fcn again, pass proper splice str along
                        return IEML_gen_header($mat[$pre_c[0]+1][0], $lev_reg, $lev_syms, $var_exp, $prim, $splice_str, $splice_off + $mat[$pre_c[0]+1][1]);
                    } 
                }
                
                $ret = array();
                
                for ($i=0; $i<$dim; $i++) {
                    if (!in_array($i, $pre_out)) {
                        $sub = IEML_sub_gen_header($mat[$i+1][0], $splice_str, $splice_off + $mat[$i+1][1] , $var_exp, $prim);
                        //echo pre_dump($sub);
                        if (FALSE !== $sub) {
                            array_push($ret, $sub);
                        }
                    }
                }
                
                return $ret;
            }
        }
    }
    
    return NULL;
}

function IEML_postproc_tables(&$table, &$low_map) {
    $ret = array();
    foreach ($table as $key => $branch) {
        if (is_array($branch)) {
            $ret[$key] = IEML_postproc_tables($branch, $low_map);
        } else if (is_string($branch)) {
            $ret[$key] = str_trans_preg($branch, $low_map);
        } else {
            $ret[$key] = $branch;
        }
    }
    return $ret;
}

function IEML_table_collect_headers($tree) {
    if (is_array($tree) && array_key_exists(0, $tree)) {
        $heads = array();
        $sub_heads = array();
        
        for ($i=0; $i<count($tree); $i++) {
            if (array_key_exists('head', $tree[$i])) {
                $heads = array_merge($heads, $tree[$i]['head']);
                if (array_key_exists('rest', $tree[$i])) {
                    $sub = IEML_table_collect_headers($tree[$i]['rest']);
                    if (FALSE !== $sub) {
                        if (count($sub_heads) == 0) {
                            $sub_heads = $sub;
                        } else {
                            for ($j=0; $j<count($sub); $j++)
                                $sub_heads[$j] = array_merge($sub_heads[$j], $sub[$j]);
                        }
                    }
                }
            }
        }
        
        if (count($heads)>0) array_push($sub_heads, $heads);
        return count($sub_heads) > 0 ? $sub_heads : FALSE;
    }
    
    return FALSE;
}

function IEML_table_collect_body($tree, $ret = array()) {
    if (is_array($tree)) {
        if (array_key_exists('body', $tree)) {
            for($i=0; $i<count($tree['body']); $i++)
               array_push($ret, $tree['body'][$i]);
        } else {
            foreach($tree as $branch) {
                $ret = IEML_table_collect_body($branch, $ret);
            }
        }
    }
    return $ret;
}

function IEML_coll_info($tree, $top) {
    $heads = array(IEML_table_collect_headers(array($tree[0])), IEML_table_collect_headers(array($tree[1])));
    $body = IEML_table_collect_body($tree[0]);
    if (FALSE !== $heads[0] && FALSE !== $heads[1]) {
        return array(
            'length' => count($heads[1][0]),
            'height' => count($body),
            'hor_header_depth' => count($heads[0]),
            'ver_header_depth' => count($heads[1]),
            'headers' => $heads,
            'body' => $body,
            'top' => $top
        );
    } else {
        return array(
            'length' => 1,
            'height' => count($body),
            'hor_header_depth' => 0,
            'ver_header_depth' => 0,
            'headers' => FALSE,
            'body' => $body,
            'top' => $top
        );
    }
    return FALSE;
}

function IEML_gen_table_info($top, $IEML_lev_reg, $IEML_syms, $IEML_toVary, $IEML_prim, $IEML_lowToVowelReg) {
    //generate logical view of table
    $tabs = IEML_gen_header($top, $IEML_lev_reg, $IEML_syms, $IEML_toVary, $IEML_prim);
    //do some postprocessing/cleaning up
    $tabs = IEML_postproc_tables($tabs, $IEML_lowToVowelReg);
    //flatten the whole thing to get a list of all expressions that appear a table
    $flat_body = array_flatten($tabs);
    $flat_body[] = $top;
    
    $flat_body = array_values(array_filter($flat_body, function($el) { return !is_numeric($el); }));
    
    $ret = IEML_coll_info($tabs, $top);
    $ret['table_flat'] = $flat_body;
    
    return $ret;
}

?>
