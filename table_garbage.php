<?php
//algorithms used for past iterations of regen_tables. i've put too much work into it to just erase it.
/*
function match_1dim($key, $regex, $offset = 0) {
    $match = array();
    foreach ($regex as $dim => $reg) {
        if (FALSE != preg_match($reg, $key, $match, PREG_OFFSET_CAPTURE, $offset)) {
            return array($dim, preg_clean_matches($match));
        }
    }
    return FALSE;
}

function expand_dim($key, $toVary, $regex, $low_reg, $lowToVowel, $offset = 0) {
    $dim_res = match_1dim($key, $regex, $offset);
    
    if (FALSE !== $dim_res) {
        list($dim, $match) = $dim_res;
        $ret = array();
        for ($i=0; $i<$dim; $i++) {
            array_push($ret, array('head' => array(), 'body' => array()));
        
            foreach ($toVary[$match[$i+1][0]] as $el) {
                $key[$match[$i+1][1]] = $el;
                
                if (FALSE && FALSE !== preg_match($low_reg, $key)) {
                    array_push($ret[$i]['head'], str_trans($key, $lowToVowel, $offset));
                } else {
                    array_push($ret[$i]['head'], $key);
                }
                
                $sub_r = expand_dim($key, $toVary, $regex, $low_reg, $lowToVowel, $offset);
                if (FALSE !== $sub_r)
                    array_push($ret[$i]['body'], $sub_r);
            }
            $key[$match[$i+1][1]] = $match[$i+1][0];
            
            if (count($ret[$i]['body']) == 0)
                unset($ret[$i]['body']);
        }
        
        return $ret;
    }
    return FALSE;
}

function tree_to_str($tree, $lev = 1) {
    $ret = "\n";
    for ($i=0; $i<count($tree['head']); $i++)
        $ret .= ($i==0?'':"\n").str_repeat("    ", $lev-1).implode(', ', $tree['head'][$i]);
    
    for ($i=0; $i<count($tree['body']); $i++)
        $ret .= tree_to_str($tree['body'][$i], $lev+1);
    
    return $ret;
}


function IEML_gen_header($str, $lev_reg, $lev_syms, $var_exp) {
    $mat = NULL;
    $lev = IEML_get_lev($str, $lev_syms);
    
    if (FALSE === $lev && strlen($str) == 1) {
        return $var_exp[$str];
    } else {
        if (FALSE !== preg_match($lev_reg[$lev], $str, $mat, PREG_OFFSET_CAPTURE)) {
            if (NULL !== $mat && count($mat) > 0) {
                $dim = count($mat)-1;
                if ($dim == 1) {
                    return IEML_gen_header($mat[1][0], $lev_reg, $lev_syms, $var_exp);
                } else {
                    $ret = array();
                    //echo pre_dump($mat);
                    for ($i=0; $i<$dim; $i++) {
                        $sub = IEML_gen_header($mat[$i+1][0], $lev_reg, $lev_syms, $var_exp);
                        if (FALSE !== $sub) {
                            for ($j=0; $j<count($sub); $j++)
                                array_push($ret, str_splice($str, $mat[$i+1][1], strlen($mat[$i+1][0]), $sub[$j]));
                        }
                    }
                    return $ret;
                }
            }
        }
    }
    
    return FALSE;
}

function IEML_gen_header($str, $lev_reg, $lev_syms, $var_exp, $vars) {
    $mat = NULL;
    $lev = IEML_get_lev($str, $lev_syms);
    
    if (FALSE !== preg_match($lev_reg[$lev], $str, $mat, PREG_OFFSET_CAPTURE) && count($mat) > 0) {
        $dim = count($mat)-1;
        if ($dim == 1) {
            return IEML_gen_header($mat[1][0], $lev_reg, $lev_syms, $var_exp, $vars);
        } else {
            $ret = array('head' => array(), 'rest' => array());
            
            for ($i=0; $i<$dim; $i++) {
                array_push($ret['head'], array());
                $sub = IEML_gen_var($mat[$i+1][0], $var_exp, $vars);
                for ($j=0; $j<count($sub); $j++)
                    array_push($ret['head'][$i], str_splice($str, $mat[$i+1][1], strlen($mat[$i+1][0]), $sub[$j]));
                
                $sub_gen = IEML_sub_gen_header($mat[$i+1][0], $lev_reg);
                for ($j=0; $j<count($sub_gen); $j++)
                   array_push($ret['rest'], str_splice($str, $mat[$i+1][1], strlen($mat[$i+1][0]), $sub_gen[$j]));
                
            }
            return $ret;
        }
    }
    
    return FALSE;
}

function loc_first_occ($str, $arr, $offs = 0) {
    $pos = -1;
    $ind = -1;
    for ($i=0; $i<count($arr); $i++) {
        $sub = strpos($str, $arr[$i], $offs);
        if (FALSE !== $sub && $pos < $sub) {
            $pos = $sub;
            $ind = $i;
        }
    }
    return -1 !== $ret ? array($pos, $ind) : FALSE;
}

*/
?>