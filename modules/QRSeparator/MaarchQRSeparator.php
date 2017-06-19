<?php

class QRSeparator 
{
    
    function __construct()
    {
        $this->Batch = $_SESSION['capture']->Batch;
    }
    
    function separatePDF($ScanSource,$ResultDirectory = false)
    {

        echo "Init process ...\n";
        $_SESSION['capture']->logEvent(
            "Init process ... "
        );

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

        $files = array_diff(scandir($ScanSource), array('..', '.','FAILED'));
        //print_r($files);

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
                    $this->split_pdf($ScanSource.$files[$key], 'tmp/'.$key.'/');
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
                $this->construct_pdf("tmp/".$key."/", $ResultDirectory);
                unlink($ScanSource.$files[$key]);
                rmdir("tmp/".$key."/");
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

    function split_pdf($filename, $end_directory = false)
    {
        include_once 'fpdf/fpdf.php';
        include_once 'fpdi/fpdi.php';
     
        $end_directory = $end_directory ? $end_directory : './';
        
        //print_r($end_directory);

        /*
         * Creation du repertoire split
         */
        if (!is_dir($end_directory)) {

            // Will make directories under end directory that don't exist (Provided that end directory exists and has the right permissions)
            mkdir($end_directory, 0755, true);
        }
        
        $pdf = new FPDI();

        //How manu pages ?
        $pagecount = $pdf->setSourceFile($filename);
        
        //Split each page of pdf file
        for ($i = 1; $i <= $pagecount; $i++) {
            $new_pdf = new FPDI();
            $new_pdf->AddPage();
            $new_pdf->setSourceFile($filename);
            $new_pdf->useTemplate($new_pdf->importPage($i));
            
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

    function construct_pdf($split_directory, $end_directory = false)
    {
        include_once 'fpdf/fpdf.php';
        include_once 'fpdi/fpdi.php';
        include_once 'qrReader/QrReader.php';
        
        $end_directory = $end_directory ? $end_directory : './';
        
        $new_pdf = new FPDI();
        
        $i=1;
        $z=0;
        $qr_label = '';
        while ($i!=0) {
            if (is_file($split_directory.$i.'.pdf')) {

                $file = $i.'.pdf';
                $next_filename = $i+1;

                //Attempt to extract QRCODE
                $qrcode = new QrReader($split_directory.$file);
                $text = $qrcode->text();

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

                } else if (empty($text) && !empty($qr_label)) {
                    //If not a separator, merge of each previous pdf
                    $isCourrier =true;
                    echo "This is a maarch document page ! Add page to pdf ...";
                    echo "\n";
                    $_SESSION['capture']->logEvent(
                        "This is a maarch document page ! Add page to pdf ..."
                    );
                    $new_pdf->AddPage();
                    $new_pdf->setSourceFile($split_directory.$file);    
                    $new_pdf->useTemplate($new_pdf->importPage(1));
                
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
                    $new_pdf->useTemplate($new_pdf->importPage(1));
                }

                /*
                 * Creation du pdf du separateur
                 */
                if ((($isCourrier == false && $old_label != '' && $text != '') || (!is_file($split_directory.$next_filename.'.pdf')))) {
                    // 
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

                    $new_pdf = new FPDI();
                }
                echo "Remove tmp file : ".$split_directory.$i.'.pdf';
                echo "\n";
                unlink($split_directory.$i.'.pdf');
            } else {
                break;
            }
            $text = '';
            $i++;
        }
    }
}
