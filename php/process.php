<html>
    <body>
	<h2><font color="#1F88A7">CellWhere Postgresql</font> </h2>
	<?php //the following html sets up a table called #sortedtable which will be created later and is sortable
	      //due to the use of javascript   ?>
	      
	<link rel="stylesheet" href="jquery_tablesorter/themes/blue/style.css" type="text/css">
	<script type="text/javascript" src="jquery_tablesorter/jquery-latest.js"></script>
	<script type="text/javascript" src="jquery_tablesorter/jquery.tablesorter.min.js"></script>
	<script type="text/javascript">
	    $(document).ready(function() {
		$("#sortedtable").tablesorter({
		    sortlist: [0,0],
		});
	    });
	    window.onbeforeunload = function(e) {
		return 'Are you sure you want to close this window';
	    };
	</script>
	
    <?php 
	session_start();
	// Session 1
	/*
	// Connect to cellwhere MySQL database
	define( "DB_SERVER",    getenv('OPENSHIFT_MYSQL_DB_HOST') );
	define( "DB_USER",      getenv('OPENSHIFT_MYSQL_DB_USERNAME') );
	define( "DB_PASSWORD",  getenv('OPENSHIFT_MYSQL_DB_PASSWORD') );
	define( "DB_DATABASE",  getenv('OPENSHIFT_APP_NAME') );
	mysql_connect(DB_SERVER,DB_USER,DB_PASSWORD) or die("mysql_connect error: " . mysql_error());
	mysql_select_db(DB_DATABASE) or die("mysql_select_db error: " . mysql_error());
	*/
	// Connect to cellwhere PostgresSQL database
	define( "DB_SERVER",    getenv('OPENSHIFT_POSTGRESQL_DB_HOST') );
	define( "DB_PORT",    	getenv('OPENSHIFT_POSTGRESQL_DB_PORT') );
	define( "DB_USER",      getenv('OPENSHIFT_POSTGRESQL_DB_USERNAME') );
	define( "DB_PASSWORD",  getenv('OPENSHIFT_POSTGRESQL_DB_PASSWORD') );
	define( "DB_DATABASE",  getenv('OPENSHIFT_APP_NAME') );
	$dbconn = pg_connect("host=".DB_SERVER." port=".DB_PORT." user=".DB_USER." dbname=".DB_DATABASE) or die("PSQL Connexion impossible");
	//echo pg_last_error($dbconn);
	if(!$dbconn) {
	    echo "Not connected!";
	}
	/* Put the uploaded query IDs into an array $ID_array */
	$ID_array=null;
	if (isset($_POST)){
	    if(!isset($_POST['ID_type'])){
		echo '<br /><br /><p style="color:red;">Error! Please enter ID type</p>';
		die();
	    }
	    
	    if(!isset($_POST["uploadedxmlfile"])){
		if(isset($_POST["session_name"])&&$_POST["session_name"]!=NULL){
		    $session_name=$_POST["session_name"];
		}else{
		    echo '<br /><br /><p style="color:red;">Error! Please name your session.</p>';
		    die();
		}
		
		if((isset($_FILES['uploadedfile'])&&is_uploaded_file($_FILES['uploadedfile']['tmp_name']))&&(isset($_POST['ID_list'])&&$_POST['ID_list']!=NULL)){
		    echo '<br /><br /><p style="color:red;">Error! Please only paste ID list or only upload ID file</p>';
		    die();
		}
	    }
	    if (isset($_POST['HumMouseBox'])) {             // Test if checkbox is ticked to restrict Gene Symbol query to human and mouse
		$organism = (string)$_POST['HumMouseBox'];
		//echo $organism;
	    }
	    //upload ID file
	    if (isset($_FILES['uploadedfile'])&&is_uploaded_file($_FILES['uploadedfile']['tmp_name'])){                 // If a file has been uploaded then does this:
		$maxsize = 10000;                           // Test if file is greater than 10 Kb. If so, die and give warning.
		if(filesize($_FILES['uploadedfile']['tmp_name']) > $maxsize) {
		    $error = 'File too large. File must be less than 10 Kb, plain text, one gene per line.\n\n10 Kb allows approximately 1000 IDs (but be prepared for a long query time!).';
		}
		if(isset($error)) {
		    echo '<script>alert("'.$error.'");</script>';
		    die(); //Ensure no more processing is done
		}
		
		// Where the file is going to be placed 
		//$target_path = "uploads/";
		$target_dir = $_ENV["OPENSHIFT_DATA_DIR"];
		
		/* Add the original filename to our target path.  
		Result is "uploads/filename.extension" */
		$target_path = $target_dir . basename( $_FILES['uploadedfile']['name']); 
		
		if(move_uploaded_file($_FILES['uploadedfile']['tmp_name'], $target_path)) {
		    echo "The file ".  basename( $_FILES['uploadedfile']['name']). 
		    " was uploaded <br />";
		} else{
		    echo "There was an error uploading the file, please try again! <br /><br />";
		}
		$fh = fopen($target_path, 'r') or die("Can't open file");
		$theData = fread($fh, filesize($target_path));
		fclose($fh);
		//echo "<br />" . $theData;
		 $theData = strtr($theData, array(  // This was a b*gger. Need to normalize end of line characters from any OS
						    // Be careful: "mysql_real_escape_string($IDs)" seems to affect the EOLs
						    // Using single quotes (' not ") was important: I think PHP recognises and parses the
						    // double quotes before strtr does the substitution
		    '\r\n' => '\n',
		    '\r' => '\n',
		    PHP_EOL => '\n',
		));
		
		$ID_array = explode('\n', $theData);	
		$ID_array = array_filter($ID_array);    // Removes empty elements from array
		$ID_array = array_unique($ID_array);    // Removes duplicate elements from array
		foreach ( $ID_array as $key => $value ) {
		    $ID_array[$key] = htmlspecialchars($value);         // Removes links and other html nasties if they are present in the uploaded text and printed later as html
		    //$ID_array[$key] = mysql_real_escape_string($value); // Removes MySQL injections from the text if it is input to a MySQL database 
		    //$ID_array[$key] = pg_escape_string($value);
		}
		
		for ($i = 0; $i <count($ID_array); $i++) {	// this is important to web version
		    $id_tmp = rtrim($ID_array[$i],'\r');	// the '\r' problem
		    $id_tmp = chop($id_tmp);
		    if($id_tmp!=""){
			$ID_array[$i]=$id_tmp;
			//echo $ID_array[$i]."-<br/>";
		    }
		}  
	    }
	    //gxxml file
	    else if(isset($_POST["uploadedxmlfile"])&&isset($_POST["att_ID_type"])&&$_POST["att_FC"]&&isset($_POST['ID_type'])){         // if a gene list generated in local according to a uploaded xml (Lu)
    		$xml_file_name=$_POST["uploadedxmlfile"];		   //gloable variable
		$att_ID_type=$_POST["att_ID_type"];                         //gloable variable
		$att_FC=$_POST["att_FC"];                                   //gloable variable
		$show_name=$_POST["show_name"];
		
		if (@simplexml_load_file($xml_file_name)){
		    $xml=simplexml_load_file($xml_file_name);
		}else{
		    echo "Can't open the xml file!";
		}
		
		$ID_array=array();
		foreach($xml->node as $nodes){
		    foreach($nodes->att as $att){
			if($att['name']==$att_ID_type){
			    $ID_array[]=(string)$att['value'];
			}                  
		    }
		}
	    }
	    //ID list
	    else if(isset($_POST['ID_list'])){             // Otherwise, if some text is pasted, does this:
		
		$maxsize = 10000;                           // Test if file is greater than 10 Kb. If so, die and give warning.
		if(strlen($_POST['ID_list']) >= $maxsize) {
		    $error = 'Too much text. Posted text must be less than 10 Kb, plain text, one gene per line.\n\n10 Kb allows approximately 1000 IDs (but be prepared for a long query time!).';
		}
		if(isset($error)) {
		    echo '<script>alert("'.$error.'");</script>';
		    die(); //Ensure no more processing is done
		}
		
		$IDs = htmlspecialchars($_POST['ID_list']);         // Removes links and other html nasties if they are present in the uploaded text and printed later as html
		//$IDs = mysql_real_escape_string($IDs);              // Removes MySQL injections from the text if it is input to a MySQL database 
		//$IDs = pg_escape_string($IDs);
		/* If you intend to put values in a MySQL database, remember to use this: $IDs = mysql_real_escape_string($IDs); */
		
		$IDs = strtr($IDs, array(   // This was a b*gger. Need to normalize end of line characters from any OS
					    // Be careful: "mysql_real_escape_string($IDs)" seems to affect the EOLs
					    // Using single quotes (' not ") was important: I think PHP recognises and parses the
					    // double quotes before strtr does the substitution
		    '\r\n' => '\n',
		    '\r' => '\n',           
		    PHP_EOL => '\n',
		));
		$ID_array = explode('\n', $IDs);
		$ID_array = array_filter($ID_array);    // Removes empty elements from array
		$ID_array = array_unique($ID_array);    // Removes duplicate elements from array
		
		
		for ($i = 0; $i <count($ID_array); $i++) {	// this is important to web version
		    $id_tmp = rtrim($ID_array[$i],'\r');	// the '\r' problem
		    $id_tmp = chop($id_tmp);
		    if($id_tmp!=""){
			$ID_array[$i]=$id_tmp;
			//echo $ID_array[$i]."-<br/>";
		    }
		}
	    }
	}
	
	if(!$ID_array){
	    echo '<br /><br /><p style="color:red;">Error! There is no ID uploaded!</p>';
	}else{
	/* Take Flavour information */  
	    if (is_uploaded_file($_FILES['uploadedflavour']['tmp_name'])){                 // If a file has been uploaded then does this:
		$maxsize = 500000;                           // Test if file is greater than 500 Kb. If so, die and give warning.
		if(filesize($_FILES['uploadedflavour']['tmp_name']) > $maxsize) {
		    $error = 'File too large. File must be less than 500 Kb.\n\nThe size of the template is around 275 Kb.';
		}
		if(isset($error)) {
		    echo '<script>alert("'.$error.'");</script>';
		    die(); //Ensure no more processing is done
		}
		
		// Where the file is going to be placed 
		//$target_path = "uploads/";
		$target_path = $_ENV["OPENSHIFT_DATA_DIR"];
		
		/* Add the original filename to our target path.  
		Result is "uploads/filename.extension" */
		$flavor_file=basename( $_FILES['uploadedflavour']['name']);
		$target_path = $target_path . basename( $_FILES['uploadedflavour']['name']); 
		
		if(move_uploaded_file($_FILES['uploadedflavour']['tmp_name'], $target_path)) {
		    echo "The file ".  basename( $_FILES['uploadedflavour']['name']). 
		    " was uploaded <br />";
		} else{
		    echo "There was an error uploading the file, please try again! <br /><br />";
		}
		
		$fh = fopen($target_path, 'r') or die("Can't open file");
		$theData = fread($fh, filesize($target_path));
		fclose($fh);
		$theData = strtr($theData, array(  // This was a b*gger. Need to normalize end of line characters from any OS
						    // Be careful: "mysql_real_escape_string($IDs)" seems to affect the EOLs
						    // Using single quotes (' not ") was important: I think PHP recognises and parses the
						    // double quotes before strtr does the substitution
		    '\r\n' => '\n',
		    '\r' => '\n',
		    PHP_EOL => '\n',
		));
		$loc_array = explode('\n', $theData);
		foreach ( $loc_array as $key => $value ) {
		    $loc_array[$key] = htmlspecialchars($value);         // Removes links and other html nasties if they are present in the uploaded text and printed later as html
		    //$loc_array[$key] = mysql_real_escape_string($value); // Removes MySQL injections from the text if it is input to a MySQL database 
		    //$loc_array[$key] = pg_escape_string($value);
		}
		$theData = implode(PHP_EOL, $loc_array);                // PHP_EOL worked here, but neither \n nor \r\n worked
		file_put_contents($target_path, $theData);              // Replace the original uploaded file with the checked and re-line-ended one
		$Flavour = "custom";
		
		// Create a temporary MySQL table to store the uploaded Flavour table
		$sql = "DROP TABLE IF EXISTS map_custom_flavour CASCADE";
		//$result = mysql_query($sql) or die(mysql_error());
		$result = pg_query($sql) or die(" drop table error");
		//echo "ok<br/>";
		
		///////////////test//////////////////////
		/*
		$test = "select count(GO_id_or_uniprot_term) from map_generic_flavour;";
		$test_result = pg_query($test) or die("error");
		while($row = pg_fetch_array($test_result)) {
		    echo "map_generic_flavour contains".$row[0]."lines <br/>";
		}
		*/
		////////////////////////////////////////
			
		$sql="CREATE TEMPORARY TABLE  map_custom_flavour(
			GO_id_or_uniprot_term varchar(255),
			Description varchar(255),
			OurLocalization varchar(255),
			UniquePriorityNumber integer,
			SpatialRelation varchar(255))";
			
		////$result = mysql_query($sql) or die(mysql_error()); $result = pg_query($sql) or die(); 
		$result = pg_query($sql) or die("create table error");
		// Puts uploaded, manipulated, data table into a MySQl table, skipping first line
		/*
		$sql2 = "LOAD DATA LOCAL INFILE '$target_path'          
			INTO TABLE map_custom_flavour
			FIELDS TERMINATED BY '\t'
			OPTIONALLY ENCLOSED BY '\"'
			LINES TERMINATED BY '\n'
			IGNORE 1 LINES ";
		*/
		//copy($target_path,"/var/lib/openshift/53d0cb78e0b8cd6cf5000187/postgresql/data");
		if(file_exists($target_path)){
		    //echo $target_path."<br/>";
		    //echo getcwd()."/ ".$flavor_file."<br/>";
		    $sql2 = "COPY map_custom_flavour FROM '".$target_path."';";
		    //$sql2 = "COPY map_custom_flavour FROM '".$flavor_file."';";
		}	    
		//echo "$sql2";pg_query
		//$result2 = mysql_query($sql2) or die(mysql_error());
		$result2 = pg_query($sql2) or die("upload flavour map error");
		
		$test="select count(GO_id_or_uniprot_term) from map_custom_flavour;";
		$result_test = pg_query($test) or die(" show flavour map error");
		/*
		 *while($row=pg_fetch_array($result_test)){
		    echo "lines: ".$row[0];
		}
		*/
	    }else if(isset($_POST['Flavour'])){             // Otherwise, sets value of $Flavour according to selection from drop-down menu
		$Flavour = $_POST['Flavour'];
		//echo $Flavour;
	    }
	    // if a gene list generated in local according to a uploaded xml   
	     if($ID_array&&isset($_POST["uploadedxmlfile"])&&isset($_POST["att_ID_type"])&&$_POST["att_FC"]&&isset($_POST['ID_type'])){
		$upload_xml="yes";
		/////////////////////////////////////////////////////////
		// Put Query IDs into temporary MySQL table
		$sql="CREATE TEMPORARY TABLE query_ids (QueryID VARCHAR (255))";
		$result = pg_query($sql) or die("CREATE query_ids table error");
		$sql="INSERT INTO query_ids VALUES ";
		foreach ($ID_array as $item) {
		    $sql .= "('".$item."'),";
		}
		$sql = rtrim($sql, ',');
		$result = pg_query($sql) or die("insert values to query_ids error");
		// Create a copy of this temporary table for use in queries where these same values need to be queried twice
		$sql = "CREATE TEMPORARY TABLE query_ids2 AS SELECT * FROM query_ids";
		$result = pg_query($sql) or die("query_ids2 error");
		/////////////////////////////////////////////////////////
		
		$ID_type = $_POST['ID_type'];                   // Sets $ID_type according to value from dropdown menu
		$Source_Loc_Term = $_POST['Source_Loc_Term'];   // Sets $Source_Loc_Term according to value from dropdown menu
			
		    //require 'UploadIDToUniprotACC.php';             // Converts uploaded $ID_array into Uniprot IDs
		    UploadIDToUniprotACC($ID_type,$ID_array,$organism);
		    //require 'QueriesAndUniprotToTempTable.php';     // Puts query IDs and Uniprot IDs into temporary MySQL table called listofids
		    QueriesAndUniprotToTempTable($ID_array);
	
		    require 'ACCtoGO.php';			    // Queries QuickGO with IDs, downloads a tsv file with GO terms
		    require 'JOINSandOutput.php';                   // Puts contents of TSV file into MySQL table, queries it against mapping file, and prints out the results
		    //echo "ok  ";
		    require 'add_location_xml.php';
		    if($ID_array&&$OurLocalization){
			$xml =  add_location_to_xml($ID_array,$att_ID_type,$xml_file_name,$OurLocalization,$show_name,$QueryID_Symbol,$QueryID_ACC);
			/*
			file_put_contents("test.xml",$xml->asXML());
			if(file_exists("test.xml")){
			    echo '<br/><a href="test.xml">download test.xml</a>';
			}*/
			$QueryID_1=NULL;
			$xml_file_name=basename($xml_file_name);
			require 'visualization.php';
			require "create_package.php";
		    }
	    
	    }
	    //if a gene list generated in local according to a uploaded gene list 
	    elseif($ID_array&&!isset($_POST["uploadedxmlfile"])){
		$upload_xml="no";
		//time for all
		$all_start = microtime(true);
		$ID_type = (string)$_POST['ID_type'];                   // Sets $ID_type according to value from dropdown menu
		
		//localization for queries
		//$start_1 = microtime(true);
		/////////////////////////////////////////////////////////
		// Put Query IDs into temporary MySQL table
		//$start = microtime(true);
		
		$sql="CREATE TEMPORARY TABLE query_ids (QueryID VARCHAR (255))";
		////$result = mysql_query($sql) or die(mysql_error()); $result = pg_query($sql) or die(); 
		$result = pg_query($sql) or die();
		$sql="INSERT INTO query_ids VALUES ";
		foreach ($ID_array as $item) {
		    $sql .= "('".$item."'),";
		}
		$sql = rtrim($sql, ',');
		////$result = mysql_query($sql) or die(mysql_error()); $result = pg_query($sql) or die(); 
		$result = pg_query($sql) or die();
		// Create a copy of this temporary table for use in queries where these same values need to be queried twice
		$sql = "CREATE TEMPORARY TABLE query_ids2 AS SELECT * FROM query_ids";
		////$result = mysql_query($sql) or die(mysql_error()); $result = pg_query($sql) or die(); 
		$result = pg_query($sql) or die();
		/*
		$end = microtime(true);
		$time= $end - $start;
		$time = round($time, 2);      // Round to 2 decimal places
		echo "<br />----Put Query IDs into temporary PostegresSQL table took:$time seconds.</br>";
		*/
		/////////////////////////////////////////////////////////
		
		$ID_type = $_POST['ID_type'];                   // Sets $ID_type according to value from dropdown menu
		$Source_Loc_Term = $_POST['Source_Loc_Term'];   // Sets $Source_Loc_Term according to value from dropdown menu  
	    
		//require 'UploadIDToUniprotACC.php';             // Converts uploaded $ID_array into Uniprot IDs
		//$start = microtime(true);
		UploadIDToUniprotACC($ID_type,$ID_array,$organism);
		/*
		$end = microtime(true);
		$time= $end - $start;
		$time = round($time, 2);      // Round to 2 decimal places
		echo "<br />----UploadIDToUniprotACC took:$time seconds.</br>";
		*/
		
	
		//require 'QueriesAndUniprotToTempTable.php';     // Puts query IDs and Uniprot IDs into temporary MySQL table called listofids
		//$start = microtime(true);
		QueriesAndUniprotToTempTable();
		/*
		$end = microtime(true);
		$time= $end - $start;
		$time = round($time, 2);      // Round to 2 decimal places
		echo "<br />----QueriesAndUniprotToTempTable took:$time seconds.</br>";
		*/
		
		//$start = microtime(true);
		require 'ACCtoGO.php';                          // Queries QuickGO with IDs, downloads a tsv file with GO terms
		/*
		$end = microtime(true);
		$time= $end - $start;
		$time = round($time, 2);      // Round to 2 decimal places
		echo "<br />----ACCtoGO took:$time seconds.</br>";
		*/
		//$start_tab = microtime(true);	
		require 'JOINSandOutput.php';
		/*	
		foreach($QueryID_ACC as $qu=>$acc){
		    echo $qu."=>".$acc."<br/>";
		}
		echo "ok!<br/>";
		 */
		$OurLocalization_1=$OurLocalization;    
		$QueryID=array_keys($QueryID_ACC);
		$QueryID_1=$QueryID;
		/*
		$end_tab = microtime(true);
		$time_tab= $end_tab - $start_tab;
		$time_tab = round($time_tab, 2);      // Round to 2 decimal places
		echo "<br />----JOINSandOutput 1 took:$time_tab seconds.</br>";
		
		$end_1 = microtime(true);
		$time_1= $end_1 - $start_1;
		$time_1 = round($time_1, 2);      // Round to 2 decimal places
		echo "<br />Localization 1 took:$time_1 seconds.</br>";
		*/
		
		
		
		//require 'mentha_network.php';
		//$start = microtime(true);
		
		$mentha_add=0;	// no relative protein added
		if(isset($_POST["mentha_add"])&&$_POST["mentha_add"]=="1"){$mentha_add=1;}
		//echo $_POST["mentha_add"]."->".$mentha_add;
		//list($prot_add,$ACC_GN)=mentha_network($session_name,$show_name,$ACCs_1,$mentha_add);
		list($prot_add)=mentha_network($session_name,$QueryID_ACC,$mentha_add,$HumMouseFlag);
		//echo "MENTHA ok!<br/>";
		$ID_type = "UniprotACC";
		/*
		$end = microtime(true);
		$time= $end - $start;
		$time = round($time, 2);      // Round to 2 decimal places
		echo "<br />mentha_network took:$time seconds.</br>";
		*/
	    //  localization for added proteins
		$Symbol_added=array();
		//$start_2 = microtime(true);
		if($mentha_add==1&&$prot_add!=null){
		    $ACCfromUniprot=NULL;
		    foreach ( $prot_add as $value ) {
			//echo $value;
			$TheseACCs = "$value\t$value\n";
			$ACCfromUniprot .= $TheseACCs;
		    }
		    file_put_contents("ACCfromUniprot.csv", $ACCfromUniprot);
		    /*
		    if(file_exists("ACCfromUniprot.tsv")){
			echo '<br/><a href="ACCfromUniprot.tsv" >download ACCfromUniprot.csv</a>';
		    }
		    */
		    /////////////////////////////////////////////////////////
		    //contruct new database
		    $ID_array=$prot_add;
		    // Put Query IDs into temporary PGSQL table
		    $sql="DROP TABLE IF EXISTS query_ids CASCADE";
		    ////$result = mysql_query($sql) or die(mysql_error()); $result = pg_query($sql) or die(); 
		    $result = pg_query($sql) or die("error");
		    $sql="CREATE TEMPORARY TABLE query_ids (QueryID VARCHAR (255))";
		    ////$result = mysql_query($sql) or die(mysql_error()); $result = pg_query($sql) or die(); 
		    $result = pg_query($sql) or die();
		    $sql="INSERT INTO query_ids VALUES ";
		    foreach ($ID_array as $item) {
			$sql .= "('".$item."'),";
		    }
		    $sql = rtrim($sql, ',');
		    ////$result = mysql_query($sql) or die(mysql_error()); 
		    $result = pg_query($sql) or die();
		    /////////////////////////////
		    /*
		    $test_result=pg_query("select * from query_ids");
		    while($row = pg_fetch_array($test_result)) {
			echo $row[0]."-";
		    }
		    echo "<br/>";
		    */
		    //////////////////////////////
		    // Create a copy of this temporary table for use in queries where these same values need to be queried twice
		    $sql = "DROP TABLE IF EXISTS query_ids2 CASCADE";
		    ////$result = mysql_query($sql) or die(mysql_error()); $result = pg_query($sql) or die(); 
		    $result = pg_query($sql) or die();
		    $sql = "CREATE TEMPORARY TABLE query_ids2 AS SELECT * FROM query_ids";
		    ////$result = mysql_query($sql) or die(mysql_error()); $result = pg_query($sql) or die(); 
		    $result = pg_query($sql) or die();
		    ///////////////////////////////////////////////////////
		    /*
		    echo "CREATE TABLE ok!<br/>";
		    //require 'QueriesAndUniprotToTempTable.php';     // Puts query IDs and Uniprot IDs into temporary MySQL table called listofids
		    $start = microtime(true);
		    */
		    QueriesAndUniprotToTempTable();
		    /*
		    $end = microtime(true);
		    $time= $end - $start;
		    $time = round($time, 2);      // Round to 2 decimal places
		    echo "<br />----QueriesAndUniprotToTempTable 2 took:$time seconds.</br>";
		    
		    echo "TempTable 2 ok!<br/>";
		    $start = microtime(true);
		    */
		    require 'ACCtoGO.php';                          // Queries QuickGO with IDs, downloads a tsv file with GO terms
		    /*
		    echo "ACCtoGO 2ok!<br/>";
		    $end = microtime(true);
		    $time= $end - $start;
		    $time = round($time, 2);      // Round to 2 decimal places
		    echo "<br />----ACCtoGO 2 took:$time seconds.</br>";
		    
		    $start_tab = microtime(true);
		    */
		    require 'JOINSandOutput.php';                   // Puts contents of TSV file into MySQL table, queries it against mapping file, and prints out the results
		    //require 'JOINSandOutput_mentha.php'; 
		    /*
		    $end_tab = microtime(true);
		    $time_tab= $end_tab - $start_tab;
		    $time_tab = round($time_tab, 2);      // Round to 2 decimal places
		    echo "<br />----JOINSandOutput 2 took:$time_tab seconds.</br>";
		    
		    $end_2 = microtime(true);
		    $time_2= $end_2 - $start_2;
		    $time_2 = round($time_2, 2);      // Round to 2 decimal places
		    echo "<br />Localization for added proteins took:$time_2 seconds.</br>";
		    */
		    $QueryID_2=$QueryID;
		    $Symbol_added=$QueryID_Symbol;
		    $OurLocalization_2=$OurLocalization;
		    
		    $QueryID = array_merge($QueryID_1,$QueryID_2);
		    $OurLocalization=array_merge($OurLocalization_1,$OurLocalization_2);
		}
		$QueryID=array_keys($QueryID_ACC);
		
		 /*	
		if($QueryID){
		    foreach($QueryID as $loc){echo $loc;}
		}else{echo "no id!!";}
		if($OurLocalization){
		    foreach($OurLocalization as $loc){echo $loc;}
		}else{echo "no loc!!";}
		*/
		//$start = microtime(true);
		require 'add_location_xml_mentha.php';	
		$xml_file_name=$session_name.".xml";
		$xml =  add_location_to_xml_mentha($QueryID,$xml_file_name,$OurLocalization);
		/*
		$end = microtime(true);
		$time= $end - $start;
		$time = round($time, 2);      // Round to 2 decimal places
		echo "<br />add_location_xml_mentha took:$time seconds.</br>";
		
		//time
		$start = microtime(true);	
		*/
		//echo "visualization<br/>"; 
		require 'visualization.php';
		/*
		$end = microtime(true);
		$vis_Duration = $end - $start;
		$vis_Duration = round($vis_Duration, 2);      // Round to 2 decimal places
		echo "<br />Visualization took:$vis_Duration seconds.</br>";
		
		$start = microtime(true);
		*/
		require "create_package.php";                   // create Zip to download
		/*
		$end = microtime(true);
		$time= $end - $start;
		$time = round($time, 2);      // Round to 2 decimal places
		echo "<br />create_package took:$time seconds.</br>";
		*/    
		$all_end = microtime(true);
		$all_time= $all_end - $all_start;
		$all_time = round($all_time, 2);      // Round to 2 decimal places
		echo "<br />all process took:$all_time seconds.</br>";
	    }
	    
	    echo '<br/><a href="'.$xml_file_name.'_web.html" target="_blank">Show the localized network!</a>';
	    if(file_exists($xml_file_name.'_cy3.zip')){
		echo '<br/><a href="'.$xml_file_name.'_cy3.zip" >download localized network (cy3)</a>';
	    }
		    
	    if(file_exists($xml_file_name.'_web.zip')){
		echo '<br/><a href="'.$xml_file_name.'_web.zip" >download localized network (web)</a>';
	    }
	}
	
	//=================================================================Functions===========================================================
	//UploadIDToUniprotACC.php
	function UploadIDToUniprotACC($ID_type,$ID_array,$organism){
	////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	/////////////////////////  Originally 'UploadIDToUniprotACC.php': Converts uploaded $ID_array into Uniprot IDs ///////////////////
	////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// Takes the query $ID_array from process.php and queries Uniprot to get an $ACC_array for ACCtoGO
	//
	// Working example link for gene symbols: http://www.uniprot.org/uniprot/?query=(gene_exact:alk+or+gene_exact:dmd)+and+reviewed:yes&columns=id&format=tab
	// For testing: $ID_array = array("alk", "dmd", "p53", "des", "tnnt3", "myoz2", "actc1", "acta2", "chrna1", "cldn5");
	//
	// As of 170214, extended this to take Uniprot ACCs, Uniprot IDs, Entrez IDS, and Ensembl IDs
	// The latter 3 make use of the Uniprot mapping service
	
	
	    $ACCfromUniprot = '';               // Initialize array to store all ACCs for all query IDs
	    $start = microtime(true);
	    switch ($ID_type) {                 // Depending on selection on dropdown menu
		case "GeneSymbol":
		    //$i=1;
		    foreach ( $ID_array as $value ) {
			//echo ($i++).".".$value ."<br/>";
			//if (empty($value)) {continue;}      // Ignore empty values
			//echo ($i++).$value ."-";
			$TheseACCs = '';                    // Initialize array to store ACCs retrieved for a single query ID
			$URLquery = "http://www.uniprot.org/uniprot/?query=(gene_exact:" . $value . ")+and+(organism:".$organism.")+and+reviewed:yes&columns=id&format=tab";
			//echo $URLquery."<br/>";
			set_time_limit(120);
			$TheseACCs = explode("\n", chop(file_get_contents($URLquery)));
			array_shift($TheseACCs);
			foreach ( $TheseACCs as $key2 => $value2 ) {    // Make table with separate row for the query ID (1st column)
			    if($value2!=NULL &&$value2!="" &&$value2!="\n\n" ){
				$TheseACCs[$key2] = "$value\t$value2";      // and each retrieved ACC (2nd column) 
				//echo "$value\t$value2<br/>";
			    }else{$TheseACCs=null;}
			}
			if($TheseACCs!=null){
			    $ACCfromUniprot .= implode("\n", $TheseACCs);   // Successively combine tables of all Query IDs and their retrieved ACCs
			    $ACCfromUniprot .= "\n";
			}
		    }
		    break;
		case "UniprotACC":
		    foreach ( $ID_array as $key => $value ) {
			$TheseACCs = "$value\t$value\n";
			$TheseACCs = rtrim($TheseACCs,'\r');		    // important to web version (\r problem)
			$ACCfromUniprot .= $TheseACCs;
		    }
		    break;
		case "UniprotID":
		    $url = 'http://www.uniprot.org/mapping/';           // Next ~10 lines POST to Uniprot mapping service using CURL 
		    $TheseIDs = implode(",", $ID_array);
		    $myvars = 'from=' . 'ID' . '&to=' . 'ACC' . '&format=' . 'tab' . '&query=' . $TheseIDs;
		    $ch = curl_init( $url );
		    curl_setopt( $ch, CURLOPT_POST, 1);
		    curl_setopt( $ch, CURLOPT_POSTFIELDS, $myvars);
		    curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 1);
		    curl_setopt( $ch, CURLOPT_HEADER, 0);
		    curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1);
		    set_time_limit(120);
		    $TheseACCs = curl_exec( $ch );
		    $TheseACCs = substr($TheseACCs, strpos($TheseACCs, "\n")+1);   // Remove first line of the response string (which is "From To\n")
		    $ACCfromUniprot = $TheseACCs;
		    break;
		case "Entrez":
		    $url = 'http://www.uniprot.org/mapping/';           // Next ~10 lines POST to Uniprot mapping service using CURL 
		    $TheseIDs = implode(",", $ID_array);
		    $myvars = 'from=' . 'P_ENTREZGENEID' . '&to=' . 'ACC' . '&format=' . 'tab' . '&query=' . $TheseIDs;
		    $ch = curl_init( $url );
		    curl_setopt( $ch, CURLOPT_POST, 1);
		    curl_setopt( $ch, CURLOPT_POSTFIELDS, $myvars);
		    curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 1);
		    curl_setopt( $ch, CURLOPT_HEADER, 0);
		    curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1);
		    set_time_limit(120);
		    $TheseACCs = curl_exec( $ch );
		    $TheseACCs = substr($TheseACCs, strpos($TheseACCs, "\n")+1);   // Remove first line of the response string (which is "From To\n")
		    $ACCfromUniprot = $TheseACCs;
		    break;
		case "Ensembl":
		    $url = 'http://www.uniprot.org/mapping/';           // Next ~10 lines POST to Uniprot mapping service using CURL 
		    $TheseIDs = implode(",", $ID_array);
		    $myvars = 'from=' . 'ENSEMBL_ID' . '&to=' . 'ACC' . '&format=' . 'tab' . '&query=' . $TheseIDs;
		    $ch = curl_init( $url );
		    curl_setopt( $ch, CURLOPT_POST, 1);
		    curl_setopt( $ch, CURLOPT_POSTFIELDS, $myvars);
		    curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 1);
		    curl_setopt( $ch, CURLOPT_HEADER, 0);
		    curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1);
		    set_time_limit(120);
		    $TheseACCs = curl_exec( $ch );
		    $TheseACCs = substr($TheseACCs, strpos($TheseACCs, "\n")+1);   // Remove first line of the response string (which is "From To\n")
		    $ACCfromUniprot = $TheseACCs;
		    break;
	    }
	
	    $end = microtime(true);
	    $UniprotDuration = $end - $start;
	    $UniprotDuration = round($UniprotDuration, 2);      // Round to 2 decimal places
	    
	    $NoQueryIDs = count($ID_array);
	    echo "<br />You submitted $NoQueryIDs unique query ID(s).";
	    echo "<br />Uniprot took: $UniprotDuration seconds.";
	    
	    file_put_contents("ACCfromUniprot.csv", $ACCfromUniprot);
	    if ($handle = opendir('.')) {
		while (false !== ($file = readdir($handle)))
		{
		    if ($file != "." && $file != ".." && strtolower(substr($file, strrpos($file, '.') + 1)) == 'xml')
		    {
			$thelist .= '<li><a href="'.$file.'">'.$file.'</a></li>';
		    }
		}
		closedir($handle);
	    }
	}
	
	function QueriesAndUniprotToTempTable(){
	    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	    ////////  Originally 'QueriesAndUniprotToTempTable.php': Puts query IDs and Uniprot IDs into temporary MySQL table called listofids /////////
	    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	    // Create a temporary MySQL table to store the uploaded IDs
	    global $target_dir;
	    //echo "enter QueriesAndUniprotToTempTable <br/>";
	    $sql = "DROP TABLE IF EXISTS listofids CASCADE";
	    ////$result = mysql_query($sql) or die(mysql_error());
	    $result = pg_query($sql) or die("error");
	    //echo "ok<br/>";
	   
	    $sql="CREATE TEMPORARY TABLE listofids (QueryID VARCHAR (50), ACC VARCHAR (50));";
	    //$result = mysql_query($sql) or die(mysql_error());
	    $result = pg_query($sql) or die("error"); 
	    //echo "ok<br/>";
	      
	    //$sql2 = "COPY listofids FROM '".$target_dir."/ACCfromUniprot_1.csv'(DELIMITER(';'));";
	    if(file_exists("ACCfromUniprot.csv")){
	       // echo '<br/><a href="ACCfromUniprot.csv" >download ACCfromUniprot in function.csv</a>';
		$sql2 = "COPY listofids FROM '".getcwd()."/ACCfromUniprot.csv' DELIMITER as '\t';";
		//echo $sql2;
		//$result2 = mysql_query($sql2) or die(mysql_error());
		$result2 = pg_query($sql2) or die("sql2 error");
		//echo "ok<br/>";
	    }
	    /*
	    // show results
	    $sql="select * from listofids;";
	    $result = pg_query($sql) or die("error");   
	    while($row = pg_fetch_array($result)) {
		echo $row[0]."	".$row[1]."<br/>";
	    }
	    */
	    
	    // Create a copy of this temporary table for use in queries where these same values need to be queried twice
	    $sql = "DROP TABLE IF EXISTS listofids2 CASCADE";
	    //$result = mysql_query($sql) or die(mysql_error());
	    $result = pg_query($sql) or die("error");
	    //echo "ok<br/>";
	    
	    $sql9 = "CREATE TEMPORARY TABLE listofids2 AS SELECT * FROM listofids";
	    //$result9 = mysql_query($sql9) or die(mysql_error());
	    $result9= pg_query($sql9) or die("error");
	    //echo "ok<br/>";
	    
	    
	    // Count how many distinct (non-duplicate) Uniprot Accessions were retrieved
	    $sql="SELECT COUNT(DISTINCT QueryID) FROM listofids WHERE QueryID IS NOT NULL AND TRIM(QueryID) != ''";     // TRIM(QueryID) != '' was necessary because some rows consist of white 
															// space but are not null. I couldn't figure out their origin.
	    //$result = mysql_query($sql) or die(mysql_error());
	    $result = pg_query($sql) or die();
	    //echo "ok<br/>";
	    $resultRow = pg_fetch_array($result);
	    if($resultRow) { echo "<br/>Uniprot accessions were found for " . $resultRow[0] . " query ID(s)."; }
	    
	    
	    // Check if any of the query IDs were not found in Uniprot, and list them in output
	    $SomeIDnotFoundFlag = 0;
	    //$result = mysql_query("SELECT QueryID FROM listofids") or die(mysql_error());
	    $result = pg_query("SELECT QueryID FROM listofids") or die("error");
	    $column = array();
	    while($row = pg_fetch_array($result)){
		array_push($column, $row["QueryID"]);   // Put all the retrieved IDs into an array$
	    }
	    $NotFoundList = "";
	    foreach ($ID_array as $ID) {                // $ID_array is the original array of Query IDs
		if ( ! in_array($ID, $column)) {        // Check for each original Query ID if it was found
		    $SomeIDnotFoundFlag = 1;
		    $NotFoundList .= "$ID ";
		}
	    }
	    if ($SomeIDnotFoundFlag == 1) {
		echo " The following query ID(s) were not found in Uniprot: " . $NotFoundList;
	    }
	}
	  
	function mentha_network($session_name,$QueryID_ACC,$mentha_add,$organism){
	    /*
	     *
	     *
	     */
	    // prot in the list
	    $org_uniprot = array_values($QueryID_ACC);
	    $org_queryID = array_keys($QueryID_ACC);
	    //foreach($org_queryID as $id){echo $id."<br/>";}
	    $ACCs  = implode(",",$org_uniprot);
	    //$ACCs  = preg_replace("/\n[a-zA-Z0-9_]+\t/",",",$ACCfile);  //string
	    $org_uniprot=array_filter(explode(",",$ACCs));                //array
		
	    //query to mentha sever
	    if(@fopen('http://mentha.uniroma2.it:8080/server/getInteractions?org='.$organism.'&ids='.$ACCs,"rb")){
		$start = microtime(true);
		$M_network=fopen('http://mentha.uniroma2.it:8080/server/getInteractions?org='.$organism.'&ids='.$ACCs,"rb");
		$end = microtime(true);
		$mentha_Duration = $end - $start;
		$mentha_Duration = round($mentha_Duration, 2);      // Round to 2 decimal places
		//echo "<br />----Mentha took:$mentha_Duration seconds.</br>";
	    }
	    //echo '----http://mentha.uniroma2.it:8080/server/getInteractions?org='.$organism.'&ids='.$ACCs;
		
	    //time for ranking the interaction
	    $start = microtime(true);
	    $interaction=$both=$one=$none=array();
	    //rank the added prots  
	    while(!feof($M_network)){
		$line = fgets($M_network);
		if($line){
		    list($prot_A,$prot_B,$score)= array_filter(explode(";",$line));
		    $prot_mentha[]=$prot_A;
		    $prot_mentha[]=$prot_B;
		    
		    if(in_array($prot_A,$org_uniprot)&&in_array($prot_B,$org_uniprot)){
		      $both[$prot_A.'-'.$prot_B]=(real)chop($score);
		    }elseif((in_array($prot_A,$org_uniprot)&&!in_array($prot_B,$org_uniprot))||(!in_array($prot_A,$org_uniprot)&&in_array($prot_B,$org_uniprot))){
		      $one[$prot_A.'-'.$prot_B]=(real)chop($score);
		    }elseif(!in_array($prot_A,$org_uniprot)&&!in_array($prot_B,$org_uniprot)){
		      $none[$prot_A.'-'.$prot_B]=(real)chop($score);
		    }
		}
	    }
	    fclose($M_network);
		
	    $prot_add=null;		// other proteins added by mentha ACC
	    $prot_all=$org_queryID;	// original query proteins queryID
		
	    $prot_mentha=array_unique($prot_mentha);
	    if($both){        arsort($both); $interaction=array_merge($interaction,$both);      }
	    if($mentha_add==1){ // if allow mentha added protein
		if($one) {        arsort($one); $interaction=array_merge($interaction,$one);        } 
		if($none){        arsort($none); $interaction=array_merge($interaction,$none);      }
		if($interaction!=null){
		    $i=$j=0;
		    $prot_top=$org_uniprot;
		    $top=50-count($org_queryID);
		    foreach($interaction as $protAB=>$score){
			if($i<$top){
			    $j++;
			    $prot=explode('-',$protAB);
			    if(!in_array($prot[0],$prot_top)) {$prot_top[]=$prot[0];$i++;}
			    if(!in_array($prot[1],$prot_top)) {$prot_top[]=$prot[1];$i++;}
			}
		    }
		    //echo "i:$i,j:$j<br/>";
		    $prot_add = array_diff($prot_top,$org_uniprot);
		    $prot_all=array_merge($org_queryID,$prot_add);
		    //$prot_all[]=$prot_add;
		    
		    $interaction==array_slice($interaction,0,$j);
		    //echo "prot_add contains".count($prot_add)."nodes";
		}
	    }
	    
	    echo count($prot_add)." proteins are added by Mentha<br/>";
	    echo count($prot_all)." proteins in taotal<br/>";
		/*
		$end = microtime(true);
		$mentha_rank= $end - $start;
		$mentha_rank = round($mentha_rank, 2);      // Round to 2 decimal places
		echo "<br />----Mentha ranking took:$mentha_rank seconds.</br>";
		*/
	    
	   // convert to xml format
	    $xml = new SimpleXMLElement('<delete/>');
	    $graph=$xml->addChild("graph");
	    $graph->addAttribute("id","mentha");
	    $graph->addAttribute("label","mentha_network");
	    $graph->addAttribute("cy-colon-documentVersion","3.0");
	    $graph->addAttribute("xmlns-colon-dc","http://purl.org/dc/elements/1.1/");
	    $graph->addAttribute("xmlns-colon-xlink","http://www.w3.org/1999/xlink");
	    $graph->addAttribute("xmlns-colon-rdf","http://www.w3.org/1999/02/22-rdf-syntax-ns#");
	    $graph->addAttribute("xmlns-colon-cy","http://www.cytoscape.org");
	    $graph->addAttribute("xmlns","http://www.cs.rpi.edu/XGMML");
	    
	    //nodes
	    foreach($prot_all as $prot){
		//echo $prot.'-';
		$prot2=$prot;
		$node = $graph->addChild("node");
		$node->addAttribute("label",$prot);
		if(array_key_exists($prot,$QueryID_ACC)){
		  $prot = $QueryID_ACC[$prot];   //if $prot is in query IDs , then get the acc number
		}
		$att=$node->addChild("att");
		$att->addAttribute("name","Uniprot ID");
		$att->addAttribute("value",$prot);
		$att->addAttribute("type","string");
		if($prot==""){$prot = $prot2;}
		$node->addAttribute("id",$prot);
		$graphics=$node->addChild("graphics");
		$graphics->addAttribute("outline","#000000");
		$graphics->addAttribute("fill","#000000");
		$graphics->addAttribute("w","40.0");
		$graphics->addAttribute("x","100.0");      
		$graphics->addAttribute("y","100.0");
		$graphics->addAttribute("z","0");
		$graphics->addAttribute("h","40.0");
		$graphics->addAttribute("width","2.0");
		$graphics->addAttribute("type","ELLIPSE");
	    }
	    
	    //edges
	    $id=1;
	    //echo 'interact:'.count($interaction);
	    foreach($interaction as $protAB=>$score){
		$prot=explode('-',$protAB);
		//echo $prot[0].'+'.$prot[1]."</br>";
		$edge = $graph->addChild("edge");
		$edge->addAttribute("id",$id);
		$edge->addAttribute("label",$prot[0]."|".$prot[1]);
		$edge->addAttribute("source",$prot[0]);
		$edge->addAttribute("target",$prot[1]);
		  
		$att=$edge->addChild("att");
		$att->addAttribute("name","score");
		$att->addAttribute("value",(string)$score);
		$att->addAttribute("type","real");
		  
		$graphics=$edge->addChild("graphics");
		$att->addAttribute("fill","#dad4a2");
		$att->addAttribute("width","3");
		  
		$id++;
	    }
	    
	    $str=$xml->asXML();
	    $str=str_replace("-colon-",":",$str);
	    $str=str_replace("<delete>","",$str);
	    $str=str_replace("</delete>","",$str); 
	
	    file_put_contents($session_name.".xml",$str);
	/*    
	    if(file_exists($session_name.".xml")){
		echo '<br/><a href='.$session_name.'.xml >download 1_'.$session_name.'xml</a>';
	    }
	*/    
	    return array($prot_add);
	}
	
    ?>
</body>
</html>
