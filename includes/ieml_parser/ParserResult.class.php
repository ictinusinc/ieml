<?php

class ParserResult {
	private $AST, $exception, $error, $state;
	
	public function __construct() {
		$this->AST = NULL;
		$this->exception = NULL;
		$this->state = -1;
	}
	
	public function AST(IEMLASTNode $AST = NULL) {
		if (isset($AST)) {
			$this->AST = $AST;
			$this->state = 0;
			
			return $this;
		} else {
			return $this->AST;
		}
	}
	
	public function except(ParseException $exception = NULL) {
		if (isset($exception)) {
			$this->exception = $exception;
			$this->state = 1;
			
			return $this;
		} else {
			return $this->exception;
		}
	}
	
	public function error(Exception $error = NULL) {
		if (isset($error)) {
			$this->error = $error;
			$this->state = 2;
			
			return $this;
		} else {
			return $this->error;
		}
	}

	
	public static function fromAST(IEMLASTNode $AST) {
		$inst = new ParserResult();
		
		$inst->AST($AST);
		
		return $inst;
	}
	
	public static function fromException(ParseException $exception) {
		$inst = new ParserResult();
		
		$inst->except($exception);
		
		return $inst;
	}
	
	public static function fromError(Exception $error) {
		$inst = new ParserResult();
		
		$inst->error($error);
		
		return $inst;
	}
	
	public function hasException() {
		return $this->state === 1;
	}
	
	public function hasError() {
		return $this->state === 2;
	}
	
	public function hasSucceeded() {
		return $this->state === 0;
	}
}

?>
