<?php
// requires config
// requires apDatabase

//include_once(constant('_GLOBAL_MODEL_DIR') . "apApplication.php" );

class apBaseElementList {
	
	protected $_elementClass;
	protected $_elementClassContructParam;
	protected $_totalElements = 0;
	protected $_elementsList = array();
	protected $_elementsListLoaded = false;
	protected $_db;
	protected $_dbtable;
	protected $_idField;
	protected $_orderField;
	protected $_elementType;

	public function __construct($elementClass, $DBtable, $idKeyField = "id", $orderField = null) {
		$this->_dbtable = $DBtable;
		$this->_idField=$idKeyField;
		$this->_orderField=$orderField;
		if (is_null($DBtable)) {
			throw new Exception("No DBTable specified in parent::__contruct of ".get_class($this));
		}
		$this->_elementClass=$elementClass;
		if (!in_array("apBaseElement",class_parents($elementClass)) ) {
			throw new Exception("No apBaseElement found as ancestor for elementClass '".$elementClass."' class in parent::__contruct of ".get_class($this));
		}
		$this->_elementType = strtolower(get_class($this));
		$this->_db = apDatabase::getDatabaseLink();
	}
	
	public function getList() {
		if (!$this->_elementsListLoaded ) $this->_loadElements();
		return $this->_elementsList;
	}
	
	protected function _loadElements() {
		$this->_elementsList = array();
	
		$SQL = sprintf ("SELECT * FROM " . $this->_dbtable. " ORDER BY ".$this->getOrderField());
		//echo $SQL; die();
		$result = $this->_db->query($SQL,PDO::FETCH_ASSOC);
		if ($result!=false) {
			foreach ($result as $entity) {
				$tempEntity=new $this->_elementClass;
				$tempEntity->loadFromArray($entity);
				$this->_elementsList[$entity[$this->_idField]]=$tempEntity;
			}
		}
		$this->_elementsListLoaded = true;
	}
	
	public function getPartialList($offset = null, $limit = null, $fieldOrderBy = null, $filterAssociativeArray = array(), $filterWhere = null) {
		$sqlLimit = "";
		$sqlWhere = "";
	
		if (is_numeric($limit) ) {
			$sqlLimit  = " LIMIT " . $limit;
	
		}
		if (is_numeric($offset) ) {
			$sqlLimit  .= " OFFSET " . $offset;
		}
	
		$arrayConditions[] = '1=1';
	
		foreach ($filterAssociativeArray as $field => $value ) {
			$not = '';
			if (strpos($value, '%') === false) {
				if (substr($field, 1) == '!') $not = '!';
				$arrayConditions[] = $field . " $not= '".$value."'";
			} else {
				if (substr($field, 0, 1) == '!') {
					$not = 'NOT';
					$field = ltrim ($field ,'!');
				}
				$arrayConditions[] = $field . " " . $not. " ILIKE '".$value."'";
			}			
		}
		
		if (count($arrayConditions)>0){
			$sqlWhere = " WHERE ".implode(' AND ',$arrayConditions);
		} else {
			$sqlWhere = '';
		}

		if ($filterWhere) {
			$sqlWhere .= ' AND ' . $filterWhere;
		}
		
		if (is_null($fieldOrderBy) ) {
			$fieldOrderBy = $this->getOrderField();
		}
		$sqlOrderBy = " ORDER BY " . $fieldOrderBy;
	
		$SQL = "SELECT * FROM ". $this->_dbtable .$sqlWhere.$sqlOrderBy.$sqlLimit;
		//echo $SQL ; die();
		$SQLCount = "SELECT count(*) as total FROM ".$this->_dbtable.$sqlWhere;
		$result = $this->_db->query($SQL,PDO::FETCH_ASSOC);
		$resultCount = $this->_db->query($SQLCount,PDO::FETCH_OBJ);
		$_elementsList = array();
		if ($result!==false) {
			foreach ($result as $entity) {
			    if ($this->_elementClassContructParam!= null) {
			        $tempEntity=new $this->_elementClass($this->_elementClassContructParam);			        
			    } else {
			        $tempEntity=new $this->_elementClass;			        
			    }
				
				$tempEntity->loadFromArray($entity);
				$_elementsList[$entity[$this->_idField]]=$tempEntity;
			}
			$this->_totalElements  = $resultCount[0]->total;
		} else {
		    throw new Exception('apBaseElementList: getPartialList SQL error executing '. $SQL);
		}
		return $_elementsList;
	}
	
	public function getTableFieldsArray() {
		$tempEntity=new $this->_elementClass;
		return $tempEntity->getTableFieldsArray();
	}
	
	public function getElement($id) {
		if ($id==null) return null;
		$tempEntity=new $this->_elementClass;
		$tempEntity->load($id);
		return $tempEntity;
	}
	
	public function getTotalElements() {
		return $this->_totalElements ;
	}
	
	protected function getOrderField() {
		if (strlen($this->_orderField) == 0) {
			return $this->_idField;
		} else {
			return $this->_orderField;
		}
	}
	
	public function setOrderField($orderField) {
		$this->_orderField = $orderField;
	}
	
	public function setElementClassContructParam($param) {
	    $this->_elementClassContructParam= $param;
	}
	
}