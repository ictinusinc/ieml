<?php
include_once('includes/config.php');
include_once('includes/functions.php');

if (isset($_REQUEST['a'])) $ajax = $_REQUEST['a'];
else die();

switch ($ajax) {
    case 'searchDictionary':
        if (isset($_REQUEST['id'])) {
            $ret = Conn::queryObject("
                SELECT
                    dynamic_fields_en.expression, dynamic_fields_en.id,
                    dynamic_fields_en.descriptor as descriptorEn, dynamic_fields_fr.descriptor as descriptorFr,
                    dynamic_fields_en.enumCategory
                FROM dynamic_fields_en
                JOIN dynamic_fields_fr ON dynamic_fields_en.expression = dynamic_fields_fr.expression
                WHERE dynamic_fields_en.id = ".goodInt($_REQUEST['id'])."
                AND dynamic_fields_en.enumDeleted = 'N' AND dynamic_fields_fr.enumDeleted = 'N'
                ORDER BY expression LIMIT 1");
            
            echo json_encode($ret);
        } else {
            if (!isset($_REQUEST['search']) || !isset($_REQUEST['search-lang-select'])) die();
            if (strpos($_REQUEST['search'], ':') || strpos($_REQUEST['search'], '.')) {
                $ret = Conn::queryObjects("
                    SELECT
                        dynamic_fields_en.expression, dynamic_fields_en.id, dynamic_fields_".goodString($_REQUEST['search-lang-select']).".descriptor,
                        dynamic_fields_en.enumCategory
                    FROM dynamic_fields_en
                    JOIN dynamic_fields_fr ON dynamic_fields_en.expression = dynamic_fields_fr.expression
                    WHERE dynamic_fields_en.expression LIKE '%".goodString($_REQUEST['search'])."%'
                    AND dynamic_fields_en.enumDeleted = 'N' AND dynamic_fields_fr.enumDeleted = 'N'
                    ORDER BY expression");
            } else {
                $ret = Conn::queryObjects("
                    SELECT dynamic_fields_en.expression, dynamic_fields_en.id, dynamic_fields_".goodString($_REQUEST['search-lang-select']).".descriptor
                    FROM dynamic_fields_en
                    JOIN dynamic_fields_fr ON dynamic_fields_en.expression = dynamic_fields_fr.expression
                    WHERE dynamic_fields_".goodString($_REQUEST['search-lang-select']).".descriptor LIKE '%".goodString($_REQUEST['search'])."%'
                    AND dynamic_fields_en.enumDeleted = 'N' AND dynamic_fields_fr.enumDeleted = 'N'
                    ORDER BY dynamic_fields_".goodString($_REQUEST['search-lang-select']).".descriptor");
            }
            
            foreach ($ret as $item) {
                echo '<tr data-result-id="'.$item->id.'"><td>'.$item->expression.'</td><td>'.$item->descriptor.'</td><td><a href="/ajax.php?id='.rawurlencode($item->id).'" class="btn editExp"><span class="icon-pencil"></span></a></td></tr>';
            }
        }
        
        break;
    case 'deleteDictionary':
        if (isset($_REQUEST['id'])) {
            Conn::query("UPDATE dynamic_fields_en SET enumDeleted = 'Y' WHERE id = ".goodInt($_REQUEST['id']));
            Conn::query("UPDATE dynamic_fields_fr SET enumDeleted = 'Y' WHERE id = ".goodInt($_REQUEST['id']));
        } else if (isset($_REQUEST['exp'])) {
            Conn::query("UPDATE dynamic_fields_en SET enumDeleted = 'Y' WHERE expression = ".goodInput($_REQUEST['exp']));
            Conn::query("UPDATE dynamic_fields_fr SET enumDeleted = 'Y' WHERE expression = ".goodInput($_REQUEST['exp']));
        }
        
        break;
    case 'editDictionary':
        if (!isset($_REQUEST['data']) || !isset($_REQUEST['exp']) || !isset($_REQUEST['id'])) die('Bad data.');
        
        foreach ($_REQUEST['data'] as $item)
            if (isset($item['lang']) && isset($item['descriptor'])) {
                Conn::query("UPDATE dynamic_fields_".goodString($item['lang'])." SET enumCategory = ".goodInput($_REQUEST['enumCategory']).", descriptor = ".goodInput($item['descriptor']).", expression = ".goodInput($_REQUEST['exp'])." WHERE id = ".goodInt($_REQUEST['id'])." LIMIT 1");
            }
        
        //var_dump($_REQUEST);
        
        break;
    case 'newDictionary': 
        if (!isset($_REQUEST['exp'])) die();
        
        $ret = array();
        
        $maxID = Conn::queryObject("SELECT MAX(id) as id FROM dynamic_fields_en");
        $maxID = ((int)$maxID->id)+1;
        
        foreach ($_REQUEST['data'] as $item) {
            if (isset($item['lang']) && isset($item['descriptor'])) {
                Conn::query("INSERT INTO dynamic_fields_".goodString($item['lang'])." (id, expression, descriptor, enumCategory) VALUES (".$maxID.", ".goodInput($_REQUEST['exp']).", ".goodInput($item['descriptor']).", ".goodInput($_REQUEST['enumCategory']).")");
                $ret['newID'] = Conn::getId();
            }
        }
        
        echo json_encode($ret);
        
        break;
    default: echo "No such identifier: ".$ajax; break;
}

Conn::closeStaticHandle();
?>