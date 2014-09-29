<html>
   <?php
      ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
      // **Very** basic password protection: part 1 (see part 2 at end of index.php //////////////////////////////////////////////////////////
      // Taken from: http://stackoverflow.com/questions/4115719/easy-way-to-password-protect-php-page. ///////////////////////////////////////
      ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
      $username = "beta_tester";
      $password = "beta_hippo";
      $nonsense = "flabbergastedrhinoceros";
      
      if (isset($_COOKIE['PrivatePageLogin'])) {
	 if ($_COOKIE['PrivatePageLogin'] == md5($password.$nonsense)) {
      ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
   ?>
   
   <?php
      /*
      if (file_exists('UploadIDToUniprotACC.php')) {   
      echo "UploadIDToUniprotACC.php exists!<br>";                         
      } else {echo "UploadIDToUniprotACC.php does not exist.<br>";}
      */
      
      if (isset($_REQUEST['sub'])){
	      sleep(5);
      }
      if (isset($_REQUEST['uploadedfile'])){
	      sleep(5);
      }
      if (isset($_REQUEST['uploadedxmlfile'])){
	      sleep(5);
      }
   ?>

   <!--This head section encodes a piece of JavaScript that displays a 'please wait' message on submit-->   
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
            }else {
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
            }else{
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
            }else{
	       ele.style.display = "block";
               text.innerHTML = "(collapse)";
            }
         } 
      </script>
   </head>

   <body>
      <h2><font color="#1F88A7">CellWhere BETA PostgreSQL</font> </h2>
      <!-- count the access number of the page -->
      <?php
	 $filename = "hits.txt";
	 $count= file($filename);
	 $count[0]++;
	 $file = fopen ($filename, "w") or die ("Cannot find $filename");
	 fputs($file, "$count[0]");
	 fclose($file);
	 echo "Access times: $count[0]<br/>";
      ?>

      <form enctype="multipart/form-data" action="process.php" method="POST" onSubmit="return showPleaseWait()">
      
      <!-- upload ID-->
      <!--Dropdown menu for ID type-->
      <b><font color="#1F88A7">Select your query ID type:</font> </b><br />
      <select name="ID_type">
	 <option value=""></option>
	 <option value="GeneSymbol">Gene Symbol (e.g. TP53, Dmd)</option>
	 <option value="UniprotACC">Uniprot Accession (e.g. P04637, P11531)</option>
	 <option value="UniprotID">Uniprot ID (e.g. P53_HUMAN, DMD_MOUSE)</option>
	 <option value="Entrez">Entrez Gene ID (e.g. 7157, 13405)</option>
	 <option value="Ensembl">Ensembl ID (e.g. ENSG00000141510, ENSMUSG00000045103)</option>
	 <option value="Blank">Type not listed? We recommend www.uniprot.org/mapping</option>
      </select>
    
      <br /><font color="#1F88A7">Restrict Gene Symbol searches species:</font><br />
      <select name="HumMouseBox">
	 <option value="9606">Homo sapiens</option>
	 <option value="10090">Mus musculus</option>
	 <option value="3702">Arabidopsis thaliana</option>
	 <option value="6239">Caenorhabditis elegans</option>
	 <option value="83333">Escherichia coli K12</option>
	 <option value="7227">Drosophila melanogaster</option>
	 <option value="10116">Rattus norvegicus</option>
	 <option value="559292">Saccharomyces cerevisiae</option>
      </select>
  
      <a id="displayText" href="javascript:toggle();">(more info)</a>
      <div id="toggleText" style="display: none"><b>Querying by Gene Symbol provides for cross-species accumulation of annotations,
					  but can be error prone.</b><br /><font size="2"
					  >Reviewed (i.e. Swiss-Prot curated)
					  Uniprot accessions are retrieved from Uniprot using the search format query=
					  (gene_exact:\"YourGeneSymbol\"). The search is
					  not case-sensitive. For example, querying Dmd or DMD will retrieve all of
					  <a href="http://www.uniprot.org/uniprot/?query=gene%3Admd+AND+reviewed%3Ayes&sort=score">these
					  hits</a>, then subcellular localization will be predicted based on prioritization
					  of their accumulated Uniprot and Gene Ontology annotations.<br />
					  Querying by Gene Symbol is error prone because multiple genes can share the same Symbol.
					  For example, <a href=
					  "http://www.uniprot.org/uniprot/?query=%28gene%3AF10+AND+
					  %28organism%3A9606%29%29+AND+reviewed%3Ayes&sort=score">querying 'F10'</a> will accumulate
					  annotations for both
					  Coagulation factor X and a
					  centrosome/spindle pole-associated protein FAM110A.<br />
					  Uncheck the above box to accumulate annotations for genes matching each Symbol
					  across all species, but be aware that the risk of crossed identifiers is high (e.g. <a href=
					  "http://www.uniprot.org/uniprot/?query=%28gene%3Aart3%29+AND+reviewed%3Ayes&sort=score">
					  querying 'ART3'</a> for all species could prioritize the vesicle localization of the yeast
					  Arrestin-related trafficking adapter 3 rather than the membrane localization of the
					  human ADP-ribosyltransferase.)<br /></font>
					  <font><b>For robust species-specific queries, use one of the other ID types.</b></font><br />
      </div>

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
    
      <!--session name-->
      <br /><br /><b><font color="#1F88A7">Session name:
      <input type="text" id="session_name" name="session_name" style='background-color:#ffffff; border:solid 1px #1F88A7'>
      
    
      <!--Box to paste list of genes-->
      <br /><br /><b><font color="#1F88A7">Paste in a list of IDs (one per line):<br /><!--(expect 3-4 seconds per ID for < 25 IDs and 1-2 seconds per ID for longer lists)--></font>
      <textarea id="text" cols="15" rows="6" name="ID_list" style='background-color:#ffffff; border:solid 1px #1F88A7'></textarea>
      
      <!--Box to upload a gene list file -->
      <!--<input type="hidden" name="MAX_FILE_SIZE" value="10000" />  <!--This is probably redundant with the $maxsize check in process.php, but I've left it just to be safe-->
      <br /><br /><b><font color="#1F88A7">Or upload (plain text, one ID per line):</font> </b><br />
      <input name="uploadedfile" type="file" style='background-color:#ffffff; border:solid 1px #1F88A7'/>  
   
       <!--Dropdown menu for mentha network-->
      <b><font color="#1F88A7"><br /><br />Mentha network:</font> </b><br />
      <select name="mentha_add">
	 <option value=0>Only queries</option>
	 <option value=1>Add relative proteins</option>
      </select> 
 
      <!--Submit and reset buttons -->
      <br /><br /><input id="butt" name="sub" type="submit" value="Submit" /><input type="reset" value="Reset" />
      </form>
      <br /><br />
    
      <!--separator -->
      <hr color="#1F88A7" >
	 
      <!-- upload gxmml file>
      <!--Box to upload a gxxml file -->
      <br /><b><font color="#1F88A7">Upload a xgmml formated network(genertated by Cytocape 3.02):</font> </b><br/>
    
      <form enctype="multipart/form-data" action="xml_attributes.php" method="POST" onSubmit="return true">
	 <input name="uploadedxmlfile" type="file" style='background-color:#ffffff; border:solid 1px #1F88A7' />
	 <a href="download_xml.php">(download xgmml example)</a><br/> 
	 <!--Submit and reset buttons -->
	 <br /><br /><input id="butt_2" name="next_2" type="submit" value="Next" /><input type="reset" value="Reset" />
      </form><br />

      <div id="msgDiv"></div>
   </body>
</html>


<?php
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// **Very** basic password protection: part 2 (see part 2 at end of index.php //////////////////////////////////////////////////////////
// Taken from: http://stackoverflow.com/questions/4115719/easy-way-to-password-protect-php-page. ///////////////////////////////////////
/// ALL OF THE FOLLOWING, TO THE END OF THE PAGE, IS REQUIRED //////////////////////////////////////////////////////////////////////////
      exit;
   } else {
      echo "The cookie is bad: maybe clear this page from your cookie cache and try again?";
      exit;
   }
}

if (isset($_GET['p']) && $_GET['p'] == "login") {
   if ($_POST['user'] != $username) {
      echo "Sorry, that username does not match.";
      exit;
   } else if ($_POST['keypass'] != $password) {
      echo "Sorry, that password does not match.";
      exit;
   } else if ($_POST['user'] == $username && $_POST['keypass'] == $password) {
      setcookie('PrivatePageLogin', md5($_POST['keypass'].$nonsense));
      header("Location: $_SERVER[PHP_SELF]");
   } else {
      echo "Sorry, you could not be logged in at this time.";
   }
}
?>


<form action="<?php echo $_SERVER['PHP_SELF']; ?>?p=login" method="post">
   <label><input type="text" name="user" id="user" /> Name</label><br />
   <label><input type="password" name="keypass" id="keypass" /> Password</label><br />
   <input type="submit" id="submit" value="Login" />
</form>