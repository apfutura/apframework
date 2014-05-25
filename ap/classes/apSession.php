<?php
class apSession {
	static protected $currentUser = false;
	static protected $userSessionVariable = 'currentUser';

	static function login($userName, $password) { //false or user
		$userClass = (!isset(CONFIG::$userClass)?'apUser':CONFIG::$userClass);
		$apUser = new $userClass();
		$response = $apUser->login($userName, $password);
		if ($response!==false) {
			//Save user
			self::$currentUser=$apUser;
			self::setToSession(self::$userSessionVariable, $apUser);
			self::setToSession(self::$userSessionVariable."_loginName", $userName);
			return true;
		} else {
			return false;
		}
	}
	
	static function logout() {
		self::$currentUser=null;
		// Set to $_SESSION
		self::setToSession(self::$userSessionVariable, null);
		self::setToSession(self::$userSessionVariable."_loginName", null);
	}

	static function getCurrentUser() {
		if (self::$currentUser != null) { 	// Get from class
			return self::$currentUser;
		} else { 							// Get from $_SESSION
			$user = self::getFromSession(self::$userSessionVariable, true);
			if ($user != null) {
				self::$currentUser = $user;
				return $user;
			}
		}
		return null;
	}
	
	static function validate($userName = null) {		
		if (self::getCurrentUser() != null) {			
			if ($userName == null) { // Validate if there is a user logged in 
				return true;
			} else {
				$loginName  = self::getFromSession(self::$userSessionVariable."_loginName", null);
				if ($loginName == $user) {
					return true;
				} else {
					return false;
				}
			}
		} else {
			return false;
		}
	}
	
	static public function setToSession($var, $value) {
		if ( !isset( $_SESSION ) ) session_start();
		if ( is_object($value) ) {
			$value = serialize($value);
		}
		$_SESSION[$var] = $value;
	}

	static public function unsetFromSession($var) {
		if ( !isset( $_SESSION ) ) session_start();
		unset($_SESSION[$var]);
	}
	
	static public function issetInSession($var) {
		if ( !isset( $_SESSION ) ) session_start();
		return isset($_SESSION[$var]);
	}
	
	static public function getFromSession($var, $unserialize = false, $defaultValue = null) {
		if ( !isset( $_SESSION ) ) session_start();
		if ( !empty($_SESSION[$var]) ) {
			$value = $_SESSION[$var];
			if ($unserialize) {
				return unserialize($value);
			} else {
				return $value;
			}
		}
		return $defaultValue;
	}
}