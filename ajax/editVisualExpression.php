<?php

$editor_array = $req['editor_array'];
$processed_result = process_editor_array($editor_array);
$expression_id = $req['id'];

if ($processed_result['result'] == 'error') {
	return $processed_result;
}

$insert_request = handle_request('editDictionary', array(
	'id' => $req['id'],
	'exp' => $processed_result['str_expression'],
	'example' => $req['example'],
	'lang' => $req['lang'],
	'library' => $req['library'],
	'enumVisual' => 'Y',
	'enumCategory' => 'N',
	'enumShowEmpties' => 'N',
	'descriptor' => '',
	'iemlEnumComplConcOff' => 'Y',
	'iemlEnumSubstanceOff' => 'Y'
));

Conn::query('
	DELETE FROM expression_primary_array
	WHERE fkExpressionPrimaryParent = ' . $expression_id
);

//run through array once more to insert elements into DB
foreach ($processed_result['processed_array'] as $i => $editor_expression)
{
	if (in_array($editor_expression['expression'], array('+', '*', '(', ')', '[', ']', '/')))
	{
		insert_operator($expression_id, $editor_expression['expression'], $i);
	}
	else
	{
		insert_primary($expression_id, $editor_expression['id'], $i);
	}
}

return handle_request('relationalExpression', array(
	'id' => $expression_id,
	'lang' => $req['lang'],
	'library' => $req['library']
));
