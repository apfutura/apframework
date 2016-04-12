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
            if ($this->builtInChecks) {   // If builtin check are in place this will be made, and will NOT continue to the custom ones if not meet
              $this->_doBuiltInChecks($input, $result, $data, $message);
              if ($result) $this->_applyCustomImportFunction($input, $result, $data, $message);
            } else $this->_applyCustomImportFunction($input, $result, $data, $message);
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
            $data = $result;
            if (!$result) $message = "Check is_numeric error for field '".$this->name. "' and value '" . $data . "'";
        }
        return;
    }

}
