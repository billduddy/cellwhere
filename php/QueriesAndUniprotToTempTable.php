<?php

// Create a temporary MySQL table to store the uploaded IDs
$sql="CREATE TEMPORARY TABLE listofids (QueryID VARCHAR (50), ACC VARCHAR (50))";
$result = mysql_query($sql) or die(mysql_error());

$sql2 = "LOAD DATA LOCAL INFILE 'ACCfromUniprot.tsv'
        INTO TABLE listofids
        FIELDS TERMINATED BY '\t'
        OPTIONALLY ENCLOSED BY '\"'
        LINES TERMINATED BY '\n'";
$result2 = mysql_query($sql2) or die(mysql_error());

// Create a copy of this temporary table for use in queries where these same values need to be queried twice
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

?>