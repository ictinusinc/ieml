<?php
//error_reporting(E_ALL);

date_default_timezone_set('UTC');

define("MYSQLSERVER","127.0.0.1");
define("USERNAME","ieml");
define("PASSWORD","ieml#ictinus");
define("DATABASE","ieml");

//use include(APPROOT.'...'); and src="<?php echo WEBAPPROOT; ? >..." for absolute URLs
//this helps to simplify the process of moving the app around the place
//also clears up confusion about nested relative includes
define('TOPDOMAIN', 'punchclock.ictinusdesign.com');
define('DOCROOT', $_SERVER['DOCUMENT_ROOT']);
define('WEBROOT', 'http://'.$_SERVER['HTTP_HOST']);
define('OFFROOT', ''); //in case the app needs to be in a subdirectory
define('APPROOT', DOCROOT . OFFROOT);
define('WEBAPPROOT', WEBROOT . OFFROOT);

//name of custom session table in db if using custom sessions
define('SESSIONTABLE', 'sessions');

$lang = ((array_key_exists('lang', $_REQUEST) && strtolower($_REQUEST['lang']) == 'fr') ? 'FR' : 'EN');

?>