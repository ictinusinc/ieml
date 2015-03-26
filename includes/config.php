<?php

date_default_timezone_set('UTC');

define("MYSQLSERVER","127.0.0.1");
define("USERNAME","ieml");
define("PASSWORD","ieml#ictinus");
define("DATABASE","ieml");

if (array_key_exists('DOCUMENT_ROOT', $_SERVER) && $_SERVER['DOCUMENT_ROOT']) {
	define('DOCROOT', $_SERVER['DOCUMENT_ROOT']);
} else {
	define('DOCROOT', dirname(__FILE__).'/..');
}

define('RSCHEME', @$_SERVER['REQUEST_SCHEME'] ?: 'http');

if (array_key_exists('HTTP_HOST',$_SERVER)) {
	define('WEBROOT', $_SERVER['HTTP_HOST']);
} else {
	define('WEBROOT', 'localhost');
}

define('OFFROOT', ''); //in case the app needs to be in a subdirectory
define('APPROOT', DOCROOT . OFFROOT);
define('WEBAPPROOT', WEBROOT . OFFROOT);

//name of custom session table in db if using custom sessions
define('SESSIONTABLE', 'sessions');

$lang = ((array_key_exists('lang', $_REQUEST) && strtolower($_REQUEST['lang']) == 'fr') ? 'FR' : 'EN');

function pre_dump() {
    ob_start();
    echo '<pre>' . "\n"; call_user_func_array('var_dump', func_get_args()); echo '</pre>' . "\n";
    return ob_get_clean();
}

function pre_dd() {
	echo call_user_func_array('pre_dump', func_get_args());
	die('DEAD! X_X' . "\n");
}
