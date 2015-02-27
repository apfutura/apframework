<?php
class index extends apControllerTask {

	function get() {
		$params = array();
		$this->renderApTemplate("index.html", $params);
	}
	
	function _testAjax1() {				
		$this->responseAjaxData(true,array(),"test ajax OK ".time() );
	}
	
	function _testAjax2() {	
		include (constant('_GLOBAL_APPMODEL_DIR').'exampleClass.php');
		$example = new exampleClass();
		$this->responseAjaxData(true,array(), $example->sayHello("apFrameeeeework") );
	}
	
	function xx() {
	    include (constant('_GLOBAL_MODEL_DIR').'apDatabaseTriggers.php');
	    apDatabaseTriggers::processAction('UPDATE','TEST','1a');
	}
	
}