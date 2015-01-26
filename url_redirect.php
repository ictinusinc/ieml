<?php

require_once('includes/config.php');
require_once(__DIR__ . '/includes/URLShortener.class.php');

$long_url = URLShortener::lengthen_url($_GET['url']);

header('HTTP/1.1 301 Moved Permanently');
header('Location: /' . $long_url);

die();
