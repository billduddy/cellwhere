<?php
    function ACCtoGS($prot_all){
        $start = microtime(true);
        foreach ( $prot_all as $ACC ) {
            $GN=$ACC;
            if(@file_get_contents("http://www.uniprot.org/uniprot/".$ACC.".fasta")){
                $txt=file_get_contents("http://www.uniprot.org/uniprot/".$ACC.".fasta");
                $pattern = "/GN=[a-zA-Z0-9._-]+ /";     
                if(preg_match($pattern, $txt)){
                    preg_match($pattern, $txt, $matches);
                    $GN=str_replace("GN=","",$matches[0]);
                }
                $ACC_GN[$ACC]=$GN;
            }
        }
        $end = microtime(true);
        $ACC_GN_Duration = $end - $start;
        $ACC_GN_Duration = round($ACC_GN_Duration, 2);      // Round to 2 decimal places
        echo "<br />ACC to Gene Symbol took:$ACC_GN_Duration seconds for ".count($prot_all)." genes. </br>";
        return $ACC_GN;
    }
?>