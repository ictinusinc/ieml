<?php
namespace IEML_ExpParse;

global $token_re_str, $vowel_to_al, $short_to_al, $lvl_to_sym, $sym_to_lvl;

$token_re_str = array(
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

$short_to_al = array(
    'O' => 'U+A',
    'M' => 'S+B+T',
    'F' => 'U+A+S+B+T',
    'I' => 'E+U+A+S+B+T'
);

$vowel_to_al = array(
   'wo' => 'UUE', 'wa' => 'UAE', 'y' => 'USE', 'o' => 'UBE', 'e' => 'UTE',
   'wu' => 'AUE', 'we' => 'AAE', 'u' => 'ASE', 'a' => 'ABE', 'i' => 'ATE',
   'j' => 'SUE', 'g' => 'SAE', 's' => 'SSE', 'b' => 'SBE', 't' => 'STE',
   'h' => 'BUE', 'c' => 'BAE', 'k' => 'BSE', 'm' => 'BBE', 'n' => 'BTE',
   'p' => 'TUE', 'x' => 'TAE', 'd' => 'TSE', 'f' => 'TBE', 'l' => 'TTE'
);
$lvl_to_sym = array(':', '.', '-', "'", ',', '_', ';');
$sym_to_lvl = array(':' => 0, '.' => 1, '-' => 2, "'" => 3, ',' => 4, '_' => 5, ';' => 6);

/**
 * Create a new token, which is just a map.
 * 
 * @access public
 * @param mixed $type Token type (PLUS, MUL, LAYER, etc.) 
 * @param mixed $value string value
 * @param mixed $ref (default: NULL) a vector containing positions in the full original expression to which this token refers
 * @param mixed $ori (default: NULL) exactly the string to which this token refers
 * @return void
 */
function new_token($type, $value, $ref = NULL, $ori = NULL) {
	return array(
		'type' => $type,
		'value' => $value,
		'_str_ref' => $ref,
		'_original' => $ori
	);
}

/**
 * Low level tokenize function, uses $token_re_str to tokenize a string.
 * 
 * @access public
 * @param mixed $str
 * @return void
 */
function tokenize($str) {
	global $token_re_str;
	
	$out = array();
	$strlen = strlen($str);
	
	for ($i=0; $i<$strlen; $i++) {
		foreach ($token_re_str as $reg_name => $reg) {
			$matches = NULL;
			if (FALSE != preg_match('/^'.$reg.'/x', substr($str, $i), $matches, 0)) {
				if ($reg_name != 'WS') {
					$mlen = strlen($matches[0]);
					
					$out[] = new_token($reg_name, $matches[0], array($i, $i + $mlen), $matches[0]);
					
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
function preprocess($tokens) {
	global $vowel_to_al, $short_to_al;
	$out = array();
	
	for ($i=0; $i<count($tokens); $i++) {
		switch ($tokens[$i]['type']) {
			case 'SHORT':
				$sub = tokenize('('.$short_to_al[$tokens[$i]['value']].')');
				for ($j=0; $j<count($sub); $j++) {
					$sub[$j]['_str_ref'] = $tokens[$i]['_str_ref'];
					$sub[$j]['_str_ref'][1]++;
					$sub[$j]['_original'] = $tokens[$i]['_original'];
				}
				array_append($out, $sub);
				break;
			case 'VOWEL':
				$sub = tokenize('('.$vowel_to_al[$tokens[$i]['value']].')');
				for ($j=0; $j<count($sub); $j++) {
					$sub[$j]['_str_ref'] = $tokens[$i]['_str_ref'];
					$sub[$j]['_original'] = $tokens[$i]['_original'];
				}
				array_append($out, $sub);
				break;
			case 'ATOM':
				$out[] = $tokens[$i];
				$out[count($out)-1]['_str_ref'][1]++;
				break;
			case 'LAYER':
				if ($tokens[$i]['value'] != ':') {
					$out[] = $tokens[$i];
				}
				break;
			default:
				$out[] = $tokens[$i];
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
function remove_implicit_mul($tokens) {
	$out = array();
	
	for ($i=0; $i<count($tokens); $i++) {
		if ($i > 0) {
			$prev_token = $tokens[$i - 1];
			switch ($tokens[$i]['type']) {
				case 'ATOM':
					if ($prev_token['type'] == 'RPAREN' || $prev_token['type'] == 'LAYER') {
						$out[] = new_token('MUL', '*');
					}
					break;
				case 'LPAREN':
					if ($prev_token['type'] == 'RPAREN' || $prev_token['type'] == 'LAYER' || $prev_token['type'] == 'ATOM') {
						$out[] = new_token('MUL', '*');
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
function str_to_tokens($str) {
	$tokens = tokenize($str);
	$prep = preprocess($tokens);
	$final = remove_implicit_mul($prep);
	
	return $final;
}

/**
 * Joins all tokens to form a string.
 * 
 * @access public
 * @param mixed $tokens a token stream
 * @return void
 */
function tokens_to_str($tokens) {
	$str = '';
	
	for ($i=0; $i<count($tokens); $i++) {
		$str .= $tokens[$i]['value'];
	}
	
	return $str;
}

function extreme_set($a, $b) {
	$out = array($a[0], $a[1]);
	
	if ($out[0] === NULL || $out[0] > $b[0])
		$out[0] = $b[0];
	
	if ($out[1] === NULL || $out[1] < $b[1])
		$out[1] = $b[1];
	
	return $out;
}
/*
	$change values canbe the following:
	0 = do nothing
	1 = recursive set
	2 = recursive expand
	3 = shallow set
	4 = shallow expand
*/
function set_str_ref_span(&$AST, $change = 0) {
	$out = array(NULL, NULL);
	
	if ($AST['internal']) {
		if ($AST['value']['_str_ref'] !== NULL) {
			$out = extreme_set($out, $AST['value']['_str_ref']);
		}
		
		for ($i=0; $i<count($AST['children']); $i++) {
			$sub = set_str_ref_span($AST['children'][$i], ($change >= 3 ? 0 : $change));
			
			$out = extreme_set($out, $sub);
		}
	} else {
		for ($i=0; $i<count($AST['value']); $i++) {
			if ($AST['value'][$i]['_str_ref'] !== NULL) {
				$out = extreme_set($out, $AST['value'][$i]['_str_ref']);
			}
		}
	}
	
	if ($change == 1) {
		$AST['_str_ref'] = $out;
	} else if ($change == 2) {
		$AST['_str_ref'] = extreme_set($out, $AST['_str_ref']);
		$out = $AST['_str_ref'];
	}
	
	return $out;
}

/**
 * new_AST_node function.
 * 
 * @access public
 * @param mixed $type
 * @param mixed $value
 * @param mixed $internal
 * @param mixed $children
 * @return void
 */
function new_AST_node($type, $value, $internal, $children) {
	$out = array(
		'type' => $type,
		'value' => $value,
		'internal' => $internal,
		'children' => $children
	);
	
	$out['_str_ref'] = set_str_ref_span($out);
	
	return $out;
}

function token_SYA($tokens) {
	global $sym_to_lvl;
	$opers = array();
	$trees = array();
	
	for ($i = 0; $i < count($tokens); $i++) {
		switch ($tokens[$i]['type']) {
            case 'START':
            	//we've started, nothing to do here
                break;
            case 'ATOM':
				if (isset($tokens[$i-1]) && $tokens[$i-1]['type'] == 'ATOM'
					&& isset($trees[count($trees)-1]) && $trees[count($trees)-1]['internal'] == FALSE
					&& count($trees[count($trees)-1]['value']) < 3) {
					$trees[count($trees)-1]['value'][] = $tokens[$i];
				} else {
					$trees[] = new_AST_node('token', array($tokens[$i]), FALSE, array());
					
					if (isset($tokens[$i-1]) && ($tokens[$i-1]['type'] == 'RPAREN' || $tokens[$i-1]['type'] == 'ATOM')) {
						$one = array_pop($trees);
						$two = array_pop($trees);
						$trees[] = new_AST_node('token', new_token('MUL', '*'), TRUE, array($two, $one));
					}
				}
                break;
            case 'LAYER':
                while (count($opers) > 0 && count($trees) > 1) {
					if ((isset($trees[count($trees)-1]) && $trees[count($trees)-1]['internal'] && $trees[count($trees)-1]['value']['type'] == 'LAYER'
						&& $sym_to_lvl[$trees[count($trees)-1]['value']['value']] >= $sym_to_lvl[$tokens[$i]['value']])
						|| (isset($trees[count($trees)-2]) && $trees[count($trees)-2]['internal'] && $trees[count($trees)-2]['value']['type'] == 'LAYER'
							&& $sym_to_lvl[$trees[count($trees)-2]['value']['value']] >= $sym_to_lvl[$tokens[$i]['value']])) {
						break;
					}
					
					$one = array_pop($trees);
					$two = array_pop($trees);
					$trees[] = new_AST_node('token', array_pop($opers), TRUE, array($two, $one));
				}
				
				$temp = array();
				
				while (count($trees) > 0) {
					if (isset($trees[count($trees)-1]) && $trees[count($trees)-1]['internal'] && $trees[count($trees)-1]['value']['type'] == 'LAYER'
						&& $sym_to_lvl[$trees[count($trees)-1]['value']['value']] >= $sym_to_lvl[$tokens[$i]['value']]) {
						break;
					}
					
					$temp[] = array_pop($trees);
				}
				
				$trees[] = new_AST_node('token', $tokens[$i], TRUE, array_reverse($temp));
                break;
            case 'PLUS':
				while (count($opers) > 0) {
					if (isset($opers[count($opers)-1]) && $opers[count($opers)-1]['type'] == 'MUL') {
						$one = array_pop($trees);
						$two = array_pop($trees);
						$trees[] = new_AST_node('token', array_pop($opers), TRUE, array($two, $one));
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
					if ($tokens[$i+$j]['type'] == 'RPAREN') {
						$pc--;
					} else if ($tokens[$i+$j]['type'] == 'LPAREN') {
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
		$trees[] = new_AST_node('token', array_pop($opers), TRUE, array($two, $one));
	}
	
	set_str_ref_span($trees[0], 2);
	//echo 'trees: '.pre_dump($trees);
	
	return $trees[0];
}

function highest_LAYER_AST($AST) {
	global $sym_to_lvl;
	if ($AST['internal']) {
		if ($AST['value']['type'] == 'LAYER') {
			return $sym_to_lvl[$AST['value']['value']];
		} else {
			$layer = 0;
			
			for ($i=0; $i<count($AST['children']); $i++) {
				$sub = highest_LAYER_AST($AST['children'][$i]);
				
				if ($sub > $layer) {
					$layer = $sub;
				}
			}
			
			return $layer;
		}
	} else {
		return 0;
	}
}

function identify_L0_astructs(&$AST) {
	global $short_to_al;

	if ($AST['internal']) {
		if ($AST['value']['type'] == 'PLUS' && !array_key_exists($AST['value']['_original'], $short_to_al)) {
			if (highest_LAYER_AST($AST) == 0) {
				$AST['type'] = 'L0PLUS';
			}
		}
			
		for ($i=0; $i<count($AST['children']); $i++) {
			$sub = identify_L0_astructs($AST['children'][$i]);
		}
	}
	
	return $AST;
}

function tokens_to_AST($tokens) {
	$AST = token_SYA($tokens);
	return identify_L0_astructs($AST);
}

function AST_eliminate_empties($AST) {
	$out = NULL;
	if ($AST['internal']) {
		if ($AST['value']['type'] == 'PLUS') {
			$out = $AST;
		} else {
			$out = new_AST_node('token', $AST['value'], TRUE, array());
			$changed = FALSE;
			
			for ($i=0; $i<count($AST['children']); $i++) {
				$sub = AST_eliminate_empties($AST['children'][$i]);
				
				if ($sub !== NULL) {
					if ($sub['internal'] && $sub['value']['type'] != 'LAYER' && count($sub['children']) == 1) {
						$changed = TRUE;
						array_append($out['children'], $sub['children']);
					} else {
						$out['children'][] = $sub;
						if (!$changed) {
							$changed = AST_to_infix_str($AST['children'][$i]) != AST_to_infix_str($sub);
						}
					}
				} else {
					$changed = TRUE;
				}
			}
			
			if (count($out['children']) == 0) {
				$out = NULL;
			} else if ($changed) {
				set_str_ref_span($out, 1);
			}
		}
	} else {
		$empty = TRUE;
		
		for ($i=0; $i<count($AST['value']) && $empty == TRUE; $i++) {
			if ($AST['value'][$i]['value'] != 'E') {
				$empty = FALSE;
			}
		}
		
		if (!$empty) {
			$out = $AST;
		}
	}
	
	return $out;
}

function AST_to_infix_str($AST, $exp = NULL, $lev = 0) {
	$out = (isset($exp) ? "\n".str_repeat('|   ', $lev) : '');
	
	if ($AST['internal'] == FALSE) {
		for ($i=0; $i<count($AST['value']); $i++) {
			$out .= $AST['value'][$i]['value'].':';
		}
		if (isset($exp)) {
			$out .= ' ['.$AST['value'][0]['_str_ref'][0].'-'.$AST['value'][0]['_str_ref'][1].': "'.AST_original_str($AST['value'][0], $exp).'"]';
		}
	} else {
		$out .= '('.$AST['value']['value'];
		if (isset($exp)) {
			$out .= ' ['.$AST['_str_ref'][0].'-'.$AST['_str_ref'][1].': "'.AST_original_str($AST, $exp).'"]';
		}
		
		for ($i=0; $i<count($AST['children']); $i++) {
			$out .= (isset($exp)? '': ' ').AST_to_infix_str($AST['children'][$i], $exp, $lev+1);
		}
		
		$out .= (isset($exp) ? "\n".str_repeat('|   ', $lev) : '').')';
	}
	
	return $out;
}

function AST_original_str($AST, $exp) {
	return substr_ab($exp, $AST['_str_ref'][0], $AST['_str_ref'][1]);
}

function AST_to_pretty_str($AST) {
	$out = '';
	
	if ($AST['internal']) {
		if ($AST['value']['type'] == 'LAYER') {
			$out = AST_to_pretty_str($AST['children'][0]).$AST['value']['value'];
		} else if ($AST['value']['type'] == 'PLUS') {
			$out .= '(';
			for ($i=0; $i<count($AST['children']); $i++) {
				$out .= ($i>0?$AST['value']['value']:'').AST_to_pretty_str($AST['children'][$i]);
			}
			$out .= ')';
		} else {
			for ($i=0; $i<count($AST['children']); $i++) {
				$out .= AST_to_pretty_str($AST['children'][$i]);
			}
		}
	} else {
		for ($i=0; $i<count($AST['value']); $i++) {
			$out .= $AST['value'][$i]['value'].':';
		}
	}
	
	return $out;
}

function split_by_concats($AST) {
	$out = array();
	
	if ($AST['value']['type'] == 'PLUS') {
		for ($i=0; $i<count($AST['children']); $i++) {
			array_append($out, split_by_concats($AST['children'][$i]));
		}
	} else {
		$out[] = $AST;
	}
	
	return $out;
}

function fetch_etymology_from_AST($AST, $level = 0) {
	$ret = NULL;
	
	if ($AST['internal']) {
		if ($AST['value']['type'] == 'LAYER' && $level > 0) {
			return array($AST);
		} else {
			$ret = array();
			
			for ($i=0; $i<count($AST['children']); $i++) {
				array_append($ret, fetch_etymology_from_AST($AST['children'][$i], $level+1));
			}
		}
	} else {
		$ret = array();
		
		for ($i=0; $i<count($AST['value']); $i++) {
			$ret[] = new_AST_node('token', array($AST['value'][$i]), FALSE, array());
		}
	}
	
	return $ret;
}

?>
