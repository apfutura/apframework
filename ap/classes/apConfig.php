<?php
class apConfig
{
	static protected $_configObject = null;

	protected $_db;
	
	protected $_configTable = 'configuration';
	protected $_config;
	protected $_user;

	public function __construct() {
  		$this->_db = apDatabase::getDatabaseLink();
  		/* TODO load all settings at once
  		$config = unserialize($_SESSION['config']);
		if (strlen($config)==0) { // Carreguem tot
		$query ="SELECT * FROM ".$this->_configTable." ORDER BY var_group";
		$results = $this->_db->query($query);
		$refGroup = null;		      		
		foreach ($results as $result) {
			if (trim($result["var_group"])!= $refGroup) $refGroup=trim($result["var_group"]);
			$this->_$config[$refGroup][trim($result["var_name"])] = trim($result["var_value"]);
		}			
		$_SESSION['apexConfig'] = serialize($this->_$config);
		*/
	}
	
	/* TODO methods to use SESSION values loads all at once
	 protected getConfigValue($varKey, $varGroup = null, $varDefaultValue = null ) {
	 	$config = unserialize($_SESSION['config']);
		if (strlen($config)==0) { 
			return null;
			} else {
				return = $config[$varKey][$varGroup];
			}
	}  
	 */
	
	protected static function getInstance() {
		if (self::$_configObject === null) {
			self::$_configObject= new self();
		}
		return  self::$_configObject;
	}
	
	protected function setConfigValue($varKey, $varValue, $varGroup = null ) {
		$str_where_group = ($varGroup == null?'':" AND var_group='".$varGroup."'");
		if ($this->_db->getFieldValueEx($this->_configTable,"var_name='".$varKey."'".$str_where_group,'var_name')==null) {			
			$sql = 'INSERT INTO ' . $this->_configTable . "(var_name,var_value,".($varGroup==null?'':'var_group,')."created_on,created_by) VALUES ('".$varKey."','".$varValue."',".($varGroup==null?'':"'".$varGroup."',")."now(),'".$this->_user."');";
		} else {
			$str_where_group = ($varGroup == null?'':" AND var_group='".$varGroup."'");
			$sql = 'UPDATE ' . $this->_configTable . " SET var_value = '".$varValue."' WHERE var_name='".$varKey."'".$str_where_group.';';
		}	
		//echo $sql;
		apCache::save($varKey . $varGroup."_value", $varValue, 10);
		
		return $this->_db->exec($sql);;
	}
	
	protected function getConfigValue($varKey, $varGroup = null, $varDefaultValue = null, $autoCreate = false ) {
		$valueCache = apCache::load($varKey . $varGroup."_value");
		if ($valueCache==false) {
			$str_where_group = ($varGroup == null?'':" AND var_group='".$varGroup."'");
			$value = $this->_db->getFieldValueEx($this->_configTable,"var_name='".$varKey."'".$str_where_group,'var_name');
			if ($value == null) { 
				apLog::sendWarning("GetConfig", "Configuration Key: $varKey / Group: $varGroup is empty");
				if ($autoCreate == true) {
					$sql = 'INSERT INTO ' . $this->_configTable . "(var_name,var_value,".($varGroup==null?'':'var_group,')."created_on,created_by) VALUES ('".$varKey."','".$varDefaultValue."',".($varGroup==null?'':"'".$varGroup."',")."now(),'".$this->_user."');";
					$this->_db->exec($sql);;
					apLog::sendWarning("GetConfig", "setup.php 'autocreateConfigOptions' is set to 'true'. Creating Configuration Key: $varKey / Group: $varGroup with value: $varDefaultValue");
				}
				if ($varDefaultValue!=null) {
					$value = $varDefaultValue;
				}
			} else {
				$value = $this->_db->getFieldValueEx($this->_configTable,"var_name='".$varKey."'".$str_where_group,'var_value');
			}		
			apCache::save($varKey . $varGroup."_value", $value, 10);
		} else {
			$value = $valueCache;
		}
		return $value;
	}

	protected function getDescription($varKey, $varGroup = null) {
		$str_where_group = ($varGroup == null?'':" AND var_group='".$varGroup."'");
		$value = $this->_db->getFieldValueEx($this->_configTable,"var_name='".$varKey."'".$str_where_group,'var_desc');
		return $value;
	}
	
	
	protected function getConfigGroupValues($varGroup) {
		$valueCache = apCache::load($varGroup."_values");
		if ($valueCache==false) {
			$sql = sprintf('SELECT var_name, var_value FROM ' . $this->_configTable . " WHERE var_group='%s'"/* ORDER BY var_name"*/,$varGroup);
			echo $sql;
			$return  = array();
			$result = $this->_db->query($sql);
			foreach ($result as $row) {
				$return[$row['var_name']]=$row['var_value'];
			}
			apCache::save( $varGroup."_values", $return, 10);
		} else {
			$return = $valueCache;
		}
		return $return;
	}
	
	protected function removeConfigValue($varKey, $varGroup=null) {
		$str_where_group = ($varGroup == null?' AND var_group IS NULL':" AND var_group='".$varGroup."'");
		$sql = 'DELETE FROM ' . $this->_configTable . " WHERE var_name='".$varKey."'".$str_where_group.';';
		return $this->_db->exec($sql);
	}
	
    public static function get($varKey, $varGroup = null, $varDefaultValue = null, $autoCreate = null ) {
    	$instance = self::getInstance();
    	if ($autoCreate==null) {
    		$autoCreate = (isset(CONFIG::$autocreateConfigOptions)?CONFIG::$autocreateConfigOptions:false);
    	}    	
    	$result = $instance->getConfigValue($varKey, $varGroup, $varDefaultValue, $autoCreate);
        return $result;
    }
    
    public static function get_description($varKey, $varGroup = null) {
    	$instance = self::getInstance();
    	$result = $instance->getDescription($varKey, $varGroup);
    	return $result;
    }
    
    
	static function getGroup($varGroup) {
    	$instance = self::getInstance();
    	$result = $instance->getConfigGroupValues($varGroup);
        return $result;
    }
    
    static function set($varKey,$varValue, $varGroup = null) {
    	$instance = self::getInstance();
    	$result = $instance->setConfigValue($varKey,$varValue, $varGroup);
        return $result;
    }
   
    static function remove($varKey, $varGroup = null) {
    	$instance = self::getInstance();
    	$result = $instance->removeConfigValue($varKey, $varGroup);
        return $result;
    }
    
    static function setUsername($userName) {
    	$instance = self::getInstance();
    	$instance->_user = $userName;
    }
}