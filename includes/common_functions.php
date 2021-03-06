<?php

function get_table_for_element($ret, $goodID, $options)
{
	$table_head_query = NULL;
	$top = NULL;
	$lang = strtolower($options['lang']);
	$goodExpression = goodString($ret['expression']);

	if ($ret['enumCategory'] == 'Y')
	{
		$top = $ret;

		// if we're dealing with a key, retrieve it accordingly
		$table_head_query = Conn::queryArrays("
			SELECT
				pkTable2D, intWidth, intHeight, intHorHeaderDepth, intVerHeaderDepth,
				prim.strExpression as expression, sublang.strExample as example,
				t2d.fkExpression as id, intLeftoverIndex, intConcatIndex
			FROM table_2d_id t2d
			JOIN expression_primary prim ON t2d.fkExpression = prim.pkExpressionPrimary
			LEFT JOIN
				(
					SELECT fkExpressionPrimary, strExample
					FROM expression_data
					WHERE strLanguageISO6391 = '" . goodString($lang) . "'
				) sublang
				ON sublang.fkExpressionPrimary = t2d.fkExpression
			WHERE t2d.enumDeleted = 'N' AND fkExpression = " . $goodID
		);

		// also check if the key is an element of a table somewhere
		array_append($table_head_query, Conn::queryArrays("
			SELECT
				pkTable2D, intWidth, intHeight, intHorHeaderDepth, intVerHeaderDepth,
				prim.strExpression as expression, sublang.strExample as example,
				t2d.fkExpression as id, intLeftoverIndex, intConcatIndex
			FROM table_2d_id t2d
			JOIN expression_primary prim ON t2d.fkExpression = prim.pkExpressionPrimary
			JOIN table_2d_ref t2dref ON pkTable2D = fkTable2D
			LEFT JOIN
				(
					SELECT fkExpressionPrimary, strExample
					FROM expression_data
					WHERE strLanguageISO6391 = '" . goodString($lang) . "'
				) sublang
				ON sublang.fkExpressionPrimary = t2d.fkExpression
			WHERE t2d.enumDeleted = 'N' AND prim.enumDeleted = 'N'
			AND t2dref.strCellExpression = '" . $goodExpression . "'"
		));
	}
	else
	{
		$table_head_query = Conn::queryArrays("
			SELECT
				t2d.pkTable2D, t2d.intWidth, t2d.intHeight, t2d.intHorHeaderDepth, t2d.intVerHeaderDepth,
				prim.strExpression AS expression, sublang.strExample AS example,
				prim.pkExpressionPrimary as id, t2d.intLeftoverIndex, t2d.intConcatIndex
			FROM table_2d_id t2d
			JOIN expression_primary prim ON prim.pkExpressionPrimary = t2d.fkExpression
			JOIN table_2d_ref t2dref ON fkTable2D = pkTable2D
			LEFT JOIN
				(
					SELECT fkExpressionPrimary, strExample
					FROM expression_data
					WHERE strLanguageISO6391 = '" . goodString($lang) . "'
				) sublang
				ON sublang.fkExpressionPrimary = prim.pkExpressionPrimary
			WHERE t2d.enumDeleted = 'N' AND prim.enumDeleted = 'N'
			AND t2dref.strCellExpression = '" . $goodExpression . "'
		");
		
		// get all related tables (leftover tables/concatenations)
		$related_tables = array();
		for ($i = 0; $i < count($table_head_query); $i++)
		{
			array_append($related_tables, Conn::queryArrays("
				SELECT
					t2d.pkTable2D, t2d.intWidth, t2d.intHeight, t2d.intHorHeaderDepth, t2d.intVerHeaderDepth,
					prim.strExpression AS expression, sublang.strExample AS example,
					prim.pkExpressionPrimary as id, t2d.intLeftoverIndex, t2d.intConcatIndex
				FROM table_2d_id t2d
				JOIN expression_primary prim ON prim.pkExpressionPrimary = t2d.fkExpression
				LEFT JOIN
					(
						SELECT fkExpressionPrimary, strExample
						FROM expression_data
						WHERE strLanguageISO6391 = '" . goodString($lang) . "'
					) sublang
					ON sublang.fkExpressionPrimary = prim.pkExpressionPrimary
				WHERE t2d.enumDeleted = 'N' AND prim.enumDeleted = 'N'
				AND t2d.fkExpression = " . $table_head_query[$i]['id'] . "
				AND t2d.intLeftoverIndex = " . $table_head_query[$i]['intLeftoverIndex'] . "
				AND t2d.intConcatIndex != " . $table_head_query[$i]['intConcatIndex'] . "
			"));
		}
		
		array_append($table_head_query, $related_tables);
		
		if (count($table_head_query) > 0)
		{
			$top = array(
				'expression' => $table_head_query[0]['expression'],
				'example' => $table_head_query[0]['example'],
				'id' => $table_head_query[0]['id']
			);
		}
	}

	$grouped_tables = array();
	for ($i = 0; $i < count($table_head_query); $i++)
	{
		$grouped_tables[$table_head_query[$i]['id']][] = $table_head_query[$i];
	}
	$grouped_tables = array_values($grouped_tables);
	
	$related_tables = array();
	for ($i = 0; $i < count($grouped_tables); $i++)
	{
		$tables_for_expression = $grouped_tables[$i];
		$leftovers_for_expression = array();

		for ($j = 0; $j < count($tables_for_expression); $j++)
		{
			$leftover_index = $tables_for_expression[$j]['intLeftoverIndex'];
			$concat_index = $tables_for_expression[$j]['intConcatIndex'];

			$formatted_sub = format_table_for($tables_for_expression[$j], $ret, $tables_for_expression[$j], $options);
		
			$formatted_sub['height'] = $tables_for_expression[$j]['intHeight'];
			$formatted_sub['length'] = $tables_for_expression[$j]['intWidth'];
			
			$leftovers_for_expression[$leftover_index][$concat_index] = $formatted_sub;
		}

		$related_tables[] = $leftovers_for_expression;
	}
	
	$related_tables = array_values($related_tables);
	for ($i = 0; $i < count($related_tables); $i++)
	{
		$related_tables[$i] = array_values($related_tables[$i]);
	}

	$ret['tables'] = $related_tables;
	$ret['etymology'] = get_etymology($ret, $lang);
	
	return $ret;
}

function fetch_example_for_expression_id($id, $lang)
{
	$example_query = Conn::queryArrays('
		SELECT strExample as example, strLanguageISO6391
		FROM expression_data
		WHERE fkExpressionPrimary = ' . $id . '
	');

	foreach ($example_query as $example)
	{
		if (strcasecmp($example['strLanguageISO6391'], $lang) == 0)
		{
			return $example['example'];
		}
	}

	return '';
}

function fetch_info_for_expression($expression, $lang)
{
	$example_query = Conn::queryArrays('
		SELECT
			strExample as example,
			strLanguageISO6391,
			fkExpressionPrimary
		FROM expression_data
		JOIN expression_primary ON fkExpressionPrimary = pkExpressionPrimary
		WHERE strExpression = \'' . goodString($expression) . '\'
	');

	$ret = NULL;

	foreach ($example_query as $example)
	{
		if (strcasecmp($example['strLanguageISO6391'], $lang) == 0)
		{
			$ret = array(
				'exp' => $expression,
				'example' => $example['example'],
				'id' => $example['fkExpressionPrimary']
			);
		}
	}

	return $ret;
}

function fetch_descriptor_for_expression_id($id, $lang)
{
	$example_query = Conn::queryArrays('
		SELECT strDescriptor as descriptor, strLanguageISO6391
		FROM expression_data
		WHERE fkExpressionPrimary = ' . $id . '
	');

	foreach ($example_query as $example)
	{
		if (strcasecmp($example['strLanguageISO6391'], $lang) == 0)
		{
			return $example['descriptor'];
		}
	}

	return '';
}

function format_table_for($table_head_query, $query_exp, $top, $options)
{
	$ret = array();
	$lang = strtolower($options['lang']);
	
	//fetch metadata about table elements
	$table_body_query = Conn::queryArrays("
		SELECT
			ref.intPosInTable, ref.enumElementType, ref.enumHeaderType, ref.intSpan,
			ref.intHeaderLevel, ref.pkTable2DRef AS refID, ref.enumEnabled,
			ref.strCellExpression as expression
		FROM table_2d_ref ref
		WHERE fkTable2D = " . $table_head_query['pkTable2D'] . "
	");

	//fetch expression data about elements in the table
	$exp_query = Conn::queryArrays("
		SELECT
			pkExpressionPrimary as id, prim.strExpression as expression
		FROM expression_primary prim
		WHERE prim.enumDeleted = 'N'
		AND   prim.strExpression IN (" . implode(',', array_map(function($a) {
			return "'" . goodString($a['expression']) . "'";
		}, $table_body_query)) . ")
	");

	//fetch examples for expressions
	foreach ($exp_query as &$expression)
	{
		$expression['example'] = fetch_example_for_expression_id($expression['id'], $lang);
	}
	
	$flat_assoc = array();
	for ($i = 0; $i < count($exp_query); $i++)
	{
		$flat_assoc[$exp_query[$i]['expression']] = $exp_query[$i];
	}
	$flat_assoc[$top['expression']] = $top;
	
	$table_info = reconstruct_table_info($top, $table_head_query, $table_body_query);
	
	$empty_head_count = IEML_count_empty_col($table_info, function($a) use ($flat_assoc)
	{
		return !isset($flat_assoc[$a[0]['expression']]);
	});
	
	$table_info['empty_head_count'] = $empty_head_count;
	
	$ret['edit_vertical_head_length'] = $table_info['ver_header_depth'] + 1;
	$ret['render_vertical_head_length'] = $table_info['ver_header_depth'] - $empty_head_count[1][1] + 1;
	$ret['edit_horizontal_head_length'] = $table_info['hor_header_depth'];
	$ret['render_horizontal_head_length'] = $table_info['hor_header_depth'] - $empty_head_count[1][0];
	
	$ret['iemlEnumComplConcOff'] = invert_bool($query_exp['enumCompConc'], 'Y', 'N');
	$ret['iemlEnumSubstanceOff'] = invert_bool($query_exp['strEtymSwitch'][0], 'Y', 'N');
	$ret['iemlEnumAttributeOff'] = invert_bool($query_exp['strEtymSwitch'][1], 'Y', 'N');
	$ret['iemlEnumModeOff'] = invert_bool($query_exp['strEtymSwitch'][2], 'Y', 'N');
	
	$ret['pkTable2D'] = $table_head_query['pkTable2D'];
	$ret['enumShowEmpties'] = $query_exp['enumShowEmpties'];
	$ret['top'] = $top;
	
	//add top as a vertical header
	$table_info['headers'][1][] = array(array(array(
		'example' => $top['example'],
		'enumElementType' => "header",
		'enumEnabled' => "Y",
		'enumHeaderType' => "ver",
		'expression' => $top['expression'],
		'id' => $top['id'],
		'intSpan' => (int)$table_info['length']
	), (int)$table_info['length']));
	
	$ret['relations'] = get_processed_relations($query_exp, $top, $table_info, $flat_assoc, $lang);
	
	$ret['table'] = IEML_postprocess_table(array(
		'headers' => $table_info['headers'],
		'body' => $table_info['body']
	), function($el) use ($flat_assoc)
	{
		if (isset($flat_assoc[$el['expression']]))
		{
			$flat_element = $flat_assoc[$el['expression']];

			$el['id'] = $flat_element['id'];
			$el['example'] = $flat_element['example'];
		}
		else
		{
			$el['id'] = NULL;
			$el['example'] = NULL;
		}
		
		return $el;
	});
	
	return $ret;
}

function get_processed_relations($query_exp, $top, $table_info, $flat_assoc, $lang)
{
	$relations = gen_exp_relations($query_exp, $top['expression'], $table_info);
	
	$relations = postproc_exp_relations($relations, function($el) use ($flat_assoc, $lang)
	{
		if (is_array($el))
		{
			if (isset($el['expression']))
			{
				$assoc_expression = $flat_assoc[$el['expression']];

				return array(
					'exp' => array($el['expression'], $el['intSpan']),
					'desc' => $assoc_expression['example'],
					'id' => $assoc_expression['id']
				);
			}
			else
			{
				$ret = array();

				if (isset($el['expression']))
				{
					$ret['exp'] = array($el['expression'], 1);
				}
				else
				{
					$ret['exp'] = NULL;
				}
				$ret['id'] = NULL;
				$ret['desc'] = NULL;

				return $ret;
			}
		}
		else if (is_string($el))
		{
			if (isset($flat_assoc[$el]))
			{
				$assoc_expression = $flat_assoc[$el];

				return array(
					'exp' => array($el, 1),
					'desc' => $assoc_expression['example'],
					'id' => $assoc_expression['id']
				);
			}
			else
			{
				$db_info = fetch_info_for_expression($el, $lang);

				if ($db_info)
				{
					return array(
						'exp' => array($el, 1),
						'desc' => $db_info['example'],
						'id' => $db_info['id']
					);
				}
			}
		}

		return array('exp' => array($el, 1), 'desc' => NULL, 'id' => NULL);
	});

	return $relations;
}

function get_etymology($query_exp, $lang)
{
	//get expression etymology
	$temp_etym = gen_etymology($query_exp['expression']);
	
	//fetch some examples from the DB so that the eymological parts display nicely
	$etym_query = array();
	if (count($temp_etym) > 0) {
		$etym_query = Conn::queryArrays("
			SELECT
				pkExpressionPrimary as id, prim.strExpression as expression,
				sublang.strExample AS example
			FROM expression_primary prim
			LEFT JOIN
				(
					SELECT fkExpressionPrimary, strExample
					FROM expression_data
					WHERE strLanguageISO6391 = '".goodString($lang)."'
				) sublang
				ON sublang.fkExpressionPrimary = prim.pkExpressionPrimary
			WHERE enumDeleted = 'N'
			AND   prim.strExpression IN (".implode(',', array_map(function($a) { return "'".goodString($a)."'"; }, $temp_etym)).")");
	}
	else
	{
		throw new Exception('Unable to retrieve etymology for expression: "' . $query_exp['expression'] . '"');
	}
	
	$flat_assoc = array();
	for ($i=0; $i<count($etym_query); $i++)
	{
		$flat_assoc[$etym_query[$i]['expression']] = $etym_query[$i];
	}
	
	//do some post-processing on the etymological array, to get some use preferences and API info out
	$temp_etym = array_map(function($el) use ($flat_assoc)
	{
		if (array_key_exists($el, $flat_assoc))
		{
			return array('exp' => $el, 'desc' => $flat_assoc[$el]['example'], 'id' => $flat_assoc[$el]['id']);
		}
		else
		{
			return array('exp' => $el, 'desc' => NULL, 'id' => NULL);
		}
	}, $temp_etym);
	
	$etymology = array();
	for ($i = 0; $i < count($temp_etym); $i++)
	{
		if ($query_exp['strEtymSwitch'][$i] == 'Y')
		{
				$etymology[] = $temp_etym[$i];
		}
	}

	return $etymology;
}
