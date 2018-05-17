<?php
//CONNECTION
try{
	$config = parse_ini_file('config/search.ini');
}catch(Exception $e){
		error_log($e->getMessage());
		exit('Cannot Load Config File.');
}
$connection = db_connect($config);
session_start();

//while click search
if(isset($_POST['completeSearch'])){
	if(preg_match('/[^a-zA-Z0-9\s\-_\.\?]/',$_POST['completeSearch'])){
		echo "<span style='color:#F00;'>Sorry, please use the search engine properly.</span>";
	} else{
		$Element = $connection->real_escape_string($_POST['element']);
		$County = $connection->real_escape_string($_POST['county']);
		$Route = $connection->real_escape_string($_POST['route']);
		$Status = $connection->real_escape_string($_POST['status']);
		$Begin = (float)$_POST['begin_postmile'];
		$End = (float)$_POST['end_postmile'];
		//echo $Element . " ". $County. " ". $Route. " ". $Status. " ". $Begin. " ".$End;
		if($Element == 'FO'){
			header('Location:fiber.php',true,303);
			die();
		}
		$sql = prepareSQL($Element, $County, $Route, $Status, $Begin, $End,$config);//get sql query with question marks.
		//echo $sql;
	}
	if(!$sql){
		echo "<span style='color:#F00;'>SQL input error.</span>";
	}else{
		//printf($sql);
		$_SESSION['SQL']=$sql;
		$_SESSION['ELEMENT']=$Element;
		$_SESSION['COUNTY']=$County;
		$_SESSION['ROUTE']=$Route;
		$_SESSION['STATUS']=$Status;
		$_SESSION['BEGIN']=$Begin;
		$_SESSION['END']=$End;
	}

	// stmt init
	$stmt = $connection->stmt_init();
	// stmt preparation
	if($stmt = $connection->prepare($sql)){
		if(strpos($sql,'WHERE 1') === false){
			//get the params into a array, param_array[0] = param types;eg: "sssi".
			$param_array = bindParam($Element,$County,$Route,$Status,$Begin,$End);

			//similar as $stmt->bind_param("s",$variable);
			call_user_func_array(array($stmt,'bind_param'), refValues($param_array));
		}

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
				$tableResult = $tableResult. "<tr><td>". $cell["CO"]. "</td>". "<td>". $cell["RTE"]. "</td>". "<td>". $cell["ELEMENT"]. "</td>". "<td>". $cell["PREFIX"]. "</td>". "<td>". $cell["POSTMILE"]. "</td>". "<td>". $cell["DIR"]. "</td>". "<td>". $cell["LOCATION"]. "</td>".
					"<td>". $cell["ID"]. "</td>"."<td>". $cell["DETECTION_TYPE"]. "</td>"."<td>". $cell["INSTALL_EA"]. "</td>"."<td>". $cell["REPLACE_EA"]. "</td>"."<td>". $cell["OPERATION_DATE"]. "</td>"."<td>". $cell["STATUS"]. "</td>"."<td>". $cell["NOTE"]. "</td>"."<td>". $cell["LATITUDE"]. "</td>"."<td>". $cell["LONGITUDE"]. "</td></tr>";
			}
		}
		
	}else{
		echo "Prepare failed: (" . $connection->errno . ") " .$connection->error;
	}
	
	$update_date = getUpdatedDate($connection,$config);
	$_SESSION['DATE'] = $update_date;
}else{
	//Redirect back to landing page if user used wrong way to search.
	header('Location: SearchEngine.html');
}

mysqli_close($connection);//close database connection

//This function is for getting the database updated date
function getUpdatedDate($connection,$config){
	$update_sql = "SELECT DATE(update_time) AS update_date FROM information_schema.tables WHERE table_schema='". $config['dbname']. "' AND table_name='". $config['table']. "';";
	$update_result = $connection->query($update_sql);
	$update_row = $update_result->fetch_assoc();
	return $update_row["update_date"];
}
//This function is for prepare sql query with question marks.
function prepareSQL($Element,$County,$Route,$Status,$Begin,$End,$config){
	$OtherFilter = false;
	if($Element==='ALL'&&$County==='ALL'&&$Route==='ALL'&&$Status==='ALL'&&$Begin == 0 && $End == 0){
		$sql = "SELECT * FROM " . $config['table']. " WHERE 1";
	}else{
		$sql = "SELECT * FROM " . $config['table']. " WHERE ";
	}

	// If user searched with filter
	if($Element!='ALL'){ // Element filter
		$sql .= "ELEMENT = ?";
		$OtherFilter = true;
	}

	if($County!='ALL' &&$OtherFilter ==false){//County Filter
		$sql .= "CO = ?";
		$OtherFilter = true;
	}elseif($County!='ALL' &&$OtherFilter ==true){
		$sql .= " AND CO = ?";
	}

	if($Route!='ALL' &&$OtherFilter ==false){//Route Filter
		$sql .= "RTE = ?";
		$OtherFilter = true;
	}elseif($Route!='ALL' &&$OtherFilter ==true){
		$sql .= " AND RTE = ?";
	}

	if($Status!='ALL' &&$OtherFilter ==false){//Status Filter
		$sql .= "Status = ?";
		$OtherFilter = true;
	}elseif($Status!='ALL' &&$OtherFilter ==true){
		$sql .= " AND Status = ?";
	}

	if($Begin!= 0 &&$End!= 0 &&$OtherFilter ==false){//Postmile Filter
		$sql .= "POSTMILE BETWEEN ? AND ?";
		$OtherFilter = true;
	}elseif($Begin!= 0 &&$End!= 0 &&$OtherFilter ==true){
		$sql .= " AND POSTMILE BETWEEN ? AND ?";
	}elseif($Begin!=0&&$End==0){
		if($OtherFilter==false){
			$sql .= "POSTMILE >= ?";
			$OtherFilter = true;
		}elseif($OtherFilter==true){
			$sql .= " AND POSTMILE >= ?";
		}
	}elseif($Begin==0&&$End!=0){
		if($OtherFilter==false){
			$sql .= "POSTMILE <= ?";
			$OtherFilter = true;
		}elseif($OtherFilter==true){
			$sql .= " AND POSTMILE <= ?";
		}
	}

	//Order by
	$sql .= " ORDER BY RTE,CO,POSTMILE,PREFIX";
	return $sql;
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

function db_connect($config) {
	//static $connection;
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
<header>
	<title>ITS Search Engine</title>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="shortcut icon" type="image/x-icon" href="images/favicon.ico" />
	<!--
	<link rel="stylesheet"
		href="https://cdn.datatables.net/scroller/1.4.4/css/scroller.bootstrap.min.css" />
	<link rel="stylesheet"
		href="https://cdn.datatables.net/1.10.16/css/dataTables.bootstrap.min.css" />-->
		
		<!--
	<link rel="stylesheet"
		href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" />-->
	<link rel="stylesheet" type="text/css" href="DataTables/datatables.min.css"/>
	<link rel="stylesheet" href="bootstrap3.3.7/css/bootstrap.min.css"/>
	<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.2.1/css/responsive.bootstrap.min.css"/>
	<link rel="stylesheet" href="css/search.css" />
</header>
<body>
	<!-- Page Loader -->
	<div class="loader"></div>
	<!-- Navbar Start-->
	<nav id="navbar"
		class="navbar navbar-default transparent navbar-fixed-top">
		<!-- Navbar Logo -->
		<div class="navbar-header col-md-2 col-xs-2">
			<a class="navbar-brand" href="http://dot.ca.gov/d10/"> <img
				alt="Brand" src="images/CT_logo_4_77x45.png"> </a>
		</div>

		<!-- Small Screen Side Buttons Start -->
		<div class="visible-xs visible-sm col-sm-8 col-xs-8 top10">
			<!-- Excel Collapse Button -->
			<div class="col-sm-4 col-xs-4">
				<form action="toexcel.php" method="POST">
					<button type="submit" class="btn btn-success btn-block"
						name="export_excel" alt="Download Excel">
						<span class="glyphicon glyphicon-save" aria-hidden="true"></span>
						EXCEL
					</button>
				</form>
			</div>

			<!-- PDF Collapse Button -->
			<div class="col-sm-4 col-xs-4">
				<form action="topdf.php" method="POST">
					<button type="submit" class="btn btn-danger btn-block"
						name="export_PDF" alt="Download PDF">
						<span class="glyphicon glyphicon-save" aria-hidden="true"></span>
						PDF
					</button>
				</form>
			</div>

			<!-- Google Map Toggle -->
			<div class="col-sm-4 col-xs-4">
				<button type="submit" class="btn btn-warning btn-block"
					id="map_collapse_switch">
					<span class="glyphicon glyphicon-off" aria-hidden="true"></span>
					MAP
				</button>
			</div>
		</div>
		<!-- Small Screen Side Buttons End -->

		<!-- Navbar Drop Down Menu and Sumbit Button Start-->
		<div class="container-fluid">
			<a class="navbar-toggle" data-toggle="collapse"
				data-target=".navbar-collapse"> <span class="icon-bar"></span> <span
				class="icon-bar"></span> <span class="icon-bar"></span> </a>
			<div class="col-md-10 col-sm- 6 col-xs-12">
				<div class="collapse navbar-collapse">
					<form class="navbar-form navbar-right" action="" method="POST"
						id="complete_search" accept-charset="UTF-8">
						<!-- County Menu -->
						<select class="form-control" name="county">
							<option value="ALL" selected>All County</option>
							<option value="ALA">ALA - Alameda</option>
							<option value="ALP">ALP - Alpine</option>
							<option value="AMA">AMA - Amador</option>
							<option value="CAL">CAL - Calaveras</option>
							<option value="ELD">ELD - El Dorado</option>
							<option value="MER">MER - Merced</option>
							<option value="MPA">MPA - Mariposa</option>
							<option value="SAC">SAC - Sacramento</option>
							<option value="SBT">SBT - San Benito</option>
							<option value="SCL">SCL - Santa Clara</option>
							<option value="SJ">SJ - San Joaquin</option>
							<option value="SOL">SOL - Solano</option>
							<option value="STA">STA - Stanislaus</option>
							<option value="TUO">TUO - Tuolumne</option>
						</select>
						<!-- Route Menu -->
						<select class="form-control" name="route">
							<option value="ALL" selected>All Route</option>
							<option value="4">4</option>
							<option value="5">5</option>
							<option value="12">12</option>
							<option value="16">16</option>
							<option value="26">26</option>
							<option value="33">33</option>
							<option value="49">49</option>
							<option value="59">59</option>
							<option value="88">88</option>
							<option value="89">89</option>
							<option value="99">99</option>
							<option value="104">104</option>
							<option value="108">108</option>
							<option value="120">120</option>
							<option value="124">124</option>
							<option value="132">132</option>
							<option value="140">140</option>
							<option value="152">152</option>
							<option value="156">156</option>
							<option value="165">165</option>
							<option value="205">205</option>
							<option value="207">207</option>
							<option value="219">219</option>
							<option value="580">580</option>
							<option value="9004">9004</option>
							<option value="9005">9005</option>
							<option value="9033">9033</option>
							<option value="9108">9108</option>
							<option value="9120">9120</option>
							<option value="9132">9132</option>
							<option value="9999">9999</option>
						</select>
						<!-- Element Menu -->
						<select class="form-control" name="element">
							<option value="ALL" selected>All Element</option>
							<option value="CCTV">CCTV</option>
							<option value="CMS">CMS</option>
							<option value="EMS">EMS</option>
							<option value="FB">FB</option>
							<option value="HAR">HAR</option>
							<option value="RM">RM</option>
							<option value="RWIS">RWIS</option>
							<option value="Signal">Signal</option>
							<option value="VDS">VDS</option>
							<option value="VCS">VCS</option>
							<option value="WIM">WIM</option>
							<option value="FO">FO & CC</option>
						</select>
						<!-- Status Menu -->
						<select class="form-control" name="status">
							<option value="ALL" selected>All Status</option>
							<option value="Existing">Existing</option>
							<option value="Proposed">Proposed</option>
							<option value="Construction">Construction</option>
							<option value="Removed">Removed</option>
						</select>
						<!-- Begin Postmile Field -->
						<input type="number" class="form-control" min="0" step=any
							name="begin_postmile" placeholder="Begin Postmile">
						<!-- End Postmile Field -->
						<input type="number" class="form-control" min="0" step=any
							name="end_postmile" placeholder="End Postmile">
						<!-- Submit Button -->
						<button type="submit" class="btn btn-primary"
							name="completeSearch">
							<span class="glyphicon glyphicon-search" aria-hidden="true"></span>
							Search
						</button>
					</form>
				</div>
			</div>
		</div>
		<!-- Navbar Drop Down Menu and Sumbit Button Start-->
	</nav>
	<!-- Navbar End -->

	<!-- ITS Search Engine Title and Labels-->
	<div class="container top40">
		<div class="text-center">
			<!-- Title -->
			<h1 class="background_text">D10 ITS Search Engine</h1>
		</div>
		<div class="col-md-12 col-xs-12 text-center">
			<a href="itsword.html" target="_blank"><span
				class="btn btn-primary btn-xs">ITS Acronyms</span> </a> <a
				href="fiber.php" target="_blank"><span class="btn btn-danger btn-xs">FO & CC</span> </a> <a
				href="https://district10.onramp.dot.ca.gov/traf-elec"
				target="_blank"><span class="btn btn-success btn-xs">About Us</span> </a>
			<a
				href="mailto:arlene.cordero@dot.ca.gov;Wenrui.Li@dot.ca.gov?subject=[D10%20ITS%20SEARCH%20ENGINE]&CC=w_li6@u.pacific.edu"><span
				class="btn btn-warning btn-xs">Email Us</span> </a>
		</div>
		<!-- Data Counter -->
		<div class="container col-md-12 col-xs-12 text-center">
			<h5 style="color: white;">
			<?php if(!empty($count)){
				echo htmlspecialchars($count);
			}else {
				echo htmlspecialchars('0 ');
			}
			?>
				Results
			</h5>
		</div>
	</div>

	<!-- Google Map API -->
	<div class="container" id="google_map"></div>

	<!-- Print Out Table Data Start -->
	<div class="container top7">
		<table id="data_table"
			class="table table-striped table-hover table-condensed table-bordered nowrap"
			cellspacing="0" width="100%">
			<thead class="bg-info">
				<tr>
					<th>County</th>
					<th>Route</th>
					<th>Element</th>
					<th>Prefix</th>
					<th>Postmile</th>
					<th>Direction</th>
					<th>Location</th>
					<th>ID</th>
					<th>Detection</th>
					<th>Install EA</th>
					<th>Replace EA</th>
					<th>Operation Date</th>
					<th>Status</th>
					<th>Comment</th>
					<th>Latitude</th>
					<th>Longitude</th>
				</tr>
			</thead>
			<tbody>
			<?php
			/*
			 session_start();
			 $_SESSION["tableResult"] = $tableResult;
			 echo "Session table result set.";
			 */
			if(!empty($tableResult)){
				//echo filter_var($tableResult,FILTER_SANITIZE_STRING);
				echo $tableResult;
			}

			?>
			</tbody>
		</table>
	</div>
	<!-- Print Out Date End -->

	<!-- Side Buttons Start -->
	<div class="container col-md-12 hidden-xs hidden-sm">
		<!-- Excel Side Button -->
		<form class="on_the_side_1" action="toexcel.php" method="POST">
			<input type="image" onclick="this.form.submit()"
				src="images/excel-xls-icon.png" name="export_excel"
				alt="Download Excel">
		</form>
		<!-- PDF Side Button -->
		<form class="on_the_side_2" action="topdf.php" method="POST">
			<input type="image" onclick="this.form.submit()"
				src="images/pdf-icon.png" name="export_pdf" alt="Download PDF">
		</form>
		<!-- Google Map Toggle -->
		<img id="map_switch" name="map_switch" class="on_the_side_3"
			src="images/map-icon-on.png" alt="Show Google Map">
	</div>
	<!-- Side Buttons End -->
	<div class="container">
	<h5 style="color: white;">
	Last Updated: <?php echo $update_date?>
	</h5>
	</div>
	<!-- Footer Begin -->
		<!--
		<div class="col-md-12 col-xs-12 text-center">
			<a href="getxml.php" target="_blank">Download XML file</a> | <a
				href="tutorial.html" target="_blank">Tutorial</a>
		</div>-->
		<!-- Labels -->
				<!-- Data Counter -->
	<div class="container col-md-12 col-xs-12 text-center">
		<p style="color: white;">&#169 2018 Caltrans District 10 ITS
			Operations. All Rights Reserved.</p>
	</div>
	<!-- Footer End -->
	<!-- Javascript Files --><!--
	<script
		src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
	<script src="https://code.jquery.com/jquery-1.12.4.js"></script>-->
	<!--
	<script
		src="https://cdn.datatables.net/1.10.16/js/jquery.dataTables.min.js"></script>
	<script
		src="https://cdn.datatables.net/1.10.16/js/dataTables.bootstrap.min.js"></script>
	<script
		src="https://cdn.datatables.net/scroller/1.4.4/js/dataTables.scroller.min.js"></script>-->
		<!--
	<script
		src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>-->
	<script type="text/javascript" src="js/jquery-3.3.1.min.js"></script>
	<script type="text/javascript" src="DataTables/datatables.min.js"></script>
	<script type="text/javascript" src="bootstrap3.3.7/js/bootstrap.min.js"></script>
	<script src="https://cdn.datatables.net/responsive/2.2.1/js/dataTables.responsive.min.js"></script>
	<script type="text/javascript" src="js/search.js"></script>
	<script async defer
		src="https://maps.googleapis.com/maps/api/js?key=AIzaSyBSidIqN-RX7Retl0shHA2LlEH0Hg4XYu8&callback=initMap">
	</script>
</body>
</html>
