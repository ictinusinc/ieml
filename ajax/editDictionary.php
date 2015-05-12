<?php

$lang = strtolower($req['lang']);
$is_visual = $req['enumVisual'];

$parser = new IEMLParser();
$parser->visual_expression = $is_visual;
$parse_res = $parser->parseAllStrings($req['exp']);

if (!$parse_res->hasException() && !$parse_res->hasError()) {
	$oldState = Conn::queryArray("
		SELECT
			pkExpressionPrimary AS id, strExpression as expression, enumCategory
		FROM expression_primary
		WHERE pkExpressionPrimary = ".goodInt($req['id']));

	$oldExample = Conn::queryArrays("
		SELECT pkExpressionData
		FROM expression_data
		WHERE strLanguageISO6391 = '".goodString($lang)."'
		AND fkExpressionPrimary = ".goodInt($req['id']));
	
	$ret = array(
		'id' => $req['id'],
		'expression' => $req['exp'],
		'enumCategory' => $req['enumCategory'],
		'example' => $req['example'],
		'descriptor' => $req['descriptor'],
		'enumCompConc' => $req['iemlEnumComplConcOff'],
		'strEtymSwitch' => $req['iemlEnumSubstanceOff'].$req['iemlEnumAttributeOff'].$req['iemlEnumModeOff'],
		'enumShowEmpties' => $req['enumShowEmpties'],
		'tables' => array()
	);
	
	if (count($oldExample) > 0) {
		Conn::query("
			UPDATE expression_data
			SET
				strExample = '".goodString($req['example'])."',
				strDescriptor = '".goodString($req['descriptor'])."'
			WHERE fkExpressionPrimary = ".goodInt($req['id'])."
			AND   strLanguageISO6391 = '".goodString($lang)."' LIMIT 1");
	} else {
		Conn::query("
			INSERT INTO expression_data
				(fkExpressionPrimary, strExample, strDescriptor, strLanguageISO6391)
			VALUES
				(".goodInt($req['id']).", '".goodString($req['example'])."', '".goodString($req['descriptor'])."', '".goodString($lang)."')");
	}
	
	Conn::query("
		UPDATE expression_primary
		SET
			enumCategory = '".goodString($req['enumCategory'])."',
			strExpression = '".goodString($req['exp'])."',
			intSetSize = ".$parse_res->AST()->getSize().",
			intLayer = ".$parse_res->AST()->getLayer().",
			strFullBareString = '".goodString($parse_res->AST()->fullExpand()->bareStr())."',
			enumClass = '".goodString(IEMLParser::getClass($req['exp']))."',
			enumShowEmpties = '".goodString($req['enumShowEmpties'])."',
			enumCompConc = '".invert_bool($req['iemlEnumComplConcOff'], 'Y', 'N')."',
			strEtymSwitch = '".invert_bool($req['iemlEnumSubstanceOff'], 'Y', 'N')
							.invert_bool($req['iemlEnumAttributeOff'], 'Y', 'N')
							.invert_bool($req['iemlEnumModeOff'], 'Y', 'N')."',
			enumVisual = '" . ($req['enumVisual'] ? 'Y' : 'N') . "'
		WHERE pkExpressionPrimary = ".goodInt($req['id']));

	if ($ret['enumCategory'] == 'Y' && $oldState['enumCategory'] == 'N') {
		ensure_table_for_key($ret);
	}
	
	$ret = getTableForElement($ret, goodInt($ret['id']), $req);
} else {
	$ret = array(
		'result' => 'error',
		'resultCode' => 1,
		'error' => '"'.$req['exp'].'" is not a valid IEML string'
	);
}

$request_ret = $ret;
