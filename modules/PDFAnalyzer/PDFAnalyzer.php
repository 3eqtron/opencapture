<?php

class PDFAnalyzer
	extends PDFLib
{
    private $optlists;
    
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
    
    function getContentPath(
        $Content
    ) {
        $ContentFullPath = $Content->getNodePath();       
        return str_replace($this->rootPath . '/Pages', '', $ContentFullPath);
    }
    
    public function Analyze(
        $Elements,
        $optlists=array(),
        $OutputDirectory
    ) {
        $this->Batch = $_SESSION['capture']->Batch;
        $this->Workflow = $_SESSION['capture']->Workflow;
        $this->Step = $_SESSION['capture']->Step;
        
        $this->OutputDirectory = $OutputDirectory;
        
        $this->load_optlists($optlists);
        
        $l = $Elements->length; 
        $_SESSION['capture']->logEvent("$l elements to proceed");      
        for($i=0; $i<$l; $i++) {
            $this->Element = $Elements->item($i);
            $this->AnalyzeElement();
        }
    }

    private function AnalyzeElement() 
    {
        $Element = $this->Element;
        $OutputDirectory = $this->OutputDirectory;
        $this->rootPath = $Element->getNodePath();
        $this->Words = array();
        
        try {
            // Open file as new PDI document
            if($Element->extension == 'pdf' || $Element->mimetype == 'application/pdf') {
                $pdi = $this->open_pdi_document($Element->path, $this->get_optlist('open_pdi_document'));
                if (!$pdi)
                    $_SESSION['capture']->sendError(
                        "ERROR: ". $this->get_errnum() ." in ". $this->get_apiname() . "(): " . $this->get_errmsg()
                    );
            } 

            $this->font = $this->load_font('Courier', 'host', '');
            
            # begin new PDF
            $outpdf = 
                $OutputDirectory . DIRECTORY_SEPARATOR 
                    . $this->Batch->id . '_' . $Element->id . "_Analyze.pdf";
            $this->begin_document($outpdf, $this->get_optlist('begin_document'));   
            
            $Pages = $Element->getPages();
            $pl = $Pages->length;
            if($pl > 10) $pl = 10;
            
            # Draw words and boxes
            ###############################################################
            for($pi=0; $pi<$pl; $pi++) {
                $Page = $Pages->item($pi);
                $pn = $pi+1;
                
                # Create output page for image
                $this->begin_page_ext(
                    $Page->width, 
                    $Page->height, 
                    $this->get_optlist('begin_page_ext')
                );
                
                $this->setlinewidth(0.1);
                $this->setColor("stroke", "rgb", 255/255, 128/255, 64/255, false); 
                
                # Import PDF Page if format is PDF
                if($pdi) {
                    $pdi_page = $this->open_pdi_page($pdi, $pn, '');
                    $this->fit_pdi_page($pdi_page, 0, 0, "");
                }
                
                # Draw boxes
                $Words = $Page->get('Word');
                $wl = $Words->length;
                for($wi=0; $wi<$wl; $wi++) {
                    $Word = $Words->item($wi);
                    $Text = iconv("UTF-8", "ISO-8859-1//TRANSLIT", $Word->getText());
                    $Box = $Word->getBox();
                    $Box->setAttribute('page', $pn);
                    $width =  round($Box->urx - $Box->llx, 2);
                    $height = round($Box->lly - $Box->ury, 2);
                    $path = $this->getContentPath($Word);
                    
                    # Show text id not a PDF document
                    if($pdi) {
                        $this->fit_font_size($Text, $width);
                        $this->set_text_pos($Box->llx, $Box->lly);
                        $this->show($Text);
                    }
                    
                    # Draw box
                    $this->rect(
                        $Box->llx,
                        $Box->lly,
                        $width,
                        $height
                    );
                    $this->stroke();
                    
                    # Annotation
                    $textflow = $this->create_textflow(
                        "x=" . $Box->llx . " y=" . $Box->lly . " w=$width h=$height",
                        "font=".$this->font." fontsize=8"
                    );
                    
                    $this->create_annotation(
                        $Box->llx,
                        $Page->height - $Box->lly,
                        $Box->urx,
                        $Page->height - $Box->ury,
                        'highlight',
                        "title=$path createrichtext={textflow=$textflow}" // if type = text: iconname=help"
                    );
                    
                }
                
                # End page
                $this->end_page_ext(""); 
            }
                        
            # End document
            $this->end_document("");
            
        } catch (PDFlibException $e) {
            $_SESSION['capture']->logEvent(
                "PDFLib exception occurred during analyze: " 
                    . "[" . $e->get_errnum() . "] " 
                    . $e->get_apiname() . ": " 
                    . $e->get_errmsg(),
                3
            );
        } 
    
    }
        
    # Analyze of sub-structures (Table/Row/Cell/Para/Line) 
    private function AnalyzeContent(
        $Content,
        $pn
    ) {
        switch($Content->tagName) {
        case "Word":
            $this->AnalyzeWord($Content, $pn);
            break;
            
        default:
            $subContents = $Content->childNodes;
            $l = $subContents->length;
            for($i=0; $i<$l; $i++) {
                $subContent = $subContents->item($i);
                $this->AnalyzeContent($subContent, $pn);
            }
        }    
    }
    

    function fit_font_size(
        $Text,
        $width
    ) {
        $fontsize = 1;
        while($this->stringwidth($Text, $this->font, $fontsize) < $width)
            $fontsize++;  
                
        $this->setfont($this->font, $fontsize);
    }
    
}


