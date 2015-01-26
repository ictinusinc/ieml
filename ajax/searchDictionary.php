<?php

$filter_str = '';
$join_str = '';

if (array_key_exists('search', $req) && strlen($req['search']) > 0) {
    $filter_str .= " AND (strExpression LIKE '%".goodString($req['search'])."%' OR sublang.strExample LIKE '%".goodString($req['search'])."%') ";
}
if (array_key_exists('layer', $req) && strlen($req['layer']) > 0) {
    $filter_str .= " AND intLayer = ".goodInt($req['layer'])." ";
}
if (array_key_exists('class', $req) && strlen($req['class']) > 0) {
    $filter_str .= " AND enumClass = '".goodString($req['class'])."' ";
}
if (array_key_exists('keys', $req) && strlen($req['keys']) > 0 && $req['keys'] == 'keys') {
    $filter_str .= " AND enumCategory = 'Y' ";
}

$ret = Conn::queryArrays("
    SELECT
        pkExpressionPrimary AS id, strExpression AS expression,
        prim.intSetSize, prim.intLayer, prim.strFullBareString,
        enumCategory, enumDeleted, sublang.strExample AS example,
        (
            SELECT GROUP_CONCAT(fkLibrary SEPARATOR ',')
            FROM library_to_expression
            WHERE fkExpressionPrimary = pkExpressionPrimary
            GROUP BY fkExpressionPrimary
        ) AS fkLibrary
    FROM expression_primary prim
    JOIN expression_data sublang
        ON sublang.fkExpressionPrimary = prim.pkExpressionPrimary
    JOIN library_to_expression ltoep
        ON prim.pkExpressionPrimary = ltoep.fkExpressionPrimary
    WHERE prim.enumDeleted = 'N'
    AND ltoep.fkLibrary = ".goodInt($req['library'])."
    AND strLanguageISO6391 = '".goodString($req['lang'])."'
    ".$filter_str."
    ORDER BY expression");

usort($ret, 'expression_sort_cmp');

for ($i = 0; $i < count($ret); $i++) {
    $ret[$i]['fkLibrary'] = explode(',', $ret[$i]['fkLibrary']);
}

$request_ret = $ret;
