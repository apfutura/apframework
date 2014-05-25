<?php
class apHtmlUtils {
	static $tableBrowseImages = array("images/icons/next.png","images/icons/back.png","images/icons/first.png","images/icons/last.png");
	
	static function inputHtml($inputId,$value, $pEditable = true ,$pVisible = true ,$pDescrip = "",$pOnchange = "",$attributes = array(), $classes = array() ) {		
		return self::inputHtmlEx( 'text', $inputId, $value, $pEditable , $pVisible , $pDescrip , $pOnchange , $attributes , $classes );
	}
	
	static function inputHtmlEx($type , $inputId,  $value, $pEditable = true ,$pVisible = true ,$pDescrip = "",$pOnchange = "",$attributes = array(), $classes = array() ) {
		$str_descrip = "";
		if ($pVisible==true) {			
			if ($pDescrip!="") {
				$str_descrip=$pDescrip;
			}
			if ($pEditable==true) {
				$str_readonly="";
			} else {
				$str_readonly="readonly='READONLY'";
			}
			$controlHtml="<input type='".$type."' class='" . implode(" ",$classes) . "' id='" . $inputId . "' name='" . $inputId . "' value='" . $value . "' placeholder='".$str_descrip."' onchange=\"" . $pOnchange . "\" " . $str_readonly . " ".implode(" ",$attributes).">";
		}	else {
			$controlHtml="<input type=\"hidden\" id=\"" . $inputId . "\" name=\"" . $inputId . "\"  value=\"" . $value . "\"  placeholder='".$str_descrip."' onchange=\"" . $pOnchange . "\" ".implode(" ",$attributes).">";
		}
		return $controlHtml;
	}
	
	static function textareaHtml($inputId,$value, $pEditable = true ,$pVisible = true ,$pDescrip = "",$pOnchange = "",$attributes = array(), $classes = array() ) {
		if ($pVisible==true) {
			$str_descrip = "";
			if ($pDescrip!="") {
				$str_descrip=$pDescrip;
			}
			if ($pEditable==true) {
				$str_readonly="";
			} else {
				$str_readonly="readonly='READONLY'";
			}
			$controlHtml="<textarea class='" . implode(" ",$classes) . "' id='" . $inputId . "' name='" . $inputId . "' placeholder='".$str_descrip."' onblur=\"" . $pOnchange . "\" " . $str_readonly . " ".implode(" ",$attributes).">".$value."</textarea>";
		}	else {
			$controlHtml="<textarea class='" . implode(" ",$classes) . "' id='" . $inputId . "' name='" . $inputId . "' placeholder='".$str_descrip."' onblur=\"" . $pOnchange . "\" " . $str_readonly . " ".implode(" ",$attributes)." style='display:none'>".$value."</textarea>";
		}
		return $controlHtml;
	}
	
	static function labelHtml($text ,$for="", $attributes = array(), $classes = array() ) {
		$controlHtml='<label for="'.$for.'" class="'. implode(" ",$classes) . '" '. implode(" ",$attributes) . '>'.$text."</label>";
		return $controlHtml;
	}

	static function comboHtml_fromDirectory($dir, $pattern, $name, $selected_file,$onChange,$html_class='',$html_stlye='', $array_attributes = null, $array_attributes_select = array() ) {
		$files  = array();
		if ($handle = opendir($dir)) {			
			foreach (glob($dir.$pattern) as $file) {
				if ($file != "." && $file != ".." ) {
					$files[] = basename($file);
				}
			}
			closedir($handle);
		}
		return self::comboHtml_fromArray($name,$files,$files,$selected_file,$onChange,$html_class,$html_stlye, $array_attributes , $array_attributes_select  ); 
	}
	
	
	static function comboHtml_fromElements($arrayOfTypes, $name,$selected_id,$onChange,$html_class='',$html_stlye='',$idFieldname = 'id_element', $option_generic_attributes = null, $array_attributes_select = array(), $array_row_attributes = array() ) {
		// ***** Elements COMBO *****
		if (strlen($html_class)==0){
			$html_class = 'stylishCombo';
		}
		$n = new network();
		$elements = $n->getElementsByTypes($arrayOfTypes,null,null,false);
		$a_e = array();
	
	
		$a_f["names"][] = getLangConstant('L_SELECT');
		$a_f["id_elements"][] = null;
	
		foreach ($elements as $e) {
			if (is_array($e)) {
				$a_f["names"][] = $e["short_name"]." (".getLangConstant("L_".$e["element_type"]).")";
				$a_f["id_elements"][] = $e[$idFieldname];
			} else {
				$a_f["names"][] = $e->get_property("short_name")." (".getLangConstant("L_".$e->get_property("element_type")).")";
				$a_f["id_elements"][] = $e->get_property($idFieldname);
			}
	
		}
		$htmlCombo = apHtmlUtils::comboHtml_fromArray($name, $a_f["id_elements"], $a_f["names"], $selected_id, $onChange, $html_class,$html_stlye, $option_generic_attributes , $array_attributes_select , $array_row_attributes );
		return $htmlCombo;
	}
	
	static function comboHtml_fromArray($nom,$array_valors = array(""),$array_ensenya = array(""),$seleccionat,$onchange,$html_class='',$html_stlye='', $option_generic_attributes = null, $array_attributes_select = array(), $array_row_attributes = array()) {
		if (strlen($html_class)==0){
			$html_class = 'stylishCombo';
		}		
		$retorna="<select id=\"$nom\" name=\"$nom\" class=\"$html_class\" style=\"$html_stlye\" onchange=\"$onchange\" ".implode(" ",$array_attributes_select).">";
		$num=count($array_valors);
		$i=0;
		while ($i<$num)
		{
			if ($seleccionat==$array_valors[$i]) {
				$tmpsel="selected='SELECTED'";
			} else {$tmpsel="";
			}			
			if ($option_generic_attributes!=null) {
				if (is_array($option_generic_attributes)) {
					$tmpatt=implode(" ",$option_generic_attributes);
				} else {
					$tmpatt=" ".$option_generic_attributes;
				}				
			} else {
				$tmpatt="";
			}
			if (count($array_row_attributes)>0) {
				$tmpRowAtt=" ".$array_row_attributes[$i];
			} else {
				$tmpRowAtt="";
			}
			$retorna=$retorna."<option value='".$array_valors[$i]."' ".$tmpsel.$tmpatt.$tmpRowAtt.">".$array_ensenya[$i]."</option>\n";
			$i++;
		}		
		$retorna=$retorna."</select>";
		return $retorna;
	}	
	
	static function comboHtml_fromTable($nom,$camp,$camp_id,$text_inicial_combo,$valor_inicial,$taula,$where,$ordre,$seleccionada,$onclick,$html_class='',$html_stlye='', $array_attributes = null, $array_attributes_select = array() ) {
		if (strlen($html_class)==0){
			$html_class = 'stylishCombo';
		}
		$onclick = str_ireplace('"', "'", $onclick);
		
		if (!isset($array_attributes_select['id'])) {
			$tmpId = $nom;
		} else {
			$tmpId = $array_attributes_select['id'];
			unset($array_attributes_select['id']);
		}
		$tmpAttr ="";
		foreach ($array_attributes_select as $k=>$v) {
			$tmpAttr  .= "$k='$v' ";
		}
		//echo $tmpAttr;
		
		$retorna="<select name=\"$nom\" id=\"$tmpId\" class=\"$html_class\" style=\"$html_stlye\" onchange=\"$onclick\" ".$tmpAttr.">";
	
		if (strlen($text_inicial_combo)>0){
			$str_tmp="";
			if (strlen($valor_inicial)>0) $str_tmp=$valor_inicial;
			$retorna=$retorna."<option value=\"".$str_tmp."\">".$text_inicial_combo."</option>";
		}
	
		if (substr($seleccionada,0,2)=="@@") $retorna=$retorna."<option value=\"\">".substr($seleccionada,2,strlen($seleccionada)-2)."</option>";
	
	
	
		$camp_sql=str_replace(";",",",$camp);
		$camp_sql2=str_replace(";",",",$camp_id);
	
		$query ="SELECT $camp_sql,$camp_sql2 FROM $taula ";
		if ($where!="") $query=$query.$where;
		if ($ordre!="") $query=$query.$ordre;
		//echo "<!-- aa ".$query."-->";
		$result=UTILS::fesSQL($query, PDO::FETCH_BOTH);
		 
		$num=count($result);
		$i=0;
		while ($i<$num)
		{
			$row=$result[$i];
			$txt_str="";
			$camps = explode(",", $camp);
			for ($zz=0;$zz<count($camps);$zz++)
			{
			$txt_str=$txt_str.$row[$zz]. " - ";
			}
			$txt_str=substr($txt_str,0,strlen($txt_str)-2);
			 
			$pos = strpos($camp, " as ");
			$pos2 = strpos($camp_id, " as ");
			if ($pos >0 ) {
			$camp=substr($camp,$pos+4,strlen($camp)-($pos+4));
			}
			if ($pos2 >0 ) {
			$camp_id=substr($camp_id,$pos+4,strlen($camp_id)-($pos+4));
			}
			 
			if ($row[$camp]==$seleccionada) {
			$tmpsel="selected='SELECTED'";
		} else {$tmpsel="";
		}
			if ($row[$camp_id]==$seleccionada) {
			$tmpsel="selected='SELECTED'";
	} else {$tmpsel="";
	}
	
	if ($array_attributes!=null) {
		$tmpatt=" ".$array_attributes[$i];
	} else {
		$tmpatt="";
	}
	$retorna=$retorna."<option value=\"".$row[$camp_id]."\" ".$tmpsel.$tmpatt.">".$txt_str."</option>";
	$i=$i+1;
	}
	$retorna=$retorna."</select>\n";
	return $retorna;
	}

	
	static function tableHtml_fromSQL($apDb, $sql, $htmlAttributes = array(), $config = array()) {
				
		$result=$apDb->query($sql, PDO::FETCH_ASSOC);			
		$num=count($result);
		$i=0;
		$resultBody = "";
		$resultHeader = "";
		$response = "";
		
		$tableName = (isset($htmlAttributes['name'])?$htmlAttributes['name']:crc32($sql));
		$htmlAttributes['name'] = $tableName;
		if (isset($config['label'])) {
				$response  = self::labelHtml($config['label'], $tableName );
		}
		
		while ($i<$num)	 {
			$record = $result[$i];
			
			if ($i==0) {
				$fields = array_keys($record);
				$resultHeader .= "<TR>";
				foreach ($fields  as $field) {
					$resultHeader .= "<TH>".$field."</TH>";
				}
				$resultHeader .= "</TR>";
				if (!isset($config['hide_count'])) {
					$resultHeader .= "<TR>";
					$resultHeader .= "<TH STYLE='text-align:right;' COLSPAN='".count($fields)."'>".$num.' {$L_RECORDS}</TH>';
					$resultHeader .= "</TR>";
				}
			}
			$rowAtributes = "";
			if (isset($config['keyField'])) {
				$keyField = $config['keyField'];
				//$keyValue = $record[strtolower($keyField)];
				$keyValue = $record[$keyField];
				$rowAtributes = "data-key='$keyValue'";
			}
			$resultBody .= "<TR $rowAtributes>";
			foreach ($fields  as $field) {
				$resultBody .= "<TD>".$record[$field]."</TD>";
			}			
			$resultBody .= "</TR>";
			$i++;
		}
		
		$htmlAttribute = "";
		foreach ($htmlAttributes as $attributeName => $attributeValue) {
			$htmlAttribute .= $attributeName.'="'.$attributeValue.'" ';
		}
			
		$response .= "<TABLE ".$htmlAttribute." data-rows=\"$num\"><THEAD>".$resultHeader."</THEAD><TBODY>".$resultBody."</TBODY></TABLE>";
		return $response;
	}

	
	static function tableHtml_fromArray($array, $htmlAttributes = array(), $config = array()) {
		if (!is_array($array)) $array = array();
		$num=count($array);
		$i=0;
		$resultBody = "";
		$resultHeader = "";		
		while ($i<$num)	 {
			$record = $array[$i];			
			if (isset($config['additional_columns'])) {
				$record = $record + $config['additional_columns'];
			}
			
			if ($i==0) {
				$fields = array_keys($record);
				$resultHeader .= "<TR>";
				foreach ($fields  as $field) {
					$resultHeader .= "<TH>".$field."</TH>";
				}
				$resultHeader .= "</TR>";
				if (!isset($config['hide_count'])) {
					$resultHeader .= "<TR>";
					$resultHeader .= "<TH STYLE='text-align:right;' COLSPAN='".count($fields)."'>".$num.' {$L_RECORDS}</TH>';
					$resultHeader .= "</TR>";
				}
			}
			$rowAtributes = "";
			if (isset($config['keyField'])) {
				$keyField = $config['keyField'];				
				//$keyValue = $record[strtolower($keyField)];
				$keyValue = $record[$keyField];
				$rowAtributes = "data-key='$keyValue'";
			}			
			$resultBody .= "<TR $rowAtributes>";
			$cellAtributes = "";
			foreach ($fields  as $field) {
				if (isset($config['set_key_name'])) {
					$cellAtributes = "data-field='$field'";
				}
				$resultBody .= "<TD $cellAtributes>".$record[$field]."</TD>";
			}
			$resultBody .= "</TR>";
			$i++;
		}
	
		$htmlAttribute = "";
		foreach ($htmlAttributes as $attributeName => $attributeValue) {
			$htmlAttribute .= $attributeName.'="'.$attributeValue.'" ';
		}
			
		$result = "<TABLE ".$htmlAttribute."><THEAD>".$resultHeader."</THEAD><TBODY>".$resultBody."</TBODY></TABLE>";
		return $result;
	}
	
	static function htmlNavigator($url,$offset,$limit,$registersCount,$registersDisplayed, $params = array()) {
		$backLimit = ($offset-$limit);
		$nextLimit = ($offset+$limit);
		$lastLimit = floor(($registersCount - 1) / $limit)  * $limit;
		$pages = $registersCount / $limit;
		$currentPage = floor($offset / $limit);
		
		if (!isset($params["offsetVarName"])) {
			$offsetVarName = "offset";
		} else {
			$offsetVarName = $params["offsetVarName"];
		}
		// TODO: take info to construct it from params
		$nextImg = '<img src="' . self::$tableBrowseImages[0]  .'">';
		$backImg = '<img src="' . self::$tableBrowseImages[1]  .'">';
		$firstImg = '<img src="' . self::$tableBrowseImages[2]  .'">';
		$lastImg = '<img src="' . self::$tableBrowseImages[3]  .'">';

		$idHtmlNavigator = "idHtmlNavigator" . rand(0, 100000);
		$script ="";
		$html = "<div id='$idHtmlNavigator' data-url='$url&$offsetVarName=' style='border: 1px solid #transparent; width: 220px; position: relative; min-height: 28px;margin-left:auto;margin-right:auto;margin-top:10px;font-size:1.1em;border: 1px solid grey;padding: 3;top: -10;border-top: none;border-radius: 0 0 4px  4px;background: ghostwhite;'><span>";
		
		if (!isset($params["ajaxLoad"])) {
			
			if ($pages>1) {
				$pageOptions = "";
				for ($x=0;$x<$pages ;$x++)  {
					if ($currentPage==$x) {
						$tmpSelectd = 'selected="SELECTED"';
					} else {
						$tmpSelectd = '';
					}
					$pageOptions .= '<option '.$tmpSelectd.' value="'.($x*$limit).'"> {$L_PAGE} -'.($x + 1).'-</option>';
				}
				$htmlPages = '<select onchange="document.location.href=\''.$url."&".$offsetVarName."=".'\'+this.options[this.selectedIndex].value;">'.$pageOptions."</select>";
			} else {
				$htmlPages = '';
			}
			
			if ($backLimit >=0 ) {
				$html .= "<div style='float:left;margin-top:5px;'> <a href='".$url."&".$offsetVarName."=0'> <div class='firstImage'> ".$firstImg." </div> </a></div>&nbsp;";
				$html .= "<div style='float:left;margin-top:5px;'> <a href='".$url."&".$offsetVarName."=".$backLimit."'> <div class='previousImage'> ".$backImg." </div> </a></div>";
			}	
			
			if ($registersCount == 0) {
				$html .= ' <div style="left: 60px;position: absolute;top: 10px;width:100px;text-align:center;">{$L_NORECORDS}</div> ';
			} else {
				$html .= ' <div style="left: 60px;position: absolute;top: 10px;width:100px;text-align:center;">'.($offset + 1).' - '.($nextLimit>$registersCount?$registersCount:$nextLimit).' {$L_HTMLNAVIGATOR_OF} '.$registersCount.'<br />'.$htmlPages.'</div> ';
			}		
			
			if ($offset + $registersDisplayed < $registersCount ) {
				$html .= '<div style="float:right; margin-top:5px;"><a href="'.$url.'&'.$offsetVarName.'='. $lastLimit .'"><div class="lastImage"> '.$lastImg.'</div></a></div>';
				$html .= '<div style="float:right; margin-top:5px;"><a href="'.$url.'&'.$offsetVarName.'='.$nextLimit.'"><div class="nextImage">'.$nextImg.'</div></a>';
				$html .= "</div>&nbsp;";
			}
			
		} else {
			
			if ($pages>1) {
				$pageOptions = "";
				for ($x=0;$x<$pages ;$x++)  {
					if ($currentPage==$x) {
						$tmpSelectd = 'selected="SELECTED"';
					} else {
						$tmpSelectd = '';
					}
					$pageOptions .= '<option '.$tmpSelectd.' value="'.($x*$limit).'"> {$L_PAGE} -'.($x + 1).'-</option>';
				}				
				$htmlPages = '<select id="'.$idHtmlNavigator.'_pages" class="navigator">'.$pageOptions."</select>";
			} else {
				$htmlPages = '';
			}
				
			if ($backLimit >=0 ) {
				$html .= '<div style="float:left;margin-top:5px;"> <span id="'.$idHtmlNavigator.'_first" class="navigator" data-offset="0"> <div class="firstImage"> '.$firstImg.' </div> </span></div>&nbsp;';
				$html .= '<div style="float:left;margin-top:5px;"> <span id="'.$idHtmlNavigator.'_previous" class="navigator" data-offset="'.$backLimit.'"> <div class="previousImage"> '.$backImg.' </div> </span></div>';
			}
			
			if ($registersCount == 0) {
				$html .= ' <div style="left: 60px;position: absolute;top: 10px;width:100px;text-align:center;">{$L_NORECORDS}</div> ';
			} else {
				$html .= ' <div style="left: 60px;position: absolute;top: 10px;width:100px;text-align:center;">'.($offset + 1).' - '.($nextLimit>$registersCount?$registersCount:$nextLimit).' {$L_HTMLNAVIGATOR_OF} '.$registersCount.'<br />'.$htmlPages.'</div>';
			}
			
				
			if ($offset + $registersDisplayed < $registersCount ) {
				$html .= '<div style="float:right; margin-top:5px;"><span id="'.$idHtmlNavigator.'_last" class="navigator" data-offset="'.$lastLimit.'"><div class="lastImage"> '.$lastImg.'</div></span></div>';
				$html .= '<div style="float:right; margin-top:5px;"><span id="'.$idHtmlNavigator.'_next" class="navigator" data-offset="'.$nextLimit.'"><div class="nextImage">'.$nextImg.'</div></span></div>&nbsp;';
			}
			$script = "
			<script>
					$(function() {
						var ajaxPopulateElement = '".$params["ajaxPopulateElement"]."';
						
						$('#$idHtmlNavigator span.navigator').off().on('click', function() {
							var url = $('#$idHtmlNavigator').attr('data-url') +  $(this).attr('data-offset');							
							getJSON(url, function(response) {
								if (response.result) {
									$('#' + ajaxPopulateElement).html(response.data);
								}
							});							
						});
						$('#$idHtmlNavigator select.navigator').off().on('change', function() {
							var url =  $('#$idHtmlNavigator').attr('data-url') + $(this).val();							
							getJSON(url, function(response) {
								if (response.result) {
									$('#' + ajaxPopulateElement).html(response.data);
								}
							});							
						});
					});					
			</script>";
		}

		$html .= '<div style="clear:both; display:block;">&nbsp;</div></span></div>';		
		$html .= $script;
		
		return $html;
	}
	
}