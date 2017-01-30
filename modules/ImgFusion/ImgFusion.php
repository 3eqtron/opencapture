<?php

class ImgFusion
{
    function __construct()
    {
        $this->Batch = $_SESSION['capture']->Batch;
    }

    function findSeparator($ContentPath, $Rules)
    {
        $Batch = $_SESSION['capture']->Batch;
        $BatchId = $Batch->id;
        /********************************************************************************
        ** Loop on FILE Elements
        ********************************************************************************/
        $OriginElements = $this->Batch->query($ContentPath);
        $l = $OriginElements->length;
        for ($i=0;$i<$l;$i++) {
            $Element = $OriginElements->item($i);
            if ($Rules[1] == 'barcode') {
                for ($cpt=0;$cpt<10;$cpt++) {
                    if ($Element->hasAttribute('barcode' . $cpt)) {
                        if ($this->checkBarcode($Element->getAttribute("barcode" . $cpt), $Rules[3])) {
                            $Element->setAttribute("sepBarcode", $Element->getAttribute("barcode" . $cpt));
                            $Element->setAttribute("aSeparator", true);
                            break;
                        }
                    }
                }
            }
        }
    }

    function fusion($ContentPath, $PathToFusionProgram, $Format, $DeleteSeparator)
    {
        $Batch = $_SESSION['capture']->Batch;
        $BatchId = $Batch->id;
        /********************************************************************************
        ** Loop on FILE Elements
        ********************************************************************************/
        $OriginElements = $this->Batch->query($ContentPath);
        $l = $OriginElements->length;
        for ($i=0;$i<$l;$i++) {
            $File = $OriginElements->item($i);
            //TODO : MANAGE THIS ERROR
            //case of the first page, must be a separator !
            if ($i==0) {
                if ($File->hasAttribute('aSeparator') && $File->getAttribute('aSeparator')) {
                    //echo 'a separator on the first page, GOOD !' . PHP_EOL;
                    $_SESSION['capture']->logEvent(
                        "A separator on the first page"
                    );
                } else {
                    echo 'WARNING !!! no separator on the first page' . PHP_EOL;
                    $_SESSION['capture']->sendError(
                        "WARNING !!! no separator on the first page"
                    );
                    exit();
                }
            }

            if ($File->hasAttribute('aSeparator') && $File->getAttribute('aSeparator')) {
                //echo 'a separator, so create a new document' . PHP_EOL;
                $_SESSION['capture']->logEvent(
                    "a separator, so create a new document with separator " . $File->getAttribute("sepBarcode")
                );
                $separator = $File;
                //create a new dom element
                $document = $Batch->addDocument($File->getAttribute("path"));
                $document->setAttribute("sepBarcode", $File->getAttribute("sepBarcode"));
            }
            $document->appendContent($File, 'File');
        }
        /********************************************************************************
        ** Loop on DOCUMENT Elements
        ********************************************************************************/
        $OriginElements = $this->Batch->query('/Batch/Documents/Document');
        $l = $OriginElements->length;
        for ($i=0;$i<$l;$i++) {
            $Document = $OriginElements->item($i);
            $FilesOriginElements = $Document->getElementsByTagName('File');
            $length = $FilesOriginElements->length;
            $fileList = '';
            $targetFile = $Document->getAttribute("path");
            /********************************************************************************
            ** Loop on FILE Elements
            ********************************************************************************/
            for ($j=0;$j<$length;$j++) {
                $File = $FilesOriginElements->item($j);
                if (DIRECTORY_SEPARATOR == "/") {
					if ($j==0) {
						$fileList .= $File->getAttribute("path");
					} else {
						$fileList .= ' ' . $File->getAttribute("path");
					}
				} else {
					if ($j==0) {
						$fileList .= $File->getAttribute("path");
					} else {
						$fileList .= ' ' . "\"" . $File->getAttribute("path") . "\"";
					}
				}
            }

            $fileListFile = $this->Batch->directory . DIRECTORY_SEPARATOR . $Document->id . '.fusion';
            file_put_contents($fileListFile, $fileList);
			
            if ($Format == 'TIFF') {
                //launch tiff2cp engine
                if (DIRECTORY_SEPARATOR == "/") {
                    $command = "tiffcp";
                    $commandLine = $command . " " . $fileList . " " . $targetFile;
                } else {
                    $commandLine = "\"" . $PathToFusionProgram . "\" " . "\"" . $fileList . "\"" . " " . $targetFile;
                }
            } elseif ($Format == 'PDF') {
                //launch gs engine
                if (DIRECTORY_SEPARATOR == "/") {
                    $command = "gs -q -dNOPAUSE -dBATCH -sDEVICE=pdfwrite -sOutputFile=" . $targetFile;
                    $commandLine = $command . " " . $fileList;
                } else {
                    $commandLine = "\"" . $PathToFusionProgram
                        . "\" -q -dNOPAUSE -dBATCH -sDEVICE=pdfwrite -sOutputFile=" . "\"" . $targetFile . "\"" . " @" . $fileListFile;
                    //    . "\" -q -dNOPAUSE -dBATCH -sDEVICE=pdfwrite -sOutputFile=" . "\"" . $targetFile . "\"" . " " . trim($fileList);
                }
            }
            echo $commandLine . PHP_EOL;
            $_SESSION['capture']->logEvent(
                "Img fusion with the command line " . $commandLine
            );

            $return = exec($commandLine, $returnTab);
        }
    }

    function checkBarcode($barcode, $regularExpr)
    {
        return preg_match($regularExpr, $barcode);
    }
}
