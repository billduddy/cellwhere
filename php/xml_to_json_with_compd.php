<?php
    /*
     * Module:	CellWhere
     * 
     * Description:
     * 		This script converts the gxxml formated file to JSON format that <cytoscape.js> library can read
     * 		and then output a html script which is capable to displays the network by browsers.
     *
     * Usage:   xml_to_json($new_xml_file,$list_comp);
     * 		$new_xml_file: the xml file re-organized according to the original xml which is waiting to be converted.
     * 		$list_comp : a variable, a list of compounds(locations) appeare in above xml file 
     *
     * Author : ZHU Lu , Master 2 Bioinformatics, Universite Paris Diderot
     * Contact: zhu.lu@hotmail.com
    */

    function xml_to_json($new_xml_file,$suffix,$list_comp,$org_nodes){
	if(!file_exists($new_xml_file.$suffix)) {
	    die("File <".$new_xml_file.$suffix."> not found");
	} else {
	    $xml=simplexml_load_file($new_xml_file.$suffix);
	}
	$new_xml_file = basename($new_xml_file);
	$out = fopen($new_xml_file."_web.html","w+");// the output html script
	/*------------------------------ write html(begin)--------------------------------------*/
	//background-image:url("'.explode('uploads/',$new_xml_file)[1].'_background.png");
	fwrite($out,'
	    <!DOCTYPE html>
	    <html>
	    <head>
	    <meta name="description" content="[An example of getting started with Cytoscape.js]" />
		<style type="text/css">
		    body { 
		      font: 14px helvetica neue, helvetica, arial, sans-serif;
		      background-color:white;
		    }
		    
		    #cy {
		      height: 100%;
		      width: 100%;
		      position: absolute;
		      left: 0;
		      top: 0;
		    }
		    
		    .bgimg {		
			background-image:url("'.$new_xml_file.'_background.png");
			background-color:white;
			background-repeat:no-repeat;
			background-position:center center;
			background-size: contain;
			-webkit-background-size: contain;
			-moz-background-size: contain;
			-o-background-size: contain;
			margin: 0;
			padding: 0;
		    }
		</style>
		
	    <!-- javascript part  --> 
	    <script src="http://ajax.googleapis.com/ajax/libs/jquery/1/jquery.min.js"></script>
	    <script>
		//function 
		$(function(){
		    //#cy network construction 
		    $(\'#cy\').cytoscape(
	');
	
	/*------------------------------ php functions --------------------------------------*/
	    function json_node($out,$root_node,$parent,$next,$org_nodes){
		for($i=0;$i<count($root_node);$i++){
		    $nodes=$root_node[$i];
		    $label=$nodes["label"];
		    $id=$nodes['id'];
		    //data
			fwrite($out,",\n".'{"data":');
			fwrite($out,'{"id":"'.$nodes["id"].'"');
			fwrite($out,', "name":"'.$label.'"');
			//link to uniprot - href: 'http://cytoscape.org'
			if($label!=""&&!strstr($id,'local')){
			    $uniprotACC = $id;
			    if($uniprotACC!=""){
				fwrite($out,', "href":"http://www.uniprot.org/uniprot/'.$uniprotACC.'"');
			    }
			}
			if($parent&&$nodes["id"]!=$parent){
			    fwrite($out,', "parent":"'.$parent.'"');
			}
		    //attributes
			for($j=0;$j<count($nodes->att);$j++){
			    $atts= $nodes->att[$j];                    
			    $name=$atts["name"];
			    if($name=='name'){ $name='label';}
			    $value=$atts["value"];
			    fwrite($out,',"'.$name.'":"'.$value.'"');
			}
			fwrite($out,'}');
			
		    //group
			fwrite($out,',"group":"nodes"');
			fwrite($out,'}');
			
		    if($nodes->attr[0]!=null){
			$parent = $nodes['id'];
			//fwrite($out,",");       
			$child= $nodes->attr[0]->graph[0]->node;
			if($root_node[$i+1]!=null){
			    $next=1;
			}
			json_node($out,$child,$parent,$next,$org_nodes);
			$parent=null;
		    }
		}
	    }
	
	function  json_position($out,$root_node){
	    for($i=0;$i<count($root_node);$i++){
		$nodes=$root_node[$i];
		$id=$nodes["id"];
		if(!strstr($id,'local')){
		    foreach($nodes->graphics as $graphic){
			fwrite($out,'cy.nodes("#'.$nodes["id"].'").position({x: '.round($graphic["x"]).',  y:'.round($graphic["y"]).'});'."\n");
		    }
		}
		if($nodes->attr[0]!=null){
		    $child= $nodes->attr[0]->graph[0]->node;
		    json_position($out,$child);
		}
	    }
	}
	
	function json_style($out,$root_node,$next,$org_nodes){
	    $shapes=array("ROUNDRECT"=>"roundrectangle","ELLIPSE"=>"ellipse","TRIANGLE"=>"triangle");
	    for($i=0;$i<count($root_node);$i++){
		$nodes=$root_node[$i];
		$id=$nodes["id"];
		$label=$nodes["label"];
		if(strstr($id,"local")){
		    $text_valign="bottom";
		    $opacity = 0.7;
		    $font_size = 70;
		    if(in_array($label,array("Cell surface","Cell wall"))){
			$text_valign="top";
		    }
		}else{
		    $font_size = 60;		
		    $text_valign = "center";
		    $opacity = 1;
		}
		foreach($nodes->graphics as $graphics){
		    $fill=$graphics['fill'];
		    if($org_nodes&&in_array($label,$org_nodes)){
			$fill="red";
		    }  
		    fwrite($out,",\n".'{"selector":"node#'.$id.'","css":{');
		    fwrite($out,'"background-color":"'.$fill.'","background-opacity":"'.$opacity.'","border-width":"'.$graphics["width"].'","border-color":"'.$fill.'","width":"'.$graphics["w"].'","height":"'.$graphics["h"].'","shape":"'.$shapes[(string)$graphics['type']].'"');		    
		    fwrite($out,',"color":"black","content":"data(name)","font-size":"'.$font_size.'","text-valign":"'.$text_valign.'","text-outline-color":"black","text-outline-width":"2px","border-opacity":"'.$opacity.'"}}');          
		}	
		if($nodes->attr[0]!=null){
		    $child= $nodes->attr[0]->graph[0]->node;
		    if($child[$i+1]!=null){
			$next=1;
		    }
		    json_style($out,$child,$next,$org_nodes);
		}
	    }
	}
	
	/*----------------------------- write json part --------------------------------------*/
	// JSON for cytoscape js 
	if($xml){
	    //	elements 
	    fwrite($out,'{"elements":'."\n");
	    ///nodes
	    if($xml->node){
		fwrite($out,'{"nodes":['."\n");
		$root_node = $xml->node;
		$parent=null;
		json_node($out,$root_node,$parent,1,$org_nodes);
		//end of node
		fwrite($out,"]");
	    }
	    ///edge 
	    if($xml->edge!=NULL){
	    fwrite($out,','."\n".'"edges":[');
		for($i=0;$i<count($xml->edge);$i++){
		    $edges=$xml->edge[$i];
		    fwrite($out,"\n".'{"data":');
		    fwrite($out,'{"source":"'.$edges["source"].'"');
		    fwrite($out,', "target":"'.$edges["target"].'"');
		    fwrite($out,', "id":"'.$edges["id"].'"}}');
		    if($xml->edge[$i+1]!=NULL){
			fwrite($out,",");            
		    }
		}
		fwrite($out,"\n]\n");//end of edge
	    }
	    fwrite($out,"},\n");//end of element // end of JSON for cytoscape js 
	    
	    //	style	cytoscape js commands
	    fwrite($out,'"style":['."\n");
	    ///node style
	    json_style($out,$xml->node,1,$org_nodes);
	    
	    ///edges style
	    $nb_edge=$xml->edge->count();
	    //echo "number of edge ".$nb_edge."!\n";
	    ////edge color lighter as the edge number bigger in total 
	    if($nb_edge<=100){
		$edge_col="#A4A4A4";
	    }elseif($nb_edge<=500){
		$edge_col="#BDBDBD";
	    }else{
		$edge_col="#D8D8D8";
	    }
	    fwrite($out,",\n".'{"selector":"edge","css":{');
	    fwrite($out,'"curve-style":"haystack","line-style":"solid","line-color":"'.$edge_col.'","width":"4" }}');
	    ///style for seleted nodes
	    fwrite($out,",\n".'{"selector":":selected","css":{"text-outline-color":"#000","background-color":"#FFFF00","border-width":"10","target-arrow-color":"#000","line-color":"#000"}}');
	    fwrite($out,"\n],\n"); //end of style

	    //other options
	    fwrite($out,'"scratch":{},'."\n".'"zoomingEnabled":true,'."\n".'"userZoomingEnabled":false,"zoom":0.5,'."\n");
	    //ready function
	    fwrite($out,'  ready: function(){'."\n".'   window.cy = this;');
	    //node position 
	    json_position($out,$xml->node);
	    //place the graph in the middle of the window
	    fwrite($out,'cy.fit();'."\n");
	    // node link to uniprot page
	    fwrite($out,"cy.on('tap', 'node', function(){window.open( this.data('href') );});");
	    fwrite($out,'}'); 	//end of ready function 
	    fwrite($out,'});'."\n");	// end of #cy
	    fwrite($out,'});'."\n");	//end of function  
	}
	
	/*-------------------------- write html(end) ------------------------------------*/
	// end of JavaScript
	fwrite($out," window.onresize=function(){\nwindow.location.reload(true);\n window.location.replace('".$new_xml_file."_web.html');}; \n </script>\n");
	fwrite($out,' <meta charset=utf-8 />
	<title>Cytoscape.js initialisation</title>
	  <script src="http://cytoscape.github.io/cytoscape.js/api/cytoscape.js-latest/cytoscape.min.js"></script>
	</head>
	<body>
	  <div id="cy" class="bgimg" ></div>
	</body>
	</html>');
	//*/
	fclose($out);
	$str=file_get_contents($new_xml_file."_web.html"); 
	$str=str_replace('{"nodes":['."\n,",'{"nodes":['."",$str);
	$str=str_replace('"style":['."\n,",'"style":['."",$str);
	file_put_contents($new_xml_file."_web.html", $str);
    }
?>