<html>
    <head>
	<script language="JavaScript">
		<!--
		function showPleaseWait() {
			var butt = document.getElementById("msgDiv");
			butt.innerHTML="Please Wait...</br>Querying Uniprot and/or QuickGO</br>...If using Gene Ontology (QuickGO), give about 1-2 seconds per query ID, sometimes much longer...";
		 return true;
		}
		//-->
	</script>
        
        <script language="javascript"> 
            function toggle() {
                    var ele = document.getElementById("toggleText");
                    var text = document.getElementById("displayText");
                    if(ele.style.display == "block") {
                            ele.style.display = "none";
                            text.innerHTML = "(more info)";
                    }
                    else {
                            ele.style.display = "block";
                            text.innerHTML = "(collapse)";
                    }
            } 
        </script>
    
        <script language="javascript"> 
            function toggle2() {
                    var ele = document.getElementById("toggleText2");
                    var text = document.getElementById("displayText2");
                    if(ele.style.display == "block") {
                            ele.style.display = "none";
                            text.innerHTML = "(more info)";
                    }
                    else {
                            ele.style.display = "block";
                            text.innerHTML = "(collapse)";
                    }
            } 
        </script>
        
        <script language="javascript"> 
            function toggle3() {
                    var ele = document.getElementById("toggleText3");
                    var text = document.getElementById("displayText3");
                    if(ele.style.display == "block") {
                            ele.style.display = "none";
                            text.innerHTML = "(more info)";
                    }
                    else {
                            ele.style.display = "block";
                            text.innerHTML = "(collapse)";
                    }
            } 
        </script>
</head>
<body>
<h2><font color="#1F88A7">CellWhere</font> </h2>
    <form enctype="multipart/form-data" action="process_Lu.php" method="POST" onSubmit="return showPleaseWait()">
    <?php
    if (isset($_POST)){
        //echo $_POST["ID_type"]."/".$_POST["att_ID_type"]."&".$_POST["uploadedxmlfile"];
        if (@simplexml_load_file($_POST["uploadedxmlfile"])){
            $xml=simplexml_load_file($_POST["uploadedxmlfile"]);
        }else{
            echo "Can't open the xml file!";
        }
        
        $fout=fopen($_POST["uploadedxmlfile"]."_gene_list.txt","w+");
        foreach($xml->node as $nodes){
            foreach($nodes->att as $att){
                if($att['name']==$_POST["att_ID_type"]){
                    fwrite($fout,$att['value']."\r\n");
                }                  
            }
        }
        fclose($fout);
	echo '<input id="ID_type" name="ID_type" value ="'.$_POST["ID_type"].'" type="hidden" />';
	echo '<input id="att_ID_type" name="att_ID_type" value ="'.$_POST["att_ID_type"].'" type="hidden" />';
	echo '<input id="gene_list_file" name="gene_list_file" value ="'.$_POST["uploadedxmlfile"].'_gene_list.txt" type="hidden" />';
	echo '<input id="att_FC" name="att_FC" value ="'.$_POST["att_FC"].'" type="hidden" />';
    }    
?>
    <!--Dropdown menu for Location term retrieval-->
    <b><font color="#1F88A7"><br /><br />Select sources from which to retrieve Location terms:</font> </b><br />
    <select name="Source_Loc_Term">
    <option value="UniprotAndGO">Both Uniprot and the Gene Ontology</option>
    <option value="UniprotOnly">Uniprot only</option>
    <option value="GOonly">The Gene Ontology only</option>
    </select>
        
    <a id="displayText2" href="javascript:toggle2();">(more info)</a>
    <div id="toggleText2" style="display: none"><b>The default is to retrieve Localization terms from both the Uniprot
                                        "Subcellular location" field and from the Gene Ontology
                                         Cellular Compartment annotation.</b><br /><font size="2"
                                        >Each have their advantages: in general, Uniprot is more conservative, 
                                         but the Gene Ontology has a greater depth. The Gene Ontology
                                          tends to be inclusive of all published locations, even locations which
                                          may be rare for a given protein. For example, the protein Dystrophin
                                          is most studied at the membrane of muscle cells and
                                          <a href="http://www.uniprot.org/uniprot/P11532">its Uniprot Subcellular location</a> is
                                          restricted to this. However, the Gene Ontology lists
                                          <a href="http://www.ebi.ac.uk/QuickGO/GProtein?ac=P11532">several related and
                                          sometimes more specific Cell
                                          Compartments</a> including the 'dystrophin-associated glycoprotein complex'
                                          and 'Z disc', but also
                                          'Filopodium' which has been reported not in muscle cells but in platelets.<br /></font>
                                        <font><b>By default, we recommend retrieving both Uniprot and GO locations, but
                                        relying on prioritization scoring to 
                                        guide the CellWhere location towards your research interests.</b></font><br />
    </div>

    <!--Dropdown menu and upload box for flavour selection-->
    <b><font color="#1F88A7"><br /><br />Select flavour:</font> </b><br />
    <select name="Flavour">
    <option value="muscle">Muscle</option>
    <option value="generic">Generic</option>
    </select>
    <!--<input type="hidden" name="MAX_FILE_SIZE" value="500000" />  <!--This is probably redundant with the $maxsize check in process.php, but I've left it just to be safe-->
    <b><font color="#1F88A7">or upload your own:</font> </b>
    <input name="uploadedflavour" type="file" style='background-color:#ffffff; border:solid 1px #1F88A7'/>  
    <?php
    echo '<a href="download_template.php">(download generic template)</a>';	// Link to a new page that will prompt download of template
    ?>
    <a id="displayText3" href="javascript:toggle3();">(more info)</a>
    <div id="toggleText3" style="display: none"><b>CellWhere retrieves localization terms for each gene/protein. The 'flavour' 
                                        mapping file tells it how to map these, and which of the mapped localizations
                                        are the more relevant to your area of research. The flavour file also tells CellWhere's
                                        network viewer how to display different localizations relative to each other.
                                        You can control each of these steps by creating your own flavour.</b><br /><font size="2">
                                        To create your own flavour, 
                                         download the tab-delimited generic template and give higher priority numbers to the localizations
                                          that interest you, then upload it in the field above. You can also change the localizations
                                          themselves (the 'OurLocalization' column). Just be careful of the following:
                                          <br />(1) don't alter the first two columns<br />(2) don't give the same priority number to more than one
                                          of your mapped localizations<br />(3) don't attribute more than one spatial relation to a mapped localization<br />
                                          (4) don't invent your own spatial relation terms (though feel free to re-attribute the ones that are already
                                          there).</b></font><br />
    </div>
    
    
    <!--Submit and reset buttons -->
    <br /><br /><input id="butt" name="sub" type="submit" value="Submit" /><input type="reset" value="Reset" />
</form>


    <br /><br />
<div id="msgDiv"></div>
</body>
</html>