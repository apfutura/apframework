<?php
// has no include requirements
class apRestClient {
	public $status = 0;
	public $response = "";
	public $requestURI = "";
	public $requestPort = "";
	public $method = "";
	public $debugMessages = false;
	public $cookies = array(); // "VAR=value"
	public $cookiePath = "/";
	
	public $contentType = 'application/json';
	public $connectTimeout = 15; //0 = inifite
	
	public $authType=0; //0 = none , 1 = http basic
	public $authUsername="";
	public $authPassword="";
	
	public $additionalHttpHeaders=array();

	function setCredentials ($username, $password) {
	    $this->authUsername = $username;
	    $this->authPassword = $password;
	    $this->authType = 1;
	}
	
	function setPort ($requestPort) {
		$this->requestPort = $requestPort;
	}
	
	function call($requestURI, $method = 'GET',$postData = null) {

		
		if ($this->debugMessages) {
			echo "<div style='width:640px;background:#CCC;'>";
			echo "URI:".$requestURI;
			echo "<br>";
			echo "Port:". $this->requestPort;
			echo "<br>";
			echo "Method:".$method;
			echo "<br>";
			if ($this->authType!=0) {
				echo "Credentials HTTP:".$this->authUsername . "/" . $this->authPassword;
			}
			echo "<br>";
			echo "Headers:".implode(",",$this->additionalHttpHeaders);
			echo "<br>";
			echo "Data:".print_r($postData,true);
			echo "<br>";
		}
		$this->requestURI = $requestURI;
		$this->method = $method;

		// Initialize the session
		$session = curl_init($requestURI);
		// Set curl options
		curl_setopt($session, CURLOPT_HEADER, true);
		if ($this->authType==1) {
		     curl_setopt($session, CURLOPT_USERPWD, $this->authUsername . ":" . $this->authPassword) ;
		}

		if ($this->requestPort!="") {
			curl_setopt($session, CURLOPT_PORT, $this->requestPort);
		}
				
		curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($session, CURLOPT_CONNECTTIMEOUT , $this->connectTimeout);
		
		$httpHeader = array();
		if ($method=='POST') {
			//http://php.net/manual/en/function.curl-setopt.php
			curl_setopt($session, CURLOPT_POST, 1);
			if ($postData != null ) {
				foreach ($postData as $key => $value) {
					$params[] = $key . '=' . urlencode($value);
				}
				$postDataStr = implode('&', $params);
				curl_setopt($session, CURLOPT_POSTFIELDS, $postDataStr);				
				$httpHeader[]= "Content-type: " . $this->contentType;
			}
			$httpHeader[]= 'Expect: '; // Sense això curl a vegades enxufa un "Expect: 100" de manera que el server tot i exectuar la crida, retorna status 100! + info: http://stackoverflow.com/questions/463144/php-http-post-fails-when-curl-data-1024
			$httpHeader[]= 'Content-Length: ' . strlen($postDataStr);			
		}
		if ($method=='PUT') {
			curl_setopt($session, CURLOPT_CUSTOMREQUEST, "PUT");
			if ($postData != null ) {
				curl_setopt($session, CURLOPT_POSTFIELDS, $postData);
				curl_setopt($session, CURLOPT_HTTPHEADER, array('Content-type: '. $this->contentType, 'Content-Length: ' . strlen($postData)));
			}
		}
		if ($method=='DELETE') {
			curl_setopt($session, CURLOPT_CUSTOMREQUEST, "DELETE"); 
			if ($postData != null ) {
				curl_setopt($session, CURLOPT_POSTFIELDS, $postData);
				curl_setopt($session, CURLOPT_HTTPHEADER, array('Content-type: '. $this->contentType, 'Content-Length: ' . strlen($postData)));
			}
		}

		//add HTTP Headers to the call
		foreach ($this->additionalHttpHeaders as $header) {
		    $httpHeader[]= $header;
		}
		if (!empty($httpHeader)) {
		    curl_setopt($session, CURLOPT_HTTPHEADER, $httpHeader);
		}
		
		//add cookies
		if (!empty($this->cookies)) {
			$strCookie = implode('; ', $this->cookies );
			$strCookie .= '; path:'. $this->cookiePath;
			curl_setopt( $curl, CURLOPT_COOKIE, $strCookie );	
		}
		
		// Make the request
		$response = curl_exec($session);
		// Get HTTP Status code from the response
		$status_code = array();
		preg_match('/\d\d\d/', $response, $status_code);
		// Get body
		$response=mb_substr($response, curl_getinfo($session,CURLINFO_HEADER_SIZE));
		// Close the curl session
		curl_close($session);

		$this->status = $status_code[0];
		$this->response = $response;
		if ($this->debugMessages) {
			echo "<br>";
			echo "Status:".$status_code[0];
			echo "<br>";
			echo "Response:";
			print_r($response);
			echo "</div>";
		}

		return $response;
	}

	function getXML() {	
		$xml = $this->response;	
		return $xml;
	}

	function getJSON() {	
		return json_decode($this->response);
	}

	function callJSON($requestURI,$method = 'GET',$postData = null) {	
		$this->call($requestURI,$method ,$postData);
	 	return $this->getJSON();
	}
	
	function callXML($requestURI,$method = 'GET',$postData = null) {	
		$this->call($requestURI,$method ,$postData);
	 	return $this->getXML();
	}

}
