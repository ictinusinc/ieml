<?php
namespace IEML_ExpParse;

global $token_re_str, $vowel_to_al, $short_to_al, $lvl_to_sym, $sym_to_lvl;

$token_re_str = array(
    'ATOM' => '[EUASBT]',
    'LEVEL' => "[:.\-',_;]",
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

function new_token($type, $value, $ref = NULL) {
	return array(
		'type' => $type,
		'value' => $value,
		'_str_ref' => $ref
	);
}

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
					$out[] = new_token($reg_name, $matches[0], array($i, $i + $mlen));
					$i += $mlen - 1;
				}
				break;
			}
		}
		//TODO: raise ERROR: Syntax error, unknown character
	}
    
	return $out;
}

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
				}
				array_append($out, $sub);
				break;
			case 'VOWEL':
				$sub = tokenize('('.$vowel_to_al[$tokens[$i]['value']].')');
				for ($j=0; $j<count($sub); $j++) {
					$sub[$j]['_str_ref'] = $tokens[$i]['_str_ref'];
				}
				array_append($out, $sub);
				break;
			case 'ATOM':
				$out[] = $tokens[$i];
				$out[count($out)-1]['_str_ref'][1]++;
				break;
			case 'LEVEL':
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

function remove_implicit_mul($tokens) {
	$out = array();
	
	for ($i=0; $i<count($tokens); $i++) {
		if ($i > 0) {
			$prev_token = $tokens[$i - 1];
			switch ($tokens[$i]['type']) {
				case 'ATOM':
					if ($prev_token['type'] == 'RPAREN' || $prev_token['type'] == 'LEVEL') {
						$out[] = new_token('MUL', '*');
					}
					break;
				case 'LPAREN':
					if ($prev_token['type'] == 'RPAREN' || $prev_token['type'] == 'LEVEL' || $prev_token['type'] == 'ATOM') {
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

function str_to_tokens($str) {
	$tokens = tokenize($str);
	$prep = preprocess($tokens);
	$final = remove_implicit_mul($prep);
	
	return $final;
}

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

function set_str_ref_span(&$AST, $expand = FALSE) {
	$start = NULL; $end = NULL;
	
	if ($AST['type'] == 'internal') {
		
		if ($AST['value']['_str_ref'] !== NULL) {
			list($start, $end) = extreme_set(array($start, $end), $AST['value']['_str_ref']);
		}
		
		for ($i=0; $i<count($AST['children']); $i++) {
			$sub = set_str_ref_span($AST['children'][$i], $set);
			
			if ($AST['children'][$i]['_str_ref'] !== NULL) {
				list($start, $end) = extreme_set(array($start, $end), $sub);
			}
		}
	} else {
		for ($i=0; $i<count($AST['value']); $i++) {
			if ($AST['value'][$i]['_str_ref'] !== NULL) {
				list($start, $end) = extreme_set(array($start, $end), $AST['value'][$i]['_str_ref']);
			}
		}
	}
	if ($expand) {
		$AST['_str_ref'] = extreme_set(array($start, $end), $AST['_str_ref']);
		list($start, $end) = $AST['_str_ref'];
	} else {
		$AST['_str_ref'] = array($start, $end);
	}
	
	return array($start, $end);
}

function new_AST_node($type, $value, $children) {
	$out = array(
		'type' => $type,
		'value' => $value,
		'children' => $children
	);
	
	$out['_str_ref'] = set_str_ref_span($out);
	
	return $out;
}

function tokens_to_AST($tokens) {
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
					&& isset($trees[count($trees)-1]) && $trees[count($trees)-1]['type'] == 'value'
					&& count($trees[count($trees)-1]['value']) < 3) {
					$trees[count($trees)-1]['value'][] = $tokens[$i];
				} else {
					$trees[] = new_AST_node('value', array($tokens[$i]), array());
					
					if (isset($tokens[$i-1]) && ($tokens[$i-1]['type'] == 'RPAREN' || $tokens[$i-1]['type'] == 'ATOM')) {
						$one = array_pop($trees);
						$two = array_pop($trees);
						$trees[] = new_AST_node('internal', new_token('MUL', '*'), array($two, $one));
					}
				}
                break;
            case 'LEVEL':
                while (count($opers) > 0 && count($trees) > 1) {
					if ((isset($trees[count($trees)-1]) && $trees[count($trees)-1]['type'] == 'internal' && $trees[count($trees)-1]['value']['type'] == 'LEVEL'
						&& $sym_to_lvl[$trees[count($trees)-1]['value']['value']] >= $sym_to_lvl[$tokens[$i]['value']])
						|| (isset($trees[count($trees)-2]) && $trees[count($trees)-2]['type'] == 'internal' && $trees[count($trees)-2]['value']['type'] == 'LEVEL'
							&& $sym_to_lvl[$trees[count($trees)-2]['value']['value']] >= $sym_to_lvl[$tokens[$i]['value']])) {
						break;
					}
					
					$one = array_pop($trees);
					$two = array_pop($trees);
					$trees[] = new_AST_node('internal', array_pop($opers), array($two, $one));
				}
				
				$temp = array();
				
				while (count($trees) > 0) {
					if (isset($trees[count($trees)-1]) && $trees[count($trees)-1]['type'] == 'internal' && $trees[count($trees)-1]['value']['type'] == 'LEVEL'
						&& $sym_to_lvl[$trees[count($trees)-1]['value']['value']] >= $sym_to_lvl[$tokens[$i]['value']]) {
						break;
					}
					
					$temp[] = array_pop($trees);
				}
				
				$trees[] = new_AST_node('internal', $tokens[$i], array_reverse($temp));
                break;
            case 'PLUS':
				while (count($opers) > 0) {
					if (isset($opers[count($opers)-1]) && $opers[count($opers)-1]['type'] == 'MUL') {
						$one = array_pop($trees);
						$two = array_pop($trees);
						$trees[] = new_AST_node('internal', array_pop($opers), array($two, $one));
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
				$subtree = tokens_to_AST(array_slice($tokens, $i+1, $j-2));
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
		$trees[] = new_AST_node('internal', array_pop($opers), array($two, $one));
	}
	
	set_str_ref_span($trees[0], TRUE);
	//echo 'trees: '.pre_dump($trees);
	
	return $trees[0];
}

function AST_eliminate_empties($AST) {
	$out = NULL;
	if ($AST['type'] == 'internal') {
		$out = new_AST_node('internal', $AST['value'], array());
		for ($i=0; $i<count($AST['children']); $i++) {
			$sub = AST_eliminate_empties($AST['children'][$i]);
			
			if ($sub !== NULL) {
				if (count($sub['children']) == 1 && $sub['type'] == 'internal' && $sub['value']['type'] != 'LEVEL') {
					array_append($out['children'], $sub['children']);
				} else {
					$out['children'][] = $sub;
				}
			}
		}
		
		if (count($out['children']) == 0) {
			$out = NULL;
		} else {
			set_str_ref_span($out);
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
	$out = (isset($exp) ? "\n".str_repeat('|   ', $lev) : ' ');
	
	if ($AST['type'] == 'value') {
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
			$out .= AST_to_infix_str($AST['children'][$i], $exp, $lev+1);
		}
		
		$out .= (isset($exp) ? "\n".str_repeat('|   ', $lev) : '').')';
	}
	
	return $out;
}

function AST_original_str($AST, $exp) {
	return substr_ab($exp, $AST['_str_ref'][0], $AST['_str_ref'][1]);
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

?>
