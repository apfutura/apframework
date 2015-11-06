<?php
include_once "apUtils.php";

class apDbPostgresql { 

	protected static $database;	
	protected static $server;
	protected static $user;
	protected static $password;

	public static $logToApache = false;
	public static $dblink;
	public $lastError="init";

	function connect ( $server,
				$database,
				$user,
				$password,
				$port = 5432 ) {
		//UTILS::utils_setMicroTimeSQLStart();	
		self::$server = $server;
		self::$database = $database;
		self::$user = $user;
		self::$password = $password;
		$cString = "pgsql:host=".$server.";port=".$port.";dbname=".$database.";user=".$user.";password=".$password;
		try {
			self::$dblink = new NestedPDO($cString);        
			self::$dblink->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
			$this->lastError = "";
			//UTILS::utils_setMicroTimeSQLEnd("connect... ");
			return true;
		} catch(PDOException $e) {
			$err = new pdoDbException($e);
			$this->lastError = $err;	    	
			//UTILS::utils_setMicroTimeSQLEnd("connect... ");
			return false;
		}
	}

	function beginTransaction() {
		self::$dblink->beginTransaction();
	}
	

	function quote($string) {
		return self::$dblink->quote($string);
	}
	
	function commit() {
		self::$dblink->commit();
	}
	
	function rollBack() {
			self::$dblink->rollBack();
	}
		
	function query($SQL,$mode = PDO::FETCH_ASSOC) {		 	
		ini_set('memory_limit', '-1');
		//UTILS::utils_setMicroTimeSQLStart();
		$this->logQuery($SQL);				
		
		try {
			$stmt = self::$dblink->query($SQL);  
			$stmt->setFetchMode($mode);   		
			$result = $stmt->fetchAll($mode);
		} catch(PDOException $e) {
			$err = new pdoDbException($e);
			$this->lastError = $err->getMessage();			
			$result = false;
		}
		//UTILS::utils_setMicroTimeSQLEnd("query: ".$SQL);		
		return $result;
	}
	
	function execute($SQL,$array=array(),&$rows=null,$mode = PDO::FETCH_ASSOC ) {		
		//UTILS::utils_setMicroTimeSQLStart();	
		try {
					
			$this->logQuery($SQL);
			$sth = self::$dblink->prepare($SQL);
			
			if (empty($array)) { 
				$r = $sth->execute();
			} else  { 
				$r = $sth->execute($array);
			}
			
			if ( ($r==true) ) {
					$sth->setFetchMode($mode);
					$rows = $sth->fetchAll();
			} 
			
		} catch (PDOException $e) {
			$err = new pdoDbException($e);
			$r=false;
			$this->lastError = $err;
		}					
		//UTILS::utils_setMicroTimeSQLEnd("execute: ". $SQL);
		return $r;
	}
	
	function exec($SQL, $array=array()) {			
		//UTILS::utils_setMicroTimeSQLStart();    
		try {     		
			$this->logQuery($SQL);
			//$dbh = $this->dblink->prepare($SQL); //sentencia nova per preparar la sentencia amb l'array
			self::$dblink->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);	
			$count = self::$dblink->exec($SQL);  
		} catch (PDOException $e) {
			$err = new pdoDbException($e);
			$count=0;
			$this->lastError = $err;
		}		
		//UTILS::utils_setMicroTimeSQLEnd("exec: ".$SQL);
		return $count;
	}
	
	function getFieldValue($table,$fieldId,$fieldIdValue,$fieldToReturnValueFrom, $returnFirstRecordIfMultipleFound = false, $insensitiveLookup = true) {
		if (!$insensitiveLookup) {
			$query ='SELECT '.$fieldToReturnValueFrom.' as "getFieldValue" FROM '.$table .' WHERE '. $fieldId."='".$fieldIdValue."'";
		} else {
			$query ='SELECT '.$fieldToReturnValueFrom.' as "getFieldValue" FROM '.$table .' WHERE '. $fieldId."::varchar ilike '".$fieldIdValue."'";
		}		
		//echo  $query;
		$value=$this->query($query);
		$return = null;		      		
		if ( ($value) /* array is no empty */ || (count($value) > 1  && $returnFirstRecordIfMultipleFound) ) {
			$return = $value[0]['getFieldValue'];
		}
	 	return $return;
	}
	
	function getFieldsValue($table,$fieldId,$fieldIdValue,$fieldToReturnValueFrom, $returnFirstRecordIfMultipleFound = false, $insensitiveLookup = true) {
		if (!$insensitiveLookup) {
			$query ='SELECT '.implode(",",$fieldToReturnValueFrom).' FROM '.$table .' WHERE '. $fieldId."='".$fieldIdValue."'";
		} else {			
			$query ='SELECT '.implode(",",$fieldToReturnValueFrom).' FROM '.$table .' WHERE '. $fieldId." ilike '".$fieldIdValue."'";
		}
		$value=$this->query($query);
		$return = null;
				
		if ( ($value) /* array is no empty */ || (count($value) > 1  && $returnFirstRecordIfMultipleFound) ) {
			$return = array_values($value[0]);			
		}
		return $return;
	}
	
	function getLastErrorMessage() {
		$message = "";
		if (is_callable(array($this->lastError, "getMessage"))) {
			$message = $this->lastError->getMessage();
		}		
		return  $message;
	}
	
	function getFieldsValueEx($table,$where, $fields, $returnFirstRecordIfMultipleFound = false) {
		$query ='SELECT '.implode(",",$fields).' FROM '.$table ." WHERE ". $where;
		$value=$this->query($query);
		$return = null;		     
		 		
		if ( ($value) /* array is no empty */ || (count($value) > 1  && $returnFirstRecordIfMultipleFound) ) {
			$return = $value[0];			
		}
 		return $return;
	}
	
	function getFieldValueEx($table,$where,$expressionToReturnValueFrom,$fieldToReturnValueFrom=null, $returnFirstRecordIfMultipleFound = false) {
	    $query ="SELECT ".$expressionToReturnValueFrom." FROM ".$table ." WHERE ". $where;
	    $value=$this->query($query);
	    $return = null;
	    if ( ($value) /* array is no empty */ || (count($value) > 1  && $returnFirstRecordIfMultipleFound) )  {
	        if ($fieldToReturnValueFrom!=null) {
	            $return = trim($value[0][$fieldToReturnValueFrom]);
	        } else {
	            $return = trim($value[0][$expressionToReturnValueFrom]);
	        }
	    }
	    return $return;
	}
	
	function lastInsertId($column) {
		return self::$dblink->lastInsertId($column);  
	}	
	
	function escape($str) {
		$str = get_magic_quotes_gpc()?stripslashes($str):$str;
		return $str;
	}
	
	protected function logQuery($SQL){
		
		if (self::$logToApache) {
			$backtrace = debug_backtrace();
			$callingMethod = '';
			if (!empty($backtrace[5])) $callingMethod .= $backtrace[5]['file'].":".$backtrace[5]['function']."() ";
			if (!empty($backtrace[4])) $callingMethod .= $backtrace[4]['file'].":".$backtrace[4]['function']."() ";
			if (!empty($backtrace[3])) $callingMethod .= $backtrace[3]['file'].":".$backtrace[3]['function']."() ";
			if (!empty($backtrace[2])) $callingMethod .= $backtrace[2]['file'].":".$backtrace[2]['function']."() ";
			apUtils\logToApache( self::$database . " (". self::$user."): ". $SQL. "\n***********".$callingMethod."***********\n\n");
		}
		
	}
}



class apDbMysql { 
	protected static $database;	
	protected static $server;
	protected static $user;
	protected static $password;
	protected static $dblink;

	function connect ( $server,
				$database,
				$user,
				$password ) {
		$this->server = $server;
		$this->database = $database;
		$this->user = $user;
		$this->password = $password;
		$this->dblink = mysql_connect($server,$user,$password);        
	}	
    
	function query($SQL) {
		$user =  $this->user;
		$pass =  $this->password;
		$db =	 $this->database;
		$server =$this->server;						
		$link = ($this->dblink=='')? mysql_connect($server,$user,$pass):$this->dblink;	    		
		if (!$link) {
	   	die(L_ERROR_DB.'error 1 !--' . mysql_error($link).'--');
			}
		$db_selected = mysql_select_db($db,$link);
		
		if (!$db_selected) {
	   	die (L_ERROR_DB.'error 2 !--' . mysql_error().'--');
			}
		$result = mysql_query($SQL,$link);
		//echo "<!-- Sql:" .$SQL." --><BR>";
	  
		return $result;
	}
	
	function execute($sql,$params) {
		global $user,$pass,$db,$server;
		$mysqli = new mysqli($server, $user, $pass, $db); 
		 
		if (mysqli_connect_errno()) { 
		    printf(L_ERROR_DB.'error 1 !--' . mysql_error().'--'); 
		    exit(); 
		} 
		
		
		$stmt = $mysqli->prepare($sql);
		
		foreach ($params as $valor=> $tipus) { 
			echo "--a->".$tipus."<----";
			echo "--b->".$valor."<----";
			$stmt->bind_param($t, $v);
			$t = $tipus;
			$v = $valor; 
		}
		
		$stmt->execute(); 
		
		$result = $stmt->affected_rows; 
		 
		echo "Err:".$stmt->error;
		$stmt->close();
		
		return $result; 
	}
	    
	function escape($str) {
		$str = get_magic_quotes_gpc()?stripslashes($str):$str;
		$str = mysql_real_escape_string($str, $this->dbConn);
		return $str;
	}    
}

class pdoDbException extends PDOException {

	public function __construct(PDOException $e) {
		if(strstr($e->getMessage(), 'SQLSTATE[')) {
			//preg_match('/SQLSTATE\[(\w+)\] \[(\w+)\] (.*)/', $e->getMessage(), $matches);
			//$this->code = ($matches[1] == 'HT000' ? $matches[2] : $matches[1]);
			//$this->message = $matches[3];
			$this->message = $e->getMessage();
		}
	}
}

class NestedPDO extends PDO {
	// Database drivers that support SAVEPOINTs.
	protected static $savepointTransactions = array("pgsql", "mysql");

	// The current transaction level.
	protected $transLevel = 0;

	protected function nestable() {
		return in_array($this->getAttribute(PDO::ATTR_DRIVER_NAME),
		self::$savepointTransactions);
	}

	public function beginTransaction() {
		if($this->transLevel == 0 || !$this->nestable()) {
			parent::beginTransaction();
		} else {
			$this->exec("SAVEPOINT LEVEL{$this->transLevel}");
		}

		$this->transLevel++;
	}

	public function commit() {
                if ($this->transLevel == 0) {
                    throw new Exception("There is no active transaction (transLevel == 0)");
                }
		$this->transLevel--;
                
		if($this->transLevel == 0 || !$this->nestable()) {
			parent::commit();
		} else {
			$this->exec("RELEASE SAVEPOINT LEVEL{$this->transLevel}");
		}
	}

	public function rollBack() {
                if ($this->transLevel == 0) {
                    throw new Exception("There is no active transaction (transLevel == 0)");
                }
		$this->transLevel--;

		if($this->transLevel == 0 || !$this->nestable()) {
			parent::rollBack();
		} else {
			$this->exec("ROLLBACK TO SAVEPOINT LEVEL{$this->transLevel}");
		}
	}
}