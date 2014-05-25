<?php
class apStandardAjaxOperationResponse {
	public $result;
	public $messages = array();
	public function __construct($result, $messages = array()){
		$this->result = $result;
		$this->messages = $messages;
	}
	public function __toString() {
		ob_end_clean();
		return json_encode($this);
	}
}

class apStandardAjaxDataResponse extends apStandardAjaxOperationResponse {
	public $data = array();
	public function __construct($result, $messages = array(), $data = array()){
		$this->data = $data;
		parent::__construct($result,$messages);
	}
}