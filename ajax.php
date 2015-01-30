<?php

require_once('includes/config.php');
require_once(APPROOT . '/includes/ieml_parser/DebugLog.class.php');
require_once(APPROOT . '/includes/functions.php');
require_once(APPROOT . '/includes/table_related/table_functions.php');
require_once(APPROOT . '/includes/common_functions.php');
require_once(APPROOT . '/includes/ieml_parser/IEMLParser.class.php');
require_once(APPROOT . '/includes/ieml_parser/IEMLScriptGen.class.php');
require_once(APPROOT . '/includes/visual_editor_functions.php');
require_once(APPROOT . '/includes/URLShortener.class.php');

//point debug output to nowhere
Devlog::output_stream(NULL);

function ensure_table_for_key($key) {
	Conn::query("DELETE FROM table_2d_id WHERE fkExpression = ".goodInt($key['id'])); //relations will auto delete dependent rows
	
	$key['table_info'] = IEML_gen_table_info($key['expression']);
	
	$key['concats'] = IEML_concat_complex_tables($key['table_info']);
	
	for ($i=0; $i<count($key['concats']); $i++) {
		for ($j=0; $j<count($key['concats'][$i]); $j++) {
			IEML_save_table($key['id'], $key['concats'][$i][$j], $i, $j);
		}
	}
	
	return $key;
}

function expression_sort_cmp($a, $b) {
	$a_eff_layer = isset($a['intLayer']) ? $a['intLayer'] : -1;
	$b_eff_layer = isset($b['intLayer']) ? $b['intLayer'] : -1;

	if ($a['intLayer'] == $b['intLayer']) {
		if ($a['intSetSize'] == $b['intSetSize']) {
			return IEMLParser::bareLexicoCompare($a['strFullBareString'], $b['strFullBareString']);
		} else {
			return $a['intSetSize'] - $b['intSetSize'];
		}
	} else {
		return $a_eff_layer - $b_eff_layer;
	}
}

function handle_request($action, $req) {
	$request_ret = NULL;
	
	switch ($action) {
		case 'relationalExpression':
			$asserts_ret = assert_arr(array('id'), $req);
			
			if (TRUE === $asserts_ret) {
				$goodID = goodInt($req['id']);
				$ret = Conn::queryArray("
					SELECT
						pkRelationalExpression AS rel_id, vchExpression AS expression,
						enumCompositionType, relexp.intLayer, relexp.enumDeleted,
						relexp.vchExample AS example, short_url.short_url AS shortUrl,
						(
							SELECT GROUP_CONCAT(fkLibrary SEPARATOR ',')
							FROM library_to_expression
							WHERE fkRelationalExpression = pkRelationalExpression
							GROUP BY fkRelationalExpression
						) AS fkLibrary
					FROM relational_expression relexp
					JOIN library_to_expression ltoep
						ON relexp.pkRelationalExpression = ltoep.fkRelationalExpression
					JOIN short_url ON relexp.fkShortUrl = short_url.id
					WHERE relexp.pkRelationalExpression = " . $goodID);

				$ret['fkLibrary'] = explode(',', $ret['fkLibrary']);

				$children_query = Conn::queryArrays("
					SELECT fkRelationalExpression, fkExpressionPrimary
					FROM relational_expression_tree
					WHERE fkParentRelation = " . $goodID . "
					ORDER BY intOrder
				");
				$ret['children'] = array();

				for ($i = 0; $i < count($children_query); $i++) {
					$child = NULL;

					if ($children_query[$i]['fkRelationalExpression']) {
						$child = Conn::queryArray("
							SELECT vchExpression as expression, vchExample as example
							FROM relational_expression
							WHERE pkRelationalExpression = " . $children_query[$i]['fkRelationalExpression'] . "
						");
					} else {
						$child = Conn::queryArray("
							SELECT strExpression as expression, sublang.strExample AS example
							FROM expression_primary prim
							LEFT JOIN expression_data sublang
								ON sublang.fkExpressionPrimary = prim.pkExpressionPrimary
							WHERE pkExpressionPrimary = " . $children_query[$i]['fkExpressionPrimary'] . "
						");
					}

					$ret['children'][] = $child;
				}

				$request_ret = $ret;
			} else {
				$request_ret = assert_format($asserts_ret);
			}
			break;

		case 'expression':
			$asserts_ret = assert_arr(array('lang'), $req);
			
			if (TRUE === $asserts_ret) {
				$lang = strtolower($req['lang']);
				
				if (isset($req['id']) || isset($req['exp'])) {
					$goodID = goodInt($req['id']);

					if (isset($req['id'])) {
						$ret = Conn::queryArray("
							SELECT
								prim.pkExpressionPrimary as id, prim.strExpression as expression,
								prim.intSetSize, prim.intLayer, prim.strFullBareString,
								prim.enumClass, prim.enumCategory, sublang.strExample AS example,
								strDescriptor AS descriptor,
								t_key.enumShowEmpties, t_key.enumCompConc, t_key.strEtymSwitch
							FROM expression_primary prim
							LEFT JOIN expression_data sublang
								ON sublang.fkExpressionPrimary = prim.pkExpressionPrimary
							LEFT JOIN table_2d_ref t2dref ON prim.pkExpressionPrimary = t2dref.fkExpressionPrimary
							LEFT JOIN table_2d_id t2did ON t2dref.fkTable2D = t2did.pkTable2D
							LEFT JOIN expression_primary t_key ON t2did.fkExpression = t_key.pkExpressionPrimary
							WHERE strLanguageISO6391 = '".goodString($lang)."'
							AND   prim.pkExpressionPrimary = ".$goodID);
					} else if (isset($req['exp'])) {
						$ret = Conn::queryArray("
							SELECT
								prim.pkExpressionPrimary as id, prim.strExpression as expression,
								prim.intSetSize, prim.intLayer, prim.strFullBareString,
								prim.enumClass, prim.enumCategory, sublang.strExample AS example,
								strDescriptor AS descriptor,
								t_key.enumShowEmpties, t_key.enumCompConc, t_key.strEtymSwitch
							FROM expression_primary prim
							LEFT JOIN expression_data sublang
								ON sublang.fkExpressionPrimary = prim.pkExpressionPrimary
							LEFT JOIN table_2d_ref t2dref ON prim.pkExpressionPrimary = t2dref.fkExpressionPrimary
							LEFT JOIN table_2d_id t2did ON t2dref.fkTable2D = t2did.pkTable2D
							LEFT JOIN expression_primary t_key ON t2did.fkExpression = t_key.pkExpressionPrimary
							WHERE strLanguageISO6391 = '".goodString($lang)."'
							AND   prim.strExpression = '".goodString($req['exp'])."'
							AND   prim.enumDeleted = 'N'");
					}
					
					//if the query above failed because no example exists for current expression
					if (!$ret) {
						$ret = Conn::queryArray("
							SELECT
								prim.pkExpressionPrimary as id, prim.strExpression as expression,
								prim.enumCategory, prim.enumShowEmpties, prim.enumCompConc, prim.strEtymSwitch
							FROM expression_primary prim
							WHERE pkExpressionPrimary = ".$goodID);
					}
					
					$ret = getTableForElement($ret, $ret['id'], $req);
					
					$request_ret = $ret;
				} else {
					$request_ret = array('result' => 'error', 'error' => "Neither 'id' nor 'exp' are set.");
				}
			} else {
				$request_ret = assert_format($asserts_ret);
			}
			
			break;
			
		case 'searchDictionary':
			$asserts_ret = assert_arr(array('search', 'lang', 'library'), $req);
			
			if (TRUE === $asserts_ret) {
				require_once(__DIR__ . '/ajax/searchDictionary.php');
			} else {
				$request_ret = assert_format($asserts_ret);
			}
			break;
			
		case 'deleteDictionary':
			if (isset($req['id'])) {
				Conn::query("UPDATE expression_primary SET enumDeleted = 'Y' WHERE pkExpressionPrimary = ".goodInt($req['id']));
				
				$request_ret = array('result' => 'success');
			} else if (isset($req['exp'])) {
				Conn::query("UPDATE expression_primary SET enumDeleted = 'Y' WHERE strExpression = '".goodString($req['exp'])."'");
				
				$request_ret = array('result' => 'success');
			} else {
				$request_ret = array('result' => 'error', 'error' => "Neither 'id' nor 'exp' are set.");
			}
			
			break;
			
		case 'deleteVisualExpression':
			$asserts_ret = assert_arr(array('rel_id'), $req);

			if (TRUE === $asserts_ret) {
				Conn::query("UPDATE relational_expression SET enumDeleted = 'Y' WHERE pkRelationalExpression = ".goodInt($req['rel_id']));
				
				$request_ret = array('result' => 'success');
			} else {
				$request_ret = assert_format($asserts_ret);
			}
			
			break;
			
		case 'editDictionary':
			$asserts_ret = assert_arr(array('enumCategory', 'exp', 'example', 'descriptor', 'lang', 'id', 'enumShowEmpties', 'iemlEnumComplConcOff', 'iemlEnumSubstanceOff'), $req);
			
			if (TRUE === $asserts_ret) {
				require_once(__DIR__ . '/ajax/editDictionary.php');
			} else {
				$request_ret = assert_format($asserts_ret);
			}
			
			break;
			
		case 'newDictionary':
			$asserts_ret = assert_arr(array('exp', 'lang', 'library', 'enumCategory', 'example', 'descriptor', 'enumShowEmpties'), $req);
			
			if (TRUE === $asserts_ret) {
				require_once(__DIR__ . '/ajax/newDictionary.php');
			} else {
				$request_ret = assert_format($asserts_ret);
			}
			
			break;
			
		case 'newVisualExpression':
			$asserts_ret = assert_arr(array('editor_array', 'lang', 'library', 'example'), $req);
			
			if (TRUE === $asserts_ret) {
				//NOTE: this terrible language construct is used because if complex program control in the file
				$request_ret = require(__DIR__ . '/ajax/newVisualExpression.php');
			} else {
				$request_ret = assert_format($asserts_ret);
			}
			
			break;
			
		case 'editVisualExpression':
			$asserts_ret = assert_arr(array('editor_array', 'lang', 'library', 'example'), $req);
			
			if (TRUE === $asserts_ret) {
				//NOTE: this terrible language construct is used because if complex program control in the file
				$request_ret = require(__DIR__ . '/ajax/editVisualExpression.php');
			} else {
				$request_ret = assert_format($asserts_ret);
			}
			
			break;
			
		case 'setTableEl':
			$asserts_ret = assert_arr(array('id'), $req);
			
			if (TRUE === $asserts_ret) {
				Conn::query("UPDATE table_2d_ref SET enumEnabled = '".goodString($req['enumEnabled'])."' WHERE pkTable2DRef = ".goodInt($req['id']));
				
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
						('".goodString($req['username'])."', '".goodString(bcrypt_hash($req['pass']))."', '".goodString($req['enumType'])."', ".$now.")");
				
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
						strEmail = '".goodString($req['strEmail'])."',
						enumType = '".goodString($req['enumType'])."'
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
					Conn::query("UPDATE users SET enumDeleted = '".goodString($req['enumDeleted'])."' WHERE pkUser = ".goodInt($req['pkUser']));
				}
				
				$request_ret = array('result' => 'success');
			} else {
				$request_ret = assert_format($asserts_ret);
			}
			
			break;
			
		case 'viewUsers':
			$asserts_ret = assert_arr(array('deleted'), $req);
			
			if (TRUE !== $asserts_ret) {
				$request_ret = assert_format($asserts_ret);
				break;
			}

			if (!isset($_SESSION['user'])) {
				$request_ret = array(
					'result' => 'error',
					'error' => 'Must be logged in to view users.'
				);
				break;
			}

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

		case 'getUserLibraries':
			$ret = NULL;
			if ($_SESSION['user']) {
				$ret = Conn::queryArrays("
					SELECT
						pkLibrary, fkUser, strName
					FROM library
					WHERE fkUser = ".goodInt($_SESSION['user']['pkUser'])."
				");
			} else {
				$ret = array(
					'result' => 'error',
					'resultCode' => 1,
					'error' => 'User must be logged in to use this route.'
				);
			}

			$request_ret = $ret;

			break;

		case 'getAllLibraries':
			$request_ret = Conn::queryArrays("
				SELECT
					pkLibrary, fkUser, strName
				FROM library
				LEFT JOIN users ON library.fkUser = users.pkUser
				WHERE
					users.enumDeleted = 'no'
					OR library.fkUser IS NULL
			");

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

		case 'validateExpression':
			require_once(APPROOT.'/includes/ieml_parser/IEMLParser.class.php');
			require_once(APPROOT.'/includes/ieml_parser/IEMLScriptGen.class.php');

			$asserts_ret = assert_arr(array('expression'), $req);
			
			if (TRUE === $asserts_ret) {
				$request_ret = array(
					'result' => 'success',
					'resultCode' => 0,
					'parser_output' => IEMLParser::AST_or_FAIL($req['expression'])
				);
			} else {
				$request_ret = assert_format($asserts_ret);
			}

			break;
		
		case 'addExpressionToLibrary':
			$asserts_ret = assert_arr(array('library'), $req);
			
			if (TRUE === $asserts_ret) {
				if (!isset($req['id']) && !isset($req['rel_id'])) {
					$request_ret = array(
						'result' => 'error',
						'resultCode' => 1,
						'error' => "Neither 'id' nor 'rel_id' were specified."
					);
				}

				if (isset($req['id'])) {
					Conn::query("
						INSERT INTO library_to_expression
							(fkLibrary, fkExpressionPrimary)
						VALUES
							(" . goodInt($req['library']) . ", " . goodInt($req['id']) . ")
					");
				} else {
					Conn::query("
						INSERT INTO library_to_expression
							(fkLibrary, fkRelationalExpression)
						VALUES
							(" . goodInt($req['library']) . ", " . goodInt($req['rel_id']) . ")
					");
				}
				
				$request_ret = array(
					'result' => 'success',
					'resultCode' => 0
				);
			} else {
				$request_ret = assert_format($asserts_ret);
			}
			break;

		default:
			$request_ret = array('result' => 'error', 'error' => 'No such identifier: ' . $action);
			
			break;
	}
	
	return $request_ret;
}

function request_error_handle($severity, $message, $filename, $lineno) {
	if ($severity == E_USER_ERROR) {
		throw new Exception('Fatal Error '.$severity.' in "'.$filename.'":'.$lineno.' "'.$message.'"');
	} else {
		//ignore; you didn't see anything...
	}
}

function wrap_request($action, $req, $callback = NULL) {
	api_log(api_message('{Action: '.$_REQUEST['a'].'}'));

	ob_start();

	set_error_handler('request_error_handle');

	try {
		$ret = handle_request($action, $req);
	} catch (Exception $e) {
		$ret = array(
			'result' => 'error',
			'resultCode' => '1',
			'error' => $e->getMessage()
		);
	}

	restore_error_handler();

	if ($callback) {
		echo $callback.'(';
	}

	echo json_encode($ret);

	if ($callback) {
		echo ')';
	}

	return ob_get_clean();
}

header('Content-type: application/json; charset=utf-8;');

session_start();

echo wrap_request($_REQUEST['a'], $_REQUEST, array_key_exists('callback', $_REQUEST) ? $_REQUEST['callback'] : NULL);

Conn::closeStaticHandle();

?>
