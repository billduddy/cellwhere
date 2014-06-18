<?php
    /*"add_location_xml.php"*/

    function add_location_to_xml($QueryID,$att_ID_type,$xml_file_name,$OurLocalization){
        if (@simplexml_load_file($xml_file_name)){
            $xml=simplexml_load_file($xml_file_name);
        }else{
            echo "Can't open the xml file!";
        }
        
        for($i=0;$i<count($QueryID);$i++){
            $ID=$QueryID[$i];
            foreach($xml->node as $nodes){
                foreach($nodes->att as $atts){
                    if($atts["name"]==$att_ID_type && $atts["value"]==$ID ){
                        $att=$nodes->addChild('att');
                        $att->addAttribute('name', 'CellWhere localization');
                        $att->addAttribute('value',$OurLocalization[$i]);
                        $att->addAttribute('type','string');
                    }
                }
            }
        }
        return $xml;
    }
?>