<?php

$IEMLNodeType = array(
	'ROOT' => 0,
	'ADD' => 1,
	'LAYER' => 2,
	'MUL' => 3
);

class IEMLASTNode {
	private $str, $type;
	
	public function __construct($str, $type) {
		$this->str = $string;
		$this->type = $type;
	}
}

?>
