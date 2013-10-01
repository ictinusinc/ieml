<?php

class Devlog {
	private static
		$ERROR = 0,
		$WARNING = 1,
		$NOTICE = 2,
		$INFO = 4;
	
	public static $output = NULL;
	
	private static function level_to_name($level) {
		$str = '';
		
		switch($level) {
			case Devlog::$ERROR:
				$str = 'ERROR';
				break;
			case Devlog::$WARNING:
				$str = 'WARNING';
				break;
			case Devlog::$NOTICE:
				$str = 'NOTICE';
				break;
			case Devlog::$INFO:
				$str = 'INFO';
				break;
			default:
				$str = '';
		}
		
		return $str;
	}
	
	public static function output_stream($resource) {
		Devlog::$output = $resource;
	}
	
	private static function Log($level, $msg, $debug_stack) {
		if (isset(Devlog::$output)) {
			fwrite(Devlog::$output, '['.$debug_stack['function'].':'.$debug_stack['line'].'@'.date('Y-m-d H:i:s').' ('.Devlog::level_to_name($level).')] '.$msg."\n");
		}
	}

	private static function cons_stack() {
		$stack = debug_backtrace();
		$ret = $stack[2];

		if (array_key_exists('line', $ret)) {
			$ret['line'] = $stack[1]['line'];
		}

		return $ret;
	}
	
	public static function e($msg) {
		Devlog::Log(Devlog::$ERROR, $msg, Devlog::cons_stack());
	}
	
	public static function w($msg) {
		Devlog::Log(Devlog::$WARNING, $msg, Devlog::cons_stack());
	}
	
	public static function n($msg) {
		Devlog::Log(Devlog::$NOTICE, $msg, Devlog::cons_stack());
	}
	
	public static function i($msg) {
		Devlog::Log(Devlog::$INFO, $msg, Devlog::cons_stack());
	}
}

?>
