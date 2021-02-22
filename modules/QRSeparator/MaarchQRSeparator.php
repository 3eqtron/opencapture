<?php

class QRSeparator
{
    public $qrcodePrefix = "false";

    public function __construct()
    {
        $this->Batch = $_SESSION['capture']->Batch;
        require __DIR__ . "/../../vendor/autoload.php";
    }

    public function reconcile($ScanSource, $qrcodePrefix = "false", $ResultDirectory = false)
    {
        $result = '';
        echo "Init process ...\n";
        $_SESSION['capture']->logEvent(
            "Init process ... "
        );

        $this->qrcodePrefix = $qrcodePrefix;

        if ($qrcodePrefix == "true") {
            echo "Prefix MAARCH_ is enabled !\n";
        }

        if (!is_readable($ScanSource)) {
            echo "Source directory is not valid !\n";
            $_SESSION['capture']->logEvent(
                "Source directory is not valid !"
            );
            exit();
        }

        if (!is_readable($ScanSource)) {
            echo "Result directory is not valid ! \n";
            $_SESSION['capture']->logEvent(
                "Result directory is not valid !"
            );
            exit();
        }

        $files = array_diff(scandir($ScanSource), array('..', '.','FAILED', 'files_errors', 'files_noseparator'));

        if (empty($files)) {
            echo "No files to process ! End of process ...\n";
            $_SESSION['capture']->logEvent(
                "No files to process ! End of process ..."
            );
            exit();
        }

        $num_file = 1;
        foreach ($files as $key => $value) {
            $text = '';
            $resultZbar = '';
            $array_files = explode('.', $files[$key]);
            //Ignore all files except pdf
            if (strtolower($array_files[1]) == 'pdf') {
                echo "\n\n * File n°".$num_file.": ".$files[$key]." *\n";
                $_SESSION['capture']->logEvent(
                    "* File n°".$num_file.": ".$files[$key]." *"
                );

                try {
                    copy($ScanSource.$files[$key], $this->Batch->directory . '/' . $key . '.pdf');
                    echo "process file n°".$this->Batch->directory . '/' . $key . '.pdf'. PHP_EOL;
                    //Attempt to extract QRCODE
                    try {
                        $qrcode = new \Zxing\QrReader($this->Batch->directory . '/' . $key.'.pdf');
                    } catch (Exception $e) {
                        echo 'Caught exception QrReader: ',  $e->getMessage(), "\n";
                        $_SESSION['capture']->logEvent(
                            "Caught exception QrReader: ".$e->getMessage()
                        );
                        return false;
                    }

                    try {
                        $text = $qrcode->text();
                    } catch (Exception $e) {
                        //lgi patch
//                        echo 'convert ' . $this->Batch->directory . '/' . $key . '.pdf' . PHP_EOL;
                        exec('convert -density 300 ' .  $this->Batch->directory . '/' . $key . '.pdf ' . $this->Batch->directory . '/' . $key . '.png');
                        exec('zbarimg ' . $this->Batch->directory . '/' . $key . '.png', $resultZbar);
//                        var_dump($resultZbar[0]);
                        $textExploded = explode(':', $resultZbar[0]);
                        //var_dump($textExploded);
                        if (is_array($textExploded) && $textExploded[0] <> 'QR-Code') {
                            echo 'NO QR CODE' . PHP_EOL;
                            $text = '';
                        } else {
                            $text = str_replace('QR-Code:', '', $resultZbar[0]);
                        }
                    }

                    echo 'QR code : ' . $text . PHP_EOL;

                    $data = json_decode($text, true);

                    $chrono = $data['chrono'];
                    $resIdMaster = $data['resIdMaster'];
                    $originId = $data['resId'];
                    $title = $data['title'];

                    if (!empty($data)) {
                        if ($this->qrcodePrefix == 'true' && preg_match("/^MAARCH_/i", $chrono)) {
                            $chrono = preg_replace("/^MAARCH_/i", '', $chrono);
                        }
                        echo "Un résultat a été trouvé.";
                        $Document = $this->Batch->addDocument($this->Batch->directory . '/' . $key . '.pdf');
                        $_SESSION['capture']->logEvent(
                            "Document " . $Document->id  . " added with source " . $this->Batch->directory . '/' . $key . '.pdf'
                        );

                        $Document->setMetadata("chrono", $chrono);
                        $Document->setMetadata("resIdMaster", $resIdMaster);
                        $Document->setMetadata("originId", empty($originId) ? null : $originId);
                        $Document->setMetadata("title", $title);
                    } else {
                        echo "Aucun résultat n'a été trouvé.";
                    }
                } catch (Exception $e) {
                    if (!is_dir($ScanSource.'files_errors/')) {
                        mkdir($ScanSource.'files_errors/', 0755, true);
                    }
                    echo 'FAILED reco qr code' . PHP_EOL;
                    copy($ScanSource.$files[$key], $ScanSource.'files_errors/'.$files[$key]);
                    unlink($ScanSource.$files[$key]);
                    $num_file++;
                    continue;
                }
                if ($result == 'NOSEPARATOR') {
                    if (!is_dir($ScanSource.'files_noseparator/')) {
                        mkdir($ScanSource.'files_noseparator/', 0755, true);
                    }
                    copy($ScanSource.$files[$key], $ScanSource.'files_noseparator/'.$files[$key]);
                    unlink($ScanSource.$files[$key]);
                    $num_file++;
                    continue;
                }
                unlink($ScanSource.$files[$key]);
            } else {
                echo $files[$key] . " : No pdf format ! skipping ...\n";
                $_SESSION['capture']->logEvent(
                    "No pdf format ! skipping ...\n"
                );
            }
            $num_file++;
        }

        echo "End of process ...\n";
        $_SESSION['capture']->logEvent(
            "End of process ..."
        );
    }
    
    public function separatePDF($ScanSource, $qrcodePrefix = "false", $ResultDirectory = false)
    {

        echo "Init process ...\n";
        $_SESSION['capture']->logEvent(
            "Init process ... "
        );

        $this->qrcodePrefix = $qrcodePrefix;

        if ($qrcodePrefix == "true") {
            echo "Prefix MAARCH_ is enabled !\n";
        }

        if (!is_readable($ScanSource)) {
            echo "Source directory is not valid !\n";
            $_SESSION['capture']->logEvent(
                "Source directory is not valid !"
            );
            exit();
        }

        if (!is_readable($ScanSource)) {
            echo "Result directory is not valid ! \n";
            $_SESSION['capture']->logEvent(
                "Result directory is not valid !"
            );
            exit();
        }

        $files = array_diff(scandir($ScanSource), array('..', '.','FAILED', 'files_errors', 'files_noseparator'));

        if (empty($files)) {
            echo "No files to process ! End of process ...\n";
            $_SESSION['capture']->logEvent(
                "No files to process ! End of process ..."
            );
            exit();
        }

        echo "There is ".count($files)." file(s) to process\n";
        $_SESSION['capture']->logEvent(
            "There is ".count($files)." to process"
        );
        $num_file = 1;
        foreach ($files as $key => $value) {
            echo "\n\n * File n°".$num_file.": ".$files[$key]." *\n";
            $_SESSION['capture']->logEvent(
                "* File n°".$num_file.": ".$files[$key]." *"
            );
            $array_files = explode('.', $files[$key]);

            //Ignore all files except pdf
            if (strtolower($array_files[1]) == 'pdf') {
                //call split function to sepearate pages
                try {
                    $this->split_pdf($ScanSource.$files[$key], $this->Batch->directory . '/' . $key);
                } catch (Exception $e) {
                    echo 'ERROR (move '.$files[$key].' to '.$ScanSource.'FAILED/) ! ',  $e->getMessage(), "\n";
                    $_SESSION['capture']->logEvent(
                        "ERROR (move ".$files[$key]." to ".$ScanSource."FAILED/) ! ".$e->getMessage()
                    );
                    if (!is_dir($ScanSource.'FAILED')) {
                        mkdir($ScanSource.'FAILED', 0755, true);
                    }
                    
                    copy($ScanSource.$files[$key], $ScanSource.'FAILED/'.$files[$key]);
                }
                //merge pages previously splited
                try {
                    $result = $this->construct_pdf($this->Batch->directory . '/' . $key, $ResultDirectory);
                } catch (Exception $e) {
                    if (!is_dir($ScanSource.'files_errors/')) {
                        mkdir($ScanSource.'files_errors/', 0755, true);
                    }
                    echo 'FAILED construct_pdf' . PHP_EOL;
                    //shell_exec('rm -Rf '. $this->Batch->directory . '/' . $key);
                    copy($ScanSource.$files[$key], $ScanSource.'files_errors/'.$files[$key]);
                    unlink($ScanSource.$files[$key]);
                    $num_file++;
                    continue;
                }
                if ($result == 'NOSEPARATOR') {
                    //shell_exec('rm -Rf ' . $this->Batch->directory . '/' . $key);
                    if (!is_dir($ScanSource.'files_noseparator/')) {
                        mkdir($ScanSource.'files_noseparator/', 0755, true);
                    }
                    copy($ScanSource.$files[$key], $ScanSource.'files_noseparator/'.$files[$key]);
                    unlink($ScanSource.$files[$key]);
                    $num_file++;
                    continue;
                }
                unlink($ScanSource.$files[$key]);
                //rmdir(realpath($this->Batch->directory . '/' . $key));
            } else {
                echo "No pdf format ! skipping ...\n";
                $_SESSION['capture']->logEvent(
                    "No pdf format ! skipping ...\n"
                );
            }
            $num_file++;
        }

        echo "End of process ...\n";
        $_SESSION['capture']->logEvent(
            "End of process ..."
        );
    }

    public function split_pdf($filename, $end_directory)
    {
        $end_directory = $end_directory.'/';
        /*
         * Creation du repertoire split
         */
        if (!is_dir($end_directory)) {

            // Will make directories under end directory that don't exist (Provided that end directory exists and has the right permissions)
            mkdir($end_directory, 0755, true);
        }
        
        $pdf = new \setasign\Fpdi\Fpdi('P', 'mm');

        //How manu pages ?
        $pdfdata = file_get_contents($filename);
        $pagecount = $pdf->setSourceFile($filename);
        
        //Split each page of pdf file
        for ($i = 1; $i <= $pagecount; $i++) {
            $new_pdf = new \setasign\Fpdi\Fpdi('P', 'mm');

            $new_pdf->AddPage();
            
            $new_pdf->setSourceFile($filename);

            $tplidx = $new_pdf->importPage($i);
            $new_pdf->useTemplate($tplidx);

            try {
                $new_filename = $end_directory.$i.".pdf";
                $new_pdf->Output($new_filename, "F");
                echo "Page ".$i." split into ".$new_filename;
                echo "\n";
                $_SESSION['capture']->logEvent(
                    "Page ".$i." split into ".$new_filename
                );
            } catch (Exception $e) {
                echo 'Caught exception: ',  $e->getMessage(), "\n";
                $_SESSION['capture']->logEvent(
                    "Caught exception: ".$e->getMessage()
                );
            }
        }
    }

    public function construct_pdf($split_directory, $end_directory = false)
    {
        echo 'qrcodePrefix ' . $this->qrcodePrefix . PHP_EOL;
        $end_directory = $end_directory ? $end_directory : $this->Batch->directory . '/';
        
        //$new_pdf = new FPDI();
        $new_pdf = new \setasign\Fpdi\Fpdi('P', 'mm');
        
        $i=1;
        $z=0;
        $qr_label = '';

        $split_directory = $split_directory.'/';

        while ($i!=0) {
            $text = '';
            $resultZbar = '';
            if (is_file($split_directory.$i.'.pdf')) {
                $file = $i.'.pdf';
                $next_filename = $i+1;

                //Attempt to extract QRCODE
                try {
                    $qrcode = new \Zxing\QrReader($split_directory.$file);
                } catch (Exception $e) {
                    echo 'Caught exception QrReader: ',  $e->getMessage(), "\n";
                    $_SESSION['capture']->logEvent(
                        "Caught exception QrReader: ".$e->getMessage()
                    );
                    return false;
                }

                try {
                    $text = $qrcode->text();
                } catch (Exception $e) {
                    $text = false;
                }

                if ($text === false) {
                    // If library fails to read qr code in pdf, we try to read it with a different method by converting the pdf to an image
//                    echo 'convert -density 30 ' . $split_directory . $file . '  -colorspace RGB ' . $split_directory . $file . '.png' . PHP_EOL;
                    exec('convert -density 30 ' . $split_directory . $file . '  -colorspace RGB ' . $split_directory . $file . '.png');
//                    echo 'zbarimg ' . $split_directory . $file . '.png' . PHP_EOL;
                    exec('zbarimg ' . $split_directory . $file . '.png', $resultZbar);
                    if (!empty($resultZbar)) {
//                        var_dump($resultZbar[0]);
                        $textExploded = explode(':', $resultZbar[0]);

                        if (is_array($textExploded) && $textExploded[0] <> 'QR-Code') {
//                            echo 'NO QR CODE' . PHP_EOL;
                            $text = '';
                        } else {
                            $text = str_replace('QR-Code:', '', $resultZbar[0]);
                        }
                    } else {
//                        echo "NO RESULT";
                        $text = '';
                    }
                }

                echo "QR code : " . $text . PHP_EOL;

                if ($this->qrcodePrefix == "false" && !empty($text) && !is_numeric($text) && $text != 'GENERIQUE') {
                    echo 'not numeric ' .  $text . PHP_EOL;
                    $text = '';
                }
                if ($this->qrcodePrefix == "true" && !empty($text)) {
                    if (preg_match("/^MAARCH_/i", $text)) {
                        $text = preg_replace("/^MAARCH_/i", '', $text);
                        echo "Un résultat a été trouvé.";
                    } else {
                        $text = '';
                        echo "Aucun résultat n'a été trouvé.";
                    }
                }

                if (!empty($text)) {
                    $isCourrier = false;
                    echo "This is a separator ! Extract name ...";
                    echo "\n";
                    $_SESSION['capture']->logEvent(
                        "This is a separator ! Extract name ..."
                    );
                    $old_label = $qr_label;
                    $qr_label = $text;
                    $label[]=$text;
                } elseif (empty($text) && !empty($qr_label)) {
                    //If not a separator, merge of each previous pdf
                    $isCourrier =true;
                    echo "This is a maarch document page ! Add page to pdf ...";
                    echo "\n";
                    $_SESSION['capture']->logEvent(
                        "This is a maarch document page ! Add page to pdf ..."
                    );
                    $new_pdf->AddPage();

                    $new_pdf->setSourceFile($split_directory.$file);

                    $tplidx = $new_pdf->importPage(1);
                    $new_pdf->useTemplate($tplidx);
                } else {
                    // If not a separator and no previous pdf, merge actual pdf
                    echo "No separator found ! Add page to pdf ...";
                    echo "\n";
                    $_SESSION['capture']->logEvent(
                        "No separator found ! Add page to pdf ..."
                    );
                    $label[$z] = 'NOSEPARATOR';
                    $new_pdf->AddPage();

                    $new_pdf->setSourceFile($split_directory.$file);

                    $tplidx = $new_pdf->importPage(1);
                    $new_pdf->useTemplate($tplidx);
                    return 'NOSEPARATOR';
                }

                /*
                 * Creation du pdf du separateur
                 */
                if ((($isCourrier == false && $old_label != '' && $text != '') || (!is_file($split_directory.$next_filename.'.pdf')))) {
                    echo "End of merge pdf for service : ".$label[$z];
                    echo "\n";
                    $_SESSION['capture']->logEvent(
                        "End of merge pdf for service : ".$label[$z]
                    );
                    $today = date("YmdHm");
                    $rand = rand();
                    
                    //create merged pdf
                    $filename = $label[$z].'_'.$today.$rand.".pdf";
                    $new_filename = $end_directory.$filename;

                    $z++;
                    $new_pdf->Output($new_filename, "F");


                    // add new document with first BODY part found
                    $Document = $this->Batch->addDocument($new_filename);

                    $_SESSION['capture']->logEvent(
                        "Document " . $Document->id  . " added with source " . $new_filename
                    );

                    //add destination data
                    $Document->setMetadata(
                        "destination",
                        $label[$z-1]
                    );

                    $new_pdf = new \setasign\Fpdi\Fpdi('P', 'mm');
                }
                echo "Remove tmp file : ".$split_directory.$i.'.pdf';
                echo "\n";
                //unlink($split_directory.$i.'.pdf');
            } else {
                break;
            }
            $text = '';
            $i++;
        }
    }
}
