<?php

function IEML_postprocess_table($tab_info, $callback) {
	for ($i=0; $i<count($tab_info['headers']); $i++) {
		for ($j=0; $j<count($tab_info['headers'][$i]); $j++) {
			for ($k=0; $k<count($tab_info['headers'][$i][$j]); $k++) {
				$tab_info['headers'][$i][$j][$k][0] = call_user_func($callback, $tab_info['headers'][$i][$j][$k][0]);
			}
		}
	}
	
	for ($i=0; $i<count($tab_info['body']); $i++) {
		for ($j=0; $j<count($tab_info['body'][$i]); $j++) {
			$tab_info['body'][$i][$j] = call_user_func($callback, $tab_info['body'][$i][$j]);
		}
	}
	
	return $tab_info;
}

?>
