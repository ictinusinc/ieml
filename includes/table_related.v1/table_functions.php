<?php

function fetch_cell_relations($cell, $info) {
    /*return array(
        'contained' => ,
        'containing' => ,
        'concurrents', 'comp', 'etymology'
    );*/
}


$IEML_prim = array('vars' => array('M', 'O', 'F', 'I'), 'vow' => array('S', 'B', 'T', 'A', 'U', 'E'));
$IEML_toVary = array(
    'M' => array('S', 'B', 'T'),
    'O' => array('U', 'A'),
    'F' => array('O', 'M'),
    'I' => array('E', 'F')
);
//NOTE: use PHP's 'x' modifier when matching regex to ignore whitespace
$IEML_regex = array(1 => "/(?:(?:[EAUSBT])\:([OMFI])\:)|(?:([OMFI])\:(?:[EAUSBT])\:)\./", 2 => "/([OMFI])\:([OMFI])\:\./");
$IEML_lowToVowelReg = array(
    '/(?<![EAUSBT]\:)U\:U\:(?:E\:)?\./' => 'wo.', '/(?<![EAUSBT]\:)U\:A\:(?:E\:)?\./' => 'wa.',
    '/(?<![EAUSBT]\:)U\:S\:(?:E\:)?\./' => 'y.', '/(?<![EAUSBT]\:)U\:B\:(?:E\:)?\./' => 'o.',
    '/(?<![EAUSBT]\:)U\:T\:(?:E\:)?\./' => 'e.',
    '/(?<![EAUSBT]\:)A\:U\:(?:E\:)?\./' => 'wu.', '/(?<![EAUSBT]\:)A\:A\:(?:E\:)?\./' => 'we.',
    '/(?<![EAUSBT]\:)A\:S\:(?:E\:)?\./' => 'u.', '/(?<![EAUSBT]\:)A\:B\:(?:E\:)?\./' => 'a.',
    '/(?<![EAUSBT]\:)A\:T\:(?:E\:)?\./' => 'i.',
    '/(?<![EAUSBT]\:)S\:U\:(?:E\:)?\./' => 'j.', '/(?<![EAUSBT]\:)S\:A\:(?:E\:)?\./' => 'g.',
    '/(?<![EAUSBT]\:)S\:S\:(?:E\:)?\./' => 's.', '/(?<![EAUSBT]\:)S\:B\:(?:E\:)?\./' => 'b.',
    '/(?<![EAUSBT]\:)S\:T\:(?:E\:)?\./' => 't.',
    '/(?<![EAUSBT]\:)B\:U\:(?:E\:)?\./' => 'h.', '/(?<![EAUSBT]\:)B\:A\:(?:E\:)?\./' => 'c.',
    '/(?<![EAUSBT]\:)B\:S\:(?:E\:)?\./' => 'k.', '/(?<![EAUSBT]\:)B\:B\:(?:E\:)?\./' => 'm.',
    '/(?<![EAUSBT]\:)B\:T\:(?:E\:)?\./' => 'n.',
    '/(?<![EAUSBT]\:)T\:U\:(?:E\:)?\./' => 'p.', '/(?<![EAUSBT]\:)T\:A\:(?:E\:)?\./' => 'x.',
    '/(?<![EAUSBT]\:)T\:S\:(?:E\:)?\./' => 'd.', '/(?<![EAUSBT]\:)T\:B\:(?:E\:)?\./' => 'f.',
    '/(?<![EAUSBT]\:)T\:T\:(?:E\:)?\./' => 'l.'
);
$IEML_low_reg = "/([EAUSBT]):([EAUSBT]):/";
$IEML_syms = array(':', '.', '-', "'", ',', '_', ';');
$IEML_lev_pre_reg = array(
    6 => "([^;]+?);(?:([^;]+?);)?(?:([^;]+?);)?",
    5 => "([^_]+?)_(?:([^_]+?)_)?(?:([^_]+?)_)?",
    4 => "([^,]+?),(?:([^,]+?),)?(?:([^,]+?),)?",
    3 => "([^']+?)'(?:([^']+?)')?(?:([^']+?)')?",
    2 => "([^\-]+?)\-(?:([^\-]+?)\-)?(?:([^\-]+?)\-)?",
    1 => "([^\.]+?)\.(?:([^\.]+?)\.)?(?:([^\.]+?)\.)?",
    0 => "([^\:]+?)\:(?:([^\:]+?)\:)?(?:([^\:]+?)\:)?"
);

$IEML_lev_reg = array(
    6 => "/".$IEML_lev_pre_reg[6]."/",
    5 => "/".$IEML_lev_pre_reg[5]."/",
    4 => "/".$IEML_lev_pre_reg[4]."/",
    3 => "/".$IEML_lev_pre_reg[3]."/",
    2 => "/".$IEML_lev_pre_reg[2]."/",
    1 => "/".$IEML_lev_pre_reg[1]."/",
    0 => "/".$IEML_lev_pre_reg[0]."/"
);

function str_splice($str, $offset, $len = 0, $rep = NULL) {
    return substr($str, 0, $offset).($rep == NULL ? '' : $rep).($len == 0 ? '' : substr($str, $offset + $len));
}

function array_2d_transpose($arr) {
    $out = array();
    foreach ($arr as $key => $subarr) {
        foreach ($subarr as $subkey => $subvalue) {
        	$out[$subkey][$key] = $subvalue;
        }
    }
    return $out;
}

function array_flatten($array, $return = array()) {
    foreach ($array as $val) {
        if(is_array($val)) {
            $return = array_flatten($val, $return);
        } else if($val) {
            $return[] = $val;
        }
    }
    return $return;
}

function IEML_get_lev($str, $lev_syms, $offset = 0) {
    for ($i=count($lev_syms)-1; $i>=0; $i--) {
        if (FALSE !== strpos($str, $lev_syms[$i], $offset))
            return $i;
    }
    return FALSE;
}

function loc_first_occ($str, $arr, $offs = 0) {
    $ret = FALSE; $pos = -1;
    
    for ($j=$offs; $j<strlen($str); $j++) {
        for ($i=0; $i<count($arr); $i++) {
            if (substr($str, $j, strlen($arr[$i])) == $arr[$i] && (FALSE === $ret || $j < $ret)) {
                $ret = $j;
                $pos = $i;
                if ($j == $offs) //no better match is possible, so we're done
                    goto __LOC_END;
            }
        }
    }
    __LOC_END:
    return FALSE === $ret ? FALSE : array($ret, $pos);
}

function preg_clean_matches($arr) {
    $len = count($arr);
    for ($i=0; $i<$len; $i++)
        if ($arr[$i][0] == '')
            unset($arr[$i]);
    return $arr;
}

function str_trans($str, $dict, $offset = 0) {
    foreach ($dict as $key => $val) {
        $t = strpos($str, $key, $offset);
        if (FALSE !== $t)
            return str_trans(str_splice($str, $t, strlen($key), $val), $dict);
    }
    return $str;
}

function str_trans_preg($str, $reg_dict) {
    $ret = $str;
    foreach ($reg_dict as $pat => $rep)
        $ret = preg_replace($pat, $rep, $ret);
    return $ret;
}

function loc_num_occ($str, $arr, $off = 0) {
    $ret = FALSE; $cpos = $off; $strlen = strlen($str);
    $c_occ = 0;
    
    while ($cpos < $strlen) {
        $sub = loc_first_occ($str, $arr, $cpos);
        if (FALSE !== $sub) {
            $cpos = $sub[0] + strlen($arr[$sub[1]]);
            $c_occ++;
        } else {
            break;
        }
    }
    
    return $c_occ;
}

function IEML_rem_empty_col(&$info, $check_call) {
    $loops = array('ver', 'hor');
    $empties = 0;
    
    for ($i=0; $i<count($info['headers']); $i++) {
        for ($j = count($info['headers'][$i])-1; $j>=0; $j--) {
            $empties = 0;
            for ($k=0; $k<count($info['headers'][$i][$j]); $k++) {
                if (call_user_func($check_call, $info['headers'][$i][$j][$k]))
                    $empties++;
            }
            if ($empties == count($info['headers'][$i][$j])) {
                unset($info['headers'][$i][$j]);
                $info[$loops[$i].'_header_depth']--;
            }
        }
        $info['headers'][$i] = array_values($info['headers'][$i]);
    }
}

function IEML_count_empty_col(&$info, $check_call) {
    $empties = 0;
    
    $ret = array(array(array(), array()), array(0, 0));
    
    for ($i=0; $i<count($info['headers']); $i++) {
        for ($j = 0; $j < count($info['headers'][$i]); $j++) {
            $empties = 0;
            for ($k=0; $k<count($info['headers'][$i][$j]); $k++) {
                if (call_user_func($check_call, $info['headers'][$i][$j][$k]))
                    $empties++;
            }
            if ($empties == count($info['headers'][$i][$j])) {
            	$ret[0][$i][] = $j;
                $ret[1][$i]++;
            }
        }
    }
    
    return $ret;
}

function IEML_fix_table_body(&$info, &$map) {
    $max_len = 0;
    for ($i=0; $i<count($info['body']); $i++) {
        $tlen = 0;
        for ($j=count($info['body'][$i]); $j>=0; $j--) {
            if (!array_key_exists($info['body'][$i][$j]['expression'], $map) || !isset($map[$info['body'][$i][$j]['expression']])) {
                unset($info['body'][$i][$j]);
            }
        }
        $info['body'][$i] = array_values($info['body'][$i]);

        $max_len = max($max_len, count($info['body'][$i]));
    }
    
    if ($info['ver_header_depth'] > 0)
        for ($i=0; $i<count($info['headers'][0]); $i++)
            $max_len = max($max_len, count($info['headers'][0][$i]));
    
    $info['length'] = $max_len;
}

function genDBInfoAboutCell($exp, &$tab_info) {
    $ret = NULL;
    
    for ($i=0; $i<count($tab_info['headers']); $i++) {
        for ($j=0; $j<count($tab_info['headers'][$i]); $j++) {
            for ($k=0; $k<count($tab_info['headers'][$i][$j]); $k++) {
                if ($tab_info['headers'][$i][$j][$k][0] == $exp) {
                    $ret = array(
                        'intPosInTable' => $k,
                        'enumElementType' => 'header',
                        'enumHeaderType' => ($i==0 ? 'hor' : 'ver'),
                        'intSpan' => $tab_info['headers'][$i][$j][$k][1],
                        'intHeaderLevel' => $j);
                    goto __THE_END;
                }
            }
        }
    }
    
    for ($i=0; $i<count($tab_info['body']); $i++) {
        for ($j=0; $j<count($tab_info['body'][$i]); $j++) {
            if ($tab_info['body'][$i][$j] == $exp) {
                $ret = array(
                    'intPosInTable' => $i,
                    'enumElementType' => 'element',
                    'enumHeaderType' => 'ver',
                    'intSpan' => 1,
                    'intHeaderLevel' => $j);
                goto __THE_END;
            }
        }
    }
    
    __THE_END:
    
    return $ret;
}

function IEML_prep_flat_arr(&$key) {
    $exp_query = Conn::queryArrays("
        SELECT
            pkExpressionPrimary as id, strExpression as expression,
                eng.strDescriptor AS descriptorEn, fra.strDescriptor AS descriptorFr
        FROM expression_primary prim
        LEFT JOIN
            (
                SELECT fkExpressionPrimary, strDescriptor
                FROM expression_descriptors
                WHERE strLanguage = 'eng'
            ) eng
            ON eng.fkExpressionPrimary = prim.pkExpressionPrimary
        LEFT JOIN
            (
                SELECT fkExpressionPrimary, strDescriptor
                FROM expression_descriptors
                WHERE strLanguage = 'fra'
            ) fra
            ON fra.fkExpressionPrimary = prim.pkExpressionPrimary
        WHERE enumDeleted = 'N'
        AND   prim.strExpression IN (".implode(',', array_map(function($a) { return goodInput($a); }, $key['table_info']['table_flat'])).")");
    
    
    $flat_body_asoc = array();
    for ($i=0; $i < count($key['table_info']['table_flat']); $i++) {
        $flat_body_asoc[$key['table_info']['table_flat'][$i]] = array('info' => genDBInfoAboutCell($key['table_info']['table_flat'][$i], $key['table_info']));
    }
    
    //associate expressions with their descriptors in a table
    
    for ($i=0; $i<count($exp_query); $i++) {
        $flat_body_asoc[$exp_query[$i]['expression']]['en'] = $exp_query[$i]['descriptorEn'];
        $flat_body_asoc[$exp_query[$i]['expression']]['fr'] = $exp_query[$i]['descriptorFr'];
        $flat_body_asoc[$exp_query[$i]['expression']]['query'] = $exp_query[$i];
    }
    
    $key['exp_query'] = $exp_query;
    $key['table_info']['flat_asoc'] = $flat_body_asoc;
}

function IEML_save_table($key) {
    Conn::query("
        INSERT INTO table_2d_id 
            (fkExpression, intWidth, intHeight, intHorHeaderDepth, intVerHeaderDepth)
        VALUES
            (".$key['id'].", ".$key['table_info']['length'].", ".$key['table_info']['height'].", ".$key['table_info']['hor_header_depth'].", ".$key['table_info']['ver_header_depth'].")");
            
    $key['new_id'] = Conn::getId();
    
    foreach ($key['table_info']['flat_asoc'] as $exp => $cell) {
        if ($exp != $key['table_info']['top']) {
            if (!isset($cell['query'])) {
                Conn::query("
                    INSERT INTO table_2d_ref 
                        (fkTable2D, fkExpressionPrimary, intPosInTable, enumElementType, enumHeaderType, intHeaderLevel, strCellExpression, intSpan)
                    VALUES
                        (".$key['new_id'].", NULL, ".$cell['info']['intPosInTable'].", ".goodInput($cell['info']['enumElementType']).",
                            ".goodInput($cell['info']['enumHeaderType']).", ".$cell['info']['intHeaderLevel'].", ".goodInput($exp).", ".goodInt($cell['info']['intSpan']).") ");
            } else {
                Conn::query("
                    INSERT INTO table_2d_ref 
                        (fkTable2D, fkExpressionPrimary, intPosInTable, enumElementType, enumHeaderType, intHeaderLevel, strCellExpression, intSpan)
                    VALUES
                        (".$key['new_id'].", ".$cell['query']['id'].", ".$cell['info']['intPosInTable'].", ".goodInput($cell['info']['enumElementType']).",
                            ".goodInput($cell['info']['enumHeaderType']).", ".$cell['info']['intHeaderLevel'].", ".goodInput($exp).", ".goodInt($cell['info']['intSpan']).")");
            }
        }
    } unset($cell); unset($exp);
}

require_once(APPROOT.'/includes/table_render_related.php');
require_once(APPROOT.'/includes/table_gen_related.php');

?>