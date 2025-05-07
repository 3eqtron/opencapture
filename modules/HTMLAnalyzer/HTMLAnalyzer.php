<?php

class HTMLAnalyzer
{
    
    function getContentPath(
        $Content
    ) {
        $ContentFullPath = $Content->getNodePath();       
        return str_replace($this->rootPath . '/Pages', '', $ContentFullPath);
    }
           
    public function Analyze(
        $ContentPath,
        $OutputDirectory
    ) {
        $this->Batch = $_SESSION['capture']->Batch;
        
        $this->OutputDirectory = $OutputDirectory;
        
        $Elements = $this->Batch->query($ContentPath);
        $l = $Elements->length; 
        $_SESSION['capture']->logEvent("$l elements to proceed");      
        for($i=0; $i<$l; $i++) {
            $Element = $Elements->item($i);
            $this->AnalyzeElement($Element);
        }
    }

    private function AnalyzeElement(
        $Element
    ) {
        $OutputDirectory = $this->OutputDirectory;
        $this->rootPath = $Element->getNodePath();
        $this->Words = array();
        
        //$this->font = $this->load_font('Courier', 'host', '');
        
        # begin new HTML
        $outhtml = 
            $OutputDirectory . DIRECTORY_SEPARATOR 
                . $this->Batch->id . '_' . $Element->id . "_Analyze.html";
        
        $hdoc = new DOMDocument();
        $hdoc->loadHTML('<html><meta http-equiv="Content-Type" content="text/html; charset=UTF-8" /><body></body></html>');
        $hbody = $hdoc->getElementsByTagName('body')->item(0);
        
        $Pages = $Element->getPages();
        $pl = $Pages->length;
        if($pl > 10) $pl = 10;
        
        # Draw words and boxes
        ###############################################################
        $this->pt = 0;
        for($pi=0; $pi<$pl; $pi++) {
            $Page = $Pages->item($pi);
            $pn = $pi+1;
            # Create output page for image
            $hpage = 
                $this->showPage(
                    $hbody, 
                    $pn, 
                    $Page->width, 
                    $Page->height,
                    $pt
                );
            
            # Draw boxes
            $Words = $Page->get('Word');
            $wl = $Words->length;
            for($wi=0; $wi<$wl; $wi++) {
                $Word = $Words->item($wi);
                $hword = 
                    $this->showWord(
                        $hpage,
                        $Word,
                        $pt
                    );
            }
            # absolute page top position
            $pt += $Page->height;
        }
        
        $hdoc->save($outhtml);
        
    }
        
    # Add a page as div
    private function showPage(
        $hparent,
        $n,
        $w, 
        $h,
        $pt
    ) {
        $hpage = $hparent->ownerDocument->createElement('div');
        $hpage->setAttribute('name', 'Page');
        $hpage->setAttribute('number', $n);
        $hpage->setAttribute(
            'style', 
            'position: absolute; 
             top: '.($pt + 10).'px;
             left: 0px;
             width: '.$w.'px; 
             height: '.$h.'px; 
             border: solid 1px;'
        );
        $hparent->appendChild($hpage);
        
        return $hpage;
    }
    
    private function showWord(
        $hparent,
        $Word,
        $pt
    ) {
        $Text = iconv("UTF-8", "ISO-8859-1//TRANSLIT", $Word->getText());
        $Box = $Word->getBox();
        $Box->setAttribute('page', $pn);
        $width =  round($Box->urx - $Box->llx, 2);
        $height = round($Box->lly - $Box->ury, 2);
        $path = $this->getContentPath($Word);
        
        $hword = $hparent->ownerDocument->createElement('span', htmlentities($Text));
        $hword->setAttribute('name', 'Word');
        $hword->setAttribute(
            'style', 
            'position: absolute;
             top: '.$Box->ury.'px;
             left: '.$Box->llx.'px;
             font-family: Arial; 
             font-size: '.(floor($height)-1).'pt;'
        );
        $hword->setAttribute(
            'title',
            "x=" . $Box->llx . " y=" . $Box->lly . " w=$width h=$height"
        );
        $hparent->appendChild($hword);
        
        # Draw box
        /*$this->rect(
            $Box->llx,
            $Box->lly,
            $width,
            $height
        );
        $this->stroke();*/
        
        # Annotation
        /*$textflow = $this->create_textflow(
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
        );*/
    
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


