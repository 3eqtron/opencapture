<?php

class Export 
{
    
    function ExportXML(
        $ContentPath,
        $ExportDirectory,
        $IncludeAncestors=false,
        $FileNameArray=array()
    ) {
        
        $Batch = $_SESSION['capture']->Batch;
        
        $BatchId = $Batch->id;

        /********************************************************************************
        ** Loop on Batch Elements
        ********************************************************************************/
        $Elements = $Batch->query($ContentPath);
        
        $l = $Elements->length;
        for($i=0; $i<$l; $i++) {
            $Element = $Elements->item($i);
            $id = $Element->id;
            
            $ExportXML = new DOMDocument('1.0', 'UTF-8');
            $Root = $ExportXML->createElement('ROOT');
            $ExportXML->appendChild($Root);
            
            /********************************************************************************
            ** Element Metadata with ancestors
            ********************************************************************************/
            $query = "./Metadata/*";
            if($IncludeAncestors)
                $query .= " | ./ancestor::*/Metadata/*";
                
            $Metadatas = 
                $Batch->query(
                    $query,
                    $Element
                );
            $m = $Metadatas->length;
            for($j=0; $j<$m; $j++) {
                $Metadata = $Metadatas->item($j);
                $Index = $ExportXML->importNode($Metadata, true);
                $Root->appendChild($Index);
            }
            
            $FileName = false;
            if(count($FileNameArray) == 0)
                $FileName = $BatchId . '_' . $id;
            else {
                foreach($FileNameArray as $FileNameItem) {
                    $init = substr($FileNameItem, 0, 1);  
                    if($init == '.' || $init == '/') {
                        if($ItemNode = 
                            @$Batch->query(
                                $FileNameItem,
                                $Element
                            )->item(0)
                        ) 
                            $FileName .= $ItemNode->nodeValue;
                    } else if($init == '@' && strlen($FileNameItem) > 1) {
                        $FileName .= $Element->getAttribute(substr($FileNameItem, 1));
                    } else 
                        $FileName .= $FileNameItem;
                }
                $FileName = preg_replace('#\\\/:\*\?"<>\|#', '_', $FileName);
            }
            
            $XMLPath = $ExportDirectory . DIRECTORY_SEPARATOR . $FileName . '.xml';
            
            if(!$ExportXML->save($XMLPath))
                $_SESSION['capture']->sendError("Error when exporting XML for document");
            
            /********************************************************************************
            ** Copy PDF
            ********************************************************************************/
            if(!is_file($Element->path)) {
                $_SESSION['capture']->logEvent(
                    "No source file to export for element $id",
                    2
                );
            } else {
                $ExportPath = 
                    $ExportDirectory . DIRECTORY_SEPARATOR 
                    . $FileName . '.' . $Element->extension;
                copy($Element->path, $ExportPath);
            }
        }

    }
	
	function ExportCSV(
        $ContentPath,
        $ExportDirectory,
        $IncludeAncestors=false,
        $FileNameArray=array()
    ) {
        $Batch = $_SESSION['capture']->Batch;
        
        $BatchId = $Batch->id;

        /********************************************************************************
        ** Loop on Batch Elements
        ********************************************************************************/
        $Elements = $Batch->query($ContentPath);
        
        $l = $Elements->length;
        for($i=0; $i<$l; $i++) {
            $Element = $Elements->item($i);
            $id = $Element->id;
            
            $ExportCSV = fopen($ExportDirectory . DIRECTORY_SEPARATOR .  $Batch->id . '.txt', "a+");
            $index = array();
            /********************************************************************************
            ** Element Metadata with ancestors
            ********************************************************************************/
            $query = "./Metadata/*";
            if($IncludeAncestors)
                $query .= " | ./ancestor::*/Metadata/*";
                
            $Metadatas = 
                $Batch->query(
                    $query,
                    $Element
                );
            $m = $Metadatas->length;
            for($j=0; $j<$m; $j++) {
                $Metadata = $Metadatas->item($j);
                $index[] = $Metadata->nodeValue;
            }
            
			fputcsv($ExportCSV, $index, ";", '"');
			
            fclose($ExportCSV);
        }

    }

}

?>