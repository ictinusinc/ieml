<?php

include_once(APPROOT.'/includes/table_related/exp_parse.php');

//NOTE: use PHP's 'x' modifier when matching regex to ignore whitespace
$IEML_lowToVowelReg = array(
	'/(?<![EAUSBTIFOM]\:)U\:U\:(?:E\:)?\./' => 'wo.', '/(?<![EAUSBTIFOM]\:)U\:A\:(?:E\:)?\./' => 'wa.',
	'/(?<![EAUSBTIFOM]\:)U\:S\:(?:E\:)?\./' => 'y.', '/(?<![EAUSBTIFOM]\:)U\:B\:(?:E\:)?\./' => 'o.',
	'/(?<![EAUSBTIFOM]\:)U\:T\:(?:E\:)?\./' => 'e.',
	'/(?<![EAUSBTIFOM]\:)A\:U\:(?:E\:)?\./' => 'wu.', '/(?<![EAUSBTIFOM]\:)A\:A\:(?:E\:)?\./' => 'we.',
	'/(?<![EAUSBTIFOM]\:)A\:S\:(?:E\:)?\./' => 'u.', '/(?<![EAUSBTIFOM]\:)A\:B\:(?:E\:)?\./' => 'a.',
	'/(?<![EAUSBTIFOM]\:)A\:T\:(?:E\:)?\./' => 'i.',
	'/(?<![EAUSBTIFOM]\:)S\:U\:(?:E\:)?\./' => 'j.', '/(?<![EAUSBTIFOM]\:)S\:A\:(?:E\:)?\./' => 'g.',
	'/(?<![EAUSBTIFOM]\:)S\:S\:(?:E\:)?\./' => 's.', '/(?<![EAUSBTIFOM]\:)S\:B\:(?:E\:)?\./' => 'b.',
	'/(?<![EAUSBTIFOM]\:)S\:T\:(?:E\:)?\./' => 't.',
	'/(?<![EAUSBTIFOM]\:)B\:U\:(?:E\:)?\./' => 'h.', '/(?<![EAUSBTIFOM]\:)B\:A\:(?:E\:)?\./' => 'c.',
	'/(?<![EAUSBTIFOM]\:)B\:S\:(?:E\:)?\./' => 'k.', '/(?<![EAUSBTIFOM]\:)B\:B\:(?:E\:)?\./' => 'm.',
	'/(?<![EAUSBTIFOM]\:)B\:T\:(?:E\:)?\./' => 'n.',
	'/(?<![EAUSBTIFOM]\:)T\:U\:(?:E\:)?\./' => 'p.', '/(?<![EAUSBTIFOM]\:)T\:A\:(?:E\:)?\./' => 'x.',
	'/(?<![EAUSBTIFOM]\:)T\:S\:(?:E\:)?\./' => 'd.', '/(?<![EAUSBTIFOM]\:)T\:B\:(?:E\:)?\./' => 'f.',
	'/(?<![EAUSBTIFOM]\:)T\:T\:(?:E\:)?\./' => 'l.'
);

function substr_ab($str, $a, $b) {
	return substr($str, $a, $b - $a);
}

function array_append(&$arr, $b) {
	foreach ($b as $el) {
		$arr[] = $el;
	}
	
	return $arr;
}

function array_merge_dest(&$arr, $b) {
	foreach ($b as $key => $el) {
		$arr[$key] = $el;
	}
	
	return $arr;
}

function str_splice($str, $offset, $len = 0, $rep = NULL) {
	return substr($str, 0, $offset).($rep == NULL ? '' : $rep).($len == 0 ? '' : substr($str, $offset + $len));
}

function array_to_string($arr) {
	$str = "\n";
	
	foreach ($arr as $el) {
		$str .= (string)$el."\n";
	}
	
	return $str;
}

function pre_to_string($mixed) {
	return '<pre>'.(string)$mixed.'</pre>';
}

function array_2d_transpose($arr) {
	$out = array();
	foreach ($arr as $key => $subarr) {
		foreach ($subarr as $subkey => $subvalue) {
			$out[$subkey][$key] = $subvalue;
		}
	}
	return $out;
}

function array_flatten($array, $return = array()) {
	foreach ($array as $val) {
		if(is_array($val)) {
			$return = array_flatten($val, $return);
		} else if($val) {
			$return[] = $val;
		}
	}
	return $return;
}

function loc_first_occ($str, $arr, $offs = 0) {
	$ret = FALSE; $pos = -1;
	
	for ($j=$offs; $j<strlen($str); $j++) {
		for ($i=0; $i<count($arr); $i++) {
			if (substr($str, $j, strlen($arr[$i])) == $arr[$i] && (FALSE === $ret || $j < $ret)) {
				$ret = $j;
				$pos = $i;
				if ($j == $offs) //no better match is possible, so we're done
					goto __LOC_END;
			}
		}
	}
	__LOC_END:
	return FALSE === $ret ? FALSE : array($ret, $pos);
}

function preg_clean_matches($arr) {
	$len = count($arr);
	for ($i=0; $i<$len; $i++)
		if ($arr[$i][0] == '')
			unset($arr[$i]);
	return $arr;
}

function str_trans($str, $dict, $offset = 0) {
	foreach ($dict as $key => $val) {
		$t = strpos($str, $key, $offset);
		if (FALSE !== $t)
			return str_trans(str_splice($str, $t, strlen($key), $val), $dict);
	}
	return $str;
}

function str_trans_preg($str, $reg_dict) {
	$ret = $str;
	foreach ($reg_dict as $pat => $rep)
		$ret = preg_replace($pat, $rep, $ret);
	return $ret;
}

function loc_num_occ($str, $arr, $off = 0) {
	$ret = FALSE; $cpos = $off; $strlen = strlen($str);
	$c_occ = 0;
	
	while ($cpos < $strlen) {
		$sub = loc_first_occ($str, $arr, $cpos);
		if (FALSE !== $sub) {
			$cpos = $sub[0] + strlen($arr[$sub[1]]);
			$c_occ++;
		} else {
			break;
		}
	}
	
	return $c_occ;
}

function IEML_rem_empty_col(&$info, $check_call) {
	$loops = array('ver', 'hor');
	$empties = 0;
	
	for ($i=0; $i<count($info['headers']); $i++) {
		for ($j = count($info['headers'][$i])-1; $j>=0; $j--) {
			$empties = 0;
			for ($k=0; $k<count($info['headers'][$i][$j]); $k++) {
				if (call_user_func($check_call, $info['headers'][$i][$j][$k]))
					$empties++;
			}
			if ($empties == count($info['headers'][$i][$j])) {
				unset($info['headers'][$i][$j]);
				$info[$loops[$i].'_header_depth']--;
			}
		}
		$info['headers'][$i] = array_values($info['headers'][$i]);
	}
}

function IEML_count_empty_col(&$info, $check_call) {
	$empties = 0;
	
	$ret = array(array(array(), array()), array(0, 0));
	
	for ($i=0; $i<count($info['headers']); $i++) {
		for ($j = 0; $j < count($info['headers'][$i]); $j++) {
			$empties = 0;
			for ($k=0; $k<count($info['headers'][$i][$j]); $k++) {
				if (call_user_func($check_call, $info['headers'][$i][$j][$k]))
					$empties++;
			}
			if ($empties == count($info['headers'][$i][$j])) {
				$ret[0][$i][] = $j;
				$ret[1][$i]++;
			}
		}
	}
	
	return $ret;
}

function IEML_fix_table_body(&$info, &$map) {
	$max_len = 0;
	for ($i=0; $i<count($info['body']); $i++) {
		$tlen = 0;
		for ($j=count($info['body'][$i]); $j>=0; $j--) {
			if (!array_key_exists($info['body'][$i][$j]['expression'], $map) || !isset($map[$info['body'][$i][$j]['expression']])) {
				unset($info['body'][$i][$j]);
			}
		}
		$info['body'][$i] = array_values($info['body'][$i]);

		$max_len = max($max_len, count($info['body'][$i]));
	}
	
	if ($info['ver_header_depth'] > 0)
		for ($i=0; $i<count($info['headers'][0]); $i++)
			$max_len = max($max_len, count($info['headers'][0][$i]));
	
	$info['length'] = $max_len;
}

function genDBInfoAboutCell($exp, &$tab_info) {
	$ret = NULL;
	
	for ($i=0; $i<count($tab_info['headers']); $i++) {
		for ($j=0; $j<count($tab_info['headers'][$i]); $j++) {
			for ($k=0; $k<count($tab_info['headers'][$i][$j]); $k++) {
				if ($tab_info['headers'][$i][$j][$k][0] == $exp) {
					$ret = array(
						'intPosInTable' => $k,
						'enumElementType' => 'header',
						'enumHeaderType' => ($i==0 ? 'hor' : 'ver'),
						'intSpan' => $tab_info['headers'][$i][$j][$k][1],
						'intHeaderLevel' => $j);
					goto __THE_END;
				}
			}
		}
	}
	
	for ($i=0; $i<count($tab_info['body']); $i++) {
		for ($j=0; $j<count($tab_info['body'][$i]); $j++) {
			if ($tab_info['body'][$i][$j] == $exp) {
				$ret = array(
					'intPosInTable' => $i,
					'enumElementType' => 'element',
					'enumHeaderType' => 'ver',
					'intSpan' => 1,
					'intHeaderLevel' => $j);
				goto __THE_END;
			}
		}
	}
	
	__THE_END:
	
	return $ret;
}
function IEML_save_cell($tab_id, $cell_info, $exp_assoc, $db_info) {
	Conn::query("
	INSERT INTO table_2d_ref 
		(fkTable2D, fkExpressionPrimary, intPosInTable, enumElementType, enumHeaderType, intHeaderLevel, strCellExpression, intSpan)
	VALUES
		(".$tab_id.",
			".(array_key_exists($cell_info, $exp_assoc) ? $exp_assoc[$cell_info]['id'] : 'NULL').",
			".$db_info['intPosInTable'].",
			'".goodString($db_info['enumElementType'])."',
			'".goodString($db_info['enumHeaderType'])."',
			".$db_info['intHeaderLevel'].",
			'".goodString($cell_info)."',
			".goodInt($db_info['intSpan']).")");
}


function IEML_save_table($key_id, $table, $leftover_index, $con_index) {
	$tab_info = $table['tables'];
	$exp_query = Conn::queryArrays("
		SELECT
			pkExpressionPrimary as id, strExpression as expression
		FROM expression_primary prim
		WHERE enumDeleted = 'N'
		AND   prim.strExpression IN (".implode(',', array_map(function($a) { return "'".goodString($a)."'"; }, $table['flat_tables'])).")");

	$exp_assoc = array();
	for ($i=0; $i<count($exp_query); $i++) {
		$exp_assoc[$exp_query[$i]['expression']] = $exp_query[$i];
	}
	
	Conn::query("
		INSERT INTO table_2d_id 
			(
				fkExpression,
				intWidth,
				intHeight,
				intHorHeaderDepth,
				intVerHeaderDepth,
				intLeftoverIndex,
				intConcatIndex
			)
		VALUES
			(
				".$key_id.", "
				.$tab_info['length'].","
				.$tab_info['height'].","
				.$tab_info['hor_header_depth'].","
				.$tab_info['ver_header_depth'].","
				.goodInt($leftover_index).","
				.goodInt($con_index)."
			)
	");
			
	$key['new_id'] = Conn::getId();
	
	for ($i=0; $i<count($tab_info['headers']); $i++) {
		for ($j=0; $j<count($tab_info['headers'][$i]); $j++) {
			for ($k=0; $k<count($tab_info['headers'][$i][$j]); $k++) {
				IEML_save_cell($key['new_id'], $tab_info['headers'][$i][$j][$k][0], $exp_assoc,
					array(
						'intPosInTable' => $k,
						'enumElementType' => 'header',
						'enumHeaderType' => ($i==0 ? 'hor' : 'ver'),
						'intSpan' => $tab_info['headers'][$i][$j][$k][1],
						'intHeaderLevel' => $j)
				);
			}
		}
	}
	
	for ($i=0; $i<count($tab_info['body']); $i++) {
		for ($j=0; $j<count($tab_info['body'][$i]); $j++) {
			IEML_save_cell($key['new_id'], $tab_info['body'][$i][$j], $exp_assoc,
				array(
					'intPosInTable' => $i,
					'enumElementType' => 'element',
					'enumHeaderType' => 'ver',
					'intSpan' => 1,
					'intHeaderLevel' => $j)
			);
		}
	}
}

require_once(APPROOT.'/includes/table_related/table_render_related.php');
require_once(APPROOT.'/includes/table_related/table_gen_related.php');

function IEML_postproc_tables(&$table) {
	global $IEML_lowToVowelReg;
	
	$ret = NULL;
	
	if (is_array($table)) {
		$ret = array();
		
		foreach ($table as $key => $branch) {
			$ret[$key] = IEML_postproc_tables($branch);
		}
	} else if (is_string($table)) {
		$ret = str_trans_preg($table, $IEML_lowToVowelReg);
	} else {
		$ret = $table;
	}
	
	return $ret;
}

function IEML_postproc_body($body) {
	for ($i=0; $i<count($body); $i++) {
		for ($j=0; $j<count($body[$i]); $j++) {
			$arr_tokens = \IEML_ExpParse\str_to_tokens($body[$i][$j]);
			$arr_AST = \IEML_ExpParse\tokens_to_AST($arr_tokens);
			$arr_AST = \IEML_ExpParse\AST_eliminate_empties($arr_AST);
			
			$body[$i][$j] = \IEML_ExpParse\AST_to_pretty_str($arr_AST, $body[$i][$j]);
		}
	}
	
	return $body;
}

function IEML_table_collect_headers($tree) {
	$out = array();
	
	if (array_key_exists('rest', $tree)) {
		for ($i=0; $i<count($tree['rest']); $i++) {
			$sub = IEML_table_collect_headers($tree['rest'][$i]);
			
			for ($j=0; $j<count($sub); $j++) {
				if ($j >= count($out)) {
					$out[$j] = $sub[$j];
				} else {
					array_append($out[$j], $sub[$j]);
				}
			}
		}
	}
	
	$out[] = $tree['head'];
	
	return $out;
}

function IEML_coll_info($tree, $top) {
	if (array_key_exists('body', $tree)) {
		return array(
			'length' => 1,
			'height' => count($tree['body']),
			'hor_header_depth' => 0,
			'ver_header_depth' => 0,
			'headers' => FALSE,
			'body' => $tree['body'],
			'top' => $top
		);
	} else {
		$heads = array(IEML_table_collect_headers($tree[0][0]), IEML_table_collect_headers($tree[0][1]));
		$body = $tree[1];
	
		return array(
			'length' => count($body[0]),
			'height' => count($body),
			'hor_header_depth' => count($heads[0]),
			'ver_header_depth' => count($heads[1]),
			'headers' => $heads,
			'body' => $body,
			'top' => $top
		);
	}
}

function IEML_combine_concats($info, $key, $cur) {
	$ret = array();
	
	if ($cur + 1 < count($info)) {
		for ($i=0; $i<count($info[$cur][$key]); $i++) {
			$acc = IEML_combine_concats($info, $key, $cur+1);
			
			for ($j=0; $j<count($acc); $j++) {
				$ret[] = array_merge($acc[$j], array($info[$cur][$key][$i]));
			}
		}
	} else {
		for ($i=0; $i<count($info[$cur][$key]); $i++) {
			$ret[] = array($info[$cur][$key][$i]);
		}
	}
	
	return $ret;
}

function IEML_concat_complex_tables($info) {
	$top_index = array();
	
	for ($i=0; $i<count($info); $i++) {
		$index_tab = array();
		
		for ($j=0; $j<count($info[$i]); $j++) {
			$key = "l".$info[$i][$j]['tables']['length']."h".$info[$i][$j]['tables']['height']."hhd".$info[$i][$j]['tables']['hor_header_depth']."vhd".$info[$i][$j]['tables']['ver_header_depth'];
			
			$index_tab[$key][] = $info[$i][$j];
		}
		
		$top_index[] = $index_tab;
	}
	
	$ret = array();
	
	foreach ($top_index[0] as $key => $value) {
		array_append($ret, IEML_combine_concats($top_index, $key, 0));
	}
	
	return $ret;
}

function IEML_force_concat_check($AST) {
	if ($AST['internal'] && $AST['value']['type'] == 'LAYER') {
		return true;
	} else {
		if ($AST['internal']) {
			for ($i=0; $i<count($AST['children']); $i++) {
				if (IEML_force_concat_check($AST['children'][$i])) {
					return true;
				}
			}
		}
	}
	
	return false;
}

function IEML_gen_table_info($top) {
	$tokens = \IEML_ExpParse\str_to_tokens($top);
	$AST = \IEML_ExpParse\tokens_to_AST($tokens);

	if (IEML_force_concat_check($AST)) {
		$concats = \IEML_ExpParse\split_by_concats($AST);
	} else {
		$concats = array($AST);
	}
	
	$table_infos = array();
	$ret = array();
	
	for ($i=0; $i<count($concats); $i++) {
		$temp_coll = array();
		
		//echo 'concat['.$i.']: '.pre_dump(IEML_ExpParse\AST_to_infix_str($concats[$i], $top));
		
		$sub_top = \IEML_ExpParse\AST_original_str($concats[$i], $top);
		$raw_tab = IEML_gen_header($concats[$i], $top);
		
		//echo 'raw_tab for concats['.$i.']: '.pre_dump($raw_tab);
		
		for ($j=0; $j<count($raw_tab); $j++) {
			$raw_tab[$j][1] = IEML_postproc_body($raw_tab[$j][1]);
			$post_tab = IEML_postproc_tables($raw_tab[$j]);
			
			//echo '$post_tab: '.pre_dump($post_tab);
		
			$tab_concat = IEML_coll_info($post_tab, $sub_top);
		
			$flat_body = array_flatten($post_tab);
			$flat_body[] = $sub_top;
			
			$temp_coll[] = array(
				'tables' => $tab_concat,
				'flat_tables' => $flat_body,
				'raw_table' => $raw_tab[$j],
				'post_raw_table' => $post_tab
			);
		}
		
		$table_infos[] = $temp_coll;
	}
	
	return $table_infos;
}

function reconstruct_table_info($top, $head, $body_query) {
	$key = array(
		'length' => (int)$head['intWidth'],
		'height' => (int)$head['intHeight'],
		'hor_header_depth' => (int)$head['intHorHeaderDepth'],
		'ver_header_depth' => (int)$head['intVerHeaderDepth'],
		'headers' => array(
			$head['intHorHeaderDepth'] > 0 ? array_fill(0, $head['intHorHeaderDepth'], array()) : array(),
			$head['intVerHeaderDepth'] > 0 ? array_fill(0, $head['intVerHeaderDepth'], array()) : array()
		),
		'body' => NULL,
		'top' => $top
	);
	
	$key['body'] = $key['height'] > 0 ? array_fill(0, $key['height'], array()) : array();
	
	foreach ($body_query as $bel) {
		if ($bel['enumElementType'] == 'element') {
			$key['body'][$bel['intPosInTable']][$bel['intHeaderLevel']] = $bel;
		} else if ($bel['enumElementType'] == 'header') {
			if ($bel['enumHeaderType'] == 'ver') {
				$key['headers'][1][$bel['intHeaderLevel']][$bel['intPosInTable']] = array($bel, $bel['intSpan']);
			} else if ($bel['enumHeaderType'] == 'hor') {
				$key['headers'][0][$bel['intHeaderLevel']][$bel['intPosInTable']] = array($bel, $bel['intSpan']);
			}
		}
	} unset($bel);
	
	return $key;
}

function get_exp_position_in_table($exp, &$tab_info)
{
	for ($i=0; $i<count($tab_info['headers']); $i++)
	{
		for ($j=0; $j<count($tab_info['headers'][$i]); $j++)
		{
			for ($k=0; $k<count($tab_info['headers'][$i][$j]); $k++)
			{
				if ($tab_info['headers'][$i][$j][$k][0]['expression'] == $exp)
				{
					return $tab_info['headers'][$i][$j][$k][0];
				}
			}
		}
	}
	
	for ($i=0; $i<count($tab_info['body']); $i++)
	{
		for ($j=0; $j<count($tab_info['body'][$i]); $j++)
		{
			if ($tab_info['body'][$i][$j]['expression'] == $exp)
			{
				return $tab_info['body'][$i][$j];
			}
		}
	}
	
	return NULL;
}

function gen_contained($exp_info, &$table)
{
	$contained = array();

	if ($exp_info['enumElementType'] == 'header')
	{
		$i = $exp_info['enumHeaderType'] == 'hor' ? 0 : 1;

		for ($i = 0; $i < 2; $i++)
		{
			for ($j = $exp_info['intHeaderLevel'] + 1; $j < count($table['headers'][$i]); $j++)
			{
				$head_acc = 0;

				for ($k = 0; $k < count($table['headers'][$i][$j]); $k++)
				{
					$header_check = $table['headers'][$i][$j][$k][0];

					if ($head_acc <= $exp_info['intPosInTable'])
					{
						$head_acc += $header_check['intSpan'];

						if ($header_check['intSpan'] == $table['length'] ||
							$exp_info['enumHeaderType'] == $header_check['enumHeaderType'] &&
							$exp_info['intPosInTable'] < $head_acc)
						{
							$contained[] = $header_check;

							break;
						}
					}
				}
			}
		}
	}
	else if ($exp_info['enumElementType'] == 'element')
	{
		for ($i = 0; $i < count($table['headers']); $i++)
		{
			$comp_var = $i == 0 ?
				$comp_var = $exp_info['intPosInTable'] : 
				$comp_var = $exp_info['intHeaderLevel'];

			for ($j = 0; $j < count($table['headers'][$i]); $j++)
			{
				$body_acc = 0;

				for ($k = 0; $k < count($table['headers'][$i][$j]); $k++)
				{
					if ($body_acc <= $comp_var)
					{
						$body_acc += $table['headers'][$i][$j][$k][0]['intSpan'];

						if ($comp_var < $body_acc)
						{
							$contained[] = $table['headers'][$i][$j][$k][0];

							break;
						}
					}
				}
			}
		}
	}

	return $contained;
}

function gen_containing_body($exp_info, &$table)
{
	$containing = array();

	if ($exp_info['enumElementType'] == 'header')
	{
		if ($exp_info['enumHeaderType'] == 'hor')
		{
			for ($j = 0; $j < count($table['body']); $j++)
			{
				for ($k = 0; $k < count($table['body'][$j]); $k++)
				{
					if ($exp_info['intPosInTable'] <= $j && $j < $exp_info['intPosInTable'] + $exp_info['intSpan'])
					{
						$containing[] = $table['body'][$j][$k];
					}
				}
			}
		}
		else
		{
			for ($j = 0; $j < count($table['body']); $j++)
			{
				for ($k = 0; $k < count($table['body'][$j]); $k++)
				{
					if ($exp_info['intPosInTable'] <= $k && $k < $exp_info['intPosInTable'] + $exp_info['intSpan'])
					{
						$containing[] = $table['body'][$j][$k];
					}
				}
			}
		}
	}

	return $containing;
}

function gen_containing($exp_info, &$table)
{
	$containing = gen_containing_body($exp_info, $table);

	if ($exp_info['enumElementType'] == 'header')
	{
		$i = $exp_info['enumHeaderType'] == 'hor' ? 0 : 1;

		for ($j = count($exp_info['intHeaderLevel']) - 1; $j >= 0; $j--)
		{
			for ($k = 0; $k < count($table['headers'][$i][$j]); $k++)
			{
				if ($exp_info['intPosInTable'] <= $table['headers'][$i][$j][$k][0]['intPosInTable']
					&& $table['headers'][$i][$j][$k][0]['intPosInTable'] < $exp_info['intPosInTable'] + $exp_info['intSpan'])
				{
					$containing[] = $table['headers'][$i][$j][$k][0];
				}
			}
		}
	}

	return $containing;
}

function gen_concurrent($contained, &$table)
{
	$concurrent = array();

	for ($i = 0; $i < count($contained); $i++)
	{
		$concurrent[$contained[$i]['expression']] = gen_containing_body($contained[$i], $table);
	}

	return $concurrent;
}

function gen_complementary($exp)
{
	global $lvl_to_sym;

	$complementary = NULL;

	$tokens = \IEML_ExpParse\str_to_tokens($exp);
	$AST = \IEML_ExpParse\tokens_to_AST($tokens);
	
	$etym = \IEML_ExpParse\fetch_etymology_from_AST($AST);

	if (count($etym) == 2)
	{
		$highest_layer = \IEML_ExpParse\highest_LAYER_AST($AST);

		$complementary = 
			\IEML_ExpParse\AST_original_str($etym[1], $exp) .
			\IEML_ExpParse\AST_original_str($etym[0], $exp) .
			$lvl_to_sym[$highest_layer];
	}

	return $complementary;
}

function gen_diagonal($info, &$table)
{
	$diagonal = array();
	$expression_in_diagonal = false;

	$body = $table['body'];

	for ($i = 0; $i < min(count($body), count($body[0])); $i++)
	{
		$current_expression = $body[$i][$i];

		if ($info['expression'] != $current_expression['expression'])
		{
			$diagonal[] = $current_expression;
		}
		else
		{
			$expression_in_diagonal = true;
		}
	}

	return $expression_in_diagonal ? $diagonal : array();
}

function extract_recursive_prop($arr, $prop)
{
	$ret = array();

	if (is_array($arr))
	{
		if (array_key_exists($prop, $arr))
		{
			$ret[] = $arr[$prop];
		}
		else
		{
			foreach ($arr as $val)
			{
				array_append($ret, call_user_func(__FUNCTION__, $val, $prop));
			}
		}
	}

	return $ret;
}

function gen_exp_relations($exp, $top, &$table)
{
	$ret = NULL;

	if ($exp['expression'] == $top)
	{
		$ret = array(
			'complementary' => gen_complementary($exp['expression']),
			'contained' => array(),
			'concurrent' => array(),
			'diagonal' => array()
		);
		$ret['containing'] = array_flatten(extract_recursive_prop($table['headers'], 'expression'));
		array_append($ret['containing'], array_flatten(extract_recursive_prop($table['body'], 'expression')));
	}
	else
	{
		$info = get_exp_position_in_table($exp['expression'], $table);

		if ($info)
		{
			$ret = array(
				'complementary' => gen_complementary($exp['expression']),
				'contained' => gen_contained($info, $table),
				'containing' => gen_containing($info, $table),
				'diagonal' => gen_diagonal($info, $table)
			);
			$ret['concurrent'] = gen_concurrent($ret['contained'], $table);
		}
	}
	
	return $ret;
}

function postproc_exp_relations(&$rel, $callback)
{
	for ($i=0; $i<count($rel['contained']); $i++)
	{
		$rel['contained'][$i] = call_user_func($callback, $rel['contained'][$i]);
	}

	for ($i=0; $i<count($rel['containing']); $i++)
	{
		$rel['containing'][$i] = call_user_func($callback, $rel['containing'][$i]);
	}

	for ($i=0; $i<count($rel['diagonal']); $i++)
	{
		$rel['diagonal'][$i] = call_user_func($callback, $rel['diagonal'][$i]);
	}

	foreach($rel['concurrent'] as $exp => $con_list)
	{
		for($i=0; $i<count($rel['concurrent'][$exp]); $i++)
		{
			$rel['concurrent'][$exp][$i] = call_user_func($callback, $rel['concurrent'][$exp][$i]);
		}
	}

	$rel['complementary'] = call_user_func($callback, $rel['complementary']);

	return $rel;
}

function gen_etymology($exp)
{
	$tokens = \IEML_ExpParse\str_to_tokens($exp);
	$AST = \IEML_ExpParse\tokens_to_AST($tokens);
	
	$etym = \IEML_ExpParse\fetch_etymology_from_AST($AST);
	
	$ret = array();
	for ($i=0; $i<count($etym); $i++)
	{
		if ($etym[$i]['internal'] == FALSE)
		{
			$ret[] = $etym[$i]['value'][0]['value'].':';
		}
		else
		{
			$ret[] = \IEML_ExpParse\AST_original_str($etym[$i], $exp);
		}
	}
	
	return $ret;
}
