<?php

require_once(__DIR__ . '/includes/URLShortener.class.php');

$id = URLShortener::id_from_short_url($_GET['url']);

header('HTTP/1.1 301 Moved Permanently');
header('Location: /EN/library-1/view/' . $id);

die();
