<?php

require_once('IEMLToken.class.php');

class IEMLParser {
	public static $token_re_str = array(
	    'ATOM' => '[EUASBT]',
	    'LAYER' => "[:.\-',_;]",
	    'END' => "\*\*",
	    'START' => "\*",
	    'SHORT' => "[IOMF]",
	    'PLUS' => "\+",
	    'MUL' => "\*",
	    'LPAREN' => '\(',
	    'RPAREN' => '\)',
	    'FORSLASH' => '\/',
	    'VOWEL' => '(wo|wa|y|o|e|wu|we|u|a|i|j|g|s|b|t|h|c|k|m|n|p|x|d|f|l)',
		
		'WS' => '\s+'
	);
	public static $short_to_al = array(
	    'O' => 'U+A',
	    'M' => 'S+B+T',
	    'F' => 'U+A+S+B+T',
	    'I' => 'E+U+A+S+B+T'
	);
	public static $vowel_to_al = array(
	   'wo' => 'UUE', 'wa' => 'UAE', 'y' => 'USE', 'o' => 'UBE', 'e' => 'UTE',
	   'wu' => 'AUE', 'we' => 'AAE', 'u' => 'ASE', 'a' => 'ABE', 'i' => 'ATE',
	   'j' => 'SUE', 'g' => 'SAE', 's' => 'SSE', 'b' => 'SBE', 't' => 'STE',
	   'h' => 'BUE', 'c' => 'BAE', 'k' => 'BSE', 'm' => 'BBE', 'n' => 'BTE',
	   'p' => 'TUE', 'x' => 'TAE', 'd' => 'TSE', 'f' => 'TBE', 'l' => 'TTE'
	);
	public static $lvl_to_sym = array(':', '.', '-', "'", ',', '_', ';');
	public static $sym_to_lvl = array(':' => 0, '.' => 1, '-' => 2, "'" => 3, ',' => 4, '_' => 5, ';' => 6);
	
	private $tokens, $ref_str;
	
	/**
	 * Low level tokenize function, uses $token_re_str to tokenize a string.
	 * 
	 * @access public
	 * @param mixed $str
	 * @return void
	 */
	public static function tokenize($str) {
		$out = array();
		$strlen = strlen($str);
		
		for ($i=0; $i<$strlen; $i++) {
			foreach (IEMLParser::$token_re_str as $reg_name => $reg) {
				$matches = NULL;
				if (FALSE != preg_match('/^'.$reg.'/x', substr($str, $i), $matches, 0)) {
					if ($reg_name != 'WS') {
						$mlen = strlen($matches[0]);
						
						$out[] = new IEMLToken($reg_name, $matches[0], array($i, $i + $mlen), $matches[0]);
						
						$i += $mlen - 1;
					}
					break;
				}
			}
			//TODO: raise ERROR: Syntax error, unknown character
		}
	    
		return $out;
	}

	/**
	 * Process a token array to enforce some extra rules.
	 * 
	 * @access public
	 * @param mixed $tokens a token array
	 * @return void
	 */
	public static function preprocess($tokens) {
		$out = array();
		
		for ($i=0; $i<count($tokens); $i++) {
			switch ($tokens[$i]->getType()) {
				case 'SHORT':
					$sub = IEMLParser::tokenize('('.IEMLParser::$short_to_al[$tokens[$i]->getValue()].')');
					
					for ($j=0; $j<count($sub); $j++) {
						$sub[$j]->setStrRef($tokens[$i]->getStrRef() + 1);

						$sub[$j]->setRefStr($tokens[$i]->getRefStr());
					}
					array_append($out, $sub);
					
					break;
					
				case 'VOWEL':
					$sub = IEMLParser::tokenize('('.IEMLParser::$vowel_to_al[$tokens[$i]->getValue()].')');
					
					for ($j=0; $j<count($sub); $j++) {
						$sub[$j]->setStrRef($tokens[$i]->getStrRef());
						
						$sub[$j]->setRefStr($tokens[$i]->getRefStr());
					}
					array_append($out, $sub);
					
					break;
					
				case 'ATOM':
					$out[] = $tokens[$i];
					
					$out[count($out)-1]->setNStrRef(1, $out[count($out)-1]->getNStrRef(1) + 1);
					
					break;
					
				case 'LAYER':
					if ($tokens[$i]->getValue() != ':') {
						$out[] = $tokens[$i];
					}
					
					break;
					
				default:
					$out[] = $tokens[$i];
					
					break;
			}
		}
		
		return $out;
	}
	
	/**
	 * Removes implicit multiplication inherent in most IEML script.
	 * 
	 * @access public
	 * @param mixed $tokens a token array
	 * @return void
	 */
	public static function remove_implicit_mul($tokens) {
		$out = array();
		
		for ($i=0; $i<count($tokens); $i++) {
			if ($i > 0) {
				$prev_token = $tokens[$i - 1];
				switch ($tokens[$i]->getType()) {
					case 'ATOM':
						if ($prev_token->getType() == 'RPAREN' || $prev_token->getType() == 'LAYER') {
							$out[] = new IEMLToken('MUL', '*');
						}
						
						break;
						
					case 'LPAREN':
						if ($prev_token->getType() == 'RPAREN' || $prev_token->getType() == 'LAYER' || $prev_token->getType() == 'ATOM') {
							$out[] = new IEMLToken('MUL', '*');
						}
						
						break;
						
					default: break;
				}
			}
			
			$out[] = $tokens[$i];
		}
		
		return $out;
	}
	
	/**
	 * Tokenize a string according to IEML grammar.
	 * 
	 * @access public
	 * @param mixed $str a string to tokenize
	 * @return void
	 */
	public static function str_to_tokens($str) {
		$instance = new IEMLParser();
		$instance->setStr($str);
		
		$instance->tokens = IEMLParser::tokenize($str);
		$instance->tokens = IEMLParser::preprocess($this->tokens);
		$instance->tokens = IEMLParser::remove_implicit_mul($this->tokens);
		
		return $instance;
	}
	
	public static function token_SYA($tokens) {
		global $sym_to_lvl;
		$opers = array();
		$trees = array();
		
		for ($i = 0; $i < count($tokens); $i++) {
			switch ($tokens[$i]->getType()) {
	            case 'START':
	            	//we've started, nothing to do here
	                break;
	            case 'ATOM':
					if (isset($tokens[$i-1]) && $tokens[$i-1]->getType() == 'ATOM'
						&& isset($trees[count($trees)-1]) && $trees[count($trees)-1]->isInternal() == FALSE
						&& count($trees[count($trees)-1]->getValue()) < 3) {
						$trees[count($trees)-1]->pushValue($tokens[$i]);
					} else {
						$trees[] = new IEMLASTNode('token', array($tokens[$i]), FALSE, array());
						
						if (isset($tokens[$i-1]) && ($tokens[$i-1]->getType() == 'RPAREN' || $tokens[$i-1]->getType() == 'ATOM')) {
							$one = array_pop($trees);
							$two = array_pop($trees);
							$trees[] = new IEMLASTNode('token', new_token('MUL', '*'), TRUE, array($two, $one));
						}
					}
	                break;
	            case 'LAYER':
	                while (count($opers) > 0 && count($trees) > 1) {
						if ((isset($trees[count($trees)-1]) && $trees[count($trees)-1]->isInternal() && $trees[count($trees)-1]->getValue()->getType() == 'LAYER'
							&& $sym_to_lvl[$trees[count($trees)-1]->getValue()->getValue()] >= $sym_to_lvl[$tokens[$i]->getValue()])
							|| (isset($trees[count($trees)-2]) && $trees[count($trees)-2]->isInternal() && $trees[count($trees)-2]->getValue()->getType() == 'LAYER'
								&& $sym_to_lvl[$trees[count($trees)-2]->getValue()->getValue()] >= $sym_to_lvl[$tokens[$i]->getValue()])) {
							break;
						}
						
						$one = array_pop($trees);
						$two = array_pop($trees);
						$trees[] = new IEMLASTNode('token', array_pop($opers), TRUE, array($two, $one));
					}
					
					$temp = array();
					
					while (count($trees) > 0) {
						if (isset($trees[count($trees)-1]) && $trees[count($trees)-1]->isInternal() && $trees[count($trees)-1]->getValue()->getType() == 'LAYER'
							&& $sym_to_lvl[$trees[count($trees)-1]->getValue()->getValue()] >= $sym_to_lvl[$tokens[$i]->getValue()]) {
							break;
						}
						
						$temp[] = array_pop($trees);
					}
					
					$trees[] = new IEMLASTNode('token', $tokens[$i], TRUE, array_reverse($temp));
	                break;
	            case 'PLUS':
					while (count($opers) > 0) {
						if (isset($opers[count($opers)-1]) && $opers[count($opers)-1]->getType() == 'MUL') {
							$one = array_pop($trees);
							$two = array_pop($trees);
							$trees[] = new IEMLASTNode('token', array_pop($opers), TRUE, array($two, $one));
						} else break;
					}
					
					$opers[] = $tokens[$i];
	                break;
	            case 'MUL':
					$opers[] = $tokens[$i];
	                break;
	            case 'LPAREN':
					$j = 1;
					$pc = 1;
					
					while ($i+$j < count($tokens) && $pc > 0) {
						if ($tokens[$i+$j]->getType() == 'RPAREN') {
							$pc--;
						} else if ($tokens[$i+$j]->getType() == 'LPAREN') {
							$pc++;
						}
						$j++;
					}
					$subtree = token_SYA(array_slice($tokens, $i+1, $j-2));
					//$subtree['_str_ref'] = extreme_set($subtree['_str_ref'], $tokens[$i + $j - 1]['_str_ref']);
					$subtree['_str_ref'] = extreme_set($tokens[$i]['_str_ref'], $tokens[$i + $j - 1]['_str_ref']);
					
					$trees[] = $subtree;
					
					$i += $j - 1;
	                break;
	            case 'RPAREN':
					//TODO: raise ERROR: mismatched parens
	                break;
	            case 'FORSLASH':
					//TODO: raise ERROR: no forward slashes allowed
					break;
	            case 'END':
	            	//aaand we're done
	                goto tstream;
				default:
					//TODO: raise ERROR: invalid token
	        }
		}
		tstream:
		
		while (count($opers) > 0 && count($trees) > 1) {
			$one = array_pop($trees);
			$two = array_pop($trees);
			$trees[] = new IEMLASTNode('token', array_pop($opers), TRUE, array($two, $one));
		}
		
		set_str_ref_span($trees[0], 2);
		//echo 'trees: '.pre_dump($trees);
		
		return $trees[0];
	}
	
	function tokens_to_AST($tokens) {
		$AST = token_SYA($tokens);
		return identify_L0_astructs($AST);
	}
}

?>
