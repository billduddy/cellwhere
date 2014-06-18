<?php
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
                $URLquery = "http://www.uniprot.org/uniprot/?query=(gene_exact:" . $value . ")+and+(organism:9606+OR+organism:10090)+and+reviewed:yes&columns=id&format=tab";
            } elseif ($HumMouseFlag == "HumanOnly") {
                $URLquery = "http://www.uniprot.org/uniprot/?query=(gene_exact:" . $value . ")+and+(organism:9606)+and+reviewed:yes&columns=id&format=tab";
            } elseif ($HumMouseFlag == "MouseOnly") {
                $URLquery = "http://www.uniprot.org/uniprot/?query=(gene_exact:" . $value . ")+and+(organism:10090)+and+reviewed:yes&columns=id&format=tab";
            }
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

file_put_contents("ACCfromUniprot.tsv", $ACCfromUniprot);
// echo $ACCfromUniprot;

//rename("QuickGO_tmp.tsv", "uploads/QuickGO_tmp.tsv");  //This can be useful if wanting to move the file

// Code to read the data out
//$fh = fopen("QuickGO_tmp.tsv", 'r') or die("Can't open file");
//$theData = fread($fh, filesize("QuickGO_tmp.tsv"));
//echo $theData;
//fclose($fh);

?>