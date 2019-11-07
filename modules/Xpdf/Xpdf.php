<?php

class Xpdf
{    
    private $Batch;
    private $dpi;

    protected $config;

    public function __construct()
    {
        $this->config = parse_ini_file(__DIR__.'/config.ini');
    }
    
    public function pdftopng($Elements, $dpi=300)
    {
        $this->Batch = $_SESSION['capture']->Batch;
        
        $this->dpi = $dpi;
        
        $_SESSION['capture']->logEvent(
            "Processing with XPDF to PNG..."
        );
        
        /********************************************************************************
        ** Loop on Batch Documents and apply OCR on images
        ********************************************************************************/
        $output = array();
        $return = null;

        $l = $Elements->length;
        for($i=0; $i<$l; $i++) {
            $Element = $Elements->item($i);
            $Element->setAttribute('uom', 'dot');
            
            $pdfPath = $Element->getAttribute('path');
            $imgPath = substr($pdfPath, 0, -4);
            
            $cmd = $this->config['path'].'/pdftopng -r 300 '.$pdfPath.' "'.$imgPath.'"';
            exec($cmd, $output, $return);

            // convert dash into underscore in filename
            $imgFilename = $imgPath.'-000001.png';
            rename($imgFilename, $imgPath.'_000001.png');

            $page = $Element->addPage();
            $page->setAttribute('number', 1);

            $PlacedImage = $this->Batch->createElement('PlacedImage');
            $PlacedImage->setAttribute('image', '000001');
            $page->appendChild($PlacedImage);

            $PlacedImage->setAttribute('x', 0);
            $PlacedImage->setAttribute('y', 3508);
            $PlacedImage->setAttribute('height', 3508);
                       
            $Pages = $page->parentNode;
            
            $resources = $this->Batch->createElement('Resources');
            $Pages->appendChild($resources);

            $images = $this->Batch->createElement('Images');
            $resources->appendChild($images);

            $Image = $this->Batch->createElement('Image');
            $images->appendChild($Image);

            $Image->setAttribute('height', 3508);
            $Image->setAttribute('extractedAs', '.png');
            $Image->setAttribute('id', '000001');
        }
    }

    public function pdftotext(
        $Elements
    ) {
        $this->Batch = $_SESSION['capture']->Batch;
               
        $_SESSION['capture']->logEvent(
            "Processing with XPDF to text..."
        );

        /********************************************************************************
        ** Loop on Batch Documents and apply OCR on images
        ********************************************************************************/
        $output = array();
        $return = null;

        $l = $Elements->length;
        for($i=0; $i<$l; $i++) {
            $Element = $Elements->item($i);
            
            $pdfPath = $Element->getAttribute('path');
            $txtPath = substr($pdfPath, 0, -4);
            
            $cmd = $this->config['path'].'/pdftotext '.$pdfPath.' -';
            exec($cmd, $output, $return);
            $text = implode("\r\n", $output);

            $TextElement = $this->Batch->createElement('Text', $text);
            $Element->appendChild($TextElement);
        }
    }
}
