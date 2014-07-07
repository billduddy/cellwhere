<?php
global $xml_file_name;
session_start();

$file = 'XGMML_example.xml';

if (file_exists($file)) {   
    header("Content-type: application/zip"); 
    header("Content-Disposition: attachment; filename=$file");
    header("Content-length: " . filesize($file));
    header("Pragma: no-cache"); 
    header("Expires: 0"); 
    readfile("$file");
    ob_clean();
    flush();
    readfile($file);
    exit;
}

?>
