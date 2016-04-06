<?php

class apImportField
{
    public $name = "";
    public $fieldname = "";
    public $required = false;

    public $customImportFunction = null;

    function __construct($name, $required = false, $fieldname = "")
    {
        $this->name = $name;
        $this->fieldname = ($fieldname ? $fieldname : $name);
        $this->required= $required;
    }

    function checkField($input)
    {
        $result = false;
        $data = null;
        if ($this->required) {
            if (strlen($input) > 0) {
                $this->_applyCustomImportFunction($input, $result, $data);
            } else {
                $result = false;
            }
        } else {
            $this->_applyCustomImportFunction($input, $result, $data);
        }
        return ["result" => $result, "data" => $data];
    }

    function _applyCustomImportFunction($input, &$result, &$data)
    {
        if ($this->customImportFunction == null) {
            $result = true;
            $data = $input;
        } else {
            $func = $this->customImportFunction;
            $result = $func($input);
            if ($result !== false) {
                $data = $result;
                $result = true;
            }
        }
    }


}