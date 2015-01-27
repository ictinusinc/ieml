<?php

$processed_result = process_editor_array($req['editor_array']);
$expression_id = goodInt($req['rel_id']);

if ($processed_result['result'] == 'error') {
	return $processed_result;
}

//insert into relational_expression table once we know it's cool
Conn::query('
	UPDATE relational_expression
	SET
		vchExpression = \'' . goodString($processed_result['str_expression']) . '\' ,
		vchExample = \'' . goodString($req['example']) . '\' ,
		enumCompositionType = \'' . $processed_result['composition_type'] . '\' ,
		intLayer = ' . (is_null($int_layer) ? 'NULL' : '\'' . $processed_result['int_layer'] . '\'') . '
	WHERE pkRelationalExpression = ' . $expression_id
);

Conn::query('
	DELETE FROM relational_expression_tree
	WHERE fkParentRelation = ' . $expression_id
);

//run through array once more to insert elements into DB
foreach ($processed_result['insertables'] as $i => $to_insert) {
	if (isset($to_insert['pkExpressionPrimary'])) {
		insert_primary($expression_id, $to_insert['pkExpressionPrimary'], $i);
	} else {
		insert_relation($expression_id, $to_insert['pkRelationalExpression'], $i);
	}
}

return array(
	'result' => 'success',
	'rel_id' => $expression_id,
	'expression' => $processed_result['str_expression'],
	'example' => $req['example'],
	'layer' => $processed_result['int_layer'],
	'shortUrl' => $short_url
);
