<?php 
Class apReport extends apBaseElement {
	
	protected $_path;
	protected $_templatePath;
	
	public function __construct() {
		parent::__construct('reports');
		$this->_path = constant('_GLOBAL_TMP_DIR');
		$this->_templatePath = constant('_GLOBAL_TEMPLATES_DIR')."reports/";
	}
	
	public function getHTML($id, $params = array()) {
		$_db = apDatabase::getDatabaseLink();
		list($name, $type, $SQL) = $_db->getFieldsValue("reports", "id", $id, array("name","type","sql"));		
		$data = $_db->query($SQL,PDO::FETCH_ASSOC);
		switch ($type) {
			case "CUSTOM":
				$params["RECORDS"] = $data;
				return apRender::renderCustom($this->_templatePath.$name, $params, null, true);
				break;
			default:				
				if ($data==false) {
					return '{$L_ERROR_EXECUTING}:'.$SQL;
				}
				return apHtmlUtils::tableHtml_fromArray($data);
				break;				
		}		
	}

	public function generate($id, $filename, $params = array()) {
		$html = $this->getHTML($id, $return, $params);
		$file =  $this->_path . $filename;
		file_put_contents($file, $html);
		return $file;		
	}
		
	public function download($id, $filename) {
		$report = $this->generate($id, $filename);		
		header('Content-Type: application/octet-stream');
		header('Content-Disposition: attachment; filename='.$filename );
		header('Expires: 0');
		header('Cache-Control: must-revalidate');
		header('Pragma: public');
		header('Content-Length: ' . filesize($file));
		readfile($report);
		exit;
	}
	
	public function downloadXLS($id) {
	
	}
	
	public function downloadPDF($id,$filename) {
		$report = $this->generate($id, $filename.".html");
		header('Content-Type: application/octet-stream');
		header('Content-Disposition: attachment; filename='.$filename );
		header('Expires: 0');
		header('Cache-Control: must-revalidate');
		header('Pragma: public');
		header('Content-Length: ' . filesize($file));
		$cmd = 'xvfb-run -a -s "-screen 0 1280x1024x24" wkhtmltopdf --dpi 96 -O landscape --page-size A4 "file:///'.$report.'" '.$this->_path.basename($filename);
		exec($cmd.' 2>&1', $out);
		readfile($report);
	}
		
}

Class apReportList extends  apBaseElementList {
	public function __construct($orderByField = null) {
		parent::__construct("apReport","reports", "id", $orderByField);
	}
	
	
}