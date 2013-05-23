<?php

//die('Dead.');

error_reporting(E_ALL);
ini_set('error_reporting', E_ALL);

define('DEBUG', TRUE);

header('Content-Type: text/html; charset=utf-8;q=0.7,*;q=0.3');
include_once('includes/config.php');
include_once(APPROOT.'/includes/functions.php');
include_once(APPROOT.'/includes/table_related/table_functions.php');

?>
<style>
table {
    border-collapse: collapse;
}
table tbody tr td {
    border: 1px solid;
    text-align: center;
    padding: 3px 2px;
}
.empty_cell {
    border: none;
}
.small_def {
    font-size: 11pt;
}
.short_hr {
    width: 50%;
    max-width: 125px;
}
</style>
<?php

$keys = array();
//$keys[] = array('expression' => "M:M:.-O:M:.-(E:.- + s.y.-)' + M:M:.-M:O:.-(E:.- + s.y.-)'");
//$keys[] = array('expression' => "(O:+M:)(O:+M:).");
//$keys[] = array('expression' => "(U:+S:)(O:+M:). + (A:+S:)(O:+M:). + (U:+B:)(O:+M:). + (A:+B:)(O:+M:). + (U:+T:)(O:+M:). + (A:+T:)(O:+M:).");
//$keys[] = array('expression' => "(U:+M:)(O:+M:). + (A:+M:)(O:+M:).");
//$keys[] = array('expression' => "(A:+S:+U:)(M:+E:+O:).");
$keys[] = array('expression' => "M:T:.");

unset($key);
foreach ($keys as &$key) {
    echo '<pre>'.$key['expression'].'</pre>';
	
	$info = IEML_gen_table_info($key['expression'], $IEML_lowToVowelReg);
	
	$tokens = \IEML_ExpParse\str_to_tokens($key['expression']);
	$AST = \IEML_ExpParse\tokens_to_AST($tokens);
	
	echo 'AST:'.pre_dump(\IEML_ExpParse\AST_to_infix_str($AST, $key['expression']));
	//echo 'AST:'.pre_dump($AST);
	
	echo 'etymology:'.pre_dump(gen_etymology($key['expression']));
	
	echo IEML_render_tables($info, function($el) {
		echo $el;
	});
	
	echo 'info: '.pre_dump($info['post_raw_table']);
}

?>