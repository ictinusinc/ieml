<?php

//die('Dead.');

error_reporting(E_ALL);
ini_set('error_reporting', E_ALL);

define('DEBUG', TRUE);

header('Content-Type: text/html; charset=utf-8;q=0.7,*;q=0.3');
include_once('includes/config.php');
include_once(APPROOT.'/includes/functions.php');
include_once(APPROOT.'/includes/table_related/table_functions.php');

?>
<html>
<head>
<style>
table {
    border-collapse: collapse;
}
table tbody tr td {
    border: 1px solid;
    text-align: center;
    padding: 3px 2px;
}
.empty_cell {
    border: none;
}
.small_def {
    font-size: 11pt;
}
.short_hr {
    width: 50%;
    max-width: 125px;
}
</style>
</head>
<body>
<div class="container" id="main_body"></div>
<?php

$keys = array();
//$keys[] = array('expression' => "O:O:.M:M:.-");
//$keys[] = array('expression' => "I:");
//$keys[] = array('expression' => "O:O:.A:U:O:.-M:M:.S:M:.-'");
//$keys[] = array('expression' => "O:M:.(M:+O:).-");
$keys[] = array('expression' => "M:M:.-O:M:.- (E:.- + s.y.-)'");
//$keys[] = array('expression' => "M:M:.O:M:.- + M:M:.M:O:.-");

foreach ($keys as &$key) {
    echo '<pre>'.$key['expression'].'</pre>';
	
	$tokens = \IEML_ExpParse\str_to_tokens($key['expression']);
	$AST = \IEML_ExpParse\tokens_to_AST($tokens);
	
	echo 'AST:'.pre_dump(\IEML_ExpParse\AST_to_infix_str($AST, $key['expression']));
	//echo 'AST:'.pre_dump($AST);
	
	echo 'etymology:'.pre_dump(gen_etymology($key['expression']));
	
	$info = IEML_gen_table_info($key['expression']);
	
	$key['concats'] = IEML_concat_complex_tables($info);
	
	for ($i=0; $i<count($key['concats']); $i++) {
		for ($j=0; $j<count($key['concats'][$i]); $j++) {
			//echo 'post_raw_table: '.pre_dump($key['concats'][$i][$j]);
			$key['concats'][$i][$j]['tables'] = IEML_postprocess_table($key['concats'][$i][$j]['tables'], function($el) { return $el; });
			
			//echo IEML_render_tables($key['concats'][$i][$j]['tables'], function($el) { echo $el; });
		}
	}
}

?>

<script src="/includes/js/libs/jquery-2.0.0.js"></script>
<script src="/includes/js/table_render.js"></script>
<script>
	var expressions = <?php echo json_encode($keys); ?>;
	
	console.log(expressions);
	
	$(function() {
		var str = '', render_callback = function(el) {
	            return el;
			};
		
		expressions.forEach(function(exp) {
			var tables = exp['concats'];
			
			for (var i=0; i<tables.length; i++) {
				str += IEML_render_table(tables[i][0]['tables'], render_callback);
				
				for (var j=1; j<tables[i].length; j++) {
					str += IEML_render_only_body(tables[i][j]['tables'], render_callback);
				}
				
				str += '<hr/>';
			}
			
			str += '<br/>';
		});
		
		$('#main_body').html(str);
	});
</script>
</body>
</html>
