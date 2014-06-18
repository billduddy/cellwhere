<?php
global $xml_file_name;
session_start();

$archive_file_name = $xml_file_name.'_html.zip';

if (file_exists($archive_file_name)) {
    
    header("Content-type: application/zip"); 
    header("Content-Disposition: attachment; filename=$archive_file_name");
    header("Content-length: " . filesize($archive_file_name));
    header("Pragma: no-cache"); 
    header("Expires: 0"); 
    readfile("$archive_file_name");
    ob_clean();
    flush();
    readfile($archive_file_name);
    exit;
}

?>
