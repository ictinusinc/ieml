<?php

/*
	determine highest layer
*/

include_once(dirname(__FILE__).'/IEMLASTNode.class.php');

class IEMLParser {
	private static $LAYER_STRINGS = array(':', '.', '-', "'", ',', '_', ';'),
		$VOWELS = array(
			'wo.', 'wa.', 'y.', 'o.', 'e.',
			'wu.', 'we.', 'u.', 'a.', 'i.',
			'j.', 'g.', 's.', 'b.', 't.',
			'h.', 'c.', 'k.', 'm.', 'n.',
			'p.', 'x.', 'd.', 'f.', 'l.'
		), $ATOMS = array(
			'A', 'U', 'S', 'B', 'T',
			'E', 'O', 'M', 'F', 'I'
		);
	
	public function parseString($string) {
		try {
			return ParserResult::fromAST($this->iniParse($string));
		} catch (ParseException $e) {
			return ParserResult::fromException($e);
		}
	}
	
	function iniParse($string) {
		if (is_string($string)) {
			$parse_string = trim($string);
			
			$in_stars = NULL;
			if (preg_match('/^\*\*([^\*]+)\*$/', $parse_string, $in_stars)) {
				$parse_string = trim($in_stars[1]);
			}
			
			$AST = new IEMLASTNode($string, IEMLNodeType::$ROOT, array());
			
			$AST->push($this->subParse($parse_string, NULL, 0));
			
			return $AST;
		} else {
			throw new ParseException('"IEMLParser->parseString" can only parse strings', 1);
		}
	}
	
	private function subParse($string, $highest_layer, $source_character) {
		Debug::LogInfo(pre_dump(func_get_args()));
		
		$AST = NULL;
		$str_len = strlen($string);
		
		if ($this->parenEnclosed($string)) {
			$AST = new IEMLASTNode($string, IEMLNodeType::$PAREN, array(), $source_character);
			
			$sub_layer = $this->matchInsideParens($string, $source_character);
			
			$AST->push($this->subParse($sub_layer[0], $highest_layer, $source_character + $sub_layer[1]));
		} else {
			if (!isset($highest_layer)) {
				$highest_layer = $this->getHighestLayer($string);
			}
			
			if ($highest_layer < 0 || $highest_layer >= count(IEMLParser::$LAYER_STRINGS)) {
				throw new ParseException('Layer mismatch', 4, array($source, strlen($string)));
			} else if ($highest_layer < 1) {
				$sub_layer = NULL;
				
				try {
					$sub_layer = $this->matchLayer($string, $highest_layer, $source_character);
					
					$AST = new IEMLASTNode($string, IEMLNodeType::$LAYER, array(), $source_character);
					
					if (in_array($sub_layer[0], IEMLParser::$ATOMS)) {
						$AST->push(new IEMLASTNode($sub_layer[0], IEMLNodeType::$ATOM, array(), $source_character + $sub_layer[1]));
					} else {
						throw new ParseException('Invalid Layer 0 expression "'.$sub_layer[0].'"', 6, array($source_character, strlen($sub_layer[0])));
					}
				} catch (ParseException $e) {
					if ($e->getCode() == 4) {
						$additive_relations = $this->getAdditiveRelations($string, $highest_layer, $source_character);
						
						$AST = new IEMLASTNode($string, IEMLNodeType::$ADD, array(), $source_character);
						
						for ($i=0; $i<count($additive_relations); $i++) {
							$AST->push($this->subParse($additive_relations[$i][0], $highest_layer, $source_character + $additive_relations[$i][1]));
						}
					} else {
						throw $e;
					}
				}
			} else if ($str_len <= 3 && in_array($string, IEMLParser::$VOWELS)) {
				$AST = new IEMLASTNode($string, IEMLNodeType::$LAYER, array(), $source_character);
				
				$sub_layer = $this->matchLayer($string, $highest_layer, $source_character);
				
				$AST->push(new IEMLASTNode($sub_layer[0], IEMLNodeType::$VOWEL, array(), $source_character + $sub_layer[1]));
			} else {
				//determine additive relations
				$additive_relations = $this->getAdditiveRelations($string, $highest_layer, $source_character);
				
				if (count($additive_relations) > 1) {
				
					$AST = new IEMLASTNode($string, IEMLNodeType::$ADD, array(), $source_character);
					
					for ($i=0; $i<count($additive_relations); $i++) {
						$AST->push($this->subParse($additive_relations[$i][0], $highest_layer, $source_character + $additive_relations[$i][1]));
					}
				} else {
					$sub_layer = $this->matchLayer($string, $highest_layer, $source_character);
					
					$multiplicative_relations = $this->getMultiplicativeRelations($sub_layer[0], $highest_layer-1, $source_character + $sub_layer[1]);
					
					if (count($multiplicative_relations) > 3) {
						throw new ParseException('Too many multiplicative relations at Layer '.($highest_layer-1), 3, array($source_character + $sub_layer[1], strlen($sub_layer[0])));
					} else if (count($multiplicative_relations) > 1) {
						$mul_AST = new IEMLASTNode($sub_layer[0], IEMLNodeType::$MUL, array(), $source_character + $sub_layer[1]);
						
						for ($i=0; $i<count($multiplicative_relations); $i++) {
							$mul_AST->push($this->subParse($multiplicative_relations[$i][0], $highest_layer-1, $source_character + $sub_layer[1] + $multiplicative_relations[$i][1]));
						}
					
						$AST = new IEMLASTNode($string, IEMLNodeType::$LAYER, array(), $source_character);
						$AST->push($mul_AST);
					} else {
						$AST = new IEMLASTNode($string, IEMLNodeType::$LAYER, array(), $source_character);
						$AST->push($this->subParse($sub_layer[0], $highest_layer-1, $source_character + $sub_layer[1]));
					}
				}
			}
		}
		
		return $AST;
	}
	
	private function getHighestLayer($string) {
		$highest_layer = NULL;
			
		for ($i=count(IEMLParser::$LAYER_STRINGS)-1; $i>=0; $i--) {
			$preg_result = preg_match("/.*".preg_quote(IEMLParser::$LAYER_STRINGS[$i], '/')."$/", $string);
			
			if ($preg_result) {
				$highest_layer = $i;
				
				break;
			}
		}
		
		return $highest_layer;
	}
	
	private function getAdditiveRelations($string, $highest_layer, $source_character) {
		$additive_results = NULL;
		$esc_marker = preg_quote(IEMLParser::$LAYER_STRINGS[$highest_layer], '/');
		$preg_result = preg_match_all('/ *([^'.$esc_marker.']+'.$esc_marker.')\ *\+?/', $string, $additive_results, PREG_OFFSET_CAPTURE);
		
		if ($preg_result && IEMLParser::assertAllMatched($string, $additive_results[0], PREG_OFFSET_CAPTURE)) {
			return $additive_results[1];
		} else {
			throw new ParseException('Could not match additive relation "'.$string.'" to Layer '.$highest_layer, 2, array($source_character, strlen($string)));
		}
	}
	
	private function getMultiplicativeRelations($string, $highest_layer) {
		Debug::LogInfo('getMultiplicativeRelations'.pre_dump(func_get_args()));
		
		$multiplicative_results = NULL;
		$esc_marker = preg_quote(IEMLParser::$LAYER_STRINGS[$highest_layer], '/');
		$preg_result = preg_match_all('/((?:\([^)]+\)'.$esc_marker.'?)|(?:[^'.$esc_marker.']+'.$esc_marker.')) *\*?/', $string, $multiplicative_results, PREG_OFFSET_CAPTURE);

		if ($preg_result) {
			Debug::LogInfo('$multiplicative_results: '.pre_dump($multiplicative_results[1]));
			
			return $multiplicative_results[1];
		} else {
			throw new MatchException('Could not match multiplicative relation in "'.$string.'" to highest layer: "'.$highest_layer.'"');
		}
	}
	
	private static function assertAllMatched($string, $results, $flags = 0) {
		$len = 0;
		
		if ($flags == 0) {
			for ($i=0; $i<count($results); $i++) {
				$len += strlen($results[$i]);
			}
		} else if ($flags & PREG_OFFSET_CAPTURE) {
			for ($i=0; $i<count($results); $i++) {
				$len += strlen($results[$i][0]);
			}
		}
		
		return $len == strlen($string);
	}
	
	private function matchLayer($string, $layer, $source_character) {
		$match_results = NULL;
		$esc_marker = preg_quote(IEMLParser::$LAYER_STRINGS[$layer], '/');
		$preg_result = preg_match('/^([^'.$esc_marker.']+)'.$esc_marker.'$/', trim($string), $match_results, PREG_OFFSET_CAPTURE);
		
		if ($preg_result) {
			return $match_results[1];
		} else {
			throw new ParseException('Could not match layer in string "'.$string.'" to Layer: '.$layer, 4, array($source_character, strlen($string)));
		}
	}
	
	private static function matchNestedPair($string, $pair = array('(', ')'), $return = FALSE) {
		$openCount = 0;
		$matched = FALSE;
		$start = NULL; $end = NULL;
		
		for ($i=0; $i<strlen($string); $i++) {
			if ($string[$i] == $pair[0]) {
				if ($openCount == 0 && !isset($start)) {
					$start = $i+1;
				}
				
				$matched = TRUE;
				
				$openCount++;
			} else if ($string[$i] == $pair[1]) {
				$openCount--;
				
				if ($openCount == 0 && !isset($end)) {
					$end = $i - $start;
				}
			}
		}
		
		if ($return == TRUE) {
			if ($matched && $openCount == 0) {
				return array(substr($string, $start, $end), $start);
			} else {
				return FALSE;
			}
		} else {
			return $matched && $openCount == 0;
		}
	}
	
	private function matchInsideParens($string, $source_character) {
		$match_results = IEMLParser::matchNestedPair(trim($string), array('(', ')'), TRUE);
		
		if ($match_results === FALSE) {
			throw new ParseException('Could not match parentheses in string "'.$string.'"', 5, array($source_character, strlen($string)));
		} else {
			return $match_results;
		}
	}
	
	private function parenEnclosed($string) {
		$res = IEMLParser::matchNestedPair(trim($string), array('(', ')'), TRUE);
		
		return $res && strlen($res[0]) + 2 == strlen($string);
	}
}

class ParserResult {
	private $AST, $exception, $hasError;
	
	public function __construct() {
		$this->AST = NULL;
		$this->exception = NULL;
		$this->hasError = FALSE;
	}
	
	public function AST(IEMLASTNode $AST = NULL) {
		if (isset($AST)) {
			$this->AST = $AST;
			
			return $this;
		} else {
			return $this->AST;
		}
	}
	
	public function except(ParseException $exception = NULL) {
		if (isset($exception)) {
			$this->exception = $exception;
			
			$this->hasError = TRUE;
			
			return $this;
		} else {
			return $this->exception;
		}
	}

	
	public static function fromAST(IEMLASTNode $AST) {
		$inst = new ParserResult();
		
		$inst->AST($AST);
		
		return $inst;
	}
	
	public static function fromException(ParseException $exception) {
		$inst = new ParserResult();
		
		$inst->except($exception);
		
		return $inst;
	}
	
	public function hasError() {
		return $this->hasError;
	}
}

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
