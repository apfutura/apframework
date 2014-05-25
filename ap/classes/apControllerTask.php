<?php
include_once(constant('_GLOBAL_MODEL_DIR')."apStandardAjax.php" );

class apControllerTask {
	public $user;
	protected $task = null;
	protected $operation = null;
	protected $validateUser = false;
	protected $checkPermissions = false;
	protected $redirectToLogin = true;
	protected $validationExceptions = array();
	
	function __construct($calledOperation) {
		$this->operation = $calledOperation;
		$this->task = get_called_class();
		
		if (method_exists($this,"onInit")) {
			$this->onInit($calledOperation);
		}
		if ($this->validateUser && (!in_array($calledOperation,$this->validationExceptions)) ) {
			if (apSession::validate()) {
				$this->user = apSession::getCurrentUser();
				if ($this->checkPermissions) {
					if (method_exists($this,"onPermissionsCheck")) {
						$validation = $this->onPermissionsCheck();
					} else {
						$permissions = new apPermissions();			
						$validation = $permissions->check($this->task, $this->operation, $this->user); 
					}
					if (!$validation) {
						if (method_exists($this,"onPermissionsError")) {
							$this->onPermissionsError($calledOperation);
						} else {
							throw new Exception('{$L_PERMISSIONS_ERROR}');
						}
					}
				}
			} else {	
				if ($this->redirectToLogin) {
					$this->redirectToLogin(); // Redirectes and Exits execution
				} else {
					$this->responseAjaxData(false,array("ERR_SESSION")); //Outputs JSON ERR_SESSION and Exits execution
				}					
			}
		}
	}
	
	function redirectToLogin() {
		$url = CONFIG::$urlBase . "?task=".apController::getLoginController();
		$previousParametres = urlencode($_SERVER['QUERY_STRING']);
		$previousScript = urlencode(basename($_SERVER['SCRIPT_NAME']));	
		header('X-ap-operation: ERR_SESSION');
		header('Location: '.(CONFIG::$SSLLogin?'https://':'http://').substr($url,7)."&url=".$previousParametres.($previousScript!="index.php"?"&script=".$_SERVER['SCRIPT_NAME']:""));
	}
	
	function afterExecuteOperation($calledOperation) {
		if (method_exists($this,"onEnd")) {
			$this->onEnd($calledOperation);
		}
	}	
	
	function responseAjaxData($result,$messages = array(),$data = null) {	
		$response = new apStandardAjaxDataResponse($result,$messages,$data);
		echo $response;
		exit(); 				
	}
	
	function responseAjaxForm($template, $vars = array(), $sendVersionData = false) {				
		$jsContents = file_get_contents($template);
		$jsContents = apRender::replaceTemplateVars($jsContents,$vars);
		$response = new apexStandardAjaxDataResponse(true,array(),$jsContents);
		echo $response;
		exit();
	}
	
	function renderApTemplate($template, $KeyValueList = array(), $crumb = null) {		
		apRender::renderCustom($template, $this->templateParams($crumb,$KeyValueList) );
	}
	
	function addValidationException($operation) {
		$this->validationExceptions[] = $operation;
	}
	
	function templateParams($currentCrumb = null, $msg = array()) {
		if (!is_array($msg)) {
			throw new Exception("apControllertask templateParams expects an array in msg"); 
		}
		$array = array();
		$array['username'] = $this->user;
		$array = $array + $msg;
		$html_crumb ='<ul id="breadcrumb"><li><a href="'.CONFIG::$urlBase.'"><img src="'.CONFIG::$urlBaseIMG.'/crumb/home.png" alt="Home" class="home" /> {$L_HOME}</a></li>';
		if (!is_null($currentCrumb)) {
			foreach (explode(" | ",$currentCrumb) as $crumb) {
				if (substr($crumb,0,2)=="$$") {
					$crumb = substr($crumb,2);
					$str_class='class="crumb_left"';
				} else {
					$str_class="";
				}
				$html_crumb .= "<li $str_class>$crumb</li>";
			}
		}
		$html_crumb .= '</ul>';
		$array = $array + array('current_crumb'=>$html_crumb);
		return $array;
	}
	
	function getOperation() {
		return $this->operation;
	}
	
}