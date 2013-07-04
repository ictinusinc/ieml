<?php

require_once('IEMLVarrArr.class.php');

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

function IEML_prepend_tree($tree, $pre, $post) {
	if (array_key_exists('head', $tree)) {
		for ($i=0; $i<count($tree['head']); $i++) {
			$tree['head'][$i][0] = $pre.$tree['head'][$i][0].$post;
			
			if (array_key_exists('rest', $tree)) {
				$tree['rest'][$i] = IEML_prepend_tree($tree['rest'][$i], $pre, $post);
			}
		}
	}
	
	return $tree;
}

function IEML_collect_lowest_headers($tree) {
	$out = array();
	
	if (array_key_exists('rest', $tree)) {
		for ($i=0; $i<count($tree['rest']); $i++) {
			array_append($out, IEML_collect_lowest_headers($tree['rest'][$i]));
		}
	} else {
		for ($i=0; $i<count($tree['head']); $i++) {
			$out[] = $tree['head'][$i][0];
		}
	}
	
	return $out;
}

function IEML_combine_headers($header_info_0, $header_info_1) {
	$bodies = array(
		IEML_collect_lowest_headers($header_info_0[0]),
		IEML_collect_lowest_headers($header_info_1[0])
	);
	
	$body = array();
	
	for ($i=0; $i<count($bodies[0]); $i++) {
		$row = array();
		
		for ($j=0; $j<count($bodies[1]); $j++) {
			$row[] = $header_info_0[1].$bodies[0][$i].$bodies[1][$j].$header_info_1[2];
		}
		
		$body[] = $row;
	}
	
	$header_info_0[0] = IEML_prepend_tree($header_info_0[0], $header_info_0[1], $header_info_0[2]);
	$header_info_1[0] = IEML_prepend_tree($header_info_1[0], $header_info_1[1], $header_info_1[2]);
	
	$headers = array($header_info_0[0], $header_info_1[0]);
	
	return array($headers, $body);
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
	    	
	    	$sub_heads = array();
	    	$out = array();
			
	    	for ($i=0; $i<$tpv_len; $i++) {
	    		$cvinst = IEMLVarrArr::instanceFromAST($tally_part_varied[$i], $exp);
	    		$variations = $cvinst->generateHeaderVariations();
	    		$temp_subs = array();
	    		
				for ($j=0; $j<count($variations)-1; $j++) {
	    			$temp_subs[] = array($variations[$j]->IEML_vary_header(0, $variations[$j]->lastIndex()), $tpv_out_str[$i][0], $tpv_out_str[$i][1]);
	    		}
	    		
	    		$sub_heads[] = $temp_subs;
	    	}
	    	
	    	if (count($sub_heads) == 2) {
		    	for ($i=0; $i<count($sub_heads[0]); $i++) {
			    	for ($j=0; $j<count($sub_heads[1]); $j++) {
			    		$out[] = IEML_combine_headers($sub_heads[0][$i], $sub_heads[1][$j]);
			    	}
		    	}
	    	} else {
	    		$third_seme = array();
	    		
		    	for ($k=0; $k<count($sub_heads[2]); $k++) {
	    			array_append($third_seme, IEML_collect_lowest_headers($sub_heads[2][$k][0]));
		    	}
		    	
		    	for ($k=0; $k<count($third_seme); $k++) {
		    		for ($i=0; $i<count($sub_heads[0]); $i++) {
				    	for ($j=0; $j<count($sub_heads[1]); $j++) {
							$out[] = IEML_combine_headers($sub_heads[0][$i], array($sub_heads[1][$j][0], $sub_heads[1][$j][1], $third_seme[$k]));
				    	}
			    	}
			    }
	    	}
    	}
    }
    
    return $out;
}

function IEML_tally_part_varied($AST, $exp, $pre = '', $post = '') {
	if ($AST['internal']) {
		$tally_part_varied = array(); $prime_tpv = array(); $tpv_out_str = array();
		
		if ($AST['type'] == 'L0PLUS') {
			$tally_part_varied[] = $AST;
			$tpv_out_str[] = array($pre, $post);
		} else if ($AST['value']['type'] == 'LAYER') {
			for ($i=0; $i<count($AST['children']); $i++) {
				$sub = IEML_tally_part_varied($AST['children'][$i], $exp, $pre, $AST['value']['value'].$post);
				
				array_append($tally_part_varied, $sub[0]);
				array_append($prime_tpv, $sub[1]);
				array_append($tpv_out_str, $sub[2]);
			}
		} else if ($AST['value']['type'] == 'MUL') {
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
