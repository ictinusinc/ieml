<?php

class IEMLScriptGen {
	private static $LAYER_STRINGS = array(':', '.', '-', "'", ',', '_', ';'),
		$VOWELS = array(
			'wo.', 'wa.', 'y.', 'o.', 'e.',
			'wu.', 'we.', 'u.', 'a.', 'i.',
			'j.', 'g.', 's.', 'b.', 't.',
			'h.', 'c.', 'k.', 'm.', 'n.',
			'p.', 'x.', 'd.', 'f.', 'l.'
		), $ATOMS = array(
			'A', 'U', 'S', 'B', 'T',
			'E', 'O', 'M', 'F', 'I'
		);
	
	private $layer, $maxConcats, $maxRelations;
	
	public function __construct($layer, $maxConcats = 1, $maxRelations = 3) {
		//some settings to influence the script instances generated
		
		if ($layer < 0 || $layer > 6) {
			throw new Exception('Argument 1 (layer) given to IEMLScriptGen constructor must satisfy: 0 <= layer <= 6');
		} else {
			$this->layer = $layer;
		}
		
		if ($maxConcats < 1) {
			throw new Exception('Argument 2 (maxConcats) given to IEMLScriptGen constructor must satisfy: 1 <= maxConcats');
		} else {
			$this->maxConcats = $maxConcats;
		}
		
		if ($maxConcats < 1) {
			throw new Exception('Argument 3 (maxRelations) given to IEMLScriptGen constructor must satisfy: 1 <= maxConcats <= 3');
		} else {
			$this->maxRelations = $maxRelations;
		}
		
		return $this;
	}
	
	private function getLowerLayer($top) {
		$script = '';
		
		$concats = rand(1, $this->maxConcats);
		
		for ($i=0; $i<$concats; $i++) {
			$relations = rand(1, $this->maxRelations);
			
			if ($i > 0) {
				$script .= ' + ';
			}
			
			for ($j=0; $j<$relations; $j++) {
				$script .= IEMLScriptGen::staticGenerate($this->layer - 1, $this->maxConcats, $this->maxRelations).IEMLScriptGen::$LAYER_STRINGS[$this->layer];
			}
		}
		
		if ($concats > 1 && !$top) {
			$script = '('.$script.')';
		}
		
		return $script;
	}
	
	private function getLayer1($top) {
		$script = '';
		
		$concats = rand(1, $this->maxConcats);
		
		for ($i=0; $i<$concats; $i++) {
			$relations = rand(1, $this->maxRelations);
			
			if ($i > 0) {
				$script .= ' + ';
			}
			
			for ($j=0; $j<$relations; $j++) {
				$script .= IEMLScriptGen::$VOWELS[rand(0, count(IEMLScriptGen::$VOWELS) - 1)];
			}
		}
		
		if ($concats > 1 && !$top) {
			$script = '('.$script.')';
		}
		
		return $script;
	}
	
	private function getLayer0($top) {
		$script = '';
		
		$concats = rand(1, $this->maxConcats);
				
		for ($i=0; $i<$concats; $i++) {
			$relations = rand(1, $this->maxRelations);
			
			if ($i > 0) {
				$script .= ' + ';
			}
			
			for ($j=0; $j<$relations; $j++) {
				$script .= IEMLScriptGen::$ATOMS[rand(0, count(IEMLScriptGen::$ATOMS) - 1)].IEMLScriptGen::$LAYER_STRINGS[$this->layer];
			}
		}
		
		if ($concats > 1 && !$top) {
			$script = '('.$script.')';
		}
		
		return $script;
	}
	
	public function generate($top = FALSE) {
		$script = '';
		
		if ($this->layer > 1) {
			$script = $this->getLowerLayer($top);
		} else if ($this->layer > 0) {
			if (rand(0,1)) {
				$script = $this->getLowerLayer($top);
			} else {
				$script = $this->getLayer1($top);
			}
		} else {
			$script = $this->getLayer0($top);
		}
		
		return $script;
	}
	
	public static function staticGenerate($layer, $maxConcats, $maxRelations) {
		$instance = new IEMLScriptGen($layer, $maxConcats, $maxRelations);
		return $instance->generate();
	}
}
