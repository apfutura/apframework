<?php
class apAuthToken {
		
		static function generateToken($user, $operation = "*", $length = 128, $timeOut = 900) {
			$randomId = UTILS::generateRandomString($length);
			$file = CONFIG::$pathTmp.$randomId.".token";
			$tokenInfo = (time() + $timeOut) . "|" . $operation . "|" . $user;
			file_put_contents($file, $tokenInfo); 
			return $randomId;
		}	
		
		static function validate($token , $operation = "*") {
			$resetFilename = CONFIG::$pathTmp.$token.".token";
			$dateTime = @filemtime($resetFilename);
			$str = trim(file_get_contents($resetFilename));
			$strA = explode("|",$str);			
			$tokenTime =  $strA[0];
			$tokenOperation =  $strA[1];
			$tokenUser =  $strA[2];			
			$now = time();
			_LogToApache("Found user: $tokenUser operation: $tokenOperation in $token\n");
			if ($now < $tokenTime ) {
				if ($tokenOperation=='*') {
					return $tokenUser;
				} else if ($operation == $tokenOperation) {
					return $tokenUser;
				} else {
					if (file_exists($resetFilename)) unlink($resetFilename);
					_LogToApache("Invalid Auth Token Operation \n");
					return false;
				}
			} else {
				if (file_exists($resetFilename)) unlink($resetFilename);
				_LogToApache("Outdated Auth Token ($now < $tokenTime == false) \n");
				return false;				
			} 
		}	
		
}