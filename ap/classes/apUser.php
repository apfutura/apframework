<?php
class apUser {
	
	public $username = null;
	protected $userData = null;
	
	protected $_db = null;
	protected $_userGroupsLoaded = false;
	protected $_userGroups = array();
	
	function __construct(){
		$this->_db = apDatabase::getDatabaseLink();
	}
	
	function login($uname, $upassword){
		$uname    = $this->_db->escape($uname);
		$password = $this->_db->escape($upassword);

		$sql = "SELECT * FROM users WHERE username = ? AND password = MD5(?) LIMIT 1";
		 
		$res = array();
		$result = $this->_db->execute($sql,array($uname,$password),$res,PDO::FETCH_BOTH);

		if (!$result || (count($res)==0 ) ) {
			return false;
		}
		$res = $res[0];

		$this->userData = $res;
		$this->username = $res['username'];
		return true;
	}
	
	function getGroups() {
		if (!$this->_userGroupsLoaded) $this->_loadGroups();
		return $this->_userGroups;
	}
	
	function _loadGroups() {
		$sql = "SELECT * FROM user_groups WHERE username = ?";
			
		$res = array();
		$result = $this->_db->execute($sql,array($this->username),$res,PDO::FETCH_ASSOC);
		
		if (!$result || (count($res)==0 ) ) {
			return false;
		}
		$this->_userGroupsLoaded =  true;
		$res = $res[0];
		$this->_userGroups = array_values($res);
		return true;		
	}
	
	function __toString() {
		return $this->username;
	}
}