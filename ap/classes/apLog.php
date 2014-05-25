<?php
class apLog {
	
	static protected $_user = null;
	
	static function setUsername($userName) {
		self::$_user = $userName;
	}
	
	static public function send($username,$action,$information,$ip = null, $systemLog = false) {
		$db = apDatabase::getDatabaseLink();
		if (($ip==null) || ($ip==""))  $ip = apUtils\getIP();		
		if ($systemLog) $systemLog = 1; else $systemLog=0;		
		$sql = "INSERT INTO LOG(username,action,information,ip,system) values (?,?,?,?,?)";

	    if ( $db->execute($sql,array ($username,$action,utf8_encode($information),$ip,$systemLog)) ) {
	    	//ok
	    } else {
	    	//err
	    	trigger_error("Could not LOG action " . $action,E_USER_ERROR);
	    }
	}
	
	static public function sendWarning($action, $information,$ip = null) {

		$username = null;
		$action = "Warning: ".$action;
		self::send($username,$action,$information,$ip, true);
	    
	}
	
	static public function log($action, $information) {
		self::send(self::$_user,$action,$information);
	}
	
	static public function get($offset = null, $limit = null) {
		$db = apDatabase::getDatabaseLink();
		if (is_numeric($limit) ) {
			$limit = " LIMIT " . $limit;
			 
		}
		if (is_numeric($offset) ) {
			$limit .= " OFFSET " . $offset;
		}
		return $db->query("SELECT * FROM log".$limit); 
	}
}
