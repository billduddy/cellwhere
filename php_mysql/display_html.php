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

    global $list_comp;
	if(!file_exists($new_xml_file."_html_out.xml")) {
	    die("File <".$new_xml_file.$suffix."> not found");
	} else {
	    $xml=simplexml_load_file($new_xml_file.$suffix);
	}
	$out = fopen($new_xml_file."_out.html","w+");// the output html script

	/*------------------------------ write html(begin)--------------------------------------*/
	echo '
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
			background-image:url("'.explode('uploads/',$new_xml_file)[1].'_background.png");
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
	';
	
	/*------------------------------ php functions --------------------------------------*/
	    function json_node($out,$root_node,$parent,$next){
		for($i=0;$i<count($root_node);$i++){
		    $nodes=$root_node[$i];
		    //data
			echo ",\n".'{"data":';
			echo '{"id":"'.$nodes["id"].'"';
			echo ', "name":"'.$nodes["label"].'"';
			if($parent&&$nodes["id"]!=$parent){
			    echo ', "parent":"'.$parent.'"';
			}
		    //attributes
			for($j=0;$j<count($nodes->att);$j++){
			    $atts= $nodes->att[$j];                    
			    $name=$atts["name"];
			    if($name=='name'){ $name='label';}
			    $value=$atts["value"];
			    echo ',"'.$name.'":"'.$value.'"';
			}
			echo '}';
			
		    //group
			echo ',"group":"nodes"';
			echo '}';
			
		    if($nodes->attr[0]!=null){
			$parent = $nodes['id'];   
			$child= $nodes->attr[0]->graph[0]->node;
			if($root_node[$i+1]!=null){
			    $next=1;
			}
			json_node($out,$child,$parent,$next);
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
			echo 'cy.nodes("#'.$nodes["id"].'").position({x: '.round($graphic["x"]).',  y:'.round($graphic["y"]).'});'."\n";
		    }
		}
		if($nodes->attr[0]!=null){
		    $child= $nodes->attr[0]->graph[0]->node;
		    json_position($out,$child);
		}
	    }
	}
	
	function json_style($out,$root_node,$next){
	    $shapes=array("ROUNDRECT"=>"roundrectangle","ELLIPSE"=>"ellipse","TRIANGLE"=>"triangle");
	    for($i=0;$i<count($root_node);$i++){
		$nodes=$root_node[$i];
		$id=$nodes["id"];
		$label=$nodes["label"];
		if(strstr($id,"local")){
		    $text_valign="bottom";
		    $opacity = 0.7;
		    $font_size = 80;
		    if(in_array($label,array("Cell surface","Cell wall"))){
			$text_valign="top";
		    }
		}else{
		    $font_size = 60;		
		    $text_valign = "center";
		    $opacity = 1;
		}
		foreach($nodes->graphics as $graphics){
		    echo ",\n".'{"selector":"node#'.$id.'","css":{';
		    echo '"background-color":"'.$graphics['fill'].'","background-opacity":"'.$opacity.'","border-width":"'.$graphics["width"].'","border-color":"'.$graphics['outline'].'","width":"'.$graphics["w"].'","height":"'.$graphics["h"].'","shape":"'.$shapes[(string)$graphics['type']].'"';		    
		    echo ',"color":"black","content":"data(name)","font-size":"'.$font_size.'","text-valign":"'.$text_valign.'","text-outline-color":"black","text-outline-width":"2px","border-opacity":"'.$opacity.'"}}';          
		}	
		if($nodes->attr[0]!=null){
		    $child= $nodes->attr[0]->graph[0]->node;
		    if($child[$i+1]!=null){
			$next=1;
		    }
		    json_style($out,$child,$next);
		}
	    }
	}
	
	/*----------------------------- write json part --------------------------------------*/
	// JSON for cytoscape js 
	if($xml){
	    //	elements 
	    echo '{"elements":'."\n";
	    ///nodes
	    if($xml->node){
		echo '{"nodes":['."\n";
		$root_node = $xml->node;
		$parent=null;
		json_node($out,$root_node,$parent,1);
		//end of node
		echo "]";
	    }
	    ///edge 
	    if($xml->edge!=NULL){
	    echo ','."\n".'"edges":[';
		for($i=0;$i<count($xml->edge);$i++){
		    $edges=$xml->edge[$i];
		    echo "\n".'{"data":';
		    echo '{"source":"'.$edges["source"].'"';
		    echo ', "target":"'.$edges["target"].'"';
		    echo ', "id":"'.$edges["id"].'"}}';
		    if($xml->edge[$i+1]!=NULL){
			echo ",";            
		    }
		}
		echo "\n]\n";//end of edge
	    }
	    echo "},\n";//end of element // end of JSON for cytoscape js 
	    
	    //	style	cytoscape js commands
	    echo '"style":['."\n";
	    ///node style
	    json_style($out,$xml->node,1);
	    
	    ///edges style
	    $nb_edge=$xml->edge->count();
	    echo "number of edge ".$nb_edge."!\n";
	    ////edge color lighter as the edge number bigger in total 
	    if($nb_edge<=100){
		$edge_col="#A4A4A4";
	    }elseif($nb_edge<=500){
		$edge_col="#BDBDBD";
	    }else{
		$edge_col="#D8D8D8";
	    }
	    echo ",\n".'{"selector":"edge","css":{';
	    echo '"curve-style":"haystack","line-style":"solid","line-color":"'.$edge_col.'","width":"4" }}';
	    ///style for seleted nodes
	    echo ",\n".'{"selector":":selected","css":{"text-outline-color":"#000","background-color":"#FFFF00","border-width":"10","target-arrow-color":"#000","line-color":"#000"}}';
	    echo "\n],\n"; //end of style

	    //other options
	    echo '"scratch":{},'."\n".'"zoomingEnabled":true,'."\n".'"userZoomingEnabled":false,"zoom":0.5,'."\n";
	    //ready function
	    echo '  ready: function(){'."\n".'   window.cy = this;';
	    //node position 
	    json_position($out,$xml->node);
	    //place the graph in the middle of the window
	    echo 'cy.fit();'."\n";	
	    echo '}'; 	//end of ready function 
	    echo '});'."\n";	// end of #cy
	    echo '});'."\n";	//end of function  
	}
	
	/*-------------------------- write html(end) ------------------------------------*/
	// end of JavaScript
	echo " window.onresize=function(){\nlocation.reload();\n}; \n </script>\n";
	echo ' <meta charset=utf-8 />
	<title>Cytoscape.js initialisation</title>
	  <script src="http://cytoscape.github.io/cytoscape.js/api/cytoscape.js-latest/cytoscape.min.js"></script>
	</head>
	<body>
	  <div id="cy" class="bgimg" ></div>
	</body>
	</html>';
	//*/
	fclose($out);
	$str=file_get_contents($new_xml_file."_out.html"); 
	$str=str_replace('{"nodes":['."\n,",'{"nodes":['."",$str);
	$str=str_replace('"style":['."\n,",'"style":['."",$str);
	file_put_contents($new_xml_file."_out.html", $str);
?>