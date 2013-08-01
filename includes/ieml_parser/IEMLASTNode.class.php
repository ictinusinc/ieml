<?php

include_once(dirname(__FILE__).'/IEMLNodeType.class.php');

class IEMLASTNode {
	private $str, $type, $children;
	
	public function __construct($str, $type, array $children = array()) {
		$this->str = $str;
		$this->type = $type;
		$this->children = $children;
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
	
	public function push(IEMLASTNode $child) {
		if (isset($child)) {
			$this->children[] = $child;
		} else {
			throw new Exception('"child" must be set.');
		}
		
		return $this;
	}
	
	public function child_count() {
		return count($this->children);
	}
	
	public function child($index, IEMLASTNode $new_val = NULL) {
		if ($index >= 0 && $index < $this->child_count()) {
			if (isset($new_val)) {
				$this->children[$index] = $new_val;
				
				return $this;
			} else {
				return $this->children[$index];
			}
		} else {
			throw new Exception('Child at index "'.$index.'" is out of bounds, so: '.$index.' < 0 OR '.$index.' >= '.$this->child_count().'.');
		}
	}
	
	public function toString($level = 0) {
		$out = str_repeat('    ', $level)."{\n";
		
		$out .= str_repeat('    ', $level+1).'str: "'.$this->str.'"'."\n";
		$out .= str_repeat('    ', $level+1).'type: '.IEMLNodeType::toString($this->type)."\n";
		$out .= str_repeat('    ', $level+1).'children:';
		if ($this->child_count() > 0) {
			$out .= ' '.$this->child_count().' ';
			
			if ($this->child_count() == 1) {
				$out .= "child\n";
			} else {
				$out .= "children\n";
			}
			
			for ($i=0; $i<$this->child_count(); $i++) {
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
}

?>
