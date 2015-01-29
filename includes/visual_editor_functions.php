<?php

function insert_relation($parent, $rel_id, $order) {
	Conn::queryArrays('
		INSERT INTO relational_expression_tree
			(fkParentRelation, fkRelationalExpression, intOrder)
		VALUES
			(' . $parent . ', ' . $rel_id . ', ' . $order . ')
	');
}

function insert_primary($parent, $id, $order) {
	Conn::queryArrays('
		INSERT INTO relational_expression_tree
			(fkParentRelation, fkExpressionPrimary, intOrder)
		VALUES
			(' . $parent . ', ' . $id . ', ' . $order . ')
	');
}

function process_editor_array($editor_array) {
	if (count($editor_array) == 0) {
		return array(
			'result' => 'error',
			'error' => 'No array to process, nothing to do.'
		);
	}

	//preprocess to gather some basic information about what's sent over
	$composition_type = NULL;
	$int_highest_layer = NULL;
	$int_layer = NULL;
	$operator_count = 0;
	$insertables = array();
	foreach ($editor_array as $el) {
		if ($el == '+' || $el == '*' || $el == '/') {
			$composition_type = $el;
			$operator_count++;
		} else if ($el != 'E' && $composition_type != '/') {
			$script_lookup = Conn::queryArray('SELECT * FROM expression_primary WHERE strExpression = \'' . goodString($el) . '\'');

			if ($script_lookup) {
				$int_highest_layer = $script_lookup['intLayer'];
			} else {
				$relational_lookup = Conn::queryArray('SELECT * FROM relational_expression WHERE vchExpression = \'' . goodString($el) . '\'');

				if ($relational_lookup) {
					$int_highest_layer = $relational_lookup['intLayer'];
				} else {
					return array(
						'result' => 'error',
						'error' => '"' . $el . '" does not exist in the database.'
					);
				}
			}
		}
	}

	if ($composition_type == '+') {
		$int_layer = $int_highest_layer;
	} else if ($composition_type != '/') {
		$int_layer = $int_highest_layer + 1;
	}

	if (is_null($composition_type)) {
		return array(
			'result' => 'error',
			'error' => 'Unable to compose an expression with no composition operator.'
		);
	}

	//run through the array again to check if everything's good
	foreach ($editor_array as $el) {
		if ($el == 'E' && is_null($int_highest_layer)) {
			return array(
				'result' => 'error',
				'error' => 'Unable to infer the layer number of an expression containing E.'
			);
		} else if (($el == '+' || $el == '*' || $el == '/') && $composition_type != $el) {
			return array(
				'result' => 'error',
				'error' => 'Unable to compose expression using multiple types of operators.'
			);
		}
	}

	foreach ($editor_array as $el) {
		if ($el == 'E') {
			if ($int_highest_layer == 0) {
				$insertables[] = Conn::queryArray('SELECT * FROM expression_primary WHERE pkExpressionPrimary = 10');
			} else {
				$insertables[] = Conn::queryArray('SELECT *, vchExpression as strExpression FROM relational_expression WHERE pkRelationalExpression = ' . $int_highest_layer);
			}
		} else if (!($el == '+' || $el == '*' || $el == '/')) {
			$script_lookup = Conn::queryArray('SELECT * FROM expression_primary WHERE strExpression = \'' . goodString($el) . '\'');

			if ($script_lookup) {
				$insertables[] = $script_lookup;
				continue;
			}

			$relational_lookup = Conn::queryArray('SELECT *, vchExpression as strExpression FROM relational_expression WHERE vchExpression = \'' . goodString($el) . '\'');

			if ($relational_lookup) {
				$insertables[] = $relational_lookup;
			}
		}
	}

	if ($composition_type == '*') {
		if (count($insertables) > 3) {
			//check for max of 3 parts of a multiplication expression
			return array(
				'result' => 'error',
				'error' => 'Unable to save multiplication expression with more than 3 parts.'
			);
		} else if (count($insertables) > 1) {
			//check for dangling empty at the end of the expression
			$last_item = $insertables[count($insertables) - 1];

			if (in_array($last_item['strExpression'], array( "E:", "E:.", "E:.-", "E:.-'", "E:.-',", "E:.-',_", "E:.-',_;" ))) {
				return array(
					'result' => 'error',
					'error' => 'Illegal to have dangling "Empty" at the end of the expression.'
				);
			}
		}
	}

	if (count($insertables) == 0) {
		//check for pointless expression
		return array(
			'result' => 'error',
			'error' => 'Unable to save empty expression.'
		);
	} else if (count($insertables) != $operator_count + 1) {
		//check for an expression with an invalid number of expressions
		return array(
			'result' => 'error',
			'error' => 'Unable to save expression with an invalid number of operators.'
		);
	}

	//run through insertables, to compose expression as string and check for errors
	$str_expression = '';
	foreach ($insertables as $i => $to_insert) {
		if ($composition_type != '/' && $to_insert['intLayer'] != $int_highest_layer) {
			return array(
				'result' => 'error',
				'error' => 'Unable to compose expressions with subexpressions of different layers.'
			);
		}

		if ($i > 0 && ($composition_type == '+' || $composition_type == '/')) {
			$str_expression .= ' ' . $composition_type . ' ';
		}

		$str_expression .= $to_insert['strExpression'];
	}
	//cover with brackets if we're adding expressions greater than layer 0
	if ($int_layer > 0 && $composition_type == '+') {
		$str_expression = '(' . $str_expression . ')';
	}
	//append layer marker if we're adding/multiplying
	if ($composition_type == '*') {
		$str_expression .= IEMLParser::$LAYER_STRINGS[$int_layer];
	}

	return array(
		'insertables' => $insertables,
		'str_expression' => $str_expression,
		'composition_type' => $composition_type,
		'int_layer' => $int_layer
	);
}
