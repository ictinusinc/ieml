<?php

if (isset($_REQUEST['id'])) {
    $goodID = goodInt($_REQUEST['id']);
    $ret = Conn::queryArray("
        SELECT
            pkExpressionPrimary as id, strExpression as expression,
            enumCategory, sublang.strDescriptor AS descriptor
        FROM expression_primary prim
        LEFT JOIN expression_descriptors sublang
        	ON sublang.fkExpressionPrimary = prim.pkExpressionPrimary
        WHERE strLanguageISO6391 = ".goodInput($_REQUEST['lang'])."
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
            enumCategory, enumDeleted, sublang.strDescriptor AS descriptor
        FROM expression_primary prim
        LEFT JOIN expression_descriptors sublang
        	ON sublang.fkExpressionPrimary = prim.pkExpressionPrimary
        WHERE enumDeleted = 'N'
        AND   strLanguageISO6391 = ".goodInput($_REQUEST['lang'])."
        AND   (strExpression LIKE '%".goodString($_REQUEST['search'])."%' OR sublang.strDescriptor LIKE '%".goodString($_REQUEST['search'])."%')
        ORDER BY expression");
    
    echo json_encode($ret);
}

?>
