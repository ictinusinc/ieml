<?php

global $IEML_toVary, $IEML_short_exapnd;

$IEML_toVary = array(
    'F:' => array(
    	array(array('U:', 'A:'), 'O:'),
    	array(array('S:', 'B:', 'T:'), 'M:')
    ),
    'I:' => array(
    	array(array('E:'), 'E:'),
    	array(array('U:', 'A:'), 'O:'),
    	array(array('S:', 'B:', 'T:'), 'M:')
    )
);

$IEML_short_exapnd = array(
	'O' => array('U', 'A'),
	'M' => array('S', 'B', 'T'),
	'F' => array('U', 'A', 'S', 'B', 'T'),
	'I' => array('E', 'U', 'A', 'S', 'B', 'T'),
);

function IEML_gen_var($AST) {
	$out = array();
	
	if ($AST['internal']) {
		if ($AST['value']['type'] == 'LAYER') {
			$out = IEML_gen_var($AST['children'][0]);
			
			for ($i=0; $i<count($out); $i++) {
				$out[$i] = $out[$i].$AST['value']['value'];
			}
		} else if ($AST['value']['type'] == 'MUL') {
			for ($i=0; $i<count($AST['children']); $i++) {
				$sub = IEML_gen_var($AST['children'][$i]);
				
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
				array_append($out, IEML_gen_var($AST['children'][$i]));
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

/**
 * Construct an array that is sued to greatly simplify the generation of tables.
 * 
 * @access public
 * @param mixed $AST an abstract syntax tree
 * @param mixed $exp the original expression that the AST was generated from
 * @return an array as described in this function's description
 */
function cons_variance_arr($AST, $exp) {
	$out = array();

	if ($AST['internal']) {
		if ($AST['value']['type'] == 'MUL') {
			for ($i=0; $i<count($AST['children']); $i++) {
				array_append($out, cons_variance_arr($AST['children'][$i], $exp));
			}
		} else if ($AST['type'] == 'L0PLUS') {
			//TODO: a more elegant version of this branch
			$out[] = '(';
			for ($i=0; $i<count($AST['children']); $i++) {
				if ($i > 0) {
					$out[] = $AST['value']['value'];
				}
				array_append($out, cons_variance_arr($AST['children'][$i], $exp));
			}
			$out[] = ')';
		} else if ($AST['value']['type'] == 'LAYER') {
			$out = cons_variance_arr($AST['children'][0], $exp);
			$out[] = $AST['value']['value'];
		} else if ($AST['value']['type'] == 'PLUS') {
			$out[] = array(IEML_gen_var($AST), \IEML_ExpParse\AST_original_str($AST, $exp));
		}
	} else {
		$out = IEML_gen_var($AST);
	}

	return $out;
}

function IEML_cvarr_to_str($cvarr, $cur, $last) {
	$out = '';
	
	for ($i=$cur; $i<=$last; $i++) {
		if (is_array($cvarr[$i])) {
			$out .= $cvarr[$i][1];
		} else {
			$out .= $cvarr[$i];
		}
	}
	
	return $out;
}

function IEML_walk_cvarr_BFS($cvarr, $cur, $last, $pre = '') {
	$out = array();
	
	if ($cur > $last) {
		$out[] = $pre;
	} else {
		if (is_array($cvarr[$cur])) {
			for ($i=0; $i < count($cvarr[$cur][0]); $i++) {
				array_append($out, IEML_walk_cvarr_BFS($cvarr, $cur+1, $last, $pre.$cvarr[$cur][0][$i]));
			}
		} else {
			$out = IEML_walk_cvarr_BFS($cvarr, $cur+1, $last, $pre.$cvarr[$cur]);
			//echo 'non-arr: '.pre_dump($cvarr[$cur], $pre, $out);
		}
	}
	
	return $out;
}

function IEML_gen_var_BFS($AST, $exp) {
	$cvarr = cons_variance_arr($AST, $exp);
	//echo 'cvarr gen_var_bfs: '.pre_dump($cvarr);
	return IEML_walk_cvarr_BFS($cvarr, 0, count($cvarr)-1);
}

function IEML_gen_possibilities($cur_exp) {
	$arr_tokens = \IEML_ExpParse\str_to_tokens($cur_exp);
	$arr_AST = \IEML_ExpParse\tokens_to_AST($arr_tokens);
	$arr_AST = \IEML_ExpParse\AST_eliminate_empties($arr_AST);
	
	$temp_arr = IEML_gen_var_BFS($arr_AST, $cur_exp);
	
	//return $temp_arr;
	
	$body_candidate = array();
	
	for ($j=0; $j<count($temp_arr); $j++) {
		$temp_tokens = \IEML_ExpParse\str_to_tokens($temp_arr[$j]);
		$temp_AST = \IEML_ExpParse\tokens_to_AST($temp_tokens);
		$temp_AST = \IEML_ExpParse\AST_eliminate_empties($temp_AST);
		$body_candidate[] = \IEML_ExpParse\AST_to_pretty_str($temp_AST);
	}
	
	return $body_candidate;
}

function IEML_gen_possible_headers($cvarr, $pre, $post) {
	
}

function IEML_vary_header($cvarr, $cur, $last, $pre, $post) {
	global $IEML_toVary;
	
	$out = NULL;
	
	if ($cur <= $last) {
		if (is_array($cvarr[$cur])) {
			$num_to_vary = 0;
			
			for ($i=$cur; $i<=$last; $i++) {
				if (is_array($cvarr[$i])) $num_to_vary++;
			}
			
			$out = array_fill(0, pow(2, $num_to_vary) - 1, array('head' => array(), 'rest' => array()));
			
			for ($i=$cur; $i<=$last; $i++) {
				for ($j=1; $j<=$last-$i; $j++) {
					$cur_mid = IEML_cvarr_to_str($cvarr, $cur, $i).$cvarr[$i][0][$k];
					$cur_exp = $pre.$cur_mid.$post;
					
					for ($k=0; $k<count($cvarr[$i][0]); $k++) {
						$sub = IEML_vary_header($cvarr, $i+$j, $last, $pre.$cur_mid, $post);
						
						$span = NULL;
						
						if (isset($sub)) {
							$out[2*$i+$j]['rest'][] = $sub;
							
							for ($l=0; $l<count($sub))
							
						} else {
							$span = 1;
						}
						
						$out[2*$i+$j]['head'][] = array($chr_exp, $span;
					}
				}
			}
		} else {
			$out = IEML_vary_header($cvarr, $cur+1, $last, $pre.$cvarr[$cur], $post);
		}
	} else {
		//we've gone past the end, so just ignore
	}
	
	return $out;
}

function IEML_gen_header($AST, $exp, $pre = "", $post = "") {
	$out = NULL;
	
	//the total number of things which can be varied, in all subtrees
    $num_var = IEML_count_num_var($AST);
	
	//echo 'num_var: '.$num_var.'<br/>';
	//echo 'AST: '.pre_dump($pre, $post, IEML_ExpParse\AST_to_Infix_str($AST, $exp));
    
    if ($num_var == 1) {
        $out = array(array('body' => array_2d_transpose(array(IEML_gen_var($AST)))));
    } else if ($num_var >= 2) {
    	//the number of parts of the current AST tree which has elements to be varied
    	$num_parts_in_lev = IEML_get_num_parts_in_lev($AST);
		
		//echo 'num_parts_in_lev: '.$num_parts_in_lev.'<br/>';
    	
    	if ($num_parts_in_lev == 1) {
	    	$out = IEML_gen_header($AST, $exp, $pre, $post);
    	} else {
    		/*
    			$tally_part_varied = parts of the current AST node that should be varied
    			$primt_tpv = parts of the current AST node that should NOT be varied
    			$tpv_out_str = an array of arrays of the form($pre, $post), with length count($tally_part_varied)
    		*/
	    	list($tally_part_varied, $prime_tpv, $tpv_out_str) = IEML_tally_part_varied($AST, $exp, $pre, $post);
			
	    	$tpv_len = count($tally_part_varied);
			//echo 'tally_part_varied: '.pre_dump($tally_part_varied).'<br/>';
			//echo 'tpv_len: '.$tpv_len.'<br/>';
	    	//echo 'tpv_out_str: '.pre_dump($tpv_out_str).'<br/>';
	    	
	    	if ($tpv_len != $num_parts_in_lev && $tpv_len == 1) {
		    	//echo 'tpv_out_str: '.pre_dump($tpv_out_str).'<br/>';
		    	return IEML_gen_header($tally_part_varied[0], $exp, $pre.$tpv_out_str[0][0], $tpv_out_str[0][1].$post);
	    	}
	    	
	    	$out = array();
			
	    	for ($i=0; $i<$tpv_len; $i++) {
	    		$cvarr = cons_variance_arr($tally_part_varied[$i], $exp);
				echo 'cvarr: '.pre_dump($cvarr);
				//echo pre_dump(IEML_cvarr_to_str($cvarr, 0, count($cvarr) - 1));
				//echo 'parts: '.pre_dump($pre, $tpv_out_str[$i][0], $tpv_out_str[$i][1], $post);
	    		$sub = IEML_vary_header($cvarr, 0, count($cvarr)-1, $tpv_out_str[$i][0], $tpv_out_str[$i][1]);
	    		//echo 'vary_header: '.pre_dump($sub);
	    		
	    		if (NULL !== $sub) {
		    		$out[] = $sub;
	    		}
	    	}
    	}
    }
    
    return $out;
}

function IEML_tally_part_varied($AST, $exp, $pre = '', $post = '') {
	if ($AST['internal']) {
		$tally_part_varied = array(); $prime_tpv = array(); $tpv_out_str = array();
		
		if ($AST['value']['type'] == 'LAYER') {
			for ($i=0; $i<count($AST['children']); $i++) {
				$sub = IEML_tally_part_varied($AST['children'][$i], $exp, $pre, $AST['value']['value'].$post);
				
				array_append($tally_part_varied, $sub[0]);
				array_append($prime_tpv, $sub[1]);
				array_append($tpv_out_str, $sub[2]);
			}
		} else if ($AST['value']['type'] == 'MUL' || $AST['type'] == 'L0PLUS') {
			for ($i=0; $i<count($AST['children']); $i++) {
				if ($AST['children'][$i]['internal'] && ($AST['children'][$i]['value']['type'] == 'MUL' || $AST['children'][$i]['type'] == 'L0PLUS')) {
					$tpre = substr_ab($exp, $AST['_str_ref'][0], $AST['children'][$i]['_str_ref'][0]);
					$tpost = substr_ab($exp, $AST['children'][$i]['_str_ref'][1], $AST['_str_ref'][1]);
					
					$sub = IEML_tally_part_varied($AST['children'][$i], $exp, $pre.$tpre, $tpost.$post);
				
					array_append($tally_part_varied, $sub[0]);
					array_append($prime_tpv, $sub[1]);
					array_append($tpv_out_str, $sub[2]);
				} else {
					$sub = IEML_count_num_var($AST['children'][$i]);
				
					if ($sub > 0) {
						$tally_part_varied[] = $AST['children'][$i];
						
						$tpre = substr_ab($exp, $AST['_str_ref'][0], $AST['children'][$i]['_str_ref'][0]);
						$tpost = substr_ab($exp, $AST['children'][$i]['_str_ref'][1], $AST['_str_ref'][1]);
						
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
	
	if ($AST['internal']) {
		if ($AST['value']['type'] == 'MUL' || $AST['value']['type'] == 'LAYER' || $AST['type'] == 'L0PLUS') {
			for ($i=0; $i<count($AST['children']); $i++) {
				$out += IEML_count_num_var($AST['children'][$i]);
			}
		} else if ($AST['value']['type'] == 'PLUS') {
			$out = 1;
		}
	}
	
	return $out;
}

/**
 * determine the number of layer subexpressions in AST
 * 
 * @access public
 * @param mixed $AST
 * @return void
 */
function IEML_get_num_parts_in_lev($AST) {
	$out = 0;
	
	if ($AST['internal']) {
		if ($AST['value']['type'] == 'LAYER') {
			$out = IEML_get_num_parts_in_lev($AST['children'][0]);
		} else if ($AST['value']['type'] == 'MUL' || $AST['type'] == 'L0PLUS') {
			$out = 0;
			
			for ($i=0; $i<count($AST['children']); $i++) {
				if ($AST['children'][$i]['internal'] && $AST['children'][$i]['value']['type'] == 'LAYER') {
					$out += 1;
				} else {
					$out += IEML_get_num_parts_in_lev($AST['children'][$i]);
				}
			}
		}
	} else {
		$out = 1;
	}
	
	return $out;
}

?>
