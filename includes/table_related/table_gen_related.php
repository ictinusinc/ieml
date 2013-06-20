<?php

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

class IEMLVarrArr {
	private $arr, $post;
	
	public static $IEML_toVary = array(
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
	
	public function __construct() {
		$this->arr = array();
		$this->post = '';
	}
	
	public function pushElement($el) {
		if (is_array($el)) {
			$this->arr[] = array($this->post, $el);
			$this->post = '';
		} else {
			$this->post .= $el;
		}
	}
	
	public function setCompleteElement($n, $el) {
		$this->arr[$n] = $el;
	}
	
	public function setArrayFor($n, $el) {
		$this->arr[$n][1] = $el;
	}
	
	public function setStringFor($n, $el) {
		$this->arr[$n][0] = $el;
	}
	
	public function pushCompleteElement($el) {
		$this->arr[] = $el;
	}
	
	public function getCompleteElement($n) {
		return $this->arr[$n];
	}
	
	public function stringFor($n) {
		return $this->arr[$n][0];
	}
	public function arrayFor($n) {
		return $this->arr[$n][1];
	}
	
	public function length() {
		return count($this->arr);
	}
	
	public function lastIndex() {
		return $this->length()-1;
	}
	
	public function getPost() {
		return $this->post;
	}
	
	public function __toString() {
		$str = '';
		
		for ($i=0; $i<$this->length(); $i++) {
			$i_arr = $this->arrayFor($i);
			
			$str .= '('.$this->stringFor($i).'['.$i_arr[1].'])';
		}
		
		$str .= $this->getPost();
		
		return $str;
	}
	
	public function toString() {
		return $this->__toString();
	}
	
	public function toPrettyString($cur = 0, $last = NULL) {
		$str = '';
		
		for ($i = $cur; $i <= (isset($last) ? $last : $this->lastIndex()); $i++) {
			$i_arr = $this->arrayFor($i);
			
			$str .= $this->stringFor($i).$i_arr[1];
		}
		
		$str .= $this->getPost();
		
		return $str;
	}
	
	public static function instanceFromVarrArr($cvinst) {
		$instance = new IEMLVarrArr();
		
		for ($i=0; $i<$cvinst->length(); $i++) {
			$instance->pushCompleteElement($cvinst->getCompleteElement($i));
		}
		
		$instance->pushElement($cvinst->getPost());
		
		return $instance;
	}

	/**
	 * Construct an array that is used to greatly simplify the generation of tables.
	 * 
	 * @access public
	 * @param mixed $AST an abstract syntax tree
	 * @param mixed $exp the original expression that the AST was generated from
	 * @return an array as described in this function's description
	 */
	public function fromAST($AST, $exp) {
		if ($AST['internal']) {
			if ($AST['value']['type'] == 'MUL') {
				for ($i=0; $i<count($AST['children']); $i++) {
					$this->fromAST($AST['children'][$i], $exp);
				}
			} else if ($AST['type'] == 'L0PLUS') {
				//TODO: a more elegant version of this branch
				$this->pushElement('(');
				for ($i=0; $i<count($AST['children']); $i++) {
					if ($i > 0) {
						$this->pushElement($AST['value']['value']);
					}
					$this->fromAST($AST['children'][$i], $exp);
				}
				$this->pushElement(')');
			} else if ($AST['value']['type'] == 'LAYER') {
				$this->fromAST($AST['children'][0], $exp);
				$this->pushElement($AST['value']['value']);
			} else if ($AST['value']['type'] == 'PLUS') {
				$this->pushElement(array(IEML_gen_var($AST), \IEML_ExpParse\AST_original_str($AST, $exp)));
			}
		} else {
			$this->pushElement(IEML_gen_var($AST));
		}
	}
	
	public static function instanceFromAST($AST, $exp) {
		$instance = new IEMLVarrArr();
		$instance->fromAST($AST, $exp);
		
		return $instance;
	}
	
	public function walkBFS($pre = '') {
		return $this->__walkBFS(0, $this->lastIndex(), $pre);
	}
	
	private function __walkBFS($cur, $last, $pre) {
		$out = array();
		
		if ($cur > $last) {
			$out[] = $pre.$this->getPost();
		} else {
			$cur_arr = $this->arrayFor($cur);
			
			for ($i=0; $i<count($cur_arr[0]); $i++) {
				array_append($out, $this->__walkBFS($cur+1, $last, $pre.$this->stringFor($cur).$cur_arr[0][$i]));
			}
		}
		
		return $out;
	}
	
	public function generateHeaderVariations() {
		return $this->__generateHeaderVariations(0, $this->lastIndex(), new IEMLVarrArr());
	}
	
	private function __generateHeaderVariations($cur, $last, $pre) {
		$coll_cvarr = array();
		
		if ($cur <= $last) {
			for ($i=0; $i<=$last-$cur+1; $i++) {
				$pre_arr = IEMLVarrArr::instanceFromVarrArr($pre);
				
				for ($j=0; $j<$i; $j++) {
					$j_arr = $this->arrayFor($cur+$j);
					
					$pre_arr->pushElement($this->stringFor($cur+$j));
					$pre_arr->pushElement($j_arr[1]);
				}
				
				if ($cur+$i <= $last) {
					$pre_arr->pushElement($this->stringFor($cur+$j));
					$pre_arr->pushElement($this->arrayFor($cur+$j));
				}
				
				array_append($coll_cvarr, $this->__generateHeaderVariations($cur+$i+1, $last, $pre_arr));
			}
		} else {
			$pre->pushElement($this->getPost());
			
			$coll_cvarr[] = $pre;
		}
		
		return $coll_cvarr;
	}
	
	
	public function IEML_vary_header($cur, $last, $pre, $post) {
		$out = NULL;
	
		if ($cur <= $last) {
			$out = array('head' => array(), 'rest' => array(), 'body' => array());
			$sub_post = $this->toPrettyString($cur+1, $last);
			$cur_arr = $this->arrayFor($cur);
			
			if (array_key_exists($cur_arr[1], IEMLVarrArr::$IEML_toVary)) {
				$sub_heads = IEMLVarrArr::$IEML_toVary[$cur_arr[1]];
				
				for ($i=0; $i<count($sub_heads); $i++) {
					$temp_sub_cvarr = IEMLVarrArr::instanceFromVarArr($this);
					$temp_sub_cvarr->setArrayFor($cur, $sub_heads[$i]);
	
					$sub = $temp_sub_cvarr->IEML_vary_header($temp_sub_cvarr, $cur, $last, $pre, $post);
	
					$span = 0;
					if (array_key_exists('head', $sub)) {
						for ($j=0; $j<count($sub['head']); $j++) {
							$span += $sub['head'][$j][1];
						}
					}
					
					$out['head'][] = array($pre.$sub_heads[$i][1].$sub_post.$post, max(1, $span));
					$out['rest'][] = $sub;
				}
			} else {
				for ($i=0; $i<count($cur_arr[0]); $i++) {
					$cur_exp = $pre.$cur_arr[0][$i].$sub_post.$post;
					
					$sub = $this->IEML_vary_header($cur+1, $last, $pre.$cur_arr[0][$i], $post);
					
					$span = 0;
					if (NULL !== $sub) {
						$out['rest'][] = $sub;
	
						if (array_key_exists('head', $sub)) {
							for ($j=0; $j<count($sub['head']); $j++) {
								$span += $sub['head'][$j][1];
							}
						}
					} else {
						$out['body'][] = IEML_gen_possibilities($cur_exp);
					}
	
					$out['head'][] = array($cur_exp, max(1, $span));
				}
			}
		
			if (isset($out) && array_key_exists('rest', $out) && count($out['rest']) == 0) unset($out['rest']);
			if (isset($out) && array_key_exists('body', $out) && count($out['body']) == 0) unset($out['body']);
		} else {
			//we've gone past the end, so just ignore
		}
	
		return $out;
	}
}

function IEML_gen_var_BFS($AST, $exp) {
	$cvinst = IEMLVarrArr::instanceFromAST($AST, $exp);
	
	return $cvinst->walkBFS();
}

function IEML_gen_possibilities($cur_exp) {
	echo pre_dump($cur_exp);
	$arr_tokens = \IEML_ExpParse\str_to_tokens($cur_exp);
	$arr_AST = \IEML_ExpParse\tokens_to_AST($arr_tokens);
	$arr_AST = \IEML_ExpParse\AST_eliminate_empties($arr_AST);
	echo pre_dump(\IEML_ExpParse\AST_to_infix_str($arr_AST, $cur_exp));
	
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
	    		//$cvarr = cons_variance_arr($tally_part_varied[$i], $exp);
	    		//echo 'cvarrs'.pre_dump(IEML_gen_possible_headers($cvarr, 0, count($cvarr)-1));
	    		$cvinst = IEMLVarrArr::instanceFromAST($tally_part_varied[$i], $exp);
	    		$variations = $cvinst->generateHeaderVariations();
	    		
	    		echo 'cvs: '.pre_dump(array_to_string($variations));
	    		
				//echo 'cvarr: '.pre_dump($cvarr);
				//echo pre_dump(IEML_cvarr_to_str($cvarr, 0, count($cvarr) - 1));
				//echo 'parts: '.pre_dump($pre, $tpv_out_str[$i][0], $tpv_out_str[$i][1], $post);
				for ($i=0; $i<1; $i++) {
	    			$sub = $variations[$i]->IEML_vary_header(0, $variations[$i]->lastIndex(), $tpv_out_str[$i][0], $tpv_out_str[$i][1]);
	    			
	    			echo 'vary_header sub: '.pre_dump($sub);
	    		}
	    		
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
