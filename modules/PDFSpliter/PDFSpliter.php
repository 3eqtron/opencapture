<?

class PDFSpliter
	extends PDFLib
{
    private $Batch;
    private $pageIndex;
    private $optlists;
    
    function __construct() {
        $this->Batch = $_SESSION['capture']->Batch;
        parent::__construct();
    }
    
    function load_optlists(
        $optlists=array()
    ) {
        if(count($optlists) === 0) return;
        foreach($optlists as $type => $name) {
            $this->load_optlist($type, $name);
        }
    }
    
    function load_optlist(
        $type,
        $name
    ) {
        $optlistfile =
            __DIR__ . DIRECTORY_SEPARATOR 
                . 'optlists' . DIRECTORY_SEPARATOR
                . $name . '.' . $type;
                
        if(is_file($optlistfile))
            $this->optlists[$type] = 
                file_get_contents($optlistfile);
    }
    
    function get_optlist(
        $type
    ) {
        if(!isset($this->optlists[$type]))
            $this->load_optlist($type, 'default');
        
        return (string)$this->optlists[$type];

    }

    function Split(
        $ContentPath,
        $MetadataNames,
        $optlists=array()
    ) {         
        $this->load_optlists($optlists);
        
        $Files = $this->Batch->query($ContentPath);
        $l = $Files->length;
        for($i=0; $i<$l; $i++) {
            $this->SplitFile(
                $Files->item($i),
                $MetadataNames
            );
        }   

    }
    
    function SplitFile(
        $File,
        $MetadataNames
    ) {	
		$Batch = $this->Batch;
        
        $BatchDirectory = $Batch->directory;
        
        try {
            // Open file as new PDI document
            $pdi = $this->open_pdi_document($File->path, $this->get_optlist('open_pdi_document'));
            if (!$pdi)
                $_SESSION['capture']->sendError(
                    "ERROR: ". $this->get_errnum() ." in ". $this->get_apiname() . "(): " . $this->get_errmsg()
                );
            
            $FileImages = $File->getContainer('Images');
            $FileFonts = $File->getContainer('Fonts');
            
            // Loop on pages, calc index and break on value
            $Document = false;
            $PageIndex = array();
            $Pages = $File->getPages();
            $epl = $Pages->length;
            for($epi=0; $epi<$epl; $epi++) {               
                $epn = $epi+1;
                $Page = $Pages->item($epi);
                
                $w = $this->pcos_get_number($pdi, "pages[".$epi."]/width");
                $h = $this->pcos_get_number($pdi, "pages[".$epi."]/height");
                
                // Retrieve Page metadatas for document break 
                $MetadataArray = array();
                foreach($MetadataNames as $MetadataName) {
                    $MetadataArray[$MetadataName] = $Page->getMetadata($MetadataName);
                }
                $PageMetadataArray[$epn] = $MetadataArray;
                
                // If new document to be added
                if($epi === 0 || $PageMetadataArray[$epn] != $PageMetadataArray[$epi]) {
                    // End previous document (pdf & xml), reset document page number
                    if($epi > 0 && $Document) {
                        $this->end_document("");
                        $Document->importResource(
                            $outpdf
                        );
                    }
                    
                    
                    // Start new batch document
                    $Document = $File->addDocument();
                    $Document->setAttribute('OCR', true);
                    $this->importDocInfo(
                        $File,
                        $Document
                    );
                    
                    // begin new PDF
                    $outpdf = 
                        $BatchDirectory . DIRECTORY_SEPARATOR 
                            . $Document->id . ".pdf";
                    $this->begin_document($outpdf, $this->get_optlist('begin_document'));   
                    
                    // reset page number
                    $dpn = 1;
                    
                    // Import splited Page Metadata
                    foreach($MetadataArray as $MetadataName => $MetadataValue) {
                        $Document->setMetadata(
                            $MetadataName,
                            $MetadataValue
                        );
                    }
                    
                    if($FileFonts)
                        $Document->appendChild($FileFonts->cloneNode(true));
                    if($FileImages)
                        $Document->appendChild($FileImages->cloneNode(true));
                    
                    
                }   
                // Append pages to current document (pdf & xml), set document page number
                $this->begin_page_ext($w, $h, "");
                $pdi_page = $this->open_pdi_page($pdi, $epn, '');
                $this->fit_pdi_page($pdi_page, 0, 0, "");
                $this->end_page_ext(""); 

                $Document->appendPage($Page);
                $Page->setAttribute('number', $dpn);
                $dpn++;
                
            }
            $this->end_document("");
            $Document->importResource($outpdf);
            //unlink($outpdf);
        
        } catch (PDFlibException $e) {
            $_SESSION['capture']->logEvent(
                "PDFLib exception occurred during split: " 
                    . "[" . $e->get_errnum() . "] " 
                    . $e->get_apiname() . ": " 
                    . $e->get_errmsg(),
                3
            );
        } 
	}
    
    function importDocInfo(
        $File,
        $Document
    ) {
        $DocInfoNames = 
            array(
                "Author",
                "Title", 
                "Subject",
                "Creator"
            );
            
        foreach($DocInfoNames as $DocInfoName) {
            if($DocInfo = $File->getMetadata($DocInfoName))
                $this->set_info($DocInfoName, $DocInfo);
        }
    
    }
        
}