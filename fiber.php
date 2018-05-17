<?php
//CONNECTION
try{
	$config = parse_ini_file('config/fiber.ini');
}catch(Exception $e){
		error_log($e->getMessage());
		exit('Cannot Load Config File.');
}
$connection = db_connect($config);

$stmt = $connection -> stmt_init();
$sql = "SELECT * FROM ". $config['table'] ." WHERE 1 ORDER BY CO, SR, Beg_PM";

if($stmt = $connection -> prepare($sql)){
	if(!$stmt->execute()){// stmt execute
		echo "Execute failed: (". $stmt -> errno .")" . $stmt -> error;
	}

	if(!$inventory = $stmt -> get_result()){//stmt get result
		echo "Get Result failed: (". $stmt -> errno .")".$stmt -> error;
	}

	$tableResult = '';
	$count = mysqli_num_rows($inventory);

	if ($count > 0) {
		// output data of each row by using HTML.
		foreach ($inventory as $cell){
			$tableResult = $tableResult. "<tr><td>". $cell["CO"]. "</td>". "<td>". $cell["SR"]. "</td>". "<td>". $cell["Beg_PM"]. "</td>". "<td>". $cell["End_PM"]. "</td>". "<td>". $cell["Miles"]. "</td>". "<td>". $cell["Location_Description"]. "</td>". "<td>". $cell["FO/CC"]. "</td>"."<td>". $cell["EA"]. "</td>"."<td>". $cell["Status"]. "</td>"."<td>". $cell["Hubs"]. "</td>". "<td>". $cell["Project_Status"]. "</td>"."<td>". $cell["Installation_Year"]. "</td>"."<td>". $cell["Comments"]. "</td><td>". $cell["Latitude"]. "</td><td>". $cell["Longitude"]. "</td></tr>";
			//$tableResult = $tableResult."<tr><td>". $cell["CO"]. "</td><td>". $cell["SR"]. "</td>". "<td>". $cell["Beg_PM"]. "</td>". "<td>". $cell["End_PM"]. "</td>". "<td>". $cell["Miles"]. "</td>". "<td>". $cell["Location_Description"]. "</td></tr>";
		}
	}
	//echo $tableResult;
}else{
	echo "Prepare failed: (" . $connection->errno . ") " .$connection->error;
}
function db_connect($config) {
	// Try and connect to the database, if a connection has not been established yet
	mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
	try{
		$connection = mysqli_connect($config['servername'],$config['username'],$config['password'],$config['dbname']);
		$connection->set_charset('utf8mb4');
		return $connection;
	}catch(Exception $e){
		error_log($e->getMessage());
		exit('Error connecting database');
	}
}
?>

<!DOCTYPE html>
<html>
<head>
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta charset="utf-8">
<title>Fiber Optics | Communication Conduit Inventory</title>
<link rel="stylesheet"
	href="https://cdn.datatables.net/scroller/1.4.4/css/scroller.bootstrap.min.css" />
<link rel="stylesheet"
	href="https://cdn.datatables.net/1.10.16/css/dataTables.bootstrap.min.css" />
<link rel="stylesheet"
	href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" />
<link rel="stylesheet" href="css/fiber.css" />

</head>
<body>
	<div class="loader"></div>
	<div class="container">
		<h1 class="background_text text-center top15">D10 Fiber Optics & Communication Conduit</h1>
	</div>
	<!-- Labels -->
	<div class="col-md-12 col-xs-12 text-center">
		<a href="SearchEngine.html" target="_blank"><span
			class="btn btn-primary btn-xs">ITS Search Engine</span> </a> <a
			href="https://district10.onramp.dot.ca.gov/traf-elec"
			target="_blank"><span class="btn btn-success btn-xs">About Us</span> </a>
		<a
			href="mailto:arlene.cordero@dot.ca.gov;Wenrui.Li@dot.ca.gov?subject=[D10%20ITS%20SEARCH%20ENGINE]&CC=w_li6@u.pacific.edu"><span
			class="btn btn-warning btn-xs">Email Us</span> </a>
	</div>

	<div class="container top30">
		<div id="map"></div>
		<div id="capture"></div>
		<div id="line_info"><!--<p class="top5">Red: Communication Conduit  		Blue: Fiber Optics  			Placemark: Hubs</p>-->
		<p>&nbsp Communicated Conduct: <img src="images/minus.png"></img> &nbsp&nbspFiber Optics: 		<img src="images/minus2.png"></img> &nbsp&nbspHubs:		<img src="images/pin.png"></img></P>
		</div>
	</div>

	<!-- Print Out Table Data Start -->
	<div class="container top7">
		<table id="data_table2"
			class="table table-striped table-hover table-condensed table-bordered nowrap"
			cellspacing="0" width="100%">
			<thead class="bg-info">
				<tr>
					<th>County</th>
					<th>Route</th>
					<th>Begin_PM</th>
					<th>End_PM</th>
					<th>Miles</th>
					<th>Location</th>
					<th>Elements</th>
					<th>Project</th>
					<th>Status</th>
					<th>Hubs</th>
					<th>Project Status</th>
					<th>Installation Year</th>
					<th>Comment</th>
					<th>Latitude</th>
					<th>Longitude</th><!---->
				</tr>
			</thead>
			<tbody>
			<?php
			//echo $tableResult;
			
			if(!empty($tableResult)){
				echo $tableResult;
			}
			?>
			</tbody>
		</table>
	</div>
	<!-- Side Buttons Start -->
	<div class="container col-md-12 hidden-xs hidden-sm">
		<!-- Excel Side Button -->
		<form class="on_the_side_1" action="fibertoexcel.php" method="POST">
			<input type="image" onclick="this.form.submit()"
				src="images/excel-xls-icon.png" name="export_excel"
				alt="Download Excel">
		</form>
		<!-- PDF Side Button -->
		<form class="on_the_side_2" action="fibertopdf.php" method="POST">
			<input type="image" onclick="this.form.submit()"
				src="images/pdf-icon.png" name="export_pdf" alt="Download PDF">
		</form>
	</div>
	<!-- Footer -->
	<div class="container col-md-12 col-xs-12 text-center top15">
		<p style="color: white;">&#169 2018 Caltrans District 10 ITS
			Operations. All Rights Reserved.</p>
	</div>

	<!-- Javascript Files -->
	<script src="https://code.jquery.com/jquery-1.12.4.js"></script>
	<script language="JavaScript" type="text/javascript"
		src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
	<script language="JavaScript" type="text/javascript"
		src="https://cdn.datatables.net/1.10.16/js/jquery.dataTables.min.js"></script>
	<script language="JavaScript" type="text/javascript"
		src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
	<script language="JavaScript" type="text/javascript"
		src="https://cdn.datatables.net/1.10.16/js/dataTables.bootstrap.min.js"></script>
	<script language="JavaScript" type="text/javascript"
		src="https://cdn.datatables.net/scroller/1.4.4/js/dataTables.scroller.min.js"></script>
	<script src="js/fiber.js"></script>
	<script async defer
		src="https://maps.googleapis.com/maps/api/js?key=AIzaSyBSidIqN-RX7Retl0shHA2LlEH0Hg4XYu8&callback&callback=initMap">
	</script>
</body>
</html>
