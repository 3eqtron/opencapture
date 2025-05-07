<?php
function Classify(
    $Batch,
    $Documents,
    $DftDocumentType
) {
	$Classifier = new Classifier($Batch);
    /********************************************************************************
	** Loop on Batch Documents and read
	********************************************************************************/
    $l = $Documents->length;
	for($i=0; $i<$l; $i++) {
		$Document = $Documents->item($i);
            
		/********************************************************************************
		** Read
		********************************************************************************/
        $Classifier->Classify(
            $Documents,
            $DftDocumentType
        );
	}
}

class Classifier
{
	private $Config;
	private $ConfigXPath;
    private $Batch;
    private $BatchXPath;
    
    public function __construct(
        $Batch
    ) {
        $Config = new DOMDocument();
        /*$Config->load("config/Classifier.xml");
        $this->Config = $Config;
        $this->ConfigXPath = new \DOMXPath($Config);*/

        $this->Batch = $Batch;
        $this->BatchXPath = new DOMXPath($Batch); 
        
    }
       
	function Classify(
        $Document,
        $defaultDocumentType
    ) {       
        
    }
        
}


?>