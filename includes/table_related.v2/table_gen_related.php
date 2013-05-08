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
				array_append($out, IEML_gen_var($AST['children'][$i], $pre, $post));
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

function cons_variance_arr($AST, $exp) {
	$out = array();
	
	if ($AST['type'] == 'internal') {
		if ($AST['value']['type'] == 'LEVEL') {
			$out = cons_variance_arr($AST['children'][0], $exp);
			$out[] = $AST['value']['value'];
		} else if ($AST['value']['type'] == 'MUL') {
			for ($i=0; $i<count($AST['children']); $i++) {
				array_append($out, cons_variance_arr($AST['children'][$i], $exp));
			}
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
			$sub_pre = '';
			for ($i=0; $i < count($cvarr[$cur][0]); $i++) {
				$sub_pre = $pre.$cvarr[$cur][0][$i];
				
				array_append($out, IEML_walk_cvarr_BFS($cvarr, $cur+1, $last, $sub_pre));
			}
		} else {
			$out = IEML_walk_cvarr_BFS($cvarr, $cur+1, $last, $pre.$cvarr[$cur]);
		}
	}
	
	return $out;
}

function IEML_gen_var_BFS($AST, $exp) {
	$cvarr = cons_variance_arr($AST, $exp);
	return IEML_walk_cvarr_BFS($cvarr, 0, count($cvarr)-1);
}

function IEML_gen_possibilities($cur_exp) {
	$arr_tokens = \IEML_ExpParse\str_to_tokens($cur_exp);
	$arr_AST = \IEML_ExpParse\tokens_to_AST($arr_tokens);
	$arr_AST = \IEML_ExpParse\AST_eliminate_empties($arr_AST);
	
	$temp_arr = IEML_gen_var_BFS($arr_AST, $cur_exp);
	$body_candidate = array();
	
	for ($j=0; $j<count($temp_arr); $j++) {
		$temp_tokens = \IEML_ExpParse\str_to_tokens($temp_arr[$j]);
		$temp_AST = \IEML_ExpParse\tokens_to_AST($temp_tokens);
		$temp_AST = \IEML_ExpParse\AST_eliminate_empties($temp_AST);
		$body_candidate[] = \IEML_ExpParse\AST_to_pretty_str($temp_AST);
	}
	
	return $body_candidate;
}

function IEML_sub_gen_header($cvarr, $cur, $last, $pre, $post) {
	global $IEML_toVary;
	
	if ($cur > $last) return NULL;
	$out = array('head' => array(), 'rest' => array(), 'body' => array());
	
	if (is_array($cvarr[$cur])) {
		$sub_post = IEML_cvarr_to_str($cvarr, $cur+1, $last);
		
		for ($i=0; $i<count($cvarr[$cur][0]); $i++) {
			$cur_exp = $pre.$cvarr[$cur][0][$i].$sub_post.$post;
			
			$sub = IEML_sub_gen_header($cvarr, $cur+1, $last, $pre.$cvarr[$cur][0][$i], $post);
			
			$span = 0;
			if (array_key_exists('head', $sub)) {
				for ($j=0; $j<count($sub['head']); $j++) {
					$span += $sub['head'][$j][1];
				}
			}
			$out['head'][] = array($cur_exp, max(1, $span));
			
			if (NULL !== $sub) {
				$out['rest'][] = $sub;
			} else {
				$out['body'][] = IEML_gen_possibilities($cur_exp);
			}
		}
	} else {
		$out = IEML_sub_gen_header($cvarr, $cur+1, $last, $pre.$cvarr[$cur], $post);
	}
	
	if (count($out['rest']) == 0) unset($out['rest']);
	if (count($out['body']) == 0) unset($out['body']);
	
	return $out;
}

function IEML_gen_header($AST, $exp, $pre = "", $post = "") {
	$out = NULL;
	
    $num_var = IEML_count_num_var($AST);
	
	//echo 'num_var: '.$num_var.'<br/>';
	//echo 'AST: '.pre_dump($AST);
    
    if ($num_var == 1) {
        $out = array(array('body' => array_2d_transpose(array(IEML_gen_var($AST)))));
    } else if ($num_var >= 2) {
    	$num_parts_in_lev = IEML_get_num_parts_in_lev($AST);
		
		//echo 'num_parts_in_lev: '.$num_parts_in_lev.'<br/>';
    	
    	if ($num_parts_in_lev == 1) {
	    	$out = IEML_gen_header($AST, $exp, $pre, $post);
    	} else {
	    	list($tally_part_varied, $prime_tpv, $tpv_out_str) = IEML_tally_part_varied($AST, $exp, $pre, $post);
			
	    	$tpv_len = count($tally_part_varied);
			//echo 'tpv_len: '.$tpv_len.'<br/>';
	    	
	    	if ($tpv_len != $num_parts_in_lev) {
		    	if ($tpv_len == 1) {
			    	return IEML_gen_header($tally_part_varied[0], $exp, $pre, $post);
		    	}
	    	}
	    	
	    	$out = array();
			
	    	for ($i=0; $i<$tpv_len; $i++) {
	    		$cvarr = cons_variance_arr($tally_part_varied[$i], $exp);
				//echo 'cvarr: '.pre_dump($cvarr);
				//echo 'parts: '.pre_dump($pre, $tpv_out_str[$i][0], $tpv_out_str[$i][1], $post);
	    		$sub = IEML_sub_gen_header($cvarr, 0, count($cvarr)-1, $pre.$tpv_out_str[$i][0], $tpv_out_str[$i][1].$post);
	    		//echo 'sub_gen_head: '.pre_dump($sub);
	    		
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
				
				array_append($tally_part_varied, $sub[0]);
				array_append($prime_tpv, $sub[1]);
				array_append($tpv_out_str, $sub[2]);
			}
		} else if ($AST['value']['type'] == 'MUL') {
			for ($i=0; $i<count($AST['children']); $i++) {
				if ($AST['children'][$i]['type'] == 'internal' && $AST['children'][$i]['value']['type'] == 'MUL') {
					$tpre = '';
					for ($j=0; $j < $i; $j++) {
						$tpre .= \IEML_ExpParse\AST_original_str($AST['children'][$j], $exp);
					}
					
					$sub = IEML_tally_part_varied($AST['children'][$i], $exp, $pre.$tpre, $post);
				
					array_append($tally_part_varied, $sub[0]);
					array_append($prime_tpv, $sub[1]);
					array_append($tpv_out_str, $sub[2]);
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

?>
