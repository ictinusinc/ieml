<?php

require_once(__DIR__ . '/includes/URLShortener.class.php');

$n = 12312;
var_dump($n);
var_dump( URLShortener::short_url_from_id( $n ) );
var_dump( URLShortener::id_from_short_url( URLShortener::short_url_from_id($n) ) );
