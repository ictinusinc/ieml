<?php

$lang = strtolower($req['lang']);

$parse_res = IEMLParser::AST_or_FAIL($req['exp']);

if ($parse_res['resultCode'] == 0) {
	$set_size = $parse_res['AST']->getSize();
	$layer = $parse_res['AST']->getLayer();
	$bare_str = $parse_res['AST']->fullExpand()->bareStr();
	$class = IEMLParser::getClass($req['exp']);

	$clean_expression = goodString($req['exp']);
	$clean_bare_str = goodString($bare_str);

	$existing_expression = Conn::queryArray('
		SELECT pkExpressionPrimary AS id
		FROM expression_primary
		WHERE strExpression = \'' . $clean_expression . '\'
		AND strFullBareString = \'' . $clean_bare_str . '\'
	');

	if (empty($existing_expression)) {
		Conn::query("
			INSERT INTO expression_primary
				(strExpression, enumCategory, intSetSize, intLayer, strFullBareString, enumClass)
			VALUES
				('" . $clean_expression . "', '" . goodString($req['enumCategory']) . "', " . $set_size
					. ", " . $layer . ", '" . $clean_bare_str . "', '" . goodString($class) . "')");
				
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
			'enumClass' => $class
		);

		Conn::query("
			INSERT INTO expression_data
				(fkExpressionPrimary, strExample, strDescriptor, strLanguageISO6391)
			VALUES
				(".goodInt($ret['id']).", '".goodString($req['example'])."', '".goodString($req['descriptor'])."', '".goodString($lang)."')");

		//TODO: handle error if unable to add to library
		handle_request('addExpressionToLibrary', array(
			'id' => $ret['id'],
			'library' => $req['library']
		));
		
		if ($req['enumCategory'] == 'Y') {
			ensure_table_for_key($ret);
		}
		
		$ret = getTableForElement($ret, goodInt($ret['id']), $req);
		
		$request_ret = $ret;
	} else {
		$ret = array(
			'result' => 'error',
			'resultCode' => 1,
			'error' => 'Expression "' . $req['exp'] . '" already exists in the database'
		);
	}
} else {
	$ret = array(
		'result' => 'error',
		'resultCode' => 1,
		'error' => '"'.$req['exp'].'" is not a valid IEML string'
	);
}
