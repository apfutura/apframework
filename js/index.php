<?php
include_once("../ap/config/setup.php" );
require_once(constant('_GLOBAL_MODEL_DIR') . "apApplication.php" );

$jsFile = array_keys($_REQUEST);
$jsFile = substr($jsFile[0],0,-3).".js";
$jsFile = constant('_GLOBAL_JS_DIR').$jsFile;

header('Content-type: application/javascript');

if ( file_exists($jsFile) ) {
	$jsContents = file_get_contents($jsFile);
	$jsContents = apRender::replaceTemplateVars($jsContents,array("USER_CURRENT" => apSession::getCurrentUser() ), true );

	echo $jsContents;
} else {
	echo "<!-- File $jsFile not found! -->";
}
exit();
