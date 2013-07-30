<?php

/*
	determine highest layer
*/

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
		);
	
	public function parseString($string) {
		if (is_string($string)) {
			$string = trim($string);
			
			//determine highest layer in script
			$highest_layer = $this->gethighestLayer($string);
			
			//determine additive relations
			$additive_relations = $this->getAdditiveRelations($string, $highest_layer);
			
			if (count($additive_relations) == 1) {
				$AST = new $IEMLASTNode($additive_relations[0], $IEMLNodeType['MUL']);
				
				$multiplicative_relations = $this->getMultiplicativeRelations();
			} else {
				for ($i=0; $i<count($additive_relations); $i++) {
					
				}
			}
			
			return $highest_layer;
		} else {
			throw new Exception("'IEMLParser::parseString' can only parse strings.");
		}
	}
	
	private function gethighestLayer($string) {
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
		$preg_result = preg_match_all("/ *([^".preg_quote(IEMLParser::$LAYER_STRINGS[$highest_layer], '/')."]+)".preg_quote(IEMLParser::$LAYER_STRINGS[$highest_layer], '/')." *\+?/", $string, $additive_results);
		
		if ($preg_result) {
			return $additive_relations[1];
		} else {
			throw new Exception("wot");
		}
	}
}

?>
