<?php
class apMail {
	
	public $SMTPdebug = false; // enables SMTP debug information (for testing)	// 1 = errors and messages	// 2 = messages only
	private $errorMessage="";
	private $configuration;
	/* Configuration KEYS:
	// $configuration["smtpServer"] = "servername";
	// $configuration["smtpUser"] = "assss@aaaa";
	// $configuration["smtpPassword"] = "zzzzzz";
	// $configuration["smtpSecure"] = "tls"; // "tls" o "ssl" o ""
	// $configuration["smtpPort"] = 25;
	// $configuration["smtpFromAddress"] = "sss@sssss"; / default
	// $configuration["smtpFromName"] = "mr sss"; / default
	// $configuration["attachImagesPath"] = dirname(__DIR__);
	 */
	
	function __construct($configuration) {	
		$this->configuration = $configuration;
	}

	
	
	function sendEmailMessage($emailTo,$subject,$messageTemplateFile,$messageParams=array(), $htmlEntities = false) {
		$templatePath = apRender::getTemplatePath($messageTemplateFile);
		if ( file_exists($templatePath) ) {
			$subject = apRender::replaceTemplateVars($subject,$messageParams);			
			$body = apRender::getTemplateContents($templatePath);
			$bodyRendered = apRender::replaceTemplateVars($body,$messageParams,$htmlEntities);
			return $this->sendMail($emailTo,$subject,$bodyRendered);
		} else {
			$this->errorMessage="Email template file ". $messageTemplateFile." not found!";
			return false;
		}
	}
	
	public function sendMail($emailTo, $subject ,$body) {
		
		require_once(_GLOBAL_LIB_DIR.'phpmailer/class.phpmailer.php');
		$mail             = new PHPMailer();
		$body             = str_replace("[\]",'',$body);
		
		if (!empty($this->configuration['smtpServer'])) {
			$mail->IsSMTP(); 
			$mail->Host = $this->configuration['smtpServer']; // SMTP server
			if ($this->SMTPdebug != false) $mail->SMTPDebug  = $this->SMTPdebug;
			if (!empty($this->configuration['smtpUser'])) {
				$mail->SMTPAuth   = true;                  // enable SMTP authentication
				$mail->Username   = $this->configuration['smtpUser']; 
				$mail->Password   = $this->configuration['smtpPassword'];           
			}
			$mail->SMTPSecure = (!empty($this->configuration['smtpSecure'])?$this->configuration['smtpSecure']:"");// sets the prefix to the "tls,"ssl" or ""
			$mail->Port       = (!empty($this->configuration['smtpPort'])?$this->configuration['smtpPort']:25);    // set the SMTP port for the server
		} else {
			$mail->IsMail(); 
		}
		
		// Embed all imgs to BODY		
		$mail->IsHTML(true);
		$mail->CharSet 	= "UTF-8";
		$mail->Encoding = "quoted-printable";
		// Retrieve all img src tags and replace them get all img tags
		preg_match_all('/<img.*?>/', $body, $matches);
		if (isset($matches[0])) {
			$i = 1;
			foreach ($matches[0] as $img) { // foreach tag, create the cid and embed image			
				$id = 'img'.($i++); // make cid
				preg_match('/src="(.*?)"/', $img, $m); // replace image web path with local path
				$mail->AddEmbeddedImage($this->configuration['attachImagesPath'].$m[1], $id, 'attachment', 'base64', 'image/jpeg'); // add
				$body = str_replace($m[1], 'cid:'.$id, $body);
			};
		}

		$mail->From = $this->configuration['smtpFromAddress'];
		$mail->FromName = $this->configuration['smtpFromName'];
		//$mail->AddReplyTo($this->configuration['smtpFromAddress,$this->configuration['smtpFromName);		
		$mail->Subject    = $subject;
		$mail->AddAddress($emailTo);		
		$mail->MsgHTML($body);

		if(!$mail->Send()) {
			$this->errorMessage = "Mailer Error: " . $mail->ErrorInfo;
			return false;
		} else {
			$this->errorMessage = "";
			return true;
		}				
	}
	
	public function getErrorMessage() {
		return $this->errorMessage;	
	}
}
?>