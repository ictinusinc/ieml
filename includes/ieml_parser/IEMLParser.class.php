<?php

/*
	determine highest layer
*/

include_once(dirname(__FILE__).'/IEMLASTNode.class.php');

class IEMLParser {
	private static $LAYER_STRINGS = array(':', '.', '-', "'", ',', '_', ';'),
		$INV_LAYER_STRING = array(
			':' => 0,
			'.' => 1,
			'-' => 2,
			"'" => 3,
			',' => 4,
			'_' => 5,
			';' => 6
		), $VOWELS = array(
			'wo.', 'wa.', 'y.', 'o.', 'e.',
			'wu.', 'we.', 'u.', 'a.', 'i.',
			'j.', 'g.', 's.', 'b.', 't.',
			'h.', 'c.', 'k.', 'm.', 'n.',
			'p.', 'x.', 'd.', 'f.', 'l.'
		);
	
	public function parseString($string) {
		if (is_string($string)) {
			$AST = new IEMLASTNode($string, IEMLNodeType::$ROOT, array());
			
			$string = trim($string);
			
			//determine highest layer in script
			$highest_layer = $this->getHighestLayer($string);
			
			$AST->push($this->subParse($string, $highest_layer));
			
			return $AST;
		} else {
			throw new Exception("'IEMLParser::parseString' can only parse strings.");
		}
	}
	
	private function subParse($string, $highest_layer) {
		$AST = NULL;
		$str_len = strlen($string);
		
		if ($this->parenEnclosed($string)) {
			$sub_layer = $this->matchInsideParens($string);
			
			$AST = $this->subParse($sub_layer, $highest_layer);
		} else if ($highest_layer < 1) {
			$sub_layer = NULL;
			
			try {
				$sub_layer = $this->matchLayer($string, $highest_layer);
				
				$AST = new IEMLASTNode($string, IEMLNodeType::$LAYER);
			
				$AST->push(new IEMLASTNode($sub_layer, IEMLNodeType::$ATOM));
			} catch (MatchException $e) {
				$additive_relations = $this->getAdditiveRelations($string, $highest_layer);
				
				$AST = new IEMLASTNode($string, IEMLNodeType::$ADD);
				
				for ($i=0; $i<count($additive_relations); $i++) {
					$AST->push($this->subParse($additive_relations[$i], $highest_layer));
				}
			}
		} else if ($str_len <= 3 && in_array($string, IEMLParser::$VOWELS)) {
			$AST = new IEMLASTNode($string, IEMLNodeType::$LAYER);
			
			$sub_layer = $this->matchLayer($string, $highest_layer);
			
			$AST->push(new IEMLASTNode($sub_layer, IEMLNodeType::$VOWEL));
		} else {
			//determine additive relations
			$additive_relations = $this->getAdditiveRelations($string, $highest_layer);
			
			if (count($additive_relations) == 1) {
				$sub_layer = $this->matchLayer($string, $highest_layer);
				
				$multiplicative_relations = $this->getMultiplicativeRelations($sub_layer, $highest_layer-1);
				
				if (count($multiplicative_relations) == 1) {
					$AST = new IEMLASTNode($string, IEMLNodeType::$LAYER);
					$AST->push($this->subParse($sub_layer, $highest_layer-1));
				} else {
					$mul_AST = new IEMLASTNode($sub_layer, IEMLNodeType::$MUL);
					
					for ($i=0; $i<count($multiplicative_relations); $i++) {
						$mul_AST->push($this->subParse($multiplicative_relations[$i], $highest_layer-1));
					}
				
					$AST = new IEMLASTNode($string, IEMLNodeType::$LAYER);
					$AST->push($mul_AST);
				}
			} else {
				$AST = new IEMLASTNode($string, IEMLNodeType::$ADD);
				
				for ($i=0; $i<count($additive_relations); $i++) {
					$AST->push($this->subParse($additive_relations[$i], $highest_layer));
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
	
	private function getAdditiveRelations($string, $highest_layer) {
		$additive_results = NULL;
		$esc_marker = preg_quote(IEMLParser::$LAYER_STRINGS[$highest_layer], '/');
		$preg_result = preg_match_all('/ *([^'.$esc_marker.']+'.$esc_marker.')\ *\+?/', $string, $additive_results);
		
		if ($preg_result) {
			return $additive_results[1];
		} else {
			throw new MatchException('Could not match additive relation in "'.$string.'" to highest layer: "'.$highest_layer.'"');
		}
	}
	
	private function getMultiplicativeRelations($string, $highest_layer) {
		$multiplicative_results = NULL;
		$esc_marker = preg_quote(IEMLParser::$LAYER_STRINGS[$highest_layer], '/');
		$preg_result = preg_match_all('/((?:\([^)]+\)'.$esc_marker.'?)|(?:[^'.$esc_marker.']+'.$esc_marker.')) *\*?/', $string, $multiplicative_results);
		
		if ($preg_result) {
			return $multiplicative_results[1];
		} else {
			throw new MatchException('Could not match multiplicative relation in "'.$string.'" to highest layer: "'.$highest_layer.'"');
		}
	}
	
	private function matchLayer($string, $layer) {
		$match_results = NULL;
		$esc_marker = preg_quote(IEMLParser::$LAYER_STRINGS[$layer], '/');
		$preg_result = preg_match('/^([^'.$esc_marker.']+)'.$esc_marker.'$/', trim($string), $match_results);
		
		if ($preg_result) {
			return $match_results[1];
		} else {
			throw new MatchException('Could not match layer in string "'.$string.'" to layer "'.$layer.'"');
		}
	}
	
	private function matchInsideParens($string) {
		$match_results = NULL;
		$preg_result = preg_match('/\(([^\)]+)\)/', $string, $match_results);
		
		if ($preg_result) {
			return $match_results[1];
		} else {
			throw new MatchException('Could not match parentheses in string "'.$string.'"');
		}
	}
	
	private function parenEnclosed($string) {
		return preg_match('/^\([^\)]+\)$/', trim($string));
	}
}

class MatchException extends Exception {
	
}

?>
