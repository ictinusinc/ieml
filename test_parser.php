<?php

require_once(dirname(__FILE__).'/includes/config.php');
require_once(APPROOT.'/includes/ieml_parser/DebugLog.class.php');
require_once(APPROOT.'/includes/functions.php');
require_once(APPROOT.'/includes/ieml_parser/IEMLParser.class.php');
require_once(APPROOT.'/includes/ieml_parser/IEMLScriptGen.class.php');

Debug::output_stream(NULL);

//$string = "o.p.-A:E:T:.-wa.-'";
//$string = "(A:+E:+B:).-p.o.-'";
//$string = "M:.-(E:.- + s.y.-)' + O:.-(E:.- + y.s.-)'";
//$string = "(O:+M:)(U:+M:).";
$string = "** (O:+M:)(O:+M:). *";
//$string = IEMLScriptGen::staticGenerate(2, 2, 2);

$parser = new IEMLParser();
$parserResult = $parser->parseString($string);
	
//var_dump($parserResult);

echo '<pre>'.$string.'</pre>';

echo '<pre>';
if ($parserResult->hasError()) {
	echo ParseException::formatError($string, $parserResult->except(), TRUE);
} else {
	echo $parserResult->AST()->toString();
}
echo '</pre>';

?>
