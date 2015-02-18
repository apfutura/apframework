<?php 
ini_set('error_reporting', E_ALL ^ E_STRICT);
error_reporting(E_ALL ^ E_STRICT);

class CONFIG {	
	/* Installation params ****************************************/ 
	public static $urlBase = 'http://{$URL_HOST}/'; // <-- With a trailing "/" at the end!		
	public static $urlBaseJS = 'http://{$URL_HOST}/js';
	public static $urlBaseCSS = 'http://{$URL_HOST}/css';
	public static $urlBaseIMG = 'http://{$URL_HOST}/img';
	public static $avaliableLanguages = array("en" => "English"); // E.g array("en" => "English", "ca" => "Català", "es" => "Español");
	public static $defaultLang = 'en';
	public static $timeZone = 'Europe/Andorra';
	
	/* Internal options  ****************************************/
	public static $doNotTranslate = false;
	public static $catchErrors = true;
	public static $pathTmp = "/tmp/"; // <--With a trailing "/" at the end! (In windows: "C:\\Temp\\";)
	/*
	 * public static $userClass = 'frjUser'; // <-- any class with the same interface as apUser
	 */
	
	/* DB Server ****************************************/	
	public static $useDb=true;
	public static $server='localhost';
	public static $db='[DB]';
	public static $user='[DBUSER]';
	public static $pass='[DBPASS]';
	public static $port=5432;
	public static $logQueriesToApache = false; 	
	
	/* SMTP Server ****************************************/
	public static $smtpFromAddress="app@example.com";
	public static $smtpFromName="APP";
	public static $smtpServer="smtp.gmail.com";
	public static $smtpUser="emailuser";
	public static $smtpPassword="emailpass";
	public static $smtpPort="587";
	public static $smtpSecure="tls";
}
