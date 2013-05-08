<?

$user = Conn::queryObject("
   SELECT pkUser, strEmail, strPassHash, enumType, strDisplayName
   FROM users
   WHERE strEmail = ".goodInput($_REQUEST['loginEmail'])."
   LIMIT 1
");

//echo 'last query: <pre class="prettyprint">'.print_r(Conn::$lastQuery, true).'</pre>';

if ($user && bcrypt_check($_REQUEST['loginPassword'], $user->strPassHash)) {
	$_SESSION['pkUser'] = $user->pkUser;
	$_SESSION['enumType'] = $user->enumType;
	$_SESSION['strDisplayName'] = $user->strDisplayName;
	header('Location: /');
} else {
	header('Location: /?error=fail');
}
die();

?>