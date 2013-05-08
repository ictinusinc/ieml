<?php

//die('Dead.');

error_reporting(E_ALL);
ini_set('error_reporting', E_ALL);

define('DEBUG', TRUE);

header('Content-Type: text/html; charset=utf-8;q=0.7,*;q=0.3');
include_once('includes/config.php');
include_once('includes/functions.php');
include_once('includes/table_related/table_functions.php');

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

$exp = "s.";
$etym = gen_etymology($exp);

echo pre_dump($etym);

$keys = array();
$keys[] = array('expression' => "(O:+S:+T:)(A:+M:).");

unset($key);
foreach ($keys as &$key) {
    echo '<pre>'.$key['expression'].'</pre>';
	
	$info = IEML_gen_table_info($key['expression'], $IEML_lowToVowelReg);
	
	echo 'info: '.pre_dump($info);
}

?>