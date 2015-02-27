<?php
class test extends apControllerTask {

	function get() {
		$params = array();
		
		$connResult = apDatabase::connect();
		$params["dbConnectionResult"] = ($connResult?"ok":"err");
		$params["cacheMethod"] = apCache::getCacheMethod();
		if ($connResult) {
			apConfig::set("time", time());
			$params["apConfig"] = apConfig::get("time");
		}		
		$this->renderApTemplate("test.html", $params);
	}

	function onInit($operation) {
		echo "this wil not be display because renderApTemplate flushes all previous outputs.\
			  If you add <!-- content --> in the template, al the previous output buffer will be printed there";
	}
	
	function onEnd($operation) {
		echo "this is written onEnd";
	}
	
}