<?php

class ASTNode {
	/**
	 * type: type of ASTNode
	 * value
	 * internal
	 * children
	 * original: original string to which this ast refers to
	 * 
	 * @var mixed
	 * @access private
	 */
	private $type, $value, $internal, $children, $original;
	
	public function __construct($type, $value, $internal, $children, $original) {
		$this->type = $type;
		$this->value = is_array($value) ? $value : array($value);
		$this->internal = $internal;
		$this->children = $children;
		$this->original = $original;
		$this->str_ref = NULL;
		
		$this->set_str_ref_span();
	}
	
	public function getType() {
		return $this->type;
	}
	
	public function isInternal() {
		return $this->internal;
	}
	
	public function getChildren() {
		return $this->children;
	}
	
	public function getChild($n) {
		if ($n >= 0 && $n < count($this->children)) {
			return $this->children[$n];
		} else {
			//error;
		}
	}
	
	public function pushChild($child) {
		$this->children[] = $child;
	}
	
	public function popChild() {
		if (count($this->children) > 0) {
			return array_pop($this->children);
		} else {
			//error;
		}
	}
	
	public function pushValue($value) {
		$this->value[] = $value;
	}
	
	public function popValue() {
		if (count($this->value) > 0) {
			return array_pop($this->value);
		} else {
			//error;
		}
	}
	
	public function getValue() {
		return $this->value;
	}
	
	public function getNValue($n) {
		if ($n >=0 && $n < count($this->value)) {
			return $this->value[$n];
		}
	}
	
	public function setValue($value) {
		return $this->value;
	}
	
	public function setNValue($n, $value) {
		$this->value[$n] = $value;
	}
	
	public function getStrRef() {
		return $this->str_ref;
	}
	
	public function setStrRef($str_ref) {
		$this->str_ref = $str_ref;
	}
	
	/*
		$change values can be the following:
		0 = do nothing
		1 = recursive set
		2 = recursive expand
		3 = shallow set
		4 = shallow expand
	*/
	public function set_str_ref_span($change = 0) {
		if ($this->isInternal()) {
			if ($this->getValue()->getStrRef() !== NULL) {
				$out = IEMLToken::extreme_set($out, $this->getValue()->getStrRef());
			}
			
			for ($i=0; $i<count($AST->getChildren()); $i++) {
				$sub = $AST->getChild($i)->set_str_ref_span($change >= 3 ? 0 : $change);
				
				$out = IEMLToken::extreme_set($out, $sub);
			}
		} else {
			for ($i=0; $i<count($AST->getValue()); $i++) {
				if ($AST->getNValue($i)->getStrRef() !== NULL) {
					$out = IEMLToken::extreme_set($out, $AST->getNValue($i)->getStrRef());
				}
			}
		}
		
		if ($change == 1) {
			$AST->setStrRef($out);
		} else if ($change == 2) {
			$AST['_str_ref'] = IEMLToken::extreme_set($out, $AST['_str_ref']);
		}
		
		return $this->getStrRef();
	}
	
	public function highest_LAYER_AST() {
		if ($this->isInternal()) {
			if ($this->getValue()->getType() == 'LAYER') {
				return IEMLParser::$sym_to_lvl[$this->getValue()->getValue()];
			} else {
				$layer = 0;
				
				for ($i=0; $i<count($this->getChildren()); $i++) {
					$sub = $this->getChild($i)->highest_LAYER_AST();
					
					if ($sub > $layer) {
						$layer = $sub;
					}
				}
				
				return $layer;
			}
		} else {
			return 0;
		}
	}
	
	public function identify_L0_astructs() {
		if ($this->isInternal()) {
			if ($this->getValue()->getType() == 'PLUS' && !array_key_exists($this->getValue()['_original'], IEMLParser::$short_to_al)) {
				if ($this->highest_LAYER_AST() == 0) {
					$this->setType('L0PLUS');
				}
			}
				
			for ($i=0; $i<count($this->getChildren()); $i++) {
				$this->getChild($i)->identify_L0_astructs();
			}
		}
		
		return $AST;
	}
	
	function AST_original_str() {
		return substr_ab($this->original, $this->str_ref['_str_ref'][0], $AST['_str_ref'][1]);
	}
	
	function AST_eliminate_empties() {
		if ($this->isInternal()) {
			if ($this->getValue()->getType() == 'PLUS') {
				$out = $AST;
			} else {
				$out = new IEMLASTNode('token', $this->getValue(), TRUE, array());
				$changed = FALSE;
				
				for ($i=0; $i<count($AST['children']); $i++) {
					$sub = AST_eliminate_empties($AST['children'][$i]);
					
					if ($sub !== NULL) {
						if ($sub['internal'] && $sub['value']['type'] != 'LAYER' && count($sub['children']) == 1) {
							$changed = TRUE;
							array_append($out['children'], $sub['children']);
						} else {
							$out['children'][] = $sub;
							if (!$changed) {
								$changed = AST_to_infix_str($AST['children'][$i]) != AST_to_infix_str($sub);
							}
						}
					} else {
						$changed = TRUE;
					}
				}
				
				if (count($out['children']) == 0) {
					$out = NULL;
				} else if ($changed) {
					set_str_ref_span($out, 1);
				}
			}
		} else {
			$empty = TRUE;
			
			for ($i=0; $i<count($AST['value']) && $empty == TRUE; $i++) {
				if ($AST['value'][$i]['value'] != 'E') {
					$empty = FALSE;
				}
			}
			
			if (!$empty) {
				$out = $AST;
			}
		}
	}
	
	function AST_to_infix_str($AST, $exp = NULL, $lev = 0) {
		$out = (isset($exp) ? "\n".str_repeat('|   ', $lev) : '');
		
		if ($AST['internal'] == FALSE) {
			for ($i=0; $i<count($AST['value']); $i++) {
				$out .= $AST['value'][$i]['value'].':';
			}
			if (isset($exp)) {
				$out .= ' ['.$AST['value'][0]['_str_ref'][0].'-'.$AST['value'][0]['_str_ref'][1].': "'.AST_original_str($AST['value'][0], $exp).'"]';
			}
		} else {
			$out .= '('.$AST['value']['value'];
			if (isset($exp)) {
				$out .= ' ['.$AST['_str_ref'][0].'-'.$AST['_str_ref'][1].': "'.AST_original_str($AST, $exp).'"]';
			}
			
			for ($i=0; $i<count($AST['children']); $i++) {
				$out .= (isset($exp)? '': ' ').AST_to_infix_str($AST['children'][$i], $exp, $lev+1);
			}
			
			$out .= (isset($exp) ? "\n".str_repeat('|   ', $lev) : '').')';
		}
		
		return $out;
	}
	
	function AST_to_pretty_str($AST) {
		$out = '';
		
		if ($AST['internal']) {
			if ($AST['value']['type'] == 'LAYER') {
				$out = AST_to_pretty_str($AST['children'][0]).$AST['value']['value'];
			} else if ($AST['value']['type'] == 'PLUS') {
				$out .= '(';
				for ($i=0; $i<count($AST['children']); $i++) {
					$out .= ($i>0?$AST['value']['value']:'').AST_to_pretty_str($AST['children'][$i]);
				}
				$out .= ')';
			} else {
				for ($i=0; $i<count($AST['children']); $i++) {
					$out .= AST_to_pretty_str($AST['children'][$i]);
				}
			}
		} else {
			for ($i=0; $i<count($AST['value']); $i++) {
				$out .= $AST['value'][$i]['value'].':';
			}
		}
		
		return $out;
	}
	
	function split_by_concats($AST) {
		$out = array();
		
		if ($AST['value']['type'] == 'PLUS') {
			for ($i=0; $i<count($AST['children']); $i++) {
				array_append($out, split_by_concats($AST['children'][$i]));
			}
		} else {
			$out[] = $AST;
		}
		
		return $out;
	}
	
	function fetch_etymology_from_AST($AST, $level = 0) {
		$ret = NULL;
		
		if ($AST['internal']) {
			/*
				if we've detected a layer node and we're at lesat one level down the tree (aka a layer subnode)
				OR if we've detected something which is a character that has been expanded
			*/
			if ((($AST['value']['type'] == 'LAYER' || $AST['value']['type'] == 'L0PLUS') && $level > 0)
				|| ($AST['value']['type'] == 'PLUS' && strlen($AST['value']['_original']) == 1)) {
				return array($AST);
				
			} else {
				$ret = array();
				
				for ($i=0; $i<count($AST['children']); $i++) {
					array_append($ret, fetch_etymology_from_AST($AST['children'][$i], $level+1));
				}
			}
		} else {
			$ret = array();
			
			for ($i=0; $i<count($AST['value']); $i++) {
				$ret[] = new_AST_node('token', array($AST['value'][$i]), FALSE, array());
			}
		}
		
		return $ret;
	}
	
	function IEML_force_concat_check($AST) {
		if ($AST['internal'] && $AST['value']['type'] == 'LAYER') {
			return true;
		} else {
			if ($AST['internal']) {
				for ($i=0; $i<count($AST['children']); $i++) {
					if (IEML_force_concat_check($AST['children'][$i])) {
						return true;
					}
				}
			}
		}
		
		return false;
	}
}

?>
