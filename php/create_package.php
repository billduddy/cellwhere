<?php
/*download_web_network.php*/
// network html + background
    if(file_exists($xml_file_name."_web.html")){
        $file=$xml_file_name.'_web.zip';
        $zip = new ZipArchive(); 
        if($zip->open($file, ZipArchive::CREATE) === true){
            $zip->addFile($xml_file_name.'_web.html');
            $zip->addFile($xml_file_name.'_background.png');
            $zip->close();
        } else {
            echo 'html file compress failed';
        }
    }else{echo "no found ".getcwd().$xml_file_name."_web.html<br/>";}
    
    
    // network xml + background
    if(file_exists($xml_file_name."_cy3.xml")){
        $file=$xml_file_name.'_cy3.zip';
        $zip = new ZipArchive(); 
        if($zip->open($file, ZipArchive::CREATE) === true){
            $zip->addFile($xml_file_name.'_cy3.xml');
            $zip->addFile($xml_file_name.'_background.png');
            $zip->close();
        } else {
            echo 'cy3 xml file compress failed';
        }
    }else{echo "no found ".getcwd().$xml_file_name."_cy3.xml<br/>";}
?>