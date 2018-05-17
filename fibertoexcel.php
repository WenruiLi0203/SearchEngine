<?php
/*
 * This File use for export data from table to a excel file.
 */

require_once 'Classes/PHPExcel.php';
session_start();

//Database Connection
$connection = db_connect();
$sql = "SELECT * FROM fiber WHERE 1";

if($stmt = $connection->prepare($sql)){
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
	$objPHPExcel->getActiveSheet()->setTitle("FO & CC");
	//Set Date & First Row
	$title = "D10 FO & CC Inventory (Last Updated: " . $_SESSION['DATE'] . ") "; 
	$objPHPExcel->getActiveSheet()->SetCellValue('A1',$title);
	$objPHPExcel->getActiveSheet()->mergeCells('A1:L1');
	$objPHPExcel->getActiveSheet()->getStyle('A1:L1')->getFont()->SetSize(18);
	//Set headers
	$objPHPExcel->getActiveSheet()->SetCellValue('A2', 'County')
								->SetCellValue('B2', 'Route')
								->SetCellValue('C2', 'Begin_PM')
								->SetCellValue('D2', 'End_PM')
								->SetCellValue('E2', 'Location')
								->SetCellValue('F2', 'EA')
								->SetCellValue('G2', 'Miles')
								->SetCellValue('H2', 'Status')
								->SetCellValue('I2', 'FO/CC')
								->SetCellValue('J2', 'Hubs')
								->SetCellValue('K2', 'Project Status')
								->SetCellValue('L2', 'Installation Year')
								->SetCellValue('M2', 'Comment');
	
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
	
	//Input Data
	foreach ($result as $row){
		$objPHPExcel->getActiveSheet()->SetCellValue('A'.$rowCount, $row['CO'])
								    ->SetCellValue('B'.$rowCount, $row['SR'])
								    ->SetCellValue('C'.$rowCount, $row['Beg_PM'])
								    ->SetCellValue('D'.$rowCount, $row['End_PM'])
								    ->SetCellValue('E'.$rowCount, $row['Location_Description'])
								    ->SetCellValue('F'.$rowCount, $row['EA'])
								    ->SetCellValue('G'.$rowCount, $row['Miles'])
								    ->SetCellValue('H'.$rowCount, $row['Status'])
								    ->SetCellValue('I'.$rowCount, $row['FO/CC'])
								    ->SetCellValue('J'.$rowCount, $row['Hubs'])
								    ->SetCellValue('K'.$rowCount, $row['Project_Status'])
								    ->SetCellValue('L'.$rowCount, $row['Installation_Year'])
								    ->SetCellValue('M'.$rowCount, $row['Comments']); 
		$rowCount++;
	}
	
	//Autosize Location and Comment 
	$objPHPExcel->getActiveSheet()->getColumnDimension('E')->setAutoSize(true);
	$objPHPExcel->getActiveSheet()->getColumnDimension('K')->setAutoSize(true);
	$objPHPExcel->getActiveSheet()->getColumnDimension('L')->setAutoSize(true);
	$objPHPExcel->getActiveSheet()->getColumnDimension('M')->setAutoSize(true);
}

//output
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="D10_FO_CC.xlsx"');
header('Cache-Control: max-age=0');
$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
$objWriter->save('php://output');

function db_connect() {
	// Try and connect to the database, if a connection has not been established yet
	mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
	try{
		$config = parse_ini_file('config/fiber.ini');
		$connection = mysqli_connect($config['servername'],$config['username'],$config['password'],$config['dbname']);
		$connection->set_charset('utf8mb4');
		return $connection;
	}catch(Exception $e){
		error_log($e->getMessage());
		exit('Error connecting database');
	}
}
?>