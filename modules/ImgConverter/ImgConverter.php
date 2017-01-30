<?php

class ImgConverter 
{
    function __construct()
    {
        $this->Batch = $_SESSION['capture']->Batch;
    }
    
    function Tiff2Pdf($ContentPath, $PathToTiff2pdf)
    {
        $Batch = $_SESSION['capture']->Batch;
        $BatchId = $Batch->id;
        
        /********************************************************************************
        ** Loop on DOCUMENT Elements
        ********************************************************************************/
        $OriginElements = $this->Batch->query($ContentPath);
        $l = $OriginElements->length;
        for ($i=0;$i<$l;$i++) {
            $arrayOrWk = array();
            $Element = $OriginElements->item($i);
            
            //launch tiff2pdf engine
            if (DIRECTORY_SEPARATOR == "/") {
                $command = "tiff2pdf";
                //replace > result by > /dev/null
                $command_line = $command . " " . $Element->path . " -o "
                    . str_replace($Element->extension, 'pdf', $Element->path) . " > /dev/null 2>&1 ";
            } else {
                /*$path = "C:\Program Files (x86)\\tiff-3.8.2-1-bin\bin";
                $command = "tiff2pdf.exe";
                $command_line = "\"" . $path . DIRECTORY_SEPARATOR
                    . $command . "\" " . $Element->path . " -o "
                    . str_replace($Element->extension, 'pdf', $Element->path);*/
				$command_line = "\"" . $PathToTiff2pdf . "\" " . $Element->path . " -o "
                    . str_replace($Element->extension, 'pdf', $Element->path);
            }
            
            echo $command_line."\n";
            $returnTab = array();
            //write log
            $_SESSION['capture']->logEvent(
                "Tiff conversion in pdf with the command line " . $command_line
            );
            $return = exec($command_line, $returnTab);
            
            //Max execution time set to 9 secs
            $starttime = round(microtime(true));
            $totaltime = 0;
            $maxtime = 9; //seconds
            for ($j=1;$j<=10;$j++) { // 10 loops 1 sec each
                if(
                    $totaltime < $maxtime 
                    && !file_exists(str_replace($Element->extension, 'pdf', $Element->path))
                ){
                    //wait 1 second
                    usleep(1000000);
                    $currenttime = round(microtime(true));
                    $totaltime = $totaltime + ($currenttime - $starttime);
                } else {
                    //stop the loop
                    break;
               }
            }
            if (file_exists(str_replace($Element->extension, 'pdf', $Element->path))) {
                unlink($Element->path);
            }
            $Element->setAttribute("fileToImport", str_replace($Element->extension, 'pdf', $Element->path));
        }
    }
}
