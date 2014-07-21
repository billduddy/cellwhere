<?php
    /*"add_location_xml.php"*/

    function add_location_to_xml_mentha($QueryID,$xml_file_name,$OurLocalization){
//        echo "add_location_to_xml_mentha!!";
        if (@simplexml_load_file($xml_file_name)){
            $xml=simplexml_load_file($xml_file_name);
        }else{
            echo "Can't open the xml file!";
        }
        
        for($i=0;$i<count($QueryID);$i++){
            $ID=$QueryID[$i];
//            echo $ID;
            foreach($xml->node as $nodes){
//                echo "-".$nodes["label"];
                    if($nodes["label"]==$ID ){
                        $att=$nodes->addChild('att');
                        $att->addAttribute('name', 'CellWhere localization');
                        $att->addAttribute('value',$OurLocalization[$i]);
//                        echo $OurLocalization[$i]."<br\>";
                        $att->addAttribute('type','string');
                    }
                
            }
        }
        return $xml;
    }
?>