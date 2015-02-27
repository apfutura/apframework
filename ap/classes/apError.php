<?php
class apError {
	static $warnings = array();
	// CATCHABLE ERRORS
	public static function captureNormal( $number, $message, $file, $line )	{
		$error = array( 'type' => $number, 'message' => $message, 'file' => $file, 'line' => $line );
		$msg = '{$L_ERROR_DETAILS}: <pre>'.print_r( $error , true).'</pre><br/><br/>'.implode("<hr/>",self::$warnings);
		switch ($number) {
			case E_USER_ERROR:
			case E_CORE_ERROR:
			case E_COMPILE_ERROR:
			case E_ERROR:		    	
			case E_PARSE:
				ob_clean();
				if (class_exists('apController')) {				    
					if ( substr(apController::$currentOperation,0, 1)=="_" ) {
						apApplication::showError( new apStandardAjaxOperationResponse(false, array($msg)) );
					} else {
						apApplication::showError("<h3>".apLang::getLangConstant("L_ERROR").":</h3>".$msg);				
					}	
				} else {
					// There an error somewhere in the framework
					apApplication::showError("<h3>".apLang::getLangConstant("L_APFRAMEWORK_ERROR").":</h3>".$msg);
				}
					
				break;
			case E_WARNING:
			case E_USER_WARNING:
				break;
			case E_USER_NOTICE:
				$warnings[] = "<b>WARNING</b> [$number] $msg<br />\n";
				break;
			default:
				$warnings[] = "<b>WARNING</b> [$number] $msg<br />\n";        		
				break;
		}
		return true;
	}

	// EXTENSIONS
	public static function captureException( $exception ) {
		if ( substr(apController::$currentOperation,0, 1)=="_" ) {
			apApplication::showError( new apStandardAjaxOperationResponse(false, array($exception)) );
		} else {
			apApplication::showError('<h3>APFRAMEWORK EXCEPTION ERROR</h3>'.$exception. "(".apController::$currentTask.")");
		}		
	}
	
	public static function captureShutdown( ) {
		$error = error_get_last( );
		if( $error ) {
			self::captureNormal( $error['type'], $error['message'], $error['file'], $error['line'] );
		} else { return true; }
	}

}
set_error_handler( array( 'apError', 'captureNormal' ) );
set_exception_handler( array( 'apError', 'captureException' ) );
register_shutdown_function( array( 'apError', 'captureShutdown' ) );