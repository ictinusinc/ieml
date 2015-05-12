<?php

$ret = NULL;

$lang = strtolower($req['lang']);
$is_visual = $req['enumVisual'];

$parser = new IEMLParser();
$parser->visual_expression = $is_visual;
$parse_res = $parser->parseAllStrings($req['exp']);

if (!$parse_res->hasException() && !$parse_res->hasError()) {
	$set_size = $parse_res->AST()->getSize();
	$layer = $parse_res->AST()->getLayer();
	$bare_str = $parse_res->AST()->fullExpand()->bareStr();
	$class = IEMLParser::getClass($req['exp']);

	$clean_expression = goodString($req['exp']);
	$clean_bare_str = goodString($bare_str);

	$existing_expression = Conn::queryArray('
		SELECT pkExpressionPrimary AS id, enumDeleted
		FROM expression_primary
		WHERE strExpression = \'' . $clean_expression . '\'
		AND strFullBareString = \'' . $clean_bare_str . '\'
	');

	if (!$existing_expression || $existing_expression && $existing_expression['enumDeleted'] == 'Y') {
		if ($existing_expression) {
			Conn::query('
				DELETE IGNORE FROM expression_primary
				WHERE pkExpressionPrimary = ' . $existing_expression['id'] . '
			');
		}

		Conn::query("
			INSERT INTO expression_primary
				(
					strExpression,
					enumCategory,
					intSetSize,
					intLayer,
					strFullBareString,
					enumClass,
					enumVisual
				)
			VALUES
				(
					'" . $clean_expression . "',
					'" . goodString($req['enumCategory']) . "',
					" . $set_size . ",
					" . $layer . ",
					'" . $clean_bare_str . "',
					'" . goodString($class) . "',
					'" . ($is_visual ? 'Y' : 'N') . "'
				)
		");
				
		$ret = array(
			'id' => Conn::getId(),
			'expression' => $req['exp'],
			'enumCategory' => $req['enumCategory'],
			'example' => $req['example'],
			'descriptor' => $req['descriptor'],
			'enumShowEmpties' => $req['enumShowEmpties'],
			'intSetSize' => $set_size,
			'intLayer' => $layer,
			'strFullBareString' => $bare_str,
			'enumClass' => $class,
			'enumCompConc' => 'N',
			'strEtymSwitch' => 'NNN'
		);

		Conn::query("
			INSERT INTO expression_data
				(
					fkExpressionPrimary, 
					strExample,
					strDescriptor,
					strLanguageISO6391
				)
			VALUES
				(
					".goodInt($ret['id']).",
					'".goodString($req['example'])."',
					'".goodString($req['descriptor'])."',
					'".goodString($lang)."'
				)
		");

		//TODO: handle error if unable to add to library
		handle_request('addExpressionToLibrary', array(
			'id' => $ret['id'],
			'library' => $req['library']
		));
		
		if ($req['enumCategory'] == 'Y') {
			ensure_table_for_key($ret);
		}
		
		$ret = getTableForElement($ret, goodInt($ret['id']), $req);
	} else {
		$ret = array(
			'result' => 'error',
			'resultCode' => 1,
			'error' => 'Duplicate expression.'
		);
	}
} else {
	$ret = array(
		'result' => 'error',
		'resultCode' => 1,
		'error' => '"'.$req['exp'].'" is not a valid IEML string'
	);
}

return $ret;
