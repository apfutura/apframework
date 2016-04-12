<?php

class apImport
{
    private $db;
    private $table = "";
    private $keyField = "";
    private $fields = array();
    private $insensitiveLookups = true;
    private $updaterepeated = true;
    private $csvDelimiter = ";";
    private $msg = "";
    private $dataOks = [];
    private $dataErrs = [];
    public $customAfterLineCheckFunction = null;
    public $customLookupFunction = null;
    public $customUpdateElementFunction = null;
    public $customInsertElementFunction = null;

    function __construct($table, $mode, $insensitiveLookups, $updaterepeated, $csvDelimiter, $keyField)
    {
        $this->db = apDatabase::getDatabaseLink();
        $this->table = $table;
        $this->keyField = $keyField;
        $this->mode = $mode;
        $this->insensitiveLookups = $insensitiveLookups;
        $this->updaterepeated = $updaterepeated;
        $this->csvDelimiter = $csvDelimiter;
    }

    function addField($field)
    {
        if ($field instanceof apImportField ) {
            $field->table = $this->table;
            $this->fields[$field->name] = $field;
        } else {
            throw new Exception('apImport::addField requires an apImportField');
        }
    }

    function getFields() {
        return $this->fields;
    }

    function getField($name) {
        return $this->fields[$name];
    }

    function getUpdateRepeated() {
        return $this->updaterepeated;
    }

    function getInsensitiveLookups() {
        return $this->insensitiveLookups;
    }

    function process($data, $mode)
    {
        $result = false;
        set_time_limit(apConfig::get("import_timeout","internal",360, true));
        ini_set('auto_detect_line_endings',TRUE);

        foreach ($data as $file) {
            $name = $file['name'];
            $size = $file['size'];
            if (!((strpos($name, "csv") || strpos($name, "CSV")) && ($size < 10000000))) {
                $this->msg .= '{$L_ONLYCSVFILES_SIZE} <small>({$L_FILE}: ' . $name .'  {$L_SIZE}:'. apUtils\bytesToSize1024($size).')';
            } else {
                $finalFilename = constant('_GLOBAL_TMP_DIR').$name;
                if (move_uploaded_file($file['tmp_name'], $finalFilename )){
                    if ($mode=="raw") {
                        $result = $this->importCSVIntoObject($finalFilename);
                    } else if ($mode=="adv") {
                        $result = $this->importCSVProcessed($finalFilename);
                    } else {
                        $this->msg .= '{$L_UNKONWN_IMPORT_MODE}<br />';
                    }

                } else {
                    $this->msg .= $name.': {$L_UPLOAD_ERR}<br />';
                }
            }
        }
        ini_set('auto_detect_line_endings',FALSE);

        return ["result" => $result, "data" => [$this->dataOks, $this->dataErrs], "msg" => $this->msg];
    }


    function importCSVIntoObject($finalFilename)
    {
        list($result, $messages) = apUtils\importCsv($finalFilename, $this->table);
        if (!$result) {
            $this->msg =  "Errors: ". implode(", ", $messages);
            return false;
        } else {
            return true;
        }
    }

    function openFile($filename, $handle)
    {
        $elementFields = $this->getFields();
        $csvFields = fgetcsv($handle, 1000, $this->csvDelimiter); //skip first line

        $isBom = false;
        $str = file_get_contents($filename);
        $bom = pack("CCC", 0xef, 0xbb, 0xbf);
        if (0 == strncmp($str, $bom, 3)) {
            $this->msg .= "BOM detected - ignoring the 3 first chars\n";
            $isBom = true;
        }

        $index = 0;
        foreach ($csvFields as $csvHeaderField) {
            $field = trim($csvHeaderField);
            if ($index==0 && $isBom) {
                $field = substr(trim($csvHeaderField),3);
            }
            if ($this->getField($field)) {
                $fieldIndex[$field] = $index;
            } else {
                $this->msg .= 'Ignoring header "' .$csvHeaderField. "\". (".print_r(implode(",",$elementFields), true).") \n";
            }
            $index++;
        }

        return $fieldIndex;
    }

    function importCSVProcessed($filename)
    {
        $handle = fopen($filename, "r");
        $fieldIndex = $this->openFile($filename, $handle);

        $result = false;
        $total = 1;

        if (count($fieldIndex)==0) {
            $this->msg = '<h4>{$L_NO_VALID_FIELDS_INTHE_FIRST_ROW}</h4>';
        }

        $this->msg .= '{$L_FOUND_FIELDS}: ' .implode(", ", array_keys($fieldIndex))." \n";

        while (($data = fgetcsv($handle, 1000, $this->csvDelimiter)) !== FALSE) {
            $elementData = array();
            $originalData = array();
            $fieldsErrors = array();
            $lineError = false;

            foreach ($fieldIndex as $field => $csvIndex) {
                $dataValue = apUtils\forceUTF8(trim($data[$csvIndex]));
                $originalData[$field] = $dataValue;

                $importField = $this->getField($field);
                if ($importField) {
                    $result = $importField->checkField($dataValue);
                    if ($result["result"]) {
                        $elementData[$importField->fieldname] = $result["data"];
                    } else {
                        $lineError = true;
                        $this->msg .= $result['message'] ."\n";
                        $fieldsErrors[] = $result['message'];
                    }
                } else {
                    $this->msg .= '{$L_UNEXISTANT_APIMPORTFIELD}';
                    $fieldsErrors[] =  $field . ' {$L_UNEXISTANT_APIMPORTFIELD}';
                }
            }

            if ($this->customAfterLineCheckFunction!==null) {
                $func = $this->customAfterLineCheckFunction;
                $result =  $func($elementData);
                if ($result["result"]) {
                    $elementData = $result["data"];
                } else {
                    $lineError = true;
                    $fieldsErrors[] = $result['msg'];
                }
                $this->msg .= $result["msg"] . "\n";
            }

            if (!$lineError) {
                $id = null;
                if ($this->customLookupFunction!==null) {
                    $func = $this->customLookupFunction;
                    $result =  $func($elementData);
                    if ($result) $id = $result;
                } else {
                  $id = $originalData[$this->keyField];
                }

                $element = new apBaseElement($this->table, $this->keyField);
                $result = $element->load($id);
                if ($result) {
                    if ($this->customUpdateElementFunction !== null) {
                        $func = $this->customUpdateElementFunction;
                        $result = $func($elementData, $element);
                    } else {
                        $result = $element->update($elementData);
                    }
                    $this->handleResult($element, $result, $total, $originalData, $elementData, '{$L_UPDATED_OK}', '{$L_ERROR_UPDATING_DATA}');
                } else {
                    if ($this->customInsertElementFunction !== null) {
                        $func = $this->customInsertElementFunction;
                        $result = $func($elementData);
                    } else {
                        $result = $element->insert($elementData);
                    }
                    $this->handleResult($element, $result, $total, $originalData, $elementData, '{$L_INSERTED}', '{$L_ERROR_SAVING_DATA}');
                }
            } else {
                $this->dataErrs[] = $this->dataResult($total, $elementData, getLangConstant("L_CSV_IMPORT_ROWDATAERROR") . ": " . implode(", ", $fieldsErrors));
                $this->msg .= "Line $total not imported due to invalid data values (Line data: ".implode(";",$originalData).").\n\n";
            }
            $total++;
        }

        fclose($handle);

        return ($result["result"] ? $result["result"] : $result);
    }

    function dataResult($total, $elementData, $msg)
    {
        $dataResult = new stdClass();
        $dataResult->line = $total;
        $dataResult->data = $elementData;
        $dataResult->msg = $msg;
        return $dataResult;
    }

    function handleResult($element, $result, $line, $originalData, $elementData, $defaultSuccessMessage, $defaultErrorMessage) {
        $errMsg = '';
        if ($result === true || $result["result"]) {
            $this->dataOks[] = $this->dataResult($line, $originalData, $defaultSuccessMessage . ( $result["msg"]?' ('.$result["msg"].')':"") . ($this->keyField && $originalData[$this->keyField]?' : ' . $originalData[$this->keyField]:''));
        } else {
            if ($element) {
                $errMsg = $element->getDB()->getLastErrorMessage();
                $this->msg .= "<span title='" . htmlspecialchars($errMsg, ENT_QUOTES) ."'>" .getLangConstant('L_ERROR_IMPORTING_LINE_NUM'). ": $line</span> (" . getLangConstant('L_ROW_DATA'). ": " . implode(";", $elementData) . ").\n";
            }
            if ($result["msg"]) $errMsg .= $result["msg"];
            $this->dataErrs[] = $this->dataResult($line, $elementData, ($errMsg?$errMsg:$defaultErrorMessage) . ' ' . implode(";", $originalData));
        }
        $this->msg .= $result["msg"] . "\n";
    }

}
