<?php 


        function checkerFile($filename) {
            $bool = file_exists($filename) ? true : false;
            return $bool;
        }

        function getFile ($file){



            return $file;
        }

        function errorFile ($file,$tabError){

            if(!checkerFile($file)){
                array_push($tabError, "Le fichier ".$file." n'a pas été trouvé");
            }
            return $tabError;
        }


        function getFlux($file) {
               //ouverture d'un flux Capture pour recupre des informations
            $dom = new DOMDocument;
            $dom->loadXML(file_get_contents($file));
            $theFlux = simplexml_load_string( $dom->saveXML());

            return $theFlux;
        }
        

        function curlWS($curlLink) {
            
            $curl = curl_init($curlLink);
            curl_setopt($curl, CURLOPT_HTTPHEADER,array('Accept: application/json'));
            curl_setopt($curl, CURLOPT_RETURNTRANSFER,TRUE);
            $vars=curl_exec($curl);
            if(!curl_errno($curl)){
                switch($http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE)) {
                    case 200 :
                        $boolWS = true;
                        break;
                    default:
                    $boolWS = false;
                }
            }

            return $boolWS;
        }


        function curlWSInfo($curlLink,$argument) {
            $bool = false;
            $curl = curl_init($curlLink);
            curl_setopt($curl, CURLOPT_HTTPHEADER,array('Accept: application/json'));
            curl_setopt($curl, CURLOPT_RETURNTRANSFER,TRUE);
            $retour=curl_exec($curl);   
            $elt = json_decode($retour,true); 
            foreach($elt["statuses"] as $statuses) {

                if($statuses["id"] == $argument){
                    $bool = true;
                    break;
                } 

            }
            return $bool;
        }

        function checkerDir($filename) {
            
            $bool = is_dir($filename) ? true : false;
            
            return $bool;
        }

    


        function accept($sFileInfo,$ext)
        {
            return (preg_match('#\.'.$ext.'$#', $sFileInfo));
        }


        function rechercher($racine,$nomfichier = null, $extension = null)
        {
            $tabXML =array();
            $dp = opendir($racine);$i = 0;
            while($entree = readdir($dp)) {                
                if(is_file("$racine/$entree") && accept($entree,$extension))
                {
                     array_push($tabXML, $racine."/".$entree);
                }
            }

            return $tabXML;
        }
        
    