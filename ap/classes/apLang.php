<?php
class apLang {

	static function getLangConstant($const, $htmlEntities = false, $KeyValueList = array()) {
		if ( isset(CONFIG::$doNotTranslate) ) {
			if (CONFIG::$doNotTranslate==true)  {
				return $const;
			}
		}
		if (defined(strtoupper($const))) {
			if ($htmlEntities) {
				$text=htmlentities(constant(strtoupper($const)),ENT_QUOTES, 'UTF-8' );
			} else {
				$text=constant(strtoupper($const));
			}
			$return = apRender::replaceTemplateVars($text,$KeyValueList, $htmlEntities);
			//$return = $text;
		} else {
			self::addConstantToLanguageFile($const);
			$return=$const;
		}
		
		return $return;
	}
	
	static function addConstantToLanguageFile($const) {
		$lang = self::getLang();
		$fileName = constant("_GLOBAL_LANG_DIR")."lang.".$lang.".php";
		$file = fopen($fileName, "a");
		$backtrace = debug_backtrace();
		$callingMethod = "";
		if (!empty($backtrace[5])) $callingMethod .= $backtrace[5]['file'].":".$backtrace[5]['function']."() ";
		if (!empty($backtrace[4])) $callingMethod .= $backtrace[4]['file'].":".$backtrace[4]['function']."() ";
		if (!empty($backtrace[3])) $callingMethod .= $backtrace[3]['file'].":".$backtrace[3]['function']."() ";
		if (!empty($backtrace[2])) $callingMethod .= $backtrace[2]['file'].":".$backtrace[2]['function']."() ";
		$time = date("Y-m-d H:i:s");
		$line  = "define('".strtoupper($const)."','".$const."');\n"; //Automatically ".$time." From: ".$callingMethod."\n";
		eval($line);
		//echo "<br>"	.$line;
		fputs($file, $line);
		fclose($file);
	}

	static function getLang($req=null) {

		$newLang = apUtils\getParamGET('lang');
		if ($newLang != null) {
			if (in_array($newLang, array_keys(CONFIG::$avaliableLanguages))) {
				$lang = $newLang; 
				self::setLang($lang);
				return $lang;
			}
		}
		if (empty($_COOKIE['lang'])) {
			if (!empty($_SESSION['lang'])) {
				$lang=(strlen($_SESSION['lang'])==0?(strlen($_SESSION['lang'])==0?CONFIG::$defaultLang:$_SESSION['lang']):$lang);
			} else {
				$lang=CONFIG::$defaultLang;
			}
		} else {
			$lang=$_COOKIE['lang'];
		}
		//$_SESSION['lang']=$lang;
		 		
		return $lang;
	}
	
	static function setLang($lang) {
		setcookie("lang", $lang, time() + (1 * 365 * 24 * 60 * 60)); 
 		$_SESSION['lang'] = $lang; 		
	}
	
	
	
	
}