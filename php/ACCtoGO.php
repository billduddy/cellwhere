<html>
     <body>
	  <?php
	  // Takes values from ACC column of listofids table created by QueriesAndUniprotToTempTable.php (must be Uniprot ACCessions) and joins them using the ASCII keycode for a comma (in hexadecimal)
	  // The string is then passed to a QuickGO query that works to query Ensembl gene IDs and returns a tab-separated file listing various parameters. Based on this:
	  // http://www.ebi.ac.uk/QuickGO/GAnnotation?&db=UniProtKB&format=tsv&aspect=C&protein=P11532%2CP10145&col=proteinID%2CproteinSymbol%2CgoID%2CgoName%2Caspect%2Cevidence%2CproteinTaxon%2Cdate
	       
	       if (($Source_Loc_Term == "UniprotAndGO") || ($Source_Loc_Term == "GOonly")) {  // Check if the Gene ontology is being used in this query  
		    // Get Uniprot ACCs from listofids table, search for each in QuickGO
		    
		    $QuickGO_tmp = '';
		    //$result = mysql_query("SELECT ACC FROM listofids");
		    $result = pg_query("SELECT ACC FROM listofids") or die("error");
		    //echo "ok<br/>";
		    // $num_rows = mysql_num_rows($result);
		     $ACCarrayFromTable = array();
		    //while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
		    //while ($row = pg_fetch_array($result,NULL,PGSQL_ASSOC)) {
		    while ($row = pg_fetch_array ($result)) {
			 $ACCarrayFromTable[] =  $row[0];
		    }
		    $ArrayOfACCarrays = array_chunk($ACCarrayFromTable, 50);
		    //echo "ok<br/>"; 
		   
		    $start = microtime(true);
		    foreach ( $ArrayOfACCarrays as $key => $value ) {
			 $ACC_string = implode("%2C", $value);    
			 // Builds up the URL query to send to QuickGO
			 $URLquery_part1 = "http://www.ebi.ac.uk/QuickGO/GAnnotation?&db=UniProtKB&format=tsv&aspect=C&protein=";
			 $URLquery_part2 = $ACC_string;
			 $URLquery_part3 = "&col=proteinID%2CproteinSymbol%2CgoID%2CgoName%2Caspect%2Cevidence%2CproteinTaxon%2Cdate";
			 $URLquery = $URLquery_part1 . $URLquery_part2 . $URLquery_part3;
			 //set_time_limit(120);
			 //echo $URLquery."<br/>";
			 $go_res=fopen($URLquery,"rb");
			 file_put_contents("QuickGO_tmp.csv", $go_res, FILE_APPEND);
			 fclose($go_res);
			 //file_put_contents("QuickGO_tmp.tsv", file_get_contents($URLquery), FILE_APPEND);
			 //file_put_contents("QuickGO_tmp.tsv", curl_get_file_contents($URLquery), FILE_APPEND);
		    }
		   
		    $end = microtime(true);
		    $QuickGODuration = $end - $start;
		    $QuickGODuration = round($QuickGODuration, 2);      // Round to 2 decimal places
		    echo "<br />QuickGO took: $QuickGODuration seconds.";
		   /*
		    if(file_exists("QuickGO_tmp.csv")){
			echo '<br/><a href="QuickGO_tmp.csv" >download QuickGO_tmp.csv</a>';
		    }
		   */
		   
		   
		   // Now read the QuickGO output into a temporary MySQL table
		   
		   // Max size of VARCHAR is 255, otherwise use TEXT
		   // I had to create the table first before importing from the tsv file (below)
		   // LOAD DATA INFILE INTO TABLE doesn't seem able to create a table on the fly
		   
		   $sql = "DROP TABLE IF EXISTS quickgotmp CASCADE";
		   $result = pg_query($sql) or die("error");
		   
		   $sql="CREATE TEMPORARY TABLE quickgotmp (ID VARCHAR (255), Symbol VARCHAR (255), GO_ID VARCHAR (255), GO_name VARCHAR (255), Aspect VARCHAR (255), Evidence VARCHAR (255), Taxon VARCHAR (255), Date VARCHAR (255))";
		   $result = pg_query($sql) or die("error");
		   
		   // Now to be consistent with table map_acc_to_uniprot_loc, rename the column 'GO_ID' to 'Localization':
		   //$result = mysql_query("ALTER TABLE quickgotmp CHANGE GO_ID Localization varchar(50)") or die("error");
		   $result = pg_query("ALTER TABLE quickgotmp
				      RENAME COLUMN GO_ID TO Localization;") or die("error");
		   
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
		    
		    if(file_exists("QuickGO_tmp.csv")){
			 $sql2 = "COPY quickgotmp FROM '".getcwd()."/QuickGO_tmp.csv' DELIMITER as '\t' CSV HEADER;";
			 //$result2 = mysql_query($sql2) or die(mysql_error());
			 $result2 = pg_query($sql2) or die("error");
		    }
	       }  
	  ?>
     </body>
</html>