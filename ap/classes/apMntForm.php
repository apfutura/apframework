<?php
class apMntForm {
	public $table		= "";
	public $maxRegisters	= 50;
	public $fields		= array();
	//HTML urls
	public $urlNavigator	= "";	
	public $urlEditForm = "";
	public $urlAddForm = "";
	public $urlIconsImg	= "";
	//Ajax urls
	public $urlRefreshHTML	= "";
	public $urlInsert = "";
	public $urlUpdate	= "";
	public $urlDelete	= "";
		
	public $dataSource	= null;
	public $customRowAttributesFunction = null; 
	
	function __construct($table, $maxRegisters, $dataSource) {
		$this->table = $table;
		$this->dataSource = $dataSource;
		$this->maxRegisters = $maxRegisters;
	}
	
	function getFilterFields() {
		$result = array();
		foreach ($this->fields as $field) {
			if ($field->filtrable) {
				$result[$field->name] = $field;
			}
		}
		return $result;	
	}
	
	function addField($field) {		
		if ($field instanceof apMntFormField ) {
			$field->table = $this->table;
			$field->mntObject = get_class($this->dataSource);
			$this->fields[$field->name] = $field;
		} else {
			 throw new Exception('apMntForm::addField requires an apMntFormField');
		}
	}
	
	function getFields() {		
		return $this->fields;
	}
	
	function getField($name) {		
		return $this->fields[$name];
	}
	
	function getHtmlTableParams() {
		$offset = apUtils\getParam( $_REQUEST,$this->table ."Offset", apSession::getFromSession($this->table ."Offset", false, 0) );
		if ($offset!=0) apSession::setToSession($this->table ."Offset", $fieldOrderBy);
		
		$fieldOrderBy = apUtils\getParam( $_REQUEST, $this->table."FieldOrderBy", apSession::getFromSession($this->table."FieldOrderBy", false, null) );
		if ($fieldOrderBy!=null) apSession::setToSession($this->table."FieldOrderBy", $fieldOrderBy);
		
		$filterFieldsAssociativeArray = apSession::getFromSession($this->table ."filterFields", false, array()) ;
		foreach ($this->getFilterFields() as $filterField) {
			$valueFilter = apUtils\getParam( $_REQUEST, "filterField_".trim($filterField), null);
			if ( !is_null($valueFilter) ) {
				if ($valueFilter == '') {
					unset($filterFieldsAssociativeArray[trim($filterField)]);
				} else {
					$filterFieldsAssociativeArray[trim($filterField)] = $valueFilter;
				}
			}
		}
		apSession::setToSession($this->table ."filterFields", $filterFieldsAssociativeArray);
		return array($offset, $fieldOrderBy, $filterFieldsAssociativeArray);
		
	}
	
	function printHtmlTable($offset = 0, $fieldOrderBy = "", $filterAssociativeArray = array(), $return = false, $params = array()) {
		$limit = $this->maxRegisters;		
		$filterWhere = null;
		if (isset($params["filterWhere"])) $filterWhere = $params["filterWhere"];
		$records = $this->dataSource->getPartialList($offset, $limit, $fieldOrderBy,$filterAssociativeArray, $filterWhere );		
		$recordsCount = $this->dataSource->getTotalElements();
		
		if ($offset>$recordsCount ) $offset=0; 
		$html = '<span id="entities_wrapper_'.$this->table.'"><style>th[data-field] {background-image:url( "{$C_urlBase}images/icons/az.gif");background-repeat:no-repeat;background-position: 4px center;	min-width: 45px;}</style>';
		if ($this->urlAddForm!="") {
			$html .= '<div><a href="'.$this->urlAddForm.'" class="button">{$L_ADD}</a></div><br />';
		}
		$html .= '<table id="entities" data-table="'.$this->table.'" data-fieldOrderBy="'.$fieldOrderBy.'" data-filter="' .htmlspecialchars(json_encode($filterAssociativeArray)).'" data-urlRefreshHTML="' .htmlspecialchars($this->urlRefreshHTML).'"  data-urlEditForm="' .htmlspecialchars($this->urlEditForm).'"  data-urlUpdate="' .htmlspecialchars($this->urlUpdate).'"  data-urlDelete="' .htmlspecialchars($this->urlDelete).'" class="ui-widget ui-widget-content entities"><thead>
			<tr class="ui-widget-header ">';

		$fields = $this->getFields();
		foreach ($fields  as $field)  {
			if ($field->visible) {
				$html .= '<th data-fieldName="'.$field.'" '.($field->filtrable?'data-field="'.$field.'"':'').'>'.$field->getHeader().'</th>';
			}			
		}
		$html .= '</tr></thead><tbody>';
		
		foreach ($records as $entity)  {
			$html .= '<tr '.implode(' ',$this->getRowAttributes($entity)).'>';
			foreach ($fields  as $field)  {		
				if ($field->visible) {	
					if ($field->customRenderFunction == null) {
						$html .= '<td data-fieldName="'.$field->name.'" '.implode(' ',$field->getAttributes($entity)).'><p id="'.$field->name.'_'.$entity->id.'" data-id="'.$entity->id.'" data-field="'.$field->name.'" '.($field->editable?'class="editable"':'').'>'.$field->getValue($entity).'</p></td>';
					} else {
						$html .= '<td data-fieldName="'.$field->name.'" '.implode(' ',$field->getAttributes($entity)).'>'.$field->getValue($entity).'</td>';
					}
				}
			}
			$html .= '</tr>';	
		}

		$html .= '</tbody></table>';
		$navigatorOptions = array("offsetVarName" => $this->table . "Offset",  "ajaxPopulateElement" => 'entities_wrapper_'.$this->table, "urlBaseIMG" => $this->urlIconsImg);
		if (isset($params["ajaxLoad"])) $navigatorOptions["ajaxLoad"] = true;
		$html .= apHtmlUtils::htmlNavigator($this->urlNavigator, $offset, $limit, $recordsCount , count($records), $navigatorOptions);
		$html .= '<script type="text/javascript" src="{$C_urlBaseJS}/?apMntForm.js"></script></span>';
		
		if (!$return) echo $html; else return apRender::replaceTemplateVars($html, array());
	}
	
// 	function printAddForm($return = false) {
// 		$html = '<fieldset id="entity" data-urlNavigator="'.htmlentities($this->urlNavigator).'" data-urlInsert="'.htmlentities($this->urlInsert).'">';		
// 		$fields = $this->getFields();
// 		foreach ($fields  as $field)  {
// 			if (!$field->isActionField()) {
// 				$html .= '<div>';
// 				if ($field->customFormRenderFunction == null) {
// 					$html .= apHtmlUtils::labelHtml($field->label,$field->name ) . "<br/>";
// 					$html .= apHtmlUtils::inputHtmlEx("text", $field->name,  /*value*/ "", $field->editableInForm, true ,$field->label, /*Onchange*/ "",/*attributes*/ array(), /*classes*/array() );					
// 				} else {
// 					$func = $field->customFormRenderFunction;					
// 					$html .= $func(); 
// 				}
// 				$html .= '</div>';
// 			}			
// 		}
// 		$html .= '</fieldset><div> <button id="insertEntity">{$L_SAVE}</button><button id="cancelInsertEntity">{$L_CANCEL}</button></div>';
// 		$html .= '<script type="text/javascript" src="{$C_urlBase}js/?apMntForm.js"></script>';
	
// 		if (!$return) echo $html; else return apRender::replaceTemplateVars($html);
// 	}
	
	function printEditForm($id = null, $return = false) {
		$record = $this->dataSource->getElement($id);
		$html = '<fieldset id="entity" data-urlNavigator="'.htmlentities($this->urlNavigator).'" data-urlInsert="'.htmlentities($this->urlInsert).'" data-urlUpdate="'.htmlentities($this->urlUpdate).'">';
		$fields = $this->getFields();
		foreach ($fields  as $field)  {
			if (!$field->isActionField()) {
				$html .= '<div>';
				if ($field->customFormRenderFunction == null) {
					if ($record!=null) {
						$value = $record->{$field->name};
					} else {
						$value = "";
					}
					$html .= apHtmlUtils::labelHtml($field->label,$field->name ) . "<br/>";
					$html .= apHtmlUtils::inputHtmlEx("text", $field->name,  $value, $field->editableInForm, true ,$field->label, /*Onchange*/ "",/*attributes*/ array(), /*classes*/array() );
				} else {
					$func = $field->customFormRenderFunction;
					$html .= $func($record);
				}
				$html .= '</div>';
			}
		}
		if ($record!=null) {
			$html .= apHtmlUtils::inputHtml("id", $record->id, false,false); 
		}
		$html .= '</fieldset><div> <button id="'.( $record==null ? "insertEntity" : "editEntity").'">{$L_SAVE}</button><button id="cancelInsertEntity">{$L_CANCEL}</button></div>';
		$html .= '<script type="text/javascript" src="{$C_urlBaseJS}/?apMntForm.js"></script>';
	
		if (!$return) echo $html; else return apRender::replaceTemplateVars($html);
	}

	function getRowAttributes($record) {
		if ($this->customRowAttributesFunction==null) {
			return array();
		} else {
			$func = $this->customRowAttributesFunction;
			return $func($record);
		}		
	}
}
