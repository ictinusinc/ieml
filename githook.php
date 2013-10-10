<?php

error_reporting(E_ALL);
ignore_user_abort(true);

function var_dump_str() {
    ob_start();
    call_user_func_array('var_dump', func_get_args());
    return ob_get_clean();
}

$mailto = 'bence.me@gmail.com';
$mail_title = 'GitHub `git pull`@'.date('Y-m-d H:i:s');
$headers = 	"From: ieml_autonotify@ictinusdesign.com\r\n".
			"MIME-Version: 1.0\r\n".
			"Content-Type: text/html; charset=ISO-8859-1\r\n".
			"X-Mailer: PHP/".phpversion();

// GitHub will hit us with POST (http://help.github.com/post-receive-hooks/)
if (!empty($_REQUEST['payload'])) {
	// pull from master
	$result = `git fetch origin master`;
	
	/* mail has been disabled for the entire ictinusdesign.com server, so this is pointless
	
	$payload = json_decode($_REQUEST['payload'], true);
	//TODO: better git pull error handling
	$email_str = 'RESULT: '.$result."\r\n\r\nINFO: ".var_dump_str($payload);
	
	mail($mailto, $mail_title, $email_str, $headers);
	*/
} else {
	/*
	mail($mailto, $mail_title, "ERROR: githook.php hit with no payload.\r\n\r\nINFO: ".var_dump_str($_REQUEST), $headers);
	*/
}
 
?>
