<?php

require_once(__DIR__ . '/includes/config.php');
require_once(__DIR__ . '/includes/URLShortener.class.php');

$URLShortener = new URLShortener();

$long_url = $URLShortener->lengthen_url($_GET['url']);

if ($long_url)
{
	header('HTTP/1.1 301 Moved Permanently');
	header('Location: ' . $long_url);
	
}
else
{
	header('HTTP/1.1 404 Not Found');
}
die();

?>
