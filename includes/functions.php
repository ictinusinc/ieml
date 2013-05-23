<?
//string and int functions----------------------------------------------------------------------

function int_clamp($val, $min, $max) {
	return $val >= $min ? ($val <= $max ? $val : $max) : $min;
}

function goodInt($item){
	return (isset($item) && !empty($item)) ? (int)$item : 0;
}

function mysql_escape_mimic($inp) { 
    if(is_array($inp)) return array_map(__METHOD__, $inp); 

    if(!empty($inp) && is_string($inp)) return str_replace(array('\\', "\0", "\n", "\r", "'", '"', "\x1a"), array('\\\\', '\\0', '\\n', '\\r', "\\'", '\\"', '\\Z'), $inp); 

    return $inp; 
} 

function goodInput($var){
	if (get_magic_quotes_gpc()) $var = stripslashes($var);
	$var = "'".mysql_escape_mimic(trim($var))."'";
	
	return $var;
}

function goodString($var){
	if (get_magic_quotes_gpc()) $var = stripslashes($var);
	$var = mysql_escape_mimic(trim($var));
	
	return $var;
}

function randomString($length = 22) {
   $charset = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
   $len = strlen($charset)-1;
   $ret = '';
   for ($i=0; $i<$length; $i++) $ret .= $charset[mt_rand(0, $len)]; 
   
   return $ret;
}

function pre_dump() {
    ob_start();
    echo '<pre>'; call_user_func_array('var_dump', func_get_args()); echo '</pre>';
    return ob_get_clean();
}


function bcrypt_hash($password, $work_factor = 8) {
   $salt = randomString();
   if ($work_factor < 4 || $work_factor > 31) $work_factor = 6;
   $salt = '$2a$'.str_pad($work_factor, 2, '0', STR_PAD_LEFT).'$'.$salt;
   return crypt($password, $salt);
}

function bcrypt_check($password, $stored_hash) {
   return crypt($password, $stored_hash) == $stored_hash;
}

function array_any($arr, $cb = NULL) {
    if ($cb == NULL) {
        foreach ($arr as $val) {
            if ($val) return TRUE;
        }
    } else {
        return array_any(array_map($cb, $arr));
    }
    return FALSE;
}

function array_all($arr, $cb = NULL) {
    if ($cb == NULL) {
        foreach ($arr as $val) {
            if (!$val) return FALSE;
        }
        return TRUE;
    } else {
        return array_all(array_map($cb, $arr));
    }
    return FALSE;
}

function invert_bool($val, $true, $false) {
	return $val === $true ? $false : $true;
}

function assert_arr($vars, $arr) {
	for ($i=0; $i<count($vars); $i++)
		if (!isset($arr[$vars[$i]])) return $vars[$i];
	return TRUE;
}

function assert_format($var) {
	return array('result' => 'error', 'error' => '"'.$var.'" must be set');
}

//custom session functions--------------------------------------------------------------------------

/**
 * returns an associative array of values for the specified session
**/
function get_session_data($id) {
	$raw_query = Conn::queryArray("
		SELECT id, jsonSessionData
		FROM ".SESSIONTABLE."
		WHERE id = ".goodInt($id)."
	");
	
	if (isset($raw_query)) {
		return json_decode($raw_query['jsonSessionData'], true);
	} else {
		return NULL;
	}
}

/**
 * sets session data
 * should be done at the completion of everything
 *
 * returns the session data passed to it
**/
function set_session_data($id, $sess) {
	Conn::query("
		UPDATE ".SESSIONTABLE."
		SET jsonSessionData = '".goodString(json_encode($sess))."'
		WHERE id = ".goodInt($id)."
	");
	
	return $sess;
}

/**
 * starts a new session
 * a unique id can be specified if provided, otherwise the IP and first 45 characters of the User Agent will be used
 *
 * returns newly created session 'token'
**/
function start_new_session() {
	Conn::query("
		INSERT INTO ".SESSIONTABLE." (jsonSessionData) VALUES ('{}')
	");
	
	return Conn::getId();
}

/**
 * destroy an existing session
 * should be done when a user logs out
 *
 * returns TRUE if successful, FALSE otherwise
**/
function destroy_session($id) {
	Conn::query("
		DELETE FROM ".SESSIONTABLE."
		WHERE id = ".goodInt($id)."
	");
	
	return TRUE;
}

/**
 * makes a bunch of guesses to end a session, forever
 *
 * returns TRUE if successful, FALSE otherwise
**/
function smart_destroy_session($request) {
	$ret = FALSE;
	
	if (isset($request['auth_token'])) {
		$ret = destroy_session($request['auth_token']);
	} else if (isset($_COOKIE['auth_token'])) {
		$ret = destroy_session($_COOKIE['auth_token']);
	}
	
	setcookie('auth_token', '', time()-3600, '/'); //set cookie timeout a minute from now, in the past
	
	session_destroy();
	
	return $ret;
}

/**
 * makes a bunch of guesses to get a session up and running
 *
 * returns a previously started session, or NULL on error
**/
function smart_session($request) {
	$sess = NULL;
	
	if (isset($request['auth_token'])) {
		$sess = get_session_data($request['auth_token']);
		$sess['auth_token'] = $request['auth_token'];
	} else if (isset($_COOKIE['auth_token'])) {
		$sess = get_session_data($_COOKIE['auth_token']);
		$sess['auth_token'] = $_COOKIE['auth_token'];
	} else {
		$sess = array(
			'auth_token' => start_new_session()
		);
	}
	
	if (!isset($_COOKIE['auth_token'])) {
		setcookie('auth_token', $sess['auth_token'], time()+157680000, '/'); //set for 5 years from now
	}
	
	session_start();
	
	$_SESSION = $sess;
}

//common functions----------------------------------------------------------------------------------

/**
 * login, now modular!
 *
 * returns an associative array with user info, or NULL if something failed
**/
function api_login($userName, $password) {
	$user = Conn::queryArray("
		SELECT
			pkUser, strDisplayName, strEmail, strPassHash, enumType
		FROM users u
		WHERE u.strEmail = ".goodInput($userName)."
		AND   u.enumDeleted = 'no'
		LIMIT 1
	");
	
	if ($user && bcrypt_check($password, $user['strPassHash'])) {
		$ret = array(
			'pkUser' => $user['pkUser'],
			'strDisplayName' => $user['strDisplayName'],
			'strEmail' => $user['strEmail'],
			'enumType' => $user['enumType']
		);
		
		return $ret;
	} else {
		return NULL;
	}
}

function api_log($msg) {
	$lfile = @fopen(APPROOT.'/access_log.log', 'a');
	
	if (FALSE !== $lfile) {
		fwrite($lfile, $msg);
		fclose($lfile);
		
		return TRUE;
	}
	
	return FALSE;
}

function api_message($msg = NULL) {
	return $_SERVER['REMOTE_ADDR']." - [".date('d/M/Y:H:i:s O', time()).'] "'.$_SERVER['REQUEST_METHOD'].' '.$_SERVER['REQUEST_URI'].' '.$_SERVER['SERVER_PROTOCOL'].'" "'.$_SERVER['HTTP_USER_AGENT'].'"'.(isset($msg) ? ' "'.$msg.'"' : '')."\r\n";
}

//Conn class----------------------------------------------------------------------------------

class Conn{
    public static $staticHandle, $staticResult, $lastQuery;
    
    static function query($sql="") {
        if (!isset(Conn::$staticHandle)) Conn::initiateStaticHandle();
        Conn::$lastQuery = $sql;
        
        Conn::$staticResult = @mysql_query($sql, Conn::$staticHandle) or Conn::dbError();
    }
    
    static function queryObjects($sql="") {
        if (!isset(Conn::$staticHandle)) Conn::initiateStaticHandle();
        Conn::$lastQuery = $sql;
        
        Conn::$staticResult = @mysql_query($sql, Conn::$staticHandle) or Conn::dbError();
        
        $aTemp = array();
        while($temp = mysql_fetch_object(Conn::$staticResult)){
            $aTemp[] = $temp;
        }
        return $aTemp;
    }
    
    static function queryObject($sql="") {
        if (!isset(Conn::$staticHandle)) Conn::initiateStaticHandle();
        Conn::$lastQuery = $sql;
        
        Conn::$staticResult = @mysql_query($sql, Conn::$staticHandle) or Conn::dbError();
        
        return $temp = mysql_fetch_object(Conn::$staticResult);
    }
    
    static function queryArrays($sql="", $type = MYSQL_ASSOC) {
        if (!isset(Conn::$staticHandle)) Conn::initiateStaticHandle();
        Conn::$lastQuery = $sql;
        
        Conn::$staticResult = @mysql_query($sql, Conn::$staticHandle) or Conn::dbError();
        
        $aTemp = array();
        while($temp = mysql_fetch_array(Conn::$staticResult, $type)){
            $aTemp[] = $temp;
        }
        return $aTemp;
    }
    
    static function queryArray($sql="", $type = MYSQL_ASSOC) {
        if (!isset(Conn::$staticHandle)) Conn::initiateStaticHandle();
        Conn::$lastQuery = $sql;
        
        Conn::$staticResult = @mysql_query($sql, Conn::$staticHandle) or Conn::dbError();
        
        return mysql_fetch_array(Conn::$staticResult, $type);
    }
    
    //convenience functions----------------------------------
    
    static function getCount(){
        return @mysql_num_rows(Conn::$staticResult) or 0;
    }
    
    static function reset(){
        @mysql_data_seek(Conn::$staticResult, 0);
    }
    
    //start and end handle------------------------------------
    
    static function initiateStaticHandle() {
        Conn::$staticHandle = @mysql_connect(MYSQLSERVER, USERNAME, PASSWORD) or Conn::dbError();
        //mysql_set_charset('utf8', Conn::$staticHandle); 
        @mysql_select_db(DATABASE, Conn::$staticHandle) or Conn::dbError();
    }
    
    static function closeStaticHandle() {
        if (isset(Conn::$staticResult)) @mysql_free_result(Conn::$staticResult);
        if (isset(Conn::$staticHandle)) @mysql_close(Conn::$staticHandle) or Conn::dbError();
    }
    
    //misc functions-----------------------------------------------
    
    static function dbError() {
        echo "<pre>MySQL Error: ".print_r(mysql_error(), true)."\n"."Last query:\n".Conn::$lastQuery."</pre>\n";
        die('Dead.');
    }
    
    static function getId(){
        return mysql_insert_id(Conn::$staticHandle);
    }
}

?>