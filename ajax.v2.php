<?php

//TODO: fix some tables' body, as the markup is sub-par

include_once('includes/config.php');
include_once('includes/functions.php');
include_once('includes/table_functions.php');

if (isset($_REQUEST['a'])) $ajax = $_REQUEST['a'];
else die('No action specified.');

$mapby_ISO6392 = array('fra' => 'fr', 'eng' => 'en');
$mapby_ISO6391 = array('fr' => 'fra', 'en' => 'eng');

switch ($ajax) {
    case 'searchDictionary':
        if (isset($_REQUEST['id'])) {
            $goodID = goodInt($_REQUEST['id']);
            $ret = Conn::queryArray("
                SELECT
                    pkExpressionPrimary as id, strExpression as expression, enumCategory,
                    enumDeleted, eng.strDescriptor AS descriptor_en, fra.strDescriptor AS descriptor_fr
                FROM expression_primary prim
                LEFT JOIN
                    (
                        SELECT fkExpressionPrimary, strDescriptor
                        FROM expression_descriptors
                        WHERE strLanguage = 'eng'
                        AND fkExpressionPrimary = ".$goodID."
                    ) eng
                    ON eng.fkExpressionPrimary = prim.pkExpressionPrimary
                LEFT JOIN
                    (
                        SELECT fkExpressionPrimary, strDescriptor
                        FROM expression_descriptors
                        WHERE strLanguage = 'fra'
                        AND fkExpressionPrimary = ".$goodID."
                    ) fra
                    ON fra.fkExpressionPrimary = prim.pkExpressionPrimary
                WHERE pkExpressionPrimary = ".$goodID);
            
            
            if (!isset($_REQUEST['disableTableGen']) || $_REQUEST['disableTableGen'] != 'true') {
                $table_head_query = NULL;
                $table_body_query = NULL;
                $top = NULL;
                $ret['table'] = NULL;
                if ($ret['enumCategory'] == 'Y') {
                    $table_head_query = Conn::queryArray("
                        SELECT pkTable2D, intWidth, intHeight, intHorHeaderDepth, intVerHeaderDepth
                        FROM table_2d_id
                        WHERE enumDeleted = 'N' AND fkExpression = ".$goodID." LIMIT 1");
                    $ret['descriptor'] = $ret['descriptor_'.$_REQUEST['search-lang-select']];
                    $top = $ret;
                } else {
                    $table_head_query = Conn::queryArray("
                        SELECT
                            pkTable2D, intWidth, intHeight, intHorHeaderDepth, intVerHeaderDepth,
                            prim.strExpression AS expression, sublang.strDescriptor AS descriptor,
                            prim.pkExpressionPrimary as id, t2dref.enumEnabled
                        FROM table_2d_id t2d
                        JOIN expression_primary prim ON prim.pkExpressionPrimary = t2d.fkExpression
                        JOIN table_2d_ref t2dref ON fkTable2D = pkTable2D
                        LEFT JOIN
                            (
                                SELECT fkExpressionPrimary, strDescriptor
                                FROM expression_descriptors
                                WHERE strLanguageISO6391 = ".goodInput($_REQUEST['search-lang-select'])."
                            ) sublang
                            ON sublang.fkExpressionPrimary = prim.pkExpressionPrimary
                        WHERE t2d.enumDeleted = 'N' AND t2dref.fkExpressionPrimary = ".$goodID." LIMIT 1");
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
                            ref.fkExpressionPrimary AS id, intPosInTable, enumElementType, enumHeaderType,
                            intHeaderLevel, enumEnabled, ref.pkTable2DRef AS refID, enumEnabled,
                            strExpression as expression, sublang.strDescriptor AS descriptor
                        FROM table_2d_ref ref
                        LEFT JOIN expression_primary prim ON pkExpressionPrimary = fkExpressionPrimary
                        LEFT JOIN
                            (
                                SELECT fkExpressionPrimary, strDescriptor
                                FROM expression_descriptors
                                WHERE strLanguageISO6391 = ".goodInput($_REQUEST['search-lang-select'])."
                            ) sublang
                            ON sublang.fkExpressionPrimary = prim.pkExpressionPrimary
                        WHERE enumDeleted = 'N'
                        AND   fkTable2D = ".$table_head_query['pkTable2D']."");
                    
                    $table_info = reconstruct_table_info($top, $table_head_query, $table_body_query);
                    IEML_rem_empty_col_REALTIME($table_info);
                    //IEML_fix_table_body_REALTIME($table_info);
                    
                    //$ret['debug'] = pre_dump($table_info);
                    $ret['table'] = IEML_render_tables($table_info, function ($el) use ($ret, $top) {
                        /*
                        if ($el['expression'] != $top['expression'])
                            echo '<input class="hide enable_check" data-ref-id="'.$el['refID'].'" type="checkbox" value="Y" '.($el['enumEnabled'] == 'Y' ? 'checked="checked"':'').'/>';
                        */
                        
                        echo '<div class="'.($el['enumEnabled'] == 'N' ? 'hide ': '').'cell_wrap';
                        if ($el['expression'] == $ret['expression'])
                            echo ' relation-sel-cell';
                        else if ($el['expression'] == $top['expression'])
                            echo ' relation-top-cell';
                        echo '">';
                        
                        echo '<a href="/ajax.php?id='.$el['id'].'&a=searchDictionary" class="editExp">'.$el['expression'].'<hr class="short_hr" />'.$el['descriptor'].'</a>';
                        
                        echo '</div>';
                    });
                }
            }

            echo json_encode($ret);
            //echo pre_dump($ret['table']);
        } else {
            if (!isset($_REQUEST['search']) || !isset($_REQUEST['search-lang-select'])) die();
            if (strpos($_REQUEST['search'], ':') || strpos($_REQUEST['search'], '.')) {
                $ret = Conn::queryArrays("
                    SELECT
                        pkExpressionPrimary AS id, strExpression AS expression,
                        enumCategory, enumDeleted, sublang.strDescriptor AS descriptor
                    FROM expression_primary prim
                    LEFT JOIN
                        (
                            SELECT fkExpressionPrimary, strDescriptor
                            FROM expression_descriptors
                            WHERE strLanguageISO6391 = ".goodInput($_REQUEST['search-lang-select'])."
                        ) sublang
                        ON sublang.fkExpressionPrimary = prim.pkExpressionPrimary
                    WHERE enumDeleted = 'N'
                    AND   strExpression LIKE '%".goodString($_REQUEST['search'])."%'
                    ORDER BY expression");
            } else {
                $ret = Conn::queryArrays("
                    SELECT
                        pkExpressionPrimary AS id, strExpression AS expression,
                        enumCategory, enumDeleted, sublang.strDescriptor AS descriptor
                    FROM expression_primary prim
                    LEFT JOIN
                        (
                            SELECT fkExpressionPrimary, strDescriptor
                            FROM expression_descriptors
                            WHERE strLanguageISO6391 = ".goodInput($_REQUEST['search-lang-select'])."
                        ) sublang
                        ON sublang.fkExpressionPrimary = prim.pkExpressionPrimary
                    WHERE enumDeleted = 'N'
                    AND   sublang.strDescriptor LIKE '%".goodString($_REQUEST['search'])."%'
                    ORDER BY expression");
                }
            
            foreach ($ret as $item) {
                echo '<tr data-result-id="'.$item['id'].'"><td>'.$item['expression'].'</td><td>'.$item['descriptor'].'</td><td><a href="/ajax.php?id='.rawurlencode($item['id']).'&a=searchDictionary" class="btn editExp"><span class="icon-pencil"></span></a></td></tr>';
            }
        }
        
        break;
    case 'deleteDictionary':
        if (isset($_REQUEST['id'])) {
            Conn::query("UPDATE expression_primary SET enumDeleted = 'Y' WHERE pkExpressionPrimary = ".goodInt($_REQUEST['id']));
        } else if (isset($_REQUEST['exp'])) {
            Conn::query("UPDATE expression_primary SET enumDeleted = 'Y' WHERE strExpression = ".goodInput($_REQUEST['exp']));
        }
        
        break;
    case 'editDictionary':
        if (!isset($_REQUEST['data']) || !isset($_REQUEST['exp']) || !isset($_REQUEST['id'])) die('Bad data.');
        
        Conn::query("
            UPDATE expression_primary
            SET
                enumCategory = ".goodInput($_REQUEST['enumCategory']).",
                strExpression = ".goodInput($_REQUEST['exp'])."
            WHERE pkExpressionPrimary = ".goodInt($_REQUEST['id']));
        
        foreach ($_REQUEST['data'] as $item)
            if (isset($item['lang']) && isset($item['descriptor'])) {
                Conn::query("
                    UPDATE expression_descriptors
                    SET
                        strDescriptor = ".goodInput($item['descriptor'])."
                    WHERE fkExpressionPrimary = ".goodInt($_REQUEST['id'])."
                    AND   strLanguageISO6391 = ".goodInput($item['lang'])." LIMIT 1");
            }
        
        //var_dump($_REQUEST);
        
        break;
    case 'newDictionary': 
        if (!isset($_REQUEST['exp'])) die();
        
        $ret = array();
            
        Conn::query("
            INSERT INTO expression_primary
                (strExpression, enumCategory)
            VALUES
                (".goodInput($_REQUEST['exp']).", ".goodInput($_REQUEST['enumCategory']).")");
                
        $ret['newID'] = Conn::getId();
        
        foreach ($_REQUEST['data'] as $item) {
            if (isset($item['lang']) && isset($item['descriptor'])) {
                Conn::query("
                    INSERT INTO expression_descriptors
                        (fkExpressionPrimary, strDescriptor, strLanguage, strLanguageISO6391)
                    VALUES
                        (".$ret['newID'].", ".goodInput($iem['descriptor']).", '".$mapby_ISO6391[goodString($item['lang'])]."', ".goodInput($item['lang']).")");
            }
        }
        
        echo json_encode($ret);
        
        break;
    case 'setTableEl':
        if (!isset($_REQUEST['id'])) die();
        
        Conn::query("UPDATE table_2d_ref SET enumEnabled = ".goodInput($_REQUEST['enumEnabled'])." WHERE pkTable2DRef = ".goodInt($_REQUEST['id']));
        
        break;
    default: echo "No such identifier: ".$ajax; break;
}

Conn::closeStaticHandle();
?>