<?php

//die('Dead.');

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
//get a list of categories

$keys = Conn::queryArrays("
    SELECT pkExpressionPrimary as id, strExpression as expression
    FROM expression_primary prim
    WHERE enumDeleted = 'N' AND enumCategory = 'Y'");

unset($key);
foreach ($keys as &$key) {
    echo $key['expression'].'<br/>';
    
	$key['table_info'] = IEML_gen_table_info($key['expression'], $IEML_lowToVowelReg);
	/*
	IEML_rem_empty_col($key['table_info'], , function($a) use ($key) {
		return !isset($key['table_info']['flat_asoc'][$info['headers'][$i][$j][$k]]) || !isset($key['table_info']['flat_asoc'][$info['headers'][$i][$j][$k]]['query']);
	});
	*/
	
	
	//generate an html table
	if (!array_key_exists('print', $_REQUEST) || $_REQUEST['print'] == 'true') {
		echo IEML_render_tables($key['table_info'], function ($exp) use ($key) {
			return '<div>'.$exp
				.(isset($key['table_info']['flat_asoc'][$exp]['query'])
					? '<hr class="short_hr" /><em class="small_def">'.$key['table_info']['flat_asoc'][$exp]['en'].'</em><hr class="short_hr" /><em class="small_def">'.$key['table_info']['flat_asoc'][$exp]['fr'].'</em>'
					: '')
				.'</div>';
		});
	}
    echo '<hr/>';
    
    if ($_REQUEST['regen'] == 'true')
        IEML_save_table($key['id'], $key['table_info']);
}

if ($_REQUEST['regen'] == 'true')
    echo 'done.';

?>