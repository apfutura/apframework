<?php
class apDatabaseMaintenance {

	static function checkDatabaseRevisionsAvailability($sqlfile = null, $dbconfig = null) {
		$firstVersion=0;
		$latestVersion=0;

		if ($sqlfile == null) $sqlfile = constant("_GLOBAL_ROOT_DIR")."sql/database_revisions.sql";

		$localVersion = apConfig::get('databaseversion','internal');
		$revisionsQueries = apDatabaseMaintenance::getRevisions ($sqlfile, $dbconfig, $firstVersion, $latestVersion, false);

		if ($latestVersion>$localVersion) {
			return $latestVersion;
		} else {
			return false;
		}
	}


	static function getRevisions($sqlfile, $dbconfig, &$firstRevision, &$latestRevision, $verbose = true) {
		if ($verbose)  echo "[File:".$sqlfile."]\n<br>";
		if ($verbose)  echo "[DB Server:".$dbconfig["dbname"]."@".$dbconfig["dbserver"]."]\n<br>";
		if ($verbose)  echo "[Starting at ".date("H:i:s d/m/Y", time() )."]\n<br>";

		$contents = @file_get_contents($sqlfile);

		if ($contents==false ) {
			if ($verbose)  echo "Error: File $sqlfile not found"."\n<br>";
			return false;
		}

		//Retrieve sql statements
		$statements = explode("--db", $contents);
		foreach ($statements as $queries) {
			$endVersionNumber = strpos($queries, "\n");
			$version = trim(substr($queries,0,$endVersionNumber));
			$queries= trim(substr($queries,$endVersionNumber + 1));
			if (is_numeric($version) ) {

				if ($version<=$firstRevision) $firstRevision = $version;
				if ($version>=$latestRevision) $latestRevision = $version;

				if (!isset($revisions[$version])) {
					$revisions[$version] = $queries;
				} else {
					echo "Error. Revision $version repeated. Ignoring second apparence"."\n<br>";
					return false;
				}
			} else {
				if (strlen($queries)>0)  echo "Skipping block: ". substr($queries,0,10)."\n<br>";
			}

		}

		if ($verbose)  echo "[Ending at ".date("H:i:s d/m/Y", time() )."]\n<br>";
		return $revisions;
	}
}