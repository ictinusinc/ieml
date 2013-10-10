<?php

require_once(dirname(__FILE__).'/includes/config.php');
require_once(APPROOT.'/includes/ieml_parser/DebugLog.class.php');
require_once(APPROOT.'/includes/functions.php');
require_once(APPROOT.'/includes/ieml_parser/IEMLParser.class.php');
require_once(APPROOT.'/includes/ieml_parser/IEMLScriptGen.class.php');

Devlog::output_stream(NULL);
//Devlog::output_stream(fopen('php://output', 'w'));

$strings = array();
//$strings[] = IEMLScriptGen::staticGenerate(2, 2, 2);
//$strings[] = array('id' => 0, 'expression' => "U:+E:");
//$strings[] = array('id' => 0, 'expression' => "E:.-");
//$strings[] = array('id' => 0, 'expression' => "s.y.-");
//$strings[] = array('id' => 0, 'expression' => "A:.-'");
//$strings[] = array('id' => 0, 'expression' => "A:S:.we.-'");
$strings[] = array('id' => 0, 'expression' => "wo.");

/*
$strings = Conn::queryArrays("
	SELECT *, strExpression as expression
	FROM expression_primary
	WHERE strExpression IN ('O:', 'S:', 'E:+U:')
");
*/

foreach ($strings as $string) {
	$parser = new IEMLParser();
	$parserResult = $parser->parseString($string['expression']);

	echo pre_print($string['id'].': '.$string['expression']);

	echo '<pre>';
	if ($parserResult->hasException()) {
		echo ParseException::formatError($string['expression'], $parserResult->except(), TRUE);
	} else if ($parserResult->hasError()) {
		echo $parserResult->error()->getMessage();
	} else {
		echo $parserResult->AST()->toString();
		//echo 'success';
	}
	echo '</pre><hr/>';
}

?>
