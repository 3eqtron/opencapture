<?php

require_once __DIR__ . DIRECTORY_SEPARATOR 
    . 'fpdf' . DIRECTORY_SEPARATOR . 'fpdf.php';
require_once __DIR__ . DIRECTORY_SEPARATOR 
    . 'fpdi' . DIRECTORY_SEPARATOR . 'fpdi.php';
    
class SpiData
{
    
    private $Batch;
    private $SpiXPath;
    
    function __construct()
    {
        $this->Batch = $_SESSION['capture']->Batch;
    }
    
    public function loadSpiData(
        $SpiDataXml,
        $action,
        $saveDirectory = false
    ) {       
        $this->action = $action;
        $this->saveDirectory = $saveDirectory;
        
        $_SESSION['capture']->logEvent(
            "Processing SpiData xml file '$SpiDataXml'"
        );
         
        /* get mimetype
        ********************************************************************************/
        if($finfo = new finfo()) {
            $mimetype = $finfo->file($SpiDataXml, FILEINFO_MIME_TYPE);
            $mimeencoding = $finfo->file($SpiDataXml, FILEINFO_MIME_ENCODING);
        } else {
            $_SESSION['capture']->logEvent(
                "Unable to get file info, check php ini file extensions", 2
            );
            trigger_error(
                "Unable to get file info, check php ini file extensions",
                E_USER_ERROR
            );
        }

        if($mimetype != 'application/xml') {
            $this->discard($SpiDataXml);
            continue;
        }
            
        // Load Batch
        $SpiData = new DOMDocument();
        $SpiData->load($SpiDataXml);
        
        $this->SpiDataXPath = new DOMXPath($SpiData);
        
        $SpiDataDocuments = $this->SpiDataXPath->query('/batch/document');
        for($i=0, $l=$SpiDataDocuments->length;
            $i<$l;
            $i++
        ) {
            $this->loadSpiDataDocument(
                $SpiDataDocuments->item($i)
            );
        }    
                    
        $this->discard($SpiDataXml);

    }
    
    private function loadSpiDataDocument(
        $SpiDataDocument
    ) {
        
        $Document = $this->Batch->addDocument();
        
        $SpiDataContents = $this->SpiDataXPath->query('./fields/*', $SpiDataDocument);
        for($i=0, $l=$SpiDataContents->length;
            $i<$l;
            $i++
        ) {
            $SpiDataContent = $SpiDataContents->item($i);
            $Metadata = $this->Batch->importNode($SpiDataContent, true);
            $Document->appendContent($Metadata, 'Metadata');
        }
        
        $SpiDataPdf = $this->SpiDataXPath->query('./docfile/pdf_file', $SpiDataDocument)->item(0);
        if(!$SpiDataPdf || !is_file($SpiDataPdf->nodeValue))
            return;
        
        $SpiDataPdfFile = $SpiDataPdf->nodeValue;
        
        $attachmentstart = $SpiDataDocument->getAttribute('attachmentstart');
        if($attachmentstart > 0) {
            $this->splitAndImport($SpiDataPdfFile, $attachmentstart, $Document);
        } else {     
            $Document->importResource($SpiDataPdfFile);
        }
        
        # Ajout des notes
        $SpiDataNotes = $this->SpiDataXPath->query('./notes/notes[text()]', $SpiDataDocument);
        for($i=0, $l=$SpiDataNotes->length;
            $i<$l;
            $i++
        ) {
            $SpiDataNote = $SpiDataNotes->item($i);
            $Note = $Document->add('Note', $SpiDataNote->nodeValue);
        }

    }
    
    private function splitAndImport(
        $SpiDataPdfFile,
        $attachmentstart,
        $Document
    ) {
        # Extract main document
        $fpdiDocument = new fpdi();
        $pagecount = $fpdiDocument->setSourceFile($SpiDataPdfFile);
        for($i=1; $i<$attachmentstart; $i++) {
			$documentPage = $fpdiDocument->importPage($i);
			$size = $fpdiDocument->getTemplateSize($documentPage);
            $fpdiDocument->AddPage('P', array(round($size['w']), round($size['h'])));
            $fpdiDocument->useTemplate($documentPage, 0, 0, $size['w'], $size['h'], $adjustPageSize=true);
		}
        $DocumentID = $this->Batch->getNextId('D');
        $DocumentPath = 
            $this->Batch->directory . DIRECTORY_SEPARATOR
                . $DocumentID . '.pdf';
        $fpdiDocument->Output($DocumentPath);
        $Document->importResource($DocumentPath);
                
        # Extract attachment
        $fpdiAttachment = new fpdi();
        $pagecount = $fpdiAttachment->setSourceFile($SpiDataPdfFile);
        for($cptAtt=$attachmentstart; $cptAtt<=$pagecount; $cptAtt++) {
            $attachmentPage = $fpdiAttachment->importPage($cptAtt);
            $size = $fpdiAttachment->getTemplateSize($attachmentPage);
            $fpdiAttachment->AddPage('P', array(round($size['w']), round($size['h'])));
            $fpdiAttachment->useTemplate($attachmentPage, 0, 0, $size['w'], $size['h'], $adjustPageSize=true);
        }
        $AttachmentID = $this->Batch->getNextId('A');
        $AttachmentPath = 
            $this->Batch->directory . DIRECTORY_SEPARATOR
                . $AttachmentID . '.pdf';
        $fpdiAttachment->Output($AttachmentPath);
        $Document->addAttachment($AttachmentPath);
    }

    private function discard(
        $SpiDataXml
    ) {
        /********************************************************************************
        ** Original File action
        ********************************************************************************/			
        switch ($this->Action) {
        case 'move':
            $_SESSION['capture']->logEvent(
                "Moving imported SpiData file to directory $MoveDirectory"
            );
            rename($SpiDataXml, $this->saveDirectory . DIRECTORY_SEPARATOR . basename($SpiDataXml));
            break;
            
        case 'delete':
            $_SESSION['capture']->logEvent(
                "Deleting imported SpiData file"
            );
            unlink($SpiDataXml);
            break;
        
        case 'none':
        default:
            // Nothing
        }

    }

}