<?php

include_once(APPROOT.'/includes/table_related/exp_parse.php');

//NOTE: use PHP's 'x' modifier when matching regex to ignore whitespace
$IEML_lowToVowelReg = array(
    '/(?<![EAUSBTIFOM]\:)U\:U\:(?:E\:)?\./' => 'wo.', '/(?<![EAUSBTIFOM]\:)U\:A\:(?:E\:)?\./' => 'wa.',
    '/(?<![EAUSBTIFOM]\:)U\:S\:(?:E\:)?\./' => 'y.', '/(?<![EAUSBTIFOM]\:)U\:B\:(?:E\:)?\./' => 'o.',
    '/(?<![EAUSBTIFOM]\:)U\:T\:(?:E\:)?\./' => 'e.',
    '/(?<![EAUSBTIFOM]\:)A\:U\:(?:E\:)?\./' => 'wu.', '/(?<![EAUSBTIFOM]\:)A\:A\:(?:E\:)?\./' => 'we.',
    '/(?<![EAUSBTIFOM]\:)A\:S\:(?:E\:)?\./' => 'u.', '/(?<![EAUSBTIFOM]\:)A\:B\:(?:E\:)?\./' => 'a.',
    '/(?<![EAUSBTIFOM]\:)A\:T\:(?:E\:)?\./' => 'i.',
    '/(?<![EAUSBTIFOM]\:)S\:U\:(?:E\:)?\./' => 'j.', '/(?<![EAUSBTIFOM]\:)S\:A\:(?:E\:)?\./' => 'g.',
    '/(?<![EAUSBTIFOM]\:)S\:S\:(?:E\:)?\./' => 's.', '/(?<![EAUSBTIFOM]\:)S\:B\:(?:E\:)?\./' => 'b.',
    '/(?<![EAUSBTIFOM]\:)S\:T\:(?:E\:)?\./' => 't.',
    '/(?<![EAUSBTIFOM]\:)B\:U\:(?:E\:)?\./' => 'h.', '/(?<![EAUSBTIFOM]\:)B\:A\:(?:E\:)?\./' => 'c.',
    '/(?<![EAUSBTIFOM]\:)B\:S\:(?:E\:)?\./' => 'k.', '/(?<![EAUSBTIFOM]\:)B\:B\:(?:E\:)?\./' => 'm.',
    '/(?<![EAUSBTIFOM]\:)B\:T\:(?:E\:)?\./' => 'n.',
    '/(?<![EAUSBTIFOM]\:)T\:U\:(?:E\:)?\./' => 'p.', '/(?<![EAUSBTIFOM]\:)T\:A\:(?:E\:)?\./' => 'x.',
    '/(?<![EAUSBTIFOM]\:)T\:S\:(?:E\:)?\./' => 'd.', '/(?<![EAUSBTIFOM]\:)T\:B\:(?:E\:)?\./' => 'f.',
    '/(?<![EAUSBTIFOM]\:)T\:T\:(?:E\:)?\./' => 'l.'
);

function substr_ab($str, $a, $b) {
	return substr($str, $a, $b - $a);
}

function array_append(&$arr, $b) {
	foreach ($b as $el) {
		$arr[] = $el;
	}
	
	return $arr;
}

function array_merge_dest(&$arr, $b) {
	foreach ($b as $key => $el) {
		$arr[$key] = $el;
	}
	
	return $arr;
}

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
function IEML_save_cell($tab_id, $cell_info, $exp_assoc, $db_info) {
	Conn::query("
	INSERT INTO table_2d_ref 
		(fkTable2D, fkExpressionPrimary, intPosInTable, enumElementType, enumHeaderType, intHeaderLevel, strCellExpression, intSpan)
	VALUES
		(".$tab_id.", ".(array_key_exists($cell_info, $exp_assoc) ? $exp_assoc[$cell_info]['id'] : 'NULL').", ".$db_info['intPosInTable'].", ".goodInput($db_info['enumElementType']).",
			".goodInput($db_info['enumHeaderType']).", ".$db_info['intHeaderLevel'].", ".goodInput($cell_info).", ".goodInt($db_info['intSpan']).")");
}

function IEML_save_table($key_id, $tab_info) {
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
        AND   prim.strExpression IN (".implode(',', array_map(function($a) { return goodInput($a); }, $tab_info['table_flat'])).")");

	$exp_assoc = array();
	for ($i=0; $i<count($exp_query); $i++) {
		$exp_assoc[$exp_query[$i]['expression']] = $exp_query[$i];
	}
	
    Conn::query("
        INSERT INTO table_2d_id 
            (fkExpression, intWidth, intHeight, intHorHeaderDepth, intVerHeaderDepth, jsonTableLogic)
        VALUES
            (".$key_id.", ".$tab_info['length'].", ".$tab_info['height'].", ".$tab_info['hor_header_depth'].", ".$tab_info['ver_header_depth'].", '".goodString(json_encode($tab_info['post_raw_table']))."')");
            
    $key['new_id'] = Conn::getId();
	
    for ($i=0; $i<count($tab_info['headers']); $i++) {
        for ($j=0; $j<count($tab_info['headers'][$i]); $j++) {
            for ($k=0; $k<count($tab_info['headers'][$i][$j]); $k++) {
				IEML_save_cell($key['new_id'], $tab_info['headers'][$i][$j][$k][0], $exp_assoc,
					array(
						'intPosInTable' => $k,
						'enumElementType' => 'header',
						'enumHeaderType' => ($i==0 ? 'hor' : 'ver'),
						'intSpan' => $tab_info['headers'][$i][$j][$k][1],
						'intHeaderLevel' => $j)
				);
            }
        }
    }
    
    for ($i=0; $i<count($tab_info['body']); $i++) {
        for ($j=0; $j<count($tab_info['body'][$i]); $j++) {
			IEML_save_cell($key['new_id'], $tab_info['body'][$i][$j], $exp_assoc,
				array(
					'intPosInTable' => $i,
					'enumElementType' => 'element',
					'enumHeaderType' => 'ver',
					'intSpan' => 1,
					'intHeaderLevel' => $j)
			);
        }
    }
}

require_once(APPROOT.'/includes/table_related/table_render_related.php');
require_once(APPROOT.'/includes/table_related/table_gen_related.php');

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
                array_append($heads, $tree[$i]['head']);
                if (array_key_exists('rest', $tree[$i])) {
                    $sub = IEML_table_collect_headers($tree[$i]['rest']);
                    if (FALSE !== $sub) {
                        if (count($sub_heads) == 0) {
                            $sub_heads = $sub;
                        } else {
                            for ($j=0; $j<count($sub); $j++)
                                array_append($sub_heads[$j], $sub[$j]);
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
    //$body = array_2d_transpose(IEML_table_collect_body($tree[1]));
    $body = IEML_table_collect_body($tree[0]);
    if (FALSE !== $heads[0] && FALSE !== $heads[1]) {
        return array(
            'length' => count($body[0]),
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

function IEML_concat_tables($tables, $top) {
	$ret = array(
            'length' => $tables['tables'][0]['length'],
            'height' => 0,
            'hor_header_depth' => $tables['tables'][0]['hor_header_depth']+1,
            'ver_header_depth' => 0,
            'headers' => array($tables['tables'][0]['headers'][0], array()),
            'body' => array(),
            'top' => $top,
			'table_flat' => array(),
			'table_count' => count($tables['tables'])
        );
	
	$top_head = array();
	
	for ($i=0; $i<count($tables['tables']); $i++) {
		$ret['height'] += $tables['tables'][$i]['height'];
		array_append($ret['body'], $tables['tables'][$i]['body']);
		array_append($ret['table_flat'], $tables['flat_tables'][$i]);
		
		if ($i > 0) {
			for ($j=0; $j<$ret['hor_header_depth'] - 1; $j++) {
				array_append($ret['headers'][0][$j], $tables['tables'][$i]['headers'][0][$j]);
			}
		}
		
		$top_span = 0;
		for ($j=0; $j<count($tables['tables'][$i]['headers'][0][$tables['tables'][$i]['hor_header_depth']-1]); $j++) {
			$top_span += $tables['tables'][$i]['headers'][0][$tables['tables'][$i]['hor_header_depth']-1][$j][1];
		}
		$top_head[] = array($tables['tables'][$i]['top'], $top_span);
	}
	
	$ret['headers'][0][] = $top_head;
	
	$ret['table_flat'][] = $top;
	
	$ret['raw_table'] = $tables['raw_table'];
	$ret['post_raw_table'] = $tables['post_raw_table'];
	
	return $ret;
}

function IEML_force_concat_check($AST) {
	if ($AST['type'] == 'internal' && $AST['value']['type'] == 'LAYER') {
		return true;
	} else {
		if ($AST['type'] == 'internal') {
			for ($i=0; $i<count($AST['children']); $i++) {
				if (IEML_force_concat_check($AST['children'][$i])) {
					return true;
				}
			}
		}
	}
	
	return false;
}

function IEML_gen_table_info($top, $IEML_lowToVowelReg) {
	$tokens = \IEML_ExpParse\str_to_tokens($top);
	$AST = \IEML_ExpParse\tokens_to_AST($tokens);	
	
	//echo pre_dump(\IEML_ExpParse\AST_to_infix_str($AST, $top));
	
	if (IEML_force_concat_check($AST)) {
		$concats = \IEML_ExpParse\split_by_concats($AST);
	} else {
		$concats = array($AST);
	}
	
	$tab_concat = array();
	$flats = array();
	$raws = array();
	$post_raws = array();
	
	for ($i=0; $i<count($concats); $i++) {
		$sub_top = \IEML_ExpParse\AST_original_str($concats[$i], $top);
		$raw_tab = IEML_gen_header($concats[$i], $top);
		$raws[] = $raw_tab;
		
		$post_tab = IEML_postproc_tables($raw_tab, $IEML_lowToVowelReg);
		$post_raws[] = $post_tab;
		
		$tab_concat[] = IEML_coll_info($post_tab, $sub_top);
		
		$flat_body = array_flatten($post_tab);
		$flat_body[] = $sub_top;
		
		$flats[] = $flat_body;
	}
	
	$ret = NULL;
	if (count($concats) > 1) {
		$tables = array(
			'tables' => $tab_concat,
			'flat_tables' => $flats,
			'raw_table' => $raws,
			'post_raw_table' => $post_raws
		);
		
		$ret = IEML_concat_tables($tables, $top);
	} else {
		$ret = $tab_concat[0];
		$ret['table_flat'] = $flats[0];
		$ret['raw_table'] = $raws[0];
		$ret['post_raw_table'] = $post_raws[0];
	}
	
	return $ret;
}

function reconstruct_table_info($top, $head, $body_query) {
    $key = array(
        'length' => (int)$head['intWidth'],
        'height' => (int)$head['intHeight'],
        'hor_header_depth' => (int)$head['intHorHeaderDepth'],
        'ver_header_depth' => (int)$head['intVerHeaderDepth'],
        'headers' => array(array_fill(0, $head['intHorHeaderDepth'], array()), array_fill(0, $head['intVerHeaderDepth'], array())),
        'body' => NULL,
        'top' => $top
    );
    
    $key['body'] = array_fill(0, $key['height'], array());
    
    foreach ($body_query as $bel) {
        if ($bel['enumElementType'] == 'element') {
            $key['body'][$bel['intPosInTable']][$bel['intHeaderLevel']] = $bel;
        } else if ($bel['enumElementType'] == 'header') {
            if ($bel['enumHeaderType'] == 'ver') {
                $key['headers'][1][$bel['intHeaderLevel']][$bel['intPosInTable']] = array($bel, $bel['intSpan']);
            } else if ($bel['enumHeaderType'] == 'hor') {
                $key['headers'][0][$bel['intHeaderLevel']][$bel['intPosInTable']] = array($bel, $bel['intSpan']);
            }
        }
    } unset($bel);
    
    return $key;
}

function table_tree_flat($tree) {
	$ret = array();
	
	if (array_key_exists('rest', $tree)) {
		for($i=0; $i<count($tree['rest']); $i++) {
			$ret[] = $tree['head'][$i][0];
			array_append($ret, table_tree_flat($tree['rest'][$i]));
		}
	} else {
		array_append($ret, array_flatten($tree['body']));
	}
	
	return $ret;
}

function table_tree_flat_degree($tree, $lev = 0) {
	$ret = array();
	
	if (array_key_exists('rest', $tree)) {
		for($i=0; $i<count($tree['rest']); $i++) {
			$ret[] = array($tree['head'][$i][0], $lev);
			array_append($ret, table_tree_flat_degree($tree['rest'][$i], $lev+1));
		}
	} else if (array_key_exists('body', $tree)) {
		for($i=0; $i<count($tree['head']); $i++) {
			$ret[] = array($tree['head'][$i][0], $lev);
		}
		
		for ($j=0; $j<count($tree['body']); $j++) {
			array_append($ret, table_tree_flat_degree($tree['body'][$j], $lev+1));
		}
	} else {
		for ($i=0; $i<count($tree); $i++) {
			$ret[] = array($tree[$i], $lev+1);
		}
	}
	
	return $ret;
}

function taxonomic_relations_single($exp, &$raw, $lev = 0) {
	$ret = array(
		'contained' => array(),
		'containing' => array(),
		'concurrent' => array(),
		'comp_concept' => NULL
	);
	
	if (array_key_exists('rest', $raw)) {
		$rolling_count = 0;
		for ($i=0; $i<count($raw['rest']); $i++) {
			if ($raw['head'][$i][0] == $exp) {
				$ret['containing'] = table_tree_flat_degree($raw['rest'][$i]);
			}
			
			$sub = taxonomic_relations_single($exp, $raw['rest'][$i], $lev+1);
			
			if (count($sub['contained']) > 0 || count($sub['containing']) > 0) {
				array_append($ret['contained'], $sub['contained']);
				array_append($ret['containing'], $sub['containing']);
				array_merge_dest($ret['concurrent'], $sub['concurrent']);
				
				$ret['contained'][] = array($raw['head'][$i][0], $lev);
				$ret['concurrent'][$raw['head'][$i][0]] = table_tree_flat_degree($raw['rest'][$i]);
			}
			
			if ($sub['comp_concept'] != FALSE) {
				$ret['comp_concept'] = array($rolling_count + $sub['comp_concept'][0], $sub['comp_concept'][1]);
			}
			
			$rolling_count += $raw['head'][$i][1];
		}
	} else {
		for ($i=0; $i<count($raw['head']); $i++) {
			if ($raw['head'][$i][0] == $exp) {
				$ret['containing'] = table_tree_flat_degree($raw['body'][$i]);
				
				break;
			}
			
			for ($j=0; $j<count($raw['body'][$i]); $j++) {
				if ($raw['body'][$i][$j] == $exp) {
					$ret['contained'][] = array($raw['head'][$i][0], $lev);
					$ret['concurrent'][$raw['head'][$i][0]] = table_tree_flat_degree($raw['body'][$i]);
					
					$ret['comp_concept'] = array($i, $j);
				
					break;
				}
			}
		}
	}
	
	return $ret;
}

function gen_contained_containing_concurent($exp, &$table, $callback) {
	$tax = array(
		taxonomic_relations_single($exp, $table[0]),
		taxonomic_relations_single($exp, $table[1])
	);
	
	$contained_lens = array(0, 0);
	
	for ($i=0; $i<count($tax); $i++) {
		if (FALSE == call_user_func($callback, $tax[$i]['contained'][0])) {
			$tax[$i]['contained'] = array_reverse($tax[$i]['contained']);
			
			for ($j=count($tax[$i]['contained'])-1; $j>=0; $j--) {
				if (FALSE == call_user_func($callback, $tax[$i]['contained'][$j])) {
					array_pop($tax[$i]['contained']);
				} else {
					break;
				}
			}
		}
		
		$contained_lens[$i] = count($tax[$i]['contained']);
		for ($j=0; $j<count($tax[$i]['contained']); $j++) {
			$tax[$i]['contained'][$j][1] = $contained_lens[$i] - $tax[$i]['contained'][$j][1];
		}
	}
	
	$ret = array(
		'contained' => array_append($tax[0]['contained'], $tax[1]['contained']),
		'containing' => array_append($tax[0]['containing'], $tax[1]['containing']),
		'concurrent' => array_merge_dest($tax[0]['concurrent'], $tax[1]['concurrent']),
		'comp_concept' => $tax[0]['comp_concept']
	);
	
	return $ret;
}

function postproc_exp_relations(&$rel, $callback) {
	for ($i=0; $i<count($rel['contained']); $i++) {
		$rel['contained'][$i] = call_user_func($callback, $rel['contained'][$i]);
	}
	
	for ($i=0; $i<count($rel['containing']); $i++) {
		$rel['containing'][$i] = call_user_func($callback, $rel['containing'][$i]);
	}
	
	foreach($rel['concurrent'] as $exp => $con_list) {
		for($i=0; $i<count($rel['concurrent'][$exp]); $i++) {
			$rel['concurrent'][$exp][$i] = call_user_func($callback, $rel['concurrent'][$exp][$i]);
		}
	}
	
	$rel['comp_concept'] = call_user_func($callback, $rel['comp_concept']);
	
	return $rel;
}

function gen_exp_relations($exp, $top, &$info, $callback) {
	$ret = NULL;
	if ($exp == $top) {
		$tax = array(
			table_tree_flat_degree($info['post_raw_table'][0]),
			table_tree_flat_degree($info['post_raw_table'][1])
		);
		
		$ret = array(
			'contained' => array(),
			'containing' => array_append($tax[0], $tax[1]),
			'concurrent' => array(),
			'comp_concept' => NULL
		);
	} else {
		$ret = gen_contained_containing_concurent($exp, $info['post_raw_table'], $callback);
		if ($ret['comp_concept'] != NULL) {
			$ret['comp_concept'] = array($info['body'][$ret['comp_concept'][1]][$ret['comp_concept'][0]], 0);
		}
	}
	
	return $ret;
}

function gen_etymology($exp) {
	$tokens = \IEML_ExpParse\str_to_tokens($exp);
	$AST = \IEML_ExpParse\tokens_to_AST($tokens);
	
	$etym = \IEML_ExpParse\fetch_etymology_from_AST($AST);
	
	//echo pre_dump($etym);
	
	$ret = array();
	for ($i=0; $i<count($etym); $i++) {
		if ($etym[$i]['type'] == 'value') {
			$ret[] = $etym[$i]['value'][0]['value'].':';
		} else {
			$ret[] = \IEML_ExpParse\AST_original_str($etym[$i], $exp);
		}
	}
	
	return $ret;
}

?>
