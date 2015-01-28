<?php

require_once(dirname(__FILE__).'/includes/config.php');
require_once(APPROOT.'/includes/ieml_parser/DebugLog.class.php');
require_once(APPROOT.'/includes/functions.php');

if (array_key_exists('eval', $_REQUEST) && $_REQUEST['eval']) {
	header('Content-type: application/json');
	require_once(APPROOT.'/includes/ieml_parser/IEMLParser.class.php');
	require_once(APPROOT.'/includes/ieml_parser/IEMLScriptGen.class.php');
	
	function handle_string($string) {
		$parser = new IEMLParser();
		$parserResult = $parser->parseAllStrings($string);
		$ret = array();
		
		if ($parserResult->hasException()) {
			$ret['result'] = 'error';
			$ret['resultCode'] = 1;
			$ret['error'] = ParseException::formatError($string, $parserResult->except(), FALSE);
		} else if ($parserResult->hasError()) {
			$ret['result'] = 'error';
			$ret['resultCode'] = 2;
			$ret['error'] = $parserResult->error()->getMessage();
		} else {
			$ret['result'] = 'success';
			$ret['resultCode'] = 0;
			$ret['AST_string'] = $parserResult->AST()->toString();
		}
		
		return $ret;
	}
	
	echo (isset($_REQUEST['callback']) ? $_REQUEST['callback'].'(' : '').json_encode(handle_string($_REQUEST['eval'])).(isset($_REQUEST['callback']) ? ')' : '');
} else {
?><!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<title>IEML Dictionary</title>
	<!-- Bootstrap -->
	<link rel="stylesheet" type="text/css" href="//maxcdn.bootstrapcdn.com/bootstrap/3.3.1/css/bootstrap.min.css">
	<style>
		.container {
			margin-top: 30px;
		}
	</style>
</head>
<body>
	<div class="container">
		<div class="row">
			<div class="col-md-4 col-md-offset-4">
				<input type="text" class="form-control string-in" />
			</div>
		</div>
		<div class="row">
			<div class="col-md-12">
				<pre class="string-out"></pre>
			</div>
		</div>
	</div>
	
	<script src="//ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
	<script src="//maxcdn.bootstrapcdn.com/bootstrap/3.3.1/js/bootstrap.min.js"></script>
	<script>
		$(function() {
			$('.string-in').on('input', function(ev) {
				$.getJSON('', { 'eval': ev.target.value }, function(response) {
					if (response.resultCode > 0) {
						$('.string-out').html(response.error);
					} else {
						$('.string-out').html(response.AST_string);
					}
				});
			});
		});
	</script>
</body><?php
	
}
