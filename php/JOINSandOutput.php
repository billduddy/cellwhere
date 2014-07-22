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

$sql = "DROP TEMPORARY TABLE IF EXISTS results CASCADE";
$result = mysql_query($sql) or die(mysql_error());
$sql = "DROP TEMPORARY TABLE IF EXISTS allresults CASCADE";
$result = mysql_query($sql) or die(mysql_error());


$start = microtime(true);
	
switch ($Source_Loc_Term) {                 // Depending on selection on dropdown menu
     case "GOonly":
	  // To map using only the Gene Ontology (works 240214)
	  $sql3 = "
		    CREATE TEMPORARY TABLE results AS SELECT b.QueryID, b.ACC, b.Symbol, b.Localization, MAX(b.UniquePriorityNumber) AS UniquePriorityNumber, COALESCE(NULLIF(b.OurLocalization, ''), 'Unknown') AS OurLocalization
		    FROM (
			    SELECT a.QueryID, a.ACC, a.Symbol, a.Localization, map_" . "$Flavour" . "_flavour.UniquePriorityNumber, map_" . "$Flavour" . "_flavour.OurLocalization
			    FROM (
				     SELECT y.QueryID, y.ACC, quickgotmp.Symbol, quickgotmp.Localization
				     FROM (
					   SELECT query_ids.QueryID, listofids.ACC
					   FROM query_ids
						LEFT JOIN listofids ON listofids.QueryID = query_ids.QueryID
				     ) AS y
					     LEFT JOIN quickgotmp ON quickgotmp.ID = y.ACC
			    ) AS a
				    LEFT JOIN map_" . "$Flavour" . "_flavour
					    ON map_" . "$Flavour" . "_flavour.GO_id_or_uniprot_term = a.Localization
			    ORDER BY map_" . "$Flavour" . "_flavour.UniquePriorityNumber DESC
		    ) AS b
		    GROUP BY b.QueryID
		    ORDER BY b.UniquePriorityNumber DESC
		 ";
	  break;
     case "UniprotAndGO":  
	  // To map using both Uniprot and the Gene Ontology (works 240214)
	  $sql3 = "
		    CREATE TEMPORARY TABLE results AS SELECT b.QueryID, b.ACC, b.UniprotID, b.Localization, MAX(b.UniquePriorityNumber) AS UniquePriorityNumber, COALESCE(NULLIF(b.OurLocalization, ''), 'Unknown') AS OurLocalization
		    FROM (
			    SELECT a.QueryID, a.ACC, a.UniprotID, a.Localization, map_" . "$Flavour" . "_flavour.UniquePriorityNumber, map_" . "$Flavour" . "_flavour.OurLocalization
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
			    ORDER BY map_" . "$Flavour" . "_flavour.UniquePriorityNumber DESC
		    ) AS b
		    GROUP BY b.QueryID
		    ORDER BY b.UniquePriorityNumber DESC
		 ";
	  break;
     case "UniprotOnly":  		 
	  // To map using only Uniprot (works 240214)
	  $sql3 = "
		  CREATE TEMPORARY TABLE results AS SELECT b.QueryID, b.ACC, b.UniprotID, b.Localization, MAX(b.UniquePriorityNumber) AS UniquePriorityNumber, COALESCE(NULLIF(b.OurLocalization, ''), 'Unknown') AS OurLocalization
		  FROM (
			  SELECT a.QueryID, a.ACC, a.UniprotID, a.Localization, map_" . "$Flavour" . "_flavour.UniquePriorityNumber, map_" . "$Flavour" . "_flavour.OurLocalization
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
			  ORDER BY map_" . "$Flavour" . "_flavour.UniquePriorityNumber DESC
		  ) AS b
		  GROUP BY b.QueryID
		  ORDER BY b.UniquePriorityNumber DESC
		 ";
	  break;
}
$result3 = mysql_query($sql3) or die(mysql_error());				// Puts results of joins into a new temporary table called results

$end = microtime(true);
$time= $end - $start;
$time = round($time, 2);      // Round to 2 decimal places
echo "<br />--------result3 took:$time seconds.</br>";
	
//////////////////////////////////////////////////////////////////
// Code to download all results, not just max priority number
// It is important to use DISTINCT here, especially for GOonly, as the QuickGO_tmp.tsv has many duplicates (UNION probably makes DISTINCT redundant in the UniprotAndGO query)

$start = microtime(true);

switch ($Source_Loc_Term) {                 // Depending on selection on dropdown menu
     case "GOonly":
	  // To map using only the Gene Ontology (works 240214)
	  $sql = "
		    CREATE TEMPORARY TABLE allresults AS SELECT DISTINCT a.QueryID, a.ACC, a.Symbol, a.Localization, map_" . "$Flavour" . "_flavour.UniquePriorityNumber, COALESCE(NULLIF(map_" . "$Flavour" . "_flavour.OurLocalization, ''), 'Unknown') AS OurLocalization
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
		    ORDER BY a.QueryID ASC
		 ";
	  break;
     case "UniprotAndGO":  
	  // To map using both Uniprot and the Gene Ontology (works 240214)
	  $sql = "
		    CREATE TEMPORARY TABLE allresults AS SELECT DISTINCT a.QueryID, a.ACC, a.UniprotID, a.Localization, map_" . "$Flavour" . "_flavour.UniquePriorityNumber, COALESCE(NULLIF(map_" . "$Flavour" . "_flavour.OurLocalization, ''), 'Unknown') AS OurLocalization
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
		    ORDER BY a.QueryID ASC
		 ";
	  break;
     case "UniprotOnly":  		 
	  // To map using only Uniprot (works 240214)
	  $sql = "
		    CREATE TEMPORARY TABLE allresults AS SELECT DISTINCT a.QueryID, a.ACC, a.UniprotID, a.Localization, map_" . "$Flavour" . "_flavour.UniquePriorityNumber, COALESCE(NULLIF(map_" . "$Flavour" . "_flavour.OurLocalization, ''), 'Unknown') AS OurLocalization
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
		    ORDER BY a.QueryID ASC
		 ";
	  break;
}		  
$result = mysql_query($sql) or die(mysql_error());				// Puts results of joins into a new temporary table called results
$end = microtime(true);
$time= $end - $start;
$time = round($time, 2);      // Round to 2 decimal places
echo "<br />--------result took:$time seconds.</br>";

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
$result4 = mysql_query("SELECT * FROM results") or die(mysql_error());				// Selects all from results table
$str = implode("\t", $Column_names_array)."\n";							// Loop through and make a string
while($row = mysql_fetch_assoc($result4)) {
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
$result10 = mysql_query("SELECT * FROM allresults") or die(mysql_error());			// Selects all from allresults table (not selecting max priority number)
$str_allresults = implode("\t", $Column_names_array_all_results)."\n";				// Loop through and make a string
while($row = mysql_fetch_assoc($result10)) {
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
$result5 = mysql_query("SELECT * FROM results") or die(mysql_error());		// Selects all from results table
//echo "<table id=\"sortedtable\" border=\"1\" align=\"left\" class=\"tablesorter\" cellspacing=\"2\">\n";
echo "<table id=\"sortedtable\" class=\"tablesorter\">\n";
echo "<thead>\n<tr>";
foreach ($Column_names_array as $name) {
     echo "<th>$name</th>";
}
echo "</tr>\n</thead>\n";
echo "<tbody>";
if (($Source_Loc_Term == "UniprotOnly") || ($Source_Loc_Term == "UniprotAndGO")) {	// Needed because 'Symbol' becomes 'UniprotID in MySQL UNION above
     while($row = mysql_fetch_array($result5)) {
	  echo "<tr><td>$row[QueryID]</td><td><a target=\"_blank\" href=\"http://www.uniprot.org/uniprot/$row[ACC]\">$row[ACC]</a></td><td>$row[UniprotID]</td><td>$row[Localization]</td><td>$row[UniquePriorityNumber]</td><td>$row[OurLocalization]</td></tr>\n";
     }
} elseif ($Source_Loc_Term == "GOonly") {						// Using 'Symbol' instead of Uniprot ID where there was no UNION
	  while($row = mysql_fetch_array($result5)) {
	  echo "<tr><td>$row[QueryID]</td><td><a target=\"_blank\" href=\"http://www.uniprot.org/uniprot/$row[ACC]\">$row[ACC]</a></td><td>$row[Symbol]</td><td>$row[Localization]</td><td>$row[UniquePriorityNumber]</td><td>$row[OurLocalization]</td></tr>\n";
     } 
}
echo "</tbody></table><br />\n";
$end = microtime(true);
$time= $end - $start;
$time = round($time, 2);      // Round to 2 decimal places
echo "<br />--------result5 took:$time seconds.</br>";


///////////////Lu//////////////////////
// Code to put results in temporary table file, and allow download
/*
$result6= mysql_query("SELECT QueryID FROM results") or die(mysql_error());		// Selects all from results table ,OurLocalization 
$QueryID = array();									// Loop through and make a string
while($row = mysql_fetch_array($result6)) {
    $QueryID[] = $row[0];
}

$result6= mysql_query("SELECT ACC FROM results") or die(mysql_error());		// Selects all from results table ,OurLocalization 
$ACCs = array();								// Loop through and make a string
while($row = mysql_fetch_array($result6)) {
    $ACCs[] = $row[0];
}
*/
$start = microtime(true);
//ACC and query
$result6= mysql_query("SELECT QueryID,ACC FROM results") or die(mysql_error());		// Selects all from results table ,OurLocalization 
$QueryID = array();									// Loop through and make a string
while($row = mysql_fetch_array($result6)) {
    $QueryID_ACC[$row[0]] = $row[1];
}
$end = microtime(true);
$time= $end - $start;
$time = round($time, 2);      // Round to 2 decimal places
echo "<br />--------result6 took:$time seconds.</br>";

$start = microtime(true);
//localization
$result7= mysql_query("SELECT OurLocalization FROM results") or die(mysql_error());	// Selects OurLocalization from results table 
$OurLocalization = array();								// Loop through and make a string
while($row = mysql_fetch_array($result7)) {
    $OurLocalization[]= $row[0];
}
$end = microtime(true);
$time= $end - $start;
$time = round($time, 2);      // Round to 2 decimal places
echo "<br />--------result7 took:$time seconds.</br>";

///////////////Lu End//////////////////////


?>