<?php


class MaarchCaptureConnector
{
    
    private $Batch;

    
    function __construct()
    {
        $this->Batch = $_SESSION['capture']->Batch;
    }
    
    public function setDocumentsDestination() 
	{        
        $Documents = $this->Batch->getDocuments();
		for($i=0, $l=$Documents->length;
			$i<$l;
			$i++
		) {
			$Document = $Documents->item($i);
		
			$file_infos = explode('_', substr($Document->filename, 7));
			$dest = $file_infos[0];
			$Document->setMetadata('destination', $dest);
		}
    }
    
}