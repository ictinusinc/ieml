<?php

include_once('includes/config.php');

echo '<pre>';
var_dump(APPROOT);
echo '</pre>';

die('Dead.');

header('Content-Type: text/html; charset=utf-8;q=0.7,*;q=0.3');
include_once('includes/config.php');
include_once(APPROOT.'/includes/functions.php');
include_once(APPROOT.'/includes/table_related/table_functions.php');
include_once(APPROOT.'/includes/common_functions.php');

$tables = Conn::queryArrays("
	SELECT t2did.*, ep.strExpression as expression
	FROM table_2d_id t2did
	LEFT JOIN expression_primary ep ON t2did.fkExpression = ep.pkExpressionPrimary
	WHERE t2did.jsonTableLogic = ''
	AND t2did.enumDeleted = 'N'");
$tinfo = NULL;
$jstring = NULL;

for ($i=0; $i<count($tables); $i++) {
    $tinfo = IEML_gen_table_info($tables[$i]['expression'], $IEML_lowToVowelReg);
    $jstring = goodString(json_encode($tinfo['post_raw_table']));
    
    echo pre_dump($jstring);
    
    Conn::query("UPDATE table_2d_id SET jsonTableLogic = '".$jstring."' WHERE pkTable2D = ".$tables[$i]['pkTable2D']);
}

echo 'Done.';

?>