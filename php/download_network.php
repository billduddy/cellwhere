<?php
/*download_network.php*/
// network html + background
    global $xml_file_name;
    $zip = new ZipArchive(); 
    if($zip->open($xml_file_name.'.zip', ZipArchive::CREATE) === true){
        $zip->addFile($xml_file_name.'_out.html');
        $zip->addFile($xml_file_name.'_background.png');
        $zip->close();
        echo 'ok';
    } else {
        echo 'compress failed';
    }


?>