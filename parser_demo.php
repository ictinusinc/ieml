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
	<!-- bootstrap-2.0.1.css -->
	<link rel="stylesheet" type="text/css" href="<?php echo WEBAPPROOT.'/includes/css/bootstrap-2.0.1.min.css'; ?>">
	<style>
		.container {
			margin-top: 30px;
		}
	</style>
</head>
<body>
	<div class="container">
		<div class="row">
			<div class="span3 offset4">
				<input type="text" class="input-large string-in" />
			</div>
		</div>
		<div class="row">
			<div class="span12">
				<pre class="string-out"></pre>
			</div>
		</div>
	</div>
	
	<script type="text/javascript" src="<?php echo WEBAPPROOT.'/includes/js/libs/jquery-1.9.1.min.js'; ?>"></script>
	<script type="text/javascript" src="<?php echo WEBAPPROOT.'/includes/js/libs/bootstrap-2.3.2.min.js'; ?>"></script>
	<script>
		$(function() {
			$('.string-in').on('input', function(ev) {
				$.getJSON('', { 'eval': ev.target.value }, function(response) {
					if (response['resultCode'] > 0) {
						$('.string-out').html(response['error']);
					} else {
						$('.string-out').html(response['AST_string']);
					}
				});
			});
		});
	</script>
</body><?php
	
}

?>
