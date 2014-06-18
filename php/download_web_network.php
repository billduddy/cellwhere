<?php
/*download_web_network.php*/
// network html + background
    global $xml_file_name;
    $file=$xml_file_name.'.zip';
    $zip = new ZipArchive(); 
    if($zip->open($file, ZipArchive::CREATE) === true){
        $zip->addFile($xml_file_name.'_out.html');
        $zip->addFile($xml_file_name.'_background.png');
        $zip->close();
    } else {
        echo 'File compress failed';
    }
    echo '<a href="'.$xml_file_name.'.zip"></a> ';
?>