<?php

require_once(dirname(__FILE__).'/includes/config.php');
require_once(APPROOT.'/includes/functions.php');
require_once(APPROOT.'/includes/table_related/IEMLParser.class.php');

$parser = new IEMLParser();
echo pre_dump($parser->parseString("A:S:T:. + we. + wo."));

?>
