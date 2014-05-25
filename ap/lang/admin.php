<?php
require_once("../setup/configuration.php" );
$defaultLanguage = "ca";
if (!isset($_GET["action"])) {
	echo "This script will update the selected language using '". $defaultLanguage."' as base file. 
			<br/>Click to generate Spanish <a href='?action=1&origin_lang=$defaultLanguage&lang=es'>Continue</a>
			<br/>Click to generate English <a href='?action=1&origin_lang=$defaultLanguage&lang=en'>Continue</a>";
	exit();
}

// Preparation
$origin_lang = $_GET["origin_lang"];
$dest_lang = $_GET["lang"];
if (is_null($dest_lang)) { echo "No language specified";die(); }
$originLangFile =  constant("_GLOBAL_LANG_DIR")."lang.".$origin_lang.".php";
$destLangFile =  constant("_GLOBAL_LANG_DIR")."lang.".$dest_lang.".php";
$translateEngine = 'no_translate';
$file = fopen($destLangFile, "a");
if (!$file) {
	echo "Error opening $destLangFile for appending<br />";die();
}
$newlines = array();
$langOrigin = loadLanguage($originLangFile);
$langDestination = loadLanguage($destLangFile);
$destLanguageKeys = array_keys($langDestination);
$total = 0;
$totalAdded = 0;

// Start process
set_time_limit(0);
echo "[Starting translation to ".$dest_lang." using '".$translateEngine."' engine]<br />";
foreach ($langOrigin as $key => $value) {
		$total++;
		if (!in_array($key , $destLanguageKeys)) {
				$totalAdded++;
				$text = (!empty($value["text"])?$value["text"]:null);
				$text_quotation= (!empty($value["text_quotation"])?$value["text_quotation"]:null);
				$key_quotation= (!empty($value["key_quotation"])?$value["key_quotation"]:null);
				$tries=0;
				$maxretries = 2;
				do {
					$tries++;
					echo "Adding Missing key ".$key."<br />"; 
					ob_flush();
					flush();
					 if ($translateEngine == '') {
					 	throw new exception('Your translating engine name is wrongly specified.');
					 }		
					 switch (strtolower($translateEngine)) {
					 		case 'no_translate':
					 			$new_text = $text;
					 			break;
							case 'bing':
								echo "Transaliting attempt:".$tries."<BR />";
								//BingTranslateAPI::setAPIKey(CONFIG::$bingAPIKEY);
								//$new_text = BingTranslateAPI::translate($text,$defaultLanguage,$dest_lang);
								break;						
							case 'google':
							default:
								echo "Transaliting attempt:".$tries."<BR />";
								//$new_text = GoogleTranslateAPI::translate($text,$defaultLanguage,$dest_lang);
								break;								
					}					
					if ( ( $new_text==false) && ($tries<$maxretries ) ) {
						sleep(15*$tries);
						echo "<font color='orange'>Warning:</font> Retrying in ".(15*$tries)." seconds.<br />";
					}
				} while ( ($new_text==false) && ($tries<=$maxretries ) );
				//$new_text=str_replace("'","\'",fixUTF8($new_text));
				$new_text=fixUTF8($new_text);
				
				if (strlen($new_text)==0) {
					$new_text = str_replace (' -.- ','"',$text);
					echo "<font color='red'>Error:</font> Couldn't get translation<br />";
				}
				
				if (strtolower($translateEngine)!="no_translate") {
					echo $origin_lang. " =  define(".$key_quotation.$key.$key_quotation.",".$text_quotation.$text.$text_quotation.")<br>"; 
					echo $origin_lang. " = define(".$key_quotation.$key.$key_quotation.",".$text_quotation.$new_text .$text_quotation.")<hr><br>";
				}
				$newlines[] = "define(".$key_quotation.$key.$key_quotation.",".$text_quotation.$new_text .$text_quotation.");\n";
				
				ob_flush();
		        flush();
		} else {
			echo "Skipping existing key ".$key."<br/>";
		}		
}
echo "'$total' Keys found in $origin_lang file '$totalAdded' added to $dest_lang file<br />";

echo "Backup previous file to $destLangFile.bak<br />";
copy($destLangFile, $destLangFile.".bak");
echo "Writting results to: $destLangFile<br />";
if ($totalAdded>0) {
	fputs($file, "// Added automatically by admin.php on ".date('Y-m-d H:i:s', time()));
}
foreach ($newlines as $lineutf8) {
	//$line = iconv('UTF-8', 'ISO-8859-1', $lineutf8);
	fputs($file, $lineutf8);
}	

fclose($file);
ECHO "Done!";



function loadLanguage($langFile) {
	$file = fopen($langFile, "r") or exit("Unable to open $langFile lang file!");
	while(!feof($file))	{
		$lines[]=fgets($file);
	}
	fclose($file);

	foreach ($lines as $line) {
		if (substr(trim($line),0,6)=="define")  {
			$value=explode(",",substr(trim($line),7,-2));
			$key = substr(trim($value[0]),1,-1);
			$text = substr(trim($value[1]),1,-1);				
			$key_quotation = substr(trim($value[0]),0,1);
			$text_quotation = substr(trim($value[1]),0,1);		
			$destionatioLanguage_keys[$key] = array("key_quotation" => $key_quotation , "text_quotation" => $text_quotation, "text" => $text);
		}
	}
	return  $destionatioLanguage_keys;
}





/**

 * @author   "Sebastián Grignoli" <grignoli@framework2.com.ar>

 * @package  forceUTF8

 * @version  1.1

 * @link     http://www.framework2.com.ar/dzone/forceUTF8-es/

 * @example  http://www.framework2.com.ar/dzone/forceUTF8-es/

  */



function forceUTF8($text){

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



  if(is_array($text))

    {

      foreach($text as $k => $v)

    {

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



function forceLatin1($text) {

  if(is_array($text)) {

    foreach($text as $k => $v) {

      $text[$k] = forceLatin1($v);

    }

    return $text;

  }

  return utf8_decode(forceUTF8($text));

}



function fixUTF8($text){

  if(is_array($text)) {

    foreach($text as $k => $v) {

      $text[$k] = fixUTF8($v);

    }

    return $text;

  }

  

  $last = "";

  while($last <> $text){

    $last = $text;

    $text = forceUTF8(utf8_decode(forceUTF8($text)));

  }

  return $text;    

}
