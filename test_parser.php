<?php

require_once(dirname(__FILE__).'/includes/config.php');
require_once(APPROOT.'/includes/functions.php');
require_once(APPROOT.'/includes/ieml_parser/IEMLParser.class.php');

$parser = new IEMLParser();
$AST =
//	$parser->parseString("o.p.-A:E:T:.-wa.-'");
//	$parser->parseString("(A:+B:).p.-");
	$parser->parseString("M:.-(E:.- + s.y.-)' + O:.-(E:.- + y.s.-)'");

//echo pre_dump($AST);

echo '<pre>'.$AST->toString().'</pre>';

?>
