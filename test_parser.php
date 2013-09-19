<?php

require_once(dirname(__FILE__).'/includes/config.php');
require_once(APPROOT.'/includes/ieml_parser/DebugLog.class.php');
require_once(APPROOT.'/includes/functions.php');
require_once(APPROOT.'/includes/ieml_parser/IEMLParser.class.php');
require_once(APPROOT.'/includes/ieml_parser/IEMLScriptGen.class.php');

Debug::output_stream(NULL);

$strings = array();
//$strings[] = "o.p.-A:E:T:.-wa.-'";
//$strings[] = "(A:+E:+B:).-p.o.-'";asjdk
//$strings[] = "M:.-(E:.- + s.y.-)' + O:.-(E:.- + y.s.-)'";
//$strings[] = "(O:+M:)(U:+M:).";
//$strings[] = 'A:.';
//$strings[] = IEMLScriptGen::staticGenerate(2, 2, 2);

$strings = Conn::queryArrays("
	SELECT pkExpressionPrimary AS id, strExpression AS expression
	FROM expression_primary
	WHERE enumDeleted = 'N'
");


foreach ($strings as $string) {
	$parser = new IEMLParser();
	$parserResult = $parser->parseString($string['expression']);

	echo '<pre>'.$string['id'].': '.$string['expression'].'</pre>';

	echo '<pre>';
	if ($parserResult->hasException()) {
		echo ParseException::formatError($string['expression'], $parserResult->except(), TRUE);
	} else if ($parserResult->hasError()) {
		echo $parserResult->error()->getMessage();
	} else {
		//echo $parserResult->AST()->toString();
		echo 'success';
	}
	echo '</pre><hr/>';
}

?>
