<?php
class apCache
{
	static protected $_cacheObject = null;
	static protected $_enabled = true;
	static protected $_method = "auto";
	static protected $_pathTmp= "/tmp/";

	protected $_path;
	protected $_cacheMethod;
	protected $_filePrefix = 'AC_';

	public function __construct() {
		switch (self::$_method) {
			case "file":
				$this->_cacheMethod = "_file";
				$this->_path = self::$_pathTmp;
				if (!is_dir($this->_path)) {
					throw new Exception("Invalid path '".$this->_path."' for apCache");
				}
				break;
			case "apc":
				$this->_cacheMethod = "_apc";
				if (! (extension_loaded('apc') && ini_get('apc.enabled')) ) {
					throw new Exception("Missing apc PHP extension");
				}
				break;
			case "auto":

				if (! (extension_loaded('apc') && ini_get('apc.enabled')) ) {
					$this->_cacheMethod = "_file";
					$this->_path = self::$_pathTmp;
					if (!is_dir($this->_path)) {
						throw new Exception("Invalid path '".$this->_path."' for apCache");
					}
				} else {
					$this->_cacheMethod = "_apc";
				}
				break;
			default:
				throw new Exception("Invalid cacheMethods in apCache");
				break;
		}
	}

	protected static function getInstance() {
		if (self::$_cacheObject === null) {
			self::$_cacheObject= new self();
		}
		return  self::$_cacheObject;
	}

	protected function get($key) {
		$method = "get".$this->_cacheMethod;
		return $this->$method($key);
	}

	protected function set($key,$value, $ttlBeforeExpiration) {
		$method = "set".$this->_cacheMethod;
		return $this->$method($key,$value, $ttlBeforeExpiration);

	}

	protected function delete($key) {
		$method = "delete".$this->_cacheMethod;
		return $this->$method($key);
	}

	protected function deleteAll() {
		$method = "deleteAll".$this->_cacheMethod;
		return $this->$method();
	}

	protected function get_apc($key) {
		return apc_fetch($this->_filePrefix.$key);
	}

	protected function set_apc($key,$value, $ttlBeforeExpiration) {
		return apc_store($this->_filePrefix.$key, $value,$ttlBeforeExpiration);
	}

	protected function deleteAll_apc() {
		return apc_clear_cache() ;
	}

	protected function delete_apc($key) {
		return apc_delete($this->_filePrefix.$key);
	}

	protected function get_file($key) {

		//TODO ç: Use the following code simplification
		//$file_mtime = @file_mtime($filepath);
		//$file_exists = !!$file_mtime;

		if (!file_exists($this->_path.$this->_filePrefix.$key)) {
			if (file_exists($this->_path.$this->_filePrefix."_serialized_".$key)) {
				$filename = $this->_path.$this->_filePrefix."_serialized_".$key;
				if ( file_exists($filename.".ttl") ) {
					$dateTime = intval(@filemtime($filename));
					$ttl = @file_get_contents($filename.".ttl");
					$time = intval(time());
					$diff = $time - $dateTime;
					//echo "-->". $time .  " - " . $dateTime .  " => " . $diff . " <= " .  $ttl." ? <--";
					if ($diff >= $ttl) {
						@unlink($filename);
						@unlink($filename.".ttl");
						return false;
					}
				}
				return unserialize(@file_get_contents($filename));
			} else {
				$filename = $this->_path.$this->_filePrefix.$key; // TODO: Would it be better simply return false?
			}
		} else {
			$filename = $this->_path.$this->_filePrefix.$key;
		}

		if ( file_exists($filename.".ttl") ) {
			$dateTime = intval(@filemtime($filename));
			$ttl = @file_get_contents($filename.".ttl");
			$time = intval(time());
			$diff = $time - $dateTime;
			//echo "-->". $time .  " - " . $dateTime .  " => " . $diff . " <= " .  $ttl." ? <--";
			if ($diff >= $ttl) {
				@unlink($filename);
				@unlink($filename.".ttl");
				return false;
			}
		}

		return @file_get_contents($filename);
	}

	protected function set_file($key,$value, $ttlBeforeExpiration) {
		if (is_object($value) || is_array($value)) {
			$value = serialize($value);
			$file = $this->_path.$this->_filePrefix."_serialized_".$key;
		} else {
			$file = $this->_path.$this->_filePrefix.$key;
		}
		$operation = @file_put_contents($file, $value);
		if ($operation) {
			@file_put_contents($file.".ttl", $ttlBeforeExpiration);
			return true;
		} else {
			return  false;
		}
	}

	protected function deleteAll_file() {
		$files = dir($this->_path);
		while($entry = $files->read()) {
		 if (($entry!= "." && $entry!= "..") && (substr($entry,0,strlen($this->_filePrefix)) == $this->_filePrefix) ) {
		 	unlink($this->_path.$entry);
		 }
		}
		$files->close();
		return true;
	}

	protected function delete_file($key) {
		return @unlink($this->_path.$this->_filePrefix.$key);
	}

	static function setKeyPrefix($prefix) {
		if (!self::$_enabled) return false;
		$instance = self::getInstance();
		return $instance->_filePrefix = $prefix;
	}

	static function load($keyName) {
		if (!self::$_enabled) return false;
		$instance = self::getInstance();
		return $instance->get($keyName);
	}

	static function save($keyName, $dataToStore, $ttlBeforeExpiration = 3600) {
		if (!self::$_enabled) return false;
		$instance = self::getInstance();
		return $instance->set($keyName, $dataToStore, $ttlBeforeExpiration);
	}

	static function clean() {
		if (!self::$_enabled) return false;
		$instance = self::getInstance();
		$result = $instance->deleteAll();
		//apLog::send(apUser::getCurrentLoggedUserName(), "Clean Cache", ($result?"ok":"err"));
		return $result;
	}

	static function remove($keyName) {
		if (!self::$_enabled) return false;
		$instance = self::getInstance();
		return $instance->delete($keyName);
	}

	static function disable() {
		self::$_enabled = false;
	}

	static function enable() {
		self::$_enabled = true;
	}

	static function getCacheMethod() {
		if (!self::$_enabled) return false;
		$instance = self::getInstance();
		return $instance->_cacheMethod;
	}
}