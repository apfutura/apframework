<?php
require_once(constant('_GLOBAL_LIB_DIR')."apDb.php" );
class apDatabase
{
	// Store the single instance of Database
	protected static $instance;
	protected $_databaseLink;

	private function __construct($dbconfig = null) {
		$this->_databaseLink = new apDbPostgresql;
		$this->_connect($dbconfig);		
	}
	
	private function _connect($dbconfig){		
		$dbport = (isset(CONFIG::$port)?CONFIG::$port:5432);
		
		if ($dbconfig == null) {
			$dbconfig = array("dbserver" => CONFIG::$server, "dbuser" => CONFIG::$user,"dbpass"=>CONFIG::$pass,"dbname" =>CONFIG::$db, "dbport" =>  $dbport);				
		} 		
		if (!$this->_databaseLink->connect($dbconfig["dbserver"],$dbconfig["dbname"],$dbconfig["dbuser"],$dbconfig["dbpass"], (isset($dbconfig["dbport"])?$dbconfig["dbport"]:5432) ) ) {
			return false;
		} else {
			$db = $this->_databaseLink;
			$db::$logToApache = (isset(CONFIG::$logQueriesToApache)?CONFIG::$logQueriesToApache:false);				
			return true;
		}		
	}
	
	protected static function getInstance($dbconfig = null)
	{
			if (!self::$instance)
			{
				self::$instance = new apDatabase($dbconfig);
			}

			return self::$instance;
	}
	
	public static function connect($dbconfig = null) {
		$instance = self::getInstance($dbconfig);
		if (! $instance->_connect($dbconfig) ) {
			return false;
		} else {
			return true;
		}
	}
	
	public static function getDatabaseLink($dbconfig = null) {
		$instance = self::getInstance($dbconfig);
		return $instance->_databaseLink;
	}
	
	public static function executeFile($sqlfile, $dbconfig = null, $useTransaction = true, $verbose = true) {		
		if ($verbose)  echo "[File:".$sqlfile."]\n<br>";
		$contents = @file_get_contents($sqlfile);		
		if ($contents==false ) {
			if ($verbose)  echo "Error: File $sqlfile not found"."\n<br>";
			return false;
		}		
		$contents = mb_convert_encoding($contents, 'UTF-8', mb_detect_encoding($contents, 'UTF-8, ISO-8859-1', true));
		return self::executeBatch($contents, $dbconfig , $useTransaction , $verbose);		
	}
	
	public static function getTableFields($table, $returnArrayInsideArray = true) {
		// TODO: Change all references to getTableFiels so it can have $returnArrayInsideArray = false as default 
		$db = self::getDatabaseLink();
		$table_fields = array();
		$SQL = "select column_name from INFORMATION_SCHEMA.COLUMNS where table_name = '".$table."' ORDER BY ordinal_position";
		$db->execute($SQL,null,$table_fields,PDO::FETCH_ASSOC);
		if (!$returnArrayInsideArray) {
			$fields = array();
			array_walk($table_fields, function($val)  use(&$fields){
				$fields[] = $val["column_name"];
			});
			return $fields; 
		} else {
			return $table_fields;
		}
	}
	
	public static function getFieldType($table, $field) {
		$valueCache = apCache::load($table .  $field . "_type");
		if ($valueCache  == false) {
			$db = self::getDatabaseLink();
			$sql = "select data_type from INFORMATION_SCHEMA.COLUMNS where table_name = '$table' and column_name = '$field'";
			$result = $db->query($sql);
			$return = $result[0]['data_type'];
			apCache::save( $table .  $field . "_type" , $return, 10);
		} else {
			$return = $valueCache;
		}
		return $return;
	}
	
	public static function getFieldIsAutoincrement($table, $field) {
		$db = self::getDatabaseLink();
		$sql = "select column_default from INFORMATION_SCHEMA.COLUMNS where table_name = '$table' and column_name = '$field'";
		//echo $sql;
		$result = $db->query($sql);
		$default = $result[0]['column_default'];
		if (strpos($default,"nextval")!== false) {
			return true;
		} else {
			return false;	
		}
	}
	
	public static function getKeyFields($table) {
		$db = self::getDatabaseLink();
		$sql = "select ccu.* from INFORMATION_SCHEMA.TABLE_CONSTRAINTS AS tc  NATURAL JOIN INFORMATION_SCHEMA.CONSTRAINT_COLUMN_USAGE AS ccu where tc.table_name = '".$table."' AND tc.constraint_type='PRIMARY KEY'";
		//echo $sql;
		$result = $db->query($sql);
		$keys = array();
		foreach ($result as $row) {
			$keys[] = $row['column_name'];
		}
		return $keys;
	}
	
	public static function executeBatch($sql, $dbconfig = null, $useTransaction = true, $verbose = true) {
		if ($verbose)  echo "[DB Server:".$dbconfig["dbname"]."@".$dbconfig["dbserver"]."]\n<br>";
		if ($verbose)  echo "[Starting at ".date("H:i:s d/m/Y", time() )."]\n<div style='padding:10px;'>";
	
		$errorMessage = "";
		$errorQuery = "";
		$contents = $sql;
	
		if (strlen(trim($contents))==0) {
			if ($verbose)  echo "- Warning: No queries"."\n<br>";
			return 0;
		}
	
		$comment_patterns = array('/\s*--.*\n/', //inline comments start with --
				/*
					'/\/\*.*(\n)*.*(\*\/)?/', //C comments
		'/\s*#.*\n/', //inline comments start with #
		*/
		);
		$contents = preg_replace($comment_patterns, "\n", $contents);
	
		// make sure ; inside literals don't fool explode
		$contents = preg_replace_callback(array("('(.*?)')"), function($match) {
			$return = $match[0];
			if (strpos($return,";")!==false) {
				$return = str_replace(";", "/*dotcomma*/}", $return ) ;
			}
			return $return;
		},$contents );
	
	
		//Retrieve sql statements
		$statements = explode(";", $contents);
		$statements = preg_replace("/\s/", ' ', $statements);
	
		if ($verbose)  echo "- ".count($statements)." sql sentences found<br>";
		if ($verbose)  echo "- Connectiong to DB Server...<br>";
	
		$index = 0;
		$indexFiles = 0;
		
		if ($dbconfig == null) {
			$db = self::getDatabaseLink($dbconfig);
		} else {
			$db = new apDbPostgresql;
			if (!$db->connect($dbconfig["dbserver"],$dbconfig["dbname"],$dbconfig["dbuser"],$dbconfig["dbpass"], (isset($dbconfig["dbport"])?$dbconfig["dbport"]:5432) ) ) {
				$db=false;
				$error=true;
			}
		}
	
		if (!$db) {
			if ($verbose)  echo "- Error connecting to: <br>".implode("<br>",$dbconfig)."\n<br>";
		} else {
	
			if ($useTransaction) {
				if ($verbose)  echo "- Beginning transaction...<br>";
				$db->beginTransaction();
			}
	
			$error = false;
			if ($verbose)  echo "- Executing sql sentences...<br>";
	
			foreach ($statements as $query) {
				$query = str_replace("/*dotcomma*/}", ";", $query) ;
// 				echo "....|".strtolower(substr(trim($query),0,12))."|.....";
				if (strtolower(substr(trim($query),0,12))=="execute_file") {
					$fileS = strpos($query,'"')+1;
					$fileE = strrpos($query,'"');
					$fileName = substr($query, $fileS, $fileE-$fileS);
					$cmd = 'psql -U '.CONFIG::$user.' '.CONFIG::$db.' < '.constant("_GLOBAL_SQL_DIR").$fileName;
					echo "- Executing sql file: # ".$fileName."\n<br>";
					exec($cmd.' 2>&1', $out);
					echo implode(",",$out)."<br>";
					$indexFiles++;
				} else if (trim($query) != '') {
					$index++;
					$query = trim($query);
					if ($verbose)  echo '#' . $index . ' Executing query: ' . substr(trim($query),0,60) . "(...)\n<br>";
					$res = $db->execute($query);
					if ($res==false) {
						$error = true;
						$errorMessage .= "#$index: ".$db->getLastErrorMessage()."\n<br />";
						$errorQuery .= "#$index: ".$query."\n";
						if ($useTransaction) {
							echo "- Aborting operation";
							break;
						}
					}
				}
			}
			echo "- ".$indexFiles . " sql files executed."."\n<br>";
			if ($error) {
				if ($verbose)  echo "- Error: <div style='border:solid 1px red;display:table'>".$errorMessage."</div>\n<br>";
				if ($verbose)  echo '<textarea cols="80" rows="12">'.$errorQuery.'</textarea>'."\n<br>";
				if ($useTransaction) {
					$db->rollBack();
					if ($verbose)  echo '- Rollingback... '."\n<br>";
				}
			} else {
				if ($verbose)  echo $index . " sql sentences executed. No errors"."\n<br>";
				if ($useTransaction) {
					if ($verbose)  echo "- Commiting..."."\n<br>";
					$db->commit();
				}
			}
		}
		if ($verbose)  echo "</div>[Ending at ".date("H:i:s d/m/Y", time() )."]\n<br>";
		return !$error;
	}
	
	

	public static function upgradeDatabase ($sqlfile, $dbconfig, $currentVersion, $toVersion, $verbose = true, $ignoreErrors = false) {
		$firstVersion =0;
		$revisionsQueries = apDatabaseMaintenance::getRevisions ($sqlfile, $dbconfig, $firstVersion, $latestVersion, $verbose);		
		echo "[Upgrade from $currentVersion to $latestVersion]"."\n<br />";
		if ($ignoreErrors) echo "[Ignoring Errors!]\n<br />"; 
		$sql = "";
		foreach ($revisionsQueries  as $version => $queries) {
			if ($version > $currentVersion && $version <= $toVersion ) {
				$sql .= $queries;
			}
		}
	
		echo "Executing queries..."."\n<br />";
		$res = self::executeBatch($sql,$dbconfig,!$ignoreErrors, $verbose);
		if ($res===false) {
			if (!$ignoreErrors) {
				echo "Aborting upgrade. Error: ".$queries."\n<br />";			
			} else {
				apConfig::set('databaseversion',$toVersion, 'internal');
				echo "Upgrade executed with errors: <br />".$queries."\n<br />";
			}
		} else {
			apConfig::set('databaseversion',$toVersion, 'internal');
			echo "Upgrade successful.<br />"."\n<br />";
		}
		echo "[End Upgrade]"."\n<br />";
	}
}
