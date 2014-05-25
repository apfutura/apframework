<?php
$path =  dirname(__FILE__). DIRECTORY_SEPARATOR;
$cfg = dirname($path).DIRECTORY_SEPARATOR."config".DIRECTORY_SEPARATOR."setup.php";
require_once($cfg);
require_once(constant('_GLOBAL_MODEL_DIR') . "apApplication.php" );

$var = array();
$var['PAGE_TITLE'] = input("Page title?");

$var['MNTCLASSNAME_TABLE'] = input("Table?");

$def= $var['MNTCLASSNAME_TABLE'];
$var['CONTROLLER_NAME'] = input("Controller name?", $def);

$def = "ap".ucwords($var['MNTCLASSNAME_TABLE']);
$var['MNTCLASSNAME'] = input("MntClass?", $def );

$def = $var['MNTCLASSNAME']."List";
$var['MNTCLASSNAME_LIST'] = input("MntClassList?", $def );

$var['VALIDATEUSER'] = "true";
$var['ISMNT'] = "true";
$var['MNTCLASSNAME_INSTANCE'] = strtolower($var['MNTCLASSNAME']);
	
$id = apDatabase::getKeyFields($var['MNTCLASSNAME_TABLE']);
$var['MNTCLASSNAME_TABLE_ID'] = $id[0];

echo "Getting fields\n";
$tableFields = apDatabase::getTableFields($var['MNTCLASSNAME_TABLE'], false);
echo ">> ".implode(", ",$tableFields)."\n";
$fields = array();
foreach ($tableFields as $tableField) {
	$field = array();
	$field['FIELD'] = $tableField;
	$field['LABEL'] = '{\$L_'.strtoupper($tableField).'}';
	$field['FILTRABLE'] = "true";
	$field['EDITABLE'] = "true";
	$field['VISIBLE'] = "true";
	$field['EDITABLEINFORM'] = "true";
	$fields[] = $field;
}
$var['MNTCLASSNAME_TABLE_FIELDS'] = $fields;



$controllerTemplate = $path . "templates". DIRECTORY_SEPARATOR."controller_php";
$controllerFile = constant("_GLOBAL_CONTROLLERS_DIR") . $var['CONTROLLER_NAME'] . ".php";
echo "Generating controller\n";
apRender::$emptyVars = false;
$newControllerContents = apRender::renderCustom("@".$controllerTemplate ,$var, null, true);
echo "Saving to controller in $controllerFile\n";
file_put_contents($controllerFile, $newControllerContents);



$mntClassTemplate = $path . "templates". DIRECTORY_SEPARATOR."mntClass_php";
$mntClassFile = constant("_GLOBAL_MODEL_DIR") . "elements" . DIRECTORY_SEPARATOR . $var['MNTCLASSNAME'] . ".php";
echo "Generating mnt class\n";
$newMntClassContents = apRender::renderCustom("@".$mntClassTemplate ,$var, null, true);
echo "Saving to mnt class in $mntClassFile\n";
file_put_contents($mntClassFile, $newMntClassContents);



$templateTemplate = $path . "templates". DIRECTORY_SEPARATOR."template_html";
$templateFile = constant("_GLOBAL_TEMPLATES_DIR") . $var['CONTROLLER_NAME'] . ".html";
echo "Generating template\n";
$newTemplateContents = apRender::renderCustom("@".$templateTemplate ,$var, null, true). "\n<!-- contents -->";
echo "Saving to template in $templateFile \n";
file_put_contents($templateFile, $newTemplateContents);

exit;



function input($question, $default = null) {
	echo $question . ($default != null ? " [$default] ":" ");
	$handle = fopen ("php://stdin","r");
	$line = fgets($handle);
	$val = trim($line);
	if ($val=="") {
		return $default;
	} else {
		return $val;
	}
}
