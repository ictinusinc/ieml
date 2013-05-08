<?php

function getTableForElement($ret, $goodID, $options) {
	$table_head_query = NULL;
    $table_body_query = NULL;
    $top = NULL;
    $ret['table'] = NULL;
    if ($ret['enumCategory'] == 'Y') {
        $table_head_query = Conn::queryArray("
            SELECT
            	pkTable2D, enumShowEmpties, intWidth, intHeight,
            	intHorHeaderDepth, intVerHeaderDepth, jsonTableLogic,
            	enumCompConc, strEtymSwitch
            FROM table_2d_id
            WHERE enumDeleted = 'N' AND fkExpression = ".$goodID." LIMIT 1");
        $top = $ret;
    } else {
        $table_head_query = Conn::queryArray("
            SELECT
                pkTable2D, enumShowEmpties, intWidth, intHeight, intHorHeaderDepth, intVerHeaderDepth,
                prim.strExpression AS expression, sublang.strDescriptor AS descriptor, jsonTableLogic,
                prim.pkExpressionPrimary as id, t2dref.enumEnabled, enumCompConc, strEtymSwitch
            FROM table_2d_id t2d
            JOIN expression_primary prim ON prim.pkExpressionPrimary = t2d.fkExpression
            JOIN table_2d_ref t2dref ON fkTable2D = pkTable2D
            LEFT JOIN
                (
                    SELECT fkExpressionPrimary, strDescriptor
                    FROM expression_descriptors
                    WHERE strLanguageISO6391 = ".goodInput($options['lang'])."
                ) sublang
                ON sublang.fkExpressionPrimary = prim.pkExpressionPrimary
            WHERE t2d.enumDeleted = 'N' AND t2dref.strCellExpression = ".goodInput($ret['expression'])." LIMIT 1");
        
        $top = array(
            'expression' => $table_head_query['expression'],
            'descriptor' => $table_head_query['descriptor'],
            'id' => $table_head_query['id'],
            'enumEnabled' => $table_head_query['enumEnabled']
        );
    }
    
    if ($table_head_query != NULL) {
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
                    WHERE strLanguageISO6391 = ".goodInput($options['lang'])."
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
        
        $ret['edit_vertical_head_length'] = $table_info['ver_header_depth'];
        $ret['render_vertical_head_length'] = $table_info['ver_header_depth'] - $empty_head_count[1][1];
        $ret['edit_horizontal_head_length'] = $table_info['hor_header_depth'];
        $ret['render_horizontal_head_length'] = $table_info['hor_header_depth'] - $empty_head_count[1][0];
        
        $ret['iemlEnumComplConcOff'] = invert_bool($table_head_query['enumCompConc'], 'Y', 'N');
        $ret['iemlEnumSubstanceOff'] = invert_bool($table_head_query['strEtymSwitch'][0], 'Y', 'N');
        $ret['iemlEnumAttributeOff'] = invert_bool($table_head_query['strEtymSwitch'][1], 'Y', 'N');
        $ret['iemlEnumModeOff'] = invert_bool($table_head_query['strEtymSwitch'][2], 'Y', 'N');
        
        $ret['pkTable2D'] = $table_head_query['pkTable2D'];
        $ret['enumShowEmpties'] = $table_head_query['enumShowEmpties'];
        
        $ret['relations'] = gen_exp_relations($ret['expression'], $top['expression'], $table_info, function($el) use ($flat_assoc) {
	        return array_key_exists($el[0], $flat_assoc);
        });
        
        //$ret['debug'] = pre_dump($ret['relations']);
        
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
        
        if ($ret['enumCategory'] == 'Y') {
        	$ret['etymology'] = NULL;
        } else {
	        //get expression etymology
	        $temp_etym = gen_etymology($ret['expression']);
	        
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
	                    WHERE strLanguageISO6391 = ".goodInput($options['lang'])."
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
        
        $ret['table'] = IEML_render_tables($table_info, function ($el) use ($ret, $top, $flat_assoc) {
            //if ($el['expression'] != $top['expression'])
                //echo '<input class="hide enable_check" data-ref-id="'.$el['refID'].'" type="checkbox" value="Y" '.($el['enumEnabled'] == 'Y' ? 'checked="checked"':'').'/>';
            if (array_key_exists('descriptor', $flat_assoc[$el['expression']])) {
                echo '<div class="'.($el['enumEnabled'] == 'N' ? 'hide ' : '').'cell_wrap';
                if ($el['expression'] == $ret['expression'])
                    echo ' relation-sel-cell';
                else if ($el['expression'] == $top['expression'])
                    echo ' relation-top-cell';
                echo '">';
                
                echo '<a href="/ajax.php?id='.$flat_assoc[$el['expression']]['id'].'&a=searchDictionary" data-exp="'.$el['expression'].'" data-id="'.$flat_assoc[$el['expression']]['id'].'" class="editExp"><span>'.$el['expression'].'</span>'.$flat_assoc[$el['expression']]['descriptor'].'</a>';
                
                echo '</div>';
            } else {
                echo '<div>';
                
                echo '<a href="javascript:void(0);" class="createEmptyExp">'.$el['expression'].'</a>';
                
                echo '</div>';
            }
        }, function($el) use ($flat_assoc) {
            return array_key_exists('descriptor', $flat_assoc[$el['expression']]);
        });
    }
    
    return $ret;
}

?>
