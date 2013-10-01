<?php

include_once(dirname(__FILE__).'/IEMLNodeType.class.php');

class IEMLASTNode {
	private static $VALUE_MAP = array(
		'O' => '2', 'M' => '3', 'F' => '5', 'I' => '6'
	);

	private $str, $type, $children, $source;

	public static function genEmptyNode($layer) {
		$ret = NULL;

		if ($layer <= 0) {
			$ret = new IEMLASTNode('E:', IEMLNodeType::$LAYER);
			$ret->push(new IEMLASTNode('E', IEMLNodeType::$ATOM));
		} else {
			$ret = new IEMLASTNode('', IEMLNodeType::$LAYER);
			$mul = new IEMLASTNode('', IEMLNodeType::$MUL);
			$str = '';

			for ($i=0; $i<3; $i++) {
				$mul->push(IEMLASTNode::genEmptyNode($layer - 1));
				$str .= $mul->child($i)->str();
			}

			$mul->str($str);
			$ret->push($mul);
			$ret->str($str.IEMLParser::$LAYER_STRINGS[$layer]);
		}

		return $ret;
	}
	
	public function __construct($str, $type, array $children = array(), $source = NULL) {
		$this->str = $str;
		$this->type = $type;
		$this->children = $children;
		$this->source = $source;
	}

	public function bareStr() {
		return preg_replace('/[^EUASBTOMFI]+/', '', $this->str());
	}

	public function fullExpand() {
		return $this->expandVowels()->padWithEmpties();
	}
	
	public function str($new_str = NULL) {
		if (isset($new_str)) {
			$this->str = $new_str;
			
			return $this;
		} else {
			return $this->str;
		}
	}
	
	public function children($new_children = NULL) {
		if (isset($new_children)) {
			$this->children = $new_children;
			
			return $this;
		} else {
			return $this->children;
		}
	}
	
	public function type($new_type = NULL) {
		if (isset($new_type)) {
			$this->type = $new_type;
			
			return $this;
		} else {
			return $this->type;
		}
	}
	
	public function source($source = NULL) {
		if (isset($source)) {
			$this->source = $source;
			
			return $this;
		} else {
			return $this->source;
		}
	}
	
	public function push(IEMLASTNode $child) {
		if (isset($child)) {
			$this->children[] = $child;
		} else {
			throw new Exception('"child" must be set.');
		}
		
		return $this;
	}
	
	public function childCount() {
		return count($this->children);
	}
	
	public function child($index, IEMLASTNode $new_val = NULL) {
		if ($index >= 0 && $index < $this->childCount()) {
			if (isset($new_val)) {
				$this->children[$index] = $new_val;
				
				return $this;
			} else {
				return $this->children[$index];
			}
		} else {
			throw new Exception('Child at index "'.$index.'" is out of bounds, must be '.$index.' >= 0 AND '.$index.' < '.$this->childCount().'.');
		}
	}
	
	public function toString($level = 0) {
		$out = str_repeat('    ', $level)."{\n";
		
		$out .= str_repeat('    ', $level+1).'str: "'.$this->str.'"'."\n";
		$out .= str_repeat('    ', $level+1).'type: '.IEMLNodeType::toString($this->type)."\n";
		$out .= str_repeat('    ', $level+1).'at: '.(isset($this->source) ? $this->source : 'NONE')."\n";
		$out .= str_repeat('    ', $level+1).'children:';
		if ($this->childCount() > 0) {
			$out .= ' '.$this->childCount().' ';
			
			if ($this->childCount() == 1) {
				$out .= "child\n";
			} else {
				$out .= "children\n";
			}
			
			for ($i=0; $i<$this->childCount(); $i++) {
				$out .= str_repeat('    ', $level+1).$i.":\n".$this->child($i)->toString($level+1);
			}
		} else {
			$out .= ' NONE'."\n";
		}
		$out .= str_repeat('    ', $level)."}\n";
		
		return $out;
	}
	
	public function __toString() {
		return $this->toString();
	}

	public function __reconstructString() {
		$ret = '';

		switch ($this->type()) {
			case IEMLNodeType::$ROOT:
			case IEMLNodeType::$MUL:
				for ($i=0; $i<$this->childCount(); $i++) {
					$ret .= $this->child($i)->__reconstructString();
				}
				break;
			case IEMLNodeType::$ADD:
				for ($i=0; $i<$this->childCount(); $i++) {
					$ret .= ($i>0?'+':'').$this->child($i)->__reconstructString();
				}
				break;
			case IEMLNodeType::$PAREN:
				$ret .= '('.$this->child(0)->__reconstructString().')';
				break;
			case IEMLNodeType::$ATOM:
			case IEMLNodeType::$VOWEL:
				$ret = $this->str();
				break;
			case IEMLNodeType::$LAYER:
				$highest_layer = NULL;

				$layer_string = IEMLParser::$LAYER_STRINGS[$this->getLayer()];

				for ($i=0; $i<$this->childCount(); $i++) {
					$ret .= $this->child($i)->__reconstructString().$layer_string;
				}

				break;
			default:
				//ruh roh
				throw new Exception('Invalid AST node type "'.$this->type().'"');
				break;
		}

		return $ret;
	}

	public function expandVowels() {
		$ret = NULL;

		if ($this->type() == IEMLNodeType::$VOWEL) {
			$parsed = IEMLParser::AST_or_FAIL(IEMLParser::$VOWELS_EXPAND[$this->str()].'.');

			if ($parsed['resultCode'] == 0) {
				$ret = $parsed['AST']->child(0)->child(0);
			} else {
				throw new Exception('Something terrible happened.');
			}
		} else {
			$ret = new IEMLASTNode($this->str(), $this->type());

			for ($i=0; $i<$this->childCount(); $i++) {
				$ret->push($this->child($i)->expandVowels());
			}

			$ret->str($ret->__reconstructString());
		}

		return $ret;
	}

	public function padWithEmpties($layer = NULL) {
		$ret = new IEMLASTNode($this->str(), $this->type());


		if ($this->type() == IEMLNodeType::$LAYER) {
			$layer = $this->getLayer() - 1;
		}

		for ($i=0; $i<$this->childCount(); $i++) {
			$ret->push($this->child($i)->padWithEmpties($layer));
		}

		if ($this->type() == IEMLNodeType::$MUL) {
			for ($i=$this->childCount(); $i<3; $i++) {
				$ret->push(IEMLASTNode::genEmptyNode($layer));
			}
		}

		$ret->str($ret->__reconstructString());

		return $ret;
	}

	public function getLayer() {
		if ($this->type() == IEMLNodeType::$LAYER) {
			$trim_str = trim($this->str());

			if (array_key_exists($trim_str[strlen($trim_str)-1], IEMLParser::$REVERSE_LAYER)) {
				return IEMLParser::$REVERSE_LAYER[$trim_str[strlen($trim_str)-1]];
			}
		} else {
			for ($i=0; $i<$this->childCount(); $i++) {
				$sub_layer = $this->child($i)->getLayer();

				if (isset($sub_layer)) {
					return $sub_layer;
				}
			}
		}

		return NULL;
	}

	public function getSize() {
		$total = 0;

		if ($this->type() == IEMLNodeType::$ROOT || $this->type() == IEMLNodeType::$LAYER
			|| $this->type() == IEMLNodeType::$PAREN) {
			$total = $this->child(0)->getSize();
		} else if ($this->type() == IEMLNodeType::$ADD) {
			for ($i=0; $i<$this->childCount(); $i++) {
				$total += $this->child($i)->getSize();
			}
		} else if ($this->type() == IEMLNodeType::$MUL) {
			$total = 1;

			for ($i=0; $i<$this->childCount(); $i++) {
				$total *= $this->child($i)->getSize();
			}
		} else if ($this->type() == IEMLNodeType::$ATOM) {
			$trim_str = trim($this->str());

			if (array_key_exists($trim_str, IEMLASTNode::$VALUE_MAP)) {
				$total = IEMLASTNode::$VALUE_MAP[$trim_str];
			} else {
				$total = 1;
			}
		} else {
			$total = 1;
		}

		return $total;
	}

	public function containsNodeType($type) {
		if ($this->type() == $type) {
			return TRUE;
		} else {
			for ($i=0; $i<$this->childCount(); $i++) {
				$sub = $this->child($i)->containsNodeType($type);
				if ($sub) return TRUE;
			}
		}

		return FALSE;
	}

	public function checkOrder() {
		if ($this->type() !== IEMLNodeType::$ATOM || $this->type() !== IEMLNodeType::$VOWEL) {
			for ($i=0; $i<$this->childCount(); $i++) {
				$sub = $this->child($i)->checkOrder();
				if ($sub[0] === FALSE) {
					return $sub;
				}
			}

			if ($this->type() == IEMLNodeType::$ADD) {
				for ($i=0; $i<$this->childCount()-1; $i++) {
					if (IEMLParser::fullLexicoCompare($this->child($i)->str(), $this->child($i+1)->str()) > 0) {
						return array(FALSE, array($this->child($i)->source(), strlen($this->child($i)->str())));
					}
				}
			}
		}

		return array(TRUE, -1);
	}

}

?>
