<?php

class apImportField
{
    public $name = "";
    public $fieldname = "";
    public $required = false;
    public $builtInChecks = null;

    public $customImportFunction = null;

    function __construct($name, $required = false, $fieldname = "", $builtInChecks = null)
    {
        $this->name = $name;
        $this->fieldname = ($fieldname ? $fieldname : $name);
        $this->required= $required;
        $this->builtInChecks = $builtInChecks;
    }

    function checkField($input)
    {
        $result = false;
        $data = null;
        $message = "";
        if ($this->required) {
            if (strlen($input) > 0) {
                if ($this->builtInChecks) {   // If builtin check are in place this will be made, and will NOT continue to the custom ones if not meet
                  $this->_doBuiltInChecks($input, $result, $data, $message);
                  if ($result) $this->_applyCustomImportFunction($input, $result, $data, $message);
                } else {
                  $this->_applyCustomImportFunction($input, $result, $data, $message);
                }

            } else {
                $result = false;
                $message = "Required field '".$this->name. "' is empty";
            }
        } else {
            if (strlen($input) > 0) {
              if ($this->builtInChecks) {   // If builtin check are in place this will be made, and will NOT continue to the custom ones if not meet
                $this->_doBuiltInChecks($input, $result, $data, $message);
                if ($result) $this->_applyCustomImportFunction($data, $result, $data, $message); // First parameter is $data and not $input because _doBuiltInChecks can modify de default input
              } else $this->_applyCustomImportFunction($input, $result, $data, $message);
            } else {
              $this->_applyCustomImportFunction($input, $result, $data, $message);
            }
        }
        return ["result" => $result, "data" => $data, "message" => $message];
    }

    function _applyCustomImportFunction($input, &$result, &$data, &$message)
    {
        if ($this->customImportFunction == null) {
            $result = true;
            $data = $input;
        } else {
            $func = $this->customImportFunction;
            $result = $func($input);
            if (is_array($result) && array_key_exists("result", $result)) {
              $message = $result["message"];
              $data = $result["data"];
              $result = $result["result"];
            } else if ($result !== false) {
                $message = "";
                $data = $result;
                $result = true;
            } else {
              $message = "Could not process field '".$this->name. "'". print_r($result, true);
              $result = false;
            }
        }
    }

    function _doBuiltInChecks($input, &$result, &$data, &$message)
    {
        if (array_key_exists("is_numeric", $this->builtInChecks) && $this->builtInChecks['is_numeric']==true) {
            $result = is_numeric($input);
            $data = $input;
            if (!$result) $message = "Check is_numeric error for field '".$this->name. "' and value '" . $input . "'";
        } else if (array_key_exists("dateto_yyyymmdd", $this->builtInChecks) && $this->builtInChecks['dateto_yyyymmdd']==true)  {
            $result = $this->_dateto_yyyymmdd($input);
            $data = $result;
            if (!$result) $message = "Check date error for field '".$this->name. "' and value '" . $input . "'";
        }
        return;
    }

    private function _dateto_yyyymmdd($date){
      $date = str_replace(array('.', '-', '\\\\'), '/', $date);
  		$dateParts = explode("/", $date);
  		$result = false;
  		if (count($dateParts)==3 &&
  				is_numeric($dateParts[0]) && is_numeric($dateParts[1]) && is_numeric($dateParts[2]) &&
  				$dateParts[0] > 0 && $dateParts[0] < 32 &&
  				$dateParts[1] > 0 && $dateParts[1] < 13 &&
  				$dateParts[2] > 1000 && $dateParts[2] < 9999) {
  					$result = $dateParts[2] . '/' . $dateParts[1]			. '/' . $dateParts[0];
  		}
      return $result;
    }

}
