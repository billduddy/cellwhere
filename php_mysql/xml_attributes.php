<html>
    <head>
	<script language="JavaScript">
		function showPleaseWait() {
			var butt = document.getElementById("msgDiv");
			butt.innerHTML="Please Wait...</br>Querying Uniprot and/or QuickGO</br>...If using Gene Ontology (QuickGO), give about 1-2 seconds per query ID, sometimes much longer...";
		 return true;
		}
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
    <h2><font color="#1F88A7">CellWhere BETA</font> </h2>

<?php
    if (isset($_POST)){    
        if (is_uploaded_file($_FILES['uploadedxmlfile']['tmp_name'])){
            if(isset($error)) {
                echo '<script>alert("'.$error.'");</script>';
                die(); //Ensure no more processing is done
            }
            
            // Where the file is going to be placed 
            $target_path = $_ENV["OPENSHIFT_DATA_DIR"];
            //$target_path = "uploads/";    // old target path on local machine
            /* Add the original filename to our target path.  
            Result is "uploads/filename.extension" */
            $target_path = $target_path . basename( $_FILES['uploadedxmlfile']['name']); 
            
            if(move_uploaded_file($_FILES['uploadedxmlfile']['tmp_name'], $target_path)) {
                echo '<p style="color:#848484;">The file "'.  basename( $_FILES['uploadedxmlfile']['name']). 
                '" was uploaded </p>';
            } else{
                echo "There was an error uploading the file, please try again! <br /><br />";
            }
            
            // rename the file with time
            date_default_timezone_set('Europe/Paris');
            $D=date("YmdHis");
            $_FILES['uploadedxmlfile']['name']=$D.$_FILES['uploadedxmlfile']['name'];
            
            //echo $target_path;
            if (@simplexml_load_file($target_path))
            {
                $xml=simplexml_load_file($target_path);
            }
            else 
            {
                echo "Can't open the xml file!";
            }
            $calculted=0;
            if($xml->node[0]->att[0]){
                foreach($xml->node[0]->att as $att){ 
                    if($att['name']=='CellWhere localization') $calculted=1;
                }
                if(!$calculted){
                    echo '<form enctype="multipart/form-data" action="process.php" method="POST" onSubmit="return showPleaseWait()">';
                    //ID type                    
                    echo '<!--Dropdown menu for ID type-->
                    <b><font color="#1F88A7"><br />Select your query ID type:</font> </b><br />
                    <select name="ID_type">
                    <option value=""></option>
                    <option value="GeneSymbol">Gene Symbol (e.g. TP53, Dmd)</option>
                    <option value="UniprotACC">Uniprot Accession (e.g. P04637, P11531)</option>
                    <option value="UniprotID">Uniprot ID (e.g. P53_HUMAN, DMD_MOUSE)</option>
                    <option value="Entrez">Entrez Gene ID (e.g. 7157, 13405)</option>
                    <option value="Ensembl">Ensembl ID (e.g. ENSG00000141510, ENSMUSG00000045103)</option>
                    <option value="Blank">Type not listed? We recommend www.uniprot.org/mapping</option>
                    </select>';
                    
                    echo '<br /><font color="#1F88A7">Restrict Gene Symbol searches species:</font><br />
                    <select name="HumMouseBox">
                    <option value="HumMouseOverlap">Humman Mouse Overlap</option>
                    <option value="HumanOnly">Human Only</option>
                    <option value="MouseOnly">Mouse Only</option>
                    </select><br />';
                    
                    //ID type attribute
                    echo '<b><font color="#1F88A7"><br />Select the attribute of query ID type:</font> </b><br />
                    <select name="att_ID_type">
                    <option value=""></option>';
                    
                    foreach($xml->node[0]->att as $att){ 
                        echo '<option value="'.$att['name'].'">'.$att['name'].'</option>';
                    }
                    echo '</select>';
                    
                    //Dropdown menu for Location term retrieval
                    echo '<br /><b><font color="#1F88A7"><br /><br />Select sources from which to retrieve Location terms:</font> </b><br />
                        <select name="Source_Loc_Term">
                        <option value="UniprotAndGO">Both Uniprot and the Gene Ontology</option>
                        <option value="UniprotOnly">Uniprot only</option>
                        <option value="GOonly">The Gene Ontology only</option>
                        </select>';
        
                    echo'<a id="displayText2" href="javascript:toggle2();">(more info)</a>
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
                                                          Compartments</a> including the "dystrophin-associated glycoprotein complex"
                                                          and "Z disc", but also
                                                          "Filopodium" which has been reported not in muscle cells but in platelets.<br /></font>
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
                    <!--<input type="hidden" name="MAX_FILE_SIZE" value="500000" />  <!--This is probably redundant with the $maxsize check in process.php, but I\'ve left it just to be safe-->
                    <b><font color="#1F88A7">or upload your own:</font> </b>
                    <input name="uploadedflavour" type="file" style="background-color:#ffffff; border:solid 1px #1F88A7"/>';
                    
                    echo '<a href="download_template.php">(download generic template)</a>';	// Link to a new page that will prompt download of template
                    echo '<a id="displayText3" href="javascript:toggle3();">(more info)</a>
                    <div id="toggleText3" style="display: none"><b>CellWhere retrieves localization terms for each gene/protein. The \'flavour\' 
                                                        mapping file tells it how to map these, and which of the mapped localizations
                                                        are the more relevant to your area of research. The flavour file also tells CellWhere\'s
                                                        network viewer how to display different localizations relative to each other.
                                                        You can control each of these steps by creating your own flavour.</b><br /><font size="2">
                                                        To create your own flavour, 
                                                         download the tab-delimited generic template and give higher priority numbers to the localizations
                                                          that interest you, then upload it in the field above. You can also change the localizations
                                                          themselves (the \'OurLocalization\' column). Just be careful of the following:
                                                          <br />(1) don\'t alter the first two columns<br />(2) don\'t give the same priority number to more than one
                                                          of your mapped localizations<br />(3) don\'t attribute more than one spatial relation to a mapped localization<br />
                                                          (4) don\'t invent your own spatial relation terms (though feel free to re-attribute the ones that are already
                                                          there).</b></font><br />
                    </div>';

                    // chose the fold change colomn        
                    echo'<br /><br /><b><font color="#1F88A7"><br />Select the attribute of gene expression fold change:</font> </b><br />
                        <select name="att_FC">
                        <option value=""></option>
                        <option value="NoFC">No Fold Change Attribute</option>';
                    foreach($xml->node[0]->att as $att){
                        echo '<option value="'.$att['name'].'">'.$att['name'].'</option>';
                    }
                    echo '</select>';
                    
                    //file name
                    echo '<input id="uploadedxmlfile" name="uploadedxmlfile" value ="'.$target_path.'" type="hidden" />';
                    
                    echo '<!--Dropdown menu for node name display-->
                        <b><font color="#1F88A7"><br /><br />Select the name type of gene you want show at network:</font> </b><br />
                        <select name="show_name">
                        <option value="Gene_name">Gene Symbol</option>
                        <option value="UniprotACC">Uniprot Accession</option>
                        <!--<option value="type_upload">The same type as upload ID</option>-->
                        </select>';  
                                        
                    //<!--Next and reset buttons -->
                    echo '<br /><br /><input id="butt" name="next" type="submit" value="Submit" /><input type="reset" value="Reset" />';
                    echo '</form>';
                }else{
                    echo '<br /><br /><p style="color:red;">This network have already been calculated!</p>';
                }
            }else{
                echo '<br /><br /><p style="color:red;">The format of xgmml you uploaded is not correct!</p>';
            }
        }
    }  
    
?>    
</body>
</html>