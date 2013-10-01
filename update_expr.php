<?php

die('Die called. X_X');

require_once(dirname(__FILE__).'/includes/config.php');
require_once(APPROOT.'/includes/ieml_parser/DebugLog.class.php');
require_once(APPROOT.'/includes/functions.php');
require_once(APPROOT.'/includes/ieml_parser/IEMLParser.class.php');

//Devlog::output_stream(fopen('php://output', 'w'));

$expr = Conn::queryArrays("
	SELECT pkExpressionPrimary, strExpression
	FROM expression_primary
	WHERE strFullBareString IS NULL
	AND enumDeleted = 'N'
	ORDER BY pkExpressionPrimary
");

for ($i=0; $i<count($expr); $i++) {
	$parse_res = IEMLParser::AST_or_FAIL($expr[$i]['strExpression']);

	if ($parse_res['resultCode'] == 0) {
		$set_size = $parse_res['AST']->getSize();
		$layer = $parse_res['AST']->getLayer();
		$bare_str = $parse_res['AST']->fullExpand()->bareStr();

		Conn::query("
			UPDATE expression_primary
			SET
				intLayer = ".(isset($layer) ? $layer : 'NULL').",
				intSetSize = ".$set_size.",
				strFullBareString = '".goodString($bare_str)."'
			WHERE pkExpressionPrimary = ".$expr[$i]['pkExpressionPrimary']."
		");
		echo $i.': '.htmlentities($expr[$i]['strExpression']).' ('.htmlentities($bare_str).')<br/>'."\n";
	} else {
		echo $i.': '.htmlentities($expr[$i]['strExpression']).' (NULL)<br/>'."\n";
	}

}

echo 'Done! ^_^'

?>
