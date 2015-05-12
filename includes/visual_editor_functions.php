<?php

require_once(APPROOT.'/includes/ieml_parser/IEMLParser.class.php');

function insert_primary($parent, $id, $order)
{
	Conn::query('
		INSERT INTO expression_primary_array
			(
				fkExpressionPrimaryParent,
				intIndex,
				fkExpressionPrimaryChild,
				enumOperator
			)
		VALUES
			(
				' . $parent . ',
				' . $order . ',
				' . $id . ',
				NULL
			)
	');
}

function insert_operator($parent, $op, $order)
{
	Conn::query('
		INSERT INTO expression_primary_array
			(
				fkExpressionPrimaryParent,
				intIndex,
				fkExpressionPrimaryChild,
				enumOperator
			)
		VALUES
			(
				' . $parent . ',
				' . $order . ',
				NULL,
				\'' . $op . '\'
			)
	');
}

function promo_str_to_layer($expr, $from_layer, $to_layer)
{
	$LAYER_MARKERS = array(':', '.', '-', "'", ',', '_', ';');

	for ($i = $from_layer + 1; $i <= $to_layer && $i < count($LAYER_MARKERS); $i++)
	{
		$expr .= $LAYER_MARKERS[$i];
	}

	return $expr;
}

function index_of_matching_bracket($array, $offset, $bracket_type = '(')
{
	static $MATCHING_PAREN = array(
		'(' => ')',
		'[' => ']'
	);
	$nest_level = 0;

	for ($i = $offset; $i < count($array); $i++)
	{
		$expression = $array[$i]['expression'];
		if ($expression == $bracket_type)
		{
			$nest_level++;
		}
		else if ($expression == $MATCHING_PAREN[$bracket_type])
		{
			$nest_level--;

			if ($nest_level == 0)
			{
				return $i;
			}
		}
	}

	return NULL;
}

function order_subexpression($editor_array)
{
	$sortables = array();
	$subexpression_size = 0;
	$is_mul = FALSE;

	for ($i = 0; $i < count($editor_array); $i++)
	{
		$editor_expression = $editor_array[$i];

		if ($editor_expression['expression'] == '(' || $editor_expression['expression'] == '[')
		{
			$index_of_matching_paren =
				index_of_matching_bracket($editor_array, $i, $editor_expression['expression']);

			list($ordered_subexpression, $size) = order_subexpression(array_slice(
				$editor_array, $i + 1, $index_of_matching_paren - $i - 1
			));

			array_splice(
				$editor_array,
				$i + 1,
				$index_of_matching_paren - $i - 1,
				$ordered_subexpression
			);

			$sortables[] = array(
				'size' => $size,
				'item' => array_slice(
					$editor_array, $i, $index_of_matching_paren - $i + 1
				),
			);

			$i += count($ordered_subexpression);
		}
		else if (isset($editor_expression['id']))
		{
			$expression_ast = (new IEMLParser())
				->parseAllStrings($editor_expression['expression'])
				->AST();

			$sortables[] = array(
				'size' => $expression_ast->getSize(),
				'item' => array($editor_expression)
			);
		}
		else if ($editor_expression['expression'] == '*')
		{
			$is_mul = TRUE;
		}
	}

	foreach ($sortables as $s) { $subexpression_size += $s['size']; }

	if ($is_mul)
	{
		return array($editor_array, $subexpression_size);
	}

	usort($sortables, function($a, $b) {
		return $a['size'] - $b['size'];
	});

	$j = 0;
	for ($i = 0; $i < count($editor_array); $i++)
	{
		$editor_expression = $editor_array[$i];

		if ($editor_expression['expression'] == '(' || $editor_expression['expression'] == '[')
		{	
			$index_of_matching_paren =
				index_of_matching_bracket($editor_array, $i, $editor_expression['expression']);

			array_splice($editor_array, $i, $index_of_matching_paren - $i + 1, $sortables[$j++]['item']);

			$i += count($sortables[$j-1]['item']);
		}
		else if (isset($editor_expression['id']))
		{
			array_splice($editor_array, $i, 1, $sortables[$j++]['item']);
		}
	}

	return array($editor_array, $subexpression_size);
}

function promote_subexpression($editor_array)
{
	$highest_layer = NULL;
	$layers = array();

	for ($i = 0; $i < count($editor_array); $i++)
	{
		$editor_expression = $editor_array[$i];
		$expression = $editor_expression['expression'];
		$layer = NULL;

		if ($expression == '(' || $expression == '[')
		{
			$index_of_matching_paren =
				index_of_matching_bracket($editor_array, $i, $expression);

			list($promoted_subexpression, $layer) = promote_subexpression(array_slice(
				$editor_array, $i + 1, $index_of_matching_paren - $i - 1
			));

			array_splice($editor_array, $i + 1, $index_of_matching_paren - $i - 1, $promoted_subexpression);

			$i += count($promoted_subexpression);
		}
		else if (isset($editor_expression['id']))
		{
			$layer = (new IEMLParser())
				->parseAllStrings($expression)
				->AST()
				->getLayer();
		}

		if (isset($layer))
		{
			$layers[] = $layer;

			if ($expression == '[')
			{
				$layer += 1;
			}

			if (!$highest_layer || $layer > $highest_layer)
			{
				$highest_layer = $layer;
			}
		}
	}

	$j = 0;
	for ($i = 0; $i < count($editor_array); $i++)
	{
		$editor_expression = $editor_array[$i];
		$expression = $editor_expression['expression'];

		if ($expression == '(' || $expression == '[')
		{
			$index_of_matching_paren = 
				index_of_matching_bracket($editor_array, $i, $expression);
			$layer = $layers[$j++];

			if ($layer < $highest_layer)
			{
				array_splice(
					$editor_array,
					$index_of_matching_paren + 1,
					0,
					array(array('expression' => promo_str_to_layer('', $layer, $highest_layer)))
				);
			}

			$i += $index_of_matching_paren - $i;
		}
		else if (isset($editor_expression['id']))
		{
			$layer = $layers[$j++];

			if ($layer < $highest_layer)
			{
				array_splice(
					$editor_array,
					$i + 1,
					0,
					array(array('expression' => promo_str_to_layer('', $layer, $highest_layer)))
				);

				$i++;
			}
		}
	}

	return array($editor_array, $highest_layer);
}

function process_editor_array($editor_array)
{
	if (count($editor_array) == 0) {
		return array(
			'result' => 'error',
			'error' => 'No array to process, nothing to do.'
		);
	}

	$str_expression = '';
	list($ordered_editor_array, ) = order_subexpression($editor_array);
	list($promoted_editor_array, ) = promote_subexpression($ordered_editor_array);

	foreach ($promoted_editor_array as $editor_expression)
	{
		$expression = $editor_expression['expression'];

		if ($expression === '+')
		{
			$str_expression .= ' ' . $expression . ' ';
		}
		else if ($expression != '*')
		{
			$str_expression .= $expression;
		}
	}

	return array(
		'str_expression' => $str_expression,
		'processed_array' => $ordered_editor_array
	);
}
