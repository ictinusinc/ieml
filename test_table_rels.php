<?php

error_reporting(E_ALL);
ini_set('error_reporting', E_ALL);

define('DEBUG', TRUE);

header('Content-Type: text/html; charset=utf-8;q=0.7,*;q=0.3');
include_once('includes/config.php');
include_once(APPROOT.'/includes/functions.php');
require_once(APPROOT.'/includes/table_related/table_functions.php');
include_once(APPROOT.'/includes/common_functions.php');

$expression = "m.u.-b.u.-'";

$ret = Conn::queryArray("
	SELECT
		prim.pkExpressionPrimary as id, prim.strExpression as expression,
		prim.enumCategory, sublang.strDescriptor AS descriptor,
		t_key.enumShowEmpties, t_key.enumCompConc, t_key.strEtymSwitch
	FROM expression_primary prim
	LEFT JOIN expression_descriptors sublang
		ON sublang.fkExpressionPrimary = prim.pkExpressionPrimary
	LEFT JOIN table_2d_ref t2dref ON prim.pkExpressionPrimary = t2dref.fkExpressionPrimary
	LEFT JOIN table_2d_id t2did ON t2dref.fkTable2D = t2did.pkTable2D
	LEFT JOIN expression_primary t_key ON t2did.fkExpression = t_key.pkExpressionPrimary
	WHERE strLanguageISO6391 = '".goodString("en")."'
	AND   prim.strExpression = '".goodString($expression)."'
	AND   prim.enumDeleted = 'N'");

if (!$ret) {
	$ret = array(
		'id' => 0,
		'expression' => $expression,
		'enumCategory' => 'N',
		'enumShowEmpties' => 'N',
		'enumCompConc' => 'Y',
		'strEtymSwitch' => 'YYY'
	);
}

echo '$ret: '.pre_print($ret['expression']);

$ret = getTableForElement($ret, $ret['id'], array(
	'a' => 'expression',
	'lang' =>'en',
	'lexicon' =>'BasicLexicon',
	'exp' => $expression,
	'id' => 2058));

echo pre_print($ret);

?>
