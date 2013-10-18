<?php
//error_reporting(E_ALL);

date_default_timezone_set('UTC');

define("MYSQLSERVER","127.0.0.1");
define("USERNAME","ieml");
define("PASSWORD","ieml#ictinus");
define("DATABASE","ieml");

if (array_key_exists('DOCUMENT_ROOT',$_SERVER)) {
	define('DOCROOT', $_SERVER['DOCUMENT_ROOT']);
} else {
	define('DOCROOT', dirname(__FILE__).'/..');
}

if (array_key_exists('HTTP_HOST',$_SERVER)) {
	define('WEBROOT', 'http://'.$_SERVER['HTTP_HOST']);
} else {
	define('WEBROOT', 'http://rentals.beaconlite.ca');
}

define('OFFROOT', ''); //in case the app needs to be in a subdirectory
define('APPROOT', DOCROOT . OFFROOT);
define('WEBAPPROOT', WEBROOT . OFFROOT);

//name of custom session table in db if using custom sessions
define('SESSIONTABLE', 'sessions');

$lang = ((array_key_exists('lang', $_REQUEST) && strtolower($_REQUEST['lang']) == 'fr') ? 'FR' : 'EN');

?>
