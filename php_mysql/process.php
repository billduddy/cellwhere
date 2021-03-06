<html>  
<body>
    <h2><font color="#1F88A7">CellWhere BETA</font> </h2>
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

// Connect to cellwhere MySQL database
define( "DB_SERVER",    getenv('OPENSHIFT_MYSQL_DB_HOST') );
define( "DB_USER",      getenv('OPENSHIFT_MYSQL_DB_USERNAME') );
define( "DB_PASSWORD",  getenv('OPENSHIFT_MYSQL_DB_PASSWORD') );
define( "DB_DATABASE",  getenv('OPENSHIFT_APP_NAME') );
mysql_connect(DB_SERVER,DB_USER,DB_PASSWORD) or die("mysql_connect error: " . mysql_error());
mysql_select_db(DB_DATABASE) or die("mysql_select_db error: " . mysql_error());

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
	$HumMouseFlag = (string)$_POST['HumMouseBox'];
    }	

   
    
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
	$target_path = $_ENV["OPENSHIFT_DATA_DIR"];
        
        /* Add the original filename to our target path.  
        Result is "uploads/filename.extension" */
        $target_path = $target_path . basename( $_FILES['uploadedfile']['name']); 
        
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
            $ID_array[$key] = mysql_real_escape_string($value); // Removes MySQL injections from the text if it is input to a MySQL database 
        }
	
	for ($i = 0; $i <count($ID_array); $i++) {	// this is important to web version
	    $id_tmp = rtrim($ID_array[$i],'\r');	// the '\r' problem
	    if($id_tmp!=""){
	        $ID_array[$i]=$id_tmp;
	    }
	}
	
	
     //////////////////////////Lu///////////////////////////////   
    }else if(isset($_POST["uploadedxmlfile"])&&isset($_POST["att_ID_type"])&&$_POST["att_FC"]&&isset($_POST['ID_type'])){         // if a gene list generated in local according to a uploaded xml (Lu)

	$xml_file_name=$_POST["uploadedxmlfile"];		   //gloable variable
	$att_ID_type=$_POST["att_ID_type"];                         //gloable variable
        $att_FC=$_POST["att_FC"];                                   //gloable variable
	
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
        ///////////////////////////////////////////////////////////////
    }else if(isset($_POST['ID_list'])){             // Otherwise, if some text is pasted, does this:
        
        $maxsize = 10000;                           // Test if file is greater than 10 Kb. If so, die and give warning.
        if(strlen($_POST['ID_list']) >= $maxsize) {
            $error = 'Too much text. Posted text must be less than 10 Kb, plain text, one gene per line.\n\n10 Kb allows approximately 1000 IDs (but be prepared for a long query time!).';
        }
        if(isset($error)) {
            echo '<script>alert("'.$error.'");</script>';
            die(); //Ensure no more processing is done
        }
        
        $IDs = htmlspecialchars($_POST['ID_list']);         // Removes links and other html nasties if they are present in the uploaded text and printed later as html
        $IDs = mysql_real_escape_string($IDs);              // Removes MySQL injections from the text if it is input to a MySQL database 
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
    }
}

if(!$ID_array){
    echo '<br /><br /><p style="color:red;">Error! There is no ID uploaded!</p>';
}else{
/* Take Flavour information */
//if (isset($_POST)){    
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
            $loc_array[$key] = mysql_real_escape_string($value); // Removes MySQL injections from the text if it is input to a MySQL database 
        }
        $theData = implode(PHP_EOL, $loc_array);                // PHP_EOL worked here, but neither \n nor \r\n worked
        file_put_contents($target_path, $theData);              // Replace the original uploaded file with the checked and re-line-ended one
        $Flavour = "custom";
        
        // Create a temporary MySQL table to store the uploaded Flavour table
        $sql="CREATE TEMPORARY TABLE map_custom_flavour (GO_id_or_uniprot_term VARCHAR (255), Description VARCHAR (255), OurLocalization VARCHAR (255), UniquePriorityNumber INT (4), SpatialRelation VARCHAR (255))";
        $result = mysql_query($sql) or die(mysql_error());
        
        // Puts uploaded, manipulated, data table into a MySQl table, skipping first line
        $sql2 = "LOAD DATA LOCAL INFILE '$target_path'          
                INTO TABLE map_custom_flavour
                FIELDS TERMINATED BY '\t'
                OPTIONALLY ENCLOSED BY '\"'
                LINES TERMINATED BY '\n'
                IGNORE 1 LINES ";
        //echo "$sql2";
        $result2 = mysql_query($sql2) or die(mysql_error());
        
    }else if(isset($_POST['Flavour'])){             // Otherwise, sets value of $Flavour according to selection from drop-down menu
        $Flavour = $_POST['Flavour'];
        //echo $Flavour;
    }
//}



    //////////////////////////////////////////////////////////////////////////////////////////////////
	// if a gene list generated in local according to a uploaded xml (Lu)    
    if($ID_array&&isset($_POST["uploadedxmlfile"])&&isset($_POST["att_ID_type"])&&$_POST["att_FC"]&&isset($_POST['ID_type'])){      
	/////////////////////////////////////////////////////////
	// Put Query IDs into temporary MySQL table
	$sql="CREATE TEMPORARY TABLE query_ids (QueryID VARCHAR (255))";
	$result = mysql_query($sql) or die(mysql_error());
	$sql="INSERT INTO query_ids VALUES ";
	foreach ($ID_array as $item) {
	    $sql .= "('".$item."'),";
	}
	$sql = rtrim($sql, ',');
	$result = mysql_query($sql) or die(mysql_error());
	// Create a copy of this temporary table for use in queries where these same values need to be queried twice
	$sql = "CREATE TEMPORARY TABLE query_ids2 AS SELECT * FROM query_ids";
	$result = mysql_query($sql) or die(mysql_error());
	/////////////////////////////////////////////////////////
	
	$ID_type = $_POST['ID_type'];                   // Sets $ID_type according to value from dropdown menu
	$Source_Loc_Term = $_POST['Source_Loc_Term'];   // Sets $Source_Loc_Term according to value from dropdown menu
    
	        
	    //require 'UploadIDToUniprotACC.php';             // Converts uploaded $ID_array into Uniprot IDs
	    UploadIDToUniprotACC($ID_type,$ID_array,$HumMouseFlag);
	    //require 'QueriesAndUniprotToTempTable.php';     // Puts query IDs and Uniprot IDs into temporary MySQL table called listofids
	    QueriesAndUniprotToTempTable($ID_array);;

	    require 'ACCtoGO.php';			    // Queries QuickGO with IDs, downloads a tsv file with GO terms
	    require 'JOINSandOutput.php';                   // Puts contents of TSV file into MySQL table, queries it against mapping file, and prints out the results
	    require 'add_location_xml.php';
	    $xml =  add_location_to_xml($ID_array,$att_ID_type,$xml_file_name,$OurLocalization);
	    $QueryID_1=NULL;
	    $ACCs_1=NULL;
	    require 'visualization.php';
	    $xml_file_name=basename($xml_file_name);
	    require "create_package.php";  
    
    }
 ///////////////////////////////////////////////////////////////////////////////////////////////////////////   
/*    //if a gene list generated in local according to a uploaded gene list 
    elseif($ID_array&&!isset($_POST["uploadedxmlfile"])){
	$show_name = $_POST['show_name'];
	$ID_type = (string)$_POST['ID_type'];                   // Sets $ID_type according to value from dropdown menu
	$Source_Loc_Term = $_POST['Source_Loc_Term'];   // Sets $Source_Loc_Term according to value from dropdown menu
    
	//require 'UploadIDToUniprotACC.php';	    // Converts uploaded $ID_array into Uniprot IDs
	UploadIDToUniprotACC($ID_type,$ID_array,$HumMouseFlag);
	//require 'mentha_network.php';
	
	$mentha_add=0;
	if(isset($_POST["mentha_add"])&&$_POST["mentha_add"]=="1"){$mentha_add=1;}
	echo $_POST["mentha_add"]."->".$mentha_add;
	list($org_nodes,$ID_array,$ACC_GN)=mentha_network($session_name,$show_name,$mentha_add);
	$ID_type = "UniprotACC";
	$ACCfromUniprot=NULL;
	foreach ( $ID_array as $key => $value ) {
	    $TheseACCs = "$value\t$value\n";
	    $ACCfromUniprot .= $TheseACCs;
	}
	file_put_contents("ACCfromUniprot.tsv", $ACCfromUniprot);
	
	/////////////////////////////////////////////////////////
	// Put Query IDs into temporary MySQL table
	$sql="CREATE TEMPORARY TABLE query_ids (QueryID VARCHAR (255))";
	$result = mysql_query($sql) or die(mysql_error());
	$sql="INSERT INTO query_ids VALUES ";
	foreach ($ID_array as $item) {
	    $sql .= "('".$item."'),";
	}
	$sql = rtrim($sql, ',');
	$result = mysql_query($sql) or die(mysql_error());
	// Create a copy of this temporary table for use in queries where these same values need to be queried twice
	$sql = "CREATE TEMPORARY TABLE query_ids2 AS SELECT * FROM query_ids";
	$result = mysql_query($sql) or die(mysql_error());
	
	//require 'QueriesAndUniprotToTempTable.php';     // Puts query IDs and Uniprot IDs into temporary MySQL table called listofids
	QueriesAndUniprotToTempTable();
	require 'ACCtoGO.php';                          // Queries QuickGO with IDs, downloads a tsv file with GO terms
	require 'JOINSandOutput.php';                   // Puts contents of TSV file into MySQL table, queries it against mapping file, and prints out the results
	require 'add_location_xml.php';
	$xml_file_name=$session_name.".xml";
	$xml =  add_location_to_xml($QueryID,"Uniprot ID",$xml_file_name,$OurLocalization);
	require 'visualization.php';
	require "create_package.php";                   // create Zip to download
	
    }*/
//if a gene list generated in local according to a uploaded gene list 
    elseif($ID_array&&!isset($_POST["uploadedxmlfile"])){
	//time for all
	$all_start = microtime(true);
    
	$ID_type = (string)$_POST['ID_type'];                   // Sets $ID_type according to value from dropdown menu
	
	//localization for queries
	$start_1 = microtime(true);
	/////////////////////////////////////////////////////////
	// Put Query IDs into temporary MySQL table
	$start = microtime(true);
	
	$sql="CREATE TEMPORARY TABLE query_ids (QueryID VARCHAR (255))";
	$result = mysql_query($sql) or die(mysql_error());
	$sql="INSERT INTO query_ids VALUES ";
	foreach ($ID_array as $item) {
	    $sql .= "('".$item."'),";
	}
	$sql = rtrim($sql, ',');
	$result = mysql_query($sql) or die(mysql_error());
	// Create a copy of this temporary table for use in queries where these same values need to be queried twice
	$sql = "CREATE TEMPORARY TABLE query_ids2 AS SELECT * FROM query_ids";
	$result = mysql_query($sql) or die(mysql_error());
	
	$end = microtime(true);
	$time= $end - $start;
	$time = round($time, 2);      // Round to 2 decimal places
	echo "<br />----Put Query IDs into temporary MySQL table took:$time seconds.</br>";
	/////////////////////////////////////////////////////////
	
	$ID_type = $_POST['ID_type'];                   // Sets $ID_type according to value from dropdown menu
	$Source_Loc_Term = $_POST['Source_Loc_Term'];   // Sets $Source_Loc_Term according to value from dropdown menu  
    
    	//require 'UploadIDToUniprotACC.php';             // Converts uploaded $ID_array into Uniprot IDs
	$start = microtime(true);
	UploadIDToUniprotACC($ID_type,$ID_array,$HumMouseFlag);
	$end = microtime(true);
	$time= $end - $start;
	$time = round($time, 2);      // Round to 2 decimal places
	echo "<br />----UploadIDToUniprotACC took:$time seconds.</br>";
	
	

	//require 'QueriesAndUniprotToTempTable.php';     // Puts query IDs and Uniprot IDs into temporary MySQL table called listofids
	$start = microtime(true);
	QueriesAndUniprotToTempTable($ID_array);
	$end = microtime(true);
	$time= $end - $start;
	$time = round($time, 2);      // Round to 2 decimal places
	echo "<br />----QueriesAndUniprotToTempTable took:$time seconds.</br>";
	
	$start = microtime(true);
	require 'ACCtoGO.php';                          // Queries QuickGO with IDs, downloads a tsv file with GO terms
	$end = microtime(true);
	$time= $end - $start;
	$time = round($time, 2);      // Round to 2 decimal places
	echo "<br />----ACCtoGO took:$time seconds.</br>";
	
	$start_tab = microtime(true);	
	require 'JOINSandOutput.php';
/*	
	foreach($QueryID_ACC as $qu=>$acc){
	    echo $qu."=>".$acc."<br/>";
	}
	echo "ok!<br/>";
*/
//	$QueryID,$ACCs,$OurLocalization
//	$QueryID_1=$QueryID;
//	$ACCs_1=$ACCs;
	$OurLocalization_1=$OurLocalization;    
	$QueryID=array_keys($QueryID_ACC);
	$QueryID_1=$QueryID;
	
	$end_tab = microtime(true);
	$time_tab= $end_tab - $start_tab;
	$time_tab = round($time_tab, 2);      // Round to 2 decimal places
	echo "<br />----JOINSandOutput 1 took:$time_tab seconds.</br>";
	
	$end_1 = microtime(true);
	$time_1= $end_1 - $start_1;
	$time_1 = round($time_1, 2);      // Round to 2 decimal places
	echo "<br />Localization 1 took:$time_1 seconds.</br>";
	
	
	
	
	//require 'mentha_network.php';
	$start = microtime(true);
	
	$mentha_add=0;	// no relative protein added
	if(isset($_POST["mentha_add"])&&$_POST["mentha_add"]=="1"){$mentha_add=1;}
	//echo $_POST["mentha_add"]."->".$mentha_add;
	//list($prot_add,$ACC_GN)=mentha_network($session_name,$show_name,$ACCs_1,$mentha_add);
	list($prot_add)=mentha_network($session_name,$QueryID_ACC,$mentha_add);
	//echo "MENTHA ok!<br/>";
	$ID_type = "UniprotACC";
	
	$end = microtime(true);
	$time= $end - $start;
	$time = round($time, 2);      // Round to 2 decimal places
	echo "<br />mentha_network took:$time seconds.</br>";

    //  localization for added proteins
    $start_2 = microtime(true);
    if($mentha_add==1&&$prot_add!=null){
	    $ACCfromUniprot=NULL;
	    foreach ( $prot_add as $value ) {
		//echo $value;
		$TheseACCs = "$value\t$value\n";
		$ACCfromUniprot .= $TheseACCs;
	    }
	    file_put_contents("ACCfromUniprot.tsv", $ACCfromUniprot);
	
	if(file_exists("ACCfromUniprot.tsv")){
	    echo '<br/><a href="ACCfromUniprot.tsv" >download ACCfromUniprot.tsv</a>';
	}
	/////////////////////////////////////////////////////////
	//contruct new database
	$ID_array=$prot_add;
	// Put Query IDs into temporary MySQL table
	$sql="DROP TEMPORARY TABLE IF EXISTS query_ids CASCADE";
        $result = mysql_query($sql) or die(mysql_error());
	$sql="CREATE TEMPORARY TABLE query_ids (QueryID VARCHAR (255))";
	$result = mysql_query($sql) or die(mysql_error());
	$sql="INSERT INTO query_ids VALUES ";
	foreach ($ID_array as $item) {
	    $sql .= "('".$item."'),";
	}
	$sql = rtrim($sql, ',');
	$result = mysql_query($sql) or die(mysql_error());
	// Create a copy of this temporary table for use in queries where these same values need to be queried twice
	$sql = "DROP TEMPORARY TABLE IF EXISTS query_ids2 CASCADE";
	$result = mysql_query($sql) or die(mysql_error());
	$sql = "CREATE TEMPORARY TABLE query_ids2 AS SELECT * FROM query_ids";
	$result = mysql_query($sql) or die(mysql_error());
	///////////////////////////////////////////////////////
	echo "CREATE TABLE ok!<br/>";
	//require 'QueriesAndUniprotToTempTable.php';     // Puts query IDs and Uniprot IDs into temporary MySQL table called listofids
	$start = microtime(true);
	QueriesAndUniprotToTempTable($ID_array);
	$end = microtime(true);
	$time= $end - $start;
	$time = round($time, 2);      // Round to 2 decimal places
	echo "<br />----QueriesAndUniprotToTempTable 2 took:$time seconds.</br>";
	
	echo "TempTable ok!<br/>";
	$start = microtime(true);
	require 'ACCtoGO.php';                          // Queries QuickGO with IDs, downloads a tsv file with GO terms
	echo "ACCtoGO ok!<br/>";
	$end = microtime(true);
	$time= $end - $start;
	$time = round($time, 2);      // Round to 2 decimal places
	echo "<br />----ACCtoGO 2 took:$time seconds.</br>";
	
	$start_tab = microtime(true);
	require 'JOINSandOutput.php';                   // Puts contents of TSV file into MySQL table, queries it against mapping file, and prints out the results
	$end_tab = microtime(true);
	$time_tab= $end_tab - $start_tab;
	$time_tab = round($time_tab, 2);      // Round to 2 decimal places
	echo "<br />----JOINSandOutput 2 took:$time_tab seconds.</br>";
	
	$end_2 = microtime(true);
	$time_2= $end_2 - $start_2;
	$time_2 = round($time_2, 2);      // Round to 2 decimal places
	echo "<br />Localization for added proteins took:$time_2 seconds.</br>";

	$QueryID_2=$QueryID;
//	$ACCs_2=$ACCs;
	$OurLocalization_2=$OurLocalization;
	
	$QueryID = array_merge($QueryID_1,$QueryID_2);
//	$ACCs = array_merge($ACCs_1,$ACCs_2);
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
	$start = microtime(true);
	require 'add_location_xml_mentha.php';	
	$xml_file_name=$session_name.".xml";
	$xml =  add_location_to_xml_mentha($QueryID,$xml_file_name,$OurLocalization);
	$end = microtime(true);
	$time= $end - $start;
	$time = round($time, 2);      // Round to 2 decimal places
	echo "<br />add_location_xml_mentha took:$time seconds.</br>";
	
	//time
	$start = microtime(true);	
	//echo "visualization<br/>"; 
	require 'visualization.php';
	$end = microtime(true);
	$vis_Duration = $end - $start;
	$vis_Duration = round($vis_Duration, 2);      // Round to 2 decimal places
	echo "<br />Visualization took:$vis_Duration seconds.</br>";
	
	$start = microtime(true);
	require "create_package.php";                   // create Zip to download
	$end = microtime(true);
	$time= $end - $start;
	$time = round($time, 2);      // Round to 2 decimal places
	echo "<br />create_package took:$time seconds.</br>";
		
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

//===================================================Functions===============================================
//UploadIDToUniprotACC.php
function UploadIDToUniprotACC($ID_type,$ID_array,$HumMouseFlag){
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
        foreach ( $ID_array as $key => $value ) {
            if (empty($value)) {continue;}      // Ignore empty values
            $TheseACCs = '';                    // Initialize array to store ACCs retrieved for a single query ID
            if ($HumMouseFlag == "HumMouseOverlap") {
                $URLquery = "http://www.uniprot.org/uniprot/?query=(gene_exact:". $value .")+and+(organism:9606+OR+organism:10090)+and+reviewed:yes&columns=id&format=tab";
            } elseif ($HumMouseFlag == "HumanOnly") {
                $URLquery = "http://www.uniprot.org/uniprot/?query=(gene_exact:" . $value . ")+and+(organism:9606)+and+reviewed:yes&columns=id&format=tab";
            } elseif ($HumMouseFlag == "MouseOnly") {
                $URLquery = "http://www.uniprot.org/uniprot/?query=(gene_exact:" . $value . ")+and+(organism:10090)+and+reviewed:yes&columns=id&format=tab";
            }
   	    //echo $URLquery."<br/>";
            set_time_limit(120);
            $TheseACCs = explode("\n", chop(file_get_contents($URLquery)));
            array_shift($TheseACCs);
            foreach ( $TheseACCs as $key2 => $value2 ) {    // Make table with separate row for the query ID (1st column)
                $TheseACCs[$key2] = "$value\t$value2";      // and each retrieved ACC (2nd column)
            }
            $ACCfromUniprot .= implode("\n", $TheseACCs);   // Successively combine tables of all Query IDs and their retrieved ACCs
            $ACCfromUniprot .= "\n";
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
        echo $TheseIDs;
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

file_put_contents("ACCfromUniprot.tsv", $ACCfromUniprot);
file_put_contents("ACCfromUniprot_1.tsv", $ACCfromUniprot);
    if(file_exists("ACCfromUniprot_1.tsv")){
	echo '<br/><a href="ACCfromUniprot_1.tsv" >download ACCfromUniprot_1.tsv</a>';
    }

//rename("QuickGO_tmp.tsv", "uploads/QuickGO_tmp.tsv");  //This can be useful if wanting to move the file

// Code to read the data out
//$fh = fopen("QuickGO_tmp.tsv", 'r') or die("Can't open file");
//$theData = fread($fh, filesize("QuickGO_tmp.tsv"));
//echo $theData;
//fclose($fh);
}

function QueriesAndUniprotToTempTable(){
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////  Originally 'QueriesAndUniprotToTempTable.php': Puts query IDs and Uniprot IDs into temporary MySQL table called listofids /////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    // Create a temporary MySQL table to store the uploaded IDs
    
    $sql = "DROP TEMPORARY TABLE IF EXISTS listofids CASCADE";
    $result = mysql_query($sql) or die(mysql_error());
    $sql="CREATE TEMPORARY TABLE listofids (QueryID VARCHAR (50), ACC VARCHAR (50))";
    $result = mysql_query($sql) or die(mysql_error());
    
    $sql2 = "LOAD DATA LOCAL INFILE 'ACCfromUniprot.tsv'
	    INTO TABLE listofids
	    FIELDS TERMINATED BY '\t'
	    OPTIONALLY ENCLOSED BY '\"'
	    LINES TERMINATED BY '\n'";
    $result2 = mysql_query($sql2) or die(mysql_error());
    
    // Create a copy of this temporary table for use in queries where these same values need to be queried twice
    $sql = "DROP TEMPORARY TABLE IF EXISTS listofids2 CASCADE";
    $result = mysql_query($sql) or die(mysql_error());
    $sql9 = "CREATE TEMPORARY TABLE listofids2 AS SELECT * FROM listofids";
    $result9 = mysql_query($sql9) or die(mysql_error());
    
    // Count how many distinct (non-duplicate) Uniprot Accessions were retrieved
    $sql="SELECT COUNT(DISTINCT QueryID) FROM listofids WHERE QueryID IS NOT NULL AND TRIM(QueryID) != ''";     // TRIM(QueryID) != '' was necessary because some rows consist of white 
														// space but are not null. I couldn't figure out their origin.
    $result = mysql_query($sql) or die(mysql_error());
    $resultRow = mysql_fetch_row($result);
    if($resultRow) { echo "<br/>Uniprot accessions were found for " . $resultRow[0] . " query ID(s)."; }
    
    // Check if any of the query IDs were not found in Uniprot, and list them in output
    $SomeIDnotFoundFlag = 0;
    $result = mysql_query("SELECT QueryID FROM listofids") or die(mysql_error());
    $column = array();
    while($row = mysql_fetch_array($result)){
	array_push($column, $row["QueryID"]);   // Put all the retrieved IDs into an array
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


    /*Description : Mentha
    http://mentha.uniroma2.it:8080/server/getInteractions?org=all&ids=O00273,P63104*/
    function show_org_uniprot($org_uniprot){
	print($org_uniprot);
	foreach($org_uniprot as $pr){
	    echo ":".$pr."_";
	}
	echo "<br/>";
    }
    
    function mentha_network($session_name,$QueryID_ACC,$mentha_add){
    //$QueryID_ACC[QueryID] = ACC;
    // prot in the list
    $org_uniprot = array_values($QueryID_ACC);
    $org_queryID = array_keys($QueryID_ACC);
    //foreach($org_queryID as $id){echo $id."<br/>";}
    $ACCs  = implode(",",$org_uniprot);
    //$ACCs  = preg_replace("/\n[a-zA-Z0-9_]+\t/",",",$ACCfile);  //string
	$org_uniprot=array_filter(explode(",",$ACCs));                //array
	
	//query to mentha sever
	if(@fopen('http://mentha.uniroma2.it:8080/server/getInteractions?org=all&ids='.$ACCs,"rb")){
	  $start = microtime(true);
	  $M_network=fopen('http://mentha.uniroma2.it:8080/server/getInteractions?org=all&ids='.$ACCs,"rb");
	  $end = microtime(true);
	  $mentha_Duration = $end - $start;
	  $mentha_Duration = round($mentha_Duration, 2);      // Round to 2 decimal places
	  echo "<br />----Mentha took:$mentha_Duration seconds.</br>";
	}
	echo '----http://mentha.uniroma2.it:8080/server/getInteractions?org=all&ids='.$ACCs;
	
	//time for ranking the interaction
	$start = microtime(true);
	$interaction=$both=$one=$none=array();
	//rank the added prots  
	while(!feof($M_network)){
	  $line = fgets($M_network);
	  if($line){
	    list($prot_A,$org_A,$prot_B,$org_B,$score)= array_filter(explode(";",$line));
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
	
	$prot_mentha=array_unique($prot_mentha);
	if($both){        arsort($both); $interaction=array_merge($interaction,$both);      }
	if($mentha_add==1){ // if allow mentha added protein
	    if($one) {        arsort($one); $interaction=array_merge($interaction,$one);        } 
	    if($none){        arsort($none); $interaction=array_merge($interaction,$none);      }
	}
	
	$prot_add=null;		// other proteins added by mentha ACC
	$prot_all=$org_queryID;	// original query proteins queryID
	    
	if($interaction!=null){
	    $top=40;
	    if(count($prot_mentha)>$top){
		$prot_top=array();
	      $interaction=array_slice($interaction,0,$top);
	      foreach($interaction as $protAB=>$score){
		$prot=explode('-',$protAB);
		$prot_top[]=$prot[0];
		$prot_top[]=$prot[1];
	      }
	      $prot_add = array_diff($prot_top,$org_uniprot);
	      //$prot_all=array_merge($org_uniprot,$prot_add);
	      $prot_all=array_merge($org_queryID,$prot_add);
	    }
	}
	//echo "prot_add contains".count($prot_add)."nodes";
        //echo "prot_all contains".count($prot_all)."nodes";
	
	$end = microtime(true);
	$mentha_rank= $end - $start;
	$mentha_rank = round($mentha_rank, 2);      // Round to 2 decimal places
	echo "<br />----Mentha ranking took:$mentha_rank seconds.</br>";
    
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
	// <edge id="27113" label="H__sapiens__1_-Hs:8997819|H__sapiens__1_-Hs:9044627|Shared protein domains" source="23605" target="23534" cy:directed="0">
       //    <graphics fill="#dad4a2" width="1.0001760324548046">
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
</body></html>
