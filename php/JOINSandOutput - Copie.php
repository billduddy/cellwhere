<?php
// This uses MySQL to map location terms from Uniprot and/or GO to CellWhere localizations, then prioritise them using
// UniquePriorityNumber, and returns an html table listing only the location having the highest priority score for each query ID.

// This query was a b*tch. It was important to use 'as' in set MAX(UniquePriorityNumber) as UniquePriorityNumber.
// The inner, indented, part joins the temp table from GO with our mapping file, selecting some parameters
// The outer parts group by symbol then select the max of UniquePriorityNumber
// This helped: http://stackoverflow.com/questions/19964640/mysql-nested-select-query
// ORDER BY DESC was important: otherwise you get the correct priority number, but the
// name of the localization is whichever was top of the SQL table

// Query tested by undoing temporariness of tables, running a search, then running query directly in PHPadmin
// Update 010114: At the moment, it is giving back a list that includes IDs for which no localization is recovered
// However, all the examples seem also to have no Gene Ontology associated: I think there should be some
// that have gene ontology but no localization (because our mapping file doesn't have that gene ontology)
// To test this, I should destroy one of the localizations (e.g. 7285 Sarcoplasmic reticulum membrane) to
// see if it will now show the gene ontology without the localization, or if the gene ontology will not be shown.
// Update 080114: This seems fine: by deleting some localizations from map_go_to_loc_muscle I was able to generate
// some hits that returned GO terms but not localizations.

echo "enter JOINSandOutput<br/>";

$sql = "DROP TABLE IF EXISTS results CASCADE";
pg_query($sql) or die();
$sql = "DROP TABLE IF EXISTS allresults CASCADE";
pg_query($sql) or die();
$sql = "DROP TABLE IF EXISTS temp_allresults CASCADE";
pg_query($sql) or die();
echo "ok<br/>";
//============================================================ALL results=====================================================================
//////////////////////////////////////////////////////////////////
// Code to download all results, not just max priority number
// It is important to use DISTINCT here, especially for GOonly, as the QuickGO_tmp.tsv has many duplicates (UNION probably makes DISTINCT redundant in the UniprotAndGO query)
/*
    // show results
    $sql="SELECT *
	  FROM listofids";
    $result = pg_query($sql) or die("error");   
    while($row = pg_fetch_array($result)) {
	echo $row[0]."-".$row[1]."<br/>";
    }

    echo "----------<br/>query ids"; 
    $sql="SELECT *
	  FROM query_ids";
    $result = pg_query($sql) or die("error");   
    while($row = pg_fetch_array($result)) {
	echo $row[0]."-<br/>";
    }
    
    echo "----------<br/>";
    // show results
    $sql="SELECT *
	  FROM query_ids
	  LEFT OUTER JOIN listofids
          ON listofids.QueryID = query_ids.QueryID";
    $result = pg_query($sql) or die("error");   
    while($row = pg_fetch_array($result)) {
	echo $row[0]."	".$row[1]."	".$row[2]."<br/>";
    }
*/    



$start = microtime(true);

switch ($Source_Loc_Term) {                 // Depending on selection on dropdown menu
     case "GOonly":
	  // To map using only the Gene Ontology (works 240214)
          
          $sql = "
		    CREATE TEMPORARY TABLE temp_allresults AS
                    SELECT DISTINCT a.QueryID, a.ACC, a.Symbol, a.Localization, map_" . "$Flavour" . "_flavour.UniquePriorityNumber, COALESCE(NULLIF(map_" . "$Flavour" . "_flavour.OurLocalization, ''), 'Unknown') AS OurLocalization
		    FROM (
			     SELECT y.QueryID, y.ACC, quickgotmp.Symbol, quickgotmp.Localization
			     FROM (
				   SELECT query_ids.QueryID, listofids.ACC
				   FROM query_ids
					LEFT JOIN listofids ON listofids.QueryID = query_ids.QueryID
			     ) AS y
				     LEFT JOIN quickgotmp ON quickgotmp.ID = y.ACC
		    ) AS a
			    LEFT JOIN map_" . "$Flavour" . "_flavour ON map_" . "$Flavour" . "_flavour.GO_id_or_uniprot_term = a.Localization
		 ";
                 
          pg_query($sql) or die("error");  
	  break;
     
     case "UniprotAndGO":  
	  // To map using both Uniprot and the Gene Ontology (works 240214)     
          $sql = "
		    CREATE TEMPORARY TABLE temp_allresults AS          
                    SELECT DISTINCT a.QueryID, a.ACC, a.UniprotID, a.Localization, map_" . "$Flavour" . "_flavour.UniquePriorityNumber, COALESCE(NULLIF(map_" . "$Flavour" . "_flavour.OurLocalization, ''), 'Unknown') AS OurLocalization
		    FROM (
			   SELECT z.QueryID, z.ACC, map_acc_to_uniprot_loc.UniprotID, map_acc_to_uniprot_loc.Localization
			   FROM (
				 SELECT query_ids.QueryID, listofids.ACC
				 FROM query_ids
				      LEFT JOIN listofids ON listofids.QueryID = query_ids.QueryID
			   ) AS z
				   LEFT JOIN map_acc_to_uniprot_loc ON map_acc_to_uniprot_loc.UniprotACC = z.ACC
			   UNION
			   SELECT y.QueryID, y.ACC, quickgotmp.Symbol, quickgotmp.Localization
			   FROM (
				 SELECT query_ids2.QueryID, listofids2.ACC
				 FROM query_ids2
				      LEFT JOIN listofids2 ON listofids2.QueryID = query_ids2.QueryID
			   ) AS y
				   LEFT JOIN quickgotmp ON quickgotmp.ID = y.ACC	
		    ) AS a
			    LEFT JOIN map_" . "$Flavour" . "_flavour ON map_" . "$Flavour" . "_flavour.GO_id_or_uniprot_term = a.Localization
		 ";
		 
          pg_query($sql) or die('error');  
	  break;  
     
     case "UniprotOnly":  		 
	  // To map using only Uniprot (works 240214)
	  $sql = "
		    CREATE TEMPORARY TABLE temp_allresults AS
                    SELECT DISTINCT a.QueryID, a.ACC, a.UniprotID, a.Localization, map_" . "$Flavour" . "_flavour.UniquePriorityNumber, COALESCE(NULLIF(map_" . "$Flavour" . "_flavour.OurLocalization, ''), 'Unknown') AS OurLocalization
                    FROM (
			    SELECT z.QueryID, z.ACC, map_acc_to_uniprot_loc.UniprotID, map_acc_to_uniprot_loc.Localization
			    FROM (
				  SELECT query_ids.QueryID, listofids.ACC
				  FROM query_ids
				       LEFT JOIN listofids ON listofids.QueryID = query_ids.QueryID
			    ) AS z
				    LEFT JOIN map_acc_to_uniprot_loc ON map_acc_to_uniprot_loc.UniprotACC = z.ACC
                    ) AS a
			    LEFT JOIN map_" . "$Flavour" . "_flavour ON map_" . "$Flavour" . "_flavour.GO_id_or_uniprot_term = a.Localization
		 ";
                 
          pg_query($sql) or die('error');  
	  break;

}
$sql = "
		    CREATE TEMPORARY TABLE allresults AS
                    
                    SELECT DISTINCT *
                    FROM temp_allresults
                    ORDER BY QueryID ASC
		 ";
		 
$result = pg_query($sql) or die('error');  				// Puts results of joins into a new temporary table called results
$end = microtime(true);
$time= $end - $start;
$time = round($time, 2);      // Round to 2 decimal places
echo "<br />--------result took:$time seconds.</br>";
//==========================================================================================================================================
/*
$result10 = pg_query("SELECT * FROM allresults") or die();			// Selects all from allresults table (not selecting max priority number)
$str_allresults = implode("\t", $Column_names_array_all_results)."\n";				// Loop through and make a string
while($row = pg_fetch_assoc($result10)) {
    $str_allresults .= implode("\t", $row)."\n";
}
$_SESSION['allresults'] = $str_allresults;							// Create a shared session variable storing allresults variable
echo '<a href="download_all.php?' . SID . '">Download all results (unfiltered by priority score)</a><br/>';	// Link to a new page that will show all results

*/
//=======================================================prioritized results================================================================
$start = microtime(true);
	
switch ($Source_Loc_Term) {                 // Depending on selection on dropdown menu
     case "GOonly":
	  // To map using only the Gene Ontology (works 280714)
	/*  $sql3 = "
		    CREATE TEMPORARY TABLE results AS
		    SELECT b.QueryID, b.ACC, b.Symbol, b.Localization, MAX(b.UniquePriorityNumber) AS UniquePriorityNumber, COALESCE(NULLIF(b.OurLocalization, ''), 'Unknown') AS OurLocalization
		    FROM (
                            SELECT DISTINCT *
                            FROM temp_allresults
                            ORDER BY UniquePriorityNumber DESC
		    ) AS b
		    GROUP BY b.QueryID
		    ORDER BY b.UniquePriorityNumber DESC
		 ";*/
          $sql = "DROP TABLE IF EXISTS temp_test CASCADE";
          pg_query($sql) or die();
          $temp = "   CREATE TEMPORARY TABLE temp_test AS
                SELECT QueryID,MAX(UniquePriorityNumber) AS UniquePriorityNumber
                FROM temp_allresults as b
                GROUP BY QueryID";
                pg_query($temp) or die("temp error");
	  $sql3 = "
                    CREATE TEMPORARY TABLE results AS
                    (select distinct b.QueryID, min(b.ACC) as ACC, b.Symbol,min(b.Localization) as Localization, b.UniquePriorityNumber, b.OurLocalization
                    from temp_allresults as b
                    INNER JOIN (SELECT QueryID,MAX(UniquePriorityNumber) AS UniquePriorityNumber
                                FROM temp_allresults
                                GROUP BY QueryID ) as a
                    on a.UniquePriorityNumber=b.UniquePriorityNumber and a.QueryID=b.QueryID
                    group by b.QueryID, b.Symbol,b.UniquePriorityNumber, b.OurLocalization
                    ORDER BY b.UniquePriorityNumber DESC)
                    UNION ALL
                    (select distinct bb.QueryID, min(bb.ACC) as ACC, bb.Symbol,min(bb.Localization) as Localization, bb.UniquePriorityNumber, bb.OurLocalization
                    from temp_allresults as bb
                    where bb.QueryID in (select aa.QueryID
                                         from temp_test as aa
                                         where aa.UniquePriorityNumber IS NULL)
                    group by bb.QueryID, bb.Symbol,bb.UniquePriorityNumber, bb.OurLocalization)
		 ";
	  break;
     case "UniprotAndGO":  
	  // To map using both Uniprot and the Gene Ontology (works 280714)
          $sql = "DROP TABLE IF EXISTS temp_test CASCADE";
          pg_query($sql) or die();
          $temp = "
                    CREATE TEMPORARY TABLE temp_test AS
                    SELECT QueryID,MAX(UniquePriorityNumber) AS UniquePriorityNumber
                    FROM temp_allresults as b
                    GROUP BY QueryID";
                    pg_query($temp) or die("temp error");
	  $sql3 = "
                    CREATE TEMPORARY TABLE results AS
                    (select DISTINCT b.QueryID, min(b.ACC) as ACC, min( b.UniprotID) as UniprotID ,min(b.Localization) as Localization, b.UniquePriorityNumber, b.OurLocalization
                    from temp_allresults as b
                    INNER JOIN (SELECT QueryID,MAX(UniquePriorityNumber) AS UniquePriorityNumber
                                FROM temp_allresults
                                GROUP BY QueryID ) as a
                    on a.UniquePriorityNumber=b.UniquePriorityNumber and a.QueryID=b.QueryID
                    group by b.QueryID, b.UniquePriorityNumber, b.OurLocalization
                    ORDER BY b.UniquePriorityNumber DESC)
                    UNION ALL
                    (select distinct bb.QueryID, min(bb.ACC) as ACC, bb.UniprotID,min(bb.Localization) as Localization, bb.UniquePriorityNumber, bb.OurLocalization
                    from temp_allresults as bb
                    where bb.QueryID in (select aa.QueryID
                                         from temp_test as aa
                                         where aa.UniquePriorityNumber IS NULL)
                    group by bb.QueryID, bb.UniprotID,bb.UniquePriorityNumber, bb.OurLocalization)
		 ";
	  break;
    case "UniprotOnly":  		 
	  // To map using only Uniprot(works 280714)
          $sql = "DROP TABLE IF EXISTS temp_test CASCADE";
          pg_query($sql) or die();
          $temp = "
                    CREATE TEMPORARY TABLE temp_test AS
                    SELECT QueryID,MAX(UniquePriorityNumber) AS UniquePriorityNumber
                    FROM temp_allresults as b
                    GROUP BY QueryID";
                    pg_query($temp) or die("temp error");
	  $sql3 = "
                    CREATE TEMPORARY TABLE results AS
                    (select DISTINCT b.QueryID, b.ACC, b.UniprotID,min(b.Localization) as Localization, b.UniquePriorityNumber, b.OurLocalization
                    from temp_allresults as b
                    INNER JOIN (SELECT QueryID,MAX(UniquePriorityNumber) AS UniquePriorityNumber
                                FROM temp_allresults
                                GROUP BY QueryID ) as a
                    on a.UniquePriorityNumber=b.UniquePriorityNumber and a.QueryID=b.QueryID
                    group by b.QueryID,b.ACC, b.UniprotID,b.UniquePriorityNumber, b.OurLocalization
                    ORDER BY b.UniquePriorityNumber DESC)
                    UNION ALL
                    (select distinct bb.QueryID, min(bb.ACC) as ACC, bb.UniprotID,min(bb.Localization) as Localization, bb.UniquePriorityNumber, bb.OurLocalization
                    from temp_allresults as bb
                    where bb.QueryID in (select aa.QueryID
                                         from temp_test as aa
                                         where aa.UniquePriorityNumber IS NULL)
                    group by bb.QueryID,bb.UniprotID,bb.UniquePriorityNumber, bb.OurLocalization)
		 ";
                 
	  break;
     
}

//$result3 = mysql_query($sql3) or die(mysql_error());				// Puts results of joins into a new temporary table called results
//b.QueryID, b.ACC, b.UniprotID, b.Localization,MAX(b.UniquePriorityNumber) AS UniquePriorityNumber,
/*
    $test = "   CREATE TEMPORARY TABLE temp_test AS
                SELECT QueryID,MAX(UniquePriorityNumber) AS UniquePriorityNumber
                FROM temp_allresults as b
                GROUP BY QueryID";
    $test_result = pg_query($test) or die("test error");
    $test = "select * from temp_test";
    $test_result = pg_query($test) or die("test error");
    while($row = pg_fetch_array($test_result)) {
        //foreach($row as $r) {echo $r."-";}
        echo $row[0]."      ".$row[1]."      ".$row[2]."<br/>";
    }
    echo "=================================<br/>";
    
    
    $test = "
    select *
    from temp_allresults as d
    inner join (select QueryID,min(ACC) as ACC_2
    from temp_allresults as b
    where b.QueryID in (select a.QueryID
    from temp_test as a
    where a.UniquePriorityNumber IS NULL)
    group by QueryID)as c
    on c.ACC_2=d.ACC and c.QueryID=d.QueryID
    ";
/*
    $test = "
                        (select DISTINCT b.QueryID, b.ACC, b.UniprotID,b.OurLocalization as Localization, b.UniquePriorityNumber, b.OurLocalization
                    from temp_allresults as b
                    INNER JOIN (SELECT QueryID,MAX(UniquePriorityNumber) AS UniquePriorityNumber
                                FROM temp_allresults
                                GROUP BY QueryID ) as a
                    on a.UniquePriorityNumber=b.UniquePriorityNumber and a.QueryID=b.QueryID
                    ORDER BY b.UniquePriorityNumber DESC)
                    UNION ALL
                    (select distinct dd.QueryID, dd.ACC, dd.UniprotID,dd.OurLocalization as Localization, dd.UniquePriorityNumber, dd.OurLocalization
                    from temp_allresults as dd
                    inner join (select QueryID,min(ACC) as ACC_2
                    from temp_allresults as bb
                    where bb.QueryID in (select aa.QueryID
                    from temp_test as aa
                    where aa.UniquePriorityNumber IS NULL)
                    group by QueryID)as cc
                    on cc.ACC_2=dd.ACC and cc.QueryID=dd.QueryID)
    ";
   
    $test_result = pg_query($test) or die("test error");
    while($row = pg_fetch_array($test_result)) {
        //foreach($row as $r) {echo $r."-";}
        echo $row[0]."-".$row[1]."-".$row[2]."-".$row[3]."-".$row[4]."-".$row[5]."<br/>";
    }

echo "=================================<br/>";  
*/
$result3 = pg_query($sql3) or die('sql3 error');
echo "results:";
$result3 = pg_query("select * from results") or die('sql3 error');

$end = microtime(true);
$time= $end - $start;
$time = round($time, 2);      // Round to 2 decimal places
echo "<br />--------result3 took:$time seconds.</br>";
//================================================================================================================================================	

/////////////////////////////////////////////////////////////////////
$start = microtime(true);
// Construct output to display
echo "<br />Here are your CellWhere localization predictions:<br />";
$Column_names_array = array('Query ID', 'Uniprot ACC', 'Top priority symbol', 'Top priority localization term', 'Priority score', 'CellWhere localization');
$Column_names_array_all_results = array('Query ID', 'Uniprot ACC', 'Symbol', 'Localization term', 'Priority score', 'CellWhere localization');

$end = microtime(true);
$time= $end - $start;
$time = round($time, 2);      // Round to 2 decimal places
echo "<br />--------output to display took:$time seconds.</br>";

////////////////////////////////////////////////////////////////////
// Code to put results in temporary table file, and allow download
$start = microtime(true);
$result4 = pg_query("SELECT * FROM results") or die();				// Selects all from results table
$str = implode("\t", $Column_names_array)."\n";							// Loop through and make a string
while($row = pg_fetch_assoc($result4)) {
    $str .= implode("\t", $row)."\n";
}
$_SESSION['results'] = $str;

$end = microtime(true);
$time= $end - $start;
$time = round($time, 2);      // Round to 2 decimal places
echo "<br />--------result4 took:$time seconds.</br>";

// Create a shared session variable storing results variable
$start = microtime(true);
echo '<br /><a href="download.php?' . SID . '">Download this table (tab-delimited)</a>';	// Link to a new page that will show results
echo ' or ';
$result10 = pg_query("SELECT * FROM allresults") or die();			// Selects all from allresults table (not selecting max priority number)
$str_allresults = implode("\t", $Column_names_array_all_results)."\n";				// Loop through and make a string
while($row = pg_fetch_assoc($result10)) {
    $str_allresults .= implode("\t", $row)."\n";
}
$_SESSION['allresults'] = $str_allresults;							// Create a shared session variable storing allresults variable
echo '<a href="download_all.php?' . SID . '">Download all results (unfiltered by priority score)</a>';	// Link to a new page that will show all results
$end = microtime(true);
$time= $end - $start;
$time = round($time, 2);      // Round to 2 decimal places
echo "<br />--------result10 took:$time seconds.</br>";


/////////////////////////////////////////////////////////////////////

$start = microtime(true);
// Code to display results in sortable table
$result5 = pg_query("SELECT * FROM results") or die("results5 error");		// Selects all from results table
//echo "<table id=\"sortedtable\" border=\"1\" align=\"left\" class=\"tablesorter\" cellspacing=\"2\">\n";
echo "<table id=\"sortedtable\" class=\"tablesorter\">\n";
echo "<thead>\n<tr>";
foreach ($Column_names_array as $name) {
     echo "<th>$name</th>";
}
echo "</tr>\n</thead>\n";
echo "<tbody>";
if (($Source_Loc_Term == "UniprotOnly") || ($Source_Loc_Term == "UniprotAndGO")) {	// Needed because 'Symbol' becomes 'UniprotID in MySQL UNION above
     while($row = pg_fetch_array($result5)) {
	  echo "<tr><td>$row[0]</td><td><a target=\"_blank\" href=\"http://www.uniprot.org/uniprot/$row[1]\">$row[1]</a></td><td>$row[2]</td><td>$row[3]</td><td>$row[4]</td><td>$row[5]</td></tr>\n";
//	  echo "<tr><td>$row[QueryID]</td><td><a target=\"_blank\" href=\"http://www.uniprot.org/uniprot/$row[ACC]\">$row[ACC]</a></td><td>$row[UniprotID]</td><td>$row[Localization]</td><td>$row[UniquePriorityNumber]</td><td>$row[OurLocalization]</td></tr>\n";
     }
} elseif ($Source_Loc_Term == "GOonly") {						// Using 'Symbol' instead of Uniprot ID where there was no UNION
	  while($row = pg_fetch_array($result5)) {
	  echo "<tr><td>$row[0]</td><td><a target=\"_blank\" href=\"http://www.uniprot.org/uniprot/$row[1]\">$row[1]</a></td><td>$row[2]</td><td>$row[3]</td><td>$row[4]</td><td>$row[5]</td></tr>\n";
//	  echo "<tr><td>$row[QueryID]</td><td><a target=\"_blank\" href=\"http://www.uniprot.org/uniprot/$row[ACC]\">$row[ACC]</a></td><td>$row[Symbol]</td><td>$row[Localization]</td><td>$row[UniquePriorityNumber]</td><td>$row[OurLocalization]</td></tr>\n";
     } 
}
echo "</tbody></table><br />\n";
$end = microtime(true);
$time= $end - $start;
$time = round($time, 2);      // Round to 2 decimal places
echo "<br />--------result5 took:$time seconds.</br>";


///////////////Lu//////////////////////
// Code to put results in temporary table file, and allow download
$start = microtime(true);
//ACC and query
$result6= pg_query("SELECT QueryID,ACC FROM results") or die("result6 error");		// Selects all from results table ,OurLocalization 
$QueryID = array();									// Loop through and make a string
while($row = pg_fetch_array($result6)) {
    $QueryID_ACC[$row[0]] = $row[1];
}
$end = microtime(true);
$time= $end - $start;
$time = round($time, 2);      // Round to 2 decimal places
echo "<br />--------result6 took:$time seconds.</br>";

$start = microtime(true);
//localization
$result7= pg_query("SELECT OurLocalization FROM results") or die("result7 error");	// Selects OurLocalization from results table 
$OurLocalization = array();								// Loop through and make a string
while($row = pg_fetch_array($result7)) {
    $OurLocalization[]= $row[0];
}
$end = microtime(true);
$time= $end - $start;
$time = round($time, 2);      // Round to 2 decimal places
echo "<br />--------result7 took:$time seconds.</br>";

///////////////Lu End//////////////////////

?>