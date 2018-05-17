<?php
/*
 * This File use for export data from table to a excel file.
 */

require_once 'Classes/PHPExcel.php';

//Database Connection
$connection = db_connect();
session_start();

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

//Create new excel file
$objPHPExcel = new PHPExcel();
$rowCount = 3;

//Style headers
$header = array(
	'font' => array(
		'bold' => true,
		'color' => array('rgb' => 'FFFFFF'),
	),
	'fill' => array(
	    'type' => PHPExcel_Style_Fill::FILL_SOLID,
		'rotation'   => 0,
		'startcolor' => array(
			'rgb' => '319ef9'
		),
		'endcolor'   => array(
			'argb' => '319ef9'
		),
	),
);

if (mysqli_num_rows($result) > 0) {
	//Set Title
	$objPHPExcel->getActiveSheet()->setTitle("Inventory");
	//Set Date & First Row
	$title = "D10 ITS Operation System Inventory (Last Updated: " . $_SESSION['DATE'] .") "; 
	$objPHPExcel->getActiveSheet()->SetCellValue('A1',$title);
	$objPHPExcel->getActiveSheet()->mergeCells('A1:N1');
	$objPHPExcel->getActiveSheet()->getStyle('A1:N1')->getFont()->SetSize(18);
	//Set headers
	$objPHPExcel->getActiveSheet()->SetCellValue('A2', 'County')
								->SetCellValue('B2', 'Route')
								->SetCellValue('C2', 'Element')
								->SetCellValue('D2', 'Prefix')
								->SetCellValue('E2', 'Postmile')
								->SetCellValue('F2', 'Direction')
								->SetCellValue('G2', 'Location')
								->SetCellValue('H2', 'ID')
								->SetCellValue('I2', 'InstallEA')
								->SetCellValue('J2', 'ReplaceEA')
								->SetCellValue('K2', 'Detection Type')
								->SetCellValue('L2', 'Operation Date')
								->SetCellValue('M2', 'Status')
								->SetCellValue('N2', 'Comment');
	
	//Set styles
	$objPHPExcel->getActiveSheet()->getStyle('A2') -> applyFromArray($header);
	$objPHPExcel->getActiveSheet()->getStyle('B2') -> applyFromArray($header);
	$objPHPExcel->getActiveSheet()->getStyle('C2') -> applyFromArray($header);
	$objPHPExcel->getActiveSheet()->getStyle('D2') -> applyFromArray($header);
	$objPHPExcel->getActiveSheet()->getStyle('E2') -> applyFromArray($header);
	$objPHPExcel->getActiveSheet()->getStyle('F2') -> applyFromArray($header);
	$objPHPExcel->getActiveSheet()->getStyle('G2') -> applyFromArray($header);
	$objPHPExcel->getActiveSheet()->getStyle('H2') -> applyFromArray($header);
	$objPHPExcel->getActiveSheet()->getStyle('I2') -> applyFromArray($header);	
	$objPHPExcel->getActiveSheet()->getStyle('J2') -> applyFromArray($header);	
	$objPHPExcel->getActiveSheet()->getStyle('K2') -> applyFromArray($header);	
	$objPHPExcel->getActiveSheet()->getStyle('L2') -> applyFromArray($header);
	$objPHPExcel->getActiveSheet()->getStyle('M2') -> applyFromArray($header);	
	$objPHPExcel->getActiveSheet()->getStyle('N2') -> applyFromArray($header);	
	//Input Data
	foreach ($result as $row){
		$objPHPExcel->getActiveSheet()->SetCellValue('A'.$rowCount, $row['CO'])
								    ->SetCellValue('B'.$rowCount, $row['RTE'])
								    ->SetCellValue('C'.$rowCount, $row['ELEMENT'])
								    ->SetCellValue('D'.$rowCount, $row['PREFIX'])
								    ->SetCellValue('E'.$rowCount, $row['POSTMILE'])
								    ->SetCellValue('F'.$rowCount, $row['DIR'])
								    ->SetCellValue('G'.$rowCount, $row['LOCATION'])
								    ->SetCellValue('H'.$rowCount, $row['ID'])
								    ->SetCellValue('I'.$rowCount, $row['INSTALL_EA'])
									->SetCellValue('J'.$rowCount, $row['REPLACE_EA'])
								    ->SetCellValue('K'.$rowCount, $row['DETECTION_TYPE'])
								    ->SetCellValue('L'.$rowCount, $row['OPERATION_DATE'])
								    ->SetCellValue('M'.$rowCount, $row['STATUS'])
								    ->SetCellValue('N'.$rowCount, $row['NOTE']); 
		$rowCount++;
	}
	
	//Autosize Location and Comment 
	$objPHPExcel->getActiveSheet()->getColumnDimension('G')->setAutoSize(true);
	$objPHPExcel->getActiveSheet()->getColumnDimension('N')->setAutoSize(true);
}

//output
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="D10_Elec_Systems_Element_Inventory.xlsx"');
header('Cache-Control: max-age=0');
$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
$objWriter->save('php://output');

function db_connect() {
	// Try and connect to the database, if a connection has not been established yet
	mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
	try{
		$config = parse_ini_file('config/search.ini');
		$connection = mysqli_connect($config['servername'],$config['username'],$config['password'],$config['dbname']);
		$connection->set_charset('utf8mb4');
		return $connection;
	}catch(Exception $e){
		error_log($e->getMessage());
		exit('Error connecting database');
	}
}

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
?>