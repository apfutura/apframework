<?php
// requires config
// requires apDatabase

//include_once(constant('_GLOBAL_MODEL_DIR') . "apApplication.php" );

class apBaseElement {
	
	protected $_db;
	protected $_dbtable;
	protected $_dbSequence;
	protected $_idField;	
	protected $_elementType;
	protected $_elementFields;
	protected $_fieldValueCustomFunctions = array();
        protected $_transformStringNullToSQLNull = false;        

	public $lastSQL;
	
	public function __construct($dbTable, $idKeyField = "id", $dbSequence = null) {
		$this->_dbtable = $dbTable;
		$this->_idField = $idKeyField;
		
		if (is_null($dbTable)) {
			throw new Exception("No DBTable specified for ".get_class($this));
		}
		if (is_null($dbSequence)) {
			$this->_dbSequence = $dbTable .'_id_seq';
		} else {
			$this->_dbSequence = $dbSequence;
		}
		$this->_elementType = strtolower(get_class($this));
		$this->_db = apDatabase::getDatabaseLink();
	}

	function getDB() {
		return $this->_db;
	}
	 
	public function toArray($processObjects = true) {
		$arrayData =  $this->processArray(get_object_vars($this),$processObjects);
		return $arrayData;
	}


	private function processArray($array,$processObjects = true ) {
		foreach($array as $key => $value) {
			if ( is_object($value)  && ($processObjects==true)  )  {
				if ( (get_class($value)!='apDbPostgresql') && (get_class($value)!='db') ){
					$array[$key] = $value->toArray();
				} else {
					unset($array[$key]);
				}
			}
			if ( is_array($value)  )  {
				//echo "+".$value."+";
				$array[$key] = $this->processArray($value, $processObjects);
			}
		}
		return $array;
	}

	public function getTableFields(){
		
		$fields = $this->_elementFields;	
		if ( empty($fields) ) {
				$table = $this->getElementTable();
				$SQL = "select column_name from INFORMATION_SCHEMA.COLUMNS where table_name = '".$table."'";
				//echo $SQL ;
				$result = apDatabase::getDatabaseLink()->execute($SQL,null,$table_fields,PDO::FETCH_ASSOC);
				if (!$result) throw new Exception("error getting table columns for class ".get_class($this).": ".apDatabase::getDatabaseLink()->lastError);
				$this->_elementFields = $table_fields;				
		} else {
			$table_fields = $fields;
		}
		
		return $table_fields;
	}

	public function getTableFieldsArray(){
		
		$table_fields = apCache::load($this->_dbtable."_fields");
		if ($table_fields == false)		{
			$actual_table_fields = $this->getTableFields();
			$table_fields = array();
			foreach($actual_table_fields  as $f){
				$fieldName = $f['column_name'];
				$table_fields[] = $fieldName;
			}
			apCache::save($this->_dbtable."_fields", $table_fields, 60);
		} 

		return $table_fields;
	}

	public function __get($var) {
		$fields = $this->getTableFieldsArray();	
		if ( in_array($var, $fields) ) {
			return $this->$var;
		} else {
			if (isset($this->_fieldValueCustomFunctions[$var]) ) {
				if (is_callable($this->_fieldValueCustomFunctions[$var])) {
					return 	$this->_fieldValueCustomFunctions[$var]();
				} else {
					return null;
				}
			} else {
				return null;
			}
		}
    }
    
    public function addFieldValueCustomFunction($field, $function) {
    	$this->_fieldValueCustomFunctions[$field] = $function;
    }

    public function deleteFieldValueCustomFunction($field) {
    	unset($this->_fieldValueCustomFunctions[$field]);
    }
    
	public function insert($data) {
		$ok=false;
		$actual_table_fields = $this->getTableFields(); 
		foreach($actual_table_fields  as $f){
			$fieldName = $f['column_name'];
			if (in_array($fieldName,array_keys($data))) {
				if (($data[$fieldName]!='') ||($data[$fieldName]==null) ) {
					$data_specific[$fieldName] = $data[$fieldName];
				}
			}
		}
		 
		$arrayValues = array_values($data_specific);
		$valuesString = '';
		foreach ($arrayValues as $value) {
			if ( $value==null || (strtolower($value)=="null" && $this->getTranformStringNullToSQLNull()==true) ) {
				$valuesString .= 'null';
			} else if ( is_string($value) ) {
			    $valuesString .= $this->_db->quote($value);
			} else {
			    $valuesString .= $value;
			}
			$valuesString .= ',';
		}
		$valuesString = substr($valuesString , 0, -1);
		 
		$SQL=sprintf("INSERT INTO %s (%s) values(%s)",$this->_dbtable ,implode(',',array_keys($data_specific)), $valuesString );
		echo $SQL."<br>\n";
		
		$affected_rows = $this->_db->exec($SQL);
		//echo $this->_dbtable.":".$affected_rows."<br>";
		if ($affected_rows>0) {
			try {
				$id_specific = $this->getDB()->lastInsertId($this->_dbSequence);
			} catch (Exception $e) {
				$id_specific = $data[$this->_idField];
				throw new exception('Couldnt get la inserted Id: Missing sequence ' . $this->_dbSequence .' and _idField "'.$this->_idField.'" is has not beed passed in');
			}			
			$ok=true;
		}

		if ($ok) {
			$this->load($id_specific);
			return $id_specific;
		} else {

			return false;
		}
	}

	public function getElementTable() {
		return strtolower($this->_dbtable);
	}

	public function getElementIdField() {
		return strtolower($this->_idField);
	}
        
                public function getTranformStringNullToSQLNull() {
            return $this->_transformStringNullToSQLNull;
        }
        
        public function setTranformStringNullToSQLNull($bool = false) {
            $this->_transformStringNullToSQLNull = $bool;
        }
	
	public function update($data, $where=null, $forceUpdateOnAllFields = false, &$affectedFields) {
		$ok=false;
		//Extract "element" data, the rest will be "table specficic" data
		foreach ($data as $k => $d) {
			if ( trim($d) != trim($this->{$k}) || $forceUpdateOnAllFields ) {
				if (($d=='') ||($d==null) || (strtolower($d)=="null" && $this->getTranformStringNullToSQLNull()==true)) {
					$d = "null";
				} else {
					$fType = apDatabase::getFieldType($this->_dbtable,$k );
					if($fType != 'integer'){
						$d = $this->_db->quote($d);
					}					
				}
				$data_specific[$k] = $k ."=". $d;
			}
		}		
		$affectedFields = array_keys($data_specific);
		if (count($data_specific)==0) {
			return true;
		}
		if ($where==null) {
			$idType = apDatabase::getFieldType($this->_dbtable,$this->_idField);
			if($idType == 'integer'){
				$condition = $this->_idField."=%d";
				$where = sprintf($this->_idField."=%d",$this->{$this->_idField});
			} else {
				$where = sprintf($this->_idField."='%s'",$this->{$this->_idField});
			}
		}
		
		$SQL=sprintf("UPDATE %s SET %s WHERE %s",$this->getElementTable(), implode(',',$data_specific), $where);
		$affected_rows = $this->_db->exec($SQL);

		if ($affected_rows==1) {
			$ok=true;
		}

		if ($ok) {
			$this->load($this->{$this->_idField});
			return true;
		} else {
			return false;
		}
	}

	function delete($id = null) {
		$ok=false;
		
		$fType = apDatabase::getFieldType($this->_dbtable,$this->_idField);		
		if ($id==null) {
			if ($this->{$this->_idField}==null) {		
				return false;
			}			
			$id = $this->{$this->_idField};
		}
		
		if($fType == 'integer'){
			$where = sprintf($this->_idField."=%d", $id);
		} else {
			$where = sprintf($this->_idField."='%s'", $id);
		}
		
		$SQL=sprintf("DELETE FROM %s WHERE %s",$this->getElementTable(), $where);		
		$this->lastSQL = $SQL;
		$affected_rows = $this->_db->exec($SQL);
		if ($affected_rows>0) {
			$ok=true;
		}

		if ($ok) {
			if ($affected_rows>1) throw new exception('Warning:' .  $affected_rows .' where deleted for '.$this->getElementTable().' id='.$id);
			return true;
		} else {
			return false;
		}
	}

	function load($id = null, $fields = "*") {
		$ok=false;
		if ($id==null) {
			if ($this->{$this->_idField}==null) {
				return false;
			} else {
				$id = trim($this->{$this->_idField});
			}
		}
		
		$table = $this->getElementTable();
		$field = $this->_idField;
		$fType = apDatabase::getFieldType($table,$field);
		if($fType == 'integer'){
			$condition = $this->_idField."=%d";
		} else {
			$condition = $this->_idField."='%s'";
		}
		
		$SQL=sprintf("SELECT ".$fields." FROM %s WHERE ".$condition,	$table,	$id);
		//echo $SQL;
		$result = $this->getDB()->query($SQL,PDO::FETCH_ASSOC);
		if (count($result)==1) {
			foreach($result[0] as $key => $value) {
				$this->{$key} = $value;
			}
			return true;
		} else {
			return false;
		}
	}
	
	function loadFromArray($valuesArray) {
		$fields = $this->getTableFieldsArray();		
		foreach ($valuesArray as $key => $value) {
			if ( in_array($key, $fields) ) {
				$this->$key = $value;
			} 	
		}
	}
	
	function getValuesArray() {
	    $fields = $this->getTableFieldsArray();
	    $dataArray = array();
	    foreach ($fields as $field) {
	        $dataArray[$field] =  $this->$field;
	    }
	    foreach ($this->_fieldValueCustomFunctions as $customField) {
	        $dataArray[$customField] =  $this->_fieldValueCustomFunctions[$customField]();
	    }	    
	    return $dataArray;
	}
	
}