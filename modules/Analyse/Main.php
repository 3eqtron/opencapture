<?php 

    require 'function/CheckFile.php';
    // AutoLoader ?? 
   
    $chemin = getcwd();
    $chemin = realpath(__DIR__."/../..");
    echo "Chemin courant : " .$chemin.PHP_EOL.PHP_EOL;
    //chdir("../..");
    //echo realpath(__DIR__ )."dsdd";
    //fichier XML
    $mainFile = 'Capture.xml';
    //$fileCapture = 'MailCapture.xml';

    // Directory
    $mainDirectory= "./config/";
    $directory = "./modules/";

    $ressource = file_get_contents($chemin."/modules/analyse/data/ressourceCourrier.json");

       
    $moduleList = array('FileImport','MaarchWSClient','MailCapture');
    //récupere le  fichier xml qui se trouve dans leur dossier modules
    $fichiersPHP= array('FileImport.php','MaarchWSClient.php','MailCapture.php');
    $fichiersXML= array( 'FileImport.xml','MaarchWSClient.xml','MailCapture.xml');

    $allXML= array( 
        'FileImport'        => array(
            "file" => "FileImport.xml",
            "directory" => "FileImport/"
        ),
        'MaarchWSClient'    => array(
            "file" => "MaarchWSClient.xml",
            "directory" => "MaarchWSClient/"
        ),
        'MailCapture'       => array(
            "file" => "MailCapture.xml",
            "directory" => "MailCapture/"
        )
    );

    $arrayXml = array();

    //recherche des fichier dans les differents repertoire
    $tabXML = rechercher($chemin,"*","xml");
    
    
    //fichier XML
    $mainFile = 'Capture.xml';

    // Directory
    $mainDirectory= $chemin."/config/";


    $tabError= array();

    //Si on trouve pas de fichier à la racine du dossier
    if(count($tabXML) <= 0) {

        
       
       //$fileCapture = 'MailCapture.xml';      
       $directory = $chemin."/modules/";



        $compteur = 0;
        foreach($moduleList as $module) {
            if(checkerFile( $directory.$moduleList[$compteur ]."/".$fichiersPHP[$compteur])) {
                echo "le fichier " . $fichiersPHP[$compteur ] ." existe : chemin = ".$directory.$moduleList[$compteur ]."/".$fichiersPHP[0].PHP_EOL;
            } else {
                echo "le fichier " . $fichiersPHP[$compteur ] ." n'existe pas : chemin : ".$directory.$moduleList[$compteur ]."/".$fichiersPHP[0].PHP_EOL;
            };
            
            $compteur++;
        }
        
         
        echo PHP_EOL;
        if(checkerFile( $mainDirectory.$mainFile)) {
            $capture = $mainDirectory.$mainFile;
            echo "le fichier ". $mainFile ." existe chemin = ".$capture;
            
        } else {
            echo "le fichier ". $mainFile ." n'existe pas";
        }

        echo PHP_EOL.PHP_EOL;
        $compteur = 0;
        foreach($moduleList as $module) {
            if(checkerFile( $directory.$moduleList[$compteur ]."/".$fichiersXML[$compteur])) {
                echo "le fichier " . $fichiersXML[$compteur ] ." existe : chemin = ".$directory.$moduleList[$compteur ]."/".$fichiersXML[$compteur].PHP_EOL;
                if($compteur == 1) $leWSXml =  $directory.$moduleList[1 ]."/".$fichiersXML[1];
                if($compteur == 2) $leXML =  $directory.$moduleList[2 ]."/".$fichiersXML[2];
            } else {
                echo "le fichier " . $fichiersXML[$compteur ] ." n'existe pas : chemin : ".$directory.$moduleList[$compteur ]."/".$fichiersXML[$compteur].PHP_EOL;
            };
            
            $compteur++;
        }
        echo PHP_EOL;

           
 
      

    }else{
        
        $cpt=0;
        
        $leXML = $chemin."/".$allXML['MailCapture']['file'];
        $leWSXml = $chemin."/".$allXML['MaarchWSClient']['file'];
        $capture =  $chemin."/".$mainFile;

        if(!checkerFile($leXML)){
            $cpt+=1;
            array_push($tabError, "Le fichier ".$leXML." n'a pas été trouvé");
        }
        if(!checkerFile($WSXml)){
            array_push($tabError,  "Le fichier ".$leWSXml." n'a pas été trouvé");
            $cpt+=1;
        }
        if(!checkerFile($capture)){
            array_push($tabError,  "Le fichier ".$capture." n'a pas été trouvé");
            $cpt+=1;
        }

    }

    if(count($tabError) > 0){
        foreach($tabError as $error) {
            echo $error.PHP_EOL;
        }
        exit;
    }
    
    //Si on met 3 fichiers à la racie du dossier, il faut vérifier si il y a bien 3 fichiersXML


    
   





    //ouverture d'un flux Capture pour recupre des informations
    $dom = new DOMDocument;
    $dom->loadXML(file_get_contents($capture));
    $captureXml = simplexml_load_string( $dom->saveXML());
  
    //ouverture d'un flux Mailcapture pour recupre des informations
    $doc = new DOMDocument;                        
    $doc->loadXML(file_get_contents( $leXML));
    $MailcaptureXml = simplexml_load_string( $doc->saveXML());
    
    //Ouverture du flux MaarchWSXml
    $docXml = new DOMDocument;                        
    $docXml->loadXML(file_get_contents( $leWSXml));
    $WSXml = simplexml_load_string( $docXml->saveXML());

  
    //check imap
    $imap = $MailcaptureXml->accounts[0]->account;
    $mbox = imap_open($imap->mailbox, $imap->username, $imap->password);
    
    if ($mbox == false) {

        $numero = dom_import_simplexml($MailcaptureXml->accounts);
        echo "Il y a une erreur de parametrage dans mon IMAP ".PHP_EOL."Vérifier vos paramètres dans le fichier ".$leXML.PHP_EOL."Ligne ." .$numero->getLineNo().", la balise '".$MailcaptureXml->accounts->getName().PHP_EOL;
    } else {
        echo "La configuration IMAP est ok".PHP_EOL;
    }
 
    imap_close($mbox);

    echo PHP_EOL;
    
    //Valeur à 1 car on ne compte pas le step importfile
    $i = 1;
    $cptStep=0;

    //Capture 
    $steps = $dom->getElementsByTagName("step");
  
    foreach($steps as $step) {

      

        $configFile = false;
        
        if((string)$step->getAttribute("module") != $moduleList[0]){
           
            echo PHP_EOL.PHP_EOL."####### Step : ".(string)$step->getAttribute("name")  ." #######".PHP_EOL.PHP_EOL;
            $inputs = $step->getElementsByTagName("input");

            //on recupere toutes les balises input du step en cours
            foreach($inputs as $input) {

                if($input->getAttribute("name")  == "configFile") {
                    $configFile = true;
                    $numero = $input->getLineNo();
                    $value = $input->nodeValue;
                }
          
            }

            
            $checkL = false;
             //Parcours nom des modules 
             foreach($allXML as $fileXml ) {
                //Verifie si la valuer correspond a la valeur file
                if($value == $fileXml['file']) {
                    $checkL = true;
                }
               
            }


             // si on trouve l'attribut configFile est vrai alors on peu acceder au fichier xml
            if($configFile == false) {
                echo "La balise configFile n'a pas été trouvé".PHP_EOL;;
            }elseif($checkL == false) {
                
                echo "La valeur ($value) de la balise configFile ligne ".$numero." du fichier ". $mainFile. "ne correspondant au nom du fichier xml (chemin : ".$allXML[$step->getAttribute("module")]['directory'].$allXML[$step->getAttribute("module")]['file'].")".PHP_EOL.PHP_EOL;
            }else{

                $boolConfigFile = false;
                

                //Parcours nom des modules 
                foreach($allXML as $fileXml ) {
                    //Verifie si la valuer correspond a la valeur file
                    if($value == $fileXml['file']) {
                        $boolConfigFile = true;
                        //$xml = $directory.$allXML[$step->getAttribute("module")]['directory'].$allXML[$step->getAttribute("module")]['file'];
                     
              
                        //test des informations webservice 
                        if($step->getAttribute("module") == $moduleList[1]) {

                            $maressource = json_decode($ressource, true);
                            
                            $linkRest = $WSXml->WS[0]["uri"];
                         
                            //verification de l'existence de la balise MaarchRestWS
                            if($WSXml->WS["name"]) {
                                
                                $maressource = json_decode($ressource, true);                              
                                $item = null;
                                $boolWS = false;
                                
                                foreach($maressource as $struct) {

                                    if($inputs[$cptStep]->nodeValue == $struct['name']){


                                        
                                        $boolWS = curlWS($linkRest."currentUser/profile");   
                                        if($boolWS == false) {
                                            echo "Il y a un probleme d'identification".PHP_EOL."vérifier vos parametres dans le fichier ".$allXML[$step->getAttribute("module")]['directory'].$allXML[$step->getAttribute("module")]['file'].PHP_EOL; 
                                        }else{
                                            echo "La configuration Webservice est ok";
                                        }
                                        
                                    }
                                }

                                if($boolWS == false){

                                    //$element = new SimpleXMLElement($xml);
                                    $numero = dom_import_simplexml($WSXml->WS);
                                    
                                    // la valeur de la balise .... ne correspond pas avec celle du fichier  ...
                                    echo "La valeur ".$inputs[$cptStep]->nodeValue ." Ligne.".$inputs[$cptStep]->getLineNo()." du fichier ".$mainFile ." ne correspond à la valeur ".$WSXml->WS["name"]." Ligne ".$numero->getLineNo()." du fichier  ".$allXML['MaarchWSClient']['file'].PHP_EOL;
                                }
                            }else{
                                echo "Nous ne trouvons pas la balise ".$WSXml->WS->getName().PHP_EOL;
                                exit();
                              
                            }
    
                            if($boolWS == true ){ //$WSXml->process
                               
                                foreach($WSXml->process as $process) {
                                    echo PHP_EOL.PHP_EOL."Name : ". $process["name"].PHP_EOL;

                                    $listArguments = $process->loop->call->argument;

                                    foreach($listArguments as $argument) {
                                                                                
                                        foreach($maressource as $key => $laressource) {
                                               
                                                if($laressource["name"] == $argument[0]["name"] && $laressource["WS"]== 1 ){
                                                   
                                                    if($process["name"]== "MaarchRestWSProcessFromMail" && $argument[0]["metadata"] == $laressource["metadata"] &&  $argument[0]["metadata"] !=NULL ) {
                                                   
                                                     
                                                        
                                                        $messageoutputs = $MailcaptureXml->messageoutputs->messageoutput;
                                                      

                                                        foreach($messageoutputs as  $messageoutput) {
                                                           
                                                            if($messageoutput["name"] == $laressource["metadata"] ) {

                                                                                                                                
                                                                $retour = curlWS($linkRest.$laressource["route"].$messageoutput[0]);                                                 
                                                                echo PHP_EOL;
                                                                $numero = dom_import_simplexml($messageoutput);
                                                                if( $retour == false) {                                                                   
                                                                    echo("Ligne ." .$numero->getLineNo().", la balise  ".$messageoutput["name"]. " dans le fichier ".$leXML);       
                                                                }else{
                                                                    echo "Ligne ." .$numero->getLineNo(). " Balise ".$messageoutput["name"]." ok.";
                                                                    
                                                                }
                                                            }
                                                        }

                                                    }else{
                                                       
                                                        //si on tombe sur statuses
                                                        if($argument[0]["name"]== "status") {
                                                            $bool = curlWSInfo($linkRest.$laressource["route"],$argument[0]);                                                 
                                                            
                                                            echo PHP_EOL;
                                                            $numero = dom_import_simplexml($argument[0]);
                                                            if($bool==true){                                                                  
                                                                echo "Ligne ." .$numero->getLineNo(). " Balise ".$argument[0]["name"]." ok.";
                                                            } else {
                                                                echo("Ligne " .$numero->getLineNo().", probleme ".$argument[0]["name"]. " dans le fichier ".$leWSXml); 
                                                            }
                                                        }else {
                                                            
                                                            $retour = curlWS($linkRest.$laressource["route"].$argument[0]); 
                                                            $numero = dom_import_simplexml($argument);
                                                            echo PHP_EOL;
                                                            if($retour == false){
                                                                
                                                                echo("Ligne ." .$numero->getLineNo().", probleme balise  ".$argument[0]["name"] . " dans le fichier ".$leWSXml);    
                                                            }else{
                                                              
                                                                echo "Ligne ." .$numero->getLineNo(). " balise ".$argument[0]["name"]." ok.";
                                                                
                                                            }
                                                        }
                                                        
                                                        
                                                    }   
                                                    
                                                }
                                                
                                        }
                                            
                                            
                                        
                                    }
                                }
                                
                            }

                        } 
                        
                        
                    }
                    
                }
                
                
               
            
            }

            $i++;
        }

        
    }
