<?php

if (isset($_REQUEST['id'])) {
    $goodID = goodInt($_REQUEST['id']);
    $ret = Conn::queryArray("
        SELECT
            pkExpressionPrimary as id, strExpression as expression,
            enumCategory, sublang.strExample AS example
        FROM expression_primary prim
        LEFT JOIN expression_data sublang
        	ON sublang.fkExpressionPrimary = prim.pkExpressionPrimary
        WHERE strLanguageISO6391 = '".goodString($_REQUEST['lang'])."'
        AND   pkExpressionPrimary = ".$goodID);
    
    if (!isset($_REQUEST['disableTableGen']) || $_REQUEST['disableTableGen'] != 'true') {
        $ret = getTableForElement($ret, $goodID, $_REQUEST);
    }

    echo json_encode($ret);
} else {
    if (standardAssertMessage(array('search', 'lang'), $_REQUEST)) die();
    
    $ret = Conn::queryArrays("
        SELECT
            pkExpressionPrimary AS id, strExpression AS expression,
            enumCategory, enumDeleted, sublang.strExample AS example
        FROM expression_primary prim
        LEFT JOIN expression_data sublang
        	ON sublang.fkExpressionPrimary = prim.pkExpressionPrimary
        WHERE enumDeleted = 'N'
        AND   strLanguageISO6391 = '".goodString($_REQUEST['lang'])."'
        AND   (strExpression LIKE '%".goodString($_REQUEST['search'])."%' OR sublang.strExample LIKE '%".goodString($_REQUEST['search'])."%')
        ORDER BY expression");
    
    echo json_encode($ret);
}

?>
