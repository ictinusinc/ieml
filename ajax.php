<?php

error_reporting(E_ERROR);

require_once('includes/config.php');
require_once(APPROOT.'/includes/functions.php');
require_once(APPROOT.'/includes/table_related/table_functions.php');
require_once(APPROOT.'/includes/common_functions.php');

function ensure_table_for_key($key, $IEML_lowToVowelReg) {
    Conn::query("DELETE FROM table_2d_id WHERE fkExpression = ".goodInt($key['id'])); //relations will auto delete dependent rows
    
    $key['table_info'] = IEML_gen_table_info($key['expression'], $IEML_lowToVowelReg);
    
	$key['concats'] = IEML_concat_complex_tables($info);
	for ($i=0; $i<count($key['concats']); $i++) {
		for ($j=0; $j<count($key['concats'][$i]); $i++) {
    		IEML_save_table($key['id'], $key['concats'][$i][$j], $i, $j);
    	}
    }
    
    return $key;
}

function handle_request($action, $req) {
	$request_ret = NULL;
	
	switch ($action) {
		case 'expression':
	    	$asserts_ret = assert_arr(array('lang'), $req);
	    	
		    if (TRUE === $asserts_ret) {
		    	$lang = strtolower($req['lang']);
		    	
		    	if (isset($req['id']) || isset($req['exp'])) {
					if (isset($req['id'])) {
					    $goodID = goodInt($req['id']);
					    $ret = Conn::queryArray("
					        SELECT
					            pkExpressionPrimary as id, strExpression as expression,
					            enumCategory, sublang.strDescriptor AS descriptor
					        FROM expression_primary prim
					        LEFT JOIN expression_descriptors sublang
					        	ON sublang.fkExpressionPrimary = prim.pkExpressionPrimary
					        WHERE strLanguageISO6391 = ".goodInput($lang)."
					        AND   pkExpressionPrimary = ".$goodID);
				    } else if (isset($req['exp'])) {
					    $ret = Conn::queryArray("
					        SELECT
					            pkExpressionPrimary as id, strExpression as expression,
					            enumCategory, sublang.strDescriptor AS descriptor
					        FROM expression_primary prim
					        LEFT JOIN expression_descriptors sublang
					        	ON sublang.fkExpressionPrimary = prim.pkExpressionPrimary
					        WHERE strLanguageISO6391 = ".goodInput($lang)."
					        AND   prim.strExpression = '".goodString($req['exp'])."'
					        AND   prim.enumDeleted = 'N'");
				    }
				    
				    //if the query above failed because no descriptor exists for current expression
				    if (!$ret) {
					    $ret = Conn::queryArray("
					        SELECT
					            pkExpressionPrimary as id, strExpression as expression, enumCategory
					        FROM expression_primary prim
					        WHERE pkExpressionPrimary = ".$goodID);
				    }
				    
				    if (!isset($req['disableTableGen']) || $req['disableTableGen'] != 'true') {
				        $ret = getTableForElement($ret, $ret['id'], $req);
				    }
				    
			    	$request_ret = $ret;
			    } else {
				    $request_ret = array('result' => 'error', 'error' => "Neither 'id' nor 'exp' are set.");
			    }
		    } else {
			    $request_ret = assert_format($asserts_ret);
		    }
		    
			break;
			
	    case 'searchDictionary':
	    	$asserts_ret = assert_arr(array('search', 'lang'), $req);
	    	
		    if (TRUE === $asserts_ret) {
			    $ret = Conn::queryArrays("
			        SELECT
			            pkExpressionPrimary AS id, strExpression AS expression,
			            enumCategory, enumDeleted, sublang.strDescriptor AS descriptor
			        FROM expression_primary prim
			        LEFT JOIN expression_descriptors sublang
			        	ON sublang.fkExpressionPrimary = prim.pkExpressionPrimary
			        WHERE prim.enumDeleted = 'N'
			        AND   strLanguageISO6391 = ".goodInput($req['lang'])."
			        ".(strlen($req['search']) > 0 ? "AND   (strExpression LIKE '%".goodString($req['search'])."%' OR sublang.strDescriptor LIKE '%".goodString($req['search'])."%')" : '')."
			        ORDER BY expression");
			    
			    $request_ret = $ret;
		    } else {
			    $request_ret = assert_format($asserts_ret);
		    }
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
	    	$asserts_ret = assert_arr(array('enumCategory', 'exp', 'descriptor', 'lang', 'id', 'enumShowEmpties', 'iemlEnumComplConcOff', 'iemlEnumSubstanceOff', 'iemlEnumAttributeOff', 'iemlEnumModeOff', 'tables'), $req);
	    	
		    if (TRUE === $asserts_ret) {
		    	$lang = strtolower($req['lang']);
		        $oldState = Conn::queryArray("
			        SELECT
			        	pkExpressionPrimary AS id, strExpression as expression, enumCategory
			        FROM expression_primary
			        WHERE pkExpressionPrimary = ".goodInt($req['id']));
		        $oldDescriptor = Conn::queryArrays("
		            SELECT pkExpressionDescriptors
		            FROM expression_descriptors
		            WHERE strLanguageISO6391 = '".goodString($lang)."'
		            AND fkExpressionPrimary = ".goodInt($req['id']));
		        
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
		        
		        if (count($oldDescriptor) > 0) {
			        Conn::query("
			            UPDATE expression_descriptors
			            SET
			                strDescriptor = ".goodInput($req['descriptor'])."
			            WHERE fkExpressionPrimary = ".goodInt($req['id'])."
			            AND   strLanguageISO6391 = ".goodInput($lang)." LIMIT 1");
		        } else {
			        Conn::query("
			            INSERT INTO expression_descriptors
			            	(fkExpressionPrimary, strDescriptor, strLanguageISO6391)
			            VALUES
			            	(".goodInt($req['id']).", ".goodInput($req['descriptor']).", ".goodInput($lang).")");
		        }
	        
		        if ($ret['enumCategory'] == 'Y' && $oldState['enumCategory'] == 'N') {
		        	ensure_table_for_key($ret, $IEML_lowToVowelReg);
		        }
		        
		        $ret = getTableForElement($ret, goodInt($ret['id']), $req);
		        
		        $request_ret = $ret;
	        } else {
			    $request_ret = assert_format($asserts_ret);
	        }
	        
	        break;
	        
	    case 'newDictionary':
	    	$asserts_ret = assert_arr(array('exp', 'lang', 'enumCategory', 'descriptor', 'enumShowEmpties', 'id'), $req);
	    	
		    if (TRUE === $asserts_ret) {
		    	$lang = strtolower($req['lang']);
		    	
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
		                (".$ret['id'].", ".goodInput($req['descriptor']).", ".goodInput($lang).")");
		        
		        if ($req['enumCategory'] == 'Y') {
		        	ensure_table_for_key($ret, $IEML_lowToVowelReg);
		        }
		        
		        $ret = getTableForElement($ret, goodInt($ret['id']), $req);
		        
		        $request_ret = $ret;
	        } else {
		        $request_ret = assert_format($asserts_ret);
	        }
	        
	        break;
	        
	    case 'setTableEl':
	    	$asserts_ret = assert_arr(array('id'), $req);
	    	
		    if (TRUE === $asserts_ret) {
		        Conn::query("UPDATE table_2d_ref SET enumEnabled = ".goodInput($req['enumEnabled'])." WHERE pkTable2DRef = ".goodInt($req['id']));
		        
		    	$request_ret = array('result' => 'success');
	    	} else {
		    	$request_ret = assert_format($asserts_ret);
	    	}
	        
	        break;
	    
	    case 'addUser':
	    	$asserts_ret = assert_arr(array('username', 'pass', 'enumType'), $req);
	    	
		    if (TRUE === $asserts_ret) {
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
	        } else {
		        $request_ret = assert_format($asserts_ret);
	        }
	        
	        break;
	        
	    case 'editUser':
	    	$asserts_ret = assert_arr(array('pkUser', 'strEmail', 'enumType'), $req);
	    	
		    if (TRUE === $asserts_ret) {
		        Conn::query("
		            UPDATE users
		            SET
		                strEmail = ".goodInput($req['strEmail']).",
		                enumType = ".goodInput($req['enumType'])."
		            WHERE pkUser = ".goodInt($req['pkUser'])."
		            LIMIT 1");
		        
		        $newUser = Conn::queryArrays("SELECT pkUser, strEmail, tsDateCreated, UNIX_TIMESTAMP(tsLastUpdate), enumType, enumDeleted FROM users WHERE pkUser = ".goodInt($req['pkUser'])." LIMIT 1");
		        
		        $request_ret = $newUser;
	        } else {
		        $request_ret = assert_format($asserts_ret);
	        }
	        
	        break;
	    
	    case 'delUser':
	    	$asserts_ret = assert_arr(array('pkUser'), $req);
	    	
		    if (TRUE === $asserts_ret) {
		        if (!isset($req['enumDeleted'])) {
		            Conn::query("UPDATE users SET enumDeleted = 'yes' WHERE pkUser = ".goodInt($req['pkUser']));
		        } else {
		            Conn::query("UPDATE users SET enumDeleted = ".goodInput($req['enumDeleted'])." WHERE pkUser = ".goodInt($req['pkUser']));
		        }
		        
		    	$request_ret = array('result' => 'success');
	    	} else {
		    	$request_ret = assert_format($asserts_ret);
	    	}
	        
	        break;
	        
	    case 'viewUsers':
	    	$asserts_ret = assert_arr(array('deleted'), $req);
	    	
		    if (TRUE === $asserts_ret) {
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
	        } else {
		    	$request_ret = assert_format($asserts_ret);
	        }
	        
	        break;
	    
	    case 'login':
	    	$asserts_ret = assert_arr(array('loginEmail', 'loginPassword'), $req);
	    	
		    if (TRUE === $asserts_ret) {
		    	$attempt_login = api_login($req['loginEmail'], $req['loginPassword']);
		    	if (isset($attempt_login)) {
			    	$_SESSION['user'] = $attempt_login;
			    	$request_ret = $attempt_login;
		    	} else {
		    		$request_ret = array('result' => 'error', 'error' => 'Invalid username or password.');
		    	}
	    	} else {
		    	$request_ret = assert_format($asserts_ret);
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

api_log(api_message('{Action: '.$_REQUEST['a'].'}'));

echo (isset($_REQUEST['callback']) ? $_REQUEST['callback'].'(' : '').json_encode(handle_request($_REQUEST['a'], $_REQUEST)).(isset($_REQUEST['callback']) ? ')' : '');

//set_session_data($_SESSION['auth_token'], $_SESSION);

Conn::closeStaticHandle();

?>
