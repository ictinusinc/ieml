<?php

function getTableForElement($ret, $goodID, $options) {
	$table_head_query = NULL;
    $table_body_query = NULL;
    $top = NULL;
    $lang = strtolower($options['lang']);
    
    if ($ret['enumCategory'] == 'Y') {
        $table_head_query = Conn::queryArrays("
            SELECT
            	pkTable2D, enumShowEmpties, intWidth, intHeight, intHorHeaderDepth, intVerHeaderDepth,
            	prim.strExpression as expression, sublang.strDescriptor as descriptor, jsonTableLogic,
            	t2d.fkExpression as id, enumCompConc, strEtymSwitch, intLeftoverIndex, intConcatIndex
            FROM table_2d_id t2d
            JOIN expression_primary prim ON t2d.fkExpression = prim.pkExpressionPrimary
            LEFT JOIN
                (
                    SELECT fkExpressionPrimary, strDescriptor
                    FROM expression_descriptors
                    WHERE strLanguageISO6391 = '".goodString($lang)."'
                ) sublang
                ON sublang.fkExpressionPrimary = t2d.fkExpression
            WHERE t2d.enumDeleted = 'N' AND fkExpression = ".$goodID);
        $top = $ret;
    } else {
        $table_head_query = Conn::queryArrays("
            SELECT
                pkTable2D, enumShowEmpties, intWidth, intHeight, intHorHeaderDepth, intVerHeaderDepth,
                prim.strExpression AS expression, sublang.strDescriptor AS descriptor, jsonTableLogic,
                prim.pkExpressionPrimary as id, enumCompConc, strEtymSwitch, t2dref.enumEnabled,
                intLeftoverIndex, intConcatIndex
            FROM table_2d_id t2d
            JOIN expression_primary prim ON prim.pkExpressionPrimary = t2d.fkExpression
            JOIN table_2d_ref t2dref ON fkTable2D = pkTable2D
            LEFT JOIN
                (
                    SELECT fkExpressionPrimary, strDescriptor
                    FROM expression_descriptors
                    WHERE strLanguageISO6391 = '".goodString($lang)."'
                ) sublang
                ON sublang.fkExpressionPrimary = prim.pkExpressionPrimary
            WHERE t2d.enumDeleted = 'N' AND t2dref.strCellExpression = '".goodString($ret['expression'])."'");
        
        $top = array(
            'expression' => $table_head_query[0]['expression'],
            'descriptor' => $table_head_query[0]['descriptor'],
            'id' => $table_head_query[0]['id'],
            'enumEnabled' => $table_head_query[0]['enumEnabled']
        );
    }
    
    $ret['tables'] = array();
    
    for ($i=0; $i<count($table_head_query); $i++) {
    	$formatted_sub = format_table_for($table_head_query[$i], $ret, $top, $options);
    
	    $formatted_sub['height'] = $table_head_query[$i]['intHeight'];
	    $formatted_sub['length'] = $table_head_query[$i]['intWidth'];
    	
    	if (array_key_exists($table_head_query[$i]['intLeftoverIndex'], $ret['tables'])) {
	    	$ret['tables'][$table_head_query[$i]['intLeftoverIndex']][$table_head_query[$i]['intConcatIndex']] = $formatted_sub;
    	} else {
	    	$ret['tables'][$table_head_query[$i]['intLeftoverIndex']] = array($table_head_query[$i]['intConcatIndex'] => $formatted_sub);
    	}
    }
    
    return $ret;
}


function format_table_for($table_head_query, $query_exp, $top, $options) {
	$ret = array();
    $lang = strtolower($options['lang']);
	
	$table_body_query = Conn::queryArrays("
        SELECT
            ref.fkExpressionPrimary AS id, ref.intPosInTable, ref.enumElementType, ref.enumHeaderType, ref.intSpan,
            ref.intHeaderLevel, ref.pkTable2DRef AS refID, ref.enumEnabled, ref.strCellExpression as expression
        FROM table_2d_ref ref
        WHERE fkTable2D = ".$table_head_query['pkTable2D']."");
        
    $exp_query = Conn::queryArrays("
        SELECT
            pkExpressionPrimary as id, prim.strExpression as expression,
            sublang.strDescriptor AS descriptor
        FROM expression_primary prim
        LEFT JOIN
            (
                SELECT fkExpressionPrimary, strDescriptor
                FROM expression_descriptors
                WHERE strLanguageISO6391 = ".goodInput($lang)."
            ) sublang
            ON sublang.fkExpressionPrimary = prim.pkExpressionPrimary
        WHERE enumDeleted = 'N'
        AND   prim.strExpression IN (".implode(',', array_map(function($a) { return goodInput($a['expression']); }, $table_body_query)).")");
    
    $table_info = reconstruct_table_info($top, $table_head_query, $table_body_query);
    $table_info['post_raw_table'] = json_decode($table_head_query['jsonTableLogic'], TRUE);
    
    $flat_assoc = array();
    for ($i=0; $i<count($exp_query); $i++) {
        $flat_assoc[$exp_query[$i]['expression']] = $exp_query[$i];
    }
    $flat_assoc[$top['expression']] = $top;
    
    /*
    IEML_rem_empty_col($table_info, function($a) use ($flat_assoc) {
        return !isset($flat_assoc[$a[0]['expression']]);
    });
    */
    //IEML_fix_table_body($table_info, $flat_assoc);
    
    $empty_head_count = IEML_count_empty_col($table_info, function($a) use ($flat_assoc) {
        return !isset($flat_assoc[$a[0]['expression']]);
    });
    
    $table_info['empty_head_count'] = $empty_head_count;
    
    $ret['edit_vertical_head_length'] = $table_info['ver_header_depth'] + 1;
    $ret['render_vertical_head_length'] = $table_info['ver_header_depth'] - $empty_head_count[1][1] + 1;
    $ret['edit_horizontal_head_length'] = $table_info['hor_header_depth'];
    $ret['render_horizontal_head_length'] = $table_info['hor_header_depth'] - $empty_head_count[1][0];
    
    $ret['iemlEnumComplConcOff'] = invert_bool($table_head_query['enumCompConc'], 'Y', 'N');
    $ret['iemlEnumSubstanceOff'] = invert_bool($table_head_query['strEtymSwitch'][0], 'Y', 'N');
    $ret['iemlEnumAttributeOff'] = invert_bool($table_head_query['strEtymSwitch'][1], 'Y', 'N');
    $ret['iemlEnumModeOff'] = invert_bool($table_head_query['strEtymSwitch'][2], 'Y', 'N');
    
    $ret['pkTable2D'] = $table_head_query['pkTable2D'];
    $ret['enumShowEmpties'] = $table_head_query['enumShowEmpties'];
    
    $ret['relations'] = gen_exp_relations($query_exp['expression'], $top['expression'], $table_info, function($el) use ($flat_assoc) {
        return array_key_exists($el[0], $flat_assoc);
    });
    
    //get expression relations
    $ret['relations'] = postproc_exp_relations($ret['relations'], function($el) use ($flat_assoc) {
    	if (isset($el)) {
        	if (is_array($el[0]) && array_key_exists('expression', $el[0])) {
        		return array('exp' => array($el[0]['expression'], $el[1]), 'desc' => $flat_assoc[$el[0]['expression']]['descriptor'], 'id' => $flat_assoc[$el[0]['expression']]['id']);
        	} else {
        		return array('exp' => $el, 'desc' => $flat_assoc[$el[0]]['descriptor'], 'id' => $flat_assoc[$el[0]]['id']);
        	}
    	}
    });
    
    if ($query_exp['enumCategory'] == 'Y') {
    	$ret['etymology'] = NULL;
    } else {
        //get expression etymology
        $temp_etym = gen_etymology($query_exp['expression']);
        
        //fetch some descriptors from the DB so that the eymological parts display nicely
        $etym_query = Conn::queryArrays("
            SELECT
                pkExpressionPrimary as id, prim.strExpression as expression,
                sublang.strDescriptor AS descriptor
            FROM expression_primary prim
            LEFT JOIN
                (
                    SELECT fkExpressionPrimary, strDescriptor
                    FROM expression_descriptors
                    WHERE strLanguageISO6391 = ".goodInput($lang)."
                ) sublang
                ON sublang.fkExpressionPrimary = prim.pkExpressionPrimary
            WHERE enumDeleted = 'N'
            AND   prim.strExpression IN (".implode(',', array_map(function($a) { return goodInput($a); }, $temp_etym)).")");
         
        for ($i=0; $i<count($etym_query); $i++) {
            $flat_assoc[$etym_query[$i]['expression']] = $etym_query[$i];
        }
        
        //do some post-processing on the etymological array, to get some use preferences and API info out
        $temp_etym = array_map(function($el) use ($flat_assoc) {
        	return array('exp' => $el, 'desc' => $flat_assoc[$el]['descriptor'], 'id' => $flat_assoc[$el]['id']);
        }, $temp_etym);
        
        $ret['etymology'] = array();
        for ($i=0; $i<count($temp_etym); $i++) {
	        if ($table_head_query['strEtymSwitch'][$i] == 'Y') {
		        $ret['etymology'][] = $temp_etym[$i];
	        }
        }
    }
    
    //add top as a vertical header
    $span = 0;
    foreach ($table_info['headers'][1][count($table_info['headers'][1]) - 1] as $last_vertical_el) {
	    $span += $last_vertical_el[1];
    }
    $table_info['headers'][1][] = array(array(array(
    	'descriptor' => $top['descriptor'],
		'enumElementType' => "header",
		'enumEnabled' => "Y",
		'enumHeaderType' => "ver",
		'expression' => $top['expression'],
		'id' => $top['id'],
		'intSpan' => $span
    ), $span));
    
    $ret['table'] = IEML_postprocess_table(array(
    	'headers' => $table_info['headers'],
    	'body' => $table_info['body']
    ), function($el) use ($flat_assoc) {
    	$el['descriptor'] = $flat_assoc[$el['expression']]['descriptor'];
    	
    	return $el;
    });
    
    return $ret;
}

?>
