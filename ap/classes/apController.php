<?php
// requires "confiugration"
class apController {
	static protected $defaultController = null;
	static protected $loginController = null;
	static protected $defaultOperation = null;	
	static protected $controllers = array();
	static protected $controllersFolder = array();
	static public $currentTask = null;
	static public $currentOperation = null;
	
	static function init($controllersFolder,$defaultController=null, $defaultOperation = null, $loginController = null)	{
		// load avaliable controllers
		$pattern = "*.php";
		$dir = $controllersFolder;
		self::$controllersFolder = $controllersFolder;
		self::$defaultController = $defaultController;
		self::$defaultOperation = $defaultOperation;
		self::$loginController = $loginController;
				
		$controllers = apCache::load("controllers_" .$controllersFolder);
		if ($controllers == false) {
			if ($handle = opendir($dir)) {
				foreach (glob($dir.$pattern) as $file) {
					if ($file != "." && $file != ".." ) {
						$basename = basename($file,".php");
						self::$controllers[] = $basename;
					}
				}
				closedir($handle);
				apCache::save("controllers_" .$controllersFolder, self::$controllers, 120);
			} else {
				throw new Exception("ERROR OPENING CONTROLLERS FOLDER '$dir'");
			}			
		} else {
			self::$controllers = $controllers;
		}
				
	}
	
	static function execute($task, $operation) {
		if ($task == null) {
			$task = self::$defaultController;
		}		
		if ($operation == null) {
			$operation = self::$defaultOperation;
		}
		self::$currentTask = $task;
		self::$currentOperation = $operation;
		if (in_array($task, self::$controllers) || $task == null ) {
			//specific controller
			require_once(self::$controllersFolder  . $task . ".php");
			$className = $task;
			if (method_exists($className,$operation)) {
				ob_start("self::_mainBootstrapBufferCallback");
				$controllerObject = new $className($operation);				
				if ($controllerObject->initRequisitesMeet()) $controllerObject->$operation();
				if (is_callable(array($controllerObject, "afterExecuteOperation"))) {
					$controllerObject->afterExecuteOperation($operation);
				}				
				@ob_end_flush();
			} else {
				if (method_exists($className,"catchAllOperation")) {
					ob_start("self::_mainBootstrapBufferCallback");
					$controllerObject = new $className($operation);
					if ($controllerObject->initRequisitesMeet()) $controllerObject->catchAllOperation($operation);
					if (is_callable(array($controllerObject, "afterExecuteOperation"))) {
						$controllerObject->afterExecuteOperation($operation);
					}
					@ob_end_flush();
				} else {
					throw new exception("NO CONTROLLER CLASS / RUN METHOD:" . $className . " -> " . $operation);
				}
								
			}				 
		} else {
			throw new exception("NO CONTROLLER FILE: " . self::$controllersFolder  . $task . ".php");
		}
	}
	
	static function getLoginController() {
		return self::$loginController;
	}
	
	static function _mainBootstrapBufferCallback($buffer) {
		return $buffer;
	}
	
}