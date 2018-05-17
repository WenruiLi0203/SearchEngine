<?php
/*
 * This File use for transfering data from database to xml file type to display as placemarks in Google Map API.
 */

//CONNECTION
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

// Start XML file, create parent node
$dom = new DOMDocument('1.0', 'UTF-8');
$node = $dom->createElement("elements");
$parnode = $dom->appendChild($node);

header("Content-type: text/xml");
// Iterate through the rows, adding XML nodes for each

if (mysqli_num_rows($result) > 0){
	// Add to XML document node
	foreach ($result as $row){
		$node = $dom->createElement("element");
		$newnode = $parnode->appendChild($node);
		//$newnode->setAttribute("id",$row['main_id']);
		$newnode->setAttribute("name",$row['LOCATION']);
		$newnode->setAttribute("address", $row['CO'] . ' ' . $row['RTE'] . ' '. $row['ELEMENT'] . ' ' . $row['DIR'] . ' '. $row['LOCATION'] . ' Postmile:' . $row['PREFIX'] . $row['POSTMILE']);
		$newnode->setAttribute("lat", $row['LATITUDE']);
		$newnode->setAttribute("lng", $row['LONGITUDE']);
		$newnode->setAttribute("type", $row['ELEMENT']);
	}
}

echo $dom->saveXML();

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
			$refs[] = &$arr[$key];
		}
		return $refs;
	}
	return $arr;
}
?>