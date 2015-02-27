<?php
namespace apUtils;
const PARAM_RAW = 1;
const PARAM_INT = 2;
const PARAM_ESCAPED_STRING = 3;
const PARAM_FLOAT = 4;

function bytesToSize1024($bytes, $precision = 2)
{
    // human readable format -- powers of 1024
    //
    $unit = array('B','KB','MB','GB','TB','PB','EB');
    if ($bytes == 0) return '0 KB';

    return @round(
        $bytes / pow(1024, ($i = floor(log($bytes, 1024)))), $precision
    ).' '.$unit[$i];
}


function getCurrentUrl() {
    $url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ? 'https://' : 'http://';
    $url .= $_SERVER['SERVER_NAME'];
    if ($_SERVER['SERVER_PORT'] != '80') {
      $url .= ':'. $_SERVER['SERVER_PORT'];
    }
    $url .= $_SERVER['REQUEST_URI'];
    return $url;
 }
 
function generateRandomString($length = 8) {
	$chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
	$count = mb_strlen($chars);

	for ($i = 0, $result = ''; $i < $length; $i++) {
		$index = rand(0, $count - 1);
		$result .= mb_substr($chars, $index, 1);
	}
	return $result;
}


/**
 * @param string with an $address
 * @return array of stdClass'es with lon lat for thew given $address 
 */
function findLatLongUsingGoogle($address, $countryFilter = null, $localityFilter = null,  $esrid = null){
	$google_maps_key='ABQIAAAAgnTxpQK8BMuqqTtXsnuJqhT2yXp_ZAY8_ufC3CFXhHIE1NvwkxQzQMhzEo31ps_yvN1qq_tt7wxC6g';
	$url = "http://maps.google.com/maps/geo?q=".rawurlencode($address);//&amp;key=".$google_maps_key;
	$xml = file_get_contents($url);
	echo $url;
	$object = json_decode($xml);
	$result = array();
	
	foreach ($object->Placemark as $placemark ) {
		$countryCode  = (isset($placemark->AddressDetails->Country->CountryNameCode)?$placemark->AddressDetails->Country->CountryNameCode:null);
		$localityName = (isset($placemark->AddressDetails->Country->AdministrativeArea->SubAdministrativeArea->Locality->LocalityName)?$placemark->AddressDetails->Country->AdministrativeArea->SubAdministrativeArea->Locality->LocalityName:null);		
		if ((strtoupper($countryFilter)==strtoupper($countryCode)) || ($countryFilter=null)) {
			$comparison = \wordMatch::match( array($localityFilter), $localityName, 1, 5);
			if (isset($comparison[0]->distance)) $comparisonDistance = $comparison[0]->distance; else $comparisonDistance = 5;
			//echo "$localityFilter i $localityName: $comparisonDistance ";
			if (($comparisonDistance<5) || ($localityFilter==null)) {				
				$tmp = new \stdClass();
				$tmp->address = $placemark->address;		
				$lon = $placemark->Point->coordinates[0];
				$lat = $placemark->Point->coordinates[1];
				if ($esrid!=null) {
					$sql ="SELECT st_x(transform(setsrid(makepoint($lon,$lat),4326), $esrid)) as lon , st_y(transform(setsrid(makepoint($lon,$lat),4326), $esrid)) as lat";
					echo $sql;
					$resultTransform = \UTILS::fesSQL($sql);
					$lon = $resultTransform[0]['lon'];
					$lat = $resultTransform[0]['lat'];
				}
				$tmp->lon = $lon;
				$tmp->lat = $lat;
				$result[] = $tmp;
			}			
		}
	}	
	return $result;
}

/**
 * @param string with an $address
 * @return array of stdClass'es with lon lat for thew given $address
 */
function findLatLongUsingNominatim($address, $countryFilter = null, $localityFilter = null,  $esrid = null){
	
	$params = "&addressdetails=1&format=json&countrycodes=".$countryFilter;
	$url = "http://nominatim.openstreetmap.org/search?q=".rawurlencode($address).$params;//&amp;key=".$google_maps_key;
	//http://open.mapquestapi.com/nominatim/v1/search?
	$xml = file_get_contents($url);
	echo $url;
	$object = json_decode($xml);
	$result = array();

	foreach ($object as $placemark ) {
		var_dump($placemark);
		$countryCode  = (isset($placemark->address->country_code)?$placemark->address->country_code:null);
		$localityName = (isset($placemark->address->city)?$placemark->address->city:null);
		if ((strtoupper($countryFilter)==strtoupper($countryCode)) || ($countryFilter=null)) {
			$comparison = \wordMatch::match( array(strtoupper($localityFilter)), strtoupper($localityName), 1, 5);
			if (isset($comparison[0]->distance)) $comparisonDistance = $comparison[0]->distance; else $comparisonDistance = 5;
			//echo "$localityFilter i $localityName: $comparisonDistance ";
			if (($comparisonDistance<5) || ($localityFilter==null)) {
				$tmp = new \stdClass();
				$tmp->address = $placemark->display_name;
				$lon = $placemark->lon;
				$lat = $placemark->lat;
				if ($esrid!=null) {
					$sql ="SELECT st_x(transform(setsrid(makepoint($lon,$lat),4326), $esrid)) as lon , st_y(transform(setsrid(makepoint($lon,$lat),4326), $esrid)) as lat";
					echo $sql;
					$resultTransform = \UTILS::fesSQL($sql);
					$lon = $resultTransform[0]['lon'];
					$lat = $resultTransform[0]['lat'];
				}
				$tmp->lon = $lon;
				$tmp->lat = $lat;
				$result[] = $tmp;
			}
		}
	}
	return $result;
}

function findAddressUsingGoogle($srs,$lat,$long){
	$url = "http://maps.google.com/maps/api/geocode/xml?latlng=".$lat.",".$long."&sensor=false";
	$xml = file_get_contents($url, false);
	return $xml;
}

/**
 * Deletes Dir AND Contents
 * @param string $dir
 */
function rrmdir($dir) {
	foreach(glob($dir . '/*') as $file) {
		if(is_dir($file)) rrmdir($file); else unlink($file);
	} rmdir($dir);
}

function logToApache($Message) {
	$stderr = fopen('php://stderr', 'w');
	fwrite($stderr,$Message."\n");
	fclose($stderr);
}

function printTimeReferenceTable() {
	global $timeReferenceLog;
	echo "<table border='1'><tr><th>Test</th><th>Temps</th></tr>";
	foreach ($timeReferenceLog as $k => $v) {
		echo "<tr><td>$k</td><td>".round($v,4)."</td></tr>";
	}
	echo "</table>";

}

function startTimeReference($meter = "default") {
	global $timeReference;
	$microsecondes=microtime();
	list($micro,$time)=explode(' ',$microsecondes);
	$timeReference[$meter] = ($micro+$time);
}

function stopTimeReference($meter = "default", $log = false) {
	global $timeReference, $timeReferenceLog;
	$microsecondes=microtime();
	list($micro,$time)=explode(' ',$microsecondes);
	$result = ($micro+$time) - $timeReference[$meter];
	if ($log) $timeReferenceLog[$meter] = $result;
	return $result;
}

function stopTimeReferenceMessage($meter = "default", $log = false) {
	return '<div style="font-style: italic;">{$L_TIME} ('.$meter.'): '.round(stopTimeReference($meter, $log),4).'</div>';
}

function rcopy($src,$dst) {
	$dir = opendir($src);
	@mkdir($dst);
	while(false !== ( $file = readdir($dir)) ) {
		if (( $file != '.' ) && ( $file != '..' )) {
			if ( is_dir($src . '/' . $file) ) {
				rcopy($src . '/' . $file,$dst . '/' . $file);
			}
			else {
				$result = copy($src . '/' . $file,$dst . '/' . $file);
			}
		}
	}
	closedir($dir);
}


function getBase64QRImage($qrText) {
	$imagedata = file_get_contents(generateElementQRImageFile($qrText));
	return base64_encode($imagedata);
}

function generateElementQRImageFile($qrText) {	
	include_once constant("_GLOBAL_LIB_DIR")."phpqrcode/qrlib.php";
	@mkdir(constant("_GLOBAL_TMP_DIR").'ap/cache/qr', 0777, true);
	$qrFilename = constant("_GLOBAL_TMP_DIR").'ap/cache/qr/element_'.md5($qrText).'.png';
	if (!file_exists($qrFilename)) {
		$errorCorrectionLevel = 'L';
		$matrixPointSize = 6;
		\QRcode::png($qrText, $qrFilename , $errorCorrectionLevel, $matrixPointSize, 2);
	}
	return $qrFilename;
}


/**
 * Function: sanitize
 * Returns a sanitized string, typically for URLs.
 *
 * Parameters:
 *     $string - The string to sanitize.
 *     $force_lowercase - Force the string to lowercase?
 *     $anal - If set to *true*, will remove all non-alphanumeric characters.
 */
function sanitize($string, $force_lowercase = true, $anal = false) {
	$strip = array("~", "`", "!", "@", "#", "$", "%", "^", "&", "*", "(", ")", "_", "=", "+", "[", "{", "]",
			"}", "\\", "|", ";", ":", "\"", "'", "&#8216;", "&#8217;", "&#8220;", "&#8221;", "&#8211;", "&#8212;",
			"â€”", "â€“", ",", "<", ".", ">", "/", "?");
	$clean = trim(str_replace($strip, "", strip_tags($string)));
	$clean = preg_replace('/\s+/', "-", $clean);
	$clean = ($anal) ? preg_replace("/[^a-zA-Z0-9]/", "", $clean) : $clean ;
	return ($force_lowercase) ?
	(function_exists('mb_strtolower')) ?
	mb_strtolower($clean, 'UTF-8') :
	strtolower($clean) :
	$clean;
}

function getParam( &$arr, $name, $def=null, $dataType = PARAM_RAW ) {
	if (get_magic_quotes_gpc()) {
		if (!empty($arr[$name])) {
			stripslashes($arr[$name]);
		}
	}
	$value = (isset($arr[$name])?$arr[$name]:$def);
	switch ($dataType) {
		case constant('apUtils\PARAM_INT'):
			$value = intval($value);
			break;
		case constant('apUtils\PARAM_FLOAT'):
		    $value = floatval($value);
		    break;			
		case constant('apUtils\PARAM_ESCAPED_STRING'):
			$value = htmlspecialchars($value, ENT_QUOTES,'UTF-8');
			break;
		case constant('apUtils\PARAM_RAW'):
		default:
			break;
	}
	return $value;
}

function getParamGET( $name, $dataType = PARAM_RAW, $def=null ) {
	return getParam($_GET, $name, $def, $dataType);
}

function getParamPOST( $name, $dataType = PARAM_RAW, $def=null ) {
	return getParam($_POST, $name, $def, $dataType);
}

function getIP() {
	$ip="";
	if (getenv("REMOTE_ADDR") && strcasecmp(getenv("REMOTE_ADDR"), ""))
	{
		$ip = getenv("REMOTE_ADDR");
	}
	else if (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], ""))
	{
		$ip = $_SERVER['REMOTE_ADDR'];
	}
	else if (getenv("HTTP_CLIENT_IP") && strcasecmp(getenv("HTTP_CLIENT_IP"), ""))
	{
		$ip = getenv("HTTP_CLIENT_IP");
	}
	else if (getenv("HTTP_X_FORWARDED_FOR") && strcasecmp(getenv("HTTP_X_FORWARDED_FOR"), ""))
	{
		$ip = getenv("HTTP_X_FORWARDED_FOR");
	}

	return($ip);
}


function exportAsociativeArrayToXLS($array,$filename, $utf8 = false, $separator = null){
	header("Content-Disposition: attachment; filename=\"$filename\"");
	$charset = "";
	if (!$utf8) $charset = " charset: iso-8859-1";
	header("Content-Type: application/vnd.ms-excel;". $charset);
	$flag=false;
	foreach ($array as $row) {
		if(!$flag) {
			# display field/column names as first row
			if ($separator) {
			    $headerStr = implode($separator, array_keys($row)) . "\r\n";
			} else {
                $headerStr = implode("\t", array_keys($row)) . "\r\n";
			}
			if (!$utf8) $headerStr = utf8_decode($headerStr);
			echo $headerStr;
			$flag = true;
		}
		
		array_walk($row, function(&$str) {
				if (!$utf8) $str = utf8_decode($str);
				$str = preg_replace("/\t/", "\\t", $str);
				$str = preg_replace("/\r?\n/", "\\n", $str);
				
				}
		);
		if ($separator) {
		    echo implode($separator, array_values($row)) . "\r\n";
		} else {
            echo implode("\t", array_values($row)) . "\r\n";
		}
	}
}


function getDMYDateFromYMDDate($dateString) {
	$return = "";
	if (strlen($dateString)>0) { 
		$return = date("d/m/Y",strtotime($dateString));
	}
	return $return;
}


function importCsv($file, $elementType, $fields = array()) {
	
	if (count($fields) == 0 ) {
		$tempEntity=new $elementType;
		$elementFields = $tempEntity->getTableFieldsArray();
	} else {
		$elementFields = $fields;
	}
	
	$ok = 0;	
	$total = 0;
	$errs = array();
	$fieldIndex = array();
	$handle = fopen($file, "r");
	$csvFields = fgetcsv($handle, 1000, ";"); //skip first line
	foreach ($csvFields as $csvHeaderField) {
		$field = strtoupper(trim($csvHeaderField));
		if (in_array($field, $elementFields)) {
			$fieldIndex[$field] =  $index;
		} else {
			$WarnMSG .= 'Ignoring header "' .$headerField. "\".\n";
		}					
		$index++;
	}
	
	while (($data = fgetcsv($handle, 1000, ";")) !== FALSE)		{		
		$elementData = array();
		foreach ($fieldIndex as $field => $csvIndex) {
			$elementData[$field] = trim($data[$csvIndex]);
		}
		$tempEntity=new $elementType;
		$result = $tempEntity->insert($elementData);
		if (!$result) {
			$err = new stdClass();
			$err->line = $total;
			$err->data = $data;
			$errs[] = $err;
		} else {
			$ok++;
		}
		$total++;
	}
	fclose($handle);
	return array($total==$ok, $errs);
}

function exportTableToCsv($exportFile, $table, $fields = null /* array */, $limit = null /* integer */, $onlyHeaders = false) {
	// TODO: make tihs function use apUtils\exportArrayToCsv
	$db = \apDatabase::getDatabaseLink();
	$sqlLimit = "";
	if ($limit!==null) {
		$sqlLimit = " LIMIT ".$limit;
	}
	$sqlFields = "*";
	if ($fields!==null) {
		$sqlFields = implode(",", $fields);
	}
	$SQL = 'SELECT '.$sqlFields.' FROM "'.$table.'"'.$sqlLimit;
 
	$results = $db->query($SQL);
	$fp = fopen($exportFile, 'w');
	$firstRegister = true;
	
	foreach ($results as $res) {
		if ($firstRegister) {
			fputcsv($fp, array_keys($res));
			$firstRegister = false;
			if ($onlyHeaders) break;
		}
		fputcsv($fp, $res);
	}			
	fclose($fp);

	return true;
}


function exportArrayToCsv($exportFile, $rowsArray, $onlyHeaders = false) {

	$fp = fopen($exportFile, 'w');
	$firstRegister = true;
	foreach ($rowsArray as $row) {
		if ($firstRegister) {
			fputcsv($fp, array_keys($row));
			$firstRegister = false;
			if ($onlyHeaders) break;
		}
		fputcsv($fp, $row);
	}
	fclose($fp);

	return true;
}

function forceUTF8($text) {
	/**
	* Function forceUTF8
	*
	* This function leaves UTF8 characters alone, while converting almost all non-UTF8 to UTF8.
	*
	* It may fail to convert characters to unicode if they fall into one of these scenarios:
	*
	* 1) when any of these characters:   ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖ×ØÙÚÛÜÝÞß
	*    are followed by any of these:  ("group B")
	*                                    ¡¢£¤¥¦§¨©ª«¬­®¯°±²³´µ¶•¸¹º»¼½¾¿
	* For example:   %ABREPRESENT%C9%BB. «REPRESENTÉ»
	* The "«" (%AB) character will be converted, but the "É" followed by "»" (%C9%BB)
	* is also a valid unicode character, and will be left unchanged.
	*
	* 2) when any of these: àáâãäåæçèéêëìíîï  are followed by TWO chars from group B,
	* 3) when any of these: ðñòó  are followed by THREE chars from group B.
	*
	* @name forceUTF8
	* @param string $text  Any string.
	* @return string  The same string, UTF8 encoded
	*
	*/

	if (is_array($text))	{
		foreach($text as $k => $v) {
			$text[$k] = forceUTF8($v);
		}
		return $text;
	}

	$max = strlen($text);
	$buf = "";

	for($i = 0; $i < $max; $i++){
		$c1 = $text{$i};
		if($c1>="\xc0"){ //Should be converted to UTF8, if it's not UTF8 already
			$c2 = $i+1 >= $max? "\x00" : $text{$i+1};
			$c3 = $i+2 >= $max? "\x00" : $text{$i+2};
			$c4 = $i+3 >= $max? "\x00" : $text{$i+3};
			if($c1 >= "\xc0" & $c1 <= "\xdf"){ //looks like 2 bytes UTF8
				if($c2 >= "\x80" && $c2 <= "\xbf"){ //yeah, almost sure it's UTF8 already
					$buf .= $c1 . $c2;
					$i++;
				} else { //not valid UTF8.  Convert it.
					$cc1 = (chr(ord($c1) / 64) | "\xc0");
					$cc2 = ($c1 & "\x3f") | "\x80";
					$buf .= $cc1 . $cc2;
				}
			} elseif($c1 >= "\xe0" & $c1 <= "\xef"){ //looks like 3 bytes UTF8
				if($c2 >= "\x80" && $c2 <= "\xbf" && $c3 >= "\x80" && $c3 <= "\xbf"){ //yeah, almost sure it's UTF8 already
					$buf .= $c1 . $c2 . $c3;
					$i = $i + 2;
				} else { //not valid UTF8.  Convert it.
					$cc1 = (chr(ord($c1) / 64) | "\xc0");
					$cc2 = ($c1 & "\x3f") | "\x80";
					$buf .= $cc1 . $cc2;
				}
			} elseif($c1 >= "\xf0" & $c1 <= "\xf7"){ //looks like 4 bytes UTF8
				if($c2 >= "\x80" && $c2 <= "\xbf" && $c3 >= "\x80" && $c3 <= "\xbf" && $c4 >= "\x80" && $c4 <= "\xbf"){ //yeah, almost sure it's UTF8 already
					$buf .= $c1 . $c2 . $c3;
					$i = $i + 2;
				} else { //not valid UTF8.  Convert it.
					$cc1 = (chr(ord($c1) / 64) | "\xc0");
					$cc2 = ($c1 & "\x3f") | "\x80";
					$buf .= $cc1 . $cc2;
				}
			} else { //doesn't look like UTF8, but should be converted
				$cc1 = (chr(ord($c1) / 64) | "\xc0");
				$cc2 = (($c1 & "\x3f") | "\x80");
				$buf .= $cc1 . $cc2;
			}
		} elseif(($c1 & "\xc0") == "\x80"){ // needs conversion
			$cc1 = (chr(ord($c1) / 64) | "\xc0");
			$cc2 = (($c1 & "\x3f") | "\x80");
			$buf .= $cc1 . $cc2;
		} else { // it doesn't need convesion
			$buf .= $c1;
		}
	}
	
	return $buf;
}
