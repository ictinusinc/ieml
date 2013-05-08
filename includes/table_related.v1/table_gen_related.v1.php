<?php

function IEML_gen_var($AST) {
	$out = array();
	
	if ($AST['type'] == 'internal') {
		if ($AST['value']['type'] == 'LEVEL') {
			$out = IEML_gen_var($AST['children'][0]);
			
			for ($i=0; $i<count($out); $i++) {
				$out[$i] = $out[$i].$AST['value']['value'];
			}
		} else if ($AST['value']['type'] == 'MUL') {
			for ($i=0; $i<count($AST['children']); $i++) {
				$sub = IEML_gen_var($AST['children'][$i], $pre, $post);
				
				if (count($sub) > 0) {
					$new_out = array();
					for ($j=0; $j<count($sub); $j++) {
						if (count($out) > 0) {
							for ($k=0; $k<count($out); $k++) {
								$new_out[] = $out[$k].$sub[$j];
							}
						} else {
							$new_out[] = $sub[$j];
						}
					}
					$out = $new_out;
				}
			}
		} else if ($AST['value']['type'] == 'PLUS') {
			for ($i=0; $i<count($AST['children']); $i++) {
				$out = array_merge($out, IEML_gen_var($AST['children'][$i], $pre, $post));
			}
		}
	} else {
		$str = '';
		for ($i=0; $i<count($AST['value']); $i++) {
			$str .= $AST['value'][$i]['value'].':';
		}
		$out[] = $str;
	}
	
	return $out;
}

function cons_variance_arr($AST) {
	$out = array();
	
	if ($AST['type'] == 'internal') {
		if ($AST['value']['type'] == 'LEVEL') {
			$out = cons_variance_arr($AST['children'][0]);
			$out[] = $AST['value']['value'];
		} else if ($AST['value']['type'] == 'MUL') {
			for ($i=0; $i<count($AST['children']); $i++) {
				if ($AST['children'][$i]['type'] == 'internal') {
					if ($AST['children'][$i]['value']['type'] == 'PLUS') {
						$out[] = cons_variance_arr($AST['children'][$i]);
					} else {
						$out = array_merge($out, cons_variance_arr($AST['children'][$i]));
					}
				} else {
					$out = array_merge($out, $AST['children'][$i]['value']);
				}
			}
		} else if ($AST['value']['type'] == 'PLUS') {
			for ($i=0; $i<count($AST['children']); $i++) {
				if ($AST['children'][$i]['type'] == 'internal') {
					if ($AST['children'][$i]['value']['type'] == 'PLUS') {
						$out = array_merge($out, cons_variance_arr($AST['children'][$i]));
					} else {
						$out[] = cons_variance_arr($AST['children'][$i]);
					}
				} else {
					$out = array_merge($out, $AST['children'][$i]['value']);
				}
			}
		}
	} else {
		$out = $AST['value'];
	}
	
	return $out;
}

function IEML_sub_gen_header($AST, $exp, $pre = "", $post = "") {
	$out = array('head' => array(), 'rest' => array());
	
	//echo 'sub_gen: '.pre_dump(\IEML_ExpParse\AST_to_infix_str($AST, $exp));
	
	if ($AST['type'] == 'internal') {
		if ($AST['value']['type'] == 'LEVEL') {
			return IEML_sub_gen_header($AST['children'][0], $exp, $pre, $AST['value']['value'].$post);
		} else if ($AST['value']['type'] == 'MUL') {
			$vars = IEML_gen_var($AST['children'][0]);
			
			echo pre_dump($AST['children'][0]).pre_dump($vars);
			
			for ($i=0; $i<count($vars); $i++) {
				$sub_pre = $pre.$vars[$i];
				$out['head'][] = $sub_pre.\IEML_ExpParse\AST_original_str($AST['children'][1], $exp).$post;
				
				$out['rest'][] = IEML_sub_gen_header($AST['children'][1], $exp, $sub_pre, $post);
			}
		} else {
			$sub = IEML_gen_var($AST);
			
			for ($i=0; $i<count($sub); $i++) {
				$sub[$i] = $pre.$sub[$i].$post;
			}
			
			$out['head'] = $sub;
		}
	} else {
		$out['head'] = $pre.\IEML_ExpParse\AST_original_str($AST).$post;
	}
	if (count($out['rest']) == 0) unset($out['rest']);
	
	return $out;
}

function IEML_gen_header($AST, $exp, $pre = "", $post = "") {
	$out = NULL;
	
	
    $num_var = IEML_count_num_var($AST);
	
	echo 'num_var: '.$num_var.'<br/>';
    
    if ($num_var == 1) {
        $out = array(array('body' => array_2d_transpose(array(IEML_gen_var($splice_str, $var_exp, $prim['vars'])))));
        //TODO: handle single column table properly
    } else if ($num_var >= 2) {
    	$num_parts_in_lev = IEML_get_num_parts_in_lev($AST);
		echo 'num_parts_in_lev: '.$num_parts_in_lev.'<br/>';
    	
    	if ($num_parts_in_lev == 1) {
	    	$out = IEML_gen_header($AST, $exp, $pre, $post);
    	} else {
	    	list($tally_part_varied, $prime_tpv, $tpv_out_str) = IEML_tally_part_varied($AST, $exp, $pre, $post);
			
	    	$tpv_len = count($tally_part_varied);
	    	
	    	if ($tpv_len != $num_parts_in_lev) {
		    	if ($tpv_len == 1) {
			    	return IEML_gen_header($AST, $exp, $pre, $post);
		    	}
	    	}
	    	
	    	$out = array();
			
	    	for ($i=0; $i<$tpv_len; $i++) {
	    		$sub = IEML_sub_gen_header($tally_part_varied[$i], $exp, $pre.$tpv_out_str[$i][0], $tpv_out_str[$i][1].$post);
	    		
	    		if (NULL !== $sub) {
		    		$out[] = $sub;
	    		}
	    	}
    	}
    }
    
    return $out;
}

function IEML_tally_part_varied($AST, $exp, $pre = '', $post = '') {
	if ($AST['type'] == 'internal') {
		$tally_part_varied = array(); $prime_tpv = array(); $tpv_out_str = array();
		
		if ($AST['value']['type'] == 'LEVEL') {
			for ($i=0; $i<count($AST['children']); $i++) {
				$sub = IEML_tally_part_varied($AST['children'][$i], $exp, $pre, $AST['value']['value'].$post);
				
				$tally_part_varied = array_merge($tally_part_varied, $sub[0]);
				$prime_tpv = array_merge($prime_tpv, $sub[1]);
				$tpv_out_str = array_merge($tpv_out_str, $sub[2]);
			}
		} else if ($AST['value']['type'] == 'MUL') {
			for ($i=0; $i<count($AST['children']); $i++) {
				if ($AST['children'][$i]['type'] == 'internal' && $AST['children'][$i]['value']['type'] == 'MUL') {
					$tpre = '';
					for ($j=0; $j < $i; $j++) {
						$tpre .= \IEML_ExpParse\AST_original_str($AST['children'][$j], $exp);
					}
					
					$sub = IEML_tally_part_varied($AST['children'][$i], $exp, $pre.$tpre, $post);
				
					$tally_part_varied = array_merge($tally_part_varied, $sub[0]);
					$prime_tpv = array_merge($prime_tpv, $sub[1]);
					$tpv_out_str = array_merge($tpv_out_str, $sub[2]);
				} else {
					$sub = IEML_count_num_var($AST['children'][$i]);
				
					if ($sub > 0) {
						$tally_part_varied[] = $AST['children'][$i];
						$tpre = ''; $tpost = '';
						
						for ($j=0; $j<count($AST['children']); $j++) {
							if ($j < $i) {
								$tpre .= \IEML_ExpParse\AST_original_str($AST['children'][$j], $exp);
							} else if ($j > $i) {
								$tpost .= \IEML_ExpParse\AST_original_str($AST['children'][$j], $exp);
							}
						}
						$tpv_out_str[] = array($pre.$tpre, $tpost.$post);
					} else {
						$prime_tpv[] = $AST['children'][$i];
					}
				}
			}
		}
		
		return array($tally_part_varied, $prime_tpv, $tpv_out_str);
	} else {
		return 0;
	}
}

function IEML_count_num_var($AST) {
	$out = 0;
	
	if ($AST['type'] == 'internal') {
		if ($AST['value']['type'] == 'MUL' || $AST['value']['type'] == 'LEVEL') {
			for ($i=0; $i<count($AST['children']); $i++) {
				$out += IEML_count_num_var($AST['children'][$i]);
			}
		} else if ($AST['value']['type'] == 'PLUS') {
			$out = 1;
		}
	}
	
	return $out;
}

function IEML_get_num_parts_in_lev($AST) {
	$out = 1;
	
	if ($AST['type'] == 'internal') {
		if ($AST['value']['type'] == 'LEVEL') {
			$out = IEML_get_num_parts_in_lev($AST['children'][0]);
		} else if ($AST['value']['type'] == 'MUL') {
			$out = 0;
			for ($i=0; $i<count($AST['children']); $i++) {
				if (($AST['children'][$i]['type'] == 'internal' && ($AST['children'][$i]['value']['type'] == 'LEVEL' || $AST['children'][$i]['value']['type'] == 'PLUS'))
					|| $AST['children'][$i]['value']['type'] == 'ATOM') {
					$out += 1;
				} else {
					$out += IEML_get_num_parts_in_lev($AST['children'][$i]);
				}
			}
		} else {
			$out = 1;
		}
	}
	
	return $out;
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
