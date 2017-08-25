<?php
/* Examples of what can be done in a template:

- Include other templates based on variables:
{$include template="{$SECTION}"}

- Conditional content:
{$IF 1==1 THEN}
Always true!
{$ELSE}
Not shown
{$ENDIF}

{$IF {$SHOWIP}==1 THEN}
SHOWIP is passed in to the render: {$IP}
{$ELSE}
No SHOWIP is passed in to the render
{$ENDIF}

- Loops on array variables
{$FOREACH {@USERS2} LOOP}
User  {$@.name}  - {$@.age}</br>
{$ENDLOOP}

{$FOREACH {@USERS} LOOP}
Username  <a href="{$C_urlBase}?username={$@}">{$@}</a>
{$ENDLOOP}
*/
class apRender {

	static public $emptyVars = true;

	static function renderBuffer($KeyValueList = array(), $contentType = null, $return = false) {
		return self::renderCustom("",$KeyValueLis, $contentType, $return);
	}

	static function renderCustom($templateFile="",$KeyValueList = array(), $contentType = null, $return = false, $grabEchoedContents = true) {

		$generatedContent = "";
		$contentSoFar = "";

		if ($grabEchoedContents) {
			$contentSoFar=ob_get_contents();
			$contentSoFar=self::replaceTemplateVars($contentSoFar,$KeyValueList, false, self::$emptyVars);
		}

		if (strlen($templateFile)>0) {

			$templateFile  = self::getTemplatePath($templateFile);
			$templateContents = self::getTemplateContents($templateFile);
			$templateRenderedContents = self::replaceTemplateVars($templateContents,$KeyValueList, false, self::$emptyVars);

			if (!empty($KeyValueList['current_crumb'])) {
				$textTitle = "";
				$textTitle = preg_replace ("'<[/!]*?[^<>]*?>'si","",$KeyValueList['current_crumb']);
				$templateRenderedContents = str_replace('{$TITLE}',$textTitle,$templateRenderedContents);
			}
			$templateRenderedContents = str_replace('{$PAGE}',$contentSoFar,$templateRenderedContents);
			$templateRenderedContents = str_replace('<!-- contents -->',$contentSoFar,$templateRenderedContents);
			$templateRenderedContents = str_replace('{$WINDOWID}',apUtils\getParam($_REQUEST, "ajaxWindow",null),$templateRenderedContents);

			$generatedContent = $templateRenderedContents;

		} else {
			$generatedContent = $contentSoFar;
		}

		if ($return) {
			return $generatedContent;
		} else {
			@ob_end_clean();
			if ($contentType != null ) header('Content-type: '.$contentType);
			Header('Cache-Control: no-cache');
			Header('Pragma: no-cache');
			echo $generatedContent;
		}
	}

	static public function getTemplatePath($templateFile) {
		if (substr(trim($templateFile),0,1) == '@') {
			$templateFile = substr(trim($templateFile),1);
		} else {
			$templateFile  = constant('_GLOBAL_TEMPLATES_DIR').$templateFile;
		}
		return $templateFile;
	}

	static public function getTemplateContents($templateFile) {
		if ( file_exists($templateFile) ) {
			$templateContents = file_get_contents($templateFile);
		} else {
			trigger_error("Template '$templateFile' not found " ,E_USER_ERROR);
		}
		return $templateContents;
	}

	static public function replaceTemplateVars($templateRenderedContents,$KeyValueList, $htmlEntities = false, $emptyVars = true) {

		/*	$templateRenderedContents = str_replace('{$USER_CURRENT}', ApexUser::getCurrentLoggedUserName(), $templateRenderedContents);
		 $templateRenderedContents = str_replace('{$URL_CURRENT}', $_SERVER['PHP_SELF'], $templateRenderedContents);
		$templateRenderedContents = str_replace('{$URL_JS}',CONFIG::$urlBaseJS,$templateRenderedContents);
		$templateRenderedContents = str_replace('{$URL_CSS}',CONFIG::$urlBaseCSS,$templateRenderedContents);
		$templateRenderedContents = str_replace('{$URL_IMG}',CONFIG::$urlBaseIMG,$templateRenderedContents);
		$templateRenderedContents = str_replace('{$URL_BASE}',CONFIG::$urlBase,$templateRenderedContents);*/

		//$templateRenderedContents = str_replace('{$C_style}',CONFIG::$style,$templateRenderedContents);
		//$templateRenderedContents = str_replace('{$C_INSTALLATION}',CONFIG::$installation,$templateRenderedContents);
		foreach (array_keys($KeyValueList) as $key) {
			$value = ($KeyValueList[$key]==null?"":$KeyValueList[$key]);
			if (is_array($value)) {
				$value = print_r($value, true);
			}
			$templateRenderedContents = str_replace('{$'.$key.'}', $value, $templateRenderedContents);
		}

		$start_pos = strpos($templateRenderedContents,'{$L_',0);
		$languageConstants = array();
		while ($start_pos!==false) {
			$end_pos = strpos($templateRenderedContents,"}",$start_pos);
			$languageConstants[] = substr($templateRenderedContents,$start_pos + 2,$end_pos-$start_pos-2);
			$start_pos = strpos($templateRenderedContents,'{$L_',$end_pos + 1);
		}
		foreach ($languageConstants as $constant) {
			$htmlEntities = true;
			if (substr($constant,-2)=='|L' || substr($constant,-2)=='|T' ) {
				$realConstant = substr($constant,0,-2);
				$htmlEntities = (substr($constant,-1)=='L'?false:true);
			} else {
				$realConstant = $constant;
			}
			$templateRenderedContents = str_replace('{$'.$constant.'}', apLang::getLangConstant($realConstant,$htmlEntities),$templateRenderedContents);
		}

		$start_pos = strpos($templateRenderedContents,'{$C_',0);
		$configConstants = array();
		while ($start_pos!==false) {
			$end_pos = strpos($templateRenderedContents,"}",$start_pos);
			$configConstants[] = substr($templateRenderedContents,$start_pos + 2,$end_pos-$start_pos-2);
			$start_pos = strpos($templateRenderedContents,'{$C_',$end_pos + 1);
		}
		foreach ($configConstants as $constant) {
			eval('$constantValue=(isset(CONFIG::$'.substr($constant,2).')?CONFIG::$'.substr($constant,2).':$constant);');
			$templateRenderedContents = str_replace('{$'.$constant.'}',$constantValue,$templateRenderedContents);
		}

	$start_pos = strpos($templateRenderedContents,'{$CDB_',0);
		$configDataBaseConstants = array();
		while ($start_pos!==false) {

			$nestedStarts = strpos($templateRenderedContents,'{$',$start_pos +1 );
			$end_pos = strpos($templateRenderedContents,"}",$start_pos  + 1);
			$last_end = $end_pos;

			while ($nestedStarts!==false && $nestedStarts<$end_pos) {
				$last_end = strpos($templateRenderedContents,"}",$end_pos + 1);
				$nestedStarts = strpos($templateRenderedContents,'{$',$nestedStarts + 1);
			}

			$configDataBaseConstants[] = substr($templateRenderedContents,$start_pos + 6,$last_end-$start_pos-6);
			//echo substr($templateRenderedContents,$start_pos + 6,$end_pos-$start_pos-6)."-------";
			$start_pos = strpos($templateRenderedContents,'{$CDB_',$last_end + 1);
		}

		foreach ($configDataBaseConstants as $constant) {
			$configDatabaseData = explode("|", $constant);
			$defaultValue = null;
			if ( isset($configDatabaseData[2]) ) $defaultValue = $configDatabaseData[2];

			$group  = $configDatabaseData[0];
			$evaluatedGroup = self::replaceTemplateVars($group , $KeyValueList, $htmlEntities, true );

			$variable = $configDatabaseData[1];
			$evaluatedVariable = self::replaceTemplateVars($variable , $KeyValueList, $htmlEntities, true );

			$constantValue = apexConfig::get($evaluatedVariable, $evaluatedGroup, $defaultValue , false );
			$templateRenderedContents = str_replace('{$CDB_'.$constant.'}', $constantValue ,$templateRenderedContents);
		}


		// Process {$IF} as object config static properties
		$start_pos = strpos($templateRenderedContents,'{$IF ',0);
		$ini_pos = 0;
		$x=0;
		while ($start_pos!==false ) {

			$x++;
			$end_pos = strpos($templateRenderedContents,'THEN}',$start_pos);
			$pos_else = strpos($templateRenderedContents,'{$ELSE}',$end_pos);
			$endif_pos = strpos($templateRenderedContents,'{$ENDIF}',$end_pos);
			$nextif_pos = strpos($templateRenderedContents,'{$IF ',$end_pos);
			$thereisElse = ($pos_else  != false) && ($pos_else < $endif_pos);
			$thereisAnnidatedIf = ($nextif_pos? ($nextif_pos < $endif_pos) : false);

			/*IF ($thereisAnnidatedIf) {
			    $insideIf = substr($templateRenderedContents,$nextif_pos,$endif_pos);
			    $evaluatedContent .= self::replaceTemplateVars($insideIf, $KeyValueList, $htmlEntities, $emptyVars);
			}*/

			if ($end_pos !=false && $endif_pos!=false && $thereisAnnidatedIf==false) {
				$condition = substr($templateRenderedContents,$start_pos + 5,$end_pos-$start_pos-5);
				if ($thereisElse) {
					$insideif = substr($templateRenderedContents,$end_pos + 5,$pos_else-$end_pos-5);
					$insideelse = substr($templateRenderedContents,$pos_else + 7,$endif_pos-$pos_else-7);
				} else {
					$insideif = substr($templateRenderedContents,$end_pos + 5,$endif_pos-$end_pos - 5);
					$insideelse ="";
				}

				$evaluatedCondition = self::replaceTemplateVars($condition,$KeyValueList, $htmlEntities, $emptyVars );
				$test = eval('return ' . $evaluatedCondition.";");

				if (!$test) {
					$evaluatedContent = self::replaceTemplateVars($insideelse ,$KeyValueList, $htmlEntities, $emptyVars);
				} else {
					$evaluatedContent = self::replaceTemplateVars($insideif ,$KeyValueList, $htmlEntities, $emptyVars);
				}
				//@ob_end_clean();
				$a = substr($templateRenderedContents, 0, $start_pos);
				$b = $evaluatedContent;
				$c = substr($templateRenderedContents, $endif_pos + 8);
				$templateRenderedContents = $a.$b.$c;
			} else {
			    if ($thereisAnnidatedIf) throw new Exception('apRender syntax error: annidated IF not supported');
			    if (!$end_pos) throw new Exception('apRender syntax error: IF without THEN}');
			    if (!$endif_pos) throw new Exception('apRender syntax error: IF without {$ENDIF}');
			    if ($end_pos && ($end_pos>$pos_else  || $end_pos>$endif_pos) ) throw new Exception('apRender syntax error: IF without ENDIF or incorrect}');
			}
			$ini_pos = $start_pos;
			$start_pos = strpos($templateRenderedContents,'{$IF ',0);
		}


		// Process {$FOREACH xxxx LOOP} as object config static properties
		$start_pos = strpos($templateRenderedContents,'{$FOREACH',0);
		$ini_pos = 0;
		while ($start_pos!==false ) {
			$x++;
			$end_pos = strpos($templateRenderedContents,'LOOP}',$start_pos);
			$endloop_pos = strpos($templateRenderedContents,'{$ENDLOOP}',$end_pos);
			if ($end_pos !=false && $endloop_pos!=false) {
				$array = trim(substr($templateRenderedContents,$start_pos + 9,$end_pos-$start_pos-9));
				$insideloop = substr($templateRenderedContents,$end_pos + 5,$endloop_pos-$end_pos - 5);
				$KeyValueListTmp = $KeyValueList; // Just to make sure that $KeyValueList is left "dirty" with @ and @.xxx for a the following foreachs
				$evaluatedContent = "";
				$arrayValue = $KeyValueListTmp[substr($array,2,-1)];
				foreach ($arrayValue as $value) {
					$KeyValueListTmp["@"] = $value;
					if (is_array($value)) {
						foreach ($value as $fieldKey => $fieldVal ) {
							$KeyValueListTmp["@.".$fieldKey] = $fieldVal;
						}
					} else if (is_object($value)) {
						foreach ($value as $fieldKey => $fieldVal ) {
							$KeyValueListTmp["@.".$fieldKey] = $fieldVal;
						}
					}
					$evaluatedContent .= self::replaceTemplateVars($insideloop, $KeyValueListTmp, $htmlEntities, $emptyVars);
				}
				//@ob_end_clean();
				$a = substr($templateRenderedContents, 0, $start_pos);
				$b = $evaluatedContent;
				$c = substr($templateRenderedContents, $endloop_pos + 10);
				$templateRenderedContents = $a.$b.$c;
			}
			$ini_pos = $start_pos;
			$start_pos = strpos($templateRenderedContents,'{$FOREACH',0);
		}


		// Process {$IF2} as object config static properties
		$start_pos = strpos($templateRenderedContents,'{$IF2',0);
		$ini_pos = 0;
		$x=0;
		while ($start_pos!==false ) {

		    $x++;
		    $end_pos = strpos($templateRenderedContents,'THEN}',$start_pos);
		    $pos_else = strpos($templateRenderedContents,'{$ELSE2}',$end_pos);
		    $endif_pos = strpos($templateRenderedContents,'{$ENDIF2}',$end_pos);
		    $nextif_pos = strpos($templateRenderedContents,'{$IF',$end_pos);
		    $nextif2_pos = strpos($templateRenderedContents,'{$IF2',$end_pos);
		    $thereisElse = ($pos_else  != false) && ($pos_else < $endif_pos);
		    $thereisAnnidatedIf = ($nextif_pos? ($nextif_pos < $endif_pos) : false);
		    $thereisAnnidatedIf = ($thereisAnnidatedIf?true:($nextif2_pos? ($nextif2_pos < $endif_pos) : false));

		    /*IF ($thereisAnnidatedIf) {
		     $insideIf = substr($templateRenderedContents,$nextif_pos,$endif_pos);
		    $evaluatedContent .= self::replaceTemplateVars($insideIf, $KeyValueList, $htmlEntities, $emptyVars);
		    }*/

		    if ($end_pos !=false && $endif_pos!=false && $thereisAnnidatedIf==false) {
		        $condition = substr($templateRenderedContents,$start_pos + 5,$end_pos-$start_pos-6);
		        if ($thereisElse) {
		            $insideif = substr($templateRenderedContents,$end_pos + 5,$pos_else-$end_pos-6);
		            $insideelse = substr($templateRenderedContents,$pos_else + 7,$endif_pos-$pos_else-8);
		        } else {
		            $insideif = substr($templateRenderedContents,$end_pos + 5,$endif_pos-$end_pos - 6);
		            $insideelse ="";
		        }

		        $evaluatedCondition = self::replaceTemplateVars($condition,$KeyValueList, $htmlEntities, $emptyVars );
		        $test = eval('return ' . $evaluatedCondition.";");

		        if (!$test) {
		            $evaluatedContent = self::replaceTemplateVars($insideelse ,$KeyValueList, $htmlEntities, $emptyVars);
		        } else {
		            $evaluatedContent = self::replaceTemplateVars($insideif ,$KeyValueList, $htmlEntities, $emptyVars);
		        }
		        //@ob_end_clean();
		        $a = substr($templateRenderedContents, 0, $start_pos);
		        $b = $evaluatedContent;
		        $c = substr($templateRenderedContents, $endif_pos + 9);
		        $templateRenderedContents = $a.$b.$c;
		    } else {
		        if ($thereisAnnidatedIf) throw new Exception('apRender syntax error: annidated IF or IF2 not supported');
		        if (!$end_pos) throw new Exception('apRender syntax error: IF2 without THEN}');
		        if (!$endif_pos) throw new Exception('apRender syntax error: IF2 without {$ENDIF2}');
		        if ($end_pos && ($end_pos>$pos_else  || $end_pos>$endif_pos) ) throw new Exception('apRender syntax error: IF2 without ENDIF2 or incorrect}');
		    }
		    $ini_pos = $start_pos;
		    $start_pos = strpos($templateRenderedContents,'{$IF2',0);
		}


		// Process {$PHP_xxxxxx} as object config static properties
		$start_pos = strpos($templateRenderedContents,'{$PHP ',0);
		$codeTags = array();
		while ($start_pos!==false) {
			$end_pos = strpos($templateRenderedContents,"}",$start_pos);
			$codeTags[] = substr($templateRenderedContents,$start_pos + 6,$end_pos-$start_pos-6);
			$start_pos = strpos($templateRenderedContents,'{$PHP ',$end_pos + 1);
		}
		foreach ($codeTags as $code) {
			ob_start();
			eval($code);
			$evaluatedContent=ob_get_contents();
			@ob_end_clean();
			$templateRenderedContents = str_replace('{$PHP '.$code.'}',$evaluatedContent,$templateRenderedContents);
		}


		$templateRenderedContents = str_replace('{$IP}',apUtils\getIP(),$templateRenderedContents);
		$templateRenderedContents = str_replace('{$URL_CURRENT}', $_SERVER['PHP_SELF'], $templateRenderedContents);
		$templateRenderedContents = str_replace('{$URL_JS}',CONFIG::$urlBaseJS,$templateRenderedContents);
		$templateRenderedContents = str_replace('{$URL_CSS}',CONFIG::$urlBaseCSS,$templateRenderedContents);
		$templateRenderedContents = str_replace('{$URL_IMG}',CONFIG::$urlBaseIMG,$templateRenderedContents);
		$templateRenderedContents = str_replace('{$URL_BASE}',CONFIG::$urlBase,$templateRenderedContents);


		// Process {$include file='xxxxx'}
		$start_pos = strpos($templateRenderedContents,'{$include template=',0);
		while ($start_pos!==false) {
			$end_pos = strpos($templateRenderedContents,"\"}",$start_pos);
			$includeFile = substr($templateRenderedContents,$start_pos + 20,$end_pos-$start_pos-20);
			$includeFileContents =  file_get_contents(constant('_GLOBAL_TEMPLATES_DIR').$includeFile) ;
			$completeIncludeTag = substr($templateRenderedContents,$start_pos,$end_pos);
			$includeFileContents = self::replaceTemplateVars($includeFileContents,$KeyValueList, $htmlEntities, $emptyVars);
			$templateRenderedContents = substr($templateRenderedContents,0,$start_pos).$includeFileContents.substr($templateRenderedContents,$end_pos+2,strlen($templateRenderedContents)-$end_pos-2);
			$start_pos = strpos($templateRenderedContents,'{$include template=',$end_pos + 1);
		}

		// Process {include file='xxxxx'}
		$start_pos = strpos($templateRenderedContents,'{$include file=',0);
		while ($start_pos!==false) {
			$end_pos = strpos($templateRenderedContents,"$literalVars\"}",$start_pos);
			$includeFile = substr($templateRenderedContents,$start_pos + 16,$end_pos-$start_pos-16);
			$includeFileContents =  file_get_contents($includeFile) ;
			$completeIncludeTag = substr($templateRenderedContents,$start_pos,$end_pos);
			$includeFileContents = self::replaceTemplateVars($includeFileContents,$KeyValueList, $htmlEntities, $emptyVars);
			$templateRenderedContents = substr($templateRenderedContents,0,$start_pos).$includeFileContents.substr($templateRenderedContents,$end_pos+2,strlen($templateRenderedContents)-$end_pos-2);
			$start_pos = strpos($templateRenderedContents,'{$include file=',$end_pos + 1);
		}


		//emtpy non replaced vars
		if ($emptyVars) {
			$start_pos = strpos($templateRenderedContents,'{$',0);
			$missingVars = array();
			while ($start_pos!==false) {
				$end_pos = strpos($templateRenderedContents,"}",$start_pos) + 1;
				$missingVars[] = substr($templateRenderedContents,$start_pos + 2 ,$end_pos-$start_pos - 3);
				$start_pos = strpos($templateRenderedContents,'{$',$end_pos + 1);
			}
			foreach ($missingVars as $constant) {
				$templateRenderedContents = str_replace('{$'.$constant.'}',"",$templateRenderedContents);
			}
		}

		//unescape literal {\$
		$templateRenderedContents = str_replace('{\$','{$',$templateRenderedContents);

		return $templateRenderedContents;
	}
}
