<?php

$processed_result = process_editor_array($req['editor_array']);

if ($processed_result['result'] == 'error') {
	return $processed_result;
}

//insert into relational_expression table once we know it's cool
Conn::query('
	INSERT INTO relational_expression
		(vchExpression, vchExample, enumCompositionType, intLayer)
	VALUES
		(\'' . goodString($processed_result['str_expression']) . '\',
			\'' . goodString($req['example']) . '\',
			\'' . $processed_result['composition_type'] . '\',
			' . (is_null($int_layer) ? 'NULL' : '\'' . $processed_result['int_layer'] . '\'') . 
		')'
);

$expression_id = Conn::getId();

$short_url = URLShortener::shorten_url('rel-view/' . $expression_id);

//update expression with short url
Conn::query('
	UPDATE relational_expression
	SET vchShortUrl = \'' . goodString($short_url) . '\'
	WHERE pkRelationalExpression = ' . $expression_id
);

//run through array once more to insert elements into DB
foreach ($processed_result['insertables'] as $i => $to_insert) {
	if (isset($to_insert['pkExpressionPrimary'])) {
		insert_primary($expression_id, $to_insert['pkExpressionPrimary'], $i);
	} else {
		insert_relation($expression_id, $to_insert['pkRelationalExpression'], $i);
	}
}

handle_request('addExpressionToLibrary', array(
	'rel_id' => $expression_id,
	'library' => $req['library']
));

return array(
	'result' => 'success',
	'rel_id' => $expression_id,
	'expression' => $processed_result['str_expression'],
	'example' => $req['example'],
	'layer' => $processed_result['int_layer'],
	'shortUrl' => $short_url
);