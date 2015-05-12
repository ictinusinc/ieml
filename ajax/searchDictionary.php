<?php

$basic_filter = '';
$join_str = '';

if (array_key_exists('search', $req) && strlen($req['search']) > 0) {
	$goodSearch = goodString($req['search']);

	$basic_filter .= " AND (strExpression LIKE '%" . $goodSearch . "%' OR sublang.strExample LIKE '%" . $goodSearch . "%') ";
}
if (array_key_exists('layer', $req) && strlen($req['layer']) > 0) {
	$basic_filter .= " AND intLayer = ".goodInt($req['layer'])." ";
}
if (array_key_exists('class', $req) && strlen($req['class']) > 0) {
	$basic_filter .= " AND enumClass = '".goodString($req['class'])."' ";
}
if (array_key_exists('keys', $req) && strlen($req['keys']) > 0 && $req['keys'] == 'keys') {
	$basic_filter .= " AND enumCategory = 'Y' ";
}
$basic_filter .= " AND ltoep.fkLibrary = ".goodInt($req['library'])." ";

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
		) AS fkLibrary,
		(
			CASE enumVisual
				WHEN 'Y' THEN 'relational'
				ELSE 'basic'
			END
		) AS enumExpressionType
	FROM expression_primary prim
	JOIN expression_data sublang
		ON sublang.fkExpressionPrimary = prim.pkExpressionPrimary
	JOIN library_to_expression ltoep
		ON prim.pkExpressionPrimary = ltoep.fkExpressionPrimary
	WHERE prim.enumDeleted = 'N'
	AND strLanguageISO6391 = '".goodString($req['lang'])."'
	".$basic_filter."
");

usort($ret, 'expression_sort_cmp');

for ($i = 0; $i < count($ret); $i++) {
	$ret[$i]['fkLibrary'] = explode(',', $ret[$i]['fkLibrary']);
}

$request_ret = $ret;
