<?php

class ParseException extends Exception {
	private $source;
	/*
		exception codes:
		0: generic error, code not provided
		1: invalid string passed to parse function
		2: additive relation mismatch
		3: multiplicative relation mismatch
		4: layer mismatch
		5: parentheses mismatch
		6: invalid layer 0 relation
		7: internal parser exception
		8: script is not properly ordered
		9: could not match category
	*/
	
	public function __construct($message, $code = 0, array $source = NULL) {
		$this->source = $source;
		
		parent::__construct($message, $code, NULL);
	}
	
	public function getSource($ind = NULL) {
		if (isset($ind)) {
			return $this->source[$ind];
		} else {
			return $this->source;
		}
	}
	
	public static function formatError($string, ParseException $exc, $stack = FALSE) {
		$out = 'Error '.$exc->getCode().": \n";
		
		$out .= '    <strong>'.$exc->getMessage().'</strong>'."\n";
		
		if ($exc->getSource()) {
			$out .= '    at character '.$exc->getSource(0).":\n";
			$out .= '    '.substr($string, 0, $exc->getSource(0)).'<u>'.substr($string, $exc->getSource(0), $exc->getSource(1)).'</u>'.substr($string, $exc->getSource(0)+$exc->getSource(1));
			$out .= "\n\n";
		}
		if ($stack) {
			$out .= 'Stack: '."\n".$exc->getTraceAsString();
		}
		
		return $out;
	}
}

?>
