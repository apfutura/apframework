<?php
// requires config
// requires apDatabase

include_once(constant('_GLOBAL_MODEL_DIR') . "apApplication.php" );

class apBaseSQLList {

	protected $_totalElements = 0;
	protected $_elementsList = array();
	protected $_elementsListLoaded = false;
	protected $_db;
	protected $_idField;
	protected $_orderField;
	protected $_sqlQuery;
	public $throwExceptionOnDuplicatedId = true;
	public $throwExceptionOnLoadError = true;
	public $fetchType = PDO::FETCH_ASSOC;

	public function __construct($sqlQuery, $idKeyField = "id", $orderField = null) {
		$this->_sqlQuery = $sqlQuery;
		$this->_idField=$idKeyField;
		$this->_orderField=$orderField;
		$this->_db = apDatabase::getDatabaseLink();
	}

	public function getList() {
		if (!$this->_elementsListLoaded ) $this->_loadElements();
		return $this->_elementsList;
	}

	protected function _loadElements() {
		$this->_elementsList = array();

		$SQL = sprintf ("SELECT * FROM ( ".$this->_sqlQuery.") as xx ORDER BY xx.".$this->getOrderField());
		//echo $SQL; die();
		$result = $this->_db->query($SQL, $this->fetchType);
		if ($result!==false) {
			foreach ($result as $entity) {
				$this->_elementsList[$entity[$this->_idField]]=$entity;
			}
		}	else {
			if ($this->throwExceptionOnLoadError) throw new Exception(get_class($this) . " getList (apBaseSQLList) SQL error: " . $this->_db->lastError);
		}
		$this->_elementsListLoaded = true;
	}

	public function getPartialList($offset = null, $limit = null, $fieldOrderBy = null, $filterAssociativeArray = array()) {
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
			$arrayConditions[] = "xx.".$field . " = '".$value."'";
		}

		if (count($arrayConditions)>0){
			$sqlWhere = " WHERE ".implode(' AND ',$arrayConditions);
		} else {
			$sqlWhere = '';
		}

		if (is_null($fieldOrderBy) ) {
			$fieldOrderBy = $this->getOrderField();
		}
		$sqlOrderBy = " ORDER BY xx." . $fieldOrderBy;

		$SQL = "SELECT * FROM ( ".$this->_sqlQuery.") as xx ". $sqlWhere . $sqlOrderBy . $sqlLimit;
		$SQLCount = "SELECT count(*) as total FROM ( ".$this->_sqlQuery.") as xx ". $sqlWhere;
		$result = $this->_db->query($SQL,PDO::FETCH_OBJ);
		$resultCount = $this->_db->query($SQLCount,PDO::FETCH_OBJ);
		$_elementsList = array();
		if ($result!==false) {
			foreach ($result as $entity) {
				if ($this->throwExceptionOnDuplicatedId && array_key_exists($entity->{$this->_idField}, $_elementsList)) {
					throw new Exception(get_class($this) . " getPartialList (apBaseSQLList) found duplicated id values. Id key = '".$this->_idField."' duplicated value = '".$entity->{$this->_idField}."'");
				}
				$_elementsList[$entity->{$this->_idField}]=$entity;
			}
			$this->_totalElements  = $resultCount[0]->total;
		} else {
			throw new Exception(get_class($this) . " getPartialList (apBaseSQLList) SQL error:\n\n".$this->_db->getLastErrorMessage());
		}
		return $_elementsList;
	}

	public function getElement($id) {
		if ($id==null) return null;
		$SQL = "SELECT * FROM ( ".$this->_sqlQuery.") as xx  WHERE xx." .$this->_idField . "= ?";
		
		$rows = null;
		$result = $this->_db->execute($SQL, array($id), $rows, PDO::FETCH_OBJ);		
		if ($result!==false) {
			return $rows[0];
		} else return false;		
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

}
