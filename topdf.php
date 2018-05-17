<?php
/*
 * This File use for export data from table to a PDF file by using TCPDF.
 */

// Include the main TCPDF library (search for installation path).
require_once('tcpdf/PDF/tcpdf_include.php');
require_once('tcpdf/tcpdf.php');

// extend TCPF with custom functions
class MYPDF extends TCPDF {

	// Load table data from file
	public function LoadData($file) {
		// Read file lines
		$lines = file($file);
		$data = array();
		foreach($lines as $line) {
			$data[] = explode(';', chop($line));
		}
		echo '<pre>'; print_r($data); echo '</pre>';
		return $data;
	}

	public function ReadData($result){
		$data = array();
		if (mysqli_num_rows($result) > 0) {
			foreach ($result as $cell){
				$data[] = array(
				$cell["CO"],$cell["RTE"],$cell["ELEMENT"],$cell["PREFIX"],$cell["POSTMILE"],$cell["DIR"],$cell["LOCATION"],$cell["ID"],$cell["INSTALL_EA"],$cell["DETECTION_TYPE"],$cell["OPERATION_DATE"],$cell["STATUS"],
				);
			}
		}
		//echo '<pre>'; print_r($data); echo '</pre>';
		return $data;
	}
	// Colored table
	public function ColoredTable($header,$data) {
		// Colors, line width and bold font
		$this->SetFillColor(20, 156, 216);
		$this->SetTextColor(255);
		$this->SetDrawColor(128, 0, 0);
		$this->SetLineWidth(0.3);
		$this->SetFont('', 'B');
		// Header width
		$w = array(10,10,20,12,15,8,95,16,18,20,23,20);
		$num_headers = count($header);
		for($i = 0; $i < $num_headers; ++$i) {
			$this->Cell($w[$i], 7, $header[$i], 1, 0, 'C', 1);
		}
		$this->Ln();
		// Color and font restoration
		$this->SetFillColor(224, 235, 255);
		$this->SetTextColor(0);
		$this->SetFont('');
		// Data
		$fill = 0;
		$h = 5;
		foreach($data as $row) {
			$this->Cell($w[0], $h, $row[0], 'LR', 0, 'C', $fill);
			$this->Cell($w[1], $h, $row[1], 'LR', 0, 'C', $fill);
			$this->Cell($w[2], $h, $row[2], 'LR', 0, 'C', $fill);
			//$this->Cell($w[2], 6, number_format((float)$row[2]), 'LR', 0, 'R', $fill);
			$this->Cell($w[3], $h, $row[3], 'LR', 0, 'C', $fill);
			$this->Cell($w[4], $h, $row[4], 'LR', 0, 'C', $fill);
			$this->Cell($w[5], $h, $row[5], 'LR', 0, 'C', $fill);
			$this->Cell($w[6], $h, $row[6], 'LR', 0, 'C', $fill);
			$this->Cell($w[7], $h, $row[7], 'LR', 0, 'C', $fill);
			$this->Cell($w[8], $h, $row[8], 'LR', 0, 'C', $fill);
			$this->Cell($w[9], $h, $row[9], 'LR', 0, 'C', $fill);
			$this->Cell($w[10], $h, $row[10], 'LR', 0, 'C', $fill);
			$this->Cell($w[11], $h, $row[11], 'LR', 0, 'C', $fill);
			$this->Ln();
			$fill=!$fill;
		}
		$this->Cell(array_sum($w), 0, '', 'T');
	}
}

//Database Connection
$connection = db_connect();

if($stmt = $connection->prepare($_SESSION['SQL'])){
	if(strpos($_SESSION['SQL'],'WHERE 1')===false){
		$param_array = bindParam($_SESSION['ELEMENT'],$_SESSION['COUNTY'],$_SESSION['ROUTE'],$_SESSION['STATUS'],$_SESSION['BEGIN'],$_SESSION['END']);
	
		call_user_func_array(array($stmt,'bind_param'), refValues($param_array));
	}
	if(!$stmt->execute()){
		echo "Execute failed: (". $stmt -> errno .")" . $stmt -> error;
	}

	if(!$result = $stmt -> get_result()){
		echo "Get Result failed: (". $stmt -> errno .")".$stmt -> error;
	}
}else{
	echo "Prepare failed: (" . $connection->errno . ") " .$connection->error;
}

// create new PDF document
$pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// set document information
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Owen Li');
$pdf->SetTitle('D10 ITS Electrical Elements');
$pdf->SetSubject('D10 ITS Operations');
$pdf->SetKeywords('PDF, District10, Caltrans, Traffic_Electrical, Elements, Inventory');

// set default header data
$pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE, PDF_HEADER_STRING);

// set header and footer fonts
$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
$pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

// set default monospaced font
$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

// set margins
$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

// set auto page breaks
$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

// set image scale factor
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

// set some language-dependent strings (optional)
if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
	require_once(dirname(__FILE__).'/lang/eng.php');
	$pdf->setLanguageArray($l);
}

// ---------------------------------------------------------

// set font
$pdf->SetFont('helvetica', '', 9);

// add a page
$pdf->AddPage('L','A4');

// column titles
$header = array( 'CO', 'RTE', 'ELEMENT', 'PREFIX', 'PM', 'DIR','LOCATION', 'ID', 'INST_EA','DETECTION','OP_DATE','STATUS');

// data loading
$data = $pdf->ReadData($result);
// print colored table
$pdf->ColoredTable($header, $data);

// ---------------------------------------------------------
// close and output PDF document
$pdf->Output('D10_ITS_ELEMENTS.pdf', 'D');

function db_connect() {
	// Try and connect to the database, if a connection has not been established yet
	mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
	try{
		$config = parse_ini_file('config/search.ini');
		$connection = mysqli_connect($config['servername'],$config['username'],$config['password'],$config['dbname']);//127.0.0.1
		$connection->set_charset('utf8mb4');
		return $connection;
	}catch(Exception $e){
		error_log($e->getMessage());
		exit('Error connecting database');
	}
}

//this function is for preparing $stmt->bind_param($type,$variable);
function bindParam($Element,$County,$Route,$Status,$Begin,$End){
	$bp_array = array();
	$bp_array[0] = '';
	if($Element != 'ALL'){
		$bp_array[0] .='s';
		$bp_array[] = &$Element;
	}
	if($County != 'ALL'){
		$bp_array[0] .='s';
		$bp_array[] = &$County;
	}
	if($Route != 'ALL'){
		$bp_array[0] .='s';
		$bp_array[] = &$Route;
	}
	if($Status != 'ALL'){
		$bp_array[0] .='s';
		$bp_array[] = &$Status;
	}
	if($Begin != 0){
		$bp_array[0] .='d';
		$bp_array[] = &$Begin;
	}
	if($End != 0){
		$bp_array[0] .='d';
		$bp_array[] = &$End;
	}
	return $bp_array;
}

function refValues($arr){
	if(strnatcmp(phpversion(),'5.3')>=0){
		$refs = array();
		foreach($arr as $key => $value){
			$refs[$key] = &$arr[$key];
		}
		return $refs;
	}
	return $arr;
}
//============================================================+
// END OF FILE
//============================================================+
?>