<?php
class apApplication {
	static function init() {
		
		// Ini
		if( defined('STDIN') )  {
			CONFIG::$urlBase = (isset(CONFIG::$urlBaseStatic)?CONFIG::$urlBaseStatic:"http://localhost/");
		} else {
			CONFIG::$urlBase = str_replace('{$URL_HOST}', $_SERVER['HTTP_HOST'], CONFIG::$urlBase );
		}
		$host = parse_url(CONFIG::$urlBase);
		$host  = $host["host"];
		CONFIG::$urlBaseJS = str_replace('{$URL_HOST}', $host, CONFIG::$urlBaseJS );
		CONFIG::$urlBaseIMG = str_replace('{$URL_HOST}', $host, CONFIG::$urlBaseIMG );
		CONFIG::$urlBaseCSS = str_replace('{$URL_HOST}', $host, CONFIG::$urlBaseCSS );		
		
		date_default_timezone_set(CONFIG::$timeZone);
		
		//Includes
		include_once(constant('_GLOBAL_LIB_DIR') . "apUtils.php" );
		include_once(constant('_GLOBAL_LIB_DIR')   . "apHtmlUtils.php" );
		
		if (!isset(CONFIG::$catchErrors)?true:CONFIG::$catchErrors) include_once(constant('_GLOBAL_MODEL_DIR') . "apError.php" );
		include_once(constant('_GLOBAL_MODEL_DIR') . "apLang.php" );
		require_once(constant('_GLOBAL_LANG_DIR') . "lang.".apLang::getLang($_REQUEST).".php" );
		include_once(constant('_GLOBAL_MODEL_DIR') . "apSession.php" );
		include_once(constant('_GLOBAL_MODEL_DIR') . "apRender.php" );
		include_once(constant('_GLOBAL_MODEL_DIR') . "apAuthToken.php" );
		include_once(constant('_GLOBAL_MODEL_DIR') . "apCache.php" );		
		include_once(constant('_GLOBAL_MODEL_DIR') . "apMail.php" );
		include_once(constant('_GLOBAL_MODEL_DIR') . "apStandardAjax.php" );
		include_once(constant('_GLOBAL_MODEL_DIR')."apMntForm.php" );
		include_once(constant('_GLOBAL_MODEL_DIR')."apMntFormField.php" );
		include_once(constant('_GLOBAL_MODEL_DIR')."apPermissions.php" );
		
				
		include_once(constant('_GLOBAL_MODEL_DIR') . "apBaseElement.php" );
		include_once(constant('_GLOBAL_MODEL_DIR') . "apBaseElementList.php" );
		include_once(constant('_GLOBAL_MODEL_DIR') . "apUser.php" );
		include_once(constant('_GLOBAL_MODEL_DIR') . "apDatabase.php" );
		include_once(constant('_GLOBAL_MODEL_DIR') . "apConfig.php" );
		include_once(constant('_GLOBAL_MODEL_DIR') . "apLog.php" );
		
		include_once constant('_GLOBAL_SETUP_DIR') . "setup_includes.php";
		
		if (CONFIG::$useDb) {
			if (!apDatabase::connect()) self::showError("<h3>".apLang::getLangConstant("L_DBERROR").":</h3>".apDatabase::getDatabaseLink()->getLastErrorMessage());
			apLog::setUsername(apSession::getCurrentUser());					
		}
		
		//Config
		$prefix = crc32(CONFIG::$db.CONFIG::$server.CONFIG::$port);
		apCache::setKeyPrefix($prefix."_");
	}
	
	static function showError($text) {
		echo $text;
		exit(0);
	}
	
}

apApplication::init();