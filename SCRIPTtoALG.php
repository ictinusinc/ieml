<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8">
		<title>IEML Dictionary</title>
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<meta name="description" content="">
		<meta name="author" content="">
		
		<!-- Le HTML5 shim, for IE6-8 support of HTML5 elements -->
		<!--[if lt IE 9]>
			<script src="//html5shim.googlecode.com/svn/trunk/html5.js"></script>
		<![endif]-->
		
		<!-- Stylesheets -->
		<link type="text/css" rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/3.0.0/css/bootstrap.min.css"/>
		<link type="text/css" rel="stylesheet" href="/includes/css/style.css" />
	</head>
	<body>
		<div class="container contentzone"><input type="text" id="ieml_script" placeholder="Script Expression" /></div>
		<div class="container contentzone" id="ieml_result"></div>
		
		<!-- Javascript Files -->
		<script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
		<script type="text/javascript" src="/includes/js/treecon.js"></script>
		<script type="text/javascript">
			$(document).on('input', '#ieml_script', function() {
				var res = STARtoAL.multiConvert($(this).val()), str = '';
				
				for (var i in res) {
					str += '<div style="font-family: monospace;">';
					for (var j in res[i]) {
						str += STARtoAL.astToStr(res[i][j]) + "\n<br />";
					}
					str += '</div>';
				}
				
				$('#ieml_result').html(str);
			});
		</script>
	</body>
</html>