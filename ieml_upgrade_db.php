<?php

die('dead.');

include_once('includes/config.php');
header('Content-Type: text/html; charset=utf-8;q=0.7,*;q=0.3');
include_once('includes/functions.php');

$en_old = Conn::queryArrays("SELECT id, expression, descriptor, enumDeleted, enumCategory FROM dynamic_fields_en ORDER BY id");
$fr_old = Conn::queryArrays("SELECT id, expression, descriptor, enumDeleted, enumCategory FROM dynamic_fields_fr ORDER BY id");

echo "old db retrieved.\n";

for ($i=0; $i<count($en_old); $i++) {
    Conn::query("INSERT INTO expression_primary
            (strExpression, enumDeleted, enumCategory)
        VALUES
            (".goodInput($en_old[$i]['expression']).", ".goodInput($en_old[$i]['enumDeleted']).", ".goodInput($en_old[$i]['enumCategory']).")");
    $last_id = Conn::getId();
    Conn::query("INSERT INTO expression_descriptors
            (fkExpressionPrimary, strDescriptor, strLanguage)
        VALUES
            (".$last_id.", ".goodInput($en_old[$i]['descriptor']).", 'eng'),
            (".$last_id.", ".goodInput($fr_old[$i]['descriptor']).", 'fra')");
}

echo "new db set.\n";

echo "done.\n";

?>
