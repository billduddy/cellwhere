<?php
    /*"add_location_xml.php"*/

    function add_location_to_xml($QueryID,$att_ID_type,$xml_file_name,$OurLocalization,$show_name,$QueryID_Symbol,$QueryID_ACC){
        if (@simplexml_load_file($xml_file_name)){
            $xml=simplexml_load_file($xml_file_name);
        }else{
            echo "Can't open the xml file!";
        }
        
        for($i=0;$i<count($QueryID);$i++){
            $ID=$QueryID[$i];
            //echo $ID." in ".$OurLocalization[$i]."<br/>";
            foreach($xml->node as $nodes){
                foreach($nodes->att as $atts){
                    if($atts["name"]==$att_ID_type && $atts["value"]==$ID ){
                        $att=$nodes->addChild('att');
                        $att->addAttribute('name', 'CellWhere localization');
                        $att->addAttribute('value',$OurLocalization[$i]);
                        $att->addAttribute('type','string');
                        $attr=$nodes->addChild("att");
                        $attr->addAttribute("name","CellWhereUniprotID");
                        $attr->addAttribute("value",(string)$QueryID_ACC[$ID]);
                        $attr->addAttribute("type","string");
                        if($show_name!=""){
                            if($show_name=="Gene_name"){
                                $nodes["label"]=(string)$QueryID_Symbol[$ID];
                            }elseif($show_name=="UniprotACC"){
                                $nodes["label"]=(string)$QueryID_ACC[$ID];
                            }
                        }
                    }
                    if($show_name!=""&&$atts["name"]==$show_name){
                        $nodes["label"]=$atts["value"];
                    }
                }
            }

        }
        return $xml;
    }
?>