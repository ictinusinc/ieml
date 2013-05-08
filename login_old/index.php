<?php

ob_start();
include_once('../includes/config.php');
require_once(APPROOT.'/includes/LANGFILE.php');
include_once(APPROOT.'/includes/functions.php');

$action = isset($_REQUEST['a']) ? $_REQUEST['a'] : '';

include_once(APPROOT.'/includes/header.php');

switch($action) {
   case 'auth': include_once('model/doLogin.php'); break;
   default: include_once('view/formLogin.php'); break;
}

include_once(APPROOT.'/includes/footer.php');

?>
