<?php

class BarcodeOCR
{
    function __construct()
    {
        $this->Batch = $_SESSION['capture']->Batch;
    }

    function BarcodeOCR($ContentPath, $pathToBarcodeReader, $BarcodeType = 'C128')
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
            //launch barcode recognition engine
            if ($pathToBarcodeReader <> '') {
                if (DIRECTORY_SEPARATOR == "/") {
                    $command_line = "\"" . $pathToBarcodeReader ."\" " . $Element->path . " " . $BarcodeType;
                } else {
                    $command_line = "\"" . $pathToBarcodeReader ."\" " . "\"" . $Element->path . "\"" . " " . $BarcodeType;
                }
            } else {
                if (DIRECTORY_SEPARATOR == "/") {
                    $command = "zbarimg";
                    $command_line = $command . " -q " . $Element->path;
                } else {
                    $path = "C:\Program Files (x86)\ZBar\bin";
                    $command = "zbarimg.exe";
                    $command_line = "\"".$path.DIRECTORY_SEPARATOR.$command."\" -q ".$Element->path;
                }
            }

            echo $command_line."\n";
            $returnTab = array();
            //write log
            $_SESSION['capture']->logEvent(
                "Ocr barcode recognition with the command line " . $command_line
            );
            $return = exec($command_line, $returnTab);

            //$barcode = str_replace("CODE-39:", "", $returnTab[0]);
            $barcodesList = array();
            $barcodesList = explode("##", $returnTab[0]);
            var_dump($barcodesList);
            for ($cpt=0;$cpt<count($barcodesList);$cpt++) {
                $barcodesClean = array();
                $theBarcode = '';
                $barcodesClean = explode(":", $barcodesList[$cpt]);
                if (count($barcodesClean) >= 2) {
                    $theBarcode = $barcodesClean[1];
                } else {
                    $theBarcode = $barcodesList[$cpt];
                }
                echo $theBarcode . PHP_EOL;
                if ($theBarcode <> '') {
                    $Element->setAttribute("barcode" . $cpt, $theBarcode);
                    $_SESSION['capture']->logEvent(
                        "A barcode " . $theBarcode
                    );
                }
            }
        }
    }
}
