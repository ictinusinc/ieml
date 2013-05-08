<?php


include_once('includes/config.php');
include_once(APPROOT.'/includes/functions.php');
include_once(APPROOT.'/includes/table_related/table_functions.php');
include_once(APPROOT.'/includes/common_functions.php');

function ensure_table_for_key($key, $IEML_lowToVowelReg) {
    Conn::query("DELETE FROM table_2d_id WHERE fkExpression = ".goodInt($key['id'])); //relations will auto delete dependent rows
    
    $key['table_info'] = IEML_gen_table_info($key['expression'], $IEML_lowToVowelReg);
    
    $key['table_info']['table_flat'] = array_values(array_filter($key['table_info']['table_flat'], function($el) { return !is_numeric($el); }));
    
    IEML_save_table($key['id'], $key['table_info']);
    
    return $key;
}

function handle_request($action, $req) {
	$request_ret = NULL;
	
	switch ($action) {
		case 'expression':
			if (isset($req['id'])) {
			    $goodID = goodInt($req['id']);
			    $ret = Conn::queryArray("
			        SELECT
			            pkExpressionPrimary as id, strExpression as expression,
			            enumCategory, sublang.strDescriptor AS descriptor
			        FROM expression_primary prim
			        LEFT JOIN expression_descriptors sublang
			        	ON sublang.fkExpressionPrimary = prim.pkExpressionPrimary
			        WHERE strLanguageISO6391 = ".goodInput($req['lang'])."
			        AND   pkExpressionPrimary = ".$goodID);
			    
			    if (!isset($req['disableTableGen']) || $req['disableTableGen'] != 'true') {
			        $ret = getTableForElement($ret, $ret['id'], $req);
			    }
			    
			    $request_ret = $ret;
		    } else if (isset($req['exp'])) {
			    $ret = Conn::queryArray("
			        SELECT
			            pkExpressionPrimary as id, strExpression as expression,
			            enumCategory, sublang.strDescriptor AS descriptor
			        FROM expression_primary prim
			        LEFT JOIN expression_descriptors sublang
			        	ON sublang.fkExpressionPrimary = prim.pkExpressionPrimary
			        WHERE strLanguageISO6391 = ".goodInput($req['lang'])."
			        AND   prim.strExpression = '".goodString($req['exp'])."'");
			    
			    if (!isset($req['disableTableGen']) || $req['disableTableGen'] != 'true') {
			        $ret = getTableForElement($ret, $ret['id'], $req);
			    }
			    
		    	$request_ret = $ret;
		    } else {
			    $request_ret = array('result' => 'error', 'error' => "Neither 'id' nor 'exp' are set.");
		    }
		    
			break;
			
	    case 'searchDictionary':
		    api_assert(array('search', 'lang'), $req);
		    
		    $ret = Conn::queryArrays("
		        SELECT
		            pkExpressionPrimary AS id, strExpression AS expression,
		            enumCategory, enumDeleted, sublang.strDescriptor AS descriptor
		        FROM expression_primary prim
		        LEFT JOIN expression_descriptors sublang
		        	ON sublang.fkExpressionPrimary = prim.pkExpressionPrimary
		        WHERE enumDeleted = 'N'
		        AND   strLanguageISO6391 = ".goodInput($req['lang'])."
		        ".(strlen($req['search']) > 0 ? "AND   (strExpression LIKE '%".goodString($req['search'])."%' OR sublang.strDescriptor LIKE '%".goodString($req['search'])."%')" : '')."
		        ORDER BY expression");
		    
		    $request_ret = $ret;
			break;
	        
	    case 'deleteDictionary':
	        if (isset($req['id'])) {
	            Conn::query("UPDATE expression_primary SET enumDeleted = 'Y' WHERE pkExpressionPrimary = ".goodInt($req['id']));
		        
		    	$request_ret = array('result' => 'success');
	        } else if (isset($req['exp'])) {
	            Conn::query("UPDATE expression_primary SET enumDeleted = 'Y' WHERE strExpression = ".goodInput($req['exp']));
		        
		    	$request_ret = array('result' => 'success');
	        } else {
	    		$request_ret = array('result' => 'error', 'error' => "Neither 'id' nor 'exp' are set.");
	        }
	        
	        break;
	        
	    case 'editDictionary':
	        if (!isset($req['exp']) || !isset($req['id'])) die('Bad data.');
	        
	        $oldState = Conn::queryArray("SELECT pkExpressionPrimary AS id, strExpression as expression, enumCategory FROM expression_primary WHERE pkExpressionPrimary = ".goodInt($req['id']));
	        
	        Conn::query("
	            UPDATE expression_primary
	            SET
	                enumCategory = ".goodInput($req['enumCategory']).",
	                strExpression = ".goodInput($req['exp'])."
	            WHERE pkExpressionPrimary = ".goodInt($req['id']));
	        
	        Conn::query("
	        	UPDATE table_2d_id
	        	SET
	        		enumShowEmpties = ".goodInput($req['enumShowEmpties']).",
	        		enumCompConc = '".invert_bool($req['iemlEnumComplConcOff'], 'Y', 'N')."',
	        		strEtymSwitch = '".invert_bool($req['iemlEnumSubstanceOff'], 'Y', 'N')
	        						.invert_bool($req['iemlEnumAttributeOff'], 'Y', 'N')
	        						.invert_bool($req['iemlEnumModeOff'], 'Y', 'N')."'
	        	WHERE pkTable2D = ".goodInt($req['pkTable2D']));
	            
	        $ret = array(
	        	'id' => $req['id'],
	        	'expression' => $req['exp'],
	        	'enumCategory' => $req['enumCategory'],
	        	'descriptor' => $req['descriptor']
	        );
	        
	        
	        Conn::query("
	            UPDATE expression_descriptors
	            SET
	                strDescriptor = ".goodInput($req['descriptor'])."
	            WHERE fkExpressionPrimary = ".goodInt($req['id'])."
	            AND   strLanguageISO6391 = ".goodInput($req['lang'])." LIMIT 1");
	        
	        if ($ret['enumCategory'] == 'Y' && $oldState['enumCategory'] == 'N') {
	        	ensure_table_for_key($ret, $IEML_lowToVowelReg);
	        }
	        
	        $ret = getTableForElement($ret, goodInt($ret['id']), $req);
	        
	        $request_ret = $ret;
	        
	        break;
	        
	    case 'newDictionary': 
	        api_assert(array('exp'), $req);
	            
	        Conn::query("
	            INSERT INTO expression_primary
	                (strExpression, enumCategory)
	            VALUES
	                (".goodInput($req['exp']).", ".goodInput($req['enumCategory']).")");
	                
	        $ret = array(
	        	'id' => Conn::getId(),
	        	'expression' => $req['exp'],
	        	'enumCategory' => $req['enumCategory'],
	        	'descriptor' => $req['descriptor'],
	        	'enumShowEmpties' => $req['enumShowEmpties']
	        );
	        
	        Conn::query("
	            INSERT INTO expression_descriptors
	                (fkExpressionPrimary, strDescriptor, strLanguageISO6391)
	            VALUES
	                (".$ret['id'].", ".goodInput($req['descriptor']).", ".goodInput($req['lang']).")");
	        
	        if ($req['enumCategory'] == 'Y') {
	        	ensure_table_for_key($ret, $IEML_lowToVowelReg);
	        }
	        
	        $ret = getTableForElement($ret, goodInt($ret['id']), $req);
	        
	        $request_ret = $ret;
	        
	        break;
	        
	    case 'setTableEl':
	        api_assert(array('id'), $req);
	        
	        Conn::query("UPDATE table_2d_ref SET enumEnabled = ".goodInput($req['enumEnabled'])." WHERE pkTable2DRef = ".goodInt($req['id']));
	        
	    	$request_ret = array('result' => 'success');
	        
	        break;
	    
	    case 'addUser':
	        api_assert(array('username', 'pass', 'enumType'), $req);
	        
	        $now = time();
	        
	        Conn::query("
	            INSERT INTO
	                users (strEmail, strPassHash, enumType, tsDateCreated)
	            VALUES
	                (".goodInput($req['username']).", ".goodInput(bcrypt_hash($req['pass'])).", ".goodInput($req['enumType']).", ".$now.")");
	        
	        $newUser = array(
	            'pkUser' => Conn::getId(),
	            'strEmail' => $req['username'],
	            'tsDateCreated' => $now,
	            'tsLastUpdate' => $now,
	            'enumType' => 'user',
	            'enumDeleted' => 'no'
	        );
	        
	        $request_ret = $newUser;
	        
	        break;
	        
	    case 'editUser':
	        api_assert(array('pkUser', 'strEmail', 'enumType'), $req);
	        
	        Conn::query("
	            UPDATE users
	            SET
	                strEmail = ".goodInput($req['strEmail']).",
	                enumType = ".goodInput($req['enumType'])."
	            WHERE pkUser = ".goodInt($req['pkUser'])."
	            LIMIT 1");
	        
	        $newUser = Conn::queryArrays("SELECT pkUser, strEmail, tsDateCreated, UNIX_TIMESTAMP(tsLastUpdate), enumType, enumDeleted FROM users WHERE pkUser = ".goodInt($req['pkUser'])." LIMIT 1");
	        
	        $request_ret = $newUser;
	        
	        break;
	    
	    case 'delUser':
	        api_assert(array('pkUser'), $req);
	        
	        if (!isset($req['enumDeleted'])) {
	            Conn::query("UPDATE users SET enumDeleted = 'yes' WHERE pkUser = ".goodInt($req['pkUser']));
	        } else {
	            Conn::query("UPDATE users SET enumDeleted = ".goodInput($req['enumDeleted'])." WHERE pkUser = ".goodInt($req['pkUser']));
	        }
	        
	    	$request_ret = array('result' => 'success');
	        
	        break;
	        
	    case 'viewUsers':
	        $res = NULL;
	        
	        if ($req['deleted'] == 'yes') {
	            $res = Conn::queryArrays("SELECT pkUser, strEmail, tsDateCreated, UNIX_TIMESTAMP(tsLastUpdate) AS tsLastUpdate, enumType, enumDeleted FROM users WHERE enumDeleted = 'yes'");
	        } else {
	            $res = Conn::queryArrays("SELECT pkUser, strEmail, tsDateCreated, UNIX_TIMESTAMP(tsLastUpdate) AS tsLastUpdate, enumType, enumDeleted FROM users WHERE enumDeleted = 'no'");
	        }
	        for ($i=0; $i<count($res); $i++) {
	            $res[$i]['tsDateCreated'] = goodInt($res[$i]['tsDateCreated']);
	            $res[$i]['tsLastUpdate'] = goodInt($res[$i]['tsLastUpdate']);
	            $res[$i]['pkUser'] = goodInt($res[$i]['pkUser']);
	        }
	        
	        $request_ret = $res;
	        
	        break;
	    
	    case 'login':
	        api_assert(array('loginEmail', 'loginPassword'), $req);
	        
	    	$attempt_login = api_login($req['loginEmail'], $req['loginPassword']);
	    	if (isset($attempt_login)) {
		    	$_SESSION['user'] = $attempt_login;
		    	$request_ret = $attempt_login;
	    	} else {
	    		$request_ret = array('result' => 'error', 'error' => 'Invalid username or password.');
	    	}
	    	
	    	
	    	break;
	    
	    case 'logout':
	    	session_destroy();
	    	
	    	$request_ret = array('result' => 'success');
	    	
	    	break;
	        
	    default:
		    $request_ret = array('result' => 'error', 'error' => 'No such identifier: '.$ajax);
		    
		    break;
	}
	
	return $request_ret;
}

header('Content-type: application/json');

//smart_session($_REQUEST);
session_start();

api_log(api_message('{Action: '.$ajax.'}'));

echo (isset($_REQUEST['callback']) ? $_REQUEST['callback'].'(' : '').json_encode(handle_request($_REQUEST['a'], $_REQUEST)).(isset($_REQUEST['callback']) ? ')' : '');

//set_session_data($_SESSION['auth_token'], $_SESSION);

Conn::closeStaticHandle();

?>
