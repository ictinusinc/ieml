<?php

require_once(dirname(__FILE__).'/includes/config.php');
require_once(APPROOT.'/includes/ieml_parser/DebugLog.class.php');
require_once(APPROOT.'/includes/functions.php');
require_once(APPROOT.'/includes/ieml_parser/IEMLParser.class.php');
require_once(APPROOT.'/includes/ieml_parser/IEMLScriptGen.class.php');

//Devlog::output_stream(NULL);
Devlog::output_stream(fopen('php://output', 'w'));
//Devlog::output_stream(fopen('php://memory', 'r+'));

$strings = array();
//$strings[] = IEMLScriptGen::staticGenerate(2, 2, 2);
//$strings[] = array('id' => 0, 'expression' => "U:+E:");
//$strings[] = array('id' => 0, 'expression' => "E:.-");
//$strings[] = array('id' => 0, 'expression' => "s.y.-");
//$strings[] = array('id' => 0, 'expression' => "A:.-'");
//$strings[] = array('id' => 0, 'expression' => "A:S:.we.-'");
//$strings[] = array('id' => 0, 'expression' => "(b.a.-b.a.-f.o.-'+ t.a.-b.a.-f.o.-') (E:A:T:.-' + E:F:.wa.-')E:E:U:.-',_");
//$strings[] = array('id' => 0, 'expression' => "(T:. + F:.)U:.-'");
$strings[] = array('id' => 0, 'expression' => "
(U:+S:) / t. / d. /
wo.y.- / wa.k.- / e.y.- / s.y.- /
k.h.- / a.u.-we.h.-' / n.-y.-s.y.-'
/
(
n.-y.-s.y.-' s.o.-k.o.-' E:E:A:.-',
+
n.-y.-s.y.-' b.i.-b.i.-' E:A:.m.-',
)
/
u.e.-we.h.-' m.a.-n.a.-f.o.-' E:E:S:.-',
/
(
e.-' s.e.-k.u.-' E:E:T:.-',_
+
e.-' (b.a.-b.a.-f.o.-'+ t.a.-b.a.-f.o.-') E:E:S:.-',_
+
k.i.-b.i.-t.u.-' b.x.-' E:E:U:.-',_
+
s.e.-k.u.-'E:.-'E:B:.s.-', k.i.-b.i.-t.u.-', E:T:.x.-',_
+
s.e.-k.u.-', b.-u.-'E:.-'E:F:.wa.-', E:T:.x.-',_
+
(b.a.-b.a.-f.o.-'+ t.a.-b.a.-f.o.-') (E:A:T:.-' + E:F:.wa.-') E:E:U:.-',_
+
(b.a.-b.a.-f.o.-' + t.a.-b.a.-f.o.-') l.o.-m.o.-s.u.-' E:E:U:.-',_
)

");

/*
$strings = Conn::queryArrays("
	SELECT *, strExpression as expression
	FROM expression_primary
	WHERE strExpression IN ('O:', 'S:', 'E:+U:')
");
*/

foreach ($strings as $string) {
	$parser = new IEMLParser();
	$parserResult = $parser->parseAllStrings($string['expression']);

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

// rewind($stream);
// echo stream_get_contents($stream);

?>
