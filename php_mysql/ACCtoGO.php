<html><body>
<?php
// Takes values from ACC column of listofids table created by QueriesAndUniprotToTempTable.php (must be Uniprot ACCessions) and joins them using the ASCII keycode for a comma (in hexadecimal)
// The string is then passed to a QuickGO query that works to query Ensembl gene IDs and returns a tab-separated file listing various parameters. Based on this:
// http://www.ebi.ac.uk/QuickGO/GAnnotation?&db=UniProtKB&format=tsv&aspect=C&protein=P11532%2CP10145&col=proteinID%2CproteinSymbol%2CgoID%2CgoName%2Caspect%2Cevidence%2CproteinTaxon%2Cdate


if (($Source_Loc_Term == "UniprotAndGO") || ($Source_Loc_Term == "GOonly")) {  // Check if the Gene ontology is being used in this query
    
    // Get Uniprot ACCs from listofids table, search for each in QuickGO
    
    $QuickGO_tmp = '';
    $result = mysql_query("SELECT ACC FROM listofids");
    // $num_rows = mysql_num_rows($result);
    $ACCarrayFromTable = Array();
    while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
        $ACCarrayFromTable[] =  $row['ACC'];
    }
    $ArrayOfACCarrays = array_chunk($ACCarrayFromTable, 50);
    
    $start = microtime(true);
    foreach ( $ArrayOfACCarrays as $key => $value ) {
        $ACC_string = implode("%2C", $value);    
        // Builds up the URL query to send to QuickGO
        $URLquery_part1 = "http://www.ebi.ac.uk/QuickGO/GAnnotation?&db=UniProtKB&format=tsv&aspect=C&protein=";
        $URLquery_part2 = $ACC_string;
        $URLquery_part3 = "&col=proteinID%2CproteinSymbol%2CgoID%2CgoName%2Caspect%2Cevidence%2CproteinTaxon%2Cdate";
        $URLquery = $URLquery_part1 . $URLquery_part2 . $URLquery_part3;
        set_time_limit(120);
        file_put_contents("QuickGO_tmp.tsv", file_get_contents($URLquery), FILE_APPEND);
    }
    $end = microtime(true);
    $QuickGODuration = $end - $start;
    $QuickGODuration = round($QuickGODuration, 2);      // Round to 2 decimal places
    echo "<br />QuickGO took: $QuickGODuration seconds.";
 
    // Now read the QuickGO output into a temporary MySQL table
    
    // Max size of VARCHAR is 255, otherwise use TEXT
    // I had to create the table first before importing from the tsv file (below)
    // LOAD DATA INFILE INTO TABLE doesn't seem able to create a table on the fly
    
    $sql = "DROP TEMPORARY TABLE IF EXISTS quickgotmp CASCADE";
    $result = mysql_query($sql) or die(mysql_error());
    
    $sql="CREATE TEMPORARY TABLE quickgotmp (ID VARCHAR (255), Symbol VARCHAR (255), GO_ID VARCHAR (255), GO_name VARCHAR (255), Aspect VARCHAR (255), Evidence VARCHAR (255), Taxon VARCHAR (255), Date VARCHAR (255))";
    $result = mysql_query($sql) or die(mysql_error());
    
    // Now to be consistent with table map_acc_to_uniprot_loc, rename the column 'GO_ID' to 'Localization':
    $result = mysql_query("ALTER TABLE quickgotmp CHANGE GO_ID Localization varchar(50)") or die(mysql_error());  
 
    // The most annoying part here was finding the correct line terminator
    // I tried \r\n (usual windows) but this didn't work: \n works
    // Maybe QuickGO uses \n by default. EditPadLite was able to use \n
    // but Notepad couldn't - maybe useful for quick checks in future
    //
    // **** If problems with empty results in future, use of LOCAL below may be the problem****
    // LOAD DATA INFILE cannot find the local file: adding 'LOCAL' fixed this
    // Solution was found here: http://stackoverflow.com/questions/8471727/load-data-infile-does-not-work
    // Based on comment here: http://ubuntuforums.org/showthread.php?t=822084
    // I actually have no idea how this works (more info maybe here: http://dev.mysql.com/doc/refman/5.0/en/load-data-local.html)
    //
    // general solution for csv upload given here: http://stackoverflow.com/questions/11448307/importing-csv-data-using-php-mysql
    // and here: http://stackoverflow.com/questions/11432511/save-csv-files-into-mysql-database/11432767
    
    $sql2 = "LOAD DATA LOCAL INFILE 'QuickGO_tmp.tsv'
            INTO TABLE quickgotmp
            FIELDS TERMINATED BY '\t'
            OPTIONALLY ENCLOSED BY '\"'
            LINES TERMINATED BY '\n'
            IGNORE 1 LINES";  
    $result2 = mysql_query($sql2) or die(mysql_error());
}

//rename("QuickGO_tmp.tsv", "uploads/QuickGO_tmp.tsv");  //This can be useful is wanting to move the file

// Code to read the data out
//$fh = fopen("QuickGO_tmp.tsv", 'r') or die("Can't open file");
//$theData = fread($fh, filesize("QuickGO_tmp.tsv"));
//echo $theData;
//fclose($fh);

?>
</body></html>