<?php

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
			} else if ($AST['value']['type'] == 'LAYER') {
				$this->fromAST($AST['children'][0], $exp);
				$this->pushElement($AST['value']['value']);
			} else if ($AST['value']['type'] == 'PLUS') {
				$this->pushElement(array(IEML_gen_var($AST), \IEML_ExpParse\AST_original_str($AST, $exp)));
			}
		} else {
			$gen_call = IEML_gen_var($AST);
			$this->pushElement($gen_call[0]);
		}
	}
	
	public static function instanceFromAST($AST, $exp) {
		$instance = new IEMLVarrArr();
		$instance->fromAST($AST, $exp);
		
		return $instance;
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
	
	
	public function IEML_vary_header($cur, $last, $pre = '', $post = '') {
		$out = NULL;
	
		if ($cur <= $last) {
			$out = array('head' => array(), 'rest' => array());
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
					$sub = $this->IEML_vary_header($cur+1, $last, $pre.$this->stringFor($cur).$cur_arr[0][$i], $post);
					
					$span = 0;
					if (NULL !== $sub) {
						$out['rest'][] = $sub;
	
						if (array_key_exists('head', $sub)) {
							for ($j=0; $j<count($sub['head']); $j++) {
								$span += $sub['head'][$j][1];
							}
						}
					}
	
					$out['head'][] = array($pre.$this->stringFor($cur).$cur_arr[0][$i].$sub_post.$post, max(1, $span));
				}
			}
		
			if (isset($out) && array_key_exists('rest', $out) && count($out['rest']) == 0) unset($out['rest']);
		} else {
			//we've gone past the end, so just ignore
		}
	
		return $out;
	}
}

?>
