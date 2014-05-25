<?php
class apMntFormField  {
	public $name   = "";
	public $label  = "";
	public $table   = "";
	public $mntObject   = "";
	public $visible = true;
	public $filtrable = false;
	public $editable = false;
	public $editableInForm = false;
	public $customValueFunction = null;
	public $customRenderFunction = null;
	public $customAttributesFunction = null;
	public $customHeaderRenderFunction = null;
	public $customFormRenderFunction = null;
		
	
	function __construct($name,$label, $filtrable = false, $editable = false, $visible=true, $editableInForm = true) {
		$this->name = $name;
		$this->label = $label;
		$this->filtrable= $filtrable;
		$this->editable= $editable;
		$this->editableInForm = $editableInForm;
		$this->visible = $visible;
	}
	
	function __toString() {
		return $this->name;
	}
		
	function getValue($record) {
		if ($this->customRenderFunction==null) {	
			if ($this->customValueFunction==null) {
				if (!property_exists($record, $this->name)) {
					throw new Exception("Missing Field '".$this->name. "' of apMntObject for table '".$this->table."' and data source '".$this->mntObject."'");
				}
				return $record->{$this->name};
			} else {
				$func = $this->customValueFunction;
				return $func($record->{$this->name});
			}
		} else {
			$func = $this->customRenderFunction;
			return $func($record);
		}
	}
	
	function getAttributes($record) {
		if ($this->customAttributesFunction==null) {
			return array();
		} else {
			$func = $this->customAttributesFunction;
			return $func($record);
		}
	}
	
	function getHeader() {
		if ($this->customHeaderRenderFunction==null) {
			return  $this->label;
		} else {
			$func = $this->customHeaderRenderFunction;
			return $func();
		}
	}
	
	function isActionField() {
		return substr($this->name, 0,1) == "@";
	}
	
	
}