<?php

class IEMLNodeType {
	public static 
		$ROOT = 0,
		$ADD = 1,
		$LAYER = 2,
		$MUL = 3,
		$ATOM = 4,
		$VOWEL = 5,
		$PAREN = 6;
	
	public static function toString($type) {
		$ret = NULL;
		
		switch ($type) {
			case IEMLNodeType::$ROOT:
				$ret = 'ROOT';
				break;
			case IEMLNodeType::$ADD:
				$ret = 'ADD';
				break;
			case IEMLNodeType::$LAYER:
				$ret = 'LAYER';
				break;
			case IEMLNodeType::$MUL:
				$ret = 'MUL';
				break;
			case IEMLNodeType::$ATOM:
				$ret = 'ATOM';
				break;
			case IEMLNodeType::$VOWEL:
				$ret = 'VOWEL';
				break;
			case IEMLNodeType::$PAREN:
				$ret = 'PAREN';
				break;
			default:
				throw new Exception('No such type: "'.$type.'"');
		}
		
		return $ret;
	}
}

?>
