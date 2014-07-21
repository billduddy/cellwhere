<?php
/*
 *CellWhere Project
 *Description:
    This script is capable to
        - analyze the localization and their relationship in spatial position in cell by reading a localization table.
        - read an original xml file in order to put out a relocalized xml file with different compounds.
        - the out xml file can be visualizd by Cytoscape js.

    Author : ZHU Lu , Master 2 Bioinformatics, Universite Paris Diderot
    Contact: zhu.lu@hotmail.com
*/
 
/*--------------------------------------------------------Main-------------------------------------------------------*/
    //time
      $start = microtime(true);


    /*-read the original xml file ------------------------------------------------*/
   /* $file_xml = $argv[1];
    $xml=simplexml_load_file($file_xml.".xml");*/
    $file_xml=$xml_file_name;
    
    /*-analyze the relation table file--------------------------------------------*/
    list($loca_table,$list_comp) = localizations($xml);

    /*----------------------------------------------------------------------------*/  
    //total compound number in relation table
    $compd_num=total_compd($loca_table);
    //echo "* There are ".$compd_num." compounds in total. \n";
    
    
    // total node number
    $node_num=total_node($xml);
    //echo "* There are ".$node_num." nodes in total.\n";
    /*----------------------------------------------------------------------------*/

    //global variables
    $base=array( 'Extracellular','Membrane','Cytoplasm','Nucleus membrane','Nucleus');
    $colors=array("compound"=>"#FAAC58","membrane"=>"#FF8000","transparent"=>"#FFFFFF","gray"=>"#A4A4A4");
   // $state_memb=array("Nucleus"=>"Nucleus membrane","Sarcoplasmic reticulum"=>"Sarcoplasmic reticulum membrane","Mitochondrion"=>"Mitochondrial membrane","Vacuole"=>"Vacuole membrane","Lysosome"=>"Lysosomal membrane","Vesicle"=>"Vesicle membrane","Golgi"=>"Golgi membrane","Endosome"=>"Endosomal membrane","Endoplasmic reticulum"=>"Endoplasmic reticulum membrane","Peroxisome"=>"Peroxisomal membrane");
    /*----------------------------------------------------------------------------*/
    //unlink($xml_file_name);
    /*$file=explode("/",$file_xml)[1];
    $path="downloads/".basename($file,".xml");
    if(!is_dir($path)){
        mkdir($path);
    }
    $file_xml=$path."/".$file;
    $xml_file_name=$file_xml;
    */
    layout($xml,$loca_table,$list_comp);
   //cytoscape js
    include("xml_to_json_with_compd.php");
    xml_to_json($file_xml,"_web.xml",$list_comp,$ACCs_1,$ACC_GN);

    unlink($file_xml."_web.xml");
    if(file_exists($file_xml."_cy.xml")){echo $file_xml."_cy.xml"; }
    
    
    $end = microtime(true);
    $vis_Duration = $end - $start;
    $vis_Duration = round($vis_Duration, 2);      // Round to 2 decimal places
    echo "<br />Visualization took:$vis_Duration seconds.</br>";
    
   /*----------------------------------------------------End main----------------------------------------------------------*/ 
   
   
   
/*-------------------------------------functions-----------------------------------*/

    /*---------------------------------location analysis--------------------------*/ 
    function add_location(&$ExtraC,&$Membr,&$Cytopl,&$NucM,&$Nucle,$my,$rele){ 
        /*add new location in the location table by the spatial relation*/
        switch($rele){
            case "UNDER Membrane":
                //echo "here UNDER Membrane";
               // $NucM[$my] = 0;
                $Cytopl[$my] = 0;
                break;
            case "ACROSS Membrane":
                $Membr[$my] = 0;
                break;
            case "IN Cytoplasm":
                $NucM[$my] = 0;
                break;
            case "IN Extracellular":
                $Membr[$my] = 0;
                break;
            case "SURFACE Membrane"://difference from Cell surface
                $Membr[$my] = 0;
                break;
            case "IN Membrane":
                $Cytopl[$my] = 0;
                break;
        }
    }

    /*------------------------------------------------------*/
    function show_location($head){
        foreach($head as $elem=>$next){
            echo $elem."\n";
            if($next!=0){            
                show_location($next);
            }          
            echo "END_".$elem."\n";
        }
    }
    
    /*------------------------------------------------------*/
    function localizations($fxml){
        // creat a 2 dimension table to represent the level of construction of the compounds
        $Nucle["Nucleus"]=0;
        $NucM["Nucleus membrane"]=$Nucle;
        $Cytopl["Cytoplasm"]=$NucM;
        $Membr["Membrane"]=$Cytopl;
        $ExtraC["Extracellular"]=$Membr;               
        $membre_elem=array("Nucleus"=>array('','Nucleus membrane'));
        $list_comp=array("Nucleus"=>"Nucleus","Nucleus membrane"=>"Nucleus membrane","Cytoplasm"=>"Cytoplasm","Membrane"=>"Membrane","Extracellular"=>"Extracellular");
        //spatial relation array
        $spatial=array("Microtubule cytoskeleton"=>"IN Cytoplasm","Cell cortex"=>"UNDER Membrane","Vesicular exosome"=>"IN Extracellular",
                       "T-tubule"=>"ACROSS Membrane","Myotendinous junction"=>"ACROSS Membrane","Neuromuscular junction"=>"ACROSS Membrane",
                       "Gap Junction"=>"ACROSS Membrane","Cell junction"=>"ACROSS Membrane","Synapse"=>"ACROSS Membrane","Motile parts"=>"ACROSS Membrane",
                       "Cytoplasm"=>"Cytoplasm","Extracellular"=>"Extracellular","Sarcoplasmic reticulum MEMBRANE"=>"IN Cytoplasm","Sarcoplasmic reticulum"=>"IN Cytoplasm",
                       "Sarcomere"=>"IN Cytoplasm","Microtubule cytoskeleton "=>"IN Cytoplasm","Actin cytoskeleton"=>"IN Cytoplasm","Cytoskeleton"=>"IN Cytoplasm",
                       "Mitochondrion MEMBRANE"=>"IN Cytoplasm","Autophagosome"=>"IN Cytoplasm","Vacuole MEMBRANE"=>"IN Cytoplasm","Lysosome MEMBRANE"=>"IN Cytoplasm",
                       "Vesicle MEMBRANE"=>"IN Cytoplasm","Golgi MEMBRANE"=>"IN Cytoplasm","Endosome MEMBRANE"=>"IN Cytoplasm","Endoplasmic reticulum MEMBRANE"=>"IN Cytoplasm",
                       "Peroxisome MEMBRANE"=>"IN Cytoplasm","Proteasome"=>"IN Cytoplasm","Chloroplast"=>"IN Cytoplasm","Viral"=>"IN Cytoplasm",
                       "Extracellular matrix"=>"IN Extracellular","Unknown"=>"IN Extracellular","Membrane (Calcium signalling)"=>"IN Membrane",
                       "Membrane"=>"Membrane","Nucleus"=>"Nucleus","Nucleus membrane"=>"Nucleus membrane","Cell surface"=>"SURFACE Membrane",
                       "Cell wall"=>"SURFACE Membrane","Dystrophin-associated complex"=>"UNDER Membrane","Focal adhesion"=>"UNDER Membrane",
                       "Costamere"=>"UNDER Membrane","Caveolae"=>"UNDER Membrane","Cell cortex"=>"UNDER Membrane","Mitochondrion"=>"IN Cytoplasm",
                       "Vacuole"=>"IN Cytoplasm","Lysosome"=>"IN Cytoplasm","Vesicle"=>"IN Cytoplasm","Golgi"=>"IN Cytoplasm","Endosome"=>"IN Cytoplasm",
                       "Endoplasmic reticulum"=>"IN Cytoplasm","Peroxisome"=>"IN Cytoplasm");
        
        foreach ($fxml->node as $nodes){
            foreach ($nodes->att as $atts ){
                if($atts['name'] =="CellWhere localization"){
                    $myloc = (string)$atts['value'];
                    if(key_exists($myloc,$spatial)){
                        $rele = $spatial[$myloc];
                    }
                    // for the membranes, change the name to location.
                    if(strstr($myloc," MEMBRANE")){
                        $myloc = explode(" MEMBRANE",$myloc)[0];
                        $atts['value']=$myloc;
                    }
                    if(strstr($myloc," membrane")){
                        $myloc = explode(" membrane",$myloc)[0];
                        $atts['value']=$myloc;
                    }
                }                
            }
            $list_comp[$myloc]=$rele;
            //add new location in the location table by the spatial relation
            add_location($ExtraC,$Membr,$Cytopl,$NucM,$Nucle,$myloc,$rele);  
        }
        $Nucle["Nucleus"]=0;
        $NucM["Nucleus membrane"]=$Nucle;
        $Cytopl["Cytoplasm"]=$NucM;
        $Membr["Membrane"]=$Cytopl;
        $ExtraC["Extracellular"]=$Membr;
        //echo "-----------------------------\n";
        //show_location($ExtraC);
        return array($ExtraC,$list_comp,$membre_elem);
    }
    
    /*-------------------------rewrite xgmml file--------------------------------------*/

    function total_node($xml){
        $node_num=0;
        foreach ($xml->node as $nodes){
            $node_num=$node_num+1;
        }
        return $node_num;
    }
   
    /*------------------------------------------------------*/     
    function total_compd($head){
        $t=0;
        foreach($head as $elem=>$next){
            $t=$t+1;
            if($next!=0){            
            $t = $t + total_compd($next);
            }          
        }        
        return $t;
    }
    
    /*--------------------------------------------------*/
    //size and color ~ fold change value
    function getProperColor($var){
        if ($var <= -3){
            return '#0070FF';//blue
        }
        else if ($var > -3 && $var < 0){
            $var=(3+$var)*256/3;
            if($var<=16){
                $var = '0'.dechex($var);
            }else{
                $var = dechex($var);
            }
            return "#".$var."70ff";
        }
        else if ($var == 0){
            return '#A4A4A4';
        }
        else if ($var >= 0 && $var < 3){
            $var=(3-$var)*256/3;
            if($var<=16){
                $var = '0'.dechex($var);
            }else{
                $var = dechex($var);
            }
            return "#ff".$var.$var;
        }
        else if ($var >= 3){
            return '#FF0000';
        }
    }
    
    function getProperSize($var){
        $var=abs($var);
        if ($var == 0){
            return 80;
        }
        else if ($var >= 0 && $var < 3){
            $var=$var*50/3+80;
            return round($var);
        }
        else if ($var >= 3){
            return 150;
        }
    }
   
    /*------------------------------------------------------*/
    //analyze the position of nodes in one location
    //record the max min and average coordiante values of the nodes in the range_xy is
    function node_position($xml,$head,&$range_xy){
        global $base;
        foreach($head as $elem=>$next){
            if($next!=0){
                node_position($xml,$next,$range_xy);
            }                
             xy_nodes($xml,$elem,$range_xy);
        }
    }
    
    function xy_nodes($fxml,$flabel,&$range_xy){
        $r_xy=null;
        $cout=0;
        foreach ($fxml->node as $nodes){
            foreach ($nodes->att as $atts){
                if($atts['name']=="CellWhere localization" && $atts['value']==$flabel){
                    $cout++;
                    foreach($nodes->graphics as $grap){
                        if($r_xy==null){
                            $r_xy=array(floatval($grap['x']),floatval($grap['x']),floatval($grap['y']),floatval($grap['y']),0,0);
                        }           
                        $r_xy[0]=min($r_xy[0],floatval($grap['x']));
                        $r_xy[1]=max($r_xy[1],floatval($grap['x']));
                      
                        $r_xy[2]=min($r_xy[2],floatval($grap['y']));
                        $r_xy[3]=max($r_xy[3],floatval($grap['y']));
                        
                        $r_xy[4]=$r_xy[4]+floatval($grap['x']);
                        $r_xy[5]=$r_xy[5]+floatval($grap['y']);
                        
                        //delete the useless graphic attributes
                        $tail=count($grap);
                        for($i=0;$i<$tail;$i++){
                            $at = $grap->att[0];
                            $dom=dom_import_simplexml($at);
                            $dom->parentNode->removeChild($dom); 
                        }                    
                    } 
                }
            }
        }
        if($cout){
            $r_xy[4]=$r_xy[4]/$cout;
            $r_xy[5]=$r_xy[5]/$cout;
        }
        $range_xy[$flabel]=$r_xy;
    }
    /*------------------------------------------------------*/   

    function node_number($fxml,$flabel){
        $n=0;
        foreach ($fxml->node as $nodes){
            foreach ($nodes->att as $atts){
                if($atts['name']=="CellWhere localization" && $atts['value']==$flabel){
                    $n++;
                }
            }
        }
        return $n;
    }
    
    /*---------------------------------------------------------xml_modify_1------------------------------*/
        /*------------------------------------------------------*/
    function Membrane($fxml,$flabel,&$range_xy,&$control_xy){
        global $colors, $att_FC;
        $size = 80;
        $col=$colors["gray"];
        $diff=array( 0,0);
        $fc=0;
        foreach ($fxml->node as $nodes){
            $diff[0] = $control_xy["memb_xy"][0];
            if($control_xy["under_xy"][0]==null){
                $diff[1] =$range_xy["Nucleus"][2]-600+rand(-10,10);
            }else{
                $diff[1] =$control_xy["under_xy"][3]-200+rand(-10,10);
            } 
            foreach ($nodes->att as $atts){
                if($atts['name']=="gene name" ||$atts['name']=="Gene Name"){
                    $nodes['label']=$atts['value'];    
                }
                    if($att_FC!="NoFC" && $atts['name']==$att_FC ){
                        $fc=(float)$atts['value'];
                        $size = getProperSize($fc);
                        $col=getProperColor($fc);
                    }
            }   
            foreach ($nodes->att as $atts){
                if($atts['name']=="CellWhere localization" && $atts['value']==$flabel){
                    //delete the graphics attributes for each node
                    foreach($nodes->graphics as $grap){
                    // $xy=x_y_creater($flabel,$limit_xy,$coordinates);
                        $grap['x']=$grap['x']+$diff[0];
                        $grap['y']=$diff[1];
                        $grap->addAttribute("cy:nodeLabelFont", "Arial-0-40");
                        $grap->addAttribute("cy:nodeTransparency", "1");
                        $grap["outline"]=$col;
                        $grap["fill"] = $col;
                        $grap["h"] = $size;
                        $grap["w"] = $size;
                        //delete the graphics                             
                        $tail=count($grap);
                        if($tail>1){
                        for($i=0;$i<$tail;$i++){
                            $at = $grap->att[0];
                            $dom=dom_import_simplexml($at);
                            $dom->parentNode->removeChild($dom); 
                        }
                        }
                    }
                //write the correspondent node to the output file
                $control_xy["memb_xy"][0] = $grap['x']+300;
                }
            }
        }

    }

    /*add invisible nodes in order to control the compound node size*/
    function xml_modify_1(&$xml,$head,&$range_xy,$list_comp){
        global $base,$colors;
        foreach($head as $elem=>$next){
            if($next!=0){
                xml_modify_1($xml,$next,$range_xy,$list_comp);
            }
            if($elem!="Membrane" && $list_comp[$elem]!="IN Membrane"){ // as we put membrane in a line, so we don't need add invisible node for size control
                change_node_1($xml,$elem,$range_xy);
            }
        }
        return $xml;
    }
    
    function change_node_1(&$fxml,$flabel,&$range_xy){
        global $colors, $att_FC;
        $fc =0;
        $j=0; //pointer the position array
        $max_size=0;
        $points=array();
        $n=node_number($fxml,$flabel);
        $size = 80; //node size
        $col=$colors["gray"]; // default  color for no "change" node
        if($n>0){
            $points = x_y_creater_circle($range_xy[$flabel][4],$range_xy[$flabel][5],50*$n,$n);                        
            foreach ($fxml->node as $nodes){            
                foreach ($nodes->att as $atts){
                    if($atts['name']=="gene name"||$atts['name']=="Gene Name"){
                        $nodes['label']=$atts['value'];    
                    }
                    //if($atts['name']=="Fold-Change(HMZ * 80 vs. WT * 80)" ){
                    //if($atts['name']=="Fold change" ){
                    if($att_FC!="NoFC" && $atts['name']==$att_FC ){
                        $fc=(float)$atts['value'];
                        $size = getProperSize($fc);
                        $col=getProperColor($fc);
                    }
                }
                foreach ($nodes->att as $atts){
                    if($atts['name']=="CellWhere localization" && $atts['value']==$flabel){               
                        foreach($nodes->graphics as $grap){
                            $grap['x']=$points[$j][0];
                            $grap['y']=$points[$j][1];
                            $grap->addAttribute("cy:nodeLabelFont", "Arial-0-40");
                            $grap->addAttribute("cy:nodeTransparency", "1");
                            $grap["outline"]=$col;
                            $grap["fill"] = $col;
                            //$grap["width"] = 10;
                            $grap["h"] = $size;
                            $grap["w"] = $size;
                            $max_size = max( $max_size,$size);
                            //delete the graphics                             
                            $tail=count($grap);
                            if($tail>1){
                            for($i=0;$i<$tail;$i++){
                                $at = $grap->att[0];
                                $dom=dom_import_simplexml($at);
                                $dom->parentNode->removeChild($dom);                     
                            }
                            }
                        }
                        $j++;
                    }
                }
            }
            $range_xy[$flabel] = max_min($points);
            if($n==1){
                $ps = x_y_creater_rectangle($points[0][0],$points[0][1],$size+20,4);
                add_4_nodes($fxml,$ps,$flabel);
                $range_xy[$flabel] = max_min($ps);
            }elseif($n<4&&$n>1){
                $ps = x_y_creater_rectangle($range_xy[$flabel][4],$range_xy[$flabel][5],(50*$n+50),4);
                add_4_nodes($fxml,$ps,$flabel);
                $range_xy[$flabel] = max_min($ps);
            }
        //}elseif(!(strstr($flabel," membrane")||strstr($flabel," MEMBRANE"))){
        }elseif($n==0&&in_array($flabel,array("Nucleus","Cytoplasm","Membrane","Extracellular"))){        
            $ps = x_y_creater_circle(0,0,40,4);
            add_4_nodes($fxml,$ps,$flabel);
            $range_xy[$flabel] = max_min($ps);
        }
    }
    
    /*--Distribute a number of node in circle position or in rectangle position--*/
    //circle
   function x_y_creater_circle($center_x,$center_y,$radius,$n){
        $alpha = M_PI * 2 / $n;
        $points = array();
        
        $i = -1;
        while( ++$i < $n ){
            $theta= $alpha * ($i+0.3);
            $pointOnCircle= array(round((cos( $theta )* $radius +$center_x),2), round((sin( $theta ) * $radius +$center_y),2));
            $points[ $i ] = $pointOnCircle;
        }
        return $points;
    
    }

    //rectangle
    function x_y_creater_rectangle($center_x,$center_y,$radius,$n){
        $alpha = M_PI * 2 / $n;
        $points = array();
        
        $i = -1;
        while( ++$i < $n ){
            $theta= $alpha * $i;
            
            if($theta==0||$theta==M_PI * 2){$pointOnCircle =array($radius,0);}
            if($theta==M_PI/2){$pointOnCircle =array(0,$radius);}
            if($theta==M_PI){$pointOnCircle =array(-$radius,0);}
            if($theta==3*M_PI/2){$pointOnCircle =array(0,-$radius);}
            
            if($theta>=0&&$theta<M_PI/4){$pointOnCircle= array($radius +$center_x,   tan( $theta ) * $radius +$center_y); }
            if($theta>=M_PI/4&&$theta<M_PI/2){$pointOnCircle= array($radius/tan( $theta ) +$center_x, $radius + $center_y); }
            
            if($theta>=M_PI/2&&$theta<3*M_PI/4){$theta =M_PI- $theta; $pointOnCircle= array(-$radius/tan( $theta ) +$center_x, $radius + $center_y); }
            if($theta>=3*M_PI/4&&$theta<M_PI){$theta =M_PI- $theta; $pointOnCircle= array(-$radius +$center_x,   tan( $theta ) * $radius +$center_y); }
            
            if($theta>=M_PI&&$theta<5*M_PI/4){$theta =$theta-M_PI; $pointOnCircle= array(-$radius +$center_x,   -(tan( $theta ) * $radius) +$center_y);  }
            if($theta>=5*M_PI/4&&$theta<3*M_PI/2){$theta =$theta-M_PI; $pointOnCircle= array(-$radius/tan( $theta ) +$center_x, -$radius + $center_y);  }
            
            if($theta>=3*M_PI/2&&$theta<7*M_PI/4){$theta =2*M_PI - $theta; $pointOnCircle= array($radius/tan( $theta ) +$center_x, -$radius + $center_y);  }
            if($theta>=7*M_PI/4&&$theta<2*M_PI){$theta =2*M_PI -$theta; $pointOnCircle= array($radius +$center_x,  - tan( $theta ) * $radius +$center_y);}  
            
            $points[ $i ] = $pointOnCircle;
        }
        return $points;
    }
    /*-----------------------------------------------------------------*/
    
    function add_4_nodes(&$fxml,$ps,$elem){
        /* for each compound node spacially for the compound nodes which contain only one node inside
         * we add 4 nodes to control the compound size.
         * (we can also add only 2 nodes in diagonal do it later)
         */
        $h=$w=0.1; // size of node  
        for($k=0;$k<4;$k++){    
            $dom=dom_import_simplexml($fxml);
            $dom = $fxml->addChild('node');
            $prefix=str_replace(" ","_",$elem);
            $dom->addAttribute('id', 'in_'.$prefix.$k);
            $dom->addAttribute('label',"");
            $att=$dom->addChild('att');
            $att->addAttribute('name',"CellWhere localization");
            $att->addAttribute('value',$elem);
            $graphics = $dom->addChild('graphics');
            $graphics->addAttribute('type', "ELLIPSE");
            $graphics->addAttribute('x', (string)$ps[$k][0]);
            $graphics->addAttribute('y', (string)$ps[$k][1]);
            $graphics->addAttribute('h', $h);
            $graphics->addAttribute('w', $w);
        }  
    }
    
    function max_min($points){
        $x = array();
        $y = array();
        foreach($points as $p){
            array_push($x,$p[0]);
            array_push($y,$p[1]);
        }
        return array(min($x),max($x),min($y),max($y),array_sum($x)/count($x),array_sum($y)/count($y));
    }
    
    /*------------------------------------------  end xml_modify_1  ------------------------------*/
    /*------------------------------------------- xml_modify_2 -----------------------------------*/
    function xml_modify_2($xml,$head,&$range_xy,$list_comp,&$control_xy,&$cyto_line){
        /* move the compound nodes(in cytoplasm and under membrane) to the right place*/
        global $base;
        $k=0;
        $diff=array(0,0);
        foreach($head as $elem=>$next){
            if($next!=0){
                xml_modify_2($xml,$next,$range_xy,$list_comp,$control_xy,$cyto_line);
            }           
            if(key_exists($elem,$list_comp)){
                $where=$list_comp[$elem];
                if($where=="IN Cytoplasm"){
                    if($control_xy["in_cyto_xy"]==null){
                        $control_xy["in_cyto_xy"][0] = 0;
                        $control_xy["in_cyto_xy"][1] = 0;
                         $cyto_line["left"]=$control_xy["in_cyto_xy"][0];
                    }
                    if($cyto_line["k"]<4){
                        $diff[0] = $control_xy["in_cyto_xy"][0]-$range_xy[$elem][0]+300;
                        $diff[1] = $control_xy["in_cyto_xy"][1]-$range_xy[$elem][2]+rand(0, 400);
                        $cyto_line["k"]++;
                    }elseif($cyto_line["k"]==4){
                        $diff[0] = $cyto_line["left"]-$range_xy[$elem][0]+200;
                        $diff[1] =$cyto_line["bottom"]-$range_xy[$elem][2]+300+rand(0, 400);
                        $control_xy["in_cyto_xy"][1]=$cyto_line["bottom"]+300;
                        $cyto_line["k"]=0;
                    }
                    change_node($xml,$elem,$range_xy,$control_xy,$diff);
                    $control_xy["in_cyto_xy"][0] = $range_xy[$elem][1];
                    $cyto_line["bottom"]=max($cyto_line["bottom"],$range_xy[$elem][3]);
                    $control_xy["all"][1] = max($cyto_line["bottom"],$control_xy["all"][1]);
                    $control_xy["all"][0] = max($range_xy[$elem][1],$control_xy["all"][0]);
                    $control_xy["all"][3] = min(0,$control_xy["all"][3]);
                    //under membrane
                }elseif($where=="UNDER Membrane"){
                    if($control_xy["under_xy"][0]==null){
                        $control_xy["under_xy"][0] = 0;
                        $control_xy["under_xy"][1] = $control_xy["all"][3]-250 ;//if touching 250
                        $control_xy["under_xy"][2] = 0;//min x
                        $control_xy["under_xy"][3] = 0;//min y
                    }
                    $diff[0] = $control_xy["under_xy"][0]-$range_xy[$elem][0];
                    $diff[1] = $control_xy["under_xy"][1]-$range_xy[$elem][3];
                    change_node($xml,$elem,$range_xy,$control_xy,$diff);
                    $control_xy["under_xy"][0] = $range_xy[$elem][1]+300;
                    $control_xy["all"][3] = min($control_xy["all"][3],$range_xy[$elem][2]);//min y
                    $control_xy["under_xy"][3] = min($range_xy[$elem][2],$control_xy["under_xy"][3]);
                }
            }                                                
        }
    }
    
    function change_node(&$fxml,$flabel,&$range_xy,&$control_xy,$diff){
        $n=node_number($fxml,$flabel);
        foreach ($fxml->node as $nodes){
            foreach ($nodes->att as $atts){
                if($atts['name']=="CellWhere localization" &&$atts['value']==$flabel){               
                    foreach($nodes->graphics as $grap){
                        $grap['x']=$grap['x']+$diff[0];
                        $grap['y']=$grap['y']+$diff[1];
                    }
                }
            }
        }
       // if($n>0){
            $range_xy[$flabel][0]=$range_xy[$flabel][0]+$diff[0];
            $range_xy[$flabel][1]=$range_xy[$flabel][1]+$diff[0];
            $range_xy[$flabel][2]=$range_xy[$flabel][2]+$diff[1];
            $range_xy[$flabel][3]=$range_xy[$flabel][3]+$diff[1];
            $range_xy[$flabel][4]=$range_xy[$flabel][4]+$diff[0];
            $range_xy[$flabel][5]=$range_xy[$flabel][5]+$diff[1];
       // }
    }
    /*-----------------------------------------------------------------------------*/  
    function xml_modify_3($xml,$head,&$range_xy,$list_comp,&$control_xy){
        /* move the compound nodes to the right place*/
        global $base;
        $diff=array(0,0);
        foreach($head as $elem=>$next){
            if($next!=0){
                xml_modify_3($xml,$next,$range_xy,$list_comp,$control_xy);
            }
            if($elem=="Nucleus membrane"){
//            if(($elem=="Nucleus membrane"||$elem=="Nucleus")&&node_number($xml,$elem)!=0){
                if(node_number($xml,$elem)==0){
                    $elem="Nucleus";
                }
                if($control_xy["nucleus_xy"]==null){
                        $control_xy["nucleus_xy"][0] = $control_xy["all"][0]+300;
                        $control_xy["nucleus_xy"][1] = 0;
                }
                $diff[0]=$control_xy["nucleus_xy"][0]-$range_xy[$elem][0];
                $diff[1]=$control_xy["nucleus_xy"][1]-$range_xy[$elem][2];
                change_node($xml,$elem,$range_xy,$control_xy,$diff);
                //$elem="Nucleus";
                $control_xy["all"][0] = max($range_xy[$elem][1],$control_xy["all"][0]);//max x
                $control_xy["all"][1] = max($range_xy[$elem][3],$control_xy["all"][1]);//max y
                $control_xy["all"][2] = min($range_xy[$elem][0],$control_xy["all"][2]);//min x
                $control_xy["all"][3] = min($range_xy[$elem][2],$control_xy["all"][3]);//min y
            }elseif(key_exists($elem,$list_comp)){
                $where=$list_comp[$elem];
                if($where=="Cytoplasm"){
                    //$diff[0]=$range_xy["Nucleus"][1]-$range_xy[$elem][0]+600;
                    $diff[0]=$control_xy["all"][0]-$range_xy[$elem][0]+300;
                    $diff[1]=$range_xy["Nucleus"][5]-$range_xy[$elem][5];
                    change_node($xml,$elem,$range_xy,$control_xy,$diff);
                    $control_xy["all"][0] = max($range_xy[$elem][1],$control_xy["all"][0]);
                    $control_xy["all"][1] = max($range_xy[$elem][3],$control_xy["all"][1]);
                }elseif($where=="UNDER Membrane"){
                    $diff =array(0, $control_xy["under_xy"][3]-$range_xy[$elem][2]);
                    change_node($xml,$elem,$range_xy,$control_xy,$diff);
                }elseif($where=="Membrane" ||$where=="IN Membrane"){
                        if($control_xy["memb_xy"]==null){
                            $control_xy["memb_xy"]=array(0,0);
                        }
                        Membrane($xml,$elem,$range_xy,$control_xy);
                        $control_xy["all"][0] = max($control_xy["all"][0],$control_xy["memb_xy"][0]);
                        $control_xy["all"][3] = min($control_xy["all"][3],$control_xy["memb_xy"][1]);
                }elseif($where=="SURFACE Membrane"){
                        if($control_xy["surface_xy"]==null){
                            $control_xy["surface_xy"][0]=0;
                            if($control_xy["under_xy"][0]!=null){
                                $control_xy["surface_xy"][1]=$control_xy["under_xy"][3]-300;
                            }else{
                                $control_xy["surface_xy"][1]=$range_xy["Nucleus"][2]-600-150;
                                //$control_xy["surface_xy"][1]=$control_xy["all"][3]-600-150;
                            }                        
                        }
                        $diff[0]=$control_xy["surface_xy"][0]-$range_xy[$elem][0];
                        $diff[1] =$control_xy["surface_xy"][1]-$range_xy[$elem][3];
                        change_node($xml,$elem,$range_xy,$control_xy,$diff);
                        $control_xy["surface_xy"][0] = $range_xy[$elem][1]+300;   // x
                        $control_xy["all"][3]=min($control_xy["all"][3],$range_xy[$elem][2]);//min y
                        $control_xy["all"][0]=max($control_xy["all"][0],$range_xy[$elem][1]);//max x
                }
            }
        }
    }
    function xml_modify_4($xml,$head,&$range_xy,$list_comp,&$control_xy){
        /* move the compound nodes to the right place*/
        global $base;
        $diff=array(0,0);
        foreach($head as $elem=>$next){
            if($next!=0){
                xml_modify_4($xml,$next,$range_xy,$list_comp,$control_xy);
            }
            if(key_exists($elem,$list_comp)){
                $where=$list_comp[$elem];
                if($where=="ACROSS Membrane"){
                    if($control_xy["across_xy"]==null){  
                        $control_xy["across_xy"][0]=max($control_xy["memb_xy"][0],$control_xy["surface_xy"][0],$control_xy["under_xy"][0]);
                        if($control_xy["under_xy"][0]!=null){
                            $control_xy["across_xy"][1]=$control_xy["under_xy"][3]-200;
                        }else{
                            $control_xy["across_xy"][1]=$range_xy["Nucleus"][2]-600;
                        }
                    }
                    $diff[0]=$control_xy["across_xy"][0]-$range_xy[$elem][0];    
                    $diff[1] = $control_xy["across_xy"][1]-$range_xy[$elem][5]+rand(-10,10);;
                    change_node($xml,$elem,$range_xy,$control_xy,$diff);
                    $control_xy["across_xy"][0] = $range_xy[$elem][1]+300;   // x
                    $control_xy["all"][3]=min($control_xy["all"][3],$range_xy[$elem][2]);//min y
                    $control_xy["all"][0]=max($control_xy["all"][0],$range_xy[$elem][1]);//max x
                }elseif($where=="Extracellular"||$where=="IN Extracellular"){
                    if($control_xy["extra_xy"]==null){
                        $control_xy["extra_xy"]=array(0,$control_xy["all"][3]-300);
                        if($control_xy["surface_xy"]==null){
                            $control_xy["extra_xy"][1]=$control_xy["extra_xy"][1]-300;
                        }
                        if($control_xy["memb_xy"]==null){
                            $control_xy["extra_xy"][1]=$control_xy["extra_xy"][1]-300;
                        }
                        if($control_xy["under_xy"][0]==null){                        
                            $control_xy["extra_xy"][1]=$control_xy["extra_xy"][1]-300;
                        }
                    }
                    $diff[0]= $control_xy["extra_xy"][0]-$range_xy[$elem][0];
                    $diff[1] = $control_xy["extra_xy"][1]-$range_xy[$elem][3]-rand(0,100);
                    change_node($xml,$elem,$range_xy,$control_xy,$diff);
                    $control_xy["extra_xy"][0] = $range_xy[$elem][1]+300;
                    $control_xy["all"][3]=min($control_xy["all"][3],$range_xy[$elem][2]);
                }
            }
        }
    }
    

    /*----------------------------------------------------------------------------*/
     function write_node($fxml,$fout,$fid,$flabel){
        foreach ($fxml->node as $nodes){
            foreach ($nodes->att as $atts){
                if($atts['name']=="CellWhere localization" &&$atts['value']==$flabel){
                    //only compounds
                    /*
                    $nodes["label"]="";
                    $nodes->graphics["h"]="1";
                    $nodes->graphics["w"]="1";*/
                    //-------------
                    fwrite($fout, $nodes->asXML()."\n");
                }                
            }
        }
        return $fid+1;
    }
    
    /*----------------------------------------------------------------------------*/   
    function write_xml($xml,$out,$head,$level,$list_comp,$range_xy,$control_xy,$cyto_line,$for_draw_bg,$compound){
        global $local_id,$base,$colors,$state_memb;
        $h=$w=0.1;
        $transpar=0.7;
        $width=0;
        $count_across=1;
        foreach($head as $elem=>$next){
            if($compound&&!in_array($elem,array("Extracellular","Cytoplasm","Membrane"))&&(!(strstr($elem," membrane")||strstr($elem," MEMBRANE")))){//only compounds
            //if(!in_array($elem,array("Extracellular"))&&(!(strstr($elem," membrane")||strstr($elem," MEMBRANE")))){//all
                $local_id=$local_id+1;
                fwrite($out, '<node id="local_'.$local_id.'"'.' label="'.$elem.'" >'."\n");
                $color=$colors["compound"];
                fwrite($out, '<graphics type="ROUNDRECT" fill="'.$color.'" cy:nodeTransparency="'.$transpar.'" width="'.$width.'" outline="'.$color.'" cy:nodeLabelFont="Arial-0-70"  />'."\n");
                fwrite($out, "<attr>"."\n"."<graph>"."\n");
            }    
            if($elem=="Membrane"){
              //up left
                fwrite($out, '<node id="invisi_memb_1" label="" >'."\n");//up
                fwrite($out, '<graphics fill="#0101DF" x="'.$for_draw_bg["membrane"]["up"][0].'" y="'.$for_draw_bg["membrane"]["up"][1].'" type="ELLIPSE" h="'.$h.'" w="'.$w.'"  cy:nodeLabelFont="Arial-0-70" />'."\n".'</node>'."\n");
                fwrite($out, '<node id="invisi_memb_2" label="" >'."\n");
                fwrite($out, '<graphics fill="#0101DF" x="'.$for_draw_bg["membrane"]["down"][0].'" y="'.$for_draw_bg["membrane"]["down"][1].'" type="ELLIPSE" h="'.$h.'" w="'.$w.'" />'."\n".'</node>'."\n");  
            }
            if($elem=="Cytoplasm"){
                //up left
                fwrite($out, '<node id="invisi_Cytoplasm_1" label="" >'."\n");
                fwrite($out, '<graphics fill="#0101DF" x="'.$for_draw_bg["cytoplasm"]["up"][0].'" y="'.$for_draw_bg["cytoplasm"]["up"][1].'" type="ELLIPSE" h="'.$h.'" w="'.$w.'" cy:nodeLabelFont="Arial-0-70" />'."\n".'</node>'."\n");
                // down right
                fwrite($out, '<node id="invisi_Cytoplasm_2" label="" >'."\n");
                fwrite($out, '<graphics fill="#0101DF" x="'.$for_draw_bg["cytoplasm"]["down"][0].'" y="'.$for_draw_bg["cytoplasm"]["down"][1].'" type="ELLIPSE" h="'.$h.'" w="'.$w.'" />'."\n".'</node>'."\n");
            } 
            if($elem=="Extracellular"){
                //up left
                fwrite($out, '<node id="invisi_extra_1" label="" >'."\n");
                fwrite($out, '<graphics fill="#0101DF" x="'.$for_draw_bg["extracellular"]["up"][0].'" y="'.$for_draw_bg["extracellular"]["up"][1].'" type="ELLIPSE" h="'.$h.'" w="'.$w.'"  cy:nodeLabelFont="Arial-0-70" />'."\n".'</node>'."\n");   
                //down right limit
                fwrite($out, '<node id="invisi_extra_2" label="" >'."\n");
                fwrite($out, '<graphics fill="#0101DF" x="'.$for_draw_bg["extracellular"]["down"][0].'" y="'.$for_draw_bg["extracellular"]["down"][1].'" type="ELLIPSE" h="'.$h.'" w="'.$w.'" />'."\n".'</node>'."\n");
            }
                
            if($next!=0){
                write_xml($xml,$out,$next,$level+1,$list_comp,$range_xy,$control_xy,$cyto_line,$for_draw_bg,$compound);
            }
            /*--------------------------------------------------------------*/
            if(key_exists($elem,$list_comp)&&(!(strstr($elem," membrane")||strstr($elem," MEMBRANE")))){
                $where=$list_comp[$elem];
                if($where=="UNDER Membrane"){
                    $local_id = write_node($xml,$out,$local_id,$elem);
                }elseif($where=="IN Cytoplasm"||$where=="Cytoplasm"){
                    $local_id = write_node($xml,$out,$local_id,$elem);
                }elseif($where=="Membrane" || $where=="IN Membrane"){
                    $local_id = write_node($xml,$out,$local_id,$elem);
                }elseif($where=="Extracellular"||$where=="IN Extracellular"){
                    $local_id = write_node($xml,$out,$local_id,$elem);
                }elseif($where=="SURFACE Membrane"||$where=="ACROSS Membrane"){
                    if($where=="ACROSS Membrane"){
                        //up left
                        fwrite($out, '<node id="invisi_across_1_'.$count_across.'" label=" " >'."\n");
                        fwrite($out, '<graphics fill="#0101DF" x="'.($range_xy[$elem][0]).'" y="'.($for_draw_bg["membrane"]["up"][1]-400).'" type="ELLIPSE" h="'.$h.'" w="'.$w.'" />'."\n".'</node>'."\n");             
                        //down right limit
                        fwrite($out, '<node id="invisi_across_2_'.$count_across.'" label=" " >'."\n");
                        fwrite($out, '<graphics fill="#0101DF" x="'.($range_xy[$elem][0]).'" y="'.($for_draw_bg["cytoplasm"]["up"][1]+100).'" type="ELLIPSE" h="'.$h.'" w="'.$w.'" />'."\n".'</node>'."\n");
                        $count_across++;
                    }
                    $local_id = write_node($xml,$out,$local_id,$elem);
                }elseif($where=="Nucleus"){
                    $local_id = write_node($xml,$out,$local_id,$elem);
                }
            }                                        
            /*------------------------------------------------------------------*/
            if($compound&&!in_array($elem,array("Extracellular","Cytoplasm","Membrane"))&&(!(strstr($elem," membrane")||strstr($elem," MEMBRANE")))){//only compounds
            //if(!in_array($elem,array("Extracellular"))&&!(strstr($elem," membrane")||strstr($elem," MEMBRANE"))){ //all
                fwrite($out, "</graph>"."\n"."</attr>"."\n");
                fwrite($out,"</node>"."\n");
            }        
        }
    }    

    /*------------------------------------------------------------------------------*/
    function convertColor($color){
        #convert hexadecimal to RGB
        if(!is_array($color) && preg_match("/^[#]([0-9a-fA-F]{6})$/",$color)){
        $hex_R = substr($color,1,2);
        $hex_G = substr($color,3,2);
        $hex_B = substr($color,5,2);
        $RGB = array(hexdec($hex_R),hexdec($hex_G),hexdec($hex_B));
        return $RGB;
        }
    }

    function ImageRectangleWithRoundedCorners(&$im, $x1, $y1, $x2, $y2, $radius, $color) {
        // draw rectangle without corners
        imagefilledrectangle($im, $x1+$radius, $y1, $x2-$radius, $y2, $color);
        imagefilledrectangle($im, $x1, $y1+$radius, $x2, $y2-$radius, $color);
        // draw circled corners
        imagefilledellipse($im, $x1+$radius, $y1+$radius, $radius*2, $radius*2, $color);
        imagefilledellipse($im, $x2-$radius, $y1+$radius, $radius*2, $radius*2, $color);
        imagefilledellipse($im, $x1+$radius, $y2-$radius, $radius*2, $radius*2, $color);
        imagefilledellipse($im, $x2-$radius, $y2-$radius, $radius*2, $radius*2, $color);
    }
    
    function draw_background($for_draw_bg,$e){
        global $colors,$file_xml;
        $keys=array("cytoplasm","membrane","extracellular");
        $a=array("up","down");
        for($i=0;$i<3;$i++){
            for($j=0;$j<2;$j++){
                for($k=0;$k<2;$k++){
                    $for_draw_bg[$keys[$i]][$a[$j]][$k]=round($for_draw_bg[$keys[$i]][$a[$j]][$k]/$e);
                }
            }
        } 
        //draw the object in the image
        $diff_x=0-$for_draw_bg["extracellular"]["up"][0];
        $diff_y=0-$for_draw_bg["extracellular"]["up"][1];
        
        $dest = imagecreatetruecolor(($for_draw_bg["extracellular"]["down"][0]+$diff_x), ($for_draw_bg["extracellular"]["down"][1]+$diff_y))
        or die('Cannot Initialize new GD image stream');
        //extracellular
        $white = imagecolorallocate($dest, 255, 255, 255);
        imagefill($dest, 0, 0, $white);
        
        // Create image instances
        $file='background.png';
        $src = imagecreatefrompng($file);
        list($width, $height)=getimagesize($file);
        
        // Copy
        $up_x_mem=$for_draw_bg["membrane"]["up"][0]+$diff_x;
        $up_y_mem=$for_draw_bg["membrane"]["up"][1]+$diff_y;
        $down_x_mem=$for_draw_bg["membrane"]["down"][0]+$diff_x;
        $down_y_mem=$for_draw_bg["membrane"]["down"][1]+$diff_y;
        imagecopyresized($dest, $src, $up_x_mem-100, $up_y_mem-65,0, 0,$down_x_mem-$up_x_mem+200, $down_y_mem-$up_y_mem+150, $width, $height);
        
        // Output and free from memory
        $background=basename($file_xml).'_background.png';
        imagepng($dest,$background);
        imagedestroy($dest);  
        imagedestroy($src);
    }
    /*------------------------------------------------------*/
    
    function layout($xml,$head,$list_comp){   
        $range_xy=array();
        //analyse the node distribution 
        node_position($xml,$head,$range_xy);
        //max x,max y,min x,min y
        $control_xy =array("all"=>array(0,0,0,0),"under_xy"=>null,"surface_xy"=>null,"across_xy"=>null,"in_cyto_xy"=>null,"memb_xy"=>null,"extra_xy"=>null,"nucleus_xy"=>null,"nucleus_xy"=>null);
        //move compound nodes + nodes inside
        $cyto_line=array("k"=>0,"left"=>0,"bottom"=>0);
        $diff_ini_x=0;$first_y=0;
        $memb_size=array();
        
        xml_modify_1($xml,$head,$range_xy,$list_comp);// add innominate nodes & control membrane and size ...
        xml_modify_2($xml,$head,$range_xy,$list_comp,$control_xy,$cyto_line);// position in cell
        xml_modify_3($xml,$head,$range_xy,$list_comp,$control_xy);//compounds for under membrane node
        xml_modify_4($xml,$head,$range_xy,$list_comp,$control_xy);//compounds for under membrane node
        
        $for_draw_bg=array();
        if($control_xy["under_xy"][0]!=null){
            $for_draw_bg["cytoplasm"]["up"][1]=$control_xy["under_xy"][3]+70;
        }else{
            $for_draw_bg["cytoplasm"]["up"][1]=$range_xy["Nucleus"][2]-300;
        }
        
        $for_draw_bg["cytoplasm"]["up"][0]=$control_xy["all"][2]-300;
        $for_draw_bg["cytoplasm"]["down"][0]=max($control_xy["all"][0]+300,$range_xy["Nucleus"][1]+300);
        $for_draw_bg["cytoplasm"]["down"][1]=max($control_xy["all"][1]+300,$range_xy["Nucleus"][3]+300);
        
        $for_draw_bg["membrane"]["up"][0]=$for_draw_bg["cytoplasm"]["up"][0]-300;
        $for_draw_bg["membrane"]["up"][1]=$for_draw_bg["cytoplasm"]["up"][1]-300;
        $for_draw_bg["membrane"]["down"][0]=$for_draw_bg["cytoplasm"]["down"][0]+300;
        $for_draw_bg["membrane"]["down"][1]=$for_draw_bg["cytoplasm"]["down"][1]+300;
                        
        $for_draw_bg["extracellular"]["up"][0]=$for_draw_bg["membrane"]["up"][0]-300;
        $for_draw_bg["extracellular"]["up"][1]=$control_xy["all"][3]-50;
        //$for_draw_bg["extracellular"]["up"][1]=$for_draw_bg["membrane"]["up"][1]-300;
        $for_draw_bg["extracellular"]["down"][0]=$for_draw_bg["membrane"]["down"][0]+300;
        $for_draw_bg["extracellular"]["down"][1]=$for_draw_bg["membrane"]["down"][1]+300;
        
        //write_xml with compound
        with_compound($xml,$head,$list_comp,$range_xy,$control_xy,$cyto_line,$for_draw_bg);
        //write xml without compound node
        without_compound($xml,$head,$list_comp,$range_xy,$control_xy,$cyto_line,$for_draw_bg);
        
        $e=3;//reduce the size of image
        draw_background($for_draw_bg,$e);
    }
    
    /* write the output xml file--------------------------------------------------*/
    function with_compound($xml,$head,$list_comp,$range_xy,$control_xy,$cyto_line,$for_draw_bg){
        global $file_xml;
        $out = fopen($file_xml."_web.xml", "w+"); 
        fwrite($out,'<graph xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:cy="http://www.cytoscape.org" xmlns="http://www.cs.rpi.edu/XGMML">'."\n");
        $local_id=0;
        //write_xml with compound
        write_xml($xml,$out,$head,0,$list_comp,$range_xy,$control_xy,$cyto_line,$for_draw_bg,1);
        //write edges
        write_edge($xml,$out);
    
        fwrite($out,"</graph>");
        fclose($out);
     
        //read the entire string 
        $str=file_get_contents($file_xml."_web.xml"); 
        $str=str_replace("      \n","",$str);
        $str=str_replace(" nodeLabelFont="," cy:nodeLabelFont=",$str);
        $str=str_replace(" nodeTransparency="," cy:nodeTransparency=",$str);
        file_put_contents($file_xml."_web.xml", $str);
    }
    
    function without_compound($xml,$head,$list_comp,$range_xy,$control_xy,$cyto_line,$for_draw_bg){
        global $file_xml;        
        $fout = fopen($file_xml."_cy3.xml", "w+"); 
        fwrite($fout,'<graph xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:cy="http://www.cytoscape.org" xmlns="http://www.cs.rpi.edu/XGMML">'."\n");
        //write_xml without compound
        write_xml($xml,$fout,$head,0,$list_comp,$range_xy,$control_xy,$cyto_line,$for_draw_bg,0);
        //write edges
        write_edge($xml,$fout);
        fwrite($fout,"</graph>");
        fclose($fout);
     
        //read the entire string 
        $str=file_get_contents($file_xml."_cy3.xml"); 
        $str=str_replace("      \n","",$str);
        $str=str_replace(" nodeLabelFont="," cy:nodeLabelFont=",$str);
        $str=str_replace(" nodeTransparency="," cy:nodeTransparency=",$str);
        file_put_contents($file_xml."_cy3.xml", $str);
        
        if(file_exists($xml_file_name.'_cy3.xml')){
	  echo '<br/><a href="'.$xml_file_name.'_cy3.xml" >cy3 xml</a>';
	}
    }
    
    /*------------------------------------------------------*/
    function write_edge($fxml,$fout){
        /*write edges*/
        foreach ($fxml->edge as $edges){
            fwrite($fout, $edges->asXML()."\n");
        }
    }

/*----------------------------------------------------End function---------------------------------------------------*/

?>