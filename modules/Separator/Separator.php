<?php

class Separator
{ 
    private $Batch;
	private $BatchXPath;
       
    function Separate(
        $Files,
        $method
    ) {
        $this->Batch = $_SESSION['capture']->Batch;
        $this->Workflow = $_SESSION['capture']->Workflow;
        $this->Step = $_SESSION['capture']->Step;
        
        $l = $Files->length;
        
        switch($method) {
        case 'pages':
            $num_pages = func_get_arg(3);
            for($i=0; $i<$l; $i++) {
                $this->SeparatePages(
                    $Files->item($i),
                    $num_pages
                );
            }
            break;
        case 'metadata':
            $argn = 3;
            $metadata = array();
            while($arg = @func_get_arg($argn)) {
                $metadata[] = $arg;
            }
            for($i=0; $i<$l; $i++) {
                $this->SeparateMetadata(
                    $Files->item($i),
                    $metadata
                );
            }
        }

    }
    
    function SeparatePages(
        $File,
        $doc_pages
    ) {	
		$BatchDirectory = $this->Batch->directory;
        
        if($PagesRoot = $File->getContainer('Pages') {
            $Resources = 
                $PagesRoot->getElementsByTagName('Resources')->item(0);
        }

        try {
            $pdi = $this->open_pdi_document($File->path, "repair=force");
            
            $num_pages = $this->pcos_get_number($pdi, "length:pages");
            $num_doc = 0;    
            
            for($pi=0; $pi<$num_pages; $pi++) {
                $pn = $pi+1;
                $w = $this->pcos_get_number($pdi, "pages[".$pi."]/width");
                $h = $this->pcos_get_number($pdi, "pages[".$pi."]/height");
                
                if($pi === 0 || ($pi % $doc_pages) === 0) {
                    if($pi > 0 && $Document) {
                        $this->end_document("");
                        $Document->importResource(
                            $outpdf,
                            'pdf',
                            'application/pdf'
                        );
                    }
                
                    $Document = $File->addDocument();
                    $outpdf = 
                        $BatchDirectory . DIRECTORY_SEPARATOR 
                            . uniqid() . ".pdf";
                    $this->begin_document($outpdf, "");        
                }   
                
                $this->begin_page_ext($w, $h, "");
                $pdi_page = $this->open_pdi_page($pdi, $pn, '');
                $this->fit_pdi_page($pdi_page, 0, 0, "");
                $this->end_page_ext(""); 
                
            }
            $this->end_document("");
            $Document->importResource(
                $outpdf,
                'pdf',
                'application/pdf'
            );
            unlink($outpdf);
        
        } catch (\PDFlibException $e) {
            print "PDFlib exception occurred:\n";
            print "[" . $e->get_errnum() . "] " . $e->get_apiname() . ": " . $e->get_errmsg() . "\n";
        } catch (\Exception $e) {
            print $e;
        }   
	}
    
    function SeparateMetadata(
        $File,
        $metadatas
    ) {	
		$BatchDirectory = $this->Batch->directory;
        
        try {
            $pdi = $this->open_pdi_document($File->path, "repair=force");
            
            $num_pages = $this->pcos_get_number($pdi, "length:pages");
            $num_doc = 0;    
            $page_keys = array();
            
            for($pi=0; $pi<$num_pages; $pi++) {
                $pn = $pi+1;
                $w = $this->pcos_get_number($pdi, "pages[".$pi."]/width");
                $h = $this->pcos_get_number($pdi, "pages[".$pi."]/height");
                
                $index = array;
                foreach($metadatas as $name) {
                    $index[$name] =  $File->getMetadata($name);
                }
                $page_keys[$pn] = md5(print_r($index, true));
                
                if($pi === 0 || $page_keys[$pn] != $page_keys[$pi]) {
                    if($pi > 0 && $Document) {
                        $this->end_document("");
                        $Document->importResource(
                            $outpdf,
                            'pdf',
                            'application/pdf'
                        );
                    }
                
                    $Document = $File->addDocument();
                    $outpdf = 
                        $BatchDirectory . DIRECTORY_SEPARATOR 
                            . uniqid() . ".pdf";
                    $this->begin_document($outpdf, "");        
                }   
                
                $this->begin_page_ext($w, $h, "");
                $pdi_page = $this->open_pdi_page($pdi, $pn, '');
                $this->fit_pdi_page($pdi_page, 0, 0, "");
                $this->end_page_ext(""); 
                
            }
            $this->end_document("");
            $Document->importResource(
                $outpdf,
                'pdf',
                'application/pdf'
            );
            unlink($outpdf);
        
        } catch (\PDFlibException $e) {
            print "PDFlib exception occurred:\n";
            print "[" . $e->get_errnum() . "] " . $e->get_apiname() . ": " . $e->get_errmsg() . "\n";
        } catch (\Exception $e) {
            print $e;
        }   
	}
    
        
}
