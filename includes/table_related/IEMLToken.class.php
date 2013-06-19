<?php

class IEMLToken {
	private $type, $value, $str_ref, $original, $original;
	
	public function __construct($type, $value, $str_ref = NULL, $original = NULL) {
		$this->type = $type;
		$this->value = $value;
		$this->str_ref = $str_ref;
		$this->original = $original;
	}
	
	public function getOriginal() {
		return substr($this->children, $this->str_ref[0], $this->str_ref[1] - $this->str_ref[0]);
	}
	
	public function getStrRef() {
		return $this->str_ref;
	}
	
	public function setStrRef($str_ref) {
		$this->str_ref = $str_ref;
	}
	
	public function getNStrRef($n, $val) {
		$this->str_ref[$n] = $val;
	}
	
	public function setNStrRef($n, $val) {
		$this->str_ref[$n] = $val;
	}
	
	public function getOriginal() {
		return $this->original;
	}
	
	public function setOriginal($original) {
		$this->original = $original;
	}
	
	public function getType() {
		return $this->type;
	}
	
	public function getValue() {
		return $this->value;
	}
	
	public function setValue($value) {
		$this->value = $value;
	}
	
	public static function extreme_set($a, $b) {
		$out = array($a[0], $a[1]);
		
		if ($out[0] === NULL || $out[0] > $b[0])
			$out[0] = $b[0];
		
		if ($out[1] === NULL || $out[1] < $b[1])
			$out[1] = $b[1];
		
		return $out;
	}
}

?>
