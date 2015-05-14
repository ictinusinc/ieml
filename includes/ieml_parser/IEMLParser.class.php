<?php

include_once(dirname(__FILE__).'/IEMLASTNode.class.php');
include_once(dirname(__FILE__).'/ParserException.class.php');
include_once(dirname(__FILE__).'/ParserResult.class.php');

function loffstrim($string, $character_mask = " \t\n\r\0\x0B")
{
	$mask_quoted = preg_quote($character_mask);
	$match_results = NULL;
	$preg_result = preg_match('/^[' . $mask_quoted . ']*(.+)$/', rtrim($string), $match_results, PREG_OFFSET_CAPTURE);

	if ($preg_result)
	{
		return $match_results[1];
	}
	else
	{
		return array($string, 0);
	}
}

class IEMLParser
{
	public $visual_expression = FALSE;

	public static $LAYER_STRINGS = array(':', '.', '-', "'", ',', '_', ';');
	public static $REVERSE_LAYER = array(':' => 0, '.' => 1, '-' => 2, "'" => 3, ',' => 4, '_' => 5, ';' => 6);
	public static $VOWELS = array(
		'wo.', 'wa.', 'y.', 'o.', 'e.', 'wu.', 'we.', 'u.', 'a.', 'i.', 'j.', 'g.', 's.', 'b.', 't.', 'h.', 'c.', 'k.', 'm.', 'n.', 'p.', 'x.', 'd.', 'f.', 'l.'
	);
	public static $VOWELS_EXPAND = array(
		'wo' => 'U:U:', 'wa' => 'U:A:', 'y' => 'U:S:', 'o' => 'U:B:', 'e' => 'U:T:',
		'wu' => 'A:U:', 'we' => 'A:A:', 'u' => 'A:S:', 'a' => 'A:B:', 'i' => 'A:T:',
		'j' => 'S:U:', 'g' => 'S:A:', 's' => 'S:S:', 'b' => 'S:B:', 't' => 'S:T:',
		'h' => 'B:U:', 'c' => 'B:A:', 'k' => 'B:S:', 'm' => 'B:B:', 'n' => 'B:T:',
		'p' => 'T:U:', 'x' => 'T:A:', 'd' => 'T:S:', 'f' => 'T:B:', 'l' => 'T:T:'
	);

	public static function bareLexicoCompare($a, $b)
	{
		static $REVERSE_ATOMS = array(
			'E' => 0, 'U' => 1, 'A' => 2, 'O'=> 3, 'S' => 4, 'B' => 5, 'T' => 6, 'M' => 7, 'F' => 8, 'I' => 9
		);

		$len = min(strlen($a), strlen($b));

		for ($i = 0; $i < $len; $i++)
		{
			$a_reverse = $REVERSE_ATOMS[$a[$i]];
			$b_reverse = $REVERSE_ATOMS[$b[$i]];

			if ($a_reverse !== $b_reverse)
			{
				return $a_reverse - $b_reverse;
			}
		}

		return strlen($a) - strlen($b);
	}

	public static function fullLexicoCompare($a, $b)
	{
		$a_AST = IEMLParser::AST_or_FAIL($a);
		$b_AST = IEMLParser::AST_or_FAIL($b);
		$code_map = array(0 => 1, 1 => 0, 2 => 0);

		if ($a_AST['resultCode'] == $b_AST['resultCode'])
		{
			$working_a = $a_AST['AST']->fullExpand()->bareStr();
			$working_b = $b_AST['AST']->fullExpand()->bareStr();

			return IEMLParser::bareLexicoCompare($working_a, $working_b);
		}
		else
		{
			return $code_map[$a_AST['resultCode']] - $code_map[$b_AST['resultCode']];
		}
	}

	public static function getClass($exp)
	{
		static $CLASS_VERBS = array('O:', 'U:', 'A:', 'y.', 'o.', 'e.', 'u.', 'a.', 'i.', 'wo.', 'wa.', 'we.', 'wu.');
		static $CLASS_NOUNS = array('M:', 'S:', 'B:', 'T:', 's.', 'b.', 't.', 'k.', 'm.', 'n.', 'd.', 'f.', 'l.', 'j.', 'h.', 'p.', 'g.', 'c.', 'x.');
		static $CLASS_HYBRID = array('I:', 'F:');

		$exp = trim($exp);
		if (in_array(substr($exp, 0, 2), $CLASS_VERBS) || in_array(substr($exp, 0, 3), $CLASS_VERBS))
		{
			return 'verb';
		}
		else if (in_array(substr($exp, 0, 2), $CLASS_NOUNS))
		{
			return 'noun';
		}
		else if (substr($exp,0,2) == 'E:')
		{
			return 'auxiliary';
		}
		else if (in_array(substr($exp, 0, 2), $CLASS_HYBRID) || strrpos($exp, '+')>=0)
		{
			return 'hybrid';
		}
		else
		{
			return NULL;
		}
	}

	public static function AST_or_FAIL($string)
	{
		$parser = new IEMLParser();
		$parserResult = $parser->parseAllStrings($string);
		$ret = array();
		
		if ($parserResult->hasException())
		{
			$ret['result'] = 'error';
			$ret['resultCode'] = 1;
			$ret['error'] = ParseException::formatError($string, $parserResult->except(), FALSE);
		}
		else if ($parserResult->hasError())
		{
			$ret['result'] = 'error';
			$ret['resultCode'] = 2;
			$ret['error'] = $parserResult->error()->getMessage();
		}
		else
		{
			$ret['result'] = 'success';
			$ret['resultCode'] = 0;
			$ret['AST'] = $parserResult->AST();
		}
		
		return $ret;
	}
	
	public function parseAllStrings($string)
	{
		set_error_handler('error_to_exception');
		
		$ret = NULL;
		
		try
		{
			$ret = $this->parseString($string);
		}
		catch (ParseException $e)
		{
			$ret = ParserResult::fromException($e);
		}
		
		restore_error_handler();
		
		return $ret;
	}
	
	public function parseString($string)
	{
		try
		{
			$AST = $this->iniParse($string);
			// $order = $AST->checkOrder();

			// if (!$order[0]) {
			// 	throw new ParseException('Script is not properly ordered', 8, $order[1]);
			// }

			return ParserResult::fromAST($AST);
		}
		catch (ParseException $e)
		{
			return ParserResult::fromException($e);
		}
	}

	private function iniParse($string)
	{
		if (is_string($string))
		{
			$parse_string = trim($string);

			if ($parse_string)
			{
				$in_stars = NULL;
				if (preg_match('/^\*\*([^\*]+)\*$/', $parse_string, $in_stars))
				{
					$parse_string = trim($in_stars[1]);
				}
				
				$AST = new IEMLASTNode($string, IEMLNodeType::$ROOT, array());
				
				$AST->push($this->subParse($parse_string, NULL, 0));
				
				return $AST;
			}
			else
			{
				throw new ParseException('Empty string provided.', 1);
			}
		}
		else
		{
			throw new ParseException('"IEMLParser->parseString" can only parse strings, '.gettype($string).' passed', 1);
		}
	}
	
	private function subParse($string, $highest_layer, $source_character)
	{
		Devlog::i(__FUNCTION__.pre_dump(func_get_args()));
		
		$AST = NULL;
		$str_len = strlen($string);
		
		if ($this->hasMultipleCategories($string))
		{
			//deal with multiple categories
			$AST = new IEMLASTNode($string, IEMLNodeType::$CATEGORY, array(), $source_character);

			$categories = $this->splitCategories($string, $source_character);

			Devlog::i('$categories'.pre_dump($categories));

			for ($i = 0; $i < count($categories); $i++) {
				$trimmed = loffstrim($categories[$i][0]);
				$AST->push(
					$this->subParse($trimmed[0], $highest_layer, $categories[$i][1] + $trimmed[1])
				);
			}
		}
		else if ($this->parenEnclosed($string))
		{
			//deal with parentheses
			$AST = new IEMLASTNode($string, IEMLNodeType::$PAREN, array(), $source_character);
			
			$sub_layer = $this->matchInsideParens($string, $source_character);
			
			$AST->push(
				$this->subParse($sub_layer[0], $highest_layer, $source_character + $sub_layer[1])
			);
		}
		else if ($this->visual_expression && $this->squareBracketEnclosed($string))
		{
			//deal with parentheses
			$AST = new IEMLASTNode($string, IEMLNodeType::$SQBRACKET, array(), $source_character);
			
			$sub_layer = $this->matchInsideSquareBrackets($string, $source_character);
			
			$AST->push(
				$this->subParse($sub_layer[0], $highest_layer, $source_character + $sub_layer[1])
			);
		}
		else if ($this->isAtom($string))
		{
			$AST = new IEMLASTNode($string, IEMLNodeType::$ATOM, array(), $source_character);
		}
		else if ($this->isLayer1Abbrev($string))
		{
			$AST = new IEMLASTNode($string, IEMLNodeType::$VOWEL, array(), $source_character);
		}
		else
		{
			$tentative_highest_layer = $this->getHighestLayer($string);

			if (isset($tentative_highest_layer)
				&& $this->hasNeedlessUpcast($string, $tentative_highest_layer))
			{
				$sub_layer =
					$this->matchLayer($string, $tentative_highest_layer, $source_character);

				$AST = new IEMLASTNode(
					$string, IEMLNodeType::$LAYER, array(), $source_character + $sub_layer[1]
				);

				$AST->push(
					$this->subParse($sub_layer[0],
					$tentative_highest_layer - 1,
					$source_character + $sub_layer[1])
				);
			}
			else
			{
				if (!isset($highest_layer))
				{
					$highest_layer = $tentative_highest_layer;
				}
				
				if ($highest_layer < 0 || $highest_layer >= count(IEMLParser::$LAYER_STRINGS))
				{
					throw new ParseException(
						'Layer mismatch', 4, array($source_character, strlen($string))
					);
				}
				else if ($this->hasTopLevelAddition($string))
				{
					//determine additive relations
					$additive_relations = $this->getAdditiveRelations($string);
					
					$AST = new IEMLASTNode($string, IEMLNodeType::$ADD, array(), $source_character);
					
					for ($i=0; $i<count($additive_relations); $i++)
					{
						$AST->push(
							$this->subParse($additive_relations[$i][0],
							$highest_layer,
							$source_character + $additive_relations[$i][1])
						);
					}
				}
				else
				{
					//deal with multiplicative expressions
					$multiplicative_relations = $this->getMultiplicativeRelations(
						$string, $highest_layer, $source_character
					);
					
					if (count($multiplicative_relations) > 3)
					{
						throw new ParseException(
							'Too many multiplicative relations at Layer '.($highest_layer),
							3,
							array($source_character, strlen($string))
						);
					}
					else if (count($multiplicative_relations) > 1)
					{
						$mul_AST = new IEMLASTNode(
							$string, IEMLNodeType::$MUL, array(), $source_character
						);
						
						for ($i=0; $i<count($multiplicative_relations); $i++)
						{
							$mul_AST->push(
								$this->subParse(
									$multiplicative_relations[$i][0],
									$highest_layer,
									$source_character + $multiplicative_relations[$i][1]
								)
							);
						}
					
						$AST = new IEMLASTNode(
							$string, IEMLNodeType::$LAYER, array(), $source_character
						);

						$AST->push($mul_AST);
					}
					else if (count($multiplicative_relations) > 0)
					{
						$AST = new IEMLASTNode(
							$string, IEMLNodeType::$LAYER, array(), $source_character
						);
						$mul_AST = new IEMLASTNode(
							$string, IEMLNodeType::$MUL, array(), $source_character
						);

						$mul_AST->push(
							$this->subParse($string, $highest_layer, $source_character)
						);
						$AST->push($mul_AST);
					}
				}
			}
		}
		
		return $AST;
	}
	
	private function getHighestLayer($string)
	{
		Devlog::i(__FUNCTION__.pre_dump(func_get_args()));
		$highest_layer = NULL;
			
		for ($i = count(IEMLParser::$LAYER_STRINGS)-1; $i >= 0; $i--)
		{
			$preg_result = preg_match("/.*".preg_quote(IEMLParser::$LAYER_STRINGS[$i], '/')."$/", trim($string));
			
			if ($preg_result)
			{
				$highest_layer = $i;
				
				break;
			}
		}

		Devlog::i(__FUNCTION__.' results'.pre_dump($highest_layer));
		
		return $highest_layer;
	}
	
	private function getAdditiveRelations($string)
	{
		Devlog::i(__FUNCTION__.pre_dump(func_get_args()));

		$string = trim($string);
		$additive_results = array();
		$paren_count = 0;
		$last_addition_offset = 0;

		for ($i = 0; $i <= strlen($string); $i++)
		{
			if ($i < strlen($string))
			{
				if ($string[$i] == '(')
				{
					$paren_count++;
				}
				else if ($string[$i] == ')')
				{
					$paren_count--;
				}
			}

			if ($i == strlen($string) || $string[$i] == '+')
			{
				if ($paren_count == 0)
				{
					$additive_results[] = array(
						trim(substr($string, $last_addition_offset, $i - $last_addition_offset)),
						$last_addition_offset
					);
					$last_addition_offset = $i+1;
				}
			}
		}

		Devlog::i(__FUNCTION__.' results'.pre_dump($additive_results));

		return $additive_results;
	}

	private function hasTopLevelAddition($string)
	{
		Devlog::i(__FUNCTION__.pre_dump(func_get_args()));

		$additive_result_count = count($this->getAdditiveRelations($string));

		Devlog::i(__FUNCTION__.' results'.pre_dump($additive_result_count, $additive_result_count > 1));

		return $additive_result_count > 1;
	}
	
	private function getMultiplicativeRelations($string, $highest_layer, $source_character)
	{
		Devlog::i(__FUNCTION__.pre_dump(func_get_args()));
		
		$multiplicative_results = NULL;
		$esc_marker = preg_quote(IEMLParser::$LAYER_STRINGS[$highest_layer], '/');
		$preg_result = preg_match_all(
			'/((?:\([^)]+\)'.$esc_marker.'?)|(?:[^'.$esc_marker.']+'.$esc_marker.')) *\*?/',
			$string,
			$multiplicative_results,
			PREG_OFFSET_CAPTURE
		);

		Devlog::i(__FUNCTION__.' results'.pre_dump($multiplicative_results));
		if ($preg_result && IEMLParser::assertAllMatched($string, $multiplicative_results[0], PREG_OFFSET_CAPTURE))
		{
			return $multiplicative_results[1];
		}
		else
		{
			throw new ParseException('Could not match multiplicative relation in "'.$string.'" to highest layer: "'.$highest_layer.'"', 3, array($source_character, strlen($string)));
		}
	}
	
	private static function assertAllMatched($string, $results, $flags = 0)
	{
		Devlog::i(__FUNCTION__.pre_dump(func_get_args()));
		$len = 0;
		
		if ($flags == 0)
		{
			for ($i=0; $i<count($results); $i++)
			{
				$len += strlen($results[$i]);
			}
		}
		else if ($flags & PREG_OFFSET_CAPTURE)
		{
			for ($i=0; $i<count($results); $i++)
			{
				$len += strlen($results[$i][0]);
			}
		}
		
		return $len == strlen($string);
	}
	
	private function matchLayer($string, $layer, $source_character)
	{
		Devlog::i(__FUNCTION__.pre_dump(func_get_args()));

		$match_results = NULL;
		$trimmed = loffstrim($string);
		$esc_marker = preg_quote(IEMLParser::$LAYER_STRINGS[$layer], '/');
		$preg_result = preg_match('/^([^'.$esc_marker.']+)'.$esc_marker.'$/', $trimmed[0], $match_results, PREG_OFFSET_CAPTURE);
		
		if ($preg_result)
		{
			Devlog::i(__FUNCTION__.' results'.pre_dump($match_results[1]));

			return $match_results[1];
		}
		else
		{
			throw new ParseException('Could not match layer in string "'.$string.'" to Layer: '.$layer, 4, array($source_character + $trimmed[1], strlen($trimmed[0])));
		}
	}
	
	private static function matchNestedPair($string, $pair = array('(', ')'), $return = FALSE)
	{
		Devlog::i(__FUNCTION__.pre_dump(func_get_args()));
		$openCount = 0;
		$matched = FALSE;
		$start = NULL; $end = NULL;
		
		for ($i=0; $i<strlen($string); $i++)
		{
			if ($string[$i] == $pair[0])
			{
				if ($openCount == 0 && !isset($start))
				{
					$start = $i+1;
				}
				
				$matched = TRUE;
				
				$openCount++;
			}
			else if ($string[$i] == $pair[1])
			{
				$openCount--;
				
				if ($openCount == 0 && !isset($end))
				{
					$end = $i - $start;
				}
			}
		}
		
		if ($return == TRUE)
		{
			if ($matched && $openCount == 0)
			{
				return array(substr($string, $start, $end), $start);
			}
			else
			{
				return FALSE;
			}
		}
		else
		{
			return $matched && $openCount == 0;
		}
	}
	
	private function matchInsideParens($string, $source_character)
	{
		Devlog::i(__FUNCTION__.pre_dump(func_get_args()));
		$match_results = IEMLParser::matchNestedPair(trim($string), array('(', ')'), TRUE);
		
		if ($match_results === FALSE)
		{
			throw new ParseException('Could not match parentheses in string "'.$string.'"', 5, array($source_character, strlen($string)));
		}
		else
		{
			return $match_results;
		}
	}
	
	private function matchInsideSquareBrackets($string, $source_character)
	{
		Devlog::i(__FUNCTION__.pre_dump(func_get_args()));
		$match_results = IEMLParser::matchNestedPair(trim($string), array('[', ']'), TRUE);
		
		if ($match_results === FALSE)
		{
			throw new ParseException('Could not match parentheses in string "'.$string.'"', 5, array($source_character, strlen($string)));
		}
		else
		{
			return $match_results;
		}
	}
	
	private function parenEnclosed($string)
	{
		Devlog::i(__FUNCTION__.pre_dump(func_get_args()));

		$trimmed = trim($string);
		$res = IEMLParser::matchNestedPair($trimmed, array('(', ')'), TRUE);
		$res = $res && strlen($res[0]) + 2 == strlen($trimmed);

		Devlog::i(__FUNCTION__.' results'.pre_dump($res));

		return $res;
	}

	private function squareBracketEnclosed($string)
	{
		Devlog::i(__FUNCTION__.pre_dump(func_get_args()));

		$trimmed = trim($string);
		$res = IEMLParser::matchNestedPair($trimmed, array('[', ']'), TRUE);
		$res = $res && strlen($res[0]) + 2 == strlen($trimmed);

		Devlog::i(__FUNCTION__.' results'.pre_dump($res));

		return $res;
	}

	private function splitCategories($string, $source_character)
	{
		Devlog::i(__FUNCTION__.pre_dump(func_get_args()));
		$trimmed = loffstrim($string);
		$match_results = NULL;
		$preg_result = preg_match('/^([^\/]+)\/(.*)$/s', $trimmed[0], $match_results, PREG_OFFSET_CAPTURE);

		if (1 === $preg_result)
		{
			$sub_result = array();

			try
			{
				$sub_result = $this->splitCategories($match_results[2][0], strlen($match_results[1][0]) + $trimmed[1]);
			}
			catch (ParseException $e)
			{
				$sub_result = array($match_results[2]);
			}

			return array_merge(array(array($match_results[1][0], $match_results[1][1] + $source_character)), $sub_result);
		} else {
			throw new ParseException('Could not match category: "'.$string.'"', 9, array($source_character + $trimmed[1], strlen($string)));
		}
	}

	private function hasMultipleCategories($string)
	{
		Devlog::i(__FUNCTION__.pre_dump(func_get_args()));

		$multi_cats = strpos($string, '/') !== FALSE;

		Devlog::i(__FUNCTION__.' results'.pre_dump($multi_cats));
		return $multi_cats;
	}

	private function hasNeedlessUpcast($string, $highest_layer)
	{
		Devlog::i(__FUNCTION__.pre_dump(func_get_args()));

		$esc_marker = preg_quote(IEMLParser::$LAYER_STRINGS[$highest_layer], '/');
		$layer_result = !!preg_match('/^([^'.$esc_marker.']+)'.$esc_marker.'$/', trim($string));
		//$layer_result = $layer_result && $highest_layer > 0;

		Devlog::i(__FUNCTION__.' results'.pre_dump($layer_result));

		return $layer_result;
	}

	private function isAtom($string)
	{
		Devlog::i(__FUNCTION__.pre_dump(func_get_args()));

		static $ATOMS = array(
			'E', 'U', 'A', 'O', 'S', 'B', 'T', 'M', 'F', 'I'
		);

		return in_array($string, $ATOMS);
	}

	private function isLayer1Abbrev($string)
	{
		Devlog::i(__FUNCTION__.pre_dump(func_get_args()));

		return isset(IEMLParser::$VOWELS_EXPAND[$string]);
	}
}
