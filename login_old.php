<?

switch($action) {
   case 'auth': include_once('login/model/doLogin.php'); break;
   default: include_once('login/view/formLogin.php'); break;
}

?>
