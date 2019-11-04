<?php

class TesseractOCR
{    
    private $Batch;
    private $dpi;
    
    protected $config;

    public function __construct()
    {
        $this->Batch = $_SESSION['capture']->Batch;
        $this->config = parse_ini_file(__DIR__.'/config.ini');
    }
    
    public function OCR(
        $ContentPath,
        $dpi=300
    ) {       
        $this->dpi = $dpi;
        
        $_SESSION['capture']->logEvent(
            "Processing OCR..."
        );
        
        /********************************************************************************
        ** Loop on Batch Documents and apply OCR on images
        ********************************************************************************/
        $Elements = $this->Batch->query($ContentPath);
        $l = $Elements->length;
        for($i=0; $i<$l; $i++) {
            $Element = $Elements->item($i);
            $this->OCRElement(
                $Element
            );
        }
    
    }

    private function OCRElement(
        $Element
    ) {
        $id = $Element->getAttribute('id');
        $_SESSION['capture']->logEvent(
            "Processing OCR on Element $id..."
        );
        /********************************************************************************
        ** Loop on Document pages to find placed images
        ********************************************************************************/
        $Pages = $Element->getPages();
        $pl = $Pages->length;
        for($pi=0; $pi<$pl; $pi++) {
            $Page = $Pages->item($pi);
            $this->OCRPage($Page, $Element);
        }
    }
    
    function OCRPage(
        $Page, 
        $Document,
        $dpi = 300
    ) {
        $pn = $Page->getAttribute('number');
        /********************************************************************************
        ** Loop on placed images to process OCR
        ********************************************************************************/
        $PlacedImages = 
            $this->Batch->query(
                './PlacedImage', 
                $Page
            );
        $pil = $PlacedImages->length;
        for($pii=0; $pii<$pil; $pii++) {
            $PlacedImage = $PlacedImages->item($pii);
            $ImageId = $PlacedImage->getAttribute('image');
            $_SESSION['capture']->logEvent(
                "Processing OCR on image $ImageId..."
            );
            $Image = 
                $this->Batch->query(
                    './Pages/Resources/Images/Image[@id="'.$ImageId.'"]', 
                    $Document
                )->item(0);
                
            $PlacedImageX = $PlacedImage->getAttribute('x');
            $PlacedImageY = $PlacedImage->getAttribute('y');
            $PlacedImageHeight = $PlacedImage->getAttribute('height');
            $ImageHeight = $Image->getAttribute('height');    
            
            $ext = $Image->getAttribute('extractedAs');
            $ImageFile = 
                (string) $this->Batch->directory
                . DIRECTORY_SEPARATOR . $Document->id . '_' . $ImageId . $ext;
            $OutFile =
                (string) $this->Batch->directory
                . DIRECTORY_SEPARATOR . $Document->id . '_' . $ImageId;
            
            /********************************************************************************
            ** Generate HOCR xhtml file
            ********************************************************************************/
            //exec('"bin/Tesseract-OCR/tesseract.exe" "'.$ImageFile.'" "'.$OutFile.'" nobatch makebox');
            exec(
                $this->config['path'].'/tesseract.exe "'.$ImageFile.'" "'.$OutFile.'" hocr'
            );
            $HocrFile = $OutFile . '.html';
            if(!is_file($HocrFile))
                continue;
            $_SESSION['capture']->logEvent(
                "Text found on image, adding to document page"
            );
            $tesDocument = new DOMDocument();
            $tesDocument->load($HocrFile);
            $tesXPath = new DOMXPath($tesDocument);
            $tesXPath->registerNamespace('xhtml', 'http://www.w3.org/1999/xhtml');
            
            $tesParas = 
                $tesXPath->query(
                    '//xhtml:p[@class="ocr_par"]'
                );
            $tpl = $tesParas->length;
            for($tpi=0; $tpi<$tpl; $tpi++) {
                $tesPara = $tesParas->item($tpi);
                $Para = $this->Batch->createElement('Para');
                $Page->appendChild($Para);
                $tesLines = 
                    $tesXPath->query(
                        './xhtml:span[@class="ocr_line"]',
                        $tesPara
                    );
                $tll = $tesLines->length;
                for($tli=0; $tli<$tll; $tli++) {
                    $tesLine = $tesLines->item($tli);
                    $Line = $this->Batch->createElement('Line');
                    $Para->appendChild($Line);
                    $tesWords = 
                        $tesXPath->query(
                            './xhtml:span[@class="ocrx_word"]',
                            $tesLine
                        );
                    $twl = $tesWords->length;
                    for($twi=0; $twi<$twl; $twi++) {
                        $tesWord = $tesWords->item($twi);
                        $Word = $this->Batch->createElement('Word');
                        $Line->appendChild($Word);
                        
                        $Text = $Word->addText($tesWord->nodeValue);

                        $Box = $this->Batch->createElement('Box');
                        $tesBox = explode(' ', $tesWord->getAttribute('title'));
                       
                        $llx = ($tesBox[1] / $dpi * 72) + $PlacedImageX;
                        $lly = ($tesBox[4] / $dpi * 72) + $PlacedImageY - $PlacedImageHeight;
                        $urx = ($tesBox[3] / $dpi * 72) + $PlacedImageX;
                        $ury = ($tesBox[2] / $dpi * 72) + $PlacedImageY - $PlacedImageHeight;
                        $w = $urx-$llx;
                        $h = $lly-$ury;
                        
                        $Box->setAttribute('llx', round($llx, 2));
                        $Box->setAttribute('lly', round($lly, 2));
                        $Box->setAttribute('urx', round($urx, 2));
                        $Box->setAttribute('ury', round($ury, 2));
                        $Box->setAttribute('w', round($w, 2));
                        $Box->setAttribute('h', round($h, 2));
                        $Box->setAttribute('page', $pn);
                        $Word->appendChild($Box);
                    }
                }
            }
            
            /********************************************************************************
            ** Generate BOX file with glyph information
            ********************************************************************************/
            /*exec(
                '"bin/Tesseract-OCR/tesseract.exe" "'.$ImageFile.'" "'.$OutFile.'" makebox'
            );
            $BoxFile = $OutFile . '.box';
            if(!is_file($BoxFile))
                continue;
            $tesFile = fopen($BoxFile, "r");
            while ($tesGlyph = fgetcsv($tesFile, 40, " ")) {
                $llx = ($tesGlyph[1]                  / $dpi * 72) + $PlacedImageX; 
                $lly = (($ImageHeight - $tesGlyph[2]) / $dpi * 72) + $PlacedImageY - $PlacedImageHeight; 
                $urx = ($tesGlyph[3]                  / $dpi * 72) + $PlacedImageX; 
                $ury = (($ImageHeight - $tesGlyph[4]) / $dpi * 72) + $PlacedImageY - $PlacedImageHeight; 

                $size = ($lly - $ury) * 1.20; // Test : dif between actual char size and drawing box size                   
                $width = $urx - $llx;
                $page = $tesGlyph[5] + 1;
                
                $Glyph = $Batch->createElement('Glyph', $tesGlyph[0]);
                $Glyph->setAttribute('font',    '');
                $Glyph->setAttribute('size',    round($size, 2));
                $Glyph->setAttribute('x',       round($llx, 2));
                $Glyph->setAttribute('y',       round($lly, 2));
                $Glyph->setAttribute('width',   round($width, 2));
                  
                $Box = 
                    $this->Batch->query(
                        '//Box['
                            . '../Text[contains(., "'.$tesGlyph[0].'")] '
                            . 'and @llx<="'.$llx.'" '
                            . 'and @lly>="'.$lly.'" '
                            . 'and @urx>="'.$llx.'" '
                            . 'and @ury<="'.$lly.'" '
                            . 'and @page="'.$page. '"'
                        .']'
                    )->item(0);
                if($Box)
                    $Box->appendChild($Glyph);
                else {
                    $Page->appendChild($Glyph);
                }
            }
            fclose($tesFile);
            */
        }
    }
}
