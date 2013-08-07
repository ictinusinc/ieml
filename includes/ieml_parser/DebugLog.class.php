<?php

class Debug {
	private static
		$ERROR = 0,
		$WARNING = 1,
		$NOTICE = 2,
		$INFO = 4;
	
	public static $output = NULL;
	
	private static function level_to_name($level) {
		$str = '';
		
		switch($level) {
			case Debug::$ERROR:
				$str = 'ERROR';
				break;
			case Debug::$WARNING:
				$str = 'WARNING';
				break;
			case Debug::$NOTICE:
				$str = 'NOTICE';
				break;
			case Debug::$INFO:
				$str = 'INFO';
				break;
			default:
				$str = '';
		}
		
		return $str;
	}
	
	public static function output_stream($resource) {
		Debug::$output = $resource;
	}
	
	public static function Log($level, $msg) {
		if (isset(Debug::$output)) {
			fwrite(Debug::$output, '['.Debug::level_to_name($level).'] '.$msg);
		}
	}
	
	public static function LogError($msg) {
		Debug::Log(Debug::$ERROR, $msg);
	}
	
	public static function LogWarning($msg) {
		Debug::Log(Debug::$WARNING, $msg);
	}
	
	public static function LogNotice($msg) {
		Debug::Log(Debug::$NOTICE, $msg);
	}
	
	public static function LogInfo($msg) {
		Debug::Log(Debug::$INFO, $msg);
	}
}

Debug::$output = fopen('php://stdout', 'w');

?>
