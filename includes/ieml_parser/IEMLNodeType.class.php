<?php

class IEMLNodeType {
	public static 
		$ROOT = 0,
		$ADD = 1,
		$LAYER = 2,
		$MUL = 3,
		$ATOM = 4,
		$VOWEL = 5,
		$PAREN = 6,
		$CATEGORY = 7;
	
	public static function toString($type) {
		switch ($type) {
			case IEMLNodeType::$ROOT:
				return 'ROOT';
			case IEMLNodeType::$ADD:
				return 'ADD';
			case IEMLNodeType::$LAYER:
				return 'LAYER';
			case IEMLNodeType::$MUL:
				return 'MUL';
			case IEMLNodeType::$ATOM:
				return 'ATOM';
			case IEMLNodeType::$VOWEL:
				return 'VOWEL';
			case IEMLNodeType::$PAREN:
				return 'PAREN';
			case IEMLNodeType::$CATEGORY:
				return 'CATEGORY';
			default:
				throw new Exception('No such type: "'.$type.'"');
		}
	}
}

?>
